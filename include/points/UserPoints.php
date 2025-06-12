<?php

class UserPoints extends Teleskope
{
    public static function PointsBalanceForUser(int $userid): int
    {
        global $_COMPANY;

        if (!$_COMPANY->getAppCustomization()['points']['enabled'])
            return 0;

        $userPoints = self::DBROGet("SELECT `points_balance` FROM `user_points` WHERE `user_id` = {$userid}");

        return array_sum(array_column($userPoints, 'points_balance'));
    }

    public static function Get(int $userid, int $pointsProgramId): array
    {
        $userPoints = self::DBROGet("SELECT * FROM `user_points` WHERE `user_id` = {$userid} AND `points_program_id` = {$pointsProgramId}");
        return $userPoints[0] ?? [];
    }
}
