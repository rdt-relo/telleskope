<?php

class PostJob extends Job
{
    public $groupid;
    public $postid;

    public function __construct($gid, $pid)
    {
        parent::__construct();
        $this->groupid = $gid;
        $this->postid = $pid;
        $this->jobid = "POS_{$this->cid}_{$gid}_{$pid}_{$this->instance}_" . microtime(TRUE);
        $this->jobtype = self::TYPE_POST;
    }

    public function __clone()
    {
        parent::__clone();
        $this->jobid = "POS_{$this->cid}_{$this->groupid}_{$this->postid}_{$this->instance}_" . microtime(TRUE);
    }

    public function saveAsBatchCreateType(int $sendEmails = 1, array $external_integrations = array())
    {
        // First save the default 0 instance as it has logic to change status to publish and also publish to external
        // Integration points
        $this->options['send_emails'] = $sendEmails; // Only the base job for create will use this
        $this->options['external_integrations'] = $external_integrations;
        $this->saveAsCreateType();
        // Next if we are not sending notifications then return
        if (!$sendEmails) {
            return;
        }
        // Else calculate the impacted users and create cloned jobs
        $post = Post::GetPost($this->postid);
        $useridArr = array();

        $chapteridsArray = empty($post->val('chapterid')) ? array(0) : explode(',', $post->val('chapterid'));
        foreach ($chapteridsArray as $chapterid) {
            $useridArr = array_merge($useridArr, explode(',', Group::GetGroupMembersAsCSV($this->groupid, $chapterid, $post->val('channelid'), 'P', $post->val('use_and_chapter_connector'))));
        }
        $useridArr = array_values(array_filter(array_unique($useridArr)));

        // If listids are provided then we use the following logic
        // If lists are at Admin Content level (i.e. groupid=0), then we will keep only the list data
        // Else, we will keep the intersection of users in the list and the provided group/chapter/channel scope
        if (!empty($post->val('listids'))){
            if ($post->isAdminContent()) {
                // $useridArr is expected to be empty in this case; so fetch all zone members
                $useridArr = explode(',', Group::GetGroupMembersAsCSV(0, 0, 0, 'P'));
            }
            $listUserids = DynamicList::GetUserIdsByListIds($post->val('listids'));
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

    public function saveAsBatchUpdateType(int $sendEmails = 1, array $external_integrations = array())
    {
        // First save the default 0 instance as it has logic to change status to publish and also publish to external
        // Integration points
        $this->options['send_emails'] = $sendEmails; // Only the base job for update will use this
        $this->options['external_integrations'] = $external_integrations;
        $this->saveAsUpdateType();
        // Next if we are not sending notifications then return
        if (!$sendEmails) {
            return;
        }
        // Else calculate the impacted users and create cloned jobs

        $post = Post::GetPost($this->postid);

        $useridArr = array();

        $chapteridsArray = empty($post->val('chapterid')) ? array(0) : explode(',', $post->val('chapterid'));
        foreach ($chapteridsArray as $chapterid) {
            $useridArr = array_merge($useridArr, explode(',', Group::GetGroupMembersAsCSV($this->groupid, $chapterid, $post->val('channelid'), 'P',$post->val('use_and_chapter_connector'))));
        }
        $useridArr = array_values(array_filter(array_unique($useridArr)));

        // If listids are provided then we use the following logic
        // If lists are at Admin Content level (i.e. groupid=0), then we will keep only the list data
        // Else, we will keep the intersection of users in the list and the provided group/chapter/channel scope
        if (!empty($post->val('listids'))){
            if ($post->isAdminContent()) {
                // $useridArr is expected to be empty in this case; so fetch all zone members
                $useridArr = explode(',', Group::GetGroupMembersAsCSV(0, 0, 0, 'P'));
            }
            $listUserids = DynamicList::GetUserIdsByListIds($post->val('listids'));
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

        $post = Post::GetPostForPublishing($this->postid, $this->instance == 0);
        if (!($post && ($post->val('companyid') == $_COMPANY->id()) && $post->isActive())) {
            // Class comparison is just for a security check which should always be true.
            return;
        }

        if (($this->instance === 0) && $_COMPANY->getAppCustomization()['integrations']['group']['enabled']) {
            $publish_to_external_integrations = $this->options['external_integrations'] ?? array();
            $integrations = GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($post->val('groupid'), $post->val('chapterid'), $post->val('channelid'), 0, true);
            foreach ($integrations as $integration) {
                if (in_array($integration->id(), $publish_to_external_integrations)) {
                    $integration->processCreatePost($post);
                }
            }
        }

        if (($this->instance === 0) && ($this->options['send_emails'] && !$post->val('publish_to_email'))) {
            // This is the first time we are processing publish to email so update the flag
            $post->updatePublishToEmail(1);
        }

        $post->postCreate();

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
            $from = $group->getFromEmailLabel($post->val('chapterid'), $post->val('channelid'));
            $reply_addr = $group->val('replyto_email');
            $group_logo = $group->val('groupicon');
        }

        if (!empty($post->val('content_replyto_email'))) { // Specific reply to address overrides all others
            $reply_addr = $post->val('content_replyto_email');
        }

        $who = User::GetUser($this->createdby);

        $members = empty($this->details) ? array() : explode(',', $this->details);
        if (count($members) <= 0) {
            return;
        } //no users to notify

        $posttitle = $post->val('title');

        $message = 'posted ' . self::mysqli_escape($posttitle) . ' in ' . self::mysqli_escape($groupname);
        $msg = $groupname . ' posted a new announcement!';

        $email_settings = $_ZONE->val('email_settings');

        $subject = html_entity_decode($posttitle);

        $url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'viewpost?id=' . $_COMPANY->encodeId($this->postid);

        $reviewNote = $this->getDisclaimer($post);

        $post_val = EmailHelper::OutlookFixes($post->val('post'));

        $email_logger = null;
        $email_open_pixel = '';
        if ($_COMPANY->getAppCustomization()['post']['email_tracking']['enabled'] && $email_settings >= 2) {
            $domain = $_COMPANY->getAppDomain($app_type);
            $email_logger = EmailLog::GetOrCreateEmailLog($domain, EmailLog::EMAILLOG_SECTION_TYPES['post'], $post->id(), $post->val('version'), 'Initial', $this->createdby);
            if ($_COMPANY->getAppCustomization()['post']['email_tracking']['track_urls']) {
                $post_val = $email_logger->updateHtmlToTrackUrlClicks($post_val);
            }
            $email_open_pixel = $email_logger->getEmailOpenPixelTemplate();
        }
        
        $email_content = EmailHelper::GetEmailTemplateForPost($groupname, $group_logo, '', $from, $posttitle, $reviewNote, '', $post_val, $url, 'Like and Comment', $email_open_pixel, '#0077b5', '#0077b5', $post);

        for ($i = 0; $i < count($members); $i++) {
            $mid = $members[$i];
            if (empty($mid) || ($touser = User::GetUser((int)$mid)) === NULL || !$touser->isActive())
                continue;

            //$insertNoti = self::DBInsert("INSERT INTO `notifications`(`zoneid`, `section`, `userid`, `whodo`, `tableid`, `message`, `datetime`, `isread`) VALUES ('{$_ZONE->id()}', '2','" . $mid . "','" . $this->createdby . "','" . $this->postid . "','" . $message . "',now(),'2')");

            // Push Notification New Announcement
            $setting = $touser->val('notification');
            if ($setting == 1 && $_COMPANY->getAppCustomization()['mobileapp']['enabled']) {
                $users = self::DBROGet("SELECT api_session_id, `userid`, `devicetype`, `devicetoken` FROM users_api_session WHERE `userid`='" . $mid . "' and devicetoken!=''");
                if (count($users) > 0) {
                    $badge = 1;
                    [$bearerToken, $firebaseProjectId] = $_COMPANY->getFirebaseBearerTokenAndProjectId();
                    for ($d = 0; $d < count($users); $d++) {
                        sendCommonPushNotification($users[$d]['devicetoken'], 'New Announcement!', $msg, $badge, self::PUSH_NOTIFICATIONS_STATUS['POST'], $this->postid, 1, $_ZONE->id(),$bearerToken,'v1',$firebaseProjectId);
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

        $post = Post::GetPost($this->postid);
        if (!($post && ($post->val('companyid') == $_COMPANY->id()) && $post->isActive())) {
            // Class comparison is just for a security check which should always be true.
            return;
        }

        if (($this->instance === 0) && $_COMPANY->getAppCustomization()['integrations']['group']['enabled']) {
            $publish_to_external_integrations = $this->options['external_integrations'] ?? array();
            $integrations = GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($post->val('groupid'), $post->val('chapterid'), $post->val('channelid'), 0, true);
            foreach ($integrations as $integration) {
                if (in_array($integration->id(), $publish_to_external_integrations)) {
                    $integration->processUpdatePost($post);
                }
            }
        }

        if (($this->instance === 0) && ($this->options['send_emails'] && !$post->val('publish_to_email'))) {
            // This is the first time we are processing publish to email so update the flag
            $post->updatePublishToEmail(1);
        }

        $post->postUpdate();

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
            $from = $group->getFromEmailLabel($post->val('chapterid'), $post->val('channelid'));
            $reply_addr = $group->val('replyto_email');
            $group_logo = $group->val('groupicon');
        }

        if (!empty($post->val('content_replyto_email'))) { // Specific reply to address overrides all others
            $reply_addr = $post->val('content_replyto_email');
        }

        $who = User::GetUser($this->createdby);

        $members = empty($this->details) ? array() : explode(',', $this->details);
        if (count($members) <= 0) {
            return;
        } //no users to notify

        $posttitle = $post->val('title');

        $message = 'updated ' . self::mysqli_escape($posttitle) . ' in ' . self::mysqli_escape($groupname);
        $msg = $groupname . ' updated an announcement!';

        $email_settings = $_ZONE->val('email_settings');

        $subject = 'Updated: ' . html_entity_decode($posttitle);

        $url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'viewpost?id=' . $_COMPANY->encodeId($this->postid);

        $reviewNote = $this->getDisclaimer($post);

        $post_val = EmailHelper::OutlookFixes($post->val('post'));

        $email_logger = null;
        $email_open_pixel = '';
        if ($_COMPANY->getAppCustomization()['post']['email_tracking']['enabled'] && $email_settings >= 2) {
            $domain = $_COMPANY->getAppDomain($app_type);
            $email_logger = EmailLog::GetOrCreateEmailLog($domain, EmailLog::EMAILLOG_SECTION_TYPES['post'], $post->id(), $post->val('version'), 'Update', $this->createdby);
            if ($_COMPANY->getAppCustomization()['post']['email_tracking']['track_urls']) {
                $post_val = $email_logger->updateHtmlToTrackUrlClicks($post_val);
            }
            $email_open_pixel = $email_logger->getEmailOpenPixelTemplate();
        }

        $email_content = EmailHelper::GetEmailTemplateForPost($groupname, $group_logo, '', $from, $posttitle, $reviewNote, '', $post_val, $url, 'Like and Comment', $email_open_pixel, '#0077b5', '#0077b5', $post);

        for ($i = 0; $i < count($members); $i++) {
            $mid = $members[$i];
            if (empty($mid) || ($touser = User::GetUser((int)$mid)) === NULL || !$touser->isActive())
                continue;

          
            //$insertNoti = self::DBInsert("INSERT INTO `notifications`(`zoneid`, `section`, `userid`, `whodo`, `tableid`, `message`, `datetime`, `isread`) VALUES ('{$_ZONE->id()}', '2','" . $mid . "','" . $this->createdby . "','" . $this->postid . "','" . $message . "',now(),'2')");
            //Push Notification Update Announcement
            $setting = $touser->val('notification');
            if ($setting == 1 && $_COMPANY->getAppCustomization()['mobileapp']['enabled']) {
                $users = self::DBROGet("SELECT api_session_id, `userid`, `devicetype`, `devicetoken` FROM users_api_session WHERE `userid`='" . $mid . "' and devicetoken!=''");
                if (count($users) > 0) {
                    $badge = 1;
                    [$bearerToken, $firebaseProjectId] = $_COMPANY->getFirebaseBearerTokenAndProjectId();
                    for ($d = 0; $d < count($users); $d++) {
                        sendCommonPushNotification($users[$d]['devicetoken'], 'Announcement Updated!', $msg, $badge, self::PUSH_NOTIFICATIONS_STATUS['POST'], $this->postid, 1, $_ZONE->id(),$bearerToken,'v1',$firebaseProjectId);
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

    protected function processAsDeleteType()
    {
        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */

        $post = Post::GetPost($this->postid);
        if (!($post && ($post->val('companyid') == $_COMPANY->id()) && $post->isInactive())) {
            // Class comparison is just for a security check which should always be true.
            return;
        }

        if (($this->instance === 0) && $_COMPANY->getAppCustomization()['integrations']['group']['enabled']) {
            $integrations = GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($post->val('groupid'), $post->val('chapterid'), $post->val('channelid'), 0, true);
            foreach ($integrations as $integration) {
                $integration->processDeletePost($post);
            }
            Post::PostDelete($this->postid);
        }
    }

    public function sendForReview(string $toList, string $reviewNote, string $subjectPrefix = 'Review: ')
    {
        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */

        $post = Post::GetPost($this->postid);
        if (!($post && ($post->val('companyid') == $_COMPANY->id()))) {
            // Class comparison is just for a security check which should always be true.
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
            $from = $group->getFromEmailLabel($post->val('chapterid'), $post->val('channelid'));
            $reply_addr = $group->val('replyto_email');
            $group_logo = $group->val('groupicon');
        }

        if (!empty($post->val('content_replyto_email'))) { // Specific reply to address overrides all others
            $reply_addr = $post->val('content_replyto_email');
        }

        $who = User::GetUser($this->createdby);

        $members = empty($toList) ? array() : explode(',', $toList);
        if (count($members) <= 0) {
            return;
        } //no users to notify

        $posttitle = $post->val('title');

        $email_settings = $_ZONE->val('email_settings');

        $subject = $subjectPrefix . html_entity_decode($posttitle);

        $url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'viewpost?id=' . $_COMPANY->encodeId($this->postid);

        if (!empty($reviewNote)) {
            $reviewNote = '<div style="background-color:#80808026; padding:20px;"><b>Note:&nbsp;</b>' . stripcslashes($reviewNote) . '</div>';
        }

        $reviewNote .= $this->getDisclaimer($post);

        $post_val = EmailHelper::OutlookFixes($post->val('post'));
        $email_open_pixel = '';
        $email_content = EmailHelper::GetEmailTemplateForPost($groupname, $group_logo, '', $from, $posttitle, $reviewNote, '', $post_val, $url, 'Like and Comment', $email_open_pixel, '#0077b5', '#0077b5', $post);

        if ($email_settings >= 2) {
            $_COMPANY->emailSend2($from, $toList, $subject, $email_content, $app_type, $reply_addr);
        }
    }

    public function cancelAllPendingJobs()
    {
        $delete_jobid = "POS_{$this->cid}_{$this->groupid}_{$this->postid}_";
        return self::DBUpdate("UPDATE jobs SET status=100, processedby='CANCELLED', processedon=now() WHERE companyid='{$this->cid}' AND (jobid like '{$delete_jobid}%' AND status=0)");
    }

    /**
     * @param string $email
     * @return bool
     */
    public function sharePostByEmail(string $email)
    {
        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */

        // Validate the post exists and is not too old
        $post = Post::GetPost($this->postid);
        if (!($post && ($post->val('companyid') == $_COMPANY->id()) && $post->isActive())) {
            // Class comparison is just for a security check which should always be true.
            return 0;
        }

        $groupname = 'All';
        $from = $_ZONE->val('email_from_label'); // Defaults to Zone From email
        $app_type = $_ZONE->val('app_type');
        $reply_addr = '';
        $group_logo = '';

        if ($this->groupid) {
            $group = Group::GetGroup($this->groupid);
            if ($group === null) {
                return false;
            } //no matching valid group found
            $groupname = $group->val('groupname');
            $from = $group->getFromEmailLabel($post->val('chapterid'), $post->val('channelid'));
            $reply_addr = $group->val('replyto_email');
            $group_logo = $group->val('groupicon');
        }

        if (!empty($post->val('content_replyto_email'))) { // Specific reply to address overrides all others
            $reply_addr = $post->val('content_replyto_email');
        }

        $who = User::GetUser($this->createdby);


        $posttitle = $post->val('title');

        $email_settings = $_ZONE->val('email_settings');

        $subject = "Shared: " . html_entity_decode($posttitle);

        $url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'viewpost?id=' . $_COMPANY->encodeId($this->postid);

        $reviewNote = '<div style="background-color:#80808026; padding:20px;"><b>Note:&nbsp;</b>' . $who->getFullName() . ' shared the following '.Post::GetCustomName(false).' with you.</div>';
        $reviewNote .= $this->getDisclaimer($post);

        $post_val = EmailHelper::OutlookFixes($post->val('post'));

        $email_logger = null;
        $email_open_pixel = '';
        if ($_COMPANY->getAppCustomization()['post']['email_tracking']['enabled'] && $email_settings >= 2) {
            $domain = $_COMPANY->getAppDomain($app_type);
            $email_logger = EmailLog::GetOrCreateEmailLog($domain, EmailLog::EMAILLOG_SECTION_TYPES['post'], $post->id(), $post->val('version'), 'Share', $this->createdby);
            if ($_COMPANY->getAppCustomization()['post']['email_tracking']['track_urls']) {
                $post_val = $email_logger->updateHtmlToTrackUrlClicks($post_val);
            }
            $email_open_pixel = $email_logger->getEmailOpenPixelTemplate();
        }

        $email_content = EmailHelper::GetEmailTemplateForPost($groupname, $group_logo, '', $from, $posttitle, $reviewNote, '', $post_val, $url, 'Like and Comment', $email_open_pixel, '#0077b5', '#0077b5', $post);

        if ($email_settings >= 2 && $email) {
            $email_content_custom = $email_content; // Init it to the template
            $enc_tracking_id = '';
            if ($email_logger) {
                $touser = User::GetUserByEmail($email);
                $enc_tracking_id = $email_logger->addOrGetRcptByUseridOrEmail(($touser ? $touser->id() : 0), $email);
                $email_content_custom = str_replace('___EMAILLOG_ENC_USER___', $enc_tracking_id, $email_content_custom);
            }
            if (!$_COMPANY->emailSend2($from, $email, $subject, $email_content_custom, $app_type, $reply_addr)) {
                if ($email_logger) {
                    $email_logger->resetRcptSentTimestampToNull($enc_tracking_id);
                }
                return 0;
            }
            return 1;
        }
        return 0;
    }

    private function getDisclaimer(Post $post): string
    {
        global $_COMPANY;
        return ($post->val('add_post_disclaimer') && $_COMPANY->getAppCustomization()['post']['post_disclaimer']['enabled']) ?
            '<br><p style="color:darkred !important; background:lightyellow !important;font-size: 10px; padding: 10px;">' . $_COMPANY->getAppCustomization()['post']['post_disclaimer']['disclaimer'] . '</p>' : '';
    }

}