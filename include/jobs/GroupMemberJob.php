<?php

class GroupMemberJob extends Job
{
    public $groupid;
    public $chapterid;
    public $channelid;

    public function __construct($gid, $chid, $chlid)
    {
        parent::__construct();
        $this->groupid = $gid;
        $this->chapterid = $chid;
        $this->channelid = $chlid;
        $this->jobid = "GRM_{$this->cid}_{$gid}_{$chid}_{$chlid}_" . microtime(TRUE);
        $this->jobtype = self::TYPE_GROUPMEMBER;
        $this->delay = 180; // Forcing a 3 minute delay to avoid user fast clicking to send too many messages
    }

    public function saveAsJoinType(int $member_userid, bool $anonymous_member)
    {
        // First cleanup all the past jobs of DELETE type matching current criteria
        if ($this->cancelAllPendingJobs(self::SUBTYPE_DELETE, $member_userid) === -1) {
            // Only save if there were no pending jobs of DELETE type otherwise it negates
            $this->details = $member_userid;
            $this->options['anonymous_member'] = $anonymous_member;
            parent::saveAsCreateType();
        }
    }

    public function saveAsLeaveType(int $member_userid, bool $anonymous_member)
    {
        // First cleanup all the past jobs of CREATE type matching current criteria
        if ($this->cancelAllPendingJobs(self::SUBTYPE_CREATE, $member_userid) === -1) {
            // Only save if there were no pending jobs of CREATE type otherwise it negates
            $this->details = $member_userid;
            $this->options['anonymous_member'] = $anonymous_member;
            parent::saveAsDeleteType();
        }
    }

    public function saveAsAnniversaryFollowup(int $member_userid, bool $anonymous_member, $communication_trigger_type): int
    {
        $this->details = $member_userid;
        $this->options['trigger_type'] = $communication_trigger_type;
        $this->options['trigger_name'] = array_flip(Group::GROUP_COMMUNICATION_TRIGGERS)[$communication_trigger_type] ?? '';
        $this->options['anonymous_member'] = $anonymous_member;
        return parent::saveAsFollowupType();
    }

