<?php
require_once __DIR__.'/head.php';
// Authorization Check
// todo
if (!$_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {
	echo "This section is disabled for your account. Please contact your Teleskope account manager if you want to enable this functionality."; die();
}
$pagetitle = "New Dynamic List";
$success = null;
$error = null;
$edit = null;
$id = 0;
$catalog_categories = array_values(UserCatalog::GetAllCatalogCategories());
if(isset($_GET['edit'])){
    $id = $_COMPANY->decodeId($_GET['edit']);
    $edit = DynamicList::GetList($id);
	$edit_criteria = $edit->getCriteria();
	$pagetitle = "Update Dynamic List";
}
// Flag for admin  
$isAdminView = true;
include(__DIR__ . '/views/header.html');
include(__DIR__ . "/../common/add_new_dynamic_list.html");
?>
