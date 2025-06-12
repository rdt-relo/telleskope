<?php

require_once __DIR__ .'/../Company.php';
require_once __DIR__ . '/../integrations/GroupIntegration.php';
require_once __DIR__ .'/../dblog/EmailLog.php';

/** Note: Job class needs $_COMPANY context */

date_default_timezone_set('UTC');

class Job extends Teleskope
{
    const TYPE_EVENT = 1;
    const TYPE_GROUP = 2;
    const TYPE_GROUPMEMBER = 3;
    const TYPE_POST = 4;
    const TYPE_POSTLIKE = 5;
    const TYPE_POSTCOMMENT = 6;
    const TYPE_MESSAGE = 7;
    const TYPE_NEWSLETTER = 8;
    const TYPE_SURVEY = 9;
    const TYPE_SURVEY_RESPONSE = 10;
    const TYPE_TEAM = 11;
    const TYPE_DISCUSSION = 12;
    const TYPE_ZONEMEMBER = 13;
    const TYPE_POINTS_CREDIT = 14;

    // Long term jobs
    const TYPE_WATCH = 100;
    // Batch Jobs
    const TYPE_S3_CLEANUP = 118;
    const TYPE_BATCH_HOURLY = 119;
    const TYPE_BATCH_DAILY = 120;
    const TYPE_BATCH_WEEKLY = 121;
    const TYPE_BATCH_MONTHLY = 122;
    const TYPE_DATA_EXPORT = 123;
    const TYPE_DATA_IMPORT = 124;
    const TYPE_USERSYNC_FILE = 125;
    const TYPE_USERSYNC_365 = 126;
    const TYPE_DBM = 127;

    const SUBTYPE_OTHER = 0; // e.g. a perpetual job has finished.
    const SUBTYPE_CREATE = 1;
    const SUBTYPE_UPDATE = 2;
    const SUBTYPE_DELETE = 3;
    const SUBTYPE_REMIND = 4;
    const SUBTYPE_FOLLOWUP = 5; #This is similar to reminder but sometimes you need another generic mechanism for followups. Use Reminder as first step.
    const SUBTYPE_COMPLETE = 6;
    const SUBTYPE_CLEANUP = 100;
    const SUBTYPE_PERPETUAL = 127;

    const STATUS_UNPROCESSED = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_PROCESSED = 100;

    static $instances = 0;
    protected $instance = 0;
    protected $batch_minimum_size = 1000; // Number of entries after which a new batch should be created
    protected $batch_maximum_count = 10; //Maximum number of batches
    protected $batch_stagger_seconds = 65; //In seconds;

    protected $createdby = 0;
    protected $jobid;
    protected $jobtype = 0;
    protected $jobsubtype = 0;
    protected $details = '';
    protected $options = array();
    protected $zoneid = 0;
    protected $processafter;

    public $delay = 5; // In seconds

    protected function __construct()
    {
        global $_COMPANY, $_ZONE, $_USER;
        $cid = ($_COMPANY !== null) ? $_COMPANY->id() : 0;

        if ($_USER !== null) { $this->createdby = $_USER->id();}
        parent::__construct(-1, $cid, [
            'createdby' => $this->createdby ?? 0,
        ]);
        if ($_ZONE !== null) { $this->zoneid = $_ZONE->id();}
    }

    protected function __clone()
    {
        $this->instance = ++self::$instances;
    }

    protected function save()
    {
        $options_str = json_encode($this->options);
        return self::DBMutatePS("INSERT INTO jobs(jobid, jobtype, jobsubtype, details, `options`, companyid, zoneid, createdby, createdon, processafter) VALUES (?,?,?,?,?,?,?,?,now(),(now()+ INTERVAL {$this->delay} second))", 'xiixxiii', $this->jobid, $this->jobtype, $this->jobsubtype, $this->details, $options_str, $this->cid, $this->zoneid, $this->createdby);
    }

