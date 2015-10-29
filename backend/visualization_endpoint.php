<?php
/**
 * Created by PhpStorm.
 * User: jasonkrone
 * Date: 10/25/15
 * Time: 11:32 PM
 */

require_once("transporter.php");
require_once("jsonminify.php");

$table_fields = [0 => 'id', 1 => 'device_id', 2 => 'number_of_probes', 3 => 'server_data_uploaded_in_kb', 4 => 'server_data_clean_in_kb',
                 5 => 'file_data_uploaded_in_kb', 6 => 'file_data_clean_in_kb', 7 => 'number_of_files', 8 => 'start_date', 9 => 'end_date',
                 10 => 'created_on', 11 => 'modified_on'];

$_POST = ['deployment_id' => '20', 'num_probes' => '22', 'num_files' => '12', 'file_data_type' => 'clean', 'server_data_type' => 'clean'];

/*
 * 1) Work with a get request that can send you info globallit.org/heatmapdata?deploymentid=23&datatype=probes&daysofdata=120
 * - figure out how to parse that  DONE
 * 2) Connect with Database DONE
 * 3) Pull data from database ish
 * 4) Put the data into a json format DONE
 * 5) convert dates back into seconds since epoch (need to do this) DONE
 * 6) minimization DONE
 * 7) cache the data you get so that it is there http://php.net/manual/en/book.apc.php
 */
/*
Allow for date params to be passes in a GET request: starting date, ending date (Optional)
maximum number of days to return (from the current date). (ex. 100 days will return 100 days of data back from this date) (Optional)
The type of data to be returned: server data -clean/uploaded; file data clean/uploaded; number of files; and number of probes (Required)
Allow for a deployment ID to be passed that will only return data for that particular deployment. (Required)
Allow for a tablet ID to be passed that will only return data for that particular tablet (Optional)
*/


// optional
$starting_date = $_POST['starting_date'];
$ending_date = $_POST['ending_date'];
$max_days_before_now = $_POST['max_days_before_now'];
$tablet_id = $_POST['tablet_id'];

// requried
$server_data_type = $_POST['server_data_type']; // clean/uploaded
$file_data_type = $_POST['file_data_type'];
$num_files = $_POST['num_files'];
$num_probes = $_POST['num_probes'];
$deployment_id =$_POST['deployment_id'];

$table_fields_to_select = $table_fields;

// todo make clean a constant
if ($server_data_type == 'clean') {
    unset($table_fields_to_select[3]);
} elseif ($server_data_type == 'uploaded') {
    unset($table_fields_to_select[4]);
}

if ($file_data_type == 'clean') {
    unset($table_fields_to_select[5]);
} elseif ($file_data_type == 'uploaded') {
    unset($table_fields_to_select[6]);
}

$fields_to_select_str = '';
$length = count($table_fields_to_select);
$i = 0;

foreach ($table_fields_to_select as $field) {
    $fields_to_select_str = $fields_to_select_str . $field;
    if ($i < $length - 1) {
        $fields_to_select_str = $fields_to_select_str . ', ';
    }
    $i += 1;
}


// if they have starting date add it

// if they have ending date add it to query

// if they have tablet id or max days b4 now add it


$transporter = new Transporter();
$dbh = $transporter->dbConnectPdo();

$query = 'SELECT ' . $fields_to_select_str . ' FROM tablet_data WHERE number_of_files=' . $num_files .
          ' AND number_of_probes=' . $num_probes . ' AND id=' . $deployment_id . ' ;';

$statement = $dbh->prepare($query);
$success = $statement->execute();

if ($success == false) {
    echo "problem";
    die();
}

$result = $statement->fetchAll(PDO::FETCH_ASSOC);
$rows = array();

if ($result == false) {
    echo "problem2";
    die();
}


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

var_dump($result_json);


function convert_to_ssepoch($str) {
    if ($str == '0000-00-00') {
        return '0';
    } else {
        return strtotime($str);
    }
}


