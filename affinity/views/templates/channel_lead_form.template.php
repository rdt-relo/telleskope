<div id="channel_lead_form_modal" tabindex="-1" class="modal fade">
	<div aria-label="<?=$form_title?>" class="modal-dialog" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h2 class="modal-title" id="form_title"><?=$form_title?></h2>
				<button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">               
                <form class="form-horizontal" method="post" action="" id="channel_lead_role_form">
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <input type="hidden" name="leadid"  value="<?=$_COMPANY->encodeId($leadid);?>">
                <div class="form-group">
                <p class="col-md-12 control-label">
                <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                </div>
                <?php if ($leadid ==0) { ?>
                    <div class="form-group">
                        <label class="col-md-3 control-label"><?=$_COMPANY->getAppCustomization()['channel']['name-short']?><span style="color: #ff0000;">*</span></label>
                        <div class="col-md-8">
                            <select aria-label="<?= sprintf(gettext("Select a %s"),$_COMPANY->getAppCustomization()['channel']['name-short']);?>" class="form-control" name="channelid" id="channelid" required>
                            <?php if (!empty($allChannels)){ ?>
                                    <option value=''><?= sprintf(gettext("Select a %s"),$_COMPANY->getAppCustomization()['channel']['name-short']);?></option>
                            <?php	foreach ($allChannels as $channel) { ?>
                                        <option  value="<?=$_COMPANY->encodeId($channel['channelid'])?>"  ><?=  htmlspecialchars($channel['channelname'])?></option>
                            <?php	} ?>
                            <?php }else{ ?>
                                        <option value=''>-- <?= sprintf(gettext("No %s available"),$_COMPANY->getAppCustomization()['channel']['name-short']);?> --</option>
                            <?php } ?>
                            </select>
                        </div>
                    </div>
                <?php } else{ ?>
                    <input type="hidden" name="channelid" id="channelid" value="<?=$encChannelId;?>">
                <?php } ?>

                <div class="form-group">
                    <label class="col-md-3 control-label" for="user_search2"><?= gettext("User");?><span style="color: #ff0000;">*</span></label>
                    <div class="col-md-8">
                <?php if($leadid > 0){ ?>
                        <label class="control-label"><strong> <?= $edit[0]['firstname']; ?> <?= $edit[0]['lastname'];?> </strong></label>
                        <input type="hidden" id="user_search" name="userid" value="<?= $_COMPANY->encodeId($edit[0]['userid']); ?>">
                <?php } else { ?>
                        <input id="user_search2" class="form-control" autocomplete="off" value="<?= $leadid ? $edit[0]['firstname'].' '.$edit[0]['lastname']: '';?>" onkeyup="searchUsersToLeadChannel('<?=$encGroupId;?>','<?=$encChannelId;?>',this.value)" placeholder="<?= gettext("Search user");?>"  type="text" required <?= $leadid ? 'readonly' : '';?> >
                        <div id="show_dropdown"> </div>
                <?php } ?>	
						</div>
					</div>

					<div class="form-group">
						<label class="col-md-3 control-label"><?= gettext("Role");?><span style="color: #ff0000;">*</span></label>
						<div class="col-md-8">
							<select aria-label="<?= gettext('Role');?>" class="form-control" name="typeid" id="role_type" required>
							<?php if (count($grouplead_type)>0){ ?>
									<option value=''><?= gettext("Select a Role");?></option>
							<?php	for($i=0;$i<count($grouplead_type);$i++){
                                        $sel= '';
                                      	if ($leadid && ($grouplead_type[$i]['typeid']==$edit[0]['grouplead_typeid']))
										    $sel='selected';
							?>
										<option  value="<?=$_COMPANY->encodeId($grouplead_type[$i]['typeid'])?>" <?= $sel; ?> ><?= htmlspecialchars($grouplead_type[$i]['type']) ?></option>
							<?php	} ?>
                            <?php }else{ ?>
										<option value=''>-- <?= gettext("No roles available");?> --</option>
                            <?php } ?>
							</select>
						</div>
					</div>

                    <div class="form-group">                        
						<label class="col-lg-3 control-label"><?= gettext("Role Title");?></label>
						<div class="col-md-8">
						<input placeholder="Role Title" class="form-control" type="text" id="roletitle" value="<?= $edit ? htmlspecialchars($edit[0]['roletitle']) : ''?>" name="roletitle">
						</div>
					</div>
                    
					<p>&nbsp;</p>
					<div class="text-center">
						<button type="button" onclick="updateChannelLeadRole('<?=$encGroupId;?>')" class="btn btn-primary prevent-multi-clicks"><?= gettext("Submit");?></button>
                        <button type="button" data-dismiss="modal" class="btn btn-affinity-gray"><?= gettext("Close");?></button>&nbsp;
					</div>
					
					
				</form>
			</div>
		</div>  
	</div>
</div>
		
<script>
    function closeDropdown(){
        var myDropDown=$("#user_search");
        var length = $('#user_search> option').length;
        myDropDown.attr('size',0);
    }

    function clearSelected(){
        var elements = document.getElementById("sel1").options;
    
        for(var i = 0; i < elements.length; i++){
          elements[i].selected = false;
        }
    }
  
    $('#channel_lead_form_modal').on('shown.bs.modal', function () {
        $('#btn_close').trigger('focus')
    });

    $('#channel_lead_form_modal').on('hidden.bs.modal', function (e) {
       $('#lead_<?=$_COMPANY->encodeId($leadid);?>').trigger('focus');		
	})
</script>