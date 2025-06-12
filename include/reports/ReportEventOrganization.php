<?php

class ReportEventOrganization extends Report
{
    public const META = array(

        'Fields' => array(
            'enc_eventid' => 'Event ID',
            'eventtitle' => 'Event Name',
            'start' => 'Event Start Datetime',
            'end' => 'Event End Datetime',
            'eventstatus' => 'Event Publish Status',
            'externalid' => 'Submitter Employee Id',
            'enc_organization_id' => 'Organization ID',
            'organization_name' => 'Organization Name',
            'organization_taxid' => 'Organization Tax ID',
            'organization_type' => 'Organization Type',

        ),
        'AdminFields' => array(),
        'Options' => array(),
        'Filters' => array(
            'groupids' => '',
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_EVENT_ORGANIZATION;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        $groupid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
            $groupid_list = implode(',', $meta['Filters']['groupids']);

            // Note: If groupids are given then we will include events that have collborating groupids in our sql.
            // This is step_1:collaborating_group_filter
            // Since this mechanism includes all events that have collaboration set, we will filter out the events
            // that do not match with selected groups when processing the data (see step_2:collaborating_group_filter)
            $groupid_filter = " AND (ev.groupid IN ({$groupid_list}) OR (ev.groupid=0 AND ev.collaborating_groupids != ''))";
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

        $select = "
                SELECT ev.eventid, ev.eventtitle, ev.start, ev.end, ev.isactive AS eventstatus, 
                    org.organization_id, org.organization_name, org.organization_taxid, org.api_org_id, org.createdby,
                    eorg.custom_fields 
                FROM `events` ev
                    JOIN event_organizations eorg ON ev.eventid = eorg.eventid 
                    JOIN company_organizations org ON org.organization_id = eorg.organizationid  
                WHERE ev.companyid={$_COMPANY->id()} 
                    AND ev.zoneid={$_ZONE->id()}
                    {$groupid_filter}
                    ";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$rows = mysqli_fetch_assoc($result)) {
            $rows['organization_type'] = '-';

            $partnerOrg = Organization::GetOrganizationFromPartnerPath($rows['api_org_id']);
            if (!empty($partnerOrg)) {
                $partnerOrg = $partnerOrg['results'][0];
                if (!empty($partnerOrg['OrganizationType'])) {
                    $rows['organization_type'] = Organization::ORGANIZATION_TYPE_MAP[$partnerOrg['OrganizationType']];
                }
            }

            $createdby = User::GetUser($rows['createdby']);
            $rows['externalid'] = $createdby ? $createdby->getExternalId() : '';
            $rows['enc_eventid'] = $_COMPANY->encodeIdForReport($rows['eventid']);
            $rows['enc_organization_id'] = $_COMPANY->encodeIdForReport($rows['organization_id']);
            
            // Set fields of csv
            $event_tz = (!empty($rows['timezone'])) ? $rows['timezone'] : 'UTC';
            $rows['start'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['start'], $event_tz);
            $rows['end'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['end'], $event_tz);
            $rows['eventstatus'] = self::CONTENT_STATUS_MAP[$rows['eventstatus']];
          
            $rows = $this->addCustomFieldsToRow($rows, $meta);
            $row = array();
            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            
            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
        }


    }

}