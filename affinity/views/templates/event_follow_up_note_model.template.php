<style>
    .swal2-close:focus { 
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 .1rem rgba(0,123,255,.25);
}
</style>
<div id="event_folloup_form_model" tabindex="-1" class="modal fade">
	<div aria-label="<?= empty($event->val('followup_notes'))?gettext('Add a Post Event Follow-up Note'):gettext('Update Post Event Follow-up Note');?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
		<div class="modal-content">
			<div class="modal-header">
                <h4 class="modal-title" id="form_title"><?= empty($event->val('followup_notes'))?gettext('Add a Post Event Follow-up Note'):gettext('Update Post Event Follow-up Note');?></h4>
				<button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">  
            <?php if($postEventSurveyLink){ ?>
                <input type="text" style="display:none;" id="shareableLink" name="shareableLink" value="<?= $postEventSurveyLink; ?>">
                <div class="input-group-append">
                    <p class="m-3 p-3 border text-center">
                        <?= gettext('You can copy the post-event survey link and use it in the message body.')?>
                        <button tabindex="0" type="button" class="btn btn-affinity" onclick="copyEventSurveyShareableLink('<?= addslashes(gettext('Post-event survey link copied to clipboard.'))?>','shareableLink')" onKeyPress="copyEventSurveyShareableLink('<?= addslashes(gettext('Post-event survey link copied to clipboard.'))?>','shareableLink')" id="basic-addon2"><?= addslashes(gettext("Copy Link")) ?></button>
                    </p>
                </div>
            <?php } ?>             
                <form class="form-horizontal" method="post" action="" id="event_folloup_form">
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <div class="form-group">
                        <label for="inputEmail" class="col-sm-12 control-label"><?= gettext("Follow-up Note");?></label>
                        <div class="col-sm-12">
                            <div id="post-inner" class="post-inner-edit">
                            <textarea class="form-control" placeholder="<?= gettext("Event follow-up note");?>..." name="followup_notes" rows="5" id="redactor_content" maxlength="2000" ><?= htmlspecialchars($event->val('followup_notes') ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    
					<div class="text-center">
						<button type="button" onclick="eventFollowUpNoteSubmitConfirmation('<?=$encEventId;?>')" class="btn btn-primary"><?= empty($event->val('followup_notes'))? addslashes(gettext('Add')):addslashes(gettext('Update'));?></button>
                        <button type="button" data-dismiss="modal" class="btn btn-secondary"><?= addslashes(gettext("Cancel"));?></button>&nbsp;
					</div>
					
					
				</form>
			</div>
		</div>  
	</div>
</div>
		
<script>
    $(document).ready(function(){
        $('#redactor_content').initRedactor('redactor_content','event_followup',[],[],'<?= $_COMPANY->getImperaviLanguage(); ?>');
        $(".redactor-voice-label").text("<?= gettext('Follow-up');?>");
        redactorFocusOut('#btn_close'); // function used for focus out from redactor when press shift.
    });


	function eventFollowUpNoteSubmitConfirmation(e){
        var options = '';
        <?php if(!empty(array_intersect($events_attendence_type, array(1,2,3,4))) && $events_rsvp_yes_no){ ?>
			options += '<div class="form-check">'+
                            '<input class="form-check-input updateTo" name="updateTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_YES'] ?>" checked disabled >'+
                            '<small class="form-check-label" for="defaultCheck1">'+
                                '<?= addslashes(gettext("RSVP - Yes"));?>'+
                            '</small>'+
                        '</div>';
		<?php } ?>
        <?php if(!empty(array_intersect($events_attendence_type, array(1,2,3,4))) && $events_rsvp_yes_no){ ?>
			options += 	'<div class="form-check mt-2">'+
                            '<input class="form-check-input updateTo" name="updateTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_MAYBE'] ?>" >'+
                            '<small class="form-check-label" for="defaultCheck1">'+
                                '<?= addslashes(gettext("RSVP - Tentative"));?>'+
                            '</small>'+
                        '</div>';
        <?php } ?>
        <?php if(!empty(array_intersect($events_attendence_type, array(1,3))) && $events_max_inperson > 0){ ?>
            options += 	'<div class="form-check mt-2">'+
                            '<input class="form-check-input updateTo" name="updateTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_INPERSON_YES'] ?>" checked >'+
                            '<small class="form-check-label" for="defaultCheck1">'+
                                '<?= addslashes(gettext("RSVP - In Person Yes"));?>'+
                            '</small>'+
                        '</div>';
        <?php } ?>
        <?php if(!empty(array_intersect($events_attendence_type, array(2,3))) && $events_max_online > 0){ ?>
            options += 	'<div class="form-check mt-2">'+
                            '<input class="form-check-input updateTo" name="updateTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_ONLINE_YES'] ?>" checked >'+
                            '<small class="form-check-label" for="defaultCheck1">'+
                                '<?= addslashes(gettext("RSVP - Online Yes"));?>'+
                            '</small>'+
                        '</div>';
        <?php } ?>
        <?php if(!empty(array_intersect($events_attendence_type, array(1,3))) && $events_max_inperson >0){ ?>
            options += 	'<div class="form-check mt-2">'+
                            '<input class="form-check-input updateTo" name="updateTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_INPERSON_WAIT'] ?>" >'+
                            '<small class="form-check-label" for="defaultCheck1">'+
                                '<?= addslashes(gettext("RSVP - In Person Waitlist"));?>'+
                            '</small>'+
                        '</div>';
        <?php } ?>
        <?php if(!empty(array_intersect($events_attendence_type, array(2,3))) && $events_max_online > 0){ ?>
            options += 	'<div class="form-check mt-2">'+
                            '<input class="form-check-input updateTo" name="updateTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_ONLINE_WAIT'] ?>" >'+
                            '<small class="form-check-label" for="defaultCheck1">'+
                                '<?= addslashes(gettext("RSVP - Online Waitlist"));?>'+
                            '</small>'+
                        '</div>';
        <?php } ?>
        <?php if(!empty(array_intersect($events_attendence_type, array(1,2,3,4)))){ ?>
            options += 	'<div class="form-check mt-2">'+
                            '<input class="form-check-input updateTo" name="updateTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_NO'] ?>" >'+
                            '<small class="form-check-label" for="defaultCheck1">'+
                                '<?= addslashes(gettext("RSVP - No"));?>'+
                            '</small>'+
                        '</div>';
        <?php } ?>
        $(document).off('focusin.modal');
		Swal.fire({
            //title: 'Where do you want to publish?',
            html:
                '<h4><?= addslashes(gettext('Where do you want to publish?'))?></h4>'+
                '<br>'+
				'<hr>'+
                '<small><?= addslashes(gettext('Click on the option below to publish update without sending emails'))?></small>' +
                '<br>' +
                '<br>' +
                '<button type="button" class="btn btn-affinity" onclick="updateEventFollowUpNote(\''+e+'\',1);"><?= addslashes(gettext('This platform only'))?></button>'+
                '<br>' +
                '<hr>'+
                '<small><?= addslashes(gettext('Click on the option below to publish on this platform and send email updates to'))?></small>' +
                '<br>'+
				'<div class="col-md-4">&nbsp;</div>'+
                '<div class="col-md-8 text-left">'+
                    options+
					'<div class="form-check mt-2">'+
						'<input class="form-check-input updateTo" name="updateTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_DEFAULT'] ?>">'+
						'<small class="form-check-label" for="defaultCheck1">'+
							'<?= gettext("RSVP - Not Responded");?>'+ 
						'</small>'+
					'</div>'+
					'<div class="form-check mt-2">'+
						'<input class="form-check-input updateTo" name="updateTo[]" type="checkbox" value="-1">'+
						'<small class="form-check-label" for="defaultCheck2">'+
							'<?= gettext("All Invited");?>'+
						'</small>'+
					'</div>'+
				'</div>'+
                '<button type="button" class="btn btn-affinity mt-3" onclick="updateEventFollowUpNote(\''+e+'\',2);"><?= addslashes(gettext('This platform & email'))?></button>'+
                '<br>',
            showCloseButton: true,
            showCancelButton: false,
            showConfirmButton: false,
            focusConfirm: true,
            allowOutsideClick:false,
        }).then(function(result){ 
			$('#swal2-close').trigger('focus');			
		});
	}

    function updateEventFollowUpNote(e,w){
        $(document).off('focusin.modal');
        var formdata = $('#event_folloup_form')[0];
			var finaldata  = new FormData(formdata);
			finaldata.append('do_what',w);
			if(w==2) {
                var opt = $('.updateTo:checked').map(function (_, el) {
                    finaldata.append('send_update_to[]', $(el).val());
                });
            }
			$.ajax({
				url: 'ajax_events.php?updateEventFollowUpNote='+e,
				type: 'POST',
				data: finaldata,
				processData: false,
				contentType: false,
				cache: false,
				success: function(data) {
                    try {
                        let jsonData = JSON.parse(data);
                        swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function (result) {
                            if (jsonData.status >0){
                                closeAllActiveModal();
                            }
                        });
                    } catch(e) { 
                        swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false});
                    }
				},
				error: function ( data ) {
						swal.fire({title: 'Error!',text:'Internal server error, please try after some time.',allowOutsideClick:false});
				}
			});
    }

$('#event_folloup_form_model').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus')
});

$('#event_folloup_form_model').on('hidden.bs.modal', function (e) {
    $('#<?=$encEventId;?>').trigger('focus');
})

</script>