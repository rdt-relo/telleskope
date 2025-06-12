<?php
require_once __DIR__.'/head.php';
if (!$_USER->isCompanyAdmin()) {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied)";
    exit();
}

$pagetitle = "Company Security";
$security =  $_COMPANY->getCompanySecurity();

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/security.html');
include(__DIR__ . '/views/footer.html');
?>
