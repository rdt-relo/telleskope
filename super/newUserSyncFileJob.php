<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageScheduledJobs);

$db	= new Hems();
$pageTitle = "New User Sync File Job";
$successCode = 0;
$metaJSONError = "";
$jobid = 0;
$edit = null;
if (!empty($_GET['jobid'])){
    $jobid = base64_decode($_GET['jobid']);
    $jobData = Job::GetJob($_SESSION['companyid'], $jobid);
    if($jobData){
        $edit = Arr::Json2Array($jobData['details']); // No need to add backslashes for JSON coming from DB
    }
}
if (isset($_POST['submit'])){
    $action = $_POST['Action'];
    $localFilename = $_POST['LocalFilename'];
    $fileFormat = intval($_POST['FileFormat']);
    $repeatDays = intval($_POST['RepeatDays']);
    $deleteMissingEntries = intval($_POST['DeleteMissingEntries']);
    $delay = intval($_POST['delay']);
    $zip = boolval($_POST['Zip']);
    $jsonPath = $_POST['JSON_Path'];
    if (!empty($_POST['Meta']) && !json_decode($_POST['Meta'], true) && json_last_error() !== JSON_ERROR_NONE) {
        $metaJSONError = 'Meta JSON error: ' . json_last_error_msg();
    }else{
    $meta = Arr::Json2Array($_POST['Meta'], true); // Enable backslashes for JSON input coming from browser text input
        //json_decode(str_replace('\\','\\\\',(str_replace('//','////',$_POST['Meta']))),true);

    // We will temporarily create $_COMPANY variable to help create a job.
    $_COMPANY = Company::GetCompany(intval($_SESSION['companyid']));

    $notifyEmails = '';
    if ($_POST['NotifyEmails']) {
        $validEmails = array();
        foreach (explode(',',$_POST['NotifyEmails']) as $e) {
            if ($_COMPANY->isValidAndRoutableEmail($e)) $validEmails[] = $e;
        }
        $validEmails = array_slice($validEmails,0,3); // Retain only first 3 emails
        $notifyEmails = implode(',', $validEmails);
    }

    $usersyncjob = new UserSyncFileJob();
    $usersyncjob->delay = $delay;
    if ($action === 'user-data-sync') {
        $source_id = intval($_POST['source_id'] ?? 0);
        $usersyncjob->saveAsUserDataSyncType($localFilename, $fileFormat, $repeatDays, $meta, $notifyEmails, $zip, $jsonPath, $deleteMissingEntries, $source_id);
    } elseif ($action === 'user-data-delete') {
        $usersyncjob->saveAsUserDataDeleteType ($localFilename,$fileFormat,$repeatDays,$meta,$notifyEmails, $zip,$jsonPath);
    }
    $_COMPANY =null;
    // and we remove the variable here

    $successCode = 1;
   }
}
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/newUserSyncFileJob.html');
include(__DIR__ . '/views/footer.html');
?>
