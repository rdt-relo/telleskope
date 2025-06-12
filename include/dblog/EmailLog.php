<?php
require_once __DIR__ .'/DBLog.php';

/**
 * Class EmailLog
 * This class encapsulates model for EmailLog
 */
class EmailLog extends DBLog
{
    const EMAILLOG_SECTION_TYPES = [
        'post' => 1,
        'event' => 2,
        'newsletter' => 3,
        'message' => 4,
        'discussion' => 5
    ];

    private $urls = null;

    /**
     * If the domain already exists, then domain id is returned otherwise a new record is created and id is returned.
     * @param string $domain
     * @return int
     */
    private static function GetOrCreateDomainId(string $domain): int
    {
        $domain = self::mysqli_escape($domain);
        $rows = self::DBGet("SELECT email_domain_id FROM dblog.email_domains WHERE email_domain='{$domain}'");
        if (!empty($rows)) {
            return $rows[0]['email_domain_id'];
        } else {
            return self::DBUpdateNoDie("INSERT INTO dblog.email_domains (email_domain) VALUES ('{$domain}')");
        }
    }

    /**
     * @param int $logid
     * @return EmailLog|null
     */
    private static function GetByEmailLogId(int $logid): ?EmailLog
    {
        $fields = self::DBGet("SELECT * FROM dblog.email_logs JOIN dblog.email_domains USING (email_domain_id) WHERE email_log_id={$logid}");
        if (!empty($fields)) {
            return new EmailLog($fields[0]);
        }
        return null;
    }

    /**
     * @param string $domain, e.g. gmail.affinties.io or gmail.talentpeak.io
     * @param int $section_type
     * @param int $section_id
     * @param int $version
     * @param string $label
     * @param int $userid
     * @return EmailLog|null
     */
    public static function GetOrCreateEmailLog (string $domain, int $section_type, int $section_id, int $version, string $label, int $userid): ?EmailLog
    {
        $domain_id = self::GetOrCreateDomainId($domain);
        $label = self::mysqli_escape($label);

        if (!$domain_id) {
            return null; // Do not proceed if domain id is not available
        }

        if (!in_array($section_type, array_values(self::EMAILLOG_SECTION_TYPES))) {
            return null; // Do not proceed if invalid section type is provided
        }

        $fields = self::DBGet("SELECT email_log_id FROM dblog.email_logs WHERE email_domain_id={$domain_id} AND section_type={$section_type} AND section_id={$section_id} AND version={$version} AND label='$label'");
        if (!empty($fields)) {
            $logid = (int)$fields[0]['email_log_id'];
        } else {
            // First create a row
            $logid = self::DBUpdateNoDie("INSERT INTO dblog.email_logs SET email_domain_id={$domain_id},section_type={$section_type},section_id={$section_id},version={$version},label='$label',createdby={$userid}");
        }
        return self::GetByEmailLogId($logid);
    }

    /**
     * @param string $domain
     * @param int $section_type
     * @param int $section_id
     * @return array of EmailLog objects, or empty array
     */
    public static function GetAllEmailLogsSummary (string $domain, int $section_type, int $section_id): array
    {
        $domain = self::mysqli_escape($domain);
        $objs = array();
        $fields = self::DBROGet("SELECT * FROM dblog.email_logs JOIN dblog.email_domains USING (email_domain_id) LEFT JOIN dblog.email_log_summary USING (email_log_id) WHERE email_domain='{$domain}' AND section_type={$section_type} AND section_id={$section_id}");
        foreach ($fields as $field) {
            $urlClickDetails = array();
            $rcpts = array();
            if (empty($field['is_summary_current'])) {
                $rcpts = self::DBROGet("SELECT sum(IF(sent_timestamp is null,0,1)) as total_rcpts,sum(IF(first_open_timestamp is null,0,1)) as unique_opens, sum(total_opens) as total_opens FROM dblog.email_log_rcpts WHERE email_log_id={$field['email_log_id']}")[0];
                $urlClicks = self::DBROGet("SELECT email_log_urls.email_url_id, email_log_urls.url, sum(IF(total_clicks is null,0,total_clicks)) as num_of_clicks,sum(IF(first_click_timestamp is null,0,1)) as num_of_unique_clicks FROM dblog.email_log_urls LEFT JOIN dblog.email_log_rcpt_url_clicks USING (email_url_id) WHERE email_log_id={$field['email_log_id']} GROUP BY email_url_id");
                $unique_clicks = array_sum(array_column($urlClicks, 'num_of_unique_clicks'));
                $total_clicks = array_sum(array_column($urlClicks, 'num_of_clicks'));
                $urlClickDetails = array(
                    'unique_clicks' => $unique_clicks,
                    'total_clicks' => $total_clicks,
                    'clickDetails' => $urlClicks
                );

                // Next update the summary for future use.
                $values_str = "total_rcpts='{$rcpts['total_rcpts']}',unique_opens='{$rcpts['unique_opens']}',total_opens='{$rcpts['total_opens']}',unique_clicks='{$unique_clicks}',total_clicks='{$total_clicks}',is_summary_current=1,created_on=NOW()";
                self::DBUpdateNoDie("INSERT INTO dblog.email_log_summary SET email_log_id={$field['email_log_id']},{$values_str} ON DUPLICATE KEY UPDATE {$values_str}");
            } else {
                // Since click details are not available in the summary table instantiate it to empty values.
                $urlClickDetails['clickDetails'] = array();
            }

            $objs[] = new EmailLog(array_merge($field, $rcpts, $urlClickDetails));

            //Logger::Log(json_encode(array_merge($field,$rcpts,$urlClickDetails)));
        }
        return $objs;
    }

