<?php
/**
 * Created by PhpStorm.
 * User: jasonkrone
 * Date: 10/25/15
 * Time: 11:32 PM
 */

require_once("transporter.php");
require_once("jsonminify.php");
require_once("vis_backend.config.php");

// gzip output buffer
ob_start('ob_gzhandler');

// TODO: get rid of after testing
$_GET = ['deployment_id' => '20', 'num_probes' => '22', 'num_files' => '12', 'file_data_type' => 'clean', 'server_data_type' => 'clean',
         'max_days_before_now' => '5', 'tablet_id' => '2'];

// TODO: put in config (not sure how since its an array
define('TABLE_FIELDS_WITHOUT_DATA_TYPES',
        serialize(['id', 'device_id', 'number_of_probes', 'number_of_files', 'start_date', 'end_date', 'created_on', 'modified_on']));

define('FILE_DATA_CLEAN', 'file_data_clean_in_kb');
define('FILE_DATA_UPLOADED', 'file_data_uploaded_in_kb');
define('SERVER_DATA_CLEAN', 'server_data_clean_in_kb');
define('SERVER_DATA_UPLOADED', 'server_data_uploaded_in_kb');
define('DEFAULT_DATE_RANGE_IN_DAYS', '365');
define('DEFAULT_DATE_RANGE_FIELD', 'created_on');
define('JSON_DATE_KEY_FIELD', 'start_date');


$_GET = ['server_data' => 'clean'];


// set the default range to be the past one year.
// BIT flag for all of the data = ALL DATA = ALL years of data.... not present assume that it is false
// option where user could set a bit flag where user can get tables from active deployement
// look up in other table get deoplyment grab all tablets under that deployement
// 1430974800: start state .... number of files
// they will just be requesting one thing ... fix that
// SET TIME LIMIT ON APC


function main() {
    /*
    $posted = post_json_if_query_cached();
    if ($posted == true) {
        return;
    }
    */

    $query = form_query();
    echo '<pre>'.var_dump($query).'</pre>';
    $result = get_result($query);
    echo '<pre>'.var_dump($result).'</pre>';


    $data = array();
    foreach ($result as $row) {
        $values = array_values($row);
        $ssepoch = convert_to_ssepoch($values[1]);
        // TODO: ssepoch is not going to be unique
        $data[$ssepoch] = (int)$values[0];
    }
    echo '<pre>'.var_dump($data).'</pre>';

    /*
    $result_json = json_encode($data);
    $json_minify = new JSONMin($result_json);
    $result_json = $json_minify->getMin();
    $get_request_json = json_encode($_GET);
    apc_add($get_request_json, $result_json);
    post_json($result_json);
    */
}


function form_query() {
    $query = null;
    if (isset($_GET['all_data'])) {
        $query = 'SELECT ' . get_table_fields_to_select() . ' FROM tablet_data;';
    } elseif ($_GET['data_for_all_active_deployments']) {
        $query = get_query_for_active_deployments();
    } elseif (isset($_GET['deployment_id'])) {
        $query = get_query_for_deployment_with_id($_GET['deployment_id']);
    } else {
        $query = get_standard_query();
    }
    return $query;
}


function get_standard_query() {
    $query = 'SELECT ' . get_table_fields_to_select() . ' FROM tablet_data WHERE' .  get_date_range_query_str();

    // extra qualifying fields to add to query
    if (isset($_GET['starting_date'])) {
        $query = $query . ' AND start_date=' . $_GET['starting_date'];
    }

    if (isset($_GET['ending_date'])) {
        $query = $query . ' AND end_date=' . $_GET['ending_date'];
    }

    if (isset($_GET['tablet_id'])) {
        $query = $query . ' AND device_id=' . $_GET['tablet_id'];
    }

    $query = $query . ';';
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


function get_query_for_deployment_with_id($deployment_id) {
    $query = 'SELECT ' . get_table_fields_to_select() . ' FROM tablet_data WHERE';
    $ids = get_tablet_ids_under_deployment($deployment_id);
    if ($ids == null) {
        die('problem');
    }
    $id_list_str = make_list_str($ids);
    $query = $query . 'device_id IN ' . $id_list_str . ';';
    return $query;
}


function get_query_for_active_deployments() {
    $query = 'SELECT ' . get_table_fields_to_select() . ' FROM tablet_data WHERE ';

    $deployment_ids = get_active_deployment_ids();
    if ($deployment_ids == null) {
        die('problem');
    }

    $tablet_ids = array();
    foreach ($deployment_ids as $id) {
        $ids_for_deployment = get_tablet_ids_under_deployment($id);
        if ($ids_for_deployment != null) {
            array_merge($tablet_ids, $ids_for_deployment);
        }
    }

    $id_list_str = make_list_str($tablet_ids);
    $query = $query . 'device_id IN ' . $id_list_str . ';';
    return $query;
}


function get_active_deployment_ids() {
    $active_query = 'SELECT deployment_id FROM deployment_information WHERE is_active=1';
    $deployment_ids = get_result($active_query);
    if ($deployment_ids == null) {
        return null;
    }
    foreach ($deployment_ids as $row) {
        array_push($deployment_ids, $row['deployment_id']);
    }
    return $deployment_ids;
}


function make_list_str($array) {
    echo '<pre>'.var_dump($array).'</pre>';
    $list_str = '(';
    $length = count($array);
    $i = 0;
    foreach ($array as $element) {
        $list_str = $list_str . $element;
        if ($i < $length - 1) {
            $list_str = $list_str . ', ';
        }
        $i += 1;
    }
    $list_str = $list_str . ')';
    return $list_str;
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

    // TODO: should I check for some kind of value with numprobes, numfiles,.. etc
    if (isset($_GET['server_data']) And $_GET['server_data'] == 'clean') {
        $table_fields = SERVER_DATA_CLEAN;
    } elseif (isset($_GET['server_data']) And $_GET['server_data'] == 'uploaded') {
        $table_fields = SERVER_DATA_UPLOADED;
    } elseif (isset($_GET['file_data']) And $_GET['file_data'] == 'clean') {
        $table_fields = FILE_DATA_CLEAN;
    } elseif (isset($_GET['file_data']) And $_GET['file_data'] == 'uploaded') {
        $table_fields = FILE_DATA_UPLOADED;
    } elseif (isset($_GET['num_probes'])) {
        $table_fields = 'number_of_probes';
    } elseif (isset($_GET['num_files'])) {
        $table_fields = 'number_of_files';
    } else {
        die('required field in POST not given');
    }

    $table_fields = $table_fields . ', ' . JSON_DATE_KEY_FIELD;

    return $table_fields;
}


function get_result($query) {
    $transporter = new Transporter();
    $dbh = $transporter->dbConnectPdo();
    if ($dbh == null) {
        // error connecting to database
        return null;
    }

    $statement = $dbh->prepare($query);
    $success = $statement->execute();

    if ($success == false) {
        // error executing query
        return null;
    }

    $result = $statement->fetchAll(PDO::FETCH_ASSOC);

    if ($result == false) {
        // no results to fetch
        return null;
    }

    return $result;
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


function post_json($json) {
    header('Content-Type: application/json');
    echo $json;
}


function convert_to_ssepoch($str) {
    if ($str == '0000-00-00') {
        return '0';
    } else {
        return strtotime($str);
    }
}


main();