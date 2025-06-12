<?php
require_once __DIR__.'/head.php';
global $_COMPANY, $_ZONE, $_USER;

if (!defined('INDEX_PAGE') && empty ($_ZONE)) { // User is logged in, but for some reason the $_ZONE is not set, logout and retry
  Http::Redirect('logout');
}

// Since we do not have my events on/off setting in the zone, we will use calendar_page_banner_title check,
// if it is empty then we will not show My Events label, else we will show (ouch ... lazy implementation).
$bannerTitle = '';//empty($_ZONE->val('calendar_page_banner_title')) ? '' : gettext('My Events');
$pageTitle = gettext('My Events');
// ZonesDropdown 
$userZoneIds = $_USER->val('zoneids');
$allEventTypes = [];
$eventTypeArray = array('all');
$userAllZones = [];
$allEventTypes = [];
if (!empty($userZoneIds)) {
    $userZoneIds = explode(',', $userZoneIds);
    foreach ($userZoneIds as $zoneId) {
        $userZone = $_COMPANY->getZone($zoneId);
        if(!empty($userZone)){
            $userAllZones[] = $userZone;
        }
    }
    // Event Types Dropdown
    $allEventTypes = Event::GetEventTypesByZones($userZoneIds, true);
    usort($allEventTypes, function ($a, $b) {
        return $a['type'] <=> $b['type'];
    });
}

include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/my_events_html.php');
include(__DIR__ . '/views/footer_html.php');
?>