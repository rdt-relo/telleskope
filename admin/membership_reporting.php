<?php
require_once __DIR__.'/head.php';
$pagetitle = "Membership Reporting";

$year = date("Y");
$_month = date("n");
$month = date("m");
$day = date("d");
$monthName = date("M");
$lastyear = $year-1;

// check for group categories
$groupCategoryRows = Group::GetAllGroupCategories(true);
$groupCategoryIds = array_column($groupCategoryRows, 'categoryid');

if (isset($_GET['filter']) && in_array($_GET['filter'],$groupCategoryIds)){
    $group_category_id = (int)$_GET['filter'];
} else {
    $group_category_id = (int)Group::GetDefaultGroupCategoryRow()['categoryid'];
}


$groups = $db->ro_get("SELECT `groupid`, `companyid`, `regionid`, `addedby`, `groupname_short`, `aboutgroup`, `coverphoto`, `overlaycolor` FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND zoneid={$_ZONE->id()} and `isactive`=1 AND categoryid={$group_category_id}");
$newmembers					= array();
$totalmembers				= array();
$monthlygrowth				= array();
$region						= array();
$regionalmembers			= array();

if (count($groups)){

	$region = array_merge($db->ro_get("SELECT `regionid`, `region` FROM `regions` WHERE `companyid`={$_COMPANY->id()} AND `isactive`=1"),[['regionid'=>'0','region'=>'Undefined']]);
	for($count=0; $count < count($region); $count++) {
        $db->ro_get("SET SESSION group_concat_max_len = 1024000");
		$branches = $db->ro_get("SELECT GROUP_CONCAT(branchid) AS branches FROM companybranches WHERE regionid={$region[$count]['regionid']} AND `companyid`={$_COMPANY->id()} AND `isactive`=1");
		if ($region[$count]['regionid'] === "0") {
			if (empty($branches[0]['branches'])) {
				$region[$count]['branches'] = "0,0";
			} else {
				$region[$count]['branches'] = $branches[0]['branches'].",0";
			}
		} else {
			$region[$count]['branches'] = $branches[0]['branches'];
		}
	}
	for($rg=0;$rg<count($groups);$rg++){
		//New Members per ERG
		$std = array("-01-01 00:00:00","-02-01 00:00:00","-03-01 00:00:00","-04-01 00:00:00","-05-01 00:00:00","-06-01 00:00:00","-07-01 00:00:00","-08-01 00:00:00","-09-01 00:00:00","-10-01 00:00:00","-11-01 00:00:00","-12-01 00:00:00");
		$edd = array("-01-31 23:59:59","-02-28 23:59:59","-03-31 23:59:59","-04-30 23:59:59","-05-31 23:59:59","-06-30 23:59:59","-07-31 23:59:59","-08-31 23:59:59","-09-30 23:59:59","-10-31 23:59:59","-11-30 23:59:59","-12-31 23:59:59");
		$newmemercount = array();
		$newmemb = $db->ro_get("SELECT count(`memberid`) as total FROM `groupmembers` LEFT JOIN users USING (userid) WHERE `groupid` = '".$groups[$rg]['groupid']."' and  (`groupjoindate` < '".$lastyear."-12-31 23:59:59') AND users.isactive=1 AND groupmembers.isactive=1");
		$newmemercount[] = $newmemb[0]['total'];
		for($mm = 0;$mm<12;$mm++){
			$startd = $year.$std[$mm];
			$endd = $year.$edd[$mm];
			$newmemb = $db->ro_get("SELECT count(`memberid`) as total FROM `groupmembers` LEFT JOIN users USING (userid) WHERE `groupid` = '".$groups[$rg]['groupid']."' and  (`groupjoindate` BETWEEN '".$startd."' AND '".$endd."') AND users.isactive=1 AND groupmembers.isactive=1");
			$newmemercount[] = $newmemb[0]['total'];
		}
		$newmembers[] = array('groupname_short'=>$groups[$rg]['groupname_short'],'newmember'=>$newmemercount,'color'=>$groups[$rg]['overlaycolor']);

		// Total members per ERG
        $totmemb = $db->ro_get("SELECT count(`memberid`) as total FROM `groupmembers` LEFT JOIN users USING (userid) WHERE `groupid` = '".$groups[$rg]['groupid']."' AND users.isactive=1 AND groupmembers.isactive=1");
		$totalmembers[] = array('groupname_short'=>$groups[$rg]['groupname_short'],'totalmembers'=>$totmemb[0]['total'],'color'=>$groups[$rg]['overlaycolor']);

		// Group members by Region
		// foreach region, calculate group members
		$rmc = array();
		foreach ($region as $r1) {
			if(empty($r1['branches'])) {
				$rmc[] = 0;
			} else {
				$rm = $db->ro_get("SELECT count(`memberid`) AS total FROM `groupmembers` g JOIN `users` u ON g.userid=u.userid WHERE g.groupid = '".$groups[$rg]['groupid']."' AND u.homeoffice IN (".$r1['branches'].") AND u.isactive=1 AND g.isactive=1");
				$rmc[] = $rm[0]['total'];
			}
		}
		// and save the regional member count vector by group
		$regionalmembers[] = array('groupname_short'=>$groups[$rg]['groupname_short'],'regionalmembers'=>$rmc);
	} // end of `groups`for loop

}

// Month wise membership per ERG
//echo '<pre>'; print_r($spentbyregion); exit;
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/membership_reporting.html');
