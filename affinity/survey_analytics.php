<?php
require_once __DIR__.'/head.php'; // Guarantee the user is logged in
global $_COMPANY, $_ZONE, $_USER;

#{$handle}
if (!$_COMPANY->getAppCustomization()['surveys']['analytics']) {
    echo "Access Denied (Feature disabled)";
    header(HTTP_FORBIDDEN);
    exit('Forbidden');
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

$questions = array();
$answers = array();
$departments = array();
$regions = array();
$branches = array();
//$jobTitles = array();
$questions = json_decode($survey->val('survey_json'), true)['pages'];

if (is_null($questions)) {
    Http::NotFound(gettext('This survey has no questions'));
}

$pageCount = count($questions);

$getAnswers = $survey->getSurveyResponses();
$skipped_response_count = 0;
foreach ($getAnswers as $ans) {
    $response_json = json_decode($ans['response_json'], true);

    if (empty($response_json)){ // Skip empty responses
        $skipped_response_count++;
        continue;
    }
    $response_json['HappendAt'] = date("F j, Y, g:i a",strtotime($ans['createdon']));
    $profile_json = json_decode($ans['profile_json'], true);
    if ($profile_json) {
        if (!$survey->val('anonymous')) {
            // For non-anonymous surveys, enhance the results with department and branchname
            $departmentName = empty($profile_json['department']) ? 'Unknown' : $profile_json['department'];
            if (($d = array_search($departmentName, $departments)) === FALSE) {
                $d = array_push($departments, $departmentName) - 1;
            }
            $response_json['departments'] = 'item' . $d;

            $branchName = empty($profile_json['officeLocation']) ? 'Unknown' : $profile_json['officeLocation'];
            if (($b = array_search($branchName, $branches)) === FALSE) {
                $b = array_push($branches, $branchName) - 1;
            }
            $response_json['officeLocation'] = 'item' . $b;

            // Value of job title is debatable as it is too personal.
            //$jobTitle = $profile_json['jobTitle'] ?: 'Unknown';
            //if (($j = array_search($jobTitle, $jobTitles)) === FALSE) {
            //    $j = array_push($jobTitles,$jobTitle)-1;
            //}
            //$response_json['jobTitle'] = 'item'.$j;
        }
    }
    array_push($answers, $response_json);
}


if (!$survey->val('anonymous')) {
    $questions[$pageCount]['name'] = 'profile';
    // For non-anonymous surveys, enhance the results with department and branchname
    // Departments
    $departmentQuestion = array();
    $departmentQuestion['name'] = 'departments';
    $departmentQuestion['title'] = 'Departments';
    $departmentQuestion['type'] = 'radiogroup';
    $choices = array();
    for ($x = 0; $x < count($departments); $x++) {
        $choices[] = array('value' => 'item' . $x, 'text' => $departments[$x]);
    }
    $departmentQuestion['choices'] = $choices;
    $questions[$pageCount]['elements'][] = $departmentQuestion;

    // Branch offices
    $branchesQuestion = array();
    $branchesQuestion['name'] = 'officeLocation';
    $branchesQuestion['title'] = 'Office Locations';
    $branchesQuestion['type'] = 'radiogroup';
    $choices = array();
    for ($x = 0; $x < count($branches); $x++) {
        $choices[] = array('value' => 'item' . $x, 'text' => $branches[$x]);
    }
    $branchesQuestion['choices'] = $choices;
    $questions[$pageCount]['elements'][] = $branchesQuestion;

    // JobTitle
    //$jobTitleQuestion = array();
    //$jobTitleQuestion['type'] = 'radiogroup';
    //$jobTitleQuestion['name'] = 'jobTitle';
    //$jobTitleQuestion['title'] = 'Job Title';
    //$choices = array();
    //for($x=0;$x<count($jobTitles);$x++){
    //    $choices[] = array('value'=>'item'.$x,'text'=>$jobTitles[$x]);
    //}
    //$jobTitleQuestion['choices'] = $choices;
    //$questions[$pageCount]['elements'][] = $jobTitleQuestion;
}

$questionJson = json_encode(array('completedHtml' => 'Thanks', 'pages' => $questions));
$answerJson = json_encode($answers);

//echo "<pre>"; print_r($answers);print_r($questions); die();
$response_count = count($answers);

include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/survey_analytics.php');
include(__DIR__ . '/views/footer_html.php');
