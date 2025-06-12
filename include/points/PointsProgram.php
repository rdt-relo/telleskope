<?php

class PointsProgram extends Teleskope
{
    use CacheableTrait;

    public const MEMEBER_POINTS_TRIGGERS = [
        'DAILY_EARNINGS',
        'NEW_COMMENT',
        'EVENT_RSVP_YES',
        'EVENT_CHECK_IN',
        'COMMENT_LIKE',
        'RECOGNITION_GIVEN',
        'RECOGNITION_RECEIVED',
        'VOLUNTEERING_SIGN_UP',
        'ON_GROUP_JOIN',
        'ON_SURVEY_RESPONSE',
    ];
    private static $active_points_programs = null;

    public static function GetTopicType(): string
    {
        return self::TOPIC_TYPES['POINTS_PROGRAM'];
    }

    public static function Create(
        string $title,
        string $description,
        int $points_total,
        string $start_date,
        string $end_date,
        float $point_conversion_rate,
        string $point_conversion_currency,
        string $points_image_url
    ): int {
        global $_COMPANY;
        global $_ZONE;

        $sql = 'INSERT INTO `points_programs` (`company_id`, `zone_id`, `title`, `description`, `points_total`, `start_date`, `end_date`, `point_conversion_rate`, `point_conversion_currency`, `points_image_url`, `isactive`) VALUES (?,?,?,?,?,?,?,?,?,?,?)';

        $retval = self::DBInsertPS(
            $sql,
            'iississdssi',
            $_COMPANY->id(),
            $_ZONE->id(),
            $title,
            $description,
            $points_total,
            $start_date,
            $end_date,
            $point_conversion_rate ?? 0,
            $point_conversion_currency ?? $_COMPANY->getCurrency($_ZONE->id()),
            $points_image_url ?? '',
            PointsProgram::STATUS_DRAFT
        );

        $_ZONE->expireRedisCache('ACTIVE_POINTS_PROGRAMS');

        return $retval;
    }

    public static function All(): array
    {
        global $_COMPANY;
        global $_ZONE;

        $sql = 'SELECT * FROM `points_programs` WHERE `company_id` = ? AND `zone_id` = ? ORDER BY points_program_id desc';

        return self::DBROGetPS(
            $sql,
            'ii',
            $_COMPANY->id(),
            $_ZONE->id()
        );
    }

    public static function AllActivePrograms(): array
    {
        global $_COMPANY;
        global $_ZONE;

        if (isset(static::$active_points_programs)) {
            return static::$active_points_programs;
        }

        static::$active_points_programs = $_ZONE->getFromRedisCache('ACTIVE_POINTS_PROGRAMS');
        if (static::$active_points_programs) {
            return static::$active_points_programs;
        }

        $sql = '
            SELECT *
            FROM `points_programs`
            WHERE `company_id` = ?
            AND `zone_id` = ?
            AND `start_date` <= NOW()
            AND `end_date` >= NOW()
            AND `isactive` = 1
            ORDER BY points_program_id DESC
        ';

        $results = self::DBROGetPS(
            $sql,
            'ii',
            $_COMPANY->id(),
            $_ZONE->id()
        );

        static::$active_points_programs = array_map(function (array $program) {
            return PointsProgram::Hydrate($program['points_program_id'], $program);
        }, $results);

        $_ZONE->putInRedisCache('ACTIVE_POINTS_PROGRAMS', static::$active_points_programs, 24 * 60 * 60);

        return static::$active_points_programs;
    }

    public static function GetPointsProgram(int $pointsProgramId): ?PointsProgram
    {
        global $_COMPANY;
        global $_ZONE;

        $retVal = self::DBROGet("SELECT * FROM `points_programs` WHERE `points_program_id` = {$pointsProgramId} AND `company_id` = {$_COMPANY->id()} AND `zone_id` = {$_ZONE->id()}"
        );

        if (empty($retVal)) {
            return null;
        }

        return new PointsProgram($pointsProgramId, $_COMPANY->id, $retVal[0]);
    }