    protected function deleteUpdateType()
    {
        self::DBUpdate("DELETE FROM jobs WHERE jobid='{$this->jobid}' AND jobsubtype='" . self::SUBTYPE_UPDATE . "'");
    }

    protected function deleteRemindType()
    {
        self::DBUpdate("DELETE FROM jobs WHERE jobid='{$this->jobid}' AND jobsubtype='" . self::SUBTYPE_REMIND . "'");
    }

    protected function deleteFollowupType()
    {
        self::DBUpdate("DELETE FROM jobs WHERE jobid='{$this->jobid}' AND jobsubtype='" . self::SUBTYPE_FOLLOWUP . "'");
    }

    protected function deleteCreateType()
    {
        self::DBUpdate("DELETE FROM jobs WHERE jobid='{$this->jobid}' AND jobsubtype='" . self::SUBTYPE_CREATE . "'");
    }

    protected function deleteAnyType()
    {
        self::DBUpdate("DELETE FROM jobs WHERE jobid like '{$this->jobid}%'");
    }

    protected function saveAsCreateType()
    {
        $this->jobsubtype = self::SUBTYPE_CREATE;
        if (!$this->save()) {
            // This should not typically happen, unless there are DB problems or misformed SQL
            Logger::Log('Job: Fatal Error, coud not save job of create subtype');
        }
    }

    protected function saveAsUpdateType()
    {
        $this->deleteRemindType();
        $this->jobsubtype = self::SUBTYPE_UPDATE;
        if (!$this->save()) {
            // This can happen if there is a pending job of create type, just ignore in that case.
            Logger::Log('Job: Warning, could not save job of update subtype', Logger::SEVERITY['WARNING']);
        }
    }

    protected function saveAsDeleteType()
    {
//        $this->deleteRemindType(); #Since we have unique job ids this delete functions were not useful
//        $this->deleteUpdateType();
        $this->jobsubtype = self::SUBTYPE_DELETE;
        if (!$this->save()) {
            $this->deleteCreateType(); //Looks like the target was not completed, so no need to send DELETE messages
            $this->jobsubtype = self::SUBTYPE_CLEANUP;
            $this->save(); //Cleanup
            // This can happen if there is unprocessed create type job.
            Logger::Log('Job: Warning, could not save job of delete subtype, setting cleanup', Logger::SEVERITY['WARNING']);
        }
    }

    protected function saveAsPerpetualType()
    {
        $this->jobsubtype = self::SUBTYPE_PERPETUAL;
        if (!$this->save()) {
            // This should not typically happen, unless there are DB problems or misformed SQL
            Logger::Log('Job: Fatal Error, coud not save job of create subtype');
        }
    }

    protected function saveAsRemindType(): int
    {
        global $_ZONE;
        $this->jobsubtype = self::SUBTYPE_REMIND;
        $retVal = $this->save();
        if (!$retVal) {
            // This can happen if reminder was set too early while the add or update job is still in progress, ignore this error
            Logger::Log('Job: Fatal Error saving job of Reminder type');
        }
        return $retVal;
    }

    public function saveAsFollowupType() : int
    {
        $this->jobsubtype = self::SUBTYPE_FOLLOWUP;
        $retVal = $this->save();
        if (!$retVal) {
            // This should not typically happen, unless there are DB problems or misformed SQL
            Logger::Log('Job: Fatal Error, coud not save job of followup subtype');
        }
        return $retVal;
    }

    protected function saveAsCompleteType(): int
    {
        global $_ZONE;
        $this->jobsubtype = self::SUBTYPE_COMPLETE;
        $retVal = $this->save();
        if (!$retVal) {
            // This can happen if reminder was set too early while the add or update job is still in progress, ignore this error
            Logger::Log('Job: Fatal Error saving job of Complete type');
        }
        return $retVal;
    }

