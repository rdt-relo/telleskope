<?php
ini_set('session.cookie_samesite', 'Lax'); // Added to set samesite cookie param for affinity session

ob_start();
session_start();
require_once __DIR__.'/../include/Company.php';
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
    && (strpos($_SERVER['HTTP_HOST'], 'talentpeak.io') === FALSE )
    ) {
    error_and_exit('Invalid URL (1)');
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