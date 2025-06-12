
<style type="text/css">
  .float-right-btn{
    float: right;
    border: 1px solid #0077b5;
    border-radius: 4px;
    padding: 0px 25px;
    cursor: pointer;
  }
 </style>
<div id="follow_chapter" class="modal fade" >
	<div aria-label="<?= sprintf(gettext("Manage Your Membership for %s %s"), $group->val('groupname_short'), $_COMPANY->getAppCustomization()['group']['name-short'] ); ?>" class="modal-dialog modal-lg" role="dialog" aria-modal="true">
		<div class="modal-content">
			<input type="hidden" id="joining_status" value="">
			<input type="hidden" id="join_chapter" value="">

			<div class="modal-header">
                <h2 id="modal-title" class="modal-title"><?= sprintf(gettext("Manage Your Membership for %s %s"), $group->val('groupname_short'), $_COMPANY->getAppCustomization()['group']['name-short'] ); ?></h2>
			</div>

			<div class="modal-body">
                <?php if (!empty($joinSuccessMsg)) { ?>
                    <div class="alert alert-info" aria-live="polite">
                        <?= $joinSuccessMsg; ?>
                    </div>
                <?php } ?>

                <div class="col-12 form-group-emphasis p-3">
                    <strong role="heading" aria-level="3"><?= sprintf(gettext("%s Membership"), $_COMPANY->getAppCustomization()['group']['name-short']) ?></strong>

                <?php
               $enc_groupid = $_COMPANY->encodeId($groupid);
                $call_method_parameters = array(
                    $enc_groupid                    
                );
                $call_other_method = base64_url_encode(json_encode(
                    array(
                        "method"=>"getFollowUnfollowGroup",
                        "parameters"=>$call_method_parameters
                    )
                )); // base64_encode for prevent js parsing error
                ?>
                <button id="follow-btn0" class="btn btn-secondary pull-right confirm"

                    <?php if (Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_LEAVE_BEFORE'], $groupid)){ ?>

                    onclick="loadDisclaimerByHook('<?=$_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_LEAVE_BEFORE'])?>', '<?=$_COMPANY->encodeId($groupid)?>', 0,'<?=$call_other_method?>')"
                    data-confirm-nobtn="No"
                    data-confirm-yesbtn="Yes"
                    data-toggle="popover"
                    title=""
                    data-original-title="<?= sprintf(gettext('Are you sure you want to leave this %s'), $_COMPANY->getAppCustomization()['group']['name-short']) ?>"
                    <?php } else { ?>

                    onclick="getFollowUnfollowGroup('<?= $_COMPANY->encodeId($groupid); ?>');"
                    data-confirm-nobtn="No"
                    data-confirm-yesbtn="Yes"
                    data-toggle="popover"
                    title=""
                    data-original-title="<?= sprintf(gettext('Are you sure you want to leave this %s'), $_COMPANY->getAppCustomization()['group']['name-short']) ?>"
                    <?php } ?>
                >
                    <?= sprintf(gettext("Leave %s"),$_COMPANY->getAppCustomization()['group']['name-short']); ?>
                </button>
            </div>

            <?php if (!empty($all_chapter)){ ?>
                <?php
                $mustJoinChapter = 0;
                $showMsgChapter = '';
                $showMsgChannel = '';
                $chapter_select_label = sprintf(gettext("Join or Leave %s of %s %s"),$_COMPANY->getAppCustomization()['chapter']['name-short'], $group->val('groupname_short'), $_COMPANY->getAppCustomization()['group']['name-short']);
                if ($group->val('chapter_assign_type')=='auto'){
                    $chapter_select_label = sprintf(gettext('Auto assigned %s'),$_COMPANY->getAppCustomization()['chapter']['name-short']);
                } elseif ($group->val('chapter_assign_type')=='auto'){

                } elseif ($group->val('chapter_assign_type')=='by_user_atleast_one'){
                    $mustJoinChapter = 1;
                    $showMsgChapter = sprintf(gettext('You are required to join one or more %1$s'),$_COMPANY->getAppCustomization()['chapter']['name-short']);
                } elseif ($group->val('chapter_assign_type')=='by_user_exactly_one'){
                    $mustJoinChapter = 1;
                    $showMsgChapter = sprintf(gettext('You are required to join one %1$s'), $_COMPANY->getAppCustomization()['chapter']['name-short']);
                } else {
                    $chapter_select_label .= ' ('. gettext('optional') .')';
                }
                ?>
            <div class="col-12 form-group-emphasis p-3" id="chapter_select_dropdown_section">
                <strong role="heading" aria-level="3"><?= sprintf(gettext("%s Membership"), $_COMPANY->getAppCustomization()['chapter']['name-short']) ?></strong>
                <div class="form-group mt-2">
                    <label for="chapter_select_dropdown_field">
                        <?= $chapter_select_label ?>
                    </label>

                    <?php if(!empty($autoAssign)){ ?>
                    <div style="font-size:medium;color:gray;" class="form-control"><?= $autoAssign ? implode(',',$autoAssign) : "Unable to match a ".$_COMPANY->getAppCustomization()['chapter']['name-short']." to your location" ?></div>
                    <?php } else { ?>
                    <select tabindex="-1" <?= $group->val('chapter_assign_type') =='auto' ? 'disabled' : ''; ?>  class="form-control selectpicker" <?= ($group->val('chapter_assign_type') != 'by_user_exactly_one') ? 'multiple' : '' ?> id="chapter_select_dropdown" style="width:100%; border:none !important; display:none;" data-live-search="true">
                    <?php
                    $followed_chapter_names = [];
                    foreach($all_chapter as $chapter) {
                        if (in_array($chapter['chapterid'], $followed_chapters)) {
                            $followed_chapter_names[] = $chapter['chaptername'];
                        }
                    ?>
                        <option data-tokens="<?= htmlspecialchars($chapter['chaptername']); ?>" value="<?=  $_COMPANY->encodeId($chapter['chapterid']);?>">
                            <?= htmlspecialchars($chapter['chaptername']); ?>
                        </option>
                    <?php
                    }
                    if (!empty($followed_chapter_names)) {
                        $showMsgChapter .= '. '. sprintf(gettext('You are currently a member of %1$s %2$s'), htmlspecialchars(Arr::NaturalLanguageJoin($followed_chapter_names)), $_COMPANY->getAppCustomization()['chapter']['name']);
                    }
                    ?>
                    </select>
                    <?php } ?>

                    <small class="form-text"><?= $showMsgChapter; ?></small>

                    <?php if (!empty($_COMPANY->getAppCustomization()['chapter']['join_button_help_text'])) { ?>
                    <small  class="form-text"><?=$_COMPANY->getAppCustomization()['chapter']['join_button_help_text']?></small>
                    <?php } ?>

                    <?php if ($group->val('chapter_assign_type') != 'auto'){ ?>
                    <button type="button" class="btn btn-affinity mt-2" id="followUnfollowGroupchapterID"  onclick="followUnfollowGroupchapter('<?= $_COMPANY->encodeId($groupid); ?>')" disabled>
                        <?= sprintf(gettext('Update %s Membership'),$_COMPANY->getAppCustomization()['chapter']['name-short']) ?>
                    </button>
                    <?php } ?>

                </div>

            </div>
            <?php } ?>

            <?php if (!empty($channels)){ ?>
				<div class="col-12 form-group-emphasis p-3" id="channel_select_dropdown_section">
                    <strong role="heading" aria-level="3"><?= sprintf(gettext("%s Membership"), $_COMPANY->getAppCustomization()['channel']['name-short']) ?></strong>
                    <div class="form-group mt-2">
                        <label for="channel_select_dropdown_field">
                            <?= sprintf(gettext("Join or Leave %s of %s %s"),$_COMPANY->getAppCustomization()['channel']['name-short-plural'], $group->val('groupname_short'), $_COMPANY->getAppCustomization()['group']['name-short']); ?> (<?= gettext("optional")?>)
                        </label>
                        <select tabindex="-1" class="form-control selectpicker" style="width: 100%; display:none;" multiple id="channel_select_dropdown" data-live-search="true" >
                            <?php
                            foreach($channels as $channel) {
                                if (in_array($channel['channelid'], $followed_channels)) {
                                    $followed_channel_names[] = $channel['channelname'];
                                }
                            ?>

                            <option data-tokens="<?= htmlspecialchars($channel['channelname']); ?>" value="<?= $_COMPANY->encodeId($channel['channelid']);?>" ><?= htmlspecialchars($channel['channelname']); ?></option>

                            <?php
                            }
                            if (!empty($followed_channel_names)) {
                                $showMsgChannel .= '. '. sprintf(gettext('You are currently a member of %1$s %2$s'), htmlspecialchars(Arr::NaturalLanguageJoin($followed_channel_names)), $_COMPANY->getAppCustomization()['channel']['name']);
                            }
                            ?>
                        </select>

                        <small class="form-text"><?= $showMsgChannel; ?></small>

                        <?php if (!empty($_COMPANY->getAppCustomization()['channel']['join_button_help_text'])) { ?>
                            <small  class="form-text"><?=$_COMPANY->getAppCustomization()['channel']['join_button_help_text']?></small>
                        <?php } ?>

                        <button type="button" class="btn btn-affinity mt-2" id="followUnfollowChannelID" onclick="followUnfollowChannel('<?= $_COMPANY->encodeId($groupid); ?>',0);" disabled>
                            <?= sprintf(gettext('Update %s Membership'),$_COMPANY->getAppCustomization()['channel']['name-short']) ?>
                        </button>
                    </div>
                </div>
            <?php } ?>

            <?php if (count($all_chapter) || count($channels)){ ?>

            <?php } ?>

            <?php if ($group->isTeamsModuleEnabled() && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']) { ?>
                <div class="col-12 form-group-emphasis p-3">
                    <strong role="heading" aria-level="3"><?= sprintf(gettext("%s Registration"), Team::GetTeamCustomMetaName($group->getTeamProgramType())) ?></strong>
                    <div class="js-team-join-requests-container">
                        Loading ....
                    </div>
                    <script>
                        manageJoinRequests('<?= $group->encodedId() ?>', 'v2');
                    </script>
                </div>
            <?php } ?>
			</div>

			<div class="modal-footer">
                <button id="btn_close2" type="button" class="btn btn-affinity button-manage" onclick="updateGroupJoinLeaveButton('<?= $_COMPANY->encodeId($groupid); ?>')"><?= gettext('Close'); ?></button>
			</div>

		</div>
	</div>
