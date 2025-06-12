<?php
include_once __DIR__.'/head.php';


//Data Validation
if (
    ($eventid = $_COMPANY->decodeId($_GET['eventid'])) < 1  ||
    ($event = Event::GetEvent($eventid))  == null || 
    ($joinStatus = $_COMPANY->decodeId($_GET['joinStatus'])) < 0 ||
    (!in_array($joinStatus,  Event::RSVP_TYPE))
) {
    error_and_exit('ERR: Invalid URL Error!');
}
$groupid = $event->val('groupid');
$survey_trigger = $_GET['trigger'];
if ($survey_trigger == Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT']) {
    $userPreEventSurveyResponses = $event->getEventSurveyResponsesByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'],true);
    $event->getMyRsvpStatus();

    if (
        $_COMPANY->getAppCustomization()['event']['enable_event_surveys'] &&
		$event->isEventSurveyAvailable(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'])
    ){
        if(!empty($event->getEventSurveyResponsesByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT']))){
            $pageTitle = "Update Event Pre Survey";
            $surveyResponses = $event->getEventSurveyResponsesByTrigger($survey_trigger) ?? array();
            $surveyQuestions = $event->getEventSurveyByTrigger($survey_trigger, $joinStatus);
            include __DIR__ . '/views/eventUpdatePrePostSurvey_html.php';
        } else {
            $pageTitle = "Respond Event Pre Survey";
            $surveyData = $event->getEventSurveyByTrigger($survey_trigger, $joinStatus);
            if ($surveyData){
                include __DIR__ . '/views/eventRespondPrePostSurvey_html.php';
            } else {
                error_and_exit('ERR-2: Invalid URL Error!');
            }
        }
    }
    else {
        error_and_exit('ERR-3: Invalid URL Error!');
    }

} elseif ($survey_trigger == Event::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT']) {
    $userPostEventSurveyResponses = $event->getEventSurveyResponsesByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT'],true);
    if ($userPostEventSurveyResponses) {
        $surveyResponses = $event->getEventSurveyResponsesByTrigger($survey_trigger) ?? array();
        $surveyQuestions = $event->getEventSurveyByTrigger($survey_trigger, $joinStatus);
        include __DIR__ . '/views/eventUpdatePrePostSurvey_html.php';
    }else {
        error_and_exit('ERR-4: Invalid URL Error!');
    }
}
