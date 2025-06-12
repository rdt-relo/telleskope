<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageZones);

$db	= new Hems();

$zones = $_SUPER_ADMIN->super_get("SELECT * FROM `company_zones` WHERE `companyid`='{$_SESSION['companyid']}' AND isactive!='0'  ORDER BY app_type");
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manage_zones.html');
include(__DIR__ . '/views/footer.html');

?>
