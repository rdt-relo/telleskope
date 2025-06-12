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
		<select class="form-control " id="collaborating_chapterids" multiple name='collaborating_chapterids[]' required onchange="checkPermissionAndMultizoneCollaboration('<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId($host_groupid); ?>');">
			<?php
				$alreadyCollaborating = array();
				$pendingCollaborating = array();
				if ($event){
					$alreadyCollaborating = explode(',', $event->val('chapterid'));
					$pendingCollaborating = explode(',', $event->val('collaborating_chapterids_pending'));
				}
				foreach($chaptersForCollaboration as $key => $row){
					$sel = '';
					$apaproved = false;

					if ($event && (in_array($row['chapterid'],$alreadyCollaborating))) {
						$sel = "selected";
						$apaproved = true;
					}

					if ($event && (in_array($row['chapterid'],$pendingCollaborating))) {
						$sel = "selected";
					}

					$approvalNeeded = "";
					if (
                        !$apaproved &&
                        !$_USER->canCreateContentInGroupChapterV2($row['groupid'],$row['regionids'], $row['chapterid'])
                    ){
                        if ($_COMPANY->getAppCustomization()['event']['collaborations']['auto_approve']) {
                            $approvalNeeded = ' (' . gettext('auto-approved') . ')';
                        } else {
                            $approvalNeeded = ' (' . gettext('needs approval') . ')';
                        }
					}
				?>
				<option value="<?= $_COMPANY->encodeId($row['chapterid']); ?>" <?= $sel; ?> ><?= $row['chaptername']; ?> [<?= $row['groupname']; ?>]<?= $approvalNeeded; ?></option>
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
		filterPlaceholder: "<?=gettext('Search ... (case sensitive) ')?>",
		nSelectedText  : "<?= sprintf(gettext('%1$s selected for %2$s collaboration'),$_COMPANY->getAppCustomization()['chapter']['name-short'],$_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
		disableIfEmpty:true,
		allSelectedText: "<?= sprintf(gettext('All %1$s selected for %1$s collaboration'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
		selectAllText: "<?= sprintf(gettext('Select all %1$s for %1$s collaboration'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
		includeSelectAllOption: true,
		enableFiltering: true,
		maxHeight:400,
		selectAllValue: 'multiselect-all',
		enableClickableOptGroups: true,
		afterSelect: function (i) {
		},
		afterDeselect: function (i) {
		}
	});

	$(document).ready(function() {
		<?php if( $event && (!empty($event->val('collaborating_groupids')) || !empty($event && $event->val('collaborating_groupids_pending')) )) { ?>
			checkPermissionAndMultizoneCollaboration('<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId($host_groupid); ?>');
		<?php } ?>
	});

</script>