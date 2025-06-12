<?php
//ini_set('session.cookie_samesite', 'Lax'); // Added to set samesite cookie param for affinity session
ob_start();
session_start();
require_once __DIR__.'/../include/Company.php';
$_COMPANY = null; // Explicitly set to null. This is untrusted file so do not set this value
$_USER = null; // Explicitly set to null. This is untrusted file so do not set this value
$_ZONE = null; // Explicitly set to null. This is untrusted file so do not set this value
$_IFRAME_COMPANY = null; // Will be used in the iframe
$_IFRAME_MODULE = null; // Will be set in the file
$db = new Hems();
$error_message = '';
$success_message = '';

// Error out if that is not the case
if (
    !(
        str_ends_with($_SERVER['HTTP_HOST'], '.affinities.io')
        || str_ends_with($_SERVER['HTTP_HOST'], '.officeraven.io')
        || str_ends_with($_SERVER['HTTP_HOST'], '.talentpeak.io')
        || str_ends_with($_SERVER['HTTP_HOST'], '.peoplehero.io')
    )
    ||
    !($_IFRAME_COMPANY = Company::GetCompanyByUrl("https://{$_SERVER['HTTP_HOST']}"))
    ) {
    error_and_exit('Invalid iframe url, error 1404');
}

//if (!isset($_COOKIE['allow_iframe']) ||
//    empty($who = $_IFRAME_COMPANY->decryptString2Array(urldecode($_COOKIE['allow_iframe'])))
//) {
//    request_login_and_exit("https://{$_SERVER['HTTP_HOST']}");
//}


/**
 * This method displays a pretty error to user and exits. No control back.
 * @param $message - This message will be displayed to user by message file.
 */
function error_and_exit($message, $next = '')
{
    $title = 'Error';
    $type = 'error';
    Logger::Log($message, Logger::SEVERITY['WARNING_ERROR']);
    $message = "<h4>{$message}</h4><i>If you feel you got this message in error, then please contact Teleskope Support Team</i>";
    $next_link = $next;

    header("Content-Security-Policy: frame-ancestors https://*"); // Add frame-ancestors required for iFrames.
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
    $message = ''.$message;
    $next_link = $next;

    header("Content-Security-Policy: frame-ancestors https://*"); // Add frame-ancestors required for iFrames.
    include(__DIR__ . '/views/message.html');
    exit();
}

//function request_login_and_exit(string $login_link)
//{
//    header("Content-Security-Policy: frame-ancestors https://*"); // Add frame-ancestors required for iFrames.
//    echo "<html><body><br><p>Please sign in <a href=\"{$login_link}\" target='_blank'> {$login_link}</a> first ... and then <a href=''>reload</a> this section</p></body></html>";
//    exit();
//}