<?php

class ReportLeadType extends Report
{
    public const META = array(
        'Fields' => array(
            'type' => 'Lead Type',
            'sys_leadtype' => 'System Type',
            // 'totalGroupLeads' => 'No of Group Leads',
            // 'totalChannelLeads' => 'No of channel leads',
            // 'totalChapterLeads' => "No of chapter leads",
            'total_leads' => 'Total No of Leaders',
            'allow_create_content' => 'Can Create Content',
            'allow_publish_content' => 'Can Publish Content',
            'allow_manage' => 'Can Manage',
            'allow_manage_grant' => 'Grant Role',
            'allow_manage_budget' => 'Can Manage Budget',
            'allow_manage_support' => 'Can Support',
            'show_on_aboutus' => 'Show on About Us',
            'isactive' => 'Is Active',
            'welcome_message' => 'Welcome Email'
        ),
        'AdminFields' => array(),
        'Options' => array(),
        'Filters' => array()
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_LEADTYPE;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();
        $leadType_rows = array();
        $systemLeadType = Group::SYS_GROUPLEAD_TYPES;
        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

        $select = "SELECT grouplead_type.*,
        (SELECT COUNT(grouplead_typeid) FROM `groupleads` WHERE typeid=grouplead_typeid)+(SELECT COUNT(grouplead_typeid) FROM `group_channel_leads` WHERE typeid=grouplead_typeid)+(SELECT COUNT(grouplead_typeid) FROM `chapterleads` WHERE typeid=grouplead_typeid) AS total_leads 
        FROM grouplead_type 
        WHERE grouplead_type.companyid={$_COMPANY->id()} AND grouplead_type.zoneid IN (0,{$_ZONE->id()}) 
        GROUP BY typeid";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
        while (@$rows = mysqli_fetch_assoc($result)) {
            // Set data values
            $allow_create_content = $rows['allow_create_content'] ? 'Yes' : 'No';
            $allow_publish_content = $rows['allow_publish_content'] ? 'Yes' : 'No';
            $allow_manage = $rows['allow_manage'] ? 'Yes' : 'No';
            $allow_manage_grant = $rows['allow_manage_grant'] ? 'Yes' : 'No';
            $allow_manage_budget = $rows['allow_manage_budget'] ? 'Yes' : 'No';
            $allow_manage_support = $rows['allow_manage_support'] ? 'Yes' : 'No';
            $allow_publish_content = $rows['allow_publish_content'] ? 'Yes' : 'No';
            $show_on_aboutus = $rows['show_on_aboutus'] ? 'Yes' : 'No';
            $leadTypeIsActive = $rows['isactive'] ? 'Active' : 'Not Active';
            // Set system lead types
            $system_lead_type = $systemLeadType[$rows['sys_leadtype']];
            $row = array(
                'type' => $rows['type'],
                'sys_leadtype' => $system_lead_type,
                // 'totalGroupLeads' => $rows['totalGroupLeads'],
                // 'totalChannelLeads' => $rows['totalChannelLeads'],
                // 'totalChapterLeads' => $rows['totalChapterLeads'],
                'total_leads' => $rows['total_leads'],
                'allow_create_content' => $allow_create_content,
                'allow_publish_content' => $allow_publish_content,
                'allow_manage' => $allow_manage,
                'allow_manage_grant' => $allow_manage_grant,
                'allow_manage_budget' => $allow_manage_budget,
                'allow_manage_support' => $allow_manage_support,
                'show_on_aboutus' => $show_on_aboutus,
                'isactive' => $leadTypeIsActive,
                'welcome_message' => $rows['welcome_message']);
            array_push($leadType_rows, $row);
        }

        foreach ($leadType_rows as $data) {
            fputcsv($file_h, $data, $delimiter, $enclosure, $escape_char);
        }
    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;
        global $_ZONE;

        $reportmeta = parent::GetDefaultReportRecForDownload();

        if (!$_COMPANY->getAppCustomization()['booking']['enabled']) {
            unset($reportmeta['Fields']['allow_manage_support']);
        }

        return $reportmeta;
    }
}