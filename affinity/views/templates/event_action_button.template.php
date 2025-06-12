<style>
    .fa.fa-envelope-open:hover{
	color: #0077b5 !important;
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
$in_user_view = (basename($_SERVER['PHP_SELF']) =='eventview.php') ? true : false;
$showPublishButton = true;
$eventActionBtn = ($event->isPublished()) ? gettext('Cancel Event') : gettext('Delete Event');
if ($event->isSeriesEventHead()) {
        $eventActionBtn = ($event->isPublished()) ? gettext('Cancel Event Series') : gettext('Delete Event Series');     
}
$topicType = TELESKOPE::TOPIC_TYPES['EVENT'];
$topicTypelabel = $event->isSeriesEventHead() ? gettext('Event Series') : gettext('Event');

$cloneAllowed = true;
if ($event->isSeriesEventSub()) {
    $seriesHead = Event::GetEvent($event->val('event_series_id'));
    if ($seriesHead->isCancelled()) {
        $cloneAllowed = false;
    }
}
$isRequestApprovalAndPublishAllowed = true;
if ($event->isSeriesEventHead() && $event->hasNoEventsOrIncompleteEventsInSeries()) {
    $isRequestApprovalAndPublishAllowed = false;
}
?>

<?php if ($event->val('form_validated') == 0 && !$event->isSeriesEventHead()){ // This check is needed. Feature #3391 allows users to create an event without filling in all the details.
?>

    <?php if (!$event->isAwaiting() && $isAllowedToUpdateContent && !$event->isSeriesEventHead()) { $opts++; ?>
        <li>
            <a role="button" href="javascript:void(0);" class=""
            <?php if ($event->isActive() && !$event->areEventSpeakersApproved()) { ?>
                onclick="swal.fire({text:'<?= addslashes(gettext("Error: This event has one or more speakers who are not approved. Please request event speaker approval first. You can request event speaker approval by choosing Manage Event Speakers from the Event options and then clicking on Request Approval for each speaker who is not yet approved."));?>'})"
            <?php } else { ?>
                onclick="updateEventForm('<?= $enc_eventid ?>',false, '<?= $_COMPANY->encodeId($parent_groupid); ?>')"
            <?php } ?>
            >
                <i class="fa fas fa-edit" aria-hidden="true"></i>&emsp;<?= gettext("Edit"); ?>
            </a>
        </li>
    <?php } ?>

    <?php if (($isAllowedToUpdateContent || $isAllowedToPublishContent) && !$event->isAwaiting()) { $opts++; ?>
        <li>
            <?php if ($event->isCancelled()) { ?>
            <a role="button" href="javascript:void(0);">
                <i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= gettext('Cancelled Event'); ?>
            </a>
            <?php }else{ ?>
            <a role="button" href="javascript:void(0);" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= sprintf(gettext("Are you sure you want to %s"), $eventActionBtn); ?>"
                <?php if ($event->isSeriesEventHead()) { ?>
                onclick="deleteEventSeriesGroup('<?= $enc_groupid; ?>','<?= $enc_eventid; ?>', '<?= $in_user_view ?>', <?= ($event->val('groupid')== 0)?'true':'false' ?>, '<?= $event->val('isactive')==1 ?>');"
                <?php } else { ?>
                onclick="deleteEvent('evnt<?= $eventid; ?>','<?= $enc_eventid ?>','<?= $enc_groupid; ?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>', '<?= $in_user_view ?>', <?= ($event->val('groupid')== 0)?'true':'false' ?>, '<?= $event->val('isactive')==1 ?>',<?=$event->hasEnded()?1:0; ?>, <?= $event->getEventBudgetedDetail(false) ? 1 : 0 ?>, <?= $event->val('publish_to_email') ? 1 : 0 ?>, <?= $event->sendIcal() ? 1 : 0 ?>);"
                <?php } ?>
            >
                <i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= ucfirst($eventActionBtn); ?>
            </a>
            <?php } ?>
        </li>

    <?php } ?>

<?php } else { //Version check Else start ?>


<?php if ($_COMPANY->getAppCustomization()['event']['pinning']['enabled'] && $isAllowedToPublishContent && $event->isActive() && !$event->isPrivateEvent() && !$event->isSeriesEvent()){ $opts++; ?>
    <?php if ($event->val('pin_to_top')) { ?>
    <li><a role="button" href="javascript:void(0);" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to unpin this event?'); ?>" onclick="pinUnpinEvent('<?=$enc_eventid ?>','0')"><i class="fa fa-thumbtack" aria-hidden="true"></i>&emsp;<?= gettext('Unpin Event'); ?></a></li>
    <?php } else { ?>
    <li><a role="button" href="javascript:void(0);" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to pin this event to show on top'); ?>?" onclick="pinUnpinEvent('<?=$enc_eventid ?>','1')"><i class="fa fa-thumbtack" aria-hidden="true"></i>&emsp;<?= gettext('Pin Event'); ?></a></li>
    <?php } ?>
<?php } ?>

<?php if ($event->val('event_series_id') && !($event->isSeriesEventHead() && $in_user_view)) { $opts++; ?>
    <li><a role="button" class="" href="javascript:void(0);" onclick="getEventDetailModal('<?= $_COMPANY->encodeId($event->val('event_series_id'));?>','<?= $_COMPANY->encodeId(0);?>','<?= $_COMPANY->encodeId(0);?>')"><i class="fa far fa-eye" aria-hidden="true"></i>&emsp;<?= gettext("View Event Series"); ?></a><li>
<?php } ?>

<?php if (!$event->isAwaiting() && ($isAllowedToUpdateContent || $isAllowedToManageContent) && $event->val('event_series_id') && !$in_user_view) { $opts++; ?>
    <li><a role="button" href="javascript:void(0);" class="" onclick="manageSeriesEventGroup('<?=$enc_groupid?>','<?= $_COMPANY->encodeId($event->val('event_series_id')) ?>')"><i class="fa fas fa-edit" aria-hidden="true"></i>&emsp;<?= gettext("Manage Event Series"); ?></a></li>
<?php } ?>

<?php if (!$event->isAwaiting() && !$event->isCancelled() && $isAllowedToUpdateContent && !$event->isSeriesEventHead()) { $opts++; ?>
    <li>
        <a role="button" href="javascript:void(0);" class=""
           <?php if ($event->isActive() && !$event->areEventSpeakersApproved()) { ?>
               onclick="swal.fire({text:'<?= addslashes(gettext("Error: This event has one or more speakers who are not approved. Please request event speaker approval first. You can request event speaker approval by choosing Manage Event Speakers from the Event options and then clicking on Request Approval for each speaker who is not yet approved."));?>'})"
           <?php } else { ?>
               onclick="updateEventForm('<?= $enc_eventid ?>',false, '<?= $_COMPANY->encodeId($parent_groupid); ?>')"
           <?php } ?>
        >
            <i class="fa fas fa-edit" aria-hidden="true"></i>&emsp;<?= gettext("Edit"); ?>
        </a>
    </li>
<?php } ?>

<?php if ($cloneAllowed && $event->loggedinUserCanUpdateEvent() && !$event->isSeriesEventHead() && !$in_user_view) { $opts++; ?>
    <li><a role="button"
            <?= $event->val('event_series_id') ?
                ' class="confirm" data-confirm-noBtn="'.gettext('No').'" data-confirm-yesBtn="'.gettext('Yes').'" title="'. gettext("This event is part of a event series, cloning it will add the cloned event to the series. Do you want to continue?").'"': ''?>
                title="<?= gettext("Clone"); ?>" href="javascript:void(0);" onclick='cloneEvent("<?= $enc_eventid; ?>","<?= $_COMPANY->encodeId(2) ?>", "<?= $_COMPANY->encodeId($parent_groupid); ?>")'>
            <i class="fa fa-clone" aria-hidden="true"></i>&emsp;<?= gettext("Clone"); ?>
        </a>
    </li>
<?php } ?>

<?php if (0) { /* Disabled on 11/03/23 */ if (($event->isDraft() || $event->isUnderReview()) && !$event->isSeriesEvent() && $isAllowedToUpdateContent && $event->val('chapterid') == '0' && $event->val('channelid') == '0'){ $opts++; ?>
    <li><a role="button" href="javascript:void(0);" class=" "onclick="openCollaborationInviteModal('<?=$enc_eventid ?>')" ><i class="fa fa-cubes" aria-hidden="true"></i>&emsp;<?= gettext('Collaborate'); ?></a><li>
<?php }} ?>

<?php if ($event->val('rsvp_enabled') && $event->isPublished() && ($isAllowedToPublishContent || $isAllowedToManageContent) && !$event->hasEnded() ) { $opts++; ?>
<li><a role="button" class="" href="javascript:void(0);" onclick="viewReminderHistory('<?= $enc_eventid ?>');"><i class="fa fa-bell" aria-hidden="true"></i>&emsp;<?= gettext("Send a Reminder"); ?></a></li>
<?php } ?>


<?php if ($_COMPANY->getAppCustomization()['event']['checkin'] && $isAllowedToUpdateContent && $event->hasEnded() && $event->isPublished()) { $opts++; ?>
    <li><a role="button" class="" href="javascript:void(0);" onclick="updateEventFollowUpNoteForm('<?= $enc_eventid; ?>');"><i class="fa fa-flag" aria-hidden="true"></i>&emsp;<?= gettext("Post Event Follow-up"); ?></a></li>
<?php } ?>

<?php if ($_COMPANY->getAppCustomization()['event']['checkin'] && $isAllowedToUpdateContent && $event->hasEnded() && $event->isPublished() && !$event->isSeriesEventHead()) { $opts++; ?>
    <li>
        <a role="button" class="" href="javascript:void(0);" onclick="updateEventRecordingLink('<?=$enc_eventid ?>')">
        <i class="fa fa-video" aria-hidden="true"></i>&emsp;<?= $event->val('event_recording_link') ? gettext('Update Event Recording Link') : gettext('Add Event Recording Link') ?>
        </a>
    </li>
<?php } ?>

<?php if ($event->val('rsvp_enabled') && $_USER->canPublishContentInCompanySomething() && $event->isPublished() && !$event->hasEnded() && !$event->isSeriesEventSub()) { $opts++;  //Removed  && ($event->val('channelid') == 0) check by HIM as per Daily Engineering call discussion with team on 24th Aug, 2022
?> 
<li><a role="button" class="" href="javascript:void(0);" onclick="inviteEventUsersForm('<?= $enc_eventid ?>')"><i class="fa fa-envelope-open" aria-hidden="true"></i>&emsp;<?= gettext("Invite Users"); ?></a></li>
<?php } ?>

<?php if ($event->isPublished()) { $opts++; ?>
<li><a role="button" class="" href="javascript:void(0);" onclick="getShareableLink('<?= $enc_groupid; ?>','<?=$enc_eventid ?>','2')"><i class="fa fa-share-square" aria-hidden="true"></i>&emsp;<?= gettext("Get Shareable Link"); ?></a></li>
<?php } ?>

<?php if ( 0 && ($isAllowedToPublishContent || $isAllowedToManageContent) && !$event->hasEnded() && !$event->val('external_facing_event') ) { $opts++; ?>
    <li><a role="button" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to enable external link?"); ?>" href="javascript:void(0);" onclick="updateEventExternalFacing('<?= $enc_eventid ?>','<?= $_COMPANY->encodeId(1)?>');"><i class="fa fa-link" aria-hidden="true"></i>&emsp;<?= gettext("Enable External Link"); ?></a></li>
<?php } ?>

<?php if ( 0 &&  ($isAllowedToPublishContent || $isAllowedToManageContent) && $event->val('external_facing_event') ) { $opts++; ?>
    <li><a role="button" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to disable external link?"); ?>" href="javascript:void(0);" onclick="updateEventExternalFacing('<?= $enc_eventid ?>','<?= $_COMPANY->encodeId(0)?>');"><i class="fa fa-times" aria-hidden="true"></i>&emsp;<?= gettext("Disable External Link"); ?></a></li>
<?php } ?>

<?php if (($event->isDraft() || $event->isUnderReview()) && ( $isAllowedToPublishContent || $isAllowedToUpdateContent)) { $opts++; ?>
<li><a role="button" class=" " href="javascript:void(0);" onclick="openEventReviewModal('<?= $enc_groupid; ?>','<?= $enc_eventid ?>');"><i class="fa fa-tasks" aria-hidden="true"></i>&emsp;<?= gettext("Email Review"); ?></a></li>
<?php } ?>

<?php if (($event->loggedinUserCanManageEvent() || $event->loggedinUserCanUpdateEvent() || $event->loggedinUserCanPublishEvent()) && $event->isPublished() && $event->hasEnded() && !$event->isSeriesEventHead()) { ?>
<?php
        if ($event->val('is_event_reconciled')) {
            $title = gettext('Are you sure you want to undo reconciliation?');
            $reconcile_btn_lable = gettext('Undo Reconciliation');
        } else {
            $title = gettext('Are you sure you want to reconcile the event?');
            $reconcile_btn_lable = gettext('Reconcile Event');
        }
?>
 <?php  if($_COMPANY->getAppCustomization()['event']['reconciliation']['enabled']){ $opts++; ?>
<li><a role="button" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= $title ?>" href="javascript:void(0);" onclick="reconcileEvent('<?= $enc_eventid ?>');"><i class="fa fa-adjust" aria-hidden="true"></i>&emsp;<?= $reconcile_btn_lable ?></a></li>
<?php } ?>
<?php } ?>

<?php if ($_COMPANY->getAppCustomization()['event']['enable_event_surveys'] && !$event->isSeriesEventHead() && ($event->isPublished() || $event->isDraft() || $event->isUnderReview()) && ( $isAllowedToPublishContent || $isAllowedToUpdateContent)) { $opts++; ?>
<li><a role="button" class=" " href="javascript:void(0);" onclick="manageEventSurvey('<?= $enc_eventid ?>');"><i class="fa fa-poll" aria-hidden="true"></i>&emsp;<?= gettext("Manage Survey"); ?></a></li>
<?php } ?>

<?php if ($isRequestApprovalAndPublishAllowed  && $_COMPANY->getAppCustomization()['event']['approvals']['enabled'] && !$event->isSeriesEventSub())   { $showPublishButton = false; ?>

    <?php if(!empty($approval) && (!$event->isCancelled())){ $opts++; ?>
        <li><a role="button" class=" " href="javascript:void(0);" role="link"
               <?php
                /* onclick="viewApprovalStatus('<?= $enc_eventid ?>')" */ # commented as part of 4092 to show full event approval details using viewApprovalDetail
               ?>
               onclick="viewApprovalDetail('<?= $_COMPANY->encodeId($approval->val('topicid'))?>','<?= $_COMPANY->encodeId($approval->val('approvalid'))?>','<?=$topicType?>','<?=$topicTypelabel?>', 'event_action')"
            >
                <i class="fa fa-check-square" aria-hidden="true"></i>&emsp;<?= gettext("View Approval Status"); ?>
            </a>
        </li>
    <?php } ?>
    <?php if(!empty($approval) && ($approval->isApprovalStatusProcessing() || $approval->isApprovalStatusRequested())){?>
        <li><a role="button" class=" " href="javascript:void(0);" role="link" onclick="cancelApprovalStatus('<?= $enc_eventid?>','<?=$topicType?>')"><i class="fa fa-times" aria-hidden="true"></i>&emsp;<?= gettext("Cancel Approval Request"); ?></a></li>
    <?php } ?>
    <?php if((empty($approval) || $approval->isApprovalStatusDenied() || $approval->isApprovalStatusReset() || $approval->isApprovalStatusCancelled()) && ($isAllowedToUpdateContent || $isAllowedToPublishContent) && (!$event->isPublished() && !$event->isAwaiting() && !$event->isCancelled())){ $opts++; ?>
        <li><a role="button" class=" " href="javascript:void(0);" onclick="openApprovalNoteModal('<?= $enc_eventid ?>');"><i class="fa fa-check-square" aria-hidden="true"></i>&emsp;<?= empty($approval) ? gettext("Request Approval") : gettext("Request Approval Again"); ?></a></li>
    <?php }?>

    <?php if (!empty($approval) && $approval->isApprovalStatusApproved() ){ $showPublishButton =true; }?>

<?php } ?>

<?php if ($isRequestApprovalAndPublishAllowed && $isAllowedToPublishContent && !$event->isSeriesEventSub()  && !$event->isPublished() && !$event->isAwaiting() && !$event->isCancelled()) {
    $opts++;
    // Disclaimer check
    $checkDisclaimerExists = Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['EVENT_PUBLISH_BEFORE'], $event->val('groupid'));
    if(/*$showPublishButton && - not needed as we will do a check at later stage */$checkDisclaimerExists){
        $call_method_parameters = array(
            $enc_groupid,
            $enc_eventid,
        );
        $call_other_method = base64_url_encode(json_encode(
            array (
                "method" => "getEventScheduleModal",
                "parameters" => $call_method_parameters
            )
        ));
        $onClickFunc = "loadDisclaimerByHook('".$_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['EVENT_PUBLISH_BEFORE'])."','".$enc_groupid."', 0, '".$call_other_method."');";
    }else{
        $onClickFunc = "getEventScheduleModal('".$enc_groupid ."', '".$enc_eventid."');";
    }
    ?>
<li><a role="button" class="" href="javascript:void(0);" onclick="<?= $onClickFunc ?>"><i class="fa fa-mail-bulk" aria-hidden="true"></i>&emsp;<?= gettext("Publish"); ?></a></li>
<?php } elseif ($event->isAwaiting() && $isAllowedToPublishContent && !$event->isSeriesEventSub()) { $opts++; ?>
<li><a role="button" href="javascript:void(0);" class="" onclick="cancelEventPublishing('<?= $enc_groupid; ?>','<?= $enc_eventid ?>');"><i class="fa fas fa-times" aria-hidden="true"></i>&emsp;<?= gettext("Cancel Publishing"); ?></a></li>
<?php } ?>


