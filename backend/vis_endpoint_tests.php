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
    test_standard_query(4);
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


// insert information you expect to get back under a specific deployment TODO: dates matter within year
function test_standard_query($test_size) {
    // form a json
    $transporter = new Transporter();
    $dbh = $transporter->dbConnectPdo();
    if ($dbh == null) {
        die('FAILURE: could not connect to db in test_standard_query');
    }

    $json = array();

    // insert values into table
    for ($i = 1; $i <= $test_size; $i++) {
        $today =  date('Y-m-d', time() - $i * 60 * 60 * 24);
        $today_str = '\'' . $today . '\'';
        $q = 'INSERT INTO tablet_data (device_id, start_date, number_of_files) VALUES (' . $i . ', ' . $today_str . ', ' . $i . ');';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            die('FAILURE: could not execute query: ' . $q . ' in test_standard_query');
        }
        $json[convert_to_ssepoch($today)] = $i;
    }

    $get_request = ['num_files' => 'true'];
    $result_json = main($get_request);
    if (dictionary_contained_in($result_json, $json)) {
        echo 'PASSED: test_standard_query';
    } else {
        echo 'FAILED: test_standard_query';
    }

    // delete inserted data
    for ($i = 1; $i <= $test_size; $i++) {
        $q = 'DELETE FROM tablet_data WHERE number_of_files = ' . $i . ';';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            echo('FAILURE: DELETE ' . $q . ' in test_standard_query');
        }
    }
}


function test_tablets_under_deployment_query($test_size) {
    $transporter = new Transporter();
    $dbh = $transporter->dbConnectPdo();
    if ($dbh == null) {
        die('FAILURE: could not connect to db in test_tablets_under_deployment_query');
    }

    $json = array();

    // insert values into table
    for ($i = 1; $i <= $test_size; $i++) {
        $q = 'INSERT INTO tablet_information (id, deployment_information_key, serial_id) VALUES (' . $i . ', 1, 1);';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            die('FAILURE: could not execute query: ' . $q . ' in test_tablets_under_deployment_query');
        }

        $today =  date('Y-m-d', time() - $i * 60 * 60 * 24);
        $today_str = '\'' . $today . '\'';
        $q = 'INSERT INTO tablet_data (device_id, start_date, number_of_files) VALUES (' . $i . ', ' . $today_str . ', ' . $i . ');';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            die('FAILURE: could not execute query: ' . $q . ' in test_tablets_under_deployment_query');
        }
        $json[convert_to_ssepoch($today)] = $i;
    }

    $get_request = ['num_files' => 'true', 'deployment_id' => '1'];
    $result_json = main($get_request);
    if (dictionary_contained_in($result_json, $json)) {
        echo 'PASSED: test_tablets_under_deployment_query';
    } else {
        echo 'FAILED: test_tablets_under_deployment_query';
    }

    // delete inserted data
    for ($i = 1; $i <= $test_size; $i++) {
        $q = 'DELETE FROM tablet_information WHERE id =' . $i . ' AND deployment_information_key = 1;';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            die('FAILURE: could not delete inserted information in test_tablets_under_deployment_query');
        }

        $q = 'DELETE FROM tablet_data WHERE number_of_files = ' . $i . ';';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            echo('FAILURE: DELETE ' . $q . ' in test_active_deployments_query');
        }
    }
}


function test_active_deployments_query($test_size) {
    // form a json along the way
    $types_of_data = ['num_probes', 'num_files'];
    $data_type = 'num_files';

    $transporter = new Transporter();
    $dbh = $transporter->dbConnectPdo();
    if ($dbh == null) {
        die('FAILURE: could not connect to db in test_active_deployments_query');
    }

    $json = array();

    // insert values into table
    for ($i = 1; $i <= $test_size; $i++) {
        // set active deployment
        $q = 'INSERT INTO deployment_information (is_active, deployment_id) VALUES (1,' . $i . ');';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            die('FAILURE: could not execute query:' . $q . ' in test_active_deployments_query');
        }


        $q = 'INSERT INTO tablet_information (id, deployment_information_key, serial_id) VALUES (' . $i . ',' . $i . ', 1);';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            die('FAILURE: could not execute query: ' . $q . ' in test_active_deployments_query');
        }

        $today =  date('Y-m-d', time() - $i * 60 * 60 * 24);  // TODO: might not be fine enough
        $today_str = '\'' . $today . '\'';
        $q = 'INSERT INTO tablet_data (device_id, start_date, number_of_files) VALUES (' . $i . ', ' . $today_str . ', ' . $i . ');';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            die('FAILURE: could not execute query: ' . $q . ' in test_active_deployments_query');
        }
        $json[(string)convert_to_ssepoch($today)] = (string)$i;
    }


    $get_request = [$data_type => 'true', 'data_for_all_active_deployments' => 'true'];
    $result_json = main($get_request);
    if (dictionary_contained_in($result_json, $json)) {
        echo 'PASSED: test_active_deployments_query';
    } else {
        echo 'FAILED: test_active_deployments_query';
    }

    // delete inserted data
    for ($i = 1; $i <= $test_size; $i++) {
        $q = 'DELETE FROM deployment_information WHERE deployment_id =' . $i . ';';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            echo('FAILURE: could not delete ');
        }

        $q = 'DELETE FROM tablet_information WHERE id = ' . $i . ' AND deployment_information_key = ' . $i . ';';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            echo('FAILURE: could not execute query: ' . $q . ' in test_active_deployments_query');
        }

        $q = 'DELETE FROM tablet_data WHERE number_of_files = ' . $i . ';';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            echo('FAILURE: DELETE ' . $q . ' in test_active_deployments_query');
        }
    }
}


