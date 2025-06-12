<?php
// Do no use require_once as this class is included in Company.php.

/**
 * budgets_v2 table stores the budget for the company, broken by groups, zones, chapters per year.
 * Each row in budgets_v2 should have a unique combinations of companyid,zoneid,groupid,chapterid
 * For example companys budget for a given zone will be in the row which matches companyid and zoneid with group,chapter
 * being 0. Similarly group budget will be in row with companyid,zoneid,groupid set and chapterid is set.
 *
 * Data should not be inserted into budgets_v2 directly, it should always be set using call Budget_UpdateBudget
 * which has several checks to keep integrity of budgets_v2 table.
 *
 */
class Budget2 extends Teleskope {

    private $child_budgets = null;
    private $total_expenses = null;
    private $total_expenses_incl_child = null;

    public const FOREIGN_CURRENCIES = [
        "AED"=>["cc" => "AED", "name" => "UAE dirham"],
        "AFN"=>["cc" => "AFN", "name" => "Afghan afghani"],
        "ALL"=>["cc" => "ALL", "name" => "Albanian lek"],
        "AMD"=>["cc" => "AMD", "name" => "Armenian dram"],
        "ANG"=>["cc" => "ANG", "name" => "Netherlands Antillean gulden"],
        "AOA"=>["cc" => "AOA", "name" => "Angolan kwanza"],
        "ARS"=>["cc" => "ARS", "name" => "Argentine peso"],
        "AUD"=>["cc" => "AUD", "name" => "Australian dollar"],
        "AWG"=>["cc" => "AWG", "name" => "Aruban florin"],
        "AZN"=>["cc" => "AZN", "name" => "Azerbaijani manat"],
        "BAM"=>["cc" => "BAM", "name" => "Bosnia and Herzegovina konvertibilna marka"],
        "BBD"=>["cc" => "BBD", "name" => "Barbadian dollar"],
        "BDT"=>["cc" => "BDT", "name" => "Bangladeshi taka"],
        "BGN"=>["cc" => "BGN", "name" => "Bulgarian lev"],
        "BHD"=>["cc" => "BHD", "name" => "Bahraini dinar"],
        "BIF"=>["cc" => "BIF", "name" => "Burundi franc"],
        "BMD"=>["cc" => "BMD", "name" => "Bermudian dollar"],
        "BND"=>["cc" => "BND", "name" => "Brunei dollar"],
        "BOB"=>["cc" => "BOB", "name" => "Bolivian boliviano"],
        "BRL"=>["cc" => "BRL", "name" => "Brazilian real"],
        "BSD"=>["cc" => "BSD", "name" => "Bahamian dollar"],
        "BTN"=>["cc" => "BTN", "name" => "Bhutanese ngultrum"],
        "BWP"=>["cc" => "BWP", "name" => "Botswana pula"],
        "BYR"=>["cc" => "BYR", "name" => "Belarusian ruble"],
        "BZD"=>["cc" => "BZD", "name" => "Belize dollar"],
        "CAD"=>["cc" => "CAD", "name" => "Canadian dollar"],
        "CDF"=>["cc" => "CDF", "name" => "Congolese franc"],
        "CHF"=>["cc" => "CHF", "name" => "Swiss franc"],
        "CLP"=>["cc" => "CLP", "name" => "Chilean peso"],
        "CNY"=>["cc" => "CNY", "name" => "Chinese/Yuan renminbi"],
        "COP"=>["cc" => "COP", "name" => "Colombian peso"],
        "CRC"=>["cc" => "CRC", "name" => "Costa Rican colon"],
        "CUC"=>["cc" => "CUC", "name" => "Cuban peso"],
        "CVE"=>["cc" => "CVE", "name" => "Cape Verdean escudo"],
        "CZK"=>["cc" => "CZK", "name" => "Czech koruna"],
        "DJF"=>["cc" => "DJF", "name" => "Djiboutian franc"],
        "DKK"=>["cc" => "DKK", "name" => "Danish krone"],
        "DOP"=>["cc" => "DOP", "name" => "Dominican peso"],
        "DZD"=>["cc" => "DZD", "name" => "Algerian dinar"],
        "EEK"=>["cc" => "EEK", "name" => "Estonian kroon"],
        "EGP"=>["cc" => "EGP", "name" => "Egyptian pound"],
        "ERN"=>["cc" => "ERN", "name" => "Eritrean nakfa"],
        "ETB"=>["cc" => "ETB", "name" => "Ethiopian birr"],
        "EUR"=>["cc" => "EUR", "name" => "European Euro"],
        "FJD"=>["cc" => "FJD", "name" => "Fijian dollar"],
        "FKP"=>["cc" => "FKP", "name" => "Falkland Islands pound"],
        "GBP"=>["cc" => "GBP", "name" => "British pound"],
        "GEL"=>["cc" => "GEL", "name" => "Georgian lari"],
        "GHS"=>["cc" => "GHS", "name" => "Ghanaian cedi"],
        "GIP"=>["cc" => "GIP", "name" => "Gibraltar pound"],
        "GMD"=>["cc" => "GMD", "name" => "Gambian dalasi"],
        "GNF"=>["cc" => "GNF", "name" => "Guinean franc"],
        "GQE"=>["cc" => "GQE", "name" => "Central African CFA franc"],
        "GTQ"=>["cc" => "GTQ", "name" => "Guatemalan quetzal"],
        "GYD"=>["cc" => "GYD", "name" => "Guyanese dollar"],
        "HKD"=>["cc" => "HKD", "name" => "Hong Kong dollar"],
        "HNL"=>["cc" => "HNL", "name" => "Honduran lempira"],
        "HRK"=>["cc" => "HRK", "name" => "Croatian kuna"],
        "HTG"=>["cc" => "HTG", "name" => "Haitian gourde"],
        "HUF"=>["cc" => "HUF", "name" => "Hungarian forint"],
        "IDR"=>["cc" => "IDR", "name" => "Indonesian rupiah"],
        "ILS"=>["cc" => "ILS", "name" => "Israeli new sheqel"],
        "INR"=>["cc" => "INR", "name" => "Indian rupee"],
        "IQD"=>["cc" => "IQD", "name" => "Iraqi dinar"],
        "IRR"=>["cc" => "IRR", "name" => "Iranian rial"],
        "ISK"=>["cc" => "ISK", "name" => "Icelandic króna"],
        "JMD"=>["cc" => "JMD", "name" => "Jamaican dollar"],
        "JOD"=>["cc" => "JOD", "name" => "Jordanian dinar"],
        "JPY"=>["cc" => "JPY", "name" => "Japanese yen"],
        "KES"=>["cc" => "KES", "name" => "Kenyan shilling"],
        "KGS"=>["cc" => "KGS", "name" => "Kyrgyzstani som"],
        "KHR"=>["cc" => "KHR", "name" => "Cambodian riel"],
        "KMF"=>["cc" => "KMF", "name" => "Comorian franc"],
        "KPW"=>["cc" => "KPW", "name" => "North Korean won"],
        "KRW"=>["cc" => "KRW", "name" => "South Korean won"],
        "KWD"=>["cc" => "KWD", "name" => "Kuwaiti dinar"],
        "KYD"=>["cc" => "KYD", "name" => "Cayman Islands dollar"],
        "KZT"=>["cc" => "KZT", "name" => "Kazakhstani tenge"],
        "LAK"=>["cc" => "LAK", "name" => "Lao kip"],
        "LBP"=>["cc" => "LBP", "name" => "Lebanese lira"],
        "LKR"=>["cc" => "LKR", "name" => "Sri Lankan rupee"],
        "LRD"=>["cc" => "LRD", "name" => "Liberian dollar"],
        "LSL"=>["cc" => "LSL", "name" => "Lesotho loti"],
        "LTL"=>["cc" => "LTL", "name" => "Lithuanian litas"],
        "LVL"=>["cc" => "LVL", "name" => "Latvian lats"],
        "LYD"=>["cc" => "LYD", "name" => "Libyan dinar"],
        "MAD"=>["cc" => "MAD", "name" => "Moroccan dirham"],
        "MDL"=>["cc" => "MDL", "name" => "Moldovan leu"],
        "MGA"=>["cc" => "MGA", "name" => "Malagasy ariary"],
        "MKD"=>["cc" => "MKD", "name" => "Macedonian denar"],
        "MMK"=>["cc" => "MMK", "name" => "Myanma kyat"],
        "MNT"=>["cc" => "MNT", "name" => "Mongolian tugrik"],
        "MOP"=>["cc" => "MOP", "name" => "Macanese pataca"],
        "MRO"=>["cc" => "MRO", "name" => "Mauritanian ouguiya"],
        "MUR"=>["cc" => "MUR", "name" => "Mauritian rupee"],
        "MVR"=>["cc" => "MVR", "name" => "Maldivian rufiyaa"],
        "MWK"=>["cc" => "MWK", "name" => "Malawian kwacha"],
        "MXN"=>["cc" => "MXN", "name" => "Mexican peso"],
        "MYR"=>["cc" => "MYR", "name" => "Malaysian ringgit"],
        "MZM"=>["cc" => "MZM", "name" => "Mozambican metical"],
        "NAD"=>["cc" => "NAD", "name" => "Namibian dollar"],
        "NGN"=>["cc" => "NGN", "name" => "Nigerian naira"],
        "NIO"=>["cc" => "NIO", "name" => "Nicaraguan córdoba"],
        "NOK"=>["cc" => "NOK", "name" => "Norwegian krone"],
        "NPR"=>["cc" => "NPR", "name" => "Nepalese rupee"],
        "NZD"=>["cc" => "NZD", "name" => "New Zealand dollar"],
        "OMR"=>["cc" => "OMR", "name" => "Omani rial"],
        "PAB"=>["cc" => "PAB", "name" => "Panamanian balboa"],
        "PEN"=>["cc" => "PEN", "name" => "Peruvian nuevo sol"],
        "PGK"=>["cc" => "PGK", "name" => "Papua New Guinean kina"],
        "PHP"=>["cc" => "PHP", "name" => "Philippine peso"],
        "PKR"=>["cc" => "PKR", "name" => "Pakistani rupee"],
        "PLN"=>["cc" => "PLN", "name" => "Polish zloty"],
        "PYG"=>["cc" => "PYG", "name" => "Paraguayan guarani"],
        "QAR"=>["cc" => "QAR", "name" => "Qatari riyal"],
        "RON"=>["cc" => "RON", "name" => "Romanian leu"],
        "RSD"=>["cc" => "RSD", "name" => "Serbian dinar"],
        "RUB"=>["cc" => "RUB", "name" => "Russian ruble"],
        "SAR"=>["cc" => "SAR", "name" => "Saudi riyal"],
        "SBD"=>["cc" => "SBD", "name" => "Solomon Islands dollar"],
        "SCR"=>["cc" => "SCR", "name" => "Seychellois rupee"],
        "SDG"=>["cc" => "SDG", "name" => "Sudanese pound"],
        "SEK"=>["cc" => "SEK", "name" => "Swedish krona"],
        "SGD"=>["cc" => "SGD", "name" => "Singapore dollar"],
        "SHP"=>["cc" => "SHP", "name" => "Saint Helena pound"],
        "SLL"=>["cc" => "SLL", "name" => "Sierra Leonean leone"],
        "SOS"=>["cc" => "SOS", "name" => "Somali shilling"],
        "SRD"=>["cc" => "SRD", "name" => "Surinamese dollar"],
        "SYP"=>["cc" => "SYP", "name" => "Syrian pound"],
        "SZL"=>["cc" => "SZL", "name" => "Swazi lilangeni"],
        "THB"=>["cc" => "THB", "name" => "Thai baht"],
        "TJS"=>["cc" => "TJS", "name" => "Tajikistani somoni"],
        "TMT"=>["cc" => "TMT", "name" => "Turkmen manat"],
        "TND"=>["cc" => "TND", "name" => "Tunisian dinar"],
        "TRY"=>["cc" => "TRY", "name" => "Turkish new lira"],
        "TTD"=>["cc" => "TTD", "name" => "Trinidad and Tobago dollar"],
        "TWD"=>["cc" => "TWD", "name" => "New Taiwan dollar"],
        "TZS"=>["cc" => "TZS", "name" => "Tanzanian shilling"],
        "UAH"=>["cc" => "UAH", "name" => "Ukrainian hryvnia"],
        "UGX"=>["cc" => "UGX", "name" => "Ugandan shilling"],
        "USD"=>["cc" => "USD", "name" => "United States dollar"],
        "UYU"=>["cc" => "UYU", "name" => "Uruguayan peso"],
        "UZS"=>["cc" => "UZS", "name" => "Uzbekistani som"],
        "VEB"=>["cc" => "VEB", "name" => "Venezuelan bolivar"],
        "VND"=>["cc" => "VND", "name" => "Vietnamese dong"],
        "VUV"=>["cc" => "VUV", "name" => "Vanuatu vatu"],
        "WST"=>["cc" => "WST", "name" => "Samoan tala"],
        "XAF"=>["cc" => "XAF", "name" => "Central African CFA franc"],
        "XCD"=>["cc" => "XCD", "name" => "East Caribbean dollar"],
        "XDR"=>["cc" => "XDR", "name" => "Special Drawing Rights"],
        "XOF"=>["cc" => "XOF", "name" => "West African CFA franc"],
        "XPF"=>["cc" => "XPF", "name" => "CFP franc"],
        "YER"=>["cc" => "YER", "name" => "Yemeni rial"],
        "ZAR"=>["cc" => "ZAR", "name" => "South African rand"],
        "ZMK"=>["cc" => "ZMK", "name" => "Zambian kwacha"],
        "ZWR"=>["cc" => "ZWR", "name" => "Zimbabwean dollar"],
    ];