    public function saveAsCleanupType()
    {
        $this->jobsubtype = self::SUBTYPE_CLEANUP;
        if (!$this->save()) {
            Logger::Log('Job: Ignoring saving a job of Cleanup type', Logger::SEVERITY['INFO']);
        }
    }

    protected function processAsCreateType()
    {
    }

    protected function processAsUpdateType()
    {
    }

    protected function processAsDeleteType()
    {
    }

    protected function processAsRemindType()
    {
    }

    protected function processAsFollowupType()
    {
    }

    protected function processAsCompleteType()
    {
    }

    protected function processAsCleanupType()
    {
    }

    protected function processAsPerpetualType()
    {
    }

    public static function DeleteJob (string $jobid): int
    {
        global $_COMPANY;
        return self::DBMutatePS("DELETE FROM jobs WHERE companyid=? AND jobid=?",
            'ix',
            $_COMPANY->id(), $jobid
        );
    }

    /**
     * @reserved
     * Reserved method to reset/update attributes prior to processing. Do not use this method outside the Job.php file
     * @param string $jobid
     * @param string $processafter
     * @param int $jobsubtype
     * @param string|null $details
     * @param string|null $options_json json encoded string of options
     * @param int $instance
     */
    public function fixParametersPriorToProcessing(string $jobid, string $processafter, int $jobsubtype, ?string $details, ?string $options_json, int $instance = -1)
    {
        $this->jobid = $jobid;
        $this->jobsubtype = $jobsubtype;
        $this->details = $details ?? '';
        $this->options = json_decode($options_json ?? '[]',true) ?: array();
        $this->instance = $instance;
        $this->processafter = $processafter;
    }

    public function process()
    {
        // Note in the following atomic update we are checking for two things
        // (a) The job status should be 0, i.e. it is not processed or in process
        // (b) processafter should match the intended jobs processafter. This avoids reprocessing the jobs which has status = 0 but has been reset for future processing
        if (!self::DBAtomicUpdate("UPDATE jobs SET status=1, processedon=now(), processedby='system' WHERE jobid='{$this->jobid}' AND status=0 AND processafter='{$this->processafter}'")) {
            Logger::Log('JOB_SKIP_PROCESSING', Logger::SEVERITY['INFO']);
            sleep(random_int(5,15)); // It looks like DB Read Replica is behind master, sleep for 5-15 seconds before processing next job.
            return;
        } //Could not get hold of the job as it was already in process

        // Set execution time to 300 seconds. This is actual CPU consumption time. Default is 30 seconds
        // We are doing this to allow jobs enough time to finish processing.
        set_time_limit(300);

        switch ($this->jobsubtype) {
            case self::SUBTYPE_CREATE:
                $this->processAsCreateType();
                break;

            case self::SUBTYPE_UPDATE:
                $this->processAsUpdateType();
                break;

            case self::SUBTYPE_DELETE:
                $this->processAsDeleteType();
                break;

            case self::SUBTYPE_REMIND:
                $this->processAsRemindType();
                break;

            case self::SUBTYPE_FOLLOWUP:
                $this->processAsFollowupType();
                break;

            case self::SUBTYPE_COMPLETE:
                $this->processAsCompleteType();
                break;

            case self::SUBTYPE_CLEANUP:
                $this->processAsCleanupType();
                break;

            case self::SUBTYPE_PERPETUAL:
                $this->processAsPerpetualType();
                break;
        }
        //self::DBUpdate("DELETE from jobs where jobid='{$this->jobid}'");

        if ($this->jobsubtype == self::SUBTYPE_PERPETUAL) {

            // This is perpetual job and if the job falls multiple 'delays' behind current time then the job will run
            // multiple times in until processafter > now() time. In order to avoid the job to be run multiple times
            // we use update the delay by $delay_multiple so to recover from fall behind time.
            $process_after_epoch = intval(strtotime($this->processafter . ' UTC') ?? time());
            $now_epoch = time();
            $delay_multiple = 1;
            if ($now_epoch > $process_after_epoch) {
                $delay_multiple = intval(($now_epoch - $process_after_epoch) / $this->delay) + 1;
            }
            $delay = $delay_multiple * $this->delay;

            self::DBUpdate("UPDATE jobs SET status=0, processedby='',createdon=now(), processafter=(processafter+ INTERVAL {$delay} second) WHERE jobid='{$this->jobid}' AND status=1");
        } else {
            self::DBUpdate("UPDATE jobs SET status=100, processedon=now(), processedby='SYSTEM CRON' WHERE jobid='{$this->jobid}' AND status=1");
        }
    }

