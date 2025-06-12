<?php
require_once __DIR__.'/head.php';

if(!empty($_GET['page'])){
	$page = $_GET['page'];
}else{
	$page = 1;
}
$pagetitle = "New Event Type";
//Featch Company branches

$companyid 	= 	$_SESSION['companyid'];

if (isset($_POST['submit'])){
	$check = $db->checkRequired(array('Event Type'=>$_POST['type']));	
    if($check){
        $msg = "Error: Not a valid input on $check.";
		Http::Redirect("new_eventtype?msg=$msg");
    }
	$type 	=	(trim($_POST['type']));
	$zoneid =(isset($_POST['scope']) && $_POST['scope'] === 'company' and $_USER->isCompanyAdmin()) ? 0 : $_ZONE->id();
	//sys_eventtype is 0 for all user created event_types;
	$insert = Event::AddUpdateEventType(0, $type, $zoneid);

	$_SESSION['updated']= time();
	Http::Redirect("event_type");
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/new_eventtype.html');

