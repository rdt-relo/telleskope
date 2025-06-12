<div tabindex="-1" id="theReportModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-md">  
		<div class="modal-content">
			<div class="modal-header">
                <h4 class="modal-title text-center" id="rsvp_report_title"><?= gettext("Download Budget report by year");?></h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
            <div class="modal-body">
                <div class="col-md-12 mt-3 text-center">
                    <form class="form-horizontal" action="ajax.php?download_budgetyear_report=1" method="post" role="form" onSubmit="return budgetYearValidateForm()">
                        <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
                            <div class="row pt-3">
                                <div class="col-12 text-left">
                                    <div class="form-group">
                                        <label for="s_group">Select Fiscal Year</label>
                                        <select id="year_filter" class="form-control" name="year_filter">
                                        <option value=""><?= gettext("Select Fiscal Year"); ?></option>
                                                <?php 
                                                foreach($budgetYears as $budgetYear){?>
                                                    <option value="<?= $_COMPANY->encodeId($budgetYear['budget_year_id']); ?>" <?=($currentBudgetYearId==$budgetYear['budget_year_id'])?'selected':'' ?> ><?= htmlspecialchars($budgetYear['budget_year_title']); ?></option>
                                                <?php }  ?>
                                        </select> 
                                    </div>
                                    <div class="form-group">
										<label for="s_group">Select Action</label>
										<select class="form-control" name="reportAction" onchange=filterMetaFields(this.value,'{}') >
											<option value="download" selected>Download Report</option>
                                        <?php if($_COMPANY->getAppCustomization()['reports']['analytics'] && false){  ?>
                                            <option value="analytic">Analytics</option>
                                        <?php } ?>
										</select>
									</div>
                                </div>
                            </div>

                        <div class="form-group mt-3">
                        <button type="submit" name="submit" class="btn btn-primary" id="submit_action_button">Download</button>
                        </div>
                        
                    </form>
                </div> 
            </div>
        </div>
	</div>
</div>
<script>
    $('#theReportModal').on('shown.bs.modal', function () {     					 
    $('.close').focus();
})
retainFocus('#theReportModal');

function budgetYearValidateForm(){
    var year_filter = document.getElementById('year_filter').value;
    if(year_filter == ""){
        showSweetAlert("<?= gettext('Error')?>","<?= addslashes(gettext('There is no Fiscal Year. Please add Fiscal Year first.'))?>",'');       
        return false;
    }
}
</script>