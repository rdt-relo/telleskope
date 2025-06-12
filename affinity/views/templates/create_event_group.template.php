<div id="neweventgroup" class="modal fade">
    <div aria-label="<?=$pageTitle?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div  class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title" class="modal-title"><?= $pageTitle; ?></h2>
                <button aria-label="close" type="button" id="btn_close" class="close" onclick="window.location.reload();" data-dismiss="modal">&times;</button>
                
            </div>
            <div class="modal-body">
				<div class="col-md-12">
                    <form  class="" id="create_event_group_form">
                    <div class="form-group">
                        <p  class="control-lable"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                    </div>
                        <div class="form-group">
                            <label  class="control-lable"><?= gettext('Create In');?></label>
                            <select class="form-control" name="event_scope" id="event_scope" onchange="changeScopeSelector(this.value)">
                            <?php if(($groupid ==0) && $_USER->isAdmin()){ ?>
                                <option value="zone" <?= $edit ? ( $edit->val('listids') == '0' ? 'selected' : ($edit->val('isactive') == 1 ? 'disabled' : '')) : ''; ?> ><?= sprintf(gettext("All %s"),$_COMPANY->getAppCustomization()['group']["name-plural"]);?></option>
                                <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) { ?>
                                <option value="dynamic_list" <?= $edit ? ( $edit->val('listids') != '0' ? 'selected' : ($edit->val('isactive') == 1 ? 'disabled' : '')) : ''; ?>><?= sprintf(gettext("Dynamic Lists"), $_COMPANY->getAppCustomization()['group']['name']); ?></option>
                                <?php } ?>
                            <?php }elseif ($groupid) { ?>
                                <option value="group" <?= $edit ? ( $edit->val('listids') == '0' ? 'selected' : ($edit->val('isactive') == 1 ? 'disabled' : '')) : ''; ?> ><?= sprintf(gettext("This %s"),$_COMPANY->getAppCustomization()['group']["name-short"]);?></option>
                                <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled'] && ($_USER->isAdmin() || $_USER->isGroupLead($groupid))) { ?>
                                <option value="dynamic_list" <?= $edit ? ( $edit->val('listids') != '0' ? 'selected' : ($edit->val('isactive') == 1 ? 'disabled' : '')) : ''; ?>><?= sprintf(gettext("This %s Dynamic Lists"), $_COMPANY->getAppCustomization()['group']['name']); ?></option>
                                <?php } ?>
                            <?php } ?>
                            </select>
                        </div>

                        <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) { ?>
                        <div id="list_selection" style="display:<?= $edit ? ( $edit->val('listids') != '0' ? 'block' : 'none') : 'none'; ?>;">
                            <div class="form-group">
                                <?php if($edit && $edit->val('isactive') == 1){ ?>
                                <div class="form-admin-option m-0 p-2">
                                    <strong>
                                        <?= sprintf(gettext('This Event Series is published in %s dynamic list'),DynamicList::GetFormatedListNameByListids($edit->val('listids')));?>
                                    </strong>
                                </div>
                                <?php } else { ?>
                                    <label  class="control-lable"><?= gettext('Select Dynamic Lists');?></label>
                                    <select class="form-control" name="list_scope[]" id="list_scope" multiple>
                                        <?php 
                                        $selectedLists = array();
                                        if ($edit){
                                            $selectedLists = explode(',',$edit->val('listids'));
                                        }
                                        foreach($lists as $list){
                                        ?>
                                            <option value="<?= $_COMPANY->encodeId($list->val('listid')); ?>" <?= in_array($list->val('listid'), $selectedLists) ? 'selected' : ''; ?>><?= $list->val('list_name'); ?></option>
                                        <?php } ?>
                                    </select>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } ?>


                        <input type="hidden" id='event_series_id' name='event_series_id' value="<?= $_COMPANY->encodeId($id); ?>">

                        <div class="form-group">
                            <label for="event_series"><?= gettext('Event Series Name');?><span style="color: #ff0000;"> *</span></label>
                            <input  class="form-control" placeholder="<?= gettext('Event Series Name');?> ..." id="event_series_name"  name="event_series_name" value="<?= $edit ? $edit->val('eventtitle') : ''; ?>" required />
                        </div>
                        <div class="form-group">
                            <label for="inputEmail"><?= gettext('Description');?></label>
                            <div id="post-inner" class="post-inner-edit">
                                <textarea class="form-control" placeholder="<?= gettext('Event Description');?>" name="event_description" rows="5" id="redactor_content" maxlength="2000" ><?= $edit ?  htmlspecialchars($edit->val('event_description')) : '' ?></textarea>
                            </div>
                        </div>

                        <?php if (!$edit || ($edit && $edit->val('groupid') > 0 && ($edit->isDraft() || $edit->isUnderReview()))){ ?>
                        <?php $use_and_chapter_connector = $edit?->val('use_and_chapter_connector') ?? false; ?>
                        <?php $warn_if_all_chapters_are_selected = true;?>
                        <?php include_once __DIR__.'/../common/init_chapter_channel_selection_box.php'; ?>
                        <?php } ?>

                        <fieldset class="form-group">
                        <legend for="event_series" style="font-size:unset;"><?= gettext('Restrict users to join');?></legend>
                              <div class="radio ml-2">
                                <label>
                                    <input type="radio" name="rsvp_restriction" value='<?= $_COMPANY->encodeId(Event::EVENT_SERIES_RSVP_RESTRICTION['ANY_NUMBER_OF_EVENTS']); ?>' <?= $edit ? ($edit->val('rsvp_restriction')== Event::EVENT_SERIES_RSVP_RESTRICTION['ANY_NUMBER_OF_EVENTS'] ? 'checked' : '') : 'checked'; ?>>
                                    <?= gettext('Allow users to join any number of events');?>
                                </label>
                              </div>
                              <div class="radio ml-2">
                                <label>
                                    <input type="radio" name="rsvp_restriction" value='<?= $_COMPANY->encodeId(Event::EVENT_SERIES_RSVP_RESTRICTION['ANY_NUMBER_OF_NON_OVERLAPPING_EVENTS']); ?>' <?= $edit ? ($edit->val('rsvp_restriction')== Event::EVENT_SERIES_RSVP_RESTRICTION['ANY_NUMBER_OF_NON_OVERLAPPING_EVENTS'] ? 'checked' : '') : ''; ?>>
                                    <?= gettext('Allow users to join any number of non-overlapping events');?>
                                </label>
                              </div>
                              <div class="radio ml-2">
                                <label>
                                    <input type="radio" name="rsvp_restriction" value="<?= $_COMPANY->encodeId(Event::EVENT_SERIES_RSVP_RESTRICTION['SINGLE_EVENT_ONLY']); ?>" <?= $edit ? ($edit->val('rsvp_restriction')== Event::EVENT_SERIES_RSVP_RESTRICTION['SINGLE_EVENT_ONLY'] ? 'checked' : '') : ''; ?> >
                                    <?= gettext('Allow users to join single event only');?>
                                </label>
                              </div>
                        </fieldset>

                        <div class="form-group">
                            <label class="form-check-label">
                                <input type="checkbox" class="ml-2" name="isprivate" id="isprivate" value="1" <?= $edit ? ( ($edit->val('isprivate')==1) ? 'checked' : '') :''; ?>>&nbsp;
                                <?= gettext('Private Event');?>
                                <small> (<?= gettext('hide from Calendar and Group Feeds');?>)</small>
                                <br><small><span id="privateOffDisabledText" style="margin-left: 2em; display: <?= $edit ? ($edit->val('listids') ? 'inline' : 'none') : 'none'?>"><?= gettext('The target scope of this event is a dynamic list, and it is thus considered a private event.');?></span></small>
                            </label>
                        </div>
                    </form>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="modal-footer text-center" style='text-align:center !important;display: block;'>
                <button type="submit" id="eventGroupId" class="btn btn-affinity prevent-multi-clicks" onclick="createEventGroup('<?= $encGroupId; ?>');" ><?= $buttonTitle; ?></button>
                <button type="button" class="btn btn-affinity" aria-hidden="true" data-dismiss="modal" onclick="window.location.reload();"><?= gettext('Close');?></button>
            </div>
        </div>
    </div>
</div>
<script>   

    $(document).ready(function(){
        var fontColors = <?= $fontColors; ?>;
        $('#redactor_content').initRedactor('redactor_content','event',['fontcolor','counter','handle','fontsize'],fontColors,'<?= $_COMPANY->getImperaviLanguage(); ?>');
        $(".redactor-voice-label").text("<?= gettext('Event Description');?>");

        <?php if ($edit && !empty($edit->val('listids'))) { ?>
        $('#isprivate').click( function() { return false; } );
        <?php } ?>

        redactorFocusOut('#event_series_name'); // function used for focus out from redactor when press tab+shift..

    });

$('#neweventgroup').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus');
   $("#channels_selection_div .multiselect").attr({'aria-expanded':"false", 'role':"combobox", 'aria-label':"<?= $_COMPANY->getAppCustomization()['channel']['name-short'];?>"}); 
   $("#chapters_selection_div .multiselect").attr({'aria-expanded':"false", 'role':"combobox", 'aria-label':"<?=$_COMPANY->getAppCustomization()['chapter']['name-short']?>"}); 
   
});
</script>
<script>
        var initialLoad = true;
        function changeScopeSelector(i) {
            handleCreationScopeDropdownChange("#event_scope");
            if (i == 'dynamic_list') {
                $("#list_selection").show();
                $("#privateOffDisabledText").show();
                $('#isprivate').prop('checked', true);
                $('#isprivate').click( function() { return false; } );
                $('#channels_selection_div').hide();
                $('#chapters_selection_div').hide();
            } else {
                $("#list_selection").hide();
                $("#privateOffDisabledText").hide();
                if(!initialLoad){
                    $('#isprivate').prop('checked', false);
                }
                $("#isprivate").prop("onclick", null).off("click");
                $('#channels_selection_div').show();
                $('#chapters_selection_div').show();
            }
            $('#event_scope').focus();
            initialLoad = false;
	    }
      $(document).ready(function () {
        var currentSelection = $("#event_scope").val();        
        changeScopeSelector(currentSelection);
      });
</script>
<script>
	$('#list_scope').multiselect({
		nonSelectedText: "<?=gettext("No list selected"); ?>",
		numberDisplayed: 3,
		nSelectedText: "<?= gettext('List selected');?>",
		disableIfEmpty: true,
		allSelectedText: "<?= gettext('Multiple lists selected'); ?>",
		enableFiltering: true,
		maxHeight: 400,
		enableClickableOptGroups: true
	});
</script>
