<div id="eventsByDate" class="modal fade">
    <div aria-label="<?= sprintf(gettext("Note: There are %s other event(s) on this date"),count($events));?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><?= sprintf(gettext("Note: There are %s other event(s) on this date"),count($events));?></h2>
                <button type="button" id="btn_close" class="close" aria-label="<?= gettext("Close");?>" aria-hidden="true" data-dismiss="modal">&times;</button>                
            </div>
            <div class="modal-body">
				<div class="col-md-12">
         
                <?php
                foreach ($events as $ev) {
                    $event = Event::ConvertDBRecToEvent($ev);
                    if (!$event->isPrivateEvent()) {
                        $eventUrl = Url::GetZoneAwareUrlBase($_ZONE->id()) . 'eventview?id=' . $_COMPANY->encodeId($event->id());
                    }
                ?>
                <div class="row m-2">
                    <div class="col-md-6">
                        <span
                            <?= ($event->isDraft() || $event->isUnderReview() || $event->isAwaiting()) ? 'style="color:red;"' : '' ?>
                        >

                        <?php if ($event->isPrivateEvent()) { ?>
                        <strong><?=gettext("Private Event")?></strong>
                        <?php } else { ?>
                        <a href="<?=$eventUrl?>" target="_blank"><strong><?= $event->val('eventtitle'); ?></strong></a>
                        <?php } ?>
                        <br>
                        </span>
                        <small><?=gettext('in');?> <?= $event->getFormatedEventCollaboratedGroupsOrChapters() ?></small>
                    </div>
                    <div class="col-md-6">
                        <p class="font-col">
                            <?= $db->covertUTCtoLocalAdvance("l M j, Y","",  $event->val('start'),$_SESSION['timezone']); ?>
                            <br>
                            <?= gettext("From");?> <?= $db->covertUTCtoLocalAdvance("g:i a T","",  $event->val('start'),$_SESSION['timezone']); ?>
                            <?= gettext("to");?> <?= $db->covertUTCtoLocalAdvance("g:i a", "",  $event->val('end'),$_SESSION['timezone']); ?>
                        </p>    
                    </div>
                    </div>
                
                <?php } ?>
                </div>
            </div>
            <div class="modal-footer text-center" style='text-align:center !important;display: block;'>     
                <button type="button" class="btn btn-affinity" aria-hidden="true" data-dismiss="modal"><?= gettext("Close");?></button>
            </div>
        </div>
    </div>
</div>

<script>
$('#eventsByDate').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus')
});

$('#eventsByDate').on('hidden.bs.modal', function (e) {
    $('#start_date').focus();
})
</script>