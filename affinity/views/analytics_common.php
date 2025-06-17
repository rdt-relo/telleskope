<!-- Survey Analytics Stuff-->
<script src="<?=TELESKOPE_.._STATIC?>/vendor/js/surveyjs-1.11.2/vendor/typedarray.js"></script>
<!--script src="https://polyfill.io/v3/polyfill.min.js"></script-->
<script src="<?=TELESKOPE_.._STATIC?>/vendor/js/surveyjs-1.11.2/vendor/plotly-latest.min.js"></script>
<script src="<?=TELESKOPE_.._STATIC?>/vendor/js/surveyjs-1.11.2/vendor/wordcloud2.js"></script>
<link href="<?=TELESKOPE_.._STATIC?>/vendor/js/surveyjs-1.11.2/survey.analytics.min.css" rel="stylesheet"/>
<script src="<?=TELESKOPE_.._STATIC?>/vendor/js/surveyjs-1.11.2/survey.analytics.min.js"></script>

<script>
    var teleskopeCsrfToken="<?=Session::GetInstance()->csrf;?>";
</script>
<style>
    body {
        background-color: #ffffff !important;

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
    .sa-toolbar__button{
        border: 1px solid #0d5380;
        border-radius:5px;
    }
    .sa-toolbar__button:hover{
        border: 1px solid #7dc1eb;
        border-radius:5px;
    }
    /*.sa-question-layouted{*/
    /*    width:100% !important;*/
    /*}*/
    .month-filter{
        /* position: absolute;
        right: 30px;
        margin-top: -1px; */
    }
    .goback-button{
        margin-top: 27px;
    }

.sa-question__select:focus { 
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 .1rem rgba(0,123,255,.25);
}
.sa-toolbar__button:focus { 
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 .1rem rgba(0,123,255,.25);
}
</style>
<main>
<div class="inner-background inner-background-tall footer-divider">
        <div class="row row-no-gutters">
            <div class="col-sm-12 surveyTitle pt-4 pl-5 pr-5">
                <div class="col-md-12">
                    <h3 class="text-center"><?= $pageTitle; ?></h3>
                </div>
                
                    <div class="col-md-12 month-filter" id="action_buttons" style="display:none">
                        <div class="col-md-9">&nbsp;</div>
                        <?php if(empty($sectionid) && empty($eventid)){ ?>
                        <div class="col-md-2 ">
                            <label for=""><?= gettext("Filter by month");?></label>
                            <select name="date" onchange="filterEventAnalytics(this.value)" class="form-control">
                            <?php for ($i = 0; $i < 12; $i++) { 
                                $d = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
                                $sel = "";
                                if($_SESSION['analytic_month']  == $d){
                                    $sel = "selected";
                                }
                            ?>
                                <option value="<?= $d; ?>" <?= $sel; ?> ><?= $d; ?></option>
                            <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-1 "><a class="btn btn-affinity goback-button" href="manage?id=<?= $_COMPANY->encodeId($groupid)?>" ><?= gettext("Back");?> </a></div>
                        <?php } else { ?>
                        <div class="col-md-1 ">&nbsp;</div>
                        <div class="col-md-2 "><a href="javascript:void(0)" class="btn btn-affinity goback-button" onclick="goBack();"><?= gettext("Back");?></a></div>
                        <?php } ?>
                    </div>
                
            </div>
            <div class="col-md-12 pl-5 pr-5">
                <div id="loadingIndicator">
                    <span>
                        <div id="loading">
                            <strong><?= gettext("loading");?>...</strong>
                            <span></span></div>
                    </span>
                </div>
                <div class="row">
                    <div id="surveyElement" style="display:inline-block;width:100%;"></div>
                    <div id="surveyResult" style="display:inline-block;width:100%;"></div>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
    $(document).ready(function(){
        Survey
           .StylesManager
           .applyTheme("default");

       var json = <?= $questionJson;?>;
       
       // survey results data object
       var data = <?= $answerJson; ?>;
       Survey.slk('<?= Config::Get('SURVEY_JS_LICENSE_KEY') ?>');
       var survey = new Survey.Model(json);

       var surveyResultNode = document.getElementById("surveyResult");
       surveyResultNode.innerHTML = "";

       SurveyAnalytics.SelectBasePlotly.types = ["pie", "doughnut","bar","scatter"];
       var visPanel = new SurveyAnalytics.VisualizationPanel(survey.getAllQuestions(), data, {          
           labelTruncateLength: 25,
           allowTopNAnswers: true,
           hideEmptyAnswers: true,
           answersOrder: 'desc',
           showPercentages: true
       });



       visPanel.showHeader = true;
       $("#loadingIndicator").hide();
       $("#action_buttons").show();
       visPanel.render(surveyResultNode);

        survey.getAllQuestions().forEach(function(question) {
            var visualizer = visPanel.getVisualizer(question.name);
            visualizer.showHeader = true;
            // "pie" for "checkbox" and "bar" for "radiogroup"
            if(question.getType() === "checkbox") {
                visualizer.setChartType("bar");
                visualizer.showHeader = false;
            }
            if(question.getType() === "radiogroup") {
                visualizer.setChartType("pie");
            }
            // if(question.name === "developer_count") {
            //     visualizer.setChartType("scatter");
            // }
        });
    });

    function filterEventAnalytics(d){
        window.location = 'event_analytics?groupid=<?= $_COMPANY->encodeId($groupid)?>&month='+d
    }
</script>
