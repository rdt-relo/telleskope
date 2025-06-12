<?php

class SurveyJob extends Job
{
    public $groupid;
    public $chapterid;
    public $channelid;
    public $surveyid;

    public function __construct($gid, $chid, $sid)
    {
        parent::__construct();
        $this->groupid = $gid;
        $this->chapterid = $chid;
        $this->surveyid = $sid;
        $this->jobid = "SUR_{$this->cid}_{$gid}_{$chid}_{$sid}_{$this->instance}_" . microtime(TRUE);
        $this->jobtype = self::TYPE_SURVEY;
    }

    public function __clone()
    {
        parent::__clone();
        $this->jobid = "SUR_{$this->cid}_{$this->groupid}_{$this->chapterid}_{$this->surveyid}_{$this->instance}_" . microtime(TRUE);
    }

    public function saveAsBatchCreateType(int $sendEmails = 1, array $external_integrations = array())
    {
        // TODO - Will decide
    }

    protected function processAsCreateType()
    {
        // TODO - Will decide
    }

    public function sendForReview(string $toList, string $reviewNote, string $subjectPrefix = 'Review: ')
    {

        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */
        $email_settings = $_ZONE->val('email_settings');
        if ($email_settings >= 1) {
            $survey = Survey2::GetSurvey($this->surveyid);
            return $survey->sendSurveyForReview($toList, $reviewNote);
        }
        return 0;
    }

    public function cancelAllPendingJobs()
    {
        $delete_jobid = "SUR_{$this->cid}_{$this->groupid}_%_{$this->surveyid}_%";
        return self::DBUpdate("UPDATE jobs SET status=100, processedby='CANCELLED', processedon=now() WHERE companyid='{$this->cid}' AND (jobid like '{$delete_jobid}' AND jobtype={$this->jobtype} AND status=0)");
    }
}