<?php
/**
 * Created by PhpStorm.
 * User: jasonkrone
 * Date: 11/14/15
 * Time: 10:38 PM
 */

ini_set('display_errors', 1);
include_once("visualization_endpoint.php");
include_once("transporter.php");


function run_tests() {
    test_get_active_deployment_ids(10);
    test_get_tablet_ids_under_deployment(10);
    test_active_deployments_query(5);
    test_tablets_under_deployment_query(4);
    test_standard_query(4); // TODO: this will cap at days past year
    //test_all_data_query();
}


// TODO: allow to test on specific tablet id
// TODO: pick a range and give that to it in this
// TODO: let the data type be flexible
// insert information you expect to get back under a specific deployment TODO: dates matter within year
function test_standard_query($test_size) {
    $json = array();

    for ($i = 1; $i <= $test_size; $i++) {
        $today =  date('Y-m-d', time() - $i * 60 * 60 * 24); // dates incrementing backwards by one day
        $today_str = '\'' . $today . '\'';
        insert_values_under_fields_in_table([$i, $today_str, $i], ['device_id', 'start_date', 'number_of_files'], 'tablet_data');
        // default time range is a year TODO: pick a range and give that to it in this
        // if ($i <= 364) {
        $json[convert_to_ssepoch($today)] = $i;
    }

    $get_request = ['num_files' => 'true'];
    $result_json = main($get_request);
    if (dictionary_contained_in($result_json, $json)) {
        echo '<pre>' . 'PASSED: test_standard_query' . '</pre>';
    } else {
        echo '<pre>' . 'FAILED: test_standard_query' . '</pre>';
    }

    for ($i = 1; $i <= $test_size; $i++) {
        delete_row_from_table(['number_of_files' => $i], 'tablet_data');
    }
}


// TODO: let the data type be flexible
function test_tablets_under_deployment_query($test_size) {
    $json = array();
    $deployment_info_key = rand(1, $test_size);

    for ($i = 1; $i <= $test_size; $i++) {
        insert_values_under_fields_in_table([$i, $deployment_info_key, 1], ['id', 'deployment_information_key', 'serial_id'],
                                            'tablet_information');

        $today =  date('Y-m-d', time() - $i * 60 * 60 * 24); // dates incrementing backwards by one day
        $today_str = '\'' . $today . '\'';
        insert_values_under_fields_in_table([$i, $today_str, $i], ['device_id', 'start_date', 'number_of_files'], 'tablet_data');
        $json[convert_to_ssepoch($today)] = $i;
    }

    $get_request = ['num_files' => 'true', 'deployment_id' => $deployment_info_key];
    $result_json = main($get_request);
    if (dictionary_contained_in($result_json, $json)) {
        echo '<pre>' . 'PASSED: test_tablets_under_deployment_query' . '</pre>';
    } else {
        echo '<pre>' . 'FAILED: test_tablets_under_deployment_query' . '</pre>';
    }

    // delete inserted data
    for ($i = 1; $i <= $test_size; $i++) {
        delete_row_from_table(['id' => $i, 'deployment_information_key' => $deployment_info_key], 'tablet_information');
        delete_row_from_table(['number_of_files' => $i], 'tablet_data');
    }
}


// TODO: let the data type be flexible
function test_active_deployments_query($test_size) {
    $types_of_data = ['num_probes', 'num_files']; // TODO: add this in
    $data_type = 'num_files';
    $json = array();

    for ($i = 1; $i <= $test_size; $i++) {
        // insert active deployment with id $i
        insert_values_under_fields_in_table([1, $i], ['is_active', 'deployment_id'], 'deployment_information');

        // put a tablet under that deployment
        insert_values_under_fields_in_table([$i, $i, 1], ['id', 'deployment_information_key', 'serial_id'], 'tablet_information');

        // give that tablet data
        $today =  date('Y-m-d', time() - $i * 60 * 60 * 24); // dates incrementing backwards by one day
        $today_str = '\'' . $today . '\'';
        insert_values_under_fields_in_table([$i, $today_str, $i], ['device_id', 'start_date', 'number_of_files'], 'tablet_data');
        $json[(string)convert_to_ssepoch($today)] = (string)$i;
    }

    // TODO: let the data type be flexible
    $get_request = [$data_type => 'true', 'data_for_all_active_deployments' => 'true'];
    $result_json = main($get_request);
    if (dictionary_contained_in($result_json, $json)) {
        echo '<pre>' . 'PASSED: test_active_deployments_query' . '</pre>';
    } else {
        echo '<pre>' . 'FAILED: test_active_deployments_query' . '</pre>';
    }

    // delete inserted data
    for ($i = 1; $i <= $test_size; $i++) {
        delete_row_from_table(['deployment_id' => $i], 'deployment_information');

        delete_row_from_table(['id' => $i, 'deployment_information_key' => $i], 'tablet_information');

        delete_row_from_table(['number_of_files' => $i], 'tablet_data');
    }
}


