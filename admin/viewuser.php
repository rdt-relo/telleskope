<?php
require_once __DIR__.'/head.php';
$pagetitle = "View User";

// Authorization Check
if (!$_USER->canManageAffinitiesUsers()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}
$searched_userid = 0;
if(isset($_GET['userid'])){
	$searched_userid = $_COMPANY->decodeId($_GET['userid']);
}
$user = null;
$user_memberships  = array();
$section = $_GET['section'] ?? 'zone';
if ($searched_userid > 0){
	$user = User::GetUser($searched_userid);
    $zoneid = ($section == 'global') ? 0 : $_ZONE->id();
	$user_memberships = Group::GetUserMembershipGroupsChaptersChannelsByZone($searched_userid, $zoneid);

}
include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/viewuser.html');
include(__DIR__ . '/views/footer.html');