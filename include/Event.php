<?php
// Do no use require_once as this class is included in Company.php.

class Event extends Teleskope {

    public const RSVP_TYPE = array(
        'RSVP_DEFAULT'		    => 0,
        'RSVP_YES'			    => 1,
        'RSVP_MAYBE'		    => 2,
        'RSVP_NO'			    => 3,
        'RSVP_INPERSON_YES'     => 11,
        'RSVP_INPERSON_WAIT'    => 12,
        'RSVP_ONLINE_YES'       => 21,
        'RSVP_ONLINE_WAIT'      => 22,
        'RSVP_INPERSON_WAIT_CANCEL' => 15,
        'RSVP_ONLINE_WAIT_CANCEL' => 25
    );

    public const EVENT_CHECKIN_METHOD = array (
        'MOBILE_APP' => 'self using app',
        'WEB_FULL_SIGNIN' => 'self',
        'WEB_COOKIE_SIGNIN' => 'cookie',
        'VIEWED_RECORDING' => 'viewed recording',
    );

    public const EVENT_CHECKIN_METHOD_TO_ENGLISH = array (
        'self using app' => 'Self - Mobile Application',
        'self' => 'Self - Web Application',
        'cookie' => 'Self - Web Application',
        'viewed recording' => 'Self - Watched Recording',
    );

	const STATUS_HIGHLIGHT_ENABLED=1;

	// Due to the database dependencies, do not change the list below. You can add to the list as needed.
	const SYS_EVENTTYPES = array (
						1 => "Recruitment",
						2 => "Professional Development",
						3 => "Community",
						4 => "Business Impact",
						5 => "Social/Culture",
						);

    const EVENT_CLASS = array (
        'EVENT' => 'event',
        'EVENTGROUP' => 'eventgroup',
        'HOLIDAY' => 'holiday',
        'TEAMEVENT' => 'teamevent',
    );

    const SPEAKER_FIELD_TYPES = array(
        'speaker_type' => 'Speaker Type',
        'speech_type' => 'Speech Type',
        'audience_type' => 'Audience Type',
    );

    const EVENT_ATTRIBUTES = array(
        'event_volunteer_requests' => 'event_volunteer_requests',
        'event_surveys' => 'event_surveys'
    );

    const EVENT_SURVEY_TRIGGERS = array(
        'INTERNAL_PRE_EVENT'=>'INTERNAL_PRE_EVENT',
        'INTERNAL_POST_EVENT'=>'INTERNAL_POST_EVENT',
        // 'EXTERNAL_PRE_EVENT'=>'EXTERNAL_PRE_EVENT',
        // 'EXTERNAL_POST_EVENT'=>'EXTERNAL_POST_EVENT'
    );

    const EVENT_SURVEY_TRIGGERS_ENGLISH = array(
        'INTERNAL_PRE_EVENT'=>'Pre Event Survey',
        'INTERNAL_POST_EVENT'=>'Post Event Survey',
        // 'EXTERNAL_PRE_EVENT'=>'EXTERNAL_PRE_EVENT',
        // 'EXTERNAL_POST_EVENT'=>'EXTERNAL_POST_EVENT'
    );

    const MY_EVENT_SECTION = array(
        'MY_UPCOMING_EVENTS'=>'MY_UPCOMING_EVENTS',
        'MY_PAST_EVENTS' => 'MY_PAST_EVENTS',
        'MY_EVENT_SUBMISSIONS' => 'MY_EVENT_SUBMISSIONS',
        'DISCOVER_EVENTS' => 'DISCOVER_EVENTS'
    );
//  `rsvp_restriction` tinyint NOT NULL DEFAULT '0' COMMENT '0 default, 1 Any Number of Events, 2 Single Event Only',
    const EVENT_SERIES_RSVP_RESTRICTION = array(
        'ANY_NUMBER_OF_EVENTS'=> 1,
        'SINGLE_EVENT_ONLY' => 2,
        'ANY_NUMBER_OF_NON_OVERLAPPING_EVENTS' => 3
    );

    const EVENT_COMMUNICATION_TYPES = array(
        'reconciliation' => 'reconciliation',
    );
    
    const EVENT_BOOKINGS = array(
        'booking_resolution_data' => 'booking_resolution_data',
    );

    const PRIVATE_EVENT_MESSAGE = 'This event is exclusive to our invited guests only, and we kindly request that you refrain from forwarding or sharing event details with others.';

    public const MAX_PARTICIPATION_LIMIT = 9999999;

    protected $eventjoiners;

    private $expense_entry;
    private $expense_entries;
    private $disclaimer_consent_required;
    private $is_action_disabled_due_to_approval_process;

    protected function __construct(int $id,int $cid,array $fields) {
		parent::__construct($id,$cid,$fields);
		//declaring it protected so that no one can create it outside this class.
		$this->eventjoiners = NULL;
        $this->expense_entry = NULL;
        $this->expense_entries = NULL;
        $this->disclaimer_consent_required = NULL;
        $this->is_action_disabled_due_to_approval_process = NULL;
    }



    // Add traits right after the constructor
    public static function GetTopicType():string {return self::TOPIC_TYPES['EVENT'];}

    use TopicLikeTrait;
    use TopicCommentTrait;
    use TopicApprovalTrait;
    use TopicAttachmentTrait;
    use TopicCustomFieldsTrait;

	public static function GetEvent(int $id) {
		$obj = null;
        global $_COMPANY; /* @var Company $_COMPANY */

		$r1 = self::DBGet("SELECT * FROM events WHERE eventid='{$id}' AND companyid = '{$_COMPANY->id()}'");

		if (count($r1)) {
			$obj = new Event($id, $_COMPANY->id(), $r1[0]);
		}
		return $obj;
	}

    /**
     * Gets speaker cc emails from the requested zone.
     * @param Zone $zone
     * @return string
     */
    public static function GetSpeakerApprovalCCEmailsForZone(Zone $zone) : string
    {
        $configuration_key = 'event.speakers.speaker_approvel_cc_email';
        return $zone->getZoneAttributesKeyVal($configuration_key) ?? '';
    }

    /**
     * Sets speaker cc emails in the current zone.
     * @param string $cc_emails
     * @return int
     */
    public static function UpdateSpeakerApprovelCCEmailsForZone(string $cc_emails): int
    {
        global $_ZONE;
        $configuration_key = 'event.speakers.speaker_approvel_cc_email';
        return $_ZONE->updateZoneAttributesKeyVal($configuration_key, $cc_emails);
    }

    public static function ExtractLatLongFromVenue(string $eventvanue, string $vanueaddress): array
    {
        global $db;
        $latitude = '';
        $longitude = '';
        if ((!empty($eventvanue) || !empty($vanueaddress)) &&
            !empty($loc = $db->getLatLong($eventvanue . ", " . $vanueaddress))
        ) {
            $latitude = $loc['lat'];
            $longitude = $loc['lng'];
        }
        return array($latitude, $longitude);
    }

    /**
     * GetEventsInSeries
     *
     * @param  int $seriesid
     * @param  bool $excludeInactive
     * @param  bool $includeSeriesHead
     * @return array
     */
    public static function GetEventsInSeries(int $event_series_id, bool $excludeInactive = true, bool $excludeSeriesHead = true) {
        $obj = array();
        global $_COMPANY; /* @var Company $_COMPANY */

        $isactiveCondition = "";
        if ($excludeInactive){
            $isactiveCondition = " AND isactive > 0";
        }

        $seriesHeadCondition = "";
        if ($excludeSeriesHead) {
            $seriesHeadCondition = " AND eventid!='{$event_series_id}'";
        }

        $r1 = self::DBGet("SELECT * FROM events WHERE companyid = '{$_COMPANY->id()}' AND event_series_id='{$event_series_id}' {$seriesHeadCondition} {$isactiveCondition} order by start");

        if (count($r1))
            foreach ($r1 as $r1_item) {
                $obj[] = new Event($r1_item['eventid'], $_COMPANY->id(), $r1_item);
            }
        return $obj;
    }

    public static function GetEventForPublishing(int $id, bool $do_state_change) {
        $obj = null;
        global $_COMPANY;
        $status_await = self::STATUS_AWAITING;
        $status_active = self::STATUS_ACTIVE;
        $status_under_review = self::STATUS_UNDER_REVIEW;

        if ($do_state_change) {
            $r1 = self::DBUpdate("UPDATE `events` SET isactive={$status_active} WHERE companyid = {$_COMPANY->id()} AND (eventid={$id} OR event_series_id={$id}) AND isactive IN ({$status_await},{$status_active},{$status_under_review}) AND publishdate < now()+interval 10 second");
            $_COMPANY->expireRedisCache("EVT:{$id}");
            if ($r1) {
                $obj = self::GetEvent($id);
                self::LogObjectLifecycleAudit('state_change', 'event', $obj->id(), $obj->val('version'), ['state' => 'published']);
            }
        } else {
            $attempts = 5;
            while ($attempts--) {
                $ev = self::GetEvent($id);
                if ($ev->val('isactive') == $status_active) {
                    $obj = $ev; // It means the event has not been published yet.
                    break;
                }
                sleep(60); // Some other process needs to do state change, wait
            }
        }
        return $obj;
    }

    public static function GetEventFromCache (int $id) {
        global $_COMPANY;
        return self::GetEventByCompany($_COMPANY, $id);
    }
    public static function GetEventByCompany(Company $company, int $id) {
        $obj = null;
        $key = "EVT:{$id}";
        if (($obj = $company->getFromRedisCache($key)) === false) {
            $obj = null; // Reset $obj to initial value
            $r1 = self::DBROGet("SELECT * FROM events WHERE eventid='{$id}' AND companyid = '{$company->id()}'");
            if (!empty($r1)) {
                $obj = new Event($id, $company->id(), $r1[0]);
                $company->putInRedisCache($key, $obj, 300);
            }
        }

        return $obj;
    }

    public static function ConvertDBRecToEvent ($rec) : ?Event
    {
	    global $_COMPANY;
	    $obj = null;
	    $e = (int)$rec['eventid'];
        $c = (int)$rec['companyid'];
	    if ($e && $c && $c === $_COMPANY->id())
	        $obj = new Event($e, $c, $rec);
	    return $obj;
    }

	public static function GetEventJoinersAsCSV (int $eventid) : string
    {
		$retVal = '';

		self::DBROGet("SET SESSION group_concat_max_len = 1024000"); //Increase the session limit; default is 1000
        $data = self::DBROGet("SELECT GROUP_CONCAT(userid) AS rsvps FROM `eventjoiners` WHERE `eventid`='{$eventid}' AND `joinstatus` IN (1,2,11,12,21,22) AND `userid` != 0");
		if (count($data))
			$retVal = $data[0]['rsvps'] ?? '';

		return $retVal;
	}

    /**
     * @param int $groupid, if null then groupid match is ignored. if 0 then zone level events are returned.
     * @param int $chapterid, if null then chapterid match is ignored. if 0 then group level events are returned.
     * @param int $channelid, if null then channelid match is ignored. if 0 then group level events are returned.
     * @param string $fromStartDate in UTC
     * @param string $toStartDate in UTC
     * @return array
     */
    public static function GetAllEventsInZoneAndScope(?int $groupid, ?int $chapterid, ?int $channelid, string $fromStartDate, string $toStartDate): array
    {
        $objs = array();
        global $_COMPANY;
        global $_USER;
        global $_ZONE;

        // for including collab events
        $groupCondition = '';
        if($groupid !== null){
            if ($groupid)
            $groupCondition = "AND (`groupid`='{$groupid}' OR find_in_set(".$groupid.",collaborating_groupids))";
            else
                $groupCondition = "AND (`groupid`=0)";
        }

        $chapterCondition = '';
        if($chapterid !== null){
            $chapterCondition	=	"AND FIND_IN_SET({$chapterid}, `chapterid`)";
        }

        $channelCondition = '';
        if($channelid !== null){
            $channelCondition	=	"AND `channelid`='{$channelid}'";

        }
        // Date condition
        $startDateCondtion = "";
        if (!empty($fromStartDate)) {
            $fromStartDate = gmdate('Y-m-d H:i:s',strtotime($fromStartDate . ' 00:00:00 UTC'));
            $startDateCondtion = " AND `events`.`start` >= '{$fromStartDate}' ";
        }
        $endDateCondtion = "";
        if (!empty($toStartDate)) {
            $toStartDate = gmdate('Y-m-d H:i:s',strtotime($toStartDate . ' 23:59:59 UTC'));
            $endDateCondtion = " AND `events`.`start` <= '{$toStartDate}' ";
        }

        $rows = self::DBGet("SELECT * FROM `events` WHERE `companyid` = '{$_COMPANY->id()}' AND `zoneid` = '{$_ZONE->id()}' {$groupCondition} {$chapterCondition} {$channelCondition} {$startDateCondtion} {$endDateCondtion} AND eventid != events.event_series_id AND `isactive` = 1 ORDER BY eventid DESC");

        foreach ($rows as $row) {
                $objs[] = Event::ConvertDBRecToEvent($row);
            }
        return $objs;
    }
	public static function GetNextEventInGroup(int $groupid,$companyLevel=0,$chapterid=0,$channelid=0) {
		$retVal = NULL;
        global $_COMPANY; /* @var Company $_COMPANY */

        $chapterCondition	=	"";
		if($chapterid){
			$chapterCondition	=	"AND FIND_IN_SET({$chapterid},chapterid)";
		}
        if($channelid){
			$channelCondition	=	"AND channelid='{$channelid}'";
		}else{
			$channelCondition	=	"";
		}
		$rows = self::DBGet("SELECT * FROM events WHERE companyid = '{$_COMPANY->id()}' AND groupid='{$groupid}' ".$chapterCondition." ".$channelCondition." AND isactive='".self::STATUS_ACTIVE."' AND end > now() + interval 5 minute  AND event_series_id = 0 AND isprivate =0  AND eventclass='event' ORDER BY start ASC LIMIT 1");

		if (count($rows)) {
			$retVal = new Event($rows[0]['eventid'], $_COMPANY->id(), $rows[0]);
		}

		return $retVal;
	}

    /**
     * Provides a label for Web Conference Service provider after parsing the URL
     * If the service provider is not support, empty string is returned
     * @param string $url of the service provider
     * @return string label of service provider or empty string
     */
	public static function GetWebConfSPName (string $url): string {
	    $retVal = '';

        $a = explode("s://",strtolower($url));
        $b = explode('.',$a[1] ?? '');
        $retVal = 'Virtual Meeting';
        if (count($b)>1){
            if ($b[1] === 'webex') {
                $retVal = 'Webex';
            } elseif ($b[1] === 'zoom') {
                $retVal = 'Zoom Meeting';
            } elseif ($b[1] === 'gotomeeting') {
                $retVal = 'GoToMeeting';
            } elseif ($b[1] === 'microsoft' && $b[0] === 'teams') {
                $retVal = 'Microsoft Teams';
            } elseif ($b[1] === 'google' && $b[0] === 'meet') {
                $retVal = 'Google Meet';
            } elseif ($b[1] === 'lync' && $b[0] === 'meet') {
                $retVal = 'Sykpe Meeting';
            }
        }
	    return $retVal;
    }

    public function getFormatedEventCollaboratedGroupsOrChapters(bool $showOnlyGroupNames = false) {
        $groupNames[] = Group::GetGroupName($this->val('groupid'));

        if ($this->val('collaborating_groupids')) {
            if ($this->val('chapterid') && !$showOnlyGroupNames) {
                $groupNames = array();
                $chapters = Group::GetChapterNamesByChapteridsCsv($this->val('chapterid'));
                $chapters = Arr::GroupBy($chapters, 'groupname');
                foreach($chapters as $groupName => $chtrs) {
                    $chpaterNames = array_column($chtrs,'chaptername');
                    usort($chpaterNames, 'strnatcasecmp');
                    $chapterNames = Arr::NaturalLanguageJoin($chpaterNames, ' & ');
                    $groupNames[] =  $groupName .' ('.$chapterNames.')';
                }

                $groupWithoutChapters = Group::FilterGroupsWithoutMatchingChapters($this->val('collaborating_groupids'), $this->val('chapterid'));
                if (!empty($groupWithoutChapters)) {
                    foreach($groupWithoutChapters as $gid) {
                        $groupNames[] = Group::GetGroupName($gid);
                    }
                }

            } else {
                $groupNames = array();
                $collaboratedWithGroups = explode(',', $this->val('collaborating_groupids'));
                foreach($collaboratedWithGroups as $id){
                    $groupNames[] = Group::GetGroupName($id);
                }
            }
        }
        usort($groupNames, 'strnatcasecmp');
        $formattedNames = Arr::NaturalLanguageJoin($groupNames, ' & ');
        return $formattedNames;
    }

    public function getEventChapterNames(): array
    {
        $chapterNames = [];
        if ($this->val('chapterid')) {
            $chapterNames = Group::GetChapterNamesByChapteridsCsv($this->val('chapterid'));
        }
        $chapterNames = array_unique(array_column($chapterNames, 'chaptername'));
        usort($chapterNames, 'strnatcasecmp');
        return $chapterNames;
    }

    /**
     * Returns either the web_conference_link or a event checkin wrapped link if event checkin is enabled
     * @return mixed|string|null
     */
    public function getWebConferenceLink () {
	    global $_COMPANY; /* @var Company $_COMPANY */
        global $_ZONE;

	    $retVal = $this->val('web_conference_link');
	    if ($this->val('web_conference_link') && $this->val('checkin_enabled') && $_COMPANY->getAppCustomization()['event']['checkin']) {
	        $retVal = $_COMPANY->getAppURL($_ZONE->val('app_type'))."ec2?e=".$_COMPANY->encodeId($this->id).'&u=1';
        }
	    return $retVal;
    }

	private function fetchEventJoiners(int $type) {
        global $_COMPANY;
		if (NULL === $this->eventjoiners) {
            $key = "EVT_JO:{$this->id}";
            if (($this->eventjoiners = $_COMPANY->getFromRedisCache($key)) === false) {
                // If the event has ended, store event joiner counts for 1 to 2 days, otherwise store for 10 to 30 minutes
                $ttl = ($this->hasRSVPEnded() || $this->hasEnded()) ? (86400* rand(1,2)): (600 * rand(1,3));
                //$this->eventjoiners = self::DBROGet("SELECT joinstatus, count(1) AS count FROM eventjoiners WHERE eventid='{$this->id}' GROUP BY JOINSTATUS");
                $this->eventjoiners = array();
                $rows = self::DBROGet("SELECT * FROM event_counters WHERE eventid={$this->id}");
                if (!empty($rows)) {
                    $this->eventjoiners[] = array('joinstatus'=>'1', 'count'=>$rows[0]['num_rsvp_1']);
                    $this->eventjoiners[] = array('joinstatus'=>'2', 'count'=>$rows[0]['num_rsvp_2']);
                    $this->eventjoiners[] = array('joinstatus'=>'3', 'count'=>$rows[0]['num_rsvp_3']);
                    $this->eventjoiners[] = array('joinstatus'=>'11', 'count'=>$rows[0]['num_rsvp_11']);
                    $this->eventjoiners[] = array('joinstatus'=>'12', 'count'=>$rows[0]['num_rsvp_12']);
                    $this->eventjoiners[] = array('joinstatus'=>'21', 'count'=>$rows[0]['num_rsvp_21']);
                    $this->eventjoiners[] = array('joinstatus'=>'22', 'count'=>$rows[0]['num_rsvp_22']);
                }
                $_COMPANY->putInRedisCache($key, $this->eventjoiners, $ttl);
            }
        }

		$retVal = 0;
		foreach ($this->eventjoiners as $r) {
			if ($r["joinstatus"] == $type) {
				$retVal = $r["count"];
				break;
			}
		}
		return $retVal;
	}

	public function invalidateJoinersCache () {
        global $_COMPANY;
        $_COMPANY->expireRedisCache("EVT_JO:{$this->id}");
        $_COMPANY->expireRedisCache("EVT_RJ:{$this->id}");
    }

	public function getRsvpYesCount() {
		return $this->fetchEventJoiners(self::RSVP_TYPE['RSVP_YES']) +
            $this->fetchEventJoiners(self::RSVP_TYPE['RSVP_INPERSON_YES']) +
            $this->fetchEventJoiners(self::RSVP_TYPE['RSVP_ONLINE_YES']);
	}

	public function getRsvpNoCount() {
        return $this->fetchEventJoiners(self::RSVP_TYPE['RSVP_NO']);
	}

	public function getRsvpMaybeCount() {
        return $this->fetchEventJoiners(self::RSVP_TYPE['RSVP_MAYBE']) +
            $this->fetchEventJoiners(self::RSVP_TYPE['RSVP_INPERSON_WAIT']) +
            $this->fetchEventJoiners(self::RSVP_TYPE['RSVP_ONLINE_WAIT']);
	}

	public function getRsvpInPersonYesCount() {
		return $this->fetchEventJoiners(self::RSVP_TYPE['RSVP_INPERSON_YES']);
	}

	public function getRsvpInPersonWaitCount() {
		return $this->fetchEventJoiners(self::RSVP_TYPE['RSVP_INPERSON_WAIT']);
	}

    public function getRsvpOnlineYesCount() {
        return $this->fetchEventJoiners(self::RSVP_TYPE['RSVP_ONLINE_YES']);
    }

    public function getRsvpOnlineWaitCount() {
        return $this->fetchEventJoiners(self::RSVP_TYPE['RSVP_ONLINE_WAIT']);
    }

	public function getJoinersCount() {
		return $this->getRsvpYesCount() + $this->getRsvpMaybeCount();
	}

	public function getRandomJoiners (int $limit = 6) {
        global $_COMPANY;
        $key = "EVT_RJ:{$this->id}";
        if (($value = $_COMPANY->getFromRedisCache($key)) === false) {
            // If event has expired, store random joiners for 1 to 3 hours, otherwise store for 10 to 30 minutes
            $ttl = ($this->hasRSVPEnded() || $this->hasEnded())  ? (3600*rand(1,3)): (600*rand(1,3));
            $value = self::DBROGet("SELECT IFNULL(u.firstname,'Deleted') as firstname, IFNULL(u.lastname,'User') as lastname, u.picture,j.userid, j.other_data FROM eventjoiners j LEFT JOIN users u USING (userid) WHERE eventid={$this->id} AND j.joinstatus in (1,2,11,12,21,22) LIMIT 25");
            foreach($value as &$row){
                if (!$row['userid']) {
                    $otherData = json_decode($row['other_data'],true) ?? [];
                    if (array_key_exists('firstname',$otherData)){
                        $row['firstname'] = $otherData['firstname'];
                        $row['lastname'] = $otherData['lastname'];
                    }
                }
            }
            unset($row);
            $_COMPANY->putInRedisCache($key, $value, $ttl);
        }
        shuffle($value); // Randomize it
        return array_slice($value, 0, $limit);
    }

	public function getMyRsvpStatus() {
        global $_USER; /* @var User $_USER */
        return $this->getUserRsvpStatus($_USER->id());
	}

    public function getUserRsvpStatus (int $whoid) {
        $retVal = 0;

        $s = self::DBGet("SELECT joinstatus FROM eventjoiners WHERE eventid='{$this->id}' AND userid='{$whoid}'");

        if (count($s))
            $retVal = $s[0]['joinstatus'];

        return $retVal;
    }

    public function isUserAlreadyInvited (int $userid)
    {
        $s = self::DBGet("SELECT joinstatus FROM eventjoiners WHERE eventid='{$this->id}' AND userid={$userid}");
        return !empty($s);
    }
//
//    public function getMyInvitationStatus () {
//        global $_USER;
//        return $this->isUserAlreadyInvited($_USER->id());
//    }

    public function selfCheckIn(): int
    {
        global $_USER;
        return $this->checkInByUserid($_USER->id(), Event::EVENT_CHECKIN_METHOD['WEB_FULL_SIGNIN']);
    }

    public function checkInByUserid(int $uid, string $checkInMethod): int
    {
        $rsvpDetail = $this->getEventRsvpDetailByUserid($uid);
        if ($rsvpDetail && $rsvpDetail['checkedin_date']) {
            Logger::AuditLog('User already checked in ', ['Existing Checkin' => $rsvpDetail, 'New Checkin' => ['checkedin_by'=>$checkInMethod, 'checkedin_date'=> gmdate('Y-m-d H:i:s')]]);
            return $rsvpDetail['joineeid'];
        }

        $q = self::DBCall("call Event_UserCheckin({$this->id},{$uid},'{$checkInMethod}')");

        Points::HandleTrigger('EVENT_CHECK_IN', [
            'eventId' => $this->id(),
            'userId' => $uid,
        ]);

        return $q['impacted_rows'];
    }

    public function nonAuthenticatedCheckin(string $firstname, string $lastname, string $email, string $checkInMethod, ?string $externalid)
    {
        // Get the user id who is checking in.
        // If user is not found by email, try the ExternalUserName as secondary email might be stored there
        if ($email) {
            $checkin_user = User::GetUserByEmail($email)
                ?? User::GetUserByExternalUserName($email)
                ?? User::GetUserByEmailUsingAnyConfiguredDomain($email);
        } elseif ($externalid) {
            $checkin_user = User::GetUserByExternalId($externalid);
            if (!$checkin_user) {
                return 0; // If external id is provided, it should match a user.
            }
        }

        $checkin_userid = $checkin_user ? $checkin_user->id() : 0;

        // If user is already checked in then return existing joineeid
        if ($checkin_userid) {
            $rsvpDetail = $this->getEventRsvpDetailByUserid($checkin_userid);
            if ($rsvpDetail) {
                if ($rsvpDetail['checkedin_date']) {
                    Logger::AuditLog('User already checked in ', ['Existing Checkin' => $rsvpDetail, 'New Checkin' => ['first_name' => $firstname, 'last_name' => $lastname, 'Email' => $email, 'checkedin_by' => $checkInMethod, 'checkedin_date' => gmdate('Y-m-d H:i:s')]]);
                } else {
                    self::DBMutatePS("UPDATE `eventjoiners` SET checkedin_date=now(), checkedin_by=? WHERE joineeid=?",'xi', $checkInMethod, $rsvpDetail['joineeid']);

                    Points::HandleTrigger('EVENT_CHECK_IN', [
                        'eventId' => $this->id(),
                        'userId' => $checkin_userid,
                    ]);
                }
                return $rsvpDetail['joineeid'];
            }
        } elseif ($email) {
            $email_filter = '%\"email\":\"' . $email . '\"%';
            $rsvpDetailRows = self::DBGetPS("SELECT `joineeid` FROM `eventjoiners` WHERE `eventid` = ? AND `other_data` like ?", 'ix', $this->id(), $email_filter);
            if ($rsvpDetailRows && $rsvpDetailRows[0]['checkedin_date']) {
                return intval($rsvpDetailRows[0]['joineeid']);
            }
        }

        // Else create a new record and return joineeid
        $data = json_encode(array("firstname" => $firstname, 'lastname' => $lastname, "email" => $email));
        $new_joineeid = self::DBInsertPS("INSERT INTO `eventjoiners`( `eventid`, `userid`, `checkedin_date`, `checkedin_by`, `other_data`) VALUES (?, {$checkin_userid}, NOW(),?,?)",'ixx',$this->id, $checkInMethod, $data);

        if ($checkin_userid && $new_joineeid) {
            Points::HandleTrigger('EVENT_CHECK_IN', [
                'eventId' => $this->id(),
                'userId' => $checkin_userid,
            ]);
        }

        return $new_joineeid;
    }

    public function externalCheckin_fetchExistingCheckinRecord (string $email)
    {
        // To be implemented in the future
    }
    /**
     * rsvpExternalEvent
     *
     * @param  string $firstname
     * @param  string $lastname
     * @param  string $email
     * @param  int $joinstatus
     * @param  string $eventJoinSurveyResponses
     * @return int
     */
    public function rsvpExternalEvent(string $firstname, string $lastname, string $email,int $joinstatus=0, string $eventJoinSurveyResponses='') {
        $data = json_encode(array("firstname" => $firstname, 'lastname' => $lastname, "email" => $email));
        $joinid =  self::DBInsertPS("INSERT INTO `eventjoiners`( `eventid`, `userid`, `joinstatus`, `joindate`, `joinmethod`, `other_data`) VALUES (?,'0',?,NOW(),1,?)",'iix',$this->id, $joinstatus, $data);

        if (!empty($eventJoinSurveyResponses)){
            $this->updateEventSurveyResponse($joinid,self::EVENT_SURVEY_TRIGGERS['EXTERNAL_PRE_EVENT'],$eventJoinSurveyResponses);
        }
        return $joinid;
    }

    /**
     * updateExternalEventRsvp
     *
     * @param  int $joineeid
     * @param  int $joinstatus
     * @param  string $eventJoinSurveyResponses
     * @return int
     */
    public function updateExternalEventRsvp(int $joineeid, int $joinstatus, string $eventJoinSurveyResponses)
    {
        self::DBMutatePS("UPDATE eventjoiners SET `joinstatus`=?,`joindate`=NOW() WHERE `eventid`=? and `joineeid`=?","iii",$joinstatus,$this->id(), $joineeid);

        if (!empty($eventJoinSurveyResponses)){
            $this->updateEventSurveyResponse($joineeid,self::EVENT_SURVEY_TRIGGERS['EXTERNAL_PRE_EVENT'],$eventJoinSurveyResponses);
        }
        return $joineeid;
    }

    public function getUserCheckinDate(int $userid) {
        $retVal = '';

        $check = self::DBGet("SELECT checkedin_date FROM eventjoiners WHERE eventid={$this->id} AND userid={$userid}");

        if (count($check) && ($check[0]['checkedin_date'] != NULL))
            $retVal = $check[0]['checkedin_date'];

        return $retVal;
    }

    public function getMyCheckinDate() {
        global $_USER; /* @var User $_USER */
        return $this->getUserCheckinDate($_USER->id());
    }

    /**
     * Returns true if event is part of a series and it is  head of the series
     * @return bool
     */
    public function isSeriesEventHead() {
        return ($this->val('event_series_id') && $this->val('event_series_id') == $this->id);
    }

    /**
     * Returns true if event is part of a series and it is not head of the series
     * @return bool
     */
    public function isSeriesEventSub() {
        return ($this->val('event_series_id') && $this->val('event_series_id') != $this->id);
    }

    /**
     * Returns true if the event is part of series or series head
     * @return bool
     */
    public function isSeriesEvent() {
        return $this->val('event_series_id') > 0;
    }

    public function isHoliday() {
        return ($this->val('eventclass')=== 'holiday');
    }

    public function isPrivateEvent() : bool
    {
        return ($this->val('isprivate') > 0);
    }

    public function isDynamicListEvent() : bool
    {
        return !empty($this->val('listids'));
    }

    public function isPublishedToEmail() {
        return ($this->val('publish_to_email') > 0);
    }

	public function hasStarted() {
		return (time() > strtotime($this->val('start').' UTC'));
	}

    public function hasCheckinStarted(int $delta=28800) {
        return (time() > (strtotime($this->val('start').' UTC')-$delta));
    }

    public function hasCheckinEnded(int $delta=28800) {
        return (time() > (strtotime($this->val('end').' UTC')+$delta));
    }

	public function hasEnded() {
		return (time() > strtotime($this->val('end').' UTC'));
	}

    /**
     * If rsvp_due by has been set then use it to calculate if the RSVP has ended, else return false
     * @return bool
     */
    public function hasRSVPEnded() {
        if (!empty($this->val('rsvp_dueby')) &&
            ($rsvp_time = strtotime($this->val('rsvp_dueby') . ' UTC')) !== false) {
            return (time() > $rsvp_time);
        } else {
            return false;
        }
    }

	/** Returns true from 15 minutes before start to 15 minutes after end */
    public function inProgress() {
        return ((time()+900 > strtotime($this->val('start').' UTC')) &&
                (time()-900 < strtotime($this->val('end').' UTC')));
    }

    /**
     * finds difference between start and end time in seconds
     * @return int duration in seconds
     */
    public function getDurationInSeconds():int {
        return (strtotime($this->val('end')) - strtotime($this->val('start')));
    }

	public function __toString() {
		return "Event ". parent::__toString();
	}

