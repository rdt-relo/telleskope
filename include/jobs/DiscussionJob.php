<?php

class DiscussionJob extends Job
{
    public $groupid;
    public $discussionid;

    public function __construct($gid, $did)
    {
        parent::__construct();
        $this->groupid = $gid;
        $this->discussionid = $did;
        $this->jobid = "DIS_{$this->cid}_{$gid}_{$did}_{$this->instance}_" . microtime(TRUE);
        $this->jobtype = self::TYPE_DISCUSSION;
    }

    public function __clone()
    {
        parent::__clone();
        $this->jobid = "DIS_{$this->cid}_{$this->groupid}_{$this->discussionid}_{$this->instance}_" . microtime(TRUE);
    }

    public function saveAsBatchCreateType(int $sendEmails = 1, array $external_integrations = array())
    {
        $this->options['send_emails'] = $sendEmails; // Only the base job for create will use this
        $this->options['external_integrations'] = $external_integrations;
        $this->saveAsCreateType();
        // Next if we are not sending notifications then return
        if (!$sendEmails) {
            return;
        }
        // Else calculate the impacted users and create cloned jobs
        $discussion = Discussion::GetDiscussion($this->discussionid);
        if(!$discussion){
            return;
        }
        
        $useridArr = array();
        $chapteridsArray = empty($discussion->val('chapterid')) ? array(0) : explode(',', $discussion->val('chapterid'));
        foreach ($chapteridsArray as $chapterid) {
            $useridArr = array_merge($useridArr, explode(',', Group::GetGroupMembersAsCSV($this->groupid, $chapterid, $discussion->val('channelid'), 'D')));
        }
        $useridArr = array_values(array_filter(array_unique($useridArr)));

        $total_recipients = count($useridArr);
        $offset_size = $this->batch_minimum_size;
        if ($total_recipients > ($this->batch_minimum_size * $this->batch_maximum_count)) { //recalculate batch size
            $offset_size = (int)($total_recipients / ($this->batch_maximum_count - 1));
        }

        for ($offset = 0; $offset < $total_recipients; $offset = $offset + $offset_size) {
            $batchArr = array_slice($useridArr, $offset, $offset_size);
            $batchList = implode(',', $batchArr);
            $copy = clone $this;
            $copy->details = $batchList;
            $copy->delay = $copy->delay + 1 + //VERY IMPORTANT TO USE THE ORIGINAL DELAY.
                ((int)($offset / $offset_size)) * $this->batch_stagger_seconds; // ADD STAGGER TO THE ORIGINAL DELAY
            $copy->saveAsCreateType();
        }
    }

    public function saveAsBatchUpdateType(int $sendEmails = 1, array $external_integrations = array())
    {
        $this->options['send_emails'] = $sendEmails; // Only the base job for update will use this
        $this->options['external_integrations'] = $external_integrations;
        $this->saveAsUpdateType();
        // Next if we are not sending notifications then return
        if (!$sendEmails) {
            return;
        }
        // Else calculate the impacted users and create cloned jobs
        $discussion = Discussion::GetDiscussion($this->discussionid);
        if(!$discussion){
            return;
        }
        // Get all member without chapter check
        $useridArr = array(); 
        $chapteridsArray = empty($discussion->val('chapterid')) ? array(0) : explode(',', $discussion->val('chapterid'));
        foreach ($chapteridsArray as $chapterid) {
            $useridArr = array_merge($useridArr, explode(',', Group::GetGroupMembersAsCSV($this->groupid, $chapterid, $discussion->val('channelid'), 'D')));
        }
        $useridArr = array_values(array_filter(array_unique($useridArr)));

        $total_recipients = count($useridArr);
        $offset_size = $this->batch_minimum_size;
        if ($total_recipients > ($this->batch_minimum_size * $this->batch_maximum_count)) { //recalculate batch size
            $offset_size = (int)($total_recipients / ($this->batch_maximum_count - 1));
        }

        for ($offset = 0; $offset < $total_recipients; $offset = $offset + $offset_size) {
            $batchArr = array_slice($useridArr, $offset, $offset_size);
            $batchList = implode(',', $batchArr);
            $copy = clone $this;
            $copy->details = $batchList;
            $copy->delay = $copy->delay + 1 + //VERY IMPORTANT TO USE THE ORIGINAL DELAY.
                ((int)($offset / $offset_size)) * $this->batch_stagger_seconds; // ADD STAGGER TO THE ORIGINAL DELAY
            $copy->saveAsUpdateType();
        }
    }

    public function saveAsBatchDeleteType(int $sendEmails = 1)
    {
        $this->options['send_emails'] = $sendEmails; // Only the base job for update will use this
        $this->saveAsDeleteType(); // Regardless of value of $sendEmails, save is directly as deleteType
    }

