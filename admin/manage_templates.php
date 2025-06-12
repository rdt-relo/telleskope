<?php
require_once __DIR__.'/head.php';
$db	= new Hems();
$pagetitle = "Manage Company Teamplates";

//Featch all Template
$templates =	$_COMPANY->getTemplates();
$type = ['','Newsletter','Announcement','Event','Communications','Communications - others'];
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manage_templates.html');
?>
