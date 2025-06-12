<div id="surveySettingFormModal" class="modal fade" >
	<div aria-label="<?=$form_title?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
        <h2 class="modal-title" id="form_title"><?=$form_title?></h2>
				<button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
                <div class="col-md-12">
                    <form class="form-horizontal" id="surveySettingForm" method="post" action="" >
                        <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                        <p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>  
                        <div class="surveyMainFields">
                          <div class="form-group">
                            <label class="control-lable" for="surveyname"><?= gettext("Survey Name");?><span style="color: #ff0000;"> *</span></label>
                            <input type="text" class="form-control" id="surveyname" name="surveyname" placeholder="<?= gettext("Survey Name");?>" aria-required="true">
                          </div>
                          <div class="form-group">
                              <label class="control-lable col-md-12 p-0 m-0" ><?= gettext("Trigger");?><span style="color: #ff0000;"> *</span></label>
                              <select aria-label="<?= gettext('Select Survey Trigger');?>" type="text" class="form-control" id="surveyTrigger" name="trigger" onchange="getOptions(this.value)" aria-required="true">
                                <?php if ($_ZONE->val('app_type') != 'talentpeak') { ?>
                                  <option value=""><?= gettext("Select Survey Trigger");?></option>
                                  <option value="1" ><?= gettext("On Join");?></option>
                                  <option value="2" ><?= gettext("On Leave");?></option>
                                    <option value="127" ><?= gettext("Link");?></option>
                                <?php } else { ?>
                                  <option value="127" ><?= gettext("Link");?></option>
                                <?php } ?>
                              </select>
                              <div id="surveyTriggerNote"></div> 
                            </div>
                          <div class="form-group" id="options">
                            <label class="control-lable" id="scope_owner_label"><?= gettext("Scope");?><span style="color: #ff0000;"> *</span></label>
                            <select aria-label="<?= gettext('Scope');?>" type="text" class="form-control" id="group_chapter_channel_id" name="group_chapter_channel_id" aria-required="true">
                              <?php if ($_USER->canManageGroup($groupid)) { ?>
                                <option data-trg="Group" data-section="<?= $_COMPANY->encodeId(0) ?>" value="<?= $_COMPANY->encodeId(0) ?>" ><?= $group->val('groupname')." ".$_COMPANY->getAppCustomization()['group']['name-short']; ?></option>
                              <?php } ?>
                        <?php if(!empty($chapters)){ ?>
                              <optgroup label="<?=$_COMPANY->getAppCustomization()['chapter']['name-short']?>">
                              <?php for($i=0;$i<count($chapters);$i++){ ?>
                                <?php if ($_USER->canManageGroupChapter($groupid,$chapters[$i]['regionids'],$chapters[$i]['chapterid'])) { ?>
                                  <option data-trg="<?=$_COMPANY->getAppCustomization()['chapter']['name-short']?>" data-section="<?= $_COMPANY->encodeId(1) ?>" value="<?= $_COMPANY->encodeId($chapters[$i]['chapterid']) ?>"  >&emsp;<?= htmlspecialchars($chapters[$i]['chaptername']); ?></option>
                                <?php } ?>
                              <?php } ?>
                            </optgroup>
                        <?php } ?>
                        <?php if(!empty($channels)){ ?>
                            <optgroup  label="<?=$_COMPANY->getAppCustomization()['channel']['name-short']?>">
                              <?php for($i=0;$i<count($channels);$i++){ ?>
                              <?php if ($_USER->canManageGroupChannel($groupid,$channels[$i]['channelid'])) { ?>
                                <option  data-trg="<?=$_COMPANY->getAppCustomization()['channel']['name-short']?>"  data-section="<?= $_COMPANY->encodeId(2) ?>" value="<?= $_COMPANY->encodeId($channels[$i]['channelid']) ?>">&emsp;<?= htmlspecialchars($channels[$i]['channelname']); ?></option>
                              <?php } ?>
                              <?php } ?>
                              </optgroup>
                        <?php } ?>
                            </select>
                          </div>
                        </div>
                        

                        <fieldset class="form-group surveyMainFields">
                            <legend class="control-label"><?= gettext("Settings");?></legend>
                            <div class="radio" id="anonymity_div">
                                <label class="checkbox-inline"><input type="checkbox" id="anonymity" name="anonymity" value="1" <?= $_COMPANY->getAppCustomization()['surveys']['default_anonymous_survey'] ? 'checked' : ''; ?>>&nbsp; <?= gettext("Anonymous");?>&nbsp;</label><i aria-label="<?=gettext('If selected, survey responses will be saved anonymously')?>" tabindex="0" class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('If selected, survey responses will be saved anonymously')?>"></i>
                            </div>
                            <div class="radio" id="is_required_div">
                                <label class="checkbox-inline"><input type="checkbox" id="is_required" name="is_required" value="1">&nbsp; <?= gettext("Response Required");?>&nbsp;</label><i aria-label="<?=gettext('If selected, option to close survey pop-up screen is disabled')?>" tabindex="0" class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('If selected, option to close survey pop-up screen is disabled')?>"></i>
                            </div>
                            <div class="radio" id="allow_multiple_div">
                                <label class="checkbox-inline"><input type="checkbox" id="allow_multiple" name="allow_multiple" value="1">&nbsp; <?= gettext("Allows Multiple Responses");?>&nbsp;</label><i aria-label="<?=gettext('If selected, users can respond multiple times to the same survey and each response will be saved seperately')?>" tabindex="0" class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('If selected, users can respond multiple times to the same survey and each response will be saved seperately')?>"></i>
                            </div>
                        </fieldset>
                        <div id="templatelistNewsurveyDiv" style="display:none" class="form-group">
                          <label class="control-lable" for="email"><?=gettext('Template')?>: <span style="color: #ff0000;"> *</span></label>
                          <select aria-label="<?= gettext('Template');?>" id="templatelist_newsurvey" name="templatelist_newsurvey"  style="width:100%" class="form-control" onchange="submitSurveySetting('<?=$encGroupId;?>',this.value);">
                            <option value=""><?=gettext('Choose a template from the list or create a new survey')?></option>
                            <option style="color: #0077b5;" value="<?php echo $_COMPANY->encodeId(0) ; ?>"><?=gettext('Create New Survey')?></option>
                          <?php
                            if(!empty($templateSurveys) > 0){
                              foreach ($templateSurveys as $templateSurveyRow) { ?>
                                <option value="<?php echo $_COMPANY->encodeId($templateSurveyRow['surveyid']);  ?>"><?php echo $templateSurveyRow['surveyname'];  ?></option>
                              <?php
                              }
                            }  ?>
                          </select>
                        </div>
                        <div class="form-group text-center mt-4">
                            <button type="button" name="submit" onclick="checkSurveyValidations('<?=$encGroupId;?>');"  class="btn btn-primary survey-btn-submit"><?= gettext("Submit");?></button>
                            <button type="button" data-dismiss="modal"class="btn btn-affinity-gray"><?= gettext("Close");?></button>&nbsp;
                        </div>  
                    </form>
                </div>
			</div>
		</div>  
	</div>
