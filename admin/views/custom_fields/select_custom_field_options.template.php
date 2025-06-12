<div class="mt-2">
	<label>has value</label>
<?php if($customField['custom_fields_type'] == 1){ ?>
	<select name="select_option[]" class="form-control" required>
        <option value="">Select an option</option>
        <?php foreach($options as $option){ 
			$sel = "";
			if ($parentCustomField && !empty($parentCustomField['visible_if'])) {
				if (in_array($option['custom_field_option_id'],$parentCustomField['visible_if']['options'])) {
					$sel = "selected";
				}
			}	
		?>
            <option value="<?= $_COMPANY->encodeId($option['custom_field_option_id']) ?>" <?= $sel; ?>><?= htmlspecialchars($option['custom_field_option']); ?></option>
        <?php } ?>
    </select>

<?php } elseif($customField['custom_fields_type'] == 2){ ?>
	<div class="checkbox-group required">
		<?php foreach($options as $option){ 
			$sel = "";
			$required = "required";
			if ($parentCustomField && !empty($parentCustomField['visible_if'])) {
				if (in_array($option['custom_field_option_id'],$parentCustomField['visible_if']['options'])) {
					$sel = "checked";
				}
				$required = "";
			}	
			
		?>
			<div class="form-check">
				<input class="form-check-input" onclick="updateRequired()" type="checkbox"	name="select_option[]" value="<?= $_COMPANY->encodeId($option['custom_field_option_id']) ?>" <?= $sel; ?> <?= $required; ?> >
				<label class="form-check-label">
					<?= htmlspecialchars($option['custom_field_option']); ?>
				</label>
			</div>
		<?php } ?>
		</div>
<?php } elseif ($customField['custom_fields_type'] == 3){ 
		$val = "";
		if ($parentCustomField && !empty($parentCustomField['visible_if'])) {
			$val = $parentCustomField['visible_if']['options'][0];
		}
?>
	<input type="text" name="select_option[]" class="form-control" value="<?= $val; ?>"required>
<?php } else { ?>
	<label>No option available!</label>
<?php } ?>
</div>


<script>
	function updateRequired(){
		let checkedCheckBoxElements = $('div.checkbox-group.required :checkbox:checked');
		let checkBoxElements = $('div.checkbox-group.required :checkbox');
		$.each(checkBoxElements, function( index, element ) {
			if (checkedCheckBoxElements.length>0){
				$(element).removeAttr('required')
			} else {
				$(element).attr('required', 'required');
			}
		});
	}
</script>