<?php
require_once __DIR__.'/points_head.php';
$pagetitle = "Points Program";

if (isset($_GET['selected_points_program_id']) && $_COMPANY->decodeId($_GET['selected_points_program_id']) > 0) {
    $_SESSION['selected_points_program_id'] = $_COMPANY->decodeId($_GET['selected_points_program_id']);
}

$programs = PointsProgram::All();

$selectedPointsProgramId = $_SESSION['selected_points_program_id'] ?? $programs[0]['points_program_id'] ?? null;
$leaderboard = [];
$selectedProgram = null;
if ($selectedPointsProgramId) {
    $selectedProgram = Arr::SearchColumnReturnRow($programs, $selectedPointsProgramId ?? 0, 'points_program_id');
    $leaderboard = PointsProgram::GetLeaderboard($selectedPointsProgramId);
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/points.html');
?>
