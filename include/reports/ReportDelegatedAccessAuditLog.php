<?php

class ReportDelegatedAccessAuditLog extends Report
{
    public const META = array(
        'Fields' => array(
            'zonename' => 'Zone Name',
            'grantor_externalid' => 'Grantor - External Id',
            'grantor_firstname' => 'Grantor - First Name',
            'grantor_lastname' => 'Grantor - Last Name',
            'grantor_email' => 'Grantor - Email',
            'grantee_externalid' => 'Delegate - External Id',
            'grantee_firstname' => 'Delegate - First Name',
            'grantee_lastname' => 'Delegate - Last Name',
            'grantee_email' => 'Delegate - Email',
            'action' => 'Action Type',
            'createdon' => 'Action Date',
            'action_by' => 'Action By',
            'action_reason' => 'Action Remarks',
        ),
        'Options' => array(),
        'Filters' => array(),
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_DELEGATED_ACCESS_AUDIT_LOG; }
    protected static function GetReportMeta() : array { return self::META; }

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        $select = "
            SELECT
                        `zoneid`,
                        `grantor_userid`,
                        `grantee_userid`,
                        `action`,
                        `action_by_userid`,
                        `action_reason`,
                        `createdon`
            FROM        `users_delegated_access_log`
            WHERE       `companyid` = {$_COMPANY->id()}
            AND         `zoneid` = {$_ZONE->id()}
        ";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

        while ($row = mysqli_fetch_assoc($result)) {
            $zone = $_COMPANY->getZone($row['zoneid']);
            $row['zonename'] = $zone->val('zonename');

            $grantor_user = $this->getUser($row['grantor_userid']); // optimization - instead of User::GetUser, we are using getUser
            $grantee_user = $this->getUser($row['grantee_userid']); // optimization - instead of User::GetUser, we are using getUser
            $action_by_user = $this->getUser($row['action_by_userid']); // optimization - instead of User::GetUser, we are using getUser

            $row['grantor_externalid'] = $grantor_user?->getExternalId() ?? 'Not Available';
            $row['grantor_firstname'] = $grantor_user?->val('firstname') ?? 'Not Available';
            $row['grantor_lastname'] = $grantor_user?->val('lastname') ?? 'Not Available';
            $row['grantor_email'] = $grantor_user?->getEmailForDisplay() ?? 'Not Available';

            $row['grantee_externalid'] = $grantee_user?->getExternalId() ?? 'Not Available';
            $row['grantee_firstname'] = $grantee_user?->val('firstname') ?? 'Not Available';
            $row['grantee_lastname'] = $grantee_user?->val('lastname') ?? 'Not Available';
            $row['grantee_email'] = $grantee_user?->getEmailForDisplay() ?? 'Not Available';

            if ($action_by_user) {
                $row['action_by'] = $action_by_user->getFullName() . ' - ' . $action_by_user->getEmailForDisplay();
            } else {
                $row['action_by'] = 'Not Available';
            }

            $row['action'] = ucfirst(strtolower($row['action']));
            $row['action_reason'] = $row['action_reason'] ?? '';
            $row['createdon'] = $db->covertUTCtoLocalAdvance('Y-m-d H:i A', ' T', $row['createdon'], $reportTimezone);

            $csv_row = [];
            foreach ($meta['Fields'] as $key => $value) {
                $csv_row[] = html_entity_decode($row[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $csv_row, $delimiter, $enclosure, $escape_char);
        }
    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;

        $reportmeta = parent::GetDefaultReportRecForDownload();
        // $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']['name-short'];
        return $reportmeta;
    }
}
