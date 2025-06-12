<div id="actionItemConfigModal" class="modal fade" role="dialog">
    <div aria-label="<?= gettext('Action Item Configuration')?>" class="modal-dialog">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title"><?= gettext('Action Item Configuration')?></h2>
          <button id="btn_close" type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="action_item_visibility"><?= gettext('Action Item Visibility');?> :</label>
                    <select class="form-control" name="action_item_visibility" id="action_item_visibility">
                    <?php foreach(Group::ACTION_ITEM_VISIBILITY_SETTING as $key =>$value){ 
                        $sel = '';
                        if ($value == $actionItemConfig['action_item_visibility']) {
                            $sel = 'selected';
                        }
                    ?>
                        <option value="<?= $key?>" <?= $sel; ?>><?= ucfirst(implode(' ',explode('_', $value)));?></option>
                    <?php } ?>
                        
                       
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="updateActionItemConfig('<?= $_COMPANY->encodeId($groupid); ?>')"><?= gettext("Update")?></button>
            <button type="button" class="btn btn-primary" data-dismiss="modal"><?= gettext("Close")?></button>
        </div>
      </div>
    </div>
  </div>
<script>
$('#actionItemConfigModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
});

retainFocus("#actionItemConfigModal");

$(document).ready(function() {
    $("#touchpointtype").on("change", handle_touchpointtype_selection);
    // Also on load.
    handle_touchpointtype_selection();
});

function handle_touchpointtype_selection() {
    var selectedValue = $("#touchpointtype").val();
    if (selectedValue === "touchpointonly") {
        $("#configure_enable_mentor_scheduler").hide();
        $("#configure_auto_approve_prposals").hide();
    } else {
        $("#configure_enable_mentor_scheduler").show();
        $("#configure_auto_approve_prposals").show();
    }
}

function updateActionItemConfig(g){
	let action_item_visibility = $("#action_item_visibility").val();
	$.ajax({
		url: 'ajax_talentpeak.php?updateActionItemConfig=1',
		type: "POST",
		data: {'groupid':g,'action_item_visibility':action_item_visibility},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						$("#loadAnyModal").html('');
						$('#actionItemConfigModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }

		}
	});
}


</script>