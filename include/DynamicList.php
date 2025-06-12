<?php

// Do no use require_once as this class is included in Company.php.

class DynamicList extends Teleskope
{
    private const COLUMN_ATTRIBUTES_CRITERIA = 'CRITERIA';

	protected function __construct($id,$cid,$fields)
    {
        $chapterList = NULL;
        parent::__construct($id, $cid, $fields);
        //declaring it protected so that no one can create it outside this class.
    }

    /**
     * @return array returns all list rows in the zone
     */
    public static function GetAllListRows() :array
    {
        $rows = array();
        global $_COMPANY,$_ZONE;
        $key = "LSTZ:{$_ZONE->id()}";
        if (($rows = $_COMPANY->getFromRedisCache($key)) === false) {
            $rows = self::DBGet("SELECT * FROM dynamic_lists WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()}");
            $_COMPANY->putInRedisCache($key, $rows, 3600);
        }
        return $rows;
    }

    /**
     * Returns `lists` in a company with zone filter
     * @return array, a list of `lists`in the company
     */
    public static function GetAllLists (string $scope = '', bool $fetchActiveOnly = false) : array
    {
        global $_COMPANY,$_ZONE;

        $objs = array();

        foreach (self::GetAllListRows() as $row) {
            if ($fetchActiveOnly && $row['isactive'] != 1) {
                continue;
            }

            if ($scope == 'zone' && !in_array($row['scope'], ['zone','zone_and_group'])) {
                continue;
            }

            if ($scope == 'group' && !in_array($row['scope'], ['group','zone_and_group'])) {
                continue;
            }
            $objs[] = new DynamicList($row['listid'], $_COMPANY->id(), $row);
        }

        return $objs;
    }

    /**
     * @param int $id , list id to be loaded
     * @return DynamicList|null
     */
    public static function GetList(int $id) : ?DynamicList
    {
        foreach (self::GetAllListRows() as $row) {
            if ($row['listid'] == $id) {
                return new DynamicList($row['listid'], $row['companyid'], $row);
            }
        }
        return null;
    }

    /**
     * @param int $id if set dynamic list will be updated
     * @param string $scope either zone or group;
     * @param string $listName name of the list
     * @param string $listDescription describes the purpose of the list
     * @param array $criteria defines how the list members are computed, it is an associative array of 'catalog_name'
     * and 'criteria'. Criteria is an associative array of comparator and value(s)
     * @return int|string
     */
    public static function AddUpdateList(int $id, string $scope, string $listName, string $listDescription, array $criteria)
    {
        global $_COMPANY,$_ZONE,$_USER;

        if (!in_array($scope, ['zone','group','zone_and_group'])) {
            $scope = 'zone';
        }

        $attributes = [];
        $attributes[self::COLUMN_ATTRIBUTES_CRITERIA] = $criteria;
        $attributes_json = json_encode($attributes);

        if ($id) {
            $retVal =  self::DBUpdatePS("UPDATE dynamic_lists SET `scope`=?,`list_name`=?,`list_description`=?,`attributes`=?,`modifiedby`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `zoneid`=? AND `listid`=?",'xxmxiiii',$scope,$listName, $listDescription,$attributes_json,$_USER->id(),$_COMPANY->id(),$_ZONE->id(),$id);
        } else {
            // $isactive = $activateDynamicList ? 1 : 2;
            $retVal = self::DBInsertPS("INSERT INTO dynamic_lists(`companyid`, `zoneid`, `scope`, `list_name`, `list_description`, `attributes`, `createdby`, `createdon`, `modifiedby`, `modifiedon`) VALUES (?,?,?,?,?,?,?,NOW(),?,NOW())",'iixxmxii',$_COMPANY->id(),$_ZONE->id(),$scope,$listName,$listDescription,$attributes_json,$_USER->id(),$_USER->id());
        }

        $_COMPANY->expireRedisCache("LSTZ:{$_ZONE->id()}");

        return $retVal;
    }

    /**
     * Returns criteria array of empty array.
     * @return array
     */
    public function getCriteria() : array
    {
        $attributes =  json_decode($this->val('attributes'), true);
        return $attributes[self::COLUMN_ATTRIBUTES_CRITERIA] ?: [];
    }

