<?php

class TopicApprovalTask extends Teleskope
{
    use TopicAttachmentTrait;

    const TOPIC_APPROVAL_STATUS = array(
        'not_started' => 'Not Started',
        'processing' => 'Processing',
        'approved' => 'Completed',
        'skipped' => 'Not Needed',
        'denied' => 'Denied',
    );

    public static function GetTopicType(): string
    {
        return self::TOPIC_TYPES['APPROVAL_TASK'];
    }

    public static function GetAllTasksByConfig(int $approval_config_id)
    {
        global $_COMPANY;
        return self::DBGet("SELECT * FROM `topic_approvals__configuration_tasks` WHERE companyid={$_COMPANY->id()} AND approval_config_id={$approval_config_id} ORDER BY sorting_order ASC");
    }

    public static function AddOrUpdateApprovalTask(int $approval_config_id, string $task_name, string $task_details, bool $is_task_required, bool $is_proof_required, int $approval_config_taskid=0)
    {
        global $_COMPANY, $_USER;
         
        if($approval_config_taskid){
            $retval = self::DBUpdatePS(
                "UPDATE `topic_approvals__configuration_tasks` SET approval_task_name=?, approval_task_details=?, isrequired=?, proof_isrequired=?, modifiedby=?, modifiedon=now() WHERE companyid=? AND approval_config_taskid=? AND approval_config_id=?",
                "ssiiiiii",
                $task_name, $task_details, $is_task_required, $is_proof_required, $_USER->id(), $_COMPANY->id(), $approval_config_taskid,$approval_config_id
            );
        }else{
            $retval = self::DBInsertPS(
                "INSERT INTO `topic_approvals__configuration_tasks` SET approval_config_id=?, companyid=?, approval_task_name=?, approval_task_details=?, isrequired=?, proof_isrequired=?, createdby=?, createdon=NOW(), modifiedby=?, isactive=?",
                "iisxiiiii",
                $approval_config_id, $_COMPANY->id(), $task_name, $task_details, $is_task_required, $is_proof_required, $_USER->id(), $_USER->id(), 2
            );
        }
        return $retval;
    }

    public static function UpdateTaskStatus(int $approval_config_task_id, int $status)
    {
        global $_COMPANY, $_USER;
        $retVal = self::DBUpdatePS(
            "UPDATE `topic_approvals__configuration_tasks` SET isactive=?, modifiedby=?, modifiedon=now() WHERE companyid=? AND approval_config_taskid=?",
            "iiii",
            $status, $_USER->id(), $_COMPANY->id(), $approval_config_task_id
        );
        return $retVal;
    }

