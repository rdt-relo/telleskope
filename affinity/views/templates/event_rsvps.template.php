<style>
.check-link-in {
    color: #039000;
}

.check-link-out {
    color: #f05341;
}
.dynamic-button-container {
	position: relative !important;
	margin-top: <?= $section == 'userend' ? 30 : 10; ?>px;
}
</style>
<div class="row" id="evnt481">								
	<div class="col-md-12 event-block">
		
		<div class="col-md-8">
			<!-- Event header row start -->
			<a role="button" href="javascript:void(0)" onclick="getEventDetailModal('<?= $_COMPANY->encodeId($event->id()); ?>','<?= $_COMPANY->encodeId(0);?>','<?= $_COMPANY->encodeId(0)?>')">
				<?php if($event->isDraft()){ ?>
					<h4 class="active" style="color:red;">	
						<?= $event->val('eventtitle'); ?>
						<img src="img/draft_ribbon.png" alt="Draft icon image" height="16px;">
					</h4>
                <?php } elseif($event->isUnderReview()){ ?>
                    <h4 class="active" style="color:darkorange;">
                        <?= $event->val('eventtitle'); ?>
                        <img src="img/review_ribbon.png" alt="Draft icon image" height="16px;">
                    </h4>
				<?php } else { ?>
					<h4 class="active">
						<?= $event->val('eventtitle'); ?>
					</h4>
				<?php } ?>

			</a>
			<div>
				<span aria-label="Time" role="img" class="fa fa-clock tele-title-icon"></span>
				<div class="tele-title">
					<p class="font-col">
						<?= $localStart->format('M j, Y \a\t g:i a T');?> -
						<?= $localEnd->format('g:i a'); ?>
					</p>
				</div>
			</div>
			<div>

				<?php if ($event->val('event_attendence_type')!=1){ ?>
					<div>
						<i aria-label="Meeting Platform" role="img" class="fa fa-laptop tele-title-icon" aria-hidden="true"></i>
						<div class="tele-title">
							<p class="font-col"><?= $event->val('web_conference_sp'); ?>&nbsp;</p>
						</div>
					</div>
				<?php } ?>
				<?php if($event->val('event_attendence_type') !=2){ ?>
					<div>
						<span aria-label="Location" role="img" class="fa fa-map-marker map-in tele-title-icon"></span>
						<div class="tele-title">
							<p class="font-col"><?= $event->val('eventvanue'); ?>&nbsp;</p>
							<p><?= $event->val('vanueaddress'); ?></p>
						</div>
					</div>
				<?php } ?>
			</div>												
		</div>

		<div class="col-md-4">
			<div class="text-right mt-4">
				<strong><?= sprintf(gettext("Total RSVPs : <span id='rsvp-count'> %s </span>"),$joinersCount);?></strong><br>
				<strong><?= sprintf(gettext("Total Checked In : <span id='checkin-count'>%s</span>"),$checkinCount);?></strong>
			</div>
		
		</div>
		
	</div>
</div>
<hr class="linec">
<div class="row inner-page-container">
	<div class="col-md-12">
		<div class="table-responsive " id="list-view">
			<table id="table-event" class="table table-hover display compact" summary="This table displays the list of RSVPs of an event">
				<thead>
					<tr>
						<th width="20%" scope="col"><?= gettext("First Name");?></th>
						<th width="20%" scope="col"><?= gettext("Last Name");?></th>
						<th width="25%" scope="col"><?= gettext("Email");?></th>
						<th width="15%" scope="col"><?= gettext("RSVP");?></th>
						<th width="20%" scope="col"><?= gettext("Check In");?></th>
						
					</tr>
				</thead>
				<tbody>
					
			    <?php
			        $i = 0;
			        $enc_eventid = $_COMPANY->encodeId($eventid);
				    foreach ($data as $joinee) {
				        if (empty($joinee['email'])) {
				            $other = json_decode($joinee['other_data'], true);
				            $joinee['firstname'] = htmlspecialchars($other['firstname']);
				            $joinee['lastname'] = htmlspecialchars($other['lastname']);
				            $joinee['email'] = htmlspecialchars($other['email']);
				        }
			    ?>
					<tr id="<?= $i++; ?>">

						<td><?= $joinee['firstname']; ?></td>
						<td><?= $joinee['lastname']; ?></td>
						<td><?= $joinee['email']; ?></td>
						<td><?= Event::GetRSVPLabel($joinee['joinstatus']) ?></td>
						<td  id="check_<?= $i+1; ?>"style="cursor:pointer;" >
						<?php if ($joinee['checkedin_date']!=NULL){ ?>
						    <button type="button" class="btn btn-success btn-xs" <?= !$event->hasCheckinStarted() ? 'disabled' : ''; ?> onclick="updateEventCheckIn(event, <?= $i+1; ?>,0,'<?= $_COMPANY->encodeId($joinee['joineeid']); ?>','<?= $enc_eventid ?>')"><?= gettext("Checked-In");?></button>
						<?php }else{  ?>
						    <button type="button" class="btn btn-secondary btn-xs" <?= !$event->hasCheckinStarted() ? 'disabled' : ''; ?> onclick="updateEventCheckIn(event, <?= $i+1; ?>,1,'<?= $_COMPANY->encodeId($joinee['joineeid']); ?>','<?= $enc_eventid ?>')"><?= gettext("Check-In");?></button>
						<?php } ?>
						</td>
					</tr>
		        <?php } ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php if ($_COMPANY->getAppCustomization()['group']['qrcode']) { ?>
<!-- Generate QR Code -->
<div id="generate-qr-code" class="modal fade">
    <div aria-label="<?= sprintf(gettext("QR Code of %s"),$event->val('eventtitle'));?>" class="modal-dialog" aria-modal="true" role="dialog">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="modal-title" class="modal-title"> <?= sprintf(gettext("QR Code of %s"),$event->val('eventtitle'));?></h4>
                <button type="button" id="btn_close" class="close" aria-hidden="true" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div style="text-align:center;" id="qrcode"></div>
                <div class="col-md-12">
                    <br>
                    <p><strong><?= gettext("Location");?> :</strong></p>
                    <p><?= $event->val('eventvanue') ?></p>
                    <p><?= $event->val('vanueaddress') ?></p>
                    <p>
                        <strong><?= $db->covertUTCtoLocalAdvance("l M j, Y \@ g:i a T","",  $event->val('start'),$_SESSION['timezone']); ?> -
                        <?= $db->covertUTCtoLocalAdvance("g:i a", "",  $event->val('end'),$_SESSION['timezone']); ?></strong>
                    </p>
                </div>
            </div>
            
        </div>

    </div>
</div>

<script>
	$('#qrcode').qrcode("<?= $code; ?>");
</script>
<?php } ?>
<div id="showcheckin_form"></div>
<div id="import_csv_checkin_modal"></div>

<script>
	$(document).ready(function() {
		var x = parseInt(localStorage.getItem("local_variable_for_table_pagination"));
		$('#table-event').DataTable( {
			pageLength:x,
			"order": [],
			"bPaginate": true,
			"bInfo" : false,
			'language': {
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },
			
		});
	});
$('#table-event').on( 'length.dt', function ( e, settings, len ) {
    localStorage.setItem("local_variable_for_table_pagination", len);
} );
</script>
<script>
$('#nonRsvpform').on('shown.bs.modal', function () {
   $('#nonRsvpform').trigger('focus')
});
$('#generate-qr-code').on('shown.bs.modal', function () {
   $('#modal-title').trigger('focus')
});
</script>