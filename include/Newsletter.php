<?php
// Do no use require_once as this class is included in Company.php.

class Newsletter extends Teleskope {
	protected function __construct(int $id,int $cid,array $fields) {
		parent::__construct($id,$cid,$fields);
		//declaring it protected so that no one can create it outside this class.
	}

    // Add traits right after the constructor
    public static function GetTopicType():string {return self::TOPIC_TYPES['NEWSLETTER'];}
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
        return $this->val('newslettername');
    }

    public function updatePublishToEmail(int $flag) {
        if (self::DBMutate("UPDATE `newsletters` set `publish_to_email`={$flag} WHERE newsletterid={$this->id}"))
            self::LogObjectLifecycleAudit('state_change', 'newsletter', $this->id(), $this->val('version'), ['publish_to_email' => $flag]);
            $this->fields['publish_to_email'] = (string)$flag;
    }

	public static function GetNewsletter(int $id) {
		$obj = null;
        global $_COMPANY; /* @var Company $_COMPANY */

		$r1 = self::DBGet("SELECT * FROM newsletters WHERE newsletterid='{$id}' AND companyid = '{$_COMPANY->id()}'");

		if (!empty($r1)) {
			$obj = new Newsletter($id, $_COMPANY->id(), $r1[0]);
		}
		return $obj;
	}

    public static function GetNewsletterForPublishing(int $id, bool $do_state_change)
    {
        $obj = null;
        global $_COMPANY; /* @var Company $_COMPANY */
        $status_await = self::STATUS_AWAITING;
        $status_active = self::STATUS_ACTIVE;
        $status_under_review = self::STATUS_UNDER_REVIEW;

        if ($do_state_change) {
            $r1 = self::DBUpdate("UPDATE `newsletters` SET isactive='{$status_active}' WHERE companyid = '{$_COMPANY->id()}' AND (newsletterid='{$id}' AND isactive IN ({$status_await},{$status_active},{$status_under_review}) AND publishdate < now()+interval 10 second)");
            $_COMPANY->expireRedisCache("NWS:{$id}");
            if ($r1) {
                $obj = self::GetNewsletter($id);
                self::LogObjectLifecycleAudit('state_change', 'newsletter', $obj->id(), $obj->val('version'), ['state' => 'published']);
            }
        } else {
            $attempts = 5;
            while ($attempts--) {
                $news = self::GetNewsletter($id);
                if ($news->isActive()) {
                    $obj = $news; // It means the newsetter has not been published yet.
                    break;
                }
                sleep(60); // Some other process needs to do state change, wait
            }
        }
        return $obj;
    }

    public static function GetNewsletterFromCache (int $id) {
        global $_COMPANY;
        return self::GetNewsletterByCompany($_COMPANY, $id);
    }

    public static function GetNewsletterByCompany(Company $company, int $id) {
        $obj = null;
        $key = "NWS:{$id}";
        if (($obj = $company->getFromRedisCache($key)) === false) {
            $obj = null; // Reset $obj to initial value
            $r1 = self::DBROGet("SELECT * FROM newsletters WHERE newsletterid='{$id}' AND companyid = '{$company->id()}'");
            if (!empty($r1)) {
                $obj = new Newsletter($id, $company->id(), $r1[0]);
                $company->putInRedisCache($key, $obj, 300);
            }
        }
        return $obj;
    }

	// added usertype for check on draft post
	public static function GetAllNewslettersInGroup(int $groupid,$chapterid=0,$companyLevel=0, $includeDraft=false) {
		$objs = array();
        global $_COMPANY; /* @var Company $_COMPANY */
		global $_USER;  /* @var User $_USER */
		
		if($includeDraft) {
			$active = "AND `isactive` !='".self::STATUS_INACTIVE."'";
		}else{
			$active = "AND `isactive` ='".self::STATUS_ACTIVE."'";
		}
		$condition = "AND `groupid`='{$groupid}'";
		if($companyLevel){
			$condition = "AND (`groupid`='{$groupid}' OR `groupid` = 0)";
		}

		if($chapterid>0){
			$chapterCondition	=	"AND FIND_IN_SET ({$chapterid}, chapterid)";
		}else{
			$chapterCondition	=	"";
		}
		
		$rows = self::DBGet("SELECT * FROM `newsletters` WHERE `companyid` = '{$_COMPANY->id()}' ".$condition." ".$active." ".$chapterCondition." ORDER BY newsletterid DESC");

		foreach ($rows as $row) {
			if (($row['groupid'] == 0) && ($row['isactive'] != self::STATUS_ACTIVE) && (!$_USER->isAdmin()))
				continue; //Remove the rows which are unpublished admin event unless the user is Admin
			else
				$objs[] = new Newsletter($row['newsletterid'], $_COMPANY->id(), $row);
		}
		return $objs;
	}

    public static function ConvertDBRecToNewsletter (array $rec) : ?Newsletter
    {
        global $_COMPANY;
        $obj = null;
        $n = (int)$rec['newsletterid'];
        $c = (int)$rec['companyid'];
        if ($n && $c && $c === $_COMPANY->id())
            $obj = new Newsletter($n, $c, $rec);
        return $obj;
    }

	/**
	 * Create newsletter
	 */

	public static function CreateNewsletter(int $groupid, string $newslettername, int $templateid, string $newletter, string $template, string $chapterids, int $channelid, string $content_replyto_email ='',string $listids='0', bool $use_and_chapter_connector = false, string $newsletter_summary='') {
        global $_COMPANY;
		global $_USER;
		global $_ZONE;
        
        if (empty($chapterids)) {
            $chapterids = '0';
        }

        $newletter = Sanitizer::RemoveHTMLScriptTags($newletter);

        $newletter = self::UpdateTitleTag($newletter, $newslettername);

        ContentModerator::CheckBlockedWords($newslettername, $newletter);

		$result = self::DBInsertPS("INSERT INTO `newsletters`(`companyid`, `zoneid`, `groupid`, `chapterid`, `userid`, `newslettername`, `templateid`,  `newsletter`,`template`, `createdon`, `modifiedon`, `isactive`,channelid, `content_replyto_email`,`listids`,`use_and_chapter_connector`,`newsletter_summary`) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW(),'2',?,?,?,?,?)",'iiixisixxisxis',$_COMPANY->id(),$_ZONE->id(),$groupid,$chapterids,$_USER->id(),$newslettername,$templateid,$newletter,$template,$channelid,$content_replyto_email,$listids, ($use_and_chapter_connector ? 1 : 0),$newsletter_summary);

        if ($result) {
            self::LogObjectLifecycleAudit('create', 'newsletter', $result, 1); 
		}
		return $result;
    }

	/**
	 * Update Newsletter
	 */

	public function updateNewsletter(string $newslettername, string $newletter, string $template, string $chapterids, int $channelid, string $content_replyto_email = '',string $listids='0', bool $use_and_chapter_connector = false, string $newsletter_summary = '') {
        global $_COMPANY;
		global $_USER;

        
        $status_draft = self::STATUS_DRAFT;

		if (empty($chapterids)) {
            $chapterids = '0';
        }

        $newletter = Sanitizer::RemoveHTMLScriptTags($newletter);

        $newletter = self::UpdateTitleTag($newletter, $newslettername);

        ContentModerator::CheckBlockedWords($newslettername, $newletter);

		$retVal = self::DBUpdatePS("UPDATE `newsletters` SET `userid`=?, `newslettername`=?, `newsletter`=?,`template`=?, `modifiedon`=NOW(), chapterid=?, channelid=?, `content_replyto_email`=?,`version`=`version`+1,isactive={$status_draft},`listids`=?,`use_and_chapter_connector`=?,newsletter_summary=? WHERE `companyid`=? AND `newsletterid`=? AND `version`=?",'isxxxisxisiii',$_USER->id(),$newslettername,$newletter,$template,$chapterids,$channelid,$content_replyto_email,$listids, ($use_and_chapter_connector ? 1 : 0), $newsletter_summary, $_COMPANY->id(),$this->id, $this->val('version'));

        if ($retVal) {
            $_COMPANY->expireRedisCache("NWS:{$this->id}");
            self::LogObjectLifecycleAudit('update', 'newsletter', $this->id(), $this->val('version'));
        }
		return $retVal;
	}

    /**
	 * Unpublish Newsletter function.
     * Unpublish newsletter by newsletter id.
     * Status changed to 2.
	 */

	public function unpublishNewsletter() {
        global $_COMPANY;
		global $_USER;  
        
        $status_draft = self::STATUS_DRAFT;

		$retVal = self::DBUpdatePS("UPDATE `newsletters` SET isactive={$status_draft} WHERE `companyid`=? AND `groupid`=? AND `newsletterid`=?",'iii',$_COMPANY->id(),$this->val('groupid'),$this->id);

        if ($retVal) {			
            self::LogObjectLifecycleAudit('state_change', 'newsletter', $this->id(), $this->val('version'), ['state' => 'draft']);
        }
		return $retVal;
	}// end function.
	
	/**
	 * Get Global Level Newsletter
	 */

	public static function GetGlobalNewsletters($includeDraft=false) {
	
        global $_COMPANY; /* @var Company $_COMPANY */
	
		if($includeDraft) {
			$active = "AND `isactive` !='".self::STATUS_INACTIVE."'";
		}else{
			$active = "AND `isactive` ='".self::STATUS_ACTIVE."'";
		}
		
		return self::DBGet("SELECT * FROM `newsletters` WHERE `companyid` = '{$_COMPANY->id()}' AND `groupid`=0 ".$active." ORDER BY newsletterid DESC");
	}

	public static function GetAllNewsletterInGroupByMonth(int $groupid,string $month) {
		$objs = array();
        global $_COMPANY; /* @var Company $_COMPANY */
		global $_USER;  /* @var User $_USER */
		
		
		$rows = self::DBGet("SELECT * FROM `newsletters` WHERE `companyid` = '{$_COMPANY->id()}' AND `groupid`='{$groupid}' AND isactive='1' AND modifiedon LIKE '%".$month."%' ");
		usort($rows, function($a, $b) {
            return $a['newsletterid'] <=> $b['newsletterid'];
        });
		foreach ($rows as $row) {
			$objs[] = new Newsletter($row['newsletterid'], $_COMPANY->id(), $row);
		}
		return $objs;
	}

    private static function UpdateTitleTag($newsletter_html, $title)
    {
        $pattern = array(
            "/<title>.*<\/title>/",
            "/This is preheader and it will show as a preview in some mail clients.<\span>/"
        );
        $replacement = array(
            "<title>{$title}</title>",
            "{$title}</span>"
        );
        return preg_replace($pattern, $replacement, $newsletter_html);
    }

    
	public function pinUnpinNewsletter(int $type) {
		global $_COMPANY;
		$pin_to_top = ($type == 1) ? 1 : 0;
		$retVal = self::DBUpdate("UPDATE `newsletters` SET `pin_to_top`={$pin_to_top},modifiedon=NOW() where `companyid`={$_COMPANY->id()} AND `newsletterid`={$this->id}");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'newsletter', $this->id(), $this->val('version'), ['pin_to_top' => $pin_to_top]);
        }        
        return $retVal;
	}

	public function addNewsletterAttachment( string $title, string $attachment) {
        global $_COMPANY, $_ZONE, $_USER;

		return self::DBInsertPS("INSERT INTO `newsletter_attachments`(`companyid`,`zoneid`,`groupid`, `newsletterid`, `title`, `attachment`) VALUES (?,?,?,?,?,?)","iiiixx",$_COMPANY->id(),$_ZONE->id(),$this->val('groupid'),$this->id(),$title,$attachment);
	}

	public function updateNewsletterForReview()
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        
        $status_under_review = self::STATUS_UNDER_REVIEW;

        $retVal = self::DBUpdate("UPDATE `newsletters` SET `isactive`='{$status_under_review}',`modifiedon`=NOW() WHERE `companyid` = '{$_COMPANY->id()}' AND (`newsletterid`='{$this->id()}')");

        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'newsletter', $this->id(), $this->val('version'), ['state' => 'review']);
        }
		return $retVal;

    }

	public function updateNewsletterForSchedulePublishing(int $delay)
    {
        global $_COMPANY,$_USER; /* @var Company $_COMPANY */
        
        $status_awiting= self::STATUS_AWAITING;

        $retVal = self::DBUpdate("UPDATE `newsletters` SET `isactive`='{$status_awiting}',`publishdate`=NOW() + interval {$delay} second, `publishedby`='{$_USER->id()}' WHERE `companyid` = '{$_COMPANY->id()}' AND (`newsletterid`='{$this->id()}')");

        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'newsletter', $this->id(), $this->val('version'), ['state' => 'awaiting publish', 'publishedby' => $_USER->id()]);
        }
		return $retVal;
    }

	public function inactivateNewsletter()
    {
        global $_COMPANY; /* @var Company $_COMPANY */
       
        $status_inactive= self::STATUS_INACTIVE;

        $retVal = self::DBUpdate("UPDATE `newsletters` SET `isactive`='{$status_inactive}',`modifiedon`=NOW() WHERE `companyid` = '{$_COMPANY->id()}' AND (`newsletterid`='{$this->id()}' )");

        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'newsletter', $this->id(), $this->val('version'), ['state' => 'inactive']);
            $job = new NewsLetterJob($this->val('groupid'), $this->val('chapterid'), $this->id());
            $job->saveAsBatchUpdateType();
        }
		return $retVal;
    }

    /**
     * This method permanently deletes the newsletter and its attachments
     * @return int
     */
    public function deleteIt() {
        global $_COMPANY;
        self::DeleteAllComments_2($this->id);
        self::DeleteAllLikes($this->id);

        foreach ($this->getNewsletterAttachments() as $attachment)
            $this->deleteNewsletterAttachment($attachment['attachment_id']);

        $result =  self::DBMutate("DELETE FROM newsletters WHERE companyid={$_COMPANY->id()} AND newsletterid={$this->id}");

        if ($result) {
            self::LogObjectLifecycleAudit('delete', 'newsletter', $this->id(), $this->val('version'));
            self::PostDelete($this->id);
        }
        return $result;
    }

	public function cancelNewsletterPublishing()
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        $status_draft = Newsletter::STATUS_DRAFT;
    	$status_awaiting = Newsletter::STATUS_AWAITING;

        $retVal = self::DBUpdate("UPDATE `newsletters` SET `isactive`='{$status_draft}',`publishdate`=null WHERE `companyid` = '{$_COMPANY->id()}' AND (`newsletterid`='{$this->id()}' AND `isactive`='{$status_awaiting}')");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'newsletter', $this->id(), $this->val('version'), ['state' => 'draft']);
        }
        return $retVal;
    }

	public function deleteNewsletterAttachment(int $attachment_id)
    {
        global $_COMPANY,$_ZONE; /* @var Company $_COMPANY */

        $filelink = self::DBGet("SELECT `attachment` FROM `newsletter_attachments` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `newsletterid`={$this->id()} AND `attachment_id`={$attachment_id}")[0]['attachment'];
        $_COMPANY->deleteFile($filelink);
		return self::DBUpdate("DELETE FROM `newsletter_attachments` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `newsletterid`={$this->id()} AND `attachment_id`={$attachment_id}");
    }

    /**
     * @param int $groupid
     * @param int $showGlobalChapterOnly if set then the newsletters that match chapterid=0 are returned, $chapterid value is ignored.
     * @param int $chapterid
     * @param int $showGlobalChannelOnly if set then the newsletters that match channeld=0 are returned, $channelid value is ignored.
     * @param int $channelid
     * @param int $year
     * @param string $timezone
     * @return array
     */
    public static function GetGroupNewsletterViewData(int $groupid, int $showGlobalChapterOnly, int $chapterid, int $showGlobalChannelOnly, int $channelid, int $year, string $timezone, ?int $page = null, int $per_page = MAX_HOMEPAGE_FEED_ITERATOR_ITEMS)
    {
        global $_COMPANY, $_ZONE, $_USER;

        $chapterCondition = '';
        if ($showGlobalChapterOnly) {
            $chapterCondition = "AND newsletters.chapterid='0'";
        } else {
            if ($chapterid > 0) {
                $chapterCondition = "AND FIND_IN_SET({$chapterid},newsletters.chapterid)";
            }
        }

        $channelCondition = "";
        if ($showGlobalChannelOnly) {
            $channelCondition    = "AND newsletters.channelid='0'";
        } else {
            if ($channelid > 0) {
                $channelCondition = "AND newsletters.channelid='{$channelid}'";
            }
        }

        $groupCondition = "AND newsletters.groupid IN ({$groupid})";
        if ($_COMPANY->getAppCustomization()['newsletters']['show_global_newsletters_in_group_feed']){
            $groupCondition = "AND newsletters.groupid IN ({$groupid},0)";
        }

        $year_condition = " AND YEAR(`newsletters`.`publishdate`) = '{$year}' ";
        $limit_clause = '';

        if (!$year) {
            $limit = $per_page;
            $offset = ($page - 1) * $per_page;

            $year_condition = '';
            $limit_clause = "LIMIT {$per_page} OFFSET {$offset}";
        }

        return self::DBROGet("
        SELECT newsletters.*,IFNULL(`groups`.groupname,'') as groupname 
        FROM `newsletters` 
            LEFT JOIN `groups` on `groups`.groupid=newsletters.groupid  
        WHERE newsletters.companyid='{$_COMPANY->id()}' 
          AND newsletters.zoneid='{$_ZONE->id()}' 
              {$year_condition}
              {$groupCondition}
              {$chapterCondition}
              {$channelCondition} 
          AND newsletters.isactive=1 
          AND newsletters.listids='0'
        ORDER BY newsletters.pin_to_top DESC,
                 CASE newsletters.pin_to_top WHEN 1 THEN newsletters.modifiedon ELSE newsletters.publishdate END DESC,
                newsletters.newsletterid DESC
        {$limit_clause}
        ");
    }

    public function getNewsletterAttachments() {
	    global $_COMPANY,$_ZONE;
		
		return self::DBGet("SELECT * FROM `newsletter_attachments` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `newsletterid`='{$this->id()}'");
	}

    public function getTypesenseDocument(): array
    {
        return [
            'id' => $this->getTypesenseId(),
            'type' => $this->getTypesenseDocumentType(),
            'company_id' => (int) $this->val('companyid'),
            'zone_id' => (int) $this->val('zoneid'),
            'title' => $this->val('newslettername'),
            'description' => Html::SanitizeHtml($this->val('newsletter')),
            'group_id' => (int) $this->val('groupid'),
        ];
    }

    public function getHomeFeedData(): array
    {
        global $_COMPANY;

        $data = $this->toArray();

        $data = [
            'content_type' => 'newsletter',
            'content_id' => $this->id(),
            'content_groupids' => $this->val('groupid'),
            'content_chapterids' => $this->val('chapterid'),
            'content_channelids' => $this->val('channelid'),
            'content_date' => $this->val('publishdate'),
            'addedon' => $this->val('publishdate'),
            'pin_to_top' => '0',
            'postedon' => $this->val('addedon'),
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

    public static function GetCustomName(bool $defaultNamePlural = false){
        global $_COMPANY, $_ZONE, $_USER;
        if ($defaultNamePlural){
            $name = gettext('Newsletters');
        } else {
            $name = gettext('Newsletter');
        }

        /***************
         *  The following was commented out as currently we do not allow alt_name in configuration for this object
        $lang = $_USER->val('language') ?? 'en';
        $altNames = $_COMPANY->getAppCustomization()['event']['alt_name'] ?? array();

        if(!empty($altNames) && array_key_exists($lang,$altNames)){

        $altName  = $altNames[$lang];
        if (!empty($altName)){
        return $altName;
        }
        }
         ***************/
        return $name;
    }
    public function getNewsletterChapterNames(): array
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

    public function isDynamicListNewsletter() : bool
    {
        return !empty($this->val('listids'));
    }
}