    public static function GetNextJob(): array
    {
        // Note: ORDER BY FIELD will put all the unspecified field values at the highest order followed by specfied order.
        // We are specifying this order so that the high volume group member join type jobs do not crowd out time
        // sensitive Event Type jobs.
        //return self::DBROGet('SELECT * FROM jobs WHERE status=0 AND processafter < now() ORDER BY FIELD(jobtype,4,2,3,5,6),processafter LIMIT 1','');
        return self::DBROGet('SELECT * FROM jobs WHERE status=0 AND processafter < now() ORDER BY jobtype,processafter LIMIT 1','');
    }

    /**
     * Calendar .ics needs commas and semi-colons to be quoted in the fields
     * @param string $txt
     * @return string
     */
    public function calQuote(string $txt)
    {
        return (string)str_replace(array(',', ';'), array('\,', '\;'), $txt);
    }

    /**
     * @param int $companyid
     * @param int $type
     * @return array
     * @deprecated This method is only for Super Admin use
     */
    public static function GetJobsByType (int $companyid, int $type)
    {
        return self::DBROGet("SELECT * FROM `jobs` WHERE `companyid`={$companyid} AND `jobtype`={$type}");
    }

    /**
     * @param int $companyid
     * @param string $jobid
     * @return mixed|null
     * @deprecated This method is only for Super Admin use.
     */
    public static function GetJob(int $companyid, string $jobid)
    {
        $row = null;
        // Note this Job should use DBGet from RW database and not the RO database it its purpose is to allow users
        // to see latest copy of the job.
        $j =  self::DBGet("SELECT * FROM `jobs` WHERE `companyid`={$companyid} AND `jobid`='{$jobid}'");
        if (!empty($j)){
            $row = $j[0];
        }
        return $row;
    }

    /**
     * @param int $companyid
     * @param string $jobid
     * @return int
     * @deprecated This method is only for Super Admin use
     */
    public static function DeleteScheduledJob(int $companyid, string $jobid): int
    {
        return self::DBUpdatePS("UPDATE jobs SET `status`=100, processedby='DELETED', processedon=now() WHERE `companyid`=? AND `jobid`=?", 'ix', $companyid, $jobid);
    }

    /**
     * @param int $companyid
     * @param string $jobid
     * @return int
     * @deprecated This method is only for Super Admin use
     */
    public static function RerunScheduledJob(int $companyid, string $jobid): int
    {
        $job_details = self::DBROGetPS("SELECT * FROM `jobs` WHERE companyid=? AND `jobid`=?", 'ix', $companyid, $jobid);
        $repeat_days = 0;
        if (!empty($job_details)){
            $job_details = $job_details[0];
            if (!in_array($job_details['jobtype'], [self::TYPE_DATA_EXPORT, self::TYPE_DATA_IMPORT, self::TYPE_USERSYNC_FILE])){
                return 0; // Only export, import or sync jobs can be rerun
            }
            $details_field = Arr::Json2Array($job_details['details']);
            if (!isset($details_field['RepeatDays'])){
                return 0; // invalid job type
            }
            $repeat_days = (int)$details_field['RepeatDays'];
        }
        return self::DBUpdatePS("UPDATE jobs SET processedby='', processafter=processafter - INTERVAL ? day WHERE `companyid`=? AND `jobid`=? AND status=0", 'iix', $repeat_days, $companyid, $jobid);
    }
}

