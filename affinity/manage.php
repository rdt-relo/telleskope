<?php
require_once __DIR__.'/head.php';

global $_COMPANY, $_ZONE, $_USER;

if (!defined('INDEX_PAGE') && empty ($_ZONE)) { // User is logged in, but for some reason the $_ZONE is not set, logout and retry
	Http::Redirect('logout');
}

$groupid = 0;
$chapterid = 0;//Use -1 to show the chapterid was not set
$group = NULL;
$page_title = gettext("Manage"); # Used in footer_html.php

//Data Validation - done inline
//Authorization - not needed

if (isset($_GET['id'])) {
	$groupid = $_COMPANY->decodeId($_GET['id']);
} elseif (isset($_GET['group'])) {
	$permatag = $_GET['group'];
	$groupid = Group::GetGroupIdByPermatag($permatag);
	if(!$groupid){
		Logger::Log("Trying to load a permatag unknown to this company", Logger::SEVERITY['SECURITY_ERROR']);
		Http::Redirect('home');
	}
} else {
	// No match
	Http::Redirect('home');
}

// Authorization Check
if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
	Logger::Log(HTTP_FORBIDDEN.' to manage section', Logger::SEVERITY['WARNING_ERROR']);
	Http::Redirect('home');
}

$group = Group::GetGroup($groupid);
if (NULL === $group) {
	Logger::Log("Trying to load a group id unknown to this company");
	Http::Redirect('home');
}

Http::RedirectIfOldUrl($group->val('zoneid'));

Http::RedirectIfHashAttributeUrl();

if (isset($_GET['chapterid'])){
	$chapterid = $_COMPANY->decodeId($_GET['chapterid']);
}

$chapters	= Group::GetChapterList($groupid);

$htmlTitle = sprintf(gettext("Manage %s"),$group->val('groupname').' '. $_COMPANY->getAppCustomization()['group']['name']);

include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/manage_html.php');
include(__DIR__ . '/views/footer_html.php');
?>
