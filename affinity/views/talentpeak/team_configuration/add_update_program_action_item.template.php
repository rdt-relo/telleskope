<div id="addUpdateActionItemModal" class="modal fade" role="dialog">
    <div aria-label="<?= $pageTitle; ?>" class="modal-dialog modal-lg modal-dialog-w1000">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><?= $pageTitle; ?></h2>
                <button type="button" aria-label="close" id="btn_close" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
				<div class="col-md-12">
                    <form id="addUpdateActionItemForm" class="form-horizontal" method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <div class="form-group">
                            <p class="col-lg-12 control-label"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                        </div>
                        <div class="form-group">
                            <label for="actionItemTitle" class="col-lg-12 control-label"><?= gettext("Action Item Title"); ?><span style="color:red;"> *</span></label>
                            <div class="col-lg-12">
                                <input id="actionItemTitle" class="form-control" placeholder="<?= gettext("Action item title here"); ?>" name="title"  type="text" value="<?= $edit ? $edit['title'] : ''; ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-12 control-label"><?= gettext("Assigned To")?><span style="color:red;"> *</span></label>
                            <div class="col-lg-12">
                                <select aria-label="<?= gettext('Assigned To');?>" id="assignedto" name="assignedto" class="form-control">
                                    <option value=""><?= gettext("Select Team Role"); ?></option>
                                <?php foreach($teamRoles as $role){ 
                                    $sel = '';
                                    if ($edit && $edit['assignedto'] == $role['roleid']){
                                        $sel = 'selected';
                                    }    
                                ?>
                                    <option value="<?= $_COMPANY->encodeId($role['roleid']); ?>" <?= $sel; ?>><?= $role['type'] ?></option>
                                <?php } ?>
                                </select>
                            
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-12" for="redactor_content"><?= gettext("Description"); ?></label>
                            <div id="post-inner" class="col-lg-12">                               
                                <textarea class="form-control" name="description" rows="10" id="redactor_content" maxlength="2000" placeholder="<?= gettext("Description here"); ?>"><?= $edit ? htmlspecialchars($edit['description']) : ''; ?></textarea>
                                
                                <p class="text-sm">
                                    <?= sprintf(gettext('<b>Personalize your %2$s:</b> You can now use the following variables in the subject and body of your %2$s: [[MENTOR_FIRST_NAME]], [[MENTOR_LAST_NAME]], [[MENTEE_FIRST_NAME]],[[MENTEE_LAST_NAME]]. These variables will be automatically replaced with the corresponding names when the %1$s is created and %2$s are generated. Name substitution will only occur if the system can uniquely identify the individual. For example, if a %1$s has multiple mentors, the variables [[MENTOR_FIRST_NAME]] and [[MENTOR_LAST_NAME]] will not be substituted. This ensures that the right information is always displayed.'), Team::GetTeamCustomMetaName($group->getTeamProgramType()), gettext('Action Items')); ?>
                                </p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="tat" class="col-lg-12 control-label"><?= sprintf(gettext("Turnaround time in weekdays from start date of %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType())); ?></label>
                            <div class="col-lg-12">
                                <input id="tat" type="number" min='0' class="form-control" placeholder="Turnaround time in weekdays e.g. 5" name="tat"  type="text" value="<?= $edit ? $edit['tat'] : '0'; ?>">
                            </div>
                        </div>

                    </form>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="modal-footer text-center" style='text-align:center !important;display: block;'>
                <button type="submit" class="btn btn-affinity prevent-multiple-submit" onclick="addUpdateProgramActionItemTemplate('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($id); ?>')" ><?= gettext("Submit");?></button>
                <button type="submit" class="btn btn-affinity" data-dismiss="modal" ><?= gettext("Close");?></button>
            </div>
        </div>
    </div>
</div>

<script>

    $(document).ready(function(){
        $('#redactor_content').initRedactor('redactor_content','teamtasks',['counter','table']);
        $(".redactor-voice-label").text("<?= gettext('Add description');?>");

        $(function () {
            $('[data-toggle="popover"]').popover({html:true, placement: "top"});  
        })

        redactorFocusOut('#assignedto'); // function used for focus out from redactor when press shift + tab.
    });

    $('#addUpdateActionItemModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
});
</script>
