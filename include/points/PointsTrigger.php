<?php

class PointsTrigger extends Teleskope
{
    const POINT_TRIGGER_TYPES = array (
        'DAILY_EARNINGS'            => 'Daily Earnings',
        'NEW_COMMENT'               => 'Comment',
        'EVENT_RSVP'                => 'Event RSVP',
        'EVENT_RSVP_YES'            => 'Event RSVP Yes',
        'EVENT_RSVP_NOT_YES'        => 'Event RSVP No/Maybe',
        'EVENT_CHECK_IN'            => 'Event Check In',
        'EVENT_CHECK_OUT'           => 'Event Check Out',
        'COMMENT_LIKE'              => 'Like',
        'RECOGNITION_GIVEN'         => 'Recognition Given',
        'RECOGNITION_RECEIVED'      => 'Recognition Received',
        'VOLUNTEERING_SIGN_UP'      => 'Volunteer - Sigup',
        'ON_GROUP_JOIN'             => 'Group Join',
        'ON_SURVEY_RESPONSE'        => 'Survey Response',
        'COMMENT_UNLIKE'            => 'Unlike',
        'DELETE_COMMENT'            => 'Uncomment',
        'DELETE_EVENT_VOLUNTEER'    => 'Volunteer - Withdraw',
        'DELETE_RECOGNITION'        => 'Recognition Delete',
        'ON_GROUP_LEAVE'            => 'Group Leave',
    );

    public static function GetPointsTrigger(int $id): ?PointsTrigger
    {
        global $_COMPANY;

        $sql = "SELECT * FROM `points_trigger_history` WHERE `points_trigger_history_id` = {$id} AND `company_id` = {$_COMPANY->id()}";

        $result = self::DBGet($sql);

        if (!$result) {
            return null;
        }

        return new PointsTrigger($id, $_COMPANY->id(), $result[0]);
    }

    public static function Create(string $points_trigger_key, int $userid, int $contextid, int $triggeredby_userid, int $groupid, string $collaborating_groupids): PointsTrigger
    {
        global $_COMPANY;
        global $_ZONE;

        if (!in_array($points_trigger_key, array_keys(self::POINT_TRIGGER_TYPES))) {
            Logger::Log("Tried creating invalid trigger history - {$points_trigger_key}", Logger::SEVERITY['FATAL_ERROR']);
        }

        // Since the only string inpput i.e. $points_trigger_key has been validated above, we can use DBInsert instead of DBInsertPS
        $points_trigger_history_id = self::DBInsert("INSERT INTO `points_trigger_history` (`points_trigger_key`, `user_id`, `company_id`, `zone_id`, `contextid`, `groupid`, `collaborating_groupids`) VALUES ('{$points_trigger_key}', {$userid}, {$_COMPANY->id()}, {$_ZONE->id()}, {$contextid}, $groupid, '{$collaborating_groupids}')");

        return self::GetPointsTrigger($points_trigger_history_id);
    }

