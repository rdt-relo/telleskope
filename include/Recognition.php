<?php

// Do no use require_once as this class is included in Company.php.

class Recognition extends Teleskope {

	protected $attachments;
	protected $comments;

	protected function __construct($id,$cid,$fields) {
		parent::__construct($id,$cid,$fields);
		//declaring it protected so that no one can create it outside this class.
		$this->attachments = NULL;
	}

	const RECOGNITION_TYPES = array (
      	"all"=>0,
        "recognize_a_colleague"=>1,
        "recognize_my_self"=>2, 
        "received_recognitions"=>3
    );

    // Add traits right after the constructor
    public static function GetTopicType():string {return self::TOPIC_TYPES['RECOGNITION'];}
    use TopicLikeTrait;
    use TopicCommentTrait;
	use TopicCustomFieldsTrait;

    /**
     * Gets the Recognition object for the matching id.
     * @param int $id of the Recognition object to fetch
     * @return Recognition|null
     */
	public static function GetRecognition(int $id, bool $allow_cross_zone_fetch = false) {
		$obj = null;
        global $_COMPANY,$_ZONE; /* @var Company $_COMPANY */
			//  AND isactive=1
		$r1 = self::DBGet("SELECT * FROM `recognitions` WHERE companyid = '{$_COMPANY->id()}' AND  recognitionid='{$id}' ");

		if (count($r1)) {
			if (!$allow_cross_zone_fetch && ((int) $r1[0]['zoneid'] !== $_ZONE->id())) {
				return null;
			}
			$obj = new Recognition($id, $_COMPANY->id(), $r1[0]);
		} else {
			Logger::Log("Recognition: GetRecognition({$id}) failed. Context (Company={$_COMPANY->id()})", Logger::SEVERITY['INFO']);
		}
		return $obj;
	}

	public static function ConvertDBRecToRecognition (array $rec) {
            global $_COMPANY;
            $obj = null;
            $p = (int)$rec['recognitionid'];
            $c = (int)$rec['companyid'];
            if ($p && $c && $c === $_COMPANY->id())
                $obj = new Recognition($p, $c, $rec);
            return $obj;
	}


	public function inactivateIt() {
		global $_COMPANY,$_ZONE;
		$status = self::STATUS_INACTIVE;
		$retval = self::DBMutate("UPDATE `recognitions` SET isactive='{$status}' WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND recognitionid='{$this->id}'");

		Points::HandleTrigger('DELETE_RECOGNITION', [
			'recognitionId' => $this->id(),
		]);

		return $retval;
	}

	public function deleteIt() {
		global $_COMPANY,$_ZONE;
        self::DeleteAllComments_2($this->id);
        self::DeleteAllLikes($this->id);
        return self::DBMutate("DELETE FROM `recognitions` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND recognitionid='{$this->id}'");
	}

	public function __toString() {
		return "Recognition ". parent::__toString();
	}

    /**
     * GET Group level RECOGNITION
     */
    public static function GetGroupRecognitions(int $groupid, int $includeGlobal = 0, int $page = 0) {
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
		

		$limitCondition = "";
		if ($page){
			$start 		=	($page - 1)*10;
			$end  		=	10;
			$limitCondition  = "LIMIT $start, $end";
		}
		
		return self::DBGet("SELECT * FROM recognitions WHERE companyid = '{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' {$groupCondition} AND `isactive`='".self::STATUS_ACTIVE."' ORDER BY modifiedon DESC, recognitionid DESC $limitCondition");

	}

	public static function AddOrUpdateRecognition(int $groupid, int $recognizedto, string $recognitiondate,string $description,string $attributes,string $recognizedbyTeamName,int $recognizebyID,string $behalfOf,int $recognitionId = 0, string $custom_fields = '') {
        global $_COMPANY;
        global $_ZONE;
		global $_USER;
 
		if($behalfOf == "Team")
		{
		   $recognizebyID = 0;
		}else{
			$recognizedbyTeamName = "";
		}
        
		if ($recognitionId > 0){
			ContentModerator::CheckBlockedWords($description);
			// recognizedto = $recognizedto in this case group leads with publish capability can be update recognizedto ID 
			self::DBUpdatePS("UPDATE `recognitions` SET `attributes`=?,`recognitiondate`=?,`description`=?,`recognizedby_name`=?,`modifiedon`=NOW(), `custom_fields` = ? WHERE `recognitionid`=? AND `companyid`=?",'xxxxxii',$attributes, $recognitiondate,$description,$recognizedbyTeamName, $custom_fields, $recognitionId,$_COMPANY->id());
			return $recognitionId;
        } else {
			ContentModerator::CheckBlockedWords($description);

			$recognition_id = self::DBInsertPS("INSERT INTO `recognitions`(`attributes`,`companyid`, `zoneid`, `createdby`, `groupid`, `recognizedby`, `recognizedto`, `recognitiondate`, `description`,`recognizedby_name`,`createdon`, `modifiedon`, `isactive`, `custom_fields`) VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW(),1,?)",'xiiiiiixxxx',$attributes,$_COMPANY->id(),$_ZONE->id(),$_USER->id(),$groupid,$recognizebyID,$recognizedto,$recognitiondate,$description,$recognizedbyTeamName, $custom_fields);
		
			Points::HandleTrigger('RECOGNITION_CREATED', [
			    'recognitionId' => $recognition_id,
			]);

			$user = User::GetUser($recognizedto);
            $LoggedUser = User::GetUser($_USER->id());
            $FromUserName = $LoggedUser->getFullName();
			$FromEmail = $LoggedUser->val('email');
            $toEmail = $user->val('email');
            $toFullName = $user->getFullName();            
            if ($recognition_id) { 
                $temp1 = EmailHelper::GetEmailTemplateForRecognitionPersonRecognized($FromUserName,$description,$behalfOf,$recognizedbyTeamName,$recognizedto);
				 $_COMPANY->emailSend2($FromUserName, $toEmail, $temp1['subject'], $temp1['message'], $_ZONE->val('app_type'), '');
				if($recognizedto != $_USER->id()){
					$temp2 = EmailHelper::GetEmailTemplateForRecognitionPersonRecognizing($toFullName,$description,$behalfOf,$recognizedbyTeamName); 
					$_COMPANY->emailSend2($FromUserName, $FromEmail, $temp2['subject'], $temp2['message'], $_ZONE->val('app_type'), '');
				  }
            }   

			return $recognition_id;
		}
   	}
	
