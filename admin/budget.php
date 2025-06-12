<?php
require_once __DIR__.'/head.php';
$pagetitle = "Budget";

// Authorization Check
if (!$_USER->canManageZoneBudget()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	echo "403 Forbidden (Access Denied)";
	exit();
}

$date = $_USER->getLocalDateNow();
$year = Session::GetInstance()->budget_year ?: Budget2::GetBudgetYearIdByDate($date);

$budgetYears = Budget2::GetCompanyBudgetYears();
$companyBudget = Budget2::GetBudget($year);
$groups = $db->get("SELECT `groupid`, `companyid`, `regionid`, `groupname`,`groupname_short`, `overlaycolor` FROM `groups` WHERE `isactive`='1' and `companyid`={$_COMPANY->id()} AND zoneid={$_ZONE->id()} ORDER BY groupname ASC");


include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/budget.html');
?>
