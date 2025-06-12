<!--modal for Region select -->
<div class="modal fade" id="select_region_modal">
        <div aria-label="<?= gettext("Select Region");?>" class="modal-dialog" aria-modal="true" role="dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title"><?= gettext("Select Region");?></h2>
                    <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>			  
                </div>
                <div class="modal-body" id='replace'>
                    <form class="form-horizontal" method="post" >  
                    <p class="mb-2"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>                    
                            <div class="form-group">
                            <label class="control-label"><?= gettext("Select Region");?><span style="color: #ff0000;">*</span></label>
                            <div class="">
                                <select aria-label="Select Region" class="form-control" name="selectedRegion" id="selectedRegion" required >
                                        <option value=''><?= gettext("Select a Region");?></option>
                                <?php	foreach ($data as $row) { ?>
                                            <option  value="<?=$_COMPANY->encodeId($row['regionid'])?>" ><?=  $row['region']?></option>
                                <?php	} ?>                
                                </select>
                            </div>
                        </div>				
                    </form>			 
                </div>
                <div class="modal-footer text-center" id="add-submit-Box">
                <button type="button" class="btn btn-affinity prevent-multi-clicks" onclick="submitRegion('<?=$_COMPANY->encodeId($groupid);?>')" ><?= gettext("Submit");?></button>
                <button type="button" id="chapter_close_button" class="btn btn-secondary" data-dismiss="modal"><?= gettext("Cancel");?></button>		  
                </div>
            </div>
        </div>
    </div>  
<script>
 

function submitRegion(g){
    $(document).off('focusin.modal');
    var rid = $("#selectedRegion").val();	
    if(rid == ""){        
        swal.fire({title: "<?=gettext("Error")?>", text: "<?=gettext("Please select a region")?>", allowOutsideClick:false});
        return;
    }
	$.ajax({
		url: 'ajax.php?openNewChapterModel=1',
		type: "GET",
        data: {'gid':g,'rid':rid},
        success : function(data) {
            closeAllActiveModal();
            $('#loadAnyModal').html(data);
            $('#new_chapter_model').modal('show');					
		}
	});
}

$('#select_region_modal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus')
});
</script>