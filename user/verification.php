<?php
include_once __DIR__.'/head.php';

if (!empty($_SESSION['userid'])){
	header('location: login?continue=1');	 // User is already signed in.
    exit();
}
$message = '';

$title = "Create Your Account - Verify Email";
include __DIR__ . '/views/verification.html';