    /**
     * This method will return url_id. If the url does not exist in the email log context, a new one will be added first
     * @param string $url
     * @return int
     */
    public function addOrGetUrl(string $url): int
    {
        $url_id = 0;
        // Since the same URL may be used again we cache it for future use in $this->urls
        if ($this->urls === null) {
            // First time here load the urls
            $this->urls = self::DBGet("SELECT email_url_id,url FROM dblog.email_log_urls WHERE email_log_id={$this->val('email_log_id')}");
        }

        foreach ($this->urls as $u) {
            if ($u['url'] === $url) {
                $url_id = (int)$u['email_url_id'];
            }
        }

        if (!$url_id) { // Url id was not found, lets add it
            $url = self::mysqli_escape($url);
            $url_id = self::DBUpdateNoDie("INSERT INTO dblog.email_log_urls (email_log_id,url) VALUES ({$this->val('email_log_id')},'{$url}')");
            // Also reset $this->urls to null so that it is reloaded on next get
            $this->urls = null;
        }

        return $url_id;
    }

    /**
     * This method adds or gets rcpt_id for a given userid or email. Note userid is primary identifier and if provided
     * email is ignored. Email is used only as a secondary identifier.
     * Returns encrypted identity that can be used in the open email or url click urls
     * @param int $rcpt_userid
     * @param string $rcpt_email
     * @return string, '' means entry was not found and counld not be created, otherwise encrypted identity is returned
     */
    public function addOrGetRcptByUseridOrEmail(int $rcpt_userid, string $rcpt_email=''): string
    {
        $rcpt_id = 0;
        $val_set = '';

        if ($rcpt_userid) {
            $val_set = "rcpt_userid={$rcpt_userid}";
        } elseif (!empty($rcpt_email)) {
            $rcpt_email = self::mysqli_escape($rcpt_email);
            $val_set= "rcpt_email='{$rcpt_email}'";
        } else {
            return ''; // Cannot process as either userid or email is needed.
        }

        $row = self::DBGet("SELECT email_rcpt_id,sent_timestamp FROM dblog.email_log_rcpts WHERE email_log_id={$this->val('email_log_id')} AND {$val_set}");
        if (!empty($row)) {
            $rcpt_id = (int)$row[0]['email_rcpt_id'];
            if (empty($row[0]['sent_timestamp'])) { // If email was resent,
                self::DBUpdateNoDie("UPDATE dblog.email_log_rcpts SET sent_timestamp=now() WHERE email_rcpt_id={$rcpt_id}");
            }
        } else {
            // Add a new row
            $rcpt_id = self::DBUpdateNoDie("INSERT INTO dblog.email_log_rcpts SET email_log_id={$this->val('email_log_id')}, {$val_set},sent_timestamp=now()");
        }

        if ($rcpt_id) {
            return self::IdEncode($this->val('email_domain'), (int)$this->val('email_log_id'),$rcpt_id);
        }
        return '';
    }

