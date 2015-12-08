<?php
/*
 * Created by Jason Krone
 * for Curious Learning
 */

ob_start('ob_gzhandler');
include_once("transporter.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);


// TODO: get user id from session info
function get_id_of_user_currently_in_session() {
    return 7;
}

function post_deployments_for_user() {
    $user_id = get_id_of_user_currently_in_session();
    $transporter = new Transporter();
    $dbh = $transporter->dbConnectPdo();

    if ($dbh == null) {
        die('error connecting to database');
    }

    // get all deployments linked to the user currently in session
    $query = 'SELECT deployment_id FROM deployment_mapping WHERE user_id=' . $user_id . ';';
    $statement = $dbh->prepare($query);
    $success = $statement->execute();

    if($success == false) {
        die('error could not execute query');
    }

    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    if ($result == false) {
        die('error no results returned for query');
    }

    $json_data = array();
    foreach ($result as $row) {
        array_push($json_data, $row['deployment_id']);
    }

    // echo json containing deployment_ids for user
    $result_json = json_encode($json_data);
    header('Content-Type: application/json');
    echo $result_json;
}

post_deployments_for_user();