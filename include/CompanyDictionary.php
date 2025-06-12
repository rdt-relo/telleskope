<?php

class CompanyDictionary extends Teleskope
{
    public static function GetCompanyIdBySubdomain (string $subdomain) {
        return self::GetCompanyDictionary()[$subdomain] ?? 0;
    }

    public static function GetCompanyDictionary(bool $force_load = false)
    {
        $obj = null;
        $cachekey = sprintf(".CompanyDictionary");
        // First look in the cache and validate cache has not expired (300 seconds)
        if ($force_load || ($obj = self::CacheGet($cachekey)) === null || (time() - $obj['___timestamp___']) > 300) {
            $rows = self::DBROGet("SELECT companyid,subdomain FROM companies WHERE isactive=1");
            $fields = array();
            foreach ($rows as $row) {
                $fields[$row['subdomain']] = $row['companyid'];
            }
            $fields['___timestamp___'] = time();
            self::CacheSet($cachekey, $fields); // Note we are persisting array
            $obj = self::CacheGet($cachekey);
        }

        return $obj;
    }
}