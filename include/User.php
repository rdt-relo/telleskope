<?php

// Do no use require_once as this class is included in Company.php.

use phpseclib3\Common\Functions\Strings;

class DuplicateAccountException extends Exception
{
    public $user1_id;
    public $user2_id;
    public function __construct(int $user1_id, int $user2_id, string $message, int $code = 0, Throwable $previous = null) {
        $this->user1_id = $user1_id;
        $this->user2_id = $user2_id;
        parent::__construct($message, $code, $previous);
    }
    public function getUser1Id(): int
    { return $this->user1_id;}
    public function getUser2Id(): int
    { return $this->user2_id;}
}

/**
 * User preferences, while providing list of valid preferences, it also provides a mapping into the JSON column of DB
 * For setting DB JSON values convert camel case upper case characters to key
 * If the enum type has ZONE_ in front of it, then the preference is zone specific.
 */
enum UserPreferenceType : string
{
    case MyWebConferenceURL = 'MWCURL';
    case MyWebConferenceDetail = 'MWCDETAIL';
    case ZONE_ShowMyGroups = 'SMG';
    case ZONE_SelectedGroupCategory = 'SGC';
}

class User extends Teleskope
{

    //const STATUS_ACTIVE = 1;
    //const STATUS_INACTIVE = 0;

    const USER_VERIFICATION_STATUS = array(
        'VERIFIED' => 1,
        'NOT_VERIFIED' => 2
    );

    const USER_STATUS_LABEL = array (
        1 => 'Active',
        3 => 'Account Locked',
        100 => 'Pending Deletion (System Initiated)',
        101 => 'Pending Deletion (User Initiated)',
        102 => 'Account Blocked'
    );

    const DATE_FORMATS = array(
        'Y-m-d' =>'YYYY-MM-DD',
        'd-m-Y' =>'DD-MM-YYYY',
        'm-d-Y' =>'MM-DD-YYYY',
        'd/m/y' =>'DD/MM/YY',
        'm/d/y' =>'MM/DD/YY',
        'M d, Y'=>'MON DD, YYYY',
//        'F d, Y'=>'MONTH DD, YYYY',
    );

    const TIME_FORMATS = array(
        'h:i a' => '12:00',
        'H:i'   => '24:00',
        'H:i a' => '24:00+',
    );

    private $groupleadrecords = null;
    private $chapterleadrecords = null;
    private $channelleadrecords = null;
    private $zoneadminrecords = null;
    private $pointsbalance = null;
    private $userpreferences = null;
    private $groups_restricted_for_content;
    private $approvaldata = null;

    private $session_delegatedzones = null;

    private const USER_PROFILE_SECRET_IV = 'BDkquByeGU06SIZzIeQoFTXLMHXQhV6n9hHpQIpQ';

    /**
     * The user is fetched from cache which is populated from RO copy of the database.
     * @param int $id
     * @param bool $from_master_db if set to true, then a fresh copy of user is loaded from master db and cache is updated
     * @return false|mixed|User|null
     */
    public static function GetUser(int $id, bool $from_master_db = false)
    {
        $obj = null;
        global $_COMPANY;
        /* @var Company $_COMPANY */
        if ($id){
            $key = "USR:{$id}";
            if ($from_master_db || ($obj = $_COMPANY->getFromRedisCache($key)) === false) {
                $obj = null; // Reset $obj to initial value
                if ($from_master_db) { // Get from Master DB
                    $r1 = self::DBGet("SELECT * FROM users WHERE `userid`='{$id}' AND `companyid`='{$_COMPANY->id()}'");
                } else { // Get from ReadOnly copy.
                    $r1 = self::DBROGet("SELECT * FROM users WHERE `userid`='{$id}' AND `companyid`='{$_COMPANY->id()}'");
                }
                if (!empty($r1)) {
                    //unset($r1[0]['extendedprofile']);
                    $obj = new User($id, $_COMPANY->id(), $r1[0]);
                    $_COMPANY->putInRedisCache($key, $obj, 86400);
                } else {
                    $_COMPANY->putInRedisCache($key, null, 2); // Put in cache for 2 seconds to optimize for looped operations
                }
            }
        }
        return $obj;
    }

    /**
     * This method should be used only for getting the user as part of session load, e.g. in head.php
     * Note: This method assumes $_SESSION has been instantiated. ache expires after one hour
     * @param int $id
     */
    public static function GetUserFromSessionCache (int $id)
    {
        global $_COMPANY;
        if (empty($_SESSION['__cachedUser']) ||
            $_SESSION['__cachedUser_expiry'] < time()
        ) {
            $_SESSION['__cachedUser'] = self::GetUser($id, true); // Always get the latest copy from RW instance of DB
            $_SESSION['__cachedUser']->fields['password'] = ''; // Remove it
            $_SESSION['__cachedUser_expiry'] = time() + 300; // Expire after 300 seconds
        }
        return $_SESSION['__cachedUser'];
    }

    private static function ApplyTransformations(mixed $attributeValue, array $transformations): mixed
    {
        foreach ($transformations as $func) {
            switch ($func) {
                case 'userhash':
                    $attributeValue = self::GetUserHash($attributeValue);
                    break;
                case 'strtolower':
                    $attributeValue = strtolower($attributeValue);
                    break;
                case 'strtoupper':
                    $attributeValue = strtoupper($attributeValue);
                    break;
                case 'strtotime':
                    $attributeValue = strtotime($attributeValue) ?: $attributeValue;
                    break;
                case 'time_to_utc_year':
                    $attributeValue = gmdate('Y', $attributeValue) ?: $attributeValue;
                    break;
                case 'time_to_utc_date':
                    $attributeValue = gmdate('Y-m-d', $attributeValue) ?: $attributeValue;
                    break;
                case 'time_to_utc_time':
                    $attributeValue = gmdate('H-i-s', $attributeValue) ?: $attributeValue;
                    break;
                case 'time_to_utc_datetime':
                    $attributeValue = gmdate('Y-m-d H:i:s', $attributeValue) ?: $attributeValue;
                    break;
                case 'time_to_utc_day':
                    $attributeValue = intval(gmdate('z', $attributeValue)) + 1;
                    break;
                case 'time_to_age_years':
                    $date1 = new DateTime("@$attributeValue"); //starting seconds
                    $date2 = new DateTime(); // ending seconds
                    $interval = date_diff($date1, $date2); //the time difference
                    $attributeValue = intval($interval->format('%y')); // converts to number of years
                    break;
                case 'intval':
                    $attributeValue = intval($attributeValue);
                    break;
                case 'boolval':
                    $attributeValue = boolval($attributeValue);
                    break;
                case 'strval':
                    $attributeValue = strval($attributeValue);
                    break;
            }
        }
        return $attributeValue;
    }

    /**
     * Return an array of userids of all user in the zone who are not marked for deletion.
     * @param int $zoneid
     * @return array
     */
    public static function GetAllUsersInZoneWhoAreNotMarkedForDeletion(int $zoneid) : array
    {
        global $_COMPANY;
        # Not marked for deletion means isactive < 100; it includes users who are temporarily disabled
        $select = "
            SELECT  `userid`
            FROM    `users`
            WHERE   `companyid` = {$_COMPANY->id()}
            AND     FIND_IN_SET({$zoneid}, `zoneids`)
            AND     isactive < 100
        ";

        return self::DBROGet($select);
    }

    public function clearSessionCache() {
        global $_COMPANY;
        if (isset($_SESSION)) {
            unset($_SESSION['__cachedUser_expiry']);
            unset($_SESSION['__cachedUser']);
        }
        $_COMPANY->expireRedisCache("USR:{$this->id()}");
    }

    public static function GetEmptyUser(int $id=0)
    {
        global $_COMPANY;
        /* @var Company $_COMPANY */
        $companyid = $_COMPANY->id();
       return new User($id,$companyid,array('firstname'=>'','lastname'=>'','email'=>null,'externalid'=>null,'jobtitle'=>'','department'=>0,'homeoffice'=>0,'companyid'=>$companyid));
    }

    public static function GetUserByEmail(string $email, bool $from_master_db = false)
    {
        $obj = null;
        global $_COMPANY; /* @var Company $_COMPANY */
        if (!empty($email)) {
            if ($from_master_db) { // Fetch from RW copy
                $r = self::DBGetPS("SELECT * FROM users WHERE companyid=? AND (email=?)", 'ix', $_COMPANY->id(), $email);
            } else { // Fetch from RO only copy.
                $r = self::DBROGetPS("SELECT * FROM users WHERE companyid=? AND (email=?)", 'ix', $_COMPANY->id(), $email);
            }
            if (count($r)) {
                $obj = new User($r[0]['userid'], $_COMPANY->id(), $r[0]);
            }
        }
        return $obj;
    }

    /**
     * Gets user by external email address.
     * @param string $external_email
     * @return User|null
     */
    public static function GetUserByExternalEmail(string $external_email, bool $from_master_db = false)
    {
        $obj = null;
        global $_COMPANY; /* @var Company $_COMPANY */
        $external_email = trim($external_email);
        if (!empty($external_email)) {
            if ($from_master_db) { // From RW copy of database
                $r = self::DBGetPS("SELECT * FROM users WHERE companyid=? AND (external_email=?)", 'ix', $_COMPANY->id(), $external_email);
            } else { // From RO copy of database
                $r = self::DBROGetPS("SELECT * FROM users WHERE companyid=? AND (external_email=?)", 'ix', $_COMPANY->id(), $external_email);
            }
            if (count($r)) {
                $obj = new User($r[0]['userid'], $_COMPANY->id(), $r[0]);
            }
        }
        return $obj;
    }

    /**
     * This method loads the user by ExternalUserName. ExternalUserName is most often in email form and is also known
     * as userPrincipalName. In some companies the users will have second email address which can be used for outgoing
     * emails. For such companies ExternalUserName can be used to store that email address and it can be used for
     * comparison purposes as second method of loading user when processing RSVPs.
     * @param string $externalUserName
     * @return User|null
     */
    public static function GetUserByExternalUserName(string $externalUserName, bool $from_master_db = false)
    {
        $obj = null;
        global $_COMPANY;
        if ($from_master_db) { // From RW copy of database
            $r = self::DBGetPS("SELECT * FROM users WHERE companyid=? AND (externalusername=?) LIMIT 2", 'is', $_COMPANY->id(), $externalUserName);
        } else { // From RO copy of database
            $r = self::DBROGetPS("SELECT * FROM users WHERE companyid=? AND (externalusername=?) LIMIT 2", 'is', $_COMPANY->id(), $externalUserName);
        }
        if (count($r) === 1) { // Only if there is a single match
            $obj = new User($r[0]['userid'], $_COMPANY->id(), $r[0]);
        }
        return $obj;
    }

    /**
     * @deprecated *** THIS METHOD IS ONLY USED FOR RSVP PROCESSING. DO NOT USE IT ANYWHERE ELSE ***
     * @param string $email
     * @return User|null
     */
    public static function GetUserByEmailUsingAnyConfiguredDomain(string $email)
    {
        $obj = null;
        global $_COMPANY;
        if ($_COMPANY->isValidEmail($email)) {
            $emailWithoutDomain = explode("@",$email)[0];
            $emailFilter = $emailWithoutDomain.'@%';
            $r = self::DBROGetPS("SELECT * FROM users WHERE companyid=? AND (email like ?) LIMIT 2", 'ix', $_COMPANY->id(), $emailFilter);
            if (count($r) === 1) { // Only if there is a single match
                $obj = new User($r[0]['userid'], $_COMPANY->id(), $r[0]);
            }
        }
        return $obj;
    }

    public static function GetUserByExternalId(string $externalId, bool $from_master_db = false)
    {
        $obj = null;
        global $_COMPANY; /* @var Company $_COMPANY */

        if (!empty($externalId)) {
            $externalId = $externalId . ':' . $_COMPANY->id();

            if ($from_master_db) { // From RW copy of database
                $r = self::DBGetPS('SELECT * FROM users WHERE companyid=? AND (externalid=?)', 'is', $_COMPANY->id(), $externalId);
            } else { // From RO copy of database
                $r = self::DBROGetPS('SELECT * FROM users WHERE companyid=? AND (externalid=?)', 'is', $_COMPANY->id(), $externalId);
            }
            if (count($r)) {
                $obj = new User($r[0]['userid'], $_COMPANY->id(), $r[0]);
            }
        }
        return $obj;
    }

    public static function GetUserByAadOid(string $aad_oid, bool $from_master_db = false)
    {
        $obj = null;
        global $_COMPANY; /* @var Company $_COMPANY */

        if (!empty($aad_oid)) {
            if ($from_master_db) { // From RW copy of database
                $r = self::DBGetPS('SELECT * FROM users WHERE companyid=? AND (aad_oid=?)', 'is', $_COMPANY->id(), $aad_oid);
            } else { // From RO copy of database
                $r = self::DBROGetPS('SELECT * FROM users WHERE companyid=? AND (aad_oid=?)', 'is', $_COMPANY->id(), $aad_oid);
            }
            if (count($r)) {
                $obj = new User($r[0]['userid'], $_COMPANY->id(), $r[0]);
            }
        }
        return $obj;
    }

    // Utility function to convert DBRec to User
    public static function ConvertDBRecToUser ($rec) {
        global $_COMPANY;
        $obj = null;
        $u = (int)$rec['userid'];
        $c = (int)$rec['companyid'];
        if ($u && $c && $c === $_COMPANY->id() && isset($rec['email']))
            $obj = new User($u, $c, $rec);
        return $obj;
    }

    /**
     * This method use userid and session id to restore app session.
     * Note: The global $_COMPANY, $_ZONE will be set to session values, and User object will be returned.
     * @param int $userid
     * @param int $sessionid
     * @return User|null
     */
    public static function RestoreSession(int $userid, int $sessionid): ?User
    {
        global $_COMPANY, $_ZONE;
        $session = self::DBROGet("SELECT * FROM users_api_session WHERE api_session_id='{$sessionid}' AND `userid`='{$userid}'");
        if (!empty($session)) {

            // Check if session expired or not. Session can expired due to either of the following cases:
            // (a) Max session time out (i.e. mobile_session_max) , or
            // (b) if the daily cutoff has exceeded (i.e. mobile_session_logout_time_in_min_utc)
            //
            $nowTime = time();
            $security =  (Company::GetCompany($session[0]['companyid']))->getCompanySecurity();
            $session_start_date_time = strtotime($session[0]['modified']." UTC");
            $expiry_time_based_on_session_length = $session_start_date_time + $security['mobile_session_max'] * 60; // Default if mobile_session_logout_time_in_min_utc not set
            $expiry_time_based_on_clock_time = $nowTime + 36000;
            if ($security && $security['mobile_session_logout_time_in_min_utc']){
                $session_start_date = explode(' ', $session[0]['modified'])[0];
                $mobile_session_logout_time_hr = (int)(intval($security['mobile_session_logout_time_in_min_utc'])/60);
                $mobile_session_logout_time_min = (int)(intval($security['mobile_session_logout_time_in_min_utc'])%60);
                $expiry_time_based_on_clock_time = strtotime($session_start_date . " {$mobile_session_logout_time_hr}:{$mobile_session_logout_time_min}:00 UTC");
                if ($session_start_date_time > $expiry_time_based_on_clock_time) {
                    $expiry_time_based_on_clock_time += 86400; // Add one day
                }
            }
            // If expired then delete the session
            if (
                ($nowTime > $expiry_time_based_on_session_length) ||
                ($nowTime > $expiry_time_based_on_clock_time)
            ) { // Expired session
                self::DBMutate("DELETE FROM users_api_session WHERE api_session_id={$session[0]['api_session_id']}");
                return null;
            } else {
                $_COMPANY = Company::GetCompany($session[0]['companyid']);
                $_ZONE = $_COMPANY->getZone($session[0]['zoneid']);
                return self::GetUser($session[0]['userid']);
            }
        }
        return null;
    }

    /**
     * This method is similar to GetUserByAadIdOrEmail, with the only difference here is we check by external id.
     * @param string $external_id
     * @param string $email
     * @return false|mixed|User|null
     * @throws DuplicateAccountException
     */
    public static function GetUserByExternalIdOrEmail(string $external_id, string $email)
    {
        $user_obj_by_external_id = self::GetUserByExternalId($external_id);

        // Rest of the procesing is to validate & update email based user or aad oid user if it has mismatch with
        // email based user, if email is empty then skip rest of the flow.
        if (empty($email)) {
            return $user_obj_by_external_id;
        }

        if ($user_obj_by_external_id) {
            // External object found, validate if it is correct.
            if (strcasecmp($user_obj_by_external_id->val('email'), $email) === 0) {
                // Perfect match found;
                // [Whitebox QA - ok]
                return $user_obj_by_external_id;
            }

            // Else
            // See if there is another user with the provided email
            $user_obj_by_email = self::GetUserByEmail($email);

            // Another user with same email exists, try to resolve by merging the users and return the merged user
            if ($user_obj_by_email && ($user_obj_by_email->id != $user_obj_by_external_id->id)) {

                $merge_status = User::MergeUsers($user_obj_by_email->id, $user_obj_by_external_id->id);
                if (!$merge_status['status']) {
                    throw new DuplicateAccountException(
                        $user_obj_by_email->id,
                        $user_obj_by_external_id->id,
                        "Different accounts found for email [{$email}] and externalid [{$external_id}]"
                    );
                }
                //[Whitebox QA - ok]
                // Since we just finished the merge, get user from master db by setting the second paramter to true.
                return User::GetUser($merge_status['userid'], true);
            }

            // No other user found, update this user
            // [Whitebox QA - ok]
            $user_obj_by_external_id->updateEmail($email);
            return $user_obj_by_external_id;

        } else {

            // User not found with External id, try finding a user with email
            $user_obj_by_email = self::GetUserByEmail($email);

            // The following section checks if the user found by email has a different external id, if so we can throw
            // an error.
            // Update on 08/17/2023 - instead of throwing an error, we will allow user to continue the session
            // as $user_obj_by_email->updateExternalId($external_id); in the following section will update the
            // external id only if its not set. This way we are priortizing user login over correctness of externalid.
            if (!empty($external_id) && $user_obj_by_email?->val('externalid')) {
                // Conflicting externalid
//                throw new DuplicateAccountException(
//                    $user_obj_by_email->id,
//                    0,
//                    "User account with email [{$email}] has a differnt externalid [{$user_obj_by_email?->getExternalId()}], the new one is [{$external_id}]"
//                );
                Logger::Log("User account with email [{$email}] has a differnt externalid [{$user_obj_by_email?->getExternalId()}], the new one is [{$external_id}], not updating leaving as is to be resolved manually", Logger::SEVERITY['WARNING_ERROR']);
            }

            // If user is found by email and external id is provided, then return the user as this external id is
            // not used anywhere
            //[Whitebox QA - ok]
            if (!empty($external_id) && $user_obj_by_email)
                $user_obj_by_email->updateExternalId($external_id);

            return $user_obj_by_email;
        }

        //return null;
    }

    /**
     * @param string $externalId
     * @param string $email
     * @param string $firstname
     * @param string $lastname
     * @param string $zoneids
     * @param string $externalEmail
     * @return User|null
     */
    public static function GetOrCreateUserByExternalId(string $externalId, string $email, string $firstname, string $lastname, string $zoneids ='', ?string $externalEmail=null)
    {
        $obj = null;
        global $_COMPANY; /* @var Company $_COMPANY */

        //$externalId = preg_replace( '/[^[:print:]]/', '', trim($externalId));
        if (empty($externalId)) {
            return null;
        }

        // Since we will be making user create/update decisions, lets use master db for fetching GetUserByExternalId or
        // GetUserByEmail

        if (($obj = self::GetUserByExternalId($externalId, true)) === null) {
            // User not found.
            // Next we can try to load it by email and assign if the externalid is null.
            // If we still cannot load, then we can create a new user.
            $userByEmailObj = self::GetUserByEmail($email, true);
            if (!$userByEmailObj) {
                $obj = self::CreateNewUser($firstname, $lastname, $email, '', User::USER_VERIFICATION_STATUS['VERIFIED']);
            } else {
                if ($userByEmailObj->val('externalid')) {
                    Logger::Log("GetOrCreateUserByExternalId: User with email {$email} has incorrect externalid: existing externalid in database = {$userByEmailObj->getExternalId()}, provided externalid = {$externalId}, resetting to new externalid", Logger::SEVERITY['WARNING_ERROR']);
                    $userByEmailObj->updateExternalId(null); // Just setting it to null here, it will be reset to new external id in next few steps.
                }
                $obj = $userByEmailObj;
            }
            // If we still cannot get the user, then exit
            if ($obj === null) {
                return null;
            }
            // Next update the external id if externalid is empty
            $eid = $externalId;
            $externalId = $externalId.':'.$_COMPANY->id();
            $zoneids_list = $obj->val('zoneids');
            if (!empty($zoneids)) {
                if (empty($zoneids_list)) {
                    $zoneids_list = $zoneids;
                } else {
                    $zoneids_arr = explode(',', $zoneids_list);
                    $zoneids_arr[] = $zoneids;
                    $zoneids_list = implode(',',array_unique($zoneids_arr));
                }
            }
            if (empty($obj->val('externalid')) && self::DBUpdatePS("UPDATE users SET externalid=?,zoneids=?,modified=NOW() WHERE userid=?",'ssi',$externalId,$zoneids_list,$obj->id)) {
                User::GetUser($obj->id, true); // Refresh cache after loading from master DB
                $obj->fields['externalid'] = $externalId;

                self::LogObjectLifecycleAudit('update', 'user', $obj->id(), 0, [
                    'ids' => $obj->getEncryptedIdentityForLogging(),
                    'oper_details' => 'updated externalid',
                ]);

            } else {
                Logger::Log("GetOrCreateUserByExternalId: Unable to set externalid = {$eid} for user with email=$email, userid=$obj->id");
                return null;
            }
        }

        if ($externalEmail) {
            $obj->updateExternalEmailAddress($externalEmail);
        }

        return $obj;
    }

    /**
     * This method is similar to GetUserByExternalIdOrEmail, with the only difference here is we check by aad_oid.
     * @param string $aad_oid
     * @param string $email
     * @return false|mixed|User|null
     */
    public static function GetUserByAadIdOrEmail(string $aad_oid, string $email)
    {
        $user_obj_by_aad_oid = self::GetUserByAadOid($aad_oid);

        // Rest of the procesing is to validate & update email based user or aad oid user if it has mismatch with
        // email based user, if email is empty then skip rest of the flow.
        if (empty($email)) {
            return $user_obj_by_aad_oid;
        }

        if ($user_obj_by_aad_oid) {
            // Aad OID object found, validate if it is correct.
            if (strcasecmp($user_obj_by_aad_oid->val('email'), $email) === 0) {
                // Perfect match found;
                // [Whitebox QA - ok]
                return $user_obj_by_aad_oid;
            }

            // Else
            // See if there is another user with the provided email
            $user_obj_by_email = self::GetUserByEmail($email);

            // Another user with same email exists, try to resolve by merging the users and return the merged user
            if ($user_obj_by_email && ($user_obj_by_email->id != $user_obj_by_aad_oid->id)) {
                $merged_user_obj = null;
                $merge_status = User::MergeUsers($user_obj_by_email->id, $user_obj_by_aad_oid->id);
                if (!$merge_status['status']) {
                    throw new DuplicateAccountException(
                        $user_obj_by_email->id,
                        $user_obj_by_aad_oid->id,
                        "Different accounts found for email [{$email}] and AAD OID [{$aad_oid}]"
                    );
                }
                //[Whitebox QA - ok]
                // Since we just merged the user, fetch one from Master DB.
                return User::GetUser($merge_status['userid'], true);
            }

            // No other user found, update this user
            // [Whitebox QA - ok]
            $user_obj_by_aad_oid->updateEmail($email);
            return $user_obj_by_aad_oid;

        } else {
            // User not found with AAD oid, try finding a user with email
            $user_obj_by_email = self::GetUserByEmail($email);

            if (!empty($aad_oid) && $user_obj_by_email?->val('aad_oid')) {
                // Conflicting aad_oid
                throw new DuplicateAccountException(
                    $user_obj_by_email->id,
                    0,
                    "User account with email [{$email}] has a differnt aad_oid [{$user_obj_by_email?->val('aad_oid')}], the new one is [{$aad_oid}]"
                );
            }

            // If user is found by email and aad_oid is set , then update aad_oid
            //[Whitebox QA - ok]
            if (!empty($aad_oid) && $user_obj_by_email)
                $user_obj_by_email->updateAadOid($aad_oid);

            return $user_obj_by_email;
        }

        //return null;
    }

    /**
     * Updates aad oid of the user. For security reasons, by default aad oid is only updated if it is empty.
     * @param string $aad_oid
     * @param bool $onlyUpdateIfEmpty
     * @return int
     */
    public function updateAadOid (string $aad_oid, bool $onlyUpdateIfEmpty = true) : int
    {
        global $_COMPANY;

        if ($onlyUpdateIfEmpty && !empty($this->val('aad_oid'))) { // Already set
            return 0;
        }

        if ($aad_oid == $this->val('aad_oid')) {
            return 1;
        }

        $retVal = self::DBUpdatePS("UPDATE users SET aad_oid=? WHERE companyid=? AND userid=?",'sii', $aad_oid, $_COMPANY->id(), $this->id);
        if ($retVal) {

            User::GetUser($this->id, true); // Refresh cache after loading from master DB
            $this->fields['aad_oid'] = $aad_oid;

            self::LogObjectLifecycleAudit('update', 'user', $this->id(), 0, [
                'ids' => $this->getEncryptedIdentityForLogging(),
                'oper_details' => 'updated aad_oid',
            ]);

        }
        return $retVal;
    }

    /**
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param string $picture
     * @param int $verificationstatus
     * @return User|null return null if user cannot be created
     */
    public static function CreateNewUser(string $firstname, string $lastname, string $email, string $picture, int $verificationstatus)
    {

        $obj = null;
        global $_COMPANY; /* @var Company $_COMPANY */

        if (!$_COMPANY->isValidEmail($email))
            return $obj;

        $companyid = $_COMPANY->id();

//        $firstname = preg_replace( '/[^[:print:]]/', '', trim($firstname)); Commented to support UTF-8
//        $lastname = preg_replace( '/[^[:print:]]/', '', trim($lastname)); Commented to support UTF-8
//        $picture = preg_replace( '/[^[:print:]]/', '', trim($picture)); Commented to support UTF-8

        // Using DBMutatePS instead of DBInsertPS as we do not want the process to die if Insert Fails. We want to
        // report the error with proper error handline.
        $no_of_rows = self::DBMutatePS("INSERT INTO users
                                (firstname, lastname, email, companyid, verificationstatus, 
                                 notification, createdon, modified,validatedon, 
                                 isactive,signuptype,picture) VALUES 
                                (?,?,?,?,?,'1',now(),now(),now(),'1','1',?)",'ssxiis',
                                    $firstname,$lastname,$email,$companyid,$verificationstatus,$picture);

        if ($no_of_rows) {
            $obj = self::GetUserByEmail($email, true);
            if ($obj) {

                self::LogObjectLifecycleAudit('create', 'user', $obj->id(), 0, [
                    'ids' => $obj->getEncryptedIdentityForLogging()
                ]);

                $check = self::DBGetPS("SELECT leadinviteid, companyid, groupid, email, invitedby FROM leadsinvites WHERE companyid=? AND email=?", 'is', $companyid, $email);

                //$check_count = count($check);
                foreach ($check as $c) {
                    $leads = self::DBInsert("INSERT INTO `groupleads`(`groupid`, `userid`, `assignedby`, `assigneddate`, `isactive`) VALUES ('{$c['groupid']}','{$obj->id()}','{$c['invitedby']}',now(),'1')");
                    $delete = self::DBUpdate("DELETE FROM `leadsinvites` WHERE `leadinviteid`='{$c['leadinviteid']}'");
                }
            }
        }

        return $obj;
    }

    public function getExternalId():string
    {
        return explode(':',$this->val('externalid') ?? '')[0];
    }

    public static function ExtractExternalId(string $external_id): string
    {
        global $_COMPANY;
        $parts = explode(':', $external_id);
        if ($parts[1] == $_COMPANY->id()) {
            return $parts[0];
        }
        return '';
    }

    public function updateExternalUsername(?string $newExternalUsername)
    {
        global $_COMPANY;
        $retVal = 0;

        if ($newExternalUsername === null)
            $retVal = self::DBMutate("UPDATE users set externalusername=null WHERE companyid={$_COMPANY->id()} AND userid={$this->id()}");
        else
            $retVal = self::DBMutatePS("UPDATE users set externalusername=? WHERE companyid=? AND userid=?",'xii', $newExternalUsername, $_COMPANY->id(), $this->id());

        if ($retVal) {
            $this->fields['externalusername'] = $newExternalUsername;
            User::GetUser($this->id, true); // Refresh cache after loading from master DB

            self::LogObjectLifecycleAudit('update', 'user', $this->id(), 0, [
                'ids' => $this->getEncryptedIdentityForLogging(),
                'oper_details' => 'updated externalusername',
            ]);
        }

        return $retVal;
    }