    public function getRelatedTriggers(): array
    {
        $triggers = (function ($points_trigger_key) {
            global $_COMPANY;

            switch ($points_trigger_key) {
                case 'EVENT_RSVP_NOT_YES':
                    return self::DBGet(<<<SQL
                        SELECT  *
                        FROM    `points_trigger_history`
                        WHERE   `points_trigger_history_id` != {$this->id()}
                        AND     `points_trigger_key` IN ('EVENT_RSVP_YES', 'EVENT_RSVP_NOT_YES')
                        AND     `contextid` = {$this->val('contextid')}
                        AND     `user_id` = {$this->val('user_id')}
                        AND     `company_id` = {$_COMPANY->id()}
                        ORDER BY `created_at` DESC
                    SQL);

                case 'EVENT_CHECK_OUT':
                    return self::DBGet(<<<SQL
                        SELECT  *
                        FROM    `points_trigger_history`
                        WHERE   `points_trigger_history_id` != {$this->id()}
                        AND     `points_trigger_key` IN ('EVENT_CHECK_IN', 'EVENT_CHECK_OUT')
                        AND     `contextid` = {$this->val('contextid')}
                        AND     `user_id` = {$this->val('user_id')}
                        AND     `company_id` = {$_COMPANY->id()}
                        ORDER BY `created_at` DESC
                    SQL);

                case 'DELETE_COMMENT':
                    return self::DBGet(<<<SQL
                        SELECT  *
                        FROM    `points_trigger_history`
                        WHERE   `points_trigger_history_id` != {$this->id()}
                        AND     `points_trigger_key` = 'NEW_COMMENT'
                        AND     `contextid` = {$this->val('contextid')}
                        AND     `company_id` = {$_COMPANY->id()}
                        ORDER BY `created_at` DESC
                    SQL);

                case 'COMMENT_UNLIKE':
                    return self::DBGet(<<<SQL
                        SELECT  *
                        FROM    `points_trigger_history`
                        WHERE   `points_trigger_history_id` != {$this->id()}
                        AND     `points_trigger_key` IN ('COMMENT_LIKE', 'COMMENT_UNLIKE')
                        AND     `contextid` = {$this->val('contextid')}
                        AND     `user_id` = {$this->val('user_id')}
                        AND     `company_id` = {$_COMPANY->id()}
                        ORDER BY `created_at` DESC
                    SQL);

                case 'DELETE_RECOGNITION':
                    return self::DBGet(<<<SQL
                        SELECT  *
                        FROM    `points_trigger_history`
                        WHERE   `points_trigger_history_id` != {$this->id()}
                        AND     `points_trigger_key` IN ('RECOGNITION_GIVEN', 'RECOGNITION_RECEIVED')
                        AND     `contextid` = {$this->val('contextid')}
                        AND     `company_id` = {$_COMPANY->id()}
                        ORDER BY `created_at` DESC
                    SQL);

                case 'DELETE_EVENT_VOLUNTEER':
                    return self::DBGet(<<<SQL
                        SELECT  *
                        FROM    `points_trigger_history`
                        WHERE   `points_trigger_history_id` != {$this->id()}
                        AND     `points_trigger_key` IN ('VOLUNTEERING_SIGN_UP', 'DELETE_EVENT_VOLUNTEER')
                        AND     `contextid` = {$this->val('contextid')}
                        AND     `company_id` = {$_COMPANY->id()}
                        ORDER BY `created_at` DESC
                    SQL);

                case 'ON_GROUP_LEAVE':
                    return self::DBGet(<<<SQL
                        SELECT  *
                        FROM    `points_trigger_history`
                        WHERE   `points_trigger_history_id` != {$this->id()}
                        AND     `points_trigger_key` IN ('ON_GROUP_JOIN', 'ON_GROUP_LEAVE')
                        AND     `contextid` = {$this->val('contextid')}
                        AND     `user_id` = {$this->val('user_id')}
                        AND     `company_id` = {$_COMPANY->id()}
                        ORDER BY `created_at` DESC
                    SQL);

                default:
                    return [];
            }
        })($this->val('points_trigger_key'));

        return array_map(function (array $trigger) {
            return PointsTrigger::Hydrate($trigger['points_trigger_history_id'], $trigger);
        }, $triggers);
    }

    public function getTransactions(): array
    {
        return self::DBGet("SELECT * FROM `points_transactions` WHERE `points_trigger_history_id` = {$this->id()}");
    }

