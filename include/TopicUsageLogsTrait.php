<?php

// Do no use require_once as this class is included in Company.php.
enum TopicUsageLogsActionType : string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case READ = 'read';
}

trait TopicUsageLogsTrait {

  abstract protected static function GetTopicType():string;

  /**
   * Get Topic Usage Logs Statastices
   * @return array
   */
  public static function getStatistics($topic_id)
  {
    global $_COMPANY;
    $topic_data_type = self::GetTopicType();

    $stats_array = array(
      "added_on" => 'N/A',
      "added_by" => 'N/A',
      "total_updates" => 0,
      "last_updated_on" => 'N/A',
      "last_updated_by" => 'N/A',
      "total_downloads" => 0,
      "unique_downloads" => 0
    );

    // Topic creation logs
    $rows = self::DBGet (
          "SELECT `tul`.*, `u`.`firstname`, `u`.`lastname` 
            FROM `topic_usage_logs` `tul` 
            LEFT JOIN `users` `u` USING(userid)
            WHERE `tul`.`topic_id` = {$topic_id}
              AND `tul`.`companyid` = {$_COMPANY->id()}
              AND `tul`.`topic_type` = '{$topic_data_type}' 
              AND `tul`.`action` = 'created'
        ");

    if (!empty($rows)) {
      $stats_array["added_on"] = $rows[0]['createdon'];
      $stats_array["added_by"] = $rows[0]['firstname'] . ' ' . $rows[0]['lastname'];
    }

    // Topic update logs
    $rows = self::DBGet (
      "SELECT `tul`.*, `u`.`firstname`, `u`.`lastname` 
            FROM `topic_usage_logs` `tul` 
            LEFT JOIN `users` `u` USING(userid)
            WHERE `tul`.`topic_id` = {$topic_id}
              AND `tul`.`companyid` = {$_COMPANY->id()}
              AND `tul`.`topic_type` = '{$topic_data_type}' 
              AND `tul`.`action` = 'updated'
        ");

    foreach ($rows as $row) {
      $stats_array["total_updates"]++;
      $stats_array["last_updated_on"] = $row['createdon'];
      $stats_array["last_updated_by"] = $row['firstname'] . ' ' . $row['lastname'];
    }

    // Total Downloads Logs
    $rows = self::DBGet (
      "SELECT COUNT(1) AS `total_downloads` FROM `topic_usage_logs` 
                    WHERE `topic_id` = {$topic_id}
                      AND `companyid` = {$_COMPANY->id()}
                      AND `topic_type` = '{$topic_data_type}' 
                      AND `action` = 'read'
        ");
    if (!empty($rows)) {
      $stats_array["total_downloads"] = $rows[0]['total_downloads'];
    }
    // Unique Opens/Downloads Logs
    $rows = self::DBGet (
      "SELECT COUNT(DISTINCT `userid`) AS `unique_downloads` FROM `topic_usage_logs` 
                    WHERE `topic_id` = {$topic_id}
                      AND `companyid` = {$_COMPANY->id()}
                      AND `topic_type` = '{$topic_data_type}' 
                      AND `action` = 'read'
        ");
    if (!empty($rows)) {
      $stats_array["unique_downloads"] = $rows[0]['unique_downloads'];
    }

    return $stats_array;
  }

  /**
   * Delete Topic Usage Logs
   * @param int $topic_id
   * @return int
   */
  public static function DeleteTopicUsageLogs($topic_id)
  {
    global $_COMPANY;
    $topic_data_type = self::GetTopicType();

    return (int)self::DBUpdate (
      "DELETE FROM `topic_usage_logs` WHERE `topic_id` = {$topic_id} AND `companyid` = {$_COMPANY->id()} AND `topic_type` = '{$topic_data_type}'"
    );
  }


  /**
   * Update Topic Usage Logs
   * @param int $topic_id
   * @param string $action  type enum:  'created','updated','read'
   * @return int
   */
  public static function UpdateTopicUsageLogs(int $topic_id, TopicUsageLogsActionType $action_type)
  {
    global $_COMPANY, $_USER;
    $topic_data_type = self::GetTopicType();

    return (int)self::DBInsert (
      "INSERT INTO `topic_usage_logs`(`topic_id`, `topic_type`, `companyid`, `userid`, `action`) VALUES ({$topic_id}, '{$topic_data_type}', {$_COMPANY->id()}, {$_USER->id}, '{$action_type->value}')"
    );
  }

}
