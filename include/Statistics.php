<?php
// Do no use require_once as this class is included in Company.php.
date_default_timezone_set('UTC');

class Statistics extends Teleskope
{

    const STATISTIC_TYPE_COMPANY = 1;
    const STATISTIC_TYPE_ZONE = 2;
    const STATISTIC_TYPE_GROUP = 3;
    const STATISTIC_TYPE_ZONE_TXNS = 4;
    const STATISTIC_TYPE_EVENT_COUNTERS = 21;

    protected $statistic_type = 0;

    protected function __construct(int $cid=0, array $fields=array())
    {
        parent::__construct(-1, $cid, $fields);
    }

    public function __destruct()
    {
    }

    protected function _generateStatistics ()
    {
        // Will be implmented in subclass
    }

    public function generateStatistics()
    {

        return $this->_generateStatistics();
    }

//    public static function GetStatisticRec(string $analyticid): array
//    {
//        //global $_COMPANY; /* @var Company $_COMPANY */
//
//        //return self::DBROGetPS("SELECT * FROM `company_analytics` WHERE companyid=? AND (analyticid=? AND isactive=1) LIMIT 1",'ix',$_COMPANY->id(), $analyticid);
//    }
//
//    public static function CreateStatisticRec (string $analyticname, string $analyticdescription, int $analytictype, string $purpose, string $analyticmeta) : string
//    {
////        global $_COMPANY,$_ZONE; /* @var Company $_COMPANY */
////        global $_USER; /* @var User $_USER */
////
////        $analyticid = $_COMPANY->val('subdomain')."_".time();
////
////
////        $q = self::DBUpdatePS("INSERT INTO `company_analytics`(`analyticid`, `companyid`, `zoneid`, `analyticname`, `analyticdescription`, `analytictype`, `analyticmeta`, `purpose`, `createdby`) VALUE (?,?,?,?,?,?,?,?,?)",'xiissixsi',$analyticid,$_COMPANY->id(),$_ZONE->id(),$analyticname,$analyticdescription,$analytictype,$analyticmeta,$purpose, $_USER->id());
////        if ($q) {
////            return $analyticid;
////        } else {
////            return '';
////        }
//    }
//
//    public static function UpdateStatisticRec (string $analyticid, string $analyticname, string $analyticdescription, int $analytictype, string $purpose, string $analyticmeta) : int
//    {
////        global $_COMPANY; /* @var Company $_COMPANY */
////        return self::DBUpdatePS("UPDATE company_analytics SET analyticname=?, analyticdescription=?, analytictype=?, purpose=?, analyticmeta=? WHERE companyid=? AND (analyticid=?)",'ssisxis',$analyticname,$analyticdescription,$analytictype,$purpose,$analyticmeta,$_COMPANY->id(), $analyticid);
//    }
//
//    public static function DeleteStatisticRec (string $analyticid) : int
//    {
////        global $_COMPANY; /* @var Company $_COMPANY */
////        return self::DBUpdatePS("UPDATE company_analytics SET isactive=100 WHERE companyid=? AND (analyticid=?)",'ix',$_COMPANY->id(),$analyticid);
//    }
}

class CompanyStatistics extends Statistics {

    public function __construct(int $cid= 0 , array $fields = array())
    {
        parent::__construct($cid, $fields);
        $this->statistic_type = self::STATISTIC_TYPE_COMPANY;
    }

