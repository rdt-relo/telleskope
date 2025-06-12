<?php
ob_start();
session_start();
require_once __DIR__.'/../include/Company.php';
$_COMPANY = null; /* @var Company $_COMPANY */
$_USER = null; /* @var User $_USER */
$_ZONE = null; // Note User module is not $_ZONE aware

if (isset($_SESSION['cid'])) {
    $_COMPANY = Company::GetCompany($_SESSION['cid']);
    $logo = $_COMPANY->val('logo');
} else {
    $logo = '../image/teleskope-logo.png';
}

$error_message = '';
$success_message = '';

// The login/user module can only be accessed from https://[dev|prd].teleskope.io or https://[dev|prd].affinities.io domain
// Error out if that is not the case
if (strpos($_SERVER['HTTP_HOST'], 'teleskope.io') === FALSE) {
    error_and_exit('Authentication URL Error!');
}

/**
 * This method displays a pretty error to user and exits. No control back.
 * @param $message - This message will be displayed to user by message file.
 */
function error_and_exit($message, $next = '', $button_text='')
{
    global $_COMPANY;
    $title = '';
    $type = 'error';
    $message = "<h4>{$message}</h4><br><i>If you feel you got this message in error, then please contact your Teleskope Support Team and provide the error code show above.</i>";
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