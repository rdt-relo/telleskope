<?php

class BlockedKeyword extends Teleskope
{
    private const REDIS_CACHE_KEY = 'CONTENT_MODERATOR:BLOCKED_KEYWORDS';

    public static function GetBlockedKeyword(int $id): ?BlockedKeyword
    {
        global $_COMPANY, $_ZONE;

        $keyword = self::DBROGet("
            SELECT  *
            FROM    `blocked_keywords`
            WHERE   `blocked_keyword_id` = {$id}
            AND     `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
        ");

        if (empty($keyword)) {
            return null;
        }

        return BlockedKeyword::Hydrate($id, $keyword[0]);
    }

    public static function GetBlockedKeywordByKeyword(string $blocked_keyword): ?BlockedKeyword
    {
        global $_COMPANY, $_ZONE;

        $keyword = self::DBROGetPS("
            SELECT  *
            FROM    `blocked_keywords`
            WHERE   `blocked_keyword` = ?
            AND     `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
            ",
            's',
            $blocked_keyword
        );

        if (empty($keyword)) {
            return null;
        }

        return BlockedKeyword::Hydrate($keyword[0]['blocked_keyword_id'], $keyword[0]);
    }

    public static function CreateNewBlockedKeyword(string $blocked_keyword): int
    {
        global $_COMPANY, $_ZONE;

        $blocked_keyword = mb_strtolower($blocked_keyword);

        $keyword = self::GetBlockedKeywordByKeyword($blocked_keyword);

        if ($keyword) {
            AjaxResponse::SuccessAndExit_STRING(
                0,
                '',
                gettext('This keyword is already blocked'),
                gettext('Error')
            );
        }

        $retval = self::DBInsertPS('
            INSERT INTO `blocked_keywords` (
                `companyid`,
                `zoneid`,
                `blocked_keyword`
            )
            VALUES (?,?,?)',
            'iix',
            $_COMPANY->id(),
            $_ZONE->id(),
            $blocked_keyword
        );

        $_ZONE->expireRedisCache(self::REDIS_CACHE_KEY);

        return $retval;
    }

    public static function GetAllBlockedKeywords(): array
    {
        global $_COMPANY, $_ZONE;

        $blocked_keywords = $_ZONE->getFromRedisCache(self::REDIS_CACHE_KEY);

        if ($blocked_keywords !== false) {
            return $blocked_keywords;
        }

        $keywords = self::DBROGet("
            SELECT  `blocked_keyword_id`, `blocked_keyword`
            FROM    `blocked_keywords`
            WHERE   `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
            ORDER BY `blocked_keyword` ASC
        ");

        $blocked_keywords = array_map(function (array $keyword) {
            return BlockedKeyword::Hydrate($keyword['blocked_keyword_id'], $keyword);
        }, $keywords);

        $_ZONE->putInRedisCache(self::REDIS_CACHE_KEY, $blocked_keywords, 3600);

        return $blocked_keywords;
    }

    public function deleteIt(): int
    {
        global $_COMPANY, $_ZONE;

        $retval = self::DBMutate("
            DELETE
            FROM    `blocked_keywords`
            WHERE   `blocked_keyword_id` = {$this->id()}
            AND     `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
        ");

        $_ZONE->expireRedisCache(self::REDIS_CACHE_KEY);

        return $retval;
    }

    public function updateBlockedKeyword(string $blocked_keyword): int
    {
        global $_COMPANY, $_ZONE;

        $blocked_keyword = mb_strtolower($blocked_keyword);

        $keyword = self::GetBlockedKeywordByKeyword($blocked_keyword);

        if ($keyword && $keyword->id() != $this->id()) {
            AjaxResponse::SuccessAndExit_STRING(
                0,
                '',
                gettext('This keyword is already blocked'),
                gettext('Error')
            );
        }

        $retval = self::DBUpdatePS("
            UPDATE  `blocked_keywords`
            SET     `blocked_keyword` = ?
            WHERE   `blocked_keyword_id` = ?
            AND     `companyid` = ?
            AND     `zoneid` = ?",
            'xiii',
            $blocked_keyword,
            $this->id(),
            $_COMPANY->id(),
            $_ZONE->id()
        );

        $_ZONE->expireRedisCache(self::REDIS_CACHE_KEY);

        return $retval;
    }
}
