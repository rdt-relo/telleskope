<?php
require_once __DIR__.'/head.php';

$pagetitle = "FAQS";

//Featch all Category data
if (!empty($_GET['page'])){
	$page =	$_GET['page'];
}else{
	$page =	1;	
}
$search = "";
//Get all FAQS
$rows = $db->get("SELECT `faqid`, `question`, `answer` FROM `faqsadmin` WHERE `isactive`='1' order by faqid asc ");

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/faq.html');
?>
