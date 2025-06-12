<script>
    var teleskopeCsrfToken="<?=Session::GetInstance()->csrf;?>";
</script>
<style>
    .sa-toolbar__button{
        border: 1px solid #0d5380;
        border-radius:5px;
    }
    .sa-toolbar__button:hover{
        border: 1px solid #7dc1eb;
        border-radius:5px;
    }
</style>

<div id="viewAnalytic" class="modal fade" role="dialog">
    <div class="modal-dialog modal-xl">
        <!-- Modal content-->
        <div class="modal-content">
           <div class="modal-header">
                <h4 class="modal-title text-center"><?= gettext("View Analytics") ?></h4>
                <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">×</button>
			</div>
            <div class="modal-body">
            <main>
                <div class="inner-background inner-background-tall footer-divider">
                    <div class="row row-no-gutte rs">
                        <div class="col-sm-12 surveyTitle pt-4 pl-5 pr-5">
                            <div class="text-center">
                                <h6><?= $analyticsTitle;?></h6>
                                <p><?= sprintf(gettext("Total Number of Responses = %s"),$totalResponses);?></p>
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
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('shown.bs.modal', '#viewAnalytic', function(){
    $('#viewAnalytic').find('#vizPanel').empty();
        var timer = undefined;
    var isUpdating = false;
    var currMin = undefined;
    var currMax = undefined;
    Survey.slk('<?= Config::Get('SURVEY_JS_LICENSE_KEY') ?>');
    Survey.StylesManager.applyTheme("default");
    var json = <?= $questionJson; ?>;
    var survey = new Survey.Model(json);
    // survey results data object
    var data = <?= $answerJson; ?>;
    var surveyResultNode = document.getElementById("surveyResult");
    surveyResultNode.innerHTML = "";
    SurveyAnalytics.MatrixPlotly.types = ['stackedbar', 'bar', 'pie', 'doughnut'];
    SurveyAnalytics.SelectBasePlotly.types = ["pie","bar", "doughnut","scatter"];
    var visPanel = new SurveyAnalytics.VisualizationPanel(survey.getAllQuestions(), data, {
        haveCommercialLicense: true,
        labelTruncateLength: 27,
    });
    visPanel.showHeader = true;
    survey.getAllQuestions().forEach(function(question) {
        var visualizer = visPanel.getVisualizer(question.name);
        visualizer.showHeader = true;
        if(question.getType() === "checkbox") {
            visualizer.setChartType("scatter");
            visualizer.showHeader = false;
        }
        if(question.getType() === "radiogroup") {
            visualizer.setChartType("doughnut");
        }
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

    $(this).find('.sa-toolbar__button').each(function(){
        $(this).attr('aria-label', 'hide');
        $(this).attr('tabindex', '0');
        $(this).attr('role', 'button');

    });
});
</script>