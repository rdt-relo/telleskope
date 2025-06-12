<?php
$isActionDisabledDuringApprovalProcess = $isActionDisabledDuringApprovalProcess ?? false;
$topictype ??= '';
?>


<?php
foreach ($custom_fields as $custom_field) {
    $current_values = empty($event_custom_fields)
        ? array()
        : array_filter($event_custom_fields, function ($value) use ($custom_field) { // Find matching field values if set
        return ($value['custom_field_id'] == $custom_field['custom_field_id']);
        });

    $display = '';
    if ( !$_COMPANY->getAppCustomization()['event']['custom_fields']['enable_visible_only_if_logic'] && !empty($custom_field['visible_if'])) {
        continue;
    }
    if (!empty($custom_field['visible_if'])) {
        $vi_custom_field_id = $custom_field['visible_if']['custom_field_id'];
        $vi_options = $custom_field['visible_if']['options'];
        if (!empty($event_custom_fields)) {
            $evt_options = Arr::SearchColumnReturnColumnVal($event_custom_fields,$vi_custom_field_id,'custom_field_id','value');
            if (!is_array($evt_options)){
                $evt_options = array($evt_options);
            }
            if ($vi_options != $evt_options) {
                $display = 'none';
            }
        } else {
            $display = 'none';
        }
    }


?>

    <div class="form-group" id="custom_field_container_<?= $custom_field['custom_field_id'] ?>" style="display:<?= $display; ?>" >

        <?php if ($custom_field['custom_fields_type'] != 2) { // This label is not for checkbox grouping - accessibility changes ?>
        <label id="customInputLabel" class="<?= $topictype !== 'REC' ? 'col-sm-12' : '' ?> control-lable"><?= htmlspecialchars($custom_field['custom_field_name'] ?? ''); ?><?php if($custom_field['is_required']){ ?><span style="color: #ff0000;"> *</span><?php } ?></label>
        <?php } ?>

        <div class="<?= $topictype !== 'REC' ? 'col-sm-12' : '' ?>">
            <?php if ($custom_field['custom_fields_type'] == 1) { // Single Value
                $current_value = empty($current_values) ? array() : array_column($current_values, 'value')[0];
            ?>
            <select aria-label="<?= htmlspecialchars($custom_field['custom_field_name'] ?? ''); ?>" class="form-control" <?= $custom_field['is_required'] ? 'required' : '' ?>
                    id="custom_field_<?= $custom_field['custom_field_id'] ?>"
                    name="custom_field_<?= $custom_field['custom_field_id'] ?>[]"
                    onchange="showHideSelectNote(<?= $custom_field['custom_field_id'] ?>,this)"
                    <?= $isActionDisabledDuringApprovalProcess ? 'disabled' : ''; ?>
                    >
                <option data-note="" value=""><?= gettext("Select an option");?></option>
                <?php foreach ($custom_field['options'] as $option) {
                    $selected = (in_array($option['custom_field_option_id'],$current_value)) ? 'selected="selected"' : '';
                    ?>
                    <option data-note="<?= htmlspecialchars($option['custom_field_option_note']); ?>" value="<?= $option['custom_field_option_id']; ?>" <?= $selected; ?>><?= htmlspecialchars($option['custom_field_option'] ?? ''); ?></option>
                <?php } ?>
            </select>
            <small id="data_note_select_<?= $custom_field['custom_field_id'] ?>" class="form-text text-muted m-0 p-0" syle="display:none;"></small>

            <?php } else if ($custom_field['custom_fields_type'] == 2) { //Multiple Values
                $current_value = empty($current_values) ? array() : array_column($current_values, 'value')[0];
                if (!is_array($current_value )){
                    $current_value = array();
                }
            ?>
            <div id="custom_field_<?= $custom_field['custom_field_id']; ?>">

            <fieldset>
                <?php if ($custom_field['custom_fields_type'] == 2) { // This label tag is for checkbox grouping - accessibility changes ?>
                <legend style="font-size: 1rem;"><?= htmlspecialchars($custom_field['custom_field_name'] ?? ''); ?><?php if($custom_field['is_required']){ ?><span style="color: #ff0000;"> *</span><?php } ?></legend>
                <?php } ?>

                <?php foreach ($custom_field['options'] as $option) {
                    $checked = (in_array($option['custom_field_option_id'], $current_value)) ? 'checked' : '';
                    ?>
                    <div class="form-check">
                        <input id="custom_field_option_id_<?= $option['custom_field_option_id']; ?>" class="form-check-input" type="checkbox"
                               name="custom_field_<?= $custom_field['custom_field_id']; ?>[]"
                               data-note="<?= htmlspecialchars($option['custom_field_option_note']); ?>"
                               value="<?= $option['custom_field_option_id']; ?>" <?= $custom_field['is_required'] ? 'required' : '' ?> <?= $checked; ?>
                               onclick="showHideChecboxNote(<?= $option['custom_field_option_id']; ?>,this)"
                               <?= $isActionDisabledDuringApprovalProcess ? 'disabled' : ''; ?>
                            >
                        <label for="custom_field_option_id_<?= $option['custom_field_option_id']; ?>" class="form-check-label">
                            <?= htmlspecialchars($option['custom_field_option'] ?? '') ?>
                        </label>
                        <small id="data_note_checkbox_<?= $option['custom_field_option_id']; ?>" class="form-text text-muted m-0 p-0" style="display:none;"></small>
                        </div>
                <?php } ?>
                </fieldset>

            </div>

            <?php } else if ($custom_field['custom_fields_type'] == 3) { // Multi-line text box
                $current_value = empty($current_values) ? '' : array_column($current_values, 'value')[0];
                if (is_array($current_value )){
                    $current_value = "";
                }
                ?>
                <textarea aria-label="<?= htmlspecialchars($custom_field['custom_field_name'] ?? ''); ?>" class="form-control" name="custom_field_<?= $custom_field['custom_field_id']; ?>" id="custom_field_<?= $custom_field['custom_field_id']; ?>" name="custom_field_<?= $custom_field['custom_field_id']; ?>" rows="3" <?= $custom_field['is_required'] ? 'required' : '' ?> <?= $isActionDisabledDuringApprovalProcess ? 'disabled' : ''; ?> ><?= htmlspecialchars($current_value ?? '') ?></textarea>

            <?php } else if ($custom_field['custom_fields_type'] == 4) { // Single-line text box
                $current_value = empty($current_values) ? '' : array_column($current_values, 'value')[0];
                if (is_array($current_value )){
                    $current_value = "";
                }
                ?>
                <input
                  type="text"
                  aria-label="<?= htmlspecialchars($custom_field['custom_field_name'] ?? ''); ?>"
                  placeholder="<?= htmlspecialchars($custom_field['custom_field_name'] ?? ''); ?>"
                  class="form-control"
                  name="custom_field_<?= $custom_field['custom_field_id']; ?>"
                  id="custom_field_<?= $custom_field['custom_field_id']; ?>"
                  name="custom_field_<?= $custom_field['custom_field_id']; ?>"
                  <?= $custom_field['is_required'] ? 'required' : '' ?>
                  value="<?= htmlspecialchars($current_value ?? '') ?>"
                  <?= $isActionDisabledDuringApprovalProcess ? 'disabled' : ''; ?>
                >
            <?php } ?>
        <?php if($custom_field['custom_field_note']){ ?>
            <small style="color:#666666;">
                <?= $custom_field['custom_field_note']; ?>
            </small>
        <?php } ?>

        </div>
    </div>
    <?php if (!empty($custom_field['visible_if']) && ($parentCustomField = Event::GetEventCustomFieldDetail($custom_field['visible_if']['custom_field_id']))!=null){ ?>

        <script>
        <?php if ($parentCustomField['custom_fields_type'] == 1){ ?>
            $("#custom_field_<?= $parentCustomField['custom_field_id']; ?>").bind('change', function(){
                showHideCustomFieldByLogic(<?= $custom_field['custom_field_id']; ?>,<?= json_encode($custom_field['visible_if']['options'])?>,[this.value], <?= $custom_field['custom_fields_type']; ?>);
            });

        <?php } elseif ($parentCustomField['custom_fields_type'] == 2) { ?>

            $("#custom_field_<?= $parentCustomField['custom_field_id']; ?>").bind('click', function(){
               initCheckboxParameter(<?= $parentCustomField['custom_field_id']; ?>,<?= $custom_field['custom_field_id']; ?>,<?= json_encode($custom_field['visible_if']['options']); ?>, <?= $custom_field['custom_fields_type']; ?>);
            });

        <?php } else { ?>
            $("#custom_field_<?= $custom_field['visible_if']['custom_field_id']; ?>").bind('keyup', function(){
               showHideCustomFieldByLogic(<?= $custom_field['custom_field_id']; ?>,[<?= json_encode($custom_field['visible_if']['options'])?>],[this.value], <?= $custom_field['custom_fields_type']; ?>);
            });

        <?php } ?>
        </script>
    <?php } ?>

<?php } ?>

