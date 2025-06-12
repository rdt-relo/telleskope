<?php

class ReportBudgetYear extends Report
{
    public const META = array(
        'Fields' => array(
            'budget_year_title' => 'Budget Year',
            'enc_groupid' => 'Group ID',
            'groupname' => 'Group Name',
            'enc_chapterid' => 'Chapter ID',
            'chaptername' => 'Chapter Name',
            'budget_amount' => 'Amount Budgeted [T]',
            'budget_allocated_to_sub_accounts' => 'Budget Assigned To Sub Accounts [G]',
            'total_expenses' => 'Total Expenses [E]',
            'remaining_amount' => 'Budget available [A = T - (G+E)]',
        ),
        'Options' => array(),
        'Filters' => array(
            'groupid' => '', // empty for all groups
            'chapterids' => array(),
            'year' => ''
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_BUDGET_YEAR;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();
        $budget_rows = array();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }


        if (!empty($meta['Filters']) && !empty($meta['Filters']['year'])) {
            $budget_year_id = $meta['Filters']['year'];
        } else {
            $budget_year_id = Budget2::GetBudgetYearIdByDate(gmdate('Y-m-d'));
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

        // add group id or chapter id if set
        $groupid = 0;
        if (isset($meta['Filters']['groupid']) && $meta['Filters']['groupid'] > 0) {
            $groupid = $meta['Filters']['groupid'];
        }

        $chapterids_list = empty($meta['Filters']['chapterids']) ? array() : Arr::IntValues($meta['Filters']['chapterids']);

        // Root budget can be company budget when groupid=0 or group budget if groupid is set.
        $rootBudget = Budget2::GetBudget($budget_year_id, $groupid, 0);
        $childBudgets1 = $rootBudget->getChildBudgets();
        // For group level budgets we want to print the group level budget row
        // For companies level budgets we will skip it.
        if ($groupid) {
            $row = array(
                "budget_year_title" => $rootBudget->val("budget_year_title"),
                "groupid" => $rootBudget->val("groupid"),
                "groupname" => (0 == $rootBudget->val("groupid")) ? '' : (Group::GetGroupName($rootBudget->val("groupid"))),
                "chapterid" => 0,
                "chaptername" => (0 == $rootBudget->val("groupid")) ? '' : $this->getChapterNamesAsCSV($rootBudget->val('chapterid')),
                // "channelid" => '',
                "budget_amount" => $_COMPANY->getCurrencySymbol() . number_format($rootBudget->val("budget_amount"), 2),
                "budget_allocated_to_sub_accounts" => empty($childBudgets1) ? '' : $_COMPANY->getCurrencySymbol() . number_format($rootBudget->val("budget_allocated_to_sub_accounts"), 2),
                "total_expenses" => $_COMPANY->getCurrencySymbol() . number_format($rootBudget->getTotalExpenses()['spent_from_allocated_budget'], 2),
                "remaining_amount" => $_COMPANY->getCurrencySymbol() . number_format((float)($rootBudget->val("budget_amount")) - (float)($rootBudget->getTotalExpenses()['spent_from_allocated_budget']) - (float)($rootBudget->val("budget_allocated_to_sub_accounts")), 2)
            );
            $budget_rows[] = $row;
        }

        foreach ($childBudgets1 as $childBudget1) {

            if (0 == $childBudget1->val("isactive")) {
                continue;
            }
            $childBudgets2 = $childBudget1->getChildBudgets();

            $row = array(
                "budget_year_title" => $rootBudget->val("budget_year_title"),
                "groupid" => $childBudget1->val("groupid"),
                "groupname" => 0 == $childBudget1->val("groupid") ? '' : (Group::GetGroupName($childBudget1->val("groupid"))),
                "chapterid" => $childBudget1->val("chapterid"),
                "chaptername" => 0 == $childBudget1->val("chapterid") ? '' : $this->getChapterNamesAsCSV($childBudget1->val('chapterid')),
                // "channelid" => '',
                "budget_amount" => $_COMPANY->getCurrencySymbol() . number_format($childBudget1->val("budget_amount"), 2),
                "budget_allocated_to_sub_accounts" => empty($childBudgets2) ? '' : $_COMPANY->getCurrencySymbol() . number_format($childBudget1->val("budget_allocated_to_sub_accounts"), 2),
                "total_expenses" => $_COMPANY->getCurrencySymbol() . number_format($childBudget1->getTotalExpenses()['spent_from_allocated_budget'], 2),
                "remaining_amount" => $_COMPANY->getCurrencySymbol() . number_format((float)($childBudget1->val("budget_amount")) - (float)($childBudget1->getTotalExpenses()['spent_from_allocated_budget']) - (float)($childBudget1->val("budget_allocated_to_sub_accounts")), 2)
            );
            $budget_rows[] = $row;

            // next level child
            foreach ($childBudgets2 as $childBudget2) {

                if (0 == $childBudget2->val("isactive")) {
                    continue;
                }

                $row = array(
                    "budget_year_title" => $rootBudget->val("budget_year_title"),
                    "groupid" => $childBudget2->val("groupid"),
                    "groupname" => 0 == $childBudget2->val("groupid") ? '' : (Group::GetGroupName($childBudget2->val("groupid"))),
                    "chapterid" => $childBudget2->val("chapterid"),
                    "chaptername" => 0 == $childBudget2->val("chapterid") ? '' : $this->getChapterNamesAsCSV($childBudget2->val('chapterid')),
                    //  "channelid" => 0 == $childBudget2->val("channelid") ? '' : (Group::GetChannelName($childBudget2->val("channelid"), $childBudget2->val("groupid"))),
                    "budget_amount" => $_COMPANY->getCurrencySymbol() . number_format($childBudget2->val("budget_amount"), 2),
                    "budget_allocated_to_sub_accounts" => '',
                    "total_expenses" => $_COMPANY->getCurrencySymbol() . number_format($childBudget2->getTotalExpenses()['spent_from_allocated_budget'], 2),
                    "remaining_amount" => $_COMPANY->getCurrencySymbol() . number_format((float)($childBudget2->val("budget_amount")) - (float)($childBudget2->getTotalExpenses()['spent_from_allocated_budget']), 2)
                );

                $budget_rows[] = $row;
            }
        }

        $chapterids_list = empty($meta['Filters']['chapterids']) ? array() : Arr::IntValues($meta['Filters']['chapterids']);

        foreach ($budget_rows as $budget_row) {
            // If chapter list was provided remove non chapter rows
            if (!empty($chapterids_list) && !in_array($budget_row['chapterid'], $chapterids_list)) {
                continue;
            }

            $budget_row['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($budget_row['groupid']);
            $budget_row['enc_chapterid'] = $_COMPANY->encodeIdsInCSVForReport($budget_row['chapterid']);

            $row = array();
            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($budget_row[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
        }
    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;
        global $_ZONE;

        $reportmeta = parent::GetDefaultReportRecForDownload();

        // Ignore custom report values for groupname, chaptername and set them to what is defined in the zone
        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']["name-short"];
        $reportmeta['Fields']['enc_groupid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' ID';
        $reportmeta['Fields']['chaptername'] = $_COMPANY->getAppCustomization()['chapter']["name-short"];
        $reportmeta['Fields']['enc_chapterid'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' ID';

        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            unset($reportmeta['Fields']['chaptername']);
            unset($reportmeta['Fields']['enc_chapterid']);
        }
        if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
            unset($reportmeta['Fields']['channelname']);
        }

        return $reportmeta;
    }

    public static function GetMetadataForAnalytics () : array
    {
        return array (
            'ExludeFields' => array (
                'budget_allocated_to_sub_accounts',
                'total_expenses',
                'remaining_amount',
            ),
            'TimeField' => ''
        );
    }
}