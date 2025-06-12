<?php
require_once __DIR__.'/head.php';
$pagetitle = "Dashboard";
$companyid=$_SESSION['companyid'];
$today	=	date("Y-m-d",strtotime(today()[0]));
$time	=	$db->convertTimetoSec(date("H:i:s",strtotime(today()[0])));
$check = 1;

// App Usage
$usage = array(); //$db->get("SELECT DATE_FORMAT(usagetime,'%Y-%m') AS date,DATE_FORMAT(usagetime,'%m') AS month,DATE_FORMAT(usagetime,'%Y') AS year, COUNT(userid) AS total FROM appusage WHERE companyid='{$companyid}' AND (zoneid='{$_ZONE->id()}' AND `usagetime` NOT REGEXP '^[0-9\.]+$') GROUP BY DATE_FORMAT(usagetime,'%Y-%m') ORDER BY usagetime asc");

$admin_global_level = 0;
$admin_zone_level = 0;
$number_of_zones = 0;
$totalRegions = 0;
$totalDepartments = 0;
$totalBranches = 0;
$user_total = 0;
$user_member_unique = 0;
$user_member_total = 0;

#Chart Variable
$companyTimeLabel = [];
$c_admin_global_level = [];
$c_admin_zone_level = [];
$c_number_of_zones = [];
$c_totalRegions = [];
$c_totalDepartments = [];
$c_totalBranches = [];
$c_user_total = [];
$c_user_member_unique = [];
$c_user_member_total = [];

// ZONE
$zone_admins = 0;
$zone_regions = 0;
$zone_branches = 0;
$user_members_unique = 0;
$user_members_1group = 0;
$user_members_2group = 0;
$user_members_3group = 0;
$user_members_total = 0;
$number_of_groups = 0;
$zone_no_of_events = 0;
$zone_no_of_posts = 0;
$zone_no_of_newsletters = 0;
$zone_no_of_album_media = 0;

#Chart Variable
$zoneTimeLabel = [];
$c_zone_admins = [];
$c_zone_regions = [];
$c_zone_branches = [];
$c_user_members_unique = [];
$c_user_members_1group = [];
$c_user_members_2group = [];
$c_user_members_3group = [];
$c_user_members_total = [];
$c_number_of_groups = [];
$c_zone_no_of_events = [];
$c_zone_no_of_posts = [];
$c_zone_no_of_newsletters = [];
$c_zone_no_of_album_media = [];

$y = date('Y');
$m = date('m');

