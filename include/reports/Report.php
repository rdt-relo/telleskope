<?php
// Do no use require_once as this class is included in Company.php.
date_default_timezone_set('UTC');

class Report extends Teleskope
{
    const REPORT_TYPE_USER_MEMBERSHIP = 1;
    const REPORT_TYPE_EVENT = 2;
    const REPORT_TYPE_BUDGET = 3;
    const REPORT_TYPE_SURVEY = 4;
    const REPORT_TYPE_EVENT_RSVP = 5;
    const REPORT_TYPE_ANNOUNCEMENT = 6;
    const REPORT_TYPE_OFFICELOCATIONS = 7;
    const REPORT_TYPE_GROUP_DETAILS = 8;
    const REPORT_TYPE_TEAM_USER = 9;
    const REPORT_TYPE_TEAM_MEMBERS = 10;
    const REPORT_TYPE_TEAM_REGISTRATIONS = 11;
    const REPORT_TYPE_BUDGET_YEAR = 12;
    const REPORT_TYPE_BUDGET_CHARGE_CODE = 13;
    const REPORT_TYPE_ADMINS = 14;
    const REPORT_TYPE_LEADTYPE = 15;
    const REPORT_TYPE_EVENT_TYPE = 16;
    const REPORT_TYPE_EVENT_SPEAKER = 17;
    const REPORT_TYPE_EVENT_VOLUNTEERS = 18;
    const REPORT_TYPE_BUDGET_EXPENSE_TYPE = 19;
    const REPORT_TYPE_REGIONS = 20;
    const REPORT_TYPE_RECOGNITION = 21;
    const REPORT_TYPE_LOGINS = 22;
    const REPORT_TYPE_USER_AUDIT_LOGS = 23;
    const REPORT_TYPE_ZONE_STATS = 24;
    const REPORT_TYPE_SURVEY_DATA = 25;
    const REPORT_TYPE_DISCLSIMER_CONTENTS = 26;
    const REPORT_TYPE_EVENT_RECORDING_LINK_CLICKS = 27;
    const REPORT_TYPE_EVENT_SURVEY = 28;
    const REPORT_TYPE_TEAM_FEEDBACK = 29;
    const REPORT_TYPE_TEAM_TEAMS = 30;
    const REPORT_TYPE_NEWSLETTERS = 31;
    const REPORT_TYPE_GROUP_JOIN_REQUESTS = 32;
    const REPORT_TYPE_POINTS_TRANSACTIONS = 33;
    const REPORT_TYPE_GROUPCHAPTER_LOCATION = 34;
    const REPORT_TYPE_TEAM_REQUESTS = 35;
    const REPORT_TYPE_POINTS_BALANCE = 36;
    const REPORT_TYPE_DIRECT_MAIL = 37;
    const REPORT_TYPE_APPROVALS = 38;
    const REPORT_TYPE_EVENT_ORGANIZATION = 39;
    const REPORT_TYPE_ORGANIZATION = 40;
    const REPORT_DELEGATED_ACCESS_AUDIT_LOG = 41;
    const REPORT_TYPE_GROUP_CHAPTER_DETAILS = 42;
    const REPORT_TYPE_GROUP_CHANNEL_DETAILS = 43;
    const REPORT_TYPE_USERS = 44;
    const REPORT_TYPE_SCHEDULES = 45;
    const REPORT_TYPE_SCHEDULE_SLOTS = 46;    

    // Custom Internal temporary use reports
    const REPORT_TYPE_NEWSLETTERS_INTERNAL = 1000;
    const REPORT_TYPE_ANNOUNCEMENT_INTERNAL = 1001;
    const REPORT_TYPE_EMAIL_INTERNAL = 1002;
    const REPORT_TYPE_EVENTS_INTERNAL = 1003;

    const CONTENT_STATUS_MAP = array(0 => 'Cancelled', 1 => 'Published', 2 => 'Draft', 3 => 'Under Review', 4 => 'Reviewed', 5 => 'Pending Publish');
    const EVENT_VENUE_STATUS_MAP = array(1 => 'In-Person', 2 => 'Virtual (Web Conference)', 3 => 'In-Person & Virtual (Web Conference)', 4 => 'Other');
    const REQUEST_STATUS_MAP = array(0 => 'Inactive', 1 => 'Active', 2 => 'Paused');
    const APPROVAL_STATUS_MAP = array(1 => "Request", 2 => "Approved", 3 => "Denied");
    const TEAM_STATUS_MAP = array(0 => 'Inactive', 1 => 'Active', 2 => 'Draft', 100 => 'Delete', 110 => 'Complete', 109 => 'Incomplete', 108 => 'Paused');
    const USER_STATUS_MAP = array(0 => 'Inactive', 1 => 'Active', 2 => 'Pending Verification', 3 => 'Account Locked', 101 => 'User Initiated Account Deletion', 100 => 'Marked as Deleted', 102 => 'User Blocked');
    const TEAM_REQUEST_STATUS_MAP = array(0 => 'Rejected', 1 => 'Sent', 2 => 'Accepted', 3 => 'Canceled', 4 => 'Withdrawn');

    protected $dbro_conn = null;
    protected $reportid;
    protected $cache = array();
    protected $policy_limit = '';
    private ?array $tskp_custom_fields = null;

    protected function __construct(int $cid, array $fields)
    {
        global $_USER; /* @var User $_USER */
        parent::__construct(-1, $cid, $fields);
        $this->reportid = $fields['reportid'];
        // If the user is a support user masq'ed in, then limit the report records to 5.
        $this->policy_limit = (isset($_USER) && $_USER && $_USER->isMasqdIn()) ? ' LIMIT 1 ' : '';
    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;
        /* @var Company $_COMPANY */
        global $_ZONE;
        $reportmeta = null;
        $reportType = static::GetReportType();
        $row = self::DBGet("SELECT * FROM company_reports WHERE companyid={$_COMPANY->id()} AND (zoneid={$_ZONE->id()} AND reporttype={$reportType} AND purpose='download' AND isactive=1) LIMIT 1");
        if (count($row) && $row[0]['reportmeta']) {
            $reportmeta = json_decode($row[0]['reportmeta'], true);
        } else {
            $reportmeta = static::GetReportMeta();
        }
        return $reportmeta;
    }

