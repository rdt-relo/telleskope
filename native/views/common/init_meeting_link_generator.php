<?php

// This common file is to generate methods that can be used for generating various meeting links.
function generateMeetingLinkOptionsHTML()
{
    global $_COMPANY, $_USER;
    $my_link = $_USER->getUserPreference(UserPreferenceType::MyWebConferenceURL);
    $my_link_detail = $_USER->getUserPreference(UserPreferenceType::MyWebConferenceDetail);
    if (in_array(true, array_values($_COMPANY->getAppCustomization()['event']['meeting_links'])) || !empty($my_link)) { // Only show link options if atleast one of the options is enabled
        $show_seperator = false;
        echo '<div class="col-12 px-0">';
        echo '    <div class="col-sm-3 px-0">' . gettext('Generate link using:') . '</div>';
        echo '    <div class="col-sm-9 px-0 text-left">';
        if ($my_link || $my_link_detail) {
            $show_seperator = true;
            echo '    <a onclick="CopyMyWebConfURL(\'' . $my_link . '\', \'' . $my_link_detail . '\');return false;" href="#" style="cursor:pointer; padding: 0 12px;"><span style="padding: 0 3px;">' . gettext('My meeting link') . '</span></a>';
        }
        if ($_COMPANY->getAppCustomization()['event']['meeting_links']['msteams']) {
            if ($show_seperator) {
                echo '<span style="color:lightgray;">|</span>';
            }
            $show_seperator = true;
            echo '    <a onclick="TeamsSignIn()" style="cursor:pointer; padding: 0 12px;"><img src="../image/microsoft-team.png" height="22"><span style="padding: 0 3px;">Teams</span></a>';
        }
        if ($_COMPANY->getAppCustomization()['event']['meeting_links']['gmeet']) {
            if ($show_seperator) {
                echo '<span style="color:lightgray;">|</span>';
            }
            $show_seperator = true;
            echo '    <a id="GoogleSignIn" role="button" tabindex="0" onclick="GoogleSignIn()" style="cursor:pointer; padding: 0 12px;"><img src="../image/google-meet.png" height="22"><span style="padding: 0 3px;">Google Meet</span></a>';
        }
        if ($_COMPANY->getAppCustomization()['event']['meeting_links']['zoom']) {
            if ($show_seperator) {
                echo '<span style="color:lightgray;">|</span>';
            }
            $show_seperator = true;
            echo '    <a id="ZoomSignIn" role="button" tabindex="0" onclick="ZoomSignIn()" style="cursor:pointer;padding: 0 12px;"><img src="../image/meetingzoom.png" height="22"><span style="padding: 0 3px;">Zoom</span></a>';
        }
        echo '    </div>';
        echo '</div>';
    }
}

?>

<script>
    function ZoomSignIn() {
        let clientKey = "<?= CLIENT_KEY_ZOOM ?>";
        let redirectURL = "<?= BASEURL ?>/callbacks/meeting/zoom_meeting";
        let redirectState = "<?= $_COMPANY->getAppDomain($_ZONE->val('app_type')) ?>";
        let strWindowFeatures = "location=yes,height=570,width=520,scrollbars=yes,status=yes";
        let URL = 'https://zoom.us/oauth/authorize?response_type=code&client_id=' + clientKey + '&redirect_uri=' + redirectURL + '&state=' + redirectState;
        win = window.open(URL, "_blank", strWindowFeatures);
        win.focus();
    }

    function GoogleSignIn() {
        let scope = 'https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/calendar.events';
        let clientKey = "<?= CLIENT_KEY_GMEET ?>";
        let redirectURL = "<?= BASEURL ?>/callbacks/meeting/google_meeting";
        let redirectState = "<?= $_COMPANY->getAppDomain($_ZONE->val('app_type')) ?>";
        let strWindowFeatures = "location=yes,height=570,width=520,scrollbars=yes,status=yes";
        let URL = 'https://accounts.google.com/o/oauth2/v2/auth?scope=' + scope + '&response_type=code&access_type=online&redirect_uri=' + redirectURL + '&client_id=' + clientKey + '&state=' + redirectState;
        win = window.open(URL, "_blank", strWindowFeatures);
        win.focus();
    }

    function TeamsSignIn() {
        let clientKey = '<?= CLIENT_KEY_TEAMS ?>';
        let redirectURL = "<?= BASEURL ?>/callbacks/meeting/msteam_meeting";
        let eventTitleForMeet = encodeURIComponent(document.getElementById('eventtitle').value.slice(0,255));
        let domainValue = encodeURIComponent('<?= $_COMPANY->getAppDomain($_ZONE->val('app_type')) ?>');

        let stateValue = `domain=${domainValue}&eventTitle=${eventTitleForMeet}`;
        let redirectState =  btoa(stateValue).replace(/\+/g, '.').replace(/\//g, '_').replace(/=/g, '-');
        let strWindowFeatures = "location=yes,height=570,width=520,scrollbars=yes,status=yes";
        let URL = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?scope=OnlineMeetings.ReadWrite&response_type=code&response_mode=query&redirect_uri=' + redirectURL + '&client_id=' + clientKey + '&state=' + redirectState;
        win = window.open(URL, "_blank", strWindowFeatures);
        win.focus();
    }

    function TeamsSignInScheduler() {
        let clientKey = '<?= CLIENT_KEY_TEAMS ?>';
        let redirectURL = "<?= BASEURL ?>/callbacks/meeting/msteam_meeting";
        let eventTitleForMeet = encodeURIComponent('Scheduled Meeting');
        let domainValue = encodeURIComponent('<?= $_COMPANY->getAppDomain($_ZONE->val('app_type')) ?>');

        let stateValue = `domain=${domainValue}&eventTitle=${eventTitleForMeet}&scheduler=true`;
        let redirectState =  btoa(stateValue).replace(/\+/g, '.').replace(/\//g, '_').replace(/=/g, '-');
        let strWindowFeatures = "location=yes,height=570,width=520,scrollbars=yes,status=yes";
        let URL = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?scope=OnlineMeetings.ReadWrite&response_type=code&response_mode=query&redirect_uri=' + redirectURL + '&client_id=' + clientKey + '&state=' + redirectState;
        win = window.open(URL, "_blank", strWindowFeatures);
        win.focus();
    }

    function CopyMyWebConfURL(l,d) {
        $("#web_conference_link").val(l);
        $("#web_conference_detail").val(d);
    }
</script>