<div id="touchpointConfigModal" class="modal fade" role="dialog">
    <div aria-label="<?= gettext('Touch Point Type Configuration')?>" class="modal-dialog">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title"><?= gettext('Touch Point Type Configuration')?></h2>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="touchpointtype"><?= gettext('Configure Touch Point Type');?> :</label>
                    <select class="form-control" name="touchpointtype" id="touchpointtype">
                        <?php if($_COMPANY->getAppCustomization()['teams']['team_events']['enabled']){ ?>
                        <option value="touchpoint" <?= $touchPointTypeConfig['type'] == 'touchpoint' ? 'selected' : '' ?>><?= gettext('Touch Points (with option to add events)');?></option>
                        <option value="touchpointevents" <?= $touchPointTypeConfig['type'] == 'touchpointevents' ? 'selected' : '' ?>><?= gettext('Touch Point Events Only');?></option>
                        <?php }  ?>
                        <option value="touchpointonly" <?= $touchPointTypeConfig['type'] == 'touchpointonly' ? 'selected' : '' ?>><?= gettext('Touch Points Only');?></option>
                    </select>
                </div>
                <div class="form-group form-check">
                    <input class="form-check-input" type="checkbox" id="show_copy_to_outlook" name="show_copy_to_outlook" <?= $touchPointTypeConfig['show_copy_to_outlook'] ? 'checked="checked"' : '' ?> value="true">
                    <label class="form-check-label" for="show_copy_to_outlook"><?= gettext('Show option to copy details (for Outlook).');?></label>
                </div>
                <?php if ($_COMPANY->getAppCustomization()['teams']['team_events']['enabled'] && $_COMPANY->getAppCustomization()['my_schedule']['enabled'] && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['NETWORKING']) { ?>
                <div class="form-group form-check" id="configure_enable_mentor_scheduler" style="display:none;" >
                    <input class="form-check-input" type="checkbox" id="enable_mentor_scheduler" name="enable_mentor_scheduler" <?= $touchPointTypeConfig['enable_mentor_scheduler'] ? 'checked="checked"' : '' ?> value="true">
                    <label class="form-check-label" for="enable_mentor_scheduler"><?= gettext('Enable Mentor Scheduler.');?></label>
                </div>
                <?php } ?>
                <?php if ($_COMPANY->getAppCustomization()['teams']['team_events']['enabled']) { ?>
                <div class="form-group form-check" id="configure_auto_approve_prposals" style="display:none;" >
                    <input class="form-check-input" type="checkbox" id="auto_approve_proposals" name="auto_approve_proposals" <?= $touchPointTypeConfig['auto_approve_proposals'] ? 'checked="checked"' : '' ?> value="true">
                    <label class="form-check-label" for="auto_approve_proposals"><?= gettext('Auto approve "New Time Proposal" from any participant.');?></label>
                    <small style="color: red;line-height: 1em;"><?= sprintf(gettext('Please do not use this feature if your %1$s can have %2$s with more than 5 members.'), $_COMPANY->getAppCustomization()['group']['name'], Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></small>
                </div>
                <?php } ?>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="updateTouchPointTypeConfig('<?= $_COMPANY->encodeId($groupid); ?>')"><?= gettext("Update")?></button>
            <button type="button" class="btn btn-primary" data-dismiss="modal"><?= gettext("Close")?></button>
        </div>
      </div>
    </div>
  </div>
<script>
$('#touchpointConfigModal').on('shown.bs.modal', function () {
    $('.close').trigger('focus');
});

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

</script>