    public function __destruct()
    {
        if ($this->dbro_conn) {
            mysqli_close($this->dbro_conn);
        }
    }

    protected static function GetReportType() : int { return 0;}
    protected static function GetReportMeta() : array { return [];}

    protected function _generateReport ($file_h, string $delimiter=",", string $enclosure='"', string $escape_char="\\"): void
    {
        // Will be implmented in subclass
    }

    /**
     * Removes character 0 from file.
     * @param string $tmp_filename
     * @param string $delimiter
     * @return false|string
     */
    private function csv2Json(string $tmp_filename)
    {
        $json_header = NULL;
        $json_data = array();
        $tmp_filename2 = TmpFileUtils::GetTemporaryFile($tmp_filename.'_');
        $comma = '';
        if (($handle_r = fopen($tmp_filename, 'r')) !== FALSE &&
            ($handle_w = fopen($tmp_filename2, 'w')) !== FALSE) {
            fputs($handle_w,'[');
            while (($row = fgetcsv($handle_r, 1000)) !== FALSE) {
                if (!$json_header)
                    $json_header = $row;
                else {
                    $json_data = array_combine($json_header, $row);
                    fputs($handle_w,$comma.json_encode($json_data, JSON_INVALID_UTF8_SUBSTITUTE));
                    $comma = ',';
                }
            }
            fputs($handle_w,']');
            fclose($handle_r);
            fclose($handle_w);
            unlink($tmp_filename);
            $tmp_filename = $tmp_filename2; // Update the tmp_filename
        }
        return $tmp_filename; // And return it
    }

    /**
     * This method will be implemented in the subclasses
     * @param int $format one of the valid formats defined in Report::REPORT_FORMAT_*
     * @param bool $zipit if set to true then zip will be returned
     * @return string filename of the file
     */
    public function generateReport(int $format, bool $zipit = false, string $zip_password = '') : string
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        // Security Check
        // - validate the global Company context matches the company for the report
        // - or the report is for global use i.e. $this->cid() == 0
        if (!$_COMPANY || ($this->cid() && ($_COMPANY->id() != $this->cid()))) {
            Logger::Log("Fatal: Cannot generate report {$this->id} that does not belong to the company {$_COMPANY->id()}");
            return '';
        }

        if (empty($this->fields))
            return '';

        // Pre-processing
        $delimiter = ",";
        $enclosure = '"';
        $escape_char = "\\";
        if ($format === self::FILE_FORMAT_TSV || $format === self::FILE_FORMAT_XLS) {
            $delimiter = "\t";
            $enclosure = chr(0);
        }

        ini_set('memory_limit', '1024M');

        // Generate Report
        $tmp_filename = TmpFileUtils::GetTemporaryFile($_COMPANY->val('subdomain').'_');
        $file_h = fopen($tmp_filename, 'w');
        $this->_generateReport($file_h, $delimiter, $enclosure, $escape_char);
        $ext = '.csv';
        fclose($file_h);

        // Post processing
        if ($format === self::FILE_FORMAT_TSV) {
            // Remove chr(0);
            $ext = '.tsv';
            file_put_contents($tmp_filename, str_replace(chr(0), '', file_get_contents($tmp_filename)));

        } elseif ($format === self::FILE_FORMAT_XLS) {
            // Remove chr(0);
            $ext = '.xls';
            file_put_contents($tmp_filename, str_replace(chr(0), '', file_get_contents($tmp_filename)));

        } elseif ($format === self::FILE_FORMAT_JSON) {
            $ext = '.json';
            $tmp_filename = $this->csv2Json($tmp_filename);
        }

        if ($zipit) {
            $zip = new ZipArchive();
            $tmp_zip_filename = TmpFileUtils::GetTemporaryFile($tmp_filename.'_zip_');
            unlink($tmp_zip_filename); //Unlinking to suppress warnings as zip archives just needs file path not a created file.
            if ($zip->open($tmp_zip_filename, ZipArchive::CREATE)!==TRUE) {
                Logger::Log("Fatal Error: Unable to zip file {$tmp_filename}");
            } else {
                //$zip->addFromString($filename, 'STRING as file');
                $filename = 'Confidential_do_not_share-report-'.time().$ext;
                $zip->addFile($tmp_filename, $filename);
                if ($zip_password) {
                    $encrypted = $zip->setEncryptionName($filename, ZipArchive::EM_AES_256, $zip_password);
                    if (!$encrypted) {
                        Logger::Log("Unable to encrypt file ");
                        return '';
                    }
                }
                $zip->close();
                return $tmp_zip_filename;
            }
        }

