<?php
require_once __DIR__.'/head.php';

$pagetitle = "Update Comapny Information";

if(isset($_POST['submit'])){
	$companyname  		= raw2clean($_POST['company']);
	$contactperson	= raw2clean($_POST['contactperson']);
	//$email	    	= raw2clean($_POST['email']);
	$contact   		= raw2clean($_POST['contact']);
	$address 		= raw2clean($_POST['address']);
	$city   		= raw2clean($_POST['city']);
	$state   		= raw2clean($_POST['state']);
	$country   		= raw2clean($_POST['country']);
	$zipcode   		= raw2clean($_POST['zipcode']);
	
	$_COMPANY->updateCompanyInformation($companyname, $contactperson, $contact, $address, $city, $state, $country, $zipcode);
	// Reload company by invalidating cache and reloading it from database
	$_COMPANY = Company::GetCompany($_COMPANY->id(), true);
	$_SESSION['updated'] = time();

	Http::Redirect("manage_contacts");
} else {
	$sql_event="SELECT * FROM companies WHERE companyid='{$_COMPANY->id()}'";
	$res_event=$db->get($sql_event,"assoc");
	$row_event=count($res_event);
}
include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/editCompany.html');
include(__DIR__ . '/views/footer.html');
?>
