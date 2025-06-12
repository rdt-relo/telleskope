<?php
require_once __DIR__.'/head.php';

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}

$chapterid = 0;
$leadid = 0;
$edit=[];
//Data Validation
if (!isset($_GET['gid']) || !isset($_GET['cid']) ||
    ($groupid = $_COMPANY->decodeId($_GET['gid']))<1 ||
	($chapterid = $_COMPANY->decodeId($_GET['cid']))<1 ||
    ($group = Group::GetGroup($groupid)) == null || (isset($_GET['lid']) && ($leadid = $_COMPANY->decodeId($_GET['lid']))<1) ){
	header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
	exit();
}
$encGroupId = $_COMPANY->encodeId($groupid);
$encChapterId = $_COMPANY->encodeId($chapterid);
$grouplead_type = $db->get("SELECT * FROM `grouplead_type` WHERE `companyid`='{$companyid}' AND (`zoneid`='{$_ZONE->id()}' AND `isactive`=1 AND sys_leadtype=4)");

if ($leadid){
    $edit = $group->getChapterLeadDetail($chapterid,$leadid);
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
        $group->updateChapterLead($chapterid,$leadid,$typeid,$roletitle);
    } else {
        $group->addChapterLead($chapterid,$leaduserid,$typeid,$roletitle,'');
        $group->addOrUpdateGroupMemberByAssignment($leaduserid,$chapterid,0);
        
        # Inform lead by email
        $group->sendGroupLeadAssignmentEmail($chapterid, 2, $typeid, $leaduserid);
    }
	Http::Redirect("manageChapterLeads?gid={$encGroupId}&cid={$encChapterId}");
} else {
    $chapter = $group->getChapter($chapterid);
    $pagetitle = $group->val('groupname')." > ".$group->getChapter($chapterid,true)['chaptername']." > ".($leadid ? 'Edit Leader' : 'Add Leader');
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/newChapterLead.html');

?>