<?php  if ($event->val('rsvp_enabled') && ($_COMPANY->getAppCustomization()['event']['rsvp_display']['allow_updates']) && ($isAllowedToPublishContent || $isAllowedToManageContent) && !$event->isSeriesEventHead() && !$event->isAwaiting() && !$event->isCancelled()) { $opts++; ?>
    <li><a role="button" href="javascript:void(0);" class="" title="<?= gettext("Are you sure you want to publish RSVP list?"); ?>" onclick="updateRSVPsListSettingModal('<?= $enc_eventid ?>')" ><i class="fa fa-list-alt" aria-hidden="true"></i>&emsp;<?= gettext("Publish RSVP List"); ?></a></li>
<?php } ?>

<?php if ($event->val('rsvp_enabled') && ($_COMPANY->getAppCustomization()['event']['checkin']) && ($isAllowedToPublishContent || $isAllowedToManageContent) && ($event->val('max_inperson')>=0 || $event->val('max_online') >=0) && $event->isPublished() && !$event->isSeriesEventHead()) { $opts++; ?>
    <li><a role="button" class="" href="javascript:void(0);" onclick="manageWaitList('<?= $enc_eventid ?>');"><i class="fa fas fa-door-open" aria-hidden="true"></i>&emsp;<?= gettext("Manage RSVP List"); ?></a></li>
<?php } ?>

