<?php
require_once __DIR__ .'/dbfunctions.php';
require_once __DIR__ .'/mysql/MySQLTrait.php';
require_once __DIR__ .'/libs/vendor/autoload.php';

date_default_timezone_set("UTC");

class Teleskope {

	# Common states
	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_DRAFT = 2;
    const STATUS_UNDER_REVIEW = 3;
    const STATUS_UNDER_APPROVAL = 4;
    const STATUS_AWAITING = 5;
	const STATUS_ARCHIVE = 10;
	const STATUS_PURGE = 100;
    const STATUS_WIPE_CLEAN = 101; //When user closes account to clean all data
    const STATUS_BLOCKED = 102; // e.g. Admin blocked user
    const STATUS_COMPLETE = 110;
    const STATUS_INCOMPLETE = 109;
    const STATUS_PAUSED = 108;

    const FILE_FORMAT_CSV = 1;
    const FILE_FORMAT_TSV = 2;
    const FILE_FORMAT_XLS = 3;
    const FILE_FORMAT_JSON = 4;

    const TOPIC_TYPES = array (
        'POST'          =>  'POS',
        'EVENT'         =>  'EVT',
        'ALBUM_MEDIA'   =>  'ALM',
        'ALBUM_MEDIA_2' => 'ALBMED',
        'NEWSLETTER'    =>  'NWS',
        'TEAMTASKS'     =>  'TSK', // It represents Team_todo, Touch points and Feedback of Teams
        'COMMENT'       =>  'CMT',
        'RESOURCE'      =>  'RES',
        'DISCUSSION'    =>  'DIS',
        'RECOGNITION'   =>  'REC',
        'EXPENSE_ENTRY' =>  'EXP',
        'BUDGET_REQUEST' => 'BRQ',
        'EPHEMERAL_TOPIC'=> 'TMP',
        "TEAMS"   => 'TMS', # Used for Team Messages
        'APPROVAL' => 'APR',
        'ZONE' => 'ZON',
        'MESSAGE' => 'MSG',
        'APPROVAL_LOG' => 'APRLOG',
        'APPROVAL_TASK' => 'APRTSK',
        'ORGANIZATION' => 'ORG',
        'EVENT_SPEAKER' => 'EVTSPK',
        'EVENT_VOLUNTEER' => 'EVTVOL',
        'POINTS_PROGRAM' => 'PTPRG',
        'SURVEY' => 'SUR'
    );

    const TOPIC_TYPES_ENGLISH = array (
        'POS' => 'Announcement',
        'EVT' => 'Event',
        'ALM' => 'Media',
        'ALBMED' => 'Album Media',
        'NWS' => 'Newsletter',
        'TSK' => 'Task',
        'CMT' => 'Comment',
        'RES' => 'Resource',
        'DIS' => 'Discussion',
        'REC' => 'Recognition',
        'EXP' => 'Expense Entry',
        'BRQ' => 'Budget Request',
        'TMP' => '',
        'APR' => 'Approval',
        'ZON' => 'Zone',
        'MESSAGE' => 'Message',
        'APRLOG' => 'Approval Log',
        'APRTSK' => 'Approval Task',
        'ORG' => 'Organization',
        'EVTSPK' => 'Event Speaker',
        'EVTVOL' => 'Event Volunteer',
        'PTPRG' => 'Points Program',
        'SUR' => 'Survey',
    );

    const TOPIC_TYPE_CLASS_MAP = [
        'POS' => 'Post',
        'EVT' => 'Event',
        'EXP' => 'ExpenseEntry',
        'BRQ' => 'BudgetRequest',
        'TMP' => 'EphemeralTopic',
        'TSK' => 'TeamTask',
        'DIS' => 'Discussion',
        'NWS' => 'Newsletter',
        'APR' => 'Approval',
        'ZON' => 'Zone',
        'MSG' => 'Message',
        'TMS' => 'Team',
        'APRLOG' => 'TopicApprovalLog',
        'APRTSK' => 'TopicApprovalTask',
        'ORG' => 'Organization',
        'EVTSPK' => 'EventSpeaker',
        'ALM' => 'Album',
        'ALBMED' => 'AlbumMedia',
        'CMT' => 'Comment',
        'REC' => 'Recognition',
        'EVTVOL' => 'EventVolunteer',
        'PTPRG' => 'PointsProgram',
        'SUR' => 'Survey2',
    ];


