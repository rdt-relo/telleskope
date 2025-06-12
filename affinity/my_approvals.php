<?php
require_once __DIR__.'/head.php';

if (!defined('INDEX_PAGE') && empty ($_ZONE)) { // User is logged in, but for some reason the $_ZONE is not set, logout and retry
  Http::Redirect('logout');
}

if (!$_USER->canApproveSomething()) {
    Http::Forbidden('This feature has been disabled for your account');
    header(HTTP_FORBIDDEN);
    exit();
}

// Since we do not have my approvals on/off setting in the zone, we will use calendar_page_banner_title check,
// if it is empty then we will not show My Events label, else we will show (ouch ... lazy implementation).
$bannerTitle = empty($_ZONE->val('calendar_page_banner_title')) ? '' : gettext('My Approvals');
$pageTitle = gettext('My Approvals');

// Tabs activation based on approvals
$eventApprovals = $_COMPANY->getAppCustomization()['event']['approvals']['enabled'] ? true : false;
$newsletterApprovals = $_COMPANY->getAppCustomization()['newsletters']['approvals']['enabled'] ? true : false;
$postApprovals = $_COMPANY->getAppCustomization()['post']['approvals']['enabled'] ? true : false;
$surveyApprovals = $_COMPANY->getAppCustomization()['surveys']['approvals']['enabled'] ? true : false;
// set topic types
$eventTopicType = $eventApprovals ? TELESKOPE::TOPIC_TYPES['EVENT'] : '';
$newsletterTopicType = $newsletterApprovals ? TELESKOPE::TOPIC_TYPES['NEWSLETTER'] : '';
$postTopicType = $postApprovals ? TELESKOPE::TOPIC_TYPES['POST'] : '';
$surveyTopicType = $surveyApprovals ? TELESKOPE::TOPIC_TYPES['SURVEY'] : '';


include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/my_approvals_html.php');
include(__DIR__ . '/views/footer_html.php');
?>