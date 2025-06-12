<?php
include_once __DIR__.'/../affinity/head.php'; // Guarantee the user is logged in
global $_COMPANY, $_ZONE, $_USER;

$htmlTitle = gettext("Edit Survey");

if (!defined('INDEX_PAGE') && empty ($_ZONE)) { // User is logged in, but for some reason the $_ZONE is not set, logout and retry
    Http::Redirect('logout');
}

if (!isset($_GET['eventid']) ||
    ($eventid = $_COMPANY->decodeId($_GET['eventid'])) < 1 ||
    ($event = Event::GetEvent($eventid)) === null ||
    !isset($_GET['trigger']) ||
    !in_array($_GET['trigger'], array_keys(Event::EVENT_SURVEY_TRIGGERS))
) {
    header(HTTP_BAD_REQUEST);
    exit('Bad Request');
}

$trigger = $_GET['trigger']; // Already validated to be valid value.

if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
    echo "Access Denied (Insufficient permissions)";
    header(HTTP_FORBIDDEN);
    exit('Forbidden');
}

$json = $event->getEventRsvpOptionsReadOnlySurvey();
$teleskopeQuestionCounter = 1;
if (
    isset($_GET['templateid']) &&
    ($templateid = $_COMPANY->decodeId($_GET['templateid'])) > 0
) {
    $clonedSurvey = Survey2::GetSurvey($templateid);
    $surveyJson = json_decode($clonedSurvey->val('survey_json'), true);
    $surveyQuestions = array();
    $surveyQuestionsPages = array();
    if (!empty($surveyJson) && isset($surveyJson['pages'])){      
        $surveyQuestionsPages = $surveyJson['pages'];        
        foreach($surveyQuestionsPages as $element){
            if(!empty($element['elements'])){
                $surveyQuestions = array_merge($surveyQuestions,$element['elements']);
            }            
        }  
    }
    if (!empty($surveyQuestions)){
        $teleskopeQuestionCounter = Survey2::GetSurveyQuestionCounter(json_encode($surveyJson));    
        $rsvpQuestion = $json['pages'][0]['elements'];
        $surveyThankyouOptions['completedHtml'] = $surveyJson['completedHtml'];
        $surveyThankyouOptions['completedBeforeHtml'] = $surveyJson['completedBeforeHtml'];
        $json['pages'][0]['elements'] = array_merge($rsvpQuestion,$surveyQuestions);
        $json['thankyou_options'] = $surveyThankyouOptions;
    }
}

$surveyData = null;
$groupid = $_COMPANY->encodeId($event->val('groupid')); // Group id is needed to return to the group on survey completion
$eventid = $_COMPANY->encodeId($eventid);
$pagetitle = gettext("Create Event Survey");


$surveyData = $event->getEventSurveyByTrigger($trigger);
if ($surveyData) {
    $json = $surveyData['survey_questions'];
    $pagetitle = gettext("Update event survey");
    $teleskopeQuestionCounter = Survey2::GetSurveyQuestionCounter(json_encode($json));
}

$rurl =  base64_url_decode($_GET['rurl']);

include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/create_event_survey.php');
include(__DIR__ . '/views/footer_html.php');

