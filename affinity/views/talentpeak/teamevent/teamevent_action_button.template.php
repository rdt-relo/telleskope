<?php
global $_USER, $_COMPANY;
$opts = 0;
$eventid = $event->id();
$enc_eventid = $_COMPANY->encodeId($event->id());
$enc_groupid = $_COMPANY->encodeId($event->val('groupid'));
$in_user_view = (basename($_SERVER['PHP_SELF']) =='eventview.php') ? true : false;
$refreshPage  = $refreshPage ?? 1;
$logged_in_user_can_manage_event = $event->loggedinUserCanManageEvent();
$logged_in_user_can_update_event = $event->loggedinUserCanUpdateEvent();
?>

<?php if (( $_USER->isProgramTeamMember($event->val('teamid')) || $_USER->canManageGroup($event->val('groupid'))) && $showBackLink) { ?>
    <li>
        <a tabindex="0" href="javascript:void(0);" class=""
               onclick="backToTeamDetail('<?= $enc_groupid ?>','<?= $_COMPANY->encodeId($event->val('teamid')); ?>')"
        >
        <i class="fa fa-arrow-right" aria-hidden="true"></i>
            &emsp;<?= sprintf(gettext("View %s detail"),$_COMPANY->getAppCustomization()['teams']['name']); ?>
        </a>
    </li>
<?php } ?>

<?php if (!$event->isAwaiting() && $logged_in_user_can_manage_event) { ?>
    <li>
        <a tabindex="0" href="javascript:void(0);" class=""
               onclick="openNewTeamEventForm('<?= $enc_groupid ?>','<?= $_COMPANY->encodeId($event->val('teamid')); ?>','<?= $enc_eventid; ?>','<?= $_COMPANY->encodeId(0); ?>')"
        >
            <i class="fa fas fa-edit" aria-hidden="true"></i>
            &emsp;<?= gettext("Edit Event"); ?>
        </a>
    </li>
<?php } ?>

<?php if ($event->isPublished() && $logged_in_user_can_manage_event && !$event->hasEnded()) { $opts++; ?>
<li><a tabindex="0" href="javascript:void(0);" class="" onclick="viewReminderHistory('<?= $enc_eventid ?>');"><i class="fa fa-bell" aria-hidden="true"></i>&emsp;<?= gettext("Send a Reminder"); ?></a></li>
<?php } ?>

<?php if ($_COMPANY->getAppCustomization()['event']['volunteers'] && $logged_in_user_can_manage_event && !$event->hasEnded()) { ?>
    <li><a  tabindex="0" href="javascript:void(0);" class="" onclick="manageVolunteers('<?= $enc_eventid ?>','<?= $refreshPage; ?>')" ><i class="fa fa-person-booth" aria-hidden="true"></i>&emsp;<?= gettext("Manage Volunteers"); ?></a></li>
<?php } ?>

<?php if ($_COMPANY->getAppCustomization()['event']['checkin'] && $logged_in_user_can_update_event && $event->hasEnded() && $event->isPublished()) { ?>
    <li><a tabindex="0" href="javascript:void(0);" class=""  onclick="updateEventFollowUpNoteForm('<?= $enc_eventid; ?>');"><i class="fa fa-flag" aria-hidden="true"></i>&emsp;<?= gettext("Post Event Follow-up"); ?></a></li>
<?php } ?>

<?php if ($event->isPublished()) { $opts++; ?>
<li><a tabindex="0" href="javascript:void(0);" class="" onclick="getShareableLink('<?= $enc_groupid; ?>','<?=$enc_eventid ?>','2')"><i class="fa fa-share-square" aria-hidden="true"></i>&emsp;<?= gettext("Get Shareable Link"); ?></a></li>
<?php } ?>

<?php if (0 && ($event->isDraft() || $event->isUnderReview()) && $logged_in_user_can_manage_event) { $opts++; ?>
<li><a tabindex="0" href="javascript:void(0);" class=" " onclick="openEventReviewModal('<?= $enc_groupid; ?>','<?= $enc_eventid ?>');"><i class="fa fa-tasks" aria-hidden="true"></i>&emsp;<?= gettext("Email Review"); ?></a></li>
<?php } ?>

<?php if ( 0 && ( ($event->isDraft() && !$_COMPANY->getAppCustomization()['event']['require_email_review_before_publish'] ) || $event->isUnderReview()) && $logged_in_user_can_manage_event) { $opts++; ?>
    <?php // Disclaimer check
                    $checkDisclaimerExists = Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['EVENT_PUBLISH_BEFORE'], $event->val('groupid'));
                    if($checkDisclaimerExists){
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
<li><a tabindex="0" href="javascript:void(0);" class="" onclick="<?= $onClickFunc; ?>"><i class="fa fa-mail-bulk" aria-hidden="true"></i>&emsp;<?= gettext("Publish"); ?></a></li>
<?php } elseif ($event->isAwaiting() && $logged_in_user_can_manage_event) { $opts++; ?>
<li><a tabindex="0" href="javascript:void(0);" class="" onclick="cancelEventPublishing('<?= $enc_groupid; ?>','<?= $enc_eventid ?>');"><i class="fa fas fa-times" aria-hidden="true"></i>&emsp;<?= gettext("Cancel Publishing"); ?></a></li>
<?php } ?>

<?php if ($event->isPublished() && $logged_in_user_can_manage_event) { ?>
    <li><a tabindex="0" class="js-download-link" href="ajax_events?exportAttendeesToExcel=<?= $enc_eventid ?>" > <i class="fa fa-download" aria-hidden="true"></i>&emsp;<?= gettext("Download RSVP List"); ?></a></li>
<?php } ?>

<?php if ($_COMPANY->getAppCustomization()['event']['checkin'] && $event->isPublished() && $logged_in_user_can_manage_event && !$event->isSeriesEventHead()) { ?>
    <li><a tabindex="0" href="javascript:void(0);" class="" onclick="eventRSVPsForCheckIn('<?= $enc_eventid ?>','userend')"><i class="fa fa-check-square"></i>&emsp;<?= gettext("Check In"); ?></a></li>
<?php } ?>

<?php if (!$event->isAwaiting() && $logged_in_user_can_manage_event) { $opts++; ?>
    <li><a tabindex="0" href="javascript:void(0);" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to delete this event?"); ?>"
            onclick="deleteTeamEvent('<?= $enc_eventid ?>','<?= $enc_groupid; ?>','<?= $_COMPANY->encodeId($event->val('teamid')); ?>','<?= $in_user_view ?>','<?= $event->val('isactive')==1 ?>');"
        ><i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= gettext("Delete"); ?></a></li>
<?php } ?>


<?php if (!$opts){  ?>
<li><a tabindex="0" href="javascript:void(0);" class="disabled"><i class="fa fas fa-exclamation" aria-hidden="true"></i>&emsp;<?= gettext("No options available"); ?></a></li>
<?php } ?>