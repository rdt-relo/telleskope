<style>
    .word-wrap{
        word-wrap: break-word;
    }
   #serachUserForm .form-control:focus {
    border-color: #ced4da !important;
    box-shadow: none !important;
}
</style>
<div id="searchUserToInviteForTeam" class="modal fade">
	<div  aria-label="<?= $form_title; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h4 class="modal-title" id="form_title"><?=$form_title?></h4>
				<button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">  
                
                <form id="serachUserForm">
                    <div class="form-group">
                    
                        <label ><?= gettext("Search user to invite");?></label>
                        <input class="form-control" autocomplete="off" id="searchBox" value="" onkeyup="searchUsertoInviteForTeam('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($subjectRoleid); ?>',this.value)" placeholder="<?= gettext('Search user');?>"  type="text" required >
                        <div id="show_dropdown"> </div>
                    </div>

                </form>
			</div>
            <div class="modal-footer text-center" style='text-align:center !important;display: block;'>
                <button type="submit" class="btn btn-affinity prevent-multi-clicks" onclick="inviteUserForTeamByRole('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($userRoleid); ?>','<?= $_COMPANY->encodeId($subjectRoleid); ?>')" ><?= gettext("Invite User");?></button>
                <button type="submit" class="btn btn-affinity" aria-label="<?= gettext('close');?>" data-dismiss="modal" ><?= gettext("Close");?></button>
            </div>
		</div>  
	</div>
</div>
		
<script>
    function searchUsertoInviteForTeam(g,r,k){
        delayAjax(function(){
            if(k.length >= 3){
                $.ajax({
                    url: '../talentpeak/ajax_talentpeak.php?searchUsertoInviteForTeam=1',
                    type: "GET",
                    data: {'groupid':g,'roleid':r,'keyword':k},
                    success: function(response){
                        $("#show_dropdown").html(response);
                        var myDropDown=$("#receiver_id");
                        var length = $('#receiver_id> option').length;
                        myDropDown.attr('size',length);
                    }
                });
            }
        }, 500 );
    }


    function closeDropdown(){
        var myDropDown=$("#receiver_id");
        var length = $('#receiver_id> option').length;
        myDropDown.attr('size',0);
    }

    function inviteUserForTeamByRole(g,sid, rid){
        $(document).off('focusin.modal');
        let formdata = $('#serachUserForm')[0];
        let finaldata  = new FormData(formdata);
        finaldata.append("groupid",g);
        finaldata.append("sender_roleid",sid);
        finaldata.append("receiver_roleid",rid);
        
        if (!$("#receiver_id").val()){
            swal.fire({title: 'Error',text:'Please search a user',allowOutsideClick:false});
            return false;
        }
        $.ajax({
            url: './ajax_talentpeak.php?inviteUserForTeamByRole=1',
            type: 'POST',
            data: finaldata,
            processData: false,
            contentType: false,
            cache: false,
            success: function(data) {

                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false})
                    .then(function(result) {
                        if(jsonData.status == 1){
                            initDiscoverTeamMembers(g);
                            $('#searchUserToInviteForTeam').modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
                            $('#loadAnyModal').html('');
                        }
                    });
                } catch(e) {
                    // Nothing to do
                    swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false});
                }
            }
        });
    }

    $('#searchUserToInviteForTeam').on('shown.bs.modal', function () {
        $('#btn_close').trigger('focus');
    });
</script>