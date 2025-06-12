<?php

class PostLikeJob extends Job
{
    public $groupid;
    public $postid;

    public function __construct($gid, $pid)
    {
        parent::__construct();
        $this->groupid = $gid;
        $this->postid = $pid;
        $this->jobid = "PLK_{$this->cid}_{$gid}_{$pid}";
        $this->jobtype = self::TYPE_POSTLIKE;
    }
}