<?php
include_once __DIR__.'/head.php';

if (isset($_POST['email'])) {
    $email = $_POST['email'];

    if (!verify_recaptcha()) {
        $error_message = "Incorrect reCAPTCHA, try again";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Your email address is invalid';
    } elseif (($user=User::GetUserByEmail($email)) !== null) {
        global $_ZONE, $_COMPANY;
        $_ZONE = $_COMPANY->getEmptyZone($_SESSION['app_type']);
        if ($user->generateAndSendPasswordResetCode()) {
            $exit_message = 'We emailed you a Password Reset link to your registered email. Please check your mailbox. <br>Password Reset link will expire in 1 hour';
            success_and_exit($exit_message);
        }
    } else {
        $error_message = "Account not found";
    }
}

$title = "Forgot Password";
include __DIR__ . '/views/forgot.html';
