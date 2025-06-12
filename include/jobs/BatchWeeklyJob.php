<?php

class BatchWeeklyJob extends Job
{

    public function __construct()
    {
        parent::__construct();
        $this->jobid = 'BWY_' . microtime(TRUE);
        $this->delay = 86400*7; //In seconds
        $this->jobtype = self::TYPE_BATCH_DAILY;
    }
    // Job can be seeded as
    //  insert into jobs (jobid,jobtype,jobsubtype,companyid,createdby,createdon,status,processafter,details,options) values ("BWY_0_0_0_0", 121,127,0,0,now(),0,(now() + interval 1 minute),'',null);

    protected function processAsPerpetualType()
    {
        global $_COMPANY, $_ZONE;
        $start = hrtime(true);

        // Write action blocks here ... block1

        //Delete Group invites after 90days
        $_COMPANY = null;
        $_ZONE = null;
        $clean = Group::CleanGroupInvitesAfterNintyDays();
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;

        Logger::Log("BatchWeeklyJob: CleanGroupInvitesAfterNintyDays (in {$eta} seconds)", Logger::SEVERITY['INFO']);

        //Delete Ephemeral attachments after 30 days
        $_COMPANY = null;
        $_ZONE = null;
        $clean = EphemeralTopic::DeleteExpiredEphemeralTopics();
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;

        Logger::Log("BatchWeeklyJob: DeleteExpiredEphemeralTopics (in {$eta} seconds)", Logger::SEVERITY['INFO']);
    }
}
