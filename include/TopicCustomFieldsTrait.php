<?php

trait TopicCustomFieldsTrait
{
    //const CUSTOM_FIELD_TYPES = array(
    //    'RADIO' => 1,
    //    'CHECKBOX' => 2,
    //    'TEXTAREA' => 3,
    //    'TEXT' => 4,
    //);

    /**
     * Get Events custom fields
     * @return array custom fields, if no custom fields are found then an empty array is returned.
     */
    public static function GetEventCustomFields(bool $activeOnly = true, bool $activeOptionOnly = true, bool $dropdownOrCheckboxTypeOnly = false, ?int $zoneid=null) :array
    {
        global $_COMPANY, $_ZONE;

        $zoneid = $zoneid ?? $_ZONE->id(); // Use zoneid if provided

        $data = array();
        $activeCondtion = "";
        if($activeOnly){
            $activeCondtion = " AND isactive=1";
        }

        $customFieldTypeCondition ="";
        if($dropdownOrCheckboxTypeOnly){
            $customFieldTypeCondition = " AND custom_fields_type IN (1, 2)";
        }

        $topictype = self::GetTopicType();

        $d =  self::DBGet("SELECT * FROM `event_custom_fields` WHERE `companyid`='{$_COMPANY->id()}' AND zoneid='{$zoneid}' AND `topictype` = '{$topictype}' {$activeCondtion} {$customFieldTypeCondition}");
        if (count($d)){
            $activeOptionCondtion = "";
            if($activeOptionOnly){
                $activeOptionCondtion = " AND isactive=1";
            }
            for($i=0;$i<count($d);$i++){
                $d[$i]['options'] = self::DBGet("SELECT * FROM `event_custom_field_options` WHERE `custom_field_id`='{$d[$i]['custom_field_id']}' {$activeOptionCondtion}");
                $d[$i]['visible_if'] = $d[$i]['visible_if'] ? json_decode($d[$i]['visible_if'],true) : array();
                $data[] = $d[$i];
            }
        }

        usort($data, function ($item1, $item2) {
            return $item1['sorting_order'] <=> $item2['sorting_order'];
        });
        return $data;
    }

    /**
     * Get event custom fields Name
     */
    public static function GetEventCustomFieldName(int $custom_field_id) {
        global $_COMPANY;
        global $_ZONE;
        $data = null;
        $d =  self::DBGet("SELECT custom_field_name FROM `event_custom_fields` WHERE `companyid`='{$_COMPANY->id()}' AND `custom_field_id`='{$custom_field_id}'");
        if (count($d)){
            $data = $d[0]['custom_field_name'];
        }
        return $data;
    }

    public function getCustomFieldsAsArray()
    {
        global $_ZONE;
        $retVal = [];
        $topic_zoneid = $this->val('zoneid') ?: $_ZONE->id();
        $event_custom_fields = json_decode($this->fields['custom_fields'] ?? '', true);
        if (!empty($event_custom_fields)) {
            foreach (self::GetEventCustomFields(zoneid: $topic_zoneid) as $custom_field) {

                $current_values = array_filter( // Find matching field values if set
                    $event_custom_fields, function ($value) use ($custom_field) {
                    return ($value['custom_field_id'] == $custom_field['custom_field_id']);
                });
                if ($custom_field['custom_fields_type'] == 1) { // Single Value
                    $ids = (empty($current_values) ? '0' : implode(',', array_column($current_values, 'value')[0]));
                    $fieldVals = self::GetCustomFieldOptionsByIdsCSV($ids, true);
                } else if ($custom_field['custom_fields_type'] == 2) { //Multiple Values
                    $ids = empty($current_values) ? '0' : implode(',',array_column($current_values, 'value')[0]);
                    $fieldVals = self::GetCustomFieldOptionsByIdsCSV($ids, true);
                } else if (($custom_field['custom_fields_type'] == 3) || ($custom_field['custom_fields_type'] == 4)) { // Text box
                    $fieldVals = (empty($current_values) ? '' : array_column($current_values, 'value')[0]);
                } else {
                    $fieldVals = '';
                }
                if (is_array($fieldVals)) {
                    $fieldVals = (implode(', ', $fieldVals));
                }
                $customFieldName = self::GetEventCustomFieldName($custom_field['custom_field_id']);
                $retVal[$customFieldName] = $fieldVals;
            }
        }
        return $retVal;
    }

    public static function GetCustomFieldOptionsByIdsCSV (string $custom_field_option_id, bool $returnOnlyOptionNameCsv = false, bool $activeOptionOnly = true)
    {

        $activeOptionCondtion = "";
        if($activeOptionOnly){
            $activeOptionCondtion = " AND isactive=1";
        }

        $custom_field_option_ids = Sanitizer::SanitizeIntegerCSV($custom_field_option_id);
        if(!$custom_field_option_ids){
            $custom_field_option_ids = '0';
        }
        $data = self::DBGet("SELECT * FROM `event_custom_field_options` WHERE `custom_field_option_id` IN({$custom_field_option_ids}) {$activeOptionCondtion}");

        if ($returnOnlyOptionNameCsv) {
            return implode(", ", array_column($data, 'custom_field_option'));

        } else {
            return $data;
        }

    }

