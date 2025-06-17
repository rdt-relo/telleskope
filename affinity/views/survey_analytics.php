<!-- Survey Analytics Stuff-->
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
    
    .sa-toolbar {   
        display: inline;    
    }

</style>

<main>
<div class="inner-background inner-background-tall footer-divider">
        <div class="row row-no-gutte rs">
            <div class="col-sm-12 surveyTitle pt-4 pl-5 pr-5">
                <div class="text-center">
                    <h3><?= sprintf(gettext("%s Survey Analytics"),$survey->val('surveyname'));?></h3>
                    <p><?= sprintf(gettext("Total Number of Responses = %s"),$response_count);?></p>
                    <p><?= sprintf(gettext("Total Number of Skipped Responses = %s"),$skipped_response_count);?></p>
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
                <div id="vizPanel"></div>
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
    var timer = undefined;
    var isUpdating = false;
    var currMin = undefined;
    var currMax = undefined;
   
    Survey
        .StylesManager
        .applyTheme("default");

    Survey.slk('<?= Config::Get('SURVEY_JS_LICENSE_KEY') ?>');

    var json = <?= $questionJson;?>;
    var survey = new Survey.Model(json);

    // survey results data object
    var data = <?= $answerJson; ?>;

    data.forEach(function(item) {
        item.HappendAt = Date.parse(item.HappendAt);
    });
    data.sort(function(d1, d2) {
        return d1.HappendAt > d2.HappendAt;
    });
    currMin = data[0].HappendAt;
    var currMaxD = data[data.length-1].HappendAt;
    const maxDate = new Date(currMaxD);
    maxDate.setDate(maxDate.getDate() + 1);
    currMax = Date.parse(maxDate);
    var surveyResultNode = document.getElementById("surveyResult");
    surveyResultNode.innerHTML = "";
    SurveyAnalytics.MatrixPlotly.types = ['stackedbar', 'bar', 'pie', 'doughnut'];
    SurveyAnalytics.SelectBasePlotly.types = ["bar","pie", "doughnut","scatter"];
    Survey.slk('<?= Config::Get('SURVEY_JS_LICENSE_KEY') ?>');
    var visPanel = new SurveyAnalytics.VisualizationPanel(survey.getAllQuestions(), data, {
        labelTruncateLength: 27,
        allowTopNAnswers: true,
        //    hideEmptyAnswers: true,
        //    showPercentages: true
    });
   
    setupDateRange(visPanel, data);
    visPanel.showHeader = true;
    //$("#loadingIndicator").hide();
    //visPanel.render(surveyResultNode);

    renderVizPanels(visPanel);
    survey.getAllQuestions().forEach(function(question) {
        var visualizer = visPanel.getVisualizer(question.name);
        visualizer.showHeader = true;
        // "pie" for "checkbox" and "bar" for "radiogroup"
        if(question.getType() === "checkbox") {
            visualizer.setChartType("scatter");
            visualizer.showHeader = false;
        }
        if(question.getType() === "radiogroup") {
            visualizer.setChartType("pie");
        }
        // if(question.name === "developer_count") {
        //     visualizer.setChartType("scatter");
        // }
    });
  
    function renderVizPanels(panel) {
        const node = document.getElementById(`vizPanel`);
        panel.render(node);
        document
        .getElementById(`loadingIndicator`)
        .style
        .display = "none";
    }


function createValsUpdater(parent, vizPanel, data) {
  return function() {
    var sliders = parent.getElementsByTagName("input");
    var slide1 = parseFloat(sliders[0].value);
    var slide2 = parseFloat(sliders[1].value);
    if(slide1 > slide2)
      { var tmp = slide2; slide2 = slide1; slide1 = tmp; }
    currMin = slide1;
    currMax = slide2;
    // var currData = data.filter(function(item) {
    //   return item.HappendAt >= currMin && item.HappendAt <= currMax;
    // });
    var displayElement = parent.getElementsByClassName("rangeValues")[0];
    displayElement.innerHTML = new Date(slide1).toLocaleDateString() + " - " + new Date(slide2).toLocaleDateString();
    displayElement = parent.getElementsByClassName("rangeValuesCount")[0];
    displayElement.innerHTML = vizPanel.dataProvider.filteredData.length + " item(s)";
    if(isUpdating) {
      return;
    }
    if(timer !== undefined) {
      clearTimeout(timer);
      timer = undefined;
    }
    timer = setTimeout(function() {
      isUpdating = true;
      vizPanel.setFilter("HappendAt", { start: slide1, end: slide2 });
      timer = undefined;
      isUpdating = false;
    }, 100);
  }
}
function setupDateRange(vizPanel, data) {
  vizPanel.registerToolbarItem("dateRange", (toolbar) => {
    var itemRoot = undefined;
    if (data.length > 0 && data[0].HappendAt) {
        var min = data[0].HappendAt;
        var currMaxD = data[data.length-1].HappendAt
        const maxDate = new Date(currMaxD);
        maxDate.setDate(maxDate.getDate() + 1);
        var max = Date.parse(maxDate);
        itemRoot = document.createElement("div");
        itemRoot.style.display = "inline-block";
        itemRoot.classList.add("pull-right");
        itemRoot.classList.add("mb-4");
        itemRoot.innerHTML = `<div class="range-slider">
        <div class="rangeValues"></div>
        <input value="` + currMin + `" min="` + min + `" max="` + max + `" type="range">
        <input value="` + currMax + `" min="` + min + `" max="` + max + `" type="range">
        <div class="rangeValuesCount"></div>
        </div>`;
      toolbar.appendChild(itemRoot);
      var slider1 = itemRoot.children[0].children[1];
      var slider2 = itemRoot.children[0].children[2];
      slider1.oninput = createValsUpdater(itemRoot.children[0], vizPanel, data);
      slider1.oninput();      
      slider2.oninput = createValsUpdater(itemRoot.children[0], vizPanel, data);
      slider2.oninput();      
    }
    return itemRoot;
  });
}
});
</script>