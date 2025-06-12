<?php

class Survey2 extends Teleskope
{
    const SURVEY_TYPE = array (
        'GROUP_MEMBER' => 1,
        'EVENT_JOINER' => 2,
        'ZONE_MEMBER' => 3,
        'TEAM_MEMBER' => 4,
    );

    const SURVEY_TRIGGER = array (
        'ON_JOIN' => 1,
        'ON_LEAVE' => 2,
        'ON_LOGIN' => 3,
        'FOLLOWUP' => 4,
        'LINK' => 127
    );
    private $is_action_disabled_due_to_approval_process = NULL;

    use TopicApprovalTrait;
    use TopicAttachmentTrait;

    /**
     * Creates a survey record
     * @param string $surveyname
     * @param int $groupid
     * @param int $chapterid
     * @param int $channelid
     * @param int $surveytype
     * @param int $surveytrigger
     * @param bool $anonymous
     * @param string $survey_json
     * @param string $options
     * @return int|string
     */
    protected static function _CreateNewSurvey(string $surveyname, int $groupid, int $chapterid, int $channelid, int $surveytype, int $surveytrigger, bool $anonymous, string $survey_json, string $options = '',int $is_required = 0,int $allow_multiple=0)
    {
        global $_COMPANY, $_ZONE, $_USER;
        
        $retVal = self::DBInsertPS("INSERT INTO surveys_v2  (companyid, zoneid,surveyname, groupid, chapterid, channelid, surveytype, surveysubtype, `anonymous`, survey_json, createdby, options_json, `is_required`,`allow_multiple`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            'iisiiiiiixixii',
            $_COMPANY->id(),$_ZONE->id(),$surveyname,$groupid,$chapterid,$channelid,$surveytype,$surveytrigger,$anonymous,$survey_json,$_USER->id(),$options,$is_required,$allow_multiple);
        
        if ($retVal) {			
            self::LogObjectLifecycleAudit('create', 'survey', $retVal, 1); 
        }
        return $retVal;
    }

    protected static function _GetSurveyRec(int $surveyid):array
    {
        global $_COMPANY, $_ZONE;
        $rows = self::DBGet("SELECT * FROM surveys_v2 WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND surveyid={$surveyid}");
        if (count($rows)) {
            return $rows[0];
        }
        return array();
    }

    /**
     * @param int $groupid
     * @param int $chapterid
     * @param int $channelid
     * @param int $surveytype
     * @param int $surveytrigger
     * @return array associative array of surveys matching the scope, type and trigger are returned.
     */
    protected static function _GetSurveyRecsMatchingScopeAndType(int $groupid, int $chapterid, int $channelid, int $surveytype = 0, int $surveytrigger=0):array
    {
        global $_COMPANY, $_ZONE;

        $surveytypeFilter = '';
        if ($surveytype) {
            $surveytypeFilter = " AND surveytype={$surveytype}";
        }

        $surveytriggerFilter = '';
        if ($surveytrigger) {
            $surveytriggerFilter = " AND surveysubtype={$surveytrigger}";
        }

        $chapterFilter = '';
        // Chapterid can be -1, if it is 0 or higher then we will apply the filter
        if ($chapterid >= 0) {
            $chapterFilter = " AND chapterid={$chapterid}";
        }

        $channelFilter = '';
        // Channelid can be -1, if it is 0 or higher then we will apply the filter
        if ($channelid >= 0) {
            $channelFilter = " AND channelid={$channelid}";
        }

        return self::DBGet("SELECT * FROM surveys_v2 WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND (groupid={$groupid} {$chapterFilter} {$channelFilter} {$surveytypeFilter} {$surveytriggerFilter})");
    }

    public static function GetTopicType():string {return self::TOPIC_TYPES['SURVEY'];}
    /**
     * Function required by Topics .
     * @return string
     */
    public function getTopicTitle(): string
    {
        return $this->val('surveyname');
    }

    public static function ConvertDBRecToSurvey2 (array $rec) : ?Survey2
    {
        global $_COMPANY;
        $obj = null;
        $s = (int)$rec['surveyid'];
        $c = (int)$rec['companyid'];
        if ($s && $c && $c === $_COMPANY->id())
            $obj = new Survey2($s, $c, $rec);
        return $obj;
    }

