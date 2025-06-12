<?php
include_once __DIR__.'/../affinity/head.php'; // Guarantee the user is logged in
global $_COMPANY, $_ZONE, $_USER;

$htmlTitle = gettext("Edit Survey");

if (!defined('INDEX_PAGE') && empty ($_ZONE)) { // User is logged in, but for some reason the $_ZONE is not set, logout and retry
    Http::Redirect('logout');
}

if (!isset($_GET['surveyid']) ||
    ($surveyid = $_COMPANY->decodeId($_GET['surveyid'])) < 1 ||
    ($survey = Survey2::GetSurvey($surveyid)) === null
) {
    header(HTTP_BAD_REQUEST);
    exit('Bad Request');
}

if (!$_USER->canManageContentInScopeCSV($survey->getGroupId(), $survey->getChapterId(), $survey->getChannelId())) {
    echo "Access Denied (Insufficient permissions)";
    header(HTTP_FORBIDDEN);
    exit('Forbidden');
}

$pagetitle = gettext("Edit Survey");
$groupid = $_COMPANY->encodeId(0); // Group id is needed to return to the group on survey completion
$version = $_COMPANY->encodeId(0);
$json = $survey->val('survey_json') ?? [];
$groupid = $_COMPANY->encodeId($survey->getGroupId());
$surveyid = $_COMPANY->encodeId($surveyid);
$version = $_COMPANY->encodeId($survey->val('version'));
if ($survey->val('survey_json')){
    $pagetitle = sprintf(gettext("Edit %s"),$survey->val('surveyname'));
} else {
    $pagetitle = sprintf(gettext("Create %s"),$survey->val('surveyname'));
}

$teleskopeQuestionCounter = Survey2::GetSurveyQuestionCounter($survey->val('survey_json'));
$allowedQuestionTypes = ["text", "comment", "boolean","checkbox", "radiogroup", "dropdown", "rating", "ranking","imagepicker","matrix","matrixdropdown","html"];
$configuredQuestionTypes =  explode(',',$_COMPANY->getAppCustomization()['surveys']['question_types_csv']);
$availableQuestionTypes = json_encode(array_values(array_intersect($allowedQuestionTypes,$configuredQuestionTypes)));

include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/create_survey.php');
include(__DIR__ . '/views/footer_html.php');

