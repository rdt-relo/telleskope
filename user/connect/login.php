<?php
include_once __DIR__ . '/../head.php';
include_once __DIR__ . '/../../include/UserConnect.php';

$title = "Teleskope | Connect login";

if (empty($_SESSION['app_type'])){
    header("Location: ../../user/login.php");
}

$message = '';

$loginExternalEmail = $_GET['email'] ?? '';

if (isset($_POST['login_external_email'])) {
    // username and password authentication mechanism
    $loginExternalEmail = $_POST['login_external_email'];
    $loginPassword = $_POST['login_password'];

    if (!verify_recaptcha()) {
        $error_message = "Incorrect reCAPTCHA, try again";
    } elseif (empty($loginExternalEmail) || empty($loginPassword)) {
        $error_message = 'Email and Password cannot be empty';
    } elseif (!filter_var($loginExternalEmail, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Your email address is invalid';
    } elseif (($tempConnectUser = UserConnect::GetConnectUserByEmail($loginExternalEmail)) !== null) {
        $loginReturnVal = $tempConnectUser->login($_SESSION['app_type'], $loginPassword);
        if ($loginReturnVal['status'] != 1) {
            $error_message = $loginReturnVal['message'];
        } else {
            $_SESSION['connect_data'] = array(
                'is_logged_in' => true,
                'connect_user_id' => $tempConnectUser->id(),
                'teleskope_user_id' => $tempConnectUser->val('userid'),
                'teleskope_company_id' => $tempConnectUser->val('companyid'),
                'external_email' => $tempConnectUser->val('external_email'),
                'app_type' => $_SESSION['app_type'],
            );
            header("location: ../login");
            exit();
        }
    } else {
        $error_message = 'Unable to login with the provided credentials';
    }
}

    include __DIR__ . '/views/login.html';

