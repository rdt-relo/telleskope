<?php

// Do no use require_once as this class is included in Company.php.

trait TopicApprovalTrait
{

    /**
     * @return mixed
     */
    abstract public static function GetTopicType(): string;

    /**
     * Returns all the stage id that user can approve
     * @param int $search_userid
     * @return array
     */
    public static function GetAllTheStagesThatUserCanApprove(int $search_userid): array
    {
        $approvalStages = [];
        for ($stage = 1; $stage <= Approval::APPROVAL_STAGE_MAX; $stage++) {
            $approvers = self::GetAllApproversByStage($stage, 0, 0, 0);
            if (in_array($search_userid, $approvers['approver_userids'])) {
                $approvalStages[] = $stage;
            }
        }
        return array_values($approvalStages);
    }

    abstract public function getTopicTitle(): string;

    /**
     * @return Approval|null
     */
    public function getApprovalObject()
    {
        return Approval::GetApprovalByTopicObject($this);
    }

    /**
     * @param string $requestNote
     * @return Approval|null
     */
    public function requestNewApproval(string $requestNote, array $selectedApprovers)
    {
        $approval = Approval::GetOrCreateNewApprovalByTopicObject($this);
        $approval ?-> request($requestNote, $selectedApprovers); // Note request method will update approval object
        return $approval;
    }