//$weekEarlier = date("Y-m-d",strtotime("-7 day",strtotime(today()[0])));
//$companyStats = array();
$zoneStats = array();
$zoneContentStats = array();
$lastmonth = "";
for ($i = 11; $i >= 0; $i--) {
    if($lastmonth){
        $lastmonth = date("Y-m", strtotime("next month",strtotime($lastmonth))); // Generate month in YYYY-mm- format
    } else {
        $lastmonth = date("Y-m", strtotime("-{$i} months",strtotime(date("Y-m-01"))));
    }
    $month = date("Y-m-",strtotime($lastmonth));
    // COMPANY stats
    $companyStatsMonthly = $db->ro_get("SELECT *,DATE_FORMAT(`stat_date`, '%b %y') as `companyTimeLabel` FROM `stats_company_daily_count` WHERE `companyid`='{$_COMPANY->id()}' AND stat_date LIKE '{$month}%' ORDER BY stat_date DESC LIMIT 1");
    
    if (count($companyStatsMonthly)){
        $companyStats[] = $companyStatsMonthly[0];
        $companyStatsAsOf  = $db->covertUTCtoLocalAdvance("F d, Y g:i a",' T P',$companyStatsMonthly[0]['createdon'],$_SESSION['timezone']);
    } else {

        $companyStats[] = array(
            "stat_date" => date("Y-m-d", strtotime($month."01")),
            "companyid" => 0,
            "admin_global_level" => 0,
            "admin_zone_level" => 0,
            "number_of_zones" => 0,
            "regions" => 0,
            "departments" => 0,
            "branches" => 0,
            "user_total" => 0,
            "user_member_unique" => 0,
            "user_member_total" => 0,
            "companyTimeLabel" => date("M y", strtotime($month."01")),
            "createdon" => date("Y-m-d H:i:s", strtotime($month."01"))
        );
        if (!isset($companyStatsAsOf)){
            $companyStatsAsOf  = $db->covertUTCtoLocalAdvance("F d, Y g:i a",' T P',date("Y-m-d"),$_SESSION['timezone']);
        }
    }

   

    // ZONE stats
    $zoneStatsMonthly = $db->ro_get("SELECT *,DATE_FORMAT(`stat_date`, '%b %y') as `zoneTimeLabel`  FROM `stats_zones_daily_count` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND stat_date LIKE '{$month}%' ORDER BY stat_date DESC LIMIT 1");

    $zoneContentStatsMonthly = $db->ro_get("SELECT sum(events_published) as events_published, sum(posts_published) as posts_published, sum(newsletters_published) as newsletters_published, sum(resources_published) as resources_published, sum(surveys_published) as surveys_published, sum(album_media_published) as album_media_published,DATE_FORMAT(`stat_date`, '%b %y') as `zoneTimeLabel`,stat_date FROM stats_groups_daily_count WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND stat_date LIKE '{$month}%' GROUP BY stat_date ORDER BY stat_date DESC LIMIT 1");
   
    if (count($zoneStatsMonthly)){
        $zoneStats[] = $zoneStatsMonthly[0];
        $zoneStatsAsOf  = $db->covertUTCtoLocalAdvance("F d, Y g:i a",' T(P)',$zoneStatsMonthly[0]['createdon'],$_SESSION['timezone']);
    } else {

        $zoneStats[] =  array(
            "stat_date" => date("Y-m-d", strtotime($month."01")),
            "companyid" => 0,
            "zoneid" => 0,
            "zone_admins" => 0,
            "zone_regions" => 0,
            "zone_branches" => 0,
            "user_members_unique" => 0,
            "user_members_1group" => 0,
            "user_members_2group" => 0,
            "user_members_3group" => 0,
            "user_members_total" => 0,
            "user_logins" => null,
            "emails_out" => null,
            "emails_in" => null,
            "number_of_groups" => 0,
            "zoneTimeLabel" => date("M y", strtotime($month."01")),
            "createdon" => date("Y-m-d H:i:s", strtotime($month."01"))
        );
        if (!isset($zoneStatsAsOf)){
            $zoneStatsAsOf  = $db->covertUTCtoLocalAdvance("F d, Y g:i a",' T(P)',date("Y-m-d"),$_SESSION['timezone']);
        }
    }
    if (!empty( $zoneContentStatsMonthly)){
        $zoneContentStats[] = $zoneContentStatsMonthly[0];
    } else {
        $zoneContentStats[] = array(
                                    "events_published" => 0,
                                    "posts_published" => 0,
                                    "newsletters_published" => 0,
                                    "resources_published" => 0,
                                    "surveys_published" => 0,
                                    "album_media_published" => 0,
                                    "zoneTimeLabel" => date("M y", strtotime($month."01")),
                                    "stat_date" => date("Y-m-d", strtotime($month."01")),
                                );
    }
}