function test_get_tablet_ids_under_deployment($test_size) {

    $deployment_info_key = rand(1, $test_size);

    for ($i = 1; $i <= $test_size; $i++) {
        insert_values_under_fields_in_table([$i, $deployment_info_key, 1], ['id', 'deployment_information_key', 'serial_id'],
                                            'tablet_information');
    }

    $tablets_under_deployment_query = 'SELECT id FROM tablet_information WHERE deployment_information_key=' . $deployment_info_key;
    $statement = execute_query($tablets_under_deployment_query);

    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    $expected_ids = array();
    foreach ($result as $row) {
        array_push($expected_ids, $row['id']);
    }

    // result to test
    $tablet_ids = get_tablet_ids_under_deployment($deployment_info_key);

    if (array_contains_values($expected_ids, $tablet_ids)) {
        echo '<pre>' . 'PASSED: test_get_tablet_ids_under_deployment' . '</pre>';
    } else {
        echo '<pre>' . 'FAILED: test_get_tablet_ids_under_deployment' . '</pre>';
    }

    // delete inserted data
    for ($i = 1; $i <= $test_size; $i++) {
        delete_row_from_table(['id' => $i, 'deployment_information_key' => $deployment_info_key], 'tablet_information');
    }
}


function test_get_active_deployment_ids($test_size) {

    for ($i = 1; $i <= $test_size; $i++) {
        insert_values_under_fields_in_table([1, $i], ['is_active', 'deployment_id'], 'deployment_information');
    }

    $active_query = 'SELECT deployment_id FROM deployment_information WHERE is_active=1 AND deployment_id IS NOT NULL';
    $statement = execute_query($active_query);
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);

    // collect active deployment ids
    $expected_ids = array();
    foreach ($result as $row) {
        array_push($expected_ids, $row['deployment_id']);
    }

    // function result to compare to
    $active_ids = get_active_deployment_ids();

    if (array_contains_values($expected_ids, $active_ids)) {
        echo '<pre>' . 'PASSED: test_get_active_deployment_ids' . '</pre>';
    } else {
        echo '<pre>' . 'FAILED: test_get_active_deployment_ids' . '</pre>';
    }

    for ($i = 1; $i <= $test_size; $i++) {
        delete_row_from_table(['is_active' => 1, 'deployment_id' => $i], 'deployment_information');
    }
}


function delete_row_from_table($conditions_dict, $table) {
    $i = 0;
    $count = count($conditions_dict);
    $conditions_str = '';
    foreach ($conditions_dict as $field => $value) {
        $conditions_str = $conditions_str . $field . ' = ' . (string) $value;
        if ($i < $count - 1) {
            $conditions_str = $conditions_str . ' AND ';
        }
        $i = $i + 1;
    }

    $query = 'DELETE FROM ' . $table . ' WHERE ' . $conditions_str . ';';
    execute_query($query);
}


function insert_values_under_fields_in_table($values, $fields, $table) {
    $value_str = make_list_str($values);
    $field_str = make_list_str($fields);
    $query = 'INSERT INTO ' . $table . ' ' . $field_str .  ' VALUES ' . $value_str . ';';
    execute_query($query);
}


// executes the given query, and returns the statement
function execute_query($query) {
    $transporter = new Transporter();
    $dbh = $transporter->dbConnectPdo();
    if ($dbh == null) {
        die('FAILURE: could not connect to db in execute_query');
    }

    $statement = $dbh->prepare($query);
    $success = $statement->execute();

    if ($success == false) {
        die('FAILURE: could not execute query: ' . $query . ' in execute_query');
    }

    return $statement;
}


function dictionary_contained_in($dict_a, $dict_b) {
    $does_contain = true;
    foreach ($dict_b as $key => $value) {
        if (isset($dict_a[$key]) == false OR $dict_a[$key] != $value) {
            $does_contain = false;
        }
    }
    return $does_contain;
}


// determines if the given array contains all of the given values
function array_contains_values($array, $values) {
    if (count(array_intersect($array, $values)) == count($values)) {
        return true;
    } else {
        return false;
    }
}

run_tests();


