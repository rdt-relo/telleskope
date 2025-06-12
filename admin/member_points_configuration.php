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

$pageTitle = gettext('Member Points Configuration');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $memberPointsConfiguration = $pointsProgram->getMemberPointsConfiguration();
    require __DIR__ . '/views/member_points_configuration.html';
    exit();
}

$input = $_POST;
unset($input['csrf_token']);

$pointsProgram = PointsProgram::GetPointsProgram($pointsProgramId);
foreach ($input as $pointsTriggerKey => $pointsEarned) {
    $pointsProgram->updateMemberPointsConfigurationByKey($pointsTriggerKey, $pointsEarned);
}

$pointsProgram->expireRedisCache('MEMBER_POINTS_CONFIG');

$msg = "Daily Earnings Updated Successfully.";
Http::Redirect("member_points_configuration.php?points_program_id={$_GET['points_program_id']}&msg={$msg}");