/*
 * On 2022-11-15 disabled this block
if (!empty($companyStats)){
    usort($companyStats,function($a,$b) {
        return strcmp($a['stat_date'], $b['stat_date']);
    });
    $companyStats_end = end($companyStats);
    $admin_global_level = $companyStats_end['admin_global_level'];
    $admin_zone_level = $companyStats_end['admin_zone_level'];
    $number_of_zones = $companyStats_end['number_of_zones'];
    $totalRegions = $companyStats_end['regions'];
    $totalDepartments = $companyStats_end['departments'];
    $totalBranches = $companyStats_end['branches'];
    $user_total = $companyStats_end['user_total'];
    $user_member_unique = $companyStats_end['user_member_unique'];
    $user_member_total = $companyStats_end['user_member_total'];

    $companyTimeLabel = array_column($companyStats,'companyTimeLabel');
    $c_admin_global_level = array_column($companyStats,'admin_global_level');
    $c_admin_zone_level = array_column($companyStats,'admin_zone_level');
    $c_number_of_zones = array_column($companyStats,'number_of_zones');
    $c_totalRegions = array_column($companyStats,'regions');
    $c_totalDepartments = array_column($companyStats,'departments');
    $c_totalBranches = array_column($companyStats,'branches');
    $c_user_total = array_column($companyStats,'user_total');
    $c_user_member_unique = array_column($companyStats,'user_member_unique');
    $c_user_member_total = array_column($companyStats,'user_member_total');
}
*/

if (count($zoneStats)){
    usort($zoneStats,function($a,$b) {
        return strcmp($a['stat_date'], $b['stat_date']);
    });
    usort($zoneContentStats,function($a,$b) {
        return strcmp($a['stat_date'], $b['stat_date']);
    });

    // Override membership stats for the latest month with some real time values;
    $latest_entry = array_key_last($zoneStats);
    $member_count_array = $_ZONE->getAllMembersCount(); //echo "<pre>";print_r($member_count_array);exit();
    $zoneStats[$latest_entry]['user_members_total'] = $member_count_array['user_members_total'];
    $zoneStats[$latest_entry]['user_members_unique'] = $member_count_array['user_members_unique'];
    $zoneStats[$latest_entry]['user_members_1group'] = $member_count_array['user_members_1group'];
    $zoneStats[$latest_entry]['user_members_2group'] = $member_count_array['user_members_2group'];
    $zoneStats[$latest_entry]['user_members_3group'] = $member_count_array['user_members_3group'];
    // End of real time calculations

    $zoneStats_end = end($zoneStats);
    $zoneContentStats_end = end($zoneContentStats);
    $zone_admins = $zoneStats_end['zone_admins'];
    $zone_regions = $zoneStats_end['zone_regions'];
    $zone_branches = $zoneStats_end['zone_branches'];
    $user_members_unique = $zoneStats_end['user_members_unique'];
    $user_members_1group = $zoneStats_end['user_members_1group'];
    $user_members_2group = $zoneStats_end['user_members_2group'];
    $user_members_3group = $zoneStats_end['user_members_3group'];
    $user_members_total = $zoneStats_end['user_members_total'];
    $number_of_groups = $zoneStats_end['number_of_groups'];
    $zone_no_of_events = $zoneContentStats_end['events_published'];
    $zone_no_of_posts = $zoneContentStats_end['posts_published'];
    $zone_no_of_newsletters = $zoneContentStats_end['newsletters_published'];
    $zone_no_of_album_media = $zoneContentStats_end['album_media_published'];

    #Chart

    $zoneTimeLabel = array_column($zoneStats,'zoneTimeLabel');
    $c_zone_admins = array_column($zoneStats,'zone_admins');
    $c_zone_regions = array_column($zoneStats,'zone_regions');
    $c_zone_branches = array_column($zoneStats,'zone_branches');
    $c_user_members_unique = array_column($zoneStats,'user_members_unique');
    $c_user_members_1group = array_column($zoneStats,'user_members_1group');
    $c_user_members_2group = array_column($zoneStats,'user_members_2group');
    $c_user_members_3group = array_column($zoneStats,'user_members_3group');
    $c_user_members_total = array_column($zoneStats,'user_members_total');
    $c_number_of_groups = array_column($zoneStats,'number_of_groups');
    $c_zone_no_of_events = array_column($zoneContentStats,'events_published');
    $c_zone_no_of_posts = array_column($zoneContentStats,'posts_published');
    $c_zone_no_of_newsletters = array_column($zoneContentStats,'newsletters_published');
    $c_zone_no_of_album_media = array_column($zoneContentStats,'album_media_published');
}
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/dashboardres.html');
