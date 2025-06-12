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
		<div id="feedbackModel" class="modal fade" role="dialog" tabindex="-1">
			<div class="modal-dialog modal-lg">
				<!-- Modal content-->
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title"><?= $pageTitle; ?></h4>
						<button type="button" id="btn_close" class="close" aria-label="Close Modal Box" data-dismiss="modal" onclick="window.location.href='success_callback.php'">&times;</button>
					</div>
					<div class="modal-body">
						<div class="col-md-12">
							<form id="todoForm">
								<input type="hidden" id='teamid' name='teamid' value="<?= $_COMPANY->encodeId($teamid); ?>">
								<input type="hidden" id='taskid' name='taskid' value="<?= $_COMPANY->encodeId($feedbackid); ?>">
								<input type="hidden" id='task_type' name='task_type' value="feedback">
								<div class="form-group" >
									<label><?= gettext("Title");?>:</label>
									<input  class="form-control" id="tasktitle"  placeholder="<?= gettext("Feedback title here");?>.." name="tasktitle" value="<?= $todo ? htmlspecialchars($todo['tasktitle']) : ''; ?>" />
								</div>
								<div class="form-group" >
									<label><?= gettext("Feedback");?>:</label>
									<div id="post-inner" class="post-inner-edit">
										<textarea  class="form-control" id="description"  placeholder="<?= gettext("Feedback here");?>.." name="description" ><?= $todo ? $todo['description'] : ''; ?></textarea>
									</div>
								</div>
								<div class="form-group">
									<label>For:</label>
									<select name="assignedto" id="assignedto" class="form-control">
										<option value="" <?= $todo ? 'disabled' : ''; ?> >-<?= gettext("Select an option");?>-</option>
										<option value="<?= $_COMPANY->encodeId(Team::TEAM_FEEDBACK_FOR_PROGRAM_LEADS) ?>" <?= $todo ? ( Team::TEAM_FEEDBACK_FOR_PROGRAM_LEADS == $todo['assignedto'] ? 'selected' : ' disabled') : ''?> >
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
								<?php if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
									<div class="radio pl-2">
										<label><input type="radio" name="visibility" value="<?=Team::TEAM_TASK_VISIBILITY['ALL_MEMBERS']?>" <?= $todo && $todo['visibility'] == Team::TEAM_TASK_VISIBILITY['ALL_MEMBERS'] ? 'checked' : '' ?>> <?= sprintf(gettext("All %s members"),$_COMPANY->getAppCustomization()['teams']['name']);?></label>
									</div>
								<?php } ?>
								</div>
							</form>
						</div>
						<div class="clearfix"></div>
					</div>
					<div class="modal-footer text-center" style='text-align:center !important;display: block;'>
						<button type="submit" class="btn btn-affinity prevent-multi-clicks" onclick="addUpdateTeamContent('<?= $_COMPANY->encodeId($groupid); ?>')" ><?= gettext("Submit");?></button>
						<button type="submit" class="btn btn-secondary"  aria-hidden="true" data-dismiss="modal" onclick="window.location.href='success_callback.php'" ><?= gettext("Close");?></button>
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
   		$(".redactor-voice-label").text("Add touchpoint description");
        $('#feedbackModel').modal({
            backdrop: 'static',
            keyboard: false
        });
    });
</script>
