<?php
require_once __DIR__.'/head.php';
global $_COMPANY, $_ZONE, $_USER;

if (!defined('INDEX_PAGE') && empty ($_ZONE)) { // User is logged in, but for some reason the $_ZONE is not set, logout and retry
  Http::Redirect('logout');
}

if (!$_COMPANY->getAppCustomization()['points']['enabled'] || !$_COMPANY->getAppCustomization()['points']['frontend_enabled']) {
  header(HTTP_BAD_REQUEST);
  exit();
}

$htmlTitle = "Points Program";
$banner	  = $_ZONE->val('banner_background');
// Since we do not have profile on/off setting in the zone, we will use calendar_page_banner_title check,
// if it is empty then we will not show Profile label, else we will show (ouch ... lazy implementation).
$bannerTitle = empty($_ZONE->val('calendar_page_banner_title')) ? '' : gettext('Points Program');

/**
 * TODO - Fix the FE user-facing points-view
 * This is still a WIP page as we have hardcoded lorem-ipsum text in the page
 */
// $pointsTransactions = PointsTransaction::GetAllTransactions();
$pointsTransactions = [];

$htmlTitle = gettext("View your points");

include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/points_program_html.php');
include(__DIR__ . '/views/footer_html.php');

?>
