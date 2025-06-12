<?php

// Do no use require_once as this class is included in Company.php.

class UserCatalog extends Teleskope
{
    private const USER_CATALOG_SECRET_IV = 'BDkquByeGU06SIZzIeQoFTXLMHXQhV6n9hHpQIpQ';
    private $user_catalog_userids;

    // Virtual Catalog Keys
//    const VIRTUAL_CATLOGS = array (
//        'groupmember.group' => 'Member Of Group',
//        'grouplead.type' => 'Group Lead Type',
//        'chapterlead.type' => 'Chapter Lead Type',
//        'channellead.type' => 'Channel Lead Type'
//    );

    // Set Type Operators
    const SET_TYPE_OPERATOR__IN = 'IN';
    const SET_TYPE_OPERATOR__NOT_IN = 'NOT_IN';
    const SET_TYPE_OPERATORS_MAP = array (
        self::SET_TYPE_OPERATOR__IN => "IN",
        self::SET_TYPE_OPERATOR__NOT_IN => "NOT IN"
    );

    const STRING_TYPE_OPERATORS = array(
        '1' => "==", // Exact Match
        '2' => "!=", // Not a Match
    );
    const INT_TYPE_OPERATORS = array(
        '1' => ">", //  Greater Than
        '2' => "==", // Equal
        '3' => "<", // Less Than
        '4' => "!=", // Does not Match
        '5' => '>=', // Greater Than or Equal To
        '6' => '<=', // Greater Than or Equal To
        '11' => '[...]' // Range match
    );
    const STRING_TYPE_OPERATORS_REVERSE = array(
        '1' => "==", // same as match
        '2' => "!=", // same as not a match
    );
    const INT_TYPE_OPERATORS_REVERSE = array(
        '1' => "<", //  Less Than
        '2' => "==", // Equal
        '3' => ">", // Greater Than
        '4' => "!=", // Does not Match
        '5' => '<=', // Greater Than or Equal To
        '6' => '>=', // Greater Than or Equal To
        '11' => '[...]' // Range match
    );

    protected function __construct(int $companyid, array $fields, array $userids)
    {
        unset($fields['user_catalog_userids']); // To ensure we are not adding userids from fields
        parent::__construct(-1, $companyid, $fields);
        $this->user_catalog_userids = $userids;// array_unique($userids);
    }

    private static function GetUserCatalogCategoryRowByName (string $category) : ?array
    {
        global $_COMPANY;
        $category_rows = self::DBGetPS("SELECT * FROM user_catalog_categories WHERE companyid=? AND (user_catalog_category=?)", 'ix', $_COMPANY->id, $category);
        if (count($category_rows) == 1) {
            return $category_rows[0];
        }

        if (count($category_rows) > 1) { // Log error
            $category_row_count = count($category_rows);
            $category_rows = array_slice($category_rows, 0, 2); // Print only two rows
            Logger::Log('Inconsistent Categories with duplicate names found', Logger::SEVERITY['FATAL_ERROR'],
                [
                'Categories Found' => $category_row_count,
                ...$category_rows
                ]
            );
        }

        return null;
    }

    /**
     * Returns categoryid if catalog categoryid is avaialble, 0 otherwise;
     * if $zoneid is provided, then search is restricted to categories visible in that zone
     * @param string $category
     * @param int|null $zoneid
     * @return int
     */
    public static function GetCategoryIdByName(string $category, ?int $zoneid = null): int
    {
        $category_rows = self::GetAllCatalogCategoriesAsRows($zoneid);

        return Arr::SearchColumnReturnColumnVal($category_rows, $category, 'user_catalog_category', 'user_catalog_categoryid') ?: 0;
    }

    public static function DeleteAllUserCatalogsByCategoryIdAndSource(string $category, int $source_id)
    {
        global $_COMPANY;
        $category_row = self::GetUserCatalogCategoryRowByName($category);
        if ($category_row) {
            $user_catalog_categoryid = $category_row['user_catalog_categoryid'];
            self::DBMutate("DELETE FROM user_catalogs WHERE companyid={$_COMPANY->id} AND (user_catalog_categoryid={$user_catalog_categoryid} AND source_id = {$source_id})");
        }
    }

