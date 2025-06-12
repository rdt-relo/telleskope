<?php
require_once __DIR__.'/head.php';

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}
//Data Validation
if (!isset($_GET['gid']) || ($groupid = $_COMPANY->decodeId($_GET['gid']))<1) {
	header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
	exit();
}

$pagetitle = Group::GetGroupName($groupid)." > Manage Leaders";

$order = "";
$priority = $db->get("SELECT `priority` FROM `groupleads` WHERE `groupid`='{$groupid}' ORDER BY `leadid` ASC LIMIT 1");
if(count($priority)){
	$priority = $priority[0]['priority'];
	if($priority){
		$order = " ORDER BY FIELD(leadid,{$priority})";
	}else{
		$order = " ORDER BY leadid ASC";
	}
}

$rows	= $db->get("SELECT *,IFNULL((select GROUP_CONCAT('',region) as region from regions where FIND_IN_SET (regionid,a.regionids)),'') as region FROM `groupleads` a LEFT JOIN users ON a.userid = users.userid WHERE`groupid`='".$groupid."' AND a.isactive='1'".$order);

$gtypes = $db->get("SELECT * FROM `grouplead_type` WHERE `companyid`='{$_COMPANY->id()}' AND (`zoneid`='{$_ZONE->id()}' AND `isactive`=1) ");

$types = array();
foreach($gtypes as $g) {
	$types[$g['typeid']]['type'] = $g['type'];
	$types[$g['typeid']]['allow_publish_content']= $g['allow_publish_content'];
	$types[$g['typeid']]['allow_create_content'] = $g['allow_create_content'];
	$types[$g['typeid']]['allow_manage'] = $g['allow_manage'];
	$types[$g['typeid']]['allow_manage_budget'] = $g['allow_manage_budget'];
	$types[$g['typeid']]['sys_leadtype'] = $g['sys_leadtype'];
}

//print_r($rows);exit;
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manageleads.html');


?>