    /**
     * This method is used to reset RCPT sent timestamp to null; for example if email send failed for
     * whatever reason call this method to reset the sent timestamp.
     * @param string $encoded_identity
     * @return int
     */
    public function resetRcptSentTimestampToNull (string $encoded_identity): int
    {
        list($email_log_id,$email_rcpt_id) = self::IdDecode($this->val('email_domain'),$encoded_identity);
        if ($email_log_id && $email_rcpt_id) {
            return self::DBUpdateNoDie("UPDATE dblog.email_log_rcpts SET sent_timestamp=null,first_open_timestamp=null,total_opens=0 WHERE email_rcpt_id={$email_rcpt_id}");
        }
        return 0;
    }

    /**
     * This method registers a URL click. URL click is registered at the timestamp provided.
     * @param string $realm
     * @param string $encoded_identity
     * @param int $email_url_id
     * @param int $timestamp unix timestamp
     * @return int 0 on error and 1 on success
     */
    public static function RegisterUrlClick (string $realm, string $encoded_identity, int $email_url_id, int $timestamp):int {
        list($email_log_id,$email_rcpt_id) = self::IdDecode($realm,$encoded_identity);
        if ($email_log_id && $email_rcpt_id) {
            $cnt = (int)self::DBROGet("SELECT count(1) as cnt FROM dblog.email_log_urls WHERE email_url_id={$email_url_id} AND email_log_id={$email_log_id}")[0]['cnt'];
            if ($cnt == 1) { // Only proceed if the URL is has a matching row for urlid and logid

                if (!self::DBUpdateNoDie("UPDATE dblog.email_log_rcpt_url_clicks SET last_click_timestamp=FROM_UNIXTIME({$timestamp}),total_clicks=total_clicks+1 WHERE email_rcpt_id={$email_rcpt_id} AND email_url_id={$email_url_id}")) {
                    // For new clicks ... just mark summary as stale so that we calculate fresh values the next time we need them.
                    self::DBUpdateNoDie("UPDATE dblog.email_log_summary SET is_summary_current=0 WHERE email_log_id={$email_log_id}");
                    return self::DBUpdateNoDie("INSERT IGNORE INTO dblog.email_log_rcpt_url_clicks SET first_click_timestamp=FROM_UNIXTIME({$timestamp}),last_click_timestamp=FROM_UNIXTIME({$timestamp}),total_clicks=1,email_rcpt_id={$email_rcpt_id},email_url_id={$email_url_id}");
                }

                // For duplicate clicks ... just increment the total clicks flag.
                self::DBUpdateNoDie("UPDATE dblog.email_log_summary SET total_clicks=total_clicks+1 WHERE email_log_id={$email_log_id}");
                return 1;
            }
        }
        return 0;
    }

    /**
     * This method registers an email open. Email open is registered at the timestamp provided.
     * @param string $realm
     * @param string $encoded_identity
     * @param int $timestamp unix timestamp
     * @return int 0 on error and 1 on success
     */
    public static function RegisterEmailOpen (string $realm, string $encoded_identity, int $timestamp) :int {
        list($email_log_id,$email_rcpt_id) = self::IdDecode($realm,$encoded_identity);
        if ($email_log_id && $email_rcpt_id) {
            self::DBUpdateNoDie("UPDATE dblog.email_log_summary SET is_summary_current=0 WHERE email_log_id={$email_log_id}");
            return self::DBUpdateNoDie("UPDATE dblog.email_log_rcpts SET last_open_timestamp=FROM_UNIXTIME({$timestamp}),total_opens=email_log_rcpts.total_opens+1,first_open_timestamp=IF(first_open_timestamp IS NULL, FROM_UNIXTIME($timestamp), first_open_timestamp) WHERE email_log_id={$email_log_id} AND email_rcpt_id={$email_rcpt_id}");
        }
        return 0;
    }

    /**
     * Give a template for Tracking Pixel
     * You will need to replace {{EMAILLOG_ENC_USER}} with encrypted user identity that is generated by
     * encryptIdentity(...)
     * @return string returns pixel <img> html
     */
    public function getEmailOpenPixelTemplate (): string {
        $pixelUrl = DBLOG_EMAIL_OPN_URL.'?f=opn&i=___EMAILLOG_ENC_USER___&r='.$this->val('email_domain');
        return '<img id="log" height="1" width="1" border="0" src="'.$pixelUrl.'">';
    }

    /**
     * Given a updated URL to track URL clicks. Please note this is a template.
     * You will need to replace {{EMAILLOG_ENC_USER}} with encrypted user identity that is generated by
     * encryptIdentity(...)
     * @param string $url
     * @return string
     */
    private function getUrlClickTemplate (string $url): string {
        $email_url_id = self::addOrGetUrl($url);
        return DBLOG_EMAIL_CLK_URL.'?f=clk&i=___EMAILLOG_ENC_USER___&r='.$this->val('email_domain').'&u='.$email_url_id.'&re='.urlencode($url);
    }

