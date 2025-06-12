<?php

class Approval extends Teleskope
{
    const APPROVAL_STAGE_MIN = 1; // Default
    const APPROVAL_STAGE_MAX = 3;

    const APPROVERS_SELECTED_MAX_LIMIT = 25;
    const APPROVERS_SELECTED_DEFAULT = 5;

    private $topicObject = null; /* @ TopicApproval $topicObject */
    private $approvalConfiguration = null; /*@TopicApprovalConfiguration $approvalConfiguration */
    private $nextStageApprovalConfiguration = null; /*@TopicApprovalConfiguration $nextStageApprovalConfiguration */
    private ?array $approval_logs = null;

    const TOPIC_APPROVAL_STATUS = array(
        'REQUESTED' => 'requested',
        'PROCESSING' => 'processing',
        'APPROVED' => 'approved',
        'DENIED' => 'denied',
        'RESET' => 'reset',
        'CANCELLED' => 'cancelled'
    );

    const STATUS_LABEL_CSS_CLASS_MAP = array(
        'requested' => 'alert-info',
        'processing' => 'alert-warning',
        'approved' => 'alert-success',
        'denied' => 'alert-danger',
        'cancelled' => 'alert-info',
        'reset' => 'alert-info'
    );

    use TopicAttachmentTrait;

    public static function GetTopicType(): string
    {
        return self::TOPIC_TYPES['APPROVAL'];
    }

    protected function __construct($id, $cid, $fields, $topicObject)
    {
        parent::__construct($id, $cid, $fields);
        //declaring it protected so that no one can create it outside this class.
        $this->topicObject = $topicObject;
    }

    public static function GetApproval(int $id): ?Approval
    {
        global $_COMPANY;

        $approval = self::DBROGet("SELECT * FROM `topic_approvals` WHERE `approvalid` = {$id} AND `companyid` = {$_COMPANY->id()}");

        if (empty($approval)) {
            return null;
        }

        $topic = Teleskope::GetTopicObj($approval[0]['topictype'], $approval[0]['topicid']);

        return new Approval($id, $_COMPANY->id(), $approval[0], $topic);
    }

    /**
     * @param object $topicObject
     * @return Approval|null
     * @deprecated This function is marked deprecated as it should not be used by anyone other than TopicApprovals Interface
     */
    public static function GetApprovalByTopicObject(object $topicObject)
    {
        global $_USER, $_COMPANY;
        $obj = null;

        $topicType = $topicObject::GetTopicType();
        $topicId = $topicObject->id();

        if (!in_array($topicType, array_values(self::TOPIC_TYPES))) { // validate if we have valid topic type
            return null;
        }

        $topicZoneid = $topicObject->val('zoneid');

        // Since we have validated the only string argument against valid values, i.e. topicType, it is safe to used DBGet instead of DBGetPS
        $r1 = self::DBGet("SELECT * FROM `topic_approvals` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$topicZoneid} AND topictype='{$topicType}' AND topicid={$topicId}");
        if (!empty($r1)) {
            $obj = new Approval($r1[0]['approvalid'], $_COMPANY->id(), $r1[0], $topicObject);
        }
        return $obj;
    }

    /**
     * @param object $topicObject
     * @return Approval|null
     * @deprecated This function is marked deprecated as it should not be used by anyone other than TopicApprovals Interface
     */
    public static function GetOrCreateNewApprovalByTopicObject(object $topicObject)
    {
        global $_USER, $_COMPANY, $_ZONE;
        $approval = null;

        if (!($approval = Approval::GetApprovalByTopicObject($topicObject))) { //create a new one.
            $topicType = $topicObject::GetTopicType();
            $topicId = $topicObject->id();
            self::DBInsertPS(" INSERT INTO `topic_approvals` (`companyid`, `zoneid`, `topicid`, `topictype`, `createdby`, `createdon`, `modifiedon`) VALUES (?,?,?,?,?,NOW(),NOW())", 'iiixi', $_COMPANY->id(), $_ZONE->id(), $topicId, $topicType, $_USER->id());
            $approval = Approval::GetApprovalByTopicObject($topicObject);
        }

        return $approval;
    }