<script>

    function initCheckboxParameter(parentId,childId,logicValue,fieldType) {
        let checkedCheckBoxElements = $('#custom_field_'+parentId+' :checkbox:checked');
        let checked = [];
        $.each(checkedCheckBoxElements, function( index, element ) {
            checked.push($(element).val());
        });

        setTimeout(() => {
            showHideCustomFieldByLogic(childId,logicValue,checked,fieldType);
        }, 100);
    }

    function showHideCustomFieldByLogic(childId,logicValue,compareValue,fieldType) {
        let lv = logicValue.toString();
        let cv = compareValue.toString();

        if (lv == cv) {
            $("#custom_field_container_"+childId).show();
        } else {
            $("#custom_field_container_"+childId).hide();

            if (fieldType == 1) {
                $('#custom_field_'+childId).val('');
            } else if (fieldType == 2) {

                let checkBoxElements = $('#custom_field_'+childId+' :checkbox');
                $.each(checkBoxElements, function( index, element ) {
                    $(element).removeAttr('checked');
                });

            } else if ((fieldType == 3) || (fieldType == 4)) {
                $('#custom_field_'+childId).val('');
            }
        }
    }
    function showHideSelectNote(i,v) {
        if (v.value) {
            var note = $(v).find(':selected').attr('data-note')
            $("#data_note_select_"+i).text(note);
            $("#data_note_select_"+i).show();
        } else {
            $("#data_note_select_"+i).text('');
            $("#data_note_select_"+i).hide();
        }
    }

    function showHideChecboxNote(i,v){

        if($(v).is(':checked')) {
            var note = $(v).attr('data-note')
            $("#data_note_checkbox_"+i).html(note);
            $("#data_note_checkbox_"+i).show();
        } else {
            $("#data_note_checkbox_"+i).html('');
            $("#data_note_checkbox_"+i).hide();
        }
    }

</script>
