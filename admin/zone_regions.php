<?php
require_once __DIR__.'/head.php';

if (!$_USER->canManageAffinitiesContent()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}
$pagetitle  = "Manage ".$_ZONE->val('zonename')." Regions";
$zoneRegions = array();
$availableRegions = $_COMPANY->getAllRegions();

$allZones = $_COMPANY->fetchAllZonesFromDB();
$usedRegionidsArray = array();
foreach($allZones as $zone){
    if ($zone['zoneid'] == $_ZONE->id()) {
        $zoneRegionidsArray  = explode(',',$zone['regionids']);
// Disable which regions are available to which zones. This will allow the regions to be used by all zones
// This change was made in light of JPMC requriements where the same regions were getting used by different zones.
//    } elseif ($zone['app_type'] != $_ZONE->val('app_type')) {
//        // Skip the zones which are for different applications.
//        continue;
//    } else {
//        $rid = explode(',',$zone['regionids']);
//        $usedRegionidsArray = array_merge($usedRegionidsArray,$rid);
    }
}
//$usedRegionidsArray = array_filter(array_unique($usedRegionidsArray));

if (isset($_POST['submit'])) {

    $homeRegionIds  = '0';
    $r  = array();
    if (isset($_POST['homeRegions'])) {        
        foreach ($_POST['homeRegions'] as $id){
            $r[] = $_COMPANY->decodeId($id);
        }
        $homeRegionIds = implode(',', array_unique($r)) ?? '0';
    }
    
    $existingZoneRegionsList =  $_COMPANY->getRegionsByZones([$_ZONE->id()]);
    $existingRegionIds = array_column($existingZoneRegionsList, 'regionid');
    $removedRegionIds = array_diff( $existingRegionIds,$r);

    if (!empty($removedRegionIds)){
        $removedRegionIds = implode(',',$removedRegionIds);
        $groupnames = implode(',',Group::GetGroupnamesByRegionIds($removedRegionIds));

        if (!empty($groupnames)) {  
            $regions = $_COMPANY->getRegionsByCSV($removedRegionIds);
            $regionnames = implode(',',array_column($regions,'region'));   
            $return_msg =  sprintf(gettext('%1$s %2$s are currently using %3$s region(s). Please remove these region(s) from above mentioned %2$s and then try again'),$groupnames,$_COMPANY->getAppCustomization()['group']['name-plural'],$regionnames);
            $_SESSION['error_msg_time'] = time();
            Http::Redirect('zone_regions?msg='.urlencode(str_replace("&"," ",$return_msg)));
        }
    }


    $_COMPANY->updateZoneRegions($homeRegionIds);
    // Reload company by invalidating cache and reloading it from database
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
    $_ZONE = $_COMPANY->getZone($_ZONE->id());
    $_SESSION['updated'] = time();
    Http::Redirect("zone_regions");
}

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/zone_regions.html');
include(__DIR__ . '/views/footer.html');

?>
