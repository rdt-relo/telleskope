<div id="todoModal" class="modal fade" tabindex="-1">
    <div aria-label="<?= $pageTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="modal-title" class="modal-title"><?= $pageTitle; ?></h4>
                <button type="button" id="btn_close" class="close" aria-label="Close Modal Box" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
				<div class="col-md-12">
                    <form id="todoForm">
                        <input type="hidden" id='teamid' name='teamid' value="<?= $_COMPANY->encodeId($teamid); ?>">
                        <input type="hidden" id='taskid' name='taskid' value="<?= $_COMPANY->encodeId($feedbackid); ?>">
                        <input type="hidden" id='task_type' name='task_type' value="feedback">
                        <p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                        <div class="form-group" >
                            <label><?= gettext("Title");?>:<span style="color: #ff0000;"> *</span></label>
                            <input  class="form-control" id="tasktitle"  placeholder="<?= gettext("Feedback title here");?>.." name="tasktitle" value="<?= $todo ? htmlspecialchars($todo['tasktitle']) : ''; ?>" required />
                        </div>
                        <div class="form-group" >
                            <label><?= gettext("Feedback");?>:<span style="color: #ff0000;"> *</span></label>
                            <div id="post-inner" class="post-inner-edit">
                                <textarea  class="form-control" id="description"  placeholder="<?= gettext("Feedback here");?>.." name="description" required><?= $todo ? $todo['description'] : ''; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>For:<span style="color: #ff0000;"> *</span></label>
                            <select aria-label="<?= gettext('For');?>" name="assignedto" id="assignedto" class="form-control" required>
                                <option value="" <?= $todo ? 'disabled' : ''; ?>>-<?= gettext("Select an option");?>-</option>
                                <option value="<?= $_COMPANY->encodeId(Team::TEAM_FEEDBACK_FOR_PROGRAM_LEADS) ?>" <?= $todo ? ( Team::TEAM_FEEDBACK_FOR_PROGRAM_LEADS == $todo['assignedto'] ? 'selected' : ' disabled') : ''?>>
                                    <?= sprintf(gettext('%s Leaders'),$_COMPANY->getAppCustomization()['group']['name-short']); ?>
                                </option>
                                <?php foreach($uniqueAssignees as $assignee){ 
                                        if ($assignee['firstname'] == "Deleted"){
                                            continue;
                                        }
                                        $checked = '';
                                        if ($todo){
                                            if ($assignee['userid'] == $todo['assignedto']){
                                                $checked = 'selected';
                                            } else {
                                                $checked = 'disabled';
                                            }
                                        }    
                                ?>
                                <?php if($assignee['userid'] != $_USER->id()){ ?>
                                    <option value="<?= $_COMPANY->encodeId($assignee['userid']); ?>" <?= $checked; ?>>
                                        <?= $assignee['firstname'].' '.$assignee['lastname']; ?>
                                        <?= $assignee['roletitle'] ? ' ('. htmlspecialchars($assignee['roletitle']) .')' : '' ?>
                                    </option>
                                <?php } ?>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?= gettext("Feedback Visibility");?>:</label>
                            <div class="radio pl-2">
                                <label><input type="radio" name="visibility" value="<?=Team::TEAM_TASK_VISIBILITY['ASSIGNED_PERSON']?>" <?= $todo ? ($todo['visibility'] == Team::TEAM_TASK_VISIBILITY['ASSIGNED_PERSON'] ? 'checked' : '' ) : 'checked' ?> > <?= gettext("Feedback recipient");?></label>
                            </div>
                            <?php if($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
                            <div class="radio pl-2">
                                <label><input type="radio" name="visibility" value="<?=Team::TEAM_TASK_VISIBILITY['ALL_MEMBERS']?>" <?= $todo && $todo['visibility'] == Team::TEAM_TASK_VISIBILITY['ALL_MEMBERS'] ? 'checked' : '' ?>> <?= sprintf(gettext("All %s members"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></label>
                            </div>
                            <?php } ?>
                        </div>

                        <?php if (!empty($team_task_model)) { ?>
                            <?= $team_task_model->renderAttachmentsComponent('v22') ?>
                        <?php } else { ?>
                            <?= TeamTask::CreateEphemeralTopic()->renderAttachmentsComponent('v22') ?>
                        <?php } ?>

                    </form>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="modal-footer text-center" style='text-align:center !important;display: block;'>
                <button type="submit" class="btn btn-affinity prevent-multiple-submit" onclick="addTeamTodo('<?= $_COMPANY->encodeId($groupid); ?>')" ><?= gettext("Submit");?></button>
                <button type="submit" class="btn btn-affinity"  aria-hidden="true" data-dismiss="modal" ><?= gettext("Close");?></button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $( "#duedate" ).datepicker({
            prevText:"click for previous months",
            nextText:"click for next months",
            showOtherMonths:true,
            selectOtherMonths: false,
            dateFormat: 'yy-mm-dd',
            beforeShow:function(textbox, instance){
                $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
            } 
        });
        var fontColors = <?= $fontColors; ?>;
        $('#description').initRedactor('description', 'teamtasks',['fontcolor','counter'],fontColors);
        redactorFocusOut('#tasktitle'); // function used for focus out from redactor when press shift +tab.

    });

$('#todoModal').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus')
});

retainFocus('#todoModal');
</script>
