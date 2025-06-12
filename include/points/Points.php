<?php
require_once __DIR__ .'/DBPoints.php';

class Points extends DBPoints
{
    public static function CreditPointsToGroupLead(int $userId): void
    {
        $programs = PointsProgram::AllActivePrograms();

        foreach ($programs as $program) {
            $userPoints = UserPoints::Get($userId, $program->val('points_program_id'));

            $pointsEarned = self::GetGroupLeadPointsEarned(
                $userId,
                $program,
                $userPoints['points_credited_till'] ?? null
            );

            self::CreditEarnedPointsToUser(
                $userId,
                $program->val('points_program_id'),
                $pointsEarned,
                'GROUP_LEAD_ACCRUED_POINTS',
                'today'
            );
        }
    }

    private static function GetGroupLeadPointsEarned(int $userId, ?PointsProgram $program, ?string $pointsCreditedTill): int
    {
        if (!$program) { // Making this function null safe for program.
            return 0;
        }

        $groupLeadPointsConfiguration = $program->getGroupLeadPointsConfiguration();
        $groupLeadPointsConfiguration = array_column($groupLeadPointsConfiguration, null, 'group_lead_type_id');

        $groups = CompanyAdmin::GetUserGroupsAsGroupLead($userId);

        $pointsEarned = 0;
        $today = new DateTime('today', new DateTimeZone('UTC'));
        foreach ($groups as $group) {
            $pointsCreditedTillDate = null;
            if ($pointsCreditedTill) {
                $pointsCreditedTillDate = new DateTime($pointsCreditedTill, new DateTimeZone('UTC'));
            }

            $programStartDate = new DateTime($program->val('start_date'), new DateTimeZone('UTC'));
            $groupJoiningDate = new DateTime($group['group_joining_date'], new DateTimeZone('UTC'));
            $fromDate = max($pointsCreditedTillDate, $programStartDate, $groupJoiningDate);

            $days = $today->diff($fromDate)->format('%a');
            $pointsEarned += ($days * ($groupLeadPointsConfiguration[$group['group_lead_type_id']]['daily_earnings'] ?? 0));
        }

        $totalPointsCredited = $program->getTotalPointsCredited();

        if (($totalPointsCredited + $pointsEarned) > $program->val('points_total')) {
            return 0;
        }

        return $pointsEarned;
    }

    public static function CreditPointsToMember(int $userId): void
    {
        $programs = PointsProgram::AllActivePrograms();

        foreach ($programs as $program) {
            $userPoints = UserPoints::Get($userId, $program->val('points_program_id'));

            $pointsEarned = self::GetMemberPointsEarned(
                $userId,
                $program,
                $userPoints['points_credited_till'] ?? null
            );

            self::CreditEarnedPointsToUser(
                $userId,
                $program->val('points_program_id'),
                $pointsEarned,
                'MEMBER_ACCRUED_POINTS',
                'today'
            );
        }
    }

    private static function GetMemberPointsEarned(int $userId, ?PointsProgram $program, ?string $pointsCreditedTill): int
    {
        if (!$program) { // Making this function null safe for program.
            return 0;
        }

        $memberPointsConfiguration = $program->getMemberPointsConfiguration('DAILY_EARNINGS');
        $dailyMemberEarnings = $memberPointsConfiguration[0]['points_earned'] ?? 0;

        $groups = CompanyAdmin::GetUserGroupsAsMember($userId);

        $pointsEarned = 0;
        $today = new DateTime('today', new DateTimeZone('UTC'));
        foreach ($groups as $group) {
            $pointsCreditedTillDate = null;
            if ($pointsCreditedTill) {
                $pointsCreditedTillDate = new DateTime($pointsCreditedTill, new DateTimeZone('UTC'));
            }

            $programStartDate = new DateTime($program->val('start_date'), new DateTimeZone('UTC'));
            $groupJoiningDate = new DateTime($group['group_joining_date'], new DateTimeZone('UTC'));
            $fromDate = max($pointsCreditedTillDate, $programStartDate, $groupJoiningDate);

            $days = $today->diff($fromDate)->format('%a');
            $pointsEarned += ($days * $dailyMemberEarnings);
        }

        $totalPointsCredited = $program->getTotalPointsCredited();

        if (($totalPointsCredited + $pointsEarned) > $program->val('points_total')) {
            return 0;
        }

        return $pointsEarned;
    }

