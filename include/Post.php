<?php

// Do no use require_once as this class is included in Company.php.

    class Post extends Teleskope {

	protected $attachments;
	protected $comments;

	protected function __construct($id,$cid,$fields) {
		parent::__construct($id,$cid,$fields);
		//declaring it protected so that no one can create it outside this class.
		$this->attachments = NULL;
	}

    // Add traits right after the constructor
    public static function GetTopicType():string {return self::TOPIC_TYPES['POST'];}
    use TopicLikeTrait;
    use TopicCommentTrait;
    use TopicApprovalTrait;
    use TopicAttachmentTrait;
    use TopicCustomFieldsTrait;

    /**
     * Function required by Topics .
     * @return string
     */
    public function getTopicTitle(): string
    {
        return $this->val('title');
    }

    /**
     * Gets the Post object for the matching id.
     * @param int $id of the Post object to fetch
     * @return Post|null
     */
	public static function GetPost(int $id) {
		$obj = null;
        global $_COMPANY; /* @var Company $_COMPANY */
			//  AND isactive=1
		$r1 = self::DBGet("SELECT * FROM post WHERE postid='{$id}' AND companyid = '{$_COMPANY->id()}'");

		if (count($r1)) {
			$obj = new Post($id, $_COMPANY->id(), $r1[0]);
		} else {
			Logger::Log("Post: GetPost({$id}) failed. Context (Company={$_COMPANY->id()})", Logger::SEVERITY['INFO']);
		}
		return $obj;
	}

	public static function GetCustomName(bool $defaultNamePlural = false){
		global $_COMPANY, $_ZONE, $_USER;
		if ($defaultNamePlural){
			$name = gettext('Announcements');
		} else {
			$name = gettext('Announcement');
		}

		$lang = $_USER->val('language') ?? 'en';
		$altNames = $_COMPANY->getAppCustomization()['post']['alt_name'] ?? array();

		if(!empty($altNames) && array_key_exists($lang,$altNames)){

			$altName  = $altNames[$lang];
			if (!empty($altName)){
				return $altName;
			}
		}
		return $name;

	}

    public static function GetPostFromCache (int $id) {
        global $_COMPANY;
        return self::GetPostByCompany($_COMPANY, $id);
    }
    public static function GetPostByCompany(Company $company, int $id) {
        $obj = null;
        $key = "POS:{$id}";
        if (($obj = $company->getFromRedisCache($key)) === false) {
            $obj = null; // Reset $obj to initial value
            $r1 = self::DBROGet("SELECT * FROM post WHERE postid='{$id}' AND companyid = '{$company->id()}'");
            if (!empty($r1)) {
                $obj = new Post($id, $company->id(), $r1[0]);
                $company->putInRedisCache($key, $obj, 300);
            }
        }
        return $obj;
    }

	public static function ConvertDBRecToPost (array $rec) : ?Post
    {
            global $_COMPANY;
            $obj = null;
            $p = (int)$rec['postid'];
            $c = (int)$rec['companyid'];
            if ($p && $c && $c === $_COMPANY->id())
                $obj = new Post($p, $c, $rec);
            return $obj;
	}

    public static function GetPostForPublishing(int $id, bool $do_state_change)
    {
        $obj = null;
        global $_COMPANY; /* @var Company $_COMPANY */
        $status_await = self::STATUS_AWAITING;
        $status_active = self::STATUS_ACTIVE;
        $status_under_review = self::STATUS_UNDER_REVIEW;

        if ($do_state_change) {
            $r1 = self::DBUpdate("UPDATE `post` SET isactive='{$status_active}' WHERE companyid = '{$_COMPANY->id()}' AND (postid='{$id}' AND isactive IN ({$status_await},{$status_active},{$status_under_review}) AND publishdate < now()+interval 10 second)");
            $_COMPANY->expireRedisCache("POS:{$id}");
            if ($r1) {
                $obj = self::GetPost($id);
                self::LogObjectLifecycleAudit('state_change', 'post', $obj->id(), $obj->val('version'), ['state' => 'published']);
            }
        } else {
            $attempts = 5;
            while ($attempts--) {
                $post = self::GetPost($id);
                if ($post->isActive()) {
                    $obj = $post; // It means the post has not been published yet.
                    break;
                }
                sleep(60); // Some other process needs to do state change, wait
            }
        }
        return $obj;
    }

	// added usertype for check on draft post
	public static function GetAllPostsInGroup(int $groupid,$companyLevel,$chapterid,$includeDraft,$channelid=0,$page = 0) {
		$objs = array();
        global $_COMPANY; /* @var Company $_COMPANY */
        global $_USER;  /* @var User $_USER */
        global $_ZONE;
		
		if($includeDraft) {
			$active = "AND `isactive` in (".self::STATUS_ACTIVE.",".self::STATUS_DRAFT.",".self::STATUS_AWAITING.")";
		}else{
			$active = "AND `isactive`='".self::STATUS_ACTIVE."'";
		}
		$condition = "AND groupid='{$groupid}'  AND isactive=1";
		if($companyLevel){
			$condition = "AND (groupid='{$groupid}' OR groupid = 0) AND isactive=".self::STATUS_ACTIVE." ";
		}
		if($chapterid>0){
			$chapterCondition	=	"AND FIND_IN_SET({$chapterid},chapterid)";
		} elseif ($chapterid == 0){ // ERG level only
			$chapterCondition = " AND chapterid=0";
		}else{
			$chapterCondition	=	"";
		}

		if($channelid>0){
			$channelCondition	=	"AND channelid='{$channelid}'";
		} elseif ($channelid == 0){
			$channelCondition	=	"AND channelid='0' ";
		} else {
			$channelCondition	=	"";
		}
		$limitCondition = "";

		if ($page){
			$start 		=	($page - 1)*30;
			$end  		=	30;
			$limitCondition  = "LIMIT $start, $end";
		}
		$rows = self::DBGet("SELECT * FROM post WHERE companyid = '{$_COMPANY->id()}' ".$condition." ".$channelCondition." ".$active." ".$chapterCondition." AND post.zoneid={$_ZONE->id()} 
		ORDER BY 
        pin_to_top DESC,
        CASE `pin_to_top` 
            WHEN 1 THEN modifiedon 
            ELSE publishdate 
        END
        DESC,
        postid DESC $limitCondition ");

		foreach ($rows as $row) {
			if (!$_COMPANY->getAppCustomization()['post']['show_global_posts_in_group_feed'])
				continue; //Remove the rows which are unpublished admin messages unless the user is Admin
			else
				$objs[] = new Post($row['postid'], $_COMPANY->id(), $row);
		}
		return $objs;
	}

	/**
     * @deprecated remove it
     */
	public static function GetPostComment(int $postcommentid)
    {
    }

    /**
     * @deprecated remove it
     */
	public function getComments($reload=false) {
	}

    /**
     * @deprecated remove it
     */
	public function addComment($comment) {
	}

	public function inactivateIt() {
        global $_COMPANY;
		$status = self::STATUS_INACTIVE;
		$retVal = self::DBMutate("UPDATE post SET isactive='{$status}' WHERE postid='{$this->id}'");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'post', $this->id(), $this->val('version'), ['state' => 'inactive']);
        }
        $_COMPANY->expireRedisCache("POS:{$this->id}");
        return $retVal;
	}

	public function deleteIt() {
        global $_COMPANY;

        $this->deleteAllAttachments();

        self::DeleteAllComments_2($this->id);
        self::DeleteAllLikes($this->id);
        $result =  self::DBMutate("DELETE FROM post WHERE companyid={$_COMPANY->id()} AND postid={$this->id}");

		if ($result) {
            self::LogObjectLifecycleAudit('delete', 'post', $this->id(), $this->val('version'));
            self::PostDelete($this->id);
        }
        return $result;
	}

	public function __toString() {
		return "Post ". parent::__toString();
	}

    public function updatePublishToEmail(int $flag) {
        global $_COMPANY;

        if (self::DBMutate("UPDATE `post` set `publish_to_email`={$flag} WHERE postid={$this->id}")) {
            $this->fields['publish_to_email'] = (string)$flag;
            self::LogObjectLifecycleAudit('state_change', 'post', $this->id(), $this->val('version'), ['publish_to_email' => 'true']);
        }

        $_COMPANY->expireRedisCache("POS:{$this->id}");
    }


	/**
	 * Create new post
	 */

	public static function CreateNewPost(int $groupid, string $title,string $post,string $chapterids,int $channelid, string $listids, string $custom_email_reply='',int $add_post_disclaimer=0, bool $use_and_chapter_connector = false) {
		global $_COMPANY;
		global $_USER;
		global $_ZONE;

        // Extract hashtag handles
        $handleids = HashtagHandle::ExtractAndCreateHandles($post);
		$handleidsCsv = implode(',',$handleids);

        ContentModerator::CheckBlockedWords($title, $post);

		$result = self::DBInsertPS("INSERT INTO `post`(`companyid`,`zoneid`,`groupid`, `userid`, `chapterid`, `title`, `post`,`add_post_disclaimer`,`postedon`, `modifiedon`, `isactive`,`channelid`,`handleids`,`content_replyto_email`,`listids`,`use_and_chapter_connector`) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW(),'2',?,?,?,?,?)",'iiiixsmsixsxi',$_COMPANY->id(),$_ZONE->id(),$groupid,$_USER->id(),$chapterids,$title,$post,$add_post_disclaimer,$channelid,$handleidsCsv,$custom_email_reply,$listids,($use_and_chapter_connector ? 1 : 0));

		if ($result) {
            self::LogObjectLifecycleAudit('create', 'post', $result, 1); 
        }
		return $result;
	}
	/**
	 * Update Saved Post
	 */
	 
	public static function UpdateSavedPost(int $postid, string $title, string $post, string $chapterids, int $channelid, string $listids, string $content_replyto_email='',int $add_post_disclaimer=0,bool $use_and_chapter_connector = false) {
		global $_COMPANY;
		
		$postObj = Post::GetPost($postid);
      
        // Extract hashtag handles
		$handleids = HashtagHandle::ExtractAndCreateHandles($post);
		$handleidsCsv = implode(',',$handleids);

        ContentModerator::CheckBlockedWords($title, $post);

		$retVal = self::DBUpdatePS("UPDATE `post` SET `title`=?,`post`=?,`add_post_disclaimer`=?,`chapterid`=?,`channelid`=?,`modifiedon`=now(),`version`=`version`+1,isactive=IF(isactive=3,2,isactive),`handleids`=?,`content_replyto_email`=?,`listids`=?,`use_and_chapter_connector`=? where `postid`=? and `companyid`=?",'smsxixsxiii',$title,$post,$add_post_disclaimer,$chapterids, $channelid,$handleidsCsv,$content_replyto_email,$listids,($use_and_chapter_connector ? 1 : 0),$postid,$_COMPANY->id());
        $_COMPANY->expireRedisCache("POS:{$postid}");

		if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'post', $postid, $postObj->val('version'));
        }
		return $retVal;
	}

	/**
	 * Update Published post
	 */

	public static function UpdatePublishedPost(int $postid, string $title, string $post,string $content_replyto_email='', int $add_post_disclaimer=0) {
		global $_COMPANY;

		
        $postObj = Post::GetPost($postid);
       	
        // Extract hashtag handles
        $handleids = HashtagHandle::ExtractAndCreateHandles($post);
		$handleidsCsv = implode(',',$handleids);

        ContentModerator::CheckBlockedWords($title, $post);

		$retVal = self::DBUpdatePS("UPDATE `post` SET `title`=?,`post`=?,`add_post_disclaimer`=?,`content_replyto_email`=?,`modifiedon`=now(),`version`=`version`+1,handleids=?  where `postid`=? and `companyid`=?",'smssxii',$title,$post,$add_post_disclaimer,$content_replyto_email,$handleidsCsv,$postid,$_COMPANY->id());
        $_COMPANY->expireRedisCache("POS:{$postid}");

        if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'post', $postid, $postObj->val('version')); 
        }
		return $retVal;
	}

	/**
	 * Get Global Posts
	 */

	public static function GetAllGlobalPosts($includeDraft) {
		$objs = array();
        global $_COMPANY; /* @var Company $_COMPANY */
		global $_USER;  /* @var User $_USER */
		global $_ZONE;
		
		if($includeDraft) {
			$active = "AND `isactive` in (".self::STATUS_ACTIVE.",".self::STATUS_DRAFT.",".self::STATUS_AWAITING.")";
		}else{
			$active = "AND `isactive`='".self::STATUS_ACTIVE."'";
		}
		
		return self::DBGet("SELECT * FROM post WHERE companyid = '{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' AND  groupid = 0 ".$active." ORDER BY postid DESC");

	}

	public static function GetAllPostInGroupByMonth(int $groupid,$month) {
		$objs = array();
        global $_COMPANY; /* @var Company $_COMPANY */
        global $_USER;  /* @var User $_USER */
        global $_ZONE;
		
		$rows = self::DBGet("SELECT * FROM post WHERE companyid = '{$_COMPANY->id()}' AND zoneid={$_ZONE->id()} AND groupid='{$groupid}' AND modifiedon LIKE '%".$month."%' AND isactive=1 ");
		usort($rows, function($a, $b) {
            return $a['postid'] <=> $b['postid'];
        });
		foreach ($rows as $row) {
			$objs[] = new Post($row['postid'], $_COMPANY->id(), $row);
		}
		return $objs;
	}

	public function pinUnpinAnnouncement(int $type) {
		global $_COMPANY;
		$pin_to_top = ($type == 1) ? 1 : 0;
		$retVal = self::DBUpdate("UPDATE `post` SET `pin_to_top`={$pin_to_top},modifiedon=NOW() where `companyid`={$_COMPANY->id()} AND `postid`={$this->id}");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'post', $this->id(), $this->val('version'), ['pin_to_top' => $pin_to_top]);
        }
        $_COMPANY->expireRedisCache("POS:{$this->id}");
        return $retVal;
	}

	public function updatePostUnderReview() {
        global $_COMPANY; /* @var Company $_COMPANY */
		
        $status_under_review = self::STATUS_UNDER_REVIEW;

        $retVal = self::DBUpdate("UPDATE `post` SET `isactive`='{$status_under_review}',`modifiedon`=NOW() WHERE `companyid` = '{$_COMPANY->id()}' AND `postid`='{$this->id()}' ");

		if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'post', $this->id(), $this->val('version'), ['state' => 'review']);
        }
		return $retVal;
    }

	public function updatePostForSchedulePublishing(int $delay) {
        global $_COMPANY,$_USER; /* @var Company $_COMPANY */
		
        $status_awaiting = self::STATUS_AWAITING;
        $retVal = self::DBUpdate("UPDATE `post` SET `isactive`='{$status_awaiting}',`publishdate`=NOW() + interval {$delay} second, `publishedby`='{$_USER->id()}' WHERE `companyid` = '{$_COMPANY->id()}' AND `postid`='{$this->id()}' ");

		if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'post', $this->id(), $this->val('version'), ['state' => 'awaiting publish', 'publishedby' => $_USER->id()]);
        }
		return $retVal;

    }

	public function cancelPostPublishing() {
        global $_COMPANY; /* @var Company $_COMPANY */
        $status_draft = PostJob::STATUS_DRAFT;
    	$status_awaiting = Post::STATUS_AWAITING;

        $retVal = self::DBUpdate("UPDATE `post` SET `isactive`='{$status_draft}',`publishdate`=null WHERE `companyid` = '{$_COMPANY->id()}' AND `postid`='{$this->id()}' AND  `isactive` = '{$status_awaiting}' ");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'post', $this->id(), $this->val('version'), ['state' => 'draft']);
        }
        return $retVal;
    }

        /**
         * @param int $groupid
         * @param int $globalChapterOnly if set then the announcements that match chapterid=0 are returned, $chapterid value is ignored.
         * @param int $chapterid
         * @param int $globalChannelOnly if set then the announcements that match channeld=0 are returned, $channelid value is ignored.
         * @param int $channelid
         * @param int $page
         * @param int $limit
         * @return array
         */
	public static function GetGroupPostViewData(int $groupid, int $globalChapterOnly, int $chapterid, int $globalChannelOnly, int $channelid, int $page, int $limit)
    {
        global $_COMPANY;
        global $_USER;
        global $_ZONE;
        global $db;

        $chapterCondition = " ";
        if ($globalChapterOnly) {
            $chapterCondition = " AND chapterid=0";
        } else {
            if ($chapterid > 0) {
                $chapterCondition = " AND FIND_IN_SET({$chapterid},`chapterid`)";
            }
        }

        $channelCondition = " ";
        if ($globalChannelOnly) {
            $channelCondition = " AND channelid=0";
        } else {
            if ($channelid > 0) {
                $channelCondition = " AND channelid='{$channelid}'";
            }
        }

        $active = "AND `isactive`=1";

        $inclueGlobalPosts = '';
        if ($_COMPANY->getAppCustomization()['post']['show_global_posts_in_group_feed']){
            $inclueGlobalPosts = " OR groupid = 0";
        }

        $max_items = $limit + 1; // We are adding 1 additional row to enable 'Show More to work'
        $start = (($page - 1) * $limit);
        return self::DBROGet("SELECT * FROM post WHERE companyid = {$_COMPANY->id()} AND post.zoneid={$_ZONE->id()} AND ((groupid={$groupid} $inclueGlobalPosts) {$chapterCondition} {$channelCondition} {$active} AND post.listids='0') 
        ORDER BY 
        pin_to_top DESC,
        CASE `pin_to_top` 
            WHEN 1 THEN modifiedon 
            ELSE publishdate 
        END
        DESC,
        postid DESC
        LIMIT {$start}, {$max_items}");
    }

    public function getTypesenseDocument(): array
    {
        return [
            'id' => $this->getTypesenseId(),
            'type' => $this->getTypesenseDocumentType(),
            'company_id' => (int) $this->val('companyid'),
            'zone_id' => (int) $this->val('zoneid'),
            'title' => $this->val('title'),
            'description' => Html::SanitizeHtml($this->val('post')),
            'group_id' => (int) $this->val('groupid'),
        ];
    }

    public function getHomeFeedData(): array
    {
        global $_COMPANY;

        $data = $this->toArray();

        $data = [
             'content_type' => 'post',
             'content_id' => $this->id(),
             'content_groupids' => $this->val('groupid'),
             'content_chapterids' => $this->val('chapterid'),
             'content_channelids' => $this->val('channelid'),
             'content_date' => $this->val('publishdate'),
             'content_pinned' => $this->val('groupid') == 0 ? $this->val('pin_to_top') : 0,
             'addedon' => $this->val('publishdate'),
         ] + $data;

         if ($data['content_groupids'] == 0) {
            $data['groupname'] = $_COMPANY->getAppCustomization()['group']['groupname0'];
            $data['groupname_short'] = $_COMPANY->getAppCustomization()['group']['groupname0'];
            $data['overlaycolor'] = '';
        } else {
            $group = Group::GetGroup($data['content_groupids']);
            $data['groupname'] =  $group->val('groupname');
            $data['groupname_short'] = $group->val('groupname_short');
            $data['overlaycolor'] = $group->val('overlaycolor');
        }
        
        return $data;
    }
    public function getPostChapterNames(): array
    {
        $chapterNames = [];
        if ($this->val('chapterid')) {
            $chapterNames = Group::GetChapterNamesByChapteridsCsv($this->val('chapterid'));
        }
        $chapterNames = array_unique(array_column($chapterNames, 'chaptername'));
        usort($chapterNames, 'strnatcasecmp');
        return $chapterNames;
    }

    public function isAdminContent() : bool
    {
        return ($this->val('groupid') == 0);
    }

    public function isDynamicListPost() : bool
    {
        return !empty($this->val('listids'));
    }

}
