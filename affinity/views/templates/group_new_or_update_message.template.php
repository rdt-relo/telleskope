<style>
    .multiselect{
        text-align: left;
    }
    .btn-group{
        width: 100%;
        max-width: 100%;
        border: 1px solid rgb(212, 212, 212);
        height: 38px;
        margin: 5px 0;
        border-radius: 5px;
        background: rgb(242, 242, 242);
    }
    .additional_Recipients{
        display:none;
    }
</style>
<div class="row">
    <div class="col-md-12" id=MessageComposer>
        <div class="">
            <div class="col-md-12">
                <div class="col-md-10">
                    <div class="inner-page-title">
                    <h2><?= gettext("New Message");?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-container">
                    <form id="send_message_form_composer">
                        <input type="hidden" name="messageid" id="messageid" value="<?= $_COMPANY->encodeId($messageid);?>" >
                        <p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>

                        <div class="form-group">
                            <label class=""><?= gettext("To");?> <span style="color:red"> *</span></label>
                            <select aria-label="<?= gettext('To');?>" class="form-control col-md-12" id="recipients_base" name="recipients_base" onchange="changeRecipientsBaseScope(this.value)" required>
                               <option value="0"><?= gettext("Select an option");?></option>
                                <?php if(!$groupid){ ?>
                                    <option value="1" <?= $message && $message->val('recipients_base') == 1 ? 'selected' : ''; ?>><?= gettext("All Users in the Zone");?></option>
                                    <option value="2" <?= $message && $message->val('recipients_base') == 2 ? 'selected' : ''; ?>><?= sprintf(gettext("All Users in the Zone who are not a member of any %s"),$_COMPANY->getAppCustomization()['group']['name-short']);?></option>
                                <?php } ?>
                                <option value="3" <?= $message && $message->val('recipients_base') == 3 ? 'selected' : ''; ?>><?= sprintf(gettext("%s Members or Leaders"),$_COMPANY->getAppCustomization()['group']['name-short']);?></option>
                               <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled'] && ($_USER->isAdmin() || $_USER->canPublishContentInGroup($groupid)) ) { ?>
                                    <option value="4"  <?= $message ? ($message->val('recipients_base') =='4' ? 'selected' : '') : ''; ?>><?= gettext("Dynamic List")?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {
                                // Dynamic list prompt
							if($groupid == 0){
								$dynamic_list_info = gettext("Only the zone members who are also part of users in the selected dynamic lists will receive an email notification when you publish by email.");
							}else{
								$dynamic_list_info = sprintf(gettext("Only the %s members who are also part of users in the selected dynamic lists will receive an email notification when you publish by email."), $group->val('groupname_short'));
							} ?>
                         
                            <div class="form-group" id="list_selection" style="display:<?= $message ? ($message->val('recipients_base') =='4' ? 'Block' : 'none') : 'none'; ?>;">
                                <label  class=""><?= gettext('Select Dynamic Lists');?></label>
                              
                                <select class="form-control" name="list_scope[]" id="list_scope" multiple>
                                    <?php 
                                    $preSelectedLists = array();
                                    if ($message){
                                        $preSelectedLists = explode(',',$message->val('listids'));
                                    }
                                    foreach($lists as $list){ ?>
                                        <option value="<?= $_COMPANY->encodeId($list->val('listid')); ?>" <?= in_array($list->val('listid'),$preSelectedLists) ? 'selected' : ''; ?>><?= $list->val('list_name'); ?></option>
                                    <?php } ?>
                                </select>
                                <small>
                                    <?= gettext("You can choose one or more existing dynamic lists or you can") ?>
                                    <a role="button" aria-label="Add a new dynamic list" onclick="manageDynamicListModal('<?=$_COMPANY->encodeId($groupid);?>')" href="javascript:void(0)"><?= gettext("create your own.")?></a>
                                    <?= $dynamic_list_info ?>
                                    <?= gettext("View the users associated with the selected lists: ")?><a role="button" aria-label="View users" onclick="getDynamicListUsers('<?=$_COMPANY->encodeId($groupid);?>')" href="javascript:void(0)"><?= gettext("view users")?></a>
                                </small>
                            </div>
                       
                        <?php } ?>


                    <?php if($groupid){
                        $gids = array();
                        if ($message){
                            $gids = explode(',',$message->val('groupids'));
                        }
                    ?>
                        <input type="hidden" name="groupids[]" value="<?= $_COMPANY->encodeId($groupid);?>" >

                        <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled'] &&  $_USER->canManageGroupSomeChapter($groupid)) {
                            $chids = array();
                                if ($message && $message->val('chapterids') != ''){
                                    $chids = explode(',',$message->val('chapterids'));
                                }    
                        ?>
                            <div class="form-group chapters_selection_div">
                                <label for="chaptersList" class=""><?= $_COMPANY->getAppCustomization()['chapter']['name']; ?></label>
                                <select tabindex="-1" class="form-control options-header-option selectpicker" name="chapterids[]" multiple id="chaptersList" onchange="recalculateRecipientType()">
                                    <?php
                                    foreach($chapters as $row){ ?>
                                    <?php if ($_USER->canManageGroupChapter($groupid,$row['regionids'],$row['chapterid'])) {?>
                                    <option data-chapter="1" value="<?= $_COMPANY->encodeId($row['chapterid']); ?>" <?= $preSelectAllChaptersAndChannels || in_array($row['chapterid'],$chids) ? 'selected' : ''; ?> ><?= htmlspecialchars($row['chaptername']); ?></option>
                                    <?php } ?>
                                    <?php }  ?>

                                    <?php if ($_USER->canManageGroup($groupid)) { ?>
                                    <option value="<?= $_COMPANY->encodeId(0); ?>" <?= in_array('0',$chids) ? 'selected' : ''; ?>><?= sprintf(gettext('%s not assigned'),$_COMPANY->getAppCustomization()['chapter']['name']);?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        <?php }  ?>

                        <?php if ($_COMPANY->getAppCustomization()['channel']['enabled'] && $_USER->canManageGroupSomeChannel($groupid)) { 
                            $chanids = array();
                            if ($message && $message->val('channelids') != ''){
                                $chanids = explode(',',$message->val('channelids'));
                            }
                        ?>
                        
                            <div class="form-group channels_selection_div">
                                <label for="channelList" class=""><?= $_COMPANY->getAppCustomization()['channel']['name']; ?></label>
                                <select tabindex="-1" class="form-control options-header-option selectpicker" name="channelids[]" multiple id="channelList" onchange="recalculateRecipientType()" >
                                    <?php
                                    foreach($channels as $row){ ?>
                                    <?php if ($_USER->canManageGroupChannel($groupid,$row['channelid'])) {?>
                                    <option data-channel="1" value="<?= $_COMPANY->encodeId($row['channelid']); ?>" <?= $preSelectAllChaptersAndChannels || in_array($row['channelid'],$chanids) ? 'selected' : ''; ?>><?= htmlspecialchars($row['channelname']); ?></option>
                                    <?php }  ?>
                                    <?php }  ?>

                                    <?php if ($_USER->canManageGroup($groupid)) { ?>
                                    <option value="<?= $_COMPANY->encodeId(0); ?>" <?= in_array('0',$chanids) ? 'selected' : ''; ?>><?= sprintf(gettext('%s not assigned'),$_COMPANY->getAppCustomization()['channel']['name']);?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        <?php }  ?>

                    <?php  } else { ?>

                        <div class="form-group group_data" style="display: <?= $message && $message->val('recipients_base')!=3 ? 'none' : 'block'; ?>">
                            <label for="groupList" class=""><?=$_COMPANY->getAppCustomization()['group']['name-plural'] ?> <span style="color:red"> *</span></label>
                            <select tabindex="-1" class="form-control options-header-option selectpicker" name="groupids[]" multiple id="groupList" onchange="filteredChapterList();filteredChannelList();">
                                <?php 	if(count($groups)>0){ 
                                            $gids = array();
                                            if ($message){
                                                $gids = explode(',',$message->val('groupids'));
                                            }
                                    ?>
                                    <?php 	foreach ($groups as $g0) { ?>
                                        <option value="<?= $_COMPANY->encodeId($g0['groupid']); ?>" <?= in_array($g0['groupid'],$gids) ? 'selected' : ''; ?>  >&ensp;<?= $g0['groupname']; ?></option>
                                    <?php	} ?>
                                    <?php	}else{ ?>
                                        <option>-<?= sprintf(gettext("No %s"),$_COMPANY->getAppCustomization()['group']['name-plural']);?> -</option>
                                <?php	} ?>
                            </select>
                        </div>

                        <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled'] ) {
                                $chids = array();
                                if ($message){
                                    $chids = explode(',',$message->val('chapterids'));
                                }

                        ?>
                            <div class="form-group group_data_chapter" style="display: <?= $message ? ($message->val('recipients_base')!=3 ? 'none' : 'block') : 'none'; ?>">
                                <label for="chaptersList" class=""><?= $_COMPANY->getAppCustomization()['chapter']['name']; ?></label>
                                <select tabindex="-1" class="form-control options-header-option selectpicker" name="chapterids[]" multiple id="chaptersList" onchange="recalculateRecipientType()" >
                                <?php
                                    foreach($filteredChapters as $key => $row){
                                    $chapteridsArray = array_column($row,'chapterid');

                                    $enids = array();
                                    $ids = array();
                                    foreach($chapteridsArray as $ch){
                                        $enids[] = $_COMPANY->encodeId($ch);
                                        $ids[] = $ch;
                                    }
                                    $chapterids = implode(',',$enids);
                                    ?>
                                    <option data-chapter="1" value="<?= $chapterids; ?>" <?= !empty(array_intersect($chids,$ids)) ? 'selected' : ''; ?>><?= $key; ?></option>
                                <?php }  ?>
                                </select>
                            </div>
                        <?php }  ?>


                        <?php if ($_COMPANY->getAppCustomization()['channel']['enabled'] ) { 
                            $chanids = array();
                            if ($message && $message->val('channelids') != ''){
                                $chanids = explode(',',$message->val('channelids'));
                            }
                        ?>
                        
                            <div class="form-group group_data_channel" style="display: <?= $message ? ($message->val('recipients_base')!=3 ? 'none' : 'block') : 'none'; ?>">
                                <label for="channelList" class=""><?= $_COMPANY->getAppCustomization()['channel']['name']; ?></label>
                                <select tabindex="-1" class="form-control options-header-option selectpicker" name="channelids[]" multiple id="channelList" onchange="recalculateRecipientType()" >
                                    <?php
                                    foreach($filteredChannels as  $key => $row){ 
                                        $channelIdsArray = array_column($row,'channelid');

                                        $chnlEncids = array();
                                        $chnlIds = array();
                                        foreach($channelIdsArray as $ch){
                                            $chnlEncids[] = $_COMPANY->encodeId($ch);
                                            $chnlIds[] = $ch;
                                        }
                                        $channelids = implode(',',$chnlEncids);
                                    ?>
                                        <option data-channel="1" value="<?= $channelids; ?>"  <?= !empty(array_intersect($chanids,$chnlIds)) ? 'selected' : ''; ?> ><?= htmlspecialchars($key); ?></option>
                                    <?php }  ?>
                                </select>
                            </div>
                        <?php }  ?>

                    <?php } ?>

                        <?php
                        $mtyps = array();
                        if ($message) {
                            $mtyps = explode(',', $message->val('sent_to'));
                        }

                        $current_group = $groupid ? Group::GetGroup($groupid) : null;
                        $from_label = $message?->val('from_name') ?: $current_group?->val('from_email_label') ?: $_ZONE?->val('email_from_label') ?: FROM_NAME;
                        $from_label_hash = md5($from_label);

                        ?>

                      <div class="form-group">
                        <label class="" for="from"><?= gettext("Email From Label");?></label>
                        <input type="text" class="form-control" name="" id="from" value="<?=$from_label?>" disabled>
                        <input type="hidden" class="form-control" name="from" value="<?=$from_label?>">
                        <input type="hidden" class="form-control" name="from_hash" value="<?=$_COMPANY->getGenericHash(html_entity_decode($from_label));?>">
                      </div>

                        <div class="form-group group_data">
                            <label class="" for="group_members"><?= gettext("Recipient Types");?> <span style="color:red"> *</span></label>
                            <select tabindex="-1" class="form-control multiselect col-md-12" id="group_members" name="members_type[]" multiple>
                                <?php if ($_COMPANY->getAppCustomization()['teams']['enabled'] && $groupid ) { ?>
                                    <option value="5" <?= in_array(5,$mtyps) ? 'selected' : ''; ?> ><?= sprintf(gettext('%1$s Members (in active %1$ss)'),$_COMPANY->getAppCustomization()['teams']['name']);?></option>
                                <?php } ?>
                                <option value="2" <?= in_array(2,$mtyps) ? 'selected' : ''; ?> ><?= sprintf(gettext("%s Members"),$_COMPANY->getAppCustomization()['group']['name-short']);?></option>
                                <option value="1" <?= in_array(1,$mtyps) ? 'selected' : ''; ?> ><?= sprintf(gettext("%s Leaders"),$_COMPANY->getAppCustomization()['group']['name-short']);?></option>
                            <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled'] ) { ?>
                                <option value="3" <?= in_array(3,$mtyps) ? 'selected' : ''; ?>><?= sprintf(gettext("%s Leaders"),$_COMPANY->getAppCustomization()['chapter']["name-short"]);?></option>
                            <?php } ?>
                            <?php if ($_COMPANY->getAppCustomization()['channel']['enabled']) { ?>
                                <option value="4" <?= in_array(4,$mtyps) ? 'selected' : ''; ?>><?= sprintf(gettext("%s Leaders"),$_COMPANY->getAppCustomization()['channel']["name-short"]);?></option>
                            <?php } ?>
                            <option value="0" <?= in_array(0,$mtyps) ? 'selected' : ''; ?>><?=gettext("Other")?></option>
                        </select>
                            
                        </div>

                        <div class="form-group additional_Recipients">
                            <label for="additionalRecipients" class=""><?= gettext("Additional Recipients");?></label>
                            <input type="text" class="form-control" name="additionalRecipients" id="additionalRecipients" value="<?= $message ? $message->val('additional_recipients') : ''; ?>" placeholder="<?= gettext('Enter additional recipients emails'); ?>..." >
                        </div>

                        <?php if ($_COMPANY->getAppCustomization()['teams']['enabled'] && $groupid ) { 
                            $allRoles = Team::GetProgramTeamRoles($groupid, 1);
                            $selectedRoles = array();
                            if ($message) {
                                $selectedRoles = explode(',', $message->val('team_roleids'));
                            }
                            
                        ?>
                            <div class="form-group group_data" id="roles_dropdown" style="display:none;">
                                <label class="" for="team_members"><?= sprintf(gettext("%s Roles"),$_COMPANY->getAppCustomization()['teams']['name']);?><span style="color:red"> *</span></label>
                                <select tabindex="-1" class="form-control multiselect col-md-12" id="team_members" name="team_member_roles[]" multiple>
                                <?php foreach($allRoles as $role){ 
                                         if ($role['sys_team_role_type'] !=2 && $role['sys_team_role_type'] !=3){
                                            continue;
                                        }
                                ?>
                                    <option value="<?= $_COMPANY->encodeId($role['roleid'])?>" <?= in_array($role['roleid'],$selectedRoles) ? 'selected' : ''; ?>><?= $role['type'];?></option>
                                <?php } ?>
                                </select>
                            </div>
                        <?php } ?>
                        <div class="form-group">
                            <label class="" for="subject"><?= gettext("Subject");?> <span style="color:red"> *</span></label>
                            <input type="text" class="form-control" name="subject" id="subject" value="<?= $message ? $message->val('subject') : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="" for="redactor_content"><?= gettext("Message");?> <span style="color:red"> *</span></label>
                            <div id="post-inner" class="post-inner-edit">
                                <textarea class="form-control" placeholder="<?= gettext('Message here');?>" name="message" rows="6" id="redactor_content" maxlength="3000" aria-required="true"><?= $message ? $message->val('message') : ''; ?></textarea>
                            </div>
                        </div>


                        
                        <div class="form-group">
                            <label  class=""><?= gettext('Other Options'); ?></label>
                            <div class="col-12 ml-1 mt-1">
                                <input type="checkbox" class="form-check-input" name="content_replyto_email_checkbox" id="content_replyto_email_checkbox" <?= (!empty($message) && $message->val('content_replyto_email') != "") ? 'checked' : '' ?>>
                               <label for="content_replyto_email_checkbox"> <?= gettext("Custom Reply To Email"); ?></label>
                                <div id="replyto_email" class="" <?= (empty($message) || $message->val('content_replyto_email') == "") ? 'style="display:none;"' : '' ?>>
                                    <input type="email" id="content_replyto_email" name="content_replyto_email" value="<?= empty($message) ? '' : $message->val('content_replyto_email') ?>" class="form-control" placeholder="<?= gettext('Add a custom reply to email'); ?>"/>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($message) { ?>
                            <?= $message->renderAttachmentsComponent('v19') ?>
                        <?php } else { ?>
                            <?= Message::CreateEphemeralTopic()->renderAttachmentsComponent('v19') ?>
                        <?php } ?>

                        <div class="col-md-12 text-center p-5">
                            <button type="button" class="btn btn-primary" onclick="groupMessageSave('<?= $_COMPANY->encodeId($groupid);?>',<?= $groupid ? 1 : 2; ?>, event)"><?= gettext("Preview Message");?></button>
                            <button type="button" class="btn btn-primary" onclick="groupMessageList('<?= $_COMPANY->encodeId($groupid);?>',<?= $groupid ? 1 : 2; ?>)"><?= gettext("Cancel");?></button>
                        </div>
                    </form>  

                </div>
            </div>
        </div>
    </div>