    public function updateHtmlToTrackUrlClicks($html)
    {
        if (empty($html)) {
            return $html;
        }

        $replacements = array();
        // Note we are using DOM only to extract the <a> tags, we will rewrite them using str_replace to not mess partial html that we have
        $dom = new DOMDocument();
        try {
            $domLoaded = @$dom->loadHTML($html); //Dom load throws a warning if there are figures, using @ to subdue it
            if (!$domLoaded) {
                Logger::Log("EmailLog was unable to parse the html, urls will not be tracked", Logger::SEVERITY['WARNING_ERROR']);
                return $html;
            }
        } catch (Error $e) {
            Logger::Log("EmailLog was unable to parse the html and urls will not be tracked, caught error {$e}", Logger::SEVERITY['WARNING_ERROR']);
            return $html;
        }

        foreach($dom->getElementsByTagName('a') as $anchor) {
            $link = $anchor->getAttribute('href');
            if (stripos($link,'mailto:') !== false) {
                // This link is a mail to link.... skip it (note we are looking for href to start with a mailto:
                continue;
            }
            if(stripos($link, 'https://urldefense.com') !== false) {
                // This link is for URL defense.... skip it as it causes the following error
                // Compilation failed: quantifier does not follow a repeatable ite
                continue;
            }
            $new_link = $this->getUrlClickTemplate($link);
            if (!isset($replacements[$link])) {
                $what = '`href=[\'"]'.$link.'[\'"]`'; //Using backtick for pattern boundary
                $with = 'href="'.$new_link.'"';
                $replacements[$what] = $with;
            }
        }
        return preg_replace(array_keys($replacements),array_values($replacements),$html) ?? $html;
    }

    /**
     * Generates an encoded id string
     * @param string $realm
     * @param int $email_log_id
     * @param int $email_rcpt_id
     * @return string
     */
    private static function IdEncode (string $realm, int $email_log_id, int $email_rcpt_id) : string
    {
        $ids = $email_log_id.'-'.$email_rcpt_id;
        return $ids.'.'.crc32($ids. 'w2qs_-_y760dl' .$realm);
    }

    /**
     * @param string $realm
     * @param string $what the string to decode. It is expected to be in id1.id2.crc32 format
     * @return array returns array of two ids ($email_log_id, $email_log_rcpt_id)
     */
    private static function IdDecode (string $realm, string $what) : array
    {
        $what_parts = explode('.',$what);
        $crc32_calc = crc32($what_parts[0]. 'w2qs_-_y760dl' .$realm);
        if (count($what_parts)== 2 && $crc32_calc === (int)$what_parts[1]) {
            $ids = explode('-',$what_parts[0]);
            return array((int)$ids[0], (int)$ids[1]);
        }
        return array(0,0);
    }
}


// ************************
// Test
//require_once __DIR__ . '/../include/dblog/EmailLog.php';
//echo "<h4>Starting...</h4>";
//$el_1 = EmailLog::GetOrCreateEmailLog('gmail2.affinities.io',1,3,1,'invite',1);
//$el_2 = EmailLog::GetOrCreateEmailLog('gmail2.affinities.io',1,3,1,'update',1);
//$el_3 = EmailLog::GetOrCreateEmailLog('gmail2.affinities.io',1,3,2,'update',1);
//$el_4 = EmailLog::GetOrCreateEmailLog('gmail2.affinities.io' ,1,3,2,'invite',1);
//$all = Emaillog::GetAllEmailLogsSummary('gmail2.affinities.io',1,3);
//<ul>
//    <?php
//    foreach ($all as $a) {
//        //$r1=$a->addOrGetRcptByUseridOrEmail(0,'asbrar@hotmail.com');
//        $r2e=$a->addOrGetRcptByUseridOrEmail(3);
//        echo "<li>".htmlspecialchars($a->getEmailOpenPixelTemplate())." *********  &emsp;[{$a->val('label')}-{$a->val('version')} ... {$a->val('unique_opens')} / {$a->val('total_rcpts')}]</li>";
//        EmailLog::RegisterEmailOpen($r2e,time());
//        EmailLog::RegisterUrlClick($r2e,13,time());
//    }
//
//</ul>
// **************************
