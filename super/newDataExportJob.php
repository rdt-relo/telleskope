<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageScheduledJobs);

$db	= new Hems();
$pageTitle = "New Data Export Job";
$successCode = 0;
$errorCode = 0;
$errorText = '';
$jobid = 0;
$edit = null;

// We will temporarily create $_COMPANY variable to help create a job.
$_COMPANY = Company::GetCompany(intval($_SESSION['companyid']));

if (!empty($_GET['jobid'])){
    $pageTitle = "Clone Data Export Job";
    $jobid = base64_decode($_GET['jobid']);
    $jobData = Job::GetJob($_SESSION['companyid'], $jobid);
    $orig_enc_password = '';
    $orig_zip_password = '';
    if($jobData){
        $edit = Arr::Json2Array($jobData['details']); // No need to add backslashes for JSON coming from DB
        if (isset($edit['SFTP'])) {
            $orig_enc_password = $edit['SFTP']['EncryptedPassword'];
        } elseif (isset($edit['HTTPS'])) {
            $orig_enc_password = $edit['HTTPS']['EncryptedPassword'];
        }
        $orig_password = aes_encrypt($orig_enc_password, 'pc9cF3l1vZQkHtAg', 'zy3d8ZjZvD93Nlrc', true, false, true);
        $orig_zip_password = aes_encrypt($edit['ReportZipPassword'] ?? '', 'pc9cF3l1vZQkHtAg', 'zy3d8ZjZvD93Nlrc', true, false, true);
        $orig_zoneid = $jobData['zoneid'];
        $pgpEncryptionKey = base64_url_decode($edit['pgpEncryptionKey']);
    }
}

$transferReports = Report::GetAllReportsInCompany('transfer');
foreach ($transferReports as $k => $report) {
    $transferReports[$k]['zonename'] = $_COMPANY->getZone($report['zoneid'])->val('zonename');
}
$reporttype = array (

                Report::REPORT_TYPE_USER_MEMBERSHIP => 'ReportUserMembership',
                Report::REPORT_TYPE_USERS => 'ReportUsers',
                Report::REPORT_TYPE_EVENT => 'ReportEvents',
                Report::REPORT_TYPE_EVENT_RSVP => 'ReportEventRSVP',
                Report::REPORT_TYPE_BUDGET => 'ReportBudget',
                Report::REPORT_TYPE_SURVEY => 'ReportSurvey',
                Report::REPORT_TYPE_SURVEY_DATA => 'ReportSurveyData',
                Report::REPORT_TYPE_ANNOUNCEMENT => 'ReportAnnouncement',
                Report::REPORT_TYPE_NEWSLETTERS => 'ReportNewsletter',
                Report::REPORT_TYPE_OFFICELOCATIONS => 'ReportOfficeLocations',
                Report::REPORT_TYPE_GROUP_DETAILS => 'ReportGroupDetails',
                Report::REPORT_TYPE_GROUP_CHAPTER_DETAILS => 'ReportGroupChapterDetails',
                Report::REPORT_TYPE_GROUP_CHANNEL_DETAILS => 'ReportGroupChannelDetails',
                Report::REPORT_TYPE_GROUPCHAPTER_LOCATION => 'ReportGroupChapterLocations',
                Report::REPORT_TYPE_TEAM_TEAMS => 'ReportTeamTeams',
                Report::REPORT_TYPE_TEAM_MEMBERS => 'ReportTeamMembers',
                Report::REPORT_TYPE_TEAM_REGISTRATIONS => 'ReportTeamRegistrations',
                Report::REPORT_TYPE_USER_AUDIT_LOGS => 'ReportUserAuditLogs',
                Report::REPORT_TYPE_LOGINS => 'ReportLogins',
                Report::REPORT_TYPE_EVENT_SURVEY => 'ReportEventSurveyData',
                Report::REPORT_TYPE_ORGANIZATION => 'ReportOrganizations',
                Report::REPORT_TYPE_EVENT_ORGANIZATION => 'ReportEventOrganizations',
);

$zones = $_COMPANY->getZones();

if (isset($_POST['submit'])){
    $reportId = $_POST['ReportId'];
    $reportFormat = $_POST['ReportFormat'];
    $repeatDays = $_POST['RepeatDays'];
    $mechanism = $_POST['Mechanism'];
    $pgpEncryptionKey = $_POST['pgpEncryptionKey'];
    $reportZip = $_POST['ReportZip'];
    $zipPassword = $_POST['ReportZipPassword'];
    $delay = $_POST['delay'];
    $successCode = 0;
    $notifyEmails = '';
    $add_timestamp_to_filename = !empty($_POST['add_timestamp_to_filename']);
    $add_trailer = !empty($_POST['add_trailer']);

    if ($_POST['NotifyEmails']) {
        $validEmails = array();
        foreach (explode(',',$_POST['NotifyEmails']) as $e) {
            if ($_COMPANY->isValidAndRoutableEmail($e)) $validEmails[] = $e;
        }
        $validEmails = array_slice($validEmails,0,3); // Retain only first 3 emails
        $notifyEmails = implode(',', $validEmails);
    }

    $zoneId = $_POST['ZoneId'];
    $_ZONE = $_COMPANY->getZone($zoneId); // required to set the job
    if (in_array($reportId, array_column($transferReports,'reportid'))) {
        $job = new DataExportJob();
        $job->delay = $delay;
        if ($mechanism == 'SFTP') {
            $hostname = $_POST['Hostname'];
            $username = $_POST['SFTP_Username'];
            $folder = $_POST['Folder'];
            $filename = $_POST['SFTP_Filename'];
            $password = $_POST['SFTP_Password'] ?? '';
            $sftp_auth_method = $_POST['sftp_auth_method'] ?? 'sftp_password';
            $job->saveAsSftpDelivery($hostname, $username, $password, $folder, $filename, $repeatDays, $reportId, $reportFormat, $notifyEmails, $reportZip, $zipPassword, $pgpEncryptionKey, $sftp_auth_method, $add_timestamp_to_filename, $add_trailer);
            $successCode = 1;
        } elseif ($mechanism === 'EMAIL') {
            if (empty($pgpEncryptionKey) && (!$reportZip || empty($zipPassword))) {
                $errorCode = 1;
                $errorText = 'Security requirements not met: Either Zip password or PGP Key is requried';
            } else {
                $filename = $_POST['Email_Filename'];
                $email_recipients = $_POST['email_recipients'] ?? '';
                $email_recipients = array_filter(explode(',', $email_recipients), function ($email_recipient) {
                    global $_COMPANY;
                    return $_COMPANY->isValidAndRoutableEmail($email_recipient);
                });
                $email_recipients = array_slice($email_recipients, 0, 5);
                if (!empty($email_recipients)) {
                    $email_subject = $_POST['email_subject'];
                    $email_body = $_POST['email_body'];
                    $job->saveAsEmailDelivery($email_recipients, $email_subject, $email_body, $repeatDays, $reportId, $reportFormat, $notifyEmails, $reportZip, $zipPassword, $pgpEncryptionKey, $filename, $add_timestamp_to_filename, $add_trailer);
                    $successCode = 1;
                }
            }
        } else { // HTTPS

            /** TODO - LOGIC */
            $successCode = 2;
        }
    }
}

$_ZONE = null;
$_COMPANY =null;
// and we remove the variable here

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/newDataExportJob.html');
include(__DIR__ . '/views/footer.html');
