<div class="modal fade" id="send_discover_pair_request" tabindex="-1">
	<div aria-label="<?= gettext("Request Email");?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
    	<div class="modal-content">				
			<div class="modal-header">
				<h2 class="modal-title"><?= gettext("Request Email");?></h2>
				<button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
        	</div>
        	<div class="modal-body">
			    <div class="row">
					<div class="col-md-12">  
						<div class="">
							<form id="send_discover_pair_request_form" method="post">
								<input type="hidden" name="receiver_id" value="<?= $_COMPANY->encodeId($receiver_id); ?>">
								<input type="hidden" name="receiver_roleid" value="<?= $_COMPANY->encodeId($receiver_roleid); ?>">
								<input type="hidden" name="sender_roleid" value="<?= $_COMPANY->encodeId($sender_roleid); ?>">
								
								<p class="red mb-3"><?= gettext("Note: If you don't provide an email subject or message, the system will automatically generate them for you.")?></p>
								
								<div class="form-group">
									<div>
										<label for="subject"><?= gettext("Subject");?></label>
										<input id="subject" class="form-control" name="subject" maxlength="255" value="<?=$join_request_subject?>"></input>
									</div>
								</div>
								<div class="form-group">
                                    <label><?= gettext("Message");?></label>
									<div class="post-inner-edit">
										<textarea class="form-control" maxlength="8000" id="redactor_content" name="message"><?=$join_request_message?></textarea>
									</div>
                                </div>

								<div id="popconfirm-container" class="form-group text-center mt-3">
								    
									<button type="button" onclick="sendRequestToJoinTeam('<?= $_COMPANY->encodeId($groupid); ?>');" class="btn btn-affinity prevent-multi-clicks"><?= gettext("Send Request");?></button>

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

<script>
 	$(document).ready(function(){
		$('#redactor_content').initRedactor('redactor_content','',[],[],'<?= $_COMPANY->getImperaviLanguage(); ?>');
		$(".redactor-voice-label").text("<?= gettext('Message');?>");
		$('#send_discover_pair_request').on('shown.bs.modal', function () {
			$('#btn_close').trigger('focus')
		});
		redactorFocusOut('#subject'); // function used for focus out from redactor when press shift +tab.		
	});
	retainFocus("#send_discover_pair_request");
</script>