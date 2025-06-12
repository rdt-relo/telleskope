<?php

require_once __DIR__ .'/../utils/Sftp.php';

use GuzzleHttp\Client;

class DataImportJob extends Job
{

    public function __construct()
    {
        parent::__construct();
        $this->jobid = "DATAIMP_{$this->cid}_0_0_0" . microtime(TRUE);
        $this->delay = 86400; //In seconds
        $this->jobtype = self::TYPE_DATA_IMPORT;
    }

    /**
     * This method creates a perpetual job to pull a file from SFTP site.
     * @param string $sftp_hostname Hostname of SFTP server
     * @param string $sftp_username Username of SFTP Account
     * @param string $sftp_password Password of SFTP Account
     * @param string $sftp_folder CD into SFTP account on login
     * @param string $sftp_filename SFTP get this filename
     * @param int $repeat_days Repeat job after these many number of days
     * @param string $local_filename Store the file locally/S3 under this name. Local file name shall include the version
     * @param string $notifyEmails - Email addresses who should get notification, comma seperated list
     */
    public function saveAsSftpGet(string $sftp_hostname, string $sftp_username, string $sftp_password, string $sftp_folder, string $sftp_filename, int $repeat_days, string $local_filename, string $notifyEmails, int $is_pgp_encrypted, string $sftp_auth_method)
    {
        global $_COMPANY;
        /* @var Company $_COMPANY */
        $details = array();
        $details['LocalFilename'] = $local_filename;
        $details['RepeatDays'] = $repeat_days;
        // Store the realm as password hash.... this is for security checking when processing the job.
        $details['SecurityHash'] = password_hash($this->cid . '_' . $_COMPANY->getRealm(), PASSWORD_BCRYPT);
        $details['Mechanism'] = 'SFTP';
        $details['SFTP']['Hostname'] = $sftp_hostname;
        $details['SFTP']['Username'] = $sftp_username;
        $details['SFTP']['EncryptedPassword'] = aes_encrypt($sftp_password, 'pc9cF3l1vZQkHtAg', 'zy3d8ZjZvD93Nlrc', false, false, true);
        $details['SFTP']['Folder'] = $sftp_folder;
        $details['SFTP']['Filename'] = $sftp_filename;
        $details['IsPgpEncrypted'] = $is_pgp_encrypted;
        $details['NotifyEmails'] = $notifyEmails;
        $details['SFTP']['AuthMethod'] = $sftp_auth_method;
        $this->details = json_encode($details,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        parent::saveAsPerpetualType();
    }

    public function saveAsHttpsGet(string $https_url, string $https_username, string $https_password, int $repeat_days, string $local_filename, string $notifyEmails, int $is_pgp_encrypted)
    {
        global $_COMPANY;
        /* @var Company $_COMPANY */
        $details = array();
        $details['LocalFilename'] = $local_filename;
        $details['RepeatDays'] = $repeat_days;
        // Store the realm as password hash.... this is for security checking when processing the job.
        $details['SecurityHash'] = password_hash($this->cid . '_' . $_COMPANY->getRealm(), PASSWORD_BCRYPT);
        $details['Mechanism'] = 'HTTPS';
        $details['HTTPS']['URL'] = $https_url;
        $details['HTTPS']['Username'] = $https_username;
        $details['HTTPS']['EncryptedPassword'] = aes_encrypt($https_password, 'pc9cF3l1vZQkHtAg', 'zy3d8ZjZvD93Nlrc', false, false, true);
        $details['IsPgpEncrypted'] = $is_pgp_encrypted;
        $details['NotifyEmails'] = $notifyEmails;
        $this->details = json_encode($details,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        parent::saveAsPerpetualType();
    }

    /**
     * @param string $client_id
     * @param string $client_secret
     * @param string $refresh_token
     * @param string $get_bearer_token_url
     * @param string $get_report_url
     * @param int $repeat_days
     * @param string $local_filename
     * @param string $notifyEmails
     * @param string $oauth_scope
     * @param string $oauth_grant_type
     * @param string $oauth_custom_header_name - custom header is used only for fetching data using report url
     * @param string $oauth_custom_header_value - custom header value is used only for fetching data using report url
     * @param string $oauth_data_handler
     * @return void
     */
    public function saveAsOAuthGet (string $client_id, string $client_secret, string $refresh_token, string $get_bearer_token_url, string $get_report_url, int $repeat_days, string $local_filename, string $notifyEmails, string $oauth_scope, string $oauth_grant_type, string $oauth_custom_header_name, string $oauth_custom_header_value, string $oauth_data_handler) : void
    {
        global $_COMPANY;

        $details = [
            'Mechanism' => 'OAuth2',
            'SecurityHash' => password_hash($this->cid . '_' . $_COMPANY->getRealm(), PASSWORD_BCRYPT),
            'LocalFilename' => $local_filename,
            'RepeatDays' => $repeat_days,
            'NotifyEmails' => $notifyEmails,
            'IsPgpEncrypted' => 0,
            'OAuth2' => [
                'ClientId' => $client_id,
                'ClientSecret' => CompanyEncKey::Encrypt($client_secret),
                'RefreshToken' => CompanyEncKey::Encrypt($refresh_token),
                'GetBearerTokenUrl' => $get_bearer_token_url,
                'GetReportUrl' => $get_report_url,
                'oauth_scope' => $oauth_scope,
                'oauth_grant_type' => $oauth_grant_type,
                'oauth_custom_header_name' => $oauth_custom_header_name,
                'oauth_custom_header_value' => $oauth_custom_header_value,
                'oauth_data_handler' => $oauth_data_handler,
            ],
        ];
        $this->details = json_encode($details, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        parent::saveAsPerpetualType();
    }

    /**
     * This method imports data from the defined location using the defined mechanism and stores the data on S3 Uploader/{realm}/incoming/v2
     */
    protected function processAsPerpetualType()
    {
        global $_COMPANY;

        global $_ZONE;
        if (!isset($_ZONE)) // $_ZONE is required by emailing functions
            $_ZONE = $_COMPANY->getEmptyZone('teleskope');

        $details = json_decode($this->details, true);
        $notifyEmails = $details['NotifyEmails'];

        $local_filename = $details['LocalFilename'];

        // Update the delay as it will be used to calculate the next job start time.
        $repeat_days = (int)$details['RepeatDays'];
        if ($repeat_days) {
            $this->delay = $repeat_days * 86400;
        } else {
            $this->jobsubtype = 0; // This is the last iteration of the job so convert if from perpetual to done.
        }

        // 0 - Perform a integrity/security check to see
        if (!password_verify($this->cid . '_' . $_COMPANY->getRealm(), $details['SecurityHash'])) {
            Logger::Log("Job {$this->jobid} - Fatal Security Check failed", Logger::SEVERITY['SECURITY_ERROR']);
            return;
        }

        // 1 - GET the file
        $file_contents = null; // Handler to store remote file contents
        $local_file_tmpname = TmpFileUtils::GetTemporaryFile($_COMPANY->val('subdomain').'_');

        if ($details['Mechanism'] === 'SFTP') {
            $sftp_hostname = $details['SFTP']['Hostname'];
            $sftp_username = $details['SFTP']['Username'];
            $sftp_password = aes_encrypt($details['SFTP']['EncryptedPassword'], 'pc9cF3l1vZQkHtAg', 'zy3d8ZjZvD93Nlrc', true, false, true);
            $sftp_folder = $details['SFTP']['Folder'];
            $sftp_filename = $details['SFTP']['Filename'];
            $sftp_auth_method = $details['SFTP']['AuthMethod'] ?? 'sftp_password';

            $sftp_hostname_parts = explode(':',$sftp_hostname);
            if (count($sftp_hostname_parts) == 1) { // If port is not provided set it to 22
                $sftp_hostname_parts[] = 22;
            }

            $tmpfilename = tempnam(sys_get_temp_dir(), $_COMPANY->val('subdomain').'_');
            $res = PhpSecLibSftp::Get($tmpfilename, $sftp_hostname_parts[0], $sftp_username, $sftp_password, $sftp_folder, $sftp_filename,$sftp_hostname_parts[1], true, $sftp_auth_method);
            if (!$res['status']) {
                Logger::Log( "Unable to fetch file {$sftp_filename} from {$sftp_hostname}", Logger::SEVERITY['FATAL_ERROR'], $res);
                    return;
                } else {
                    $file_contents = file_get_contents($tmpfilename);
                    $number_of_bytes = strlen($file_contents);
                    Logger::Log("SFTP GET Done - Fetched {$sftp_filename}", Logger::SEVERITY['INFO'], $res);
                    $subject = 'Completed SFTP Get';
                    $message = json_encode(array('filename' => $sftp_filename, 'filesize' => $number_of_bytes), JSON_UNESCAPED_SLASHES);

                    if ($details['IsPgpEncrypted']) {
                        $file_contents = $_COMPANY->decryptWithPGP($file_contents, Company::DEFAULT_PGP_KEY_NAME);
                    }
                    file_put_contents($local_file_tmpname, $file_contents);
                }
            unlink($tmpfilename); // Remove the temp file created above.

        }
        elseif ($details['Mechanism'] === 'HTTPS') {
            $https_url = $details['HTTPS']['URL'];
            $https_username = $details['HTTPS']['Username'];
            $https_password = aes_encrypt($details['HTTPS']['EncryptedPassword'], 'pc9cF3l1vZQkHtAg', 'zy3d8ZjZvD93Nlrc', true, false, true);

            // Get CURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $https_url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Timeout after 5 minutes
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "{$https_username}:{$https_password}");
            $file_contents = curl_exec($ch);
            $curl_info = curl_getinfo($ch);
            curl_close($ch);

            if (!empty($file_contents)) {
                $number_of_bytes = strlen($file_contents);
                $subject = 'Completed HTTPS Get';
                $message = json_encode(array('url' => $https_url, 'filesize' => $number_of_bytes), JSON_UNESCAPED_SLASHES);
                Logger::Log("HTTPS GET Done - " . json_encode($curl_info), Logger::SEVERITY['INFO']);

                //if ($details['IsPgpEncrypted']) {
                //    $file_contents = $_COMPANY->decryptWithPGP($file_contents, Company::DEFAULT_PGP_KEY_NAME);
                //}

                file_put_contents($local_file_tmpname, $file_contents);

            } else {
                Logger::Log("Unable to fetch data from {$https_url}");
                return;
            }
        }
        elseif ($details['Mechanism'] === 'OAuth2') {
            $this->downloadFileUsingOAuth($local_file_tmpname, $details);
            $file_contents = file_get_contents($local_file_tmpname);
            if (!empty($file_contents)) {
                $number_of_bytes = strlen($file_contents);
                $subject = 'Completed HTTPS Get using OAuth2';
                $message = json_encode(array('url' => $details['OAuth2']['GetReportUrl'], 'filesize' => $number_of_bytes), JSON_UNESCAPED_SLASHES);
                Logger::Log("HTTPS OAuth2 GET Done - " . $message, Logger::SEVERITY['INFO']);
            }
        }

        // Store imported contents in a temporary file and upload them to the uploader in file and folder defined by local_filename
        $retVal = $_COMPANY->saveFileInUploader($local_file_tmpname, basename($local_filename), str_replace('/' . basename($local_filename), '', $local_filename));
        unlink($local_file_tmpname);

        if (!empty($retVal)) {
            Logger::Log("DataImportJob - uploaded {$local_filename}, tmp_name={$local_file_tmpname}, encryption=" . ($details['IsPgpEncrypted'] ? 'true': 'false') . "", Logger::SEVERITY['INFO']);

            if (!empty($notifyEmails) && !empty($subject)) {
                $_COMPANY->emailSend2('Teleskope Scheduler', $notifyEmails, $subject, $message, $_ZONE->val('app_type'));
            }
        } else {
            Logger::Log("DataImportJob - Fatal Error upload failed {$local_filename}, tmp_name={$local_file_tmpname}, encryption=" . !$details['IsPgpEncrypted'] . "");
        }
    }

    /**
     * @param string $filename
     * @param array $details
     * @return mixed
     */
    private function downloadFileUsingOAuth(string $filename, array $details)
    {
        $client = new Client();

        $form_params = [];
        if (!empty($details['OAuth2']['oauth_grant_type'])) {
            $form_params['grant_type'] = $details['OAuth2']['oauth_grant_type']; //'refresh_token';
        }

        if (!empty($details['OAuth2']['RefreshToken'])) {
            $form_params['refresh_token'] = CompanyEncKey::Decrypt($details['OAuth2']['RefreshToken']);
            if (empty($form_params['grant_type'])) {
                // If refresh token is provided but grant type is not then set the grant type to referesh token
                $form_params['grant_type'] = 'refresh_token';
            }
        }

        if (!empty($details['OAuth2']['oauth_scope'])) {
            $form_params['scope'] = $details['OAuth2']['oauth_scope'];
        }

        $response = $client->post($details['OAuth2']['GetBearerTokenUrl'], [
            'auth' => [
                $details['OAuth2']['ClientId'],
                CompanyEncKey::Decrypt($details['OAuth2']['ClientSecret']),
            ],
            'form_params' => $form_params,
        ]);

        $json = $response->getBody();
        $response_data = json_decode($json, true);

        $bearer_token = $response_data['access_token'];

        $headers = [
            'Authorization' => 'Bearer ' . $bearer_token,
        ];

        if ($oauth_custom_header_name = ($details['OAuth2']['oauth_custom_header_name'] ?? '')) {
            $headers[$oauth_custom_header_name] = $details['OAuth2']['oauth_custom_header_value'];
        }

        if ($details['OAuth2']['oauth_data_handler'] == 'microsoft_api') {
            $response_data_set = [];
            $iteration = 0;
            $next_report_url = $details['OAuth2']['GetReportUrl'];

            $data_report_url_parsed = parse_url($next_report_url) ?? [];
            if (empty($data_report_url_parsed)) {
                return;
            }
            $data_report_url_host = $data_report_url_parsed['scheme'] . '://' . $data_report_url_parsed['host'];

            while (($iteration++ < 100) && !empty($next_report_url)) {
                $response = $client->get($next_report_url, [
                    'headers' => $headers,
                ]);
                if ($response->getStatusCode() != 200) {
                    $next_report_url = '';
                    break;
                }

                $json = $response->getBody()->getContents();
                $response_data = json_decode($json, true);
                if (empty($response_data)) {
                    $next_report_url = '';
                    break;
                }

                $response_data_newset = $response_data['payload']['value'];
                $number_of_records_in_data_newset = count($response_data_newset);
                Logger::Log("Iteration: {$iteration}, fetched {$number_of_records_in_data_newset} records from URL {$next_report_url}", Logger::SEVERITY['INFO']);

                $response_data_set = array_merge($response_data_newset, $response_data_set);

                // Reset the next URL
                if (!empty($response_data['payload']['@odata.nextlink'])) {
                    $next_link_starts_with = substr($response_data['payload']['@odata.nextlink'], 0, 8);
                    $next_report_url_prefix = substr($next_report_url, 0, strpos($next_report_url, $next_link_starts_with));
                    $next_report_url = $next_report_url_prefix . $response_data['payload']['@odata.nextlink'];
                } else {
                    $next_report_url = '';
                }

            }
            file_put_contents($filename, json_encode(['value'=> $response_data_set]));
            return count($response_data_set);
        } else {
            $response = $client->get($details['OAuth2']['GetReportUrl'], [
                'headers' => $headers,
                'sink' => $filename,
            ]);
        }

        return $response;
    }
}