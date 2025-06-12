<div class="mt-2">
    <label>If field</label>
    <select name="if_field_selected" id="if_field_selected" class="form-control" onchange="showAvailableOptionsForLogic('<?= $_COMPANY->encodeId($custom_field_id)?>',this)" required >
        <option value="">Select a field</option>
        <?php foreach($customFields as $customField){ 
            if ($customField['custom_field_id'] == $custom_field_id){
                continue;
            }
			$sel = "";
			if ($parentCustomField && !empty($parentCustomField['visible_if'])) {
				if ($parentCustomField['visible_if']['custom_field_id'] == $customField['custom_field_id']) {
					$sel = "selected";
				}
			}
        ?>
            <option value="<?= $_COMPANY->encodeId($customField['custom_field_id']) ?>" <?= $sel; ?>><?= htmlspecialchars($customField['custom_field_name']); ?></option>
        <?php } ?>
    </select>
</div>
<div id="showAvailableOptions"></div>

<script>
	$(document).ready(function(){
		<?php if ($parentCustomField && !empty($parentCustomField['visible_if'])) { ?>
			$("#if_field_selected").trigger("change");
		<?php } ?>
	});
    function showAvailableOptionsForLogic(fid,e) {
        if (e.value !=''){
			$.ajax({
				url: 'ajax.php?showAvailableOptionsForLogic=1',
				type: 'GET',
				data: {'parent_custom_field_id':fid,'custom_field_id':e.value},
				success: function(data) {
					try {
						let jsonData = JSON.parse(data);
						swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false});
					} catch(e) { 
						$("#showAvailableOptions").html(data);
					}
				}
			});
		} else {
			$("#showAvailableOptions").html('');
		}
    }
</script>