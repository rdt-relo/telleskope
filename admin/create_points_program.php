<?php
require_once __DIR__ . '/points_head.php';
$pagetitle = "Create Points Program";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    include(__DIR__ . '/views/header.html');
    include(__DIR__ . '/views/create_points_program.html');
    exit();
}

$input = $_POST;

$description = $input['description'];
// Add '&nbsp' to all empty <p> tags. This will show empty <p> tags as newlines
$description = preg_replace('#<p></p>#','<p>&nbsp;</p>', $description);

$points_image_url = PointsProgram::UploadPointsImage($_FILES['points_image'] ?? []);

PointsProgram::Create(
    title: $input['title'],
    description: $description,
    points_total: $input['points_total'],
    start_date: $input['start_date'],
    end_date: $input['end_date'],
    point_conversion_rate: $input['point_conversion_rate'] ?? 0,
    point_conversion_currency: $_COMPANY->getCurrency($_ZONE->id()),
    points_image_url: $points_image_url
);
$msg = "Point program created successfully";
Http::Redirect('points.php?msg='.$msg.'');
?>