    public function updatePublishToEmail(int $flag) {
        global $_COMPANY;
        // Update publish_to_email flag for the event and all sub events.
        if (self::DBMutate("UPDATE `events` set `publish_to_email`={$flag} WHERE companyid={$_COMPANY->id()} AND (eventid={$this->id} OR event_series_id={$this->id})")) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['publish_to_email' => $flag]);
            $this->fields['publish_to_email'] = (string)$flag;
        }
        $_COMPANY->expireRedisCache("EVT:{$this->id}");
    }

    public function isLimitedCapacityInperson(): bool
    {
        $att_type = (int)$this->val('event_attendence_type');
        return  (($att_type == 1 || $att_type ==3) ? $this->val('max_inperson') : 0) > 0;
    }

    public function isLimitedCapacityOnline(): bool
    {
        $att_type = (int)$this->val('event_attendence_type');
        return  (($att_type == 2 || $att_type ==3) ? $this->val('max_online') : 0) > 0;
    }

    /**
     * Returns true if the event isLimitedCapacity
     * @return bool
     */
    public function isLimitedCapacity(): bool
    {
        return $this->isLimitedCapacityInperson() || $this->isLimitedCapacityOnline();
    }

    public function sendIcal() : bool
    {
        global $_COMPANY;

        // Don't send ics file if the event is a series head
        if ($this->isSeriesEventHead())
            return false;

        // Don't send ics file if pre event survey is available and we are not building custom rsvp
        if ($_COMPANY->getAppCustomization()['event']['enable_event_surveys'] && $this->isEventSurveyAvailable(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT']))
            return false;

        // Don't send ics file if consents are required
        if ($this->isEventConsentRequired())
            return false;

        // Don't send ics file if RSVP is disabled
        if (!$this->isRsvpEnabled())
            return false;

        // Don't send ics file for limited capacity events
        if ($this->isLimitedCapacity())
            return false;

        return true;
    }

    /**
     * Returns the RSVP Options for the user identified by $_USER;
     * When showing on the web or phone
     *  - print out all the buttons,
     *  - Use 'my_rsvp_status' to select a button as current selection
     *  - Use mod 10 to find the remainder for button color section. Remainder of 1 => green, 2 => blue, 3 => red.
     *
     */
	public function getMyRSVPOptions (): array {
        global $_USER, $_COMPANY, $_ZONE;
        $retVal = array();
        $buttons = array();
        $att_type = (int)$this->val('event_attendence_type');
        $message = '';
        $message1 = '';
        $message2 = '';

        $retVal['my_rsvp_status']       = $_USER ? (int)$this->getMyRsvpStatus() : 0;
        $retVal['max_inperson']         = (int)(($att_type == 1 || $att_type ==3) ? $this->val('max_inperson') : 0);
        $retVal['max_inperson_waitlist']= (int)(($att_type == 1 || $att_type ==3) ? $this->val('max_inperson_waitlist') : 0);
        $retVal['max_online']           = (int)(($att_type == 2 || $att_type ==3) ? $this->val('max_online') : 0);
        $retVal['max_online_waitlist']  = (int)(($att_type == 2 || $att_type ==3) ? $this->val('max_online_waitlist') : 0);
        $retVal['available_inperson']   = $retVal['max_inperson'] ? ($retVal['max_inperson'] - (int)$this->getRsvpInPersonYesCount()) : 0;
        $retVal['available_online']     = $retVal['max_online'] ?  ($retVal['max_online'] - (int)$this->getRsvpOnlineYesCount()) : 0;
        $retVal['available_inperson_waitlist']   = $retVal['max_inperson_waitlist'] ? ($retVal['max_inperson_waitlist'] - (int)$this->getRsvpInPersonWaitCount()) : 0;
        $retVal['available_online_waitlist']     = $retVal['max_online_waitlist'] ?  ($retVal['max_online_waitlist'] - (int)$this->getRsvpOnlineWaitCount()) : 0;

        $parentEvent = null;
        if ($this->val('event_series_id')) {
            $parentEvent = Event::GetEvent($this->val('event_series_id'));
            if ($parentEvent?->val('rsvp_restriction') == self::EVENT_SERIES_RSVP_RESTRICTION['SINGLE_EVENT_ONLY']) {
                $message = gettext("This event is part of an event series with restricted attendance. You may only attend one event in this series.") . "<br><br>";
            }
        }

        if ($this->hasRSVPEnded()) {
            $message = gettext("Sorry, this Event RSVP has closed. We are no longer accepting RSVP updates for this event.");
//        }elseif ($this->isPrivateEvent() && !$this->getMyInvitationStatus()) {
//            $message = "This is a private Event. RSVP options are restricted to users on the invitation list";
        } elseif ($parentEvent?->val('rsvp_restriction') == self::EVENT_SERIES_RSVP_RESTRICTION['SINGLE_EVENT_ONLY'] && ($conflictedEvent = $this->checkRsvpConflictForSeriesEventForUser($_USER->id()))) {
            $evtLink = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'eventview?id=' . $_COMPANY->encodeId($conflictedEvent['eventid']);
            $message = sprintf(gettext("This event is part of an event series with restricted attendance. You may only attend one event in this series. If you wish to join this event, you must first decline your RSVP for %s event in the series"), '<a href="'.$evtLink.'">'.$conflictedEvent["eventtitle"].'</a>');
        } elseif ($parentEvent?->val('rsvp_restriction') == self::EVENT_SERIES_RSVP_RESTRICTION['ANY_NUMBER_OF_NON_OVERLAPPING_EVENTS'] && ($conflictedEvent = $this->checkScheduleConflictForSeriesEventForUser($_USER->id()))) {
            $evtLink = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'eventview?id=' . $_COMPANY->encodeId($conflictedEvent['eventid']);
            $message = sprintf(gettext('You have already joined an event that has a schedule conflict with this event. If you wish to join this event, first update your RSVP for %s to decline to join.'),'<a href="'.$evtLink.'">'.$conflictedEvent["eventtitle"].'</a>');
        } elseif ($retVal['max_inperson'] || $retVal['max_online']) {
            if (!$this->isParticipationLimitUnlimited('max_inperson') || !$this->isParticipationLimitUnlimited('max_online')) {
                $message .= gettext('This is a limited capacity event.');
            }
            if ($retVal['max_inperson']) {
                if ($retVal['available_inperson'] > 0 || $retVal['my_rsvp_status'] === self::RSVP_TYPE['RSVP_INPERSON_YES']) {
                    $seats_available = $this->isParticipationLimitUnlimited('max_inperson') ? 'Unlimited' : $retVal['available_inperson'];
                    $buttons[self::RSVP_TYPE['RSVP_INPERSON_YES']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_YES'], true);
                    if ($retVal['my_rsvp_status'] === self::RSVP_TYPE['RSVP_INPERSON_YES'])
                        $message1 .= sprintf(gettext(' Your seat is confirmed to "%s" !'),self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_YES']));
                    else                    
                        $message2 .=  sprintf(gettext(' There are %1$s seats available to "%2$s" reserve your seat by clicking on the "%2$s" button below.'), $seats_available, self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_YES']));

                } elseif ($retVal['available_inperson_waitlist'] > 0 || $retVal['my_rsvp_status'] === self::RSVP_TYPE['RSVP_INPERSON_WAIT']) {
                    $buttons[self::RSVP_TYPE['RSVP_INPERSON_WAIT']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_WAIT'], true);
                    if ($retVal['my_rsvp_status'] === self::RSVP_TYPE['RSVP_INPERSON_WAIT'])
                       
                        $message1 .= sprintf(gettext(' You are on the waitlist to "%1$s"! Your seat will be automatically confirmed if one becomes available.'), self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_YES']));

                    else                       

                        $message2 .=  sprintf(gettext(' No seats are available to "%1$s"; you can choose to be on the waitlist by clicking on the "%2$s" button below.'), self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_YES']), self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_WAIT']));
                } else {                  

                        $message2 .=  sprintf(gettext(' No seats or waitlist options are available to "%1$s"'), self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_YES']));
                }
            }
            if ($retVal['max_online']) {
                if ($retVal['available_online'] > 0 || $retVal['my_rsvp_status'] === self::RSVP_TYPE['RSVP_ONLINE_YES']) {
                    $seats_available = $this->isParticipationLimitUnlimited('max_online') ? 'Unlimited' : $retVal['available_online'];
                    $buttons[self::RSVP_TYPE['RSVP_ONLINE_YES']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_YES'], true);
                    if ($retVal['my_rsvp_status'] === self::RSVP_TYPE['RSVP_ONLINE_YES'])

                        $message1 .= sprintf(gettext('Your seat is confirmed to "%1$s"!'), self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_YES']));

                    else
                        
                        $message2 .=  sprintf(gettext(' There are %1$s seats available to "%2$s"; reserve your seat by clicking on the "%2$s" button below.'), $seats_available, self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_YES']));

                        
                } elseif ($retVal['available_online_waitlist'] > 0 || $retVal['my_rsvp_status'] === self::RSVP_TYPE['RSVP_ONLINE_WAIT']) {
                    $buttons[self::RSVP_TYPE['RSVP_ONLINE_WAIT']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_WAIT'], true);
                    if ($retVal['my_rsvp_status'] === self::RSVP_TYPE['RSVP_ONLINE_WAIT'])
                       
                        $message1 .= sprintf(gettext(' You are on the waitlist to "%1$s"! Your seat will be automatically confirmed if one becomes available.'), self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_YES']));

                    else {
                        if (strpos($message2, " No seats are available") === FALSE)
                                                    
                            $message2 .=  sprintf(gettext(' No seats are available to "%1$s"; you can choose to be on the waitlist by clicking on the "%2$s" button below.'), self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_YES']), self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_WAIT']));

                        else // Replace the message as no seats in either category are available
                            
                            $message2 =  sprintf(gettext(' No seats are available to "%1$s" or "%2$s"; you can choose to be on the waitlist by clicking on a "ADD TO WAITLIST" options below.'), self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_YES']), self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_YES']));
                    }
                } else {
                    if (strpos($message2, " No seats or waitlist options ") === FALSE)
                        
                        $message2 .=  sprintf(gettext(' No seats or waitlist options are available to "%1$s"'), self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_YES']));
                    else
                        $message2 =  sprintf(gettext(' No seats or waitlist options are available to "%1$s" or "%2$s"'), self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_YES']), self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_YES']));
                }
            }
            if (empty($message1)) {
                $message .= $message2;
                if (!empty($buttons)) {
                    // Add RSVP No button only if there is another button available.
                    $buttons[self::RSVP_TYPE['RSVP_NO']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_NO'], true);
                } elseif ($retVal['my_rsvp_status'] === self::RSVP_TYPE['RSVP_NO']) {
                    $message .= gettext(". You declined this event in the past");
                } else {
                    $message .= ".";
                }
            } else {
                $message .= $message1;
                $message .=  sprintf(gettext(' If you cannot attend this event, please let us know by clicking on the "%1$s" button below.'), self::GetRSVPLabel(self::RSVP_TYPE['RSVP_NO']));

                $buttons[self::RSVP_TYPE['RSVP_NO']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_NO'], true);
            }
        } else {
            $buttons[self::RSVP_TYPE['RSVP_YES']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_YES'], true);
            $buttons[self::RSVP_TYPE['RSVP_MAYBE']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_MAYBE'], true);
            $buttons[self::RSVP_TYPE['RSVP_NO']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_NO'], true);
        }

        $retVal['buttons'] = $buttons;
        $retVal['message'] = $message;
        return $retVal;
    }

    /**
     * This method is used to get a list of all rsvp options for the event regardless of start/end time etc.
     */
    public function getAllRSVPOptionsForManagement (): array {
        global $_USER; /* @var User $_USER */
        $retVal = array();
        $buttons = array();
        $att_type = (int)$this->val('event_attendence_type');


        $retVal['max_inperson']         = (int)(($att_type == 1 || $att_type ==3) ? $this->val('max_inperson') : 0);
        $retVal['max_inperson_waitlist']= (int)(($att_type == 1 || $att_type ==3) ? $this->val('max_inperson_waitlist') : 0);
        $retVal['max_online']           = (int)(($att_type == 2 || $att_type ==3) ? $this->val('max_online') : 0);
        $retVal['max_online_waitlist']  = (int)(($att_type == 2 || $att_type ==3) ? $this->val('max_online_waitlist') : 0);
        $retVal['available_inperson']   = $retVal['max_inperson'] ? ($retVal['max_inperson'] - (int)$this->getRsvpInPersonYesCount()) : 0;
        $retVal['available_online']     = $retVal['max_online'] ?  ($retVal['max_online'] - (int)$this->getRsvpOnlineYesCount()) : 0;
        $retVal['available_inperson_waitlist']   = $retVal['max_inperson_waitlist'] ? ($retVal['max_inperson_waitlist'] - (int)$this->getRsvpInPersonWaitCount()) : 0;
        $retVal['available_online_waitlist']     = $retVal['max_online_waitlist'] ?  ($retVal['max_online_waitlist'] - (int)$this->getRsvpOnlineWaitCount()) : 0;

        if ((int)($retVal['max_inperson'] == 0 && $retVal['max_online'] == 0)) {
            $buttons[self::RSVP_TYPE['RSVP_YES']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_YES']);
            $buttons[self::RSVP_TYPE['RSVP_MAYBE']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_MAYBE']);
        }

        if ($retVal['max_inperson'] > 0) {
            $buttons[self::RSVP_TYPE['RSVP_INPERSON_YES']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_YES']);
            $buttons[self::RSVP_TYPE['RSVP_INPERSON_WAIT']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_WAIT']);
            $buttons[self::RSVP_TYPE['RSVP_INPERSON_WAIT_CANCEL']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_WAIT_CANCEL']);
        }

        if ($retVal['max_online'] > 0) {
            $buttons[self::RSVP_TYPE['RSVP_ONLINE_YES']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_YES']);
            $buttons[self::RSVP_TYPE['RSVP_ONLINE_WAIT']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_WAIT']);
            $buttons[self::RSVP_TYPE['RSVP_ONLINE_WAIT_CANCEL']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_WAIT_CANCEL']);
        }

        $buttons[self::RSVP_TYPE['RSVP_NO']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_NO']);

        $retVal['buttons'] = $buttons;
        $retVal['message'] = '';
        return $retVal;
    }

    /** Provides a string labs based on RSVP status
    * @param int $rsvp_status
    * @param bool $localized
    * @return mixed|string
    */
    public static function GetRSVPLabel (int $rsvp_status, bool $localized = false) {
        $rsvpLabelArr = array(
            self::RSVP_TYPE['RSVP_YES']           => $localized ? gettext('YES') : 'YES',
            self::RSVP_TYPE['RSVP_MAYBE']          => $localized ? gettext('TENTATIVE') : 'TENTATIVE',
            self::RSVP_TYPE['RSVP_NO']            => $localized ? gettext('DECLINE') : 'DECLINE',
            self::RSVP_TYPE['RSVP_INPERSON_YES']    => $localized ? gettext('ATTEND IN PERSON') : 'ATTEND IN PERSON',
            self::RSVP_TYPE['RSVP_INPERSON_WAIT']   => $localized ? gettext('ADD TO WAITLIST (In Person)') : 'ADD TO WAITLIST (In Person)',
            self::RSVP_TYPE['RSVP_ONLINE_YES']      => $localized ? gettext('ATTEND ONLINE') : 'ATTEND ONLINE',
            self::RSVP_TYPE['RSVP_ONLINE_WAIT']     => $localized ? gettext('ADD TO WAITLIST (Online)') : 'ADD TO WAITLIST (Online)',
            self::RSVP_TYPE['RSVP_INPERSON_WAIT_CANCEL']   => $localized ? gettext('CANCEL WAITLIST (In Person)') : 'CANCEL WAITLIST (In Person)',
            self::RSVP_TYPE['RSVP_ONLINE_WAIT_CANCEL']     => $localized ? gettext('CANCEL WAITLIST (Online)') : 'CANCEL WAITLIST (Online)'
        );
        return $rsvpLabelArr[$rsvp_status] ?? '';
    }


    /**
     * @param string $system name of the system who is calling this method
     * @param string $email
     * @param string $udate DateTiem when the event was actually RSVP'ed
     * @param string $subject Subject of the Event. Used for logging,
     * @param string $uid Unique ID of the event
     * @param int $rsvp_status RSVP status maps to one of the self::RSVP_YES | self::RSVP_NO | self::RSVP_MAYBE
     * @param string $realm the realm that in which this rsvp was generated
     * @param string $rrule_string recurring rule - optional
     * @return int Return codes:
     *                  1: Procesed Sucessfully
     *                  0: not processed
     *                 -1: User/Company not found
     *                 -2: Event not found
     */
    public static function ProcessRsvpForBot(string $system, string $email, string $udate, string $subject, string $uid, int $rsvp_status, string $realm, string $rrule_string): int
    {
        // find the company for context, return error if not found
        if (($company = Company::GetCompanyByEmail($email)) === NULL)
            return -1;

        if ($realm) {
            $subdomain = explode('.', $realm, 2)[0];
            if ($subdomain != $company->val('subdomain'))
                return -1;
        }

        $event = null;
        if ($uid) {
            $event = self::GetEventByUid($company,$uid);
        } else {
            // UID is not provided, we will try best effort search by subject
            $event = self::GetEventByTitle($company,$subject);
        }
        if ($event === null)
            return -2;

        $retVal = 0;
        if (isset($_COMPANY) || isset($_ZONE)) {
            // We are not expecting $_COMPANY & $_ZONE to be set so skipping
            return 0;
        }

        $zone = $company->getZone($event->val('zoneid'));

        // We need to set $_COMPANY and $_ZONE to allow joinEvent method to work; reset before returning
        global $_COMPANY, $_ZONE;
        $_COMPANY = $company;
        $_ZONE = $zone;


        if ($event->isCancelled()) {
            $reason  = '<p><b>We could not process your RSVP as this event has been cancelled.</b></p>';
            $reason .= '<br><b>Cancellation Reason:</b>';
            $reason .= '<br>'.$event->val('cancellation_reason');
            $reason .= '<br>';
            self::EmailUserToRSVPOnline($event, $company, $zone, $email, $reason);
            return 1;
        }


        // Get the user id who is RSVP'ing.
        // If user is not found by email, try the ExternalUserName as secondary email might be stored there
        $who = User::GetUserByEmail($email)
                ?? User::GetUserByExternalUserName($email)
                ?? User::GetUserByEmailUsingAnyConfiguredDomain($email);

        if ($who) {
            if (((int)$event->val('max_inperson') || (int)$event->val('max_online')) && $rsvp_status != 3) {// Events which have remote participation on cannot be RSVP
                // For events with participation limit, we do not allow email RSVP's except for decline
                $existing_js = self::DBGet("SELECT joinstatus FROM `eventjoiners` WHERE eventid={$event->id()} AND userid={$who->id()}");
                if (empty($existing_js) ||
                    ($rsvp_status==1 && !in_array((int)$existing_js[0]['joinstatus'],array(1,11,21))) ||
                    ($rsvp_status==2 && !in_array((int)$existing_js[0]['joinstatus'],array(2,12,22)))
                ) {
                    $reason = 'We could not process your RSVP as this event has participation limits and email RSVPs are disabled';
                    self::EmailUserToRSVPOnline($event, $company, $zone, $email, $reason);
                }
                // Event processed successfully
                $retVal = 1;
            } else {

                // Todo: RRule String
                // There is a new function paramter $rrule_string; to be implemented by Vishwas
                //
                $joinEventStatus = $event->joinEvent($who->id(), $rsvp_status, 2, 0, $udate);

                $action = 'Error Processing';
                if ($joinEventStatus === 0) {
                    $action = 'Skipped';
                } elseif ($joinEventStatus === 1) {
                    $action = 'Updated';
                } elseif ($joinEventStatus === 2) {
                    $action = 'Added';
                }
                Logger::Log("{$system}: {$action} RSVP {$subject} [{$email} | {$udate} | {$rsvp_status} | {$uid} | {$rrule_string}]", Logger::SEVERITY['INFO']);
                self::DBInsert("INSERT INTO `appusage`(`userid`, `companyid`, `zoneid`, `usageif`) VALUES ({$who->id()},{$company->id()},{$event->val('zoneid')},'email')");
                // Event processed successfully
                $retVal = 1;
            }
        } else {
            // The user is not registered with the platform yet.
            // If the users email is from company domain, then send him an email asking to join the platform first.
            $reason = 'We could not process your RSVP request as your user account with email address '.$email.' was not found in the system.';
            self::EmailUserToRSVPOnline($event, $company, $zone, $email, $reason);
            // Event processed successfully
            $retVal = 1;
        }
        $_ZONE = null; // reset $_ZONE to null as we no longer need it.
        $_COMPANY = null; // reset $_COMPANY to null as we no longer need it.
        return $retVal;
    }

    /**
     * @param string $system name of the system who is calling this method
     * @param string $email
     * @param string $udate DateTime when the event was actually RSVP'ed
     * @param string $subject Subject of the Event. Used for logging,
     * @param string $uid Unique ID of the event
     * @param int $counter_dtstart New time proposed start date time
     * @param int $counter_dtend New time proposed end date time; we ignore it
     * @param string $realm the realm that in which this rsvp was generated
     * @param string $rrule_string recurring rule - optional ... future use only / not sure what to do about it yet,
     * @return int Return codes:
     *                  1: Procesed Sucessfully
     *                  0: not processed
     *                 -1: User/Company not found
     *                 -2: Event not found
     */
    public static function ProcessCounterForBot(string $system, string $email, int $udate, string $subject, string $uid, int $counter_dtstart, int $counter_dtend, string $realm, string $rrule_string): int
    {
        // find the company for context, return error if not found
        if (($company = Company::GetCompanyByEmail($email)) === NULL)
            return -1;

        if ($realm) {
            $subdomain = explode('.', $realm, 2)[0];
            if ($subdomain != $company->val('subdomain'))
                return -1;
        }

        $event = null;
        if ($uid) {
            $event = self::GetEventByUid($company,$uid);
        }
        if ($event === null)
            return -2;

        $retVal = 0;
        if (isset($_COMPANY) || isset($_ZONE)) {
            // We are not expecting $_COMPANY & $_ZONE to be set so skipping
            return 0;
        }

        if (strtotime($event->val('modifiedon') . ' UTC') > $udate) {
            // Skip, the event was updated since this counter was sent
            return 1;
        }

        if ( !$event->isActive()) { // Do not process counters for events which are not not active
            return 1;
        }

        $zone = $company->getZone($event->val('zoneid'));

        // We need to set $_COMPANY and $_ZONE to allow joinEvent method to work; reset before returning
        global $_COMPANY, $_ZONE;
        $_COMPANY = $company;
        $_ZONE = $zone;

        // Get the user id who is requesting a counter
        // If user is not found by email, try the ExternalUserName as secondary email might be stored there
        $who = User::GetUserByEmail($email)
            ?? User::GetUserByExternalUserName($email)
            ?? User::GetUserByEmailUsingAnyConfiguredDomain($email);

        $old_start = strtotime($event->val('start') . ' UTC');
        $new_start_in_epoch_secs = $counter_dtstart;//strtotime($counter_dtstart);
        $new_start = gmdate('Y-m-d H:i:s', $new_start_in_epoch_secs);
        $duration_seconds = $event->getDurationInSeconds();
        $new_end = gmdate('Y-m-d H:i:s', $new_start_in_epoch_secs + $duration_seconds);
        try {
            $new_start_in_event_tz_obj = new DateTime($new_start, new DateTimeZone('UTC'));
            $new_start_in_event_tz_obj->setTimezone(new DateTimeZone($event->val('timezone')));
            $new_start_in_event_tz = $new_start_in_event_tz_obj->format('F jS, Y \a\t g:i A T(P)');
        } catch (Exception $e) {
            $new_start_in_event_tz = $new_start . ' UTC';
        }

        if (abs($old_start - $new_start_in_epoch_secs) < 300) {
            return 1; // Skip if the start times differ only by 5 minutes
        }

        if ($who) {

            if (
                empty($event->val('teamid')) ||
                ($team = Team::GetTeam($event->val('teamid'))) == null ||
                !$team->isActive()
            ) {

                // Do not process counters for events which are not associated with Team or are not active
                return 1;

            } elseif (
                !$event->val('groupid') ||
                !($group = Group::GetGroup($event->val('groupid'))) ||
                !($touchPointTypeConfig = $group->getTouchpointTypeConfiguration()) ||
                !$touchPointTypeConfig['auto_approve_proposals']
            ) {

                // Do not process counters for events where they are not configured at the group touchpoint
                // configuration level
                return 1;

            } else {

                $team_members = $team->getTeamMembers(0);
                if (count($team_members) > 6) {
                    return 1; // Do not process counters for large teams, larger than 6.
                }
                $member_record = Arr::SearchColumnReturnColumnVal($team_members, $who->id(), 'userid', 'email');
                if (empty($member_record)) {
                    return 1;
                }
            }

            $retVal = 1; // Regardless of whether we can update event or not consider this function a success
            $event->updateEventStartEndDates($new_start, $new_end, "<p style=\"background-color:#EEEEEE;margin: 5px; padding: 5px; font-size: 12px;\">New time: {$new_start_in_event_tz} proposed by {$email} has been automatically processed.<p>");
            $job = new EventJob($event->val('groupid'), $event->id());
            $job->saveAsBatchUpdateType(1,[1,2]);
            Logger::Log("{$system}: COUNTER {$subject} [{$email} | {$udate} | {$counter_dtstart} | {$counter_dtend} | {$uid} | {$rrule_string}]", Logger::SEVERITY['INFO']);
        }
        $_ZONE = null; // reset $_ZONE to null as we no longer need it.
        $_COMPANY = null; // reset $_COMPANY to null as we no longer need it.
        return $retVal;
    }

    /**
     * @param Event $event
     * @param $company
     * @param $zone
     * @param string $email
     * @param string $reason
     */
    protected static function EmailUserToRSVPOnline(Event $event, $company, $zone, string $email, string $reason): void
    {
        $eventtitle = html_entity_decode($event->val('eventtitle'));
        // Send an email to user
        $url = $company->getAppURL($zone->val('app_type')) . 'eventview?id=' . $company->encodeId($event->id());
        $from_label = $zone->val('email_from_label');
        $rsvpSubject = 'Action Required: Unable to process your RSVP request';
        $app_type = $zone->val('app_type');

        $emesg = <<<EOMEOM

        <p>Thank you for RSVP'ing to the event {$eventtitle}.</p>
        <p>&nbsp;</p>
        <p>{$reason}</p>
        <p>&nbsp;</p>
        <p>You can RSVP online by visiting the event page: <a href='{$url}'>{$url}</a></p>

EOMEOM;
        $template = $company->getEmailTemplateForNonMemberEmails();
        $emesg = str_replace('#messagehere#', $emesg, $template);
        $company->emailSend2($from_label, $email, $rsvpSubject, $emesg, $app_type);
    }


    public function getEventUid()
    {
        if (empty($this->val('event_uid'))) {
            // Generate and set it
            $uid = 'e1_'. base64_url_encode(json_encode(array('e'=>$this->id())));
            self::DBUpdate("UPDATE events set event_uid='{$uid}' WHERE eventid={$this->id}");
            $this->fields['event_uid'] = $uid;
            return $uid;
        }
        return $this->val('event_uid');
    }

    /**
     * This function gets a Event that matches the UID
     * @param Company $company
     * @param string $uid
     * @return Event|null event or null if no matching future event is found
     */
    private static function GetEventByUid(Company $company, string $uid)
    {
        $companyid = $company->id();
        if (strpos($uid,'e1_') === 0) {
            // New UID format effective 07/31/2022
            $uid = substr($uid,3); // remove 'e1_';
            $uid_json = json_decode(base64_url_decode($uid),true);
            $eventid = $uid_json['e'] ?? '0';
            $events = self::DBGet("SELECT * FROM `events` WHERE `companyid`='{$companyid}' AND eventid={$eventid}");
            if (!empty($events)) {
                return new Event($eventid, $companyid, $events[0]);
            }
        } elseif(0) { // Old UID format -- commented out on 2024-03-08
            $events = self::DBGet("SELECT * FROM `events` WHERE `companyid`='{$companyid}' AND (`isactive`=1 AND start > NOW() - INTERVAL 30 DAY ) ORDER BY `eventid` DESC");
            for ($eventIter = 0; $eventIter < count($events); $eventIter++) {
                $eventid = $events[$eventIter]['eventid'];
                $groupid = $events[$eventIter]['groupid'];
                $uid_new = md5("WEQA" . $eventid . "YFDS" . $groupid);
                if ($uid_new === $uid) {
                    return new Event($eventid, $companyid, $events[$eventIter]);
                }
            }
        }
        return null;
    }

    /**
     * Tries to find a matching event by title.
     * This is a best effort match and can be useful for RSVP processing. Do not use it for any other purpose
     * @param Company $company
     * @param string $title
     * @return Event|null event or null
     */
    private static function GetEventByTitle(Company $company, string $title)
    {
        $companyid = $company->id();

        // On 2024-03-08 added a check to exclude team touchpoint events; so that we are no matching them by title.
        // There can be many team touchpoints with same title in different teams with similar dates
        $matching_events = self::DBGetPS('
            SELECT * FROM events
            WHERE companyid=?
              AND (eventtitle=? AND isactive=1 AND teamid=0 AND `start` > NOW() - INTERVAL 1 DAY )',
            'is', $companyid, $title);

        if (count($matching_events) === 1) {
            return new Event($matching_events[0]['eventid'], $companyid, $matching_events[0]);
        }
        return null;

    }

    /**
     * Returns all event types for provided zones.
     * @param array $zoneids
     * @param bool $activeOnly
     * @return array
     */
    public static function GetEventTypesByZones(array $zoneids, bool $activeOnly=true) : array
    {
        global $_COMPANY, $_ZONE;

        $isActiveFilter = '';
        if ($activeOnly) {
            $isActiveFilter = ' AND isactive=1 ';
        }

        $zoneid_list = '0'; // By default we will return the event types that belong to global zone
        $zoneids = Sanitizer::SanitizeIntegerArray($zoneids);
        if(!empty($zoneids)) {
            $zoneid_list = implode(',', $zoneids) . ',0';
        }
        return self::DBROGet("SELECT * FROM `event_type` WHERE `companyid`={$_COMPANY->id()}  AND `zoneid` IN ({$zoneid_list}) {$isActiveFilter} ORDER BY type");
    }

    /**
     * This method confirms seats for any users who are on the waitlist for events that have participation limit.
     * @param int $queueType, 1 for inperson attendance queue, 2 for online attendance queue.
     */
    public function processWaitlist(int $queueType): void
    {
        $rsvpWait = ($queueType === 2) ? self::RSVP_TYPE['RSVP_ONLINE_WAIT'] : self::RSVP_TYPE['RSVP_INPERSON_WAIT'];
        $rsvpYes = ($queueType === 2) ? self::RSVP_TYPE['RSVP_ONLINE_YES'] : self::RSVP_TYPE['RSVP_INPERSON_YES'];
        $limit = ($queueType === 2) ? $this->val('max_online') : $this->val('max_inperson');

        // If no limit on count return;
        if (!$limit)
            return;

        do {
            $keep_trying = false;
            // Using prepared statement to nicely wrap it up in a transaction
            $callResult = self::DBCall("call Event_Processwaitlist({$this->id}, {$rsvpYes}, {$rsvpWait}, {$limit})");
            $w_uid = $callResult['impacted_id'];
            if ($w_uid > 0) {
                $job = new EventJob($this->val('groupid'), $this->id());
                $job->sendRsvp($w_uid, $rsvpYes);
                $keep_trying = true;
            }
        } while ($keep_trying);
    }

    /**
     * Updates the eventjoiners for given user and joinstatus.
     * @param int $userid
     * @param int $joinstatus
     * @param int $joinmethod
     * @param int $sendrsvp
     * @param int $joindate
     * @param int $joinee_id useful for updating records of users who no longer have a userid, i.e. deleted users or external users
     * @return int 1 on sucess(updated), 2 on success (added), 0 if join was ignored, -1 on internal error, -2 on email error, -3 for single event restriction conflict, -4 for schedule conflict
     */
    public function joinEvent (int $userid, int $joinstatus, int $joinmethod, int $sendrsvp, int $joindate=0, int $joinee_id = 0):int
    {
        global $_COMPANY, $_ZONE;
        if (!$_COMPANY or !$_ZONE) {
            // Since this method can be called by email handlers do no proceed if $_ZONE or $_COMPANY are not set.
            return 0;
        }

        if (!$joindate) {
            $joindate = time();
        }

        $rsvplimit = -1;
        if ($joinstatus === self::RSVP_TYPE['RSVP_INPERSON_YES']) {
            $rsvplimit = ($this->val('max_inperson') > 0) ? (int)$this->val('max_inperson') : $rsvplimit;
        } elseif ($joinstatus === self::RSVP_TYPE['RSVP_INPERSON_WAIT']) {
            $rsvplimit = (int)$this->val('max_inperson_waitlist');
        } elseif ($joinstatus === self::RSVP_TYPE['RSVP_ONLINE_YES']) {
            $rsvplimit = ($this->val('max_online') > 0) ? (int)$this->val('max_online') : $rsvplimit;
        } elseif ($joinstatus === self::RSVP_TYPE['RSVP_ONLINE_WAIT']) {
            $rsvplimit = (int)$this->val('max_online_waitlist');
        }

        $event_series_id = $this->val('event_series_id');
        $event_series_maxevents = 0;
        if ($event_series_id) {
            $event_series = Event::GetEvent($event_series_id);
            $event_series_maxevents = ($event_series->val('rsvp_restriction') == 2)? 1 : 0;

            if (in_array($joinstatus, array(1,2,11,12,21,22))) {
                if (($event_series?->val('rsvp_restriction') == self::EVENT_SERIES_RSVP_RESTRICTION['SINGLE_EVENT_ONLY']) && ($conflictedEvent = $this->checkRsvpConflictForSeriesEventForUser($userid))) {
                    return -3;
                } elseif (($event_series?->val('rsvp_restriction') == self::EVENT_SERIES_RSVP_RESTRICTION['ANY_NUMBER_OF_NON_OVERLAPPING_EVENTS']) && ($conflictedEvent = $this->checkScheduleConflictForSeriesEventForUser($userid))) {
                    return -4;
                }
            }
        }

        $callResult = self::DBCall("call Event_RSVPUpdate3({$this->id},{$userid},{$joinee_id},{$joinstatus},{$rsvplimit},{$joinmethod},{$joindate},{$event_series_id},{$event_series_maxevents})");
        $insertid = $callResult['insert_id'];
        $updated = $callResult['impacted_rows'];
        $impacted_id = $callResult['impacted_id'];
        $retVal = ($insertid) ? 2 : ($updated ? 1 : 0);

        if (!$updated) {
            return 0;
        } elseif ($callResult['error_code']) {
            return -1;
        }

        $this->invalidateJoinersCache();

        if ($joinstatus === self::RSVP_TYPE['RSVP_NO']) {
            if ($insertid) {
                if ($this->isLimitedCapacity() || !$this->isPublishedToEmail() || !$this->isRsvpEnabled()) {
                    // Do not send RSVP's for first time de
                    $sendrsvp = 0;
                }
            } else {
                if ($this->val('max_inperson') && $this->val('automanage_inperson_waitlist')) { //If this was decline update and there is a waitlist
                    $this->processWaitlist(1);
                }

                if ($this->val('max_online') && $this->val('automanage_inperson_waitlist')) { //If this was decline update and there is a waitlist
                    $this->processWaitlist(2);
                }
            }
        }

        if ($sendrsvp) {
            $job = new EventJob($this->val('groupid'),$this->id());
            if ($job->sendRsvp($userid,$joinstatus)) {
                return $retVal;
            } else {
                return -2;
            }
        } else {
            return $retVal;
        }
    }

    /**
     * Get event custom fields detail
     */
    public static function GetEventCustomFieldDetail(int $custom_field_id) {
        global $_COMPANY;
        global $_ZONE;
        $data = null;
        $d =  self::DBGet("SELECT * FROM `event_custom_fields` WHERE `companyid`='{$_COMPANY->id()}' AND `custom_field_id`='{$custom_field_id}'");
        if (count($d)){
            $d[0]['options'] = self::DBGet("SELECT * FROM `event_custom_field_options` WHERE `custom_field_id`='{$d[0]['custom_field_id']}' AND isactive =1");
            $d[0]['visible_if'] = $d[0]['visible_if'] ? json_decode($d[0]['visible_if'],true) : array();

            $data = $d[0];
        }
        return $data;
    }

    /**
     * Documentation? What is each field supposed to do
     * Add Event Custom field
     */
    public static function AddUpdateEventCustomField(int $custom_field_id, string $custom_field_name,int $custom_fields_type,bool $is_required,string $custom_field_note, string $visible_if, array $custom_field_option_ids, array $custom_fields_options, array $custom_fields_options_note, string $topictype = 'EVT')
    {

        global $_COMPANY;
        global $_ZONE;

        if (!in_array($custom_fields_type, [1,2,3,4]))
            return; // Invalid field type

        if ($custom_field_id){

            $retVal = self::DBUpdatePS("UPDATE `event_custom_fields` SET `custom_field_name`=?, `custom_fields_type`=?, `is_required`=?,`custom_field_note`=?,`visible_if`=? WHERE companyid=? AND custom_field_id=? ",'xiixxii',$custom_field_name,$custom_fields_type,$is_required, $custom_field_note, $visible_if, $_COMPANY->id(),$custom_field_id);
            if ($retVal) {
                self::LogObjectLifecycleAudit('update', 'event_custom_fields', $custom_field_id, 0);
            }

        } else {
            $custom_field_id =  self::DBInsertPS("INSERT INTO `event_custom_fields`( `companyid`, `zoneid`, `custom_field_name`, `custom_fields_type`, `is_required`,`custom_field_note`,`visible_if`,sorting_order, `topictype`) VALUES (?,?,?,?,?,?,?,(SELECT IFNULL(MAX(inner_ecf.sorting_order),0)+1 FROM `event_custom_fields` inner_ecf WHERE inner_ecf.companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()}), ?)",'iixiixxs',$_COMPANY->id(),$_ZONE->id(),$custom_field_name,$custom_fields_type,$is_required,$custom_field_note,$visible_if, $topictype);

        }
        if ($custom_field_id){
            self::AddUpdateCustomFieldOptons($custom_field_id,$custom_field_option_ids, $custom_fields_options, $custom_fields_options_note);
        }

        return $custom_field_id;

    }
    /**
     * Update Event custom fields options
     */
    /**
     * AddUpdateCustomFieldOptons
     *
     * @param  int $custom_field_id
     * @param  array $custom_field_option_ids
     * @param  array $custom_fields_options
     * @param  array $custom_fields_options_notes
     * @return int
     */
    public static function AddUpdateCustomFieldOptons(int $custom_field_id, array $custom_field_option_ids, array $custom_fields_options, array $custom_fields_options_notes) :int
    {
        $retVal = 0;
        $i = 0;
        $newOptionIds = array();
        foreach($custom_field_option_ids as $custom_field_option_id){
            $custom_fields_option = $custom_fields_options[$i];
            $custom_fields_options_note = $custom_fields_options_notes[$i];
            if ($custom_field_option_id) {
                $retVal =  self::DBUpdatePS("UPDATE `event_custom_field_options` SET `custom_field_option`=?,`custom_field_option_note`=?,`modifiedon`=NOW() WHERE `custom_field_option_id`=? AND `custom_field_id`=?",'xxii',$custom_fields_option,$custom_fields_options_note, $custom_field_option_id, $custom_field_id);
            } else {
                $retVal =  self::DBInsertPS("INSERT INTO `event_custom_field_options`( `custom_field_id`, `custom_field_option`, `custom_field_option_note`, `createdon`, `modifiedon`) VALUES (?,?,?,NOW(),NOW())",'ixx',$custom_field_id,$custom_fields_option,$custom_fields_options_note);
                $newOptionIds[] = $retVal;
            }
            $i++;
        }

        // Todo: Delete permanently only if the field is not currently in use by any event.... otherwise do not delete it.
        // Update status to inactive
        $custom_field_option_ids = implode(',' , array_merge($custom_field_option_ids,$newOptionIds));

        if(!empty($custom_field_option_ids)){
            self::DBMutate("UPDATE `event_custom_field_options` SET `isactive`=0 WHERE `custom_field_id`='{$custom_field_id}' AND `custom_field_option_id` NOT IN({$custom_field_option_ids})");
        }

        return $retVal;
    }


    /**
     * Create New Event
     */

    public static function CreateNewEvent(int $groupid, string $chapterids, string $eventtitle, string $start, string $end, string $event_tz, string $eventvanue, string $vanueaddress, string $event_description, int $eventtype, string $invited_groups, int $max_inperson, int $max_inperson_waitlist, int $max_online, int $max_online_waitlist, int $event_attendence_type, string $web_conference_link, string $web_conference_detail, string $web_conference_sp, int $checkin_enabled, string $collaborate, int $channelid, int $event_series_id, string $custom_fields_input, string $event_contact, string $venue_info, string $venue_room, int $isprivate, string $eventclass = 'event', int $add_photo_disclaimer=0, int $calendar_blocks=1, string $content_replyto_email = '', string $listids='0', bool $use_and_chapter_connector = false, int $form_validated = 0, string $disclaimerids='', int $rsvp_enabled = 1, string $event_contact_phone_number = '', string $event_contributors = '')
    {
        global $_COMPANY;
        global $_ZONE;
        global $_USER;

        ContentModerator::CheckBlockedWords($eventtitle, $event_description);

        $custom_fields_input = $custom_fields_input ?: '[]';

         // Business Rule: Collaborative events have chapters and channels set to zero.
        if (!empty($collaborate)) {
                 $channelid = 0;
                 $chapterids = '0';
        }

        $event_tz = $event_tz ?: 'UTC';

        // *** Business Rule - CONTENT__TARGETED_TO_LISTS_IS_PRIVATE ***
        // * List Id targeted events are always private
        if ($listids) {
            $isprivate = 1;
        }

        // *** Business Rule - EVENT__SERIES_SUB_EVENTS_DERIVE_ATTRIBUTES_FROM_SERIES_HEAD ***
        // * Update derived fields of sub events, the following are derived fields
        //      $chapterids, $channelid, $invited_groups, $listids, $isprivate, $and_chapter_connector
        $eventSeries = null;
        if ($event_series_id) {

            $eventSeries = Event::GetEvent($event_series_id);
            if (!$eventSeries || $groupid != $eventSeries->val('groupid'))
                return 0;

            $chapterids = $eventSeries->val('chapterid');
            $channelid = $eventSeries->val('channelid');
            $use_and_chapter_connector = $eventSeries->val('use_and_chapter_connector');
            $invited_groups = $eventSeries->val('invited_groups');
            $collaborate = $eventSeries->val('collaborating_groupids');
            $listids = $eventSeries->val('listids');
            $isprivate = $eventSeries->val('isprivate');
        } else {
            $event_series_id = 0;
        }

        // Extract hashtag handles
        $handleids = HashtagHandle::ExtractAndCreateHandles($event_description);
        $handleidsCsv = implode(',',$handleids);

        $latitude = '';
        $longitude = '';
        if (in_array($event_attendence_type, array(1, 3))) {
            list($latitude, $longitude) = self::ExtractLatLongFromVenue($eventvanue, $vanueaddress);
        }

        $invited_locations =0;

        $disclaimerids = Sanitizer::SanitizeIntegerCSV($disclaimerids);

        $retVal = self::DBInsertPS("INSERT INTO `events`(`companyid`,`zoneid`,`groupid`, `userid`,`chapterid`, `eventtitle`, `start`, `end`, `timezone`, `eventvanue`, `vanueaddress`,`event_description`,`latitude`, `longitude`, eventtype, `invited_groups`,`max_inperson`,`max_inperson_waitlist`,`max_online`,`max_online_waitlist`,`invited_locations`,`event_attendence_type`, `web_conference_link`, `web_conference_detail`,`web_conference_sp`,`checkin_enabled`,collaborating_groupids,channelid,event_series_id,`custom_fields`, `addedon`, `modifiedon`, `isactive`,`event_contact`,`isprivate`,`eventclass`,`add_photo_disclaimer`,`calendar_blocks`,`handleids`,`content_replyto_email`,`listids`, `use_and_chapter_connector`, `venue_info`, `venue_room`, `form_validated`,`disclaimerids`,`rsvp_enabled`, `event_contact_phone_number`, `event_contributors`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW(),'2',?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            'iiiixssssssmssisiiiisisssisiixxisiixsxissixixx',
            $_COMPANY->id(), $_ZONE->id(), $groupid, $_USER->id(), $chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $latitude, $longitude, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $invited_locations, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $event_series_id, $custom_fields_input, $event_contact, $isprivate, $eventclass,$add_photo_disclaimer,$calendar_blocks,$handleidsCsv,$content_replyto_email,$listids, $use_and_chapter_connector ? 1 : 0, $venue_info , $venue_room, $form_validated,$disclaimerids,$rsvp_enabled,$event_contact_phone_number,$event_contributors);

        if ($retVal) {
            // Post processing
            // Insert a row into event_counters
            self::DBMutate("INSERT INTO event_counters SET eventid={$retVal}");

            // Check if the event type had any attributes that need to be migrated to the event.
            if ($eventtype) {
                $eventTypeRow = Event::GetEventTypeById($eventtype);
                if (!empty($eventTypeRow) && !empty($eventTypeRow['attributes']) ) {
                    $eventAttributes = array();
                    $eventTypeAttributes = json_decode($eventTypeRow['attributes'],true);

                    // Migrate event_volunteer_requests a
                    $eventTypeAttributesVolunteerRequests = $eventTypeAttributes[self::EVENT_ATTRIBUTES['event_volunteer_requests']] ?? array();
                    if ($eventTypeAttributesVolunteerRequests) {
                        $eventAttributes[self::EVENT_ATTRIBUTES['event_volunteer_requests']] = $eventTypeAttributesVolunteerRequests;
                    }

                    if (!empty($eventAttributes)) {
                        $eventAttributesJson = json_encode($eventAttributes);
                        self::DBUpdate("UPDATE events SET attributes='{$eventAttributesJson}' WHERE companyid={$_COMPANY->id()} AND eventid={$retVal}");
                    }
                }
            }

            self::UpdateEventSeriesStartEndDates($event_series_id);

            self::LogObjectLifecycleAudit('create', 'event', $retVal, 1);

        }

        return $retVal;
    }

    /**
     * Get Event Type row for a given event type id.
     * @param int $typeid
     * @return array|mixed
     */
    public static function GetEventTypeById(int $typeid)
    {
        global $_COMPANY;
        $rows = self::DBGet("SELECT * FROM event_type WHERE companyid={$_COMPANY->id()} AND typeid={$typeid}");
        if (!empty($rows)) {
            return $rows[0];
        }
        return array();
    }

    /**
     * Update Event
     */
    public function updateEvent(string $chapterids, string $eventtitle, string $start, string $end, string $event_tz, string $eventvanue, string $vanueaddress, string $event_description, int $eventtype, string $invited_groups, int $max_inperson, int $max_inperson_waitlist, int $max_online, int $max_online_waitlist, int $event_attendence_type, string $web_conference_link, string $web_conference_detail, string $web_conference_sp, int $checkin_enabled, string $collaborate, int $channelid, string $custom_fields_input, string $event_contact, int $add_photo_disclaimer, string $venue_info, string $venue_room, int $isprivate, int $calendar_blocks, string $content_replyto_email ='', string $listids = '0', bool $use_and_chapter_connector = false, string $disclaimerids='', int $rsvp_enabled = 1, string $event_contact_phone_number='', string $event_contributors=''): int
    {

        global $_COMPANY;

        ContentModerator::CheckBlockedWords($eventtitle, $event_description);

        $custom_fields_input = $custom_fields_input ?: '[]';
        $organization_id = 0;

        // Business Rule:

        // *** Rule 1 *** Collaborative events have chapters and channels set to zero.
        if (!empty($collaborate)) {
            $channelid = 0;
            $chapterids = '0';
        }

        // *** Business Rule - CONTENT__TARGETED_TO_LISTS_IS_PRIVATE ***
        // * List Id targeted events are always private
        if ($listids) {
            $isprivate = 1;
        }

        // *** Business Rule - EVENT__SERIES_SUB_EVENTS_DERIVE_ATTRIBUTES_FROM_SERIES_HEAD ***
        // * Update derived fields of sub events, the following are derived fields
        //      $chapterids, $channelid, $invited_groups, $listids, $isprivate, $and_chapter_connector
        $event_series_id = $this->val('event_series_id');
        $eventSeries = null;
        if ($event_series_id) {
            $eventSeries = Event::GetEvent($event_series_id);
            if (!$eventSeries || $this->val('groupid') != $eventSeries->val('groupid'))
                return 0;

            $chapterids = $eventSeries->val('chapterid');
            $channelid = $eventSeries->val('channelid');
            $use_and_chapter_connector = $eventSeries->val('use_and_chapter_connector');
            $listids = $eventSeries->val('listids');
            $collaborate = $eventSeries->val('collaborating_groupids');
            $invited_groups = $eventSeries->val('invited_groups');
            $isprivate = $eventSeries->val('isprivate');
        }

        // *** Business Rule - EVENT__AFTER_PUBLISH_SOME_ATTRIBUTES_ARE_IMMUTABLE
        // If the value was originally saved as 's' type then note we need to reset using htmlspecialchars_decode()
        //  to avoid double encoding
        if ($this->isPublished()) {
            // Once published groupid, chapteris, channelid cannot be changed
            $chapterids = $this->val('chapterid');
            $channelid = $this->val('channelid');
            $listids = $this->val('listids');
            $collaborate = $this->val('collaborating_groupids');
            // We used to have the following constraints, but not sure why they were added.
            // For now this has been disbled, if needed in future lets add it and add the reason.
            $isprivate = empty($this->val('listids')) ? $isprivate : 1;  // If event is based on dynamic list then it is always private.
            //$checkin_enabled = $this->val('checkin_enabled');
        }

        // *** Business Rule - EVENT__DO_NOT_ALLOW_CUSTOM_FIELDS_TO_BE_CHANGED_IF_APPROVED_OR_IN_PROCESS
        // If the event actions are blocked due to approval process state, then reset the custom
        // fields to their existing value
        if ($this->isActionDisabledDuringApprovalProcess()) {
            $custom_fields_input = $this->val('custom_fields');
        }

        # Extract hashtag handles
        $handleids = HashtagHandle::ExtractAndCreateHandles($event_description);
        $handleidsCsv = implode(',',$handleids);

        $latitude = '';
        $longitude = '';
        if (in_array($event_attendence_type, array(1, 3))) {
            list($latitude, $longitude) = self::ExtractLatLongFromVenue($eventvanue, $vanueaddress);
        }
        $disclaimerids = Sanitizer::SanitizeIntegerCSV($disclaimerids);
        $retVal = self::DBUpdatePS("UPDATE `events` SET `chapterid`=?, `eventtitle`=?, `start`=?, `end`=?, `timezone`=?, `eventvanue`=?, `vanueaddress`=?, event_description=?,`latitude`=?, `longitude`=?, eventtype=?,invited_groups =?, max_inperson=?, max_inperson_waitlist=?, max_online = ?, max_online_waitlist = ?,`event_attendence_type`=?, `web_conference_link`=?, `web_conference_detail`=?,`web_conference_sp`=?, `checkin_enabled`=?,collaborating_groupids=?,channelid=?,`custom_fields`=?,`modifiedon`=now(),`version`=`version`+1, isactive=IF(isactive=3,2,isactive),`event_contact`=?,add_photo_disclaimer=?,isprivate=?,`calendar_blocks`=?,`handleids`=?,`content_replyto_email`=?,`listids`=?, `use_and_chapter_connector` = ?, `venue_info` = ?, `venue_room` = ?, `disclaimerids`=?, `rsvp_enabled`=?,`event_contact_phone_number`=?, `event_contributors`=? WHERE companyid=? AND (eventid=?)",
            'xssssssmssisiiiiisssisixxiiixsxissxixxii',
            $chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $latitude, $longitude, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $custom_fields_input, $event_contact, $add_photo_disclaimer, $isprivate, $calendar_blocks, $handleidsCsv, $content_replyto_email, $listids, $use_and_chapter_connector ? 1 : 0,  $venue_info,  $venue_room, $disclaimerids, $rsvp_enabled, $event_contact_phone_number, $event_contributors, $_COMPANY->id(), $this->id());

        $_COMPANY->expireRedisCache("EVT:{$this->id()}");
        $updatedEvent = self::GetEvent($this->id());

        self::UpdateEventSeriesStartEndDates($this->val('event_series_id'));

        if ($retVal) {
            if (!$this->isPublished() && ($approval = $this->getApprovalObject())) {
                if ($approval->isApprovalStatusDenied()) {
                    // If denied event is updated, reset its approval object.
                    $approval->reset('event updated');
                } elseif ($approval->isApprovalStatusApproved()) {
                    // The following is not needed now as we disallow important fields from being edited once the approval
                    // is in process see EVENT__DO_NOT_ALLOW_CUSTOM_FIELDS_TO_BE_CHANGED_IF_APPROVED_OR_IN_PROCESS
                    // But we may need to enable it in the future for some customers ... that is why leaving the
                    // code block below.
                    // See if event description has changed.
                    //$event_description_changed = $this->val('event_description') != $event_description;
                    //if ($event_description_changed) {
                    //    $approval->reset('event description updated');
                    //}
                }
            }

            // For published events which are limited capacity and if capacity increased, process the queues
            if ($this->isPublished()) {
                if ((int)$this->val('max_inperson') && (int)$this->val('max_inperson') < $updatedEvent->val('max_inperson')) {
                    // Confirm the seat for next person in the row.
                    // ***** Important: Run process waitlist on updated event as we need to use new capacities *****
                    $updatedEvent->processWaitlist(1);
                }
                if ((int)$this->val('max_online') && (int)$this->val('max_online') < $updatedEvent->val('max_online')) {
                    // Confirm the seat for next person in the row.
                    // ***** Important: Run process waitlist on updated event as we need to use new capacities *****
                    $updatedEvent->processWaitlist(2);
                }
            }

            self::LogObjectLifecycleAudit('update', 'event', $this->id(), $this->val('version'));
        }
		return $retVal;
    }

    public function updateEventStartEndDates(string $new_start, string $new_end, string $reason='')
    {
        global $_COMPANY;

        $retVal = self::DBUpdatePS("UPDATE `events` SET `start`=?, `end`=?,event_description=concat(?,event_description),`version`=`version`+1,modifiedon=now() WHERE companyid=? AND (eventid=?)",
            'xxxii',
                $new_start, $new_end, $reason, $_COMPANY->id(), $this->id());
        $_COMPANY->expireRedisCache("EVT:{$this->id()}");

        self::UpdateEventSeriesStartEndDates($this->val('event_series_id'));

        if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'event', $this->id(), $this->val('version'));
        }
        return $retVal;
    }

    public static function CreateNewEventSeries(int $groupid, string $event_series_name, string $event_description, string $chapterids, int $channelid, string $listids, string $invited_groups, int $rsvp_restriction, int $isprivate, bool $use_and_chapter_connector) : int
    {
        global $_COMPANY;

        // *** Business Rule - CONTENT__TARGETED_TO_LISTS_IS_PRIVATE ***
        // * List Id targeted events are always private
        if ($listids) {
            $isprivate = 1;
        }

        $tz = $_SESSION['timezone'];
        $event_series_id = Event::CreateNewEvent($groupid, $chapterids, $event_series_name, '', '', $tz, '', '', $event_description, 0, $invited_groups, 0, 0, 0, 0, 0, '', '', '', 0, '', $channelid, 0, '', '', '', '', $isprivate, 'eventgroup', 0, 1, '', $listids, $use_and_chapter_connector);
        if ($event_series_id) {
            // This series head, so update it to itself
            self::DBMutate("UPDATE events set rsvp_restriction={$rsvp_restriction},event_series_id={$event_series_id},start=now(),end=now() WHERE companyid={$_COMPANY->id()} AND eventid={$event_series_id}");
            self::LogObjectLifecycleAudit('create', 'event', $event_series_id, 0);
        }
        return $event_series_id;
    }


    public function updateEventSeries(string $event_series_name, string $event_description, string $chapterids, int $channelid, string $invited_groups, string $listids, int $rsvp_restriction, int $isprivate, bool $use_and_chapter_connector)
    {
        global $_COMPANY;
        $and_chapter_connector = $use_and_chapter_connector ? 1 : 0;

        ContentModerator::CheckBlockedWords($event_series_name, $event_description);

        // *** Business Rule - CONTENT__TARGETED_TO_LISTS_IS_PRIVATE ***
        // * List Id targeted events are always private
        if ($listids) {
            $isprivate = 1;
        }

        // *** Business Rule - EVENT__AFTER_SERIES_PUBLISH_SOME_ATTRIBUTES_ARE_IMMUTABLE ***
        // - Reset all items that become immutable after event series is published
        if ($this->isPublished()) {
            $chapterids = $this->val('chapterid');
            $channelid = $this->val('channelid');
            $invited_groups = $this->val('invited_groups');
            $listids = $this->val('listids');
            $isprivate = empty($this->val('listids')) ? $isprivate : 1;  // If event is based on dynamic list then it is always private.
            $and_chapter_connector = $this->val('use_and_chapter_connector');
        }

        $retVal = self::DBUpdatePS("UPDATE events set eventtitle=?,event_description=?,chapterid=?,channelid=?,invited_groups=?,listids=?,rsvp_restriction=?,isprivate=?,use_and_chapter_connector=?, version=version+1 WHERE companyid=? AND eventid=?",
            'smxixxiiiii',
            $event_series_name, $event_description, $chapterids, $channelid, $invited_groups, $listids, $rsvp_restriction, $isprivate, $and_chapter_connector, $_COMPANY->id(),$this->id());

        if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'event', $this->id(), $this->val('version'));

            // *** Business Rule - EVENT__SERIES_SUB_EVENTS_DERIVE_ATTRIBUTES_FROM_SERIES_HEAD ***
            // * Update derived fields of sub events, the following are derived fields
            //      $chapterids, $channelid, $invited_groups, $listids, $isprivate, $and_chapter_connector
            //
            $sub_events = Event::GetEventsInSeries($this->id());
            foreach($sub_events as $sub_event){
                $retVal = self::DBUpdatePS("UPDATE events set chapterid=?, channelid=?, invited_groups=?, listids=?, isprivate=?, use_and_chapter_connector=? WHERE companyid=? AND eventid=? AND event_series_id=?",
                    'xixxiiiii',
                    $chapterids, $channelid, $invited_groups, $listids, $isprivate, $and_chapter_connector, $_COMPANY->id(), $sub_event->id(), $this->id());
                $_COMPANY->expireRedisCache("EVT:{$this->id}");
                if ($retVal) {
                    self::LogObjectLifecycleAudit('update', 'event', $this->id(), $this->val('version'), [
                        'chapterid' => $chapterids,
                        'channelid' => $channelid,
                        'invited_groups' => $invited_groups,
                        'listids' => $listids,
                        'isprivate' => $isprivate,
                        'use_and_chapter_connector' => $and_chapter_connector
                    ]);
                }
            }
        }

        $_COMPANY->expireRedisCache("EVT:{$this->id()}");

        return $retVal;
    }

    /**
     * Cancels the events, sends cancelation events with cancellation reason if provided.
     * If the event was not published then the event will be deleted.
     * @param string $cancellationReason cancellationReason
     * @param bool $sendCancellationEmails set to true to send cancellation emails
     * @return int
     */
    public function cancelEvent (string $cancellationReason, bool $sendCancellationEmails, bool $deleteAfterCancel = false): int
    {
        global $_COMPANY;

        if ($this->isInactive()) {
            $_COMPANY->expireRedisCache("EVT:{$this->id}");
            return 1; // The event is alredy Inactive, skip rest of the process.
        }

        if (!$this->val('publish_to_email')) {
            // Double check: If the event was not published to email,
            // then never send out cancelled emails to group.
            $sendCancellationEmails = false;
        }

        $delete	= self::DBMutatePS("UPDATE events SET isactive=0, cancel_reason=?, canceledon=now() WHERE companyid=? AND eventid=?", 'xii', $cancellationReason, $_COMPANY->id, $this->id);
        if (!$delete) {
            return 0;
        }

        self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), [
            'state' => 'delete',
            'send_cancellation_emails' => $sendCancellationEmails,
            'delete_after_cancel' => $deleteAfterCancel
            ]);

        if ($this->isPublished()) {
            if (!$this->isSeriesEventHead()) {
              $job = new EventJob($this->val('groupid'), $this->id);
              // checkbox "Send cancellation emails" == $sendCancellationEmails
              $job->saveAsBatchDeleteType($sendCancellationEmails ? 1 : 0, $deleteAfterCancel);
            }
        } elseif ($this->isDraft() || $this->isUnderReview()) {
            // The event is not published yet, so we can safely delete it.
            $this->deleteIt();
        }
        $_COMPANY->expireRedisCache("EVT:{$this->id}");
        return 1;
    }

    /**
     * This method permanently deleted the event and its dependencies.
     * @return int
     */
    public function deleteIt()
    {
        global $_COMPANY;

        $this->deleteAllAttachments();

        self::DeleteAllComments_2($this->id);
        self::DeleteAllLikes($this->id);

        self::DBMutate("DELETE FROM event_speakers WHERE event_speakers.companyid={$_COMPANY->id()} AND eventid={$this->id()}");
        self::DBMutate("DELETE FROM eventjoiners WHERE eventid={$this->id()}");
        self::DBMutate("DELETE FROM event_counters WHERE eventid={$this->id()}");
        self::DBMutate("DELETE FROM event_volunteers WHERE eventid={$this->id()}");
        self::DBMutate("DELETE FROM event_reminder_history WHERE companyid={$_COMPANY->id()} AND eventid={$this->id()}");
        // set expense entries of event to 0
        $this->unlinkExpenseEntryFromEvent();
       
        $approval = $this->getApprovalObject();
        if ($approval) {
            Approval::DeleteApproval($approval->id());
        }

        $this->deleteEventScheduleData();

        $result = self::DBMutate("DELETE FROM events WHERE companyid={$_COMPANY->id()} AND eventid={$this->id()}");

        if ($result) {
            self::LogObjectLifecycleAudit('delete', 'event', $this->id(), $this->val('version'));
            self::PostDelete($this->id);
        }
        return $result;
    }

    public function unlinkExpenseEntryFromEvent(){
        global $_COMPANY, $_ZONE;
        $allExpenseEntries  = self::DBGet("SELECT * from `budgetuses` where eventid={$this->id}");
        foreach ($allExpenseEntries as $expenseEntry) {
            self::DBUpdate("UPDATE `budgetuses` SET `eventid`=0 WHERE `companyid`='{$_COMPANY->id()}' AND eventid={$this->id} AND `usesid`='{$expenseEntry['usesid']}'");
        }

        return true;
    }

    public function validateAndGetError(array $overrides): string
    {
        global $_COMPANY;

        $old = $this;

        $fields = $overrides + $this->fields;
        $new = new Self(0, $_COMPANY->id(), $fields);

        if ($old->isPublished()) {

            // For published events max participation can only by increased; it cannot be decreased.
            if ((int) $old->val('max_inperson') > $new->val('max_inperson'))
                //(int)$event->val('max_inperson_waitlist') > $max_inperson_waitlist
            {
                return gettext("Participant limit cannot be a number fewer than the previously published value. In order to continue, you need to make sure that the participant limit is equal to or more than the previous value. If you wish to have a smaller event, you will need to cancel this event, and request a new event to be added.");
            }

            // For published events max participation can only by increased; it cannot be decreased.
            if ((int) $old->val('max_online') > $new->val('max_online'))
                //(int)$event->val('max_online_waitlist') > $max_online_waitlist
            {
                return gettext("Participant limit cannot be a number fewer than the previously published value. In order to continue, you need to make sure that the participant limit is equal to or more than the previous value. If you wish to have a smaller event, you will need to cancel this event, and request a new event to be added.");
            }
        }

        return '';
    }

    public function updateOnlineSlots (int $max_online, int $max_online_waitlist) : int {
        if ($this->validateAndGetError([
            'max_online' => $max_online,
            'max_online_waitlist' => $max_online_waitlist,
        ])) {
            return -1;
        }

        global $_COMPANY;

        if ($this->isParticipationLimitUnlimited('max_online')) {
            $max_online = Event::MAX_PARTICIPATION_LIMIT;
        }

        if ($this->isParticipationLimitUnlimited('max_online_waitlist')) {
            $max_online_waitlist = Event::MAX_PARTICIPATION_LIMIT;
        }

        $retVal = self::DBUpdate("UPDATE events SET max_online={$max_online},max_online_waitlist={$max_online_waitlist},modifiedon=now() WHERE groupid={$this->val('groupid')} AND eventid={$this->id}");
        $_COMPANY->expireRedisCache("EVT:{$this->id}");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['max_online' => $max_online, 'max_online_waitlist'=>$max_online_waitlist]);
        }
        return $retVal;
    }

    public function updateInPersonSlots (int $max_inperson, int $max_inperson_waitlist): int {
        if ($this->validateAndGetError([
            'max_inperson' => $max_inperson,
            'max_inperson_waitlist' => $max_inperson_waitlist,
        ])) {
            return -1;
        }

        global $_COMPANY;

        if ($this->isParticipationLimitUnlimited('max_inperson')) {
            $max_inperson = Event::MAX_PARTICIPATION_LIMIT;
        }

        if ($this->isParticipationLimitUnlimited('max_inperson_waitlist')) {
            $max_inperson_waitlist = Event::MAX_PARTICIPATION_LIMIT;
        }

        $retVal = self::DBUpdate("UPDATE events SET max_inperson={$max_inperson},max_inperson_waitlist={$max_inperson_waitlist},modifiedon=now() WHERE groupid={$this->val('groupid')} AND eventid={$this->id}");
        $_COMPANY->expireRedisCache("EVT:{$this->id}");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['max_inperson' => $max_inperson, 'max_inperson_waitlist'=>$max_inperson_waitlist]);
        }
        return $retVal;
    }

    public function getMyEventSeriesRsvpStatus() {
        global $_USER; /* @var User $_USER */
		$retVal = null;
        $s = self::DBGet("SELECT eventjoiners.eventid, eventjoiners.joinstatus, events.eventtitle FROM eventjoiners LEFT JOIN events ON events.eventid= eventjoiners.eventid WHERE eventjoiners.joinstatus not in (0,3,15,25) AND events.event_series_id={$this->val('event_series_id')} AND eventjoiners.userid={$_USER->id()}");

		if (count($s)){
			$retVal = $s[0];
        }
		return $retVal;
	}

	public function loggedinUserCanUpdateEvent()
    {
        global $_COMPANY, $_USER;

        if ($_USER->isAdmin()) {
            return true;
        }

        // For team Events
        if ($this->val('teamid') && $_USER->isProgramTeamMember($this->val('teamid'))) {
            return true;
        }

        // For User created Events when my_events feature is enabled.
        // if ($_COMPANY->getAppCustomization()['event']['my_events']['enabled'] && $_USER->id() == $this->val('userid')) {
        //     return true;
        // }

        if (!empty($this->val('collaborating_groupids')) || (!empty($this->val('chapterid')) && $this->val('channelid') ==0)) {
            $cgs = explode(',', $this->val('collaborating_groupids'));
            foreach ($cgs as $cg) {
                if ($cg && ($_USER->canCreateContentInGroup($cg) || $_USER->canPublishContentInGroup($cg))) {
                    return true;
                }
            }
            $chpts = Group::GetChapterNamesByChapteridsCsv($this->val('chapterid'));
            foreach ($chpts as $ch) {
                if ($_USER->canCreateContentInGroupChapterV2($ch['groupid'],$ch['regionids'],$ch['chapterid']) || $_USER->canPublishContentInGroupChapterV2($ch['groupid'],$ch['regionids'],$ch['chapterid'])) {
                    return true;
                }
            }
        } else {
            return $_USER->canUpdateContentInScopeCSV($this->val('groupid'), $this->val('chapterid'), $this->val('channelid'), $this->val('isactive'));
        }
        return false;
    }

    public function loggedinUserCanPublishEvent()
    {
        global $_COMPANY, $_USER;

        if ($_USER->isAdmin()) {
            return true;
        }

        // For team Events
        if ($this->val('teamid') && $_USER->isProgramTeamMember($this->val('teamid'))) {
            return true;
        }

        // For User created Events when my_events feature is enabled.
        // if ($_COMPANY->getAppCustomization()['event']['my_events']['enabled'] && $_USER->id() == $this->val('userid')) {
        //     return true;
        // }

        if (!empty($this->val('collaborating_groupids')) || (!empty($this->val('chapterid')) && $this->val('channelid') ==0)) {
            $cgs = explode(',', $this->val('collaborating_groupids'));
            foreach ($cgs as $cg) {
                if ($cg && $_USER->canPublishContentInGroup($cg)) {
                    return true;
                }
            }

            $chpts = Group::GetChapterNamesByChapteridsCsv($this->val('chapterid'));
            foreach ($chpts as $ch) {
                if ($_USER->canPublishContentInGroupChapter($ch['groupid'],$ch['regionids'],$ch['chapterid'])) {
                    return true;
                }
            }
        } else {
            return $_USER->canPublishContentInScopeCSV($this->val('groupid'), $this->val('chapterid'), $this->val('channelid'));
        }
        return false;
    }
    public function loggedinUserCanManageEvent() {
        global $_COMPANY, $_USER;

        if ($_USER->isAdmin()) {
            return true;
        }

        // For team Events
        if ($this->val('teamid') && $_USER->isProgramTeamMember($this->val('teamid'))) {
            return true;
        }

        // For User created Events when my_events feature is enabled.
        // if ($_COMPANY->getAppCustomization()['event']['my_events']['enabled'] && $_USER->id() == $this->val('userid')) {
        //     return true;
        // }

        if (!empty($this->val('collaborating_groupids')) || (!empty($this->val('chapterid')) && $this->val('channelid') ==0)) {
            $cgs = explode(',', $this->val('collaborating_groupids'));
            foreach ($cgs as $cg) {
                if ($cg && $_USER->canManageGroup($cg)) {
                    return true;
                }
            }

            $chpts = Group::GetChapterNamesByChapteridsCsv($this->val('chapterid'));
            foreach ($chpts as $ch) {
                if ($_USER->canManageContentInGroupChapterV2($ch['groupid'],$ch['regionids'],$ch['chapterid'])) {
                    return true;
                }
            }
        } else {
            return $_USER->canManageContentInScopeCSV($this->val('groupid'), $this->val('chapterid'), $this->val('channelid'));
        }
        return false;
    }
    public function loggedinUserCanUpdateOrPublishOrManageEvent()
    {
        global $_COMPANY, $_USER;

        if ($_USER->isAdmin()) {
            return true;
        }

        // For team Events
        if ($this->val('teamid') && $_USER->isProgramTeamMember($this->val('teamid'))) {
            return true;
        }

        // For User created Events when my_events feature is enabled.
        // if ($_COMPANY->getAppCustomization()['event']['my_events']['enabled'] && $_USER->id() == $this->val('userid')) {
        //     return true;
        // }

        if (!empty($this->val('collaborating_groupids')) || (!empty($this->val('chapterid')) && $this->val('channelid') ==0)) {
            $cgs = explode(',', $this->val('collaborating_groupids'));
            foreach ($cgs as $cg) {
                if ($cg){
                    if ($_USER->canManageGroup($cg) || $_USER->canCreateContentInGroup($cg) || $_USER->canPublishContentInGroup($cg)) {
                        return true;
                    }
                }
            }
            $chpts = Group::GetChapterNamesByChapteridsCsv($this->val('chapterid'));
            foreach ($chpts as $ch) {
                if ($_USER->canCreateContentInGroupChapter($ch['groupid'],$ch['regionids'],$ch['chapterid']) || $_USER->canPublishContentInGroupChapter($ch['groupid'],$ch['regionids'],$ch['chapterid']) || $_USER->canManageContentInGroupChapterV2($ch['groupid'],$ch['regionids'],$ch['chapterid'])) {
                    return true;
                }
            }
        } else {
            return $_USER->canCreateOrPublishOrManageContentInScopeCSV($this->val('groupid'), $this->val('chapterid'), $this->val('channelid'));
        }
        return false;
    }

    /**
     * @Depricated Use FilterEvents2
     */
    public static function FilterEvents(string $groupIds, string $linkedGroupIds, string $linkedChapterIds, string $linkedChannelIds, array $eventType, bool  $showDrafts) {
//
//        global $_COMPANY,$_ZONE,$_USER;
//
//        $linkedGroupIds = Sanitizer::SanitizeIntegerCSV($linkedGroupIds);
//        $linkedChapterIds = Sanitizer::SanitizeIntegerCSV($linkedChapterIds);
//        $linkedChannelIds = Sanitizer::SanitizeIntegerCSV($linkedChannelIds);
//
//        $data = array();
//        if ($groupIds!=''){
//            $condition = "";
//            $eventTypeCondition = "";
//            if (!empty($eventType)){
//                $eventType = Sanitizer::SanitizeIntegerArray($eventType);
//                $event_type_list = implode(',', $eventType);
//                $eventTypeCondition = " AND eventtype IN({$event_type_list})";
//            }
//
//            $linkedGroupsCondition = '';
//            if (!empty($linkedGroupIds) && (!empty($linkedChapterIds) || !empty($linkedChannelIds))){
//                $linkedGroupsCondition = " OR (events.groupid IN ($linkedGroupIds))";
//            }
//
//            $condition = " AND ((events.groupid IN ({$groupIds})) {$linkedGroupsCondition})";
//
//            if ($showDrafts) {
//                $isactive_filter = " AND events.`isactive` != '".Teleskope::STATUS_INACTIVE."'";
//            } else { // Else show only active events
//                $isactive_filter = " AND events.`isactive` = '".Teleskope::STATUS_ACTIVE."'";
//            }
//
//            $data = self::DBROGet("SELECT events.*,chapters.regionids,chapters.latitude as chapter_latitude,chapters.longitude as chapter_longitude  FROM events LEFT JOIN chapters using (chapterid) WHERE events.companyid={$_COMPANY->id()} AND (`start` > now() - interval 6 month {$condition} {$eventTypeCondition} {$isactive_filter} AND (event_series_id !=eventid)) AND `events`.`teamid`='0' ");
//
//            if (!empty($linkedGroupsCondition)) {
//                $linkedGroupArray = explode(',', $linkedGroupIds);
//                $linkedChapterArray = explode(',', $linkedChapterIds);
//                $linkedChannelArray = explode(',', $linkedChannelIds);
//                foreach ($data as $key => $row) {
//                    if (in_array($row['groupid'], $linkedGroupArray)) {
//                        // Keep only the events that have an intersecting chapters or intersecting channels
//                        if (empty(array_intersect(explode(',',$row['chapterid']),$linkedChapterArray)) || empty(array_intersect(explode(',',$row['channelid']),$linkedChannelArray))) {
//                            unset($data[$key]);
//                        }
//                    }
//                }
//                $data = array_values($data); // Recreate array to remove holes
//            }
//        }
//        return $data;
        return [];
    }

    /**
     * Returns all the events matching the specified criteria. Note for the filter the null values are treated
     * differently as empty arrays. Empty array means no matching filter was provided hence the no row will be matched
     * Null array means the filter shouid be ignored.
     *
     * @param array|null $zoneIdArray if null chapter filter is ignored. If empty, that means filtering is done but no values will match ... just like how one would expect from drop down selection.
     * @param array|null $groupIdArray if null group filter is ignored. If empty, that means filtering is done but no values will match ... just like how one would expect from drop down selection.
     * @param array|null $chapterIdArray if null chapter filter is ignored. If empty, that means filtering is done but no values will match ... just like how one would expect from drop down selection.
     * @param array|null $channelIdArray if null channel filter is ignored. If empty, that means filtering is done but no values will match ... just like how one would expect from drop down selection.
     * @param array|null $eventTypeArray
     * @param bool $showDrafts
     * @return array
     */
    public static function FilterEvents2(?array $zoneIdArray, ?array $groupIdArray, ?array $chapterIdArray, ?array $channelIdArray, ?array $eventTypeArray, bool $showDrafts): array
    {
        $event_list = self::FilterEvents1($zoneIdArray, $groupIdArray, $chapterIdArray, $channelIdArray, $eventTypeArray, $showDrafts);

        if ($groupIdArray !== null) {
            // Get groups
            $groupIdArray = Sanitizer::SanitizeIntegerArray($groupIdArray);
            if (empty($groupIdArray))
                return [];
            $linkedValues = Group::GetAllLinkedGroupsChaptersChannels(implode(',', $groupIdArray));

            $linkedGroupIdArray = Sanitizer::SanitizeIntegerArray($linkedValues['linkedGroupIds']);

            $linkedChapterIdArray = Sanitizer::SanitizeIntegerArray($linkedValues['linkedChapterIds']);
            // Add back chapterid = 0 to linked Chapterids to pull Global Events in the linked Groups
            if (empty($linkedChapterIdArray)) {
                $linkedChapterIdArray = null;
            } else {
                $linkedChapterIds[] = 0;
            }

            $linkedChannelIdArray = Sanitizer::SanitizeIntegerArray($linkedValues['linkedChannelIds']);
            // Add back channelid = 0 to linked ChannelIds to pull Global Events in the linked Groups
            if (empty($linkedChannelIdArray)) {
                $linkedChannelIdArray = null;
            } else {
                $linkedChannelIdArray[] = 0;
            }

            $linkedGroupZoneIdArray = Sanitizer::SanitizeIntegerArray($linkedValues['linkedGroupZoneIds']);

            //$linkedEventIdArray = null; // For linked events set event id array to null to fetch events of all types.

            //Before #3747, we were sending $linkedEventIdArray as null with the aforementioned exception.
            //Now we are calculating linked groups events using the defined $eventTypeArray value.

            //Logger::LogDebug('Linked Values', ['Group Ids' => $linkedGroupIdArray ? array_values($linkedGroupIdArray) : $linkedGroupIdArray, 'Chapter Ids'=> $linkedChapterIdArray ? array_values($linkedChapterIdArray) : $linkedChapterIdArray, 'Channel Ids'=> $linkedChannelIdArray ? array_values($linkedChannelIdArray) : $linkedChannelIdArray,'Zone Ids'=> $linkedGroupZoneIdArray ? array_values($linkedGroupZoneIdArray) : $linkedGroupZoneIdArray]);

            $event_list_linked = self::FilterEvents1($linkedGroupZoneIdArray, $linkedGroupIdArray, $linkedChapterIdArray, $linkedChannelIdArray, $eventTypeArray, $showDrafts);

            $event_list = array_merge($event_list, $event_list_linked);
        }

        return $event_list;
    }

    private static function FilterEvents1 (?array $zoneIdArray, ?array $groupIdArray, ?array $chapterIdArray, ?array $channelIdArray, ?array $eventTypeArray, bool $showDrafts): array
    {

        global $_COMPANY;

        $condition = '';

        if ($groupIdArray !== null) {
            // Get groups
            $groupIdArray = Sanitizer::SanitizeIntegerArray($groupIdArray);
            if (empty($groupIdArray))
                return [];
        }

        if ($chapterIdArray !== null) {
            $chapterIdArray = Sanitizer::SanitizeIntegerArray($chapterIdArray);
           // if (empty($chapterIdArray))
               // return [];
        }

        if ($channelIdArray !== null) {
            $channelIdArray = Sanitizer::SanitizeIntegerArray($channelIdArray);
            if (empty($channelIdArray))
                return [];
        }

        if ($eventTypeArray !== null) {
            $eventTypeArray = Sanitizer::SanitizeIntegerArray($eventTypeArray);
            if (empty($eventTypeArray))
                return [];

            $event_type_list = implode(',', $eventTypeArray) ?: '0';
            $condition = " AND eventtype IN({$event_type_list})";
        }

        if ($showDrafts) {
            $condition .= " AND (events.`isactive` != '".Teleskope::STATUS_INACTIVE."')";
        } else { // Else show only active events
            $condition .= " AND (events.`isactive` = '".Teleskope::STATUS_ACTIVE."')";
        }
        $cleanZoneIdArray = array();
        if ($zoneIdArray !== null) {
            $cleanZoneIdArray = Sanitizer::SanitizeIntegerArray($zoneIdArray);
            if (empty($cleanZoneIdArray))
                return [];

            $zoneIdList = implode(',', $cleanZoneIdArray) ?: '0';

            if ($groupIdArray == null) {
                // If groupids are not given given then we will only fetch events in specified zone
                $condition .= " AND (events.`zoneid` IN ({$zoneIdList}))";
            } else {
                // Else we will find the events that are in the specified zones or if the events have groupid =0
                // To include events that may be multi-zone collaboration
                $condition .= " AND (events.`zoneid` IN ({$zoneIdList}) OR events.groupid=0 )";
            }
        }

        $data = self::DBROGet("SELECT events.*,event_type.type AS event_type  FROM events LEFT JOIN event_type ON event_type.typeid=events.eventtype WHERE events.companyid={$_COMPANY->id()} AND (`start` > now() - interval 6 month {$condition} AND (event_series_id !=eventid)) AND `events`.`teamid`='0' AND `events`.listids=0 AND eventclass != 'booking' ");

        foreach ($data as $key => $row) {
            // If group array is provided, ensure a group id is matching.
            if ($groupIdArray !== null) {
                if ($row['groupid']) { // For non collaborating event
                    if (!in_array($row['groupid'], $groupIdArray)) {
                        unset($data[$key]);
                        continue;
                    }
                } elseif (empty($row['collaborating_groupids'])) { // Global Event,  Dynamic list and holiday case

                    if (!empty($row['invited_groups'])){
                        if (!in_array(0, $groupIdArray) && empty(array_intersect(explode(',', $row['invited_groups']), $groupIdArray))) {
                            unset($data[$key]);
                            continue;
                        }
                    }

                    //TODO Dynamic list handling! Discussion Needed

                    if (!in_array($row['zoneid'], $cleanZoneIdArray)) { // Only show zone-specific events. [global event, holiday handled]
                        unset($data[$key]);
                        continue;
                    }

                } else { // For collaborating events
                    if (!empty($row['collaborating_groupids']) && empty(array_intersect(explode(',', $row['collaborating_groupids']), $groupIdArray))) {
                        unset($data[$key]);
                        continue;
                    }
                }
            }

            // If $chapterIdArray is set to null, then it means ignore chapterId check. If not null then we make the checks
            if ($chapterIdArray !== null) {
                // If $chapterIdArray is empty array, then remove all events as we are expecting array(0) for including global events
                // If $chapterIdArray is not empty array, then remove all events as which do not have interecting chapters
                if (empty($chapterIdArray) || empty(array_intersect(explode(',', $row['chapterid']), $chapterIdArray))) {
                    unset($data[$key]);
                    continue;
                }

            }

            // If channelids  are given, then do a channel id match check.
            if ($channelIdArray !== null && empty(array_intersect(explode(',',$row['channelid']), $channelIdArray))) {
                unset($data[$key]);
                continue;
            }
        }

        //Logger::LogDebug('FilterEvents1', ['count' => count($data), 'zoneIdArray' => $zoneIdArray ? array_values($zoneIdArray) : $zoneIdArray, 'groupIdArray' => $groupIdArray ? array_values($groupIdArray) : $groupIdArray, 'chapterIdArray'=> $chapterIdArray ? array_values($chapterIdArray) : $chapterIdArray,'channelIdArray'=> $channelIdArray ? array_values($channelIdArray) : $channelIdArray,'eventTypeArray'=> $eventTypeArray ? array_values($eventTypeArray) : $eventTypeArray,'showDrafts'=>$showDrafts]);

        // Recreate array to remove holes
        return array_values($data);
    }

    public static function GetAllEventsInGroupByMonth(int $groupid, string $month) {
		$objs = array();
        global $_COMPANY; /* @var Company $_COMPANY */
		global $_USER;  /* @var User $_USER */

		$active = "AND events.`isactive` ='".self::STATUS_ACTIVE."'";

		$condition = "AND (events.`groupid`='{$groupid}' OR find_in_set(".$groupid.",events.collaborating_groupids))";

        $monthCondition = "";
        if ($month){
            $month = self::raw2clean($month);
            $monthCondition = " AND events.`start` LIKE '%".$month."%' ";
        }
        $rows = self::DBGet("SELECT `events`.*,chapters.chaptername,group_channels.channelname FROM `events` LEFT JOIN chapters on chapters.chapterid=events.chapterid LEFT JOIN group_channels ON group_channels.channelid=events.channelid  WHERE events.`companyid` = '{$_COMPANY->id()}' ".$condition." ".$active." ".$monthCondition." ");
        usort($rows, function($a, $b) {
            return $a['eventid'] <=> $b['eventid'];
        });
		// Extract an array of eventseries titles
		$event_series_titles = array();
		foreach ($rows as $row) {
		    if ($row['event_series_id'] == $row['eventid'])
                $event_series_titles['eventid'] = $row['eventtitle'];
        }

		foreach ($rows as $row) {
			if($row['eventid']==$row['event_series_id'])
                continue; // Skip the event series wrappers
            if($row['event_series_id'] && ($row['eventid']!=$row['event_series_id'])){
                $row['event_series_name'] = $event_series_titles['eventid'];
            }
            $objs[] = Event::ConvertDBRecToEvent($row);
        }
		return $objs;
	}

    /**
     * Returns an array of RSVP records for the event.
     * @param string|null $rsvpTypeCsv optional, if set only the rsvp types set in the list will be returned.
     * @param int|null $start optional if set records from the start number will be returned.
     * @param int|null $limit optional
     * @return array
     */
    public function getEventRSVPsList(?string $rsvpTypeCsv = null, ?int $start = null, ?int $limit = null) : array
    {
        global $_COMPANY,$_ZONE,$_USER;
        $filterByRsvpType = "";
        if ($rsvpTypeCsv != null){
            $rsvpTypeCsv = Sanitizer::SanitizeIntegerCSV($rsvpTypeCsv);
            $filterByRsvpType = " AND eventjoiners.joinstatus IN({$rsvpTypeCsv})";
        }

        $limitCondition = "";
        if ($limit){
            $startLimit = $start ?: '';
            $limitCondition = " LIMIT {$startLimit} {$limit}";
        }

        $data =  self::DBGet("SELECT eventjoiners.*, IFNULL(users.firstname,'Deleted') as firstname, IFNULL(users.lastname,'User') as lastname, users.email, users.jobtitle, companybranches.branchname, departments.department,users.picture FROM eventjoiners LEFT JOIN users ON eventjoiners.userid=users.userid LEFT JOIN companybranches ON users.homeoffice=companybranches.branchid LEFT JOIN departments ON users.department = departments.departmentid WHERE eventjoiners.eventid='{$this->id()}' {$filterByRsvpType} {$limitCondition}" );

        foreach($data as &$row){
            if (!$row['userid']) {
                $otherData = json_decode($row['other_data'],true);
                if(!empty($otherData) && array_key_exists('firstname',$otherData)){
                    $row['firstname'] = $otherData['firstname'];
                    $row['lastname'] = $otherData['lastname'];
                    $row['jobtitle'] = '-';
                }
            }
        }
        unset($row);
        return $data;

    }

    public function getEventRSVPsList__memoized()
    {
        $memoize_key = __METHOD__ . ':'  . $this->id() . ':' . serialize(func_get_args());

        if (!isset(self::$memoize_cache[$memoize_key]))
            self::$memoize_cache[$memoize_key] = $this->getEventRSVPsList();

        return self::$memoize_cache[$memoize_key];
    }

    public function updateEventFollowUpNote(string $followup_notes) {

        global $_COMPANY;
        global $_ZONE;
        $retVal = self::DBUpdatePS("UPDATE `events` SET `followup_notes`=? WHERE companyid=? AND eventid=? ",'mii',$followup_notes,$_COMPANY->id(),$this->id());
        if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'event', $this->id(), $this->val('version'), ['update' => 'followup_notes']);
        }
        return $retVal;
    }

    public function addEventSpeaker(int $speech_length,string $speaker_name,string $speaker_title,int $speaker_fee,string $speaker_picture,string $speaker_bio,string $other,int $expected_attendees, string $custom_fields)
    {
        global $_USER;
        $retVal = self::DBInsertPS("INSERT INTO `event_speakers`(`companyid`, `zoneid`, `eventid`,`speech_length`, `speaker_name`, `speaker_title`, `speaker_picture`, `speaker_bio`, `speaker_fee`, `other`,`createdby`, `createdon`, `approval_status`, `expected_attendees`, `custom_fields`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)",'iiiixxxxixiiix',$this->cid, $this->val('zoneid'), $this->id,$speech_length,$speaker_name,$speaker_title,$speaker_picture,$speaker_bio,$speaker_fee,$other,$_USER->id(),0,$expected_attendees, $custom_fields);
        if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'event', $this->id(), $this->val('version'), ['speaker_added' => $retVal]);
        }
        return $retVal;
    }

    public function updateEventSpeaker(int $speakerid,int $speech_length,string $speaker_name,string $speaker_title,int $speaker_fee,string $speaker_picture,string $speaker_bio,string $other, int $expected_attendees, string $custom_fields)
    {
        global $_USER,$_COMPANY,$_ZONE;
        $retVal = self::DBUpdatePS("UPDATE `event_speakers` SET `speech_length`=?,`speaker_name`=?,`speaker_title`=?,`speaker_picture`=?,`speaker_bio`=?,`speaker_fee`=?,`other`=?,createdon=NOW(),approval_status=0,expected_attendees=?, `custom_fields` = ? WHERE `companyid`=? AND `zoneid`=? AND `speakerid`=? AND `eventid`=?",'ixxxxixixiiii',$speech_length,$speaker_name,$speaker_title,$speaker_picture,$speaker_bio,$speaker_fee,$other,$expected_attendees, $custom_fields, $_COMPANY->id(),$_ZONE->id(),$speakerid,$this->id());
        if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'event', $this->id(), $this->val('version'), ['speaker_updated' => $speakerid]);
        }
        return $retVal;
    }

    public function getEventSpeakers(int $includeDraft = 1) {
        global $_COMPANY, $_ZONE;
        $condition = "";
        if(!$includeDraft){
                $condition = " AND event_speakers.approval_status!=0";
        }
        $speakers =  self::DBGet("SELECT event_speakers.* FROM event_speakers WHERE event_speakers.eventid={$this->id()} AND event_speakers.companyid={$_COMPANY->id()} AND event_speakers.zoneid={$_ZONE->id()} {$condition} ");
        usort($speakers,function($a,$b) {
            return strcmp($a['speaker_name'], $b['speaker_name']);
        });
        return $speakers;
    }

    public function getApprovedEventSpeakers() {

        global $_COMPANY, $_ZONE;
        $speakers =  self::DBGet("select speakerid, speaker_name,speaker_title from event_speakers where companyid={$_COMPANY->id()} and zoneid={$_ZONE->id()} and approval_status = 3");

        $total_required_speakers = array();
        $speaker_name_title_array = array();
        # It is sorted by event_type in descending order and the title is sorted in ascending order.
        array_multisort($speakers, SORT_DESC);
        foreach ($speakers as $speaker) {
                $speaker_name_title = $speaker['speaker_name'].'_'.$speaker['speaker_title'];
                 if (!in_array($speaker_name_title, $speaker_name_title_array)) {
                     $total_required_speakers[] = array("speakerid" => $speaker['speakerid'], "speaker_name" => $speaker['speaker_name'], "speaker_title" => $speaker['speaker_title']);
                     $speaker_name_title_array[] = $speaker_name_title;
                }
        }
        return $total_required_speakers;
    }

    public function areEventSpeakersApproved():bool {
        global $_COMPANY;
        if(!$_COMPANY->getAppCustomization()['event']['speakers']['approvals']){
            return true;
        }
        $speakers = $this->getEventSpeakers();
        foreach ($speakers as $speaker) {
            if ($speaker['approval_status'] != 3) {
                return false;
            }
        }
        return true;
    }

    public function getEventSpeakerDetail(int $speakerid) {
        global $_COMPANY, $_ZONE;
        $row = null;
        $r = self::DBGet("SELECT event_speakers.* FROM event_speakers WHERE event_speakers.companyid={$_COMPANY->id()} AND event_speakers.zoneid={$_ZONE->id()} AND event_speakers.speakerid={$speakerid} ");
        if (count($r)){
            $row = $r[0];
        }
        return $row;
    }

    public function deleteEventSpeaker(int $speakerid) {
        global $_COMPANY, $_ZONE;
        $retVal = self::DBUpdate("DELETE FROM `event_speakers` WHERE event_speakers.companyid={$_COMPANY->id()} AND event_speakers.zoneid={$_ZONE->id()} AND `speakerid`={$speakerid} AND `eventid`={$this->id}");
        if ($retVal) {
            self::LogObjectLifecycleAudit('delete', 'event_sepakers', $this->id(), $this->val('version'), ['speaker_deleted' => $speakerid]);
        }
        return $retVal;
    }

    public function updateEventSpeakerStatus(int $speakerid,int $approval_status, string $approver_note = '',int $approved_by=NULL){
        global $_USER;
        global $_COMPANY, $_ZONE;
        return self::DBUpdatePS("UPDATE `event_speakers` SET `approval_status`=?,`approver_note`=?,`approved_by`=?,`approved_on`=NOW() WHERE `companyid`=? AND `zoneid`=? AND `speakerid`=? AND `eventid`=?",'ixiiiii',$approval_status,$approver_note,$approved_by,$_COMPANY->id(),$_ZONE->id(),$speakerid,$this->id);
    }

    public function approveAllPendingSpeakerApprovals(string $approver_note)
    {
        global $_USER;
        global $_COMPANY, $_ZONE;
        // Approve only the speakers who are in requested approval or pending approval status.
        return self::DBUpdatePS("UPDATE `event_speakers` SET `approval_status`=3,`approver_note`=?,`approved_by`=?,`approved_on`=NOW() WHERE `companyid`=? AND `zoneid`=? AND `eventid`=? AND approval_status IN (1,2)",'xiiii', $approver_note, $_USER->id(), $_COMPANY->id(), $_ZONE->id(), $this->id);
    }

    public function getEventBudgetedDetail(bool $force_load = true, int $groupid = 0, int $chapterid=0) {
        if (!$force_load && $this->expense_entry) {
            return $this->expense_entry;
        }
        $groupCondition = '';
        $chapterCondition = '';
        if ($groupid) {
            $groupCondition = " AND groupid = {$groupid}";
        }
        if ($chapterid) {
            $chapterCondition = " AND chapterid ={$chapterid}";
        }
        $budget_rec  = self::DBGet("SELECT * from budgetuses where eventid={$this->id} {$groupCondition} {$chapterCondition }");
        if (count($budget_rec)) {
            $this->expense_entry = $budget_rec[0];
        }
        return $this->expense_entry;
    }

    public function isEventBudgetApproved() {
        $budget = $this->getEventBudgetedDetail();
        if (!empty($budget)) {
            return $budget['budget_approval_status'] == 2;
        }
        return true;
    }

    public static function GenerateEventRSVPCounters(int $eventid, int $is_series) {
        $rsvp_counters = array('0'=>0, '1'=>0, '2'=>0, '3'=>0, '11'=>0, '12'=>0, '21'=>0, '22'=>0);
        if ($is_series) {
            $rows = self::DBROGet("SELECT joinstatus, count(1) AS CC FROM eventjoiners WHERE eventid IN (select eventid from events where event_series_id={$eventid}) GROUP BY JOINSTATUS");
        } else {
            $rows = self::DBROGet("SELECT joinstatus, count(1) AS CC FROM eventjoiners WHERE eventid={$eventid} GROUP BY JOINSTATUS");
        }
        foreach ($rows as $row) {
            $rsvp_counters[(string)$row['joinstatus']] = (int)$row['CC'];
        }
        $set_counters = "num_rsvp_0={$rsvp_counters['0']},num_rsvp_1={$rsvp_counters['1']},num_rsvp_2={$rsvp_counters['2']},num_rsvp_3={$rsvp_counters['3']},num_rsvp_11={$rsvp_counters['11']},num_rsvp_12={$rsvp_counters['12']},num_rsvp_21={$rsvp_counters['21']},num_rsvp_22={$rsvp_counters['22']}";
        self::DBMutate("INSERT INTO event_counters SET eventid={$eventid}, {$set_counters} ON DUPLICATE KEY UPDATE {$set_counters}");
    }

    public static function GenerateEventCheckinCounters(int $eventid, int $is_series) {
        if ($is_series) {
            $num_checkedin = self::DBROGet("SELECT count(1) AS num_checkedin FROM eventjoiners WHERE eventid IN (select eventid from events where event_series_id={$eventid}) AND checkedin_date is not null")[0]['num_checkedin'];
        } else {
            $num_checkedin = self::DBROGet("SELECT count(1) AS num_checkedin FROM eventjoiners WHERE eventid={$eventid} AND checkedin_date is not null")[0]['num_checkedin'];
        }
        $set_counters = "num_checkedin={$num_checkedin}";
        self::DBMutate("INSERT INTO event_counters SET eventid={$eventid}, {$set_counters} ON DUPLICATE KEY UPDATE {$set_counters}");
    }

    public function searchUsersForEventRSVP( string $searchKeyWord, int $limit = 10){
        global $_COMPANY;
        global $_ZONE;
        $rows = array();
        $searchKeyWord = trim ($searchKeyWord);
        if (strlen($searchKeyWord) > 2){
            $keyword_list = explode(' ',$searchKeyWord);

            if(count($keyword_list) == 2){
                $like_keyword1 = $keyword_list[0]. '%'; //First keyword is full match
                $like_keyword2 = '%' . $keyword_list[1] . '%';
                $rows = self::DBGetPS("SELECT `userid`, `firstname`, `lastname`, `email` FROM `users` WHERE `companyid`=? AND (`isactive`='1' AND `verificationstatus`='1' AND ((firstname LIKE ? OR lastname LIKE ?) AND (firstname LIKE ? OR lastname LIKE ?))) AND userid NOT IN (SELECT `userid` FROM `eventjoiners` WHERE `eventid`='{$this->id}' ) LIMIT ?", 'issssi', $_COMPANY->id(), $like_keyword1, $like_keyword1, $like_keyword2, $like_keyword2, $limit);
            } else if (count($keyword_list) > 0) { // For all other cases
                $like_keyword = '%' . $keyword_list[0] . '%';
                $rows = self::DBGetPS("SELECT `userid`, `firstname`, `lastname`, `email` FROM `users` WHERE `companyid`=? AND (`isactive`='1' AND `verificationstatus`='1' AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ?)) AND userid NOT IN (SELECT `userid` FROM `eventjoiners` WHERE `eventid`='{$this->id}') LIMIT ?", 'isssi', $_COMPANY->id(), $like_keyword, $like_keyword, $like_keyword, $limit);
            }

            usort($rows, function($a, $b) {
                return $a['firstname'] <=> $b['firstname'];
            });
        }

        return $rows;

    }

    public static function GetEventSpeakerFieldDetail(int $field_id) {
        global $_COMPANY, $_ZONE;
        $data = null;
        $d =  self::DBGet("SELECT * FROM `event_speaker_fields` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `speaker_fieldid`='{$field_id}' AND companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()}");
        if (count($d)){
            $data = $d[0];
        }
        return $data;
    }

    public static function GetEventSpeakerFieldsList($activeOnly = false) {
        global $_COMPANY, $_ZONE;
        $activeCondtion = " AND isactive!=0";
        if($activeOnly){
            $activeCondtion = " AND isactive=1";
        }

        return self::DBGet("SELECT * FROM `event_speaker_fields` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' $activeCondtion ");
    }

    public function pinUnpinEvent (int $status) : int {
        global $_COMPANY;
        $retVal = self::DBUpdate("UPDATE events SET pin_to_top={$status},modifiedon=now() WHERE companyid={$_COMPANY->id()} AND eventid={$this->id}");
        $_COMPANY->expireRedisCache("EVT:{$this->id}");

        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['pin_to_top' => $status]);
        }
        return $retVal;
    }

    public function updateCollaboratingGroupids (array $collaborating_groupids_arr, array $collaborating_groupids_pending_arr, array $collaborating_chapterids_arr, array $collaborating_chapterids_pending_arr=array()) : int
    {
        global $_COMPANY;

        if ($_COMPANY->getAppCustomization()['event']['collaborations']['auto_approve']) {
            // Approve all pending groups and chapters.
            $collaborating_groupids_arr = array_merge($collaborating_groupids_arr, $collaborating_groupids_pending_arr);
            $collaborating_chapterids_arr = array_merge($collaborating_chapterids_arr, $collaborating_chapterids_pending_arr);
            $collaborating_groupids_pending_arr = array();
            $collaborating_chapterids_pending_arr = array();
        }
        
        if (empty($collaborating_groupids_arr) && empty($collaborating_groupids_pending_arr)) {
            return 0; // No point processing if $collaborating_groupids_arr is empty
        }

        $event_groupid  = $this->val('groupid');

        // Check if the event is still owned by a group other than 0 and we are trying to set the $collaborating_groupids_arr
        // If so means the event has not been promoted to admin group. Lets add event to the $collaborating_groupids_arr
        if ($event_groupid && !in_array($event_groupid, $collaborating_groupids_arr)) {
            $collaborating_groupids_arr[] = $event_groupid;
        }

        // If there is intersection between $collaborating_groupids_pending_arr and $collaborating_groupids_arr
        // Then remove them from $collaborating_groupids_pending_arr list
        if (!empty($collaborating_groupids_pending_arr)){
            foreach ($collaborating_groupids_arr as $cg) {
                $collaborating_groupids_pending_arr = Arr::RemoveByValue($collaborating_groupids_pending_arr, $cg);
            }
        }
        // If there is intersection between $collaborating_chapterids_pending_arr and $collaborating_chapterids_arr
        // Then remove them from $collaborating_chapterids_pending_arr list
        foreach ($collaborating_chapterids_arr as $ch) {
            $collaborating_chapterids_pending_arr = Arr::RemoveByValue($collaborating_chapterids_pending_arr, $ch);
        }

        // Sort and filter for unique values only
        $collaborating_groupids_pending = implode(',', array_filter(array_unique($collaborating_groupids_pending_arr)));
        $collaborating_groupids = implode(',', array_filter(array_unique($collaborating_groupids_arr)));
        $collaborating_chapterids = implode(',', array_filter(array_unique($collaborating_chapterids_arr)))?:'0';
        $collaborating_chapterids_pending = implode(',', array_filter(array_unique($collaborating_chapterids_pending_arr)));

        // Update invited groups
        $invited_groups = Str::ConvertCSVToArray($this->val('invited_groups'));
        // In case of invited groups order is important as we use it to calculate audience delta
        foreach ($collaborating_groupids_arr as $cg_id) {
            if (!in_array($cg_id, $invited_groups)) {
                $invited_groups[] = $cg_id; /* Maintain Order */
            }
        }
        $invited_groupids = implode(',', array_filter(array_unique($invited_groups)));



        $new_groupid = 0;
        $retVal = self::DBUpdate("
            UPDATE events
            SET `collaborating_groupids_pending`='{$collaborating_groupids_pending}',
                `collaborating_groupids`='{$collaborating_groupids}',
                `invited_groups`='{$invited_groupids}',
                `chapterid`='{$collaborating_chapterids}',
                `groupid`={$new_groupid},
                `collaborating_chapterids_pending`='{$collaborating_chapterids_pending}'
            WHERE companyid={$_COMPANY->id()}
              AND eventid={$this->id}
              ");

        if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'event', $this->id(), $this->val('version'), [
                'collaborating_groupids_pending' => $collaborating_groupids_pending,
                'collaborating_groupids' => $collaborating_groupids,
                'invited_groups' => $collaborating_groupids,
                'groupid' => $new_groupid,
                'collaborating_chapterids' => $collaborating_chapterids,
                'collaborating_chapterids_pending' => $collaborating_chapterids_pending
            ]);
            $_COMPANY->expireRedisCache("EVT:{$this->id}");
        }
        return 1;
    }

    public function updateAutomanageWaitlist (int $value, int $section) {
        global $_COMPANY;

        $updateField = "`automanage_inperson_waitlist` = {$value}";
        if ($section == 2){
            $updateField = "`automanage_online_waitlist` = {$value}";
        }

        $retVal = self::DBUpdate("UPDATE events SET $updateField,modifiedon=now() WHERE companyid={$_COMPANY->id()} AND eventid={$this->id}");
        $_COMPANY->expireRedisCache("EVT:{$this->id}");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['automanage_waitlist' => $value, 'section' => $section]);
        }
        return $retVal;
    }

    public static function GetEventVolunteerTypesForCurrentZone(bool $ignoreStatus=false) {
        global $_COMPANY, $_ZONE;
        $isActiveFilter = '';
        if (!$ignoreStatus) {
            $isActiveFilter = ' AND isactive=1 ';
        }
        return self::DBROGet("SELECT * FROM `event_volunteer_type` WHERE `companyid`={$_COMPANY->id()} AND (`zoneid`={$_ZONE->id()} $isActiveFilter)");
    }

    public static function GetEventVolunteerType(int $volunteertypeid) {
        global $_COMPANY, $_ZONE;
        $row = null;
        $r =  self::DBGet("SELECT * FROM `event_volunteer_type` WHERE `companyid`={$_COMPANY->id()} AND (`volunteertypeid`='{$volunteertypeid}')");
        if (!empty($r)){
            $row = $r[0];
        }
        return $row;
    }

    public static function AddOrUpdateEventVolunteerType(int $volunteertypeid, string $type) {
        global $_COMPANY, $_ZONE;

        $existing_row = self::DBGetPS("SELECT * FROM `event_volunteer_type` WHERE companyid=?  AND `zoneid`= ? AND `type`= ?",'iix',$_COMPANY->id,$_ZONE->id,$type);

        if($volunteertypeid == 0 && !empty($existing_row) && $existing_row[0]['isactive'] == 0){ // Reactivate if record fund
            return self::DBMutate("UPDATE `event_volunteer_type` SET `isactive`=1 WHERE volunteertypeid ='{$existing_row[0]['volunteertypeid']}'");
        }

        if ($volunteertypeid){
            // 1. Check if the provided volunteer type is already in use in the zone by another volunteer id
            // 2. Update type only if it is not used.
            $isTypeUsed = self::DBGetPS("SELECT count(1) AS cc FROM event_volunteer_type WHERE `companyid`=? AND `zoneid`=? AND `volunteertypeid`!=? AND `type`=?", "iiix", $_COMPANY->id(), $_ZONE->id(), $volunteertypeid, $type)[0];

            if ($isTypeUsed['cc']==0) {
                return self::DBMutatePS("UPDATE `event_volunteer_type` SET `type`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `zoneid`=? AND `volunteertypeid`=?", "xiii", $type, $_COMPANY->id(), $_ZONE->id(), $volunteertypeid);
            }
            return 0;
        } else {
            return self::DBInsertPS("INSERT INTO `event_volunteer_type`( `companyid`, `zoneid`, `type`, `modifiedon`, `isactive`) VALUES (?,?,?,NOW(),1) ON DUPLICATE KEY UPDATE `type`=?,`zoneid`=?, modifiedon=now()","iixxi",$_COMPANY->id(),$_ZONE->id(),$type,$type,$_ZONE->id());
        }

    }

    public static function DeleteOrUndoDeleteEventVolunteerType(int $volunteertypeid, string $action) {
        global $_COMPANY, $_ZONE;
        $check = self::GetEventVolunteerType($volunteertypeid);
        if ($check){
            $isactive = 1;
            if ($action == 'do'){
                $isactive = 0;
            }
            return self::DBUpdate("UPDATE `event_volunteer_type` SET `isactive`='{$isactive}',`modifiedon`=NOW() WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `volunteertypeid`='{$volunteertypeid}'");
        }
        return false;
    }

    public function getEventVolunteers() {
        global $_COMPANY, $_ZONE;
        return self::DBGet("SELECT event_volunteers.*,event_volunteer_type.type,users.firstname,users.lastname,users.email,users.picture,users.jobtitle FROM `event_volunteers` JOIN event_volunteer_type USING(volunteertypeid) LEFT JOIN users USING(userid) WHERE `eventid`='{$this->id()}' AND `event_volunteers`.`isactive` = 1");
    }

    /**
     * @param int $personid
     * @param int $volunteertypeid
     * @return int 1 if added, 2 if updated, 3 already assigned, 0 on error, -1 not allowed to add due to capacity,
     */
    public function addOrUpdateEventVolunteer(int $personid, int $volunteertypeid): int
    {
        global $_COMPANY, $_ZONE, $_USER;
        $retVal = 0;

        $volunteersCount = $this->getVolunteerCountByType($volunteertypeid);
        $eventVolunteerRequest = $this->getEventVolunteerRequestByVolunteerTypeId($volunteertypeid);

        if (!$eventVolunteerRequest) {
            return 0;
        }

        if ($this->isEventVolunteerSignup($personid, $volunteertypeid)) {
            return 3;
        }

        if ($eventVolunteerRequest['volunteer_needed_count'] <= $volunteersCount){
            return -1; // Not allow to add
        } else {
            $j = self::DBGet("SELECT volunteerid,volunteertypeid FROM event_volunteers WHERE eventid='{$this->id()}' AND userid='{$personid}'");

            if (empty($j)) {
                $approval_status = 2; // Auto approve
                $inserted = self::DBInsert("INSERT INTO `event_volunteers` (`eventid`, `volunteertypeid`, `userid`, `createdby`, `approval_status`) VALUES ({$this->id},{$volunteertypeid},{$personid},{$_USER->id()}, {$approval_status})");
                if ($inserted) {
                    $volunteerid = $inserted;
                    $this->sendEventVolunteerAssignmentEmail($personid, $volunteertypeid, 'create');
                    $retVal = 1;
                }
            } else {
                $volunteerid = $j[0]['volunteerid'];
                $previousVolunteerTypeId =  $j[0]['volunteertypeid'];
                $approval_status = 2; // Auto approve
                $updated = self::DBUpdate("UPDATE `event_volunteers` SET `volunteertypeid`={$volunteertypeid},`approval_status`={$approval_status} WHERE `volunteerid`={$volunteerid}");
                if ($updated) {
                    $this->sendEventVolunteerAssignmentEmail($personid, $volunteertypeid, 'update', $previousVolunteerTypeId);
                    $retVal = 2;
                }
            }
        }

        if (in_array($retVal, [1, 2])) {
            Points::HandleTrigger('VOLUNTEERING_SIGN_UP', [
                'volunteerId' => (int) $volunteerid,
            ]);
        }

        return $retVal;
    }

    public static function GetEventVolunteer(int $volunteerid)
    {
        return self::DBROGetPS('SELECT * FROM `event_volunteers` WHERE `volunteerid` = ?', 'i', $volunteerid);
    }

    public function removeEventVolunteer(int $personid){
        global $_COMPANY, $_ZONE;
        $existing_row = self::DBGet("SELECT * FROM event_volunteers WHERE `eventid`='{$this->id()}' AND `userid`='{$personid}'");
        if (!empty($existing_row)) {
            self::DBUpdate("DELETE FROM `event_volunteers` WHERE `eventid`='{$this->id()}' AND `userid`='{$personid}'");
            // Send Email Notification
            $this->sendEventVolunteerAssignmentEmail($personid, $existing_row[0]['volunteertypeid'], 'delete');

            Points::HandleTrigger('DELETE_EVENT_VOLUNTEER', [
                'volunteerId' => (int) $existing_row[0]['volunteerid'],
                'userId' => $personid,
                'eventId' => $this->id(),
            ]);
        }
    }

    public function getEventVolunteerRequests()
    {
        $key = self::EVENT_ATTRIBUTES['event_volunteer_requests'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?: array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return array();
    }

    /**
     * Get Volunteer Request by Volunteer Type Id
     * @param int $volunteertypeid
     * @return mixed|null
     */
    public function getEventVolunteerRequestByVolunteerTypeId (int $volunteertypeid)
    {
        $eventVolunteerRequests = $this->getEventVolunteerRequests();
        foreach ($eventVolunteerRequests as $volunteerRequest) {
            if ($volunteerRequest['volunteertypeid'] == $volunteertypeid) {
                return $volunteerRequest;
            }
        }
        return null;
    }


    public function addUpdateEventVolunteerRequests(string $volunteerType, int $volunteerCount, float $volunteerHours, string $ccEmail, $volunteerDescription, int $hide_from_signup_page = 0, bool $allow_external_volunteers = false): int
    {
        global $_COMPANY, $_ZONE;

        $id  = $this->getOrCreateVolunteerIdByVolunteerType($volunteerType);
        $volunteerHours = ceil($volunteerHours * 100)/100; // Convert it into two decimal points.

        $requestAttributes = array(
            'volunteertypeid' => $id,
            'volunteer_needed_count' => $volunteerCount,
            'cc_email' => $ccEmail,
            'volunteer_description' => $volunteerDescription,
            'volunteer_hours' => $volunteerHours,
            'hide_from_signup_page' => $hide_from_signup_page,
            'allow_external_volunteers' => $allow_external_volunteers,
        );

        // Get event attributes and update volunteer request attributes
        $key = self::EVENT_ATTRIBUTES['event_volunteer_requests'];
        $event_attributes = json_decode($this->val('attributes') ?? '', true) ?: array();
        $found = false;
        if (isset($event_attributes[$key])) {
            foreach ($event_attributes[$key] as $ky => $event_attribute) {
                if ($event_attribute['volunteertypeid'] == $id) {
                    // Update object
                    $event_attributes[$key][$ky]['volunteer_needed_count'] = $volunteerCount;
                    $event_attributes[$key][$ky]['cc_email'] = $ccEmail;
                    $event_attributes[$key][$ky]['volunteer_description'] = $volunteerDescription;
                    $event_attributes[$key][$ky]['volunteer_hours'] = $volunteerHours;
                    $event_attributes[$key][$ky]['hide_from_signup_page'] = $hide_from_signup_page;
                    $event_attributes[$key][$ky]['allow_external_volunteers'] = $allow_external_volunteers;
                    $found = true;
                    break;
                }
            }
        }
        if (!$found) { // Add a new object.
            $event_attributes[$key][]= $requestAttributes;
        }

        // Convert back to JSON.
        $event_attributes_json = json_encode($event_attributes);

        $retVal = self::DBUpdatePS("UPDATE `events` SET `attributes`=?,`modifiedon`=NOW() WHERE`companyid`=? AND  `zoneid`=? AND  `eventid`=?", 'xiii', $event_attributes_json, $_COMPANY->id(),$_ZONE->id(),$this->id());
        $_COMPANY->expireRedisCache("EVT:{$this->id}");
        if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'event', $this->id(), $this->val('version'), ['updated_volunteer_request' => $requestAttributes]);
        }
        return $retVal;
    }

    public function getOrCreateVolunteerIdByVolunteerType(string $volunteer_type){
        global $_COMPANY, $_ZONE;
        $r =  self::DBGetPS("SELECT `volunteertypeid` FROM `event_volunteer_type` WHERE `companyid`=? AND (`zoneid`=? AND `type`=?)", 'iix', $_COMPANY->id(), $_ZONE->id(), $volunteer_type);
        if (!empty($r)){
           return $r[0]['volunteertypeid'];
        } else {
            return self::DBInsertPS("INSERT INTO `event_volunteer_type`( `companyid`, `zoneid`, `type`, `modifiedon`, `isactive`) VALUES (?,?,?,NOW(),1)","iix",$_COMPANY->id(),$_ZONE->id(),$volunteer_type);
        }
    }

    public function deleteEventVolunteerRequest(int $id)
    {
        global $_COMPANY, $_ZONE;
        $key = self::EVENT_ATTRIBUTES['event_volunteer_requests'];
        $event_attributes = json_decode($this->val('attributes') ?? '', true) ?: array();
        if (isset($event_attributes[$key])) {
            $event_volunteer_attributes = $event_attributes[$key];
            foreach ($event_volunteer_attributes as $k => $v) {
                if ($v['volunteertypeid'] == $id) {
                    unset($event_attributes[$key][$k]);
                    break;
                }
            }
        }
        // Reindex
        $event_attributes[$key] = array_values($event_attributes[$key]);
        $event_attributes_json = json_encode($event_attributes);

        $retVal = self::DBUpdatePS("UPDATE `events` SET `attributes`=?,`modifiedon`=NOW() WHERE`companyid`=? AND  `zoneid`=? AND  `eventid`=?", 'xiii', $event_attributes_json, $_COMPANY->id(),$_ZONE->id(),$this->id());
        $_COMPANY->expireRedisCache("EVT:{$this->id}");
        if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'event', $this->id(), $this->val('version'), ['delete_volunteer_request' => $id]);
        }
        return $retVal;
    }

    public function getVolunteerTypeValue(int $volunteertypeid){
        $type = self::GetEventVolunteerType($volunteertypeid);
        if($type){
            return $type['type'];
        }
        return '';
    }

    public function isEventVolunteerSignup(int $personid, int $volunteertypeid = 0)
    {
        $volunteertypeid_condition = '';
        if ($volunteertypeid) {
            $volunteertypeid_condition = "AND `volunteertypeid`='{$volunteertypeid}'";
        }
        $check = self::DBGet("SELECT volunteerid FROM event_volunteers WHERE eventid='{$this->id()}' AND userid='{$personid}' {$volunteertypeid_condition} AND `isactive`=1");

        if (!empty($check)){
            return true;
        }
        return false;
    }

    /**
     * @param int $volunteerUserId
     * @param int $volunteertypeid
     * @param string $action - valid values are create, update, delete
     * @return bool
     */
    private function sendEventVolunteerAssignmentEmail(int $volunteerUserId,int $volunteertypeid, string $action,int $oldVolunteerType = 0){
        global $_COMPANY;
        global $_USER;
        global $_ZONE;
        $retVal = false;
        $eventCreator = User::GetUser($this->val('userid'));
        $eventVolunteerRequest = $this->getEventVolunteerRequestByVolunteerTypeId($volunteertypeid) ?? [];
        if(array_key_exists('cc_email', $eventVolunteerRequest) && $eventVolunteerRequest['cc_email'] != ""){
            $ccEmail = $eventVolunteerRequest['cc_email'];
        }else{
            $ccEmail = "";
        }
        $volunteer = User::GetUser($volunteerUserId);
        $welcomeMessage = '';
        $volunteerType = self::GetEventVolunteerType($volunteertypeid);

        $group = Group::GetGroup($this->val('groupid'));
        $app_type = $_ZONE->val('app_type');
        $url = $_COMPANY->getAppURL($app_type)."eventview?id=".$_COMPANY->encodeId($this->id())."#eventDetail";
        $fromEmailLabel = $group->getFromEmailLabel(0, 0);

        if ($volunteer &&  $volunteerType && in_array($action, array('create','update','delete','approve','denied'))) {

            $receiverName = $volunteer->getFullName();
            $roleName = $volunteerType['type'];
            $eventName = $this->val('eventtitle');
            $start = new DateTime($this->val('start'), new DateTimeZone('UTC'));
            $start->setTimezone(new DateTimeZone($volunteer->val('timezone') ?: 'UTC'));
            $eventDate = $start->format('F j, Y');
            $eventTime = $start->format('g:i a (T)');
            $eventCreatorOrHostedBy = $this->val('event_contact') ?: ($eventCreator ? ($eventCreator->getFullName()." (".$eventCreator->val('email').")" ) : "Customer Support");

            $eventVolunteerDescription = $eventVolunteerRequest['volunteer_description'] ?? '';
            if ($action == 'delete'){
                $template = EmailHelper::EventVolunteerRoleOrganizerRemove($receiverName, $roleName, $eventName, $eventDate, $eventTime, $eventCreatorOrHostedBy,$fromEmailLabel);
                $subject = $template['subject'];
                $welcomeMessage =  $template['message'];

            } elseif ($action == 'create'){
                if ($_USER->id() != $volunteerUserId){// Case : if volunteer assgined by administrator
                    $template =  EmailHelper::EventVolunteerRoleOrganizerAssign($receiverName, $roleName, $eventName, $eventDate, $eventTime, $eventCreatorOrHostedBy,$fromEmailLabel,$this->isPublished(), $eventVolunteerDescription);
                    $subject = $template['subject'];
                    $welcomeMessage =  $template['message'];
                } else {
                    $template = EmailHelper::EventVolunteerRoleSomeoneSignsup($receiverName, $roleName, $eventName, $eventDate, $eventTime, $eventCreatorOrHostedBy,$fromEmailLabel, $eventVolunteerDescription);
                    $subject = $template['subject'];
                    $welcomeMessage =  $template['message'];
                }
            } elseif($action == 'update') {
                if ($_USER->id() != $volunteerUserId){// Case : if volunteer assgined by administrato]
                    $template = EmailHelper::EventVolunteerRoleOrganizerUpdates($receiverName, $roleName, $eventName, $eventDate, $eventTime, $eventCreatorOrHostedBy,$fromEmailLabel,$this->isPublished(),$eventVolunteerDescription);
                    $subject = $template['subject'];
                    $welcomeMessage =  $template['message'];
                } else {
                    $oldrole = self::GetEventVolunteerType($oldVolunteerType);
                    $template = EmailHelper::EventVolunteerRoleSomeoneUpdates($oldrole['type'], $receiverName, $roleName, $eventName, $eventDate, $eventTime, $eventCreatorOrHostedBy,$fromEmailLabel,$eventVolunteerDescription);
                    $subject = $template['subject'];
                    $welcomeMessage =  $template['message'];
                }
            } elseif($action == 'approve'){
                $template = EmailHelper::EventVolunteerRoleApproval($receiverName, $roleName, $eventName, $eventDate, $eventTime, $eventCreatorOrHostedBy,$fromEmailLabel);
                $subject = $template['subject'];
                $welcomeMessage =  $template['message'];
            }elseif($action == 'denied'){
                //TODO
            } else {
                return false;
            }

            $content_subheader = '';
            $content_subfooter = '';
            $welcomeMessage = '<p>'.EmailHelper::OutlookFixes($welcomeMessage).'</p>';
            $email_content = EmailHelper::GetEmailTemplateForGenericHTMLContent($content_subheader, $content_subfooter, $welcomeMessage);

            try {
                $retVal = $_COMPANY->emailSend2($fromEmailLabel, $volunteer->val('email'), $subject, $email_content, $app_type, '','',array(), $ccEmail);
                if ($retVal && $this->isPublished() && !$this->getUserRsvpStatus($volunteer->id())) {
                    // Send event invitation
                    $job = new EventJob($this->val('groupid'), $this->id());
                    $job->saveAsInviteType($volunteer->id(), 'Thank you for volunteering as '.$volunteerType['type']);
                }
            } catch (Exception $e) {
                Logger::Log("Caught exception sending Volunteer email to ".$volunteer->val('email'). ", exception ".$e->getMessage());
            }
        }

        return $retVal;
    }

    /**
     * @param int $volunteertypeid
     * @return int
     */
    public function getVolunteerCountByType(int $volunteertypeid): int
    {
        $condition = "";
        if ($volunteertypeid){
            $condition = "  AND `volunteertypeid`='{$volunteertypeid}'";
        }
        $v = self::DBGet("SELECT count(1) as volunteerCount FROM event_volunteers WHERE eventid='{$this->id()}' AND `isactive` = 1 {$condition}");
        return intval($v[0]['volunteerCount']);
    }

    public function updateRsvpListSetting(int $rsvp_display) {
        global $_COMPANY;
        $rsvp_display = in_array ($rsvp_display, [0,1,2,3]) ? $rsvp_display : 2;
        $retVal = self::DBMutate("UPDATE `events` set `rsvp_display`={$rsvp_display} WHERE eventid={$this->id}");
        $_COMPANY->expireRedisCache("EVT:{$this->id}");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['rsvp_display' => $rsvp_display]);
        }
        return $retVal;
    }

    public function updateEventVolunteer(int $volunteerid,int $status ){
        global $_COMPANY, $_ZONE;
        $existing_row = self::DBGet("SELECT * FROM event_volunteers WHERE `eventid`='{$this->id()}' AND `volunteerid`='{$volunteerid}'");
        if (!empty($existing_row)) {
            self::DBUpdate("UPDATE `event_volunteers` SET approval_status='{$status}' WHERE `eventid`='{$this->id()}' AND `volunteerid`='{$volunteerid}'");
            // Send Email Notification
            $this->sendEventVolunteerAssignmentEmail($existing_row[0]['userid'], $existing_row[0]['volunteertypeid'], 'approve');
        }
    }


    public function getPendingCollaboratingRequestsWith(){
        global $_COMPANY, $_ZONE;

        if ($this->val('collaborating_groupids_pending')){
            // Relax zone id check due to cross zone dependencies
            //AND `zoneid`='{$_ZONE->id()}'
            return self::DBGet("SELECT `groupid`,`groupname` FROM `groups` WHERE `companyid`='{$_COMPANY->id()}' AND `groupid` IN ({$this->val('collaborating_groupids_pending')})");
        }
        return array();
    }

    public function assignSpeakerApprover(int $speakerid, int $assigned_userid){
        global $_USER;
        global $_COMPANY, $_ZONE;
        return self::DBUpdatePS("UPDATE `event_speakers` SET `approval_status`=?,`approved_by`=? WHERE `companyid`=? AND `zoneid`=? AND `speakerid`=? AND `eventid`=?",'iiiiii',2,$assigned_userid,$_COMPANY->id(),$_ZONE->id(),$speakerid,$this->id);
    }

    public function isAllRequestedVolunteersSignedup(){
        global $_COMPANY, $_ZONE, $_USER;
        $eventVolunteerRequests = $this->getEventVolunteerRequests();

        // See if there is atleast one volunteer request that has not met volunteer requirement.
        foreach($eventVolunteerRequests as &$request){
            $volunteertypeid = $request['volunteertypeid'];
            $volunteer_needed_count = $request['volunteer_needed_count'];
            $signedupVolunteersCount = $this->getVolunteerCountByType($volunteertypeid);

            if ($volunteer_needed_count > $signedupVolunteersCount) {
                return false;
            }
        }

        return true;
    }

    /**
     * Function required by Topics .
     * @return string
     */
    public function getTopicTitle(): string
    {
        return $this->val('eventtitle');
    }

    public static function AddUpdateEventType(int $typeid, string $type, int $zoneid)
    {
        global $_COMPANY;

        $existing_row = self::DBGetPS("SELECT * FROM `event_type` WHERE `companyid`=? AND `zoneid`= ? AND `type` =?",'iis',$_COMPANY->id(),$zoneid, $type);
        if($typeid == 0 && !empty($existing_row) && $existing_row[0]['isactive'] == '0'){ // Reactivate deleted same record
            return self::DBMutate("UPDATE `event_type` SET `isactive`=1 WHERE typeid='{$existing_row[0]['typeid']}' ");
        }
        if ($typeid) { // Update explicitly provided rowid
            return self::DBMutatePS("UPDATE `event_type` SET `type`=?, `modifiedon`=now() WHERE `companyid`=? AND `zoneid`=? AND `typeid`=?",
                'siii',
                $type, $_COMPANY->id(), $zoneid, $typeid);
        } else{
            return self::DBInsertPS("INSERT INTO `event_type` (`type`,`sys_eventtype`, `companyid`, `zoneid`, `modifiedon`, `isactive`) VALUES (?,?,?,?,now(),1) ON DUPLICATE KEY UPDATE `type`=?, modifiedon=now()",'siiis',
            $type, 0, $_COMPANY->id(), $zoneid, $type);
        }

    }


    /**
     * @param int $typeid
     * @param int $volunteer_typeid
     * @param int $volunteer_needed_count
     * @return int
     */
    public static function UpdateEventTypeEventVolunteerRequests(int $typeid, int $volunteer_typeid, int $volunteer_needed_count): int
{
    global $_COMPANY;
    $key = self::EVENT_ATTRIBUTES['event_volunteer_requests'];

    $event_type_row = self::DBGet("SELECT * FROM event_type WHERE companyid={$_COMPANY->id()} AND typeid={$typeid}");
    if (count($event_type_row) != 1) {
        return 0;
    }

    $eventtype_attributes = json_decode($event_type_row[0]['attributes'] ?? '', true) ?: array();
    $existing_volunteer_requests = $eventtype_attributes[$key] ?? array();

    // Check if the volunteertypeid already exists in the existing data
    $volunteerTypeFound = false;
    foreach ($existing_volunteer_requests as &$existing_request) {
        if ($existing_request['volunteertypeid'] == $volunteer_typeid) {
            // If it exists, update the volunteer_needed_count
            $existing_request['volunteer_needed_count'] = $volunteer_needed_count;
            $volunteerTypeFound = true;
            break;
        }
    }
    unset($existing_request);
    // If not found, add it to the existing data
    if (!$volunteerTypeFound) {
        $existing_volunteer_requests[] = ['volunteertypeid' => $volunteer_typeid, 'volunteer_needed_count' => $volunteer_needed_count];
    }

    // Update the attributes with the merged data
    $eventtype_attributes[$key] = $existing_volunteer_requests;
    $eventtype_attributes_json = json_encode($eventtype_attributes);

    return self::DBUpdatePS("UPDATE `event_type` SET `attributes`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `typeid`=?", 'xii', $eventtype_attributes_json, $_COMPANY->id(), $typeid);
}

    /**
     * Remove a volunteer from the EventType's event_volunteer_requests data.
     *
     * @param int $typeid The event type ID.
     * @param int $volunteerTypeId The ID of the volunteer type to be removed.
     * @return int The number of affected rows (should be 1 if successful, 0 otherwise).
     */
    public static function RemoveEventTypeVolunteer(int $typeid, int $volunteerTypeId): int
    {
        global $_COMPANY, $_ZONE;
        $key = self::EVENT_ATTRIBUTES['event_volunteer_requests'];

        // Fetch the existing event type data
        $event_type_row = self::DBGet("SELECT * FROM event_type WHERE companyid={$_COMPANY->id()} AND typeid={$typeid}");
        if (count($event_type_row) != 1) {
            return 0;
        }

        $eventtype_attributes = json_decode($event_type_row[0]['attributes'] ?? '', true) ?: array();
        $existing_volunteer_requests = $eventtype_attributes[$key] ?? array();

        // Remove the volunteer with the specified volunteerTypeId from the event_volunteer_requests
        $volunteerRequests = array_filter($existing_volunteer_requests, function ($request) use ($volunteerTypeId) {
            return $request['volunteertypeid'] !== intval($volunteerTypeId);
        });

        // Update the attributes with the modified volunteer requests
        $eventtype_attributes[$key] = array_values($volunteerRequests);
        $eventtype_attributes_json = json_encode($eventtype_attributes);

        // Update the EventType record in the database with the new attributes data
        return self::DBUpdatePS("UPDATE `event_type` SET `attributes`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `typeid`=?", 'xii', $eventtype_attributes_json, $_COMPANY->id(), $typeid);
    }

    /**
     * @Depricated This method is to be used only for bulk provisioning.
     * @return int
     */
    public function activateEvent()
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        $retVal = self::DBMutate("UPDATE `events` SET publishdate= IF(start<now(), start,now()),`isactive`=1 WHERE `companyid`='{$_COMPANY->id()}' AND `eventid`='{$this->id()}' ");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['state' => 'published']);
        }
        return $retVal;
    }

    public function inviteUserToEvent(int $invitedUserId): bool
    {
        if (!$this->isUserAlreadyInvited($invitedUserId)) {
            $retVal = self::DBInsertPS("INSERT INTO `eventjoiners`( `eventid`, `userid`, `joindate`) VALUES (?,?,NOW())", 'ii', $this->id, $invitedUserId);
            if ($retVal) {
                self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), 0, ['state' => 'invite users', 'invited_userids' => $invitedUserId]);
            }
            return boolval($retVal);
        }
        return true;
    }

    /**
     * *** Business Rule - EVENT__SERIES_HEAD_START_END_TIMES_IS_BASED_ON_SUB_EVENTS ***
     * Updates start/end dates for event series head. Alyways call this function after event is created/updated/canceled.
     * @param int $eventSeriesId
     * @return int
     */
    private static function UpdateEventSeriesStartEndDates(int $eventSeriesId) : int
    {
        global $_COMPANY, $_ZONE;

        if (!$eventSeriesId)
            return 0;

        $series_dates = self::DBGet("SELECT MIN(start) AS series_start, MAX(end) as series_end FROM events WHERE companyid={$_COMPANY->id()} AND eventid!='{$eventSeriesId}' AND event_series_id='{$eventSeriesId}' AND isactive>0");
        if (!empty($series_dates)) {
            return self::DBMutatePS("UPDATE `events` SET `start`=?,`end`=? WHERE `companyid`=? AND  `eventid`=? AND event_series_id=?",'xxiii', $series_dates[0]['series_start'], $series_dates[0]['series_end'], $_COMPANY->id(), $eventSeriesId, $eventSeriesId);
        }
        return 0;
    }

    /**
     * Toggles joinee's checkin state.
     * @param int $joineeid
     * @param string $type
     * @param string $checkedin_by
     * @return int
     */
    public function toggleEventCheckinByJoinid(int $joineeid, string $type, string $checkedin_by)
    {

        global $_COMPANY, $_ZONE;
        $result = self::DBGet("SELECT `checkedin_date`, `userid` FROM `eventjoiners` WHERE `eventid`='{$this->id()}' and `joineeid`='{$joineeid}' ");

        if (!empty($result)){
            $userid = $result[0]['userid'];
            if ($type == 'checkin') {
                $impacted_rows = self::DBMutatePS("UPDATE eventjoiners SET checkedin_date=NOW(),checkedin_by=? WHERE `eventid`=? and `joineeid`=?","xii",$checkedin_by,$this->id(), $joineeid);

                // User was not previously checked-in, so send the check-in points
                if (!$result[0]['checkedin_date']) {
                    Points::HandleTrigger('EVENT_CHECK_IN', [
                        'eventId' => $this->id(),
                        'userId' => $userid,
                    ]);
                }

                return $impacted_rows;
            }else { // cancel Checkin
                $impacted_rows = self::DBMutatePS("UPDATE eventjoiners SET checkedin_date=NULL,checkedin_by=? WHERE `eventid`=? and `joineeid`=?","xii",'',$this->id(), $joineeid);

                if ($impacted_rows) {
                    Points::HandleTrigger('EVENT_CHECK_OUT', [
                        'eventId' => $this->id(),
                        'userId' => $userid,
                    ]);
                }

                return $impacted_rows;
            }
        }
        return 0;
    }


    public function inviteGroupsToEvent(string $invited_groups)
    {
        global $_COMPANY, $_ZONE;
        $retVal = self::DBMutatePS("UPDATE `events` SET `invited_groups`=? WHERE `companyid`=? AND  (`eventid`=? OR `event_series_id`=?)",'xiii', $invited_groups, $_COMPANY->id(), $this->id(),$this->id());
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['state' => 'invite groups', 'invited_groupids' => $invited_groups]);
        }
        return $retVal;
    }

    public function inviteChaptersToEvent(string $invited_chapters)
    {
        global $_COMPANY, $_ZONE;
        $retVal = self::DBMutatePS("UPDATE `events` SET `invited_chapters`=? WHERE `companyid`=? AND  `eventid`=?",'xii', $invited_chapters, $_COMPANY->id(), $this->id());

        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['state' => 'invite chapters', 'invited_chapterids' => $invited_chapters]);
        }
        return $retVal;
    }

    public function updateEventUnderReview()
    {
        global $_COMPANY; /* @var Company $_COMPANY */


        $status_under_review = self::STATUS_UNDER_REVIEW;
        $retVal = self::DBMutate("UPDATE `events` SET `isactive`='{$status_under_review}' WHERE `companyid`='{$_COMPANY->id()}' AND  `eventid`='{$this->id()}'");

        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['state' => 'review']);
        }
		return $retVal;
    }

    public function updateEventForSchedulePublishing(int $delay)
    {
        global $_COMPANY,$_USER; /* @var Company $_COMPANY and $_USER */

        $status_awaiting = self::STATUS_AWAITING;
        $retVal = self::DBMutate("UPDATE `events` SET `isactive`='{$status_awaiting}', `publishdate`=now() + interval {$delay} second, `publishedby`='{$_USER->id()}' WHERE `companyid`='{$_COMPANY->id()}' AND `eventid`='{$this->id()}'");

        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['state' => 'awaiting publish', 'publishedby' => $_USER->id()]);
        }
		return $retVal;
    }

    public function updateEventSeriesForSchedulePublishing(int $delay)
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        $status_awaiting = self::STATUS_AWAITING;
        $isactiveStatus = self::STATUS_DRAFT.','.self::STATUS_UNDER_REVIEW;
        $retVal = self::DBMutate("UPDATE `events` SET `isactive`='{$status_awaiting}', `publishdate`=now() + interval {$delay} second  WHERE `companyid`='{$_COMPANY->id()}' AND `event_series_id`='{$this->id()}' AND `isactive` IN ({$isactiveStatus}) ");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['state' => 'awaiting publish']);
        }
        return $retVal;
    }

    public function cancelEventForSchedulePublishing()
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        $status_draft = self::STATUS_DRAFT;
        $status_awaiting = self::STATUS_AWAITING;
        $status_active = self::STATUS_ACTIVE;
        $updateFields = "`isactive`='{$status_draft}', `publishdate`=null";
        if ($this->isSeriesEventHead()) {
            $activeEventsCount = self::DBGet("SELECT COUNT(1) as activeEventsCount FROM events WHERE companyid = '{$_COMPANY->id()}' AND event_series_id='{$this->id()}' AND `eventid`!='{$this->id()}' AND isactive='{$status_active}'");
            if ($activeEventsCount[0]['activeEventsCount']) { // update series head to active state
                $updateFields = "`isactive`='{$status_active}'";
            }
        }
        $retVal = self::DBMutate("UPDATE `events` SET {$updateFields} WHERE `companyid`='{$_COMPANY->id()}' AND `eventid`='{$this->id()}' AND `isactive`='{$status_awaiting}'");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['state' => 'draft']);
        }
        return $retVal;
    }

    public function cancelEventSeriesForSchedulePublishing()
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        $status_draft = self::STATUS_DRAFT;
        $status_pending_publish = self::STATUS_AWAITING;
        $retVal = self::DBMutate("UPDATE `events` SET `isactive`='{$status_draft}', `publishdate`=null WHERE `companyid`='{$_COMPANY->id()}' AND `event_series_id`='{$this->id()}' AND `eventid`!='{$this->id()}' AND isactive={$status_pending_publish}");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['state' => 'draft']);
        }
        return $retVal;
    }

    public function updateEventRsvpDuebyDatetime(string $rsvp_dueby)
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        $retVal = self::DBMutate("UPDATE `events` SET `rsvp_dueby`='{$rsvp_dueby}' WHERE `companyid`='{$_COMPANY->id()}' AND `eventid`='{$this->id()}' ");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['rsvp_dueby' => $rsvp_dueby]);
        }
        return $retVal;
    }

    public function deleteHoliday()
    {
        global $_COMPANY,$_ZONE;

        $retVal = self::DBMutate("DELETE FROM `events` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `eventid`='{$this->id()}'");
        if ($retVal) {
            self::LogObjectLifecycleAudit('delete', 'event', $this->id(), $this->val('version'));
        }
        return $retVal;
    }

    public function activateDeactivateHoliday(int $status)
    {
        global $_COMPANY,$_ZONE;
        $status = ($status == 1) ? 1 : 2;
        $retVal = self::DBMutate("UPDATE `events` SET `modifiedon`=NOW(),`isactive`='{$status}' WHERE `companyid`='{$_COMPANY->id()}' AND `eventid`='{$this->id()}'");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['state' => $status]);
        }
        return $retVal;
    }

    public static function UpdateEventTypeStatus(int $typeid, int $status)
    {
        global $_COMPANY,$_ZONE;
        $status = ($status == 1) ? 1 : 0; // One or zero
        return self::DBMutate("UPDATE `event_type` SET `isactive`='{$status}', `modifiedon`=NOW() WHERE `companyid`='{$_COMPANY->id()}' AND `typeid`='{$typeid}'");
    }

    public static function ActivateDeactivateEventCustomField(int $custom_field_id, int $status)
    {
        global $_COMPANY;
        return self::DBMutate("UPDATE `event_custom_fields` SET `isactive`='{$status}' WHERE `companyid`='{$_COMPANY->id()}' AND `custom_field_id`='{$custom_field_id}' ");
    }

    public static function ActivateDeactivateEventSpeakerField(int $speaker_fieldid, int $action)
    {
        global $_COMPANY, $_ZONE;
        return self::DBUpdatePS("UPDATE `event_speaker_fields` SET `isactive`='{$action}', `modifiedon`=NOW() WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `speaker_fieldid`='{$speaker_fieldid}' ");
    }

    public function linkTeamidOnEvent(int $teamid)
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        $retVal = self::DBMutate("UPDATE `events` SET `teamid`='{$teamid}' WHERE `companyid`='{$_COMPANY->id()}' AND `eventid`='{$this->id()}' ");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'event', $this->id(), $this->val('version'), ['teamid' => $teamid]);
        }
        return $retVal;
    }

    /**
     * This is a function to help build the data for the view.
     * @param string $eventClass should be from Event::EVENT_CLASS values
     * @param int $groupid events matching group id are fetched
     * @param bool $globalChapterOnly if set then the events that match chapterid=0 are set, $chapterid value is ignored.
     * @param int $chapterid events matching chapterid id are fetched
     * @param bool $globalChannelOnly if set then the events that match channelid=0 are set, $channelid value is ignored.
     * @param int $channelid events matching channel id are fetched
     * @param int $page
     * @param int $limit
     * @param bool $newEventsOnly if true upcoming events are returned, if false the past events are returned.
     * @param bool|null $pinnedEventsOnly, if null pin_to_top check will not be performed, otherwise it will be matched
     * or not matched depending upon the value
     * @param string $timezone
     * @param string $filterByStartDateTime
     * @param string $filterByEndDateTime
     * @param int $filterByVolunteerId
     * @return array array of data matched
     * @throws Exception
     */
    public static function GetGroupEventsViewData(string $eventClass, int $groupid, bool $globalChapterOnly, int $chapterid, bool $globalChannelOnly, int $channelid, int $page, int $limit, bool $newEventsOnly, ?bool $pinnedEventsOnly, string $timezone, string $filterByStartDateTime='', string $filterByEndDateTime='', int $filterByVolunteerId=0): array
    {
        global $_COMPANY;
        global $_USER;
        global $_ZONE;
        global $db;

        $chapterCondition = " ";
        if ($globalChapterOnly) {
            $chapterCondition = " AND chapterid=0";
        } elseif ($chapterid > 0) {
            $chapterCondition = " AND FIND_IN_SET({$chapterid},`chapterid`)";
        }

        $channelCondition = " ";
        if ($globalChannelOnly) {
            $channelCondition = " AND channelid=0";
        } elseif ($channelid > 0) {
            $channelCondition = " AND channelid={$channelid}";
        }

        $inclueGlobalEvents = '';
        if ($_COMPANY->getAppCustomization()['event']['show_global_events_in_group_feed']) {
            // CAUTION: OR condition in SQL ... validated OR part in bracket.
            $inclueGlobalEvents = " OR (`zoneid`={$_ZONE->id()} AND groupid=0 AND collaborating_groupids='')";
        }

        // Events can be collaborated across zones.
        // CAUTION: OR condition in SQL ... validated OR part in bracket.
        $collaboratingGroupCondition = " OR (FIND_IN_SET({$groupid},collaborating_groupids))";

        // CAUTION: OR condition in SQL ... validated OR part in bracket.
        $groupCondition = "AND (`groupid`={$groupid} {$inclueGlobalEvents} {$collaboratingGroupCondition})";


        if ($newEventsOnly) {
            $orderBy = " ORDER BY start ASC, eventid ASC"; // Adding eventid to order to not have random results where start date of two events is same.
        } else {
            $orderBy = " ORDER BY start DESC, eventid DESC"; // Adding eventid to order to not have random results where start date of two events is same.
        }

        // Note this pinned event filter block should be immediately following order by block as it updates previously
        // set order for pinned events.
        $pinnedEventFilter = '';
        if ($pinnedEventsOnly !== null) {
            if ($pinnedEventsOnly) {
                $pinnedEventFilter = ' AND pin_to_top=1';
                $orderBy = "ORDER BY modifiedon DESC";
            } else {
                if ($newEventsOnly){
                    $pinnedEventFilter = ' AND pin_to_top=0';
                }
            }
        }

        $startDateFilter = "";
        if (!empty($filterByStartDateTime)){
            $filterByStartDateTime = $db->covertLocaltoUTC("Y-m-d H:i:s", $filterByStartDateTime, $timezone);
            if ($filterByStartDateTime) {
                $startDateFilter = " AND `events`.`start`>= '{$filterByStartDateTime}'";
            }
        } else {
            if ($newEventsOnly && ! $startDateFilter) {
                // code commented out because for new event we are now checking only end date>= NOW in $endDateFilter condition
               // $startDateFilter = " AND  (`events`.`start`>= NOW() || (NOW() BETWEEN `events`.`start` AND `events`.`end`))";
            }
        }

        $endDateFilter = "";
        if (!empty($filterByEndDateTime)){
            $filterByEndDateTime = $db->covertLocaltoUTC("Y-m-d H:i:s", $filterByEndDateTime, $timezone);
            if ($filterByEndDateTime) {
                $endDateFilter = " AND `events`.`end`<= '{$filterByEndDateTime}'";
            }
        } else {
            if ($newEventsOnly) {
                $endDateFilter = " AND `events`.`end` >= NOW()";
            } else {
                $endDateFilter = " AND `events`.`end` < NOW()";
            }
        }

        $volunteerFilter = "";
        if ($filterByVolunteerId > 0){
            $volunteerFilter = " AND  JSON_CONTAINS(`attributes` -> '$.event_volunteer_requests[*].volunteertypeid', '{$filterByVolunteerId}')";
        }

        $eventClassFilter = '';
        if (in_array($eventClass, array_values(self::EVENT_CLASS))) {
            $eventClassFilter = " AND `eventclass` = '{$eventClass}'";
        }

        $myTeamEventsOnly = '';
        if (
            $eventClass === Event::EVENT_CLASS['TEAMEVENT']
            && !$_COMPANY->getAppCustomization()['teams']['team_events']['event_list']['show_all']
        ) {
            $myTeams = Team::GetMyTeams($groupid);

            if (empty($myTeams)) {
                return [];
            }

            $myTeamIds = implode(',', array_column($myTeams, 'teamid'));
            $myTeamEventsOnly = " AND `events`.`teamid` IN ({$myTeamIds})";
        }

        $limitFilter = '';
        if ($limit) {
            $max_items = $limit + 1; // We are adding 1 additional row to enable 'Show More to work'
            $start = (($page - 1) * $limit);
            $limitFilter = " LIMIT  {$start}, {$max_items}";
        }

        $data = self::DBROGet("
                SELECT * FROM `events`
                WHERE `companyid`={$_COMPANY->id()}
                  AND `eventid`!=`event_series_id`
                  AND `isprivate`=0
                  AND `isactive`=1
                  AND `listids`=0
                  {$groupCondition}
                  {$chapterCondition}
                  {$channelCondition}
                  {$startDateFilter}
                  {$endDateFilter}
                  {$volunteerFilter}
                  {$eventClassFilter}
                  {$pinnedEventFilter}
                  {$myTeamEventsOnly}
                  {$orderBy}
                  {$limitFilter}
              ");

        // Set the no of items we want to show; Note: The query returns 1 extra row to show load more button, but we
        // do not want to show more than the limit.
        $max_iter = count($data);
        $max_iter = ($max_iter > $limit) ? $limit : $max_iter;

        for ($i = 0; $i < $max_iter; $i++) {
            $event = Event::ConvertDBRecToEvent($data[$i]);
            $data[$i]['joinersCount'] = $event->getJoinersCount();
            $data[$i]['eventJoiners'] = $event->getRandomJoiners(12);
            $utc_tz = new DateTimeZone('UTC');
            $local_tz = new DateTimeZone($timezone);
            $localStart = (new DateTime($data[$i]['start'], $utc_tz))->setTimezone($local_tz);
            $data[$i]['localStart'] = $localStart;
            $data[$i]['localEnd'] = (new DateTime($data[$i]['end'], $utc_tz))->setTimezone($local_tz);
            $data[$i]['month'] = $localStart->format('F_Y');
            $data[$i]['collaboratedWith'] = null;
            $data[$i]['collaboratedWithFormated'] = null;
            $data[$i]['multiday'] = $event->getDurationInSeconds() > 86400;

            if (trim($data[$i]['collaborating_groupids'])) {
                $data[$i]['collaboratedWithFormated'] = $event->getFormatedEventCollaboratedGroupsOrChapters();
            }
            if ($data[$i]['event_series_id']) {
                $event = Event::GetEvent($data[$i]['event_series_id']);
                $data[$i]['event_series_name'] = $event->val('eventtitle');
            } else {
                $data[$i]['event_series_name'] = null;
            }
        }
        return $data;
    }

    /**
     * GetEventsByDateFilter
     *
     * @param  string $fromStartDate
     * @param  string $toStartDate
     * @param  int $excludeEventid
     * @param bool $excluePrivateEvents
     * @return array
     */
    public static function GetEventsByDateFilter(string $fromStartDate, string $toStartDate, int $excludeEventid)
    {
        global $_COMPANY, $_ZONE;

        return self::DBGet("SELECT * FROM `events` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND (`eventid`!='{$excludeEventid}' AND (`start` BETWEEN '{$fromStartDate}' AND'{$toStartDate}') AND teamid=0 AND isactive != 0) ");
    }

    /**
     * getEventRsvpDetail
     *
     * @param  int $joineeid
     * @return array||null
     */
    public function getEventRsvpDetail (int $joineeid)
    {
        global $_COMPANY, $_ZONE;

        $r =  self::DBGet("SELECT * FROM `eventjoiners` WHERE `eventid`='{$this->id()}' AND `joineeid`='{$joineeid}'");
        if (!empty($r)){
            return $r[0];
        }
        return null;
    }

    /**
     * getEventRsvpsByStatus
     *
     * @param  int $status
     * @return array
     */
    public function getEventRsvpsByStatus (int $joinstatus)
    {
        global $_COMPANY, $_ZONE;

        return self::DBGet("SELECT * FROM `eventjoiners` WHERE `eventid`='{$this->id()}' AND `joinstatus`='{$joinstatus}'");
    }

    /**
     * GetGroupHolidays
     *
     * @param  int $groupid
     * @param  bool $ignoreStatus
     * @return array
     */
    public static function GetGroupHolidays(int $groupid, bool $ignoreStatus = false)
    {
        global $_COMPANY, $_ZONE;

        $isActiveFilter = '';
        if (!$ignoreStatus) {
            $isActiveFilter = ' AND isactive=1 ';
        }

        return self::DBGet("SELECT * FROM `events` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `groupid`={$groupid} AND `eventclass`='holiday' {$isActiveFilter} ORDER BY start DESC");

    }

    /**
     * enableExternalFacing
     *
     * @return int
     */
    public function enableExternalFacing():int
    {
        global $_COMPANY, $_ZONE;

        return self::DBUpdate("UPDATE `events` SET `external_facing_event`=1, `modifiedon`=NOW() WHERE `companyid` = {$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `eventid` = {$this->id()}");
    }

    /**
     * disableExternalFacing
     *
     * @return int
     */
    public function disableExternalFacing() :int
    {
        global $_COMPANY, $_ZONE;

        return self::DBUpdate("UPDATE `events` SET `external_facing_event`=0, `modifiedon`=NOW() WHERE `companyid` = {$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `eventid` = {$this->id()}");
    }

    /**
     * getExternalFacingLink
     *
     * @return string
     */
    public function getExternalFacingLink():string
    {
        global $_COMPANY, $_ZONE;
        $urlParameters = json_encode(array('companyid'=>$_COMPANY->id(),'eventid'=>$this->id()));
        $encUrlParameters = aes_encrypt($urlParameters, TELESKOPE_USERAUTH_API_KEY, "u7KD33py3JsrPPfWCilxOxojsDDq0D3M", false);
        return $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'external/event?params=' . $encUrlParameters;
    }


    /**
     * getEventSurveys
     *
     * @return array
     */

    public function getEventSurveys():array
    {
        $key = self::EVENT_ATTRIBUTES['event_surveys'];
        $attributes = $this->val('attributes');

        if ($attributes) {
            $attributes = json_decode($attributes, true) ?: array();
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return array();
    }

    public function getEventSurveyByTrigger(string $trigger, int $rsvpStatus = 0)
    {
        if (in_array($trigger, self::EVENT_SURVEY_TRIGGERS)){
            $allSurveys = $this->getEventSurveys();
            if (!empty( $allSurveys)){
                if (array_key_exists($trigger,$allSurveys)){

                    $survey =  $allSurveys[$trigger];

                    if ($survey && $rsvpStatus) {
                        $rsvp_message = $this->getRSVPSuccessMessage($rsvpStatus);

                        if ($rsvp_message) {
                           $survey['survey_questions']['completedHtml'] = '<p style="font-size:16px !important;color:gray;">'.$rsvp_message.'</p>';
                        }
                    }
                    return $survey;
                }
            }
        }
        return null;
    }


    public function addUpdateEventSurvey(string $survey_title, string $survey_trigger, string $quesionJSON)
    {
        global $_COMPANY, $_ZONE;

        $surveyData = $this->getEventSurveyByTrigger($survey_trigger);
        if (!$surveyData){
            $surveyData['isactive'] = 2;
        }
        $surveyData['survey_title'] = $survey_title;
        $surveyData['survey_trigger'] = $survey_trigger;
        $surveyData['survey_questions'] = $quesionJSON ? json_decode($quesionJSON,true) : array();

        $attributes = $this->val('attributes');
        $key = self::EVENT_ATTRIBUTES['event_surveys'];
        if ($attributes) {
            $attributes = json_decode($attributes, true) ?: array();
            $attributes[$key][$survey_trigger] = $surveyData;
        } else {
            $attributes = array();
            $attributes[$key][$survey_trigger] = $surveyData;
        }
        $attributes = json_encode($attributes);

        $retVal = self::DBUpdatePS("UPDATE `events` SET `attributes`=?,`modifiedon`=NOW() WHERE`companyid`=? AND  `zoneid`=? AND  `eventid`=?", 'xiii', $attributes, $_COMPANY->id(),$_ZONE->id(),$this->id());
        $_COMPANY->expireRedisCache("EVT:{$this->id}");

        return $retVal;
    }


    public function activateDeactivateEventSurvey(string $survey_trigger, string $action)
    {
        global $_COMPANY, $_ZONE;

        $surveyData = $this->getEventSurveyByTrigger($survey_trigger);

        if ($surveyData){
            $status = $action =='activate' ? 1 : 0;
            $surveyData['isactive'] = $status;
            $attributes = json_decode($this->val('attributes'),true);
            $key = self::EVENT_ATTRIBUTES['event_surveys'];
            $attributes[$key][$survey_trigger] = $surveyData;
            $attributes = json_encode($attributes);
            $retVal = self::DBUpdatePS("UPDATE `events` SET `attributes`=?,`modifiedon`=NOW() WHERE`companyid`=? AND  `zoneid`=? AND  `eventid`=?", 'xiii', $attributes, $_COMPANY->id(),$_ZONE->id(),$this->id());
            $_COMPANY->expireRedisCache("EVT:{$this->id}");

            return $retVal;
        }
        return false;
    }

    public function isEventSurveyAvailable($trigger)
    {
        $eventSurvey = $this->getEventSurveyByTrigger($trigger);

        if ( $eventSurvey) {
            if ($eventSurvey['isactive'] == 1) {
                return true;
            }
        }
        return false;
    }

    public function updateEventSurveyResponse(int $userid, string $trigger, string $survey_responses)
    {
        if (empty($survey_responses) || !in_array($trigger, array_keys(self::EVENT_SURVEY_TRIGGERS))){
            return -1;
        }

        $rsvpDetail = $this->getEventRsvpDetailByUserid($userid);
        if ($rsvpDetail) {
            $joineeid = $rsvpDetail['joineeid'];
            $other_data =  $rsvpDetail['other_data'] ? json_decode($rsvpDetail['other_data'],true) : array();
        } else {
            // First insert a row
            $joineeid = self::DBInsertPS("INSERT INTO `eventjoiners` SET eventid={$this->id()}, userid={$userid}");
            $other_data = [];
        }

        $other_data[$trigger] = json_decode($survey_responses,true)??array();
        $other_data = json_encode($other_data);

        return self::DBUpdatePS("UPDATE `eventjoiners` SET `other_data`=? WHERE `eventid`=? AND `joineeid`=?", 'xii', $other_data, $this->id(),$joineeid);
    }


    /**
     * saveEventReminderHistory
     *
     * @param  string $subject
     * @param  string $message
     * @param  int $includeEventDetail
     * @param  array $reminderTo
     * @param  int $delayInSeconds
     * @return int
     */
    public function saveEventReminderHistory(string $subject, string $message, int $includeEventDetail, array $reminderTo, int $delayInSeconds): int
    {
        global $_COMPANY, $_USER;
        $reminderTo = implode(",",$reminderTo);
        $reminderTo = Sanitizer::SanitizeIntegerCSV($reminderTo);

        return self::DBInsertPS("INSERT INTO `event_reminder_history`( `companyid`, `eventid`, `reminder_to`, `reminder_subject`, `reminder_message`, `send_event_detail`, publishdate, createdon, createdby) VALUES (?,?,?,?,?,?,now() + interval {$delayInSeconds} second, now(), ?)",'iixxxii',$_COMPANY->id(), $this->id, $reminderTo, $subject, $message, $includeEventDetail, $_USER->id());
    }

    /**
     * getEventReminderHistory
     *
     * @return array
     */
    public function getEventReminderHistory(): array
    {
        global $_COMPANY;
        return self::DBROGet("SELECT * FROM `event_reminder_history` WHERE `eventid`='{$this->id()}' AND `companyid`={$_COMPANY->id()}");
    }

    /**
     * deleteReminderHistory
     *
     * @param  mixed $rid
     * @return int
     */
    public function deleteReminderHistory(int $rid): int
    {
        global $_COMPANY;
        return self::DBMutate("DELETE FROM event_reminder_history WHERE reminderid={$rid} AND eventid={$this->id()} AND companyid={$_COMPANY->id()}");
    }

    public function getEventDate(string $format = 'Y-m-d', string $extra = ''): string
    {
        $db = new Hems();
        return $db->covertUTCtoLocalAdvance($format, $extra, $this->val('start'), $_SESSION['timezone']);
    }

    public function updateEventRecordingLink(string $event_recording_link, string $event_recording_note): int
    {
        $retval = self::DBUpdatePS(
            'UPDATE `events` SET `event_recording_link` = ?, `event_recording_note` = ? WHERE `eventid` = ?',
            'sxi',
            $event_recording_link,
            $event_recording_note,
            $this->id()
        );

        if ($retval === 1) {
            self::LogObjectLifecycleAudit('update', 'event', $this->id(), $this->val('version'), [
                'old' => [
                    'event_recording_link' => $this->val('event_recording_link'),
                    'event_recording_note' => 'length = '. strlen($this->val('event_recording_note'))
                ],
                'new' => [
                    'event_recording_link' => $event_recording_link,
                    'event_recording_note' => 'length = '. strlen($event_recording_note)
                ]
            ]);
        }

        return $retval;
    }

    public function getEventRecordingShareableLink(): string
    {
        return $this->getZoneAwareUrlBase() . 'view_event_recording?e=' . $this->encodedId();
    }

    /**
     * signupExternalEventVolunteer
     *
     * @param  string $firstname
     * @param  string $lastname
     * @param  string $email
     * @param  int $volunteertypeid
     * @return int
     */
    public function signupExternalEventVolunteer(string $firstname, string $lastname, string $email, int $volunteertypeid): int
    {
        $volunteersCount = $this->getVolunteerCountByType($volunteertypeid);
        $eventVolunteerRequest = $this->getEventVolunteerRequestByVolunteerTypeId($volunteertypeid);

        if (!$eventVolunteerRequest) {
            return 0;
        }

        if ($this->isExternalEventVolunteerSignup($email, $volunteertypeid)) {
            return 3;
        }

        if ($eventVolunteerRequest['volunteer_needed_count'] <= $volunteersCount){
            return -1; // Not allow to add
        } else {

            // check if other role is signedup.
            $email_filter = '%\"email\":\"'. $email .'\"%';
            $checkExisting = self::DBGet("SELECT `volunteerid`, `volunteertypeid` FROM event_volunteers WHERE eventid='{$this->id()}' AND `other_data` like '{$email_filter}'");

            if (empty($checkExisting)){
                $other_data = json_encode(array("firstname" => $firstname, 'lastname' => $lastname, "email" => $email));
                $insert = self::DBInsertPS("INSERT INTO `event_volunteers` (`eventid`, `volunteertypeid`, `userid`, `createdby`, `approval_status`,`other_data`) VALUES (?,?,0,0,2,?)",'iix',$this->id, $volunteertypeid,$other_data);
                if($insert) {
                    return 1;
                }
            } else {
                $volunteerid = $checkExisting[0]['volunteerid'];
                $updated = self::DBUpdate("UPDATE `event_volunteers` SET `volunteertypeid`={$volunteertypeid},`approval_status`=2 WHERE `volunteerid`={$volunteerid}");
                if ($updated) {
                    return 2;
                }
            }
            return 0;
        }

    }

    /**
     * isExternalEventVolunteerSignup
     *
     * @param  string $email
     * @param  int $volunteertypeid
     * @return bool
     */
    public function isExternalEventVolunteerSignup(string $email, int $volunteertypeid){

        $email_filter = '%\"email\":\"'. $email .'\"%';

        $check = self::DBGet("SELECT volunteerid FROM event_volunteers WHERE eventid='{$this->id()}' AND `other_data` like '{$email_filter}' AND `volunteertypeid`='{$volunteertypeid}'");

        if (!empty($check)){
            return true;
        }
        return false;
    }


    /**
     * removeExternalEventVolunteer
     *
     * @param  string $email
     * @return void
     */
    public function removeExternalEventVolunteer(string $email){

        $email_filter = '%\"email\":\"'. $email .'\"%';
        $existing_row = self::DBGet("SELECT volunteerid FROM `event_volunteers` WHERE `eventid`='{$this->id()}' AND `other_data` like '{$email_filter}' ");

        if (!empty($existing_row)) {
            self::DBUpdate("DELETE FROM `event_volunteers` WHERE `eventid`='{$this->id()}' AND `volunteerid`='{$existing_row[0]['volunteerid']}' ");
        }
    }


    public function getEventRsvpOptionsReadOnlySurvey()
    {

        $rsvpOptions    = $this->getRSVPOptionsForSurveys();

        $question = array();
        $choices = array();

        foreach($rsvpOptions as $key=>$value){
            $choices[] = array ('value' => $key,'text'=>$value );
        }
        $question[] = array (
                        'type' => 'radiogroup',
                        'name' => 'question0',
                        'title' => 'Rsvp options [Do not update/delete this question]',
                        'description' => 'This question can be used for showing/hiding question based on rsvp status.',
                        'visible' => false,
                        'readOnly' => true,
                        'choices' => $choices,
                        'showNoneItem' => false
        );

        return array (
            'pages' => array(
                array (
                    'name' => 'page1',
                    'elements' => $question

                )
            )
        );
    }


    public function getShareableLink(): string
    {
        return $this->getZoneAwareUrlBase() . 'eventview?id=' . $this->encodedId();
    }

    public function getEventRsvpDetailByUserid (int $userid)
    {

        $r =  self::DBGet("SELECT * FROM `eventjoiners` WHERE `eventid`='{$this->id()}' AND `userid`='{$userid}'");
        if (!empty($r)){
            return $r[0];
        }
        return null;
    }


    public function canRespondPostEventSurvey()
    {
        global $_USER;
        $isRsvped = $this->getEventRsvpDetailByUserid($_USER->id());
        $isSurveyAvailable = $this->isEventSurveyAvailable(self::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT']);
        $canRespondSurvey = $this->getEventSurveyResponsesByTrigger(self::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT']) ? false : true;
        return $isRsvped  && $isSurveyAvailable && $canRespondSurvey && $this->isPublished() && $this->hasEnded();
    }


    public function getEventJoinersRecordForUser (int $userid)
    {
        return self::DBROGet("SELECT * FROM eventjoiners WHERE eventid={$this->id()} AND userid={$userid}");
    }

    public function logEventRecordingLinkClick(): void
    {
        global $_USER;

        self::DBInsert("INSERT INTO `event_recording_link_clicks` (`eventid`, `userid`, `clicked_at`) VALUES ({$this->id()}, {$_USER->id()}, NOW())");
    }

    public function getEventRecordingLinkClickStatsForUser (int $userid)
    {
        $retVal = ['first_clicked_at' => '', 'total_clicks' => 0];
        $result = self::DBROGet("
            SELECT MIN(`clicked_at`) AS `first_clicked_at`, COUNT(*) AS `total_clicks`
            FROM `event_recording_link_clicks`
            WHERE eventid = {$this->id} AND userid={$userid}
            ORDER BY `first_clicked_at` ASC
            ");

        if (!empty($result)) {
            return $result[0];
        }
        return $retVal;
    }

    public function getTypesenseDocument(): array
    {
        return [
            'id' => $this->getTypesenseId(),
            'type' => $this->getTypesenseDocumentType(),
            'company_id' => (int) $this->val('companyid'),
            'zone_id' => (int) $this->val('zoneid'),
            'title' => $this->val('eventtitle'),
            'description' => Html::SanitizeHtml($this->val('event_description')),
            'group_id' => (int) $this->val('groupid'),
        ];
    }

    public function getHomeFeedData(): array
    {
        global $_COMPANY;

        $utc_tz = new DateTimeZone('UTC');
        $local_tz = new DateTimeZone($_SESSION['timezone'] ?: 'UTC');

        $data = $this->toArray();

        $data = [
            'content_type' => 'event',
            'content_id' => $this->id(),
            'content_groupids' => $this->val('groupid'),
            'content_chapterids' => $this->val('chapterid'),
            'content_channelids' => $this->val('channelid'),
            'content_date' => $this->val('publishdate'),
            'content_pinned' => $this->val('groupid') == 0 ? $this->val('pin_to_top') : 0,
            'addedon' => $this->val('publishdate'),
            'joinerCount' => $this->getJoinersCount(),
            'joinerData' => $this->getRandomJoiners(12),
            'localStart' => (new DateTime($this->val('start'), $utc_tz))->setTimezone($local_tz),
            'collaboratedWith' => null,
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

        if ($this->val('collaborating_groupids')) {
            $data['collaboratedWith'] = Group::GetGroups(explode(',', $this->val('collaborating_groupids')),true,false);
        }

        return $data;
    }

    public function searchable(): bool
    {
        return
            $this->isActive()
            && !$this->isPrivateEvent()
            && $this->val('eventclass') === self::EVENT_CLASS['EVENT']
        ;
    }

    ###
    ### TODO: The following method needs optimizations.
    ###
    public static function GetDiscoverMyEventsData(int $zoneid, array $eventTypeArray, int $page, int $limit, string $timezone, string $startDate, string $endDate, bool $deepLoad): array
    {
        global $_COMPANY, $_USER, $db;

        $key = "DSCVR_MYEVTS:{$_USER->id()}";

        $requestedStartDateInUTC = $db->covertLocaltoUTC("Y-m-d H:i:s", $startDate . " 00:00:00", $timezone);
        $requestedEndDateInUTC = $db->covertLocaltoUTC("Y-m-d H:i:s", $endDate . " 23:59:59", $timezone);

        $offset = ($page - 1) * $limit;

        if (empty($eventTypeArray)) {
            return [
                'events' => [],
                'show_more' => false
            ];
        }
        if (
            !$deepLoad &&
            // load from cache
            ($data = $_COMPANY->getFromRedisCache($key)) != false &&
            // check if date range is withing previously cached data; if not we cannot use the cache.
            ($requestedStartDateInUTC >= $data['startDate']) &&
            ($requestedEndDateInUTC <= $data['endDate'])
        ) {
            $filteredEvents = array_filter($data['events'], function ($event) use ($requestedStartDateInUTC, $requestedEndDateInUTC, $timezone, $eventTypeArray, $zoneid) {
                $isWithinDateRange =
                    // Event start date is between the provided dates
                    ($event['start'] >= $requestedStartDateInUTC && $event['start'] <= $requestedEndDateInUTC) ||
                    // Event end date is between the provided dates dates
                    (($event['end'] >= $requestedStartDateInUTC && $event['end'] <= $requestedEndDateInUTC)) ||
                    // Sometimes the event might be multiday event which starts before the start date and ends after then end date.
                    (($event['start'] < $requestedStartDateInUTC && $event['end'] > $requestedEndDateInUTC));

                //filter based on event type
                $matchesEventType = empty($eventTypeArray) || in_array($event['eventtype'], $eventTypeArray);
                // filter based on zones
                $matchesZone = ($zoneid == 0) || ($event['zoneid'] == $zoneid);
                return $isWithinDateRange && $matchesEventType && $matchesZone;
            });
            $filteredEvents = array_values($filteredEvents);
            $pagedEvents = array_slice($filteredEvents, $offset, $limit);
            $show_more = ($offset + $limit) < count($filteredEvents);
            return [
                'events' => $pagedEvents,
                'show_more' => $show_more
            ];
        }

        $newData = self::GetEventsByZone($zoneid, $timezone, $requestedStartDateInUTC, $requestedEndDateInUTC);

        $newCachedData = [
            'startDate' => $requestedStartDateInUTC,
            'endDate' => $requestedEndDateInUTC,
            'events' => $newData
        ];
        $_COMPANY->putInRedisCache($key, $newCachedData, 900); // 15 minutes

        // Filter for event type
        $newData = array_filter($newData, function ($event) use ($eventTypeArray) {
            return empty($eventTypeArray) || in_array($event['eventtype'], $eventTypeArray);
        });
        // pagination to cached data
        $pagedEvents = array_slice($newData, $offset, $limit);
        $show_more = ($offset + $limit) < count($newData);
        return [
            'events' => $pagedEvents,
            'show_more' => $show_more
        ];
    }

    public static function GetEventsByZone(int $zoneid, string $timezone, string $startDateInUTC, string $endDateInUTC):array
    {
        global $_COMPANY, $_USER,  $db;

        // User zones
        $userZoneIds = $_USER->val('zoneids');
        // User groups
        $joinedGroups =  $_USER->getFollowedGroupsAsCSV() ?? '';
        $joinedGroupIds = [];
        $joinedChapterIds = [];
        $joinedChannelIds = [];

        $joinedGroupIds = array_filter(array_unique(explode(',',$joinedGroups)));

        // User chapters
        $joinedChapters = '';
        foreach ($joinedGroupIds as $groupid) {
            $joinedChapters .= ',' . $_USER->getFollowedGroupChapterAsCSV($groupid);
        }
        $joinedChapterIds = array_filter(array_unique(explode(',', $joinedChapters)));

        $joinedChannels = '';
        foreach ($joinedGroupIds as $groupid) {
            $joinedChannels .= ',' . $_USER->getFollowedGroupChannels($groupid);
        }
        $joinedChannelIds = array_filter(array_unique(explode(',', $joinedChannels)));

        $zoneCondition = " AND (events.`zoneid` IN ({$userZoneIds})) ";
        if($zoneid){
            $zoneCondition = " AND (events.`zoneid` IN ({$zoneid})) ";
        }

        $eventClass = self::EVENT_CLASS['EVENT'];
        $eventClassFilter = '';
        if (in_array($eventClass, array_values(self::EVENT_CLASS))) {
            $eventClassFilter = " AND `eventclass` = '{$eventClass}'";
        }

        // Date filters
        $startDateFilter = "";
        if (!empty($startDateInUTC)){
            $filterByStartDateTime = Sanitizer::SanitizeUTCDatetime($startDateInUTC);
            if ($filterByStartDateTime) {
                $startDateFilter = " AND (`events`.`start`>= '{$filterByStartDateTime}' OR ( '{$filterByStartDateTime}' BETWEEN `events`.`start` AND  `events`.`end` ))";
            }
        }

        $endDateFilter = "";
        if (!empty($endDateInUTC)){
            $filterByEndDateTime = Sanitizer::SanitizeUTCDatetime($endDateInUTC);
            if ($filterByEndDateTime) {
                $endDateFilter = " AND `events`.`end`<= '{$filterByEndDateTime}'";
            }
        }
        $activeGroupCondition = " AND (events.groupid=0 OR g.`isactive`=1)"; // Active group check is not applicable if groupid=0

        $data = self::DBROGet(" SELECT `events`.eventtitle, `events`.pin_to_top, `events`.isactive, `events`.addedon, `events`.publishdate, `events`.channelid, `events`.chapterid, `events`.collaborating_groupids, `events`.event_series_id, `events`.eventid, `events`.companyid, `events`.groupid, `events`.zoneid, `events`.`start`, `events`.`end`, `events`.event_attendence_type,`events`.eventvanue, `events`.vanueaddress, `events`.web_conference_sp, `events`.rsvp_display, `events`.eventtype, `events`.pin_to_top FROM `events`
         LEFT JOIN `groups` g ON g.groupid=events.groupid AND g.isactive=1
         WHERE events.`companyid`={$_COMPANY->id()}
        --  AND (events.groupid IN ({$joinedGroups}) OR (events.groupid=0) )
         AND `eventid`!=`event_series_id`
         AND events.`isactive`=1
          AND events.`isprivate`=0
         {$activeGroupCondition}
         {$zoneCondition}
         {$eventClassFilter}
         {$startDateFilter}
         {$endDateFilter}");

        $data = array_filter($data, function($event) use ($joinedGroupIds, $joinedChapterIds, $joinedChannelIds) {
            // This function executes various checks to see if the event should be included or not. We short circuit
            // processing to return as soon as we find a criteria that satisfies the event should be included.

            // For Global Events
            $isGlobalEvent = $event['groupid'] == 0 && $event['collaborating_groupids'] == '';
            if ($isGlobalEvent) {
                return true;
            }

            // Match joined groups
            $isMemberGroupEvent = in_array($event['groupid'], $joinedGroupIds) && $event['chapterid'] == 0 &&  $event['channelid'] == 0;
            if ($isMemberGroupEvent) {
                return true;
            }

            // Match collab events
            $collabGroupids = explode(',',$event['collaborating_groupids']);
            $isMemberGroupCollabEvent = !empty(array_intersect($joinedGroupIds, $collabGroupids)) && $event['chapterid'] == 0 &&  $event['channelid'] == 0;
            if ($isMemberGroupCollabEvent) {
                return true;
            }

            // if event is chapter based, see if the user has joined one of the chapters
            if ($event['chapterid'] != 0) {
                $eventChapterIds = explode(',', $event['chapterid']);
                $isMemberChapterEvent = !empty(array_intersect($joinedChapterIds, $eventChapterIds));
                if ($isMemberChapterEvent) {
                    return true;
                }
            }

            // Channel filter
            if ($event['channelid'] !=0){
                $isMemberChannelEvent = in_array($event['channelid'],$joinedChannelIds);
                if ($isMemberChannelEvent) {
                    return true;
                }
            }

            return false;

        });

        usort($data, function($a, $b){
            $dtA = strtotime($a['start'] . ' UTC');
            $dtB = strtotime($b['start'] . ' UTC');
            return $dtA - $dtB;
        });
         // Set the no of items we want to show;
        $data = array_values($data);
        $max_iter = count($data);

        for ($i = 0; $i < $max_iter; $i++) {
            $event = Event::ConvertDBRecToEvent($data[$i]);
            $data[$i]['joinersCount'] = $event->getJoinersCount();
            $data[$i]['eventJoiners'] = $event->getRandomJoiners(12);
            $utc_tz = new DateTimeZone('UTC');
            $local_tz = new DateTimeZone($timezone);
            $localStart = (new DateTime($data[$i]['start'], $utc_tz))->setTimezone($local_tz);
            $data[$i]['localStart'] = $localStart;
            $data[$i]['localEnd'] = (new DateTime($data[$i]['end'], $utc_tz))->setTimezone($local_tz);
            $data[$i]['month'] = $localStart->format('F_Y');
            $data[$i]['collaboratedWith'] = null;
            $data[$i]['collaboratedWithFormated'] = null;
            $data[$i]['multiday'] = $event->getDurationInSeconds() > 86400;
            $data[$i]['zonename'] = $_COMPANY->getZone($data[$i]['zoneid']) ?-> val('zonename');

            // Group data
            $groupData = Group::GetGroup($data[$i]['groupid']);
            $data[$i]['group_overlaycolor'] = $groupData->val('overlaycolor');
            $data[$i]['groupname'] = $groupData->val('groupname');
            $data[$i]['groupname_short'] = $groupData->val('groupname_short');
            $data[$i]['group_zone_url'] = Url::GetZoneAwareUrlBase($data[$i]['zoneid']);
            if (trim($data[$i]['collaborating_groupids'])) {
                $data[$i]['collaboratedWith'] = Group::GetGroups(explode(',',$event->val('collaborating_groupids')),true,false);
                $data[$i]['collaboratedWithFormated'] = $event->getFormatedEventCollaboratedGroupsOrChapters();
            }
            if ($data[$i]['event_series_id']) {
                $event = Event::GetEvent($data[$i]['event_series_id']);
                $data[$i]['event_series_name'] = $event->val('eventtitle');
            } else {
                $data[$i]['event_series_name'] = null;
            }
        }
        return $data;
    }

    public static function GetMyEventsBySection(string $section, string $eventClass, int $selected_zone_id, int $page, int $limit, string $timezone, string $filterByStartDateTime='', string $filterByEndDateTime='', int $filterByVolunteerId=0): array
    {
        global $_COMPANY, $_USER,  $db;

        if ((!in_array($section,self::MY_EVENT_SECTION))){
            return array();
        }

        if ($section == self::MY_EVENT_SECTION['MY_UPCOMING_EVENTS']) {
            $orderBy = " ORDER BY `events`.start ASC, `events`.eventid ASC"; // Adding eventid to order to not have random results where start date of two events is same.
        } else {
            $orderBy = " ORDER BY `events`.start DESC, `events`.eventid DESC"; // Adding eventid to order to not have random results where start date of two events is same.
        }

        $startDateFilter = "";
        if (!empty($filterByStartDateTime)){
            $filterByStartDateTime = $db->covertLocaltoUTC("Y-m-d H:i:s", $filterByStartDateTime, $timezone);
            if ($filterByStartDateTime) {
                $startDateFilter = " AND `events`.`start`>= '{$filterByStartDateTime}'";
            }
        }

        $endDateFilter = "";
        if (!empty($filterByEndDateTime)){
            $filterByEndDateTime = $db->covertLocaltoUTC("Y-m-d H:i:s", $filterByEndDateTime, $timezone);
            if ($filterByEndDateTime) {
                $endDateFilter = " AND `events`.`end`<= '{$filterByEndDateTime}'";
            }
        } else {
            if ($section == self::MY_EVENT_SECTION['MY_UPCOMING_EVENTS']) {
                $endDateFilter = " AND `events`.`end` >= NOW()";
            } else {
                $endDateFilter = " AND `events`.`end` < NOW()";
            }
        }

        $volunteerFilter = "";
        if ($filterByVolunteerId > 0){
            $volunteerFilter = " AND  JSON_CONTAINS(`attributes` -> '$.event_volunteer_requests[*].volunteertypeid', '{$filterByVolunteerId}')";
        }

        $eventClassFilter = '';
        if (in_array($eventClass, array_values(self::EVENT_CLASS))) {
            $eventClassFilter = " AND `eventclass` = '{$eventClass}'";
        }

        $limitFilter = '';
        if ($limit) {
            $max_items = $limit + 1; // We are adding 1 additional row to enable 'Show More to work'
            $start = (($page - 1) * $limit);
            $limitFilter = " LIMIT  {$start}, {$max_items}";
        }

        $myEventsJoinCondition = " JOIN `eventjoiners` USING(eventid)";
        $myEventsCondition = "  AND `eventjoiners`.userid='{$_USER->id()}' AND `eventjoiners`.joinstatus IN(1,2,11,12,21,22)";

        // Zone Filter
        $zoneCondition = '';
        if ($selected_zone_id) {
            $selected_zone = $_COMPANY->getZone($selected_zone_id);

            $collaboraing_zoneids = empty($selected_zone->val('collaborating_zoneids')) ? $selected_zone->id() : $selected_zone->id() . ',' . $selected_zone->val('collaborating_zoneids');
            $zoneGlobalEvents = " (events.`zoneid`={$selected_zone->id()} AND events.groupid=0 AND collaborating_groupids='')";
        $zoneCollaborationEvents = " (events.zoneid IN({$collaboraing_zoneids}) AND events.groupid=0 AND collaborating_groupids!='')";
            $zoneCondition = " AND (events.`zoneid`={$selected_zone->id()} OR {$zoneGlobalEvents} OR {$zoneCollaborationEvents})";
        }

        $activeGroupCondition = " AND (events.groupid=0 OR g.`isactive`=1)"; // Active group check is not applicable if groupid=0

        $data = self::DBROGet("
                SELECT `events`.* FROM `events`
                {$myEventsJoinCondition}
                LEFT JOIN `groups` g ON g.groupid=events.groupid
                WHERE events.`companyid`={$_COMPANY->id()}
                {$zoneCondition}
                  AND `eventid`!=`event_series_id`
                  AND events.`isactive`=1
                  {$activeGroupCondition}
                  {$myEventsCondition}
                  {$startDateFilter}
                  {$endDateFilter}
                  {$volunteerFilter}
                  {$eventClassFilter}
                  {$orderBy}
                  {$limitFilter}
              ");

        // Set the no of items we want to show; Note: The query returns 1 extra row to show load more button, but we
        // do not want to show more than the limit.
        $max_iter = count($data);
        $max_iter = ($max_iter > $limit) ? $limit : $max_iter;

        for ($i = 0; $i < $max_iter; $i++) {
            $event = Event::ConvertDBRecToEvent($data[$i]);
            $data[$i]['joinersCount'] = $event->getJoinersCount();
            $data[$i]['eventJoiners'] = $event->getRandomJoiners(12);
            $utc_tz = new DateTimeZone('UTC');
            $local_tz = new DateTimeZone($timezone);
            $localStart = (new DateTime($data[$i]['start'], $utc_tz))->setTimezone($local_tz);
            $data[$i]['localStart'] = $localStart;
            $data[$i]['localEnd'] = (new DateTime($data[$i]['end'], $utc_tz))->setTimezone($local_tz);
            $data[$i]['month'] = $localStart->format('F_Y');
            $data[$i]['collaboratedWith'] = null;
            $data[$i]['collaboratedWithFormated'] = null;
            $data[$i]['multiday'] = $event->getDurationInSeconds() > 86400;

            if (trim($data[$i]['collaborating_groupids'])) {
                $data[$i]['collaboratedWithFormated'] = $event->getFormatedEventCollaboratedGroupsOrChapters();
            }
            if ($data[$i]['event_series_id']) {
                $event = Event::GetEvent($data[$i]['event_series_id']);
                $data[$i]['event_series_name'] = $event->val('eventtitle');
            } else {
                $data[$i]['event_series_name'] = null;
            }
        }
        return $data;
    }

    public static function GetMyEventSubmissions(int $state_filter, int $year_filter, int $selected_zone_id): array
    {
        global $_COMPANY, $_USER; 

        $isactiveCondition = " AND a.isactive = '".self::STATUS_ACTIVE . "'";
        if ($state_filter !=  self::STATUS_ACTIVE) { // Only Active
            $isactiveCondition = ' AND a.isactive IN ('.self::STATUS_DRAFT.','.self::STATUS_UNDER_REVIEW.','.self::STATUS_AWAITING.')';
        }
        if ($state_filter == self::STATUS_INACTIVE) { // Only Cancelled events, status with zero
            $isactiveCondition = " AND a.isactive = '".self::STATUS_INACTIVE. "'";
        }

        $yearCondition = " AND YEAR(a.start) = {$year_filter}";
        if ($year_filter >date('Y')){
            $yearCondition = " AND YEAR(a.start) >= {$year_filter}";
        }
        $selectedZoneConditionA = '';
        $selectedZoneConditionG = '';
        if ($selected_zone_id) {
            $selectedZoneConditionA = "AND a.zoneid = {$selected_zone_id}";
            $selectedZoneConditionG = "AND g.zoneid = {$selected_zone_id}";
        }

        return self::DBROGet("SELECT
                                a.*,
                                IFNULL(
                                    (SELECT GROUP_CONCAT(DISTINCT chaptername SEPARATOR '^')
                                    FROM chapters
                                    WHERE FIND_IN_SET(chapterid, a.chapterid)
                                    ),
                                    ''
                                ) as chaptername,
                                IFNULL(
                                    (SELECT GROUP_CONCAT(list_name SEPARATOR '^')
                                    FROM dynamic_lists
                                    WHERE FIND_IN_SET(listid, a.listids)
                                    ),
                                    ''
                                ) as listname,
                                ch.channelname
                            FROM
                                events a
                            LEFT JOIN
                                group_channels ch ON ch.channelid = a.channelid
                            LEFT JOIN
                                (
                                    SELECT
                                        g.groupid
                                    FROM
                                        `groups` g
                                    WHERE
                                      g.isactive = 1
                                      AND g.companyid = {$_COMPANY->id()}
                                      {$selectedZoneConditionG}
                                ) g
                                ON g.groupid = a.groupid
                                OR FIND_IN_SET(g.groupid, a.collaborating_groupids)
                                OR FIND_IN_SET(g.groupid, a.collaborating_groupids_pending)
                            WHERE
                                a.companyid = {$_COMPANY->id()}
                                AND (
                                    FIND_IN_SET({$_USER->id()},a.event_contributors)
                                    AND a.event_series_id != a.eventid
                                    AND a.eventclass NOT IN ('holiday', 'teamevent')
                                    {$selectedZoneConditionA}
                                    {$isactiveCondition}
                                    {$yearCondition}
                                )
                            GROUP BY
                                a.eventid
                            ORDER BY
                                a.eventid DESC;
                            ");

    }

    /**
     * If the event series has RSVP restriction SINGLE_EVENT_ONLY, then this method will return
     * schedule conflicts.
     * @return mixed|null
     */
    public function checkRsvpConflictForSeriesEventForUser (int $userid)
    {
        if ($this->isPublished() && !$this->hasEnded() && $this->val('event_series_id')) {

            $eventGroup = self::GetEvent($this->val('event_series_id'));

            if ( $eventGroup->val('rsvp_restriction') == self::EVENT_SERIES_RSVP_RESTRICTION['SINGLE_EVENT_ONLY']) {

                // Get users RSVP status for any event in the given series.
                $e = self::DBGet("SELECT eventjoiners.eventid, eventjoiners.joinstatus, events.eventtitle FROM eventjoiners JOIN events ON events.eventid= eventjoiners.eventid WHERE eventjoiners.joinstatus not in (0,3,15,25) AND events.event_series_id={$this->val('event_series_id')} AND eventjoiners.userid={$userid} AND events.eventid != {$this->id()} AND events.isactive=1");

                if (!empty($e)) {
                    return $e[0];
                }
            }
        }
        return null;
    }

    /**
     * If the event series has RSVP restriction ANY_NUMBER_OF_NON_OVERLAPPING_EVENTS, then this method will return
     * schedule conflicts.
     * @return mixed|null
     */
    public function checkScheduleConflictForSeriesEventForUser (int $userid)
    {
        global $_COMPANY, $_ZONE;

        if ($this->isPublished() && !$this->hasEnded() && $this->val('event_series_id') && !in_array($this->getUserRsvpStatus($userid),array(1,2,11,12,21,22)) ) {

            $eventGroup = self::GetEvent($this->val('event_series_id'));

            if ( $eventGroup->val('rsvp_restriction') == self::EVENT_SERIES_RSVP_RESTRICTION['ANY_NUMBER_OF_NON_OVERLAPPING_EVENTS']){
                // Include events the user has already RSVP'ed.
                $includeEventsSubQuery = "
                        SELECT eventjoiners.`eventid`
                        FROM `eventjoiners`
                            JOIN events USING(`eventid`)
                        WHERE eventjoiners.userid='{$userid}'
                          AND events.companyid={$_COMPANY->id()}
                          AND events.event_series_id='{$this->val('event_series_id')}'
                          AND `eventjoiners`.joinstatus IN (1,2,11,12,21,22)
                          AND `events`.isactive=1
                          ";

                // Exclude the current event and series head envelope.
                $excludeIds = $this->id().','.$this->val('event_series_id');

                $e = self::DBGet("
                    SELECT *
                    FROM `events`
                    WHERE `companyid`='{$_COMPANY->id()}'
                      AND `zoneid`='{$_ZONE->id()}'
                      AND (
                          `eventid` NOT IN ({$excludeIds})
                              AND (
                                  (`start` >= '{$this->val("start")}' AND `start` < '{$this->val("end")}')
                                      OR
                                  ('{$this->val("start")}' >= `start` AND '{$this->val("start")}' < `end`)
                              )
                              AND isactive=1
                              AND `eventid` IN ({$includeEventsSubQuery})
                      )
                  ");

                if (!empty($e)) {
                    return $e[0];
                }
            }
        }
        return null;
    }




    public static function UpdateCustomFieldPriority(int $custom_field_id,int $old_sorting_order, int $new_sorting_order)
    {
        global $_COMPANY, $_ZONE;

        $row = self::DBGet("SELECT `sorting_order` FROM `event_custom_fields` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `custom_field_id`={$custom_field_id}");
        $oldSortingOrder = (int) $row[0]['sorting_order'] ?? $old_sorting_order;

        if ($new_sorting_order < $old_sorting_order) {
            return self::DBMutate("UPDATE `event_custom_fields` SET `sorting_order` = IF (custom_field_id={$custom_field_id},$new_sorting_order, `sorting_order` + 1) WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND sorting_order BETWEEN {$new_sorting_order} AND {$oldSortingOrder}");
        } else {
            return self::DBMutate("UPDATE `event_custom_fields` SET `sorting_order` = IF (custom_field_id={$custom_field_id},$new_sorting_order, `sorting_order` - 1) WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND sorting_order BETWEEN {$oldSortingOrder} AND {$new_sorting_order}");
        }

    }

    public static function GetFieldsUsingSelectedFieldsForVisiableIfLogic(int $custom_field_id)
    {
        global $_COMPANY, $_ZONE;

        $rows = self::DBGetPS('SELECT `custom_field_name` FROM `event_custom_fields` WHERE `companyid` = ? AND `zoneid`=? AND `visible_if`->>"$.custom_field_id"=?','iii',$_COMPANY->id(), $_ZONE->id(), $custom_field_id);

        return implode(', ', array_column($rows,'custom_field_name'));

    }

    public function getEventSurveyResponsesByTrigger(string $trigger, bool $alwaysAllowSurveyResponsesUpdate = false)
    {
        global $_USER;
        $response = false;
        if (!$this->isEventSurveyAvailable($trigger)){
            return false;
        }
        $rsvpDetail = $this->getEventRsvpDetailByUserid($_USER->id());
        if ($rsvpDetail) {
            if ($trigger == self::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT'] && !$this->hasEnded()){
                return false;
            }
            if ($alwaysAllowSurveyResponsesUpdate){
                return true;
            }
            $other_data =  $rsvpDetail['other_data'] ? json_decode($rsvpDetail['other_data'],true) : array();
            if (array_key_exists($trigger,$other_data)) {
                $response = $other_data[$trigger];
            }
        }
        return $response;
    }

    public function getRSVPOptionsForSurveys()
    {
        $buttons = array();
        $att_type = (int)$this->val('event_attendence_type');
        $max_inperson   = (int)(($att_type == 1 || $att_type ==3) ? $this->val('max_inperson') : 0);
        $max_online     = (int)(($att_type == 2 || $att_type ==3) ? $this->val('max_online') : 0);

        if ($max_inperson || $max_online) {
            if ($max_inperson) {
                $buttons[self::RSVP_TYPE['RSVP_INPERSON_YES']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_YES']);
                $buttons[self::RSVP_TYPE['RSVP_INPERSON_WAIT']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_INPERSON_WAIT']);
            }
            if ($max_online) {
                $buttons[self::RSVP_TYPE['RSVP_ONLINE_YES']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_YES']);
                $buttons[self::RSVP_TYPE['RSVP_ONLINE_WAIT']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_ONLINE_WAIT']);
            }

        } else {
            $buttons[self::RSVP_TYPE['RSVP_YES']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_YES']);
            $buttons[self::RSVP_TYPE['RSVP_MAYBE']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_MAYBE']);
        }
        $buttons[self::RSVP_TYPE['RSVP_NO']] = self::GetRSVPLabel(self::RSVP_TYPE['RSVP_NO']);

        return $buttons;
    }


    public function isEventAccessible(bool $passActiveCheck = false)
    {
        global $_COMPANY, $_ZONE;
        $group = null;
        if ($this->val('groupid')){
            $group =  $group = Group::GetGroup($this->val('groupid'));
        }
        $iAccessible = true;
        $message = '';

        if ($group && $group->val('isactive') != Group::STATUS_ACTIVE ) {
            $iAccessible = false;
            $message =  sprintf(gettext('Access denied. The %s is not active. Please contact your adminstroator'),$_COMPANY->getAppCustomization()['group']['name-short']);

        } elseif (!$passActiveCheck && $this->val('isactive') != Event::STATUS_ACTIVE) {
            $iAccessible = false;
            $message =  gettext('This event is not active and cannot be accessed.');
        } else {
            if ($this->val('teamid')){

                if ($group && !$group->isTeamsModuleEnabled()){
                    $iAccessible = false;
                    $message = sprintf(gettext('Access denied. The Event feature is unavailable because the %s feature is disabled. Please contact your administrator.'),$_COMPANY->getAppCustomization()['teams']['name']);
                }

                if (!$_COMPANY->getAppCustomization()['teams']['team_events']['enabled']) {
                    $iAccessible = false;
                    $message = gettext('Access denied. The event feature is disabled. Please contact your administrator.');
                }

            } else {
                if (!$_COMPANY->getAppCustomization()['event']['enabled']) {
                    $iAccessible = false;
                    $message =  gettext('Access denied. The event feature is disabled. Please contact your administrator.');
                }
            }
        }

        return array($iAccessible, $message);

    }


    public function getRSVPSuccessMessage(int $rsvpStatus) {
        $rsvpSuccessMessage = '';
        if ($rsvpStatus) {
            switch ($rsvpStatus) {
                case self::RSVP_TYPE['RSVP_YES']:
                case self::RSVP_TYPE['RSVP_MAYBE']:
                    $rsvpSuccessMessage = sprintf(gettext('Thank you for RSVPing "%s" to this event. Your RSVP has been recorded, and you will receive an email with a corresponding calendar hold.'), Event::GetRSVPLabel($rsvpStatus,true));
                    break;
                case self::RSVP_TYPE['RSVP_NO']:
                case self::RSVP_TYPE['RSVP_INPERSON_WAIT_CANCEL']:
                case self::RSVP_TYPE['RSVP_ONLINE_WAIT_CANCEL']:
                    $rsvpSuccessMessage = sprintf(gettext('Thank you for RSVPing "%s" to this event. Your RSVP selection has been recorded.'),  Event::GetRSVPLabel($rsvpStatus,true));
                    break;
                case self::RSVP_TYPE['RSVP_INPERSON_YES']:
                case self::RSVP_TYPE['RSVP_ONLINE_YES']:
                case self::RSVP_TYPE['RSVP_INPERSON_WAIT']:
                case self::RSVP_TYPE['RSVP_ONLINE_WAIT']:
                    $rsvpSuccessMessage = sprintf(gettext('Thank you for RSVPing "%s" to this event. Your RSVP selection has been recorded. If you cannot attend the event, please select "%s" to the calendar invite to open up your reservation for another user.'),  Event::GetRSVPLabel($rsvpStatus,true), Event::GetRSVPLabel(self::RSVP_TYPE['RSVP_NO'],true));
                    break;
                default:
                    $rsvpSuccessMessage = '';
            }

        }

        return $rsvpSuccessMessage;

    }

    public function __getval_use_and_chapter_connector(): int|string|null
    {
        if ($this->val('event_series_id') && $this->id() !== (int) $this->val('event_series_id')) {
            $series_event = Event::GetEvent($this->val('event_series_id'));

            return $series_event->val('use_and_chapter_connector');
        }

        return $this->val('use_and_chapter_connector', false);
    }

    public function isCollaboratingEvent() : bool
    {
        return $this->val('collaborating_groupids') && ($this->val('groupid') == 0);
    }

    public function isAdminContent() : bool
    {
        return empty($this->val('collaborating_groupids')) && ($this->val('groupid') == 0);
    }


    public static function GetAllPreferredTimezones() {
        global $_COMPANY, $_ZONE;
        $key = "EVT:TZ_{$_ZONE->id()}";
        if (($data = $_COMPANY->getFromRedisCache($key)) === false) {
            $data = self::DBGet("SELECT * FROM `event_preferred_timezones` WHERE companyid = '{$_COMPANY->id()}' AND `zoneid` = '{$_ZONE->id()}'");
            $_COMPANY->putInRedisCache($key, $data, 86400);
        }
        return $data;
    }

    public static function GetPreferredTimezoneData(int $timezonId){
        global $_COMPANY, $_ZONE;
        // Since this method is used by Admin panel we are fetching data from RW copy of data instead of RO.
        $row = self::DBGet("SELECT * FROM `event_preferred_timezones` WHERE companyid = '{$_COMPANY->id()}' AND `zoneid` = '{$_ZONE->id()}' AND timezoneid={$timezonId}");
        if($row){
            return $row[0];
        }
        return false;
    }
    public static function AddUpdatePreferredTimezoneType(int $timezoneid, string $timezone_display_name, string $timezone_system_value){
        global $_COMPANY, $_ZONE, $_USER;
        $key = "EVT:TZ_{$_ZONE->id()}";
        if($timezoneid){
            $result = self::DBMutatePS("UPDATE `event_preferred_timezones` SET `timezone_display_name`=?,  `timezone_system_value` = ?, `modifiedby`=?, `modifiedon`=NOW() WHERE `companyid`=? AND `zoneid`=? AND `timezoneid` = ? ", 'ssiiii', $timezone_display_name, $timezone_system_value,$_USER->id(), $_COMPANY->id(), $_ZONE->id(), $timezoneid);
        }else{
           $result = self::DBInsertPS("INSERT INTO `event_preferred_timezones`( `companyid`, `zoneid`, `timezone_display_name`, `timezone_system_value`, `createdby`, `createdon`, `modifiedby`) VALUES (?,?,?,?,?,NOW(),?)",'iissii', $_COMPANY->id(), $_ZONE->id(), $timezone_display_name, $timezone_system_value,$_USER->id(),$_USER->id());
        }
        $_COMPANY->expireRedisCache($key);
        return $result;
    }

    public static function DeletePreferredTimezone(int $timezonId){
        global $_COMPANY, $_ZONE;
        $key = "EVT:TZ_{$_ZONE->id()}";
        $result = self::DBMutate("DELETE FROM `event_preferred_timezones` WHERE companyid={$_COMPANY->id()} AND timezoneid={$timezonId} AND `zoneid` = '{$_ZONE->id()}'");
        $_COMPANY->expireRedisCache($key);
        return $result;
    }

    public function getAssociatedOrganization()
    {
        global $_COMPANY, $_ZONE;

        $row = self::DBGet("SELECT org.*, eo.additional_contacts, eo.custom_fields FROM `company_organizations` org JOIN `event_organizations` eo ON org.organization_id = eo.organizationid WHERE eo.companyid={$_COMPANY->id()} AND org.companyid={$_COMPANY->id()} AND eventid='{$this->id()}'");
        if (!empty($row)) {
            return $row;
        }
        return null;
    }

    public function updateEventOrganization($organization_id, $contactsJSON, $custom_fields){
        return $this->manageOrgIds($organization_id, $contactsJSON, $custom_fields);
    }

    public function removeEventOrganization($organization_id)
    {
        global $_COMPANY;
        // Remove relationship between event and organization
       $result = self::DBMutatePS("DELETE FROM `event_organizations` WHERE companyid={$_COMPANY->id()} AND eventid={$this->id()} AND organizationid={$organization_id}");
       return true;
    }

    private function manageOrgIds(int $organization_id, string $contactsJSON='', string $custom_fields=''){

        global $_COMPANY, $_USER;
        $existing_org = self::DBGetPS("SELECT * FROM `event_organizations` WHERE companyid=? AND `organizationid`= ? AND `eventid`=?",'iii',$_COMPANY->id,$organization_id,$this->id());

        if (!$existing_org){
            $result = self::DBInsertPS("INSERT INTO `event_organizations`( `eventid`, `organizationid`,`companyid`,`custom_fields`, `additional_contacts`,`createdby`, `createdon`) VALUES (?,?,?,?,?,?,NOW())" ,"iiixxi",$this->id(),$organization_id,$_COMPANY->id(), $custom_fields, $contactsJSON, $_USER->id());
        } else{
            $result = self::DBUpdatePS("UPDATE `event_organizations` SET `additional_contacts`=?, `custom_fields`=? WHERE `companyid`=? AND `eventid`=? AND `organizationid`= ?" , 'xxiii', $contactsJSON, $custom_fields, $_COMPANY->id(), $this->id(), $organization_id);
           return 1; // Data is updating successfully but return zero $result so added return 1
        } 
        return $result;
    }

    public static function GetCustomName(bool $defaultNamePlural = false){
        global $_COMPANY, $_ZONE, $_USER;
        if ($defaultNamePlural){
            $name = gettext('Events');
        } else {
            $name = gettext('Event');
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

    public static function GetJoinedEventsByDate (int $userid, string $date) :array
    {
        global $_COMPANY;
        $date = Sanitizer::SanitizeUTCDatetime($date,'Y-m-d');
        return self::DBROGet("SELECT e.eventid, e.`start`, e.`end`
                                FROM `events` e
                                JOIN `eventjoiners` ej USING (eventid)
                                WHERE 
                                e.companyid = {$_COMPANY->id()}
                                AND (e.isactive = 1 OR e.isactive = 5)
                                AND ej.userid = {$userid}
                                AND e.`start` BETWEEN '{$date}' AND e.`end`");
    }

    public function updateFormValidatedStatus() {
        global $_COMPANY, $_ZONE;
        return self::DBMutatePS("UPDATE `events` SET `form_validated`=1 WHERE `companyid`=? AND `zoneid`=? AND eventid = ?", 'iii', $_COMPANY->id(), $_ZONE->id(), $this->id);
    }

    public function isSeriesEventsFormValidated()
    {
        $seriesEvents = self::GetEventsInSeries($this->id());

        foreach($seriesEvents as $sevent ) {
            if ($sevent->val('isactive') != self::STATUS_INACTIVE && $sevent->val('form_validated') !=1){
                return false;
            }
        }
        return true;
    }

    public function canUpdateEventExpenseEntry(): bool
    {
        global $_COMPANY, $_USER;

        if (
            $_COMPANY->getAppCustomization()['budgets']['enabled'] &&
            $_COMPANY->getAppCustomization()['event']['budgets'] &&
            ($this->loggedinUserCanManageEventBudget()) &&
            ($this->isCancelled() || $this->isDraft() || $this->isUnderReview() || $this->isPublished()) &&
            !$this->isSeriesEventHead() &&
            //!$this->val('collaborating_groupids') &&
           // $this->val('groupid') &&
            // Event is not purely a channel based event, there is atleast one chapter
            !($this->val('channelid')!=0 && $this->val('chapterid')==0)
        ) {
            return true;
        }
        return false;
    }

    public function cloneEvent(array $overrides = []): Event
    {
        global $_COMPANY, $_USER;
        $fields = $overrides + $this->fields;
        $event = new Event(0, $_COMPANY->id(), $fields);

        $event_contact = html_entity_decode($event->val('event_contact'));
        // Very Important how we set the invited groups as we do not want to carry over the invited groups in the clone
        // If the event is group specific then we will add only the group to the invited group list to avoid other invited groups to be cloned over. For Admin events, existing invited groups will be cloned.

        $pendingChapters = [];
        $pendingGroups = [];
        $eventChapterIds = $event->val('chapterid');
        $joinedGroupIds = $event->val('collaborating_groupids');
        if ($event->val('groupid')) {
            $invited_groups = $event->val('groupid');
        } elseif (!empty($event->val('collaborating_groupids'))) {
        
            // process cloned groupids permissions
            $eventChapterIds = explode(',',$eventChapterIds);
            $joinedGroupIds = explode(',',$joinedGroupIds);
          
            foreach ($joinedGroupIds as $groupid) {
                // $chapter_ids = explode(',',$_USER->getFollowedGroupChapterAsCSV($groupid));
                $getGroupApprovedChapters = Group::GetGroupChaptersFromChapterIdsCSV($groupid, $event->val('chapterid'));
                if (!empty($getGroupApprovedChapters)){
                    $chapter_ids = explode(',', $getGroupApprovedChapters);
                    $chapterLeadsCount = 0;
                    foreach($chapter_ids as $chapter_id){
                        $chapterDetail = Group::GetChapterNamesByChapteridsCsv($chapter_id);
                        if ($chapterDetail) {
                            if (!$_USER->canCreateContentInGroupChapterV2($groupid,$chapterDetail[0]['regionids'],$chapter_id) && !$_USER->canPublishContentInGroupChapterV2($groupid,$chapterDetail[0]['regionids'],$chapter_id)) {  // add to collaborating_chapterids_pending
                                $pendingChapters[] = $chapter_id;
                            } else {
                                $chapterLeadsCount++;
                            }
                        }
                    }
                    if (!$chapterLeadsCount) { // If user have chapter with allowed permission, auto approve parent group
                        $pendingGroups[] = $groupid;
                    }
                } else {
                    $group = Group::GetGroup($groupid);
                    if (
                        !$_USER->isCompanyAdmin()
                        &&
                        !$_USER->isZoneAdmin($group->val('zoneid'))
                        &&
                        !$_USER->canCreateContentInGroupOnly($groupid) 
                        &&
                        !$_USER->canPublishContentInGroupOnly($groupid)
                        &&
                        !$_USER->isRegionallead($groupid)
                    ) {
                        $pendingGroups[] = $groupid;
                    }
                }  
            }
        
            // Remove the chapterid and groupid from the eventchapterids and joinedgroupids
            $eventChapterIds = implode(',',array_diff($eventChapterIds, $pendingChapters));
            $joinedGroupIds = implode(',',array_diff($joinedGroupIds, $pendingGroups));

            if ($this->val('collaborating_groupids_pending')) {
                $pendingGroups = array_merge($pendingGroups,explode(',',$this->val('collaborating_groupids_pending')));
                $pendingGroups = array_diff($pendingGroups, explode(',', $joinedGroupIds));
                // Reindex the array to avoid gaps in keys (optional)
                $pendingGroups = array_values($pendingGroups);
            }
        
            if ($this->val('collaborating_chapterids_pending')) {
                $pendingChapters = array_merge($pendingChapters,explode(',',$this->val('collaborating_chapterids_pending')));
                $pendingChapters = array_diff($pendingChapters, explode(',', $eventChapterIds));
                // Reindex the array to avoid gaps in keys (optional)
                $pendingChapters = array_values($pendingChapters);
            }

            $invited_groups = $joinedGroupIds;

        } else {
            $invited_groups = $event->val('invited_groups');
        }

        //safe check to ensure nothing is empty or duplicate
        $pendingChapters = array_unique($pendingChapters);
        $pendingGroups = array_unique($pendingGroups);

        $clone_id = Event::CreateNewEvent($event->val('groupid'), $eventChapterIds, $event->val('eventtitle'), $event->val('start'), $event->val('end'), $event->val('timezone'), $event->val('eventvanue'), $event->val('vanueaddress'), $event->val('event_description'), $event->val('eventtype'), $invited_groups, $event->val('max_inperson'), $event->val('max_inperson_waitlist'), $event->val('max_online'), $event->val('max_online_waitlist'), $event->val('event_attendence_type'), $event->val('web_conference_link'), $event->val('web_conference_detail'), $event->val('web_conference_sp'), $event->val('checkin_enabled'), $joinedGroupIds, $event->val('channelid'), $event->val('event_series_id'), $event->val('custom_fields'), $event_contact, $event->val('venue_info')??'', $event->val('venue_room'), $event->val('isprivate'), $event->val('eventclass'), $event->val('add_photo_disclaimer'), $event->val('calendar_blocks'), $event->val('content_replyto_email'), $event->val('listids'), $event->val('use_and_chapter_connector'), 0, $event->val('disclaimerids'), $event->val('rsvp_enabled'), $event->val('event_contact_phone_number'),$event->val('event_contributors')??'');

        $clone_event = self::GetEvent($clone_id);
        if ($clone_event) {
            $clone_event->updateRsvpListSetting($event->val('rsvp_display'));
            $clone_event->copyAttachmentsFrom($this);

            if ($this->val('collaborating_groupids') || $this->val('collaborating_groupids_pending')) {
                $clone_event->updateCollaboratingGroupids(explode(',', $joinedGroupIds), $pendingGroups, explode(',', $eventChapterIds), $pendingChapters);
            }
        }

        return $clone_event;
    }

    public function isParticipationLimitUnlimited(string $field): bool
    {
        return (int) $this->val($field) === Event::MAX_PARTICIPATION_LIMIT;
    }
    public function getFormatedEventPendingCollaboratedGroups() {
        if ($this->val('collaborating_groupids_pending')) {
            $groupNames = array();
            $collaboratedWithGroups = explode(',', $this->val('collaborating_groupids_pending'));
            foreach($collaboratedWithGroups as $id){
                $groupNames[] = Group::GetGroupName($id);
            }
            usort($groupNames, 'strnatcasecmp');
            $formattedNames = Arr::NaturalLanguageJoin($groupNames, ' & ');
            return $formattedNames;
        }
        return '';
    }

    public function getEventCollabroatedChapters()
    {
        $chapterNames = [];
        if ($this->val('chapterid')) {
            $chapterNames = Group::GetChapterNamesByChapteridsCsv($this->val('chapterid'));
        }
        $chapterNames = array_unique(array_column($chapterNames, 'chaptername'));
        usort($chapterNames, 'strnatcasecmp');
        return Arr::NaturalLanguageJoin($chapterNames, ' & ');
    }

    public function getEventPendingCollabroatedChapters()
    {
        $chapterNames = [];
        if ($this->val('collaborating_chapterids_pending')) {
            $chapterNames = Group::GetChapterNamesByChapteridsCsv($this->val('collaborating_chapterids_pending'));
        }
        $chapterNames = array_unique(array_column($chapterNames, 'chaptername'));
        usort($chapterNames, 'strnatcasecmp');
        return Arr::NaturalLanguageJoin($chapterNames, ' & ');

    }

    public function loggedinUserCanManageEventBudget() {
//        /* Issue 4313, logic change */
//        global $_USER;
//
//        if ($this->val('groupid')){
//            return $_USER->canManageBudgetGroupSomethingV1($this->val('groupid'));
//        } elseif ($this->val('collaborating_groupids')) {
//            $gids = explode(',',$this->val('collaborating_groupids'));
//            foreach($gids as $gid){
//                if($_USER->canManageBudgetGroupSomethingV1($gid)){
//                    return true;
//                }
//            }
//        }
//        return false;

        return $this->loggedinUserCanUpdateOrPublishOrManageEvent() || $this->isEventContributor();
    }
    public function isEventConsentRequired():bool
    {
        if ($this->disclaimer_consent_required === NULL) {
            $this->disclaimer_consent_required = false;
            $disclaimers = Disclaimer::GetDisclaimersByIdCsv($this->val('disclaimerids'));
            if (!empty($disclaimers)) {
                foreach ($disclaimers as $disclaimer) {
                    if ($disclaimer->isConsentRequired()) {
                        $this->disclaimer_consent_required = true;
                    }
                }
            }
        }
        return $this->disclaimer_consent_required;
    }

    public function isRsvpEnabled() : bool
    {
        return boolval ($this->val('rsvp_enabled'));
    }

    public function getEventExpenseEntries(bool $force_load = true) {
        if (!$force_load && $this->expense_entries) {
            return $this->expense_entries;
        }

        $budget_recs  = self::DBGet("SELECT budgetuses.*,groups.groupname,`chapters`.`chaptername` from budgetuses  left JOIN `groups` ON groups.groupid=budgetuses.groupid LEFT JOIN `chapters` ON  `chapters`.chapterid = budgetuses.chapterid  where budgetuses.eventid={$this->id}");
        if (count($budget_recs)) {
            $this->expense_entries = $budget_recs;
        }
        return $this->expense_entries;
    }

    public function getExternalVolunteerRoles(): array
    {
        global $_COMPANY;

        if (
            !$_COMPANY->getAppCustomization()['event']['volunteers']
            || !$_COMPANY->getAppCustomization()['event']['external_volunteers']
        ) {
            return [];
        }

        $volunteer_roles = $this->getEventVolunteerRequests();

        $external_volunteer_roles = array_filter($volunteer_roles, function (array $event_role) {
            return $event_role['allow_external_volunteers'] ?? false;
        });

        return array_map(function (array $event_role) {
            $role = Event::GetEventVolunteerType($event_role['volunteertypeid']);
            $event_role['type'] = $role['type'];
            return $event_role;
        }, $external_volunteer_roles);
    }

    public function getMyExternalVolunteers(bool $from_master_db = false): array
    {
        global $_USER;
        return $this->getUsersExternalVolunteers($_USER->id(), $from_master_db);
    }

    public function getUsersExternalVolunteers(int $userid, bool $from_master_db = false): array
    {
        $method_name = $from_master_db ? 'DBGet' : 'DBROGet';

        $volunteers = call_user_func(
            [self::class, $method_name],
            "SELECT * FROM `event_volunteers` WHERE `eventid` = {$this->id()} AND `external_user_careofid` = {$userid} AND `isactive` = 1 ORDER BY `volunteerid` ASC"
        );

        return array_map(function (array $volunteer) {
            return EventVolunteer::Hydrate($volunteer['volunteerid'], $volunteer);
        }, $volunteers);
    }

    public function addExternalEventVolunteer(string $firstname, string $lastname, string $email, int $volunteertypeid, int $careof_userid): int
    {
        global $_USER;

        $volunteersCount = $this->getVolunteerCountByType($volunteertypeid);
        $eventVolunteerRequest = $this->getEventVolunteerRequestByVolunteerTypeId($volunteertypeid);

        if ($eventVolunteerRequest['volunteer_needed_count'] <= $volunteersCount) {
            return -1;
        }

        $other_data = json_encode([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'external_user_email' => $email,
        ]);

        $userid = 0;
        $createdby_userid = $_USER->id();
        $approval_status = 2;

        if (EventVolunteer::GetExternalEventVolunteerByEmail($this->id(), $email)) {
            return -2;
        }

        $id = self::DBInsertPS('
                INSERT INTO `event_volunteers` (`eventid`, `volunteertypeid`, `userid`, `createdby`, `approval_status`, `other_data`, `external_user_careofid`)
                VALUES (?,?,?,?,?,?,?)
            ',
            'iiiiixi',
            $this->id(),
            $volunteertypeid,
            $userid,
            $createdby_userid,
            $approval_status,
            $other_data,
            $careof_userid
        );

        self::LogObjectLifecycleAudit('create', 'EVTVOL', $id, 0, [
            'operation_details' => [
                'opname' => 'create_external_event_volunteer',
                'new' => [
                    'id' => $id,
                    'other_data' => $other_data,
                ],
            ],
        ]);

        return $id;
    }

    public function reconcileEvent(int $is_event_reconciled)
    {
       global $_COMPANY, $_ZONE;
       if (!$this->hasEnded()) { // If event has not ended then force the event to be marked as not reconciled.
           $is_event_reconciled = 0;
       }
       return self::DBMutate("UPDATE `events` SET `is_event_reconciled`='{$is_event_reconciled}' WHERE `companyid`={$_COMPANY->id()} AND `eventid`={$this->id()}");
    }

    public static function UnpinPastEvents()
    {
        global $_COMPANY;

        // get a list of all target events that ended in past 30 days from DB ReadOnly
        $target_events_list = self::DBROGet("SELECT eventid FROM events WHERE `end` BETWEEN now() - interval 10 day AND now() - interval 1 day AND pin_to_top > 0");

        // Update pin_to_top = 0 for these events
        foreach ($target_events_list as $target_events) {
            $eid = $target_events['eventid'];
            self::DBMutate("UPDATE events SET pin_to_top = 0 where eventid = {$eid}");
        }
    }

    /**
     * This method calculates if the Action button should be disabled if the event is approved or in the state of approval
     * @return bool
     */
    public function isActionDisabledDuringApprovalProcess() {
        global $_COMPANY;

        $event_zone_id = $this->val('zoneid');
        // Performance improvement, if is_action_disabled_due_to_approval_process was previously calculated, use it.
        if ($this->is_action_disabled_due_to_approval_process !== NULL) {
            return $this->is_action_disabled_due_to_approval_process;
        }

        $this->is_action_disabled_due_to_approval_process = false;
        if ($_COMPANY->getAppCustomizationForZone($event_zone_id)['event']['approvals']['enabled']) {

            if($this->isSeriesEventSub()) {
                $seriesHead = self::GetEvent($this->val('event_series_id'));
                $approval = $seriesHead->getApprovalObject();
            } else {
                $approval = $this->getApprovalObject();
            }
            
            if ($approval && !$approval->isApprovalStatusDenied() && !$approval->isApprovalStatusReset() && !$approval->isApprovalStatusCancelled()) {
                $this->is_action_disabled_due_to_approval_process = true;
            }
        }
        return $this->is_action_disabled_due_to_approval_process;
    }

    
    /**
     * @param  string $communication_type_key
     * @param string $email_subject
     * @param string $email_body
     * @param int $email_trigger_days
     * @return int
     */
    public static function SetEventCommunicationTemplateForZone (string $communication_type_key, string $email_subject, string $email_body, int $email_trigger_days): int
    {
        global $_ZONE;
        $communication_type_key  = self::EVENT_COMMUNICATION_TYPES[$communication_type_key];
        $retVal = 0;
        if ($communication_type_key) {
            $configuration_key = 'event.email_templates.' . $communication_type_key;
            $configuration_vals = array(
                'subject' => $email_subject,
                'body' => $email_body,
                'trigger_days' => $email_trigger_days
            );
            $retVal = $_ZONE->updateZoneAttributesKeyVal($configuration_key, $configuration_vals);
        }
        return $retVal;
    }

    /**
     * DeleteEventCommunicationTemplate
     *
     * @param  string $communication_type_key
     * @return int
     */
    public static function DeleteEventCommunicationTemplate (string $communication_type_key): int
    {
        global $_ZONE;
        $communication_type_key  = self::EVENT_COMMUNICATION_TYPES[$communication_type_key];
        $retVal = 0;
        if ($communication_type_key) {
            $configuration_key = 'event.email_templates.' . $communication_type_key;
            $retVal = $_ZONE->updateZoneAttributesKeyVal($configuration_key, null); // Setting it to null will remove it
        }
        return $retVal;
    }

    /**
     * GetEventCommunicationTemplates
     *
     * @param  string $communication_type_key
     * @param  bool $forceReload
     * @return array
     */
    public static function GetEventCommunicationTemplatesForZone(Zone $zone, string $communication_type_key='')
    {
        $configuration_key = 'event.email_templates';
        if ($communication_type_key){ // if $communication_type_key is empty, then all values are fetched
            $communication_type_key = self::EVENT_COMMUNICATION_TYPES[$communication_type_key] ?? '';
            $configuration_key .= '.' . $communication_type_key;
        }
        $configuration_vals = $zone->getZoneAttributesKeyVal($configuration_key) ?? array();

        return $configuration_vals;
    }


    public static function EventCompletionFollowup(int $lookback_days)
    {
        global $_COMPANY, $_ZONE;
        if ($_COMPANY || $_ZONE) {
            Logger::Log("Calling multi-tenant method from Company or Zone context:" . $_COMPANY->id(), Logger::SEVERITY['FATAL_ERROR']);
            return; // This job can only be executed if it is called from non company and non zone context. If company is set, exit it.
        }

        if ($lookback_days < 1) {
            return;
        }

        $companies = self::DBROGet("SELECT `companyid`,`subdomain` FROM `companies` WHERE companies.isactive=1");
        // Step 1: Iterate over active companies and zones
        foreach ($companies as $company) {
			$companyObj = Company::GetCompany($company['companyid']);
            if (!$companyObj) {
                continue;
            }

            // Step 2: Iterate over active Zones
            foreach ($companyObj->getZones() as $zoneArr) {

                if (
                    $zoneArr['isactive'] != 1 ||
                    ($zoneObj = $companyObj->getZone($zoneArr['zoneid'])) == null
                ) {
                    continue; // Skip inactive zones;
                }

                $past_events = self::DBROGet("SELECT `groupid`, `collaborating_groupids`, `eventid`, `start`, `end`, `eventtitle`, `is_event_reconciled`, `zoneid`, `companyid`, `event_contributors` FROM `events` WHERE `companyid`={$companyObj->id()} AND zoneid={$zoneObj->id()} AND `isactive`=1 AND (`end` BETWEEN NOW() - INTERVAL {$lookback_days} DAY AND NOW())");
                if (empty($past_events)) continue;


                // Temporarily set the $_COMPANY & _ZONE
                {

                    $_COMPANY = $companyObj;
                    $_ZONE = $zoneObj;
                    # Handle event reconciliation jobs
                    if (
                        $_COMPANY->getAppCustomization()['event']['reconciliation']['enabled'] &&
                        ($event_reconciliation_email_template = self::GetEventCommunicationTemplatesForZone($_ZONE, self::EVENT_COMMUNICATION_TYPES['reconciliation'])) &&
                        !empty($event_reconciliation_email_template['subject']) &&
                        !empty($event_reconciliation_email_template['body']) &&
                        !empty($event_reconciliation_email_template['trigger_days'])
                    ) {
                        $trigger_days = $event_reconciliation_email_template['trigger_days'];
                        $reconciliation_jobs = 0;

                        foreach ($past_events as $past_event) {
                            if ($past_event['is_event_reconciled'])
                                continue;

                            $delay = strtotime($past_event['end'] . ' UTC') + 86400 * $trigger_days - time();
                            if ($delay > 0) {
                                $reconciliation_jobs++;
                                $job = new EventJob($past_event['groupid'], $past_event['eventid']);
                                $job->delay = $delay;
                                $job->saveAsEventReconciliationFollowup($event_reconciliation_email_template['subject'], $event_reconciliation_email_template['body'], $past_event['event_contributors']);
                            }
                        }
                        Logger::LogInfo("EventCompletionFollowup - processed reconciliation emails for {$_COMPANY->val('subdomain')} -> {$_ZONE->val('zonename')} zone", ['target events' => count($past_events), 'reconciliation_jobs' => $reconciliation_jobs, 'trigger_days' => $trigger_days]);
                    }

                    # ... add blocks for other type of follow up messages

                    $_COMPANY = null;
                    $_ZONE = null;
                }
            }
        }

    }

    public function showPublishSeriesButton(array $sub_events)
    {
        global $_COMPANY, $_ZONE, $_USER;

        if (!$this->isSeriesEventsFormValidated()) {
            return false;
        }
        $isApprovalStatusApproved = true;
        if($_COMPANY->getAppCustomization()['event']['approvals']['enabled']){
            $isApprovalStatusApproved = false;
            if($approval = $this->getApprovalObject()){
                $isApprovalStatusApproved = $approval->isApprovalStatusApproved();
            }
        }
        $isEmailReviewRequired = $_COMPANY->getAppCustomization()['event']['require_email_review_before_publish'];
        $isSubEventsReadyToPublish = false;
        foreach($sub_events as $sevt) {
            if($isEmailReviewRequired ) {
                if($sevt->isDraft()) {
                    $isSubEventsReadyToPublish = false;
                    break;
                } elseif($sevt->isUnderReview()) {
                    $isSubEventsReadyToPublish =true;
                }
            } else {
                if($sevt->isDraft()) {
                    $isSubEventsReadyToPublish = true;
                    break;
                }
            }
        }
        $canPublish = $_USER->canPublishContentInGroupSomething($this->val('groupid'));
        return  $canPublish && $isApprovalStatusApproved && $isSubEventsReadyToPublish;

    }

    /**
     * @return array
     */
    public function listEventDependencies(): array
    {
        $dependencies = array();
        if ($this->getEventBudgetedDetail()) {
            $dependencies[] = 'expense entries';
        }

        if (!empty($this->getEventVolunteerRequests())){
            $dependencies[] = 'volunteers';
        }

        if (!empty($this->getEventSpeakers())){
            $dependencies[] = 'speakers';
        }

        if ($this->getAssociatedOrganization()) {
            $dependencies[] = 'organizations';
        }
        $approval = $this->getApprovalObject();
        if (!$this->isSeriesEventSub() && $approval && !$approval->isApprovalStatusDenied() && !$approval->isApprovalStatusCancelled()) {
            $dependencies[] = 'approval';
        }

        return $dependencies;
    }

    public static function GetMyEventsHavingScheduleId(DateTimeImmutable $event_start_date, DateTimeImmutable $event_end_date): array
    {
        global $_USER, $_COMPANY;

        $start_date_utc = $event_start_date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $end_date_utc = $event_end_date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        $teamEvents = self::DBROGet("
            SELECT      `events`.*
            FROM        `team_members`
            INNER JOIN  `teams` ON `team_members`.`teamid` = `teams`.`teamid`
            INNER JOIN  `events` ON `teams`.`teamid` = `events`.`teamid`
            WHERE       `team_members`.`userid` = {$_USER->id()}
            AND         `teams`.`isactive` = 1
            AND         `events`.`isactive` = 1
            AND         `events`.`companyid` = {$_COMPANY->id()}
            AND         `events`.`start` >= '{$start_date_utc}'
            AND         `events`.`start` <= '{$end_date_utc}'
            AND         `events`.`schedule_id` > 0
            AND         `events`.`teamid` > 0
        ");

        $supportEvents = self::DBROGet("
            SELECT      `events`.*
            FROM        `eventjoiners`
            INNER JOIN  `events` ON `eventjoiners`.`eventid` = `events`.`eventid`
            WHERE       `eventjoiners`.`userid` = {$_USER->id()}
            AND         `events`.`isactive` = 1
            AND         `events`.`companyid` = {$_COMPANY->id()}
            AND         `events`.`start` >= '{$start_date_utc}'
            AND         `events`.`start` <= '{$end_date_utc}'
            AND         `events`.`schedule_id` > 0
            AND         `events`.`teamid` = 0
        ");
        $events = array_merge($teamEvents, $supportEvents);
        usort($events, function($a, $b) {
            return $a['start'] <=> $b['start'];
        });
        return array_map(function (array $event) {
            return Event::Hydrate($event['eventid'], $event);
        }, $events);
    }

    public function isEventContributor()
    {
        global $_USER;
        if ($this->val('event_contributors')) {
            $event_contributors = explode(',',$this->val('event_contributors'));
            if (in_array($_USER->id,$event_contributors)) {
                return true;
            }
        }
        return false;
    }

    public function updateScheduleId(int $schedule_id)
    {
        global $_COMPANY;
        self::DBUpdate("UPDATE events set schedule_id={$schedule_id} WHERE companyid={$_COMPANY->id()} AND eventid={$this->id()}");
    }

    public function hasNoEventsOrIncompleteEventsInSeries()
    {
        $eventsInSeries = self::GetEventsInSeries($this->id());
        if (empty($eventsInSeries)) {
            return true;
        }

        foreach($eventsInSeries as $evt) {
            if ($evt->val('form_validated') == 0 ) {
                return true;
            }
        }
        return false;
    }

    public static function GetMySentSupportBookings(int $groupid)
    {   
        global $_COMPANY, $_USER;

        $events = self::DBROGet("SELECT * FROM `events` WHERE `companyid`={$_COMPANY->id()} AND  `groupid`={$groupid} AND `userid`={$_USER->id()} AND `eventclass`='booking' ");
        if (!empty($groupid)) {
            foreach($events as &$evt) {
                $evt['joiners'] = self::GetEventJoinersAsCSV($evt['eventid']);
            }
            unset($evt);
        }
        usort($events, function($a, $b) {
            return $a['start'] <=> $b['start'];
        });
        return $events;
    }

    public static function GetMyReceivedSupportBookings(int $groupid)
    {   
        global $_COMPANY, $_USER;

        $events = self::DBROGet("SELECT `events`.* FROM `events` JOIN  `eventjoiners` USING(`eventid`) WHERE `events`.`companyid`={$_COMPANY->id()} AND  `events`.`groupid`={$groupid} AND `eventjoiners`.`userid`={$_USER->id()} AND `events`.`userid` != {$_USER->id()} AND `events`.`eventclass`='booking' ");
        if (!empty($groupid)) {
            foreach($events as &$evt) {
                $evt['joiners'] = self::GetEventJoinersAsCSV($evt['eventid']);
            }
            unset($evt);
        }
        usort($events, function($a, $b) {
            return $a['start'] <=> $b['start'];
        });
        return $events;
    }

    public function publishEventToUsersImmediately (array $joinerUserIds, int $rsvp)
    {
        $group = Group::GetGroup($this->val('groupid'));
        $reminderTemplate = $group->getMeetingEmailTemplate('meeting_reminder_email_template');
        $reminder_days = isset($reminderTemplate['reminder_days']) ? (int)$reminderTemplate['reminder_days'] : null;
        $final_reminder_days = isset($reminderTemplate['final_reminder_days']) ? (int)$reminderTemplate['final_reminder_days'] : null;

        if ($reminder_days) {
            $eventStart = new DateTime($this->val('start'));
            $now = new DateTime();
            $interval = $now->diff($eventStart);
            $daysUntilEvent = (int)$interval->format('%a');
            if ($daysUntilEvent < ($reminder_days + 1)) {
                return false;
            }
        }

        $this->updateEventForSchedulePublishing(-1);
        $this->updatePublishToEmail(1);
        $ev = self::GetEventForPublishing($this->id, 1);
        if ($ev ?-> isActive()) {
            foreach ($joinerUserIds as $joinerUserId) {
                $ev->joinEvent($joinerUserId, $rsvp, 0, 1);
            }

            // --- Schedule batch reminder jobs ---
            $eventStartTimestamp = strtotime($ev->val('start'));
            
            // Cancel any existing reminder jobs before scheduling new ones
            if ($reminder_days || $final_reminder_days) {
                $cancelJob = new EventJob($group->id(), $ev->id());
                $cancelJob->cancelAsRemindType();
            }
            
            // Schedule regular reminder job if reminder_days is configured
            if ($reminder_days) {
                $job = new EventJob($group->id(), $ev->id());
                $job->scheduleBookingReminderBatchJob(
                    $reminderTemplate['subject'] ?? '',
                    $reminderTemplate['message'] ?? '',
                    $joinerUserIds,
                    $reminder_days,
                    $eventStartTimestamp,
                    $ev->val('web_conference_link')
                );
            }

            // Schedule final reminder job if final_reminder_days is configured
            if ($final_reminder_days) {
                $finalReminderJob = new EventJob($group->id(), $ev->id());
                $finalReminderJob->scheduleBookingReminderBatchJob(
                    $reminderTemplate['subject'] ?? '',
                    $reminderTemplate['message'] ?? '',
                    $joinerUserIds,
                    $final_reminder_days,
                    $eventStartTimestamp,
                    $ev->val('web_conference_link')
                );
            }

            return true;
        }
        return false;
    }

    public static function GetEventsByUserSchedule(string $groupids, int $startLimit=0, int $endLimit=0, string $searchKeyword='',bool $clearCache = false, string $orderBy ='', string $orderDirection = ''){
		global $_COMPANY,$_ZONE,$_USER;

        $rows = self::DBROGet("SELECT `events`.*, user_schedule.schedule_name, user_schedule.userid as supportUserId, `groups`.groupname FROM `events` JOIN user_schedule USING(schedule_id) LEFT JOIN `groups` USING(groupid)  WHERE `events`.`companyid`={$_COMPANY->id()} AND `events`.zoneid={$_ZONE->id()} AND `events`.`groupid` IN({$groupids}) AND `events`.`teamid`=0 AND user_schedule.isactive=1");
		
		$searchKeyword = trim($searchKeyword);
		if (!empty($searchKeyword)) { // Search
			$rows = array_values(array_filter($rows, function($item) use ($searchKeyword) {
				return stripos($item['eventtitle'], $searchKeyword) !== false || stripos($item['schedule_name'], $searchKeyword) !== false;
			}));
		}
        // Order Data
        usort($rows, function($a, $b) use ($orderBy, $orderDirection) {
            if ($orderBy == 'eventtitle') {

                if ($orderDirection == "ASC") {
                    return $a['eventtitle'] <=> $b['eventtitle'];
                } else {
                    return $b['eventtitle'] <=> $a['eventtitle'];
                }

            } elseif ($orderBy == 'schedule_name') {
                if ($orderDirection == "ASC") {
                    return $a['schedule_name'] <=> $b['schedule_name'];
                } else {
                    return $b['schedule_name'] <=> $a['schedule_name'];
                }

            } elseif ($orderBy == 'start') {
                if ($orderDirection == "ASC") {
                    return $a['start'] <=> $b['start'];
                } else {
                    return $b['start'] <=> $a['start'];
                }
            } else {
                return $b['eventid'] <=> $a['eventid'];
            }
        });

        // Paginate data
        if ($endLimit) {
            $page_number = intval($startLimit / $endLimit);
            $rowsChunk = array_chunk($rows, $endLimit)[$page_number];
        } else {
            $rowsChunk = $rows; // All rows
        }

        return [
            count($rows),
            $rowsChunk
        ];
	}

    public static function GetEventByScheduledIdAndStartTime(int $schedule_id, string $start_time){
        $eventSchedule = self::DBGetPS('SELECT `user_schedule_events`.`eventid` FROM `user_schedule_events` JOIN `events` USING(eventid) WHERE `user_schedule_events`.`schedule_id`=? AND `user_schedule_events`.`start_time_utc`=? AND `events`.`isactive`IN(1,2,3,4,5)',
        'is', $schedule_id, $start_time);
        if (!empty($eventSchedule)) {
            return  self::GetEvent($eventSchedule[0]['eventid']);
        }
        return null;
    }

    public function createEventScheduleData(int $schedule_id, string $start_date): int
    {
        return self::DBMutatePS("INSERT INTO user_schedule_events (schedule_id, start_time_utc, eventid) VALUES (?, ?, ?)", 'isi', $schedule_id, $start_date, $this->id());
    }
    public function updateOrCreateEventScheduleData(int $schedule_id, string $start_date): int
    {
        $curr_row = self::DBGet("SELECT * FROM user_schedule_events WHERE eventid={$this->id()}");
        if (empty($curr_row)) {
            return $this->createEventScheduleData($schedule_id, $start_date);
        }
        return self::DBMutatePS("UPDATE user_schedule_events SET schedule_id=?, start_time_utc=?, modifiedon=NOW() WHERE eventid=?", 'isi', $schedule_id, $start_date, $this->id());
    }

    public function deleteEventScheduleData(): int
    {
        return self::DBMutate("DELETE FROM user_schedule_events WHERE eventid={$this->id()}");
    }

    public function updateEventAttributes(string $attributes)
    {
        global $_COMPANY;
        $attributes = ($attributes == '[]') ? '{}' : $attributes; // Convert empty to object;
        return self::DBUpdatePS("UPDATE `events` SET `attributes`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `eventid`=?", 'xii', $attributes, $_COMPANY->id(), $this->id);
    }

    public function updateEventBookingResolutionData(string $resolution, string $comment)
    {
        global $_COMPANY, $_USER;

        $key = self::EVENT_BOOKINGS['booking_resolution_data'];
        $attributes = array();
        $oldattributes = $this->val('attributes');

        if ($oldattributes) {
            $attributes = json_decode($oldattributes, true);
        }
        $attributes[$key]['resolution'] = $resolution;
        $attributes[$key]['comment'] = $comment;
        $attributes[$key]['datatime_utc'] = date("Y-m-d H:i:s");
        $attributes[$key]['userid'] = $_USER->id();
        
        $attributes = json_encode($attributes);

        return $this->updateEventAttributes($attributes);
    }

    public function removeOldJoinersAndNotify(array $keepUserIds) {
        $eventJoiners = explode(',',self::GetEventJoinersAsCSV($this->id()));
        $removeJoinerIds = array_diff($eventJoiners, $keepUserIds);
        foreach ($removeJoinerIds as $joinerId) {
            // Send cancellation email
            $this->joinEvent($joinerId, self::RSVP_TYPE['RSVP_NO'], 1,1,0);
            // And delete from the joiners table
            self::DBMutate("DELETE FROM eventjoiners WHERE eventid={$this->id()} AND userid={$joinerId}");
        }
        return true;
    }

    public function getBookingStatus()
    {
        if ($this->val('isactive') == 0) {
            return array('resolution'=>'Canceled', 'comment'=>$this->val('cancel_reason'),'datatime_utc'=>$this->val('canceledon'));
        } else {
            $key = self::EVENT_BOOKINGS['booking_resolution_data'];
            $attributes = $this->val('attributes');

            if ($attributes) {
                $attributes = json_decode($attributes, true) ?? array();
                if (array_key_exists($key, $attributes)) {
                    return $attributes[$key];
                }
            }
            return array('resolution'=>'Scheduled', 'comment'=>'','datatime_utc'=>$this->val('publishdate'));
        }
    }

    public function syncEventCreator(int $eventCreatorId)
    {
        global $_COMPANY;
        if ($this->val('userid')!=$eventCreatorId){
            return self::DBUpdate("UPDATE `events` SET `userid`={$eventCreatorId},`modifiedon`=NOW() WHERE `companyid`={$_COMPANY->id()} AND `eventid`={$this->id}");
        }
        return;
    }

}
