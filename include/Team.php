<?php

// Do no use require_once as this class is included in Company.php.

class Team extends Teleskope {

	protected function __construct($id,$cid,$fields) {
		parent::__construct($id,$cid,$fields);
	}

	// Add traits right after the constructor
    public static function GetTopicType():string {return self::TOPIC_TYPES['TEAMS'];}
    use TopicLikeTrait;
    use TopicCommentTrait;

    const SYS_TEAMROLE_TYPES = array (
        0 => "Other",
        1 => "Admin",  // E.g. HR Admin, Supervisor
        2 => "Mentor", // E.g. Mentor, 
        3 => "Mentee", // E.g. Mentee,
		4 => "Individual", // Individual Development, 
    );

	const GET_TALENTPEAK_TODO_STATUS = array (
		'1' => 'Not Started',
		'51' => 'In Progress',
		'52' => 'Done'
	);

	const PROGRAM_TEAM_TAB = array (
		'TEAM_ROLE' => '1',
		'ACTION_ITEM' => '2',
		'TOUCH_POINT' => '3',
		'FEEDBACK' => '4',
		'MATCHING_ALGORITHM' => '5',
		'TEAM_MESSAGE' => '6',
	);

	const CUSTOM_ATTRIBUTES_MATCHING_OPTIONS = array (
		'MATCH' => '1', // For Radio/ Select
		'DONOT_MATCH' => '2', // For Radio/Select
		'MATCH_N_NUMBERS' => '3', // For Checkbox
		'GREATER_THAN' => '4', // For Rating
		'EQUAL_TO' => '5', // For Rating
		'LESS_THAN' => '6', // For Rating
		'NOT_EQUAL_TO' => '7', // For Rating
		'GREATER_THAN_OR_EQUAL_TO' => '8', // For Rating
		'LESS_THAN_OR_EQUAL_TO' => '9', // For Rating
        'WORD_MATCH' => '10', // poor mans text matching
		'IGNORE' => '0', // Common
		'RANGE_MATCH'=>'11'
	);

    const TEAM_PROGRAM_TYPE = array (
		'DEFAULT'=> 0,
        'ADMIN_LED' => 1,
        'PEER_2_PEER' => 2,
        'INDIVIDUAL_DEV' => 3,
		'NETWORKING' => 4,
		'CIRCLES' => 5
    );

    const TEAM_TASK_VISIBILITY = array (
        'ASSIGNED_PERSON' => 1,
        'ALL_MEMBERS' => 2,
    );

    const TEAM_FEEDBACK_FOR_PROGRAM_LEADS = 0;

	const TEAM_ATTRIBUTES_KEYS = array(
		'roles_capacity' => 'roles_capacity'
	);

    const TEAM_REQUEST_STATUS = array(
        'PENDING' => 1,
        'ACCEPTED' => 2,
        'REJECTED' => 0,
        'CANCELED' => 3,
        'WITHDRAWN' => 4,
    );

	const FIELD_KEY_MAP = array(
		'total_action_items' => 'ACTION_ITEM',
		'count_actionitem_pending' => 'ACTION_ITEM',
		'count_actionitem_inprogress' => 'ACTION_ITEM',
		'count_actionitem_complete' => 'ACTION_ITEM',
		'total_touchpoints' => 'TOUCH_POINT',
		'count_touchpoints_pending' => 'TOUCH_POINT',
		'count_touchpoints_inprogress' => 'TOUCH_POINT',
		'count_touchpoints_complete' => 'TOUCH_POINT',
		'count_feedback' => 'FEEDBACK',
		'count_messages' => 'TEAM_MESSAGE',		
	);
    public static function GetTeamRequestStatusLabel (int $status)
    {
        switch ($status) {
            case 1:
                return gettext('Pending');
            case 2:
                return gettext('Accepted');
            case 0:
                return gettext('Rejected');
            case 3:
                return gettext('Canceled by system');
            case 4:
                    return gettext('Canceled by sender');
            default:
                return gettext('Invalid');
        }
    }
    /**
     * Get Team Detail
     */
    
    public static function GetTeam(int $id, bool $allow_cross_zone_fetch = false) {
		$obj = null;
        global $_COMPANY,$_ZONE; /* @var Company $_COMPANY */

        $r = self::DBGet("SELECT * FROM `teams` WHERE `companyid`='{$_COMPANY->id()}' AND `teamid`='{$id}'");
		if (!empty($r)){
			if (!$allow_cross_zone_fetch && ((int) $r[0]['zoneid'] !== $_ZONE->id())) {
				return null;
			}

			$obj =  new Team($id,$_COMPANY->id(),$r[0]);
		}
		return $obj;
	}

    /**
     * Returns team by a given teamName. Seach is scoped in provided groupid. Team object is returned only if one team
     * with a given name is found. If more than one teams are found then it is considered a mismatch and null is returned.
     * @param int $groupid
     * @param string $teamname
     * @return Team|null
     */
	public static function GetTeamByTeamName(int $groupid,string $teamname) {
		$obj = null;
        global $_COMPANY,$_ZONE; /* @var Company $_COMPANY */

        $r = self::DBGetPS("SELECT * FROM `teams` WHERE `companyid`=? AND `zoneid`=? AND `groupid`=? AND team_name=?",'iiix',$_COMPANY->id(),$_ZONE->id(),$groupid,$teamname);
		if (!empty($r) && count($r) == 1){
			$obj =  new Team($r[0]['teamid'],$_COMPANY->id(),$r[0]);
		}
		return $obj;
	}

    public static function GetProgramTeamRoles(int $groupid, int $activeOnly = 0,int $sys_role_type = 0){
        global $_COMPANY,$_ZONE; /* @var Company $_COMPANY */
        $condition = "";
        if ($activeOnly){
            $condition = "  AND `isactive`=1";
        }
		$sys_role_conditon = "";
		if ($sys_role_type){
			$sys_role_conditon = " AND sys_team_role_type = '{$sys_role_type}'";
		}
		$rows =  self::DBGet("SELECT * FROM `team_role_type` WHERE `team_role_type`.`companyid`='{$_COMPANY->id()}' AND `team_role_type`.`zoneid`='{$_ZONE->id()}' AND `groupid`='{$groupid}' $sys_role_conditon $condition ");
		usort($rows, function($a, $b) {
			return $a['sys_team_role_type'] <=> $b['sys_team_role_type'];
		});
		return $rows;
    }
    public static function GetTeamRoleType(int $id){
        $data = null;
        global $_COMPANY,$_ZONE; /* @var Company $_COMPANY */
        $key ="GRP_TEAM_ROLE_TYPE:{$id}";
        if (($data = $_COMPANY->getFromRedisCache($key)) === false) {
            // Removed zone check (for 3689) to allow the method to work if $_ZONE is not set.
            $t = self::DBGet("SELECT * FROM `team_role_type` WHERE  `team_role_type`.`companyid`='{$_COMPANY->id()}' AND `roleid`='{$id}'");
            if (count($t)) {
                $data = $t[0];
            }
            $_COMPANY->putInRedisCache($key, $data, 86400);
        }
		return $data;
    }

    public static function AddOrUpdateTeamRole(int $roleid, int $groupid, string $type, int $sys_team_role_type, int $min_required, int $max_allowed, int $discover_tab_show, string $discover_tab_html, string $welcome_message, string $restrictions= '{}', string $welcome_email_subject ='',string $registration_start_date='' , string $registration_end_date='', int $role_capacity = 1,int $role_request_buffer = 0, int $hide_on_request_to_join = 0,string $joinrequest_email_subject='',string $joinrequest_message = '',string $completion_message='',string $completion_email_subject='', string $action_on_member_termination = '', string $member_termination_email_subject ='', string $member_termination_message= '', int $email_on_member_termination=1, int $auto_match_with_mentor=0, int $maximum_registrations=0){
        global $_COMPANY,$_ZONE;

        $type = trim($type);

        if ($roleid){
            self::DBUpdatePS("UPDATE `team_role_type` SET `type`=?,`min_required`=?,`max_allowed`=?,`discover_tab_show`=?,`discover_tab_html`=?,`welcome_message`=?,`restrictions`=?,`modifiedon`=NOW(),`welcome_email_subject`=?,registration_start_date=?,registration_end_date=?,role_capacity=?,`hide_on_request_to_join`=?,`joinrequest_email_subject`=?,`joinrequest_message`=?,`completion_message`=?,`completion_email_subject`=?, `role_request_buffer`=?, `action_on_member_termination`=?, `member_termination_email_subject` =?, `member_termination_message`=?,email_on_member_termination=?,auto_match_with_mentor=?, maximum_registrations=? WHERE `companyid`=? AND `zoneid`=? AND `roleid`=? AND `groupid`=?",'xiiixxxxxxiixxxxixxxiiiiiii',$type,$min_required,$max_allowed,$discover_tab_show,$discover_tab_html,$welcome_message,$restrictions,$welcome_email_subject,$registration_start_date,$registration_end_date,$role_capacity,$hide_on_request_to_join,$joinrequest_email_subject,$joinrequest_message,$completion_message,$completion_email_subject,$role_request_buffer,$action_on_member_termination, $member_termination_email_subject, $member_termination_message, $email_on_member_termination, $auto_match_with_mentor,$maximum_registrations, $_COMPANY->id(),$_ZONE->id(),$roleid,$groupid);
            $_COMPANY->expireRedisCache("GRP_TEAM_ROLE_TYPE:{$roleid}");
        } else {
            $roleid = self::DBInsertPS("INSERT INTO `team_role_type`(`companyid`, `zoneid`, `sys_team_role_type`, `groupid`, `type`, `min_required`, `max_allowed`,`discover_tab_show`, `discover_tab_html`, `welcome_message`,`restrictions`, `modifiedon`, `isactive`,`welcome_email_subject`,`registration_start_date`,`registration_end_date`,`role_capacity`,hide_on_request_to_join,`joinrequest_email_subject`,`joinrequest_message`,`completion_message`,`completion_email_subject`,`role_request_buffer`,`action_on_member_termination`, `member_termination_email_subject`, `member_termination_message`, `email_on_member_termination`,`auto_match_with_mentor`,`maximum_registrations`) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),1,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",'iiiixiiixxxxxxiixxxxixxxiii',$_COMPANY->id(),$_ZONE->id(),$sys_team_role_type, $groupid, $type, $min_required, $max_allowed,$discover_tab_show,$discover_tab_html,$welcome_message,$restrictions,$welcome_email_subject,$registration_start_date,$registration_end_date,$role_capacity,$hide_on_request_to_join,$joinrequest_email_subject,$joinrequest_message,$completion_message,$completion_email_subject,$role_request_buffer,$action_on_member_termination, $member_termination_email_subject, $member_termination_message,$email_on_member_termination,$auto_match_with_mentor,$maximum_registrations);
        }
        return $roleid;
    }

    public static function UpdateTeamRoleStatus(int $groupid,int $roleid, int $status)
    {
        global $_COMPANY,$_ZONE;

        $retVal = self::DBUpdatePS("UPDATE `team_role_type` SET `modifiedon`=NOW(),`isactive`=? WHERE `companyid`=? AND `zoneid`=? AND `roleid`=? AND `groupid`=?",'iiiii',$status, $_COMPANY->id(), $_ZONE->id(), $roleid, $groupid);
        $_COMPANY->expireRedisCache("GRP_TEAM_ROLE_TYPE:{$roleid}");

        return $retVal;
    }

    /**
     * Get My created and joined teams
     */
    public static function GetMyTeams(int $groupid, int $chapterid=0, int $showGlobalChapterOnly=0){
        global $_COMPANY,$_ZONE,$_USER;
		
		$chapterFilter="";

        if ($showGlobalChapterOnly){
            $chapterFilter = " AND teams.`chapterid`=0";
        } else {
            if ($chapterid > 0){
                $chapterFilter = " AND FIND_IN_SET(" . $chapterid . ", teams.chapterid) ";
            }
        }

		return self::DBGet("SELECT teams.*,team_members.teamid,team_members.userid,team_members.roleid,team_members.last_active_date FROM teams JOIN team_members USING (teamid) WHERE teams.companyid='{$_COMPANY->id()}' AND teams.zoneid='{$_ZONE->id()}' AND teams.groupid='{$groupid}' AND team_members.userid='{$_USER->id()}' {$chapterFilter} ");
    }

    /**
     * Returns all common teams between two users in a group
     * @param int $groupid
     * @param int $userid1
     * @param int $userid2
     * @param int|null $userid1_roleid
     * @param int|null $userid2_roleid
     * @param bool $activeOnly
     * @return array
     */
    public static function GetCommonTeamsBetweenUsers (int $groupid, int $userid1, int $userid2, ?int $userid1_roleid=null, ?int $userid2_roleid=null, bool $activeOnly=true): array
    {
        global $_COMPANY;
        $active_filter = $activeOnly ? ' AND teams.isactive=1' : '';
        $userid1_filter = $userid1_roleid ? " AND team_members.roleid={$userid1_roleid}" : '';
        $userid2_filter = $userid2_roleid ? " AND team_members.roleid={$userid2_roleid}" : '';
        $teams1 = self::DBROGet("SELECT teamid FROM teams JOIN team_members USING (teamid) WHERE companyid={$_COMPANY->id()} AND groupid={$groupid} AND team_members.userid={$userid1} {$active_filter} {$userid1_filter}");
        $teams2 = self::DBROGet("SELECT teamid FROM teams JOIN team_members USING (teamid) WHERE companyid={$_COMPANY->id()} AND groupid={$groupid} AND team_members.userid={$userid2} {$active_filter} {$userid2_filter}");

        return array_intersect(array_column($teams1, 'teamid'), array_column($teams2, 'teamid'));
    }

    public static function GetCircleHashTagUrl(int $hashtagid): string
    {
        global $_COMPANY;
        return '#circles/hashtags/' . $_COMPANY->encodeId($hashtagid);
    }

    public static function GetUserRegistrationRecord(int $groupid, int $userid, int $roleid): array
    {
        global $_COMPANY;
        return self::DbGet("SELECT * FROM member_join_requests WHERE `member_join_requests`.`companyid`={$_COMPANY->id()} AND `roleid`={$roleid} AND `userid`={$userid} AND `groupid`={$groupid}");
    }

    /**
     * @param int $roleid
     * @return array
     */
    public function getTeamMembers (int $roleid): array
    {
		global $_COMPANY,$_ZONE,$_USER;
		$condition  = '';
		if ($roleid){
			$condition  = " AND team_members.roleid = {$roleid}";
		}
		$rows = self::DBGet("SELECT team_members.*,IFNULL(team_role_type.`type`,'Other') as `role`, team_role_type.sys_team_role_type, IFNULL(users.firstname,'Deleted') as firstname,IFNULL(users.lastname,'User') as lastname,IFNULL(users.email,'') as email, IFNULL(users.picture,'') as picture, IFNULL(IF (users.jobtitle='','Job title unavailable',users.jobtitle),'') as jobtitle  FROM `team_members` LEFT JOIN users ON users.userid=team_members.userid LEFT JOIN team_role_type ON team_role_type.roleid= team_members.roleid WHERE team_members.`teamid`='{$this->id}' {$condition} ");
		usort($rows, function($a, $b) {
			return $a['sys_team_role_type'] <=> $b['sys_team_role_type'];
		});
		return $rows;
	}
	
	/**
	 * GetAllTeamsInGroup
	 *
	 * @param  int $groupid
	 * @param  string $chapterids
	 * @param  int $startLimit
	 * @param  int $endLimit // 0 means no limit set. Featch All Data.
	 * @param  string $searchKeyword
	 * @param  int $clearCache
	 * @param  string $orderBy
	 * @param  string $orderDirection
	 * @param  array|null $state_filter
	 * @param  int|null $year_filter
	 * @return array
	 */
	public static function GetAllTeamsInGroup(int $groupid, string $chapterids = '0', int $startLimit=0, int $endLimit=0, string $searchKeyword='',bool $clearCache = false, string $orderBy ='', string $orderDirection = '', ?array $state_filter = null, ?int $year_filter = null){
		global $_COMPANY,$_ZONE,$_USER;

		//$key = "GRP_TEAM_TBL_LST:{$groupid}";

		if ($clearCache) {
			//$_COMPANY->expireRedisCache($key);
			$_COMPANY->expireRedisCache("GRP_TEAM_DECORATED_TEAMS__MEMBERS:{$groupid}");
		}

		//if (($rows = $_COMPANY->getFromRedisCache($key)) === false) { // Not in cache
			$rows = self::DBROGet("SELECT teamid, teams.groupid, teams.chapterid, team_name, handleids, attributes, createdby, teams.createdon, team_start_date, team_complete_date, teams.modifiedon, teams.isactive,`chapters`.chaptername, `chapters`.`isactive` as chapterStatus FROM `teams` LEFT JOIN `chapters` ON `teams`.`chapterid`=`chapters`.`chapterid` WHERE `teams`.`companyid`={$_COMPANY->id()} AND `teams`.`zoneid`={$_ZONE->id()} AND `teams`.`groupid`={$groupid}");
			//$_COMPANY->putInRedisCache($key, $rows, 300);
		//}

        if (!empty($chapterids)){
            $chapterids_arr = Str::ConvertCSVToArray($chapterids);
            $rows = array_values(array_filter($rows, function($item) use ($chapterids_arr) {
                return in_array($item['chapterid'], $chapterids_arr);
            }));
        }

        if (!empty($state_filter)){
            $state_filter_arr = $state_filter;
            $rows = array_values(array_filter($rows, function($item) use ($state_filter_arr) {
                return in_array($item['isactive'], $state_filter_arr);
            }));
        }

        if (!empty($year_filter)){
            $year_filter_arr = Str::ConvertCSVToArray($year_filter);
            $rows = array_values(array_filter($rows, function($item) use ($year_filter_arr) {
                return in_array($item['createdon'], $year_filter_arr);
            }));
        }

        if (!empty($chapterids)){
            $chapterids_arr = Str::ConvertCSVToArray($chapterids);
            $rows = array_values(array_filter($rows, function($item) use ($chapterids_arr) {
                return in_array($item['chapterid'], $chapterids_arr);
            }));
        }

		$searchKeyword = trim($searchKeyword);
		if (!empty($searchKeyword)) { // Search
			$rows = array_values(array_filter($rows, function($item) use ($searchKeyword) {
				return stripos($item['team_name'], $searchKeyword) !== false || stripos($item['chaptername'], $searchKeyword) !== false;
			}));
		}

        self::DecorateTeamRowsWithComputedDetails($groupid, $rows);

        // Order Data
        usort($rows, function($a, $b) use ($orderBy, $orderDirection) {
            if ($orderBy == 'team_name') {

                if ($orderDirection == "ASC") {
                    return $a['team_name'] <=> $b['team_name'];
                } else {
                    return $b['team_name'] <=> $a['team_name'];
                }

            } elseif ($orderBy == 'chaptername') {
                if ($orderDirection == "ASC") {
                    return $a['chaptername'] <=> $b['chaptername'];
                } else {
                    return $b['chaptername'] <=> $a['chaptername'];
                }

            } elseif ($orderBy == 'last_activity') {
                if ($orderDirection == "ASC") {
                    return $a['last_activity'] <=> $b['last_activity'];
                } else {
                    return $b['last_activity'] <=> $a['last_activity'];
                }

            }	elseif ($orderBy == 'feedback_count') {
                if ($orderDirection == "ASC") {
                    return $a['feedback_count'] <=> $b['feedback_count'];
                } else {
                    return $b['feedback_count'] <=> $a['feedback_count'];
                }

            }	elseif ($orderBy == 'isactive') {
                if ($orderDirection == "ASC") {
                    return $a['isactive'] <=> $b['isactive'];
                } else {
                    return $b['isactive'] <=> $a['isactive'];
                }
            } else {
                return $b['teamid'] <=> $a['teamid'];
            }
        });

        // Paginate data
        if ($endLimit) {
            $page_number = intval($startLimit / $endLimit);
            $rowsChunk = array_chunk($rows, $endLimit)[$page_number] ?? [];
        } else {
            $rowsChunk = $rows; // All rows
        }

        return [
            count($rows),
            $rowsChunk
        ];
	}

    public static function DecorateTeamRowsWithComputedDetails (int $groupid, array &$teams)
    {
        global $_COMPANY;

        // Section - Get Team Members
        $keyMembers ="GRP_TEAM_DECORATED_TEAMS__MEMBERS:{$groupid}";
        if (($dataMembers = $_COMPANY->getFromRedisCache($keyMembers)) === false) {
            $dataMembers = array();
            foreach ($teams as $t) {
                $tid = $t['teamid'];
                $tObj = Team::Hydrate($t['teamid'], $t);
                $dataMembers[$tid]['team_members'] = $tObj->getTeamMembers(0);
            }
            $_COMPANY->putInRedisCache($keyMembers, $dataMembers, 3600);
        }

        // Section - Get Last Activity
        $keyLastActivity ="GRP_TEAM_DECORATED_TEAMS__LAST_ACTIVITY:{$groupid}";
        if (($dataLastActivity = $_COMPANY->getFromRedisCache($keyLastActivity)) === false) {
            $dataLastActivity = array();
            foreach ($teams as $t) {
                $tid = $t['teamid'];
                $tObj = Team::Hydrate($t['teamid'], $t);
                $dataLastActivity[$tid]['last_activity'] = $tObj->getTeamLastActivity();
            }
            $_COMPANY->putInRedisCache($keyLastActivity, $dataLastActivity, 1800);
        }

        // Section - Get Feedback count
        $keyFeedbackCount ="GRP_TEAM_DECORATED_TEAMS__FEEDBACK_COUNT:{$groupid}";
        if (($dataFeedbackCount = $_COMPANY->getFromRedisCache($keyFeedbackCount)) === false) {
            $dataFeedbackCount = array();
            foreach ($teams as $t) {
                $tid = $t['teamid'];
                $tObj = Team::Hydrate($t['teamid'], $t);
                $dataFeedbackCount[$tid]['feedback_count'] = $tObj->getTeamsFeedbackCount();
            }
            $_COMPANY->putInRedisCache($keyFeedbackCount, $dataFeedbackCount, 3600);
        }

        // Finally complete the decoration
        foreach ($teams as &$team) {
            $teamid = $team['teamid'];
            $team['team_members'] = $dataMembers[$teamid]['team_members'] ?? [];
            $team['last_activity'] = $dataLastActivity[$teamid]['last_activity'] ?? '';
            $team['feedback_count'] = $dataFeedbackCount[$teamid]['feedback_count'] ?? 0;
        }
    }
	public static function CreateOrUpdateTeam(int $groupid, int $teamid,string $team_name, int $chapterid = 0, int $creatorId = 0, string $team_description='', string $handleids = ''){
		global $_COMPANY,$_ZONE,$_USER;
		$team_name = self::MakeTeamNameUnique($groupid,$teamid, $team_name);
		if (!$creatorId){
			$creatorId = $_USER->id();
		}
		if ($teamid>0){
			$retval = self::DBUpdatePS("UPDATE `teams` SET `team_name`=?,`chapterid`=?,`modifiedon`=NOW(),`team_description`=?, `handleids`=? WHERE `companyid`=? AND `zoneid`=? AND `groupid`=? AND `teamid`=?","xixxiiii",$team_name,$chapterid,$team_description, $handleids, $_COMPANY->id(),$_ZONE->id(),$groupid,$teamid);

			$team = Team::GetTeam($teamid);
			$team->postUpdate();

			return $retval;
		} else {
			$teamid = self::DBInsertPS("INSERT INTO `teams`(`companyid`, `zoneid`, `groupid`, `team_name`,`chapterid`, `createdby`,`team_description`,`handleids`) VALUES (?,?,?,?,?,?,?,?)","iiixiixx",$_COMPANY->id(),$_ZONE->id(), $groupid, $team_name, $chapterid, $creatorId, $team_description, $handleids);

			$team = Team::GetTeam($teamid);
			$team->postCreate();

			return $teamid;
		}
	}

	
    /**
     * Get team's Tasks list
     */
    public function getTeamsTodoList(){
		global $_COMPANY,$_ZONE,$_USER;
		$data = self::DBGet("SELECT team_tasks.*,IFNULL(users.firstname,'Deleted') as firstname,IFNULL(users.lastname,'User') as lastname,IFNULL(users.picture,'') as picture FROM `team_tasks` LEFT JOIN users ON users.userid=team_tasks.assignedto WHERE `team_tasks`.`companyid`='{$_COMPANY->id()}' AND `team_tasks`.`zoneid`='{$_ZONE->id()}' AND team_tasks.`teamid`='{$this->id}' AND team_tasks.task_type='todo' ");
		usort($data, function($a, $b) {
			return $a['duedate'] <=> $b['duedate'];
		});
		return $data;
	}

	public function getTodoDetail(int $taskid, bool $return_model = false): array|TeamTask|null
	{
		global $_COMPANY,$_ZONE,$_USER;
		$todo = null;
		$t =  self::DBGet("SELECT team_tasks.*,IFNULL(users.firstname,'Deleted') as firstname, IFNULL(users.lastname,'User') as lastname, IFNULL(users.email,'') as email,IFNULL(users.picture,'') as picture, IFNULL(u.firstname,'Deleted') as creator_firstname,IFNULL(u.lastname,'User') as creator_lastname,IFNULL(u.email,'') as creator_email,IFNULL(u.picture,'') as creator_picture FROM `team_tasks` LEFT JOIN users ON users.userid=team_tasks.assignedto LEFT JOIN users as u ON u.userid=team_tasks.`createdby` WHERE  `team_tasks`.`companyid`='{$_COMPANY->id()}' AND `team_tasks`.`zoneid`='{$_ZONE->id()}' AND  team_tasks.`teamid`='{$this->id}' AND team_tasks.taskid='{$taskid}'");
		if(!empty($t)){
			$todo = $t[0];
		}

		if (!$return_model) {
		return $todo;
	}
	
		if (empty($t)) {
			return null;
		}

		return TeamTask::Hydrate($taskid, $t[0]);
	}

	public function addOrUpdateTeamTask(int $taskid, string $tasktitle, string $assignedto,?string $duedate, string $description, string $task_type, int $visibility,int $parent_taskid = 0){
		global $_COMPANY,$_ZONE,$_USER;

        $created_by = $_USER ?-> id() ?? 0;

        ContentModerator::CheckBlockedWords($tasktitle, $description);

        if ($taskid>0){
            self::DBUpdatePS("UPDATE `team_tasks` SET `tasktitle`=?,`assignedto`=?,`duedate`=?,`description`=?,`modifiedon`=NOW(),`visibility`=?, modifiedby=? WHERE companyid=? AND zoneid=? AND `taskid`=? AND `teamid`=?","xisxiiiiii",$tasktitle,$assignedto,$duedate,$description,$visibility,$created_by,$_COMPANY->id(),$_ZONE->id(),$taskid,$this->id);
		} else {
            $taskid = self::DBInsertPS("INSERT INTO `team_tasks`(`companyid`,`zoneid`,`teamid`, `tasktitle`, `assignedto`, `duedate`, `description`, `task_type`, `createdby`, `createdon`,`visibility`,`parent_taskid`) VALUES (?,?,?,?,?,?,?,?,?,NOW(),?,?)","iiixisxsiii",$_COMPANY->id(),$_ZONE->id(),$this->id,$tasktitle,$assignedto,$duedate,$description,$task_type, $created_by,$visibility,$parent_taskid);
		}

        if ($task_type == 'feedback') {
            $keyFeedbackCount ="GRP_TEAM_DECORATED_TEAMS__FEEDBACK_COUNT:{$this->val('groupid')}";
            $_COMPANY->expireRedisCache($keyFeedbackCount, 180); // Expire after 3 minutes
        }

        return $taskid;
	}


	public function deleteTeamPermanently()
    {
        global $_COMPANY, $_ZONE;
		$teamTasks = self::DBGet("SELECT `taskid`,`eventid` FROM `team_tasks` WHERE `teamid`={$this->id()}");
		foreach($teamTasks as $task){
            $this->deleteTeamTask($task['taskid']);
		}
        $teamMembers = $this->getTeamMembers(0);
        foreach($teamMembers as $member){
            $this->deleteTeamMember($member['team_memberid']);
        }
		return self::DBUpdate("DELETE FROM `teams` WHERE `companyid`={$_COMPANY->id()} AND `teamid`={$this->id()}");
    }


