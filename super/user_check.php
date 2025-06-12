<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::CheckUser);

$db	= new Hems();
$companyid = $_SESSION['companyid'];
$search_by = 'search_by_email';
$selected_group = null;
$selected_group_id = 0;

if (!$_SESSION['manage_super'] && !isset($_SESSION['manage_companyids']) && !in_array(intval($_SESSION['companyid']),explode(',',$_SESSION['manage_companyids']))) {
    exit();
}

{
    if (isset($_POST) && isset($_POST["user_search"])) {

        require_once __DIR__ . '/../include/Company.php'; //This file internally calls dbfunctions, Company etc.
        require_once __DIR__ . '/../include/User.php'; //This file internally calls dbfunctions, User etc.

        global $_COMPANY;
        global $_ZONE;

        $user_search = raw2clean($_POST["user_search"]);
        $search_by = empty($_POST['search_by']) ? 'search_by_email' : $_POST['search_by'];
        $searched_user = null;

        $_COMPANY = Company::GetCompany(intval($_SESSION['companyid']));
        if ($search_by == 'search_by_externalid') {
            $searched_user = User::GetUserByExternalId($user_search, true);
        } elseif ($search_by == 'search_by_aad_oid') {
            $searched_user = User::GetUserByAadOid($user_search, true);
        } elseif ($search_by == 'search_by_externalname') {
            $searched_user = User::GetUserByExternalUserName($user_search, true);
        } elseif ($search_by == 'search_by_userid') {
            $searched_user = User::GetUser($user_search, true);
        } elseif ($search_by == 'search_by_external_email') {
            $searched_user = User::GetUserByExternalEmail($user_search, true);
        } else {
            $search_by == 'search_by_email';
            $searched_user = User::GetUserByEmail($user_search, true);
        }

        if (isset($_POST["zone_selector"])) {   // if ZONE is in $_POST  - select it

            $zone_id = (int)($_POST["zone_selector"]);
            $_ZONE = $_COMPANY->getZone($zone_id);

        } else {   // else get all company zones and pick the first one
            $zones_array = $_COMPANY->getZones();
            if (!empty($zones_array)) {
                $_ZONE = $_COMPANY->getZone(array_key_first($zones_array));
            }
        }
        
        if (isset($_POST["group_selector"])) {   // if ZONE is in $_POST  - select it
            $selected_group_id = (int)($_POST["group_selector"]);
        }

        if (isset($_POST["reset_user_id"]) &&
            ($_POST["reset_user_id"] == $searched_user->id()) &&
            ($_SESSION['rand_tok'] == $_POST['rand_tok']) // Want to make sure the call was generated within the same session
        ) {   // if ZONE is in $_POST  - select it
            $searched_user->updateExternalId(null);
            $searched_user = User::GetUser($searched_user->id()); // Reload the user
            $_SESSION['rand_tok'] = null;
            Logger::Log("Super Admin su-{$superid} reset externalid for user {$_COMPANY->id()}|{$searched_user->id()}", Logger::SEVERITY['INFO']);
        }

        if (isset($_POST["aad_oid"]) && ($_SESSION['rand_tok'] == $_POST['rand_tok'])) {   
            $searched_user->resetAadOid();
            $searched_user = User::GetUser($searched_user->id()); // Reload the user
            $_SESSION['rand_tok'] = null;
            Logger::Log("Super Admin su-{$superid} reset aad_oid for user {$_COMPANY->id()}|{$searched_user->id()}", Logger::SEVERITY['INFO']);
        }
    }

    $_SESSION['rand_tok'] = $_SESSION['rand_tok'] ?: generateRandomToken(8);

    include(__DIR__ . '/views/header.html');
    include(__DIR__ . '/views/user_check.html');
    include(__DIR__ . '/views/footer.html');

    $_COMPANY = null;
    $_ZONE = null;
    $searched_user = null;
}
?>
