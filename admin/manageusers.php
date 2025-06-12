<?php
require_once __DIR__.'/head.php';
$pagetitle = "Manage Zone Users";

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
$actionUrl = 'ajax.php?getUsersList=zone';

// Show connect users button only if connect is enabled and if the connect login is configured for the zone app type
$show_connect_users_button = $_ZONE->isConnectFeatureEnabled();

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/manageusers.html');
include(__DIR__ . '/views/footer.html');
?>
