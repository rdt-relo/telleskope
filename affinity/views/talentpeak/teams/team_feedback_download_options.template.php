<div id="theReportModal" class="modal fade">
	<div aria-label="<?= sprintf(gettext("%s Feedback Download Options"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h2 class="modal-title text-center" id="rsvp_report_title"><?= sprintf(gettext("%s Feedback Download Options"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></h2>
                <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
            <div class="modal-body">
<form class="form-horizontal" id="reportsForm" action="ajax_talentpeak?downloadTeamsFeedbackReport=<?= $_COMPANY->encodeId($groupid); ?>" method="post" role="form" style="display: block;width:100% !important">
    <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
    <div class="row mt-3" tyle="padding: 0 50px; border:1px solid rgb(223, 223, 223); padding-top:10px;">        
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="teamstatus"><?= sprintf(gettext("Select %s Status"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></label>
                    <select class="form-control" name="teamstatus[]" id="teamstatus" multiple size="5">
                        <option value="<?= $_COMPANY->encodeId(Team::STATUS_ACTIVE); ?>" selected><?= sprintf(gettext("Active %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></option>
                        <option value="<?= $_COMPANY->encodeId(Team::STATUS_INACTIVE); ?>" selected><?= sprintf(gettext("In-Active %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></option>
                        <option value="<?= $_COMPANY->encodeId(Team::STATUS_COMPLETE); ?>" selected><?= sprintf(gettext("Completed %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></option>
                        <option value="<?= $_COMPANY->encodeId(Team::STATUS_INCOMPLETE); ?>" selected><?= sprintf(gettext("Incomplete %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></option>
                        <option value="<?= $_COMPANY->encodeId(Team::STATUS_PAUSED); ?>" selected><?= sprintf(gettext("Paused %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></option>
                    </select>

                    <?php if (0) { ?>
                    <label for="roleids"><?= sprintf(gettext("Select %s Role"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></label>
                    <select class="form-control" name="roleids[]" id="roleids" multiple size="5">
                    <?php foreach($allRoles as $role){ ?>
                        <option value="<?= $_COMPANY->encodeId($role['roleid']); ?>" selected><?= $role['type']; ?></option>
                    <?php } ?>
                    </select>
                    <?php } ?>
                </div>
                           <div class="form-group">
                                <label for="s_group"><?= gettext("Select Action");?></label>
                                <select id="s_group" class="form-control" name="reportAction" onchange="filterMetaFields(this.value,'<?= htmlspecialchars($excludeAnalyticMetaFields); ?>')" >
                                    <option value="download" selected><?= gettext("Download Report");?></option>
                                <?php if($_COMPANY->getAppCustomization()['reports']['analytics']){  ?>
                                    <option value="analytic"><?= gettext("Analytics");?></option>
                                <?php } ?>
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
        <button type="submit" id="submit_action_button" name="submit" class="btn btn-primary"><?= gettext("Download");?></button> 
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
<script>
    document.getElementById('reportsForm').addEventListener('submit', function(event){
       
       var formData = new FormData(this);
       var action = formData.get('reportAction');
       if(action !== 'download'){
           event.preventDefault();
           
           $.ajax({
               url: this.action,
               type: "POST",
               data: formData,
               processData: false,
               contentType: false,
               cache: false,
                   success : function(data) {
                       if(data){
                           // viewAnalytic
                           $('body').removeClass('modal-open');
                           $('.modal-backdrop').remove();
                           $("#loadAnyModal").html(data);
                           $('#viewAnalytic').modal({
                               backdrop: 'static',
                               keyboard: false
                           });
                       } else {
                        swal.fire({title: 'Error!', text: "<?= gettext('Not enough data. Please select higher range of dates');?>"});
                           console.log('error');
                       }
                                       
                   }
           });
       }
   
   });
</script>