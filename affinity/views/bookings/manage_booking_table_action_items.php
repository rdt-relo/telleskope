<style>
    .fa.fa-envelope-open:hover{
	color: #0077b5 !important;
}
</style>

<?php 

$opt = 0;
?>

<?php if($resolution == 'Scheduled' && !$event->hasEnded()){ $opt++;?>
<li>
    <a role="button" href="javascript:void(0);" onclick="viewReminderHistory('<?= $_COMPANY->encodeId($eventid); ?>')">
        <i class="fa fa-bell"></i>&emsp;<?= gettext('Resend the meeting invite'); ?>
    </a>
<li>
<?php } ?>
<?php if($resolution == 'Scheduled'){ $opt++;?>
<li>
    <a role="button" href="javascript:void(0);" onclick="rescheduleBooking('<?= $_COMPANY->encodeId($eventid); ?>')">
        <i class="fa fas fa-calendar-day"></i>&emsp;<?= gettext('Reschedule'); ?>
    </a>
<li>
<?php } ?>
<?php if($resolution == 'Scheduled'){ $opt++;?>
<li>
    <a role="button" href="javascript:void(0);" onclick="reassignBooking('<?= $_COMPANY->encodeId($eventid); ?>')">
    <i class="fa fas fa-calendar-check"></i>&emsp;<?= gettext('Reassign'); ?>
    </a>
<li>
<?php } ?>
<?php if($resolution == 'Scheduled' || $resolution != 'Canceled'){ $opt++;?>
<li>
    <a role="button" href="javascript:void(0);" onclick="cancelEventBooking('<?= $_COMPANY->encodeId($eventid); ?>','<?= $_COMPANY->encodeId($event->val('groupid')); ?>')">
        <i class="fa far fa-window-close"></i>&emsp;<?= gettext('Cancel'); ?>
    </a>
<li>
<?php } ?>
<?php if($resolution == 'Scheduled' || ($resolution != 'complete' &&  $resolution != 'Canceled')){ $opt++;?>
<li>
    <a role="button" href="javascript:void(0);" onclick="updateEventBookingResolutionData('<?= $_COMPANY->encodeId($eventid); ?>','<?= $_COMPANY->encodeId($event->val('groupid')); ?>','complete')">
        <i class="fa fas fa-calendar-check"></i>&emsp;<?= gettext('Mark as Complete'); ?>
    </a>
<li>
<?php } ?>
<?php if($resolution == 'Scheduled' || ($resolution != 'noshow' &&  $resolution != 'Canceled')){ $opt++;?>
<li>
    <a role="button" href="javascript:void(0);" onclick="updateEventBookingResolutionData('<?= $_COMPANY->encodeId($eventid); ?>','<?= $_COMPANY->encodeId($event->val('groupid')); ?>','noshow')">
        <i class="fa fas fa-calendar-times"></i>&emsp;<?= gettext('Cancel as No Show'); ?>
    </a>
<li>
<?php } ?>

<?php if(!$opt){ ?>
<li><a role="button" class="disabled"><i class="fa fas fa-exclamation" aria-hidden="true"></i>&emsp;<?= gettext("No options available"); ?></a></li>
<?php } ?>