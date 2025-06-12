<?php

	// This file provides common inline functions to initialize chapters and channel selection box

	/**
	 * Dependencies
	 * 
	 * int $groupid
	 * array $channels
	 * array $chapters
	 * array $selectedChapterIds
	 * int  $selectedChannelId
	 * 
	 */
	$canCreateUpdateContentInChannel = $_USER->canCreateContentInGroupSomeChannel($groupid);
	$canCreateUpdateContentInChapter = $_USER->canCreateContentInGroupSomeChapter($groupid);
    $isGroupLead = $_USER->canCreateContentInGroup($groupid);

	if ($canCreateUpdateContentInChannel){
		$fieldsOrder = array('channel','chapter');
	} else {
		$fieldsOrder = array('chapter','channel');
	}
    $showChapterGlobal = $canCreateUpdateContentInChannel || ($isGroupLead);
    $showChannelGlobal = $canCreateUpdateContentInChapter || ($isGroupLead);

	/**
	 * Display Style
	 * $displayStyle = row: Will show title and select dropdown in new line
	 * $displayStyle = column: Will show title and select dropdown parallel in column
	 */

	$lebalCol = " col-md-2";
	$selectDropdownCol = " col-md-10";
	if (isset($displayStyle) && $displayStyle == 'row'){
		$lebalCol = "";
		$selectDropdownCol = "";
	}


?>

<?php if(($canCreateUpdateContentInChapter || $canCreateUpdateContentInChannel)){ ?>

	<?php foreach($fieldsOrder as $order){ ?>
		<?php if($_COMPANY->getAppCustomization()['channel']['enabled'] && !empty($channels) && $order=='channel'){ ?>
		<!-- Show channel dropdown -->
			<div id="channels_selection_div" class="form-group">
				
				<label class="control-lable <?= $lebalCol;?>"><?=$_COMPANY->getAppCustomization()['channel']['name-short']?></label>
				<div class="<?= $selectDropdownCol; ?>">
					<select tabindex="0" required class="form-control" id="channel_input" name="channelid" <?php if(!$isGroupLead && !$canCreateUpdateContentInChapter) { ?> onchange="updateChapterSelectionRestriction(1);" <?php } ?>>
                        <?php if ($showChannelGlobal) { ?>
						<option data-section="0" value="<?= $_COMPANY->encodeId(0)?>"><?= sprintf(gettext("%s Not Selected (Default All)"),$_COMPANY->getAppCustomization()['channel']['name-short']);?></option>					

                        <?php } ?>
						<?php foreach ($channels as $chn) {
                            $canManageChn = $_USER->canCreateContentInGroupChannel($groupid,$chn['channelid']);
						    if(!$canManageChn){
                                if ($canCreateUpdateContentInChapter) {
                                    $chn['channelname'] .= ' (R) ';
                                } else {
                                    continue;
                                }
                            }
                        ?>
						<option data-section="1" value="<?= $_COMPANY->encodeId($chn['channelid']); ?>" <?= $selectedChannelId==$chn['channelid'] ? ' selected' : ''; ?> >
							<?= htmlspecialchars($chn['channelname']); ?>
						</option>
						
						<?php }	?>
					</select>
					<small class="red" id="channelHelpText"></small>
				</div>
			</div>
		<?php } ?>

		<?php if($_COMPANY->getAppCustomization()['chapter']['enabled'] && !empty($chapters) && $order == 'chapter'){ ?>
		<!-- Show chapter dropdown -->
			<div id="chapters_selection_div" class="form-group form-group-scroll">
				<label class="control-lable <?= $lebalCol;?>"><?=$_COMPANY->getAppCustomization()['chapter']['name-short-plural']?></label>
					<div class="<?= $selectDropdownCol; ?>">
					<select tabindex="0" required class="form-control" id="chapter_input" name="chapters[]" multiple <?php if (!$isGroupLead && !$canCreateUpdateContentInChannel){ ?> onchange="updateChannelSelectionRestriction(1);" <?php } ?>>
						<?php foreach ($chapters as $key=>$chps) {	?>
							<?php // Remove empty optgroups
							foreach($chps as $k => $c) {
                                if (!$_USER->canCreateContentInGroupChapter($groupid,$c['regionids'], $c['chapterid'])) {
                                    if ($canCreateUpdateContentInChannel) {
                                        $chps[$k]['chaptername'] .= ' (R) ';
                                    } else {
                                        unset($chps[$k]);
                                    }
                                }
                            }
							if (empty($chps)) continue;
							?>
						<optgroup class="clickable-optgroup" label="<?=$key; ?>">
						<?php foreach($chps as $chp){ ?>
							<option value="<?= $_COMPANY->encodeId($chp['chapterid']); ?>" <?= in_array($chp['chapterid'],$selectedChapterIds) ? "selected" : ""; ?> >
								<?= htmlspecialchars($chp['chaptername']); ?>
							</option>
						<?php	}	?>
						</optgroup>
						<?php }	?>
					</select>
					<small class="red" id="chapterHelpText"></small>
				</div> 
			</div> 
		<?php } ?>
	<?php } ?>
<?php } ?>



