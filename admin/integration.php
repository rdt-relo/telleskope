<?php
require_once __DIR__.'/head.php';

// Authorization Check
if (!$_USER->canManageAffinitiesContent() || !$_COMPANY->getAppCustomization()['integrations']['group']['enabled']) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}
$cid = 0;
$chid = 0;
//Data Validation
if (!isset($_GET['gid']) || ($groupid = $_COMPANY->decodeId($_GET['gid']))<1  || (($group = Group::GetGroup($groupid)) == null)||
(isset($_GET['cid']) && ($cid = $_COMPANY->decodeId($_GET['cid']))<0)|| 
(isset($_GET['chid']) && ($chid = $_COMPANY->decodeId($_GET['chid']))<0)) {
	header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
	exit();
}
$chapterName = "";
$channelName = "";
if($cid){
	$chapterName = $group->getChapter($cid,true)['chaptername']." > ";
	
}else if($chid){
	$channelName = $group->getChannel($chid,true)['channelname']." > ";
}

$pagetitle = $group->val('groupname')." > ".$chapterName." ".$channelName."  Integration ";
$GroupIntegration = GroupIntegration::GetGroupIntegrationsByExactScope($groupid,$cid,$chid,0);

$encodedgroupid = $_COMPANY->encodeId($groupid);
$encodedchapterid = $_COMPANY->encodeId($cid);
$encodedchannelid = $_COMPANY->encodeId($chid);


include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/integration.html');
?>