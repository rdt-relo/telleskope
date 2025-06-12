<?php
require_once __DIR__.'/head.php';

$pagetitle = "Manage Events";
$timezone = @$_SESSION['timezone'];
$isEnabled=false;

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
    header("HTTP/1.1 403 Forbidden (Access Denied)");
    exit();
}
$startDate = date("Y-m-d",strtotime('-1 month'));
if ($_COMPANY->getAppCustomization()['event']['enabled']) {
    // Get all events


    // Get group
    $group = $db->ro_get("SELECT `groupid`, `groupname` FROM `groups` WHERE `companyid`='{$_COMPANY->id()}' AND (`zoneid`='{$_ZONE->id()}' AND `isactive`='1')");

    // Get Region
    $regionids =  $_ZONE->val("regionids") ?? 0;
    $zoneRegions = $_COMPANY->getRegionsByZones([$_ZONE->id()]);
    $isEnabled=true;
}
$events = array();
$year_filter = '';
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/event.html');
?>
