<?php

class EventVolunteer extends Teleskope
{
    private $user = null;
    private $careof_user = null;
    private $createdby_user = null;

    public static function GetTopicType(): string
    {
        return self::TOPIC_TYPES['EVENT_VOLUNTEER'];
    }

    public static function GetEventVolunteer(int $volunteerid): ?Self
    {
        global $_COMPANY;

        $volunteer = Event::GetEventVolunteer($volunteerid);
        if (empty($volunteer)) {
            return null;
        }

        return new EventVolunteer($volunteerid, $_COMPANY->id(), $volunteer[0]);
    }

    public function updateExternalEventVolunteer(int $eventid, string $firstname, string $lastname, string $email): int
    {
        global $_USER;

        $external_volunteer = EventVolunteer::GetExternalEventVolunteerByEmail($eventid, $email);
        if ($external_volunteer && $external_volunteer['volunteerid'] !== $this->id()) {
            return -2;
        }

        $other_data = json_encode([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'external_user_email' => $email,
            'modifiedby_userid' => $_USER->id(),
        ]);

        $retval = self::DBUpdatePS('
                UPDATE `event_volunteers` SET `other_data` = ? WHERE `volunteerid` = ?
            ',
            'xi',
            $other_data,
            $this->id()
        );

        self::LogObjectLifecycleAudit('update', 'EVTVOL', $this->id(), 0, [
            'operation_details' => [
                'opname' => 'update_external_event_volunteer',
                'old' => [
                    'other_data' => $this->val('other_data'),
                ],
                'new' => [
                    'other_data' => $other_data,
                ],
            ],
        ]);

        return $retval;
    }

    public function isExternalVolunteer(): bool
    {
        return (int) $this->val('userid') === 0;
    }

    public function getFirstName(): string
    {
        if ($this->isExternalVolunteer()) {
            return ($this->getOtherData())['firstname'];
        }

        /**
         * In case we do an EventVolunteer::Hydrate($id, $volunteer)
         * And $volunteer already has the user-data from a JOIN on user's table
         * So we can use the already fetched data and avoid an extra query
         */
        if (!empty($this->val('firstname'))) {
            return $this->val('firstname');
        }

        return $this->getUser()->val('firstname');
    }

    public function getLastName(): string
    {
        if ($this->isExternalVolunteer()) {
            return ($this->getOtherData())['lastname'];
        }

        /**
         * In case we do an EventVolunteer::Hydrate($id, $volunteer)
         * And $volunteer already has the user-data from a JOIN on user's table
         * So we can use the already fetched data and avoid an extra query
         */
        if (!empty($this->val('lastname'))) {
            return $this->val('lastname');
        }

        return $this->getUser()->val('lastname');
    }

    /**
     * Helper method to get the volunteer email
     * This method works for both internal and external volunteers
     */
    public function getVolunteerEmail(): string
    {
        if ($this->isExternalVolunteer()) {
            $other_data = $this->getOtherData();
            return $other_data['external_user_email'] ?? '';
        }

        /**
         * In case we do an EventVolunteer::Hydrate($id, $volunteer)
         * And $volunteer already has the user-data from a JOIN on user's table
         * So we can use the already fetched data and avoid an extra query
         */
        if (!empty($this->val('email'))) {
            return $this->val('email');
        }

        return $this->getUser()->val('email');
    }

    private function getUser(): User
    {
        if ($this->user) {
            return $this->user;
        }

        $this->user = User::GetUser($this->val('userid'));
        return $this->user;
    }

    private function getOtherData(): array
    {
        if (!$this->val('other_data')) {
            return [];
        }

        return json_decode($this->val('other_data'), true);
    }

    public function getCareofUser(): ?User
    {
        if ($this->careof_user) {
            return $this->careof_user;
        }

        $this->careof_user = User::GetUser($this->val('external_user_careofid'));
        return $this->careof_user;
    }

    public function getCreatedByUser(): ?User
    {
        if ($this->createdby_user) {
            return $this->createdby_user;
        }

        $this->createdby_user = User::GetUser($this->val('createdby'));
        return $this->createdby_user;
    }

    public function deleteIt(): int
    {
        $retval = self::DBMutate("DELETE FROM `event_volunteers` WHERE `volunteerid` = {$this->id()}");
        self::LogObjectLifecycleAudit('delete', 'EVTVOL', $this->id(), 0, $this->toArray());
        return $retval;
    }

    public static function GetExternalEventVolunteerByEmail(int $eventid, string $external_user_email): ?array
    {
        global $_COMPANY;

        $result = self::DBROGetPS('
            SELECT      `event_volunteers`.*
            FROM        `event_volunteers`
                JOIN  `events` ON `event_volunteers`.`eventid` = `events`.`eventid`
            WHERE       `events`.`eventid` = ?
                AND         `events`.`companyid` = ?
                AND         `event_volunteers`.`userid` = 0
                AND         `event_volunteers`.other_data->>"$.external_user_email"  = ?
        ',
            'iis',
            $eventid, $_COMPANY->id(), $external_user_email
        );

        return $result[0] ?? null;
    }
}
