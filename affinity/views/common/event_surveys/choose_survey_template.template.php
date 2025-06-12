<div id="eventSurveyTemplateFormModal" tabindex="-1" class="modal fade">
	<div aria-label="<?= $form_title; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h2 class="modal-title" id="form_title"><?=$form_title?></h2>
				<button aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
          <div class="col-md-12">
              <form class="form-horizontal" id="surveySettingForm" method="post" action="" >
                  <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                
                  <div class="form-group">
                    <label class="control-lable" for="templatelist_newsurvey"><?=gettext('Survey Template')?>: <span style="color: #ff0000;"> *</span></label>
                    <select id="templatelist_newsurvey" name="templatelist_newsurvey"  style="width:100%" class="form-control" onchange="chooseEventSurveyTemplate(this.value);">
                      <option value=""><?=gettext('Choose a template from the list or create a survey')?></option>
                      <option style="color: #0077b5;" value="<?= $_COMPANY->encodeId(0) ; ?>"><?=gettext('Create New Survey')?></option>
                    <?php
                      if(!empty($templateSurveys) > 0){
                        foreach ($templateSurveys as $templateSurveyRow) { ?>
                          <option value="<?= $_COMPANY->encodeId($templateSurveyRow['surveyid']);  ?>"><?= $templateSurveyRow['surveyname'];  ?></option>
                        <?php
                        }
                      }  ?>
                    </select>
                  </div>
                  <div class="form-group text-center">
                    <button type="button" data-dismiss="modal"class="btn btn-primary"><?= gettext("Cancel");?></button>
                  </div>  
                
              </form>
          </div>
			</div>
		</div>  
	</div>
</div>

<script>
    function chooseEventSurveyTemplate(t) {
        if (t == ''){
            swal.fire({title: '<?= gettext("Error"); ?>',text:"<?= gettext('Please choose an option'); ?>"});
            return;
        }
        createEventSurveyCreateUpdateURL('<?= $_COMPANY->encodeId($event->id())?>', '<?= $survey_trigger; ?>',t)
    }

$('#eventSurveyTemplateFormModal').on('shown.bs.modal', function () {	
    $('.close').trigger('focus');
});
</script>