    /**
     * This method returns all Approval Configuration Rows for a given topic type.
     * Possible Use cases: When displaying configuration rows to allow user to view/edit approval settings
     * @param int $groupid optional, if provided only rows matcing groupid will be returned. In the future we might add more filters for Chapter and Channels.
     * @return array|void
     */
    public static function GetAllApprovalConfigurationRows(int $groupid=-1, int $chapterid=-1, int $channelid=-1, int $approvalStage=-1)
    {
        global $_USER, $_COMPANY, $_ZONE;
        $topicType = self::GetTopicType();
        $scopeFilter = '';

        // For now we are just ignoring the groupid, chapterid, channelid and scopeFilter
        //        if ($groupid >= 0) {
        //            $scopeFilter .= " AND `groupid`={$groupid} ";
        //        }
        //        if ($chapterid >= 0) {
        //            $scopeFilter .= " AND `chapterid`={$chapterid} ";
        //        }
        //        if ($channelid >= 0) {
        //            $scopeFilter .= " AND `channelid`={$channelid} ";
        //        }
        //    if ($approvalStage >= 0) {
        //        $scopeFilter .= " AND `approval_stage`={$approvalStage} ";
        //    }

        $key = "TOPIC_APPROVERS:{$_ZONE->id()}";
        if (($value = $_COMPANY->getFromRedisCache($key)) === false) {
            $value = self::DBGet("
                SELECT ta_configuration.*, ta__approvers.approval_approver_id, ta__approvers.approver_userid, ta__approvers.approver_role, ta_configuration.topic_type
                FROM `topic_approvals__configuration` AS ta_configuration
                    LEFT JOIN `topic_approvals__configuration_approvers` AS ta__approvers
                    ON ta_configuration.approval_config_id = ta__approvers.approval_config_id
                WHERE ta_configuration.companyid={$_COMPANY->id()} 
                    AND ta_configuration.zoneid={$_ZONE->id()} 
                    {$scopeFilter}
                    ");

            if (!is_array($value)) {
                $value = [];
            }

            $_COMPANY->putInRedisCache($key, $value, 86400);
        }

        $results = array_filter($value, function ($v) use ($topicType) {
                        return ($v['topic_type'] == $topicType);
                    });

        // Organize the results into an array with 'approvers' for each stage
        $configurationWithApprovers = [];
        foreach ($results as $result) {
            $configId = $result['approval_config_id'];
            if (!isset($configurationWithApprovers[$configId])) {
                $configurationWithApprovers[$configId] = $result;
                $configurationWithApprovers[$configId]['approvers'] = [];
            }
            if (!empty($result['approval_approver_id'])) {
                $configurationWithApprovers[$configId]['approvers'][] = [
                    'approval_approver_id' => $result['approval_approver_id'],
                    'approver_userid' => $result['approver_userid'],
                    'approver_role' => $result['approver_role'],
                ];
            }
        }

        return array_values($configurationWithApprovers);
    }

    public static function CreateApprovalConfiguration(int $groupid, int $chapterid, int $channelid, int $approvalStage)
    {
        global $_USER, $_COMPANY, $_ZONE;
        // If channel id or chapter id is set, then groupid should also be set.
        if (empty($groupid) && (!empty($chapterid) || !empty($channelid))) {
            return 0;
        }
        // Both chapterid and channelid cannot be set
        if (!empty($chapterid) && !empty($channelid)) {
            return 0;
        }

        // Approval Stage can only be between APPROVAL_STAGE_MIN and APPROVAL_STAGE_MAX
        if ($approvalStage < Approval::APPROVAL_STAGE_MIN || $approvalStage > Approval::APPROVAL_STAGE_MAX) {
            return 0;
        }
        $topicType = self::GetTopicType();

        $retVal = self::DBInsertPS(
            "INSERT INTO `topic_approvals__configuration` SET companyid=?, zoneid=?, groupid=?, chapterid=?, channelid=?, topic_type=?, approval_stage=?, createdby=?, modifiedby=?",
            "iiiiixiii",
            $_COMPANY->id(), $_ZONE->id(), $groupid, $chapterid, $channelid, $topicType, $approvalStage, $_USER->id(), $_USER->id()
        );
        $_COMPANY->expireRedisCache("TOPIC_APPROVERS:{$_ZONE->id()}");
        return $retVal;
    }

    public static function AddApproverToApprovalConfiguration(int $approvalConfigId, int $approverUserId, string $approverRoleTitle="")
    {
        global $_COMPANY, $_ZONE, $_USER;

        $existingApproverId = self::GetApproverIdIfExists($approvalConfigId, $approverUserId);

        if ($existingApproverId) {
            // Topic approver already exists, update their role title
            return self::UpdateApproverRoleTitle($existingApproverId, $approverRoleTitle);
        } else {
            // Topic approver doesn't exist, insert them into the new table
            $insertResult = self::InsertNewApprover($approvalConfigId, $approverUserId, $approverRoleTitle);
            return $insertResult;
        }
    }

    // Helper method to get the approver ID if it exists
    private static function GetApproverIdIfExists(int $approvalConfigId, int $approverUserId)
    {
        global $_COMPANY;
        $existingApprover = self::DBGet("SELECT approval_approver_id FROM topic_approvals__configuration_approvers WHERE approval_config_id={$approvalConfigId} AND approver_userid={$approverUserId} AND companyid={$_COMPANY->id()}");
        return (!empty($existingApprover)) ? $existingApprover[0]['approval_approver_id'] : null;
    }

    // Helper method to update the approver role title
    private static function UpdateApproverRoleTitle(int $approverId, string $approverRoleTitle="")
    {
        global $_USER, $_ZONE, $_COMPANY;
        $retVal = self::DBUpdatePS("UPDATE topic_approvals__configuration_approvers SET approver_role=?, modifiedon=NOW(), modifiedby=? WHERE approval_approver_id=? AND companyid=?" , "siii", $approverRoleTitle, $_USER->id(), $approverId, $_COMPANY->id());
        $_COMPANY->expireRedisCache("TOPIC_APPROVERS:{$_ZONE->id()}");
        return $retVal;
    }

    // Helper method to insert a new approver
    private static function InsertNewApprover(int $approvalConfigId, int $approverUserId, string $approverRoleTitle)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $retVal = self::DBInsertPS(
            "INSERT INTO topic_approvals__configuration_approvers SET approval_config_id=?, approver_userid=?, approver_role=?, companyid=?, createdby=?, modifiedby=?", "iisiii", $approvalConfigId , $approverUserId, $approverRoleTitle, $_COMPANY->id(), $_USER->id(), $_USER->id()
        );
        $_COMPANY->expireRedisCache("TOPIC_APPROVERS:{$_ZONE->id()}");
        return $retVal;
    }

