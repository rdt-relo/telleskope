<?php

class GroupJob extends Job
{
    public $groupid;

    public function __construct($gid)
    {
        parent::__construct();
        $this->groupid = $gid;
        $this->jobid = "GRP_{$this->cid}_{$gid}";
        $this->jobtype = self::TYPE_GROUP;
    }
}