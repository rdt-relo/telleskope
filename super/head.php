<?php
ob_start();
session_name('super');
session_start();

require_once __DIR__.'/../include/Company.php';
require_once __DIR__.'/../include/SuperAdminFunctions.php';
require_once __DIR__ . '/auth/Auth.php';

$_SUPER_ADMIN = new SuperAdminFunctions();

if(strpos(BASEURL,"://{$_SERVER['HTTP_HOST']}") == false) {
	http_response_code(404);
	die();
}

// Super sessions expire 30 minutes after use
if (isset($_SESSION['l_a']) && (time() - (int)@$_SESSION['l_a'])>1800){
	session_unset();
	session_destroy();
	session_start();
} else {
	$_SESSION['l_a'] = time();
}

isset($_SESSION['timezone']) ? date_default_timezone_set($_SESSION['timezone']) : date_default_timezone_set("UTC");

$superid = "";


if (!defined("INDEX_PAGE") && !isset($_SESSION['superid'])){
	// Indexpage not defined & superid not in session
	session_unset();
	session_destroy();
	header("location:index");
	exit;
} else {
	if(isset($_SESSION['superid'])) {
		// Indexpage defined & superid in session
		// Indexpage not defined & superid in session
		$superid = $_SESSION['superid'];
        $now_time = time();
        if (!isset($_SESSION['session_reload_after']) ||  $_SESSION['session_reload_after'] < $now_time) {
            $super_admin = $_SUPER_ADMIN->getSuperAdmin($superid);
            if ($super_admin['is_expired'] || $super_admin['is_blocked'] || $super_admin['isactive'] != 1) {
                session_unset();
                session_destroy();
                header("location:index");
                exit;
            }
            $_SESSION['manage_companyids'] = implode(',', $super_admin['manage_companyids']);
            $_SESSION['manage_super'] = $super_admin['is_super_super_admin'];
            $_SESSION['manage_accounts'] = $super_admin['is_super_super_admin'] || count($super_admin['manage_companyids']) > 0;
            $_SESSION['permissions'] = json_encode($super_admin['permissions']);
            $_SESSION['session_reload_after'] = $now_time + 180;
        }
		Auth::Init();
	} // else Indexpage defined & superid not in session
}

function cleanMarkup($input) {
	$allowed_tags = /** @lang text */
		"<p><strong><img><ol><ul><li><a><hr><br><em><s><blockquote><span><u><i><del><figure><figcaption>";
	return strip_tags($input,$allowed_tags);
}
