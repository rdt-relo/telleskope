<?php
include_once __DIR__.'/head.php';
$pageTitle = "Add|Update Action Item";

//Data Validation
if (
    ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
    ($group = Group::GetGroup($groupid)) === null ||
    ($roleid = $_COMPANY->decodeId($_GET['roleid'])) < 0 ||
    ($preselected = $_COMPANY->decodeId($_GET['preselected'])) < 0
) {
    error_and_exit(HTTP_BAD_REQUEST);
}

$id = $roleid ? $roleid : $preselected;
$preSelectedRole = Team::GetTeamRoleType($id);

if (!empty($preSelectedRole['registration_start_date']) && $preSelectedRole['registration_start_date'] > date('Y-m-d')){
    AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Sorry, registration is unavailable at this time, please check back after %s'),$preSelectedRole['registration_start_date'] ), '');
}
if (!empty($preSelectedRole['registration_end_date']) && $preSelectedRole['registration_end_date'] < date('Y-m-d')){
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Sorry, the registration is closed and we are no longer accepting registration requests at this time'), '');
}

$all_chapters= Group::GetChapterList($groupid);
$requested_chapters = [];
$encodedRequestedChapters = [];
$questionJson = $group->getTeamMatchingAlgorithmAttributes();
$isQuestionAvailable = count($questionJson);
$questionJson = json_encode($questionJson);
$allRoles = Team::GetProgramTeamRoles($groupid, 1);
$joinRequest = Team::GetRequestDetail($groupid,$roleid);
$modalTitle = sprintf(gettext('Thank you for your interest in being a <b>%s</b>!'),($preSelectedRole['type']));
$submitBtn = gettext("Submit"); //gettext("Send Request");
if (!empty($joinRequest)) {
    $myRequestedRoles =array();
    $modalTitle = sprintf(gettext('Update your %s registration'),strtolower($preSelectedRole['type']));
    $submitBtn = gettext("Update Registration"); //gettext("Update Request");
    $requested_chapters = explode(',',$joinRequest['chapterids']);
} else {
    $myRequestedRoles = array_column(Team::GetUserJoinRequests($groupid,0,0),'roleid');
    $requested_chapters = array_filter(explode(',', $_USER->getFollowedGroupChapterAsCSV($groupid) ?: ''));
}

// Auto Assign Chapter
$autoAssign = null;
if (count($requested_chapters)){
    foreach($requested_chapters as $chapterId){
        $encodedRequestedChapters[] = $_COMPANY->encodeId($chapterId);
    }

}
$roleCapacityTitle = sprintf(gettext("Please select a maximum number of %ss that you would like to be part of in the %s role."), $_COMPANY->getAppCustomization()['teams']['name'], $preSelectedRole['type']);

$chapterSelectionSetting = $group->getTeamRoleRequestChapterSelectionSetting();

include __DIR__ . '/views/sendUdateTeamRoleJoinRequest_html.php';
