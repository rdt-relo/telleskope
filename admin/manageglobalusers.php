<?php
require_once __DIR__.'/head.php';
$pagetitle = "Manage Global Users";

// Authorization Check
if (!$_USER->canManageAffinitiesUsers()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}

if(isset($_GET['msg'])){
	$msg = getmsg($_GET['msg']);
}else{
	$msg = "";
}
// Set actionurl for use in manageusers.html
$actionUrl = 'ajax.php?getUsersList=global';

// Do not show in global users as we show connect users in zone page if connect login is configured in the zone.
$show_connect_users_button = false;

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/manageusers.html');
include(__DIR__ . '/views/footer.html');
?>
