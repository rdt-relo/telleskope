<?php
include_once __DIR__.'/iframe_specific_head.php';
$_IFRAME_MODULE = 'CALENDAR';
/**
 * This iFrame has no secret information and thus does not require user login.
 */

if (
    !($iframe_request = $_GET['p']) ||
    !($iframe_params = $_IFRAME_COMPANY->decryptString2Array(urldecode($iframe_request))) ||
    empty($iframe_params['companyid']) ||
    $iframe_params['companyid'] != $_IFRAME_COMPANY->id() ||
    empty($iframe_params['zoneid']) ||
    empty($iframe_params['ancestor_domain'])
) {
    error_and_exit("Invalid iframe url, error 1405");
    exit();
}

Logger::LogDebug('Processing iFrame', $iframe_params);

if ($iframe_params['expires_after'] < time()) {
    error_and_exit("Expired iframe url, error 1406");
    exit();
}

//Logger::LogDebug('IFRAME Parameters', $iframe_params);

// Check if the calendar iframe is being called from the correct parent.
// *** HTTP Referer is required ***
// *** and it should match the ancestor_domain sent in the token
//
if (
    empty($_SERVER['HTTP_REFERER']) ||
    stripos($_SERVER['HTTP_REFERER'], 'https://'.$iframe_params['ancestor_domain']) === false
) {
    error_and_exit("Incorrect iFrame setup, error 1407");
    exit();
}

echo getCalendarIframe($_IFRAME_COMPANY, $iframe_request, $iframe_params);
exit();


