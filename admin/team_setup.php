<?php

require_once __DIR__ . '/head.php';

if (!$_USER->canManageAffinitiesContent()) {
    Http::Forbidden();
}

if (!isset($_GET['groupid']) || ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 || ($group = Group::GetGroup($groupid)) === null ) {
	header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
	exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enable_teams'])) {
	$enableDisableTeams = ($_POST['enable_teams'] === '1');

    /** Disabled on 04/04/2024 to allow teams to be disabled even for Talent Peak - Mckesson Canada Cafe Usecase
    if (
        ($_ZONE->val('app_type') === 'talentpeak')
        && !$enableDisableTeams
     ) {
        AjaxResponse::SuccessAndExit_STRING(
            0,
            '',
            gettext('Teams cannot be disabled for Talentpeak Programs'),
            gettext('Error')
        );
    }
     **/

    $success = $group->activateTeamsModule($enableDisableTeams);
    if ($success) {
        AjaxResponse::SuccessAndExit_STRING(1, '', 'Successfully updated', gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', 'Unable to update, please try again later', gettext('Error'));
    }
}

$pagetitle = "Manage {$group->val('groupname')} {$_COMPANY->getAppCustomization()['group']['name-short']}  {$_COMPANY->getAppCustomization()['teams']['name']} Setting";

$hiddenTabs = $group->getHiddenProgramTabSetting();
$surveyDownloadSetting = $group->getSurveyDownloadSetting();

require __DIR__ . '/views/header.html';
require __DIR__ . '/views/team_setup.html.php';
