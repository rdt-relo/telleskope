<?php
require_once __DIR__ .'/../init.php';
require_once __DIR__ .'/../mysql/MySQLTrait.php';
$dbpoints_conn = null;
$dbpointsro_conn = null;

/**
 * Class DBPoints
 * Base class for DBPoints
 */
class DBPoints
{
    protected $fields;

    protected function __construct(array $fields)
    {
        $this->fields = $fields;
    }


    // Add MySql Trait and define the conditions required
    use MySQLTrait;
    private static function GetRWConnection() : mysqli
    {
        // Since currently we do not have a seperate database for points, we will reuse the connection we have with affinities DB
        return GlobalGetDBConnection();
        // The rest of the code is not used yet, once we have dedicated db for points program the rest of code will come into play

        global $dbpoints_conn;
        if (!$dbpoints_conn) {
            $dbpoints_conn = mysqli_connect(DBPOINTS_HOST, DBPOINTS_USER, DBPOINTS_PASSWORD);
            if (!$dbpoints_conn || !mysqli_select_db($dbpoints_conn, DBPOINTS_NAME)) {
                Logger::Log("DBPoints: Fatal Error connecting to DBPoints, may have impacted Jobs!!!");
                die(mysqli_connect_error());
            }
            $ctx =  isset($_SESSION) ? (($_SESSION['companyid'] ?? '') . '|' . ($_SESSION['context_userid'] ?? '')) : '';
            mysqli_query($dbpoints_conn, "SET SESSION sql_mode = '';#W #C= ".$ctx);
        }
        return $dbpoints_conn;
    }

    private static function GetROConnection() : mysqli
    {
        // Since currently we do not have a seperate database for points, we will reuse the connection we have with affinities DB
        return GlobalGetDBROConnection();
        // The rest of the code is not used yet, once we have dedicated db for points program the rest of code will come into play

        global $dbpointsro_conn;
        if (!$dbpointsro_conn) {
            $dbpointsro_conn = mysqli_connect(DBPOINTS_RO_HOST, DBPOINTS_USER, DBPOINTS_PASSWORD);
            if (!$dbpointsro_conn || !mysqli_select_db($dbpointsro_conn, DBPOINTS_NAME)) {
                Logger::Log("DBPoints: Fatal Error connecting to DBROPoints, may have impacted Jobs!!!");
                die(mysqli_connect_error());
            }
            $ctx =  isset($_SESSION) ? (($_SESSION['companyid'] ?? '') . '|' . ($_SESSION['context_userid'] ?? '')) : '';
            mysqli_query($dbpointsro_conn, "SET SESSION sql_mode = '';#W #C= ".$ctx);
        }
        return $dbpointsro_conn;
    }
    // Trait setup complete
}