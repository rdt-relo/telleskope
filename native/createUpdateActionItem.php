<?php
include_once __DIR__.'/head.php';
$pageTitle = "Add|Update Action Item";

//Data Validation

if (
    ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 1 ||
    ($taskid = $_COMPANY->decodeId($_GET['taskid'])) < 0 ||
    ($team = Team::GetTeam($teamid)) == null
) {
    error_and_exit(HTTP_BAD_REQUEST);
}

$groupid = $team->val('groupid');
$group = Group::GetGroup($groupid);
$pageTitle = gettext("Add an action item");
$todo = null;
$finalAssignees = $team->getTeamMembers(0);
$uniqueAssignees = array_intersect_key($finalAssignees, array_unique(array_column($finalAssignees, 'userid')));
$timezone = $_USER->val('timezone') ?: 'UTC';
$parent_taskid = 0;
if (isset($_GET['parent_taskid']) &&   ($decoded_parent_taskid = $_COMPANY->decodeId($_GET['parent_taskid'])) > 0 ) {
    $parent_taskid = $decoded_parent_taskid;
}
if ($taskid || $parent_taskid) {
    $todo = $team->getTodoDetail($taskid ?: $parent_taskid);
    if ($taskid){
        $pageTitle = gettext("Update action item");
    }
    $duedate = '';
    $hour = '';
    $minutes = '';
    $period  = '';
    if ($todo['duedate'] && strtotime($todo['duedate'])> 0 ){
        $duedate = $db->covertUTCtoLocalAdvance("Y-m-d", '', $todo['duedate'], $timezone);
        $hour = $db->covertUTCtoLocalAdvance("h", '', $todo['duedate'], $timezone);
        $minutes = $db->covertUTCtoLocalAdvance("i", '', $todo['duedate'], $timezone);
        $period = $db->covertUTCtoLocalAdvance("A", '', $todo['duedate'], $timezone);
    }
    $parent_taskid = $parent_taskid ? $parent_taskid : $todo['parent_taskid'];

}
$fontColors = json_encode(array($group->val('overlaycolor'), $group->val('overlaycolor2')));

include __DIR__ . '/views/createUpdateActionItem_html.php';
