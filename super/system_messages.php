<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::GlobalSystemMessaging);

$db	= new Hems();
unset($_SESSION['companyid']);


$type = ['','System Maintenance','Product Update','Incident Management', 'Training Update', 'Webinar Update'];
$bg_color = ['grey','darkblue','darkgreen','orange','blueviolet','darkcyan'];
$data = $_SUPER_ADMIN->super_get("SELECT * FROM `system_messages` WHERE `status` !=0 ORDER BY `updatedon` DESC");

include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/system_messages.html');
include(__DIR__ . '/views/footer.html');
?>
