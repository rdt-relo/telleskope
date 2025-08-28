<?php

// Do no use require_once as this class is included in Company.php.

class Group extends Teleskope {


    // Due to the database dependencies, do not change the list below. You can add to the list as needed.
    const SYS_GROUPLEAD_TYPES = array(
        1 => "Executive Sponsor",
        2 => "Global Leader",
        3 => "Regional Leader",
        4 => "Chapter Leader",
        5 => "Channel Leader",
        0 => "Other",
    );

    // Due to the database dependencies, do not change the list below. You can add to the list as needed.
    const GROUP_TYPE_OPEN_MEMBERSHIP = 0;
    const GROUP_TYPE_INVITATION_ONLY = 10;
    const GROUP_TYPE_REQUEST_TO_JOIN = 30;
    const GROUP_TYPE_MEMBERSHIP_DISABLED = 50;
    const GROUP_TYPE_LABELS = array(
        0 => "Open Membership",
        10 => "Invitation Only",
        30 => "Membership by Request Only",
        50 => "Membership Disabled"
    );

    const GROUP_CONTENT_RESTRICTIONS = array (
        'anyone_can_view' => 'anyone_can_view',
        'members_only_can_view' => 'members_only_can_view',
    );

    const GROUP_COMMUNICATION_TRIGGERS = array(
        'GROUP_JOIN' => 1, // Also works for ZONE JOIN which is groupid=0
        'GROUP_LEAVE' => 2, // Also works for ZONE LEAVE which is groupid=0

        'GROUP_ANNIVERSARY' => 3,

        'GROUP_ANNIVERSARY_BEFORE_THIRTY' => 4,
        'GROUP_ANNIVERSARY_BEFORE_SIXTY' => 5,
        'GROUP_ANNIVERSARY_BEFORE_FORTYFIVE' => 6,
        'GROUP_ANNIVERSARY_BEFORE_NINETY' => 7,
        'GROUP_ANNIVERSARY_BEFORE_SEVEN' => 8,
        'GROUP_ANNIVERSARY_BEFORE_FOURTEEN' => 9,
        // If adding less than 90 days ... you will need to update GetGroupMembersWithAnniversaryDates function

        'GROUP_ANNIVERSARY_AFTER_THIRTY' => 14,
        'GROUP_ANNIVERSARY_AFTER_SIXTY' => 15,
        'GROUP_ANNIVERSARY_AFTER_FORTYFIVE' => 16,
        'GROUP_ANNIVERSARY_AFTER_NINETY' => 17,
        'GROUP_ANNIVERSARY_AFTER_SEVEN' => 18,
        'GROUP_ANNIVERSARY_AFTER_FOURTEEN' => 19,
        // If adding more than 90 days ... you will need to update GetGroupMembersWithAnniversaryDates function

        'USER_REMOVED' => 120,
        'USER_LOCK' => 121,
        'USER_MARK_DELETE' => 122,
        'USER_MARK_DEEP_DELETE' => 123,
    );

    const GROUP_COMMUNICATION_ANNIVERSARSY_TRIGGER_TO_INTERVAL_DAY_MAP = array(
            Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_BEFORE_NINETY'] => -90,
            Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_BEFORE_SIXTY'] => -60,
            Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_BEFORE_FORTYFIVE'] => -45,
            Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_BEFORE_THIRTY'] => -30,
            Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_BEFORE_FOURTEEN'] => -14,
            Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_BEFORE_SEVEN'] => -7,
            Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY'] => 0,
            Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_AFTER_SEVEN'] => 7,
            Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_AFTER_FOURTEEN'] => 14,
            Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_AFTER_THIRTY'] => 30,
            Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_AFTER_FORTYFIVE'] => 45,
            Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_AFTER_SIXTY'] => 60,
            Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_AFTER_NINETY'] => 90,
    );

    // Group Resources placeholders
    const GROUP_RESOURCE_PLACEHOLDERS = array(
        'png' => '../image/resources/png.png',
        'jpg' => '../image/resources/jpg.png',
        'jpeg' => '../image/resources/jpg.png',
        'xls' => '../image/resources/xls.png',
        'xlsx' => '../image/resources/xls.png',
        'doc' => '../image/resources/doc.png',
        'docx' => '../image/resources/doc.png',
        'ppt' => '../image/resources/ppt.png',
        'pptx' => '../image/resources/ppt.png',
        'pdf' => '../image/resources/pdf.png',
        'link' => '../image/resources/link.png',
        'folder' => '../image/resources/folder.png',
        'empty' => '../image/resources/empty.png'
    );


    const LEADS_PERMISSION_TYPES = array(
        'MANAGE'=> 'allow_manage',
        'MANAGE_BUDGET' => 'allow_manage_budget',
        'PUBLISH_CONTENT' => 'allow_publish_content',
        'CREATE_CONTENT' => 'allow_create_content',
        'MANAGE_SUPPORT' => 'allow_manage_support'
    );

    const GROUP_JOIN_REQUEST_STATUS = array (
        'INACTIVE' => 0,
        'ACTIVE' => 1,
        'PAUSED' => 2,
    );

    const MATCHING_ATTRIBUTES_VISIBILITY_KEYS = array(
        'show_matchp_users'=>'show_matchp_users',
        'show_value_users'=>'show_value_users',
        'show_matchp_leaders'=>'show_matchp_leaders',
        'show_value_leaders' => 'show_value_leaders'
    );

    const MATCHING_ATTRIBUTES_VISIBILITY_KEYS_DEFAULT_VALUE = [
        'show_matchp_users' => 'show',
        'show_value_users' => 'hide',
        'show_matchp_leaders' => 'show',
        'show_value_leaders' => 'show',
    ];

    protected $chapterList;
    protected $chapterCount = null;
    protected $channelCount = null;
    protected $memberCount = null;


	protected function __construct($id,$cid,$fields) {
        $chapterList = NULL;
        parent::__construct($id, $cid, $fields);
        //declaring it protected so that no one can create it outside this class.
    }

    /**
     * Returns `groups`in a company for a given Group status
     * @param int $companyid , company id for which to fetch the groups.
     * @param int $zoneid zoneid
     * @return array, a list of `groups`in the company. All `groups`are returned.
     */
    public static function GetAllGroupsByCompanyid (int $companyid, int $zoneid, bool $fetchActiveOnly = false, int $group_category_id = 0) {
        $obj = array();
        $status_filter = '';
        if ($fetchActiveOnly) {
            $status_filter = 'AND `isactive`=1';
        }
        $group_category_filter = '';
        if($group_category_id){
            $group_category_filter = " AND `categoryid` = {$group_category_id} ";
        }
        if (!empty($companyid)) {
            $vals = self::DBGet("SELECT `groupid`,`priority` FROM `groups` WHERE `companyid`='{$companyid}' AND zoneid='{$zoneid}' {$status_filter} {$group_category_filter} ");

            if (!empty($vals)) {
                $priority = explode(',',$vals[0]['priority']);
                if (!empty($priority)) {
                    usort($vals, function ($a, $b) use ($priority) {
                        $pos_a = array_search($a['groupid'], $priority);
                        $pos_b = array_search($b['groupid'], $priority);
                        return $pos_a - $pos_b;
                    });
                }
            }
            foreach ($vals as $val) {
                $obj[] = self::GetGroup($val['groupid']);
            }
        }
        return $obj;
    }

    /**
     * This is trivial function, it has been memoized to allow it to be called as many times with little penality
     * Loads the Group. Note a shallow load is performed, i.e. without loading the text fields.
     * If you need all the fields of the group, the call the member function ->fullyLoad on the group variable
     * @param int $id , group id to be loaded. Group must belong to the company set in $_COMPANY global
     * @return Group|null
     */
    public static function GetGroup(int $id, bool $fromMaster = false) {
        $obj = null;
        global $_COMPANY; /* @var Company $_COMPANY */

        $memoize_key = __METHOD__ . ':' . serialize(func_get_args());

        if ($fromMaster) {
            unset(self::$memoize_cache[$memoize_key]);
        }

        if (isset(self::$memoize_cache[$memoize_key]))
            return self::$memoize_cache[$memoize_key];

        if ($id) {
            $key = "GRP:{$id}";
            if (($obj = $_COMPANY->getFromRedisCache($key)) === false || $fromMaster) {
                $obj = null; // Reset $obj to initial value
                $r1 = self::DBGet("SELECT * FROM `groups` WHERE `groupid`='{$id}' AND `companyid`='{$_COMPANY->id()}'");
                if (!empty($r1)) {
                    $obj = new Group($id, $_COMPANY->id(), $r1[0]);
                    $_COMPANY->putInRedisCache($key, $obj, 86400);
                }
            }
        } else {
            $obj = new Group($id, $_COMPANY->id(), ['groupid' => 0, 'companyid' => $_COMPANY->id(), 'zoneid' => 0, 'regionid' => '', 'group_type' => Group::GROUP_TYPE_OPEN_MEMBERSHIP, 'group_category' => 0, 'addedby' => 0, 'groupname_short' => $_COMPANY->getAppCustomization()['group']['groupname0'], 'groupname' => $_COMPANY->getAppCustomization()['group']['groupname0'], 'abouttitle' => '', 'aboutgroup' => '', 'about_show_members' => 0, 'coverphoto' => '', 'sliderphoto' => '', 'overlaycolor' => $_COMPANY->getAppCustomization()['group']['group0_color'], 'overlaycolor2' => $_COMPANY->getAppCustomization()['group']['group0_color2'], 'groupicon' => '', 'permatag' => '', 'priority' => '', 'addedon' => '', 'from_email_label' => '', 'replyto_email' => '', 'show_overlay_logo' => '', 'modifiedon' => '', 'isactive' => '1']);
        }

        self::$memoize_cache[$memoize_key] = $obj;

        return self::$memoize_cache[$memoize_key];
    }

    public static function GetGroupIdByPermatag(string $permatag) : int
    {

        global $_COMPANY;
        $row = self::DBROGetPS("SELECT groupid FROM `groups` WHERE companyid=? AND permatag=?", 'is', $_COMPANY->id(), $permatag);
        return empty($row) ? 0 : intval($row[0]['groupid']);
    }

	/**
	 * GetGroups
	 *
     * This function retrieves the list of Group objects based on the default order determined by the first argument of groupids.
     * The third argument can be used to override the default sorting order. If the third argument is set to true (the default),
     * the groups are ordered by the groupids provided in the first argument. If set to false, the groups are ordered by
     * group name in ascending order.
     *
	 * @param  array $groupids
	 * @param  bool $activeOnly
	 * @param  bool $maintainDefinedPriority set it false if want to sort groups by Groupname ASC
	 * @return array
	 */
	public static function GetGroups (array $groupids, bool $activeOnly =  true, bool $maintainDefinedPriority = true) {
        $groups = array();
        foreach ($groupids as $id) {
            if ($g = self::GetGroup($id)) {
                if ($activeOnly && !$g->isActive()){
                    continue;
                }
                if ($maintainDefinedPriority) { // By defined priority
                    $groups[] = $g;
                } else {
                    $groups[urlencode(strtolower($g->val('groupname')))] = $g;
                }
            }
        }
        if (!$maintainDefinedPriority){
            ksort($groups);
            $groups = array_values($groups);
        }
        return $groups;
    }

    /**
     * @param int $branchid
     * @param int $zoneid
     * @return int matching groupid
     */
	public static function GetGroupIdForBranchId (int $branchid, int $zoneid) {
        $retVal = 0;
	    global $_COMPANY; /* @var Company $_COMPANY */

        if ($branchid) {
            $r1 = self::DBROGet("SELECT `groups`.groupid FROM `groups` LEFT JOIN chapters using (groupid) WHERE `groups`.companyid={$_COMPANY->id()} AND `groups`.zoneid={$zoneid} AND `groups`.isactive=1 AND FIND_IN_SET({$branchid},chapters.branchids)");

            if (count($r1)) {
                $retVal = (int)$r1[0]['groupid'];
            }
        }
        return $retVal;
    }

    // Utility function to convert DBRec to Group
    public static function ConvertDBRecToGroup ($rec) {
        global $_COMPANY;
        $obj = null;
        $g = (int)$rec['groupid'];
        $c = (int)$rec['companyid'];
        if ($g && $c && $c === $_COMPANY->id())
            $obj = new Group($g, $c, $rec);
        return $obj;
    }

    //Get group name by region ids
    public static function GetGroupnamesByRegionIds (string $regionIds) : array
    {
        global $_COMPANY,$_ZONE;
        $regionIds = explode(',',$regionIds);
        $groupNames = array();
        foreach ($regionIds as $regionId) {
            $regionId = (int)$regionId;
            if ($regionId) {
                $rows = self::DBROGet("SELECT `groupname` FROM `groups` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND FIND_IN_SET($regionId,regionid)");
                $groupNames = array_merge($groupNames, array_column($rows, 'groupname'));
            }
        }
        return array_filter(array_unique($groupNames));
    }

    /**
     * @param int $groupid - if set the members of the group are fetched. If 0, then members of all `groups`are fetched
     * @param int $chapterid - if groupid is 0, then chapterid is ignored, else members of specified chapter are fetched
     * @param int $channelid - if groupid is 0, then channelid is ignored, else members of specified channel are fetched
     * @param string $context - Valid values, 'P' for Post, 'E' for Event, 'N for News, 'O' for other. If set only users who have a specific notification on are returned.
     * @return string - returns a comma seperated list of matching userids
     */
    public static function GetGroupMembersAsCSV (int $groupid, int $chapterid, int $channelid, string $context='',bool $use_and_chapter_connector = false) {
        $retVal = '';
        global $_COMPANY; /* @var Company $_COMPANY */
        global $_ZONE;
        $notificationFilter = '';

        if ($context === 'P') {
            $notificationFilter = " AND `notify_posts`='1'";
        } elseif ($context === 'E') {
            $notificationFilter = " AND `notify_events`='1'";
        } elseif ($context === 'N') {
            $notificationFilter = " AND `notify_news`='1'";
        } elseif ($context === 'D') {
            $notificationFilter = " AND `notify_discussion`='1'";
        }

        $chapterChannelFilter = '';

        self::DBROGet("SET SESSION group_concat_max_len = 2048000"); //Increase the session limit; default is 1000
        if ($groupid == 0) {
            // Returns members of all `groups`in the company
            // Used IFNULL to prevent fatal errors when using grps as an ID string. This applies when no active groups are available.
            $g = self::DBROGet("SELECT IFNULL(GROUP_CONCAT(groupid),0) AS grps FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `isactive`=1");
            $groups = $g[0]['grps'];
        } else {
            // Return members of a given group
            $groups = $groupid;

            $chapterFilter = empty($chapterid) ? '' : "FIND_IN_SET({$chapterid},chapterid)";
            $channelFilter = empty($channelid) ? '' : "FIND_IN_SET({$channelid},channelids)";
            if (!empty($chapterFilter) && !empty($channelFilter)) {
                $chapter_channel_connector = ($use_and_chapter_connector ? 'AND' : 'OR');
                $chapterChannelFilter = " AND ({$chapterFilter} {$chapter_channel_connector} {$channelFilter})";
            } elseif (!empty($chapterFilter)) {
                $chapterChannelFilter = " AND ({$chapterFilter})";
            } elseif (!empty($channelFilter)) {
                $chapterChannelFilter = " AND ({$channelFilter})";
            }
        }
        $u = self::DBROGet("SELECT GROUP_CONCAT(DISTINCT(groupmembers.userid)) AS users FROM `groupmembers` JOIN users ON groupmembers.userid=users.userid WHERE users.companyid='{$_COMPANY->id()}' AND users.isactive=1 AND groupmembers.isactive=1 AND groupid IN ({$groups}) {$chapterChannelFilter} {$notificationFilter}");

        if (count($u))
            $retVal = $u[0]['users'];

        return $retVal;
    }

    /**
     * @param string $groupids
     * @param int $chapterid
     * @param int $channelid
     * @param string $locations
     * @param string $context
     * @param bool $use_and_chapter_connector default is OR
     * @return mixed|string
     */
    public static function GetGrouplistMembersAsCSV (string $groupids, int $chapterid, int $channelid, string $locations, string $context='', bool $use_and_chapter_connector = false) {
        $retVal = '';
        global $_COMPANY; /* @var Company $_COMPANY */
        global $_ZONE;
        $notificationFilter = '';

        if ($context === 'P') {
            $notificationFilter = " AND `notify_posts`='1'";
        } elseif ($context === 'E') {
            $notificationFilter = " AND `notify_events`='1'";
        } elseif ($context === 'N') {
            $notificationFilter = " AND `notify_news`='1'";
        }

        if (empty($groupids))
            return $retVal;

        if (empty($locations))
            $locations = "0";

        $chapterChannelFilter = '';
        $chapterFilter = empty($chapterid) ? '' : "FIND_IN_SET({$chapterid},chapterid)";
        $channelFilter = empty($channelid) ? '' : "FIND_IN_SET({$channelid},channelids)";
        if (!empty($chapterFilter) && !empty($channelFilter)) {
            $chapter_channel_connector = ($use_and_chapter_connector ? 'AND' : 'OR');
            $chapterChannelFilter = " AND ({$chapterFilter} {$chapter_channel_connector} {$channelFilter})";
        } elseif (!empty($chapterFilter)) {
            $chapterChannelFilter = " AND ({$chapterFilter})";
        } elseif (!empty($channelFilter)) {
            $chapterChannelFilter = " AND ({$channelFilter})";
        }

        self::DBROGet("SET SESSION group_concat_max_len = 2048000"); //Increase the session limit; default is 1000
        //First extract only valid groupids that belong to this company
        // Used IFNULL to prevent fatal errors when using grps as an ID string. This applies when no active groups are available.
        $g = self::DBROGet("SELECT IFNULL(GROUP_CONCAT(groupid),0)   AS grps FROM `groups`WHERE `companyid`='{$_COMPANY->id()}' AND `groupid` in ($groupids) AND `isactive`=1");
        $groups = $g[0]['grps'];

        if (!empty($groups)) {
            if ($locations == "0") {
                $u = self::DBROGet("SELECT GROUP_CONCAT(DISTINCT(groupmembers.userid)) AS users FROM groupmembers JOIN users ON groupmembers.userid=users.userid WHERE users.companyid='{$_COMPANY->id()}' AND users.isactive=1 AND groupmembers.isactive=1 AND groupid IN ({$groups}) {$chapterChannelFilter} {$notificationFilter}");
            } else {
                $u = self::DBROGet("SELECT GROUP_CONCAT(DISTINCT(groupmembers.userid)) AS users FROM groupmembers JOIN users ON groupmembers.userid=users.userid WHERE users.companyid='{$_COMPANY->id()}' AND users.isactive=1 AND groupmembers.isactive=1 AND groupid IN ({$groups}) AND homeoffice in ({$locations}) {$chapterChannelFilter} {$notificationFilter}");
            }
            if (!empty($u) && $u[0]['users'])
                $retVal = $u[0]['users'];
        }

        return $retVal;
    }

    /**
     * This function parses a csv list of groupids to find a particular groupid. Then it breaks the list of groupids to
     * a sublist so that only thr groupids that appear before a given groupid are kept.
     * Next it computes all the unique members of the groupid that are not in sublist of groupids.
     * This function is used to find the members of a newly invited group who need to be sent invitations.
     * @param string $groupids
     * @param int $groupid
     * @param string $locations
     * @param string $context
     * @return mixed|string
     */
	public static function GetDeltaGroupMembersAsCSV (string $groupids, int $groupid,string $locations, string $context='', bool $use_and_chapter_connector = false) {
        $retVal = '';
        $chapterid = 0;
        $channelid = 0;
        global $_COMPANY; /* @var Company $_COMPANY */

        if (empty($groupids) || empty($groupid))
            return $retVal;

        if (empty($locations))
            $locations = "0";

        $groupid_arr = explode(',', $groupids);
        $groupid_new_arr = [];

        // Get a substring of groupids before groupid
        foreach ($groupid_arr as $g) {
            if ($g == $groupid)
                break;
            $groupid_new_arr[] = $g;
        }
        $groupids_new = implode(',', $groupid_new_arr);
        $groups = '';

        $retVal = self::GetGrouplistMembersAsCSV($groupid, $chapterid, $channelid, $locations, $context, $use_and_chapter_connector); // if `groups`is empty then we will return the members of $groupid only
        if (!empty($groupids_new)) {
            $u1 = self::GetGrouplistMembersAsCSV($groupids_new, $chapterid, $channelid, $locations, $context, $use_and_chapter_connector);
            $delta = array_diff(explode(',', $retVal), explode(',', $u1));
            $retVal = implode(',', $delta);
        }
        return $retVal;
    }

    /**
     * This function is memoized so it can be trivially called as many times as needed without loading the DB
     * @param int $groupid
     * @return mixed|string
     */
	public static function GetGroupName (int $groupid) : string
    {
        global $_COMPANY;

        if (!$groupid)
            return $_COMPANY->getAppCustomization()['group']['groupname0'];

        $memoize_key = __METHOD__ . ':' . serialize(func_get_args());
        if (!isset(self::$memoize_cache[$memoize_key])) {
            $grow = self::DBROGet("SELECT groupname FROM `groups`WHERE groupid='{$groupid}' AND companyid={$_COMPANY->id()}");
            self::$memoize_cache[$memoize_key] = empty($grow) ? '-' : $grow[0]['groupname'];
        }

        return self::$memoize_cache[$memoize_key];
    }

	public static function GetChapterName (int $chapterid, int $groupid) {
        if ($chapterid < 0 || $groupid < 0) {
            return null;
        } elseif ($chapterid === 0) {
            $c = self::GetGroupName($groupid);
            return array('chapterid' => 0, 'chaptername' => $c, 'colour' => 'rgb(102,102,102)', 'regionids' => '0');
        } else {
            foreach (self::GetChapterList($groupid) as $chp) {
                if ($chp['chapterid'] == $chapterid)
                    return $chp;
            }
        }
        return array('chapterid' => 0, 'chaptername' => 'Not found', 'colour' => 'rgb(0,0,0)', 'regionids' => '0');
    }

	public function __toString() {
        return "" . parent::__toString();
    }

//    public function getPermalinkUrl() {
//        global $_COMPANY, $_ZONE;
//        return $_COMPANY->getAppURL($_ZONE->val('app_type')) . "goto?group=" . $this->val('permatag');
//    }

    /**
     * Returns a unique URL that can be shared to go directly to the group.
     * @return string
     */
    public function getShareableUrl()
    {
        global $_COMPANY, $_ZONE;
        if ($this->id == 0) {
            return $_COMPANY->getAppURL($_ZONE->val('app_type'));
        }
        return $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'detail?id=' . $_COMPANY->encodeId($this->id());

    }

    /**
     * Returns a list of all the chapters in a given group. If $ignoreStatus is set to false then all chapters
     * are returned, else only the active ones are returned.
     * @param int $groupid
     * @param bool $ignoreStatus , default is false which means only active chapters are returned.
     * @return array
     */
    public static function GetChapterListDetail (int $groupid, bool $ignoreStatus=false, bool $includeBranchName=false) : array {
        global $_COMPANY; //,$_ZONE; do not use $_ZONE ... it breaks some usecases.
        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            return array();
        }

        $status_filter = '';
        if (!$ignoreStatus) {
            $status_filter = 'AND `isactive`=1';
        }

