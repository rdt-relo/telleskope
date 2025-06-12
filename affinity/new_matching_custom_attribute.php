<?php
require_once __DIR__.'/head.php';

global $_COMPANY, $_ZONE, $_USER;

if (!defined('INDEX_PAGE') && empty ($_ZONE)) { // User is logged in, but for some reason the $_ZONE is not set, logout and retry
	header('location: logout');
	exit();
}

if (
	($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
	($group = Group::GetGroup($groupid)) === null
) {
	header(HTTP_BAD_REQUEST);
	exit();
}
$jsonObj = json_encode($group->getTeamMatchingAlgorithmAttributes());
$teleskopeQuestionCounter = Survey2::GetSurveyQuestionCounter($jsonObj);

include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/new_matching_custom_attributes_html.php');
include(__DIR__ . '/views/footer_html.php');
?>
