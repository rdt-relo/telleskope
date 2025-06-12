<?php

class ReportUsers extends Report
{

    public const META = array(
        'Fields' => array(
            'enc_userid' => 'User ID',
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'pronouns' => 'Pronouns',
            'email' => 'Email',
            'jobtitle' => 'Job Title',
            'employeetype' => 'Employee Type',
            'department' => 'Department',
            'branchname' => 'Office Location',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
            'opco' => 'Company',
            'homezone' => 'Home Zone',
            'createdon' => 'User Created On',
            'modified' => 'User Last Updated On',
            'user_status' => 'User Status',
            'externalroles' => 'External Roles',
            'employee_hire_date' => 'Hire Date',
            'employee_start_date' => 'Start Date',
            'employee_termination_date' => 'Termination Date',
            'user_profile_bio' => 'User Profile Bio',
        ),
        'AdminFields' => array(
            'externalid' => 'Employee Id',
        ),
        'Options' => array(
            'onlyActiveUsers' => true,
        ),
        'Filters' => array(
            'zoneid' => 0, // If 0 all zones
        )
    );

    const ATTRIBUTES_TO_BE_ANONYMIZED = array(
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType(): int
    {
        return self::REPORT_TYPE_USER_MEMBERSHIP;
    }

    protected static function GetReportMeta(): array
    {
        return self::META;
    }

    public function getMetaArray(): array
    {
        global $_COMPANY;

        $meta = parent::getMetaArray();

        return $meta;
    }

    /**
     * Generates and writes the report in the provided file handler.
     * @param $file_h
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape_char
     */
    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        // Step 0 -
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        $zoneid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['zoneid'])) {
            $filter_zoneid = intval($meta['Filters']['zoneid']);
            $zoneid_filter = " AND FIND_IN_SET({$filter_zoneid}, users.zoneids)";
        }


        $activeUsersCondition = "";
        if ($meta['Options']['onlyActiveUsers'] ?? false) {
            $activeUsersCondition = "AND users.isactive = 1";
        }

        $userCreateDateCondition = '';
        if (!empty($meta['Options']['startDate']) && Sanitizer::SanitizeUTCDatetime($meta['Options']['startDate']) == $meta['Options']['startDate']) {
            $userCreateDateCondition .= " AND `createdon` >= '{$meta['Options']['startDate']}' ";
        }

        if (!empty($meta['Options']['endDate']) && Sanitizer::SanitizeUTCDatetime($meta['Options']['endDate']) == $meta['Options']['endDate']) {
            $userCreateDateCondition .= " AND `createdon` <= '{$meta['Options']['endDate']}' ";
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        //
        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';

        # Step 2 - All Zone Users
        $select = "SELECT users.userid,zoneids,firstname,lastname,pronouns,email,external_email,externalid,jobtitle,extendedprofile,externalroles,employeetype,department,homeoffice,opco, users.modified,users.createdon, users.isactive as user_status, users.employee_hire_date, users.employee_start_date, users.employee_termination_date
        FROM users
        WHERE users.companyid='{$this->cid()}'
          {$activeUsersCondition}
          {$zoneid_filter}
          {$userCreateDateCondition}
          {$this->policy_limit}";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt' => $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$rows = mysqli_fetch_assoc($result)) {

            $rows['enc_userid'] = $_COMPANY->encodeIdForReport($rows['userid']);

            if ($rows['external_email']) {
                $rows['email'] = User::PickEmailForDisplay($rows['email'], $rows['external_email'], true);
            }

            $rows['user_status'] = self::USER_STATUS_MAP[$rows['user_status']];
            //do something with $rows;
            $rows['externalid'] = explode(':', $rows['externalid'] ?? '')[0];
            if (!empty($rows['extendedprofile'])) {
                $profile = User::DecryptProfile($rows['extendedprofile']);
                foreach ($profile as $pk => $value) {
                    $rows['extendedprofile.' . $pk] = $value;
                }
            }

            //Decorate with additional fields
            $rows = array_merge(
                $rows,
                $this->getBranchAndRegionValues($rows['homeoffice']),
                $this->getDepartmentValues($rows['department']),
                $this->getHomezoneNamesAsCSV($rows['zoneids'])
            );

            // timezone conversion
            $rows['since'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['since'], $reportTimezone);
            $rows['modified'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['modified'], $reportTimezone);
            $rows['createdon'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['createdon'], $reportTimezone);

            if (isset($meta['Fields']['user_profile_bio'])) {
                $rows['user_profile_bio'] = Html::HtmlToReportText((User::Hydrate($rows['userid'], []))->getBio());
            }

            $row = array();
            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
        }
        mysqli_free_result($result);

    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;
        global $_ZONE;

        $reportmeta = parent::GetDefaultReportRecForDownload();

        if (!$_COMPANY->getAppCustomization()['profile']['enable_pronouns']) {
            unset($reportmeta['Fields']['pronouns']);
        }

        if ($_ZONE->val('app_type') !== 'peoplehero') {
            unset($reportmeta['Fields']['employee_hire_date']);
            unset($reportmeta['Fields']['employee_start_date']);
            unset($reportmeta['Fields']['employee_termination_date']);
        }

        if (!$_COMPANY->getAppCustomization()['profile']['enable_bio']) {
            unset($reportmeta['Fields']['user_profile_bio']);
        }

        return $reportmeta;
    }

    public static function GetMetadataForAnalytics(): array
    {
        return array(
            'ExludeFields' => array(
                'enc_userid',
                'firstname',
                'lastname',
                'pronouns',
                'email',
                'modified',
                'since',
                'externalid',
                'externalroles',
                'employee_hire_date',
                'employee_start_date',
                'employee_termination_date',
                'user_profile_bio',
            ),
            'TimeField' => 'modified'
        );
    }
}
