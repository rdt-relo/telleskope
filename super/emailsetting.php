<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageEmailSettings);

$db	= new Hems();

$setting = $_SUPER_ADMIN->super_get("SELECT * FROM `company_email_settings` WHERE `companyid`='{$_SESSION['companyid']}'")[0];

//print_r($setting); die();
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/emailsetting.html');
include(__DIR__ . '/views/footer.html');

?>
