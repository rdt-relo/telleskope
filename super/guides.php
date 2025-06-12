<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::GlobalManageGuides);

$db	= new Hems();
unset($_SESSION['companyid']);

include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/guides.html');
include(__DIR__ . '/views/footer.html');
?>