    /**
     * Deletes approval and all approval logs permanently.
     * @param int $approvalId
     * @return bool|void
     */
    public static function DeleteApproval(int $approvalId)
    {
        global $_COMPANY, $_ZONE;
        if (self::DBMutate("DELETE FROM `topic_approvals` WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND approvalid={$approvalId}")) {
            self::DBMutate("DELETE FROM topic_approvals__logs WHERE companyid={$_COMPANY->id()} AND approvalid={$approvalId}");
            return true;
        }
    }

    public function getApprovalConfiguration(): ?TopicApprovalConfiguration
    {
        if ($this->approvalConfiguration === null) {
            // Initialize it
            $this->approvalConfiguration = TopicApprovalConfiguration::GetApprovalConfigurationByTopicAndStage($this->val('topictype'), $this->val('approval_stage'));
        }
        return $this->approvalConfiguration;
    }

    public function getNextStageApprovalConfiguration(): ?TopicApprovalConfiguration
    {
        if ($this->nextStageApprovalConfiguration === null) {
            // Initialize it
            if ($this->val('approval_stage') < self::APPROVAL_STAGE_MAX) {
            $this->nextStageApprovalConfiguration = TopicApprovalConfiguration::GetApprovalConfigurationByTopicAndStage($this->val('topictype'), $this->val('approval_stage') + 1);
            }
        }
        return $this->nextStageApprovalConfiguration;
    }

    public function isNextStageApprovalConfigured(): bool
    {
        return !empty($this->getNextStageApprovalConfiguration() ?-> getApprovers());
    }

    public function getApprovalLogs()
    {
        if (isset($this->approval_logs)) {
            return $this->approval_logs;
        }

        global $_COMPANY;
        $approval_logs = self::DBGet("SELECT * FROM topic_approvals__logs WHERE companyid={$_COMPANY->id()} AND approvalid={$this->id()}");
        usort($approval_logs, function($a,$b) {return ($a['approval_logid'] - $b['approval_logid']);});

        return $this->approval_logs = array_map(function (array $approval_log) {
            return TopicApprovalLog::Hydrate($approval_log['approval_logid'], $approval_log);
        }, $approval_logs);
    }

    public function getLastApproverByStage(int $stage)
    {
        global $_COMPANY;
        if ($stage) {
            $row = self::DBGet("SELECT createdby FROM topic_approvals__logs WHERE companyid={$_COMPANY->id()} AND approvalid={$this->id()} AND approval_stage={$stage} ORDER by approval_logid DESC LIMIT 1");
            if (!empty($row)) {
                return User::GetUser($row[0]['createdby']);
            }
        }
        return null;
    }

    public function addApprovalLog(string $title, string $note, bool $auto_approved = false, ?string $approval_log_type = null): TopicApprovalLog
    {
        global $_COMPANY;

        $approval_logid = TopicApprovalLog::CreateNewTopicApprovalLog(
            $this->id(),
            $title,
            $note,
            $this->val('approval_stage'),
            $approval_log_type
        );

        $approval_log = TopicApprovalLog::GetTopicApprovalLog($approval_logid);

        if (!$auto_approved && !empty($_POST['ephemeral_topic_id'])) {
            $ephemeral_topic_id = $_COMPANY->decodeId($_POST['ephemeral_topic_id']);
            $ephemeral_topic = EphemeralTopic::GetEphemeralTopic($ephemeral_topic_id);
            $approval_log->moveAttachmentsFrom($ephemeral_topic);
        }

        return $approval_log;
    }

    private function updateStatus(string $approvalStatus, int $assignedToUserid, int $approvalStage)
    {
        global $_COMPANY, $_USER;

        // Approval Stage can only be between APPROVAL_STAGE_MIN and APPROVAL_STAGE_MAX
        if ($approvalStage < Approval::APPROVAL_STAGE_MIN || $approvalStage > Approval::APPROVAL_STAGE_MAX) {
            return false;
        }

        $update = self::DBUpdatePS("UPDATE topic_approvals SET assigned_to=?, approval_status=?, approval_stage=?, modifiedon=now() WHERE companyid=? AND approvalid=? AND modifiedon=?", "ixiiix", $assignedToUserid, $approvalStatus, $approvalStage, $_COMPANY->id(), $this->id(), $this->val('modifiedon'));
        if ($update) {
            $this->reload(); // Reload this object from latest data in database
            return true;
        }
        return false;
    }

