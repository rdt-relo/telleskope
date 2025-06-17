<?php include __DIR__ . '/header.html'; ?>
<!-- SurveyJs-->
<link rel="stylesheet" href="<?=TELESKOPE_.._STATIC?>/vendor/js/surveyjs-1.11.2/modern.min.css">
<script src="<?=TELESKOPE_.._STATIC?>/vendor/js/surveyjs-1.11.2/survey.jquery.min.js"></script>

<style>
    .sv-root-modern .sv-completedpage {
        color: rgb(64, 64, 64);
        background-color: rgb(255, 255, 255) !important;
    }
    .sv-root-modern .sv-question__title--answer {
        background-color: rgba(255, 255, 255);
    }
    .sv-btn.sv-btn--navigation
    { 
      margin-bottom: 10px;
    }
    input[value="Proceed with RSVP update"], .sv-btn.sv-btn--navigation.sv-footer__complete-btn {
        float: left;
    }
</style>
<div aria-label="event survey" id="eventSurveyModal" class="modal fade">
    <div aria-label="<?= $surveyData['survey_title']; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="form_title"><?= $surveyData['survey_title']; ?></h4>
                <button aria-label="Close dialog" type="button" id="modalCloseButton"  onclick="window.location.href='success_callback.php'" class="close" data-dismiss="modal" >&times;</button>
            </div>
            <div class="modal-body">
                <div class="text-center mt-2" id="loadingSpinner">
                    <span class="fa fa-spinner fa-spin fa-2x"></span>&emsp;<?= gettext("loading questions");?>...
                </div>
                <input type="hidden" id="joinStatus" name="joinStatus" value="<?= $_COMPANY->encodeId($joinStatus)?>">
                <div class="col-12" id="surveyContainer"></div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        setTimeout(function () {
            // Color customization
            // Survey.StylesManager.applyTheme("defaultV2");
            var defaultThemeColorsSurvey = Survey.StylesManager.ThemeColors["modern"];
            defaultThemeColorsSurvey["$main-color"] = "#0077b5";
            defaultThemeColorsSurvey["$main-hover-color"] = "#0D5380";
            defaultThemeColorsSurvey["$text-color"] = "#505050";
            defaultThemeColorsSurvey["$header-color"] = "#0077b5";
            defaultThemeColorsSurvey["$header-background-color"] = "#505050";
            defaultThemeColorsSurvey["$body-container-background-color"] = "#f8f8f8";
            Survey.slk('<?= Config::Get('SURVEY_JS_LICENSE_KEY') ?>');
            Survey.StylesManager.applyTheme("Default");
            Survey.settings.titleTags.survey = "h1";
            Survey.settings.titleTags.page = "h2";
            Survey.settings.titleTags.panel = "h3";
            Survey.settings.titleTags.question = "h2";

            
            var surveyJSON = <?= json_encode($surveyData['survey_questions']); ?>;
            $("#loadingSpinner").hide();
            $("#language_selection").show();
            window.survey = new Survey.Model(surveyJSON);
            survey.completeText = '<?= addslashes(gettext('Proceed with RSVP update'))?>';
            survey.data = <?= json_encode(array('question0'=>$joinStatus)); ?>;
            $("#surveyContainer").Survey({
                model: survey,
                onComplete: initSaveEventPreJoinSurveyResponse
            });

            function initSaveEventPreJoinSurveyResponse(survey) {
                let eventid = '<?= $_COMPANY->encodeId($eventid); ?>';
                let joinStatus = <?= $joinStatus; ?>;
                let trigger = '<?=$survey_trigger;?>';
                let eventSurveyResponse = JSON.stringify(survey.data);
                saveEventPreJoinSurveyResponse(eventid, joinStatus, trigger, eventSurveyResponse);
            }
            setTimeout(function(){
                document.querySelectorAll('div[role="listbox"]').forEach(function (el){
                    $(el).removeAttr( "role" );
                });
                $('.sv-checkbox__svg, .sv-radio__svg').css({"border-color": "#949494", 
                    "border-width":"3px", 
                    "border-style":"solid"});
            },1000);
            $('#eventSurveyModal').modal({
                backdrop: 'static',
                keyboard: false
              });
        }, 1000);
    });
</script>