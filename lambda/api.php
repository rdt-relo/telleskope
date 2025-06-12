<?php 
require_once __DIR__.'/../include/jobs/Job.php';
exit();
// Check if the originator is sending x-api-key header.
// Since the only user of this API is lambda, we have it fixed in our code.
//if (!isset($_SERVER['HTTP_X_API_KEY']) || ($_SERVER['HTTP_X_API_KEY']) !== TELESKOPE_LAMBDA_API_KEY) {
//    header("HTTP/1.1 401 Unauthorized");
//    exit();
//}
//
//
//if (isset($_GET['addUserSyncFileJob']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
//    if (!isset($_POST['filename']) || empty($filename = $_POST['filename'])
//        || strpos($filename,'..') !== false || strpos($filename,'/') !== false
//        || !isset($_POST['filetype']) || empty($filetype = $_POST['filetype'])
//        || !isset($_POST['realm']) || empty($realm = $_POST['realm'])
//        || !isset($_POST['version']) || empty($version = $_POST['version'])
//        || empty($subdomain = explode('.',$realm)[0])
//        || ($company = Company::GetCompanyBySubdomain($subdomain)) === null) {
//        Logger::Log("LAMBDA: Fatal add job for {$realm} - filesync {$filename}");
//        header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
//        exit();
//    }
//
//    $userSyncFileJob = new UserSyncFileJob($company->id(), 0);
//    if ($filetype === 'user-data-sync') {
//        $userSyncFileJob->delay = rand(15,30); // 15 to 30 minute delay
//        $filepath = "incoming/user-data-sync/{$version}/{$filename}";
//        $userSyncFileJob->saveAsUserSyncUpdateType(true, $filepath);
//    } elseif ($filetype === 'user-data-delete') {
//        $userSyncFileJob->delay = rand(31,45); // 31 to 45 minute delay
//        $filepath = "incoming/user-data-delete/{$version}/{$filename}";
//        $userSyncFileJob->saveAsUserSyncDeleteType(true, $filepath);
//    } else {
//        Logger::Log("LAMBDA: Fatal add job for {$realm} - filesync {$filename}");
//        header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
//        exit();
//    }
//
//    Logger::Log("LAMBDA: Added Job for {$realm} - filesync {$filename}");
//}



