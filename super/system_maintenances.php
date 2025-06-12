<?php
require_once __DIR__.'/head.php';

$db	= new Hems();
unset($_SESSION['companyid']);

if (!$_SESSION['manage_super']) {
    exit();
}

include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/system_maintenances.html');
?>