    protected function __construct(int $id,int $cid,array $fields) {
		parent::__construct($id,$cid,$fields);
		//declaring it protected so that no one can create it outside this class.
	}

	public function __toString() {
		return "Resource ". parent::__toString();
	}

    /**
     * @param int $budgetYearId
     * @param int $groupid ; set to 0 if you want company level budget. If set to a value, only group level budget will be returned.
     * @param int $chapterid set to 0 if you want group or company level budget. If set to a value, chapter level budget will be returned.
     * @return Budget2
     */
    public static function GetBudget(int $budgetYearId, int $groupid=0, int $chapterid=0): Budget2
    {
        global $_COMPANY, $_ZONE;

        $rows = self::DBGet("SELECT budgets_v2.*,budget_years.budget_year_title FROM budgets_v2 LEFT JOIN budget_years ON budget_years.budget_year_id = budgets_v2.budget_year_id WHERE budgets_v2.companyid={$_COMPANY->id()}  AND budgets_v2.zoneid={$_ZONE->id()} AND budgets_v2.budget_year_id='{$budgetYearId}' AND budgets_v2.groupid={$groupid} AND budgets_v2.chapterid={$chapterid}");
        if(!empty($rows)){
           return new Budget2($rows[0]['budget_id'], $_COMPANY->id(), $rows[0]);
        } else {
            // Return empty budget object
            return new Budget2(0, $_COMPANY->id(),
                array('budget_id' => '0',
                    'companyid'=> strval($_COMPANY->id()),
                    'zoneid' => strval($_ZONE->id()),
                    'groupid' => strval($groupid),
                    'chapterid' => strval($chapterid),
                    'budget_year_id' => strval($budgetYearId),
                    'budget_year_title' => '',
                    'budget_amount' => '0.00',
                    'budget_allocated_to_sub_accounts' => '0.00',
                    'budget_currency' => 'USD'
                    ));
        }
    }