    const PUSH_NOTIFICATIONS_STATUS = array(
        'POST' => '1',
        'EVENT' => '2',
        'EVENT_CANCELED'=> '2.1',
        'DISCUSSION' => '3',
        'POST_COMMENTS'=> '4',
        'NEWSLETTER' => '5',
        'USER_INBOX' => '6',
		'TEAM' => '7',
		'TEAM_ACTION_ITEAM' => '7.1',
		'TEAM_TOUCHPOINT' => '7.2',
		'TEAM_FEEDBACK'	=> '7.3',
		'TEAM_WEEKLYDIGEST'	=> '7.4',
        'TEAM_INVITE' => '7.5'

	);
    const TOPIC_TYPE_CONFIGURATION_NAME_MAP = [
        'POS' => 'post',
        'EVT' => 'event',
        'NWS' => 'newsletters',
        'SUR' => 'surveys',
    ];
    protected $id; //Object identifier
	protected $cid; //Companyid
	protected $fields;
	protected $timestamp;
    protected bool $_is_hard_deleted;

    static array $memoize_cache;

    // Add MySql Trait and define the conditions required
    use MySQLTrait;
    private static function GetRWConnection() : mysqli { return GlobalGetDBConnection();}
    private static function GetROConnection() : mysqli{ return GlobalGetDBROConnection();}
    // Trait setup complete

	protected function __construct(int $id,int $cid,array $fields) {
		$this->id = $id;
		$this->cid = $cid;
		$this->fields = $fields;
		$this->timestamp = time();
	}

	public function id() :int { return (int)$this->id; }

    public function encodedId(): string
    {
        global $_COMPANY;
        return $_COMPANY->encodeId($this->id());
    }

	public function cid() :int { return (int)$this->cid; }

	public function isActive() {
	    return ($this->fields['isactive'] == self::STATUS_ACTIVE);
	}

    public function isInactive() {
        return ($this->fields['isactive'] == self::STATUS_INACTIVE);
    }

    public function isDeleted() {
        return ($this->fields['isactive'] == self::STATUS_PURGE);
    }

    public function isDraft() {
        return ($this->val('isactive') == self::STATUS_DRAFT);
    }

    public function isPublished() {
        return ($this->val('isactive') == self::STATUS_ACTIVE);
    }

    public function isCancelled() {
        return ($this->val('isactive') == self::STATUS_INACTIVE);
    }

    public function isUnderReview() {
        return ($this->val('isactive') == self::STATUS_UNDER_REVIEW);
    }

    public function isAwaiting() {
        return ($this->val('isactive') == self::STATUS_AWAITING);
    }

    public function isComplete() {
        return ($this->val('isactive') == self::STATUS_COMPLETE);
    }

    public function isIncomplete() {
        return ($this->val('isactive') == self::STATUS_INCOMPLETE);
    }
    public function isPaused() {
        return ($this->val('isactive') == self::STATUS_PAUSED);
    }

	public function __toString() {
		return '';//"{".'"id":"'.$this->id.'", "cid":"'.$this->cid.'", "fields":'.json_encode($this->fields)."}";
	}

    protected static function CacheSet($key, $val) {
        $include_file = rtrim(sys_get_temp_dir(),'/').'/'.$key;
        $val = var_export($val, true);
        // Write to temp file first to ensure atomicity
        $tmp = tempnam(sys_get_temp_dir(),$key);
        file_put_contents($tmp, '<?php $val = ' . $val . ';', LOCK_EX);
        $new_name = $include_file;
        rename($tmp, $new_name);
        if (extension_loaded('Zend OPcache'))
            opcache_invalidate($new_name);
    }

    protected static function CacheGet($key) {
        $include_file = rtrim(sys_get_temp_dir(),'/').'/'.$key;
        if (file_exists($include_file)) {
            @include $include_file;
            return isset($val) ? $val : null;
        }
        return null;
    }

    public static function prepareDataForExcelCell($str) {
	    $str = html_entity_decode($str,ENT_COMPAT | ENT_HTML401, 'ISO-8859-15');
		$str = preg_replace("/\t/", "\\t", $str);
		$str = preg_replace("/\r?\n/", "\\n", $str);
		if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
		return $str;
    }

