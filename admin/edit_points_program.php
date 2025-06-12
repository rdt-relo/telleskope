<?php
require_once __DIR__.'/points_head.php';
$pagetitle = "Edit Points Program";

include(__DIR__ . '/views/header.html');


$pointsProgramId = $_COMPANY->decodeId($_GET['points_program_id']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $program = PointsProgram::GetPointsProgram($pointsProgramId);

    require __DIR__ . '/views/edit_points_program.html';
    exit();
}

$input = $_POST;
$description = $input['description'];
// Add '&nbsp' to all empty <p> tags. This will show empty <p> tags as newlines
$description = preg_replace('#<p></p>#','<p>&nbsp;</p>', $description);

$points_image_url = PointsProgram::UploadPointsImage($_FILES['points_image'] ?? []);

$program = PointsProgram::GetPointsProgram($pointsProgramId);

$program->update(
    title: $input['title'],
    description: $description,
    points_total: $input['points_total'],
    start_date: $input['start_date'] ?? $program->val('start_date'),
    end_date: $input['end_date'] ?? $program->val('end_date'),
    point_conversion_rate: $input['point_conversion_rate'] ?? $program->val('point_conversion_rate'),
    point_conversion_currency: $_COMPANY->getCurrency($_ZONE->id()),
    points_image_url: $points_image_url
);
$msg = "Point program updated successfully";
Http::Redirect('points.php?msg='.$msg.'');
?>
