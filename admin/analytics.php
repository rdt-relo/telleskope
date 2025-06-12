<?php
require_once __DIR__.'/head.php';
$pagetitle = "Analytics";
//if (!$_USER->isAdmin()) {
//	header(HTTP_FORBIDDEN);
//	echo "403 Forbidden (Access Denied)";
//	exit();
//}

if (empty($_SESSION['analytics_data'])){
	$_SESSION['noAnalyticsData'] = time();
	Http::Redirect("reports");
}

$analyticsData = $_SESSION['analytics_data'];
$_SESSION['analyticsPageRefreshed'] = true;
$_SESSION['analytics_data'] = array();
$analyticsTitle = $analyticsData['title'];
$questionJson = json_encode($analyticsData['questions']);
$totalResponses = count($analyticsData['answers']);
$answerJson = json_encode($analyticsData['answers']);
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/templates/analytics.template.php');
?>
