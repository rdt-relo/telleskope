<?php

require_once __DIR__ . '/../head.php';

$check2 = date('Ymd');
if ($check2 > '20250410') {
    echo "<p>Migration expired</p>";
    echo "<p>Exiting</p>";
    exit();
}

Auth::CheckSuperSuperAdmin();

// Migrate EVENT_RSVP -> EVENT_RSVP_YES and EVENT_RSVP_NOT_YES
function migrateEventRSVPTriggers()
{
    global $_SUPER_ADMIN;

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `points_trigger_history`
        SET     `points_trigger_key` = 'EVENT_RSVP_YES'
        WHERE   `points_trigger_key` = 'EVENT_RSVP'
        AND     `trigger_data`->>'$.rsvpStatus' = 1
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `points_trigger_history`
        SET     `points_trigger_key` = 'EVENT_RSVP_NOT_YES'
        WHERE   `points_trigger_key` = 'EVENT_RSVP'
        AND     `trigger_data`->>'$.rsvpStatus' != 1
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `points_transactions`
        JOIN    `points_trigger_history`
        ON      `points_transactions`.`points_trigger_history_id` = `points_trigger_history`.`points_trigger_history_id`
        SET     `points_transactions`.`point_transaction_type_key` = `points_trigger_history`.`points_trigger_key`
        WHERE   `points_transactions`.`point_transaction_type_key` = 'EVENT_RSVP'
        AND     `points_trigger_history`.`points_trigger_key` IN ('EVENT_RSVP_YES', 'EVENT_RSVP_NOT_YES')
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE points_program__configuration_members SET `points_trigger_key` = 'EVENT_RSVP_YES' WHERE `points_trigger_key` = 'EVENT_RSVP' AND `points_program_id` NOT IN (SELECT `points_program_id` FROM (SELECT `points_program_id` FROM `points_program__configuration_members` WHERE `points_trigger_key` = 'EVENT_RSVP_YES') t)
    SQL);
}

function addContextIdToTriggers()
{
    global $_SUPER_ADMIN;

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `points_trigger_history`
        SET     `contextid` = `trigger_data`->>'$.eventId'
        WHERE   `contextid` = 0
        AND     `points_trigger_key` IN ('EVENT_RSVP_YES', 'EVENT_RSVP_NOT_YES', 'EVENT_CHECK_IN', 'EVENT_CHECK_OUT')
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `points_trigger_history`
        SET     `contextid` = `trigger_data`->>'$.recognitionId'
        WHERE   `contextid` = 0
        AND     `points_trigger_key` IN ('RECOGNITION_GIVEN', 'RECOGNITION_RECEIVED', 'DELETE_RECOGNITION')
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `points_trigger_history`
        SET     `contextid` = `trigger_data`->>'$.volunteerId'
        WHERE   `contextid` = 0
        AND     `points_trigger_key` IN ('VOLUNTEERING_SIGN_UP', 'DELETE_EVENT_VOLUNTEER')
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `points_trigger_history`
        SET     `contextid` = `trigger_data`->>'$.groupId'
        WHERE   `contextid` = 0
        AND     `points_trigger_key` IN ('ON_GROUP_JOIN', 'ON_GROUP_LEAVE')
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `points_trigger_history`
        SET     `contextid` = `trigger_data`->>'$.surveyResponseId'
        WHERE   `contextid` = 0
        AND     `points_trigger_key` = 'ON_SURVEY_RESPONSE'
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `points_trigger_history`
        SET     `contextid` = `trigger_data`->>'$.commentId'
        WHERE   `contextid` = 0
        AND     `points_trigger_key` IN ('COMMENT_LIKE', 'COMMENT_UNLIKE', 'NEW_COMMENT', 'DELETE_COMMENT')
    SQL);
}

function addGroupIdToTriggers()
{
    global $_SUPER_ADMIN;

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `points_trigger_history`
        JOIN    `events` ON `points_trigger_history`.`contextid` = `events`.`eventid`
        SET     `points_trigger_history`.`groupid` = `events`.`groupid`,
                `points_trigger_history`.`collaborating_groupids` = `events`.`collaborating_groupids`
        WHERE   `points_trigger_history`.`groupid` = 0
        AND     `points_trigger_history`.`collaborating_groupids` = ''
        AND     `points_trigger_key` IN ('EVENT_RSVP_YES', 'EVENT_RSVP_NOT_YES', 'EVENT_CHECK_IN', 'EVENT_CHECK_OUT')
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `points_trigger_history`
        JOIN    `recognitions` ON `points_trigger_history`.`contextid` = `recognitions`.`recognitionid`
        SET     `points_trigger_history`.`groupid` = `recognitions`.`groupid`
        WHERE   `points_trigger_history`.`groupid` = 0
        AND     `points_trigger_history`.`collaborating_groupids` = ''
        AND     `points_trigger_key` IN ('RECOGNITION_GIVEN', 'RECOGNITION_RECEIVED', 'DELETE_RECOGNITION')
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `points_trigger_history`
        JOIN    `event_volunteers` ON `points_trigger_history`.`contextid` = `event_volunteers`.`volunteerid`
        JOIN    `events` ON `event_volunteers`.`eventid` = `events`.`eventid`
        SET     `points_trigger_history`.`groupid` = `events`.`groupid`,
                `points_trigger_history`.`collaborating_groupids` = `events`.`collaborating_groupids`
        WHERE   `points_trigger_history`.`groupid` = 0
        AND     `points_trigger_history`.`collaborating_groupids` = ''
        AND     `points_trigger_key` IN ('VOLUNTEERING_SIGN_UP', 'DELETE_EVENT_VOLUNTEER')
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `points_trigger_history`
        SET     `points_trigger_history`.`groupid` = `points_trigger_history`.`contextid`
        WHERE   `points_trigger_history`.`groupid` = 0
        AND     `points_trigger_history`.`collaborating_groupids` = ''
        AND     `points_trigger_key` IN ('ON_GROUP_JOIN', 'ON_GROUP_LEAVE')
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `points_trigger_history`
        JOIN    `survey_responses_v2` ON `points_trigger_history`.`contextid` = `survey_responses_v2`.`responseid`
        JOIN    `surveys_v2` ON `survey_responses_v2`.`surveyid` = `surveys_v2`.`surveyid`
        SET     `points_trigger_history`.`groupid` = `surveys_v2`.`groupid`
        WHERE   `points_trigger_history`.`groupid` = 0
        AND     `points_trigger_history`.`collaborating_groupids` = ''
        AND     `points_trigger_key` = 'ON_SURVEY_RESPONSE'
    SQL);

    addGroupIdToCommentTriggersForAllCompanies();
}