	public function addUpdateTeamMember(int $roleid, int $userid, int $team_memberid=0, string $roletitle='')
    {
		global $_COMPANY,$_ZONE,$_USER;

        $check = self::DBGet("SELECT `team_memberid`,`roleid` FROM `team_members` WHERE `teamid`='{$this->id}' AND `userid`='{$userid}'");

        if (!empty($check)){
            if (count($check) > 1 || (count($check) == 1 && $roleid !=$check[0]['roleid']) || ($team_memberid && $team_memberid != $check[0]['team_memberid'])) {
                return; // Do not update as user is not supposed to the in a team more than once.
            }
        }

		$m = User::GetUser($userid);

		if (
			$_ZONE->val('app_type') !== 'talentpeak'
			&& !$m->isGroupMember($this->val('groupid'))
		) {
			AjaxResponse::SuccessAndExit_STRING(
				0,
				'',
				sprintf(
					gettext('Please add this user to the %s first before adding to a %s'),
					$_COMPANY->getAppCustomization()['group']['name'],
					$_COMPANY->getAppCustomization()['teams']['name'],
				),
				gettext('Error')
			);
		}

		if (!$team_memberid){
			$check = self::DBGet("SELECT team_memberid FROM `team_members` WHERE `teamid`='{$this->id}' AND `roleid`='{$roleid}' AND `userid`='{$userid}'");
			if (!empty($check)){
				$team_memberid = $check[0]['team_memberid']; // To avoid duplicate membership. This is the case of Team data import
			}
		}


		if($team_memberid){
			self::DBUpdatePS("UPDATE `team_members` SET `roletitle`=? WHERE `teamid`=? AND `userid`=? AND team_memberid=?","xiii",$roletitle,$this->id,$userid,$team_memberid);
            // Since we are only updating roletitle, no need to add user log.
            // GroupUserLogs::CreateGroupUserLog($this->val('groupid'), $userid, GroupUserLogs::GROUP_USER_LOGS_ACTION['UPDATE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['TEAM_MEMBER'], $roleid, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['TEAM'], $this->id,GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
            return 1;
		} else {
			$m->joinGroup($this->val('groupid'), $this->val('chapterid'), 0);
            $memberid = self::DBInsertPS("INSERT INTO `team_members`(`teamid`, `userid`, `roleid`,`roletitle`, `createdon`) VALUES (?,?,?,?,NOW())","iiix",$this->id,$userid,$roleid,$roletitle);
			if($memberid){

				// Update Used role capacity
				self::UpdateUsedRoleCapacity($this->val('groupid'),$userid,$roleid);

				// Group User Log
				GroupUserLogs::CreateGroupUserLog($this->val('groupid'), $userid, GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['TEAM_MEMBER'], $roleid, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['TEAM'], $this->id, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);

				if($this->val('isactive') == self::STATUS_ACTIVE){
                    // If the team is active then send upcoming event invitations to the newly added Team member
					$this->sendUpcomingEventsEmailInvitations($m->id());
					// Send team todo/touchpoint memberlist  to user
					$this->sendTeamStatusChangeEmailToTeamMembers(self::STATUS_ACTIVE, $memberid);
				}

                // Do the post team member addition cleanup
                // POST_ADDITION_1. Cancel outstanding requests ... outstanding requests are applicable only to Peer2Peer Team Types
                Team::CancelOutstandingRequestsIfCapacityHasBeenReached($this->val('groupid'), $userid, $roleid);

                $keyMembers ="GRP_TEAM_DECORATED_TEAMS__MEMBERS:{$this->val('groupid')}";
                $_COMPANY->expireRedisCache($keyMembers, 15); // Expire after 15 seconds

				return $memberid;
			}
		}
		return 0;
	}

    /**
     * Updates the used_capacity in member_join_requests to value that matches the number of teams a user is part of.
     * Also updates teams_incomplete_count, teams_complete_count, and teams_assigned_count.
     * @param int $groupid
     * @param int $userid
     * @param int $roleid
     * @return int
     */
	public static function UpdateUsedRoleCapacity(int $groupid, int $userid, int $roleid) {
		global $_COMPANY,$_ZONE;
        $retVal = 0;
		#$retVal =  self::DBUpdate("UPDATE `member_join_requests` SET `used_capacity`=(SELECT COUNT(1) FROM `team_members` JOIN teams USING(teamid) WHERE `team_members`.userid=`member_join_requests`.userid AND `team_members`.roleid=`member_join_requests`.roleid AND teams.groupid=`member_join_requests`.groupid AND teams.isactive NOT IN(109,110)) WHERE `companyid`={$_COMPANY->id()} AND `groupid`={$groupid} AND `userid`={$userid} AND `roleid`={$roleid}");
        $counts = self::DBGet("
            SELECT
                   COUNT(1) AS `teams_assigned_count`,
                   SUM(IF(`teams`.`isactive`=109,1,0)) AS teams_incomplete_count,
                   SUM(IF(`teams`.`isactive`=110,1,0)) AS teams_complete_count,
                   SUM(IF(`teams`.`isactive` NOT IN (109, 110),1,0)) AS used_capacity_count
            FROM `team_members` JOIN `teams` USING (`teamid`)
            WHERE `team_members`.`userid` = {$userid} 
              AND `team_members`.`roleid` = {$roleid} 
              AND `teams`.`groupid` = {$groupid}"
        );

        if (!empty($counts)) {
            // Cast them to int convert NULL's to 0's
            $used_capacity_count = (int)$counts[0]['used_capacity_count'];
            $teams_assigned_count = (int)$counts[0]['teams_assigned_count'];
            $teams_incomplete_count = (int)$counts[0]['teams_incomplete_count'];
            $teams_complete_count = (int)$counts[0]['teams_complete_count'];

            $retVal = self::DBMutate("
                    UPDATE `member_join_requests`
                    SET
                        `used_capacity` = {$used_capacity_count},
                        `teams_assigned` = {$teams_assigned_count},
                        `teams_incomplete` = {$teams_incomplete_count},
                        `teams_complete` = {$teams_complete_count}
                    WHERE `groupid` = {$groupid} 
                      AND `userid` = {$userid}
                      AND `roleid` = {$roleid}
                    ");
        }
        return $retVal;
	}

	public function updateTeamTaskStatus(int $taskid, int $status): int
    {
		global $_COMPANY,$_ZONE,$_USER;
		$retVal = 0;
        if (in_array($status,array_keys(Team::GET_TALENTPEAK_TODO_STATUS))){
		    self::DBUpdate("UPDATE `team_tasks` SET `isactive`='{$status}',`modifiedon`=NOW(),modifiedby={$_USER->id()} WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND `taskid`={$taskid} AND  `teamid`={$this->id}");
			$retVal = 1;
			if ($status == 52){ //If the status is 'Done,' then proceed to check the team completion setting.
				$group = Group::GetGroup($this->val('groupid'));
				$teamWorkflowSetting = $group->getTeamWorkflowSetting();
				if ($teamWorkflowSetting['auto_complete_team_on_action_touchpoints_complete'] == 1) {
					[$pendingActionItemsCount,$pendingTouchPointsCount] = $this->GetPendingActionItemAndTouchPoints();
					if (($pendingActionItemsCount+$pendingTouchPointsCount) == 0) {// All Action item and touch points are done
						$this->complete();
						$retVal = 2;
					}
				}
			}
        }
		return $retVal;
	}

	public function deleteTeamMember(int $team_memberid){
		global $_COMPANY,$_ZONE,$_USER;
		$returnStatus = 0;
		$memberDetail = self::DBGet("SELECT `userid`,`roleid` FROM `team_members` WHERE `teamid`='{$this->id}' AND `team_memberid`='{$team_memberid}'");
        $modified_by = $_USER ?-> id() ?? 0;

		if (!empty($memberDetail)){
			if (self::DBUpdatePS("DELETE FROM `team_members` WHERE `teamid`=? AND `team_memberid`=?","ii",$this->id,$team_memberid)){
				// Update Used role capacity
				self::UpdateUsedRoleCapacity($this->val('groupid'),$memberDetail[0]['userid'],$memberDetail[0]['roleid']);

				// Remove role request if program type is circle and user have not teams with requested role
                $this->deleteEmptyJoinRequests($memberDetail[0]['userid'], $memberDetail[0]['roleid']);

				// Group User Log
				GroupUserLogs::CreateGroupUserLog($this->val('groupid'), $memberDetail[0]['userid'], GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['TEAM_MEMBER'], $memberDetail[0]['roleid'], GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['TEAM'],$this->id, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);

				self::DBUpdate("UPDATE `team_tasks` SET `assignedto`=0, `modifiedon`=NOW(), modifiedby={$modified_by} WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `teamid`={$this->id} AND `assignedto`={$memberDetail[0]['userid']}");

				// Leave group membership if not a member of any team
				if (
					(self::GetWhyCannotLeaveProgramMembership($this->val('groupid'),$memberDetail[0]['userid']) === '')
					&& ($_ZONE->val('app_type') === 'talentpeak')
				) { // LEAVE GROUP IF CAN
					$u = User::GetUser($memberDetail[0]['userid']);
					if ($u){ // check if user exist
						$u->leaveGroup($this->val('groupid'), 0, 0);
					}
				}

				$returnStatus = 1;
			} 
		}
		return $returnStatus;
	}

	
	public function deleteTeamTask(int $taskid){
		global $_COMPANY,$_ZONE,$_USER;
        $teamTask = self::DBGet("SELECT `taskid`,`eventid` FROM `team_tasks` WHERE companyid={$_COMPANY->id()} AND `teamid`={$this->id()} AND taskid={$taskid}");
        foreach ($teamTask as $task) {
            $taskid = intval($task['taskid']);

			$team_task_model = TeamTask::Hydrate($taskid, $task);

            self::DeleteAllComments_2($taskid);
            self::DeleteAllLikes($taskid);
            if (!empty($task['eventid'])) {
                $teamEvent = TeamEvent::GetEvent($task['eventid']);
                if ($teamEvent) {
                    $sendCancellationEmails = true; // For team events always send cancellation emails
                    $teamEvent->cancelEvent('Touchpoint Deleted', $sendCancellationEmails);
                }
            }

            if (self::DBUpdate("DELETE FROM `team_tasks` WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND `taskid`='{$taskid}' AND `teamid`={$this->id}")) {
                self::DBUpdate("UPDATE `team_tasks` SET `parent_taskid`=0 WHERE  companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND `teamid`='{$this->id}' AND `parent_taskid`={$taskid}");

				$team_task_model->deleteAllAttachments();
            }
        }
        return 1;
	}
	
	public function getTeamsTouchPointsList(){
		global $_COMPANY,$_ZONE,$_USER;
		$data = self::DBGet("SELECT team_tasks.*,IFNULL(users.firstname,'Deleted') as firstname, IFNULL(users.lastname,'User') as lastname,IFNULL(users.picture,'') as picture, `events`.eventtitle FROM `team_tasks` LEFT JOIN users ON users.userid=team_tasks.createdby LEFT JOIN `events` USING (eventid) WHERE `team_tasks`.`companyid`='{$_COMPANY->id()}' AND `team_tasks`.`zoneid`='{$_ZONE->id()}' AND  team_tasks.`teamid`='{$this->id}' AND team_tasks.task_type='touchpoint' ");
		usort($data, function($a, $b) {
			return $a['duedate'] <=> $b['duedate'];
		});
		return $data;
	}

	public function getTeamsFeedbackList(){
		global $_COMPANY,$_ZONE,$_USER;
		
		return self::DBGet("SELECT team_tasks.*,IFNULL(users.firstname,'Deleted') as firstname, IFNULL(users.lastname,'User') as lastname, IFNULL(users.picture,'') as picture FROM `team_tasks` LEFT JOIN users ON users.userid=team_tasks.assignedto WHERE `team_tasks`.`companyid`={$_COMPANY->id()} AND team_tasks.`teamid`={$this->id} AND team_tasks.task_type='feedback'");
	}

	public function getTeamsFeedbackCount(){
		global $_COMPANY,$_ZONE,$_USER;

		$result = self::DBROGet("SELECT COUNT(*) AS total_feedbacks FROM team_tasks WHERE task_type='feedback' AND teamid={$this->id} AND `companyid`={$_COMPANY->id()}");

		return $result[0]['total_feedbacks'];
	}

	public function getTeamLastActivity() : bool|string
    {
		global $_COMPANY;
		$latestUpdate = self::DBROGet("SELECT `modifiedon` FROM `team_tasks` WHERE `companyid`={$_COMPANY->id()} AND `teamid`={$this->val('teamid')} ORDER BY `modifiedon` DESC LIMIT 1");

		if (!empty($latestUpdate)) {
			return $latestUpdate[0]['modifiedon'];
		}
		return false;
	}

	public function getTodoDetailWithChild(int $taskid){
		global $_COMPANY,$_ZONE,$_USER;
		
		return self::DBGet("SELECT team_tasks.*,IFNULL(users.firstname,'Deleted') as firstname, IFNULL(users.lastname,'') as lastname,IFNULL(users.picture,'') as picture,IFNULL(u.firstname,'Deleted') as creator_firstname,IFNULL(u.lastname,'User') as creator_lastname,IFNULL(u.picture,'') as creator_picture FROM `team_tasks` LEFT JOIN users ON users.userid=team_tasks.assignedto LEFT JOIN users as u ON u.userid=team_tasks.`createdby` WHERE `team_tasks`.`companyid`='{$_COMPANY->id()}' AND `team_tasks`.`zoneid`='{$_ZONE->id()}' AND team_tasks.`teamid`='{$this->id}' AND ( team_tasks.taskid='{$taskid}' || team_tasks.parent_taskid = '{$taskid}') ");
	}

    /**
     * @param int $groupid
     * @param int $roleid
     * @param string $role_survey_response
     * @param int $request_capacity
     * @return int returns 2 on insert and 1 on update
     */
    public static function SaveTeamJoinRequestData(int $groupid, int $roleid, string $role_survey_response, int $request_capacity = 1, bool $sendEmailNotification = true, int $userid=0, string $chapterids = '0'){
        global $_COMPANY,$_ZONE,$_USER;
        // if $userid is not provided then it is self update by the signed in user.
        if (!$userid){
            $userid = $_USER->id();
        }

        $userObj = User::GetUser($userid);
        if (!$userObj) {
            return 0;
        }

        $role_survey_response = json_encode(json_decode($role_survey_response,true) ?: (object)array());// To make sure it is a proper JSON
        $check = self::GetUserRegistrationRecord($groupid, $userid, $roleid);
        if (!empty($check)){
            $updated = self::DBUpdatePS("UPDATE `member_join_requests` SET `role_survey_response`=?, `request_capacity`=?,`chapterids`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `roleid`=? AND `userid`=? AND `groupid`=?",
                'xixiiii',
                $role_survey_response,$request_capacity,$chapterids,$_COMPANY->id(),$roleid,$userid,$groupid);
            if ($updated) {
                if($sendEmailNotification){
                    self::SendTeamRequestUpdateNotification($groupid, $userObj, $roleid, 'update');
                }
                return 1;
            }
        } else {

            $inserted = self::DBInsertPS("INSERT INTO `member_join_requests`
									(`companyid`,`roleid`, `userid`,`groupid`, `role_survey_response`, `request_capacity`,`chapterids`, `modifiedon`, isactive)
								VALUES
									(?, ?, ?, ?, ?,?,?, NOW(),1)",
                'iiiixix',
                $_COMPANY->id(),$roleid,$userid,$groupid,$role_survey_response,$request_capacity,$chapterids);

			if ($inserted){
				self::UpdateUsedRoleCapacity($groupid,$userid,$roleid); // Update the capacity, because as per new flow we are allowing user to cancel requested role request even after request processing.
			}
            $check2 = self::GetUserRegistrationRecord($groupid, $userid, $roleid);
            if (!empty($check2)) {
                if($sendEmailNotification){
                    self::SendTeamRequestUpdateNotification($groupid, $userObj, $roleid, 'create');
                }
                return 2;
            }
        }
        return 0;
    }
	
	public static function GetRequestDetail(int $groupid, int $roleid, int $userid = 0){
		global $_COMPANY,$_ZONE,$_USER;
		if (!$userid){
			$userid = $_USER->id();
		}
		$data = null;
		$r =  self::DBGet("SELECT member_join_requests.*,team_role_type.type,team_role_type.sys_team_role_type, team_role_type.role_request_buffer FROM `member_join_requests` LEFT JOIN team_role_type ON team_role_type.roleid=member_join_requests.roleid AND team_role_type.groupid='{$groupid}' WHERE `member_join_requests`.`companyid`='{$_COMPANY->id()}' AND member_join_requests.roleid ='{$roleid}' AND  member_join_requests.userid='{$userid}' AND member_join_requests.groupid='{$groupid}'");

		if (!empty($r)){
			$data = $r[0];
		}
		return $data;
	}

	public static function GetUserJoinRequests(int $groupid, int $userid = 0, int $activeOnly = 1){
		global $_COMPANY,$_ZONE,$_USER;
		if (!$userid){
			$userid = $_USER->id();
		}
		$activeCondition = "";
		if ($activeOnly){
			$activeCondition = " AND member_join_requests.isactive='1' ";
		}

		return self::DBGet("SELECT member_join_requests.*,team_role_type.type,team_role_type.sys_team_role_type, team_role_type.role_request_buffer FROM `member_join_requests` JOIN team_role_type ON team_role_type.roleid=member_join_requests.roleid AND team_role_type.groupid='{$groupid}' WHERE `member_join_requests`.`companyid`='{$_COMPANY->id()}' AND member_join_requests.userid='{$userid}' AND member_join_requests.groupid='{$groupid}' {$activeCondition} ");
		
	}
	public static function CancelTeamJoinRequest(int $groupid, int $roleid, int $userid, string $action = 'cancel', bool $sendEmailNotification=true, ?string $customSubject=null, ?string $customMessage=null){
		global $_COMPANY, $_USER, $_ZONE;

        if (!in_array($action, ['decline', 'cancel'])) {
            return;
        }

		$updated = self::DBUpdate("DELETE FROM `member_join_requests` WHERE `member_join_requests`.`companyid`='{$_COMPANY->id()}' AND `roleid`='{$roleid}' AND `userid`='{$userid}' AND groupid='{$groupid}'");
        if ($updated) {
            $u = User::GetUser($userid);

            if ($sendEmailNotification) {
                self::SendTeamRequestUpdateNotification($groupid, $u, $roleid, $action, $customSubject, $customMessage);
            }
			// Leave group if there are no refernces to team or team requests
			if (
				(self::GetWhyCannotLeaveProgramMembership($groupid, $userid) === '')
				&& ($_ZONE->val('app_type') === 'talentpeak')
			) {
				$u->leaveGroup($groupid, 0, 0);
			}
        }
        return 1;
	}

    public static function TogglePauseTeamJoinRequest(int $groupid, int $roleid, int $userid){
        global $_COMPANY;
        return self::DBMutate("UPDATE `member_join_requests` SET isactive=IF(isactive=2, 1, 2) WHERE `companyid`={$_COMPANY->id()} AND `roleid`={$roleid} AND `userid`={$userid} AND groupid={$groupid} AND isactive in (1,2)");
    }

	public static function ToggleActivateTeamJoinRequestStatus(int $isactive, int $groupid, int $roleid, int $userid)
	{
		global $_COMPANY;

        return self::DBMutate("
			UPDATE `member_join_requests`
			SET 	`isactive` = {$isactive}
			WHERE 	`companyid` = {$_COMPANY->id()}
			AND 	`roleid` = {$roleid}
			AND 	`userid`= {$userid}
			AND 	`groupid` = {$groupid}
		");
	}

    /**
     * Gets all the Team Join requests in a program. The data is filtered down to specified roleids if specified.
     * @param int $groupid
     * @param int $roleid if set the data is matched only for the specified roleids
     * @param bool $activeOnly
     * @param string $searchKeyword
     * @param string $orderByField
     * @param int $start
     * @param int $limit
     * @param bool $returnTotalRecordCountOnly
     * @param array $teamsFilters - valid filters are 'unassigned', 'assigned', 'completed', 'incomplete'
     * @return array|mysqli_result
     */
	public static function GetTeamJoinRequests(int $groupid, int $roleid = 0, bool $activeOnly = false, string $searchKeyword = '', string $orderByField='', int $start = -1, int $limit = -1, bool $returnTotalRecordCountOnly = false, array $teamsFilters = [])
    {
		global $_COMPANY,$_ZONE;

		$roleCondition = '';
		if ($roleid){
			$roleCondition = " AND member_join_requests.roleid='{$roleid}'";
		}

		$teamsFilterCondition = '';
        if (!empty($teamsFilters)) {
            $conditions = [];
            if (in_array('unassigned', $teamsFilters)) $conditions[] = 'member_join_requests.teams_assigned = 0';
            if (in_array('assigned', $teamsFilters)) $conditions[] = 'member_join_requests.teams_assigned > 0';
            if (in_array('complete', $teamsFilters)) $conditions[] = 'member_join_requests.teams_complete > 0';
            if (in_array('incomplete', $teamsFilters)) $conditions[] = 'member_join_requests.teams_incomplete > 0';

            if (!empty($conditions)) {
                $teamsFilterCondition = ' AND ('. implode( ' OR ', $conditions) . ')';
            }
        }

		$activeCondition = "";
		if ($activeOnly){
			$activeCondition = " AND member_join_requests.isactive= 1";
		}

		
		$limitFilter = '';
        if ($start !=-1 && $limit !=-1) {
            $limitFilter = " LIMIT  {$start}, {$limit}";
        }

		$orderby = '';
		if ($orderByField) {
			$orderby = " ORDER BY {$orderByField}";
		}

		$search = $searchKeyword .'%';

		if ($returnTotalRecordCountOnly) {
			return self::DBROGetPS("
					SELECT 
						COUNT(1) as totalRows
					FROM member_join_requests 
						JOIN users ON member_join_requests.userid=users.userid AND member_join_requests.companyid=users.companyid
						LEFT JOIN team_role_type ON member_join_requests.roleid = team_role_type.roleid AND member_join_requests.companyid=team_role_type.companyid
					WHERE member_join_requests.companyid={$_COMPANY->id()} 
					AND member_join_requests.groupid={$groupid} {$roleCondition} {$activeCondition} {$teamsFilterCondition}
					AND (users.firstname LIKE ? OR users.lastname LIKE ? OR users.email LIKE ?)
					",'sss',$search, $search, $search)[0]['totalRows'];
		} else {
			return self::DBROGetPS("
					SELECT 
						member_join_requests.join_requestid,
						member_join_requests.roleid,
						member_join_requests.modifiedon, 
						member_join_requests.role_survey_response, 
						member_join_requests.chapterids,
						member_join_requests.isactive, 
						member_join_requests.request_capacity,
						member_join_requests.used_capacity,
						team_role_type.type as roleType, 
						team_role_type.sys_team_role_type,
						users.`userid`,
						users.`firstname`,
						users.`lastname`,
						users.`email`,
					    users.`external_email`,
						users.`homeoffice`,
						users.`department`,
						users.`regionid`,
						users.picture,
						IF (users.jobtitle='','Job title unavailable',users.jobtitle) as jobtitle,
						`users`.`employee_start_date`
					FROM member_join_requests 
						JOIN users ON member_join_requests.userid=users.userid AND member_join_requests.companyid=users.companyid
						LEFT JOIN team_role_type ON member_join_requests.roleid = team_role_type.roleid AND member_join_requests.companyid=team_role_type.companyid
					WHERE member_join_requests.companyid={$_COMPANY->id()} 
					AND member_join_requests.groupid={$groupid} {$roleCondition} {$activeCondition} {$teamsFilterCondition}
					AND (users.firstname LIKE ? OR users.lastname LIKE ? OR users.email LIKE ?)
					{$orderby}
					{$limitFilter}
					",'sss',$search,$search,$search);
		
		}
	}

    /**
     * @param Group $group
     * @param int $newStatus
     * @param array|null $teamRole
     * @return string[] containing $subject, $message
     */
    public static function GetEmailSubjectAndMessageForTeamStatusChange(Group $group, int $newStatus, ?array $teamRole): array
    {
        $subject = '';
        $message = '';
        $teamCustomName = Team::GetTeamCustomMetaName($group->getTeamProgramType(), 0);

        if ($newStatus == self::STATUS_ACTIVE) {
            $subject = "Your {$group->val('groupname')} {$teamCustomName} is now active!";
            $message .= "<p>Hi [[PERSON_FIRST_NAME]],</p>";
            //$message .= "<br>";
            $message .= "<p>The [[TEAM_NAME]] {$teamCustomName} is now active, and you have been assigned the role <strong>[[TEAM_MEMBER_ROLE_AND_TITLE]]</strong></p>";
            //$message .= "<br>";
            //$message .= '<hr/>';
            // << Pre
            //$message = $teamRole['welcome_message'] ?: $message;
            // >> Post
            // $message .= '<hr/>';
            //$message .= "<br>";
            $message .= "<div><strong>{$teamCustomName} Members:</strong></div>";
            $message .= "[[TEAM_MEMBER_LIST]]";
            $message .= "<br>";
            $message .= "<br>";
            $message .= "[[TEAM_URL_BUTTON]]";

            // Next if team role is provided, override deault
            if ($teamRole) {
                $subject = $teamRole['welcome_email_subject'] ?: $subject;
                $message = $teamRole['welcome_message'] ?: $message;
            }

        } elseif ($newStatus == self::STATUS_INCOMPLETE) {
            $subject = "Your {$group->val('groupname')} {$teamCustomName} has been closed as incomplete!";
            $message .= "<p>Hi [[PERSON_FIRST_NAME]],</p>";
            //$message .= "<br>";
            $message .= "<p>The [[TEAM_NAME]] {$teamCustomName} has been closed as incomplete.</p>";
            $message .= "<br>";
            $message .= "<br>";
            $message .= "[[MY_TEAMS_SECTION_URL_BUTTON]]";
            // Next if team role is provided, override deault
            //if ($teamRole) {
            //    $subject = $teamRole['incomplete_team_email_subject'] ?: $subject;
            //    $message = $teamRole['incomplete_team_message'] ?: $message;
            //}
        }elseif ($newStatus == self::STATUS_PAUSED) {
            $subject = "Your {$group->val('groupname')} {$teamCustomName} has been marked as paused!";
            $message .= "<p>Hi [[PERSON_FIRST_NAME]],</p>";
            $message .= "<p>The [[TEAM_NAME]] {$teamCustomName} has been marked as paused.</p>";
            $message .= "<br>";
            $message .= "<br>";
            $message .= "[[MY_TEAMS_SECTION_URL_BUTTON]]";
        } elseif ($newStatus == self::STATUS_COMPLETE) {
            $subject = "Your {$group->val('groupname')} {$teamCustomName} has been completed!";
            $message .= "<p>Hi [[PERSON_FIRST_NAME]],</p>";
            //$message .= "<br>";
            $message .= "<p>The [[TEAM_NAME]] {$teamCustomName} has been completed.</p>";
            $message .= "<br>";
            $message .= "<br>";
            $message .= "[[MY_TEAMS_SECTION_URL_BUTTON]]";
            // Next if team role is provided, override deault
            if ($teamRole) {
                $subject = $teamRole['completion_email_subject'] ?: $subject;
                $message = $teamRole['completion_message'] ?: $message;
            }
        }

        return array($subject, $message);
    }

    /**
     * @param int $status
     * @param int|null $toMemberId optional, if not provided, then email is sent to all team members. The use case to
     * provide $toMemberId arises from the need to send email when user is added to an active team.
     * @return void
     */
	public function sendTeamStatusChangeEmailToTeamMembers(int $status, ?int $toMemberId = null)
    {
		global $_COMPANY,$_ZONE,$db;

        if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_COMPLETE, self::STATUS_INCOMPLETE, self::STATUS_PAUSED])) {
            return;
        }

        $group = Group::GetGroup($this->val('groupid'));
        $myTeamsSectionUrl = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'detail?id='.$_COMPANY->encodeId($this->val('groupid')).'&hash=getMyTeams#getMyTeams';
        $teamHash = 'getMyTeams-'.$_COMPANY->encodeId($this->id());
        $teamUrl = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'detail?id=' . $_COMPANY->encodeId($this->val('groupid')) . '&hash=' . $teamHash . '#' . $teamHash;
        $teamCustomNamePlural = Team::GetTeamCustomMetaName($group->getTeamProgramType(), 1);
        $teamCustomNameSingular = Team::GetTeamCustomMetaName($group->getTeamProgramType(), 0);

        $teamActionItemListHTML = '';
        $todos = $this->getTeamsTodoList();
        if (!empty($todos)){
            $teamActionItemListHTML .= "<ul>";
            foreach($todos as $todo){
                $teamActionItemListHTML .= "<li>\"{$todo['tasktitle']}\" to be completed by {$todo['firstname']} {$todo['lastname']} by {$db->covertUTCtoLocalAdvance('M j, Y  T',"", $todo['duedate'], 'UTC')}</li>";
            }
            $teamActionItemListHTML .= "</ul>";
        }

        $teamTouchpointListHTML = '';
        $touchPoints = $this->getTeamsTouchPointsList();
        if (!empty($touchPoints)){
            $teamTouchpointListHTML .= "<ul>";
            foreach($touchPoints as $touchPoint){
                $teamTouchpointListHTML .= "<li>{$touchPoint['tasktitle']} by {$db->covertUTCtoLocalAdvance('M j, Y  T',"", $touchPoint['duedate'], 'UTC')}</li>";
            }
            $teamTouchpointListHTML .= "</ul>";
        }

        $teamMemberListHTML = '';
        $teamMembers = $this->getTeamMembers(0);
        if(!empty($teamMembers)){
            $teamMemberListHTML .= $this->getTeamMembersAsHtmlTable($teamMembers);
        }

        $emailIds = array();

        $toMembers = $teamMembers;
        if ($toMemberId !== null) {
            $toMembers = array(Arr::SearchColumnReturnRow($teamMembers, $toMemberId, 'team_memberid'));
        }

        foreach ($toMembers as $member) {

            $memberObj = User::GetUser($member['userid']);
            if (!$memberObj)
                continue;

            if (empty($member['email'])) {
                continue;
            }

            if (in_array($member['email'], $emailIds)) {
                continue;
            }
            $emailIds[] = $member['email'];

            $teamRole = self::GetTeamRoleType($member['roleid']);
            if (!$teamRole)
                continue;

            list ($subject, $message) = self::GetEmailSubjectAndMessageForTeamStatusChange($group, $status, $teamRole);

            $team_name = htmlspecialchars($this->val('team_name'));
            $team_member_role_and_title = $member['role'] . (empty($member['roletitle']) ? '' : (' - ' . htmlspecialchars($member['roletitle'])));

			$manager_user = $memberObj->getUserHeirarcyManager();

            $emesg = str_replace(
                array(
                    '[[PERSON_FIRST_NAME]]', '[[PERSON_LAST_NAME]]', '[[TEAM_NAME]]', '[[TEAM_MEMBER_ROLE_AND_TITLE]]',
                    '[[TEAM_URL]]', '[[MY_TEAMS_SECTION_URL]]',
                    '[[UPCOMING_ACTION_ITEMS]]', '[[UPCOMING_TOUCHPOINTS]]', '[[TEAM_MEMBER_LIST]]',
                    '[[TEAM_URL_BUTTON]]', '[[MY_TEAMS_SECTION_URL_BUTTON]]',
                    '[[PERSON_JOB_TITLE]]',
                    '[[PERSON_START_DATE]]',
					'[[MANAGER_FIRST_NAME]]',
					'[[MANAGER_LAST_NAME]]',
					'[[MANAGER_EMAIL]]',
                ),
                array($member['firstname'], $member['lastname'], $team_name, $team_member_role_and_title,
                    $teamUrl, $myTeamsSectionUrl,
                    $teamActionItemListHTML, $teamTouchpointListHTML, $teamMemberListHTML,
                    EmailHelper::BuildHtmlButton("View {$teamCustomNameSingular}", $teamUrl), EmailHelper::BuildHtmlButton("My {$teamCustomNamePlural}", $myTeamsSectionUrl),
                    $member['jobtitle'],
                    $memberObj->getStartDate(),
					$manager_user?->val('firstname') ?? '',
					$manager_user?->val('lastname') ?? '',
					$manager_user?->getEmailForDisplay() ?? '',
                ),
                $message
            );

            $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
            $emesg = str_replace('#messagehere#', $emesg, $template);
            $_COMPANY->emailSend2($group->val('from_email_label'), $member['email'], $subject, $emesg, $_ZONE->val('app_type'), $group->val('replyto_email'));

            // Push notification
            $pushBodytext = $group->val('groupname').' '.$_COMPANY->getAppCustomization()['teams']['name'].': '.htmlspecialchars($this->val('team_name'));
            $this->sendTeamModulePushNotifications($member['userid'],self::PUSH_NOTIFICATIONS_STATUS['TEAM'],1,$subject,$pushBodytext);
        }
	}

	public function sendActionItemEmailToTeamMember(int $id, string $actionStatus = '') {
		global $_COMPANY,$_ZONE,$_USER,$db;
        $group = Group::GetGroup($this->val('groupid'));
        $groupCustomName = $_COMPANY->getAppCustomization()['group']['name-short'];
        $groupName = $group->val('groupname');
        $teamCustomName = Team::GetTeamCustomMetaName($group->getTeamProgramType());
        $teamName = htmlspecialchars($this->val('team_name'));

		$baseurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));
        $teamhash = 'getMyTeams-'.$_COMPANY->encodeId($this->id());
        $teamUrl = $baseurl . 'detail?id='.$_COMPANY->encodeId($this->val('groupid')) . '&hash='. $teamhash . '#'. $teamhash;