    public static function GetTasksWithStatusForApproval(int $approval_id, int $approval_config_id)
    {
        global $_COMPANY;
        return self::DBGet("
            SELECT tact.*, tat.approval_status, tat.assigned_to, tat.approval_taskid
            FROM `topic_approvals__configuration_tasks` AS tact
                LEFT JOIN `topic_approvals__tasks` tat
                ON tact.approval_config_taskid = tat.approval_config_taskid AND tat.approvalid={$approval_id}
            WHERE tact.companyid={$_COMPANY->id()}
              AND tact.approval_config_id={$approval_config_id} 
              AND tact.isactive = 1 
            ORDER BY tact.sorting_order ASC
            ");

    }

    public static function ChangeApprovalTasksStatus(int $approvalConfigTaskId, string $status, int $approvalId)
    {
        global $_COMPANY, $_USER;
        // Check if a record already exists
        $existingTask = self::GetTaskRecord($approvalConfigTaskId,$approvalId);

        if($existingTask){
            $retVal = self::DBUpdatePS(
                "UPDATE `topic_approvals__tasks` SET approval_status=?, modifiedby=?, modifiedon=now() WHERE companyid=? AND approval_config_taskid=? AND approvalid=?",
                "siiii",
                $status, $_USER->id(), $_COMPANY->id(), $approvalConfigTaskId, $approvalId);
        }else{
            $retVal = self::DBInsertPS(
                "INSERT INTO `topic_approvals__tasks` SET approvalid=?, approval_config_taskid=?, companyid=?, approval_status=?, assigned_to=?, createdby=?, modifiedby=?, modifiedon=now(), createdon=NOW()",
                "iiisiii",
                $approvalId, $approvalConfigTaskId, $_COMPANY->id(), $status, $_USER->id(), $_USER->id(), $_USER->id()
            );

            $approval_task = TopicApprovalTask::GetTopicApprovalTask($retVal);
            $approval_task->moveAttachmentsFromEphemeralTopic();
        }
        return $retVal ? 1 : 0 ;
    }

    public static function GetTaskRecord(int $taskId, int $approvalId)
    {
        global $_COMPANY;
        return self::DBGet("SELECT * FROM `topic_approvals__tasks` WHERE companyid={$_COMPANY->id()} AND approval_config_taskid={$taskId} AND approvalid={$approvalId} ORDER BY createdon DESC");
    }

    public static function ChangeAssignee(int $taskId, int $userid, int $approvalId)
    {
        global $_COMPANY, $_USER;
         // Check if a record already exists
        $existingTask = self::GetTaskRecord($taskId,$approvalId);
        if($existingTask){
            $retVal = self::DBUpdatePS(
                "UPDATE `topic_approvals__tasks` SET assigned_to=?, modifiedby=?, modifiedon=now() WHERE companyid=? AND approval_config_taskid=? AND approvalid=?",
                "iiiii",
                $userid, $_USER->id(), $_COMPANY->id(), $taskId, $approvalId
            );
        }else{
            $retVal = self::DBInsertPS(
                "INSERT INTO `topic_approvals__tasks` SET approvalid=?, approval_config_taskid=?, companyid=?, approval_status=?, assigned_to=?, createdby=?, createdon=NOW()",
                "iiisii",
                $approvalId, $taskId, $_COMPANY->id(), 'not_started', $userid, $_USER->id()
            );

            $approval_task = TopicApprovalTask::GetTopicApprovalTask($retVal);
            $approval_task->moveAttachmentsFromEphemeralTopic();
        }

        return $retVal ? 1 : 0 ;
    }  

    // Check if all required tasks are approved. Works as a safety check and doesn't rely solely on approve button disabled/enabled
    public static function AreAllRequiredTasksApprovedForConfiguration(int $approvalId, int $approval_config_id) : bool
    {
        global $_COMPANY;

        $approvalConfig = TopicApprovalConfiguration::GetApprovalConfiguration($approval_config_id);
        if (!$approvalConfig || !($topicType = $approvalConfig->val('topic_type'))) {
            return false;
        }

        $config_name = Teleskope::TOPIC_TYPE_CONFIGURATION_NAME_MAP[$topicType] ?? 'unknown';

        if (!$_COMPANY->getAppCustomization()[$config_name]['approvals']['tasks']) {
            return true;
        }

        $allTasks = self::GetTasksWithStatusForApproval($approvalId, $approval_config_id);

        // reduce the tasks to only required tasks
        $allRequiredTasks = array_filter($allTasks, function($task){return $task['isrequired'] != 0;});

        // Count all $allRequiredTasks rows to see how many have approval_status === 'approved' or 'skipped'
        // and compared with the count of configured required tasks
        return
            count(array_filter(
                $allRequiredTasks,
                function($requiredTask){
                    return $requiredTask['approval_status'] === 'approved' || $requiredTask['approval_status'] === 'skipped';
                }
            )) === count($allRequiredTasks);
    }

    // check if using
    public static function GetTaskConfigurationDetails(int $approval_config_task_id)
    {
        global $_COMPANY;
        return self::DBGet("SELECT * FROM `topic_approvals__configuration_tasks` WHERE companyid={$_COMPANY->id()} AND approval_config_taskid={$approval_config_task_id}");
    }

    // For Attachements
    public static function GetTopicApprovalTask(int $id): ?TopicApprovalTask
    {
        global $_COMPANY;

        $results = self::DBGet("
            SELECT * FROM `topic_approvals__tasks`
            WHERE `approval_taskid` = {$id}
            AND `companyid` = {$_COMPANY->id()}
        ");

        return new TopicApprovalTask($id, $_COMPANY->id(), $results[0]);
    }

    public static function IsProofRequired(int $approval_config_task_id)
    {
        $taskDetails = self::GetTaskConfigurationDetails($approval_config_task_id);
        return $taskDetails[0]['proof_isrequired'];

    }

    public static function UpdateTaskSortOrder(array $newOrder){

        global $_COMPANY, $_ZONE;
        $cases = [];
        $ids = [];
        $retVal = 0;
        foreach ($newOrder as $order => $id) {
            $id = (int)$id;
            $order = (int)$order + 1; 
            $cases[] = " WHEN `approval_config_taskid` = $id THEN $order";
            $ids[] = $id; 
        }

        if(!empty($cases)){
            $idsString = implode(',', array_map('intval', $ids));
            $caseStatement = implode(' ', $cases);
            $retVal = self::DBUpdatePS("UPDATE `topic_approvals__configuration_tasks` SET `sorting_order`= CASE $caseStatement END WHERE `companyid`=? AND `approval_config_taskid` IN ($idsString)",'i', $_COMPANY->id());

            // $_COMPANY->expireRedisCache("GRP_CAT:{$_ZONE->id()}");
        }
        return $retVal;

        // $retVal = self::DBUpdatePS(
        //     "UPDATE `topic_approvals__configuration_tasks` SET sorting_order=? WHERE companyid=? AND approval_config_taskid=?",
        //     "iii",
        //     $sorting_order, $_COMPANY->id(), $approval_config_taskid
        // );
        // return $retVal ? 1 : 0 ;
    }
}
