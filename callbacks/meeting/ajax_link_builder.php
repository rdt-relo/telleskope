<?php
require_once __DIR__.'/../../include/init.php';

function echoCompressedJSONAndExit(array $arr) {
    $json = json_encode($arr);
    $json_zip = gzdeflate($json, 9); // Level 9 compression
    echo 'ZJSON:' . strtr(base64_encode($json_zip), '+/=', '._-');
    exit();
}

function echoURLAndExit(string $url) {
    echo 'URL:' . strtr(base64_encode($url), '+/=', '._-');
    exit();
}

function echoErrorAndExit(string $errorCode='Unknown Error') {
    echo 'ERRCODE:' . strtr(base64_encode($errorCode), '+/=', '._-');
    exit();
}

if (isset($_GET['createZoomMeetingLink']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	// Setting the essential data from zoom
	$clientKey = CLIENT_KEY_ZOOM;
	$clientSecret = CLIENT_SECRET_ZOOM;
    $redirectURL= BASEURL."/callbacks/meeting/zoom_meeting";
	$apiHost = "https://zoom.us/oauth/token";
    $apiMeetURL= "https://api.zoom.us/v2/users/me/meetings";
    $method = 'POST'; 
	$authtoken = $_POST['authToken'];
	// Setting the api data
	$apiData = array(
		'grant_type' => 'authorization_code',
		'redirect_uri' => $redirectURL,
		'code' => $authtoken
	);
	$authorization = base64_encode($clientKey . ':' . $clientSecret);
	$header = ['Authorization: Basic ' . $authorization, 'Accept: application/json'];

	// Check for the apiData to set the url accordingly
	$paramString = null;
	if (isset($apiData) && is_array($apiData)) {
		$paramString = '?' . http_build_query($apiData);
	}

    // Start curl request
	$ch = curl_init();
    
	curl_setopt($ch, CURLOPT_URL, $apiHost);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	// curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_POST, count($apiData));
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
	curl_setopt($ch, CURLOPT_POST, TRUE);

	$jsonData = curl_exec($ch);

	// Check for data error
	if (!$jsonData) {
        Logger::Log("Zoom Link Fatal Error (302): Unable to generate link, ".curl_error($ch));
        echoErrorAndExit();
	}
	curl_close($ch);
	$result = json_decode($jsonData);

    $access_token = $result->access_token;
    //Call for the meeting link

    $headerData = [
        'Authorization: Bearer ' . $access_token,
        'content-type: application/json',
        'Accept: application/json'
    ];
    // set the meeting json data
    $postFields = array(
    "agenda" => "Test Meeting",
    "default_password"=> true,
    "duration"=> 60,
    "pre_schedule"=> false,
    // "start_time"=> "2022-08-10T07:32:55Z",
    "template_id"=> "Dv4YdINdTk+Z5RToadh5ug==",
    "timezone"=> "America/Los_Angeles",
    "topic"=> "Test Meeting",
    "type"=> 2);
    $payload = json_encode($postFields);
    // Meeting curl request
    $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiMeetURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        // curl_setopt($ch, CURLOPT_HEADER, true);

        $jsonMeetingData = curl_exec($ch);
            // Check for data error
    if (!$jsonMeetingData) {
        Logger::Log("Zoom Link Fatal Error (303): Unable to generate link, ".curl_error($ch));
        echoErrorAndExit();
    }
    curl_close($ch);
    $meetingResult =  json_decode($jsonMeetingData);
    //Logger::Log("Zoom Link request got ". $jsonMeetingData);
    if (!isset($meetingResult->join_url)) {
        Logger::Log("Zoom Link Fatal Error (304): Unable to generate link > {$meetingResult}");
        echoErrorAndExit();
    }

    echoURLAndExit($meetingResult->join_url);
}

elseif (isset($_GET['createTeamsMeetingLink']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Setting the essential data for google meet
	$clientKey = CLIENT_KEY_TEAMS;
	$clientSecret = CLIENT_SECRET_TEAMS;
    $redirectURL= BASEURL."/callbacks/meeting/msteam_meeting";
	$apiHost = "https://login.microsoftonline.com/common/oauth2/v2.0/token";
    $method = 'POST'; 
	$authtoken = $_POST['authToken'];
	// Setting the api data
	$apiData = array(
        'code' => $authtoken,
        'client_id' => $clientKey,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectURL,
        'scope' => 'OnlineMeetings.ReadWrite',
		'grant_type' => 'authorization_code'
	);
	$header = ['Content-Type: application/x-www-form-urlencoded'];

	// Check for the apiData to set the url accordingly
	$paramString = null;
	if (isset($apiData) && is_array($apiData)) {
		$paramString = '?' . http_build_query($apiData);
	}

    // Start curl request
	$ch = curl_init();
    
	curl_setopt($ch, CURLOPT_URL, $apiHost);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_POST, count($apiData));
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
	curl_setopt($ch, CURLOPT_POST, TRUE);

	$jsonData = curl_exec($ch);

	// Check for data error
	if (!$jsonData) {
        Logger::Log("MSTeams Link Fatal Error (102): Unable to generate link, ".curl_error($ch));
        echoErrorAndExit('MS Teams connection error 001, please try again later');
	}
	curl_close($ch);
	$result = json_decode($jsonData);
    $access_token = $result->access_token;
    
    //Call for the meeting link

    $headerData = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
        
    ];
    // set event title
    $subject = !empty($_GET['title'])
        ? urldecode($_GET['title'])
        : "Meeting scheduled using ".htmlentities($_GET['domain'] ?? '');

    $noOfMeetingLinks = empty($_GET['link_count']) ? 1 : intval($_GET['link_count']);

    $meetingLinks = [];
    for ($i = 0; $i < $noOfMeetingLinks; $i++) {
        // set the meeting json data
        // As we iterate through the array add one day for each meeting to avoid MS team duplicate timeslot restriction
        $postFields = array(
                "startDateTime"=> gmdate('Y-m-d\Th:m:s.000000-00:00',time()-5*31536000 + 86400*$i), // 5 years ago
                "endDateTime"=> gmdate('Y-m-d\Th:m:s.000000-00:00',time()-5*31536000 + 86400*($i+1)), // 5 years ago + 1 day
            "subject"=> $subject
            );
        $payload = json_encode($postFields);
        $conferenceDataVersion = 1;
        $apiMeetURL= "https://graph.microsoft.com/v1.0/me/onlineMeetings";
        // Meeting curl request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiMeetURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        // curl_setopt($ch, CURLOPT_HEADER, true);

        $jsonMeetingData = curl_exec($ch);
        // Check for data error
        if (!$jsonMeetingData) {
            Logger::Log("MSTeams Link Fatal Error (103): Unable to generate link, ".curl_error($ch));
            echoErrorAndExit('MS Teams connection error 002, please try again later');
        }
        curl_close($ch);
        //Logger::Log("MSTeams Link request got ". $jsonMeetingData);
        $meetingResult =  json_decode($jsonMeetingData);
        if (!isset($meetingResult->joinUrl)) {
            Logger::Log("MSTeams Link Fatal Error (104): Unable to generate link > {$jsonMeetingData}");
            $errorCode = (isset($meetingResult->error) && isset($meetingResult->error->code)) ? $meetingResult->error->code : 'MS Teams Error';
            echoErrorAndExit($errorCode);
        }


        $meetingLinks[] = $meetingResult->joinUrl;
    }

    if ($noOfMeetingLinks == 1) { // Return the link as a string
        echoURLAndExit($meetingResult->joinUrl);
    } else { // Return links as JSON.
        // Place the meeting links in redis and return the hand
        echoCompressedJSONAndExit($meetingLinks);
    }

}

