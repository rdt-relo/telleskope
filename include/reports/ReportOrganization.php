<?php

class ReportOrganization extends Report
{

    public const META = array(
        'Fields' => array(
            'organization_id' => 'Organization ID',
            'organization_name' => 'Organization Name',
            'organization_type' => 'Organization Type',
            'organization_taxid' => 'Tax ID / EIN',
            'organization_street' => 'Street Address',
            'organization_city' => 'City',
            'organization_state' => 'State/Province',
            'organization_zip' => 'Zip Code',
            'organization_country' => 'Country',
            'organization_primary_contact' => 'Primary Contact',
            'organization_ceo' => 'CEO',
            'organization_cfo' => 'CFO',
            'organization_board_member_1' => 'Board Member 1',
            'organization_board_member_2' => 'Board Member 2',
            'organization_board_member_3' => 'Board Member 3',
            'organization_board_member_4' => 'Board Member 4',
            'organization_board_member_5' => 'Board Member 5',
            'company_organization_notes' => 'Notes about this Organization',
            'organization_mission_statement' => 'Organization Mission',
            // 'last_date_confirmed' => 'Last Date Confirmed',
            // 'last_date_approved' => 'Last date approved',
        ),
        'AdminFields' => array(),
        'Filters' => array()
    );
  

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_ORGANIZATION;}
    protected static function GetReportMeta() : array { return self::META;}

    /**
     * Generates and writes the report in the provided file handler.
     * @param $file_h
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape_char
     */
    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $_USER, $db;
        // Step 0 -
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

        $select = "SELECT * FROM `company_organizations` WHERE `company_organizations`.`companyid`='{$this->cid()}' {$this->policy_limit}";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$rows = mysqli_fetch_assoc($result)) {

            $partnerOrg = Organization::GetOrganizationFromPartnerPath($rows['api_org_id']);
            if (!empty($partnerOrg)) {
                $partnerOrg = $partnerOrg['results'][0];
            }

            $rows['organization_id'] = $_COMPANY->encodeIdForReport($rows['organization_id']);

            if (!empty($partnerOrg['ID'])) {
                $rows['organization_type'] = Organization::ORGANIZATION_TYPE_MAP[$partnerOrg['OrganizationType']];
                $rows['organization_city'] = trim($partnerOrg['City']);
                $rows['organization_street'] = trim($partnerOrg['Street']);
                $rows['organization_state'] = trim($partnerOrg['State']);
                $rows['organization_zip'] = trim($partnerOrg['Zip']);
                $rows['organization_country'] = trim($partnerOrg['Country']);
                $rows['organization_primary_contact'] = trim($partnerOrg['ContactFirstName'] . ' ' . $partnerOrg['ContactLastName'] . (!empty($partnerOrg['ContactEmail']) ? " ({$partnerOrg['ContactEmail']})" : ''));
                $rows['organization_ceo'] = trim($partnerOrg['CEOFirstName'] . ' ' . $partnerOrg['CEOLastName'] . (!empty($partnerOrg['CEODOB']) ? " ({$partnerOrg['CEODOB']})" : ''));
                $rows['organization_cfo'] = trim($partnerOrg['CFOFirstName'] . ' ' . $partnerOrg['CFOLastName'] . (!empty($partnerOrg['CFODOB']) ? " ({$partnerOrg['CEODOB']})" : ''));
                $rows['organization_board_member_1'] = trim($partnerOrg['bm1FirstName'] . ' ' . $partnerOrg['bm1LastName'] . (!empty($partnerOrg['bm1DOB']) ? " ({$partnerOrg['bm1DOB']})" : ''));
                $rows['organization_board_member_2'] = trim($partnerOrg['bm2FirstName'] . ' ' . $partnerOrg['bm2LastName'] . (!empty($partnerOrg['bm2DOB']) ? " ({$partnerOrg['bm2DOB']})" : ''));
                $rows['organization_board_member_3'] = trim($partnerOrg['bm3FirstName'] . ' ' . $partnerOrg['bm3LastName'] . (!empty($partnerOrg['bm3DOB']) ? " ({$partnerOrg['bm3DOB']})" : ''));
                $rows['organization_board_member_4'] = trim($partnerOrg['bm4FirstName'] . ' ' . $partnerOrg['bm4LastName'] . (!empty($partnerOrg['bm4DOB']) ? " ({$partnerOrg['bm4DOB']})" : ''));
                $rows['organization_board_member_5'] = trim($partnerOrg['bm5FirstName'] . ' ' . $partnerOrg['bm5LastName'] . (!empty($partnerOrg['bm5DOB']) ? " ({$partnerOrg['bm5DOB']})" : ''));
                $rows['organization_mission_statement'] = trim($partnerOrg['MissionStatement']);
            }

            $row = [];
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
        return $reportmeta;
    }
}