    /**
     * Lazy load function to load Child Budgets only when needed.
     */
    private function loadChildBudgets () {
        global $_COMPANY, $_ZONE;
        $rows = array();
        $this->child_budgets = array();
        if ($this->val('groupid') == 0) {
            $rows = self::DBGet("SELECT `groupname` as budget_name, regionid as regionids, IFNULL(budget_id,0) as budget_id, {$this->val('companyid')} as companyid, {$this->val('zoneid')} as zoneid, groups.groupid,0 as chapterid, '{$this->val('budget_year_id')}' as budget_year_id, IFNULL(budget_amount,0.0000) as budget_amount, IFNULL(budget_allocated_to_sub_accounts,0.0000) as budget_allocated_to_sub_accounts, IFNULL(budget_currency,'{$this->val('budget_currency')}') as budget_currency,groups.isactive FROM `groups` LEFT JOIN `budgets_v2` ON `groups`.groupid=budgets_v2.groupid AND budgets_v2.budget_year_id='{$this->val('budget_year_id')}' AND budgets_v2.chapterid=0 WHERE groups.companyid={$this->val('companyid')} AND groups.zoneid={$this->val('zoneid')} ORDER BY groupname");
        } elseif ($this->val('chapterid') == 0) {
            $rows = self::DBGet("SELECT `chaptername` as budget_name,regionids,IFNULL(budget_id,0) as budget_id, {$this->val('companyid')} as companyid, {$this->val('zoneid')} as zoneid, chapters.groupid,chapters.chapterid, '{$this->val('budget_year_id')}' as budget_year_id, IFNULL(budget_amount,0.0000) as budget_amount, IFNULL(budget_allocated_to_sub_accounts,0.0000) as budget_allocated_to_sub_accounts, IFNULL(budget_currency,'{$this->val('budget_currency')}') as budget_currency,chapters.isactive FROM `chapters` LEFT JOIN `budgets_v2` ON chapters.groupid=budgets_v2.groupid AND chapters.chapterid=budgets_v2.chapterid AND budgets_v2.budget_year_id='{$this->val('budget_year_id')}' WHERE chapters.companyid='{$_COMPANY->id()}' AND ( chapters.zoneid='{$_ZONE->id()}' AND chapters.groupid={$this->val('groupid')}) ORDER BY chaptername");
        }
        foreach ($rows as $row) {
            $this->child_budgets[] = new Budget2($this->val('budget_id'), $this->val('companyid'), $row);
        }
    }

    /**
     * Gets an array of Budget2 objects.
     * @return array|null
     */
    public function getChildBudgets() {
        if ($this->child_budgets === null) {
            $this->loadChildBudgets();
        }
        return $this->child_budgets;
    }

    /**
     * Gets total expenses for given scope
     * This method is static as Expenses can exist without budget object
     * @param int $year
     * @param int|null $groupid , if null then groupid is ignored. This is different than 0 which will look for 0 match
     * @param int|null $chapterid if null then chapterid is ignored. This is different than 0 which will look for 0 match
     * @return array with float values for spent_from_allocated_budget, spent_from_other_funding, spent_total
     */
    public static function _GetTotalExpenses ( int $year, ?int $groupid=null, ?int $chapterid=null) : array
    {
        global $_COMPANY, $_ZONE;
        $filter = '';

        if ($groupid !== null) {
            $filter = " AND groupid={$groupid}";
        }

        if ($chapterid !== null) {
            $filter .= " AND chapterid={$chapterid}";
        }

        $spent = self::DBGet("SELECT sum(budgetuses.usedamount) as spent, funding_source  FROM budgetuses WHERE companyid={$_COMPANY->id()}  AND zoneid={$_ZONE->id()} AND budget_year_id='{$year}' {$filter} GROUP BY funding_source");
        $spent_from_allocated_budget = floatval(Arr::SearchColumnReturnColumnVal($spent, 'allocated_budget', 'funding_source', 'spent') ?: 0);
        $spent_from_other_funding = floatval(Arr::SearchColumnReturnColumnVal($spent, 'other_funding', 'funding_source', 'spent') ?: 0);
        $spent_total = $spent_from_allocated_budget + $spent_from_other_funding;
        return [
            'spent_from_allocated_budget' => $spent_from_allocated_budget,
            'spent_from_other_funding' => $spent_from_other_funding,
            'spent_total' => $spent_total,
            ];
    }

    /**
     * A wrapper around _GetTotalExpenses
     * @return float
     */
    public function getTotalExpenses() : array
    {
        if ($this->total_expenses === null) { // Lazy Loading
            $this->total_expenses = self::_GetTotalExpenses($this->val('budget_year_id'), $this->val('groupid'), $this->val('chapterid'));
        }
        return $this->total_expenses;
    }

    public function getTotalExpensesInclChild() : array
    {
        if ($this->total_expenses_incl_child === null) { // Lazy Loading
            $this->total_expenses_incl_child = self::_GetTotalExpenses($this->val('budget_year_id'), $this->val('groupid') ?: null, $this->val('chapterid') ?: null);
        }
        return $this->total_expenses_incl_child;
    }

    /**
     * @param int $year
     * @param int $groupid
     * @param int $chapterid
     * @return array list of all the expense records
     */
//    public function _GetAllExpenses (int $year, int $groupid=0, int $chapterid=0) : array
//    {
//        global $_COMPANY, $_ZONE;
//        $filter = '';
//
//        if ($groupid > 0) {
//            $filter = " AND groupid={$groupid}";
//        }
//
//        if ($chapterid > 0) {
//            $filter .= " AND chapterid={$chapterid}";
//        }
//
//        return self::DBGet("SELECT *  FROM budgetuses WHERE companyid={$_COMPANY->id()}  AND zoneid={$_ZONE->id()} AND budget_year_id='{$year}' {$filter}");
//    }