    protected function _generateStatistics ()
    {
        $stat_date = date("Y-m-d");
        $companies = self::DBROGet("SELECT `companyid`,(SELECT COUNT(1) FROM company_admins WHERE company_admins.companyid=companies.companyid and zoneid=0) AS admin_global_level, (SELECT COUNT(1) FROM company_admins WHERE company_admins.companyid=companies.companyid and zoneid > 0) AS admin_zone_level, (SELECT COUNT(1) FROM users WHERE users.companyid=companies.companyid and users.isactive=1) AS user_total,(SELECT COUNT(1) FROM `company_zones` WHERE company_zones.`companyid`=companies.companyid and company_zones.isactive=1) AS number_of_zones,(SELECT COUNT(1) FROM `regions` WHERE regions.`companyid`=companies.companyid and regions.isactive=1) AS regions,(SELECT COUNT(1) FROM `departments` WHERE departments.companyid=companies.companyid and departments.isactive=1) AS departments,(SELECT COUNT(1) FROM `companybranches` WHERE companybranches.companyid=companies.companyid and companybranches.isactive=1) AS branches FROM `companies` WHERE companies.isactive=1");

        foreach($companies as $company){

            $companyid = $company['companyid'];
            $admin_global_level = $company['admin_global_level'];
            $admin_zone_level = $company['admin_zone_level'];
            $number_of_zones = $company['number_of_zones'];
            $regions  = $company['regions'];
            $departments = $company['departments'];
            $branches = $company['branches'];
            $user_total = $company['user_total'];
            $members = self::DBROGet("SELECT COUNT(1) AS user_member_total, COUNT(DISTINCT userid) AS user_member_unique FROM groupmembers left join users using (userid) WHERE users.companyid={$companyid} AND users.isactive=1 AND groupmembers.isactive=1;");
            $user_member_unique = $members[0]['user_member_unique'] ?? 0;
            $user_member_total = $members[0]['user_member_total'] ?? 0;

            $stat_keys = "stat_date='{$stat_date}',companyid={$companyid}";
            $stat_values = "admin_global_level={$admin_global_level},admin_zone_level={$admin_zone_level},number_of_zones={$number_of_zones},regions={$regions},departments={$departments},branches={$branches},user_total={$user_total},user_member_unique={$user_member_unique},user_member_total={$user_member_total},createdon=now()";
            self::DBInsert("INSERT INTO stats_company_daily_count SET {$stat_keys},{$stat_values} ON DUPLICATE KEY UPDATE {$stat_values}");
        }
        
        return true;
    }
}

class ZoneStatistics extends Statistics {

    public function __construct(int $cid= 0 , array $fields = array())
    {
        parent::__construct($cid, $fields);
        $this->statistic_type = self::STATISTIC_TYPE_ZONE;
    }

    protected function _generateStatistics ()
    {
        $stat_date = date("Y-m-d");
        $zones = self::DBROGet("SELECT `companyid`,`zoneid`,`regionids`, (SELECT COUNT(1) FROM company_admins WHERE company_admins.companyid=company_zones.companyid and zoneid=company_zones.zoneid) as zone_admins, (SELECT COUNT(1) FROM companybranches WHERE companybranches.companyid=company_zones.companyid AND FIND_IN_SET(companybranches.regionid,(company_zones.regionids))) AS zone_branches, (SELECT COUNT(1) FROM `groups` WHERE `groups`.companyid=company_zones.companyid AND`groups`.`zoneid`=company_zones.zoneid) AS number_of_groups FROM `company_zones`;");

        foreach($zones as $zone){
            $companyid= $zone['companyid'];
            $zoneid= $zone['zoneid'];
            $zone_admins= $zone['zone_admins'];
            $zone_regions = count(explode(',',$zone['regionids'] ?? ''));
            $zone_branches= $zone['zone_branches'];
            $number_of_groups = $zone['number_of_groups']; 

            $groups = self::DBROGet("SELECT IFNULL(GROUP_CONCAT(`groupid`),0) AS groupIds FROM `groups` WHERE `zoneid`= {$zoneid}");

            $members = self::DBROGet("SELECT COUNT(1) AS user_members_total, COUNT(DISTINCT userid) AS user_members_unique FROM groupmembers LEFT JOIN users USING (userid) WHERE groupid IN ({$groups[0]['groupIds']}) AND users.isactive=1 AND groupmembers.isactive=1");
            $user_members_unique = $members[0]['user_members_unique'] ?? 0;
            $user_members_total = $members[0]['user_members_total'] ?? 0;

            $member_counts = self::DBROGet("SELECT SUM(group_1) AS sgroup_1, SUM(group_2) AS sgroup_2, SUM(group_3) AS sgroup_3 FROM (SELECT SUM(if(agg1.no_of_groups = 1, 1, 0)) AS group_1, SUM(if(agg1.no_of_groups = 2, 1, 0)) AS group_2, SUM(if(agg1.no_of_groups > 2, 1, 0)) AS group_3 FROM (SELECT COUNT(1) AS no_of_groups FROM groupmembers LEFT JOIN users USING (userid) WHERE groupid IN({$groups[0]['groupIds']}) AND users.isactive=1 AND groupmembers.isactive=1 GROUP BY groupmembers.`userid`) agg1 GROUP BY agg1.no_of_groups) agg2");
            $user_members_1group = intval($member_counts[0]['sgroup_1'] ?? 0);
            $user_members_2group = intval($member_counts[0]['sgroup_2'] ?? 0);
            $user_members_3group = intval($member_counts[0]['sgroup_3'] ?? 0);

            $stat_keys = "stat_date='{$stat_date}',companyid={$companyid},zoneid={$zoneid}";
            $stat_values = "zone_admins={$zone_admins},zone_regions={$zone_regions},zone_branches={$zone_branches},user_members_unique={$user_members_unique},user_members_1group={$user_members_1group},user_members_2group={$user_members_2group},user_members_3group={$user_members_3group},user_members_total={$user_members_total},number_of_groups={$number_of_groups},createdon=now()";
            self::DBInsert("INSERT INTO stats_zones_daily_count SET {$stat_keys},{$stat_values} ON DUPLICATE KEY UPDATE {$stat_values}");

        }
        
        return true;
    }
}