    public static function DeleteApproverFromApprovalConfiguration(int $approvalConfigId, int $approverUserid)
    {
        global $_COMPANY, $_ZONE, $_USER;
        $row = self::DBUpdate("DELETE FROM topic_approvals__configuration_approvers WHERE approval_config_id={$approvalConfigId} AND approver_userid={$approverUserid} AND companyid={$_COMPANY->id()}");
        if ($row > 0) {
            // Update the 'modifiedon' and 'modifiedby' fields in 'topic_approvals__configuration'
            $updateQuery = "UPDATE topic_approvals__configuration SET modifiedon=now(), modifiedby={$_USER->id()} WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND approval_config_id={$approvalConfigId}";
            self::DBUpdate($updateQuery);
        }
        $_COMPANY->expireRedisCache("TOPIC_APPROVERS:{$_ZONE->id()}");
        return $row;
    }

    public static function UpdateApprovalConfigurationEmailSettings (int $approvalConfigId, string $approver_cc_emails, int $approver_max_approvers_limit, string $stage_approval_email_subject, string $stage_approval_email_body, string $stage_denial_email_subject, $stage_denial_email_body, int $disallow_submitter_approval)
    {
        global $_COMPANY,$_ZONE;

        $vals = array();
        $vals['approver_cc_emails'] = $approver_cc_emails;
        $vals['approver_max_approvers_limit'] = min($approver_max_approvers_limit, Approval::APPROVERS_SELECTED_MAX_LIMIT);
        $vals['stage_approval_emails'] = array(
            'subject' => $stage_approval_email_subject,
            'body' => $stage_approval_email_body
        );
        $vals['stage_denial_emails'] = array(
            'subject' => $stage_denial_email_subject,
            'body' => $stage_denial_email_body
        );
        $vals['disallow_submitter_approval'] = $disallow_submitter_approval;

        self::UpdateApprovalConfigurationAttributesKeyVal($approvalConfigId, 'emails', $vals);

        $_COMPANY->expireRedisCache("TOPIC_APPROVERS:{$_ZONE->id()}");
        return 1;
    }

