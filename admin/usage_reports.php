<?php
require_once __DIR__.'/head.php';
$pagetitle = "Usage Reports";

const chart_bgcolor_1 = '#ffadadff';
const chart_bgcolor_2 = '#ffd6a5ff';
const chart_bgcolor_3 = '#caffbfff';
const chart_bgcolor_4 = '#9bf6ffff';
const chart_bgcolor_5 = '#a0c4ffff';
const chart_bgcolor_6 = '#bdb2ffff';
const chart_bgcolor_7 = '#ffc6ffff';
const chart_bgcolor_8 = '#fdffb6ff';


if (isset($_GET['d']) && is_numeric($_GET['d']) && date("Y")>$_GET['d']){
	$date = date('Y-m-d', strtotime('12/31/'. $_GET['d']));
	$year = date("Y",strtotime($date));
	$lastyear = $year-1;
	$_month = date("n",strtotime($date));
	$month = date("m",strtotime($date));
	$day = date("d",strtotime($date));
	$monthName = date("M",strtotime($date));
} else {
	$year = date("Y");
	$_month = date("n");
	$month = date("m");
	$day = date("d");
	$monthName = date("M");
	$lastyear = $year-1;
}

$section = "1";
if (isset($_GET['section'])){
    $section = $_COMPANY->decodeId($_GET['section'] );
}

$groupid = 0;
if (isset($_GET['groupid'])){
    $groupid = $_COMPANY->decodeId($_GET['groupid']);
}

// check for group categories
$groupCategoryRows = Group::GetAllGroupCategories(true);
$groupCategoryIds = array_column($groupCategoryRows, 'categoryid');

if (isset($_GET['filter']) && is_array($_GET['filter'])){
    $group_category_id =  array_map('intval', array_intersect($_GET['filter'], $groupCategoryIds));
}else {
    $group_category_id = [(int)Group::GetDefaultGroupCategoryRow()['categoryid']];
}
$groupCategoryFilter = $group_category_id ? " AND categoryid IN(".implode(',', $group_category_id).")" : "";
$groups = $db->ro_get("SELECT `groupid`, `groupname_short` FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND zoneid={$_ZONE->id()} $groupCategoryFilter AND `isactive`=1");
usort($groups,function($a,$b) {
    // Sort groups alphabetically
    return strcmp($a['groupname_short'], $b['groupname_short']);
});

## Interaction Section
if ($section == 1){
    /**
     * Logins && Email Rsvp
    */

    $loginData = array();
    $emailRsvpsData = array();
    $app_type = $_ZONE->val('app_type');

    $rows = $db->ro_get("SELECT  MONTH(`usagetime`) as `month`, sum(IF(usageif='{$app_type}',1,0)) as login, sum(IF(usageif='email',1,0)) as email from appusage WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND YEAR(`usagetime`)={$year} GROUP BY MONTH(`usagetime`)");

    include(__DIR__ . '/views/header.html');
    include(__DIR__ . '/views/usage_report_interactions.html');


} else { ## Event Participant Section

    $departmentid = 0;
    $departments = $_COMPANY->getAllDepartments();

    if (isset($_GET['departmentid'])){
        $departmentid = $_COMPANY->decodeId($_GET['departmentid']);
    }

    $groupids = 0;
    if ($groupid>0){
        $groupids = $groupid;
    } else {
        if(count($groups)){
            $groupids = implode(',',array_column($groups,'groupid'));
        }
    }

    $rsvpNo = Event::RSVP_TYPE['RSVP_NO'];

    $departmentCondition = "";
    if ($departmentid){
        $departmentCondition = " JOIN users ON users.userid=eventjoiners.userid AND users.department='".$departmentid."' ";
    }

    $rows = $db->ro_get("SELECT MONTH(start) AS `month`, count(distinct IF(joinstatus!={$rsvpNo},eventjoiners.userid,-1)) AS unique_rsvps, SUM(IF(joinstatus!={$rsvpNo},1,0)) as total_rsvps, count(distinct(eventjoiners.eventid)) as total_events, sum(IF(checkedin_date,1,0)) as total_attendees, count(distinct IF(checkedin_date,eventjoiners.userid,-1)) as unique_attendees, count(1) as totals_rows  FROM `eventjoiners` LEFT JOIN events USING (eventid) {$departmentCondition} WHERE YEAR(start)='{$year}' AND groupid IN ({$groupids}) GROUP BY MONTH(start)");

    foreach ($rows as &$row) {
        $row['unique_attendees'] = $row['unique_attendees'] ? $row['unique_attendees'] - 1 : 0; // If set, reduce by one as count distinct will provide one additional
        $row['average_rsvps'] = round($row['total_rsvps'] / $row['total_events'], 0);
        $row['average_attendees'] = round($row['total_attendees'] / $row['total_events'], 0);
    }
    unset($row);

    // Print the charts
    include(__DIR__ . '/views/header.html');
    include(__DIR__ . '/views/usage_report_event_participants.html');
}

