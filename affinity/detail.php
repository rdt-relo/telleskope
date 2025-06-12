<?php
include_once __DIR__.'/add_delay_for_deep_linking.php';
require_once __DIR__.'/head.php';

global $_COMPANY, $_ZONE, $_USER;

$groupid = 0;
$chapterid = 0;//Use -1 to show the chapterid was not set
$channelid = 0;
$group = NULL;
$chapters = NULL;
$channels = NULL;

//Data Validation - done inline
//Authorization - not needed

if (isset($_GET['id'])) {
	$groupid = $_COMPANY->decodeId($_GET['id']);
} elseif (isset($_GET['group'])) {
	$groupid = Group::GetGroupIdByPermatag($_GET['group']);
	if(!$groupid){
		Logger::Log("Trying to load a permatag unknown to this company", Logger::SEVERITY['SECURITY_ERROR']);
		Http::Redirect("home");
	}
} else {
	// No match
	Http::Redirect("home");
}

$group = Group::GetGroup($groupid);
if (NULL === $group) {
	Logger::Log("Trying to load a group id unknown to this company");
	Http::Redirect("home");
} elseif (!$group->isActive()) {
	Logger::Log("Unable to load this group page.This group has been deactivated and is no longer available.", Logger::SEVERITY['WARNING_ERROR']);
	Http::Redirect("home");
}
$htmlTitle = sprintf(gettext("View %s"),$group->val('groupname').' '. $_COMPANY->getAppCustomization()['group']['name']);
Http::RedirectIfOldUrl($group->val('zoneid'));

Http::RedirectIfHashAttributeUrl();

$selected_chapter = null;
if (isset($_GET['chapterid'])){
	$chapterid = $_COMPANY->decodeId($_GET['chapterid']);
	$selected_chapter = $group->getChapter($chapterid);
}

$selected_channel = null;
if (isset($_GET['channelid'])){
	$channelid = $_COMPANY->decodeId($_GET['channelid']);
	$selected_channel = $group->getChannel($channelid);
}

if (!empty($_GET['showGlobalChapterOnly'])) {
	$_SESSION['showGlobalChapterOnly'] = 1;
} elseif ($chapterid || !empty($_GET['showAllChapters'])) {
	unset($_SESSION['showGlobalChapterOnly']);
//} else {
//	$_SESSION['showGlobalChapterOnly'] = 1;
}

if (!empty($_GET['showGlobalChannelOnly'])) {
	$_SESSION['showGlobalChannelOnly'] = 1;
} elseif ($channelid || !empty($_GET['showAllChannels'])) {
	unset($_SESSION['showGlobalChannelOnly']);
//} else {
//	$_SESSION['showGlobalChannelOnly'] = 1;
}

$enc_chapterid   = $_COMPANY->encodeId($chapterid);
$enc_groupid    = $_COMPANY->encodeId($groupid);
$enc_channelid    = $_COMPANY->encodeId($channelid);

$chapters = Group::GetChapterListByRegionalHierachies($groupid);
$channels= Group::GetChannelList($groupid);

$canViewContent = $_USER->canViewContent($group->val('groupid'));


include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/detail_html.php');
include(__DIR__ . '/views/common/menu_active_state.template.php');
include(__DIR__ . '/views/footer_html.php');
?>
