<?php include_once __DIR__.'/../common/init_meeting_link_generator.php'; ?>
<style>
	.fa.fa-times {
		color: #f80e0e;
		background-color: #fff;
		position: absolute;
		margin-left: 0px;
	}
	.form-group {
		padding-top: 4px;
	}
	.multiday{
		margin-top:7px !important;
	}
	a.disclaimerbtn {
    color: #0077b5;
    text-decoration: underline;
    cursor: pointer;
}
a.disclaimerbtn:hover {
    color: #0077b5;
    text-decoration: underline;
    cursor: pointer;
}
div#customEmailReply {
    margin-left: -12px;
    margin-right: 12px;
}
.other-section-checkbox{
	margin-left:1px;
}

.select2-container .select2-selection--single {
	height: calc(1.5em + .75rem + 2px);
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
	line-height: 33px;
}
.select2-container--default .select2-selection--single {
	border: 1px solid #dbdbdb;
}
.custom-switch .custom-control-label::after {
   background-color: #525960;
}
.form-group-emphasis-dark {
    font-size: 1.25rem;
}
.label-text label{
    width: auto;
    padding-right: 5px;
}
</style>
<div class="container inner-background input_form_container">
	<div class="row row-no-gutters">
		<div class="col-md-12">
			<div class="inner-page-title">
				<h2><?= $eventFromTitle; ?></h2>
				<hr class="linec" >
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-container">
				<form class="form-horizontal" method="post" id="new-event-data">
                    <input type="hidden" name="action" id="action" value="<?= $action;?>" />
					<input type="hidden" name="parent_groupid" value="<?= $_COMPANY->encodeId($parent_groupid); ?>" />
                    <input type="hidden" name="groupid" value="<?= $_COMPANY->encodeId($groupid); ?>" />
					<input type="hidden" id="eventid" name="eventid" value="<?= $_COMPANY->encodeId($eventid) ?>" />
					<input type="hidden" name="event_series_id" value="<?= $_COMPANY->encodeId($event_series_id); ?>" />
					<input type="hidden" id="allow_past_date_event" name="allow_past_date_event" value='0'>
					<input type="hidden" id="is_it_past_date_event" value='<?= $isItPastDateEvent; ?>'>
					<input type="hidden" class="form-control" name="version" value="<?= $_COMPANY->encodeId($eventVersion) ?>" /> 
                    <p class="mb-3"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                    <!-- Event Name Section: Start -->
                    <div class="col-12 form-group-emphasis">
                        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext('Event Name');?></h3>
                            <div class="form-group">
                                <label class="control-lable col-sm-12" for="eventtitle"><?= gettext('Name');?>
                                    <?php if($allowTitleUpdate ){ ?>
                                    <span style="color: #ff0000;"> *</span>
                                    <?php } else { ?>
                                    <i class="fa fa-info-circle fa-small info-black" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext('%s editing is disabled because the %s has been submitted for approval.'), gettext("Event Name"), gettext("Event"))?>"></i>
                                    <?php } ?>
                                </label>
                                <div class="col-sm-12">
                                    <input type="text" id="eventtitle" name="eventtitle" value="<?= $eventid ? $event->val('eventtitle') : ''; ?>" required class="form-control" placeholder="<?= gettext('Event Name');?>" <?= !$allowTitleUpdate ? 'readonly' : ''; ?>  />
                                </div>
                            </div>
                    </div>
                    <!-- Event Name Section: End -->

                    <!-- Event Scope Section: Start -->
					<div class="col-12 form-group-emphasis" style="display:<?= ($event_series_id || ($event && $event->val('groupid') !=0 && (!$event->isDraft() && !$event->isUnderReview()))) ? 'none' : 'block'; ?>;">
						<h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= sprintf(gettext('%s'),$_COMPANY->getAppCustomization()['group']["name"])?></h3>
                    <?php if ($eventid ||  (($global ==1) && $_USER->isAdmin()) || $_USER->canPublishContentInGroup($groupid) || $_USER->canCreateContentInGroupSomeChapter($groupid)) { ?>
                    
                        <?php if ($event_series_id<1) { ?>
							<!-- Show Admin dropdown -->
							<?php if ($eventid){ ?>
                                <?php if ($event->val('groupid') == 0 && ($_USER->isAdmin() || $event->loggedinUserCanUpdateEvent())) { ?>
                                    <input type="hidden" name="global" id="global" value="1"/>
                                    <?php if ($event->isDraft() || $event->isUnderReview()) { ?>
										<?php if ($event->val('collaborating_groupids') || $event->val('collaborating_groupids_pending')) { ?>
										<input type="hidden" name="event_scope" value="collaborating_groups"/>
										<script>
											getGroupsForCollaboration('<?= $_COMPANY->encodeId($event->id())?>','<?= $_COMPANY->encodeId($parent_groupid)?>');
										</script>
										<?php } elseif($_COMPANY->getAppCustomization()['dynamic_list']['enabled'] && $event->val('listids')){ ?>
											<input type="hidden" id="event_scope" name="event_scope" value="dynamic_list"/>
                                            <label class="control-lable col-sm-12"><?= gettext('Create In');?></label>
                                            <div class="col-sm-12">
                                                <strong><?= sprintf(gettext('This event will be published to %1$s dynamic lists of this zone'),DynamicList::GetFormatedListNameByListids($event->val('listids')) ?: '[not found]');?></strong>
                                            </div>
										<?php } elseif ($event->val('groupid') == 0) { ?>
											<input type="hidden" name="event_scope" value="zone"/>
											<div class="form-group">
												<label class="control-lable col-sm-12"><?= gettext('Create In');?></label>
												<div class="col-sm-12">
													<strong><?= sprintf(gettext('This is a global event which will be published in all %s'),$_COMPANY->getAppCustomization()['group']["name-plural"]);?></strong>
												</div>
											</div>
										<?php } ?>
                                    <?php } else { ?>
										<?php if($event->val('groupid') == 0 || $event->val('listids') !='0'){ ?>
											<div class="form-group">
												<label class="control-lable col-sm-12"></label>
												<div class="col-sm-12">
													<strong class="col-sm-12 form-admin-option m-0 p-2" >
													<?php if ($event->val('collaborating_groupids')) { ?>
														<?= sprintf(gettext('This event is in collaboration between %s'),$event->getFormatedEventCollaboratedGroupsOrChapters());?>
													<?php } elseif($_COMPANY->getAppCustomization()['dynamic_list']['enabled'] && $event->val('listids') !='0') { ?>
														<?= sprintf(gettext('This is a global event published to %s dynamic lists'),DynamicList::GetFormatedListNameByListids($event->val('listids')));?>
													<?php } elseif($event->val('groupid') == '0'){ ?>
														<?= sprintf(gettext('This is a global event published in all %s'),$_COMPANY->getAppCustomization()['group']["name-plural"]);?>
													<?php } ?>
													</strong>
												</div>
											</div>
											<?php } ?>
                                    <?php } ?>

								<?php }  elseif($event->val('groupid') && $event->val('listids') == 0){  ?>
                                    <input type="hidden" name="event_scope" value="group"/>
                                    <div class="form-group">
										<label class="control-lable col-sm-12"><?= gettext('Create In');?></label>
										<div class="col-sm-12">
											<strong><?= sprintf(gettext('This event will be published in %1$s %2$s'),$group->val('groupname'), $_COMPANY->getAppCustomization()['group']["name"]);?></strong>
										</div>
									</div>
                                <?php }else{ ?>
                                    <input type="hidden" id="event_scope" name="event_scope" value="dynamic_list"/>
                                    <div class="form-group">
										<label class="control-lable col-sm-12"><?= gettext('Create In');?></label>
										<div class="col-sm-12">
											<strong>
                                            <?= sprintf(gettext('This event will be published to %1$s dynamic lists of %2$s %3$s'),DynamicList::GetFormatedListNameByListids($event->val('listids')), $group->val('groupname'), $_COMPANY->getAppCustomization()['group']["name"]);?>
                                            </strong>
										</div>
									</div>
                                <?php } ?>

							<?php } else{ ?>
								<div class="form-group">
								<?php if(($global ==1 && $_USER->isAdmin())|| $_USER->canPublishContentInGroup($groupid)){ ?>
									<input type="hidden" name="global" id="global" value="1"/>
								<?php } ?>
									<label class="control-lable col-sm-12"><?= gettext('Create In');?> <span style="color: #ff0000;"> *</span></label>
									<div class="col-sm-12">
										<select aria-label="<?= gettext('Create In');?>" id="event_scope" class="form-control" name='event_scope' onchange="eventScopeSelector(this.value)" required>
										<?php if(($global ==1 && $_USER->isAdmin())){ ?>
											<option value="zone"><?= sprintf(gettext("All %s"),$_COMPANY->getAppCustomization()['group']["name-plural"]);?></option>
                                            <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) { ?>
                                            <option value="dynamic_list"><?= gettext("Dynamic Lists"); ?></option>
                                            <?php } ?>
                                        <?php } else { ?>
											<option value="group"><?= sprintf(gettext("This %s only"),$_COMPANY->getAppCustomization()['group']["name"]);?></option>
                                            <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled'] && ($_USER->isAdmin() || $_USER->isGroupLead($groupid))) { ?>
                                            <option value="dynamic_list"><?= sprintf(gettext("This %s Dynamic Lists"), $_COMPANY->getAppCustomization()['group']['name']) ?></option>
                                            <?php } ?>
                                        <?php } ?>

                                        <?php if($allowEventCollaboration){ ?>
											<option value="collaborating_groups"><?= sprintf(gettext("Collaborating %s"),$_COMPANY->getAppCustomization()['group']["name-plural"]);?></option>
                                        <?php } ?>
										</select>
									</div>
								</div>
							<?php } ?>

							<!-- Show Collaboration dropdown, default off -->
							<div id="collaboration_selection" style="display:none;">
								<!-- Dynamic content for group or chapter level collaboration will be shown here -->
							</div>
							<?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled'] && (!$eventid || ($event && ($event->isDraft() || $event->isUnderReview()) ) ))  { 
                                // Dynamic list prompt
							if($groupid == 0){
								$dynamic_list_info = gettext("Only the zone members who are also part of users in the selected dynamic lists will receive an email notification when you publish by email.");
							}else{
								$dynamic_list_info = sprintf(gettext("Only the %s members who are also part of users in the selected dynamic lists will receive an email notification when you publish by email."), $group->val('groupname_short'));
							}
                            ?>
								<div id="list_selection" style="display:<?= $eventid ? ($event->val('listids') !='0'  ? 'block': 'none') : 'none'; ?>;">
									<div class="form-group">
										<label for="list_scope" class="control-lable col-sm-12"><?= gettext('Select Dynamic Lists');?></label>
										<div class="col-sm-12">
											<select aria-label="<?= gettext('Select Dynamic Lists');?>" class="form-control " name="list_scope[]" id="list_scope" multiple>
												<?php
													$listids = array();
													if ($eventid){
														$listids = explode(',',$event->val('listids'));
													}
												?>
												<?php foreach($lists as $list){ ?>
													<option value="<?= $_COMPANY->encodeId($list->val('listid')); ?>"  <?= in_array($list->val('listid'),$listids) ? 'selected': ($eventid ? ($event->val('isactive') == 1 ? 'disabled' : '') : ''); ?>  ><?= $list->val('list_name'); ?></option>
												<?php } ?>
											</select>

                                            <small>
                                                <?= gettext("You can choose one or more existing dynamic lists or you can") ?>
                                                <a aria-label="Add a new dynamic list" onclick="manageDynamicListModal('<?=$_COMPANY->encodeId($groupid);?>')" href="javascript:void(0)"><?= gettext("create your own.")?></a>
                                                <?= $dynamic_list_info ?>
                                                <?= gettext("View the users associated with the selected lists: ")?><a role="button" aria-label="View users" onclick="getDynamicListUsers('<?=$_COMPANY->encodeId($groupid);?>')" href="javascript:void(0)"><?= gettext("view users")?></a>
                                            </small>
                                            
										</div>
									</div>
								</div>
							<?php } ?>
							
							<?php } else {?>
								<input type="hidden" name="event_scope" value="series"/>
							<?php } ?>
						<?php } ?>
						

							<input type="hidden" name="sendCollaborationRequestText" id="sendCollaborationRequestText" value="<?= $eventid ? (!empty($acceptancePending) ?  sprintf(gettext("The event includes %s that require approval for collaboration. Click the 'Send Collaboration Request' button to request collaboration."), $_COMPANY->getAppCustomization()['group']['name-short']) : '') : '';?>">

							<!-- Show following options only if it is not a series event -->
							<?php 	if ($event_series_id<1 && (($eventid && $event->val('groupid') > 0 && ($event->isDraft() || $event->isUnderReview())) || ($event_series_id<1 && $global == 0 && !$eventid))){ ?>
                                <?php $warn_if_all_chapters_are_selected = true; ?>
								<?php include_once __DIR__.'/../common/init_chapter_channel_selection_box.php'; ?>
							<?php } ?>

					</div>
                    <!-- Event Scope Section: End -->

                    <!-- Event Where Section: Start -->
                    <div class="col-12 form-group-emphasis">
                        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext('Event Date/Time');?></h3>

                            <div class="form-group date">
                                <label id="event_start_date" class="control-lable col-sm-12" for="start_date"><?= gettext('Event Date');?> <span style="color: #ff0000;"> *</span></label>
                                <div class="col-sm-4">
                                    <input type="text" onchange="<?php if(!$_COMPANY->getAppCustomization()['event']['disable_event_conflict_checks']) { ?>checkEventsByDate('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($eventid); ?>',this.value),<?php } ?>updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" name="eventdate" required id="start_date" value="<?= $eventid ? $s_date : ''; ?>" class="form-control" placeholder="YYYY-MM-DD" autocomplete="off"/>
                                </div>
                                <div class="col-sm-3 hrs-minutes pr-0">
                                    <select aria-label="<?= gettext('Start Time Hour');?>" class="form-control" id="start_date_hour" name='hour' onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" required>
                                        <?=getTimeHoursAsHtmlSelectOptions($eventid ? $s_hrs : '');?>
                                    </select>
                                </div>
                                <div class="col-sm-3 hrs-minutes pr-0">
                                    <select aria-label="<?= gettext('Start time minutes');?>" class="form-control" id="start_date_minutes" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" name="minutes" required>
                                        <?=getTimeMinutesAsHtmlSelectOptions($eventid ? $s_mmt : '');?>
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <fieldset>
                                    <div role="group" aria-labelledby="event_start_date">
                                        <label class="radio-inline"><input aria-label="<?= gettext('AM');?>" type="radio" value="AM" name="period" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" required  <?= $eventid ? ($s_prd=='AM' ? "checked" : '') : "checked"; ?> >AM</label>
                                        <label class="radio-inline"><input aria-label="<?= gettext('PM');?>" type="radio" value="PM" name="period" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" <?= $eventid ? ($s_prd =='PM' ? "checked" : '') : ""; ?> >PM</label>
                                    </div>
                                    </fieldset>
                                </div>
                                <div class="col-sm-12">
                                    <p class='timezone' onclick="showTzPicker();" ><a href="javascript:void(0)"  class="link_show" id="tz_show"><?= sprintf(gettext('%s Time'),($eventid ? ($event->val('timezone') ? $event->val('timezone') : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' )) : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC')))?></a></p>
                                </div>
                                <input type="hidden" name="timezone" id="tz_input" value="<?= $eventid ? ($event->val('timezone') ? $event->val('timezone') : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' )) : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC') ?>">
                                <div id="tz_div" style="display:none;">
                                    <label class="col-sm-12 control-lable"><?= gettext('Timezone');?></label>
                                    <div class="col-sm-12">
                                        <select aria-label="<?= gettext('Timezone');?>" class="form-control teleskope-select2-dropdown" id="selected_tz" onchange="selectedTimeZone();updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>');" style="width: 100%;">
                                            <?php echo getTimeZonesAsHtmlSelectOptions($event_tz); ?>
                                        </select>
                                        <script> $(".teleskope-select2-dropdown").select2({width: 'resolve'});</script>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-12 pt-0">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                        <input aria-label="<?= gettext('Multi-day event');?>" type="checkbox" class="form-check-input multiday" name="multiDayEvent" id="multiDayEvent" <?= $eventid ? (($event->getDurationInSeconds() > 86400) ? 'checked' : '') : ''; ?> onchange="setCalendarBlockPermissionAndValues(1,0,'');updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>');" ><?= gettext('Multi-day event');?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group" id="event_duration" style="display:<?= $eventid ? (($event->getDurationInSeconds() > 86400) ? 'none' : 'block') : 'block'; ?>;">
                                <label for="inputEmail" class="col-sm-12 control-lable"><?= gettext('Duration');?> <span style="color: #ff0000;"> *</span></label>
                                <div class="col-sm-3 hrs-minutes pr-0">
                                    <select aria-label="<?= gettext('hour duration');?>" class="form-control" id="hour_duration" name='hour_duration' onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" required>
                                <?php for ($i=0;$i<25;$i++){ ?>
                                        <option value="<?= $i; ?>" <?= $eventid ? ($e_hrs==$i ? "selected" : '') : ""; ?> ><?= $i; ?> hr</option>
                                <?php } ?>
                                    </select>
                                </div>
                                <div class="col-sm-3 hrs-minutes pr-0">
                                    <select aria-label="<?= gettext('minutes duration');?>" class="form-control" id="minutes_duration" name="minutes_duration" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" <?= $eventid  ? (($e_hrs == '24')?'disabled':'') : ''; ?>>
                                <?php	for($m=0;$m<60;$m=$m+5){ ?>
                                        <option value="<?= $m; ?>" <?= $eventid ? ($e_mnt==$m ? "selected" : '') : ""; ?> > <?= $m; ?> min</option>
                                <?php	} ?>
                                    </select>
                                </div>
                            </div>
                            <div id="multi_day_end" style="display:<?= $eventid ? ( ($event->getDurationInSeconds() > 86400) ? 'block' : 'none') : 'none'; ?>;">
                                <div class="form-group date">
                                    <label id="event_end_date" class="control-lable col-sm-12" for="end_date"><?= gettext('End Date');?> <span style="color: #ff0000;"> *</span></label>
                                    <div class="col-sm-4">
                                        <input type="text"  name="end_date" required id="end_date" class="form-control" value="<?= $eventid ? $e_date : ''; ?>" placeholder="YYYY-MM-DD" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" />
                                    </div>

                                    <div class="col-sm-3 hrs-minutes pr-0">
                                        <select aria-label="<?= gettext('end hour');?>" class="form-control" id="end_hour"  name='end_hour' onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" required>
                                            <?=getTimeHoursAsHtmlSelectOptions($eventid ? $e_hrs : '');?>
                                        </select>
                                    </div>
                                    <div class="col-sm-3 hrs-minutes pr-0">
                                        <select aria-label="<?= gettext('End time minutes');?>" class="form-control" id="end_minutes" name="end_minutes" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" required>
                                            <?=getTimeMinutesAsHtmlSelectOptions($eventid ? $e_mnt : '');?>
                                        </select>
                                    </div>
                                    <div class="col-sm-2">
                                    <fieldset>
                                        <div role="group" aria-labelledby="event_end_date">
                                            <label class="radio-inline"><input aria-label="<?= gettext('AM');?>" type="radio" value="AM" name="end_period" required <?= $eventid ? ($e_prd=='AM' ? "checked" : 'checked') : "checked"; ?> onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" >AM</label>
                                            <label class="radio-inline"><input aria-label="<?= gettext('PM');?>" type="radio" value="PM" name="end_period" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" <?= $eventid ? ($e_prd =='PM' ? "checked" : '') : ""; ?> >PM</label>
                                        </div>
                                    </fieldset>
                                    </div>
                                </div>

                            </div>

                            <div class="form-group">
                                <label id="calendar_block" class="control-lable col-sm-12"><?= gettext('Calendar Block:');?></label>
                                <div class="btn-group btn-group-toggle col-sm-2 calendarBlockBtnBlock" data-toggle="buttons" >
                                    <label  id="calendarOn" class="btn btn-default <?= $eventid ? ($event->val('calendar_blocks')==1 ? 'active' : '') : 'active'; ?> btn-on btn-xs adv">
                                        <input aria-describedby="calendar_block onOffAttendeesCalendar" aria-label="<?= gettext('Calendar Block ON');?>" type="radio" value="1" name="calendar_blocks" id="calendar_blocks_on" <?= $eventid ? ($event->val('calendar_blocks')==1 ? 'checked' : '') : 'checked'; ?> >ON
                                    </label>
                                    <label id="calendarOff" class="btn btn-default btn-off <?= $eventid ? ($event->val('calendar_blocks')==0 ? 'active' : '') : ''; ?> btn-xs adv">
                                        <input aria-describedby="calendar_block" aria-label="<?= gettext('Calendar Block OFF');?>" <?= $eventid ? ($event->val('calendar_blocks')==0 ? 'checked' : '') : ''; ?> type="radio" value="0" name="calendar_blocks" id="calendar_blocks_off">OFF
                                    </label>
                                </div>
                                <div class="col-sm-8 p-1" id="onOffAttendeesCalendar">
                                    <?= gettext('If ON, the event will block attendees calendar after they confirm attendance'); ?>
                                </div>
                            </div>

                    </div>
                    <!-- Event Where Section: End -->

                    <!-- Event Where Section: Start -->
                    <?php if ($eventid){ ?>
                    <div class="col-12 form-group-emphasis">
                        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Event Location"); ?></h3>

                            <div class="form-group">
                                <label class="col-sm-12 control-lable"><?= gettext('Venue Type');?><span style="color: #ff0000;"> *</span></label>
                                <div class="col-sm-12">
                                    <select aria-label="<?= gettext('Venue Type');?>" class="form-control" id="event_attendence_type" onchange="changeEventAttendenceType(this.value);" name='event_attendence_type' required>
                                        <option value="1" <?= $eventid ? ($event->val('event_attendence_type')==1 ? 'selected' : '') : ''; ?> <?=$eventid ? (($event->isPublished() && in_array((int)$event->val('event_attendence_type'), array(2, 3)) && $event->isLimitedCapacity()) ?'disabled':'' ) : ''?>  ><?= gettext('In-Person');?></option>
                                        <option value="2" <?=  $eventid ? ($event->val('event_attendence_type')==2 ? 'selected' : '') : ''; ?> <?= $eventid ? (($event->isPublished() && in_array((int)$event->val('event_attendence_type'), array(1, 3)) && $event->isLimitedCapacity()) ?'disabled':'') : ''; ?> ><?= gettext('Virtual (Web Conference)');?></option>
                                        <option value="3" <?=  $eventid ? ($event->val('event_attendence_type')==3 ? 'selected' : '') : ''; ?> ><?= gettext('In-Person & Virtual (Web Conference)');?></option>
                                        <option value="4" <?= $eventid ? ($event->val('event_attendence_type')==4 ? 'selected' : '') : ''; ?> <?=$eventid ? (($event->isPublished() && in_array((int)$event->val('event_attendence_type'), array(1,2,3)))?'disabled':'') : ''; ?> ><?= gettext('Other');?></option>
                                    </select>
                                </div>
                            </div>

                            <div id="conference_div" <?= $eventid && ($event->val('event_attendence_type')==2 || $event->val('event_attendence_type')==3) ? '' : 'style="display:none;"' ?> >
                                <div class="form-group">
                                    <label class="control-lable col-sm-12" for="web_conference_link"><?= gettext('Enter the web conference link.');?><span style="color: #ff0000;"> *</span></label>
                                    <div class="col-sm-12">
                                        <?php generateMeetingLinkOptionsHTML() ?>
                                        <input type="url" id="web_conference_link"  name="web_conference_link" onblur="isValidUrl(this.value)" required class="form-control" value="<?= $eventid ? $event->val('web_conference_link') : '';?>" placeholder="<?= gettext('Link to join Webex/Zoom/Teams/Hangouts/GotoMeeting, link starts with https://');?>">
                                         <div id="web_conference_link_note" style="color: rgb(173, 0, 0); background-color: rgb(249, 249, 249); font-size: small;"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-lable col-sm-12" for="web_conference_detail"><?= gettext('Web Conf. Details');?><span style="color: #ff0000;"></span></label>
                                    <div class="col-sm-12">
                                        <div id="web_conference_detail_note" style="color:#AD0000; background-color: #F9F9F9; display:none;font-size: small">
                                            <?= $_COMPANY->getAppCustomization()['event']['web_conf_detail_message_override'] ?: gettext('In order to properly track event attendance, please DO NOT share the actual event link in the body of the invitation.')?>
                                        </div>
                                        <textarea class="form-control" id="web_conference_detail" name="web_conference_detail" placeholder="<?= gettext('Please provide additional details for joining (ie. meeting ID, password, login information, requirements, etc)');?>"><?= $eventid ? str_replace('<br>', "\r\n", $event->val('web_conference_detail')) : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <?php $col_12 = true; $show_additional_location_fields = true; ?>
                            <?php require __DIR__ . '/event_location_picker.html.php' ?>

                            <?php $event ??= null; ?>
                            <div id="participation_limit_div" class="form-group">
                                <label class="control-lable col-sm-12"><?= gettext('Participant Limit');?></label>
                                <div class="btn-group btn-group-toggle col-sm-2 status" data-toggle="buttons">
                                    <label class="btn btn-default btn-on btn-xs adv <?= $hasLimitedParticipation ? 'active' : '' ?> <?= $eventid ? ($event->isPublished()?'disabled':'') : '';?>">
                                        <input aria-label="<?= gettext("Participant Limit ON");?>" tabindex="0" type="radio" value="1" name="participation_onoff" class="participation_onoff" <?= $hasLimitedParticipation ? 'checked="checked"' : '' ?> >ON
                                    </label>
                                    <label class="btn btn-default btn-off btn-xs <?= !$hasLimitedParticipation ? 'active' : '' ?> <?= $eventid ? ($event->isPublished()?'disabled':'') : ''; ?> adv">
                                        <input aria-label="<?= gettext("Participant Limit OFF");?>" tabindex="0" type="radio" value="0" name="participation_onoff" <?= !$hasLimitedParticipation ? 'checked="checked"' : '' ?> class="participation_onoff">OFF
                                    </label>
                                </div>

                                <div class="col-sm-10">
                                    <div id="participation_limit_inperson" <?= !$hasLimitedParticipation ?'style="display:none;"':''?> >
                                        <div class="row">
                                            <div class="col-sm-5">
                                                <label class="control-lable"><?= gettext('In-Person Limit');?></label>
                                            </div>
                                            <div class="col-sm-7">
                                                <input aria-label="<?= gettext("In-Person Limit");?>" type="number" onchange="updateWaitlist(this)" min="<?= $eventid ? ($event->isPublished() ? $event->val('max_inperson') : '0') : '0';?>" name="max_inperson" id="max_inperson" class="form-control adv js-participation-limit-input js-max-inperson" style="display:inline-block;width:80px;" maxlength="6" inputmode="numeric" oninput="this.value = parseInt(this.value.replace(/[^0-9]/g, ''));"
                                                  <?=
                                                    $event?->isParticipationLimitUnlimited('max_inperson')
                                                    ?
                                                      'disabled data-unlimited="1"'
                                                    :
                                                      'value="' . ($event?->val('max_inperson') ?: 0)  . '"'
                                                  ?>
                                                />

                                                <label>
                                                  <input
                                                    name="inperson_limit_unlimited"
                                                    class="js-participation-limit-unlimited-chk js-max-inperson"
                                                    type="checkbox"
                                                    data-target="#max_inperson"
                                                    <?=
                                                      $event?->isParticipationLimitUnlimited('max_inperson')
                                                      ?
                                                        'checked data-value="0" data-unlimited="1"' . ($event->isPublished() ? ' disabled data-published="1"' : '')
                                                      :
                                                        'data-value="' . ($event?->val('max_inperson') ?: 0) . '"'
                                                    ?>
                                                    onchange="participationLimitUnlimitedToggle(event)"
                                                  >
                                                    &nbsp; <?= gettext('Mark as unlimited') ?>
                                                </label>
                                            </div>
                                            <div class="col-sm-12 mt-2"></div>
                                            <div class="col-sm-5">
                                                <label class="control-lable"><?= gettext('In-Person Waitlist');?></label>
                                            </div>
                                            <div class="col-sm-7">
                                                <input aria-label="<?= gettext("In-Person Waitlist");?>" type="number" min="<?= $eventid ? ($event->isPublished() ? $event->val('max_inperson_waitlist') : '0') : '0';?>" name="max_inperson_waitlist" id="max_inperson_waitlist" class="form-control adv js-participation-limit-input js-max-inperson-waitlist" style="display:inline-block;width:80px;" maxlength="6" inputmode="numeric" oninput="this.value = parseInt(this.value.replace(/[^0-9]/g, ''));"
                                                  <?=
                                                    $event?->isParticipationLimitUnlimited('max_inperson_waitlist')
                                                    ?
                                                      'disabled data-unlimited="1"'
                                                    :
                                                      'value="' . ($event?->val('max_inperson_waitlist') ?: 0)  . '"'
                                                  ?>
                                                />

                                                <label>
                                                  <input
                                                    name="inperson_waitlist_unlimited"
                                                    class="js-participation-limit-unlimited-chk js-max-inperson-waitlist"
                                                    type="checkbox"
                                                    data-target="#max_inperson_waitlist"
                                                    <?=
                                                      $event?->isParticipationLimitUnlimited('max_inperson_waitlist')
                                                      ?
                                                        'checked data-value="0" data-unlimited="1"' . ($event->isPublished() ? ' disabled  data-published="1"' : '')
                                                      :
                                                        'data-value="' . ($event?->val('max_inperson_waitlist') ?: 0) . '"'
                                                    ?>
                                                    onchange="participationLimitUnlimitedToggle(event)"
                                                  >
                                                    &nbsp; <?= gettext('Mark as unlimited') ?>
                                                </label>
                                            </div>
                                            <div class="col-sm-12 mt-2"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-10 offset-sm-2">
                                    <div id="participation_limit_online" <?= !$hasLimitedParticipation ?'style="display:none;"':''?>>
                                        <div class="row">
                                            <div class="col-sm-5">
                                                <label class="control-lable"><?= gettext('Online Limit');?></label>
                                            </div>
                                            <div class="col-sm-7">
                                                <input aria-label="<?= gettext("Online Limit");?>" type="number" onchange="updateWaitlist(this)" min="<?= $eventid ? ($event->isPublished() ? $event->val('max_online') : '0') : '0';?>" name="max_online" id="max_online" class="form-control adv js-participation-limit-input js-max-online" style="display:inline-block;width:80px;" maxlength="6" inputmode="numeric" oninput="this.value = parseInt(this.value.replace(/[^0-9]/g, ''));"
                                                  <?=
                                                    $event?->isParticipationLimitUnlimited('max_online')
                                                    ?
                                                      'disabled data-unlimited="1"'
                                                    :
                                                      'value="' . ($event?->val('max_online') ?: 0)  . '"'
                                                  ?>
                                                />

                                                <label>
                                                  <input
                                                    name="online_limit_unlimited"
                                                    class="js-participation-limit-unlimited-chk js-max-online"
                                                    type="checkbox"
                                                    data-target="#max_online"
                                                    <?=
                                                      $event?->isParticipationLimitUnlimited('max_online')
                                                      ?
                                                        'checked data-value="0" data-unlimited="1"' . ($event->isPublished() ? ' disabled  data-published="1"' : '')
                                                      :
                                                        'data-value="' . ($event?->val('max_online') ?: 0) . '"'
                                                    ?>
                                                    onchange="participationLimitUnlimitedToggle(event)"
                                                  >
                                                    &nbsp; <?= gettext('Mark as unlimited') ?>
                                                </label>
                                            </div>
                                            <div class="col-sm-12 mt-2"></div>
                                            <div class="col-sm-5">
                                                <label class="control-lable"><?= gettext('Online Waitlist');?></label>
                                            </div>
                                            <div class="col-sm-7">
                                                <input aria-label="<?= gettext("Online Waitlist");?>"  type="number" min="<?= $eventid ? ($event->isPublished() ? $event->val('max_online_waitlist') : '0') : '0';?>" name="max_online_waitlist" id="max_online_waitlist" class="form-control adv js-participation-limit-input js-max-online-waitlist" style="display:inline-block;width:80px;" maxlength="6" inputmode="numeric" oninput="this.value = parseInt(this.value.replace(/[^0-9]/g, ''));"
                                                  <?=
                                                    $event?->isParticipationLimitUnlimited('max_online_waitlist')
                                                    ?
                                                      'disabled data-unlimited="1"'
                                                    :
                                                      'value="' . ($event?->val('max_online_waitlist') ?: 0)  . '"'
                                                  ?>
                                                />

                                                <label>
                                                  <input
                                                    name="online_waitlist_unlimited"
                                                    class="js-participation-limit-unlimited-chk js-max-online-waitlist"
                                                    type="checkbox"
                                                    data-target="#max_online_waitlist"
                                                    <?=
                                                      $event?->isParticipationLimitUnlimited('max_online_waitlist')
                                                      ?
                                                        'checked data-value="0" data-unlimited="1"' . ($event->isPublished() ? ' disabled  data-published="1"' : '')
                                                      :
                                                        'data-value="' . ($event?->val('max_online_waitlist') ?: 0) . '"'
                                                    ?>
                                                    onchange="participationLimitUnlimitedToggle(event)"
                                                  >
                                                    &nbsp; <?= gettext('Mark as unlimited') ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($_COMPANY->getAppCustomization()['event']['checkin']) { 
                                $checkinToggleState =  $eventid ? ($event->isPublished() ? 'disabled':'') : ''; 
                                ?>
                            <div id="enable_checkin_part" class="form-group" <?= $eventid ? (((int)$event->val('event_attendence_type')==2 || (int)$event->val('event_attendence_type')==3) ?'':'style="display:none;"') : 'style="display:none;"'; ?> >
                                <label class="control-lable col-sm-12"><?= gettext('Enable Checkin');?></label>
                                <div class="btn-group btn-group-toggle col-sm-2 status" data-toggle="buttons">
                                    <label class="btn btn-default btn-on btn-xs <?= $eventid ? ( $event->val('checkin_enabled')?'active':'') : ($_COMPANY->getAppCustomization()['event']['checkin_default'] ?'active':'')?> adv <?= $checkinToggleState; ?> ">
                                        <input aria-label="<?= gettext("Enable Checkin ON");?>" type="radio" value="1" name="checkin_enabled" <?= $eventid ? ($event->val('checkin_enabled')?'checked="checked"':'') : ($_COMPANY->getAppCustomization()['event']['checkin_default'] ? 'checked="checked"':'') ?> >ON
                                    </label>
                                    <label class="btn btn-default btn-off btn-xs <?= $eventid ? (!$event->val('checkin_enabled')?'active':'') : (!$_COMPANY->getAppCustomization()['event']['checkin_default'] ?'active':'')?> adv <?= $checkinToggleState; ?>" style="padding: 3px 12px; margin:0;">
                                        <input aria-label="<?= gettext("Enable Checkin Off");?>" type="radio" value="0" name="checkin_enabled" <?= $eventid ? (!$event->val('checkin_enabled')?'checked="checked"':'') : (!$_COMPANY->getAppCustomization()['event']['checkin_default'] ? 'checked="checked"':'') ?> >OFF
                                    </label>
                                </div>
                                <div class="col-sm-10">
                                    <?= gettext('If \'ON\', event attendance will be automatically tracked for virtual event');?>
                                </div>
                            </div>
                            <?php } ?>


                    </div>
                    <?php } ?>
                    <!-- Event Where Section: End -->

                    <!-- Event Contact Section: Start -->
                    <?php if ($eventid){ ?>
                        <div class="col-12 form-group-emphasis">
                            <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Event Contact"); ?></h3>
                            <div class="form-group">
                            <div class="label-text">
                                <label class="col-sm-12 control-lable" for="event_contact"><?= gettext('Event Contact');?> <span style="color: #ff0000;"> * </span></label> <i tabindex="0" class="fa fa-info-circle" aria-label="<?= gettext('The contact field is a free-form field. You can input multiple comma-separated email addresses, or other relevant contact information about coordinators.')?>" data-toggle="tooltip" data-placement="top" title="<?= gettext('The contact field is a free-form field. You can input multiple comma-separated email addresses, or other relevant contact information about coordinators.')?>"></i>
                                <div class="col-sm-12">
                                    <input type="text" id="event_contact" name="event_contact" value="<?= ($eventid && $event->val('version') > 1) ? (htmlspecialchars($event->val('event_contact'))) : (trim($_USER->getFullName().' ('.$_USER->val('email').')')); ?>" class="form-control" placeholder="<?= gettext('Contact Name (email)');?>" required maxlength="255" />
                                </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-12 control-lable" for="event_contact_phone_number"><?= gettext('Event Contact Phone Number');?></label>
                                <div class="col-sm-12">
                                    <input type="text" id="event_contact_phone_number" name="event_contact_phone_number" value="<?= $event->val('event_contact_phone_number'); ?>" class="form-control" placeholder="<?= gettext('Contact Name Phone Number');?>" required />
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <!-- Event Contact Section: End -->

                    <!-- Event Type Section: Start -->
                    <?php if ($eventid){ ?>
                    <div class="col-12 form-group-emphasis">
                        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Event Type"); ?></h3>

                            <div class="form-group">
                                <label class="control-lable col-sm-12"><?= gettext('Type');?><span style="color: #ff0000;"> *</span></label>
                                <div class="col-sm-12">
                                    <select aria-label="<?= gettext('Type');?>" class="form-control" name="eventtype" id="sel1" required>
                                        <option value=""><?= gettext('Select Event Type');?></option>
                            <?php 	if(count($type)>0){ ?>
                            <?php		for($ty=0;$ty<count($type);$ty++){ ?>
                                            <option value="<?= $type[$ty]['typeid']; ?>" <?=  $eventid ? ($event->val('eventtype') == $type[$ty]['typeid'] ? "selected" : "") : ''; ?> ><?= $type[$ty]['type']; ?></option>
                            <?php		} ?>
                            <?php 	}else{ ?>
                                        <option value="">- <?= gettext('No type to select');?> -</option>
                            <?php	} ?>
                                    </select>
                                </div>
                            </div>

                    </div>
                    <?php } ?>
                    <!-- Event Type Section: End -->

                    <!-- Event Details Section: Start -->
                    <?php if ($eventid){ ?>
                    <div class="col-12 form-group-emphasis">
                        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Event Details"); ?></h3>

                            <div class="form-group">
                                <label for="inputEmail" class="col-sm-12 control-lable"><?= gettext('Description');?>
                                    <?php if($allowDescriptionUpdate ){ ?>
                                    <?php if ($_COMPANY->getAppCustomization()['event']['is_description_required']) { ?><span style="color: #ff0000;"> *</span><?php } ?>
                                    <?php } else { ?>
                                    <i class="fa fa-info-circle fa-small info-black" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext('%s editing is disabled because the %s has been submitted for approval.'), gettext("Event Description"), gettext("Event"))?>"></i>
                                    <?php } ?>
                                </label>
                                <div class="col-sm-12">
                                    <div id="eventDescriptionNote" style="color:#AD0000; background-color: #F9F9F9;  display:none;font-size: small">
<?= $_COMPANY->getAppCustomization()['event']['web_conf_detail_message_override'] ?: gettext('In order to properly track event attendance, please DO NOT share the actual event link in the body of the invitation.')?></div>
                                    <div id="post-inner" class="post-inner-edit">
                                    <textarea class="form-control" placeholder="<?= gettext('Event Description');?>" name="event_description" rows="5" id="redactor_content" maxlength="2000" ><?= $eventid ? htmlspecialchars($event->val('event_description')) : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <?php if($eventid){ ?>
                                <?= $event->renderAttachmentsComponent('v2') ?>
                            <?php } else { ?>
                                <?= Event::CreateEphemeralTopic()->renderAttachmentsComponent('v7') ?>
                            <?php } ?>

                    </div>
                    <?php 	} ?>
                    <!-- Event Details Section: End -->

                    <!-- Event Custom Fields Section: Start -->
                    <?php if ($eventid && $custom_fields){ ?>
                    <div class="col-12 form-group-emphasis">
                        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Additional Fields"); ?></h3>
                        <?php if ($isActionDisabledDuringApprovalProcess) { ?>
                        <div class="col-md-12">
                            <div class="alert-warning p-3 text-small">
                                <?=sprintf(gettext('This event is currently in the approval process or has been approved. %1$s changes are not permitted. To make changes, request the event approver to deny the approval.'), gettext('Additional Fields'))?>
                            </div>
                        </div>
                        <?php } ?>


                            <?php
                                include(__DIR__ . '/../templates/event_custom_fields.template.php');
                            ?>

                    </div>
                    <?php 	} ?>
                    <!-- Event Custom Fields Section: End -->

                    <!-- Event Settings Section: Start -->
                    <?php if ($eventid){ ?>
                    <div class="col-12 form-group-emphasis">
                        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Other Event Settings"); ?> +/-</h3>

                            <div id="other_options_part" class="form-group">
                                <label for="inputEmail" class="col-12 control-lable"><strong><?= gettext('Other Options');?></strong></label>
                                <div class="col-12">

                                    <div id="rsvp_enabled_div" class="col-12 pl-0 mt-1">
                                        <div class="custom-control custom-switch">
                                            <input <?= $eventid ? ($event->val('rsvp_enabled') ? 'checked="checked"' : '') : 'checked="checked"' ?> type="checkbox" class="custom-control-input" name="rsvp_enabled" id="rsvp_enabled"  >
                                            <label class="custom-control-label" for="rsvp_enabled"><?= gettext('RSVP enabled');?></label>
                                        </div>
                                    </div>

                                    <?php if (!$event_series_id){ ?>
                                        <div id="private_event_part" class="col-12 pl-0 mt-1">
                                            <div class="custom-control custom-switch">
                                                <input <?= $eventid ? ($event->val('isprivate') ? 'checked="checked"' : '') : 'checked="checked"' ?> type="checkbox" class="custom-control-input" name="isprivate" id="isprivate" <?= ($eventid && $event->val('listids')!='0' ? 'disabled' : '') ?> >
                                                <label class="custom-control-label" for="isprivate"><?= gettext('Private Event');?></label>
                                            </div>
                                        </div>
                                    <?php } ?>

                                    <div class="col-12 pl-0 mt-1" >

                                        <div class="custom-control custom-switch">
                                            <input  aria-label="<?= gettext("Custom Reply To Email");?>" <?= $eventid ? ($event->val('content_replyto_email') ? 'checked' : '') : ''; ?> type="checkbox" class="custom-control-input" name="content_replyto_email_checkbox" id="content_replyto_email_checkbox" >
                                            <label class="custom-control-label" for="content_replyto_email_checkbox"><?= gettext("Custom Reply To Email");?></label>
                                        </div>
                                        <div id="replyto_email" class="" style="display:<?= $eventid ? (empty($event->val('content_replyto_email')) ? 'none' : '') : 'none' ?>;">
                                            <input aria-label="<?= gettext("Add a custom reply to email");?>" type="text" id="content_replyto_email" name="content_replyto_email" value="<?= $eventid ? $event->val('content_replyto_email') : ''; ?>" class="form-control" placeholder="<?= gettext('Enter an email ID');?>" />
                                        </div>
                                    </div>
                                <?php if ($_COMPANY->getAppCustomization()['event']['photo_disclaimer']['enabled'] || !empty($disclaimerWaivers)) { ?>
                                    <label for="inputEmail" class="col-12 control-lable mt-2 pl-0 ml-0"><strong><?=  gettext('Disclaimers / Waivers'); ?></strong></label>
                                <?php } ?>
                                    <?php if ($_COMPANY->getAppCustomization()['event']['photo_disclaimer']['enabled']) { ?>
                                        <div class="col-12 pl-0 mt-1">
                                            <div class="custom-control custom-switch">
                                                <input <?= $eventid ? ($event->val('add_photo_disclaimer') ? 'checked' : '') : ($_COMPANY->getAppCustomization()['event']['photo_disclaimer']['enabled_default'] ? 'checked="checked"' : '') ?> type="checkbox" class="custom-control-input" name="add_photo_disclaimer" id="add_photo_disclaimer" >
                                                <label class="custom-control-label" for="add_photo_disclaimer"><?= sprintf(gettext("Add %s Disclaimer"),$_COMPANY->val("companyname"));?> <a href="javascript:void(0)" class='disclaimerbtn small' onclick='disclaimerBtnClickHideShow("disclaimerdiv")'> <?=gettext("[view]")?> </a></label>
                                            </div>


                                            <div id="disclaimerdiv" class="small alert-secondary p-3" style="display:none;">
                                                <?=  $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['disclaimer']; ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                <?php if(!empty($disclaimerWaivers)){ ?>
                                    
                                    <?php foreach($disclaimerWaivers as $disclaimerWaiver){ 
                                            $checked = "";
                                            if ($event ?-> val('version') > 1) {
                                                if (in_array($disclaimerWaiver->val('disclaimerid'), $disclaimerids)) {
                                                    $checked = 'checked';
                                                }
                                            } else {
                                                $checked = $disclaimerWaiver->val('enabled_by_default') ? 'checked' : '';
                                            }
                                            $disclaimerMessage =  $disclaimerWaiver->getDisclaimerBlockForLanguage($_USER->val('language'));
                                        ?>
                                            <div class="col-12 pl-0 mt-1">
                                                <div class="custom-control custom-switch">
                                                    <input <?= $eventid ? ($checked ? 'checked' : '') :  '' ?> type="checkbox" class="custom-control-input" name="disclaimerids[]" id="disclaimerid<?= $_COMPANY->encodeId($disclaimerWaiver->val('disclaimerid')); ?>"  value="<?= $_COMPANY->encodeId($disclaimerWaiver->val('disclaimerid')); ?>" >
                                                    <label class="custom-control-label" for="disclaimerid<?= $_COMPANY->encodeId($disclaimerWaiver->val('disclaimerid')); ?>"  value="<?= $_COMPANY->encodeId($disclaimerWaiver->val('disclaimerid')); ?>"><?= $disclaimerWaiver->val('disclaimer_name'); ?> <a href="javascript:void(0)" class='disclaimerbtn small' onclick='disclaimerBtnClickHideShow("disclaimerWaiverDiv<?= $_COMPANY->encodeId($disclaimerWaiver->val('disclaimerid')); ?>")'> <?=gettext("[view]")?> </a></label>
                                                </div>
                                                <div id="disclaimerWaiverDiv<?= $_COMPANY->encodeId($disclaimerWaiver->val('disclaimerid')); ?>" class="small alert-secondary p-3" style="display:none;">
                                            <?=  $disclaimerMessage['disclaimer']; ?>
                                        </div>
                                            </div>
                                        <?php } ?>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    <!-- Event Settings Section: End -->
                     
                <!-- Event contributors Section: Start -->
                <?php if ($eventid && $_COMPANY->getAppCustomization()['event']['event_contributors']['enabled']) { ?>
                    <?php include_once __DIR__.'/../common/event_contributors_selection.template.php'; ?>
                <?php } ?>
                 <!-- Event contributors Section: End -->

                <!-- Event Budget/Expense Section: Start -->
                <?php
                    if ($_COMPANY->getAppCustomization()['event']['event_form']['show_module_settings'] && $eventid && $event->canUpdateEventExpenseEntry() && !$event->isPublished())  {
                        $preSelectBudget = $event->getEventBudgetedDetail() ? true : false;
                    ?>
                        <div class="col-12 form-group-emphasis">
                            <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Budget Settings"); ?></h3>

                            <!-- Switch and text -->
                            <div class="col-12" id="budgetSwitchBlock">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" name="budgetSwitch" id="budgetSwitch" onchange="budgetSwitchChange('<?= $_COMPANY->encodeId($eventid); ?>')" <?= $preSelectBudget ? 'checked ' : '' ?> >
                                    <label class="custom-control-label" for="budgetSwitch"><?= sprintf(gettext('Does this event require budget?')); ?></label>
                                </div>
                            </div>

                            <!-- Manage button -->
                            <div class="col-12">
                                <button id="manageBudgetTab" class="btn btn-affinity prevent-multi-clicks m-3" style="display:<?= $preSelectBudget ? 'block' : 'none'; ?> ;" type="button" onclick="manageEventExpenseEntries('<?= $_COMPANY->encodeId($eventid); ?>')"><?=gettext('Manage Budget')?></button>
                            </div>

                        </div>
                    <?php } ?>
                    <!-- Event Budget/Expense Section: End -->

                    <!-- Partner Organization Section: Start -->
                    <?php if ($_COMPANY->getAppCustomization()['event']['partner_organizations']['enabled'] && $_COMPANY->getAppCustomization()['event']['event_form']['show_module_settings'] && $eventid && !$event->isPublished()) { $preSelectOrg = $event->getAssociatedOrganization() ? true : false; ?>
                        <div class="col-12 form-group-emphasis">
                            <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Partner Organization Settings"); ?><?php if ($_COMPANY->getAppCustomization()['event']['partner_organizations']['is_required']) { ?><span style="color: #ff0000;"> *</span><?php } ?></h3>

                            <!-- Switch and text -->
                            <div class="col-12" id="orgSwitchBlock">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" name="orgSwitch" id="orgSwitch" onchange="orgSwitchChange('<?= $_COMPANY->encodeId($eventid); ?>')" <?= ($preSelectOrg || $_COMPANY->getAppCustomization()['event']['partner_organizations']['is_required']) ? 'checked ' : '' ?>>
                                    <label class="custom-control-label" for="orgSwitch"><?= sprintf(gettext('Is this event in partnership with external organizations?')); ?></label>
                                </div>
                            </div>

                            <!-- Manage button -->
                            <div class="col-12">
                                <button id="manageOrgTab" class="btn btn-affinity prevent-multi-clicks m-3" style="display:<?= ($preSelectOrg || $_COMPANY->getAppCustomization()['event']['partner_organizations']['is_required']) ? 'block' : 'none'; ?> ;" type="button" onclick="manageOrganizations('<?= $_COMPANY->encodeId($eventid); ?>')"><?=gettext('Manage Partner Organizations')?></button>
                            </div>

                        </div>
                    <?php } ?>
                    <!-- Partner Organization Section: End -->

                     <!-- Event Speaker Section: Start -->
                     <?php if ($_COMPANY->getAppCustomization()['event']['speakers']['enabled'] && $_COMPANY->getAppCustomization()['event']['event_form']['show_module_settings'] && $eventid && !$event->isPublished()) {
                            $preSelectSpeekers = !empty($event->getEventSpeakers()) ? true : false;
                    ?>
                        <div class="col-12 form-group-emphasis">
                            <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Speaker Settings"); ?></h3>

                            <!-- Switch and text -->
                            <div class="col-12" id="speakerSwitchBlock">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" name="speakerSwitch" id="speakerSwitch" onchange="speakerSwitchChange('<?= $_COMPANY->encodeId($eventid); ?>')" <?= $preSelectSpeekers ? 'checked ' : '' ?>>
                                    <label class="custom-control-label" for="speakerSwitch"><?= sprintf(gettext('Does this event require speakers?')); ?></label>
                                </div>
                            </div>

                            <!-- Manage button -->
                            <div class="col-12">
                                <button id="manageSpeakerTab" class="btn btn-affinity prevent-multi-clicks m-3" style="display:<?= $preSelectSpeekers ? 'block' : 'none'; ?> ;" type="button" onclick="manageEventSpeakers('<?= $_COMPANY->encodeId($eventid); ?>')"><?=gettext('Manage Speakers')?></button>
                            </div>

                        </div>
                    <?php } ?>
                    <!-- Event Speaker Section: End -->

                    <!-- Event Volunteer Section: Start -->
                    <?php if ($_COMPANY->getAppCustomization()['event']['volunteers'] && $_COMPANY->getAppCustomization()['event']['event_form']['show_module_settings'] && $eventid && !$event->isPublished()) {
                        $preSelectVolunteer = !empty($event->getEventVolunteerRequests()) ? true : false;
                    ?>
                        <div class="col-12 form-group-emphasis">
                            <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Volunteer Settings"); ?></h3>

                            <!-- Switch and text -->
                            <div class="col-12" id="volunteerSwitchBlock">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" name="volunteerSwitch" id="volunteerSwitch" onchange="volunteerSwitchChange('<?= $_COMPANY->encodeId($eventid); ?>')" <?= $preSelectVolunteer ? 'checked' : ''; ?> >
                                    <label class="custom-control-label" for="volunteerSwitch"><?= sprintf(gettext('Does this event need volunteers?')); ?></label>
                                </div>
                            </div>

                            <!-- Manage button -->
                            <div class="col-12">
                                <button id="manageVolunteerTab" class="btn btn-affinity prevent-multi-clicks m-3" style="display:<?= $preSelectVolunteer ? 'block' : 'none'; ?>;" type="button" onclick="manageVolunteers('<?= $_COMPANY->encodeId($eventid); ?>',0)"><?=gettext('Manage Volunteers')?></button>
                            </div>

                        </div>
                    <?php } ?>
                    <!-- Event Volunteer Section: Start -->                 

                    <!-- Save/Cancel Event Buttons: Start -->
                    <div class="form-group mt-3">
                        <div class="col-md-12 text-center">
                        <?php if ($eventid) { ?>

                            <?php if($event->val('isactive') == Event::STATUS_DRAFT || $event->val('isactive') == Event::STATUS_UNDER_REVIEW) { ?>

                            <!--Buttons for Unpublished Event: Start -->
                            <button class="btn btn-affinity" type="button"  id="singleEventId" onclick="$('#submit_approval_clicked').val(0);initEventUpdateProcess(false,0);" name="submit"><?= gettext('Save Draft');?></button>

                            <?php if ($_COMPANY->getAppCustomization()['event']['approvals']['enabled'] && !$event->isSeriesEventSub() && !$event->isPublished() && !$event->isAwaiting())   {
                                $approval = $event->getApprovalObject() ?: '';
                                $event_approved_tooltip = gettext('This event has already been approved. If you would like to make changes to the event, please choose \'Save Draft\'. Saving changes to an approved event will reset the approval and you will have to \'Request Approval\' from event options on the next page.');
                                $event_pending_approval_tooltip = gettext('This event was already submitted for approval, and it is pending final approval decision. If you would like to make changes to the event, please choose \'Save Draft\'.');
                            ?>
                                <?php if((empty($approval) || $approval->isApprovalStatusDenied() || $approval->isApprovalStatusReset())){?>
                                <button class="btn btn-affinity" type="button"  id="requestEventApprovalButton" onclick="$('#submit_approval_clicked').val(1);initEventUpdateProcess(false,0);" name="submit"><?= gettext('Submit for Approval');?></button>
                                <?php } else { ?>
                                <button class="btn btn-affinity disabled" type="button"  id="requestEventApprovalButton" data-toggle="tooltip" data-placement="top" title = "<?=$approval->isApprovalStatusApproved() ? $event_approved_tooltip : $event_pending_approval_tooltip; ?>"><?= gettext('Submit for Approval');?></button>
                                <?php } ?>
                            <?php } ?>
                            <input type="hidden" id="submit_approval_clicked" value="0">

                            <button type="button" class="btn btn-affinity-gray" onClick="window.location.reload();"><?= gettext("Close");?></button>
                            <div class="my-2"><small><?= gettext('Note: You will have to select "Publish" from event options on the next page to publish the event and to send invitations to group members.');?></small></div>
                            <!--Buttons for Unpublished Event: End -->

                            <?php } else { ?>

                            <!--Buttons for Published Event: Start -->
                            <button type="button" class="confirm btn btn-affinity " id="singleEventId" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to publish this update?");?>" onclick="swal_publish();" name="submit"><?= gettext('Publish Update');?></button>
                            <button type="button" class="btn btn-affinity-gray" onClick="window.location.reload();"><?= gettext("Close");?></button>
                            <!--Buttons for Published Event: End -->

                            <?php } ?>

                        <?php } else { ?>

                            <!--Buttons for First Draft : Start -->
                            <button class="btn btn-affinity prevent-multi-clicks" id="singleEventId" type="button" onclick="initEventCreateProcess(true,0);" name="submit"><?= gettext('Save Draft to Continue');?></button>
							<button type="button" class="btn btn-affinity-gray" onClick="window.location.reload();"><?= gettext("Close");?></button>
                            <!--Buttons for First Draft : End -->

                        <?php } ?>

                        </div>
                    </div>
                    <!-- Save/Cancel Event Buttons: End -->
                    <!-- Do not delete this DIV. Here Collaborating events approvers selection input checkbox will append to this event form -->
					<div id="dynamicApproverList"></div>

				</form>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	$(function () {
        $("#content_replyto_email_checkbox").click(function () {
            if ($(this).is(":checked")) {
                $("#replyto_email").show();
            } else {
                $("#replyto_email").hide();
            }
        });
    });
</script>
	<script>
	<?php if($eventid){ ?>
        function swal_publish() {
			var is_it_past_date_event = parseInt($("#is_it_past_date_event").val());
			$(document).off('focusin.modal');
			$("#allow_past_date_event").val(1);
			var publishInputs =  '<h4><?= addslashes(gettext("Where do you want to publish?"));?></h4>'+
					'<br>'+
					'<hr>'+
					'<small><?= addslashes(gettext("Click on the option below to publish update without sending emails")) ?></small>' +
					'<br>' +
					'<br>' +
					'<button type="button" class="btn btn-affinity" onclick="createANewEvent(false,1);"><?= addslashes(gettext("Publish on this platform only")); ?></button>'+
					'<br>';

					publishInputs  = publishInputs + '<hr>'+
					'<small><?= addslashes(gettext("Click the options below to publish and send email updates to")) ;?></small>' +
					'<br>'+
					'<br>' +
					'<div class="form-group">';


						<?php if($event->val('listids') == '0'){ ?>
						publishInputs = publishInputs + '<div class="col-md-5">&nbsp;</div>'+
						'<div class="form-check  col-md-7 text-left">'+
							'<input class="form-check-input" name="publish_where_online" type="checkbox" value="online" checked disabled>'+
							'<small class="form-check-label" for="defaultCheck1">'+
								'<?= gettext("This platform");?>'+
							'</small>'+
						'</div>';

						<?php foreach($integrations as $integration){ ?>
							publishInputs  = publishInputs + '<div class="col-md-5">&nbsp;</div>'
							+'<div class="form-check  col-md-7 text-left">'
								+' <input class="form-check-input" type="checkbox" value="<?= $_COMPANY->encodeId($integration['externalId']) ?>" name="publish_where_integration[]" ' + (is_it_past_date_event ? '' : '<?=  $integration['checked']; ?>') + '>'
								+'<small class="form-check-label" for="publish_where_integrations">'
								+'<?= $integration['externalName'];?>'
								+'</small>'
							+'</div>';
						<?php } ?>

						<?php } ?>

						publishInputs  = publishInputs +
						'<div class="col-md-5">&nbsp;</div>'+
						'<div class="form-check  col-md-7 text-left">'+
							'<input class="form-check-input updateTo" name="updateTo[]" type="checkbox" value="1" '+ (is_it_past_date_event ? '' : 'checked disabled ') +'>'+
							'<small class="form-check-label" for="defaultCheck1">'+
								'<?= gettext("Emails to all RSVP\'d") ?>'+
							'</small>'+
						'</div>'+
						'<div class="col-md-5">&nbsp;</div>';


                    <?php if(!$_COMPANY->getAppCustomization()['event']['disable_email_publish']){ ?>
                        let allowPreCheckedAllMembers = '<?= ($event->val('publish_to_email') && $event->sendIcal()) ? ' checked disabled' : ($event->val('publish_to_email') ? 'checked' : '') ?>';
                        publishInputs  = publishInputs +
                        '<div class="form-check col-md-7 text-left">'+
							'<input class="form-check-input updateTo" name="updateTo[]" type="checkbox" value="2" ' + (is_it_past_date_event ? '' : allowPreCheckedAllMembers) + '>' +
							'<small class="form-check-label" for="defaultCheck2">'+
								'<?= gettext("Emails to all members") ?>'+
							'</small>'+
						'</div>';
                   <?php } ?>
                        publishInputs  = publishInputs + '</div>'+
					'<br>' +
					'<br>' +
					'<button type="button" class="btn btn-affinity" onclick="createANewEvent(false,2);"><?= addslashes(gettext("Publish Update")); ?></button>';

				publishInputs  = publishInputs + '<br>';

			Swal.fire({
				//title: 'Where do you want to publish?',
				html:publishInputs,
				showCloseButton: true,
				showCancelButton: false,
				showConfirmButton: false,
				focusConfirm: true,
				allowOutsideClick:false,
			}).then(function(result){
			});
		}

        jQuery(document).on("change", ".participation_onoff", function () {
            setMaxParticipationState();

            let val = $(this).val();
            if (val == 0) {
                $("#participation_limit_inperson").hide();
                $("#participation_limit_online").hide();
            } else {
                $("#participation_limit_inperson").show();
                $("#participation_limit_online").show();
            }
        });
		$(document).ready(function(){
			setMaxParticipationState();
			// Load calendar block setting on page load
			updateCalendarBlockSetting('<?= $_COMPANY->encodeId($event->val('groupid')); ?>',1);
		});

		<?php if(!empty($event->val('collaborating_groupids')) || !empty($event->val('collaborating_groupids_pending')) ){ ?>
			$("#chapters_selection_div").hide();
			$("#channels_selection_div").hide();
		<?php } else { ?>
			$("#chapters_selection_div").show();
			$("#channels_selection_div").show();
		<?php } ?>



    async function initEventUpdateProcess(c, w) {
        createANewEvent(c, w, 0);
    }

	<?php } else {  ?>

		function initEventCreateProcess(c,w){
            createANewEvent(c,w,0);
		}

		jQuery(document).on("change", ".participation_onoff", function () {
            setMaxParticipationState();

            let val = $(this).val();
            if (val == 0) {
                $("#max_inperson").val("");
                $("#max_inperson_waitlist").val("");
                $("#max_online").val("");
                $("#max_online_waitlist").val("");
                $("#participation_limit_inperson").hide();
                $("#participation_limit_online").hide();
            } else {
                $("#participation_limit_inperson").show();
                $("#participation_limit_online").show();
            }
        });

	<?php } ?>

		function disclaimerBtnClickHideShow(id)
		{
			$("#"+id).toggle();
		}

		function createANewEvent(c,w,r){
			$(document).off('focusin.modal');
			var formdata = $('#new-event-data')[0];
			var finaldata  = new FormData(formdata);
			finaldata.append('do_what',w);
			finaldata.append('sendCollaborationRequest',r);

			if(w==2){
				var opt = $('.updateTo:checked').map(function(_, el) {
					return $(el).val();
				}).get();
				if (!opt.length){
					opt = ['1'];
				}
				finaldata.append('send_update_to',opt);
				$("input:checkbox[name='publish_where_integration[]']:checked").each(function(){
					finaldata.append("publish_where_integration[]",$(this).val());
				});

			}
			preventMultiClick(1);
			$.ajax({
				url: 'ajax_events.php?createANewEvent=new',
				type: 'POST',
				data: finaldata,
				processData: false,
				contentType: false,
				cache: false,
				success: function(data) {
					try {
                        let jsonData = JSON.parse(data);
						if(jsonData.status == -1) { // Event date is a past date
							Swal.fire({
								title: '<?= gettext("Confirmation"); ?>!',
								text: "<?= gettext('This event\'s start time is in the past. Are you sure you want to save it?');?>",
								showCancelButton: true,
								confirmButtonText: '<?= gettext("Yes, I am sure"); ?>'
							}).then((result) => {
								if (result.value) {
									$("#allow_past_date_event").val(1); // Allow past event

									$("#singleEventId").trigger("click"); // Auto submit
								}
							})
						} else {
                            let skip_send_collaboration_request =  <?= $_COMPANY->getAppCustomization()['event']['skip_send_collaboration_request']?:0; ?>;
                            let submit_approval_clicked = $('#submit_approval_clicked').val();
                            if (jsonData.status == 1 && submit_approval_clicked == 1) {
                                $('#submit_approval_clicked').val(0);
                                if (skip_send_collaboration_request){
                                    openApprovalNoteModal('<?= $_COMPANY->encodeId($eventid); ?>');
                                } else { 
                                    proceedToEventApprovalModal('<?= $_COMPANY->encodeId($eventid); ?>');
                                }
                            }  else{
                                    swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false})
                                    .then(function(result) {
                                        if (jsonData.val) {
                                            updateEventForm(jsonData.val,false,'<?= $_COMPANY->encodeId($parent_groupid); ?>');
                                            resetContentFilterState(2);
                                        } else {
                                            if(jsonData.status == 1){
                                                let sendCollaborationRequestText = $("#sendCollaborationRequestText").val();
                                                if (!skip_send_collaboration_request && sendCollaborationRequestText) {
                                                    initEventCollaborationRequestProcess('<?= $_COMPANY->encodeId($eventid); ?>',sendCollaborationRequestText);
                                                } else{
                                                    location.reload();
                                                }
                                            } else if(jsonData.status == 2) {
                                                $("#web_conference_link").focus();
                                            } else if (jsonData.status == -3) {
                                                $("#chapter_input").focus();
                                                $("#channel_input").focus();
                                            }
                                        }
                                    });
                                
                            }
						}
					} catch(e) { 
						// Nothing to do
						swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false});
					}
				},
				error: function ( data ) {
					swal.fire({title: 'Error!',text:'Internal server error, please try after some time.',allowOutsideClick:false}).then(function() {
                    });
                }
			});
		}


        function initEventCollaborationRequestProcess (eid, sendCollaborationRequestText) {
            Swal.fire({
                text: sendCollaborationRequestText,
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "<?= gettext('Send Collaboration Request'); ?>"
            }).then((result) => {
                if (result.isConfirmed) {
                    getCollaborationRequestApprovers(eid,'EVT');
                } else {
                    location.reload();
                }
            });
        }
	</script>

	<script>
        $(document).ready(function() {
			jQuery(".confirm").popConfirm({content: ''});

			//initial for blank profile picture
			$('.initial').initial({
				charCount: 2,
				textColor: '#ffffff',
                color: window.tskp?.initial_bgcolor ?? null,
				seed: 0,
				height: 50,
				width: 50,
				fontSize: 20,
				fontWeight: 300,
				fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
				radius: 0
			});

			$( "#multiDayEvent" ).click(function() {
				if(this.checked){
					$("#event_duration").hide();
					$("#multi_day_end").show();
				}
				if(!this.checked){
					$("#event_duration").show();
					$("#multi_day_end").hide();
				}
			});

			$('.partner_organization').select2({
				placeholder: "<?= gettext('Search partner organization')?>",
				ajax: {
					url: 'ajax_events.php?search_partner_organization=1',
					data: function (params) {
						var query = {
							keyword: params.term
						}
						return query;
					},
					dataType: 'json'
				}
			});

			$(function(){
				$('.btn-group>ul>li.clickable-optgroup>a>label').click(function (e) {
					setTimeout(function(){
						checkPermissionAndMultizoneCollaboration('<?= $_COMPANY->encodeId($eventid); ?>', <?=$_COMPANY->encodeId($groupid); ?>);
					}, 100);
				});
			});
			var fontColors = <?= $fontColors; ?>;
			let redactorApp = $('#redactor_content').initRedactor('redactor_content','event',['fontcolor','counter','handle','fontsize'],fontColors,'<?= $_COMPANY->getImperaviLanguage(); ?>');
            <?php if(!$allowDescriptionUpdate){ ?>
                redactorApp.enableReadOnly();
            <?php } ?>
            redactorFocusOut('#sel1'); // function used for focus out from redactor when press tab+shift.

            <?php if (!empty($disclaimerWaivers)){ ?>
                $('#disclaimerids').multiselect({
                    nonSelectedText: "<?=  gettext('Select disclaimers'); ?>",
                    numberDisplayed: 3,
                    nSelectedText: "<?= gettext('disclaimers selected'); ?>",
                    disableIfEmpty: true,
                    enableFiltering: true,
                    maxHeight: 400,
                    enableCaseInsensitiveFiltering: true,
                    enableClickableOptGroups: true,
                    onChange: function(option, checked) {
                        let selectedOptions = $('#disclaimerids option:selected');

                        if (selectedOptions.length >= 6) {
                            // Disable unchecked options
                            var nonSelectedOptions = $('#disclaimerids option').filter(function() {
                                return !$(this).is(':selected');
                            });

                            nonSelectedOptions.each(function() {
                                var input = $('input[value="' + $(this).val() + '"]');
                                input.prop('disabled', true);
                                input.parent('li').addClass('disabled');
                            });
                        } else {
                            // Enable all options
                            $('#disclaimerids option').each(function() {
                                var input = $('input[value="' + $(this).val() + '"]');
                                input.prop('disabled', false);
                                input.parent('li').removeClass('disabled');
                            });
                        }
                    },
                    onInitialized: function(select, container) {
                        var selectedOptions = $('#disclaimerids option:selected');
                        if (selectedOptions.length >= 6) {
                            // Disable all non-selected options on initialization if limit is reached
                            var nonSelectedOptions = $('#disclaimerids option').filter(function() {
                                return !$(this).is(':selected');
                            });

                            nonSelectedOptions.each(function() {
                                var input = $('input[value="' + $(this).val() + '"]');
                                input.prop('disabled', true);
                                input.parent('li').addClass('disabled');
                            });
                        }
                    }
          
                });


            <?php } ?>



		});
        
		jQuery(function() {
			jQuery("#start_date").datepicker({
				prevText: "click for previous months",
				nextText: "click for next months",
				showOtherMonths: true,
				selectOtherMonths: false,
				dateFormat: 'yy-mm-dd',
                beforeShow:function(textbox, instance){
                    $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
                }
			});
				jQuery("#end_date").datepicker({
				prevText: "click for previous months",
				nextText: "click for next months",
				showOtherMonths: true,
				selectOtherMonths: true,
				dateFormat: 'yy-mm-dd',
                beforeShow:function(textbox, instance){
                    $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
                }
			});
		});

		$(function() {			
			$(".hasDatepicker, .ui-datepicker, .ui-datepicker-trigger").click(function(event) {
				event.stopPropagation();
			});	
		});
	</script>

	<script>
		$(function() {
			$("#hour_duration").change(function() {
				var selectedValue = $(this).val();
				if (selectedValue == 24) {
					$("#minutes_duration").attr('disabled', true);
					$("#minutes_duration").prop('selectedIndex', 0);
				} else {
					$("#minutes_duration").attr('disabled', false);
				}
			});
		});
	</script>
    <script>
        $(document).ready(function () {
            var currentSelection = $("#event_scope").val();
            eventScopeSelector(currentSelection);
        });
    </script>
    <script>

		function eventScopeSelector(i) {
            if (i == 'dynamic_list') {
                $("#isprivate").prop("checked", "checked");
            }

            if (i == 'zone' || i == 'group') {
                $("#chapter").val(0);
                $("#chapter").prop("disabled", true);
                $("#collaboration_selection").html('');
				if (i == 'group'){
					$("#chapters_selection_div").show();
					$("#channels_selection_div").show();
				}
				$("#list_selection").hide();
            } else if (i == 'collaborating_groups') {
                $("#chapter").val(0);
                $("#chapter").prop("disabled", true);
                getGroupsForCollaboration('<?= $_COMPANY->encodeId(0)?>','<?= $_COMPANY->encodeId($parent_groupid)?>')

				$("#chapters_selection_div").hide();
				$("#channels_selection_div").hide();
				$("#list_selection").hide();
				$("#group_level_only").hide();
            } else if (i == 'dynamic_list'){
				$("#chapter").prop("disabled", true);
                $("#collaboration_selection").html('');
				$("#chapters_selection_div").hide();
				$("#channels_selection_div").hide();
				$("#list_selection").show();
				$("#group_level_only").hide();
			} else {
				$("#list_selection").hide();
                $("#chapter").prop("disabled", false);
            }            
        }

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
    </script>

