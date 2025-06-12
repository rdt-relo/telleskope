<?php

class ReportAdmins extends Report
{
    public const META = array(
        'Fields' => array(
            'accounttype' => 'Admin Type',
            'externalid' => 'Employee Id', // Since this report is for Admin Panel only, we can put externalid here
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'jobtitle' => 'Job Title',
            'email' => 'Email',
            'manage_budget' => 'Can Manage Budget',
            'manage_approvers' => 'Can Manage Approvers',
            'createdon' => 'Added On',
            'createdby' => 'Added By',
            'validatedon' => 'Validated On'
        ),
        'AdminFields' => array(
        ),
        'Options' => array(),
        'Filters' => array()
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_ADMINS;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_USER, $_ZONE;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();
        $admin_rows = array();

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

        $select = "SELECT firstname,lastname,email,externalid,jobtitle,validatedon,company_admins.* 
            FROM `company_admins` 
                JOIN users ON company_admins.userid=users.userid AND users.isactive=1 
            WHERE company_admins.companyid={$_COMPANY->id()} AND zoneid IN (0,{$_ZONE->id()})";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$rows = mysqli_fetch_assoc($result)) {
            $addedBy = User::GetUser($rows['createdby']);
            $admin = User::Hydrate($rows['userid'], $rows);
            $row = array(
                'accounttype' => ($rows['zoneid'] == 0) ? 'Company Admin' : 'Zone Admin (' . $_ZONE->val('zonename') . ')',
                'externalid' => $admin->getExternalId(),
                'firstname' => $rows['firstname'],
                'lastname' => $rows['lastname'],
                'jobtitle' => $rows['jobtitle'],
                'email' => $rows['email'],
                'manage_budget' => $rows['manage_budget'] == 1 ? 'Yes' : 'No',
                'manage_approvers' => $rows['manage_approvers'] == 1 ? 'Yes' : 'No',
                'createdon' =>  $_USER->formatUTCDatetimeForDisplayInLocalTimezone($rows['createdon'],true,true,true),
                'createdby' => $addedBy ? $addedBy->getFullName() : '',
                'validatedon' =>  $_USER->formatUTCDatetimeForDisplayInLocalTimezone($rows['validatedon'],true,true,true),
            );

            array_push($admin_rows, $row);
        }

        foreach ($admin_rows as $data) {
            fputcsv($file_h, $data, $delimiter, $enclosure, $escape_char);
        }
    }

}