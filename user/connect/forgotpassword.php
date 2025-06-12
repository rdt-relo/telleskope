<?php
include_once __DIR__.'/../head.php';
include_once __DIR__.'/../../include/UserConnect.php';

if (empty($_SESSION['app_type'])){
    header("Location: ../../user/login.php");
}

if (isset($_POST['email'])) {
    $email = raw2clean($_POST['email']);

    if (!verify_recaptcha()) {
        $error_message = "Incorrect reCAPTCHA, try again";
    } else {
        if (($connectUser = UserConnect::GetConnectUserByEmail($email)) !== null) {
            $connectUser->generateAndSendPasswordResetCode($_SESSION['app_type']);
        }
        $exit_message = 'If the email address <i>(' . htmlentities($email) . ')</i> matches our database, a password reset link will be emailed. <br>Password reset link will expire in 15 minutes';
        success_and_exit($exit_message);
    }
}

$title = "Reset Password";
include __DIR__ . '/views/forgotpassword.html';
