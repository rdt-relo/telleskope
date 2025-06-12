<?php
require_once __DIR__.'/head.php';

global $_COMPANY, $_ZONE, $_USER;

// SET LOGIN Survey, if it was not checked for this zone in this session.
if (!Session::GetInstance()->login_survey_checked) {
    $_USER->pushSurveyIntoSession(Survey2::SURVEY_TYPE['ZONE_MEMBER'], Survey2::SURVEY_TRIGGER['ON_LOGIN'],0,0,0);
    Session::GetInstance()->login_survey_checked = 1;
}

Http::RedirectIfOldUrl();

Http::RedirectIfHashAttributeUrl();

$htmlTitle = "Welcome To - ". $_COMPANY->val('companyname');
$htmlTitle = sprintf(gettext("Welcome to %s home"), $_COMPANY->val('companyname') .' '. $_COMPANY->getAppCustomization()['group']['name-plural'] . ' - ' . $_ZONE->val('zonename') . ' Zone');
$page = 1;

$banner	  = $_ZONE->val('banner_background');
$web_banner_subtitle	= $_ZONE->val('banner_subtitle');
$web_banner_title		= $_ZONE->val('banner_title');
$banner_title	 = $web_banner_title;// ? $web_banner_title : "Welcome to Affinities!"; 
$banner_subtitle = $web_banner_subtitle;// ? $web_banner_subtitle : "A digital platform for Employee Resource Groups"; 
// If the user is in homezone then use the landing page preference else show the discover mode.
$landing_page = intval($_COMPANY->getAppCustomization()['group']['homepage']['show_my_groups_option'] ? $_USER->getUserPreference(UserPreferenceType::ZONE_ShowMyGroups) : 0);
$myGroupsOnly = $landing_page;

[$groupCategoryRows, $groupCategoryIds, $group_category_id] = ViewHelper::InitGroupCategoryVariables();

// Reset session values for Global Chapter or Channel
unset($_SESSION['showGlobalChapterOnly']);
unset($_SESSION['showGlobalChannelOnly']);

$groupIdAry = Group::GetAvailableGroupsForGlobalFeeds($group_category_id, $myGroupsOnly);
$groups = Group::GetGroups($groupIdAry);
$groupIdAry[] = 0; // Add groupid zero to the list, only after the groups are fetched else it shows blank tile

$feeds = array();
$contentsCount = 0;
$show_more = false;
if ($_COMPANY->getAppCustomization()['group']['homepage']['show_global_feed']){
    $include_content_types = Content::GetAvailableContentTypes();
    $show_more = true;
}
$allTags = Group::GetGroupTags($group_category_id);
include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/home_html.php');
include(__DIR__ . '/views/footer_html.php');