	public static function GetRecognitions(int $groupid, int $recognition_type, int $activeOnly, int $year, string $searchKeyword, string $orderBy, string $orderDirection, int $startLimit, int $endLimit,int $returnTotalRowsCountOnly = 0) {
		global $_COMPANY, $_ZONE, $_USER;

		$searchKeyword = raw2clean($searchKeyword);
		// Remap order by valid values are ['person_recognized','person_recognizing','recognition_date'];
        if ($orderBy == 'person_recognized') {
            $orderBy = 'b.firstname';
        } elseif ($orderBy == 'person_recognizing') {
            $orderBy = 'c.firstname';
        } else {
            $orderBy = 'a.modifiedon';
        }

		$orderDirection = strtoupper($orderDirection) === 'ASC' ? 'ASC' : 'DESC';

		$isactiveCondition = "";
		if ($activeOnly){ // Only Active
			$isactiveCondition = " AND a.isactive='".Self::STATUS_ACTIVE."'";
		} 
		$searchCondition = "";
		if ($searchKeyword){
			$searchCondition = " AND (b.firstname LIKE '".$searchKeyword."%' OR b.lastname LIKE '".$searchKeyword."%' OR b.email LIKE '".$searchKeyword."%' OR c.firstname LIKE '".$searchKeyword."%' OR c.lastname LIKE '".$searchKeyword."%' OR c.email LIKE '".$searchKeyword."%')";
		}

		$typeCondtion = "";
        if ($recognition_type == Recognition::RECOGNITION_TYPES['recognize_a_colleague']) {
            $typeCondtion = " AND `createdby`='{$_USER->id()}'";
        } elseif($recognition_type == Recognition::RECOGNITION_TYPES['recognize_my_self']){
            $typeCondtion = " AND `createdby`='{$_USER->id()}' AND `recognizedto`='{$_USER->id()}'";
        } elseif($recognition_type == Recognition::RECOGNITION_TYPES['received_recognitions']){
            $typeCondtion = " AND `recognizedto`='{$_USER->id()}'";
        }

		$limitCondition = "";
		$yearCondition = "";
		$orderByWithDirection =  $orderBy ? 'ORDER BY '.$orderBy.' '.$orderDirection : "ORDER BY a.`modifiedon ASC";
		
		
		$limitCondition = " limit {$startLimit},{$endLimit}";	
		if ($year){
			$yearCondition = " AND YEAR(a.`recognitiondate`)={$year}";
			if($year > date('Y')){
				$yearCondition = " AND YEAR(a.`recognitiondate`) >='".$year."'";
			}
		}
		
		if ($returnTotalRowsCountOnly){
			return self::DBGet("SELECT count(1) as totalRows FROM recognitions a LEFT JOIN users b ON b.userid=a.createdby JOIN `users` as c ON c.userid=a.recognizedto WHERE a.companyid='{$_COMPANY->id()}' AND ( a.zoneid='{$_ZONE->id()}' AND a.`groupid`={$groupid} {$isactiveCondition} {$yearCondition} {$searchCondition })")[0]['totalRows'];
		} else {
			return self::DBGet("SELECT a.*,IFNULL(b.firstname,'Deleted') as firstname_by,IFNULL(b.lastname,'User') as lastname_by,IFNULL(b.jobtitle,'') as jobtitle_by,IFNULL(b.picture,'') as picture_by,IFNULL(b.email,'') as email_by,IFNULL(c.firstname,'Deleted') as firstname_to,IFNULL(c.lastname,'User') as lastname_to,IFNULL(c.jobtitle,'') as jobtitle_to,IFNULL(c.picture,'') as picture_to,IFNULL(c.email,'') as email_to FROM recognitions a LEFT JOIN users b ON b.userid=a.createdby LEFT JOIN `users` as c ON c.userid=a.recognizedto WHERE a.companyid='{$_COMPANY->id()}' AND ( a.zoneid='{$_ZONE->id()}' AND a.`groupid`={$groupid} {$isactiveCondition} {$yearCondition} {$searchCondition} {$typeCondtion}) {$orderByWithDirection} {$limitCondition}");
		}
	}

	public static function GetCustomName(bool $defaultNamePlural = false){
		global $_COMPANY, $_ZONE, $_USER;
		if ($defaultNamePlural){
			$name = gettext('Recognitions');
		} else {
			$name = gettext('Recognition');
		}

		$lang = $_USER->val('language') ?? 'en';
		$altNames = $_COMPANY->getAppCustomization()['recognition']['alt_name'] ?? array();

		if(!empty($altNames) && array_key_exists($lang,$altNames)){

			$altName  = $altNames[$lang];
			if (!empty($altName)){
				return $altName;
			}
		}
		return $name;

	}

}
