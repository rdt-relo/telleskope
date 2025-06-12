<div id="lead_form_modal" class="modal fade" tabindex="-1">
	<div aria-label="<?= $form_title; ?>" class="modal-dialog" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
				<h2 class="modal-title" id="form_title"><?=$form_title?></h2>
                <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">               
                <form class="form-horizontal" method="post" id="group_lead_role_form">
                    
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <input type="hidden" name="leadid" value="<?= $_COMPANY->encodeId($leadid); ?>">
                    <?php if($leadid == 0){ ?>
                        <p><?= gettext("Only currently enrolled members can be assigned leader privileges. To add a new leader, search for a user from the list, and assign the desired role and privileges. If the user you wish to add is not in the search list, then the user has not yet joined the platform yet and should be invited to join before being assigned a leader role.");?></p>
                        <br/>
                    <?php } ?>
                    <div class="form-group">
                        <p class="col-md-12 control-label">
                        <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label" for="user_search2"><?= gettext("User");?><span style="color: #ff0000;">*</span></label>
                        <div class="col-md-8">
                        <?php if($leadid > 0){ ?>
                            <label class="control-label" for="user_search"><strong> <?= $edit[0]['firstname']; ?> <?= $edit[0]['lastname'];?> </strong></label>
                            <input type="hidden" id="user_search" name="userid" value="<?= $_COMPANY->encodeId($edit[0]['userid']); ?>">
                        <?php } else { ?>
                            <input class="form-control" id="user_search2" autocomplete="off" onkeyup="searchUsers(this.value)" placeholder="<?= gettext("Search user");?>"  type="text" required>
                            <div id="show_dropdown"> </div>
                        <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label" for="typeid"><?= gettext("Role");?><span style="color: #ff0000;">*</span></label>
                        <div class="col-md-8">
                            <select aria-label="<?= gettext('Role');?>" class="form-control" id="role_type" onchange="showHideSelectRegion(this.value)" name="typeid" required >
                            <?php if ($grouplead_type){ ?>
                                    <option <?php if($leadid > 0){ ?>disabled <?php } ?>  value=''><?= gettext("Select a Role");?></option>
                            <?php	for($i=0;$i<count($grouplead_type);$i++){
                                        $sel= '';
                                        if ($grouplead_type[$i]['sys_leadtype'] === '4')
                                            continue; // Skip Chapter Leads
                                        if ($leadid >0 && ($grouplead_type[$i]['typeid']==$edit[0]['grouplead_typeid']))
                                            $sel='selected';
                            ?>
                                        <option  value="<?=$_COMPANY->encodeId($grouplead_type[$i]['typeid'])?>" <?= $sel; ?> ><?= htmlspecialchars($grouplead_type[$i]['type'])?></option>
                            <?php	} ?>
                            <?php }else{ ?>
                                        <option value=''>-- <?= gettext("No roles available");?> --</option>
                            <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">                        
						<label class="col-lg-3 control-label">Role Title</label>
						<div class="col-md-8">
						<input placeholder="Role Title" class="form-control" type="text" id="roletitle" value="<?= $edit ? htmlspecialchars($edit[0]['roletitle']) : '' ?>" name="roletitle">
						</div>
					</div>

                    <div class="form-group" style="display:<?= $leadid  && $checkLeadType == 3 ? '' : 'none'; ?>" id="select_region">
                        <label class="col-lg-3 control-label" for="sel1">Region&ensp;</label>
                        <div class="col-md-8">
                            <select aria-label="<?= gettext('Region');?>" class="form-control" name="regionids[]" multiple id="sel1">
                        <?php if ($region){ ?>
                            <?php for($i=0;$i<count($region);$i++){ ?>
                        <?php	if($leadid >0){
                                    if(in_array($region[$i]['regionid'],explode(',',$edit[0]['regionids']))){
                                        $sel = "selected";
                                    }else{
                                        $sel = "";
                                    }
                                }else{ 
                                    $sel = "";
                                } ?>
                                <option value="<?=$_COMPANY->encodeId($region[$i]['regionid'])?>" <?= $sel; ?>><?=$region[$i]['region'] ?></option>
                                
                        <?php	} ?>
                        <?php }else{ ?>
                                <option value="0"><?= gettext("No Region");?></option>
                        
                        <?php } ?>
                            </select>
                            <a href="#" onclick="clearSelected();"><?= gettext("Clear");?></a>
                        </div>
                    </div>
                    <p>&nbsp;</p>
                    <div class="form-group text-center">
                        <button type="button" onclick="updateGroupLeadRole('<?=$encGroupId;?>');" class="btn btn-primary prevent-multi-clicks"><?= gettext("Submit");?></button>
                        <button type="button" data-dismiss="modal" class="btn btn-affinity-gray"><?= gettext("Close");?></button>&ensp;
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
    
    function showHideSelectRegion(v){
        $("#role_type option:first").attr("disabled", "true");
        if ('' == v) {
            return false;
        }

        $.ajax({
            url: 'ajax.php?checkGroupleadType='+v,
            type: "POST",
            processData: false,
            contentType: false,
            cache: false,
            success: function(data) {
                if (data == 3){
                    $("#select_region").show();
                } else{
                    $('select#sel1 option').removeAttr("selected")
                    $("#select_region").hide();
                }
            }
        });
    }

    $('#lead_form_modal').on('shown.bs.modal', function () {
        $('#btn_close').trigger('focus')
    });
    $('#lead_form_modal').on('hidden.bs.modal', function (e) {
       $('#lead_<?= $_COMPANY->encodeId($leadid);?>').trigger('focus');		
	})

    retainFocus("#lead_form_modal");
</script>