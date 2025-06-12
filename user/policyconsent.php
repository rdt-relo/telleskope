<?php
include_once __DIR__.'/head.php';

if ($_COMPANY == null) {
    error_and_exit('Error [031]: Invalid State. Please restart your sign in session.');
}

$message = '';
if (isset($_POST['acceptPrivacyPolicy']) &&  $_POST['acceptPrivacyPolicy'] == "on") {
    $_SESSION['policy_accepted'] = '1';
    if (isset($_GET["re"]) && 1 == (int)$_GET["re"]) {
        $location = "login";
    } else {
        $location = "signup";
    }
    header("location: ".$location);
    exit();
}

$title = "User Account - Accept Policy";
include __DIR__ . '/views/policyconsent.html';
