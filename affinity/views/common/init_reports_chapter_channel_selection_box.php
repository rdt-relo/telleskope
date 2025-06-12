<?php
// This file provides common inline functions to initialize chapters and channel selection box for reports

	/**
	 * Dependencies
	 * int $groupid
	 */
    $chapters = Group::GetChapterListByRegionalHierachies($groupid);
    $channels= Group::GetChannelList($groupid);
    $isGroupLead = $_USER->canCreateContentInGroup($groupid);
	$isChapterLead = $_USER->canManageGroupSomeChapter($groupid);
	$isChannelLead = $_USER->canManageGroupSomeChannel($groupid);

    $showChapterGlobal = ($isGroupLead) ? true : false;
    $showChannelGlobal = ($isGroupLead) ? true : false;
	
	// Report specific conditions here
    $includeChapterleads = ((isset($reportType) && !empty($reportType)) ? (($reportType == 'chapterleads' || $reportType == 'members' || $reportType == 'budget' || $reportType == 'expense') ? true : false ) : true);
	$includeChannelleads = ((isset($reportType) && !empty($reportType)) ? (($reportType == 'channelleads' || $reportType == 'members') ? true : false ) : true);
?>
<!-- Note will be visible Only for   -->
 <?php
 if ($_COMPANY->getAppCustomization()['chapter']['enabled']) $scope_options[] = $_COMPANY->getAppCustomization()['chapter']['name'];
 if ($_COMPANY->getAppCustomization()['channel']['enabled']) $scope_options[] = $_COMPANY->getAppCustomization()['channel']['name'];
 $scope_string = isset($scope_options) && is_array($scope_options) ?  implode(' or ', $scope_options) : '';
 if($reportType !== 'chapterleads' && $reportType !== 'channelleads' && $isGroupLead){
 ?>
<div class="group_placeholder_text" style="font-size: 13px;"><?= sprintf(gettext('By default, the report will have data for whole %s . To select a %s scope please select any option from the below dropdown.'),$group->val('groupname'), $scope_string) ?></div>
<?php } ?>
<!-- Show the chapter and channels based if group has them -->
<?php if($_COMPANY->getAppCustomization()['chapter']['enabled'] && !empty($chapters) && $includeChapterleads && ($isGroupLead || $isChapterLead)){ ?>
		<!-- Show chapter dropdown -->
			<div id="chapters_selection_div" class="form-group form-group-scroll">
				<div>
					<select tabindex="-1" class="form-control" id="chapters_input" name="chapters[]" multiple>
							<?php foreach ($chapters as $key=>$chps) {	?>
								<?php // Remove empty optgroups
								foreach($chps as $k => $c) {
									if (
                                        (in_array($reportType,  ['budget','expense']) && !$_USER->canManageBudgetGroupChapter($groupid,$c['regionids'], $c['chapterid']))
                                        ||
                                        (!in_array($reportType,  ['budget','expense']) && !$_USER->canManageGroupChapter($groupid,$c['regionids'], $c['chapterid']))
                                    ) {
											unset($chps[$k]);
									}
								}
								if (empty($chps)) continue;
								?>
							<optgroup class="clickable-optgroup" label="<?=$key; ?>">
							<?php foreach($chps as $chp){ ?>
								<option value="<?= $_COMPANY->encodeId($chp['chapterid']); ?>" >
									<?= htmlspecialchars($chp['chaptername']); ?>
								</option>
							<?php	}	?>
							</optgroup>
							<?php }	?>
					</select>
				</div> 
			</div> 
		<?php } ?>


		<?php if($_COMPANY->getAppCustomization()['channel']['enabled'] && !empty($channels) && $includeChannelleads &&($isGroupLead || $isChannelLead)){ ?>
		<!-- Show channel dropdown -->
			<div id="channels_selection_div" class="form-group">
				
				<div>
					<select tabindex="-1" class="form-control" id="channel_input" name="channelid">
                        <?php if ($showChannelGlobal) { ?>
						<option data-section="0" value="<?= $_COMPANY->encodeId(0)?>"><?= sprintf(gettext("%s Not Selected (Default All)"),$_COMPANY->getAppCustomization()['channel']['name-short']);?></option>					
                        <?php } else { ?>
                        <option data-section="0" value="<?= $_COMPANY->encodeId(0)?>"><?= sprintf(gettext("Select a %s"),$_COMPANY->getAppCustomization()['channel']['name-short']);?></option>
                        <?php }  ?>
						<?php foreach ($channels as $chn) {
						    if($_USER->canManageGroupChannel($groupid,$chn['channelid'])){ ?>
						<option data-section="1" value="<?= $_COMPANY->encodeId($chn['channelid']); ?>">
							<?= htmlspecialchars($chn['channelname']); ?>
						</option>
                        <?php }
                            }
                        ?>
					</select>
				</div>
			</div>
			<?php }	?>

