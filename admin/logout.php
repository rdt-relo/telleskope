<?php
session_start();
setcookie(session_name(), '', time() - 3600, '/');
$_SESSION = array();
session_destroy();
Http::Redirect('index?logout=1');