    private function sendTopicEmail(string $sendEmailTo, string $sendEmailCC, array $emailData)
    {
        global $_COMPANY, $_ZONE, $_USER;
        // Send email
        $groupid = $this->topicObject->val('groupid') ?? 0;
        $chapterid = $this->topicObject->val('chapterid') ?? 0;
        $channelid = $this->topicObject->val('channelid') ?? 0;
        if ($groupid) {
            $group = Group::GetGroup($groupid);
            $from = $group->getFromEmailLabel($chapterid, $channelid);
        } else {
            $from = $_ZONE->val('email_from_label');
        }
        return $_COMPANY->emailSend2($from, $sendEmailTo, html_entity_decode($emailData['subject']), $emailData['message'], $_ZONE->val('app_type'), $_USER->val('email'), '', array(), $sendEmailCC);
    }

    public function request(string $requestNote, array $selectedApprovers)
    {
        global $_COMPANY, $_ZONE, $_USER;

        if ($this->isApprovalStatusRequested()) {
            return false; // Approval was already requested.
        }

        $this->recalculateAndUpdateApproverUserids($selectedApprovers);

        $requesterName = $_USER->getFullName();
        $action = "Requested by {$requesterName}";

        // Add a approval log.
        $approval_log = $this->addApprovalLog($action, $requestNote, false, 'requested');
        if (!$approval_log) {
            return false;
        }

        // Record the request
        $update_status = $this->updateStatus(self::TOPIC_APPROVAL_STATUS['REQUESTED'], 0, 1);
        if (!$update_status) {
            return false;
        }

        // Send email to requester telling the request has been recieved.
        $topicId = $this->topicObject->id();
        $topicTypeLabel = $this->getTopicTypeLabel();
        $topicTitle = html_entity_decode($this->topicObject->getTopicTitle());
        $emailData1 = EmailHelper::TopicApproval_requestNotifyRequester($topicId, $topicTypeLabel, $topicTitle, $requesterName, 'Approval Team');
        $email1 = $this->sendTopicEmail($_USER->val('email'), '', $emailData1);

        $steps = 1;
        while ($this->autoApproveIfPossible()) {
            // steps are added just to avoid infinite loops in case there is bug in this function.
            // It guarantee a maximum of APPROVAL_STAGE_MAX iterations.
            if ($steps++ > self::APPROVAL_STAGE_MAX) break;
        }

        $this->notifyNextStageApprovers($requestNote);

        return true;
    }

    /**
     * Gets a comma seperated list of email addresses of users who are approvers for the Approval stage.
     * We get the list by finding the intersection of configured approvers for the stage and users added to the approval.
     * In case no users are added to the approval then all the approvers configured for the stage will be returned.
     */
    public function getApproverEmails()
    {
        $stageApprovers = ($this->getApprovalConfiguration() ?-> getApprovers()) ?? array();
        $stageApproverUserIdArr = array_column($stageApprovers, 'approver_userid');

        $assignedApproverUserIdArr = Str::ConvertCSVToArray($this->val('approver_userids'));

        $approverUserIdArr = array_intersect($assignedApproverUserIdArr, $stageApproverUserIdArr);

        if (empty($approverUserIdArr)) { // If intersection resulted in empty array, assign all users in the stage to the list.
            $approverUserIdArr = $stageApproverUserIdArr;
        }

        $approverEmailArr = array();

        foreach ($approverUserIdArr as $approverUserId) {
            $approver = User::GetUser($approverUserId);
            if ($approver) {
                $approverEmailArr[] = $approver->val('email');
            }
        }

        return implode(',', $approverEmailArr);
    }

