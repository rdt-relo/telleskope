<?php
require_once __DIR__.'/head.php';
require_once __DIR__ . '/../include/Company.php'; //This file internally calls dbfunctions, Company etc.
require_once __DIR__ . '/../include/User.php'; //This file internally calls dbfunctions, User etc.

Auth::CheckPermission(Permission::MergeUser);

$db	= new Hems();
$companyid = $_SESSION['companyid'];
$rand_tok = generateRandomToken(8);
$search_by = 'search_by_email';
$_SESSION['rand_tok'] = $rand_tok;

if (!$_SESSION['manage_super'] && !isset($_SESSION['manage_companyids']) && !in_array(intval($_SESSION['companyid']),explode(',',$_SESSION['manage_companyids']))) {
    exit();
}
$user1 = null;
$user2  = null;
$error = null;
$finalMessage = null;
if (isset($_POST["proccedToMerge"])) { 
    global $_COMPANY;
    global $_ZONE;
    $_COMPANY = Company::GetCompany(intval($_SESSION['companyid']));
    $userid1 = raw2clean($_POST["userid1"]);
    $userid2 = raw2clean($_POST["userid2"]);

    if ($userid1 == $userid2){
        $error  = "UserId 1 and UserId 2 can't be same;";
    }
    $user1 = User::GetUser($userid1, true);

    if (!$error){
        if (!$user1){
            $error  = "No user found with UserId 1";
        }
        if (!$error){
            $user2 = User::GetUser($userid2, true);
            if (!$user2){
                $error  = "No user found with UserId 2";
            }
        }
    }
}
$mainUser = null;
$extraUser = null;
if (isset($_POST["startMerging"])) { 
    global $_COMPANY;
    global $_ZONE;
    $_COMPANY = Company::GetCompany(intval($_SESSION['companyid']));
    $userid1 = raw2clean($_POST["userid1"]);
    $userid2 = raw2clean($_POST["userid2"]);
    $user1 = User::GetUser($userid1);
    $user2 = User::GetUser($userid2);
    if ($user1->val('createdon') < $user2->val('createdon')){
        $mainUser = $user1;
        $extraUser = $user2;
    } else {
        $mainUser = $user2;
        $extraUser = $user1;
    }


    if ($mainUser){
        $finalMessage =  $extraUser->id(). ' userid will be merged with '.$mainUser->id().' userid. Are you sure to proceed?';
    } else {
        $error  = "Merging process stoped because external_id and aad_oid criteria not matched";
    }
}

    include(__DIR__ . '/views/header.html');
    include(__DIR__ . '/views/merge_user.html');
    include(__DIR__ . '/views/footer.html');

    $_COMPANY = null;
    $_ZONE = null;

?>
