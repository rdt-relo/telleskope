<style>
#modal-title{
    float:left;
}
</style>
<div id="view_reminder_history" class="modal fade" tabindex="-1">
	<div aria-label="<?= gettext('Reminders') ?>" class="modal-dialog modal-dialog-w1000" aria-modal="true" role="dialog">
    	<div class="modal-content">				
			<div class="modal-header">
                <div>
                    <h4 id="modal-title" class="modal-title"><?= gettext("Reminders");?> </h4>
                    <a href="javascript:void(0);" class="plus-icon" onclick="sendReminderForm('<?= $_COMPANY->encodeId($eventid);?>');"><i aria-label="Send Reminder" class="fa fa-plus-circle my-3 ml-2"></i></a>
                </div>
                    
				<button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
        	</div>
        	<div class="modal-body">
		    <div class="row">		
						
                <div class="col-md-12">
                    <div class="table-responsive ">
                        <table  class="table display" id="reminderHistory" summary="This table display the event reminder history" width="100%">
                            <thead>
                                <tr>
                                    <th width="10%" scope="col"><?= gettext("Date");?></th>
                                    <th width="20%" scope="col"><?= gettext("Subject");?></th>
                                    <th width="15%" scope="col"><?= gettext("Sent To");?></th>
                                    <th width="40%" scope="col"><?= gettext("Reminder Message");?></th>
                                    <th width="10%" scope="col"><?= gettext("Sent By");?></th>

                                    <?php if (1) { ?> <th width="5%" scope="col"><?= gettext("Action");?></th> <?php } ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($reminderArray as $rem){  
                                                            
                            $remArray = explode(",",$rem['reminder_to']);                         
                                ?>
                                <tr>
                                <td><?=  $_USER->formatUTCDatetimeForDisplayInLocalTimezone($rem['publishdate'],true,true,true); ?><?= strtotime($rem['publishdate'] . ' UTC')>time() ? "<small style='color:blue;'>[Scheduled]</small>" : '';?></td>
                                <td><?= htmlspecialchars($rem['reminder_subject']); ?></td>
                                <td>
                                    
                                <?php                        

                                $arrayIntersect = array_intersect($remArray, array(-1,0,1,2,3,11,12,21,22,15,25));
                                foreach($arrayIntersect as $val){
                                    $val = intval($val);
                                    echo match ($val) {
                                        0 => '<p><input type="checkbox" checked disabled> ' . gettext("RSVP - Not Responded") . "</p>",
                                        1 => '<p><input type="checkbox" checked disabled> ' . gettext("RSVP - Yes") . "</p>",
                                        2 => '<p><input type="checkbox" checked disabled> ' . gettext("RSVP - Tentative") . "</p>",
                                        3 => '<p><input type="checkbox" checked disabled> ' . gettext("RSVP - No") . "</p>",
                                        11 => '<p><input type="checkbox" checked disabled> ' . gettext("RSVP - In Person Yes") . "</p>",
                                        12 => '<p><input type="checkbox" checked disabled> ' . gettext("RSVP - In Person Waitlist") . "</p>",
                                        21 => '<p><input type="checkbox" checked disabled> ' . gettext("RSVP - Online Yes") . "</p>",
                                        22 => '<p><input type="checkbox" checked disabled> ' . gettext("RSVP - Online Waitlist") . "</p>",
                                        -1 => '<p><input type="checkbox" checked disabled> ' . gettext("All Invited") . "</p>",
                                    };
                                }?>


                                </td>
                                <td  class="">
                                    <?= $rem['reminder_message'];?>
                                    <br>
                                    <span style="font-weight: 400; font-size: x-small;">
                                        <?= gettext("Include Event Detail")?>:
                                        <?= $rem['send_event_detail'] == 1 ? gettext("Yes") : gettext("No"); ?>
                                    </span>
                                </td>

                                <td>
                                    <?= User::GetUser($rem['createdby'])?->getFullName() ?? '-' ?>
                                </td>
                                <td>
                                <?php if (strtotime($rem['publishdate'] . 'UTC')>time() ) { ?>
                                    <a href="javascript:void(0);" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" onclick="deleteReminderHistory('<?= $_COMPANY->encodeId($rem['eventid']);?>','<?= $_COMPANY->encodeId($rem['reminderid']);?>')" title="<?= gettext('Are you sure you want to delete the reminder history and cancel the scheduled reminder?') ?>"><i class="fa fa-trash" aria-hidden="true"></i> <?= gettext('Cancel');?></a>
                                <?php } else { echo "-"; } ?>
                                </td>
                                </tr>                          
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>	
                </div>
        
            </div>
        	</div>

		</div>
	</div>
</div>

<script>
$(document).ready(function() {
    $(".confirm").popConfirm({content: ''});          
          $('#reminderHistory').DataTable( {
            info:     false,
            order: [[ 0, "desc" ]],
            language: {
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },
            columnDefs: [
              { targets: [2,3,4,5], orderable: false }
            ],
          } );
      } );       
      
$('#view_reminder_history').on('shown.bs.modal', function () {   
    $('.plus-icon').trigger('focus');    
});
</script>