    public function assignTo(int $assignedToUserid, string $note)
    {
        global $_USER, $_COMPANY, $_ZONE;
        $assignedByName = $_USER->getFullName();

        $stageConfiguration = $this->getApprovalConfiguration();
        if (!$stageConfiguration) {
            return 0;
        }

        $stageApprovers = $stageConfiguration->getApprovers();
        if (!$stageApprovers ||
            !in_array($assignedToUserid, array_column($stageApprovers,'approver_userid')) ||
            !($assignedTo = User::GetUser($assignedToUserid))
        ) {
            return 0; // user is not allowed as approver, thus cannot assign.
        }

        $this->recalculateAndUpdateApproverUserids([$assignedToUserid]);


        $assignedToName = $assignedTo->getFullName();
        // Email of the Assigned person
        $action = "Assigned by {$assignedByName} to {$assignedToName}";
        // Add record
        if (
            $this->addApprovalLog($action, $note, false, 'assignment') && $this->updateStatus(self::TOPIC_APPROVAL_STATUS['PROCESSING'], $assignedTo->id(), $this->val('approval_stage'))
        ) {
            $topicId = $this->topicObject->id();
            $topicTypeLabel = $this->getTopicTypeLabel();
            $topicTitle = $this->topicObject->getTopicTitle();
            $topicAdminApprovalPage = 'topic_approvals?topicType=' . $this->val('topictype');
            $actionUrls['admin'] = Url::GetZoneAwareUrlBase($_ZONE->id(), 'admin') . $topicAdminApprovalPage;
            $actionUrls['my_approvals'] = Url::GetZoneAwareUrlBase($_ZONE->id()) . 'my_approvals';
            $emailData = EmailHelper::TopicApproval_assignedNotifyNewApprover($topicId, $topicTypeLabel, $topicTitle, $assignedByName, $assignedToName, $actionUrls);
            $this->recalculateAndUpdateApproverUserids([$assignedToUserid]);
            return $this->sendTopicEmail($assignedTo->val('email'), $_USER->val('email'), $emailData);
        }

        return true;
    }

    public function approve(string $note)
    {
        global $_USER, $_COMPANY, $_ZONE;


        // Validations
        $approvalConfiguration = $this->getApprovalConfiguration();
        //Step a: Get all approvers for current stage and validate if the logged in user is one of the approvers
        $approvers_current = $approvalConfiguration->getApprovers();
        if (!in_array($_USER->id(), array_column($approvers_current, 'approver_userid'))) {
            return false; // Logged in user is not allowed to approve the stage
        }

        // Step b: safety check if all required tasks are approved
        $config_name = Teleskope::TOPIC_TYPE_CONFIGURATION_NAME_MAP[$this->val('topictype')] ?? 'unknown';
        if ($_COMPANY->getAppCustomization()[$config_name]['approvals']['tasks']) {
            if (!TopicApprovalTask::AreAllRequiredTasksApprovedForConfiguration($this->id(), $approvalConfiguration->id())) {
                return false; // Required tasks are not approved
            }
        }

        $this->_approve($note, false);

        $steps = 1;
        while ($this->autoApproveIfPossible()) {
            // steps are added just to avoid infinite loops in case there is bug in this function.
            // It guarantee a maximum of APPROVAL_STAGE_MAX iterations.
            if ($steps++ > self::APPROVAL_STAGE_MAX) break;
        }

        $this->notifyNextStageApprovers();

        return true;
    }

