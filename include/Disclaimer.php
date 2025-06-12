<?php

// Do no use require_once as this class is included in Company.php.

class Disclaimer extends Teleskope
{

    const DISCLAIMER_HOOK_TRIGGERS = [
        'LOGIN_FIRST' => 1,

        ### Group Section ###
        'GROUP_JOIN_BEFORE' => 10,
         // 'GROUP_JOIN_AFTER' => 11,
        'GROUP_LEAVE_BEFORE' => 12,

        ### Survey Section ###
        'SURVEY_CREATE_BEFORE' => 20,
        //'SURVEY_CREATE_AFTER' => 21,
        // 'SURVEY_PUBLISH_BEFORE' => 22,
        // 'SURVEY_PUBLISH_AFTER' => 23,

        ### Event Section ###
        'EVENT_CREATE_BEFORE' => 30,
        // 'EVENT_CREATE_AFTER' => 31,
        'EVENT_PUBLISH_BEFORE' => 32,
        //'EVENT_PUBLISH_AFTER' => 33,

        ### Announcement Section ###
        'POST_CREATE_BEFORE' => 40,
        'POST_PUBLISH_BEFORE' => 41,

        ### Discussion Section ###
        'DISCUSSION_CREATE_BEFORE' => 50,

        ### Newsletter Section ###
        'NEWSLETTER_CREATE_BEFORE' => 60,
        'NEWSLETTER_PUBLISH_BEFORE' => 61,

        ### Teams Section ###
        'TEAMS__CIRCLE_CREATE_BEFORE' => 70,

        ### Budget Section ###
        'BUDGET_REQUEST_CREATE_BEFORE' => 80,
        'BUDGET_EXPENSE_CREATE_BEFORE' => 81,

        #DM#
        'DIRECT_MESSAGE_CREATE_BEFORE' => 90,
        
    ];

    const DISCLAIMER_HOOK_LINKS = [
        'EVENT_RSVP' => 10001,
    ];

    const DISCLAIMER_INVOCATION_TYPE = [
        'TRIGGER' => 'TRIGGER',
        'LINK' => 'LINK'
    ];
    const DISCLAIMER_CONSENT_TYPE = [
        'CHECKBOX' => 'checkbox',
        'TEXT' => 'text',
    ];

    protected function __construct($id, $cid, $fields)
    {
        parent::__construct($id, $cid, $fields);
        //declaring it protected so that no one can create it outside this class.
    }

    /**
     * @param string $disclaimer_name
     * @param string $invocation_type
     * @param int $hookid
     * @param array $disclaimer ["en" => ["disclaimer" => "disclaimer html ...", "
     * @param int $consent_required
     * @param string|null $consent_type checkbox or text
     * @return Disclaimer|null
     */
    public static function CreateANewDisclaimer(string $disclaimer_name, string $invocation_type, int $hookid, array $disclaimer, int $consent_required, ?string $consent_type, int $enable_by_default): ?Disclaimer
    {
        global $_COMPANY, $_ZONE, $_USER;
        // Data validation
        if (!self::IsHookValidType($hookid)) return null;

        if ($consent_type && !in_array($consent_type, array_values(self::DISCLAIMER_CONSENT_TYPE))) return null;

        $consent_required = $consent_required ? 1 : 0;
        $consent_type = $consent_required ? $consent_type : null;

        $disclaimer = self::SanitizeDisclaimer($disclaimer);
        if (empty($disclaimer)) return null;

        $disclaimer_json = json_encode($disclaimer);

        // Note in the following we are intentionally setting version=version on duplicate key update to avoid fatal error
        $i1 = self::DBInsertPS("
                    INSERT INTO disclaimers
                    (companyid, zoneid, `disclaimer_name`, `invocation_type`, hookid, disclaimer, enabled_by_default, consent_required, consent_type, createdby, modifiedby) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE version = version",
            'iixxixiixii',
            $_COMPANY->id(), $_ZONE->id(),$disclaimer_name, $invocation_type, $hookid, $disclaimer_json, $enable_by_default, $consent_required, $consent_type, $_USER->id(), $_USER->id()
        );

        if ($i1) {
            self::LogObjectLifecycleAudit('create', 'disclaimer', $i1, 1);
            return self::GetDisclaimerById($i1);
        }

        return null;
    }

