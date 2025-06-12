<style>
    .card{
        box-shadow:none;
    }
    .highlighted {
        box-shadow: 0px 2px 8px 4px gold;
    }
    .popover{
        max-width:40%;      
    }
    #modal-title{
        float:left;
    }
</style>
<div class="modal" id="manageEventSpeakerModal">
    <div aria-label="<?= gettext("Manage Event Speakers");?>" class="modal-dialog modal-xl modal-dialog-w1000" aria-modal="true" role="dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <div>
                    <h2 id="modal-title" class="modal-title"><?= gettext("Manage Event Speakers");?></h2>
                    <?php if (!$event->hasEnded() && !$isActionDisabledDuringApprovalProcess) { ?>
                    <a role="button" class="plus-icon" aria-label="<?= gettext('Add Event Speakers');?>" href="javascript:void(0);" onclick="showSelectFromPastSpeakerDiv('<?=$_COMPANY->encodeId($event->id())?>')"><i class="fa fa-plus-circle" aria-hidden="true"></i></a>
                    <?php } ?>
                </div>
                
                <div class="pull-right row">
                <div style="text-right; margin-right:20px;">
                    <?php
                    $page_tags = 'manage_event_speaker';
                    ViewHelper::ShowTrainingVideoButton($page_tags);
                    ?>
			    </div>
                
                <button aria-label="<?= gettext("close");?>" id="btn_close" type="button" style="margin-right:20px; margin-top: -14px;" class="close" data-dismiss="modal">&times;</button>
                </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <?php if ($isActionDisabledDuringApprovalProcess) { ?>
                    <div class="col-md-12">
                        <div class="alert-warning m-3 p-3 text-small">
                        <?=sprintf(gettext('This event is currently in the approval process or has been approved. %1$s changes are not permitted. To make changes, request the event approver to deny the approval.'), gettext('Speaker'))?>
                        </div>
                    </div>
                    <?php } ?>

                    <div class="col-md-12" id="selectFromPastSpeakerDiv" style="display: none;">
                        <div class="form-group">
                            <label id="add_speaker_label"><?=gettext('Add a Speaker')?>: <span style="color: #ff0000;"> *</span></label>
                            <select id="selected_approved_speaker" name="selected_approved_speaker" onchange="openAddOrUpdateEventSpeakerModal('<?=$_COMPANY->encodeId($event->id())?>',this.value, 1)" class="form-control">
                                <option value=""><?=gettext('Choose one of the existing speakers or add a new speaker')?></option>
                                <option style="color: #0077b5;" value="<?=$_COMPANY->encodeId(0)?>"><?=gettext('New Speaker')?></option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-12" id="speaker_card">
                <?php if(count($eventSpeakers)){ ?>
                
                <?php foreach($eventSpeakers as $speaker){ ?>
                    <?php $event_speaker_obj = EventSpeaker::Hydrate($speaker['speakerid'], $speaker); ?>
                        <div class="col-md-4 speaker_card_container" id="<?= $_COMPANY->encodeId($speaker['speakerid']); ?>">
                            <div class="card pt-0 mb-3" style="width:100%">
                                <div class="row">
                                    <div class="col-md-12 text-center">
                                        <strong class="pt-3"><?= htmlspecialchars($speaker['speaker_name']); ?></strong>
                                        <br>
                                        <?= htmlspecialchars($speaker['speaker_title']); ?>
                                    <?php if($_COMPANY->getAppCustomization()['event']['speakers']['approvals']){ ?>
                                        <br>
                                        <small>
                                            <?= gettext("Approval Status");?>:
                                            <strong id="status<?= $_COMPANY->encodeId($speaker['speakerid']); ?>" class="
                                            <?php if ($speaker['approval_status'] == 1) { ?>bg-warning text-white
                                            <?php } elseif ($speaker['approval_status'] == 2) { ?>bg-warning text-white
                                            <?php } elseif ($speaker['approval_status'] == 3) { ?>bg-success text-white
                                            <?php } elseif ($speaker['approval_status'] == 4) { ?>bg-danger text-white
                                            <?php } else { ?>bg-dark text-white
                                            <?php } ?>
                                            mx-1 px-1
                                            ">
                                                <?= $approvelStatus[$speaker['approval_status']]?>
                                            </strong>
                                        </small>
                                        
                                        <?php if($speaker['approver_note']){ ?>
                                            <small data-html="true" title='Approver Note &nbsp;&nbsp; <button style="margin-top: -7px;" onclick=$(this).closest("div.popover").popover("hide") type="button" class="close" aria-hidden="true">&times;</button>'  data-container="body" data-toggle="popover" data-placement="top" data-content="<?= htmlspecialchars($speaker['approver_note']); ?>">&nbsp;<i class="fa fa-comment" aria-hidden="true"></i></small>
                                        <?php } ?>
                                    <?php } ?>
                                    </div>
                                </div>
                                <div class="col-md-12 p-0">
                                    <div style="text-align: center; border-top: 1.5px dashed rgb(185, 182, 182)">
                                        <strong style="display: inline-block; position: relative; top: -10px; background-color: white; padding: 0px 10px"></strong>
                                    </div> 
                                </div>

                                <small><?= gettext("Expected Attendees");?>:</small>
                                <strong><?= $speaker['expected_attendees']; ?></strong>
                                <small><?= gettext("Speech Length");?>:</small>
                                <strong><?= $speaker['speech_length']; ?> <?= gettext("minutes");?></strong>
                                <small><?= gettext("Speaker Fee");?>:</small>
                                <strong>$<?= htmlspecialchars($speaker['speaker_fee']); ?></strong>

                                <?= $event_speaker_obj->renderCustomFieldsComponent('v3') ?>

                                <button class="btn btn-link" title='<?= gettext("Other details");?>'  data-container="body" data-html="true"  data-toggle="popover" data-placement="bottom" data-content="<p><strong><?= gettext("Speaker Bio");?>:</strong></p><p><?= htmlspecialchars($speaker['speaker_bio']); ?></p><br/><p><strong><?= gettext("Other Information");?>:</strong></p><p class='pb-2'><?= htmlspecialchars($speaker['other']) ?></p>" ><?= gettext("Other details");?></button>
                                <div class="col-md-12 p-0">
                                    <div style="text-align: center; border-top: 1.5px dashed rgb(185, 182, 182)">
                                        <strong style="display: inline-block; position: relative; top: -10px; background-color: white; padding: 0px 10px"></strong>
                                    </div>
                                </div>
                                <?php if ((!$event->hasEnded() || !$event->isActive()) && !$isActionDisabledDuringApprovalProcess) { ?>
                                <div style="margin: 10px 0;">
                                    <a href="#" onclick="openAddOrUpdateEventSpeakerModal('<?= $_COMPANY->encodeId($eventid)?>','<?= $_COMPANY->encodeId($speaker['speakerid'])?>',0)" ><i class="fa fas fa-edit"></i> <?= gettext("Edit");?></a>
                                   &emsp;
                                    <a href="#" onclick="deleteEventSpeaker('<?= $_COMPANY->encodeId($eventid)?>','<?= $_COMPANY->encodeId($speaker['speakerid'])?>')" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to delete this speaker?");?>" ><i class="fa fa-trash"></i> <?= gettext("Delete");?></a>
                            <?php if($_COMPANY->getAppCustomization()['event']['speakers']['approvals']){ ?>
                                <?php if ($speaker['approval_status'] == 0){ ?>
                                    &emsp;
                                    <br>
                                    <br>
                                    <button id="btn<?= $_COMPANY->encodeId($speaker['speakerid']); ?>" onclick="updateEventSpeakerStatus('<?= $_COMPANY->encodeId($speaker['eventid'])?>','<?= $_COMPANY->encodeId($speaker['speakerid'])?>',1)" class="btn btn-affinity confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to send approval request?");?>" ><?= gettext("Request Approval");?></button>
                                <?php } elseif ($speaker['approval_status'] == 0) { ?>
                                <?php }  ?>
                            <?php } ?>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                        <div class="col-md-12 text-center p-3" id="noSpeaker">
                            <p><?= gettext("No event speaker added to this event.");?></p>
                            <br>
                            <?php if (!$event->isPublished() && !$isActionDisabledDuringApprovalProcess) { ?>
                            <a role="button" aria-label="<?= gettext('Add Event Speakers');?>" href="#" onclick="showSelectFromPastSpeakerDiv('<?=$_COMPANY->encodeId($event->id())?>')" >
                                <i class="fa fa-plus-circle" aria-hidden="true"></i> <?= gettext("Add");?>
                            </a>
                            <?php } ?>
                        </div>

                <?php } ?>
                       
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-affinity" data-dismiss="modal"><?= gettext("Close");?></button>
            </div>
        </div>
    </div>
</div>
<script>   
    $(document).ready(function(){
        $('[data-toggle="popover"]').popover({
            sanitize:false                    
        });         
    });    

    function updateEventSpeakerStatus(e,s,a){
        $.ajax({
            url: 'ajax_events.php?updateEventSpeakerStatus=1',
            type: "post",
            data: {eventid:e,speakerid:s,approval_status:a},
            success : function(data) {
               $("#status"+s).html(data);
               $("#btn"+s).hide();
                swal.fire({title: 'Success', text: '<?= addslashes(gettext("Approval request generated"));?>'}) .then(function(result) {
                    $('.plus-icon').focus();
                });
            }
        });

    }
    
</script>
<script>
     $(document).ready(function() {       
        $('.select2-selection--single').attr( {'aria-labelledby':"select2-selected_approved_speaker-container add_speaker_label"} );
     });

$('#manageEventSpeakerModal').on('shown.bs.modal', function () {
   $('.plus-icon').trigger('focus');
});
</script>