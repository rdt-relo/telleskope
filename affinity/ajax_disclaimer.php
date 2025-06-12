<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__.'/head.php';

/* @var Company $_COMPANY */
/* @var User $_USER */

###### All Ajax Calls Regarding Disclaimer ##########

// GET Discussions

if (isset($_GET['loadDisclaimerByHook'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    
    if (
        ($hook = $_COMPANY->decodeId($_GET['disclaimerHook'])) < 1 ||
        !in_array($hook,Disclaimer::DISCLAIMER_HOOK_TRIGGERS)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $consentContextId = isset($_GET['consentContextId']) ? $_COMPANY->decodeId($_GET['consentContextId']) : 0;

    $reloadOnclose = boolval($_GET['reloadOnClose'] ?? false);
    $callOtherMethodOnClose = isset($_GET['callOtherMethodOnClose']) ? base64_url_decode($_GET['callOtherMethodOnClose']) : '{}';

    $callOtherMethodOnClose = json_decode($callOtherMethodOnClose,true);
    // Zone check and assignment are required for expense entries, as they can be added across zones in a collaborated event.
    if (isset($callOtherMethodOnClose['method']) && $callOtherMethodOnClose['method'] == 'addEventExpenseEntry'){
        $gid = $_COMPANY->decodeId($callOtherMethodOnClose['parameters'][0]);
        if ($gid>0) {
            $group = Group::GetGroup($gid);
            if ($group->val('zoneid')!=$_ZONE->id()){
                $_ZONE = $_COMPANY->getZone($group->val('zoneid'));
            }
        }
    }
    $disclaimer = Disclaimer::GetDisclaimerByHook($hook);

    if (!empty($disclaimer)){
        $disclaimer_language = $_USER->val('language');
        $disclaimerMessage =  $disclaimer->getDisclaimerBlockForLanguage($disclaimer_language);

        if (!empty($disclaimerMessage)){
            $disclaimer_language = $disclaimerMessage['language'];
        }
        include(__DIR__ . '/views/common/disclaimer_new_template.php');
    }else{
        AjaxResponse::SuccessAndExit_STRING(0, '', "Invalid hook", gettext('Error'));
    }
}
// Add consent data to database disclaimer_consent table.

elseif (isset($_GET['addConsent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
       
    if (
        ($disclaimerId =  $_COMPANY->decodeId($_POST['disclaimerId']))<1 ||
        ($disclaimer = Disclaimer::GetDisclaimerById($disclaimerId)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // grab values
    $consentText =  $_POST['consentText'];
    $consentLang =  $_POST['consentLang'];

    $consentContextId = isset($_POST['consentContextId']) ? $_COMPANY->decodeId($_POST['consentContextId']) : 0;
    // save consent
    $id = $disclaimer->saveUserConsent($consentText, $consentLang, $consentContextId);

    if ($id) {
        if ($disclaimer->val('hookid') == Disclaimer::DISCLAIMER_HOOK_TRIGGERS['LOGIN_FIRST']) {
            Session::GetInstance()->login_disclaimer_shown = 1;
        }
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Consent saved successfully'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Error saving consent, please try again", gettext('Error'));
    }
}

elseif (isset($_GET['updateShowDisclaimerConsentSession']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    Session::GetInstance()->login_disclaimer_shown = 1;
    echo 1;
}

elseif (isset($_GET['checkIfAllConcentAccepted']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (
        ($disclaimerIds =  $_COMPANY->decodeIdsInCSV($_GET['disclaimerIds'])) == '' ||
        ($consentContextId =  $_COMPANY->decodeId($_GET['consentContextId'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (Disclaimer::IsAllWaiverAccepted($disclaimerIds, $consentContextId)) {
        echo 1;
    } else {
        echo 0;
    }
}

else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}

