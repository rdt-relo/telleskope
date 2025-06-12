<?php include __DIR__ . '/header.html'; ?>
<!-- SurveyJs-->
<link rel="stylesheet" href="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/modern.min.css">
<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/survey.jquery.min.js"></script>
<style>
    .sv-root-modern .sv-completedpage {
        color: rgb(64, 64, 64);
        background-color: rgb(255, 255, 255) !important;
    }
    .sv-root-modern .sv-question__title--answer {
        background-color: rgba(255, 255, 255);
    }
    .sv-completedpage{
        margin-left: 0 !important;
    }
    .sv-btn.sv-btn--navigation
    { 
      margin-bottom: 10px;
    }
    input[value="Proceed with RSVP update"], .sv-btn.sv-btn--navigation.sv-footer__complete-btn {
        float: left;
    }
</style>
<div aria-label="update event survey" id="eventSurveyModal" class="modal fade">
    <div aria-label="<?= sprintf(gettext('Update %s responses'),$surveyQuestions['survey_title']); ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="form_title"><?= sprintf(gettext('Update %s responses'),$surveyQuestions['survey_title']); ?></h4>
                <button aria-label="Close dialog" type="button" id="modalCloseButton"  onclick="window.location.href='success_callback.php'" class="close" data-dismiss="modal" >&times;</button>
            </div>
            <div class="modal-body">
                <div class="text-center mt-2" id="loadingSpinner">
                    <span class="fa fa-spinner fa-spin fa-2x"></span>&emsp;<?= gettext("loading questions");?>...
                </div>
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
            Survey.StylesManager.applyTheme("Default");
            Survey.settings.titleTags.survey = "h1";
            Survey.settings.titleTags.page = "h2";
            Survey.settings.titleTags.panel = "h3";
            Survey.settings.titleTags.question = "h2";

            
            var surveyJSON = <?= json_encode($surveyQuestions['survey_questions']); ?>;
            $("#loadingSpinner").hide();
            $("#language_selection").show();
            Survey.slk('<?= Config::Get('SURVEY_JS_LICENSE_KEY') ?>');
            window.survey = new Survey.Model(surveyJSON);
            survey.completeText = '<?= addslashes(gettext('Update Survey Responses'))?>';
            survey.data = <?= json_encode(array_merge(array('question0'=>$joinStatus),$surveyResponses?:array())); ?>;
            $("#surveyContainer").Survey({
                model: survey,
                onComplete: updateEventSurveyResponses
            });

            function updateEventSurveyResponses(survey) {
                var responseJson = JSON.stringify(survey.data);

                <?php if ($survey_trigger == Event::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT']){ ?>
                        $.ajax({
                            url: 'ajax_native.php?updatePostEventSurveyResponses=1',
                            type: "POST",
                            data: {
                                'eventid': '<?= $_COMPANY->encodeId($eventid); ?>',
                                'survey_trigger': '<?= $survey_trigger; ?>',
                                'responseJson': responseJson
                            },
                            success: function (data) {
                                try {
                                    let jsonData = JSON.parse(data);
                                    swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false}).then(function(result) {
                                        if(jsonData.status == 1){
                                            window.location.href= 'success_callback.php';
                                        }
                                    });;
                                } catch(e) { swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false}); }
                            }
                        });
                <?php } else{ ?>
                    saveEventPreJoinSurveyResponse('<?= $_COMPANY->encodeId($eventid); ?>', <?= $joinStatus; ?>,'<?=$survey_trigger;?>', responseJson);
                <?php } ?>
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
            $('#eventSurveyModal').modal({
                backdrop: 'static',
                keyboard: false
            });
        }, 1000);

    });


</script>