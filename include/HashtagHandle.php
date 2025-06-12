<?php

class HashtagHandle extends Teleskope
{
    public static function SanitizeHandle(string $handle)
    {
        return preg_replace(array('/[^a-zA-Z0-9_]/', '/^[_]*/'), '', $handle);
    }

    private static function GetHandleRedisKey(string $handle)
    {
        global $_ZONE;
        $firstCharOfHandleLC = strtolower($handle[0]);
        return 'HSH_'.$_ZONE->id().':'. $firstCharOfHandleLC.'__';
    }

    private static function GetRecsFromRedisCache (string $handle) {
        global $_COMPANY, $_ZONE;
        $key = self::GetHandleRedisKey($handle);
        $firstCharOfHandleLC = strtolower($handle[0]);
        if (($rows = $_COMPANY->getFromRedisCache($key)) === false) {
            $rows = self::DBGet("SELECT hashtagid, handle FROM handle_hashtags WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND handle like '{$firstCharOfHandleLC}%'");
            $_COMPANY->putInRedisCache($key, $rows, 86400); // $obj can be empty or null
        }
        return $rows;
    }

    public static function GetHandlesObject(string $handle)
    {
        global $_COMPANY, $_ZONE;

        $origHandle = $handle;
        $handle = self::SanitizeHandle($handle);
        if (empty($handle)) {
            return array();
        }

        $rows = self::GetRecsFromRedisCache($handle);

        $data = array();
        if (strlen($handle) > 2) {
            $data[$origHandle] = array(
                "item" => '<span style="color:#bbbbbb;">add new tag:</span> '.$handle,
                "replacement" => "<a href='{$_COMPANY->getAppURL($_ZONE->val('app_type'))}hashtag?handle={$handle}'>#{$handle}</a>"
            );
        }

        $count = 0;
        foreach ($rows as $row) {
            if (stripos($row['handle'],$handle) !== 0) continue;
            $data[$row['handle']] = array(
                "item" => $row['handle'],
                "replacement" => "<a href='{$_COMPANY->getAppURL($_ZONE->val('app_type'))}hashtag?handle={$row['handle']}'>#{$row['handle']}</a>"
            );
            if ($count++ > 9) break;
        }

        return $data;
    }

    /**
     * Returns handle db row array
     * @param string $handle
     * @return array empty array if handle was not found.
     */
    public static function GetHandle(string $handle)
    {
        global $_COMPANY, $_ZONE;
        $handle = self::SanitizeHandle($handle);
        if (empty($handle) || strlen($handle) < 2) {
            return array();
        }

        $rows = self::GetRecsFromRedisCache($handle);
        $hid = Arr::SearchColumnReturnRow($rows, $handle, 'handle');
        return $hid;
    }

    private static function CreateHandle(string $handle)
    {
        global $_COMPANY, $_ZONE;

        if (empty($handle))
            return;

        $handle = self::SanitizeHandle($handle);

        # Create new handle using DBMutate as we do not want the script to die in case of error
        $result = self::DBMutatePS("INSERT INTO handle_hashtags(companyid, zoneid, handle) VALUES (?,?,?)", 'iix', $_COMPANY->id(), $_ZONE->id(), $handle);
        if ($result) {
            $_COMPANY->expireRedisCache(self::GetHandleRedisKey($handle));
        }
        return $result;
    }

    private static function UpdateHandle(int $hashtagid, string $handle)
    {
        global $_COMPANY, $_ZONE;
        $handle = self::SanitizeHandle($handle);

        # Create new handle using DBMutate as we do not want the script to die in case of error
        $result = self::DBMutatePS("UPDATE handle_hashtags SET handle=? WHERE companyid=? AND zoneid=? AND hashtagid=?", 'xiii', $handle, $_COMPANY->id(), $_ZONE->id(),$hashtagid );
        if ($result) {
            $_COMPANY->expireRedisCache(self::GetHandleRedisKey($handle));
        }
        return $result;
    }

    public static function GetOrCreateHandle (string $handle)
    {
        $handleRow = self::GetHandle($handle);
        if (empty($handleRow) && !empty($id = self::CreateHandle($handle))) {
            $handleRow = self::GetHandle($handle);
        }
        return $handleRow;
    }

    public static function ExtractAndCreateHandles(string $content) {
        global $_COMPANY, $_ZONE;
        preg_match_all('/hashtag\?handle=[a-zA-Z0-9_]{3,}"/', $content, $matches);
        $startCutoff = strlen('hashtag?handle=');
        $handleIds = array();
        if (!empty($matches) && !empty($matches[0])) {
            $matches = array_unique($matches[0]);
            foreach ($matches as $match) {
                // Extract the tag
                $tag = substr($match, $startCutoff);
                $tag = substr($tag, 0, -1); // Remove last quote;
                $handle = self::GetOrCreateHandle($tag);
                $handleIds[] = $handle['hashtagid'];
            }
        }
        return $handleIds;
    }

    public static function GetHandlesByIds (string $handleIds = '') {
        global $_COMPANY, $_ZONE;
        $condition = "";
        if (empty($handleIds)) {
            return [];
        }
        return self::DBGetPS("SELECT hashtagid, handle FROM handle_hashtags WHERE companyid=? AND zoneid=? AND FIND_IN_SET(hashtagid,?)",'iix',$_COMPANY->id(),$_ZONE->id(),$handleIds);
    }



    public static function GetAllHashTagHandles (string $handleIds = '' ) {
        global $_COMPANY, $_ZONE;
        $condition = "";
        $handleIds =  Sanitizer::SanitizeIntegerCSV($handleIds);
        if (!empty($handleIds)){
            $condition = "AND FIND_IN_SET(hashtagid,'".$handleIds."')";
        }
        $rows =  self::DBGet("SELECT hashtagid, handle FROM handle_hashtags WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} {$condition}");
        usort($rows, function($a, $b) {
            return strtolower($a['handle']) <=> strtolower($b['handle']);
        });
        return $rows;
    }


    public static function GetHashTagHandleById (int $hashtagid) {
        global $_COMPANY, $_ZONE;
        $row =  self::DBGet("SELECT hashtagid, handle FROM handle_hashtags WHERE companyid='{$_COMPANY->id()}' AND zoneid='{$_ZONE->id()}' AND hashtagid='{$hashtagid}'");
        if (!empty($row)) {
            return $row[0];
        }
        return null;
    }

    public static function AddOrUpdateHashtag(int $hashtagid, string $handle) {
        if ($hashtagid) {
            self::UpdateHandle($hashtagid, $handle);
        } else {
            self::CreateHandle($handle);
        }
    }

    public static function CheckHashTagIsUnique(int $hashtagid, string $handle) {
        $isUnique = true;

        if ( $hashtagid) {
            $row = self::GetHandle($handle);
            if ($row && $row['hashtagid']!=$hashtagid) {
                $isUnique = false;
            }

        }  else {
            if(!empty(self::GetHandle($handle))) {
                $isUnique = false;
            }
        }
        return $isUnique;
    }
}