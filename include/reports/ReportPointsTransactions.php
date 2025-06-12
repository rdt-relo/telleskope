<?php

class ReportPointsTransactions extends Report
{
    public const META = array (
        'Fields' => array (
            'points_program_title' => 'Program',
            'externalid' => 'Employee Id', // Since this report is for Admin Panel only, we can put externalid here
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'email' => 'Email',
            'amount' => 'Points',
            'transaction_description' => 'Description',
            'groupname' => 'Group Name',
            'created_at' => 'Time',
            'action' => 'Action',
        ),
        'Options' => array(
            'startDate' => null,
            'endDate' => null,
        ),
        'Filters' => array(
            'selected_group_id' => null,
            'include_group_leader_transactions' => false,
            'include_group_member_transactions' => false,
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType(): int
    {
        return self::REPORT_TYPE_POINTS_TRANSACTIONS;
    }

    protected static function GetReportMeta(): array
    {
        return self::META;
    }

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $_USER, $db;

        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        $from_utc = Sanitizer::SanitizeUTCDatetime($meta['Options']['startDate'] ?? '');
        $to_utc = Sanitizer::SanitizeUTCDatetime($meta['Options']['endDate'] ?? '') ;

        if (empty($from_utc) || empty($to_utc)) {
            return;
        }

        $groupid_condition = '';
        $group_leader_member_condition = '';

        $selected_group_id = $meta['Filters']['selected_group_id'];

        if ($selected_group_id) {
            $groupid_condition = "AND (
                (`points_trigger_history`.`groupid` = {$selected_group_id})
                OR (
                    `points_trigger_history`.`groupid` = 0 AND FIND_IN_SET({$selected_group_id}, `points_trigger_history`.`collaborating_groupids`)
                )
            )";

            if (!$meta['Filters']['include_group_leader_transactions'] || !$meta['Filters']['include_group_member_transactions']) {
                if ($meta['Filters']['include_group_leader_transactions']) {
                    $group_leader_member_condition = " INNER JOIN `groupleads` ON (`users`.`userid` = `groupleads`.`userid` AND `groupleads`.`groupid` = {$selected_group_id} AND `groupleads`.`isactive` = 1)";
                }

                if ($meta['Filters']['include_group_member_transactions']) {
                    $group_leader_member_condition = " INNER JOIN `groupmembers` ON (`users`.`userid` = `groupmembers`.`userid` AND `groupmembers`.`groupid` = {$selected_group_id} AND `groupmembers`.`isactive` = 1)";
                }
            }
        } elseif ($selected_group_id === 0) {
            $groupid_condition = "AND (
                `points_trigger_history`.`groupid` = 0
                AND `points_trigger_history`.`collaborating_groupids` = ''
            )";
        }

        $select = "
            SELECT
                        `points_transactions`.`points_transaction_id`,
                        `points_transactions`.`user_id`,
                        `points_transactions`.`points_program_id`,
                        `points_programs`.`title` AS `points_program_title`,
                        `points_transactions`.`amount`,
                        `points_transactions`.`created_at`,
                        `points_transactions`.`point_transaction_type_key`,
                        `users`.`email`,
                        `users`.`externalid`,
                        `users`.`firstname`,
                        `users`.`lastname`,
                        `points_trigger_history`.`points_trigger_history_id`,
                        `points_trigger_history`.`points_trigger_key`,
                        `points_trigger_history`.`contextid`,
                        `points_trigger_history`.`groupid`,
                        `points_trigger_history`.`collaborating_groupids`
            FROM        `points_transactions`
            INNER JOIN  `points_programs` USING (`points_program_id`)
            INNER JOIN  `points_trigger_history` ON `points_transactions`.`points_trigger_history_id` = `points_trigger_history`.`points_trigger_history_id`
            LEFT JOIN   `users` ON `points_transactions`.`user_id` = `users`.`userid` AND `users`.`companyid` = {$_COMPANY->id()}
            {$group_leader_member_condition}
            WHERE       `points_programs`.`company_id` = {$_COMPANY->id()}
            AND         `points_programs`.`zone_id` = {$_ZONE->id()}
            AND         `points_transactions`.`created_at` >= '{$from_utc}'
            AND         `points_transactions`.`created_at` <= '{$to_utc}'
            {$groupid_condition}
        ";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char);

        while ($transaction = mysqli_fetch_assoc($result)) {
            $transaction['transaction_description'] = PointsTransaction::TRANSACTION_TYPES[$transaction['point_transaction_type_key']];
            $transaction['email'] = $transaction['email'] ?? '';
            $transaction['externalid'] = ($transaction['externalid'] === null) ? 'not found' : User::ExtractExternalId($transaction['externalid']);
            $transaction['created_at'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $transaction['created_at'], $reportTimezone);

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

            $csv_row = [];
            foreach ($meta['Fields'] as $key => $value) {
                $csv_row[] = html_entity_decode($transaction[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $csv_row, $delimiter, $enclosure, $escape_char);
        }
    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;

        $reportmeta = parent::GetDefaultReportRecForDownload();
        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']['name-short'];
        return $reportmeta;
    }
}
