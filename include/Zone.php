<?php

class Zone extends Teleskope
{
    use CacheableTrait;

    public function __construct($id, $cid, $fields)
    {
        parent::__construct($id, $cid, $fields);
    }

    public function getZoneCustomization(): array
    {
        return $this->val('customization');
    }

    private function applyAppRestrictions(array &$customization): void
    {
        $app_type = $this->val('app_type');
        switch ($this->val('app_type')) {

            case 'affinities' :
                break;

            case 'officeraven' :
                $customization['app']['teams']['enabled'] = false;
                break;

            case 'talentpeak' :
                $customization['app']['budgets']['enabled'] = false;
                break;

            case 'peoplehero' :
                $customization['app']['budgets']['enabled'] = false;
                break;
        }

        if (!($customization['app']['budgets']['enabled'] ?? false)) {
            $customization['app']['event']['budgets'] = false;
        }
    }

    public function updateZoneCustomization(array $customization): int
    {
        global $_COMPANY;

        $this->applyAppRestrictions($customization);
        $template = self::GetZoneSettingsTemplate($this->val('app_type'));
        $original_customization = Arr::Minify($this->val('customization'), $template);
        $updated_customization = Arr::Minify($customization, $template);

        $retVal = self::DBUpdatePS("UPDATE `company_zones` SET `customization`=? WHERE companyid=? AND `zoneid`=? AND `modifiedon`<=?", 'xiix', json_encode($updated_customization, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $_COMPANY->id(), $this->id(), $this->val('modifiedon'));
        if ($retVal) {
            Logger::AuditLog("config_change: original zone config ", $original_customization);
            Logger::AuditLog("config_change: new zone config ", $updated_customization);
        }

        Company::GetCompany($_COMPANY->id(), true);

        return $retVal;
    }

    /**
     * This method is different from updateZoneCustomization as it only updates the provided key val pairs vs
     * updateZoneCustomization which updates the entire configuration.
     * @param array $pairs
     * @return int
     */
    public function updateZoneCustomizationKeyVal(array $pairs): int
    {
        $pairs = array_replace_recursive($this->val('customization'), $pairs);
        // Send the modifiedon arbitarily to 10 minute ahead to force the update by updateZoneCustomization which compares dates
        $this->fields['modifiedon'] = date('Y-m-d H:i:s', strtotime("{$this->val('modifiedon')} + 10 minute"));
        return $this->updateZoneCustomization($pairs);
    }

    public static function GetZoneSettingsTemplate(string $app_type): array
    {
        if ($app_type === 'officeraven') {
            $template = Company::GetDefaultSettingsForOfficeRaven();
        } elseif ($app_type === 'talentpeak') {
            $template = Company::GetDefaultSettingsForTalentPeak();
        } elseif ($app_type === 'peoplehero') {
            $template = Company::GetDefaultSettingsForPeopleHero();
        } else {
            $template = Company::GetDefaultSettingsForAffinities();
        }
        return $template;
    }

    public function getAllMembersCount()
    {
        $member_count_array = [];
        $groups = self::DBROGet("SELECT IFNULL(GROUP_CONCAT(`groupid`),0) AS groupIds FROM `groups` WHERE `zoneid`= {$this->id}");
        $members = self::DBROGet("SELECT COUNT(1) AS user_members_total, COUNT(DISTINCT userid) AS user_members_unique FROM groupmembers JOIN users USING (userid) WHERE groupid IN ({$groups[0]['groupIds']}) AND users.isactive=1 AND groupmembers.isactive=1");
        $member_count_array['user_members_unique'] = $members[0]['user_members_unique'] ?? 0;
        $member_count_array['user_members_total'] = $members[0]['user_members_total'] ?? 0;

        $member_counts = self::DBROGet("SELECT SUM(group_1) AS sgroup_1, SUM(group_2) AS sgroup_2, SUM(group_3) AS sgroup_3 FROM (SELECT SUM(if(agg1.no_of_groups = 1, 1, 0)) AS group_1, SUM(if(agg1.no_of_groups = 2, 1, 0)) AS group_2, SUM(if(agg1.no_of_groups > 2, 1, 0)) AS group_3 FROM (SELECT COUNT(1) AS no_of_groups FROM groupmembers JOIN users USING (userid) WHERE groupid IN({$groups[0]['groupIds']}) AND users.isactive=1 AND groupmembers.isactive=1 GROUP BY groupmembers.`userid`) agg1 GROUP BY agg1.no_of_groups) agg2");
        $member_count_array['user_members_1group'] = intval($member_counts[0]['sgroup_1'] ?? 0);
        $member_count_array['user_members_2group'] = intval($member_counts[0]['sgroup_2'] ?? 0);
        $member_count_array['user_members_3group'] = intval($member_counts[0]['sgroup_3'] ?? 0);

        return $member_count_array;
    }

    public function deleteIt(): bool
    {
        global $_COMPANY;

        if ($this->getWhyCannotDeleteIt()) {
            return false;
        }

        self::LogObjectLifecycleAudit('delete', 'zone', $this->id(), 0);

        self::DBMutate("DELETE FROM `company_zones` WHERE `companyid` = {$_COMPANY->id()} AND `zoneid` = {$this->id()}");

        return true;
    }

    public function getWhyCannotDeleteIt(): string
    {
        global $_COMPANY, $_ZONE;

        if (!Env::IsSuperAdminDashboard()) {
            return gettext('Access Denied');
        }

        $groups = Group::GetAllGroupsByCompanyid($_COMPANY->id(), $this->id(), false);

        if (count($groups)) {
            return gettext('Please delete all groups in this zone first');
        }

        // @Todo We need to implement deep deletion of company until then we will deliberately fail deletion
        // see delete_company.sql for deep deletion for zone specific data
        return ('Deep deletion is pending');

        //return '';
    }

    /**
     * @deprecated This method is for restricted use in Super Admin functions only
     * @param int $zoneid
     * @return Zone|null
     */
    public static function GetZone(int $zoneid): ?Zone
    {
        global $_COMPANY;

        $zone = self::DBROGet("SELECT * FROM `company_zones` WHERE `companyid` = {$_COMPANY->id()} AND `zoneid` = {$zoneid}");

        if (empty($zone)) {
            return null;
        }
        $zone = $zone[0];

        $minified_zone_customization = $zone['customization'] ? json_decode($zone['customization'], true) : array();
        $zone_settings_template = Zone::GetZoneSettingsTemplate($zone['app_type']);
        // Step 1 - first add any company customization to zone customiztion template
        // Note any customization set at the company level will override the corresponding key set at the zone level default
        $minified_company_customization = (json_decode($_COMPANY->val('customization') ?? '', true)) ?: [];
        $company_settings_with_company_block = Arr::Unminify($minified_company_customization, Company::DEFAULT_COMPANY_SETTINGS);
        unset($company_settings_with_company_block['company']);
        $zone_settings_template_with_company_customization = Arr::Unminify($company_settings_with_company_block, $zone_settings_template);
        // Step 2- updated zone customization template with zone customization
        // Note any customization set at the zone level will override the corresponding key set at the company level or zone default
        $zone['customization'] = Arr::Unminify($minified_zone_customization, $zone_settings_template_with_company_customization);

        return new Zone($zoneid, $_COMPANY->id(), $zone);
    }

    public static function GetTopicType(): string
    {
        return self::TOPIC_TYPES['ZONE'];
    }

    public function isConnectFeatureEnabled()
    {
        global $_COMPANY;
        return $_COMPANY->isConnectEnabled() && $_COMPANY->getCompanyLoginMethodOfConnectType($this->val('app_type'));
    }


    /**
     * @param string $configuration_key , the configuration key can be in dot notation.
     * @return mixed
     */
    public function getZoneAttributesKeyVal(string $configuration_key): mixed
    {
        if (!$configuration_key)
            return null;

        $configuration_key_arr = explode('.', $configuration_key);
        if (empty($configuration_key))
            return null;

        $attributes = Arr::Json2Array($this->val('attributes'));

        foreach ($configuration_key_arr as $configuration_key_item) {
            if ($attributes && isset($attributes[$configuration_key_item])) {
                $attributes = $attributes[$configuration_key_item];
            } else {
                $attributes = null;
                break;
            }
        }

        return $attributes;
    }

    /**
     * Sets the key in attributes JSON column. The key can be in the dot notation, e.g. emails.approvals
     * ***** If $configuration_val is null, the the value is removed if it exists *****
     * @param string $configuration_key
     * @param mixed $configuration_val
     * @return int
     */
    public function updateZoneAttributesKeyVal(string $configuration_key, mixed $configuration_val): int
    {
        global $_COMPANY, $_USER;
        $retVal = 0;

        // First construct a $configuration_array
        $configuration_array = array($configuration_key => $configuration_val);
        $json_doc = json_encode(Arr::Undot($configuration_array)); // $configuration_key may be in dot notation
        //$sql = "UPDATE configuration SET keyvals = JSON_MERGE_PATCH(keyvals, '{$." . implode(".$", $keys) . "': $value}')";
        $json_doc = json_encode(Arr::Undot(array($configuration_key => $configuration_val)));
        $retVal = self::DBMutatePS("
            UPDATE `company_zones` 
            SET attributes=JSON_MERGE_PATCH(IFNULL(attributes,JSON_OBJECT()), ?), modifiedon=NOW()
            WHERE companyid=? AND zoneid=?",
            'xii',
            $json_doc, $_COMPANY->id(), $this->id()
        );

        if ($retVal) {
            Company::GetCompany($_COMPANY->id(), true); // Reload cache
            self::LogObjectLifecycleAudit('update', 'zone', $this->id(), 0, $configuration_array);
        }

        return $retVal;
    }

}