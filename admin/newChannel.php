<?php
require_once __DIR__.'/head.php';

// There are four use cases here
// 1 - Generate form to add channel -  If GET is true and channelid is not provided - need gid 
// 2 - Generate form for update channel - If GET is true and channelid is *provided*
// 3 - Process Submit for Add channel - If $_POST['submit'] is true and channelid is not provided - need gid and cid
// 4 - Process Submit for Update Channel - If $_POST['submit'] is true and channelid is *provided*

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}
$groupid    = 0;
$channelid  = 0;
//Data Validation

if (!isset($_GET['gid']) ||
    ($groupid = $_COMPANY->decodeId($_GET['gid']))<1 ||
    (isset($_GET['cid']) && ($channelid = $_COMPANY->decodeId($_GET['cid']))<1) ||
    ($group = Group::GetGroup($groupid)) == null) {
    header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
    exit();
}

$encGroupId = $_COMPANY->encodeId($groupid);
if (isset($_POST['submit'])) { // Add or Update

    $channelname = 	Sanitizer::SanitizeGroupName($_POST['channelname']);
    $colour	= Sanitizer::SanitizeColor($_POST['colour']);
    
    if ($channelid){
        $group->updateChannel($channelid, $channelname, $colour);
        $_SESSION['updated'] = time();
    } else {
        $about = "About {$channelname} ...";
        $add =  $group->addChannel($channelname,$about, $colour);
        $_SESSION['added'] = time();
        $_SESSION['add-msg'] ="New ". $_COMPANY->getAppCustomization()['channel']["name-short"]." was created successfully";
        
    }
    Http::Redirect("manage_channels?gid={$encGroupId}");
}
// Else generate data needed to show the form.

$form = "Add";

if ($channelid) {
    // Get the branches already assigned to the channel
    // Also get the regionid
    $form = "Edit";
    $edit = $group->getChannel($channelid, true);
    if (empty($edit)) {
        header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
        exit();
    }
}

$pagetitle = $group->val('groupname')." > ".$form. " ". $_COMPANY->getAppCustomization()['channel']["name-short"];


include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/newChannel.html');

?>
