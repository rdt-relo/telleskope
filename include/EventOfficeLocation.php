<?php

class EventOfficeLocation extends Teleskope
{
    private const REDIS_CACHE_KEY = 'EVENT_OFFICE_LOCATIONS:ACTIVE_LOCATIONS';

    public static function All(bool $get_active_records_only = true): array
    {
        global $_COMPANY, $_ZONE;

        $use_redis_cache = $get_active_records_only;

        if ($use_redis_cache) {
            $office_locations = $_ZONE->getFromRedisCache(self::REDIS_CACHE_KEY);

            if ($office_locations !== false) {
                return $office_locations;
            }
        }

        $sql = "
            SELECT      *
            FROM        `event_office_locations`
            WHERE       `companyid` = ?
            AND         `zoneid` = ?
        " .
            ($get_active_records_only ? 'AND `isactive` = 1': '')
        . "
            ORDER BY    `event_office_location_id` DESC
        ";

        // In order to avoid race conditions, get from Master DB as this method is used for fetching records right
        // after adding/updating them
        $office_locations = self::DBGetPS(
            $sql,
            'ii',
            $_COMPANY->id(),
            $_ZONE->id()
        );

        $office_locations = array_map(function (array $office_location) {
            return EventOfficeLocation::Hydrate($office_location['event_office_location_id'], $office_location);
        }, $office_locations);

        if ($use_redis_cache) {
            $_ZONE->putInRedisCache(self::REDIS_CACHE_KEY, $office_locations, 3600);
        }

        return $office_locations;
    }

    public static function GetEventOfficeLocation(int $event_office_location_id): ?EventOfficeLocation
    {
        global $_COMPANY;
        global $_ZONE;

        $sql = 'SELECT * FROM `event_office_locations` WHERE `event_office_location_id` = ? AND `companyid` = ? AND `zoneid` = ?';

        $result = self::DBROGetPS(
            $sql,
            'iii',
            $event_office_location_id,
            $_COMPANY->id(),
            $_ZONE->id()
        );

        if (empty($result)) {
            return null;
        }

        return new EventOfficeLocation($event_office_location_id, $_COMPANY->id, $result[0]);
    }

    public static function CreateNewEventOfficeLocation(string $location_name, string $location_address): int
    {
        global $_COMPANY, $_ZONE, $_USER;

        $sql = '
            INSERT INTO `event_office_locations` (
                `companyid`,
                `zoneid`,
                `location_name`,
                `location_address`,
                `createdby`,
                `modifiedby`
            ) VALUES (
                ?,?,?,?,?,?
            )
        ';

        $retval = self::DBInsertPS(
            $sql,
            'iissii',
            $_COMPANY->id(),
            $_ZONE->id(),
            $location_name,
            $location_address,
            $_USER->id(),
            $_USER->id()
        );

        $_ZONE->expireRedisCache(self::REDIS_CACHE_KEY);

        return $retval;
    }

    public function updateEventOfficeLocation(string $location_name, string $location_address): int
    {
        global $_COMPANY, $_ZONE, $_USER;

        $sql = '
            UPDATE  `event_office_locations`
            SET     `location_name` = ?,
                    `location_address` = ?,
                    `modifiedby` = ?,
                    `modifiedon` = NOW()
            WHERE   `event_office_location_id` = ?
            AND     `companyid` = ?
            AND     `zoneid` = ?
        ';

        $retval = self::DBUpdatePS(
            $sql,
            'ssiiii',
            $location_name,
            $location_address,
            $_USER->id(),
            $this->id(),
            $_COMPANY->id(),
            $_ZONE->id()
        );

        $_ZONE->expireRedisCache(self::REDIS_CACHE_KEY);

        return $retval;
    }

    public function updateEventOfficeLocationStatus(int $status): int
    {
        global $_COMPANY, $_ZONE, $_USER;

        $sql = "
            UPDATE  `event_office_locations`
            SET     `isactive` = {$status},
                    `modifiedby` = {$_USER->id()},
                    `modifiedon` = NOW()
            WHERE   `event_office_location_id` = {$this->id()}
            AND     `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
        ";

        $retval = self::DBUpdate($sql);

        $_ZONE->expireRedisCache(self::REDIS_CACHE_KEY);

        return $retval;
    }
}