    public function getHumanReadableString(): string
    {
        global $_COMPANY;

        switch ($this->val('points_trigger_key')) {
            case 'EVENT_RSVP_YES':
                $event = Event::GetEvent($this->val('contextid'));

                return sprintf(
                    gettext("RSVP'ed Yes to event (title - %s, start date - %s)"),
                    $event->val('eventtitle'),
                    $event->getEventDate('Y-m-d H:i A', ' T')
                );

            case 'EVENT_RSVP_NOT_YES':
                $event = Event::GetEvent($this->val('contextid'));

                return sprintf(
                    gettext("RSVP'ed Maybe/No to event (title - %s, start date - %s)"),
                    $event->val('eventtitle'),
                    $event->getEventDate('Y-m-d H:i A', ' T')
                );

            case 'EVENT_CHECK_IN':
                $event = Event::GetEvent($this->val('contextid'));

                return sprintf(
                    gettext('Checked-in to event (title - %s, start date - %s)'),
                    $event->val('eventtitle'),
                    $event->getEventDate('Y-m-d H:i A', ' T')
                );

            case 'EVENT_CHECK_OUT':
                $event = Event::GetEvent($this->val('contextid'));

                return sprintf(
                    gettext('Checked-out of event (title - %s, start date - %s)'),
                    $event->val('eventtitle'),
                    $event->getEventDate('Y-m-d H:i A', ' T')
                );

            case 'VOLUNTEERING_SIGN_UP':
                $event_volunteer = Event::GetEventVolunteer($this->val('contextid'));

                if (!$event_volunteer) {
                    return gettext('Added as a volunteer to an event');
                }

                $event = Event::GetEvent($event_volunteer[0]['eventid']);

                return sprintf(
                    gettext('Added as a volunteer to event (title - %s, start date - %s)'),
                    $event->val('eventtitle'),
                    $event->getEventDate('Y-m-d H:i A', ' T')
                );

            case 'DELETE_EVENT_VOLUNTEER':
                return gettext('Removed as a volunteer to an event');

            case 'NEW_COMMENT':
                $comment = Comment::GetCommentDetail($this->val('contextid'));

                if (!$comment) {
                    return gettext('Added a new comment');
                }

                $topic_name = Teleskope::TOPIC_TYPES_ENGLISH[$comment['topictype']];
                if (in_array(strtolower($topic_name[0]), ['a', 'e', 'i', 'o', 'u'])) {
                    return sprintf(gettext('Added a new comment on an %s'),  $topic_name);
                }

                return sprintf(gettext('Added a new comment on a %s'),  $topic_name);

            case 'DELETE_COMMENT':
                return gettext('Deleted a comment');

            case 'COMMENT_LIKE':
                $comment = Comment::GetCommentDetail($this->val('contextid'));

                if (!$comment) {
                    return gettext('Liked a comment');
                }

                $topic_name = Teleskope::TOPIC_TYPES_ENGLISH[$comment['topictype']];
                if (in_array(strtolower($topic_name[0]), ['a', 'e', 'i', 'o', 'u'])) {
                    return sprintf(gettext('Liked a comment on an %s'),  $topic_name);
                }

                return sprintf(gettext('Liked a new comment on a %s'),  $topic_name);

            case 'COMMENT_UNLIKE':
                $comment = Comment::GetCommentDetail($this->val('contextid'));

                if (!$comment) {
                    return gettext('Liked a comment');
                }

                $topic_name = Teleskope::TOPIC_TYPES_ENGLISH[$comment['topictype']];
                if (in_array(strtolower($topic_name[0]), ['a', 'e', 'i', 'o', 'u'])) {
                    return sprintf(gettext('Unliked a comment on an %s'),  $topic_name);
                }

                return sprintf(gettext('Unliked a comment on a %s'),  $topic_name);

            case 'ON_GROUP_JOIN':
                $group = Group::GetGroup($this->val('contextid'));
                return sprintf(gettext('Joined a %s (name - %s)'), $_COMPANY->getAppCustomization()['group']['name-short'], $group->val('groupname'));

            case 'ON_GROUP_LEAVE':
                $group = Group::GetGroup($this->val('contextid'));
                return sprintf(gettext('Left a %s (name - %s)'), $_COMPANY->getAppCustomization()['group']['name-short'], $group->val('groupname'));


            case 'RECOGNITION_GIVEN':
            case 'RECOGNITION_RECEIVED':
            case 'DELETE_RECOGNITION':
            case 'ON_SURVEY_RESPONSE':
            case 'GROUP_LEAD_ACCRUED_POINTS':
            case 'MEMBER_ACCRUED_POINTS':
                // TODO - Need to evolve this for other triggers based on client feedback
                return '';
        }

        return '';
    }
}