    private function _approve(string $note, bool $auto_approved)
    {
        global $_USER, $_COMPANY, $_ZONE;

        if ($this->isApprovalStatusApproved()) {
            return false; // Approval was already requested.
        }

        // Sanity check.
        if (!($approvalConfiguration = $this->getApprovalConfiguration())) {
            return false;
        }

        $approvalStage = $this->val('approval_stage');

        // Next approve the stage, if the stage is final then notify the requestor
        // Initialize values
        $approvedByName = $_USER->getFullName();
        $approval_log_type = 'approved';
        if ($auto_approved) {
            $approvedByName = 'Auto Approver';
            $note = 'Your event has been auto-approved through this stage';
            $approval_log_type = 'auto_approved';
        }

        $action = "Approved by {$approvedByName}";
        $approvalStatus = self::TOPIC_APPROVAL_STATUS['APPROVED'];
        $newStage = $approvalStage;

        if ($this->isNextStageApprovalConfigured()) {
            $newStage = $approvalStage + 1;
            $approvalStatus = self::TOPIC_APPROVAL_STATUS['REQUESTED']; //self::TOPIC_APPROVAL_STATUS['PROCESSING'];
        }

        // Since ater updateStatus is executed, the approval configuration will change, lets load all the varaibles that
        // we need to the current stage.
        [$approver_cc_emails, $approver_min_approvers_limit, $approver_max_approvers_limit, $stage_approval_email_subject, $stage_approval_email_body, $stage_denial_email_subject, $stage_denial_email_body] = $this->topicObject::GetApprovalConfigurationEmailSettings($approvalConfiguration->id());

        // Main action: Add approval log and approve approval
        if (!($approval_log = $this->addApprovalLog($action, $note, $auto_approved, $approval_log_type))) {
            return false;
        }

        # **** State will change after this ****
        if (!$this->updateStatus($approvalStatus, 0, $newStage)) {
            return false;
        }

        // If the user who is approving is not part of approver user ids, then add the user.
        if ($_USER) $this->recalculateAndUpdateApproverUserids([$_USER->id()]);

        // Send approval email to requester about the stage approval or full approval
        $topicId = $this->topicObject->id();
        $topicTypeLabel = $this->getTopicTypeLabel();
        $topicTitle = $this->topicObject->getTopicTitle();

        // Send note to end user only if it was person approval (not auto approval) or if it was a final stage approval
        if (!$auto_approved || $approvalStage == self::APPROVAL_STAGE_MAX) {
            $requester = User::GetUser($this->val('createdby')); // The person who created approval request
            if ($requester) {
                $emailData1 = EmailHelper::TopicApproval_approved($topicId, $approvalStage, self::TOPIC_APPROVAL_STATUS['APPROVED'], $topicTitle, $requester, $_USER, $note, $approval_log, $stage_approval_email_subject, $stage_approval_email_body);
                $email1 = $this->sendTopicEmail($requester->val('email'), '', $emailData1);
            }
        }

        // Complete any other housekeeping stuff on final approval
        if ($approvalStatus == self::TOPIC_APPROVAL_STATUS['APPROVED']) {
             if ($this->topicObject::GetTopicType() == self::TOPIC_TYPES['EVENT']) {
                 $this->topicObject->approveAllPendingSpeakerApprovals('Approved as part of event approval process');
             }
        }

        return true;
    }

    public function deny(string $note)
    {
        global $_USER, $_COMPANY, $_ZONE;
        $deniedByName = $_USER->getFullName();

        //Step 1: Get all approvers for current stage and validate if the logged in user is one of the approvers
        $approvers_current = $this->topicObject::GetAllApproversByStage($this->val('approval_stage'), $this->topicObject->val('groupid'), $this->topicObject->val('chapterid'), $this->topicObject->val('channelid'));
        if (!in_array($_USER->id(), $approvers_current['approver_userids'])) {
            return 0; // Logged in user is not allowed to approve the stage
        }

        $action = "Denied by {$deniedByName}";
        $approvalStatus = self::TOPIC_APPROVAL_STATUS['DENIED']; //
        $approvalStage = $this->val('approval_stage'); // Maintain existing stage

        if (
            ($approval_log = $this->addApprovalLog($action, $note, false, 'denied')) &&
            $this->updateStatus($approvalStatus, 0, self::APPROVAL_STAGE_MIN)
        ) {

            // If the user who is canceling is not part of approver user ids, then add the user.
            if ($_USER) $this->recalculateAndUpdateApproverUserids([$_USER->id()]);

            $topicId = $this->topicObject->id();
            $topicTypeLabel = $this->getTopicTypeLabel();
            $topicTitle = $this->topicObject->getTopicTitle();
            $requester = User::GetUser($this->val('createdby')); // The person who created approval request
            $requesterEmail = $requester ? $requester->val('email') : ''; // The requestor might have been terminated, hence this check.

            [$approver_cc_emails, $approver_min_approvers_limit, $approver_max_approvers_limit, $stage_approval_email_subject, $stage_approval_email_body, $stage_denial_email_subject, $stage_denial_email_body] = $this->topicObject::GetApprovalConfigurationEmailSettings($approvers_current['approval_config_id']);

            $emailData1 = EmailHelper::TopicApproval_denied($topicId, $approvalStage, $approvalStatus, $topicTitle, $requester, $_USER, $note, $approval_log, $stage_denial_email_subject, $stage_denial_email_body);
            return $this->sendTopicEmail($requesterEmail, '', $emailData1);
        }
        return false;
    }

