<div id="new_volunteer_form_modal" class="modal fade" tabindex="-1">
	<div aria-label="<?=$form_title?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="form_title"><?=$form_title?></h4>
                <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">               
                <form class="form-horizontal" method="post" id="event_volunteer_form">
                    
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">

                    <?php if (!$volunteer && $event->getExternalVolunteerRoles()) { ?>
                      <div class="form-group">
                        <div class="list-group list-group-horizontal">
                          <button type="button" class="list-group-item active">
                            <?= gettext('Internal Volunteer') ?>
                          </button>
                          <button
                            type="button"
                            class="list-group-item"
                            onclick="window.tskp.event_volunteer.openAddOrEditExternalVolunteerByLeaderModal(event)"
                            data-eventid="<?= $_COMPANY->encodeId($event->id()) ?>"
                          >
                            <?= gettext('External Volunteer') ?>
                          </button>
                        </div>
                      </div>
                    <?php } ?>

                    <div class="form-group">

                        <?php if($volunteer){ ?>
                            <input type="hidden" id="user_search" name="userid" value="<?= $_COMPANY->encodeId($volunteer->id()); ?>">
                            <div class="col-md-12">
                            <label class="control-label"><strong> <?= $volunteer->val('firstname'); ?> <?= $volunteer->val('lastname');?> </strong></label>
                            </div>
                        <?php } else { ?>
                            <label for="user_search2" class="col-md-12 control-label"><?= gettext("Search Volunteer User");?></label>
                            <div class="col-md-12">
                            <input class="form-control" tabindex="0" id="user_search2" autocomplete="off" onkeyup="searchUsersForEventVolunteer(this.value)" placeholder="<?= gettext("Search users for event volunteer by name or email");?>"  type="text" required>
                            <div id="show_dropdown"> </div>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="form-group">
                        <label for="volunteertypeid" class="col-md-12 control-label"><?= gettext("Event Volunteer Types");?>&ensp;</label>
                        <div class="col-md-12">
                            <select class="form-control" tabindex="0" id="volunteertypeid"  name="volunteertypeid" required >
                            <?php if (!empty($eventVolunteerTypes)){ ?>
                                    <option disabled value=''><?= gettext("Select Event Volunteer Type");?></option>
                            <?php	foreach($eventVolunteerTypes as $type){ 
                                        $sel = '';
                                        if ($volunteer && $type['volunteertypeid'] == $volunteerTypeId ){
                                            $sel = 'selected';
                                        }
                            ?>
                                        <option  value="<?=$_COMPANY->encodeId($type['volunteertypeid'])?>" <?= $sel; ?> ><?= htmlspecialchars($event->getVolunteerTypeValue($type['volunteertypeid']))?></option>
                            <?php	} ?>
                            <?php }else{ ?>
                                        <option value=''>-- <?= gettext("No event volunteer type available");?> --</option>
                            <?php } ?>
                            </select>
                        </div>
                    </div>
                    
                    <p>&nbsp;</p>
                    <div class="form-group text-center">
                        <button type="button" cdata-dismiss="modal" class="btn btn-secondary" onclick="manageVolunteers('<?= $_COMPANY->encodeId($eventid)?>');"><?= gettext("Cancel");?></button>&ensp;
                        <button type="button" tabindex="0" onclick="addOrUpdateEventVolunteer('<?= $_COMPANY->encodeId($eventid)?>');" class="btn btn-primary prevent-multi-clicks"><?= gettext("Submit");?></button>
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

$('#new_volunteer_form_modal').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus')
});
</script>