</div>
<div id="groupMessagePreview"></div>

<script type="text/javascript">
    function recalculateRecipientType() {

        let chapters = $('#chaptersList').find(':selected').data('chapter');
        let channels = $('#channelList').find(':selected').data('channel');
       
        let groupLeaders = 1;
        let chapterLeaders = 3;
        let channelLeaders = 4;

        console.log(chapters);
        console.log(channels);
        if (chapters == 1) {
            $('#group_members option[value="' + groupLeaders + '"]').prop('selected', false);
            $('#group_members option[value="' + groupLeaders + '"]').prop('disabled', true);
            $('#group_members option[value="' + chapterLeaders + '"]').prop('disabled', false);
            if (channels == 1) {
                // Logic for chapters == 1 and channels == 1
                $('#group_members option[value="' + channelLeaders + '"]').prop('disabled', false);
            } else {
                // Logic for chapters == 1 and channels != 1
                $('#group_members option[value="' + channelLeaders + '"]').prop('selected', false);
                $('#group_members option[value="' + channelLeaders + '"]').prop('disabled', true);
            }
        } else {
            if (channels == 1) {
                // Logic for chapters != 1 and channels == 1
                $('#group_members option[value="' + groupLeaders + '"]').prop('selected', false);
                $('#group_members option[value="' + chapterLeaders + '"]').prop('selected', false);
                $('#group_members option[value="' + groupLeaders + '"]').prop('disabled', true);
                $('#group_members option[value="' + chapterLeaders + '"]').prop('disabled', true);
                $('#group_members option[value="' + channelLeaders + '"]').prop('disabled', false);
            } else {
                // Logic for chapters != 1 and channels != 1
                $('#group_members option[value="' + groupLeaders + '"]').prop('disabled', false);
                $('#group_members option[value="' + chapterLeaders + '"]').prop('disabled', false);
                $('#group_members option[value="' + channelLeaders + '"]').prop('disabled', false);

            }
        }
        setTimeout(() => {
            $("#group_members").multiselect("refresh");
		}, 1000);
    }

    $(function () {
        $("#content_replyto_email_checkbox").click(function () {
            if ($(this).is(":checked")) {
                $("#replyto_email").show();
            } else {
                $("#replyto_email").hide();
            }
        });
    });
