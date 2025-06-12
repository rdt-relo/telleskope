<?php
require_once __DIR__.'/head.php';
$pagetitle = "Reporting";

// For Budget Reports
$date = $_USER->getLocalDateNow();
$year = Session::GetInstance()->budget_year ?: Budget2::GetBudgetYearIdByDate($date);

$budgetYears = Budget2::GetCompanyBudgetYears();
$companyBudget = Budget2::GetBudget($year);
$groups = $db->get("SELECT `groupid`, `companyid`, `regionid`, `groupname`, `overlaycolor` FROM `groups` WHERE `isactive`='1' and `companyid`={$_COMPANY->id()} AND zoneid={$_ZONE->id()} ORDER BY groupname ASC");

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/reports.html');
?>
