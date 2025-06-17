<?php
// This is step 1 - executed under common domain e.g. dev.teleskope.io
require_once __DIR__.'/../../include/init.php';

// Validate if have a valid state to determine calling domain for redirect, if not valid state then error out.
if (empty($_GET['state'])) {
    header(HTTP_UNAUTHORIZED);
    die('Unathorized Access: Missing state');
}
$state_parts = explode('.', $_GET['state']);
if (count($state_parts) < 3 || !in_array($state_parts[1], array('affinities','talentpeak','officeraven','peoplehero'))) {
    header(HTTP_UNAUTHORIZED);
    die('Unathorized Access: Missing state');
}
$redirect_domain = $state_parts[0] . '.' . $state_parts[1] . '.io';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Generating Google Meet link - 1/2</title>
</head>
<body>
<p>Getting meeting link for <?= htmlspecialchars($redirect_domain);?> <span id="status1"> ...</span></p>
<script src="../vendor/js/jquery-3.5.1/dist/jquery.min.js"></script>
<script>
	window.onload = function () {
	const params = new URLSearchParams(window.location.search);
    var authToken = params.get('code');
    // Send request with sign in token
    $.ajax({
        url: 'ajax_link_builder?createGoogleMeetingLink',
        type: 'POST',
        data: {authToken: authToken},
        success: function (data) {
            $("#status1").html(', recieved: ' + data);
            location.replace('https://<?=$redirect_domain?>/1/callbacks/meeting/redirect_to_caller?code=' + data);
        }
    });
}
</script>
</body>
</html>
