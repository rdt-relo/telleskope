<?php
require_once __DIR__.'/head.php';

// Authorization Check
if (!$_USER->canManageAffinitiesContent() || !($_COMPANY->getAppCustomization()['channel']['enabled'])) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}

//Data Validation
if (!isset($_GET['gid']) || ($groupid = $_COMPANY->decodeId($_GET['gid']))<1) {
	header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
	exit();
}

$pagetitle = Group::GetGroupName($groupid)." > Manage ". $_COMPANY->getAppCustomization()['channel']["name-short-plural"];

// Get Channels
$channels 	= Group::GetChannelListDetail($groupid, true);

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manage_channels.html');


?>
