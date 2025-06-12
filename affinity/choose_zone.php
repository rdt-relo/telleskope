<?php
require_once __DIR__.'/head.php';

if (!defined('INDEX_PAGE') && empty ($_ZONE)) { // User is logged in, but for some reason the $_ZONE is not set, logout and retry
  Http::Redirect('logout');
}

$htmlTitle = "Choose Zone";
$bannerTitle	  = gettext("Select a Zone to continue");


$hideMainMenuOptionsTemporary = true;

$zoneids = explode(',', $_COMPANY->val('zone_selector_zoneids'));
if(empty($zoneids)){
  $zoneids[] = $_ZONE->id();
}

if ($_USER->isDelegatedAccessUser()) {
  $authorized_zoneids = $_USER->getDelegatedAccessUserAuthorizedZones();
  $zoneids = array_intersect($zoneids, $authorized_zoneids);
}

include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/choose_zone_html.php');
include(__DIR__ . '/views/footer_html.php');

?>
