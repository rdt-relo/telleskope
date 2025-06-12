<?php

class ReportBudget extends Report
{
    public const META = array(
        'Fields' => array(
            'budget_year_title' => 'Budget Year',
            'date' => 'Date',
            'enc_groupid' => 'Group ID',
            'groupname' => 'Group Name',
            'enc_chapterid' => 'Chapter ID',
            'chaptername' => 'Chapter Name',
            'description' => 'Description',
            // 'eventtitle' => 'Event name',
            'eventtype' => 'Event type',
            'funding_source' => 'Funding Source',
            'budgeted_amount' => 'Budgeted amount',
            'usedamount' => 'Spent amount',
            'charge_code' => 'Charge code',
            'vendor_name' => 'Vendor Name',
            'createdby' => 'Created By',
            'subitems' => 'Sub items'
        ),
        'Options' => array(),
        'Filters' => array(
            'groupids' => array(),
            'chapterids' => array(),
            'year' => ''
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_BUDGET;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        $groupid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
            $groupid_list = implode(',', $meta['Filters']['groupids']);
            $groupid_filter = " AND  bu.groupid IN ({$groupid_list})";
        }

        $chapterid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['chapterids'])) {
            $chapterid_list = implode(',', $meta['Filters']['chapterids']);
            $chapterid_filter = " AND (bu.chapterid IN ({$chapterid_list}))";
        }

        if (!empty($meta['Filters']) && !empty($meta['Filters']['year'])) {
            $budget_year_id = $meta['Filters']['year'];
        } else {
            $budget_year_id = Budget2::GetBudgetYearIdByDate(gmdate('Y-m-d'));
        }
        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $select = "SELECT bu.*,byrs.budget_year_title,
            groups.groupname,`chapters`.`chaptername`,
            IFNULL(bcc.charge_code,'-') as charge_code,
            (SELECT IFNULL(regions.region,'-') as region FROM `chapters` LEFT JOIN regions ON regions.regionid=chapters.regionids WHERE chapters.chapterid=bu.chapterid ) as regionname
            FROM `budgetuses` AS bu
            JOIN `groups`ON groups.groupid=bu.groupid AND `groups`.zoneid='{$_ZONE->id()}' AND groups.isactive=1
            left JOIN events ON events.eventid=bu.eventid
            LEFT JOIN `chapters` ON  `chapters`.chapterid = bu.chapterid
            LEFT JOIN budget_charge_codes AS bcc on bcc.charge_code_id=bu.charge_code_id
            LEFT JOIN budget_years AS byrs ON bu.budget_year_id = byrs.budget_year_id
            WHERE bu.`companyid`='{$_COMPANY->id()}' AND bu.`zoneid`={$_ZONE->id()}
            AND (
            bu.`budget_year_id`='{$budget_year_id}'
            $groupid_filter
            $chapterid_filter
            ) ORDER BY usesid DESC {$this->policy_limit}";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$rows = mysqli_fetch_assoc($result)) {
            //do something with $rows;
            $row = array();

            $subitems = mysqli_query($dbc, "SELECT *, IFNULL((SELECT `expensetype` FROM budget_expense_types WHERE `expensetypeid`=`budgetuses_items`.expensetypeid ),'') as expensetype FROM `budgetuses_items` WHERE `usesid`='" . $rows['usesid'] . "' {$this->policy_limit}");
            $subRow = "";
            while (@$subs = mysqli_fetch_assoc($subitems)) {
                $otherCurrencyPayment = '';
                $expensedType = $subs['expensetype'] . '/' . $subs['item'];
                $budgetedAmount = !(empty(floatval($subs['item_budgeted_amount']))) ? ', budgeted = ' . $_COMPANY->getCurrencySymbol() . number_format($subs['item_budgeted_amount'], 2) : '';
                $expensedAmount = $_COMPANY->getCurrencySymbol() . number_format($subs['item_used_amount'], 2);
                if ($subs['foreign_currency']) {
                    $otherCurrencyPayment = ' (expensed ' . $subs['foreign_currency'] . ' ' . number_format($subs['foreign_currency_amount'], 2) . ' @ ' . number_format($subs['conversion_rate'], 7) . ' conversion rate)';
                }
                $subRow .= '[ ' . $expensedType . ' = ' . $expensedAmount . $otherCurrencyPayment . $budgetedAmount . ' ], ';
            }
            $rows['subitems'] = rtrim($subRow, ', ');

            $rows['funding_source'] = ($rows['funding_source'] == 'allocated_budget') ? 'Allocated Budget' : 'Other Funding';

            $rows['budgeted_amount'] = $_COMPANY->getCurrencySymbol() . number_format($rows['budgeted_amount'], 2);
            $rows['usedamount'] = $_COMPANY->getCurrencySymbol() . number_format($rows['usedamount'], 2);
            $createdBy = User::GetUser($rows['createdby']);
            $rows['createdby'] = $createdBy ? $createdBy->getFullName(): '';

            $rows = $this->addCustomFieldsToRow($rows, $meta);

            $rows['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($rows['groupid']);
            $rows['enc_chapterid'] = $_COMPANY->encodeIdsInCSVForReport($rows['chapterid']);

            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
        }
    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;
        global $_ZONE;

        $reportmeta = parent::GetDefaultReportRecForDownload();

        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']["name-short"];
        $reportmeta['Fields']['enc_groupid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' ID';
        $reportmeta['Fields']['chaptername'] = $_COMPANY->getAppCustomization()['chapter']["name-short"];
        $reportmeta['Fields']['enc_chapterid'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' ID';

        if (!$_COMPANY->getAppCustomization()['budgets']['vendors']['enabled'])
            unset($reportmeta['Fields']['vendor_name']);

        return $reportmeta;
    }

    public static function GetMetadataForAnalytics () : array
    {
        return array (
            'ExludeFields' => array(),
            'TimeField' => 'time'
        );
    }
}
