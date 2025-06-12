
<!-- SurveyJs Stuff-->
<link rel="stylesheet" href="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/defaultV2.css">
<link rel="stylesheet" href="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/modern.min.css">
<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/survey.jquery.min.js"></script>
<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/vendor/knockout-latest.js"></script>
<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/survey.i18n.min.js"></script>
<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/survey-knockout-ui.min.js"></script>
<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/survey-creator-core.min.js"></script>
<link rel="stylesheet" href="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/survey-creator-core.css">
<!-- <script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.9.110/survey-creator.min.i18n.min.js"></script> -->
<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/survey-creator-knockout.min.js"></script>


<style>
    /* body {
        background-color: #ffffff !important;
    } */
    .svd_container .svd_content {
        width: 100%;
        text-align: left;
    }
    .sv-question,.sv-btn,.sv-container-modern,.svd_container,.sv_main,.sv-title,.sv-completedpage {
        font-family: 'Lato-Regular' !important;
    }
    .svd_container .svd_content .svd_survey_designer .svd_editors .svd_questions_editor {
        padding-left: 15px;
    }
    .sv_header__text{
        padding: 10px;
        width:100% !important;
    }
    .title_editable, .description_editable{
        width:100% !important;
    }
    .sv_row > div{
        padding:10px !important;
    }
    input[type=text]{
        width: 100%;
        padding: 10px;
    }
    .svd_container.sv_default_css .svd_surveyjs_designer_container.svd_surveyjs_designer_container .sv_row .svd_question{
        background-color: #f4f4f4 !important;
        margin-bottom:15px;
    }
    .sv_main.sv_default_css .sv_p_root > .sv_row{
        background-color: #ffffff !important;
    }
    .sv_body{
        padding-top:20px !important;
    }
    #svd-save {
        display: none;
    }
    .footer-divider{
        border-bottom: 10px solid rgb(211, 211, 209);
    }

    .sd-dropdown--empty:not(.sd-input--disabled), .sd-dropdown--empty:not(.sd-input--disabled) .sd-dropdown__value {
        padding-top: 2px;
    }
    .spg-dropdown {
        padding-top: 2px;
    }

    .svc-page__question-type-selector {
        height: auto;
    }

    .svc-tab-designer .sd-container-modern.sd-container-modern--static {
        max-width: 100% !important;
        padding-left:15px;
    }
    .svc-page__question-type-selector:hover, .svc-page__question-type-selector:focus {
        height: 56px;
        width: 44px;
        padding: 1px 0px 0px 10px;
    }
    label[title='Video']{
        display:none;
    }
    /* .spg-input.spg-dropdown.sd-input.sd-dropdown{
        pointer-events: none;
        cursor: not-allowed;
    } */
    /* hide vavigate to url input option form Survey complete setting block */
    div[data-name="navigateToUrl"] { display:none; } 
   
    .svc-page__content--new{
        display: none;
    }
    #convertInputType {
        display: none !important;
    }

    .container-fluid, .container-lg, .container-md, .container-sm, .container-xl {
        padding-right: 0px;
        padding-left: 0px;
    }
</style>
<div id="main_section" class="container inner-background inner-background-tall">
    <div class="container-fluid">
        <div class="row">          
            <div class="col-md-12">
               
                <div class="col-md-12 border-bottom">
                    <h4 class="p-3"><?= gettext('Custom Matching Attributes'); ?></h4>
                </div>
                <div class="col-md-12">
                    <div id="surveyContainer" style="width:100%;">
                        <div id="creatorElement" style="height: 100vh;"></div>
                    </div>
                </div>
                <div class="col-md-12 text-center p-3">
                    <button type="button" class="btn btn-primary" onclick="saveMatchingCustomAttributes()" ><?= gettext("Submit"); ?></button>
                    
                    <button class="btn btn-primary" onclick="backToTeamConfiguration('<?= $_COMPANY->encodeId($groupid); ?>')" ><?= gettext("Cancel"); ?></button>
                </div>
               
            </div>
        </div>
    </div>
