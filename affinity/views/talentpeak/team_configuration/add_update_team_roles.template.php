<?php
$groupProgramType = $group->getTeamProgramType();
$teamCustomName = Team::GetTeamCustomMetaName($groupProgramType);
list(
    $welcomeEmailSubject,
    $welcomeEmailMessage
    ) = Team::GetEmailSubjectAndMessageForTeamStatusChange($group, Team::STATUS_ACTIVE, $edit);
list(
    $completionEmailSubject,
    $completionEmailMessage
    ) = Team::GetEmailSubjectAndMessageForTeamStatusChange($group, Team::STATUS_COMPLETE, $edit);

list (
    $memberTerminationSubject,
    $memberTerminationMessage
) = EmailHelper::EmailTemplateForOnTeamMemberTermination($group,$edit);

$mentorData = array();
if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']){ // call a method in case of circle type
  $mentorData = Team::GetProgramTeamRoles($groupid,0,2); //Get Mentors role only 
}
?>
<div id="addUpdateRoleModal" class="modal fade" role="dialog">
    <div aria-label="<?= $pageTitle; ?>" class="modal-dialog modal-lg modal-dialog-w1000">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= $pageTitle; ?></h4>
                <button  aria-label="<?= gettext("close");?>" type="button" id="btn_close" class="close" data-dismiss="modal">&times;</button>
              
            </div>
            <div class="modal-body">
				<div class="col-md-12">
                    <form id="addUpdateTeamRoleForm" class="form-horizontal" method="post" action="">

                        <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                        <div class="form-group">
                            <p class="col-lg-12 control-label"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                        </div>
                        <!-- Basic info block -->
                        <div class="col-12 form-group-emphasis">
                            <div class="form-group">

                                    <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Role Name and Type")?></h5>
                                    <p class="form-group-emphasis-heading-text">
                                        
                                    </p>

                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="input_role_type" class="col-md-12 control-label"><?= sprintf(gettext('%s Role Type Name'),$teamCustomName); ?><span style="color:red;"> *</span></label>
                                        <div class="col-md-12">
                                            <input id="input_role_type" class="form-control" placeholder="<?= sprintf(gettext('%s Role type'),$teamCustomName); ?>" name="type"  type="text" value="<?= $edit ? $edit['type'] : ''; ?>" required>
                                        </div>
                                    </div>

                                    <?php if($groupProgramType!= Team::TEAM_PROGRAM_TYPE['NETWORKING']){ ?>
                                    <div class="form-group">
                                        <div class="form-check ml-3">
                                            <?php if ($edit['sys_team_role_type'] == 1) { ?>
                                                <input type="hidden" name="hide_on_request_to_join" value="1">
                                                <input id="hide_role_checkbox" type="checkbox" name="hide_on_request_to_join_hidden" value="1" class="form-check-input" id="hide_on_request_to_join" checked disabled >
                                            <?php } else { ?>
                                            <input id="hide_role_checkbox" type="checkbox" name="hide_on_request_to_join" value="1" class="form-check-input" id="hide_on_request_to_join" <?= $edit && $edit['hide_on_request_to_join'] ? 'checked' : ''; ?>>
                                            <?php } ?>
                                            <label for="hide_role_checkbox" class="form-check-label"><?= gettext("Hide this role from Registration options screen"); ?>?</label>
                                        </div>
                                    </div>
                                    <?php } ?>
                                <?php if($groupProgramType== Team::TEAM_PROGRAM_TYPE['NETWORKING']){ ?>
                                        <input  type="hidden" name="sys_team_role_type" id="sys_team_role_type" value="<?= $_COMPANY->encodeId(3);?>" >
                                <?php } else{ ?>
                                    <div class="form-group">
                                        <label for="sys_team_role_type" class="col-md-12 control-label"><?= sprintf(gettext('%s System Role type'),$teamCustomName); ?><span style="color:red;"> *</span></label>
                                        <div class="col-md-12">
                                            <select class="form-control" name="sys_team_role_type" id="sys_team_role_type" onchange="updateMemberCountRestriction(this.value)" required>
                                            <option value="" <?= $edit ? 'disabled' : ''; ?>><?= gettext("Select a system role type")?></option>
                                            <?php foreach(Team::SYS_TEAMROLE_TYPES as $key=>$value){
                                                
                                                if (in_array($key,$restrictSystemRoles)) {
                                                    continue;
                                                }

                                                $sel = '';
                                                if ($edit && $edit['sys_team_role_type'] == $key){
                                                    $sel = 'selected';
                                                }
                                                $disabled = "";
                                                if ($edit && $edit['sys_team_role_type'] != $key){
                                                    $disabled = "disabled";
                                                }

                                                 if (!($edit) && count($mentorData) >= 1 && $key == 2 ){ // If already mentor exist then disable mentor option for circle
                                                     $disabled = "disabled";
                                                 }
                                            ?>
                                            <?php 
                                                if ($groupProgramType == Team::TEAM_PROGRAM_TYPE['CIRCLES'] && $key == 1){ // Do not allow Admin type on CIRCLES type
                                                    continue;
                                                }
                                            ?>
                                            
                                            <?php if ($groupProgramType != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){
                                                if ($key == 0 || $key == 4){
                                                    continue;
                                                }
                                            ?>
                                                <option value="<?= $_COMPANY->encodeId($key);?>" <?= $disabled; ?> <?= $sel; ?>><?= $value; ?></option>
                                            <?php } else { ?>
                                                <?php if($key == 4){ ?>
                                                    <option value="<?= $_COMPANY->encodeId($key);?>" <?= $disabled; ?>  <?= $sel; ?>><?= $value; ?></option>
                                                <?php } ?>
                                            <?php } ?>
                                            <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <?php if($groupProgramType == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER']){ ?>
                                    <div class="form-group" id="auto_match_with_mentor_div" style="display:<?= $edit ? ($edit['sys_team_role_type'] == 3 ? 'block' : 'none') : 'none'; ?>;">
                                        <div class="form-check ml-3">
                                            <input type="checkbox" name="auto_match_with_mentor" value="1" class="form-check-input" id="auto_match_with_mentor" <?= $edit && $edit['auto_match_with_mentor'] ? 'checked' : ''; ?>>
                                            <label for="auto_match_with_mentor" class="form-check-label"><?= gettext("Auto Match with Mentor"); ?></label>
                                        </div>
                                    </div>
                                    <?php } ?>

                            <?php } ?>
                                </div>
                            </div>
                        </div>

                        <!-- Registration email block -->
                        <div class="col-md form-group-emphasis">
                            <div class="form-group">

                                <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Registration Email Template")?></h5>
                                <p class="form-group-emphasis-heading-text">
                                    <?= sprintf(gettext('This section lets you set up an email template to send to users after they register for this role')); ?>
                                </p>


                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-12" for="email_subject"><?= gettext("Email Subject"); ?></label>
                                    <div class="col-md-12">
                                        <input id="email_subject" class="form-control" name="joinrequest_email_subject" placeholder="<?= sprintf(gettext('Your %1$s registration for %2$s role has been received'),$group->val('groupname'),($edit ? $edit['type']: '...')); ?>" value="<?= !empty($edit['joinrequest_email_subject']) ? htmlspecialchars($edit['joinrequest_email_subject']) : '' ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-md-12" for="redactor_content_request_email"><?= gettext("Email Template"); ?></label>
                                    <div id="post-inner" class="col-md-12">
                                        <textarea class="form-control" name="joinrequest_message" rows="10" id="redactor_content_request_email" maxlength="8000" placeholder="<?= gettext("You can customize the email message that will be sent out when someone requests to join this role"); ?>"><?= !empty($edit['joinrequest_message']) ? htmlspecialchars($edit['joinrequest_message']) : ''; ?></textarea>
                                        <p class="text-sm">
                                            <?= sprintf(
                                                    gettext("Note: You can use the following variables: %s which will be replaced by actual values when sending out the emails."),
                                                '[[PERSON_FIRST_NAME]], [[PERSON_LAST_NAME]], [[PERSON_JOB_TITLE]], [[MANAGER_FIRST_NAME]], [[MANAGER_LAST_NAME]], [[MANAGER_EMAIL]]' . ($_ZONE->val('app_type') == 'peoplehero' ? ', [[PERSON_START_DATE]]' : '')
                                            )?>.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>

                        <!-- Activation email block -->
                        <div class="col-12 form-group-emphasis">
                            <div class="form-group">

                                <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=sprintf(gettext("%s Activation Email Template"),$teamCustomName)?></h5>
                                <p class="form-group-emphasis-heading-text">
                                    <?= sprintf(gettext('This section lets you set up an email template to send email to %1$s members upon %1$s Activation.'),$teamCustomName); ?>.
                                    <?= sprintf(gettext('This email template is also used for sending email to new members who are added to an active %1$s.'),$teamCustomName); ?>
                                </p>


                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-12" for="welcome_email_subject"><?= sprintf(gettext('Email Subject'),$teamCustomName); ?></label>
                                    <div class="col-md-12">
                                        <input class="form-control" id="welcome_email_subject" name="welcome_email_subject" placeholder="<?= gettext("Welcome email subject here") . ' ... ' . gettext("(will be reset to default if empty)"); ?>" value="<?= $welcomeEmailSubject ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-md-12" for="redactor_content_welcome_email"><?= sprintf(gettext('Email Template'),$teamCustomName); ?></label>
                                    <div id="post-inner" class="col-md-12">
                                        <textarea class="form-control" name="welcome_message" rows="10" id="redactor_content_welcome_email" maxlength="8000" placeholder="<?= gettext("Welcome message here") . ' ... ' . gettext("(will be reset to default if empty)"); ?>"><?= $welcomeEmailMessage ?></textarea>
                                        <p class="text-sm">
                                            <?=sprintf(
                                                gettext('You can use the following variables: %s which will be replaced by actual values when sending out the emails.'),
                                                '[[PERSON_FIRST_NAME]], [[PERSON_LAST_NAME]], [[TEAM_NAME]], [[TEAM_MEMBER_ROLE_AND_TITLE]], [[TEAM_URL]], [[MY_TEAMS_SECTION_URL]], [[MY_TEAMS_SECTION_URL]], [[TEAM_URL_BUTTON]], [[MY_TEAMS_SECTION_URL_BUTTON]], [[UPCOMING_ACTION_ITEMS]], [[UPCOMING_TOUCHPOINTS]], [[TEAM_MEMBER_LIST]], [[PERSON_JOB_TITLE]], [[MANAGER_FIRST_NAME]], [[MANAGER_LAST_NAME]], [[MANAGER_EMAIL]]' . ($_ZONE->val('app_type') == 'peoplehero' ? ', [[PERSON_START_DATE]]' : '')
                                            )?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>

                        <!-- Complete email block -->
                        <div class="col-12 form-group-emphasis">
                            <div class="form-group">

                                <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=sprintf(gettext("%s Completion Email Template"),$teamCustomName)?></h5>
                                <p class="form-group-emphasis-heading-text">
                                    <?= sprintf(gettext('This section lets you set up an email template to send to %1$s members upon %1$s Completion'),$teamCustomName); ?>
                                </p>


                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-12" for="completion_email_subject"><?= sprintf(gettext('Email Subject'),$teamCustomName); ?></label>
                                    <div class="col-md-12">
                                        <input class="form-control" id="completion_email_subject" name="completion_email_subject" placeholder="<?= gettext("Completion email subject here"). ' ... ' . gettext("(will be reset to default if empty)"); ?>" value="<?= $completionEmailSubject ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-md-12" for="redactor_content_completion_email"><?= sprintf(gettext('Email Template'),$teamCustomName); ?></label>
                                    <div id="post-inner" class="col-md-12">
                                        <textarea class="form-control" name="completion_message" rows="10" id="redactor_content_completion_email" maxlength="8000" placeholder="<?= gettext("Completion message here") . ' ... ' . gettext("(will be reset to default if empty)"); ?>"><?= $completionEmailMessage; ?></textarea>
                                        <p class="text-sm">
                                            <?=sprintf(
                                                gettext('You can use the following variables: %s which will be replaced by actual values when sending out the emails.'),
                                                '[[PERSON_FIRST_NAME]], [[PERSON_LAST_NAME]], [[TEAM_NAME]], [[TEAM_MEMBER_ROLE_AND_TITLE]], [[TEAM_URL]], [[MY_TEAMS_SECTION_URL]], [[MY_TEAMS_SECTION_URL]], [[TEAM_URL_BUTTON]], [[MY_TEAMS_SECTION_URL_BUTTON]], [[UPCOMING_ACTION_ITEMS]], [[UPCOMING_TOUCHPOINTS]], [[TEAM_MEMBER_LIST]], [[PERSON_JOB_TITLE]], [[MANAGER_FIRST_NAME]], [[MANAGER_LAST_NAME]], [[MANAGER_EMAIL]]' . ($_ZONE->val('app_type') == 'peoplehero' ? ', [[PERSON_START_DATE]]' : '')
                                            )?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>

                        <!-- Discover Tab options block / applicable only for Peer to Peer -->
                        <?php if ($groupProgramType == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER']) { ?>
                        <div class="col-12 form-group-emphasis">
                            <div class="form-group">

                                    <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Discover Tab Options")?></h5>
                                    <p class="form-group-emphasis-heading-text">
                                        <?= sprintf(gettext('This section allows you to configure how matches for this role appear in the Discover tab.'),$teamCustomName); ?>
                                    </p>


                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label col-md-12" for="discover_tab_show"><?= sprintf(gettext('Display Matches On Discover Tab'),$teamCustomName); ?></label>
                                        <div class="col-md-12">

                                            <select id="discover_tab_show" class="form-control" name="discover_tab_show" >
                                                <?php
                                                $discover_tab_show_optons = [
                                                        '1' => gettext('Yes'),
                                                        '0' => gettext('No')
                                                ];
                                                foreach($discover_tab_show_optons as $key => $item){
                                                    $sel = ($edit && isset($edit['discover_tab_show']) && $key == $edit['discover_tab_show']) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?= $key ;?>" <?= $sel; ?>><?= $item ;?></option>
                                                <?php } ?>
                                            </select>

                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="control-label col-md-12" for="redactor_discover_tab_html"><?= gettext('Discover Tab Role Match Description'); ?></label>
                                        <div id="post-inner" class="col-md-12">
                                            <textarea class="form-control" name="discover_tab_html" rows="4" id="redactor_discover_tab_html" maxlength="2000" placeholder="<?=sprintf('Based on your registration information here are the %s matches recommended for you to connect with:', ($edit ? $edit['type'] : 'Role')   )?>"><?= $edit ? htmlspecialchars($edit['discover_tab_html']) : ''; ?></textarea>
                                            <p><?=gettext('The description you set above will replace the default description in corresponding recommended Matches section of this role on the Discover Tab')?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>

                        <!-- Min / Max allowed block -->
                        <?php if ($groupProgramType == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']) { ?>
                        <input name="min_required" type="hidden" value="1">
                        <input name="max_allowed" type="hidden" value="1">
                        <?php } elseif ($groupProgramType == Team::TEAM_PROGRAM_TYPE['NETWORKING']) { ?>
                        <input name="min_required" type="hidden" value="2">
                        <input name="max_allowed" type="hidden" value="2">
                        <?php } else { ?>
                        <div class="col-12 form-group-emphasis">
                            <div class="form-group">


                                    <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Member count restrictions")?></h5>
                                    <p class="form-group-emphasis-heading-text">
                                        <?= sprintf(gettext('This section lets you set the minimum number of members of this role that are required to start a %1$s and a maximum number of members of this role that can be added to the %1$s'),$teamCustomName); ?>
                                    </p>


                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="min_required" class="col-lg-12 control-label"><?= gettext("Minimum Required"); ?><span style="color:red;"> *</span></label>
                                        <div class="col-lg-3">
                                            <input id="min_required" class="form-control text-left" placeholder="<?= gettext("Minimum Members Required"); ?>" name="min_required" <?= $edit && ($groupProgramType == Team::TEAM_PROGRAM_TYPE['CIRCLES'])  ? 'readonly' : ($groupProgramType == Team::TEAM_PROGRAM_TYPE['CIRCLES'] ? 'readonly' : ''); ?> type="number" min="0" value="<?= $edit ? $edit['min_required'] : 0; ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="max_allowed" class="col-lg-12 control-label"><?= gettext("Maximum Allowed"); ?><span style="color:red;"> *</span></label>
                                        <div class="col-lg-3">
                                            <input id="max_allowed" class="form-control" placeholder="<?= gettext("Maximum Members Allowed"); ?>" min="1" name="max_allowed"  type="number" <?= $edit ? (($groupProgramType == Team::TEAM_PROGRAM_TYPE['CIRCLES']) && ($edit['sys_team_role_type'] == 2) ? 'readonly' : '') : ($groupProgramType == Team::TEAM_PROGRAM_TYPE['CIRCLES'] ? 'readonly' : ''); ?> value="<?= $edit ? $edit['max_allowed'] : 1; ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>

                        <!-- Role capacity block -->
                        <?php if ($groupProgramType == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
                        <input type="hidden" value="1" name="role_capacity">
                        <?php  } elseif ($groupProgramType== Team::TEAM_PROGRAM_TYPE['NETWORKING']){ ?>
                        <input type="hidden" value="1" name="role_capacity">
                        <?php } else { ?>
                        <div class="col-12 form-group-emphasis">
                            <div class="form-group">


                                    <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Role Capacity")?></h5>
                                    <p class="form-group-emphasis-heading-text">
                                        <?= sprintf(gettext('This section lets you set the maximum role capacity that should be allowed when user is given an option to select their role capacity, e.g. if you want Mentors to mentor at most 3 %s then set this value to 3 for the mentors'),Team::GetTeamCustomMetaName($groupProgramType,1)); ?>
                                    </p>


                                <div class="col-md-12">
                                    <div class="form-group" id="role_capacity_input" >
                                        <label for="maximum_capacity" class="col-lg-12 control-label"><?= gettext("Maximum Capacity"); ?><span style="color:red;"> * </span></label>
                                        <div class="col-lg-3">
                                            <select id="maximum_capacity" class="form-control" name="role_capacity" >
                                                <?php
                                                foreach([1,2,3,4,5,6,7,8,9,10,20,30,40,50,100,0] as $item){
                                                    $sel = ($edit && isset($edit['role_capacity']) && $item == $edit['role_capacity']) ? 'selected' : '';
                                                ?>
                                                <option value="<?= $item ;?>" <?= $sel; ?>><?= $item == 0 ? gettext('Unlimited') : $item;?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <?php if (
                                        0 /* Disabled feature as we currently do not have a clear definition */
                                        && ($groupProgramType == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER'])
                                    ) { ?>
                                    <div class="form-group" id="role_capacity_input" >
                                        <label for="role_request_buffer" class="col-lg-12 control-label"><?= gettext("Outstanding Request Buffer"); ?><span style="color:red;"> * </span><i class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('This field defines the number of additional outstanding requests a role can allow beyond its available capacity.')?>"></i></label>
                                        <div class="col-lg-3">
                                            <input class="form-control" type="number" name="role_request_buffer" id="role_request_buffer" value="<?= $edit ? $edit['role_request_buffer'] : 0; ?>">
                                        </div>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
            <?php if(in_array($groupProgramType,[Team::TEAM_PROGRAM_TYPE['PEER_2_PEER'],Team::TEAM_PROGRAM_TYPE['ADMIN_LED']])){ ?>
                        <div class="col-12 form-group-emphasis">
                            <div class="form-group">
                                <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Registration Limits")?></h5>
                                <p class="form-group-emphasis-heading-text">
                                    <?= sprintf(gettext('Set the maximum number of users who can register for this role. Enter 0 to allow unlimited registrations.  When the limit is reached, new registrations will be blocked.')); ?>
                                </p>
                                <div class="col-md-12">
                                    <div class="form-group" id="role_capacity_input" >
                                        <label for="maximum_registrations" class="col-lg-12 control-label"><?= gettext("Maximum Registrations"); ?><span style="color:red;"> * </span></label>
                                        <div class="col-lg-3">
                                            <input type="number" value="<?= $edit ? $edit['maximum_registrations'] : '1'; ?>" min='1' id='maximum_registrations' name='maximum_registrations'>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php } ?>

                        <?php } ?>

                        <!-- Registration start/end date block -->
                        <?php if ($groupProgramType != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
                        <div class="col-12 form-group-emphasis">
                            <div class="form-group">


                                    <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Registration Window")?></h5>
                                    <p class="form-group-emphasis-heading-text">
                                        <?= gettext("This section allows you to set the registration start and end dates. Users will be allowed to sign up only during the time period between registration start and end dates."); ?>
                                    </p>

                                
                                <div class="col-md-6">

                                    <div class="form-group">
                                        <label for="start_date" class="col-lg-4 control-label"><?= gettext("Start Date"); ?></label>
                                        <div class="col-lg-8">
                                            <input type="text" onchange="validateRegistrationWindow()" value="<?= $edit ? $edit['registration_start_date'] : '';?>" class="form-control" id="start_date" name="registration_start_date"  placeholder="YYYY-MM-DD">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="end_date" class="col-lg-4 control-label"><?= gettext("End Date"); ?></label>
                                        <div class="col-lg-8">
                                            <input type="text" onchange="validateRegistrationWindow()" value="<?= $edit ? $edit['registration_end_date'] : '';?>"  class="form-control" id="end_date" name="registration_end_date"  placeholder="YYYY-MM-DD">
                                        </div>
                                    </div>

                                </div>
                                <div class="col-md-6 pt-4">
                                    <button class="btn btn-affinity" onclick="$('#start_date').val('');$('#end_date').val('')" type="button"><?= gettext('Reset Dates')?></button>
                                </div>
                            </div>
                        </div>
                        <?php } ?>

                        <!-- Role Restrictions block -->
                        <?php if ($groupProgramType != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
                        <div class="col-12 form-group-emphasis">
                            <div class="form-group">


                                    <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Role Registration Restrictions")?></h5>
                                    <p class="form-group-emphasis-heading-text">
                                        <?= gettext("This section allows you to set role restrictions to ensure only users who meet the criteria specified below can register for this role"); ?>
                                    </p>


                                <div class="col-md-12">
                                <?php foreach($catalog_categories as $category){ ?>
                                    <div class="form-group">
                                        <label for="<?= trim($category); ?>" class="col-lg-2 control-label"><?= $category; ?></label>
                                        <div class="col-lg-5">
                                            <select class="form-control" name="<?= $category; ?>" id="<?= trim($category); ?>" onchange="showHideCategoryKeys(this.value,'<?= $category; ?>')">
                                                <option value="0">Ignore</option>
                                                <option value="1" <?= !empty($editRestrictions) && $editRestrictions[$category]['type'] == 1 ? 'selected' : ''; ?> >In</option>
                                                <option value="2" <?= !empty($editRestrictions) && $editRestrictions[$category]['type'] == 2 ? 'selected' : ''; ?>>Not In</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-5">
                                            <select class="form-control multi_select" name="<?= $category; ?>_val[]" id="<?= $category;?>_val" multiple="multiple" style="width:100%;" >
                                                <option value="">Select a value</option>
                                                <?php foreach(UserCatalog::GetAllCategoryKeys($category) as $key){ 
                                                    $sel = '';
                                                    if ($edit && !empty($editRestrictions[$category]) && in_array($key,$editRestrictions[$category]['keys'])){
                                                        $sel = 'selected';
                                                    }
                                                ?>
                                                <option value="<?= $key ;?>" <?= $sel; ?>><?= $key ;?></option>
                                            <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                <?php } ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 form-group-emphasis">
                            <div class="form-group">
                                <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Handle User Terminations")?></h5>
                                <p class="form-group-emphasis-heading-text"></p>
                                <div class="col-md-12">
                                    <div class="form-group" id="role_capacity_input" >
                                        <label for="action_on_member_termination" class="col-lg-12 control-label"><?= gettext("When a member with this role leaves the organization"); ?></label>
                                        <div class="col-lg-12">
                                            <?php
                                                $roleLeaveOptions = Team::GetRoleOptionsOnLeaveMemberTermination($group->id());
                                            ?>
                                            <select id="action_on_member_termination" class="form-control" name="action_on_member_termination" >
                                                    <option value=""><?= gettext("Select an option"); ?></option>
                                                <?php foreach($roleLeaveOptions as $key => $value){
                                                    $sel = '';
                                                    if ($edit && $edit['action_on_member_termination'] == $key) {
                                                        $sel = 'selected';
                                                    }
                                                    
                                                ?>
                                                    <option value="<?= $key; ?>" <?= $sel; ?>><?= $value; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group ml-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1" name="email_on_member_termination" id="email_on_member_termination" <?= $edit ? ($edit['email_on_member_termination'] ? 'checked' : '') : 'checked'; ?> >
                                            <label class="form-check-label" for="email_on_member_termination">
                                                <?= sprintf(gettext('Send email notification to remaining %s members'),$teamCustomName); ?>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="control-label col-md-12" for="member_termination_email_subject"><?= sprintf(gettext('Email Subject'),$teamCustomName); ?></label>
                                        <div class="col-md-12">
                                            <input class="form-control" id="member_termination_email_subject" name="member_termination_email_subject" placeholder="<?= $memberTerminationSubject; ?>" value="<?= $memberTerminationSubject ?>">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="control-label col-md-12" for="redactor_content_completion_email"><?= gettext('Email Body'); ?></label>
                                        <div id="post-inner" class="col-md-12">
                                            <textarea class="form-control" name="member_termination_message" rows="10" id="redactor_content_completion_email" maxlength="8000" placeholder="<?= gettext("Message here") . ' ... ' . gettext("(will be reset to default if empty)"); ?>"><?= $memberTerminationMessage; ?></textarea>
                                            <p class="text-sm">
                                                <?=sprintf(
                                                    gettext('You can use the following variables: %s which will be replaced by actual values when sending out the emails.'),
                                                        '[[PERSON_FIRST_NAME]], [[PERSON_LAST_NAME]], [[TEAM_NAME]], [[TEAM_MEMBER_WHO_LEFT]], [[TEAM_MEMBER_WHO_LEFT_ROLE]], [[PERSON_JOB_TITLE]], [[MANAGER_FIRST_NAME]], [[MANAGER_LAST_NAME]], [[MANAGER_EMAIL]]' . ($_ZONE->val('app_type') == 'peoplehero' ? ', [[PERSON_START_DATE]]' : '')
                                                )?>
                                            </p>
                                        </div>
                                    </div>
                                       
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    </form>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="modal-footer text-center" style='text-align:center !important;display: block;'>
                <button type="submit" class="btn btn-affinity prevent-multiple-submit" onclick="addUpdateProgramRole('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($id); ?>')" ><?= gettext("Submit");?></button>
                <button type="submit" class="btn btn-affinity" data-dismiss="modal" ><?= gettext("Close");?></button>
            </div>
        </div>
    </div>
</div>

<script>
        $(document).ready(function(){
            $('#redactor_content_request_email').initRedactor('redactor_content_request_email','teamtasks',['counter']);
            $('#redactor_content_welcome_email').initRedactor('redactor_content_welcome_email','teamtasks',['counter']);
            $('#redactor_discover_tab_html').initRedactor('redactor_discover_tab_html','',['counter']);
            $(".redactor-voice-label").text("<?= gettext('Add description');?>");
            $('#redactor_content_completion_email').initRedactor('redactor_content_completion_email','teamtasks',['counter']);
            setTimeout(() => {
                $('.multi_select').select2(
                    {
                        placeholder: "<?= gettext('Select or search options'); ?>",
                        maximumSelectionLength: 5
                    }
                );
            },200)

            redactorFocusOut('#input_role_type'); // function used for focus out from redactor when press shift +tab.
        });

        function showHideCategoryKeys(v,c){
            if (v){
                $('#'+c+'_div').show();
            }else{
                $('#'+c+'_div').hide();
            }
        }

        function showOrHideCapacityInput(v){
            $.ajax({
                url: 'ajax_talentpeak.php?showOrHideCapacityInput=1',
                type: "GET",
                data: {'sys_role_type':v},
                success: function(data) {
                    if (data == 1){
                        $("#role_capacity_input").show();
                    } else {
                        $("#role_capacity_input").hide();
                    }
                }
            });
        }

        $( "#start_date" ).datepicker({
            prevText:"click for previous months",
            nextText:"click for next months",
            showOtherMonths:true,
            selectOtherMonths: false,
            dateFormat: 'yy-mm-dd',
            onSelect: function (date) {
                var date2 = $('#start_date').datepicker('getDate');
                $('#end_date').datepicker('option', 'minDate', date2);
            }
        });
        $( "#end_date" ).datepicker({
            prevText:"click for previous months",
            nextText:"click for next months",
            showOtherMonths:true,
            selectOtherMonths: true,
            dateFormat: 'yy-mm-dd' 
        });

$('#addUpdateRoleModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
});

$("input:checkbox").keypress(function (event) {
if (event.keyCode === 13) {
	$(this).click();
}
});

$(function () {
    $('[data-toggle="tooltip"]').tooltip();
})

function updateMemberCountRestriction(v) {

    <?php  if ($groupProgramType == Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?>

        let max_allowed = $("#max_allowed");
        let min_required = $("#min_required");
        if (v == '<?= $_COMPANY->encodeId(2);?>') {
            max_allowed.val('1');
            min_required.val('1');
            max_allowed.prop("readonly", true);
            min_required.prop("readonly", true);
        } else if(v == '<?= $_COMPANY->encodeId(3);?>') {
            max_allowed.val('1');
            min_required.val('0');
            max_allowed.prop("readonly", false);
            min_required.prop("readonly", true);
        } else {
            max_allowed.val('1');
            min_required.val('0');
            max_allowed.prop("readonly", false);
            min_required.prop("readonly", false);
        }
    <?php } elseif($groupProgramType == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER']){ ?>
        $("#auto_match_with_mentor_div").hide();

        if (v == '<?= $_COMPANY->encodeId(2);?>') {
            $("#auto_match_with_mentor_div").hide();
        } else if(v == '<?= $_COMPANY->encodeId(3);?>') {
            $("#auto_match_with_mentor_div").show();
        }
    <?php } ?>
}
</script>

