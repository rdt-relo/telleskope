<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::GlobalManageAppVersions);

$db	= new Hems();
unset($_SESSION['companyid']);

$select = "SELECT * FROM `app_versions` ORDER BY isactive DESC";
$rows=$_SUPER_ADMIN->super_get($select);
$status = array('0'=>'Deleted','1'=>'Approved','2'=>'Under Review');


include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/app_versions.html');
include(__DIR__ . '/views/footer.html');
?>
