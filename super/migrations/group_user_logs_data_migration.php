<?php
require_once __DIR__.'/../head.php';
ini_set('max_execution_time', 3000);

$db	= new Hems();

echo "<p>Starting Migration ... processing groups</p>";

$check1 = $_SUPER_ADMIN->super_get("SELECT COUNT(1) AS C FROM group_user_logs")[0]['C'];
if (!empty($check1)) {
    echo "<p>group_user_logs table is not empty ... migration might have run in the past</p>";
    echo "<p>Exiting</p>";
    exit();
}

$check2 = date('Ymd');
if ($check2 > '20230228') {
    echo "<p>Migration expired</p>";
    echo "<p>Exiting</p>";
    exit();
}


$companies = $_SUPER_ADMIN->super_get("SELECT companyid,subdomain FROM `companies`");
foreach ($companies as $company) {
    echo "<p>Migrating {$company['subdomain']} ({$company['companyid']})</p>";
    $companyid = $company['companyid'];
//    $groups = $_SUPER_ADMIN->get("SELECT groupid,groupname FROM `groups` WHERE companyid={$companyid}");
//    echo "<ul>";
//    foreach ($groups as $group) {
        $groupMembersMigrated =  json_encode(migrateGroupMemberData($companyid));
        $groupLeadsMigrated = json_encode(migrateGroupLeadsData($companyid));
        $chapterLeadsMigrated = json_encode(migrateChapterLeadsData($companyid));
        $channelLeadsMigrated =  json_encode(migrateChannelLeadsData($companyid));
        echo //"<li>{$group['groupname']} ({$group['groupid']}): " .
                "<ul>" . 
                    "<li>$groupMembersMigrated</li>" .
                    "<li>$groupLeadsMigrated</li>" .
                    "<li>$chapterLeadsMigrated</li>" .
                    "<li>$channelLeadsMigrated</li>" .
                "</ul>" ;
            //"</li>";
            
    //}
//    echo "</ul>";
}
exit();

//
//
//
//

function migrateGroupMemberData(int $companyid){
   
    global $db;
    $response = ['Total Rows Processed'=>0,'Total Log Rows Created'=>0,'Total Failed'=>0,'Title'=>""];
    $totalLogsRowsCreated = 0;
    $allmembers = $_SUPER_ADMIN->get("SELECT groupmembers.* FROM `groupmembers` JOIN `groups` USING(`groupid`) WHERE `groups`.companyid='{$companyid}'");
    foreach( $allmembers as $member){
        $chapterids = explode(',',$member['chapterid']);
        $channelids = explode(',',$member['channelids']);
        // Create Group user log
        createGroupUserLog($member['groupid'], $member['userid'], GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, '', 0, $companyid,$member['groupjoindate']);
        $totalLogsRowsCreated++;

        foreach ($chapterids as $chap) {
            if ($chap) { // We do not want to execute for chapterid 0
                // Create Group user log
                createGroupUserLog($member['groupid'], $member['userid'], GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chap, $companyid,$member['groupjoindate']);
                $totalLogsRowsCreated++;
            }
        }
    
        foreach ($channelids as $chan) {
            if ($chan) { // We do not want to execute for channelid 0
                // Create Group user log
                createGroupUserLog($member['groupid'], $member['userid'], GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $chan, $companyid,$member['groupjoindate']);
                $totalLogsRowsCreated++;
            }
        }


    }
    $response = ['Title'=>"Total Group members audit logs migrated:",'Total Rows Processed'=>count($allmembers),'Total Log Rows Created'=>$totalLogsRowsCreated,'Total Failed'=>0];

    return $response;
}

function migrateGroupLeadsData(int $companyid){
    global $db;
    $response = ['Total Rows Processed'=>0,'Total Log Rows Created'=>0,'Total Failed'=>0,'Title'=>""];
    $totalLogsRowsCreated = 0;
    $allGroupLeads = $_SUPER_ADMIN->get("SELECT groupleads.* FROM `groupleads` JOIN `groups` USING(`groupid`) WHERE `groups`.companyid='{$companyid}'");
    foreach( $allGroupLeads as $lead){
        // Create Group user log

        if ($lead['isactive'] == 100){
            $action =  GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'];
        } else {
            $action = GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'];
        }
        createGroupUserLog($lead['groupid'], $lead['userid'],$action , GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_LEAD'], $lead['grouplead_typeid'], '', 0, $companyid,$lead['assigneddate']);
        $totalLogsRowsCreated++;            
    }
    $response = ['Title'=>"Total Group Leaders audit logs migrated:",'Total Rows Processed'=>count($allGroupLeads),'Total Log Rows Created'=>$totalLogsRowsCreated,'Total Failed'=>0];

    return $response;
}

function migrateChapterLeadsData(int $companyid){
    global $db;
    $response = ['Total Rows Processed'=>0,'Total Log Rows Created'=>0,'Total Failed'=>0,'Title'=>""];
    $totalLogsRowsCreated = 0;
    $allChapterLeads = $_SUPER_ADMIN->get("SELECT chapterleads.* FROM `chapterleads` JOIN `groups` USING(`groupid`) WHERE `groups`.companyid='{$companyid}'");
    foreach( $allChapterLeads as $lead){
        // Group User Log
        createGroupUserLog($lead['groupid'], $lead['userid'], GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHAPTER_LEAD'], $lead['grouplead_typeid'], GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $lead['chapterid'], $companyid,$lead['assigneddate']);
        $totalLogsRowsCreated++;            
    }
    $response = ['Title'=>"Total Chapter Leaders audit logs migrated:",'Total Rows Processed'=>count($allChapterLeads),'Total Log Rows Created'=>$totalLogsRowsCreated,'Total Failed'=>0];

    return $response;
}

function migrateChannelLeadsData(int $companyid){
    global $db;
    $response = ['Total Rows Processed'=>0,'Total Log Rows Created'=>0,'Total Failed'=>0,'Title'=>""];
    $totalLogsRowsCreated = 0;
    $allChannelLeads = $_SUPER_ADMIN->get("SELECT group_channel_leads.* FROM `group_channel_leads` JOIN `groups` USING(`groupid`) WHERE `groups`.companyid='{$companyid}'");
    foreach( $allChannelLeads as $lead){
        // Group User Log
        createGroupUserLog($lead['groupid'], $lead['userid'], GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHANNEL_LEAD'], $lead['grouplead_typeid'], GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $lead['channelid'], $companyid,$lead['assigneddate']);
        $totalLogsRowsCreated++;            
    }
    $response = ['Title'=>"Total Channel Leaders audit logs migrated:",'Total Rows Processed'=>count($allChannelLeads),'Total Log Rows Created'=>$totalLogsRowsCreated,'Total Failed'=>0];

    return $response;
}


function createGroupUserLog(int $groupid, int $userid, string $action, string $role, int $roleid, string $sub_scope, int $sub_scopeid, string $companyid, string $createdOn = "") : int
{
    global $db;
    $action_by = 0;

    if($createdOn == ''){
        $createdOn = "NOW()";
    } else {
        $createdOn = "'$createdOn'";
    }

    return $_SUPER_ADMIN->update("INSERT INTO `group_user_logs` (`companyid`,`userid`,`groupid`,`action`,`role`,`roleid`,`sub_scope`,`sub_scopeid`,`action_by`,`action_reason`,`createdon`) VALUES ('{$companyid}','{$userid}','{$groupid}','{$action}','{$role}','{$roleid}','{$sub_scope}','{$sub_scopeid}','{$action_by}','group_maintenance',{$createdOn})");
}