    private static function _HandleTrigger(string $points_trigger_key, array $triggerData): void
    {
        global $_COMPANY;
        global $_USER;

        if (!$_COMPANY->getAppCustomization()['points']['enabled']) {
            return;
        }

        if ($points_trigger_key === 'RECOGNITION_CREATED') {
            self::HandleTrigger('RECOGNITION_GIVEN', $triggerData);
            self::HandleTrigger('RECOGNITION_RECEIVED', $triggerData);
            return;
        }

        if ($points_trigger_key === 'EVENT_RSVP') {
            if (in_array((int) $triggerData['rsvpStatus'], [Event::RSVP_TYPE['RSVP_YES'], Event::RSVP_TYPE['RSVP_INPERSON_YES'], Event::RSVP_TYPE['RSVP_ONLINE_YES']])) {
                /**
                 * Scenario: User RSVP's as 'Attend Online', then RSVP's as 'Attend In-Person' OR Vice-versa
                 * Then user should not be getting multiple points
                 * User would get RSVP points just once for an event
                 */
                if (in_array((int) $triggerData['existingJoinStatus'], [Event::RSVP_TYPE['RSVP_YES'], Event::RSVP_TYPE['RSVP_INPERSON_YES'], Event::RSVP_TYPE['RSVP_ONLINE_YES']])) {
                    return;
                }

                self::HandleTrigger('EVENT_RSVP_YES', $triggerData);
            } else {
                self::HandleTrigger('EVENT_RSVP_NOT_YES', $triggerData);
            }

            return;
        }

        [$context_userid, $contextid, $groupid, $collaborating_groupids] = self::GetTriggerContext($points_trigger_key, $triggerData);

        if (!isset($_USER) && Env::IsCronJob()) {
            $triggeredby_userid = 0;
        } else {
            $triggeredby_userid = $_USER?->id() ?? 0;
        }

        $points_trigger = PointsTrigger::Create(
            points_trigger_key: $points_trigger_key,
            userid: $context_userid,
            contextid: $contextid,
            triggeredby_userid: $triggeredby_userid,
            groupid: $groupid,
            collaborating_groupids: $collaborating_groupids
        );

        self::ProcessTrigger($points_trigger);
    }

    public static function HandleTrigger(...$args): void
    {
        try {
            self::_HandleTrigger(...$args);
        } catch (Throwable $e) {
            Logger::LogException($e);

            if (Env::IsDebugMode()) {
                throw $e;
            }
        }
    }

    private static function ProcessTrigger(PointsTrigger $points_trigger): void
    {
        if (in_array($points_trigger->val('points_trigger_key'), array(
                'EVENT_RSVP_NOT_YES',
                'COMMENT_UNLIKE',
                'DELETE_COMMENT',
                'DELETE_EVENT_VOLUNTEER',
                'DELETE_RECOGNITION',
                'ON_GROUP_LEAVE',
                'EVENT_CHECK_OUT',
            ))) {
            self::ProcessNegativeTrigger($points_trigger);
        } else {
            self::ProcessPositiveTrigger($points_trigger);
        }
    }

    private static function GetPointsEarnedByTrigger(string $points_trigger_key, ?PointsProgram $program): int
    {
        if (!$program) { // Making this function null safe for program.
            return 0;
        }

        $config = $program->getMemberPointsConfiguration($points_trigger_key);
        $pointsEarned = $config[0]['points_earned'] ?? 0;

        $totalPointsCredited = $program->getTotalPointsCredited();

        if (($totalPointsCredited + $pointsEarned) > $program->val('points_total')) {
            return 0;
        }

        return $pointsEarned;
    }

