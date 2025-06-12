<?php

class ReportPointsBalance extends Report
{
    public const META = array (
        'Fields' => array (
            'points_program_title' => 'Program',
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'email' => 'Email',
            'jobtitle' => 'Job Title',
            'employeetype' => 'Employee Type',
            'department' => 'Department',
            'branchname' => 'Office Location',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
            'region' => 'Region',
            'opco' => 'Company',
            'points_earned' => 'Points earned in selected period',
            'total_points_earned' => 'Points earned till now (Across All Groups)',
        ),
        'AdminFields' => array(
            'externalid' => 'Employee Id',
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
        return self::REPORT_TYPE_POINTS_BALANCE;
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
                        `points_programs`.`points_program_id`,
                        `points_programs`.`title` AS `points_program_title`,
                        `users`.`userid`,
                        `users`.`email`,
                        `users`.`externalid`,
                        `users`.`extendedprofile`,
                        `users`.`firstname`,
                        `users`.`lastname`,
                        `users`.`jobtitle`,
                        `users`.`employeetype`,
                        `users`.`department`,
                        `users`.`homeoffice`,
                        `users`.`opco`,
                        SUM(`points_transactions`.`amount`) AS `points_earned`
            FROM        `points_transactions`
            INNER JOIN  `points_programs` USING (`points_program_id`)
            INNER JOIN  `points_trigger_history` ON `points_transactions`.`points_trigger_history_id` = `points_trigger_history`.`points_trigger_history_id`
            INNER JOIN  `users` ON `points_transactions`.`user_id` = `users`.`userid` AND `users`.`companyid` = {$_COMPANY->id()}
            {$group_leader_member_condition}
            WHERE       `points_programs`.`company_id` = {$_COMPANY->id()}
            AND         `points_programs`.`zone_id` = {$_ZONE->id()}
            AND         `points_transactions`.`created_at` >= '{$from_utc}'
            AND         `points_transactions`.`created_at` <= '{$to_utc}'
            {$groupid_condition}
            GROUP BY    `users`.`userid`, `points_programs`.`points_program_id`
        ";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char);

        while ($user = mysqli_fetch_assoc($result)) {
            $user['department'] = ($this->getDepartmentValues($user['department'] ?? 0))['department'] ?? '';
            $homeoffice = $this->getBranchAndRegionValues($user['homeoffice'] ?? 0);
            $user['branchname'] = $homeoffice['branchname'] ?? '';
            $user['city'] = $homeoffice['city'] ?? '';
            $user['state'] = $homeoffice['state'] ?? '';
            $user['country'] = $homeoffice['country'] ?? '';
            $user['region'] = $homeoffice['region'] ?? '';

            $user['externalid'] = explode(':', $user['externalid'] ?? '')[0];
            if (!empty($user['extendedprofile'])) {
                $profile = User::DecryptProfile($user['extendedprofile']);
                foreach ($profile as $pk => $value) {
                    $user['extendedprofile.' . $pk] = $value;
                }
            }

            $user['total_points_earned'] = (UserPoints::Get($user['userid'], $user['points_program_id']))['points_balance'] ?? 0;

            $csv_row = [];
            foreach ($meta['Fields'] as $key => $value) {
                $csv_row[] = html_entity_decode($user[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $csv_row, $delimiter, $enclosure, $escape_char);
        }
    }

    public function getMetaArray(): array
    {
        global $_COMPANY;

        $meta = parent::getMetaArray();

        if ($meta['Filters']['selected_group_id']) {
            $meta['Fields']['points_earned'] = sprintf(
                'Points earned in selected period (In selected %s)',
                $_COMPANY->getAppCustomization()['group']['name-short']
            );
        } elseif ($meta['Filters']['selected_group_id'] === 0) {
            $meta['Fields']['points_earned'] = 'Points earned in selected period (In global admin-content)';
        } else {
            $meta['Fields']['points_earned'] = sprintf(
                'Points earned in selected period (Across all %s)',
                $_COMPANY->getAppCustomization()['group']['name-short-plural']
            );
        }

        return $meta;
    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;
        global $_ZONE;

        $reportmeta = parent::GetDefaultReportRecForDownload();

        // Ignore custom report values for groupname, chaptername and channelname and set them to what is defined in the zone
        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']["name-short"];
        $reportmeta['Fields']['chaptername'] = $_COMPANY->getAppCustomization()['chapter']["name-short"];
        $reportmeta['Fields']['channelname'] = $_COMPANY->getAppCustomization()['channel']["name-short"];
        $reportmeta['Fields']['total_points_earned'] = sprintf(
            'Points earned till now (Across all %s)',
            $_COMPANY->getAppCustomization()['group']['name-short-plural']
        );
        return $reportmeta;
    }
}
