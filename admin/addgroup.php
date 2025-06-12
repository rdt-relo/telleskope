<?php
require_once __DIR__.'/head.php';
$pagetitle = "Add/Edit". $_COMPANY->getAppCustomization()['group']['name'];

// Authorization Check
if (!$_USER->canManageAffinitiesUsers()) {
    header("HTTP/1.1 403 Forbidden (Access Denied)");
    exit();
}

$groupid = 0;
$showOverlayLogo = 1;
$showAppOverlayLogo = 1;
$selectedTags = array();
if(isset($_GET['edit'])){
    $groupid=$_COMPANY->decodeId($_GET['edit']);
    $res_group=$db->get("SELECT * FROM `groups` WHERE `groupid`='{$groupid}' AND `companyid`='{$_COMPANY->id()}'");
    $showOverlayLogo = (int)$res_group[0]['show_overlay_logo'];
    $showAppOverlayLogo = (int)$res_group[0]['show_app_overlay_logo'];
    $selectedTags = array_map('intval',explode(',',$res_group[0]['tagids']));
}
$zoneRegions = $_COMPANY->getRegionsByZones([$_ZONE->id()]);

$allTags = Group::GetGroupTags(0);
// check for group categories
$groupCategoryRows = Group::GetAllGroupCategoriesByZone($_ZONE, true, true);
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/addgroup.html');