    public static function IsHookValidType(int $hookid): bool
    {
        return (in_array($hookid, array_values(self::DISCLAIMER_HOOK_TRIGGERS)) || in_array($hookid, array_values(self::DISCLAIMER_HOOK_LINKS)));
    }


    /**
     * @param string $disclaimer_name
     * @param array $disclaimer ["en" => ["disclaimer" => "disclaimer html ...", "
     * @param int $consent_required 1 for yes, 0 for no
     * @param string $consent_type checkbox or text
     * @param bool $update_consent_version, if true minimum consent version will be updated to latest
     * @return int
     */
    public function updateDisclaimer(string $disclaimer_name, array $disclaimer, int $consent_required, string $consent_type, int $enabled_by_default, bool $update_consent_version): int
    {
        global $_COMPANY, $_ZONE, $_USER;

        // Before updating backup the current version of the disclaimer.
        $this->backupDisclaimerVersion();

        $update_consent_version_query = "";
        if ($update_consent_version) { // 1 for update consent min version.
           $update_consent_version_query =  ',consent_min_version=version';
        }

        // Data validation
        if (!in_array($consent_type, array_values(self::DISCLAIMER_CONSENT_TYPE))) return 0;
        $consent_required = $consent_required ? 1 : 0;
        $consent_type = $consent_required ? $consent_type : null;

        $disclaimer = self::SanitizeDisclaimer($disclaimer);
        if (empty($disclaimer)) return 0;

        $disclaimer_json = json_encode($disclaimer);

        $r1 = self::DBUpdatePS("
                    UPDATE disclaimers 
                    SET `disclaimer_name`= ?, disclaimer=?, enabled_by_default=?, consent_required=?, consent_type=?, modifiedby=?, modifiedon=NOW(), version=version+1 {$update_consent_version_query}
                    WHERE companyid=? AND zoneid=? AND disclaimerid=?",
            'xxiixiiii',
            $disclaimer_name, $disclaimer_json, $enabled_by_default, $consent_required, $consent_type, $_USER->id(), $_COMPANY->id(), $_ZONE->id(), $this->id()
        );

        if ($r1) {
            $additional_attributes = [
                'consent_min_version_updated' => $update_consent_version
            ];
            self::LogObjectLifecycleAudit('update', 'disclaimer', $this->id(), $this->val('version'), $additional_attributes);
        }

        return $r1;
    }

    /**
     * set the consent minimum version, users who have provided consent to the previous version will be asked to provide consent again.
     * @param int $new_version
     * @return int
     */
    public function updateDisclaimerConsentVersion (int $new_version): int
    {
        global $_COMPANY;
        $u1 = self::DBMutate("
                    UPDATE disclaimers 
                    SET consent_min_version={$new_version} 
                    WHERE companyid={$_COMPANY->id()} 
                      AND disclaimerid={$this->id}"
        );

        if ($u1) {
            self::LogObjectLifecycleAudit('state_change', 'disclaimer', $this->id(), $this->val('version'), ['consent_min_version'=>$new_version]);
        }

        return $u1;
    }

    public static function GetDisclaimerById(int $disclaimerid): ?Disclaimer
    {
        global $_COMPANY, $_ZONE;

        $r1 = self::DBGet("
            SELECT * 
            FROM disclaimers 
            WHERE companyid = {$_COMPANY->id()} 
              AND disclaimerid={$disclaimerid}"
        );

        if (!empty($r1)) {
            return new Disclaimer($r1[0]['disclaimerid'], $r1[0]['companyid'], $r1[0]);
        }
        return null;
    }

    /**
     * This method is company is zone aware, it will get the disclaimer for a given language or english language
     * Only active disclaimer is returned
     * @param int $hookid
     * @param bool $active_only
     * @return Disclaimer|null ();
     */
    public static function GetDisclaimerByHook(int $hookid, bool $active_only = true): ?Disclaimer
    {
        global $_COMPANY, $_ZONE;
        $active_filter = ($active_only) ? ' AND isactive=1' : '';

        $r1 = self::DBGet("
            SELECT * 
            FROM disclaimers 
            WHERE companyid ={$_COMPANY->id()}
              AND zoneid={$_ZONE->id()}
              AND hookid={$hookid} 
              {$active_filter}"
        );

        if (!empty($r1)) {
            return new Disclaimer($r1[0]['disclaimerid'], $r1[0]['companyid'], $r1[0]);
        }

        return null;
    }