    /**
     * Fetches category id of existing category or createa a new one.
     * @param string $category
     * @param string $category_internal_id
     * @param string $keytype
     * @return int returns categoryid or 0 if error
     */
    private static function GetOrCreateUserCatalogCategory(string $category, string $category_internal_id, string $keytype) : int
    {
        global $_COMPANY;
        $keytype = ($keytype == 'int') ? 'int' : 'string';

        // First check if we already have a category by that name

        $category_row = self::GetUserCatalogCategoryRowByName($category);

        if ($category_row) { // Category exists; validate internalid and keytype
            if ($category_internal_id != $category_row['user_catalog_category_internal_id'] ||
                $keytype != $category_row['user_catalog_keytype']
            ) {
                Logger::Log('Unable to create inconsistent catalogs', Logger::SEVERITY['FATAL_ERROR'], [
                    'Category' => $category,
                    'New Category Internal ID' => $category_internal_id,
                    'New Category Keytype' => $keytype,
                    'Existing Category Internal ID' => $category_row['user_catalog_category_internal_id'],
                    'Existing Category Keytype' => $category_row['user_catalog_keytype'],
                ]);
                return 0;
            }
            $category_id = $category_row['user_catalog_categoryid'];
        } else { // Create a new one
            $category_id = self::DBInsertPS("INSERT into user_catalog_categories (companyid, user_catalog_category, user_catalog_category_internal_id, user_catalog_keytype) VALUES (?,?,?,?)", 'ixxx', $_COMPANY->id(), $category, $category_internal_id, $keytype);
            if (!$category_id) {
                Logger::Log('Failed to insert new user catalog category', Logger::SEVERITY['FATAL_ERROR'], [
                    'Category' => $category,
                    'Category Internal ID' => $category_internal_id,
                    'Keytype' => $keytype
                ]);
                return 0;
            }
        }

        return $category_id;
    }
    /**
     * This function first deletes and then Saves a persistence record. If items are not provided then it simply delete.
     * Since this method is called in a loop, after finishing the loop dont forget to call $_COMPANY->expireRedisCache("UCC:{$_COMPANY->id()}");
     * @param string $category
     * @param string $category_internal_id
     * @param string $keyname
     * @param string $keytype
     * @param array $userids
     * @param int $source_id
     * @return void
     */
    public static function DeleteAndSaveCatalog(string $category, string $category_internal_id, string $keyname, string $keytype, array $userids, int $source_id)
    {
        global $_COMPANY;

        $category_id = self::GetOrCreateUserCatalogCategory($category, $category_internal_id, $keytype);

        if (!$category_id)
            return;

        self::DBMutatePS("DELETE FROM user_catalogs WHERE companyid=? AND (user_catalog_categoryid=? AND user_catalog_keyname=? AND source_id = ?)",'iixi', $_COMPANY->id, $category_id, $keyname, $source_id);

        if (!empty($userids)) {
            $keytype = ($keytype == 'int') ? 'int' : 'string';
            self::DBUpdatePS("INSERT INTO user_catalogs (companyid, user_catalog_categoryid, user_catalog_keyname, user_catalog_userids, source_id)  VALUES (?,?,?,?,?)", 'iixxi', $_COMPANY->id, $category_id, $keyname, implode(',',$userids), $source_id);
        }
    }

