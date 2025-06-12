<?php

class SurveyResponseJob extends Job
{
    public $surveyid;
    public $surveyresponseid;

    public function __construct($sid, $rid)
    {
        parent::__construct();
        $this->surveyid = $sid;
        $this->surveyresponseid = $rid;
        $this->jobid = "SRVRES_{$this->cid}_S_{$sid}_{$rid}_{$this->instance}_" . microtime(TRUE);
        $this->jobtype = self::TYPE_SURVEY_RESPONSE;
    }

    public function __clone()
    {
        parent::__clone();
        $this->jobid = "SRVRES_{$this->cid}_S_{$this->surveyid}_{$this->surveyresponseid}_{$this->instance}_" . microtime(TRUE);
    }

    public function saveAsBatchCreateType()
    {
        // code start
        $this->saveAsCreateType();

    }

    protected function processAsCreateType()
    {
        global $_ZONE, $_COMPANY;
        //get the data we need where emails are present in send_email_notification_to column
        $data = self::DBGet("SELECT survey.surveyname,survey.anonymous,survey.send_email_notification_to,survey.groupid,survey.chapterid,survey.channelid,surveyresponse.userid 
            FROM `surveys_v2` AS survey
            LEFT JOIN `survey_responses_v2` AS surveyresponse ON survey.surveyid = surveyresponse.surveyid
            WHERE survey.companyid='{$_COMPANY->id()}' AND survey.surveyid='{$this->surveyid}' AND surveyresponse.responseid ='{$this->surveyresponseid}'");

        if (!empty($data)) {
            //set the variables for chapterid, channelid, groupid
            $anonymousSurvey = $data[0]['anonymous'];
            $send_emails_to = $data[0]['send_email_notification_to'];
            $surveyName = $data[0]['surveyname'];
            $group = Group::GetGroup($data[0]['groupid']);
            $ergName = $group->val('groupname');
            $reply_addr = $group->val('replyto_email');
            $from = $group->getFromEmailLabel($data[0]['chapterid'], $data[0]['channelid']) ?: $_ZONE->val('email_from_label');
            $from = $from . ' Surveys';

            //survey responder name
            // set the value of the person who filled survey
            if (!$anonymousSurvey && $data[0]['userid']) {
                $user = User::GetUser($data[0]['userid']);
                $responderName = $user->getFullName();
                $responderEmail = $user->val('email');
                $responderNameAndEmail = $responderName . '(' . $responderEmail . ')';
            } else {
                $responderName = 'Anonymous';
                $responderEmail = '';
                $responderNameAndEmail = 'Anonymous';
            }

            if ($responderEmail) {
                // first send email to the person who submitted response
                $temp = EmailHelper::SurveySubmissionConfirmation($group->id(), $responderName, $surveyName, $ergName);
                $_COMPANY->emailSend2($from, $responderEmail, $temp['subject'], $temp['message'], $_ZONE->val('app_type'), $reply_addr);

            }

            // check if notification is to be sent to others as well
            if ($send_emails_to) {
                $temp = EmailHelper::NewSurveyResponse($group->id(), $responderNameAndEmail, $surveyName, $ergName);
                $_COMPANY->emailSend2($from, $send_emails_to, $temp['subject'], $temp['message'], $_ZONE->val('app_type'), $reply_addr);
            }

            Points::HandleTrigger('ON_SURVEY_RESPONSE', [
                'surveyResponseId' => $this->surveyresponseid,
            ]);
        }
    }

    public function cancelAllPendingJobs()
    {
        $delete_jobid = "SRVRES_{$this->cid}_S_{$this->surveyid}_{$this->surveyresponseid}_%";
        return self::DBUpdate("UPDATE jobs SET status=100, processedby='CANCELLED', processedon=now() WHERE companyid='{$this->cid}' AND (jobid like '{$delete_jobid}' AND jobtype={$this->jobtype} AND status=0)");
    }
}