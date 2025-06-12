<style>
    .list-group-item {
        font-size: 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
       
    }
    .list-group-item:nth-child(odd) {
        border: none;
    }
    .list-group-item strong {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .badge {
        font-size: 16px;
        padding: 8px 15px;
        border-radius: 20px;
    }
</style>
<div class="modal" tabindex="-1" role="dialog" id="viewScheduleStats">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= sprintf(gettext('%s Statistics'),$schedule->val('schedule_name')); ?></h5>
            </div>
            <div class="modal-body">           
                <div class="col-12">
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= gettext('Total Configured Count'); ?>:</span>
                                <span class="badge badge-primary badge-pill"><?= $totalSlotsCount; ?> <?= gettext("Slots"); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= gettext('Upcoming Configured Count'); ?>:</span>
                                <span class="badge badge-primary badge-pill"><?= $totalUpcomingSlotsCount; ?> <?= gettext("Slots"); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= gettext('Total Booked Count'); ?>:</span>
                                <span class="badge badge-success badge-pill"><?= $totalBookedCount; ?> <?= gettext("Slots"); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= gettext('Upcoming Booked Count'); ?>:</span>
                                <span class="badge badge-success badge-pill"><?= $totalUpcomingBookedCount; ?> <?= gettext("Slots"); ?></span>
                            </li>
                            <?php if (0) { ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= gettext('Total Available Count'); ?>:</span>
                                <span class="badge badge-warning badge-pill"><?= $totalAvailableCount; ?> <?= gettext("Slots"); ?></span>
                            </li>
                            <?php } ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= gettext('Upcoming Available Count'); ?>:</span>
                                <span class="badge badge-warning badge-pill"><?= $totalUpcomingAvailableCount; ?> <?= gettext("Slots"); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-affinity" id="btn_close" data-dismiss="modal"><?= gettext("Close");?></button>
            </div>
        </div>
    </div>
</div>
<script>
$('#viewScheduleStats').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
});
</script>
