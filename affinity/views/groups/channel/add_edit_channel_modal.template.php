<link href="../vendor/js/select2-4.0.12/dist/css/select2.min.css" rel="stylesheet" />
<script src="../vendor/js/select2-4.0.12/dist/js/select2.min.js"></script>
<style>
	.btn-block {
		background: #418bbb9c;		
	}	
</style>

<div class="modal fade" id="new_channel_model">
	<div aria-label="<?= $pagetitle; ?>" class="modal-dialog" style="max-width:850px" aria-modal="true" role="dialog">
        <!-- Modal content-->
		<div class="modal-content">
                <div class="modal-header">
                    <h2><?= $pagetitle; ?>&nbsp;</h2>
                    <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                    
                    <div class="col-md-12">
                        <div class="container w4">
                            <div class="col-md-12 discu-pad">  
                                <div class="container">
                                    <form class="form-horizontal" id="newChannel" method="post" action="" >
                                        
                                        <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">

                                        <p class="mb-2 col-12"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                                        <div class="form-group">
                                            <label class="control-lable col-sm-2" for="channelname"><?= sprintf(gettext("%s Name"),$_COMPANY->getAppCustomization()['channel']['name-short']);?><span style="color: #ff0000;">*</span></label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="channelname" name="channelname" value="<?= $channelid > 0 ? $edit['channelname'] : ''; ?>" placeholder="<?= $_COMPANY->getAppCustomization()['channel']["name-short"]?> Name" maxlength="34" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-lable col-sm-2" for="colour"><?= gettext('Colour');?></label>
                                            <div  id="cp2" class="col-sm-10 input-group colorpicker-component ">
                                                <input aria-label="<?= gettext('Colour');?>" type="text"  id="colour" class="form-control" name="colour" value="<?= $channelid > 0 ? $edit['colour'] : '#000'; ?> " /><span tabindex="0" class="input-group-addon"><i aria-label="<?= gettext('Select colour');?>" role="button"></i></span>
                                            </div>
                                        </div> 
                                        <div class="form-group">
                                            <div class="col-sm-12 text-center">
                                                <button type="button" class="btn btn-affinity prevent-multi-clicks" onclick="add_update_channel('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($channelid);?>')"><?= gettext('Submit');?></button>
                                                <button type="button" id="channel_close_button" class="btn btn-secondary" data-dismiss="modal"><?= gettext('Cancel');?></button>	                  
                                                                                        
                                            </div>
                                        </div>
                                    </form>
                    </div>
                                  
            </div>
        </div>
    </div>
  </div>
 </div>
</div>

<script>
    $(function() { $('#cp2').colorpicker({format: 'rgb'});});
</script>

<script>
function add_update_channel(g,c){	
    $(document).off('focusin.modal');
    var chapterName = $("#channelname").val().trim();
    if(chapterName == ""){
        swal.fire({title: "<?=gettext("Error")?>", text: "<?=sprintf(gettext("%s name cannot be empty"),$_COMPANY->getAppCustomization()['channel']['name-short'])?>", allowOutsideClick:false});
        return;
    }
	var formdata = $('#newChannel')[0];
	var finaldata  = new FormData(formdata);
	finaldata.append("gid",g);
    finaldata.append("cid",c);		
	$.ajax({
		url: 'ajax.php?add_update_channel=1',
		type: 'POST',
		data: finaldata,		
		processData: false,
		contentType: false,
		cache: false,		
        success : function(data) { 
            manageDashboard(g);  
            closeAllActiveModal();     
            //$('#loadAnyModal').html(data);
            $('#new_channel_model').modal('show');	
			let jsonData = JSON.parse(data);
			swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false})
		}
	});
}

$('#new_channel_model').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus')
});

//On Enter Key...
 $(function(){
       $(".input-group-addon").keypress(function (e) {
           if (e.keyCode == 13) {
               $(this).trigger("click");
           }
        });
    });

$('#new_channel_model').on('hidden.bs.modal', function (e) {
    $('#channel_<?=$_COMPANY->encodeId($channelid);?>').trigger('focus');
})
</script>

