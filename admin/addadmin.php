<?php
require_once __DIR__.'/head.php';
global $_COMPANY, $_ZONE;

$pagetitle = ($_GET['context'] == 'zone') ? "Add Zone Admin" : "Add Global Admin";

// Authorization Check - works for both zone admin and Global admin

if (!$_USER->isCompanyAdmin() && !$_USER->isZoneAdmin($_ZONE->id())) {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied)";
    exit();
}


if(isset($_POST['adminassign'])){
	if (!isset($_POST['userid']) || ($adminassign_id = $_COMPANY->decodeId($_POST['userid'])) < 1) {
		header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
		exit();
	}

    $new_admin = User::GetUser($adminassign_id);
	if ($_GET['context']== 'zone'){ // Zone Admin
        $manage_budget = isset($_POST['manageZoneBudget']) ? 1 : 0;
        $manage_approvers = isset($_POST['manageZoneSpeaker']) ? 1 : 0;
        $can_view_reports = isset($_POST['can_view_reports']) ? 1 : 0;
        $update = $new_admin->assignAdminPermissions($_ZONE->id(), $manage_budget, $manage_approvers, $can_view_reports);
        if ($update) {
            Http::Redirect("manageZoneAdmin");
        } else {
            $error = 'Unable to add the user as a Zone Administrator';
            $_SESSION['error'] = time();
        }
	} else { //Global Admin
        $update = $new_admin->assignAdminPermissions(0,0,0,1);
        if ($update) {
            Http::Redirect("manageadmin");
        } else {
            $error = 'Unable to add the user as a Global Administrator';
            $_SESSION['error'] = time();
        }
    }
}

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/addadmin.html');
include(__DIR__ . '/views/footer.html');
