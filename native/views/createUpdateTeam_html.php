<?php include __DIR__ . '/header.html'; ?>

<!-- New/update Team POP UP -->
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
		<div id="newTeamModal" class="modal fade" role="dialog" tabindex="-1">
			<div class="modal-dialog modal-lg">
				<!-- Modal content-->
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title"><?= $pageTitle; ?></h4>
						<button type="button" id="btn_close" class="close"  onclick="window.location.href='success_callback.php'" aria-label="Close Modal Box" data-dismiss="modal">&times;</button>
					</div>
					<div class="modal-body">
						<div class="col-md-12">
						<form  class="" id="create_team_form">
                            <input type="hidden" id='teamid' name='teamid' value="<?= $_COMPANY->encodeId($teamid); ?>">
                            <p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                        <div class="col-12 form-group-emphasis p-2">
                            <div class="form-group">
                                <label for="team_name"><?= sprintf(gettext("%s name"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?><span style="color: #ff0000;"> *</span></label>
                                <input  class="form-control" id="team_name"  placeholder="<?= sprintf(gettext("%s name here"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>.." name="team_name" value="<?= $team ?  htmlspecialchars($team->val('team_name')) : ''; ?>" />
                            </div>
                        <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
                            <?php if($team){ ?>
                                <input type="hidden" name="chapterid" id="chapterid" value="<?= $_COMPANY->encodeId($team->val('chapterid')); ?>" >
                            <?php } else{ ?>
                            <?php if (!empty($chapters) && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?>
                                <div class="form-group">
                                    <label for="chapterid"><?= sprintf(gettext("Select a %s"),$_COMPANY->getAppCustomization()['chapter']['name-short']);?></label>
                                    <select name="chapterid" id="chapterid" class="form-control">
                                        <option value=""><?= sprintf(gettext("Select a %s"),$_COMPANY->getAppCustomization()['chapter']['name-short']);?></option>
                                        <?php foreach($chapters as $chapter){ 
                                            if (!$_USER->canManageGroupChapter($groupid,$chapter['regionids'], $chapter['chapterid'])) {
                                                continue;
                                            }
                                            $sel = "";
                                            if ($team && $team->val('chapterid') == $chapter['chapterid']){
                                                $sel = "selected";
                                            }
                                        ?>
                                            <option value="<?= $_COMPANY->encodeId($chapter['chapterid']); ?>" <?= $sel; ?>><?= $chapter['chaptername']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            <?php } ?>
                            <?php } ?>
                        <?php } ?>
                    <?php  if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']) { ?>
                        <div class="form-group">
                            <label for="team_description"><?= gettext("Description");?><span style="color: #ff0000;"> *</span></label>
                            <textarea  class="form-control" id="team_description"  placeholder="<?= gettext("Add description");?> ..." name="team_description" ><?= $team ?  htmlspecialchars($team->val('team_description')) : ''; ?></textarea>
                        </div>
                    <?php } ?>
                    </div>
                    <?php  if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']) { ?>
                        <div class="col-12 form-group-emphasis p-2">
                            <div class="form-group">
                                <label class="control-label" for="email"><?= gettext("Hashtags");?> &nbsp;<i class="fa fa-question-circle" data-toggle="tooltip" title="<?= sprintf(gettext('Hashtags are used to search for or filter %s'),Team::GetTeamCustomMetaName($group->getTeamProgramType()))?>"></i></label>
                                <select class="form-control" id="handleids" name="handleids[]" style="width: 100%;" multiple>
                                    <?php foreach($hashTagHandles as $tag){ ?>
                                        <?php 
                                            $selected = "";
                                            if (in_array($tag['hashtagid'],$selectedHandles)){
                                                $selected = "selected";
                                            }
                                        ?>
                                        <option value="<?= $tag['handle']; ?>" <?=$selected; ?> ><?= $tag['handle']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    
                        <div class="col-12 form-group-emphasis p-2">
                            <div class="form-group">
                                <label for="team_description"><strong><?= sprintf(gettext("%s Members Restrictions"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></strong></label>
                                <div class="form-group mt-2">
                                <?php foreach($allRoles as $role){ ?>
                                    <div class="form-group">
                                        <label><?= sprintf(gettext("%s maximum number of participants"),$role['type']);?></label>
                                        <input type="hidden" name="circle_roleid[]" value="<?= $role['roleid']; ?>">
                                        <input type="number" min="<?= $role['min_required']> 1 ? $role['min_required'] : 1;?>" max="<?= $role['max_allowed']; ?>" class="form-control" placeholder="<?= sprintf(gettext('Please input an integer value between or equal to %1$s and %2$s'),($role['min_required']> 1 ? $role['min_required'] : 1), $role['max_allowed'])?>" name="circle_role_capacity[]" value="<?= $circleRolesCapacity[$role['roleid']]['circle_role_max_capacity']?>" <?= $role['sys_team_role_type'] == '2' ? 'readonly' : ''?>>
                                        <small class="form-text text-muted"><?= sprintf(gettext('Please input an integer value between or equal to %1$s and %2$s'),($role['min_required']> 1 ? $role['min_required'] : 1), $role['max_allowed'])?></small>
                                    </div>
                                <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                        </form>
						</div>
						<div class="clearfix"></div>
					</div>
					<div class="modal-footer text-center" style='text-align:center !important;display: block;'>
						<button type="button" class="btn btn-affinity" onclick="createNewTeam('<?= $_COMPANY->encodeId($groupid); ?>')" ><?= gettext("Submit");?></button>
						<button type="button" class="btn btn-secondary"  aria-hidden="true" data-dismiss="modal"  onclick="window.location.href='success_callback.php'" ><?= gettext("Close");?></button>
					</div>
				</div>
			</div>
		</div>
    </div>
</div>
<script>
	$('#newTeamModal').on('shown.bs.modal', function () {
		$('#modal-title').trigger('focus')
	});

	$(function () {
		$('[data-toggle="tooltip"]').tooltip();
	})

	$(document).ready(function(){
		var fontColors = <?= $fontColors; ?>;
		$('#team_description').initRedactor('team_description', 'team',['fontcolor','counter','handle','fontsize'],fontColors,'<?= $_COMPANY->getImperaviLanguage(); ?>');
		$(".redactor-voice-label").text("<?= gettext('Add description');?>");


		$("#handleids").select2({
			placeholder: "<?= gettext('Add or search hash tags'); ?>",
			allowClear: true,

			<?php if ($_COMPANY->getAppCustomization()['teams']['hashtag']['can_create_realtime_on_userend'] ?? false) { ?>
			tags: true,
			createTag: function (params) {
				return {
				id: params.term,
				text: params.term,
				newOption: true
				}
			},
			"language": {
				"noResults": function(){
					return '<?=addslashes(gettext("No existing Hash Tags found. To create a new Hash Tag enter a value without space."))?>';
				}
			},
			<?php } ?>

			templateResult: function (data) {
				var $result = $("<span></span>");

				$result.text(data.text);

				if (data.newOption) {
				$result.append('<em style="float: right;">(<?=addslashes(gettext("Add a new Hash Tag"))?>)</em>');
				}

				return $result;
		}
		}).on('select2:opening', function(e) {
			$(this).data('select2').$dropdown.find(':input.select2-search__field').attr('placeholder', "<?= addslashes(gettext('Add or Search Hash Tags')) ?>..")
		});	


		$('#newTeamModal').modal({
			backdrop: 'static',
			keyboard: false
		});
	});


	function createNewTeam(g,s){
	
		let formdata = $('#create_team_form')[0];
		let finaldata  = new FormData(formdata);
		finaldata.append("groupid",g);

		let team_name = $("#team_name").val();
        let team_nm = $("#team_name");
        let team_nm_val = team_nm.val().trim();
        if (team_nm_val.length<3) {
            team_nm.focus();
            swal.fire({title: '',text:'Name required (minimum 3 characters)',allowOutsideClick:false});
            return;
        }
        let team_desr = $("#team_description");
        if ((typeof team_desr.val() !== 'undefined')) {
            let team_desr_val = stripTags(team_desr.val()).trim();
            if (team_desr_val.length < 24) {
                team_desr.focus();
                swal.fire({title: '',text:'Description required (minimum 24 characters)',allowOutsideClick:false});
                return;
            }
        }
        $.ajax({
            url: './ajax_native.php?createNewTeam=1',
            type: 'POST',
            data: finaldata,
            processData: false,
            contentType: false,
            cache: false,
            success: function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
                        if (jsonData.status >0){
                            window.location.href= 'success_callback.php';
                        }
                    });
                } catch(e) { swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false}); }
            }
        });

	}


</script>
