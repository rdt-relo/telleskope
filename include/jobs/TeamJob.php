<?php

class TeamJob extends Job
{
    public $teamid;
    public $groupid;
    public function __construct($gid,$tid)
    {
        parent::__construct();
        $this->groupid = $gid;
        $this->teamid = $tid;
        $this->jobid = "TEM_{$this->cid}_{$gid}_{$tid}_{$this->instance}_" . microtime(TRUE);
        $this->jobtype = self::TYPE_TEAM;
    }

    const REMINDER_TYPES = [
        'INACTIVITY' => 'INACTIVITY',
        'UPCOMING_TASK' => 'UPCOMING_TASK',
        'OVERDUE_TASK' => 'OVERDUE_TASK',
    ];

    const FOLLOWUP_TYPES = [
        'OVERDUE_ROLE_REQUEST' => 'OVERDUE_ROLE_REQUEST',
    ];

    public function __clone()
    {
        parent::__clone();
        $this->jobid = "TEM_{$this->cid}_{$this->groupid}_{$this->teamid}_{$this->instance}_" . microtime(TRUE);
    }

    public function saveAsTeamInactivityNotificationJob (int $days, int $notification_days_after, int $notification_frequency) : int
    {
        global $_COMPANY;

        // Calculate the impacted users and create cloned jobs
        $retVal = 0;
        $data = self::DBGet("SELECT `team_memberid`, `userid`, `roleid` FROM `team_members` LEFT JOIN users USING (userid) WHERE `teamid`='{$this->teamid}' AND users.companyid={$_COMPANY->id()} AND users.isactive=1");
        $useridArr = array_column($data,'userid');
        if (!empty($useridArr)) {
            $this->options['days'] = $days;
            $this->options['notification_days_after'] = $notification_days_after;
            $this->options['notification_frequency'] = $notification_frequency;
            $this->options['reminder_type'] = self::REMINDER_TYPES['INACTIVITY'];
            $this->details = implode(',', $useridArr);
            $retVal = $this->saveAsRemindType();
        }
        return  $retVal;
    }

    public function saveAsUpcomingTaskNotificationJob (string $taskType, string $taskTitle, int $taskAssignedTo, string $taskDueDate) : int
    {
        global $_COMPANY;
        $retVal = 0;

        if(empty($taskType) || !in_array($taskType, ['todo','touchpoint'])) {
            return 0;
        }

        if ($taskAssignedTo) {
            // Task is assigned to an individual
            $useridArr = [$taskAssignedTo];
        } else {
            // Task is not assigned to individual, update the entire team
            $data = self::DBGet("SELECT `team_memberid`, `userid`, `roleid` FROM `team_members` LEFT JOIN users USING (userid) WHERE `teamid`='{$this->teamid}' AND users.companyid={$_COMPANY->id()} AND users.isactive=1");
            $useridArr = array_column($data,'userid');
        }

        if (!empty($useridArr)) {
            $this->options['task_type'] = $taskType;
            $this->options['task_title'] = $taskTitle;
            $this->options['task_assigned_to'] = $taskAssignedTo;
            $this->options['task_due_date'] = $taskDueDate;
            $this->options['reminder_type'] = self::REMINDER_TYPES['UPCOMING_TASK'];
            $this->details = implode(',', $useridArr);
            $retVal = $this->saveAsRemindType();
        }

        return $retVal;
    }

    public function saveAsOverdueTaskNotificationJob (string $taskType, string $taskTitle, int $taskAssignedTo, string $taskDueDate) : int
    {
        global $_COMPANY;
        $retVal = 0;

        if(empty($taskType) || !in_array($taskType, ['todo','touchpoint'])) {
            return 0;
        }

        if ($taskAssignedTo) {
            // Task is assigned to an individual
            $useridArr = [$taskAssignedTo];
        } else {
            // Task is not assigned to individual, update the entire team
            $data = self::DBGet("SELECT `team_memberid`, `userid`, `roleid` FROM `team_members` LEFT JOIN users USING (userid) WHERE `teamid`='{$this->teamid}' AND users.companyid={$_COMPANY->id()} AND users.isactive=1");
            $useridArr = array_column($data,'userid');
        }

        if (!empty($useridArr)) {
            $this->options['task_type'] = $taskType;
            $this->options['task_title'] = $taskTitle;
            $this->options['task_assigned_to'] = $taskAssignedTo;
            $this->options['task_due_date'] = $taskDueDate;
            $this->options['reminder_type'] =  self::REMINDER_TYPES['OVERDUE_TASK'];
            $this->details = implode(',', $useridArr);
            $retVal = $this->saveAsRemindType();
        }

        return $retVal;
    }

