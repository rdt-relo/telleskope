<div class="modal fade" id="volunteer_email_form_modal">
	<div aria-label="<?= $modalTitle;?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
    	<div class="modal-content">				
			<div class="modal-header">
				<h4 class="modal-title"><?= $modalTitle;?></h4>
				<button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
        	</div>
        	<div class="modal-body">
			    <div class="row">
					<div class="col-md-12">  
						<div class="">
							<form id="send_volunteer_email_form" method="post">
								<div class="form-group">
									<div>
										<label><?= gettext("Select Volunteers");?><span style="color:red"> *</span></label>
										<select  tabindex="-1" name="volunteerIds[]" id="volunteerIds" class="form-contorl" multiple >
											<?php foreach($volunteerTypesGrouping as $typeKey => $typeValue){ ?>
												<?php if(empty($typeValue)){ continue; } ?>
												<optgroup class="clickable-optgroup" label="<?=$typeKey; ?>">
												<?php foreach($typeValue as $vlounteerKey => $volunteer){ ?>
                          <?php $volunteer_obj = EventVolunteer::Hydrate($volunteer['volunteerid'], $volunteer); ?>
                          <?php if ($volunteer_obj->isExternalVolunteer()) { ?>
                            <?php if ($_COMPANY->isValidEmail($volunteer_obj->getVolunteerEmail())) { ?>
                              <option value="external_volunteer:<?= $volunteer_obj->encodedId() ?>">
                                <?= $volunteer_obj->getFirstName() ?> <?= $volunteer_obj->getLastName() ?> (<?= $volunteer_obj->getVolunteerEmail() ?>) (<?= gettext('External Volunteer') ?>)
                              </option>
                            <?php } else { ?>
                              <option value="external_volunteer:<?= $volunteer_obj->encodedId() ?>">
                                <?= htmlspecialchars(sprintf(
                                  <<<HTML
                                  <span>
                                    {$volunteer_obj->getFirstName()} {$volunteer_obj->getLastName()} ({$volunteer_obj->getVolunteerEmail()}) (%s)
                                  </span>
                                  <i
                                    class="fa fa-info-circle"
                                    data-toggle="tooltip"
                                    data-placement="top"
                                    title="%s"
                                  >
                                  </i>
                                  HTML
                                  ,gettext('External Volunteer')
                                  ,gettext("Please verify this email address, it's not a company email address")
                                )) ?>
                              </option>
                            <?php } ?>
                          <?php } else { ?>
                            <option value="<?= $_COMPANY->encodeId($volunteer['userid'])?>">
                              <?= $volunteer['firstname'].' '.$volunteer['lastname']; ?>
                            </option>
                          <?php } ?>
												<?php } ?>
												</optgroup>
											<?php } ?>
                                        </select>
									</div>
								</div>

								<div class="form-group">
									<div>
										<label><?= gettext("Subject");?><span style="color:red"> *</span></label>
										<input id="subject" class="form-control" name="subject" maxlength="255" value="" placeholder="<?= gettext("Email subject")?>"></input>
									</div>
								</div>
								<div class="form-group">
                                    <label><?= gettext("Message");?><span style="color:red"> *</span></label>
									<div class="post-inner-edit">
										<textarea class="form-control" maxlength="8000" id="redactor_content" name="message"></textarea>
									</div>
                                </div>
								<div class="form-group text-center mt-3">
								    <button type="button" onclick="sendEmailToVolunteers('<?= $_COMPANY->encodeId($eventid); ?>');" class="btn btn-affinity confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to send email?')?>" name="send"><?= gettext("Send Email");?></button>
									<button type="button" class="btn btn-affinity-gray" data-dismiss="modal"><?= gettext("Cancel");?></button>
								</div>
							</form>
			            </div>
			        </div>
				</div>
        	</div>
		</div>
	</div>
</div>
<style type="text/css">
	textarea{
		resize: none;
		display:block;
		height: 100px !important;
	}	
</style>

<script>
    $(document).ready(function(){
		$('#redactor_content').initRedactor('redactor_content','event_volunteer',[],[],'<?= $_COMPANY->getImperaviLanguage(); ?>');
		redactorFocusOut('#subject'); // function used for focus out from redactor when press shift +tab.
	});
   $('#volunteerIds').multiselect({
		nonSelectedText: "<?=  gettext('Select volunteers'); ?>",
		numberDisplayed: 3,
		nSelectedText: "<?= gettext('Volunteers selected')?>",
		disableIfEmpty: true,
		allSelectedText: "<?= gettext('Multiple volunteers selected'); ?>",
		enableFiltering: true,
		maxHeight: 400,
    enableClickableOptGroups: true,
    enableHTML: true
	});

	function sendEmailToVolunteers(e){
		$(document).off('focusin.modal');
		var formdata = $('#send_volunteer_email_form')[0];
		var finaldata  = new FormData(formdata);
		finaldata.append("eventid",e);
		$.ajax({
			url: 'ajax_events.php?sendEmailToVolunteers=1',
			type: 'POST',
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success: function(data) {
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
						if (jsonData.status == 1){
							manageVolunteers(e,0)
						}
					});
				} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
			}
		});

	}

$('#volunteer_email_form_modal').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus')
});
$(document).ready(function () {
      $('.multiselect').attr( 'tabindex', '0' );
	  $(".redactor-voice-label").text("<?= gettext('Message');?>");
});
//On Enter Key...
$(document).keyup(function(e) {
     if (e.keyCode == 9) { 
        $('.dropdown-menu').removeClass('show'); 
            
    }
});

$(function () {
  $('[data-toggle="tooltip"]').tooltip();
});
</script>