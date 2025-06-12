<?php
//define ("INDEX_PAGE",1); fixed issue #1335
require_once __DIR__.'/head.php';

if (isset($_GET['group']) && !empty($_GET['group'])){
	$group = $_GET['group'];
	$_SESSION['goto']= $group;
	if ($_USER->id()){
		Http::Redirect("detail?group={$group}");
	}else{
		Http::Redirect("index");

	}
}else{
	Http::Redirect("index");
}
//print_r($rows);exit;
//include('html/header_html.php');
//include('html/goto.html');

?>
