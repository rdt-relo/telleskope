<?php

class ReportLogins extends Report
{
    public const META = array(
        'Fields' => array(
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'email' => 'Email',
            'jobtitle' => 'Job Title',
            'department' => 'Department',
            'branchname' => 'Office Location',
            'loginzone' => 'Login Zone',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
            'opco' => 'Company',
            'usagetime' => 'Login Date - Time',
            'usageif' => 'Application Name'
        ),
        'AdminFields' => array(
            'externalid' => 'Employee Id',
        ),
        'Options' => array(
            'startDate' => null,
            'endDate' => null,
        ),
        'Filters' => array(
            'application' => ''
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_LOGINS;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        $startDateCondtion = "";
        if (!empty($meta['Options']['startDate'])) {
            $meta['Options']['startDate'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['startDate']) ?: '2200-12-31 00:00:00';
            $startDateCondtion = " AND appusage.`usagetime` >= '{$meta['Options']['startDate']}' ";
        }

        $endDateCondtion = "";
        if (!empty($meta['Options']['endDate'])) {
            $meta['Options']['endDate'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['endDate']) ?: '1900-12-31 00:00:00';
            $endDateCondtion = " AND appusage.`usagetime` <= '{$meta['Options']['endDate']}' ";
        }


        $appFilterCondition = ' AND false '; // Setting it to false to let the query fail
        if (!empty($meta['Filters']['application']) && in_array($meta['Filters']['application'], array('admin', 'affinities', 'native', 'email', 'officeraven', 'talentpeak', 'peoplehero'))) {
            $appFilterCondition = " AND appusage.`usageif` = '{$meta['Filters']['application']}' ";
        }
        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

        $select = "SELECT appusage.*, users.userid as uid, users.firstname, users.lastname, users.email, users.external_email, users.jobtitle, users.department, users.homeoffice, users.externalid  
            FROM appusage
                LEFT JOIN users ON users.userid=appusage.userid 
            WHERE 
                appusage.companyid={$this->cid()}
              	{$startDateCondtion}
		        {$endDateCondtion}
                {$appFilterCondition}
            ORDER BY appusage.`usagetime` DESC {$this->policy_limit}";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$rows = mysqli_fetch_assoc($result)) {
            if ($rows['external_email']) {
                $rows['email'] = User::PickEmailForDisplay($rows['email'], $rows['external_email'], false);
            }
            $rows['externalid'] = explode(':', $rows['externalid'] ?? '')[0];
            //do something with $rows;
            if (!$rows['uid']) {
                $rows['firstname'] = 'User Deleted';
                $rows['lastname'] = 'User Deleted';
            }

            // Add the zone info
            $loginzone = $_COMPANY->getZone($rows['zoneid']);
            $rows['loginzone'] = $loginzone ? $loginzone->val('zonename') : '';
            $rows['usagetime'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['usagetime'], 'UTC');

            $rows = array_merge(
                $rows,
                $this->getBranchAndRegionValues($rows['homeoffice'] ?? 0),
                $this->getDepartmentValues($rows['department'] ?? 0),
            );
            $row = array();
            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
        }
    }

    public static function GetMetadataForAnalytics () : array
    {
        return array (
            'ExludeFields' => array (
                'firstname',
                'lastname',
                'email',
                'usagetime'
            ),
            'TimeField' => 'usagetime'
        );
    }
}