    /**
     * A wrapper around _GetAllExpenses
     * @return array
     */
//    public function getAllExpenses():array
//    {
//        return self::_GetTotalExpenses($this->val('budget_year_id'),$this->val('groupid'), $this->val('chapterid'));
//    }

    public function getTotalBudget() : float
    {
        return floatval($this->val('budget_amount'));
    }

    /**
     * Returns total budget allocated to children (groups or chapters)
     * @return float
     */
    public function getTotalBudgetAllocatedToSubAccounts() : float
    {
        return floatval($this->val('budget_allocated_to_sub_accounts'));
    }

    public function getTotalBudgetAllocated() : float
    {
        return floatval($this->val('budget_allocated_to_sub_accounts'));
    }

    public function getTotalBudgetAvailable() : float
    {
        return floatval($this->val('budget_amount') - $this->val('budget_allocated_to_sub_accounts') - $this->getTotalExpenses()['spent_from_allocated_budget']);
    }

    /**
     * This method moves the specified amount from the parent to self, if parent has budget available.
     * If -ve value is specified then the money is moved from self to parent.
     * @param float $amount
     * @return int
     */
    public function moveBudgetFromParentToMe (float $amount): int {
        if (!$this->val('groupid')) {
            return -2; // Error
        }
        $newAmount = $amount + $this->val('budget_amount');
        return self::UpdateBudget($newAmount, $this->val('budget_year_id'), $this->val('groupid'), $this->val('chapterid'));
    }

    public static function UpdateBudget(float $budget_amount, int $year, int $groupid=0, int $chapterid=0): int
    {
        global $_COMPANY, $_ZONE, $_USER;
        $budget_currency = $_COMPANY->getAppCustomization()['budgets']['currency'] ?? 'USD';

        $callResult = self::DBCall("call Budget_UpdateBudget({$_COMPANY->id()}, {$_ZONE->id()}, {$groupid}, {$chapterid}, '{$year}', {$budget_amount}, '{$budget_currency}', {$_USER->id()})");
        $insertid = $callResult['insert_id'];
        $updated = $callResult['impacted_rows'];

        if ($callResult['error_code']) {
            return -1;
        } else {
            return ($insertid) ? 2 : ($updated ? 1 : 0);
        }
    }

    public static function GetBudgetUse(int $usesid)
    {
        global $_COMPANY;
        $rows = self::DBGet("SELECT * FROM budgetuses WHERE companyid={$_COMPANY->id()} AND usesid={$usesid}");
        if (!empty($rows)) {
            return $rows[0];
        }
        return null;
    }

    public static function DeleteBudgetUse(int $usesid)
    {
        global $_COMPANY;
        global $_ZONE;
        global $_USER;

        $expense_entry = ExpenseEntry::GetExpenseEntry($usesid);
        $expense_entry->deleteAllAttachments();

        // No need to delete budgetuses_items explicitly, DB constraint to delete cascade will do it
        $retVal1 = self::DBUpdate("DELETE FROM `budgetuses` WHERE `companyid`={$_COMPANY->id()} AND (`usesid`={$usesid})");
        // Remove corresponding budget_requests link
        $retVal2 = self::DBUpdate("UPDATE budget_requests SET budget_usesid=NULL WHERE companyid={$_COMPANY->id()} AND budget_usesid={$usesid}");
        return $retVal1 && $retVal2;
    }

    public static function GetGroupBudgetUsed(int $groupid, int $year) : int
    {
        global $_COMPANY;
        $rows = self::DBGet("SELECT IFNULL(sum(usedamount),0) as usedAmount from budgetuses where companyid={$_COMPANY->id()} AND (groupid={$groupid} AND budget_year_id={$year})");
        if (!empty($rows)) {
            return (int)$rows[0]['usedAmount'];
        }
        return 0;
    }

    public static function GetBudgetRequestRecByUsesId(int $usesid)
    {
        global $_COMPANY;

        if (!$usesid) {
            return [];
        }
        $row = self::DBGet("SELECT * FROM budget_requests WHERE companyid={$_COMPANY->id()} AND budget_usesid={$usesid}");
        if (!empty($row)) {
            return $row[0];
        }
        return [];
    }

    /**
     * @param int $request_id
     * @param int $groupid
     * @param float $requested_amount
     * @param string $purpose
     * @param string $need_by
     * @param string $request_details
     * @param int $chapterid
     * @return int
     */
     public static function CreateOrUpdateBudgetRequest(int $request_id, int $groupid, float $requested_amount, string $purpose, string $need_by, string $request_details, int $chapterid=0, string $custom_fields = ''): int
    {
        global $_COMPANY, $_ZONE, $_USER;

        if (empty($request_id)) {
            $request_id = self::DBInsertPS("
                INSERT INTO budget_requests (companyid, zoneid, groupid, requested_by, requested_amount, purpose, need_by, request_date, request_modified_date, request_status, is_active, `description`, chapterid, custom_fields)
                VALUES (?,?,?,?,?,?,?,NOW(),NOW(),1,1,?,?,?)",
                'iiiidxxxix',
                $_COMPANY->id(), $_ZONE->id(), $groupid, $_USER->id(), $requested_amount, $purpose, $need_by, $request_details, $chapterid, $custom_fields);
        } else {
            self::DBUpdatePS("
                UPDATE budget_requests SET requested_by=?, requested_amount=?, purpose=?, need_by=?, request_modified_date=now(),request_status=1,is_active=1,`description`=?,`chapterid`=?, `custom_fields` = ?
                WHERE `companyid`=? AND `zoneid`=? AND request_id=?
                ",
                'idxxxixiii',
                $_USER->id(), $requested_amount, $purpose, $need_by, $request_details, $chapterid, $custom_fields, $_COMPANY->id(), $_ZONE->id(), $request_id);
        }
        return $request_id;

        /**  We need to move the send email code here in the future, until that time we are commenting it out.

         if (!$sendEmail)
            return $request_id;

        if (!$request_id)
            return false;

        $group = Group::GetGroup($groupid);
        $groupname = $group->val('groupname');
        $app_type = $_ZONE->val('app_type');
        $reply_addr = $group->val('replyto_email');
        $from = $group->val('from_email_label') . ': Budget Request';
        $admins = User::GetAllZoneAdminsWhoCanManageZoneBudget();
        $email = implode(',', array_column($admins, 'email'));
        $username = $_USER->getFullName();
        $subject = "Budget request from " . $groupname;
        $requested_amount = $_COMPANY->getCurrencySymbol().$_USER->formatAmountForDisplay($requested_amount);
        $msg = <<<EOMEOM
                <p>There is a budget request from {$groupname}. Please login to the admin account and go to the <a href="{$_COMPANY->getAdminURL()}/admin/budget#budgetRequests"> budget section </a> to approve or deny this request.</p>
                <br>
                <p>Budget Request Summary:</p>
                <p>-------------------------------------------------</p>
                <p>Requested by:  {$username}</p>
                <p>Requested amount:  {$requested_amount}</p>
                <p>Purpose:  {$purpose}</p>
                <p>Needed by:  {$need_by}</p>
                <p>Description:  {$request_details}</p>
                <p>-------------------------------------------------</p>
EOMEOM;
        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $emesg = str_replace('#messagehere#', $msg, $template);
        return $_COMPANY->emailSend2($from, $email, $subject, $emesg, $app_type, $reply_addr);
         **/
    }

