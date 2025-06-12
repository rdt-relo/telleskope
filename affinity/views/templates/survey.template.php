<style>
    .sv-root-modern .sv-completedpage {
        color: rgb(64, 64, 64);
        background-color: rgb(255, 255, 255) !important;
    }
</style>
<div tabindex="-1"  id="showSurveyModal" class="modal fade">
    <div aria-label="<?= $survey->val('surveyname'); ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title" id="form_title"><?= $survey->val('surveyname'); ?></h1>
                <button aria-label="Close dialog" type="button" id="modalCloseButton" class="close" data-dismiss="modal" <?php if ($groupid && ($survey->val('surveytype') == Survey2::SURVEY_TYPE['GROUP_MEMBER'])) { ?> onclick="updateGroupJoinLeaveButton('<?= $encGroupId; ?>')" <?php } ?>>&times;</button>
            </div>
            <div class="modal-body">
                <div class="text-center mt-2" id="loadingSpinner">
                    <span class="fa fa-spinner fa-spin fa-2x"></span>&emsp;<?= gettext("loading questions");?>...
                </div>
                <input type="hidden" id="objectId" name="objectId" value="<?= isset($objectId) ?  $objectId : ''; ?>">
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
    $(document).ready(function () {
    <?php if($survey->val('is_required')){ ?>   
        $("#modalCloseButton").hide();
    <?php } ?>
        setTimeout(function () {
            // Color customization
            // Survey.StylesManager.applyTheme("defaultV2");
            Survey.slk('<?= Config::Get('SURVEY_JS_LICENSE_KEY') ?>');
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

            var surveyJSON = <?= $survey->val('survey_json'); ?>;
            $("#loadingSpinner").hide();
            $("#language_selection").show();
            window.survey = new Survey.Model(surveyJSON);
            $("#surveyContainer").Survey({
                model: survey,
                onComplete: saveSurveyResponse
            });

            function saveSurveyResponse(survey) {
                var objectId = $("#objectId").val();
                var responseJson = JSON.stringify(survey.data);
                $.ajax({
                    url: 'ajax.php?saveSurveyResponse=1',
                    type: "post",
                    data: {
                        'responseJson': responseJson,
                        'groupid': '<?= $encGroupId; ?>',
                        'surveyid': '<?= $_COMPANY->encodeId($survey->val('surveyid')); ?>',
                        'objectId':objectId
                    },
                    success: function (data) {
                        $("#modalCloseButton").show();
                        $(".sv-root-modern > form").attr('tabindex',-1);                        
                        $(".sv-root-modern > form").focus();
                        $("#modalCloseButton").focus();
                        $(".modal-content").attr('id', 'tabBlock');
                        document.getElementById('tabBlock').addEventListener('keydown', (event) => {
                            if (event.keyCode === 9) {
                                event.preventDefault();
                            }
                        });
                    }
                });
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

    $('#showSurveyModal').on('show.bs.modal', function (e) {	
        setTimeout(function(){
            document.querySelectorAll('.sv-selectbase').forEach(function (el){                
                $(el).attr("role", 'group');
            });

            document.querySelectorAll('.sv-question__num').forEach(function (el){                
                $(el).removeAttr("aria-hidden");
            });
            
        },2000);	    
	});
</script>