<script>
	$(document).ready(function(){
		$('#chapters_input').multiselect({
			nonSelectedText: "<?= $showChapterGlobal ? sprintf(gettext("%s Not Selected (Default All)"),$_COMPANY->getAppCustomization()['chapter']['name-short-plural']) : sprintf(gettext('Select %s'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural']); ?>",
			numberDisplayed: 3,
			nSelectedText: "<?= sprintf(gettext('%s selected'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
			disableIfEmpty: true,
			allSelectedText: "<?= sprintf(gettext('Multiple %s selected'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
			enableFiltering: false,
			maxHeight: 400,
			enableCaseInsensitiveFiltering: false,
			enableClickableOptGroups: true,
			onChange: function(checked, select) {
				$('#chapters_input').trigger('change');
            }
		});
	
		$('#channel_input').multiselect({
            nonSelectedText: "<?= $showChannelGlobal ? sprintf(gettext("%s Not Selected (Default All)"),$_COMPANY->getAppCustomization()['channel']['name-short']) : sprintf(gettext('Select %s'),$_COMPANY->getAppCustomization()['channel']['name-short']); ?>",
			numberDisplayed: 1,
			disableIfEmpty: true,
			enableFiltering: false,
			maxHeight: 400,
			enableCaseInsensitiveFiltering: false,
		});

	 	// Handle bootstrap multiselct dropdown close by tab press
		$('.multiselect-container').keyup(function(e) { 
			if (e.key == 'Tab') {
				$(this).click();
			}
		});
		$('.multiselect').attr( 'tabindex', '0' );
	});
</script> 
<script>
// Handle show/hide of chapter and channel dropdwon, ERG selected text.
$(document).ready(function(){
	function handleChapterInputChange(){
		if($(this).val().length > 0){
			$('#channels_selection_div').hide();
		}else{
			$('#channels_selection_div').show();
		}
		$('.group_placeholder_text').hide();
	}
	$('#chapters_input').on('change', handleChapterInputChange);
	$('#channel_input').on('change', function(){
		if($(this).val() !== '' && $(this).val() !== '<?= $_COMPANY->encodeId(0)?>'){
			$('#chapters_selection_div').hide();
		}else{
			$('#chapters_selection_div').show();
		}
		$('.group_placeholder_text').hide();
	});	

	$('#submit_action_button').click(function (event){

		var isUserGroupLead = '<?=$isGroupLead?>';
		var isUserChapterLead = '<?=$isChapterLead?>';
		var isUserChannelLead = '<?=$isChannelLead?>';

		var selectedChapter = $('#chapters_input').map(function(){
			return $(this).val();
		}).get();
		var selectedChannel = $('#channel_input').is(':visible') ? $('#channel_input').val() : '<?= $_COMPANY->encodeId(0)?>';

		if(isUserGroupLead){
			// skip chapter channel validation
			return true; 
		}

		if(isUserChapterLead || isUserChannelLead){
			// chapter lead validation
			if(
				isUserChapterLead && selectedChapter.length === 0 && (!isUserChannelLead || selectedChannel == '<?= $_COMPANY->encodeId(0)?>')
			){
				event.preventDefault();
				swal.fire({title: '', text: "Please Select at least one Chapter or Channel"}).then(()=>{
					return false;
				});
				return;
			}

			// channel lead validation
			if(
				isUserChannelLead && selectedChannel == '<?= $_COMPANY->encodeId(0)?>' && (!isUserChapterLead || selectedChapter.length === 0)
			){
				event.preventDefault();
				swal.fire({title: '', text: "Please Select a valid channel"}).then(()=>{
					return false;
				});
				return;
			}

		}
	})
});
</script>

