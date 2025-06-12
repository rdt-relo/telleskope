
<div class="modal" tabindex="-1" role="dialog" id="team_detal_modal">
  <div aria-label="<?= htmlspecialchars($teamObj->val('team_name'))?>" class="modal-dialog modal-dialog-w1000" role="document">
    <div class="modal-content">
      <div class="modal-body" id="teamFullDetail">
      <?php include(__DIR__ . "/get_team_basic_detail.template.php"); ?>
      </div>
      <div class="modal-footer">
        <button type="button" id="btn_close" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<script>
retainFocus('#team_detal_modal');
$('#team_detal_modal').on('shown.bs.modal', function () {
    $('#doutdBtn').trigger('focus');
});

$('.confirm').on('hidden.bs.popover', function () {    
		$(this).trigger('focus');
	})
</script>