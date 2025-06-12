<?php
require_once __DIR__.'/head.php';

$db	= new Hems();
unset($_SESSION['companyid']);
//Featch All Company From Company Table

if (!$_SESSION['manage_super']) {
    exit();
}

    $select = "select count(1) as count from companies where status='1' and isactive='1'";
$client_count = $_SUPER_ADMIN->super_get($select)[0]['count'];

$select1 = "select count(1) as count from users where isactive='1'";
$user_count=$_SUPER_ADMIN->super_get($select1)[0]['count'];



include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/dashboard.html');
include(__DIR__ . '/views/footer.html');
?>
