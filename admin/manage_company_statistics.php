<?php
require_once __DIR__.'/head.php';
$pagetitle = "Company Statistics";
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

$weekEarlier = date("Y-m-d",strtotime("-7 day",strtotime(today()[0])));
$companyStats = array();
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
   
}

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
    //$c_user_member_unique = array_column($companyStats,'user_member_unique');
    //$c_user_member_total = array_column($companyStats,'user_member_total');
}

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/manage_company_statistics.html');
include(__DIR__ . '/views/footer.html');
