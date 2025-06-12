<?php

class PointsTransaction extends Teleskope
{
    const TRANSACTION_TYPES = array (
        'GROUP_LEAD_ACCRUED_POINTS' => 'Group Lead Accrued Points',
        'MEMBER_ACCRUED_POINTS' => 'Member Accrued Points',
        'NEW_COMMENT' => 'New Comment',
        'EVENT_RSVP_YES' => 'Event RSVP Yes',
        'EVENT_RSVP_NOT_YES' => 'Event RSVP No/Maybe',
        'EVENT_CHECK_IN' => 'Event Check-In',
        'COMMENT_LIKE' => 'Comment Like',
        'RECOGNITION_GIVEN' => 'Recognition Given',
        'RECOGNITION_RECEIVED' => 'Recognition Received',
        'VOLUNTEERING_SIGN_UP' => 'Event volunteer sign up',
        'ON_GROUP_JOIN' => 'Group Join',
        'ON_SURVEY_RESPONSE' => 'Survey Response',
        'COMMENT_UNLIKE' => 'Comment Unlike',
        'DELETE_COMMENT' => 'Comment Deleted',
        'DELETE_EVENT_VOLUNTEER' => 'Event volunteer removed',
        'DELETE_RECOGNITION' => 'Recongition Deleted',
        'ON_GROUP_LEAVE' => 'Group Left',
        'EVENT_CHECK_OUT' => 'Event Check-Out'
    );

    public static function Create(array $attributes): int
    {

        $sql = 'INSERT INTO `points_transactions` (`points_program_id`, `user_id`, `amount`, `point_transaction_type_key`, `points_trigger_history_id`) VALUES (?, ?, ?, ?, ?)';

        return self::DBInsertPS(
            $sql,
            'iidsi',
            $attributes['points_program_id'],
            $attributes['user_id'],
            $attributes['amount'],
            $attributes['point_transaction_type_key'],
            $attributes['points_trigger_history_id'] ?? null
        );
    }

    public static function GetUserPointsBreakdown(int $userid, int $points_program_id, $from_datetime_utc, $to_datetime_utc, ?int $selected_group_id): array
    {
        global $_COMPANY, $_ZONE, $db;

        $groupid_condition = '';

        if ($selected_group_id) {
            $groupid_condition = "AND (
                (`points_trigger_history`.`groupid` = {$selected_group_id})
                OR 
                (`points_trigger_history`.`groupid` = 0 AND FIND_IN_SET({$selected_group_id}, `points_trigger_history`.`collaborating_groupids`))
            )";
        } elseif ($selected_group_id === 0) {
            $groupid_condition = "AND (`points_trigger_history`.`groupid` = 0 AND `points_trigger_history`.`collaborating_groupids` = '')";
        }

        $transactions = self::DBROGet("
            SELECT
                `points_transactions`.`points_transaction_id`,
                `points_transactions`.`user_id`,
                `points_transactions`.`points_program_id`,
                `points_programs`.`title` AS `points_program_title`,
                `points_transactions`.`amount`,
                `points_transactions`.`created_at`,
                `points_transactions`.`point_transaction_type_key`,
                `points_trigger_history`.`points_trigger_history_id`,
                `points_trigger_history`.`points_trigger_key`,
                `points_trigger_history`.`contextid`,
                `points_trigger_history`.`groupid`,
                `points_trigger_history`.`collaborating_groupids`
            FROM        `points_transactions`
                INNER JOIN  `points_programs` USING (`points_program_id`)
                INNER JOIN  `points_trigger_history` ON `points_transactions`.`points_trigger_history_id` = `points_trigger_history`.`points_trigger_history_id`
            WHERE       `points_programs`.`company_id` = {$_COMPANY->id()}
                AND         `points_programs`.`zone_id` = {$_ZONE->id()}
                AND         `points_programs`.`points_program_id` = {$points_program_id}
                AND         `points_transactions`.`user_id` = {$userid}
                AND         `points_transactions`.`created_at` >= '{$from_datetime_utc}'
                AND         `points_transactions`.`created_at` <= '{$to_datetime_utc}'
            {$groupid_condition}
            ORDER BY    `points_transactions`.`points_transaction_id` DESC
        ");

        return array_map(function (array $transaction) {
            global $db;

            $transaction['transaction_description'] = PointsTransaction::TRANSACTION_TYPES[$transaction['point_transaction_type_key']];
            $transaction['created_at'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $transaction['created_at'], $_SESSION['timezone'] ?? 'UTC');

            $points_trigger = PointsTrigger::Hydrate($transaction['points_trigger_history_id'], $transaction);
            $transaction['action'] = $points_trigger->getHumanReadableString();

            $transaction['action'] = $transaction['action'] ?: $transaction['transaction_description'];

            if ($transaction['groupid']) {
                $transaction['groupname'] = Group::GetGroupName($transaction['groupid']);
            } else {
                if (empty($transaction['collaborating_groupids'])) {
                    $transaction['groupname'] = '';
                } else {
                    $groupNames = [];
                    $collaboratedWithGroups = explode(',', $transaction['collaborating_groupids']);
                    foreach($collaboratedWithGroups as $groupid) {
                        $groupNames[] = Group::GetGroupName($groupid);
                    }
                    $transaction['groupname'] = Arr::NaturalLanguageJoin($groupNames, ' & ');
                }
            }

            return $transaction;
        }, $transactions);
    }
}
