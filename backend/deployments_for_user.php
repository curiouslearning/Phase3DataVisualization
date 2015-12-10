<?php
/*
 * Created by Jason Krone
 * for Curious Learning
 */

include_once("backend_utils.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);


function main() {
    if (isset($_GET['deployment_names']) AND $_GET['deployment_names'] == 'true') {
        $deployment_names = get_name_of_deployments_for_user_in_session();
        post_data(json_encode($deployment_names));
    } elseif (isset($_GET['deployment_ids']) AND $_GET['deployment_ids'] == 'true') {
        $deployment_ids = get_deployment_ids_for_user_in_session();
        post_json(json_encode($deployment_ids));
    }
}


// returns an array containing the names of all the deployments associated
// with the user currently in session
function get_name_of_deployments_for_user_in_session() {
    $deployment_ids = get_deployment_ids_for_user_in_session();

    $deployment_names = array();
    foreach($deployment_ids as $id) {
        $query = 'SELECT location FROM deployment_information WHERE deployment_id = ' . $id;
        $result = get_result($query);
        if ($result == null) {
            array_push($deployment_names, 'Deployment Id: ' . $id);
        } else {
            array_push($deployment_names, $result[0]['location']);
        }
    }

    return $deployment_names;
}


// returns an array containing the ids of all the deployments associated
// with the user currently in session
function get_deployment_ids_for_user_in_session() {
    $user_id = get_id_of_user_in_session();

    // get all deployments linked to the user currently in session
    $query = 'SELECT deployment_id FROM deployment_mapping WHERE user_id=' . $user_id . ';';
    $result = get_result($query);

    if ($result == null) {
        die('error: no deployments linked to current user');
    }

    $deployment_ids = array();
    foreach ($result as $row) {
        array_push($deployment_ids, $row['deployment_id']);
    }

    return $deployment_ids;
}


// TODO: get user id from session info
function get_id_of_user_in_session() {
    return 7;
}


main();
