<?php
// This is step 2 - executed under calling domain e.g. subdomain.affinities.io or subdomain.talentpeak.io
// Here we take the passed code and update the web_conference_link in the parent window
require_once __DIR__.'/../../include/init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Generating Meeting link - 2/2</title>
</head>
<body>

    <?php
    if (empty($_GET['code']) || (str_starts_with($_GET['code'],'ERRCODE:'))) {
        if (str_starts_with($_GET['code'],'ERRCODE:')) {
            $errorCode = str_replace('ERRCODE:', '', $_GET['code']);
            $errorCode = base64_decode(strtr($errorCode, '._-', '+/='));
        }
    ?>
        <h4>Error generating link. This window will automatically close in <span id="closeInterval">15</span> seconds </h4>
        <br>
        <p>-----</p>
        <p style="color:red;">Error Code = <?=$errorCode ?></p>
        <p>-----</p>
    <script>
        let timeout = setTimeout(function () { window.close();}, 15000);
    </script>

    <?php
    }
    elseif (str_starts_with($_GET['code'],'URL:')) {
        $url = str_replace('URL:', '', $_GET['code']);
        $url = base64_decode(strtr($url, '._-', '+/='));
        $note_for_meeting_title = gettext('The generated Teams link will show the current event title as the Teams meeting title. If you change the event title in the future, please regenerate the Teams link to update the Teams meeting title.');
    ?>
    <p style="color:green;">Getting meeting link<span id="status1"> done, redirecting</span></p>
    <script>
        window.onload = function () {
            window.opener.document.getElementById("web_conference_link").value = '<?=$url?>';
            window.opener.document.getElementById("web_conference_link_note").innerText = '<?=$note_for_meeting_title?>';
            window.close();
        }
    </script>
    <?php } ?>
</body>
</html>

<script type="text/javascript">
    function updateCloseTime() {
        const ci = document.getElementById("closeInterval");
        var t = ci.innerText;
        t--;
        ci.textContent = t;
        if (t <= 0) {
            clearInterval(closeInterval);
        }
    }

    updateCloseTime(); // run function once at first to avoid delay
    var closeInterval = setInterval(updateCloseTime, 1000);
</script>