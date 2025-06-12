<style>
  .sv-question,.sv-btn,.sv-container-modern,.svd_container,.sv_main,.sv-title,.sv-completedpage {
    font-family: 'Lato-Regular' !important;
  }
.survey-preview-modal {
  padding: 0 !important;
}
.survey-preview-modal .modal-dialog {
  width: 100%;
  max-width: none;
  height: 100%;
  margin: 0;
}
.survey-preview-modal .modal-content {
  height: 100%;
  border: 0;
  border-radius: 0;
}
.survey-preview-modal .modal-body {
  overflow-y: auto;
}
.sv-root-modern .sv-completedpage{
  background: #fff !important;
}

.sv-boolean__slider{
    margin-left: 0px !important;
}
.sv-question__form-group {
    padding-top: 20px;
    font-size: var(--sjs-font-size, 16px);
    margin-top: calc(2 * (var(--sjs-base-unit, var(--base-unit, 8px))));
    display: flex;
    flex-direction: column;
    gap: var(--sjs-base-unit, var(--base-unit, 8px));
    color: var(--sjs-general-forecolor, var(--foreground, #161616));
    white-space: normal;
    clear: both;
}
.sv-question__form-group textarea {
    padding: calc(1.5 * (var(--sjs-base-unit, var(--base-unit, 8px)))) calc(2 * (var(--sjs-base-unit, var(--base-unit, 8px))));
    line-height: calc(1.5 * (var(--sjs-internal-font-editorfont-size)));
    color: var(--sjs-font-editorfont-color, var(--sjs-general-forecolor, rgba(0, 0, 0, 0.91)));  
    background-color: var(--sjs-editorpanel-backcolor, var(--sjs-editor-background, var(--sjs-general-backcolor-dim-light, var(--background-dim-light, #f9f9f9))));   
    border-radius: var(--sjs-editorpanel-cornerRadius, var(--sjs-corner-radius, 4px));
    text-align: start;
    box-shadow: var(--sjs-shadow-inner, inset 0px 1px 2px 0px rgba(0, 0, 0, 0.15)), 0 0 0 0px var(--sjs-primary-backcolor, var(--primary, #19b394));
    transition: box-shadow var(--sjs-transition-duration, 150ms);
}


</style>
<div id="surveyPreview" class="modal fade survey-preview-modal">
	<div aria-label="<?=$form_title?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
        <h2 class="modal-title" id="form_title"><?=$form_title?>  </h2>
				<button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
          <div class="text-center mt-3" id="loadingSpinner">
            <span class="fa fa-spinner fa-spin fa-2x"></span>&emsp;<?= gettext("loading questions");?>...
          </div>

        <?php if(!empty($surveyLanguages)){ ?>
          <div class="col-12" id="language_selection" style="display:none;">
            <select class="form-control pull-right mb-2" style="max-width:300px" onchange="survey.locale = this.value;">
              
              <?php
                foreach($surveyLanguages['languages'] as $lang){
                  $langVal = $lang;
                  if ($lang == 'default'){
                    $langVal = "en";
                  }

                  $sel = "";
                  if ($langVal == $surveyLanguages['locale']){
                    $sel = "selected";
                  }

              ?>
              <option value="<?= $langVal; ?>" <?= $sel; ?>><?= Locale::getDisplayName($langVal); ?></option>
              <?php } ?>
            </select>
          </div>
        <?php } ?>
          
          <div class="col-12" id="surveyContainer"></div>
			</div>
		</div>  
	</div>
</div>
<script>
    $( document ).ready(function() {
      setTimeout(function(){

        Survey.slk('<?= Config::Get('SURVEY_JS_LICENSE_KEY') ?>');
        // Survey.StylesManager.applyTheme("defaultV2");
        // Color customization
        var defaultThemeColorsSurvey = Survey.StylesManager.ThemeColors["modern"];
        defaultThemeColorsSurvey["$main-color"] = "#0077b5";
        defaultThemeColorsSurvey["$main-hover-color"] = "#0D5380";
        defaultThemeColorsSurvey["$text-color"] = "#505050";
        defaultThemeColorsSurvey["$header-color"] = "#0077b5";
        defaultThemeColorsSurvey["$header-background-color"] = "#505050";
        defaultThemeColorsSurvey["$body-container-background-color"] = "#f8f8f8";
        Survey.StylesManager.applyTheme("Default");
        Survey.settings.titleTags.survey = "h1";
        Survey.settings.titleTags.page = "h2";
        Survey.settings.titleTags.panel = "h3";
        Survey.settings.titleTags.question = "h2";

        var surveyJSON = '<?= addslashes($survey_json); ?>';
        $("#loadingSpinner").hide();
        $("#language_selection").show();
        window.survey = new Survey.Model(surveyJSON);
        survey.locale = '<?= $surveyLanguages['locale'] ?? 'en'; ?>';
        $("#surveyContainer").Survey({
            model:survey,
        });
        
        setTimeout(function(){
          document.querySelectorAll('div[role="listbox"]').forEach(function (el){
            $(el).removeAttr( "role" )
          });
          const checkboxContainers = document.querySelectorAll('.sv-checkbox');

          for(const container of checkboxContainers){
            const questionTitle = container.closest('.sv-question__title');
            let questionText = '';
            if(questionTitle){
              questionText = questionTitle.textContent;
            }else{
              questionText = "Checkbox Group";
            }
            
            container.setAttribute('aria-label', questionText);
          }

          $('.sv-checkbox__svg, .sv-radio__svg').css({"border-color": "#949494", 
             "border-width":"3px", 
             "border-style":"solid"});
        },1000);

      },1000);
    });
  
$('#surveyPreview').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
});

$(document).click(function(event) {
    $("#hidden_div_for_notification").html('');
		$("#hidden_div_for_notification").removeAttr('aria-live'); 

    var submitBtn = $(event.target).hasClass('sv-footer__complete-btn');
    if(submitBtn){
        $("#hidden_div_for_notification").attr({role:"status","aria-live":"polite"});   
        document.getElementById('hidden_div_for_notification').innerHTML="<?= gettext('Thank you for completing the survey') ?>";
        $("#btn_close").focus();
    }
});



</script>