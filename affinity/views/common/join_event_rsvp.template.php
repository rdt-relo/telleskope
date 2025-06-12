<?php 

/**
 * EVENT RSVP template
 * 
 * This is a common RSVP template for regular events or event series. This template will handle
 * RSVP responses for regular events or event series. It also handles pre- and post-event surveys.
 * 
 * Dependency :
 * $subjectEvent Object
 */

$btnCss         = array('warning','success','info','danger','danger','danger');
$rsvpOptions    = $subjectEvent->getMyRSVPOptions();
$userPreEventSurveyResponses = null;
$userPostEventSurveyResponses = null;

if ($_COMPANY->getAppCustomization()['event']['enable_event_surveys'] && $subjectEvent->isPublished()) {
    $userPreEventSurveyResponses = $subjectEvent->getEventSurveyResponsesByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'],true);
    $userPostEventSurveyResponses = $subjectEvent->getEventSurveyResponsesByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT'],true);
}

$RSVPSuccessMessage = '';
if (!empty($showRsvpSussessMessage)) {
	$RSVPSuccessMessage  = $subjectEvent->getRSVPSuccessMessage($subjectEvent->getMyRsvpStatus());
}

?>
<?php if ($subjectEvent->isPublished() && !$subjectEvent->hasEnded()){ ?>
	<h5 class="py-2 "><?= gettext('Event RSVPs options');?></h5>
<?php } ?>
<?php if (!$subjectEvent->val('rsvp_enabled') && !$subjectEvent->hasEnded()) { ?> 

	<div class="alert alert-secondary text-center" style="">
		<strong><?=gettext("RSVPs are not required for this event.")?></strong>
	</div>

<?php } elseif ($subjectEvent->isPublished() && $subjectEvent->hasRSVPEnded() && !$subjectEvent->hasEnded()){ ?>
<div class="alert alert-info">
	<div align="center" style="font-weight: 900">
		<p><?=$rsvpOptions['message']?></p>
	</div>
</div>
<?php } elseif ($subjectEvent->isPublished() && $subjectEvent->hasEnded()){ ?>	
	<div class="alert alert-info text-center" style="">
		<strong><?=gettext("This event is over")?></strong>
		<?php if ($subjectEvent->val('event_recording_link')) { ?>
		<br>
		<br>
		<a href="<?= $subjectEvent->getEventRecordingShareableLink() ?>" target="_blank" rel=”noopener noreferrer”><?=gettext("View Event Recording");?></a>
		<?php if ($subjectEvent->val('event_recording_note')) { ?>
		<br>
		<div class="border-top border-bottom" style="margin:0 25%; font-size: small;"><?= (htmlspecialchars($subjectEvent->val('event_recording_note')));?></div>
		<?php } ?>
		<?php if ($_COMPANY->getAppCustomization()['event']['checkin'] && empty($subjectEvent->getMyCheckinDate())) { ?>
		<br>
		<br>
		<?=gettext("If you couldn't make it to the live event, don't worry! Just click on the recording link, and once you have finished viewing it, mark your attendance.")?>
		<button type="button" onclick="confirmEventRecordingAttendance('<?=$_COMPANY->encodeId($subjectEvent->id())?>');" class="btn btn-sm btn-outline-primary confirm popconfirm-selectable-button" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('I have finished watching the event recording video, please mark my attendance')?>" name="confirmEventRecordingAttendanceButton" id="confirmEventRecordingAttendanceButton"><?= gettext("Mark as watched");?></button>
		<?php } ?>
		<?php } ?>
		<?php if ($_COMPANY->getAppCustomization()['event']['enable_event_surveys'] && ($userPostEventSurveyResponses)){ ?>
		<div class="text-center mt-3">
			<?php
				if(!empty($subjectEvent->getEventSurveyResponsesByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT']))){
					$postSurveyRespondBtn = gettext("Update");
					$postSurveyAvailableTitle = gettext("Post event survey responses");
				} else {
					$postSurveyRespondBtn = gettext("Respond");
					$postSurveyAvailableTitle = gettext("Post event survey is available");
				}
				?>
			<strong class="pl-4"><?= $postSurveyAvailableTitle; ?>: </strong>
			<button class="btn-link btn-no-style" onclick="initUpdateEventSurveyResponses('<?=$_COMPANY->encodeId($subjectEvent->id())?>','<?= Event::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT']; ?>','<?= $subjectEvent->getMyRsvpStatus(); ?>')">
			<?= $postSurveyRespondBtn; ?>
			</button>
		</div>
		<?php } ?>
	</div>

<?php } elseif ($subjectEvent->isPublished() && !$subjectEvent->hasEnded()){ ?>

<div class="alert alert-<?=empty($rsvpOptions['buttons']) ? 'secondary' : $btnCss[$rsvpOptions['my_rsvp_status'] %10]?> <?= $lockOptions ? 'locked-container' : ''; ?>">
	<?php if($lockOptions){ ?>
        <div class="locked-container-overlay"><span class="locked-text"><i class="fa fa-lock" style="color:white;" aria-hidden="true"></i> <?= $lockMessage; ?></span></div>
	<?php } ?>
	<p style="font-size: smaller;"><?=$rsvpOptions['message']?></p>

	<?php if (!empty($rsvpOptions['buttons'])) { ?>
	<div align="center" style="margin: 10px auto;font-weight: 900">
		<p>
			<?= gettext("Will you be attending this event?"); ?>
		</p>
	</div>
	<?php } ?>

	<div align="center">
		<?php foreach ($rsvpOptions['buttons'] as $rsvpId => $buttonLabel) {
			$outline = ($rsvpOptions['my_rsvp_status'] == $rsvpId) ? '' : 'outline-';
			$joinFunction = "joinEvent";
			$triggerUpdateSurvey = false;
			if ($_COMPANY->getAppCustomization()['event']['enable_event_surveys'] &&
			    $subjectEvent->isEventSurveyAvailable(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'])
			){
			    if(!empty($subjectEvent->getEventSurveyResponsesByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT']))){
			        $triggerUpdateSurvey = true;
			    } else {
			        $joinFunction = "getPreJoinEventSurvey";
			    }
			}
			?>
		<button class="btn btn-sm btn-<?=$outline.$btnCss[$rsvpId %10]?> rsvpbtn" <?php if(empty($outline)) { ?>
			disabled
			<?php } else{ ?>
			<?php if($triggerUpdateSurvey ) { ?>
			onclick="initUpdateEventSurveyResponses('<?=$_COMPANY->encodeId($subjectEvent->id())?>','<?= Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT']; ?>','<?= $rsvpId ?>')"
			<?php } else{ ?>
			onclick="<?= $joinFunction ?>('<?=$_COMPANY->encodeId($subjectEvent->id())?>', '<?= $rsvpId ?>')"
			<?php } ?>
			<?php } ?>
			><?=$buttonLabel?></button>
		<?php } ?>
		<?php if($subjectEvent->val('add_photo_disclaimer') && $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['enabled']){ ?>
		<p><small><?= $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['disclaimer']; ?></small></p>
		<?php } ?>
	</div>
	<?php if ($_COMPANY->getAppCustomization()['event']['enable_event_surveys'] && $userPreEventSurveyResponses){ ?>
	<div class="text-center mt-3">
		<?php if($userPreEventSurveyResponses){ 
			if ($subjectEvent->getEventSurveyResponsesByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'])){ ?>
		<strong><?= gettext("Pre event survey responses")?>: </strong>
		<button class="btn-link btn-no-style" onclick="initUpdateEventSurveyResponses('<?=$_COMPANY->encodeId($subjectEvent->id())?>','<?= Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT']; ?>','<?= $subjectEvent->getMyRsvpStatus(); ?>')"><?= gettext("Update");?></button>
		<?php } else{ ?>
		<strong><?= gettext("Pre event survey is available")?>: </strong>
		<button class="btn-link btn-no-style" onclick="getPreJoinEventSurvey('<?=$_COMPANY->encodeId($subjectEvent->id())?>',<?= $subjectEvent->getMyRsvpStatus(); ?>)"><?= gettext("Respond");?></button>

		<?php 	}
			}
			?>
	</div>
	<?php } ?>
</div>
<?php } ?>


<script>
  $(document).ready(function() {
    // Select all elements inside the div with the class 'locked-container'
    $('.locked-container, .locked-container *').attr('tabindex', '-1');
  });
</script>


<?php if($RSVPSuccessMessage){ ?>
	<script>
        // Show success message
        swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            customClass: {popup: 'colored-toast'},
            timer: 5000
        }).fire({
            text: '<?= addslashes($RSVPSuccessMessage); ?>',
            icon: 'success'
        });
	</script>
<?php } ?>