    public static function GetBudgetRequestDetail(int $request_id, bool $allow_cross_zone_fetch = false)
    {
        global $_COMPANY, $_ZONE;
        $row = NULL;
        $r = self::DBGet("SELECT * FROM budget_requests WHERE companyid={$_COMPANY->id()} AND request_id={$request_id}");
        if (!empty($r)){
            $row = $r[0];

            if (!$allow_cross_zone_fetch && ((int) $row['zoneid'] !== $_ZONE->id())) {
                return null;
            }
        }
       return $row;
    }
    public static function UpdateBudgetUsesId(int $request_id, int $budget_uses_id)
    {
        global $_COMPANY;
        return self::DBUpdate("UPDATE budget_requests SET budget_usesid={$budget_uses_id} WHERE companyid={$_COMPANY->id()} AND request_id={$request_id}");
    }

    public static function ApproveOrDenyBudgetRequest(int $groupid, int $request_id, float $amount_approved, string $approver_comment, int $request_status, int $usesid, int $move_parent_budget_to_child)
    {
        global $_COMPANY, $_ZONE, $_USER;

        $update = self::DBUpdatePS("UPDATE `budget_requests` SET `request_status`=?,`amount_approved`=?,`approved_by`=?,`approver_comment`=?,`approved_date`=NOW(), `move_parent_budget_to_child`=?  WHERE `companyid`=? AND `groupid`=? AND `request_id`=?",
        'ixisiiii',
        $request_status,$amount_approved,$_USER->id(),$approver_comment, $move_parent_budget_to_child, $_COMPANY->id(),$groupid,$request_id
        );

        if ($usesid>0 && $request_status == 2){ ## Case Approved -> Update budget uses if exist
            self::DBUpdatePS("UPDATE `budgetuses` SET `budget_approval_status`='2',`budgeted_amount`=?,`usedamount`=?,`budget_approved_by`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `usesid`=? ",
            'ixiii',
            $amount_approved,$amount_approved, $_USER->id(), $_COMPANY->id(),$usesid
            );
        }

        if ($usesid>0 && $request_status == 3){ ## Case Denied -> Update budget uses if exist
            self::DBUpdatePS("UPDATE `budgetuses` SET `budget_approval_status`='3',`budgeted_amount`='0',`usedamount`='0',`budget_approved_by`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `usesid`=? ",
            'iii',
            $_USER->id(),$_COMPANY->id(),$usesid
            );
        }
        return $update;
    }


    public static function GetCompanyBudgetYears()
    {
        global $_COMPANY, $_ZONE;
        $rows = self::DBGet("SELECT * FROM `budget_years` WHERE `company_id`='{$_COMPANY->id()}' AND `zone_id`='{$_ZONE->id()}' AND `isactive`=1");
        usort($rows,function($a,$b) {
            return strcmp($a['budget_year_start_date'], $b['budget_year_start_date']);
        });
        return $rows;
    }

    public static function GetCompanyBudgetYearDetail(int $budget_year_id)
    {
        global $_COMPANY, $_ZONE;
        $data = null;
        $b = self::DBGet("SELECT * FROM `budget_years` WHERE `company_id`='{$_COMPANY->id()}' AND `zone_id`='{$_ZONE->id()}' AND `budget_year_id`='{$budget_year_id}'");
        if (!empty($b)){
            $data = $b[0];
        }
        return $data;
    }

    /**
     * @param string $dt
     * @return mixed|null
     */
    public static function GetBudgetYearByDate(string $dt)
    {
        $rows = self::GetCompanyBudgetYears();
        if (preg_match('/20[0-9][0-9]-[01][0-9]-[0123][0-9]/', $dt)) {
            foreach ($rows as $row) {
                if ($dt >= $row['budget_year_start_date'] && $dt <= $row['budget_year_end_date']) {
                    return $row;
                }
            }
        }
        return null;
    }

    /**
     * Return budget year id row (array) for a given date. 0 on error
     * @param string $dt should be in format 'YYYY-mm-dd'
     * @return int
     */
    public static function GetBudgetYearIdByDate(string $dt) : int
    {
        $budgetYear = self::GetBudgetYearByDate($dt);
        return empty($budgetYear) ? 0 : intval($budgetYear['budget_year_id']);
    }

    public static function SaveCompanyBudgetYear(int $budget_year_id, string $budget_year_title, string $budget_year_start_date, string $budget_year_end_date)
    {
        global $_COMPANY, $_ZONE, $_USER;

        if (strtotime($budget_year_start_date) > strtotime($budget_year_end_date)) {
            return -1;
        }


        $overlap_count = (int)self::DBGetPS("SELECT count(1) as overlap_count FROM budget_years WHERE company_id=? AND zone_id=? AND ((budget_years.budget_year_start_date <= ? AND budget_years.budget_year_end_date >= ?) OR (budget_years.budget_year_start_date <= ? AND budget_years.budget_year_end_date >= ?)) AND budget_year_id != ? ",'iixxxxi',$_COMPANY->id(), $_ZONE->id(), $budget_year_start_date, $budget_year_start_date, $budget_year_end_date, $budget_year_end_date, $budget_year_id)[0]['overlap_count'];
        if ($overlap_count) {
            return -2;
        }

        if ($budget_year_id) {
            self::DBUpdatePS("UPDATE `budget_years` SET `budget_year_title`=?,`budget_year_start_date`=?,`budget_year_end_date`=?,`modifiedon`=NOW(),`modifiedby`=? WHERE `company_id`=? AND `zone_id`=? AND `budget_year_id`=?",
                'xxxiiii',
                $budget_year_title,$budget_year_start_date,$budget_year_end_date, $_USER->id(),$_COMPANY->id(),$_ZONE->id(),$budget_year_id);
        } else {
            $budget_year_id = self::DBInsertPS("INSERT INTO `budget_years`(`company_id`, `zone_id`, `budget_year_title`, `budget_year_start_date`, `budget_year_end_date`,`createdby`,`modifiedby`) VALUES (?,?,?,?,?,?,?)",
                'iixxxii',
                $_COMPANY->id(), $_ZONE->id(),$budget_year_title, $budget_year_start_date, $budget_year_end_date, $_USER->id(),$_USER->id());
        }
        return $budget_year_id;
    }

