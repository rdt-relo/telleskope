<div id="eventCollaborationApproverSelectionModal" tabindex="-1" class="modal fade">
	<div class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h4 tabindex="0" class="modal-title" id="form_title"><?= $modelTitle; ?></h4>
			</div>
			<div class="modal-body">
                <div class="col-md-12">
                <form class="form-horizontal" method="post" id="eventCollaborationApproverSelectionFrom">

                    <?php foreach($groupToInvites as $g){ 
                        $approvers = $g->getGroupApproversToAcceptTopicCollaborationInvites();

                        $pendingChapters = array();
                        if (isset($chapterToInvites[$g->id()])) {
                            $pendingChapters = $chapterToInvites[$g->id()];
                        }
                    ?>
                        
                        <div class="form-group form-group-emphasis">
                            <?php  if (in_array($g->id(), $groupsWithChapterSelected)) { ?>
                                <label class="control-lable col-12" for="title"><?=sprintf(gettext('%1$s %2$s - %3$s approvers'), $g->val('groupname'), $_COMPANY->getAppCustomization()['group']['name-short'],$_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>:</label>
                            <?php } else{ ?>
                            <label class="control-lable col-12" for="title"><?=sprintf(gettext('%1$s %2$s approvers'), $g->val('groupname'), $_COMPANY->getAppCustomization()['group']['name-short'])?>:</label>
                            <div class="col-12 m-0 p-0 pl-3">
                            <?php if (!empty($approvers)){ ?>
                            
                                <?php foreach($approvers as $approver){ ?>
                                    <div class="form-check form-check-inline border m-2 px-2">
                                        <input class="form-check-input" type="checkbox" name="approversEmails_<?= $_COMPANY->encodeId($g->id()); ?>[]" value="<?= $approver['email']; ?>">
                                        <label class="form-check-label ellipsis" for="inlineCheckbox1">

                                        <?= User::BuildProfilePictureImgTag($approver['firstname'], $approver['lastname'],$approver['picture'],'memberpicture_small',sprintf(gettext('%s Profile Picture'),$approver['firstname'])); ?> <?= $approver['firstname'].  " ". $approver['lastname']; ?>
                    
                                        </label>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <p class="m-2">
                                    <small id="emailHelp" class="form-text text-muted"><?= sprintf(gettext('This %1$s currently lacks approvers, which prevents collaboration requests from being processed. To move forward, kindly consider either removing this %1$s from the collaboration or requesting your administrator to add approvers.'), $_COMPANY->getAppCustomization()['group']['name-short']) ?></small>
                                </p>
                            <?php } ?>
                            </div>
                        <?php } ?>
                    <?php   if(!empty($pendingChapters)){ 
                                foreach($pendingChapters as $chapterid){ 
                                $chapter = Group::GetChapterName($chapterid,$g->id());
                                $approvers = Group::GetChaptersApproversToAcceptTopicCollaborationInvites($chapterid); ?>
                        
                                <div class="form-group form-group-emphasis my-0 py-0 pl-3">
                                    <label class="control-lable col-12" for="email"><i class="fas fa-globe" style="" aria-hidden="true"></i> <?=sprintf(gettext('%1$s %2$s approvers'), $chapter['chaptername'], $_COMPANY->getAppCustomization()['chapter']['name-short'])?>:</label>
                                    <div class="col-12 m-0 p-0 pl-3">
                                    <?php if (!empty($approvers)){ ?>
                                    
                                        <?php foreach($approvers as $approver){ ?>
                                            <div class="form-check form-check-inline border m-2 px-2">
                                                <input class="form-check-input" type="checkbox" name="approversEmails_chapter_<?= $_COMPANY->encodeId($chapterid); ?>[]" value="<?= $approver['email']; ?>">
                                                <label class="form-check-label ellipsis" for="inlineCheckbox1">

                                                <?= User::BuildProfilePictureImgTag($approver['firstname'], $approver['lastname'],$approver['picture'],'memberpicture_small',sprintf(gettext('%s Profile Picture'),$approver['firstname'])); ?> <?= $approver['firstname'].  " ". $approver['lastname']; ?>
                            
                                                </label>
                                            </div>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <p class="m-2 px-2">
                                            <small id="emailHelp" class="form-text text-muted"><?= sprintf(gettext('This %1$s currently lacks approvers, which prevents collaboration requests from being processed. To move forward, kindly consider either removing this %1$s from the collaboration or requesting your administrator to add approvers.'), $_COMPANY->getAppCustomization()['chapter']['name-short']) ?></small>
                                        </p>
                                    <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                    <?php } ?>
                        </div>

                    <?php } ?>
                </form>
                  
                </div>
			</div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="location.reload();" data-dismiss="modal"><?= gettext('Cancel'); ?></button>
                <button type="button" class="btn btn-primary" 
                    onclick="sendTopicCollaborationApproverRequest('<?= $_COMPANY->encodeId($topicId); ?>')"
                ><?= gettext('Submit'); ?></button>
            </div>
		</div>  
	</div>
</div>

<script>
    $('.initial').initial({
        charCount: 2,
        textColor: '#ffffff',
        color: window.tskp?.initial_bgcolor ?? null,
        seed: 0,
        height: 30,
        width: 30,
        fontSize: 15,
        fontWeight: 300,
        fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
        radius: 0
    });


    function sendTopicCollaborationApproverRequest(e) {
        var formdata = $('#eventCollaborationApproverSelectionFrom')[0];
        var finaldata = new FormData(formdata);
        finaldata.append('topicType', '<?= $topicType; ?>');
        finaldata.append('topicId', e);
        $.ajax({
            url: 'ajax_events.php?sendTopicCollaborationApproverRequest=1',
            type: 'POST',
            data: finaldata,
            processData: false,
            contentType: false,
            cache: false,
            success: function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title:jsonData.title,text:jsonData.message}).then(function (result) {
                        if(jsonData.status==1){
                            location.reload();
                        }
				    });
                } catch (e) {}
            }
        });
    }
</script>