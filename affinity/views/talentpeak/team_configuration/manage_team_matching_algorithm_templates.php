<style>
    .fa-check{
        display: none;
    }
</style>
<style>
    .switch {
      position: relative;
      display: inline-block;
      width: 40px;
      height: 22px;
    }

    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      -webkit-transition: .4s;
      transition: .4s;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 13px;
      width: 13px;
      left: 7px;
      bottom: 4px;
      background-color: white;
      -webkit-transition: .4s;
      transition: .4s;
    }

    input:checked + .slider {
      background-color: red;
    }

    input:focus + .slider {
      box-shadow: 0 0 1px red;
    }

    input:checked + .slider:before {
      -webkit-transform: translateX(13px);
      -ms-transform: translateX(13px);
      transform: translateX(13px);
    }

    /* Rounded sliders */
    .slider.round {
      border-radius: 17px;
    }

    .slider.round:before {
      border-radius: 50%;
    }
    .dropdown-menu a {
        white-space: pre-wrap;
    }

    .matching-attribute-name {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        line-height: 1rem;
    }
    .matching-attribute-table {
        margin: 20px 0 10px 0;
    }
    .matching-attribute-table thead tr {
        border-bottom: 1px solid lightgrey;
    }
    .matching-attribute-table tbody tr {
        line-height: 1.2    rem;
    }
    .primary-table th,td{
        padding-left:16px;
    }
    .primary-table caption{
        color: #505050;
    }
</style>
<div class="row mb-4">