    public static function DeleteBudgetYear(int $budget_year_id)
    {
        global $_COMPANY, $_ZONE, $_USER;

        $check = self::DBGet("SELECT budget_year_id, budget_year_start_date, budget_year_end_date FROM `budget_years` WHERE `company_id`= '{$_COMPANY->id()}' AND `zone_id`='{$_ZONE->id()}' AND `budget_year_id` = '{$budget_year_id}'");   
        if(empty($check)) {
            return 0;
        }

        // Since we are only checking if there are any budgets attached to budget_year_id, we can skip COMPANY and ZONE check.
        $rows = self::DBGet("SELECT count(1) as totalrows FROM `budgets_v2` WHERE `budget_year_id`={$budget_year_id}");
        if($rows[0]['totalrows']>0){
            return 0;
        }

        // Since we are only checking if there are any budgets_other_funding attached to budget_year_id, we can skip COMPANY and ZONE check.
        $rows = self::DBGet("SELECT count(1) as totalrows FROM `budgets_other_funding` WHERE `budget_year_id`={$budget_year_id}");
        if($rows[0]['totalrows']>0){
            return 0;
        }

        // Since we are only checking if there are any budgetuses attached to budget_year_id, we can skip COMPANY and ZONE check.
        $rows = self::DBGet("SELECT count(1) as totalrows FROM `budgetuses` WHERE `budget_year_id`={$budget_year_id}");
        if($rows[0]['totalrows']>0){
            return 0;
        }

        return self::DBUpdate("DELETE FROM `budget_years` WHERE `company_id`= '{$_COMPANY->id()}' AND `zone_id`='{$_ZONE->id()}' AND `budget_year_id` = '{$budget_year_id}'");
    }

    public static function GetGroupOtherFunding(int $groupid, int $budget_year_id,int $chapterid)
    {
        global $_COMPANY, $_ZONE, $_USER;

        $chapterCondition = "";
        if ($chapterid && $_COMPANY->getAppCustomization()['chapter']['enabled']){
            $chapterCondition = " AND budgets_other_funding.chapterid='{$chapterid}' ";
        } elseif(!$_COMPANY->getAppCustomization()['chapter']['enabled']){
            $chapterCondition = " AND budgets_other_funding.chapterid=0 ";
        }

        return self::DBGet("SELECT budgets_other_funding.*,budget_years.budget_year_title FROM `budgets_other_funding` LEFT JOIN budget_years ON budget_years.budget_year_id= budgets_other_funding.budget_year_id WHERE budgets_other_funding.`companyid`='{$_COMPANY->id()}' AND (budgets_other_funding.`zoneid`='{$_ZONE->id()}' AND budgets_other_funding.`groupid`='{$groupid}' AND budgets_other_funding.`budget_year_id`='{$budget_year_id}') {$chapterCondition}");
    }

    public static function GetGroupTotalOtherFundingByBudgetYear(int $budget_year_id, int $groupid, int $chapterid)
    {
        global $_COMPANY, $_ZONE, $_USER;
        $totalOtherFund = 0;
        $chapterCondition = "";
        if ($chapterid && $_COMPANY->getAppCustomization()['chapter']['enabled']) {
            $chapterCondition = "  AND budgets_other_funding.chapterid='{$chapterid}'";
        } elseif (!$_COMPANY->getAppCustomization()['chapter']['enabled']){
            $chapterCondition = " AND budgets_other_funding.chapterid=0 ";
        }
        $b =  self::DBGet("SELECT SUM(funding_amount) as totalOtherFund FROM `budgets_other_funding` WHERE budgets_other_funding.`companyid`='{$_COMPANY->id()}' AND (budgets_other_funding.`zoneid`='{$_ZONE->id()}' AND budgets_other_funding.`groupid`='{$groupid}' {$chapterCondition} AND budgets_other_funding.budget_year_id='{$budget_year_id}')");

        if (!empty($b)){
            $totalOtherFund = $b[0]['totalOtherFund'];
        }
        return $totalOtherFund;
    }
    
    public static function GetGroupOtherFundingDetail(int $funding_id)
    {
        global $_COMPANY, $_ZONE, $_USER;
        $row = null;
        $b =  self::DBGet("SELECT budgets_other_funding.* FROM `budgets_other_funding` WHERE budgets_other_funding.`companyid`='{$_COMPANY->id()}' AND (budgets_other_funding.`zoneid`='{$_ZONE->id()}' AND budgets_other_funding.`funding_id`='{$funding_id}')");

        if (!empty($b)){
            $row = $b[0];
        }
        return $row;
    }

    public static function CreateOrUpdateGroupOtherFunding(int $funding_id, int $groupid, int $chapterid, string $funding_source, string $funding_date, int $budget_year_id, float $funding_amount, string $funding_currency, string $funding_description){

        global $_COMPANY, $_ZONE, $_USER;

        if ($funding_id) {
            self::DBUpdatePS("UPDATE `budgets_other_funding` SET `funding_source`=?,`funding_date`=?,`budget_year_id`=?,`funding_amount`=?,`funding_currency`=?,`funding_description`=?,`modifiedon`=NOW(),`modifiedby`=? WHERE `companyid`=? AND `zoneid`=? AND `groupid`=? AND `chapterid`=? AND `funding_id`=?",
                'ssisssiiiiii',
                $funding_source,$funding_date,$budget_year_id, $funding_amount, $funding_currency, $funding_description, $_USER->id(),$_COMPANY->id(), $_ZONE->id(),$groupid,$chapterid,$funding_id);
        } else {
            $funding_id = self::DBInsertPS("INSERT INTO `budgets_other_funding`(`companyid`, `zoneid`, `groupid`, `chapterid`, `funding_source`,`funding_date`,`budget_year_id`, `funding_amount`, `funding_currency`, `funding_description`, `modifiedby`,`createdby`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                'iiiississsii',
                $_COMPANY->id(), $_ZONE->id(),$groupid, $chapterid,$funding_source,$funding_date,$budget_year_id, $funding_amount, $funding_currency, $funding_description, $_USER->id(),$_USER->id());
        }
        return $funding_id;
    }

    public static function DeleteGroupOtherFunding(int $funding_id)
    {
        global $_COMPANY, $_ZONE, $_USER;
        return self::DBMutate("DELETE FROM budgets_other_funding WHERE `companyid`='{$_COMPANY->id()}' AND (budgets_other_funding.`zoneid`='{$_ZONE->id()}' AND budgets_other_funding.`funding_id`='{$funding_id}')");
    }