    public function saveAsOverdueRoleRequestFollowupJob(int $requestSenderId, int $requestReceiverId, int $requestedRoleId, string $requestDate, int $days, int $notificationDaysAfter, int $notificationFrequency) : int
    {
        $this->options['days'] = $days;
        $this->options['notification_days_after'] = $notificationDaysAfter;
        $this->options['notification_frequency'] = $notificationFrequency;
        $this->options['request_sender_id'] = $requestSenderId;
        $this->options['request_receiver_id'] = $requestReceiverId;
        $this->options['requested_role_id'] = $requestedRoleId;
        $this->options['request_date'] = $requestDate;
        $this->options['followup_type'] =  self::FOLLOWUP_TYPES['OVERDUE_ROLE_REQUEST'];
        $this->details = '';
        return $this->saveAsFollowupType();
    }

    protected function processAsRemindType()
    {
        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */

        if (empty($this->details) || !($members = explode(',', $this->details))) {
            return;
        } //no users to notify

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

        $team =  Team::GetTeam($this->teamid);
        if (!$team) {
            return;
        }

        $teamName = $team->val('team_name');
        $teamCustomName = $_COMPANY->getAppCustomization()['teams']['name'];
        $groupCustomName = $_COMPANY->getAppCustomization()['group']['name-short'];

        $baseurl = $_COMPANY->getAppURL($app_type);
        $teamhash = 'getMyTeams-'.$_COMPANY->encodeId($team->id());
        $teamUrl = $baseurl . 'detail?id=' . $_COMPANY->encodeId($this->groupid) . '&hash='. $teamhash . '#'. $teamhash;
        $reminder_type = $this->options['reminder_type'] ?? '';

        for ($i = 0; $i < count($members); $i++) {
            $mid = $members[$i];

            if (empty($mid) || ($touser = User::GetUser((int)$mid)) === NULL || !$touser->isActive())
                continue;

            if ($_ZONE->val('email_settings') >= 2) {

                if($reminder_type == self::REMINDER_TYPES['UPCOMING_TASK']) {
                    $taskType = ($this->options['task_type'] == 'touchpoint') ? 'Touch Point' : 'Action Item';
                    $taskTitle = $this->options['task_title'];
                    //$taskAssignedTo = $this->options['task_assigned_to'];
                    $taskDueDate = $touser->formatUTCDatetimeForDisplayInLocalTimezone($this->options['task_due_date'], true, false, true);

                    $temp = EmailHelper::TeamUpcomingReminder($touser->getFullName(), $teamName, $teamCustomName, $teamUrl, $groupName, $groupCustomName, $taskType, $taskTitle, $taskDueDate);
                }
                elseif($reminder_type == self::REMINDER_TYPES['OVERDUE_TASK']) {
                    $taskType = ($this->options['task_type'] == 'touchpoint') ? 'Touch Point' : 'Action Item';
                    $taskTitle = $this->options['task_title'];
                    //$taskAssignedTo = $this->options['task_assigned_to'];
                    $taskDueDate = $touser->formatUTCDatetimeForDisplayInLocalTimezone($this->options['task_due_date'], true, false, true);

                    $temp = EmailHelper::TeamOverdueReminder($touser->getFullName(), $teamName, $teamCustomName, $teamUrl, $groupName, $groupCustomName, $taskType, $taskTitle, $taskDueDate);
                }
                elseif ($reminder_type == self::REMINDER_TYPES['INACTIVITY']) {
                    $inactivityDays = $this->options['days'];
                    $notificationDaysAfter = $this->options['notification_days_after'];
                    $notificationFrequency = $this->options['notification_frequency'];
                    $temp = EmailHelper::TeamInactivity($touser->getFullName(), $teamName, $teamCustomName, $teamUrl, $groupName, $groupCustomName, $inactivityDays, $notificationDaysAfter, $notificationFrequency);
                }

                $email = $touser->val('email');
                $_COMPANY->emailSend2($from, $email, $temp['subject'], $temp['message'], $app_type, $reply_addr);
            }
        }
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

        $teamCustomName = $_COMPANY->getAppCustomization()['teams']['name'];
        $groupCustomName = $_COMPANY->getAppCustomization()['group']['name-short'];

        $followup_type = $this->options['followup_type'] ?? '';

        if ($_ZONE->val('email_settings') >= 2) {

            if($followup_type == self::FOLLOWUP_TYPES['OVERDUE_ROLE_REQUEST']) {
                $baseurl = $_COMPANY->getAppURL($app_type);
                $requestSenderUser = User::GetUser($this->options['request_sender_id']);
                $requestReceiverUser = User::GetUser($this->options['request_receiver_id']);
                $requestedRole = Team::GetTeamRoleType($this->options['requested_role_id']);
                if ($requestSenderUser && $requestSenderUser->isActive() && $requestReceiverUser && $requestReceiverUser->isActive() && $requestedRole) {
                    // Remind the receiver
                    $requestUrl = $baseurl . 'detail?id=' . $_COMPANY->encodeId($this->groupid) . '&hash=getMyTeams/getTeamReceivedRequests';
                    $requestDate = $requestReceiverUser->formatUTCDatetimeForDisplayInLocalTimezone($this->options['request_date']);
                    $temp = EmailHelper::TeamRoleRequestFollowupEmailTemplateToReceiver($requestReceiverUser, $requestSenderUser, $requestedRole['type'], $requestDate, $requestUrl, $groupCustomName, $groupName);
                    $_COMPANY->emailSend2($from, $requestReceiverUser->val('email'), $temp['subject'], $temp['message'], $app_type, $reply_addr);

                    // Update the sender
                    $requestUrl = $baseurl . 'detail?id=' . $_COMPANY->encodeId($this->groupid) . '&hash=getMyTeams/getTeamInvites';
                    $requestDate = $requestSenderUser->formatUTCDatetimeForDisplayInLocalTimezone($this->options['request_date']);
                    $temp = EmailHelper::TeamRoleRequestFollowupEmailTemplateToSender($requestReceiverUser, $requestSenderUser, $requestedRole['type'], $requestDate, $requestUrl, $groupCustomName, $groupName);
                    $_COMPANY->emailSend2($from, $requestSenderUser->val('email'), $temp['subject'], $temp['message'], $app_type, $reply_addr);
                }
            }
        }

    }