    /**
     * @param $format
     * @param $utcDateTime
     * @param $timezone
     * @param $locale
     * @return false|string
     * @throws Exception
     */
	public static function covertUTCtoLocalDateTimeZone($format, $utcDateTime, $timezone, $locale = null){
		if(empty($timezone)){
			$timezone	=	'UTC';
		}
        if (null === $locale) {
            $locale = \Locale::getDefault();
        }

        $dt = new DateTime($utcDateTime, new DateTimeZone('UTC'));
        $tz = new DateTimeZone($timezone);
        $dt->setTimezone($tz);

		if (null !==$locale && $locale != 'en'){
            $intlDateFormat = \IntlDateFormatter::FULL;
            $intlTimeFormat = \IntlDateFormatter::FULL;
            $intlFormat = self::ConvertDatetimePatternToICUPattern($format);

            $currentLocale = \Locale::getDefault();
            \Locale::setDefault($locale);
            $intlDateFormatter = new \IntlDateFormatter($locale, $intlDateFormat, $intlTimeFormat, $tz);
            if (null !== $intlFormat){
                $intlDateFormatter->setPattern($intlFormat);
            }
            $result = $intlDateFormatter->format($dt);
            \Locale::setDefault($currentLocale);
            return $result;
		} else {
			return $dt->format($format);
		}
	}

    public static function ConvertDatetimePatternToICUPattern($patterns){
		$patterns = (preg_split(' /([-\s:\s,\s(\s)])/', $patterns,-1,PREG_SPLIT_DELIM_CAPTURE));
		$icuFormatArray = array();
		foreach ($patterns as $index => $val) {
			switch ($val) {
				case 'Y':
					$icuFormatArray[$val] = 'y';
					break;
				case 'y':
					$icuFormatArray[$val] = 'yy';
					break;
				case 'M':
					$icuFormatArray[$val] = 'MMM';
					break;
				case 'm':
					$icuFormatArray[$val] = 'MM';
					break;
				case 'F':
					$icuFormatArray[$val] = 'MMMM';
					break;
				case 'D':
					$icuFormatArray[$val] = 'E';
					break;
				case 'd':
					$icuFormatArray[$val] = 'dd';
					break;
				case 'j':
					$icuFormatArray[$val] = 'd';
					break;
				case 'l':
					$icuFormatArray[$val] = 'EEEE';
					break;
				case 'H':
					 $icuFormatArray[$val] = 'HH';
					 break;
				case 'h':
					$icuFormatArray[$val] = 'hh';
					break;
				case 'g':
					$icuFormatArray[$val] = 'hh';
					break;
				case 'i':
					$icuFormatArray[$val] = 'mm';
					break;
				case 's':
					$icuFormatArray[$val] = 'ss';
					break;
				case 'a':
					$icuFormatArray[$val] = 'a';
					break;
				case 'A':
					$icuFormatArray[$val] = 'a';
					break;
				case 'T':
					$icuFormatArray[$val] = 'zzzz';
					break;
				case 'P':
                    $icuFormatArray[$val] = 'O';
					break;
			}
		}
		$icuFormatFinalArray = str_replace(array_keys($icuFormatArray), array_values($icuFormatArray), $patterns);
		$icuPatterns = null;
		if (!empty($icuFormatFinalArray)){
			$icuPatterns = implode('',$icuFormatFinalArray);
		}
		return $icuPatterns;
	}