    /**
     * Returns a list values
     * @param int $approval_config_id
     * @return array $approver_cc_emails, $approver_min_approvers_limit, $approver_max_approvers_limit, $stage_approval_email_subject, $stage_approval_email_body, $stage_denial_email_subject, $stage_denial_email_body
     */
    public static function GetApprovalConfigurationEmailSettings (int $approval_config_id)
    {
        global $_COMPANY, $_ZONE;

        $topicLabel = Teleskope::TOPIC_TYPES_ENGLISH[self::GetTopicType()];

        $stage_approval_email_subject = "{$topicLabel} Approver approved stage [[APPROVAL_STAGE]]";
        $stage_approval_email_body = "<p>Dear [[REQUESTER_FIRST_NAME]] [[REQUESTER_LAST_NAME]],</p>";
        $stage_approval_email_body .= "<p>Congratulations! Your approval request for {$topicLabel} <b>[[APPROVAL_TOPIC_TITLE]]</b> ({$topicLabel} ID : [[APPROVAL_TOPIC_ID]]) has been approved at stage [[APPROVAL_STAGE]]!</p>";
        $stage_approval_email_body .= "<p>Approvers Note: [[APPROVER_NOTE]]</p>";
        $stage_approval_email_body .= "<p>[[APPROVAL_LOG_ATTACHMENTS]]</p>";
        $stage_approval_email_body .= "<p>Sincerely,</p>";
        $stage_approval_email_body .= "<p>[[APPROVER_FIRST_NAME]] [[APPROVER_LAST_NAME]]</p>";

        $stage_denial_email_subject = $topicLabel.' Approver denied the '.$topicLabel;
        $stage_denial_email_body = "<p>Dear [[REQUESTER_FIRST_NAME]] [[REQUESTER_LAST_NAME]],</p>";
        $stage_denial_email_body .= "<p>Uh oh! Your approval request for {$topicLabel} <b>[[APPROVAL_TOPIC_TITLE]]</b> ({$topicLabel} ID : [[APPROVAL_TOPIC_ID]]) has been denied at stage [[APPROVAL_STAGE]]!</p>";
        $stage_denial_email_body .= "<p>Approvers Note: [[APPROVER_NOTE]]</p>";
        $stage_denial_email_body .= "<p>Sincerely,</p>";
        $stage_denial_email_body .= "<p>[[APPROVER_FIRST_NAME]] [[APPROVER_LAST_NAME]]</p>";

        $approver_cc_emails = '';
        $approver_max_approvers_limit = Approval::APPROVERS_SELECTED_DEFAULT;
        $approver_min_approvers_limit = 1;
        $disallow_submitter_approval = 0;

        $approvalConfigurationDetail = self::GetApprovalConfigurationDetail($approval_config_id);
        if ($approvalConfigurationDetail) {
            $approvalConfigurationAttributes = Arr::Json2Array($approvalConfigurationDetail['attributes']);
            if (!empty($approvalConfigurationAttributes['emails']['approver_cc_emails'])) {
                $approver_cc_emails  = $approvalConfigurationAttributes['emails']['approver_cc_emails'];
            }
            if (isset($approvalConfigurationAttributes['emails']['approver_max_approvers_limit'])) { #note: using isset as limit can be 0
                $approver_max_approvers_limit  = (int)$approvalConfigurationAttributes['emails']['approver_max_approvers_limit'];
            }
            if (!empty($approvalConfigurationAttributes['emails']['stage_approval_emails']['subject'])) {
                $stage_approval_email_subject = $approvalConfigurationAttributes['emails']['stage_approval_emails']['subject'];
            }
            if (!empty($approvalConfigurationAttributes['emails']['stage_approval_emails']['body'])) {
                $stage_approval_email_body = $approvalConfigurationAttributes['emails']['stage_approval_emails']['body'];
            }
            if (!empty($approvalConfigurationAttributes['emails']['stage_denial_emails']['subject'])) {
                $stage_denial_email_subject = $approvalConfigurationAttributes['emails']['stage_denial_emails']['subject'];
            }
            if (!empty($approvalConfigurationAttributes['emails']['stage_denial_emails']['body'])) {
                $stage_denial_email_body = $approvalConfigurationAttributes['emails']['stage_denial_emails']['body'];
            }
            if (isset($approvalConfigurationAttributes['emails']['disallow_submitter_approval']) && !empty($approvalConfigurationAttributes['emails']['disallow_submitter_approval'])) {
                $disallow_submitter_approval = $approvalConfigurationAttributes['emails']['disallow_submitter_approval'];
            } 
            if ($approvalConfigurationDetail['approval_stage'] > 1) {
                $approver_min_approvers_limit = 0;
            }
        }

        return array($approver_cc_emails, $approver_min_approvers_limit, $approver_max_approvers_limit, $stage_approval_email_subject, $stage_approval_email_body, $stage_denial_email_subject, $stage_denial_email_body, $disallow_submitter_approval);
    }

    public static function DeleteApprovalConfiguration (int $approvalConfigId)
    {
        global $_USER, $_COMPANY, $_ZONE;

        $retVal = self::DBUpdate("DELETE FROM `topic_approvals__configuration` WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND approval_config_id={$approvalConfigId}");
        $_COMPANY->expireRedisCache("TOPIC_APPROVERS:{$_ZONE->id()}");
        return $retVal;
    }

    public static function UpdateAutoApprovalConfiguration (int $approvalConfigId, $autoApprovalConfiguration)
    {
        global $_COMPANY,$_ZONE,$_USER;

        $retVal = self::DBUpdatePS(
            "UPDATE `topic_approvals__configuration` SET auto_approval_configuration=?, modifiedby=?, modifiedon=now() WHERE companyid=? AND zoneid=? AND approval_config_id=?",
            "xiiii",
            $autoApprovalConfiguration,$_USER->id(), $_COMPANY->id(), $_ZONE->id(), $approvalConfigId
        );
        $_COMPANY->expireRedisCache("TOPIC_APPROVERS:{$_ZONE->id()}");
        return $retVal;
    }

