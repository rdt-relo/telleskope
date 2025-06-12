<?php

class ReportEventType extends Report
{
    public const META = array(
        'Fields' => array(
            'type' => 'Event Type',
            'scope' => 'Scope',
            'isactive' => 'Status'
        ),
        'AdminFields' => array(),
        'Options' => array(),
        'Filters' => array()
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_EVENT_TYPE;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();
        $EvenType_rows = array();

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        // if ($_ZONE->id() == 0) {}

        $select = "SELECT e.type,e.isactive,e.zoneid FROM `event_type` AS e WHERE e.companyid={$_COMPANY->id()} AND e.zoneid IN (0,{$_ZONE->id()})";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
        while (@$rows = mysqli_fetch_assoc($result)) {
            // Set data values
            $row = array(
                'type' => $rows['type'],
                'scope' => ($rows['zoneid'] == 0) ? 'Global' : 'Zone (' . $_ZONE->val('zonename') . ')',
                'isactive' => ($rows['isactive'] == 1) ? 'Active' : 'Not Active'
            );
            array_push($EvenType_rows, $row);
        }

        foreach ($EvenType_rows as $data) {
            fputcsv($file_h, $data, $delimiter, $enclosure, $escape_char);
        }
    }

}