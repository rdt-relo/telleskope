<div class="modal" id="booking_reschedule_model">
    <div class="modal-dialog modal-lg">
		<div class="modal-content">
		
			<!-- Modal Header -->
			<div class="modal-header">
				<h4 class="modal-title"><?= $pageTitle; ?></h4>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			
			<!-- Modal body -->
			<div class="modal-body">
			<?php
				include(__DIR__ . "/new_booking.template.php");
			?>
			</div>
		</div>
    </div>
</div>