<?php
/**
 * Created by PhpStorm.
 * User: jasonkrone
 * Date: 12/8/15
 * Time: 11:20 PM
 */


ob_start('ob_gzhandler');
include_once("transporter.php");

// forms a list string for the given array ex: (item1, item2, item3)
// assumes that the input in not null
function make_list_str($array) {
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


// returns the result for the given query
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


// posts the given data as a json
function post_json($json) {
    header('Content-Type: application/json');
    echo $json;
}
