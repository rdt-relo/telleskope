<?php
include_once __DIR__.'/head.php';
$pageTitle = "Add|Update Team Feedback";

//Data Validation


if (
    ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 0 ||
    ($feedbackid = $_COMPANY->decodeId($_GET['feedbackid'])) < 0 ||
    ($team = Team::GetTeam($teamid)) == null
) {
    error_and_exit(HTTP_BAD_REQUEST);
}

$groupid = $team->val('groupid');
$group = Group::GetGroup($groupid);
$pageTitle = gettext("Feedback");
$todo = null;
$finalAssignees = $team->getTeamMembers(0);
$uniqueAssignees = array_intersect_key($finalAssignees, array_unique(array_column($finalAssignees, 'userid')));

if ($feedbackid) {
    $todo = $team->getTodoDetail($feedbackid);
    $pageTitle = gettext("Update Feedback");
}
$fontColors = json_encode(array($group->val('overlaycolor'), $group->val('overlaycolor2')));

include __DIR__ . '/views/createUpdateTeamFeedback_html.php';