    public static function GetAutoApprovalDataByStage (int $approvalStage)
    {
        global $_COMPANY,$_ZONE,$_USER;
        $topicType = self::GetTopicType();
        $data =  self::DBGet("SELECT auto_approval_configuration FROM `topic_approvals__configuration` WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND approval_stage={$approvalStage} AND topic_type='{$topicType}'");

        if($data && isset($data[0]['auto_approval_configuration'])){
            $autoApprovalConfiguration = $data[0]['auto_approval_configuration'];
            if($autoApprovalConfiguration !== NULL){
                return json_decode($autoApprovalConfiguration, true);
            }
        }
        return [];
    }

    public static function DeleteAutoApprovalCriterion (int $approvalStage, int $approvalConfigId, int $custom_field_id)
    {
        global $_COMPANY,$_ZONE,$_USER;
        $data = self::GetAutoApprovalDataByStage($approvalStage);
        if($data){
            foreach ($data as $index => $entry) {
                if (isset($entry['custom_field_id']) && $entry['custom_field_id'] == $custom_field_id) {
                    unset($data[$index]);
                    break; //exit if unset
                }
            }
        $json_data = json_encode(array_values($data));
        $result =  self::UpdateAutoApprovalConfiguration($approvalConfigId,$json_data);
        return $result;
        }
        return false;
    }
    /**
     * Returns an array of 'approver_userids' for a given stage and scope (groupids,chapterids,channelids)
     * @param int $approvalStage - if 0 all approvers across all stages are returned.
     * @param string $groupids
     * @param string $chapterids
     * @param string $channelids
     * @return array[]
     */

    public static function GetAllApproversByStage(int $approvalStage = 1, string $groupids='0', string $chapterids='0', string $channelids='0')
    {
        $retVal = array('approver_userids' => array(), 'approver_role' => array(), 'approval_config_id' => -1);
        // If channel id or chapter id is set, then groupid should also be set.
        if (empty($groupids) && (!empty($channelids) || !empty($channelids))) {
            return $retVal;
        }

        // Approval Stage can only <= APPROVAL_STAGE_MAX
        if ($approvalStage > Approval::APPROVAL_STAGE_MAX) {
            return 0;
        }

        $groupids_arr = array_map('intval',explode(',', $groupids) ?: array());
        $chapterids_arr = array_map('intval',explode(',', $chapterids) ?: array());
        $channelids_arr = array_map('intval',explode(',', $channelids) ?: array());

        $allApprovalConfigurationRows = self::GetAllApprovalConfigurationRows();
        $approverUserids = array();
        //$approverCCEmails = array();
        $approver_role = array();
        $approval_config_id = -1;

        foreach ($allApprovalConfigurationRows as $approvalConfigurationRow) {
            if (($approvalConfigurationRow['approval_stage'] == $approvalStage || $approvalStage == 0)
                //&& For now we are just ignoring the group/chapter/channel matches. That is a topic for another day.
                // Do not delete the code below. It works
//                !empty(array_intersect(array($approvalConfigurationRow['groupid']), $groupids_arr)) &&
//                (empty($approvalConfigurationRow['chapterid']) || !empty(array_intersect(array($approvalConfigurationRow['chapterid']), $chapterids_arr))) &&
//                (empty($approvalConfigurationRow['channelid']) || !empty(array_intersect(array($approvalConfigurationRow['channelid']), $channelids_arr)))
            ) {
                $approval_config_id = $approvalConfigurationRow['approval_config_id'];
                foreach ($approvalConfigurationRow['approvers'] as $approver) {
                    $approverUserids[] = $approver['approver_userid'];
                    $approver_role[] = $approver['approver_role'];
                }
                //$approverCCEmails[] = $approvalConfigurationRow['approver_cc_emails'];
            }
        }

        // Using array values to get true arrays
        $retVal['approver_userids'] = array_values($approverUserids);
        //$retVal['cc_emails'] = array_values(array_unique($approverCCEmails));
        $retVal['approver_role'] = array_values($approver_role);
        $retVal['approval_config_id'] = $approval_config_id;
        return $retVal;
    }

