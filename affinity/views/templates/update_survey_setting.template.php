<div id="updateSurveySettingFormModal" class="modal fade">
	<div aria-label="<?=$form_title?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
        <h2 class="modal-title" id="form_title"><?=$form_title?></h2>
				<button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
        <div class="col-md-12">
            <form class="form-horizontal" id="updateSurveySettingForm" method="post" action="" >
                <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                <p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>  
                <div class="surveyMainFields">
                  <div class="form-group">
                    <label class="control-lable" for="surveyname"><?= gettext("Survey Name");?>
                        <?php if($allowTitleUpdate ){ ?>
                        <span style="color: #ff0000;"> *</span>
                        <?php } else { ?>
                        <i class="fa fa-info-circle fa-small info-black" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext('%s editing is disabled because the %s has been submitted for approval.'), gettext("Survey Name"), gettext("Survey"))?>"></i>
                        <?php } ?>
                    </label>
                      <input type="text" class="form-control" id="surveyname" name="surveyname" value="<?= $survey->val('surveyname'); ?>" placeholder="<?= gettext("Survey Name");?>" <?= !$allowTitleUpdate ? 'readonly' : ''; ?> >
                  </div>

                <div class="form-group surveyMainFields">
                    <div class="EmailNotificationsList">
                        <label for="sendEmailNotificationTo"><?= gettext("Get E-mail Notfication");?> &nbsp;<i class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('Provide an email address to get an email notification each time a response is submitted')?>"></i></label>
                        <input type="email" class="form-control" id="sendEmailNotificationTo" name="sendEmailNotificationTo" value="<?= $survey->val('send_email_notification_to'); ?>" >
                    </div>
                </div>

        <fieldset class="form-group surveyMainFields">
            <div class="form-group">
              <?php if($survey->val('surveysubtype')!='127'){ ?>
                <legend class="control-label"><?= gettext("Settings");?></legend>
                <?php if ($isActionDisabledDuringApprovalProcess) { ?>
                            <div class="alert-warning p-3 text-small">
                                <?=gettext('This survey is currently in the approval process or has been approved. Changes in survey settings are not permitted. To make changes, request the survey approver to deny the approval.')?>
                            </div>
                <?php } ?>
                  <div class="radio">
                      <label class="checkbox-inline"><input type="checkbox" id="is_required" name="is_required" value="1" <?= $survey->val('is_required') ? 'checked' : ''; ?> <?= $isActionDisabledDuringApprovalProcess ? 'disabled' : ''; ?>>&nbsp; <?= gettext("Response required");?>&nbsp;<i class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('If selected, option to close survey pop-up screen is disabled')?>"></i></label>
                  </div>
              <?php } ?>

              <?php if ($survey->isDraft()) { ?>

                <?php if ($survey->val('surveysubtype') == '127') { ?>
                <legend class="control-label"><?= gettext("Settings");?></legend>
                <?php } ?>

                <div class="radio" id="anonymity_div">
                    <label class="checkbox-inline"><input type="checkbox" id="anonymity" name="anonymity" value="1" <?= $survey->val('anonymous') ? 'checked' : ''; ?> <?= $isActionDisabledDuringApprovalProcess ? 'disabled' : ''; ?>>&nbsp; <?= gettext("Anonymous");?>&nbsp;<i class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('If selected, survey responses will be saved anonymously')?>"></i></label>
                </div>
                <?php } ?>
            </div>
        </fieldset>
                
                <div class="form-group text-center mt-4">
                    <button type="button" data-dismiss="modal"class="btn btn-primary"><?= gettext("Cancel");?></button>&nbsp;
                    <button type="button" name="submit" onclick="updateSurveySetting('<?=$encGroupId;?>','<?=$encSurveyId;?>');"  class="btn btn-primary survey-btn-submit prevent-multi-clicks"><?= gettext("Update");?></button>
                </div>  
            </form>
        </div>
			</div>
		</div>  
	</div>
</div>

<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    })

$('#updateSurveySettingFormModal').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus');
});
</script>