</div>

<script>

function getOptions(v){
    // v values are 1 on group join, 2 on group leave, 3 on login, -1 on team close, -2 on team start
<?php if($_ZONE->val('app_type') != 'talentpeak'){ ?>
	if (v == 127){
    $("#scope_owner_label").html("Scope"+"<span style='color: #ff0000;'> *</span>");
    $("#is_required_div").hide();
    $("#is_required").prop("checked", false);
	} else{
    $("#scope_owner_label").html("Scope"+"<span style='color: #ff0000;'> *</span>");
    $("#is_required_div").show();
	}
<?php } ?>
    if (v == 3 || v < 0){
        $("#anonymity_div").hide();
        $("#anonymity").prop("checked", false);
    } else{
        $("#anonymity_div").show();
    }
  if (v == '-2'){
    $("#triggerInputSection").show();
  } else {
    $("#triggerInputSection").hide();
  }
  if(v == 127){
    $("#surveyTriggerNote").html('<div class="alert alert-info linkNote mt-2 mb-1"><small style="line-height: normal"><?= gettext("Note, when using a link based survey trigger, you will need to first activate the survey and click on Get Shareable Link to retrieve the Survey Link. Once you retrieve the Shareable Link you can use the Shareable Link in an Announcement, Newsletter, Event Description, or Direct Email to share the Surevy with your group members.");?></small></div>');
  }else{
    $("#surveyTriggerNote").html('');  
  }
  if(v == 1 || v == 2){
    isSet = $("#group_chapter_channel_id").val();
    selectedOption = $("#group_chapter_channel_id").find(':selected');
    if(isSet)
    {
      dataTrgValue = selectedOption.data('trg');
      trigerValue = $("#surveyTrigger").val();

    if(trigerValue == 1 || trigerValue == 2){
      JoinText = dataTrgValue+" Leave";
      if(trigerValue == 1){
        JoinText = dataTrgValue+" Join";
      }
      $("#surveyTriggerNote").html('<div class="alert alert-info linkNote mt-2 mb-1"><small style="line-height: normal">Note: The survey will be triggered on '+JoinText+'</small></div>');
    }
    }
  }
}
</script>

<script>
  $(document).ready(function() {
    $('#templatelist_newsurvey').select2({
      placeholder: "<?= gettext("Choose a template from the list or create a new survey");?>",
    });
  });
  $(function () {
      $('[data-toggle="tooltip"]').tooltip();
  })

  $('#surveySettingFormModal').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus');
});
</script>
<script>
    $(document).ready(function () {
        $('#anonymity').change(function (){
          if ($('#anonymity').prop('checked')){
          $('#allow_multiple').prop('checked', true);
          }
        });
      $('#group_chapter_channel_id').on('change', function() {
          selectedOption = $(this).find(':selected'); 
          dataTrgValue = selectedOption.data('trg');
          trigerValue = $("#surveyTrigger").val();
        if(trigerValue == 1 || trigerValue == 2){
          JoinText = dataTrgValue+" Leave";
          if(trigerValue == 1){
            JoinText = dataTrgValue+" Join";
          }
          $("#surveyTriggerNote").html('<div class="alert alert-info linkNote mt-2 mb-1"><small style="line-height: normal">Note: The survey will be triggered on '+JoinText+'</small></div>');
        }
      });
    });
</script>