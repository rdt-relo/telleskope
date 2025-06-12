<?php
require_once __DIR__ . '/../head.php';

$check2 = date('Ymd');
if ($check2 > '20250530') {
    echo "<p>Migration expired</p>";
    echo "<p>Exiting</p>";
    exit();
}

$db	= new Hems();
Auth::CheckSuperSuperAdmin();
$companies = $_SUPER_ADMIN->super_get("SELECT companyid FROM companies where isactive=1");
$updated_data = 0;
echo "<p>Team Event Schedule Association Migration Starting...</p>";
foreach ($companies as $company) {
    $companyid = $company["companyid"];
    $_COMPANY = Company::GetCompany($companyid);
    $events_data = $_SUPER_ADMIN->super_get("SELECT `eventid`,groupid FROM `events` WHERE `companyid`={$companyid} AND `eventclass`='teamevent' AND `schedule_id`=0");

    // Reset the data correctly
    if(!empty($events_data)){
        foreach($events_data as $eventRow){
            $eventid = $eventRow['eventid'];
            $event = Event::GetEvent($eventid);
            if ($event->getDurationInSeconds() > 86400) { // Skip multi-day events
                continue;
            } 
            $teamid = $event->val('teamid');
            // get Team Member with Mentor Role
            $teamMentors = $_SUPER_ADMIN->super_get("SELECT `team_members`.`userid` FROM `team_members` JOIN team_role_type USING(`roleid`) WHERE team_members.`teamid`= {$teamid} and team_role_type.`sys_team_role_type`=2");

            if (!empty($teamMentors)) {
                $mentorUserId = $teamMentors[0]['userid'];
                $date = $db->covertLocaltoUTC("Y-m-d", $event->val('start'), 'UTC');
                [$newAvailableSlots,$newBufferSkippedSlots] = UserSchedule::GetUserAvailableBookingSlots($mentorUserId ,$date,'UTC','team_event', $eventRow['groupid']);

                if (!empty($newAvailableSlots)) {
                    $eventStartInTime = strtotime($event->val('start'));
                    $eventDurationInMinutes = $event->getDurationInSeconds()/60;
                    foreach($newAvailableSlots as $slot) {
                        $slotDateTime = strtotime($slot['date'].' '.$slot['start24']);
                        $slotDuration = $slot['duration']['minutes'];

                        if ($eventDurationInMinutes == $slotDuration &&  $eventStartInTime == $slotDateTime ) {
                           $schedule_id = $slot['schedule_id'];
                           $event->updateScheduleId($schedule_id);
                           $updated_data++;
                           break;
                        }
                    }
                }
            }
        }
    }
    $_COMPANY = null;
}

echo "<p>Total {$updated_data} team events migrated. </p>";
echo "<p>Migration Done...</p>";