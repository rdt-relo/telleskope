<div id="manage_event_expense_entries_model" class="modal fade">
    <div aria-label="<?=$modelTitle;?>" class="modal-dialog" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="modal-title" class="modal-title"><?=$modelTitle;?></h4>
                <button aria-label="<?= gettext("close");?>" type="button" id="btn_close" class="close" data-dismiss="modal">&times;</button>
                
            </div>
            <div class="modal-body">
                <div class="form-group" >
                    <label for="group_selection" class="col-12 control-lable"><?= sprintf(gettext('Select a %s to proceed with the event expense entry'),$_COMPANY->getAppCustomization()['group']["name-short"]);?></label>
                    <div class="col-12">
                        <select class="form-control " id="group_selection" name='groupids_selection' onchange="getSelectedGroupsAvailableChapters(this.value,'<?= $_COMPANY->encodeId($event->val('eventid')); ?>');" required>
                            <option value=""><?= gettext("Select an option")?></option>
                            <?php foreach($eventGroups as $g) {
                                $disabled = '';
                                $disabledMessage = gettext('Read-only');
                                if (!$_COMPANY->getAppCustomizationForZone($g->val('zoneid'))['budgets']['enabled']) {
                                    $disabled = 'disabled';
                                    $disabledMessage = gettext('Budget disabled');
                                } elseif (
                                     !$_USER->canManageBudgetGroupSomethingV1($g->val('groupid')) &&
                                     !$canManageEventBudget
                                 ) {
                                     $disabled = 'disabled';
                                } 

                            ?>
                                <option value="<?= $_COMPANY->encodeId($g->val('groupid')); ?>" <?= $disabled; ?>><?= $g->val('groupname'). ($disabled ? ' ['.$disabledMessage.']' : ''); ?></option>
                            <?php } ?>
                        </select>
                        <p class="alert-warning mt-2 p-3" id="infoMessage" style="display:none;"></p>
                        
                    </div>
                </div>
                <div id="chapter_selection_div">
                    <!-- Chapters dropdown will shown here -->
                </div>

            </div>
            <div class="modal-footer text-center">
            <button type="button" onclick="proceedToEventExpenseEntryForm('<?= $_COMPANY->encodeId($eventid); ?>');" class="btn btn-affinity"><?= gettext("Proceed");?></button> <button type="button" class="btn btn-affinity" data-dismiss="modal"><?= gettext("Close");?></button>
              </div>
        </div>
    </div>
</div>
<script>
	  function checkChapterSelected(v){
            if(v!='0'){
                $("#collaboration_selection").hide();
                $("#group_selection").val([]).change();
            } else {
                $("#collaboration_selection").show();
            }
        }

		$('#group_selection').multiselect('destroy');
		$('#group_selection').multiselect({
			nonSelectedText: "<?= sprintf(gettext('Select %s for event expense entry'),$_COMPANY->getAppCustomization()['group']['name-short'])?>",
			disableIfEmpty:true,
			enableFiltering: true,
			maxHeight:400,
			enableClickableOptGroups: true,
		});

		
    $('#manage_event_expense_entries_model').on('shown.bs.modal', function () {
        $('#btn_close').focus();
    });
    
    function getSelectedGroupsAvailableChapters(g,e) {
        if (g == '') {
            swal.fire({title: '<?= gettext("Error"); ?>', text: "<?= sprintf(gettext('Please select a %s for event expense entry'),$_COMPANY->getAppCustomization()['group']['name-short'])?>",allowOutsideClick:false});
            $('#chapter_selection_div').html('');
            return;
        }
        $.ajax({
            url: 'ajax_events?getSelectedGroupsAvailableChapters=1',
            data: {groupid:g,eventid:e},
            success: function (data) {
               $('#chapter_selection_div').html(data);
            }
        });
    }

    function proceedToEventExpenseEntryForm(e) {
        let group_selection = $("#group_selection").val();
        let chapter_selection = $("#chapter_selection").val();
        chapter_selection = (typeof chapter_selection !== 'undefined') ? chapter_selection : '';
        if (group_selection == '') {
            swal.fire({title: '<?= gettext("Error"); ?>', text: "<?= sprintf(gettext('Please select a %s for event expense entry'),$_COMPANY->getAppCustomization()['group']['name-short'])?>",allowOutsideClick:false});
            return;
        }
        $.ajax({
            url: 'ajax_events?proceedToEventExpenseEntryForm=1',
            data: {eventid:e, group_selection:group_selection, chapter_selection:chapter_selection},
            success: function (data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false});
                } catch(e) { 
                    $('#modal_over_modal').html(data);
                }
            }
        });
    }

    
</script>