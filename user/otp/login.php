<?php
include_once __DIR__ . '/../head.php';

$title = "Teleskope | OTP login";
$from_email_label = $_SESSION['otp_settings']['otp_from_email_label'] ?? '';
$application_name = $_SESSION['otp_settings']['application_name'] ?? '';
$otp_identities = $_SESSION['otp_settings']['login_identities'] ?? [];
$otp_identities_labels = array_map(function ($otp_identity) {
            return ['email' => 'Business Email', 'external_email' => 'Personal Email', 'phone_number'=>'Mobile Phone'][$otp_identity];
        }, $otp_identities);

$otp_identities_label = Arr::NaturalLanguageJoin($otp_identities_labels, 'or');

if (empty($_SESSION['app_type'])){
    header("Location: ../../user/login.php");
}

$message = '';
if (isset($_GET['regenerate'])) {
    clear_otp_session('otp');
    header("location: login");
    exit();
}
if (!empty($_POST['login_otp_identity'])) {
    $login_otp_identity = $_POST['login_otp_identity'];
    if (!verify_recaptcha()) {
        $error_message = "Incorrect reCAPTCHA, try again";
    } elseif (!filter_var($login_otp_identity, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Your email address is invalid';
    } else {
        $login_user = null;
        // First try external email
        if (in_array('external_email', $otp_identities)) {
            $login_user = User::GetUserByExternalEmail($login_otp_identity);
        }

        //
        if (!$login_user && in_array('email', $otp_identities)) {
            $login_user = User::GetUserByEmail($login_otp_identity);
        }

//        if (!$login_user && in_array('phone_number', $otp_identities)) {
//            $login_user = User::GetUserByMobilePhoneNumber($otp_login_identity);
//        }

        if ($login_user) {
            $_SESSION['login_otp_identity'] = $login_otp_identity;
            $_SESSION['login_otp_userid'] = $login_user->id();
        } else {
            clear_otp_session();
            $error_message = "We couldn't find an account associated with the {$otp_identities_label} address you entered. Please double-check the address and try again.";
        }
    }
}

if ($_SESSION['login_otp_userid']) {
    if (isset($_POST['login_otp'])) {

        $error_message = 'Incorrect OTP, please re-enter or generate a new OTP';

        // OTP is provided, verify it.

        if (time() > ($_SESSION['login_otp_valid_until'] ?? 0)) {
            $error_message = 'OTP expired, lets start over';
            clear_otp_session();
        }


        if (($_POST['login_otp'] == $_SESSION['login_otp'])) {
            // Autheticated
            $_SESSION['otp_data'] = array(
                'is_logged_in' => true,
                'otp_login_userid' => $_SESSION['login_otp_userid'],
                'otp_login_email' => $_SESSION['login_otp_identity'],
                'app_type' => $_SESSION['app_type'],
            );

            clear_otp_session();

            $error_message = '';

            header("location: ../login");
            exit();
        } else {
            $_SESSION['login_otp_auth_attempts'] = ($_SESSION['login_otp_auth_attempts'] ?? 0) + 1;
            if ($_SESSION['login_otp_auth_attempts'] > 3) {
                $error_message = 'Too many failed attempts, lets start over';
                clear_otp_session();
            }
        }
    } else {
        $_SESSION['login_otp_generation_attempts'] = ($_SESSION['login_otp_generation_attempts'] ?? 0) + 1;
        if ($_SESSION['login_otp_generation_attempts'] > 3) {
            clear_otp_session();
            $error_message = 'Too many OTP generation attempts, lets start over';
        } else {
            $login_user = User::GetUser($_SESSION['login_otp_userid']);
            $_ZONE = $_COMPANY->getEmptyZone($_SESSION['app_type']);
            $_SESSION['login_otp'] = $login_user->generateAndSendAuthenticationOTPCode($application_name, $from_email_label);
            $_SESSION['login_otp_valid_until'] = time() + 300;
            $success_message = 'Please check your email for One-Time Password (OTP)';
        }
    }
}

function clear_otp_session ($scope = 'all') {

    if ($scope == 'all') {
        unset($_SESSION['login_otp_identity']);
        unset($_SESSION['login_otp_userid']);
        unset($_SESSION['login_otp_generation_attempts']);
    }
    unset($_SESSION['login_otp']);
    unset($_SESSION['login_otp_valid_until']);
    unset($_SESSION['login_otp_auth_attempts']);
}

include __DIR__ . '/views/login.html';