    // We can combine this and deny methods if the past log and emails is necessary. Initially setting this as separate one to not mess with existing code
    public function cancel(string $cancellation_note)
    {
        global $_USER, $_COMPANY, $_ZONE;
        $cancelledByName = $_USER->getFullName();

        $action = "Cancelled by {$cancelledByName}";
        $approvalStatus = self::TOPIC_APPROVAL_STATUS['CANCELLED'];

        if (
            ($approval_log = $this->addApprovalLog($action, $cancellation_note, false, 'cancelled')) &&
            $this->updateStatus($approvalStatus, 0, self::APPROVAL_STAGE_MIN)
        ) {
            // we can send mail if required
            return true;
        }
        return false;
    }

    public function addGeneralNote($note)
    {
        global $_USER, $_COMPANY;
        $noteTitle = 'Note added by ' . $_USER->getFullName();

        if (
            ($approval_log = $this->addApprovalLog($noteTitle, $note, false, 'general'))
        ) {
            $topicId = $this->topicObject->id();
            $topicTypeLabel = $this->getTopicTypeLabel();
            $topicTitle = $this->topicObject->getTopicTitle();
            $requesterUser = User::GetUser($this->val('createdby'));
            $requesterEmail = $requesterUser ? $requesterUser->val('email') : '';
            $requesterName = $requesterUser?->getFullName() ?: 'User not found';
            $emailData1 = EmailHelper::TopicApproval_newNoteNotification($topicId, $topicTypeLabel, $topicTitle, $_USER->getFullName(), $note, $approval_log);
            $approvers = $this->topicObject::GetAllApproversByStage($this->val('approval_stage'), $this->topicObject->val('groupid'), $this->topicObject->val('chapterid'), $this->topicObject->val('channelid'));
            [$approver_cc_emails, $approver_min_approvers_limit, $approver_max_approvers_limit, $stage_approval_email_subject, $stage_approval_email_body, $stage_denial_email_subject, $stage_denial_email_body] = $this->topicObject::GetApprovalConfigurationEmailSettings($approvers['approval_config_id']);

            // Update approval requestor about the not
            if (!empty($requesterEmail)) {
                $email1 = $this->sendTopicEmail($requesterEmail, '', $emailData1);
            }

            # Commented out as part of 4062
            // Update approvers about the note
            //$approverEmails = $this->getApproverEmails();
            //if (!empty($approverEmails)) {
            //    $email2 = $this->sendTopicEmail($approverEmails, $approver_cc_emails, $emailData1);
            //}

            return true;
        }
        return false;
    }

    public function reset(string $note)
    {
        global $_USER;
        $action = "Approval reset by {$_USER->getFullName()}";
        return (
            $this->addApprovalLog($action, $note, false, 'reset') &&
            $this->updateStatus(self::TOPIC_APPROVAL_STATUS['RESET'], 0, self::APPROVAL_STAGE_MIN)
        );
    }

    /**
     * Checks if the topic's current state meets auto approval criteria, auto approves it, and returns true if
     * auto approval was done. If not false is returned. Run this function in a while loop to auto approval all possible stages.
     * @return bool
     */
    public function autoApproveIfPossible() : bool
    {
        $topicType = $this->topicObject::GetTopicType();
        // Step 1: Validation
        if (($this->val('approval_stage') > self::APPROVAL_STAGE_MAX) || $this->isApprovalStatusApproved()) {
            return false;
        }

        $approvalConfiguration = $this->getApprovalConfiguration();
        if (!$approvalConfiguration) {
            return false;
        }
        $autoApprovalConfiguration = $approvalConfiguration->getAutoApprovalData();
        if (empty($autoApprovalConfiguration)) {
            return false;
        }

        // Step 2: Check if the topic can be auto approved.
        $isTopicAutoApprovalCriteriaMatched = false;
        // There is special handling for events that are part of series.
        if ($topicType == Teleskope::TOPIC_TYPES['EVENT'] && $this->topicObject->isSeriesEvent()) {
            // For event series, check if each *draft | under_review * event in the event series matches the matching criteria
            $seriesEvents = Event::GetEventsInSeries($this->topicObject->id());
            foreach ($seriesEvents as $seriesEvent) {
                // Do not check for events that have already been
                if (in_array($seriesEvent->val('isactive'), [self::STATUS_INACTIVE, self::STATUS_ACTIVE, self::STATUS_AWAITING])) {
                    continue;
                }
                $criteriaMatched = $seriesEvent->isAutoApprovalCriteriaMet($autoApprovalConfiguration);
                if ($criteriaMatched) {
                    $isTopicAutoApprovalCriteriaMatched = true;
                } else {
                    $isTopicAutoApprovalCriteriaMatched = false;
                    break;
                }
            }
        } else {
            // Check if individual object auto approval criteria is met
            $isTopicAutoApprovalCriteriaMatched = $this->topicObject->isAutoApprovalCriteriaMet($autoApprovalConfiguration);
        }

        // Step 3: Approve if the topic matches auto approval criteria
        if ($isTopicAutoApprovalCriteriaMatched) {
            $this->_approve('AUTOAPPROVED', true);
            return true;
        }
        return false;
    }