</script>
<script>
     function filteredChapterList(){
        var g = $("#groupList").val();
        $.ajax({
            url: 'ajax_message.php?getFilteredChapterList=1',
            type: 'GET',
            data: {'groupids':g.join()},
            success: function(data) {
                $('#chaptersList').multiselect('destroy');
                if (g.length){
                    $(".group_data_chapter").show();
                    $("#chaptersList").html(data);
                    $('#chaptersList').multiselect({nonSelectedText: "- <?= gettext('Not Selected (Default All)');?> -",numberDisplayed: 1,nSelectedText  : "<?= sprintf(gettext('%s selected'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural']);?>",allSelectedText: "<?= sprintf(gettext('All %s selected'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural']);?>", includeSelectAllOption: true,disableIfEmpty:true, maxHeight:400});
                } else {
                    $(".group_data_chapter").hide();
                }
                recalculateRecipientType();
            
            }
        });
    }

    function filteredChannelList(){
        var g = $("#groupList").val();
        $.ajax({
            url: 'ajax_message.php?getFilteredChannelList=1',
            type: 'GET',
            data: {'groupids':g.join()},
            success: function(data) {
                $('#channelList').multiselect('destroy');
                if (g.length){
                    $(".group_data_channel").show();
                    $("#channelList").html(data);
                    $('#channelList').multiselect({nonSelectedText: "- <?= gettext('Not Selected (Default All)');?> -",numberDisplayed: 1,nSelectedText  : "<?= sprintf(gettext('%s selected'),$_COMPANY->getAppCustomization()['channel']['name-short-plural']);?>",allSelectedText: "<?= sprintf(gettext('All %s selected'),$_COMPANY->getAppCustomization()['channel']['name-short-plural']);?>", includeSelectAllOption: true,disableIfEmpty:true, maxHeight:400});
                } else {
                    $(".group_data_channel").hide();
                }
                recalculateRecipientType();
            }
        });
    }
     

    $('#groupList').multiselect({nonSelectedText: "<?= sprintf(gettext('Select a %s'),$_COMPANY->getAppCustomization()['group']['name-short']);?>",numberDisplayed: 3,nSelectedText  : "<?= sprintf(gettext('%s selected'),$_COMPANY->getAppCustomization()['group']['name-short-plural']);?>",disableIfEmpty:true,allSelectedText: "<?= sprintf(gettext('All %s selected'),$_COMPANY->getAppCustomization()['group']['name-short-plural']);?>", includeSelectAllOption: true, maxHeight:400});

    <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
        $('#chaptersList').multiselect({nonSelectedText: "- <?= sprintf(gettext('%s Not Selected (Default All)'),$_COMPANY->getAppCustomization()['chapter']['name-short']);?> -",numberDisplayed: 3,nSelectedText  : "<?= sprintf(gettext('%s selected'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural']);?>",disableIfEmpty:true,allSelectedText: "<?= sprintf(gettext('All %s selected'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural']);?>", includeSelectAllOption: true, maxHeight:400});
     <?php } ?> 
    <?php if ($_COMPANY->getAppCustomization()['channel']['enabled']) { ?>
        $('#channelList').multiselect({nonSelectedText: "- <?= sprintf(gettext('%s Not Selected (Default All)'),$_COMPANY->getAppCustomization()['channel']['name-short']);?> -",numberDisplayed: 3,nSelectedText  : "<?= sprintf(gettext('%s selected'),$_COMPANY->getAppCustomization()['channel']['name-short-plural']);?>",disableIfEmpty:true,allSelectedText: "<?= sprintf(gettext('All %s selected'),$_COMPANY->getAppCustomization()['channel']['name-short-plural']);?>", includeSelectAllOption: true, maxHeight:400});
    <?php } ?>
     $('#group_regions').multiselect({nonSelectedText: "<?= gettext('Select Regions');?>",numberDisplayed: 3,nSelectedText  : "<?= gettext('Region(s) Selected');?>",disableIfEmpty:true,allSelectedText: "<?= gettext('All Regions Selected');?>", maxHeight:400});
     $('#group_members').multiselect({nonSelectedText: "<?= gettext('Select Recipient Types');?>",numberDisplayed: 4,nSelectedText  : "<?= gettext('Recipient Type(s) selected');?>",disableIfEmpty:true,allSelectedText: "<?= gettext('All Recipient Types Selected');?>", maxHeight:400});

     $('#team_members').multiselect({nonSelectedText: "<?= gettext('Select Role Types');?>",numberDisplayed: 4,nSelectedText  : "<?= gettext('Recipient Type(s) selected');?>",disableIfEmpty:true,allSelectedText: "<?= gettext('All Recipient Types Selected');?>", maxHeight:400});
