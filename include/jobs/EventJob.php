<?php

class EventJob extends Job
{
    public $groupid;
    public $eventid;

    const PRIVATE_EVENT_MESSAGE = 'This event is exclusive to our invited guests only, and we kindly request that you refrain from forwarding or sharing event details with others.';

    const FOLLOWUP_TYPES = [
        'EVENT_RECONCILIATION' => 'EVENT_RECONCILIATION',
    ];

    public function __construct($gid, $eid)
    {
        parent::__construct();
        $this->groupid = $gid;
        $this->eventid = $eid;
        $this->jobid = "EVT_{$this->cid}_{$gid}_{$eid}_{$this->instance}_" . microtime(TRUE);
        $this->jobtype = self::TYPE_EVENT;
    }

    public function __clone()
    {
        parent::__clone();
        $this->jobid = "EVT_{$this->cid}_{$this->groupid}_{$this->eventid}_{$this->instance}_" . microtime(TRUE);
    }

    public function saveAsBatchCreateType(int $sendEmails = 1, array $external_integrations = array())
    {
        // First save the default 0 instance as it has logic to change status to publish and also publish to external
        // Integration points
        $this->options['send_emails'] = $sendEmails; // Only the base job for update will use this
        $this->options['external_integrations'] = $external_integrations;
        $this->saveAsCreateType();
        // Next if we are not sending notifications then return
        if (!$sendEmails) {
            return;
        }
        // Else calculate the impacted users and create cloned jobs
        $event = Event::GetEvent($this->eventid);
        $useridArr = array();

        if ($event->val('teamid')) {
            $team = Team::GetTeam($event->val('teamid'));
            $teamMembers = $team->getTeamMembers(0);
            $useridArr = array_column($teamMembers, 'userid');
        } else {
            $groupIdsWithOutChapterDependencies = Group::FilterGroupsWithoutMatchingChapters($event->val('invited_groups'), $event->val('chapterid'));
            if (!empty($groupIdsWithOutChapterDependencies)) { // Get Group level members
                $groupIdsWithChapterDependenciesCSV = implode(',',$groupIdsWithOutChapterDependencies);
                $useridArr = array_merge($useridArr, explode(',', Group::GetGrouplistMembersAsCSV($groupIdsWithChapterDependenciesCSV, 0, $event->val('channelid'), $event->val('invited_locations'), 'E', $event->val('use_and_chapter_connector'))));
            }

            // Process chapter level members
            $groupIdsWithChapterDependencies = array_diff(explode(',',$event->val('invited_groups')), $groupIdsWithOutChapterDependencies);
            if (!empty($groupIdsWithChapterDependencies)) { // Get chapter level members
                $groupIdsWithChapterDependenciesCSV = implode(',', $groupIdsWithChapterDependencies);
                $chapteridsArray = empty($event->val('chapterid')) ? array(0) : explode(',', $event->val('chapterid'));
                foreach ($chapteridsArray as $chapterid) {
                    $useridArr = array_merge($useridArr, explode(',', Group::GetGrouplistMembersAsCSV($groupIdsWithChapterDependenciesCSV, $chapterid, $event->val('channelid'), $event->val('invited_locations'), 'E', $event->val('use_and_chapter_connector'))));
                }
            }

            // If listids are provided then we use the following logic
            // If lists are at Admin Content level, then we will keep only the list data
            // Else, we will keep the intersection of users in the list and the provided group/chapter/channel scope
            if (!empty($event->val('listids'))) {
                if ($event->isAdminContent()) {
                    // $useridArr is expected to be empty in this case; so fetch all zone members
                    $useridArr = explode(',', Group::GetGroupMembersAsCSV(0, 0, 0, 'E'));
                }
                $listUserids = DynamicList::GetUserIdsByListIds($event->val('listids'));
                $useridArr = array_intersect($useridArr, $listUserids);
            }
        }
        // Add event volunteers
        $volunteers = array_column($event->getEventVolunteers(), 'userid');
        $useridArr = array_merge($useridArr, $volunteers);
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

    public function saveAsBatchUpdateType(int $sendEmails = 1, array $sendEmailTo = array('1'), array $external_integrations = array())
    {
        // First save the default 0 instance as it has logic to change status to publish and also publish to external
        // Integration points
        $this->options['send_emails'] = $sendEmails; // Only the base job for update will use this
        $this->options['external_integrations'] = $external_integrations;
        //$this->options['set_publish_to_email_to_true'] = in_array('2', $sendEmailTo) ? 1 :0; // Only if we are sending emails to all should we update the flag.
        $this->saveAsUpdateType();
        // Next if we are not sending notifications then return
        if (!$sendEmails) {
            return;
        }
        // Else calculate the impacted users and create cloned jobs
        $useridArr = array();
        $rsvpArr = array();
        $event = Event::GetEvent($this->eventid);

        if ($event->val('teamid')) {
            $team = Team::GetTeam($event->val('teamid'));
            $teamMembers = $team->getTeamMembers(0);
            $useridArr = array_column($teamMembers, 'userid');
        } else {
            if (in_array('1', $sendEmailTo)) { // All RSVPed
                $rsvpArr = explode(',', Event::GetEventJoinersAsCSV($this->eventid)) ?: array();
            }
            if (in_array('2', $sendEmailTo)) { // All Invited

                // Add the members from invited chapters. Note chapters from any group can be invited which is why we are using GetChapterMembers method
                $invitedChapterMembers = Group::GetChapterMembers($event->val('invited_chapters'),'','E');
                $useridArr = array_merge($useridArr, $invitedChapterMembers);

                $groupIdsWithOutChapterDependencies = Group::FilterGroupsWithoutMatchingChapters($event->val('invited_groups'), $event->val('chapterid'));
                if (!empty($groupIdsWithOutChapterDependencies)) { // Get Group level members
                    $groupIdsWithChapterDependenciesCSV = implode(',',$groupIdsWithOutChapterDependencies);
                    $useridArr = array_merge($useridArr, explode(',', Group::GetGrouplistMembersAsCSV($groupIdsWithChapterDependenciesCSV, 0, $event->val('channelid'), $event->val('invited_locations'), 'E', $event->val('use_and_chapter_connector'))));
                }

                // Process chapter level members
                $groupIdsWithChapterDependencies = array_diff(explode(',',$event->val('invited_groups')), $groupIdsWithOutChapterDependencies);
                if (!empty($groupIdsWithChapterDependencies)) { // Get chapter level members
                    $groupIdsWithChapterDependenciesCSV = implode(',', $groupIdsWithChapterDependencies);
                    $chapteridsArray = empty($event->val('chapterid')) ? array(0) : explode(',', $event->val('chapterid'));
                    foreach ($chapteridsArray as $chapterid) {
                        $useridArr = array_merge($useridArr, explode(',', Group::GetGrouplistMembersAsCSV($groupIdsWithChapterDependenciesCSV, $chapterid, $event->val('channelid'), $event->val('invited_locations'), 'E', $event->val('use_and_chapter_connector'))));
                    }
                }

                // If listids are provided then we use the following logic
                // If lists are at Admin Content level, then we will keep only the list data
                // Else, we will keep the intersection of users in the list and the provided group/chapter/channel scope
                if (!empty($event->val('listids'))) {
                    if ($event->isAdminContent()) {
                        // $useridArr is expected to be empty in this case; so fetch all zone members
                        $useridArr = explode(',', Group::GetGroupMembersAsCSV(0, 0, 0, 'E'));
                    }
                    $listUserids = DynamicList::GetUserIdsByListIds($event->val('listids'));
                    $useridArr = array_intersect($useridArr, $listUserids);
                }
            }
            $useridArr = array_merge($useridArr, $rsvpArr);
        }
        // Add event volunteers
        $volunteers = array_column($event->getEventVolunteers(), 'userid');
        $useridArr = array_merge($useridArr, $volunteers);
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

    public function saveAsBatchDeleteType(int $sendEmails = 1, bool $deleteAfterCancel = false)
    {
        // First save the default 0 instance as it has logic to change status to publish and also publish to external
        // Integration points
        $this->options['send_emails'] = $sendEmails; // Only the base job may use this
        $this->options['delete_after_cancel'] = $deleteAfterCancel;
        $this->saveAsDeleteType();

        $event = Event::GetEvent($this->eventid);
        $useridArr = array();

        if ($sendEmails) { // Only if the send emails to all invited is on then calculate useridArrs based on invited user lists
            if ($event->val('teamid')) {
                $team = Team::GetTeam($event->val('teamid'));
                $teamMembers = $team->getTeamMembers(0);
                $useridArr = array_column($teamMembers, 'userid');
            } else {
                // If the event was orignially published with calendar invitation - i.e. published to email and was not a limited capacity event, then send cancellation note to all target members
                //if ($event->val('publish_to_email') && $event->sendIcal()) {

                // Add the members from invited chapters. Note chapters from any group can be invited which is why we are using GetChapterMembers method
                $invitedChapterMembers = Group::GetChapterMembers($event->val('invited_chapters'), '', 'E');
                $useridArr = array_merge($useridArr, $invitedChapterMembers);

                $groupIdsWithOutChapterDependencies = Group::FilterGroupsWithoutMatchingChapters($event->val('invited_groups'), $event->val('chapterid'));
                if (!empty($groupIdsWithOutChapterDependencies)) { // Get Group level members
                    $groupIdsWithChapterDependenciesCSV = implode(',', $groupIdsWithOutChapterDependencies);
                    $useridArr = array_merge($useridArr, explode(',', Group::GetGrouplistMembersAsCSV($groupIdsWithChapterDependenciesCSV, 0, $event->val('channelid'), $event->val('invited_locations'), 'E', $event->val('use_and_chapter_connector'))));
                }

                // Process chapter level members
                $groupIdsWithChapterDependencies = array_diff(explode(',', $event->val('invited_groups')), $groupIdsWithOutChapterDependencies);
                if (!empty($groupIdsWithChapterDependencies)) { // Get chapter level members
                    $groupIdsWithChapterDependenciesCSV = implode(',', $groupIdsWithChapterDependencies);
                    $chapteridsArray = empty($event->val('chapterid')) ? array(0) : explode(',', $event->val('chapterid'));
                    foreach ($chapteridsArray as $chapterid) {
                        $useridArr = array_merge($useridArr, explode(',', Group::GetGrouplistMembersAsCSV($groupIdsWithChapterDependenciesCSV, $chapterid, $event->val('channelid'), $event->val('invited_locations'), 'E', $event->val('use_and_chapter_connector'))));
                    }
                }

                // If listids are provided then we use the following logic
                // If lists are at Admin Content level, then we will keep only the list data
                // Else, we will keep the intersection of users in the list and the provided group/chapter/channel scope
                if (!empty($event->val('listids'))) {
                    if ($event->isAdminContent()) {
                        // $useridArr is expected to be empty in this case; so fetch all zone members
                        $useridArr = explode(',', Group::GetGroupMembersAsCSV(0, 0, 0, 'E'));
                    }
                    $listUserids = DynamicList::GetUserIdsByListIds($event->val('listids'));
                    $useridArr = array_intersect($useridArr, $listUserids);
                }
            }
        }

        $rsvpArr = explode(',', Event::GetEventJoinersAsCSV($this->eventid)) ?: array();
        $useridArr = array_values(array_filter(array_unique(array_merge($useridArr, $rsvpArr))));

        // Add event volunteers
        $volunteers = array_column($event->getEventVolunteers(), 'userid');
        $useridArr = array_values(array_filter(array_unique(array_merge($useridArr, $volunteers))));

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
            $copy->saveAsDeleteType();
        }
    }

    public function saveAsInviteType($useridList, string $invitationMessage)
    {
        if (!empty($useridList)) {
            $this->jobid = $this->jobid . '_' . microtime(TRUE);
            $this->details = $useridList;
            $this->options['invitation_message'] = $invitationMessage;
            $this->options['subject_prefix'] = 'Invitation:';
            $copy = clone $this; // Skip the instance type 0 as we are not using it. Clone increases instance number

            $copy->saveAsCreateType();
        }
    }

    /**
     * @param string $subject
     * @param string $message
     * @param int $includeEventDetails
     * @param array $reminderTo
     * @param int $futureEventsOnly
     * @param string $userIdsCSV a comma seperated value of userids
     * @return int; if no users were found in the rsvp/to list, then -1 is returned; else 1 for success and 0 for error
     */
    public function saveAsBatchRemindType(string $subject, string $message, int $includeEventDetails, array $reminderTo, int $futureEventsOnly = 0, string $userIdsCSV=''): int
    {
        $retVal = -1;
        $useridArr = array();
        $l1 = array();
        $l2 = array();
        $detail = array();
        $detail['SUBJECT'] = $subject;
        $detail['MESSAGE'] = $message;
        $detail['INCLUDE_EVENT_DETAILS'] = $includeEventDetails;

        if (str_starts_with($subject, 'Followup: ')) {
            $detail['LABEL'] = 'Followup: ';

        } elseif (str_starts_with($subject, 'Review: ')) {
            $detail['LABEL'] = 'Review: ';
        } else {
            $detail['LABEL'] = 'Reminder: ';
        }

        if(!empty($userIdsCSV)){ // First add the explicitly added userids
            $useridArr = explode(',',$userIdsCSV);
            $useridArr = array_filter($useridArr); // Remove empty values
        }
        // Make sure $reminderTo is an int array. Current version of PHP does not allow array to be specified as array
        // of int. So we will use this method.
        $reminderTo = array_unique(array_map('intval', $reminderTo));

        $event = Event::GetEvent($this->eventid);
        $eventIds = strval($this->eventid); // Since this var will store a comma seperated list, cast it to string.
        if ($event->isSeriesEventHead()) {
            $eventFilter = "";
            if ($futureEventsOnly) {
                $eventFilter = "AND `start` > NOW()";
            }

            $allEventsInSeries = self::DBGet("SELECT `eventid` FROM `events` WHERE `event_series_id`='{$this->eventid}' {$eventFilter} AND `isactive`=1");
            if (!empty($allEventsInSeries)) {
                $eventIds = implode(',', array_column($allEventsInSeries, 'eventid'));
            }
        }

        self::DBGet("SET SESSION group_concat_max_len = 2048000"); //Increase the session limit; default is 1000

        // First build a list of all invited users for the following two usecases:
        // (a) Remind all invited i.e. value -1
        // (b) Remind all invited who did not RSVP yet, i.e. value 0
        if (in_array('-1', $reminderTo) || in_array('0', $reminderTo)) { // All Invited
            if ($event->isPublishedToEmail()) {
                if ($event->val('teamid')) { // If team event
                    $team = Team::GetTeam($event->val('teamid'));
                    $teamMembers = $team->getTeamMembers(0);
                    $useridArr = array_column($teamMembers, 'userid');
                } else { // If group/chapter/channel event
                    // Build dynamic list of invited users only if the event was published to email
                    // Other group members are not really invited yet.

                    // Add the members from invited chapters. Note chapters from any group can be invited which is why we are using GetChapterMembers method
                    $invitedChapterMembers = Group::GetChapterMembers($event->val('invited_chapters'),'','E');
                    $useridArr = array_merge($useridArr, $invitedChapterMembers);

                    $groupIdsWithOutChapterDependencies = Group::FilterGroupsWithoutMatchingChapters($event->val('invited_groups'), $event->val('chapterid'));
                    if (!empty($groupIdsWithOutChapterDependencies)) { // Get Group level members
                        $groupIdsWithChapterDependenciesCSV = implode(',',$groupIdsWithOutChapterDependencies);
                        $useridArr = array_merge($useridArr, explode(',', Group::GetGrouplistMembersAsCSV($groupIdsWithChapterDependenciesCSV, 0, $event->val('channelid'), $event->val('invited_locations'), 'E', $event->val('use_and_chapter_connector'))));
                    }

                    // Process chapter level members
                    $groupIdsWithChapterDependencies = array_diff(explode(',',$event->val('invited_groups')), $groupIdsWithOutChapterDependencies);
                    if (!empty($groupIdsWithChapterDependencies)) { // Get chapter level members
                        $groupIdsWithChapterDependenciesCSV = implode(',', $groupIdsWithChapterDependencies);
                        $chapteridsArray = empty($event->val('chapterid')) ? array(0) : explode(',', $event->val('chapterid'));
                        foreach ($chapteridsArray as $chapterid) {
                            $useridArr = array_merge($useridArr, explode(',', Group::GetGrouplistMembersAsCSV($groupIdsWithChapterDependenciesCSV, $chapterid, $event->val('channelid'), $event->val('invited_locations'), 'E', $event->val('use_and_chapter_connector'))));
                        }
                    }

                    // Store all invited in $useridArr
                    $useridArr = array_values(array_filter(array_unique($useridArr)));

                    // If listids are provided then we use the following logic
                    // If lists are at Admin Content level, then we will keep only the list data
                    // Else, we will keep the intersection of users in the list and the provided group/chapter/channel scope
                    if (!empty($event->val('listids'))) {
                        if ($event->isAdminContent()) {
                            // $useridArr is expected to be empty in this case; so fetch all zone members
                            $useridArr = explode(',', Group::GetGroupMembersAsCSV(0, 0, 0, 'E'));
                        }
                        $listUserids = DynamicList::GetUserIdsByListIds($event->val('listids'));
                        $useridArr = array_intersect($useridArr, $listUserids);
                    }
                }
            }

            // Now add back all the users from event joiners list
            if ($event->isSeriesEventHead()) {
                $data = self::DBGet("SELECT GROUP_CONCAT(DISTINCT(eventjoiners.userid)) AS rsvps FROM `eventjoiners` WHERE eventjoiners.`eventid` IN ({$eventIds}) AND eventjoiners.`userid` != 0");
            } else {
                $data = self::DBGet("SELECT GROUP_CONCAT(userid) AS rsvps FROM `eventjoiners` WHERE `eventid`={$this->eventid} AND `userid` != 0");
            }

            if (!empty($data[0]['rsvps'])) {
                $useridArr = array_unique(array_merge($useridArr, explode(',', $data[0]['rsvps'])));
            }


            // At this point $useridArr list contains all the invited users, whether explicitly or implicitly, so lets remove -1 from remind array
            if (($minusOneIndex = array_search('-1', $reminderTo)) !== false)
                unset($reminderTo[$minusOneIndex]);

            // Next only if we are reminding users who did not respond then, lets get a list of users who responded and
            // remove them from the invited list.
            if (in_array('0', $reminderTo)) {
                // Step 1: First find all who rsvpd
                if ($event->isSeriesEventHead()) {
                    $data = self::DBGet("SELECT GROUP_CONCAT(DISTINCT(eventjoiners.userid)) AS rsvps FROM `eventjoiners` WHERE eventjoiners.`eventid` IN ({$eventIds}) AND eventjoiners.`joinstatus`!=0 AND eventjoiners.`userid` != 0");
                } else {
                    $data = self::DBGet("SELECT GROUP_CONCAT(userid) AS rsvps FROM `eventjoiners` WHERE `eventid`={$this->eventid} AND `joinstatus`!=0 AND `userid` != 0");
                }

                // Step 2: Update list $useridArr to remove users who rsvp'ed
                if (!empty($data[0]['rsvps'])) {
                    $useridArr = array_values(array_diff($useridArr, explode(',', $data[0]['rsvps'])));
                }

                if (($zeroIndex = array_search('0', $reminderTo)) !== false)
                    unset($reminderTo[$zeroIndex]);
            }
        }

        // At this point option -1 and 0 are processed, lets process any other remaining options
        if (count($reminderTo)) { // All RSVPed
            $reminderTo = implode(',', $reminderTo);
            self::DBGet("SET SESSION group_concat_max_len = 2048000"); //Increase the session limit; default is 1000
            if ($event->isSeriesEventHead()) {
                $data = self::DBGet("SELECT GROUP_CONCAT(DISTINCT(eventjoiners.userid)) AS rsvps FROM `eventjoiners` WHERE eventjoiners.`eventid` IN ({$eventIds}) AND eventjoiners.`joinstatus` IN ({$reminderTo}) AND eventjoiners.`userid` != 0");
            } else {
                $data = self::DBGet("SELECT GROUP_CONCAT(userid) AS rsvps FROM `eventjoiners` WHERE `eventid`='{$this->eventid}' AND `joinstatus` IN ({$reminderTo}) AND `userid` != 0");
            }
            if (!empty($data[0]['rsvps'])) {
                $useridArr = array_unique(array_merge($useridArr, explode(',', $data[0]['rsvps'])));
            }
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
            $detail['USERIDS'] = $batchList;
            $copy->details = json_encode($detail);
            $copy->delay = $copy->delay + 1 + //VERY IMPORTANT TO USE THE ORIGINAL DELAY.
                ((int)($offset / $offset_size)) * $this->batch_stagger_seconds; // ADD STAGGER TO THE ORIGINAL DELAY
            $retVal = $copy->saveAsRemindType();
            if (!$retVal) {
                break;
            }
        }
        return $retVal;
    }
    
    protected function processAsCreateType()
    {
        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */

        $event = Event::GetEventForPublishing($this->eventid, $this->instance == 0);
        if (!($event && ($event->val('companyid') == $_COMPANY->id()) && $event->isActive())) {
            // Class comparison is just for a security check which should always be true.
            return;
        }

        // Publish to external integration points if integrations are on and the event is not private
        if (($this->instance === 0) && $_COMPANY->getAppCustomization()['integrations']['group']['enabled'] && !$event->isPrivateEvent()) {
            $publish_to_external_integrations = $this->options['external_integrations'] ?? array();

            if ($event->val('collaborating_groupids') && $event->val('groupid') == 0) { // Collaborating event
                $collaborating_groupids = explode(',', $event->val('collaborating_groupids'));
                foreach ($collaborating_groupids as $collaborating_groupid) {
                    $integrations = GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($collaborating_groupid, $event->val('chapterid'), $event->val('channelid'), 0, true);
                    $ev = $event;
                    $ev->fields['groupid'] = $collaborating_groupid;
                    $ev->fields['chapterid'] = 0;
                    $ev->fields['channelid'] = 0;
                    foreach ($integrations as $integration) {
                        if (in_array($integration->id(), $publish_to_external_integrations)) {
                            $integration->processCreateEvent($ev);
                        }
                    }
                }
            } else {
                $integrations = GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid'), 0, true);
                foreach ($integrations as $integration) {
                    if (in_array($integration->id(), $publish_to_external_integrations)) {
                        $integration->processCreateEvent($event);
                    }
                }
            }
        }

        if (($this->instance === 0) && ($this->options['send_emails'] && (!$event->val('publish_to_email') || $event->isSeriesEventHead()))) {
            // This is the first time we are processing publish to email so update the flag
            $event->updatePublishToEmail(1);
        }

        $event->postCreate();

        $group = null;
        if ($event->val('groupid') && ($group = Group::GetGroup($event->val('groupid'))) === null) {
            return; //no matching valid group found
        }

        $members = explode(',', $this->details);
        if (empty($this->details) || count($members) <= 0) {
            return;
        } //no users to notify

        list(
            $groupname,
            $from,
            $app_type,
            $reply_addr,
            $rsvp_addr,
            $group_logo
            ) = $this->calculateVariousGroupVariables($group);

        list(
            $from,
            $reply_addr,
            $subject,
            $send_ical,
            $venue,
            $address,
            $formated_when,
            $url,
            $description,
            $eventtitle,
            $event_header,
            $content_subheader,
            $content_subfooter,
            $include_web_conf
            ) = $this->calculateVariousEventVariables($event, $group, $from, $reply_addr);

        //$message = 'created a new event ' . self::mysqli_escape($eventtitle) . '  in ' . self::mysqli_escape($groupname);
        $msg = $groupname . ' - ' . htmlspecialchars_decode($eventtitle);

        if (!empty($this->options['invitation_message'])) {
            $content_subheader = '<div style="background-color:#80808026; padding:20px;">Note: ' . $this->options['invitation_message'] . '.</div><br>' . $content_subheader;
        }

        $subjectPrefix = '';
        if (!empty($this->options['subject_prefix'])) {
            $subjectPrefix = $this->options['subject_prefix'];
            $subject = $subjectPrefix . $subject;
        }

        $description = EmailHelper::OutlookFixes($description);

        $email_logger = null;
        $email_open_pixel = '';
        if ($_COMPANY->getAppCustomization()['event']['email_tracking']['enabled'] && $_ZONE->val('email_settings') >= 2) {
            $domain = $_COMPANY->getAppDomain($app_type);

            $label = ($subjectPrefix == 'Invitation:') ? 'Invite' : 'Initial';
            $label .= ($send_ical) ? ' *' : ''; // We add * as a hint to tell that open rates for this email may be incorrect
            if ($subjectPrefix == 'Invitation:') {
                try {
                    $jobCreatedOn = new DateTime($this->val('createdon') . ' UTC');
                    $label = trim($label) . ' ' . $jobCreatedOn->format('md.Hi');
                } catch (Exception $ex) {
                    Logger::Log("Exception creating label " . $ex->getMessage());
                }
            }

            $email_logger = EmailLog::GetOrCreateEmailLog($domain, EmailLog::EMAILLOG_SECTION_TYPES['event'], $event->id(), $event->val('version'), $label, $this->createdby);
            if ($_COMPANY->getAppCustomization()['event']['email_tracking']['track_urls']) {
                $description = $email_logger->updateHtmlToTrackUrlClicks($description);
            }
            $email_open_pixel = $email_logger->getEmailOpenPixelTemplate();
        }
        $eventVolunteerRequests = $event->getEventVolunteerRequests();
        $volunteerRequests = array();
        foreach ($eventVolunteerRequests as $key => $volunteer) { 
            $volunteerRequests[] = array('volunteerNeeds'=>$volunteer['volunteer_needed_count'],'volunteerSignedUp'=>$event->getVolunteerCountByType($volunteer['volunteertypeid']),'volunteerRole'=>htmlspecialchars($event->getVolunteerTypeValue($volunteer['volunteertypeid'])));
        }

        $email_content = EmailHelper::GetEmailTemplateForEvent($groupname, $group_logo, '', $from, $eventtitle, $content_subheader, $content_subfooter, $description, $url, 'Event Page', $formated_when, $event_header, $email_open_pixel,'#0077b5','#0077b5',$volunteerRequests, $event);

        for ($i = 0; $i < count($members); $i++) {
            $mid = $members[$i];

            if (empty($mid) || ($touser = User::GetUser((int)$mid)) === NULL || !$touser->isActive())
                continue;

            //$insertNoti = self::DBInsert("INSERT INTO `notifications`(`zoneid`,`section`, `userid`, `whodo`, `tableid`, `message`, `datetime`, `isread`) VALUES ('{$_ZONE->id()}', '3','" . $mid . "','" . $this->createdby . "','" . $this->eventid . "','" . $message . "',now(),'2')");
            // Push Notification Create New Event
            if ($touser->val('notification') == 1 && $_COMPANY->getAppCustomization()['mobileapp']['enabled']) {
                $users = self::DBGet("SELECT api_session_id, `userid`, `devicetype`, `devicetoken` FROM users_api_session WHERE `userid`='" . $mid . "' and devicetoken!=''");
                if (count($users) > 0) {
                    $badge = 1;
                    [$bearerToken, $firebaseProjectId] = $_COMPANY->getFirebaseBearerTokenAndProjectId();
                    for ($d = 0; $d < count($users); $d++) {
                        if ($event->val('event_series_id') == 0) {
                            sendCommonPushNotification($users[$d]['devicetoken'], 'New Event!', $msg, $badge, self::PUSH_NOTIFICATIONS_STATUS['EVENT'], $this->eventid, 1, $_ZONE->id(),$bearerToken,'v1',$firebaseProjectId);
                        } else if ($event->val('event_series_id') > 0) {
                            sendCommonPushNotification($users[$d]['devicetoken'], 'New Event Series!', $msg, $badge, self::PUSH_NOTIFICATIONS_STATUS['EVENT'], $this->eventid, 1, $_ZONE->id(),$bearerToken,'v1',$firebaseProjectId);
                        }

                    }
                }
            }


            if ($_ZONE->val('email_settings') >= 2) {
                $email_content_custom = $email_content; // Init it to the template
                $email = $touser->val('email');
                $enc_tracking_id = '';
                if ($email_logger) {
                    $enc_tracking_id = $email_logger->addOrGetRcptByUseridOrEmail($touser->id());
                    $email_content_custom = str_replace('___EMAILLOG_ENC_USER___', $enc_tracking_id, $email_content_custom);
                }

                $ical_str = ($send_ical) ?
                    $this->build_ical_str_for_create_or_update_event($event, $from, $rsvp_addr, $email, $url, $venue, $address, $include_web_conf) :
                    '';

                if (!$_COMPANY->emailSend2($from, $email, $subject, $email_content_custom, $app_type, $reply_addr, $ical_str)) {
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

        $event = Event::GetEvent($this->eventid);
        if (!($event && ($event->val('companyid') == $_COMPANY->id()) && $event->isActive())) {
            // Class comparison is just for a security check which should always be true.
            return;
        }

        if (($this->instance === 0) && $_COMPANY->getAppCustomization()['integrations']['group']['enabled'] && !$event->isPrivateEvent()) {
            $publish_to_external_integrations = $this->options['external_integrations'] ?? array();

            if ($event->val('collaborating_groupids') && $event->val('groupid') == 0) { // Collaborating event
                $collaborating_groupids = explode(',', $event->val('collaborating_groupids'));
                foreach ($collaborating_groupids as $collaborating_groupid) {
                    $ev = $event;
                    $ev->fields['groupid'] = $collaborating_groupid;
                    $ev->fields['chapterid'] = 0;
                    $ev->fields['channelid'] = 0;
                    $integrations = GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($collaborating_groupid, $event->val('chapterid'), $event->val('channelid'), 0, true);
                    foreach ($integrations as $integration) {

                        if (in_array($integration->id(), $publish_to_external_integrations)) {
                            $integration->processUpdateEvent($ev);
                        }
                    }
                }
            } else {
                $integrations = GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid'), 0, true);
                foreach ($integrations as $integration) {
                    if (in_array($integration->id(), $publish_to_external_integrations)) {
                        $integration->processUpdateEvent($event);
                    }
                }
            }
        }

        $event->postUpdate();

        $group = null;
        if ($event->val('groupid') && ($group = Group::GetGroup($event->val('groupid'))) === null) {
            return; //no matching valid group found
        }

        $members = explode(',', $this->details);
        if (empty($this->details) || count($members) <= 0) {
            return;
        } //no users to notify

        list(
            $groupname,
            $from,
            $app_type,
            $reply_addr,
            $rsvp_addr,
            $group_logo
            ) = $this->calculateVariousGroupVariables($group);

        list(
            $from,
            $reply_addr,
            $subject,
            $send_ical,
            $venue,
            $address,
            $formated_when,
            $url,
            $description,
            $eventtitle,
            $event_header,
            $content_subheader,
            $content_subfooter,
            $include_web_conf
            ) = $this->calculateVariousEventVariables($event, $group, $from, $reply_addr);

        // Headers can be different for events with max participation where user RSVP is in person yes or online yes, so lets calculate the headers for those two use cases.
        list(, , , , , , , , , , $event_header_limited_participation_inperson_yes, $content_subheader_inperson_yes,$content_subfooter_inperson_yes, $include_web_conf_inperson_yes) = $this->calculateVariousEventVariables($event, $group, $from, $reply_addr, Event::RSVP_TYPE['RSVP_INPERSON_YES']);
        list(, , , , , , , , , , $event_header_limited_participation_online_yes, $content_subheader_online_yes,$content_subfooter_online_yes, $include_web_conf_online_ye) = $this->calculateVariousEventVariables($event, $group, $from, $reply_addr, Event::RSVP_TYPE['RSVP_ONLINE_YES']);
        list(, , , , , , , , , , $event_header_limited_participation_inperson_wait, $content_subheader_inperson_wait,$content_subfooter_inperson_wait, $include_web_conf_inperson_wait) = $this->calculateVariousEventVariables($event, $group, $from, $reply_addr, Event::RSVP_TYPE['RSVP_INPERSON_WAIT']);
        list(, , , , , , , , , , $event_header_limited_participation_online_wait, $content_subheader_online_wait,$content_subfooter_online_wait, $include_web_conf_online_wait) = $this->calculateVariousEventVariables($event, $group, $from, $reply_addr, Event::RSVP_TYPE['RSVP_ONLINE_WAIT']);


        //$message = 'updated event ' . self::mysqli_escape($eventtitle) . '  in ' . self::mysqli_escape($groupname);
        $msg = $groupname . ' - ' . htmlspecialchars_decode($eventtitle);

        $subject = 'Updated: ' . $subject;

        $description = EmailHelper::OutlookFixes($description);

        $email_logger = null;
        $email_open_pixel = '';
        if ($_COMPANY->getAppCustomization()['event']['email_tracking']['enabled'] && $_ZONE->val('email_settings') >= 2) {
            $domain = $_COMPANY->getAppDomain($app_type);
            $label = ($send_ical) ? 'Update *' : 'Update'; // We add * as a hint to tell that open rates for this email may be incorrect
            $email_logger = EmailLog::GetOrCreateEmailLog($domain, EmailLog::EMAILLOG_SECTION_TYPES['event'], $event->id(), $event->val('version'), $label, $this->createdby);
            if ($_COMPANY->getAppCustomization()['event']['email_tracking']['track_urls']) {
                $description = $email_logger->updateHtmlToTrackUrlClicks($description);
            }
            $email_open_pixel = $email_logger->getEmailOpenPixelTemplate();
        }

        $email_content = EmailHelper::GetEmailTemplateForEvent($groupname, $group_logo, '', $from, $eventtitle, '##rsvpmessage##', $content_subfooter, $description, $url, 'Event Page', $formated_when, $event_header, $email_open_pixel, '#0077b5', '#0077b5', [], $event);
        $email_content_limited_participation_inperson_yes = EmailHelper::GetEmailTemplateForEvent($groupname, $group_logo, '', $from, $eventtitle, '##rsvpmessage##', $content_subfooter_inperson_yes, $description, $url, 'Event Page', $formated_when, $event_header_limited_participation_inperson_yes, $email_open_pixel, '#0077b5', '#0077b5', [], $event);
        $email_content_limited_participation_online_yes = EmailHelper::GetEmailTemplateForEvent($groupname, $group_logo, '', $from, $eventtitle, '##rsvpmessage##', $content_subfooter_online_yes, $description, $url, 'Event Page', $formated_when, $event_header_limited_participation_online_yes, $email_open_pixel, '#0077b5', '#0077b5', [], $event);
        $email_content_limited_participation_inperson_wait = EmailHelper::GetEmailTemplateForEvent($groupname, $group_logo, '', $from, $eventtitle, '##rsvpmessage##', $content_subfooter_inperson_wait, $description, $url, 'Event Page', $formated_when, $event_header_limited_participation_inperson_wait, $email_open_pixel, '#0077b5', '#0077b5', [], $event);
        $email_content_limited_participation_online_wait = EmailHelper::GetEmailTemplateForEvent($groupname, $group_logo, '', $from, $eventtitle, '##rsvpmessage##', $content_subfooter_online_wait, $description, $url, 'Event Page', $formated_when, $event_header_limited_participation_online_wait, $email_open_pixel, '#0077b5', '#0077b5', [], $event);

        for ($i = 0; $i < count($members); $i++) {
            $mid = $members[$i];

            if (empty($mid) || ($touser = User::GetUser((int)$mid)) === NULL || !$touser->isActive())
                continue;

            //$insertNoti = self::DBInsert("INSERT INTO `notifications`(`zoneid`,`section`, `userid`, `whodo`, `tableid`, `message`, `datetime`, `isread`) VALUES ('{$_ZONE->id()}','3','" . $mid . "','" . $this->createdby . "','" . $this->eventid . "','" . $message . "',now(),'2')");

            // Step: Push Notification Event update
            if ($touser->val('notification') == 1 && $_COMPANY->getAppCustomization()['mobileapp']['enabled']) {
                $users = self::DBGet("SELECT api_session_id, `userid`, `devicetype`, `devicetoken` FROM users_api_session WHERE `userid`='" . $mid . "' and devicetoken!=''");
                if (count($users) > 0) {
                    $badge = 1;
                    [$bearerToken, $firebaseProjectId] = $_COMPANY->getFirebaseBearerTokenAndProjectId();
                    for ($d = 0; $d < count($users); $d++) {
                        sendCommonPushNotification($users[$d]['devicetoken'], 'Event Updated!', $msg, $badge, self::PUSH_NOTIFICATIONS_STATUS['EVENT'], $this->eventid, 1, $_ZONE->id(),$bearerToken,'v1',$firebaseProjectId);
                    }
                }
            }

            if ($_ZONE->val('email_settings') >= 2) {
                $user_join_status = (int)User::GetUserEventJoinStatus($touser->id(), $this->eventid);
                $email = $touser->val('email');

                // Step: Build Email Body, we have to use RSVP specific body if RSVP is inperson yes or online yes.
                $email_content_custom = $email_content; // Init it to the template
                if ($user_join_status == Event::RSVP_TYPE['RSVP_INPERSON_YES']) {
                    $email_content_custom = $email_content_limited_participation_inperson_yes;
                } elseif ($user_join_status == Event::RSVP_TYPE['RSVP_ONLINE_YES']) {
                    $email_content_custom = $email_content_limited_participation_online_yes;
                } elseif ($user_join_status == Event::RSVP_TYPE['RSVP_INPERSON_WAIT']) {
                    $email_content_custom = $email_content_limited_participation_inperson_wait;
                } elseif ($user_join_status == Event::RSVP_TYPE['RSVP_ONLINE_WAIT']) {
                    $email_content_custom = $email_content_limited_participation_online_wait;
                }

                // Step: Determing if we need to send ical attachment.
                $send_ical_custom = $send_ical;
                if (in_array($user_join_status, array(Event::RSVP_TYPE['RSVP_YES'], Event::RSVP_TYPE['RSVP_MAYBE'], Event::RSVP_TYPE['RSVP_INPERSON_YES'], Event::RSVP_TYPE['RSVP_INPERSON_WAIT'], Event::RSVP_TYPE['RSVP_ONLINE_YES'], Event::RSVP_TYPE['RSVP_ONLINE_WAIT']))) {  // If the users join status is in array then get the custom message
                    $send_ical_custom = 1;
                }

                // Step: Generate tracking id that is unique to each recipient email and embed it into email content.
                $enc_tracking_id = '';
                if ($email_logger) {
                    $enc_tracking_id = $email_logger->addOrGetRcptByUseridOrEmail($touser->id());
                    $email_content_custom = str_replace('___EMAILLOG_ENC_USER___', $enc_tracking_id, $email_content_custom);
                }

                // Step: Update RSVP Message for the receipient to inform them of their current RSVP status.
                $rsvp_message = '';
                if ($user_join_status && in_array($user_join_status, array(1, 2, 3, 11, 12, 21, 22))) {
                    $rsvp_message = $this->convertRsvpStatusToString($user_join_status);
                    $rsvp_message = '<div style="background-color:#80808026; padding:20px;">' . $rsvp_message . '</div>';
                } else {
                    $rsvp_message = $content_subheader;
                }
                $email_content_custom = str_replace('##rsvpmessage##', $rsvp_message, $email_content_custom);

                // Step: Check if we need to incude web conference address in the iCAL
                $include_web_conf = $include_web_conf && in_array($user_join_status, array(Event::RSVP_TYPE['RSVP_YES'], Event::RSVP_TYPE['RSVP_MAYBE'], Event::RSVP_TYPE['RSVP_ONLINE_YES']));

                // Step: Build icalendar string.
                $ical_str = ($send_ical_custom) ?
                    $this->build_ical_str_for_create_or_update_event($event, $from, $rsvp_addr, $email, $url, $venue, $address, $include_web_conf) :
                    '';

                // Step: Send the email
                if (!$_COMPANY->emailSend2($from, $email, $subject, $email_content_custom, $app_type, $reply_addr, $ical_str)) {
                    // If email sending failed, reset the email send record to nullify the send timestamp.
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

        $event = Event::GetEvent($this->eventid);
        if (!($event && ($event->val('companyid') == $_COMPANY->id()) && $event->isInactive())) {
            // Class comparison is just for a security check which should always be true.
            return;
        }

        if (($this->instance === 0) && $_COMPANY->getAppCustomization()['integrations']['group']['enabled']) {

            if ($event->val('collaborating_groupids') && $event->val('groupid') == 0) { // Collaborating event
                $collaborating_groupids = explode(',', $event->val('collaborating_groupids'));
                foreach ($collaborating_groupids as $collaborating_groupid) {
                    $ev = $event;
                    $ev->fields['groupid'] = $collaborating_groupid;
                    $ev->fields['chapterid'] = 0;
                    $ev->fields['channelid'] = 0;
                    $integrations = GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($collaborating_groupid, $event->val('chapterid'), $event->val('channelid'), 0, true);
                    foreach ($integrations as $integration) {
                        $integration->processDeleteEvent($ev);
                    }
                }
            } else {
                $integrations = GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid'), 0, true);
                foreach ($integrations as $integration) {
                    $integration->processDeleteEvent($event);
                }
            }

            Event::PostDelete($this->eventid);
        }

        $group = null;
        if ($event->val('groupid') && ($group = Group::GetGroup($event->val('groupid'))) === null) {
            return; //no matching valid group found
        }

        $members = explode(',', $this->details);
        if (empty($this->details) || count($members) <= 0) {
            return;
        } //no users to notify

        list(
            $groupname,
            $from,
            $app_type,
            $reply_addr,
            $rsvp_addr,
            $group_logo
            ) = $this->calculateVariousGroupVariables($group);

        list(
            $from,
            $reply_addr,
            $subject,
            $send_ical,
            $venue,
            $address,
            $formated_when,
            $url,
            $description,
            $eventtitle,
            $event_header,
            $content_subheader,
            $content_subfooter,
            $include_web_conf
            ) = $this->calculateVariousEventVariables($event, $group, $from, $reply_addr);

        //$message = 'canceled event ' . self::mysqli_escape($eventtitle) . '  in ' . self::mysqli_escape($groupname);
        $msg = $groupname . ' - ' . htmlspecialchars_decode($eventtitle);

        $event_header = '<b>Event canceled</b><br/><b>Event Contact:</b>' . htmlspecialchars($event->val('event_contact'));
        $content_subheader = $event->val('cancel_reason') ?? '';
        $content_subfooter = '';

        $subject = 'Canceled: ' . $subject;

        $description = '';//EmailHelper::OutlookFixes($description);
        $email_content = EmailHelper::GetEmailTemplateForEvent($groupname, $group_logo, '', $from, $eventtitle, $content_subheader, $content_subfooter, $description, '', 'Event Canceled', $formated_when, $event_header, '', '#0077b5', '#0077b5', [], $event);

        for ($i = 0; $i < count($members); $i++) {
            $mid = $members[$i];
            if (empty($mid) || ($touser = User::GetUser((int)$mid)) === NULL || !$touser->isActive())
                continue;

            //$insertNoti = self::DBInsert("INSERT INTO `notifications`(`zoneid`,`section`, `userid`, `whodo`, `tableid`, `message`, `datetime`, `isread`) VALUES ('{$_ZONE->id()}','3','" . $mid . "','" . $this->createdby . "','','" . $message . "',now(),'2')");

            // Push Notification Event cancel
            if ($touser->val('notification') == 1 && $_COMPANY->getAppCustomization()['mobileapp']['enabled']) {
                $users = self::DBGet("SELECT api_session_id, `userid`, `devicetype`, `devicetoken` FROM users_api_session WHERE `userid`='" . $mid . "' and devicetoken!=''");
                if (count($users) > 0) {
                    $badge = 1;
                    [$bearerToken, $firebaseProjectId] = $_COMPANY->getFirebaseBearerTokenAndProjectId();
                    for ($d = 0; $d < count($users); $d++) {
                        sendCommonPushNotification($users[$d]['devicetoken'], 'Event Canceled!', $msg, $badge, self::PUSH_NOTIFICATIONS_STATUS['EVENT_CANCELED'], $this->eventid, 1, $_ZONE->id(),$bearerToken,'v1',$firebaseProjectId);
                    }
                }
            }

            if ($_ZONE->val('email_settings') >= 2) {
                $email = $touser->val('email');
                $user_join_status = User::GetUserEventJoinStatus($mid, $this->eventid);
                $ical_str = ($send_ical || ($user_join_status != 0 && $user_join_status != 3)) ?
                    $this->build_ical_str_for_cancel_event($event, $from, $rsvp_addr, $email, $user_join_status, $venue, $address) :
                    '';

                $_COMPANY->emailSend2($from, $email, $subject, $email_content, $app_type, $reply_addr, $ical_str);
            }
        }

        if ($this->options['delete_after_cancel']) {
            // At this point fully delete the event
            $event->deleteIt();
        }
    }

    public function processAsRemindType()
    {
        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */

        $event = Event::GetEvent($this->eventid);
        if (!($event && ($event->val('companyid') == $_COMPANY->id()) && $event->isActive())) {
            // Class comparison is just for a security check which should always be true.
            return;
        }

        $group = null;
        if ($event->val('groupid') && ($group = Group::GetGroup($event->val('groupid'))) === null) {
            return; //no matching valid group found
        }

        $detail = json_decode($this->details, 1);
        $members = explode(',', $detail['USERIDS']);
        if (count($members) <= 0) {
            return;
        } //no users to notify

        list(
            $groupname,
            $from,
            $app_type,
            $reply_addr,
            $rsvp_addr,
            $group_logo
            ) = $this->calculateVariousGroupVariables($group);

        list(
            $from,
            $reply_addr,
            $subject,
            $send_ical,
            $venue,
            $address,
            $formated_when,
            $url,
            $description,
            $eventtitle,
            $event_header,
            $content_subheader,
            $content_subfooter,
            $include_web_conf,
            ) = $this->calculateVariousEventVariables($event, $group, $from, $reply_addr);

        // Headers can be different for events with max participation where user RSVP is in person yes or online yes, so lets calculate the headers for those two use cases.
        list(, , , , , , , , , , $event_header_limited_participation_inperson_yes, $content_subheader_inperson_yes,$content_subfooter_inperson_yes, $include_web_conf_inperson_year) = $this->calculateVariousEventVariables($event, $group, $from, $reply_addr, Event::RSVP_TYPE['RSVP_INPERSON_YES']);
        list(, , , , , , , , , , $event_header_limited_participation_online_yes, $content_subheader_online_yes,$content_subfooter_online_yes, $include_web_conf_online_yes) = $this->calculateVariousEventVariables($event, $group, $from, $reply_addr, Event::RSVP_TYPE['RSVP_ONLINE_YES']);
        list(, , , , , , , , , , $event_header_limited_participation_inperson_wait, $content_subheader_inperson_wait,$content_subfooter_inperson_wait, $inclue_web_conf_inperson_wait) = $this->calculateVariousEventVariables($event, $group, $from, $reply_addr, Event::RSVP_TYPE['RSVP_INPERSON_WAIT']);
        list(, , , , , , , , , , $event_header_limited_participation_online_wait, $content_subheader_online_wait,$content_subfooter_online_wait, $include_web_conf_online_wait) = $this->calculateVariousEventVariables($event, $group, $from, $reply_addr, Event::RSVP_TYPE['RSVP_ONLINE_WAIT']);


        $subject = html_entity_decode($detail['SUBJECT']);
        $message = $detail['MESSAGE'];
        $includeEventDetails = intval($detail['INCLUDE_EVENT_DETAILS']);

        $content_subheader = EmailHelper::OutlookFixes($message) . '<br>##rsvpmessage##';
        if (!$includeEventDetails) {
            $description = '';
            //$content_subfooter = '';
        }

        $email_logger = null;
        $email_open_pixel = '';
        if ($_COMPANY->getAppCustomization()['event']['email_tracking']['enabled'] && $_ZONE->val('email_settings') >= 2) {
            $domain = $_COMPANY->getAppDomain($app_type);
            $label = $detail['LABEL'] ?? 'Reminder: ';
            //if (empty($label)) {
            //    $label = 'Reminder: ';
            //} else {
            //    $label = ' '; //substr($label, 0, 12); // commenting as the unicode characters cause a lot of issues.
            //}

            try {
                $jobCreatedOn = new DateTime($this->val('createdon') . ' UTC');
                $label = trim($label) . ' ' . $jobCreatedOn->format('md.Hi');
            } catch (Exception $ex) {
                Logger::Log("Exception creating label " . $ex->getMessage());
            }

            //$label .= ($send_ical) ? ' *' : ''; // Intentionally removed as $send_ical is not used;
            $email_logger = EmailLog::GetOrCreateEmailLog($domain, EmailLog::EMAILLOG_SECTION_TYPES['event'], $event->id(), $event->val('version'), $label, $this->createdby);
            if ($_COMPANY->getAppCustomization()['event']['email_tracking']['track_urls']) {
                $content_subheader = $email_logger->updateHtmlToTrackUrlClicks($content_subheader);
                $description = $email_logger->updateHtmlToTrackUrlClicks($description);
            }
            $email_open_pixel = $email_logger->getEmailOpenPixelTemplate();
        }

        $email_content = EmailHelper::GetEmailTemplateForEvent($groupname, $group_logo, '', $from, $eventtitle, $content_subheader, $content_subfooter, $description, $url, 'Event Page', $formated_when, $event_header, $email_open_pixel, '#0077b5', '#0077b5', [], $event);
        $email_content_limited_participation_inperson_yes = EmailHelper::GetEmailTemplateForEvent($groupname, $group_logo, '', $from, $eventtitle, $content_subheader, $content_subfooter_inperson_yes, $description, $url, 'Event Page', $formated_when, $event_header_limited_participation_inperson_yes, $email_open_pixel, '#0077b5', '#0077b5', [], $event);
        $email_content_limited_participation_online_yes = EmailHelper::GetEmailTemplateForEvent($groupname, $group_logo, '', $from, $eventtitle, $content_subheader, $content_subfooter_online_yes, $description, $url, 'Event Page', $formated_when, $event_header_limited_participation_online_yes, $email_open_pixel, '#0077b5', '#0077b5', [], $event);
        $email_content_limited_participation_inperson_wait = EmailHelper::GetEmailTemplateForEvent($groupname, $group_logo, '', $from, $eventtitle, $content_subheader, $content_subfooter_inperson_wait, $description, $url, 'Event Page', $formated_when, $event_header_limited_participation_inperson_wait, $email_open_pixel, '#0077b5', '#0077b5', [], $event);
        $email_content_limited_participation_online_wait = EmailHelper::GetEmailTemplateForEvent($groupname, $group_logo, '', $from, $eventtitle, $content_subheader, $content_subfooter_online_wait, $description, $url, 'Event Page', $formated_when, $event_header_limited_participation_online_wait, $email_open_pixel, '#0077b5', '#0077b5', [], $event);

        for ($i = 0; $i < count($members); $i++) {
            $mid = $members[$i];
            if (empty($mid) || ($touser = User::GetUser((int)$mid)) === NULL || !$touser->isActive())
                continue;

            if ($_ZONE->val('email_settings') >= 2) {
                $email = $touser->val('email');
                $user_join_status = (int)User::GetUserEventJoinStatus($touser->id(), $this->eventid);

                // Step: Build Email Body, we have to use RSVP specific body if RSVP is inperson yes or online yes.
                $email_content_custom = $email_content; // Init it to the template
                if ($user_join_status == Event::RSVP_TYPE['RSVP_INPERSON_YES']) {
                    $email_content_custom = $email_content_limited_participation_inperson_yes;
                } elseif ($user_join_status == Event::RSVP_TYPE['RSVP_ONLINE_YES']) {
                    $email_content_custom = $email_content_limited_participation_online_yes;
                } elseif ($user_join_status == Event::RSVP_TYPE['RSVP_INPERSON_WAIT']) {
                    $email_content_custom = $email_content_limited_participation_inperson_wait;
                } elseif ($user_join_status == Event::RSVP_TYPE['RSVP_ONLINE_WAIT']) {
                    $email_content_custom = $email_content_limited_participation_online_wait;
                }

                $enc_tracking_id = '';

                if ($email_logger) {
                    $enc_tracking_id = $email_logger->addOrGetRcptByUseridOrEmail($touser->id());
                    $email_content_custom = str_replace('___EMAILLOG_ENC_USER___', $enc_tracking_id, $email_content_custom);
                }

                if ($event->isSeriesEventHead()) {
                    $rsvp_message = '';
                } else {
                    $rsvp_message = $this->convertRsvpStatusToString($user_join_status);
                    $rsvp_message = '<div style="background-color:#80808026; padding:20px;">' . $rsvp_message . ' To make changes go to the Event Page</div>';
                }
                $email_content_custom = str_replace('##rsvpmessage##', $rsvp_message, $email_content_custom);

                if (!$_COMPANY->emailSend2($from, $email, $subject, $email_content_custom, $app_type, $reply_addr)) {
                    if ($email_logger) {
                        $email_logger->resetRcptSentTimestampToNull($enc_tracking_id);
                    }
                }
            }
        }
    }

    public function sendRsvp(int $userid, int $rsvp)
    {
        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */

        $event = Event::GetEvent($this->eventid);
        if (!($event && ($event->val('companyid') == $_COMPANY->id()) && $event->isActive())) {
            // Class comparison is just for a security check which should always be true.
            return 0;
        }

        $group = null;
        if ($event->val('groupid') && ($group = Group::GetGroup($event->val('groupid'))) === null) {
            return; //no matching valid group found
        }

        list(
            $groupname,
            $from,
            $app_type,
            $reply_addr,
            $rsvp_addr,
            $group_logo
            ) = $this->calculateVariousGroupVariables($group);

        list(
            $from,
            $reply_addr,
            $subject,
            $send_ical,
            $venue,
            $address,
            $formated_when,
            $url,
            $description,
            $eventtitle,
            $event_header,
            $content_subheader,
            $content_subfooter,
            $include_web_conf
            ) = $this->calculateVariousEventVariables($event, $group, $from, $reply_addr, $rsvp);

        $touser = User::GetUser($userid);
        if ($_ZONE->val('email_settings') >= 1 && $touser) {
            $email = $touser->val('email');
            $useriname = $touser->getFullName();

            if ($rsvp == Event::RSVP_TYPE['RSVP_YES']) {
                $rsvp_message = 'You RSVPed <b>YES</b> for this event!';
                $transp = $event->val('calendar_blocks') ? 'OPAQUE' : 'TRANSPARENT';
                $partstat = 'ACCEPTED';
                $status = 'CONFIRMED';
            } elseif ($rsvp == Event::RSVP_TYPE['RSVP_MAYBE']) {
                $rsvp_message = 'You RSVPed <b>MAYBE</b> for this event!';
                $transp = 'TRANSPARENT';
                $partstat = 'TENTATIVE';
                $status = 'CONFIRMED';
            } elseif ($rsvp == Event::RSVP_TYPE['RSVP_INPERSON_YES']) {
                $rsvp_message = 'You RSVPed <b>ATTEND IN PERSON</b> for this event. Your seat is confirmed!';
                $transp = $event->val('calendar_blocks') ? 'OPAQUE' : 'TRANSPARENT';
                $partstat = 'ACCEPTED';
                $status = 'CONFIRMED';
            } elseif ($rsvp == Event::RSVP_TYPE['RSVP_ONLINE_YES']) {
                $rsvp_message = 'You RSVPed <b>ATTEND ONLINE</b> for this event. Your seat is confirmed!';
                $transp = $event->val('calendar_blocks') ? 'OPAQUE' : 'TRANSPARENT';
                $partstat = 'ACCEPTED';
                $status = 'CONFIRMED';
            } elseif ($rsvp == Event::RSVP_TYPE['RSVP_INPERSON_WAIT']) {
                $rsvp_message = 'You RSVPed <b>ADD TO WAITLIST (In Person)</b> for this event! We will automatically process your request and confirm your seat once a spot opens up.';
                $transp = 'TRANSPARENT';
                $partstat = 'TENTATIVE';
                $status = 'CONFIRMED';
            } elseif ($rsvp == Event::RSVP_TYPE['RSVP_ONLINE_WAIT']) {
                $rsvp_message = 'You RSVPed <b>ADD TO WAITLIST (Online)</b> for this event! We will automatically process your request and confirm your seat once a spot opens up.';
                $transp = 'TRANSPARENT';
                $partstat = 'TENTATIVE';
                $status = 'CONFIRMED';
            } elseif ($rsvp == Event::RSVP_TYPE['RSVP_INPERSON_WAIT_CANCEL']) {
                $rsvp_message = 'Your RSVP for this event has been <b>CANCELLED</b> by the event administrator';
                $transp = 'TRANSPARENT';
                $partstat = 'DECLINED';
                $status = 'CANCELLED';
            } elseif ($rsvp == Event::RSVP_TYPE['RSVP_ONLINE_WAIT_CANCEL']) {
                $rsvp_message = 'Your RSVP for this event has been <b>CANCELLED</b> by the event administrator';
                $transp = 'TRANSPARENT';
                $partstat = 'DECLINED';
                $status = 'CANCELLED';
            } else { //$rsvp === 3, for declines
                $rsvp_message = 'You RSVPed <b>DECLINE</b> for this event!';
                $transp = 'TRANSPARENT';
                $partstat = 'DECLINED';
                $status = 'CANCELLED';
            }

            $content_subheader = '<div style="margin-top:10px;margin-bottom:10px; background-color:#80808026; padding:20px;">' . $rsvp_message . '</div>';
            //$content_subfooter = '';

            $subject = 'RSVP Confirmation: ' . $subject;

            $description = EmailHelper::OutlookFixes($description);
            $email_content = EmailHelper::GetEmailTemplateForEvent($groupname, $group_logo, '', $from, $eventtitle, $content_subheader, $content_subfooter, $description, $url, 'Event Page', $formated_when, $event_header, '', '#0077b5', '#0077b5', [], $event);

            $include_web_conf = $include_web_conf && in_array($rsvp, array(Event::RSVP_TYPE['RSVP_YES'], Event::RSVP_TYPE['RSVP_MAYBE'], Event::RSVP_TYPE['RSVP_ONLINE_YES']));

            $ical_str = $this->build_ical_str_for_rsvp_confirmation($event, $from, $rsvp_addr, $email, $url, $venue, $address, $partstat, $rsvp, $status, $transp, $include_web_conf);

            //$emesg = str_replace('#messagehere#', $emesg, $template);
            return ($_COMPANY->emailSend2($from, $email, $subject, $email_content, $app_type, $reply_addr, $ical_str,array(),'',$event->val('zoneid')));
        }
        return 0;
    }

    public function inviteByEmails($emails, bool $disableActiveCheck = false, string $inviteNote = '', string $subjectPrefix = '', bool $sendIcal = true)
    {

        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */

        $event = Event::GetEvent($this->eventid);
        if (!($event && ($event->val('companyid') == $_COMPANY->id()) && ($disableActiveCheck || $event->isActive()))) {
            // Class comparison is just for a security check which should always be true.
            return;
        }

        $group = null;
        if ($event->val('groupid') && ($group = Group::GetGroup($event->val('groupid'))) === null) {
            return; //no matching valid group found
        }

        $members = explode(',', $emails);
        if (count($members) <= 0) {
            return;
        } //no users to notify

        list(
            $groupname,
            $from,
            $app_type,
            $reply_addr,
            $rsvp_addr,
            $group_logo
            ) = $this->calculateVariousGroupVariables($group);

        list(
            $from,
            $reply_addr,
            $subject,
            $send_ical,
            $venue,
            $address,
            $formated_when,
            $url,
            $description,
            $eventtitle,
            $event_header,
            $content_subheader,
            $content_subfooter,
            $include_web_conf
            ) = $this->calculateVariousEventVariables($event, $group, $from, $reply_addr);

        $who = User::GetUser($this->createdby);
        $message = 'invited you to a event ' . self::mysqli_escape('') . '  in ' . self::mysqli_escape($groupname);
        $msg = $who->getFullName() . ' invited you to a event ' . htmlspecialchars_decode($eventtitle) . ' in ' . $groupname;

        $subject = trim($subjectPrefix) . ' ' . html_entity_decode($eventtitle);

        if (!empty($inviteNote)) {
            $content_subheader = '<div style="margin-top:10px;margin-bottom:10px; background-color:#80808026; padding:20px;"><b>Note:&nbsp;</b>' . stripcslashes($inviteNote) . '</div><br>' . $content_subheader;
        }

        $description = EmailHelper::OutlookFixes($description);

        $email_logger = null;
        $email_open_pixel = '';
        if ($_COMPANY->getAppCustomization()['event']['email_tracking']['enabled'] &&
            $_ZONE->val('email_settings') >= 2 &&
            stripos($subjectPrefix, 'Review:') !== 0
        ) {
            $domain = $_COMPANY->getAppDomain($app_type);
            $label = ($subjectPrefix == 'Review: ') ? 'Review' : 'Share'; // Even though we will not have Review here but this check is just for safety
            $label .= ($send_ical) ? ' *' : ''; // We add * as a hint to tell that open rates for this email may be incorrect
            $email_logger = EmailLog::GetOrCreateEmailLog($domain, EmailLog::EMAILLOG_SECTION_TYPES['event'], $event->id(), $event->val('version'), $label, $this->createdby);
            if ($_COMPANY->getAppCustomization()['event']['email_tracking']['track_urls']) {
                $description = $email_logger->updateHtmlToTrackUrlClicks($description);
            }
            $email_open_pixel = $email_logger->getEmailOpenPixelTemplate();
        }

        $email_content = EmailHelper::GetEmailTemplateForEvent($groupname, $group_logo, '', $from, $eventtitle, $content_subheader, $content_subfooter, $description, $url, 'Event Page', $formated_when, $event_header, $email_open_pixel,'#0077b5', '#0077b5', [], $event);

        for ($i = 0; $i < count($members); $i++) {
            if ($_ZONE->val('email_settings') >= 2) {
                $email_content_custom = $email_content; // Init it to the template
                $email = $members[$i];
                $enc_tracking_id = '';

                if ($email_logger) {
                    $touser = User::GetUserByEmail($email);
                    $enc_tracking_id = $email_logger->addOrGetRcptByUseridOrEmail(($touser ? $touser->id() : 0), $email);
                    $email_content_custom = str_replace('___EMAILLOG_ENC_USER___', $enc_tracking_id, $email_content_custom);
                }

                $ical_str = ($send_ical && $sendIcal) ?
                    $this->build_ical_str_for_create_or_update_event($event, $from, $rsvp_addr, $email, $url, $venue, $address, $include_web_conf) :
                    '';

                if (!$_COMPANY->emailSend2($from, $email, $subject, $email_content_custom, $app_type, $reply_addr, $ical_str)) {
                    if ($email_logger) {
                        $email_logger->resetRcptSentTimestampToNull($enc_tracking_id);
                    }
                }
            }
        }
    }

    public function sendForReview(string $toList, string $reviewNote, string $subjectPrefix = 'Review: ')
    {
        $this->inviteByEmails($toList, true, $reviewNote, $subjectPrefix, true);
    }

    public function cancelAllPendingJobs()
    {
        $delete_jobid = "EVT_{$this->cid}_{$this->groupid}_{$this->eventid}_";
        return self::DBUpdate("UPDATE jobs SET status=100, processedby='CANCELLED', processedon=now() WHERE companyid='{$this->cid}' AND (jobid like '{$delete_jobid}%' AND status=0)");
    }

    public function cancelAsRemindType()
    {
        $cancelJob = "EVT_{$this->cid}_{$this->groupid}_{$this->eventid}_";
        $jobType = self::TYPE_EVENT;
        $jobSubType = self::SUBTYPE_REMIND;
        return self::DBUpdate("UPDATE jobs SET status=100, processedby='CANCELLED', processedon=now() WHERE companyid='{$this->cid}' AND (jobid like '{$cancelJob}%' AND status=0 AND jobtype={$jobType} AND jobsubtype={$jobSubType})");
    }


    /**
     * @param Event $event ... pass event by reference for caching purposes
     * @param string $from
     * @param string $rsvp_addr
     * @param $email
     * @param string $url
     * @param string $venue
     * @param string $address
     * @param bool $include_web_conf
     * @return string
     * @throws Exception
     */
    protected function build_ical_str_for_create_or_update_event(Event &$event, string $from, string $rsvp_addr, $email, string $url, string $venue, string $address, bool $include_web_conf): string
    {
        global $_COMPANY;
        if (!$event) return '';

        if ($event->val('teamid') && $_COMPANY->getAppCustomization()['teams']['team_events']['detailed_ics']) {
                return CalendarICS::GenerateIcsFileForEvent($event,'REQUEST', $email, 0, $include_web_conf);
        }

        $tz_utc = new DateTimeZone('UTC');
        $start = new DateTime($event->val('start'), $tz_utc);
        $end = new DateTime($event->val('end'), $tz_utc);
        $description = $event->val('event_description');
        $eventtitle = $event->val('eventtitle');
        $venue = empty($address) ? $venue : "{$venue} ({$address})";
        $web_conf_url = $event->getWebConferenceLink();
        $web_conf_details = $event->val('web_conference_detail');
        $event_contact = $event->val('event_contact');
        $transp = $event->val('calendar_blocks') ? 'OPAQUE' : 'TRANSPARENT';

        $additional_location_details = '';
        if (!empty($venue)) {
            // Add the following additional details to the description
            if (!empty($event->val('venue_room'))) {
                $room = preg_replace('/\s\s+/', ' ', $event->val('venue_room'));
                $additional_location_details .= "Room: {$room} \\n";
            }
            if (!empty($event->val('venue_info'))) {
                $info = preg_replace('/\s\s+/', ' ', $event->val('venue_info'));
                $additional_location_details .= "Additional Information: {$info} \\n";
            }
            if (!empty($additional_location_details)) {
                $additional_location_details = "-::~:~::~:~:~:~:~:~::~:~::-\\n" . $additional_location_details;
            }
        }

        $description = preg_replace('/\s\s+/', ' ', $description);

        $web_conf_info = "-::~:~::~:~:~:~:~:~::~:~::-\\n";
        if ($event->isPrivateEvent()) {
            $web_conf_info .= self::PRIVATE_EVENT_MESSAGE . "\\n";
            $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\\n";
        }
        if (($event->val('add_photo_disclaimer') && $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['enabled'] && $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['show_on_top_in_emails'])) {
            $web_conf_info .= $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['disclaimer'] . "\\n";
            $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\\n";
        }
        if ($include_web_conf && !empty($web_conf_url)) {
            $web_conf_details = preg_replace('/\s\s+/', ' ', $web_conf_details);

            $web_conf_info .= "Join the event (Web conference link): {$web_conf_url} \\n";
            $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\\n";
            $web_conf_info .= $event->val('web_conference_sp') . " Details: {$web_conf_details} \\n";
            $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\\n";
        }
        $web_conf_info .= "Event Page: {$url} \\n";
        $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\\n";
        $web_conf_info .= "Event Contact: {$event_contact} \\n";
        $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\\n";
        if (($event->val('add_photo_disclaimer') && $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['enabled'] && !$_COMPANY->getAppCustomization()['event']['photo_disclaimer']['show_on_top_in_emails'])) {
            $web_conf_info .= $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['disclaimer'] . "\\n";
            $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\\n";
        }
        $web_conf_info .= "   \\n";

        return
            "BEGIN:VCALENDAR\r\n" .
            "PRODID:-//Teleskope LLC//Affinities Calendar v1.18.1\r\n" .
            "VERSION:2.0\r\n" .
            "CALSCALE:GREGORIAN\r\n" .
            "METHOD:REQUEST\r\n" .
            "BEGIN:VEVENT\r\n" .
            'DTSTART:' . $start->format('Ymd\THi00\Z') . "\r\n" .
            'DTEND:' . $end->format('Ymd\THi00\Z') . "\r\n" .
            'DTSTAMP:' . date('Ymd\THis\Z') . "\r\n" .
            self::ics_line_split('ORGANIZER;CN='.$this->calQuote($from).':MAILTO:' . $rsvp_addr) . "\r\n" .
            'UID:' . $event->getEventUid() . "\r\n" .
            self::ics_line_split("ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=Member:MAILTO:{$email}") . "\r\n" .
            self::ics_line_split($this->calQuote('DESCRIPTION:' . $additional_location_details. $web_conf_info . html_entity_decode($description))) . "\r\n" .
            self::ics_line_split($this->calQuote('LOCATION:' . html_entity_decode($venue))) . "\r\n" .
            'SEQUENCE:' . time() . "\r\n" .
            "STATUS:CONFIRMED\r\n" .
            self::ics_line_split($this->calQuote('SUMMARY:' . html_entity_decode($eventtitle))) . "\r\n" .
            ($event->isPrivateEvent() ? "CLASS:PRIVATE\r\n" : '') .
            "TRANSP:{$transp}\r\n" .
            "BEGIN:VALARM\r\n" .
            "DESCRIPTION:REMINDER\r\n" .
            "TRIGGER;RELATED=START:-PT15M\r\n" .
            "ACTION:DISPLAY\r\n" .
            "END:VALARM\r\n" .
            "END:VEVENT\r\n" .
            "END:VCALENDAR\r\n";
    }

    /**
     * @param Event $event ... pass event by reference for caching purposes
     * @param string $from
     * @param string $rsvp_addr
     * @param string $email
     * @param int $rsvp
     * @param string $venue
     * @param string $address
     * @return string
     * @throws Exception
     */
    protected function build_ical_str_for_cancel_event(Event &$event, string $from, string $rsvp_addr, string $email, int $rsvp, string $venue, string $address): string
    {
        global $_COMPANY;
        if (!$event) return '';

        if ($event->val('teamid') && $_COMPANY->getAppCustomization()['teams']['team_events']['detailed_ics']) {
            return CalendarICS::GenerateIcsFileForEvent($event,'CANCEL', $email, $rsvp);
        }

        $tz_utc = new DateTimeZone('UTC');
        $start = new DateTime($event->val('start'), $tz_utc);
        $end = new DateTime($event->val('end'), $tz_utc);
        $eventtitle = $event->val('eventtitle');
        $venue = empty($address) ? $venue : "{$venue} ({$address})";

        return
            "BEGIN:VCALENDAR\r\n" .
            "PRODID:-//Teleskope LLC//Affinities Calendar v1.18.1\r\n" .
            "VERSION:2.0\r\n" .
            "CALSCALE:GREGORIAN\r\n" .
            "METHOD:CANCEL\r\n" .
            "BEGIN:VEVENT\r\n" .
            'DTSTART:' . $start->format('Ymd\THi00\Z') . "\r\n" .
            'DTEND:' . $end->format('Ymd\THi00\Z') . "\r\n" .
            'DTSTAMP:' . date('Ymd\THis\Z') . "\r\n" .
            self::ics_line_split('ORGANIZER;CN='.$this->calQuote($from).':MAILTO:' . $rsvp_addr) . "\r\n" .
            'UID:' . $event->getEventUid() . "\r\n" .
            self::ics_line_split("ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;CN=Member:mailto:{$email}") . "\r\n" .
            self::ics_line_split('DESCRIPTION: CANCELLED ' . $this->calQuote(html_entity_decode($eventtitle))) . "\r\n" .
            self::ics_line_split('LOCATION:' . $this->calQuote(html_entity_decode($venue))) . "\r\n" .
            'SEQUENCE:' . time() . "\r\n" .
            "STATUS:CANCELLED\r\n" .
            self::ics_line_split('SUMMARY:' . $this->calQuote(html_entity_decode($eventtitle))) . "\r\n" .
            "TRANSP:TRANSPARENT\r\n" .
            "END:VEVENT\r\n" .
            "END:VCALENDAR\r\n";
    }

    /**
     * @param Event $event ... pass event by reference for caching purposes
     * @param string $from
     * @param string $rsvp_addr
     * @param string $email
     * @param string $url
     * @param string $venue
     * @param string $address
     * @param string $partstat
     * @param int $rsvp
     * @param string $status
     * @param string $transp
     * @param bool $include_web_conf
     * @return string
     * @throws Exception
     */
    protected function build_ical_str_for_rsvp_confirmation(Event &$event, string $from, string $rsvp_addr, string $email, string $url, string $venue, string $address, string $partstat, int $rsvp, string $status, string $transp, bool $include_web_conf): string
    {
        global $_COMPANY;
        if (!$event) return '';

        $method = ($status === 'CANCELLED') ? 'CANCEL' : 'REQUEST';

        if ($event->val('teamid') && $_COMPANY->getAppCustomization()['teams']['team_events']['detailed_ics']) {
                return CalendarICS::GenerateIcsFileForEvent($event, $method, $email, $rsvp, $include_web_conf);
        }

        $tz_utc = new DateTimeZone('UTC');
        $start = new DateTime($event->val('start'), $tz_utc);
        $end = new DateTime($event->val('end'), $tz_utc);
        $description = $event->val('event_description');
        $eventtitle = $event->val('eventtitle');
        $venue = empty($address) ? $venue : "{$venue} ({$address})";
        $web_conf_url = $event->getWebConferenceLink();
        $web_conf_details = $event->val('web_conference_detail');
        $event_contact = $event->val('event_contact');
        $method = ($status === 'CANCELLED') ? 'CANCEL' : 'REQUEST';

        $additional_location_details = '';
        if (!empty($venue)) {
            // Add the following additional details to the description
            if (!empty($event->val('venue_room'))) {
                $room = preg_replace('/\s\s+/', ' ', $event->val('venue_room'));
                $additional_location_details .= "Room: {$room} \\n";
            }
            if (!empty($event->val('venue_info'))) {
                $info = preg_replace('/\s\s+/', ' ', $event->val('venue_info'));
                $additional_location_details .= "Additional Information: {$info} \\n";
            }
            if (!empty($additional_location_details)) {
                $additional_location_details = "-::~:~::~:~:~:~:~:~::~:~::-\\n" . $additional_location_details;
            }
        }

        $description = preg_replace('/\s\s+/', ' ', $description);

        $web_conf_info = "-::~:~::~:~:~:~:~:~::~:~::-\\n";
        if ($event->isPrivateEvent()) {
            $web_conf_info .= self::PRIVATE_EVENT_MESSAGE . "\\n";
            $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\\n";
        }

        if (($event->val('add_photo_disclaimer') && $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['enabled'] && $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['show_on_top_in_emails'])) {

            $web_conf_info .= $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['disclaimer'] . "\\n";
            $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\\n";
        }
        if ($include_web_conf && !empty($web_conf_url)) {
            $web_conf_details = preg_replace('/\s\s+/', ' ', $web_conf_details);

            $web_conf_info .= "Join the event (Web conference link): {$web_conf_url} \\n";
            $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\\n";
            $web_conf_info .= $event->val('web_conference_sp') . " Details: {$web_conf_details} \\n";
            $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\\n";
        }
        $web_conf_info .= "Event Page: {$url} \\n";
        $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\\n";
        $web_conf_info .= "Event Contact: {$event_contact} \\n";
        $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\\n";
        if (($event->val('add_photo_disclaimer') && $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['enabled'] && !$_COMPANY->getAppCustomization()['event']['photo_disclaimer']['show_on_top_in_emails'])) {
            $web_conf_info .= $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['disclaimer'] . "\\n";
            $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\\n";
        }
        $web_conf_info .= "   \\n";

        return
            "BEGIN:VCALENDAR\r\n" .
            "PRODID:-//Teleskope LLC//Affinities Calendar v1.18.1\r\n" .
            "VERSION:2.0\r\n" .
            "CALSCALE:GREGORIAN\r\n" .
            "METHOD:{$method}\r\n" .
            "BEGIN:VEVENT\r\n" .
            'DTSTART:' . $start->format('Ymd\THi00\Z') . "\r\n" .
            'DTEND:' . $end->format('Ymd\THi00\Z') . "\r\n" .
            'DTSTAMP:' . date('Ymd\THis\Z') . "\r\n" .
            self::ics_line_split('ORGANIZER;CN='.$this->calQuote($from).':MAILTO:' . $rsvp_addr) . "\r\n" .
            'UID:' . $event->getEventUid() . "\r\n" .
            self::ics_line_split("ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT={$partstat};RSVP=TRUE;CN=Member:MAILTO:{$email}") . "\r\n" .
            self::ics_line_split($this->calQuote('DESCRIPTION:' . $additional_location_details. $web_conf_info . html_entity_decode($description))) . "\r\n" .
            self::ics_line_split($this->calQuote('LOCATION:' . html_entity_decode($venue))) . "\r\n" .
            'SEQUENCE:' . time() . "\r\n" .
            "STATUS:{$status}\r\n" .
            self::ics_line_split($this->calQuote('SUMMARY:' . html_entity_decode($eventtitle))) . "\r\n" .
            ($event->isPrivateEvent() ? "CLASS:PRIVATE\r\n" : '') .
            "TRANSP:{$transp}\r\n" .
            "BEGIN:VALARM\r\n" .
            "DESCRIPTION:REMINDER\r\n" .
            "TRIGGER;RELATED=START:-PT15M\r\n" .
            "ACTION:DISPLAY\r\n" .
            "END:VALARM\r\n" .
            "END:VEVENT\r\n" .
            "END:VCALENDAR\r\n";
    }

    private static function ics_line_split($str)
    {
        $end = "\r\n "; // Very important for ICS
        if (strlen($str) > 75) { // ICS RFC for max line length is 75 characters
            $firstchar = mb_substr($str, 0, 1);
            $str_to_chunk = mb_substr($str, 1);
            $pattern = '~.{1,74}~u';
            $str = $firstchar . preg_replace($pattern, '$0' . $end, $str_to_chunk);
            return rtrim($str, $end);
        } else {
            return $str;
        }
    }

    /**
     * @param int $rsvp
     * @return string
     */
    private function convertRsvpStatusToString(int $rsvp): string
    {
        if ($rsvp == Event::RSVP_TYPE['RSVP_YES']) {
            $rsvp_message = 'You RSVPed <b>YES</b> for this event!';
        } elseif ($rsvp == Event::RSVP_TYPE['RSVP_MAYBE']) {
            $rsvp_message = 'You RSVPed <b>MAYBE</b> for this event!';
        } elseif ($rsvp == Event::RSVP_TYPE['RSVP_INPERSON_YES']) {
            $rsvp_message = 'You RSVPed <b>ATTEND IN PERSON</b> for this event. Your seat is confirmed!';
        } elseif ($rsvp == Event::RSVP_TYPE['RSVP_ONLINE_YES']) {
            $rsvp_message = 'You RSVPed <b>ATTEND ONLINE</b> for this event!';
        } elseif ($rsvp == Event::RSVP_TYPE['RSVP_INPERSON_WAIT']) {
            $rsvp_message = 'You RSVPed <b>ADD TO WAITLIST (In Person)</b> for this event! We will automatically process your request and confirm your seat once a spot opens up.';
        } elseif ($rsvp == Event::RSVP_TYPE['RSVP_ONLINE_WAIT']) {
            $rsvp_message = 'You RSVPed <b>ADD TO WAITLIST (Online)</b> for this event! We will automatically process your request and confirm your seat once a spot opens up.';
        } elseif ($rsvp == Event::RSVP_TYPE['RSVP_NO']) {
            $rsvp_message = 'You RSVPed <b>DECLINE</b> for this event!';
        } else {
            $rsvp_message = 'You did not RSVP for this event.';
        }
        return $rsvp_message;
    }

    /**
     * Calculates the various Event variables for building event email.
     * @param Event $event
     * @param Group|null $group
     * @param string $from
     * @param string $reply_addr
     * @param int $rsvp if this method is called for RSVP updates, enter the RSVP type here.
     * @return array
     * @throws Exception
     */
    private function calculateVariousEventVariables(Event $event, ?Group $group, string $from, string $reply_addr, int $rsvp=0): array
    {
        global $_COMPANY, $_ZONE;
        $tz_utc = new DateTimeZone('UTC');
        $creator_tz = new DateTimeZone($event->val('timezone') ?: 'UTC');
        $venue = $event->val('eventvanue');
        $venue_info = $event->val('venue_info') ?? '';
        $venue_room = $event->val('venue_room') ?? '';
        $creator_start = (new DateTime($event->val('start'), $tz_utc))->setTimezone($creator_tz);
        $creator_end = (new DateTime($event->val('end'), $tz_utc))->setTimezone($creator_tz);
        $addedon = new DateTime($event->val('addedon'), $tz_utc);
        $address = $event->val('vanueaddress');
        $description = $event->val('event_description');
        $formated_when = $creator_start->format('F j, Y @g:i a T');
        $eventtitle = $event->val('eventtitle');
        $max_inperson = $event->val('max_inperson') ? (($event->val('max_inperson') == Event::MAX_PARTICIPATION_LIMIT) ? 'unlimited In-Person' : $event->val('max_inperson') . ' In-Person') : '';
        $max_online = $event->val('max_online') ? (($event->val('max_online') == Event::MAX_PARTICIPATION_LIMIT) ? 'unlimited Online' : $event->val('max_online') . ' Online') : '';
        $max_participant = ($max_inperson && $max_online) ? $max_inperson . ' / ' . $max_online : $max_inperson . $max_online;
        $send_ical = $event->sendIcal();
        $eventattendencetype = (int)$event->val('event_attendence_type');
        $web_conf_details = '';
        $onsite_addr = '';
        $web_conf_addr = '';
        $include_web_conf = false;
        $subject = html_entity_decode($eventtitle);
        $url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'eventview?id=' . $_COMPANY->encodeId($event->id());

        if (!empty($event->val('content_replyto_email'))) { // Specific reply to address overrides all others
            $reply_addr = $event->val('content_replyto_email');
        }

        $from = Group::BuildFromEmailLabel($event->val('collaborating_groupids') ?: $event->val('groupid'), $event->val('chapterid'), $event->val('channelid'));

        if ($eventattendencetype === 2 || $eventattendencetype === 3) {
            // Remote or Onsite and Remote
            $include_web_conf = true;
            $web_conf_addr = "<b>Meeting Link:</b> <a href='{$event->getWebConferenceLink()}'> {$event->val('web_conference_sp')}</a><br/>";

            if ($event->val('web_conference_detail')) {
                $web_conf_details = '<b>' . $event->val('web_conference_sp') . ' Details</b><br>' . $event->val('web_conference_detail');
            }
            if ($eventattendencetype === 2) {
                $venue = $event->val('web_conference_sp');
                $address = '';
            } elseif ($eventattendencetype === 3) {
                $venue .= ' / ' . $event->val('web_conference_sp');
            }
        }

        if ($eventattendencetype === 1 || $eventattendencetype === 3) {
            // Onsite or Onsite and Remote
            $onsite_addr = "<b>Where:</b> {$venue}<br/><b>Address:</b> {$address}<br/>";
            if(!empty($venue_room)){
                $onsite_addr .= "<b>Room:</b> {$venue_room}<br/>";
            }
            if(!empty($venue_info)){
                $onsite_addr .= "<b>Additional Information:</b> {$venue_info}<br/>";
            }
            
        }

        if ($eventattendencetype === 4) {
            $onsite_addr = "<b>Where:</b> Other / See Event Details<br/>";
        }

        if ($event->isSeriesEventHead()) {
            // Set some of the values to tailor the invitation for Event Series
            $formated_when = 'Multiple Events starting on ' . $formated_when;
            $onsite_addr = '';
            $web_conf_addr = '';
            $photo_disclaimer = '';
            $event_contact = "<b>Event Contact:</b> Visit Event Page";
            $rsvp_link = "<b style='color:red;'>RSVP REQUIRED:</b> There are multiple events in this series. To RSVP please follow the link provided for each individual Event";
            $url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'eventview?id=' . $_COMPANY->encodeId($this->eventid);
            $eventpage_url_str = "Event Series URL: <a href='{$url}'>{$url}</a>";
            $web_conf_details = '';
            // Show Series Description
            $description = $event->val('event_description');
            $description .= '<br>';
            $description .= '<table style="width:100%" border=1 background="grey"><thead><tr><th>Event</th><th>Start</th><th>End</th><th>RSVP Link</th></tr></thead>';
            $description .= '<tbody>';
            $events_in_series = Event::GetEventsInSeries($this->eventid);
            foreach ($events_in_series as $item) {
                $item_title = $item->val('eventtitle');
                $item_creator_tz = new DateTimeZone($item->val('timezone'));
                $item_creator_start = (new DateTime($item->val('start'), $tz_utc))->setTimezone($item_creator_tz);
                $item_creator_end = (new DateTime($item->val('end'), $tz_utc))->setTimezone($item_creator_tz);
                $item_link = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'eventview?id=' . $_COMPANY->encodeId($item->id());
                $description .= '<tr><td>' . $item_title . '</td><td>' . $item_creator_start->format('F j, Y @g:i a T') . '</td><td>' . $item_creator_end->format('F j, Y @g:i a T') . '</td><td><a href="' . $item_link . '">RSVP</a></td></tr>';
            }
            $description .= '</tbody>';
            $description .= '</table>';
        } else {
            $event_contact = "<b>Event Contact:</b> " . htmlspecialchars($event->val('event_contact'));
            $rsvp_link = '';
            $photo_disclaimer = ($event->val('add_photo_disclaimer') && $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['enabled']) ?
                '<br><p style="color:darkred !important; background:lightyellow !important;font-size: 10px; padding: 10px;">' . $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['disclaimer'] . '</p>' : '';

            if ($max_participant && $eventattendencetype != 4) { //We will not show links for limited participation events
                $rsvp_link = "<b style='color:red;'>RSVP REQUIRED:</b> This event has participation limit of {$max_participant} attendees. To confirm your attendance and reserve your seat please visit the Event Page.";
                // Links are not shown if the person does not have confirmed or waitlisted seat
                //if (!in_array($rsvp, array(Event::RSVP_TYPE['RSVP_INPERSON_YES'], Event::RSVP_TYPE['RSVP_INPERSON_WAIT'], Event::RSVP_TYPE['RSVP_ONLINE_YES'], Event::RSVP_TYPE['RSVP_ONLINE_WAIT']))) {
                if (!in_array($rsvp, array(Event::RSVP_TYPE['RSVP_INPERSON_YES'], Event::RSVP_TYPE['RSVP_ONLINE_YES']))) {
                    $onsite_addr = '';
                    $web_conf_addr = '';
                    $web_conf_details = '';
                }
            }
        }

        $private_event_note = '';
        if ($event->isPrivateEvent()) {
            $private_event_note = '<span style="color:red;">' . self::PRIVATE_EVENT_MESSAGE . '</span><br><br>';
        }
        $event_header = "{$private_event_note}{$onsite_addr}{$web_conf_addr}{$event_contact}";
        $content_subheader = $rsvp_link;
        $content_subfooter = $web_conf_details;

        if ($_COMPANY->getAppCustomization()['event']['photo_disclaimer']['show_on_top_in_emails']) {
            $event_header .= $photo_disclaimer;
        } else {
            $content_subfooter .= $photo_disclaimer;
        }

        if ($event->getDurationInSeconds() > 86400){
            $formated_when = $formated_when .' - '.$creator_end->format('F j, Y @g:i a T');
        }

        return array(
            $from,
            $reply_addr,
            $subject,
            $send_ical,
            $venue,
            $address,
            $formated_when,
            $url,
            $description,
            $eventtitle,
            $event_header,
            $content_subheader,
            $content_subfooter,
            $include_web_conf
        );
    }

    /**
     * @param Group|null $group
     * @return array
     */
    private function calculateVariousGroupVariables(?Group $group): array
    {
        global $_COMPANY, $_ZONE;
        $groupname = 'All';
        $from = $_ZONE->val('email_from_label'); // Defaults to Zone From email
        $app_type = $_ZONE->val('app_type');
        $reply_addr = '';
        $group_logo = '';

        if ($group) {
            $groupname = $group->val('groupname');
            $from = $group->val('from_email_label');
            $reply_addr = $group->val('replyto_email');
            $group_logo = $group->val('groupicon');
        }

        $rsvp_addr = $_COMPANY->getRsvpEmailAddr($app_type);

        return array(
            $groupname,
            $from,
            $app_type,
            $reply_addr,
            $rsvp_addr,
            $group_logo
        );
    }


    protected function processAsFollowupType()
    {
        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */

        $groupName = 'All';
        $from = '';
        $app_type = $_ZONE->val('app_type');
        $reply_addr = '';

        $group = null;
        if ($this->groupid) {
            $group = Group::GetGroup($this->groupid);
            if ($group === null) {
                return;
            } //no matching valid group found
            $groupName = $group->val('groupname');
            $from = $group->val('from_email_label');
            $reply_addr = $group->val('replyto_email');
        }
        if ($group) {
            $from = $group->getFromEmailLabel(0, 0);
        }

        $groupCustomName = $_COMPANY->getAppCustomization()['group']['name-short'];

        $followup_type = $this->options['followup_type'] ?? '';

        if ($_ZONE->val('email_settings') >= 2) {

            if($followup_type == self::FOLLOWUP_TYPES['EVENT_RECONCILIATION']) {
                $past_event = Event::GetEvent($this->eventid);
                if (!$past_event || $past_event->val('isactive') == 0 || $past_event->val('is_event_reconciled')) {
                    return; // Nothing to do.
                }
                $emailTemplate = EmailHelper::EmailReconcileReminderTemplate($past_event->val('eventid'), $past_event->val('eventtitle'), $this->options['email_subject_template'], $this->options['email_body_template']);

                if ($emailTemplate) {
                    $contributor_ids = explode(',', $past_event->val('event_contributors') ?? '');
                    $contributor_ids[] = $past_event->val('userid');
                    $contributor_ids = array_filter(array_unique($contributor_ids));
                    foreach ($contributor_ids as $contributor_id) {
                        //$contributor = User::GetUser($contributor_id);
                        $contributor = User::GetUser((int)$contributor_id);
                        if ($contributor and $contributor->isActive()) {
                            $_COMPANY->emailSend2($from, $contributor->val('email'), $emailTemplate['subject'], $emailTemplate['message'], $app_type, $reply_addr);
                        }
                    }
                }
            }
        }

    }

    public function saveAsEventReconciliationFollowup(string $email_subject_template, string $email_body_template, string $contributorUseridCSV)
    {
        $this->options['email_subject_template'] = $email_subject_template;
        $this->options['email_body_template'] = $email_body_template;
        $this->options['followup_type'] =  self::FOLLOWUP_TYPES['EVENT_RECONCILIATION'];
        $this->details = $contributorUseridCSV;

        # First check if another event reconciliation jobs is set, if so skip this one
        $partial_job_id = 'EVT_' . $this->cid() . '_' . $this->groupid . '_' . $this->eventid . '_0_' ;
        $event_job_type = (int)self::TYPE_EVENT;
        $followup_job_subtype = (int)self::SUBTYPE_FOLLOWUP;
        $reconciliation_followup_type = self::FOLLOWUP_TYPES['EVENT_RECONCILIATION'];
        $rows = self::DBROGet("SELECT jobid, `status` FROM `jobs` WHERE companyid={$this->cid()} AND `status`=0 AND jobid like '{$partial_job_id}%' AND jobtype={$event_job_type} AND jobsubtype={$followup_job_subtype} AND jobs.options->>\"$.followup_type\"='{$reconciliation_followup_type}'");
        if (!empty($rows)) {
            Logger::LogInfo('Skipping setting an event follow-up job as a duplicate already exists: ' . $rows[0]['jobid'],
                [
                    'followup_type' => self::FOLLOWUP_TYPES['EVENT_RECONCILIATION'],
                    'eventid' => $this->eventid,
                ]
            );
            return 0;
        }

        return $this->saveAsFollowupType(); // Save a new job as there are no other duplicates
    }

    /**
     * Schedules a batch reminder job for booking events.
     *
     * @param string $subjectTemplate
     * @param string $bodyTemplate
     * @param array $userIds Array of user IDs to remind.
     * @param int $reminder_days
     * @param int $eventStartTimestamp
     * @param string $meetingLink
     * @return void
     */
    public function scheduleBookingReminderBatchJob(
        string $subjectTemplate,
        string $bodyTemplate,
        array $userIds,
        int $reminder_days,
        int $eventStartTimestamp,
        string $meetingLink
    ): void {
        // Calculate delay in seconds
        $reminderTimestamp = $eventStartTimestamp - ($reminder_days * 86400);
        $delay = $reminderTimestamp - time();
        if ($delay < 0) $delay = 0;
        $this->delay = $delay;

        // Prepare template with placeholders for batch
        $meetingDate = date('Y-m-d', $eventStartTimestamp);
        $meetingTime = date('H:i', $eventStartTimestamp);

        $template = EmailHelper::getBookingReminderEmailTemplate(
            $subjectTemplate,
            $bodyTemplate,
            $meetingDate,
            $meetingTime,
            $meetingLink
        );

        $this->saveAsBatchRemindType(
            $template['subject'],
            $template['body'],
            0, // includeEventDetails
            $userIds, // batch of user IDs
            0  // futureEventsOnly
        );
    }
}