	/**
	 * Get header Authorization
	 **/
	public static function GetAuthorizationHeader(){
		$headers = null;
		if (isset($_SERVER['Authorization'])) {
			$headers = trim($_SERVER["Authorization"]);
		}
		else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
			$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		} elseif (function_exists('apache_request_headers')) {
			$requestHeaders = apache_request_headers();
			// Server-side fix for bug in old Android versions (don't support capitalization for Authorization)
			$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
			if (isset($requestHeaders['Authorization'])) {
				$headers = trim($requestHeaders['Authorization']);
			}
		}
		return $headers;
	}

	/**
	* get access token from header
	* */
	public static function GetBearerToken() {
		$headers = self::GetAuthorizationHeader();
		// HEADER: Get the access token from the header
		if (!empty($headers)) {
			if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
				return $matches[1];
			}
		}
		return null;
	}

    public function toArray(): array
    {
        $fields = $this->fields;

        foreach (($this->appends ?? []) as $fieldname) {
            $getter_fn = 'get' . Str::ConvertSnakeCaseToCamelCase($fieldname);
            $fields[$fieldname] = $this->{$getter_fn}();
        }

        return $fields;
    }

    /**
     * Create a runtime object of the called class for a given id and fields.
     * Require global $_COMPANY as the object will be created in the $_COMPANY context.
     * @param int $id
     * @param array $fields
     * @param ...$extra Added this for Approval object where we pass the topicObj as the 4th parameter
     * @return static
     */
    public static function Hydrate(int $id, array $fields, ...$extra): Static
    {
        global $_COMPANY;
        return new Static($id, $_COMPANY->id(), $fields, ...$extra);
    }

    public function setField(string $field, $value): Static
    {
        $this->fields[$field] = $value;
        return $this;
    }

    public function getZoneAwareUrlBase(string $context = 'app'): string
    {
        if (!$this->val('zoneid')) {
            return '';
        }

        return Url::GetZoneAwareUrlBase($this->val('zoneid'), $context);
    }

    /**
     * Adds a Audit Log statement for the lifecycle operation.
     * @param string $lifecycle_operation - should be one of the following 'create','update','delete','state_change','failed_fetch'
     * @param string $object_name
     * @param int $object_id
     * @param int $object_version
     * @param array|null $other_details
     * @return void
     */
    protected static function LogObjectLifecycleAudit (string $lifecycle_operation, string $object_name, int $object_id, int $object_version, ?array $other_details=null)
    {
        if (!in_array($lifecycle_operation, ['create','update','delete','state_change','failed_fetch'])) {
            Logger::Log("Invalid lifecycle operation {$lifecycle_operation}",Logger::SEVERITY['FATAL_ERROR']);
        }

        $other_details = $other_details ?? [];

        Logger::AuditLog('lifecycle', [
            'oper' => $lifecycle_operation,
            'obj' => $object_name,
            'oid' => $object_id,
            'ver' => $object_version
        ] + $other_details);
    }

    public function getTypesenseId(): string
    {
        return Typesense::GetDocumentId(get_class($this), $this->id());
    }

    public function getTypesenseDocumentType(): string
    {
        return Typesense::GetDocumentType(get_class($this));
    }

    public static function IsTypesenseDocument(): bool
    {
        return in_array(static::class, array_column(TypesenseDocumentType::cases(), 'name'));
    }

    public function refreshTypesenseDocument(): void
    {
        try {
            if ($this->searchable()) {
                Typesense::UploadModel($this);
            } else {
                Typesense::DeleteDocument($this->getTypesenseId());
            }
        } catch (Throwable $e) {
            Logger::LogException($e, [
                'model_class' => get_class($this),
                'model_id' => $this->id(),
            ]);
        }
    }

    public function postCreate(): void
    {
        if (self::IsTypesenseDocument()) {
            $this->refreshTypesenseDocument();
        }
    }

    public function postUpdate(): void
    {
        if (self::IsTypesenseDocument()) {
            $this->refreshTypesenseDocument();
        }
    }

    public static function PostDelete(int $id): void
    {
        try {
            if (self::IsTypesenseDocument()) {
                $document_id = Typesense::GetDocumentId(static::class, $id);
                Typesense::DeleteDocument($document_id);
            }
        } catch (Throwable $e) {
            Logger::LogException($e, [
                'model_class' => static::class,
                'model_id' => $id,
            ]);
        }
    }

    public function searchable(): bool
    {
        if (!static::IsTypesenseDocument()) {
            return false;
        }

        $this->refresh();

        if ($this->isHardDeleted()) {
            return false;
        }

        return $this->isActive();
    }

    public static function GetTopicObj(string $topictype, int $topicid): ?Teleskope
    {
        $topic_class = self::TOPIC_TYPE_CLASS_MAP[$topictype];

        if (!$topic_class) return null;

        $getter_fn_name = 'Get' . $topic_class;
        return call_user_func([$topic_class, $getter_fn_name], $topicid, true);
    }

    public function getCurrentTopicType(): string
    {
        return call_user_func([static::class, 'GetTopicType']);
    }

    public static function CreateEphemeralTopic(): EphemeralTopic
    {
        return EphemeralTopic::CreateNewEphemeralTopic(static::GetTopicType());
    }

    public function refresh(): static
    {
        $fresh = static::GetTopicObj($this->getCurrentTopicType(), $this->id());

        if (is_null($fresh)) {
            $this->_is_hard_deleted = true;
            return $this;
        }

        $this->fields = $fresh->toArray();
        $this->_is_hard_deleted = false;
        return $this;
    }

    public function isHardDeleted(): bool
    {
        if ($this->_is_hard_deleted) {
            return true;
        }

        $this->refresh();

        return $this->_is_hard_deleted;
    }

    public function isSoftDeleted(): bool
    {
        if ($this->isDeleted()) {
            return true;
        }

        switch ($this->getCurrentTopicType()) {
            case 'POS':
            case 'EVT':
                return $this->isInactive();
        }

        return false;
    }
}