<?php if (0) { /* DO NOT ENABLE: Disabled as we will retain this featre on the Admin Panel */ ?>
<div class="col-md-12" id="reportTeamMemberJoinSurveyResponseDownloadOptions" style="display: none;"></div>
<?php } ?>

    <form class="form-group" id="matchingAlgorithmForm">
    <?php  $i = 0; ?>
    <?php // if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']){ /* Commenting this as we will diable Matching algo tab for Circles */ ?>

        <div class="col-12 form-group-emphasis">
        <div class="">
        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext('Primary Attributes'); ?></h3> 
        <table class="table matching-attribute-table primary-table attribute-table">
        <caption style="padding-left: 12px;margin-top: -22px;"><?= gettext('Select which primary attributes you want to match'); ?></caption>
                     
                <thead>
                <tr>
                    <th style="width:52%;"><?= gettext('Field'); ?></th>
                    <th style="width:20%;"><?= gettext('Criteria'); ?><i role="button" tabindex="0" class="fa fa-question-circle" aria-label="<?= sprintf(gettext('Matching Criteria Info'))?>" data-toggle="tooltip" title="" data-original-title="<?= sprintf(gettext('Define attribute matching criteria for %1$s-%2$s pairings'), $algorithmMatchingBetweenLabel[$group->getTeamProgramType()][0], $algorithmMatchingBetweenLabel[$group->getTeamProgramType()][1])?>"></i></th>
                    <th style="width:13%;"><?= gettext('Is Required'); ?></th>
                    <th style="width:15%;"><?= gettext('Match Visibility'); ?></th>
                </tr>
                </thead>
                <tbody>
            <?php foreach($catalog_categories as $category){ ?>
                
                <tr data-type="catalog">
                    <td class="matching-attribute-name">
                        <?= $category; ?>
                        <p><small class="help-text lh-1"></small></p>
                    </td>
                    <td>
                <?php if (UserCatalog::GetCategoryKeyType($category) === 'string'){ ?>
                        <select aria-label="<?= $category; ?> of&nbsp;<?= $algorithmMatchingBetweenLabel[$group->getTeamProgramType()][0]; ?>" class="form-control form-control-sm criteria-dropdown" name="<?= $category; ?>"  style="min-width: 150px;" onchange="enableDisableRequiredOption('<?= $category; ?>',this.value)">
                            <option value="0">-</option>
                            <option value="equals" <?= $primaryParameters && isset($primaryParameters[$category]) && $primaryParameters[$category] == 1 ? 'selected' : ''; ?> ><?= gettext("equals"); ?></option>
                            <option value="notEquals" <?= $primaryParameters && isset($primaryParameters[$category]) &&  $primaryParameters[$category] == 2 ? 'selected' : ''; ?>><?= gettext("does not equal"); ?></option>
                        </select>
                <?php } else {  ?>
                   
                        <select aria-label="<?= $category; ?> of&nbsp;<?= $algorithmMatchingBetweenLabel[$group->getTeamProgramType()][0]; ?>" class="form-control form-control-sm criteria-dropdown" name="<?= $category; ?>" style="min-width: 150px;" onchange="enableDisableRequiredOption('<?= $category; ?>',this.value)">
                            <option value="0">-</option>
                            <option value="1" <?= $primaryParameters && isset($primaryParameters[$category]) && $primaryParameters[$category] == 1 ? 'selected' : ''; ?> ><?= gettext("greater than"); ?></option>
                            <option value="2" <?= $primaryParameters && isset($primaryParameters[$category]) &&  $primaryParameters[$category] == 2 ? 'selected' : ''; ?>><?= gettext("is equal to"); ?></option>
                            <option value="3" <?= $primaryParameters && isset($primaryParameters[$category]) &&  $primaryParameters[$category] == 3 ? 'selected' : ''; ?>><?= gettext("less than"); ?></option>
                            <option value="4" <?= $primaryParameters && isset($primaryParameters[$category]) && $primaryParameters[$category] == 4 ? 'selected' : ''; ?>><?= gettext("is not equal to"); ?></option>
                            <option value="5" <?= $primaryParameters && isset($primaryParameters[$category]) && $primaryParameters[$category] == 5 ? 'selected' : ''; ?>><?= gettext("greater than or equal to"); ?></option>
                            <option value="6" <?= $primaryParameters && isset($primaryParameters[$category]) && $primaryParameters[$category] == 6 ? 'selected' : ''; ?>><?= gettext("less than or equal to"); ?></option>
                            <option value="11" <?= $primaryParameters && isset($primaryParameters[$category]) && $primaryParameters[$category] == 11 ? 'selected' : ''; ?>><?= gettext('range match'); ?></option>
                            
                        </select>
                <?php } ?>
                  
                    <div class="range-match-config" style="display: none;">
                        <label class="mb-1 small text-muted"><?= 'Difference (Mentor-Mentee)'; /* No translation needed here */  ?></label>
                        <div style="display: flex; gap: 8px;">
                        <input type="number" class="form-control range-min form-control-sm" style="width: 35%;" value="<?= $mandatoryPrimaryParameters && isset($mandatoryPrimaryParameters[$category]) && !empty($mandatoryPrimaryParameters[$category]['matching_adjustment']) ? $mandatoryPrimaryParameters[$category]['matching_adjustment'][0] : 0; ?>"  name="<?= $category; ?>_matching_min_adjustment" title="Enter minimum value of difference between Mentor and mentee">
                            -
                        <input type="number" class="form-control range-max form-control-sm" style="width: 35%;" value="<?= $mandatoryPrimaryParameters && isset($mandatoryPrimaryParameters[$category]) && !empty($mandatoryPrimaryParameters[$category]['matching_adjustment']) ? $mandatoryPrimaryParameters[$category]['matching_adjustment'][1] : 0; ?>" name="<?= $category; ?>_matching_max_adjustment" title="Enter maximum value of difference between Mentor and mentee">
                        </div>
                    </div>
                    </td>
                    <td>
                        <select aria-label="<?= $category; ?> &emsp;that&nbsp;of&nbsp;<?= $algorithmMatchingBetweenLabel[$group->getTeamProgramType()][1]; ?>" class="form-control form-control-sm" name="<?= $category; ?>_is_required" id="<?= $category; ?>_is_required">
                            <option value="0">-</option>
                            <option value="1" <?= $mandatoryPrimaryParameters && isset($mandatoryPrimaryParameters[$category]) && $mandatoryPrimaryParameters[$category]['is_required'] == 1 ? 'selected' : ''; ?> ><?= gettext("Is required"); ?></option>
                        </select>
                    </td>

                    <td>
                        <select style="display:none;" aria-label="<?= $category; ?> &emsp;that&nbsp;of&nbsp;<?= $algorithmMatchingBetweenLabel[$group->getTeamProgramType()][1]; ?>" class="form-control form-control-sm visibilitySetting" name="<?= $category; ?>_visibility_setting[]" id="<?= $category; ?>_visibility_setting" multiple>
                            <option value="<?= Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_matchp_users']; ?>" <?= $group->getTeamMatchingAttributeKeyVisibilitySetting('primary_parameters',$category,Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_matchp_users']) == 'show' ? 'selected' : ''; ?>><?= gettext('Show percentage match to users')?></option>
                            <option value="<?= Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_value_users']; ?>" <?= $group->getTeamMatchingAttributeKeyVisibilitySetting('primary_parameters',$category,Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_value_users']) == 'show' ? 'selected' : ''; ?>><?= gettext('Show field values to users')?></option>
                            <option value="<?= Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_matchp_leaders']; ?>" <?= $group->getTeamMatchingAttributeKeyVisibilitySetting('primary_parameters',$category,Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_matchp_leaders']) == 'show' ? 'selected' : ''; ?>><?= gettext('Show percentage match to leaders')?></option>
                            <option value="<?= Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_value_leaders']; ?>" <?= $group->getTeamMatchingAttributeKeyVisibilitySetting('primary_parameters',$category,Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_value_leaders']) == 'show' ? 'selected' : ''; ?>><?= gettext('Show field values to leaders')?></option>

                        </select>
                    </td>
                </tr>
               
            <?php  $i++; } ?>
            </tbody>
                </table>
        </div>
        </div>

    <?php // } /* Commenting this as we will diable Matching algo tab for Circles */ ?>
       
        <div class="col-12 form-group-emphasis">
            <div class="">   
                <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext('Custom Attributes (Registration Survey)');?></h3>       
                <table id="matchingTable" class="table matching-attribute-table primary-table attribute-table">
                <?php if (0 ) { // if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']){  /* Commenting this as we will diable Matching algo tab for Circles */ ?>

                    <?php if(!empty($customAttributes)){ ?>
                        <a class="btn btn-affinity" aria-label="<?= gettext('Update survey questions'); ?>" href="new_matching_custom_attribute?groupid=<?= $_COMPANY->encodeId($groupid); ?>">
                        <?= gettext("Update Survey"); ?>
                    </a>
                    <?php } else { ?>
                        <a class="btn btn-affinity" aria-label="<?= gettext('Create survey questions'); ?>" href="new_matching_custom_attribute?groupid=<?= $_COMPANY->encodeId($groupid); ?>">
                        <?= gettext("Create Survey"); ?>
                        </a>
                    <?php } ?>

                <?php } else { ?>
                    <caption style="padding-left: 12px;margin-top: -22px;"><?= gettext('Select custom attributes you want to match'); ?>
                    <a aria-label="<?= gettext('Add custom attributes'); ?>" href="new_matching_custom_attribute?groupid=<?= $_COMPANY->encodeId($groupid); ?>">
                        <i aria-hidden="true" class="fa fa-plus-circle" title="Add custom attributes"></i>
                    </a></caption>

                <?php } ?>


                <?php //if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']){  /* Commenting this as we will diable Matching algo tab for Circles */ ?>

                
                    <thead>
                    <tr>
                        <th style="width:52%;"><?= gettext('Fields'); ?></th>
                        <th style="width:20%;"><?= gettext('Criteria'); ?></th>
                        <th style="width:13%;"><?= gettext('Is Required'); ?></th>
                        <th style="width:15%;"><?= gettext('Match Visibility'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($customAttributes as $customAttribute){
                        $question = array_key_exists('title',$customAttribute) ? $customAttribute['title'] : $customAttribute['name'];
                        $type = $customAttribute['type'];
                        $customValue = -1;

                        if ($customParameters && array_key_exists($customAttribute['name'],$customParameters)){
                            $customValue = $customParameters[$customAttribute['name']];
                        }

                        if ($type == 'html'){
                            continue;
                        }
                        if ($type == 'comment'){
                            continue;
                        }
                        ?>
                      
                        <tr data-type='survey'>
                            <td class="matching-attribute-name">
                                <?= $question; ?>
                                <p><small class="help-text lh-1"></small></p>
                            </td>
                            <!-- <td>of&nbsp;<?= $algorithmMatchingBetweenLabel[$group->getTeamProgramType()][0]; ?></td> -->
                            <?php if($type == 'radiogroup' || $type == 'dropdown'){ ?>
                                <td>
                                    <select class="form-control form-control-sm" name="<?= $customAttribute['name']; ?>"  style="min-width: 150px;" onchange="enableDisableRequiredOption('<?= $customAttribute['name']; ?>',this.value)">
                                        <option value="0">-</option>
                                        <option value="<?= $customAttributesMatchingOptions['MATCH']; ?>" <?= $customValue == $customAttributesMatchingOptions['MATCH'] ? 'selected' : ''; ?> ><?= gettext("matches"); ?></option>
                                        <option value="<?= $customAttributesMatchingOptions['DONOT_MATCH']; ?>" <?= $customValue == $customAttributesMatchingOptions['DONOT_MATCH'] ? 'selected' : ''; ?> ><?= gettext("does not match"); ?></option>
                                    </select>
                                </td>
                            <?php } elseif($type == 'checkbox'){ ?>
                                <td>
                                    <select class="form-control form-control-sm" name="<?= $customAttribute['name']; ?>"  style="min-width: 150px;" onchange="enableDisableRequiredOption('<?= $customAttribute['name']; ?>',this.value)">
                                        <option value="0">-</option>
                                        <option value="<?= $customAttributesMatchingOptions['MATCH_N_NUMBERS']; ?>" <?= $customValue == $customAttributesMatchingOptions['MATCH_N_NUMBERS'] ? 'selected' : ''; ?> ><?= gettext("matches"); ?></option>
                                    </select>
                                </td>

                            <?php } elseif($type == 'rating'){ ?>
                                <td>
                                    <select class="form-control form-control-sm criteria-dropdown" name="<?= $customAttribute['name']; ?>"  style="min-width: 150px;" onchange="enableDisableRequiredOption('<?= $customAttribute['name']; ?>',this.value)">
                                        <option value="0">-</option>
                                        <option value="<?= $customAttributesMatchingOptions['GREATER_THAN']; ?>" <?= $customValue == $customAttributesMatchingOptions['GREATER_THAN'] ? 'selected' : ''; ?> ><?= gettext("greater than"); ?></option>
                                        <option value="<?= $customAttributesMatchingOptions['EQUAL_TO']; ?>" <?= $customValue == $customAttributesMatchingOptions['EQUAL_TO'] ? 'selected' : ''; ?> ><?= gettext("equal to"); ?></option>
                                        <option value="<?= $customAttributesMatchingOptions['LESS_THAN']; ?>" <?= $customValue == $customAttributesMatchingOptions['LESS_THAN'] ? 'selected' : ''; ?> ><?= gettext("less than"); ?></option>
                                        <option value="<?= $customAttributesMatchingOptions['NOT_EQUAL_TO']; ?>" <?= $customValue == $customAttributesMatchingOptions['NOT_EQUAL_TO'] ? 'selected' : ''; ?> ><?= gettext("not equal to"); ?></option>
                                        <option value="<?= $customAttributesMatchingOptions['GREATER_THAN_OR_EQUAL_TO']; ?>" <?= $customValue == $customAttributesMatchingOptions['GREATER_THAN_OR_EQUAL_TO'] ? 'selected' : ''; ?> ><?= gettext("greater than or equal to"); ?></option>
                                        <option value="<?= $customAttributesMatchingOptions['LESS_THAN_OR_EQUAL_TO']; ?>" <?= $customValue == $customAttributesMatchingOptions['LESS_THAN_OR_EQUAL_TO'] ? 'selected' : ''; ?> ><?= gettext("less than or equal to"); ?></option>
                                        <option value="<?= $customAttributesMatchingOptions['RANGE_MATCH']; ?>" <?= $customValue ==  $customAttributesMatchingOptions['RANGE_MATCH'] ? 'selected' : ''; ?>><?= gettext('range match'); ?></option>
                                    </select>
                                    <div class="range-match-config" style="display: none;">
                                        <label class="mb-1 small text-muted"><?= 'Difference (Mentor-Mentee)'; /* No translation needed here */  ?></label>
                                        <div style="display: flex; gap: 8px;">
                                        <input type="number" class="form-control range-min form-control-sm" style="width: 35%;" value="<?= $mandatoryCustomParameters && isset($mandatoryCustomParameters[$customAttribute['name']]) && !empty($mandatoryCustomParameters[$customAttribute['name']]['matching_adjustment']) ? $mandatoryCustomParameters[$customAttribute['name']]['matching_adjustment'][0] : 0; ?>"  name="<?= $customAttribute['name']; ?>_matching_min_adjustment" title="Enter minimum value of difference between Mentor and mentee">
                                        <input type="number" class="form-control range-max form-control-sm"  style="width: 35%;" value="<?= $mandatoryCustomParameters && isset($mandatoryCustomParameters[$customAttribute['name']]) && !empty($mandatoryCustomParameters[$customAttribute['name']]['matching_adjustment']) ? $mandatoryCustomParameters[$customAttribute['name']]['matching_adjustment'][1] : 2; ?>" name="<?= $customAttribute['name']; ?>_matching_max_adjustment" title="Enter maximum value of difference between Mentor and mentee">
                                        </div>
                                    </div>
                                </td>
                            <?php } elseif($type == 'text'){ ?>
                                <td>
                                    <select class="form-control form-control-sm" name="<?= $customAttribute['name']; ?>"  style="min-width: 150px;" onchange="enableDisableRequiredOption('<?= $customAttribute['name']; ?>',this.value)">
                                        <option value="0">-</option>
                                        <option value="<?= $customAttributesMatchingOptions['WORD_MATCH']; ?>" <?= $customValue == $customAttributesMatchingOptions['WORD_MATCH'] ? 'selected' : ''; ?> ><?= gettext("match words"); ?></option>
                                    </select>
                                </td>
                            <?php } ?>
                            <td>
                                <select class="form-control form-control-sm" id="<?=$customAttribute['name']; ?>_is_required" name="<?=$customAttribute['name']; ?>_is_required">
                                    <option value="0">-</option>
                                    <option value="1" <?= $mandatoryCustomParameters && isset($mandatoryCustomParameters[$customAttribute['name']]) && $mandatoryCustomParameters[$customAttribute['name']]['is_required'] == 1 ? 'selected' : ''; ?> ><?= gettext("Is required"); ?></option>
                                </select>
                            </td>


                            <td>
                                <select style="display:none;" aria-label="<?= $customAttribute['name']; ?> &emsp;that&nbsp;of&nbsp;<?= $algorithmMatchingBetweenLabel[$group->getTeamProgramType()][1]; ?>" class="form-control form-control-sm visibilitySetting" name="<?= $customAttribute['name']; ?>_visibility_setting[]" id="<?= $customAttribute['name']; ?>_visibility_setting" multiple width="100%">
                                    <option value="show_matchp_users" <?= $group->getTeamMatchingAttributeKeyVisibilitySetting('custom_parameters',$customAttribute['name'],'show_matchp_users') == 'show' ? 'selected' : ''; ?>><?= gettext('Show percentage match to users')?></option>
                                    <option value="show_value_users" <?= $group->getTeamMatchingAttributeKeyVisibilitySetting('custom_parameters',$customAttribute['name'],'show_value_users') == 'show' ? 'selected' : ''; ?>><?= gettext('Show field values to users')?></option>
                                    <option value="show_matchp_leaders" <?= $group->getTeamMatchingAttributeKeyVisibilitySetting('custom_parameters',$customAttribute['name'],'show_matchp_leaders') == 'show' ? 'selected' : ''; ?>><?= gettext('Show percentage match to leaders')?></option>
                                    <option value="show_value_leaders" <?= $group->getTeamMatchingAttributeKeyVisibilitySetting('custom_parameters',$customAttribute['name'],'show_value_leaders') == 'show' ? 'selected' : ''; ?>><?= gettext('Show field values to leaders')?></option>
                                </select>
                            </td>
                        </tr>
                       

                    <?php $i++; } ?>
                    </tbody>
                </table>

            <?php //} /* Commenting this as we will diable Matching algo tab for Circles */ ?>
                
            </div>
        <?php if($_COMPANY->getAppCustomization()['chapter']['enabled']){ 
            $chapterSelectionSetting = $group->getTeamRoleRequestChapterSelectionSetting();
            $disabled = '';
            if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['ADMIN_LED']){
                $disabled = 'disabled style="cursor:not-allowed;"';
            }
        ?>
            <div class="col-12 pt-3">
                <div class="form-group">
                    <label><input type="checkbox" id="allow_chapter_selection" name="allow_chapter_selection" value="1"  <?= $disabled; ?> <?= $chapterSelectionSetting['allow_chapter_selection'] ? 'checked' : ''; ?> onchange="onAllowChapterSelectionChange('<?=$group->val('chapter_assign_type')?>')"> <?= sprintf(gettext('Allow user to select a %s'),$_COMPANY->getAppCustomization()['chapter']["name-short"])?></label>
                </div>  
                <div class="form-group">
                    <?php if ($disabled ){ ?>
                        <p class="pl-2 pt-0 mt-0 red"><?= sprintf(gettext('The %1$s selection feature is not available for this %2$s type. Please contact your coordinator for more information.'),$_COMPANY->getAppCustomization()['chapter']["name-short"],$_COMPANY->getAppCustomization()['group']['name-short'])?></p>
                    <?php } else { ?>
                        <label><?= sprintf(gettext('%s selection message'),$_COMPANY->getAppCustomization()['chapter']["name-short"]);?></label>
                        <input class="form-control input-sm" id="chapter_selection_label" name="chapter_selection_label" type="text" value="<?= $chapterSelectionSetting['chapter_selection_label'] ?: sprintf(gettext("Select %s of this %s"),$_COMPANY->getAppCustomization()['chapter']['name-short'], $_COMPANY->getAppCustomization()['group']['name-short']); ?>" <?= $chapterSelectionSetting['allow_chapter_selection'] ? '' : 'disabled'?>>
                    <?php } ?>
                </div>        
            </div>
        <?php } ?>
        </div>


        <div id="customAttributeModalData"></div>
        <?php if(!$matchingParameters /*&& $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES'] #Commenting this as we will diable Matching algo tab for Circles */){ ?>
            <p class="pl-5" style="color:red"><?= gettext('Note: Matching Algorithm not saved yet. Please update')?>!</p>
        <?php } ?>
        <div class="col-md-12 p-3 text-center">
            <button class="btn btn-primary" type="button" onclick="updateTeamMatchingAlgorithmParameters('<?= $_COMPANY->encodeId($groupid); ?>')"><?= gettext("Update"); ?></button>
            &emsp;
            <?php if (0) { ?>
            <button onclick="getMatchingAlgorithmSetting('<?= $_COMPANY->encodeId($groupid); ?>')" type="button" class="btn btn-primary">Cancel</button>
            <?php } ?>
        </div>
    </form>

<?php if (0) { /* DO NOT ENABLE: Disabled as we will retain this featre on the Admin Panel */ ?>
<div id="confirmChangeModal" class="modal fade" role="dialog">
    <div aria-label="<?= gettext('Confirmation')?>" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-center">
                <h2 class="modal-title"><?= gettext('Confirmation')?></h2>
            </div>
            <div class="modal-body">
                <p><?= sprintf(gettext('I understand the survey may contain senstive data and allowing survey data to be downloaded is ok. Type %s below to provide your consent.'),"'I agree'") ?></p>
                <div class="form-group">
                    <label><?= gettext("Confirmation"); ?></label>
                    <input type="text" class="form-control form-control-sm" id="confirmChange" onkeyup="initDeleteAccount()" placeholder="I agree" name="confirmChange">
                  </div>
            </div>
            <div class="modal-footer text-center">
                <span id="action_button"><button class="btn btn-primary" disabled ><?= gettext("Submit"); ?></button></span>
                <button type="button" class="btn btn-info" onclick="$('#settingCheckbox').prop('checked', false);" data-dismiss="modal"><?= gettext('Cancel'); ?></button>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<?php if (0) { /* DO NOT ENABLE: Disabled as we will retain this featre on the Admin Panel */ ?>
<div id="deleteSurveyDataConfirmationModal" class="modal fade" role="dialog">
    <div aria-label="<?= gettext('Confirmation')?>" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-center">
                <h4 class="modal-title"><?= gettext('Confirmation'); ?></h4>
            </div>
            <div class="modal-body">
                <p><?= gettext('I understand the survey response data will be immediately and permanently deleted. By typing "<b>Yes, permanently delete the survey responses</b>" below, I am providing my consent to delete the responses!');?></p>
                <div class="form-group">
                    <label><?= gettext("Confirmation"); ?>:</label>
                    <input type="text" class="form-control form-control-sm" id="confirmChangeSurvey" onkeyup="initDeleteSurveyResponse()" placeholder="" name="confirmChangeSurvey">
                  </div>
            </div>
            <div class="modal-footer text-center">
                <span id="action_button_survey"><button class="btn btn-outline-danger" disabled ><?= gettext("Submit"); ?></button></span>
                <button type="button" class="btn btn-info" data-dismiss="modal"><?= gettext("Cancel"); ?></button>
            </div>
        </div>
    </div>
</div>
<?php } ?>

</div>

<script>
    $('.visibilitySetting').multiselect({nonSelectedText: "<?= gettext('Define match visibility');?>",numberDisplayed: 0,nSelectedText  : "<?= gettext('option(s) selected');?>",disableIfEmpty:true,allSelectedText: "", maxHeight:400, buttonClass: 'form-control form-control-sm',
    });

    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    })
    
    function enableDisableRequiredOption(i,v){
        let id = i+'_is_required';
        if (v!='0'){
            $('#'+id).prop('disabled', false);
        } else {
            $('#'+id).prop('selectedIndex',0);
            $('#'+id).prop('disabled', true);
        }
    }
    function onAllowChapterSelectionChange(sel_type) {
        if (sel_type=='by_user_exactly_one' || sel_type=='by_user_atleast_one') {
            $('#allow_chapter_selection').prop('checked', true); // Force selection
        }
        if ($('#allow_chapter_selection').is(':checked')) {
            $('#chapter_selection_label').prop('disabled', false);
        } else {
            $('#chapter_selection_label').prop('disabled', true);
        }

    }
</script>

<?php if (0) { /* DO NOT ENABLE: Disabled as we will retain this featre on the Admin Panel */ ?>
<script>
    function deleteSurveyDataConfirmation(){
        $('#deleteSurveyDataConfirmationModal').modal({
            backdrop: 'static',
            keyboard: false
        });
    }
    function initDeleteSurveyResponse(){
        var v = $("#confirmChangeSurvey").val();
        if (v =='Yes, permanently delete the survey responses'){
            $("#action_button_survey").html('<button class="btn btn-danger" onclick="deleteTeamJoinRequestSurveyData(\'<?= $_COMPANY->encodeId($groupid);?>\');" ><?= gettext('Submit'); ?></button>');
        } else {
            $("#action_button_survey").html('<button class="btn btn-outline-danger no-drop" disabled ><?= gettext('Submit'); ?></button>');
        }
    }

    function confirmationInput(v){
        if ($(v).is(':checked')) {
            $('#confirmChangeModal').modal({
				backdrop: 'static',
				keyboard: false
			});
        } else {
            updateSurveyDownloadSetting('<?= $_COMPANY->encodeId($groupid);?>',0);
        }

    }

    function initDeleteAccount(){
        var v = $("#confirmChange").val();
        if (v =='I agree'){
            $("#action_button").html('<button class="btn btn-primary" onclick="updateSurveyDownloadSetting(\'<?= $_COMPANY->encodeId($groupid);?>\',1);" ><?= gettext('Submit'); ?></button>');
        } else {
            $("#action_button").html('<button class="btn btn-primary no-drop" disabled ><?= gettext('Submit'); ?></button>');
        }
    }

    function updateSurveyDownloadSetting(g,s){
        $("#confirmChange").val('');
        $.ajax({
            url: 'ajax_talentpeak.php?updateSurveyDownloadSetting=1',
            type: "POST",
            data: {'groupid':g,'status':s},
            success : function(data) {
                swal.fire({title: 'Success',text:'Updated successfully'});
                $('#confirmChangeModal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            }
        });
    }
   
</script>
<?php } ?>


<script>
    $(document).ready(function() {
        $(".criteria-dropdown").on("change", function() {
            let row = $(this).closest("tr");
            updateHelpText(row);
        });

        // Initialize all help texts on load
        $("tbody tr").each(function() {
            updateHelpText($(this));
        });
        $(".range-min, .range-max").on("input", function () {
            let row = $(this).closest("tr");
            updateHelpText(row);
        });
    });
</script>
