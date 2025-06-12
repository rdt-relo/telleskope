<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageDomains);

$db	= new Hems();
$cid = (int)$_SESSION['companyid'];

$rows=$_SUPER_ADMIN->super_get("SELECT * FROM `company_email_domains` WHERE companyid={$cid}");

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manage_domains.html');
include(__DIR__ . '/views/footer.html');
?>
