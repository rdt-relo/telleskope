<?php

// Do no use require_once as this class is included in Company.php.

class Message extends Teleskope
{
    use TopicAttachmentTrait;

    protected function __construct($id, $cid, $fields)
    {
        parent::__construct($id, $cid, $fields);
    }


    /**
     * @param int $messageid
     * @param string $groupids
     * @param string $regionids
     * @param string $from_name
     * @param string $sent_to
     * @param int $total_recipients
     * @param string $recipients
     * @param string $subject
     * @param string $message
     * @param string $chapterids
     * @param string $channelids
     * @param int $is_admin
     * @param int $recipients_base
     * @param string $additional_recipients
     * @return int|string
     */
    public static function CreateOrUpdateMessage(int $messageid, string $groupids, string $regionids, string $from_name, string $sent_to, int $total_recipients, string $recipients, string $subject, string $message, string $chapterids, string $channelids, int $is_admin, int $recipients_base,string $additional_recipients,string $team_member_roleIds,string $content_replyto_email='',string $listids = '0')
    {

        global $_COMPANY;
        global $_USER;
        global $_ZONE;

        ContentModerator::CheckBlockedWords($subject, $message);

        if (!empty($listids)){
            $listids = Sanitizer::SanitizeIntegerCSV($listids);
        }

        if ($messageid){

            $retVal = self::DBUpdatePS("UPDATE `messages` SET `groupids`=?, `regionids`=?, `from_name`=?, `sent_to`=?, `total_recipients`=?, `recipients`=?, `subject`=?, `message`=?, `chapterids`=?,`channelids`=?, `userid`=?,`recipients_base`=?,`additional_recipients`=?,modifiedon=now(),`team_roleids`=?,`content_replyto_email`=?,`listids`=? WHERE companyid=? AND (zoneid=? AND messageid=?)", 'ssssisxmssiisxsxiii', $groupids, $regionids, $from_name, $sent_to, $total_recipients, $recipients, $subject, $message, $chapterids, $channelids, $_USER->id(), $recipients_base, $additional_recipients,$team_member_roleIds,$content_replyto_email,$listids, $_COMPANY->id(), $_ZONE->id(), $messageid);
            if ($retVal) {
                self::LogObjectLifecycleAudit('update', 'message', $messageid, 0);
            }

        } else {
            $messageid =  self::DBInsertPS("INSERT INTO `messages`(`companyid`, `userid`, `zoneid`,`groupids`, `regionids`, `from_name`, `sent_to`, `total_recipients`, `recipients`, `subject`, `message`,`chapterids`,`channelids`,`is_admin`,`isactive`,`recipients_base`,`additional_recipients`,`team_roleids`,`content_replyto_email`,`listids`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", 'iiissssisxmssiiisxsx', $_COMPANY->id(), $_USER->id(), $_ZONE->id(), $groupids, $regionids, $from_name, $sent_to, $total_recipients, $recipients, $subject, $message, $chapterids, $channelids, $is_admin,2,$recipients_base,$additional_recipients,$team_member_roleIds,$content_replyto_email,$listids);
            if ($messageid) {
                self::LogObjectLifecycleAudit('create', 'message', $messageid, 0);
            }
        }

        return $messageid;
    }

    /**
     * Deletes the message if it is currently in draft mode, or marks it in 100 status if message was already sent.
     * @return int
     */
    public function deleteMessage ():int
    {
        global $_COMPANY;
        global $_ZONE;
        $retVal = 0;

        if ($this->isActive()) {
            $retVal = self::DBUpdate("UPDATE messages SET isactive=100 WHERE companyid={$_COMPANY->id()} AND (zoneid={$_ZONE->id()} AND messageid={$this->id()} AND isactive=1)");
            if ($retVal) {
                self::LogObjectLifecycleAudit('state_change', 'message', $this->id(), 0, ['state' => 'inactive']);

                $this->deleteAllAttachments();
            }

        } elseif ($this->isDraft() || $this->isUnderReview()) {
            $retVal = $this->deleteIt();
        }
        return $retVal;

    }


