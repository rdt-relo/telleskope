<?php

class PointsCreditJob extends Job
{
    public function __construct(int $zid)
    {
        parent::__construct();
        // PTSCREDIT - Points Credit
        $this->zoneid = $zid;
        $this->jobid = "PTSCREDIT_{$this->cid}_{$zid}_" . microtime(TRUE);
        $this->jobtype = self::TYPE_POINTS_CREDIT;
    }

    public function saveJob(string $action)
    {
        $this->details = json_encode(['action' => $action]);
        parent::saveAsCreateType();
    }

    protected function processAsCreateType()
    {
        global $_ZONE;
        $details = json_decode($this->details, true);
        $action = $details['action'];

        switch ($action) {
            case 'credit_points_to_members':
                $users = User::GetAllUsersInZoneWhoAreNotMarkedForDeletion($_ZONE->id());
                foreach ($users as $user) {
                    Points::CreditPointsToMember($user['userid']);
                }

                $this->notifyJobScheduler($action);
                return;

            case 'credit_points_group_leads':
                $users = User::GetAllUsersInZoneWhoAreNotMarkedForDeletion($_ZONE->id());
                foreach ($users as $user) {
                    Points::CreditPointsToGroupLead($user['userid']);
                }

                $this->notifyJobScheduler($action);
                return;
        }
    }

    private function notifyJobScheduler($action): void
    {
        global $_COMPANY, $_ZONE, $_USER;

        $email_details = EmailHelper::PointsCreditDailyEarningsNotification($action, $_USER->getFullName());
        $subject = $email_details['subject'];
        $message = $email_details['message'];
        $to_addr = $_USER->val('email');
        $_COMPANY->emailSend2('', $to_addr, $subject, $message, $_ZONE->val('app_type'),'');
    }

}
