<?php
/**
 * Created by PhpStorm.
 * User: jasonkrone
 * Date: 10/25/15
 * Time: 11:32 PM
 */

include_once("transporter.php");
include_once("jsonminify.php");
include_once("backend_utils.php");

// gzip output buffer
//ob_start('ob_gzhandler');
error_reporting(E_ALL);
ini_set('display_errors', 1);


// tablet data fields that can be requested
define('FILE_DATA_CLEAN_FIELD', 'file_data_clean_in_kb');
define('FILE_DATA_UPLOADED_FIELD', 'file_data_uploaded_in_kb');
define('SERVER_DATA_CLEAN_FIELD', 'server_data_clean_in_kb');
define('SERVER_DATA_UPLOADED_FIELD', 'server_data_uploaded_in_kb');
define('NUMBER_OF_PROBES_FIELD', 'number_of_probes');
define('NUMBER_OF_FILES_FIELD', 'number_of_files');

// special requests
define('ALL_DATA_PARAM', 'all_data');
define('ACTIVE_DEPLOYMENTS_PARAM', 'all_active_deployments');
define('DATA_UNDER_DEPLOYMENT_PARAM', 'deployment_id');

// how many days of data to return if no range is given
define('DEFAULT_DATE_RANGE_IN_DAYS', '365');
define('DEFAULT_DATE_RANGE_FIELD', 'start_date');
define('JSON_DATE_KEY_FIELD', 'start_date');

//define('TEST_MODE', 'false');


$_GET = ['number_of_probes' => 'true', 'deployment_id' => 22];

function main($test_request) {
    if (TEST_MODE == 'true') {
        $_GET = $test_request;
    } else {
        $posted = post_json_if_query_cached();
        if ($posted == true) {
            return;
        }
    }

    $query = form_query();

    if ($query == null) {
        die('could not form query from request');
    }
echo($query);
    $result = get_result($query);
    if ($result == null) {
        die('query returned no data');
    }

    // TODO: ssepoch is not going to be unique
    $data = array();
    foreach ($result as $row) {
        $values = array_values($row);
        $ssepoch = convert_to_ssepoch($values[1]);
        $data[$ssepoch] = (int)$values[0];
    }
    if (TEST_MODE == 'true') {
        return $data;
    }


    $result_json = json_encode($data);
    $json_minify = new JSONMin($result_json);
    $result_json = $json_minify->getMin();
    $get_request_json = json_encode($_GET);
    $time_to_live = strtotime('tomorrow 00:00:00') - time(); // until midnight
    apc_add($get_request_json, $result_json, $time_to_live);
    post_json($result_json);
}


function form_query() {
    $query = null;
    if (isset($_GET[ALL_DATA_PARAM]) And $_GET[ALL_DATA_PARAM] == true) {
        $query = 'SELECT ' . get_table_fields_to_select() . ' FROM tablet_data;';
    } elseif (isset($_GET[ACTIVE_DEPLOYMENTS_PARAM]) And $_GET[ACTIVE_DEPLOYMENTS_PARAM] == true) {
        $query = get_query_for_active_deployments();
    } elseif (isset($_GET[DATA_UNDER_DEPLOYMENT_PARAM])) {
        $query = get_query_for_tablets_under_deployment($_GET[DATA_UNDER_DEPLOYMENT_PARAM]);
    } else {
        $query = get_standard_query();
    }
    return $query;
}


function get_standard_query() {
    $append = false;

    $query = 'SELECT ' . get_table_fields_to_select() . ' FROM tablet_data WHERE ';

    if (isset($_GET['start_date']) == false And isset($_GET['end_date']) == false And
        isset($_GET['tablet_id']) == false) {
        $query = $query . get_date_range_query_str();
    }

    if (isset($_GET['start_date'])) {
        $query = $query . 'start_date=' . '\'' . $_GET['start_date'] . '\'';
        $append = true;
    }

    if (isset($_GET['end_date'])) {
        if ($append == true) {
            $query = $query . ' AND ';
        }
        $query = $query . 'end_date=' . '\'' . $_GET['end_date'] . '\'';
        $append = true;
    }

    if (isset($_GET['tablet_id'])) {
        if ($append == true) {
            $query = $query . ' AND ';
        }
        $query = $query . 'device_id=' .  $_GET['tablet_id'];
    }

    $query = $query . ';';
    return $query;
}


