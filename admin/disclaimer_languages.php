<?php
require_once __DIR__.'/head.php';
$pagetitle = "Disclaimers";

if (!$_COMPANY->getAppCustomization()['disclaimer_consent']['enabled']) {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied)";
    exit();
}

$disclaimerid = 0;
$allowedLanguages = $_COMPANY->getValidLanguages();
if (isset($_GET['disclaimerid'])){
    $disclaimerid = $_COMPANY->decodeId($_GET['disclaimerid']);
    $editDisclaimerlan = Disclaimer::GetDisclaimerById($disclaimerid);
    $lan = $editDisclaimerlan->val('disclaimer');
    $lanArray = json_decode($lan, true);
    $encodedId  = $_GET['disclaimerid'];   
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/disclaimer_languages.html');