<?php if ($event->val('rsvp_enabled') && ($event->isPublished() || $event->isCancelled()) && $isAllowedToManageContent) { $opts++; ?>
    <li><a role="button" class="js-download-link" href="ajax_events?exportAttendeesToExcel=<?= $enc_eventid ?>" > <i class="fa fa-download" aria-hidden="true"></i>&emsp;<?= gettext("Download RSVP List"); ?></a></li>
<?php } ?>

<?php if ($event->val('rsvp_enabled') && $_COMPANY->getAppCustomization()['event']['checkin'] && $event->isPublished()  && $event->loggedinUserCanUpdateOrPublishOrManageEvent() /*&& $event->hasCheckinStarted() && !$event->hasCheckinEnded()*/ && !$event->isSeriesEventHead() && !$in_user_view) { $opts++; ?>
    <li><a role="button" class="" href="javascript:void(0);" onclick="eventRSVPsForCheckIn('<?= $enc_eventid ?>','')"><i class="fa fa-check-square"></i>&emsp;<?= gettext("Check In"); ?></a></li>
<?php } ?>

<?php if ($_COMPANY->getAppCustomization()['event']['analytics'] && $event->isPublished() &&  $isAllowedToManageContent && !$event->isSeriesEventHead()) { $opts++; ?>
<li><a role="button" class="" href="event_analytics?groupid=<?= $enc_groupid ?>&eventid=<?= $enc_eventid ?>"><i class="fa fas fa-chart-pie" aria-hidden="true"></i>&emsp;<?= gettext("Analytics"); ?></a><li>
<?php } ?>

