<?php
include_once __DIR__.'/head.php';
$pageTitle = "Add|Update Touch Point Event";

//Data Validation
if (
    ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 1 ||
    ($team = Team::GetTeam($teamid)) == null ||
    ($eventid = $_COMPANY->decodeId($_GET['eventid'])) < 0  ||
    ($touchPointId = $_COMPANY->decodeId($_GET['touchpointid'])) < 0
) {
     error_and_exit(HTTP_BAD_REQUEST);
}
$groupid = $team->val('groupid');
$pageTitle = sprintf(gettext("Create New %s Event"),$_COMPANY->getAppCustomization()['teams']['name']);
$type = Event::GetEventTypesByZones([$_ZONE->id()]);
$touchPointDetail = $team->getTodoDetail($touchPointId);
$submitButton = gettext("Send Invitations");

$availableDaysToSchedule = array();

 if ($_COMPANY->getAppCustomization()['my_schedule']['enabled']) {
    $mentors = $team->getTeamMembersBasedOnSysRoleid(2);

    $sourceDateTimeRanage = array();
    if (!empty($mentors)) {
        $mentorUserid = $mentors[0]['userid'];
        $timeSchedules = UserSchedule::GetAllUsersSchedules($mentorUserid,true,'team_event', $team->val('groupid'));
        foreach ($timeSchedules as $timeSchedule) {

            $days = Date::GetDatesFromRange($timeSchedule['start_date_in_user_tz'], $timeSchedule['end_date_in_user_tz'], 'd-m-Y');

            foreach (Date::GetDatesFromRange($timeSchedule['start_date_in_user_tz'], $timeSchedule['end_date_in_user_tz'], 'Y-m-d') as $dt) {
                foreach ($timeSchedule['weekly_available_time'] as $time) {
                    $sourceDateTimeRanage[] = array($dt . ' ' . $time['daily_start_time_in_user_tz'], $dt . ' ' . $time['daily_end_time_in_user_tz']);
                }
            }
            $availableDaysToSchedule = array_merge($availableDaysToSchedule, $days);
        }
    }
}



$timezone = "UTC";
if (isset($_GET['timezone'])){
    $timezone = (string)$_GET['timezone'];
}
$event = null;
if ($eventid>0) {
    $event = Event::GetEvent($eventid);
    $submitButton = gettext("Send Updates");
    $pageTitle = sprintf(gettext("Update %s"),$_COMPANY->getAppCustomization()['teams']['name']);
    $event_tz = (!empty($event->val('timezone'))) ? $event->val('timezone') : $timezone;
}

$group = Group::GetGroup($team->val('groupid'));
$touchPointTypeConfig = $group->getTouchpointTypeConfiguration();
if (!empty($availableDaysToSchedule) && $touchPointTypeConfig['enable_mentor_scheduler']) {
    if ($event) {
        $evtDateObj = Date::ConvertDatetimeTimezone($event->val('start'),$event_tz, $event_tz);
        $preSelectDate = $evtDateObj->format('Y-m-d');
    }
   include __DIR__ . '/views/createUpdateTouchPoint_quick_event_scheduler_html.php';
} else {

    if ($eventid > 0) {
        
       
        $s_date = $db->covertUTCtoLocalAdvance("Y-m-d", '', $event->val('start'), $event_tz);
        $s_hrs = $db->covertUTCtoLocalAdvance("h", '', $event->val('start'), $event_tz);
        $s_mmt = $db->covertUTCtoLocalAdvance("i", '', $event->val('start'), $event_tz);
        $s_prd = $db->covertUTCtoLocalAdvance("A", '', $event->val('start'), $event_tz);

        #End Date
        $e_date = '';
        $e_hrs = '';
        $e_mnt = '';
        $e_prd = '';
    
        if ($event->getDurationInSeconds() > 86400){ #Multiday event
            $e_date = $db->covertUTCtoLocalAdvance("Y-m-d", '', $event->val('end'), $event_tz);
            $e_hrs = $db->covertUTCtoLocalAdvance("h", '', $event->val('end'), $event_tz);
            $e_mnt = $db->covertUTCtoLocalAdvance("i", '', $event->val('end'), $event_tz);
            $e_prd = $db->covertUTCtoLocalAdvance("A", '', $event->val('end'), $event_tz);

        } else { #One day event
            $diff = $db->roundTrimTimeDiff($event->val('start'). ' '. $event_tz, $event->val('end'). ' '. $event_tz);
            $e_hrs = $diff[0];
            $e_mnt = $diff[1];
        }
        
    }
    include __DIR__ . '/views/createUpdateTouchPoint_event_html.php';
}

