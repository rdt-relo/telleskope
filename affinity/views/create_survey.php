
    <script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/vendor/knockout-latest.js"></script>
    <script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/survey.core.min.js"></script>  
    <script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/index.min.js"></script>
    <script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/survey.i18n.min.js"></script>
    <!--<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/survey-creator-core.i18n.min.js"></script>-->
    <script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/survey-knockout-ui.min.js"></script>
    <script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/survey-creator-core.min.js"></script>   
    <script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/survey-creator-knockout.min.js"></script>
    <link rel="stylesheet" href="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/defaultV2.css" />
    <link rel="stylesheet" href="<?=TELESKOPE_CDN_STATIC?>/vendor/js/surveyjs-1.11.2/survey-creator-core.css" />
    
<style>
    body {
        background-color: #ffffff !important;
    }
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

    .svc-logic-paneldynamic div.svc-logic-operator {
        height: auto !important;
    }
    .sl-dropdown__value {
        padding-top: 5px !important;
    }

    #convertInputType {
        display: none !important;
    }

</style>

<main>

<div class="inner-background inner-background-tall footer-divider">
        <div class="row row-no-gutters">
            <div class="col-md-12 pt-4 pl-5 pr-5" >
                <div class="col-sm-6 surveyTitle ">
                    <h3 class=""><?= $pagetitle; ?></h3>
                </div>
                <div class="col-sm-6 ">
                    <a href="javascript:void(0)" class="btn btn-affinity-gray pull-right mr-3"
                    onclick="goBack()">
                        <?= gettext("Close");?></a>
                    <button class="btn btn-primary pull-right mr-3" onclick="saveAndExitSurvey();"><?= gettext("Save Survey & Exit");?></button>
                </div>
                <div class="col-md-12 mt-4">
                    <div id="surveyContainer" style="width:100%;">
                        <div id="creatorElement" style="height: 100vh;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

    <script>
        // Survey
        var __options = { 
            showLogicTab: false,
            isAutoSave: false,
            // show the embedded survey tab. It is hidden by default
            showEmbededSurveyTab: false,
            // hide the test survey tab. It is shown by default
            showTestSurveyTab: true,
            // hide the JSON text editor tab. It is shown by default
            showJSONEditorTab: false,
            // show the "Options" button menu. It is hidden by default
            showOptions: false,
            // Question Types
            // questionTypes: ["text", "comment", "boolean","checkbox", "radiogroup", "dropdown", "rating", "ranking","imagepicker","matrix","matrixdropdown","panel"],
            questionTypes: <?= $availableQuestionTypes; ?>,

            // We will not allow multi-language translations yet. Just leaving the code in for reference on how to
            // Activate this feature
            // Whether to show translations tabs for multilanguage surveys
            showTranslationTab: false,
        };

        Survey.slk('<?= Config::Get('SURVEY_JS_LICENSE_KEY') ?>');

        // Global create the SurveyJS Creator and render it in div with id equals to "creatorElement"
        // let __creator = new SurveyCreator.SurveyCreator("creatorElement", __options); // depricated
        // let __creator = new SurveyCreator.SurveyCreator( __options);
        const __creator = new SurveyCreator.SurveyCreator(__options);

        // In order to avoid question conflicts in surveys which have been published, we will use custom numbering
        // to always get a unique number for every newly added question.
        let teleskopeQuestionCounter = <?=$teleskopeQuestionCounter?>;
        __creator.onQuestionAdded.add((sender, options) => {
            const question = options.question;
            question.name = `question${teleskopeQuestionCounter}`;
            teleskopeQuestionCounter++;
        });

        __creator.render("creatorElement");
        let __id = 0;
        let __groupid = 0;
        let __version = 0;
        // Hide add logo feature
        // Survey.Serializer.removeProperty("survey", "logo");
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
            // "choicesVisibleIf",
            // "choicesEnableIf",
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
        __creator
            .toolbox
            .getItemByName("imagepicker")
            .json
            .choices = [];
    
        // SurveyCreator.QuestionConverter.convertInfo = {};

        function initSurveyCreator(json = '', id = '', groupid = '',version = '') {

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

            //Limited the number of showing locales in survey.locale property editor
            <?php
            if ($_COMPANY->getAppCustomization()['locales']['enabled']) {
                $locales = array_keys($_COMPANY->getAppCustomization()['locales']['languages_allowed']);
                $surveyLocales = array();
                foreach ($locales as $locale) {
                    $surveyLocales[] = $_COMPANY->getSurveyLanguage($locale);
                }
                $surveyLocalesStr = json_encode(array_values($surveyLocales));
             
                echo "Survey.surveyLocalization.supportedLocales = " . $surveyLocalesStr . ";\n";
            }
            ?>

            //You may use any of these: "default", "orange", "darkblue", "darkrose", "stone", "winter", "winterstone"
            // SurveyCreator.StylesManager.applyTheme("darkblue");
            //Show toolbox in the right container. It is shown on the left by default
            // __creator.showToolbox = "left"; // depricated
            // __creator.showToolbox = true;
            //Show property grid in the right container, combined with toolbox
            // __creator.showPropertyGrid = "right"; // devpricated
            //Make toolbox active by default
            // __creator.rightContainerActiveItem("toolbox"); // depricated
            var questionjson = {};
            if (json){
                questionjson = JSON.parse(json)
            }
            __creator.JSON = questionjson;
            __id = id;
            __groupid = groupid;
            __version = version;
            __creator.saveSurveyFunc = updateSurvey;
        }
    
        function updateSurvey() {
            var surveyJSON = (JSON.parse(__creator.text));
            surveyJSON['teleskopeQuestionCounter'] = teleskopeQuestionCounter;
            surveyJSON = JSON.stringify(surveyJSON);
            $.ajax({
                url: 'ajax_survey.php?updateSurvey=1',
                type: "post",
                data: {'surveyJSON': surveyJSON, 'surveyid': __id,'version':__version},
                success: function (data) {
                    try {
                        let jsonData = JSON.parse(data);
                        resetContentFilterState(2);
                        swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
                            if (jsonData.status == 1){
                                window.location.href = jsonData.val;
                            }
                        });
                    } catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
                }
            });
        }
    
        function saveAndExitSurvey() {
            updateSurvey();
            // $('.svd_save_btn').trigger('click');
            //$('#svd-save>.sv-action__content>button').trigger('click');
        }
    </script>
    
    <script>
      // Init Survey
      var json = <?php echo json_encode($json) ?>;
      initSurveyCreator(json,'<?= $surveyid; ?>','<?= $groupid; ?>','<?= $version; ?>');

    </script>