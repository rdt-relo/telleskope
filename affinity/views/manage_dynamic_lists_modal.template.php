<div class="modal " tabindex="-1" role="dialog" id="dynamic_lists_modal">
    <div class="modal-dialog modal-xl modal-dialog-w1000" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title"><?= $modalTitle ?>:</h2>
          <button type="button" id="btn_close" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
				<div class="col-md-12">
					<div class="pull-right">
						<a aria-label="Add new Dynamic List" class="btn btn-primary mb-3" onclick="addNewDynamicList('<?=$_COMPANY->encodeId($groupid)?>')" href="javascript:void(0)"><?= gettext("+ New Dynamic List")?></a>
					</div>
					<?php 
					// Flag for admin panel table
					$isAdminView = false;
					include(__DIR__ . "/../../common/manage_dynamic_list_table.html"); ?>	
				</div>
            </div>
            <br>
        </div>
        <div class="text-center mb-5">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  <script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/datatables-2.1.8/datatables.min.js"></script>
<script>
		$(document).ready(function() {
			var dtable = 	$('#manage_dynamic_lists').DataTable( {
				stateSave: true,
				bFilter: true,
				bInfo : false,
				bDestroy: true,
				order: [[ 0, "DESC" ]],
				"columnDefs": [
                    { targets: [2], orderable: false }
                ],
				language: {
					searchPlaceholder: "...",
					url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val("language")); ?>.json'
				},
			} );
		} );

	$('#dynamic_lists_modal').on('shown.bs.modal', function () {
		$('#btn_close').trigger('focus')
	});

	</script>