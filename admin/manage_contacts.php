<?php
require_once __DIR__.'/head.php';
if (!$_USER->isCompanyAdmin()) {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied)";
    exit();
}

$pagetitle = "Company Information";
$business_contact =  $_COMPANY->GetCompanyContact('Business');
$technical_contact =  $_COMPANY->GetCompanyContact('Technical');
$security_contact =  $_COMPANY->GetCompanyContact('Security');


include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/manage_contacts.html');
include(__DIR__ . '/views/footer.html');
?>