<?php if ($_COMPANY->getAppCustomization()['event']['email_tracking']['enabled'] && ($event->isPublished() || $event->isCancelled()) && ($isAllowedToManageContent || $isAllowedToPublishContent) && !$event->isSeriesEventSub()) { $opts++; ?>
<li><a role="button" class="" href="javascript:void(0);" onclick="getEmailLogstatistics('<?= $enc_groupid; ?>','<?= $enc_eventid ?>', '<?=  $_COMPANY->encodeId(2); ?>')" ><i class="fa far fa-chart-bar" aria-hidden="true"></i>&emsp;<?= gettext("Email Tracking"); ?></a></li>
<?php } ?>

<?php if ($_COMPANY->getAppCustomization()['event']['speakers']['enabled'] && !$event->isCancelled() && ($event->loggedinUserCanUpdateEvent() || $event->loggedinUserCanPublishEvent()) && ($event->isDraft() || $event->isUnderReview() || !$event->hasCheckinStarted() || !$event->areEventSpeakersApproved()) && !$event->isSeriesEventHead() && !$event->isAwaiting()) { $opts++; ?>
    <li><a role="button" class="" href="javascript:void(0);" onclick="manageEventSpeakers('<?= $enc_eventid ?>')"><i class="fa fa-microphone" aria-hidden="true"></i>&emsp;<?= gettext("Manage Event Speakers"); ?></a></li>
 <?php } ?>

