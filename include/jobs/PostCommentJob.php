<?php

class PostCommentJob extends Job
{
    public $groupid;
    public $postid;
    public $commentid;

    public function __construct($gid, $pid, $pcid)
    {
        parent::__construct();
        $this->groupid = $gid;
        $this->postid = $pid;
        $this->commentid = $pcid;
        $this->jobid = "PCM_{$this->cid}_{$gid}_{$pid}_{$pcid}";
        $this->jobtype = self::TYPE_POSTCOMMENT;
    }

    public function saveAsCreateType()
    {
        //if ($this->groupid) { // Send notifications for comments only on group specific posts
        $data = self::DBGet("SELECT `chapterid`,`channelid` FROM `post` WHERE `companyid`='{$this->cid}' AND `postid`='{$this->postid}'");
        $useridArr = array();
        $chapteridsArray = empty($data[0]['chapterid']) ? array(0) : explode(',', $data[0]['chapterid']);
        foreach ($chapteridsArray as $chapterid) {
            $useridArr = array_merge($useridArr, explode(',', Group::GetGroupMembersAsCSV($this->groupid, $chapterid, $data[0]['channelid'], 'P')));
        }
        $useridArr = array_values(array_filter(array_unique($useridArr)));

        $useridList = implode(',', $useridArr);
        $this->jobid = $this->jobid . '_' . microtime(TRUE);
        $this->details = $useridList;
        parent::saveAsCreateType();
        //}
    }

    protected function processAsCreateType()
    {
        global $_COMPANY, $_ZONE;
        /* @var Company $_COMPANY */

        $groupname = 'All';
        $from = '';
        $g1 = array();
        if ($this->groupid) {
            $g1 = self::DBGet("SELECT groupname, from_email_label FROM `groups` WHERE groupid='{$this->groupid}' AND companyid='{$this->cid}'");
            if (count($g1) <= 0) {
                return;
            } //no matching valid group found
            $groupname = $g1[0]['groupname'];
            $from = $g1[0]['from_email_label'];
        }
        $who = User::GetUser($this->createdby);

        // Validate the post exists and is not too old
        $posts = self::DBGet("SELECT * FROM post WHERE postid='{$this->postid}' AND companyid='{$this->cid}' AND groupid='{$this->groupid}' AND isactive=1");

        if (!empty($g1)) {
            //$from = $group->getFromEmailLabel($posts[0]['chapterid'], $posts[0]['channelid']);
        }

        if (count($posts) <= 0) {
            return;
        } //no matching valid event found

        $members = empty($this->details) ? array() : explode(',', $this->details);
        if (count($members) <= 0) {
            return;
        } //no users to notify

        $posttitle = $posts[0]['title'];

        if (($postcomment = Post::GetPostComment($this->commentid)) === NULL) {
            return;
        }

//        $comment = $postcomment->val('comment');
//        if (strlen($comment) > 500) {
//            $comment = substr($comment, 0, 500) . '...';
//        }

        $message = 'commented on ' . self::mysqli_escape($posttitle) . ' in ' . self::mysqli_escape($groupname);
        //$msg = $who->getFullName() . ' ' . $message;

        for ($i = 0; $i < count($members); $i++) {
            $mid = $members[$i];
            if (empty($mid) || ($touser = User::GetUser((int)$mid)) === NULL || !$touser->isActive())
                continue;
            //$insertNoti = self::DBInsert("INSERT INTO `notifications`(`zoneid`,`section`, `userid`, `whodo`, `tableid`, `message`, `datetime`, `isread`) VALUES ('{$_ZONE->id()}', '4','" . $mid . "','" . $this->createdby . "','" . $this->commentid . "','" . $message . "',now(),'2')");

            // Push Notification
//            $setting = $db->GetUsersAnything($mid, 'notification');
//            if ($setting == 1 && $_COMPANY->getAppCustomization()['mobileapp']['enabled']) {
//                $users = self::DBGet("SELECT `id`, `userid`, `devicetype`, `devicetoken` FROM `session` WHERE `userid`='" . $mid . "' and devicetoken!=''");
//                if (count($users) > 0) {
//                    $badge = (int)$db->getNotificationCount($mid);
//                    for ($d = 0; $d < count($users); $d++) {
//                        $db->sendCommonPushNotification($users[$d]['devicetoken'], $msg, $badge, self::PUSH_NOTIFICATIONS_STATUS['POST_COMMENTS'], $this->postid, $insertNoti, $_ZONE->id());
//                    }
//                }
//            }

        }
    }
}