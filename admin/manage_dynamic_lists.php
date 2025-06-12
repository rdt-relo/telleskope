<?php
require_once __DIR__.'/head.php';
$pagetitle = "Manage Dynamic Lists";
if (!$_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {
   echo "This section is disabled for your account. Please contact your Teleskope account manager if you want to enable this functionality."; die();
}

$lists = DynamicList::GetAllLists();
$status = array('0'=>'Inactive','1'=>"Active",'2'=>'Draft');
$bgcolor = array('0'=>'#fde1e1','1'=>"#ffffff",'2'=>'#ffffce');

// Flag for admin panel table
$isAdminView = true;
// Table headers with width
$tableHeaders = [
   'List Name <small>(uses)</small>' => '20%',
   'List Description' => '44%',
   'Created / Modified By' => '10%',
   'Users' => '9%',
   'Rules' => '9%',
   'Action' => '7%'
];
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manage_dynamic_lists.html');
?>