    /**
     * Updates Survey JSON
     * @param string $survey_json
     * @return int
     */
    public function updateSurvey (string $survey_json)
    {
        global $_COMPANY, $_ZONE;
        $jsonArray = json_decode($survey_json,true);
        $pagesArray =  $jsonArray['pages'];
        $p = 0;
        foreach($pagesArray  as $pages){
            $e = 0;
            foreach ($pages['elements'] as $element){
             
                if ($element['type'] == "imagepicker"){
                
                    $choices = $element['choices']??array();
                    $c = 0;
                    foreach($choices as $choice){
                        $imgLink =  $choice['imageLink'];

                        if(is_array($choice['imageLink'])){                         
                            $imgLink = (array_values($choice['imageLink']))[0];                                 
                        }                       

                        if ( substr($imgLink, 0, 10) == 'data:image'){

                            $data = $imgLink;
                        
                            list($type, $data) = explode(';', $data);
                            list(, $data)      = explode(',', $data);
                            $data = base64_decode($data);

                            $tempfile = tempnam(sys_get_temp_dir(), teleskope_uuid());
                            file_put_contents($tempfile, $data);

                            $extension = explode('/', mime_content_type($tempfile))[1];                            
                            $actual_name = 'surveyimg_'.$this->id().'-'.teleskope_uuid().'.'.$extension;
                            $tempfile = $_COMPANY->resizeImage($tempfile, $extension, 900);
                                                        
                            // Upload to s3
                            $link = $_COMPANY->saveFile($tempfile, $actual_name, 'SURVEY');
                            unlink($tempfile);
                            $jsonArray['pages'][$p]['elements'][$e]['choices'][$c]['imageLink'] = $link;
                        }
                    
                        $c++;
                    }
                }
                $e ++;
            }
            $p++;
        }

        if (array_key_exists('logo',$jsonArray)){ 
                 
            if(is_string($jsonArray['logo'])){                
                if ( substr($jsonArray['logo'], 0, 10) == 'data:image'){
                    $data = $jsonArray['logo'];                            
                    list($type, $data) = explode(';', $data);
                    list(, $data)      = explode(',', $data);
                    $data = base64_decode($data);

                    $tempfile = tempnam(sys_get_temp_dir(), teleskope_uuid());
                    file_put_contents($tempfile, $data);

                    $extension = explode('/', mime_content_type($tempfile))[1];
                    $actual_name = 'surveylogo_'.$this->id().'.'.$extension;
                    $tempfile = $_COMPANY->resizeImage($tempfile, $extension, 900);
                    
                    // Upload to s3
                    $logolink = $_COMPANY->saveFile($tempfile, $actual_name, 'SURVEY');
                    unlink($tempfile);
                    $jsonArray['logo'] = $logolink;
                }
            }
        }

        
        $survey_json = json_encode($jsonArray) ?? '';
        // For a survey the only field that can be updated is survey_json
        $retVal = self::DBUpdatePS("UPDATE surveys_v2  SET survey_json=?, `version` = `version`+1,modifiedon=NOW(),isactive=2 WHERE surveyid=?",
            'xi',
            $survey_json, $this->id());

        if ($retVal) {
            $updated_what = [
                'update' => 'survey questions',
            ];
            self::LogObjectLifecycleAudit('update', 'survey', $this->id(), $this->val('version'), $updated_what);
        }
        return $retVal;
    }

    public function updateSurveyInformation (string $surveyname,int $is_required, string $sendEmailNotificationTo, int $anonymous)
    {

        if (!$this->isDraft()) { // Allow $anonymous value to be used for update only if the survey is in draft mode.
            $anonymous = intval($this->val('anonymous'));
        }

        $retVal = self::DBUpdatePS("UPDATE `surveys_v2` SET surveyname=?,`is_required`=?,`modifiedon`=NOW(),`send_email_notification_to`=?,`anonymous`=? WHERE surveyid=?",'sixii',$surveyname,$is_required, $sendEmailNotificationTo, $anonymous, $this->id());

        if ($retVal) {
            $updated_what = [
                'surveyname' => $surveyname,
                'is_required' => $is_required,
                'send_email_notification_to' => $sendEmailNotificationTo,
                'anonymous' => $anonymous
            ];
            self::LogObjectLifecycleAudit('update', 'survey', $this->id(), $this->val('version'), $updated_what);
        }
        return $retVal;
    }

    /**
     * NOTE: THIS METHOD NEEDS COMPANY OBJECT AS IT CAN BE CALLED FROM UNTRUSTED CONTEXT
     * @param Company $company
     * @param int $surveyid
     * @return GroupMemberSurvey|ZoneMemberSurvey|null
     */
    public static function GetSurveyByCompany(Company $company,int $surveyid){
        $rows = self::DBGet("SELECT * FROM surveys_v2 WHERE companyid={$company->id()} AND surveyid={$surveyid}");
        if (!empty($rows)) {
            $row = $rows[0];
            switch ($row['surveytype']) {
                case self::SURVEY_TYPE['GROUP_MEMBER'] :
                    return new GroupMemberSurvey((int)$row['surveyid'], (int)$row['companyid'], $row);
                case self::SURVEY_TYPE['ZONE_MEMBER'] :
                    return new ZoneMemberSurvey((int)$row['surveyid'], (int)$row['companyid'], $row);
//                case self::SURVEY_TYPE['TEAM_MEMBER'] : /*Disabled Talent Peak scope on 09/25/22*/
//                    return new TeamMemberSurvey((int)$row['surveyid'], (int)$row['companyid'], $row);
                default :
                    return null;
            }
        } else {
            return null;
        }
    }

    public static function GetSurvey(int $surveyid){
        global $_COMPANY;
        return self::GetSurveyByCompany($_COMPANY, $surveyid);
    }
    /*
 * This function was declared because Teleskope::GetTopicObj() references 
 * the class Survey2, but the expected method name was not found.
 * 
 * The method name is constructed dynamically using call_user_func(ClassName, FunctionName), 
 * where FunctionName must match the expected method within the class.
 * 
 * Since the method name was missing in Survey2, we explicitly define GetSurvey2
 * to properly handle the request.
 */
public static function GetSurvey2(int $surveyid) {
    global $_COMPANY;
    return self::GetSurveyByCompany($_COMPANY, $surveyid);
}

