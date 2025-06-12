<?php
require_once __DIR__.'/head.php';
$pagetitle = "Event Reporting";
if (isset($_GET['d']) && is_numeric($_GET['d']) && date("Y")>$_GET['d']){
	$date = date('Y-m-d', strtotime('12/31/'. $_GET['d']));
	$year = date("Y",strtotime($date));
	$lastyear = $year-1;
  // Setting the month filter
  $_month = isset($_GET['m']) && is_numeric($_GET['m']) && $_GET['m'] >= 1 && $_GET['m'] <= 12 ? (int)$_GET['m'] : date("n",strtotime($date));
  $month = str_pad($_month, 2, "0", STR_PAD_LEFT);
	$day = date("d",strtotime($date));
	$monthName = date("F",mktime(0,0,0,$month,1));
} else {
	$year = date("Y");
  // Setting the month filter
  $_month = isset($_GET['m']) && is_numeric($_GET['m']) && $_GET['m'] >= 1 && $_GET['m'] <= 12 ? (int)$_GET['m'] : date("n");

  	$month = str_pad($_month, 2, "0", STR_PAD_LEFT);
	$day = date("d");
	$monthName = date("F",mktime(0,0,0,$month,1));
	$lastyear = $year-1;
}

$filter = "0";
if (isset($_GET['filter'])){
	$filter = $_GET['filter'];
}

// check for group categories
$groupCategoryRows = Group::GetAllGroupCategories(true);
$groupCategoryIds = array_column($groupCategoryRows, 'categoryid');

if (isset($_GET['filter']) && in_array($_GET['filter'],$groupCategoryIds)){
	$group_category_id = (int)$_GET['filter'];
} else {
	$group_category_id = (int)Group::GetDefaultGroupCategoryRow()['categoryid'];
}

$groups = $db->ro_get("SELECT `groupid`, `companyid`, `regionid`, `groupname_short`, `overlaycolor` FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} and `isactive`=1 AND categoryid={$group_category_id}");

$eventCategories = Event::GetEventTypesByZones([$_ZONE->id()]);

$categoresNames = [];
if (count($eventCategories)){
    $categoresNames = array_column($eventCategories,'type');
}

$eventsByGroups             = array();
$eventsByCategories         = array();
$rsvpByGroups               = array();
$rsvpByCategories           = array();


if (count($groups)){
	$groupids = implode(',',array_column($groups,'groupid'));
	$monthFilter = (!empty($_GET['m']) && is_numeric($_GET['m'])) ? " AND MONTH(`start`) = '{$_month}' " : "";
	$allEvents = $db->ro_get("SELECT groupid,eventtype,COUNT(1) as totalEvents FROM `events` WHERE `groupid` IN ({$groupids}) AND `companyid`={$_COMPANY->id()}  AND YEAR(`start`)='".$year."' $monthFilter AND `isactive`=1 GROUP BY groupid,eventtype");

	foreach ($groups as $group) {
		$gid = $group['groupid'];
		$eventsByCat = array();

		$filteredRows = array_filter($allEvents,function ($value) use ($gid) {
			return ($value['groupid'] == $gid);
		});

		foreach ($eventCategories as $category) {
			$catEvents = Arr::SearchColumnReturnColumnVal($filteredRows, $category['typeid'], 'eventtype', 'totalEvents') ?: 0;
			$eventsByCat[] = ['category'=>$category['type'],'totalEvent'=>$catEvents];
		}

		$totalEventsInGroup = array_sum(array_column($filteredRows,'totalEvents'));
		$eventsByGroups[]  = ['groupname_short'=>$group['groupname_short'],'totalEvent'=>$totalEventsInGroup,'color'=>$group['overlaycolor']];
		$eventsByCategories[] = ['groupname_short'=>$group['groupname_short'],'categories'=>$eventsByCat,'color'=>$group['overlaycolor']];
	}
}

//print"<pre>"; print_r($eventsByCategories); die();
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/event_reporting.html');
?>
