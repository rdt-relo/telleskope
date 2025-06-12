die(); /* Disabled on 11/05/2023 */

<div class="modal" id="CollaborationInviteModal" tabindex="-1">
    <div aria-label="<?= sprintf(gettext("Invite %s to collaborate"),$_COMPANY->getAppCustomization()['group']['name-short-plural']);?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="modal-title" class="modal-title"><?= sprintf(gettext("Invite %s to collaborate"),$_COMPANY->getAppCustomization()['group']['name-short-plural']);?></h5>
                <button id="btn_close" type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <p class="col-md-12 red">Important Note: The event collaboration feature is now available on Create/Update Event form. This screen is now deprecated and it will be removed after May 2023.</p>
                    <div class="form-group form-group-scroll" id="collaboration_selection">
                        <label for="inputEmail" class="col-sm-12 control-lable"><?= gettext("Collaborate with");?></label>
                        <div class="col-sm-12">
                            <select tabindex="-1" class="form-control" id="collaborate" multiple name='collaborating_groupids[]' required>
                                <?php foreach($groups as $g_k =>$g_v) {
                                    echo '<optgroup class="clickable-optgroup" label="' . $g_k . '">';
                                    foreach ($g_v as $g) {
                                        $selected = '';
                                        $groupname = $g['groupname'];
                                        if (in_array($g['groupid'], $alreadyCollaborating)) {
                                            if (in_array($g['groupid'],$alreadyInvited)) {
                                                $groupname .= ' (Pending Acceptance)';
                                            }
                                            $selected = 'selected';
                                        }
                                        echo '<option value="' . $_COMPANY->encodeId($g['groupid']) . '" ' . $selected . '>' . $groupname . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="sendEventCollaborationRequest('<?= $_COMPANY->encodeId($eventid);?>');" class="btn btn-primary"><?= gettext("Send Request");?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= gettext("Close");?></button>
            </div>
        </div>
    </div>
</div>

<script>
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
        enableClickableOptGroups: true
    });

    $('#CollaborationInviteModal').on('shown.bs.modal', function () {
        $('#modal-title').trigger('focus')
    });
    $(document).ready(function(){
		$('.multiselect').attr( 'tabindex', '0' );
    });
</script>