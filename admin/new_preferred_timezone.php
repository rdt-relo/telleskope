<?php
require_once __DIR__.'/head.php';

if(!empty($_GET['page'])){
	$page = $_GET['page'];
}else{
	$page = 1;
}
$pagetitle = "New Preferred Timezone";
$companyid 	= 	$_SESSION['companyid'];

if (isset($_POST['submit'])){
	$selected_timezone 	=	trim($_POST['selected_timezone']);
	$display_name 	=	trim($_POST['display_name']);
	if(empty($selected_timezone)){
		$error_message = "Please select a timezone!";
	} else {
		if (isset($_POST['timezoneId'])) {
			$id = $_COMPANY->decodeId($_POST['timezoneId']);
			$update = Event::AddUpdatePreferredTimezoneType($id, $display_name, $selected_timezone);	
		}else{
			$insert = Event::AddUpdatePreferredTimezoneType(0, $display_name, $selected_timezone);
		}
		$_SESSION['updated']= time();
		Http::Redirect("event_timezones");
	}
}
if (isset($_GET['edit'])) {
    $timezoneId = $_COMPANY->decodeId($_GET['edit']);
    $timezoneData = Event::GetPreferredTimezoneData($timezoneId);
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/new_preferred_timezone.html');