function fetchAndProcessAJob()
{
    global $_COMPANY, $_USER, $_ZONE, $_LOGGER_META_JOB, $_JOB;
    /* @var Company $_COMPANY */

    if (Config::Get('GR_TEST_MODE_ENABLED') == 1) { // Do not run jobs if GR_TEST mode is enabled.
        return '000NONE000';
    }

    $job = NULL;
    $nextjob = Job::GetNextJob();
    $job_start_time = floor(microtime(true) * 1000);
    $_LOGGER_META_JOB = [];

    if (count($nextjob) > 0) {
        $_COMPANY = null; // reset to null for the next job
        $_ZONE = null; // reset to null for the next job
        $_USER = null; // reset to null for the next job
        $jobid = $nextjob[0]['jobid'];
        $jobid_pieces = explode('_', $jobid);
        $instance = -1;

        if ($nextjob[0]['companyid'] === $jobid_pieces[1]) { //Security check
            $_COMPANY = ((int)$nextjob[0]['companyid']) ? Company::GetCompany($nextjob[0]['companyid']) : null;
            $_ZONE    = ((int)$nextjob[0]['zoneid']) ? $_COMPANY->getZone((int)$nextjob[0]['zoneid']) : null;
            $_USER    = ((int)$nextjob[0]['createdby']) ? User::GetUser((int)$nextjob[0]['createdby']) : null;

            $_LOGGER_META_JOB = [
                'jobid' => $nextjob[0]['jobid'],
                'processafter' => $nextjob[0]['processafter'],
            ];

            switch ($nextjob[0]['jobtype']) {
                case Job::TYPE_EVENT:
                    $job = new EventJob($jobid_pieces[2], $jobid_pieces[3]);
                    $instance = (int)$jobid_pieces[4];
                    break;

                case Job::TYPE_GROUP:
                    $job = new GroupJob($jobid_pieces[2]);
                    break;

                case Job::TYPE_GROUPMEMBER:
                    $job = new GroupMemberJob($jobid_pieces[2], $jobid_pieces[3], $jobid_pieces[4]);
                    break;

                case Job::TYPE_POST:
                    $job = new PostJob($jobid_pieces[2], $jobid_pieces[3]);
                    $instance = (int)$jobid_pieces[4];
                    break;

                case Job::TYPE_DISCUSSION:
                    $job = new DiscussionJob($jobid_pieces[2], $jobid_pieces[3]);
                    $instance = (int)$jobid_pieces[4];
                    break;

                case Job::TYPE_POSTLIKE:
                    $job = new PostLikeJob($jobid_pieces[2], $jobid_pieces[3]);
                    break;

                case Job::TYPE_POSTCOMMENT:
                    $job = new PostCommentJob($jobid_pieces[2], $jobid_pieces[3], $jobid_pieces[4]);
                    break;

                case Job::TYPE_MESSAGE:
                    $job = new MessageJob($jobid_pieces[2], $jobid_pieces[3], $jobid_pieces[4]);
                    break;

                case Job::TYPE_NEWSLETTER:
                    $job = new NewsletterJob($jobid_pieces[2], $jobid_pieces[3], $jobid_pieces[4]);
                    $instance = (int)$jobid_pieces[5];
                    break;

                case Job::TYPE_SURVEY:
                    $job = new SurveyJob($jobid_pieces[2], $jobid_pieces[3], $jobid_pieces[4]);
                    $instance = (int)$jobid_pieces[5];
                    break;

                case Job::TYPE_SURVEY_RESPONSE:
                    $job = new SurveyResponseJob($jobid_pieces[3], $jobid_pieces[4]);
                    $instance = (int)$jobid_pieces[5];
                    break;

                case Job::TYPE_DATA_EXPORT:
                    $job = new DataExportJob();
                    break;

                case Job::TYPE_DATA_IMPORT:
                    $job = new DataImportJob();
                    break;

                case Job::TYPE_USERSYNC_FILE:
                    $job = new UserSyncFileJob();
                    break;

                case Job::TYPE_USERSYNC_365:
                    $job = new UserSync365Job();
                    break;

                case Job::TYPE_DBM:
                    $job = new DBMJob();
                    break;

                case Job::TYPE_WATCH:
                    $job = new WatchJob();
                    break;

                case Job::TYPE_BATCH_HOURLY:
                    $job = new BatchHourlyJob();
                    break;

                case Job::TYPE_BATCH_DAILY:
                    $job = new BatchDailyJob();
                    break;

                case Job::TYPE_BATCH_WEEKLY:
                    $job = new BatchWeeklyJob();
                    break;

                case Job::TYPE_BATCH_MONTHLY:
                    $job = new BatchMonthlyJob();
                    break;
                case Job::TYPE_TEAM:
                    $job = new TeamJob($jobid_pieces[2], $jobid_pieces[3]);
                    $instance = (int)$jobid_pieces[4];
                    break;

                case Job::TYPE_S3_CLEANUP:
                    $job = new S3CleanupJob();
                    break;
                case Job::TYPE_ZONEMEMBER:
                    $job = new ZoneMemberJob($jobid_pieces[2]);
                    break;

                case Job::TYPE_POINTS_CREDIT:
                    $job = new PointsCreditJob($jobid_pieces[2]);
                    break;
            }
        }

        if ($job) {
            $_JOB = $job;

            // We use fixParametersPriorToProcessing as we do not have access to protected attributes outside the class.
            $job->fixParametersPriorToProcessing($jobid, $nextjob[0]['processafter'], (int)$nextjob[0]['jobsubtype'], $nextjob[0]['details'],$nextjob[0]['options'],$instance);
            $job->process();
            $_LOGGER_META_JOB['jobtime'] = floor(microtime(true) * 1000) - $job_start_time;
            Logger::Log("JOB_DONE", Logger::SEVERITY['INFO']);

            $_JOB = null;
            return $jobid;
        } else {
            $_LOGGER_META_JOB['jobtime'] = floor(microtime(true) * 1000) - $job_start_time;
            Logger::Log("JOB_DONE");
            return '000-ERROR-001';
        }
    }
    return '000NONE000';
}