<script src="../vendor/js/datatables-2.1.8/datatables.min.js"></script>
<script>

    $('#template_list').DataTable( {
		"order": [],
		"bPaginate": false,
		"bInfo" : false
		
    } );

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

<script>
//On Enter Key...
$(function(){
        $("#tz_show,#TeamsSignIn, #GoogleSignIn, #ZoomSignIn").keypress(function (e) {
        if (e.keyCode == 13) {
            $(this).trigger("click");
        }
    });
});

$(document).ready(function(){
	$('.multiselect').attr( 'tabindex', '0' );
    $(".redactor-voice-label").text("<?= gettext('Description');?>");
    // for on change
    let event_attendence_type = document.getElementById("event_attendence_type");
    if(event_attendence_type){
        changeEventAttendenceType(event_attendence_type.value);
    }
});

//On ESC Key...
$(document).keyup(function(e) {
	if (e.keyCode == 27) {
		$('.dropdown-menu').removeClass('show');

	}
});

function validateEventFormInputs(c, w) {
    return new Promise((resolve, reject) => {
        var formdata = $('#new-event-data')[0];
        var finaldata = new FormData(formdata);
        finaldata.append('do_what', w);
        $.ajax({
            url: 'ajax_events.php?validateEventFormInputs=new',
            type: 'POST',
            data: finaldata,
            processData: false,
            contentType: false,
            cache: false,
            success: function(data) {
                if (data == 1) {
                    resolve(true);
                } else {
                    try {
                        let jsonData = JSON.parse(data);
                        if(jsonData.status == -1) { // Event date is a past date
							Swal.fire({
								title: '<?= gettext("Confirmation"); ?>!',
								text: "<?= gettext('This event\'s start time is in the past. Are you sure you want to save it?');?>",
								showCancelButton: true,
								confirmButtonText: '<?= gettext("Yes, I am sure"); ?>'
							}).then((result) => {
								if (result.value) {
									$("#allow_past_date_event").val(1); // Allow past event

									$("#singleEventId").trigger("click"); // Auto submit
								}
							})
						} else {
                            swal.fire({title: jsonData.title, text: jsonData.message, allowOutsideClick: false});
                            resolve(false);
                        }
                    } catch (e) {
                        resolve(true);
                    }
                }
            },
            error: function() {
                resolve(false);
            }
        });
    });
}

function proceedToEventApprovalModal(e) {
    $.ajax({
        url: 'ajax_events.php?proceedToEventApprovalModal=1',
        type: 'GET',
        data:{eventid:e},
        success: function(data) {
            try {
                let jsonData = JSON.parse(data);
                if (jsonData.status == 1){
                    $('input[name="version"]').val(jsonData.val);
                    openApprovalNoteModal(e);
                } else {
                    swal.fire({title: jsonData.title, text: jsonData.message, allowOutsideClick: false});
                }

            } catch (e) {}
        }
    });
}

function showApprovalStatus(s) {
    let title = '';
    let message = '';
    if (s == 'approved') {
        message = '<?= addslashes(gettext("This event was previously submitted for approval and it has been approved"))?>';
    } else {
        message = '<?= addslashes(gettext("This event was previously submitted for approval and it is currently pending approval"))?>' + s;
    }
    swal.fire({title: title, text: message, allowOutsideClick: false});
}

$(document).ready(function() {
    $("body").tooltip({ selector: '[data-toggle=tooltip]' });
});
// visibility toggle for chapter and channel selection, on checking/unchecking group level dynamic lists 
//$('#list_scope').on('change', function() {
//        toggleChapterChannelBoxVisibility(); 
//});
</script>