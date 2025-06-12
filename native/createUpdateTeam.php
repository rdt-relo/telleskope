<?php
include_once __DIR__.'/head.php';
$pageTitle = "Add|Update Action Item";

//Data Validation

if (
    ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 0 ||
    ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
    ($group = Group::GetGroup($groupid)) == null
) {
    error_and_exit(HTTP_BAD_REQUEST);
}
$chapters= Group::GetChapterList($groupid);
$pageTitle = sprintf(gettext("Create new %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
$selectedHandles = array();
$team = null;
if ($teamid) {
    $pageTitle = sprintf(gettext("Update %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    $team = Team::GetTeam($teamid);
    $selectedHandles = explode(',',$team->val('handleids')??'');
}
$fontColors = $groupid > 0 ? json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2'))) : json_encode(array());
$allRoles = Team::GetProgramTeamRoles($groupid, 1);
$circleRolesCapacity = Team::GetCircleRolesCapacity($groupid,$teamid);
$hashTagHandles = HashtagHandle::GetAllHashTagHandles();

include __DIR__ . '/views/createUpdateTeam_html.php';