    public function GetAllTasksByApproval()
    {
        global $_COMPANY;
        $row = self::DBGet("
            SELECT tat.*, tact.approval_task_name, tac.approval_stage
            FROM `topic_approvals__tasks` AS tat
                JOIN `topic_approvals__configuration_tasks` AS tact ON tat.approval_config_taskid = tact.approval_config_taskid 
                JOIN `topic_approvals__configuration` AS tac ON tact.approval_config_id = tac.approval_config_id
            WHERE tat.companyid={$_COMPANY->id()}
                AND approvalid={$this->id()}
            ORDER by tat.modifiedon DESC
            ");

        return $row;
    }

    /**
     * @return bool
     */
    public function isApprovalStatusRequested()
    {
        return $this->val('approval_status') == self::TOPIC_APPROVAL_STATUS['REQUESTED'];
    }

    /**
     * @return bool
     */
    public function isApprovalStatusProcessing()
    {
        return $this->val('approval_status') == self::TOPIC_APPROVAL_STATUS['PROCESSING'];
    }

    public function isApprovalAssigned()
    {
        return (int)$this->val('assigned_to') > 0;
    }

    /**
     * @return bool
     */
    public function isApprovalStatusApproved()
    {
        return $this->val('approval_status') == self::TOPIC_APPROVAL_STATUS['APPROVED'];
    }

    /**
     * @return bool
     */
    public function isApprovalStatusDenied()
    {
        return $this->val('approval_status') == self::TOPIC_APPROVAL_STATUS['DENIED'];
    }

    /**
     * @return bool
     */
    public function isApprovalStatusReset()
    {
        return $this->val('approval_status') == self::TOPIC_APPROVAL_STATUS['RESET'];
    }

    /**
     * @return bool
     */
    public function isApprovalStatusCancelled()
    {
        return $this->val('approval_status') == self::TOPIC_APPROVAL_STATUS['CANCELLED'];
    }

    /**
     * Check if the current object's approval stage matches the given stage.
     *
     * @param int $stage The stage to compare with.
     * @return bool Whether the approval stage matches the given stage.
     */
    public function isApprovalStage(int $stage): bool
    {
        return $this->val('approval_stage') == $stage;
    }


    /**
     * @param array $newApproverUserIds array of userids who should be added as approvers
     * @return bool|int
     */
    public function recalculateAndUpdateApproverUserids(array $newApproverUserIds)
    {
        global $_COMPANY;
        $newApproverUserIds = Arr::IntValues($newApproverUserIds);
        if (!empty($newApproverUserIds)) {
            $approverUserIds = explode(',', $this->val('approver_userids') ?? '');
            $approverUserIds = array_filter(array_unique(array_merge($approverUserIds, $newApproverUserIds)));
            $approverUserIdsCsv = implode(',', $approverUserIds);

            $retVal = self::DBUpdatePS("UPDATE `topic_approvals` SET `approver_userids`=?, `modifiedon`=now() WHERE `companyid`=? AND `approvalid`=?", "xii", $approverUserIdsCsv, $_COMPANY->id(), $this->id());
            if ($retVal) {
                $this->reload();
            }
            return $retVal;
        }
        return true;
    }

    public function reload()
    {
        global $_COMPANY;
        $rows = self::DBGet("SELECT * FROM `topic_approvals` WHERE `companyid`={$_COMPANY->id()} AND `approvalid`={$this->id()}");
        if (empty($rows)) {
            Logger::Log("Unexpected state ... unable to load approval = {$this->id()}");
            exit();
        }
        $this->fields = $rows[0];
        $this->approvalConfiguration = null;
        $this->nextStageApprovalConfiguration = null;
        $this->approval_logs = null;
    }

    public function getMostRecentStageApproval(int $stage): ?TopicApprovalLog
    {
        $approval_notes = $this->getApprovalLogs();

        $most_recent_stage_approval = null;
        $most_recent_stage_approval_timestamp = 0;
        foreach ($approval_notes as $note) {
            if (!in_array($note->val('approval_log_type'), ['approved', 'denied', 'auto_approved', 'reset'])) {
                continue;
            }

            if ((int) $note->val('approval_stage') !== $stage) {
                continue;
            }

            $stage_approval_timestamp =
                (DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $note->val('createdon'), new DateTimeZone('UTC')))
                    ->getTimestamp();

            if ($stage_approval_timestamp > $most_recent_stage_approval_timestamp) {
                $most_recent_stage_approval_timestamp = $stage_approval_timestamp;
                $most_recent_stage_approval = $note;
            }
        }
        return $most_recent_stage_approval;
    }

