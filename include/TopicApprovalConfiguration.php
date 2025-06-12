<?php

class TopicApprovalConfiguration extends Teleskope
{
    private ?array $approvers = null;
    private ?array $tasks = null;
    private bool $from_master_db = false;

    public static function GetApprovalConfiguration(int $configuration_id, bool $from_master_db=false): ?TopicApprovalConfiguration
    {
        global $_COMPANY, $_ZONE;

        $sql = "
                SELECT * 
                FROM `topic_approvals__configuration` 
                WHERE companyid = {$_COMPANY->id()} 
                  AND approval_config_id = {$configuration_id}
              ";
        $rows = $from_master_db ? self::DBGet($sql) : self::DBROGet($sql);
        if (!empty($rows)) {
            $ta = new TopicApprovalConfiguration($rows[0]['approval_config_id'], $rows[0]['companyid'], $rows[0]);
            $ta->from_master_db = $from_master_db;
            return $ta;
        }
        return null;
    }

    public static function GetApprovalConfigurationByTopicAndStage(string $topic_type, int $stage, bool $from_master_db=false): ?TopicApprovalConfiguration
    {
        global $_COMPANY, $_ZONE;

        $allConfigurationRows = self::GetAllApprovalConfigurationRowsByTopic($topic_type, $from_master_db);
        $stageConfigurationRow = Arr::SearchColumnReturnRow($allConfigurationRows, $stage, 'approval_stage');
        if (!empty($stageConfigurationRow)) {
            $ta = new TopicApprovalConfiguration($stageConfigurationRow['approval_config_id'], $stageConfigurationRow['companyid'], $stageConfigurationRow);
            $ta->from_master_db = $from_master_db;
            return $ta;
        }
        return null;
    }

    public static function GetAllApprovalConfigurationRowsByTopic(string $topic_type, bool $from_master_db=false): array
    {
        global $_COMPANY, $_ZONE;

        if (!in_array($topic_type,array_values(TELESKOPE::TOPIC_TYPES))){
            return array();
        }

        // For now we are just ignoring the database columns groupid, chapterid, channelid and scopeFilter
        // In future if we want to take these into consideration, just added the following scopes.
        //        $scopeFilter = '';
        //        if ($groupid >= 0) {
        //            $scopeFilter .= " AND `groupid`={$groupid} ";
        //        }
        //        if ($chapterid >= 0) {
        //            $scopeFilter .= " AND `chapterid`={$chapterid} ";
        //        }
        //        if ($channelid >= 0) {
        //            $scopeFilter .= " AND `channelid`={$channelid} ";
        //        }

        $sql = "
                SELECT * 
                FROM `topic_approvals__configuration` 
                WHERE companyid = {$_COMPANY->id()} 
                  AND zoneid = {$_ZONE->id()} 
                  AND `topic_type` = '{$topic_type}' 
              ";
        return $from_master_db ? self::DBGet($sql) : self::DBROGet($sql);
    }

    public function getApprovers() : array
    {
        global $_COMPANY, $_ZONE;

        if ($this->approvers === null) {
            $this->approvers = array();
            $sql = "
                SELECT approval_approver_id,approver_userid,approver_role,firstname,lastname,email 
                FROM topic_approvals__configuration_approvers 
                    JOIN users ON approver_userid = userid
                WHERE topic_approvals__configuration_approvers.companyid = {$_COMPANY->id()}
                    AND topic_approvals__configuration_approvers.approval_config_id = {$this->id()}
                    AND users.isactive = 1
                ";

            $this->approvers = $this->from_master_db ? self::DBGet($sql) : self::DBROGet($sql);
        }
        return $this->approvers;
    }

    public function getTasks() : array
    {
        global $_COMPANY;

        if ($this->tasks === null) {
            $this->tasks = array();
            $sql = "
                SELECT *
                FROM `topic_approvals__configuration_tasks` 
                WHERE companyid={$_COMPANY->id()}
                    AND approval_config_id={$this->id()}
                ";

            $tasks = $this->from_master_db ? self::DBGet($sql) : self::DBROGet($sql);

            usort($tasks, function($a,$b) {
                return $a['sorting_order'] - $b['sorting_order'];
            });

            $this->tasks = $tasks;
        }

        return $this->tasks;
    }

    public function getAutoApprovalData () : array
    {
        return $this->val_json2Array('auto_approval_configuration') ?? array();
    }

    public function getNextStageConfiguration() : ?TopicApprovalConfiguration
    {
        if ($this->val('approval_stage') < Approval::APPROVAL_STAGE_MAX) {
           return self::GetApprovalConfigurationByTopicAndStage($this->val('topic_type'), $this->val('approval_stage')+1);
        }
        return null;
    }

    public function deleteIt(): bool
    {
        global $_COMPANY, $_ZONE;

        if ($this->getWhyCannotDeleteIt()) {
            return false;
        }

        self::LogObjectLifecycleAudit('delete', 'topic_approval_configuration', $this->id(), 0);

        self::DBMutate("DELETE FROM `topic_approvals__configuration` WHERE `approval_config_id` = {$this->id()} AND `companyid` = {$_COMPANY->id()} AND `zoneid` = {$_ZONE->id()}");

        $_COMPANY->expireRedisCache("TOPIC_APPROVERS:{$_ZONE->id()}");

        return true;
    }

    public function getWhyCannotDeleteIt(): string
    {
        global $_COMPANY, $_ZONE;

        if ($this->getNextStageConfiguration()) {
            return 'NOT_LAST_STAGE';
        }

        if ($this->getApprovers()) {
            return 'HAS_APPROVERS';
        }

        if ($this->getAutoApprovalData()) {
            return 'HAS_AUTO_APPROVAL_CONFIG';
        }

        $result = self::DBROGet("
            SELECT  1
            FROM    `topic_approvals`
            WHERE   `topictype` = '{$this->val('topic_type')}'
            AND     `approval_stage` = {$this->val('approval_stage')}
            AND     `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
            LIMIT   1
        ");

        if (!empty($result)) {
            return 'HAS_APPROVAL_TOPICS';
        }

        return '';
    }
}
