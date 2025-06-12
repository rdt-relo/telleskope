<?php
ob_start();
session_start();
require_once __DIR__.'/../include/Company.php';
require_once __DIR__.'/../affinity/ViewHelper.php';
$_COMPANY = null; /* @var Company $_COMPANY */
$_USER = null; /* @var User $_USER */
$_ZONE = null; // Note User module is not $_ZONE aware

// The login/user module can only be accessed from https://[dev|prd].teleskope.io or https://[dev|prd].affinities.io domain
// Error out if that is not the case
if (strpos($_SERVER['HTTP_HOST'], 'teleskope.io') === FALSE) {
    error_and_exit('Authentication URL Error!');
}
$allowedURLs = array(
                    'createUpdateRecognition.php',
                    'createUpdateDiscussion.php',
                    'createUpdateActionItem.php',
                    'createUpdateTouchPoint.php',
                    'createUpdateTeamFeedback.php',
                    'sendUdateTeamRoleJoinRequest.php',
                    'createUpdateTouchPointEvent.php',
                    'createUpdateTeam.php',
                    'eventPrePostSurvey.php'
                );

if (in_array(basename($_SERVER['PHP_SELF']),$allowedURLs)){
    $bearerToken =  Teleskope::getBearerToken();
    $_SESSION['bearer_token'] = $bearerToken;
} else {
    $bearerToken = $_SESSION['bearer_token'];
}

if (!$bearerToken){
    error_and_exit('Invalid Authentication Token');
}

[$companyid,$zoneid,$userid] = explode(':',encrypt_decrypt($bearerToken,2));

$_COMPANY = Company::GetCompanyByUrl("https://{$_SERVER['HTTP_HOST']}");

if (!$_COMPANY){
    error_and_exit('ERR-1: Invalid URL Error!');
}

if ($_COMPANY->id() != $companyid){
    error_and_exit('ERR-2: Invalid URL Error!');
}

if (
   (
        $zoneid< 1 ||
        ($_ZONE = $_COMPANY->getZone($zoneid)) === null ||
        $userid <1 ||
        ($_USER= User::GetUser($userid)) === null ||
        ($_USER->val('companyid') != $_COMPANY->id())
    )

){
    error_and_exit('ERR-3: Invalid URL Error!');
}

if (empty($_SESSION['timezone']))
    $_SESSION['timezone'] = $_USER->val('timezone');

// This is a fix the problem of invalid timezone due to outdated ICU (International Components for Unicode)
$_SESSION['timezone'] = TskpTime::OUTDATED_TIMEZONE_MAP[$_SESSION['timezone']] ?? $_SESSION['timezone'];

date_default_timezone_set($_SESSION['timezone'] ?: 'UTC');

function error_and_exit($message, $next = '')
{
    global $_COMPANY;
    $title = 'Error';
    $type = 'error';
    $message = "<h4>{$message}</h4><i>If you feel you got this message in error, then please contact your Teleskope Support Team</i>";
    $next_link = $next;
    include(__DIR__ . '/views/message.html');
    exit();
}

/**
 * This method displays a pretty success message and exits. No control back.
 * @param $message - This message will be displayed to user by message file.
 */
function success_and_exit($message, $next = '', $button_text='Sign In Page')
{
    global $_COMPANY;
    $title = 'Done';
    $type = 'success';
    $message = ''.$message;
    $next_link = $next;

    include(__DIR__ . '/views/message.html');
    exit();
}