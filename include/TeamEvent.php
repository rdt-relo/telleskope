<?php
// Do no use require_once as this class is included in Company.php.

class TeamEvent extends Event {

	public static function GetEvent(int $id) {
		$parent_obj = parent::GetEvent($id);
        if($parent_obj){
            return new TeamEvent($parent_obj->id,$parent_obj->cid,$parent_obj->fields);
        }
		return $parent_obj;
	}

    /**
     * Create New Event
     */

    public static function CreateNewTeamEvent(int $teamid, int $groupid, string $chapterids, string $eventtitle, string $start, string $end, string $event_tz, string $eventvanue, string $vanueaddress, string $event_description, int $eventtype, string $invited_groups, int $max_inperson, int $max_inperson_waitlist, int $max_online, int $max_online_waitlist, int $event_attendence_type, string $web_conference_link, string $web_conference_detail, string $web_conference_sp, int $checkin_enabled, string $collaborate, int $channelid, int $event_series_id, string $custom_fields_input, string $event_contact, string $venue_info, string $venue_room, int $isprivate, string $eventclass = 'teamevent', int $add_photo_disclaimer=0, int $calendar_blocks=1)
    {
        global $_COMPANY;
        $eid = self::CreateNewEvent($groupid, $chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $event_series_id, $custom_fields_input, $event_contact, $venue_info, $venue_room, $isprivate, $eventclass, $add_photo_disclaimer, $calendar_blocks, '', '0', false);
        if ($eid) {
            self::DBUpdate("UPDATE events set teamid={$teamid} WHERE companyid={$_COMPANY->id()} AND eventid={$eid}");
        }
        return $eid;
    }
    /**
     * Update Event
     */

    public function updateTeamEvent(string $chapterids, string $eventtitle, string $start, string $end, string $event_tz, string $eventvanue, string $vanueaddress, string $event_description, int $eventtype, string $invited_groups, int $max_inperson, int $max_inperson_waitlist, int $max_online, int $max_online_waitlist, int $event_attendence_type, string $web_conference_link, string $web_conference_detail, string $web_conference_sp, int $checkin_enabled, string $collaborate, int $channelid, string $custom_fields_input, string $event_contact, int $add_photo_disclaimer, string $venue_info, string $venue_room, int $isprivate, int $calendar_blocks) {
        
       $retVal = $this->updateEvent($chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $custom_fields_input, $event_contact, $add_photo_disclaimer, $venue_info, $venue_room, $isprivate, $calendar_blocks, '', '0', false);

       return $retVal;
    }

    

}
