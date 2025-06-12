<style>
    .sv-root-modern .sv-completedpage {
        color: rgb(64, 64, 64);
        background-color: rgb(255, 255, 255) !important;
    }
    .sv-root-modern .sv-question__title--answer {
        background-color: rgba(255, 255, 255);
    }

    .sv-checkbox--allowhover:hover .sv-checkbox__svg {
    border: none;
    background-color: #fff;
    fill: #fff;
}

</style>
<div aria-label="event survey" id="eventSurveyModal" class="modal fade">
    <div aria-label="<?= $surveyData['survey_title']; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="form_title"><?= $surveyData['survey_title']; ?></h4>
                <button aria-label="Close dialog" type="button" id="modalCloseButton" class="close" data-dismiss="modal" >&times;</button>
            </div>
            <div class="modal-body">
                <div class="text-center mt-2" id="loadingSpinner">
                    <span class="fa fa-spinner fa-spin fa-2x"></span>&emsp;<?= gettext("loading questions");?>...
                </div>
                <input type="hidden" id="joinStatus" name="joinStatus" value="<?= $_COMPANY->encodeId($joinStatus)?>">
                <div class="col-12" id="surveyContainer"></div>
            </div>
            <div class="modal-footer text-center" id="backToEvent" style="display:none;">
               <button class="btn btn-affinity" onclick="getEventDetailModal('<?= $_COMPANY->encodeId($event->id())?>','<?= $_COMPANY->encodeId(0)?>','<?= $_COMPANY->encodeId(0)?>')"><?= gettext('Back to Event Detail')?></button>
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
                onComplete: saveEventPreJoinSurveyResponse
            });

            function saveEventPreJoinSurveyResponse(survey) {
                var responseJson = JSON.stringify(survey.data);
                $("#backToEvent").show();
                joinEvent('<?= $_COMPANY->encodeId($eventid); ?>',<?= $joinStatus; ?>,responseJson,'<?=$survey_trigger;?>');
            }
            setTimeout(function(){
                document.querySelectorAll('div[role="listbox"]').forEach(function (el){
                    $(el).removeAttr( "role" );
                });
                $('.sv-checkbox__svg, .sv-radio__svg').css({"border-color": "#949494", 
                    "border-width":"3px", 
                    "border-style":"solid"});
            },1000);
            setTimeout(function(){
                $("#modalCloseButton").focus();
                // Trap focus inside modal
                // add all the elements inside modal which you want to make focusable
                let  ___focusableElements =
                'button, [href], input, select, textarea, radio,checkbox,rating,[tabindex]:not([tabindex="-1"])';
                let ___modal = document.querySelector('.modal'); // select the modal by class

                if (___modal != null || false){

                    let ___firstFocusableElement = ___modal.querySelectorAll(___focusableElements)[0]; // get first element to be focused inside modal
                    let ___focusableContent = ___modal.querySelectorAll(___focusableElements);
                    let ___lastFocusableElement = ___focusableContent[___focusableContent.length - 1]; // get last element to be focused inside modal


                    document.addEventListener('keydown', function(e) {
                    let ___isTabPressed = e.key === 'Tab' || e.keyCode === 9;

                    if (!___isTabPressed) {
                        return;
                    }

                    if (e.shiftKey) { // if shift key pressed for shift + tab combination
                        if (document.activeElement === ___firstFocusableElement) {
                        ___lastFocusableElement.focus(); // add focus for the last focusable element
                        e.preventDefault();
                        }
                    } else { // if tab key is pressed
                        if (document.activeElement === ___lastFocusableElement) { // if focused has reached to last focusable element then focus first focusable element after pressing tab
                        ___firstFocusableElement.focus(); // add focus for the first focusable element
                        e.preventDefault();
                        }
                    }
                    });

                    ___firstFocusableElement.focus();
                }
            },1000);
        }, 1000);

    });


</script>