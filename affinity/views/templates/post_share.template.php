<style>
	.about-button:focus { 
	border-color: #80bdff;
	outline: 0;
	box-shadow: 0 0 0 .1rem rgba(0,123,255,.25);
	}
	.hide{
		display:none;
	}
	.progress_bar{
		background-color: #efefef;
		margin: 5px 0px;
		padding: 15px;
	}
</style>
<div id="sharePostWithUser" class="modal fade">
    <div aria-label="<?= sprintf(gettext("Share %s with Users"),Post::GetCustomName(true))?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
		<div class="modal-content">

            <div class="modal-header">
            	<h4 id="modal-title" class="modal-title"><?= sprintf(gettext("Share %s with Users"),Post::GetCustomName(true))?>
			</h4>

                <button aria-label="<?= gettext("close");?>" type="button" id="btn_close" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body"> 
				<div class="container container-remove">
					<p style="text-align:center; color:green;display:none" id="showmessage"></p> 
					<p style="text-align:center; color:red;display:none" id="showerror"></p>

					<form class="form-horizontal" id="sharePost" method="post">
						<input type="hidden" id="posttoshare" name="postid" value="<?= $_COMPANY->encodeId($postid) ?>">

						<div class="row">
                            <div class="col-md-12">
                                <div class="progress_bar form-group hide" id="progress_bar">
                                    <p><?= sprintf(gettext('Sharing %1$s with <span id ="totalBulkRecored"></span> recipients. Please wait.'), Post::GetCustomName(false));?></p>
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-animated" id="prgress_bar" style="width:0%"></div>
                                    </div>
                                    <div class="text-center progress_status" aria-live="polite"></div>
                                </div>

                                <div class="mb-3 p-4 progress_done progress_bar hide">
                                    <strong><?= gettext("Sent");?> :</strong>
                                    <p class="pl-3" id="inviteSent" style="color: green;"></p>
                                    <p class="pl-3" id="againSent" style="color: darkgreen"></p>
                                    <strong><?= gettext("Failed");?>: </strong>
                                    <p class="pl-3" style="color: red;" id="inviteFailed"></p>
                                    <div class="co-md-12 text-center pt-3">
                                        <button id="close_show_progress_btn" type="button" class="btn btn-affinity hide" onclick="closeEventInvitesStats()"><?= gettext("Close");?></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                      	<div id="email_in_form" class="form-group">
							<label class="control-lable"><?= gettext("Emails");?></label>
							<div class="">
                                <textarea id="email_in" rows="5" class="form-control" name="email_in"  value="" placeholder="<?= sprintf(gettext('Enter up to %1$s emails'), MAX_POST_SHARE_EMAILS);?>" required></textarea>
							</div>
                        </div>

						<div class="form-group">
                            <div class="text-center">
								<button  tabindex="0" id="invitation-submit-button" type="button" onclick="sharePost();" class="confirm about-button" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('You cannot withdraw the invitation. Are you sure with your selection?');?></button>" ><?= gettext("Submit");?></button>
								<button  tabindex="0" type="button" class="about-button" data-dismiss="modal"><?= gettext("Close");?></button>
							</div>
						</div>
					</form>
				</div> <!-- end of div container -->
            </div>  <!-- end of div modal body -->
        </div> <!-- end of div modal content -->
	</div> <!-- end of div modal dialog -->
</div> 
<script>
$('#sharePostWithUser').on('shown.bs.modal', function () {
	$('#btn_close').trigger('focus')
});
</script>