    /**
     * This method gets all approval rows from Read Only instance of DB. When doing a get after update add a 1 second
     * delay between the calls.
     * @param string $approvalStatus
     * @param string $requestYear
     * @param int $search_userid
     * @param int $start
     * @param int $limit
     * @return array|mysqli_result
     */
    public static function GetAllApprovalRows(string $approvalStatus, string $requestYear, int $search_userid, int $start=0, int $limit=0)
    {
        global $_COMPANY, $_ZONE;
        $topicType = self::GetTopicType();

        $approval_status = "";
        //if ($approvalStatus == 'requested') {
        if ($approvalStatus == 'processing') {
            $approval_status = "'" . Approval::TOPIC_APPROVAL_STATUS['PROCESSING'] . "','" . Approval::TOPIC_APPROVAL_STATUS['REQUESTED'] . "'";
        } elseif($approvalStatus == 'processed') {
            $approval_status = "'" . Approval::TOPIC_APPROVAL_STATUS['APPROVED'] . "','" . Approval::TOPIC_APPROVAL_STATUS['DENIED'] . "'";
        } elseif ($approvalStatus == 'reset') {
            // Note: After the approval is denied, if the user edits the topic, the deny status is changed to reset.
            $approval_status = "'" . Approval::TOPIC_APPROVAL_STATUS['RESET'] . "','" . Approval::TOPIC_APPROVAL_STATUS['CANCELLED'] . "'";
        }
        $approvalStatusCondtion =  $approval_status ?  " AND approval_status IN ($approval_status)" : '';

        $userCondition = '';
        if ($search_userid) {
            $userCondition = " AND FIND_IN_SET({$search_userid}, topic_approvals.approver_userids)";
        }
        
        $requestYearCondition = " AND YEAR(topic_approvals.createdon) = YEAR(CURDATE())";
        if (!empty($requestYear)) {
            $requestYearCondition = " AND YEAR(topic_approvals.createdon) = '{$requestYear}'";
        }

        return self::DBROGet("SELECT topic_approvals.* FROM `topic_approvals` WHERE topic_approvals.companyid={$_COMPANY->id()} AND topic_approvals.zoneid={$_ZONE->id()} AND topictype='{$topicType}' {$userCondition} {$approvalStatusCondtion} {$requestYearCondition} ORDER BY topic_approvals.createdon DESC");
    }
    /**
     * Get topic approvals for the user at all stages they are an approver of.
     *
     * @param int $search_userid The ID of the user.
     * @return array An array of topic approvals for all stages where the user is an approver.
     */
    public static function GetApprovalsForUser(int $search_userid): array
    {
        global $_COMPANY, $_ZONE;

        // Find out all the stages that user can approve.
        $approvalStages = self::GetAllTheStagesThatUserCanApprove($search_userid);

        if (!empty($approvalStages)) {
            // User is an approver at one or more stages
            $topicType = self::GetTopicType();

            $approvalStagesStr = implode(', ', $approvalStages);
            //$stageOrUserFilter = "AND (topic_approvals.approval_stage IN ({$approvalStagesStr}) OR topic_approvals.assigned_to={$search_userid})";
            $stageOrUserFilter = "AND (topic_approvals.approval_stage IN ({$approvalStagesStr}))";

            return self::DBGet("
                SELECT topic_approvals.* FROM `topic_approvals` 
                WHERE topic_approvals.companyid={$_COMPANY->id()} 
                    AND topic_approvals.zoneid={$_ZONE->id()} 
                    AND topictype='{$topicType}' 
                    {$stageOrUserFilter}
                ORDER BY topic_approvals.createdon DESC"
            );
        }

        return [];
    }