    public static function GetAllDisclaimersInZone(string $invocation_type = '', bool $activeOnly = false)
    {
        global $_COMPANY, $_ZONE;
        $invocation_type_filter = '';
        if ($invocation_type && in_array($invocation_type, self::DISCLAIMER_INVOCATION_TYPE)) {
            $invocation_type_filter = " AND invocation_type= '{$invocation_type}'";
        }
        $activeCondition = '';
        if ($activeOnly) {
            $activeCondition = " AND `isactive`=1";
        }
        $disclaimers = array();
        $rows = self::DBGet("
            SELECT * 
            FROM disclaimers 
            WHERE companyid ={$_COMPANY->id()}
              AND zoneid={$_ZONE->id()}
            {$invocation_type_filter}
            {$activeCondition}
        ");

        if (!empty($rows)) {
            foreach($rows as $row){
                $disclaimers[] =  new Disclaimer($row['disclaimerid'], $row['companyid'], $row);
            }
        }

        return  $disclaimers;
    }

    public function getDisclaimerArray()
    {
        return json_decode($this->val('disclaimer'), true);
    }
    /**
     * This function save the user survey consent to db disclaimer_consent table
     * @param string $consent_text
     * @return int;
     */
    public function saveUserConsent(string $consent_text, string $consent_lang, int $consent_contextid=0): int
    {
        global $_COMPANY, $_USER;
        $ip = IP::GetRemoteIPAddr();

        if ($this->val('consent_type') != 'text') {
            $consent_text = null;
        }

        if (!in_array((int)$this->val('hookid'), [
            self::DISCLAIMER_HOOK_TRIGGERS['GROUP_JOIN_BEFORE'],
            self::DISCLAIMER_HOOK_TRIGGERS['GROUP_LEAVE_BEFORE'],
            self::DISCLAIMER_HOOK_TRIGGERS['TEAMS__CIRCLE_CREATE_BEFORE'],
            self::DISCLAIMER_HOOK_TRIGGERS['BUDGET_REQUEST_CREATE_BEFORE'],
            self::DISCLAIMER_HOOK_TRIGGERS['BUDGET_EXPENSE_CREATE_BEFORE'],
            self::DISCLAIMER_HOOK_TRIGGERS['EVENT_PUBLISH_BEFORE'],
            self::DISCLAIMER_HOOK_TRIGGERS['POST_PUBLISH_BEFORE'],
            self::DISCLAIMER_HOOK_TRIGGERS['NEWSLETTER_PUBLISH_BEFORE'],
            self::DISCLAIMER_HOOK_TRIGGERS['DIRECT_MESSAGE_CREATE_BEFORE'],
            self::DISCLAIMER_HOOK_LINKS['EVENT_RSVP']
        ])) {
            // Consent context id is only allowed for group type hooks
            $consent_contextid = 0;
        }

        $i1 = (int)self::DBInsertPS("
            INSERT INTO disclaimer_consents
                (`companyid`, `disclaimerid`, `disclaimer_version`, `disclaimer_lang`, `userid`, `consent_contextid`,`consent_text`, `ipaddress`, `createdon`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE disclaimer_version=?, disclaimer_lang=?, consent_text=?, ipaddress=?, createdon=NOW()
                ",
            'iiixiixxixxx',
            $_COMPANY->id(), $this->id, $this->version(), $consent_lang, $_USER->id(), $consent_contextid, $consent_text, $ip, $this->version(), $consent_lang, $consent_text, $ip
        );

        if ($i1) {
            self::LogObjectLifecycleAudit('create', 'disclaimer_consent', $i1, 0, ['disclaimerid' => $this->id(), 'consent_contextid' => $consent_contextid]);
        }

        return $i1;
    }

    /**
     * This function get the user survey consent data response as 1 or 0 from db disclaimer_consent table.
     * @param int $userid
     * @param int $consent_contextid
     * @return array
     */
    public function getConsentForUserid(int $userid, int $consent_contextid=0): array
    {
        global $_COMPANY;

        $res = self::DBGet("
            SELECT * 
            FROM disclaimer_consents
            WHERE companyid={$_COMPANY->id()}
              AND disclaimerid={$this->id}
              AND userid={$userid} 
              AND consent_contextid={$consent_contextid}
              "
        );

        if (!empty($res)) {
            return $res[0];
        }
        return [];
    }

    /**
     * @return array
     */
    public function getAllConsents()
    {
        global $_COMPANY;

        return self::DBGet("
            SELECT * 
            FROM disclaimer_consents
            WHERE companyid={$_COMPANY->id()}
              AND disclaimerid={$this->id}"
        );
    }

    public function getConsentCount()
    {
        global $_COMPANY;

        return self::DBGet("
            SELECT count(1) as cc
            FROM disclaimer_consents
            WHERE companyid={$_COMPANY->id()}
              AND disclaimerid={$this->id}"
        )[0]['cc'] ?? 0;
    }

    public function isConsentRequired(): bool
    {
        return boolval($this->val('consent_required'));
    }

    /**
     * @param int $status
     * @return int
     */
    private function setStatus(int $status): int
    {
        global $_COMPANY, $_ZONE, $_USER;
        $u1 = self::DBMutate("
                UPDATE disclaimers 
                SET isactive={$status}, modifiedon=NOW(), modifiedby={$_USER->id()}
                WHERE companyid={$_COMPANY->id()} 
                  AND zoneid={$_ZONE->id()} 
                  AND disclaimerid={$this->id}"
        );
        if ($u1) {
            $this->fields['isactive'] = $status;
            self::LogObjectLifecycleAudit('state_change', 'disclaimer', $this->id(), $this->val('version'), ['isactive'=>$status]);
        }
        return $u1;
    }

    public function activateIt(): int
    {
        return $this->setStatus(self::STATUS_ACTIVE);
    }

    public function inactivateIt(): int
    {
        return $this->setStatus(self::STATUS_INACTIVE);
    }

    public function deleteIt(): int
    {
        global $_COMPANY, $_ZONE;

        $d1 = self::DBUpdate("
                DELETE FROM disclaimers 
                WHERE `companyid`={$_COMPANY->id()} 
                  AND `zoneid`={$_ZONE->id()} 
                  AND disclaimerid={$this->id}");

        if ($d1) {
            self::LogObjectLifecycleAudit('delete', 'disclaimer', $this->id(), $this->val('version'));

            $d2 = self::DBUpdate("
                DELETE FROM disclaimer_consents 
                WHERE companyid={$_COMPANY->id()} 
                  AND disclaimerid={$this->id}
                  ");
            if ($d2 > 0) {
                self::LogObjectLifecycleAudit('delete', 'disclaimer_consent', $this->id(), 0, ['disclaimerid' => $this->id(), 'comments' => 'deleted all consents']);
            }
        }

        return $d1;
    }

    public function version(): int
    {
        return intval($this->val('version'));
    }

    public function getDisclaimerBlockForLanguage(string $userLang = '')
    {
        $disclaimer_block = null;

        if (empty($userLang)) {
            $envLang = explode('.', Env::Get('LANG'));
            $userLang = !empty($envLang) ? $envLang[0] : 'en';
        }

        if (($disclaimer_json = $this->getDisclaimerArray())) {
            $disclaimer_block = $disclaimer_json[$userLang];

            if (!$disclaimer_block){
                $disclaimer_block = $disclaimer_json['en'];
                $userLang = 'en';
            }
        }
        if ($disclaimer_block){
            $disclaimer_block['language'] = $userLang;
        }
        return $disclaimer_block;
    }

    public static function IsDisclaimerAvailable(int $hookid, int $consent_contextid=0)
    {
        global $_USER;
        $disclaimer = self::GetDisclaimerByHook($hookid);
        if ($disclaimer) {
            if ($disclaimer->isConsentRequired()) {
                $user_consent = $disclaimer->getConsentForUserid($_USER->id(), $consent_contextid);
                if (!empty($user_consent) && $user_consent['disclaimer_version'] >= $disclaimer->val('consent_min_version')) {
                    // TODO Check: Didn't get logic behind $disclaimer->val('consent_min_version') field. So compared with $disclaimer->val('version') field 
                    // Consent already provided and it is current.
                    return false;
                }
                // Consent not provided or the old consent is stale.
                return true;
            }
            // Disclaimer without consent requirements is always available.
            return true;
        }
        return false;
    }

    public static function IsDisclaimerAvailableV2(int $disclaimerid, int $consent_contextid=0)
    {
        global $_USER;
        $disclaimer = self::GetDisclaimerById($disclaimerid);
        if ($disclaimer) {
            if ($disclaimer->isConsentRequired()) {
                $user_consent = $disclaimer->getConsentForUserid($_USER->id(), $consent_contextid);
                if (!empty($user_consent) && $user_consent['disclaimer_version'] >= $disclaimer->val('consent_min_version')) {
                    // TODO Check: Didn't get logic behind $disclaimer->val('consent_min_version') field. So compared with $disclaimer->val('version') field 
                    // Consent already provided and it is current.
                    return false;
                }
                // Consent not provided or the old consent is stale.
                return true;
            }
            // Disclaimer without consent requirements is always available.
            return true;
        }
        return false;
    }

    public function __toString()
    {
        return "Disclaimer " . parent::__toString();
    }

    private static function SanitizeDisclaimer(array $disclaimer): array
    {
        return array_filter($disclaimer, function ($v, $k) {
            global $_COMPANY;
            if ($v == 'enabled_by_default') return true;
            return (
                $_COMPANY->isValidLanguage($k) &&
                //isset($v['title']) &&
                isset($v['disclaimer']) &&
                isset($v['consent_input_value'])
            );
        }, ARRAY_FILTER_USE_BOTH);

    }

    /** Provides a string labs based on hookid from DISCLAIMER_HOOK_TRIGGERS
     * @param int $hookid
     * @return mixed|string
     */
	public static function GetDisclaimerTriggerLabel (int $hookid)
    {
        global $_COMPANY;
        $disclaimerLabelArr = array(
            self::DISCLAIMER_HOOK_TRIGGERS['LOGIN_FIRST'] => 'On First Login',
            self::DISCLAIMER_HOOK_TRIGGERS['GROUP_JOIN_BEFORE'] => 'Before User Joins a Group',
            self::DISCLAIMER_HOOK_TRIGGERS['GROUP_LEAVE_BEFORE'] => 'Before User Leaves a Group',
            // self::DISCLAIMER_HOOK_TRIGGERS['GROUP_JOIN_AFTER'] => 'After Group Join',
            self::DISCLAIMER_HOOK_TRIGGERS['SURVEY_CREATE_BEFORE'] => 'Before User Creates a Survey',
            // self::DISCLAIMER_HOOK_TRIGGERS['SURVEY_CREATE_AFTER'] => 'After Survey Create',
            // self::DISCLAIMER_HOOK_TRIGGERS['SURVEY_PUBLISH_BEFORE'] => 'Before Survey Publish',
            // self::DISCLAIMER_HOOK_TRIGGERS['SURVEY_PUBLISH_AFTER'] => 'After Survey Survey',
            self::DISCLAIMER_HOOK_TRIGGERS['EVENT_CREATE_BEFORE'] => 'Before User Creates an Event',
            // self::DISCLAIMER_HOOK_TRIGGERS['EVENT_CREATE_AFTER'] => 'After Event Create',
            self::DISCLAIMER_HOOK_TRIGGERS['EVENT_PUBLISH_BEFORE'] => 'Before Event Publish',
            // self::DISCLAIMER_HOOK_TRIGGERS['EVENT_PUBLISH_AFTER'] => 'Before Event Publish',

            self::DISCLAIMER_HOOK_TRIGGERS['POST_CREATE_BEFORE'] => 'Before User Creates an ' . Post::GetCustomName(),
            self::DISCLAIMER_HOOK_TRIGGERS['POST_PUBLISH_BEFORE'] => 'Before '. Post::GetCustomName() . ' Publish',
            self::DISCLAIMER_HOOK_TRIGGERS['DISCUSSION_CREATE_BEFORE'] => 'Before User Creates a Discussion',
            self::DISCLAIMER_HOOK_TRIGGERS['NEWSLETTER_CREATE_BEFORE'] => 'Before User Creates a Newsletter',
            self::DISCLAIMER_HOOK_TRIGGERS['NEWSLETTER_PUBLISH_BEFORE'] => 'Before Newsletter Publish',
            self::DISCLAIMER_HOOK_TRIGGERS['TEAMS__CIRCLE_CREATE_BEFORE'] => 'Before User Creates a ' . $_COMPANY->getAppCustomization()['teams']['name'] . ' > Circle',
            self::DISCLAIMER_HOOK_TRIGGERS['BUDGET_REQUEST_CREATE_BEFORE'] => 'Before User Creates a Budget',
            self::DISCLAIMER_HOOK_TRIGGERS['BUDGET_EXPENSE_CREATE_BEFORE'] => 'Before User Creates an Expense',
            self::DISCLAIMER_HOOK_LINKS['EVENT_RSVP'] => 'Event RSVP',
            self::DISCLAIMER_HOOK_TRIGGERS['DIRECT_MESSAGE_CREATE_BEFORE'] => 'Before User Creates a Direct Message',
        );
        return $disclaimerLabelArr[$hookid] ?? '';
    }
    public static function GetDisclaimerTypeLinkLabel (int $hookid)
    {
        global $_COMPANY;
        $disclaimerLabelArr = array(
            self::DISCLAIMER_HOOK_LINKS['EVENT_RSVP'] => 'Event RSVP'
        );
        return $disclaimerLabelArr[$hookid] ?? '';
    }


    public static function GetDisclaimersByIdCsv(?string $disclaimerids):array
    {
        global $_COMPANY, $_ZONE;
        if (empty($disclaimerids)) {
            return array();
        }

        $disclaimers = array();
        $rows = self::DBROGet("
            SELECT * 
            FROM disclaimers 
            WHERE companyid = {$_COMPANY->id()} 
              AND disclaimerid IN({$disclaimerids})
              AND isactive=1"
        );

        if (!empty($rows)) {
            foreach ($rows as $row ){
                $disclaimers[] =  new Disclaimer($row['disclaimerid'], $row['companyid'], $row);
            }
        }
        return $disclaimers;
    }


    public static function IsAllWaiverAccepted(string $disclaimerids, int $contextId)
    {
        global $_USER; 
        $allDisclaimers = self::GetDisclaimersByIdCsv($disclaimerids);
        if (!empty($allDisclaimers)) {
            foreach($allDisclaimers as $disclaimer){
                if($disclaimer->val('consent_required') == 1){
                    $isUserConsentAvailable = self::IsDisclaimerAvailableV2($disclaimer->val('disclaimerid'),$contextId);
                    if ($isUserConsentAvailable){
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function getDisclaimerByVersion (int $version) {
        global $_COMPANY, $_ZONE;
        $r1 = self::DBGet("
            SELECT * 
            FROM disclaimer_versions 
            WHERE companyid = {$_COMPANY->id()} 
              AND disclaimerid={$this->id()}
              AND version={$version}"
        );

        if (!empty($r1)) {
            return new Disclaimer($r1[0]['disclaimerid'], $r1[0]['companyid'], $r1[0]);
        }
        return null;
    }

    /**
     * Backs up disclaimer version in disclaimer_versions table for audit purposes.
     * @return int|string
     */
    public function backupDisclaimerVersion()
    {
        global $_COMPANY, $_ZONE;

        // Housekeeping -check if there are disclaimers associated with the version previous to current, if not delete it.
        // Note: here we are deleted two version old log, e.g. if the current version of disclaimer is 5, then we will delete
        // delete disclaimer version 4 if there are not consents available for it. In the next step we will backup the current version 5.
        $current_version_row = self::DBGet(" SELECT version FROM disclaimers WHERE companyid={$_COMPANY->id()} AND disclaimerid={$this->val('disclaimerid')}");
        if (!empty($current_version_row) && $current_version_row[0]['version'] > 2) {
            $previous_version = $current_version_row[0]['version'] - 1;
            $previous_version_consent_exists = self::DBGet("SELECT 1 FROM disclaimer_consents WHERE companyid={$_COMPANY->id()} AND disclaimerid={$this->val('disclaimerid')} AND disclaimer_version={$previous_version} LIMIT 1;");
            if (empty($previous_version_consent_exists)) {
                self::DBMutate("DELETE FROM disclaimer_versions WHERE companyid={$_COMPANY->id()} AND disclaimerid={$this->val('disclaimerid')} AND version={$previous_version}");
            }
        }

        // Copy directly from database table.
        $retVal = self::DBMutate("
            INSERT IGNORE INTO `disclaimer_versions` (`disclaimerid`, `companyid`, `zoneid`, `hookid`, `version`, `disclaimer`, `createdon`, `createdby`) 
            SELECT `disclaimerid`, `companyid`, `zoneid`, `hookid`, `version`, `disclaimer`, `createdon`, `createdby` FROM disclaimers WHERE companyid={$_COMPANY->id()} AND disclaimerid={$this->val('disclaimerid')}
        ");
        return $retVal;
    }

}
