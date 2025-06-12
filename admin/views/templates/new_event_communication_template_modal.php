<!--
## Communication Templates Dependencies ##
$pagetitle
$communication_type_key
$emailTemplate null | array
-->
<div class="modal" id="addUpdateEmailEventCommunicationTemplateModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header">
        <h4 class="modal-title"><?= $pagetitle; ?></h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>

        <!-- Modal body -->
        <div class="modal-body">

            <div class="col-12">
                <form id="communication_template">
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <div class="col-12 form-group-emphasis">
                        <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Template Type")?></h5>
                        <!-- <p class="form-group-emphasis-heading-text"></p> -->
                        <div class="col-12">
                            <div class="form-group">
                                <label>CommunicationTemplate Type <span style="color:red;">*</span></label>
                                <select class="form-control" name="communication_type_key" id="communication_type_key" onchange="handleCommunicationKeySelectChange()">
                                    <option value="" <?=$communication_type_key ? 'disabled' : ''?> >Select Communication Template Type</option>
                                    <?php foreach($communication_type_keys as $communicationTypeKey => $communicationTypeValue) {
                                        if ($communication_type_key) {
                                        $sel = ($communication_type_key == $communicationTypeKey) ? 'selected' : 'disabled';
                                        } else {
                                        $sel = isset($existingTemplates[$communicationTypeKey]) ? 'disabled' : 'ssf';
                                        }
                                    ?>
                                    <option value="<?= $communicationTypeKey; ?>" <?= $sel; ?>><?= ucfirst($communicationTypeValue); ?></option>
                                    <?php } ?>
                                </select>
                                <div class="mt-3">
                                    <span class="communication_type_key_message" id="communication_type_key_message_reconciliation" style="display: none;">Reconciliation emails will be sent to all event contributors. You can set the number of days after the event's conclusion for these emails to be automatically delivered.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 form-group-emphasis">
                        <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Configure Emails")?></h5>
                        <!-- <p class="form-group-emphasis-heading-text"></p> -->
                        <div class="col-12">
                            
                            <div class="form-group">
                                <label>Email Subject <span style="color:red;">*</span></label>
                                <input type="text" class="form-control" placeholder="Email Subject" name="event_communication_email_subject" value="<?= htmlspecialchars($emailTemplate['subject'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Email Body</label>
                                <textarea class="form-control" placeholder="The event [[EVENT_TITLE]] needs your attention ... " name="event_communication_email_body" id="event_communication_email_body"><?=  htmlspecialchars($emailTemplate['body'] ?? '') ?></textarea>
                           
                                <p class="pt-3">
                                    <?=sprintf(
                                        gettext('You can use the following variables in \'Email Subject\' and \'Email Body\': <strong>%s</strong> which will be replaced by actual values when sending out the emails.'),
                                        '[[EVENT_ID]], [[EVENT_TITLE]], [[EVENT_URL]]'
                                    )?>
                                </p>
                            
                            </div>
                        </div>
                    </div>
                    <?php $trigger_days = $emailTemplate['trigger_days'] ?? 0; ?>
                    <div class="col-12 form-group-emphasis">
                        <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Trigger Configuration")?></h5>
                        <!-- <p class="form-group-emphasis-heading-text"></p> -->
                        <div class="col-12">
                            <div class="form-group">
                                <label>Email Trigger Time
                                    <span class="communication_type_key_trigger_message" id="communication_type_key_trigger_message_reconciliation" style="display:none;">(days after the event end date)</span>
                                    <!-- add other spans as needed -->
                                </label>
                                <select class="form-control" name="event_communication_trigger_days" id="event_communication_trigger_days">
                                    <option value="">Select Trigger Time</option>
                                    <option value="0" <?= $trigger_days == 0 ? 'selected' : ''; ?>>Disabled</option>
                                    <option value="1" <?= $trigger_days == 1 ? 'selected' : ''; ?>>1 day</option>
                                    <option value="3" <?= $trigger_days == 3 ? 'selected' : ''; ?>>3 days</option>
                                    <option value="7" <?= $trigger_days == 7 ? 'selected' : ''; ?>>7 days</option>
                                    <option value="15" <?= $trigger_days == 15 ? 'selected' : ''; ?>>15 days</option>
                                    <option value="30" <?= $trigger_days == 30 ? 'selected' : ''; ?>>30 days</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group text-center">
                        <button type="button" class="btn btn-primary" onclick="addUpdateEventCommunicationTemplate()" ><?= gettext("Submit");?></button>
                        <button type="submit" class="btn btn-primary" data-dismiss="modal" ><?= gettext("Close");?></button>
                    </div>
                </form>
            </div>

        </div>

        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
		$R('#event_communication_email_body',
			{
				linkNewTab: false,
				linkTarget: '_blank',
				linkTitle: true,
				linkNofollow: true,
				linkSize: 256,
				linkValidation: true,
				styles: true,
				minHeight: '200px',
				buttons: ['redo', 'undo', 'bold', 'italic', 'underline', 'link'],
				formatting: ['p', 'blockquote'],
				pasteLinkTarget: '_blank',
				pasteImages: true, //Since we have clipboardUpload set to true.
				pastePlainText: false,
				pasteBlockTags: [ 'p','blockquote'],
				pasteInlineTags: ['a', 'br', 'b', 'u', 'i'],
				// Uploads
				plugins: ['fontcolor'],
			}
		);
        handleCommunicationKeySelectChange();
	});

    function handleCommunicationKeySelectChange() {
        const selectedValue = $('#communication_type_key').val(); // Replace 'mySelect' with the actual ID of your select element
        $('.communication_type_key_message').hide();
        $('#communication_type_key_message_' + selectedValue).show();
        $('.communication_type_key_trigger_message').hide();
        $('#communication_type_key_trigger_message_' + selectedValue).show();
    }

    function addUpdateEventCommunicationTemplate() {
		var formdata = $('#communication_template')[0];
		var finaldata  = new FormData(formdata);
		$.ajax({
			url: 'ajax.php?addUpdateEventCommunicationTemplate=1',
			type: "POST",
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success : function(data) {
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
						if (jsonData.status == 1){
                            $('#addUpdateEmailEventCommunicationTemplateModal').modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
							location.reload();
						}
					});
				} catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
			}
		});
	}
</script>