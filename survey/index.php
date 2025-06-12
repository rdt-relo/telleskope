<?php
include_once __DIR__.'/head.php';
$surveyid = 0;

//$_SESSION['input'] = null;
if (empty($_GET['surveyid'])) {
    error_and_exit("The survey link is invalid(1).");
    exit();
}

$company = Company::GetCompanyByUrl("https://{$_SERVER['HTTP_HOST']}");

if (!$company ||
    ($surveyid = $company->decodeId($_GET['surveyid'])) < 1 ||
    ($survey = Survey2::GetSurveyByCompany($company,$surveyid)) === null ||
    $survey->val('isactive') !='1' ||
    $survey->val('surveysubtype') != 127
     ) {
        error_and_exit("The survey link is invalid(2).");
        exit();
}

// Check User already took Survey
if (isset($_COOKIE["survey_".hash("crc32", $_GET['surveyid'])]) && !$survey->val('allow_multiple')) {
    error_and_exit("It seems you have already answered this survey.");
    exit();
}

$enc_surveyid = $company->encodeId($surveyid);
$anonymous = $survey->val('anonymous');
$survey_json = $survey->val('survey_json');
$survey_name = $survey->val('surveyname');
$surveyLanguages = $survey->getSurveyLanguages();
$csrfToken = '';
$next_url = 'ajax.php?saveSurveyResponse=1';

if (!$anonymous) {
    if (empty($_SESSION['userid'])) {
        ## Redirect URL after login
        $rurl = base64_url_encode("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
        if (strpos($_SERVER['HTTP_HOST'], 'affinities') !== FALSE) {
            $redirect = $company->getAppURL('affinities') . 'index?rurl=' . $rurl;
            header('location:'.$redirect); // Take the user to login screen
            exit();
        } elseif (strpos($_SERVER['HTTP_HOST'], 'officeraven') !== FALSE) {
            $redirect = $company->getAppURL('officeraven') . 'index?rurl=' . $rurl;
            header('location:'.$redirect); // Take the user to login screen
            exit();
        } elseif (strpos($_SERVER['HTTP_HOST'], 'talentpeak') !== FALSE) {
            $redirect = $company->getAppURL('talentpeak') . 'index?rurl=' . $rurl;
            header('location:'.$redirect); // Take the user to login screen
            exit();
        }  else {
            error_and_exit("The survey link is invalid(3).");
        }
    } else { // check survey response setting
        $responseSetting = $survey->val('survey_response_setting');
        if ($responseSetting=='update_not_allowed' && $survey->isSurveyResponded($_SESSION['userid'])){
            error_and_exit("You have already responded to this survey");
        }

        {   // init $_USER and $_ZONE for getting scrfToken
            $_COMPANY = $company;
            $_USER = User::GetUser($_SESSION['userid']);
            $_ZONE = $_COMPANY->getZone($survey->val('zoneid'));
            $csrfToken = Session::GetInstance()->csrf;
            $next_url = Url::GetZoneAwareUrlBase($_ZONE->id()) . 'ajax.php?saveSurveyResponse=1';
            $COMPANY = null;// Reset
            $_USER = null; // Reset
            $_ZONE = null; // Reset
        }
    }
}

//
// Temporarily set global $_COMPANY for the following block as it is needed for Group::GetGroup
$_COMPANY = $company;
$logo = $_COMPANY->val('logo');
if ($survey->val('groupid')){
    $group = Group::GetGroup($survey->val('groupid'));
    if($group && $group->val('groupicon')){
        $logo =  $group->val('groupicon');
    }

}
$_COMPANY  = null; // Reset


include __DIR__ . '/views/index.html';
