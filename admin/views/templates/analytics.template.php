 <!-- Survey.js -->
<!-- <link rel="stylesheet" href="../vendor/js/surveyjs-1.9.50/survey.min.css"> -->
<link rel="stylesheet" href="../vendor/js/surveyjs-1.11.2/defaultV2.css">
<link href="../vendor/js/surveyjs-1.11.2/modern.min.css" rel="stylesheet">
<script src="../vendor/js/surveyjs-1.11.2/survey.jquery.min.js"></script>
<!-- Survey Analytics Stuff-->
<script src="../vendor/js/surveyjs-1.11.2/vendor/typedarray.js"></script>
<!--script src="https://polyfill.io/v3/polyfill.min.js"></script-->
<script src="../vendor/js/surveyjs-1.11.2/vendor/plotly-latest.min.js"></script>
<script src="../vendor/js/surveyjs-1.11.2/vendor/wordcloud2.js"></script>
<link href="../vendor/js/surveyjs-1.11.2/survey.analytics.min.css" rel="stylesheet"/>
<script src="../vendor/js/surveyjs-1.11.2/survey.analytics.min.js"></script>

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
    .sa-panel__header {
        margin: 1em;
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

    /* Date slider style */
    div.range-slider {
        position: relative;
        width: 200px;
        height: 35px;
        text-align: center;
        margin-left:50px;
    }

    div.range-slider input {
        pointer-events: none;
        position: absolute;
        overflow: hidden;
        left: 0;
        top: 30px;
        width: 200px;
        outline: none;
        height: 18px;
        margin: 0;
        padding: 0;
    }

    div.range-slider input::-webkit-slider-thumb {
        pointer-events: all;
        position: relative;
        z-index: 1;
        outline: 0;
    }

    div.range-slider input::-moz-range-thumb {
        pointer-events: all;
        position: relative;
        z-index: 10;
        -moz-appearance: none;
        width: 9px;
    }
    input[type="range"]{
        color:red !important;
    }
    
</style>

<div class="container  col-md-offset-2 margin-top">
    <div class="row">
        <div class="col-md-12">
            <div class="widget-simple-chart card-box report-admin">
                <div class="col-md-12 divider">
                    <div class="col-md-10 pl-0 ml-0">
                        <h6><?= $analyticsTitle;?></h6>
                        <h5 class="mt-5"><?= sprintf(gettext("Number of Responses = %s"),$totalResponses);?></h5>
                    </div>
                    <div class="col-md-2">
                        <a href="reports" class="btn btn-primary pull-right">Back</a>
                    </div>
                </div>
                <div id="loadingIndicator">
                    <span>
                        <div id="loading">
                            <strong><?= gettext("loading");?>...</strong>
                            <span></span></div>
                    </span>
                </div>
               
                <div id="vizPanel" class="col-md-12" ></div>
               
                <div class="col-md-12 box-wrap"> 
                    <div id="surveyElement" style="display:inline-block;width:100%;"></div>
                    <div id="surveyResult" style="display:inline-block;width:100%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    var timer = undefined;
    var isUpdating = false;
    var currMin = undefined;
    var currMax = undefined;

    Survey.slk('<?= Config::Get('SURVEY_JS_LICENSE_KEY') ?>');

    Survey
        .StylesManager
        .applyTheme("default");

    
    var json = <?= $questionJson; ?>;
    var survey = new Survey.Model(json);

    // survey results data object
    var data = <?= $answerJson; ?>;
    var surveyResultNode = document.getElementById("surveyResult");
    surveyResultNode.innerHTML = "";
    SurveyAnalytics.MatrixPlotly.types = ['stackedbar', 'bar', 'pie', 'doughnut'];
    SurveyAnalytics.SelectBasePlotly.types = ["pie","bar", "doughnut","scatter"];
    var visPanel = new SurveyAnalytics.VisualizationPanel(survey.getAllQuestions(), data, {
        
        labelTruncateLength: 27,
        //allowTopNAnswers: true,
        //    hideEmptyAnswers: true,
        //    showPercentages: true
    });
   
    Survey.slk('<?= Config::Get('SURVEY_JS_LICENSE_KEY') ?>');

    visPanel.showHeader = true;
   
    survey.getAllQuestions().forEach(function(question) {
        var visualizer = visPanel.getVisualizer(question.name);
        visualizer.showHeader = true;
        // "pie" for "checkbox" and "bar" for "radiogroup"
        if(question.getType() === "checkbox") {
            visualizer.setChartType("scatter");
            visualizer.showHeader = false;
        }
        if(question.getType() === "radiogroup") {
            visualizer.setChartType("doughnut");
        }
        // if(question.name === "developer_count") {
        //     visualizer.setChartType("scatter");
        // }
    });
    renderVizPanels(visPanel);
    
    function renderVizPanels(panel) {
        const node = document.getElementById(`vizPanel`);
        panel.render(node);
        document
        .getElementById(`loadingIndicator`)
        .style
        .display = "none";
    }

});
</script>