function get_query_for_tablets_under_deployment($deployment_id) {
    $query = 'SELECT ' . get_table_fields_to_select() . ' FROM tablet_data WHERE ';
    $tablet_ids = get_tablet_ids_under_deployment($deployment_id);

    if ($tablet_ids == null) {
        return null;
    }

    $id_list_str = make_list_str($tablet_ids);
    $query = $query . 'device_id IN ' . $id_list_str . ' AND ' . get_date_range_query_str() . ';';
    return $query;
}


function get_query_for_active_deployments() {
    $query = 'SELECT ' . get_table_fields_to_select() . ' FROM tablet_data WHERE ';

    $active_deployment_ids = get_active_deployment_ids();
    if ($active_deployment_ids == null) {
        // there are no active deployments
        return null;
    }

    // collect ids of tablets at active deployments
    $tablet_ids = array();
    foreach ($active_deployment_ids as $deployment_id) {
        $ids = get_tablet_ids_under_deployment($deployment_id);
        if ($ids != null) {
            $tablet_ids = array_merge($tablet_ids, $ids);
        }
    }

    if (count($tablet_ids) == 0) {
        // there are no tablets at active deployments, nothing to query
        return null;
    }

    $id_list_str = make_list_str($tablet_ids);
    $query = $query . 'device_id IN ' . $id_list_str . ';';
    return $query;
}


function get_tablet_ids_under_deployment($deployment_id) {
    $id_list = array();
    $deployment_query = 'SELECT id FROM tablet_information WHERE deployment_information_key=' . $deployment_id  . ';';
    $tablet_ids = get_result($deployment_query);

    if ($tablet_ids == null) {
        return null;
    }

    foreach ($tablet_ids as $row) {
        array_push($id_list, $row['id']);
    }

    return $id_list;
}


// returns array of active deployment ids or null if there are no active deployments
function get_active_deployment_ids() {
    $active_ids = array();
    $active_query = 'SELECT deployment_id FROM deployment_information WHERE is_active=1 AND deployment_id IS NOT NULL';
    $result = get_result($active_query);
    if ($result == null) {
        return null;
    }

    foreach ($result as $row) {
        array_push($active_ids, $row['deployment_id']);
    }

    return $active_ids;
}


function get_date_range_query_str() {
    $days_before_now = null;

    if (isset($_GET['max_days_before_now'])) {
        $days_before_now = (int)$_GET['max_days_before_now'];
    } else {
        $days_before_now = (int)DEFAULT_DATE_RANGE_IN_DAYS;
    }
    $date_days_before_now = time() - ($days_before_now * 24 * 60 * 60);
    $datetime_days_before_now = date('Y-m-d H:m:s', $date_days_before_now);
    $now = date('Y-m-d H:m:s');
    return DEFAULT_DATE_RANGE_FIELD . ' BETWEEN ' . '\'' . $datetime_days_before_now . '\'' . ' AND ' . '\'' . $now . '\'';
}


function get_table_fields_to_select() {
    $table_fields = '';

    if (isset($_GET['server_data_clean']) And $_GET['server_data_clean'] == 'true') {
        $table_fields = SERVER_DATA_CLEAN_FIELD;
    } elseif (isset($_GET['server_data_uploaded']) And $_GET['server_data_uploaded'] == 'true') {
        $table_fields = SERVER_DATA_UPLOADED_FIELD;
    } elseif (isset($_GET['file_data_clean']) And $_GET['file_data_clean'] == 'true') {
        $table_fields = FILE_DATA_CLEAN_FIELD;
    } elseif (isset($_GET['file_data_uploaded']) And $_GET['file_data_uploaded'] == 'true') {
        $table_fields = FILE_DATA_UPLOADED_FIELD;
    } elseif (isset($_GET['number_of_probes']) And $_GET['number_of_probes'] == 'true') {
        $table_fields = NUMBER_OF_PROBES_FIELD;
    } elseif (isset($_GET['number_of_files']) And $_GET['number_of_files'] == 'true') {
        $table_fields = NUMBER_OF_FILES_FIELD;
    } else {
        die('required field in POST not given');
    }

    $table_fields = $table_fields . ', ' . JSON_DATE_KEY_FIELD;

    return $table_fields;
}


function post_json_if_query_cached() {
    // check if the json version of the post is in the cache
    $get_request_json = json_encode($_GET);
    $data_json = apc_fetch($get_request_json);
    $was_posted = $data_json;

    if ($data_json == true) {
        post_json($data_json);
    }
    return $was_posted;
}


function convert_to_ssepoch($str) {
    if ($str == '0000-00-00') {
        return '0';
    } else {
        return strtotime($str);
    }
}


if (TEST_MODE != 'true') {
    main(null);
}
