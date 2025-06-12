<?php

/**
 * Watch job is simple job that looks up the database to check if
 * (a) The requried jobs are configured
 * (b) If one of the jobs go stuck.
 */
class WatchJob extends Job
{

    public function __construct()
    {
        parent::__construct();
        $this->jobid = 'WATCH_' . microtime(TRUE);
        $this->delay = 300; //Runs every 5 minutes
        $this->jobtype = self::TYPE_DBM;
    }
    // Job can be seeded as
    //  insert into jobs (jobid,jobtype,jobsubtype,companyid,createdby,createdon,status,processafter,details,options) values ("WATCH_0_0_0_0", 100,127,0,0,now(),0,(now() + interval 1 minute),'',null);

    protected function processAsPerpetualType()
    {
        // Check if the required jobs are configured
        // Check if the hourly job is configured
        $j = self::DBROGet('SELECT jobid FROM jobs WHERE status IN (0,1) AND jobtype=' . self::TYPE_BATCH_HOURLY);
        if (empty($j)) {
            Logger::Log('JOB MISSING: hourly Job', Logger::SEVERITY['ALARM']);
        }

        // Check if the daily job is configured
        $j = self::DBROGet('SELECT jobid FROM jobs WHERE status IN (0,1) AND jobtype=' . self::TYPE_BATCH_DAILY);
        if (empty($j)) {
            Logger::Log('JOB MISSING: Daily Job', Logger::SEVERITY['ALARM']);
        }

        // Check if the weekly job is configured
        $j = self::DBROGet('SELECT jobid FROM jobs WHERE status IN (0,1) AND jobtype=' . self::TYPE_BATCH_WEEKLY);
        if (empty($j)) {
            Logger::Log('JOB MISSING: Weekly Job', Logger::SEVERITY['ALARM']);
        }

        // Check if the monthly job is configured
        $j = self::DBROGet('SELECT jobid FROM jobs WHERE status IN (0,1) AND jobtype=' . self::TYPE_BATCH_MONTHLY);
        if (empty($j)) {
            Logger::Log('JOB MISSING: Monthly Job', Logger::SEVERITY['ALARM']);
        }

        // Check if the DBM job is configured
        $j = self::DBROGet('SELECT jobid FROM jobs WHERE status IN (0,1) AND jobtype=' . self::TYPE_DBM);
        if (empty($j)) {
            Logger::Log('JOB MISSING: DBM Job', Logger::SEVERITY['ALARM']);
        }

        // Check if there are stuck jobs.
        // Jobs are stuck if they have been in the status = 1 for over an hour
        $j = self::DBROGet('SELECT jobid, IFNULL(TIME_TO_SEC(now()-processedon),0) elapsed_time FROM jobs WHERE status=1 AND processedon < now() - interval 1 hour');
        if (!empty($j)) {
            Logger::Log('JOB STUCK: '. count($j), Logger::SEVERITY['ALARM'],
                array_combine(array_column($j, 'jobid'), array_column($j, 'elapsed_time'))
            );
        }

        // Check if there are too many queued jobs. too many > 300 since 15 minute interval
        $jc = self::DBROGet('SELECT count(1) AS JC FROM jobs WHERE status=0 AND processafter < now() - interval 15 minute')[0]['JC'];
        Logger::Log('JOB QUEUE SIZE', Logger::SEVERITY['AUDIT'], ['queue_size' => $jc]);
        if ($jc > 300) {
            Logger::Log('JOB QUEUE SIZE THRESHOLD CROSSED', Logger::SEVERITY['ALARM'], ['queue_size' => $jc]);
        }
    }
}