    public static function GetApprovalConfigurationDetail(int $approval_config_id)
    {
        global $_COMPANY, $_ZONE;

        $approvalConfiguration = self::DBGet("
                SELECT ta_configuration.*
                FROM `topic_approvals__configuration` AS ta_configuration
                WHERE ta_configuration.companyid={$_COMPANY->id()} 
                    AND ta_configuration.zoneid={$_ZONE->id()} 
                    AND ta_configuration.approval_config_id = '{$approval_config_id}'
            ");
        return $approvalConfiguration ? $approvalConfiguration[0] : null;

    }

    /**
     * Sets the configuration key in attributes JSON column. The key can be in the dot notation, e.g. emails.approvals
     * ***** If $configuration_val is null, the the value is removed if it exists *****
     * @param int $approval_configuration_id
     * @param string $configuration_key
     * @param mixed $configuration_val
     * @return int
     */
    private static function UpdateApprovalConfigurationAttributesKeyVal (int $approval_configuration_id, string $configuration_key, mixed $configuration_val): int
    {
        global $_COMPANY, $_ZONE, $_USER;
        $retVal = 0;

        if (!$approval_configuration_id) {
            return 0;
        }

        // First construct a $configuration_array
        $configuration_array = array($configuration_key => $configuration_val);
        $json_doc = json_encode(Arr::Undot($configuration_array)); // $configuration_key may be in dot notation
        //$sql = "UPDATE configuration SET keyvals = JSON_MERGE_PATCH(keyvals, '{$." . implode(".$", $keys) . "': $value}')";
        $json_doc = json_encode(Arr::Undot(array($configuration_key=>$configuration_val)));
        $retVal = self::DBMutatePS("
            UPDATE `topic_approvals__configuration` 
            SET attributes=JSON_MERGE_PATCH(IFNULL(attributes,JSON_OBJECT()), ?), modifiedon=NOW(), modifiedby=? 
            WHERE companyid=? AND approval_config_id=?",
            'xiii',
            $json_doc, $_USER->id(), $_COMPANY->id(), $approval_configuration_id
        );

        if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'topic_approval__configuration', $approval_configuration_id, 0, $configuration_array);
        }

        return $retVal;
    }


    /**
     * All this function does is it checks the topics custom fields and see if the criteria desired for auto approval
     * is met. If it is met, it returns true, else it returns false.
     * @param array $auto_approval_configuration the auto approval configuration against which the value should be tested.
     * @return bool
     */
    public function isAutoApprovalCriteriaMet(array $auto_approval_configuration): bool
    {
        $criteriaMatched = false;

        if (empty($auto_approval_configuration)) {
            return false;
        }

        $topic_custom_fields = json_decode($this->val('custom_fields') ?? '', true);
        if (empty($topic_custom_fields)) {
            return false;
        }

        $groupedCriteriaMatched = null; // Important: We set it to null here, we will initialize this variable on first use.
        $individualCriteriaMatched = false;
        foreach ($auto_approval_configuration as $auto_approval_configuration_item) {

            // Find the topic custom field with the same custom_field_id
            $matching_topic_custom_field = Arr::SearchColumnReturnRow($topic_custom_fields, $auto_approval_configuration_item['custom_field_id'], 'custom_field_id');

            // If custom field is found, check if any desired value exists in the topic custom field's values
            if ($matching_topic_custom_field) {
                $subCriteriaMatched = array_intersect($auto_approval_configuration_item['value'], $matching_topic_custom_field['value']);
                if (!empty($auto_approval_configuration_item['condition_group'])) {
                    $groupedCriteriaMatched = $groupedCriteriaMatched ?? true; // If $groupedCriteriaMatched is null, initialize it.
                    $groupedCriteriaMatched = $groupedCriteriaMatched && $subCriteriaMatched;
                } else {
                    $individualCriteriaMatched = $individualCriteriaMatched || $subCriteriaMatched;
                }
            }
        }

        // At the end all groupedCriteria should match or atleast some individualCriteriaMatched should be matched
        return ($groupedCriteriaMatched === true) || $individualCriteriaMatched;
    }
}


