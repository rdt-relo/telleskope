<?php
require_once __DIR__.'/head.php';

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}

//Data Validation
if (!isset($_GET['gid']) ||
	($groupid = $_COMPANY->decodeId($_GET['gid']))<1 ||
	($group = Group::GetGroup($groupid)) == null ||
	(isset($_GET['cid']) && ($chapterid = $_COMPANY->decodeId($_GET['cid']))<1) ) {
	header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
	exit();
}

$pagetitle = $group->val('groupname')." > ".$group->getChapter($chapterid,true)['chaptername']." > Manage Leaders";
$leads =  $group->getChapterLeads($chapterid);
$gtypes = $db->get("SELECT * FROM `grouplead_type` WHERE `companyid`='{$_COMPANY->id()}' AND `isactive`=1");

$types = array();
foreach($gtypes as $g) {
	$types[$g['typeid']]['type'] = $g['type'];
	$types[$g['typeid']]['allow_publish_content']= $g['allow_publish_content'];
	$types[$g['typeid']]['allow_create_content'] = $g['allow_create_content'];
	$types[$g['typeid']]['allow_manage'] = $g['allow_manage'];
	$types[$g['typeid']]['allow_manage_budget'] = $g['allow_manage_budget'];
	$types[$g['typeid']]['sys_leadtype'] = $g['sys_leadtype'];
}


include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manageChapterLeads.html');


?>
