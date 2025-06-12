<div class="col-12 form-group-emphasis">
    <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext('Event Contributors');?></h5>
	<div class="form-group">
		<label class="control-lable col-sm-12" for="event_contributors"><?= gettext('Search Contributors');?></label>
		<div class="col-sm-12">
			<select id="event_contributors" name="event_contributors[]" multiple="multiple" style="width: 100%;">
			<?php
				
				if ($event) {
					$event_contributors = explode(',',$event->val('event_contributors')?:0);
					foreach($event_contributors as $id) {
						if ($id == $_USER->id()) { continue; }
						$u  = User::GetUser($id);
						if (!$u) { continue; }
			?>
						<option value="<?= $_COMPANY->encodeId($id); ?>" selected ><?= $u->getFullName(); ?> (<?= $u->val('email'); ?>)</option>
			<?php	
					}
				}
			?>
			</select>
		</div>
	</div>
</div>

<script>
	$(document).ready(function() {
		$('#event_contributors').select2({
			ajax: {
				url: 'ajax_events.php?search_event_contributors=<?= $_COMPANY->encodeId($eventid); ?>',
				dataType: 'json',
				delay: 20,
				data: function (params) {
					return {
						keyword: params.term // search term
					};
				},
				processResults: function (data) {
					return {
						results: data.items // array of items
					};
				},
				cache: true,
				error:function(jqXHR, textStatus, errorThrown) {
					if (textStatus === 'abort') { 
						// When multiple AJAX calls are in progress, the server may abort some requests.  
						// In such cases, we have a check to show an error message.  
						// To prevent displaying an error for aborted requests, we set this flag.  
						jqXHR.skip_default_error_handler = true;
					} else {
						console.error('Error fetching data:', textStatus, errorThrown);
					}
                }
			},
			minimumInputLength: 3, // minimum characters to trigger search
			placeholder: '<?= gettext("Please enter at least 3 characters to search for users."); ?>', // Placeholder text
			// allowClear: true // Allow the user to clear selections
		});
		$(".select2-selection--multiple").attr('aria-label', "<?= gettext("Search Contributors"); ?>");
	});
</script>