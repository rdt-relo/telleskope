<?php
include_once __DIR__.'/head.php';

if (empty($_SESSION['confirmation'])) {
	header("location: login?rurl={$_SESSION['rurl']}");
	exit();
}

$user = User::GetUser($_SESSION['confirmation']);

if (isset($_POST['confirmationcode'])){
	$confirmationcode = raw2clean($_POST['confirmationcode']);
	$failed_attempts = User::GetFailedLoginAttempts($user->val('email'));

	if ($failed_attempts >= 5) {
	    $user->lock();
		User::DeletePasswordResetCode($user->val('email'));
		User::CreateOrUpdateFailedLoginAttempts($user->val('email'));
		$error_message = 'Account locked due to many failed attempts! <br>Please contact your system administrator to unlock your account.';
	} elseif (!verify_recaptcha()) {
        $error_message = "Incorrect reCAPTCHA, try again";
    } elseif ($user->isVerified()
            || ($user->val('confirmationcode') == $confirmationcode && $user->validateConfirmationCode($confirmationcode))){
        if (empty($user->val('password'))) {
            $_ZONE = $_COMPANY->getEmptyZone($_SESSION['app_type']);
            $user->generateAndSendPasswordResetCode();
            $exit_message = "We have emailed you a password reset link at: <i>".htmlentities($user->val('email'))."</i>, which will be valid for 15 minutes. Please follow the link to reset your password and gain access to your account.";
            success_and_exit($exit_message);
        } else {
            header('location: login?continue=1');
            exit();
        }
	} else {
        User::CreateOrUpdateFailedLoginAttempts($user->val('email'));
		$error_message = 'Invalid confirmation code!';
	}
}

$title = "Email Confirmation";
include __DIR__ . '/views/confirmation.html';
