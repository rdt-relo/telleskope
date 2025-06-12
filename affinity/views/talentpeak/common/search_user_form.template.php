<style>
    .word-wrap{
        word-wrap: break-word;
    }
</style>
<div id="search_user_modal" class="modal fade">
	<div  aria-label="<?= $form_title; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h2 class="modal-title" id="form_title"><?=$form_title?></h2>
				<button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">  
                
                <form id="serachUserForm">
                    <input type="hidden" id='teamid' name='teamid' value="<?= $_COMPANY->encodeId($teamid); ?>">
                    <input type="hidden" id='team_memberid' name='team_memberid' value="<?= $_COMPANY->encodeId($team_memberid); ?>">
                    <div class="form-group">
                        <label for="type"><?= gettext("Type");?><span style="color: #ff0000;">*</span></label>
                        <select class="form-control" name="type" id="type" <?php if(!$memberDetail ){?>onchange="showSuggestions(this.value),showMinMaxRequired(this);" <?php } ?>>
                            <option data-id='0' value="" <?= $memberDetail ? 'disabled' : '' ?>><?= gettext("Select role type");?></option>
                            <?php foreach(Team::GetProgramTeamRoles($groupid,1) as $role){ ?>
                                <option data-min="<?= $role['min_required']; ?>" data-max="<?= $role['max_allowed']; ?>" data-id="<?= $role['sys_team_role_type']; ?>" value="<?= $_COMPANY->encodeId($role['roleid']); ?>" <?= $memberDetail && $memberDetail['roleid'] == $role['roleid'] ? 'selected' : '';?> <?= $memberDetail &&  $memberDetail['roleid'] !== $role['roleid'] ? 'disabled' : '';?>><?= $role['type']; ?></option>
                            <?php } ?>
                        </select>
                        <small id="showMinMax" style="display:none"><?= gettext("Minimum Required")?> : <span id="minrequired">1</span>&emsp;|&emsp;<?= gettext("Maximum Allowed")?> : <span id="maxrequired">1</span></small>
                    </div>
                    <div class="clearfix"></div>
                    <div class="" id="suggetion_container" Style="display: none;">
                        <div class="" id="suggested_content">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="checkbox">
                            <label><?= gettext('Role Title');?></label>
                            <input placeholder="Role Title" class="form-control" type="text" id="" value="<?= $memberDetail ? htmlspecialchars($memberDetail['roletitle']) : ''; ?>" name="roletitle">
                           
                        </div>
                    </div>
                    <div class="form-group">
                    <?php if($memberDetail ){ ?>
                        <label ><?= gettext("Member");?></label>
                        <input type="hidden" name="userid" id='user_search' value="<?= $_COMPANY->encodeId($memberDetail['userid']); ?>">
                        <div class="form-group">
                            <p>
                                <strong>
                                    <?= $memberDetail['firstname'] .' '.$memberDetail['lastname']; ?>( <?= $memberDetail['email']?> )
                                </strong>
                            </p>
                        </div>
                    <?php } else { ?>
                        <label ><?= gettext("Search user (existing members are excluded from search)");?><span style="color: #ff0000;">*</span></label>
                        <div class="checkbox">
                            <label><input type="checkbox" id="searchAllusers" value="1"> <small><?= gettext("Search all users (if unchecked only users who have requested to join for any role are searched)"); ?></small></label>
                        </div>
                        <input class="form-control" autocomplete="off" id="searchBox" value="" onkeyup="searchUserForTeam('<?= $_COMPANY->encodeId($groupid); ?>',this.value)" placeholder="<?= gettext('Search user');?>"  type="text" required >
                        <div id="show_dropdown"> </div>
                    <?php } ?>
                    </div>

                </form>
			</div>
            <div class="modal-footer text-center" style='text-align:center !important;display: block;'>
                <button type="submit" class="btn btn-affinity prevent-multi-clicks" onclick="addUpdateTeamMember('<?= $_COMPANY->encodeId($groupid); ?>')" ><?= gettext("Submit");?></button>
                <button type="submit" class="btn btn-affinity" aria-label="<?= gettext('close');?>" data-dismiss="modal" ><?= gettext("Close");?></button>
            </div>
		</div>  
	</div>
</div>
		
<script>
    function showMinMaxRequired(v){
        var sysid = $("#type").find(':selected').data('id');
        if(sysid != 0 ){
            var min = $("#type").find(':selected').data('min');
            var max = $("#type").find(':selected').data('max');
            $('#minrequired').html(min);
            $('#maxrequired').html(max);
            $('#showMinMax').show();
        } else {
            $('#showMinMax').hide();
        }
    }

    function showSuggestions(v){
        var sysid = $("#type").find(':selected').data('id');
        if (v && (sysid ==2 || sysid ==3)){
            getSuggestedUserForTeam('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($teamid); ?>',v);
        } else {
            $("#suggetion_container").hide();
            $("#suggested_content").html('');
        }

        $("#show_dropdown").html('');
        var myDropDown=$("#user_search");
        var length = $('#user_search> option').length;
        myDropDown.attr('size',length);
    }

    function searchUserForTeam(g,k){
        delayAjax(function(){
            if(k.length >= 3){
                var teamid = $("#teamid").val();
                var type = $("#type").val();
                if(type){ 
                    var searchAllUsers = $('#searchAllusers').is(':checked');
                    $.ajax({
                        url: '../talentpeak/ajax_talentpeak.php?searchUserForTeam=1',
                        type: "GET",
                        data: {'groupid':g,'teamid':teamid,'roleid':type,'keyword':k,'searchAllUsers':searchAllUsers},
                        success: function(response){
                            $("#show_dropdown").html(response);
                            var myDropDown=$("#user_search");
                            var length = $('#user_search> option').length;
                            myDropDown.attr('size',length);
                        }
                    });
                } else {
                    swal.fire({title: 'Error',text:"Please select a type!"});
                }
            }
        }, 500 );
    }


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

    function selectSuggestedUser(i,n){
        var s = "";
        s += "<select class='form-control userdata' name='userid' onchange='closeDropdown()' id='user_search' required >";
		s +=  "<option value='"+i+"'>"+n+"</option>";
        s += "</select>";
        s += "<button type='button' class='btn btn-link' onclick=removeSelectedUser('"+i+"') ><?= gettext("Remove")?></button>";
        $("#show_dropdown").html(s);
        var myDropDown=$("#user_search");
        var length = $('#user_search> option').length;
        myDropDown.attr('size',length);
        
        $("#"+i).hide();

        if (!$(".suggestion").length){
            $("#suggested_content").html("<p class='pl-5'>-<?= gettext("No more suggestion")?>-</p>")
        }
    }
    function removeSelectedUser(i){
        $("#searchBox").val('');
        $("#show_dropdown").html('');
        $("#"+i).show();
    }

$('#search_user_modal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus')
});
</script>