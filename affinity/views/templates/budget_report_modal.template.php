<!-- Budget Report Modal  -->

<style>
.budget-year-box {
    font-size: 1rem !important;
    color: #212529;
}
</style>
<div id="theBudgetReportModal" class="modal fade">
	<div  aria-label="<?= sprintf(gettext(" %s Report Download Options"),$reportTitle);?>" class="modal-dialog modal-md" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h2 class="modal-title text-center" id="rsvp_report_title"><?= sprintf(gettext(" %s Report Download Options"),$reportTitle);?></h2>
                <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
            <div class="modal-body">
                <div class="col-md-12">
                    <form action="ajax_reports?downloadBudgetReport=<?= $_COMPANY->encodeId($groupid); ?>" method="post" role="form" id="downloadBudgetReport" name="downloadBudgetReport">
                    <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
                    <input type="hidden" name="reportType" value="<?= $reportType ?>">
                    <div class="col-sm-12">
                    <label for="chapterId"><?= gettext('Scope'); ?></label>
                    <?php include_once __DIR__.'/../common/init_reports_chapter_channel_selection_box.php'; ?>
		            </div>
                    <div class="col-sm-12 mt-3">
                        <label for="budget_year"><?= gettext('Select Budget Year'); ?></label>
                            <select id="budget_year" class="form-control budget-year-box" name="budget_year" style="font-size:small;border-radius: 5px; margin: 0 auto;" required >
                                <?php
                                foreach($budgetYears as $budgetYear){ ?>
                                    <option <?= ($defaultBudgetYear && $defaultBudgetYear == $budgetYear['budget_year_id']) ? 'selected' : '' ?> value="<?= $_COMPANY->encodeId($budgetYear['budget_year_id']); ?>"><?= htmlspecialchars($budgetYear['budget_year_title']) ?></option>
                                <?php } ?>

                            </select>
                    </div>
                    <div class="col-sm-12 mt-3" style="text-align:center;">
                    <button type="submit" name="submit" id="submit_action_button" class="btn btn-primary"><?= gettext("Download Report");?></button>
                    
                    </div>
                    </form>
                </div>
            </div>
        </div>
	</div>
</div>

<script>
    $('#theBudgetReportModal').on('shown.bs.modal', function () {
        $('#btn_close').trigger('focus')
    });

$(function() {
    $('#downloadBudgetReport').submit(function(){         
        $('#btn_close').focus();      
    });
});
</script>