    public function getTopicTypeLabel(): string
    {
        return ($this->val('topictype') == Teleskope::TOPIC_TYPES['POST']) ? Post::GetCustomName(false) : Teleskope::TOPIC_TYPES_ENGLISH[$this->val('topictype')];
    }

    private function notifyNextStageApprovers(string $requestNote = ''): void
    {
        global $_ZONE;

        $topicId = $this->topicObject->id();
        $topicTypeLabel = $this->getTopicTypeLabel();
        $topicTitle = html_entity_decode($this->topicObject->getTopicTitle());
        $requesterName = User::GetUser($this->val('createdby')) ?-> getFullName() ?? '';

        // If this is not approved, send an email to the approvers of this stage.
        if (!$this->isApprovalStatusApproved()) {
            // Note as the approval was going through auto approvals its approval stage advances.
            $approvalConfiguration = $this->getApprovalConfiguration();
            $actionUrls['admin'] = Url::GetZoneAwareUrlBase($_ZONE->id(), 'admin') . 'topic_approvals?topicType=' . $this->val('topictype');
            $actionUrls['my_approvals'] = Url::GetZoneAwareUrlBase($_ZONE->id()) . 'my_approvals';

            // We will send different emails depending upon if this is the first request or subsequent request
            if ($this->val('approval_stage') == self::APPROVAL_STAGE_MIN) {
                $emailData2 = EmailHelper::TopicApproval_requestNotifyApprovers($topicId, $topicTypeLabel, $topicTitle, $requesterName, $actionUrls, $requestNote, $this);
            } else {
                $approvalStage = (int)$this->val('approval_stage');
                $emailData2 = EmailHelper::TopicApproval_notifyNextStageApprovers($topicId, $topicTypeLabel, $topicTitle, $requesterName, $actionUrls,'approved', $approvalStage - 1, $approvalStage);
            }
            [$approver_cc_emails, $approver_min_approvers_limit, $approver_max_approvers_limit, $stage_approval_email_subject, $stage_approval_email_body, $stage_denial_email_subject, $stage_denial_email_body] = $this->topicObject::GetApprovalConfigurationEmailSettings($approvalConfiguration->id());
            $approverEmails = $this->getApproverEmails();
            $email2 = $this->sendTopicEmail($approverEmails, $approver_cc_emails, $emailData2);
        }
    }
    public function canUpdateAfterApproval() {
        global $_COMPANY;
        $topicType = $this->topicObject::GetTopicType();
        $allowTitleUpdate = true;
        $allowDescriptionUpdate = true;
        $configName = Teleskope::TOPIC_TYPE_CONFIGURATION_NAME_MAP[$topicType] ?? 'default';
        if ($this->isApprovalStatusRequested() || $this->isApprovalStatusProcessing() || $this->isApprovalStatusApproved()){
            $allowTitleUpdate = $_COMPANY->getAppCustomization()[$configName]['approvals']['allow_update_title_after_approval_start'] ?? true;
            $allowDescriptionUpdate = $_COMPANY->getAppCustomization()[$configName]['approvals']['allow_update_description_after_approval_start'] ?? true;
        }

        return array($allowTitleUpdate,$allowDescriptionUpdate);

    }
}