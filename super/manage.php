<?php
require_once __DIR__.'/head.php';

$db	= new Hems();
unset($_SESSION['companyid']);
if (!$_SESSION['manage_accounts']) {
	echo "Method not allowed";
	exit();
}

if (!empty($_GET['page'])){
	$page =	$_GET['page'];
}else{
	$page =	1;	
}

//Featch All Company
$companyFilter = '';
if (!$_SESSION['manage_super']) {
	$companyFilter = 'AND 0';
	if (!empty($_SESSION['manage_companyids'])) {
		$companyFilter = 'AND `companyid` in ('.$_SESSION['manage_companyids'].')';
	}
}

$select = "select * from companies where isactive!=3 {$companyFilter} order by createdon desc";
$rows=$_SUPER_ADMIN->super_get($select);


if (isset($_POST['submit'])){
	$email = raw2clean($_POST['email']);
	//$mail = CompnaySingupLInk($email);
	$message= "Company Registration link sent Successfully.";
}

include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/manage.html');
include(__DIR__ . '/views/footer.html');
?>
