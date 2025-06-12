<?php

class BatchDailyJob extends Job
{

    public function __construct()
    {
        parent::__construct();
        $this->jobid = 'BDY_' . microtime(TRUE);
        $this->delay = 86400; //In seconds
        $this->jobtype = self::TYPE_BATCH_DAILY;
    }
    // Job can be seeded as
    //  insert into jobs (jobid,jobtype,jobsubtype,companyid,createdby,createdon,status,processafter,details,options) values ("BDY_0_0_0_0", 120,127,0,0,now(),0,(now() + interval 1 minute),'',null);

    protected function processAsPerpetualType()
    {
        global $_COMPANY, $_ZONE;
        $start = hrtime(true);

        // Write action blocks here ... block1
        $_COMPANY = null;
        $_ZONE = null;
        $setInactivityNotifications = Team::SetTeamVariousNotificationJobs();
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("BatchDailyJob: SetTeamVariousNotificationJobs (in {$eta} seconds)", Logger::SEVERITY['INFO']);

        // Write action blocks here ... block2
        $_COMPANY = null;
        $_ZONE = null;
        $sartOrResetNetworkingTeamSetting = Team::RecycleNetworkingTeams();
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("BatchDailyJob: RecycleNetworkingTeams (in {$eta} seconds)", Logger::SEVERITY['INFO']);

        // Write action blocks here ... block3
        $_COMPANY = null;
        $_ZONE = null;
        $d = UserInbox::DeleteOldUserInboxMessages();
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("BatchDailyJob: DeleteOldUserInboxMessages (in {$eta} seconds)", Logger::SEVERITY['INFO']);

        // Write action blocks here ... block4
        $_COMPANY = null;
        $_ZONE = null;
        $sendGroupAnniversaryEmails = Group::SendGroupAnniversaryEmails();
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("BatchDailyJob: $sendGroupAnniversaryEmails (in {$eta} seconds)", Logger::SEVERITY['INFO']);

        // Remove pins from past events
        $_COMPANY = null;
        $_ZONE = null;
        $status = Event::UnpinPastEvents();
        $end = hrtime(true);    
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("BatchDailyJob: UnpinPastEvents (in {$eta} seconds)", Logger::SEVERITY['INFO']);

         // Event Reconciliation reminders
        $_COMPANY = null;
        $_ZONE = null;
        $sendEventPostReminders = Event::EventCompletionFollowup(3);
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("BatchDailyJob: sendEventReconciliationReminder (in {$eta} seconds)", Logger::SEVERITY['INFO']);

        // Auto complete team after n days
        $_COMPANY = null;
        $_ZONE = null;
        $autoCompleteTeamAfterNDays = Team::AutoCompleteTeamAfterNDays();
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("BatchDailyJob: AutoCompleteTeamAfterNDays (in {$eta} seconds)", Logger::SEVERITY['INFO']);

        // Auto complete team on mentee start date
        $_COMPANY = null;
        $_ZONE = null;
        $autoCompleteTeamOnMenteeStartDate = Team::AutoCompleteTeamOnMenteeStartDate();
        $end = hrtime(true);
        $eta = ($end - $start) / 1e+9;
        $start = $end;
        Logger::Log("BatchDailyJob: AutoCompleteTeamOnMenteeStartDate (in {$eta} seconds)", Logger::SEVERITY['INFO']);
    }
}