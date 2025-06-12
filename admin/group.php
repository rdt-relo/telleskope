<?php
require_once __DIR__.'/head.php';
$pagetitle = "Manage ".$_COMPANY->getAppCustomization()['group']["name-plural"];

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
    header("HTTP/1.1 403 Forbidden (Access Denied)");
    exit();
}

// check for group categories 
$groupCategoryRows = Group::GetAllGroupCategoriesByZone($_ZONE, true, true);
$groupCategoryIds = array_column($groupCategoryRows, 'categoryid');

if (isset($_GET['filter']) && in_array($_GET['filter'],$groupCategoryIds)){
    $group_category_id = (int)$_GET['filter'];
} else {
    $group_category_id = (int)Group::GetDefaultGroupCategoryRow()['categoryid'];
    Http::Redirect("group?filter={$group_category_id}");
}

$rows	= $db->get("SELECT *,IFNULL((select GROUP_CONCAT(region SEPARATOR '%%') as region from regions where FIND_IN_SET (regionid,a.regionid)),'No Region') as region FROM `groups` a WHERE `companyid`={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND categoryid={$group_category_id}");
$priority = $rows[0]['priority'] ?? '';
$order = explode(',', $priority);

$rows = Arr::SortByOrder($rows, $order, 'groupid');

// For templates
$importedTemplates = TskpTemplate::GetAllTemplates(true, $_ZONE->val('app_type'));

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/group.html');

?>
