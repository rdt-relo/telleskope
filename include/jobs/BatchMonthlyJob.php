<?php

class BatchMonthlyJob extends Job
{

    public function __construct()
    {
        parent::__construct();
        $this->jobid = 'BMY_' . microtime(TRUE);
        $this->delay = 86400*30; //In seconds
        $this->jobtype = self::TYPE_BATCH_DAILY;
    }
    // Job can be seeded as
    //  insert into jobs (jobid,jobtype,jobsubtype,companyid,createdby,createdon,status,processafter,details,options) values ("BMY_0_0_0_0", 122,127,0,0,now(),0,(now() + interval 1 minute),'',null);

    protected function processAsPerpetualType()
    {
        global $_COMPANY, $_ZONE;
        $start = hrtime(true);

        // Write action blocks here ... block1
        // See how we are writing blocks in BatchDailyJob for inspiration

        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;

        //Logger::Log("BatchMonthlyJob: {Completed some action} (in {$eta} seconds)", Logger::SEVERITY['INFO']);
    }
}