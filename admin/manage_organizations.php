<?php
require_once __DIR__.'/head.php';
if (!$_USER->isCompanyAdmin()) {
	header(HTTP_FORBIDDEN);
	echo "403 Forbidden (Access Denied)";
	exit();
}

$pagetitle = "Manage Organizations";
//Fetching from server side in datatable
// $organizations = Organization::GetOrgDataBySearchTerm();

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/manage_organizations.html');
include(__DIR__ . '/views/footer.html');
?>
