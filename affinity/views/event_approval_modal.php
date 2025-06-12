<style>
  span.multiselect-selected-text{
    white-space: normal;
    word-wrap: break-word;
    display: block;
  }
</style>
<div class="modal" tabindex="-1" id="requestApprovalNoteModal">
    <div aria-label="<?=gettext("Approval Request Note")?>" class="modal-dialog modal-dialog-w700" role="document" aria-modal="true" role="dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title"><?=gettext("Approval Request Note")?></h2>
          <button id="btn_close" type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <form id="requestApprovalNote">
                <input type="hidden" id="topicTypeId" name="topicTypeId" value="<?= $enc_topictype_id ?>">
                <!-- Multiselect -->
                <?php
                if(count($stageRowsData['approver_userids'])>0) {
                    $pre_select_approver = '';
                    if ($approver_max_approvers_limit >= count($stageRowsData['approver_userids'])) {
                        $pre_select_approver = 'selected';
                    }
                ?>
                <div class="form-group">
                  <select name="selectApprovers" class="form-control multiselect hide-for-tab" multiple id="selectApprovers" style="display:none;">
                      <?php 	
                      foreach ($stageRowsData['approver_userids'] as $index => $approverId) {
                          $event_approver_data = User::GetUser($approverId);
                          if (!$event_approver_data) continue;
                          $approverRole = $stageRowsData['approver_role'][$index];
                        ?>
                        <option data-section="1" value="<?= $_COMPANY->encodeId($event_approver_data->id()); ?>" <?=$pre_select_approver?>>&ensp; <?= $event_approver_data->val('firstname') .' ' .$event_approver_data->val('lastname') ?> (<?= $event_approver_data->val('email'); ?>)<?= !empty($approverRole) ? " - $approverRole": ""; ?></option>
                      <?php	} ?>
                      
                  </select>
                </div>
                <?php	} ?>
                <div class="form-group">
                    <textarea aria-describedby="text_note" class="form-control" maxlength="1000" rows="4" id="requesterNote" name="requesterNote" placeholder="<?=gettext("If you have any approval request notes for the approver, enter them here.")?>"></textarea>
                    <small id="text_note" style="color:red"><?=gettext("Maximum 1000 characters allowed!")?></small>
                </div>

                <?= Approval::CreateEphemeralTopic()->setField('APPROVAL_TOPICTYPE', 'EVT')->renderAttachmentsComponent('v17') ?>
            </form>
        </div>
        <div class="text-center mb-5">
                <button type="button" onclick="requestEventApproval(event, '<?= $topicType ?>')" class="btn btn-primary"><?=gettext("Submit")?></button>
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=gettext("Close")
                ?></button>
        </div>
      </div>
    </div>
  </div>
<script>
$('#requestApprovalNoteModal').on('shown.bs.modal', function () {
  $('#btn_close').trigger('focus')
});
</script>
<script>
  $(document).ready(function() {
    $('#selectApprovers').multiselect({
      templates: {
          li: '<li><a href="javascript:void(0);"><label class="pl-2"></label></a></li>'
      },
      maxHeight: 400,
      nonSelectedText: '<?=gettext("Select Approver(s)")?>',
      enableFiltering: true,
      filterBehavior: 'text',
      enableCaseInsensitiveFiltering: true,
      onChange: function(option, checked){
        const selectedOptions = $("#selectApprovers option:selected").length;
        let maxApproversLimit_input = '<?= (int)$approver_max_approvers_limit; ?>';
        console.log(maxApproversLimit_input)
        // check for max limit
        if(selectedOptions > maxApproversLimit_input){
          Swal.fire({
            icon: 'warning',
            text: `You can select a maximum of ${maxApproversLimit_input} approvers`,
            confirmButtonText: 'Ok'
          });
          option.prop('selected', false);
          $('#selectApprovers').multiselect('refresh');
        }
      },
    });
});
</script>
<script>
// function to add "ESC key" exit on popover of tooltip
$(document).keyup(function (event) {
    if (event.which === 27) {
        $(".dropdown-menu").removeClass('show')
    }
});
// This Fix the text overflow.
$(document).ready(function() {
    var initialHeight = $('.multiselect-selected-text').height();
    
    // Create a MutationObserver
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                var newHeight = $('.multiselect-selected-text').height();
                var heightDifference = newHeight - initialHeight;
                $('.multiselect-container').css('top', '+=' + heightDifference + 'px');
                initialHeight = newHeight;
            }
        });
    });

    // Observe changes in the selected text element's content
    observer.observe($('.multiselect-selected-text')[0], {
        childList: true,
        subtree: true
    });
});

$(".multiselect-container li a").keyup(function (e) {
  var keyCode = e.keyCode || e.which; 
	if (keyCode == 9) { 
		$("#requesterNote").focus();
		$(".multiselect-container").removeClass('show');
	}
});

</script>