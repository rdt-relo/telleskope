<style>
    .hide{
        display:none;
    }
    .progress_bar{
        background-color: #efefef;
        margin: 5px 0px;
        padding: 15px;
    }
</style>
<div class="col-md-12 inner-page-container">
	<div class="col-md-12 my-3">
			<div class="mb-3 progress_bar hide" id="progress_bar_invite" role="alert">
				<p><?= gettext('Sending invitations to <span id ="totalBulkRecored"></span> email(s). Please wait.');?></p>
				<div class="progress">
					<div class="progress-bar progress-bar-animated" id="prgress_bar" style="width:0%"></div>
				</div>
				<div class="text-center progress_status" role="alert"></div>
			</div>

			<div class="mb-3 p-4 progress_done progress_bar hide">
                <strong><?= gettext("Skipped (existing members)");?> :</strong>
                <p class="pl-3" id="skipped_i" style="color: blue;"></p>
                <?php if ($_COMPANY->getAppCustomization()['group']['member_restrictions']) { ?>
                <strong><?= gettext("Skipped (do not meet membership requirements) ");?> :</strong>
                <p class="pl-3" id="restricted_i" style="color: blue;"></p>
                <?php } ?>
				<strong><?= gettext("Sent");?> :</strong>
				<p class="pl-3" id="inviteSent_i" style="color: green;"></p>
                <p class="pl-3" id="alreadySent_i" style="color: darkgreen"></p>
				<strong><?= gettext("Failed");?>: </strong>
				<p class="pl-3" style="color: red;" id="inviteFailed_i"></p>
				<div class="co-md-12 text-center pt-3">
					<button id="close_show_progress_btn" class="btn btn-affinity hide" onclick="inviteGroupMembers('<?= $_COMPANY->encodeId($groupid); ?>')"><?= gettext("Close");?></button>
				</div>
			</div>
			
			<div class="invite-group-join p-3" style="border:1px solid #ccc;">
				<form class="form-horizontal" id="form-invitation">
				<div class="form-group">
						<p class="col-md-12 control-lable" > <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
				</div>
					<div class="form-group">
						<label class="col-md-12 control-lable" ><?= gettext("Invite to join");?></label>
						<div class="col-md-12">
							<select aria-label="<?= gettext('Invite to join');?>" type="text" class="form-control" id="group_chapter_channel_id" name="group_chapter_channel_id" >
							<?php if ($_USER->canManageGroup($groupid)) { ?>
								<option data-section="<?= $_COMPANY->encodeId(0) ?>" value="<?= $_COMPANY->encodeId(0) ?>" ><?= $group->val('groupname')." ".$_COMPANY->getAppCustomization()['group']['name-short']; ?></option>
							<?php } ?>

							<?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
							<optgroup label="<?=$_COMPANY->getAppCustomization()['chapter']['name-short']?>">
								<?php if($group->val('chapter_assign_type') == 'auto'){  ?> <!--chapter auto assign -->
									<option data-section="<?= $_COMPANY->encodeId(1) ?>" value="<?= $_COMPANY->encodeId(0) ?>"  disabled ><?= sprintf(gettext("%s will be assigned automatically based on users office location"),$_COMPANY->getAppCustomization()['chapter']['name-short']); ?></option>
								<?php } else { ?>
								<?php for($i=0;$i<count($chapters);$i++){ ?>
								<?php if ($_USER->canManageGroupChapter($groupid,$chapters[$i]['regionids'],$chapters[$i]['chapterid'])) { ?>
								<option data-section="<?= $_COMPANY->encodeId(1) ?>" value="<?= $_COMPANY->encodeId($chapters[$i]['chapterid']) ?>"  >&emsp;<?= htmlspecialchars($chapters[$i]['chaptername']); ?></option>
								<?php } ?>
								<?php } ?>
							<?php } ?>
							</optgroup>

							<?php } ?>

							<?php if ($_COMPANY->getAppCustomization()['channel']['enabled']) { ?>
							<optgroup  label="<?=$_COMPANY->getAppCustomization()['channel']['name-short']?>">
							<?php for($i=0;$i<count($channels);$i++){ ?>
								<?php if ($_USER->canManageGroupChannel($groupid,$channels[$i]['channelid'])) { ?>
								<option  data-section="<?= $_COMPANY->encodeId(2) ?>" value="<?= $_COMPANY->encodeId($channels[$i]['channelid']) ?>">&emsp;<?= htmlspecialchars($channels[$i]['channelname']); ?></option>
								<?php } ?>
								<?php } ?>
							</optgroup>
							<?php } ?>

							</select>
						</div>
					</div>

					<div class="form-group">
						<label class="control-lable col-sm-12" for="inviteUserTextArea"><?= gettext("Email");?><span style="color: #ff0000;">*</span></label>
						<div class="col-sm-12">
                            <textarea id="inviteUserTextArea" rows="3" class="form-control" name="emails"  value="" placeholder="<?= gettext("Enter up to 1000 emails");?>" required></textarea>
						</div>  
					</div>  
					<div class="form-group">						
						<div class="col-sm-12 text-center">
						<button id="inviteBtn" onclick="processGroupMemberInvite('<?=$_COMPANY->encodeId($groupid);?>');" class="btn btn-affinity prevent-multi-clicks" type="button" name="invite"><?= gettext("Invite");?></button>
						</div>  
						
					</div>
				</form>
			</div>
			<div class="tab-content col-md-12 my-3 p-0">
				<div class="inner-page-title">
					<h5><?= gettext("Invites List"); ?>:</h5>
				</div>
				<div class="table-responsive pt-3" id="list-view">
					<table id="table-event" class="table table-hover display compact" summary="This table displays the list invited users of a group" width="100%">
						<thead>
							<tr> 
								<th width="25%" scope="col"><?= gettext("Email");?></th>
							<?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
								<th width="15%" scope="col"><?= $_COMPANY->getAppCustomization()['chapter']['name-short'];?></th>
							<?php } ?>
							<?php if ($_COMPANY->getAppCustomization()['channel']['enabled']) { ?>
								<th width="15%" scope="col"><?= $_COMPANY->getAppCustomization()['channel']['name-short'];?></th>
							<?php } ?>
								<th width="15%" scope="col"><?= gettext("Invited On");?></th>						

								<th width="10%" scope="col"><?= gettext("Status");?></th>
								<th width="20%" scope="col"><?= gettext("Action");?></th>
							</tr>
						</thead>
						<tbody>
						<?php for($i=0;$i<count($sentInvitation);$i++){?>
							<tr id="row_<?=$i?>">
								<td><?= $sentInvitation[$i]['email'];?></td>

								<?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
									<td><?= htmlspecialchars($sentInvitation[$i]['chaptername'] ?? '-'); ?></td>
								<?php } ?>
								<?php if ($_COMPANY->getAppCustomization()['channel']['enabled']) { ?>
									<td><?= $sentInvitation[$i]['channelname'] ?? '-'; ?></td>
								<?php } ?>							
								<td><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($sentInvitation[$i]['createdon'],true,true,false)?></td>
								<td>
									<?php if($sentInvitation[$i]['status'] == 1){?>
										<?= gettext("Pending");?>
									<?php }elseif($sentInvitation[$i]['status'] == 2){?>
										<?= gettext("Accepted");?>
									<?php }?>
								</td>
								<td>
									<?php 									
									if ($_USER->canManageContentInScopeCSV($groupid,$sentInvitation[$i]['chapterid'],$sentInvitation[$i]['channelid']) && $sentInvitation[$i]['status'] == 1) { ?>
										<button data-toggle="popover" aria-label="Withdraw" onclick="withdrawGroupMemberInvite('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($sentInvitation[$i]['memberinviteid']); ?>','row_<?=$i?>');" class="deluser btn btn-no-style" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to withdraw the invitation?");?>">
										<i class="fa fa-undo" aria-hidden="true"> <?= gettext("Withdraw");?> </i></button> 
									<?php } else { ?>
                                        -
                                    <?php } ?>
								</td>
							</tr>
						<?php }?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	<script>
		$("#li-invite").addClass("active2");		
	</script> 
	<script>
		$(document).ready(function() {
			var x = parseInt(localStorage.getItem("local_variable_for_table_pagination"));
			var dtable = $('#table-event').DataTable( {
				pageLength:x,
				"order": [],
				"bPaginate": true,
				"bInfo" : false,
				columnDefs: [
					{ targets: [-1], orderable: false }
				],
				'language': {
					"sZeroRecords": "<?= gettext('No data available in table');?>",
					url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
				},	
				
			});
			screenReadingTableFilterNotification('#table-event',dtable);
		});
		$('#table-event').on( 'length.dt', function ( e, settings, len ) {
    localStorage.setItem("local_variable_for_table_pagination", len);
} );

	

retainPopoverLastFocus(); //When Cancel the popover then retain the last focus.
</script>
 
	
