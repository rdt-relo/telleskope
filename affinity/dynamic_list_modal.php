<style>
	/* Will work only for FE add dynamic list form column */
	#addDynamicList .col-md-3 .form-control{
		height: 38px !important;
	}
</style>
<div id="addDynamicList" class="modal fade" tabindex="-1">
	<div aria-label="<?= gettext("Add Dynamic List");?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
		<div class="modal-content">
			<div class="modal-header">
                <div><h2 class="modal-title" id="form_title"><?= gettext("Add New Dynamic List");?></h2></div>
				<button id="btn_close" aria-label="close" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
                <?php
					// Flag for admin  
					$isAdminView = false;
 					include(__DIR__ . "/../common/add_new_dynamic_list.html"); ?>
            </div>
		</div>  
	</div>
</div>

<script>
$('#addDynamicList').on('shown.bs.modal', function () {
	$('#btn_close').trigger('focus')
});
</script>