    /**
     * @param int $surveyid
     * @param int $userid
     * @param string $responseJson
     * @param string $profile_json
     * @param string $when
     * @return int|string
     */
    public function saveOrUpdateSurveyResponse(int $userid, string $responseJson, string $profile_json, string $when='', string $objectid = '', int $importedby = 0) {
        // If multiple updates are allowed and response is not anonymous, check if the userid is the record already exists , if so update and return
        if (!$this->val('allow_multiple') && $userid > 0) {
            $checkAlreadyResponded = self::DBGetPS("SELECT `responseid` FROM `survey_responses_v2` WHERE `survey_responses_v2`.`companyid`=? AND `surveyid`=? AND `userid`=? AND objectid=?",'iiix',$this->val('companyid'),$this->id(),$userid,$objectid);
            if (!empty($checkAlreadyResponded)) {
                self::DBUpdatePS("UPDATE `survey_responses_v2` SET response_json=?,profile_json=?,`modifiedon`=NOW(),`importedby`=? WHERE responseid=?", 'xxii', $responseJson, $profile_json, $importedby, $checkAlreadyResponded[0]['responseid']);
                return $checkAlreadyResponded[0]['responseid'];
            }
        }
        // If we are here it means this is a new submission.
        if ($when) {
            $responseid = self::DBInsertPS("INSERT INTO `survey_responses_v2`(`companyid`, `surveyid`, `userid`, `response_json`, `profile_json`, `createdon`,`objectid`,`importedby`) VALUES (?,?,?,?,?,?,?,?)", 'iiixxxxi', $this->val('companyid'), $this->id(), $userid, $responseJson, $profile_json, $when,$objectid,$importedby);
        } else {
            $responseid = self::DBInsertPS("INSERT INTO `survey_responses_v2`(`companyid`, `surveyid`, `userid`, `response_json`, `profile_json`, `createdon`,`objectid`,`importedby`) VALUES (?,?,?,?,?,NOW(),?,?)", 'iiixxxi', $this->val('companyid'), $this->id(), $userid, $responseJson, $profile_json,$objectid,$importedby);
        }

        global $_USER;

        $survey_saved_by_self = true;
        if ($importedby > 0 && $importedby != $userid) { // The user who imported is different than the user for whom the import is done.
            $survey_saved_by_self = false;
        }

        if ($responseid &&
            $survey_saved_by_self // Don't set email job for surveys that are updated by admin
        ) {
            global $_COMPANY, $_ZONE;
            if (!isset($_COMPANY)) {
                $_COMPANY = Company::GetCompany($this->val('companyid')); // Temporarily set $_COMPANY only to create instance of SurveyResponseJob, this happens for link based anonymous surveys.
                $_ZONE = $_ZONE ?? $_COMPANY->getZone($this->val('zoneid'));
                $job = new SurveyResponseJob($this->id(), $responseid);

                if ($_USER) {
                    Points::HandleTrigger('ON_SURVEY_RESPONSE', [
                        'surveyResponseId' => $responseid,
                    ]);
                }

                $_ZONE = null;
                $_COMPANY = null;
            } else {
                $job = new SurveyResponseJob($this->id(), $responseid);
            }
            $job->saveAsBatchCreateType();
        }

        return $responseid;
    }

    /**
     * Permanently deletes the survey and its responses
     * @return int
     */
    public function deleteIt()
    {
        global $_COMPANY;
        
        $result = self::DBMutate("DELETE FROM survey_responses_v2 WHERE survey_responses_v2.companyid={$_COMPANY->id()} AND surveyid= {$this->id}");
        $result = $result && self::DBMutate("DELETE FROM surveys_v2 WHERE companyid={$_COMPANY->id()} AND surveyid={$this->id}");

        if ($result) {
            self::LogObjectLifecycleAudit('delete', 'survey', $this->id(), $this->val('version'));
        }
        return $result;
    }

    public function getSurveyResponses() {
        global $_COMPANY;
        return self::DBGet("SELECT * FROM `survey_responses_v2` WHERE  `survey_responses_v2`.`companyid`='{$_COMPANY->id()}' AND `surveyid`= {$this->id}");
    }

    public static function GetSurveyResponseById(int $survey_response_id)
    {
        return self::DBROGetPS('SELECT * FROM `survey_responses_v2` WHERE `responseid` = ?', 'i', $survey_response_id);
    }

    public function getSurveyResponsesCount (bool $excludeSkipedResponses = false)
    {
        global $_COMPANY;
        $excludeSkipedResponsesCondition = "";
        if ($excludeSkipedResponses){
            $excludeSkipedResponsesCondition = " AND json_length(`response_json`)";
        }
        $responses = self::DBGet("SELECT COUNT(1) as totalResponses FROM `survey_responses_v2` WHERE  `survey_responses_v2`.`companyid`='{$_COMPANY->id()}' AND `surveyid`={$this->id} {$excludeSkipedResponsesCondition}");
        return $responses[0]['totalResponses'];
    }

    public function getGroupId() {
        return $this->val('groupid');
    }

    public function getChapterId() {
        return $this->val('chapterid');
    }

