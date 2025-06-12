<?php
require_once __DIR__.'/head.php';
global $_COMPANY, $_ZONE, $_USER;

$htmlTitle = sprintf(gettext("Welcome to %s Calendar"), $_COMPANY->val('companyname') .' '. $_COMPANY->getAppCustomization()['group']['name-plural'] . ' - ' . $_ZONE->val('zonename') . ' Zone');

if (!defined('INDEX_PAGE') && empty ($_ZONE)) { // User is logged in, but for some reason the $_ZONE is not set, logout and retry
	Http::Redirect('logout');
}

if (!$_COMPANY->getAppCustomization()['calendar']['enabled']) {
	// If calendar is not configured in header, then return.
	echo "Access Denied (Feature disabled)";
	header(HTTP_FORBIDDEN);
	exit();
}

// Data Validation - not needed
// Authorization - not needed

// The following is legacy check for compatibility reasons with old url's.
// Do not remove or modify it.
Http::RedirectIfOldUrl();

Http::RedirectIfHashAttributeUrl();
$requestedCalendarView = $_GET['calendarDefaultView'] ?? '';
$requestedCalendarDate = $_GET['calendarDefaultDate'] ?? '';

$selectedZoneIds = '';
if (!empty($_GET['zoneids'])) {
	if ($_GET['zoneids'] == 'all') {
		$selectedZoneIds = trim($_ZONE->id() . ',' . $_ZONE->val('calendar_sharing_zoneids'), ',');
	} else {
		$selectedZoneIds = $_COMPANY->decodeIdsInCSV($_GET['zoneids']);
	}
}
$zoneIdsArray = Str::ConvertCSVToArray($selectedZoneIds) ?: array($_ZONE->id());

// The following code should be kept in sync with similar code-001 in ajax_events.php
$groupCategoryRows = [];
$groupCategoryArray = [];
if (count($zoneIdsArray) <= 1) {
	// If more than one ZoneIds are selected, then we will disable Group Category Filter
	// Else, set $groupCategoryRows and filter
	$zoneForCategory = $zoneIdsArray[0] ? ($_COMPANY->getZone($zoneIdsArray[0]) ?? $_ZONE) : $_ZONE;
	$groupCategoryRows = Group::GetAllGroupCategoriesByZone($zoneForCategory, true, true);
	$filter = $_GET['category'] ?? $_GET['filter'] ?? '';
	if (empty($filter) || $filter == 'all') {
		// If filter is set to all or no category selected then add all group gategory ids to array.
		$groupCategoryArray = array_values(array_column($groupCategoryRows, 'categoryid'));
	} else {
		$groupCategoryArray = Str::ConvertCSVToArray($filter);	
	}	
}

// Note: The $_GET['regionids'] can be empty i.e. on first calendar page load
// or it may be set to 'all'
// or an array of regionids
$regionIdsArray = array('all');
if (!empty($_GET['regionids']) && $_GET['regionids'] != 'all') {
	$regionIdsArray = $_COMPANY->decodeIdsInArray(Str::ConvertCSVToArray($_GET['regionids']));
}

// Note: The $_GET['groups'] can be empty i.e. on first calendar page load
// or it may be set to 'all'
// or an array of groupids
$groupIdsArray = array('all');
if (!empty($_GET['groups']) && $_GET['groups'] != 'all') {
	$groupIdsArray = $_COMPANY->decodeIdsInArray(Str::ConvertCSVToArray($_GET['groups']));
}

// Note: The $_GET['chapterid'] can be empty i.e. on first calendar page load or chapter feature not enabled
// or it may be set to 'all'
// or an array of chapterids
$chapterIdsArray = array('all');
if (isset($_GET['chapterid'])) {
	if (empty($_GET['chapterid'])) {
		$chapterIdsArray = array();
	} elseif ($_GET['chapterid'] != 'all') {
		$chapterIdsArray  = $_COMPANY->decodeIdsInArray(Str::ConvertCSVToArray($_GET['chapterid']));
	}
}


// Note: The $_GET['eventType'] can be empty i.e. on first calendar page load
// or it may be set to 'all'
// or an array of eventTypes
$eventTypeArray = array('all');
if (isset($_GET['eventType'])) {
	if (empty($_GET['eventType'])) {
		$eventTypeArray = array();
	} elseif ($_GET['eventType'] != 'all') {
		$eventTypeArray  = $_COMPANY->decodeIdsInArray(Str::ConvertCSVToArray($_GET['eventType']));
	}
}

//Distance based Filter
$distance_radius = 100;
$current_latitude = 0;
$current_longitude = 0;

if (0 && !isset($_SESSION['fullAddress']) && !isset($_SESSION['current_latitude']) && !isset($_SESSION['current_longitude'])){
	$userOficeLocation = $_COMPANY->getBranch($_USER->val('homeoffice'));
	$_SESSION['current_latitude'] = $current_latitude;
	$_SESSION['current_longitude'] = $current_longitude;
	$_SESSION['fullAddress'] = '';

	if ($userOficeLocation){
		$_SESSION['fullAddress'] = trim(rtrim((str_replace(", , ",", ",$userOficeLocation->val('branchname') . ", " . $userOficeLocation->val('street') . ", " . $userOficeLocation->val('city') . ", " . $userOficeLocation->val('state') . ", " . $userOficeLocation->val('zipcode') . ", " . $userOficeLocation->val('country'))),' ,'));
		$geoLocation = $db->getLatLong($_SESSION['fullAddress']);

		if (!empty($geoLocation)) {
			$current_latitude = $geoLocation['lat'];
			$_SESSION['current_latitude'] = $current_latitude;
			$current_longitude = $geoLocation['lng'];
			$_SESSION['current_longitude'] = $current_longitude;
		}
	}
}

include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/calendar_html.php');
include(__DIR__ . '/views/footer_html.php');

?>
