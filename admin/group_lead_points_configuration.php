<?php

require_once __DIR__ . '/points_head.php';
include(__DIR__ . '/views/header.html');

if (!isset($_GET['points_program_id']) ||
    ($pointsProgramId = $_COMPANY->decodeId($_GET['points_program_id'])) < 1 ||
    ($pointsProgram = PointsProgram::GetPointsProgram($pointsProgramId)) == null
) {
    header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
    exit();
}

$pageTitle = gettext('Group Lead Points Configuration');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $groupLeadPointsConfiguration = $pointsProgram->getGroupLeadPointsConfiguration();

    $groupLeadPointsConfiguration = Arr::GroupBy($groupLeadPointsConfiguration, 'sys_leadtype');

    require __DIR__ . '/views/group_lead_points_configuration.html';
    exit();
}

$input = $_POST;
unset($input['csrf_token']);

foreach ($input as $groupLeadTypeId => $dailyEarnings) {
    $pointsProgram->updateGroupLeadPointsConfiguration($groupLeadTypeId, $dailyEarnings);
}
$msg = "Group Leader Configuration Updated Successfully.";
Http::Redirect("group_lead_points_configuration?points_program_id={$_GET['points_program_id']}&msg=$msg");
