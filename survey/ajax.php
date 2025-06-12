<?php
// This AJAX file is used to keep all functions that are accessbile without user login required
require_once __DIR__.'/head.php';

if (isset($_GET['saveSurveyResponse'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (!verify_recaptcha()) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Lower case $company is used to avoid setting global variable
    $company = Company::GetCompanyByUrl("https://{$_SERVER['HTTP_HOST']}");

    if (($surveyid = $company->decodeId($_POST['surveyid']))<0 ||
        ($survey = Survey2::GetSurveyByCompany($company,$surveyid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Check User already took Survey
    if(isset($_COOKIE["survey_".hash("crc32", $_POST['surveyid'])]) && !$survey->val('allow_multiple')) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$survey->val('anonymous')) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $responseJson = $_POST['responseJson'];

    if ($survey->saveOrUpdateSurveyResponse(0,$responseJson,'')) {
        // Note: Cookie is set using encoded surveyid.
        setcookie("survey_".hash("crc32", $_POST['surveyid']), "1", time() + (86400 * 365), "/1/survey");
        echo 1;
        exit();
    }
    echo 0;
    exit();
} 

elseif (isset($_GET['saveNativeSurveyResponse'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Lower case $company is used to avoid setting global variable
    $company = Company::GetCompanyByUrl("https://{$_SERVER['HTTP_HOST']}");

    if (
        ($surveyid = $company->decodeId($_POST['surveyid']))<0
        ||
        ($survey = Survey2::GetSurveyByCompany($company,$surveyid)) === null
        ||
        ($uid = $company->decodeId($_POST['uid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $uid = ($survey->val('anonymous')) ? 0 : $uid;

    $responseJson = $_POST['responseJson'];

    $anonymous = $survey->val('anonymous');
    $userid = 0;
    $profile_json = '';
    if(!$anonymous){
        $userid = $uid;

        { // Just a block to show the importance of temporary use of $_COMPANY variable
            global $_COMPANY, $_USER;
            $_COMPANY = $company; // Temporarily setting global $_COMPANY variable to get the user data.
            $survey_user = User::GetUser($userid);
            if ($survey_user) {
                $profile_json = json_encode(array('firstname'=>$survey_user->val('firstname'),'lastname'=>$survey_user->val('lastname'),'email'=>$survey_user->getEmailForDisplay(),'jobTitle'=>$survey_user->val('jobtitle'),'officeLocation'=>$survey_user->getBranchName(),'department'=>$survey_user->getDepartmentName()));
            }
            $_COMPANY = null; // Resetting it back.
        }
    }

    if (!empty($responseJson) &&
        $survey->saveOrUpdateSurveyResponse($userid,$responseJson,$profile_json,'')
    ) {
        echo 1;
        exit();
    }
    echo 0;
    exit();
}

else {
    Logger::Log ("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}