</script>

<script>
    var fontColors = <?= $fontColors; ?>;
    $('#redactor_content').initRedactor('redactor_content','messages',['fontcolor','counter','fontsize'],fontColors,'<?= $_COMPANY->getImperaviLanguage(); ?>');
    redactorFocusOut('#subject'); // function used for focus out from redactor when press shift.
</script>

<script>
     function changeRecipientsBaseScope(v) {
        $(".group_data").hide();
        $("#list_selection").hide();
        $(".group_data_channel").hide();
        $(".group_data_chapter").hide();
        $(".chapters_selection_div").show();
        $(".channels_selection_div").show();
        if (v == 3) {
            $(".group_data").show();
            $(".group_data_channel").show();
            $(".group_data_chapter").show();
        } else if (v == 4) {
            $("#list_selection").show();
            $(".chapters_selection_div").hide();
            $(".channels_selection_div").hide();
        }
        // Hide .additional_Recipients div when v is not 3
        if (v !== "3") {
            $("#group_members option").prop("selected", false);
            $("#group_members").multiselect("refresh");
            $(".additional_Recipients").hide();
        } else {
            // Show .additional_Recipients div when v is 3 and "Others" is selected
            if ($("#group_members option[value='0']").is(":selected")) {
                $(".additional_Recipients").show();
            }
        }
    }

    $(document).ready(function () {
        let v = $("#recipients_base").val();
        changeRecipientsBaseScope(v);

        // Event listener to handle changes in the recipients_base dropdown
        $("#recipients_base").on("change", function () {
            let v = $(this).val();
            changeRecipientsBaseScope(v);
        });
    });
