<?php
require_once __DIR__.'/head.php';
$pagetitle = "Manage ".$_COMPANY->getAppCustomization()['group']["name-plural"]." categories";

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
    header("HTTP/1.1 403 Forbidden (Access Denied)");
    exit();
}
// commented code is not needed
// $groupCategoryRows = Group::GetAllGroupCategories(true);
// $groupCategoryIds = array_column($groupCategoryRows, 'categoryid');
// $groupCategories = $groupCategoryIds ?? array();


$rows = Group::GetAllGroupCategories(true);
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/group_category.html');
?>
