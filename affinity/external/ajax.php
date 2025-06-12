<?php
// This AJAX file is used to keep all functions that are accessbile without user login required
require_once __DIR__.'/head.php';

if(isset($_GET['joinExternalEvent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        !isset($_POST['joinStatus'])
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $email = '';
    if (isset($_SESSION['external_user'])){
        if ($_SESSION['external_user']['email']){
            $email = $_SESSION['external_user']['email'];
        }
    }

    if ($email == ''){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("User info font found"), gettext('Error'));
    }

	$joinstatus = (int)($_POST['joinStatus']); // joinstats: 1 for Accept, 2 for Tentative, 3 for Decline, 11 for in-person, 12 for waitlist, 21 for remote
    $eventJoinSurveyResponses = '';
    if (!empty($_POST['eventJoinSurveyResponse'])){
        $eventJoinSurveyResponses = $_POST['eventJoinSurveyResponse'];
    }

    $joinid = $event->externalCheckin_fetchExistingCheckinRecord($email);

    if ($joinid) {
        $event->updateExternalEventRsvp($joinid,$joinstatus,$eventJoinSurveyResponses);
    } else {
        [$firstname,$lastname,$email] = array_values($_SESSION['external_user']);
        $joinid = $event->RsvpExternalEvent($firstname,$lastname,$email,$joinstatus,$eventJoinSurveyResponses);
    }

    if ($joinid) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("RSVP updated successfully"), gettext('Success'));
    } 

    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Your request to update RSVP failed. Please check your connection and try again later."), gettext('Error'));

}

elseif (isset($_GET['getPreJoinExternalEventSurvey'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ||
        ($event = Event::GetEvent($eventid)) === NULL  ) {
            header(HTTP_FORBIDDEN);
            exit();
    }
    $joinStatus = (int)$_GET['joinStatus'];
   
    if (in_array($joinStatus,  Event::RSVP_TYPE)){
        $surveyData = $event->getEventSurveyByTrigger(Event::EVENT_SURVEY_TRIGGERS['EXTERNAL_PRE_EVENT']);

        if ($surveyData){
            include(__DIR__ . "/views/external_event_survey.template.php");
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("No pre event join survey found."), gettext('error'));
        }
       
    }  else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('error'));
    }
    
}

elseif(isset($_GET['downloadICSFile']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (empty($_GET['downloadICSFile'])
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
	$params = json_decode(aes_encrypt($_GET['downloadICSFile'], TELESKOPE_USERAUTH_API_KEY, "u7KD33py3JsrPPfWCilxOxojsDDq0D3M", true),true); 
    if (!empty($params)){
        $eventid = $params['eventid'];
        $email = $params['email'];
        if ($eventid  && $email ){
            $event = $db->get("SELECT * FROM `events` WHERE `eventid`='{$eventid}'");
            if (!empty($event)){
                $_COMPANY = Company::GetCompany($event[0]['companyid']); // Temporary 
                $_ZONE = $_COMPANY->getZone($event[0]['zoneid']); // Temporary
                $job = new EventJob($event[0]['groupid'],$eventid);
                $ics = $job->getIcsFile($email);
                $_COMPANY =null;
                $_ZONE = null;
                if($ics){
                    header("Content-type:text/calendar");
                    header('Content-Disposition: attachment; filename="'.$event[0]['eventtitle'].'.ics"');
                    Header('Content-Length: '.strlen($ics));
                    Header('Connection: close');
                    echo $ics;
                }
            }
        }
    }
    echo false;
}

elseif (isset($_GET['saveExternalUserInformation'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<0 ||
        ($event = Event::GetEvent($eventid)) === NULL  ) 
    {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $timezone = $_POST['timezone'];
    $check = array('First Name' => $firstname, 'Last Name' => $lastname, 'Email' => $email,'Timezone'=>$timezone);
    $checkRequired = $db->checkRequired($check);
    if ($checkRequired) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$checkRequired), gettext('error'));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s is not a valid email"),$email), gettext('error'));
    }
    $_SESSION['external_user'] = array('firstname'=>$firstname,'lastname'=>$lastname,'email'=>$email,'timezone'=>$timezone);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Information saved."), gettext('Success'));

}

elseif (isset($_GET['clearExternalEventSession'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $_SESSION = array();
    session_destroy();
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Event checkin session logout successfully"), gettext('Success'));
}

elseif (isset($_GET['joinExternalEventAsEventVolunteer']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

	if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL || 
        ($volunteertypeid = $_COMPANY->decodeId($_GET['volunteertypeid']))<0
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (empty($_SESSION['external_user'])){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please provide your basic information to signup as volunteer.'), gettext('Error'));
    }

    [$firstname,$lastname,$email] = array_values($_SESSION['external_user']);

    $status = $event->signupExternalEventVolunteer($firstname,$lastname,$email, $volunteertypeid);

    if ($status == -1) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Event Volunteer request capacity has been met'), gettext('Error'));
    } elseif ($status == 1 || $status == 2) {
        $rsvpDetails = $event->getMyRSVPOptions();
        $joinid = $event->externalCheckin_fetchExistingCheckinRecord($email);
        if (!$joinid && $status == 1) { // Join Event
            if ($rsvpDetails['max_inperson'] >0 || $rsvpDetails['max_online'] >0){
                if ($rsvpDetails['available_inperson'] >0 ) {
                    $event->RsvpExternalEvent($firstname,$lastname,$email,Event::RSVP_TYPE['RSVP_INPERSON_YES'],'');
                } elseif ($rsvpDetails['available_online'] >0 ) {
                    $event->RsvpExternalEvent($firstname,$lastname,$email,Event::RSVP_TYPE['RSVP_ONLINE_YES'],'');
                } else {
                    if ($rsvpDetails['max_inperson'] >0) {
                        $event->RsvpExternalEvent($firstname,$lastname,$email,Event::RSVP_TYPE['RSVP_INPERSON_WAIT'],'');
                    } else {
                        $event->RsvpExternalEvent($firstname,$lastname,$email,Event::RSVP_TYPE['RSVP_ONLINE_WAIT'],'');
                    }                    
                }
            } else {
                $event->RsvpExternalEvent($firstname,$lastname,$email,Event::RSVP_TYPE['RSVP_YES'],'');
            }
        }                  
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Event Volunteer assigned successfully"), 'Success');
        
    } elseif ($status == 3) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Yo have already signedup for this role"), 'Success'); // Already a member
    } 
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error'));
}

elseif (isset($_GET['leaveExternalEventVolunteerEnrollment'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (empty($_SESSION['external_user'])){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please provide your basic information to signup as volunteer.'), gettext('Error'));
    }

    [$firstname,$lastname,$email] = array_values($_SESSION['external_user']);

    $event->removeExternalEventVolunteer($email);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Event Volunteer role left successfully"), 'Success');
}

else {
    error_log ("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}
