<?php
require_once __DIR__.'/head.php'; // Guarantee the user is logged in
global $_COMPANY, $_ZONE, $_USER;

$groupid = 0;
$eventid = 0;
$month = date("Y-m");

if (isset($_GET['groupid'])) {
    $groupid = $_COMPANY->decodeId($_GET['groupid']);
}

if (isset($_GET['eventid'])) {
    $eventid = $_COMPANY->decodeId($_GET['eventid']);
} 
if (isset($_GET['month'])){
    $month  = $_GET['month'];
}
$analyticsMeta = AnalyticAffinitiesEvent::GetDefaultAnalyticMeta();
$analyticsMeta['Filters']['groupid']=$groupid;
$analyticsMeta['Filters']['eventid']=$eventid;
$analyticsMeta['Filters']['month']=$month;

$analytics = new AnalyticAffinitiesEvent ($_COMPANY->id(),$analyticsMeta);
$data = $analytics->generateAnalytics();
$questionJson = json_encode($data['questions']);
$answerJson = json_encode($data['answers']);
$pageTitle = gettext("Event Analytics");
include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/analytics_common.php');
include(__DIR__ . '/views/footer_html.php');
