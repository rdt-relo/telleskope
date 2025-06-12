<?php

class EphemeralTopic extends Teleskope
{
    use TopicAttachmentTrait;

    public static function CreateNewEphemeralTopic(string $topictype): EphemeralTopic
    {
        global $_COMPANY, $_USER;

        $id = self::DBInsert("
            INSERT INTO `ephemeral_topics` (`companyid`, `topictype`, `userid`)
            VALUES ({$_COMPANY->id()}, '{$topictype}', {$_USER->id()})
        ");

        return self::GetEphemeralTopic($id);
    }

    public static function GetEphemeralTopic(int $id): ?EphemeralTopic
    {
        global $_COMPANY;

        $results = self::DBGet("
            SELECT * FROM `ephemeral_topics`
            WHERE `ephemeral_topic_id` = {$id}
            AND `companyid` = {$_COMPANY->id()}
        ");

        if (empty($results[0])) {
            self::LogObjectLifecycleAudit('failed_fetch', 'ephemeral_topic', $id, 1);
            return null;
        }

        $results[0]['isactive'] = 1; // Since ephemeral topics do not have isactive, just add it for use by other functions;

        return new EphemeralTopic($id, $_COMPANY->id(), $results[0]);
    }

    public static function GetTopicType(): string
    {
        return self::TOPIC_TYPES['EPHEMERAL_TOPIC'];
    }

    public function deleteIt(bool $delete_s3_file = true): void
    {
        global $_COMPANY;

        $this->deleteAllAttachments(delete_s3_file: $delete_s3_file);

        self::DBMutate("
            DELETE FROM `ephemeral_topics`
            WHERE `companyid` = {$_COMPANY->id()}
            AND `ephemeral_topic_id` = {$this->id()}
        ");

        self::LogObjectLifecycleAudit('delete', 'ephemeral_topic', $this->id(), 1);
    }

    /**
     * This method deletes all ephemeral topics regardless of the Company
     * @param int $older_than_days
     * @return void
     */
    public static function DeleteExpiredEphemeralTopics(int $older_than_days = 30)
    {
        global $_COMPANY, $_ZONE;
        if ($_COMPANY || $_ZONE) {
            Logger::Log("Calling multi-tenant method from Company or Zone context:" . $_COMPANY->id(), Logger::SEVERITY['FATAL_ERROR']);
            return false; // This job can only be executed if it is called from non company and non zone context. If company is set, exit it.
        }

        $cids = self::DBGet("SELECT companyid FROM companies");

        { // block to temporarily instantiate the global $_COMPANY;
            $_COMPANY = null;
            foreach ($cids as $cid) {
                $_COMPANY = Company::GetCompany($cid['companyid']);

                $results = self::DBGet("
                    SELECT * FROM `ephemeral_topics`
                    WHERE companyid={$cid['companyid']} AND `createdon` < NOW() - INTERVAL {$older_than_days} DAY
                ");

                foreach ($results as $result) {
                    $resulting_topic = new EphemeralTopic($result['ephemeral_topic_id'], $result['companyid'], $result);
                    $resulting_topic->deleteIt(true);
                }
            }
            $_COMPANY = null;
        }
    }
}