    public static function AddOrUpdateBudgetUse(int $usesid, int $groupid, int $chapterid, float $usedamount, float $budgeted_amount, string $description, string $date, int $expense_budget_year_id, string $eventtype, int $charge_code_id, string $vendor_name, int $eventid, $budget_details, string $funding_source, bool $is_paid=false, string $po_number='', string $invoice_number='', string $custom_fields = '') {

        global $_COMPANY, $_ZONE, $_USER;

        // Funding source is an enum, so set to default value if it is not set.
        $funding_source = in_array ($funding_source, ['other_funding','allocated_budget']) ? $funding_source : 'allocated_budget';

        if ($usesid) {
            self::DBUpdatePS("UPDATE budgetuses set `usedamount`=?,`budgeted_amount`=?,`description`=?,`date`=?,`budget_year_id`=?,modifiedon=now(),eventtype=?,funding_source=?,`chapterid`=?,`charge_code_id`=?,`vendor_name`=?,`modifiedby`=?,`budget_details`=?, `is_paid`=?, `po_number`=?, `invoice_number`=?, `custom_fields` = ? WHERE `companyid`=? AND `zoneid`=? AND `groupid`=? AND `usesid`=?",
            'ddxxixxiixixixxxiiii',
            $usedamount,$budgeted_amount,$description,$date,$expense_budget_year_id,$eventtype,$funding_source,$chapterid,$charge_code_id,$vendor_name, $_USER->id(),$budget_details,$is_paid, $po_number, $invoice_number, $custom_fields, $_COMPANY->id(), $_ZONE->id(),$groupid,$usesid);
        } else {
            $usesid = self::DBInsertPS("INSERT INTO `budgetuses`(`companyid`,`zoneid`, `groupid`, `chapterid`,`eventid`,`usedamount`, `budgeted_amount`, `description`, `date`, `budget_year_id`,`eventtype`,`funding_source`,`charge_code_id`,`vendor_name`,`createdby`,`modifiedby`,`createdon`, `modifiedon`, `isactive`,`budget_details`,`is_paid`, `po_number`, `invoice_number`, `custom_fields`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),now(),'1',?,?,?,?,?)",
            'iiiiiddxxixxixiixixxx',
            $_COMPANY->id(), $_ZONE->id(), $groupid, $chapterid, $eventid, $usedamount, $budgeted_amount, $description, $date, $expense_budget_year_id, $eventtype, $funding_source, $charge_code_id, $vendor_name, $_USER->id(), $_USER->id(),$budget_details, $is_paid, $po_number, $invoice_number, $custom_fields);
        }
        return $usesid;
    }

    public static function AddOrUpdateBudgetUseItem(int $usesid, int $itemid, string $item, float $item_budgeted_amount, float $item_used_amount, int $expensetypeid, string $foreigncurrency='', float $foreigncurrencyamount=0, float $currencyconversionrate=0){

        global $_COMPANY, $_ZONE, $_USER;
        if ($itemid) {
            self::DBUpdatePS("UPDATE `budgetuses_items` SET `item`=?,  `item_budgeted_amount`=?, `item_used_amount`=?, `expensetypeid`=?,`modifiedby`=?, `foreign_currency`=?,`foreign_currency_amount`=?,`conversion_rate`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `zoneid`=? AND itemid=? AND `usesid`=?",
            'xddiixddiiii',
            $item, $item_budgeted_amount,$item_used_amount, $expensetypeid, $_USER->id(), $foreigncurrency, $foreigncurrencyamount, $currencyconversionrate, $_COMPANY->id(), $_ZONE->id(),$itemid,$usesid);
        } else {
            $itemid = self::DBInsertPS("INSERT INTO `budgetuses_items`(`companyid`, `zoneid`,`usesid`, `item`, `item_budgeted_amount`, `item_used_amount`, `expensetypeid`, `createdby`,`modifiedby`,`foreign_currency`,`foreign_currency_amount`,`conversion_rate`,`createdon`, `modifiedon`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,now(),now())",
            'iiixddiiixdd',
            $_COMPANY->id(), $_ZONE->id(),$usesid,$item,$item_budgeted_amount,$item_used_amount,$expensetypeid, $_USER->id(),$_USER->id(),$foreigncurrency, $foreigncurrencyamount, $currencyconversionrate);
        }
        return $itemid;
    }

    public static function AddUpdateBudgetForeignCurrency(int $budget_year_id, string $allowed_foreign_currencies){
        global $_COMPANY, $_ZONE, $_USER;

        return self::DBUpdatePS("UPDATE `budget_years` SET `allowed_foreign_currencies`=?,`modifiedon`=NOW(),`modifiedby`=? WHERE `company_id`=? AND `zone_id`=? AND `budget_year_id`=?","xiiii",$allowed_foreign_currencies,$_USER->id(),$_COMPANY->id(),$_ZONE->id(),$budget_year_id);

    }

    public static function AddOrUpdateExpenseType(int $expenseTypeId, string $expenseType)
    {
        global $_COMPANY, $_ZONE, $_USER;

        if ($expenseTypeId) { // Update explicitly provided rowid
            return self::DBMutatePS("UPDATE `budget_expense_types` SET `expensetype`=?, `modifiedon`=now() WHERE `companyid`=? AND `zoneid`=? AND `expensetypeid`=?",
                'xiii',
                $expenseType, $_COMPANY->id(), $_ZONE->id(), $expenseTypeId);
        } else { // Add or update row on conflict
            return self::DBInsertPS("INSERT INTO `budget_expense_types` (`companyid`, `zoneid`, `expensetype`, `createdby`, `modifiedon`, `createon`, `isactive`) VALUES (?,?,?,?,now(),now(),1) ON DUPLICATE KEY UPDATE expensetype=?,modifiedon=now(),isactive=1",
                'iixix',
                $_COMPANY->id(), $_ZONE->id(), $expenseType, $_USER->id(), $expenseType);
        }
    }

    public static function AddOrUpdateChargeCodes(int $chargeCodeId, string $chargeCode)
    {
        global $_COMPANY, $_ZONE, $_USER;

        if ($chargeCodeId) { // Update explicitly provided rowid
            return self::DBMutatePS("UPDATE `budget_charge_codes` SET `charge_code`=?, `modifiedon`=now() WHERE `companyid`=? AND `zoneid`=? AND `charge_code_id`=?",
                'xiii',
                $chargeCode, $_COMPANY->id(), $_ZONE->id(), $chargeCodeId);
        } else { // Add or update row on conflict
            return self::DBInsertPS("INSERT INTO `budget_charge_codes` (`companyid`, `zoneid`, `charge_code`, `createdby`, `modifiedon`, `createdon`, `isactive`) VALUES (?,?,?,?,now(),now(),1) ON DUPLICATE KEY UPDATE charge_code=?,modifiedon=now(),isactive=1",
                'iixix',
                $_COMPANY->id(), $_ZONE->id(), $chargeCode, $_USER->id(), $chargeCode);
        }
    }


    public static function DeleteBudgetRequest(int $groupid, int $request_id)
    {
        global $_COMPANY;

        $budget_request = BudgetRequest::GetBudgetRequest($request_id);
        $budget_request->deleteAllAttachments();

        return self::DBUpdate("DELETE FROM `budget_requests` WHERE `companyid`='{$_COMPANY->id()}' AND `request_id`='{$request_id}' AND `groupid`='{$groupid}' ");
    }

    public static function ArchiveBudgetRequest(int $groupid, int $request_id)
    {
        global $_COMPANY;
        return self::DBUpdate("UPDATE `budget_requests` SET `is_active`=0 WHERE `companyid`='{$_COMPANY->id()}' AND `request_id`='{$request_id}' AND `groupid`='{$groupid}' ");
    }

