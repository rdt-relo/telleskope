<?php

	// This file provides common inline functions to initialize cross zone group chapter collaboration

	/**
	 * Display Style
	 * $displayStyle = row: Will show title and select dropdown in new line
	 * $displayStyle = column: Will show title and select dropdown parallel in column
	 */

	$lableCol = " col-md-2";
	$selectDropdownCol = " col-md-10";
	if (isset($displayStyle) && $displayStyle == 'row'){
		$lableCol = " col-md-12";
		$selectDropdownCol = " col-md-12";
	}
?>
<style>
	.multiselect-selected-text {
		white-space:initial;
	}
</style>
<div class="form-group" >
	<label for="collaborating_chapterids" class="<?= $lableCol;?> control-lable"><?= sprintf(gettext('Select %s for collaboration'),$_COMPANY->getAppCustomization()['chapter']["name-short-plural"]);?></label>
	<div class="<?= $selectDropdownCol; ?>">
    <?php if($chapterCollaborationError){ ?>
        <p><?= $chapterCollaborationError; ?></p>
    <?php } else{ ?>
		<select class="form-control " id="collaborating_chapterids" multiple name='collaborating_chapterids[]' required>
			<?php
				$alreadyCollaborating = array();
				if ($event){
					$alreadyCollaborating = explode(',', $event->val('chapterid'));
				}
				foreach($chaptersForCollaboration as $key => $row){
					$chapteridsArray = array_column($row,'chapterid');
					$ids = array();
					$idsEnc = array();
					$sel = '';
					foreach($chapteridsArray as $ch){
						$ids[] = $ch;
						$idsEnc[] = $_COMPANY->encodeId($ch);

						if ($sel =='' && $event && (in_array($ch,$alreadyCollaborating))) {
							$sel = "selected";
						}
					}
					$chapteridsString = implode(',',$idsEnc);
					$groupnames = implode(', ', array_column($row,'groupname'));
				?>
				<option value="<?= $chapteridsString; ?>" <?= $sel; ?> ><?= $key; ?> [<?= $groupnames; ?>]</option>
			<?php }  ?>
		</select>
    <?php } ?>
	</div>
</div>
<script>
	$('#collaborating_chapterids').multiselect('destroy');
	$('#collaborating_chapterids').multiselect({
		nonSelectedText: "<?= sprintf(gettext('Select %1$s for %1$s collaboration'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
		numberDisplayed: 3,
		filterPlaceholder: "<?=gettext('Search ...')?>",
		nSelectedText  : "<?= sprintf(gettext('%1$s selected for %2$s collaboration'),$_COMPANY->getAppCustomization()['chapter']['name-short'],$_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
		disableIfEmpty:true,
		allSelectedText: "<?= sprintf(gettext('All %1$s selected for %1$s collaboration'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
		selectAllText: "<?= sprintf(gettext('Select all %1$s for %1$s collaboration'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
		includeSelectAllOption: true,
		enableFiltering: true,
		maxHeight:400,
		selectAllValue: 'multiselect-all',
		enableClickableOptGroups: true,
		enableCaseInsensitiveFiltering: true,
		afterSelect: function (i) {
		},
		afterDeselect: function (i) {
		}
	});
</script>