<script>



	function updateChapterSelectionRestriction(trigerFrom){
		let selectedChannelSection = parseInt($('#channel_input').find(':selected').attr("data-section"));
		
		if (!selectedChannelSection){
			$('#chapter_input').prop('disabled', true);
			$("#chapter_input").multiselect("disable");
			$("#chapterHelpText").html("<?= sprintf(gettext('Note: %1$s selection disabled. You need to select a %2$s to enable it.'),$_COMPANY->getAppCustomization()['chapter']['name-short'],$_COMPANY->getAppCustomization()['channel']['name-short'])?>");
		} else {
			
			$('#chapter_input').prop('disabled', false);
			$("#chapterHelpText").html("");
			$("#chapter_input").multiselect("enable");

		}

	}

	function updateChannelSelectionRestriction(trigerFrom){
		let selectedChapterCounts = $('#chapter_input').val().length;
		let multiselectChannelInput = $("#channel_input");
		if (!selectedChapterCounts){
			$('#channel_input').prop('disabled', true);
			multiselectChannelInput.multiselect("disable");
			$("#channelHelpText").html("<?= sprintf(gettext('Note: %1$s selection disabled. You need to select a %2$s to enable it.'),$_COMPANY->getAppCustomization()['channel']['name-short'],$_COMPANY->getAppCustomization()['chapter']['name-short'])?>");
		} else {
			if (trigerFrom){
				$('#channel_input').val('');
				multiselectChannelInput.multiselect('refresh');
			}
			$('#channel_input').prop('disabled', false);
			$("#channelHelpText").html("");
			multiselectChannelInput.multiselect("enable");
		}
	}

	$( document ).ready(function() {
		$('#chapter_input').multiselect({
			nonSelectedText: "<?= $showChapterGlobal ? sprintf(gettext("%s Not Selected (Default All)"),$_COMPANY->getAppCustomization()['chapter']['name-short-plural']) : sprintf(gettext('Select %s'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural']); ?>",
			numberDisplayed: 3,
			nSelectedText: "<?= sprintf(gettext('%s selected'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
			disableIfEmpty: true,
			allSelectedText: "<?= sprintf(gettext('Multiple %s selected'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
			enableFiltering: true,
			maxHeight: 400,
			enableClickableOptGroups: true
		});
	
		$('#channel_input').multiselect({
			nonSelectedText: "<?= sprintf(gettext("%s Not Selected (Default All)"),$_COMPANY->getAppCustomization()['channel']['name-short-plural'])?>",
			numberDisplayed: 1,
			disableIfEmpty: true,
			enableFiltering: true,
			maxHeight: 400
		});

		<?php if(!$canCreateUpdateContentInChannel){ ?>
			$('.btn-group>ul>li.clickable-optgroup>a>label').click(function (e) {
				setTimeout(function(){
					updateChannelSelectionRestriction(1)
				}, 100);
			});
		<?php } ?>
		<?php if (!$isGroupLead && !$canCreateUpdateContentInChapter){ // Disable Chapter selection by detault ?>
		updateChapterSelectionRestriction(0);
		<?php } elseif (!$isGroupLead && !$canCreateUpdateContentInChannel){ // Disable Channel selection by detault ?>
		updateChannelSelectionRestriction(0);
		<?php } ?>

		// Handle bootstrap multiselct dropdown close by tab press
		$('.multiselect-container').keyup(function(e) { 
			if (e.key == 'Tab') {
				$(this).click();
			}
		});
	});
</script>
