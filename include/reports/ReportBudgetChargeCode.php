<?php

class ReportBudgetChargeCode extends Report
{
    public const META = array(
        'Fields' => array(
            'charge_code' => 'Charge Code',
            'createdon' => 'Created On',
            'createdby' => 'Created By',
        ),
        'Options' => array(),
        'Filters' => array()
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_BUDGET_CHARGE_CODE;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();
        $budget_rows = array();

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $select = "SELECT * FROM budget_charge_codes WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `isactive`=1 {$this->policy_limit}";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
        while (@$rows = mysqli_fetch_assoc($result)) {
            $row = [];
            if (!empty($rows['createdby'])) {
                $user = User::GetUser($rows['createdby']);
                $row = array(
                    "charge_code" => $rows['charge_code'],
                    "createdon" => $rows['createdon'] . ' UTC',
                    "createdby" => $user ? $user->getFullName() : '',
                );
            }
            array_push($budget_rows, $row);
        }

        foreach ($budget_rows as $data) {
            fputcsv($file_h, $data, $delimiter, $enclosure, $escape_char);
        }

    }

}