<?php if ($_COMPANY->getAppCustomization()['event']['volunteers'] && !$event->isCancelled() && ($event->loggedinUserCanManageEvent() || $event->loggedinUserCanUpdateEvent() || $event->loggedinUserCanPublishEvent()) && !$event->hasEnded() && !$event->isSeriesEventHead()) { $opts++; ?>
<li><a role="button" class="" href="javascript:void(0);" onclick="manageVolunteers('<?= $enc_eventid ?>')" ><i class="fa fa-person-booth" aria-hidden="true"></i>&emsp;<?= gettext("Manage Volunteers"); ?></a></li>
<?php } ?>

<?php if ($_COMPANY->getAppCustomization()['event']['partner_organizations']['enabled'] && !$event->isCancelled() && ($event->loggedinUserCanManageEvent() || $event->loggedinUserCanUpdateEvent() || $event->loggedinUserCanPublishEvent()) && !$event->isSeriesEventHead()) { $opts++; ?>
    <li><a role="button" class="" href="javascript:void(0);" onclick="manageOrganizations('<?= $enc_eventid ?>')" ><i class="fa fa-hand-holding-heart" aria-hidden="true"></i>&emsp;<?= gettext("Partner Organizations"); ?></a></li>
<?php } ?>

 <?php if ($event->canUpdateEventExpenseEntry()) { $opts++; ?>
 <li><a role="button" href="javascript:void(0);" onclick="manageEventExpenseEntries('<?= $enc_eventid; ?>'); "><i class="fa fas fa-wallet"></i>&emsp; <?= gettext('Manage Event Expense'); ?><?php //$event->getEventBudgetedDetail(); ?></a></li>
 <?php } ?>

