<?php

class TopicApprovalLog extends Teleskope
{
    use TopicAttachmentTrait;

    public static function CreateNewTopicApprovalLog(
        int $approvalid,
        string $log_title,
        string $log_notes,
        int $approval_stage,
        string $approval_log_type
    ): int {
        global $_COMPANY, $_USER;

        return self::DBInsertPS(
            "INSERT into topic_approvals__logs (companyid, approvalid, log_title, log_notes, approval_stage, createdby, createdon, approval_log_type) VALUES (?,?,?,?,?,?,now(),?)",
            'iixxiis',
            $_COMPANY->id(),
            $approvalid,
            $log_title,
            $log_notes,
            $approval_stage,
            $_USER->id(),
            $approval_log_type
        );
    }

    public static function GetTopicApprovalLog(int $id): ?TopicApprovalLog
    {
        global $_COMPANY;

        $results = self::DBGet("
            SELECT * FROM `topic_approvals__logs`
            WHERE `approval_logid` = {$id}
            AND `companyid` = {$_COMPANY->id()}
        ");

        return new TopicApprovalLog($id, $_COMPANY->id(), $results[0]);
    }

    public static function GetTopicType(): string
    {
        return self::TOPIC_TYPES['APPROVAL_LOG'];
    }
}