    public static function AutoApproveBudgetRequest(int $usesid, int $eventid)
    {
        global $_COMPANY;
        return self::DBUpdate("UPDATE `budgetuses` SET `budget_approval_status`='2',`budget_approved_by`='auto',`usedamount`=`budgeted_amount` WHERE `companyid`='{$_COMPANY->id()}' AND `usesid`='{$usesid}' AND `eventid`='{$eventid}' ");

    }

    public static function DeleteExpenseType(int $expensetypeid)
    {
        global $_COMPANY, $_ZONE;
        $status_purge = self::STATUS_PURGE;
        return self::DBMutatePS("UPDATE budget_expense_types SET `isactive`='{$status_purge}',`modifiedon`=NOW() WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `expensetypeid`='{$expensetypeid}' ");
    }


    public static function DeleteBudgetChargeCodes(int $charge_code_id)
    {
        global $_COMPANY;
        $status_purge = self::STATUS_PURGE;
        return self::DBMutatePS("UPDATE `budget_charge_codes` SET `isactive`='{$status_purge}',`modifiedon`=NOW() WHERE `companyid`='{$_COMPANY->id()}' AND `charge_code_id`='{$charge_code_id}' ");
    }

    public static function GetGroupBudgetRequests(int $groupid, string $chapterids, int $limit) : array
    {
        global $_COMPANY, $_ZONE;

        // Safety check: Sanitize chapterids string to remove non-int chapterids if any.
        $chapterids = Sanitizer::SanitizeIntegerCSV($chapterids);

        $chapterCondition = '';
        if (!empty($chapterids)) {
            $chapterCondition = " AND `chapterid` IN ({$chapterids})";
        }
        return self::DBGet("SELECT * FROM `budget_requests` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `groupid`='{$groupid}' {$chapterCondition} AND `is_active`='1'  order by `request_id` DESC LIMIT {$limit}");

    }

    public static function GetBudgetExpenseTypes() : array
    {
        global $_COMPANY, $_ZONE;

        return self::DBGet("SELECT * FROM budget_expense_types WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `isactive`=1");
    }

    public static function GetBudgetChargeCodes() : array
    {
        global $_COMPANY, $_ZONE;
        
        return self::DBGet("SELECT * FROM budget_charge_codes WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `isactive`=1");

    }

    public static function GetUniqueVendorNames() : array
    {
        global $_COMPANY, $_ZONE;
        return self::DBGet("SELECT DISTINCT(`vendor_name`) as `vendor_name` FROM `budgetuses` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}'");
    }

    public static function GetBudgetUsesItems(int $usesid) : array
    {
        global $_COMPANY, $_ZONE;
        return self::DBGet("SELECT *, IFNULL((SELECT `expensetype` FROM budget_expense_types WHERE `expensetypeid`=`budgetuses_items`.expensetypeid ),'-') as expensetype FROM `budgetuses_items` WHERE companyid = '{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `usesid`='{$usesid}'");
    }
    
    public static function GetAllocatedBudgetDefinition() : string 
    {
        global $_COMPANY;
        $group_name = $_COMPANY->getAppCustomization()['group']['name'];
        return $_COMPANY->getAppCustomization()['budgets']['allocated_budget_definition'] ?: sprintf(gettext('Allocated Budget: are funds that have been specifically provided to your %s by your company.'), $group_name);
    }

    public static function GetOtherFundingDefinition() : string 
    {
        global $_COMPANY;
        return $_COMPANY->getAppCustomization()['budgets']['other_funding_definition'] ?: sprintf(gettext('Other Funding Source: are funds you receive from any other source.'));
    }

    public function getExpenseRows() : array
    {
        global $_COMPANY, $_ZONE;

        if (empty($this->val('budget_year_id'))) {
            return [];
        }

        $group_condition = '';
        if ($this->val('groupid')) {
            $group_condition .= " AND budgetuses.`groupid`='{$this->val('groupid')}'";
        }
        if ($this->val('chapterid')){
            $group_condition .= " AND budgetuses.`chapterid`='{$this->val('chapterid')}'";
        }

        $expenses   = self::DBGet("SELECT budgetuses.*,groups.groupname,`chapters`.`chaptername`,events.eventtitle FROM `budgetuses` left JOIN `groups`ON groups.groupid=budgetuses.groupid left JOIN events ON events.eventid=budgetuses.eventid LEFT JOIN `chapters` ON  `chapters`.chapterid = budgetuses.chapterid WHERE budgetuses.`companyid`='{$_COMPANY->id()}' AND ( budgetuses.`budget_year_id`='{$this->val('budget_year_id')}' AND budgetuses.`isactive`=1 {$group_condition})");

        usort($expenses, function($a, $b) {
            return $b['createdon'] <=> $a['createdon'];
        });

        return $expenses;
    }

    public function getItemLevelExpenseRows() : array
    {
        global $_COMPANY, $_ZONE;

        if (empty($this->val('budget_year_id'))) {
            return [];
        }

        $group_condition = '';
        if ($this->val('groupid')) {
            $group_condition .= " AND budgetuses.`groupid`='{$this->val('groupid')}'";
        }
        if ($this->val('chapterid')){
            $group_condition .= " AND budgetuses.`chapterid`='{$this->val('chapterid')}'";
        }

        return self::DBROGet("SELECT budgetuses.vendor_name,budgetuses.description,budgetuses.charge_code_id,budgetuses.funding_source,budgetuses.po_number,budgetuses.invoice_number, budgetuses.eventtype, budgetuses.date expense_date, budgetuses.usesid, budgetuses.groupid, `groups`.groupname, budgetuses.chapterid, chapters.chaptername,events.eventtitle, IFNULL(budgetuses_items.expensetypeid, 0) expensetypeid, IFNULL(budgetuses_items.item_budgeted_amount,budgetuses.budgeted_amount) budgeted_amount, IFNULL(budgetuses_items.item_used_amount,budgetuses.usedamount) expense_amount, IFNULL(budgetuses_items.itemid,0) itemid FROM `budgetuses` left JOIN `groups`ON groups.groupid=budgetuses.groupid left JOIN events ON events.eventid=budgetuses.eventid LEFT JOIN `chapters` ON  `chapters`.chapterid = budgetuses.chapterid LEFT JOIN `budgetuses_items` USING (usesid) WHERE budgetuses.`companyid`='{$_COMPANY->id()}' AND ( budgetuses.`budget_year_id`='{$this->val('budget_year_id')}' AND budgetuses.`isactive`=1 {$group_condition})");
    } 

    public static function FinalizeSubitems(int $usesid, array $keepItemIds)
    {
        global $_COMPANY, $_ZONE;
        if(empty($keepItemIds)) {
            // All items deleted
            return self::DBUpdate("DELETE FROM `budgetuses_items` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `usesid`={$usesid}");
        } else {
            // All items other than the ones on the keep list will be deleted
            $keepItemIds = Sanitizer::SanitizeIntegerArray($keepItemIds);
            $keepItemIdsList = implode(',', $keepItemIds);
            return self::DBUpdate("DELETE FROM `budgetuses_items` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND `usesid`={$usesid} AND `itemid` NOT IN ({$keepItemIdsList})");
        }
    }

}