    protected function processAsCreateType()
    {
        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */

        $groupname = 'All';
        $from = $_ZONE->val('email_from_label'); // Defaults to Zone From email
        $app_type = $_ZONE->val('app_type');
        $reply_addr = '';
        $group_logo = '';
        $discussion = Discussion::GetDiscussion($this->discussionid);

        if (!$discussion){
            return;
        }

        $discussion->postCreate();

        if ($this->groupid) {
            $group = Group::GetGroup($this->groupid);
            if ($group === null) {
                return;
            } //no matching valid group found
            $groupname = $group->val('groupname');
            $from = $group->getFromEmailLabel($discussion->val('chapterid'), $discussion->val('channelid'));
            $reply_addr = $group->val('replyto_email');
            $group_logo = $group->val('groupicon');
        }
        
        $who = User::GetUser($this->createdby);
        $members = empty($this->details) ? array() : explode(',', $this->details);
        
        if (count($members) <= 0) {
            return;
        } //no users to notify
        $discussiontitle = $discussion->val('title');
        $message = 'posted ' . self::mysqli_escape($discussiontitle) . ' in ' . self::mysqli_escape($groupname);
        $msg = $groupname . ' posted a new discussion!';
        $email_settings = $_ZONE->val('email_settings');
        $subject = 'New discussion: '. html_entity_decode($discussiontitle);
        $url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'viewdiscussion?id=' . $_COMPANY->encodeId($this->discussionid);
        $discussion_val = EmailHelper::OutlookFixes($discussion->val('discussion'));
        $email_logger = null;
        $email_open_pixel = '';

        if ($_COMPANY->getAppCustomization()['discussions']['email_tracking']['enabled'] && $email_settings >= 2) {
            $domain = $_COMPANY->getAppDomain($app_type);
            $email_logger = EmailLog::GetOrCreateEmailLog($domain, EmailLog::EMAILLOG_SECTION_TYPES['discussion'], $discussion->id(), $discussion->val('version'), 'Initial', $this->createdby);
            if ($_COMPANY->getAppCustomization()['discussions']['email_tracking']['track_urls']) {
                $discussion_val = $email_logger->updateHtmlToTrackUrlClicks($discussion_val);
            }
            $email_open_pixel = $email_logger->getEmailOpenPixelTemplate();
        }
        $email_content = EmailHelper::GetEmailTemplateForDiscussion($groupname, $group_logo, '', $from, $discussiontitle, '', '', $discussion_val, $url, 'Like and Comment', $email_open_pixel);

        for ($i = 0; $i < count($members); $i++) {
            $mid = $members[$i];
            if (empty($mid) || ($touser = User::GetUser((int)$mid)) === NULL || !$touser->isActive())
                continue;

            //$insertNoti = self::DBInsert("INSERT INTO `notifications`(`zoneid`, `section`, `userid`, `whodo`, `tableid`, `message`, `datetime`, `isread`) VALUES ('{$_ZONE->id()}', '2','" . $mid . "','" . $this->createdby . "','" . $this->discussionid . "','" . $message . "',now(),'2')");

            // Push Notification New discussion
            $setting = $touser->val('notification');
            if ($setting == 1 && $_COMPANY->getAppCustomization()['mobileapp']['enabled']) {
                $users = self::DBGet("SELECT api_session_id, `userid`, `devicetype`, `devicetoken` FROM users_api_session WHERE `userid`='" . $mid . "' and devicetoken!=''");
                if (count($users) > 0) {
                    $badge = 1;
                    [$bearerToken, $firebaseProjectId] = $_COMPANY->getFirebaseBearerTokenAndProjectId();
                    for ($d = 0; $d < count($users); $d++) {
                        sendCommonPushNotification($users[$d]['devicetoken'], 'New discussion!', $msg, $badge, self::PUSH_NOTIFICATIONS_STATUS['DISCUSSION'], $this->discussionid, 1, $_ZONE->id(),$bearerToken,'v1',$firebaseProjectId);
                    }
                }
            }

            $email = $touser->val('email');
            if ($email_settings >= 2 && $email) {
                $email_content_custom = $email_content; // Init it to the template
                $enc_tracking_id = '';

                if ($email_logger) {
                    $enc_tracking_id = $email_logger->addOrGetRcptByUseridOrEmail($touser->id());
                    $email_content_custom = str_replace('___EMAILLOG_ENC_USER___', $enc_tracking_id, $email_content_custom);
                }

                if (!$_COMPANY->emailSend2($from, $email, $subject, $email_content_custom, $app_type, $reply_addr)) {
                    if ($email_logger) {
                        $email_logger->resetRcptSentTimestampToNull($enc_tracking_id);
                    }
                }
            }
        }
    }

