<style>
    .fa.fa-envelope-open:hover {
        color: #0077b5 !important;
    }
    li > button:hover{
        background-color: gainsboro;
        color: inherit;
    }
</style>

<?php
global $_USER, $_COMPANY;
$opts = 0;
$eventid = $event->id();
$enc_eventid = $_COMPANY->encodeId($event->id());
$enc_groupid = $_COMPANY->encodeId($event->val('groupid'));
$enc_chapterid = $_COMPANY->encodeId(0);
$enc_channelid = $_COMPANY->encodeId($event->val('channelid'));

if ($event->val('form_validated') != 0){
    $isAllowedToUpdateContent = $event->val('groupid') ? $_USER->canUpdateContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid'), $event->val('isactive')) : $event->loggedinUserCanUpdateEvent();
        
    $isAllowedToPublishContent = $event->val('groupid') ? $_USER->canPublishContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')) : $event->loggedinUserCanPublishEvent();

    $isAllowedToManageContent = $event->val('groupid') ? $_USER->canManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')) : $event->loggedinUserCanManageEvent();

    if (!empty($event->val('collaborating_groupids'))) {
        $isAllowedToUpdateContent = $event->loggedinUserCanUpdateEvent();
        $isAllowedToPublishContent = $event->loggedinUserCanPublishEvent();
        $isAllowedToManageContent = $event->loggedinUserCanManageEvent();
    }

?>


    <?php if ($event->val('rsvp_enabled') && $_USER->canPublishContentInCompanySomething() && $event->isPublished() && !$event->hasEnded() && !$event->isSeriesEventSub()) { $opts++;
    ?> 
    <li><a class="" href="javascript:void(0);" onclick="inviteEventUsersForm('<?= $enc_eventid ?>')"><i class="fa fa-envelope-open" aria-hidden="true"></i>&emsp;<?= gettext("Invite Users"); ?></a></li>
    <?php } ?>

    <?php if ($event->isPublished()) { $opts++; ?>
    <li><a class="" href="javascript:void(0);" onclick="getShareableLink('<?= $enc_groupid; ?>','<?=$enc_eventid ?>','2')"><i class="fa fa-share-square" aria-hidden="true"></i>&emsp;<?= gettext("Get Shareable Link"); ?></a></li>
    <?php } ?>

    <?php if ($event->val('rsvp_enabled') && ($_COMPANY->getAppCustomization()['event']['checkin']) && ($event->isEventContributor() ||  ($isAllowedToPublishContent || $isAllowedToManageContent)) && ($event->val('max_inperson')>=0 || $event->val('max_online') >=0) && $event->isPublished() && !$event->isSeriesEventHead()) { $opts++; ?>
        <li><a class="" href="javascript:void(0);" onclick="manageWaitList('<?= $enc_eventid ?>');"><i class="fa fas fa-door-open" aria-hidden="true"></i>&emsp;<?= gettext("Manage RSVP List"); ?></a></li>
    <?php } ?>

    <?php if ($event->val('rsvp_enabled') && ($event->isPublished() || $event->isCancelled()) && ($event->isEventContributor() || $isAllowedToManageContent)) { $opts++; ?>
        <li><a class="js-download-link" href="ajax_events?exportAttendeesToExcel=<?= $enc_eventid ?>" > <i class="fa fa-download" aria-hidden="true"></i>&emsp;<?= gettext("Download RSVP List"); ?></a></li>
    <?php } ?>


    <?php if ($event->val('rsvp_enabled') && $_COMPANY->getAppCustomization()['event']['checkin'] && $event->isPublished()  && ($event->isEventContributor() || $event->loggedinUserCanUpdateOrPublishOrManageEvent()) && !$event->isSeriesEventHead()) { $opts++; ?>
        <li><a class="" href="javascript:void(0);" onclick="eventRSVPsForCheckIn('<?= $enc_eventid ?>','')"><i class="fa fa-check-square"></i>&emsp;<?= gettext("Check In"); ?></a></li>
    <?php } ?>

    <?php if ($_COMPANY->getAppCustomization()['event']['analytics'] && $event->isPublished() &&  $isAllowedToManageContent && !$event->isSeriesEventHead()) { $opts++; ?>
    <li><a class="" href="event_analytics?groupid=<?= $enc_groupid ?>&eventid=<?= $enc_eventid ?>"><i class="fa fas fa-chart-pie" aria-hidden="true"></i>&emsp;<?= gettext("Analytics"); ?></a><li>
    <?php } ?>

    <?php if (0 && $event->canUpdateEventExpenseEntry()) { $opts++; ?>
    <li><a href="javascript:void(0);" onclick="manageEventExpenseEntries('<?= $enc_eventid; ?>'); "><i class="fa fas fa-wallet"></i>&emsp; <?= gettext('Manage Event Expense'); ?><?php //$event->getEventBudgetedDetail(); ?></a></li>
    <?php } ?>

    <?php if (0 && $_COMPANY->getAppCustomization()['event']['volunteers'] && ($event->loggedinUserCanManageEvent() || $event->loggedinUserCanUpdateEvent() || $event->loggedinUserCanPublishEvent()) && !$event->hasEnded() && !$event->isSeriesEventHead()) { $opts++; ?>
    <li><a class="" href="javascript:void(0);" onclick="manageVolunteers('<?= $enc_eventid ?>')" ><i class="fa fa-person-booth" aria-hidden="true"></i>&emsp;<?= gettext("Manage Volunteers"); ?></a></li>
    <?php } ?>
<?php } ?>

<?php if (!$opts){  ?>
<li><button class="btn-list-item"><i class="fa fas fa-exclamation" aria-hidden="true"></i>&emsp;<?= gettext("No options available"); ?></button></li>
<?php } ?>