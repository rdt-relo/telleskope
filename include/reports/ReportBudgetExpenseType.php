<?php

class ReportBudgetExpenseType extends Report
{
    public const META = array(
        'Fields' => array(
            'expensetype' => 'Expense Type',
            'isactive' => 'Is Active'
        ),
        'Options' => array(),
        'Filters' => array()
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_BUDGET_EXPENSE_TYPE;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();
        $budget_expense_types = array();

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $select = "SELECT expensetype,isactive FROM `budget_expense_types` WHERE `companyid`='{$_COMPANY->id()}' AND zoneid={$_ZONE->id()} {$this->policy_limit}";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
        while (@$rows = mysqli_fetch_assoc($result)) {
            if ($rows['isactive'] == 1) {
                $budgetExpenseActive = "Active";
            } else {
                $budgetExpenseActive = "Not Active";
            }
            $row = array(
                "expensetype" => $rows['expensetype'],
                "isactive" => $budgetExpenseActive);
            array_push($budget_expense_types, $row);
        }

        foreach ($budget_expense_types as $data) {
            fputcsv($file_h, $data, $delimiter, $enclosure, $escape_char);
        }

    }

}