<?php
require_once __DIR__.'/head.php';

$pagetitle = "Manage Event Types";

$timezone = @$_SESSION['timezone'];	

// Get all events types of companyid
$data = Event::GetEventTypesByZones([$_ZONE->id()],false);

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/event_type.html');
?>
