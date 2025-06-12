<?php
require_once __DIR__.'/head.php'; // Guarantee the user is logged in
global $_COMPANY, $_ZONE, $_USER;

$groupid = 0;
$sectionid = 0;
$month = date("Y-m");
$section = "1";

if (isset($_GET['groupid'])) {
    $groupid = $_COMPANY->decodeId($_GET['groupid']);
}

if (isset($_GET['sectionid'])) {
    $sectionid = $_COMPANY->decodeId($_GET['sectionid']);
} 
if (isset($_GET['month'])){
    $month  = $_GET['month'];
}
if (isset($_GET['section'])){
    $section  = $_GET['section'];
}
$analyticsMeta = array (
    'Fields' => array(),
    'Options' => array(
    ),
    'Filters' => array(
        'groupid' => $groupid,
        'sectionid' => $sectionid,
        'section'=>$section, // can be EVENT or NEWSLETTER
        'month' => $month  // Format : Y-m 2021-03
    )
);
$analytics = new EmailLogAnalytic ($_COMPANY->id(),$analyticsMeta);
$data = $analytics->generateAnalytics();
$questionJson = json_encode($data['questions']);
$answerJson = json_encode($data['answers']);

$pageTitle = gettext("Announcement Email Tracking");
if ($section == '2'){
    $pageTitle = gettext('Event Email Tracking');
} elseif ($section == '3'){
    $pageTitle = gettext("Newsletter Email Tracking");
}

include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/analytics_common.php');
include(__DIR__ . '/views/footer_html.php');