    /** Permanently delete the message */
    public function deleteIt(): int
    {
        global $_COMPANY;
        $result = self::DBUpdate("DELETE FROM messages WHERE companyid={$_COMPANY->id()} AND messageid={$this->id()}");
        if ($result) {
            self::LogObjectLifecycleAudit('delete', 'message', $this->id(), 0);
        }

        $this->deleteAllAttachments();

        return $result;
    }

    /**
     * @param int $messageid
     * @return Message|null
     */
    public static function GetMessage(int $messageid, bool $allow_cross_zone_fetch = false): ?Message
    {
        global $_COMPANY;
        global $_ZONE;

        $rows = self::DBGet("SELECT *, IFNULL(chapterids,'') as chapterids, IFNULL(channelids,'') as channelids FROM messages WHERE companyid={$_COMPANY->id()} AND messageid={$messageid}");
        if (!empty($rows)) {
            if (!$allow_cross_zone_fetch && ((int) $rows[0]['zoneid'] !== $_ZONE->id())) {
                return null;
            }

            return new Message($messageid,$_COMPANY->id(), $rows[0]);
        }
        return null;
    }

    public static function ConvertDBRecToMessage (array $rec) : ?Message
    {
        global $_COMPANY;
        $obj = null;
        $m = (int)$rec['messageid'];
        $c = (int)$rec['companyid'];
        if ($m && $c && $c === $_COMPANY->id())
            $obj = new Message($m, $c, $rec);
        return $obj;
    }
    /**
     * Updates message status to active and schedules a email job
     * @return int
     */
    public function sendMessage(int $delay) : int
    {
        global $_USER;
        $awaiting = self::STATUS_AWAITING;
        if (self::DBMutate("UPDATE messages SET isactive={$awaiting},`publishdate`=now() + interval {$delay} second, `publishedby`='{$_USER->id()}' WHERE messageid={$this->id}")) {
            $counter = 0;
            $batch = 200;
            $total_recipients = (int)$this->val('total_recipients'); #BASF reported issue where > 200 users in additional_receipients were not getting batched
            while ($counter < $total_recipients) {
                // Create multiple message jobs of size $batch starting with 0
                $job = new MessageJob($this->id,$counter,$batch);
                if ($delay){
                    $job->delay = $delay;
                }
                $job->saveAsCreateType();
                $counter = $counter+$batch;
            }

            self::LogObjectLifecycleAudit('state_change', 'message', $this->id(), 0, ['state' => 'awaiting publish', 'publishedby' => $_USER->id()]);

            return 1;
        }
        return 0;
    }

    /**
     * Returns a CSV string of emails
     */
    public function getRecipients() : string
    {
        global $_COMPANY;
        global $_ZONE;
        return self::DBGet("SELECT recipients FROM messages WHERE companyid={$_COMPANY->id()} AND (zoneid={$_ZONE->id()} AND messageid={$this->id})")[0]['recipients'];
    }

    public function isAdminMessage() : bool
    {
        return $this->val('is_admin') === "1";
    }

    public function updateMessageForReview ():int
    {
        global $_COMPANY,$_ZONE;

        $status_under_review = self::STATUS_UNDER_REVIEW;
        $retVal = self::DBUpdate("UPDATE `messages` SET `isactive`='{$status_under_review}',`modifiedon`=NOW() WHERE `companyid`={$_COMPANY->id()} AND (`zoneid`={$_ZONE->id()} AND `messageid`={$this->id()} )");

        if ($retVal) {			
            self::LogObjectLifecycleAudit('state_change', 'message', $this->id(), 0, ['state' => 'review']);
        }
        return $retVal;
    }

    public function cancelMessagePublishing():int
    {
        global $_COMPANY,$_ZONE;
        $status_draft = self::STATUS_DRAFT;
        $status_awaiting = self::STATUS_AWAITING;
        $retVal = self::DBUpdate("UPDATE `messages` SET `isactive`='{$status_draft}',`modifiedon`=NOW() WHERE `companyid`={$_COMPANY->id()} AND (`zoneid`={$_ZONE->id()} AND `messageid`={$this->id()} AND `isactive`='{$status_awaiting}' )");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'message', $this->id(), 0, ['state' => 'draft']);
        }
        return $retVal;
    }

    public static function GetTopicType(): string
    {
        return self::TOPIC_TYPES['MESSAGE'];
    }
}
