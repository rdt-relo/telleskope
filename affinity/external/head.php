<?php

// TEMPORARY FIX TO DISALBE EXTERNAL EVENTS
header('HTTP/1.1 401 Unauthorized.... this feature is not available.');
exit;
// END OF TEMPORARY FIX

ini_set('session.cookie_samesite', 'Lax'); // Added to set samesite cookie param for affinity session

ob_start();
session_start();
require_once __DIR__.'/../../include/Company.php';
$db  = new Hems();
$_COMPANY = null; // Explicitly set to null. This is untrusted file so do not set this value
$_USER = null; // Explicitly set to null. This is untrusted file so do not set this value
$_ZONE = null; // Explicitly set to null. This is untrusted file so do not set this value

$error_message = '';
$success_message = '';
$logo = '../image/teleskope-logo.png'; // Gets updated in header.html to company logo

// Survey module can only be accessed from https://[dev|prd].teleskope.io domain
// Error out if that is not the case
if ((strpos($_SERVER['HTTP_HOST'], 'affinities.io') === FALSE )
    && (strpos($_SERVER['HTTP_HOST'], 'officeraven.io') === FALSE )
    && (strpos($_SERVER['HTTP_HOST'], 'peoplehero.io') === FALSE )
    && (strpos($_SERVER['HTTP_HOST'], 'talentpeak.io') === FALSE )
    && (strpos($_SERVER['HTTP_HOST'], 'peoplehero.io') === FALSE )
    ) {
    error_and_exit('Invalid URL (1)');
}

$allowedURLs = array(
    'event.php'
);

if (in_array(basename($_SERVER['PHP_SELF']),$allowedURLs)){
    $params = json_decode(aes_encrypt($_GET['params'], TELESKOPE_USERAUTH_API_KEY, "u7KD33py3JsrPPfWCilxOxojsDDq0D3M", true),true);
    if (empty($params)){
        error_and_exit("Event link is not valid.");
        exit();
    }
    $companyid = $params['companyid'];
    $eventid = $params['eventid'];
    $_SESSION['external_companyid'] = $companyid;
    $_SESSION['external_eventid'] = $eventid;

} else {
    $companyid = $_SESSION['external_companyid'];
    $eventid = $_SESSION['external_eventid'];
}


if (empty($companyid) || empty($eventid)){
    error_and_exit("Event link is not valid.");
    exit();
}
$_COMPANY = Company::GetCompany($companyid); // Temporary
if (!$_COMPANY){
    error_and_exit("Event link is not valid.");
    exit();
}


/**
 * This method displays a pretty error to user and exits. No control back.
 * @param $message - This message will be displayed to user by message file.
 */
function error_and_exit($message, $next = '')
{
    $title = 'Error';
    $type = 'error';
    $message = "<h4>{$message}</h4><i>If you feel you got this message in error, then please contact Teleskope Support Team</i>";
    $next_link = $next;
    include(__DIR__ . '/views/message.html');
    exit();
}

/**
 * This method displays a pretty success message and exits. No control back.
 * @param $message - This message will be displayed to user by message file.
 */
function success_and_exit($message, $type='success', $next = '')
{
    $title = '';
    $type = $type;
    $message = ''.$message;
    $next_link = $next;
    include(__DIR__ . '/views/message.html');
    exit();
}