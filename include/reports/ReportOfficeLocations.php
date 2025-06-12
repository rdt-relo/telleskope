<?php

class ReportOfficeLocations extends Report
{
    public const META = array(
        'Fields' => array(
            'branchname' => 'Location Name',
            'street' => 'Street',
            'city' => 'City',
            'state' => 'State',
            'zipcode' => 'Zip Code',
            'country' => 'Country',
            'region' => 'Region',
            'employees' => 'No of employees',
            'branchtype' => 'Location Type',
            'isactive' => 'Is Active'
        ),
        'Options' => array(),
        'Filters' => array(
            'regionids' => array(),
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_OFFICELOCATIONS;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        $regionid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['regionids'])) {
            $regionid_list = implode(',', $meta['Filters']['regionids']);
            $regionid_filter = " AND companybranches.regionid IN ({$regionid_list})";
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $select = "SELECT companybranches.*, regions.region
        FROM companybranches 
        LEFT JOIN regions USING (regionid)
        WHERE companybranches.companyid={$this->cid()} 
        AND (companybranches.`isactive`>0 
        {$regionid_filter}
        ) 
        order by branchname ASC {$this->policy_limit}";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$rows = mysqli_fetch_assoc($result)) {
            $row = array();
            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
        }
    }
}