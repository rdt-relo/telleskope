<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
define('AJAX_CSRF_EXEMPT',1); //Create an exception to bypass CSRF checks for hashtag checks from imperavi
require_once __DIR__.'/head.php';


if (isset($_GET['searchHashTag'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Logger::Log("Handler API - got: {$_POST['handle']}");
    $handle = strtolower(trim($_POST['handle']));
    $handleData = HashtagHandle::GetHandlesObject($handle);
    $jsonData = json_encode((object)($handleData));
    //Logger::Log("Handler API - returning: {$jsonData}");
    die ($jsonData);
}

else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}
