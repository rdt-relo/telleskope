<?php
require_once __DIR__.'/head.php';

$pagetitle = "Manage Zone Admin";
$section = "zone";
$userid=$_SESSION['adminid'];

if(isset($_GET['msg'])){
	$msg = getmsg($_GET['msg']);
}else{
	$msg = "";
}

$rows = User::GetAllZoneAdminsForZone($_ZONE->id());

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/manageadmin.html');
include(__DIR__ . '/views/footer.html');
?>
