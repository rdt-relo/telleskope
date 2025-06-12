<?php
require_once __DIR__.'/head.php';

$pagetitle = "Manage Event Volunteer Types";

$timezone = @$_SESSION['timezone'];	

// Get all events types of companyid
$data = Event::GetEventVolunteerTypesForCurrentZone(true);

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/event_volunteer_types.html');
?>