// Add all subclasses of Job.php
require_once __DIR__ .'/DBMJob.php';
require_once __DIR__ .'/WatchJob.php';
require_once __DIR__ .'/DataExportJob.php';
require_once __DIR__ .'/DataImportJob.php';
require_once __DIR__ .'/EventJob.php';
require_once __DIR__ .'/GroupJob.php';
require_once __DIR__ .'/GroupMemberJob.php';
require_once __DIR__ .'/MessageJob.php';
require_once __DIR__ .'/NewsLetterJob.php';
require_once __DIR__ .'/PostCommentJob.php';
require_once __DIR__ .'/PostJob.php';
require_once __DIR__ .'/PostLikeJob.php';
require_once __DIR__ .'/SurveyJob.php';
require_once __DIR__ .'/SurveyResponseJob.php';
require_once __DIR__ .'/UserSync365Job.php';
require_once __DIR__ .'/UserSyncFileJob.php';
require_once __DIR__ .'/BatchHourlyJob.php';
require_once __DIR__ .'/BatchDailyJob.php';
require_once __DIR__ .'/BatchWeeklyJob.php';
require_once __DIR__ .'/BatchMonthlyJob.php';
require_once __DIR__ .'/TeamJob.php';
require_once __DIR__ .'/DiscussionJob.php';
require_once __DIR__ .'/S3CleanupJob.php';
require_once __DIR__ .'/ZoneMemberJob.php';
require_once __DIR__ .'/PointsCreditJob.php';
