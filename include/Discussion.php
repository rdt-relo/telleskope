<?php

// Do no use require_once as this class is included in Company.php.

    class Discussion extends Teleskope {

	protected $attachments;
	protected $comments;

	protected function __construct($id,$cid,$fields) {
		parent::__construct($id,$cid,$fields);
		//declaring it protected so that no one can create it outside this class.
		$this->attachments = NULL;
	}

    // Add traits right after the constructor
    public static function GetTopicType():string {return self::TOPIC_TYPES['DISCUSSION'];}
    use TopicLikeTrait;
    use TopicCommentTrait;

    /**
     * Gets the Discussion object for the matching id.
     * @param int $id of the Discussion object to fetch
     * @return Discussion|null
     */
	public static function GetDiscussion(int $id) {
		$obj = null;
        global $_COMPANY;
			//  AND isactive=1
		$r1 = self::DBGet("SELECT * FROM `discussions` WHERE companyid = '{$_COMPANY->id()}' AND  discussionid='{$id}' ");

		if (count($r1)) {
			$obj = new Discussion($id, $_COMPANY->id(), $r1[0]);
		} else {
			Logger::Log("Discussion: GetDiscussion({$id}) failed. Context (Company={$_COMPANY->id()})",Logger::SEVERITY['WARNING_ERROR']);
		}
		return $obj;
	}

	public static function ConvertDBRecToDiscussion (array $rec) : ?Discussion
    {
            global $_COMPANY;
            $obj = null;
            $p = (int)$rec['discussionid'];
            $c = (int)$rec['companyid'];
            if ($p && $c && $c === $_COMPANY->id())
                $obj = new Discussion($p, $c, $rec);
            return $obj;
	}


	public function inactivateIt() {
		global $_COMPANY,$_ZONE;
		$status = self::STATUS_INACTIVE;
		$retVal = self::DBMutate("UPDATE discussions SET isactive='{$status}' WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND discussionid='{$this->id}'");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'discussion', $this->id(), $this->val('version'), ['state' => 'inactive']);
        }
        return $retVal;
	}

	public function deleteIt() {
		global $_COMPANY,$_ZONE;
		
        self::DeleteAllComments_2($this->id);
        self::DeleteAllLikes($this->id);
        $retVal = self::DBMutate("DELETE FROM discussions WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND discussionid='{$this->id}'");

		if ($retVal) {			
			self::LogObjectLifecycleAudit('delete', 'discussion',$this->id, $this->val('version'));
            self::PostDelete($this->id);
		}
		return $retVal;
	}

	public function __toString() {
		return "Discussion ". parent::__toString();
	}


	/**
	 * Create new Discussion
	 */

	public static function CreateOrUpdateDiscussion(int $discussionid, int $groupid, string $title,string $discussion,string $chapterids,int $channelid, int $anonymous_post=0) {
		global $_COMPANY;
		global $_USER;
		global $_ZONE;
		     				

		// Extract hashtag handles
		$handleids = HashtagHandle::ExtractAndCreateHandles($discussion);
		$handleidsCsv = implode(',',$handleids);

		if ($discussionid){		
			ContentModerator::CheckBlockedWords($title, $discussion);

			if (self::DBUpdatePS("UPDATE `discussions` SET `title`=?,`discussion`=?,`chapterid`=?,`channelid`=?,`modifiedby`=?,`modifiedon`=now(),handleids=?  where `discussionid`=? and `companyid`=?",'smxiixii',$title, $discussion,$chapterids, $channelid,$_USER->id(),$handleidsCsv,$discussionid,$_COMPANY->id())){
				self::LogObjectLifecycleAudit('update', 'discussion', $discussionid, 0);

			    return $discussionid;
			}else{
                return 0;
			}
			

		} else {
			ContentModerator::CheckBlockedWords($title, $discussion);

			$retVal = self::DBInsertPS("INSERT INTO `discussions`(`companyid`,`zoneid`,`groupid`, `createdby`, `chapterid`, `title`, `discussion`,`createdon`, `modifiedon`, `isactive`,`channelid`,`handleids`,`anonymous_post`) VALUES (?,?,?,?,?,?,?,NOW(),NOW(),'1',?,?,?)",'iiiixsmixi',$_COMPANY->id(),$_ZONE->id(),$groupid,$_USER->id(),$chapterids,$title, $discussion,$channelid,$handleidsCsv,$anonymous_post);
			if ($retVal) {				
				self::LogObjectLifecycleAudit('create', 'discussion', $retVal, 1);  
			}
			return $retVal;
		}
	}

    /**
     * GET Group level discusstion
     */
    public static function GetGroupDiscussions(int $groupid, int $chapterid=-1, int $channelid = -1, int $includeGlobal = 0, int $page = 0) {
		$objs = array();
        global $_COMPANY; /* @var Company $_COMPANY */
		global $_USER;  /* @var User $_USER */
		global $_ZONE;
	
        $groupCondition = '';
        if ($includeGlobal){
            $groupCondition = " AND ( `groupid` = '{$groupid}' OR `groupid` =0 )";
        } else {
            $groupCondition = " AND ( `groupid` = '{$groupid}' )";
        }
		

		$chapterCondition = " ";
        if ($chapterid > 0) {
            $chapterCondition = " AND FIND_IN_SET({$chapterid},`chapterid`)" ;
        } elseif($chapterid == 0){
			$chapterCondition = " AND chapterid = '0' " ;
		}
        $channelCondition = " ";
        if ($channelid > 0) {
            $channelCondition = " AND channelid='{$channelid}'";
        } elseif($channelid == 0){
			$channelCondition = " AND channelid='0' ";
		}

		$limitCondition = "";
		if ($page){
			$start 		=	($page - 1)*30;
			$end  		=	30;
			$limitCondition  = "LIMIT $start, $end";
		}
		
		return self::DBGet("SELECT * FROM discussions WHERE companyid = '{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' {$groupCondition} {$chapterCondition} {$channelCondition} AND `isactive`='".self::STATUS_ACTIVE."' ORDER BY pin_to_top DESC, modifiedon DESC, discussionid DESC $limitCondition");

	}

    public function pinUnpinDiscussion(int $type) {
		global $_COMPANY,$_ZONE;
		$pin_to_top = ($type == 1) ? 1 : 0;
		$retVal = self::DBUpdate("UPDATE `discussions` SET `pin_to_top`={$pin_to_top},modifiedon=NOW() WHERE `companyid`={$_COMPANY->id()} AND `zoneid`='{$_ZONE->id()}' AND `discussionid`={$this->id}");

		if ($retVal) {			
			self::LogObjectLifecycleAudit('state_change', 'discussion', $this->id(), $this->val('version'), ['pin_to_top' => $pin_to_top]);
		}
        $_COMPANY->expireRedisCache("DCS:{$this->id}");
		return $retVal;
	}

    public function getTypesenseDocument(): array
    {
        return [
            'id' => $this->getTypesenseId(),
            'type' => $this->getTypesenseDocumentType(),
            'company_id' => (int) $this->val('companyid'),
            'zone_id' => (int) $this->val('zoneid'),
            'title' => $this->val('title'),
            'description' => Html::SanitizeHtml($this->val('discussion')),
            'group_id' => (int) $this->val('groupid'),
        ];
    }

    public static function GetDiscussionFromCache (int $id) {
        global $_COMPANY;
        return self::GetDiscussionByCompany($_COMPANY, $id);
    }

    public static function GetDiscussionByCompany(Company $company, int $id) {
        $obj = null;
        $key = "DCS:{$id}";
        if (($obj = $company->getFromRedisCache($key)) === false) {
            $obj = null; // Reset $obj to initial value
            $r1 = self::DBROGet("SELECT * FROM discussions WHERE discussionid='{$id}' AND companyid = '{$company->id()}'");
            if (!empty($r1)) {
                $obj = new Discussion($id, $company->id(), $r1[0]);
                $company->putInRedisCache($key, $obj, 300);
            }
        }
        return $obj;
    }

    public function getHomeFeedData(): array
    {
        global $_COMPANY;

        $data = $this->toArray();

        $data = [
             'content_type' => 'discussion',
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

    public static function GetCustomName(bool $defaultNamePlural = false){
        global $_COMPANY, $_ZONE, $_USER;
        if ($defaultNamePlural){
            $name = gettext('Discussions');
        } else {
            $name = gettext('Discussion');
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
}
