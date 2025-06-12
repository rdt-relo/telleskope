<?php include __DIR__ . '/header.html'; ?>


<!-- New recognition POP UP -->
<style>
.fa.fa-times {
	color: #f80e0e;
	background-color: #fff;
	position: absolute;
	margin-left: 0px;
}
</style>
<div class="container">
    <div class="row">
		<div id="todoModal" class="modal fade" role="dialog" tabindex="-1">
			<div class="modal-dialog modal-lg">
				<!-- Modal content-->
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title"><?= $pageTitle; ?></h4>
						<button type="button" id="btn_close" class="close"  onclick="window.location.href='success_callback.php'" aria-label="Close Modal Box" data-dismiss="modal">&times;</button>
					</div>
					<div class="modal-body">
						<div class="col-md-12">
							<form id="todoForm">
								<input type="hidden" id='teamid' name='teamid' value="<?= $_COMPANY->encodeId($teamid); ?>">
								<input type="hidden" id='taskid' name='taskid' value="<?= $_COMPANY->encodeId($taskid); ?>">
								<input type="hidden" id='parent_taskid' name='parent_taskid' value="<?= $_COMPANY->encodeId($parent_taskid); ?>">
								<input type="hidden" id='task_type' name='task_type' value="todo">
								<div class="form-group">
									<label><?= gettext("Task Title");?></label>
									<input  class="form-control" id="tasktitle"  placeholder="<?= gettext("Task title here");?>.." name="tasktitle" value="<?= $todo ? htmlspecialchars($todo['tasktitle']) : ''; ?>" />
								</div>
							<?php if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
								<input type="hidden" id='assignedto' name='assignedto' value="<?= $_COMPANY->encodeId($_USER->id()); ?>">
							<?php } else { ?>
								<div class="form-group">
									<label><?= gettext("To be completed by");?></label>
									<select name="assignedto" id="assignedto" class="form-control">
										<option value="">-<?= gettext("Select an option");?>-</option>
									<?php if(!empty($uniqueAssignees)){ ?>
										<?php foreach($uniqueAssignees as $assignee){ 
											if ($assignee['firstname'] == "Deleted"){
												continue;
											}
											$checked = '';
											if ($todo && $assignee['userid'] == $todo['assignedto']){
												$checked = 'selected';
											}    
										?>
											<option value="<?= $_COMPANY->encodeId($assignee['userid']); ?>" <?= $checked; ?>>
												<?= $assignee['firstname'].' '.$assignee['lastname']; ?>
											</option>
										<?php } ?>
									<?php } ?>
									</select>
								</div>
							<?php } ?>

								<div class="form-group date">
									<div class="row">
										<label class="col-sm-12"><?= gettext("Due Date (Optional)");?></label>
										<div class="col-sm-4 mb-2">
											<input  class="form-control" id="start_date" value="<?= $todo ? $duedate : ''; ?>"  placeholder="YYYY-MM-DD" name="duedate" readonly />
										</div>
										<div class="col-sm-2 mb-2">
											<select aria-label="<?= gettext('hour');?>" class="form-control" id="start_date_hour" name='hour'>
												<?=getTimeHoursAsHtmlSelectOptions($todo ? $hour : '');?>
											</select>
										</div>
										
										<div class="col-sm-2 mb-2">
											<select aria-label="<?= gettext('minutes');?>" class="form-control" id="start_date_minutes" name="minutes">
												<?=getTimeMinutesAsHtmlSelectOptions($todo ? $minutes : '');?>
											</select>
										</div>
										
										<div class="col-sm-4">
												<label class="radio-inline"><input aria-label="<?= gettext('AM');?>" type="radio" value="AM" name="period" <?= $todo ? ($period=='AM' ? "checked" : '') : "checked"; ?> >AM</label>
												<label class="radio-inline"><input aria-label="<?= gettext('PM');?>" type="radio" value="PM" name="period" <?= $todo ? ($period =='PM' ? "checked" : '') : ""; ?> >PM</label>
										</div>
										<br/>
										<div class="col-md-12">
											<button type="button" class='timezone btn btn-link' onclick="showTzPicker();" ><a href="javascript:void(0)"  class="link_show" id="tz_show"><?= $timezone; ?> Time</a></button>
										</div>
										<input type="hidden" name="timezone" id="tz_input" value="<?= $timezone; ?>">
										<div id="tz_div" style="display:none;">
											<label class="col-sm-12 control-lable" for="selected_tz"><?= gettext('Change Timezone');?></label>
											<div class="col-sm-12">
												<select class="form-control teleskope-select2-dropdown" id="selected_tz" onchange="selectedTimeZone();" style="width: 100%;">
													<?php echo getTimeZonesAsHtmlSelectOptions($timezone); ?>
												</select>
											</div>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label><?= gettext("Description (Optional)");?></label>
									<div id="post-inner" class="post-inner-edit">
										<textarea  class="form-control" id="description"  placeholder="<?= gettext("Description here");?>.." name="description" ><?= $todo ? $todo['description'] : ''; ?></textarea>
								
									</div>
								</div>
							</form>
						</div>
						<div class="clearfix"></div>
					</div>
					<div class="modal-footer text-center" style='text-align:center !important;display: block;'>
						<button type="button" class="btn btn-affinity" onclick="addUpdateTeamContent('<?= $_COMPANY->encodeId($groupid); ?>')" ><?= gettext("Submit");?></button>
						<button type="button" class="btn btn-secondary"  aria-hidden="true" data-dismiss="modal"  onclick="window.location.href='success_callback.php'" ><?= gettext("Close");?></button>
					</div>
				</div>
			</div>
		</div>
    </div>
</div>
<script>
	$(document).ready(function(){
		var fontColors = <?= $fontColors; ?>;
		$('#description').initRedactor('description', 'teamtasks',['fontcolor','counter','handle'],fontColors,'<?= $_COMPANY->getImperaviLanguage(); ?>');
   		$(".redactor-voice-label").text("Add action item description");
        $('#todoModal').modal({
            backdrop: 'static',
            keyboard: false
        });
    });
	retainFocus('#todoModal');
</script>