<?php if (($isAllowedToUpdateContent || $isAllowedToPublishContent) && !$event->isAwaiting()) { $opts++; ?>
    <li>
        <?php if(!$event->isCancelled()){ ?>
        <a role="button" href="javascript:void(0);" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= sprintf(gettext("Are you sure you want to %s?"), $eventActionBtn); ?>"
            <?php if($event->isSeriesEventHead()) { ?>
            onclick="deleteEventSeriesGroup('<?= $enc_groupid; ?>','<?= $enc_eventid; ?>', '<?= $in_user_view ?>', <?= ($event->val('groupid')== 0)?'true':'false' ?>, '<?= $event->val('isactive')==1 ?>');"
            <?php } else { ?>
            onclick="deleteEvent('evnt<?= $eventid; ?>','<?= $enc_eventid ?>','<?= $enc_groupid; ?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>', '<?= $in_user_view ?>', <?= ($event->val('groupid')== 0)?'true':'false' ?>, '<?= $event->val('isactive')==1 ?>', <?=$event->hasEnded()?1:0; ?>,<?= $event->getEventBudgetedDetail(false) ? 1 : 0 ?>, <?= $event->val('publish_to_email') ? 1 : 0 ?>, <?= $event->sendIcal() ? 1 : 0 ?>);"
            <?php } ?>
        >
            <i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= ucfirst($eventActionBtn); ?>
        </a>
        <?php } ?>
    </li>
<?php } ?>

<?php } // Version check Else Close ?>

<?php if (!$opts){  ?>
<li><a role="button" class="disabled"><i class="fa fas fa-exclamation" aria-hidden="true"></i>&emsp;<?= gettext("No options available"); ?></a></li>
<?php } ?>