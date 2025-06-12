<?php

	// This file provides common inline functions to initialize cross zone group collaboration

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


<div class="form-group" >
	<label for="collaborate" class="<?= $lableCol;?> control-lable"><?= sprintf(gettext('%s Collaboration'),$_COMPANY->getAppCustomization()['group']["name-short-plural"]);?></label>
	<div class="<?= $selectDropdownCol; ?>">
		<select class="form-control " id="collaborate" multiple name='collaborating_groupids[]' onchange="checkPermissionAndMultizoneCollaboration('<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId($groupid); ?>'); getChaptersForCollaborations('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($event? $event->val('eventid'): 0);?>')" required>

			<?php foreach($groups as $g_k =>$g_v) {
				if (!is_array($g_v)) {
					$g_v = [$g_v];
				} else {
					echo '<optgroup class="clickable-optgroup" label="' . $g_k . '">';
				}
				foreach ($g_v as $g) {
					$groupname = $g['groupname'];
                    $groupname_suffix = '';
                    if (
                        !$_USER->isCompanyAdmin()
                        && !$_USER->isZoneAdmin($g['zoneid'])
                        && !$_USER->canCreateContentInGroupOnly($g['groupid'])
                        && !$_USER->canPublishContentInGroupOnly($g['groupid'])
                        && !$_USER->isRegionallead($g['groupid'])
                    ) {
                        if ($_COMPANY->getAppCustomization()['event']['collaborations']['auto_approve']) {
                            $groupname_suffix = ' (' . gettext('auto-approved') . ')';
                        } else {
                            $groupname_suffix = ' (' . gettext('needs approval') . ')';
                        }
					}

                    $disabled = '';
                    $selected = '';
                    if ($event) {
                        if (in_array($g['groupid'], explode(',', $event->val('collaborating_groupids')))) {
                            $groupname_suffix = ' (' . gettext('collaborating') . ')';
                            $selected = 'selected';
							if ($g['groupid'] == $groupid) {
								$disabled = 'disabled';
							}
                        } elseif (in_array($g['groupid'], explode(',', $event->val('collaborating_groupids_pending')??''))) {
							if (empty($groupname_suffix)){
                            	$groupname_suffix = ' (' . gettext('collaborating') . ')';
							}
                            $selected = 'selected';
							if ($g['groupid'] == $groupid) {
								$disabled = 'disabled';
							}
                        }
                    } else {
                        if ($g['groupid'] == $groupid) {
                            $disabled = 'disabled';
                            $selected = 'selected';
                        }
                    }

					echo '<option value="' . $_COMPANY->encodeId($g['groupid']) . '" '. $disabled . ' ' . $selected . ' >' . $groupname . $groupname_suffix . '</option>';
				}
			}
			?>
		</select>
		<p class="alert-warning mt-2 p-3" id="infoMessage" style="display:none;"></p>
		
	</div>
</div>


<div id="chapter_colleboration">
	<!-- Chapters dropdown will shown here -->
</div>

<script>
	  function checkChapterSelected(v){
            if(v!='0'){
                $("#collaboration_selection").hide();
                $("#collaborate").val([]).change();
            } else {
                $("#collaboration_selection").show();
            }
        }

		$('#collaborate').multiselect('destroy');
		$('#collaborate').multiselect({
			nonSelectedText: "<?= sprintf(gettext('Select %s for collaboration'),$_COMPANY->getAppCustomization()['group']['name-short-plural'])?>",
			numberDisplayed: 3,
			filterPlaceholder: "<?=gettext('Search ... (case sensitive) ')?>",
			nSelectedText  : "<?= sprintf(gettext('%s selected for collaboration'),$_COMPANY->getAppCustomization()['group']['name-short-plural'])?>",
			disableIfEmpty:true,
			allSelectedText: "<?= sprintf(gettext('All %s selected for collaboration'),$_COMPANY->getAppCustomization()['group']['name-short-plural'])?>",
			selectAllText: '<?= gettext("Select All for Collaboration");?>',
			// includeSelectAllOption: true,
			enableFiltering: true,
			maxHeight:400,
			selectAllValue: 'multiselect-all',
			enableClickableOptGroups: true,
            afterSelect: function (i) {
                console.log("selected ");
                console.log(i);
            },
            afterDeselect: function (i) {
                console.log("deelected ");
                console.log(i);
            }
		});

		$(function(){
			$('.btn-group>ul>li.clickable-optgroup>a>label').click(function (e) {
				setTimeout(function(){
					checkPermissionAndMultizoneCollaboration('<?= $_COMPANY->encodeId($eventid); ?>','<?= $_COMPANY->encodeId($groupid); ?>');
                    getChaptersForCollaborations('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($event? $event->val('eventid'): 0);?>');
				}, 100);
			});
		});

		$( document ).ready(function() {
			<?php if ($event){ ?>
				getChaptersForCollaborations('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($event->val('eventid'));?>');
				checkPermissionAndMultizoneCollaboration('<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId($groupid); ?>');
			<?php } ?>
		});

</script>