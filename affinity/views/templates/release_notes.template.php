<div id="releaseNotes" class="modal fade">
    <div aria-label="<?= $modalTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header text-center">
                <h4 class="modal-title"><?= $modalTitle; ?></h4>
            </div>
            <div class="modal-body">
                <div class="col-md-12">
                    <div id="accordion">
                <?php 
                    $i=0;
                    foreach($rows as $releaseNote){ ?>
                        <div class="card card-release-notes">
                          <div class="card-header">
                            <a class="card-link" data-toggle="collapse" href="#collapse<?= $i; ?>">
                              <?= $releaseNote['releasename']; ?>
                            </a>
                          </div>
                          <div id="collapse<?= $i; ?>" class="collapse <?= $i==0 ? 'show' : ''; ?>" data-parent="#accordion">
                            <div class="card-body">
                                <?= $releaseNote['notes']; ?>
                            </div>
                          </div>
                        </div>
                <?php
                    $i++;
                    } ?>

                    </div>
                </div>
            </div>
            
            <div class="modal-footer text-center">
                <span id="submit_btn"></span>
                <button type="button" id="btn_close" class="btn btn-affinity" data-dismiss="modal"><?= gettext("Close");?></button>
            </div>
        </div>          
    </div>
</div>

<script>
    $('#releaseNotes').on('shown.bs.modal', function () {
		$('#btn_close').trigger('focus');
	});
</script>