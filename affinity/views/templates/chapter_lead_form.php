<div id="chapter_lead_form_modal" tabindex="-1" class="modal fade">
	<div aria-label="<?=$form_title?>" class="modal-dialog" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h2 class="modal-title" id="form_title"><?=$form_title?></h2>
				<button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">               
                <form class="form-horizontal" method="post" action="" id="chapter_lead_role_form">
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <input type="hidden" name="leadid"  value="<?=$_COMPANY->encodeId($leadid);?>">
                <div class="form-group">
                <p class="col-md-12 control-label">
                <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                </div>
                <?php if ($leadid ==0) { ?>
                    <div class="form-group">
                        <label class="col-md-3 control-label"><?=$_COMPANY->getAppCustomization()['chapter']['name-short']?><span style="color: #ff0000;">*</span></label>
                        <div class="col-md-8">
                            <select aria-label="<?= gettext('Select a chapter');?>" class="form-control" name="chapterid" id="chapterid" required>
                            <?php if (!empty($allchapter)){ ?>
                                    <option value=''><?= sprintf(gettext("Select a %s"),$_COMPANY->getAppCustomization()['chapter']['name-short']);?></option>
                            <?php	foreach ($allchapter as $chapter) { ?>
                                        <option  value="<?=$_COMPANY->encodeId($chapter['chapterid'])?>"  ><?= htmlspecialchars($chapter['chaptername']) ?></option>
                            <?php	} ?>
                            <?php }else{ ?>
                                        <option value=''>-- <?= gettext("No chapters available");?> --</option>
                            <?php } ?>
                            </select>
                        </div>
                    </div>
                <?php } else{ ?>
                    <input type="hidden" name="chapterid" id="chapterid" value="<?=$encChapterId;?>">
                <?php } ?>

                <div class="form-group">
                    <label class="col-md-3 control-label" for="user_search2"><?= gettext("User");?><span style="color: #ff0000;">*</span></label>
                    <div class="col-md-8">
                <?php if($leadid > 0){ ?>
                        <label class="control-label"><strong> <?= $edit[0]['firstname']; ?> <?= $edit[0]['lastname'];?> </strong></label>
                        <input type="hidden" id="user_search" name="userid" value="<?= $_COMPANY->encodeId($edit[0]['userid']); ?>">
                <?php } else { ?>
                        <input class="form-control" id="user_search2" autocomplete="off" value="<?= $leadid ? $edit[0]['firstname'].' '.$edit[0]['lastname']: '';?>" onkeyup="searchUsersToLeadChapter('<?=$encGroupId;?>','<?=$encChapterId;?>',this.value)" placeholder="<?= gettext("Search user");?>"  type="text" required <?= $leadid ? 'readonly' : '';?> >
                        <div id="show_dropdown"> </div>
                <?php } ?>	
						</div>
					</div>

					<div class="form-group">
						<label class="col-md-3 control-label"><?= gettext("Role");?><span style="color: #ff0000;">*</span></label>
						<div class="col-md-8">
							<select aria-label="<?= gettext('Role');?>" class="form-control" name="typeid" id="role_type" required>
							<?php if (count($grouplead_type)>0){ ?>
									<option <?php if($leadid > 0){ ?> disabled <?php } ?>  value=''><?= gettext("Select a Role");?></option>
							<?php	for($i=0;$i<count($grouplead_type);$i++){
                                        $sel= '';
                                      	if ($leadid && ($grouplead_type[$i]['typeid']==$edit[0]['grouplead_typeid']))
										    $sel='selected';
							?>
										<option  value="<?=$_COMPANY->encodeId($grouplead_type[$i]['typeid'])?>" <?= $sel; ?> ><?=  htmlspecialchars($grouplead_type[$i]['type']) ?></option>
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
						<input placeholder="<?= gettext("Role Title");?>" class="form-control" type="text" id="roletitle" value="<?= $edit ? htmlspecialchars($edit[0]['roletitle']) : '' ?>" name="roletitle">
						</div>
					</div>

					<p>&nbsp;</p>
					<div class="text-center">
						<button type="button" onclick="updateChapterLeadRole('<?=$encGroupId;?>')" class="btn btn-primary prevent-multi-clicks"><?= gettext("Submit");?></button>
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
    
    function showHideSelectRegion(v){
        $.ajax({
            url: 'ajax.php?checkGroupleadType='+v,
            type: "POST",
            processData: false,
            contentType: false,
            cache: false,
            success : function(data) {
                if (data == 3){
                    $("#select_region").show();
                } else{
                    $('select#sel1 option').removeAttr("selected")
                    $("#select_region").hide();
                }
            }
        });
    }

    $('#chapter_lead_form_modal').on('shown.bs.modal', function () {
        $('#btn_close').trigger('focus')
    });

    $('#chapter_lead_form_modal').on('hidden.bs.modal', function (e) {
		$('#lead_<?= $_COMPANY->encodeId($leadid); ?>').trigger('focus');
	})
</script>