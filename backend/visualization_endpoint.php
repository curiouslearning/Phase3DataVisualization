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

// TODO: get rid of after testing
$_GET = ['deployment_id' => '20', 'num_probes' => '22', 'num_files' => '12', 'file_data_type' => 'clean', 'server_data_type' => 'clean',
         'max_days_before_now' => '5', 'tablet_id' => '2'];

// TODO: put in config (not sure how since its an array
define('TABLE_FIELDS_WITHOUT_DATA_TYPES',
        serialize(['id', 'device_id', 'number_of_probes', 'number_of_files', 'start_date', 'end_date', 'created_on', 'modified_on']));


post_json_if_query_cached();
$table_fields_to_select = get_table_fields_to_select();
$field_str = make_table_field_str($table_fields_to_select);
$query = form_query($field_str);
$result = get_result($query);

$rows = array();
// convert dates to seconds since epoch
foreach ($result as $row) {
    $row['start_date'] = convert_to_ssepoch($row['start_date']);
    $row['end_date'] = convert_to_ssepoch($row['end_date']);
    $row['created_on'] = convert_to_ssepoch($row['created_on']);
    $row['modified_on'] = convert_to_ssepoch($row['modified_on']);
    $rows = array_merge($rows, $row);
}

$result_json = json_encode($rows);
$jsonMinify = new JSONMin($result_json);
$result_json = $jsonMinify->getMin();
$get_request_json = json_encode($_GET);
apc_add($get_request_json, $result_json);



function make_table_field_str($table_field_array) {
    $fields_to_select_str = '';
    $length = count($table_field_array);
    $i = 0;

    foreach ($table_field_array as $field) {
        $fields_to_select_str = $fields_to_select_str . $field;
        if ($i < $length - 1) {
            $fields_to_select_str = $fields_to_select_str . ', ';
        }
        $i += 1;
    }
    return $fields_to_select_str;
}


function get_table_fields_to_select() {
    $server_data_type = $_GET['server_data_type'];
    $file_data_type = $_GET['file_data_type'];

    $fields_to_select = unserialize(TABLE_FIELDS_WITHOUT_DATA_TYPES);

    if ($server_data_type == 'clean') {
        array_push($fields_to_select, 'server_data_clean_in_kb');
    } elseif ($server_data_type == 'uploaded') {
        array_push($fields_to_select, 'server_data_uploaded_in_kb');
    }

    if ($file_data_type == 'clean') {
        array_push($fields_to_select, 'file_data_clean_in_kb');
    } elseif ($file_data_type == 'uploaded') {
        array_push($fields_to_select, 'file_data_uploaded_in_kb');
    }
    return $fields_to_select;
}


function form_query($fields_to_select) {
    $num_files = $_GET['num_files'];
    $num_probes = $_GET['num_probes'];
    $deployment_id =$_GET['deployment_id'];

    $query = 'SELECT ' . $fields_to_select . ' FROM tablet_data WHERE number_of_files=' . $num_files .
        ' AND number_of_probes=' . $num_probes . ' AND id=' . $deployment_id;

    if (isset($_GET['starting_date'])) {
        $query = $query . ' AND start_date=' . $_GET['starting_date'];
    }

    if (isset($_GET['ending_date'])) {
        $query = $query . ' AND end_date=' . $_GET['ending_date'];
    }

    if (isset($_GET['tablet_id'])) {
        $query = $query . ' AND device_id=' . $_GET['tablet_id'];
    }

    if (isset($_GET['max_days_before_now'])) {
        $now = date('Y-m-d H:i:s');
        $days_before_now = (int)$_GET['max_days_before_now'];
        $date_days_before_now = time() - ($days_before_now * 24 * 60 * 60);
        $datetime_days_before_now = date('Y-m-d H:i:s', $date_days_before_now);
        $query = $query . ' AND created_on BETWEEN ' . '\'' . $datetime_days_before_now . '\'' . ' AND ' . '\'' . $now . '\'';
    }

    $query = $query . ';';
    return $query;
}


function get_result($query) {
    $transporter = new Transporter();
    $dbh = $transporter->dbConnectPdo();
    if ($dbh == null) {
        // error connecting to database
        echo 'error connecting to db';
        return null;
    }

    $statement = $dbh->prepare($query);
    $success = $statement->execute();

    if ($success == false) {
        echo "problem";
        return null;
    }

    $result = $statement->fetchAll(PDO::FETCH_ASSOC);

    if ($result == false) {
        echo "didn't recieve any data";
        return null;
    }

    return $result;
}


function post_json_if_query_cached() {
    // check if the $json version of the post is in the cache
    $get_request_json = json_encode($_GET);
    $data_json = apc_fetch($get_request_json);

    if ($data_json == true) {
        echo "its here";
        post_json($data_json);
    }
}

function post_json($json) {
    echo "what do it do here";
}


function convert_to_ssepoch($str) {
    if ($str == '0000-00-00') {
        return '0';
    } else {
        return strtotime($str);
    }
}
