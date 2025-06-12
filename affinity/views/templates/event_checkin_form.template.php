<style>
.about-button:focus { 
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 .1rem rgba(0,123,255,.25);
}
</style>
<div  tabindex="-1" id="nonRsvpform" class="modal fade">
	<div aria-label="<?= sprintf(gettext("%s check-in"),$event->val("eventtitle"));?>" class="modal-dialog" aria-modal="true" role="dialog">
		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">

			<h4 class="modal-title"><?= sprintf(gettext("%s check-in"),$event->val("eventtitle"));?></h4>
            <button id="btn__close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>

            <div class="alert alert-danger" id="error_message" style="display:none;"></div>
            <div class="alert alert-success" id="success_message" style="display:none;"></div>

			<div class="modal-body">
                <form class="form-horizontal" id="event_check_in_form" method="post">
						<input type="hidden" class="form-control" name="eventid" value="<?= $_COMPANY->encodeId($eventid); ?>" />
                        
                        <div class="form-group">
                            <label class="col-md-12 control-label"><?= gettext("Search User");?>&ensp;</label>
                            <div class="col-md-12">
                                <input class="form-control" name="search_users" id="user_search2" autocomplete="off" onkeyup="searchUsersForEventCheckin(this.value)" placeholder="<?= gettext("Search user");?>"  type="text" required>
                                <div id="show_dropdown"></div>
                            </div>
                        </div>
                        <div id="userDetail" style="display:none;">
                            <div class="form-group">
                                <label class="control-lable col-sm-12"><?= gettext("Email");?></label>
                                <div class="col-sm-12">
                                    <input type="text" name="email" id="email" required class="form-control" placeholder="<?= gettext("Email");?>" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-lable col-sm-12"><?= gettext("First Name");?></label>
                                <div class="col-sm-12">
                                    <input type="text" id="firstname" name="firstname" required class="form-control" placeholder="<?= gettext("First Name");?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-lable col-sm-12"><?= gettext("Last Name");?></label>
                                <div class="col-sm-12">
                                    <input type="text" name="lastname" required id="last_name" class="form-control" placeholder="<?= gettext("Last name");?>" />
                                </div>
                            </div>
                        </div>
					
						<div class="form-group" id="submit_checkin" style="display:none;">
							<div class="col-md-12 text-center">
								<button class="about-button" type="button" onclick="submitEventCheckinForm();" ><?= gettext("Check In");?></button>
							</div>
						</div>
						
						</form>
			</div>
		</div>
	</div>
<script>
$('#nonRsvpform').on('shown.bs.modal', function () {    
   $('#btn__close').trigger('focus');
});
$('#nonRsvpform').on('hidden.bs.modal', function (e) {
    $('#nonRSVPCheckinDropdownMenuLink').trigger('focus');
})
</script>