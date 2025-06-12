<div id="theReportModal" class="modal fade">
	<div aria-label="<?= $modalTitle;?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h2 class="modal-title text-center" id="rsvp_report_title"><?= $modalTitle;?></h2>
                <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
            <div class="modal-body">
<form class="form-horizontal" action="ajax_talentpeak?downloadTeamsReport=<?= $_COMPANY->encodeId($groupid); ?>" method="post" role="form" style="display: block;width:100% !important">
    <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
    <div class="row mt-3" tyle="padding: 0 50px; border:1px solid rgb(223, 223, 223); padding-top:10px;">        
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="teamstatus"><?= sprintf(gettext("Select %s Status"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></label>
                    <select class="form-control" name="teamstatus[]" id="teamstatus" multiple size="5">
                        <option value="<?= $_COMPANY->encodeId(Team::STATUS_ACTIVE); ?>" selected><?= sprintf(gettext("Active %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></option>
                        <option value="<?= $_COMPANY->encodeId(Team::STATUS_DRAFT); ?>" selected><?= sprintf(gettext("Draft %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></option>
                        <option value="<?= $_COMPANY->encodeId(Team::STATUS_INACTIVE); ?>" selected><?= sprintf(gettext("In-Active %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></option>
                        <option value="<?= $_COMPANY->encodeId(Team::STATUS_COMPLETE); ?>" selected><?= sprintf(gettext("Completed %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></option>
                        <option value="<?= $_COMPANY->encodeId(Team::STATUS_INCOMPLETE); ?>" selected><?= sprintf(gettext("Incomplete %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></option>
                        <option value="<?= $_COMPANY->encodeId(Team::STATUS_PAUSED); ?>" selected><?= sprintf(gettext("Paused %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></option>
                    </select>

                </div>
            </div>

            <div class="col-sm-1"></div>
            <div class="col-sm-5" style="max-height:500px; overflow-y:scroll;">
                    <fieldset>
                        <legend style="font-size: 1.2rem;"><?= gettext("Select Fields"); ?></legend>
                                    <div class="mb-2 text-sm">
                                        <a role="button" href="javascript:void(0)" class="link_show" onclick="checkUncheckAllCheckBoxes('userOptionsMultiCheck',true)"> <?= gettext("Select All");?></a> | <a role="button" href="javascript:void(0)" class="link_show" onclick="checkUncheckAllCheckBoxes('userOptionsMultiCheck',false)"> <?= gettext("Deselect All");?></a>
                                    </div>
                                <?php foreach($fields as $key => $value){ ?>
                                    <input aria-label="<?= $value; ?>" class="userOptionsMultiCheck" type="checkbox" name="<?= $key; ?>" value="<?= $key; ?>" checked>&emsp;<?= $value; ?><br>
                                <?php } ?>
                    </fieldset>
                    </div>

        </div>

    <div class="form-group mt-2" style="text-align:center;">
            <button type="submit" name="submit" class="btn btn-primary"><?= gettext("Download");?></button>
    </div>
</form>

</div>
        </div>
	</div>
</div>
<script>
$('#theReportModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus')
});
</script>