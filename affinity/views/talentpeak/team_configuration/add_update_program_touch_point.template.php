<style>
    .redactor-box {
        border: 1px solid rgb(212, 212, 212);
        padding: 10px;
    }
    
    .timeline-range ol {
        position: relative;
        display: block;
        height: 4px;
        background: #ced5d9;
    }
    .timeline-range ol::before,
    .timeline-range ol::after {
        content: "";
        position: absolute;
        top: -5px;
        display: block;
        width: 0;
        height: 0;
        border-radius: 10px;
        border: 6px solid #ced5d9;
    }
    .timeline-range ol::before {
        left: -5px;
    }
    .timeline-range ol::after {
        right: -10px;
        border: 7px solid transparent;
        border-right: 0;
        border-left: 10px solid #ced5d9;
        border-radius: 3px;
    }

    /* ---- Timeline elements ---- */
    .li {
        position: relative;
        display: inline-block;
        float: left;
        width: 5%;
        font: bold 14px arial;
        height: 50px;
    }
    .li .diplome {
        position: absolute;
        top: -47px;
        left: 36%;
        color: #000000;
    }
    .li .point {
        content: "";
        top: -6px;
        left: 90%;
        display: block;
        width: 15px;
        height: 15px;
        border: 4px solid #31708F;
        border-radius: 10px;
        background: #fff;
        position: absolute;
    }

    .li .description {
        display: none;
        background-color: #f4f4f4;
        padding: 10px;
        margin-top: 20px;
        position: relative;
        font-weight: normal;
        z-index: 1;
    }
    .description::before {
        content: '';
        width: 0; 
        height: 0; 
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-bottom: 5px solid #f4f4f4;
        position: absolute;
        top: -5px;
        left: 43%;
    }

    /* ---- Hover effects ---- */
    .li:hover {
        cursor: pointer;
        color: #48A4D2;
    }
    .li:hover .description {
        display: block;
    }
</style>
<div id="addUpdateTouchPointModal" class="modal fade" role="dialog">
    <div aria-label="<?= $pageTitle; ?>" class="modal-dialog modal-lg modal-dialog-w1000">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= $pageTitle; ?></h4>
                <button type="button" aria-label="<?= gettext("close");?>" id="btn_close" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
				<div class="col-md-12">
                    <form id="addUpdateTouchPointForm" class="form-horizontal" method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                         
                        <div class="form-group">
                            <p class="col-lg-12 control-label"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                        </div>
                        <div class="form-group">
                            <label for="title" class="col-lg-12 control-label"><?= gettext("Touch Point Title"); ?><span style="color: #ff0000;">*</span></label>
                            <div class="col-lg-12">
                                <input id="title" class="form-control" placeholder="<?= gettext("Title here"); ?>" name="title"  type="text" value='<?= $edit ? htmlentities($edit['title']) : ''; ?>'' required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-12" for="redactor_content"><?= gettext("Description"); ?></label>
                            <div id="post-inner" class="col-lg-12">
                                <textarea class="form-control" name="description" rows="10" id="redactor_content" maxlength="2000" placeholder="<?= gettext("Description here"); ?>"><?= $edit ? htmlspecialchars($edit['description']) : ''; ?></textarea>
                                <p class="text-sm">
                                    <?= sprintf(gettext('<b>Personalize your %2$s:</b> You can now use the following variables in the subject and body of your %2$s: [[MENTOR_FIRST_NAME]], [[MENTOR_LAST_NAME]], [[MENTEE_FIRST_NAME]],[[MENTEE_LAST_NAME]]. These variables will be automatically replaced with the corresponding names when the %1$s is created and %2$s are generated. Name substitution will only occur if the system can uniquely identify the individual. For example, if a %1$s has multiple mentors, the variables [[MENTOR_FIRST_NAME]] and [[MENTOR_LAST_NAME]] will not be substituted. This ensures that the right information is always displayed.'), Team::GetTeamCustomMetaName($group->getTeamProgramType()), gettext('Touch Points')); ?>
                                </p>
                            </div>
                        </div>

                        <?php if(!empty($touchpoints)){ ?>
                        <div class="form-group mt-4">
                            <label class="control-label col-lg-12" for="comment"><?= gettext("Touch Point Timeline"); ?></label>
                            <div class="col-lg-12">
                                <div class="mt-3 timeline-range">
                                    <ol style="width:100% !important;">
                                        <?php
                                        $width = 0;
                                        $prev_width = 0;
                                        $x = 0;
                                        foreach ($touchpoints as $touchpoint) {
                                            $curr_width = ($touchpoint['tat']/$lastTat)*100;
                                            $width = $curr_width - $prev_width;
                                            $prev_width = $curr_width;
                                           
                                        ?>
                                        <li class="li" style="width: <?=$width?>% !important;">
                                            <span class="point"
                                            title="<?= $touchpoint['title'];?>" onclick='return;viewTodoOrTouchPointDetail("<?= $_COMPANY->encodeId($groupid); ?>","<?=$_COMPANY->encodeId($x); ?>","<?=$_COMPANY->encodeId(1); ?>",1)'></span>
                                        </li>
                                    <?php  $x++; } ?>
                                    </ol>
                                </div>
                            
                            </div>
                        </div> 
                        <?php } ?>

                        <div class="form-group">
                            <label for="tat" class="col-lg-12 control-label"><?= sprintf(gettext("Turnaround time in weekdays from start date of %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType())); ?></label>
                            <div class="col-lg-12">
                                <input id="tat"  type="number" min='0' class="form-control" placeholder="<?= gettext("Turnaround time in weekdays e.g. 5"); ?>" name="tat"  type="text" value="<?= $edit ? $edit['tat'] : '0'; ?>" required>
                            </div>
                        </div>
                        
                    </form>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="modal-footer text-center" style='text-align:center !important;display: block;'>
                <button type="submit" class="btn btn-affinity prevent-multiple-submit" onclick="addUpdateProgramTouchPointTemplate('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($id); ?>')" ><?= gettext("Submit");?></button>
                <button type="submit" aria-label="<?= gettext("close");?>" class="btn btn-affinity" data-dismiss="modal" ><?= gettext("Close");?></button>
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

        redactorFocusOut('#title'); // function used for focus out from redactor when press shift +tab.
    });


    $('#addUpdateTouchPointModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
});
</script>


