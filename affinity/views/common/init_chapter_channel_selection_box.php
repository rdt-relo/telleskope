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

	 $passChapterChannelPermissionCheck = false;
	 if (isset($exemptPermissions) && $exemptPermissions == 1 && $_COMPANY->getAppCustomization()['event']['my_events']['enabled']){
		 $canCreateUpdateContentInChannel = true;
		 $canCreateUpdateContentInChapter = true;
		 $isGroupLead = true;
		 $passChapterChannelPermissionCheck = true;
	 } else{
		$canCreateUpdateContentInChannel = $_USER->canCreateContentInGroupSomeChannel($groupid);
		$canCreateUpdateContentInChapter = $_USER->canCreateContentInGroupSomeChapter($groupid);
		$isGroupLead = $_USER->canCreateContentInGroup($groupid);
	 }

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
	} elseif (isset($displayStyle) && $displayStyle == 'row12') {
		$lebalCol = " col-md-12";
		$selectDropdownCol = " col-md-12";
	}
 // check pages 
  $warn_if_all_chapters_are_selected ??= false;
?>

<?php if(($canCreateUpdateContentInChapter || $canCreateUpdateContentInChannel)){ ?>

	<?php foreach($fieldsOrder as $order){ ?>
		<?php if($_COMPANY->getAppCustomization()['channel']['enabled'] && !empty($channels) && $order=='channel'){ ?>
		<!-- Show channel dropdown -->
			<div id="channels_selection_div" class="form-group">
				
				<label class="control-lable <?= $lebalCol;?>"><?=$_COMPANY->getAppCustomization()['channel']['name-short']?></label>
				<div class="<?= $selectDropdownCol; ?>">
					<select tabindex="-1" required class="form-control" id="channel_input" name="channelid" <?php if(!$isGroupLead && !$canCreateUpdateContentInChapter) { ?> onchange="updateChapterSelectionRestriction(1);" <?php } ?>>
                        <?php if ($showChannelGlobal) { ?>
						<option data-section="0" value="<?= $_COMPANY->encodeId(0)?>"><?= sprintf(gettext("%s Not Selected (Default All)"),$_COMPANY->getAppCustomization()['channel']['name-short']);?></option>					

                        <?php } ?>
						<?php foreach ($channels as $chn) {
                            $canManageChn = $_USER->canCreateContentInGroupChannel($groupid,$chn['channelid']);
						    if(!$passChapterChannelPermissionCheck && !$canManageChn){
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
					<select tabindex="-1" required class="form-control" id="chapter_input" name="chapters[]" multiple <?php if (!$isGroupLead && !$canCreateUpdateContentInChannel){ ?> onchange="updateChannelSelectionRestriction(1);" <?php } ?>>
						<?php foreach ($chapters as $key=>$chps) {	?>
							<?php // Remove empty optgroups
							foreach($chps as $k => $c) {
                                if (!$passChapterChannelPermissionCheck && !$_USER->canCreateContentInGroupChapter($groupid,$c['regionids'], $c['chapterid'])) {
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

  <?php if (isset($use_and_chapter_connector)) { ?>
    <?php $use_and_chapter_connector = ((int) $use_and_chapter_connector === 1); ?>
    <div id="js_use_and_chapter_connector" class="form-group" style="display:none;">
      <?php if ($lebalCol && !isset($displayStyle)) { ?>
          <div class="<?= $lebalCol;?>">&nbsp;</div>
      <?php } ?>
      <div class="<?= $selectDropdownCol; ?> p-0">
          <div class="alert-warning <?=$lebalCol ? 'mx-3' : ''?> py-2 px-3 rounded-sm text-sm">
              <?= sprintf(gettext('By default, everyone in all selected %1$s and the selected %3$s will receive an email notification when you publish by email. If you only want to notify members who belong to both selected %2$s AND the selected %3$s, check the box here.'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'],$_COMPANY->getAppCustomization()['chapter']['name-short'],$_COMPANY->getAppCustomization()['channel']['name-short']) ?> &nbsp;&nbsp;
              <input type="checkbox" class="checkbox-inline" name="use_and_chapter_connector" disabled <?= $use_and_chapter_connector ? 'checked' : '' ?> data-initial-state="<?= (int) $use_and_chapter_connector ?>">
          </div>
      </div>
    </div>
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

  function showHideChapterChannelConnector()
  {
    var container = $('#js_use_and_chapter_connector');
    var checkbox = container.find('input[name="use_and_chapter_connector"]')
	
    var chapter_input = $('#chapter_input');
    var channel_input = $('#channel_input');

	if (
	chapter_input?.length &&
	chapter_input.closest('div.form-group').css('display') !== 'none' &&
	chapter_input.val()?.length &&
	channel_input?.length &&
	channel_input.closest('div.form-group').css('display') !== 'none' &&
	channel_input.val()?.length &&
	channel_input.val() !== '<?= $_COMPANY->encodeId(0) ?>'
	) {
		container.show();
		checkbox.prop('disabled', false);
		checkbox.prop('checked', checkbox.data('initial-state'));
		return;
		}
	
    container.hide();
    checkbox.prop('disabled', true);
  }
	
	$(document).ready(function(){
		$('#chapter_input').multiselect({
			nonSelectedText: "<?= $showChapterGlobal ? sprintf(gettext("%s Not Selected (Default All)"),$_COMPANY->getAppCustomization()['chapter']['name-short-plural']) : sprintf(gettext('Select %s'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural']); ?>",
			numberDisplayed: 3,
			nSelectedText: "<?= sprintf(gettext('%s selected'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
			disableIfEmpty: true,
			allSelectedText: "<?= sprintf(gettext('Multiple %s selected'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
			enableFiltering: true,
			maxHeight: 400,
	  enableCaseInsensitiveFiltering: true,
      enableClickableOptGroups: true,
      onChange: function () {
         showHideChapterChannelConnector();
		<?php if ($showChapterGlobal && isset($chapters) && count($chapters)>1 && !empty($warn_if_all_chapters_are_selected)) { ?>
		    checkAllSelected();
         <?php } ?>
      },
		});
	
		$('#channel_input').multiselect({
			nonSelectedText: "<?= sprintf(gettext("%s Not Selected (Default All)"),$_COMPANY->getAppCustomization()['channel']['name-short-plural'])?>",
			numberDisplayed: 1,
			disableIfEmpty: true,
			enableFiltering: true,
      maxHeight: 400,
	  enableCaseInsensitiveFiltering: true,
      onChange: function () {
        showHideChapterChannelConnector();
      },
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

		$('.multiselect').attr( 'tabindex', '0' );

       showHideChapterChannelConnector();

	});


	<?php if ($showChapterGlobal && isset($chapters) && count($chapters)>1 && !empty($warn_if_all_chapters_are_selected)) { ?>
			// Function to check if all options are selected
			function checkAllSelected() {
					var selectedOptions = $('#chapter_input option:selected').length;
					var totalOptions = $('#chapter_input option').length;
				if (selectedOptions === totalOptions) { 
					// show  message
			
					Swal.fire({
					title: "",
					html:"<?= sprintf(gettext('You have selected all %1$s and thus only members of the selected %1$s will receive email when you publish this content by email. There may be %4$s members who aren\'t in any %1$s and they won\'t get the email. If you wish to target all %4$s members, choose All %4$s members option below which will reset %3$s selection to its default state.'), $_COMPANY->getAppCustomization()["chapter"]["name-short-plural"],$_COMPANY->getAppCustomization()["group"]["name-short-plural"],$_COMPANY->getAppCustomization()["chapter"]["name-short"],$_COMPANY->getAppCustomization()["group"]["name-short"])?>" + "<br><br><strong><?= gettext('Choose your target audience:')?></strong>",
					showCancelButton: true,
					confirmButtonColor: "#3085d6",
					cancelButtonColor: "#d33",
					cancelButtonText: "<?= sprintf(gettext('Selected %1$s members'),$_COMPANY->getAppCustomization()["chapter"]["name-short"]);?>",
                    confirmButtonText: "<?= sprintf(gettext('All %1$s members'),$_COMPANY->getAppCustomization()["group"]["name-short"]);?>",
					reverseButtons: true,
					allowOutsideClick: false // Disable closing by clicking outside
					}).then((result) => {
					if (result.isConfirmed) {

				    	 $('#chapter_input').multiselect('deselectAll', false);
                         $('#chapter_input').multiselect('updateButtonText'); // This line ensures the button text is updated
						 $('#js_use_and_chapter_connector').hide();
					}
					});


				  }
				} 
		<?php } ?>
</script> 
