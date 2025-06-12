<?php
require_once __DIR__.'/head.php';
$pagetitle = "New Event Volunteer Type";
$formTitle = "New Event Volunteer Type";
$error = null;
$edit = null;
$volunteertypeid = 0;
if (isset($_GET['volunteertypeid'])){
	$formTitle = "Update Event Volunteer Type";
	$volunteertypeid = $_COMPANY->decodeId($_GET['volunteertypeid']);
	$edit = Event::GetEventVolunteerType($volunteertypeid);
}
if (isset($_POST['submit'])){
	$check = $db->checkRequired(array('Volunteer Type'=>$_POST['type']));	
    if($check){
        $msg = "Error: Not a valid input on $check.";
		Http::Redirect("new_event_volunteer_type?msg=$msg");
    }
	$type = raw2clean(trim($_POST['type']));

    if ($edit && $edit['type'] == $type) {
        $retVal = 0; // Skip processing
    } else {
        $type = htmlspecialchars_decode($type);
        $retVal = Event::AddOrUpdateEventVolunteerType($volunteertypeid, $type);
        if (($volunteertypeid && $retVal))
            $_SESSION['updated'] = time();
        elseif (!$volunteertypeid && $retVal)
            $_SESSION['added'] = time();
        else
            $error = "Event volunteer type is already in the list!";
    }
    if (!$error){
	    Http::Redirect("event_volunteer_types");
    }
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/new_event_volunteer_type.html');