</script>
<script>
$(document).ready(function () {
    // pre select options
    recalculateRecipientType();

    $("#group_members").change(function () {
        $(".additional_Recipients").hide();
        $("#roles_dropdown").hide();

        $(this).find("option:selected").each(function () {
            var optionValue = $(this).attr("value");
            if (optionValue === "0") {
                $(".additional_Recipients").show();
            }
            if (optionValue === "5") {
                $("#roles_dropdown").show();
            }
        });
    }).change();

    $('.multiselect').attr('tabindex', '0');
    $('#group_members').attr('tabindex', '-1');
    $(".redactor-voice-label").text("<?= gettext('Message here');?>");
});
    
//On Esc Key...
$(document).keyup(function(e) {
     if (e.keyCode == 27) {
        $('.dropdown-menu').removeClass('show');        
    }       
});
</script>

<script>
	$('#list_scope').multiselect({
		nonSelectedText: "<?=gettext("No list selected"); ?>",
		numberDisplayed: 3,
		nSelectedText: "<?= gettext('List selected');?>",
		disableIfEmpty: true,
		allSelectedText: "<?= gettext('Multiple lists selected'); ?>",
		enableFiltering: true,
		maxHeight: 400,
		enableClickableOptGroups: true
	});

$("input:checkbox").keypress(function (event) {
    if (event.keyCode === 13) {
        $(this).click();
    }
});
</script>


