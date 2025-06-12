<?php
require_once __DIR__.'/head.php';

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}

$channelid = 0;
$leadid = 0;
$edit=[];
//Data Validation
if (!isset($_GET['gid']) || !isset($_GET['cid']) ||
    ($groupid = $_COMPANY->decodeId($_GET['gid']))<1 ||
	($channelid = $_COMPANY->decodeId($_GET['cid']))<1 ||
    ($group = Group::GetGroup($groupid)) == null || (isset($_GET['lid']) && ($leadid = $_COMPANY->decodeId($_GET['lid']))<1) ){
	header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
	exit();
}
$encGroupId = $_COMPANY->encodeId($groupid);
$encChannelId = $_COMPANY->encodeId($channelid);

$grouplead_type = $db->get("SELECT * FROM `grouplead_type` WHERE `companyid`='{$companyid}' AND (`zoneid`='{$_ZONE->id()}' AND `isactive`=1 AND sys_leadtype=5)");

if ($leadid){
    $edit = $group->getChannelLeadDetail($channelid,$leadid);
}

if (isset($_POST['submit'])){

    if (!$leadid){
        if ((!isset($_POST['userid'])) || ($leaduserid = $_COMPANY->decodeId($_POST['userid']))<1) {
            header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
            exit();
        }
    }
    $typeid = $_COMPANY->decodeId($_POST['typeid']);
    $roletitle = $_POST['roletitle'];

    if ($leadid){
        $group->updateChannelLead($channelid,$leadid,$typeid,$roletitle);
    } else {
        $group->addChannelLead($channelid,$leaduserid,$typeid,$roletitle);
        $group->addOrUpdateGroupMemberByAssignment($leaduserid,0,$channelid);
        # Inform lead by email
        $group->sendGroupLeadAssignmentEmail($channelid, 3, $typeid, $leaduserid);
    }
	Http::Redirect("manageChannelLeads?gid={$encGroupId}&cid={$encChannelId}");
} else {
    $channel = $group->getChannel($channelid, true);
    $pagetitle = $group->val('groupname')." > ".$channel['channelname']." > ".($leadid ? 'Edit ' : 'Add '). $_COMPANY->getAppCustomization()['channel']["name-short"].' Leader';
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/newChannelLead.html');

?>