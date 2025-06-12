<?php 
class ReportGroupJoinRequests extends Report{
    protected const META = array(
        'Fields' => array(
            'enc_groupid' => 'Group ID',
            'groupname' => 'Group Name',
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'email' => 'Email',
            'jobtitle' => 'Job Title',
            'employeetype' => 'Employee Type',
            'department' => 'Department',
            'branchname' => 'Office Location',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
            'opco' => 'Company',
            'createdon' => 'Requested On',
        ),
        'AdminFields' => array(
            'externalid' => 'Employee Id',
        ),
        'Options' => array(
        ),
        'Filters' => array(
            'groupid' => 0,
            'userid' => 0,
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_GROUP_JOIN_REQUESTS;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        // implementation in sub classes for now
        global $_COMPANY, $_ZONE, $_USER, $db;
        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        $groupid = $meta['Filters']['groupid'][0];
        $userid = $meta['Filters']['userid'];

        $group = Group::GetGroup($groupid);

        $questionMeta = array();
        $fields = $meta['Fields']; // Variable used to store fields so that we can update them.
       
        $startDateCondtion = "";
        if (!empty($meta['Options']['startDate'])) {
            $startDateCondtion = " AND member_join_requests.`createdon` >= '{$meta['Options']['startDate']}' ";
        }

        $endDateCondtion = "";
        if (!empty($meta['Options']['endDate'])) {
            $endDateCondtion = " AND member_join_requests.`createdon` <= '{$meta['Options']['endDate']}' ";
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($fields), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $condition = "";
        if ($userid) {
            $condition = " AND member_join_requests.userid='{$userid}'";
        }

        $select = "SELECT member_join_requests.groupid, member_join_requests.createdon, member_join_requests.chapterids,  users.`userid`, users.firstname, users.lastname, users.email, users.externalid, users.jobtitle, users.extendedprofile, users.employeetype, users.homeoffice, users.department,users.opco
            FROM member_join_requests 
            LEFT JOIN users ON member_join_requests.userid=users.userid AND member_join_requests.companyid=users.companyid
            WHERE member_join_requests.companyid={$_COMPANY->id()} AND member_join_requests.groupid={$groupid}         {$startDateCondtion}
        {$endDateCondtion} {$condition} {$this->policy_limit}";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));


        while (@$response = mysqli_fetch_assoc($result)) {

            if (!$_USER->canManageContentInScopeCSV($response['groupid'],$response['chapterids'])){
                continue;
            }

            if ($response['userid'] == null) {
                $response['userid'] = -1;
                $response['firstname'] = 'User Deleted';
                $response['lastname'] = 'User Deleted';
                $response['email'] = 'User Deleted';
                $response['jobtitle'] = 'User Deleted';
                $response['homeoffice'] = 0;
                $response['department'] = 0;
            }
            $response['groupname'] = $group->val('groupname');
            $response['externalid'] = explode(':', $response['externalid'] ?? '')[0];
            if (!empty($response['extendedprofile'])) {
                $profile = User::DecryptProfile($response['extendedprofile']);
                foreach ($profile as $pk => $value) {
                    $response['extendedprofile.' . $pk] = $value;
                }
            }

            //Decorate with additional fields
            $response = array_merge(
                $response,
                $this->getBranchAndRegionValues($response['homeoffice']),
                $this->getDepartmentValues($response['department'])
            );

            $response['createdon'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $response['createdon'], $reportTimezone);
            $response['enc_groupid'] = $_COMPANY->encodeIdForReport($response['groupid']);

            $row = array();
            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($response[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
        }
    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;
        global $_ZONE;

        $reportmeta = parent::GetDefaultReportRecForDownload();

        // Ignore custom report values for groupname, chaptername and channelname and set them to what is defined in the zone
        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']["name-short"];
        $reportmeta['Fields']['enc_groupid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' ID';

        return $reportmeta;
    }
}