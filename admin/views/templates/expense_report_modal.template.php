<div tabindex="-1" id="theReportModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-md">  
		<div class="modal-content">
			<div class="modal-header">
                <h4 class="modal-title text-center" id="rsvp_report_title"><?= gettext("Expense/Spend Report Download Options");?>&nbsp;<i class="fa fa-info-circle info-black" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="<?= gettext('Below, choose the filters you wish to include in the downloaded report.'); ?>"></i></h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
            <div class="modal-body">
                <div class="col-md-12">
                    <form action="ajax.php?download_expense_report" method="post" role="form" id="download_expense_report" name="download_expense_report">
                    <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label for="s_group">Select <?= $_COMPANY->getAppCustomization()['group']["name-plural"]; ?></label>
                            <select class="form-control" name="s_group[]" id="s_group" multiple size="6">
                                <!-- <option value="<?= $_COMPANY->encodeId(0) ?>">All Groups</option> -->
                                <?php foreach ($group as $g) { ?>
                                    <option value="<?= $_COMPANY->encodeId($g['groupid']) ?>"
                                            selected><?= $g['groupname']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
		           
                        <div class="form-group">
                            <label for="budget_year"><?= gettext('Select Budget Year'); ?></label>
                            <select id="budget_year" class="form-control" name="budget_year" style="font-size:small;border-radius: 5px; margin: 0 auto;" required >
                                <?php
                                foreach($budgetYears as $budgetYear){ ?>
                                    <option value="<?= $_COMPANY->encodeId($budgetYear['budget_year_id']); ?>"><?= htmlspecialchars($budgetYear['budget_year_title']) ?></option>
                                <?php } ?>

                            </select>
                        </div>

                        <div class="form-group">
                            <label for="s_group">Select Action</label>
                            <select class="form-control" name="reportAction" onchange=filterMetaFields(this.value,'<?= $excludeAnalyticMetaFields; ?>') >
                                <option value="download" selected>Download Report</option>
                            <?php if($_COMPANY->getAppCustomization()['reports']['analytics'] && 0){  ?>
                                <option value="analytic">Analytics</option>
                            <?php } ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-sm-12 mt-3" style="text-align:center;">
                    <button type="submit" name="submit" class="btn btn-primary" id="submit_action_button">Download</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
	</div>
</div>
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });

    $('#theReportModal').on('shown.bs.modal', function () {     					 
    $('.close').focus();
})

retainFocus('#theReportModal');
</script>
 