elseif (isset($_GET['createGoogleMeetingLink']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Setting the essential data for google meet
	$clientKey = CLIENT_KEY_GMEET;
	$clientSecret = CLIENT_SECRET_GMEET;
    $redirectURL= BASEURL."/callbacks/meeting/google_meeting";
	$apiHost = "https://oauth2.googleapis.com/token";
    $method = 'POST'; 
	$authtoken = $_POST['authToken'];
	// Setting the api data
	$apiData = array(
        'code' => $authtoken,
        'client_id' => $clientKey,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectURL,
        'scope' => 'https://www.googleapis.com/auth/calendar.events',
		'grant_type' => 'authorization_code'
	);
	$header = ['Content-Type: application/x-www-form-urlencoded'];

	// Check for the apiData to set the url accordingly
	$paramString = null;
	if (isset($apiData) && is_array($apiData)) {
		$paramString = '?' . http_build_query($apiData);
	}

    // Start curl request
	$ch = curl_init();
    
	curl_setopt($ch, CURLOPT_URL, $apiHost);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_POST, count($apiData));
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
	curl_setopt($ch, CURLOPT_POST, TRUE);

	$jsonData = curl_exec($ch);

	// Check for data error
	if (!$jsonData) {
        Logger::Log("GMeet Link Fatal Error (202): Unable to generate link, ".curl_error($ch));
        echoErrorAndExit();
	}
	curl_close($ch);

	$result = json_decode($jsonData);

    $access_token = $result->access_token;
    
    //Call for the meeting link

    $headerData = [
        'Authorization: Bearer ' . $access_token,
        'content-type: application/json',
        'Accept: application/json'
    ];
    $calendarId = "primary";
    // set the meeting json data
    $postFields = array(
        "calendarId" => "primary",
          "conferenceDataVersion" => 1,
          "sendNotifications" => false,
          "sendUpdates" => "none",
          "supportsAttachments" => false,
          "start" => array(
            // "timeZone" => "America/Los_Angeles",
            "date" => gmdate('Y-m-d',time()-5*31536000) // 5 years ago
        ),
        "end" => array(
            // "timeZone" => "America/Los_Angeles",
            "date" => gmdate('Y-m-d',time()-5*31536000+86400) // 5 years ago + 1 day
        ),
        "conferenceData" => array(
            "createRequest" => array(
              "conferenceSolutionKey" => array(
                "type"=>"hangoutsMeet"
              ),
              "requestId"=>"7qxalsvy0e"
            ),
          ),
        );

    $payload = json_encode($postFields);
    $conferenceDataVersion = 1;
    $apiMeetURL= "https://www.googleapis.com/calendar/v3/calendars/".$calendarId."/events?conferenceDataVersion=1";
    // Meeting curl request
    $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiMeetURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        // curl_setopt($ch, CURLOPT_HEADER, true);

        $jsonMeetingData = curl_exec($ch);
            // Check for data error
    if (!$jsonMeetingData) {
        Logger::Log("GMeet Link Fatal Error (203): Unable to generate link, " . curl_error($ch));
        echoErrorAndExit();
    }
    curl_close($ch);
    //Logger::Log("GMeet Link request got ". $jsonMeetingData);
    $meetingResult =  json_decode($jsonMeetingData);
    if (!isset($meetingResult->hangoutLink)) {
        Logger::Log("GMeet Link Fatal Error (204): Unable to generate link > {$jsonMeetingData}");
        echoErrorAndExit();
    }
    echoURLAndExit($meetingResult->hangoutLink);
}

else {
    Logger::Log ("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}