    /**
     * This is a trivial function which has been memoized and it can be called as many times as needed without much performance penality.
     * This method fetches distinct catalog category rows from the database. Implements 1 day Redis cache
     * @param int $zoneid
     * @return array returns an array of all catalog category rows with columns user_catalog_category, user_catalog_category_internal_id,user_catalog_keytype
     */
    public static function GetAllCatalogCategoriesAsRows(?int $zoneid) : array
    {
        global $_COMPANY;
        $obj = null;

        $memoize_key = __METHOD__ . ':' . serialize(func_get_args());
        if (isset(self::$memoize_cache[$memoize_key]))
            return self::$memoize_cache[$memoize_key] ?? [];

        $key = "UCC:{$_COMPANY->id()}";
        if (($rows = $_COMPANY->getFromRedisCache($key)) === false) {
            $rows = self::DBGet("
                SELECT *
                FROM user_catalog_categories 
                WHERE companyid={$_COMPANY->id()}
                ");

            if (!is_array($rows)) $rows = [];

            // Note redis caching is at global level
            $_COMPANY->putInRedisCache($key, $rows, 3600); // 1 Hour
        }

        if ($zoneid) {
            $rows = array_filter($rows, function ($r) use ($zoneid) {
                return in_array($zoneid, explode(',', $r['visible_in_zoneids'] ?? ''));
            });
        }

        // Note memoization is a zone level as memoize function is aware of passed paramters (i.e. zoneid)
        self::$memoize_cache[$memoize_key] = $rows;

        return self::$memoize_cache[$memoize_key];
    }

    /**
     * #### This method will return Catalog Categories visibile in the current zone ($_ZONE)
     * This method fetches all Catalog categories and returns an associative array with catalog name in the
     * array values and internal key name that maps to users table in array key.
     * @return array
     */
    public static function GetAllCatalogCategories() : array
    {
        global $_ZONE;
        $retVal = array();
        $rows = self::GetAllCatalogCategoriesAsRows($_ZONE->id());
        foreach ($rows as $row) {
            $retVal[$row['user_catalog_category_internal_id']] = $row['user_catalog_category'];
        }
//        foreach (self::VIRTUAL_CATLOGS as $k => $v) {
//            $retVal[$k] = $v;
//        }

        return $retVal;
    }

    public static function GetAllCategoryKeys(string $category) : array
    {
        global $_COMPANY;

        $catalog_categoryid = self::GetCategoryIdByName($category, null);
        $rows = self::DBROGetPS("SELECT user_catalog_keyname FROM user_catalogs WHERE companyid=? AND (user_catalog_categoryid=?)",'ii',$_COMPANY->id(),$catalog_categoryid);
        return array_unique(array_column($rows ?: [], 'user_catalog_keyname'));
    }

    /**
     * Returns the key type,
     * @param string $category
     * @return string return values are 'string' or 'int'
     */
    public static function GetCategoryKeyType(string $category) : string
    {
        // pass null to GetAllCatalogCategoriesAsRows get all values regardless of zone.
        $val = Arr::SearchColumnReturnColumnVal(self::GetAllCatalogCategoriesAsRows(null), $category, 'user_catalog_category', 'user_catalog_keytype');
        return ($val === 'int') ? 'int' : 'string';
    }

    public static function GetCategoryInternalId(string $category) : string
    {
        // pass null to GetAllCatalogCategoriesAsRows get all values regardless of zone.
        $val = Arr::SearchColumnReturnColumnVal(self::GetAllCatalogCategoriesAsRows(null), $category, 'user_catalog_category', 'user_catalog_category_internal_id');
        return is_string($val) ? $val : '';
    }

    /**
     * Returns a UserSet of given type identified by name
     * @param string $category
     * @param string $keyname
     * @param string $comparator one of the following '==', '!=', '>', '<', '>=', '<='
     * @param array|null $restrict_search_to_userids
     * @return UserCatalog|null
     */
    public static function GetUserCatalog (string $category, string $keyname, string $comparator, ?array $restrict_search_to_userids = null)
    {
        global $_COMPANY;

        $compartor_mapping = array(
            '==' => '=',
            '!=' => '!=',
            '>' => '>',
            '<' => '<',
            '>=' => '>=',
            '<=' => '<=',
            '[...]' => '[...]',
        );
        $match_op = $compartor_mapping[$comparator] ?? '=';

        $fields = array(
            'companyid' => $_COMPANY->id,
            'user_catalog_category' => $category,
            'user_catalog_keyname' => $match_op.$keyname,
            'createdon' => gmdate('Y-m-d H:m:s')
        );


        $user_catalog_userids  = [];

        if (!empty($category) && !empty($keyname)) {

            $catalog_categoryid = self::GetCategoryIdByName($category, null);

            if ($comparator == '>' || $comparator == '<') { // For > or < the value types are int
                $rows = self::DBROGetPS("SELECT * FROM user_catalogs WHERE companyid=? AND (user_catalog_categoryid=? AND user_catalog_keyname {$match_op} ?)", 'iii', $_COMPANY->id(), $catalog_categoryid, $keyname);
            } elseif($comparator == '[...]'){
                [$a,$b] = json_decode($keyname,true);
                $minimum = min($a,$b);
                $maximum = max($a,$b);
                $rows = self::DBROGetPS("SELECT * FROM user_catalogs WHERE companyid=? AND (user_catalog_categoryid=? AND user_catalog_keyname BETWEEN ? AND ?)", 'iiii', $_COMPANY->id(), $catalog_categoryid, $minimum,$maximum);
            }else {
                $rows = self::DBROGetPS("SELECT * FROM user_catalogs WHERE companyid=? AND (user_catalog_categoryid=? AND user_catalog_keyname {$match_op} ?)", 'iix', $_COMPANY->id(), $catalog_categoryid, $keyname);
            }

            if (!empty($rows)) {
                // ### Performance Improvement 1: ###
                // Instead of using array_merge in a loop
                // just process array of arrays in array_merge as it is much faster
                // So instead of
                //
                //foreach ($rows as $row) {
                //    $user_catalog_userids = array_merge($user_catalog_userids, explode(',', $row['user_catalog_userids']));
                //}
                // do the following
                //
                $user_catalog_userids = array_unique(
                            array_merge($user_catalog_userids, ... array_map(
                                function($val) { return explode(',', $val??'');},
                                array_column($rows,'user_catalog_userids')
                                )
                            )
                        );
            }
        }
        if ($restrict_search_to_userids!==null) {
            // ### Performance Improvement 2: ###
            // array_intersect on large set of values can be slow, so we will use keys
            // merge using array flip
            // so insted of the following
            //$user_catalog_userids = array_intersect($user_catalog_userids,$restrict_search_to_userids);
            // we will do the following which is 10x faster
            $user_catalog_userids = array_flip(
                array_intersect_key(
                    array_flip($user_catalog_userids),
                    array_flip($restrict_search_to_userids)
                )
            );
        }

        return new UserCatalog($_COMPANY->id, $fields, $user_catalog_userids);
    }

    /**
     * This is a ZONE specific method so $_ZONE needs to be set.
     * Returns a catalog of users with the matching criteria in the given zone.
     * *** NOTE *** Only users in the current $_ZONE are returned.
     * @param string $category
     * @param int $comparator
     * @param array $keynames
     * @return UserCatalog|null
     */
     public static function GetZoneUserCatalogBySet (string $category, string $comparator, array $keynames): ?UserCatalog
     {
        global $_COMPANY, $_ZONE;

        $catalog_categoryid = self::GetCategoryIdByName($category, null);

        if (!in_array($comparator, array_keys(self::SET_TYPE_OPERATORS_MAP)))
            return null;

        $fields = array(
            'companyid' => $_COMPANY->id,
            'user_catalog_category' => $category,
            'user_catalog_keyname' => 'undefined',
            'createdon' => gmdate('Y-m-d H:m:s')
        );

        $user_catalog_userids  = [];

        if (!empty($category) && !empty($keynames)) {
            foreach ($keynames as $keyname) {
                $rows   = self::DBGetPS("SELECT `user_catalog_userids` FROM `user_catalogs` WHERE `companyid`=? AND `user_catalog_categoryid`=? AND `user_catalog_keyname`=?", 'iix', $_COMPANY->id(), $catalog_categoryid, $keyname);

                if (!empty($rows)) {
                    $row_userids = implode(',', array_column($rows,'user_catalog_userids'));
                    $user_catalog_userids = array_unique(array_merge($user_catalog_userids, explode(',', $row_userids)));
                }
            }
        }
        $user_rows = self::DBROGet("SELECT userid FROM users WHERE companyid={$_COMPANY->id()} AND FIND_IN_SET ({$_ZONE->id()}, zoneids)");
        $zone_userids = empty($user_rows) ? array() : array_column($user_rows,'userid');

        if ($comparator == self::SET_TYPE_OPERATOR__NOT_IN) { // NOT IN
            $user_catalog_userids = array_diff($zone_userids, $user_catalog_userids);
        } else { // IN
            $user_catalog_userids = array_intersect($zone_userids, $user_catalog_userids);
        }

        return new UserCatalog($_COMPANY->id, $fields, array_values($user_catalog_userids));
    }

    /**
     * @param string $category
     * @param int|User|null $user pass userid or User object; if null is passed an empty string is returned.
     * @return int|string
     */
    public static function GetCatalogKeynameForUser (string $category, int|User|null $user): int|string
    {
        $retVal = '';
        $keyType = 'string';
        if ($user) {
            $user = is_int($user) ? User::GetUser($user) : $user;
            $keyType = self::GetCategoryKeyType($category);
            $internalId = self::GetCategoryInternalId($category);
            $userProfile = $user ? User::DecryptProfile($user->val('extendedprofile')) : [];
            $profileKey = Str::GetStringAfterCharacter($internalId, '.');
            $retVal = $userProfile[$profileKey] ?? '';
        }
        return $keyType == 'int' ? intval($retVal) : strval($retVal);
    }

    public function intersect (UserCatalog $other) : UserCatalog
    {
        if ($this->cid != $other->cid) {
            return $this;
        }
        return new UserCatalog($this->cid,array(), array_intersect($this->user_catalog_userids, $other->user_catalog_userids));
    }

    public function union (UserCatalog $other) : UserCatalog
    {
        if ($this->cid != $other->cid) {
            return $this;
        }
        return new UserCatalog($this->cid,array(), array_unique(array_merge($this->user_catalog_userids, $other->user_catalog_userids)));
    }

    public function getUserIds(): array
    {
        return $this->user_catalog_userids;
    }

    public static function GetSurveyResponseByQuestionKey (string $questionKey,array $esponseArray){
        $response = null;
        if (array_key_exists($questionKey,$esponseArray)){
            $response = $esponseArray[$questionKey];
        }
        return  $response;
    }

    public static function GetSurveyResponseCatalog (string $questionKey, int $questionType, array $comparatorResponses, array $matchWithResponses, bool $reverseCondition = false, float $matchingParameterPercentage=0.00 )
    {
        global $_COMPANY;
        $survey_catalog_userids  = [];

        // When processing data for the search filters we need to skip empty values
        // For calculating percentage match we will be adding empty values valid users.
        $add_users_with_empty_values = ($matchingParameterPercentage) ? true : false;
        $comparatorResponse = self::GetSurveyResponseByQuestionKey($questionKey,$comparatorResponses);
        foreach($matchWithResponses as $key => $value){
            if (!empty($value)){
                $matchResponse = self::GetSurveyResponseByQuestionKey($questionKey,$value);
                if ($comparatorResponse && $matchResponse) {

                    if ($questionType == TEAM::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['MATCH']){
                        if ($comparatorResponse  == $matchResponse){
                            $survey_catalog_userids[$key] = $matchingParameterPercentage;
                        }
                    } elseif($questionType == TEAM::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['MATCH_N_NUMBERS']) {
                        // The following line is to recover from a situation where admin had changed a question from
                        // single input to multiple inputs.
                        if (is_string($matchResponse))
                            $matchResponse = [$matchResponse];
                        // End of the fix

                        if (is_string($comparatorResponse))
                            $comparatorResponse = [$comparatorResponse];

                        $intersect = array_intersect($comparatorResponse,$matchResponse);
                        if (!empty($intersect)){
                            if (count($comparatorResponse) == count($intersect)){
                                $survey_catalog_userids[$key] = $matchingParameterPercentage;
                            } else {
                                $diffPercentage = (count($comparatorResponse) - count($intersect)) * 100 / count($comparatorResponse);
                                $survey_catalog_userids[$key] = round(($matchingParameterPercentage * $diffPercentage / 100), 0);
                            }
                        }
                    } elseif($questionType == TEAM::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['GREATER_THAN']) {
                        if ($reverseCondition){
                            if ($comparatorResponse < $matchResponse){
                                $survey_catalog_userids[$key] = $matchingParameterPercentage;
                            }
                        } else{
                            if ($comparatorResponse > $matchResponse){
                                $survey_catalog_userids[$key] = $matchingParameterPercentage;
                            }
                        }
                    } elseif($questionType == TEAM::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['EQUAL_TO']) {
                        if ($comparatorResponse == $matchResponse){
                            $survey_catalog_userids[$key] = $matchingParameterPercentage;
                        }
                    } elseif($questionType == TEAM::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['LESS_THAN']) {
                        if ($reverseCondition){
                            if ($comparatorResponse > $matchResponse){
                                $survey_catalog_userids[$key] = $matchingParameterPercentage;
                            }
                        } else {
                            if ($comparatorResponse < $matchResponse){
                                $survey_catalog_userids[$key] = $matchingParameterPercentage;
                            }
                        }
                    } elseif($questionType == TEAM::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['NOT_EQUAL_TO']) {
                        if ($comparatorResponse != $matchResponse){
                            $survey_catalog_userids[$key] = $matchingParameterPercentage;
                        }
                    } elseif($questionType == TEAM::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['DONOT_MATCH']) {
                        if ($comparatorResponse  != $matchResponse){
                            $survey_catalog_userids[$key] = $matchingParameterPercentage;
                        }
                    } elseif($questionType == TEAM::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['IGNORE']) {
                        $survey_catalog_userids[$key] = $matchingParameterPercentage;
                    } elseif($questionType == TEAM::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['GREATER_THAN_OR_EQUAL_TO']) {
                        if ($reverseCondition){
                            if ($comparatorResponse <= $matchResponse){
                                $survey_catalog_userids[$key] = $matchingParameterPercentage;
                            }
                        } else {
                            if ($comparatorResponse >= $matchResponse){
                                $survey_catalog_userids[$key] = $matchingParameterPercentage;
                            }
                        }
                    } elseif($questionType == TEAM::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['LESS_THAN_OR_EQUAL_TO']) {
                        if ($reverseCondition){
                            if ($comparatorResponse >= $matchResponse){
                                $survey_catalog_userids[$key] = $matchingParameterPercentage;
                            }
                        } else {
                            if ($comparatorResponse <= $matchResponse){
                                $survey_catalog_userids[$key] = $matchingParameterPercentage;
                            }
                        }
                    } elseif($questionType == TEAM::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['WORD_MATCH']) {
                        $wordMatchPercentage = Str::WordMatchPercentageOfWordsInText($comparatorResponse, $matchResponse);
                        if ($wordMatchPercentage) {
                            $survey_catalog_userids[$key] = round(($matchingParameterPercentage * $wordMatchPercentage / 100), 0);
                        }
                    } elseif ($questionType == TEAM::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['RANGE_MATCH']) {
                        // In case of range match, we do not need to check for reverse condition as range is updated
                        // for reverse conditions by the calling function
                        $a = $matchResponse[0];
                        $b = $matchResponse[1];
                        $minimum = min($a, $b);
                        $maximum = max($a, $b);
                        if (!empty($matchResponse) && $comparatorResponse>= $minimum && $comparatorResponse <= $maximum) {
                            $survey_catalog_userids[$key] = $matchingParameterPercentage;
                        }
                    }
                } 
            } elseif ($add_users_with_empty_values) { // by pass
                $survey_catalog_userids[$key] = 0;
            }
        }
        return new UserCatalog($_COMPANY->id, array(), $survey_catalog_userids);
    }

    public static function UpdateZoneVisibility(int $catalog_id, string $zone_id_keys){
        global $_COMPANY;
        $zone_id_keys = !empty($zone_id_keys) ? implode(',', Arr::IntValues(explode(",", $zone_id_keys))) : '';
        $result =  self::DBUpdatePS("UPDATE `user_catalog_categories` SET `visible_in_zoneids`=? WHERE `user_catalog_categoryid` = ? AND `companyid` = ?", 'sii', $zone_id_keys, $catalog_id, $_COMPANY->id());

        // Expire cache
        $_COMPANY->expireRedisCache("UCC:{$_COMPANY->id()}");

        return $result;

    }
    public static function FetchAllStatistics(int $catalog_categoryid){
        global $_COMPANY;
        $rows = self::DBROGet("
            SELECT 
                ucc.user_catalog_category, 
                uc.user_catalog_keyname, 
                uc.source_id, 
                IF(LENGTH(uc.user_catalog_userids), (LENGTH(uc.user_catalog_userids) - LENGTH(REPLACE(uc.user_catalog_userids, ',', '')) + 1), 0) AS user_count, 
                uc.createdon 
            FROM `user_catalog_categories` AS ucc 
                JOIN `user_catalogs` AS uc USING (user_catalog_categoryid)
            WHERE uc.companyid={$_COMPANY->id()} AND user_catalog_categoryid={$catalog_categoryid}
        ");
        return $rows;
    }


    
}
