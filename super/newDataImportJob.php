<?php
require_once __DIR__ . '/head.php';

Auth::CheckPermission(Permission::ManageScheduledJobs);

$db = new Hems();
$pageTitle = "New Data Import Job";
$successCode = 0;
$jobid = 0;
$edit = null;

// We will temporarily create $_COMPANY variable to help create a job.
$_COMPANY = Company::GetCompany(intval($_SESSION['companyid']));

if (!empty($_GET['jobid'])) {
    $pageTitle = "Clone Data Import Job";
    $jobid = base64_decode($_GET['jobid']);
    $jobData = Job::GetJob($_SESSION['companyid'], $jobid);
    if ($jobData) {
        $edit = Arr::Json2Array($jobData['details']); // No need to add backslashes for JSON coming from DB
        if (isset($edit['SFTP'])) {
            $orig_enc_password = $edit['SFTP']['EncryptedPassword'];
        } elseif (isset($edit['HTTPS'])) {
            $orig_enc_password = $edit['HTTPS']['EncryptedPassword'];
        }

        if (isset($orig_enc_password)) {
            $orig_dec_password = aes_encrypt($orig_enc_password, 'pc9cF3l1vZQkHtAg', 'zy3d8ZjZvD93Nlrc', true, false, true);
            $masked_password = Str::GenerateMask($orig_dec_password, 5);
        }
        $orig_zoneid = $jobData['zoneid'];

        if ($edit['Mechanism'] === 'OAuth2') {
            $orig_dec_client_secret = CompanyEncKey::Decrypt($edit['OAuth2']['ClientSecret']);
            $masked_client_secret = Str::GenerateMask($orig_dec_client_secret, 5);

            $masked_refresh_token = '';
            if (!empty($edit['OAuth2']['RefreshToken'])) {
                $orig_dec_refresh_token = CompanyEncKey::Decrypt($edit['OAuth2']['RefreshToken']);
                $masked_refresh_token = Str::GenerateMask($orig_dec_refresh_token, 5);
            }
        }
    }
}

$zones = $_COMPANY->getZones();

if (isset($_POST['submit'])) {

    $repeatDays = $_POST['RepeatDays'];
    $mechanism = $_POST['Mechanism'];
    $hostname = $_POST['Hostname'] ?? '';
    $username = $_POST['Username'] ?? '';
    $folder = $_POST['Folder'] ?? '';
    $filename = $_POST['Filename'] ?? '';
    $is_pgp_encrypted = (int) isset($_POST['IsPgpEncrypted']);
    $local_filename = $_POST['LocalFilename'];
    $delay = $_POST['delay'];

    $notifyEmails = '';
    if ($_POST['NotifyEmails']) {
        $validEmails = array();
        foreach (explode(',',$_POST['NotifyEmails']) as $e) {
            if ($_COMPANY->isValidAndRoutableEmail($e)) $validEmails[] = $e;
        }
        $validEmails = array_slice($validEmails,0,3); // Retain only first 3 emails
        $notifyEmails = implode(',', $validEmails);
        $_POST['notify_emails'] = $notifyEmails;
    }

    $zoneId = $_POST['ZoneId'];
    $_ZONE = $_COMPANY->getZone($zoneId); // required to set the job
    $job = new DataImportJob();
    $job->delay = $delay;
    if ($mechanism == 'HTTPS') {
        // Update $_POST['password'] if mask was provided - for cloned jobs
        if (isset($orig_dec_password) && isset($masked_password) && ($_POST['Password'] == $masked_password)) {
            $_POST['Password'] = $orig_dec_password;
        }
        $password = $_POST['Password'] ?? '';
        $job->saveAsHttpsGet($hostname, $username, $password, $repeatDays, $local_filename, $notifyEmails, $is_pgp_encrypted);
        $successCode = 1;
    } elseif ($mechanism === 'SFTP') {
        // Update $_POST['password'] if mask was provided - for cloned jobs
        if (isset($orig_dec_password) && isset($masked_password) && (($_POST['Password'] ?? '') == $masked_password)) {
            $_POST['Password'] = $orig_dec_password;
        }
        $password = $_POST['Password'] ?? '';
        $sftp_auth_method = $_POST['sftp_auth_method'] ?? 'sftp_password';
        $job->saveAsSftpGet($hostname, $username, $password, $folder, $filename, $repeatDays, $local_filename, $notifyEmails, $is_pgp_encrypted, $sftp_auth_method);
        $successCode = 2;
    } elseif ($mechanism === 'OAuth2') {
        // Update $_POST['ClientSecret'] if mask was provided - for cloned jobs
        if (isset($orig_dec_client_secret) && isset($masked_client_secret) && ($_POST['ClientSecret'] == $masked_client_secret)) {
            $_POST['ClientSecret'] = $orig_dec_client_secret;
        }

        // Update $_POST['RefreshToken'] if mask was provided - for cloned jobs
        if (isset($orig_dec_refresh_token) && isset($masked_refresh_token) && ($_POST['RefreshToken'] == $masked_refresh_token)) {
            $_POST['RefreshToken'] = $orig_dec_refresh_token;
        }

        $clientId = $_POST['ClientId'];
        $clientSecret = $_POST['ClientSecret'];
        $refreshToken = trim($_POST['RefreshToken'] ?? '');
        $getBearerTokenUrl = $_POST['GetBearerTokenUrl'];
        $getTeportUrl = $_POST['GetReportUrl'];
        $oauth_scope = $_POST['oauth_scope'] ?? '';
        $oauth_grant_type = $_POST['oauth_grant_type'] ?? '';
        $oauth_custom_header_name = $_POST['oauth_custom_header_name'] ?? '';
        $oauth_custom_header_value = $_POST['oauth_custom_header_value'] ?? '';

        $oauth_data_handler = 'default';
        if (isset($_POST['oauth_data_handler']) && in_array($_POST['oauth_data_handler'], ['microsoft_api'])) {
            $oauth_data_handler = $_POST['oauth_data_handler'];
        }

        $job->saveAsOAuthGet($clientId, $clientSecret, $refreshToken, $getBearerTokenUrl, $getTeportUrl, $repeatDays, $local_filename, $notifyEmails, $oauth_scope, $oauth_grant_type, $oauth_custom_header_name, $oauth_custom_header_value, $oauth_data_handler);

        $successCode = 3;
    }
}

$_ZONE = null;
$_COMPANY = null;
// and we remove the variable here
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/newDataImportJob.html');
include(__DIR__ . '/views/footer.html');