    private static function GetTriggerContext(string $points_trigger_key, array $triggerData): array
    {
        global $_USER;

        switch ($points_trigger_key) {
            case 'RECOGNITION_GIVEN':
            case 'RECOGNITION_RECEIVED':
                $recognition = Recognition::GetRecognition($triggerData['recognitionId']);

                $userid = ($points_trigger_key === 'RECOGNITION_GIVEN') ? $recognition->val('recognizedby') : $recognition->val('recognizedto');
                $contextid = $triggerData['recognitionId'];

                $groupid = $recognition->val('groupid');
                break;

            case 'DELETE_RECOGNITION':
                $contextid = $triggerData['recognitionId'];

                $recognition = Recognition::GetRecognition($triggerData['recognitionId']);
                $groupid = $recognition->val('groupid');
                break;

            case 'VOLUNTEERING_SIGN_UP':
                $event_volunteer = Event::GetEventVolunteer($triggerData['volunteerId']);
                $userid = $event_volunteer[0]['userid'];
                $contextid = $triggerData['volunteerId'];

                $event = Event::GetEvent($event_volunteer[0]['eventid']);
                $groupid = $event->val('groupid');
                $collaborating_groupids = $event->val('collaborating_groupids');
                break;

            case 'DELETE_EVENT_VOLUNTEER':
                $contextid = $triggerData['volunteerId'];
                $userid = $triggerData['userId'];

                $event = Event::GetEvent($triggerData['eventId']);
                $groupid = $event->val('groupid');
                $collaborating_groupids = $event->val('collaborating_groupids');
                break;

            case 'ON_GROUP_JOIN':
            case 'ON_GROUP_LEAVE':
                $userid = $triggerData['userId'];
                $contextid = $triggerData['groupId'];

                $groupid = $contextid;
                break;

            case 'ON_SURVEY_RESPONSE':
                $survey_response = Survey2::GetSurveyResponseById($triggerData['surveyResponseId']);
                $userid = $survey_response[0]['userid'];
                $contextid = $triggerData['surveyResponseId'];

                $survey = Survey2::GetSurvey($survey_response[0]['surveyid']);
                $groupid = $survey->val('groupid');
                break;

            case 'EVENT_RSVP_YES':
            case 'EVENT_RSVP_NOT_YES':
            case 'EVENT_CHECK_IN':
            case 'EVENT_CHECK_OUT':
                $userid = $triggerData['userId'];
                $contextid = $triggerData['eventId'];

                $event = Event::GetEvent($triggerData['eventId']);
                $groupid = $event->val('groupid');
                $collaborating_groupids = $event->val('collaborating_groupids');
                break;

            case 'COMMENT_LIKE':
            case 'COMMENT_UNLIKE':
                $contextid = $triggerData['commentId'];
                $userid = $_USER->id();

                $comment = Comment::GetCommentDetail($triggerData['commentId'], true);
                $topic = Teleskope::GetTopicObj($comment['topictype'], $comment['topicid']);
                $groupid = $topic->val('groupid');
                $collaborating_groupids = $topic->val('collaborating_groupids');
                break;

            case 'NEW_COMMENT':
                $contextid = $triggerData['commentId'];

                $comment = Comment::GetCommentDetail($triggerData['commentId'], true);
                $topic = Teleskope::GetTopicObj($comment['topictype'], $comment['topicid']);
                $groupid = $topic->val('groupid');
                $collaborating_groupids = $topic->val('collaborating_groupids');
                break;

            case 'DELETE_COMMENT':
                $contextid = $triggerData['commentId'];

                $topic = Teleskope::GetTopicObj($triggerData['topictype'], $triggerData['topicid']);
                $groupid = $topic->val('groupid');
                $collaborating_groupids = $topic->val('collaborating_groupids');
                break;
        }

        $userid ??= $_USER->id();
        $groupid ??= 0;
        $collaborating_groupids ??= '';

        return [$userid, $contextid, $groupid, $collaborating_groupids];
    }

