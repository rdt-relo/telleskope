<?php

class CompanyAdmin extends Teleskope
{
    public static function GetUserGroupsAsGroupLead(int $userId): array
    {
        global $_COMPANY;
        global $_ZONE;

        $sql = '
            SELECT
                        `groups`.`groupid`,
                        `grouplead_typeid` AS `group_lead_type_id`,
                        DATE(`assigneddate`) AS `group_joining_date`
            FROM        `groupleads`
            INNER JOIN  `groups`
            ON          `groups`.`groupid` = `groupleads`.`groupid`
            WHERE       `userid` = ?
            AND         `groups`.`companyid` = ?
            AND         `groups`.`zoneid` = ?
            AND         `groupleads`.`isactive` = 1
        ';

        return self::DBROGetPS(
            $sql,
            'iii',
            $userId,
            $_COMPANY->id(),
            $_ZONE->id()
        );
    }

    public static function GetUserGroupsAsMember(int $userId): array
    {
        global $_COMPANY;
        global $_ZONE;

        $groupsAsGroupLead = static::GetUserGroupsAsGroupLead($userId);
        $groupIdsAsGroupLead = array_column($groupsAsGroupLead, 'groupid');

        $sql = '
            SELECT
                        `groups`.`groupid`,
                        DATE(`groupmembers`.`groupjoindate`) AS `group_joining_date`
            FROM        `groupmembers`
            INNER JOIN  `groups`
            ON          `groupmembers`.`groupid` = `groups`.`groupid`
            WHERE       `groupmembers`.`userid` = ?
            AND         `groups`.`companyid` = ?
            AND         `groups`.`zoneid` = ?
            AND         `groupmembers`.`isactive` = 1
        ';

        $groupsAsMember = self::DBROGetPS(
            $sql,
            'iii',
            $userId,
            $_COMPANY->id(),
            $_ZONE->id()
        );

        return array_values(
            array_filter($groupsAsMember, function ($group) use ($groupIdsAsGroupLead) {
                return !in_array($group['groupid'], $groupIdsAsGroupLead);
            })
        );
    }
}
