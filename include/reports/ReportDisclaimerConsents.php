<?php

class ReportDisclaimerConsents extends Report{
    public const META = array(
        'Fields' => array(
            'disclaimer_name' => 'Disclaimer Name',
            'invocation_type' => 'Invocation Type',
            'trigger' => 'Disclaimer Trigger',
            'disclaimer_lang' => 'Disclaimer Language',
            'disclaimer_version' => 'Disclaimer Version',
            'consent_context' => 'Context',
            'email' => 'Email',
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'jobtitle' => 'Job Title',
            'consent_text' => 'Consent Text',
            'ipaddress' => 'Consent From IP Address',
            'createdon' => 'Consent Date'
        ),
        'AdminFields' => array(
            'externalid' => 'Employee Id',
        ),
        'Options' => array(
           
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_DISCLSIMER_CONTENTS;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }
        // get the diclaimer id here and use it SQL
        $disclaimerid_filter = '';
        if (!empty($meta['Options']) && !empty($meta['Options']['disclaimerid'])) {
            $disclaimerid = $meta['Options']['disclaimerid'];
            $disclaimerid_filter = " AND disclaimers.disclaimerid = {$disclaimerid}";
        }
        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

        $select = "SELECT disclaimers.*,disclaimer_consents.createdon as consent_date, disclaimer_consents.userid,disclaimer_consents.consent_contextid,disclaimer_consents.ipaddress,disclaimer_consents.consent_text,disclaimer_consents.disclaimer_version,disclaimer_consents.disclaimer_lang,users.firstname,users.lastname,users.email,users.jobtitle, users.externalid
        FROM disclaimers 
        JOIN disclaimer_consents ON disclaimer_consents.disclaimerid=disclaimers.disclaimerid 
        LEFT JOIN users ON users.userid = disclaimer_consents.userid
        WHERE disclaimers.companyid ={$this->cid()}
        AND disclaimers.zoneid ={$_ZONE->id()}
        {$disclaimerid_filter}
        ORDER BY disclaimers.`version` DESC {$this->policy_limit}";        

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$rows = mysqli_fetch_assoc($result)) {

            // Prepare the disclaimer title data
            // if (!empty($rows['disclaimer'])) {
            //     $disclaimerData = json_decode($rows['disclaimer'], true);
            //     if(!empty($disclaimerData)){
            //         $disclaimerDataByLang = $disclaimerData[$rows['disclaimer_lang']];
            //         if (empty($disclaimerDataByLang)){
            //             $disclaimerDataByLang = $disclaimerData['en'];
            //         }
            //         $rows['disclaimer'] = $disclaimerDataByLang['title'];
            //     }
            // }
            $rows['jobtitle'] = (!empty($rows['jobtitle'])) ? $rows['jobtitle'] : ' - ';
            $rows['consent_text'] = (!empty($rows['consent_text'])) ? $rows['consent_text'] : ' - ';
            // Disclaimer Trigger
            $rows['trigger'] = Disclaimer::GetDisclaimerTriggerLabel($rows['hookid']);

            $rows['consent_context'] = '';

            // In the below list only add disclaimer hooks which need group context
            if (in_array($rows['hookid'], [
                Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_JOIN_BEFORE'],
                Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_LEAVE_BEFORE'],
                Disclaimer::DISCLAIMER_HOOK_TRIGGERS['TEAMS__CIRCLE_CREATE_BEFORE'],
            ])) {
                if ($rows['consent_contextid']) {
                    $rows['consent_context'] = $this->getGroupName($rows['consent_contextid']);
                }
            }
            // In the below list only add disclaimer hooks which need event context
            elseif(in_array($rows['hookid'], [
                Disclaimer::DISCLAIMER_HOOK_LINKS['EVENT_RSVP']
            ])) {
                if ($rows['consent_contextid']) {
                    $rows['consent_context'] = $this->getEventName($rows['consent_contextid']);
                }
            }

            $rows['externalid'] = explode(':', $rows['externalid'] ?? '')[0];
            // Date set
            $rows['createdon'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['consent_date'], $_SESSION['timezone']);
            $row = array();
            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
        }
    }

}