class GroupStatistics extends Statistics {

    public function __construct(int $cid= 0 , array $fields = array())
    {
        parent::__construct($cid, $fields);
        $this->statistic_type = self::STATISTIC_TYPE_GROUP;
    }

    protected function _generateStatistics ()
    {
        $stat_date = date("Y-m-d");
        $groups = self::DBROGet("SELECT `groupid`,`companyid`,`zoneid`,`regionid`,(SELECT COUNT(1) FROM  `chapters` WHERE `chapters`.`groupid`=`groups`.groupid) AS group_chapters,(SELECT COUNT(1) FROM `group_channels` WHERE `group_channels`.`groupid`=`groups`.groupid) AS group_channels FROM `groups`");
        $group_leads = self::DBROGet("SELECT groupid,SUM(IF(grouplead_type.sys_leadtype=1,1,0)) AS group_admin_1, SUM(IF(grouplead_type.sys_leadtype=2,1,0)) AS group_admin_2, SUM(IF(grouplead_type.sys_leadtype=3,1,0)) AS group_admin_3 FROM `groupleads` LEFT JOIN grouplead_type ON grouplead_type.typeid = groupleads.grouplead_typeid LEFT JOIN users USING (userid) WHERE users.isactive=1 GROUP BY groupid");
        $chapter_leads = self::DBROGet("SELECT groupid,COUNT(1) AS group_admin_4 FROM chapterleads LEFT JOIN users USING (userid) WHERE users.isactive=1 GROUP BY groupid");
        $channel_leads = self::DBROGet("SELECT groupid,COUNT(1) AS group_admin_5 FROM group_channel_leads LEFT JOIN users USING (userid) WHERE users.isactive=1 GROUP BY groupid");
        $group_members = self::DBROGet("SELECT groupid,COUNT(1) AS user_members_group, SUM(IF(groupmembers.chapterid='0',0,1)) AS user_members_chapters, SUM(IF(groupmembers.channelids='0',0,1)) AS user_members_channels FROM `groupmembers` JOIN users USING (userid) WHERE users.isactive=1 AND groupmembers.isactive=1 GROUP BY groupid");
        $album_media = self::DBROGet("SELECT zoneid,groupid,COUNT(1) AS album_media_published FROM album_media LEFT JOIN albums USING (albumid) GROUP BY zoneid, groupid");
        $resources = self::DBROGet("SELECT groupid, COUNT(1) AS resources_published FROM group_resources WHERE group_resources.isactive > 0 and group_resources.resource_type in (1,2) GROUP BY groupid");
        // Since the following content can be created at the admin level, we need zoneid as well to identify admin content, i.e. groupid=0
        $events = self::DBROGet("SELECT zoneid, groupid, SUM(IF(isactive=1,1,0)) AS events_published, SUM(IF(isactive IN (2,3,4,5),1,0)) AS events_draft, SUM(IF(end<now() and isactive=1,1,0)) AS events_completed FROM events WHERE events.isactive > 0 GROUP BY zoneid,groupid");
        $posts = self::DBROGet("SELECT zoneid, groupid, SUM(IF(isactive=1,1,0)) AS posts_published, SUM(IF(isactive IN (2,3,4,5),1,0)) AS posts_draft FROM post WHERE post.isactive > 0 GROUP BY zoneid,groupid");
        $newsletters = self::DBROGet("SELECT zoneid, groupid, SUM(IF(isactive=1,1,0)) AS newsletters_published, SUM(IF(isactive IN (2,3,4,5),1,0)) AS newsletters_draft FROM newsletters WHERE newsletters.isactive > 0 GROUP BY zoneid,groupid");
        $surveys = self::DBROGet("SELECT zoneid, groupid, SUM(IF(isactive=1,1,0)) AS surveys_published, SUM(IF(isactive=2,1,0)) AS surveys_draft FROM surveys_v2 WHERE surveys_v2.isactive > 0 GROUP BY zoneid,groupid");
        $team_registrations = self::DBROGet("SELECT SUM(IF(sys_team_role_type=2,1,0)) AS mentors_registered, SUM(IF(sys_team_role_type=3,1,0)) AS mentees_registered FROM member_join_requests JOIN team_role_type USING(roleid) JOIN users ON member_join_requests.userid = users.userid WHERE sys_team_role_type IN (2,3) GROUP BY zoneid,member_join_requests.groupid");
        $team_roles = self::DBROGet("SELECT teams.zoneid, teams.groupid, SUM(IF(teams.isactive=1 AND sys_team_role_type=2,1,0)) AS mentors_active, SUM(IF(teams.isactive=110 AND sys_team_role_type=2,1,0)) AS mentors_completed, SUM(IF(teams.isactive=109 AND sys_team_role_type=2,1,0)) AS mentors_not_completed, SUM(IF(teams.isactive=1 AND sys_team_role_type=3,1,0)) AS mentees_active, SUM(IF(teams.isactive=110 AND sys_team_role_type=3,1,0)) AS mentees_completed, SUM(IF(teams.isactive=109 AND sys_team_role_type=3,1,0)) AS mentees_not_completed, count(1) AS total FROM teams JOIN team_members USING(teamid) JOIN team_role_type USING(roleid) JOIN users ON team_members.userid = users.userid WHERE sys_team_role_type IN (2,3) GROUP BY zoneid,groupid");
        $teams = self::DBROGet("select zoneid,groupid,SUM(IF(teams.isactive=1,1,0)) AS teams_active, SUM(IF(teams.isactive=110,1,0)) AS teams_completed,SUM(IF(teams.isactive=109,1,0)) AS teams_not_completed,SUM(IF(teams.isactive=2,1,0)) AS teams_draft,SUM(IF(teams.isactive=0,1,0)) AS teams_inactive FROM teams GROUP BY zoneid, groupid");

        $unique_zones = array();

        foreach($groups as $group){
            $companyid = intval($group['companyid']);
            $zoneid = intval($group['zoneid']);
            $groupid = intval($group['groupid']);
            $group_chapters = ($group['group_chapters']);
            $group_channels = ($group['group_channels']);

            $events_draft = intval(Arr::SearchColumnReturnColumnVal($events, $groupid, 'groupid', 'events_draft'));
            $events_published = intval(Arr::SearchColumnReturnColumnVal($events, $groupid, 'groupid', 'events_published'));
            $events_completed = intval(Arr::SearchColumnReturnColumnVal($events, $groupid, 'groupid', 'events_completed'));
            $posts_draft = intval(Arr::SearchColumnReturnColumnVal($posts, $groupid, 'groupid', 'posts_draft'));
            $posts_published = intval(Arr::SearchColumnReturnColumnVal($posts, $groupid, 'groupid', 'posts_published'));
            $newsletters_draft = intval(Arr::SearchColumnReturnColumnVal($newsletters, $groupid, 'groupid', 'newsletters_draft'));
            $newsletters_published = intval(Arr::SearchColumnReturnColumnVal($newsletters, $groupid, 'groupid', 'newsletters_published'));
            $resources_published = intval(Arr::SearchColumnReturnColumnVal($resources, $groupid, 'groupid', 'resources_published'));
            $surveys_draft = intval(Arr::SearchColumnReturnColumnVal($surveys, $groupid, 'groupid', 'surveys_draft'));
            $surveys_published = intval(Arr::SearchColumnReturnColumnVal($surveys, $groupid, 'groupid', 'surveys_published'));
            $album_media_published = intval(Arr::SearchColumnReturnColumnVal($album_media, $groupid, 'groupid', 'album_media_published'));
            $teams_roles_row = Arr::SearchColumnReturnRow($team_roles, $groupid, 'groupid');
            $teams_mentors_active = empty($teams_roles_row) ? 0 : intval($teams_roles_row['mentors_active']);
            $teams_mentors_completed = empty($teams_roles_row) ? 0 : intval($teams_roles_row['mentors_completed']);
            $teams_mentors_not_completed = empty($teams_roles_row) ? 0 : intval($teams_roles_row['mentors_not_completed']);
            $teams_mentees_active = empty($teams_roles_row) ? 0 : intval($teams_roles_row['mentees_active']);
            $teams_mentees_completed = empty($teams_roles_row) ? 0 : intval($teams_roles_row['mentees_completed']);
            $teams_mentees_not_completed = empty($teams_roles_row) ? 0 : intval($teams_roles_row['mentees_not_completed']);
            $teams_row = Arr::SearchColumnReturnRow($teams, $groupid, 'groupid');
            $teams_active = empty($teams_row) ? 0 : intval($teams_row['teams_active']);
            $teams_completed = empty($teams_row) ? 0 : intval($teams_row['teams_completed']);
            $teams_not_completed = empty($teams_row) ? 0 : intval($teams_row['teams_not_completed']);
            $teams_draft = empty($teams_row) ? 0 : intval($teams_row['teams_draft']);
            $teams_inactive = empty($teams_row) ? 0 : intval($teams_row['teams_inactive']);
            $teams_registrations_row = Arr::SearchColumnReturnRow($team_registrations, $groupid, 'groupid');
            $teams_mentors_registered = empty($teams_registrations_row) ? 0 : intval($teams_registrations_row['mentors_registered']);
            $teams_mentees_registered = empty($teams_registrations_row) ? 0 : intval($teams_registrations_row['mentees_registered']);
            $group_admin_1 = intval(Arr::SearchColumnReturnColumnVal($group_leads, $groupid, 'groupid', 'group_admin_1'));
            $group_admin_2 = intval(Arr::SearchColumnReturnColumnVal($group_leads, $groupid, 'groupid', 'group_admin_2'));
            $group_admin_3 = intval(Arr::SearchColumnReturnColumnVal($group_leads, $groupid, 'groupid', 'group_admin_3'));
            $group_admin_4 = intval(Arr::SearchColumnReturnColumnVal($chapter_leads, $groupid, 'groupid', 'group_admin_4'));
            $group_admin_5 = intval(Arr::SearchColumnReturnColumnVal($channel_leads, $groupid, 'groupid', 'group_admin_5'));

            $user_members_group = intval(Arr::SearchColumnReturnColumnVal($group_members, $groupid, 'groupid', 'user_members_group'));
            $user_members_chapters = intval(Arr::SearchColumnReturnColumnVal($group_members, $groupid, 'groupid', 'user_members_chapters'));
            $user_members_channels = intval(Arr::SearchColumnReturnColumnVal($group_members, $groupid, 'groupid', 'user_members_channels'));

            $stat_keys = "stat_date='{$stat_date}',companyid={$companyid},zoneid={$zoneid},groupid={$groupid}";
            $stat_values = "group_chapters={$group_chapters},group_channels={$group_channels},group_admin_1={$group_admin_1},group_admin_2={$group_admin_2},group_admin_3={$group_admin_3},group_admin_4={$group_admin_4},group_admin_5={$group_admin_5},user_members_group={$user_members_group},user_members_chapters={$user_members_chapters},user_members_channels={$user_members_channels},events_draft={$events_draft},events_published={$events_published},events_completed={$events_completed},posts_draft={$posts_draft},posts_published={$posts_published},newsletters_draft={$newsletters_draft},newsletters_published={$newsletters_published},resources_published={$resources_published},surveys_draft={$surveys_draft},surveys_published={$surveys_published},album_media_published={$album_media_published},teams_mentors_active={$teams_mentors_active},teams_mentors_completed={$teams_mentors_completed},teams_mentors_not_completed={$teams_mentors_not_completed},teams_mentors_registered={$teams_mentors_registered},teams_mentees_active={$teams_mentees_active},teams_mentees_completed={$teams_mentees_completed},teams_mentees_not_completed={$teams_mentees_not_completed},teams_mentees_registered={$teams_mentees_registered},teams_active={$teams_active},teams_completed={$teams_completed},teams_not_completed={$teams_not_completed},teams_inactive={$teams_inactive},teams_draft={$teams_draft},createdon=now()";
            self::DBInsert("INSERT INTO stats_groups_daily_count SET {$stat_keys},{$stat_values} ON DUPLICATE KEY UPDATE {$stat_values}");


            // Next add stats for groupid=0 (Admin Content) for each of the zones.
            if (!in_array($zoneid, $unique_zones)) {
                $unique_zones[] = $zoneid;
                $groupid = 0;

                $events_row = array_filter($events,
                    function ($value) use ($zoneid) {
                        return ($value['groupid'] == 0 && $value['zoneid'] == $zoneid);
                    });
                $events_row  = array_values($events_row);
                $events_draft = $events_row[0]['events_draft'] ?? 0;
                $events_published = $events_row[0]['events_published'] ?? 0;
                $events_completed = $events_row[0]['events_completed'] ?? 0;

                $posts_row = array_filter($posts,
                    function ($value) use ($zoneid) {
                        return ($value['groupid'] == 0  && $value['zoneid'] == $zoneid);
                    });
                $posts_row  = array_values($posts_row);
                $posts_draft = $posts_row[0]['posts_draft'] ?? 0;
                $posts_published = $posts_row[0]['posts_published'] ?? 0;

                $newsletters_row = array_filter($newsletters,
                    function ($value) use ($zoneid) {
                        return ($value['groupid'] == 0  && $value['zoneid'] == $zoneid);
                    });
                $newsletters_row  = array_values($newsletters_row);
                $newsletters_draft = $newsletters_row[0]['newsletters_draft'] ?? 0;
                $newsletters_published = $newsletters_row[0]['newsletters_published'] ?? 0;

                $surveys_row = array_filter($surveys,
                    function ($value) use ($zoneid) {
                        return ($value['groupid'] == 0  && $value['zoneid'] == $zoneid);
                    });
                $surveys_row  = array_values($surveys_row);
                $surveys_draft = $surveys_row[0]['surveys_draft'] ?? 0;
                $surveys_published = $surveys_row[0]['surveys_published'] ?? 0;

                $stat_keys = "stat_date='{$stat_date}',companyid={$companyid},zoneid={$zoneid},groupid=0";
                $stat_values = "group_chapters=0,group_channels=0,group_admin_1=0,group_admin_2=0,group_admin_3=0,group_admin_4=0,group_admin_5=0,user_members_group=0,user_members_chapters=0,user_members_channels=0,events_draft={$events_draft},events_published={$events_published},events_completed={$events_completed},posts_draft={$posts_draft},posts_published={$posts_published},newsletters_draft={$newsletters_draft},newsletters_published={$newsletters_published},resources_published=0,surveys_draft={$surveys_draft},surveys_published={$surveys_published},album_media_published=0,teams_mentors_active=0,teams_mentors_completed=0,teams_mentors_not_completed=0,teams_mentors_registered=0,teams_mentees_active=0,teams_mentees_completed=0,teams_mentees_not_completed=0,teams_mentees_registered=0,teams_active=0,teams_completed=0,teams_not_completed=0,teams_inactive=0,teams_draft=0,createdon=now()";
                self::DBInsert("INSERT INTO stats_groups_daily_count SET {$stat_keys},{$stat_values} ON DUPLICATE KEY UPDATE {$stat_values}");

            }
        }
        return true;
    }

