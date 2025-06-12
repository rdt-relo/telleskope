<?php
require_once __DIR__.'/head.php';
// Authorization Check
if (!$_USER->canManageZoneBudget()) {
    header("HTTP/1.1 403 Forbidden (Access Denied)");
    echo "403 Forbidden (Access Denied)";
    exit();
}

$pagetitle = "Manage Expense Types";
$data  = $db->get("SELECT * FROM budget_charge_codes WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `isactive`=1");

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/budget_charge_codes.html');
?>