        return $tmp_filename;
    }

    public function generateReportAsAssocArray () : array
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        // Security Check
        // - validate the global Company context matches the company for the report
        // - or the report is for global use i.e. $this->cid() == 0
        if (!$_COMPANY || ($this->cid() && ($_COMPANY->id() != $this->cid()))) {
            Logger::Log("Fatal: Cannot generate report {$this->id} that does not belong to the company {$_COMPANY->id()}");
            return [];
        }

        if (empty($this->fields))
            return [];

        $file_mem = fopen('php://memory','r+');
        $this->_generateReport($file_mem);
        rewind($file_mem);

        $retVal = [];
        $header = fgetcsv($file_mem);
        if (!empty($header)) {
            while (($row = fgetcsv($file_mem)) !== FALSE) {
                $entry = array_combine($header, $row);
                $retVal[] = $entry;
            }
        }
        fclose($file_mem);
        return $retVal;
    }

    /**
     * Gets array with branchid,branchname,branchtype,city,`state`,country,zipcode,region for a given branchid
     * @param int $branchid
     * @return mixed|string[]|void
     */
    protected function getBranchAndRegionValues (int $branchid)
    {
        if ($branchid) {
            if (empty($this->cache['companybranches'])) { // Seed the cache if it is not set.
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT branchid,branchname,branchtype,city,`state`,country,zipcode,region FROM companybranches LEFT JOIN regions USING(regionid) WHERE companybranches.companyid={$this->cid()} AND companybranches.isactive=1";
                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));               

                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['companybranches'][intval($rows['branchid'])] = $rows;
                }
            }

            if (!empty($this->cache['companybranches'][$branchid])) {
                return $this->cache['companybranches'][$branchid];
            }
        }
        return array('branchid'=>'','branchname'=>'','branchtype'=>'','city'=>'','state'=>'','country'=>'','zipcode'=>'','region'=>'');
    }

    /**
     * Regurns array with department values like department
     * @param int $departmentid
     * @return mixed|string[]|void
     */
    protected function getDepartmentValues (int $departmentid)
    {
        if ($departmentid) {
            if (empty($this->cache['departments'])) { // Seed the cache if it is not set.
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT departmentid,department FROM departments WHERE companyid={$this->cid()} AND isactive=1";
                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));                

                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['departments'][intval($rows['departmentid'])] = $rows;
                }
            }

            if (!empty($this->cache['departments'][$departmentid])) {
                return $this->cache['departments'][$departmentid];
            }
        }
        return array('departmentid'=>'','department'=>'');
    }

    protected function getLeadTypeValues (int $typeid)
    {
        if ($typeid) {
            if (empty($this->cache['leadtypes'])) { // Seed the cache if it is not set.
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT typeid,type as rolename,allow_create_content,allow_publish_content,allow_manage FROM grouplead_type WHERE companyid={$this->cid()} AND isactive=1";
                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));                

                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['leadtypes'][intval($rows['typeid'])] = $rows;
                }
            }

            if (!empty($this->cache['leadtypes'][$typeid])) {
                return $this->cache['leadtypes'][$typeid];
            }
        }
        return array('typeid'=>'','rolename'=>'','allow_create_content'=>'','allow_publish_content'=>'','allow_manage'=>'');
    }

    protected  function getGroup (int $groupid) : array
    {
        global $_ZONE;
        if ($groupid) {
            if (empty($this->cache['groups'])) { // Seed the cache if it is not set.
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT groupid,groupname,regionid AS `regionids` FROM `groups` WHERE companyid={$this->cid()} AND isactive=1";
                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['groups'][intval($rows['groupid'])] = $rows;
                }
            }
            if (!empty($this->cache['groups'][$groupid])) {
                return $this->cache['groups'][$groupid];
            }
        }
        return [
            'groupid' => $groupid,
            'groupname' => $groupid ? '' : 'Global', // Global for groupid == 0, empty otherwise
            'regionid' => '0'
        ];
    }
    protected function getGroupName (int $groupid):string
    {
        return $this->getGroup($groupid)['groupname'];
    }

    protected function getGroupNamesAsCSV (string $groupids) {
        $retVal = array();
        $gids = $groupids ? explode(',',$groupids) : ['0'];
        foreach ($gids as $gid) {
            $retVal[] = $this->getGroupName($gid);
        }
        $retVal = array_filter(array_unique($retVal));
        if (count($retVal)) {
            return implode(',',$retVal);
        } else {
            return '';
        }
    }

    protected function getChapter (int $chapterid) : array
    {
        if ($chapterid) {
            if (empty($this->cache['chapters'])) {
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT chapterid,chaptername,regionids FROM chapters WHERE companyid={$this->cid()} AND isactive=1";
                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['chapters'][intval($rows['chapterid'])] = $rows;
                }
            }

            if (!empty($this->cache['chapters'][$chapterid])) {
                return $this->cache['chapters'][$chapterid];
            }
        }
        return [
            'chaptername' => '',
            'regionids' => 0
        ];
    }

    protected function getChapterName (int $chapterid):string
    {
        return $this->getChapter($chapterid)['chaptername'];
    }

    protected function getRegionName (int $regionid):string
    {
        if ($regionid) {
            if (empty($this->cache['regions'])) {
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT regionid, region FROM regions WHERE companyid={$this->cid()} AND isactive=1";
                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt' => $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['regions'][intval($rows['regionid'])] = $rows;
                }
            }

            if (!empty($this->cache['regions'][$regionid])) {
                return $this->cache['regions'][$regionid]['region'];
            }
        }
        return '';
    }

    protected function getChapterCSVRegion(?string $chapterids): string
    {
        if (!empty($chapterids)) {
            $chapterIdsArray = explode(',', $chapterids);
            $region_names=[];

            foreach ($chapterIdsArray as $chpid) {
                if (!$chpid) continue;
                $regionids = explode(',', $this->getChapter((int)$chpid)['regionids']) ?? [];
                foreach ($regionids as $regionid) {
                    $region_name = $this->getRegionName((int)$regionid);
                    if (!empty($region_name)) {
                        $region_names[] = $region_name;
                    }
                }
            }
            $region_names = array_unique($region_names);
            sort($region_names);
            return implode(',', $region_names);
        }
        return '';
    }

    protected function getGroupCSVRegion(string $groupids): string
    {
        if (!empty($groupids)) {
            $groupIdsArray = explode(',', $groupids);
            $region_names=[];

            foreach ($groupIdsArray as $grpid) {
                if (!$grpid) continue;
                $regionids = explode(',', $this->getGroup((int)$grpid)['regionids']) ?? [];
                foreach ($regionids as $regionid) {
                    $region_name = $this->getRegionName((int)$regionid);
                    if (!empty($region_name)) {
                        $region_names[] = $region_name;
                    }
                }
            }
            $region_names = array_unique($region_names);
            sort($region_names);
            return implode(',', $region_names);
        }
        return '';
    }

    protected function getRegionNamesAsCSV(?string $regionids): string
    {
        if (!empty($regionids)) {
            $regionIdsArray = explode(',', $regionids);
            $region_names=[];

            foreach ($regionIdsArray as $regionid) {
                if (!$regionid) continue;
                    $region_name = $this->getRegionName((int)$regionid);
                    if (!empty($region_name)) {
                        $region_names[] = $region_name;
                    }
            }
            $region_names = array_unique($region_names);
            sort($region_names);
            return implode(',', $region_names);
        }
        return '';
    }

    protected function getChapterNamesAsCSV (string $chapterids) {
        $retVal = array();
        $cids = $chapterids ? explode(',',$chapterids) : ['0'];
        foreach ($cids as $cid) {
            $retVal[] = $this->getChapterName($cid);
        }
        $retVal = array_unique($retVal);
        if (count($retVal)) {
            return implode(',',$retVal);
        } else {
            return '';
        }
    }

    protected function getChannelName (int $channelid): string {
        if ($channelid) {
            if (empty($this->cache['channels'])) {
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT channelid,channelname FROM group_channels WHERE companyid={$this->cid()} AND isactive=1";
                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['channels'][intval($rows['channelid'])] = $rows;
                }
            }

            if (!empty($this->cache['channels'][$channelid])) {
                return $this->cache['channels'][$channelid]['channelname'];
            }
        }
        return '';
    }

    protected function getChannelNamesAsCSV (string $channelids) {
        $retVal = array();
        $cids = $channelids ? explode(',',$channelids) : ['0'];
        foreach ($cids as $cid) {
            $retVal[] = $this->getChannelName($cid);
        }
        $retVal = array_unique($retVal);
        if (count($retVal)) {
            return implode(',',$retVal);
        } else {
            return '';
        }
    }

    protected function getUserGroupIdsAsCSV(int $userid)
    {
        if (empty($this->cache['user_groupids'][$userid])) {
            $dbc = GlobalGetDBROConnection();
            // Note: Anonymous members are not to be included *** Data Protection Requirement ***
            $select = "SELECT IFNULL(GROUP_CONCAT(groupid),'') AS groupids FROM groupmembers WHERE userid={$userid} AND anonymous=0 AND isactive=1";
            $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
            if (@$rows = mysqli_fetch_assoc($result)) {
                $this->cache['user_groupids'][$userid] = $rows['groupids'];
            } else {
                $this->cache['user_groupids'][$userid] = '';
            }
            mysqli_free_result($result);
        }
        return (string)($this->cache['user_groupids'][$userid] ?: '');
    }

    protected function getHomezoneNamesAsCSV (string $zonedis) {
        global $_COMPANY;
        $user_zoneids = empty($zonedis) ? [] : explode(',', $zonedis) ;
        $zonenames = array();
        foreach ($user_zoneids as $user_zoneid) {
            $user_zoneid = intval($user_zoneid);

            if (empty($this->cache['zoneids'][$user_zoneid])) {
                $zone = $_COMPANY->getZone($user_zoneid);
                if ($zone) {
                    $this->cache['zoneids'][$user_zoneid] = $zone->val('zonename');
                }
            }
            if (!empty($this->cache['zoneids'][$user_zoneid])) {
                $zonenames[] = $this->cache['zoneids'][$user_zoneid];
            }
        }
        return array('homezone' => implode(', ', $zonenames));
    }

    protected function getHashtag (int $hashtagid) : string
    {
        if ($hashtagid) {
            if (empty($this->cache['hashtags'])) {
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT hashtagid,handle FROM handle_hashtags WHERE companyid={$this->cid()}";
                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['hashtags'][intval($rows['hashtagid'])] = $rows;
                }
            }
            return $this->cache['hashtags'][$hashtagid]['handle'] ?? '';
        }
        return '';
    }
    protected function getHashtagsAsCSV (string $hashtagids) {
        $retVal = array();
        $hids = $hashtagids ? explode(',',$hashtagids) : [];
        foreach ($hids as $hid) {
            $retVal[] = $this->getHashtag($hid);
        }
        $retVal = array_unique($retVal);
        if ($retVal) {
            return implode(',',$retVal);
        } else {
            return '';
        }
    }

    protected function getTagName(int $tagid): string{
        global $_COMPANY, $_ZONE;
        if($tagid){
            if (empty($this->cache['tagnames'][$tagid])) {
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT `tagid`,`tag` FROM `group_tags` WHERE tagid={$tagid} AND `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()}";
                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['tagnames'][intval($rows['tagid'])] = $rows;
                }
            }
            return $this->cache['tagnames'][$tagid]['tag'] ?? '';
        }
        return '';
    }
    protected function getTagNameAsCSV(string $tagids): string{
        if (!empty($tagids)) {
            $tagIdsArray = explode(',', $tagids);
            $tags=[];

            foreach ($tagIdsArray as $tagid) {
                if (!$tagid) continue;
                $tags[] = $this->getTagName((int)$tagid);
            }
            $tags = array_unique($tags);
            return implode(',', $tags);
        }
        return '';
    }

    protected function getUser(int $userid) : ?User
    {
        if (empty($this->cache['users'][$userid])) {
            $this->cache['users'][$userid] = User::GetUser($userid) ?? User::GetEmptyUser($userid);
        }
        return $this->cache['users'][$userid];
    }

    public static function GetReportRec(string $reportid): array
    {
        global $_COMPANY; /* @var Company $_COMPANY */

        return self::DBGetPS("SELECT * FROM company_reports WHERE companyid=? AND (reportid=? AND isactive=1) LIMIT 1",'ix',$_COMPANY->id(), $reportid);
    }

    /**
     * Creates report record. Generates a unique report id as well.
     * @param string $reportname
     * @param string $reportdescription
     * @param int $reporttype
     * @param string $reportmeta
     * @return int
     */
    public static function CreateReportRec (string $reportname, string $reportdescription, int $reporttype, string $purpose, string $reportmeta) : string
    {
        global $_COMPANY,$_ZONE; /* @var Company $_COMPANY */
        global $_USER; /* @var User $_USER */

        $reportid = $_COMPANY->val('subdomain')."_".time();
        $q = self::DBUpdatePS("INSERT INTO company_reports (reportid, companyid, zoneid,reportname, reportdescription, reporttype, purpose, reportmeta, createdby) VALUE (?,?,?,?,?,?,?,?,?)",'xiissisxi',$reportid,$_COMPANY->id(),$_ZONE->id(),$reportname,$reportdescription,$reporttype,$purpose, $reportmeta,$_USER->id());
        if ($q) {
            return $reportid;
        } else {
            return '';
        }
    }

    /**
     * Updates the report record. Note companyid and reporttype cannot be updated. Also isactive has been left out as we do not want to use this method for isactive update
     * @param string $reportid
     * @param string $reportname
     * @param string $reportdescription
     * @param string $reportmeta
     * @return int
     */
    public static function UpdateReportRec (string $reportid, string $reportname, string $reportdescription, int $reporttype, string $purpose, string $reportmeta) : int
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        return self::DBUpdatePS("UPDATE company_reports SET reportname=?, reportdescription=?, reporttype=?, purpose=?, reportmeta=? WHERE companyid=? AND (reportid=?)",'ssisxis',$reportname,$reportdescription,$reporttype,$purpose,$reportmeta,$_COMPANY->id(), $reportid);
    }

    /**
     * Marks the report for deletion
     * @param string $reportid
     * @return int
     */
    public static function DeleteReportRec (string $reportid) : int
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        return self::DBUpdatePS("UPDATE company_reports SET isactive=100 WHERE companyid=? AND (reportid=?)",'ix',$_COMPANY->id(),$reportid);
    }

    /**
     * @param string $purpose downlod or transfer, all types of reports returned if purpose not provided
     * @return array
     */
    public static function GetAllReportsInCompany (string $purpose=''): array
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        $purpose_filter = '' ;
        if (in_array($purpose, array('download','transfer'))) {
            $purpose_filter = " AND purpose='{$purpose}'";
        }
        return self::DBGet("SELECT reportid,reportname,reporttype,zoneid FROM company_reports WHERE companyid={$_COMPANY->id()} AND isactive=1 {$purpose_filter}");
    }

    protected function getTeamName (int $teamid): string {
        if ($teamid) {
            if (empty($this->cache['teams'])) {
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT teamid,team_name FROM teams WHERE companyid={$this->cid()}";
                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['teams'][intval($rows['teamid'])] = $rows;
                }
            }

            if (!empty($this->cache['teams'][$teamid])) {
                return $this->cache['teams'][$teamid]['team_name'];
            }
        }
        return '';
    }

    protected function getTeamRoleTypeValues (int $roleid)
    {
        if ($roleid) {
            if (empty($this->cache['teamRoleTpes'])) { // Seed the cache if it is not set.
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT roleid,`type` as rolename FROM team_role_type WHERE companyid={$this->cid()}";
                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['teamRoleTpes'][intval($rows['roleid'])] = $rows;
                }
            }

            if (!empty($this->cache['teamRoleTpes'][$roleid])) {
                return $this->cache['teamRoleTpes'][$roleid];
            }
        }
        return array('roleid'=>'','rolename'=>'');
    }

    protected function getEventVolunteerType (int $typeid): string
    {
        if ($typeid) {
            if (empty($this->cache['event_volunteer_type'])) {
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT * FROM `event_volunteer_type` WHERE `companyid`={$this->cid()} AND isactive=1";

                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['event_volunteer_type'][intval($rows['volunteertypeid'])] = $rows;
                }
            }

            if (!empty($this->cache['event_volunteer_type'][$typeid])) {
                return $this->cache['event_volunteer_type'][$typeid]['type'];
            }
        }
        return '';
    }

    protected function getEventType (int $typeid): string
    {
        if ($typeid) {
            if (empty($this->cache['event_type'])) {
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT * FROM `event_type` WHERE `companyid`={$this->cid()}";

                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['event_type'][intval($rows['typeid'])] = $rows;
                }
            }

            if (!empty($this->cache['event_type'][$typeid])) {
                return $this->cache['event_type'][$typeid]['type'];
            }
        }
        return '';
    }

    protected function getEventName (int $eventid): string {
        if ($eventid) {
            if (empty($this->cache['events'][$eventid])) {
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT `eventid`,`eventtitle` FROM `events` WHERE companyid={$this->cid()} AND eventid={$eventid}";
                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
                if (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['events'][$eventid] = $rows;
                } else {
                    $this->cache['events'][$eventid] = array ('eventid'=>$eventid, 'eventtitle' => '');
                }
            }

            if (!empty($this->cache['events'][$eventid])) {
                return $this->cache['events'][$eventid]['eventtitle'];
            }
        }
        return '';
    }

    protected function getDisclaimersCSV (string $disclaimerids): string {
        $disclaimersCSV = '';
        if (!empty($disclaimerids)) {
            if (empty($this->cache['disclaimers'])) {
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT `disclaimerid`,`disclaimer_name` FROM `disclaimers` WHERE companyid={$this->cid()} AND isactive=1";
                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['disclaimers'][intval($rows['disclaimerid'])] = $rows;
                }
            }
            $disclaimeridsArray = explode(',',$disclaimerids);
            $d = array();
            foreach($disclaimeridsArray as $disclaimerid){
                if (!empty($this->cache['disclaimers'][$disclaimerid])) {
                    $d[] =  $this->cache['disclaimers'][$disclaimerid]['disclaimer_name'];
                }
            }
            $disclaimersCSV = implode(', ', $d);
        }
        return $disclaimersCSV;
    }

    protected function getDynamicListNamesCSV (string $listids): string {
        $dynamicListNamesCSV = '';
        if (!empty($listids)) {
            if (empty($this->cache['dynamiclists'])) {
                $dbc = GlobalGetDBROConnection();
                $select = "SELECT * FROM dynamic_lists WHERE `companyid`={$this->cid()} AND isactive=1";
                $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
                while (@$rows = mysqli_fetch_assoc($result)) {
                    $this->cache['dynamiclists'][intval($rows['listid'])] = $rows;
                }
            }
            $listidsArray = explode(',',$listids);
            $d = array();
            foreach($listidsArray as $listid){
                if (!empty($this->cache['dynamiclists'][$listid])) {
                    $d[] =  $this->cache['dynamiclists'][$listid]['list_name'];
                }
            }
            $dynamicListNamesCSV = implode(', ', $d);
        }
        return $dynamicListNamesCSV;
    }

    /** 
    * We are creating this map for efficiency reasons, much better than doing complex joins on main query.
    * Before getting the events build a map of organizations in the events if organizations are requested*
    * Keep the event part of WHERE clause in below query same as main query
    */
    protected function mapPartnerOrganizations(string $groupid_filter, string $startDateCondtion, string $endDateCondtion) : array
    {
        global $_ZONE;
        $partners_organizations = [];
        $rows =  self::DBROGet("
                    SELECT organization_name,company_organizations.organization_id,event_organizations.eventid
                    FROM event_organizations 
                        JOIN company_organizations ON company_organizations.organization_id=event_organizations.organizationid 
                        JOIN events ON events.eventid=event_organizations.eventid
                    WHERE events.companyid={$this->cid()} 
                    AND events.zoneid={$_ZONE->id()}
                    AND (
                        events.eventclass IN ('event', 'eventgroup')
                        {$groupid_filter}
                        {$startDateCondtion}
                        {$endDateCondtion}
                        AND events.event_series_id !=events.eventid
                        )
                    ");
        if(!empty($rows)){
            $partners_organizations = Arr::GroupBy($rows, 'eventid');
        }
        return $partners_organizations;
    }

    /**
     * Override this method in sub class
     * @return array
     */
    public static function GetMetadataForAnalytics () : array
    {
        return array('ExludeFields' => array(), 'TimeField' => '');
    }

    public function generateAnalyticData( string $title) : array
    {
        global $_COMPANY; /* @var Company $_COMPANY */

        if (!$_COMPANY || ($this->cid() && ($_COMPANY->id() != $this->cid()))) {
            Logger::Log("Fatal: Cannot generate report {$this->id} that does not belong to the company {$_COMPANY->id()}");
            return array();
        }

        if (empty($this->fields))
            return array();

        $meta = $this->getMetaArray();

        // Remove fields exluded for analytics before generating the report
        // Use static:: to call overriden method defined in the subclass.
        $excludeMetaFields = static::GetMetadataForAnalytics()['ExludeFields'] ?? [];
        foreach ($excludeMetaFields as $e_k) {
            unset ($meta['Fields'][$e_k]);
            unset ($meta['AdminFields'][$e_k]);
        }
        $this->fields['reportmeta'] = json_encode($meta);

        $metaFields = $meta['Fields'];
        $metaFieldsFlip = array_flip($metaFields);

        ini_set('memory_limit', '1024M');

        $file_mem = fopen('php://memory','r+');
        $this->_generateReport($file_mem);
        rewind($file_mem);

        $finalData = array();
        $questions = array();
        $data = [];
        $header = fgetcsv($file_mem);
        if (!empty($header)) {
            while (($row = fgetcsv($file_mem)) !== FALSE) {
                $entry = array_combine($header, $row);
                $data[] = $entry;
                foreach ($entry as $key => $value) {
                    if (empty($value)){ $value = "Undefined"; }
                    if (array_key_exists($key,$finalData) ){
                        $keyValue = $finalData[$key];
                        $k = array_search($value, array_column($keyValue, 'text'));
                        if ($k === false ){
                            $index = count($keyValue);
                            $keyValue[] = array("value"=>"item".$index,"text"=>$value);
                            $finalData[$key] =$keyValue;
                        }
                    } else {
                        $finalData[$key] = array(array("value"=>"item0","text"=>$value));
                    }
                }
            }
        }
        fclose($file_mem);

        if (empty($data))
            return array();

        if (!empty($finalData)){
            $questions[0]['name'] = 'questions';
            foreach($finalData as $key =>$value){
                if (empty($value)){ $value = "Undefined"; }

                $q = array();
                $q['name'] = $metaFieldsFlip[$key];
                $q['title'] = $key;
                $q['type'] = 'radiogroup';
                $q['choices'] = $value;
                $questions[0]['elements'][] = $q;
            }
        }

        $answers = self::GenerateAnalyticAnswers($data,$questions,$metaFields);

        return array('title'=> $title,'questions'=>array('completedHtml' => 'Thanks', 'pages' => $questions),'answers'=>$answers);
    }

    public static function GenerateAnalyticAnswers(array $data, array $questions, array $metaFields) : array
    {
        if (empty($questions)){ return array(); }
        $questions = $questions[0]['elements'];
        $i = 1;
        $answers = array();
        $metaFieldsFlip = array_flip($metaFields);
        foreach ($data as $row){
            $a = array();
            foreach ($row as $key => $value) {
                if (empty($value)){ $value = "Undefined"; }

                
                $metaKey = $metaFieldsFlip[$key];
                $questionKey = array_search($metaKey, array_column($questions, 'name'));
                $question = $questions[$questionKey];
                $choices = $question['choices'];
                $answerKey = array_search($value, array_column($choices, 'text'));
                $answer[$metaKey] = 'item' . $answerKey;
                
            }
            array_push($answers, $answer);
            $i++;
        }
        return $answers;
    }

        public function downloadReportAndExit (int $fileFormat, string $prefix = '')
    {
        global $_COMPANY;

        if ($fileFormat == self::FILE_FORMAT_CSV) {
            $ext = '.csv';
        } elseif ($fileFormat == self::FILE_FORMAT_TSV) {
            $ext = '.tsv';
        } elseif ($fileFormat == self::FILE_FORMAT_JSON) {
            $ext = '.json';
        }

        $clientFilename = ($prefix ? $prefix . '-' : '') .
            str_replace(
                [
                    '[[subdomain]]',
                    '[[reportname]]',
                    '[[date]]',
                    '[[time]]',
                ],
                [
                    strtoupper($_COMPANY->val('subdomain')),
                    slugify($this->val('reportname')),
                    date('Ymd'),
                    date('His'),
                ],
                $_COMPANY->getCompanyCustomization()['reports']['report_file_format']
            ) .
            $ext;

        $report_file = $this->generateReport($fileFormat, false);

        if (file_exists($report_file) && filesize($report_file)) {
            //        ob_start();
            header('Content-Description: File Transfer');
            header('Content-Type: ' . mime_content_type($report_file));
            header('Content-Disposition: attachment; filename="' . $clientFilename . '"');
            header('Content-Transfer-Encoding: text');
            header('Expires: 0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($report_file));
            //        ob_end_clean();
            //        ob_clean();
            //        flush();
            readfile($report_file);
            unlink($report_file);
            exit();
        }
    }

    public function getMetaArray()
    {
        $meta = json_decode($this->val('reportmeta'), true);
        // Sanitize the meta Array
        if (!empty($meta['Options']['startDate'])) {
            $meta['Options']['startDate'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['startDate']) ?: '2200-12-31 00:00:00';
        }
        if (!empty($meta['Options']['endDate'])) {

            $meta['Options']['endDate'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['endDate']) ?: '1900-12-31 00:00:00';
        }
        if (!empty($meta['Filters']['groupids'])) {
            $meta['Filters']['groupids'] = Sanitizer::SanitizeIntegerArray($meta['Filters']['groupids']);
        }
        if (!empty($meta['Filters']['chapterids'])) {
            $meta['Filters']['chapterids'] = Sanitizer::SanitizeIntegerArray($meta['Filters']['chapterids']);
        }
        if (!empty($meta['Filters']['channelids'])) {
            $meta['Filters']['channelids'] = Sanitizer::SanitizeIntegerArray($meta['Filters']['channelids']);
        }

        //Append custom fields
        if ($meta['Options']['includeCustomFields'] ?? 0) {
            $topictype = $meta['Options']['topictype'] ?? 'EVT';
                $topic_class = Teleskope::TOPIC_TYPE_CLASS_MAP[$topictype];
                $allCustomFields = call_user_func([$topic_class, 'GetEventCustomFields']);
                foreach ($allCustomFields as $custom_field) {
                    $meta['Fields']['custom'.$custom_field['custom_field_id']] = $custom_field['custom_field_name'];
                }
        }

        return $meta;
    }


    /**
     * @param array $row
     * @param string $caching_key2 - a second level caching key for use in functions where repetitive calculations are
     * involved, e.g. Event RSVP's which processes custom fields for events over and over again, providing 'eventid'
     * as caching_key2 will accelerate the processing.
     * @return array
     */
    protected function addCustomFieldsToRow(array $row, array $meta, string $caching_key2 = ''): array
    {
        if (empty($meta['Options']['includeCustomFields'])) {
            return $row;
        }

        if (!$row['custom_fields']) {
            return $row;
        }

        // Build the cache if it is not already set
        if (!isset($this->cache['customfields'])) {
            $this->cache['customfields'] = array();
            $topictype = $meta['Options']['topictype'] ?? 'EVT';
            $topic_class = Teleskope::TOPIC_TYPE_CLASS_MAP[$topictype];
            $this->cache['customfields'] = call_user_func([$topic_class, 'GetEventCustomFields'], true, false);
        }

        $customValues = array();
        // Get the value of caching_key2 from the row, e.g. if $caching_key2='eventid' then $key2 will contain
        // the numeric value corresponding to eventid in the $row array.
        $key2 = !empty($caching_key2) && !empty($row[$caching_key2]) ? $row[$caching_key2] : '';
        if ($key2 && isset($this->cache['customfields_caching_key2'][$key2])) {
            // Check if the cached value exists
            $customValues = $this->cache['customfields_caching_key2'][$key2];
            return array_merge($row, $customValues);
        }

        $event_custom_fields = json_decode($row['custom_fields'], true) ?? array(); // Returns an array of array with following two columns custom_field_id, value. value is an array of values.

        foreach ($this->cache['customfields'] as $custom_field) {

            $currentVal = Arr::SearchColumnReturnColumnVal($event_custom_fields, $custom_field['custom_field_id'], 'custom_field_id', 'value');

            if ($custom_field['custom_fields_type'] == 1 || $custom_field['custom_fields_type'] == 2) { // Single Value = 1, Multiple Values = 2
                $v = [];
                if (!is_array($currentVal)) {
                    $currentVal = [$currentVal];
                }
                foreach ($currentVal as $cv) {
                    $v[] = Arr::SearchColumnReturnColumnVal($custom_field['options'], $cv, 'custom_field_option_id', 'custom_field_option');
                }
                $filedVals = implode(', ', $v);
            } else {
                $filedVals = (is_array($currentVal) ? ($currentVal[0] ?? '') : $currentVal) ?? '';
            }

            $customValues['custom' . $custom_field['custom_field_id']] = $filedVals;
        }

        if ($key2) {
            // Save for future use.
            $this->cache['customfields_caching_key2'][$key2] = $customValues;
        }

        return array_merge($row, $customValues);
    }
}

/**
 * Generates the report file and returns report filename. Note: The report will be generated only if the company owns
 * the report or the report is global (owned by companyid=0).
 * @param string $report_id
 * @param int $file_format one of the formats provided by Teleskope::FILE_FORMAT_* e.g. Teleskope::FILE_FORMAT_CSV
 * @param bool $zipit set to true if you want the file zip'ed
 * @param string $zip_password
 * @return string
 */
function generateTeleskopeReportForExport (string $report_id, int $file_format, bool $zipit = false, string $zip_password = '') : string
{
    // Set execution time to 120 seconds. This is actual CPU consumption time. Default is 30 seconds
    // We are doing this to allow jobs enough time to finish processing.
    set_time_limit(120);

    $report_rec = Report::GetReportRec($report_id);
    $report = null;

    if (count($report_rec) > 0) {
        switch ((int)$report_rec[0]['reporttype']) {
            case Report::REPORT_TYPE_USER_MEMBERSHIP:
                $report = new ReportUserMembership ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_USERS:
                $report = new ReportUsers ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_EVENT:
                $report = new ReportEvents ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_EVENT_RSVP:
                $report = new ReportEventRSVP ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_BUDGET:
                $report = new ReportBudget ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_SURVEY:
                $report = new ReportSurvey ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_SURVEY_DATA:
                $report = new ReportSurveyData ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_ANNOUNCEMENT:
                $report = new ReportAnnouncement ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_NEWSLETTERS:
                $report = new ReportNewsletter($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_OFFICELOCATIONS:
                $report = new ReportOfficeLocations ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_GROUP_DETAILS:
                $report = new ReportGroupDetails ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_GROUP_CHAPTER_DETAILS:
                $report = new ReportGroupChapterDetails ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_GROUP_CHANNEL_DETAILS:
                $report = new ReportGroupChannelDetails ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_GROUPCHAPTER_LOCATION:
                $report = new ReportGroupChapterLocation($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_TEAM_TEAMS:
                $report = new ReportTeamTeams ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_TEAM_MEMBERS:
                $report = new ReportTeamMembers ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_TEAM_REGISTRATIONS:
                $report = new ReportTeamRegistrations ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_USER_AUDIT_LOGS:
                $report = new ReportUserAuditLogs ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_LOGINS:
                $report = new ReportLogins ($report_rec[0]['companyid'],$report_rec[0]);
                break;

            case Report::REPORT_TYPE_EVENT_SURVEY:
                $report = new ReportEventSurveyData ($report_rec[0]['companyid'],$report_rec[0]);
                break;
            case Report::REPORT_TYPE_ORGANIZATION:
                $report = new ReportOrganization ($report_rec[0]['companyid'],$report_rec[0]);
                break;
            case Report::REPORT_TYPE_EVENT_ORGANIZATION:
                $report = new ReportEventOrganization ($report_rec[0]['companyid'],$report_rec[0]);
                break;

        }
        
        if ($report) {

            // First update the start/end dates if set to real datetime values
            $meta = json_decode($report->val('reportmeta'), true);
            // Convert the start/end date from relative format to absolute
            if (!empty($meta['Options']['startDate'])) {
                $st = strtotime($meta['Options']['startDate']);
                $meta['Options']['startDate'] = $st ? gmdate('Y-m-d H:i:s', $st) : '';
            }
            if (!empty($meta['Options']['endDate'])) {
                $st = strtotime($meta['Options']['endDate']);
                $meta['Options']['endDate'] = $st ? gmdate('Y-m-d H:i:s', $st) : '';
            }
            $report->setField('reportmeta',json_encode($meta));

            return $report->generateReport($file_format, $zipit, $zip_password);
        }
    }
    return '';
}


/**
 * Include all subclasses here
 */

if (!isset($db)) { $db = new Hems();} // @Todo This is a temporary initatilization to get localization function in reports.

include_once __DIR__ . '/ReportAdmins.php';
include_once __DIR__ . '/ReportAnnouncement.php';
include_once __DIR__ . '/ReportBudget.php';
include_once __DIR__ . '/ReportBudgetChargeCode.php';
include_once __DIR__ . '/ReportBudgetExpenseType.php';
include_once __DIR__ . '/ReportBudgetYear.php';
include_once __DIR__ . '/ReportEvents.php';
include_once __DIR__ . '/ReportEventRSVP.php';
include_once __DIR__ . '/ReportEventSpeaker.php';
include_once __DIR__ . '/ReportEventOrganization.php';
include_once __DIR__ . '/ReportEventType.php';
include_once __DIR__ . '/ReportEventVolunteers.php';
include_once __DIR__ . '/ReportGroupDetails.php';
include_once __DIR__ . '/ReportGroupChapterDetails.php';
include_once __DIR__ . '/ReportGroupChannelDetails.php';
include_once __DIR__ . '/ReportLeadType.php';
include_once __DIR__ . '/ReportLogins.php';
include_once __DIR__ . '/ReportOfficeLocations.php';
include_once __DIR__ . '/ReportRecognitions.php';
include_once __DIR__ . '/ReportRegions.php';
include_once __DIR__ . '/ReportSurvey.php';
include_once __DIR__ . '/ReportUserMembership.php';
include_once __DIR__ . '/ReportUsers.php';
include_once __DIR__ . '/ReportUserAuditLogs.php';
include_once __DIR__ . '/ReportTeamRegistrations.php';
include_once __DIR__ . '/ReportTeamMembers.php';
include_once __DIR__ . '/ReportSurveyData.php';
include_once __DIR__ . '/ReportDisclaimerConsents.php';
include_once __DIR__ . '/ReportEventRecordingLinkClicks.php';
include_once __DIR__ . '/ReportEventSurveyData.php';
include_once __DIR__ . '/ReportTeamsFeedback.php';
include_once __DIR__ . '/ReportTeamTeams.php';
include_once __DIR__ . '/ReportNewsletter.php';
include_once __DIR__ . '/ReportGroupJoinRequests.php';
//include_once __DIR__ . '/ReportZoneStatistics.php';
include_once __DIR__ . '/ReportPointsTransactions.php';
include_once __DIR__ . '/ReportPointsBalance.php';
include_once __DIR__ . '/ReportGroupChapterLocation.php';
include_once __DIR__ . '/ReportTeamRequests.php';
include_once __DIR__ . '/ReportDirectMails.php';
include_once __DIR__ . '/ReportApprovals.php';
include_once __DIR__ . '/ReportOrganization.php';
include_once __DIR__ . '/ReportDelegatedAccessAuditLog.php';
include_once __DIR__ . '/ReportSchedules.php';
include_once __DIR__ . '/ReportScheduleSlots.php';