    /**
     * @param int $groupid
     * @param string $month in 'Y-m' format
     * @return array|mysqli_result
     */
    public static function GetGroupMonthlyLatestStatistics(int $groupid, string $month){
        global $_COMPANY,$_ZONE;
        return  self::DBROGet("SELECT *, DATE_FORMAT(`stat_date`, '%b %y') as `groupTimeLabel` FROM `stats_groups_daily_count` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND  `groupid`='{$groupid}' AND stat_date LIKE '{$month}%' ORDER BY stat_date DESC LIMIT 1 " );
    }
}

class ZoneTxnsStatistics extends Statistics {

    public function __construct(int $cid= 0 , array $fields = array())
    {
        parent::__construct($cid, $fields);
        $this->statistic_type = self::STATISTIC_TYPE_ZONE_TXNS;
    }

    protected function _generateStatistics ()
    {
        
        $date = date('Y-m-d');

       $txns = self::DBROGet("SELECT `companyid`,`zoneid`, (SELECT IFNULL(SUM(IF(usageif!='email',1,0)),0) FROM `appusage` WHERE  `appusage`.`zoneid`=company_zones.zoneid AND `appusage`.`usagetime` LIKE '%".$date."%') AS user_logins,(SELECT IFNULL(SUM(IF(usageif='email',1,0)),0) AS email FROM `appusage` WHERE  `appusage`.`zoneid`=company_zones.zoneid AND `appusage`.`usagetime` LIKE '%".$date."%') AS emails_in FROM `company_zones` WHERE company_zones.isactive=1");

        foreach($txns as $txn){
            $stat_date = date("Y-m-d");
            $companyid = $txn['companyid'];
            $zoneid = $txn['zoneid'];
            $user_logins = $txn['user_logins'];
            $emails_in = $txn['emails_in'];
            $emails_out =0; #todo

            //self::DBInsert("INSERT INTO `stats_zones_daily_txns`(`stat_date`, `companyid`, `zoneid`, `user_logins`, `emails_out`, `emails_in`) VALUES ('{$stat_date}','{$companyid}','{$zoneid}','{$user_logins}','{$emails_out}','{$emails_in}')");
        }
        return true;
    }
}

class EventCounters extends Statistics
{

    public function __construct(int $cid = 0, array $fields = array())
    {
        parent::__construct($cid, $fields);
        $this->statistic_type = self::STATISTIC_TYPE_EVENT_COUNTERS;
    }

    protected function _generateStatistics()
    {
        $filter = "AND (end > now() - interval 5 day AND end < now() + interval 1 day)"; // Finalize the stats
        //$filter = '';
        $events = self::DBROGet("SELECT `eventid`,`event_series_id` FROM `events` WHERE isactive=1 {$filter}");

        // First iterate over all the not head events
        foreach ($events as $event) {
            $is_series = 0;
            if ($event['eventid'] == $event['event_series_id']) {
                $is_series = 1;
            }
            Event::GenerateEventRSVPCounters($event['eventid'], $is_series);
            Event::GenerateEventCheckinCounters($event['eventid'], $is_series);
        }
        return true;
    }
}