    public function renderCustomFieldsComponent(string $version = 'v1'): string
    {
        $custom_fields = $this->getCustomFieldsAsArray();

        ob_start();
        require __DIR__ . "/../affinity/views/components/custom_fields/{$version}.html.php";
        return ob_get_clean();
    }

    private static function IsCustomFieldUsedInApprovals(int $customFieldId): bool
    {
        if (!method_exists(static::class, 'GetAutoApprovalDataByStage')) {
            return false;
        }

        $matchFound = '';
        for ($approvalStage=1; $approvalStage <= Approval::APPROVAL_STAGE_MAX; $approvalStage++) {
            $customFieldInConfig = self::GetAutoApprovalDataByStage ($approvalStage);
            if (!empty($customFieldInConfig)) {
            $matchFound = in_array($customFieldId, array_column($customFieldInConfig, 'custom_field_id'));
                if ($matchFound) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function IsCustomFieldUsedInEvents(int $customFieldId): ?bool
    {
        global $_COMPANY, $_ZONE;

        switch (self::GetTopicType()) {
            case 'EVT':
                $allMatchingEvents = self::DBGet("
                    SELECT  `eventid`
                    FROM    `events`
                    WHERE   `custom_fields` != ''
                    AND     JSON_CONTAINS(`custom_fields`, '{\"custom_field_id\": $customFieldId}', '$')
                    AND     `companyid` = {$_COMPANY->id()}
                    AND     `zoneid` = {$_ZONE->id()}
                    LIMIT 1
                ");

                return !empty($allMatchingEvents);

            case 'EXP':
                $allMatchingExpenseEntries = self::DBGET("
                    SELECT  `usesid`
                    FROM    `budgetuses`
                    WHERE   `custom_fields` != ''
                    AND     JSON_CONTAINS(`custom_fields`, '{\"custom_field_id\": $customFieldId}', '$')
                    AND     `companyid` = {$_COMPANY->id()}
                    AND     `zoneid` = {$_ZONE->id()}
                    LIMIT   1
                ");

                return !empty($allMatchingExpenseEntries);

            case 'BRQ':
                $allMatchingBudgetRequests = self::DBGET("
                    SELECT  `request_id`
                    FROM    `budget_requests`
                    WHERE   `custom_fields` != ''
                    AND     JSON_CONTAINS(`custom_fields`, '{\"custom_field_id\": $customFieldId}', '$')
                    AND     `companyid` = {$_COMPANY->id()}
                    AND     `zoneid` = {$_ZONE->id()}
                    LIMIT   1
                ");

                return !empty($allMatchingBudgetRequests);

            case 'EVTSPK':
                $allMatchingEventSpeakers = self::DBGET("
                    SELECT  `speakerid`
                    FROM    `event_speakers`
                    WHERE   `custom_fields` != ''
                    AND     JSON_CONTAINS(`custom_fields`, '{\"custom_field_id\": $customFieldId}', '$')
                    AND     `companyid` = {$_COMPANY->id()}
                    AND     `zoneid` = {$_ZONE->id()}
                    LIMIT   1
                ");

                return !empty($allMatchingEventSpeakers);

            case 'ORG':
                $allMatchingOrganizations = self::DBGET("
                    SELECT  `eventorganizationid`
                    FROM    `event_organizations`
                    WHERE   `custom_fields` != ''
                    AND     JSON_CONTAINS(`custom_fields`, '{\"custom_field_id\": $customFieldId}', '$')
                    AND     `companyid` = {$_COMPANY->id()}
                    LIMIT   1
                ");

                return !empty($allMatchingOrganizations);    

            case 'REC':
                $allMatchingRecognitions = self::DBGET("
                    SELECT  `recognitionid`
                    FROM    `recognitions`
                    WHERE   `custom_fields` != ''
                    AND     JSON_CONTAINS(`custom_fields`, '{\"custom_field_id\": $customFieldId}', '$')
                    AND     `companyid` = {$_COMPANY->id()}
                    LIMIT   1
                ");

                return !empty($allMatchingRecognitions);

        }
        return false;
    }

    // Delete event custom field permanently
    public static function DeleteEventCustomField(int $custom_field_id): bool
    {
        global $_COMPANY, $_ZONE;
        $topictype = self::GetTopicType();
        // Get events matching with custom field id
        $allMatchingEvents = self::IsCustomFieldUsedInEvents($custom_field_id);
        // Check if the custom field is in any stage of auto approval configuration
        $matchCustomFieldInConfig =  self::IsCustomFieldUsedInApprovals($custom_field_id);
        // Delete the custom field
        if(!$allMatchingEvents && !$matchCustomFieldInConfig){
            return self::DBMutate("DELETE FROM `event_custom_fields` WHERE `companyid`='{$_COMPANY->id()}' AND zoneid={$_ZONE->id()} AND `custom_field_id`='{$custom_field_id}' AND `topictype` = '{$topictype}'");
        }
        return false;
    }
}