    protected function processAsUpdateType()
    {
        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */

        $discussion = Discussion::GetDiscussion($this->discussionid);
        if (!$discussion) {
            return;
        }
        $groupname = 'All';
        $from = $_ZONE->val('email_from_label'); // Defaults to Zone From email
        $app_type = $_ZONE->val('app_type');
        $reply_addr = '';
        $group_logo = '';

        if ($this->groupid) {
            $group = Group::GetGroup($this->groupid);
            if ($group === null) {
                return;
            } //no matching valid group found
            $groupname = $group->val('groupname');
            $from = $group->getFromEmailLabel($discussion->val('chapterid'), $discussion->val('channelid'));
            $reply_addr = $group->val('replyto_email');
            $group_logo = $group->val('groupicon');
        }

        $who = User::GetUser($this->createdby);

        $members = empty($this->details) ? array() : explode(',', $this->details);
        if (count($members) <= 0) {
            return;
        } //no users to notify

        $discussiontitle = $discussion->val('title');

        $message = 'updated ' . self::mysqli_escape($discussiontitle) . ' in ' . self::mysqli_escape($groupname);
        $msg = $groupname . ' updated an discussion!';

        $email_settings = $_ZONE->val('email_settings');

        $subject = 'Discussion Updated: ' . html_entity_decode($discussiontitle);

        $url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'viewdiscussion?id=' . $_COMPANY->encodeId($this->discussionid);

        $discussion_val = EmailHelper::OutlookFixes($discussion->val('discussion'));

        $email_logger = null;
        $email_open_pixel = '';
        if ($_COMPANY->getAppCustomization()['discussions']['email_tracking']['enabled'] && $email_settings >= 2) {
            $domain = $_COMPANY->getAppDomain($app_type);
            $email_logger = EmailLog::GetOrCreateEmailLog($domain, EmailLog::EMAILLOG_SECTION_TYPES['discussion'], $discussion->id(), $discussion->val('version'), 'Update', $this->createdby);
            if ($_COMPANY->getAppCustomization()['discussions']['email_tracking']['track_urls']) {
                $discussion_val = $email_logger->updateHtmlToTrackUrlClicks($discussion_val);
            }
            $email_open_pixel = $email_logger->getEmailOpenPixelTemplate();
        }

        $email_content = EmailHelper::GetEmailTemplateForDiscussion($groupname, $group_logo, '', $from, $discussiontitle, '', '', $discussion_val, $url, 'Like and Comment', $email_open_pixel);

        for ($i = 0; $i < count($members); $i++) {
            $mid = $members[$i];
            if (empty($mid) || ($touser = User::GetUser((int)$mid)) === NULL || !$touser->isActive())
                continue;

            if ($this->groupid) {
                //Push Notification Update discussion
                $setting = $touser->val('notification');
                if ($setting == 1 && $_COMPANY->getAppCustomization()['mobileapp']['enabled']) {
                    $users = self::DBGet("SELECT api_session_id, `userid`, `devicetype`, `devicetoken` FROM users_api_session WHERE `userid`='" . $mid . "' and devicetoken!=''");
                    if (count($users) > 0) {
                        $badge = 1;
                        [$bearerToken, $firebaseProjectId] = $_COMPANY->getFirebaseBearerTokenAndProjectId();
                        for ($d = 0; $d < count($users); $d++) {
                            sendCommonPushNotification($users[$d]['devicetoken'], 'Discussion Updated!', $msg, $badge, self::PUSH_NOTIFICATIONS_STATUS['DISCUSSION'], $this->discussionid, 1, $_ZONE->id(),$bearerToken,'v1',$firebaseProjectId);
                        }
                    }
                }
            }

            $email = $touser->val('email');
            if ($email_settings >= 2 && $email) {
                $email_content_custom = $email_content; // Init it to the template
                $enc_tracking_id = '';

                if ($email_logger) {
                    $enc_tracking_id = $email_logger->addOrGetRcptByUseridOrEmail($touser->id());
                    $email_content_custom = str_replace('___EMAILLOG_ENC_USER___', $enc_tracking_id, $email_content_custom);
                }

                if (!$_COMPANY->emailSend2($from, $email, $subject, $email_content_custom, $app_type, $reply_addr)) {
                    if ($email_logger) {
                        $email_logger->resetRcptSentTimestampToNull($enc_tracking_id);
                    }
                }
            }
        }
    }
    public function cancelAllPendingJobs()
    {
        $delete_jobid = "DIS_{$this->cid}_{$this->groupid}_{$this->discussionid}_";
        return self::DBUpdate("UPDATE jobs SET status=100, processedby='CANCELLED', processedon=now() WHERE companyid='{$this->cid}' AND (jobid like '{$delete_jobid}%' AND status=0)");
    }


}