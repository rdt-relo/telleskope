<?php
require_once __DIR__.'/head.php';
if (!$_USER->isCompanyAdmin()) {
	header(HTTP_FORBIDDEN);
	echo "403 Forbidden (Access Denied)";
	exit();
}

$pagetitle = "Manage User Catalog Categories";
$catalogs = UserCatalog::GetAllCatalogCategoriesAsRows(null);

// Creating a map for zoneid => zonename for all the zones in company 
$zoneNameMap = [];
foreach ($_COMPANY->getZones() as $zone) {
		$zoneNameMap[$zone['zoneid']] = $zone['zonename'];
}
include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/user_catalogs.html');
include(__DIR__ . '/views/footer.html');
?> 