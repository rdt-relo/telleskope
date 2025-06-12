<?php

require_once __DIR__ .'/../utils/Sftp.php';

class DataExportJob extends Job
{
    public function __construct()
    {
        parent::__construct();
        $this->jobid = "DATAEXP_{$this->cid}_0_0_0_" . microtime(TRUE);
        $this->delay = 86400; //In seconds
        $this->jobtype = self::TYPE_DATA_EXPORT;
    }

    /**
     * When creating the job, set the delay to number of seconds from now when the job should be first run.
     * @param string $sftp_hostname
     * @param string $sftp_username
     * @param string $sftp_password
     * @param string $sftp_folder
     * @param string $sftp_filename the name that customer wants for the file
     * @param int $repeat_days number of days after which the job should be re-run, typically 1 or 7 days
     * @param string $report_id
     * @param int $report_format , 1 for CSV, 2 for TSV, 3 for XLS and 4 for JSON
     * @param string $notifyEmails - Email addresses who should get notification, comma seperated list
     * @param bool $reportZip
     * @param string $reportZipPassword
     * @param string $pgpEncryptionKey
     */
    public function saveAsSftpDelivery(string $sftp_hostname, string $sftp_username, string $sftp_password, string $sftp_folder, string $sftp_filename, int $repeat_days, string $report_id, int $report_format, string $notifyEmails, bool $reportZip, string $reportZipPassword, string $pgpEncryptionKey, string $sftp_auth_method, bool $add_timestamp_to_filename, bool $add_trailer)
    {
        global $_COMPANY;
        /* @var Company $_COMPANY */
        $details = array();
        $details['ReportId'] = $report_id;
        $details['ReportFormat'] = $report_format;
        $details['ReportZip'] = $reportZip;
        $details['ReportZipEncryptedPassword'] = $reportZipPassword ? aes_encrypt($reportZipPassword, 'pc9cF3l1vZQkHtAg', 'zy3d8ZjZvD93Nlrc', false, false, true) : '';
        $details['RepeatDays'] = $repeat_days;
        // Store the realm as password hash.... this is for security checking when processing the job.
        $details['SecurityHash'] = password_hash($this->cid . '_' . $_COMPANY->getRealm(), PASSWORD_BCRYPT);
        $details['Mechanism'] = 'SFTP';
        $details['SFTP']['Hostname'] = $sftp_hostname;
        $details['SFTP']['Username'] = $sftp_username;
        $details['SFTP']['EncryptedPassword'] = aes_encrypt($sftp_password, 'pc9cF3l1vZQkHtAg', 'zy3d8ZjZvD93Nlrc', false, false, true);
        $details['SFTP']['Folder'] = $sftp_folder;
        $details['SFTP']['Filename'] = $sftp_filename;
        $details['pgpEncryptionKey'] = base64_url_encode($pgpEncryptionKey); // PGP keys are not JSON friendly.
        $details['NotifyEmails'] = $notifyEmails;
        $details['SFTP']['AuthMethod'] = $sftp_auth_method;
        $details['addTimeStampToFileName'] = $add_timestamp_to_filename;
        $details['addTrailer'] = ($report_format === 1) ? $add_trailer : false; // Trailer is only applicable for CSV
        $this->details = json_encode($details,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        parent::saveAsPerpetualType();
    }

    public function saveAsEmailDelivery(array $email_recipients, string $email_subject, string $email_body, int $repeat_days, string $report_id, int $report_format, string $notifyEmails, bool $reportZip, string $reportZipPassword, string $pgpEncryptionKey, string $filename, bool $add_timestamp_to_filename, bool $add_trailer)
    {
        global $_COMPANY;
        /* @var Company $_COMPANY */

        if (empty($pgpEncryptionKey) && (!$reportZip || empty($reportZipPassword))) {
            return; // Email delivery requires either PGP encryption of password encrypted zipe
        }

        $details = array();
        $details['ReportId'] = $report_id;
        $details['ReportFormat'] = $report_format;
        $details['ReportZip'] = $reportZip;
        $details['ReportZipEncryptedPassword'] = $reportZipPassword ? aes_encrypt($reportZipPassword, 'pc9cF3l1vZQkHtAg', 'zy3d8ZjZvD93Nlrc', false, false, true) : '';
        $details['RepeatDays'] = $repeat_days;
        // Store the realm as password hash.... this is for security checking when processing the job.
        $details['SecurityHash'] = password_hash($this->cid . '_' . $_COMPANY->getRealm(), PASSWORD_BCRYPT);
        $details['Mechanism'] = 'EMAIL';
        $details['pgpEncryptionKey'] = base64_url_encode($pgpEncryptionKey); // PGP keys are not JSON friendly.
        $details['NotifyEmails'] = $notifyEmails;

        $details['EMAIL']['EmailRecipients'] = $email_recipients;
        $details['EMAIL']['EmailSubject'] = $email_subject;
        $details['EMAIL']['EmailBody'] = $email_body;
        $details['EMAIL']['Filename'] = $filename;
        $details['addTimeStampToFileName'] = $add_timestamp_to_filename;
        $details['addTrailer'] = ($report_format === 1) ? $add_trailer : false; // Trailer is only applicable for CSV

        $this->details = json_encode($details,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        //$this->details = mysqli_real_escape_string(GlobalGetDBROConnection(), $this->details);

        parent::saveAsPerpetualType();
    }

    protected function processAsPerpetualType()
    {
        global $_COMPANY;
        global $_ZONE;

        if (empty($_ZONE)) {
            Logger::Log("Fatal Zone is missing");
            return;
        }

        $details = json_decode($this->details, true);
        $notifyEmails = $details['NotifyEmails'];

        // Update the delay as it will be used to calculate the next job start time.
        $repeat_days = (int)$details['RepeatDays'];
        if ($repeat_days) {
            $this->delay = $repeat_days * 86400;
        } else {
            $this->jobsubtype = 0; // This is the last iteration of the job so convert if from perpetual to done.
        }

        // 0 - Perform a integrity/security check to see
        if (!password_verify($this->cid . '_' . $_COMPANY->getRealm(), $details['SecurityHash'])) {
            Logger::Log("Fatal Security Check failed", Logger::SEVERITY['SECURITY_ERROR']);
            return;
        }

        $zip_password = $details['ReportZipEncryptedPassword'] ?? '';
        if ($zip_password) {
            $zip_password = aes_encrypt($zip_password, 'pc9cF3l1vZQkHtAg', 'zy3d8ZjZvD93Nlrc', true, false, true);
        }

        // 1 - Fetch the report
        $report_filename = generateTeleskopeReportForExport($details['ReportId'], $details['ReportFormat'], $details['ReportZip'], $zip_password);
        if (empty($report_filename)) {
            Logger::Log("Fatal failed to generate report");
            return;
        }

        // 1.1 - Add trailer if appliable
        $add_trailer = $details['addTrailer'] ?? false;
        if ($details['ReportFormat'] === 1 && $add_trailer) {
            $this->appendCsvTrailer($report_filename);
        }

        // 1.5 - PGP Encrypt the report
        $pgp_bytes_written = 0;
        if (!empty($details['pgpEncryptionKey'])) {
            $pgpEncryptionKey = base64_url_decode($details['pgpEncryptionKey']); // PGP keys are stored as base64 encoded
            Env::Put("GNUPGHOME=/tmp");
            $gpg = new gnupg();
            $gpg->seterrormode(gnupg::ERROR_EXCEPTION);
            $info = $gpg->import($pgpEncryptionKey);
            $gpg->addencryptkey($info['fingerprint']);
            $pgp_bytes_written = file_put_contents($report_filename, $gpg->encrypt(file_get_contents($report_filename))); // piping file read and conversion and write for memory use efficiency.
            if (!$pgp_bytes_written) { // If we cannot encrypt, throw fatal error. Return as we do not want to send unencrypted data.
                Logger::Log("Fatal Exception while trying to encrypt file {$report_filename}");
                unlink($report_filename);
                return;
            }
        }

        $filename_suffix = '';
        if ($details['addTimeStampToFileName'] ?? false) {
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $filename_suffix = '_' . $now->format('Ymd-His');
        }

        // 2 - Transfer the report
        if ($details['Mechanism'] === 'SFTP') {
            $sftp_hostname = $details['SFTP']['Hostname'];
            $sftp_username = $details['SFTP']['Username'];
            $sftp_password = aes_encrypt($details['SFTP']['EncryptedPassword'], 'pc9cF3l1vZQkHtAg', 'zy3d8ZjZvD93Nlrc', true, false, true);
            $sftp_folder = $details['SFTP']['Folder'];
            $sftp_filename = $this->addSuffixToFilename($details['SFTP']['Filename'], $filename_suffix);
            $sftp_auth_method = $details['SFTP']['AuthMethod'] ?? 'sftp_password';

            $sftp_hostname_parts = explode(':',$sftp_hostname);
            if (count($sftp_hostname_parts) == 1) { // If port is not provided set it to 22
                $sftp_hostname_parts[] = 22;
            }

            $res = PhpSecLibSftp::Put($report_filename, $sftp_hostname_parts[0], $sftp_username, $sftp_password, $sftp_folder, $sftp_filename,$sftp_hostname_parts[1], $sftp_auth_method);
            if (!$res['status']) {
                Logger::Log( "Unable to put file {$sftp_filename} in {$sftp_hostname}", Logger::SEVERITY['FATAL_ERROR'], $res);
            } else {
                Logger::Log("SFTP PUT Done - Put $sftp_filename", Logger::SEVERITY['INFO'], $res);

                if (!empty($notifyEmails)) {
                    $subject = 'Completed SFTP Delivery';
                    $message = json_encode(array('filename' => $sftp_filename, 'filesize' => $res['bytes']));
                    $_COMPANY->emailSend2('Teleskope Scheduler', $notifyEmails, $subject, $message, $_ZONE->val('app_type'));
                }
            }
        } elseif ($details['Mechanism'] === 'EMAIL') {
            $email_body = nl2br(htmlspecialchars($details['EMAIL']['EmailBody']));
            $email_body .= '<br><br><hr><p>You are receiving this email from Teleskope automated data export job. To make changes to the report or cancel the scheduled automated job, please send an email to support@teleskope.atlassian.net</p><hr><br>';
            $filename = $this->addSuffixToFilename($details['EMAIL']['Filename'], $filename_suffix);
            $filesize = ($pgp_bytes_written) ?: strlen(file_get_contents($report_filename));
            $emailStatus = $_COMPANY->emailSend2(
                'Teleskope Scheduler',
                implode(',', $details['EMAIL']['EmailRecipients']),
                $details['EMAIL']['EmailSubject'],
                $email_body,
                $_ZONE->val('app_type'),
                '',
                '',
                [
                    [
                        'content' => file_get_contents($report_filename),
                        'filename' => $filename,
                    ]
                ]
            );

            if ($emailStatus) {
                $message = json_encode(array('filename' => $filename, 'filesize' => $filesize));
                if (!empty($notifyEmails)) {
                    $subject = 'Completed EMAIL Delivery';
                    $_COMPANY->emailSend2('Teleskope Scheduler', $notifyEmails, $subject, $message, $_ZONE->val('app_type'));
                }
                Logger::Log("EMAIL Delivery Done - " . $message, Logger::SEVERITY['INFO']);
            }
        }

        // 4 - Clean up
        unlink($report_filename);
    }

    private function addSuffixToFilename(string $filename, string $suffix): string
    {
        $path_parts = pathinfo($filename);
        $path_parts['filename'] .= $suffix;

        if (isset($path_parts['extension'])) {
            return $path_parts['filename'] . '.' . $path_parts['extension'];
        }

        return $path_parts['filename'];
    }

    private function appendCsvTrailer($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath) || !is_writable($filePath)) {
            return false;
        }

        $rowCount = 0;
        $columnCount = 0;

        // Read the file to count rows and columns
        if (($handle = fopen($filePath, 'r')) !== false) {
            $isFirstRow = true;
            while (($data = fgetcsv($handle)) !== false) {
                if ($isFirstRow) {
                    $isFirstRow = false; // Skip header
                    $columnCount = count($data);
                } else {
                    $rowCount++;
                }
            }
            fclose($handle);
        }

        // Prepare trailer format, e.g., "TRAILER,<row_count>,<column_count>"
        $trailer = ['TRAILER', $rowCount, $columnCount];

        // Append trailer to file
        if (($handle = fopen($filePath, 'a')) !== false) {
            fputcsv($handle, $trailer);
            fclose($handle);
            return true;
        }

        return false;
    }
}