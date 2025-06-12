<div id="team_assignment" class="modal fade">
    <div aria-label="<?= $pageTitle; ?>" class="modal-dialog" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title" class="modal-title"><?= $pageTitle; ?></h2>
                <button aria-label="<?= gettext('close');?>" type="button" id="btn_close" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
				<div class="col-md-12">
                    <form  class="" id="create_or_assign_team">
                        <input type="hidden" id='userid' name='userid' value="<?= $_COMPANY->encodeId($useridSelected); ?>">
                        <input type="hidden" id='section' name='section' value="<?= $_COMPANY->encodeId($section); ?>">
                        <?php if($section == 1 || $section == 3){ // $section == 3 is Assign to Self feature of People Hero ?>
                            <div class="form-group">
                                <label for="event_series"><?= Team::GetTeamCustomMetaName($group->getTeamProgramType()); ?> <?= gettext("Name");?></label>
                                <input  class="form-control" id="team_name"  placeholder="<?= sprintf(gettext("%s name here"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>..." name="team_name" value="<?= isset($suggestedTeamName) ? $suggestedTeamName : ''; ?>" />
                            </div>
                        <?php if(!empty($chapters)){ ?>
                            <div class="form-group">
                                <label ><?= sprintf(gettext("Select a %s"),$_COMPANY->getAppCustomization()['chapter']['name-short']);?></label>
                                <select class="form-control" name="chapterid" id="chapterid">
                                    <option value=""><?= sprintf(gettext("Select a %s"),$_COMPANY->getAppCustomization()['chapter']['name-short']);?></option>
                                    <?php foreach($chapters as $chapter){ 
                                        if (!$_USER->canManageGroupChapter($groupid,$chapter['regionids'], $chapter['chapterid'])) {
                                            continue;
                                        }
                                    ?>
                                        <option value="<?= $_COMPANY->encodeId($chapter['chapterid']); ?>" <?= $chapter['isactive'] == 0 ? 'disabled' : ''; ?>><?= $chapter['chaptername']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        <?php } ?>
                        <?php } else { ?>
                            <div class="form-group">
                                <label ><?= Team::GetTeamCustomMetaName($group->getTeamProgramType(),1); ?></label>
                                <select class="form-control" name="teamid" id="teamid">
                                    <option value=""><?= sprintf(gettext("Select a %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType())); ?></option>
                                    <?php foreach($teams as $team){ ?>
                                        <option value="<?= $_COMPANY->encodeId($team['teamid']); ?>"><?= htmlspecialchars($team['team_name']); ?> (<?=$statusLabel[$team['isactive']]?>)</option>
                                    <?php } ?>
                                </select>
                            </div>
                        <?php } ?>

                    <?php if ($_ZONE->val('app_type') == 'peoplehero' && $section ==3){  // $section == 3 is Assign to Self feature of People Hero ?>
                        <div class="form-group">
                            <label ><?= gettext("Your Role as");?></label>
                            <select class="form-control" name="mentor_type" id="mentor_type">
                                <option value="" disabled><?= gettext("Select role type");?></option>
                                <?php foreach($allRoles as $role){
                                    $sel = "";
                                    if ($mentorJoinRequest && $mentorJoinRequest['roleid'] == $role['roleid']){
                                        $sel = "selected";
                                    }  else {
                                        continue;
                                    }
                                ?>
                                    <option value="<?= $_COMPANY->encodeId($role['roleid']); ?>" <?= $sel; ?>><?= $role['type']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="type"><?= sprintf(gettext("%s Role as"),$selectedUser->getFullName());?></label>
                            <select class="form-control" name="type" id="type">
                                <option value="" disabled><?= gettext("Select role type");?></option>
                                <?php foreach($allRoles as $role){
                                    $sel = "";
                                    if ($joinRequest && $joinRequest['roleid'] == $role['roleid']){
                                        $sel = "selected";
                                    } else {
                                        continue;
                                    } 
                                ?>
                                    <option value="<?= $_COMPANY->encodeId($role['roleid']); ?>" <?= $sel; ?>><?= $role['type']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        
                    <?php } else{ ?>
                        <div class="form-group">
                            <label for="type"><?= gettext("Role Type");?></label>
                            <select class="form-control" name="type" id="type">
                                <option value="" disabled><?= gettext("Select role type");?></option>
                                <?php foreach($allRoles as $role){
                                    $sel = "";
                                    if ($joinRequest && $joinRequest['roleid'] == $role['roleid']){
                                        $sel = "selected";
                                    } else {
                                        continue;
                                    } 
                                ?>
                                    <option value="<?= $_COMPANY->encodeId($role['roleid']); ?>" <?= $sel; ?>><?= $role['type']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    <?php } ?>
                    
                    </form>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="modal-footer text-center" style='text-align:center !important;display: block;'>
                <button type="submit" class="btn btn-affinity prevent-multi-clicks" onclick="createOrAssignExitingTeamToUser('<?= $_COMPANY->encodeId($groupid); ?>')" ><?= gettext("Submit");?></button>
                <button type="submit" class="btn btn-affinity"  aria-hidden="true" data-dismiss="modal" ><?= gettext("Close");?></button>
            </div>
        </div>
    </div>
</div>

<script>
$('#team_assignment').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
});
</script>