    public function getChannelId() {
        return $this->val('channelid');
    }

    public function activateDeactivateSurvey(int $updatePublishDate = 0,bool $allowDuplicates = false)
    {
        global $_COMPANY, $_ZONE, $_USER;
               

        if ($this->val('isactive') == self::STATUS_DRAFT) { // Draft --> Active
            $status = self::STATUS_ACTIVE;
           
        } elseif ($this->val('isactive') == self::STATUS_INACTIVE) { // Inctive --> Active
            $status = self::STATUS_ACTIVE;
           
        } elseif($this->val('isactive') == self::STATUS_UNDER_REVIEW){ // Under Email Review --> Active
            $status = self::STATUS_ACTIVE;

        } elseif ($this->val('isactive') == self::STATUS_PURGE) { // Delete --> Inactive
            $status = self::STATUS_INACTIVE;
            
        } else {
            $status = self::STATUS_INACTIVE; // * --> Inactive
            
        }

        if ($status == 1 && $this->val('surveysubtype') != 127 && !$allowDuplicates) {

            $teamSurveyCondition = "";
            if ($this->val('surveysubtype') == 4){
                $options_json = json_decode($this->val('options_json'),true);
                if (!empty($options_json)){ 
                    $roleType = $options_json['role_type'];
                    $daysFromStart = $options_json['days_from_start'];
                    $teamSurveyCondition = " AND JSON_EXTRACT(options_json, '$.role_type')={$roleType} AND JSON_EXTRACT(options_json, '$.days_from_start')={$daysFromStart}";
                }
            }
            $checkExisting = self::DBGet(
                "SELECT surveyid FROM `surveys_v2` 
                            WHERE `companyid`={$_COMPANY->id()} 
                              AND zoneid={$_ZONE->id()} 
                              AND surveyid !={$this->id()} 
                              AND groupid={$this->val('groupid')} 
                              AND chapterid={$this->val('chapterid')} 
                              AND channelid={$this->val('channelid')} 
                              AND surveytype={$this->val('surveytype')} 
                              AND surveysubtype = {$this->val('surveysubtype')} 
                              AND surveysubtype != 127 
                              {$teamSurveyCondition}
                              AND isactive = 1"
            );

            if (count($checkExisting)) {
                return -1;
            }
        }
        $publishDateCondtion = "";
        if ($updatePublishDate || ($status == self::STATUS_ACTIVE && empty($this->val('publishdate')))){
            $publishDateCondtion = " ,publishdate=NOW()"; // Publish date is updated only if explicitly requested by user or if it is first time publish.
        }

        $retVal = self::DBUpdate("UPDATE `surveys_v2` SET isactive={$status},`modifiedon`=NOW(), publishedby='{$_USER->id()}' {$publishDateCondtion} WHERE companyid={$_COMPANY->id()} AND (surveyid={$this->id})");
       
        if ($retVal) {			
            if ($status == self::STATUS_INACTIVE){
                self::LogObjectLifecycleAudit('state_change', 'survey', $this->id(), $this->val('version'), ['state' => 'unpublish']);                
            } elseif ($status == self::STATUS_ACTIVE) {
                self::LogObjectLifecycleAudit('state_change', 'survey', $this->id(), $this->val('version'), ['state' => 'publish']);
            }
        }
        return $retVal;
    }

    public function getSurveyTriggerLabel() {
        $triggerLabel = array_flip(self::SURVEY_TRIGGER)[$this->val('surveysubtype')];
        $triggerLabel = str_replace('_',' ', strtolower($triggerLabel));
        return ucfirst($triggerLabel);
    }


