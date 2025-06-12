<?php

class ReportRegions extends Report
{
    public const META = array(
        'Fields' => array(
            'regions' => 'Regions',
            'zones' => 'Zones',
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

    protected static function GetReportType() : int { return self::REPORT_TYPE_REGIONS;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();
        $regions_list = array();

        // Set the region id filters
        $regionid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['regionids'])) {
            $regionid_list = implode(',', $meta['Filters']['regionids']);
            $regionid_filter = " AND companybranches.regionid IN ({$regionid_list})";
        }
        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $select = "SELECT regions.*,
                    (SELECT GROUP_CONCAT(zonename) FROM company_zones WHERE company_zones.companyid={$_COMPANY->id()} AND FIND_IN_SET(regionid, company_zones.regionids)) as zones
                FROM `regions` WHERE companyid={$_COMPANY->id()}";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
        while (@$rows = mysqli_fetch_assoc($result)) {
            if ($rows['isactive'] == 1) {
                $regionIsActive = "Active";
            } else {
                $regionIsActive = "Not Active";
            }
            $row = array(
                'regions' => $rows['region'],
                'zones' => $rows['zones'],
                'isactive' => $regionIsActive);
            array_push($regions_list, $row);
        }

        foreach ($regions_list as $data) {
            fputcsv($file_h, $data, $delimiter, $enclosure, $escape_char);
        }

    }
}