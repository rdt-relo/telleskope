<!--
    Dependencies:
    $statsTitle
    $statsTotalRecoreds
    $statsTotalInProgress
    $statsTotalCompleted
    $statsTotalOverDues
--->

<style>
    .badge-light-new {
        background-color: #e9ecef !important;
    }
</style>
<small><?= $statsTitle; ?>: <?= $statsTotalRecoreds; ?></small>
<div class="progress mt-2" style="height: 11px;">
    <div class="progress-bar bg-primary small" role="progressbar" style="width: <?= $statsTotalInProgress; ?>%" aria-valuenow="<?= $statsTotalInProgress; ?>" aria-valuemin="0" aria-valuemax="100"><?= $statsTotalInProgress ? $statsTotalInProgress.'%' : ''; ?></div>
    <div class="progress-bar bg-success small" role="progressbar" style="width: <?= $statsTotalCompleted; ?>%" aria-valuenow="<?= $statsTotalCompleted; ?>" aria-valuemin="0" aria-valuemax="100"><?= $statsTotalCompleted ? $statsTotalCompleted.'%' : ''; ?></div>
</div>
<div class="m-0">
    <small><span class="badge badge-light-new px-2 py-1"> </span>&nbsp;<?= gettext('Not Started'); ?>&nbsp;<span class="badge badge-primary px-2 py-1"> </span>&nbsp;<?= gettext('In Progress'); ?>&nbsp;<span class="badge badge-success px-2 py-1"> </span>&nbsp;<?= gettext('Done'); ?></small>
</div>
<div class="progress mt-2" style="height: 10px;">
    <div class="progress-bar bg-danger small" role="progressbar" style="width: <?= $statsTotalOverDues; ?>%" aria-valuenow="<?= $statsTotalOverDues; ?>" aria-valuemin="0" aria-valuemax="100"><?= $statsTotalOverDues ? $statsTotalOverDues.'%' : ''; ?></div>
</div>
<div class="m-0">
    <small><span class="badge badge-danger px-2 py-1"> </span>&nbsp;<?= gettext('Overdue'); ?></small>
</div>