    public function updateEmail(?string $newEmail)
    {
        global $_COMPANY;
        $retVal = 0;

        if ($newEmail === null)
            $retVal = self::DBMutate("UPDATE users set email=null WHERE companyid={$_COMPANY->id()} AND userid={$this->id()}");
        else
            $retVal = self::DBMutatePS("UPDATE users set email=? WHERE companyid=? AND userid=?",'xii', $newEmail, $_COMPANY->id(), $this->id());

        if ($retVal) {
            $this->fields['email'] = $newEmail;
            User::GetUser($this->id, true); // Refresh cache after loading from master DB

            self::LogObjectLifecycleAudit('update', 'user', $this->id(), 0, [
                'ids' => $this->getEncryptedIdentityForLogging(),
                'oper_details' => 'updated email',
            ]);
        }

        return $retVal;
    }

    public function getAadOid():string
    {
        return $this->val('aad_oid');
    }

    /**
     * Deletes users who were marked for deletion. Note this function is dependent upon $_COMPANY->getUserLifecylce()['allow_delete']
     * setting to be turned on.
     * @param int $days
     * @return void
     */
    public static function DeleteUsersMarkedForDeletion(int $days=30): void
    {
        global $_COMPANY; /* @var Company $_COMPANY */

        if ($_COMPANY && $_COMPANY->getUserLifecycleSettings()['allow_delete']) {
            $days = max($days, intval($_COMPANY->getUserLifecycleSettings()['delete_after_days']));
            // Delete only the users who are marked for deletion (purge state) and have been in that state for more than 30 days)
            $deleteUsers = self::DBGet("SELECT userid FROM users WHERE companyid='{$_COMPANY->id()}' AND isactive='" . self::STATUS_PURGE . "' AND (modified < now() - interval {$days} day)");
            foreach ($deleteUsers as $deleteUser) {
                $d = User::GetUser($deleteUser['userid'], true);
                $d->delete();
            }

            $deleteEmailBouncebackUsers = self::DBGet("SELECT userid FROM users WHERE companyid='{$_COMPANY->id()}' AND isactive='" . self::STATUS_UNDER_REVIEW . "' AND (modified < now() - interval {$days} day)");
            foreach ($deleteEmailBouncebackUsers as $deleteUser) {
                $d = User::GetUser($deleteUser['userid'], true);
                $d->delete();
            }

            $deleteCleanUsers = self::DBGet("SELECT userid FROM users WHERE companyid='{$_COMPANY->id()}' AND isactive='" . self::STATUS_WIPE_CLEAN . "' AND (modified < now() - interval {$days} day)");
            foreach ($deleteCleanUsers as $deleteUser) {
                $d = User::GetUser($deleteUser['userid'], true);
                $d->delete(deleteClean: true);
            }
        }
    }

    public static function PurgeUsersNotValidatedSince (int $days): void
    {
        global $_COMPANY; /* @var Company $_COMPANY */

        if ($_COMPANY) {
            // Delete only the users who are marked for deletion (purge state) and have been in that state for more than 30 days)
            // Note we are fetching only userid,email,externalid,validatedon,modified,isactive to optimize loading too much data.
            $purgeUsers = self::DBGet("SELECT userid,email,externalid,validatedon,modified,isactive FROM users WHERE companyid='{$_COMPANY->id()}' AND (isactive < 100 AND validatedon < now() - interval {$days} day)");
            $purgeUserCount = count($purgeUsers);
            $allUserCount = self::DBGet("SELECT count(1) AS cc FROM users WHERE companyid='{$_COMPANY->id()}'")[0]['cc'];
            if (intval($purgeUserCount/$allUserCount*100) > 5) {
                Logger::Log("Fatal Error in PurgeUsersNotValidatedSince: max user threshold breached. Unable to delete {$purgeUserCount} users from {$allUserCount}");
                return;
            }
            foreach ($purgeUsers as $purgeUser) {
                $p = new User($purgeUser['userid'], $_COMPANY->id(), $purgeUser);
                $p->purge();
            }
        }
    }

    /**
     * This method set the extended profile field to null from local copy of user fields. It *does not* update the
     * copy of user in the database.
     * @return void
     */
    public function removeExtendedProfile()
    {
        $this->fields['extendedprofile'] = null;
    }

    /**
     * Use this method for updating user profile as reported by user.
     */
    public function updateProfileSelf (string $firstname, string $lastname, string $pronouns, string $language='', string $timezone='', string $date_format = 'Y-m-d', string $time_format = 'h:m a'):int
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        $updateStr = 'isactive=1, modified=now()';
        $types = '';
        $vals = array();

        $firstname = Sanitizer::SanitizePersonName($firstname);
        $lastname =  Sanitizer::SanitizePersonName($lastname);
        $pronouns =  Sanitizer::SanitizePersonPronouns($pronouns);

        if ($firstname != trim($this->fields['firstname']) || $lastname != trim($this->fields['lastname']) || $pronouns != trim($this->fields['pronouns'] ?? '')) {
            // If the user is changing their firstname, lastname or pronouns change the user_managed_profile to on.
            $updateStr .= ',user_managed_profile=1';
        }

        if (!empty($firstname) && ($firstname != $this->fields['firstname'])) {
            $updateStr .= ', firstname=?';
            $types .= 's';
            $vals[] = $firstname;
        }

        if (!empty($lastname) && ($lastname != $this->fields['lastname'])) {
            $updateStr .= ', lastname=?';
            $types .= 's';
            $vals[] = $lastname;
        }

        if (($pronouns != $this->fields['pronouns'])) {
            $updateStr .= ', pronouns=?';
            $types .= 's';
            $vals[] = trim($pronouns);
        }

        if (!empty($language) && ($language != $this->fields['language'])) {
            $updateStr .= ', language=?';
            $types .= 's';
            $vals[] = $language;
        }

        if (!empty($timezone) && ($timezone != $this->fields['timezone'])) {
            $updateStr .= ', timezone=?';
            $types .= 's';
            $vals[] = $timezone;
        }

        if (!empty($date_format) && ($date_format != $this->fields['date_format'])) {
            $date_format = in_array($date_format, array_keys(self::DATE_FORMATS)) ? $date_format : 'Y-m-d';
            $updateStr .= ', date_format=?';
            $types .= 's';
            $vals[] = $date_format;
        }

        if (!empty($time_format) && ($time_format != $this->fields['timezone'])) {
            $time_format = in_array($time_format, array_keys(self::TIME_FORMATS)) ? $time_format : 'h:m a';
            $updateStr .= ', time_format=?';
            $types .= 's';
            $vals[] = $time_format;
        }

        $updateStr = "UPDATE users SET {$updateStr} WHERE companyid=? AND userid=?";
        $types .= 'ii';
        $vals[] = $_COMPANY->id; // Order is important
        $vals[] = $this->id;

        $params = array($updateStr,$types);
        foreach ($vals as $v) {
            $params[] = $v;
        }
        call_user_func_array(array($this, "DBMutatePS"), $params);

