<style>
    legend {
	font-size:1rem;
}
</style>
<div class="modal" id="eventSpeakerModalForm">
    <div aria-label="<?= $modalTitle; ?>" class="modal-dialog modal-l" aria-modal="true" role="dialog">
      <div class="modal-content">
  
        <!-- Modal Header -->
        <div class="modal-header">
          <h2 id="modal-title" class="modal-title"><?= $modalTitle; ?></h2>
          <button aria-label="<?= gettext('close');?>" id="btn_close" type="button" class="close" data-dismiss="modal" onclick="manageEventSpeakers('<?= $_COMPANY->encodeId($eventid);?>')">&times;</button>
        </div>
        <form id="eventSpeakerForm">
            <!-- Modal body -->
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <input type="hidden" id="speakerid" name="speakerid" value="<?=  ($speakerid && !$clone) ? $_COMPANY->encodeId($speakerid) : $_COMPANY->encodeId(0);?>">
                        <p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                        <div class="form-group mb-2 col-md-12">
                            <label for="speaker_name"><?= gettext("Speaker Name");?>: <span style="color: #ff0000;"> *</span></label>
                            <input type="text" class="form-control" id="speaker_name" name="speaker_name" value="<?= $speakerid ? htmlspecialchars($speaker['speaker_name']) : ''?>" placeholder=" <?= gettext("Speaker name");?>" aria-required="true">
                        </div>

                        <div class="form-group mb-2 col-md-12">
                            <label for="speaker_title"><?= gettext("Speaker Professional Title");?>: <span style="color: #ff0000;"> *</span></label>
                            <input type="text" class="form-control" id="speaker_title" name="speaker_title" placeholder="<?= gettext("Speaker title");?>" value="<?= $speakerid ? htmlspecialchars($speaker['speaker_title']) : ''?>" aria-required="true">
                        </div>

                        <div class="form-group mb-2 col-md-12">
                            <label for="expected_attendees"><?= gettext("Expected Number of Attendees");?>: <span style="color: #ff0000;"> *</span></label>
                            <input onkeydown="if(event.key==='.'){event.preventDefault();}"  oninput="event.target.value = event.target.value.replace(/[^0-9]*/g,'');" type="number" class="form-control" id="expected_attendees" name="expected_attendees" pattern="[0-9]" value="<?= $speakerid ? $speaker['expected_attendees'] : ''?>" placeholder=" eg. 10" aria-required="true">
                        </div>

                        <div class="form-group mb-2 col-md-12">
                            <label for="speaker_fee"><?= gettext("Speaker Fee");?>: <span style="color: #ff0000;"> *</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon1">$</span>
                                </div>
                                <input type="number" class="form-control" min="0" id="speaker_fee" name="speaker_fee" placeholder=" <?= gettext("Speaker fees");?>" value="<?= $speakerid ? htmlspecialchars($speaker['speaker_fee']) : ''?>" aria-required="true">
                            </div>
                        </div>
                        <div class="form-group mb-2 col-md-12">
                            <label for="usr"><?= gettext("Speech Length");?>: <span style="color: #ff0000;"> *</span></label>
                            <select aria-label="<?= gettext('Speech Length');?>" class="form-control" id="speech_length" name="speech_length" aria-required="true">
                            <?php for($i=1; $i<6;$i++){ ?>
                                <?php
                                    $sel = "";
                                    if(!empty($speakerid) && $speaker['speech_length'] == ($i*15)){
                                        $sel = "selected";
                                    }
                                ?>
                                <option value="<?= $i*15; ?>" <?= $sel; ?>><?= sprintf(gettext("%s minutes"),$i*15); ?> </option>
                            <?php } ?>
                            </select>
                        </div>
                        <div class="form-group mb-2 col-md-12">
                            <label for="speaker_bio"><?= gettext("Speaker Bio");?>:</label>
                            <textarea class="form-control" id="speaker_bio" name="speaker_bio" placeholder=" <?= gettext("Speaker bio");?>"><?= $speakerid ? htmlspecialchars($speaker['speaker_bio']) : ''?></textarea>
                        </div>
                        <div class="form-group mb-2 col-md-12">
                            <label for="other"><?= gettext("Other information");?>:</label>
                            <textarea class="form-control" id="other" name="other" placeholder="<?= gettext("Add all information to get approval (ie. internal locations, sharepoints where contracts or approvals are stored).");?>"><?= $speakerid ? htmlspecialchars($speaker['other']) : ''?></textarea>
                        </div>
                        
						<?php include(__DIR__ . '/../templates/event_custom_fields.template.php'); ?>
                    </div>
                </div>
            </div>
    
            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="form-group text-center">
                    <button type="button" onclick="addOrUpdateEventSpeaker('<?= $_COMPANY->encodeId($eventid);?>')" class="btn btn-affinity prevent-multi-clicks"><?= gettext("Submit");?></button>&emsp;<button type="button" class="btn btn-affinity" data-dismiss="modal" onclick="manageEventSpeakers('<?= $_COMPANY->encodeId($eventid);?>')"><?= gettext("Close");?></button>
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
<script>
$('#eventSpeakerModalForm').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus')
});
</script>