<div id="manageRSVPModal" class="modal fade">
	<div aria-label="<?= gettext("Manage RSVP publish Options");?>" class="modal-dialog modal-md" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h2 class="modal-title" id="form_title"><?= gettext("Manage RSVP publish Options");?></h2>
			</div>
            <div class="modal-body">
            
                <div class="col-md-12 mt-3">
                    <form id="rsvpForm">
                        <div class="input-group col-12">
                            <!-- The options list for RSVPs -->
                            <select aria-label="<?= gettext("Manage RSVP Publish Options");?>" class="form-control" name="rsvp_display" id="rsvp-options">
                                <option value="<?= $_COMPANY->encodeId(0);?>" <?= $event->val('rsvp_display') == 0 ? 'selected' : ''; ?>><?= gettext("Do not show any RSVP info at all");?></option>
                                <option value="<?= $_COMPANY->encodeId(1);?>" <?= $event->val('rsvp_display') == 1 ? 'selected' : ''; ?>><?= gettext("Show only RSVP count");?></option>
                                <option value="<?= $_COMPANY->encodeId(2);?>" <?= $event->val('rsvp_display') == 2 ? 'selected' : ''; ?>><?= gettext("Show RSVP count + RSVP avatars");?></option>
                                <option value="<?= $_COMPANY->encodeId(3);?>" <?= $event->val('rsvp_display') == 3 ? 'selected' : ''; ?>><?= gettext("Show RSVP count + RSVP avatars + RSVP Table");?></option>
                            </select>

                        </div>
                        <div class="col-12 text-center mt-5">
                            <button class="btn btn-affinity" id="basic-addon2" type="button" onclick="submitRsvpListSetting('<?= $_COMPANY->encodeId($eventid);?>')"><?= gettext("Update");?></button>&ensp;
                            <button type="button" data-dismiss="modal" class="btn btn-affinity-gray"><?= gettext("Cancel");?></button>
                        </div>
                    </form>  
                </div> 
            </div>
        </div>
	</div>
</div>
<script>
function submitRsvpListSetting(eventid){
    $(document).off('focusin.modal');
    // set values of form
    var rsvpOption = $('#rsvp-options').val();

    $.ajax({
		url: 'ajax_events.php?submitRsvpListSetting',
        type: "POST",
        data: {rsvpOption, eventid},
        success: function(data){
            //console.log(data);
            swal.fire({title: '<?= gettext("Success");?>',text:'<?= gettext("Setting updated successfully");?>',allowOutsideClick:false}).then(function(result) {
					location.reload();
			});
        }
    });

};

$('#manageRSVPModal').on('shown.bs.modal', function () {
   $('#rsvp-options').trigger('focus')
});
</script>