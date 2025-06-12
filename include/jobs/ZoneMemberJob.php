<?php

class ZoneMemberJob extends Job
{
    public $zoneid;

    public function __construct($zid)
    {
        parent::__construct();
        $this->zoneid = $zid;
        $this->jobid = "ZON_{$this->cid}_{$zid}_" . microtime(TRUE);
        $this->jobtype = self::TYPE_ZONEMEMBER;
    }

    public function saveAsJoinType(int $userid)
    {
        // First cleanup all the past jobs of DELETE type matching current criteria
        if ($this->cancelAllPendingJobs(self::SUBTYPE_DELETE, $userid) === -1) {
            // Only save if there were no pending jobs of DELETE type otherwise it negates
            $this->details = $userid;
            parent::saveAsCreateType();
        }
    }

    public function saveAsLeaveType(int $userid)
    {
        // First cleanup all the past jobs of DELETE type matching current criteria
        if ($this->cancelAllPendingJobs(self::SUBTYPE_CREATE, $userid) === -1) {
            // Only save if there were no pending jobs of CREATE type otherwise it negates
            $this->details = $userid;
            parent::saveAsDeleteType();
        }
    }


    /**
     * @param int $type
     * @return void
     */
    private function processAsAnyType(int $type)
    {
        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */
        $app_type = $_ZONE->val('app_type');
        $reply_addr = '';
        $from = '';

        if (($touser = User::GetUser((int)$this->details)) === null) {
            return; // Target user does not exist.
        }

        $gm_row = self::DBGet("SELECT communication,emailsubject,IFNULL(email_cc_list,'') as email_cc_list,`send_upcoming_events_email` FROM group_communications WHERE group_communications.companyid='{$_COMPANY->id()}' AND group_communications.zoneid='{$_ZONE->id()}' AND groupid=0 AND chapterid=0 AND channelid=0 AND communication_trigger={$type} AND isactive=1");

        if (count($gm_row) && !empty($gm_row[0]['communication'])) {
            
            $emesg = $gm_row[0]['communication'];
            $subject = html_entity_decode($gm_row[0]['emailsubject']);

            if (empty($subject)) {
                $subject = ($type === 3) ? 'Welcome' : 'See you later';
            }

            $email_cc_list = $gm_row[0]['email_cc_list'];

            $email_settings = $_ZONE->val('email_settings');
            
            $email = $touser->val('email');
            
            if ($email_settings >= 1 && $email) {
                $chaptername = '';
                $groupname = '';
                $grouplogo = '';
                $groupcolor = '#000000';
                $groupcolor2 = '#000000';
                $companyname = $_COMPANY->val('companyname');
                $companylogo = $_COMPANY->val('logo');
                $companyurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));
                $groupurl = $companyurl;
                $person_firstname = $touser->val('firstname');
                $person_lastname = $touser->val('lastname');
                $person_name = $touser->getFullName();
                $person_email = $touser->val('email');
                $replace_vars = ['[%COMPANY_NAME%]','COMPANY_LOGO','[%GROUP_NAME%]', 'GROUP_LOGO', '[%CHAPTER_NAME%]', '[%COMPANY_URL%]', '[%GROUP_URL%]', '[%RECIPIENT_NAME%]', '[%RECIPIENT_FIRST_NAME%]', '[%RECIPIENT_LAST_NAME%]', '[%RECIPIENT_EMAIL%]', '#000001','#000002'];
                $replacement_vars = [$companyname, $companylogo, $groupname, $grouplogo, $chaptername,$companyurl, $groupurl, $person_name, $person_firstname, $person_lastname, $person_email, $groupcolor, $groupcolor2];
                $emesg = str_replace($replace_vars, $replacement_vars, $emesg);
                $_COMPANY->emailSend2($from, $email, $subject, $emesg, $app_type, $reply_addr, '', array(), $email_cc_list);
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

    public function cancelAllPendingJobs(int $jobsubtype, int $userid): int
    {
        $delete_jobid = "ZON_{$this->cid}_{$this->zoneid}_";
        return self::DBUpdate("UPDATE jobs SET status=100, processedby='CANCELLED', processedon=now() WHERE companyid='{$this->cid}' AND (jobid like '{$delete_jobid}%' AND jobsubtype={$jobsubtype} AND status=0 AND details='{$userid}')");
    }
}