		$team_task_model = $this->getTodoDetail($id, true);
		$todo = $team_task_model?->toArray() ?? [];
		if ($todo) {
            $todoTasktitle = $todo['tasktitle'];
            $todoAssignee = $todo['firstname'].' '.$todo['lastname'];
            $todoDueDate = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($todo['duedate'],true,true,true);
            $todoStatus = self::GET_TALENTPEAK_TODO_STATUS[$todo['isactive']];
            $todoAssignedToUserid = $todo['assignedto'];

            $subject = '';
            $pushTitle = '';
            $nextSteps = '';
			if ($actionStatus == 'created'){
				$subject = "A new action item has been added to your {$teamCustomName}";
				$messageHeading = "The following Action Item has been added to your {$teamCustomName}.";
				$pushTitle = "A new action item has been added";
			} elseif ($actionStatus == 'updated') { // This is for simple update where the state has not changed
				$subject = "An action item for your {$teamCustomName} has been updated";
				$messageHeading = "The following Action Item for your {$teamCustomName} has been updated.";
				$pushTitle = "Action item has been updated";
			} else {
                if ($todo['isactive'] == 51) {
                    $subject = "The status of an Action Item for your {$teamCustomName} has been updated";
                    $messageHeading = "The status following Action Item has been updated to in progress.";
                    $pushTitle = "Action item status has been updated";
                } elseif ($todo['isactive'] == 52) {
                    $subject = "The status of an Action Item for your {$teamCustomName} has been completed";
                    $messageHeading = "Great news! The following Action Item has sucessfully been completed.";
                    $pushTitle = "Action item status has been completed";
                    $nextSteps = '<p>';
                    $nextSteps .= '<b>Next Steps:</b>';
                    $nextSteps .= '<ul>';
                    $nextSteps .= "<li>Review other Action Items of your {$teamCustomName} assigned to you.</li>";
                    $nextSteps .= "<li>Determine if there are any updates you want to make to them.</li>";
                    $nextSteps .= '</ul>';
                    $nextSteps .= '</p>';
                }
            }

            if (empty($subject) || empty($todoAssignedToUserid)) {
                return;
            }

            $message = "<p>Dear ##person_name##,</p>";
            $message .= '<br>';
			$message .= "<p>{$messageHeading}</p>";
            $message .= "<br>";
            $message .= '<div style="background-color:#80808026; padding:10px;">';
            $message .= "<p><b>{$groupCustomName} Name:</b> {$groupName}</p>";
            $message .= "<p><b>{$teamCustomName} Name:</b> {$teamName}</p>";
			$message .= "<p><b>Action Item:</b> {$todoTasktitle}</p>";
			$message .= "<p><b>Assignee:</b> {$todoAssignee}</p>";
			$message .= "<p><b>Due Date:</b> {$todoDueDate}</p>";
			$message .= "<p><b>Current Status:</b> {$todoStatus}</p>";
            $message .= "</div>";
			$message .= "<br>";
            if ($nextSteps) {
                $message .= $nextSteps;
            }
			$message .= EmailHelper::BuildHtmlButton('Go to my ' . $teamCustomName, $teamUrl);

			$message .= $team_task_model?->renderAttachmentsComponent('v24') ?? '';

            $teamMembers = $this->getTeamMembers(0);

            $toPerson = Arr::SearchColumnReturnRow($teamMembers, $todoAssignedToUserid, 'userid');
            $ccEmail = Arr::SearchColumnReturnColumnVal($teamMembers, $_USER->id(), 'userid', 'email');

            if (!empty($toPerson)) {
                $pushBodytext = $groupName . ' > ' . $teamCustomName . ': ' . $teamName;
                $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
                $template = str_replace('#messagehere#', $message, $template);

                // Email notification
                $person_name = $toPerson['firstname'] . ' ' . $toPerson['lastname'];
                $emesg = str_replace('##person_name##', $person_name, $template);
                $_COMPANY->emailSend2($group->val('from_email_label'), $toPerson['email'], $subject, $emesg, $_ZONE->val('app_type'), $group->val('replyto_email'), '', [], $ccEmail);

                // Push notification
                $this->sendTeamModulePushNotifications($toPerson['userid'], self::PUSH_NOTIFICATIONS_STATUS['TEAM_ACTION_ITEAM'], 1, $pushTitle, $pushBodytext);
            }
		}
	}

	public function sendTouchPointEmailToTeamMember(int $id, string $actionStatus = ''){
		global $_COMPANY,$_ZONE,$_USER,$db;
        $group = Group::GetGroup($this->val('groupid'));
        $groupCustomName = $_COMPANY->getAppCustomization()['group']['name-short'];
        $groupName = $group->val('groupname');
        $teamCustomName = Team::GetTeamCustomMetaName($group->getTeamProgramType());
        $teamName = htmlspecialchars($this->val('team_name'));

		$baseurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));
        $teamhash = 'getMyTeams-'.$_COMPANY->encodeId($this->id());
        $teamUrl = $baseurl . 'detail?id='.$_COMPANY->encodeId($this->val('groupid')) . '&hash='. $teamhash . '#'. $teamhash;

		$team_task_model = $this->getTodoDetail($id, true);
		$touchpoint = $team_task_model?->toArray() ?? [];

		if ($touchpoint /*&& $touchpoint['isactive'] !=51*/ ) {
		    $touchpointTitle = $touchpoint['tasktitle'];
            $touchpointDueDate = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($touchpoint['duedate'],true,true,true);
            $touchpointStatus = self::GET_TALENTPEAK_TODO_STATUS[$touchpoint['isactive']];

            $subject = '';
            $pushTitle = '';
            $nextSteps = '';
			if ($actionStatus == 'created'){
				$subject = "A new Touch Point has been added to your {$teamCustomName}";
				$messageHeading = "The following Touch Point has been added to your {$teamCustomName}.";
				$pushTitle = "A new Touch Point has been added";
			} elseif ($actionStatus == 'updated') { // This is for simple update where the state has not changed
				$subject = "A Touch Point for your {$teamCustomName} has been updated";
				$messageHeading = "The following Touch Point for your {$teamCustomName} has been updated.";
				$pushTitle = "Touch Point has been updated";
			} else {
                if ($touchpoint['isactive'] == 51) {
                    $subject = "The status of your {$teamCustomName} Touch Point has been updated";
                    $messageHeading = "The status of the following Touch Point has been updated to in progress.";
                    $pushTitle = "Touch Point status updated to in progress";
                } elseif ($touchpoint['isactive'] == 52) {
                    $subject = "The status of your {$teamCustomName} Touch Point has been completed";
                    $messageHeading = "Great news! The following Touch Point has sucessfully been completed.";
                    $pushTitle = "Touch Point status update to complete";
                    $nextSteps = '<p>';
                    $nextSteps .= '<b>Next Steps:</b>';
                    $nextSteps .= '<ul>';
                    $nextSteps .= "<li>Review the next Touch Point in your {$teamCustomName} and schedule the meeting.</li>";
                    $nextSteps .= "<li>Determine if there are any updates you want to make to the next agenda.</li>";
                    $nextSteps .= '</ul>';
                    $nextSteps .= '</p>';
                }
            }

			if (empty($subject)) {
                return;
            }

            $message = "<p>Dear ##person_name##,</p>";
            $message .= '<br>';
			$message .= "<p>{$messageHeading}</p>";
			$message .= "<br>";
            $message .= '<div style="background-color:#80808026; padding:10px;">';
            $message .= "<p><b>{$groupCustomName} Name:</b> {$groupName}</p>";
            $message .= "<p><b>{$teamCustomName} Name:</b> {$teamName}</p>";
			$message .= "<p><b>Touch Point:</b> {$touchpointTitle}</p>";
			$message .= "<p><b>Due Date:</b> {$touchpointDueDate}</p>";
			$message .= "<p><b>Current Status:</b> {$touchpointStatus}</p>";
            $message .= "</div>";
			$message .= "<br>";
            if ($nextSteps) {
                $message .= $nextSteps;
            }
			$message .= EmailHelper::BuildHtmlButton('Go to my ' . $teamCustomName, $teamUrl);

			$message .= $team_task_model?->renderAttachmentsComponent('v24') ?? '';

            $pushBodytext = $groupName . ' > ' . $teamCustomName . ': ' . $teamName;
            $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
            $template = str_replace('#messagehere#', $message, $template);

            $teamMembers = $this->getTeamMembers(0);
            $emailIds = array();
            foreach ($teamMembers as $member) {
                if (in_array($member['email'], $emailIds)) {
                    continue;
                }
                // Email Notification
                $emailIds[] = $member['email'];
                // Email notification
                $person_name = $member['firstname'] . ' ' . $member['lastname'];
                $emesg = str_replace('##person_name##', $person_name, $template);
                $_COMPANY->emailSend2($group->val('from_email_label'), $member['email'], $subject, $emesg, $_ZONE->val('app_type'), $group->val('replyto_email'));
                // Push notification
				$this->sendTeamModulePushNotifications($member['userid'],self::PUSH_NOTIFICATIONS_STATUS['TEAM_TOUCHPOINT'],1,$pushTitle,$pushBodytext);
            }
		}
	}

	public function sendFeedbackEmailToTeamMember(int $taskid, string $actionStatus = ''){
		global $_COMPANY,$_ZONE,$_USER,$db;
        $group = Group::GetGroup($this->val('groupid'));
        $groupCustomName = $_COMPANY->getAppCustomization()['group']['name-short'];
        $groupName = $group->val('groupname');
        $teamCustomName = Team::GetTeamCustomMetaName($group->getTeamProgramType());
        $teamName = htmlspecialchars($this->val('team_name'));
        $baseurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));
        $teamhash = 'getMyTeams-'.$_COMPANY->encodeId($this->id());
        $teamUrl = $baseurl . 'detail?id='.$_COMPANY->encodeId($this->val('groupid')) . '&hash='. $teamhash . '#'. $teamhash;

		$team_task_model = $this->getTodoDetail($taskid, true);
		$feedback = $team_task_model?->toArray() ?? [];
        $emails = array();
		if ($feedback) {
			$emails[] = $feedback['creator_email'];

			if($feedback['assignedto'] == Team::TEAM_FEEDBACK_FOR_PROGRAM_LEADS){
                $feedback['firstname'] = $groupCustomName . ' Leader';
                $feedback['lastname'] = '';
                // Feedback targeted towards program sent to program leads
				if($this->val('chapterid')){
					$leads = $group->getChapterLeads($this->val('chapterid'), Group::LEADS_PERMISSION_TYPES['MANAGE']);
				}else{
                    $leads = $group->getGroupLeads(Group::LEADS_PERMISSION_TYPES['MANAGE']);
				}

                foreach($leads as $lead){
                    $emails[] = $lead['email'];
                }
			} else {
                // Feedback targeted towards an individual
                $emails[] = $feedback['email'];
            }

            // Remove empty emails and duplicates
			$emails = array_filter(array_unique($emails));

			foreach($emails as $email){
				$message = "";
				if ($email == $feedback['creator_email']){
					if ($actionStatus == 'created'){
						$subject = "Your feedback for {$feedback['firstname']} {$feedback['lastname']} has been posted";
						$message .= "<p>Dear {$feedback['creator_firstname']},</p>";
						$message .= "<br>";
                        $message .= "<p>Thank you for submitting feedback for your {$teamCustomName} ({$teamName}). Feedback is a gift – and when used correctly, can be a valuable developmental tool. Your comments have successfully been added.</p>";
					} elseif($actionStatus == 'updated') {
						$subject = "Your  feedback for {$feedback['firstname']} {$feedback['lastname']} has been updated";
                        $message .= "<p>Dear {$feedback['creator_firstname']},</p>";
						$message .= "<br>";
                        $message .= "<p>Thank you for submitting feedback for your {$teamCustomName} ({$teamName}). Feedback is a gift – and when used correctly, can be a valuable developmental tool. Your comments have successfully been updated.</p>";
					}
					
				} else {
					if ($actionStatus == 'created'){
						$subject = "You have received feedback from {$feedback['creator_firstname']} {$feedback['creator_lastname']}";
						$message .= "<p>Dear {$feedback['firstname']} {$feedback['lastname']},</p>";
						$message .= "<br>";
						$message .= "<p>You have received feedback from {$feedback['creator_firstname']} {$feedback['creator_lastname']} for your {$teamCustomName} ({$teamName}).</p>";

						$pushTitle ="A new feedback was received on the ".$_COMPANY->getAppCustomization()['teams']['name'];
					} elseif($actionStatus == 'updated'){
						$subject = "You have received updated feedback from {$feedback['creator_firstname']} {$feedback['creator_lastname']}";
						$message .= "<p>Dear {$feedback['firstname']} {$feedback['lastname']},</p>";
						$message .= "<br>";
                        $message .= "<p>You have received updated feedback from {$feedback['creator_firstname']} {$feedback['creator_lastname']} for your {$teamCustomName} ({$teamName}).</p>";

						$pushTitle ="A updated feedback was received on the ".$_COMPANY->getAppCustomization()['teams']['name'];
					}

					// Push notification
					$receiver = User::GetUserByEmail($email);
					if ($receiver){
                        $pushBodytext = $groupName . ' > ' . $teamCustomName . ': ' . $teamName;
						$this->sendTeamModulePushNotifications($receiver->id(),self::PUSH_NOTIFICATIONS_STATUS['TEAM_FEEDBACK'],1,$pushTitle,$pushBodytext);
					}
				}
				$message .= "<br>";
				$message .= "<p>Feedback:</p>";
                $message .= '<div style="background-color:#80808026; padding:10px;">';
				$message .= "<p><strong>{$feedback['tasktitle']}</strong></p>";
				$message .= "<p>{$feedback['description']}</p>";
                $message .= '</div>';
				if ($email != $feedback['creator_email']){
					$message .= "<br>";

                    if ($feedback['assignedto'] == Team::TEAM_FEEDBACK_FOR_PROGRAM_LEADS) {
                        $message .= "<p>To view this feedback, navigate to: <strong>{$group->val('groupname')} {$_COMPANY->getAppCustomization()['group']['name']} > Manage > {$teamCustomName} > Manage {$teamCustomName} and then search for the team by name and view the team feedback</strong></p>";
                    } else {
                        $message .= "<p>To view this feedback navigate to the link below</p>";
                        $message .= EmailHelper::BuildHtmlButton('Go to my ' . $teamCustomName, $teamUrl);
                    }
				} else {
                    $message .= EmailHelper::BuildHtmlButton('Go to my ' . $teamCustomName, $teamUrl);
                }

				$message .= $team_task_model?->renderAttachmentsComponent('v24') ?? '';

				$template = $_COMPANY->getEmailTemplateForNonMemberEmails();
				$emesg	= str_replace('#messagehere#',$message,$template);
				$_COMPANY->emailSend2($group->val('from_email_label'), $email, $subject, $emesg, $_ZONE->val('app_type'), $group->val('replyto_email'));
			}
		}
	}

	public function updateTeamMemberLastActiveDate(){
		global $_USER;
		return self::DBUpdate("UPDATE `team_members` SET `last_active_date`=NOW() WHERE `teamid`='{$this->id()}' AND `userid`='{$_USER->id()}' ");
	}

	public static function SendWeeklyDigestEmailToTeamMember(int $teamid){
		global $_COMPANY,$_ZONE,$_USER,$db;

		$team = self::GetTeam($teamid);
        $group = Group::GetGroup($team->val('groupid'));

		if ($team){
			$subject = "Weekly update for {$team->val('team_name')}";
			$message = "<br>";
			$message .= "<p>Weekly update for ".htmlspecialchars($team->val('team_name')).".</p>";
			$todos = $team->getTeamsTodoList();
			usort($todos, function($a, $b) {
				return $a['duedate'] <=> $b['duedate'];
			});
			if (!empty($todos)){
				$message .= "<p>Upcoming Action Items:</p>";
				foreach($todos as $todo){
					if ($db->covertUTCtoLocalAdvance('Y-m-d',"", $todo['duedate'], 'UTC') >= $db->covertUTCtoLocalAdvance('Y-m-d',"", date('Y-m-d'), 'UTC')){
						$message .= "<li>\"{$todo['tasktitle']}\" to be completed by {$todo['firstname']} {$todo['lastname']} by {$db->covertUTCtoLocalAdvance('M j, Y  T',"", $todo['duedate'], 'UTC')}</li>";
					}
				}
			}
			
			$touchPoints = $team->getTeamsTouchPointsList();
			usort($touchPoints, function($a, $b) {
				return $a['duedate'] <=> $b['duedate'];
			});
			if (!empty($touchPoints)){
				$message .= "<p>Upcoming Touch Points:</p>";
				foreach($touchPoints as $touchPoint){
					if ($db->covertUTCtoLocalAdvance('Y-m-d',"", $touchPoint['duedate'], 'UTC') >= $db->covertUTCtoLocalAdvance('Y-m-d',"", date('Y-m-d'), 'UTC')){
						$message .= "<li>{$touchPoint['tasktitle']} by {$db->covertUTCtoLocalAdvance('M j, Y  T',"", $touchPoint['duedate'], 'UTC')}</li>";
					}
				}
			}
			
			$teamMembers = $team->getTeamMembers(0);
            if(!empty($teamMembers)){
                $message .= "<div><strong>{$_COMPANY->getAppCustomization()['teams']['name']} Members:</strong></div>";
                $message .= $team->getTeamMembersAsHtmlTable($teamMembers);
            }

            $emailIds = array();
            foreach ($teamMembers as $member) {
                if (in_array($member['email'], $emailIds)) {
                    continue;
                }
                $emailIds[] = $member['email'];
                $emesg = "<p>Hi {$member['firstname']},</p>" . $message;
				$template = $_COMPANY->getEmailTemplateForNonMemberEmails();
				$emesg	= str_replace('#messagehere#',$emesg,$template);
				$_COMPANY->emailSend2($group->val('from_email_label'), $member['email'], $subject, $emesg, $_ZONE->val('app_type'), $group->val('replyto_email'));
			}
		}
	}
    /**
     * @param int $groupid
     * @param int $receiver_id
     * @param int $receiver_roleid
     * @param int $sender_roleid
     * @return int|mixed|string|void
     */
    public static function SendRequestToJoinTeam(int $groupid,int $receiver_id,int $receiver_roleid, int $sender_roleid, string $subject,string $message){
        global $_COMPANY,$_USER, $_ZONE;

        $check = self::DBGet("SELECT `team_request_id`, `status` FROM `team_requests` WHERE `companyid`='{$_COMPANY->id()}' AND `groupid`='{$groupid}' AND `senderid`='{$_USER->id()}' AND `sender_role_id`='{$sender_roleid}' AND `receiverid`='{$receiver_id}' AND `receiver_role_id`='{$receiver_roleid}'");

        if(!empty($check)){
            $team_request_id =  $check[0]['team_request_id'];
            $request_status_pending = Team::TEAM_REQUEST_STATUS['PENDING'];
			self::DBUpdate("UPDATE `team_requests` SET `status`={$request_status_pending}, modifiedon=NOW() WHERE `team_request_id`={$team_request_id}");
        } else {
            $team_request_id =  self::DBInsert("INSERT INTO `team_requests`(`companyid`, `groupid`, `senderid`, `sender_role_id`, `receiverid`, `receiver_role_id`) VALUES ('{$_COMPANY->id()}','{$groupid}','{$_USER->id}','{$sender_roleid}','{$receiver_id}','{$receiver_roleid}')");
			
        }

        $receiverUser = User::GetUser($receiver_id);
		if ($team_request_id && $receiverUser){

            if (empty($subject)){
                $subject = "New {$_COMPANY->getAppCustomization()['teams']['name']} join request";
            }

            $group = Group::GetGroup($groupid);
            $groupName = $group->val('groupname') . ' ' . $_COMPANY->getAppCustomization()['group']['name'];

            if (empty($message)){
                $role = Team::GetTeamRoleType($receiver_roleid);
                $baseurl = Url::GetZoneAwareUrlBase($_ZONE->id());
                $teamUrl = $baseurl . 'detail?id=' . $_COMPANY->encodeId($groupid) . '&hash=getMyTeams/getTeamReceivedRequests#getMyTeams/getTeamReceivedRequests';
                $emailTemplate = EmailHelper::InviteUserForTeamByRoleEmailTeamplate($receiverUser, $_USER, $role['type'], $teamUrl, $groupName, Team::GetTeamCustomMetaName($group->getTeamProgramType()));
                $subject = $emailTemplate['subject'];
                $emesg = $emailTemplate['message'];
            } else {
                $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
                $emesg = str_replace('#messagehere#', $message, $template);
            }

            $_COMPANY->emailSend2($group->val('from_email_label'), $receiverUser->val('email'), $subject, $emesg, $_ZONE->val('app_type'), '');

			// Push Notification Create team invted
            if ($receiverUser->val('notification') == 1 && $_COMPANY->getAppCustomization()['mobileapp']['enabled']) {
                $users = self::DBGet("SELECT api_session_id, `userid`, `devicetype`, `devicetoken` FROM users_api_session WHERE `userid`='" . $receiverUser->id() . "' and devicetoken!=''");
                if (count($users) > 0) {
                    $badge = 1;
					[$bearerToken, $firebaseProjectId] = $_COMPANY->getFirebaseBearerTokenAndProjectId();
                    for ($d = 0; $d < count($users); $d++) {
                    	sendCommonPushNotification($users[$d]['devicetoken'], sprintf('New %s invite received',Team::GetTeamCustomMetaName($group->getTeamProgramType())), $subject, $badge, self::PUSH_NOTIFICATIONS_STATUS['TEAM_INVITE'], $groupid, 1, $_ZONE->id(),$bearerToken,'v1',$firebaseProjectId);
                    }
                }
            }
            return true;
		}
		return $team_request_id;
    }

    /**
     * @param int $groupid
     * @return array|void
     */
    public static function GetTeamSentRequests( int $groupid){
        global $_COMPANY,$_USER;
        return self::DBGet("SELECT a.*,b.firstname,b.lastname,b.email,b.picture,b.jobtitle,departments.department,companybranches.branchname,team_role_type.type,team_role_type.role_request_buffer FROM `team_requests` a JOIN users b ON a.receiverid=b.userid LEFT JOIN departments ON b.department= departments.departmentid AND a.receiverid=b.userid LEFT JOIN companybranches ON companybranches.branchid=b.homeoffice AND a.receiverid=b.userid LEFT JOIN team_role_type ON team_role_type.roleid=a.`receiver_role_id` WHERE a.`companyid`='{$_COMPANY->id()}' AND a.`groupid`='{$groupid}' AND a.`senderid`='{$_USER->id()}' ");

    }

    /**
     * @param int $groupid
     * @return array|void
     */
    public static function GetAllTeamRequestsReceivedByUser(int $groupid, int $receiver_userid)
    {
        global $_COMPANY;
        return self::DBROGet("SELECT tr.*,u.firstname,u.lastname,u.email,u.picture,u.jobtitle,departments.department,companybranches.branchname,team_role_type.type,team_role_type.role_request_buffer FROM `team_requests` tr JOIN users u ON tr.senderid=u.userid LEFT JOIN departments ON u.department= departments.departmentid LEFT JOIN companybranches ON companybranches.branchid=u.homeoffice AND tr.receiverid=u.userid JOIN team_role_type ON team_role_type.roleid=tr.`sender_role_id` WHERE tr.`companyid`='{$_COMPANY->id()}' AND tr.`groupid`='{$groupid}' AND tr.`receiverid`='{$receiver_userid}' ");
    }

    /**
     * @param int $groupid
     * @param int $request_id
     * @return int
     */
    public static function DeleteTeamRequest(int $groupid, int $request_id): int
    {
        global $_COMPANY,$_USER;
        return self::DBMutate("DELETE FROM `team_requests` WHERE `companyid`={$_COMPANY->id} AND `groupid`={$groupid} AND `team_request_id`={$request_id}");
    }

    /**
     * @param int $groupid
     * @param int $request_id
     * @param int $status
     * @return int
     */
    public static function AcceptOrRejectTeamRequest(int $groupid,int $request_id,int $status, string $rejection_reason=''){
        global $_COMPANY,$_USER;
        $status = ($status == Team::TEAM_REQUEST_STATUS['ACCEPTED']) ? Team::TEAM_REQUEST_STATUS['ACCEPTED'] : Team::TEAM_REQUEST_STATUS['REJECTED'];
        return self::DBMutatePS("UPDATE `team_requests` SET `modifiedon`=NOW(),`status`=?,`rejection_reason`=? WHERE `companyid`=? AND `groupid`=? AND `receiverid`=? AND `team_request_id`=?",'ixiiii',$status, $rejection_reason, $_COMPANY->id(),$groupid,$_USER->id(),$request_id);
    }

	public function getTeamMembersBasedOnSysRoleid (int $sysRoleId): array
    {
		global $_COMPANY,$_ZONE;
        return  self::DBGet("SELECT team_members.*, users.firstname,users.lastname,users.email,users.picture FROM team_members LEFT JOIN users ON users.userid=team_members.userid AND users.companyid={$_COMPANY->id()} LEFT JOIN team_role_type USING (roleid) WHERE team_members.teamid={$this->id()} AND team_role_type.sys_team_role_type={$sysRoleId}");
	}

    /**
     * Returns active survey response
     * @param int $groupid
     * @param int $userid
     * @param int $roleid
     * @return mixed|null
     */
	public function getUsersActiveTeamRoleJoinSurveyResponse(int $groupid, int $userid, int $roleid){
		global $_COMPANY;
		$row = null;
		
		$r = self::DBGet("SELECT `roleid`, `companyid`, `groupid`, `userid`, `role_survey_response`, `request_capacity`, `used_capacity`, `isactive` FROM `member_join_requests` WHERE `companyid`='{$_COMPANY->id()}' AND `groupid`='{$groupid}' AND  `userid`='{$userid}' AND `roleid`='{$roleid}' AND `member_join_requests`.`isactive` = 1");

		if (!empty($r)){
			$role_survey_response = json_decode($r[0]['role_survey_response'],true);
			$row = $role_survey_response;
		}
		return $row;
	}

	public static function GetTeamJoinRequestDetail(int $groupid, int $senderId, int $senderRoleId, int $receiverId,int $receiverRoleid){
		global $_COMPANY;
		$check = self::DBGet("SELECT `team_request_id`,`status` FROM `team_requests` WHERE `companyid`='{$_COMPANY->id()}' AND  `groupid`='{$groupid}' AND `senderid`='{$senderId}' AND `sender_role_id`='{$senderRoleId}' AND `receiverid`='{$receiverId}' AND `receiver_role_id`='{$receiverRoleid}' ");
		if (!empty($check)){
			return $check[0];
		}
		return null;
	}

	public function isAllowedNewTeamMemberOnRole(int $roleid){
		global $_COMPANY;
		$allowed = false;
		
		$role = Team::GetTeamRoleType($roleid);
		$memberCount = self::DBGet("SELECT COUNT(1) as memberCount FROM `team_members` WHERE `teamid`='{$this->id()}' AND `roleid`='{$roleid}'");
		
		if (!empty($role) && !empty($memberCount)){
			if ($role['max_allowed'] > $memberCount[0]['memberCount']) {
				$allowed = true;
			}
		}
		return $allowed;
	}

	public static function GetUserTeamsList(int $groupid, int $userid, bool $activeOnly = true){
		global $_COMPANY,$_USER, $_ZONE;

		$activeCondition = "";
		if ($activeOnly){
			$activeCondition = " AND teams.isactive='1' ";
		}

		return self::DBROGet("SELECT teams.teamid,teams.team_name,teams.isactive,team_members.userid,team_members.roleid,team_role_type.type, team_role_type.sys_team_role_type,teams.isactive
                                   FROM teams 
                                       JOIN team_members ON teams.teamid = team_members.teamid 
                                       LEFT JOIN team_role_type ON team_members.roleid = team_role_type.roleid 
                                   WHERE teams.companyid='{$_COMPANY->id()}' 
                                     AND teams.zoneid='{$_ZONE->id()}' 
                                     AND teams.groupid='{$groupid}' 
                                     AND team_members.userid='{$userid}' 
                                     $activeCondition
                                     ");

	}

	public static function CheckRequestRoleJoinAllowed(int $subject_userid, array $rules){
		global $_COMPANY;
		$allowed = true;
		foreach($rules as $key=> $val){
			$matchWithkeys = $val['keys'];
			$type = $val['type'];
			if ($type  == '0' || empty($matchWithkeys)){ // Ignore
				continue;
			}
			$userCategoryKey = UserCatalog::GetCatalogKeynameForUser($key, $subject_userid);
			if ($type == 1){ // IN
				if (!in_array($userCategoryKey,$matchWithkeys)){
					$allowed = false;
					break;
				}
			} elseif($type == 2){ // Not IN
				if (in_array($userCategoryKey,$matchWithkeys)){
					$allowed = false;
					break;
				}
			}
		}
		return $allowed;
	}

	public static function CalculateFinalMatchesAndMatchingPercentage(array $allMatches){
		if (!empty($allMatches)){
            $matchesWithPercentage = array();
            foreach ($allMatches as $match) {
                foreach ($match as $k => $v) {
					$percentage = floatval(isset($matchesWithPercentage[$k]) ? $matchesWithPercentage[$k] + $v : $v);
                    $matchesWithPercentage[$k] = $percentage > 100 ? 100 : $percentage;
                }
            }
			$matchesWithPercentage = array_map('ceil',$matchesWithPercentage);
			arsort($matchesWithPercentage);// Order by matching percentage DESC
			return $matchesWithPercentage;
		} 
		return array();
	}

    /**
     * Returns an array of users who are matching
     * @param int $teamid
     * @param array $matchedusers
     * @return array
     */
	public static function GetSuggestedUsers(int $teamid,array $matchedusers) {
		global $_COMPANY;
        $suggestions = array();
        $existingTeamMembers = array();

        if ($teamid) {
            $existingTeamMembers = self::DBROGet("SELECT userid FROM team_members WHERE teamid='{$teamid}'");
            if (!empty($existingTeamMembers)) {
                $existingTeamMembers = array_column($existingTeamMembers, 'userid');
            }
        }

        foreach ($matchedusers as $matcheduser) {

            if (empty($matcheduser))
                continue;

            $matcheduser = intval($matcheduser);

            if (in_array($matcheduser, $existingTeamMembers))
                continue; // Skip users who are already members of the team.

            $rows = self::DBROGet("SELECT users.`userid`,users.`firstname`,users.`lastname`,users.`email`,users.`regionid`,IF (users.jobtitle='','Job title unavailable',users.jobtitle) as jobtitle, users.picture,departments.department FROM `users` LEFT JOIN departments ON users.department= departments.departmentid WHERE users.`companyid`='{$_COMPANY->id()}' AND (users.userid={$matcheduser} AND users.`isactive`='1')");
            if (!empty($rows))
                $suggestions[] = $rows[0];

        }
		return $suggestions;
	}

	public static function CalculateParameterWisePercentage(int $userid, array $allMatches, array $customAttributes,float $matchingParameterPercentage, array $seekerSurveyResponses, array $matchedUsersSurveyResponses){
		// Calculate Parameter wise percentage
		$parameterWisePercentage = array();
	
		foreach($allMatches as $key => $userMatchData){
			$value = '';
			$attributeType = '';
			$keyTitle = $key;
			$index = array_search($key, array_column($customAttributes, 'name'));
            $allValuesOfMatchedUserArr = array();
			if ($index !== false ){
				$q = $customAttributes[$index];
				
				if (array_key_exists('title',$q)){
					$options = Survey2::GetSurveyQuestionOptionValues($q);
					if ($q['type'] == 'checkbox') {
						$seekerResponses = $seekerSurveyResponses[$key] ?? array();
						$matchResponses = $matchedUsersSurveyResponses[$userid][$key] ?? array();
						$commonResponsees = array_intersect($seekerResponses,$matchResponses);
						$valuesArray = array();
						foreach($commonResponsees as $keyValue) {
                            if (key_exists($keyValue, $options)) {
                                $valuesArray[] = $options[$keyValue];
                            }
						}
						$value = implode(', ', $valuesArray);
						foreach($matchResponses as $mrKeyValue) {
                            if (key_exists($mrKeyValue, $options)) {
                                $allValuesOfMatchedUserArr[] = ['value' => $options[$mrKeyValue], 'is_matched' => in_array($options[$mrKeyValue], $valuesArray)];
                            }
						}

                    } elseif ($q['type'] == 'radiogroup' || $q['type'] == 'dropdown') {
                        $value =
                            (isset($matchedUsersSurveyResponses[$userid][$key]) && !is_array($matchedUsersSurveyResponses[$userid][$key]))
                            ? ($options[$matchedUsersSurveyResponses[$userid][$key]] ?? '')
                            : '';
                    } elseif ($q['type'] == 'text') {
						if ((isset($matchedUsersSurveyResponses[$userid][$key]) && is_array($matchedUsersSurveyResponses[$userid][$key]))){
                            //This is a known issue caused by bad data generated by the Survey.js dependency.
                            // The issue occurs when the array data type of question is deleted after the user's
                            // response, and a question of type 'text' (string type) is added.
                            // This error only happens in this specific scenario.
							Logger::Log("Invalid survey data for text question type, string response is expected but array was given");
						}
                        $value= htmlspecialchars($matchedUsersSurveyResponses[$userid][$key] ?? '');
                    } else {
						$value = implode(', ', array_values($options));
					}
					$attributeType = 'custom_parameters';
					$keyTitle = $q['title'];
                    if (is_array($keyTitle)){
                        $keyTitle = $keyTitle['default'];
                    }
				}
			}  else {
				$value  = UserCatalog::GetCatalogKeynameForUser($keyTitle,$userid);
				$attributeType = 'primary_parameters';
			}
			if (!empty($userMatchData)){
				$percentageGot = 0;
				if (array_key_exists($userid,$userMatchData)){
					$percentageGot = $userMatchData[$userid];
				}
				$finalPercentage = round(($percentageGot * 100 / $matchingParameterPercentage),2);
				$parameterWisePercentage[$key] = array('title'=>$keyTitle,'value'=>$value,'allValuesOfMatchedUserArr' => $allValuesOfMatchedUserArr, 'attributeType'=>$attributeType,'percentage'=>$finalPercentage);

			} else {
				$parameterWisePercentage[$key] = array('title'=>$keyTitle,'value'=>$value,'allValuesOfMatchedUserArr' => $allValuesOfMatchedUserArr, 'attributeType'=>$attributeType,'percentage'=>0);
			}
		}
		return $parameterWisePercentage;
	}

    /**
     * This function runs matching algorithm against all $usersByRoleRequestWithSurveyResponses. Note it does not check
     * if the user has capacity available, etc. so the list provided as $usersByRoleRequestWithSurveyResponses should be
     * only of users who have capacity.
     * @param int $groupid
     * @param int $teamid
     * @param int $roleid
     * @param array $primaryParameters
     * @param array $customParameters
     * @param int $matchAgainstUserId
     * @param array $matchAgainstSurveyResponses
     * @param array $usersByRoleRequestWithSurveyResponses
     * @param array $customAttributes
     * @param array $mandatory
     * @return array
     */
	public static function RunMatchingAlgorithmForSuggestions(int $groupid, int $teamid,int $roleid, array $primaryParameters, array $customParameters, int $matchAgainstUserId, array $matchAgainstSurveyResponses, array $usersByRoleRequestWithSurveyResponses,array $customAttributes,array $mandatory, int $page=1){
		global $_COMPANY,$_USER;
		$group  = Group::GetGroup($groupid);
		$allMatches 	= array();
		$teamRoleType = self::GetTeamRoleType($roleid);
		$sys_team_role_type = $teamRoleType['sys_team_role_type'];
        $restrict_search_to_userids = array_keys($usersByRoleRequestWithSurveyResponses);
		$matchingParameterPercentage = self::GetMatchingParameterAveragePercentage($primaryParameters,$customParameters);
		// Get matches by custom parameters (Matching Survey responses)
		foreach($customParameters as $key => $value){
			if ($value >0) {
				// Check if 'matching_adjustment' is set then do matching adjustment on usersByRoleRequestWithSurveyResponses for 'rating' type of question
				if (isset($mandatory[$key]) && isset($mandatory[$key]['matching_adjustment'])) {
					$matching_adjustment = $mandatory[$key]['matching_adjustment'];
					if (!empty($matching_adjustment)){
						$question = $group->getTeamCustomAttributesQuestion($key);
						if ($question && $question['type'] == 'rating'){
							// apply matching adjustment
							$matching_min_adjustment  = $matching_adjustment[0];
							$matching_max_adjustment  = $matching_adjustment[1];
							foreach ($usersByRoleRequestWithSurveyResponses as $usrKey => &$itemAry) {
								if (isset($itemAry[$question['name']])) {
                                    // Range match is handled as a special case, instead of depending upon cataglo to
                                    // handle reverse conditions, we are pre-setting the values to desired values.
									 if ($sys_team_role_type == '3'){ // 3 is for Mentee, mentor is looking for mentees
                                         // When finding Mentees for a given mentor, SUBTRACT RANGE from mentor value to get mentee range
									 	$newValue = [($itemAry[$question['name']] - ($matching_min_adjustment)), ($itemAry[$question['name']] - ($matching_max_adjustment))];
									 } else {
                                         // When finding Mentors for a given mentee, ADD RANGE to mentee value to get mentor range
										$newValue = [($itemAry[$question['name']] + ($matching_min_adjustment)), ($itemAry[$question['name']] + ($matching_max_adjustment))];;
									}
									$itemAry[$question['name']] = $newValue;
								}
							}
							// Unset reference to avoid potential bugs
							unset($itemAry);
						}
					}
				}
				$userCatalogKeyName = UserCatalog::GetSurveyResponseCatalog($key, $value,$matchAgainstSurveyResponses, $usersByRoleRequestWithSurveyResponses, ($sys_team_role_type == '3' ? true : false), $matchingParameterPercentage, false);
				$allMatches[$key] = $userCatalogKeyName->getUserIds();
			}
        }
		// Get matches by Primary parameters (User Catalog)
		foreach($primaryParameters as $key => $value){
			
            if ($value > 0){
				$operator = self::GetMatchingOperator($key, $value, $sys_team_role_type);
				$userCatalogKeyName = UserCatalog::GetCatalogKeynameForUser($key,$matchAgainstUserId);
				// Get CatalogType int|string
				$catalogType = UserCatalog::GetCategoryKeyType($key);
				if ($catalogType == 'int'){ // If 'int', check if 'matching_adjustment' is set
					if (isset($mandatory[$key]) && isset($mandatory[$key]['matching_adjustment'])) {
						$matching_adjustment = $mandatory[$key]['matching_adjustment'];
                        if (!empty($matching_adjustment)) {
                            // Adjust the catalog value by the matching_adjustment value (increment or decrement)
							$matching_min_adjustment  = $matching_adjustment[0];
							$matching_max_adjustment  = $matching_adjustment[1];
                            if ($sys_team_role_type == '3') { // 3 is for Mentee, mentor is looking for mentees
                                // GetUserCatalog function expects string value for $userCatalogKeyName, so convert array to string
                                // When finding Mentees for a given mentor, SUBTRACT RANGE from mentor value to get mentee range
                                $userCatalogKeyName = json_encode([($userCatalogKeyName - ($matching_min_adjustment)),($userCatalogKeyName - ($matching_max_adjustment))]);
                            } else {
                                // GetUserCatalog function expects string value for $userCatalogKeyName, so convert array to string
                                // When finding Mentors for a given mentee, ADD RANGE to mentee value to get mentor range
                                $userCatalogKeyName = json_encode([($userCatalogKeyName + ($matching_min_adjustment)),($userCatalogKeyName + ($matching_max_adjustment))]);
                            }
                        }
					}
				}
				if ($userCatalogKeyName){ 
					$matchCatalogKeyName = UserCatalog::GetUserCatalog($key, $userCatalogKeyName, $operator,$restrict_search_to_userids);
					$matchedIds = $matchCatalogKeyName->getUserIds();
					// Map all id with matched percentage share for calculate matching percentage
					$allMatches[$key] = array_fill_keys($matchedIds, $matchingParameterPercentage);
				}
            }
        }
		if (empty($allMatches)) {
			return array([],false);
		}
		
		// Get Final matches with matching percentage
		return  self::FinalizeSuggestions($teamid, $matchingParameterPercentage, $allMatches, $customAttributes, $mandatory, $matchAgainstSurveyResponses,$usersByRoleRequestWithSurveyResponses, $page);

	}

	public static function FinalizeSuggestions(int $teamid,float $matchingParameterPercentage, array $allMatches, array $customAttributes, array $mandatory,array $seekerSurveyResponses, array $matchedUsersSurveyResponses, int $page=1) {

		// Get Final matches with matching percentage
		$finalMatches = self::CalculateFinalMatchesAndMatchingPercentage($allMatches);
		$suggestions = array();
		$finalSuggestions = array();
		$loadMoreData = false;
		$totalSuggestionsCount = 0;
		if (!empty($finalMatches)){
			$start = (($page - 1) * MAX_TEAMS_ROLE_MATCHING_RESULTS);
			if (count($finalMatches) > $page * MAX_TEAMS_ROLE_MATCHING_RESULTS) {
				$loadMoreData = true;
			}
			$totalSuggestionsCount = count($finalMatches);
			$finalMatches = array_slice($finalMatches,$start,MAX_TEAMS_ROLE_MATCHING_RESULTS,true);
		
			$matchedUsers = array_keys($finalMatches);
			$suggestions = self::GetSuggestedUsers($teamid,$matchedUsers);
		
			// Map matching percentage 
			for($i=0;$i<count($suggestions);$i++){
				$parameterWiseMatchingPercentage = self::CalculateParameterWisePercentage($suggestions[$i]['userid'],$allMatches,$customAttributes,$matchingParameterPercentage, $seekerSurveyResponses, $matchedUsersSurveyResponses);
				
				$validated = true;
				if (!empty($mandatory)){
					$validated = self::ValidateMandatoryAttributes($mandatory,$parameterWiseMatchingPercentage);
				}
				if ($validated /* && self::CanJoinARoleInTeam($groupid,$suggestions[$i]['userid'],$roleid) #users already validated by the time we reach here */){
					$suggestions[$i]['parameterWiseMatchingPercentage']  = $parameterWiseMatchingPercentage;
					$suggestions[$i]['matchingPercentage'] = $finalMatches[$suggestions[$i]['userid']];
					$finalSuggestions[]	= $suggestions[$i];
				}	
			}
		}	
		return [$finalSuggestions,$loadMoreData, $totalSuggestionsCount];
	}



	public static function GetMatchingOperator(string $key, int $value, int $sys_team_role_type){
		if (UserCatalog::GetCategoryKeyType($key) === 'string'){
			// if ($sys_team_role_type == '3'){ //Matching mentor against mentee
			// 	$operator   = UserCatalog::STRING_TYPE_OPERATORS_REVERSE[$value];
			// } else {
			// 	$operator   = UserCatalog::STRING_TYPE_OPERATORS[$value];
			// }

			$operator   = UserCatalog::STRING_TYPE_OPERATORS[$value];
		
		} else {
			 if ($sys_team_role_type == '3'){ //Matching mentor against mentee
			 	$operator   = UserCatalog::INT_TYPE_OPERATORS_REVERSE[$value];
			 } else {
			 	$operator   = UserCatalog::INT_TYPE_OPERATORS[$value];
			 }
			//$operator   = UserCatalog::INT_TYPE_OPERATORS[$value];
		}

		return $operator;

	}

	public static function GetMatchingParameterAveragePercentage($primaryParameters,$customParameters)
    {

		$totalValidMatchingParametersCount = 0; 
		$matchingParameterPercentage = 100.00;
		foreach($customParameters as $key => $value){
			if ($value > 0){
				$totalValidMatchingParametersCount ++;
			}
		}
		
		foreach($primaryParameters as $key => $value){
			if ($value > 0){
				$totalValidMatchingParametersCount ++;
			}
		}
	
		if ($totalValidMatchingParametersCount){
			$matchingParameterPercentage = round((100/$totalValidMatchingParametersCount),2);
		}
		return $matchingParameterPercentage;
	}

	public static function ValidateMandatoryAttributes($mandatory,$parameterWiseMatchingPercentage){

		$validatation = 1;
		foreach($mandatory as $key => $value){
			if ($value['is_required'] == 1){
				$percentage =$parameterWiseMatchingPercentage[$key]['percentage'];
				if ($percentage < 100){
					$validatation = 0;
					break;
				}
			} 
		}
		return $validatation;
	}

	public static function CanJoinATeamWithOtherUser(int $groupid, int $subjectuserid,int $subjectRoleid, int $otherUserid) : int
    {
        global $_COMPANY;
		$programObj = Group::GetGroup($groupid);
		if ($programObj->getTeamProgramType() == self::TEAM_PROGRAM_TYPE['NETWORKING']){
			$teamids = self::DBROGet("SELECT group_concat(teamid) as team_ids FROM teams JOIN team_members USING (teamid) WHERE companyid={$_COMPANY->id()} AND groupid={$groupid} AND team_members.userid={$subjectuserid} AND roleid={$subjectRoleid}")[0]['team_ids'];

			if (!empty($teamids)) {
				$t =  self::DBROGet("SELECT teamid FROM `team_members` WHERE `userid`={$otherUserid} AND teamid IN ({$teamids}) LIMIT 1");
				if (!empty($t)) {
					return false;
				}
			}
		}
		return true;
    }

	public static function CanJoinARoleInTeam(int $groupid, int $userid, int $roleid){
		global $_COMPANY;
        $requestDetail = self::GetRequestDetail($groupid,$roleid,$userid);
        if ($requestDetail) {
            return self::DoesJoinRequestHaveActiveCapacity ($requestDetail);
        }
        return true;
	}

    /**
     * imple utility function to encapsulate the math for request capacity vs used capacity. Use this function instead of
     * CanJoinARoleInTeam if the request details are available.
     * @param array $requestDetail the following array keys are required 'request_capacity', 'used_capacity', 'isactive'
     * @return bool
     */
    public static function DoesJoinRequestHaveActiveCapacity (array $requestDetail) : bool
    {
		if ($requestDetail['request_capacity']>0){
        	return ((intval($requestDetail['request_capacity']) -  intval($requestDetail['used_capacity'])) > 0) && $requestDetail['isactive'] == 1;
		} else { // If Capacity is unlimited then chek onlly isactive
			return $requestDetail['isactive'] == 1;
		}
    }


	public function getAllJoinedTeamsInProgram($userid){
	
		return self::DBGet("SELECT COUNT(1) as totalJoinedTeams FROM `team_members` JOIN `teams` USING(teamid) WHERE `team_members`.`userid`='{$userid}' AND `teams`.`groupid`='{$this->val('groupid')}' AND `teams`.`isactive`!=100")[0]['totalJoinedTeams'];
	}
	
	public static function GetWhyCannotLeaveProgramMembership(int $groupid, int $userid): string
	{
		global $_COMPANY,$_ZONE;

		$totalJoinedTeams =  self::DBGet("SELECT team_members.userid FROM `team_members` JOIN teams ON teams.teamid=team_members.teamid WHERE teams.companyid ='{$_COMPANY->id()}' AND teams.zoneid='{$_ZONE->id()}' AND teams.groupid='{$groupid}' AND team_members.userid='{$userid}'");

		if (!empty($totalJoinedTeams)) {
			return sprintf(
				gettext('You are already assigned to a %s. Please leave the %s before leaving the %s'),
				$_COMPANY->getAppCustomization()['teams']['name'],
				$_COMPANY->getAppCustomization()['teams']['name'],
				$_COMPANY->getAppCustomization()['group']['name'],
			);
		}

        $groupLead = self::DBGet("SELECT `leadid` FROM `groupleads` WHERE `groupid`='{$groupid}' AND `userid`='{$userid}'");

		if (!empty($groupLead) && ($_ZONE->val('app_type') === 'talentpeak')) {
			return sprintf(
				gettext('You are a %s Lead and so cannot leave the %s'),
				$_COMPANY->getAppCustomization()['group']['name'],
				$_COMPANY->getAppCustomization()['group']['name'],
			);
		}

        $totalActiveRequests =  self::DBGet("SELECT member_join_requests.userid FROM `member_join_requests` WHERE `member_join_requests`.`companyid`='{$_COMPANY->id()}' AND member_join_requests.userid='{$userid}' AND member_join_requests.groupid='{$groupid}' ");

        if (!empty($totalActiveRequests)) {
            return sprintf(
                gettext('You have requested to join a %s. Please cancel join requests before leaving the %s'),
                $_COMPANY->getAppCustomization()['teams']['name'],
                $_COMPANY->getAppCustomization()['group']['name'],
            );
        }

		return '';
	}

	public  function getTeamMemberById(int $team_memberid)
    {
		global $_COMPANY,$_ZONE,$_USER;
		$row = null;
		$r = self::DBGet("SELECT team_members.*,IFNULL(team_role_type.`type`,'Other') as `role`, team_role_type.sys_team_role_type, users.firstname,users.lastname,users.email,users.picture,IF (users.jobtitle='','Job title unavailable',users.jobtitle) as jobtitle  FROM `team_members` JOIN users ON users.userid=team_members.userid JOIN team_role_type ON team_role_type.roleid= team_members.roleid WHERE  users.`companyid`='{$_COMPANY->id()}' AND team_members.teamid='{$this->id()}' AND team_members.`team_memberid`='{$team_memberid}'");
		if (!empty($r)){
			$row = $r[0];
		}
		return $row;

	}

    public static function SendTeamRequestUpdateNotification(int $groupid, ?User $receiver, int $roleid, string $action, ?string $overrideSubject = null, ?string $overrideMessage= null)
    {
		global $_COMPANY,$_ZONE,$db;

		if (empty($receiver) ||
            !in_array($action,array('create','update','cancel','decline'))
        ){
			return false;
		}

        $roleRow = self::GetTeamRoleType($roleid);
        if (!($roleRow) && $roleid > 0){
            return false;
        }

        $role = $roleRow ? $roleRow['type'] : $_COMPANY->getAppCustomization()['group']["name-short"].' Member'; //TRG join request case

		$name = $receiver->getFullName();
		$email = $receiver->val('email');
		$message  ="";
		$group = Group::GetGroup($groupid);

		if ($action == 'create'){
			$subject = "Your request to join {$group->val('groupname')} for the {$role} role has been received";
			$message .= "<p>Your request to join {$group->val('groupname')} for the {$role} role has been received. We will update you once your request is processed.</p>";
		} elseif ($action == 'update'){
            return true; // Update emails disabled as they do not make sense.
			//$subject = "Your request to join {$group->val('groupname')} for the {$role} role has been updated";
			//$message .= "<p>Your request to join {$group->val('groupname')} for the {$role} role has been updated.  We will update you once your request is processed.</p>";
        } elseif ($action == 'cancel'){
            $subject = "Your request to join {$group->val('groupname')} for the {$role} role has been cancelled";
            $message .= "<p>Your request to join {$group->val('groupname')} for the {$role} role has been cancelled.</p>";
        } elseif ($action == 'decline'){
            $subject = "Cancelled: Join request to {$group->val('groupname')} ({$role} role)";
            $message = "<p>Your request to join {$group->val('groupname')} for the {$role} role has been cancelled by program administrator.";
		} else {
			return false;
		}

		// Adding our override here if exists
		$subject = !empty($overrideSubject) ? $overrideSubject : $subject;
		$message = !empty($overrideMessage) ? $overrideMessage : $message;

		$emesg = "<p>Hi {$name},</p>";
		$emesg .= "<br/>";
		$emesg .= $message;
        $emesg .= "<br/>";
        $emesg .= "<p>- {$group->val('groupname')} {$_COMPANY->getAppCustomization()['group']['name']} Team</p>";

        // Check if JoinRequest email has been configured, if so use the subject and body from there.
        if ($action == 'create' && !empty($roleRow['joinrequest_email_subject'])) {
            $subject =  !empty($roleRow['joinrequest_email_subject']) ? $roleRow['joinrequest_email_subject'] : $subject;
        }
        if ($action == 'create' && !empty($roleRow['joinrequest_message'])) {
			$manager_user = $receiver->getUserHeirarcyManager();

            $emesg = str_replace(
                [
                    '[[PERSON_NAME]]',
                    '[[PERSON_FIRST_NAME]]',
                    '[[PERSON_LAST_NAME]]',
                    '[[PERSON_JOB_TITLE]]',
                    '[[PERSON_START_DATE]]',
					'[[MANAGER_FIRST_NAME]]',
					'[[MANAGER_LAST_NAME]]',
					'[[MANAGER_EMAIL]]',
                ],
                [
                    $name,
                    $receiver->val('firstname'),
                    $receiver->val('lastname'),
                    $receiver->val('jobtitle'),
                    $receiver->getStartDate(),
					$manager_user?->val('firstname') ?? '',
					$manager_user?->val('lastname') ?? '',
					$manager_user?->getEmailForDisplay() ?? '',
                ],
                    $roleRow['joinrequest_message']
                );
        }

		$template = $_COMPANY->getEmailTemplateForNonMemberEmails();
		$emesg = str_replace('#messagehere#', $emesg, $template);
		return $_COMPANY->emailSend2($group->val('from_email_label'), $email, $subject, $emesg, $_ZONE->val('app_type'), $group->val('replyto_email'));

	}

	public static function GetUniqueProgramMembersByRoleIds (int $groupid, string $roleids): array
    {
		global $_COMPANY,$_ZONE;
        $roleids = implode(',',array_map('intval', explode(',', $roleids))); // Clean roleids string by converting each value to int.
		$team_members = self::DBGet("SELECT  users.firstname,users.lastname,users.email,users.picture FROM `team_members` JOIN users ON users.userid=team_members.userid JOIN teams ON teams.teamid= team_members.teamid JOIN team_role_type ON team_role_type.roleid= team_members.roleid WHERE teams.companyid='{$_COMPANY->id()}' AND teams.zoneid='{$_ZONE->id}' AND teams.groupid='{$groupid}' AND team_role_type.roleid IN({$roleids}) AND teams.`isactive`=1 ");

		return array_values(array_unique($team_members, SORT_REGULAR));

	}

	public static function GetTeamRoleTypesByIds(int $groupid,string $roleids = '')
    {
        global $_COMPANY,$_ZONE;
        $roleidCondition = "";
		if ($roleids){
            $roleids = implode(',',array_map('intval', explode(',', $roleids))); // Clean roleids string by converting each value to int.
			$roleidCondition = " AND  `roleid` IN ({$roleids})";
		}
		return self::DBGet("SELECT * FROM `team_role_type` WHERE  `team_role_type`.`companyid`='{$_COMPANY->id()}' AND `team_role_type`.`zoneid`='{$_ZONE->id()}' AND team_role_type.groupid='{$groupid}' {$roleidCondition}");
	}


	public function isTeamMember(int $userid, int $matchRoleid = 0){
		$isMember = false;
		$check = self::DBGet("SELECT `roleid` FROM `team_members` WHERE `teamid`='{$this->id()}' AND `userid`='{$userid}'");
		if(!empty($check)){
			if ($matchRoleid) {
				$isMember = ($check[0]['roleid'] == $matchRoleid);
			} else {
				$isMember = true;
			}
		}
		return $isMember;
	}

	public function linkTouchpointEventId(int $touchpointid, int $eventid, string $duedate_utc){
		global $_COMPANY,$_ZONE, $_USER;
        // See GET_TALENTPEAK_TODO_STATUS for status

        $modified_by = $_USER ?-> id() ?? 0;

        $duedate_timestamp = strtotime($duedate_utc . ' UTC');
        $status = ($duedate_timestamp > time()) ? 51 : 52;
        $duedate = gmdate('Y-m-d H:i:s', $duedate_timestamp);
        $duedate_update_str = $duedate ? ", duedate='{$duedate}'" : '';

        return self::DBUpdate("
            UPDATE `team_tasks`
            SET `eventid`={$eventid}, isactive={$status} {$duedate_update_str}, modifiedon=NOW(), modifiedby={$modified_by}
            WHERE `companyid`={$_COMPANY->id()}
              AND `zoneid`={$_ZONE->id()} 
              AND `taskid`={$touchpointid} 
              AND `teamid`={$this->id()}
              ");
	}

	public function unlinkEventFromTouchpoint(int $eventid){
		global $_COMPANY,$_ZONE,$_USER;
        $modified_by = $_USER ?-> id() ?? 0;
        return self::DBUpdate("UPDATE `team_tasks` SET `eventid`=0,`modifiedon`=NOW(),`modifiedby`={$modified_by} WHERE companyid='{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' AND `teamid`='{$this->id()}' AND eventid='{$eventid}'");
	}

	public function sendUpcomingEventsEmailInvitations(int $inviteUserId){
		global $_COMPANY,$_ZONE;
		$rows = self::DBGet("SELECT * FROM events WHERE `companyid` = '{$_COMPANY->id()}' AND `groupid`='{$this->val('groupid')}' AND `teamid`='{$this->id()}' AND  `isactive`='".self::STATUS_ACTIVE."' AND `end` > now() + interval 5 minute  AND `event_series_id` = 0 AND `isprivate` =0  AND `eventclass`='teamevent' ");
		$invitedBy = 'You are invited to join <b>'. $this->val('team_name').' '.$_COMPANY->getAppCustomization()['teams']['name']. '</b> events';
        foreach($rows as $row) {
            $eventJob = new EventJob($row['groupid'], $row['eventid']);
            $eventJob->saveAsInviteType($inviteUserId, $invitedBy);
        }
		return true;
	}

	public static function GetTeamIdsToBulkUpdateByAction(int $groupid, int $action){
		global $_COMPANY,$_ZONE;
		if (in_array($action, array (self::STATUS_DRAFT, self::STATUS_INACTIVE, self::STATUS_ACTIVE, self::STATUS_COMPLETE, self::STATUS_INCOMPLETE, self::STATUS_PAUSED))){
			$teams = self::DBGet("SELECT `team_name`,`teamid`,`groupid`,`chapterid` FROM `teams` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `groupid`='{$groupid}' AND isactive='{$action}'");
            return $teams;
		}
		return array();
	}

	public function activate(bool $validateRequiredMember = true){
		global $_COMPANY,$_ZONE;

		if (empty($this->getTeamMembers(0))){
			return array('status'=>0,'error'=>sprintf(gettext('This %s cannot be activated because it has no members yet'),$_COMPANY->getAppCustomization()['teams']['name']));
		}
		$action = self::STATUS_ACTIVE;
		$group = Group::GetGroup($this->val('groupid'));

		if ($validateRequiredMember && !in_array($group->getTeamProgramType(),array(Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']))){
			$validationJson = $this->validateRequiredTeamMember();
			if (!$validationJson['status']){
				return $validationJson;
			}
		}

        $start_complete_date = ', team_complete_date = null';
        if ($this->val('isactive') == self::STATUS_DRAFT) { // Set start date if this is the first time the team is getting activated
            $start_complete_date .= ', team_start_date = now()';
        }

		if (self::DBUpdate("UPDATE `teams` SET `isactive`='{$action}',`modifiedon`=NOW() {$start_complete_date} WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `teamid`='{$this->id()}'")){
			if ($this->val('isactive') == self::STATUS_DRAFT){
				$this->createActionItemsAndTouchpointsFromTemplates();
                $this->sendTeamStatusChangeEmailToTeamMembers($action);
			}

			$this->postUpdate();

			return array('status'=>1,'error'=>'');
		}
		return array('status'=>0,'error'=>gettext('Something went wrong. Please try again.'));
	}

	public function complete(){
		global $_COMPANY,$_ZONE;
		$action = self::STATUS_COMPLETE;
        $update_status = self::DBUpdate("UPDATE `teams` SET `isactive`='{$action}',`modifiedon`=NOW(), team_complete_date=now() WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `teamid`='{$this->id()}' AND isactive != '{$action}'");

        if ($update_status) { // Check for update status to allow the following actions only on the first update
            // Reset role used capacity
            $this->resetRoleUsedCapacity();
            // Send notification
            $this->sendTeamStatusChangeEmailToTeamMembers($action);

            $this->postUpdate();
        }

		return array('status'=>1,'error'=>'');
	}

	public function incomplete(){
		global $_COMPANY,$_ZONE;
		$action = self::STATUS_INCOMPLETE;
		$update_status = self::DBUpdate("UPDATE `teams` SET `isactive`='{$action}',`modifiedon`=NOW(), team_complete_date=now() WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `teamid`='{$this->id()}' AND isactive != '{$action}'");

        if ($update_status) { // Check for update status to allow the following actions only on the first update
            // Reset role used capacity
            $this->resetRoleUsedCapacity();
            // Send notification
            $this->sendTeamStatusChangeEmailToTeamMembers($action);

            $this->postUpdate();
        }

		return array('status'=>1,'error'=>'');
	}

	public function paused(){
		global $_COMPANY,$_ZONE;
		$action = self::STATUS_PAUSED;
        $update_status =  self::DBUpdate("UPDATE `teams` SET `isactive`='{$action}',`modifiedon`=NOW() WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `teamid`='{$this->id()}' AND isactive != '{$action}'");

        if ($update_status) { // Check for update status to allow the following actions only on the first update
            // Reset role used capacity
            $this->resetRoleUsedCapacity();
            // Send notification
            $this->sendTeamStatusChangeEmailToTeamMembers($action);

            $this->postUpdate();
        }

		return array('status'=>1,'error'=>'');
	}

	public function deactivate(){
		global $_COMPANY,$_ZONE;
		$action = self::STATUS_INACTIVE;
        $update_status = self::DBUpdate("UPDATE `teams` SET `isactive`='{$action}',`modifiedon`=NOW() WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `teamid`='{$this->id()}' AND isactive != '{$action}'");

        if ($update_status) { // Check for update status to allow the following actions only on the first update
            $this->postUpdate();
        }

		return array('status'=>1,'error'=>'');
	}

	public function validateRequiredTeamMember(){
		global $_COMPANY,$_ZONE;

		//$group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']

		$minRequiredRole = array();
		$maxRequiredRole = array();
		$allRoles = self::GetProgramTeamRoles($this->val('groupid'), 1);
		foreach ($allRoles as $role) {

			$memberCount = self::DBGet("SELECT COUNT(1) as memberCount FROM `team_members` WHERE `teamid`='{$this->id()}' AND `roleid`='{$role['roleid']}'");

			if ($memberCount[0]['memberCount'] < $role['min_required']) {
				$minRequiredRole[] = $role['type'];
			}

			if ($memberCount[0]['memberCount'] > $role['max_allowed']) {
				$maxRequiredRole[] = $role['type'];
			}
		}

		if (!empty($minRequiredRole) || !empty($maxRequiredRole)) {
			$err = "";
			if (!empty($minRequiredRole)) {
				$err .= sprintf(gettext('Minimum members required for %s role.'),implode(', ', $minRequiredRole));
			}
			if (!empty($maxRequiredRole)) {
				$err .= sprintf(gettext('Maximum allowed members exceeded for %s role.'), implode(', ', $maxRequiredRole));
			}
			if (!empty($err)){
				return array('status'=>0,'error'=>$err);
			}
		}
		return array('status'=>1,'error'=>'');
	}

	public function createActionItemsAndTouchpointsFromTemplates(){
		global $_COMPANY,$_ZONE,$db;
		$group = Group::GetGroup($this->val('groupid'));

		## Create Action item form Templates
		$actionItems = $group->getTeamActionItemTemplate();
		$teamMemberRows = $this->getTeamMembers(0);
		if (!empty($actionItems)){
			usort($actionItems, function ($a, $b) {
				return ($a['tat'] <=> $b['tat']);
			});
		}
		foreach ($actionItems as $actionItem) {
			$tasktitle = $actionItem['title'];
			$assignedto = $actionItem['assignedto'];
			$tat = $actionItem['tat'] ?? 0;
            $duedate = gmdate('Y-m-d H:i:s', strtotime("+" . $tat . " weekdays")); // in UTC
			$description = $actionItem['description'];
			$task_type = 'todo';
			$visibility = 0;

			// Assign action item to every member with a role for which action item is assigned.
			if ($assignedto > 0) {
				$teamRoleMembers = $this->getTeamMembers($assignedto);
				list($tasktitle, $description) = $this->processPlaceHoldersForTouchpointsAndActionItems($teamMemberRows, $tasktitle, $description);

				foreach ($teamRoleMembers as $m) {
					$this->addOrUpdateTeamTask(0, $tasktitle, $m['userid'], $duedate, $description, $task_type, $visibility);
				}
			}
		}

		# Create Touch points form templates
		$touchpoints = $group->getTeamTouchPointTemplate();
		if (!empty($touchpoints)){
			usort($touchpoints, function ($a, $b) {
				return ($a['tat'] <=> $b['tat']);
			});
		}

		foreach ($touchpoints as $touchpoint) {
			$tasktitle = $touchpoint['title'];
			$tat = $touchpoint['tat'] ? $touchpoint['tat'] : 0;
            $duedate = gmdate('Y-m-d H:i:s', strtotime("+" . $tat . " weekdays"));
            $description = $touchpoint['description'];
			$assignedto = 0;
			$task_type = 'touchpoint';
			$visibility = 0;
			list($tasktitle, $description) = $this->processPlaceHoldersForTouchpointsAndActionItems($teamMemberRows, $tasktitle, $description);
			$this->addOrUpdateTeamTask(0, $tasktitle, $assignedto, $duedate, $description, $task_type, $visibility);
		}

		return true;
	}

	private function processPlaceHoldersForTouchpointsAndActionItems($teamRoleMembers, $tasktitle, $description){
		$mentors = array_filter($teamRoleMembers, fn($m) => $m['sys_team_role_type'] == 2);
		$mentees = array_filter($teamRoleMembers, fn($m) => $m['sys_team_role_type'] == 3);
		if(count($mentors) == 1){
			$mentors = reset($mentors);
			$description = str_replace(['[[MENTOR_FIRST_NAME]]', '[[MENTOR_LAST_NAME]]'], [$mentors['firstname'], $mentors['lastname']], $description);
			$tasktitle = str_replace(['[[MENTOR_FIRST_NAME]]', '[[MENTOR_LAST_NAME]]'], [$mentors['firstname'], $mentors['lastname']], $tasktitle);
		}

		if(count($mentees) == 1){
			$mentees = reset($mentees);
			$description = str_replace(['[[MENTEE_FIRST_NAME]]', '[[MENTEE_LAST_NAME]]'], [$mentees['firstname'], $mentees['lastname']], $description);
			$tasktitle = str_replace(['[[MENTEE_FIRST_NAME]]', '[[MENTEE_LAST_NAME]]'], [$mentees['firstname'], $mentees['lastname']], $tasktitle);
		}

		return [$tasktitle, $description];
	}

	public static function SetTeamVariousNotificationJobs(): bool
    {
		global $_COMPANY,$_ZONE;
        if ($_COMPANY || $_ZONE) {
            Logger::Log("Calling multi-tenant method from Company or Zone context:" . $_COMPANY->id(), Logger::SEVERITY['FATAL_ERROR']);
            return false; // This job can only be executed if it is called from non company and non zone context. If company is set, exit it.
        }

        // We will schedule email notifications for all upcoming tasks in the next 24 hours.
        $timestamp_now = time();
        $datetime_after_24hr = gmdate('Y-m-d H:i:s', $timestamp_now  + 3600*24); // + 24 hours
        $datetime_after_48hr = gmdate('Y-m-d H:i:s', $timestamp_now + 3600*48); // + 48 hours
        $task_complete_status = 52; //see GET_TALENTPEAK_TODO_STATUS

		$datetime_before_24hr = gmdate('Y-m-d H:i:s', $timestamp_now - 3600*24); // - 24 hours
        $datetime_before_48hr = gmdate('Y-m-d H:i:s', $timestamp_now - 3600*48); // - 48 hours

        $companies = self::DBROGet("SELECT `companyid` FROM `companies` WHERE companies.isactive=1");
        // Step 1: Iterate over active companies and zones
        foreach ($companies as $company) {
			$_COMPANY = Company::GetCompany($company['companyid']);
            if (!$_COMPANY) {
                continue;
            }
            // Step 2: Iterate over active Zones
            foreach ($_COMPANY->getZones() as $zonesArr) {

                if ($zonesArr['isactive'] != 1 ||
                    ($_ZONE = $_COMPANY->getZone($zonesArr['zoneid'])) == null ||
                    !$_COMPANY->getAppCustomization()['teams']['enabled']
                ) {
                    continue; // Skip inactive zones or zones that do not have teams enabled.
                }

                // Step 3: Iterate over active groups
                $programs = self::DBROGet("SELECT `groupid` FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `isactive`=1");
                foreach($programs as $program) {

                    $programObj = Group::GetGroup($program['groupid']);

                    if (!$programObj->isTeamsModuleEnabled()) continue; // Optimization, if teams module is not enabled, then there is no point processing this group

                    $notificationSetting = $programObj->getTeamInactivityNotificationsSetting();
                    $notification_days_after = $notificationSetting['notification_days_after'];
                    $notification_frequency = $notificationSetting['notification_frequency'];
                    $now = new DateTime(date("Y-m-d H:i:s"));

                    // Step 4: Iterate over active teams
                    $teams = self::DBROGet("SELECT `teamid` FROM `teams` WHERE `companyid`='{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' AND `groupid`='{$program['groupid']}'  AND `isactive`=1");
                    if (!empty($teams)) {
                        foreach ($teams as $team) {
                            //
                            // Inactivity Notification Section: In this section we will remind all team members about
                            // inactivity notifications in their group
                            //
                            $latestUpdate = self::DBROGet("SELECT `modifiedon` FROM `team_tasks` WHERE `companyid`='{$_COMPANY->id()}' AND `teamid`='{$team['teamid']}' ORDER BY `modifiedon` DESC LIMIT 1");

                            if (!empty($latestUpdate)) {
                                $lastUpdate = new DateTime($latestUpdate[0]['modifiedon']);

                                $days = $now->diff($lastUpdate)->format("%a");

                                if ($notification_days_after > 0 && $notification_frequency > 0 && $days > $notification_days_after && (($days - $notification_days_after) % $notification_frequency) == 0) {
                                    // Create Job
                                    $job = new TeamJob($program['groupid'], $team['teamid']);
                                    $job->saveAsTeamInactivityNotificationJob($days,$notification_days_after,$notification_frequency);
                                }
                            }

                            //
                            // Upcoming Task due-date reminder: In this seciton we will setup jobs for reminding users
                            // about upcoming task due dates.
                            //
                            // Iterate over active tasks in active teams that are due in the next 24 hours from the
                            // $now_gmt_datetime
                            // Only find task of action item (to do) or touchpoint type
                            // Exclude touchpoints that have associated events
                            $upcoming_tasks = self::DBROGet("SELECT teamid,taskid,task_type,tasktitle,assignedto,duedate FROM team_tasks WHERE companyid={$_COMPANY->id()} AND `teamid`='{$team['teamid']}' AND duedate between '{$datetime_after_24hr}' AND '{$datetime_after_48hr}' AND isactive != {$task_complete_status} AND eventid=0");
                            foreach ($upcoming_tasks as $upcoming_task) {
                                // Create Job
                                $job = new TeamJob($program['groupid'], $upcoming_task['teamid']);
                                $job->saveAsUpcomingTaskNotificationJob($upcoming_task['task_type'], $upcoming_task['tasktitle'], $upcoming_task['assignedto'],$upcoming_task['duedate']);
                            }
                            $upcoming_tasks = null;

							 //
                            // Overdue Task due-date reminder: In this seciton we will setup jobs for reminding users
							$overdue_tasks = self::DBROGet("SELECT teamid,eventid,taskid,task_type,tasktitle,assignedto,duedate FROM team_tasks WHERE companyid={$_COMPANY->id()} AND `teamid`='{$team['teamid']}' AND duedate between '{$datetime_before_48hr}' AND '{$datetime_before_24hr}' AND isactive != {$task_complete_status}");
                            foreach ($overdue_tasks as $overdue_task) {
                                // Create Job
                                if ($overdue_task['eventid']) {
                                    // Since this tasks has an event, update the task due date or update the status
                                    $overdue_task_id = $overdue_task['taskid'];
                                    self::DBUpdate("UPDATE team_tasks JOIN events USING (eventid) SET team_tasks.isactive=IF(events.end > now(), 51, 52), duedate=IF(events.end > team_tasks.duedate, events.end, duedate) WHERE taskid={$overdue_task_id}");
                                    // ... and since we updated the task as complete or future we can skip the notification
                                } else {
                                    $job = new TeamJob($program['groupid'], $overdue_task['teamid']);
                                    $job->saveAsOverdueTaskNotificationJob($overdue_task['task_type'], $overdue_task['tasktitle'], $overdue_task['assignedto'], $overdue_task['duedate']);
                                }
                            }
                            $overdue_tasks = null;

                        }
                    }
                    $teams = null;

                    // Unresolved Team requests reminder: In this section we will remind all users who have pending
                    // team formation requests
                    //
                    $overdue_team_requests_notification_days_after = 3; // In the future make this field configurable at Program level
                    $overdue_team_requests_notification_frequency = 3; // In the future make this field configurable at Program level
                    $request_status_pending = Team::TEAM_REQUEST_STATUS['PENDING'];

                    $overdue_team_requests = self::DBROGet("SELECT * FROM `team_requests` WHERE `companyid`={$_COMPANY->id()} AND `groupid`={$program['groupid']}  AND `status`={$request_status_pending}");
                    foreach ($overdue_team_requests as $overdue_team_request) {
                        $lastUpdate = new DateTime($overdue_team_request['modifiedon']);

                        $days = $now->diff($lastUpdate)->format("%a");

                        if ($overdue_team_requests_notification_days_after > 0 && $overdue_team_requests_notification_frequency > 0 && $days > $overdue_team_requests_notification_days_after && (($days - $overdue_team_requests_notification_days_after) % $overdue_team_requests_notification_frequency) == 0) {
                            // Set up a job to remind the receiver to take an action.
                            $job = new TeamJob($program['groupid'], 0);
                            $job->saveAsOverdueRoleRequestFollowupJob($overdue_team_request['senderid'], $overdue_team_request['receiverid'], $overdue_team_request['receiver_role_id'], $overdue_team_request['modifiedon'], $days, $overdue_team_requests_notification_days_after, $overdue_team_requests_notification_frequency);
                        }
                    }
                }

                $programs = null;
                $_ZONE = null;
            }
            $_ZONE = null;
            $_COMPANY = null;
        }
		$_COMPANY = null;
		$_ZONE = null;
		return true;
	}


	public function canUpdateTeamStatus(Group $group){
		global $_COMPANY,$_USER;
		$programType  = $group->getTeamProgramType();
		$check = self::DBROGet("SELECT `roleid` FROM `team_members` WHERE `teamid`='{$this->id()}' AND `userid`='{$_USER->id()}'");
		if(!empty($check)){
			if ($programType == Team::TEAM_PROGRAM_TYPE['NETWORKING'] || $programType == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){
				return true;
			} else {
				$role = self::GetTeamRoleType($check[0]['roleid']);
				if ($role){
					$teamWorkflowSetting = $group->getTeamWorkflowSetting();
					if (2 == $role['sys_team_role_type']){ // Mentor role
						if (isset($teamWorkflowSetting['any_mentor_can_complete_team'])) {
							return $teamWorkflowSetting['any_mentor_can_complete_team'] == 1 ? true : false;
						} else {
							return true;
						}
					} elseif(3 == $role['sys_team_role_type']) { // Mentee  role
						if (isset($teamWorkflowSetting['any_mentee_can_complete_team'])) {
							return $teamWorkflowSetting['any_mentee_can_complete_team'] == 1 ? true : false;
						}
					}
				}
			}
		}
		return false;
	}

	public static function ProcessTeamProvisioningData(Group $group, array $data, int $auto_extend_capacity = 0){
		global $_COMPANY,$_ZONE, $_USER, $db;
        $groupid = $group->id();
		$response = ['totalProcessed'=>0,'totalSuccess'=>0,'memberCreated'=>0,'totalFailed'=>0,'failed'=>[]];
	
		if (!empty($data)){
			$failed = array();
			$userCreated = 0;
			$memberCreated = 0;
			$rowid = 0;
			foreach($data as $row){
                    $userData = null;
				$rowid++;
				if (!empty($row['external_id']) || !empty($row['email']))
				{
                    $chapterid = 0;
                    if (isset($row['chapter_name'])) {
                        $chaptername = Sanitizer::SanitizeGroupName($row['chapter_name']);
                        if ($chaptername) {
                            $ch = $group->getChapterByName($chaptername);
                            if ($ch) {
                                $chapterid = $ch['chapterid'];
                            }
                        }
                    }

                    if (!($_USER->canManageContentInScopeCSV($groupid, $chapterid, 0))) {
                        array_unshift($row,$rowid.': Not having sufficient permission');
                        array_push($failed,$row);
                        continue;
                    }

                    if (!empty($row['external_id'])) {
                        $userData = User::GetUserByExternalId($row['external_id'], true);
                    }

                    if (!$userData && !empty($row['email'])) {
                        $email = $row['email'];
                        if (!$_COMPANY->isValidEmail($email)){
                            array_unshift($row,$rowid.': Invalid Email');
                            array_push($failed,$row);
                            continue;
                        }
                        $userData = User::GetUserByEmail($email, true);
                    }

                    if (!$userData){
                        array_unshift($row,$rowid.': Unable to find user by external_id or email address');
                        array_push($failed,$row);
                        continue;
                    }

                    if (isset($row['role_name']) && isset($row['team_name'])){
                        if (empty(trim($row['team_name']))) {
                            array_unshift($row,$rowid.': team_name not provided');
                            array_push($failed,$row);
                            continue;
                        }

                        $roleType = addslashes($row['role_name']);
                        $role = self::GetTeamRoleByName($roleType,$groupid);
                        if ($role){
							$description = "";
							if ($role['sys_team_role_type'] == '2' && !empty($row['description'])) {
								$description = '<p>'.trim(strip_tags($row['description'])).'</p>';
							}
							$hashtags = "";
							if ($role['sys_team_role_type'] == '2' && !empty($row['hashtags'])) {
								$tagidsArray = array();
								$hashtagsArray = explode(';',$row['hashtags']);
								foreach($hashtagsArray as $handle){
									$cleanHandle = trim(ltrim($handle,'#'));
									if ($cleanHandle){
										$tagidsArray[] = HashtagHandle::GetOrCreateHandle($cleanHandle);
									}
								}
								if (!empty($tagidsArray)){
									$hashtags = implode(',',array_column($tagidsArray,'hashtagid'));
								}
							}
							$roleid = $role['roleid'];
                            $roletitle = $row['role_title'] ?? '';

                            $proceedToProvisionTeamCreation = true;
                            if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] ) {
								$checkRegistration = self::GetUserRegistrationRecord($groupid, $userData->id(), $roleid);
                                // Init silent registration process of mentor sys role type
                                if ($role['sys_team_role_type'] == 2) {
                                    $allowedCircleRoleId = Team::CanCreateCircleByRole($groupid, $userData->id());
                                    if ($allowedCircleRoleId == $roleid){ // if allowed to create a team
                                        // Init silent registration process
										if (empty($checkRegistration)){
											$join_request_status = Team::CreateAutomaticJoinRequest($group, $userData->id(), $roleid);
											if (!$join_request_status['status']) {
												$proceedToProvisionTeamCreation = false;
											}
										}
                                    } else {
                                        $proceedToProvisionTeamCreation = false;
                                    }
                                } else {
                                    // Init silent registration process
									if (empty($checkRegistration)){
                                    	$join_request_status = Team::CreateAutomaticJoinRequest($group, $userData->id(), $roleid);
									}
                                }
                            }

							// join group to user
							if (!$userData->isGroupMember($groupid)) {
								$userData->joinGroup($groupid, $chapterid, 0, 0, false, false, 'LEAD_INITIATED');
							}

                            if ($proceedToProvisionTeamCreation){
								$teamid = self::ProvisionTeamAndMembership($userData->id(),$groupid,$roleid,$row['team_name'],$chapterid,$roletitle, $group->getTeamProgramType(), $role['sys_team_role_type'], $auto_extend_capacity, $description , $hashtags);
                                if($teamid > 0){
                                    $memberCreated = $memberCreated + 1;
                                } elseif ($teamid == -1) {
									array_unshift($row,$rowid.': Maximum allowed team member limit has been reached for this role.');
                                    array_push($failed,$row);
								} elseif ($teamid == -2){
									array_unshift($row,$rowid.': Cannot assign this role because the maximum requested capacity for this role has been reached');
                                    array_push($failed,$row);
								} elseif ($teamid == -3) {
									array_unshift($row,$rowid.': The maximum number of times this user can participate in this role has been reached.');
                                    array_push($failed,$row);
								} else {
                                    array_unshift($row,$rowid.': Team creation or adding member failed');
                                    array_push($failed,$row);
                                }
                            } else {
                                array_unshift($row,$rowid.': User not allowed to start a circle');
                                array_push($failed,$row);
                            }

                        } else {
                            array_unshift($row,$rowid.': role_name does not match system configuration');
                            array_push($failed,$row);
                        }
                    } else {

                        array_unshift($row,$rowid.': role_name or team_name key not found');
                        array_push($failed,$row);
                    }
				} else{
					array_unshift($row,$rowid.': Missing email and external_id');
					array_push($failed,$row);
				}
			}
			$response = ['totalProcessed'=>count($data),'totalSuccess'=>(count($data) - count($failed)),'memberCreated'=>$memberCreated,'totalFailed'=>count($failed),'failed'=>$failed];
		}
		return $response;
	}

    public static function ProcessTeamRegistrationProvisioningData(Group $group, array $data, int $send_registration_emails = 1){
        global $_COMPANY,$_ZONE, $_USER, $db;
        $groupid = $group->id();
        $response = ['totalProcessed'=>0,'totalSuccess'=>0,'memberCreated'=>0,'totalFailed'=>0,'failed'=>[]];

        if (!empty($data) and count($data) < 26){
            $failed = array();
            $userCreated = 0;
            $memberCreated = 0;
            $rowid = 0;
            foreach($data as $row){
                $rowid++;
                $userData = null;
                if (!empty($row['external_id']) || !empty($row['email']))
                {
                    $chapterid = 0;
                    if (isset($row['chapter_name'])) {
                        $chaptername = Sanitizer::SanitizeGroupName($row['chapter_name']);
                        if ($chaptername) {
                            $ch = $group->getChapterByName($chaptername);
                            if ($ch) {
                                $chapterid = $ch['chapterid'];
                            }
                        }
                    }

                    if (!($_USER->canManageContentInScopeCSV($groupid, $chapterid, 0))) {
                        array_unshift($row,$rowid.': Not having sufficient permission');
                        array_push($failed,$row);
                        continue;
                    }

                    if (!empty($row['external_id'])) {
                        $userData = User::GetUserByExternalId($row['external_id'], true);
                    }

                    if (!$userData && !empty($row['email'])) {
                        $email = $row['email'];
                        if (!$_COMPANY->isValidEmail($email)){
                            array_unshift($row,$rowid.': Invalid Email');
                            array_push($failed,$row);
                            continue;
                        }
                        $userData = User::GetUserByEmail($email, true);
                    }

                    if (!$userData){
                        array_unshift($row,$rowid.': Unable to find user by external_id or email address');
                        array_push($failed,$row);
                        continue;
                    }

                    if (isset($row['role_name'])){

                        $roleType = addslashes($row['role_name']);
                        $role = self::GetTeamRoleByName($roleType,$groupid);
                        if ($role){
                            $roleid = $role['roleid'];
                            $max_role_capacity = intval($role['role_capacity']);
                            $role_capacity = isset($row['role_capacity']) ? intval($row['role_capacity']) : $max_role_capacity;
                            $role_capacity = min($role_capacity,$max_role_capacity); // Should be less than max_role_capacity

                            $existing_registrations = self::GetUserJoinRequests($groupid, $userData->id());
                            if (!empty($existing_registrations)){
                                // Check if user is already registered for the same role
                                $existing_roleids_registered = array_column($existing_registrations,'roleid');
                                if (in_array($roleid, $existing_roleids_registered)) {
                                    array_unshift($row, $rowid . ': Skipping, user already registered');
                                    array_push($failed, $row);
                                    continue;
                                }
                                // Check if the user is allowed to register for more than one roles
                                if ($group->getTeamJoinRequestSetting()!=1) {
                                    array_unshift($row, $rowid . ': Skipping, user already registered for another role, only one registration is allowed');
                                    array_push($failed, $row);
                                    continue;
                                }
                            }

                            // Check if role guardrails are in place and if they are satisfied
                            $guardRails = json_decode($role['restrictions'],true);
                            if (!empty($guardRails)){
                                $isRequestAllowd = Team::CheckRequestRoleJoinAllowed($userData->id(), $guardRails);
                                if (!$isRequestAllowd) {
                                    array_unshift($row, $rowid . ': Skipping, user not allowed to register for this role');
                                    array_push($failed, $row);
                                    continue;
                                }
                            }

                            $rval = self::SaveTeamJoinRequestData($groupid, $roleid, '{}',$role_capacity, $send_registration_emails, $userData->id(), $chapterid);
                            if ($rval) {
                                if ($rval == 2 && ($_ZONE->val('app_type') === 'talentpeak')) { // If insert then add users to join group
                                    $userData->joinGroup($groupid, $chapterid, 0, 0, boolval($send_registration_emails), false, 'LEAD_INITIATED');
                                }
                            } else {
                                array_unshift($row,$rowid.': Unable to save registration data');
                                array_push($failed,$row);
                                continue;
                            }
                        } else {
                            array_unshift($row,$rowid.': role_name does not match system configuration');
                            array_push($failed,$row);
                        }
                    } else {

                        array_unshift($row,$rowid.': role_name key not found');
                        array_push($failed,$row);
                    }
                } else{
                    array_unshift($row,$rowid.': Missing email and external_id');
                    array_push($failed,$row);
                }
            }
            $response = ['totalProcessed'=>count($data),'totalSuccess'=>(count($data) - count($failed)),'memberCreated'=>$memberCreated,'totalFailed'=>count($failed),'failed'=>$failed];
        } else {
            $response = ['totalProcessed'=>0,'totalSuccess'=>0,'memberCreated'=>0,'totalFailed'=>count($data),'failed'=> count($data) ? [['Too many rows, maximum allowed is 25']] : [['0 data rows']]];
        }
        return $response;
    }
	
	public static function ProvisionTeamAndMembership(int $userid,int $groupid,int $roleid,string $teamname, int $chapterid =0, string $roletitle ='', string $programType = '', int $systemRoleType = 0, int $auto_extend_capacity= 0, string $description='' , string $hashtags=''){
		global $_COMPANY,$_ZONE;
		$teamname = addslashes($teamname);

		$checkTeam = self::DBGetPS("SELECT `teamid` FROM `teams` WHERE `companyid`=? AND `zoneid`=? AND `groupid`=? AND `team_name`=?",'iiix',$_COMPANY->id(),$_ZONE->id(),$groupid,$teamname);
		
		if (empty($checkTeam)){
			$teamid = Team::CreateOrUpdateTeam($groupid, 0, $teamname, $chapterid,0,$description, $hashtags);
		 } else {
			$teamid = $checkTeam[0]['teamid'];
		}

        if ($teamid) {
            $team = Team::GetTeam($teamid);
			if (!empty($circle_member_capacity_attribue)) {
				$team->updateCircleMaxRolesCapacity($circle_member_capacity_attribue,$roleid);
				// Reinitiate team object
				$team = Team::GetTeam($teamid);
			}	

			$isTeamMember = $team->isTeamMember($userid);
			if (!$isTeamMember && !$team->isAllowedNewTeamMemberOnRole($roleid)) {
				return -1;
			}
			$roleCapacityFilled = false;
			$errorReturnCode = 0;
			if ($programType == Team::TEAM_PROGRAM_TYPE['CIRCLES']) { 

				if ($systemRoleType !=2 ){
					if (!$isTeamMember){
						$members = $team->getTeamMembers($roleid);
						$canJoinRole = Team::CanJoinARoleInTeam($groupid,$userid,$roleid);
						$circleRolesCapacity = Team::GetCircleRolesCapacity($groupid,$teamid);
						$allowedRoleCapacity = $circleRolesCapacity[$roleid]['circle_role_max_capacity'];
						$availableRoleCapacity = ($allowedRoleCapacity - count($members));

						if (!$canJoinRole) {
							$roleCapacityFilled = true;
							$errorReturnCode = -2;
						} elseif ($availableRoleCapacity < 1) {
							$roleCapacityFilled = true;
							$errorReturnCode = -3;
						}
					}
				} elseif($systemRoleType == 2 && !empty($checkTeam)) { 
					//This is the case when team is already created and sys role type is Mentor type 
					Team::CreateOrUpdateTeam($groupid, $team->val('teamid'), $teamname, $team->val('chapterid'),0,$description, $hashtags);
				}
			} else {
				if ($isTeamMember ) {
					return -2;
				} elseif(!Team::CanJoinARoleInTeam($groupid, $userid, $roleid)) {
					$roleCapacityFilled = true;
					$errorReturnCode = -2;
				}
			}
			if ($roleCapacityFilled && $auto_extend_capacity) {
				self::ExtendRoleRequestedCapacity($groupid, $userid, $roleid);
			} elseif($roleCapacityFilled && $errorReturnCode < 0){
				return $errorReturnCode;
			}
			
            $team->addUpdateTeamMember($roleid, $userid, 0, $roletitle);
        }

		return $teamid;
	}

	public static function GetTeamRoleByName(string $roleName,int $groupid, bool $activeOnly = true){
		global $_COMPANY,$_ZONE;

		$activeOnlyCondition = "";
		if ($activeOnly){
			$activeOnlyCondition = " AND isactive=1";
		}
		$role = self::DBGetPS("SELECT * FROM `team_role_type` WHERE `companyid`=? AND `zoneid`=? AND `groupid`=? AND `type`=? {$activeOnlyCondition}",'iiix',$_COMPANY->id(),$_ZONE->id(),$groupid, $roleName);
        if (!empty($role)){
			return $role[0];
		}
		return null;
	}

    public function loggedinUserCanViewFeedback(int $feedbackVisibility, int $feedbackCreatedBy, int $feedbackAssignedTo) : bool
    {
        global $_USER;

        if ($feedbackCreatedBy == $_USER->id() || // User created it
            $feedbackAssignedTo == $_USER->id() || // User is assigned the feedback
            $_USER->canManageContentInScopeCSV($this->id(), $this->val('chapterid')) || // User is program less
            $feedbackVisibility == self::TEAM_TASK_VISIBILITY['ALL_MEMBERS'] // It is open to all to view
        ) {
            return true;
        }
        return false;
    }

	public static function GetRoleUsesStat(int $groupid, int $roleid){
		global $_COMPANY,$_ZONE;

		$totalMembers = self::DBROGet("SELECT COUNT(1) as totalMember FROM `team_members` WHERE `roleid`='{$roleid}'")[0]['totalMember'];
		$memberJoinRequests = self::DBROGet("SELECT COUNT(1) as memberJoinRequests FROM `member_join_requests` WHERE `companyid`='{$_COMPANY->id()}' AND `groupid`='{$groupid}' AND `roleid`='{$roleid}'")[0]['memberJoinRequests'];

		return array('totalMembersByRole'=>$totalMembers,'totalMemberRequestsByRole'=>$memberJoinRequests);
	}

	public static function GetTeamRequestDetail(int $groupid, int $team_request_id) : ?array
    {
		global $_COMPANY,$_ZONE;
		$r = self::DBGet("SELECT * FROM `team_requests` WHERE `companyid`='{$_COMPANY->id()}' AND `groupid`='{$groupid}' AND `team_request_id`='{$team_request_id}'");
		if (!empty($r)){
			return $r[0];
		}
		return null;
	}

	public static function GetAcceptedTeamRequestsCount(int $groupid, int $senderid, int $sender_role_id, int $receiver_role_id) : int
    {
		global $_COMPANY,$_ZONE;
        $request_status_accepted = Team::TEAM_REQUEST_STATUS['ACCEPTED'];
		$r = self::DBGet("SELECT COUNT(1) as totalAccepted FROM `team_requests` WHERE `companyid`='{$_COMPANY->id()}' AND `groupid`='{$groupid}' AND `senderid`={$senderid} AND `sender_role_id`={$sender_role_id} AND `receiver_role_id`={$receiver_role_id} AND `status`={$request_status_accepted}");
		return intval($r[0]['totalAccepted']);
	}

	public static function CreateNetworkingTeam(int $groupid, int $roleid, int $subject_userid, int $matched_userid, )
	{
		global $_USER;

		if (
			($subject_user = User::GetUser($subject_userid)) === null ||
			($matched_user = User::GetUser($matched_userid)) === null
		) {
			return false;
		}

		$team_name =  $subject_user->getFullName() .' & '. $matched_user->getFullName();
		$teamid = self::CreateOrUpdateTeam($groupid, 0, $team_name,0,$subject_userid);
		if ($teamid){ 
			$team = Team::GetTeam($teamid);
			// Add Members
			$team->addUpdateTeamMember($roleid, $matched_user->id());
			$team->addUpdateTeamMember($roleid, $subject_user->id());
			// Update Team Status
			$team->activate(false); // This method will take care of creating touchpiont and task from templates and send email to members
			return true;
		}
		return false;
	}

	
	/**
	 * MakeTeamNameUnique
	 *
	 * @param  int $groupid
	 * @param  int $teamid
	 * @param  string $teamname
	 * @return string
	 */
	public static function MakeTeamNameUnique(int $groupid, int $teamid, string $teamname){
		global $_COMPANY,$_ZONE; /* @var Company $_COMPANY */

        $r = self::DBGetPS("SELECT * FROM `teams` WHERE `companyid`=? AND `zoneid`=? AND `groupid`=? AND team_name=?",'iiix',$_COMPANY->id(),$_ZONE->id(),$groupid,$teamname);
		if (!empty($r)){
			if ($r[0]['teamid'] != $teamid) {
				$teamname = $teamname.' '.date("Y-m-d H:i:s");
			}
		}
		return $teamname;
	}



	public static function RecycleNetworkingTeams(): bool
    {
        global $_COMPANY, $_ZONE;
        if ($_COMPANY || $_ZONE) {
            Logger::Log("Calling multi-tenant method from Company or Zone context:" . $_COMPANY->id(), Logger::SEVERITY['FATAL_ERROR']);
            return false; // This job can only be executed if it is called from non company and non zone context. If company is set, exit it.
        }

        $utc_timezone = new DateTimeZone('UTC');

		$companies = self::DBGet("SELECT `companyid` FROM `companies` WHERE companies.isactive=1");
        // Step 1: Iterate over active companies and zones
        foreach ($companies as $company) {
            $now_utc_datetime_obj = new DateTime('now', $utc_timezone);
			$_COMPANY = Company::GetCompany($company['companyid']);
            if (!$_COMPANY) {
                continue;
            }
            // Step 2: Iterate over active Zones
            foreach ($_COMPANY->getZones() as $zonesArr) {

                if ($zonesArr['isactive'] != 1 ||
                    ($_ZONE = $_COMPANY->getZone($zonesArr['zoneid'])) == null ||
                    !$_COMPANY->getAppCustomization()['teams']['enabled']
                ) {
                    continue; // Skip inactive zones;
                }

                // Step 3: Iterate over active groups
                $active_programs = self::DBGet("SELECT `groupid` FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `isactive`=1");
                foreach($active_programs as $program) {
                    $program_obj = Group::GetGroup($program['groupid']);

                    // We will process the program only if it is of Networking Type
                    if (!$program_obj->isTeamsModuleEnabled() || $program_obj->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['NETWORKING']) {
                        continue;
                    }

                    $networkProgramSetting = $program_obj->getNetworkingProgramStartSetting();
                    $program_start_date = null;
                  	try { $program_start_date = new DateTime($networkProgramSetting['program_start_date'] . ' UTC');} catch (Exception $e) {}
                    $team_match_cycle_days = (int)$networkProgramSetting['team_match_cycle_days'];

                    // We will process only if $program_start_date is in the past and program is past $team_match_cycle_days
                    $tolerate_days = min ($team_match_cycle_days, 3); // No of days after anniversary days for which team recycling can be done.
                    if (
                        !$program_start_date ||
                        !$team_match_cycle_days ||
                        ($now_utc_datetime_obj->diff($program_start_date)->days < $team_match_cycle_days) || // Program is not event one cycle complete
                        (($now_utc_datetime_obj->diff($program_start_date)->days % $team_match_cycle_days) > $tolerate_days) // Will try  for upto tolerate days of last batch anniversary.
                    ) {
                        continue;
                    }

                    // Step 4: Iterate over active teams
                    $teams = self::DBGet("SELECT `teamid`,`team_start_date` FROM `teams` WHERE `companyid`='{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' AND `groupid`='{$program['groupid']}'  AND `isactive`=1");
                    foreach ($teams as $team) {
                        $team_start_utc_datetime_obj = new DateTime($team['team_start_date'], $utc_timezone);
                        $interval = $now_utc_datetime_obj->diff($team_start_utc_datetime_obj);
                        $days_since_team_start =  $interval->days;

                        if ($days_since_team_start >= $team_match_cycle_days) {

                            $team_obj = self::GetTeam($team['teamid']);
                            // Mark team as complete
                            $team_obj->complete();
                        }
                        $team_obj = null;
                    }
                    $teams = null;

                    // Step 5: For users who have completed team make them part of another team
                    // Shuffle up the array to introduce randomness
                    $pending_users_rows = self::DBGet("SELECT userid FROM member_join_requests WHERE companyid={$_COMPANY->id()} AND groupid={$program['groupid']} AND request_capacity>member_join_requests.used_capacity AND isactive=1");
                    $users_who_need_to_be_paired = array_column($pending_users_rows, 'userid');
                    shuffle($users_who_need_to_be_paired);
                    while (!empty($users_who_need_to_be_paired)) {
                        $subject_userid = (int)$users_who_need_to_be_paired[0]; // Pick the first element
                        [$status, $requested_role_with_suggesstions] = self::GetTeamMembersSuggestionsForRequestRoles($program_obj,$subject_userid);
                        $matched_userid = 0;
                        // Re-create a team with best match
                        if ($status == 1 && !empty($requested_role_with_suggesstions)) {
                            $suggestions = $requested_role_with_suggesstions[0]['suggestions'];
                            
                            if (!empty($suggestions)) {
                                $matched_userid = self::GetBestSuggestedUserForNetworking($program_obj->id(), $subject_userid, $suggestions);
                                if ($matched_userid){
                                    self::CreateNetworkingTeam($program_obj->id(),$requested_role_with_suggesstions[0]['roleid'],$subject_userid, $matched_userid);
                                }
                            }
                        }
                        // Remove subject userid from $users_who_have_completed - subject_userid and matched_userid
                        $users_who_need_to_be_paired = array_filter($users_who_need_to_be_paired, function ($u) use ($subject_userid, $matched_userid ) {
                            return (($u != $subject_userid) && ($u != $matched_userid));
                        });
                        $users_who_need_to_be_paired = array_values($users_who_need_to_be_paired);
                    }
                }
                $active_programs = null;
                $_ZONE = null;
            }
            $_ZONE = null;
            $_COMPANY = null;
        }
		$_COMPANY = null;
		$_ZONE = null;
		return true;
	}

    /**
     * This method checks if the suggested users have been previously paired with the subject userid, if so those
     * suggestions will be skipped.
     * @param int $groupid
     * @param int $subject_userid
     * @param array $suggestions
     * @return int
     */
	public static function GetBestSuggestedUserForNetworking (int $groupid, int $subject_userid, array $suggestions) : int
	{
		global $_COMPANY,$_ZONE,$_USER;

		if (empty($suggestions) || !self::CanJoinARoleInTeam($groupid, $subject_userid, $suggestions[0]['roleid'])) {
			return 0;
		}
		$bestMatchedUserid = 0;
        // Get past or current teams for the subject userid
		$myTeams = self::GetUserTeamsList($groupid,$subject_userid, false);
		if (!empty($myTeams)) {
            // Get a list of all userids from teams of which the subject userid has been/is part of
			$subject_users_teamids = implode(',', array_column($myTeams,'teamid'));
			$members_of_subject_users_teams = self::DBGet("SELECT team_members.userid  FROM `team_members` WHERE team_members.`teamid` IN({$subject_users_teamids})");
			
			$memberUids = array_column($members_of_subject_users_teams,'userid');

			foreach($suggestions as $suggestion) {
				if (!in_array($suggestion['userid'], $memberUids) && self::CanJoinARoleInTeam($groupid, $suggestion['userid'], $suggestion['roleid'])) {
					$bestMatchedUserid = (int)$suggestion['userid'];
					break;
				}
			}
		} else{
			if (!empty($suggestions)){
				foreach($suggestions as $suggestion) {
					if ( self::CanJoinARoleInTeam($groupid, $suggestion['userid'], $suggestion['roleid'])) {
						$bestMatchedUserid = (int)$suggestion['userid'];
						break;
					}
				}
			} 
		}
		return $bestMatchedUserid;
	}


	public static function CanCreateNetworkingTeam(int $groupid, string $programType){
		global $_COMPANY,$_ZONE,$_USER;

		if ($programType == self::TEAM_PROGRAM_TYPE['NETWORKING']) {
			if (!empty(self::GetUserJoinRequests($groupid))) {
				$myNetowkingTeams = self::GetUserTeamsList($groupid,$_USER->id(),false);
				if ( empty($myNetowkingTeams) ) {
					return true;
				} else {
					foreach($myNetowkingTeams as $mteam) {
						if ($mteam['isactive'] == self::STATUS_ACTIVE  || $mteam['isactive'] == self::STATUS_INACTIVE || $mteam['isactive'] == self::STATUS_PAUSED) {
							return false;
						}
					}
					return true;
				}
			}
		} 
		return false;

	}

	public static function GetTeamMembersSuggestionsForRequestRoles(Group $group, int $matchAgainstUserid, int $matchAgainstTeamRoleId = 0, array $oppositeUseridsWithRoles = array(), array $filter_attribute_value=array(), array $filter_attribute_name = array(), string $name_attribute='', array $filter_attribute_type = array(), int $page = 1 )
	{	
		global $_COMPANY,$_ZONE;
		$status = 0;
		$groupid = $group->id();
		$joinRequests = array();
        $isCallFromDiscoverTab = empty($matchAgainstTeamRoleId);
		if (
			($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['NETWORKING']) ||
			$group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER'] ||
			$group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['ADMIN_LED']
		){

			if ($matchAgainstTeamRoleId) {
                // Get suggestions for specific role as the user $matchAgainstUserid might have registered
                // for multiple roles. This call is typicaly made from Manage > Team Create tab
                // For Discover tab, we get $matchAgainstTeamRoleId=0
				$findMatchAgainstJoinRequest = Team::GetRequestDetail($groupid, $matchAgainstTeamRoleId, $matchAgainstUserid);
				if ($findMatchAgainstJoinRequest){
					$joinRequests[] = $findMatchAgainstJoinRequest;
				}
			} else { // Get suggestions for all requested roles
				$joinRequests = Team::GetUserJoinRequests($groupid,$matchAgainstUserid,0);
			}

			if (!empty($joinRequests)) {
				$matchingParameters = $group->getTeamMatchingAlgorithmParameters();
				$customAttributes = $group->getTeamMatchingAlgorithmAttributes();
	
		
				foreach($joinRequests as &$matchAgainstJoinRequest){
					$sys_team_role_type = $matchAgainstJoinRequest['sys_team_role_type'];
					$matchAgainstUserId = $matchAgainstJoinRequest['userid'];
					$matchAgainstSurveyResponses =  json_decode($matchAgainstJoinRequest['role_survey_response'],true) ?: array();

					// Get user to match against <- END
					$matchAgainstTeamRoleId = 0;
                    $skipMatches = false;
					if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['NETWORKING']){
						$matchAgainstTeamRoleId = $matchAgainstJoinRequest['roleid'];
						$oppositRolesType =  $matchAgainstJoinRequest['type'];
					} else {
						$sys_team_role_type = $sys_team_role_type == 2 ? 3 : 2; // If Mentor then Set Mentee vice-versa
						$oppositRoles = Team::GetProgramTeamRoles($groupid,1, $sys_team_role_type);
						$oppositRolesType = '';
						if (!empty($oppositRoles)){
							$matchAgainstTeamRoleId = $oppositRoles[0]['roleid'];
							$oppositRolesType =  $oppositRoles[0]['type'];
                            // Find matches unless the configuration for opposite roles is set to skip - $skipFindMatches
                            $skipMatches = $isCallFromDiscoverTab && !$oppositRoles[0]['discover_tab_show'];
						}
					}

					$suggestions = array();
                    // Initialize key parts of the return array element
                    $matchAgainstJoinRequest['suggestions'] =  array();
                    $matchAgainstJoinRequest['oppositRoleId'] = $matchAgainstTeamRoleId;
                    $matchAgainstJoinRequest['oppositRolesType'] = $oppositRolesType;
                    $matchAgainstJoinRequest['skipMatches'] = $skipMatches;

					if ($matchAgainstTeamRoleId && !$skipMatches){ // Proccess logic only if Role found to match

						$skipJoinRequestCapacityCheck = false;
                        // Get all unmatched user against selected role and make survey response compatible for matching Algo -> START
						if (isset($oppositeUseridsWithRoles['oppositeUserids'])) { // Set of given data if want to get percentage of matching for given data only
							$skipJoinRequestCapacityCheck = $oppositeUseridsWithRoles['skipJoinRequestCapacityCheck'];
							$oppositeJoinRequests = array();
							foreach($oppositeUseridsWithRoles['oppositeUserids'] as $oppositeUseridsWithRole) {
								
								$requestData = Team::GetRequestDetail($groupid,$oppositeUseridsWithRole['roleid'],$oppositeUseridsWithRole['userid']);
								if ($requestData){
									$oppositeUserData = User::GetUser($oppositeUseridsWithRole['userid']);
									$requestData['firstname'] = $oppositeUserData->val('firstname');
									$requestData['lastname'] = $oppositeUserData->val('lastname');
									$requestData['email'] = $oppositeUserData->val('email');
									$requestData['homeoffice'] = $oppositeUserData->val('homeoffice');
									$requestData['department'] = $oppositeUserData->val('department');
									$requestData['picture'] = $oppositeUserData->val('picture');
									$requestData['jobtitle'] = $oppositeUserData->val('jobtitle')?:'Job title unavailable';
								}
								$oppositeJoinRequests[] = $requestData;
							}

						} else {
							$oppositeJoinRequests  = Team::GetTeamJoinRequests($groupid,$matchAgainstTeamRoleId, true);
						}
						
						// Skip run matching Algorithm if there are not role requests to match.
						if (empty($oppositeJoinRequests)) {
							break;
						}	

						$filteredUserids = array();
						$isPrimaryFilterSet = 0;
						$isCustomFilterSet = 0;
						if (!empty($filter_attribute_name) && !empty($filter_attribute_value) && !empty($filter_attribute_type)) {
							// Note array_flip and subsequent isset is used for faster searches. isset is 100 times
							// faster than in_array
							$primaryCommonUserIds = array();
                            $surveyCommonUserids = array();
                            $searchCustomParametersMatchType = array();
                            $searchCustomParametersMatchValue = array();

                            // Process each search attribute to find the users who match the various search criteria.
                            // The final set of userids that match the search criteria will be stored in $filteredUserids
							for($x = 0; $x < count($filter_attribute_name); $x++) {
								if (!empty($filter_attribute_name[$x]) && !empty($filter_attribute_value[$x])){
									if ($filter_attribute_type[$x] == 'primary'){
                                        $primaryCommonUserIds[] = array_flip(UserCatalog::GetUserCatalog($filter_attribute_name[$x], $filter_attribute_value[$x], '==')->getUserIds());
										$isPrimaryFilterSet = 1;
									} elseif ($filter_attribute_type[$x] == 'custom') {
                                        // For custom attributes, here we are just building the search arrays
                                        // * $searchCustomParametersMatchType: Associative array where keys are question names and values are match type
                                        // * $searchCustomParametersMatchValue: Associative array where keys are question names and values are value that need to be matched to
										$questionKey = $filter_attribute_name[$x];
										$question = $group->getTeamCustomAttributesQuestion($questionKey);
										if (!empty($question)) {
											if ($question['type'] == 'radiogroup' || $question['type'] == 'dropdown') {
												$searchCustomParametersMatchType[$questionKey] = self::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['MATCH'];
												$searchCustomParametersMatchValue[$questionKey] = $filter_attribute_value[$x];
											} elseif ($question['type'] == 'checkbox'){
												$searchCustomParametersMatchType[$questionKey] = self::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['MATCH_N_NUMBERS'];
												$searchCustomParametersMatchValue[$questionKey] = array($filter_attribute_value[$x]);
											} elseif ($question['type'] == 'rating'){
												$searchCustomParametersMatchType[$questionKey] = self::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['EQUAL_TO'];
												$searchCustomParametersMatchValue[$questionKey] = $filter_attribute_value[$x];
											} elseif ($question['type'] == 'text'){
												$searchCustomParametersMatchType[$questionKey] = self::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['WORD_MATCH'];
												$searchCustomParametersMatchValue[$questionKey] = $filter_attribute_value[$x];
											} elseif ($question['type'] == 'comment') {
                                                $searchCustomParametersMatchType[$questionKey] = self::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['IGNORE'];
                                                $searchCustomParametersMatchValue[$questionKey] = $filter_attribute_value[$x];
                                            }
										}
										$isCustomFilterSet = 1;
									}
								}
							}

                            // At this point $primaryCommonUserIds is array of arrays of userids representing users matching each primary attribute filter
                            // Collapse the array into one set of users that match all primary attribute filters and store in $primaryCommonUserIds
							if (count($primaryCommonUserIds)){
                                // Since we are using flipped arrays, we need to use array_intersect_key
                                $primaryCommonUserIds = call_user_func_array('array_intersect_key', $primaryCommonUserIds);
							}

                            // Lets fetch common user ids that match all customer (survey) filters
                            if (!empty($searchCustomParametersMatchType) && !empty($searchCustomParametersMatchValue)) {
                                $surveyCommonUserids = self::GetSurveyResponsesMatchedUsers($searchCustomParametersMatchType, $searchCustomParametersMatchValue, $oppositeJoinRequests);

                                // At this point $surveyCommonUserids is array of array of userids representing users matching each custom attribute filter
                                // Collapse the array into one set of users that match all survey attribute filters and store in $surveyCommonUserids
                                if (count($surveyCommonUserids)) {
                                    // Since we are using flipped arrays, we need to use array_intersect_key
                                    $surveyCommonUserids = call_user_func_array('array_intersect_key', $surveyCommonUserids);
                                }
                            }

                            // Create final list of userids that match both primary and custom filter sets, store final result in $filteredUserids
							if ($isPrimaryFilterSet && $isCustomFilterSet){
                                // Since we are using flipped arrays, we need to use array_intersect_key
								$filteredUserids = array_intersect_key($primaryCommonUserIds,$surveyCommonUserids);
							} elseif(!$isPrimaryFilterSet && $isCustomFilterSet) {
								$filteredUserids = $surveyCommonUserids;
                            } elseif($isPrimaryFilterSet && !$isCustomFilterSet) {
                              $filteredUserids = $primaryCommonUserIds;
                            //} else {
                            //    $filteredUserids = array();
                            }
						
						}

						$usersByRoleRequestWithSurveyResponses = array();
						foreach ($oppositeJoinRequests as $oppositeJoinRequest){
							if ($oppositeJoinRequest['userid']== $matchAgainstUserId){
                                continue;
							}

                            // Filter by search - If filter is set, skip users who do not meet filter criteria
                            if (($isPrimaryFilterSet || $isCustomFilterSet) && !isset($filteredUserids[$oppositeJoinRequest['userid']])) {
                                continue;
                            }

                            // Filter by name - If filter is set, skip users do do not meet filter criteria
                            if (!empty($name_attribute)) {
                                // Using stripos instead of preg_match for performance
                                if (stripos(($oppositeJoinRequest['firstname'].' '.$oppositeJoinRequest['lastname']), $name_attribute) === false) {
                                    continue;
                                }
                            }

							if (!$skipJoinRequestCapacityCheck && !self::DoesJoinRequestHaveActiveCapacity ($oppositeJoinRequest)) {
                                continue;
							}

							$role_survey_response = json_decode($oppositeJoinRequest['role_survey_response'],true);
							$usersByRoleRequestWithSurveyResponses[$oppositeJoinRequest['userid']] = $role_survey_response;
						}
						// Get all unmatched user against selected role <- END


						// Matching Algorithm Parameters
						$primaryParameters = $matchingParameters['primary_parameters'] ?? array(); // Primary Parameters (User Catalog)
						$customParameters = $matchingParameters['custom_parameters'] ?? array();  // Custom Parameters (Survey Responses)
						$customAttributes = $customAttributes['pages'][0]['elements'] ?? $customAttributes;
						$mandatoryPrimaryParameters = $matchingParameters['mandatory_primary_parameters'] ?? array();
						$mandatoryCustomParameters = $matchingParameters['mandatory_custom_parameters'] ?? array();
						$mandatory = array_merge($mandatoryPrimaryParameters,$mandatoryCustomParameters);

						// check if Primery or custom parameter matching criteria set
						$isPrimaryCriteriaSet = count(array_filter($primaryParameters, function($value) {
							return $value > 0;
						})) > 0;
						
						$isCustomCriteriaSet = count(array_filter($customParameters, function($value) {
							return $value > 0;
						})) > 0;

						// Get Suggestions
						$totalSuggestionsCount = 0;
						if (($isPrimaryCriteriaSet || $isCustomCriteriaSet) && (!empty(array_keys(array_filter($customParameters ?? []))) && !empty($customAttributes)) || (!empty(array_keys(array_filter($primaryParameters ?? []))) && !empty($usersByRoleRequestWithSurveyResponses))){
							
							[$suggestionsRows,$loadMoreDataAvailable, $totalSuggestionsCount] = self::RunMatchingAlgorithmForSuggestions($groupid,0,$matchAgainstTeamRoleId,$primaryParameters, $customParameters, $matchAgainstUserId, $matchAgainstSurveyResponses,$usersByRoleRequestWithSurveyResponses, $customAttributes, $mandatory,$page);

							foreach($suggestionsRows as &$s){
								if ($s['userid'] == $matchAgainstUserid || $s['matchingPercentage'] <= 0) {
									--$totalSuggestionsCount;
									continue;
								}
								if (
									!self::CanJoinATeamWithOtherUser($groupid, $s['userid'], $matchAgainstTeamRoleId, $matchAgainstUserid)
                                    // || !self::CanJoinARoleInTeam($groupid,$s['userid'],$matchAgainstTeamRoleId) /* Commented out as this check was already performed by the time this method was called */
                                ) {
									--$totalSuggestionsCount;
									continue;
								}
								$s['roleid'] = $matchAgainstTeamRoleId;
								$suggestions[] = $s;
							}
							unset($s);
						
							
						} else { // If matching and survey criteria not set
							foreach($oppositeJoinRequests as $oppositeJoinRequest){
								if ($oppositeJoinRequest['userid'] == $matchAgainstUserid) {
									continue;
								}

                                // Filter by search - If filter is set, skip users who do not meet filter criteria
                                if (($isPrimaryFilterSet || $isCustomFilterSet) && !isset($filteredUserids[$oppositeJoinRequest['userid']])) {
                                    continue;
                                }

                                // Filter by name - If filter is set, skip users who do not meet filter criteria
                                if (!empty($name_attribute)) {
                                    // Using stripos instead of preg_match for performance
                                    if (stripos(($oppositeJoinRequest['firstname'].' '.$oppositeJoinRequest['lastname']), $name_attribute) === false) {
                                        continue;
                                    }
                                }

								if (
									!self::CanJoinATeamWithOtherUser($groupid, $oppositeJoinRequest['userid'], $oppositeJoinRequest['roleid'], $matchAgainstUserid) ||
									!self::CanJoinARoleInTeam($groupid,$oppositeJoinRequest['userid'],$matchAgainstTeamRoleId)
								) {
									continue;
								}


								$suggestion = $oppositeJoinRequest;
								$suggestion['matchingPercentage'] = 100;
								$suggestion['parameterWiseMatchingPercentage'] = array('requestMatch'=> array('title'=> gettext('Matched based on role request'), 'value'=>'','attributeType'=>'', 'percentage'=>100));
								if (isset($suggestion['department']) && is_numeric($suggestion['department'])) {
									$suggestion['department'] = $_COMPANY->getDepartmentName($suggestion['department']);
								}
								$suggestions[] = $suggestion;
							}

							$start = (($page - 1) * MAX_TEAMS_ROLE_MATCHING_RESULTS);
							$loadMoreDataAvailable = false;
							if (count($suggestions)> $page * MAX_TEAMS_ROLE_MATCHING_RESULTS) {
								$loadMoreDataAvailable = true;
							}
							$totalSuggestionsCount = count($suggestions);
							$suggestions = array_slice($suggestions,$start,MAX_TEAMS_ROLE_MATCHING_RESULTS,true);
						}
						$matchAgainstJoinRequest['suggestions'] =  $suggestions;
						$matchAgainstJoinRequest['totalSuggestionsCount'] = $totalSuggestionsCount;
						$matchAgainstJoinRequest['oppositRoleId'] = $matchAgainstTeamRoleId;
						$matchAgainstJoinRequest['oppositRolesType'] = $oppositRolesType;
						$matchAgainstJoinRequest['loadMoreDataAvailable'] = $loadMoreDataAvailable;
					} 
				}
				unset($matchAgainstJoinRequest); // Unset reference
				$status = 1;
			}
		}
		return array ($status,$joinRequests);
	}

    public static function GetTeamProgramType(int $numericValue)
	{
		switch ($numericValue) {
			case self::TEAM_PROGRAM_TYPE['ADMIN_LED']:
				return 'Admin Led';
			case self::TEAM_PROGRAM_TYPE['PEER_2_PEER']:
				return 'Peer-to-Peer';
			case self::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']:
				return 'Individual Development';
			case self::TEAM_PROGRAM_TYPE['NETWORKING']:
				return 'Networking';
			case self::TEAM_PROGRAM_TYPE['CIRCLES']:
				return 'Circle';
			default:
				return 'Undefined';
		}
	}


	/**
	 * resetRoleUsedCapacity Reset user team role join capacity
	 *
	 * @return void
	 */
	public function resetRoleUsedCapacity() 
	{
		global $_COMPANY,$_ZONE;
		//$program = Group::GetGroup($this->val('groupid'));
		//if ($program->getTeamProgramType() == self::TEAM_PROGRAM_TYPE['NETWORKING'] || $program->getTeamProgramType() == self::TEAM_PROGRAM_TYPE['CIRCLES']){ // Reset role capacity when team complete for every program type
			$members = $this->getTeamMembers(0);
			foreach($members as $member){
				// Reset used_capacity and status to role availe to join
                self::UpdateUsedRoleCapacity($this->val('groupid'),  $member['userid'], $member['roleid']);
                // Remove role request if program type is circle and user have not teams with requested role
                $this->deleteEmptyJoinRequests($member['userid'], $member['roleid']);
			}
		//}
	}

	public static function CanCreateCircleByRole(int $groupid, int $subject_userid)
	{
		global $_COMPANY,$_ZONE;
		$program = Group::GetGroup($groupid);
		if ($program->getTeamProgramType() != self::TEAM_PROGRAM_TYPE['CIRCLES']){
			return 0;
		}
		$roleTypes = Self::GetProgramTeamRoles($groupid,1,2); //Get Mentors role only
		$canCreate = 0;

		
		if (!empty($roleTypes)  ) {
			
			$isRegistrationClosed = false;
			foreach ($roleTypes as $role) {
				$start = $role['registration_start_date'] ?: null;
				$end = $role['registration_end_date'] ?: null;

				if (
					($start === null || date('Y-m-d') >= $start) && // No start restriction or now >= $start
					($end === null || date('Y-m-d') <= $end)       // No end restriction or now <= $end date
				) {
					$isRegistrationClosed = false;
					break; // Break on any condition which satisfies registration closed criteria
				} else {
					$isRegistrationClosed = true;
				}
			}

			if ($isRegistrationClosed) {
				return $canCreate;
			}


			$roleIds = implode(array_column($roleTypes,'roleid'));
			$joinRequests = self::DBGet("SELECT member_join_requests.* FROM `member_join_requests`  WHERE `member_join_requests`.`companyid`='{$_COMPANY->id()}' AND member_join_requests.userid='{$subject_userid}' AND member_join_requests.groupid='{$groupid}' AND roleid IN({$roleIds}) AND isactive=1");
			if (!empty($joinRequests)) {
				foreach($joinRequests as $request) {
					if ($request['request_capacity'] ==0) { // Setting is unlimited
						$canCreate = $request['roleid'];
						break;
					}
					if ((intval($request['request_capacity']) -  intval($request['used_capacity']))>0){
						$canCreate = $request['roleid'];
						break;
					}
				}
			} else {
				// Check for guard rails
				foreach($roleTypes as $row){
					$guardRails = json_decode($row['restrictions'],true);
					if (!empty($guardRails)){
						$isRequestAllowd = Team::CheckRequestRoleJoinAllowed($subject_userid, $guardRails);
						
						if ($isRequestAllowd) {
							$canCreate = $row['roleid'];
							break;
						}
					}
				}
			}
		} 
		return $canCreate;
	}

	public static function DiscoverAvailableCircles(int $groupid, int $subject_userid, int $page,int $limit,  array $filter_attribute_keywords, array $filter_primary_attributes, string $name_keyword, int $showAvailableOnly, string $search_str, array $hashtag_ids, array $filter_attribute_type = array())
	{
		global $_COMPANY,$_ZONE;
        $availableTeams = array();
		$showMore = false;
		$max_items = $limit + 1; // We are adding 1 additional row to enable 'Show More to work'
        $start = (($page - 1) * $limit);
        $hashtag_ids_str = implode(',',$hashtag_ids);
		$att_keywords_str = implode(',',$filter_attribute_keywords);
		$prim_att_str = implode(',',$filter_primary_attributes);
        $cache_hash = hash('sha256', "{$att_keywords_str}_{$prim_att_str}_{$name_keyword}_{$showAvailableOnly}_{$search_str}_{$hashtag_ids_str}") ;
		$matchedTeamIds = array();
        $availableTeamIds = null;

        $key = "Team_DiscoverAvailableCircles:{$groupid}:pagination_" . $cache_hash;
        if (($matchedTeamIds = $_COMPANY->getFromRedisCache($key)) === false) {
            $matchedTeamIds = array(); // Reset to initial value

            if ($search_str || $hashtag_ids) {
                $search_results = Typesense::Search($search_str, [
                    'group_id' => $groupid,
                    'type' => TypesenseDocumentType::Team->value,
                    'hashtag_ids' => $hashtag_ids,
                ], 1, 250); // give static $page, $per_page value. Pagigation will be handled on cached data on model side

                $availableTeamIds = array_map(function (array $result) {
                    return Typesense::GetModelId($result['document']['id']);
                }, $search_results['hits']);
            }

			$availableTeamsConditions = "";
			if ($showAvailableOnly){
				$allRoles = Team::GetProgramTeamRoles($groupid, 1);
				$conditions = array();
				foreach ($allRoles as $role) {
					if ($role['sys_team_role_type'] != 2) {
						$conditions[] = "(JSON_EXTRACT(attributes, '$.roles_capacity.\"".$role['roleid']."\".circle_role_max_capacity') > (SELECT COUNT(1) FROM `team_members` WHERE `teamid`=`teams`.teamid AND `roleid`='{$role['roleid']}'))";
					}
				}
				$conditionStrings = "";
				if (!empty($conditions)) {
					$conditionStrings = implode(' OR ', $conditions);
				}
			}
			if (!empty($conditionStrings)) {
				$availableTeamsConditions = " AND ({$conditionStrings})";
			}
            $recs = self::DBROGet("
                    SELECT teamid,roleid,users.companyid,users.userid,firstname,lastname,email,extendedprofile,users.isactive
                    FROM teams 
                        JOIN team_members USING (teamid) 
                        JOIN team_role_type USING (roleid) 
                        JOIN users USING (userid)
                    WHERE teams.companyid={$_COMPANY->id()}
                      AND teams.groupid={$groupid}
                      AND teams.isactive=1 
                      AND users.companyid={$_COMPANY->id()}
                      AND sys_team_role_type=2 
					  {$availableTeamsConditions}
                      ");

            if ((!empty(array_filter($filter_attribute_keywords)) && !empty(array_filter($filter_primary_attributes))) || $name_keyword) {
                foreach ($recs as $rec) {
                    $match_filter = false;
                    $match_name = false;
                    $member = User::Hydrate($rec['userid'], $rec); // Create a partial user object.

                    // Filter by search
                    if (empty(array_filter($filter_attribute_keywords)) || empty(array_filter($filter_primary_attributes))) {
                        $match_filter = true;
                    } else {

						for($x = 0; $x < count($filter_primary_attributes); $x++) {
							$filter_primary_attribute = $filter_primary_attributes[$x];
							$filter_attribute_keyword = $filter_attribute_keywords[$x];
							if (empty($filter_primary_attribute) || empty($filter_attribute_keyword)) {
								continue;
							}
							$catalogKeyValue = UserCatalog::GetCatalogKeynameForUser($filter_primary_attribute, $member);
							if ($filter_attribute_keyword == $catalogKeyValue) {
								$match_filter = true;
							} else {
								$match_filter = false;
								break;
							}
						}
                    }

                    // Filter by name
                    if (empty($name_keyword)) {
                        $match_name = true;
                    } else {
                        if (preg_match("/{$name_keyword}/i", $member->getFullName())) {
                            $match_name = true;
                        }
                    }

                    if ($match_filter && $match_name) {
                        $matchedTeamIds[] = $rec['teamid'];
                    }
                }

            } else {
                $matchedTeamIds = array_column($recs, 'teamid');
            }
			
			// Find unique team ids, sort
            $matchedTeamIds = array_unique($matchedTeamIds);
            sort($matchedTeamIds, SORT_DESC);

            if (!is_null($availableTeamIds)){
                $matchedTeamIds = array_intersect($availableTeamIds, $matchedTeamIds);
            }

            // Save in cache
            if (!empty($matchedTeamIds)) {
                $ttl = $showAvailableOnly ? 60 : 360; // pagination cache 60 seconds for available only and 6 minute cache otherwise
                $_COMPANY->putInRedisCache($key, $matchedTeamIds, $ttl);
            }
        }

        // Cut a slice
		$totalTeamsCount = count($matchedTeamIds);
        $matchedTeamIdSlice = array_slice($matchedTeamIds, $start, $max_items);

        $showMore = (count($matchedTeamIdSlice)> $limit);
        if ($showMore){
            array_pop($matchedTeamIdSlice);
        }

        foreach($matchedTeamIdSlice as $teamid){
            $availableTeams[] = self::GetTeam($teamid);
        }

		return [$availableTeams,$showMore,$totalTeamsCount];
	}

	public function discoverBestUserToMatchAgainst(int $sysRoleId)
	{
		$matchAgainstUserId  = 0;
		$matchAgainstRoleid  = 0;
		$matchAgainstMembers = $this->getTeamMembersBasedOnSysRoleid($sysRoleId);
		foreach($matchAgainstMembers as $m) {
			
			$requestDetail = self::GetRequestDetail($this->val('groupid'),$m['roleid'],$m['userid']);
			if ($requestDetail) {
				$matchAgainstUserId  = $m['userid'];
				$matchAgainstRoleid  = $m['roleid'];
				break;
			}
		}
		return array($matchAgainstUserId,$matchAgainstRoleid);
	}

	public static function GetTeamCustomMetaName(int $programType, int $returnPlural = 0)
	{
		global $_COMPANY,$_ZONE;
		if ($programType == self::TEAM_PROGRAM_TYPE['CIRCLES']) {
			if ($returnPlural){
				return "Circles";
			}
			return "Circle";
		} elseif ($programType == self::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']) {
			return "Development";
		} else{
			if ($returnPlural){
				return $_COMPANY->getAppCustomization()['teams']['name-short-plural'];
			}
			return $_COMPANY->getAppCustomization()['teams']['name-short'];
		}
	}
	
	public function canLeaveCircle(int $teamMemberId)
	{
		global  $_USER;
		$canLeave = false;
		$memberDetail = $this->getTeamMemberById($teamMemberId);
		if ($memberDetail) {
			if ($memberDetail['userid'] == $_USER->id() && $memberDetail['sys_team_role_type'] != 2) {
				$canLeave = true;
			}
		}
		return $canLeave;
	}

	public function isCircleCreator(){
		global  $_USER;
		$isCreator = false;
		$joinedRoles = self::DBGet("SELECT `team_members`.roleid, `team_members`.`userid`, `team_role_type`.sys_team_role_type FROM `team_members` JOIN team_role_type ON `team_role_type`.roleid= `team_members`.roleid WHERE  `team_members`.teamid='{$this->id()}' AND team_members.`userid`='{$_USER->id()}'");

		if (!empty($joinedRoles)){
			foreach($joinedRoles as $joinedRole) {
				if ($joinedRole['userid'] == $_USER->id() && $joinedRole['sys_team_role_type'] == 2) { // Mentor, means he is circle creator
					$isCreator = true;
					break;
				}
			}
		}

		return $isCreator;
	}

	public static function GetCircleRolesCapacity(int $groupid, int $teamid)
	{	
		$rolesCapacity = array();
		if ($teamid) {
			$team = self::GetTeam($teamid);
			if ($team) {
				if (!empty($team->val('attributes'))) {
					$attributes = json_decode($team->val('attributes'),true);
					
					if (array_key_exists(self::TEAM_ATTRIBUTES_KEYS['roles_capacity'],$attributes)) {
						$rolesCapacity = $attributes[self::TEAM_ATTRIBUTES_KEYS['roles_capacity']];
					}
				}
			}
		}
		
		$allRoles = Team::GetProgramTeamRoles($groupid, 1);

		foreach($allRoles as $role) {
			$maxCircleRoleCapacity = 1; // restrict role capacity to only one for Mentor
			if ($role['sys_team_role_type'] != 2){
				if (array_key_exists($role['roleid'], $rolesCapacity)) {
					//$maxCircleRoleCapacity = ($role['max_allowed'] < $rolesCapacity[$role['roleid']]['circle_role_max_capacity'] ? $role['max_allowed'] : $rolesCapacity[$role['roleid']]['circle_role_max_capacity']);
					$maxCircleRoleCapacity = $rolesCapacity[$role['roleid']]['circle_role_max_capacity']?:$role['max_allowed']; // If Team  max capaity is set on Atributes then show it directly else show from role capacity
				} else {
					$maxCircleRoleCapacity = $role['max_allowed'];
				}
			}
			
			$rolesCapacity[$role['roleid']] = array(
                'role_max_capacity'=> $role['max_allowed'],
                'circle_role_max_capacity' => $maxCircleRoleCapacity,
                'roleid' => $role['roleid'],
                'type' => $role['type']
            );
		}
		return $rolesCapacity;
	}

	public function updateCircleMaxRolesCapacity(array $maxRolesCapacity, int $roleid = 0)
	{
		$attributes  = array();
		if (!empty($this->val('attributes'))) {
			$attributes = json_decode($this->val('attributes'),true);
		}

		if (array_key_exists(self::TEAM_ATTRIBUTES_KEYS['roles_capacity'],$attributes) && $roleid > 0) {
			$attributes[self::TEAM_ATTRIBUTES_KEYS['roles_capacity']][$roleid] = $maxRolesCapacity;
		} else {
			$attributes[self::TEAM_ATTRIBUTES_KEYS['roles_capacity']] = $maxRolesCapacity;
		}

		$attributes = json_encode($attributes);

		return self::DBUpdatePS("UPDATE `teams` SET `attributes`=?,`modifiedon`=NOW() WHERE  `teamid`=?", 'xi',$attributes, $this->id());

	}

    public function getTypesenseDocument(): array
    {
		$hashtag_ids = [];
		if ($this->val('handleids')) {
			$hashtag_ids = array_map(function (string $hashtag_id) {
            	return (int) $hashtag_id;
			}, explode(',', $this->val('handleids')));
		}

        return [
            'id' => $this->getTypesenseId(),
            'type' => $this->getTypesenseDocumentType(),
            'company_id' => (int) $this->val('companyid'),
            'zone_id' => (int) $this->val('zoneid'),
            'title' => $this->val('team_name'),
            'description' => Html::SanitizeHtml($this->val('team_description') ?? ''),
            'group_id' => (int) $this->val('groupid'),
            'hashtag_ids' => $hashtag_ids,
        ];
    }

	public function sendTeamModulePushNotifications(int $userid, string $section, int $bedgeCount, string $title, string $bodyMessage)
	{
		global $_COMPANY,$_ZONE;

		if (empty($userid) || ($touser = User::GetUser($userid)) === NULL || !$touser->isActive()){
			return;
		}
		// Push Notification
		if ($touser->val('notification') == 1 && $_COMPANY->getAppCustomization()['mobileapp']['enabled']) {
			$users = self::DBGet("SELECT api_session_id, `userid`, `devicetype`, `devicetoken` FROM users_api_session WHERE `userid`='" . $userid . "' and devicetoken!=''");
			if (count($users) > 0) {
                [$bearerToken, $firebaseProjectId] = $_COMPANY->getFirebaseBearerTokenAndProjectId();
				for ($d = 0; $d < count($users); $d++) {
					sendCommonPushNotification($users[$d]['devicetoken'], htmlspecialchars_decode($title), htmlspecialchars_decode($bodyMessage), $bedgeCount, $section, $this->id(), 1, $_ZONE->id(),$bearerToken,'v1',$firebaseProjectId);
				}
			}
		}
	}

	public function getCommentsLikesSetting()
	{
		global $_COMPANY,$_ZONE;
		$showComments = false;
        $canAddComment = false;
        $canLike = false;
        if ($_COMPANY->getAppCustomization()['teams']['comments']) {
            $showComments = true;
            if ($this->val('isactive') == Team::STATUS_ACTIVE){
                $canAddComment = true;
                $canLike= true;
            }
        }

		return array($showComments, $canAddComment , $canLike);
	}


	public function GetPendingActionItemAndTouchPoints()
	{
		global $_COMPANY,$_ZONE;

		$actionItems = self::DBGet("SELECT COUNT(1) AS pendingActionItems FROM `team_tasks` WHERE `team_tasks`.`companyid`='{$_COMPANY->id()}' AND `team_tasks`.`zoneid`='{$_ZONE->id()}' AND team_tasks.`teamid`='{$this->id}' AND team_tasks.task_type='todo' AND isactive!='52' ");

		$touchPoints = self::DBGet("SELECT COUNT(1) AS pendingTouchPoints FROM `team_tasks` WHERE `team_tasks`.`companyid`='{$_COMPANY->id()}' AND `team_tasks`.`zoneid`='{$_ZONE->id()}' AND team_tasks.`teamid`='{$this->id}' AND team_tasks.task_type='touchpoint' AND isactive!='52' ");

		return array($actionItems[0]['pendingActionItems'],$touchPoints[0]['pendingTouchPoints']);

	}

	/**
     * Returns the invitations sent by the sender_userid
     * @param int $groupid
     * @return array
     */
    public static function GetTeamInvites(int $groupid, int $sender_userid) {
        global $_COMPANY,$_USER;
        return self::DBGet("
                SELECT *
                FROM `team_requests` 
                WHERE `companyid`={$_COMPANY->id()} 
                  AND `groupid`={$groupid}
                  AND `senderid`={$sender_userid}
              ");
    }
	
	/**
     * @param int $groupid
	 * @param int $team_request_id
     * @return int
     */
	public static function CancelTeamRequest(int $groupid,int $team_request_id){
        global $_COMPANY,$_USER;

        $teamJoinRequest = self::GetTeamRequestDetail($groupid, $team_request_id);
        if ($teamJoinRequest) {
            $request_status_withdrawn = Team::TEAM_REQUEST_STATUS['WITHDRAWN'];
            self::DBUpdate("UPDATE `team_requests` SET `status`={$request_status_withdrawn} WHERE `team_request_id`={$team_request_id}");

            // Notify receiver regarding withdrawal of request
            $role = self::GetTeamRoleType($teamJoinRequest['receiver_role_id']);

			self::SendRequestWithdrwalNotificationToInvitedUser($groupid, $teamJoinRequest['senderid'], $teamJoinRequest['receiverid'], $role['type']);
			return 1;
        }  else {
			return 0;
		}
    }

	public function isRoleCapacityAvailableOfMembers(){
		$teamMembers = $this->getTeamMembers(0);
		$isRoleCapacityAvailable = true;
		foreach($teamMembers as $member) {

			if (!self::CanJoinARoleInTeam($this->val('groupid'), $member['userid'], $member['roleid'])) {
				$isRoleCapacityAvailable = false;
				break;
			}
		}	
		return $isRoleCapacityAvailable;
	}

    /**
     * The following was implemented to support issue reported in #3291 point 1
     * Since the Circles create silent member_join_request, we need a cleanup method for member_join_requests for
     * circle types if the used capacity falls down to 0. Delete join requests if group is of circles type and capacity is 0
     * @param int $userid
     * @param int $roleid
     * @return void
     */
	public function deleteEmptyJoinRequests(int $userid, int $roleid)
	{
		global $_COMPANY;
		$program = Group::GetGroup($this->val('groupid'));
		if ($program->getTeamProgramType() ==  self::TEAM_PROGRAM_TYPE['CIRCLES']){ // process only if circle program type
            $member_teams = self::DBGet("SELECT teamid FROM team_members JOIN teams USING (teamid) WHERE teams.groupid={$this->val('groupid')} AND team_members.userid={$userid} AND team_members.roleid={$roleid} LIMIT 1");
			if (empty($member_teams)) { // Delete only if the user is not member of any team with a given role.
                self::DBUpdate("DELETE FROM `member_join_requests` WHERE `companyid`={$_COMPANY->id} AND `groupid`={$this->val('groupid')} AND `userid`={$userid} AND `roleid`={$roleid} AND `used_capacity`=0");
            }
		}
	}

    /**
     * This method creates or updates a join request if applicable. Currently we only allow Automatic Join Requests
     * for Circle Types.
     * @param Group $group
     * @param int $userid
     * @param int $roleid
     * @return array with 'status' set to true|false , 'status_code' values ROLE_UNAVAILABLE|ADDED|UPDATED|empty
     */
    public static function CreateAutomaticJoinRequest(Group $group, int $userid, int $roleid): array
    {
        $retVal = ['status' => true, 'status_code' => ''];

        if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']) { // Init silent registration process of mentor sys role type
            $groupid = $group->id();

            $role = Team::GetTeamRoleType($roleid);
            if (!$role) {
                $retVal['status'] = false;
                $retVal['status_code'] = 'ROLE_UNAVAILABLE';
                return $retVal;
            }

            $status = Team::SaveTeamJoinRequestData($groupid, $roleid, '{}', $role['role_capacity'], false, $userid);
            if ($status > 0) {
                $retVal['status'] = true;
                $retVal['status_code'] = ($status === 2) ? 'ADDED' : 'UPDATED';
            }
        }
        return $retVal;
    }

	public static function CanSendP2PTeamJoinRequest(int $groupid, int $userid, $roleid){
		global $_COMPANY;
        $requestDetail = self::GetRequestDetail($groupid,$roleid,$userid);
        if ($requestDetail) {
			if ($requestDetail['request_capacity'] == 0) { // Unlimited Capacity
				return true;
			}
			if (self::DoesJoinRequestHaveActiveCapacity ($requestDetail)) {
                $pendingSentOrReceivedRequestCount = self::GetPendingSentOrReceivedRequestCount($groupid,$userid,$roleid);
				return (intval($requestDetail['request_capacity']) -  (intval($requestDetail['used_capacity']) + $pendingSentOrReceivedRequestCount)) > 0;
			};

        }
        return false;
	}

    public static function GetPendingSentRequestCount(int $groupid, int $userid, int $roleid=0): int
    {
        global $_COMPANY;
		$roleCondition = "";
		if ($roleid) {
			$roleCondition = " AND `sender_role_id` = {$roleid}";
		}
        return (int)self::DBROGet("SELECT count(1) as totalRequestsSent FROM team_requests WHERE `companyid` = {$_COMPANY->id()} AND `groupid` = {$groupid} AND `senderid` = {$userid} {$roleCondition} AND `status` = 1")[0]['totalRequestsSent'];
    }

    public static function GetPendingReceivedRequestCount(int $groupid, int $userid, int $roleid=0): int
    {
        global $_COMPANY;
		$roleCondition = "";
		if ($roleid) {
			$roleCondition = " AND `receiver_role_id` = {$roleid}";
		}
        return (int)self::DBROGet("SELECT count(1) as totalRequestsReceived FROM team_requests WHERE `companyid` = {$_COMPANY->id()} AND `groupid` = {$groupid} AND `receiverid` = {$userid} {$roleCondition} AND `status` = 1")[0]['totalRequestsReceived'];
    }

    public static function GetPendingSentOrReceivedRequestCount(int $groupid, int $userid, int $roleid): int
    {
        global $_COMPANY;
        $request_status_pending = intval(self::TEAM_REQUEST_STATUS['PENDING']);
        return (int)self::DBROGet("SELECT count(1) as totalPendingRequests FROM team_requests WHERE `companyid`={$_COMPANY->id()} AND `groupid`={$groupid} AND ((`senderid`={$userid} AND `sender_role_id`={$roleid}) OR (`receiverid`={$userid} AND `receiver_role_id`={$roleid})) AND `status`={$request_status_pending}")[0]['totalPendingRequests'];
    }
	/**
	 * CancelAllOutStandngRequests
	 *
	 * @param  int $groupid
	 * @param  int $userid
	 * @param  int $roleid
	 * @return void
	 */
	public static function CancelOutstandingRequestsIfCapacityHasBeenReached(int $groupid, int $userid, int $roleid) {
		global $_COMPANY;

		if (!Team::CanJoinARoleInTeam($groupid,$userid,$roleid)) {
            $request_status_pending = intval(self::TEAM_REQUEST_STATUS['PENDING']);
            $request_status_canceled = intval(self::TEAM_REQUEST_STATUS['CANCELED']);

            // Cancel all outstanding requests for $roleid for given $userid where the userid is a sender or a recipient.
			$outstandingRequests = self::DBGet("SELECT * FROM `team_requests`  WHERE `companyid`='{$_COMPANY->id()}' AND `groupid`='{$groupid}' AND ((`senderid`='{$userid}' AND `sender_role_id`='{$roleid}') OR (`receiverid`='{$userid}' AND `receiver_role_id`='{$roleid}')) AND `status`={$request_status_pending}");

            $role = Team::GetTeamRoleType($roleid);
            // Pending recieved requests
            foreach($outstandingRequests as $request) {
                if (self::DBUpdate("UPDATE `team_requests` SET `status`={$request_status_canceled} WHERE `companyid`='{$_COMPANY->id()}' AND team_request_id={$request['team_request_id']}")) {
                    self::SendCancelOutstandingRequestsEmail($groupid, $userid, $request['senderid'], $request['receiverid'], $role['type']);
                }
            }
		}
	}


	public static function SendCancelOutstandingRequestsEmail(int $groupid, int $userid, int $senderid, int $receiverid, string $roleName) {
		global $_COMPANY, $_ZONE, $_USER;
		$baseurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));
		$sender = User::GetUser($senderid);
		$receiver = User::GetUser($receiverid);
		$group = Group::GetGroup($groupid);

		if ($sender && $receiver /*&& $receiver->isActive() && $sender->isActive()*/) {
			// Remind the receiver

				// SENDER EMAIL
            if ($senderid == $userid) {
                $senderMessageContext = sprintf(/*gettext*/ ('Your outstanding request for the %1$s role to %2$s has been cancelled because you have reached the maximum capacity limit for this role.'), $roleName, $receiver->getFullName());
            } else {
                $senderMessageContext = sprintf(/*gettext*/ ('Your outstanding request for the %1$s role to %2$s has been cancelled because %2$s has reached the maximum capacity limit for this role.'), $receiver->getFullName(), $roleName);
            }
            $requestUrl = $baseurl . 'detail?id=' . $_COMPANY->encodeId($groupid) . '&hash=getMyTeams/getTeamInvites';
            // Process email
            self::ProcessCancelOutstandingRequestsEmail($group, $sender->val('email'), $sender->getFullName(), $roleName,$requestUrl,$senderMessageContext);
				
            // RECEIVER EMAIL
            if ($receiverid == $userid) {
                $reciverMessageContext = sprintf(/*gettext*/('Your outstanding request from %1$s for the %2$s role has been cancelled because you have reached the maximum capacity limit for this role.'),$sender->getFullName(),$roleName);
            } else {
                $reciverMessageContext = sprintf(/*gettext*/('Your outstanding request from %1$s for the %2$s role has been cancelled because %1$s has reached the maximum capacity limit for this role.'),$sender->getFullName(),$roleName);
            }
            $requestUrl = $baseurl . 'detail?id=' . $_COMPANY->encodeId($groupid) . '&hash=getMyTeams/getTeamReceivedRequests';
            // Process email
            self::ProcessCancelOutstandingRequestsEmail($group, $receiver->val('email'), $receiver->getFullName(), $roleName, $requestUrl,$reciverMessageContext);
		}
	}

	public static function ProcessCancelOutstandingRequestsEmail(Group $group, string $subjectUserEmail, string $subjectUserFullname, string $roleName, string $requestUrl, string $messageContext) {
		global $_COMPANY, $_ZONE;

        $groupName = $group->val('groupname');
        $from = $group->val('from_email_label');
        $reply_addr = $group->val('replyto_email');
        $groupCustomName = $_COMPANY->getAppCustomization()['group']['name-short'];
        $app_type = $_ZONE->val('app_type');

		$temp = EmailHelper::EmailTemplateCancelOutstandingRequestsEmail($subjectUserFullname, $roleName, $requestUrl, $groupCustomName, $groupName, $messageContext);

		$_COMPANY->emailSend2($from, $subjectUserEmail, $temp['subject'], $temp['message'], $app_type, $reply_addr);

	}


	public static function SendRequestWithdrwalNotificationToInvitedUser(int $groupId, int $senderId, int $receiverId, string $roleType) {
		global $_COMPANY, $_ZONE;
		$app_type = $_ZONE->val('app_type');
		$baseurl = $_COMPANY->getAppURL($app_type);
		$requestSenderUser = User::GetUser($senderId);
		$requestReceiverUser = User::GetUser($receiverId);
		$group = Group::GetGroup($groupId);

		if ($requestSenderUser && $requestSenderUser->isActive() && $requestReceiverUser && $requestReceiverUser->isActive()) {
			// Remind the receiver
			$groupName = $group->val('groupname');
            $from = $group->val('from_email_label');
            $reply_addr = $group->val('replyto_email');
			$teamCustomName = self::GetTeamCustomMetaName($group->getTeamProgramType());
        	$groupCustomName = $_COMPANY->getAppCustomization()['group']['name-short'];
			$requestUrl = $baseurl . 'detail?id=' . $_COMPANY->encodeId($groupId) . '&hash=getMyTeams/initDiscoverTeamMembers';
			$temp = EmailHelper::EmailTemplateWithdrwalNotificationToInvitedUser($requestSenderUser->getFullName(),$requestReceiverUser->getFullName(), $roleType,$requestUrl,$groupCustomName, $groupName, $teamCustomName);

			$_COMPANY->emailSend2($from, $requestReceiverUser->val('email'), $temp['subject'], $temp['message'], $app_type, $reply_addr);
		}
	}
	
	/**
	 * sendLeaveCircleNotificationToMentor
	 *
	 * @param  Group $group
	 * @param  array $mentorData
	 * @return void
	 */
	public function sendLeaveCircleNotificationToMentor (Group $group, array $mentorData)
	{
		global $_COMPANY, $_ZONE, $_USER;
		$role = Team::GetTeamRoleType($mentorData['roleid']);
		$app_type = $_ZONE->val('app_type');
		$reply_addr = $group->val('replyto_email');
		$from = $group->val('from_email_label') . sprintf(gettext('%s  Left'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));

		$mentorEmail = $mentorData['email'];
		$mentorName = $mentorData['firstname'] . ' ' . $mentorData['lastname'];
		$menteeName = $_USER->getFullName();
		$teamMetaName = $_COMPANY->getAppCustomization()['teams']['name'];
		$groupMetaName = $_COMPANY->getAppCustomization()['group']['name'];
		$temp = EmailHelper::LeaveCircleNotificationToMentorTemplate($groupMetaName, $teamMetaName, $this->val('team_name'), $role['type'], $mentorName, $menteeName, $group->val('groupname'),  date("Y-m-d"));

		$_COMPANY->emailSend2($from, $mentorEmail, $temp['subject'], $temp['message'], $app_type,$reply_addr);  
	}

    /**
     * Generates list of team members in a table form, great for embedding into emails.
     */
    public function getTeamMembersAsHtmlTable(array $teamMembers): string
    {
        #
        # Note we are embedding table inside table for proper formatting ... DO NOT CHANGE IT
        #
        $message = '';
        $message .= '<table cellpadding="0" cellspacing="0" border="0" width="100%" class="block" style="width: 100%;">';
        $message .= '   <tr>';
        $message .= '    <td align="left" valign="top" style="vertical-align: top; line-height: 1; padding: 5px 0px 0px 0px;">';
        $message .= '        <table cellpadding="5" cellspacing="0" width="100%" align="left" border="0" style="width: 100%;">';
        $message .= '            <tr style="background-color: #dedede;"><th style="padding: 5px;text-align: left;">Name</th><th style="padding: 5px;text-align: left;">Email</th><th style="padding: 5px;text-align: left;">Role</th></tr>';
        $bg_color = '';
        foreach ($teamMembers as $member) {
            $bg_color = ($bg_color == '#fefefe') ? '#efefef' : '#fefefe';
            $member_role = $member['role'];
            if (!empty(trim($member['roletitle']))) {
                $member_role .= ' - ' . htmlspecialchars(trim($member['roletitle']));
            }
            $message .= "        <tr style='background-color: {$bg_color};'><td style='padding: 5px;text-align: left;'>{$member['firstname']} {$member['lastname']}</td><td style='padding: 5px;text-align: left;'>{$member['email']}</td><td style='padding: 5px;text-align: left;'>{$member_role}</td></tr>";
        }
        $message .= '        </table>';
        $message .= '    </td>';
        $message .= '   </tr>';
        $message .= '</table>';
        return $message;
    }

    public static function GetRoleCapacityValues(int $groupid, int $subjectRoleId, int $subjectUserid): array
    {
        $roleRequest = Team::GetRequestDetail($groupid, $subjectRoleId, $subjectUserid);
        $roleRequestBuffer = intval($roleRequest['role_request_buffer'] ?? 0);
        $roleSetCapacity = intval($roleRequest['request_capacity'] ?? 0);
        $roleUsedCapacity = intval($roleRequest['used_capacity'] ?? 0);
        $roleAvailableCapacity = $roleSetCapacity - $roleUsedCapacity;
        $pendingSentOrReceivedRequestCount = Team::GetPendingSentOrReceivedRequestCount($groupid, $subjectUserid, $subjectRoleId);
        $roleAvailableRequestCapacity = $roleSetCapacity - ($roleUsedCapacity + $pendingSentOrReceivedRequestCount);
        $roleAvailableBufferedRequestCapacity = ($roleRequestBuffer +$roleSetCapacity) - ($roleUsedCapacity + $pendingSentOrReceivedRequestCount);

        return array(
            $roleSetCapacity,
            $roleUsedCapacity,
            $roleRequestBuffer,
            $roleAvailableCapacity,
            $roleAvailableRequestCapacity,
            $roleAvailableBufferedRequestCapacity,
            $pendingSentOrReceivedRequestCount
        );
    }

	public function getContentsProgressStats(Group $group, int $subjectUserId,string $type){
		global $_COMPANY, $_ZONE, $_USER;

		if (!in_array($type, array('todo', 'touchpoint'))) {
			return array(0,0,0,0);
		}
		$contentIdsCondtion = '';
		if ($type == 'todo' && $subjectUserId){
			$actionItemConfig = $group->getActionGonfiguration();
			$todolist = $this->getTeamsTodoList();
			$filteredIds = array();

			foreach($todolist as $todo) {
				if ($actionItemConfig['action_item_visibility'] == Group::ACTION_ITEM_VISIBILITY_SETTING['show_to_assignee_and_mentors']) {
					$memberDetail = $this->getTeamMembershipDetailByUserId($_USER->id());
					if ($todo['assignedto'] != $_USER->id() && $memberDetail['sys_team_role_type'] != 2){
					  continue;
					}
				}
				$filteredIds[] = $todo['taskid'];
			}
			if (!empty($filteredIds)) {
				$filteredIds = implode(',',$filteredIds);
				$contentIdsCondtion = " AND taskid IN({$filteredIds})";
			}
		}
		
		$userContition = "";
		if ($subjectUserId) {
			$userContition = " AND assignedto = {$subjectUserId}";
		}

		$taskTypeContion = "";
		if ($type == 'todo') {
			$taskTypeContion = " AND task_type ='{$type}'";
		} elseif($type == 'touchpoint'){
			$taskTypeContion = " AND task_type ='{$type}'";
		}

		$stats =  self::DBROGet("SELECT
								(COUNT(1)) AS totalData,
								IFNULL((SUM(CASE WHEN isactive = 51 THEN 1 ELSE 0 END) * 100 / COUNT(1)),0) AS inProgressPercentage,
								IFNULL((SUM(CASE WHEN isactive = 52 THEN 1 ELSE 0 END) * 100 / COUNT(1)),0) AS completedPercentage,
								IFNULL((SUM(duedate < NOW() AND (isactive = 1 OR isactive = 51)) / COUNT(1) * 100),0) AS overduePercentage
							FROM team_tasks
							WHERE companyid = {$_COMPANY->id()} AND zoneid = {$_ZONE->id()} AND teamid = {$this->id()} {$contentIdsCondtion} {$taskTypeContion} {$userContition}");
		return array($stats[0]['totalData'],round($stats[0]['inProgressPercentage'],2),round($stats[0]['completedPercentage'],2),round($stats[0]['overduePercentage'],2));

	}

	public static function GetSurveyResponsesMatchedUsers (array $searchCustomParameters, array $searchResponsesToMatch, array $surveyRespondersData) {
		$usersByRoleRequestWithSurveyResponses = array();
		foreach ($surveyRespondersData as $oppositeJoinRequest){
			$role_survey_response = json_decode($oppositeJoinRequest['role_survey_response'],true);
			$usersByRoleRequestWithSurveyResponses[$oppositeJoinRequest['userid']] = $role_survey_response;
		}
		$matchedUserids = array();
		foreach($searchCustomParameters as $key => $value){
			if ($value >0) {
				$userCatalogKeyName = UserCatalog::GetSurveyResponseCatalog($key, $value,$searchResponsesToMatch, $usersByRoleRequestWithSurveyResponses);
				$matchedUserids[] = $userCatalogKeyName->getUserIds();
			}
		}
		return $matchedUserids;
	}
	
	public static function ExtendRoleRequestedCapacity(int $groupid, int $userid, int $roleid)
	{
		global $_COMPANY;
		$requestDetail = self::GetRequestDetail($groupid,$roleid,$userid);
		if ($requestDetail && $requestDetail['request_capacity'] == 0) { // request_capacity means unlimited set
			return 1;
		}
		return self::DBUpdate("UPDATE `member_join_requests` SET `request_capacity`=`request_capacity`+1,`modifiedon`=NOW() WHERE `companyid`='{$_COMPANY->id()}' AND `roleid`='{$roleid}' AND `userid`='{$userid}' AND `groupid`='{$groupid}'");
	}

	public static function GetRoleOptionsOnLeaveMemberTermination(int $groupid) {
		global $_COMPANY;
		if (!$groupid) {
			return array();
		}
 		$group = Group::GetGroup($groupid);
		$teamCustomName = Team::GetTeamCustomMetaName($group->getTeamProgramType());

		return array (
			'close_as_incomplete' => sprintf(gettext('Close the %s as Incomplete'),$teamCustomName),
			'leave_as_is' => sprintf(gettext('Leave the %s as is'),$teamCustomName)
		);
	}

	public function handleTeamMemberTermination(int $subjectUserid, int $subjectRoleid)
    {	
		global $_COMPANY,$_ZONE;

		$group = Group::GetGroup($this->val('groupid'));

		if ($group->getTeamProgramType() == self::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']) { // Skip email for INDIVIDUAL_DEV
			return;
		}
		$teamMembers = $this->getTeamMembers(0);
		if (!empty($teamMembers)) {
			
			$teamRole = self::GetTeamRoleType($subjectRoleid);
			list ($subject, $message) = EmailHelper::EmailTemplateForOnTeamMemberTermination($group, $teamRole);

			$subjectUser = User::GetUser($subjectUserid);
			$subject_role_and_title = $teamRole['type'];
			foreach($teamMembers as $member){

				if ($member['userid'] == $subjectUserid) {
					continue;
				}
				
				if ($group->getTeamProgramType() != self::TEAM_PROGRAM_TYPE['NETWORKING'] && !in_array($member['sys_team_role_type'],array(2,3))) {
					continue;
				}

				$manager_user = $subjectUser->getUserHeirarcyManager();

                $template_keywords = array(
                    '[[PERSON_FIRST_NAME]]', '[[PERSON_LAST_NAME]]', '[[TEAM_NAME]]',
                    '[[TEAM_MEMBER_WHO_LEFT]]', '[[TEAM_MEMBER_WHO_LEFT_ROLE]]',
                    '[[PERSON_JOB_TITLE]]',
                    //'[[PERSON_START_DATE]]',
					'[[MANAGER_FIRST_NAME]]',
					'[[MANAGER_LAST_NAME]]',
					'[[MANAGER_EMAIL]]',
                );

                $replacement_words = array(
                    $member['firstname'], $member['lastname'], $this->val('team_name'),
                    $subjectUser->getFullName(), $subject_role_and_title,
                    $member['jobtitle'],
                    //'',
					$manager_user?->val('firstname') ?? '',
					$manager_user?->val('lastname') ?? '',
					$manager_user?->getEmailForDisplay() ?? '',
                );

				$subject = str_replace($template_keywords, $replacement_words, $subject);
				$emesg = str_replace($template_keywords, $replacement_words, $message);

				$template = $_COMPANY->getEmailTemplateForNonMemberEmails();
				$emesg = str_replace('#messagehere#', $emesg, $template);
				$_COMPANY->emailSend2($group->val('from_email_label'), $member['email'], $subject, $emesg, $_ZONE->val('app_type'), $group->val('replyto_email'));
			}

			if ($teamRole['action_on_member_termination'] == 'close_as_incomplete') {
				$this->incomplete();
			}
		}
    }


	public static function HandleUserTerminations(int $terminated_userid)
	{
		global $_COMPANY, $_ZONE;

		$terminatedUserTeamMemberships = self::DBROGet("SELECT team_members.* FROM `team_members` JOIN teams ON teams.teamid=team_members.teamid AND teams.companyid={$_COMPANY->id()} WHERE team_members.userid={$terminated_userid} AND teams.isactive=1");

		foreach($terminatedUserTeamMemberships as $terminatedUserTeamMembership) {
			$role = self::GetTeamRoleType($terminatedUserTeamMembership['roleid']);
			if (!$role['email_on_member_termination']) {
				continue;
			}
			$team = self::GetTeam($terminatedUserTeamMembership['teamid'], true);
			if ($team){
                // *** *** *** *** ***
                // Note here we are setting $_ZONE to the zone of the team to allow emails to work correctly
                // since this method is being used across zone to handle terminations
                // *** *** *** *** ***
                $original_zone = $_ZONE;
                $_ZONE = $_COMPANY->getZone($team->val('zoneid'));
                $team->handleTeamMemberTermination($terminatedUserTeamMembership['userid'], $terminatedUserTeamMembership['roleid']);
                $_ZONE = $original_zone;
                // *** *** *** *** ***
                // In the end we reset the $_ZONE back to original value
                // *** *** *** *** ***
			}
		}
	}

	public function getTeamMembershipDetailByUserId(int $userid) {
		$member =  self::DBGet("SELECT team_members.*,IFNULL(team_role_type.`type`,'Other') as `role`, team_role_type.sys_team_role_type  FROM `team_members` LEFT JOIN team_role_type ON team_role_type.roleid= team_members.roleid WHERE team_members.`teamid`='{$this->id}' AND team_members.userid='{$userid}' ");

		if (!empty($member)) {
			return $member[0];
		}
		return null;
	}

    /**
     * This function checks if there are any group level constraints getting violated. The function is memoized and
     * thus can be used in the loops.
     * @param int $groupid
     * @param int $roleid
     * @return bool
     */
	public static function IsTeamRoleRequestAllowed(int $groupid, int $roleid): bool
    {
        $memoize_key = __METHOD__ . ':' . serialize(func_get_args());

        if (!isset(self::$memoize_cache[$memoize_key])) {
            self::$memoize_cache[$memoize_key] = true;
            $role = self::GetTeamRoleType($roleid);
            if ($role && !empty($role['maximum_registrations'])) {
                // Using a direct query for optimization. Also not using companyid as groupid and roleid are being used and we are just getting a count
                $totalRoleJoinRequests = self::DBROGet("SELECT count(1) as number_registered FROM member_join_requests WHERE groupid={$groupid} AND roleid={$roleid}")[0]['number_registered'];
                if ($totalRoleJoinRequests >= $role['maximum_registrations']) {
                    self::$memoize_cache[$memoize_key] = false;
                }
            }
        }
        return self::$memoize_cache[$memoize_key];
	}

	public static function AutoCompleteTeamAfterNDays(): bool
    {
        global $_COMPANY, $_ZONE;
        if ($_COMPANY || $_ZONE) {
            Logger::Log("Calling multi-tenant method from Company or Zone context:" . $_COMPANY->id(), Logger::SEVERITY['FATAL_ERROR']);
            return false; // This job can only be executed if it is called from non company and non zone context. If company is set, exit it.
        }

        $utc_timezone = new DateTimeZone('UTC');

		$companies = self::DBROGet("SELECT `companyid` FROM `companies` WHERE companies.isactive=1");
        // Step 1: Iterate over active companies and zones
        foreach ($companies as $company) {
            $now_utc_datetime_obj = new DateTime('now', $utc_timezone);
			$_COMPANY = Company::GetCompany($company['companyid']);
            if (!$_COMPANY) {
                continue;
            }
            // Step 2: Iterate over active Zones
            foreach ($_COMPANY->getZones() as $zonesArr) {

                if ($zonesArr['isactive'] != 1 ||
                    ($_ZONE = $_COMPANY->getZone($zonesArr['zoneid'])) == null ||
                    !$_COMPANY->getAppCustomization()['teams']['enabled']
                ) {
                    continue; // Skip inactive zones;
                }

                // Step 3: Iterate over active groups
                $programs = self::DBROGet("SELECT `groupid` FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `isactive`=1");
                foreach($programs as $program) {
                    $programObj = Group::GetGroup($program['groupid']);

                    // We will process the program only if team module enabled
                    if (!$programObj->isTeamsModuleEnabled()) {
                        continue;
                    }

                    $programSetting = $programObj->getTeamWorkflowSetting();
					$automatically_close_team_after_n_days = (int)$programSetting['automatically_close_team_after_n_days'] ?? 0;

                    if (!$automatically_close_team_after_n_days) {
                        // If not set then skip
                        continue;
                    }

                    // Step 4: Iterate over active teams
                    $teams = self::DBROGet("SELECT `teamid`,`team_start_date` FROM `teams` WHERE `companyid`='{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' AND `groupid`='{$program['groupid']}'  AND `isactive`=1 AND team_start_date + INTERVAL {$automatically_close_team_after_n_days} DAY < NOW()");
                    foreach ($teams as $team) {
                        //Set job
                        $job = new TeamJob($program['groupid'], $team['teamid']);
                        $job->saveAsAutoComplete_afterNDaysType($automatically_close_team_after_n_days, $team['team_start_date']);
                    }
                    $teams = null;
                }
                $programs = null;
                $_ZONE = null;
            }
            $_ZONE = null;
            $_COMPANY = null;
        }
		$_COMPANY = null;
		$_ZONE = null;
		return true;
	}

    public static function AutoCompleteTeamOnMenteeStartDate(): bool
    {
        global $_COMPANY, $_ZONE;
        if ($_COMPANY || $_ZONE) {
            Logger::Log("Calling multi-tenant method from Company or Zone context:" . $_COMPANY->id(), Logger::SEVERITY['FATAL_ERROR']);
            return false; // This job can only be executed if it is called from non company and non zone context. If company is set, exit it.
        }

        $utc_timezone = new DateTimeZone('UTC');

        $companies = self::DBROGet("SELECT `companyid` FROM `companies` WHERE companies.isactive=1");
        // Step 1: Iterate over active companies and zones
        foreach ($companies as $company) {
            $_COMPANY = Company::GetCompany($company['companyid']);
            if (!$_COMPANY) {
                continue;
            }
            // Step 2: Iterate over active Zones
            foreach ($_COMPANY->getZones() as $zonesArr) {

                if ($zonesArr['isactive'] != 1 ||
                    ($_ZONE = $_COMPANY->getZone($zonesArr['zoneid'])) == null ||
                    !$_COMPANY->getAppCustomization()['teams']['enabled'] ||
                    ($_ZONE->val('app_type') !== 'peoplehero')
                ) {
                    continue; // Skip inactive zones and only run for peoplehero zones;
                }

                // Step 3: Iterate over active groups
                $programs = self::DBROGet("SELECT `groupid` FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `isactive`=1");
                foreach($programs as $program) {
                    $programObj = Group::GetGroup($program['groupid']);

                    // We will process the program only if team module enabled
                    if (!$programObj->isTeamsModuleEnabled()) {
                        continue;
                    }

                    $programSetting = $programObj->getTeamWorkflowSetting();
                    $auto_complete_team_ndays_after_mentee_start_date = (int) ($programSetting['auto_complete_team_ndays_after_mentee_start_date'] ?? 0);

                    if (!$auto_complete_team_ndays_after_mentee_start_date) {
                        // If not set then skip
                        continue;
                    }

                    $min_allowed_mentee_start_date =
                        (new DateTime("-{$auto_complete_team_ndays_after_mentee_start_date}days", $utc_timezone))
                        ->format('Y-m-d');

                    // Step 4: Iterate over active teams
                    // Get all active teams that have mentee's (role-type = 3) and mentee's start date is less than now
                    // Close such teams
                    $teams = self::DBROGet("
                        SELECT      `teams`.`teamid`,
                                    group_concat(`users`.`userid`) as `mentee_userids`,
                                    group_concat(`users`.`employee_start_date`) as `mentee_start_dates`
                        FROM        `teams`
                        INNER JOIN  `team_members` ON `teams`.`teamid` = `team_members`.`teamid`
                        INNER JOIN  `team_role_type` ON `team_members`.`roleid` = `team_role_type`.`roleid`
                        INNER JOIN  `users` ON `team_members`.`userid` = `users`.`userid`
                        WHERE       `teams`.`companyid` = {$_COMPANY->id()}
                        AND         `teams`.`zoneid` = {$_ZONE->id()}
                        AND         `teams`.`groupid` = {$program['groupid']}
                        AND         `teams`.`isactive` = 1
                        AND         `team_role_type`.`sys_team_role_type` = 3
                        AND         `team_role_type`.`isactive` = 1
                        AND         `users`.`employee_start_date` IS NOT NULL
                        AND         `users`.`employee_start_date` <= '{$min_allowed_mentee_start_date}'
                        AND         `users`.`isactive` = 1
                        GROUP BY    `teams`.`teamid`
                    ");
                    foreach ($teams as $team) {
                        //Set job
                        $job = new TeamJob($program['groupid'], $team['teamid']);
                        $job->saveAsAutoCompleteTeamOnMenteeStartDateType($auto_complete_team_ndays_after_mentee_start_date, $team['mentee_userids'], $team['mentee_start_dates']);
                    }
                    $teams = null;
                }
                $programs = null;
                $_ZONE = null;
            }
            $_ZONE = null;
            $_COMPANY = null;
        }
        $_COMPANY = null;
        $_ZONE = null;
        return true;
    }
}
