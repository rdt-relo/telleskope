<?php
require_once __DIR__.'/../include/dblog/EmailLog.php';
$_LAMBDA_MODULE = 'EMAIL_TRACKER';

if (!isset($_SERVER['HTTP_X_API_KEY']) || ($_SERVER['HTTP_X_API_KEY']) !== TELESKOPE_LAMBDA_API_KEY) {
    Logger::Log ("LambdaBot:EmailTracker Unauthorized =".stripslashes (json_encode($_POST)), Logger::SEVERITY['SECURITY_ERROR']);
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

if (isset($_POST['f']) && $_POST['f'] === 'opn') {

    if (empty($_POST['r']) || empty($_POST['i'])) {
        Logger::Log ("LambdaBot:EmailTracker Missing parameters =".stripslashes (json_encode($_POST)));
        exit();
    }

    $realm = $_POST['r'];
    $enc_id = $_POST['i'];
    $call_time = (int)($_POST['t'] ?? time());

    if (EmailLog::RegisterEmailOpen($realm,$enc_id,$call_time)) {
        Logger::Log ("LambdaBot:EmailTracker Processed =".stripslashes (json_encode($_POST)), Logger::SEVERITY['INFO']);
        exit();
    } else {
        Logger::Log ("LambdaBot:EmailTracker Error Not Found ".stripslashes (json_encode($_POST)),Logger::SEVERITY['WARNING_ERROR']);
        exit();
    }
}

elseif (isset($_POST['f']) && $_POST['f'] === 'clk'){

    if (empty($_POST['r']) || empty($_POST['i']) || empty($_POST['u'])) {
        Logger::Log ("LambdaBot:EmailTracker Missing parameters =".stripslashes (json_encode($_POST)));
        exit();
    }

    $realm = $_POST['r'];
    $enc_id = $_POST['i'];
    $url_id = (int)$_POST['u'];
    $url = urldecode($_POST['re']);
    $call_time = (int)($_POST['t'] ?? time());

    if (EmailLog::RegisterUrlClick($realm,$enc_id,$url_id,$call_time)) {
        Logger::Log ("LambdaBot:EmailTracker Processed =".stripslashes (json_encode($_POST)), Logger::SEVERITY['INFO']);
        exit();
    } else {
        Logger::Log ("LambdaBot:EmailTracker Error Not Found ".stripslashes (json_encode($_POST)), Logger::SEVERITY['WARNING_ERROR']);
        exit();
    }
}

else {
    Logger::Log ("LambdaBot:EmailTracker Invalid URL parameters =".stripslashes (json_encode($_POST)), Logger::SEVERITY['WARNING_ERROR']);
    exit();
}