        $branches = null;
        if ($groupid) {
            $rows = self::DBGet("SELECT * FROM `chapters` WHERE chapters.companyid='{$_COMPANY->id()}' AND (`groupid`='{$groupid}' {$status_filter})");

            foreach ($rows as $k => $row) {
                $rows[$k]['region'] = $_COMPANY->getRegionName($row['regionids']);
                if ($includeBranchName) {
                    $branches ??= Arr::GroupBy($_COMPANY->getAllBranches(), 'branchid'); // Initialize if null.
                    $branchname = '';
                    $brch_ids = Str::ConvertCSVToArray($row['branchids']);
                    foreach ($brch_ids as $brch_id) {
                        $branchname = $branchname . $branches[$brch_id][0]['branchname'] . '||';
                    }
                    $rows[$k]['branchname'] = trim($branchname,'|') ?: '-';
                }
            }

            usort($rows, function ($a, $b) {
                return $a['chaptername'] <=> $b['chaptername'];
            });
            return $rows;
        }
        return array();
    }

    /**
     * Returns a list of all active chapters in a given group are returned
     * This function caches the results.
     * @param int $groupid
     * @return array
     */
    public static function GetChapterList (int $groupid) : array {
        global $_COMPANY, $_MEMOIZE_CACHE;
        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            return array();
        }
        // Since this function can be called many times within each call, lets cache the value temporarily
        // Note Redis cache is shared by all front-ends, TEMP_CACHE is only used for the duration of the process.
        if (!isset($_MEMOIZE_CACHE['GROUP_CHAPTER_LIST'][$groupid])) {
            $key = "GRP_CHP_LST:{$groupid}";
            if (($chpList = $_COMPANY->getFromRedisCache($key)) === false) { // Not in cache
                $chpList = Group::GetChapterListDetail($groupid);
                $_COMPANY->putInRedisCache($key, $chpList, 3600);
            }
            $_MEMOIZE_CACHE['GROUP_CHAPTER_LIST'][$groupid] = $chpList;
        }
        return $_MEMOIZE_CACHE['GROUP_CHAPTER_LIST'][$groupid];
    }

    public function getAllMembersCount() : array
    {
        global $_COMPANY; /* @var Company $_COMPANY */

        if ($this->memberCount === null) { // Meaning the memberCount was never set
            $key = "GRP_MEM_C:{$this->id}";

            // Added for data migration, remove after Oct 2023 as we changed the key from int to array.
            if (is_string($_COMPANY->getFromRedisCache($key))) { $_COMPANY->expireRedisCache($key);}
            // End of remove after october 2023

            if (($this->memberCount = $_COMPANY->getFromRedisCache($key)) === false) { // Not in cache
                $this->memberCount =self::DBGet("SELECT COUNT(1) AS user_members_group, SUM(IF(groupmembers.chapterid='0',0,1)) AS user_members_chapters, SUM(IF(groupmembers.channelids='0',0,1)) AS user_members_channels FROM `groupmembers` JOIN users USING (userid) WHERE groupid='{$this->id}' AND users.isactive=1 AND groupmembers.isactive=1 ")[0];
                $_COMPANY->putInRedisCache($key, $this->memberCount, 86400);
            }
        }
        return $this->memberCount;
    }

    public function getAllTeamStats() : array
    {
        // See Statistics -> GroupStatistics::_generateStatistics() for the pattern
        $teams_registrations_row = self::DBROGet("SELECT SUM(IF(sys_team_role_type=2,1,0)) AS mentors_registered, SUM(IF(sys_team_role_type=3,1,0)) AS mentees_registered FROM member_join_requests JOIN team_role_type USING(roleid) JOIN users ON member_join_requests.userid = users.userid  WHERE sys_team_role_type IN (2,3) AND member_join_requests.groupid={$this->id()}");
        $teams_roles_row = self::DBROGet("SELECT SUM(IF(teams.isactive=1 AND sys_team_role_type=2,1,0)) AS mentors_active, SUM(IF(teams.isactive=110 AND sys_team_role_type=2,1,0)) AS mentors_completed, SUM(IF(teams.isactive=109 AND sys_team_role_type=2,1,0)) AS mentors_not_completed, SUM(IF(teams.isactive=1 AND sys_team_role_type=3,1,0)) AS mentees_active, SUM(IF(teams.isactive=110 AND sys_team_role_type=3,1,0)) AS mentees_completed, SUM(IF(teams.isactive=109 AND sys_team_role_type=3,1,0)) AS mentees_not_completed, count(1) AS total FROM teams join team_members using(teamid) JOIN team_role_type USING(roleid) JOIN users ON team_members.userid = users.userid WHERE sys_team_role_type IN (2,3) AND teams.groupid={$this->id()}");
        $teams_row = self::DBROGet("SELECT SUM(IF(teams.isactive=1,1,0)) AS teams_active, SUM(IF(teams.isactive=110,1,0)) AS teams_completed, SUM(IF(teams.isactive=109,1,0)) AS teams_not_completed, SUM(IF(teams.isactive=2,1,0)) AS teams_draft,SUM(IF(teams.isactive=0,1,0)) AS teams_inactive FROM teams WHERE teams.groupid={$this->id()}");

        return [
            'teams_mentors_active'          => empty($teams_roles_row) ? 0 : intval($teams_roles_row[0]['mentors_active']),
            'teams_mentors_completed'       => empty($teams_roles_row) ? 0 : intval($teams_roles_row[0]['mentors_completed']),
            'teams_mentors_not_completed'   => empty($teams_roles_row) ? 0 : intval($teams_roles_row[0]['mentors_not_completed']),
            'teams_mentees_active'          => empty($teams_roles_row) ? 0 : intval($teams_roles_row[0]['mentees_active']),
            'teams_mentees_completed'       => empty($teams_roles_row) ? 0 : intval($teams_roles_row[0]['mentees_completed']),
            'teams_mentees_not_completed'   => empty($teams_roles_row) ? 0 : intval($teams_roles_row[0]['mentees_not_completed']),

            'teams_active'                  => empty($teams_row) ? 0 : intval($teams_row[0]['teams_active']),
            'teams_completed'               => empty($teams_row) ? 0 : intval($teams_row[0]['teams_completed']),
            'teams_not_completed'           => empty($teams_row) ? 0 : intval($teams_row[0]['teams_not_completed']),
            'teams_draft'                   => empty($teams_row) ? 0 : intval($teams_row[0]['teams_draft']),
            'teams_inactive'                => empty($teams_row) ? 0 : intval($teams_row[0]['teams_inactive']),

            'teams_mentors_registered'      => empty($teams_registrations_row) ? 0 : intval($teams_registrations_row[0]['mentors_registered']),
            'teams_mentees_registered'      => empty($teams_registrations_row) ? 0 : intval($teams_registrations_row[0]['mentees_registered'])
        ];
    }

    public function getGroupMembersCount() :int
    {
        return  (int) $this->getAllMembersCount()['user_members_group'];
    }

    public function getChapterCount() : int {
        return count(Group::GetChapterList($this->id));
    }

    public function getChannelCount() :int {
        return count(Group::GetChannelList($this->id));
    }

    public function getChapter (int $chapterid, bool $ignoreStatus=false) {
        global $_COMPANY,$_ZONE;
        $status_filter = '';
        if (!$ignoreStatus) {
            $status_filter = 'AND `isactive`=1';
        }

        if ($chapterid < 0) {
            return null;
        } elseif ($chapterid == 0) {
            return array('chapterid' => 0, 'regionids' => 0, 'chaptername' => $_COMPANY->getAppCustomization()['group']['name-short'], 'colour' => 'rgb(102,102,102)');
        } else {
            $r1 = self::DBGet("SELECT * FROM `chapters` WHERE chapters.companyid='{$_COMPANY->id()}' AND ( chapters.zoneid='{$_ZONE->id()}' AND `chapterid`='{$chapterid}' AND `groupid`='{$this->id}'  {$status_filter})");

            if (count($r1))
                return $r1[0];
            else
                return null;
        }
    }

    public function getChapterByName (string $name) : array
    {
        $chapters = self::GetChapterList($this->id());
        return Arr::SearchColumnReturnRow($chapters, $name, 'chaptername');
    }

    public function getChannelByName (string $name) : array
    {
        $channels = self::GetChannelList($this->id());
        return Arr::SearchColumnReturnRow($channels, $name, 'channelname');
    }

    /**
     * @return array of all the chapters in the group.
     */
    public function getAllChapters (bool $activeOnly = false) {
        global $_COMPANY;

        if ($this->chapterList === null) {
            $activeCondition = "";
            if ($activeOnly) {
                $activeCondition = " AND `isactive`=1";
            }
            //load it, please do not use zone match here as it breaks auto provisioning of chapters
            $this->chapterList = self::DBGet("SELECT chapterid, groupid, chaptername, about, colour, branchids, regionids, isactive FROM `chapters` WHERE chapters.companyid='{$_COMPANY->id()}' AND (`groupid`='{$this->id}' {$activeCondition}) ");
        }
        return $this->chapterList;
    }

    /**
     * Returns all the chapters that have a matching branchid
     * @param int $branchid
     * @param bool $includeDefault if true then default chapter 0 is included in the list.
     * @return array
     */
    public function getChaptersMatchingBranchIds(int $branchid, bool $includeDefault=false) {
        global $_COMPANY; /* @var Company $_COMPANY */
        $this->getAllChapters();
        $matchingChapters = array();

        if ($branchid == 0) {
            $matchingChapters[] = array('chapterid' => 0, 'chaptername' => 'Default');
            return $matchingChapters; // Return right away
        }

        if ($includeDefault) {
            $matchingChapters[] = array('chapterid' => 0, 'chaptername' => 'Default');
        }
        foreach ($this->chapterList as $chapter) {
            $branchids = explode(',', $chapter['branchids']);
            if (in_array($branchid, $branchids)) {
                $matchingChapters[] = array('chapterid' => (int)$chapter['chapterid'], 'chaptername' => $chapter['chaptername']);
            }
        }

        return $matchingChapters;
    }

	public  function addChapter (string $chaptername, string $chaptercolor, string $leads, string $branchids, string $regionid, string $virtual_event_location, string $latitude, string $longitude) {
        global $_USER;
        global $_COMPANY,$_ZONE;

        $chaptername = Sanitizer::SanitizeGroupName($chaptername);
        // Create Chapter
        $about = 'About ' . $chaptername . ' ...';
        $retVal = self::DBInsertPS("INSERT INTO `chapters` (`companyid`,`zoneid`,`groupid`, `chaptername`, `colour`, `about`, `userid`,`isactive`, `leads`,`branchids`,`regionids`,`virtual_event_location`, `latitude`, `longitude`,`createdon`,`modifiedon`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())", 'iiixsxiisssxss', $_COMPANY->id,$_ZONE->id, $this->id, $chaptername, $chaptercolor, $about, $_USER->id, 0, $leads, $branchids, $regionid, $virtual_event_location, $latitude, $longitude);

        if ($retVal) {
            self::LogObjectLifecycleAudit('create', 'chapter', $retVal, 0);
        }
        return $retVal;
    }

	public  function updateChapter (int $chapterid, string $chaptername, string $chaptercolor, string $branchids, string $virtual_event_location, string $latitude, string $longitude) {
        global $_COMPANY,$_ZONE;

        $chaptername = Sanitizer::SanitizeGroupName($chaptername);
        $retVal =  self::DBMutatePS("UPDATE `chapters` SET `chaptername`= ?,`colour`=?,`branchids`=?,`virtual_event_location`=?, `latitude`=?, `longitude`=?,`modifiedon`=now() WHERE companyid = ?  AND zoneid= ? AND `chapterid` = ? AND `groupid`=?", 'xssxssiiii', $chaptername, $chaptercolor, $branchids, $virtual_event_location, $latitude, $longitude,$_COMPANY->id,$_ZONE->id, $chapterid, $this->id);
        $_COMPANY->expireRedisCache("GRP_CHP_LST:{$this->id}");
         if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'chapter', $chapterid, 0);

        }
        return $retVal;
    }

	public function changeChapterStatus (int $chapterid,int $status) {
        global $_COMPANY,$_ZONE;

        $retVal = self::DBMutate("UPDATE `chapters` SET `isactive`= '{$status}' WHERE companyid='{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' AND `chapterid` = '{$chapterid}' AND `groupid`='{$this->id}' ");
        $_COMPANY->expireRedisCache("GRP_CHP_LST:{$this->id}");

        if ($retVal) {
            if($status == '1'){
                self::LogObjectLifecycleAudit('state_change', 'chapter', $chapterid, 0, ['state' => 'publish']);

            }else{
                self::LogObjectLifecycleAudit('state_change', 'chapter', $chapterid, 0, ['state' => 'unpublish']);
            }
        }
        return $retVal;
    }

    /**
     * @param int $chapterid ; if $chapter = 0 then return all chapters leads of group
     * @param string $permissionType see Group::LEADS_PERMISSION_TYPES for value permission types, if empty then all
     * permissions are returned.
     * @param bool $showOnAboutUsFilterOnly for grouplead_type.show_on_aboutus status. If true then show grouplead_type
     * chapter lead on about us page
     * @return array
     */
	public function getChapterLeads (int $chapterid, string $permissionType = '', bool $showOnAboutUsFilterOnly = false)
    {
        global $_COMPANY;
        $permissionCondition = '';
        if ($permissionType) {
            $permissions = self::LEADS_PERMISSION_TYPES;
            if (in_array($permissionType,$permissions)){
                $permissionCondition = " AND grouplead_type.`{$permissionType}`=1";
            }
        }

        $showOnAboutUsFilterOnlyFilter = "";
        if($showOnAboutUsFilterOnly){
            $showOnAboutUsFilterOnlyFilter = " AND `grouplead_type`.`show_on_aboutus`=1";
        }

        $chapterFilter = '';
        if ($chapterid){
            $chapterFilter = "  AND `chapterleads`.`chapterid`='{$chapterid}' ";
        }

        $leads =  self::DBROGet("
            SELECT `chapterleads`.*,`grouplead_type`.`type`,`grouplead_type`.`allow_manage_budget`,`grouplead_type`.`allow_create_content`,`grouplead_type`.`allow_publish_content`,`grouplead_type`.`allow_manage` 
            FROM `chapterleads` 
                JOIN `grouplead_type` ON `chapterleads`.`grouplead_typeid` = `grouplead_type`.`typeid` 
            WHERE grouplead_type.companyid={$_COMPANY->id()} 
              {$chapterFilter}
              AND `chapterleads`.`groupid`='{$this->id}' 
              {$permissionCondition} 
              {$showOnAboutUsFilterOnlyFilter}
              ");

        // Step 1: Sort leads using priority in the priority column
        usort($leads, function ($a, $b) {
            $positionA = (int)strpos($a['priority'], $a['leadid']) ;
            $positionB = (int)strpos($b['priority'], $b['leadid']) ;
            return $positionA <=> $positionB;
        });

        // Step 2: Decorate the leads
        foreach($leads as &$lead){
            $leadUser = User::GetUser($lead['userid']);
            if ($leadUser){
                $lead['firstname'] = $leadUser->val('firstname');
                $lead['lastname'] = $leadUser->val('lastname');
                $lead['email'] = $leadUser->val('email');
                $lead['external_email'] = $leadUser->val('external_email');
                $lead['picture'] = $leadUser->val('picture');
                $lead['jobtitle'] = $leadUser->val('jobtitle');
                $lead['pronouns'] = $_COMPANY->getAppCustomization()['profile']['enable_pronouns'] ? $leadUser->val('pronouns') : '';
            } else {
                $lead['firstname'] = '';
                $lead['lastname'] = '';
                $lead['email'] = '';
                $lead['picture'] = '';
                $lead['jobtitle'] = '';
                $lead['pronouns'] = '';
            }
        }
        unset($lead);

        return $leads;
    }

	public function getChapterLeadDetail (int $chapterid, int $leadid) {
        return self::DBGet("SELECT chapterleads.`leadid`,chapterleads.`grouplead_typeid`,chapterleads.`roletitle`,chapterleads.priority,chapterleads.assigneddate, users.`userid`, users.`firstname`, users.`lastname`,grouplead_type.type FROM `chapterleads` LEFT JOIN users ON users.userid=chapterleads.userid LEFT JOIN grouplead_type on grouplead_type.typeid = chapterleads.grouplead_typeid WHERE chapterleads.chapterid='{$chapterid}' AND chapterleads.groupid='{$this->id}' AND chapterleads.`leadid`='{$leadid}' ");
    }

	public function addChapterLead (int $chapterid, int $userid, int $typeid, string $roletitle, string $assigneddate='' ) {
        global $_COMPANY, $_ZONE, $_USER;
        $rows1 = self::DBGet("SELECT `chapterid` FROM `chapters` WHERE chapters.companyid='{$_COMPANY->id()}' AND ( chapters.zoneid='{$_ZONE->id()}' AND `chapterid`='{$chapterid}' AND `groupid` ='{$this->id}' ) ");
        $rows2 = self::DBGet("SELECT `userid` FROM `users` WHERE `companyid`='{$this->cid}' AND `userid`='{$userid}' AND `isactive`='1'");
        $leads = '';
        if (count($rows1) && count($rows2)) {
            $rows3 = self::DBGet("SELECT `leadid`, `assigneddate` FROM `chapterleads` WHERE `chapterid`='{$chapterid}' AND `userid`='{$userid}'");
            if (count($rows3)) {
                if (empty($assigneddate)) {
                    $assigneddate = $rows3[0]['assigneddate']; // Keep existing values
                }
                $retVal = self::DBUpdatePS("UPDATE `chapterleads` SET `grouplead_typeid`=?, `roletitle`=?, assigneddate=?, `isactive`=1 WHERE `leadid`=?", 'ixxi', $typeid, $roletitle, $assigneddate, $rows3[0]['leadid']);
                if($retVal){
                    // Group User Log
                    GroupUserLogs::CreateGroupUserLog($this->id, $userid, GroupUserLogs::GROUP_USER_LOGS_ACTION['UPDATE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHAPTER_LEAD'], $typeid, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chapterid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
                }
                return $retVal;
            } else {

                $assigneddate = $assigneddate ?: Sanitizer::SanitizeUTCDatetime($assigneddate);
                $assigneddate = $assigneddate ?: gmdate('Y-m-d H:i:s');

                $retVal = self::DBInsertPS("INSERT INTO `chapterleads`(`chapterid`, `groupid`, `userid`, `grouplead_typeid`, `roletitle`, `assignedby`, `assigneddate`) VALUES (?,?,?,?,?,?,?)", 'iiiixix', $chapterid, $this->id, $userid, $typeid, $roletitle, $_USER->id(), $assigneddate);
                if ($retVal){
                    // Group User Log
                    GroupUserLogs::CreateGroupUserLog($this->id, $userid, GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHAPTER_LEAD'], $typeid, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chapterid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
                }
                return $retVal;
            }
        }
        return 0;
    }

	public function updateChapterLead (int $chapterid, int $leadid, int $typeid, string $roletitle) {
        global $_COMPANY, $_ZONE, $_USER;
        $rows1 = self::DBGet("SELECT `leads` FROM `chapters` WHERE chapters.companyid='{$_COMPANY->id()}' AND ( chapters.zoneid='{$_ZONE->id()}' AND `chapterid`='{$chapterid}' AND `groupid` ='{$this->id}' )");
        if (count($rows1)) {
            $check = self::DBGet("SELECT `leadid`,`userid` FROM `chapterleads` WHERE `leadid`='{$leadid}' AND `chapterid`='{$chapterid}'");
            if (!empty($check)){
                $retVal =  self::DBMutatePS("UPDATE `chapterleads` SET `grouplead_typeid`=?, `roletitle`=?, `assignedby`=?, `assigneddate`=NOW() WHERE `leadid`=? AND `chapterid`=?", 'ixiii', $typeid, $roletitle, $_USER->id(), $leadid, $chapterid);
                if($retVal){
                    // Group User Log
                    GroupUserLogs::CreateGroupUserLog($this->id, $check[0]['userid'], GroupUserLogs::GROUP_USER_LOGS_ACTION['UPDATE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHAPTER_LEAD'], $typeid, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chapterid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
                }
                return $retVal;
            }
        }
        return 0;
    }

    public function removeChapterLead (int $chapterid, int $leadid ) {
        global $_COMPANY, $_ZONE, $_USER;
        $check = self::DBGet("SELECT `leadid`,`userid`,`grouplead_typeid` FROM `chapterleads` WHERE `leadid`='{$leadid}' AND `chapterid`='{$chapterid}'");
        if (!empty($check)){
            $retVal = self::DBMutate("DELETE FROM `chapterleads` WHERE `leadid`='{$leadid}' AND `chapterid`='{$chapterid}'");
            if($retVal){
                // Group User Log
                GroupUserLogs::CreateGroupUserLog($this->id, $check[0]['userid'], GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHAPTER_LEAD'], $check[0]['grouplead_typeid'], GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chapterid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
            }
            return $retVal;
        }
        return 0;
    }

	public static function GetGroupDetail(int $id) {
        global $_COMPANY;
        return self::DBGet("SELECT * FROM `groups` WHERE `groupid`='{$id}' AND `companyid`='{$_COMPANY->id()}'");
    }

	public function updateGroupSetting(int $group_type, int $about_show_members, string $chapter_assign_type, int $join_anonymously, string $content_restrictions){
        global $_COMPANY;

        // Make sure chapter assign type is a valid values
        $chapter_assign_type = in_array($chapter_assign_type, array('auto', 'by_user_any', 'by_user_exactly_one', 'by_user_atleast_one')) ? $chapter_assign_type : 'by_user_any';

        // Make sure content restriction type is a valid value
        $content_restrictions = in_array($content_restrictions, array_keys(self::GROUP_CONTENT_RESTRICTIONS)) ? $content_restrictions : self::GROUP_CONTENT_RESTRICTIONS['anyone_can_view'];

        $retVal = self::DBMutate("UPDATE `groups` SET `group_type`='{$group_type}',`about_show_members`='{$about_show_members}',`chapter_assign_type`='{$chapter_assign_type}',`join_group_anonymously`='{$join_anonymously}',`content_restrictions` = '{$content_restrictions}', `modifiedon`=NOW() WHERE `groupid`='{$this->id}' AND `companyid`='{$_COMPANY->id()}'");
        $_COMPANY->expireRedisCache("GRP:{$this->id}");
        return $retVal;
    }

    /**
     * This method returns a list of all the groups a person is a member of along with member chapters and channels.
     * @param int $zoneid if set to 0 then search is made across all zones.
     * @param int $userid
     * @return array
     */
	public static function GetUserMembershipGroupsChaptersChannelsByZone (int $userid, int $zoneid)
    {
        global $_COMPANY;

        $zone_filter = '';
        if ($zoneid) {
            $zone_filter = "AND `groups`.`zoneid`='{$zoneid}'";
        }

        $groups = self::DBROGet("SELECT `groupmembers`.*,`groupname`,`company_zones`.`zoneid`,`zonename`,`join_group_anonymously` from `groupmembers` JOIN `groups` ON (`groupmembers`.`groupid`=`groups`.`groupid`) JOIN company_zones ON (`groups`.`zoneid`=`company_zones`.`zoneid`) WHERE `groups`.`companyid`='{$_COMPANY->id()}' {$zone_filter} AND `company_zones`.`isactive` = 1 AND `userid`='{$userid}' AND `groupmembers`.`isactive`=1 AND `groups`.`isactive`=1");
        array_multisort(array_column($groups,'zonename'), SORT_ASC, array_column($groups,'groupname'), SORT_ASC, $groups);

        foreach ($groups as $k1 => $v1) {
            if ($v1['chapterid']) {
                $groups[$k1]['chapters'] = self::DBROGet("SELECT `chapterid`, `chaptername` FROM `chapters` WHERE chapters.companyid='{$_COMPANY->id()}' AND (`chapterid` IN ({$v1['chapterid']}) AND `isactive`=1 )");
            } else {
                $groups[$k1]['chapters'] = array();
            }
            if ($v1['channelids']) {
                $groups[$k1]['channels'] = self::DBROGet("SELECT `channelid`,`channelname` FROM `group_channels` WHERE group_channels.`companyid`='{$_COMPANY->id()}' AND (`channelid` IN ({$v1['channelids']}) AND `isactive`=1)");
            } else {
                $groups[$k1]['channels'] = array();
            }
        }

        return $groups;
    }

	public function getGroupLead (int $leadid ) {
        return self::DBGet("SELECT groupleads.*,users.firstname,users.lastname,users.email,users.picture FROM `groupleads` JOIN users on users.userid=groupleads.userid WHERE groupleads.leadid='{$leadid}' AND groupleads.`groupid`='{$this->id}' AND groupleads.isactive=1");
    }

    /**
     * @param string $permissionType see Group::LEADS_PERMISSION_TYPES for value permission types, if empty then all
     * permissions are returned.
     * @param bool $showOnAboutUsFilterOnly for grouplead_type.show_on_aboutus status. If true then show grouplead_type
     * group lead on about us page
     * @return array
     */
    public function getGroupLeads (string $permissionType = '', bool $showOnAboutUsFilterOnly = false) {
        global $_COMPANY;

        $permissionCondition = '';
        if ($permissionType){
            $permissions = self::LEADS_PERMISSION_TYPES;
            if (in_array($permissionType,$permissions)){
                $permissionCondition = " AND grouplead_type.`{$permissionType}`=1";
            }
        }

        $showOnAboutUsFilterOnlyFilter = "";
        if($showOnAboutUsFilterOnly){
            $showOnAboutUsFilterOnlyFilter = " AND `grouplead_type`.`show_on_aboutus`=1";
        }

        $leads =  self::DBROGet("
                SELECT groupleads.*,grouplead_type.type,grouplead_type.allow_manage_budget,grouplead_type.allow_create_content,grouplead_type.allow_publish_content,grouplead_type.allow_manage 
                FROM `groupleads` 
                    JOIN grouplead_type ON grouplead_type.typeid=groupleads.grouplead_typeid  
                WHERE grouplead_type.companyid={$_COMPANY->id()} 
                  AND groupleads.`groupid`='{$this->id}' 
                  AND groupleads.isactive=1 
                  AND grouplead_type.companyid={$_COMPANY->id()} 
                  {$permissionCondition} 
                  {$showOnAboutUsFilterOnlyFilter} 
                " );

        // Step 1: Sort leads using priority in the priority column
            usort($leads, function ($a, $b) {
            $positionA = (int)strpos($a['priority'], $a['leadid']) ;
            $positionB = (int)strpos($b['priority'], $b['leadid']) ;
            return $positionA <=> $positionB;
        });

        // Step 2: Decorate the leads
        foreach($leads as &$lead){
            $leadUser = User::GetUser($lead['userid']);
            if ($leadUser){
                $lead['firstname'] = $leadUser->val('firstname');
                $lead['lastname'] = $leadUser->val('lastname');
                $lead['email'] = $leadUser->val('email');
                $lead['external_email'] = $leadUser->val('external_email');
                $lead['picture'] = $leadUser->val('picture');
                $lead['jobtitle'] = $leadUser->val('jobtitle');
                $lead['pronouns'] = $_COMPANY->getAppCustomization()['profile']['enable_pronouns'] ? $leadUser->val('pronouns') : '';
                
            } else {
                $lead['firstname'] = '';
                $lead['lastname'] = '';
                $lead['email'] = '';
                $lead['picture'] = '';
                $lead['jobtitle'] = '';
                $lead['pronouns'] = '';
            }
        }
        unset($lead);

        return $leads;
    }

	public static function AddGroupLead(int $groupid,int $userid, int $typeid, string $regionids, string $roletitle){
        global $_USER;
        $check = self::DBGet("SELECT leadid from `groupleads` WHERE `groupid`='{$groupid}' AND `userid`='{$userid}'");

        if (count($check)) {
            self::DBMutatePS("UPDATE `groupleads` SET `grouplead_typeid`=?, `roletitle`=?, `regionids`=? WHERE `groupid`=? AND `userid`=?", 'ixxii', $typeid, $roletitle, $regionids, $groupid, $userid);
            // Group User Log
            GroupUserLogs::CreateGroupUserLog($groupid, $userid, GroupUserLogs::GROUP_USER_LOGS_ACTION['UPDATE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_LEAD'], $typeid, '', 0,GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
            return 2;
        } else {
            $id =  self::DBInsertPS("INSERT INTO `groupleads`(`groupid`, `userid`, `regionids`, `grouplead_typeid`, `roletitle`, `assignedby`, `assigneddate`, `isactive`) VALUES (?,?,?,?,?,?,now(),1)", 'iixixi', $groupid, $userid, $regionids, $typeid, $roletitle, $_USER->id());
            if ($id){
                // Group User Log
                GroupUserLogs::CreateGroupUserLog($groupid, $userid, GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_LEAD'], $typeid, '', 0, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
            }
            return $id;
            //return 1;
        }
    }

	public static function UpdateGroupLead(int $groupid, int $leadid, int $typeid, string $regionids, string $roletitle){
        global $_USER;
        $retVal = 0;
        $check = self::DBGet("SELECT * from `groupleads` WHERE `groupid`='{$groupid}' AND `leadid`='{$leadid}'");
        if (!empty($check)){
            $retVal =  self::DBMutatePS("UPDATE `groupleads` SET `grouplead_typeid`=?, `regionids`=?, `roletitle`=? WHERE `groupid`=? and `leadid`=?", 'ixxii', $typeid, $regionids, $roletitle, $groupid, $leadid);
            if ($retVal){
                // Group User Log
                GroupUserLogs::CreateGroupUserLog($groupid, $check[0]['userid'], GroupUserLogs::GROUP_USER_LOGS_ACTION['UPDATE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_LEAD'], $typeid, '', 0,GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
            }
        }
        return $retVal;
    }

	public static function DeleteGroupLead(int $groupid, int $leadid)
    {
        global $_USER;
        $check = self::DBGet("SELECT * from `groupleads` WHERE `groupid`='{$groupid}' AND `leadid`='{$leadid}'");
        if (!empty($check)){
            self::DBMutate("DELETE FROM `groupleads` WHERE `groupid`='{$groupid}' AND `leadid`='{$leadid}' ");
            // Group User Log
            GroupUserLogs::CreateGroupUserLog($groupid, $check[0]['userid'], GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_LEAD'], $check[0]['grouplead_typeid'], '', 0, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
            return 1;
        }
        return 0;
    }

    // Groupleads Delete
    public static function GetAllGroupLeadsByType(int $typeid)
    {
        global $_COMPANY;
        $groupLeads = self::DBGet("
            SELECT groupleads.leadid,users.firstname,users.lastname,users.email, `groups`.groupname,`groups`.groupid 
            FROM `groupleads` 
                JOIN `users` ON groupleads.userid = users.userid
                JOIN `groups` ON `groups`.groupid = groupleads.groupid
            WHERE
                groupleads.grouplead_typeid='{$typeid}' 
                AND `groups`.companyid = {$_COMPANY->id()}
            ");

        if (!empty($groupLeads)) {
            return $groupLeads;
        }
        return array();
    }

    public static function GetAllChapterLeadsByType(int $typeid)
    {
        global $_COMPANY;
        $chapterLeads = self::DBGet("
            SELECT chapterleads.leadid,users.firstname,users.lastname,users.email, `groups`.groupname,`groups`.groupid,chapters.chaptername 
            FROM `chapterleads` 
                JOIN `users` ON chapterleads.userid=users.userid
                JOIN `groups` ON chapterleads.groupid=`groups`.groupid 
                JOIN `chapters` ON chapterleads.chapterid=chapters.chapterid 
            WHERE 
                chapterleads.grouplead_typeid='{$typeid}'
                AND `groups`.companyid = {$_COMPANY->id()}
            ");

        if (!empty($chapterLeads)) {
            return $chapterLeads;
        }
        return array();
    }

    public static function GetAllChannelLeadsByType(int $typeid)
    {
        global $_COMPANY;
        $channelLeads = self::DBGet("
            SELECT group_channel_leads.leadid,users.firstname,users.lastname,users.email, `groups`.groupname,`groups`.groupid,group_channels.channelname 
            FROM `group_channel_leads` 
                JOIN `users` ON group_channel_leads.userid=users.userid
                JOIN `groups` ON group_channel_leads.groupid=`groups`.groupid 
                JOIN `group_channels` ON group_channel_leads.channelid=group_channels.channelid
            WHERE 
                group_channel_leads.grouplead_typeid='{$typeid}'
                AND `groups`.companyid = {$_COMPANY->id()}
            ");

        if (!empty($channelLeads)) {
            return $channelLeads;
        }
        return array();
    }

    public static function DeleteGroupLeadType (int $typeid)
    {
        global $_COMPANY;
        // Here first find all users with this role using the GetAllGroupLeadsByType method
        // and then for each leadid call the following methods
        $allusers = self::GetAllGroupLeadsByType($typeid);
        if(count($allusers)>0){
            foreach($allusers as $user){
                // delete lead
                self::DeleteGroupLead($user['groupid'],$user['leadid']);
            }
        }

        //Note:There is group_lead_points_configuration dependency ... TODO lets make points module manage it on its own
        // delete the entry from grouplead_type table
        $result = self::DBMutate("DELETE FROM `grouplead_type` WHERE `companyid`='{$_COMPANY->id()}' AND typeid='{$typeid}'");
        if ($result) {
            self::LogObjectLifecycleAudit('delete', 'grouplead_type - group',  $typeid, 0);

        }
        return $result;
    }

    public static function DeleteChapterLeadType (int $typeid) {
        global $_COMPANY,$_ZONE;
        $allusers = self::GetAllChapterLeadsByType($typeid);
        if(count($allusers)>0){
            foreach($allusers as $user){
                self::removeChapterLead($user['groupid'],$user['leadid']);
            }
        }

        //Note:There is group_lead_points_configuration dependency ... TODO lets make points module manage it on its own
        // delete the entry from grouplead_type table
        $result = self::DBMutate("DELETE FROM `grouplead_type` WHERE `companyid`='{$_COMPANY->id()}' AND typeid='{$typeid}'");
        if ($result) {
            self::LogObjectLifecycleAudit('delete', 'grouplead_type - chapter',  $typeid, 0);
        }
        return $result;
    }

    public static function DeleteChannelLeadType (int $typeid) {
        global $_COMPANY;
        $allusers = self::GetAllChannelLeadsByType($typeid);
        if(count($allusers)>0){
            foreach($allusers as $user){
                self::removeChannelLead($user['groupid'],$user['leadid']);
            }
        }

        //Note:There is group_lead_points_configuration dependency ... TODO lets make points module manage it on its own
        // delete the entry from grouplead_type table
        $result = self::DBMutate("DELETE FROM `grouplead_type` WHERE `companyid`='{$_COMPANY->id()}' AND typeid='{$typeid}'");
        if ($result) {
            self::LogObjectLifecycleAudit('delete', 'grouplead_type - channel',  $typeid, 0);
        }
        return $result;
    }

	public function getChapterMembersCount(int $chapterid) {
        global $_COMPANY;
        $group = self::DBGet("SELECT COUNT(1) AS membersCount FROM `groupmembers` LEFT JOIN `groups` USING (groupid) WHERE `groups`.companyid='{$_COMPANY->id()}' AND  (`groups`.groupid='{$this->id}' AND groupmembers.isactive=1 AND FIND_IN_SET('{$chapterid}',groupmembers.chapterid))");
        return $group[0]['membersCount'];
    }

    /**
     * Returns a list of all the Channels in a given group. The values are always fetched from the databes
     * Unless DB fetch is needed, fetch data using GetChannel
     * @param int $groupid
     * @param bool $ignoreStatus default is false which means only active Channels are returned.
     * @return array
     */
    public static function GetChannelListDetail (int $groupid, bool $ignoreStatus=false) {
        global $_COMPANY;
        if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
            return array();
        }

        $status_filter = '';
        if (!$ignoreStatus) {
            $status_filter = 'AND group_channels.`isactive`=1';
        }

        if ($groupid) {
            $rows = self::DBGet("SELECT group_channels.*,users.firstname,users.lastname,users.picture FROM `group_channels` LEFT JOIN users ON users.userid=group_channels.createdby WHERE group_channels.`companyid`='{$_COMPANY->id()}' AND group_channels.groupid='{$groupid}' {$status_filter}");
            if (count($rows) > 0) {
                usort($rows, function ($a, $b) {
                    return $a['channelname'] <=> $b['channelname'];
                });
                return $rows;
            }
        }
        return array();
    }

    /**
     * Returns all active channels in the group
     * @param int $groupid
     * @return array
     */
    public static function GetChannelList (int $groupid) : array {
        global $_COMPANY, $_MEMOIZE_CACHE;
        if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
            return array();
        }
        // Since this function can be called many times within each call, lets cache the value temporarily
        // Note Redis cache is shared by all front-ends, TEMP_CACHE is only used for the duration of the process.
        if (!isset($_MEMOIZE_CACHE['GROUP_CHANNEL_LIST'][$groupid])) {
            $key = "GRP_CHL_LST:{$groupid}";
            if (($chlList = $_COMPANY->getFromRedisCache($key)) === false) { // Not in cache
                $chlList = Group::GetChannelListDetail($groupid);
                $_COMPANY->putInRedisCache($key, $chlList, 3600);
            }
            $_MEMOIZE_CACHE['GROUP_CHANNEL_LIST'][$groupid] = $chlList;
        }
        return $_MEMOIZE_CACHE['GROUP_CHANNEL_LIST'][$groupid];
    }

	public function getChannel (int $channelid, bool $ignoreStatus=false) {
        global $_COMPANY,$_ZONE;
        $status_filter = '';
        if (!$ignoreStatus) {
            $status_filter = 'AND `isactive`=1';
        }

        if ($channelid < 0) {
            return null;
        } else {
            $r1 = self::DBGet("SELECT * FROM `group_channels` WHERE group_channels.`companyid`='{$_COMPANY->id()}' AND group_channels.zoneid='{$_ZONE->id()}' AND `channelid`='{$channelid}' AND `groupid`='{$this->id}'  {$status_filter}");

            if (count($r1))
                return $r1[0];
            else
                return null;
        }
    }
    /**
     * Add Chanel
     */
	public  function addChannel (string $channelname, string $about, string $colour) {
        global $_USER;
        global $_COMPANY,$_ZONE;

        $channelname = Sanitizer::SanitizeGroupName($channelname);
        // Create Channel
        $retVal = self::DBInsertPS("INSERT INTO `group_channels` (`companyid`,`zoneid`,`groupid`, `channelname`, `about`,`createdby`,`colour`) VALUES (?,?,?,?,?,?,?)", 'iiixxis', $_COMPANY->id(), $_ZONE->id(),$this->id, $channelname, $about, $_USER->id(), $colour);
        $_COMPANY->expireRedisCache("GRP_CHL_LST:{$this->id}");

        if ($retVal) {
            self::LogObjectLifecycleAudit('create', 'channel', $retVal, 0);
        }
        return $retVal;
    }
    /**
     * Update Channel
     */
	public  function updateChannel (int $channelid, string $channelname,string $colour) {
        global $_COMPANY,$_ZONE;

        $channelname = Sanitizer::SanitizeGroupName($channelname);
        $retVal = self::DBUpdatePS("UPDATE group_channels SET channelname=?,`colour`=?, modifiedon=now() WHERE companyid=? AND (zoneid=? AND channelid=? AND groupid=?)", 'xxiiii', $channelname, $colour, $_COMPANY->id(), $_ZONE->id(), $channelid, $this->id);
        $_COMPANY->expireRedisCache("GRP_CHL_LST:{$this->id}");

        if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'channel', $channelid, 0);

        }
        return $retVal;
    }

    /**
     * Change channel status
     */
	public function changeChannelStatus (int $channelid,int $status) {
        global $_COMPANY,$_ZONE;

        $retVal = self::DBUpdate("UPDATE `group_channels` SET `isactive`='{$status}' WHERE group_channels.`companyid`='{$_COMPANY->id()}' AND group_channels.zoneid='{$_ZONE->id()}' AND `channelid` ='{$channelid}' AND `groupid`='{$this->id}'");
        $_COMPANY->expireRedisCache("GRP_CHL_LST:{$this->id}");
        if ($retVal) {
            if($status == '1'){
                self::LogObjectLifecycleAudit('state_change', 'channel', $channelid, 0, ['state' => 'publish']);
            }else{
                self::LogObjectLifecycleAudit('state_change', 'channel', $channelid, 0, ['state' => 'unpublish']);
            }
        }
        return $retVal;
    }

    /**
     * @param int $channelid, if channelid is set to 0 then return all channel leads in the group across all channels
     * @param string $permissionType see Group::LEADS_PERMISSION_TYPES for value permission types, if empty then all
     * permissions are returned.
     * @param bool $showOnAboutUsFilterOnly for grouplead_type.show_on_aboutus status. If true then show grouplead_type
     * channel lead on about us page
     * @return array
     */
	public function getChannelLeads (int $channelid, string $permissionType = '', bool $showOnAboutUsFilterOnly = false)
    {
        global $_COMPANY;
        $permissionCondition = '';
        if ($permissionType) {
            $permissions = self::LEADS_PERMISSION_TYPES;
            if (in_array($permissionType,$permissions)){
                $permissionCondition = " AND grouplead_type.`{$permissionType}`=1";
            }
        }

        $showOnAboutUsFilterOnlyFilter = "";
        if($showOnAboutUsFilterOnly){
            $showOnAboutUsFilterOnlyFilter = " AND `grouplead_type`.`show_on_aboutus`=1";
        }

        $channelLeadsFilter = "";
        if ($channelid){
            $channelLeadsFilter = "   AND group_channel_leads.channelid='{$channelid}'";
        }
        $leads = self::DBROGet("
            SELECT group_channel_leads.*,grouplead_type.type, grouplead_type.allow_manage_budget,grouplead_type.allow_create_content,grouplead_type.allow_publish_content,grouplead_type.allow_manage 
            FROM `group_channel_leads` 
                JOIN grouplead_type ON group_channel_leads.grouplead_typeid = grouplead_type.typeid 
            WHERE grouplead_type.companyid={$_COMPANY->id()} 
              {$channelLeadsFilter} 
              AND group_channel_leads.groupid='{$this->id}' 
              {$permissionCondition} 
              {$showOnAboutUsFilterOnlyFilter}
              ");

        // Step 1: Sort leads using priority in the priority column
        usort($leads, function ($a, $b) {
            $positionA = (int)strpos($a['priority'], $a['leadid']) ;
            $positionB = (int)strpos($b['priority'], $b['leadid']) ;
            return $positionA <=> $positionB;
        });

        // Step 2: Decorate the leads
        foreach($leads as &$lead){
            $leadUser = User::GetUser($lead['userid']);
            if ($leadUser){
                $lead['firstname'] = $leadUser->val('firstname');
                $lead['lastname'] = $leadUser->val('lastname');
                $lead['email'] = $leadUser->val('email');
                $lead['external_email'] = $leadUser->val('external_email');
                $lead['picture'] = $leadUser->val('picture');
                $lead['jobtitle'] = $leadUser->val('jobtitle');
                $lead['pronouns'] = $_COMPANY->getAppCustomization()['profile']['enable_pronouns'] ? $leadUser->val('pronouns') : '';
            } else {
                $lead['firstname'] = '';
                $lead['lastname'] = '';
                $lead['email'] = '';
                $lead['picture'] = '';
                $lead['jobtitle'] = '';
                $lead['pronouns'] = '';
            }
        }
        unset($lead);

        return $leads;
    }

	public function getChannelLeadDetail (int $channelid, int $leadid) {
        return self::DBGet("SELECT group_channel_leads.*, users.`userid`, users.`firstname`, users.`lastname`,grouplead_type.type FROM `group_channel_leads` LEFT JOIN users ON users.userid=group_channel_leads.userid LEFT JOIN grouplead_type on grouplead_type.typeid = group_channel_leads.grouplead_typeid WHERE group_channel_leads.channelid='{$channelid}' AND group_channel_leads.groupid='{$this->id}' AND group_channel_leads.`leadid`='{$leadid}' ");
    }

	public function addChannelLead (int $channelid, int $userid, int $typeid, string $roletitle, string $assigneddate='') {
        global $_COMPANY,$_ZONE,$_USER;
        global $_USER;
        $rows1 = self::DBGet("SELECT `channelid` FROM `group_channels` WHERE group_channels.`companyid`='{$_COMPANY->id()}' AND group_channels.zoneid='{$_ZONE->id()}' AND `channelid`='{$channelid}' AND `groupid` ='{$this->id}'");
        $rows2 = self::DBGet("SELECT `userid` FROM `users` WHERE `companyid`='{$this->cid}' AND `userid`='{$userid}' AND `isactive`='1'");
        $leads = '';
        if (count($rows1) && count($rows2)) {
            $rows3 = self::DBGet("SELECT `leadid`, `assigneddate` FROM `group_channel_leads` WHERE `channelid`='{$channelid}' AND `userid`='{$userid}'");
            if (count($rows3)) {
                if (empty($assigneddate)) {
                    $assigneddate = $rows3[0]['assigneddate']; // Keep existing values
                }
                $retVal = self::DBUpdatePS("UPDATE `group_channel_leads` SET `grouplead_typeid`=?, `roletitle`=?, `assigneddate`=?, `isactive`=1 WHERE `leadid`=?", 'ixxi', $typeid, $roletitle, $assigneddate, $rows3[0]['leadid']);
                if ($retVal){
                    // Group User Log
                    GroupUserLogs::CreateGroupUserLog($this->id, $userid, GroupUserLogs::GROUP_USER_LOGS_ACTION['UPDATE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHANNEL_LEAD'], $typeid, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $channelid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
                }
                return $retVal;
            } else {

                $assigneddate = $assigneddate ?: Sanitizer::SanitizeUTCDatetime($assigneddate);
                $assigneddate = $assigneddate ?: gmdate('Y-m-d H:i:s');

                $retVal =  self::DBInsertPS("INSERT INTO `group_channel_leads`(`channelid`, `groupid`, `userid`, `grouplead_typeid`, `roletitle`, `assignedby`, `assigneddate`) VALUES (?,?,?,?,?,?,?)", 'iiiixix', $channelid, $this->id, $userid, $typeid, $roletitle, $_USER->id(), $assigneddate);
                if ($retVal){
                    // Group User Log
                    GroupUserLogs::CreateGroupUserLog($this->id, $userid, GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHANNEL_LEAD'], $typeid, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $channelid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
                }
                return $retVal;
            }
        }
        return 0;
    }

	public function updateChannelLead (int $channelid, int $leadid, int $typeid,string $roletitle) {
        global $_COMPANY,$_ZONE,$_USER;
        $rows1 = self::DBGet("SELECT `channelid` FROM `group_channels` WHERE group_channels.`companyid`='{$_COMPANY->id()}' AND group_channels.zoneid='{$_ZONE->id()}' AND `channelid`='{$channelid}' AND `groupid` ='{$this->id}'");
        if (count($rows1)) {
            $check = self::DBGet("SELECT `leadid`,`userid` FROM `group_channel_leads` WHERE `channelid`='{$channelid}' AND `leadid`='{$leadid}'");
            if (!empty($check)) {
                $retVal =  self::DBMutatePS("UPDATE `group_channel_leads` SET `grouplead_typeid`=?, `roletitle`=? WHERE `leadid`=? AND `channelid`=?", 'ixii', $typeid, $roletitle, $leadid, $channelid);

                if ($retVal){
                    // Group User Log
                    GroupUserLogs::CreateGroupUserLog($this->id, $check[0]['userid'], GroupUserLogs::GROUP_USER_LOGS_ACTION['UPDATE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHANNEL_LEAD'], $typeid, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $channelid,GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
                }
                return $retVal;
            }
        }
        return 0;
    }

	public function removeChannelLead (int $channelid, int $leadid ) {
        global $_COMPANY,$_ZONE,$_USER;

        $check = self::DBGet("SELECT `leadid`,`userid`,`grouplead_typeid` FROM `group_channel_leads` WHERE `channelid`='{$channelid}' AND `leadid`='{$leadid}'");

        if (!empty($check)) {

            $retVal =  self::DBMutate("DELETE FROM `group_channel_leads` WHERE `leadid`='{$leadid}' AND `channelid`='{$channelid}'");
            if ($retVal){
                // Group User Log
                GroupUserLogs::CreateGroupUserLog($this->id, $check[0]['userid'], GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHANNEL_LEAD'], $check[0]['grouplead_typeid'], GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $channelid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
            }
            return $retVal;
        }
        return 0;
    }

    /** @deprecated This method is deprecated.... use User ... joinGroup method instead.
     * @param int $leadid
     * @param int $chapterid
     * @param int $channelid
     * @param bool $send_welcome_email
     * @return bool|int
     */
    public function addOrUpdateGroupMemberByAssignment(int $leadid, int $chapterid = 0, int $channelid = 0, bool $send_welcome_email = false,string $groupjoindate = '')
    {
        $retVal = false;

        $lead_user = User::GetUser($leadid);

        if ($leadid < 0 || !$lead_user) {
            return false;
        }

        $q1 = self::DBGet("SELECT memberid, chapterid, channelids, groupjoindate,isactive FROM groupmembers WHERE groupid='{$this->id}' AND userid='{$leadid}'");

        if (!empty($q1)) {
            // Record exists, update it.
            $newChapterJoined = false;
            $upd_chapterids = explode(',', $q1[0]['chapterid']);
            if ($chapterid && !in_array($chapterid,$upd_chapterids)){
                $newChapterJoined = true;
                $upd_chapterids[] = $chapterid;
            }
            $newChannelJoined = false;
            $upd_channelids = explode(',', $q1[0]['channelids']);
            if ($channelid && !in_array($channelid,$upd_channelids)){
                $newChannelJoined = true;
                $upd_channelids[] = $channelid;
            }

            if (!empty($groupjoindate)) {
                $retVal = self::DBMutatePS("UPDATE groupmembers SET groupjoindate=? WHERE memberid=?",'xi', $groupjoindate, $q1[0]['memberid']);
            }

            if ($newChapterJoined || $newChannelJoined) {
                $retVal = self::DBMutate("UPDATE groupmembers SET chapterid = '" . implode(",", array_unique($upd_chapterids)) . "', channelids = '" . implode(",", array_unique($upd_channelids)) . "' WHERE memberid={$q1[0]['memberid']}");

                if ($send_welcome_email){
                    if ($newChapterJoined) {
                        $job = new GroupMemberJob($this->id, $chapterid, 0);
                        $job->saveAsJoinType($leadid, false);
                    }
                    if ($newChannelJoined) {
                        $job = new GroupMemberJob($this->id, 0, $channelid);
                        $job->saveAsJoinType($leadid, false);
                    }
                }
            }

            if ($newChapterJoined){
                GroupUserLogs::CreateGroupUserLog($this->id, $leadid, GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chapterid,GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
            }

            if($newChannelJoined){
                    GroupUserLogs::CreateGroupUserLog($this->id, $leadid, GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $channelid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
            }
        } else {
            $upd_chapterids = array('0');
            $upd_channelids = array('0');

            $upd_chapterids[] = $chapterid;
            $upd_channelids[] = $channelid;

            $groupjoindate = $groupjoindate ?: Sanitizer::SanitizeUTCDatetime($groupjoindate);
            $groupjoindate = $groupjoindate ?: gmdate('Y-m-d H:i:s');

            //Create Membership
            if (self::DBInsert("INSERT INTO groupmembers(groupid, userid, chapterid, channelids, groupjoindate, isactive) VALUES ({$this->id},{$leadid},'" . implode(",", array_unique($upd_chapterids)) . "','" . implode(",", array_unique($upd_channelids)) . "','{$groupjoindate}','1')")) {
                $lead_user->addUserZone($this->val('zoneid'), false, false);

                if ($send_welcome_email){
                    $job = new GroupMemberJob($this->id, 0,0);
                    $job->saveAsJoinType($leadid, false);
                }

                $retVal = true;
                // Create Group user log
                GroupUserLogs::CreateGroupUserLog($this->id, $leadid, GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, '', 0,GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);

                if ($chapterid){
                    if ($send_welcome_email){
                        $job = new GroupMemberJob($this->id, $chapterid,0);
                        $job->saveAsJoinType($leadid, false);
                    }
                    GroupUserLogs::CreateGroupUserLog($this->id, $leadid, GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chapterid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
                }

                if ($channelid){
                    if ($send_welcome_email){
                        $job = new GroupMemberJob($this->id, 0, $channelid);
                        $job->saveAsJoinType($leadid, false);
                    }
                    GroupUserLogs::CreateGroupUserLog($this->id, $leadid, GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $channelid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
                }
            }
        }
        return $retVal;
    }

	public static function GetChannelName (int $channelid,int $groupid) {

        if ($channelid < 0 || $groupid < 0) {
            return null;
        } elseif ($channelid === 0) {
            //$c = self::GetGroupName($groupid);
            return array('channelid' => 0, 'channelname' => '', 'colour' => 'rgb(0,0,0)');
        } else {
            foreach (self::GetChannelList($groupid) as $chl) {
                if ($chl['channelid'] == $channelid)
                    return $chl;
            }
        }
        return array('channelid' => 0, 'channelname' => 'Not found', 'colour' => 'rgb(0,0,0)');
    }

	public function getChannelMembersCount(int $channelid) {
        global $_COMPANY;
        $group = self::DBGet("SELECT COUNT(1) AS membersCount FROM `groupmembers` LEFT JOIN `groups` USING (groupid) WHERE `groups`.companyid='{$_COMPANY->id()}' AND  (`groups`.groupid='{$this->id}' AND FIND_IN_SET('{$channelid}',groupmembers.channelids))");
        return $group[0]['membersCount'];
    }

    /**
     * Return list of local and global groups by user office location
     */
    /**
     * This method is used to sent email to user who is assigned Lead for Group/Channel/Chapter
     * @param int $id is group id if $section =1, chapter id if $section = 2, channel id if $section = 3
     * @param int $section = 1 for Group, =2 for Chapter, =3 for channel
     * @param int $leadTypeId
     * @param int $leadUserid
     * @return bool
     */
	public function sendGroupLeadAssignmentEmail(int $id, int $section, int $leadTypeId, int $leadUserid){
        global $_COMPANY;
        global $_USER;
        global $_ZONE;
        $leadUser = User::GetUser($leadUserid);
        $app_type = $_ZONE->val('app_type');
        $welcomeMessage = '';
        $gtypes = $_COMPANY->getGroupLeadType($leadTypeId);

        $leadType = "";
        $welcomeMessage = "";
        if ($gtypes) {
            $leadType = $gtypes['type'];
            if ($gtypes['welcome_message']){
                $welcomeMessage = ($gtypes['welcome_message']);
            }
        }

        $ergName = "";
        $chapterid = '0';
        $channelid = 0;
        $url = $_COMPANY->getAppURL($app_type);
        if ($section == 2) { #Chapter
            $chapterid = $id;
            $chapter = $this->getChapter($chapterid);
            if ($chapter) {
                $ergName = $this->val('groupname') . " > " . $chapter['chaptername'];
            }
            $url .= 'detail?id=' . $_COMPANY->encodeId($this->id()) . '&chapterid=' . $_COMPANY->encodeId($id);

        } else if ($section == 3) { #Channel
            $channelid = $id;
            $channel = $this->getChannel($channelid);
            if ($channel) {
                $ergName = $this->val('groupname') . " > " . $channel['channelname'];
            }
            $url .= 'detail?id=' . $_COMPANY->encodeId($this->id()) . '&channelid=' . $_COMPANY->encodeId($id);

        } else { # Group
            $ergName = $this->val('groupname');
            $url .= 'detail?id=' . $_COMPANY->encodeId($this->id());
        }


        $fromEmailLabel = $this->getFromEmailLabel($chapterid, $channelid);

        if ($leadType && $ergName) {
            $subject = 'You have been added as a ' . $leadType . ' for ' . $ergName . '';
            $content_subheader = '<br><p>You are now a ' . htmlspecialchars($leadType) . ' of <a href="' . $url . '">' . htmlspecialchars($ergName) . '</a></p><br>';
            $content_subfooter = '';

            $welcomeMessage = EmailHelper::OutlookFixes($welcomeMessage);
            $email_content = EmailHelper::GetEmailTemplateForGenericHTMLContent($content_subheader, $content_subfooter, $welcomeMessage);

            return $_COMPANY->emailSend2($fromEmailLabel, $leadUser->val('email'), $subject, $email_content, $app_type, '');
        }

        return true;
    }

	public function autoLinkOtherRegionalGroupsChapters () {
        global $_COMPANY,$_ZONE;
        global $_USER;

        // First Get all branches for the select office raven group
        $my_chapters = self::DBGet("SELECT chapters.branchids FROM `chapters` WHERE  chapters.companyid='{$_COMPANY->id()}' AND ( chapters.zoneid='{$_ZONE->id()}' AND groupid={$this->id})");
        $my_branches = array();
        foreach ($my_chapters as $item) {
            $my_branches = array_merge($my_branches, explode(',', $item['branchids']));
        }
        $my_branches = array_unique($my_branches);

        // Next get all the affinities zones that have intersecting regions
        $zoneids_array = $_COMPANY->getIntersectingZoneIds($this->val('zoneid'));

        $all_chapters = array();
        $all_groups = array();

        // Get all the branchids for all Affinities zones
        if (count($zoneids_array)) {
            $zoneids_str = implode(',', $zoneids_array);
            $group_type = Group::GROUP_TYPE_OPEN_MEMBERSHIP;
            $all_groups = self::DBGet("SELECT groupid FROM `groups` WHERE `groups`.`companyid`='{$_COMPANY->id()}' AND (zoneid IN ({$zoneids_str}) AND `groups`.isactive=1 AND `groups`.group_type={$group_type})");
            $all_groups_str = implode(',', array_column($all_groups, 'groupid'));
            if (!empty($all_groups_str)) {
                $all_chapters = self::DBGet("SELECT chapterid, groupid, branchids FROM `chapters` WHERE chapters.companyid='{$_COMPANY->id()}' AND (groupid IN ({$all_groups_str}) AND `isactive`=1)");
            }
        }

        // Next find all the groups that have chapters with interesting branches
        $localGroups = array();
        // First we will add all the global groups and initialize them to 0. This will allow us to add global groups
        // which is a required to show global groups.
        foreach ($all_groups as $all_group) {
            $localGroups[$all_group['groupid']] = []; // Empty array
        }
        foreach ($all_chapters as $chapter) {
            $branchids = explode(',', $chapter['branchids']);
            if (count(array_intersect($my_branches, $branchids))) { // Check if local chapter
                $localGroups[$chapter['groupid']][] = $chapter['chapterid'];
            }
        }

        // Next link groups
        // First delete existing links, next add new links
        self::DBUpdate("DELETE FROM group_linked_groups WHERE groupid={$this->id}");

        foreach ($localGroups as $k => $v) {
            if (empty($v)) {
                $chp_vals = '0';
            } elseif (is_array($v)) {
                $chp_vals = implode(',', $v);
            } else {
                $chp_vals = $v;
            }

            self::DBInsert("INSERT INTO group_linked_groups (groupid, linked_groupid, linked_chapterids, linked_channelids, modifiedby) VALUES ({$this->id}, {$k}, '{$chp_vals}', '', {$_USER->id()})");
        }
    }

    public static function GetGroupByNameAndZoneId(string $groupname, int $zoneid) {
        global $_COMPANY;

        if (empty($groupname)) {
            return null;
        }

        $row = self::DBROGetPS("SELECT groupid FROM `groups` WHERE companyid=? AND (groupname=? AND zoneid=?)", 'isi', $_COMPANY->id(), $groupname, $zoneid);
        if (count($row)) {
            return self::GetGroup($row[0]['groupid']);
        }
        return null;
    }

    /**
     * @param string $groupname
     * @param int $regionid
     * @return Group|int|null
     */

	public static function GetOrCreateGroupByName(string $groupname, int $regionid=0, int $checkOnly=0) {
        global $_COMPANY;
        global $_USER;
        global $_ZONE;

        $groupname = Sanitizer::SanitizeGroupName($groupname);

        if (empty($groupname)) {
            return 0;
        }

        $row = self::DBGetPS("SELECT groupid,regionid FROM `groups` WHERE companyid=? AND (groupname=? AND zoneid=?)", 'ixi', $_COMPANY->id(), $groupname, $_ZONE->id());

        if (count($row) > 1) {
            return null; // More than one groups got matched, this can happen when two groups have same name, e.g. one in each different category.
        }

        if ($checkOnly) {
            if (count($row)) {
                return self::GetGroup($row[0]['groupid']);
            } else {
                return null;
            }
        }
        if (count($row)) {
            if ($regionid) {
                $new_regions = '';
                $existing_regions = explode(',', $row[0]['regionid']);
                if (count($existing_regions) === 1 && $existing_regions[0] === '0') {
                    $new_regions = $regionid;
                } elseif (!in_array($regionid, $existing_regions)) {
                    $existing_regions[] = $regionid;
                    if (($zero_key = array_search('0', $existing_regions)) !== false) {
                        unset($existing_regions[$zero_key]);
                    }
                    $new_regions = implode(',', $existing_regions);
                }
                if (!empty($new_regions)) {
                    self::DBMutatePS("UPDATE `groups` SET regionid='{$new_regions}' WHERE groupid={$row[0]['groupid']} AND companyid={$_COMPANY->id()}");
                }
            }
            return self::GetGroup($row[0]['groupid']);
        } else {
            $default_categoryid = (int)Group::GetDefaultGroupCategoryRow()['categoryid'];
            $permatag = substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(12 / strlen($x)))), 1, 12);
            $groupid = self::DBInsertPS("INSERT into `groups` (companyid, regionid, addedby, groupname,groupname_short, abouttitle, aboutgroup, coverphoto, overlaycolor, overlaycolor2, groupicon, permatag, priority, addedon, from_email_label, zoneid, categoryid, modifiedon, isactive) values (?,?,?,?,?,?,?,'',?,?,'',?,'',NOW(),'',?,?,NOW(),1)", 'iiixxxxxxxii', $_COMPANY->id(), $regionid, $_USER->id(), $groupname, $groupname, $groupname, 'About ' . $groupname . ' ...', 'rgb(240,255,255)', 'rgb(240,255,255)', $permatag, $_ZONE->id(),$default_categoryid);

            return self::GetGroup($groupid);
        }
    }

    /**
     * Get or Create channel by name
     */
	public function getOrCreateChapterByName(string $chaptername,int $regionid, int $branchid,int $checkOnly=0) {

        global $_COMPANY;
        global $_USER;
        global $_ZONE;

        $chaptername = Sanitizer::SanitizeGroupName($chaptername);

        if (empty($chaptername)) {
            return 0;
        }

        $row = self::DBGetPS("SELECT chapterid,chapters.regionids,branchids from `chapters` left join `groups` on chapters.groupid=`groups`.groupid where chapters.companyid=? AND chapters.zoneid=? AND chapters.groupid=? and chaptername=?", 'iiix', $_COMPANY->id(),$_ZONE->id(),$this->id, $chaptername);
        if ($checkOnly) {
            if (count($row)) {
                return $row[0]['chapterid'];
            } else {
                return 0;
            }
        }

        if (count($row) == 0) {
            $retVal = self::DBInsertPS("INSERT into `chapters` (groupid, companyid,zoneid, chaptername, colour, createdon, userid, about, modifiedon, isactive,regionids,branchids) values (?,?,?,?,?,NOW(),?,?,NOW(),1,?,?)", 'iiixxixix', $this->id, $_COMPANY->id(),$_ZONE->id(), $chaptername, 'rgb(100,100,100)', $_USER->id(), 'About ' . $chaptername . ' ...', $regionid, $branchid);
            $_COMPANY->expireRedisCache("GRP_CHP_LST:{$this->id}");
            return $retVal;
        } else {
            if ($regionid) {
                if (!empty($row[0]['regionids']) && ($regionid != $row[0]['regionids'])) {
                    return 0; //regionid is already assigned and new regionid is different
                } elseif (empty($row[0]['regionids'])) {
                    //Setting regionid for the first time
                    self::DBUpdatePS("UPDATE chapters set regionids=?,modifiedon=NOW() WHERE companyid=? AND zoneid=? AND chapterid=?", 'iiii', $regionid,$_COMPANY->id(),$_ZONE->id(), $row[0]['chapterid']);
                }
            }

            if ($branchid) {
                $chk1 = self::DBGet("SELECT chapterid FROM `chapters` left join `groups` on chapters.groupid=`groups`.groupid WHERE chapters.companyid='{$_COMPANY->id()}' AND ( chapters.zoneid='{$_ZONE->id()}' AND chapters.groupid={$this->id} AND FIND_IN_SET({$branchid},chapters.branchids) )");
                if (count($chk1) && $row[0]['chapterid'] != $chk1[0]['chapterid']) {
                    return 0; // branch is already assigned to another chapter
                } else {
                    $new_branches = '';
                    $existing_branches = explode(',', $row[0]['branchids']);
                    if (!in_array($branchid, $existing_branches) || in_array('0', $existing_branches)) {
                        $existing_branches[] = $branchid;
                        if (($zero_key = array_search('0', $existing_branches)) !== false) {
                            unset($existing_branches[$zero_key]);
                        }
                        $new_branches = implode(',', array_unique($existing_branches));
                    }

                    if (!empty($new_branches)) {
                        self::DBUpdatePS("UPDATE chapters set branchids=?,modifiedon=NOW() WHERE companyid=? AND zoneid=? AND chapterid=?", 'siii', $new_branches, $_COMPANY->id(), $_ZONE->id(), $row[0]['chapterid']);
                    }
                }
            }
            $_COMPANY->expireRedisCache("GRP_CHP_LST:{$this->id}");
            return $row[0]['chapterid'];
        }
    }

    /**
     * Get or create channel by name
     */

	public function getOrCreateChannelByName(string $channelname,int $checkOnly=0) {

        global $_COMPANY;
        global $_USER;
        global $_ZONE;

        $channelname = Sanitizer::SanitizeGroupName($channelname);

        if (empty($channelname)) {
            return 0;
        }

        $row = self::DBGetPS("SELECT channelid from `group_channels` left join `groups` on group_channels.groupid=`groups`.groupid where group_channels.`companyid`=? AND group_channels.zoneid=? AND group_channels.groupid=? and `groups`.companyid=? and channelname=?", 'iiiix',$_COMPANY->id(), $_ZONE->id(), $this->id, $_COMPANY->id(), $channelname);
        if ($checkOnly) {
            if (count($row)) {
                return $row[0]['channelid'];
            } else {
                return 0;
            }
        }

        if (count($row) == 0) {
            $retVal = self::DBInsertPS("INSERT into `group_channels` (`companyid`,`zoneid`,`groupid`, `channelname`, `about`,`createdby`, `createdon`,`isactive`) values (?,?,?,?,?,?,NOW(),1)", 'iiixxi', $_COMPANY->id(), $_ZONE->id(), $this->id, $channelname, 'About ' . $channelname . ' ...', $_USER->id());
            $_COMPANY->expireRedisCache("GRP_CHL_LST:{$this->id}");
            return $retVal;
        } else {
            return $row[0]['channelid'];
        }
    }

    /**
     * Get or create group lead type by type
     */

	public static function GetOrCreateGroupLeadTypeByType(string $groupleadtype,int $sys_leadtype) {

        global $_COMPANY;
        global $_USER;
        global $_ZONE;
        $retVal = array();

        if (empty($groupleadtype))
            return 0;

        $row = self::DBGetPS("select typeid,sys_leadtype from `grouplead_type` where `companyid`=? and `type`=? and zoneid=? and sys_leadtype=?", 'isii', $_COMPANY->id(), $groupleadtype, $_ZONE->id(), $sys_leadtype);

        if (count($row)) {
            $retVal['typeid'] = $row[0]['typeid'];
            $retVal['systypeid'] = $row[0]['sys_leadtype'];
        } else {
            $i = self::DBInsertPS("INSERT INTO `grouplead_type` (companyid,`sys_leadtype`, `type`, zoneid, modifiedon, isactive) VALUES (?,?,?,?,NOW(),1)", 'iisi', $_COMPANY->id(), $sys_leadtype, $groupleadtype, $_ZONE->id());
            $retVal['typeid'] = $i;
            $retVal['systypeid'] = 0;
        }
        return $retVal;
    }

	public static function GetGroupleadTypes () {
        global $_COMPANY;
        global $_ZONE;
        $gtypes = self::DBGet("SELECT * FROM grouplead_type WHERE companyid='{$_COMPANY->id()}' AND isactive=1");
        $types = array();

        foreach($gtypes as $g) {
            $types[$g['typeid']]['type'] = $g['type'];
            $types[$g['typeid']]['sys_leadtype'] = $g['sys_leadtype'];
            $permissions = array();
            $g['allow_create_content'] ? $permissions[] = 'Create Content' : '';
            $g['allow_publish_content'] ? $permissions[] = 'Publish Content' : '';
            $g['allow_manage'] ? $permissions[] = 'Manage' : '';
            $g['allow_manage_grant'] ? $permissions[] = 'Grant Role' : '';
            $g['allow_manage_budget'] ? $permissions[] = 'Budget' : '';
            $g['allow_manage_support'] ? $permissions[] = 'Support' : '';
            $types[$g['typeid']]['permissions'] =  $permissions;
        }
        return $types;
    }
    /**
     * Add or update Group Lead
     */

	public function addOrUpdateGroupLead(int $who, int $typeid, string $roletitle, string $assigneddate='', string $regionids='0') {
        global $_COMPANY;
        global $_USER;
        global $_ZONE;

        $gl_row = self::DBGetPS("select leadid, assigneddate from groupleads where groupid=? and userid=?", 'ii', $this->id, $who);
        if (count($gl_row)) {
            // Update
            if (empty($assigneddate)) {
                $assigneddate = $gl_row[0]['assigneddate']; // Keep existing values
            }
            self::DBUpdatePS("update groupleads set grouplead_typeid=?, roletitle=?, regionids=?, assigneddate=? where leadid=?", 'ixxxi', $typeid, $roletitle, $regionids, $assigneddate, $gl_row[0]['leadid']);
            $retVal = $gl_row[0]['leadid'];
            if ($retVal){
                // Group User Log
                GroupUserLogs::CreateGroupUserLog($this->id, $who, GroupUserLogs::GROUP_USER_LOGS_ACTION['UPDATE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_LEAD'], $typeid, '', 0, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
            }
        } else {

            $assigneddate = $assigneddate ?: Sanitizer::SanitizeUTCDatetime($assigneddate);
            $assigneddate = $assigneddate ?: gmdate('Y-m-d H:i:s');

            $retVal =  self::DBInsertPS("insert into groupleads (groupid,userid,regionids,grouplead_typeid,roletitle,assignedby,assigneddate,isactive) VALUES (?,?,?,?,?,?,?,1)", 'iixixix', $this->id, $who, $regionids, $typeid, $roletitle, $_USER->id(), $assigneddate);
            if ($retVal){
                // Group User Log
                GroupUserLogs::CreateGroupUserLog($this->id, $who, GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_LEAD'], $typeid, '', 0, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['LEAD_INITIATED']);
            }
        }
        return $retVal;
    }
    /**
     * create Communication Template
     */
	public static function CreateCommunicationTemplate(int $groupid,string $field, int $fieldid, int $communication_trigger,  int $templateid, string $template, string $communication, string $emailsubject,string $email_cc_list = '', int $send_upcoming_events_email = 0, ?int $anniversary_interval=null) {
        global $_COMPANY,$_ZONE,$_USER;
        if ($field !== 'chapterid' && $field !== 'channelid') {
            return 0;
        } elseif (!in_array($communication_trigger, array_values(Group::GROUP_COMMUNICATION_TRIGGERS))) {
            return 0; // Invalid trigger
        } else {
            return self::DBInsertPS("INSERT INTO `group_communications` (`companyid`, `zoneid`, `groupid`, {$field}, `communication_trigger`, `templateid`, `template`, `communication`, `emailsubject`,`createdby`, `createdon`, `modifiedon`,`email_cc_list`,`send_upcoming_events_email`, `anniversary_interval`) VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW(),?,?,?)", 'iiiiiixxsixii', $_COMPANY->id(),$_ZONE->id(),$groupid, $fieldid, $communication_trigger, $templateid, $template, $communication, $emailsubject, $_USER->id(), $email_cc_list,$send_upcoming_events_email, $anniversary_interval);
        }
    }

    /**
     * Update Communication template
     */
    public static function UpdateCommunicationTemplate(int $communicationid, int $groupid, string $template, string $communication, string $emailsubject, string $email_cc_list = '',int $send_upcoming_events_email=0): int
    {
        global $_COMPANY,$_ZONE;
        return self::DBUpdatePS("UPDATE `group_communications` SET `template`=?, `communication`=?,`modifiedon`=NOW(),emailsubject=?,email_cc_list=?,`send_upcoming_events_email`=? WHERE companyid=? AND zoneid=? AND communicationid=? AND groupid=?", 'xxsxiiiii', $template, $communication, $emailsubject, $email_cc_list, $send_upcoming_events_email, $_COMPANY->id(), $_ZONE->id(), $communicationid, $groupid);
    }

    /**
     * Get Communication Detail
     */
    public function getCommunicationTemplateDetail(int $communicationid)
    {
        global $_COMPANY,$_ZONE;
        $row = null;
        $c =  self::DBGet("SELECT * FROM `group_communications` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `groupid`='{$this->id()}' AND`communicationid`='{$communicationid}'");
        if (!empty($c)){
            $row = $c[0];
        }
        return $row;
    }


	public function updateGroupAboutUs(string $aboutgroup) {
        global $_COMPANY;
        $retVal = self::DBUpdatePS("UPDATE `groups` SET `aboutgroup`=?,`modifiedon`=NOW() WHERE `groupid`=? AND `companyid`=?", 'xii', $aboutgroup, $this->id, $_COMPANY->id());
        $_COMPANY->expireRedisCache("GRP:{$this->id}");
        return $retVal;

    }
	public function updateChapterAboutUs(int $chapterid, string $about) {
        global $_COMPANY,$_ZONE;

        $retVal = self::DBUpdatePS("UPDATE `chapters` SET `about`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `zoneid`=? AND `chapterid`=? AND `groupid`=?", 'xiiii', $about, $_COMPANY->id(),$_ZONE->id(), $chapterid, $this->id);
        $_COMPANY->expireRedisCache("GRP_CHP_LST:{$this->id}");
        return $retVal;
    }
	public function updateChannelAboutUs(int $channelid, string $about) {
        global $_COMPANY,$_ZONE;
        $retVal = self::DBUpdatePS("UPDATE `group_channels` SET `about`=?,`modifiedon`=NOW() WHERE group_channels.`companyid`=? AND group_channels.zoneid=? AND `groupid`=? AND `channelid`=?", 'xiiii', $about, $_COMPANY->id(), $_ZONE->id(), $this->id, $channelid);
        $_COMPANY->expireRedisCache("GRP_CHL_LST:{$this->id}");
        return $retVal;

    }

    /**
     * @param array $zoneids array of zoneids in which to search
     * @param array $groupCategories Optional array of group categories to use for search
     * @return array of groups with groupid, groupname, color set. Only active groups are returned.
     */
    public static function GetAllGroupsByZones (array $zoneids, array $groupCategories = array(), bool $getVisibleGroupsOnly = true) : array
    {
        global $_COMPANY, $_ZONE;
        $groupCategoryFilter = "";
        if (!empty($groupCategories)) {
            $groupCategoryFilter = ' AND categoryid IN (' . implode("','", $groupCategories) . ') ';
        }

        $zoneids = Sanitizer::SanitizeIntegerArray($zoneids);
        if(empty($zoneids)) {
            return [];
        }
        $zoneids_csv = implode(',', $zoneids);

        $groupTypeFilter = '';
        if ($getVisibleGroupsOnly) {
            $groupTypeFilter= ' AND group_type IN ('.Group::GROUP_TYPE_OPEN_MEMBERSHIP.','.Group::GROUP_TYPE_REQUEST_TO_JOIN.','.Group::GROUP_TYPE_MEMBERSHIP_DISABLED.')';
        }

        $groups = self::DBROGet("SELECT `groupid`, `groupname`, groupname_short, overlaycolor, group_type, regionid FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND (zoneid IN ({$zoneids_csv}) {$groupCategoryFilter} {$groupTypeFilter} AND isactive=1)");
        usort($groups, function ($a, $b) {
            return $a['groupname'] <=> $b['groupname'];
        });
        return $groups;
    }

	public static function GetAllLinkedGroupsChaptersChannels (string $groupIds) : array {
        global $_COMPANY, $_ZONE;
        $linkedRows = array();
        $linkedGroupRows = array();
        $linkedGroupIds = array();
        $linkedChapterIds = array();
        $linkedChannelIds = array();
        $linkedGroupZoneIds = array();

        $searchInGroupidStr = Sanitizer::SanitizeIntegerCSV($groupIds);
        if (!empty($searchInGroupidStr)) {
            // Using (linked_chapterids > 0 OR linked_channelids > 0 filter as we want linked rows that are chapter or channel specific, note chapters or channel vals can be empty
            $linkedRows = self::DBROGet("SELECT `groupid`, `linked_groupid`, `linked_chapterids`, `linked_channelids` FROM `group_linked_groups` WHERE `groupid` IN ({$searchInGroupidStr}) AND (linked_chapterids > 0 OR linked_channelids > 0) AND `isactive`=1");
            if (count($linkedRows)) {
                $linkedGroupIds = array_filter(array_unique(array_column($linkedRows, 'linked_groupid')));
                if (!empty($linkedGroupIds)) {
                    $linkedGroupIdsString = implode(',', $linkedGroupIds);
                    $linkedGroupRows = self::DBROGet("SELECT `groupid`, `groupname`, overlaycolor, zoneid FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND groupid IN ({$linkedGroupIdsString})");
                }
                $linkedGroupZoneIds = array_filter(array_unique(array_column($linkedGroupRows, 'zoneid')));
                // Note: Since we are using array_filter, it removes chapterids 0 which is our intention.
                $linkedChapterIds = array_filter(array_unique(array_column($linkedRows, 'linked_chapterids')));
                // $linkedChapterIds is an array with a set of chapter ids, flatten it and create single value array
                if (!empty($linkedChapterIds)) {
                    $linkedChapterIds = array_filter(array_unique(explode(',', implode(',', $linkedChapterIds))));
                }
                // Note: Since we are using array_filter, it removes channelids 0 which is our intention.
                $linkedChannelIds = array_filter(array_unique(array_column($linkedRows, 'linked_channelids')));
            }
        }
        return array(
            'linkedRows' => $linkedRows,
            'linkedGroupRows' => $linkedGroupRows,
            'linkedGroupIds' => $linkedGroupIds,
            'linkedChapterIds' => $linkedChapterIds,
            'linkedChannelIds' => $linkedChannelIds,
            'linkedGroupZoneIds' => $linkedGroupZoneIds,
        );
    }

    /**
     * Create New Group
     */
    public static function CreateGroup(
        string $groupname_short,
        string $groupname,
        string $aboutgroup,
        string $coverphoto,
        string $overlaycolor,
        string $from_email_label,
        string $regionid,
        string $groupicon,
        string $permatag,
        string $overlaycolor2,
        string $sliderphoto,
        int    $show_overlay_logo,
        string $group_category,
        string $replyto_email,
        int    $group_type = 0,
        string $app_sliderphoto = '',
        string $app_coverphoto = '',
        int    $show_app_overlay_logo = 1,
        string $tagids = "0",
        int    $group_category_id = 0,
        string $attributes = ""
    )
    {
        global $_COMPANY, $_ZONE, $_USER;

        $groupname = Sanitizer::SanitizeGroupName($groupname);
        $groupname_short = Sanitizer::SanitizeGroupName($groupname_short);
        $permatag = substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(12 / strlen($x)))), 1, 12);

        if ($group_category_id == 0) { // 0 is not a valid value, if 0 is provided then use the default group category row for the zone.
            $group_category_id = (int)Group::GetDefaultGroupCategoryRow()['categoryid'];
        }

        $retVal = self::DBInsertPS("INSERT INTO `groups` (`companyid`, `zoneid`, `addedby`,`groupname_short`, `groupname`, `aboutgroup`, `coverphoto`, `overlaycolor`, `from_email_label`,`regionid`,`groupicon`,`permatag`, `overlaycolor2`,`sliderphoto`,`show_overlay_logo`,`group_category`,`addedon`, `modifiedon`, `isactive`,`replyto_email`,`group_type`,`app_sliderphoto`,`app_coverphoto`,`show_app_overlay_logo`,`tagids`, `attributes`,`categoryid`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW(),?,?,?,?,?,?,?,?,?)", 'iiixxxssxsssssisisissixxi', $_COMPANY->id(), $_ZONE->id(), $_USER->id(), $groupname_short, $groupname, $aboutgroup, $coverphoto, $overlaycolor, $from_email_label, $regionid, $groupicon, $permatag, $overlaycolor2, $sliderphoto, $show_overlay_logo, $group_category, 0, $replyto_email,$group_type,$app_sliderphoto,$app_coverphoto,$show_app_overlay_logo,$tagids,$attributes,$group_category_id);

        if ($retVal) {
            self::LogObjectLifecycleAudit('create', 'group', $retVal, 0);
        }
        return $retVal;
    }

    /**
     * Update group
     */
    public static function UpdateGroup(int $groupid, string $groupname_short, string $groupname, string $coverphoto, string $overlaycolor, string $from_email_label, string $regionid, string $groupicon, string $permatag, string $overlaycolor2, string $sliderphoto, int $show_overlay_logo, string $replyto_email, int $group_category_id, string $app_sliderphoto='', string $app_coverphoto='', int $show_app_overlay_logo=1, string $tagids = '0'){
        global $_COMPANY, $_ZONE;

        $groupname = Sanitizer::SanitizeGroupName($groupname);
        $groupname_short = Sanitizer::SanitizeGroupName($groupname_short);

        //$retVal = self::DBUpdatePS("UPDATE `groups` SET `groupname_short`=?, `groupname`=?, `coverphoto`=?,`overlaycolor`=?,`regionid`=?,`groupicon`=?,`from_email_label`=?,`permatag`=?,`overlaycolor2`=?,`sliderphoto` =?,`show_overlay_logo`=?,`modifiedon`=now(),`replyto_email`=?,`app_sliderphoto`=?, `app_coverphoto`=?, `show_app_overlay_logo`=?,`tagids`=? where `groupid`=? AND `companyid`=? AND `zoneid`=?", 'xxssssxsssisssixiii', $groupname_short, $groupname, $coverphoto, $overlaycolor, $regionid, $groupicon, $from_email_label, $permatag, $overlaycolor2, $sliderphoto, $show_overlay_logo, $replyto_email,$app_sliderphoto, $app_coverphoto, $show_app_overlay_logo,$tagids,$groupid, $_COMPANY->id(), $_ZONE->id());
        $retVal = self::DBUpdatePS("UPDATE `groups` SET `groupname_short`=?, `groupname`=?, `coverphoto`=?,`overlaycolor`=?,`regionid`=?,`groupicon`=?,`from_email_label`=?,`overlaycolor2`=?,`sliderphoto` =?,`show_overlay_logo`=?,`modifiedon`=now(),`replyto_email`=?,`app_sliderphoto`=?, `app_coverphoto`=?, `show_app_overlay_logo`=?,`tagids`=?, `categoryid`=? where `groupid`=? AND `companyid`=? AND `zoneid`=?", 'xxssssxssisssixiiii', $groupname_short, $groupname, $coverphoto, $overlaycolor, $regionid, $groupicon, $from_email_label, $overlaycolor2, $sliderphoto, $show_overlay_logo, $replyto_email,$app_sliderphoto, $app_coverphoto, $show_app_overlay_logo,$tagids,$group_category_id,$groupid, $_COMPANY->id(), $_ZONE->id());

        $_COMPANY->expireRedisCache("GRP:{$groupid}");
        if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'group', $groupid, 0);

        }
        return $retVal;
    }

    /**
     * @param int $regionid
     * @param string $zoneids  (if want to include groups by region of given zoneids)
     * @return Group IDs | array
     */
    public static function GetGroupsByRegion (int $regionid,string $zoneids ='') {
        $data = array();
        global $_COMPANY,$_ZONE; /* @var Company $_COMPANY */

        if ($regionid) {

            $zoneFilter = " AND `zoneid`={$_ZONE->id()}";
            $zoneids = Sanitizer::SanitizeIntegerCSV($zoneids);
            if(!empty($zoneids)){
                $zoneids = $_ZONE->id().','.$zoneids;
                $zoneFilter = " AND `zoneid` IN({$zoneids})";
            }
            $data = self::DBGet("SELECT `groupid` FROM `groups` WHERE `companyid`='{$_COMPANY->id()}' {$zoneFilter} AND FIND_IN_SET({$regionid},`regionid`)");
        }
        return $data;
    }

    /**
     * @Depricated: Note use Group::BuildFromEmailLabel method instead as it is more versatile.
     * Returns the from email label that should be used if chapterid and channelid are set
     * @param string $chapterids
     * @param int $channelid
     * @return string
     */
    public function getFromEmailLabel(string $chapterids = '0', int $channelid = 0): string
    {
        $from = $this->val('from_email_label');
        if ($channelid) {
            $channel = $this->getChannel($channelid);
            if (!empty($channel) && !empty($from)) {
                $from = $channel['channelname'] . ' - ' . $from;
            }
        }

        if ($chapterids !='0') {
            $chapterids = explode(',',$chapterids);
            $fromChapter = array();
            foreach($chapterids as $chapterid){
                $chapter = $this->getChapter($chapterid);
                if (!empty($chapter) && !empty($from)) {
                    $fromChapter[] = $chapter['chaptername'];
                }
            }
            if (!empty($fromChapter)){
                if (count($fromChapter) < 3) {
                    $from = $from . ' [' . implode(', ', $fromChapter) . ']';
                } else {
                    $from = $from . ' ['. $fromChapter[0] .', '. $fromChapter[1] . ', +'. (count($fromChapter)-2) .' more]';
                }
            }
        }

        return $from;
    }

    /**
     * This method build email from label from groupids, chapterids and channelid. The format of Email label is
     * Channel Name - Group Names [Chapter Names].
     * First channel name, upto three group names and upto three chapter names are listed in in the label.
     * If not from names from the groups or chapters/channel names can be determind then zone email_from_label is returned.
     * @param string $groupids_csv
     * @param string $chapterids_csv
     * @param string $channelids_csv
     * @return string
     */
    public static function BuildFromEmailLabel(string $groupids_csv = '0', string $chapterids_csv = '0', string $channelids_csv = '0'): string
    {
        global $_COMPANY, $_ZONE;
        $from = '';

        // Step 1 - Build the Group Part
        $groupids_csv = Sanitizer::SanitizeIntegerCSV($groupids_csv);
        if (!empty($groupids_csv)) {
            $group_rows = self::DBROGet("SELECT IF(`from_email_label`='' ,`groupname_short`, `from_email_label`) as `from_email_label`, `groupname` FROM `groups` WHERE companyid={$_COMPANY->id()} AND isactive=1 AND groupid IN ({$groupids_csv}) ORDER BY groupname");
            $group_labels = array_column($group_rows, 'from_email_label');
            $group_labels = Arr::NaturalLanguageJoin($group_labels, '&', 3, ' more '. $_COMPANY->getAppCustomization()['group']['name-short']);
            $from = $group_labels;
        }

        // Step 2 - Next add Channel prefix.
        $channelids_csv = Sanitizer::SanitizeIntegerCSV($channelids_csv);
        if (!empty($channelids_csv)) {
            $channel_rows = self::DBROGet("SELECT `channelname` FROM `group_channels` WHERE companyid={$_COMPANY->id()} AND isactive=1 AND channelid IN ({$channelids_csv})");
//            if (!empty($channel_rows) && !empty($from)) {
            if (!empty($channel_rows)) {
                $from = $channel_rows[0]['channelname'] . ' - ' . $from;
                $from = trim($from,' -');
            }
        }

        // Step 3 - Next add Chapter Suffix
        $chapterids_csv = Sanitizer::SanitizeIntegerCSV($chapterids_csv);
        if (!empty($chapterids_csv)) {
            $chapter_rows = self::DBROGet("SELECT `chaptername` FROM `chapters` WHERE companyid={$_COMPANY->id()} AND isactive=1 AND chapters.chapterid in ({$chapterids_csv}) ORDER BY chaptername");
            $chapter_names = array_column($chapter_rows, 'chaptername');

            $chapter_labels = Arr::NaturalLanguageJoin($chapter_names, '&', 3, ' more '. $_COMPANY->getAppCustomization()['chapter']['name-short']);
            if (!empty($chapter_labels)) {
                $from = $from . " [{$chapter_labels}]";
            }
        }

        return empty($from) ? $_ZONE->val('email_from_label') : $from;
    }



    public function inviteGroupToCollaborateOnTopic (string $topicType, int $topicId, string $approversEmails, string $chapterName = '')
    {
        global $_COMPANY, $_ZONE, $_USER;

        if (!$topicId){
            return;
        }
        $baseurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));
        $topicTitle = '';
        $topic = null;
        if ($topicType == 'EVT') {
            $topic = Event::GetEvent($topicId);
            $topicURL = $baseurl . 'eventview?id=' . $_COMPANY->encodeId($topicId);
            $topicTitle = $topic->val("eventtitle");
        }
        
        if (!$topic ){
            return;
        }

        $topicEnglish = Teleskope::TOPIC_TYPES_ENGLISH[$topicType];
        $subject = $topicEnglish." Collaboration Request";
        $app_type = $_ZONE->val('app_type');

        if (!empty($approversEmails)) {
            $reply_addr = $_USER->val('email') ?: $this->val('replyto_email');
            $from = $this->val('from_email_label') . " Collaboration Request";
            $emails = $approversEmails;
            $msg = "<p>Hi,</p>";
            $msg .= "<p>{$this->val('groupname')}{$chapterName} has been invited to collaborate on <b><a href='{$topicURL}'>{$topicTitle}</a></b> ".strtolower($topicEnglish).".<p>";
            $msg .= "<p>Please follow this link, you will find the option to accept this collaboration.</p><br>";
            $msg .= "<p>Thank you for your attention to this matter</p>";
            $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
            $emesg = str_replace('#messagehere#', $msg, $template);
            $_COMPANY->emailSend2($from, $emails, $subject, $emesg, $app_type, $reply_addr);
        }
    }

    public function notifyCollaborationDenial($eventId, $deniedByUserId) {
        global $_COMPANY, $_USER, $_ZONE;
        
        $event = Event::GetEvent($eventId);
        if (!$event) {
            return false;
        }
        
        // Get the event creator (the one who sent the collaboration request)
        $eventCreator = User::GetUser($event->val('userid'));
        if (!$eventCreator) {
            return false;
        }
        
        // Get the user who denied the request
        $deniedByUser = User::GetUser($deniedByUserId);
        if (!$deniedByUser) {
            return false;
        }
        
        // Get group information for context
        $group = Group::GetGroup($this->id);
        if (!$group) {
            return false;
        }
        
        // Prepare email content
        $subject = "Collaboration Request Denied - " . $event->val('eventtitle');
        
        $message = "Hello " . $eventCreator->getFullName() . ",\n\n";
        $message .= "Your collaboration request for the event \"" . $event->val('eventtitle') . "\" has been denied.\n\n";
        $message .= "Event: " . $event->val('eventtitle') . "\n";
        $message .= "Group: " . $group->val('groupname') . "\n";
        $message .= "Denied by: " . $deniedByUser->getFullName() . "\n\n";
        $message .= "If you have any questions about this decision, please contact the group administrators.\n\n";
        $message .= "Best regards,\n";
        $message .= $group->val('groupname') . " Team";
        
        // Get email template
        $emailTemplate = $_COMPANY->getEmailTemplateForNonMemberEmails($subject, $message);
        
        // Send email notification
        $retVal = $_COMPANY->emailSend2(
            $eventCreator->val('email'),
            $emailTemplate['subject'],
            $emailTemplate['message'],
            $deniedByUser->val('email') // Reply to the user who denied
        );
        
        return $retVal;
    }

    //Get Group Custom Tabs List.
    public function getGroupCustomTabs (bool $fetchActiveOnly = true) {
        global $_COMPANY, $_ZONE, $_USER;
        $status_filter = '';
        if ($fetchActiveOnly) {
            $status_filter = 'AND `isactive`=1';
        }

        return self::DBGet("SELECT * FROM `group_tabs` WHERE  `companyid`='{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' AND `groupid`='{$this->id}' {$status_filter} ");
    }

    //Get Single Custom Tab.
    public function getGroupCustomTabDetail(int $tabid)
    {
        global $_COMPANY, $_ZONE, $_USER;

        $row = null;
        $t =  self::DBGet("SELECT * FROM `group_tabs` WHERE  `companyid`='{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' AND `groupid`='{$this->id}' AND tabid='{$tabid}' ");

        if (!empty($t)){
            $row = $t[0];
        }
        return $row;
    }

    //Add or Update Custom group tabs.
    public function addUpdateGroupCustomTabs (int $tabid, string $tabtype, string $tabname, string $tabhtml) {
        global $_COMPANY, $_ZONE, $_USER;

        if($tabid >0){
            $tabDetail = $this->getGroupCustomTabDetail($tabid);
            if (!empty($tabDetail)) {
                return self::DBUpdatePS("UPDATE group_tabs SET tab_type=?, tab_name=?,`tab_html`=? WHERE companyid=? AND (zoneid=? AND tabid=? AND groupid=?)", 'xxxiiii', $tabtype, $tabname, $tabhtml, $_COMPANY->id(), $_ZONE->id(), $tabDetail['tabid'], $this->id);
            }
        }else{
            return self::DBInsertPS("INSERT INTO `group_tabs`( `companyid`, `zoneid`, `groupid`, `tab_type`, `tab_name`, `tab_html`, `createdby`) VALUES (?,?,?,?,?,?,?)", 'iiixxxi',$_COMPANY->id(), $_ZONE->id(), $this->id, $tabtype, $tabname, $tabhtml, $_USER->id());
        }
    }

    //Activate or Deactivate Custom group tabs.
    public  function changeTabStatus (int $tabid, int $status) {
        global $_COMPANY, $_ZONE;
        $retVal = self::DBUpdate("UPDATE `group_tabs` SET `isactive`='{$status}' WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `groupid`={$this->id} AND`tabid` ='{$tabid}'");
        return $retVal;
    }

    //Delete Custom group tabs.
    public  function deleteTabs (int $tabid) {
        global $_COMPANY, $_ZONE;
        return self::DBMutate("DELETE FROM `group_tabs` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `groupid`={$this->id} AND `tabid`={$tabid}");
    }


     /**
     * @return int
     */
    public function deleteGroupPermanently(): int
    {
        global $_COMPANY, $_ZONE;
        $result = 0;

        // 30 days after creation, group can be self deleted only after 5 days of wait period after the last state change
        
        $modified_on_plus_five_days = strtotime($this->val('modifiedon')) + 86400 * 5;
        $created_on_plus_thirty_days = strtotime($this->val('addedon')) + 86400 * 30;
        if ((time() > $created_on_plus_thirty_days) && (time() < $modified_on_plus_five_days)) {
            return 0;
        }

        // Check if the group has budgets, chapters or channels. If so do not allow delete of the group.
        $hasBudgets = (int)self::DBGet("SELECT count(1) AS cnt FROM `budgets_v2` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND budget_amount > 0.0000")[0]['cnt'];

        
        $hasChapters = (int)self::DBGet("SELECT count(1) AS cnt FROM `chapters` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id}")[0]['cnt'];
        $hasChannels = (int)self::DBGet("SELECT count(1) AS cnt FROM `group_channels` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id}")[0]['cnt'];
        if ($hasBudgets || $hasChapters || $hasChannels) {
            return 0;
        }

        $groupData = self::DBGet("SELECT groupid FROM `groups` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `groupid`='{$this->id}' AND isactive!=1");
        if(!empty($groupData)){
            self::DBMutate("DELETE FROM `budgets_other_funding` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id}");
            self::DBMutate("DELETE FROM `budgetuses` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id}");
            self::DBMutate("DELETE FROM `budget_requests` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id}");

            // Delete events and related data
            $e_rows = self::DBGet("SELECT * FROM `events` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id}");
            foreach ($e_rows as $e_row) {
                $e = Event::ConvertDBRecToEvent($e_row);
                $e ?-> deleteIt();
            }

            // Remove groupid from collaborative events
            self::DBMutate("UPDATE `events` SET `collaborating_groupids` = TRIM(BOTH ',' FROM REPLACE(CONCAT(',',collaborating_groupids, ','),',{$this->id},',',')) WHERE `companyid`={$_COMPANY->id()} AND groupid=0 and FIND_IN_SET($this->id,`collaborating_groupids`)");
            self::DBMutate("UPDATE `events` SET `collaborating_groupids_pending` = TRIM(BOTH ',' FROM REPLACE(CONCAT(',',collaborating_groupids_pending, ','),',{$this->id},',',')) WHERE `companyid`={$_COMPANY->id()} AND groupid=0 and FIND_IN_SET($this->id,`collaborating_groupids_pending`)");

            // Delete post and related data
            $p_rows = self::DBGet("SELECT * FROM `post` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id}");
            foreach ($p_rows as $p_row) {
                $p = Post::ConvertDBRecToPost($p_row);
                $p ?-> deleteIt();
            }

            // Delete newsletters
            $n_rows = self::DBGet("SELECT * FROM `newsletters` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id}");
            foreach ($n_rows as $n_row) {
                $n = Newsletter::ConvertDBRecToNewsletter($n_row);
                $n ?-> deleteIt();
            }

            // Delete  surveys and responses
            $s_rows = self::DBGet("SELECT * FROM `surveys_v2` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id}");
            foreach ($s_rows as $s_row) {
                $s = Survey2::ConvertDBRecToSurvey2($s_row);
                $s ?-> deleteIt();
            }

            // Delete albums
            $a_rows = self::DBGet("SELECT * FROM `albums` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id}");
            foreach ($a_rows as $a_row) {
                $a = Album::ConvertDBRecToAlbum($a_row);
                $a ?-> deleteIt();
            }

            // DELETE Discussions
            $d_rows = self::DBGet("SELECT * FROM `discussions` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id}");
            foreach ($d_rows as $d_row) {
                $d = Discussion::ConvertDBRecToDiscussion($d_row);
                $d ?-> deleteIt();
            }

            // Delete from Group Resource
            $gr_rows = self::DBGet("SELECT * FROM `group_resources` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id}");
            foreach ($gr_rows as $gr_row) {
                $gr = Resource::ConvertDBRecToResource($gr_row);
                $gr ?-> deleteIt();
            }

            // Delete from Message only for exact matches.
            $msg_rows = self::DBGet("SELECT * FROM `messages` WHERE `companyid`={$_COMPANY->id()} AND groupids='{$this->id}'");
            foreach ($msg_rows as $msg_row) {
                $ms = Message::ConvertDBRecToMessage($msg_row);
                $ms ?-> deleteIt();
            }

            // Delete Integrations
            foreach(GroupIntegration::GetGroupIntegrationsByExactScope($this->id,0,0) as $group_integration) {
                $group_integration ?-> deleteIt();
            }

            // Delete Groups
            self::DBMutate("DELETE FROM `groupmembers` WHERE `groupid`={$this->id}");
            self::DBMutate("DELETE FROM `memberinvites` WHERE `groupid`={$this->id}");
            self::DBMutate("DELETE FROM `groupleads` WHERE `groupid`={$this->id}");
            self::DBMutate("DELETE FROM `leadsinvites` WHERE `groupid`={$this->id}");
            self::DBMutate("DELETE FROM `group_linked_groups` WHERE `groupid`={$this->id}");
            self::DBMutate("DELETE FROM `group_linked_groups` WHERE `linked_groupid`={$this->id}");
            self::DBMutate("DELETE FROM `group_communications` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id}");
            self::DBMutate("DELETE FROM `group_tabs` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id}");

            // Careful .... run only for the group.
            self::DBMutate("DELETE FROM `stats_groups_daily_count` WHERE companyid={$_COMPANY->id()} AND groupid={$this->id}");

             // run at the end
            $result = self::DBMutate("DELETE FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND `groupid`={$this->id}");

            if ($result) {
                self::LogObjectLifecycleAudit('delete', 'groups', $this->id(), 0);
            }
        }

        $_COMPANY->expireRedisCache("GRP:{$this->id}");

        return $result;
    }
    /**
     * @param int $chapterid
     * @return int
     */
    public function deleteChapterPermanently(int $chapterid)
    {
        global $_COMPANY, $_ZONE, $_USER;

        $status = 0;
        $chapter = self::DBGet("SELECT chapterid FROM `chapters` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `groupid`='{$this->id}' AND `chapterid`='{$chapterid}' AND isactive!=1");
        if (!empty($chapter)) {
            self::DBMutate("DELETE FROM `chapterleads` WHERE `chapterid`={$chapterid}");
            self::DBMutate("DELETE FROM `budgets_other_funding` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND `chapterid`={$chapterid}");
            self::DBMutate("DELETE FROM `budgets_v2` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND `chapterid`={$chapterid}");
            self::DBMutate("DELETE FROM `budgetuses` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND `chapterid`={$chapterid}");
            self::DBMutate("DELETE FROM `budget_requests` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND `chapterid`={$chapterid}");
            self::DBMutate("DELETE FROM `group_communications` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND `chapterid`={$chapterid}");
            self::DBMutate("DELETE FROM `group_tabs` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND `chapterid`={$chapterid}");

            // Delete events and related data
            self::DBMutate("DELETE eventjoiners FROM `events` LEFT JOIN eventjoiners using (eventid) WHERE events.companyid={$_COMPANY->id()} AND events.groupid={$this->id} AND events.chapterid={$chapterid} AND channelid='0'");
            self::DBMutate("DELETE event_speakers FROM `events` LEFT JOIN event_speakers using (eventid) WHERE events.companyid={$_COMPANY->id()} AND events.groupid={$this->id} AND events.chapterid={$chapterid} AND channelid='0'");
            self::DBMutate("DELETE event_volunteers FROM `events` LEFT JOIN event_volunteers using (eventid) WHERE events.companyid={$_COMPANY->id()} AND events.groupid={$this->id} AND events.chapterid={$chapterid} AND channelid='0'");
            self::DBMutate("DELETE event_reminder_history FROM `events` LEFT JOIN event_reminder_history using (eventid) WHERE events.companyid={$_COMPANY->id()} AND events.groupid={$this->id} AND events.chapterid={$chapterid} AND channelid='0'");
            self::DBMutate("DELETE FROM `events` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND chapterid={$chapterid} AND channelid='0'"); // Delete chapter specific events only
            self::DBMutate("UPDATE `events` SET chapterid='0' WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND chapterid={$chapterid}"); // For remaining chapter events set chapter to zero

            // Delete post and related data
            $p_rows = self::DBGet("SELECT * FROM `post` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND chapterid={$chapterid} AND channelid=0"); // chapter specific post only
            foreach ($p_rows as $p_row) {
                $p = Post::ConvertDBRecToPost($p_row);
                $p->deleteIt();
            }
            self::DBMutate("UPDATE `post` SET chapterid='0' WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND chapterid={$chapterid}"); // For remaining chapter posts set chapter to zero

            // Trim this chapterid from comma separated field value
            self::DBMutate("UPDATE `newsletters` SET `chapterid`= TRIM(BOTH ',' FROM REPLACE( REPLACE(CONCAT(',',REPLACE(`chapterid`, ',', ',,'), ','),',{$chapterid},', ''), ',,', ',')) WHERE `companyid`='{$_COMPANY->id}' AND `zoneid`='{$_ZONE->id()}' AND `groupid`='{$this->id}' AND FIND_IN_SET('{$chapterid}',`chapterid`)");
            // Delete from newsletters where chapterid blank after trim and if channelids is 0
            self::DBMutate("DELETE FROM `newsletters` WHERE `companyid`='{$_COMPANY->id}' AND `zoneid`='{$_ZONE->id()}' AND `groupid`='{$this->id}' AND chapterid='' AND channelid='0'");
            // Set default value to 0 of chapterid blank after trim
            self::DBMutate("UPDATE `newsletters` SET `chapterid`= '0' WHERE `companyid`='{$_COMPANY->id}' AND `zoneid`='{$_ZONE->id()}' AND `groupid`='{$this->id}' AND chapterid=''");

            self::DBMutate("DELETE survey_responses_v2 FROM `surveys_v2` LEFT JOIN survey_responses_v2 USING (surveyid) WHERE surveys_v2.companyid={$_COMPANY->id()} AND surveys_v2.groupid={$this->id} AND surveys_v2.chapterid={$chapterid}");
            self::DBMutate("DELETE FROM `surveys_v2` WHERE companyid={$_COMPANY->id()} AND groupid={$this->id} AND chapterid={$chapterid}");

            // Group Resource
            self::DBMutate("DELETE FROM `group_resources` WHERE `companyid`='{$_COMPANY->id}' AND `zoneid`={$_ZONE->id()} AND `groupid`={$this->id} AND `chapterid`={$chapterid} AND channelid=0 ");

            // Trim this chapterid from comma separated field value
            self::DBMutate("UPDATE `groupmembers` SET `chapterid`= TRIM(BOTH ',' FROM REPLACE( REPLACE(CONCAT(',',REPLACE(`chapterid`, ',', ',,'), ','),',{$chapterid},', ''), ',,', ',')) WHERE `groupid`='{$this->id}' AND FIND_IN_SET('{$chapterid}',`chapterid`)");
            // Set default value to 0 of chapterid is blank after trim
            self::DBMutate("UPDATE `groupmembers` SET `chapterid`= '0' WHERE `groupid`='{$this->id}' AND chapterid='' ");

            // Trim this linked_chapterids from comma separated field value
            self::DBMutate("UPDATE `group_linked_groups` SET `linked_chapterids`= TRIM(BOTH ',' FROM REPLACE( REPLACE(CONCAT(',',REPLACE(`linked_chapterids`, ',', ',,'), ','),',{$chapterid},', ''), ',,', ',')) WHERE `groupid`='{$this->id}' AND FIND_IN_SET('{$chapterid}',`linked_chapterids`)");
            // Set default value to 0 of linked_chapterids blank after trim
            self::DBMutate("UPDATE `group_linked_groups` SET `linked_chapterids`='0' WHERE `groupid`='{$this->id}' AND linked_chapterids=''");

            // Trim this chapterids from comma separated field value
            self::DBMutate("UPDATE `messages` SET `chapterids`= TRIM(BOTH ',' FROM REPLACE( REPLACE(CONCAT(',',REPLACE(`chapterids`, ',', ',,'), ','),',{$chapterid},', ''), ',,', ',')) WHERE `companyid`='{$_COMPANY->id}' AND `zoneid`='{$_ZONE->id()}' AND FIND_IN_SET('{$chapterid}',`chapterids`)");

            $status = self::DBMutate("DELETE FROM `chapters` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `groupid`={$this->id} AND `chapterid`={$chapterid}");
        }
        $_COMPANY->expireRedisCache("GRP:{$this->id}");
        $_COMPANY->expireRedisCache("GRP_CHP_LST:{$this->id}");
        if ($status) {
            self::LogObjectLifecycleAudit('delete', 'chapter', $chapterid, 0);
        }
        return $status;
    }

    /**
     * @param int $channelid
     * @return int
     */
    public function deleteChannelPermanently(int $channelid): int
    {
        global $_COMPANY, $_ZONE, $_USER;

        $status = 0;
        $channel = self::DBGet("SELECT channelid FROM `group_channels` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `groupid`='{$this->id}' AND `channelid`='{$channelid}' AND isactive!=1");
        if (!empty($channel)) {
            self::DBMutate("DELETE FROM `group_channel_leads` WHERE `channelid`={$channelid}");
            //self::DBMutate("DELETE FROM `budgets_other_funding` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND `channelid`={$channelid}");
            //self::DBMutate("DELETE FROM `budgets_v2` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND `channelid`={channelid}");
            //self::DBMutate("DELETE FROM `budgets_v2` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND `channelid`={channelid}");
            self::DBMutate("DELETE FROM `budgetuses` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND `channelid`={$channelid}");
            //self::DBMutate("DELETE FROM `budget_requests` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND `channelid`={$channelid}");
            self::DBMutate("DELETE FROM `group_communications` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND `channelid`={$channelid}");
            self::DBMutate("DELETE FROM `group_tabs` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND `channelid`={$channelid}");

            // Delete events and related data
            self::DBMutate("DELETE eventjoiners FROM `events` LEFT JOIN eventjoiners using (eventid) WHERE events.companyid={$_COMPANY->id()} AND events.groupid={$this->id} AND events.channelid={$channelid} AND chapterid='0'");
            self::DBMutate("DELETE event_speakers FROM `events` LEFT JOIN event_speakers using (eventid) WHERE events.companyid={$_COMPANY->id()} AND events.groupid={$this->id} AND events.channelid={$channelid} AND chapterid='0'");
            self::DBMutate("DELETE event_volunteers FROM `events` LEFT JOIN event_volunteers using (eventid) WHERE events.companyid={$_COMPANY->id()} AND events.groupid={$this->id} AND events.channelid={$channelid} AND chapterid='0'");
            self::DBMutate("DELETE event_reminder_history FROM `events` LEFT JOIN event_reminder_history using (eventid) WHERE events.companyid={$_COMPANY->id()} AND events.groupid={$this->id} AND events.channelid={$channelid} AND chapterid='0'");
            self::DBMutate("DELETE FROM `events` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND `channelid`={$channelid} AND chapterid='0'"); // Delete channel specific events only
            self::DBMutate("UPDATE `events` SET chapterid='0' WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND channelid={$channelid}"); // For remaining channel events set channelid to zero

            // Delete post and related data
            $p_rows = self::DBGet("SELECT * FROM `post` WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND channelid={$channelid}  AND chapterid='0'"); // Delete channel specific post only
            foreach ($p_rows as $p_row) {
                $p = Post::ConvertDBRecToPost($p_row);
                $p->deleteIt();
            }
            self::DBMutate("UPDATE `post` SET channelid='0' WHERE `companyid`={$_COMPANY->id()} AND groupid={$this->id} AND channelid={$channelid}"); // For remaining channel posts set channel to zero

            // Delete from newsletters only if chapterid is zero... note chapterid is int so this is the correct logic instead of replace/trim
            self::DBMutate("DELETE FROM `newsletters` WHERE `companyid`={$_COMPANY->id} AND `zoneid`={$_ZONE->id()} AND `groupid`={$this->id} AND channelid={$channelid} AND chapterid='0'");

            self::DBMutate("DELETE survey_responses_v2 FROM `surveys_v2` LEFT JOIN survey_responses_v2 USING (surveyid) WHERE surveys_v2.companyid={$_COMPANY->id()} AND surveys_v2.groupid={$this->id} AND surveys_v2.channelid={$channelid}");
            self::DBMutate("DELETE FROM `surveys_v2` WHERE companyid={$_COMPANY->id()} AND groupid={$this->id} AND channelid={$channelid}");

            // Group Resource
            self::DBMutate("DELETE FROM `group_resources` WHERE `companyid`='{$_COMPANY->id}' AND `zoneid`={$_ZONE->id()} AND `groupid`={$this->id} AND `channelid`={$channelid} AND chapterid=0 ");

            // Trim this channelids from comma separated field value
            self::DBMutate("UPDATE `groupmembers` SET `channelids`= TRIM(BOTH ',' FROM REPLACE( REPLACE(CONCAT(',',REPLACE(`channelids`, ',', ',,'), ','),',{$channelid},', ''), ',,', ',')) WHERE `groupid`={$this->id} AND FIND_IN_SET('{$channelid}',`channelids`)");
            // Set default value to 0 of channelids is blank after trim
            self::DBMutate("UPDATE `groupmembers` SET `channelids`= '0' WHERE `groupid`='{$this->id}' AND channelids='' ");

            // Trim this linked_channelids from comma separated field value
            self::DBMutate("UPDATE `group_linked_groups` SET `linked_channelids`= TRIM(BOTH ',' FROM REPLACE( REPLACE(CONCAT(',',REPLACE(`linked_channelids`, ',', ',,'), ','),',{$channelid},', ''), ',,', ',')) WHERE `groupid`={$this->id} AND FIND_IN_SET('{$channelid}',`linked_channelids`)");
            // Set default value to 0 of linked_channelids blank after trim
            self::DBMutate("UPDATE `group_linked_groups` SET `linked_channelids`= '0' WHERE `groupid`='{$this->id}' AND linked_channelids='' ");

            // Trim this channelids from comma separated field value
            self::DBMutate("UPDATE `messages` SET `channelids`= TRIM(BOTH ',' FROM REPLACE( REPLACE(CONCAT(',',REPLACE(`channelids`, ',', ',,'), ','),',{$channelid},', ''), ',,', ',')) WHERE `companyid`='{$_COMPANY->id}' AND `zoneid`='{$_ZONE->id()}' AND FIND_IN_SET('{$channelid}',`channelids`)");

            $status = self::DBMutate(" DELETE FROM `group_channels` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `groupid`='{$this->id}' AND `channelid`='{$channelid}' ");
        }
        $_COMPANY->expireRedisCache("GRP:{$this->id}");
        $_COMPANY->expireRedisCache("GRP_CHL_LST:{$this->id}");
        if ($status) {
            self::LogObjectLifecycleAudit('delete', 'channel', $channelid, 0);

        }
        return $status;
    }

    public function getWhoCanManageGroupBudget(){
        global $_COMPANY, $_ZONE, $_USER;

        $whoCanManageBudget = array();
        $groupLeads = self::DBGet("SELECT `groupleads`.`userid` FROM `groupleads` JOIN grouplead_type ON grouplead_type.typeid=`groupleads`.`grouplead_typeid` AND grouplead_type.allow_manage_budget=1 WHERE grouplead_type.companyid={$_COMPANY->id} AND (groupleads.groupid='{$this->id()}' AND `groupleads`.`isactive`=1 AND  grouplead_type.isactive=1)");

        if (!empty($groupLeads)){
           $groupLeadIds = implode(',',array_column($groupLeads,'userid'));
           $whoCanManageBudget = self::DBGet("SELECT `userid`, `firstname`, `lastname`, `email`, `picture`, `jobtitle` FROM `users` WHERE `companyid`='{$_COMPANY->id()}' AND (`userid` IN({$groupLeadIds}) AND `isactive`='1')");
        }
        return $whoCanManageBudget;
    }

    public function getWhoCanManageChapterBudget(int $chapterid){
        global $_COMPANY, $_ZONE, $_USER;

        $whoCanManageBudget = array();
        $groupLeads = self::DBGet("SELECT `chapterleads`.`userid` FROM `chapterleads` JOIN grouplead_type ON grouplead_type.typeid=`chapterleads`.`grouplead_typeid` AND grouplead_type.allow_manage_budget=1 WHERE grouplead_type.companyid={$_COMPANY->id} AND (chapterleads.groupid='{$this->id()}' AND chapterleads.chapterid='{$chapterid}' AND `chapterleads`.`isactive`=1 AND  grouplead_type.isactive=1)");

        if (!empty($groupLeads)){
           $groupLeadIds = implode(',',array_column($groupLeads,'userid'));
           $whoCanManageBudget = self::DBGet("SELECT `userid`, `firstname`, `lastname`, `email`, `picture` FROM `users` WHERE `companyid`='{$_COMPANY->id()}' AND (`userid` IN({$groupLeadIds}) AND `isactive`='1')");
        }
        return $whoCanManageBudget;
    }

    /**
     * @param string $email
     * @param int $chapterid
     * @param int $channelid
     * @return int 0 on error, 1 on success, 2 if user was already invited and is now reinvited, 3 if user is already a member,
     */
    public function inviteUserToJoinGroup(string $email, int $chapterid, int $channelid): int
    {
        global $_COMPANY, $_ZONE, $_USER;
        $retVal = 1;

        if ($this->val('group_type') == Group::GROUP_TYPE_MEMBERSHIP_DISABLED) {
            return 0; // Membership is disabled for group_type=50
        }

        if ($chapterid < 0) $chapterid = 0;
        if ($channelid < 0) $channelid = 0;

        $invitedUser = User::GetUserByEmail($email);
        if ($invitedUser) {

            // Check for group restriction before any other processing.
            if(!$this->isUserAllowedToJoin($invitedUser->id())){
                return 5; // User restricted.
            }

            $check1 = self::DBGet("SELECT * FROM `groupmembers` WHERE `userid`={$invitedUser->id()} AND `groupid`={$this->id}");

            if (!empty($check1)) {
               $isChapterMember = !($chapterid) || in_array($chapterid, explode(',',$check1[0]['chapterid']));
               $isChannelMember = !($channelid) || in_array($channelid, explode(',',$check1[0]['channelids']));
                if ($isChapterMember && $isChannelMember)
                    return 3; // user is already a member
            }
        }

        $check2 = self::DBGetPS("SELECT * FROM `memberinvites` WHERE `companyid`=? AND `groupid`=? AND `email`=?", 'iix', $_COMPANY->id(), $this->id, $email);
        $memberinviteid = 0;
        if (!empty($check2)) {
            foreach ($check2 as $item) {
                if (($item['chapterid'] == $chapterid) && ($item['channelid'] == $channelid)) {
                    $retVal = 2;
                    $memberinviteid = $check2[0]['memberinviteid'];
                    break;
                }
            }
        }
        if ($memberinviteid){
            self::DBMutate("UPDATE `memberinvites` SET `invitedby`={$_USER->id()},`status`=1 WHERE `companyid`={$_COMPANY->id()} AND `groupid`={$this->id()} AND `memberinviteid`={$memberinviteid}");
        } else {
            $memberinviteid =  self::DBInsertPS("INSERT INTO `memberinvites`( `companyid`, `groupid`, `chapterid`, `channelid`, `email`, `invitedby`, `createdon`, `status`) VALUES (?,?,?,?,?,?,NOW(),1)", 'iiiixi', $_COMPANY->id(),$this->id(),$chapterid, $channelid, $email, $_USER->id());
        }

        $url = Url::GetZoneAwareUrlBase($_ZONE->id()) . 'memberaccept?id=' . $_COMPANY->encodeId($memberinviteid);
        $from = $this->val('from_email_label');
        $app_type = $_ZONE->val('app_type');
        $reply_addr = $this->val('replyto_email');
        $subject = "Invitation to join " . $this->val('groupname');
        $chapter_or_channel = '';
        if ($chapterid && !empty($ch = self::GetChapterName($chapterid, $this->id))) {
            $chapter_or_channel = ' ' . $ch['chaptername'] . ' ' . $_COMPANY->getAppCustomization()['chapter']['name'];
        } elseif ($channelid && !empty($ch = self::GetChannelName($channelid, $this->id))) {
            $chapter_or_channel = ' ' . $ch['channelname'] . ' ' . $_COMPANY->getAppCustomization()['channel']['name'];
        }

        $msg = <<<EOMEOM
        <p>You are invited to join {$_COMPANY->val('companyname')} {$this->val('groupname')} {$_COMPANY->getAppCustomization()['group']['name']}{$chapter_or_channel}. Please click on the link below to join : </p>
        <p>Link:  <a href='{$url}'>{$url}</a></p>
             
        <br>		
        Note: If you have not registered with the {$_COMPANY->val('companyname')} {$_COMPANY->getAppCustomization()['group']['name']} site, then you must first register and then click on the above link again to accept the membership invitation.
        <br>
        <br>		
        <p>Thanks!</p>
        <p>{$this->val('groupname')} Team</p>
EOMEOM;

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $emesg = str_replace('#messagehere#', $msg, $template);

        try {
            if (!$_COMPANY->emailSend2($from, $email, $subject, $emesg, $app_type, $reply_addr)) {
                $retVal = 0;
            }
        } catch (Exception $e) {
            $retVal = 0;
        }
        return $retVal;
    }

    /**
     * @param  string #hastag
     * @return mixed
     * @throws Exception
     */
    public static function GetFeedsByHashtag(int $hashtagid, string $timezone = "")
    {
        global $_COMPANY;
        global $_USER;
        global $_ZONE;
        global $db;

        if (!$hashtagid){
            return array();
        }
        // Get post and events
        $utc_tz = new DateTimeZone('UTC');
        if ($timezone){ // Mobile API
            $local_tz = $timezone;
        } else { // Web protal
            $local_tz = new DateTimeZone($_SESSION['timezone'] ?: 'UTC');
        }

        $events = self::DBGetPS("SELECT content.*,content.publishdate as addedon,groups.groupname,groups.groupname_short,groups.overlaycolor, 0 as pin_to_top FROM `events` content LEFT JOIN `groups` using (groupid) WHERE content.companyid={$_COMPANY->id()} AND (content.zoneid={$_ZONE->id()} AND content.isactive=1 AND FIND_IN_SET(?,content.handleids) AND end>=NOW() AND content.event_series_id !=content.eventid  AND `isprivate`=0 AND content.`eventclass`='event') ORDER BY content.publishdate DESC LIMIT 10", 'i', $hashtagid);


        if (count($events) > 0) {
            for ($e = 0; $e < count($events); $e++) {
                $temp_event = Event::ConvertDBRecToEvent($events[$e]);
                $events[$e]['joinerCount'] = $temp_event->getJoinersCount();
                if ($events[$e]['groupid'] == 0) {
                    $events[$e]['groupname'] = $_COMPANY->getAppCustomization()['group']['groupname0'];
                    $events[$e]['groupname_short'] = $_COMPANY->getAppCustomization()['group']['groupname0'];
                }
                $events[$e]['type'] = '1';
                $events[$e]['joinerData'] = $temp_event->getRandomJoiners(12);
                $events[$e]['localStart'] = (new DateTime($events[$e]['start'], $utc_tz))->setTimezone($local_tz);
                $events[$e]['collaboratedWith'] = null;
                if ($events[$e]['collaborating_groupids']) {
                    $events[$e]['collaboratedWith'] = self::DBGetPS("SELECT `groupid`, `groupname`, `groupname_short`,`overlaycolor`,`groupicon` FROM `groups` WHERE `groupid` IN(" . $events[$e]['collaborating_groupids'] . ") AND `isactive`=1");

                }
            }
        }

        // Note: In the following SQL groups.isactive == NULL is added to capture Admin messages which will not have a matching group (groupid=0)
        $posts = self::DBGetPS("SELECT content.*, content.publishdate as addedon, groups.groupname,groups.groupname_short,groups.overlaycolor FROM `post` content LEFT JOIN `groups` USING (groupid) WHERE content.companyid={$_COMPANY->id()} AND (content.zoneid={$_ZONE->id()} AND content.isactive=1 AND FIND_IN_SET(?,content.handleids)) ORDER BY pin_to_top DESC,`publishdate` DESC LIMIT 10",'i',$hashtagid);

        if (count($posts) > 0) {
            for ($i = 0; $i < count($posts); $i++) {
                if ($posts[$i]['groupid'] == 0) {
                    $posts[$i]['groupname'] = $_COMPANY->getAppCustomization()['group']['groupname0'];
                    $posts[$i]['groupname_short'] = $_COMPANY->getAppCustomization()['group']['groupname0'];
                }
                $posts[$i]['type'] = '2';
            }
        }

        $discussions = self::DBGetPS("SELECT content.*, content.modifiedon as addedon, groups.groupname,groups.groupname_short,groups.overlaycolor FROM `discussions` content LEFT JOIN `groups` USING (groupid) WHERE content.companyid={$_COMPANY->id()} AND (content.zoneid={$_ZONE->id()} AND content.isactive=1 AND FIND_IN_SET(?,content.handleids)) ORDER BY pin_to_top DESC,`modifiedon` DESC LIMIT 10",'i',$hashtagid);

        if (count($discussions) > 0) {
            for ($i = 0; $i < count($discussions); $i++) {
                if ($discussions[$i]['groupid'] == 0) {
                    $discussions[$i]['groupname'] = $_COMPANY->getAppCustomization()['group']['groupname0'];
                    $discussions[$i]['groupname_short'] = $_COMPANY->getAppCustomization()['group']['groupname0'];
                }
                $discussions[$i]['type'] = '4';
            }
        }

        return Arr::OrderBy(array_merge($posts, $events, $discussions), 'pin_to_top', SORT_DESC, 'addedon', SORT_DESC);
    }

    public static function GetChapterListByRegionalHierachies(int $groupid){
        global $_COMPANY,$_ZONE;
        $data = array();
        $allChapters = self::GetChapterList($groupid);
        if (!empty($allChapters)){
            $allRegionids = implode(',',array_column($allChapters,'regionids'));
            // Unique
            $regionids = implode(',',array_unique(explode(',',$allRegionids)));
            // Get Regions
            $regions = self::DbGet("SELECT `regionid`,  `region` FROM `regions` WHERE `companyid`='{$_COMPANY->id()}' AND  `regionid` IN({$regionids}) AND `isactive`='1'");
            $regions  = array_merge($regions,array(array('regionid'=>0,'region'=>'Undefined')));
            foreach($regions as $region){
                $chapters = array();
                foreach($allChapters as $ch){
                    if (in_array($region['regionid'],explode(',',$ch['regionids']))){
                        $chapters[] = $ch;
                    }
                }
                if (!empty($chapters)){
                    $data[$region['region']] = $chapters;
                }
            }
        }
        return $data;
    }

    /********--------------------------------------------*********/
    /******** GROUP TEAM STRUCTURE SECTION - Starts here *********/

    const GROUP_ATTRIBUTES_KEYS = array( // Keys used for JSON consitency
        'team_touch_point_template' => 'team_touch_point_template',
        'team_matching_algorithm_attributes' => 'matching_algorithm_attributes',
        'team_matching_algorithm_parameters' => 'matching_algorithm_parameters',
        'team_action_item_template' => 'team_action_item_template',
        'team_program_type' => 'team_program_type',
        'team_meta_name' => 'team_meta_name',
        'hidden_program_team_tab' => 'hidden_program_team_tab',
        'survey_view_download_permission' => 'survey_view_download_permission',
        'touchpoint_type_configuration' => 'touchpoint_type_configuration',
        'team_inactivity_notification' => 'team_inactivity_notification',
        'can_send_multiple_join_request' => 'can_send_multiple_join_request',
        'is_teams_activated' => 'is_teams_activated',
        'is_teams_frontend_activated' => 'is_teams_frontend_activated',
        'networking_program_start_setting'=> 'networking_program_start_setting',
        'discover_search_attributes' =>'discover_search_attributes',
        'team_request_chapter_selection_setting'=>'team_request_chapter_selection_setting',
        'action_item_configuration' => 'action_item_configuration',
        'team_workflow_setting' => 'team_workflow_setting',

        // Booking setting
        'booking_start_buffer'=>'booking_start_buffer',
        'booking_email_template' =>'booking_email_template',
        'meeting_email_template' => 'meeting_email_template',
        'meeting_cancel_email_template' => 'meeting_cancel_email_template',
        'meeting_reminder_email_template' => 'meeting_reminder_email_template',
        'meeting_mark_as_done_email_template' => 'meeting_mark_as_done_email_template',

        // Recognitions settings
        'recognitions_configuration' => 'recognitions_configuration',

        // Discussions settings
        'discussions_configuration' => 'discussions_configuration', 

        // program Disclaimers
        'program_disclaimer'=>'program_disclaimer',

        // Member restrictions
        'member_restrictions'=>'member_restrictions',
        // Request to join ERG email settings on user join
        'join_request_mail_settings' => 'join_request_mail_settings',

        // Action iteam and Touch point progress bars setting
        'actionitem_touchpoint_progress_bar_setting'=> 'actionitem_touchpoint_progress_bar_setting'
    );

    const ACTION_ITEM_VISIBILITY_SETTING = array(
        'show_to_all'=>'show_to_all',
        'show_to_assignee_and_mentors'=>'show_to_assignee_and_mentors'
    );
    /** Updates attribute column of group. Please note attribute column it JSON encoded, so instead of using this
     * method directly, create helper functions to set JSON values correctly.
     * @param string $attributes
     * @return int
     */
    public function updateGroupAttributes(string $attributes)
    {
        global $_COMPANY;
        $attributes = ($attributes == '[]') ? '{}' : $attributes; // Convert empty to object;
        $retVal = self::DBUpdatePS("UPDATE `groups` SET `attributes`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `groupid`=? AND modifiedon=?", 'xiix', $attributes, $_COMPANY->id(), $this->id, $this->val('modifiedon'));
        $_COMPANY->expireRedisCache("GRP:{$this->id}");
        return $retVal;

    }

    public function getTeamTouchPointTemplate()
    {
        $key = self::GROUP_ATTRIBUTES_KEYS['team_touch_point_template'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return array();
    }

    public function addUpdateTouchPointTemplateItem(int $id, string $title, string $description, int $tat)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['team_touch_point_template'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        $team_touch_point_templates = array();
        if (array_key_exists($key, $attributes)) {
            $team_touch_point_templates = $attributes[$key];
            usort($team_touch_point_templates,function($a,$b) {
                return (intval($a['tat'])<=>intval($b['tat']));
            });
        }

        $newAttribute = array('title' => $title, 'description' => $description, 'tat' => $tat);
        if ($id >= 0) {
            $team_touch_point_templates[$id] = $newAttribute;
        } else {
            $team_touch_point_templates[] = $newAttribute;
        }
        $attributes[$key] = $team_touch_point_templates;
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function deleteTeamTouchPointTemplateItem(int $id)
    {
        global $_COMPANY, $_ZONE;
        $key = self::GROUP_ATTRIBUTES_KEYS['team_touch_point_template'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        $team_touch_point_templates = array();
        if (array_key_exists($key, $attributes)) {
            $team_touch_point_templates = $attributes[$key];
            unset($team_touch_point_templates[$id]);
            $team_touch_point_templates = array_merge(array(), $team_touch_point_templates);
        }

        $attributes[$key] = $team_touch_point_templates;
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function getTeamMatchingAlgorithmAttributes()
    {
        $key = self::GROUP_ATTRIBUTES_KEYS['team_matching_algorithm_attributes'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return array();
    }

    /**
     * Updates the team_matching_algorithm_attributes key in group attributes json
     * @param string $questionJSON if set to empty string, team_matching_algorithm_attributes key is removed from JSON
     * @return int
     */
    public function updateTeamMatchingAlgorithmAttributes(string $questionJSON)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['team_matching_algorithm_attributes'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        if (empty($questionJSON)) {
            unset($attributes[$key]);
        } else {
            $attributes[$key] = json_decode($questionJSON, true);
        }

        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function getTeamMatchingAlgorithmParameters()
    {
        $key = self::GROUP_ATTRIBUTES_KEYS['team_matching_algorithm_parameters'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return array();
    }

    public function updateTeamMatchingAlgorithmParameters(array $primaryParameters, array $customParameters,array $mandatoryPrimaryParameters = array(),array $mandatoryCustomParameters = array(), int $allow_chapter_selection = -1, string $chapter_selection_label='')
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['team_matching_algorithm_parameters'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        $attributes[$key] = array('primary_parameters' => $primaryParameters, 'custom_parameters' => $customParameters,'mandatory_primary_parameters' => $mandatoryPrimaryParameters,'mandatory_custom_parameters' => $mandatoryCustomParameters);

        if (in_array($allow_chapter_selection, array(0,1)) && $chapter_selection_label) {
            $key = self::GROUP_ATTRIBUTES_KEYS['team_request_chapter_selection_setting'];
            $attributes[$key] = array('allow_chapter_selection'=>$allow_chapter_selection,'chapter_selection_label'=>$chapter_selection_label);
        }

        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function updateTeamProgramType(int $program_type_value)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['team_program_type'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        $attributes[$key] = array('value' => $program_type_value);
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function getTeamProgramType()
    {
        if (!isset($this->fields['team_program_type'])) {
            $this->fields['team_program_type'] = 0; // First give it a default value.

            $attributes = $this->val('attributes') ? json_decode($this->val('attributes'), true) : array();
            if(!empty($attributes)){
                $key = self::GROUP_ATTRIBUTES_KEYS['team_program_type'];
                if (array_key_exists($key, $attributes)) {
                    $this->fields['team_program_type'] = $attributes[$key]['value'];
                }
            }
        }
        return $this->fields['team_program_type'] ?? 0;
    }

    public function updateTeamMetaName(string $team_meta_name)
    {
        global $_COMPANY, $_ZONE;

        $key = Self::GROUP_ATTRIBUTES_KEYS['team_meta_name'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        $attributes[$key] = array('value' => $team_meta_name);
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function getTeamMetaName()
    {
        if (!isset($this->fields['team_meta_name'])) {
            $this->fields['team_meta_name'] = 'Team'; // First give it a default value.
            $attributes = json_decode($this->val('attributes'), true) ?? array();
            $key = self::GROUP_ATTRIBUTES_KEYS['team_meta_name'];
            if (array_key_exists($key, $attributes)) {
                $this->fields['team_meta_name'] = $attributes[$key]['value'];
            }
        }
        return $this->fields['team_meta_name'];
    }

    public function getTeamActionItemTemplate()
    {
        $key = self::GROUP_ATTRIBUTES_KEYS['team_action_item_template'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return array();
    }

    public function addUpdateTeamActionItemTemplateItem(int $id, string $title, int $assignedto, int $tat, string $description)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['team_action_item_template'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        $team_action_iteam_templates = array();
        if (array_key_exists($key, $attributes)) {
            $team_action_iteam_templates = $attributes[$key];
            usort($team_action_iteam_templates,function($a,$b) {
                return (intval($a['tat'])<=>intval($b['tat']));
            });
        }

        $newAttribute = array('title' => $title, 'assignedto' => $assignedto, 'tat' => $tat, 'description' => $description);
        if ($id >= 0) {
            $team_action_iteam_templates[$id] = $newAttribute;
        } else {
            $team_action_iteam_templates[] = $newAttribute;
        }
        $attributes[$key] = $team_action_iteam_templates;
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function deleteTeamActionItemTemplateItem(int $id)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['team_action_item_template'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        $team_action_iteam_templates = array();
        if (array_key_exists($key, $attributes)) {
            $team_action_iteam_templates = $attributes[$key];
            unset($team_action_iteam_templates[$id]);
            $team_action_iteam_templates = array_merge(array(), $team_action_iteam_templates);
        }
        $attributes[$key] = $team_action_iteam_templates;
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function updateHiddenProgramTabSetting(array $hidenTabs)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['hidden_program_team_tab'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        $oldHidenTabs = array();
        if (array_key_exists($key, $attributes)) {
            $oldHidenTabs = $attributes[$key];
        }
        $oldHidenTabs[$this->id()] = $hidenTabs;
        $attributes[$key] = $oldHidenTabs;
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function getHiddenProgramTabSetting()
    {
        $key = self::GROUP_ATTRIBUTES_KEYS['hidden_program_team_tab'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                $allHiddenTabs = $attributes[$key];
                if (array_key_exists($this->id(), $allHiddenTabs)) {
                    return $allHiddenTabs[$this->id()];
                }
            }
        }
        return array();
    }

    public function updateSurveyDownloadSetting(int $status)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['survey_view_download_permission'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        $attributes[$key] = array('allowed' => $status);
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function getSurveyDownloadSetting()
    {
        $key = self::GROUP_ATTRIBUTES_KEYS['survey_view_download_permission'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return array();
    }

    public function canDownloadOrViewSurveyReport(): bool
    {
        $check = $this->getSurveyDownloadSetting();
        return $check && ($check['allowed'] == 1);
    }

    /**
     * This method returns all the members of chapters provided. Will use $_COMPANY context for scoping
     * @param string $chapterids a comma seperated list of chapters.
     * @param string $locations
     * @param string $context
     * @return string an array ofchapter members
     */
    public static function GetChapterMembers (string $chapterids, string $locations, string $context='') {
        global $_COMPANY; /* @var Company $_COMPANY */
        global $_ZONE;
        $notificationFilter = '';

        if ($context === 'P') {
            $notificationFilter = " AND `notify_posts`='1'";
        } elseif ($context === 'E') {
            $notificationFilter = " AND `notify_events`='1'";
        } elseif ($context === 'N') {
            $notificationFilter = " AND `notify_news`='1'";
        }

        if (empty($chapterids))
            return array();

        if (empty($locations))
            $locations = "0";

        self::DBGet("SET SESSION group_concat_max_len = 2048000"); //Increase the session limit; default is 1000
        //First extract only valid groupids that belong to this company

        $chapterIdsArray = explode(',',$chapterids);
        $userids = array();
        foreach($chapterIdsArray as $chapterid) {
            // Need to get groupid first to use groupmembers.groupid index
            $groupid_rows = self::DBGet("SELECT groupid FROM chapters WHERE companyid={$_COMPANY->id()} AND chapterid={$chapterid}");
            $groupid = !empty($groupid_rows) ? (int)$groupid_rows[0]['groupid'] : -1; // If groupid row is not found then set groupid to -1 for chapter lookup to always fail
            if ($locations == "0") {
                $u = self::DBGet("SELECT DISTINCT(userid) AS users FROM groupmembers WHERE groupid='{$groupid}' AND groupmembers.isactive=1 AND FIND_IN_SET({$chapterid},chapterid)  {$notificationFilter}");
            } else {
                $u = self::DBGet("SELECT DISTINCT(groupmembers.userid) AS users FROM groupmembers JOIN users ON groupmembers.userid=users.userid WHERE groupid ='{$groupid}' AND groupmembers.isactive=1 AND homeoffice in ({$locations}) AND FIND_IN_SET({$chapterid},chapterid) {$notificationFilter}");
            }
            $ids = array_column($u,'users');
            if (!empty($ids)){
                $userids = array_merge($userids,$ids);
            }
        }
        $userids = array_unique($userids);

        return $userids;
    }

    public static function GetDeltaChapterMembersAsCSV (string $chapterids, int $chapterid, string $locations, string $context='') {
        if (empty($chapterid))
            return '';

        if (empty($locations))
            $locations = "0";

        $usersNew = self::GetChapterMembers($chapterid, $locations, $context);
        if (!empty($chapterids)) {
            $usersPrevious = self::GetChapterMembers($chapterids, $locations, $context);
            $usersNew = array_diff($usersNew, $usersPrevious);
        }
        return implode(',', $usersNew);
    }

    public static function GetChaptersCSV (string $chapterids, int $groupid) {
        global $_COMPANY, $_ZONE;
        if (empty($chapterids)){
            return array();
        }
        $chapters = self::DBROGet("SELECT `chapterid`,`chaptername`,`colour`,`isactive` FROM `chapters` WHERE `companyid`='{$_COMPANY->id()}' AND `groupid`='{$groupid}' AND `chapterid` IN({$chapterids})");
        for($i=0;$i<count($chapters);$i++){
            $chapters[$i]['chaptername'] =  htmlspecialchars_decode($chapters[$i]['chaptername']);
        }
        return $chapters;
    }

    public function getAllMembers (int $chapterid=0, int $channelid=0): array
    {
        global $_COMPANY;
        $filter = '';
        if ($chapterid) {
            $filter .= " AND FIND_IN_SET({$chapterid}, groupmembers.chapterid)";
        }
        if ($channelid) {
            $filter .= " AND FIND_IN_SET({$channelid}, groupmembers.channelids)";
        }
        return self::DBROGet("SELECT users.userid,firstname,lastname,email,groupjoindate,users.isactive FROM `groupmembers` JOIN users USING(userid) WHERE users.companyid={$_COMPANY->id()} AND (groupmembers.groupid={$this->id} AND groupmembers.isactive=1 $filter)");
    }
    /******** GROUP TEAM STRUCTURE SECTION - Ends Here *********/
    /********----------------------------------------- *********/

    public function getConfiguredRecongnitionCustomFields()
    {
        $recognitions_configuration_key = self::GROUP_ATTRIBUTES_KEYS['recognitions_configuration'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            return $attributes[$recognitions_configuration_key]['configured_custom_fields'] ?? array();
        }
        return array();
    }

    public function updateRecognitionCustomFields(int $custom_field_id, string $action)
    {
        global $_COMPANY, $_ZONE;

        $recognitions_configuration_key = self::GROUP_ATTRIBUTES_KEYS['recognitions_configuration'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        $recognition_custom_fields = array();
        if (array_key_exists($recognitions_configuration_key, $attributes)) {
            $recognition_custom_fields = $attributes[$recognitions_configuration_key]['configured_custom_fields'] ?? array();
            if ($action == 'add'){
                $recognition_custom_fields[] = $custom_field_id;
            } else {
                $recognition_custom_fields = array_diff( $recognition_custom_fields, [$custom_field_id]);
                $recognition_custom_fields = array_values($recognition_custom_fields);
            }
        }

        $attributes[$recognitions_configuration_key]['configured_custom_fields'] = $recognition_custom_fields;
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }


    public function updateRecognitionSettings(bool $enable_user_view_recognition, bool $enable_self_recognition, bool $enable_colleague_recognition)
    {
        global $_COMPANY, $_ZONE;

        $recognitions_configuration_key = self::GROUP_ATTRIBUTES_KEYS['recognitions_configuration'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        $attributes[$recognitions_configuration_key]['enable_user_view_recognition'] = $enable_user_view_recognition;
        $attributes[$recognitions_configuration_key]['enable_self_recognition'] = $enable_self_recognition;
        $attributes[$recognitions_configuration_key]['enable_colleague_recognition'] = $enable_colleague_recognition;

        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }






    







    public function updateTouchpointTypeConfiguration(string $touchpointtype, bool $show_copy_to_outlook = false, bool $enable_mentor_scheduler = false, bool $auto_approve_proposals = false)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['touchpoint_type_configuration'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        $enable_mentor_scheduler = $enable_mentor_scheduler && $_COMPANY->getAppCustomization()['my_schedule']['enabled'];

        $attributes[$key] = array('type'=>$touchpointtype, 'show_copy_to_outlook'=>$show_copy_to_outlook, 'enable_mentor_scheduler'=> $enable_mentor_scheduler, 'auto_approve_proposals' => $auto_approve_proposals);
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function getTouchpointTypeConfiguration()
    {
        global $_COMPANY;
        $key = self::GROUP_ATTRIBUTES_KEYS['touchpoint_type_configuration'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                if (!isset($attributes[$key]['show_copy_to_outlook'])){
                    $attributes[$key]['show_copy_to_outlook'] = false;
                }
                if (!isset($attributes[$key]['enable_mentor_scheduler']) || !$_COMPANY->getAppCustomization()['my_schedule']['enabled']){
                    $attributes[$key]['enable_mentor_scheduler'] = false;
                }
                if (!isset($attributes[$key]['auto_approve_proposals'])){
                    $attributes[$key]['auto_approve_proposals'] = false;
                }

                return $attributes[$key];
            }
        }
        return array(
            'type'=>'touchpoint',
            'show_copy_to_outlook' => false,
            'enable_mentor_scheduler' => false,
            'auto_approve_proposals' => false,
        );
    }

    public function deleteGroupJoinRequest(int $requesterid) {
        global $_COMPANY;
        // Set role_survey_response to empty JSON
        return self::DBMutate("DELETE FROM `member_join_requests` WHERE `companyid`='{$_COMPANY->id()}' AND `groupid`='{$this->id()}' AND userid='{$requesterid}'");
    }

    public function deleteTeamJoinRequestSurveyData()
    {
        global $_COMPANY, $_ZONE;
        // Set role_survey_response to empty JSON
        return self::DBMutate("UPDATE `member_join_requests` SET role_survey_response='{}' WHERE `companyid`='{$_COMPANY->id()}' AND `groupid`='{$this->id()}'");
    }

    public function saveTeamInactivityNotificationsSetting(int $notification_days_after, int $notification_frequency)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['team_inactivity_notification'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }


        $attributes[$key] = array('notification_days_after'=>$notification_days_after,'notification_frequency'=>$notification_frequency);
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function getTeamInactivityNotificationsSetting()
    {
        $key = self::GROUP_ATTRIBUTES_KEYS['team_inactivity_notification'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return array('notification_days_after'=>0,'notification_frequency'=>0);
    }


	public static function CleanGroupInvitesAfterNintyDays(): bool
    {
        if ($_COMPANY || $_ZONE) {
            Logger::Log("Calling multi-tenant method from Company or Zone context:" . $_COMPANY->id(), Logger::SEVERITY['FATAL_ERROR']);
            return false; // This job can only be executed if it is called from non company and non zone context. If company is set, exit it.
        }

        $companies = self::DBGet("SELECT `companyid` FROM `companies` WHERE companies.isactive=1");
        // Step 1: Iterate over active companies
        foreach ($companies as $company) {
            // Step 2: Iterate over active groups
            $groups = self::DBGet("SELECT `groupid` FROM `groups` WHERE `companyid`={$company['companyid']} AND `isactive`=1");
            foreach($groups as $group) {
                self::DBMutate("DELETE FROM `memberinvites` WHERE `companyid`='{$company['companyid']}' AND `groupid`='{$group['groupid']}' AND `createdon` < now() - interval 90 DAY ");
            }
        }
        $_ZONE = null;
        $_COMPANY = null;
		return true;
	}

    /**
     * @param int $group_category_id Valid values are based on group categories id. 0 means show all
     * @param int $myGroupsOnly If 1 then only the groups for which the user is member of will be returned.
     * If 0 then all the groups in the zone that match the criteria will be returned. Hidden/Invite only groups are hidden
     * @return array Array of groupids matching the filter and membership criteria, results sorted by priority order
     */
    public static function GetAvailableGroupsForGlobalFeeds(int $group_category_id = 0, int $myGroupsOnly = 0, array $tagsArray = array())
    {
        global $_COMPANY;
        global $_USER;
        global $_ZONE;

        $group_category_filter = '';
        if($group_category_id){
            $group_category_filter = " AND `groups`.categoryid ='{$group_category_id}'";
        }

        $group_condition = '';

        if ($myGroupsOnly) {
            // Find all the groups that I am part of in the current zone and matching group_category filter
            $myGroupRows = self::DBGet("
            SELECT distinct `groupmembers`.groupid 
            FROM `groupmembers` 
                JOIN `groups` ON `groupmembers`.groupid=`groups`.groupid 
                JOIN group_categories as `gc` ON `groups`.categoryid = `gc`.categoryid 
            WHERE groupmembers.userid={$_USER->id()} 
              AND groupmembers.isactive=1 
              AND `groups`.companyid={$_COMPANY->id()} 
              AND `groups`.isactive=1 
              AND `groups`.zoneid={$_ZONE->id()} 
              AND `gc`.isactive=1 
              {$group_category_filter}
              ");
            $myGroupIdList = implode(',', array_column($myGroupRows, 'groupid'));
            if (empty($myGroupIdList)) {
                return array();
            } else {
                $group_condition = "AND groupid IN ({$myGroupIdList})";
            }
        } else {
            // For discover groups do not show Invite-Only /hidden groups
            $group_condition = 'AND group_type IN('.Group::GROUP_TYPE_OPEN_MEMBERSHIP.','.Group::GROUP_TYPE_REQUEST_TO_JOIN.','.Group::GROUP_TYPE_MEMBERSHIP_DISABLED.')';
        }

        $groups = self::DBGet("
            SELECT groupid, `priority`, `tagids`, `groups`.`categoryid`  
            FROM `groups` 
                JOIN group_categories as `gc` ON `groups`.categoryid = `gc`.categoryid  
            WHERE `groups`.`companyid`={$_COMPANY->id()} 
              AND `gc`.isactive=1 
              AND (`groups`.`zoneid`={$_ZONE->id()} AND `groups`.isactive=1)  
              {$group_category_filter} 
              {$group_condition}
              ");

        // First sort the group rows by category sorting order
        $groupCategorySortingOrder = array_column(Group::GetAllGroupCategories(true), 'categoryid');
        $groups = Arr::SortByOrder($groups, $groupCategorySortingOrder, 'categoryid');

        // If tags are provided, next remove groups which do not have matching tags
        $data = array();
        if (!empty($tagsArray)){
            foreach($groups as $group){
                $groupTagsArray = explode(",",$group['tagids']);
                if (!empty(array_intersect($groupTagsArray,$tagsArray))){
                    $data[] = $group;
                }
            }
        } else {
            $data = $groups;
        }

        // Now sort the groups by group priority within each category area
        // For this first group the rows by categoryid
        $groupsByCategory = Arr::GroupBy($data, 'categoryid');
        // Next iterate over each categoryid and sort groups within that category using group priority.
        foreach ($groupsByCategory as $key => $val) {
            $priority = Str::ConvertCSVToArray($val[0]['priority']);
            $groupsByCategory[$key] = Arr::SortByOrder($val, $priority, 'groupid');
        }

        // Now ungroup the group categories back to group rows
        $data = Arr::UngroupTo($groupsByCategory,'categoryid'); // At this point we have a list sorted by categoryid followed by priority

        return array_column($data,'groupid');
    }


    public function updateTeamJoinRequestSetting(int $value)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['can_send_multiple_join_request'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        $attributes[$key] = array('value' => $value);
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function getTeamJoinRequestSetting() : int
    {
        $retVal = 0;

        $attributes = json_decode($this->val('attributes'), true) ?? array();
        $key = self::GROUP_ATTRIBUTES_KEYS['can_send_multiple_join_request'];
        if (array_key_exists($key, $attributes)) {
            $retVal = $attributes[$key]['value'];
        }

        return $retVal;
    }

    public function updateDiscussionsConfiguration(string $who_can_post, bool $allow_anonymous_post, bool $allow_email_publish)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['discussions_configuration'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        if (!in_array($who_can_post, array('leads','members'))) {
            $who_can_post = 'leads'; // Default to leads.
        }

        $attributes[$key] = array('who_can_post' => $who_can_post, 'allow_anonymous_post' => $allow_anonymous_post, 'allow_email_publish' => $allow_email_publish);
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function getDiscussionsConfiguration()
    {
        $retVal = array();
        $attributes = json_decode($this->val('attributes'), true) ?? array();
        $key = self::GROUP_ATTRIBUTES_KEYS['discussions_configuration'];
        if (array_key_exists($key, $attributes)) {
            $retVal = $attributes[$key];
        }
        return $retVal;
    }

    public function getRecognitionConfiguration(): array
    {
        $attributes = json_decode($this->val('attributes') ?? '', true) ?? array();
        $recognitions_configuration_key = self::GROUP_ATTRIBUTES_KEYS['recognitions_configuration'];
        $config = $attributes[$recognitions_configuration_key] ?? [];
        return [
            'enable_user_view_recognition' => boolval($config['enable_user_view_recognition'] ?? true), // true is default,
            'enable_self_recognition' => boolval($config['enable_self_recognition'] ?? true), // true is default,
            'enable_colleague_recognition' => boolval($config['enable_colleague_recognition'] ?? true), // true is default,
        ];
    }

    // For showing formatted groupnames on user list page in admin panel
    public static function GetFormattedListOfGroupnamesByUserMembership (int $userid) {
        global $_COMPANY, $_ZONE;
        $retVal = array();

        $groups = self::DBROGet("SELECT `groupmembers`.`anonymous`,`groupname` FROM `groupmembers` left join `groups` on (`groupmembers`.`groupid`=`groups`.`groupid`) WHERE `userid`='{$userid}' AND groupmembers.isactive=1 AND `groups`.`companyid`='{$_COMPANY->id()}' AND `groups`.`zoneid`='{$_ZONE->id()}' AND `groups`.`isactive`=1 AND `groupmembers`.`isactive`=1");
        foreach($groups as $group){
            if($group["anonymous"]=='1'){
                $group["groupname"] = "Anonymous";
            }
            $retVal[] = $group["groupname"];
        }
        return $retVal;
    }

    /**
     * @param int $categoryid, if 0 then all group tags [tagid, tag] in the zone are returned,
     * else only the ones matching the categoryid are returned
     * @return array|mixed
     */
    public static function GetGroupTags(int $categoryid)
    {
        global $_COMPANY, $_ZONE;

        $key = "ZONE_GRP_CAT_TAGS:{$_ZONE->id()}";
        if (($obj = $_COMPANY->getFromRedisCache($key)) === false) {

            self::DBGet('SET SESSION group_concat_max_len = 1024000');
            $groupTagRows = self::DBGet("SELECT categoryid, GROUP_CONCAT(tagids) AS tagids FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND `isactive`=1 GROUP BY categoryid");
            $allTagRows   = self::DBGet("SELECT `tagid`, `tag` FROM `group_tags` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()}");

            $obj = array();

            foreach ($groupTagRows as $groupTagRow) {
                $obj['tagids_by_category'][$groupTagRow['categoryid']] = Arr::IntValues(array_values(array_filter(array_unique(Str::ConvertCSVToArray($groupTagRow['tagids'])))));
            }
            $obj['all_tags'] = $allTagRows;

            $_COMPANY->putInRedisCache($key, $obj, 3600*8);
        }

        // If category is 0, which means all then return all the tags
        if ($categoryid == 0) {
            return $obj['all_tags'];
        }

        // If category is something other than 0, then return tags that match the provided filter
        $selected_tagids = $obj['tagids_by_category'][$categoryid];
        if (empty($selected_tagids)) {
            return [];
        }
        return Arr::KeepRowsByFilter($obj['all_tags'],$selected_tagids, 'tagid');
    }

    public static function GetOrCreateTagidsArrayByTag(array $tags) {
        global $_COMPANY, $_ZONE;
        $tagIds = array();
        foreach($tags as $tag){
            $cleanTag = $tag;
            $row =  self::DBGetPS("SELECT `tagid` FROM `group_tags` WHERE `companyid`=? AND `zoneid`=? AND tag=?","iix",$_COMPANY->id(),$_ZONE->id(),$cleanTag);
            if (!empty($row)) {
                $tagIds[] = $row[0]['tagid'];
            } else {
                $tagIds[] = self::DBInsertPS("INSERT INTO `group_tags`(`companyid`, `zoneid`, `tag`, `modifiedon`) VALUES (?,?,?,NOW())",'iix',$_COMPANY->id(),$_ZONE->id(),$cleanTag);
                $_COMPANY->expireRedisCache("ZONE_GRP_CAT_TAGS:{$_ZONE->id()}");
            }
        }
        return  $tagIds;
    }

    public static function DeleteUnusedTags() {
        global $_COMPANY, $_ZONE;
        $allTags = self::GetGroupTags(0); // 0 for all tags

        // Get all tags in use across all groups in the zone
        $groupRows = self::DBGet("SELECT tagids FROM `groups` WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()}");
        $tagsColumn = array_unique(array_filter(explode(',',implode(',',array_column($groupRows,'tagids')))));

        foreach ($allTags as $allTag) {
            if (!in_array($allTag['tagid'], $tagsColumn)) {
                $tagidToRemove = intval($allTag['tagid']);
                self::DBMutate("DELETE FROM `group_tags` WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND tagid={$tagidToRemove}");
            }
        }

        $_COMPANY->expireRedisCache("ZONE_GRP_CAT_TAGS:{$_ZONE->id()}");
    }


    public function updateGroupTags(string $tagids){
        global $_COMPANY, $_ZONE;

        $retVal = self::DBUpdatePS("UPDATE `groups` SET `tagids`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `zoneid`=? AND `groupid`=?",'xiii',$tagids,$_COMPANY->id(),$_ZONE->id(),$this->id());
        $_COMPANY->expireRedisCache("GRP:{$this->id}");
        $_COMPANY->expireRedisCache("ZONE_GRP_CAT_TAGS:{$_ZONE->id()}");
        return $retVal;
    }

    /**
     * This method returns a heirarcy of zonenames and groupnames sorted by zonename,groupname.
     * Note 1: Invitation Type groups are excluded
     * @return array of zoneid->[...groupids ... ] with values zoneid, zonename, groupid, groupname. e.g
     * [
     *  'Europe' => [
     *      ['zoneid' => '5', 'zonename' => 'Europe', 'groupid' => '31', 'groupname' => 'Berlin'],
     *  ]
     *  'USA' => [
     *      ['zoneid' => '1', 'zonename' => 'USA', 'groupid' => '3', 'groupname' => 'HOLA'],
     *      ['zoneid' => '1', 'zonename' => 'USA', 'groupid' => '4', 'groupname' => 'ADEPT']
     *  ]
     * ]
     */
    public static function GetAllGroupsForZoneCollaboration ()
    {
        global $_COMPANY, $_ZONE, $_USER;

        $group_objects = array();

        $collaboraing_zoneids = empty($_ZONE->val('collaborating_zoneids')) ? $_ZONE->id() : $_ZONE->id() . ',' . $_ZONE->val('collaborating_zoneids');
        $collaboraing_zoneids = Sanitizer::SanitizeIntegerCSV($collaboraing_zoneids);
        $exclude_group_types = implode(',', [Group::GROUP_TYPE_INVITATION_ONLY]);

        $group_rows = self::DBGet("
                    SELECT zoneid, zonename, groupid, groupname 
                    FROM company_zones JOIN `groups` USING (zoneid) 
                    WHERE company_zones.companyid='{$_COMPANY->id()}' 
                      AND company_zones.zoneid IN({$collaboraing_zoneids}) 
                      AND company_zones.isactive=1 
                      AND `groups`.isactive=1
                      AND `groups`.group_type NOT IN ($exclude_group_types)
                  ");

        // First sort the rows by zone and group names
        usort($group_rows, function($a, $b) {
            return [strtolower($a['zonename']), strtolower($a['groupname'])] <=> [strtolower($b['zonename']), strtolower($b['groupname'])];
        });


        foreach ($group_rows as $row) {
            $zname = $row['zonename'];
            if (!isset($group_objects[$zname]))
                $group_objects[$zname] = []; // Intialize it

            $group_objects[$zname][] = $row;

        }

        return $group_objects;
    }

    public function updateGroupleadsPriorityOrder(string $priority)
    {
        global $_COMPANY, $_ZONE;
        return self::DBUpdatePS("UPDATE `groupleads` SET `priority`=? WHERE `groupid`=?",'xi',$priority,$this->id());
    }

    public function updateChapterleadsPriorityOrder(int $chpaterid, string $priority)
    {
        global $_COMPANY, $_ZONE;
        return self::DBUpdatePS("UPDATE `chapterleads` SET `priority`=? WHERE `groupid`=? AND `chapterid`=?",'xii',$priority,$this->id(),$chpaterid);
    }

    public function updateChannelleadsPriorityOrder(int $channelid, string $priority)
    {
        global $_COMPANY, $_ZONE;
        return self::DBUpdatePS("UPDATE `group_channel_leads` SET `priority`=? WHERE `groupid`=? AND `channelid`=?",'xii',$priority,$this->id(),$channelid);
    }

    public function withdrawGroupMemberInvite(int $memberinviteid)
    {
        global $_COMPANY, $_ZONE;
        return self::DBMutate("DELETE FROM `memberinvites` WHERE `companyid`='{$_COMPANY->id()}' AND `groupid`='{$this->id()}' AND `memberinviteid`='{$memberinviteid}' ");
    }

    public function activateGroupCommunicationTemplate(int $communicationid)
    {
        global $_COMPANY, $_ZONE;
        return self::DBMutate("UPDATE `group_communications` SET `isactive`='1',`modifiedon`=NOW() WHERE `companyid`='{$_COMPANY->id()}' AND `groupid`='{$this->id()}' AND `communicationid`='{$communicationid}' ");
    }

    public function deActivateGroupCommunicationTemplate(int $communicationid)
    {
        global $_COMPANY, $_ZONE;
        return self::DBMutate("UPDATE `group_communications` SET `isactive`='0',`modifiedon`=NOW() WHERE `companyid`='{$_COMPANY->id()}' AND `groupid`='{$this->id()}' AND `communicationid`='{$communicationid}' ");
    }


    public function deleteGroupCommunicationTemplate(int $communicationid)
    {
        global $_COMPANY, $_ZONE;
        return self::DBMutate("DELETE FROM `group_communications` WHERE `companyid`='{$_COMPANY->id()}' AND `groupid`='{$this->id()}' AND `communicationid`='{$communicationid}' ");
    }

    public function updateGroupStatus(int $status)
    {
        global $_COMPANY, $_ZONE;

        $retVal = self::DBMutate("UPDATE `groups` SET `isactive`='{$status}', `modifiedon`=now() WHERE `companyid`='{$_COMPANY->id()}' AND `groupid`='{$this->id()}'");
        $_COMPANY->expireRedisCache("GRP:{$this->id()}");
        if ($retVal) {
            if($status == '1'){
                self::LogObjectLifecycleAudit('state_change', 'group', $this->id(), 0, ['state' => 'publish']);
            }else if($status == '0'){
                self::LogObjectLifecycleAudit('state_change', 'group', $this->id(), 0, ['state' => 'unpublish']);
            }else{
                self::LogObjectLifecycleAudit('delete', 'group', $this->id(), 0);
            }
        }
        return $retVal;
    }

    public static function UpdateGroupPriorityOrder(string $priority, int $categoryid)
    {
        global $_COMPANY, $_ZONE;
        return self::DBUpdatePS("UPDATE `groups` SET `priority`=? WHERE `companyid`=? AND `zoneid`=? AND `categoryid`=?",'xiii',$priority, $_COMPANY->id(), $_ZONE->id(), $categoryid);
    }

    public static function UpdateGroupLeadStatus(int $typeid, int $status)
    {
        global $_COMPANY, $_ZONE;

        return self::DBMutate("UPDATE grouplead_type SET `isactive`='{$status}', `modifiedon`=NOW() WHERE `companyid`='{$_COMPANY->id()}' AND typeid='{$typeid}'");
    }

    public function updateChapterMemberships(int $memberid, string $chapterids)
    {
        return self::DBUpdatePS("UPDATE `groupmembers` SET `chapterid`=? WHERE `groupid`=? AND `memberid`=?",'xii',$chapterids,$this->id(),$memberid);
    }


    public function updateGroupMedia(string $groupicon, string $sliderphoto, string $coverphoto, string $app_sliderphoto, string $app_coverphoto)
    {
        global $_COMPANY, $_ZONE;
        $retVal =  self::DBUpdatePS("
            UPDATE `groups` 
            SET `groupicon`=?, `sliderphoto`=?,`coverphoto`=?,`app_sliderphoto`=?, `app_coverphoto`=?, `modifiedon`=NOW()  
            WHERE `companyid`=? AND `zoneid`=? AND `groupid`=?",
            'xxxxxiii',
            $groupicon, $sliderphoto, $coverphoto, $app_sliderphoto, $app_coverphoto,
            $_COMPANY->id(), $_ZONE->id(), $this->id()
        );
        $_COMPANY->expireRedisCache("GRP:{$this->id()}");
        return $retVal;
    }


    public function getGroupMemberInvites(){

        global $_COMPANY, $_ZONE;

        return self::DBGet("SELECT memberinvites.`memberinviteid`,memberinvites.`chapterid`,memberinvites.`channelid`,memberinvites.`email`,memberinvites.`status`,memberinvites.`createdon`,`chapters`.chaptername, `group_channels`.channelname FROM `memberinvites`  LEFT JOIN `chapters` ON chapters.chapterid=memberinvites.chapterid LEFT JOIN group_channels ON group_channels.channelid=memberinvites.channelid WHERE memberinvites.`companyid`='{$_COMPANY->id()}' AND memberinvites.`groupid`='{$this->id()}'");
    }

    /**
     * Returns all the Communication Templates by mathing criteria
     * @param string $scope;  global or  chapter or channel
     * @param int $scopeid;  if global ignore it, if chapter then mantch chapter condtion,  if channel then match channel condition
     * @param int $communicationTrigger; On join or on leave.
     * @return array
     */
    public function getCommunicationTemplatesByTrigger(string $scope, int $scopeid, int $communicationTrigger)
    {
        global $_COMPANY,$_ZONE;
        $scopeCondtion  = " AND `groupid`='{$this->id()}' AND `chapterid`='0'";

        if ($scope == 'chapter'){
            $scopeCondtion  = " AND `groupid`='{$this->id()}' AND `chapterid`='{$scopeid}'";
        } elseif ($scope == 'channel'){
            $scopeCondtion  = " AND `groupid`='{$this->id()}' AND `channelid`='{$scopeid}'";
        }

        return self::DBGet("SELECT * FROM `group_communications` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' {$scopeCondtion} AND`communication_trigger`='{$communicationTrigger}'");
    }



    public static function GetChapterRegionIdsByChapterIdsCSV(string $chapterids){
        global $_COMPANY, $_ZONE;

        $chapterids = Sanitizer::SanitizeIntegerCSV($chapterids);
        if (empty($chapterids)){
            return '';
        }

        $retVal = self::DBROGet("SELECT IFNULL(GROUP_CONCAT(DISTINCT `regionids`),'') as `regionids` FROM `chapters` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `chapterid` IN ({$chapterids}) AND `isactive`='1'");
        return $retVal[0]['regionids'];
    }

    /**
     * GetChapterByGroupsAndRegion
     *
     * @param  string $groupIds
     * @param  int $regionid
     * @return array
     */
    public static function GetChapterByGroupsAndRegion(string $groupIds, int $regionid, bool $groupByChaptername = true)
    {
        global $_COMPANY, $_ZONE;

        $groupCondition = '';
        $groupIds = Sanitizer::SanitizeIntegerCSV($groupIds);
        if($groupIds){
            $groupCondition = " AND `chapters`.`groupid` IN({$groupIds}) ";
        }
        $regionConditioin = "";
        if ($regionid){
            $regionConditioin = " AND `chapters`.`regionids`='{$regionid}'";
        }

        $rows = self::DBROGet("SELECT `chapters`.*,IFNULL(`groups`.`groupname`,'Global') as groupname FROM `chapters` JOIN `groups` using (`groupid`) WHERE `chapters`.`companyid`='{$_COMPANY->id()}' {$groupCondition} {$regionConditioin} AND `chapters`.`isactive`=1");
        if (!empty($rows)){
            if ($groupByChaptername){
                $rows = Arr::GroupBy($rows, 'chaptername');
                ksort($rows);
            } else {
                usort($rows, function ($a, $b) {
                    return $a['chaptername'] <=> $b['chaptername'];
                });
            }
        }

        return $rows;
    }

    /**
     * GetAnyUserGroupMembershipByGroupidCSV
     *
     * @param  int $userid
     * @param  string $groupids
     * @return array
     */
    public static function GetAnyUserGroupMembershipByGroupidCSV(int $userid, string $groupids)
    {
        global $_COMPANY;
        $groupids = Sanitizer::SanitizeIntegerCSV($groupids);
        return self::DBGet("SELECT groupmembers.* FROM groupmembers JOIN users USING (userid) WHERE users.companyid={$_COMPANY->id()} AND groupmembers.userid='{$userid}' AND `groupid` IN ({$groupids}) AND groupmembers.isactive=1");
    }


/**
 * GetChannelsByGroupIdsCsv
 *
 * @param  string $groupIds
 * @return array
 */
public static function GetChannelsByGroupIdsCsv(string $groupIds)
    {
        global $_COMPANY, $_ZONE;

        $groupCondition = '';
        $groupIds = Sanitizer::SanitizeIntegerCSV($groupIds);
        if($groupIds){
            $groupCondition = " AND `groups`.groupid IN({$groupIds}) ";
        }

        $rows = self::DBROGet("SELECT `groups`.groupname, group_channels.* FROM `groups` JOIN group_channels USING (`groupid`) WHERE `groups`.`companyid`='{$_COMPANY->id()}' AND (`groups`.`zoneid`='{$_ZONE->id()}' {$groupCondition} AND `groups`.isactive=1 AND `group_channels`.`isactive`=1)");
        if (!empty($rows)){
            $rows = Arr::GroupBy($rows, 'channelname');
            ksort($rows);
        }

        return $rows;
    }

    /**
     * Prepares the template import data for a group.
     *
     * @return array|false The encoded JSON data if successful, false otherwise.
     */
    public function export(){
        global $_COMPANY;
        $data = [];
        // Retrieve the necessary group data
        $groupname = "Template - " . $this->val('groupname') ?? "";
        $groupname_short = "Template - " .  $this->val('groupname_short') ?? "";
        $aboutgroup = $this->val('aboutgroup') ?? "";
        $overlaycolor = $this->val('overlaycolor') ?? "";
        $overlaycolor2 = $this->val('overlaycolor2') ?? "";
        $group_category = $this->val('group_category') ?? "";
        $from_email_label = $this->val('from_email_label') ?? "";
        $show_overlay_logo = $this->val('show_overlay_logo') ?? 1;
        $replyto_email = "";
        $group_type = $this->val('group_type') ?? 0;
        $show_app_overlay_logo = $this->val('show_app_overlay_logo') ?? 1;
        $tagids = $this->val('tagids') ?? "";
        $groupicon = "";
        $coverphoto = "";
        $sliderphoto = "";
        $app_coverphoto = "";
        $app_sliderphoto = "";

        // Process groupicon, coverphoto, sliderphoto, app_coverphoto, app_sliderphoto
        if ($this->val('groupicon')) {
            $ext = pathinfo($this->val('groupicon'), PATHINFO_EXTENSION);
            $actual_name = "groupicon_" . teleskope_uuid() . "." . $ext;
            $groupicon = $_COMPANY->copyS3File($this->val('groupicon'), $actual_name, 'TSKP_TEMPLATE');
        }

        if ($this->val('coverphoto')) {
            $ext = pathinfo($this->val('coverphoto'), PATHINFO_EXTENSION);
            $actual_name = "groupcover_" . teleskope_uuid() . "." . $ext;
            $coverphoto = $_COMPANY->copyS3File($this->val('coverphoto'), $actual_name, 'TSKP_TEMPLATE');
        }

        if ($this->val('sliderphoto')) {
            $ext = pathinfo($this->val('sliderphoto'), PATHINFO_EXTENSION);
            $actual_name = "group_slider_" . teleskope_uuid() . "." . $ext;
            $sliderphoto = $_COMPANY->copyS3File($this->val('sliderphoto'), $actual_name, 'TSKP_TEMPLATE');
        }

        if ($this->val('app_coverphoto')) {
            $ext = pathinfo($this->val('app_coverphoto'), PATHINFO_EXTENSION);
            $actual_name = "group_app_coverphoto_" . teleskope_uuid() . "." . $ext;
            $app_coverphoto = $_COMPANY->copyS3File($this->val('app_coverphoto'), $actual_name, 'TSKP_TEMPLATE');
        }

        if ($this->val('app_sliderphoto')) {
            $ext = pathinfo($this->val('app_sliderphoto'), PATHINFO_EXTENSION);
            $actual_name = "group_app_sliderphoto_" . teleskope_uuid() . "." . $ext;
            $app_sliderphoto = $_COMPANY->copyS3File($this->val('app_sliderphoto'), $actual_name, 'TSKP_TEMPLATE');
        }
        // Copy only custom attributes of matching algorithm
        $attributes = $this->val('attributes') ? json_decode($this->val('attributes'), true) : [];
        unset($attributes['matching_algorithm_parameters']['primary_parameters']);
        unset($attributes['matching_algorithm_parameters']['mandatory_primary_parameters']);

        // Prepare the data array
        $data = [
            'source_template_id' => 'GRP_' . $_COMPANY->val('companyid') . '_' . $this->id(),
            'groupname' => $groupname,
            'groupname_short' => $groupname_short,
            'aboutgroup' => $aboutgroup,
            'overlaycolor' => $overlaycolor,
            'overlaycolor2' => $overlaycolor2,
            'group_category' => $group_category,
            'from_email_label' => $from_email_label,
            'show_overlay_logo' => $show_overlay_logo,
            'replyto_email' => $replyto_email,
            'show_app_overlay_logo' => $show_app_overlay_logo,
            'tagids' => $tagids,
            'grouptype' => $group_type,
            'groupicon' => $groupicon,
            'coverphoto' => $coverphoto,
            'sliderphoto' => $sliderphoto,
            'app_coverphoto' => $app_coverphoto,
            'app_sliderphoto' => $app_sliderphoto,
            'attributes' => $attributes,
        ];

        // Team roles Data
        $teamRoles = Team::GetProgramTeamRoles($this->id());
        if ($teamRoles) {
            $data['attributes']['teamroles'] = $teamRoles;
        }

        // Retrieve Surveys
        $surveys = GroupMemberSurvey::GetAllSurveys($this->id(),0,0,0, true);
        if ($surveys) {
            foreach($surveys as $survey){
                $fields = array();
                $fields['surveyname'] = $survey->val('surveyname');
                $fields['surveytype'] = $survey->val('surveytype');
                $fields['surveysubtype'] = $survey->val('surveysubtype');
                $fields['is_required'] = $survey->val('is_required') ?? 0;
                $fields['allow_multiple'] = $survey->val('allow_multiple') ?? 0;
                $fields['anonymous'] = $survey->val('anonymous') ?? 0;

                $survey_json = json_decode($survey->val('survey_json') ?? '', true) ?? [];

                //Remove Logo
                unset($survey_json['logo']);
                unset($survey_json['logoWidth']);
                unset($survey_json['logoHeight']);
                unset($survey_json['logoFit']);
                unset($survey_json['logoPosition']);

                // Remove image picker elements
                if (isset($survey_json['pages'])) {
                    foreach ($survey_json['pages'] as $k => $page) {
                        if (isset($page['elements'])) {
                            $page_elements = $page['elements'];
                            foreach ($page_elements as $l => $page_element) {
                                if ($page_element['type'] == 'imagepicker') {
                                    unset($survey_json['pages'][$k]['elements'][$l]); // Remove image picker questions.
                                }
                            }
                        }
                        $survey_json['pages'][$k]['elements'] = array_values($survey_json['pages'][$k]['elements']);
                    }
                }
                $fields['survey_json'] = json_encode($survey_json);

                $data['attributes']['surveys'][] = $fields;
            }
        }
        if ($data !== false) {
            return $data;
        }

        return false; // Encoding failed
    }


    /**
     * Create a group from a template.
     *
     * @param string $sourceTemplateId The ID of the source template.
     *
     * @return Group|false The Group object if successful, false otherwise.
     */
    public static function CreateFromTemplate($sourceTemplateId): Group|false
    {

        global $_COMPANY, $_ZONE;
        // Fetch the Template Data -
        $templateData = TskpTemplate::GetTskpTemplate($sourceTemplateId);
        if (!$templateData) {
            return false; // Template data not found
        }

        // Decode the JSON data
        $data = json_decode($templateData['template_data'], true);
        if (!$data) {
            return false; // JSON decoding failed
        }
        //if the grouptype is set in the exported data then we use that, otherwise we use same logic when we create a group/program. This fixes for older templates
        $group_type = $data['grouptype'] ?? 0;
        if (is_array($group_type)){
            // This is the case due to bad data stored in group template
            // e.g.
            // "grouptype": {
            //     "0": "Open Membership",
            //     "10": "Invitation Only",
            //     "30": "Membership by Request Only",
            //     "50": "Membership Disabled"
            // }
            $group_type = 0; // set 0 default
        }
        if(empty($group_type)){
            if ($_ZONE->val('app_type') == 'talentpeak'){
                $group_type = Group::GROUP_TYPE_REQUEST_TO_JOIN;
            } elseif($_ZONE->val('app_type') == 'officeraven'){
                $group_type = Group::GROUP_TYPE_MEMBERSHIP_DISABLED;
            } else {
                $group_type = Group::GROUP_TYPE_OPEN_MEMBERSHIP;
            }
        }
        // Extract the required values from the decoded data array
        $groupname_short = $data['groupname_short'] ?? '';
        $groupname = $data['groupname'] ?? '';
        $aboutgroup = $data['aboutgroup'] ?? 'About '.$groupname.' ...';
        $overlaycolor = $data['overlaycolor'] ?? '';
        $from_email_label = $data['from_email_label'] ?? '';
        $regionid = $data['regionid'] ?? '';
        $overlaycolor2 = $data['overlaycolor2'] ?? '';
        $show_overlay_logo = $data['show_overlay_logo'] ?? 1;
        $group_category = $data['group_category'] ?? '';
        $replyto_email = '';
        $attributes = json_encode($data['attributes']);
        $show_app_overlay_logo = $data['show_app_overlay_logo'] ?? 1;
        $tagids = $data['tagids'] ?? '';

        $groupicon = "";
        if ($data['groupicon']){
            $ext = pathinfo($data['groupicon'], PATHINFO_EXTENSION);
            $actual_name ="groupicon_".teleskope_uuid().".".$ext;
            $groupicon = $_COMPANY->copyS3File($data['groupicon'],$actual_name,'GROUP');
        }

        $coverphoto = "";
        if ($data['coverphoto']){
            $ext = pathinfo($data['coverphoto'], PATHINFO_EXTENSION);
            $actual_name ="groupcover_".teleskope_uuid().".".$ext;
            $coverphoto = $_COMPANY->copyS3File($data['coverphoto'],$actual_name,'GROUP');
        }
        $sliderphoto = "";
        if ($data['sliderphoto']){
            $ext = pathinfo($data['sliderphoto'], PATHINFO_EXTENSION);
            $actual_name = "group_slider_" . teleskope_uuid() . "." . $ext;
            $sliderphoto = $_COMPANY->copyS3File($data['sliderphoto'],$actual_name,'GROUP');
        }

        $app_coverphoto = "";
        if ($data['app_coverphoto']){
            $ext = pathinfo($data['app_coverphoto'], PATHINFO_EXTENSION);
            $actual_name = "group_app_coverphoto_" . teleskope_uuid() . "." . $ext;
            $app_coverphoto = $_COMPANY->copyS3File($data['app_coverphoto'],$actual_name,'GROUP');
        }
        $app_sliderphoto = "";
        if ($data['app_sliderphoto']){
            $ext = pathinfo($data['app_sliderphoto'], PATHINFO_EXTENSION);
            $actual_name = "group_app_sliderphoto_" . teleskope_uuid() . "." . $ext;
            $app_sliderphoto = $_COMPANY->copyS3File($data['app_sliderphoto'],$actual_name,'GROUP');
        }

        $permatag = substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(12 / strlen($x)))), 1, 12);
        $group_category_id = (int)Group::GetDefaultGroupCategoryRow()['categoryid'];
        $importedGroupId = self::CreateGroup( $groupname_short, $groupname,  $aboutgroup,  $coverphoto, $overlaycolor, $from_email_label, $regionid, $groupicon, $permatag, $overlaycolor2, $sliderphoto, $show_overlay_logo, $group_category,$replyto_email, $group_type, $app_sliderphoto,$app_coverphoto,$show_app_overlay_logo,$tagids, $group_category_id, $attributes);
        if (!$importedGroupId) {
            return false; // Group creation failed
        }
        return self::GetGroup($importedGroupId);
    }

    // For controlling Teams module configurability.

    /**
     * Returns true if the Module is configured for the group
     * @return bool
     */
    public function isTeamsModuleEnabled(): bool
    {
        return $this->isTeamsModuleAllowed() && $this->isTeamsModuleActivated();
    }

    public function isTeamsModuleAllowed(): bool
    {
        global $_COMPANY, $_ZONE;
        // Teams can be enabled only for Talent Peak Groups or if the Affinities groups are of type GROUP_TYPE_OPEN_MEMBERSHIP
        // Teams will be disabled if the group allows anonymous join as user data is needed to configure teams
        return (
            $_COMPANY->getAppCustomization()['teams']['enabled']
            && (($_ZONE->val('app_type') == 'talentpeak') || ($this->val('group_type') == self::GROUP_TYPE_OPEN_MEMBERSHIP))
            && !$this->val('join_group_anonymously')
            && empty($this->getGroupMemberRestrictions())
        );
    }

    /**
     * Returns if the provided module is enabled for the group.
     * @return bool
     */
    public function isTeamsModuleActivated(): bool
    {
        global $_ZONE;
        $key = self::GROUP_ATTRIBUTES_KEYS['is_teams_activated'];
        $attributes = json_decode($this->val('attributes') ?? '', true) ?? [];
        $is_teams_activated = $_ZONE->val('app_type') === 'talentpeak' ? true : false; // Default value

        if (array_key_exists($key, $attributes)) {
            $is_teams_activated = $attributes[$key];
            if ($_ZONE->val('app_type') === 'talentpeak') {
                // If attribute is specifically set then use it
                if ($is_teams_activated !== null) {
                    return boolval($is_teams_activated);
                }
                // Else default for talentpeak is true.
                return true;
            }
        }
        return boolval($is_teams_activated);
    }

    /**
     * Enables or disable Teams Module for the group
     * @param bool $value true to enable, false to disable
     * @return bool
     */
    public function activateTeamsModule(bool $value): bool
    {
        $key = self::GROUP_ATTRIBUTES_KEYS['is_teams_activated'];
        $attributes = json_decode($this->val('attributes') ?? '', true);
        $attributes[$key] = $value;
        return (bool)$this->updateGroupAttributes(json_encode($attributes));
    }


    public function saveNetworkingProgramStartSetting(string $program_start_date, int $team_match_cycle_days)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['networking_program_start_setting'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }


        $attributes[$key] = array('program_start_date'=>$program_start_date,'team_match_cycle_days'=>$team_match_cycle_days);
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function getNetworkingProgramStartSetting()
    {
        $key = self::GROUP_ATTRIBUTES_KEYS['networking_program_start_setting'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return array('program_start_date'=>'','team_match_cycle_days'=>'0'); // Default setting
    }

    /**
     * getTeamRoleRequestChapterSelectionSetting
     *
     * @return array
     */
    public function getTeamRoleRequestChapterSelectionSetting()
    {
        global $_COMPANY, $_ZONE;
        $key = self::GROUP_ATTRIBUTES_KEYS['team_request_chapter_selection_setting'];

        if ($this->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['ADMIN_LED'] ) {
            return array('allow_chapter_selection'=>0,'chapter_selection_label'=>'');
        }

        $attributes = $this->val('attributes');
        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return array('allow_chapter_selection'=>1,'chapter_selection_label'=>sprintf(gettext("Please select the %s of this %s you'd like to participate in"),$_COMPANY->getAppCustomization()['chapter']['name-short'], $_COMPANY->getAppCustomization()['group']['name-short']));
    }

    public static function GetChapterNamesByChapteridsCsv(string $chapterids)
    {
        global $_COMPANY, $_ZONE;

        $chapterids = Sanitizer::SanitizeIntegerCSV($chapterids) ?: '0';
        
        return self::DBROGet("SELECT `chapters`.*,IFNULL(`groups`.`groupname`,'Global') as groupname FROM `chapters` JOIN `groups` using (`groupid`) WHERE `chapters`.`companyid`='{$_COMPANY->id()}' AND chapters.chapterid IN ({$chapterids}) AND `chapters`.`isactive`=1 AND `groups`.isactive=1");
    }

    public static function SendGroupAnniversaryEmails(){
        global $_COMPANY,$_ZONE;

        if ($_COMPANY || $_ZONE) {
            Logger::Log("Calling multi-tenant method from Company or Zone context:" . $_COMPANY->id(), Logger::SEVERITY['FATAL_ERROR']);
            return; // This job can only be executed if it is called from non company and non zone context. If company is set, exit it.
        }

		$companies = self::DBGet("SELECT `companyid` FROM `companies` WHERE companies.isactive=1");
        // Step 1: Iterate over active companies and zones
        foreach ($companies as $company) {
			$_COMPANY = Company::GetCompany($company['companyid']);
            if (!$_COMPANY) {
                continue;
            }
            // Step 2: Iterate over active Zones
            foreach ($_COMPANY->getZones() as $zonesArr) {

                if ($zonesArr['isactive'] != 1 ||
                    ($_ZONE = $_COMPANY->getZone($zonesArr['zoneid'])) == null
                ) {
                    continue; // Skip inactive zones;
                }

                // Step 3: Iterate over active groups and the ones which have group anniversary template.
                $activeGroups = self::GetActiveGroupsWithAnniversaryTemplate();

                    foreach ($activeGroups as $group) {
                        if($group['groupid'] == NULL || $group['valid_intervals'] == NULL){
                            continue;
                        }
                        $groupMembersWithMatchingAnnivDates = self::GetGroupMembersWithAnniversaryDates($group['groupid'], $group['valid_intervals']);
                        if(!empty($groupMembersWithMatchingAnnivDates)){
                            foreach ($groupMembersWithMatchingAnnivDates as $member) {
                                $anniversaryJob = new GroupMemberJob($group['groupid'], 0, 0);
                                $communication_trigger_type = array_flip(self::GROUP_COMMUNICATION_ANNIVERSARSY_TRIGGER_TO_INTERVAL_DAY_MAP)[$member['anniversary_interval']] ?? '';
                                $anniversaryJob->saveAsAnniversaryFollowup($member['userid'], $member['anonymous'], $communication_trigger_type);
                            }
                        }
                    }
                $_ZONE = null;
            }
            $_ZONE = null;
            $_COMPANY = null;
        }
        $_ZONE = null;
        $_COMPANY = null;
    }

    private static function GetActiveGroupsWithAnniversaryTemplate() {
        global $_COMPANY, $_ZONE;
        $query = "SELECT g.groupid, GROUP_CONCAT(DISTINCT c.anniversary_interval) as valid_intervals
                  FROM `groups` g
                  JOIN group_communications c ON g.groupid = c.groupid
                  WHERE g.companyid = {$_COMPANY->id()}
                    AND g.zoneid = {$_ZONE->id()}
                    AND g.isactive = 1
                    AND c.isactive = 1
                    AND c.communication_trigger IN (3,4,5,6,7,8,9,14,15,16,17,18,19) GROUP BY groupid";

        return self::DBROGet($query);
    }

    protected static function GetGroupMembersWithAnniversaryDates(int $groupid, string $valid_intervals): array
    {

        if (empty($valid_intervals))
            return array();

        $valid_intervals_array = Arr::IntValues(explode(',', $valid_intervals));
        $utcTZ = new DateTimeZone('UTC');
        $currentDate = new DateTime('now', $utcTZ);
        $currentDateYmd = $currentDate->format('Y-m-d');
        $currentYear = $currentDate->format('Y');
        $previousYear = $currentYear - 1;

        $query = "
                SELECT groupmembers.userid, groupmembers.memberid, groupmembers.groupjoindate,groupmembers.anonymous,groupmembers.groupid
                FROM groupmembers
                    JOIN users USING(userid)
                WHERE groupmembers.groupid={$groupid}
                  AND users.isactive=1
                  AND groupjoindate < now() - interval 1 day
                  AND groupmembers.isactive=1 
                ";

        $rows = self::DBROGet($query);

        $results = array();

        foreach ($rows as $row) {
            $joinDate = new DateTime($row['groupjoindate'], $utcTZ);
            // Calculate the anniversary for this year
            $anniversary = new DateTime($joinDate->format('Y-m-d'), $utcTZ);
            $anniversary->setDate($currentYear, $joinDate->format('m'), $joinDate->format('d'));

            // Edge case handing - since we only allow max of 90 day anniversaries,
            // if the anniversary date is more than (365-90 - 2 = 273) days out, then we reduce anniversary by one year
            // to uncover an edge case.
            $diff = $anniversary->diff($currentDate);
            $diff_days = $diff->days;
            if ($diff_days > 273) {
                $anniversary->setDate($previousYear, $joinDate->format('m'), $joinDate->format('d'));
            }

            // Iterate through each interval to find a match
            foreach ($valid_intervals_array as $interval) {
                $intervalPeriod = clone $anniversary;
                $abs_interval = abs($interval);
                if ($interval < 0) {
                    $intervalPeriod->modify("-{$abs_interval} days");
                } elseif ($interval > 0) {
                    $intervalPeriod->modify("+{$abs_interval} days");
                }

                if ($currentDateYmd == $intervalPeriod->format('Y-m-d')) { // Match found
                    $row['anniversary_interval'] = $interval;
                    $row['nearest_anniversary_date'] = $anniversary->format('Y-m-d');
                    $results[] = $row;
                    break;
                }
            }
        }
        return $results;
    }

    public function getProgramDiscoverSearchAttributes()
    {
        global $_COMPANY, $_ZONE;
        $retVal = array (
            'primary' => array(),
            'custom' => array(),
            'default_for_show_only_with_available_capacity' => 0
        );
        $key = self::GROUP_ATTRIBUTES_KEYS['discover_search_attributes'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                $retVal = $attributes[$key];
                if (!array_key_exists('primary', $retVal)) { $retVal['primary'] = array();}
                if (!array_key_exists('custom', $retVal)) { $retVal['custom'] = array();}
                if (!array_key_exists('default_for_show_only_with_available_capacity', $retVal)) { $retVal['default_for_show_only_with_available_capacity'] = 0;}

            }
        }
        return $retVal;
    }

    public function saveDiscoverSearchAttributes(array $primary_parameters, array $custom_parameters, int $default_for_show_only_with_available_capacity=0)
    {
        global $_COMPANY, $_ZONE;

        $valid_primary_parameters = array_values(UserCatalog::GetAllCatalogCategories());
        $primary_parameters = array_intersect($valid_primary_parameters, $primary_parameters);

        // Now save them.
        $key = self::GROUP_ATTRIBUTES_KEYS['discover_search_attributes'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }
        $attributes[$key]['primary'] = array_values($primary_parameters);
        $attributes[$key]['custom'] = array_values($custom_parameters);
        $attributes[$key]['default_for_show_only_with_available_capacity'] = $default_for_show_only_with_available_capacity;
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }


    public function getProgramMentorMenteeStats (string $date) {
        global $_COMPANY, $_ZONE;

        $totalMentors = -1;
        $totalMentees = -1;

        $key = "GRP_TEAM_STATS:{$this->id}:{$date}";
        if (($obj = $_COMPANY->getFromRedisCache($key)) === false) {

            $activeRoleSystemTypes = self::DBROGet("SELECT DISTINCT(`sys_team_role_type`) as `sys_team_role_type` FROM `team_role_type` WHERE companyid='{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' AND `groupid`='{$this->id()}' AND`sys_team_role_type`IN (2,3) AND `isactive`=1");

            if (!empty($activeRoleSystemTypes)) {
                $activeRoleSystemTypes = array_column($activeRoleSystemTypes, 'sys_team_role_type');
                $date = rtrim($date, '-');
                if (in_array(2, $activeRoleSystemTypes)) {
                    $totalMentors = self::DBROGet("SELECT SUM((SELECT COUNT(1) FROM team_members JOIN teams USING(teamid) WHERE team_members.roleid=team_role_type.roleid AND teams.isactive IN(1,110) AND teams.groupid=team_role_type.groupid AND DATE_FORMAT(team_members.createdon,'%Y-%m') <= '{$date}' )) as totalMentors FROM `team_role_type` WHERE companyid='{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' AND  `groupid`='{$this->id()}' and `sys_team_role_type`=2")[0]['totalMentors'];
                }
                if (in_array(3, $activeRoleSystemTypes)) {
                    $totalMentees = self::DBROGet("SELECT SUM((SELECT COUNT(1) FROM team_members JOIN teams USING(teamid) WHERE team_members.roleid=team_role_type.roleid AND teams.isactive IN(1,110) AND teams.groupid=team_role_type.groupid AND DATE_FORMAT(team_members.createdon,'%Y-%m') <= '{$date}' )) as totalMentees FROM `team_role_type` WHERE companyid='{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' AND  `groupid`='{$this->id()}' and `sys_team_role_type`=3")[0]['totalMentees'];
                }
            }
            $obj = array($totalMentors, $totalMentees);
            $_COMPANY->putInRedisCache($key, $obj, 600);
        }

        return $obj;
    }

    /**
     * This method returns rows of groups and chapters that match provided zoneids, regionids and group category. Target
     * use is calendar drop downs.
     * @param array $zoneIdsArray at least one zoneid is required
     * @param array|null $regionIdsArray optional - default null, if null the regionids filter is not used.
     * @param array|null $groupCategoryArray optional - default null, if null the group category filter is not used.
     * @return array
     */
    public static function GetGroupsAndChapterRows (array $zoneIdsArray, ?array $regionIdsArray = null, ?array $groupCategoryArray = null) : array
    {
        global $_COMPANY, $_ZONE, $_USER;

        $zoneIdsCsv = implode(',', Sanitizer::SanitizeIntegerArray($zoneIdsArray) ?: [0]);

        $rows = self::DBROGet("
                SELECT 
                       g.groupid, g.regionid AS group_regionids, g.groupname, g.groupname_short, g.zoneid AS group_zoneids, g.group_category,g.categoryid, g.isactive as group_is_active,
                       g.group_type as group_type, g.content_restrictions as content_restrictions,
                       c.chapterid, c.regionids AS chapter_regionids, c.chaptername, c.zoneid AS chapter_zoneids, c.isactive AS chapter_is_active
                FROM `groups` g LEFT JOIN `chapters` c USING (`groupid`) 
                WHERE g.`companyid`={$_COMPANY->id()} 
                    AND g.zoneid IN ({$zoneIdsCsv})
                    AND g.isactive = 1
            ");


        foreach ($rows as $k => $v) {
            if ($v['content_restrictions'] === 'members_only_can_view' && !($_USER ?-> canViewContent($v['groupid']))) {
                unset ($rows[$k]);
            }
        }

        // Filter out all rows that do not have a matching group category
        if ($groupCategoryArray !== null) {
            $rows = array_filter($rows,function ($value) use ($groupCategoryArray) {
                return (in_array($value['categoryid'], $groupCategoryArray));
            });
        }

        $keptRows = [];
        $chapterDataBlank = array (
            'chapterid' => '0',
            'chapter_regionids' => '0',
            'chaptername' => '',
            'chapter_zoneids' => '0',
            'chapter_is_active' => '0'
            );

        $groupOnlyRows = Arr::Unique(Arr::KeepColumns($rows, ['groupid','group_regionids','groupname','groupname_short','group_zoneids','group_category','categoryid','group_is_active']), 'groupid');
        // First add group only rows
        foreach ($groupOnlyRows as $groupOnlyRow) {
            if (!empty($regionIdsArray) && !array_intersect(explode(',', $groupOnlyRow['group_regionids']), $regionIdsArray)) {
                continue;
            }

            $keptRows[] = array_merge($groupOnlyRow, $chapterDataBlank);
        }
        // Since this is a complex filtering, we will go through each row and remove the ones that need to be filtered out.
        foreach ($rows as $row) {
            if (empty($row['chapterid'])) {
                continue; // This is group only row which has already been processed
            }

            if ($row['chapter_is_active'] != 1) {
                continue;// Remove inactive chapter rows
            }

            if (!empty($regionIdsArray) && !array_intersect(explode(',', $row['chapter_regionids']), $regionIdsArray)) {
                continue; // Remove row if chapter region does not match one of the specified one.
            }
            $keptRows[] = $row;
        }

        //Logger::Log('GetGroupsAndChapterRows',Logger::SEVERITY['INFO'], ['count' => count($rows), 'zoneIdsArray' => $zoneIdsArray, 'regionIdsArray' => $regionIdsArray, 'groupCategoryArray'=>$groupCategoryArray]);
        return array_values($keptRows);
    }

    public function getTeamMatchingAttributeKeyVisibilitySetting(string $type,string $category, string $key)
    {
        $visibility = self::MATCHING_ATTRIBUTES_VISIBILITY_KEYS_DEFAULT_VALUE[$key];

        $matchingParameters = $this->getTeamMatchingAlgorithmParameters();
        if ($matchingParameters){
            if ($type == 'primary_parameters'){
                $setting = $matchingParameters['mandatory_primary_parameters'] ?? null;
            }  else {
                $setting = $matchingParameters['mandatory_custom_parameters'] ?? null;
            }

            if (!empty($setting[$category][$key])) {
                $visibility = $setting[$category][$key];
            }
        }
        return $visibility;
    }

    public function getProgramDisclaimer()
    {
        global $_COMPANY, $_ZONE;
        $key = self::GROUP_ATTRIBUTES_KEYS['program_disclaimer'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return null;
    }

    public function saveProgramDisclaimer(string $disclaimer)
    {
        global $_COMPANY, $_ZONE;

        // Now save them.
        $key = self::GROUP_ATTRIBUTES_KEYS['program_disclaimer'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }
        if ($disclaimer) {
            $attributes[$key] = $disclaimer;
        } else {
            unset($attributes[$key]);
        }
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    /**
     * @param string $label
     * @param string $shortLabel
     * @param string $desc
     * @return int|string
     */
    public static function CreateGroupCategory(string $label,string $shortLabel,string $desc)
    {
        global $_COMPANY,$_ZONE;
        $retVal = self::DBInsertPS("INSERT INTO group_categories (`companyid`,`zoneid`,`category_label`, category_name, `category_description`) VALUES (?,?,?,?,?)", 'iissx', $_COMPANY->id(), $_ZONE->id(),$label, $shortLabel, $desc);

        $_COMPANY->expireRedisCache("GRP_CAT:{$_ZONE->id()}");

        return $retVal;
    }

    /**
     * This method is like GetAllGroupCategoriesByZone but uses $_ZONE as default zone.
     * @param bool $sort_results_by_sorting_order
     * @return array
     */
    public static function GetAllGroupCategories(bool $sort_results_by_sorting_order = false): array
    {
        global $_ZONE;
        return self::GetAllGroupCategoriesByZone($_ZONE, $sort_results_by_sorting_order, false);
    }

    /**
     * @param Zone $zone
     * @param bool $sort_results_by_sorting_order
     * @param bool $active_only set to true to get active only group categories
     * @return array
     */
    public static function GetAllGroupCategoriesByZone(Zone $zone, bool $sort_results_by_sorting_order, bool $active_only): array
    {
        global $_COMPANY;
        $key = "GRP_CAT:{$zone->id()}";
        $group_categories = [];
        if (($group_categories = $_COMPANY->getFromRedisCache($key)) === false) {
            $group_categories = self::DBGet("SELECT * FROM group_categories WHERE `companyid`='{$_COMPANY->id()}' AND zoneid='{$zone->id()}'");
            $_COMPANY->putInRedisCache($key, $group_categories, 86400);
        }

        if ($active_only) {
            $group_categories = array_filter($group_categories, function($group_category) {
                return $group_category['isactive'] == 1;
            });
        }

        if($sort_results_by_sorting_order){
            usort($group_categories, function ($a, $b) {
                return strcmp($a['sorting_order'], $b['sorting_order']);
            });
        }

        return $group_categories;
    }

    /**
     * Returns Group Category Row by searching in default $_ZONE
     * @param int $categoryId
     * @return array
     */
    public static function GetGroupCategoryRow(int $categoryId): array
    {
        global $_ZONE;
        return self::GetGroupCategoryRowByZone($_ZONE, $categoryId);
    }

    /**
     * Returns Group Category Row by searching in given $zone
     * @param Zone $zone
     * @param int $categoryId
     * @return array
     */
    public static function GetGroupCategoryRowByZone(Zone $zone, int $categoryId): array
    {
        return Arr::SearchColumnReturnRow(
            self::GetAllGroupCategoriesByZone($zone, false, false),
            $categoryId,
            'categoryid'
        );
    }

    /**
     * Return Default Group Category Row for the default $_ZONE
     * @return array
     */
    public static function GetDefaultGroupCategoryRow(): array
    {
        global $_ZONE;
        return self::GetDefaultGroupCategoryRowByZone($_ZONE);
    }

    /**
     * Return Default Group Category Row for the given $zone
     * @param Zone $zone
     * @return array
     */
    public static function GetDefaultGroupCategoryRowByZone(Zone $zone): array
    {
        return Arr::SearchColumnReturnRow(
            self::GetAllGroupCategoriesByZone($zone, false, true),
            1,
            'is_default_category'
        );
    }

    /**
     * @param int $categoryId
     * @return array
     */
    public static function GetGroupCategoryById(int $categoryId): array
    {
        global $_COMPANY,$_ZONE;
        $result = self::DBGet("SELECT * FROM group_categories WHERE `companyid`='{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' AND categoryid='{$categoryId}'");
        if($result){
            return $result[0];
        }
        return array();
    }

    /**
     * @param int $categoryId
     * @param string $label
     * @param string $shortLabel
     * @param string $desc
     * @return int
     */
    public static function UpdateGroupCategory(int $categoryId, string $label,string $shortLabel,string $desc)
    {
        global $_COMPANY,$_ZONE;
        $retVal =  self::DBMutatePS("UPDATE group_categories SET `category_label`= ?,category_name=?,`category_description`=?,`modifiedon`=now() WHERE companyid = ?  AND zoneid= ? AND `categoryid`=?", 'ssxiii', $label, $shortLabel, $desc, $_COMPANY->id, $_ZONE->id, $categoryId);
        if($retVal){
            $_COMPANY->expireRedisCache("GRP_CAT:{$_ZONE->id()}");
        }
        

        return $retVal;
    }

    /**
     * @param array $newOrder
     * @return int
     */
    public static function UpdateGroupCategoryOrder(array $newOrder)
    {
        global $_COMPANY, $_ZONE;
        $cases = [];
        $ids = [];
        $retVal = 0;
        foreach ($newOrder as $order => $id) {
            $id = (int)$id;
            $order = (int)$order + 1; 
            $cases[] = " WHEN `categoryid` = $id THEN $order";
            $ids[] = $id; 
        }

        if(!empty($cases)){
            $idsString = implode(',', array_map('intval', $ids));
            $caseStatement = implode(' ', $cases);
            $retVal = self::DBUpdatePS("UPDATE group_categories SET `sorting_order`= CASE $caseStatement END WHERE `companyid`=? AND `zoneid`=? AND `categoryid` IN ($idsString)",'ii', $_COMPANY->id(), $_ZONE->id());

            $_COMPANY->expireRedisCache("GRP_CAT:{$_ZONE->id()}");
        }
        return $retVal;
    }

    /**
     * @param int $categoryId
     * @return int
     */
    public static function DeleteGroupCategory(int $categoryId)
    {
        global $_COMPANY, $_ZONE;
        // Note default group_category cannot be deleted.
        $retVal = self::DBMutate("DELETE FROM group_categories WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `categoryid`={$categoryId} AND `is_default_category`=0");

        $_COMPANY->expireRedisCache("GRP_CAT:{$_ZONE->id()}");
        $_COMPANY->expireRedisCache("ZONE_GRP_CAT_TAGS:{$_ZONE->id()}");

        return $retVal;
    }

        /**
     * @param int $categoryId
     * @param string $label
     * @param string $shortLabel
     * @param string $desc
     * @return int
     */
    public static function UpdateGroupCategoryStatus(int $categoryId, int $status)
    {
        global $_COMPANY,$_ZONE;
        $retVal =  self::DBMutatePS("UPDATE group_categories SET `isactive`= ?,`modifiedon`=now() WHERE companyid = ?  AND zoneid= ? AND `categoryid`=?", 'iiii', $status, $_COMPANY->id, $_ZONE->id, $categoryId);
        if($retVal){
            $_COMPANY->expireRedisCache("GRP_CAT:{$_ZONE->id()}");
        }

        return $retVal;
    }

    /**
     * @param $categoryId
     * @return array
     */
    public static function GetGroupIdsByCategoryId($categoryId)
    {
        global $_COMPANY, $_ZONE;
        return self::DBROGet("SELECT groupid FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `categoryid`={$categoryId}");
    }

    public function isContentRestrictedToMembers()
    {
        return $this->val('content_restrictions') == self::GROUP_CONTENT_RESTRICTIONS['members_only_can_view'];
    }

    // Member restrictions
    public function getGroupMemberRestrictions(){
        // Extract restrictions
        $key = self::GROUP_ATTRIBUTES_KEYS['member_restrictions'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return array();
    }

    public static function GetAllRestrictedGroups(array $zoneids = null)
    {
        global $_COMPANY;

        $zone_filter = '';
        if (!is_null($zoneids)) {
            $zids = implode(',', Arr::IntValues($zoneids));
            if (!empty($zids)) {
                $zone_filter = " AND zoneid IN ({$zids})";
            }
        }
        $group_rows = self::DBROGet("SELECT * FROM `groups` WHERE companyid={$_COMPANY->id()} {$zone_filter} AND attributes IS NOT NULL AND LENGTH(attributes) > 0 AND JSON_EXTRACT(attributes, '$.member_restrictions') IS NOT NULL");
        $group_objects = [];
        foreach ($group_rows as $group_row) {
            $group_objects[] = new Group($group_row['groupid'], $_COMPANY->id(), $group_row);
        }
        return $group_objects;
    }

    /**
     * Checks whether the user is allowed to join this group
     * @param int|null|User $subject_user
     * @return bool
     */
    public function isUserAllowedToJoin(null|int|User $subject_user) : bool
    {
		global $_COMPANY;
		$allowed = true;

        if (is_null($subject_user)) {
            return false;
        }

        $restrictions = $this->getGroupMemberRestrictions();
        foreach($restrictions as $key=> $val) {
            $matchWithkeys = $val['keys'];
            $type = $val['type'];
            if ($type  == '0' || empty($matchWithkeys)){ // Ignore
                continue;
            }

            $userCategoryKey = UserCatalog::GetCatalogKeynameForUser($key, $subject_user);
            if (empty($userCategoryKey)) {
                // If we cannot get the $userCategoryKey then we cannot determine if the user is allowed or not
                // so we will exit with a false.
                $allowed = false;
                break;
            }

            if ($type == 1){ // IN ; if user is IN one of these category keys then DO allow to join => i.e. if user not in this list, deny join;
                if (!in_array($userCategoryKey,$matchWithkeys)){
                    $allowed = false;
                    break;
                }
            } elseif($type == 2){ // Not IN; if user is NOT IN one of these category keys then do allow to join => i.e. if user IN this list, deny join.
                if (in_array($userCategoryKey,$matchWithkeys)){
                    $allowed = false;
                    break;
                }
            }
        }

		return $allowed;
	} 
    public function applyGroupMemberRestrictions(array $restrictionsData)
    {
        $key = self::GROUP_ATTRIBUTES_KEYS['member_restrictions'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        if (empty($restrictionsData)) {
            unset ($attributes[$key]);
        } else {
            $attributes[$key] = $restrictionsData;
        }
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }
    // Request to join ERG - Join request settings
    public function updateJoinRequestMailSettings(bool $enable_mail_to_leaders, bool $enable_specific_emails, string $specific_emails)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['join_request_mail_settings'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }
        $attributes[$key] = array(
            'mail_to_leader' => $enable_mail_to_leaders,
            'mail_to_specific_emails' => $enable_specific_emails,
            'specific_emails' => $specific_emails
        );

        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function getJoinRequestMailSettings():array {
        $key = self::GROUP_ATTRIBUTES_KEYS['join_request_mail_settings'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return array( // Default values
            'mail_to_leader' => true,
            'mail_to_specific_emails' => false,
            'specific_emails' => ''
        );
    }

    public function removeNonCompliantGroupMembers(string $initiated_by): void
    {
        $users = $this->getAllMembers();

        array_walk($users, function (array $user) use ($initiated_by) {
            $user = User::GetUser($user['userid']);

            if ($user->isAllowedToJoinGroup($this->id())) {
                return;
            }

            $user->leaveGroup($this->id(), 0, 0, false, false, $initiated_by);
        });
    }


    public function updateActionItemTouchPointProgressBarSetting(string $contextKey, int $status)
    {
        if (!in_array($contextKey, array('show_actionitem_progress_bar','show_touchpoint_progress_bar')))
            return 0;

        $key = self::GROUP_ATTRIBUTES_KEYS['actionitem_touchpoint_progress_bar_setting'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        if (array_key_exists($key, $attributes)) {
            $setting = $attributes[$key];
            $setting[$contextKey] = $status;
        } else {
            $setting = array('show_actionitem_progress_bar'=>1,'show_touchpoint_progress_bar'=>1); // Default
            $setting[$contextKey] = $status;
        }
        
        $attributes[$key] = $setting;
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function getActionItemTouchPointProgressBarSetting()
    {
        $key = self::GROUP_ATTRIBUTES_KEYS['actionitem_touchpoint_progress_bar_setting'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return array('show_actionitem_progress_bar'=>1,'show_touchpoint_progress_bar'=>1); // Defalut show
    }

    public function getTeamCustomAttributesQuestion(string $questionKeyNameValue)
    {
        $question = array();
        $customAttributes = $this->getTeamMatchingAlgorithmAttributes();
        if (!empty($customAttributes)){
            $customAttributes = $customAttributes['pages'];
            foreach ($customAttributes as $key => $elements) {
                if (array_key_exists('elements',$elements)) {
                    $questions = $elements['elements'];
                    $question = Arr::SearchColumnReturnRow($questions,$questionKeyNameValue,'name');
                }
                if (!empty($question)) {
                    break;
                }
            }
        }
        return $question;
    }


     /**
     * Returns an array of all group leads who can approve an event.
     * @return array
     */
    public function getGroupApproversToAcceptTopicCollaborationInvites() : array
    {
        global $_COMPANY;

        return self::DBROGet("
            SELECT users.userid, email, firstname, lastname, picture, jobtitle  
            FROM groupleads
                JOIN users ON groupleads.userid = users.userid 
                JOIN grouplead_type ON groupleads.grouplead_typeid = grouplead_type.typeid
            WHERE users.companyid = {$_COMPANY->id()} 
              AND users.isactive = 1
              AND grouplead_type.isactive = 1
              AND (groupleads.groupid={$this->id()} AND grouplead_type.sys_leadtype=2  AND grouplead_type.allow_publish_content=1 )
            ");
    }

    public static function GetChaptersApproversToAcceptTopicCollaborationInvites(int $chapterid) : array
    {
        global $_COMPANY;

        return self::DBROGet("
            SELECT users.userid, email, firstname, lastname, picture, jobtitle  
            FROM chapterleads
                JOIN users ON chapterleads.userid = users.userid 
                JOIN grouplead_type ON chapterleads.grouplead_typeid = grouplead_type.typeid
            WHERE users.companyid = {$_COMPANY->id()} 
              AND users.isactive = 1
              AND grouplead_type.isactive = 1
              AND (chapterleads.chapterid ={$chapterid} AND grouplead_type.sys_leadtype=4  AND grouplead_type.allow_publish_content=1 )
            ");
    }


    public static function IsGroupInCurrentZone (array $groupids, int $zoneid) {
       
        global $_COMPANY; /* @var Company $_COMPANY */
        if (empty($groupids)) {
            return false;
        }
        $rows = self::DBGet("SELECT `groupid` FROM `groups` WHERE `companyid`='{$_COMPANY->id()}' AND zoneid={$zoneid} AND isactive=1");

        if (!empty($rows)) {
            if (!empty(array_intersect($groupids,array_column($rows,'groupid')))) {
                return true;
            }
        }
        return false;
    }



    public static function FilterGroupsWithoutMatchingChapters(string $groupIds, string $chapterids) {
        if (empty($groupIds)) {
            return array();
        }
        $groupsWithoutChapters = array();
        $groupIdsArray = explode(',', $groupIds);
        $chapterIdsArray = explode(',', $chapterids);
        foreach ($groupIdsArray as $groupid) {
            $groupChapters = self::GetChapterList($groupid);
            if (empty($groupChapters)) {
                $groupsWithoutChapters[] = $groupid;
            } else{
                $groupIdsChapteridsArray = array_column($groupChapters,'chapterid');
                if (empty(array_intersect($groupIdsChapteridsArray, $chapterIdsArray))) {
                    $groupsWithoutChapters[] = $groupid;
                }
            }
        }
        return $groupsWithoutChapters;
    }

    public static function GetGroupChaptersFromChapterIdsCSV(int $groupid, string $chaptersIds) {

        $chapterIdsArray = array();
        if (!empty($chaptersIds)){
            $chapters = self::GetChapterList($groupid);
            if (!empty($chapters)) {
                $groupChapterIds = array_column($chapters,'chapterid');
                if (!empty($groupChapterIds)) {
                    $chaptersIds = explode(',', $chaptersIds);
                    $chapterIdsArray = array_intersect($groupChapterIds,$chaptersIds);
                }
            }
        }
        return implode(',', $chapterIdsArray);
    }

    public static function GetUserGloballyJoinedGroups(int $userid) {
        global $_COMPANY;
        $joinedGroups = self::DBROGet("SELECT `groupmembers`.groupid from `groupmembers` join `groups` on (`groupmembers`.`groupid`=`groups`.`groupid`) WHERE `userid`='{$userid}' AND `groupmembers`.`isactive`=1 AND `groups`.`isactive`=1 AND `groups`.`companyid`='{$_COMPANY->id()}'");
        $finalData = array();
        foreach($joinedGroups as $joined) {
            $finalData[] = self::GetGroup($joined['groupid']);
        }

        return $finalData;
    }
    public function getActionGonfiguration()
    {
        global $_COMPANY;
        $key = self::GROUP_ATTRIBUTES_KEYS['action_item_configuration'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                if (!isset($attributes[$key]['action_item_visibility'])){
                    $attributes[$key]['action_item_visibility'] = self::ACTION_ITEM_VISIBILITY_SETTING['show_to_all'];
                }
                return $attributes[$key];
            }
        }
        return array(
            'action_item_visibility'=>self::ACTION_ITEM_VISIBILITY_SETTING['show_to_all']
        );
    }

    public function updateActionItemConfiguration(string $action_item_visibility)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['action_item_configuration'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }
        $attributes[$key]['action_item_visibility'] = $action_item_visibility;
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public static function GetGroupLeadDetailByUserid (int $groupid, int $userid) {
        $detail = self::DBROGet("SELECT groupleads.*,grouplead_type.type FROM `groupleads` LEFT JOIN grouplead_type on grouplead_type.typeid = groupleads.grouplead_typeid WHERE groupleads.groupid='{$groupid}' AND groupleads.`userid`='{$userid}'");
        return $detail ? $detail[0] : null;
    }

    public static function GetChapterLeadDetailByUserid (int $chapterid, int $userid) {
        $detail =  self::DBROGet("SELECT chapterleads.`leadid`,chapterleads.`grouplead_typeid`,chapterleads.`roletitle`,chapterleads.priority,chapterleads.assigneddate,grouplead_type.type FROM `chapterleads` LEFT JOIN grouplead_type on grouplead_type.typeid = chapterleads.grouplead_typeid WHERE chapterleads.chapterid='{$chapterid}' AND chapterleads.`userid`='{$userid}' ");
        return $detail ?$detail[0] : null;
    }

    public static function GetChannelLeadDetailByUserid (int $channelid, int $userid) {
        $detail = self::DBROGet("SELECT group_channel_leads.*,grouplead_type.type FROM `group_channel_leads` LEFT JOIN grouplead_type on grouplead_type.typeid = group_channel_leads.grouplead_typeid WHERE group_channel_leads.channelid='{$channelid}' AND group_channel_leads.`userid`='{$userid}'");
        return $detail ? $detail[0] : null;
    }

    public function getTeamWorkflowSetting()
    {
        $key = self::GROUP_ATTRIBUTES_KEYS['team_workflow_setting'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return array(
            'hide_member_in_discover_tab' => 0,
            'any_mentor_can_complete_team' => 1,
            'any_mentee_can_complete_team' => 0,
            'auto_complete_team_on_action_touchpoints_complete' => 0,
            'automatically_close_team_after_n_days' => 0,
            'auto_complete_team_ndays_after_mentee_start_date' => 0
        );
    }

    public function saveTeamWorkflowSetting(string $settingKey, int $settingValue)
    {
        global $_COMPANY, $_ZONE;

        $default_team_workflow_setting = array(
            'hide_member_in_discover_tab' => 0,
            'any_mentor_can_complete_team' => 1,
            'any_mentee_can_complete_team' => 0,
            'auto_complete_team_on_action_touchpoints_complete' => 0,
            'automatically_close_team_after_n_days' => 0,
            'auto_complete_team_ndays_after_mentee_start_date' => 0,
        );

        if (!in_array($settingKey, array_keys($default_team_workflow_setting))) {
            return; // If $settingKey is not valid do not store it.
        }

        $key = self::GROUP_ATTRIBUTES_KEYS['team_workflow_setting'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        if (array_key_exists($key, $attributes)) {
            $team_workflow_setting = $attributes[$key];
        } else {
            $team_workflow_setting = $default_team_workflow_setting;
        }

        $team_workflow_setting[$settingKey] = $settingValue;
        $attributes[$key] = $team_workflow_setting;
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }


    public static function GetBookingCustomName(bool $defaultNamePlural = false){
		global $_COMPANY, $_ZONE, $_USER;
		if ($defaultNamePlural){
			$name = gettext('Schedule Meetings');
		} else {
			$name = gettext('Schedule Meeting');
		}

		$lang = $_USER->val('language') ?? 'en';
		$altNames = $_COMPANY->getAppCustomization()['booking']['alt_name'] ?? array();

		if(!empty($altNames) && array_key_exists($lang,$altNames)){

			$altName  = $altNames[$lang];
			if (!empty($altName)){
				return $altName;
			}
		}
		return $name;

	}
    
    public function getAllGroupLeadsBySupportType()
    {
        global $_COMPANY;
        $groupLeads = self::DBGet("
            SELECT groupleads.leadid,users.userid,users.firstname,users.lastname,users.email, `groups`.groupname,`groups`.groupid 
            FROM `groupleads` 
                JOIN `users` ON groupleads.userid = users.userid
                JOIN `groups` ON `groups`.groupid = groupleads.groupid
                JOIN `grouplead_type` ON grouplead_type.typeid=`groupleads`.`grouplead_typeid`
            WHERE
                `groups`.companyid = {$_COMPANY->id()}
                AND `groupleads`.`groupid` = {$this->id()}
                AND grouplead_type.allow_manage_support=1
                AND grouplead_type.sys_leadtype IN(2)
            ");

        if (!empty($groupLeads)) {
            return $groupLeads;
        }
        return array();
    }


    public function getSupportBookingSlots()
    {
        global $_USER;
        $groupSupportLeads = $this->getAllGroupLeadsBySupportType();
        if (empty($groupSupportLeads)) {
            return array();
        }
        $availableBookingSlots =  array();
        $groupSupportLeadsUserIds = array_column($groupSupportLeads,'userid');
        $groupAvailableSchedules = UserSchedule::GetAllActiveGroupSupportSchedulesByGroup($this->id());
       
        foreach($groupAvailableSchedules  as $schedule) {
            if (!in_array($schedule->val('userid'), $groupSupportLeadsUserIds)){
                continue;
            }
            //$availableBookingSlots[] = $schedule;
        }
        return $availableBookingSlots;
    }


    public static function GetAllGroupsForScheduleConfiguration(string $scope): array
    {
        global $_COMPANY;

        if (!in_array($scope, ['bookings', 'teams'])) {
            return array();
        }

        $configuration_block = ($scope === 'bookings') ? 'booking' : 'teams'; // Fix for label differences.
        // Get all enabled zone IDs for 'app.my_schedule'
        $enabledZones = array_filter( // remove falsy - empty values
            array_map(
                function ($zone) use ($configuration_block) {
                    return ($zone['customization']['app']['my_schedule']['enabled'] && $zone['customization']['app'][$configuration_block]['enabled']) ? $zone['zoneid'] : '';
                },
                $_COMPANY->getZones()
        ));

        if (empty($enabledZones)) {
            return [];
        }

        $zoneIdsCsv = implode(',', Arr::IntValues($enabledZones));
        $allowedGroupTypes = implode(',', [Group::GROUP_TYPE_OPEN_MEMBERSHIP, Group::GROUP_TYPE_REQUEST_TO_JOIN]);

        // Fetch active groups in the enabled zones
        $groupRows = self::DBROGet("
            SELECT 
                zonename, groupid, groupname
            FROM `groups` 
                JOIN `company_zones` USING (companyid, zoneid) 
            WHERE companyid = {$_COMPANY->id()} 
              AND `company_zones`.zoneid IN ({$zoneIdsCsv})
              AND `groups`.isactive = 1
              AND `groups`.group_type IN ({$allowedGroupTypes})
        ");

        if (empty($groupRows)) {
            return [];
        }

        // Sort by group name
        usort($groupRows, fn($a, $b) => strcmp($a['groupname'], $b['groupname']));

        return $groupRows;
    }


    public function getBookingStartBuffer()
    {
        global $_COMPANY;
        $key = self::GROUP_ATTRIBUTES_KEYS['booking_start_buffer'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (isset($attributes['booking']) && array_key_exists($key, $attributes['booking'])) {
                return $attributes['booking'][$key];
            }
        }
        return array(
            'days_before_start_to_allow_booking'=>0
        );
    }

    public function updateBookingStartBuffer(int $numberOfDays)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['booking_start_buffer'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }
        $attributes['booking'][$key]['days_before_start_to_allow_booking'] = $numberOfDays;
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function getBookingEmailTemplate()
    {
        global $_COMPANY;
        $key = self::GROUP_ATTRIBUTES_KEYS['booking_email_template'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (isset($attributes['booking']) && array_key_exists($key, $attributes['booking'])) {
                return $attributes['booking'][$key];
            }
        }
        return array(
            'booking_email_subject'=>'',
            'booking_message' => ''
        );
    }

    public function getMeetingEmailTemplate(string $attribute_key = 'meeting_email_template')
    {
        global $_COMPANY;
        $key = self::GROUP_ATTRIBUTES_KEYS[$attribute_key];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?? array();
            if (isset($attributes['booking']) && array_key_exists($key, $attributes['booking'])) {
                $template = $attributes['booking'][$key];
                
                // Add reminder_days if this is meeting_reminder_email_template
                if ($attribute_key === 'meeting_reminder_email_template') {
                    if (!isset($template['reminder_days'])) {
                        $template['reminder_days'] = 1;
                    }
                    if (!isset($template['final_reminder_days'])) {
                        $template['final_reminder_days'] = 1;
                    }
                }
                
                return $template;
            }
        }
        
        $default = array(
            'booking_email_subject'=>'',
            'booking_message' => ''
        );
        
        // Add defaults for reminder template
        if ($attribute_key === 'meeting_reminder_email_template') {
            $default['reminder_days'] = 1;
            $default['final_reminder_days'] = 1;
        }
        
        return $default;
    }

      public function updateMeetingEmailTemplate(string $booking_email_subject, string $booking_message, string $attribute_key = 'meeting_email_template', $reminder_days = null, $final_reminder_days = null)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS[$attribute_key];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }

        $templateArr = array(
            'booking_email_subject' => $booking_email_subject,
            'booking_message' => $booking_message
        );

        // Add reminder_days and final_reminder_days if this is the reminder template and values are provided
        if ($attribute_key === 'meeting_reminder_email_template') {
            if ($reminder_days !== null) {
                $templateArr['reminder_days'] = (int)$reminder_days;
            }
            if ($final_reminder_days !== null) {
                $templateArr['final_reminder_days'] = (int)$final_reminder_days;
            }
        }

        $attributes['booking'][$key] = $templateArr;
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

    public function updateBookingEmailTemplate(string $booking_email_subject, string $booking_message)
    {
        global $_COMPANY, $_ZONE;

        $key = self::GROUP_ATTRIBUTES_KEYS['booking_email_template'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }
        $attributes['booking'][$key] = array('booking_email_subject'=>$booking_email_subject,'booking_message' => $booking_message);
        $attributes = json_encode($attributes);

        return $this->updateGroupAttributes($attributes);
    }

}
