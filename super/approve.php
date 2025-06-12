<?php
require_once __DIR__.'/head.php';

$db	= new Hems();

//Featch All Company From Company Table

if (!empty($_GET['page'])){
	$page =	(int)$_GET['page'];
}else{
	$page =	1;	
}

if (isset($_GET['cid'])){
	
	$companyid 	= 	(int)base64_decode($_GET['cid']);
    $update = "update companies set status=1, user_lifecycle_settings='{\"allow_delete\":true}', approvedate=now() where companyid={$companyid}";
    $query1 = $_SUPER_ADMIN->super_update($update);
	if ($query1){
        // Add default event types.
        foreach (Event::SYS_EVENTTYPES as $k => $v) {
            $_SUPER_ADMIN->super_insert("INSERT INTO event_type (sys_eventtype, companyid, type, modifiedon, isactive) VALUES ('{$k}','{$companyid}','{$v}',now(),1)");
        }
		$_SESSION['updated'] = time();
		header("location:manage");
		
	}else{
		$_SESSION['ERROR'] = time();
		header("location:manage");
	}
}