function test_get_tablet_ids_under_deployment($test_size) {
    // 'SELECT id FROM tablet_information WHERE deployment_information_key=' . $deployment_id  . ';'
    // insert a much of tablets under a given deployment
    $transporter = new Transporter();
    $dbh = $transporter->dbConnectPdo();
    if ($dbh == null) {
        die('FAILURE: could not connect to db in test_get_tablet_ids_under_deployment');
    }

    // insert values into table
    for ($i = 1; $i <= $test_size; $i++) {
        $q = 'INSERT INTO tablet_information (id, deployment_information_key, serial_id) VALUES (' . $i . ', 1, 1);';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            die('FAILURE: could not execute query: ' . $q . ' in test_get_tablet_ids_under_deployment');
        }
    }

    $q = 'SELECT id FROM tablet_information WHERE deployment_information_key=1;';
    $statement = $dbh->prepare($q);
    $success = $statement->execute();
    if ($success == false) {
        die('FAILURE: could not execute query: ' . $q . ' in test_get_tablet_ids_under_deployment');
    }

    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    $expected_ids = array();
    foreach ($result as $row) {
        array_push($expected_ids, $row['id']);
    }

    $tablet_ids = get_tablet_ids_under_deployment(1);

    if (array_contains_values($expected_ids, $tablet_ids)) {
        echo 'PASSED: test_get_tablet_ids_under_deployment';
    } else {
        echo 'FAILED: test_get_tablet_ids_under_deployment';
    }

    // delete inserted data
    for ($i = 1; $i <= $test_size; $i++) {
        $q = 'DELETE FROM tablet_information WHERE id =' . $i . ' AND deployment_information_key = 1;';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            die('FAILURE: could not delete inserted information in test_get_tablet_ids_under_deployment');
        }
    }
}


function test_get_active_deployment_ids($test_size) {
    $transporter = new Transporter();
    $dbh = $transporter->dbConnectPdo();
    if ($dbh == null) {
        die('FAILURE: could not connect to db in test_get_active_deployment_ids');
    }

    // insert values into table
    for ($i = 1; $i <= $test_size; $i++) {
        $q = 'INSERT INTO deployment_information (is_active, deployment_id) VALUES (1,' . $i . ');';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            die('FAILURE: could not execute query:' . $q . ' in test_get_active_deployment_ids');
        }
    }

    $active_query = 'SELECT deployment_id FROM deployment_information WHERE is_active=1 AND deployment_id IS NOT NULL';
    $statement = $dbh->prepare($active_query);
    $success = $statement->execute();
    if ($success == false) {
        die('FAILURE: could not execute query:' . $active_query . ' in test_get_active_deployment_ids');
    }

    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    $expected_ids = array();
    foreach ($result as $row) {
        array_push($expected_ids, $row['deployment_id']);
    }

    $active_ids = get_active_deployment_ids();

    if (array_contains_values($expected_ids, $active_ids)) {
        echo 'PASSED: test_get_active_deployment_ids';
    } else {
        echo 'FAILED: test_get_active_deployment_ids';
    }

    for ($i = 1; $i <= $test_size; $i++) {
        $q = 'DELETE FROM deployment_information WHERE is_active = 1 AND deployment_id = ' . $i . ';';
        $statement = $dbh->prepare($q);
        $success = $statement->execute();
        if ($success == false) {
            die('FAILURE: could not delete inserted information in test_get_active_deployment_ids');
        }
    }
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


