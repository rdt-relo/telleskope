<?php

class UserSync365Job extends Job
{
    public function __construct()
    {
        parent::__construct();
        $this->jobid = "USERSYNC365_{$this->cid}_0_0_{$this->instance}_" . microtime(TRUE);
        $this->jobtype = self::TYPE_USERSYNC_365;
        $this->batch_maximum_count = 15; // For sync jobs, increasing the batches to 15.
    }

    public function __clone()
    {
        parent::__clone();
        $this->jobid = "USERSYNC365_{$this->cid}_{$this->createdby}_0_{$this->instance}_" . microtime(TRUE);
    }

    /**
     * @param string $token
     * @param array $o365_customization
     * @param int $sync_days
     */
    public function saveAsUserSyncUpdateType(string $token, array $o365_customization, int $sync_days)
    {
        if (!$sync_days) {
            return;
        }

        $this->details = json_encode(array('token' => $token, 'o365_customization' => $o365_customization, 'sync_days' => $sync_days));
        $existingJobs = self::DBGet("SELECT `jobid` FROM `jobs` WHERE companyid='{$this->cid}' AND jobtype='{$this->jobtype}' AND status != '100' AND createdon > now() - INTERVAL 10 MINUTE LIMIT 1");

        $total_syncs = 0;
        if (count($existingJobs) === 0) {
        $total_syncs = self::DBGet("SELECT count(1) AS count FROM users WHERE companyid='{$this->cid}' AND isactive=1 AND (validatedon < now() - interval {$sync_days} day)")[0]['count'];
        }

        if ($total_syncs > 0) {
            $offset_size = $this->batch_minimum_size;
            if ($total_syncs > ($this->batch_minimum_size * $this->batch_maximum_count)) { //recalculate batch size
                $offset_size = (int)($total_syncs / ($this->batch_maximum_count - 1));
            }

            for ($offset = 0; $offset < $total_syncs; $offset = $offset + $offset_size) {
                $copy = clone $this;
                $copy->delay = ((int)($offset / $offset_size)) * $this->batch_stagger_seconds;
                $copy->saveAsUpdateType();
            }
        }
    }

    protected function processAsUpdateType()
    {
        $instance = (int)explode('_', $this->jobid)[4];
        $delete_users = $instance === 1; // Only the prime job can delete users, not the clones
        $detail = json_decode($this->details, true);
        User::syncUsers365($detail['token'], $detail['o365_customization'], $this->cid, $delete_users, $detail['sync_days'], $instance-1);
    }
}