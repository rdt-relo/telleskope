<?php
require_once __DIR__.'/head.php';
$pagetitle = "Budget Reporting";

// Authorization Check
if (!$_USER->canManageZoneBudget()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	echo "403 Forbidden (Access Denied)";
	exit();
}

$selectedBudgetYear = null;
$date = $_USER->getLocalDateNow();
$currentYearId  = Budget2::GetBudgetYearIdByDate($date);
$yearId  = $currentYearId;
if (!empty($_GET['d'])){
	$yearId = $_COMPANY->decodeId($_GET['d']);
	$selectedBudgetYear = Budget2::GetCompanyBudgetYearDetail($yearId);
	$date = date('Y-m-d', strtotime($selectedBudgetYear['budget_year_end_date']));
	if ($selectedBudgetYear['budget_year_id'] == $currentYearId  ){
		$date = $_USER->getLocalDateNow();
	}
	$year = date("Y",strtotime($date));
	$lastyear = $year-1;
	$_month = date("n",strtotime($date));
	$month = date("m",strtotime($date));
	$day = date("d",strtotime($date));
	$monthName = date("M",strtotime($date));
} else {
	$year = date("Y",strtotime($date));
	$lastyear = $year-1;
	$_month = date("n",strtotime($date));
	$month = date("m",strtotime($date));
	$day = date("d",strtotime($date));
	$monthName = date("M",strtotime($date));
}
$budgetYears = Budget2::GetCompanyBudgetYears();

if (!empty($budgetYears)){

	// check for group categories
	$groupCategoryRows = Group::GetAllGroupCategories(true);
	$groupCategoryIds = array_column($groupCategoryRows, 'categoryid');

	if (isset($_GET['filter']) && in_array($_GET['filter'],$groupCategoryIds)){
		$group_category_id = (int)$_GET['filter'];
	} else {
		$group_category_id = (int)Group::GetDefaultGroupCategoryRow()['categoryid'];
	}

	//Company Budget
	$companyBudgetObj = Budget2::GetBudget($yearId);
	$companybudget = intval($companyBudgetObj->getTotalBudget());
	$spentbyregion 				= array();
	$quarterlyspend 			= array();

	$groups = array();
	$totalAlloc = 0;
	$regionalUse = array();
	foreach ($companyBudgetObj->getChildBudgets() as $g) {
		$totalBudget = intval($g->getTotalBudget());
		$totalExpenses = intval($g->getTotalExpenses()['spent_from_allocated_budget']);
		$totalBudgetAvailable = intval($g->getTotalBudgetAvailable());
		$percent = ($companybudget) ? round($totalBudget*100/$companybudget,0) : 0;
		$grp = Group::GetGroup($g->val('groupid'));
		if (($grp->val('categoryid') == $group_category_id) &&
			($grp->val('isactive') == 1 || $totalBudget || $totalExpenses)) {
			$totalAlloc += $totalBudget;
			$groups[] = array(
				'groupid' => $grp->val('groupid'),
				'regionid' => $grp->val('regionid'),
				'groupname_short' => $grp->val('groupname_short'),
				'overlaycolor' => $grp->val('overlaycolor'),
				'isactive' => $grp->val('isactive'),
				'totalBudget' => $totalBudget,
				'totalExpenses' => $totalExpenses,
				'percent' => $percent,
				);

			// Split budget by regions as well.
			$chapterAndChannelBudgets = $g->getChildBudgets();
			foreach ($chapterAndChannelBudgets as $cg) {
				if (isset($regionalUse[$cg->val('regionids')])) {
					$regionalUse[$cg->val('regionids')] += intval($g->getTotalBudget());
				} else {
					$regionalUse[$cg->val('regionids')] = intval($g->getTotalBudget());
				}
			}
			if ($totalBudgetAvailable) {
				$groupRegions = explode(',', $g->val('regionids'));
				$countGR = count($groupRegions);
				foreach ($groupRegions as $r1) {
					if (isset($regionalUse[$cg->val('regionids')])) {
						$regionalUse[$cg->val('regionids')] += intval($totalBudgetAvailable/$countGR);
					} else {
						$regionalUse[$cg->val('regionids')] = intval($totalBudgetAvailable/$countGR);
					}
				}
			}
		}
	}

	if (count($groups)){

		if (0){ 
			//Quarterly spend per ERG
			for($rg=0;$rg<count($groups);$rg++){
				$st = array("-01-01","-04-01","-07-01","-10-01");
				$ed = array("-03-31","-06-30","-09-30","-12-31");
				$usedamount = array();
				for($sp = 0;$sp<4;$sp++){
					$start = $year.$st[$sp];
					$end = $year.$ed[$sp];
					$spend = $db->ro_get("SELECT IFNULL(sum(`usedamount`),0) as `usedamount` FROM `budgetuses` WHERE `groupid`='".$groups[$rg]['groupid']."' and  `budget_year_id`='".$yearId."' ");
					$usedamount[] = $spend[0]['usedamount'];
				
				}
				$quarterlyspend[] = array('groupname_short'=>$groups[$rg]['groupname_short'],'spend'=>$usedamount);
				
			} // end of `groups`for loop
		}
			
		##Budget Percentage By Regions
		$regions = $db->ro_get("SELECT `regionid`, `region` FROM `regions` WHERE `companyid`={$_COMPANY->id()} AND `isactive`='1'");

		$totalalc = 0;
		foreach ($regions as $region) {
			$aloamt = intval($regionalUse[$region['regionid']] ?? 0);
			$totalalc += $aloamt;
			$spentbyregion[] = array('region'=>$region['region'],'allocated'=>$aloamt,'percent'=>($companybudget ? round(($aloamt*100/$companybudget),0) : 0));
		}

		if($companybudget>$totalalc){
			$spentbyregion[] = array('region'=>'Not allocated','allocated'=>($companybudget-$totalalc),'percent'=>round((($companybudget-$totalalc)*100/$companybudget),0));
		}

	}

	if ($companyBudgetObj->getTotalBudgetAvailable()) {
		$groups[] = array('groupid'=>-1,'groupname_short'=>'Not allocated','totalBudget'=>intval($companyBudgetObj->getTotalBudgetAvailable()),'totalExpenses'=>0,'percent'=>round((($companyBudgetObj->getTotalBudgetAvailable())*100/$companybudget),0),'overlaycolor'=>'#636262');
	}
	$totalUnaccounted = $companybudget - ($totalAlloc + $companyBudgetObj->getTotalBudgetAvailable());
	if ($totalUnaccounted) {
		$groups[] = array('groupid'=>-2,'groupname_short'=>'Other','totalBudget'=>intval($totalUnaccounted),'totalExpenses'=>0,'percent'=>round((($totalUnaccounted)*100/$companybudget),0),'overlaycolor'=>'#bcbcbc');
	}
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/budget_reporting.html');
?>