    private static function CreditPointsToMemberViaTrigger(int $userId, PointsTrigger $points_trigger): void
    {
        $programs = PointsProgram::AllActivePrograms();

        foreach ($programs as $program) {
            $pointsEarned = self::GetPointsEarnedByTrigger(
                $points_trigger->val('points_trigger_key'),
                $program
            );

            self::CreditEarnedPointsToUser(
                $userId,
                $program->val('points_program_id'),
                $pointsEarned,
                $points_trigger->val('points_trigger_key'),
                null,
                $points_trigger
            );
        }
    }

    private static function CreditEarnedPointsToUser(
        int $userId,
        int $pointsProgramId,
        float $pointsEarned,
        string $pointTransactionTypeKey,
        string $pointsCreditedTill = null,
        PointsTrigger $points_trigger = null
    ): void {
        $userPoints = UserPoints::Get($userId, $pointsProgramId);

        $updatedPointsBalance = ($userPoints['points_balance'] ?? 0) + $pointsEarned;

        $sql = '
            INSERT INTO `user_points` (`user_id`, `points_program_id`, `points_balance`, `points_credited_till`)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY
            UPDATE `points_balance` = ?, `points_credited_till` = ?
        ';

        if (empty($pointsCreditedTill)) {
            $pointsCreditedTill = $userPoints['points_credited_till'] ?? null;
        } else {
            $pointsCreditedTill =
                (new DateTime($pointsCreditedTill, new DateTimeZone('UTC')))
                ->format('Y-m-d');
        }

        self::DBInsertPS(
            $sql,
            'iidsds',
            $userId,
            $pointsProgramId,
            $updatedPointsBalance,
            $pointsCreditedTill,
            $updatedPointsBalance,
            $pointsCreditedTill
        );

        PointsTransaction::Create([
            'user_id' => $userId,
            'points_program_id' => $pointsProgramId,
            'amount' => $pointsEarned,
            'point_transaction_type_key' => $pointTransactionTypeKey,
            'points_trigger_history_id' => $points_trigger?->id(),
        ]);
    }

    private static function ProcessPositiveTrigger(PointsTrigger $points_trigger): void
    {
        $userId = $points_trigger->val('user_id');
        self::CreditPointsToMemberViaTrigger($userId, $points_trigger);
    }

    private static function ProcessNegativeTrigger(PointsTrigger $points_trigger): void
    {
        $triggers = $points_trigger->getRelatedTriggers();

        $transactions = [];
        foreach ($triggers as $trigger) {
            $transactions = [
                ...$transactions,
                ...$trigger->getTransactions(),
            ];
        }

        $transaction_groups = Arr::GroupBy($transactions, 'points_program_id');

        $active_programs = PointsProgram::AllActivePrograms();
        $active_program_ids = array_map(function (PointsProgram $program) {
            return $program->val('points_program_id');
        }, $active_programs);

        foreach ($transaction_groups as $points_program_id => $transactions) {
            if (!in_array($points_program_id, $active_program_ids)) {
                continue;
            }

            $transaction_groups_by_user_id = Arr::GroupBy($transactions, 'user_id');

            foreach ($transaction_groups_by_user_id as $user_id => $user_transactions) {
                $total_earnings = array_sum(array_column($user_transactions, 'amount'));

                if ($total_earnings <= 0) {
                    continue;
                }

                self::CreditEarnedPointsToUser(
                    $user_id,
                    $points_program_id,
                    -1 * $total_earnings,
                    $points_trigger->val('points_trigger_key'),
                    null,
                    $points_trigger
                );
            }
        }
    }
}

// Include all related utility classes.
require_once __DIR__ .'/CompanyAdmin.php';
require_once __DIR__ .'/PointsProgram.php';
require_once __DIR__ .'/PointsTransaction.php';
require_once __DIR__ .'/PointsTrigger.php';
require_once __DIR__ .'/UserPoints.php';
