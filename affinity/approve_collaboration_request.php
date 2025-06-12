<?php
require_once __DIR__.'/head.php';

$success = 0;
$message = gettext("We are unable to process your request as the link has expired."); // Default message
$goToLink = Url::GetZoneAwareUrlBase($_ZONE->id()) . 'home';

if (!empty($_GET['token'])){
    $token = json_decode(aes_encrypt($_GET['token'], TELESKOPE_USERAUTH_API_KEY, 'u7KD33py3JsrPPfWCilxOxojsDDq0D3M', true),true);

    if (!empty($token)){
        $token_eventid = $token['eventid'];
        $token_groupid = $token['groupid'];

        if ( ($event = Event::GetEvent($token_eventid)) !== NULL ) {
            $goToLink = $baseurl . 'eventview?id=' . $_COMPANY->encodeId($event->id());
            $cgids = $event->val('collaborating_groupids');
            $cgidsPending = $event->val('collaborating_groupids_pending');
            $collaboratingGroupIds = empty($cgids) ? array() : explode(',', $cgids);
            $collaboratingGroupIdsPending = empty($cgidsPending) ? array() : explode(',', $cgidsPending);

            if ($event->isDraft() || $event->isUnderReview()){
                if (in_array($token_groupid,$collaboratingGroupIds)){
                    $message = gettext("This approval request was already processed. Thank you for showing interest.");
                    $success = 1;
                } elseif (!$_USER->canCreateOrPublishOrManageContentInScopeCSV($token_groupid)) {
                    $message = gettext("Your role does not have enough permissions to approve this event");
                } elseif (($key = array_search($token_groupid, $collaboratingGroupIdsPending)) !== false) {

                    $collaboratingGroupIds[] = $token_groupid; // Add it to collaborating list
                    unset($collaboratingGroupIdsPending[$key]); // Remove the value from pending list

                    if ($event->updateCollaboratingGroupids($collaboratingGroupIds, $collaboratingGroupIdsPending, explode(',',$event->val('chapterid')))){
                        $message = gettext("Approval request processed successfully.");
                        $success = 1;
                    } else {
                        $message = gettext("We are unable to process your request due to internal system error.");
                        $success = 0;
                    }
                }
            }
        }
    }
} 
include(__DIR__ . '/views/approve_collaboration_request.php');

?>