    public function getGroupLeadPointsConfiguration(): array
    {
        global $_COMPANY;
        global $_ZONE;

        $results = self::DBGet("
            SELECT
                        `typeid`,
                        `sys_leadtype`,
                        `type`,
                        (
                            SELECT  `daily_earnings`
                            FROM    points_program__configuration_groupleads
                            WHERE   `group_lead_type_id` = `typeid`
                            AND     `points_program_id` = {$this->id()}
                        ) AS `daily_earnings`
            FROM        `grouplead_type`
            WHERE       `companyid` = {$_COMPANY->id()}
            AND         `zoneid` = {$_ZONE->id()}
            AND         `isactive` = 1
            ORDER BY    `daily_earnings` DESC, `typeid` ASC
        ");

        return array_map(function ($result) {
            $result['group_lead_type_id'] = $result['typeid'];
            $result['daily_earnings'] ??= 0;
            $result['group_lead_type_name'] = Group::SYS_GROUPLEAD_TYPES[$result['sys_leadtype']];
            return $result;
        }, $results);
    }

    public function updateGroupLeadPointsConfiguration(int $groupLeadTypeId, float $dailyEarnings): int
    {
        $sql = '
            INSERT INTO points_program__configuration_groupleads (`points_program_id`, `group_lead_type_id`, `daily_earnings`)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY
            UPDATE `daily_earnings` = ?
        ';

        return self::DBInsertPS(
            $sql,
            'iidd',
            $this->id(),
            $groupLeadTypeId,
            $dailyEarnings,
            $dailyEarnings
        );
    }

    public function getMemberPointsConfiguration(string $pointsTriggerKey = ''): array
    {
        $pointsConfig = $this->getMemberPointsConfigurationFromBE();
        $pointsConfig = array_column($pointsConfig, null, 'points_trigger_key');

        $pointsTriggers = PointsProgram::MEMEBER_POINTS_TRIGGERS;

        if ($pointsTriggerKey) {
            $pointsTriggers = [$pointsTriggerKey];
        }

        $config = array_map(function ($pointsTriggerKey) use ($pointsConfig) {
            return [
                'points_trigger_key' => $pointsTriggerKey,
                'points_earned' => $pointsConfig[$pointsTriggerKey]['points_earned'] ?? 0,
            ];
        }, $pointsTriggers);

        return $config;
    }

    private function getMemberPointsConfigurationFromBE(): array
    {
        if ($config = $this->getFromRedisCache('MEMBER_POINTS_CONFIG')) {
            return $config;
        }

        $config = self::DBROGet("
            SELECT `points_earned`, `points_trigger_key`
            FROM    points_program__configuration_members
            WHERE   `points_program_id` = {$this->id()}
        ");

        $this->putInRedisCache('MEMBER_POINTS_CONFIG', $config, 24 * 60 * 60);

        return $config;
    }

    public function updateMemberPointsConfigurationByKey(string $pointsTriggerKey, float $pointsEarned): int
    {
        $sql = '
            INSERT INTO points_program__configuration_members (`points_program_id`, `points_trigger_key`, `points_earned`)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY
            UPDATE `points_earned` = ?
        ';

        $retval = self::DBInsertPS(
            $sql,
            'isdd',
            $this->id(),
            $pointsTriggerKey,
            $pointsEarned,
            $pointsEarned
        );

        return $retval;
    }

    public function update(
        string $title,
        string $description,
        int $points_total,
        string $start_date,
        string $end_date,
        float $point_conversion_rate,
        string $point_conversion_currency,
        string $points_image_url
    ): int {
        global $_COMPANY;
        global $_ZONE;

        $sql = 'UPDATE `points_programs` SET `title` = ?, `description` = ?, `points_total` = ?, `start_date` = ?, `end_date` = ?, `point_conversion_rate` = ?, `point_conversion_currency` = ? '
        . (empty($points_image_url) ? '' : ', `points_image_url` = ? ')
        . 'WHERE `points_program_id` = ? AND `company_id` = ? AND `zone_id` = ?';

        $conditional_params = [];
        if (!empty($points_image_url)) {
            $conditional_params = [
                ['value' => $points_image_url, 'type' => 's'],
            ];
        }

        if (!$this->isDraft()) {
            $start_date = $this->val('start_date');
            $end_date = $this->val('end_date');
        }

        $parameters = [
            ['value' => $title, 'type' => 's'],
            ['value' => $description, 'type' => 's'],
            ['value' => $points_total, 'type' => 'i'],
            ['value' => $start_date, 'type' => 's'],
            ['value' => $end_date, 'type' => 's'],
            ['value' => $point_conversion_rate ?: $this->val('point_conversion_rate'), 'type' => 'd'],
            ['value' => $point_conversion_currency ?: $this->val('point_conversion_currency') , 'type' => 's'],
            ...$conditional_params,
            ['value' => $this->id(), 'type' => 'i'],
            ['value' => $_COMPANY->id(), 'type' => 'i'],
            ['value' => $_ZONE->id(), 'type' => 'i'],
        ];

        $retVal = self::DBUpdatePS(
            $sql,
            implode('', array_column($parameters, 'type')),
            ...(array_column($parameters, 'value'))
        );

        if ($retVal) {
            if (!empty($points_image_url) && ($this->val('points_image_url') != $points_image_url)) {
                $_COMPANY->deleteFile($this->val('points_image_url'));
            }
        }

        $_ZONE->expireRedisCache('ACTIVE_POINTS_PROGRAMS');

        return $retVal;
    }

    public static function GetLeaderboard(int $pointsProgramId): array
    {
        global $_COMPANY;
        global $_ZONE;

        $sql = '
            SELECT
                        `user_points`.`user_id`,
                        `user_points`.`points_balance`,
                        `user_points`.`points_credited_till`
            FROM        `user_points`
            INNER JOIN  `points_programs`
            ON          `user_points`.`points_program_id` = `points_programs`.`points_program_id`
            INNER JOIN  `users`
            ON          `user_points`.`user_id` = `users`.`userid`
            WHERE       `points_programs`.`points_program_id` = ?
            AND         `points_programs`.`company_id` = ?
            AND         `points_programs`.`zone_id` = ?
            ORDER BY    `points_balance` DESC, `user_points`.`user_id` DESC
            LIMIT       100
        ';

        $leaderboard = self::DBROGetPS(
            $sql,
            'iii',
            $pointsProgramId,
            $_COMPANY->id(),
            $_ZONE->id()
        );

        $leaderboardUserIds = array_column($leaderboard, 'user_id');
        foreach ($leaderboardUserIds as $index => $userId) {
            $leaderboard[$index]['user'] = User::GetUser($userId);
        }

        return $leaderboard;
    }

    public static function UploadPointsImage(array $uploaded_file): string
    {
        global $_COMPANY;

        if (empty($uploaded_file)) {
            return '';
        }

        if ($uploaded_file['error'] !== 0) {
            return '';
        }

        $context = 'POINTS';

        $uploaded_file_path = $uploaded_file['tmp_name'];

        $mimetype   =   mime_content_type($uploaded_file_path);
        $valid_mimes = array('image/png' => 'png');
        if (!in_array($mimetype,array_keys($valid_mimes))) {
            $error = 'invalid_file_format';
            header("Location: {$_SERVER['REQUEST_URI']}#error={$error}");
            die();
        }
        $ext = $valid_mimes[$mimetype];

        if ($ext === 'png') {
            [$width, $height] = getimagesize($uploaded_file_path);

            if ($height > 100 || $width > 100) {
                $error = 'oversized_image';
                header("Location: {$_SERVER['REQUEST_URI']}#error={$error}");
                die();
            }
        }

        $s3_file = strtolower($context) . '_' . teleskope_uuid() . '.' . $ext;
        $s3_url = $_COMPANY->saveFile($uploaded_file_path, $s3_file, $context);
        return $s3_url;
    }

    public function updatePointsProgramStatus(int $status): int
    {
        global $_COMPANY, $_ZONE;

        if ($this->isDraft() && $status !== PointsProgram::STATUS_ACTIVE) {
            return -1;
        }

        if ($status === PointsProgram::STATUS_DRAFT) {
            return -1;
        }

        $sql = "
            UPDATE  `points_programs`
            SET     `isactive` = {$status}
            WHERE   `points_program_id` = {$this->id()}
            AND     `company_id` = {$_COMPANY->id()}
            AND     `zone_id` = {$_ZONE->id()}
        ";

        $retval = self::DBUpdate($sql);

        $_ZONE->expireRedisCache('ACTIVE_POINTS_PROGRAMS');

        return $retval;
    }

    public function getTotalPointsCredited(): float
    {
        $total_points_credited_info = $this->getFromRedisCache('TOTAL_POINTS_CREDITED_INFO');

        $prev_amount = 0;
        $where_condition = '';
        if ($total_points_credited_info) {
            $total_points_credited_info = json_decode($total_points_credited_info, true);
            $prev_amount = $total_points_credited_info['amount'];
            $where_condition = "AND `points_transactions`.`points_transaction_id` > {$total_points_credited_info['last_transaction_id']}";
        }

        $results = self::DBROGet("
            SELECT
                    COALESCE(SUM(`amount`), 0) AS `total_points_credited`,
                    MAX(`points_transaction_id`) AS `last_transaction_id`
            FROM    `points_transactions`
            WHERE   `points_program_id` = {$this->id()}
            {$where_condition}
        ");

        $total_points_credited_info = [
            'amount' => $prev_amount + ($results[0]['total_points_credited'] ?? 0),
            'last_transaction_id' => $results[0]['last_transaction_id'] ?? $total_points_credited_info['last_transaction_id'] ?? 0,
        ];

        $this->putInRedisCache(
            'TOTAL_POINTS_CREDITED_INFO',
            json_encode($total_points_credited_info),
            24 * 60 * 60
        );

        return $total_points_credited_info['amount'];
    }
}