    public function delete()
    {
        global $_COMPANY,$_ZONE;
        $retVal =  self::DBUpdate("DELETE FROM dynamic_lists WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `listid`={$this->id()} AND `isactive`=2");
        $_COMPANY->expireRedisCache("LSTZ:{$_ZONE->id()}");
        return $retVal;
    }
    public function activate(){
        global $_COMPANY,$_ZONE, $_USER;
        $retVal =  self::DBUpdate("UPDATE dynamic_lists SET isactive=1,`modifiedby`={$_USER->id()},`modifiedon`=NOW() WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `listid`={$this->id()}");
        $_COMPANY->expireRedisCache("LSTZ:{$_ZONE->id()}");
        return $retVal;
    }
    public function deactivate()
    {
        global $_COMPANY,$_ZONE, $_USER;
        $retVal =  self::DBUpdatePS("UPDATE dynamic_lists SET isactive=0,`modifiedby`={$_USER->id()},`modifiedon`=NOW() WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `listid`={$this->id()}");
        $_COMPANY->expireRedisCache("LSTZ:{$_ZONE->id()}");
    }

    /**
     * @param string $listids
     * @param bool $return_array, default is false. set it to true if you want the results to be returned as array
     * @return array|string
     */
    public static function GetFormatedListNameByListids(string $listids = '0', bool $return_array = false)
    {
        global $_COMPANY,$_ZONE, $_USER;

        $listids = Sanitizer::SanitizeIntegerCSV($listids);
        if (empty($listids)){
            return '';
        }

        //$rows = self::DBROGet("SELECT IF(isactive=1, list_name, concat(list_name,'(inactive)')) as list_name_with_status FROM dynamic_lists WHERE `companyid`={$_COMPANY->id()} AND `listid` IN ({$listids})");
        //return Arr::NaturalLanguageJoin(array_column($rows, 'list_name_with_status'), '&');

        $rows = self::DBROGet("SELECT list_name FROM dynamic_lists WHERE `companyid`={$_COMPANY->id()} AND `listid` IN ({$listids})");
        $list_names = array_column($rows, 'list_name');
        if($return_array) {
            return $list_names;
        }
        return Arr::NaturalLanguageJoin($list_names, '&');
    }

    public function listInvalidCatalogs(): array
    {
        $invalidCatalogs = [];
        $zoneid = intval($this->val('zoneid'));
        foreach($this->getCriteria() as $catalog_category => $v) {
            $catalog_categoryid = UserCatalog::GetCategoryIdByName($catalog_category, $zoneid);
            if (!$catalog_categoryid){
                $invalidCatalogs[] = $catalog_category;
            }
        }
        return $invalidCatalogs;
    }

    public function getUserIds() : array
    {
        $zoneid = intval($this->val('zoneid'));
        $uc = null;
        foreach($this->getCriteria() as $catalog_category => $v) {

            # *** Important *** Search for categoryid with zone
            $catalog_categoryid = UserCatalog::GetCategoryIdByName($catalog_category, $zoneid);
            if (empty($catalog_categoryid)) {
                #there is a reference to a catalog that either does not exist or it is not visible in the zone
                #processing a dynamic list that has invalid catalogs will result in providing excessive userids
                Logger::Log("Invalid catalog '{$catalog_category}' refered in the dynamic list. Probably causes: (1) The catalog has been removed from the system, but it is still referred in the dynamic list, (2) The catalog visibilty in the current zone has changed.");
                die('Invalid catalog reference');
            }

            if (!empty($v['comparator'])){
                $uc2 = UserCatalog::GetZoneUserCatalogBySet($catalog_category, $v['comparator'], $v['keys']);
                if ($uc2) {
                    $uc = $uc ? $uc->intersect($uc2) : $uc2;
                }
            }
        }
        return $uc?->getUserIds() ?: array();
    }

    /**
     * @param string $listids
     * @return array
     */
    public static function GetUserIdsByListIds(string $listids) : array
    {
        $final_user_list = array();

        $listidsArray  = explode(',',$listids);
        foreach($listidsArray as $listid){
            $list = DynamicList::GetList($listid);
            if ($list) {
                $uc1 = $list->getUserIds();
                $final_user_list = array_unique(array_merge($uc1, $final_user_list));
            }
        }
        return $final_user_list;
    }

    public function isGroupScope() : bool
    {
        return in_array($this->val('scope'), ['group','zone_and_group']);
    }
    public function isZoneScope() : bool
    {
        return in_array($this->val('scope'), ['zone','zone_and_group']);
    }

    public function getScopeString() : string
    {
        $scope = array();
        if ($this->isZoneScope()) $scope[] = 'Zone';
        if ($this->isGroupScope()) $scope[] = 'Group';
        return Arr::NaturalLanguageJoin($scope, '&');
    }
}
