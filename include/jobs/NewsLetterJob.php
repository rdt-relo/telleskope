<?php

class NewsLetterJob extends Job
{
    public $groupid;
    public $chapterid;
    public $channelid;
    public $newsletterid;

    public function __construct($gid, $chid, $nid)
    {
        parent::__construct();
        $this->groupid = $gid;
        $this->chapterid = $chid;
        $this->newsletterid = $nid;
        $this->jobid = "NEW_{$this->cid}_{$gid}_{$chid}_{$nid}_{$this->instance}_" . microtime(TRUE);
        $this->jobtype = self::TYPE_NEWSLETTER;
    }

    public function __clone()
    {
        parent::__clone();
        $this->jobid = "NEW_{$this->cid}_{$this->groupid}_{$this->chapterid}_{$this->newsletterid}_{$this->instance}_" . microtime(TRUE);
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

        $newsletter = Newsletter::GetNewsletter($this->newsletterid);
        // Use the chapterid set in the job as there can be multiple chapterids in the newsletter
        // But use the channelid from newsletter
        $useridArr = array();

        $chapteridsArray = empty($newsletter->val('chapterid')) ? array(0) : explode(',', $newsletter->val('chapterid'));
        foreach ($chapteridsArray as $chapterid) {
            $useridArr = array_merge($useridArr, explode(',', Group::GetGroupMembersAsCSV($this->groupid, $chapterid, $newsletter->val('channelid'), 'N', $newsletter->val('use_and_chapter_connector'))));
        }
        $useridArr = array_values(array_filter(array_unique($useridArr)));

        // If listids are provided then we use the following logic
        // If lists are at Admin Content level (i.e. groupid=0), then we will keep only the list data
        // Else, we will keep the intersection of users in the list and the provided group/chapter/channel scope
        if (!empty($newsletter->val('listids'))){
            if ($newsletter->isAdminContent()) {
                // $useridArr is expected to be empty in this case; so fetch all zone members
                $useridArr = explode(',', Group::GetGroupMembersAsCSV(0, 0, 0, 'N'));
            }
            $listUserids = DynamicList::GetUserIdsByListIds($newsletter->val('listids'));
            $useridArr = array_intersect($useridArr, $listUserids);
        }

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

    public function saveAsBatchUpdateType()
    {
        $this->saveAsUpdateType();
    }

    protected function processAsCreateType()
    {
        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */
        $replace_vars = ['[%RECIPIENT_NAME%]', '[%RECIPIENT_FIRST_NAME%]', '[%RECIPIENT_LAST_NAME%]', '[%RECIPIENT_EMAIL%]', '[%PUBLISH_DATE_TIME%]'];

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

        $newsletter = Newsletter::GetNewsletterForPublishing($this->newsletterid, $this->instance == 0);
        // Validate the newsletter exists and is not too old

        if (!empty($newsletter->val('content_replyto_email'))) { // Specific reply to address overrides all others
            $reply_addr = $newsletter->val('content_replyto_email');
        }

        // Publish to external integration points if integrations are on
        // Only group level newsletters will be published.
        // In the future we will evolve it to allow chapter newsletters to be published
        if (($this->instance === 0) && $_COMPANY->getAppCustomization()['integrations']['group']['enabled']) {
            $publish_to_external_integrations = $this->options['external_integrations'] ?? array();
            $integrations = GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($newsletter->val('groupid'), $newsletter->val('chapterid'), $newsletter->val('channelid'), 0, true);
            foreach ($integrations as $integration) {
                if (in_array($integration->id(), $publish_to_external_integrations)) {
                    $integration->processCreateNewsletter($newsletter);
                }
            }
        }

        if (($this->instance === 0) && ($this->options['send_emails'] && !$newsletter->val('publish_to_email'))) {
            // This is the first time we are processing publish to email so update the flag
            $newsletter->updatePublishToEmail(1);
        }

        $newsletter->postCreate();

        if ($newsletter === null || !$newsletter->isActive()) {
            return;
        } //no matching valid newsletter found

        $members = empty($this->details) ? array() : explode(',', $this->details);
        if (count($members) <= 0) {
            return;
        } //no users to notify

        if ($group) {
            $from = $group->getFromEmailLabel($newsletter->val('chapterid'), $newsletter->val('channelid'));
        }

        $email_content = $newsletter->val('newsletter');
        $email_logger = null;
        $email_open_pixel = '';
        if ($_COMPANY->getAppCustomization()['newsletters']['email_tracking']['enabled'] && $_ZONE->val('email_settings') >= 2) {
            $domain = $_COMPANY->getAppDomain($app_type);
            $email_logger = EmailLog::GetOrCreateEmailLog($domain, EmailLog::EMAILLOG_SECTION_TYPES['newsletter'], $newsletter->id(), $newsletter->val('version'), 'Initial', $this->createdby);
            if ($_COMPANY->getAppCustomization()['newsletters']['email_tracking']['track_urls']) {
                $email_content = $email_logger->updateHtmlToTrackUrlClicks($email_content);
            }
            $email_open_pixel = $email_logger->getEmailOpenPixelTemplate();
        }
        $email_content = EmailHelper::GetEmailTemplateForNewsletter('', '', $email_content, '', '', $email_open_pixel);

        $email_settings = $_ZONE->val('email_settings');

        if (empty($from)) $from = $_ZONE->val('email_from_label');

        $subject = html_entity_decode($newsletter->val('newslettername'));

        $attachments = array();
        $newsletter_attachments = self::DBROGet("SELECT * FROM `newsletter_attachments` WHERE `newsletter_attachments`.`companyid`='{$_COMPANY->id()}' AND `newsletter_attachments`.`zoneid`='{$_ZONE->id()}' AND  `newsletterid`='{$this->newsletterid}' AND groupid='{$this->groupid}'");
        $index = 1;
        foreach ($newsletter_attachments as $datum) {
            if (strpos($datum['attachment'], $_COMPANY->val('s3_folder')) !== false) {
                $ext = pathinfo($datum['attachment'], PATHINFO_EXTENSION);
                $filename = slugify($datum['title']);

                $filenameCounts = array_count_values(array_column($attachments, 'filename'));
                $previousUseCount = $filenameCounts[$filename.'.'.$ext] ?? 0;
                if($previousUseCount){
                    $filename = $filename.($index++);
                }


                $attach_contents = get_curl($datum['attachment'], array());

                $attach['filename'] = $filename . '.' . $ext;
                $attach['content'] = $attach_contents;
                $attachments[] = $attach;
            }
        }

        $msg = $groupname . ' - ' . htmlspecialchars_decode($subject);

        for ($i = 0; $i < count($members); $i++) {
            $mid = $members[$i];
            if (empty($mid) || ($touser = User::GetUser((int)$mid)) === NULL || !$touser->isActive())
                continue;

            // Push Notification New Announcement
            $setting = $touser->val('notification');
            if ($setting == 1 && $_COMPANY->getAppCustomization()['mobileapp']['enabled']) {
                $users = self::DBROGet("SELECT api_session_id, `userid`, `devicetype`, `devicetoken` FROM users_api_session WHERE `userid`='" . $mid . "' and devicetoken!=''");
                if (count($users) > 0) {
                    $badge = 1;
                    [$bearerToken, $firebaseProjectId] = $_COMPANY->getFirebaseBearerTokenAndProjectId();
                    for ($d = 0; $d < count($users); $d++) {
                        sendCommonPushNotification($users[$d]['devicetoken'], 'New Newsletter!', $msg, $badge, self::PUSH_NOTIFICATIONS_STATUS['NEWSLETTER'], $this->newsletterid, 1, $_ZONE->id(),$bearerToken,'v1',$firebaseProjectId );
                    }
                }
            }

            $email = $touser->val('email');
            if ($email_settings >= 2 && $email) {
                $email_content_custom = $email_content; // Init it to the template

                $name = $touser->getFullName();
                $enc_tracking_id = '';
                if ($email_logger) {
                    $enc_tracking_id = $email_logger->addOrGetRcptByUseridOrEmail(($touser ? $touser->id() : 0), $email);
                    $email_content_custom = str_replace('___EMAILLOG_ENC_USER___', $enc_tracking_id, $email_content_custom);
                }
                $publish_date_time  = $touser->formatUTCDatetimeForDisplayInLocalTimezone($newsletter->val('publishdate'), true, true, true,'', $touser->val('timezone') ?? 'UTC');
                $replacement_vars = [$name, $touser->val('firstname'), $touser->val('lastname'), $email, $publish_date_time];
                $emesg = str_replace($replace_vars, $replacement_vars, $email_content_custom);
                if (!$_COMPANY->emailSend2($from, $email, $subject, $emesg, $app_type, $reply_addr, '', $attachments)) {
                    if ($email_logger) {
                        $email_logger->resetRcptSentTimestampToNull($enc_tracking_id);
                    }
                }
            }
        }
    }

    public function sendForReview(string $toList, string $reviewNote, string $subjectPrefix = 'Review: ')
    {

        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */
        $replace_vars = ['[%RECIPIENT_NAME%]', '[%RECIPIENT_FIRST_NAME%]', '[%RECIPIENT_LAST_NAME%]', '[%RECIPIENT_EMAIL%]', '[%PUBLISH_DATE_TIME%]'];

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

        $newsletter = Newsletter::GetNewsletter($this->newsletterid);
        if (!$newsletter) {
            return;
        } //no matching valid newsletter found

        if (!empty($newsletter->val('content_replyto_email'))) { // Specific reply to address overrides all others
            $reply_addr = $newsletter->val('content_replyto_email');
        }

        $members = empty($toList) ? array() : explode(',', $toList);
        if (count($members) <= 0) {
            return;
        }

        if ($group) {
            $from = $group->getFromEmailLabel($newsletter->val('chapterid'), $newsletter->val('channelid'));
        }

        $email_content = $newsletter->val('newsletter');
        $publishdate = "";
        if($newsletter->val('publishdate')){
          $publishdate = $newsletter->val('publishdate');
        }

        $email_settings = $_ZONE->val('email_settings');

        if (empty($from)) $from = $_ZONE->val('email_from_label');

        if (empty($subject)) {
            $subject = $subjectPrefix . html_entity_decode($newsletter->val('newslettername'));
        }

        $email_content = EmailHelper::GetEmailTemplateForNewsletter($reviewNote, '', $email_content, '', '', '');

        $attachments = array();
        $newsletter_attachments = self::DBROGet("SELECT * FROM `newsletter_attachments` WHERE `newsletter_attachments`.`companyid`='{$_COMPANY->id()}' AND `newsletter_attachments`.`zoneid`='{$_ZONE->id()}' AND `newsletterid`='{$newsletter->id()}' AND groupid='{$this->groupid}'");

        $index = 1;
        foreach ($newsletter_attachments as $datum) {
          
            if (strpos($datum['attachment'], $_COMPANY->val('s3_folder')) !== false) {

                $ext = pathinfo($datum['attachment'], PATHINFO_EXTENSION);
                $filename = slugify($datum['title']);

                $filenameCounts = array_count_values(array_column($attachments, 'filename'));
                $previousUseCount = $filenameCounts[$filename.'.'.$ext] ?? 0;
                if($previousUseCount){
                    $filename = $filename.($index++);
                }

                $attach_contents = get_curl($datum['attachment'], array());

                $attach['filename'] = $filename . '.' . $ext;
                $attach['content'] = $attach_contents;
                $attachments[] = $attach;
            }
        }


        if ($email_settings >= 2) {
            foreach ($members as $member_email) {
                $replacement_vars = array();
                $toUser = User::GetUserByEmail($member_email);
                if ($toUser) {
                    $publish_date_time  = $toUser->formatUTCDatetimeForDisplayInLocalTimezone($newsletter->val('publishdate'), true, true, true,'', $toUser->val('timezone') ?? 'UTC');
                    $replacement_vars = [$toUser->getFullName(), $toUser->val('firstname'), $toUser->val('lastname'), $toUser->val('email'), $publish_date_time];
                } else {
                    $publish_date_time = $newsletter->val('publishdate');
                    $replacement_vars = [$member_email, $member_email, $member_email, $member_email, $publish_date_time]; // We do not know the firstname or lastname
                }
                $member_email_content = str_replace($replace_vars, $replacement_vars, $email_content);
                $_COMPANY->emailSend2($from, $member_email, $subject, $member_email_content, $app_type, $reply_addr, '', $attachments);
            }
        }

    }

    public function cancelAllPendingJobs()
    {
        $delete_jobid = "NEW_{$this->cid}_{$this->groupid}_%_{$this->newsletterid}_%";
        return self::DBUpdate("UPDATE jobs SET status=100, processedby='CANCELLED', processedon=now() WHERE companyid='{$this->cid}' AND (jobid like '{$delete_jobid}' AND jobtype={$this->jobtype} AND status=0)");
    }

    protected function processAsUpdateType()
    {
        $newsletter = Newsletter::GetNewsletter($this->newsletterid);
        $newsletter->postUpdate();
    }
}