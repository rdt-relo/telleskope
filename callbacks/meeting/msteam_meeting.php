<?php
// This is step 1 - executed under common domain e.g. dev.teleskope.io
require_once __DIR__.'/../../include/init.php';

// Validate if have a valid state to determine calling domain for redirect, if not valid state then error out.
if (empty($_GET['state'])) {
    header(HTTP_UNAUTHORIZED);
    die('Unathorized Access: Missing state');
}

$link_count = 1;

if (isset($_GET['state'])) {
	$decodedState = base64_decode(strtr($_GET['state'], '._-', '+/='));

	$stateParams = [];
	foreach (explode('&', $decodedState) as $pair) {
		$pair_parts = explode('=', $pair, 2);
        if (count($pair_parts) == 2) {
            $key = $pair_parts[0];
            $value = $pair_parts[1];
            $stateParams[$key] = urldecode($value);
        }
	}

	$redirectDomain = $stateParams['domain'] ?? '';
    $eventTitle = $stateParams['eventTitle'] ?? '';
	$scheduler =  $stateParams['scheduler'] ?? false;
    if ($scheduler) {
        $eventTitle = 'Meeting scheduled using scheduler';
        $link_count = 25;
    }


	if(!$redirectDomain) {
		die('Bad Request: Missing domain in state');
	}
	// Domain validation and logic
    $domain_parts = explode('.', $redirectDomain);
	if (count($domain_parts) < 3 || !in_array($domain_parts[1], array('affinities','talentpeak','officeraven','peoplehero'))) {
		header(HTTP_UNAUTHORIZED);
		die('Unathorized Access: Missing state');
	}
	$redirect_domain = $domain_parts[0] . '.' . $domain_parts[1] . '.io';
	
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generating Teams Meeting link - 1/2</title>
</head>
<body>
<p>Getting meeting link for <?= htmlspecialchars($redirect_domain)?> <span id="status1"> ...</span></p>
<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/jquery-3.5.1/dist/jquery.min.js"></script>
<script>
	window.onload = function () {

    let dotInterval;
    function addProgressDot() {
        const progressSpan = document.getElementById('status1');
        progressSpan.textContent += '.';
    }
    dotInterval = setInterval(addProgressDot, 1000);

	const params = new URLSearchParams(window.location.search);
	var authToken = params.get('code');
	// Send request with sign in token
	$.ajax({
		url: 'ajax_link_builder?createTeamsMeetingLink&link_count=<?=$link_count?>&title=<?=urlencode($eventTitle)?>&domain=<?=htmlspecialchars($redirect_domain)?>',
		type: 'POST',
		data: {authToken:authToken},
		success : function(data) {
            clearInterval(dotInterval);
            location.replace('https://<?=$redirect_domain?>/1/callbacks/meeting/<?= $scheduler ? 'redirect_to_caller_scheduler' : 'redirect_to_caller'; ?>?code=' + data);
		}
	});
}
</script>
</body>
</html>
