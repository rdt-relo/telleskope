<div class="modal" id="detailedView" tabindex="-1" role="dialog">
    <div aria-label="<?= $modalTitle; ?>" class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><?= sprintf(gettext('%s'), $modalTitle); ?></h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="col-lg-12">
                    <?php if(!empty($data)){ ?>

                        <div class="form-group">
                            <label ><?= gettext("Title")?></label>
                            <div class="form-control" style="height: auto !important;">
                            <?= sprintf(gettext('%s'), $data['title']); ?>
                            </div>
                        </div>
                    <?php if($section == 1){ 
                           $tat = $data['tat']??0; ?>
                        <div class="form-group">
                            <label ><?= gettext("Turnaround time in weekdays from start date of Team")?></label>
                            <div class="form-control" style="height: auto !important;">
                            <?= sprintf(gettext('%s Days'), $tat); ?>  
                            </div>
                        </div>
                       
                    <?php } elseif($section == 2){ ?>
                        <div class="form-group">
                            <label ><?= gettext("Assigned To")?></label>
                            <div class="form-control" style="height: auto !important;">
                                <?php foreach($teamRoles as $role){ 
                                    if ($data['assignedto'] == $role['roleid']){
                                       echo sprintf(gettext('%s'), $role['type']);
                                    }
                                } ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label ><?= gettext("Turnaround time in weekdays from start date of Team")?></label>
                            <div class="form-control" style="height: auto !important;">
                                <?php  $tat = $data['tat']??0; 
                                    echo sprintf(gettext('%s Days'), $tat);
                                ?>
                            </div>
                        </div>

                    <?php } ?>
                        <div class="form-group">
                            <label><?= gettext("Description")?></label>
                            <div class="form-control" style="height: auto !important;min-height: 200px;">
                                <?php 
                                $desc =  $data['description'] ? $data['description'] : '- No description -';
                                echo sprintf(gettext('%s'), $desc);                                
                                ?>
                            </div>
                        </div>
                    <?php } else{ ?>
                        <div class="col-lg-12 text-center">
                            <p> - <?= gettext("No detail found")?> - </p>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= gettext("Close")?></button>
            </div>
        </div>
    </div>
</div>

<script>
    // Fix for images going out of the Description field
    $(document).ready(function(){
        $('figure img').each(function (){
            var parentWidth = $(this).parent('figure').width();
            $(this).width(parentWidth);
        });
    });

    $('#detailedView').on('shown.bs.modal', function () {     					 
		$('.close').focus();
	})
</script>