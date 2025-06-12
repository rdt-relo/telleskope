<?php
require_once __DIR__.'/head.php';

$pagetitle = "Update Branding";

//print '<pre>'; print_r($data); exit;

$data = $_COMPANY->getHotlinks(false);
$footerLinks = $_COMPANY->getFooterLinks(true);

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/branding.html');

?>
