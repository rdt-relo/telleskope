<?php

class DBMJob extends Job
{

    public function __construct()
    {
        parent::__construct();
        $this->jobid = 'DBM_' . microtime(TRUE);
        $this->delay = 86400; //In seconds
        $this->jobtype = self::TYPE_DBM;
    }
    // Job can be seeded as
    //  insert into jobs (jobid,jobtype,jobsubtype,companyid,createdby,createdon,status,processafter,details,options) values ("DBM_0_0_0_0", 127,127,0,0,now(),0,(now() + interval 1 minute),null,null);

    protected function processAsPerpetualType()
    {
        //self::DBUpdate("SELECT * FROM jobs WHERE status=0 AND processafter < now() ORDER BY createdon ASC LIMIT 1");
        $start = hrtime(true);

        self::DBUpdate('DELETE FROM notifications WHERE datetime < (now() - interval 90 day)');
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("DBMJob: Deleted notifications older than 90 days (in {$eta} seconds)", Logger::SEVERITY['INFO']);

        self::DBUpdate('DELETE FROM jobs WHERE createdby !=0 AND status=100 AND createdon < (now() - interval 30 day)');
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("DBMJob: Deleted processed jobs older than 30 days (in {$eta} seconds)", Logger::SEVERITY['INFO']);

        self::DBUpdate('UPDATE companybranches, (SELECT homeoffice AS branchid,COUNT(1) AS employees FROM users GROUP BY homeoffice) AS temp_tbl SET companybranches.employees = temp_tbl.employees WHERE  companybranches.branchid = temp_tbl.branchid');
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("DBMJob: Updated employee count for company branches (in {$eta} seconds)", Logger::SEVERITY['INFO']);

        $companyStatistics = new CompanyStatistics();
        $companyStatistics->generateStatistics();
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("DBMJob: Generated Company Statistics  (in {$eta} seconds)", Logger::SEVERITY['INFO']);

        $zoneStatistics = new ZoneStatistics();
        $zoneStatistics->generateStatistics();
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("DBMJob: Generated Zone Statistics (in {$eta} seconds)", Logger::SEVERITY['INFO']);

        $groupStatistics = new GroupStatistics();
        $groupStatistics->generateStatistics();
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("DBMJob: Generated Group Statistics (in {$eta} seconds)", Logger::SEVERITY['INFO']);

        $eventCounters = new EventCounters();
        $eventCounters->generateStatistics();
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("DBMJob: Generated Event Counters (in {$eta} seconds)", Logger::SEVERITY['INFO']);

        // Jobs that are run at other times
        $today = date('Y-m-d');
        $lastSaturdayOfThisMonth = date("Y-m-d", strtotime("last saturday of this month"));
        if ($today === $lastSaturdayOfThisMonth) {
            // Jobs executed on last saturday of month.
        }
    }
}