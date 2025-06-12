<div id="surveySettingFormModal" class="modal fade" tabindex="-1">
	<div aria-label="<?= $form_title; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h2 class="modal-title" id="form_title"><?=$form_title?></h2>
				<button aria-label="close" id="btn_close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
          <div class="col-md-12">
              <form class="form-horizontal" id="surveySettingForm" method="post" action="" >
                  <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                  <p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p> <br>   
                <div id="admin_survey_container">
                  <div class="form-group">
                    <label for="surveyname" class="control-lable" ><?= gettext("Survey Name");?><span style="color: #ff0000;"> *</span></label>
                    <input type="text" class="form-control" id="surveyname" surveyname="surveyname" placeholder="<?= gettext("Survey Name");?>" required>
                  </div>
                  <div class="form-group">
                      <label class="control-lable" ><?= gettext("Trigger");?><span style="color: #ff0000;"> *</span></label>
                      <select aria-label="<?= gettext('Select Survey Trigger');?>" type="text" class="form-control" id="surveyTrigger" name="trigger" onchange="getOptions(this.value)" required>
                          <option value=""><?= gettext("Select Survey Trigger");?></option>
                          <option value="3" ><?= gettext("On Login");?></option>
                          <option value="127" ><?= gettext("Link");?></option>
                      </select>
                      <div id="surveyTriggerNote"></div>
                  </div>
                  <fieldset class="form-group">
                      <legend class="control-label"><?= gettext("Settings");?></legend>
                      <div class="radio" id="anonymity_div">
                          <label class="checkbox-inline"><input type="checkbox" id="anonymity" name="anonymity" value="1" <?= $_COMPANY->getAppCustomization()['surveys']['default_anonymous_survey'] ? 'checked' : ''; ?>>&nbsp; <?= gettext("Anonymous");?>&nbsp;</label><i aria-label="<?=gettext('If selected, survey responses will be saved anonymously')?>" tabindex="0" class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('If selected, survey responses will be saved anonymously')?>"></i>
                      </div>
                      <div class="radio" id="is_required_div">
                          <label class="checkbox-inline"><input type="checkbox" id="is_required" name="is_required" value="1">&nbsp; <?= gettext("Response required");?>&nbsp;</label><i aria-label="<?=gettext('If selected, option to close survey pop-up screen is disabled')?>" tabindex="0" class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('If selected, option to close survey pop-up screen is disabled')?>"></i>
                      </div>
                      <div class="radio" id="allow_multiple_div">
                          <label class="checkbox-inline"><input type="checkbox" id="allow_multiple" name="allow_multiple" value="1">&nbsp; <?= gettext("Allows Multiple Responses");?>&nbsp;</label><i aria-label="<?=gettext('If selected, users can respond multiple times to the same survey and each response will be saved seperately')?>" tabindex="0" class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('If selected, users can respond multiple times to the same survey and each response will be saved seperately')?>"></i>
                      </div>
                  </fieldset>
                  <div class="form-group text-center">
                      <button type="button" data-dismiss="modal"class="btn btn-primary"><?= gettext("Cancel");?></button>&nbsp;
                      <button type="button" id="submit_admin_survey" name="submit" class="btn btn-primary"><?= gettext("Submit");?></button>
                  </div>
                </div>
                <div id="templatelistNewsurveyDiv" style="display:none">
                  <div class="form-group">
                    <label for="templatelist_newsurvey" class="control-lable"><?=gettext('Survey Template')?>: <span style="color: #ff0000;"> *</span></label>
                    <select aria-label="<?= gettext('Survey Template');?>" id="templatelist_newsurvey" name="templatelist_newsurvey"  style="width:100%" class="form-control" onchange="submitGlobalSurveySetting(this.value);">
                      <option value=""><?=gettext('Choose a template from the list or create a new survey')?></option>
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
                </div>
              </form>
          </div>
			</div>
		</div>  
	</div>
</div>

<script>
  $(document).ready(function() {
    $("#submit_admin_survey").click(function(){
      var surveyname = $("#surveyname").val();
      var surveyTrigger = $("#surveyTrigger").val();
      if (surveyname == '') {
        swal.fire({title: 'Error',text:"<?= gettext('Please enter a survey name');?>"});
      } else if (surveyTrigger == '') {
        swal.fire({title: 'Error',text:"<?= gettext('Please select a survey trigger');?>"});
      } else {
        $("#admin_survey_container").hide();
        $("#templatelistNewsurveyDiv").show();
      }      
      $('#btn_close').trigger('focus');
      $(".swal2-confirm").focus();   
    }); 
  });
  function getOptions(v){
      // v values are 1 on group join, 2 on group leave, 3 on login, -1 on team close, -2 on team start
    if (v == 127){
      $("#is_required_div").hide();
      $("#is_required").prop("checked", false);
    } else{
      $("#is_required_div").show();
    }
    if (v == 3 || v < 0){
      $("#anonymity_div").hide();
      $("#anonymity").prop("checked", false);
    } else{
      $("#anonymity_div").show();
    }
    if(v == 127){
     $("#surveyTriggerNote").html('<div role="alert" class="alert alert-info linkNote mt-2 mb-1"><small style="line-height: normal"><?= addslashes(gettext("Note, when using a link based survey trigger, you will need to first activate the survey and click on Get Shareable Link to retrieve the Survey Link. Once you retrieve the Shareable Link you can use the Shareable Link in an Announcement, Newsletter, Event Description, or Direct Email to share the Surevy with your group members."));?></small></div>');
    }else{
     $("#surveyTriggerNote").html('');  
    }
  }
  $(function () {
      $('[data-toggle="tooltip"]').tooltip();
  })

$('#surveySettingFormModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
});

$("input:checkbox").keypress(function (event) {
if (event.keyCode === 13) {
	$(this).click();
}
});
</script>
<script>
    $(document).ready(function () {
        $('#anonymity').change(function (){
          if ($('#anonymity').prop('checked')){
            $('#allow_multiple').prop('checked', true);
          }
        });
    });
</script>