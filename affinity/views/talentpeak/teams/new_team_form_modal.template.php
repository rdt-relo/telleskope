<style>
    .select2-container--focus{      
        box-shadow: 0 0 0 .2rem #000 !important;
    }
</style>
<div id="newTeamModal" class="modal fade">
    <div aria-label="<?= $pageTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title" class="modal-title"><?= $pageTitle; ?></h2>
                <button aria-label="<?= gettext('close');?>" type="button" id="btn_close" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div id="modal_body">
                <div class="modal-body">
                    <div class="col-md-12">
                        <form  class="" id="create_team_form">
                            <input type="hidden" id='teamid' name='teamid' value="<?= $_COMPANY->encodeId($teamid); ?>">
                            <p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                        <div class="col-12 form-group-emphasis p-2">
                            <div class="form-group">
                                <label for="team_name"><?= sprintf(gettext("%s name"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?><span style="color: #ff0000;"> *</span></label>
                                <input  class="form-control" id="team_name"  placeholder="<?= sprintf(gettext("%s name here"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>.." name="team_name" value="<?= $team ?  htmlspecialchars($team->val('team_name')) : ''; ?>" required/>
                            </div>
                        <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
                            <?php if($team){ ?>
                                <input type="hidden" name="chapterid" id="chapterid" value="<?= $_COMPANY->encodeId($team->val('chapterid')); ?>" >
                            <?php } else{ ?>
                            <?php if (!empty($chapters) && $group->getTeamRoleRequestChapterSelectionSetting()['allow_chapter_selection']){ ?>
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
                            <textarea  class="form-control" id="team_description"  placeholder="<?= gettext("Add description");?> ..." name="team_description" required><?= $team ?  htmlspecialchars($team->val('team_description')) : ''; ?></textarea>
                        </div>
                    <?php } ?>
                    </div>
                    <?php  if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']) { ?>
                        <div class="col-12 form-group-emphasis p-2">
                            <div class="form-group">
                                <label class="control-label" for="handleids"><?= gettext("Hashtags");?> &nbsp;<i tabindex="0" aria-label="<?= sprintf(gettext('Hashtags are used to search for or filter %s'),Team::GetTeamCustomMetaName($group->getTeamProgramType()))?>" class="fa fa-question-circle" data-toggle="tooltip" title="<?= sprintf(gettext('Hashtags are used to search for or filter %s'),Team::GetTeamCustomMetaName($group->getTeamProgramType()))?>"></i></label>
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
                                        <label for="label_<?= $circleRolesCapacity[$role['roleid']]['circle_role_max_capacity']?>"><?= sprintf(gettext("%s maximum number of participants"),$role['type']);?></label>
                                        <input type="hidden" name="circle_roleid[]" value="<?= $role['roleid']; ?>">
                                        <input type="number" id="label_<?= $circleRolesCapacity[$role['roleid']]['circle_role_max_capacity']?>" min="<?= $role['min_required']> 1 ? $role['min_required'] : 1;?>" max="<?= $role['max_allowed']; ?>" class="form-control" placeholder="<?= sprintf(gettext('Please input an integer value between or equal to %1$s and %2$s'),($role['min_required']> 1 ? $role['min_required'] : 1), $role['max_allowed'])?>" name="circle_role_capacity[]" value="<?= $circleRolesCapacity[$role['roleid']]['circle_role_max_capacity']?>" <?= $role['sys_team_role_type'] == '2' ? 'readonly' : ''?>>
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
                    <button type="submit" class="btn btn-affinity prevent-multi-clicks" onclick="createNewTeam('<?= $_COMPANY->encodeId($groupid); ?>','<?= $section; ?>')" ><?= gettext("Submit");?></button>
                    <button aria-label="<?= gettext('close');?>" type="submit" class="btn btn-affinity" data-dismiss="modal" ><?= gettext("Close");?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$('#newTeamModal').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus');
   $('.redactor-in').attr('aria-required', 'true');
});

$('#newTeamModal').on('hidden.bs.modal', function () {   
    var team_value = $('#team_name').val();
    if(team_value){
        var teamid = $('#teamid').val();
         $('#teamBtn'+teamid).focus();
    }else{
        $('#newTeamBtn').focus();
    }   
});
retainFocus('#newTeamModal');

$(function () {
    $('[data-toggle="tooltip"]').tooltip();
})


$(document).ready(function(){
	var fontColors = <?= $fontColors; ?>;
    $('#team_description').initRedactor('team_description', 'team',['fontcolor','counter','handle','fontsize'],fontColors,'<?= $_COMPANY->getImperaviLanguage(); ?>');
    $(".redactor-voice-label").text("<?= gettext('Add description');?>");
    redactorFocusOut('#team_name'); // function used for focus out from redactor when press shift +tab.

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
});


</script>