</div>
<script>
    // Chapter Section
    $('#chapter_select_dropdown').multiselect({nonSelectedText:"<?= ($group->val('chapter_assign_type') =='auto') ? gettext('Not Assigned') : sprintf(gettext('Select %s to join'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",numberDisplayed:3,nSelectedText:"<?=sprintf(gettext('%s Joined'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",allSelectedText:"<?=sprintf(gettext('All %s Joined'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",maxHeight:200,onChange:function(option,checked){$('#followUnfollowGroupchapterID').prop('disabled',false);}});
    $('#chapter_select_dropdown').val(<?= json_encode($encodedFollowedChapters); ?>);
    $("#chapter_select_dropdown").multiselect("refresh");
    //Channels Section
    $('#channel_select_dropdown').multiselect({nonSelectedText:"<?= sprintf(gettext('Select %s to join'), $_COMPANY->getAppCustomization()['channel']['name-short-plural']) ?>",numberDisplayed:3,nSelectedText:"<?= sprintf(gettext('%s Joined'), $_COMPANY->getAppCustomization()['channel']['name-short-plural']) ?>",allSelectedText:"<?= sprintf(gettext('All %s Joined'), $_COMPANY->getAppCustomization()['channel']['name-short-plural']) ?>",maxHeight:200,onChange:function(option,checked){$('#followUnfollowChannelID').prop('disabled',false);}});
    $('#channel_select_dropdown').val(<?= json_encode($encodedFollowedChannels); ?>);
    $("#channel_select_dropdown").multiselect("refresh");
</script>

<script>
    function updateSelectedChapter(g,c){
       $('#follow_btn_chapter').attr('onClick', "followUnfollowGroupchapter('"+g+"','"+c+"');");
    }
</script>
<script>
    $(document).ready(function () {	    
        $('.multiselect').attr( 'tabindex', '0' );
    });
</script>

<script>
    $(document).ready(function(){ // Handle bootstrap multiselct dropdown close by tab press
        $(function(){
            $('.multiselect-container').keyup(function(e) { 
                if (e.key == 'Tab') {
                    $(this).click();
                }
            });
        });
    })

retainPopoverLastFocus(); //When Cancel the popover then retain the last focus.

$('#follow_chapter').on('shown.bs.modal', function () {
   $('body').addClass('modal-open');
   $('#follow-btn0').trigger('focus');   
});

trapFocusInModal("#follow_chapter");
</script>

