<?php

use Spatie\IcalendarGenerator\Components\Calendar as SpatieCalendar;
use Spatie\IcalendarGenerator\Components\Event as SpatieEvent;
use Spatie\IcalendarGenerator\Enums\Classification as SpatieClassification;
use Spatie\IcalendarGenerator\Enums\EventStatus as SpatieEventStatus;
use Spatie\IcalendarGenerator\Enums\ParticipationStatus as SpatieParticipationStatus;
use Spatie\IcalendarGenerator\Properties\TextProperty as SpatieTextProperty;

class CalendarICS
{
    /**
     * @param Event $event ... pass event by reference for caching purposes
     * @param string $method either 'CANCEL' or 'REQUEST'
     * @param string $recipient_email
     * @param int $recipient_rsvp_status
     * @param bool $includeWebConf
     * @return string text representing contents of ics file
     * @throws Exception
     */
    public static function GenerateIcsFileForEvent(Event &$event, string $method, string $recipient_email, int $recipient_rsvp_status, bool $includeWebConf = true): string
    {
        global $_COMPANY, $_ZONE;

        // Paramter Validation
        $method = $method === 'CANCEL' ? 'CANCEL' : 'REQUEST';

        $calendar = SpatieCalendar::create()
            ->withoutAutoTimezoneComponents()
            ->productIdentifier('-//Teleskope LLC//Affinities Calendar v24.04');

        $web_conf_info = "-::~:~::~:~:~:~:~:~::~:~::-\n";
        if ($event->isPrivateEvent()) {
            $web_conf_info .= Event::PRIVATE_EVENT_MESSAGE . "\n";
            $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\n";
        }
        if (($event->val('add_photo_disclaimer') && $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['enabled'])) {
            $web_conf_info .= $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['disclaimer'] . "\n";
            $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\n";
        }

        $web_conf_url = $event->getWebConferenceLink();

        if ($includeWebConf && !empty($web_conf_url)) {
            $web_conf_details = preg_replace('/\s\s+/', ' ', $event->val('web_conference_detail'));

            $web_conf_info .= "Join the event (Web conference link)': {$web_conf_url} \n";
            $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\n";
            $web_conf_info .= $event->val('web_conference_sp') . " Details: {$web_conf_details} \n";
            $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\n";
        }

        $url = $event->getShareableLink();
        $event_contact = $event->val('event_contact');

        $web_conf_info .= "Event Page: {$url} \n";
        $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\n";
        $web_conf_info .= "Event Contact: {$event_contact} \n";
        $web_conf_info .= "-::~:~::~:~:~:~:~:~::~:~::-\n";
        $web_conf_info .= "   \n";

        $tz_utc = new DateTimeZone('UTC');
        $start = new DateTime($event->val('start'), $tz_utc);
        $end = new DateTime($event->val('end'), $tz_utc);

        $from = Group::BuildFromEmailLabel($event->val('collaborating_groupids') ?: $event->val('groupid'), $event->val('chapterid'), $event->val('channelid'));

        $app_type = $_ZONE->val('app_type');
        $rsvp_addr = $_COMPANY->getRsvpEmailAddr($app_type);

        $venue = $event->val('eventvanue');
        $address = $event->val('vanueaddress');

        $location = empty($address) ? $venue : "{$venue} ({$address})";

        $additional_location_details = '';
        if (!empty($location)) {
            // Add the following additional details to the description
            if (!empty($event->val('venue_room'))) {
                $room = preg_replace('/\s\s+/', ' ', $event->val('venue_room'));
                $additional_location_details .= "Room: {$room} \n";
            }
            if (!empty($event->val('venue_info'))) {
                $info = preg_replace('/\s\s+/', ' ', $event->val('venue_info'));
                $additional_location_details .= "Additional Information: {$info} \n";
            }
            if (!empty($additional_location_details)) {
                $additional_location_details = "-::~:~::~:~:~:~:~:~::~:~::-\n" . $additional_location_details;
            }
        }

        $event_title = html_entity_decode($event->val('eventtitle'));

        $event_description = $event->val('event_description');
        $event_description = preg_replace('/\s\s+/', ' ', $event_description);
        $event_description = $additional_location_details . $web_conf_info . html_entity_decode($event_description);

        $rsvp_to_participation_status_map = [
            Event::RSVP_TYPE['RSVP_DEFAULT'] => SpatieParticipationStatus::needs_action(),
            Event::RSVP_TYPE['RSVP_YES'] => SpatieParticipationStatus::accepted(),
            Event::RSVP_TYPE['RSVP_MAYBE'] => SpatieParticipationStatus::tentative(),
            Event::RSVP_TYPE['RSVP_NO'] => SpatieParticipationStatus::declined(),
            Event::RSVP_TYPE['RSVP_INPERSON_YES'] => SpatieParticipationStatus::accepted(),
            Event::RSVP_TYPE['RSVP_INPERSON_WAIT'] => SpatieParticipationStatus::tentative(),
            Event::RSVP_TYPE['RSVP_ONLINE_YES'] => SpatieParticipationStatus::accepted(),
            Event::RSVP_TYPE['RSVP_ONLINE_WAIT'] => SpatieParticipationStatus::tentative(),
            Event::RSVP_TYPE['RSVP_INPERSON_WAIT_CANCEL'] => SpatieParticipationStatus::declined(),
            Event::RSVP_TYPE['RSVP_ONLINE_WAIT_CANCEL'] => SpatieParticipationStatus::declined(),
        ];

        $isactive_to_event_status_map = [
            Teleskope::STATUS_INACTIVE => SpatieEventStatus::cancelled(),
            Teleskope::STATUS_ACTIVE => SpatieEventStatus::confirmed(),
            Teleskope::STATUS_DRAFT => SpatieEventStatus::tentative(),
            Teleskope::STATUS_UNDER_REVIEW => SpatieEventStatus::tentative(),
            Teleskope::STATUS_UNDER_APPROVAL => SpatieEventStatus::tentative(),
            Teleskope::STATUS_AWAITING => SpatieEventStatus::tentative(),
            Teleskope::STATUS_PURGE => SpatieEventStatus::cancelled(),
        ];

        if ($method === 'CANCEL') {
            // For cancel method always force event status to cancel
            $event_status = SpatieEventStatus::cancelled();
        } else {
            $event_status = $isactive_to_event_status_map[$event->val('isactive')];
        }

        $spatie_event = SpatieEvent::create()
            ->name($event_title)
            ->description($event_description)
            ->uniqueIdentifier($event->getEventUid())
            ->startsAt($start)
            ->endsAt($end)
            ->organizer($rsvp_addr, $from)
            ->address($location)
            ->status($event_status)
            ->appendProperty(SpatieTextProperty::create('SEQUENCE', time())
            );

        if ($event->isPrivateEvent()) {
            $spatie_event->classification(SpatieClassification::private());
        }

        // For events that are associated with teams, add all paricipants to the calendar.
        if ($event->val('teamid')) {
            $team = Team::GetTeam($event->val('teamid'));
            $team_members = $team->getTeamMembers(0);
            //$all_rsvps = $event->getEventRSVPsList__memoized();
            foreach ($team_members as $member) {
                // Do not show participation status of users except for the recipient
                //$member_rsvp_status = Arr::SearchColumnReturnColumnVal($all_rsvps, $member['email'], 'email', 'joinstatus') ?: 0;
                //if ($member_rsvp_status == Event::RSVP_TYPE['RSVP_INPERSON_WAIT_CANCEL'] || $member_rsvp_status == Event::RSVP_TYPE['RSVP_ONLINE_WAIT_CANCEL']) {
                //    continue; // Do not show users who have been removed by Admin.
                //}

                $member_rsvp_status = 0;
                if ($member['email'] === $recipient_email) {
                    $member_rsvp_status = $recipient_rsvp_status;
                }

                $spatie_event->attendee(
                    email: $member['email'],
                    participationStatus: $rsvp_to_participation_status_map[$member_rsvp_status],
                    requiresResponse: true
                );
            }
        } else { // Only add the single recipient
            $rsvp_status = $rsvp_to_participation_status_map[$recipient_rsvp_status];
            $spatie_event->attendee(
                email: $recipient_email,
                participationStatus: $rsvp_status,
                requiresResponse: true
            );
        }

        if (!$event->val('calendar_blocks')) {
            $spatie_event = $spatie_event->transparent();
        }

        $spatie_event->alertMinutesBefore(15, 'Reminder');

        return $calendar
            ->appendProperty(
                SpatieTextProperty::Create('METHOD', $method)
            )
            ->event($spatie_event)
            ->get();
    }