function addGroupIdToCommentTriggersForACompany()
{
    global $_SUPER_ADMIN, $_COMPANY;

    $stmt = $_SUPER_ADMIN->super_get(
        select: <<<SQL
            SELECT      `points_trigger_history`.*,
                        `topic_comments`.`topicid`,
                        `topic_comments`.`topictype`
            FROM        `points_trigger_history`
            INNER JOIN  `topic_comments`
            ON          `points_trigger_history`.`contextid` = `topic_comments`.`commentid`
            WHERE       `points_trigger_history`.`groupid` = 0
            AND         `points_trigger_history`.`collaborating_groupids` = ''
            AND         `points_trigger_key` IN ('COMMENT_LIKE', 'COMMENT_UNLIKE', 'NEW_COMMENT', 'DELETE_COMMENT')
            AND         `points_trigger_history`.`company_id` = {$_COMPANY->id()}
            AND         `topic_comments`.`companyid` = {$_COMPANY->id()}
        SQL,
        get_result_stmt: true
    );

    while ($row = mysqli_fetch_assoc($stmt)) {
        try {
            $trigger = PointsTrigger::Hydrate($row['points_trigger_history_id'], $row);

            /**
             * Side-effect of the wrong topictype added when a comment is made on an album-media
             * In topic_comments table, when a comment is added to an album-media, the topictype is ALM, it should had been ALBMED
             * Side-effect experienced in the points-module (Points::GetTriggerContext, DELETE_COMMENT)
             *
             * Read comment on Album::GetTopicType() method
             *
             * Even though we are in Album class, we are setting the topic as ALBUM_MEDIA as likes and comments work at
             * individual media level ... not at the album level.
             * Ideally we should have a seperate class for Album Media  ... a long term @todo
             */
            $topictype = $trigger->val('topictype');
            if ($topictype === 'ALM') {
                $topictype = 'ALBMED';
            }

            $topic = Teleskope::GetTopicObj($topictype, $trigger->val('topicid'));

            if (!$topic) {
                continue;
            }

            $groupid = $topic->val('groupid') ?: 0;
            $collaborating_groupids = $topic->val('collaborating_groupids') ?: '';

            $_SUPER_ADMIN->super_update(<<<SQL
                UPDATE  `points_trigger_history`
                SET     `points_trigger_history`.`groupid` = {$groupid},
                        `points_trigger_history`.`collaborating_groupids` = '{$collaborating_groupids}'
                WHERE   `points_trigger_history_id` = {$trigger->id()}
                AND     `company_id` = {$_COMPANY->id()}
            SQL);
        } catch (\Throwable $th) {
            echo "Failed for points_trigger_history_id - {$row['points_trigger_history_id']}, Exception - {$th->getMessage()} <br>";
        }
    }
}

function addGroupIdToCommentTriggersForAllCompanies()
{
    global $_COMPANY;

    $prev_company = $_COMPANY;

    $db = new Hems();

    $company_ids = $db->ro_get('SELECT `companyid` FROM `companies` WHERE `isactive` = 1 AND `status` = 1');
    $company_ids = array_column($company_ids, 'companyid');

    foreach ($company_ids as $companyid) {
        $_COMPANY = Company::GetCompany($companyid);
        addGroupIdToCommentTriggersForACompany();
    }

    $_COMPANY = $prev_company;
}

function fixUserPointsBalance()
{
    global $_SUPER_ADMIN;
    $db = new Hems();

    $results = $db->ro_get(<<<'SQL'
        select * from
        user_points inner join
        (select user_id, points_program_id, sum(amount) as amount
        from points_transactions
        group by user_id, points_program_id) t
        on user_points.points_program_id = t.points_program_id and user_points.user_id = t.user_id
        where user_points.points_balance != t.amount
    SQL);

    echo '<pre>';
    print_r($results);
    echo '</pre>';

    $_SUPER_ADMIN->super_update(<<<SQL
        update user_points
        inner join (select user_id, points_program_id, sum(amount) as amount
        from points_transactions
        group by user_id, points_program_id) t
        on user_points.points_program_id = t.points_program_id and user_points.user_id = t.user_id
        set points_balance = t.amount
        where user_points.points_balance != t.amount
    SQL);
}

migrateEventRSVPTriggers();
echo 'Migrated Event RSVP Triggers <br>';

addContextIdToTriggers();
echo 'Added contextid to old triggers <br>';

addGroupIdToTriggers();
echo 'Added groupid to old triggers <br>';

fixUserPointsBalance();
echo 'Fixed user points balance';

echo 'Migration Successful <br>';
