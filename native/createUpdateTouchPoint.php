<?php
include_once __DIR__.'/head.php';
$pageTitle = "Add|Update Touch Point";

//Data Validation
if (
    ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 1 ||
    ($team = Team::GetTeam($teamid)) == null ||
    ($touchpointid = $_COMPANY->decodeId($_GET['touchpointid'])) < 0
) {
    error_and_exit(HTTP_BAD_REQUEST);
}
$groupid = $team->val('groupid');
$group = Group::GetGroup($groupid);
$pageTitle = gettext("Add a Touch Point");
$touchPoint = null;
$timezone = $_USER->val('timezone') ?: 'UTC';
$parent_taskid = 0;
if (isset($_GET['parent_taskid']) &&   ($decoded_parent_taskid = $_COMPANY->decodeId($_GET['parent_taskid'])) > 0 ) {
    $parent_taskid = $decoded_parent_taskid;
}
if ($touchpointid || $parent_taskid) {
    $touchPoint = $team->getTodoDetail($touchpointid ?: $parent_taskid);
    if ($touchpointid){
        $pageTitle = gettext("Update Touch Point");
    }
    $duedate = $db->covertUTCtoLocalAdvance("Y-m-d", '', $touchPoint['duedate'], $timezone);
    $hour = $db->covertUTCtoLocalAdvance("h", '', $touchPoint['duedate'], $timezone);
    $minutes = $db->covertUTCtoLocalAdvance("i", '', $touchPoint['duedate'], $timezone);
    $period = $db->covertUTCtoLocalAdvance("A", '', $touchPoint['duedate'], $timezone);
    $parent_taskid = $parent_taskid ? $parent_taskid : $touchPoint['parent_taskid'];
}
$fontColors = json_encode(array($group->val('overlaycolor'), $group->val('overlaycolor2')));

include __DIR__ . '/views/createUpdateTouchPoint_html.php';