// All validations done... now lets try to fetch the data and present it.
// We will create a semi trusted block to show the data.
function getCalendarIframe(Company $_IFRAME_COMPANY, string $iframe_request, array $iframe_params)
{
    global $db;

    // We will be setting $_COMPANY and $_ZONE temporarily
    // PS: Most of the code in this block is copied from affinity/ajax_events -> orderByGroupOrRegion block.
    // So if making change over there, copy it here as well
    global $_COMPANY, $_ZONE;
    $_COMPANY = $_IFRAME_COMPANY; // Temporary
    $_ZONE = $_IFRAME_COMPANY->getZone($iframe_params['zoneid']);

    // Check if calendar embed feature is enabled fro the company
    if (!$_IFRAME_COMPANY->getAppCustomization()['calendar']['allow_embed']) {
        error_and_exit("Calendar iFrame disabled by configuration, error 1401");
        exit();
    }

    if (!$_IFRAME_COMPANY->getAppCustomization()['event']['enabled']) {
        error_and_exit("Calendar iFrame disabled due to the event feature being disabled, error 1413");
        exit();
    }
    

    // Check if authentication token is required
    if ($_IFRAME_COMPANY->getAppCustomization()['calendar']['enable_secure_embed'] || !empty($iframe_params['requireAuthToken'])) {
        // Authentication token is required, check if valid and unexpired token is present
        if (empty($_GET['auth_token'])) {
            error_and_exit("Missing auth_token, error 1410");
            exit();
        }
        $auth_token_json = CompanyPSKey::DecryptToken($_GET['auth_token']);
        if ($auth_token_json == '') {
            error_and_exit("Invalid auth_token, error 1411");
            exit();
        }
        $auth_token = json_decode($auth_token_json, true);
        if (intval($auth_token['time']) + 60 < time()) {
            // Token expires after 60 seconds.
            error_and_exit("Expired auth_token, error 1412");
            exit();
        }
    }

    $key = 'HTMLCONTENT:' . $iframe_params['zoneid'].':' . $iframe_request;
    if (($content = $_IFRAME_COMPANY->getFromRedisCache($key)) === false) {

        $http_query_params = []; // Will be used to build direct URL;

        //Logger::LogDebug("In Calendar IFrame with params", $iframe_params);

        // Legacy checks ... to migrate from old interface to new interface
        if (isset( $iframe_params['eventType']) && $iframe_params['eventType'] == -1) $iframe_params['eventType'] = 'all';
        if (isset($iframe_params['category']) && is_array($iframe_params['category'])) $iframe_params['category'] = implode(',', $iframe_params['category']);
        // End of legacy migration

        // Group Categories
        $groupCategoryArray = null;
        if (0 && isset($iframe_params['category'])) { /* Disable group category logic from iframe calendar as it causes too many problems. */
            if (empty($iframe_params['category'])) {
                $http_query_params['category'] = '';
            } elseif ($iframe_params['category'] == 'all') {
                $http_query_params['category'] = 'all';
            } else {
                $groupCategoryArray = Str::ConvertCSVToArray($iframe_params['category']);
                $http_query_params['category'] = implode(',', $groupCategoryArray);
            }
        }

        // Zone ids
        $zoneIdsArray = null;
        if (isset($iframe_params['zoneids'])) {
            if ($iframe_params['zoneids'] == 'all') { #zoneids == 'all' is not a valid usecase
                $http_query_params['zoneids'] = 'all';
            } else {
                $zoneIdsArray = explode(',', $iframe_params['zoneids']);
                $http_query_params['zoneids'] = implode(',', $_IFRAME_COMPANY->encodeIdsInArray($zoneIdsArray));
            }
        }

        if (!$zoneIdsArray) {
            $zoneIdsArray = array($iframe_params['zoneid']); #set it to default zone
        }

        // RegionIds
        $regionIdsArray = null; // Set it to null to get all regions
        if (isset($iframe_params['regionids'])) {
            if ($iframe_params['regionids'] == 'all') {
                $http_query_params['regionids'] = 'all';
            } else {
                $regionIdsArray = Str::ConvertCSVToArray($iframe_params['regionids']);
                $http_query_params['regionids'] = implode(',', $_IFRAME_COMPANY->encodeIdsInArray($regionIdsArray));
                // Add back region id 0 to always see events without region, add it only after setting $http_query_params['regionids']
                $regionIdsArray[] = 0;
            }
        }

        // Get groups
        // First find the list of all groupids that match the group category in the zones and regions
        $group_chapter_rows = Group::GetGroupsAndChapterRows($zoneIdsArray, $regionIdsArray, $groupCategoryArray);

        $groupIdsArray = null;
        if (isset($iframe_params['groups'])) {
            if ($iframe_params['groups'] == 'all') {
                $groupIdsArray = array_unique(array_column($group_chapter_rows,'groupid'));
                $groupIdsArray[] = 0; // Add back 0 just to be sure.
                $http_query_params['groups'] = 'all';
            } else {
                $groupIdsArray = Str::ConvertCSVToArray($iframe_params['groups']);
                $http_query_params['groups'] = implode(',', $_IFRAME_COMPANY->encodeIdsInArray($groupIdsArray));
            }
        }

        // Chapters
        $chapterIdsArray = array();
        if (isset($iframe_params['chapterid'])) {
            if ($iframe_params['chapterid'] == 'all') {
                $chapterIdsArray = null; // Sending null to get all the values
                $http_query_params['chapterid'] = 'all';
            } elseif ($iframe_params['chapterid'] == '0') {
                $chapterIdsArray = [0];
                $http_query_params['chapterid'] = implode(',', $_IFRAME_COMPANY->encodeIdsInArray($chapterIdsArray));

            } else {
                $chapterIdsArray = Str::ConvertCSVToArray($iframe_params['chapterid']);
                $http_query_params['chapterid'] = implode(',', $_IFRAME_COMPANY->encodeIdsInArray($chapterIdsArray));
            }
        }


        // Channels (Not implimented yet)
        $channelIdsArray = null;

        // Get Event types
        $eventTypeArray = array();
        if (isset($iframe_params['eventType'])) {
            if ($iframe_params['eventType'] == 'all') {
                $eventTypeArray = null; // Sending null to get all the values
                $http_query_params['eventType'] = 'all';
            } else {
                $eventTypeArray = Str::ConvertCSVToArray($iframe_params['eventType']);
                $http_query_params['eventType'] = implode(',', $_IFRAME_COMPANY->encodeIdsInArray($eventTypeArray));
            }
        }

        $events = Event::FilterEvents2(
            $zoneIdsArray,
            $groupIdsArray,
            $chapterIdsArray,
            $channelIdsArray,
            $eventTypeArray,
            false
        );
        // After we migrate iFrame calendar to V6, switch the map to V3 to V6
        $calendarDefaultView = 'dayGridMonth';
        if(!empty($iframe_params['calendarDefaultView'])) {
            // After we migrate iFrame calendar to V6, switch the map to V3 to V6
            $calendarV3toV6Map = [
                'month' => 'dayGridMonth',
                'agendaWeek' => 'dayGridWeek',
                'agendaDay' => 'dayGridDay',
                'dayGridMonth' => 'dayGridMonth',
                'timeGridWeek' => 'timeGridWeek',
                'timeGridDay' => 'timeGridDay',
                'listMonth' => 'listMonth',
            ];

            $selectedView = array_intersect($iframe_params['calendarDefaultView'], array_keys($calendarV3toV6Map));
            if (!empty($selectedView)){
                $calendarDefaultView = $calendarV3toV6Map[$selectedView[0]];

            }
        }

        $calendarDefaultDate = date("Y-m-d"); // Override the date always to current date.

        $calendarLang = 'en';
        if (!empty($iframe_params['calendarLang'])) {
            $calendarLang = $iframe_params['calendarLang'];
        }

        $timezone = 'UTC';
        if (!empty($iframe_params['timezone'])) {
            $timezone = $iframe_params['timezone'];
        }

        $http_query_params['zone'] = $_IFRAME_COMPANY->encodeId($_ZONE->id());
        $applicationCalendarLink = $_IFRAME_COMPANY->getAppURL($_ZONE->val('app_type')).'calendar?'. http_build_query($http_query_params);

        //regionids=all&chapterid=all&calendarDefaultDate=02%2F01%2F2024&calendarDefaultView=dayGridMonth&zoneids=f1_1awpv3%2Cf1_1awpv4
        $showPrivateEventBlocks = false;
        // Print the contents of calendar.html into $content variable to allow us to cache.
        ob_start();
        include __DIR__ . '/views/calendar.html';
        $content = ob_get_clean();

        $_IFRAME_COMPANY->putInRedisCache($key,$content,300);
    }

    $_COMPANY = null; // Reset
    $_ZONE = null; // Reset

    // Set frame-ancestors CSP
    if ($iframe_params['ancestor_domain']) {
        header("Content-Security-Policy: frame-ancestors https://{$iframe_params['ancestor_domain']} "); // Add frame-ancestors required for iFrames.
    }
    return $content;
}