    /**
     * @param $type 1 for Group Join (Create), 2 for Group Leave (Delete)
     */
    private function processAsAnyType(int $type)
    {
        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */

        $anonymous_member = $this->options['anonymous_member'] ?? false;

        $groupname = 'All';
        $from = '';
        $app_type = $_ZONE->val('app_type');
        $reply_addr = '';
        $group = null;
        if ($this->groupid) {
            $group = Group::GetGroup($this->groupid);
            if ($group === null) {
                return;
            } //no matching valid group found
            $groupname = $group->val('groupname');
            $from = $group->val('from_email_label');
            $reply_addr = $group->val('replyto_email');
        }

        if (($touser = User::GetUser((int)$this->details)) === null) {
            return; // Target user does not exist.
        }

        // Validate the members exists
        $gm_row = self::DBROGet("SELECT * FROM groupmembers WHERE groupid='{$this->groupid}' AND userid='{$touser->id()}' AND isactive=1");

        if ($this->chapterid &&
            (($type === 1 && (count($gm_row) < 1 || array_search($this->chapterid, explode(',', $gm_row[0]['chapterid'])) === false)) ||
                ($type === 2 && (count($gm_row) > 0 && array_search($this->chapterid, explode(',', $gm_row[0]['chapterid'])) !== false)))
        ) {
            return;
        } elseif ($this->channelid &&
            (($type === 1 && (count($gm_row) < 1 || array_search($this->channelid, explode(',', $gm_row[0]['channelids'])) === false)) ||
                ($type === 2 && (count($gm_row) > 0 && array_search($this->channelid, explode(',', $gm_row[0]['channelids'])) !== false)))
        ) {
            return;
        }

        if ($group) {
            $from = $group->getFromEmailLabel($this->chapterid, $this->channelid);
        }

        if ($this->chapterid) {
            $comm_row = self::DBROGet("SELECT communication,emailsubject,IFNULL(email_cc_list,'') as email_cc_list,`send_upcoming_events_email` FROM group_communications WHERE group_communications.companyid='{$_COMPANY->id()}' AND group_communications.zoneid='{$_ZONE->id()}' AND  groupid={$this->groupid} AND chapterid={$this->chapterid} AND channelid=0 AND communication_trigger={$type} AND isactive=1");
        } elseif ($this->channelid) {
            $comm_row = self::DBROGet("SELECT communication,emailsubject,IFNULL(email_cc_list,'') as email_cc_list,`send_upcoming_events_email` FROM group_communications WHERE group_communications.companyid='{$_COMPANY->id()}' AND group_communications.zoneid='{$_ZONE->id()}' AND  groupid={$this->groupid} AND chapterid=0 AND channelid={$this->channelid} AND communication_trigger={$type} AND isactive=1");
        } else {
            $comm_row = self::DBROGet("SELECT communication,emailsubject,IFNULL(email_cc_list,'') as email_cc_list,`send_upcoming_events_email` FROM group_communications WHERE group_communications.companyid='{$_COMPANY->id()}' AND group_communications.zoneid='{$_ZONE->id()}' AND groupid={$this->groupid} AND chapterid=0 AND channelid=0 AND communication_trigger={$type} AND isactive=1");
        }
        if (count($comm_row) && !empty($comm_row[0]['communication'])) {
            $emesg = $comm_row[0]['communication'];
            $subject = html_entity_decode($comm_row[0]['emailsubject']);

            if (empty($subject)) {
                $subject = '';
                if ($type === 1) {
                    $subject = 'Welcome';
                } elseif ($type === 2) {
                    $subject = 'See you later';
                } elseif (in_array($type, [4, 5, 6, 7, 8, 9, 14, 15, 16, 17, 18, 19])) {
                    // Anniversary email
                    $subject = 'Membership Anniversary Email';
                    $comm_row[0]['send_upcoming_events_email'] = 0; //force false
                    $anonymous_member = $gm_row[0]['anonymous'] ?? $anonymous_member;
                }
            }
            $email_cc_list = $comm_row[0]['email_cc_list'];
            $email_settings = $_ZONE->val('email_settings');
            $email = $touser->val('email');
            if ($email_settings >= 1 && $email) {
                // Send Updcoming events if configured
                if ($comm_row[0]['send_upcoming_events_email']) {
                    global $_COMPANY, $_ZONE;
                    $chapterid_filter = ($this->chapterid) ? " AND FIND_IN_SET({$this->chapterid}, chapterid)" : " AND chapterid='0' ";
                    $channelid_filter = ($this->channelid) ? " AND channelid='{$this->channelid}'" : " AND channelid='0' ";
                    $start_time_filter = " AND start > now() AND start < now() + interval {$comm_row[0]['send_upcoming_events_email']} day ";
                    $rows = self::DBROGet("SELECT * FROM events WHERE `companyid` = '{$_COMPANY->id()}' AND (`groupid`='{$this->groupid}' {$chapterid_filter} {$channelid_filter} {$start_time_filter} AND `teamid`='0' AND `isactive`='" . self::STATUS_ACTIVE . "'  AND `event_series_id` = 0 AND `isprivate` =0  AND `eventclass`='event')");
                    $invitedBy = 'You are invited to join the following event';
                    foreach ($rows as $row) {
                        $eventJob = new EventJob($row['groupid'], $row['eventid']);
                        $eventJob->saveAsInviteType($touser->id(), $invitedBy);
                    }
                }

                $chaptername = Group::GetChapterName($this->chapterid, $this->groupid)['chaptername'];
                $groupname = $group->val('groupname');
                $grouplogo = $group->val('groupicon');
                $groupcolor = rgb_to_hex($group->val('overlaycolor'));
                $groupcolor2 = rgb_to_hex($group->val('overlaycolor2'));
                $companyname = $_COMPANY->val('companyname');
                $companylogo = $_COMPANY->val('logo');
                $companyurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));
                $groupurl = $group->getShareableUrl();
                $person_firstname = $touser->val('firstname');
                $person_lastname = $touser->val('lastname');
                $person_name = $touser->getFullName();
                $person_email = $touser->val('email');
                $replace_vars = ['[%COMPANY_NAME%]','COMPANY_LOGO','[%GROUP_NAME%]', 'GROUP_LOGO', '[%CHAPTER_NAME%]', '[%COMPANY_URL%]', '[%GROUP_URL%]', '[%RECIPIENT_NAME%]', '[%RECIPIENT_FIRST_NAME%]', '[%RECIPIENT_LAST_NAME%]', '[%RECIPIENT_EMAIL%]', '#000001','#000002'];
                $replacement_vars = [$companyname, $companylogo, $groupname, $grouplogo, $chaptername,$companyurl, $groupurl, $person_name, $person_firstname, $person_lastname, $person_email, $groupcolor, $groupcolor2];

                if ($anonymous_member) {
                    // Send seperate emails to members and cc list.
                    // 1. First email to the member
                    $emesg_to = str_replace($replace_vars, $replacement_vars, $emesg); // Note we are using $emesg_to here
                    $_COMPANY->emailSend2($from, $email, $subject, $emesg_to, $app_type, $reply_addr, '', array(), '');

                    // 2. Second email to the cc list, if cc list is set.
                    if (!empty($email_cc_list)) {
                        $replacement_vars = [$companyname, $companylogo, $groupname, $grouplogo, $chaptername,$companyurl, $groupurl, 'Anonymous User', 'Anonymous', 'Anonymous', '', $groupcolor, $groupcolor2];
                        $emesg_cc = str_replace($replace_vars, $replacement_vars, $emesg); // Note we are using $emesg_cc here.
                        $_COMPANY->emailSend2($from, '', $subject, $emesg_cc, $app_type, $reply_addr, '', array(), $email_cc_list);
                    }
                } else {
                    $emesg = str_replace($replace_vars, $replacement_vars, $emesg);
                    $_COMPANY->emailSend2($from, $email, $subject, $emesg, $app_type, $reply_addr, '', array(), $email_cc_list);
                }

            }
        }
    }

    protected function processAsCreateType()
    {
        $this->processAsAnyType(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_JOIN']);
    }

    protected function processAsDeleteType()
    {
        $this->processAsAnyType(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_LEAVE']);
    }

    protected function processAsFollowupType()
    {
        $trigger_type = $this->options['trigger_type'];
        $this->processAsAnyType($trigger_type);
    }

    public function cancelAllPendingJobs(int $jobsubtype, int $member_userid)
    {
        $delete_jobid = "GRM_{$this->cid}_{$this->groupid}_{$this->chapterid}_{$this->channelid}_";
        return self::DBUpdate("UPDATE jobs SET status=100, processedby='CANCELLED', processedon=now() WHERE companyid='{$this->cid}' AND (jobid like '{$delete_jobid}%' AND jobsubtype={$jobsubtype} AND status=0 AND details='{$member_userid}')");
    }
}