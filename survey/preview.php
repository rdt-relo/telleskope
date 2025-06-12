<?php
include_once __DIR__.'/head.php';

//$_SESSION['input'] = null;
if (empty($_GET['params'])) {
    error_and_exit("Survey link is not valid.");
    exit();
}

$params = json_decode(aes_encrypt($_GET['params'], TELESKOPE_USERAUTH_API_KEY, "oBu1tOvMUKuWlFHrFaMuswWj7eloTXDWbFb6Y1NZ", true),true);
$company = Company::GetCompanyByUrl("https://{$_SERVER['HTTP_HOST']}");
if (empty($params['companyid']) ||
    empty($params['surveyid']) ||
    empty($params['expirytime']) ||
    (Company::GetCompany($params['companyid']) != $company)){
    error_and_exit("Survey link is not valid.");
    exit();
}

if ($params['expirytime'] < time()) {
    error_and_exit("Survey link has expired");
    exit();
}

{ // Block to accentuate temporary use of Company Object
    $_COMPANY = $company; // Temporarily set
    if (($survey = Survey2::GetSurvey($params['surveyid'])) === null) {
        error_and_exit("Survey link is not valid.");
        exit();
    }
    $survey_json = $survey->val('survey_json');
    $survey_name = $survey->val('surveyname');

    include __DIR__ . '/views/preview.html';

    $_COMPANY = null; // Reset
}

