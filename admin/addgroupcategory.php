<?php
require_once __DIR__.'/head.php';
$pagetitle = "Add/Edit". $_COMPANY->getAppCustomization()['group']['name']." Category";

// Authorization Check
if (!$_USER->canManageAffinitiesUsers()) {
    header("HTTP/1.1 403 Forbidden (Access Denied)");
    exit();
}

$companyid 	= 	$_SESSION['companyid'];
$groupCategoryId = 0;
if (isset($_POST['submit'])){
	$categoryLabel = strip_tags(trim($_POST['category_label']));
	$categoryShortLabel = strip_tags(trim($_POST['category_name']));
    $categoryDesc = preg_replace('#<p></p>#','<p>&nbsp;</p>', $_POST['category_description']);
	$categoryDesc = trim($categoryDesc);


    if (isset($_POST['groupCategoryId']) && ($groupCategoryId = $_COMPANY->decodeId($_POST['groupCategoryId'])) > 0) {
        $update = Group::UpdateGroupCategory($groupCategoryId, $categoryLabel, $categoryShortLabel, $categoryDesc);
        $_SESSION['updated']= time();        
    }else{
        $insert = Group::CreateGroupCategory($categoryLabel, $categoryShortLabel, $categoryDesc);
        $_SESSION['added']= time();
    }
	Http::Redirect("group_category");
}

if (isset($_GET['edit'])) {
    $groupCategoryId = $_COMPANY->decodeId($_GET['edit']);
    $groupCategoryData = Group::GetGroupCategoryById($groupCategoryId);
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/addgroupcategory.html');