    public function cancelAllPendingJobs()
    {
        $delete_jobid = "TEM_{$this->cid}_{$this->groupid}_{$this->teamid}_%";
        return self::DBUpdate("UPDATE jobs SET status=100, processedby='CANCELLED', processedon=now() WHERE companyid='{$this->cid}' AND (jobid like '{$delete_jobid}' AND jobtype={$this->jobtype} AND status=0)");
    }

    public function saveAsAutoComplete_afterNDaysType(int $n_days, string $team_start_date): int
    {
        $this->options['reason'] = 'Auto-complete after n days';
        $this->options['n'] = $n_days;
        $this->options['team_start_date'] = $team_start_date;
        return parent::saveAsCompleteType();
    }

    public function saveAsAutoCompleteTeamOnMenteeStartDateType(int $n_days, string $mentee_userids, string $mentee_start_dates): int
    {
        $this->options['reason'] = "Auto-complete as Mentee's start date has passed";
        $this->options['n'] = $n_days;
        $this->options['mentee_userids'] = $mentee_userids;
        $this->options['mentee_start_dates'] = $mentee_start_dates;
        return parent::saveAsCompleteType();
    }

    protected function processAsCompleteType()
    {
        $this->cancelAllPendingJobs();

        $team_obj = Team::GetTeam($this->teamid);

        $team_obj ?-> complete();
    }
}