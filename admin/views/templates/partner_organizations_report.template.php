<div tabindex="-1" id="partnerOrganizationsReport" class="modal fade" role="dialog">
	<div class="modal-dialog modal-lg">  
		<div class="modal-content">
			<div class="modal-header">
                <h4 class="modal-title text-center" id="partner_organizations_report_title"><?= gettext("Download Partner Organizations Report");?></h4> 
                <button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
            <div class="modal-body">
                <div class="col-md-12 mt-3 text-left">
                <form class="form-horizontal" action="ajax.php?downloadPartnerOrganizationsReport=1" method="post" role="form" style="display: block;">
                        <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
                        <div id="partnerOrganizationsOptions" style="display:block; padding: 0 50px;" id="filterOptions">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="s_group">Select Action</label>
                                        <select class="form-control" name="reportAction" >
                                            <option value="download" selected>Download Report</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-sm-1"></div>
                                <div class="col-sm-5">
                                    <div class="form-group">
										<label for="s_options">Select Fields</label>
                                        <br>
                                        <div class="mb-2 text-sm">
                                            <button type="button" class="btn btn-no-style link_show" onclick="checkUncheckAllCheckBoxes('partnerOrganizationsOptionsMultiCheck',true)"> <?= gettext("Select All");?></button> | <button type="button" class="btn btn-no-style link_show" onclick="checkUncheckAllCheckBoxes('partnerOrganizationsOptionsMultiCheck',false)"> <?= gettext("Deselect All");?></button>
                                        </div>
									<?php
                                        foreach($fields as $key => $value){ ?>
                                            <span  id="id_<?= $key; ?>"><input class="partnerOrganizationsOptionsMultiCheck metaFields" type="checkbox" name="<?= $key; ?>" value="<?= $key; ?>" checked>&emsp;<?= $value; ?><br></span>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-3 text-center">
                            <button type="submit" name="submit" class="btn btn-primary" id="submit_action_button">Download</button>
                        </div>
                    </form>
                </div> 
            </div>
        </div>
	</div>
</div>

<script>
    // This anonymous function needs to init in all report modal for validation
    $(function() {
        $(".metaFields").click(function(){
            $('#submit_action_button').prop('disabled',$('input.metaFields:checked').length == 0);
        });
    });

    $('#partnerOrganizationsReport').on('shown.bs.modal', function () {     					 
		$('.close').focus();
	})
    retainFocus("#partnerOrganizationsReport");
</script>