        User::GetUser($this->id, true); // Refresh cache after loading from master DB
        return 1;
    }

    // Returns 1 if successful 0 otherwise
    public function updateProfile2 (string $email, ?string $firstname, ?string $lastname, ?string $pronouns, ?string $jobTitle, ?string $department, ?string $officeLocation, ?string $city, ?string $state, ?string $country, ?string $region, ?string $opco, ?string $employeeType, ?string $externalUsername, ?string $extendedProfile, bool $isValidated, ?string $employee_hire_date, ?string $employee_start_date, ?string $employee_termination_date):int
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        $updateStr = 'modified=now()';
        $idsUpdated = [];

        // Reset user to active status for all scenarios other than if the user is blocked by Admin or if the user
        // has initated self delete.
        if (!$this->isBlocked() && !$this->isPendingWipeClean()) {
            $updateStr .= ', isactive=1';
        }

        $types = '';
        $vals = array();

        //$email = preg_replace( '/[^[:print:]]/', '', trim($email));  Commented to support UTF-8
        if (!empty($email) && ($email != $this->fields['email']) && $_COMPANY->isValidEmail($email)) {
            //self::DBMutatePS("UPDATE users SET {$updateStr}, email=? WHERE userid=?",'si',$email,$this->id);
            $updateStr .= ', email=?';
            $types .= 'x';
            $vals[] = $email;
            $idsUpdated[] = 'email';
        }

        //$firstname = preg_replace( '/[^[:print:]]/', '', trim($firstname)); Commented to support UTF-8
        if ($this->val('user_managed_profile') == "0" && !empty($firstname) && ($firstname != $this->fields['firstname'])) {
            $firstname =  Sanitizer::SanitizePersonName($firstname);
            //self::DBMutatePS("UPDATE users SET {$updateStr}, firstname=? WHERE userid=?",'si',$firstname,$this->id);
            $updateStr .= ', firstname=?';
            $types .= 's';
            $vals[] = $firstname;
        }

        //$lastname = preg_replace( '/[^[:print:]]/', '', trim($lastname));  Commented to support UTF-8
        if ($this->val('user_managed_profile') == "0" && !empty($lastname) && ($lastname != $this->fields['lastname'])) {
            $lastname =  Sanitizer::SanitizePersonName($lastname);
            //self::DBMutatePS("UPDATE users SET {$updateStr}, lastname=? WHERE userid=?",'si',$lastname,$this->id);
            $updateStr .= ', lastname=?';
            $types .= 's';
            $vals[] = $lastname;
        }

        if ($this->val('user_managed_profile') == "0" && !empty($pronouns) && ($pronouns != $this->fields['pronouns'])) {
            $pronouns =  Sanitizer::SanitizePersonPronouns($pronouns);
            $updateStr .= ', pronouns=?';
            $types .= 's';
            $vals[] = $pronouns;
        }

        //$jobTitle = preg_replace( '/[^[:print:]]/', '', trim($jobTitle));  Commented to support UTF-8
        if (!empty($jobTitle) && ($jobTitle != $this->fields['jobtitle'])) {
            //self::DBMutatePS("UPDATE users SET {$updateStr}, jobtitle=? WHERE userid=?",'si',$jobTitle,$this->id);
            $updateStr .= ', jobtitle=?';
            $types .= 's';
            $vals[] = $jobTitle;
        }

        // $department = preg_replace( '/[^[:print:]]/', '', trim($department));  Commented to support UTF-8
        if (!empty($department)) {
            $departmentid = $_COMPANY->getOrCreateDepartment__memoized($department);
            if (!empty($departmentid) && $departmentid != $this->fields['department']) {
                //self::DBMutatePS("UPDATE users SET {$updateStr}, department=? WHERE userid=?", 'ii', $departmentid, $this->id);
                $updateStr .= ', department=?';
                $types .= 'i';
                $vals[] = $departmentid;
            }
        }

        //$officeLocation = preg_replace( '/[^[:print:]]/', '', trim($officeLocation)); ;  Commented to support UTF-8
        if (!empty($officeLocation)) {
            //$city = preg_replace( '/[^[:print:]]/', '', trim($city)); Commented to support UTF-8
            //$state = preg_replace( '/[^[:print:]]/', '', trim($state)); Commented to support UTF-8
            //$country = preg_replace( '/[^[:print:]]/', '', trim($country)); Commented to support UTF-8
            $inputRegionId = $_COMPANY->getOrCreateRegion__memoized($region);
            $branch_rec = $_COMPANY->getOrCreateOrUpdateBranch__memoized($officeLocation, $city, $state, $country,'',$inputRegionId);
            if (!empty($branch_rec) && $branch_rec['branchid'] != $this->fields['homeoffice']) {
                $branchid = $branch_rec['branchid'];
                $regionid = $branch_rec['regionid'];
                //self::DBMutatePS("UPDATE users SET {$updateStr}, homeoffice=?, regionid=? WHERE userid=?", 'iii', $branchid, $regionid , $this->id);

                $updateStr .= ', homeoffice=?';
                $types .= 'i';
                $vals[] = $branchid;

                $updateStr .= ', regionid=?';
                $types .= 'i';
                $vals[] = $regionid;

                #update Auto Assigned Chapters
                $this->updateAutoAssignedChapters($branchid);
            }
        }

        //$opco = preg_replace( '/[^[:print:]]/', '', trim($opco)); Commented to support UTF-8
        if (!empty($opco) && ($opco != $this->fields['opco'])) {
            //self::DBMutatePS("UPDATE users SET {$updateStr}, opco=? WHERE userid=?", 'si', $opco, $this->id);
            $updateStr .= ', opco=?';
            $types .= 's';
            $vals[] = $opco;
        }

        //$employeeType = preg_replace( '/[^[:print:]]/', '', trim($employeeType)); Commented to support UTF-8
        if (!empty($employeeType) && ($employeeType != $this->fields['employeetype'])) {
            //self::DBMutatePS("UPDATE users SET {$updateStr}, employeetype=? WHERE userid=?", 'si', $employeeType, $this->id);
            $updateStr .= ', employeetype=?';
            $types .= 's';
            $vals[] = $employeeType;
        }

        //$externalUsername = preg_replace( '/[^[:print:]]/', '', trim($externalUsername)); Commented to support UTF-8
        if (!empty($externalUsername) && ($externalUsername != $this->fields['externalusername'])) {
            //self::DBMutatePS("UPDATE users SET {$updateStr}, externalusername=? WHERE userid=?", 'si', $externalUsername, $this->id);
            $updateStr .= ', externalusername=?';
            $types .= 'x';
            $vals[] = $externalUsername;
            $idsUpdated[] = 'externalusername';
        }

        //$extendedProfile = preg_replace( '/[^[:print:]]/', '', trim($extendedProfile)); Commented to support UTF-8
        if (!empty($extendedProfile)) {
            if (empty($this->fields['extendedprofile']) || empty($existing_vals = self::DecryptProfile($this->fields['extendedprofile']))) {
                $newExtendedProfile = self::EncryptProfile(json_decode($extendedProfile,true));
            } else {
                // Extended Profile can contain main UTF-8 characters and we want to store them as is so we will use JSON_UNESCAPED_UNICODE
                $newExtendedProfile = self::EncryptProfile(array_merge($existing_vals,json_decode($extendedProfile,true)));
            }
            if ($this->fields['extendedprofile'] !== $newExtendedProfile) {
                //self::DBMutatePS("UPDATE users SET {$updateStr}, extendedprofile=? WHERE userid=?", 'xi', $newExtendedProfile, $this->id);
                $updateStr .= ', extendedprofile=?';
                $types .= 'x';
                $vals[] = $newExtendedProfile;
                $this->fields['extendedprofile'] = $newExtendedProfile;
            }
        }

        if ($isValidated) {
            //self::DBMutate("UPDATE users SET validatedon=now() WHERE userid={$this->id}");
            $updateStr .= ', validatedon=now(), verificationstatus=1';
        }

        if (!empty($employee_hire_date) && !empty($employee_hire_date_tm = strtotime($employee_hire_date))) {
            $employee_hire_date = gmdate('Y-m-d', $employee_hire_date_tm);
            $updateStr .= ', employee_hire_date=?';
            $types .= 'x';
            $vals[] = $employee_hire_date;
        }

        if (!empty($employee_start_date) && !empty($employee_start_date_tm = strtotime($employee_start_date))) {
            $employee_start_date = gmdate('Y-m-d', $employee_start_date_tm);
            $updateStr .= ', employee_start_date=?';
            $types .= 'x';
            $vals[] = $employee_start_date;
        }

        if (!empty($employee_termination_date) && !empty($employee_termination_date_tm = strtotime($employee_termination_date))) {
            $employee_termination_date = gmdate('Y-m-d', $employee_termination_date_tm);
            $updateStr .= ', employee_termination_date=?';
            $types .= 'x';
            $vals[] = $employee_termination_date;
        }

        $updateStr = "UPDATE users SET {$updateStr} WHERE companyid=? AND userid=?";
        $types .= 'ii';
        $vals[] = $_COMPANY->id; // Order is important
        $vals[] = $this->id;

        $params = array($updateStr,$types);
        foreach ($vals as $v) {
            $params[] = $v;
        }
        $retVal = call_user_func_array(array($this, "DBMutatePS"), $params);

        if ($retVal) {
            if (in_array('email', $idsUpdated)) {
                self::LogObjectLifecycleAudit('update', 'user', $this->id(), 0, [
                    'ids' => $this->getEncryptedIdentityForLogging(),
                    'oper_details' => 'updated email',
                ]);
            }
            if (in_array('externalusername', $idsUpdated)) {
                self::LogObjectLifecycleAudit('update', 'user', $this->id(), 0, [
                    'ids' => $this->getEncryptedIdentityForLogging(),
                    'oper_details' => 'updated externalusername',
                ]);
            }
        }

        User::GetUser($this->id, true); // Refresh cache after loading from master DB
        return $retVal;
    }

    public static function GetUserEventJoinStatus(int $userid, int $eventid): int
    {

        $check = self::DBGet("SELECT eventid, joinstatus FROM `eventjoiners` WHERE `eventid`='{$eventid}' AND `userid`='{$userid}'");

        if (count($check) > 0) {
            return (int)$check[0]['joinstatus'];
        }

        return 0;
    }

    /**
     * @param int $groupid
     * @param int $chapterid , default is 0
     * @param int $channelid , default is 0
     * @param int $anonymous , optional default is 0
     * @param bool $sendEmail
     * @param bool $showSurvey
     * @param string $initatedBy should match one of the keys in GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON
     * @return bool
     */
    public function joinGroup(int $groupid, int $chapterid, int $channelid, int $anonymous=0, bool $sendEmail=true, bool $showSurvey=true, string $initatedBy='USER_INITATED')
    {
        global $_COMPANY, $_ZONE;
        $retVal = false;
        $jobs = array();

        if ($chapterid < 0 || $channelid < 0) {
            return false;
        }

        if (!in_array($initatedBy, array_keys(GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON))) {
            return false;
        }

        if (!$this->isAllowedToJoinGroup($groupid)) {
            return false;
        }
        if($anonymous){
            $sendEmail = false;
        }

        $q1 = self::DBGet("SELECT chapterid, channelids, anonymous,isactive FROM groupmembers WHERE groupid={$groupid} AND userid={$this->id()}");

        if (!empty($q1)) {

            if (!$q1[0]['isactive']) {
                $this->activateInactivateMembership($groupid,true, $initatedBy);
            }

            if ($chapterid || $channelid) { // Optimization: Process only if chapterid or channelid was provided.
                $anonymous = (int)$q1[0]['anonymous']; // Reset anonymous to previously set value in the table.

                // Calculate new chapterid list
                $is_new_chapterid = false;
                // Record exists, update it.
                $chapter_list_arr = explode(",", $q1[0]['chapterid']);
                if ($chapterid && !in_array($chapterid, $chapter_list_arr)) {
                    $is_new_chapterid = true;
                }
                // add back chapter 0, remove duplicates and resort them
                $chapter_list_arr[] = 0;
                $chapter_list_arr[] = $chapterid;
                asort($chapter_list_arr, SORT_NUMERIC);
                $chapter_list_arr = array_unique($chapter_list_arr, SORT_NUMERIC);
                $chapter_list = implode(',', $chapter_list_arr);

                // Calculate new channelid list
                $is_new_channelid = false;
                $channel_list_arr = explode(",", $q1[0]['channelids']);
                if ($channelid && !in_array($channelid, $channel_list_arr)) {
                    $is_new_channelid = true;
                }
                // add back channel 0, remove duplicates and resort them
                $channel_list_arr[] = 0;
                $channel_list_arr[] = $channelid;
                asort($channel_list_arr, SORT_NUMERIC);
                $channel_list_arr = array_unique($channel_list_arr, SORT_NUMERIC);
                $channel_list = implode(',', $channel_list_arr);

                if ($is_new_chapterid || $is_new_channelid) {
                    $this->clearPendingMemberInvitations($groupid, $chapterid, $channelid);
                    $updateResult = self::DBMutate("UPDATE groupmembers SET chapterid = '{$chapter_list}', channelids = '{$channel_list}', isactive=1 WHERE groupid={$groupid} AND userid={$this->id()}");
                    if ($updateResult) {
                        $retVal = true;
                        if ($chapterid && $is_new_chapterid) {
                            if ($anonymous == 0 && $showSurvey) { // Request user to respond to survey if anonymous setting is off
                                $this->pushSurveyIntoSession(Survey2::SURVEY_TYPE['GROUP_MEMBER'], Survey2::SURVEY_TRIGGER['ON_JOIN'], $groupid, $chapterid, 0);
                            }
                            if ($sendEmail) {
                                $jobs[] = new GroupMemberJob($groupid, $chapterid, 0);
                            }

                            // Create Group user log
                            GroupUserLogs::CreateGroupUserLog($groupid, $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chapterid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON[$initatedBy]);
                        }
                        if ($channelid && $is_new_channelid) {
                            // Surveys for channels are not available yet.
                            if ($anonymous == 0 && $showSurvey) { // Request user to respond to survey if anonymous setting is off
                                $this->pushSurveyIntoSession(Survey2::SURVEY_TYPE['GROUP_MEMBER'], Survey2::SURVEY_TRIGGER['ON_JOIN'], $groupid, 0, $channelid);
                            }
                            if ($sendEmail) {
                                $jobs[] = new GroupMemberJob($groupid, 0, $channelid);
                            }
                            // Create Group user log
                            GroupUserLogs::CreateGroupUserLog($groupid, $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $channelid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON[$initatedBy]);
                        }
                    }
                }
            }
        } else {
            // This is the new join, so create a new record.
            $chapter_list = ($chapterid)? '0,'.$chapterid : '0';
            $channel_list = ($channelid)? '0,'.$channelid : '0';

            //Update Membership
            $this->clearPendingMemberInvitations($groupid, $chapterid, $channelid);
            $insertResult = self::DBInsert("INSERT INTO groupmembers(groupid, userid, chapterid,channelids, groupjoindate, anonymous, isactive) VALUES ({$groupid},{$this->id()},'{$chapter_list}','{$channel_list}', now(), '{$anonymous}', 1)");
            if ($insertResult) {
                $this->addUserZone($_ZONE->id(), false, false);
                // reset groups_restricted_for_content
                $this->canViewContent($groupid, true);

                 // Create Group user log
                 GroupUserLogs::CreateGroupUserLog($groupid, $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, '', 0,GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON[$initatedBy]);
                $retVal = true;
                if ($chapterid) {
                    if ($anonymous == 0 && $showSurvey) { // Request user to respond to survey if anonymous setting is off
                        $this->pushSurveyIntoSession(Survey2::SURVEY_TYPE['GROUP_MEMBER'], Survey2::SURVEY_TRIGGER['ON_JOIN'], $groupid, $chapterid, 0);
                    }
                    if ($sendEmail) {
                        $jobs[] = new GroupMemberJob($groupid, $chapterid, 0);
                    }
                    // Create Group user log
                    GroupUserLogs::CreateGroupUserLog($groupid, $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chapterid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON[$initatedBy]);
                }
                if ($channelid) {
                    // Surveys for channels are not available yet.
                    if ($anonymous == 0 && $showSurvey) { // Request user to respond to survey if anonymous setting is off
                        $this->pushSurveyIntoSession(Survey2::SURVEY_TYPE['GROUP_MEMBER'], Survey2::SURVEY_TRIGGER['ON_JOIN'], $groupid, 0, $channelid);
                    }
                    if ($sendEmail) {
                        $jobs[] = new GroupMemberJob($groupid, 0, $channelid);
                    }
                    // Create Group user log
                    GroupUserLogs::CreateGroupUserLog($groupid, $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $channelid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON[$initatedBy]);
                }
                if ($sendEmail) {
                    $jobs[] = new GroupMemberJob($groupid, 0, 0);
                }
                if ($anonymous == 0 && $showSurvey) { // Request user to respond to survey if anonymous setting is off
                    $this->pushSurveyIntoSession(Survey2::SURVEY_TYPE['GROUP_MEMBER'], Survey2::SURVEY_TRIGGER['ON_JOIN'], $groupid, 0, 0);
                }
            }

            Points::HandleTrigger('ON_GROUP_JOIN', [
                'groupId' => $groupid,
                'userId' => $this->id(),
            ]);
        }

        if ($retVal) {
            foreach ($jobs as $job) {
                $job->saveAsJoinType($this->id, boolval($anonymous));
            }
        }
        $_COMPANY->expireRedisCache("GRP_MEM_C:{$groupid}");

        return $retVal;
    }
    // For checking restrictions on join group
    public function isAllowedToJoinGroup(int $groupid)
    {
        $group = Group::GetGroup($groupid);
        return $group->isUserAllowedToJoin($this);
    }

    /** Clears all pending member invited for the given group.
     * For chapters, we will compare invitations by joined chapter or 0 to accomodate for auto assignment
     * We will also compare for a channelid match
     * @param int $groupid
     * @param int $chapterid
     * @param int $channelid
     * @return int
     */
    private function clearPendingMemberInvitations (int $groupid, int $chapterid, int $channelid):int {
        return self::DBMutatePS("UPDATE memberinvites SET status=2 WHERE groupid={$groupid} AND companyid=? AND email=? AND chapterid in (0,?) AND channelid=?", "ixii", $this->cid(),$this->val('email'),$chapterid,$channelid);
    }
    /**
     * @param int $groupid
     * @param int $chapterid , default is 0
     * @param int $channelid , default is 0
     * @param bool $sendEmail
     * @param bool $showSurvey
     * @param string $initatedBy should match one of the keys in GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON
     * @return bool
     */
    public function leaveGroup(int $groupid, int $chapterid, int $channelid, bool $sendEmail=true, bool $showSurvey=true, string $initatedBy='USER_INITATED')
    {
        global $_COMPANY, $_ZONE;
        $retVal = false;
        $jobs = array();
        $anonymous = 0;

        if ($chapterid < 0 || $channelid < 0) {
            return false;
        }

        // Validate $initiatedBy is set to a valid value
        if (!in_array($initatedBy, array_keys(GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON))) {
            return false;
        }

        $group = Group::GetGroup($groupid);
        if ($group->isTeamsModuleEnabled()) {
            $reason = Team::GetWhyCannotLeaveProgramMembership($groupid, $this->id());
            if ($reason) {
                if (Http::IsAjaxRequest()) {
                    AjaxResponse::SuccessAndExit_STRING(
                        0,
                        ['btn' => gettext('<strong>Leave</strong>')],
                        $reason,
                        gettext('Error')
                    );
                }
                return false;
            }
        }

        if ($this->getWhyCannotLeaveGroup($groupid, $chapterid, $channelid)) {
            return false;
        }

        $q1 = self::DBGet("SELECT chapterid,channelids,anonymous FROM groupmembers WHERE groupid={$groupid} AND userid={$this->id()}");
        if (count($q1)) {
            $anonymous = (int)$q1[0]['anonymous']; // Use anonymous settings from the table
            // set Survey Session
            if ($chapterid == 0 && $channelid == 0) { // Leaving Group
                if (self::DBMutate("DELETE FROM groupmembers WHERE groupid={$groupid} AND userid={$this->id()}")) {

                    // reset groups_restricted_for_content
                    $this->canViewContent($groupid, true);

                    // Create Group user log
                    GroupUserLogs::CreateGroupUserLog($groupid, $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, '', 0, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON[$initatedBy]);

                    $retVal = true;
                    $chapters = explode(',', $q1[0]['chapterid']);
                    foreach ($chapters as $chap) {
                        if ($chap) { // We do not want to execute for chapterid 0
                            if ($anonymous == 0 && $showSurvey) { // Request user to respond to survey if anonymous setting is off
                                $this->pushSurveyIntoSession(Survey2::SURVEY_TYPE['GROUP_MEMBER'], Survey2::SURVEY_TRIGGER['ON_LEAVE'], $groupid, $chap, 0);
                            }
                            if ($sendEmail) {
                                $jobs[] = new GroupMemberJob($groupid, $chap, 0);
                            }

                            // Create Group user log
                            GroupUserLogs::CreateGroupUserLog($groupid, $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chap, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON[$initatedBy]);
                        }
                    }
                    $channels = explode(',', $q1[0]['channelids']);
                    foreach ($channels as $chan) {
                        if ($chan) { // We do not want to execute for channelid 0
                            // Surveys for channels are not available yet.
                            if ($anonymous == 0 && $showSurvey) { // Request user to respond to survey if anonymous setting is off
                                $this->pushSurveyIntoSession(Survey2::SURVEY_TYPE['GROUP_MEMBER'], Survey2::SURVEY_TRIGGER['ON_LEAVE'], $groupid, 0, $chan);
                            }
                            if ($sendEmail) {
                                $jobs[] = new GroupMemberJob($groupid, 0, $chan);
                            }
                            // Create Group user log
                            GroupUserLogs::CreateGroupUserLog($groupid, $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $chan, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON[$initatedBy]);
                        }
                    }
                    // Now lets set group level survey and communication job
                    if ($anonymous == 0 && $showSurvey) { // Request user to respond to survey if anonymous setting is off
                        $this->pushSurveyIntoSession(Survey2::SURVEY_TYPE['GROUP_MEMBER'], Survey2::SURVEY_TRIGGER['ON_LEAVE'], $groupid, 0, 0);
                    }
                    if ($sendEmail) {
                        $jobs[] = new GroupMemberJob($groupid, 0, 0);
                    }

                    Points::HandleTrigger('ON_GROUP_LEAVE', [
                        'groupId' => $groupid,
                        'userId' => $this->id(),
                    ]);
                }
            } else {
                // Record exists, update it.
                $chapter_list_arr = explode(",", $q1[0]['chapterid']);
                $k = array_search($chapterid, $chapter_list_arr);
                unset($chapter_list_arr[$k]); //Remove matching chapterid/channelid
                // add back chapter 0, remove duplicates and resort them
                $chapter_list_arr[] = 0;
                asort($chapter_list_arr, SORT_NUMERIC);
                $chapter_list_arr = array_unique($chapter_list_arr,SORT_NUMERIC);
                $chapter_list = implode(',', $chapter_list_arr);

                $channel_list_arr = explode(",", $q1[0]['channelids']);
                $k = array_search($channelid, $channel_list_arr);
                unset($channel_list_arr[$k]); //Remove matching chapterid/channelid
                // add back channel 0, remove duplicates and resort them
                $channel_list_arr[] = 0;
                asort($channel_list_arr, SORT_NUMERIC);
                $channel_list_arr = array_unique($channel_list_arr,SORT_NUMERIC);
                $channel_list = implode(',', $channel_list_arr);

                if (self::DBMutate("UPDATE groupmembers SET chapterid='{$chapter_list}',channelids='{$channel_list}' WHERE groupid={$groupid} AND userid={$this->id()}")) {
                    $retVal = true;
                    if ($chapterid) { // Leaving Chapter only
                        if ($anonymous == 0 && $showSurvey) { // Request user to respond to survey if anonymous setting is off
                            $this->pushSurveyIntoSession(Survey2::SURVEY_TYPE['GROUP_MEMBER'], Survey2::SURVEY_TRIGGER['ON_LEAVE'], $groupid, $chapterid, 0);
                        }
                        if ($sendEmail) {
                            $jobs[] = new GroupMemberJob($groupid, $chapterid, 0);
                        }
                        // Create Group user log
                        GroupUserLogs::CreateGroupUserLog($groupid, $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chapterid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON[$initatedBy]);
                    } elseif ($channelid) { // Leaving Channel only
                        // Surveys for channels are not available yet.
                        if ($anonymous == 0 && $showSurvey) { // Request user to respond to survey if anonymous setting is off
                            $this->pushSurveyIntoSession(Survey2::SURVEY_TYPE['GROUP_MEMBER'], Survey2::SURVEY_TRIGGER['ON_LEAVE'], $groupid, 0, $channelid);
                        }
                        if ($sendEmail) {
                            $jobs[] = new GroupMemberJob($groupid, 0, $channelid);
                        }

                        // Create Group user log
                        GroupUserLogs::CreateGroupUserLog($groupid, $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $channelid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON[$initatedBy]);
                    }
                }
            }
        }

        if ($retVal) {
            foreach ($jobs as $job) {
                $job->saveAsLeaveType($this->id, boolval($anonymous));
            }
        }
        $_COMPANY->expireRedisCache("GRP_MEM_C:{$groupid}");

        return $retVal;
    }

    public function getWhyCannotLeaveGroup(int $groupid, int $chapterid, int $channelid): string
    {
        /**
         * Who can leave group?
         * Group leader cannot leave group
         * Chapter leader cannot leave group
         * Channel leader cannot leave group
         * Regional leader behaves like a group leader and cannot leave group membership
         *
         * Who can leave chapter?
         * Chapter leader cannot leave chapter
         * Group leader can leave chapter
         * Channel leader can leave chapter
         *
         * Who can leave channel?
         * Channel leader cannot leave channel
         * Group leader can leave channel
         * Chapter leader can leave channel
         *
         */
        if ($chapterid === 0 && $channelid === 0) { // Leaving Group
            if (
                $this->isGrouplead($groupid)
                || $this->isChapterlead($groupid, -1)
                || $this->isChannellead($groupid, -1)
                || $this->isRegionallead($groupid)
            ) {
                return 'LEADER_CANNOT_LEAVE_MEMBERSHIP';
            }
        } elseif ($chapterid === 0) { // Leaving Channel
            if ($this->isChannellead($groupid, $channelid)) {
                return 'LEADER_CANNOT_LEAVE_MEMBERSHIP';
            }
        } else {
            if ($this->isChapterlead($groupid, $chapterid)) { // Leaving Chapter
                return 'LEADER_CANNOT_LEAVE_MEMBERSHIP';
            }
        }

        return '';
    }

    public function activateInactivateMembership (int $groupid, bool $activate, string $initatedBy='HRIS_SYNC')
    {
        global $_COMPANY, $_ZONE;

        // Validate $initiatedBy is set to a valid value
        if (!in_array($initatedBy, array_keys(GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON))) {
            return false;
        }

        $status = $activate ? 1 : 0;
        $retVal = self::DBMutate("UPDATE groupmembers SET isactive={$status} WHERE groupid={$groupid} AND userid={$this->id()}");
        if ($retVal) {
            GroupUserLogs::CreateGroupUserLog($groupid, $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['UPDATE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, '', 0, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON[$initatedBy]);
            $_COMPANY->expireRedisCache("GRP_MEM_C:{$groupid}");
        }

        return $retVal;
    }

    public function isVerified(): bool
    {
        return ($this->fields['verificationstatus'] == 1);
    }

    public function isActive(): bool
    {
        return ($this->fields['isactive'] == 1);
    }

    public function isBlocked(): bool
    {
        return ($this->fields['isactive'] == self::STATUS_BLOCKED);
    }

    public function isPendingWipeClean() : bool
    {
        return ($this->fields['isactive'] == self::STATUS_WIPE_CLEAN);
    }

    /**
     * This function returns true if the user is member of given group (and chapter). Chapterid defaults to 0
     * NOTE: This function will fail if the chapterid = 0 is not in the list of chapterid. Be careful when updating
     * chapterid field of groupmembers table.... do not remove 0 from the list.
     * @param int $groupid
     * @param int $chapterid
     * @return bool
     */
    public function isGroupMember(int $groupid, int $chapterid = 0, bool $fromMaster = true): bool
    {
        if ($chapterid < 0) {
            return false;
        }

        $retVal = false;
        $sql = "SELECT chapterid FROM groupmembers WHERE groupid='{$groupid}' AND userid='{$this->id()}' AND isactive=1";
        $q1 = ($fromMaster) ? self::DBGet($sql) : self::DBROGet($sql);
        if (count($q1)) {
            $c = explode(",", $q1[0]['chapterid']);
            $retVal = in_array($chapterid, $c);
        }
        return $retVal;
    }

    /**
     * @param int $groupid
     * @param string $chapterids
     * @param string $channelids
     * @return bool true if the user is member of any given chapter and any given channel
     */
    public function isGroupMemberInScopeCSV(int $groupid, string $chapterids, string $channelids): bool
    {
        $retVal = false;
        $chapterids = trim($chapterids);
        $channelids = trim($channelids);
        $q1 = self::DBGet("SELECT chapterid,channelids FROM groupmembers WHERE groupid='{$groupid}' AND groupmembers.isactive=1 AND userid='{$this->id()}'");
        if (count($q1)) {
            $input_chp_ary = explode(',',$chapterids);
            $input_chn_ary = explode(',',$channelids);
            $member_chp_ary = explode(',', $q1[0]['chapterid']);
            $member_chn_ary = explode(',', $q1[0]['channelids']);
            // Only if a user is member of both the chapter and the channel
            $retVal = !empty(array_intersect($input_chp_ary, $member_chp_ary)) && !empty(array_intersect($input_chn_ary, $member_chn_ary));
        }
        return $retVal;
    }

    /**
     * This functions checks if the logged in user is lead of a given group.
     * If the logged in user is a regional lead type, then we match the provided region to exist in the list of regions
     * the user can lead. If no region is provided and the user is regional lead type, then false is returned.
     * @param int $groupid - Group id to check; if $groupid = -1, then a true is returned if user is lead of any group
     * @return bool - True if the user is Lead of the given group
     */
    public function isGrouplead(int $groupid): bool
    {
        return !empty($this->filterGroupleadRecords($groupid, 0)); // return true if matching records were found
    }

    /**
     * This function is exactly the same as isGrouplead but has been provided for better contextual usage when one
     * wants to know if the user is regional lead in the group. If regionid is provided then the users is checked if he
     * is regional lead for that region
     * @param int $groupid
     * @param int $regionid - Defaults to -1 which means ignore region and just tell if user is of regional lead type.
     * @return bool
     */
    public function isRegionallead(int $groupid, $regionid=-1): bool
    {
        $recs = $this->filterGroupleadRecords($groupid, $regionid);
        if ($regionid == -1) { // Check if atleast one of recs has a regionid otherwise it means region was not set, bugfix #1595
            foreach ($recs as $rec) {
                if ($rec['regionids']) {
                    return true;
                }
            }
            return false; // Either no recs or none of the recs have a regionid set.
        }
        return !empty($recs);
    }
    /**
     * This function checks if logged in user is lead of a given chapter.
     * @param int $groupid the group id of the chapter for which we want to search. If groupid = -1, then group match or
     * chapter match is not done, as long as user is lead of [any chapter in the company], true is returned.
     * @param int $chapterid the chapterid to search. If chapterid = -1 then chapter match is not done. As long as the
     * user is lead of [any chapter in the group], true is returned
     * @return bool True if the user is a lead of a given chapter.
     */
    public function isChapterlead(int $groupid, int $chapterid): bool
    {
        return !empty($this->filterChapterleadRecords($groupid, $chapterid)); // return true if matching records were found
    }

    /**
     * This function checks if logged in user is lead of a given channel.
     * @param int $groupid the group id of the channel for which we want to search. If groupid = -1, then group match or
     * channel match is not done, as long as user is lead of [any channel in the company], true is returned.
     * @param int $channelid the channelid to search. If channelid = -1 then channel match is not done. As long as the
     * user is lead of [any channel in the group], true is returned
     * @return bool True if the user is a lead of a given channel.
     */
    public function isChannellead(int $groupid, int $channelid): bool
    {
        return !empty($this->filterChannelleadRecords($groupid, $channelid)); // return true if matching records were found
    }

    /**
     * Returns true if the user is lead of a given group or any chapter or channel in the group.
     * If groupid is 0, then all `groups`in the company are searched.
     * This method is used to determine if 'Create Announcement' or 'Create Event' button should be shown to the user
     * as at that time the chapter or channel context is not known.
     * Common Usages: use for decisions where to show draft or not
     * @param int $groupid
     * @return bool; true if a user is lead of the group
     */
    public function
    isGroupleadOrGroupChapterleadOrGroupChannellead(int $groupid): bool
    {
        return ($this->isAdmin() ||
            $this->isGrouplead($groupid) ||
            $this->isRegionallead($groupid) ||
            $this->isChapterlead($groupid, -1) ||
            $this->isChannellead($groupid, -1));
    }

    public function getAllFollowedGroupsInZoneAsCSV(int $zoneid)
    {
        return self::DBROGet("SELECT IFNULL(group_concat(groupid),'') grps FROM groupmembers JOIN `groups` USING (groupid) WHERE `groups`.zoneid='{$zoneid}' AND `groups`.isactive=1 AND userid='{$this->id()}' AND groupmembers.isactive=1")[0]['grps'];
    }

    public function getFollowedGroupsAsCSV()
    {
        return self::DBGet("SELECT IFNULL(group_concat(groupid),'') grps FROM groupmembers WHERE userid='{$this->id()}' AND groupmembers.isactive=1")[0]['grps'];
    }

    public function getFollowedGroupChapterAsCSV(int $groupid)
    {
        $retVal = '';

        if ($groupid !== 0) {
            $q1 = self::DBGet("SELECT chapterid FROM groupmembers WHERE groupid='{$groupid}' AND userid='{$this->id()}' AND groupmembers.isactive=1");
            if (count($q1)) {
                $retVal = $q1[0]['chapterid'];
            }
        }

        return $retVal;
    }

    public function getFollowedGroupChannels(int $groupid)
    {
        $retVal = '';

        if ($groupid !== 0) {
            $q1 = self::DBROGet("SELECT channelids FROM groupmembers WHERE groupid='{$groupid}' AND userid='{$this->id()}' AND groupmembers.isactive=1 ");
            if (count($q1)) {
                $retVal = $q1[0]['channelids'];
            }
        }

        return $retVal;
    }

    public function isGroupChannelMember(int $groupid, int $channelid = 0, bool $fromMaster = true): bool
    {
        if ($channelid < 0) {
            return false;
        }

        $retVal = false;
        $sql = "SELECT channelids FROM groupmembers WHERE groupid='{$groupid}' AND userid='{$this->id()}' AND groupmembers.isactive=1";

        $q1 = $fromMaster ? self::DBGet($sql) : self::DBROGet($sql);
        if (count($q1)) {
            $c = explode(",", $q1[0]['channelids']);
            $retVal = in_array($channelid, $c);
        }
        return $retVal;
    }

    // Todo - refactor rename to canAdminCompanySettings
    public function canManageCompanySettings(): bool
    {
        return $this->isAdmin();
    }

    // *****************************
    // Start of Zone Admin functions
    // *****************************
    private function getAllAdminRecords(): array
    {
        global $_COMPANY;
        if ($this->zoneadminrecords === null) {

            $external_admin_roles = $_COMPANY->getExternalAdminRoles('company_admin_external_roles');
            if ($external_admin_roles && !array_intersect($external_admin_roles, $this->getExternalRoles())) {
                $full_admin_records = [];
            } else {
                $full_admin_records = self::DBGet("SELECT * FROM company_admins WHERE companyid={$_COMPANY->id()} AND userid={$this->id} AND zoneid=0");
            }

            $external_zone_admin_roles = $_COMPANY->getExternalAdminRoles('zone_admin_external_roles');
            if ($external_zone_admin_roles && !array_intersect($external_zone_admin_roles, $this->getExternalRoles())) {
                $zone_admin_records = [];
            } else {
                $zone_admin_records = self::DBGet("SELECT * FROM company_admins WHERE companyid={$_COMPANY->id()} AND userid={$this->id} AND zoneid!=0");
            }
            $this->zoneadminrecords = array_merge($full_admin_records, $zone_admin_records);
        }

        return $this->zoneadminrecords;
    }

    /**
     * Assigns admin persmissions to the users. If zone is not provided the company level admin persmissions are assigned.
     * @param int $zoneid
     * @param int $manage_budget
     * @param int $manage_approvers
     * @param int $can_view_reports
     * @return int
     */
    public function assignAdminPermissions (int $zoneid, int $manage_budget, int $manage_approvers, int $can_view_reports): int
    {
        global $_COMPANY, $_USER;

        $keys = "companyid={$_COMPANY->id()}, zoneid={$zoneid}, userid={$this->id}";
        $values = "manage_budget={$manage_budget},manage_approvers={$manage_approvers},can_view_reports={$can_view_reports}";
        // Note $_USER is the logged in user and $this is the assigned user.
        $update = self::DBUpdate("INSERT INTO company_admins SET {$keys}, {$values}, createdby={$_USER->id()} ON DUPLICATE KEY UPDATE {$values}");
        // Update accounttype, set it to 3 if zoneid is 0 (i.e. global admin), else set it to 5 if accounttype is not already 3.
        $accounttype = $zoneid ? 5 : 3;
        $update2 = self::DBUpdate("UPDATE users SET accounttype=(IF(accounttype=3,3,'{$accounttype}')) WHERE companyid={$_COMPANY->id()} AND userid={$this->id}");
        User::GetUser($this->id, true); // Refresh cache after loading from master DB

        // Add user to the mailing list; all admins will be added to the mailing list.
        TeleskopeMailingList::AddOrUpdateUserMailingList($this->id, 1, 1, 0);

        return $update;
    }

    /**
     * Revoke Admin persmissions for the given zone. If zoneid is not provided then company level admin persmissions are revoked.
     * @param int $zoneid
     * @return int
     */
    public function revokeAdminPermissions (int $zoneid=0): int
    {
        global $_COMPANY;
        $update = self::DBUpdate("DELETE FROM company_admins WHERE companyid={$_COMPANY->id()} AND zoneid={$zoneid} AND userid={$this->id}");
        // Update account type
        $check = self::DBGet("SELECT count(1) as `is_admin` FROM company_admins WHERE {$_COMPANY->id()} AND userid={$this->id}")[0];
        $accounttype = $check['is_admin'] ? ($zoneid ? 'accounttype' : 5) : 1;
        if ($accounttype != 'accounttype') {
            $update2 = self::DBUpdate("UPDATE users SET accounttype={$accounttype} WHERE companyid={$_COMPANY->id()} AND userid={$this->id}");
        }

        User::GetUser($this->id, true); // Refresh cache after loading from master DB
        return $update;
    }

    /**
     * This method checks if the user is some sort of Admin to allow access to the Admin Panel
     * @return bool
     */
    public function allowAdminPanelLogin(): bool
    {
        return (int)$this->val('accounttype') > 1 &&
            !empty($this->getAllAdminRecords());
    }

    /**
     * Checks if the user is a Zone Admin of current zone or a Company Admin
     * @return bool
     */
    public function isAdmin(): bool
    {
        global $_ZONE;
        return $this->isCompanyAdmin() || ($_ZONE && $this->isZoneAdmin($_ZONE->id()));
    }

    public function isCompanyAdmin(): bool
    {
        return (int)$this->val('accounttype') == 3 &&
            !empty(Arr::SearchColumnReturnRow($this->getAllAdminRecords(), 0, 'zoneid'));
    }

    /**
     * Returns true if the user is a Zone Admin of the given zone
     * @param int $zoneid
     * @return bool
     */
    public function isZoneAdmin(int $zoneid): bool
    {
        global $_COMPANY;

        return (int)$this->val('accounttype') > 1 &&
            !empty(Arr::SearchColumnReturnRow($this->getAllAdminRecords(), $zoneid, 'zoneid'));
    }


    /**
     * Returns true if the user can Manage Zone budget of the current zone
     * @return bool
     */
    public function canManageZoneBudget(int $zoneid = 0): bool
    {
        global $_ZONE;

        if ($zoneid == 0) {
            $zoneid = $_ZONE->id();
        }
        if ((int)$this->val('accounttype') > 1) {
            $row = Arr::SearchColumnReturnRow($this->getAllAdminRecords(), $zoneid, 'zoneid');
            return boolval($row['manage_budget'] ?? false);
        }
        return false;
    }

    /**
     * Returns true if the user can manage Speakers for the current zone
     * @return bool
     */
    public function canManageZoneSpeakers(): bool
    {
        global $_ZONE;
        if ((int)$this->val('accounttype') > 1) {
            $row = Arr::SearchColumnReturnRow($this->getAllAdminRecords(), $_ZONE->id(), 'zoneid');
            return boolval($row['manage_approvers'] ?? false);
        }
        return false;
    }

    public function canManageZoneEvents(): bool
    {
        return $this->canManageZoneSpeakers();
    }

    public function canManageZoneNewsletter(): bool
    {
        return $this->canManageZoneSpeakers();
    }

    public function canManageZonePosts(): bool
    {
        return $this->canManageZoneSpeakers();
    }

    public function canViewReports(): bool
    {
        global $_ZONE;

        if ($this->isCompanyAdmin()) {
            return true;
        }

        $row = Arr::SearchColumnReturnRow($this->getAllAdminRecords(), $_ZONE->id(), 'zoneid');
        return boolval($row['can_view_reports'] ?? false);
    }

    /**
     * Returns an array of Administrators in the Current Zone with a given permission.
     * @param string $permission
     * @return array
     */
    public static function GetAllZoneAdminsForZone (int $zoneid, string $permission=''){
        global $_COMPANY;
        $retVal = array();
        $rows = self::DBGet("SELECT company_admins.*,`firstname`,`lastname`,`jobtitle`,`email`,`picture` FROM company_admins LEFT JOIN users USING (companyid,userid) WHERE companyid='{$_COMPANY->id()}' AND isactive=1 AND zoneid={$zoneid}");

        if (!empty($permission)) {
            foreach($rows as $row){
                if (isset($row[$permission]) && $row[$permission]) {
                    $retVal[] = $row;
                }
            }
            return $retVal;
        } else {
            return $rows;
        }
    }

    /**
     * Company Admins are Zone Admins for zone 0
     * @return array|void
     */
    public static function GetAllCompanyAdmins ()
    {
        global $_COMPANY, $_ZONE;
        return self::GetAllZoneAdminsForZone(0);
    }

    public static function GetAdministratorsForCurrentZone()
    {
        global $_ZONE;
        return self::GetAllZoneAdminsForZone($_ZONE->id());
    }

    /**
     * Returns an array of Zone Administrators who can manage speakers
     * @return array
     */
    public static function GetAllZoneAdminsWhoCanManageZoneSpeakers(){
        global $_ZONE;
        return self::GetAllZoneAdminsForZone($_ZONE->id(),'manage_approvers');
    }

    /**
     * Returns an array of Zone Administrators who can manage budget
     * @return array
     */
    public static function GetAllZoneAdminsWhoCanManageZoneBudget(){
        global $_ZONE;
        return self::GetAllZoneAdminsForZone($_ZONE->id(),'manage_budget');
    }

    // *****************************
    // End of Zone Admin functions
    // *****************************

    // Todo - refactor rename to canAdminAffinitiesContent
    public function canManageAffinitiesContent(): bool
    {
        return $this->isAdmin();
    }

    // Todo - refactor rename to canAdminAffinitiesUsers
    public function canManageAffinitiesUsers(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Fetches all the records for which the user is a group leader.
     * Lazy fetching & caching is done.
     * @return array of records or empty array
     */
    private function getAllGroupleadRecords(): array
    {
        global $_COMPANY;

        if ($this->groupleadrecords === null) {


            $external_admin_roles = $_COMPANY->getExternalAdminRoles('group_lead_external_roles');
            if ($external_admin_roles && !array_intersect($external_admin_roles, $this->getExternalRoles())) {
                $this->groupleadrecords = array();
            } else {

                $this->groupleadrecords = self::DBGet("
                SELECT groupleads.*,grouplead_type.*,`groups`.groupname,`groups`.zoneid 
                FROM `groupleads` 
                    JOIN `grouplead_type` ON `groupleads`.grouplead_typeid = `grouplead_type`.typeid 
                    JOIN `groups` ON groupleads.`groupid` = `groups`.`groupid` 
                WHERE `groupleads`.userid='{$this->id}' 
                    AND `grouplead_type`.companyid = '{$this->cid}'
                    AND `groupleads`.isactive=1
                    AND `grouplead_type`.isactive=1
                    AND `groups`.isactive=1
                    AND (allow_publish_content+allow_publish_content+allow_manage+allow_manage_budget+allow_manage_grant+allow_create_content+allow_manage_support) > 0
                  ");
            }
        }
        return $this->groupleadrecords;
    }

    /**
     * Fetches all the records for which the user is a chapter leader
     * Lazy fetching & chaching is done.
     * @return array of records or empty array
     */
    private function getAllChapterleadRecords(): array
    {
        global $_COMPANY;

        if ($this->chapterleadrecords === null) {
            $external_admin_roles = $_COMPANY->getExternalAdminRoles('chapter_lead_external_roles');
            if ($external_admin_roles && !array_intersect($external_admin_roles, $this->getExternalRoles())) {
                $this->chapterleadrecords = array();
            } else {
                $this->chapterleadrecords = self::DBGet("
                SELECT * FROM `chapterleads` 
                    JOIN `grouplead_type` ON `chapterleads`.grouplead_typeid = `grouplead_type`.typeid
                    JOIN `groups` ON chapterleads.groupid = `groups`.groupid
                    JOIN chapters  ON chapterleads.`chapterid` = chapters.`chapterid` 
                WHERE `chapterleads`.userid='{$this->id}' 
                    AND `groups`.isactive=1
                    AND `grouplead_type`.companyid = '{$this->cid}'
                    AND `chapterleads`.isactive=1 
                    AND `grouplead_type`.isactive=1
                    AND `chapters`.isactive=1
                    AND (allow_publish_content+allow_publish_content+allow_manage+allow_manage_budget+allow_manage_grant+allow_create_content+allow_manage_support) > 0
                  ");
            }
        }
        return $this->chapterleadrecords;
    }

    /**
     * Fetches all the records for which the user is a channel leader
     * Lazy fetching & chaching is done.
     * @return array of records or empty array
     */
    private function getAllChannelleadRecords(): array
    {
        global $_COMPANY;
        if ($this->channelleadrecords === null) {
            $external_admin_roles = $_COMPANY->getExternalAdminRoles('channel_lead_external_roles');
            if ($external_admin_roles && !array_intersect($external_admin_roles, $this->getExternalRoles())) {
                $this->channelleadrecords = array();
            } else {
                $this->channelleadrecords = self::DBGet("
                SELECT * FROM `group_channel_leads` 
                    JOIN `grouplead_type` ON `group_channel_leads`.grouplead_typeid = `grouplead_type`.typeid 
                    JOIN `groups` ON group_channel_leads.groupid = `groups`.groupid
                    JOIN group_channels  ON group_channel_leads.`channelid` = group_channels.`channelid`
                WHERE `group_channel_leads`.userid='{$this->id}' 
                    AND `grouplead_type`.companyid = '{$this->cid}'
                    AND `group_channel_leads`.isactive=1 
                    AND `grouplead_type`.isactive=1
                    AND `groups`.isactive=1
                    AND `group_channels`.isactive=1
                    AND (allow_publish_content+allow_publish_content+allow_manage+allow_manage_budget+allow_manage_grant+allow_create_content+allow_manage_support) > 0
                  ");
            }
        }
        return $this->channelleadrecords;
    }

    /**
     * This functions filters the grouplead rows of the logged-in user.
     * If the rows is regional lead type, then we match the provided region to exist in the list of regions
     * assigned for that row. If no region is provided (i.e. = 0) and the user is regional lead type, then that row is not selected
     * How to use this function:
     * (1) To get all grouplead records pass groupid = -1
     * (2) To get all grouplead records that match groupid and ignore the regionid, pass groupid to value > 0 and regionid = -1
     * (3) To get all grouplead records that match groupid and match regionid if grouplead is of regional type, then pass values for groupid and regionid > 0
     * @param int $groupid - Group id to check; if $groupid = -1, then group matching is not done (region matching is also not done)
     * If value is set to 0 then empty array is returned
     * @param int $regionid 0 for global region. If -1 is passed, regional matching is not done.
     * @return array - All the matching rows or empty array
     */
    private function filterGroupleadRecords(int $groupid, int $regionid): array
    {
        $retVal = array();
        // Make sure groupleadrecords was loaded
        if ($this->groupleadrecords === null)
            $this->getAllGroupleadRecords();

        if ($groupid === 0 || //$groupid = 0 is invalid.
            empty($this->groupleadrecords)) {
            return $retVal; // empty array.
        } elseif ($groupid === -1) {
            return $this->groupleadrecords; // Return all records, no need to filter
        } else {
            $rows = array_filter( // Filter rows for this group
                $this->groupleadrecords, function ($value) use ($groupid) {
                return ($value['groupid'] == $groupid);
            });

            if ($regionid === -1) { // We will ignore the region checking and return here.
                return $rows;
            } else { // We need to check if either the grouplead is not regional or region matches
                foreach ($rows as $row) {
                    if ($row['sys_leadtype'] !== "3") { // i.e. if the leadtype is global (only 3 is regional)
                        // User is not of regional type, so we determine user is a group lead
                        $retVal[] = $row;
                    } elseif ($regionid && array_search($regionid, explode(',', $row['regionids'])) !== false) {
                        // User is of regional type, and the region set in his grouplead record matches provided region
                        // If regionid is 0, then it is a global region and we will not be here
                        $retVal[] = $row;
                    }
                }
            }
        }
        return $retVal;
    }

    /**
     * This functions filters the grouplead rows of the logged-in user.
     * How to use this function:
     * (1) To get all chapterlead records pass groupid = -1, chapterid is ignored and all records are returned
     * (2) To get all chapterlead records matching a group, pass $groupid to a valid number and chapterid = -1
     * (3) To get all chapterlead records matching a group and chapter, pass valid values for both
     * @param int $groupid the group id of the chapter for which we want to search. If groupid = -1, then group match and
     * chapter match is not done, and all chapterlead rows for the user are returned. If $groupid = 0, then empty array
     * is returned
     * @param int $chapterid the chapterid to search. If chapterid = -1 then chapter match is not done. All rows
     * for any chapter in the group are returned. If $chapterid = 0 then empty array is returned.
     * @return array Array of matching rows or empty array
     */
    private function filterChapterleadRecords(int $groupid, int $chapterid): array
    {
        global $_COMPANY;
        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            return array();
        }

        $retVal = array();
        // Make sure chapterleadrecords was loaded
        if ($this->chapterleadrecords === null)
            $this->getAllChapterleadRecords();

        if ($groupid === 0 ||
            $chapterid === 0 ||
            empty($this->chapterleadrecords)) {
            return $retVal; // There are no chapter leadership records, $groupid = 0 and $chapterid = 0 are invalid
        } elseif ($groupid === -1) {
            return $this->chapterleadrecords; // Return all records, no need to filter
        } elseif ($chapterid === -1) {
            // We dont need to do chapter match, as long as group match succeeds return true
            return array_filter(
                $this->chapterleadrecords, function ($value) use ($groupid) {
                return ($value['groupid'] == $groupid);
            });
        } else {
            // We need to match group and chapter
            return array_filter( // Filter rows for this group
                $this->chapterleadrecords, function ($value) use ($groupid, $chapterid) {
                return (($value['groupid'] == $groupid) && ($value['chapterid'] == $chapterid));
            });
        }
        return $retVal;
    }

    private function filterChannelleadRecords(int $groupid, int $channelid): array
    {
        global $_COMPANY;
        if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
            return array();
        }

        $retVal = array();
        // Make sure channelleadrecords was loaded
        if ($this->channelleadrecords === null)
            $this->getAllChannelleadRecords();

        if ($groupid === 0 ||
            $channelid === 0 ||
            empty($this->channelleadrecords)) {
            return $retVal; // There are no channel leadership records, $groupid = 0 and $channelid = 0 are invalid
        } elseif ($groupid === -1) {
            return $this->channelleadrecords; // Return all records, no need to filter
        } elseif ($channelid === -1) {
            // We dont need to do channel match, as long as group match succeeds return true
            return array_filter(
                $this->channelleadrecords, function ($value) use ($groupid) {
                return ($value['groupid'] == $groupid);
            });
        } else {
            // We need to match group and channel
            return array_filter( // Filter rows for this group
                $this->channelleadrecords, function ($value) use ($groupid, $channelid) {
                return (($value['groupid'] == $groupid) && ($value['channelid'] == $channelid));
            });
        }
        return $retVal;
    }

    /**
     * Returns true if the logged in user can do $what on Group or Chapter.
     * @param int $groupid, if -1 then $groupid match is ignored
     * @param int $regionid - regionid of the chapter, if chapter is given, if -1 then regionid match is ignored
     * @param int $chapterid, if -1 then chapterid match is ignored
     * @param string $what valid values are 'allow_publish_content', 'allow_create_content', 'allow_manage', 'allow_manage_budget', 'allow_manage_grant', 'allow_manage_support'
     * @return bool
     */
    private function isGroupleadOrChapterleadAllowedTo (int $groupid, int $regionid, int $chapterid, string $what): bool
    {
        if (
            ($what == 'allow_manage_budget' && $this->canManageZoneBudget()) // Admins need explict budget role.
            ||
            ($what != 'allow_manage_budget' && $this->isAdmin())
        ) {
            return true;
        }
        // First check if there is a match at Group level
        $groupleadRows = $this->filterGroupleadRecords($groupid,$regionid);
        foreach ($groupleadRows as $grow) {
            if ($grow[$what]) {
                return true;
            }
        }

        // Next check if there is a match at Chapter level
        $chapterleadRows = $this->filterChapterleadRecords($groupid, $chapterid);
        foreach ($chapterleadRows as $crow) {
            if ($crow[$what]) {
                return true;
            }
        }
        return false;
    }

    private function isGroupleadOrChannelleadAllowedTo (int $groupid, int $channelid, string $what): bool
    {
        if (
            ($what == 'allow_manage_budget' && $this->canManageZoneBudget()) // Admins need explict budget role.
            ||
            ($what != 'allow_manage_budget' && $this->isAdmin())
        ) {
            return true;
        }
        // First check if there is a match at Group level
        $groupleadRows = $this->filterGroupleadRecords($groupid,0);
        foreach ($groupleadRows as $grow) {
            if ($grow[$what]) {
                return true;
            }
        }

        // Next check if there is a match at Channel level
        $channelleadRows = $this->filterChannelleadRecords($groupid, $channelid);
        foreach ($channelleadRows as $crow) {
            if ($crow[$what]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if the user is manager of specified $group in the company. User needs to be a full lead and not
     * a regional or chapter lead
     * @param int $groupid, should be >= 0 in order to process, otherwise false is returned
     * @return bool
     */
    public function canManageGroup (int $groupid): bool
    {
        return ($groupid >= 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,0,0,'allow_manage'));
    }

    public function canManageBudgetGroup (int $groupid): bool
    {
        return ($groupid >= 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,0,0,'allow_manage_budget'));
    }

    public function canManageGrantGroup (int $groupid): bool
    {
        return ($groupid >= 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,0,0,'allow_manage_grant'));
    }

    public function canManageSupportGroup (int $groupid): bool
    {
        return ($groupid >= 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,0,0,'allow_manage_support'));
    }

    /**
     * Returns true if the user is content creator of specified $group in the company. User needs to be a full lead and not
     * a regional or chapter lead
     * @param int $groupid, should be >= 0 in order to process, otherwise false is returned
     * @return bool
     */
    public function canCreateContentInGroup (int $groupid): bool
    {
        return ($groupid >= 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,0,0,'allow_create_content'));
    }

    /**
     * Returns true if the user is content publisher of specified $group in the company. User needs to be a full lead and not
     * a regional or chapter lead
     * @param int $groupid, should be >= 0 in order to process, otherwise false is returned
     * @return bool
     */
    public function canPublishContentInGroup (int $groupid): bool
    {
        return ($groupid >= 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,0,0,'allow_publish_content'));
    }

    /**
     * This function returns true if user is allowed to Manage Chapter
     * @param int $groupid, should be >= 0 in order to process, otherwise false is returned
     * @param int $regionid, should be >= 0 in order to process, otherwise false is returned
     * @param int $chapterid, should be >= 0 in order to process, otherwise false is returned
     * @return bool
     */
    public function canManageGroupChapter (int $groupid, int $regionid, int $chapterid): bool
    {
        if ($chapterid === 0)
            $regionid = 0; // Regionid cannot be set if chapter is not set.

        return ($groupid >= 0 &&
            $regionid >= 0 &&
            $chapterid >= 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,$regionid,$chapterid,'allow_manage'));
    }

    public function canManageGroupChannel (int $groupid, int $channelid): bool
    {

        return ($groupid >= 0 &&
            $channelid >= 0 &&
            $this->isGroupleadOrChannelleadAllowedTo($groupid,$channelid,'allow_manage'));
    }

    /**
     * This method checks if the user is allowed to manage some chapter in the group.
     * @param int $groupid
     * @return bool
     */
    public function canManageGroupSomeChapter (int $groupid): bool
    {
        return ($groupid > 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,-1,-1,'allow_manage'));
    }

    public function canManageGroupSomeChannel (int $groupid): bool
    {
        return ($groupid > 0 &&
            $this->isGroupleadOrChannelleadAllowedTo($groupid,-1,'allow_manage'));
    }

    public function canManageBudgetGroupChapter (int $groupid, int $regionid, int $chapterid): bool
    {
        if ($chapterid === 0)
            $regionid = 0; // Regionid cannot be set if chapter is not set.

        return ($groupid >= 0 &&
            $regionid >= 0 &&
            $chapterid >= 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,$regionid,$chapterid,'allow_manage_budget'));
    }

    public function canManageGrantGroupChapter (int $groupid, int $regionid, int $chapterid): bool
    {
        if ($chapterid === 0)
            $regionid = 0; // Regionid cannot be set if chapter is not set.

        return ($groupid >= 0 &&
            $regionid >= 0 &&
            $chapterid >= 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,$regionid,$chapterid,'allow_manage_grant'));
    }

    public function canManageSupportGroupChapter (int $groupid, int $regionid, int $chapterid): bool
    {
        if ($chapterid === 0)
            $regionid = 0; // Regionid cannot be set if chapter is not set.

        return ($groupid >= 0 &&
            $regionid >= 0 &&
            $chapterid >= 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,$regionid,$chapterid,'allow_manage_support'));
    }

    public function canManageGrantSomeChapter(int $groupid): bool
    {
        return ($groupid > 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,-1,-1,'allow_manage_grant'));
    }
    public function canManageSupportSomeChapter(int $groupid): bool
    {
        return ($groupid > 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,-1,-1,'allow_manage_support'));
    }

    public function canManageBudgetGroupChannel (int $groupid, int $channelid): bool
    {

        return ($groupid >= 0 &&
            $channelid >= 0 &&
            $this->isGroupleadOrChannelleadAllowedTo($groupid,$channelid,'allow_manage_budget'));
    }

    public function canManageGrantGroupChannel (int $groupid, int $channelid): bool
    {

        return ($groupid >= 0 &&
            $channelid >= 0 &&
            $this->isGroupleadOrChannelleadAllowedTo($groupid,$channelid,'allow_manage_grant'));
    }
    public function canManageSupportGroupChannel (int $groupid, int $channelid): bool
    {

        return ($groupid >= 0 &&
            $channelid >= 0 &&
            $this->isGroupleadOrChannelleadAllowedTo($groupid,$channelid,'allow_manage_support'));
    }

    public function canManageGrantSomeChannel(int $groupid): bool
    {
        return ($groupid >= 0 &&
            $this->isGroupleadOrChannelleadAllowedTo($groupid,-1,'allow_manage_grant'));
    }
    public function canManageSupportSomeChannel(int $groupid): bool
    {
        return ($groupid >= 0 &&
            $this->isGroupleadOrChannelleadAllowedTo($groupid,-1,'allow_manage_support'));
    }
    /**
     * This function returns true if user is allowed to Create Content in a chapter
     * @param int $groupid, should be >= 0 in order to process, otherwise false is returned
     * @param int $regionid, should be >= 0 in order to process, otherwise false is returned
     * @param int $chapterid, should be >= 0 in order to process, otherwise false is returned
     * @return bool
     */
    public function canCreateContentInGroupChapter (int $groupid, int $regionid, int $chapterid): bool
    {
        if ($chapterid === 0)
            $regionid = 0; // Regionid cannot be set if chapter is not set.

        return ($groupid >= 0 &&
            $regionid >= 0 &&
            $chapterid >= 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,$regionid,$chapterid, 'allow_create_content'));
    }

    /**
     * This method checks if the user is allowed to create content in some chapter in the group.
     * @param int $groupid
     * @return bool
     */
    public function canCreateContentInGroupSomeChapter (int $groupid): bool
    {
        return ($groupid >= 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,-1,-1, 'allow_create_content'));
    }

    public function canCreateContentInGroupChannel (int $groupid, int $channelid): bool
    {
        return ($groupid >= 0 &&
            $channelid >= 0 &&
            $this->isGroupleadOrChannelleadAllowedTo($groupid,$channelid, 'allow_create_content'));
    }

    /**
     * This method checks if the user is allowed to create content in some channel in the group.
     * @param int $groupid
     * @return bool
     */
    public function canCreateContentInGroupSomeChannel (int $groupid): bool
    {
        return ($groupid >= 0 &&
            $this->isGroupleadOrChannelleadAllowedTo($groupid,-1, 'allow_create_content'));
    }

    /**
     * This function returns true if user is allowed to Publish Content in a chapter
     * @param int $groupid, should be >= 0 in order to process, otherwise false is returned
     * @param int $regionid, should be >= 0 in order to process, otherwise false is returned
     * @param int $chapterid, should be >= 0 in order to process, otherwise false is returned
     * @return bool
     */
    public function canPublishContentInGroupChapter (int $groupid, int $regionid, int $chapterid): bool
    {
        if ($chapterid === 0)
            $regionid = 0; // Regionid cannot be set if chapter is not set.

        return ($groupid >= 0 &&
            $regionid >= 0 &&
            $chapterid >= 0 &&
            $this->isGroupleadOrChapterleadAllowedTo($groupid,$regionid,$chapterid,'allow_publish_content'));
    }

    public function canPublishContentInGroupChannel (int $groupid, int $channelid): bool
    {

        return ($groupid >= 0 &&
            $channelid >= 0 &&
            $this->isGroupleadOrChannelleadAllowedTo($groupid,$channelid,'allow_publish_content'));
    }
    /**
     * Returns true if the user is manager of group identified by $groupid or any of its chapters
     * @param int $groupid
     * @return bool
     */
    public function canManageGroupSomething (int $groupid): bool
    {
        return $this->isGroupleadOrChapterleadAllowedTo($groupid,-1,-1,'allow_manage')
            || $this->isGroupleadOrChannelleadAllowedTo($groupid, -1,'allow_manage');
    }

    public function canManageBudgetGroupSomething (int $groupid): bool
    {
        return $this->isGroupleadOrChapterleadAllowedTo($groupid,-1,-1,'allow_manage_budget')
            || $this->isGroupleadOrChannelleadAllowedTo($groupid, -1,'allow_manage_budget');
    }

    public function canManageGrantGroupSomething (int $groupid): bool
    {
        return $this->isGroupleadOrChapterleadAllowedTo($groupid,-1,-1,'allow_manage_grant')
            || $this->isGroupleadOrChannelleadAllowedTo($groupid, -1,'allow_manage_grant');
    }

    public function canManageSupportGroupSomething (int $groupid): bool
    {
        return $this->isGroupleadOrChapterleadAllowedTo($groupid,-1,-1,'allow_manage_support')
            || $this->isGroupleadOrChannelleadAllowedTo($groupid, -1,'allow_manage_support');
    }
    /**
     * Returns true if the user is content creator of group identified by $groupid or any of its chapters
     * @return bool
     */
    public function canCreateContentInGroupSomething (int $groupid): bool
    {
        return $this->isGroupleadOrChapterleadAllowedTo($groupid,-1,-1,'allow_create_content')
            || $this->isGroupleadOrChannelleadAllowedTo($groupid, -1,'allow_create_content');
    }

    /**
     * Returns true if the user is content publisher of group identified by $groupid or any of its chapters
     * @return bool
     */
    public function canPublishContentInGroupSomething (int $groupid): bool
    {
        return $this->isGroupleadOrChapterleadAllowedTo($groupid,-1,-1,'allow_publish_content')
            || $this->isGroupleadOrChannelleadAllowedTo($groupid, -1,'allow_publish_content');
    }

    /**
     * Returns true if the user is manager of any group or chapter in the company
     * @return bool
     */
    public function canManageCompanySomething (): bool
    {
        return $this->isGroupleadOrChapterleadAllowedTo(-1,-1,-1,'allow_manage')
            || $this->isGroupleadOrChannelleadAllowedTo(-1,-1,'allow_manage');
    }

    public function canManageBudgetCompanySomething (): bool
    {
        return $this->isGroupleadOrChapterleadAllowedTo(-1,-1,-1,'allow_manage_budget')
            || $this->isGroupleadOrChannelleadAllowedTo(-1,-1,'allow_manage_budget');
    }

    public function canManageGrantCompanySomething (): bool
    {
        return $this->isGroupleadOrChapterleadAllowedTo(-1,-1,-1,'allow_manage_grant')
            || $this->isGroupleadOrChannelleadAllowedTo(-1,-1,'allow_manage_grant');
    }
    public function canManageSupportCompanySomething (): bool
    {
        return $this->isGroupleadOrChapterleadAllowedTo(-1,-1,-1,'allow_manage_support')
            || $this->isGroupleadOrChannelleadAllowedTo(-1,-1,'allow_manage_support');
    }

    /**
     * Returns true if the user is content creator of any group or chapter in the company
     * @return bool
     */
    public function canCreateContentInCompanySomething (): bool
    {
        return $this->isGroupleadOrChapterleadAllowedTo(-1,-1,-1,'allow_create_content')
            || $this->isGroupleadOrChannelleadAllowedTo(-1,-1,'allow_create_content');
    }

    /**
     * Returns true if the user is content publisher of any group or chapter in the company
     * @return bool
     */
    public function canPublishContentInCompanySomething (): bool
    {
        return $this->isGroupleadOrChapterleadAllowedTo(-1,-1,-1,'allow_publish_content')
            || $this->isGroupleadOrChannelleadAllowedTo(-1,-1,'allow_publish_content');
    }

    /**
     * Return raw record of groupleads type that matches the groupid
     * @param int $groupid - Group id to check
     * @return array - All the matching rows or empty array
     */
    public function getMyGroupleadRecords(int $groupid): array
    {
        return self::DBGet("
                SELECT groupleads.*,grouplead_type.*,`groups`.groupname,`groups`.zoneid 
                FROM `groupleads` 
                    JOIN `grouplead_type` ON `groupleads`.grouplead_typeid = `grouplead_type`.typeid 
                    JOIN `groups` ON groupleads.`groupid` = `groups`.`groupid` 
                WHERE `groupleads`.userid='{$this->id}' 
                    AND `grouplead_type`.companyid = '{$this->cid}'
                    AND `groupleads`.isactive=1
                    AND `grouplead_type`.isactive=1
                    AND `groupleads`.groupid={$groupid}
        ");

    }

    /**
     * Return raw record of chapterleads type that matches the groupid, chapterid
     * @param int $groupid - Group id to check
     * @param int $chapterid - Chapter id to check
     * @return array - All the matching rows or empty array
     */
    public function getMyChapterleadRecords(int $groupid, int $chapterid): array
    {
        return self::DBGet("
                SELECT * FROM `chapterleads` 
                    JOIN `grouplead_type` ON `chapterleads`.grouplead_typeid = `grouplead_type`.typeid
                    JOIN `groups` ON chapterleads.groupid = `groups`.groupid
                    JOIN chapters  ON chapterleads.`chapterid` = chapters.`chapterid` 
                WHERE `chapterleads`.userid='{$this->id}' 
                    AND `groups`.isactive=1
                    AND `grouplead_type`.companyid = '{$this->cid}'
                    AND `chapterleads`.isactive=1 
                    AND `grouplead_type`.isactive=1
                    AND `chapterleads`.`groupid`={$groupid}
                    AND `chapterleads`.`chapterid`={$chapterid}
                  ");
    }

    public function getMyChannelleadRecords(int $groupid, int $channelid): array
    {
        return self::DBGet("
                SELECT * FROM `group_channel_leads` 
                    JOIN `grouplead_type` ON `group_channel_leads`.grouplead_typeid = `grouplead_type`.typeid 
                    JOIN `groups` ON group_channel_leads.groupid = `groups`.groupid
                    JOIN group_channels  ON group_channel_leads.`channelid` = group_channels.`channelid`
                WHERE `group_channel_leads`.userid='{$this->id}' 
                    AND `grouplead_type`.companyid = '{$this->cid}'
                    AND `group_channel_leads`.isactive=1 
                    AND `grouplead_type`.isactive=1
                    AND `group_channel_leads`.`groupid`={$groupid}
                    AND `group_channel_leads`.`channelid`={$channelid}
                  ");
    }

    /**
     * This method returns true if user can view group content. group id is always true.
     * @param int $groupid
     * @return bool
     */
    public function canViewContent(int $groupid, bool $fromMaster = false): bool
    {
        if (!$groupid)
            return true;

        if ($this->groups_restricted_for_content === null || $fromMaster) {
             $sql_restricted_groups = "SELECT g.groupid FROM `groups` g WHERE g.companyid={$this->cid()} AND g.content_restrictions = 'members_only_can_view'";
             $sql_member_groups = "SELECT gm.groupid FROM groupmembers gm WHERE userid={$this->id} AND gm.isactive=1";

            if ($fromMaster) {
                $g = self::DBGet($sql_restricted_groups);
                $gm = self::DBGet($sql_member_groups);
            }
            else {
                $g = self::DBROGet($sql_restricted_groups);
                $gm = self::DBROGet($sql_member_groups);
            }

            $this->groups_restricted_for_content = array_diff(
                Arr::IntValues(array_column($g,'groupid')),
                Arr::IntValues(array_column($gm,'groupid')),
                );
        }
        
        return !in_array($groupid, $this->groups_restricted_for_content);
    }

    /**
     * @param int $groupid
     * @param int $chapterid
     * @param int $channelid
     * @return bool
     */
    public function canManageBudgetInScope(int $groupid, int $chapterid, int $channelid)
    {
        if ($groupid === 0) {
            return $this->isAdmin();
        }

        if (!$chapterid && !$channelid) {
            return $this->canManageBudgetGroup($groupid);
        }

        $allowChapter = true;
        if ($chapterid) {
            $regionid = Group::GetChapterName($chapterid,$groupid)['regionids'] ?? 0;
            $allowChapter = $this->canManageBudgetGroupChapter($groupid, $regionid, $chapterid);
        }

        $allowChannel = true;
        if ($channelid) {
            $allowChannel = $this->canManageBudgetGroupChannel($groupid, $channelid);
        }

        return $allowChapter && $allowChannel;
    }

    /**
     * Process (1) Admin , (2) Group Lead , (3) Any Channel Lead , (4) *ANY* Chapter Lead
     * @param int $groupid
     * @param string $chapterids CSV of various chapterids
     * @param string $channelids CSV of various channelids
     * @return bool
     */
    public function canManageContentInScopeCSV(int $groupid, string $chapterids='', string $channelids='')
    {
        # Step 1 - For global scope check if  Admin
        if ($groupid === 0) {
            return $this->isAdmin();
        }

        # Step 2 - Check if group lead
        if ($this->canManageGroup($groupid)) {
            return true;
        }

        # Step 3 - Check if channel lead of one of the channels
        $channelids = trim($channelids);
        if ($channelids) {
            $channelid_arr = array_filter(explode(',',$channelids));
            foreach ($channelid_arr as $channelid) {
                if ($this->canManageGroupChannel($groupid, $channelid)) {
                        return true;
                }
            }
        }

        # Step 4 - Check if chapter lead
        $chapterids = trim($chapterids);
        $allowChapter = false;
        if ($chapterids) {
            //Filter out if any blank or null chapterids
            $chapterid_arr = array_filter(explode(',',$chapterids));
            foreach ($chapterid_arr as $chapterid) {
                $regionid = Group::GetChapterName($chapterid,$groupid)['regionids'] ?? 0;
                $allowChapter = $this->canManageGroupChapter($groupid, $regionid, $chapterid);
                if ($allowChapter){ // break loop if allowd
                    break;
                }
            }
        }
        return $allowChapter;
    }

    /**
     * This method is different from canManageContentInScopeCSV in a way that it will test every chapter or channel in
     * scope and return true only if the user can manage every one of them.
     * Process (1) Admin , (2) Group Lead , (3) All Channels & all Chapters
     * @param int $groupid
     * @param string $chapterids CSV of various chapterids
     * @param string $channelids CSV of various channelids
     * @return bool
     */
    public function canManageContentInEveryScopeCSV(int $groupid, string $chapterids='', string $channelids='')
    {
        # Step 1 - For global scope check if  Admin
        if ($groupid === 0) {
            return $this->isAdmin();
        }

        # Step 2 - Check if group lead
        if ($this->canManageGroup($groupid)) {
            return true;
        }

        # Step 3 - Check if channel lead of one of the channels
        $channelids = trim($channelids);
        if ($channelids) {
            $channelid_arr = array_filter(explode(',',$channelids));
            foreach ($channelid_arr as $channelid) {
                if (!$this->canManageGroupChannel($groupid, $channelid)) {
                    return false;
                }
            }
        }

        # Step 4 - Check if chapter lead
        $chapterids = trim($chapterids);
        $allowChapter = false;
        if ($chapterids) {
            //Filter out if any blank or null chapterids
            $chapterid_arr = array_filter(explode(',',$chapterids));
            foreach ($chapterid_arr as $chapterid) {
                $regionid = Group::GetChapterName($chapterid,$groupid)['regionids'] ?? 0;
                if (!$this->canManageGroupChapter($groupid, $regionid, $chapterid)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Process (1) Admin , (2) Group Lead , (3) Any Channel Lead , (4) *ALL* Chapter Lead
     * @param int $groupid
     * @param string $chapterids
     * @param string $channelids
     * @return bool
     */
    public function canCreateContentInScopeCSV(int $groupid, string $chapterids = '', string $channelids = '')
    {
        # Step 1 - For global scope check if  Admin
        if ($groupid === 0) {
            return $this->isAdmin();
        }

        # Step 2 - Check if group lead
        if ($this->canCreateContentInGroup($groupid)) {
            return true;
        }

        # Step 3 - Check if channel lead of one of the channels
        $channelids = trim($channelids);
        if ($channelids) {
            $channelid_arr = array_filter(explode(',',$channelids));
            foreach ($channelid_arr as $channelid) {
                if ($this->canCreateContentInGroupChannel($groupid, $channelid)) {
                    return true;
                }
            }
        }

        $chapterids = trim($chapterids);
        if ($chapterids) {
            $allowChapter = true;
            //Filter out if any blank or null chapterids
            $chapterid_arr = array_filter(explode(',',$chapterids));
            foreach ($chapterid_arr as $chapterid) {
                $regionid = Group::GetChapterName($chapterid,$groupid)['regionids'] ?? 0;
                $allowChapter = $allowChapter && $this->canCreateContentInGroupChapter($groupid, $regionid, $chapterid);
            }
            if ( $allowChapter){
                return true;
            }
        }


        return false;
    }

    /**
     * Process: for published content use canCreateContentInScopeCSV && canPublishContentInScopeCSV otherwise canCreateContentInScopeCSV.
     * @param int $groupid
     * @param string $chapterids
     * @param string $channelids
     * @param int $isactive
     * @return bool
     */
    public function canUpdateContentInScopeCSV(int $groupid, string $chapterids, string $channelids, int $isactive)
    {
        if ($isactive === Teleskope::STATUS_DRAFT || $isactive === Teleskope::STATUS_UNDER_REVIEW) {
            return $this->canCreateContentInScopeCSV($groupid, $chapterids, $channelids);
        } else { // For published content, person needs to be a publisher and creator to edit.
            return $this->canCreateContentInScopeCSV($groupid, $chapterids, $channelids) &&
                $this->canPublishContentInScopeCSV($groupid, $chapterids, $channelids);
        }
    }

    /**
     * Process (1) Admin , (2) Group Lead , (3) Any Channel Lead , (4) *ALL* Chapter Lead
     * @param int $groupid
     * @param string $chapterids
     * @param string $channelids
     * @return bool
     */
    public function canPublishContentInScopeCSV(int $groupid, string $chapterids='', string $channelids = '')
    {
        # Step 1 - For global scope check if  Admin
        if ($groupid === 0) {
            return $this->isAdmin();
        }

        # Step 2 - Check if group lead
        if ($this->canPublishContentInGroup($groupid)) {
            return true;
        }

        # Step 3 - Check if channel lead of one of the channels
        $channelids = trim($channelids);
        if ($channelids) {
            $channelid_arr = array_filter(explode(',',$channelids));
            foreach ($channelid_arr as $channelid) {
                if ($this->canPublishContentInGroupChannel($groupid, $channelid)) {
                    return true;
                }
            }
        }

        $chapterids = trim($chapterids);
        if ($chapterids) {
            $allowChapter = true;
            //Filter out if any blank or null chapterids
            $chapterid_arr = array_filter(explode(',',$chapterids));
            foreach ($chapterid_arr as $chapterid) {
                $regionid = Group::GetChapterName($chapterid,$groupid)['regionids'] ?? 0;
                $allowChapter = $allowChapter && $this->canPublishContentInGroupChapter($groupid, $regionid, $chapterid);
            }
            if ($allowChapter){
                return true;
            }
       }
        return false;
    }

    /**
     * Process Create Or Publish
     * @param int $groupid
     * @param string $chapterids
     * @param string $channelids
     * @return bool
     */
    public function canCreateOrPublishContentInScopeCSV(int $groupid, string $chapterids='', string $channelids='')
    {
        return $this->canCreateContentInScopeCSV($groupid,$chapterids,$channelids) ||
            $this->canPublishContentInScopeCSV($groupid,$chapterids,$channelids);
    }

    public function canCreateOrPublishOrManageContentInScopeCSV(int $groupid, string $chapterids='', string $channelids='')
    {
        return $this->canCreateContentInScopeCSV($groupid,$chapterids,$channelids) ||
            $this->canPublishContentInScopeCSV($groupid,$chapterids,$channelids) ||
            $this->canManageContentInScopeCSV($groupid,$chapterids,$channelids);
    }

    public function canPublishOrManageContentInScopeCSV(int $groupid, string $chapterids='', string $channelids='')
    {
        return $this->canPublishContentInScopeCSV($groupid,$chapterids,$channelids) ||
            $this->canManageContentInScopeCSV($groupid,$chapterids,$channelids);
    }


    public function canManageCompanySurveys(): bool
    {
        return $this->isAdmin();
    }

    /**
     * This function returns the initials constructed from firstname and lastname. If firstname and lastname
     * are empty, then first letter of email is returned.
     * @return string Uppercase initials, or smiley :) if initials are not available
     */
    public function getInitials(): string
    {
        $retVal = substr($this->val('firstname'), 0, 1) . substr($this->val('lastname'), 0, 1);
        if (empty($retVal))
            $retVal = substr($this->val('email'), 0, 1);
        return strtoupper($retVal ?: ':)');
    }


    public function lock () {
        global $_COMPANY;

        self::LogObjectLifecycleAudit('state_change', 'user', $this->id(), 0, [
            'previous_state' => $this->val('isactive'),
            'new_state' => self::STATUS_UNDER_REVIEW,
            'oper_details' => 'Lock user',
        ]);

        $retVal = self::DBMutate("UPDATE users SET isactive=". self::STATUS_UNDER_REVIEW .", modified=now() WHERE userid='{$this->id}'");
        self::DBMutate("DELETE FROM users_api_session WHERE `userid`='{$this->id()}'");
        User::GetUser($this->id, true); // Refresh cache after loading from master DB
        return $retVal;
    }

    public function purge()
    {
        global $_COMPANY;

        self::LogObjectLifecycleAudit('state_change', 'user', $this->id(), 0, [
            'previous_state' => $this->val('isactive'),
            'new_state' => self::STATUS_PURGE,
            'oper_details' => 'Purge user',
        ]);

        self::DBMutate("DELETE FROM users_api_session WHERE `userid`='{$this->id()}'");
        $retVal = self::DBMutate("UPDATE `users` SET `isactive`=". self::STATUS_PURGE .", modified=now() WHERE userid='{$this->id}' AND isactive < 100");
        User::GetUser($this->id, true); // Refresh cache after loading from master DB

        // Handle Joined Team
        Team::HandleUserTerminations($this->id);
        
        return $retVal;
    }

    public function block()
    {
        global $_COMPANY;

        self::LogObjectLifecycleAudit('state_change', 'user', $this->id(), 0, [
            'previous_state' => $this->val('isactive'),
            'new_state' => self::STATUS_BLOCKED,
            'oper_details' => 'Block user',
        ]);

        $retVal = self::DBMutate("UPDATE `users` SET `isactive`=". self::STATUS_BLOCKED .", modified=now() WHERE userid='{$this->id}'");
        self::DBMutate("DELETE FROM users_api_session WHERE `userid`='{$this->id()}'");
        User::GetUser($this->id, true); // Refresh cache after loading from master DB
        return $retVal;
    }


    public function unblock()
    {
        global $_COMPANY;

        self::LogObjectLifecycleAudit('state_change', 'user', $this->id(), 0, [
            'previous_state' => $this->val('isactive'),
            'new_state' => self::STATUS_ACTIVE,
            'oper_details' => 'Unblock user',
        ]);

        $retVal = self::DBMutate("UPDATE `users` SET `isactive`=". self::STATUS_ACTIVE .", modified=now() WHERE userid='{$this->id}'");
        User::GetUser($this->id, true); // Refresh cache after loading from master DB
        return $retVal;
    }

    public function wipeClean()
    {

        self::LogObjectLifecycleAudit('state_change', 'user', $this->id(), 0, [
            'previous_state' => $this->val('isactive'),
            'new_state' => self::STATUS_WIPE_CLEAN,
            'oper_details' => 'Wipe Clean user',
        ]);

        self::DBMutate("DELETE FROM users_api_session WHERE `userid`='{$this->id()}'");
        $retVal = self::DBMutate("UPDATE `users` SET `isactive`=". self::STATUS_WIPE_CLEAN .", modified=now() WHERE userid='{$this->id}'");
        User::GetUser($this->id, true); // Refresh cache after loading from master DB
        return $retVal;
    }

    public function reset() {
        global $_COMPANY; /* @var Company $_COMPANY */

        self::LogObjectLifecycleAudit('state_change', 'user', $this->id(), 0, [
            'previous_state' => $this->val('isactive'),
            'new_state' => self::STATUS_ACTIVE,
            'oper_details' => 'Reset user',
        ]);

        $retVal = (self::DBMutate("UPDATE users SET isactive=". self::STATUS_ACTIVE .", verificationstatus=1, modified=now() WHERE userid='{$this->id}'"));
        $this->fields['verificationstatus'] = '1';
        $this->fields['isactive'] = '1';
        User::GetUser($this->id, true); // Refresh cache after loading from master DB
        return $retVal;
    }

    public function delete(bool $deleteClean = false, bool $forceDelete = false): int
    {
        global $_COMPANY; /* @var Company $_COMPANY */

        if (empty($this->id())) {
            return 0; // Do not continue if the userid is 0 as it can impact rows of other users
        }

        if (!$_COMPANY->getUserLifecycleSettings()['allow_delete'] && !$forceDelete) {
            // Need allow_delete to be set in user lifecycle to delete the user;
            return 0;
        }

        if (
            (time() < strtotime($this->val('modified')) + $_COMPANY->getUserLifecycleSettings()['delete_after_days'] * 86400) &&
            !$forceDelete
        ){
            // Do not delete if aging time has not passed.
            return 0;
        }

        self::LogObjectLifecycleAudit('delete', 'user', $this->id(), 0, [
            'ids' => $this->getEncryptedIdentityForLogging(),
            'previous_state' => $this->val('isactive'),
            'last_modified_on' => $this->val('modified'),
            'last_validated_on' => $this->val('validatedon'),
            'force_delete' => $forceDelete,
            'delete_clean' => $deleteClean,
            'oper_details' => 'Permanently deleting',
        ]);

        $reset_survey_profile = '{"firstname":"User","lastname":"Deleted","email":""}';
        $deleteAdmin  = self::DBMutate("DELETE FROM company_admins WHERE companyid={$_COMPANY->id()} AND userid={$this->id()}");
        $delete1 = self::DBMutate("DELETE FROM users_api_session WHERE `userid`='{$this->id()}'");
        //$delete2	= self::DBMutate("DELETE FROM `appusage` WHERE `userid`='{$this->id()}'");
        $delete3 = self::DBMutate("DELETE FROM `notifications` WHERE `userid`='{$this->id()}'");

        $members = self::DBGet("SELECT * FROM `groupmembers` WHERE `userid`='{$this->id()}'");
        if (!empty($members))
            foreach($members as $member){
            $chapterids = explode(',',$member['chapterid']);
            $channelids = explode(',',$member['channelids']);

            // Create Group user log
            GroupUserLogs::CreateGroupUserLog($member['groupid'], $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, '', 0, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);

            foreach ($chapterids as $chap) {
                if ($chap) {
                    // Create Group user log
                    GroupUserLogs::CreateGroupUserLog($member['groupid'], $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chap, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
                }
            }
            foreach ($channelids as $chan) {
                if ($chan) {
                    // Create Group user log
                    GroupUserLogs::CreateGroupUserLog($member['groupid'], $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $chan, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
                }
            }
        }
        $delete5 = self::DBMutate("DELETE FROM `groupmembers` WHERE `userid`='{$this->id()}'");

        $leads = self::DBGet("SELECT * FROM `groupleads` WHERE `userid`='{$this->id()}'");
        foreach($leads as $lead){
            // Group User Log
            GroupUserLogs::CreateGroupUserLog($lead['groupid'], $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_LEAD'], $lead['grouplead_typeid'], '', 0, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
        }
        $delete4 = self::DBMutate("DELETE FROM `groupleads` WHERE `userid`='{$this->id()}'");

        $chapterLeads = self::DBGet("SELECT * FROM `chapterleads` WHERE `userid`='{$this->id()}'");
        foreach($chapterLeads as $lead){
            GroupUserLogs::CreateGroupUserLog($lead['groupid'], $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHAPTER_LEAD'], $lead['grouplead_typeid'], GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $lead['chapterid'], GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
        }
        $delete7 = self::DBMutate("DELETE FROM `chapterleads` WHERE `userid`='{$this->id()}'");

        $channelLeads = self::DBGet("SELECT * FROM `group_channel_leads` WHERE `userid`='{$this->id()}'");
        foreach($channelLeads  as $lead){
            GroupUserLogs::CreateGroupUserLog($lead['groupid'], $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHANNEL_LEAD'], $lead['grouplead_typeid'], GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $lead['channelid'],GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
        }
        $delete8 = self::DBMutate("DELETE FROM `group_channel_leads` WHERE `userid`='{$this->id()}'");
        if ($deleteClean) {
            $delete6a = self::DBMutate("UPDATE topic_comments SET userid=0, comment='*** comment erased ***' WHERE companyid={$_COMPANY->id()} AND userid={$this->id()}");
        } else {
            $delete6a = self::DBMutate("UPDATE topic_comments SET userid=0 WHERE companyid={$_COMPANY->id()} AND userid={$this->id()}");
        }
        $delete6b = self::DBMutate("UPDATE topic_likes SET userid=0 WHERE companyid={$_COMPANY->id()} AND userid={$this->id()}");
        $delete10 = self::DBMutate("UPDATE  survey_responses_v2 JOIN surveys_v2 USING(surveyid) SET survey_responses_v2.userid='0',survey_responses_v2.profile_json=IF(survey_responses_v2.profile_json = '' or survey_responses_v2.profile_json is null,'','{$reset_survey_profile}') WHERE surveys_v2.companyid={$_COMPANY->id()} AND survey_responses_v2.companyid={$_COMPANY->id()} AND survey_responses_v2.userid={$this->id()}");
        $delete11 = self::DBMutate("DELETE FROM `event_volunteers` WHERE `userid`='{$this->id()}'");

        // For delete clean and regular clean we dis-associating the user and moving them other data. It is same as if the user data was added manually.
        $other_data=json_encode(array('firstname'=>$this->val('firstname'),'lastname'=>$this->val('lastname'),'email'=>$this->val('email')));
        $update12 = self::DBMutatePS("UPDATE `eventjoiners` SET `userid`=0, `other_data`=? WHERE `userid`=?",'xi',$other_data,$this->id());

        // For delete clean we will not delete the discussion content created by users, we will only delete user id. Content is part of organizations content at this point.
        $delete14 = self::DBMutate("UPDATE discussions SET `createdby`=0 WHERE companyid={$_COMPANY->id()} AND createdby={$this->id()}");

        $delete15 = self::DBMutate("DELETE FROM topic_approvals__configuration_approvers WHERE companyid={$_COMPANY->id()} AND approver_userid={$this->id()}");

        $delete18 = self::DBMutate("DELETE FROM teleskope_mailing_list WHERE companyid={$_COMPANY->id()} AND userid={$this->id()}");

        if ($this->has('email')) {
            $delete13 = self::DBMutatePS("DELETE FROM `memberinvites` WHERE companyid=? AND `email`=?", 'ix',$_COMPANY->id(), $this->val('email'));
        }

        if ($this->has('picture')) {
            $_COMPANY->deleteFile($this->val('picture'));
        }

        $this->deleteAllUserPreferences();

        DelegatedAccess::HandleUserTerminations($this->id(), "Name: {$this->getFullName()} | Email: {$this->getEmailForDisplay()} | ExternalId: {$this->getExternalId()}");

        $delete = self::DBMutate("DELETE FROM `users` WHERE `userid`='{$this->id()}'");
        $this->fields = "";
        $_COMPANY->expireRedisCache("USR:{$this->id}");
        return $delete;
    }

    public function getFullName(bool $includePronouns=false): string
    {
        global $_COMPANY;
        if ($includePronouns && !$_COMPANY->getAppCustomization()['profile']['enable_pronouns']) {
            $includePronouns = false;
        }

        return rtrim(
            $this->val('firstname') . ' ' .
            $this->val('lastname') .
            (($includePronouns && !empty($this->val('pronouns'))) ? ' (' . $this->val('pronouns') .')' : '')
        );
    }

    /**
     * Chooses the best email address for display between email and external_email of user.
     * @param string $email
     * @param string|null $external_email
     * @return string
     */
    public static function PickEmailForDisplay (string $email, ?string $external_email, bool $both=false) : string
    {
        global $_COMPANY;
        // return email is it is a valid and routable email
        // else if external email is set return it... if not set then return whatever value we have for email
        return $both
            ?  trim (($email . ', ' . $external_email ?? ''), ' ,')
            : ($_COMPANY->isValidAndRoutableEmail($email) ? $email : ($external_email ?? $email));
    }

    public function getEmailForDisplay (bool $both = false) : string
    {
        return self::PickEmailForDisplay($this->val('email') ?? '', $this->val('external_email'), $both);
    }

    public function getDepartmentName(): string
    {
        global $_COMPANY; /* @var Company $_COMPANY; */
        return $_COMPANY->getDepartmentName((int)$this->val('department'));
    }

    public function getBranchName(): string
    {
        global $_COMPANY; /* @var Company $_COMPANY; */
        return $_COMPANY->getBranchName((int)$this->val('homeoffice'));
    }

    /**
     * Do not use this method for logged in users picture update.
     * @param string $picture
     * @return int
     */
    public function updatePicture(string $picture): int
    {
        global $_COMPANY;
        $this->fields['picture'] = $picture; //update local copy
        $retVal = self::DBUpdatePS("UPDATE `users` SET `picture`=? WHERE companyid=? AND `userid`=?",'sii',$picture, $_COMPANY->id(), $this->id);
        User::GetUser($this->id, true); // Refresh cache after loading from master DB
        return $retVal;
    }

    public function __toString()
    {
        return "User " . parent::__toString();
    }

    /**
     * This method is used to get Azure Active Directoy profile using the Graph API with signed in persons token
     * @param $myToken - The token of the signedin user
     * @return array with attributes, or null if the Graph API cannot get data
     */
    public static function GetO365User($myToken)
    {

        $service_url = 'https://graph.microsoft.com/v1.0/me/';
        $header = array();
        $header[] = 'Authorization: Bearer ' . $myToken;
        $ch = curl_init($service_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        $ch_response = curl_exec($ch);
        curl_close($ch);

        if ($ch_response) {
            //Logger::Log("User->GetO365User recieved {$ch_response}");
            return json_decode($ch_response, true);
        }
        return null;
    }

    /** Thus function updates current users O365 profile using the provided access token
     * The token can be user or admin token
     * The function will update profile picture if it has changed.
     * It also checks the other fields and updates them.
     * All O365 comparisons are done using Azure Active Directory 'id' (which we call as aad_oid in the system)
     *
     * Returns:
     *    0 - No operation
     *    1 - Success
     *   -1 - User Not found in Azure Active Directory
     *   -2 - Access Token Expired
     * @param string $token
     * @param array $customization
     * @return int
     */
    public function updateO365Profile(string $token, array $customization): int
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        $retVal = 0;
        $header = array();
        $header[] = 'Authorization: Bearer ' . $token;

        if (empty($this->fields['email'])) {
            return -1;
        }
        // Part 0 - Before updating profile, check if the user has aad_oid  set
        // If not, then first update those. This is the usecase where users got migrated from username/password to AAD
        // For getting the AAD OID, our preference is to use userPrincipalName which is mapped to externalusername in
        // Teleskope database. Only if userPrincipalName is not set, then we use the email. The reason is that email
        // changes often but userPrincipal name is relatively static.
        if (empty($this->fields['aad_oid'])) {

            if (empty($this->fields['externalusername'])) {
                $url0 = 'https://graph.microsoft.com/v1.0/users/?$filter=Mail%20eq%20%27' . $this->fields['email'] . "%27";
            } else {
                $url0 = 'https://graph.microsoft.com/v1.0/users/?$filter=userPrincipalName%20eq%20%27' . $this->fields['externalusername'] . "%27";
            }
            $ch0 = curl_init($url0);
            curl_setopt($ch0, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch0, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch0, CURLOPT_TIMEOUT, 50);
            $ch_response0 = curl_exec($ch0);
            curl_close($ch0);

            if ($ch_response0) {
                //Logger::Log("Sync0 ({$url0}) Got = " . $ch_response0);
                $resp0 = json_decode($ch_response0, true);
                if (!empty($resp0['error']) && $resp0['error']['code'] === 'InvalidAuthenticationToken')
                    return -2;
                if (isset($resp0['value'][0]['id']) && !empty($resp0['value'][0]['id'])) {
                    $aad_oid0 = $resp0['value'][0]['id']; // add suffix
                    $externalusername0 = $resp0['value'][0]['userPrincipalName'];
                    // First check if there is another user with the same aad_oid, if so merge the users;
                    $other_user = User::GetUserByAadOid($aad_oid0);
                    if ($other_user && $other_user->id() != $this->id()) {
                        $merge_status = User:: MergeUsers($other_user->id(), $this->id());
                        if (!empty($merge_status['status'])) {
                            // Merge was successful, there is nothing else we can do as the current user might have been
                            // merged with the other_user so lets exit.
                            return 1;
                        }
                    } else {
                        if (self::DBUpdate("UPDATE users SET aad_oid='{$aad_oid0}',externalusername='{$externalusername0}' WHERE userid='{$this->id}'")) {
                            $this->fields['aad_oid'] = $aad_oid0;
                            $this->fields['externalusername'] = $externalusername0;
                            User::GetUser($this->id, true); // Refresh cache after loading from master DB

                            self::LogObjectLifecycleAudit('update', 'user', $this->id(), 0, [
                                'ids' => $this->getEncryptedIdentityForLogging(),
                                'oper_details' => 'updated aad_oid, externalusername',
                            ]);

                        }
                    }
                }
            }
        }

        if (empty($this->fields['aad_oid'])) {
            Logger::Log("updateO365Profile: Error for [{$_COMPANY->id()}|{$this->id}]. Error = Error retrieving OID from AAD", Logger::SEVERITY['WARNING_ERROR']);
            return -1;
        }


        // Part 1 - Update Profile attributes
        //   Also updates the validation time and sets the status to Active
        //   Lookup graph api using ID and then match for principalName with username - complete the loop
        $service_url = 'https://graph.microsoft.com/v1.0/users/' . $this->getAadOid() . '?$select=id';

        $attrs = array();
        foreach ($customization['fields'] as $key => $value) {
            if ($key === 'extended') {
                // Extract embedded values
                foreach ($value as $key2 => $value2) {
                    $attrs[] = $value2['ename'];
                }
            } else {
                $attrs[] = $value['ename'];
            }
        }

        $attrs[] = 'userPrincipalName';
        $attrs = array_unique($attrs);
        $service_url .= ','.implode(',',$attrs);

        $ch = curl_init($service_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        $ch_response = curl_exec($ch);
        curl_close($ch);

        if ($ch_response) {
            $resp = json_decode($ch_response, true);
            if (!empty($resp['error']) && $resp['error']['code'] === 'InvalidAuthenticationToken')
                return -2;
            if (isset($resp['id'])) {

                $externalId = User::XtractAndXformValue('externalid', $resp, $customization['fields']);
                $newEmail = User::XtractAndXformValue('email', $resp, $customization['fields']);
                $firstname = User::XtractAndXformValue('firstname', $resp, $customization['fields']);
                $lastname = User::XtractAndXformValue('lastname', $resp, $customization['fields']);
                $pronouns = User::XtractAndXformValue('pronouns', $resp, $customization['fields']);
                $jobTitle = User::XtractAndXformValue('jobtitle', $resp, $customization['fields']);
                $opco = User::XtractAndXformValue('opco', $resp, $customization['fields']);
                $department = User::XtractAndXformValue('department', $resp, $customization['fields']);
                $employeeType = User::XtractAndXformValue('employeetype', $resp, $customization['fields']);
                $officeLocation = User::XtractAndXformValue('branchname', $resp, $customization['fields']);
                $externalRoles = User::XtractAndXformValue('externalroles', $resp, $customization['fields'], true);
                $externalUsername = $resp['userPrincipalName'];

                $extended_profile = array();
                if (!empty($customization['fields']['extended'])) {
                    foreach ($customization['fields']['extended'] as $key => $value) {
                        $extended_profile[$key] = User::XtractAndXformValue($key, $resp, $customization['fields']['extended']);
                    }
                }
                // Extended Profile can contain main UTF-8 characters and we want to store them as is so we will use JSON_UNESCAPED_UNICODE
                $extended_profile_str = json_encode($extended_profile, JSON_UNESCAPED_UNICODE);
                //Note: For Microsoft, aad_oid is used as a primary key, but externalid can still be set to allow sync with HRIS
                $this->updateExternalId($externalId);
                if ($this->getExternalId() !== $externalId) {
                    // External id was not updated, most likely there is another user with the same external id
                    // Lets find the other user and merge
                    $other_user = User::GetUserByExternalId($externalId, true);
                    if ($other_user && $other_user->id() != $this->id()) {
                        $merge_status = User:: MergeUsers($other_user->id(), $this->id());
                        if (!empty($merge_status['status'])) {
                            // Merge was successful, there is nothing else we can do as the current user might have been
                            // merged with the other_user so lets exit.
                            return 1;
                        }
                    }
                }

                $this->updateExternalRoles($externalRoles);

                if ($this->updateProfile2($newEmail, $firstname, $lastname, $pronouns, $jobTitle, $department, $officeLocation, '','','', '', $opco, $employeeType, $externalUsername, $extended_profile_str, true, null, null, null)) {
                    $retVal = 1;
                }
            } elseif (isset($resp['error'])) {
                if ($resp['error']['code'] === 'Request_ResourceNotFound') {
                    Logger::Log("updateO365Profile: [{$_COMPANY->id()}|{$this->id}]. Error = {$resp['error']['code']}, Message = {$resp['error']['message']}", Logger::SEVERITY['INFO']);
                } else {
                    Logger::Log("updateO365Profile: Error processing [{$_COMPANY->id()}|{$this->id}]. Error = {$resp['error']['code']}, Message = {$resp['error']['message']}");
                }                  
                return -1; // The user no longer exists else
            }
        }

        // Part 2 - Update Profile Picture if available
        // But only update if picture is not already set or settings tell us to always synx
        $sync_always = $customization['picture']['sync_always'] ?? false;
        $sync_if_null = $customization['picture']['sync_if_null'] ?? true;
        if (!$sync_always &&
            !($sync_if_null && !$this->has('picture'))) {
            return $retVal;
        }

        $file = '/tmp/' . uniqid('O365_', true); // temp file for storing the fetched picture
        $out = fopen($file, 'wb');

        if ($retVal === 1 && $out) {
            // Process only if the Part 1 was successful
            $service_url = 'https://graph.microsoft.com/v1.0/users/' . $this->getAadOid() . '/photos/240x240/$value';
            $ch = curl_init($service_url);
            $etag = '1';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_FILE, $out);
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION,
                static function ($ch, $h) use (&$etag) {
                    if (strpos($h, 'ETag') === 0) {
                        $etag = trim(trim(explode(':', $h, 2)[1]), '"');
                    }
                    return strlen($h);
                });

            $ch_response = curl_exec($ch);
            fclose($out);

            if ($ch_response) {
                //Logger::Log("User->updateO365Picture recieved {$ch_response}");
                $extensions = array(
                    'image/jpeg' => 'jpeg',
                    'image/gif' => 'gif',
                    'image/png' => 'png',
                    'image/bmp' => 'bmp',
                    'image/webp' => 'webp'
                );

                $mime_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

                if ($this->has('picture')) {
                    $old_picture = $this->val('picture');
                } else {
                    $old_picture = '';
                }

                //Change the picture if it is set and the etag has changed
                if (isset($extensions[$mime_type]) && (strpos($old_picture, '_' . $etag . '_') === FALSE)) {
                    $ext = $extensions[$mime_type];
                    $picture_name = 'profile_' . teleskope_uuid() . $etag . '_' . generateRandomToken(10) . '.' . $ext;
                    $picture = $_COMPANY->saveFile($file, $picture_name,'USER');
                    if (!empty($picture)) {
                        $this->updatePicture($picture);
                        if (!empty($old_picture)) {
                            $_COMPANY->deleteFile($old_picture);
                        }
                    }
                } elseif (str_contains($mime_type, 'json')) { //Else check if GraphAPI returned error
                    $errors = json_decode(file_get_contents($file), TRUE);
                    if ($errors['error']['code'] === 'ErrorItemNotFound' || $errors['error']['code'] === 'ImageNotFound') {
                        if (!empty($old_picture)) {
                            $this->updatePicture('');
                            $_COMPANY->deleteFile($old_picture);
                        }
                    } else {
                        Logger::Log("updateO365Profile photo for [{$_COMPANY->id()}|{$this->id}]. Error={$errors['error']['code']}, Description={$errors['error']['message']}", Logger::SEVERITY['WARNING_ERROR']);
                    }
                }
            }
            curl_close($ch);
        }
        unlink($file);

        return $retVal;
    }

    public static function syncUsers365($token, $o365_customization, $companyid, bool $deleteUsers, int $syncDays, int $batch_offset = 0)
    {
        global $_COMPANY;
        /* @var Company $_COMPANY */
        if (!isset($_COMPANY)) {
            $_COMPANY = Company::GetCompany($companyid);
        }

        $batch = 10;
        $batch_start = $batch_offset * $batch;
        $sync_users = array();
        $bad_userids = array();
        // Conitnue the do loop as long as we are finding new users to sync.
        do {
            $number_of_users_tried = 0;
            $syncDays = ($syncDays > 0) ? $syncDays : 7;
            // We will sync users who are not marked for deletion. Note: in case of HRIS file sync we even sync users
            // who are marked for deletion because HRIS is authoritative source but in case of O365 sync we not doing
            // as the user may have been marked for deletion by HRIS file sync.
            // Use DB RW instance to avoid a race condition where a user may have been updated just before this statement.
            $sync_users = self::DBGet("SELECT * FROM users WHERE companyid='{$companyid}' AND isactive < 100 AND (validatedon < now() - interval {$syncDays} day) LIMIT {$batch_start},{$batch}");
            foreach ($sync_users as $syncUser) {

                // Check if the user was already tried and it was deemed to be a bad userid. If so continue.
                // This helps break the infinite loop where bad userids are tried forever until timeout.
                if (in_array($syncUser['userid'], $bad_userids))
                    continue;

                $s = new User($syncUser['userid'], $companyid, $syncUser);
                $synced = $s->updateO365Profile($token, $o365_customization);

                if ($synced === 1) {
                    Logger::Log("UserSync: Updating Validation Time [{$_COMPANY->id()}|{$s->id()}]", Logger::SEVERITY['INFO']);
                } elseif ($synced === -1) {
                    $s->purge();
                    $bad_userids[] = $syncUser['userid']; // Do not process the user again.
                } elseif ($synced === -2) {
                    break; // -2 is for expired token. We need to exit now.
                } else {
                    $bad_userids[] = $syncUser['userid']; // Do not process the user again.
                }
                $number_of_users_tried++;

                if ($number_of_users_tried%25 == 0) { // For every 25 records print error log to show work is in progress
                    Logger::Log("UserSync: Processing record number {$number_of_users_tried}", Logger::SEVERITY['INFO']);
                }
            }
        } while (count($sync_users) && $number_of_users_tried);

        if ($deleteUsers)
            self::DeleteUsersMarkedForDeletion();

        return null;
    }

    /**
     * This method updates users session to mark the group's chapters he joined or left to show the surveys later on
     * This method is works only if custom['affinity']['surveys']['enabled'] is true
     * @param int $surveytype Valid values are Survey2::SURVEY_TYPE['GROUP_MEMBER'] | Survey2::SURVEY_TYPE['ZONE_MEMBER']
     * @param int $surveysubtype Valid values are Survey2::SURVEY_TRIGGER['ON_JOIN']|Survey2::SURVEY_TRIGGER['ON_LEAVE']|Survey2::SURVEY_TRIGGER['ON_LOGIN']
     * @param int $groupid
     * @param int $chapterid
     * @param int $channelid ,
     * @return int - returns number of elements in survey array
     */
    public function pushSurveyIntoSession(int $surveytype, int $surveysubtype, int $groupid, int $chapterid, int $channelid): int
    {
        global $_COMPANY;
        $retVal = 0;

        $commonSession = $this->getGlobalSession();

        if ($_COMPANY->getAppCustomization()['surveys']['enabled']) {

            $type = $surveytype == 2 ? 3 : 2;
            if (isset($commonSession['survey'])) {
                foreach ($commonSession['survey'] as $key => $value) {
                    if (
                        $value['surveytype'] == $surveytype
                        &&
                        ( // Check if survey subtypes are JOIN or LEAVE type and they are opposites of value being set
                            $value['surveysubtype'] != $surveysubtype
                            &&
                            ($value['surveysubtype'] == Survey2::SURVEY_TRIGGER['ON_JOIN'] || $value['surveysubtype'] == Survey2::SURVEY_TRIGGER['ON_LEAVE'])
                            &&
                            ($surveytype == Survey2::SURVEY_TYPE['GROUP_MEMBER'] || $surveytype == Survey2::SURVEY_TYPE['ZONE_MEMBER'])
                        )
                        &&
                        $value['groupid'] == $groupid
                        &&
                        $value['chapterid'] == $chapterid
                        &&
                        $value['channelid'] == $channelid
                    ) {
                        unset($commonSession['survey'][$key]);
                        $commonSession['survey'] = array_values(array_unique($commonSession['survey'],SORT_REGULAR));
                        $this->setGlobalSession($commonSession);
                        return count($commonSession['survey']);
                    }
                }
            }
            // Survey Session
            $newEntry = array();
            $newEntry['surveytype'] = $surveytype;
            $newEntry['surveysubtype'] = $surveysubtype;
            $newEntry['groupid'] = $groupid;
            $newEntry['chapterid'] = $chapterid;
            $newEntry['channelid'] = $channelid;
            $commonSession['survey'][] = $newEntry;
            $commonSession['survey'] = array_unique($commonSession['survey'],SORT_REGULAR);
            $retVal = (count($commonSession['survey']));
        } else {
            unset ($commonSession['survey']);
        }

        $this->setGlobalSession($commonSession);
        return $retVal;
    }


    /**
     * This method pops the survey set from $commonSession['survey']
     * @return array with attributes like 'groupid', 'chapterid', 'surveytype', surveysubtype or NULL
     */
    public function popSurveyFromSession()
    {
        $commonSession = $this->getGlobalSession();
        if (!isset($commonSession['survey'])) {
            return null;
        } elseif (empty($commonSession['survey'])) {
            unset ($commonSession['survey']);
            $this->setGlobalSession($commonSession);
            return null;
        } else {
            $survey = array_pop($commonSession['survey']);
            if (empty($commonSession['survey'])) {
                unset ($commonSession['survey']);
            }
            $this->setGlobalSession($commonSession);
            return $survey;
        }
    }

    /**
     * Generates a password reset code and sends to users email.
     * @return bool
     */
    public function generateAndSendPasswordResetCode (): bool {
        global $_COMPANY; /* @var Company $_COMPANY */
        $request_code = base64_encode(md5(time().rand().microtime()));

        self::DeletePasswordResetCode($this->val('email'));
        self::DBInsertPS("INSERT INTO users_password_reset(request_id, email, expireson) VALUES (?,?,now() + interval 1 hour)", "ss", $request_code, $this->val('email'));

        $url = "https://{$_SERVER['HTTP_HOST']}/1/user/reset.php?code={$request_code}";
        $subject = "Password Reset";

        $message = <<< EOMEOM
        <p>Dear {$this->getFullName()},</p><br/>
        <p>We recently received your request to reset the password for your Teleskope account for {$_COMPANY->val('companyname')}. Here is a secure link to reset your password:</p>
        <p><a href="{$url}">Password Reset Link</a></p>
        <p>Please click the link, enter your new password and then click 'Submit' to update it. This link will expire after 15 minutes.</p>
        <p>Thanks again and we look forward to having you back on.</p>
        <br/>
        <p>Thanks!</p>
EOMEOM;
        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $emesg	= str_replace('#messagehere#',$message,$template);
        return $_COMPANY->emailSend2('', $this->val('email'), $subject, $emesg, 'TELESKOPE','');
    }

    /**
     * Fetches the email matching the requestid. The function validates if the request has not expired, if it has then the request will be removed and empty string is returned
     * @param string $requestid
     * @return string email for the corresponding request, if the request was found and it had not expired, '' otherwise
     */
    public static function GetEmailByPasswordResetCode (string $requestid):string {
        $email = '';
        $row = self::DBGetPS("SELECT email, IF(now()>expireson,1,0) AS expired FROM users_password_reset WHERE request_id=?","s",$requestid);
        if (count($row)) {
            if ($row[0]['expired']) {
                self::DeletePasswordResetCode($row[0]['email']);
            } else {
                $email = $row[0]['email'];
            }
        }
        return $email;
    }

    /**
     * Deletes the password reset request, call this function after user logs in or the user has reset the password.
     * @param string $email
     * @return int
     */
    public static function DeletePasswordResetCode (string $email) {
        return self::DBUpdatePS("DELETE FROM users_password_reset WHERE email=?","s",$email);
    }

    /**
     * @param string $email
     * @return int|string
     */
    public static function CreateOrUpdateFailedLoginAttempts (string $email) {
        return self::DBInsertPS("INSERT INTO users_failed_login (email, attempts, createdon, modifiedon) VALUES (?,1,now(),now()) ON DUPLICATE KEY UPDATE attempts=attempts+1,modifiedon=now()", "s", $email);
    }

    /**
     * Returns the number of consecutuve failed login attempts recorded for a given email.
     * @param string $email
     * @return int
     */
    public static function GetFailedLoginAttempts (string $email) : int {
        $attempts = 0;
        $row = self::DBGetPS("SELECT attempts FROM users_failed_login WHERE email=?","s",$email);
        if (count($row)) {
            $attempts = (int)$row[0]['attempts'];
        }
        return $attempts;
    }

    /**
     * Deletes the failed login attempt for a given email. This function is used for email/password based login in conjuction with other functions
     * @param string $email
     * @return int
     */
    public static function DeleteFailedLoginAttempt (string $email) {
        return self::DBUpdatePS("DELETE FROM users_failed_login WHERE email=?","s",$email);
    }

    /**
     * @param string $password, password must be set, if empty then 0 is returned and password is not updated
     * @return int
     */
    public function updatePassword(string $password) {
        if (empty($password)) {
            return 0;
        }
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        return self::DBMutatePS("UPDATE users SET password = ? WHERE userid = ? ", "si", $passwordHash,$this->id);
    }

    public function validateConfirmationCode (string $confirmationcode) {
        global $_COMPANY;
        $retVal = self::DBMutatePS("UPDATE users SET confirmationcode='',verificationstatus='1', validatedon=now(), modified=now() WHERE userid=? AND confirmationcode=?", "is", $this->id, $confirmationcode);
        User::GetUser($this->id, true); // Refresh cache after loading from master DB
        return $retVal;
    }

    /**
     * Generates and emails confirmation code
     * @return bool, true on success
     */
    public function generateAndSendConfirmationCode() {
        global $_COMPANY; /* @var Company $_COMPANY */
        $confirmationcode = rand (100999,999999); //six digit random number
        self::DBMutatePS("UPDATE users SET confirmationcode=?,verificationstatus='2' WHERE userid=?", "si", $confirmationcode,$this->id);
        User::GetUser($this->id, true); // Refresh cache after loading from master DB

        $subject	= "Teleskope account confirmation code";
        $msg  ="";
        //$msg .= "<div style='width:400px;background:#fff;color:#000;font-family:Source Sans Pro,sans-serif;padding:10px;' >";
        $msg .= "<p>Dear {$this->getFullName()},</p><br/>" ;
        $msg .= "<p>Thank you for signing up for Teleskope account for {$_COMPANY->val('companyname')}.</p><br/>" ;
        $msg .= "<p>To complete your registration, please enter the confirmation code below into confirmation input box on the webpage: </p><br/>" ;
        $msg .= "<p><code style='font-weight: 900; font-size: 18px;'>{$confirmationcode}</code></p><br/>";
        $msg .= "<p>We're excited to have you join us.</p><br/>" ;
        $msg .= "<p>Thanks again!</p><br/>";
        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $emesg	= str_replace('#messagehere#',$msg,$template);
        return $_COMPANY->emailSend2('', $this->val('email'), $subject, $emesg, 'TELESKOPE','');

    }

    public function generateAndSendAuthenticationOTPCode(string $application_name, string $from_email_label) : string
    {
        global $_COMPANY, $_ZONE; /* @var Company $_COMPANY */
        $confirmationcode = rand (100000,999999); //eight digit random number

        if (empty($application_name)) {
            $application_name = $_COMPANY->val('companyname') . ' ' . Company::APP_LABEL[$_ZONE->val('app_type')];
        }

        $subject	= "Your One-Time Password (OTP) for {$application_name}";
        $msg  ="";
        //$msg .= "<div style='width:400px;background:#fff;color:#000;font-family:Source Sans Pro,sans-serif;padding:10px;' >";
        $msg .= "<p>Dear {$this->getFullName()},</p><br/>" ;
        $msg .= "<p>You have requested a one-time password (OTP) for your {$application_name} account.</p><br/>" ;
        $msg .= "<p>Your OTP is <code style='font-weight: 900; font-size: 20px;background-color:#fafac8;'>$confirmationcode</code></p><br/>" ;
        $msg .= "<p>Please use this OTP to complete your log in. Your OTP is valid for 5 minutes.</p><br/>" ;
        $msg .= "<p>Important: Do not share this OTP with anyone. This OTP is for one-time use only. If you did not request this OTP please ignore this email.</p><br/>" ;
        $msg .= "<p>Thank you,</p><br/>";
        $msg .= "<p>{$application_name} Team</p><br/>";
        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $emesg	= str_replace('#messagehere#',$msg,$template);

        if ($_COMPANY->isValidAndRoutableEmail($this->val('email'))) {
            $_COMPANY->emailSend2($from_email_label, $this->val('email'), $subject, $emesg, 'TELESKOPE','');
        } else {
            $_COMPANY->emailSendExternal($from_email_label, $this->val('external_email'), $subject, $emesg, 'TELESKOPE','');
        }
        return $confirmationcode;
    }

    /**
     * This method updates users isactive status to 2, which denotes email verification required whenever
     * email has boundced. Only active users (i.e with isactive=1 are updated) to protect deleted users (100,101)
     * from becoming active again accidently.
     * @param string $bouncedEmail target email address
     * @return int returns 0 if no match was found and 1 if row was updated.
     */
    public static function HandleBouncedEmail (string $bouncedEmail):int {
        global $_COMPANY;
        $r1 = self::DBGetPS('SELECT userid,isactive FROM users WHERE email = ? AND isactive=1', "s", $bouncedEmail);
        if (!empty($r1) && !empty($r1[0]['userid'])) {
            $target_userid = $r1[0]['userid'];
            $retVal = self::DBMutate("UPDATE users SET isactive = 2 WHERE userid = {$target_userid}");
            User::GetUser($target_userid, true); // Refresh cache after loading from master DB

            self::LogObjectLifecycleAudit('state_change', 'user', $r1[0]['userid'], 0, [
                'previous_state' => $r1[0]['isactive'],
                'new_state' => 2,
                'oper_details' => 'Mark user for verification',
            ]);

            return $retVal;
        }
        return 1;
    }

    /**
     * This method updates auto assigned chapters.
     * Call this method after user updates their office location or the office location gets updated as part of the profile update.
     * @param int $newofficelocation
     */
    public function updateAutoAssignedChapters(int $newofficelocation)
    {
        global $_COMPANY;

        // Get all my group members for groups that have auto-assign settings on .... including inactive groups
        $myGroupMemberships = self::DBGet("SELECT memberid,groupid,chapterid FROM groupmembers LEFT JOIN `groups` USING (groupid) WHERE groupmembers.userid={$this->id} AND `groups`.chapter_assign_type='auto'");

        foreach ($myGroupMemberships as $groupMembership) {
            $group = Group::GetGroup($groupMembership['groupid']);

            $newChapteridList = $group->getChaptersMatchingBranchIds($newofficelocation,true);
            $newChapterIds = implode(',',array_column($newChapteridList,'chapterid'));

            // Note: for auto assign chapter groups, a user can have only one chapter... that is why we do not need to parse the existingchapters
            if ($groupMembership['chapterid'] != $newChapterIds) {
                // Update the chapter if the old one was different
                self::DBMutate("UPDATE groupmembers SET chapterid='{$newChapterIds}' WHERE memberid={$groupMembership['memberid']} AND userid={$this->id}");

                // Create group user logs for removed chapters
                $removed = array_diff(explode(',',$groupMembership['chapterid'] ),explode(',',$newChapterIds));
                if (!empty($removed)){
                    foreach($removed as $chid){
                        if ($chid){
                            // Create Group user log
                            GroupUserLogs::CreateGroupUserLog($groupMembership['groupid'], $this->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['GROUP_MAINTENANCE']);
                        }
                    }
                }
            }
        }
    }

    /**
     * This method finds users configured zone for a given app. This function just return the first matching configured
     * domain for the provide app_type
     * @param string $app_type Application (officeraven or affinities) for which you want the default domain.
     * @return int zoneid or 0 if no match is found
     */
    public function getMyConfiguredZone(string $app_type):int {
        global $_COMPANY; /* @var Company $_COMPANY */
        $myzones = explode (',',$this->val('zoneids'));
        foreach ($myzones as $myzone) {
            $myzone = (int)$myzone;
            $z = $_COMPANY->getZone($myzone);
            if ($z && $z->val('app_type') === $app_type) {
                return $myzone; // Match for app_type found
            }
        }
        return 0;
    }

    public function updateTimezone(string $timezone): int {
        global $_COMPANY;
        $retVal = self::DBUpdatePS("UPDATE `users` SET `timezone`=? WHERE `userid`=?",'si',$timezone,$this->id);
        if ($retVal)
            $this->fields['timezone'] = $timezone;

        $this->clearSessionCache();
        return $retVal;
    }

    public function recordUsage (int $zoneid, string $usageif) {
        global $_COMPANY;
        self::DBMutate("INSERT INTO `appusage`(`userid`, `companyid`,`zoneid`,`usageif`) VALUES ({$this->id},{$_COMPANY->id()},{$zoneid},'{$usageif}')");
    }

    public function isUserInboxEnabled()
    {
        global $_COMPANY;
        return $_COMPANY->getAppCustomization()['user_inbox']['enabled'] &&
            !$_COMPANY->isValidAndRoutableEmail($this->val('email')) &&
            $this->val('inbox_enabled');
    }

    /**
     * @param bool $status set true to enable
     * @return void
     */
    public function enableDisableInbox (bool $status)
    {
        global $_COMPANY;
        $inbox_enabled = $status ? 1 : 0;

        if (
            !$_COMPANY->isValidAndRoutableEmail($this->val('email')) &&
            $this->val('inbox_enabled') != $inbox_enabled
        ) {
            self::DBMutate("UPDATE users SET inbox_enabled={$inbox_enabled} WHERE companyid={$_COMPANY->id()} AND userid={$this->id()}");
        }
    }

    public function updateExternalEmailAddress(?string $external_email_address)
    {
        global $_COMPANY;
        $retVal = 0;

        if ($external_email_address) {
            $external_email_address = trim($external_email_address);
        }

        if ($external_email_address && $_COMPANY->isValidEmail($external_email_address)) {
            // Cannot assign a valid company email as external email
            return 0;
        }

        if ($_COMPANY->isValidAndRoutableEmail($this->val('email'))) {
            // Users with company routable email address should have external_email as null
            $external_email_address = null; // reset it.
        }

        if ($this->val('external_email') != $external_email_address) {
            if ($external_email_address) {
                $retVal = self::DBMutatePS("UPDATE users SET external_email=? WHERE companyid=? AND userid=?", 'xii', $external_email_address, $_COMPANY->id(), $this->id());
            } else {
                $retVal =self::DBMutate("UPDATE users SET external_email=NULL WHERE companyid={$_COMPANY->id()} AND userid={$this->id()}");
            }

            $this->clearSessionCache();

            self::LogObjectLifecycleAudit('update', 'user', $this->id(), 0, [
                'ids' => $this->getEncryptedIdentityForLogging(),
                'oper_details' => 'updated external email',
            ]);
        }else{
            // same address, hence no change in DB
            return $retVal = 1; 
        }

        return $retVal;
    }

    public function getLastXLogins (int $x=5) {
        global $_COMPANY;
        $x = ($x > 20) ? 20 : $x;
        return self::DBGet("SELECT * FROM `appusage` WHERE companyid={$_COMPANY->id()} AND userid={$this->id} ORDER BY usagetime DESC LIMIT $x");
    }

    /**
     * @param int $zoneid
     * @param bool $send_zone_emails
     * @return int
     */
    public function addUserZone(int $zoneid, bool $add_as_home_zone, bool $send_zone_emails){
        global $_COMPANY;
        $retVal = 0;

        $user_zones = array_filter(array_map('intval',explode(',',$this->val('zoneids') ?: '')));

        if (in_array($zoneid, $user_zones)) {
            $send_zone_emails = false; // User is already a member of the zone, so override any zone email to not be sent
        }

        // If $add_as_home_zone then add the new zone to the front of the array, else to the end
        if ($add_as_home_zone) {
            array_unshift($user_zones, $zoneid);
        } else {
            $user_zones[] = $zoneid;
        }
        $user_zones = array_unique($user_zones);

        $zoneids = implode(',',$user_zones);

        if ($this->val('zoneids') != $zoneids) { // Update zones only if they have changed.
            // Before updating validate the zone
            $zone = $_COMPANY->getZone($zoneid);
            if (!$zone) {
                // Security Violation
                Logger::Log("Fatal Error: Tried to set non-existing zone = {$zoneid} for user {$this->id()}.");
                return 0;
            }

            if ($retVal = self::DBUpdate("UPDATE `users` SET `zoneids`='{$zoneids}' WHERE `userid`={$this->id}")) {
                $this->fields['zoneids'] = $zoneids;
                User::GetUser($this->id, true); // Reload user to refresh caches.
                if ($send_zone_emails) {
                    $job = new ZoneMemberJob($zoneid);
                    $job->saveAsJoinType($this->id);
                }
            }
        }

        return $retVal;
    }

    /**
     * @param int $zoneid
     * @param bool $send_zone_emails
     * @return int
     */
    public function removeUserZone(int $zoneid, bool $send_zone_emails){
        global $_COMPANY;
        $retVal = 0;

        $user_zones = explode(',',$this->val('zoneids'));

        if (($key = array_search($zoneid, $user_zones)) !== false) {

            unset($user_zones[$key]);

            $zoneids = implode(',',array_unique($user_zones));

            if ($retVal = self::DBUpdate("UPDATE `users` SET `zoneids`='{$zoneids}' WHERE `userid`={$this->id}")) {
                $this->fields['zoneids'] = $zoneids;
                User::GetUser($this->id, true); // Refresh cache after loading from master DB
                if ($send_zone_emails) {
                    $job = new ZoneMemberJob($zoneid);
                    $job->saveAsLeaveType($this->id);
                }
            }
        }
        return $retVal;
    }

    /**
     * Redirecto to getMyConfiguredZone
     */
    public function getHomeZone (string $app_type):int {
        return $this->getMyConfiguredZone($app_type);
    }

    /**
     * this method tells if the user is in home zone of the application
     * The session variable app_type is used for application context
     * @return bool
     */
    public function isInHomeZone ():bool {
        global $_COMPANY, $_ZONE;
        return ($_ZONE->id() == $this->getMyConfiguredZone($_SESSION['app_type']));
    }

    private function getEncryptedIdentityForLogging () {
        global $_COMPANY;
        $ids = [
            'userid' => $this->id,
            'email' => $this->val('email'),
            'externalid' => $this->getExternalId(),
            'externalusername' => $this->val('externalusername'),
            'aad_oid' => $this->val('aad_oid'),
            'external_email' => $this->val('external_email')
        ];
        return self::EncryptProfile($ids);
    }

    /**
     * Returns a list of users in the Company by matching the keywork with firstname, lastname or email.
     * Upto two words can be provided with a space between them in which case firstname lastname match will be done
     * @param string $searchKeyWord it should be a minimum of 3 characters
     * @param int $limit
     * @return array
     */
    public static function SearchUsersByKeyword(string $searchKeyWord, string $searchScopeCondition='', string $excludeCondition='',int $limit=20){
        global $_COMPANY;
        global $_ZONE;
        $rows = array();
        $searchKeyWord = trim ($searchKeyWord);
        $searchScopeCondition = !empty($searchScopeCondition) ? " AND ({$searchScopeCondition}) " : '';
        $excludeConditionFilter = !empty($excludeCondition) ? " AND ({$excludeCondition}) " : '';

        if (strlen($searchKeyWord) > 2){
            $keyword_list = explode(' ',$searchKeyWord);

            if(count($keyword_list) == 2){
                $like_keyword1 = $keyword_list[0]. '%'; //First keyword is full match
                $like_keyword2 = '%' . $keyword_list[1] . '%';
                // On 12/17/2021 removed the FIND_IN_SET(zoneid,zoneids) filter to allow entire user db to be searched.
                $rows = self::DBGetPS("SELECT `userid`, `firstname`, `lastname`, `email`, `accounttype`, IF (jobtitle='','Job title unavailable',jobtitle) as jobtitle FROM `users` WHERE `companyid`=? AND (`isactive`='1' AND `verificationstatus`='1' AND ((firstname LIKE ? OR lastname LIKE ?) AND (firstname LIKE ? OR lastname LIKE ?))) {$searchScopeCondition} {$excludeConditionFilter} LIMIT ?", 'issssi', $_COMPANY->id(),  $like_keyword1, $like_keyword1, $like_keyword2, $like_keyword2, $limit);
            } else if (count($keyword_list) > 0) { // For all other cases
                $like_keyword = $keyword_list[0] . '%';
                $rows = self::DBGetPS("SELECT `userid`, `firstname`, `lastname`, `email`, `accounttype`, IF (jobtitle='','Job title unavailable',jobtitle) as jobtitle FROM `users` WHERE `companyid`=? AND (`isactive`='1' AND `verificationstatus`='1' AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ?)) {$searchScopeCondition} {$excludeConditionFilter} LIMIT ?", 'isssi', $_COMPANY->id(), $like_keyword, $like_keyword, $like_keyword, $limit);
            }

            usort($rows, function($a, $b) {
                return $a['firstname'] <=> $b['firstname'];
            });
        }

        return $rows;
    }

    /**
     * @param $attributeName - subject attribute name
     * @param $externalAttributes - an array of external attributes. Attribute value may be an array
     * @param $customizations - customizations for attribute, if cutomization is not set then '' is returned
     * @param bool $returnNullIfMissing - returns null if attribute name is missing in customizations; default is false
     * @return mixed|string|string[]|null
     */
    public static function XtractAndXformValue (string $attributeName, array $externalAttributes, array $customizations, bool $returnNullIfMissing = false): mixed
    {
        $attributeValue = '';

        if (!isset($customizations[$attributeName])) {
            return $returnNullIfMissing ? null : '';
        }

        if (isset($customizations[$attributeName]['constant'])) { // Constant value is provided, use it instead of computing value
            return $customizations[$attributeName]['constant'];
        }

        $externalAttributeName = $customizations[$attributeName]['ename'];

        if (isset($externalAttributes[$externalAttributeName])) {
            if (is_array($externalAttributes[$externalAttributeName])) {
                if (!empty($customizations[$attributeName]['array_to_csv'])) {
                    $attributeValue = implode (',', $externalAttributes[$externalAttributeName]);
                } else {
                    $attributeValue = $externalAttributes[$externalAttributeName][0];
                }
            } else {
                $attributeValue = $externalAttributes[$externalAttributeName];
            }
        }

        if (empty($attributeValue) && !empty($customizations[$attributeName]['or_ename'])) {
            // Try with a second value, e.g. preferred_firstname can be ename and legal_firstname can be or_ename
            $externalAttributeName2 = $customizations[$attributeName]['or_ename'];
            $attributeValue =
                isset($externalAttributes[$externalAttributeName2])
                    ? (is_array($externalAttributes[$externalAttributeName2]) ? $externalAttributes[$externalAttributeName2][0] : $externalAttributes[$externalAttributeName2])
                    : '';
        }

        if(!empty($customizations[$attributeName]['pattern']) && isset($customizations[$attributeName]['replace'])){
            $attributeValue = preg_replace($customizations[$attributeName]['pattern'],$customizations[$attributeName]['replace'], $attributeValue);
        }

        if (!empty($customizations[$attributeName]['transform'])) {
            $attributeValue = self::ApplyTransformations($attributeValue, $customizations[$attributeName]['transform']);
        }

        return $attributeValue;
    }


    /**
     * Update App Session
     */

    public function updateAppSession(int $devicetype,string $devicetoken){
        global $_COMPANY, $_ZONE;
        self::DBMutatePS("DELETE FROM users_api_session WHERE userid=? AND companyid=? AND zoneid=? AND devicetoken=?",'iiis',$this->id(),$_COMPANY->id(),$_ZONE->id(),$devicetoken);
        return self::DBInsertPS("INSERT INTO users_api_session (userid, `companyid`, `zoneid`, devicetype, devicetoken, modified) VALUES (?,?,?,?,?,NOW())",'iiiis',$this->id(),$_COMPANY->id(),$_ZONE->id(),$devicetype,$devicetoken);
    }

    /**]
     * App User Logout
     */

    public function appLogout(int $sessionid){
        return self::DBMutate("DELETE FROM users_api_session WHERE api_session_id='{$sessionid}' AND userid='{$this->id}'");
    }

    public function updateNotificationStatus(int $status){
        global $_COMPANY, $_ZONE;
        $retVal = self::DBUpdatePS("UPDATE `users` SET `notification`=?,`modified`=NOW() WHERE  userid=? AND companyid=?","iii",$status,$this->id,$_COMPANY->id());
        User::GetUser($this->id, true); // Refresh cache after loading from master DB
        return $retVal;
    }

    /**
     * Update profile picture
     */
    public function updateProfilePicture(string $picture){
        global $_COMPANY, $_ZONE;
        $retVal = self::DBUpdatePS("UPDATE `users` SET `picture`=?,`modified`=NOW() WHERE  userid=? AND companyid=?","sii",$picture,$this->id,$_COMPANY->id());
        User::GetUser($this->id, true); // Refresh cache after loading from master DB
        return $retVal;
    }

    /**
     * Returns a list of reviewers in groups, chapter, channel scope. Note there can be multiple of each.
     * @param string $groupids_csv
     * @param string $chapterids_csv
     * @param string $channelids_csv
     * @return array List of reviewes with the following fields set - userid, email, firstname, lastname
     */
    public static function GetReviewersByScope (string $groupids_csv, string $chapterids_csv, string $channelids_csv):array
    {
        global $_COMPANY, $_ZONE;
        $groupleads = array();
        $chapterleads = array();
        $channelleads = array();

        $groupids_csv = implode(',',array_map('intval', explode(',', $groupids_csv)));
        if (!empty($groupids_csv)) {
            $groupleads = self::DBROGet("SELECT users.`userid`, `email`, `firstname`, `lastname`, gt.`type` as role_name FROM `users` JOIN groupleads g ON users.`userid` = g.`userid` JOIN grouplead_type gt ON g.`grouplead_typeid` = gt.`typeid` WHERE users.`companyid` = {$_COMPANY->id()} AND (g.`groupid` IN ({$groupids_csv}) AND (gt.sys_leadtype='2' OR gt.sys_leadtype='1')  AND (gt.`allow_publish_content` = 1 OR gt.`allow_create_content` = 1 OR gt.`allow_manage` = 1) )");
        }

        $chapterids_csv = implode(',',array_map('intval', explode(',', $chapterids_csv)));
        if (!empty($chapterids_csv)){
            // For newsletter chapterid is a list s
            $chapterleads = self::DBROGet("SELECT users.`userid`, `email`, `firstname`, `lastname`, gt.`type` as role_name FROM `users` JOIN chapterleads g ON users.`userid` = g.`userid` JOIN grouplead_type gt ON g.`grouplead_typeid` = gt.`typeid` WHERE users.`companyid` = {$_COMPANY->id()} AND (g.chapterid IN ({$chapterids_csv}) AND gt.sys_leadtype='4' AND ( gt.`allow_publish_content` = 1  OR gt.`allow_create_content` = 1 OR gt.`allow_manage` = 1 ) )");
        }

        $channelids_csv = implode(',',array_map('intval', explode(',', $channelids_csv)));
        if ($channelids_csv){
            $channelleads = self::DBROGet("SELECT users.`userid`, `email`, `firstname`, `lastname`, gt.`type` as role_name FROM `users` JOIN group_channel_leads g ON users.`userid` = g.`userid` JOIN grouplead_type gt ON g.`grouplead_typeid` = gt.`typeid` WHERE users.`companyid` = {$_COMPANY->id()} AND (g.channelid IN ({$channelids_csv}) AND gt.sys_leadtype='5' AND ( gt.`allow_publish_content` = 1  OR gt.`allow_create_content` = 1 OR gt.`allow_manage` = 1 ) )");
        }

        $company_admins = self::DBROGet(
            "SELECT DISTINCT `u`.`userid`, `u`.`email`, `u`.`firstname`, `u`.`lastname`, 'Admin' as role_name
                    FROM `company_admins` `ca`
                    JOIN `users` `u` USING (`companyid`,`userid`)  
                    WHERE `u`.`companyid` = {$_COMPANY->id()} 
                    AND `ca`.`zoneid` IN ({$_ZONE->id()},0)
                    AND `u`.`isactive`=1;"
        );

        $reviewers = array_merge($groupleads,$chapterleads,$channelleads,$company_admins);
        $sorted_reviewers = array();
        foreach ($reviewers as $reviewer) {
            $uid = $reviewer['userid'];
            if (empty($sorted_reviewers[$uid])) {
                // Add unique values to $sorted_reviewers
                $sorted_reviewers[$uid] = $reviewer;
            } else {
                // Merge role_names for non_unique user rows.
                $current_role_names = explode(' | ', $sorted_reviewers[$uid]['role_name']);
                if (!in_array($reviewer['role_name'], $current_role_names)) {
                    $current_role_names[] = $reviewer['role_name'];
                    $sorted_reviewers[$uid]['role_name'] = implode(' | ', $current_role_names);
                }
            }
        }

        //if (!empty($reviewers)){
        //    $reviewers = array_map("unserialize", array_unique(array_map("serialize", $reviewers)));
        //}
        return array_values($sorted_reviewers);
    }

    /**
     * Updates the externalId only if the following criteria is met: provided externailid is not empty,
     * currently externalid is not set
     * @param string|null $externalId (external representation)
     * @return bool true means update was done false means it was not done.
     */
    public function updateExternalId(?string $externalId): bool
    {
        global $_COMPANY;
        $retVal = 0;

        if ($externalId === null) {
            $retVal = self::DBMutate("UPDATE users SET externalid=null WHERE companyid={$_COMPANY->id()} AND userid={$this->id}");
        } elseif (!empty($externalId) && empty($this->getExternalId()) && $externalId != $this->getExternalId()) {
            $externalId .= ':' . $_COMPANY->id();
            $retVal = self::DBMutatePS("UPDATE users SET externalid=? WHERE companyid=? AND userid=?", 'sii', $externalId, $_COMPANY->id(), $this->id);
        }

        if ($retVal) {
            $this->fields['externalid'] = $externalId;
            User::GetUser($this->id, true); // Refresh cache after loading from master DB

            self::LogObjectLifecycleAudit('update', 'user', $this->id(), 0, [
                'ids' => $this->getEncryptedIdentityForLogging(),
                'oper_details' => 'updated externalid',
            ]);
        }

        return $retVal;
    }

    /**
     * Update the aad_oid to null,
     * @param string|null $aad_oid (external representation)
     * @return bool true means update was done false means it was not done.
    */
    public function resetAadOid(): bool
    {
        global $_COMPANY;

        $retVal = self::DBMutate("UPDATE users SET aad_oid=null WHERE companyid={$_COMPANY->id()} AND userid={$this->id}");

        if ($retVal) {
            $this->fields['aad_oid'] = null;
            User::GetUser($this->id, true); // Refresh cache after loading from master DB

            self::LogObjectLifecycleAudit('update', 'user', $this->id(), 0, [
                'ids' => $this->getEncryptedIdentityForLogging(),
                'oper_details' => 'reset aad_oid',
            ]);
        }

        return $retVal;
    }

    public function formatAmountForDisplay($amount, $k_sep=',', $decimalplaces = 2): string
    {
        return number_format($amount ?? 0.0, $decimalplaces, '.', $k_sep);
    }

    public function formatNumberForDisplay($val): string
    {
        return number_format($val ?? 0.0,0,'',','); // Todo in future get seperator from locale
    }

    public function updatePolicyAcceptedDate(){
        global $_COMPANY;
        return self::DBMutate("UPDATE users SET policy_accepted_on=NOW() WHERE userid={$this->id}");

    }


    /**
     * Return date in 'YYYY-mm-dd' format based on the current timezone
     * @return string
     */
    public function getLocalDateNow () {
        $tz = @$_SESSION['timezone'] ?: 'UTC';
        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone($tz));
        return $dt->format('Y-m-d');
    }

    /**
     * This method get the global session (shared between devices and web) from DB and returns as an array
     * @return array empty array if there is no session
     */
    private function getGlobalSession(): array
    {
        global $_COMPANY, $_ZONE;
        $row = self::DBGet("SELECT session_data,modifiedon FROM users_common_session WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND userid={$this->id()}");
        if (empty($row)) {
            return [];
        } else {
            return unserialize($row[0]['session_data']);
        }
    }

    /**
     * This method sets the value of session data into global session (shared between devices and web).
     * If session data is empty session record is deleted
     * @param array $session_data
     * @return int  1 on success, 0 on error
     */
    private function setGlobalSession(array $session_data=[]): int
    {
        global $_COMPANY, $_ZONE;
        if (empty($session_data)) { // If session data is empty delete the session
            return self::DBMutate("DELETE FROM users_common_session WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND userid={$this->id()}");
        } else { // otherwise update it
            $session_data_s = serialize($session_data);
            if (strlen($session_data_s) < 2048) { // Max value that can be stored is 2048
                $k = "companyid={$_COMPANY->id()},zoneid={$_ZONE->id()},userid={$this->id}";
                $v = "modifiedon=now(),session_data='" . serialize($session_data) . "'";
                return self::DBMutate("INSERT INTO users_common_session SET {$k},{$v} ON DUPLICATE KEY UPDATE {$v}");
            } else {
                return 0;
            }
        }
    }

    /**
     * Generates a random code and sets in session for one time consumption
     * @return string
     */
    public function generateAuditCode()
    {
        $_SESSION['audit_code'] = randomPasswordGenerator(16);
        return $_SESSION['audit_code'];
    }

    /**
     * Validates if the audit code matches the session value.
     * Also onces the comparison is done, the code is remove from the session.
     * @param string $audit_code
     * @return mixed
     */
    public function validateAuditCode(string $audit_code)
    {
        $session_audit_code = $_SESSION['audit_code'] ?? '';
        unset($_SESSION['audit_code']);
        return $session_audit_code === $audit_code;
    }

    public function isMasqdIn()
    {
        return $_SESSION['masq_access'] ?? false;
    }

    public function isDelegatedAccessUser(): bool
    {
        return $_SESSION['delegated_access'] ?? false;
    }

    /**
     * Creates a img tag for a given user.
     * @param string|null $firstname
     * @param string|null $lastname
     * @param string|null $picture
     * @param string $css_class
     * @param string $alt_tag
     * @param int|null $subject_userid
     * @param string|null $profile_detail_level This variable works only if $subject_userid is provided. Valid values are null, profile_basic, profile_full
     * @return string
     */
    public static function BuildProfilePictureImgTag(?string $firstname, ?string $lastname, ?string $picture, string $css_class, string $alt_tag="User Profile Picture", ?int $subject_userid = 0, ?string $profile_detail_level = null): string
    {
        global $_COMPANY;

        if (empty($picture)) {

            $firstname = $firstname ?? '';
            $lastname = $lastname ?? '';
            $initials = substr($firstname, 0, 1) . substr($lastname, 0, 1);
            $initials = preg_replace("/[^A-Za-z]/","",$initials);
            $initials = strtoupper($initials ?: ':)');
            $img_tag = "<img data-name=\"{$initials}\" alt=\"{$alt_tag}\" class=\"initial {$css_class}\"/>";
        } else {
            $img_tag = "<img src=\"{$picture}\" alt=\"{$alt_tag}\" class=\"{$css_class}\"/>";
        }

        if ($subject_userid && $profile_detail_level) { // If the userid is provided, then wrap the tag in span to make it a button.
            $encUid = $_COMPANY->encodeId($subject_userid);
            return "<span type='button' class='btn-link profile-pic-center' onclick=\"getProfileDetailedView(this,{'userid': '{$encUid}','profile_detail_level':'{$profile_detail_level}'})\">{$img_tag}</span>";
        } else {
            return $img_tag;
        }
    }

    public static function EncryptProfile(array $arr): string
    {
        if (!is_array($arr) || empty($arr)) {
            return "";
        }
        global $_COMPANY;
        $aes_key = Config::Get('USER_PROFILE_AES_PREFIX') . $_COMPANY->val('aes_suffix');
        return aes_encrypt(json_encode($arr, JSON_UNESCAPED_UNICODE), $aes_key, self::USER_PROFILE_SECRET_IV, false, false, true);
    }

    public static function DecryptProfile(?string $in): array
    {
        if (empty($in)) {
            return array();
        }
        global $_COMPANY;
        $aes_key = Config::Get('USER_PROFILE_AES_PREFIX') . $_COMPANY->val('aes_suffix');
        return json_decode(aes_encrypt($in, $aes_key, self::USER_PROFILE_SECRET_IV, true),true) ?? array();
    }

    /**
     * Returns a company specific hash for a given string
     * @param string|null $in
     * @return string
     */
    public static function GetUserHash(?string $in): string
    {
        global $_COMPANY;
        if ($in) {
            $val = strtolower($in) . $_COMPANY->val('aes_suffix');
            return trim(base64_url_encode(md5($val, true)),"-"); // Remove "-" as we will not convert it back.
        }
        return '';
    }

    public static function GetEmailsWithoutExternalIdsAsAMap()
    {
        global $_COMPANY;
        $vals = self::DBGet("SELECT email FROM users WHERE companyid={$_COMPANY->id} AND externalid IS NULL");
        $email_map = array();
        foreach ($vals as $val) {
            if (!empty($val['email'])) {
                $email_map[strtolower($val['email'])] = 1;
            }
        }
        return $email_map;
    }

    public static function RepairEmailExternalId (string $email, string $externalid)
    {
        global $_COMPANY;
        $userByExternalId = self::GetUserByExternalId($externalid, true);
        $userByEmail = self::GetUserByEmail($email, true);
        $eid = $externalid . ':' . $_COMPANY->id;
        $retVal = 0;

        if (!$userByEmail) {
            // User by email was not found.... there is nothing we can do at this point, so lets exit.
            return 0;
        }

        if (!empty($userByEmail->getExternalId())) {
            // User by email already has an externalid ... so we cannot update it and we cannot migrate it so lets exit.
            Logger::Log("User::RepairEmailExternalId, Fatal Error conflicting records: user identified with email={$email} already has externalid {$userByEmail->getExternalId()} and HRIS requested externalid to by changed to {$externalid}");
            return 0;
        }

        if ($userByExternalId == null) {
            // The external id is not associated with any other user so lets associate it with user identified by email
            $retVal = self::DBMutatePS("UPDATE users SET externalid=?,modified=now() WHERE companyid=? AND userid=?", 'sii', $eid, $_COMPANY->id, $userByEmail->id());
        } elseif (empty($userByEmail->val('externalid'))) {
            // User with given externalid already exists but emails are different, lets fix the email address.
            // Lets first free up email and aad_oid to assign them to the second record.
            self::DBMutate("UPDATE users SET email=concat('--',email),aad_oid=null,externalusername=null,isactive=100,modified=now() WHERE companyid={$_COMPANY->id()} AND userid={$userByEmail->id()}");
            // Not lets assign aad_oid, if we need to
            if (!empty($userByEmail->val('aad_oid')) && empty($userByExternalId->val('aad_oid'))) {
                self::DBMutatePS("UPDATE users SET aad_oid=? WHERE companyid=? AND userid=?", 'sii', $userByEmail->val('aad_oid'), $_COMPANY->id(), $userByExternalId->id());
                Logger::Log("User::RepairEmailExternalId, migrating aad_oid ({$userByEmail->val('aad_oid')}) from user identified with email={$email} to user identified by externalid={$externalid}", Logger::SEVERITY['INFO']);
            }
            // Next assign the email to user identified by $userByExternalId
            $retVal = self::DBMutatePS("UPDATE users SET email=?,modified=now() WHERE companyid=? AND userid=?", 'xii', $email, $_COMPANY->id(), $userByExternalId->id());
            Logger::Log("User::RepairEmailExternalId, migrating email from user identified with email={$email} to user identified by externalid={$externalid}", Logger::SEVERITY['INFO']);

            // migrate the groupleads records from $userByEmail to $userByExternalId only if $userByExternalId records are missing
            $glCount = self::DBGet("SELECT count(1) as cc FROM groupleads WHERE userid={$userByExternalId->id()}")[0]['cc'];
            if (0 == $glCount) {
                self::DBMutate("UPDATE groupleads SET userid={$userByExternalId->id()} WHERE userid={$userByEmail->id()}");
                Logger::Log("User::RepairEmailExternalId, migrating groupleads from user identified with email={$email} to user identified by externalid={$externalid}", Logger::SEVERITY['INFO']);
            }
            // migrate the chapterleads records from $userByEmail to $userByExternalId only if $userByExternalId records are missing
            $chpCount = self::DBGet("SELECT count(1) as cc FROM chapterleads WHERE userid={$userByExternalId->id()}")[0]['cc'];
            if (0 == $chpCount) {
                self::DBMutate("UPDATE chapterleads SET userid={$userByExternalId->id()} WHERE userid={$userByEmail->id()}");
                Logger::Log("User::RepairEmailExternalId, migrating chapterleads from user identified with email={$email} to user identified by externalid={$externalid}", Logger::SEVERITY['INFO']);
            }
            // migrate the group_channel_leads records from $userByEmail to $userByExternalId only if $userByExternalId records are missing
            $chnCount = self::DBGet("SELECT count(1) as cc FROM group_channel_leads WHERE userid={$userByExternalId->id()}")[0]['cc'];
            if (0 == $chnCount) {
                self::DBMutate("UPDATE group_channel_leads SET userid={$userByExternalId->id()} WHERE userid={$userByEmail->id()}");
                Logger::Log("User::RepairEmailExternalId, migrating group_channel_leads from user identified with email={$email} to user identified by externalid={$externalid}", Logger::SEVERITY['INFO']);
            }
            // migrate the groupmembers records from $userByEmail to $userByExternalId only if $userByExternalId records are missing
            $memCount = self::DBGet("SELECT count(1) as cc FROM groupmembers WHERE userid={$userByExternalId->id()}")[0]['cc'];
            if (0 == $memCount) {
                self::DBMutate("UPDATE groupmembers SET userid={$userByExternalId->id()} WHERE userid={$userByEmail->id()}");
                Logger::Log("User::RepairEmailExternalId, migrating groupmembers from user identified with email={$email} to user identified by externalid={$externalid}", Logger::SEVERITY['INFO']);
            }
            // migrate member_join_requests
            $memJoinCount = self::DBGet("SELECT count(1) as cc FROM member_join_requests WHERE userid={$userByExternalId->id()}")[0]['cc'];
            if (0 == $memJoinCount) {
                self::DBMutate("UPDATE member_join_requests SET userid={$userByExternalId->id()} WHERE userid={$userByEmail->id()}");
                Logger::Log("User::RepairEmailExternalId, migrating member_join_requests from user identified with email={$email} to user identified by externalid={$externalid}", Logger::SEVERITY['INFO']);
            }
            // migrate team_members
            $teamMemCount = self::DBGet("SELECT count(1) as cc FROM team_members WHERE userid={$userByExternalId->id()}")[0]['cc'];
            if (0 == $teamMemCount) {
                self::DBMutate("UPDATE team_members SET userid={$userByExternalId->id()} WHERE userid={$userByEmail->id()}");
                Logger::Log("User::RepairEmailExternalId, migrating team_members from user identified with email={$email} to user identified by externalid={$externalid}", Logger::SEVERITY['INFO']);
            }
            // migrate eventjoiners
            $rsvpCount = self::DBGet("SELECT count(1) as cc FROM eventjoiners WHERE userid={$userByExternalId->id()}")[0]['cc'];
            if (0 == $rsvpCount) {
                self::DBMutate("UPDATE eventjoiners SET userid={$userByExternalId->id()} WHERE userid={$userByEmail->id()}");
                Logger::Log("User::RepairEmailExternalId, migrating eventjoiners from user identified with email={$email} to user identified by externalid={$externalid}", Logger::SEVERITY['INFO']);
            }
        }
        return $retVal;
    }

    public function isProgramTeamMember(int $teamid): bool
    {
        if ( $teamid < 1) {
            return false;
        }

        $retVal = false;
        $q1 = self::DBGet("SELECT `team_memberid` FROM `team_members` WHERE `teamid`='{$teamid}' AND `userid`='{$this->id()}'");
        if (!empty($q1)) {
            $retVal = true;
        }
        return $retVal;
    }

    /**
     * @return int
     */
//    public function updateFirstloginInZone ()
//    {
//        global $_COMPANY, $_ZONE;
//
//        $zoneids = $this->val('firstlogin_zoneids') ? explode(',',$this->val('firstlogin_zoneids')) : array();
//        $zoneids[] = $_ZONE->id();
//        $zoneids = array_unique($zoneids);
//        $zoneids_str = implode(',',$zoneids);
//        $retVal = self::DBMutate("UPDATE `users` SET `firstlogin_zoneids`='{$zoneids_str}',`modified`=now() WHERE `companyid`='{$_COMPANY->id}' AND `userid`='{$this->id}'");
//        User::GetUser($this->id, true); // Refresh cache after loading from master DB
//        $_SESSION['__cachedUser'] = self::GetUser($this->id);
//        return $retVal;
//    }

    public function swithMobileAppZone(int $zoneid,int $sessionkey){
        global $_COMPANY, $_ZONE;
        return self::DBUpdate("UPDATE `users_api_session` SET zoneid = '{$zoneid}', modified = NOW() WHERE `companyid`='{$_COMPANY->id()}' AND userid='{$this->id}' AND  api_session_id='{$sessionkey}' ");
    }


    public static function MergeUsers(int $userId1, int $userId2){
        global $_COMPANY,$_USER;
        $user1 = self::GetUser($userId1, true);
        $user2 = self::GetUser($userId2, true);
        if($user1->getExternalId() && $user2->getExternalId() ){ 
            return array('status'=>0,'message'=>'Two identities with distinct externalid cannot be merged');
        }elseif($user1->val('aad_oid') && $user2->val('aad_oid')){ 
            return array('status'=>0,'message'=>'Two identities with distinct AAD_OID cannot be merged');
        } elseif(!$user1->getExternalId() && !$user2->getExternalId() && !$user1->val('aad_oid') && !$user2->val('aad_oid')){
            return array('status'=>0,'message'=>'At least one user must have externalid / AAD_OID  to proceed merge');
        } else {
            if ($user1->val('createdon') < $user2->val('createdon')){
                $mainUser = $user1;
                $extraUser = $user2;
            } else {
                $mainUser = $user2;
                $extraUser = $user1;
            }

            // extra user will be deleted, lets first check if the extra users has any data that will prevent it to be merged
            // Once we figure out how to merge these records then we can remove these sanity checks.
            $extraUserMemberJoinRequestRecords = self::DBGet("SELECT count(1) as CC FROM member_join_requests WHERE companyid={$_COMPANY->id()} AND userid={$extraUser->id()}")[0]['CC'];
            if ($extraUserMemberJoinRequestRecords) { return array('status'=>0,'message'=>'Needs member join request merge, exiting'); }
            $extraUserTeamMembershipRecords = self::DBGet("SELECT count(1) as CC FROM team_members JOIN teams USING (teamid) WHERE teams.companyid={$_COMPANY->id()} AND team_members.userid={$extraUser->id()}")[0]['CC'];
            if ($extraUserTeamMembershipRecords) { return array('status'=>0,'message'=>'Needs team membership record merge, exiting'); }
            $extraUserEventVolunteerRecords = self::DBGet("SELECT count(1) as CC FROM event_volunteers JOIN events USING (eventid) WHERE events.companyid={$_COMPANY->id()} AND event_volunteers.userid={$extraUser->id()}")[0]['CC'];
            if ($extraUserEventVolunteerRecords) { return array('status'=>0,'message'=>'Needs volunteer record merge, exiting'); }
            $extraUserApprovalRecords = self::DBGet("SELECT count(1) as CC FROM topic_approvals__configuration_approvers WHERE companyid={$_COMPANY->id()} AND approver_userid={$extraUser->id()}")[0]['CC'];
            if ($extraUserApprovalRecords) { return array('status'=>0,'message'=>'Needs approval record merge, exiting'); }
            $extraUserPointsRecords = self::DBGet("SELECT count(1) as CC FROM user_points JOIN points_programs USING (points_program_id) WHERE points_programs.company_id={$_COMPANY->id()} AND user_points.user_id={$extraUser->id()}")[0]['CC'];
            if ($extraUserPointsRecords) { return array('status'=>0,'message'=>'Needs points program merge, exiting'); }

            //
            // Update group membership
            //
            $extraUserMemberships = self::DBGet("SELECT `memberid`, `groupmembers`.`groupid`, `chapterid`, `channelids`,`groupjoindate`, `groupmembers`.`isactive`, `groups`.zoneid FROM `groupmembers` JOIN  `groups` USING (groupid) WHERE `companyid`={$_COMPANY->id()} AND `userid`={$extraUser->id()}");
            foreach ($extraUserMemberships as $extraUserMembershp) {

                $mainUserMembershp = self::DBGet("SELECT `memberid`, `groupid`, `chapterid`, `channelids` FROM `groupmembers` WHERE `groupid`= {$extraUserMembershp['groupid']} AND `userid`={$mainUser->id()}");

                if (!empty($mainUserMembershp)){
                    $chapterids = implode(',',array_unique(array_merge(explode(',',$extraUserMembershp['chapterid']),explode(',',$mainUserMembershp[0]['chapterid']))));
                    $channelids = implode(',',array_unique(array_merge(explode(',',$extraUserMembershp['channelids']),explode(',',$mainUserMembershp[0]['channelids']))));

                    self::DBMutate("UPDATE groupmembers SET chapterid = '{$chapterids}', channelids = '{$channelids}' WHERE groupid={$extraUserMembershp['groupid']} AND userid={$mainUser->id()}");

                    $newChapterJoined = array_diff(explode(',',$extraUserMembershp['chapterid']),explode(',',$mainUserMembershp[0]['chapterid']));
                    foreach($newChapterJoined as $chapterid){
                        if($chapterid){
                            GroupUserLogs::CreateGroupUserLog($extraUserMembershp['groupid'], $mainUser->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chapterid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
                        }
                    }

                    $newChannelJoined = array_diff(explode(',',$extraUserMembershp['channelids']),explode(',',$mainUserMembershp[0]['channelids']));

                    foreach($newChannelJoined as $channelid) {
                        if ($channelid){
                            GroupUserLogs::CreateGroupUserLog($extraUserMembershp['groupid'], $mainUser->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $channelid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
                        }
                    }

                } else {
                    // Using DBMutate below as if
                    self::DBMutate("INSERT INTO groupmembers(groupid, userid, chapterid,channelids, groupjoindate, isactive) VALUES ({$extraUserMembershp['groupid']},{$mainUser->id()},'{$extraUserMembershp['chapterid']}','{$extraUserMembershp['channelids']}','{$extraUserMembershp['groupjoindate']}',{$extraUserMembershp['isactive']})");

                    $mainUser->addUserZone($extraUserMembershp['zoneid'], false, false);
                    // Create Group user log
                    GroupUserLogs::CreateGroupUserLog($extraUserMembershp['groupid'], $mainUser->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, '', 0, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
                    if ($extraUserMembershp['chapterid']){
                        $chapters = explode(',',$extraUserMembershp['chapterid']);
                        foreach($chapters as $chapterid){
                            if($chapterid){
                                GroupUserLogs::CreateGroupUserLog($extraUserMembershp['groupid'], $mainUser->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chapterid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
                            }
                        }
                    }
                    if ($extraUserMembershp['channelids']){
                        $channels = explode(',',$extraUserMembershp['channelids']);
                        foreach($channels as $channelid){
                            if ($channelid){
                                GroupUserLogs::CreateGroupUserLog($extraUserMembershp['groupid'], $mainUser->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $channelid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
                            }
                        }
                    }
                }
                // Clean

                if (self::DBMutate("DELETE FROM `groupmembers` WHERE `memberid`={$extraUserMembershp['memberid']}")){

                    // Create Group user log
                    GroupUserLogs::CreateGroupUserLog($extraUserMembershp['groupid'], $extraUser->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, '', 0, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);


                    $chapters = explode(',',$extraUserMembershp['chapterid']);
                    foreach ($chapters as $chap) {
                        if ($chap) { // We do not want to execute for chapterid 0
                            // Create Group user log
                            GroupUserLogs::CreateGroupUserLog($extraUserMembershp['groupid'], $extraUser->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chap, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
                        }
                    }
                    $channels = explode(',',$extraUserMembershp['channelids']);
                    foreach ($channels as $chan) {
                        if ($chan) { // We do not want to execute for channelid 0
                            // Create Group user log
                            GroupUserLogs::CreateGroupUserLog($extraUserMembershp['groupid'], $extraUser->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $chan, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
                        }
                    }
                };
            }

            //
            // Update Group lead roles
            //
            $extraUserLeads = self::DBGet("SELECT `leadid`, `groupleads`.`groupid`, `groupleads`.`userid`, `grouplead_typeid` as `typeid`, `groupleads`.`priority`, `assignedby`, `assigneddate`, `groupleads`.`isactive` FROM `groupleads` JOIN `groups` USING (groupid) WHERE companyid={$_COMPANY->id()} AND `userid`={$extraUser->id()}");
            foreach($extraUserLeads as $extraUserLead) {

                $mainUserLead = self::DBGet(" SELECT `leadid` FROM `groupleads` WHERE `groupid`={$extraUserLead['groupid']} AND `userid`={$mainUser->id()} AND `grouplead_typeid`={$extraUserLead['typeid']}");

                if (empty($mainUserLead )){ // Create record if not exist
                    self::DBInsert("INSERT INTO groupleads (groupid,userid,grouplead_typeid,assignedby,assigneddate,isactive) VALUES ({$extraUserLead['groupid']},{$mainUser->id()},{$extraUserLead['typeid']},{$extraUserLead['assignedby']},'{$extraUserLead['assigneddate']}',{$extraUserLead['isactive']})");
                    // Group User Log
                    GroupUserLogs::CreateGroupUserLog($extraUserLead['groupid'], $mainUser->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_LEAD'], $extraUserLead['typeid'], '', 0, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);

                }
                //Clean
                if(self::DBMutate("DELETE FROM `groupleads` WHERE `leadid`={$extraUserLead['leadid']}")){
                    // Group User Log
                    GroupUserLogs::CreateGroupUserLog($extraUserLead['groupid'], $extraUser->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_LEAD'], $extraUserLead['typeid'], '', 0, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);

                };
            }

            //
            // Update Chapterlead Recoreds
            //
            $extraUserChapterLeads = self::DBGet("SELECT `leadid`, `chapterid`, `chapterleads`.`groupid`, `chapterleads`.`userid`, `grouplead_typeid` as `typeid`, `chapterleads`.`priority`, `assignedby`, `assigneddate`, `chapterleads`.`isactive` FROM `chapterleads` JOIN `chapters` USING (chapterid) WHERE companyid={$_COMPANY->id()} AND chapterleads.userid={$extraUser->id()}");
            foreach($extraUserChapterLeads as $extraUserChapterLead) {

                $mainUserChapterLead = self::DBGet("SELECT `leadid` FROM `chapterleads` WHERE groupid={$extraUserChapterLead['groupid']} AND chapterid={$extraUserChapterLead['chapterid']} AND userid={$mainUser->id()} AND grouplead_typeid={$extraUserChapterLead['typeid']}");

                if (empty($mainUserChapterLead )){ // Create record if not exist
                    self::DBInsert("INSERT INTO `chapterleads`(`chapterid`, `groupid`, `userid`, `grouplead_typeid`, `assignedby`, `assigneddate`) VALUES ({$extraUserChapterLead['chapterid']},{$extraUserChapterLead['groupid']},{$mainUser->id()},{$extraUserChapterLead['typeid']},{$extraUserChapterLead['assignedby']},'{$extraUserChapterLead['assigneddate']}')");
                    // Group User Log
                    GroupUserLogs::CreateGroupUserLog($extraUserChapterLead['groupid'], $mainUser->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHAPTER_LEAD'], $extraUserChapterLead['typeid'], GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $extraUserChapterLead['chapterid'], GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
                }

                //Clean
                if(self::DBMutate("DELETE FROM `chapterleads` WHERE `leadid`={$extraUserChapterLead['leadid']}")){
                    // Group User Log
                    GroupUserLogs::CreateGroupUserLog($extraUserChapterLead['groupid'], $extraUser->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHAPTER_LEAD'], $extraUserChapterLead['typeid'], GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $extraUserChapterLead['chapterid'], GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
                }
            }

            //
            // Update channel lead
            //
            $extraUserChapterLeads = self::DBGet("SELECT `leadid`, `group_channel_leads`.`channelid`, `group_channel_leads`.`groupid`, `group_channel_leads`.`userid`, `grouplead_typeid` as `typeid`, `group_channel_leads`.`priority`, `assignedby`, `assigneddate`, `group_channel_leads`.`isactive` FROM `group_channel_leads` JOIN `group_channels` USING (channelid) WHERE companyid={$_COMPANY->id()} AND `group_channel_leads`.`userid`={$extraUser->id()}");
            foreach ($extraUserChapterLeads as $extraUserChapterLead) {
                $mainUserChapterLead = self::DBGet("SELECT `leadid` FROM `group_channel_leads` WHERE `groupid`={$extraUserChapterLead['groupid']} AND `channelid`={$extraUserChapterLead['channelid']} AND `userid`={$mainUser->id()} AND `grouplead_typeid`={$extraUserChapterLead['typeid']}");
                if (empty($mainUserChapterLead )){
                    self::DBInsert("INSERT INTO `group_channel_leads`(`channelid`, `groupid`, `userid`, `grouplead_typeid`, `assignedby`, `assigneddate`,`isactive`) VALUES ({$extraUserChapterLead['channelid']},{$extraUserChapterLead['groupid']},{$mainUser->id()},{$extraUserChapterLead['typeid']},{$extraUserChapterLead['assignedby']},{$extraUserChapterLead['assigneddate']},{$extraUserChapterLead['isactive']})");

                    // Group User Log
                    GroupUserLogs::CreateGroupUserLog($extraUserChapterLead['groupid'], $mainUser->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['ADD'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHANNEL_LEAD'], $extraUserChapterLead['typeid'], GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $extraUserChapterLead['channelid'], GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);

                }
                //Clean
                if(self::DBMutate("DELETE FROM `group_channel_leads` WHERE `leadid`={$extraUserChapterLead['leadid']}")){
                    // Group User Log
                    GroupUserLogs::CreateGroupUserLog($extraUserChapterLead['groupid'], $extraUser->id(), GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['CHANNEL_LEAD'], $extraUserChapterLead['typeid'], GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL'], $extraUserChapterLead['channelid'], GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['USER_MAINTENANCE']);
                }
            }

            //
            // Update Admin
            //
            $extraUserAdmins = self::DBGet("SELECT `companyid`, `zoneid`, `userid`, `manage_budget`, manage_approvers, `createdby`, `createdon` FROM `company_admins` WHERE `companyid`={$_COMPANY->id()} AND `userid`={$extraUser->id()}");
            foreach($extraUserAdmins as $extraUserAdmin) {

                $mainUserAdmin = self::DBGet("SELECT `companyid`, `zoneid`, `userid`, `manage_budget`, manage_approvers, `createdby`, `createdon` FROM `company_admins` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`='{$extraUserAdmin['zoneid']}' AND `userid`='{$mainUser->id()}' ");

                if (!empty($mainUserAdmin)){
                    $manage_budget = $mainUserAdmin[0]['manage_budget'] ?: $extraUserAdmin['manage_budget'];
                    $manage_approvers =$mainUserAdmin[0]['manage_approvers'] ?: $extraUserAdmin['manage_approvers'];

                    self::DBMutate("UPDATE `company_admins` SET `manage_budget`='{$manage_budget}',manage_approvers='{$manage_approvers}' WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$extraUserAdmin['zoneid']} AND `userid`='{$mainUser->id()}' ");
                } else {
                    self::DBInsert("INSERT INTO `company_admins`(`companyid`, `zoneid`, `userid`, `manage_budget`, manage_approvers, `createdby`, `createdon`) VALUES ({$_COMPANY->id()},{$extraUserAdmin['zoneid']},{$mainUser->id()},'{$extraUserAdmin['manage_budget']}','{$extraUserAdmin['manage_approvers']}','{$extraUserAdmin['createdby']}','{$extraUserAdmin['createdon']}')");
                }
            }

            //
            // Update Profile
            //
            $userzoneds = rtrim(implode(',',array_unique(array_merge(explode(',',$mainUser->val('zoneids')),explode(',',$extraUser->val('zoneids'))))),',');
            $firstname = $mainUser->val('firstname') ?: $extraUser->val('firstname');
            $lastname = $mainUser->val('lastname') ?: $extraUser->val('lastname');
            $pronouns = $mainUser->val('pronouns') ?: $extraUser->val('pronouns');
            $email = $extraUser->val('email') ?: $mainUser->val('email');
            $jobtitle = $mainUser->val('jobtitle') ?: $extraUser->val('jobtitle');
            $homeoffice = $mainUser->val('homeoffice') ?: $extraUser->val('homeoffice');
            $department = $mainUser->val('department') ?: $extraUser->val('department');
            $externalid = $mainUser->val('externalid') ?: ($extraUser->val('externalid') ?: null);
            $aad_oid = $mainUser->val('aad_oid') ?: ($extraUser->val('aad_oid') ?: null);
            $externalusername = $mainUser->val('externalusername') ?: ($extraUser->val('externalusername') ?: null);

            $firstname = addslashes($firstname ?: '');
            $lastname = addslashes($lastname ?: '');
            $pronouns = addslashes($pronouns ?: '');
            $email = addslashes($email);
            $jobtitle = addslashes($jobtitle);
            $externalid = addslashes($externalid);
            $externalusername = addslashes($externalusername);

            $extraUserId = $extraUser->id();
            $extraUserEmail = $extraUser->val('email');
            $extraUserExternalId = $extraUser->val('externalid');
            $extraUserAadOid = $extraUser->val('aad_oid');
            $extraUserExternalName = $extraUser->val('externalusername');

            //
            // Update likes, comments, event joiners
            //
            self::DBMutate("UPDATE topic_likes SET userid={$mainUser->id()} WHERE companyid={$_COMPANY->id()} AND userid={$extraUserId}");
            self::DBMutate("UPDATE topic_comments SET userid={$mainUser->id()} WHERE companyid={$_COMPANY->id()} AND userid={$extraUserId}");
            // commented event joiners as userid cannot be updated
            //self::DBMutate("UPDATE eventjoiners SET userid={$mainUser->id()} WHERE userid={$extraUserId}");
            self::DBMutate("UPDATE survey_responses_v2 SET userid={$mainUser->id()} WHERE companyid={$_COMPANY->id()} AND userid={$extraUserId}");
            self::DBMutate("UPDATE member_join_requests SET userid={$mainUser->id()} WHERE companyid={$_COMPANY->id()} AND userid={$extraUserId}");
            self::DBMutate("UPDATE appusage SET userid={$mainUser->id()} WHERE companyid={$_COMPANY->id()} AND userid={$extraUserId}");

            //
            // Delete extra user
            //
            $extraUser->delete(forceDelete: true);
            $updateProfile = self::DBUpdate("UPDATE users SET zoneids='{$userzoneds}',`firstname`='{$firstname}',`lastname`='{$lastname}', `pronouns`='{$pronouns}',`email`='{$email}',`jobtitle`='{$jobtitle}',`homeoffice`='{$homeoffice}',`department`='{$department}' WHERE companyid='{$_COMPANY->id()}' AND userid='{$mainUser->id()}'");
            if (empty($externalid)) {
                $updateProfile2 = self::DBUpdate("UPDATE users SET `externalid`=null WHERE companyid='{$_COMPANY->id()}' AND userid='{$mainUser->id()}'");
            } else {
                $updateProfile2 = self::DBUpdate("UPDATE users SET `externalid`='{$externalid}' WHERE companyid='{$_COMPANY->id()}' AND userid='{$mainUser->id()}'");
            }
            if (empty($aad_oid)) {
                $updateProfile2 = self::DBUpdate("UPDATE users SET `aad_oid`=null WHERE companyid='{$_COMPANY->id()}' AND userid='{$mainUser->id()}'");
            } else {
                $updateProfile2 = self::DBUpdate("UPDATE users SET `aad_oid`='{$aad_oid}' WHERE companyid='{$_COMPANY->id()}' AND userid='{$mainUser->id()}'");
            }
            if (empty($externalusername)) {
                $updateProfile3 = self::DBUpdate("UPDATE users SET `externalusername`=null WHERE companyid='{$_COMPANY->id()}' AND userid='{$mainUser->id()}'");
            } else {
                $updateProfile3 = self::DBUpdate("UPDATE users SET `externalusername`='$externalusername' WHERE companyid='{$_COMPANY->id()}' AND userid='{$mainUser->id()}'");
            }

            //
            // Clear Cache
            //
            User::GetUser($extraUserId, true); // Refresh cache after loading from master DB
            User::GetUser($mainUser->id(), true); // Refresh cache after loading from master DB

            Logger::Log("User->MergeUsers: Merged user [{$extraUserId},{$extraUserEmail},{$extraUserExternalId},{$extraUserAadOid},{$extraUserExternalName}] into [{$mainUser->id()},{$mainUser->val('email')},{$mainUser->val('externalid')},{$mainUser->val('aad_oid')},{$mainUser->val('externalusername')}]", Logger::SEVERITY['INFO']);

            return array('status'=>1,'message'=>'User Merged successfully','userid' => $mainUser->id, 'deleted_userid' => $extraUser->id);
        }
    }

    /**
     * Changes the homezone from current value to the specified value.
     * @param int $zoneid
     * @param string $app_type
     * @return int
     */
    public function changeHomeZone (int $zoneid,string $app_type)
    {
        return $this->addUserZone($zoneid, true, false);
    }

    public function formatUTCDatetimeForDisplayInLocalTimezone(?string $utcDatetime, bool $showDate=true, bool $showTime=true, bool $showTimezone=true, string $forceFormat='', string $timezone=''): string
    {
        if (!$utcDatetime)
            return '';

        $finalDateTimeFormat = '';

        if (empty($forceFormat)) {
            $finalDateTimeFormat .= ($showDate) ? $this->val('date_format') : '';
            $finalDateTimeFormat .= ($showTime) ? ' ' . $this->val('time_format') : '';
            $finalDateTimeFormat .= ($showTimezone) ? ' T (P)' : '';
            $finalDateTimeFormat = trim($finalDateTimeFormat, ' ');
        } else {
            $finalDateTimeFormat = $forceFormat;
        }

        $timezone = $timezone ?: $_SESSION['timezone'] ?? $this->val('timezone') ?: 'UTC';
        return self::covertUTCtoLocalDateTimeZone($finalDateTimeFormat, $utcDatetime, $timezone, $this->val('language'));
    }

    public function getGroupMembershipDetail(int $groupid): array
    {
        if ($groupid < 0) {
            return array();
        }
        $q = self::DBGet("SELECT * FROM groupmembers WHERE groupid='{$groupid}' AND userid='{$this->id()}' AND groupmembers.isactive=1 ");
        return empty($q) ? array() : $q[0];
    }

    public function updateNotificationSetting(int $memberid, string $key, int $value)
    {
       
        $setting = "";
        $value = ($value === 1) ? 1 : 0; // Apply strict check, if not one, then it is 0.
        if ($key === 'e'){
            $setting = "`notify_events`='{$value}'";
        } elseif ($key === 'p'){
            $setting = "`notify_posts`='{$value}'";
        } elseif ($key === 'n'){
            $setting = "`notify_news`='{$value}'";
        } elseif ($key === 'd'){
            $setting = "`notify_discussion`='{$value}'";
        } 

        if ( $setting ) {
            return  self::DBMutate("UPDATE groupmembers SET $setting WHERE `userid`='{$this->id()}' AND `memberid`='{$memberid}'");
        }
        return 0;
    }

    public function changeGroupMembershipPrivacySetting(int $memberid, int $anonymous)
    {
        $anonymous = ($anonymous === 1) ? 1 : 0; // Apply strict check, if not one, then it is 0.
        return  self::DBMutate("UPDATE `groupmembers` SET `anonymous`='{$anonymous}' WHERE `userid`='{$this->id()}' AND `memberid`='{$memberid}'");
    }

    public function updateMemberInvitesOtherData(int $memberinviteid, string $other_data)
    {
        global $_COMPANY;
        return self::DBMutatePS("UPDATE `memberinvites` SET `other_data` = ? WHERE `companyid`=? AND (`memberinviteid`=?)", 'xii', $other_data, $_COMPANY->id(), $memberinviteid);
    }

    /**
     * This method checks if the user can publish content in specified group as a grouplead. The way it is different
     * from canPublishContentInGroup is as described below:
     *  - canPublishContentInGroup: will return true if user can publish content into the group as a group lead, zone admin or company admin
     *  - canPublishContentInGroupOnly: will return true if user can publish content into the group as a group lead. It excludes zone admin or company admin checks.
     * @param int $groupid
     * @return bool
     */
    public function canPublishContentInGroupOnly (int $groupid): bool
    {
        $groupleadRows = $this->filterGroupleadRecords($groupid,0);
        foreach ($groupleadRows as $grow) {
            if ($grow['allow_publish_content']) {
                return true;
            }
        }
        return false;
    }

    /**
     * This method checks if the user can create content in specified group as a grouplead. The way it is different
     * from canCreateContentInGroup is as described below:
     *  - canCreateContentInGroup: will return true if user can publish content into the group as a group lead, zone admin or company admin
     *  - canCreateContentInGroupOnly: will return true if user can publish content into the group as a group lead. It excludes zone admin or company admin checks.
     * @param int $groupid
     * @return bool
     */
    public function canCreateContentInGroupOnly (int $groupid): bool
    {
        $groupleadRows = $this->filterGroupleadRecords($groupid,0);
        foreach ($groupleadRows as $grow) {
            if ($grow['allow_create_content']) {
                return true;
            }
        }
        return false;
    }


    public function getJoinedChapters(){
        global $_COMPANY, $_ZONE;
        $joinedChapters = self::DBROGet("SELECT IFNULL(GROUP_CONCAT(`groupmembers`.`chapterid`),0) as joinedChapters FROM `groupmembers` JOIN `groups` USING (`groupid`) WHERE `groups`.`companyid`='{$_COMPANY->id()}' AND `groups`.`zoneid`='{$_ZONE->id()}' AND `groupmembers`.`userid`='{$this->id()}'");
        return array_unique(explode(',', $joinedChapters[0]['joinedChapters']));
    }

    public function getJoinedChannels(){
        global $_COMPANY, $_ZONE;
        $joinedChannels = self::DBROGet("SELECT  IFNULL(GROUP_CONCAT(`channelids`),0) as joinedChannels FROM `groupmembers` JOIN `groups` USING (`groupid`) WHERE `groups`.`companyid`='{$_COMPANY->id()}' AND `groups`.`zoneid`='{$_ZONE->id()}' AND `groupmembers`.`userid`='{$this->id()}'");
       return array_unique(explode(',', $joinedChannels[0]['joinedChannels']));
    }

    
    /**
     * GetZoneUsersByUserids Return all active users by matching userids with in the current zone of a company
     *
     * @param  array $userids
     * @return array only returns email. In the future the method can be extended to retrn other user attrs
     */
    public static function GetZoneUsersByUserids(array $userids, bool $includeUserProfileFields = false): array
    {
        global $_COMPANY, $_ZONE;
        $users = array();
        $includeFileds = "";
        if ($includeUserProfileFields) {
            $includeFileds = ",`firstname`, `lastname`, `email`, `picture`, `jobtitle`";
        }

        $userids_chunks = array_chunk($userids, 1000); // Max 1000 users at a time to reduce DB Load
        foreach ($userids_chunks as $chunk) {
            $chunk = Sanitizer::SanitizeIntegerArray($chunk);

            if (!empty($chunk)) {
                $chunk_str = implode(',', $chunk);
                $chunk_users = self::DBROGet("SELECT `email` {$includeFileds} FROM users WHERE `companyid`='{$_COMPANY->id()}' AND FIND_IN_SET({$_ZONE->id()},zoneids) AND `userid` IN({$chunk_str}) AND isactive=1");
                $users = array_merge($users, $chunk_users);
            }
        }
        return $users;
    }

    /**
     * Use this function to get logged in users points as it can cache points in session cache
     * @param bool $refresh
     * @return int
     */
    public function getPointsBalance(bool $refresh=false): int
    {
        global $_COMPANY;
        if ($this->pointsbalance === null || $refresh) {
            $this->pointsbalance = UserPoints::PointsBalanceForUser($this->id());
        }
        return $this->pointsbalance;
    }

    /**
     * User preferences are key value pairs for user data
     * @param UserPreferenceType $preferenceType
     * @param bool $refresh if true then a fresh copy is fetched from the database
     * @return string
     */
    public function getUserPreference(UserPreferenceType $preferenceType, bool $fromMaster = false): int|string|null
    {
        global $_COMPANY, $_ZONE;

        $key = str_starts_with($preferenceType->name, 'ZONE_') ? "Z{$_ZONE->id()}_".$preferenceType->value : $preferenceType->value;

        if ($this->userpreferences === null || $fromMaster) {
            $sql = "SELECT preferences FROM user_preferences WHERE companyid={$this->cid()} AND userid={$this->id()}";
            if ($fromMaster)
                $pref = self::DBGet($sql);
            else
                $pref = self::DBROGet($sql);

            if (!empty($pref)) {
                $this->userpreferences = json_decode($pref[0]['preferences'], true) ?? [];
            }
        }

        return $this->userpreferences[$key] ?? null;
    }

    /**
     * @param UserPreferenceType $preferenceType
     * @param int|string|null $value if null the preference is removed from user preferences
     * @return int
     */
    public function setUserPreference(UserPreferenceType $preferenceType, int|string|null $value): int
    {
        global $_COMPANY, $_ZONE;

        if ($this->getUserPreference($preferenceType) === $value)
            return 1; // Already correctly set in value and type

        $key = str_starts_with($preferenceType->name, 'ZONE_') ? "Z{$_ZONE->id()}_".$preferenceType->value : $preferenceType->value;
        $keypath = '$.'.$key;
        $retVal = 0;

        if (is_null($value))
            $retVal = self::DBMutatePS("
                UPDATE user_preferences SET preferences=JSON_REMOVE(preferences, ?)
                WHERE companyid=? AND userid=?",
                'xii',
                $keypath, $this->cid(), $this->id()
            );
        else
            $retVal = self::DBMutatePS("
                INSERT INTO user_preferences (companyid, userid, preferences) 
                VALUES (?,?,JSON_OBJECT(?,?)) 
                ON DUPLICATE KEY UPDATE preferences=JSON_SET(preferences, ?, ?)",
                (is_int($value) ? 'iixixi' : 'iixxxx'),
                $this->cid(),$this->id(), $key, $value, $keypath, $value
            );

        $this->getUserPreference($preferenceType, true); // Reload preferences
        return $retVal;
    }

    /**
     * This function will be added to Zone removal procedure
     * @param int $zoneid
     * @return int
     */
    public function deleteAllUserPreferencesForZone (int $zoneid)
    {
        $keys_to_remove = [
            UserPreferenceType::ZONE_SelectedGroupCategory->value,
            UserPreferenceType::ZONE_ShowMyGroups->value,
        ];
        $keys_to_remove = array_map(function($val) use ($zoneid){return "'$.Z{$zoneid}_{$val}'";}, $keys_to_remove);

        $key_paths = implode(',', $keys_to_remove);
        return self::DBMutate("
                UPDATE user_preferences 
                SET preferences = JSON_REMOVE(preferences, {$key_paths}) 
                WHERE companyid=$this->cid AND userid=$this->id
                ");
    }

    public function deleteAllUserPreferences ()
    {
        return self::DBMutate("DELETE FROM user_preferences WHERE companyid={$this->cid} AND userid={$this->id}");
    }

    public static function GetAllUsers (int $page = 1, int $limit = 100) : array
    {
        global $_COMPANY;

        if ($limit < 0) {
            return array();
        }

        $start = ($page - 1) * $limit;
        if ($start < 0) {
            return array();
        }

        return self::DBROGet("SELECT companyid, userid, email, firstname, lastname, externalid, createdon, modified, isactive FROM users WHERE companyid={$_COMPANY->id()} LIMIT {$start}, {$limit}");
    }

    public function getExternalRoles(): array
    {
        $external_roles = $this->val('externalroles');

        if (!$external_roles) {
            return [];
        }

        return explode(',', $external_roles);
    }

    /**
     * @param string|null $externalRoleCsv if null, the function does not do anything.
     * @return int
     */
    public function updateExternalRoles (?string $externalRoleCsv) : int
    {
        if ($externalRoleCsv === null) {
            return 0;
        }

        if ($externalRoleCsv === $this->val('externalroles')) {
            return 0;
        }

        // 1. Update in Database
        self::DBUpdatePS(
            'UPDATE users SET externalroles = ?, modified = NOW() WHERE userid = ?',
            'si',
            $externalRoleCsv,
            $this->id()
        );

        User::GetUser($this->id(), true); // Refresh cache after loading from master DB

        // 1.1 If update is sucessful, add Audit Log showing previous and new volues of $external_roles
        self::LogObjectLifecycleAudit('update', 'user', $this->id(), 0, [
            'operation_details' => [
                'opname' => 'update_user_external_roles',
                'old' => [
                    'externalroles' => $this->val('externalroles'),
                ],
                'new' => [
                    'externalroles' => $externalRoleCsv,
                ],
            ],
        ]);

        // 1.2 If update is sucessful, update value of current object to allow downstream use
        $this->setField('externalroles', $externalRoleCsv);

        //return db update value
        return 1;
    }

    public static function CleanUserAppSessionByDeviceToken(string $devicetoken){
        global $_COMPANY;
        return self::DBMutate("DELETE FROM `users_api_session` WHERE `companyid`='{$_COMPANY->id()}' AND `devicetoken`='{$devicetoken}'");
    }

    /**
     * This function checks if a user's membership of a restricted group is still allowed and if not remove the membership
     * @param array $restricted_groups
     * @param string $initiated_by
     * @return void
     */
    public function deactivateMembershipForNonCompliantRestrictedGroups(array $restricted_groups, string $initiated_by): void
    {
        array_walk($restricted_groups, function (Group $group) use ($initiated_by) {
            // Optimization: continue only is user is a group member
            if ($this->isGroupMember($group->id(),0,false) && !$group->isUserAllowedToJoin($this)) {
                $this->activateInactivateMembership($group->id(), false, 'HRIS_SYNC');
            }
        });
    }

    public function isOnlyChaptersLeadCSV(string $groupids) 
    {   
        global $_COMPANY;
        $groupids = explode(',',Sanitizer::SanitizeIntegerCSV($groupids));
        if (empty($groupids)) {
            return false;
        }

        if ($this->isCompanyAdmin()) {
            return false;
        }
        $groups = array();
        // First check Group level hand higher permissions
        foreach ($groupids as $groupid) {
            if ($groupid) {
                $g = Group::GetGroup($groupid);
                if ( 
                    $this->isZoneAdmin($g->val('zoneid')) ||
                    $this->isGrouplead($groupid) || 
                    $this->isRegionallead($groupid) 
                ) {
                    return false;
                }
                $groups[] = $g;
            }
        }
        // If higher permissions failed then check Chapter level permission only
        foreach ($groups as $g) {
            if ( 
                $this->isChapterlead($g->val('groupid'),-1) 
            ) {
                return true;
            }
        }
        return false;
    }

    public function isUserHaveAnyAdminstrativePermissions(int $groupid)
    {
        if ($this->isCompanyAdmin()) {
            return true;
        }
        if ($groupid) {
            $g = Group::GetGroup($groupid);
            if (
                $this->isZoneAdmin($g->val('zoneid')) ||
                $this->isGrouplead($groupid) ||
                $this->isRegionallead($groupid) ||
                $this->isChapterlead($groupid,-1) ||
                $this->isChannellead($groupid,-1)
            ) {
                return true;
            }
        }
        return false;
    }


    public function canCreateContentInGroupChapterV2 (int $groupid, int $regionid, int $chapterid): bool
    {
        if ($chapterid === 0)
            $regionid = 0; // Regionid cannot be set if chapter is not set.


        if ($this->isCompanyAdmin()) {
            return true;
        }

        if ($groupid) {
            $group = Group::GetGroup($groupid);

            if ($this->isZoneAdmin($group->val('zoneid'))){
                return true;
            }
        }

        if (
            $groupid < 0 ||
            $regionid < 0 ||
            $chapterid < 0
        ) {
            return false;
        }

        $what = 'allow_create_content';

        // First check if there is a match at Group level
        $groupleadRows = $this->filterGroupleadRecords($groupid,$regionid);
        foreach ($groupleadRows as $grow) {
            if ($grow[$what]) {
                return true;
            }
        }

         // Next check if there is a match at Chapter level
        $chapterleadRows = $this->filterChapterleadRecords($groupid, $chapterid);
        foreach ($chapterleadRows as $crow) {
            if ($crow[$what]) {
                return true;
            }
        }
        return false;
    }


    public function canPublishContentInGroupChapterV2 (int $groupid, int $regionid, int $chapterid): bool
    {
        if ($chapterid === 0)
            $regionid = 0; // Regionid cannot be set if chapter is not set.

        if ($this->isCompanyAdmin()) {
            return true;
        }

        if ($groupid) {
            $group = Group::GetGroup($groupid);

            if ($this->isZoneAdmin($group->val('zoneid'))){
                return true;
            }
        }

        if (
            $groupid < 0 ||
            $regionid < 0 ||
            $chapterid < 0
        ) {
            return false;
        }

        $what = 'allow_publish_content';

        // First check if there is a match at Group level
        $groupleadRows = $this->filterGroupleadRecords($groupid,$regionid);
        foreach ($groupleadRows as $grow) {
            if ($grow[$what]) {
                return true;
            }
        }

         // Next check if there is a match at Chapter level
        $chapterleadRows = $this->filterChapterleadRecords($groupid, $chapterid);
        foreach ($chapterleadRows as $crow) {
            if ($crow[$what]) {
                return true;
            }
        }
        return false;
    }

    public function canManageBudgetGroupSomethingV1 (int $groupid): bool
    {
        
        if ($this->isCompanyAdmin()) {
            return true;
        }

        $group = Group::GetGroup($groupid);
        if (!$group) {
            return false;
        }

        if (
            ($this->canManageZoneBudget($group->val('zoneid'))) // Admins need explict budget role.
        ) {
            return true;
        }
        $what = 'allow_manage_budget';
        // First check if there is a match at Group level
        $groupleadRows = $this->filterGroupleadRecords($groupid,-1);
        foreach ($groupleadRows as $grow) {
            if ($grow[$what]) {
                return true;
            }
        }

        // Next check if there is a match at Chapter level
        $chapterleadRows = $this->filterChapterleadRecords($groupid, -1);
        foreach ($chapterleadRows as $crow) {
            if ($crow[$what]) {
                return true;
            }
        }
        // Next check if there is a match at Channel level
        $channelleadRows = $this->filterChannelleadRecords($groupid, -1);
        foreach ($channelleadRows as $crow) {
            if ($crow[$what]) {
                return true;
            }
        }
        return false;
    }

    public function canManageContentInGroupChapterV2 (int $groupid, int $regionid, int $chapterid): bool
    {
        if ($chapterid === 0)
            $regionid = 0; // Regionid cannot be set if chapter is not set.

        if ($this->isCompanyAdmin()) {
            return true;
        }

        if ($groupid) {
            $group = Group::GetGroup($groupid);

            if ($this->isZoneAdmin($group->val('zoneid'))){
                return true;
            }
        }

        if (
            $groupid < 0 ||
            $regionid < 0 ||
            $chapterid < 0
        ) {
            return false;
        }

        $what = 'allow_manage';

        // First check if there is a match at Group level
        $groupleadRows = $this->filterGroupleadRecords($groupid,$regionid);
        foreach ($groupleadRows as $grow) {
            if ($grow[$what]) {
                return true;
            }
        }

         // Next check if there is a match at Chapter level
        $chapterleadRows = $this->filterChapterleadRecords($groupid, $chapterid);
        foreach ($chapterleadRows as $crow) {
            if ($crow[$what]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if the user can approve something
     * @return bool
     */
    public function canApproveSomething(): bool
    {
        global $_COMPANY;
        if ($this->approvaldata === null) {
            $this->approvaldata = [];
            $this->approvaldata['stages']['events'] = [];
            if ($_COMPANY->getAppCustomization()['event']['enabled']) {
                $this->approvaldata['stages']['events'] = Event::GetAllTheStagesThatUserCanApprove($this->id());
            }

            $this->approvaldata['stages']['newsletters'] = [];
            if ($_COMPANY->getAppCustomization()['newsletters']['enabled']) {
                $this->approvaldata['stages']['newsletters'] = Newsletter::GetAllTheStagesThatUserCanApprove($this->id());
            }

            $this->approvaldata['stages']['post'] = [];
            if ($_COMPANY->getAppCustomization()['post']['enabled']) {
                $this->approvaldata['stages']['post'] = Post::GetAllTheStagesThatUserCanApprove($this->id());
            }

            $this->approvaldata['stages']['surveys'] = [];
            if ($_COMPANY->getAppCustomization()['surveys']['enabled']) {
                $this->approvaldata['stages']['surveys'] = Survey2::GetAllTheStagesThatUserCanApprove($this->id());
            }
        }

        return
            $this->approvaldata['stages']['events'] ||
            $this->approvaldata['stages']['newsletters'] ||
            $this->approvaldata['stages']['post'] || 
            $this->approvaldata['stages']['surveys'];
    }

    public function getStartDate()
    {
        return $this->val('employee_start_date') ?? '';
    }

    /**
     * Note: This is a trivial function that does not need a database lookup unless user session is
     * refreshed.
     * @return array
     */
    public function getDelegatedAccessUserAuthorizedZones(): array
    {
        global $_USER;

        if (!$this->isDelegatedAccessUser()) {
            return [];
        }

        if ($this->session_delegatedzones === null) {
            /**
             * Remember, $_USER is now the grantor
             * To get the grantee userid, see the session variable $_SESSION['grantee_userid']
             */
            $this->session_delegatedzones = DelegatedAccess::GetDelegatedZonesByGrantorGranteeUserId(grantee_userid: (int)$_SESSION['grantee_userid'], grantor_userid: $_USER->id());
        }

        return $this->session_delegatedzones;
    }

    public function updateBio(string $bio)
    {
        return self::DBMutatePS("INSERT INTO user_bio SET `userid`=?, `companyid`=?, `bio` = ? ON DUPLICATE KEY UPDATE `bio` = ?", "iixx", $this->id, $this->cid, $bio, $bio);
    }

    public function getBio() : string
    {
        $bio = self::DBROGet("SELECT `bio` FROM user_bio WHERE userid={$this->id()} AND companyid={$this->cid}");
        if (!empty($bio)) {
            return $bio[0]['bio'] ?? '';
        }
        return '';
    }

    public function updateManagerUserId (?int $manager_userid)
    {
        global $_COMPANY;

        $manager_userid_param = $manager_userid ?? 'null';
        return self::DBUpdate("UPDATE `users` SET manager_userid={$manager_userid_param} WHERE companyid={$_COMPANY->id()} AND userid={$this->id()}");
    }
    public function getUserHeirarcyManager() : ?User
    {
        return empty($this->val('manager_userid')) ? null : User::GetUser($this->val('manager_userid'));
    }

    public function getUserHeirarcyPeers() : ?array
    {
        global $_COMPANY;
        $peers = array();
        $manager_id = $this->val('manager_userid');
        if ($manager_id) {
            $peer_recs = self::DBROGet("SELECT * FROM users WHERE manager_userid = {$manager_id} AND companyid = {$_COMPANY->id()} AND userid != {$this->id}");
            $peers = array_map(function($peer_rec) {
                    return new User($peer_rec['userid'], $peer_rec['companyid'], $peer_rec);
                }, $peer_recs);
        }
        return $peers;
    }

    public function getUserHeirarcySubordinates(?int $levels = null) : ?array
    {
        global $_COMPANY;
        $subordinates = [];
        $reporting_recs = [];
        $currentLevelIds = [$this->id];
        $currentLevel = 0;
        $visited = [];

        while (!empty($currentLevelIds)) {

            if (!is_null($levels) && $currentLevel >= $levels) break;

            $manager_userids = implode(',', $currentLevelIds);

            $subordinate_rows = self::DBROGet("SELECT * FROM users WHERE companyid={$_COMPANY->id()} AND manager_userid IN ($manager_userids)");

            $reporting_recs = array_merge($reporting_recs, $subordinate_rows);

            $currentLevelIds = [];
            foreach ($subordinate_rows as $sub) {
                if (!in_array($sub['userid'], $visited)) {
                    $currentLevelIds[] = $sub['userid'];
                    $visited[] = $sub['userid'];
                }
            }
            $currentLevel++;
        }

        $subordinates = array_map(function($reporting_rec) {
                return new User($reporting_rec['userid'], $reporting_rec['companyid'], $reporting_rec);
            }, $reporting_recs);

        return $subordinates;
    }
    
    public function getAllGroupsUserCanManage(string $permission)
    {
       $allGroups =  $this->getAllGroupleadRecords();
       $groupsCanManage = array();
       foreach($allGroups as $g) {
            if ($g[$permission]) {
                $groupsCanManage[] = $g;
            }
        }
        return  $groupsCanManage;
    }
}
