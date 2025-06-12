<?php
require_once __DIR__.'/head.php';

$db	= new Hems();
unset($_SESSION['companyid']);

//Featch company Information

if (isset($_GET['cid'])) {
	$companyid = base64_decode($_GET['cid']);
	if ($_SESSION['manage_super'] || (isset($_SESSION['manage_companyids']) && in_array($companyid,explode(',',$_SESSION['manage_companyids'])))) {
		$_SESSION['companyid'] = $companyid;
	}
}

$select = "select * from companies as a where companyid='".$_SESSION['companyid']."'";
$row=$_SUPER_ADMIN->super_get($select);


include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/managecompany.html');
include(__DIR__ . '/views/footer.html');
?>
