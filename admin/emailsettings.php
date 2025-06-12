<?php
require_once __DIR__.'/head.php';

$pagetitle = "Email Setting";

$disabled = ($_COMPANY->val('in_maintenance') < 2) ? 'disabled' : '';

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/emailsettings.html');
include(__DIR__ . '/views/footer.html');
?>
