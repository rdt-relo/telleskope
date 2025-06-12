<?php
require_once __DIR__ .'/../init.php';
require_once __DIR__ .'/../mysql/MySQLTrait.php';
$dblog_conn = null;
$dblog_ro_conn = null;

/**
 * Class DBLog
 * Base class for DBLog
 */
class DBLog
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
        global $dblog_conn;
        if (!$dblog_conn) {

            $max_retries = 7;
            $current_try = 0;
            $retry_interval = 0;
            // Since we may run out of connections on DBLog RW instance due to long running connections
            // we will try for sometime before giving up.
            while ($current_try++ < $max_retries) {
                $dblog_conn = mysqli_connect(DBLOG_HOST, DBLOG_USER, DBLOG_PASSWORD);
                if ($dblog_conn) {
                    break; // Connection established, skip retries
                }
                $retry_interval += rand($retry_interval,$retry_interval + 10);
                Logger::Log("DBLog: Unable to get a connection, will try again in {$retry_interval} seconds (retry no {$current_try})", Logger::SEVERITY['WARNING_ERROR']);
                sleep($retry_interval);
            }

            if (!$dblog_conn || !mysqli_select_db($dblog_conn, DBLOG_NAME)) {
                Logger::Log("DBLog: Fatal Error connecting to DBLog, may have impacted Jobs!!!");
                die(mysqli_connect_error());
            }
            $ctx =  isset($_SESSION) ? (($_SESSION['companyid'] ?? '') . '|' . ($_SESSION['context_userid'] ?? '')) : '';
            mysqli_query($dblog_conn, "SET SESSION sql_mode = '';#W #C= ".$ctx);
        }
        return $dblog_conn;
    }
    private static function GetROConnection() : mysqli
    {
        global $dblog_ro_conn;
        if (!$dblog_ro_conn) {
            $dblog_ro_conn = mysqli_connect(DBLOG_RO_HOST, DBLOG_USER, DBLOG_PASSWORD);
            if (!$dblog_ro_conn || !mysqli_select_db($dblog_ro_conn, DBLOG_NAME)) {
                Logger::Log("DBLog: Fatal Error connecting to DBLog Read Only, may have impacted Jobs!!!");
                die(mysqli_connect_error());
            }
            $ctx =  isset($_SESSION) ? (($_SESSION['companyid'] ?? '') . '|' . ($_SESSION['context_userid'] ?? '')) : '';
            mysqli_query($dblog_ro_conn, "SET SESSION sql_mode = '';#R #C= ".$ctx);
        }
        return $dblog_ro_conn;
    }
    // Trait setup complete



    /**
     * @param string $stmt
     * @return int -1 on error. If this was insert then insert id if one exists or no of rows impacted on update.
     */
    protected static function DBUpdateNoDie(string $stmt) : int
    {
        $db_conn = self::GetRWConnection();
        $result = -1;
        $query = mysqli_query($db_conn, $stmt);
        if ($query) {
            $result = mysqli_insert_id($db_conn) ?: mysqli_affected_rows($db_conn) ?: 0;
        } else {
            Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($db_conn), 'sql_stmt'=> $stmt]);
        }
        return $result;
    }
}