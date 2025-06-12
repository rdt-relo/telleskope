<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__.'/../affinity/head.php';//Common head.php includes destroying Session for timezone so defining INDEX_PAGE. Need head for logging and protecting

global $_COMPANY;
global $_USER;
global $_ZONE;

/* @var Company $_COMPANY */
/* @var User $_USER */

###### All Ajax Calls ##########

if (isset($_GET['updateSurvey'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (($surveyid = $_COMPANY->decodeId($_POST['surveyid']))<0 ||
        ($survey = Survey2::GetSurvey($surveyid)) === null ||
        ($version = $_COMPANY->decodeId($_POST['version']))<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }


    if (!$_USER->canManageContentInScopeCSV($survey->getGroupId(), $survey->getChapterId(), $survey->getChannelId())) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    ViewHelper::ValidateObjectVersionMatchesWithPostAttribute($survey);

    // Remove navigateToUrl attribute if set. navigateToUrl feature conflicts with survey response storage.
    $surveyJSON = json_decode($_POST['surveyJSON'],true);
    if (array_key_exists('navigateToUrl',$surveyJSON)){
        unset($surveyJSON['navigateToUrl']);
    }
    $surveyJSON = json_encode($surveyJSON);
    $update = $survey->updateSurvey($surveyJSON);
    
    if ($update){
        $rurl = $survey->getSurveyListUrl();

        if ($surveyid){
            $mssage = gettext("Survey updated successfully");
        } else {
            $mssage = gettext("Survey created successfully");
        }
        
        $response = json_encode(array('id'=>$surveyid,'rurl'=>$rurl));
        AjaxResponse::SuccessAndExit_STRING(1, $rurl, $mssage, gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', '', gettext('Error'));
    }
}

elseif (isset($_GET['showSurveyPublishRestrictionModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (
        ($surveyid = $_COMPANY->decodeId($_GET['surveyid']))<0 ||
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        ($survey = Survey2::GetSurvey($surveyid))  == null
     ) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $adminstrators = User::GetReviewersByScope('0','0','0');
    include(__DIR__ . "/views/templates/survey_restriction_modal.template.php");   
}
elseif (isset($_GET['sendRequestForSurveyAction'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (
        ($surveyid = $_COMPANY->decodeId($_POST['surveyid']))<0 ||
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0  ||
        ($group = Group::GetGroup($groupid))  == null ||
        ($survey = Survey2::GetSurvey($surveyid))  == null ||
        ($adminid = $_COMPANY->decodeId($_POST['adminid']))<0
        
     ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $adminUser = User::GetUser($adminid);
    $email = $adminUser->val('email');

    if (!empty($email)){
        $review_note = "Please review and publish this survey";
        $survey_review_job = new SurveyJob($groupid,0,$surveyid);
        if ($survey_review_job->sendForReview($email, $review_note)) {
            $survey->updateSurveyForReview();
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Your survey has been emailed to reviewers to review.'), gettext('Success'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Unable to send email at this time. Please try again later"), gettext('Error'));
        }
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please add some reviewers'), gettext('Error'));
    }
}
elseif (isset($_GET['openReviewSurveyModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['openReviewSurveyModal']))<0 ||
        ($surveyid = $_COMPANY->decodeId($_GET['surveyid'])) < 1 ||
        ($survey = Survey2::GetSurvey($surveyid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canCreateOrPublishContentInScopeCSV($survey->val('groupid'),$survey->val('chapterid'), $survey->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
	}

    $reviewers = User::GetReviewersByScope('0','0','0');
    // The template needs following inputs in addition to $reviewers set above
    $template_review_what = gettext('Survey');
    $enc_groupid = $_COMPANY->encodeId($survey->val('groupid'));
    $enc_objectid = $_COMPANY->encodeId($surveyid);
    $template_email_review_function = 'sendSurveyToReview';

    include(__DIR__ . "/views/templates/general_email_review_modal.template.php");
	
}

elseif (isset($_GET['sendSurveyToReview']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        !isset($_POST['objectid']) ||
        ($surveyid = $_COMPANY->decodeId($_POST['objectid']))<1 ||
        ($survey = Survey2::GetSurvey($surveyid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
	}

    $validEmails = [];
    $invalidEmails = [];

    $e_arr = explode(',',str_replace(';',',',$_POST['emails']));
    foreach ($e_arr as $e) {
        $e = trim($e);
        if (empty($e)){
            continue;
        }
        if (!filter_var($e, FILTER_VALIDATE_EMAIL)){
            array_push($invalidEmails,$e);
        } elseif (!$_COMPANY->isValidEmail($e)) {
            array_push($invalidEmails,$e);
        } else {
            array_push($validEmails,$e);
        }
    }

    if (count($invalidEmails)){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Invalid emails: %s'),implode(', ',$invalidEmails)), gettext('Error'));
    }

    if (count($validEmails) > 10){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Too many emails... %s emails entered (maximum allowed is 10)'),count($validEmails)), gettext('Error'));
    }

	$reviewers = [];
	if (isset($_POST['reviewers']) && !empty(@$_POST['reviewers'][0])){
		$reviewers = $_POST['reviewers'];
	}
	$reviewers[] = $_USER->val('email'); // Add the current user to review list as well.

	$allreviewers = implode(',',array_unique (array_merge ($validEmails , $reviewers)));
	if (!empty($allreviewers)){
		$review_note = raw2clean($_POST['review_note']);
		$survey_review_job = new SurveyJob($groupid,0,$surveyid);
		if ($survey_review_job->sendForReview($allreviewers, $review_note)) {
            $survey->updateSurveyForReview();
            AjaxResponse::SuccessAndExit_STRING(1, $groupid, gettext('Your survey has been emailed to reviewers to review.'), gettext('Success'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Unable to send email at this time. Please try again later"), gettext('Error'));
        }
	} else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please add some reviewers'), gettext('Error'));
	}
}

elseif (isset($_GET['updateSurveyInfoModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        ($surveyid = $_COMPANY->decodeId($_GET['surveyid']))<1 || 
        ($survey = Survey2::GetSurvey($surveyid)) === null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($survey->val('groupid'));
    $encSurveyId = $_COMPANY->encodeId($survey->id());
    // Authorization Check
    if ($groupid){
        if (!$_USER->canManageContentInScopeCSV($survey->val('groupid'),$survey->val('chapterid'),$survey->val('channelid'))) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } else {
        if (!$_USER->isAdmin()) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }
    // approval check
    $isActionDisabledDuringApprovalProcess = $survey ? $survey->isActionDisabledDuringApprovalProcess() : false;
    $form_title = gettext("Update Survey Setting");
    $approval = $survey->getApprovalObject();
    $allowTitleUpdate=true;
    $allowDescriptionUpdate=true;
    if ($approval) {
        [$allowTitleUpdate,$allowDescriptionUpdate] = $approval->canUpdateAfterApproval();
    }
	include(__DIR__ . "/views/templates/update_survey_setting.template.php");
}


elseif (isset($_GET['updateSurveySetting'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($surveyid = $_COMPANY->decodeId($_POST['surveyid']))<1 ||
        ($survey = Survey2::GetSurvey($surveyid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($survey->val('groupid')){
        if (!$_USER->canManageContentInScopeCSV($survey->val('groupid'),$survey->val('chapterid'),$survey->val('channelid'))) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } else {
        if (!$_USER->isAdmin()) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }

    if (!isset($_POST['surveyname']) || ($surveyname = trim($_POST['surveyname'])) == ''){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter survey name!'), gettext('Error'));
    }
    
    $sendEmailNotificationTo = $_POST['sendEmailNotificationTo'];
    if (!empty($sendEmailNotificationTo) && !$_COMPANY->isValidAndRoutableEmail($sendEmailNotificationTo)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter a valid email address!'), gettext('Error'));
    }
   
    $is_required = 0;
    if (isset($_POST['is_required'])){
        $is_required = (int)$_POST['is_required'];
    }

    $anonymity  = 0;
    if (isset($_POST['anonymity'])){
        $anonymity  = (int)$_POST['anonymity'];
    } 
    
    if ($survey->updateSurveyInformation($surveyname,$is_required,$sendEmailNotificationTo,$anonymity)){
        AjaxResponse::SuccessAndExit_STRING(($groupid ? 1 : 2), '', gettext('Survey information updated successfully'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong, please try again.'), gettext('Error'));
    }
}



elseif (isset($_GET['importSurveyDataModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($surveyid = $_COMPANY->decodeId($_GET['surveyid']))<1 ||
        ($survey = Survey2::GetSurvey($surveyid)) === null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($survey->val('anonymous')){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Import feature is not available for anonymous surveys'), gettext('Error'));
    }
    elseif($survey->val('allow_multiple')){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Import feature is not available for surveys that allow multiple responses'), gettext('Error'));
    }
    elseif($survey->checkSurveyQuestionsHasType(array('matrix','matrixdropdown'))){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Data for this survey cannot be imported as the survey contains questions that are currently not supported by the import feature'), gettext('Error'));
    }
    else{
        $pageTitle = gettext("Import Survey Responses");
        include(__DIR__ . "/views/survey/import_surveys_form_csv_file.template.php");
    }
}

elseif (isset($_GET['deleteSurveyDataConfirmationModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($surveyid = $_COMPANY->decodeId($_GET['surveyid']))<1 ||
        ($survey = Survey2::GetSurvey($surveyid)) === null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    include(__DIR__ . "/views/survey/delete_surveys_responses.template.php");

}

elseif (isset($_GET['submitImportSurveysData']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($surveyid = $_COMPANY->decodeId($_POST['surveyid']))<1 ||
        ($survey = Survey2::GetSurvey($surveyid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $response = array();
    if(!empty($_FILES['import_file']['name'])){
        $file 	   		=	basename($_FILES['import_file']['name']);
        $tmp 			=	$_FILES['import_file']['tmp_name'];
        $ext = substr(pathinfo($file)['extension'],0,4);
        // Allow certain file formats
        if($ext != "csv"  ) {
            $error =  gettext("Sorry, only .csv file format allowed");
            $response = array('status'=>0,'title'=>gettext('Error'),'message'=>$error);
        }

        if (empty($response)) {
            try {
                $csv = Csv::ParseFile($tmp);
                if ($csv) {
                   $success = $survey->processImportSurveys($csv);
                   $response = array('status'=>1,'message'=>gettext('Survey response file processed successfully'), 'data'=>$success );
                } else {
                    $response = array('status'=>0,'title'=>gettext('Success'),'message'=>gettext('CSV file format issue'));
                }
            } catch (Exception $e) {
                //$error = $e->getMessage();
                $response = array('status'=>0,'title'=>gettext('Error'),'message'=>$e->getMessage());
            }
        }
    } else {
        $response = array('status'=>0,'title'=>gettext('Error'),'message'=>gettext('Please select a csv file'));
    }
    echo json_encode($response);
    exit();
}



else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}