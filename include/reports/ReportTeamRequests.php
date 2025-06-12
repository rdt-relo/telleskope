<?php

class ReportTeamRequests extends Report
{

    public const META = array(
        'Fields' => array(
            'mentee_firstname' => 'Mentee First Name',
            'mentee_lastname' => 'Last Name',
            'mentee_email' => 'Mentee Email',
            'mentee_employeeid' => 'Mentee Employee ID',
            'mentor_firstname' => 'Mentor First Name',
            'mentor_lastname' => 'Mentor Last Name',
            'mentor_email' => 'Mentor Email',
            'mentor_employeeid' => 'Mentee Employee ID',
            'createdon' => "Request Creation Date",
            'modifiedon' => "Request Modification Date",
            'groupname' => 'Group name',
            'status' => 'Request Status',
            'rejection_reason' => 'Rejection Reason',
        ),
        'AdminFields' => array(
        ),
        'Options' => array(
        ),
        'Filters' => array(
            'groupid' => 0,
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_TEAM_REQUESTS;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {

        global $_COMPANY, $_ZONE, $_USER, $db;
        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        $groupid = $meta['Filters']['groupid'];
        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $select = "SELECT S.firstname AS mentee_firstname,
            S.lastname AS mentee_lastname,
            S.email AS mentee_email,
            S.externalid AS mentee_employeeid,
            R.firstname AS mentor_firstname,
            R.lastname AS mentor_lastname,
            R.email AS mentor_email,
            R.externalid AS mentor_employeeid,
            T.status,T.rejection_reason, T.createdon, T.modifiedon,G.groupname,T.status
            FROM team_requests AS T
            LEFT JOIN `users` AS S ON T.senderid=S.userid 
            LEFT JOIN `users` AS R ON T.receiverid=R.userid 
            LEFT JOIN `groups` AS G ON T.groupid=G.groupid 
            WHERE T.companyid={$_COMPANY->id()}
            AND T.groupid = {$groupid}
            {$this->policy_limit}";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$rows = mysqli_fetch_assoc($result)) {
            // request status map
            $rows['status'] = self::TEAM_REQUEST_STATUS_MAP[(int)$rows['status']];
            
            $rows['mentee_employeeid'] = $rows['mentee_employeeid'] ? User::ExtractExternalId($rows['mentee_employeeid']) : '';
            $rows['mentor_employeeid'] = $rows['mentor_employeeid'] ? User::ExtractExternalId($rows['mentor_employeeid']) : '';
            $rows['createdon'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['createdon'], $reportTimezone);
            $rows['modifiedon'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['modifiedon'], $reportTimezone);
            $row = array();
            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
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
        return $reportmeta;
    }

}