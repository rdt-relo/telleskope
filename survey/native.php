<?php
include_once __DIR__ . '/head.php';
$survey = array();
$error = null;
$uid = 0;
$app_type = 'affinities';
$enc_surveyid = '';
$next_link = 'flutter';


/**
 * Convert response to JSON
 * @param string $method name of the method to be sent back to client
 * @param string $data the data to be sent back to client
 * @param int $success 1 on success, 0 on failure, -1 on Fatal
 * @param string $message
 * @param int $http_code
 * @return false|string
 */
function commonBuildApiResponseAsJson($method = '', $data = '', $success = 1, $message = '', $http_code = 200)
{
    http_response_code($http_code);
    $response = array();
    $response['message'] = $message;
    $response['success'] = $success;
    $response['apiFetchTime'] = time();
    $response['cacheExpiryTime'] = $response['apiFetchTime'] + 3600;
    if ($method) {
        $response['method'] = $method;
    }
    if ($data) {
        $response['data'] = $data;
    }
    //Logger::Log("Returning: ".json_encode($response));
    return json_encode($response);
}

if (empty($_GET['sid'])) {
    $error = commonBuildApiResponseAsJson('getSurveyQuestion', '', 0, 'This survey does not exist anymore.');
} elseif (
    ($company = Company::GetCompanyByUrl("https://{$_SERVER['HTTP_HOST']}")) === null
    || empty($_GET['uid'])
    || ($uid = $company->decodeId($_GET['uid'])) < 0
    ) {
    $error = commonBuildApiResponseAsJson('getSurveyQuestion', '', 0, 'Unauthorized access.');
} else {
    // The previous call set the global $_COMPANY, $_ZONE
    $enc_surveyids = $_GET['sid'];
    $enc_surveyid = array_pop($enc_surveyids);
    if ($enc_surveyid && ($surveyid = $company->decodeId($enc_surveyid))) {
        $survey = Survey2::GetSurveyByCompany($company, $surveyid);

        if (!empty($survey)) {
            $topicsubtype = $survey->val('topicsubtype');
            $anonymous = $survey->val('anonymous');
            $survey_json = $survey->val('survey_json');
            $logo = $company->val('logo');
            $enc_surveyid = $company->encodeId($surveyid);
            $enc_resp_uid = $company->encodeId($anonymous ? 0 : $uid);

            if (count($enc_surveyids)) { // There are more surveys so set the next survey link to take after this survy
                $next_link = "https://{$_SERVER['HTTP_HOST']}/1/survey/native";
                $next_link .= '?uid=' . $company->encodeId($uid);
                foreach ($enc_surveyids as $enc_sid) {
                    $next_link .= '&sid[]='.$company->encodeId($company->decodeId($enc_sid));
                }
            }
        } else {
            $error = commonBuildApiResponseAsJson('getSurveyQuestion', '', 0, 'This survey does not exist anymore.');
        }
    }
}
// Note native.html knows how to handle the error messages, so include it in the last.
include __DIR__ . '/views/native.html';
