<style>
    .chapter_selection {
        padding: 7px 2px 0px 1px;
        background-color: #fff;
        height: 55px;
    }

    .multiselect{
        text-align: left;
        margin-left: 5px;
        border: 1px solid #0077b5;
        border-radius: 4px;
        padding: 0px 25px;
        cursor: pointer;
    }
    .btn-group, .btn-group-vertical {
        width: 100%;
    }
    .multiselect-selected-text{
        overflow-y: auto;
        width: 100%;
        float: left;
    }
    .multiselect-native-select > .btn-group {
        border: none !important;
    }
</style>
<div tabindex="-1" id="update_chapter_channel_membership" class="modal fade">
	<div aria-label="<?= sprintf(gettext('Update %1$s %2$s Membership of %3$s'),$group_name ,$_COMPANY->getAppCustomization()['group']['name-short'],$memberUser->val('firstname').' '.$memberUser->val('lastname')); ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
		<div class="modal-content">
			<input type="hidden" id="joining_status" value="">
			<input type="hidden" id="join_chapter" value="">

			<div class="modal-header">
				<h2 id="modal-title" class="modal-title"><?= sprintf(gettext('Update %1$s %2$s Membership of %3$s'),$group_name ,$_COMPANY->getAppCustomization()['group']['name-short'],$memberUser->val('firstname').' '.$memberUser->val('lastname')); ?></h2>
                <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">×</button>
			</div>

			<div class="modal-body">
            <?php if (!empty($all_chapter)){ ?>
                <div class="">
                    <p class="mb-1">
                    <?php if ($group->val('chapter_assign_type')=='auto'){ ?>
                        <?= sprintf(gettext('Auto assigned %s'),$_COMPANY->getAppCustomization()['chapter']['name-short']); ?>
                    <?php } else { ?>
                        <?= sprintf(gettext("Update membership for %s of this %s"),$_COMPANY->getAppCustomization()['chapter']['name-short'], $_COMPANY->getAppCustomization()['group']['name-short']); ?>
                        <?php
                        if(!in_array($group->val('chapter_assign_type'), array('by_user_atleast_one', 'by_user_exactly_one'))) {
                        echo "(". gettext("optional") .")";
                        }
                        ?>
                    <?php } ?>
                    </p>
                    <div class="chapter_selection">
                        <?php if(!empty($autoAssign)){ ?>
                        <div style="font-size:medium;color:gray;" class="auto-assign-chapter pl-2"><?= $autoAssign ? implode(',',$autoAssign) : "Unable to match a ".$_COMPANY->getAppCustomization()['chapter']['name-short']." to user location" ?></div>
                        <?php } else { ?>
                        <select tabindex="-1" <?= $group->val('chapter_assign_type') =='auto' ? 'disabled' : ''; ?>  class="form-control selectpicker" <?= ($group->val('chapter_assign_type') != 'by_user_exactly_one') ? 'multiple' : '' ?> id="chapter_select_dropdown" onchange="updateGroupChapterMembership('<?= $_COMPANY->encodeId($memberUserid); ?>','<?= $_COMPANY->encodeId($groupid); ?>')" style="width:100%; border:none !important;" data-live-search="true">
                        <?php foreach($all_chapter as $chapter){ 
                            if (!$_USER->canManageGroupChapter($groupid,$chapter['regionids'],$chapter['chapterid'])) {
                                continue;
                            }
                        ?>
                            <option data-tokens="<?= htmlspecialchars($chapter['chaptername']); ?>" value="<?=  $_COMPANY->encodeId($chapter['chapterid']);?>" ><?= htmlspecialchars($chapter['chaptername']); ?></option>
                            <?php } ?>
                        </select>
                        <?php } ?>
                    </div>

                    <?php
                    $mustJoinChapter = 0;
                    if($group->val('chapter_assign_type') == 'by_user_atleast_one' ||  $group->val('chapter_assign_type') == 'by_user_exactly_one'){
                        $mustJoinChapter = 1;
                        if($group->val('chapter_assign_type') == 'by_user_exactly_one'){
                            $showMsg = sprintf(gettext("User is required to join one %2\$s."),$_COMPANY->getAppCustomization()['group']['name-short'],$_COMPANY->getAppCustomization()['chapter']['name-short']);
                        } else {
                            $showMsg = sprintf(gettext("User is required join one or more %2\$s."),$_COMPANY->getAppCustomization()['group']['name-short'],$_COMPANY->getAppCustomization()['chapter']['name-short']);
                        }
                    ?>
                    <small class="pl-2 mb-3 mt-0 pt-0 " style="color:gray;"><?= $showMsg; ?></small>
                    <?php } ?>

                </div>
            <?php } ?>

            <?php if (!empty($channels)){ ?>
				<div class="mt-3">
                    <p class="mb-1">
                        <?= sprintf(gettext("Update membershop for %s of this %s"),$_COMPANY->getAppCustomization()['channel']['name-short-plural'], $_COMPANY->getAppCustomization()['group']['name-short']); ?>
                        (<?= gettext("optional")?>)
                    </p>
				    <div class="chapter_selection">
                        <select tabindex="-1" class="selectpicker" style="width: 100%;" multiple id="channel_select_dropdown" onchange="updateGroupChannelMembership('<?= $_COMPANY->encodeId($memberUserid); ?>','<?= $_COMPANY->encodeId($groupid); ?>',0);" data-live-search="true" >
                            <?php foreach($channels as $channel){ 
                                
                                if (!$_USER->canManageGroupChannel($groupid, $channel['channelid'])) {
                                    continue;
                                }    
                            ?>
                            <option data-tokens="<?= htmlspecialchars($channel['channelname']); ?>" value="<?= $_COMPANY->encodeId($channel['channelid']);?>" ><?= htmlspecialchars($channel['channelname']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
             
                </div>
            <?php } ?>

			</div>

			<div class="modal-footer">
				<button id="btn_close2" type="button" class="btn btn-affinity-gray button-manage"
                onclick="closeJoinGroupModal('<?= $_COMPANY->encodeId($groupid); ?>',<?= ($group->val('chapter_assign_type') == 'by_user_atleast_one' ||  $group->val('chapter_assign_type') == 'by_user_exactly_one') ? 1 : 0;?>,'<?= $_COMPANY->encodeId($memberUserid); ?>')"><?= gettext("Close"); ?></button>
			</div>

		</div>
	</div>
</div>

<style type="text/css">
  .modal-body{
    max-height: 500px;
  }
  .float-right-btn{
    float: right;
    border: 1px solid #0077b5;
    border-radius: 4px;
    padding: 0px 25px;
    cursor: pointer;
    min-width: 
  }
  .loder {
    padding: 0px 20px;
    -webkit-animation: spin 2s linear infinite;
    animation: spin 2s linear infinite;
  }

  @-webkit-keyframes spin {
    0% { -webkit-transform: rotate(0deg); }
    100% { -webkit-transform: rotate(360deg); }
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
</style>
<script>
    // Chapter Section
    $('#chapter_select_dropdown').multiselect({nonSelectedText: "<?= ($group->val('chapter_assign_type') =='auto') ? gettext("Not Assigned") : sprintf(gettext('Select %s to join'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",numberDisplayed: 3,nSelectedText  : '<?=sprintf(gettext("%s Joined"), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>',allSelectedText: '<?=sprintf(gettext("All %s Joined"), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>',maxHeight:200});
    $('#chapter_select_dropdown').val(<?= json_encode($encodedFollowedChapters); ?>);
    $("#chapter_select_dropdown").multiselect("refresh");
    
    //Channels Section
    $('#channel_select_dropdown').multiselect({nonSelectedText: "<?= sprintf(gettext('Select %s to join'),$_COMPANY->getAppCustomization()['channel']['name-short-plural'])?>",numberDisplayed: 3,nSelectedText  : '<?=sprintf(gettext("%s Joined"), $_COMPANY->getAppCustomization()['channel']['name-short-plural'])?>',allSelectedText: '<?=sprintf(gettext("All %s Joined"), $_COMPANY->getAppCustomization()['channel']['name-short-plural'])?>',maxHeight:200});
    $('#channel_select_dropdown').val(<?= json_encode($encodedFollowedChannels); ?>);
    $("#channel_select_dropdown").multiselect("refresh");
   
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

    $('#update_chapter_channel_membership').on('shown.bs.modal', function () {
        $('#btn_close').trigger('focus')
    });

    $('#update_chapter_channel_membership').on('hidden.bs.modal', function (e) {
		$('#lead_<?= $_COMPANY->encodeId($memberUserid); ?>').trigger('focus');
	})

    $(document).ready(function () {
        $('.multiselect').attr( 'tabindex', '0' );
    });
    retainFocus("#update_chapter_channel_membership");
</script>