    public  function updateSurveyTemplateFlag(int $isTemplate)
    {
        global $_COMPANY, $_ZONE;
        $retVal = self::DBUpdate("UPDATE `surveys_v2` SET is_template={$isTemplate} WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND (surveyid={$this->id})");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'survey', $this->id(), $this->val('version'), ['is_template' => $isTemplate]);
        }
        return $retVal;
    }

    public static function GetSurveyTemplate( int $surveyType) {
        global $_COMPANY, $_ZONE;

        // Add Zone Member survey to all admin surveys to show in the list.
        if ($surveyType != self::SURVEY_TYPE['ZONE_MEMBER']) {
            $surveyType .= ','. self::SURVEY_TYPE['ZONE_MEMBER'];
        }
        $surveyType .= ',127';

        return self::DBGet("SELECT `surveyid`, `surveyname` FROM surveys_v2 WHERE `companyid`={$_COMPANY->id()} AND  `zoneid`={$_ZONE->id()} AND `surveytype` IN ({$surveyType}) AND `is_template`='1'");
    }


    public function canSurveyRespond(string $objectid = '')
    {
        global $_USER;
        global $_COMPANY;

        $response = self::DBGet("SELECT `createdon`,`objectid` FROM `survey_responses_v2` WHERE `survey_responses_v2`.`companyid`='{$_COMPANY->id()}' AND `surveyid`={$this->id} AND `userid`={$_USER->id()} AND createdon > '{$this->val('publishdate')}'");
        return empty($response);
        // We are doing matching for object id in PHP instead of DB for performance. Objectid is a string and using
//        $matched_item = null;
//        foreach ($response as $item) {
//            // Check for objectid for a match
//            if ((empty($objectid) && empty($item['objectid'])) || ($objectid == $item['objectid'])) {
//                $matched_item = $item;
//                break;
//            }
//        }
//
//        if ($matched_item) {
//            if (strtotime($this->val('publishdate')) > strtotime($item['createdon'])) {
//                return true;
//            } else {
//                return false;
//            }
//        } else {
//            return true;
//        }
    }

    public function sendSurveyForReview(string $emails,string $reviewNote, string $subjectPrefix = ''){
        global $_COMPANY,$_ZONE,$_USER;

        $group = Group::GetGroup($this->getGroupId());
        $subject = gettext("Request to Review & Publish Survey");
        $app_type = $_ZONE->val('app_type');
        $reply_addr = $group->val('replyto_email');
        $from = sprintf(gettext("%s Survey Review Request"),$group->val('from_email_label'));
    
        $m = sprintf(gettext("There is a new %s"),strtolower($subject));
        $urlParameters = json_encode(array('companyid'=>$_COMPANY->id(),'surveyid'=>$this->id(), 'expirytime' => time() + 2592000));
        $encUrlParameters = aes_encrypt($urlParameters, TELESKOPE_USERAUTH_API_KEY, "oBu1tOvMUKuWlFHrFaMuswWj7eloTXDWbFb6Y1NZ", false,true);
        $surveyListUrl = $this->getSurveyListUrl();
        $surveyPreviewUrl = $_COMPANY->getSurveyURL($_ZONE->val('app_type')) . 'preview?params=' . $encUrlParameters;
    
        $triggerLabelSuffix = '';
        $options = null;
        if ($this->val('options_json')){
            $options = $this->val_json2Array('options_json');
            $triggerLabelSuffix = ($options ? ($options['days_from_start'] == -1 ? ' (On Close)' : ( $options['days_from_start'] == -2 ? 'Link' : ' ('.$options['days_from_start'].' '.gettext('days').')') ): '');
        }
        $surveyType = ($options && $options['days_from_start'] == -2) ? $triggerLabelSuffix : $this->getSurveyTriggerLabel().$triggerLabelSuffix;
    
        $surveyName = htmlspecialchars($this->val('surveyname'));
        $anonymous = $this->val('anonymous') ? 'Yes' : 'No';

        // Build Survey scope string
        $surveyScope = 'Global';
        if ($_ZONE->val('app_type') != 'talentpeak') {
            if ($this->getGroupId()) {
                $surveyScope = $group->val('groupname') . ' ' . $_COMPANY->getAppCustomization()['group']['name'];
                if ($this->getChapterId()) {
                    $surveyScope .= ' > ' . htmlspecialchars(Group::GetChapterName($this->getChapterId(), $this->getGroupId())['chaptername']) . ' ' . $_COMPANY->getAppCustomization()['chapter']['name'];
                } elseif ($this->getChannelId()) {
                    $surveyScope .= ' > ' . htmlspecialchars(Group::GetChannelName($this->getChannelId(), $this->getGroupId())['channelname']) . ' ' . $_COMPANY->getAppCustomization()['channel']['name'];
                }
            }
        } elseif ($_ZONE->val('app_type') == 'talentpeak' && !empty($options)) {
            $surveyScope = ($roleType = Team::GetTeamRoleType($options['role_type'])) ? $roleType['type'] : '';
        }
        
        if (!empty($reviewNote)) {
            $reviewNote = '<div style="background-color:#80808026; padding:20px;"><b>Note:&nbsp;</b>' . stripcslashes($reviewNote) . '</div>';
        }
        $msg = <<<EOMEOM
                {$reviewNote}
                <p>There is a new survey review request from {$group->val('groupname')}.</p>
                <br>
                <p>Survey Review Request Summary:</p>
                <p>---------------------------------------------------------------------</p>
                <p><b>Requested by:</b> {$_USER->getFullName()} ({$_USER->val('email')})</p>
                <p><b>Survey Name :</b> {$this->val('surveyname')}</p>
                <p><b>Survey Scope :</b> {$surveyScope}</p>
                <p><b>Survey Type :</b> {$surveyType}</p>
                <p><b>Anonymous :</b> {$anonymous}</p>
                <p><b>Survey preview link:</b> <a href="{$surveyPreviewUrl}">Survey preview without login</a></p><br>
                <p>Choose the survey from the <b><a href="{$surveyListUrl}">list</a></b> to view, edit and publish once approved</p>
                <p>---------------------------------------------------------------------</p>
                <br>
                <br>
EOMEOM;
        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $emesg = str_replace('#messagehere#', $msg, $template);
        if ($_COMPANY->emailSend2($from, $emails, $subject, $emesg, $app_type, $reply_addr)){
            return 1;
        } else {
            return 0;
        }
    }
    
    public function getSurveyListUrl(): string
    {
        global $_COMPANY;

            $base = Url::GetZoneAwareUrlBase($this->val('zoneid'));

        if ((int) $this->val('groupid')) {
            return $base . 'manage?id='.$_COMPANY->encodeId($this->getGroupId()).'&survey'; // Add &survey as url parameter to distinguish that its a Survey link, becasue # parameter is not accessible by php
        }

        return $base . 'manage_admin_contents?survey'; // Add &survey as url parameter to distinguish that its a Survey link, becasue # parameter is not accessible by php
    }

    /**
     * To function recursivels extract all 'title' keys (which are languages) from a deeply nested PHP array
     * like $surveyData.
     * @param $array
     * @return array
     */
    private function extractLanguagesFromTitle($array): array
    {
        $title_keys = [];

        foreach ($array as $key => $value) {
            if ($key === 'title' && is_array($value)) {
                $title_keys = array_merge($title_keys, array_keys($value));
                // No need to continue deeper under this branch if title found
                continue;
            }

            if (is_array($value)) {
                $title_keys = array_merge($title_keys, $this->extractLanguagesFromTitle($value));
            }
        }

        return array_unique($title_keys);
    }
    public function getSurveyLanguages(){

        $retVal = array();
        $locale = '';
        $languages = array();

        if ($this->val('survey_json')){
            $surveyObjects = json_decode($this->val('survey_json'),true);

            $languages = $this->extractLanguagesFromTitle($surveyObjects);

            if (!empty($surveyObjects['locale'])){
                $locale = $surveyObjects['locale'];
                $languages[] = $locale;
            }

            if (!empty($languages)) {
                $languages[] = 'default';
                if (in_array('en', $languages) && in_array('default', $languages)) {
                    // since en and default mean the same, we need to remove one.
                    $languages = Arr::RemoveByValue($languages, 'en');
                }
                $retVal =  array('locale'=>$locale,'languages'=>array_unique($languages));
            }
        }
        return $retVal;
    }

    public function isSurveyResponded(int $userid){
        global $_USER;
        $responded = false;
        $checkAlreadyResponded = self::DBROGet("SELECT `responseid` FROM `survey_responses_v2` WHERE `survey_responses_v2`.`companyid`='{$this->val('companyid')}' AND `surveyid`='{$this->id()}' AND `userid`='{$userid}'");
        if(!empty($checkAlreadyResponded)){
            $responded = true;
        }
        return $responded;
    }

    public function processImportSurveys(array $csvData){
        global $_COMPANY,$_ZONE,$_USER;

        $meta = ReportSurvey::GetDefaultReportRecForDownload();
        $fields = array_map('strtolower', array_values($meta['Fields']));
        $emailColumnName = strtolower($meta['Fields']['email']);
        $responseDateColumnName = strtolower($meta['Fields']['responsedate']);

        $response = ['totalProcessed'=>0,'totalFailed'=>0,'failed'=>[]];

        $surveyJson = json_decode($this->val('survey_json'), true);
        $surveyQuestionsPages = $surveyJson['pages'];

        $surveyQuestions = array();

        foreach($surveyQuestionsPages as $element){
            if (empty($element['elements']))
                continue;
            $surveyQuestions = array_merge($surveyQuestions,$element['elements']);
        }

		if (!empty($csvData)){
			$failed = array();
			$rowid = 0;
			foreach($csvData as $row){
                $rowid++;

                // Extract the email and fetch the user
                if (
                    empty($row[$emailColumnName]) ||
                    ($responder = User::GetUserByEmail($row[$emailColumnName])) === null
                ){
                    array_unshift($row,$rowid.': Email/user not found');
			        array_push($failed,$row);
                    continue;
                }

                // Extract or set the response date
                $response_date = empty($row[$responseDateColumnName]) ? time() : (strtotime($row[$responseDateColumnName] . ' UTC') ?? time());
                $when = gmdate("Y-m-d H:i:s", $response_date);
                
                $failedAnyRow = false;
                $surveyResponses = array();
                foreach ($row as $key=>$value){

                    if (in_array($key,$fields)){
                        continue;
                    }

                    $index =  array_search($key, array_map('strtolower', array_column($surveyQuestions, 'title')));

                    if ($index === false){
                        array_unshift($row,$rowid.': Question not found!');
			            array_push($failed,$row);
                        $failedAnyRow = true;
                        break;
                    } else {
                        $question = $surveyQuestions[$index];

                        if ($question['type'] == 'matrix' || $question['type'] == 'matrixdropdown'){
                            array_unshift($row,$rowid.': Matrix type question not supported!');
			                array_push($failed,$row);
                            continue;
                        }

                        $retVal  = self::GetSurveyQuestionKeyByValue($question, $value);

                        $questionKeyValue = $retVal['value'];
                        $questionComment = $retVal['comment'];

                        if ($questionKeyValue!==''){

                            if ($question['type'] == 'boolean'){
                                $questionKeyValue = $questionKeyValue ? true : false;
                            }

                            $surveyResponses = array_merge( $surveyResponses,array($question['name']=>$questionKeyValue));
                            if (!empty($questionComment)) {
                                $surveyResponses = array_merge( $surveyResponses,array($question['name'].'-Comment'=>$questionComment));
                            }
                        }
                    }
                }

                if (!$failedAnyRow){
                    $profile_json = json_encode(array('firstname'=>$responder->val('firstname'),'lastname'=>$responder->val('lastname'),'email'=>$responder->getEmailForDisplay(),'jobTitle'=>$responder->val('jobtitle'),'officeLocation'=>$responder->getBranchName(),'department'=>$responder->getDepartmentName()));
                    // Update Survey Responses
                    self::saveOrUpdateSurveyResponse($responder->id(),json_encode($surveyResponses),$profile_json, $when, '', $_USER->id());
                }

            }
			$response = ['totalProcessed'=>count($csvData),'totalSuccess'=>count($csvData)-count($failed),'totalFailed'=>count($failed),'failed'=>$failed];
		}
		return $response;
    }


    public static function GetSurveyQuestionKeyByValue(array $question, string $value) : array
    {
        $retVal = array('value' => '', 'comment' => null);
        $value = trim($value);
        if (empty($question) || empty($value)) {
            return $retVal;
        }

        if ($question['type'] == 'radiogroup' || $question['type'] == 'dropdown') {

            $choices = $question['choices'];

            $valueLower = strtolower($value);
            $choicesText = array_map('strtolower',array_map('trim',array_column($question['choices'], 'text')));
            $matching_key = array_search($valueLower, $choicesText);
            if ($matching_key !== false) {
               $retVal['value'] = $question['choices'][$matching_key]['value'];
            } elseif ($question['hasNone'] && $valueLower === 'none') { // Usecase: None
                $retVal['value'] = 'none';
            } elseif ($question['hasOther']) { // Usecase: Other
                $otherValue = str_starts_with($valueLower,'other:') ? trim(substr($value, 6)) : $value;
                if (!empty($otherValue)) {
                    $retVal['value'] = 'other';
                    $retVal['comment'] = $otherValue;
                }
            }
        }

        elseif ($question['type'] == 'checkbox' || $question['type'] == 'ranking') {

            $keyValue = array();
            // Determine if $question['choices'] is indexed or associative
            $isAssociativeChoices = isset($question['choices'][0]) || (isset($question['choices'][0]['value']) && isset($question['choices'][0]['text']));
            // Prepare the choicesText array based on the structure of the $question['choices']
            if ($isAssociativeChoices) {
                $choicesText = array_map('strtolower', array_map('trim', array_column($question['choices'], 'text')));
                
            } else {
                $choicesText = array_map('strtolower', array_map('trim', $question['choices']));
            }

        
            $valueArray = array_map('trim', explode(',', $value));
            $otherValue = '';
            foreach ($valueArray as $v) {
                $vLower = strtolower($v);
                $matching_key = array_search($vLower, $choicesText);
                if ($matching_key !== false) {
                    if ($isAssociativeChoices) {
                        $keyValue[] = $question['choices'][$matching_key]['value'];
                    } else {
                        $keyValue[] = $question['choices'][$matching_key];
                    }
                } elseif ($question['hasOther']) {
                    if ($isAssociativeChoices) {
                        $o = str_starts_with($vLower, 'other:') ? trim(substr($v, 6)) : $v;
                        if (!empty($o)) {
                            $keyValue[] = 'other';
                            $retVal['comment'] = $o;
                        }
                    } else {
                        $otherValue = str_starts_with($vLower, 'other:') ? trim(substr($v, 6)) : $v;
                    }
                }
            }
            if (!empty($otherValue)) {
                $keyValue[] = 'other';
                $retVal['comment'] = $otherValue;
            }

            $retVal['value'] = $keyValue;
        }

        elseif ($question['type'] == 'text'  || $question['type'] == 'comment') {
            $retVal['value'] = $value;
        }

        elseif ($question['type'] == 'boolean') {

            if (array_key_exists('labelTrue',$question) && $question['labelTrue'] == $value){
                $retVal['value'] = 1;
            } elseif(array_key_exists('labelFalse',$question) && $question['labelFalse'] == $value){
                $retVal['value'] = 0;
            } else {
                $retVal['value'] = $value == "Yes";
            }
        }
        elseif ($question['type'] == 'rating') {

            if (isset($question['rateValues'])) {
                if (in_array($value, self::ExtractValuesFromRatingQuestion($question['rateValues']))) {
                    $retVal['value'] = (int)$value;
                }
            } else {
                $retVal['value'] = (int)$value;
            }
        }

        elseif ($question['type'] == 'imagepicker') {

            $choices = $question['choices'];
            $keyValue = '';
            foreach($choices  as $choice){

                if (array_key_exists('imageLink',$choice) && $choice['value'] == $value){
                    $keyValue = $value;
                    break;
                } elseif(array_key_exists('imageLink',$choice)) {

                    if ($choice['imageLink'] == $value){
                        $keyValue = $choice['value'];
                        break;
                    }
                } else {
                    $keyValue = $value;
                    break;
                }
            }
            $retVal['value'] = $keyValue;
        }

        return $retVal;

    }

    public static function ExtractValuesFromRatingQuestion(array $rateQuestion) : array {
        $values = [];

        foreach ($rateQuestion as $item) {
            if (is_array($item) && isset($item['value'])) {
                $values[] = $item['value'];
            } elseif (is_int($item)) {
                $values[] = $item;
            }
        }

        return $values;
    }

    public function checkSurveyQuestionsHasType(array $questionTypes){
        global $_COMPANY,$_ZONE,$_USER;

        $surveyJson = json_decode($this->val('survey_json'), true);
        $surveyQuestionsPages = $surveyJson['pages'];

        $surveyQuestions = array();

        foreach($surveyQuestionsPages as $element){

            foreach($element['elements'] as $question){

                if (in_array($question['type'], $questionTypes)){
                    return true;
                }
            }
        }
        return false;
    }
    public function updateSurveyForReview()
    {
        global $_COMPANY, $_ZONE;
        
        $status_under_review = self::STATUS_UNDER_REVIEW;
        $retVal = self::DBUpdate("UPDATE `surveys_v2` SET isactive={$status_under_review},`modifiedon`=NOW() WHERE companyid={$_COMPANY->id()} AND (surveyid={$this->id})");

        if ($retVal) {			
                self::LogObjectLifecycleAudit('state_change', 'survey', $this->id(), $this->val('version'), ['state' => 'review']);
        }
        return $retVal;
    }


    /**
     * This method takes survey.js question and converts the question options as key value pairs.
     * @param array $question
     * @return array[] returns an associative array e.g. ['item1' => 'red', 'item2' => 'blue'] or empty array;
     */
    public static function GetSurveyQuestionOptionValues(array $question) : array
    {
        $options = [];
        if (empty($question)) {
            return [];
        }

        if ($question['type'] == 'radiogroup' || $question['type'] == 'checkbox' || $question['type'] == 'dropdown' || $question['type'] == 'ranking') {
            $choices = $question['choices'];
            if (!empty($choices)) {
                $options = array_combine (array_column($choices,'value'), array_column($choices,'text'));
            }
        }

        elseif ($question['type'] == 'boolean') {
            $labelTrue = 'Yes';
            $labelFalse = "No";
            if (array_key_exists('labelTrue',$question)) {
                $labelTrue = $question['labelTrue'];
            }
            if (array_key_exists('labelFalse',$question)) {
                $labelFalse = $question['labelFalse'];
            }
            $options = array_combine(array('labelTrue','labelFalse'), array($labelTrue,$labelFalse));
        }

        elseif ($question['type'] == 'rating') {

            if (array_key_exists('autoGenerate', $question) && $question['autoGenerate'] == false) { // if value altered

                $choices = $question['rateValues'];
                $values = [];
                $texts = [];
                foreach ($choices as $item) {
                    if (is_array($item)) {
                        $values[] = $item['value'];
                        $texts[] = $item['text'];
                    } else {
                        $values[] = $item;
                        $texts[] = $item;
                    }   
                }
                $options = array_combine($values, $texts);
            }  else { // default 5 star or  if value not altered but start count incresed or decreased
                $rateMax = 5;
                if (array_key_exists('rateMax',$question)) {
                    $rateMax = $question['rateMax'];
                    $values = range(1,$rateMax);
                    $options = array_combine($values, $values);
                }
            }
        }
        // Imagepicker and matrix type not considered yet.
        return $options;
    }

    public static function GetCustomName(bool $defaultNamePlural = false){
        global $_COMPANY, $_ZONE, $_USER;
        if ($defaultNamePlural){
            $name = gettext('Surveys');
        } else {
            $name = gettext('Survey');
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

    public function getSurveyChapterNames(): array
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
     * This method calculates if the Action button should be disabled if the survey is approved or in the state of approval
     * @return bool
     */
    public function isActionDisabledDuringApprovalProcess() {
        global $_COMPANY;

        $this->is_action_disabled_due_to_approval_process = false;
        if ($_COMPANY->getAppCustomization()['surveys']['approvals']['enabled']) {
            $approval = $this->getApprovalObject();
            if ($approval && !$approval->isApprovalStatusDenied() && !$approval->isApprovalStatusReset() && !$approval->isApprovalStatusCancelled()) {
                $this->is_action_disabled_due_to_approval_process = true;
            }
        }
        return $this->is_action_disabled_due_to_approval_process;
    }

    public static function GetSurveyQuestionCounter(?string $survey_json)
    {   
        if (empty($survey_json)) {
            return 1; // Default
        }
        $survey_json = json_decode($survey_json,true);

        $numbers = [];
        foreach ($survey_json['pages'] as $page) {
            foreach ($page['elements'] as $element) {
                if (isset($element['name'])) {
                    // Extract number from name
                    preg_match_all('/\d+/', $element['name'], $matches);
                    foreach ($matches[0] as $num) {
                        $numbers[] = (int)$num;
                    }
                }
            }
        }

        if (isset($survey_json['teleskopeQuestionCounter']) && $survey_json['teleskopeQuestionCounter'] > count($numbers)) {
            return $survey_json['teleskopeQuestionCounter'];
        }
        return (!empty($numbers) ? max(max($numbers),count($numbers)) : 1)+2; //Increment by 2 as a precaution to ensure the ID is unique

    }

    public static function HasDuplicateQuestionKey(?string $survey_json) 
    {
        if (empty($survey_json)) {
            return false; // Default
        }
        $survey_json = json_decode($survey_json,true);
        $questionKeys = [];
        foreach ($survey_json['pages'] as $page) {
            foreach ($page['elements'] as $element) {
                if (isset($element['name'])) {
                    if (in_array($element['name'],$questionKeys)) {
                        return true;
                    }
                    $questionKeys[] = $element['name'];
                }
            }
        }
        return false;
    }

}

