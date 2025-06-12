<?php
// Do no use require_once as this class is included in Company.php.

class GroupUserLogs extends Teleskope {

    const GROUP_USER_LOGS_ROLES = array(
        'GROUP_MEMBER' => 'group_member',
        'GROUP_LEAD' => 'group_lead',
        'CHAPTER_LEAD' => 'chapter_lead',
        'CHANNEL_LEAD' => 'channel_lead',
        'TEAM_MEMBER' => 'team_member'
    );

    const GROUP_USER_LOGS_ACTION = array(
        'ADD' => 'add',
        'REMOVE' => 'remove',
        'UPDATE' => 'update'
    );

    const GROUP_USER_LOGS_ACTION_REASON =array(
        'HRIS_SYNC' => 'hris_sync',
        'USER_MAINTENANCE' => 'user_maintenance',
        'GROUP_MAINTENANCE' => 'group_maintenance',
        'USER_INITATED' => 'user_initiated',
        'LEAD_INITIATED' => 'lead_initated',
        'GROUP_RESTRICTIONS_UPDATE' => 'group_restrictions_update',
    );

    const GROUP_USER_LOGS_SUB_SCOPE = array(
        "GROUP" => '',
        'CHAPTER' => 'chapter',
        'CHANNEL' => 'channel',
        'TEAM' => 'team'
    );

	protected function __construct(int $id,int $cid,array $fields) {
		parent::__construct($id,$cid,$fields);

	}

    /**
     * @param int $groupid
     * @param int $userid
     * @param string $action valid values are in GroupUserLogs::GROUP_USER_LOGS_ACTION
     * @param string $role valid values are in GroupUserLogs::GROUP_USER_LOGS_ROLES
     * @param int $roleid
     * @param string $sub_scope valid values are in GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE
     * @param int $sub_scopeid
     * @param int $action_by
     * @param string $action_reason valid values are in GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON. If the action
     * reason is set to self::GROUP_USER_LOGS_ACTION_REASON['USER_INITATED'] and the action is performed by some other
     * users then the action_reason will be updated to self::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED'] or
     * self::GROUP_USER_LOGS_ACTION_REASON['HRIS_SYNC']
     * @return int
     */
    public static function CreateGroupUserLog(int $groupid, int $userid, string $action, string $role, int $roleid, string $sub_scope, int $sub_scopeid, string $action_reason) : int
    {
        global $_COMPANY, $_USER;

        $action_by = $_USER ? $_USER->id : 0;
        if ($action_reason == self::GROUP_USER_LOGS_ACTION_REASON['USER_INITATED'] && $userid != $action_by) {
            if ($action_by) {
                $action_reason  = self::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED'];
            } else {
                $action_reason  = self::GROUP_USER_LOGS_ACTION_REASON['HRIS_SYNC'];
            }
        }

        return self::DBInsertPS("INSERT INTO `group_user_logs` (`companyid`,`userid`,`groupid`,`action`,`role`,`roleid`,`sub_scope`,`sub_scopeid`,`action_by`,`action_reason`,`createdon`) VALUES (?,?,?,?,?,?,?,?,?,?, NOW())",'iiissisiis', $_COMPANY->id(), $userid, $groupid, $action, $role, $roleid, $sub_scope, $sub_scopeid, $action_by, $action_reason);
    }

    public static function GetAuditLogs(string $startDate, string $endDate, int $groupId, int $chapterId, int $channelId) {
        global $_COMPANY, $_ZONE;
        $result = [];
        $groupCondition = '';
        if($groupId){
            $groupCondition = " AND group_user_logs.groupid = {$groupId}";
        }
        $chapterCondition ='';        
        if ($chapterId) {
            $chapterCondition = " AND group_user_logs.chapterid = {$chapterId}";
        }
        $channelCondition ='';
        if ($channelId) {
            $channelCondition = " AND group_user_logs.channelid = {$channelId}";   
        }
        

        $result = self::DBGet("SELECT group_user_logs.*, users.zoneids,users.firstname,users.lastname,users.email,users.externalid, users.isactive, `groups`.groupname, 'Member' as 'rolename'
                FROM group_user_logs 
                JOIN users ON  group_user_logs.userid=users.userid 
                JOIN `groups` ON group_user_logs.groupid = groups.groupid 
                WHERE `group_user_logs`.companyid = {$_COMPANY->id()}
                AND`groups`.companyid = {$_COMPANY->id()}
                AND `groups`.zoneid = {$_ZONE->id()}
                {$groupCondition}
                {$chapterCondition}
                {$channelCondition}
                AND group_user_logs.createdon BETWEEN '{$startDate}' AND '{$endDate}' 
                ORDER BY group_user_logs.createdon DESC");
        return $result;
         
    }

}