    public static function GenerateIcsFileForTeamTouchpoint(Team &$team, array $touchpoint): string
    {
        global $_COMPANY, $_ZONE, $_USER;

        $calendar = SpatieCalendar::create()
            ->productIdentifier('-//Teleskope LLC//Affinities Calendar v24.04');

        $tz_utc = new DateTimeZone('UTC');
        $duedate = empty($touchpoint['duedate']) ? 'now' : $touchpoint['duedate'];
        $curr_dt = new DateTime('now', $tz_utc);
        $curr_dt->modify('+8 hour');
        $end_dt = new DateTime($duedate, $tz_utc);
        if ($end_dt < $curr_dt) {
            $end_dt = $curr_dt;
        }

        $start_dt = clone $end_dt;
        $start_dt->modify('-1 hour');

        $from = $_USER->getFullName();
        $rsvp_addr = $_USER->val('email');

        $name = html_entity_decode($touchpoint['tasktitle']) ?? '';
        $description_html = $touchpoint['description'] ?? '';
        $description_text = Html::SanitizeHtml($description_html);
        $uniqueid = base64_encode($touchpoint['companyid'] .'.'. $touchpoint['taskid'] . '.' . $_USER->id());

        $location = 'TBD';

        // Note for ICS files we will not set organizer to allow import.
        $spatie_event = SpatieEvent::create()
            ->name($name)
            ->description($description_text)
            ->uniqueIdentifier($uniqueid)
            ->startsAt($start_dt)
            ->endsAt($end_dt)
            ->organizer($rsvp_addr, $from)
            ->address($location)
            ->appendProperty(
                SpatieTextProperty::create('X-ALT-DESC;FMTTYPE=TEXT/HTML', $description_html)
            )
        ;


        // For events that are associated with teams, add all paricipants to the calendar.

        $team_members = $team->getTeamMembers(0);
        foreach ($team_members as $member) {
            $spatie_event->attendee(
                email: $member['email'],
                participationStatus: SpatieParticipationStatus::needs_action(),
                requiresResponse: true
            );
        }

        return $calendar
            ->appendProperty(
                SpatieTextProperty::Create('METHOD', 'REQUEST')
            )
            ->event($spatie_event)
            ->get();
    }
}