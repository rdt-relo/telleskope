<div id="joinRequestModal" class="modal fade" tabindex="-1">
    <div aria-label="<?= sprintf(gettext('%s Registration'), Team::GetTeamCustomMetaName($group->getTeamProgramType())); ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="modal-title" class="modal-title"><?= sprintf(gettext('%s Registration'), Team::GetTeamCustomMetaName($group->getTeamProgramType())); ?></h4>
                <button type="button" id="btn_close" class="close" aria-label="Close Modal Box" data-dismiss="modal">&times;</button>
            </div>
            <div id="modal_body">
                <div class="modal-body">
                    <div class="col-md-12 mb-5">
                        <div class="table-responsive">
                            <?php require __DIR__ . '/manage_join_requests_table.template.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$('#joinRequestModal').on('shown.bs.modal', function () {
    $(".modal").removeAttr('aria-modal');
    $('#btn_close').trigger('focus');
});
</script>