<?php
if(!isset($_SESSION))
{
    session_start();
}
require_once("db.config.php");
class Transporter {
    function checkLogin($username, $password)
    {
        // Hashing the password with its hash as the salt returns the same hash
        if ( crypt($password, SALT) === $this->GetPasswdHash($username) )
        {
            $_SESSION['authenticated'] = true;
            return true;
        }
        else
        {
            return false;
        }
    }
    function getUserName($username, $password)
    {
        $db = $this->dbConnect();
        $mainquery = "select retrieve_user_credentials('$username', '$password');";
        $result = mysql_query($mainquery);
        if (!$result) {
            $message  = 'Invalid query: ' . mysql_error() . "\n";
            $message .= 'Whole query: ' . $mainquery;
            die($message);
        }
        else
        {
            $db_field = mysql_fetch_assoc($result);
            if(!empty($db_field[0]))
            {
                var_dump($db_field);
                mysql_close($db);
                $_SESSION['fullName'] = explode("~", $db_field[1]);
                $_SESSION['accessLevel'] = explode("~", $db_field[0]);
                $_SESSION['userId'] = explode("~", $db_field[2]);
                return array("fullName"=>explode("~", $db_field[1]), "accessLevel"=>explode("~", $db_field[0]));
            }
            else
            {
                mysql_close($db);
                return false;
            }
        }
    }
    function userLogin($username, $password)
    {
        $db = $this->dbConnect();
        $saltedPassword = crypt($password, SALT);
        $mainquery = "select retrieve_user_credentials('$username', '$saltedPassword');";
        $result = mysql_query($mainquery);
        if (!$result) {
            $message  = 'Invalid query: ' . mysql_error() . "\n";
            $message .= 'Whole query: ' . $mainquery;
            die($message);
        }
        else
        {
            $db_field = mysql_fetch_array($result);
            if(!empty($db_field[0]))
            {
                mysql_close($db);
                $_SESSION['fullName'] = explode("~", $db_field[0])[0];
                $_SESSION['accessLevel'] = explode("~", $db_field[0])[1];
                $_SESSION['userId'] = explode("~", $db_field[0])[2];
                $_SESSION['districtId'] = explode("~", $db_field[0])[3];
                $_SESSION['schoolId'] = explode("~", $db_field[0])[4];
                $_SESSION['classId'] = explode("~", $db_field[0])[5];
                $_SESSION['groupId'] = explode("~", $db_field[0])[6];
                $_SESSION['authenticated'] = true;
                return true;
            }
            else
            {
                $_SESSION['authenticated'] = false;
                mysql_close($db);
                return false;
            }
        }
    }
    function dbConnect()
    {
        //Connect to the database
        $db = mysql_connect(DB_HOST, DB_USERNAME, DB_PASSWORD);
        if (!$db) {
            die("Internal Server Error");
        }
        $er = $er = mysql_select_db(DB_DATABASE);
        if(!$er)
            die("Couldn't find the DB!");
        return $db;
    }
    function getPasswdHash($username) {
        $db = $this->dbConnect();
        $mainquery = "SELECT * from user_credentials where username='$username';";
        $result = mysql_query($mainquery);
        if (!$result) {
            $message  = 'Invalid query: ' . mysql_error() . "\n";
            $message .= 'Whole query: ' . $mainquery;
            die($message);
        }
        else
        {
            $db_field = mysql_fetch_assoc($result);
            if(!empty($db_field['passwd']))
            {
                mysql_close($db);
                return $db_field["passwd"];
            }
        }
    }
    function sendQuery($query)
    {
        $db = $this->dbConnect();
        $result =  mysql_query($query);
        mysql_close($db);
        return $result;
    }
    function createUser($username, $password, $accessLevel, $fullName, $location, $deploymentId, $country, $schoolManager)
    {
        $PasswdHash = crypt($password, SALT);
        $createdBy = $_SESSION['userId'];
        if($schoolManager)
        $query = "insert into user_credentials".
            "(username, passwd, access_level, full_name, location, created_date, modified_date, deployment_information_key, country_code, created_by, district_id, school_id, class_id, group_id, )
            values ('$username', '$PasswdHash','$accessLevel','$fullName','$location', now(), now(), '$deploymentId', '$country', $createdBy, );";
        $result = $this->sendQuery($query);
        return true;
    }
    function updatePassword($username, $currentPassword, $newPassword)
    {
        $newPasswdHash = crypt($newPassword, SALT);
        $currentPasswordHash = crypt($currentPassword, SALT);
        $query = "select update_password('$username', '$currentPasswordHash', '$newPasswdHash');";
        $result = $this->sendQuery($query);
        if(!$result)
            return "Error in processing request";
        $result = mysql_fetch_array($this->sendQuery($query));
        return $result[0];
    }
    function addSSHKeys($privateKey, $publicKey, $username, $serialId)
    {
        $query = "insert into ssh_keys (username, private_key, public_key, serial_id, date_created, date_modified)
                values ('$username', '$privateKey', '$publicKey', '$serialId', now(), now())";
        $this->sendQuery($query);
    }
    function checkSerialId($serialId)
    {
        $query = "SELECT check_for_serial_id('$serialId')";
        return  mysql_fetch_array($this->sendQuery($query));
    }
    function insertHash($publicKey, $serialId, $creator)
    {
        $query = "Insert into tablet_connection values ( '', '$publicKey', '$creator', now(), now(), '$serialId');";
        $this->sendQuery($query);
    }
    function checkForLabel($serialIdToCheck)
    {
        $query = "SELECT check_for_label('$serialIdToCheck')";
        return mysql_fetch_array($this->sendQuery($query));
    }
    function updateLabel($serialId, $label, $userId, $tabletOption, $usingRaspberryPi)
    {
        $query = "select insert_label_serialID_tablet_information('$serialId', '$label', '$userId', '$tabletOption', '$usingRaspberryPi'')";
        return mysql_fetch_array($this->sendQuery($query));
    }
    function log($msg, $isError = false, $errorLevel = 0)
    {
        date_default_timezone_set("America/New_York");
        $query = "INSERT INTO php_log(error_description, is_error, error_level, creation_date) VALUES('" . str_replace("'", "`",$msg) . "', $isError, $errorLevel,'". date('Y-m-d H:i:s') . "');";
        $this->sendQuery($query);
    }
    function dbConnectPdo()
    {
        try
        {
            $dbh = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_DATABASE,
                DB_USERNAME, DB_PASSWORD);
            return $dbh;
        }
        catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            return null;
        }
    }
}//End class