</div>
</div>
 <script>
    // Survey
    var __options = {
        showLogicTab: false,
        // show the embedded survey tab. It is hidden by default
        showEmbededSurveyTab: false,
        // hide the test survey tab. It is shown by default
        showTestSurveyTab: false,
        // hide the JSON text editor tab. It is shown by default
        showJSONEditorTab: false,
        // show the "Options" button menu. It is hidden by default
        showOptions: false,
        // Question Types
        // questionTypes: ["text", "comment", "boolean","checkbox", "radiogroup", "dropdown", "rating", "ranking"],
        questionTypes: ["checkbox", "radiogroup", "rating","text","comment","dropdown","html"],
        
        showSidebar : false,
        allowControlSurveyTitleVisibility:false,
        showSurveyTitle:'never'
    };

    Survey.slk('<?= Config::Get('SURVEY_JS_LICENSE_KEY') ?>');
    // Hide page title
    Survey.settings.allowShowEmptyTitleInDesignMode = false;
    // Hide add logo feature
    // Survey.Serializer.removeProperty("survey", "logo");
    // Survey.Serializer.removeProperty("selectbase", "choicesByUrl");
    // Global create the SurveyJS Creator and render it in div with id equals to "creatorElement"
    var __creator = new SurveyCreator.SurveyCreator( __options);

    // In order to avoid question conflicts in surveys which have been published, we will use custom numbering
    // to always get a unique number for every newly added question.
    let teleskopeQuestionCounter = <?=$teleskopeQuestionCounter?>;
        __creator.onQuestionAdded.add((sender, options) => {
            const question = options.question;
            question.name = `question${teleskopeQuestionCounter}`;
            teleskopeQuestionCounter++;
        });

    __creator.render("creatorElement");
    var __id = 0;
    var __groupid = 0;

    // Hide add logo feature
    Survey.Serializer.removeProperty("survey", "logo");
    Survey.Serializer.removeProperty("selectbase", "choicesByUrl");
    Survey.JsonObject.metaData.findProperty("question", "name").readOnlyValue = true;
    Survey.JsonObject.metaData.findProperty("itemvalue", "value").readOnlyValue = true;
    
    //A black list of properties displayed in Logic categories for different survey elements
    var propertyStopList = [
        // "visibleIf",
        // "enableIf",
        // "requiredIf",
        // "bindings",
        // "defaultValueExpression",
        // "columnsVisibleIf",
        // "rowsVisibleIf",
        // "hideIfChoicesEmpty",
        //"choicesVisibleIf",
        //"choicesEnableIf",
        // "minValueExpression",
        // "maxValueExpression",
        // "calculatedValues",
        // "triggers"
        "cookieName",
    ];

    __creator
        .onShowingProperty
        .add(function (sender, options) {
            options.canShow = propertyStopList.indexOf(options.property.name) == -1;
        });

    __creator.onElementAllowOperations.add(function (_, options) {
        options.allowChangeType = false;
    });

    function initSurveyCreator(json,groupid) {
        // Color customization
        // var defaultThemeColorsSurvey = Survey.StylesManager.ThemeColors["default"];
        // defaultThemeColorsSurvey["$main-color"] = "#0077b5";
        // defaultThemeColorsSurvey["$main-hover-color"] = "#0D5380";
        // defaultThemeColorsSurvey["$text-color"] = "#505050";
        // defaultThemeColorsSurvey["$header-color"] = "#0077b5";
        // defaultThemeColorsSurvey["$header-background-color"] = "#505050";
        // defaultThemeColorsSurvey["$body-container-background-color"] = "#f8f8f8";
        // Survey.StylesManager.applyTheme();

        // var defaultThemeColorsEditor = SurveyCreator.StylesManager.ThemeColors["default"];
        // defaultThemeColorsEditor["$primary-color"] = "#0077b5";
        // defaultThemeColorsEditor["$secondary-color"] = "#0077b5";
        // defaultThemeColorsEditor["$primary-hover-color"] = "#0D5380";
        // defaultThemeColorsEditor["$primary-text-color"] = "#505050";
        // defaultThemeColorsEditor["$selection-border-color"] = "#0077b5";
        // SurveyCreator.StylesManager.applyTheme();
        //You may use any of these: "default", "orange", "darkblue", "darkrose", "stone", "winter", "winterstone"
        // SurveyCreator.StylesManager.applyTheme("darkblue");
        //Show toolbox in the right container. It is shown on the left by default
        __creator.showToolbox = true;
        //Show property grid in the right container, combined with toolbox
        // __creator.showPropertyGrid = "right";
        //Make toolbox active by default
        // __creator.rightContainerActiveItem("toolbox"); // depricated
       
        var questionjson = {};
        if (json){
            questionjson = json;
        }
        __creator.JSON = questionjson;
        __groupid = groupid;
        __creator.saveSurveyFunc = saveMatchingCustomAttributes;

        // Remove toolbar items except undo/redo buttons
        __creator.toolbarItems.splice(2, 5);
    }

    function saveMatchingCustomAttributes() {
        var quesionJSON = (JSON.parse(__creator.text));
        quesionJSON['teleskopeQuestionCounter'] = teleskopeQuestionCounter;
        quesionJSON = JSON.stringify(quesionJSON);

        if ( quesionJSON.indexOf('elements') == -1) {
            swal.fire({
                title: '<?= gettext("Warning"); ?>',
                text:"<?= gettext('No questions entered! Are you sure you want to save this empty custom attributes form?')?>",
                showCancelButton: true,
            }).then(function(result) {
                if (result.isConfirmed) {
                    submitMatchingCustomAttributes(null, 'remove');
                } else {
                    return;
                }
            });
        } else {
            submitMatchingCustomAttributes(quesionJSON, 'no');
        }
    }

    function submitMatchingCustomAttributes(q, remove) {
        $.ajax({
            url: 'ajax_talentpeak.php?submitMatchingCustomAttributes=1',
            type: "post",
            data: {'quesionJSON': q, 'groupid': '<?= $_COMPANY->encodeId($groupid); ?>', 'removeCustomAttributes': remove},
            success: function (data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title, text: jsonData.message}).then(function (result) {
                        if (jsonData.status == 1) {
                            backToTeamConfiguration('<?= $_COMPANY->encodeId($groupid); ?>');
                        }
                    });
                } catch (e) {
                    swal.fire({title: '<?= gettext("Error")?>', text: "<?= gettext("Unknown error")?>"});
                }
            }
        });
    }

    function backToTeamConfiguration(g){
	    localStorage.setItem("manage_active", "manageTeamsConfiguration");
        window.location.href = 'manage?id='+g+'#algorithmtab';
    }
</script>

<script>
    var json = <?= $jsonObj; ?>;
    // Init Survey
    initSurveyCreator(json,'<?= $_COMPANY->encodeId($groupid); ?>');

</script>

