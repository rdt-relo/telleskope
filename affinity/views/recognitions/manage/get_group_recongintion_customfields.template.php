
<div class="modal" id="configurationRecognitioinModal" tabindex="-1">
  <div aria-label="<?= $formTitle;?>" class="modal-dialog modal-xl modal-dialog-w1000" aria-modal="true" role="dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modal-title" class="modal-title"><?= $formTitle;?></h2>
        <button id="btn_close" type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="col-12">
          <form onsubmit="updateRecognitionSettings(event)">
            <input type="hidden" name="groupid" value="<?= $_COMPANY->encodeId($groupid) ?>">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="enable_user_view_recognition" name="enable_user_view_recognition" value="1"
                onchange="onToggleUserViewRecognition(event)"
                <?= $group->getRecognitionConfiguration()['enable_user_view_recognition'] ? 'checked' : '' ?>
              >
              <label class="custom-control-label" for="enable_user_view_recognition"><?= gettext('Enable User View') ?></label>
      </div>
            <div class="ml-4 p-2">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="enable_self_recognition" name="enable_self_recognition" value="1"
                  <?= $group->getRecognitionConfiguration()['enable_self_recognition'] ? 'checked' : '' ?>
                  <?= $group->getRecognitionConfiguration()['enable_user_view_recognition'] ? '' : 'disabled' ?>
                >
                <label class="custom-control-label" for="enable_self_recognition"><?= gettext('Enable Self Recognition') ?></label>
    </div>
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="enable_colleague_recognition" name="enable_colleague_recognition" value="1"
                  <?= $group->getRecognitionConfiguration()['enable_colleague_recognition'] ? 'checked' : '' ?>
                  <?= $group->getRecognitionConfiguration()['enable_user_view_recognition'] ? '' : 'disabled' ?>
                >
                <label class="custom-control-label" for="enable_colleague_recognition"><?= gettext('Enable Colleague Recognition') ?></label>
  </div>
</div>
            <button type="submit" class="btn btn-primary">Submit</button>
          </form>
</div>

      </div>
    </div>
  </div>
</div>

<script>
function updateRecognitionSettings(jsevent)
       {
  jsevent.preventDefault();
  var form = $(jsevent.target);
  var tskp_submit_btn = form.find('button[type="submit"]');
  var data = form.serialize();
       
      $.ajax({
        url: 'ajax_recognition.php?updateRecognitionSettings=1',
        type: "POST",
    data: data,
    tskp_submit_btn: tskp_submit_btn,
        success : function(data) {
          swal.fire({title: 'Success',text:'Updated successfully'});
        }
      });
}

function onToggleUserViewRecognition(jsevent)
{
  var is_checked = $(jsevent.target).is(':checked');
  if (is_checked) {
    $('#enable_self_recognition, #enable_colleague_recognition').prop('disabled', false);
  } else {
    $('#enable_self_recognition, #enable_colleague_recognition').prop('disabled', true);
  }
    }
</script>