/***
Usage mysql -u user_id -p password -h mysql-host -A -e "set @companyid=companyid; source delete_company.sql;" database_name
***/
SELECT CONCAT('Deleting content for ',companyname,'(',companyid,')',' ... please delete images from the s3 folder ',s3_folder,' from S3') FROM companies WHERE companyid=@companyid;
/* */
START TRANSACTION;
/* */
DELETE FROM `eventjoiners` USING `eventjoiners` INNER JOIN `events`  ON (eventjoiners.eventid = events.eventid) WHERE (events.companyid=@companyid);
DELETE FROM `event_counters` USING `event_counters` INNER JOIN `events`  ON (event_counters.eventid = events.eventid) WHERE (events.companyid=@companyid);
DELETE FROM `event_volunteers` USING `event_volunteers` INNER JOIN `events`  ON (event_volunteers.eventid = events.eventid) WHERE (events.companyid=@companyid);
DELETE FROM `notifications` USING `notifications` INNER JOIN `users`  ON (notifications.userid = users.userid) WHERE (users.companyid=@companyid);
DELETE FROM `topic_likes` WHERE companyid=@companyid;
DELETE FROM `topic_comments` WHERE companyid=@companyid;
DELETE FROM `users_api_session` WHERE companyid=@companyid;
DELETE FROM `users_common_session` WHERE companyid=@companyid;
DELETE FROM `budgetuses_items` WHERE `companyid`=@companyid;
DELETE FROM `album_media` USING album_media INNER JOIN albums USING (albumid) WHERE (albums.companyid=@companyid);
DELETE FROM `albums` WHERE (companyid=@companyid);
DELETE FROM `newsletter_attachments` USING newsletter_attachments INNER JOIN newsletters n on newsletter_attachments.groupid = n.groupid WHERE (n.companyid=@companyid);
DELETE FROM `appusage` where `companyid`=@companyid;
DELETE FROM `budgetuses` where `companyid`=@companyid;
DELETE FROM `budgets_v2` where `companyid`=@companyid;
DELETE FROM `budget_requests` where `companyid`=@companyid;
DELETE FROM `event_reminder_history` where `companyid`=@companyid;
DELETE FROM `events` where `companyid`=@companyid;
DELETE FROM `leadsinvites` where `companyid`=@companyid;
DELETE FROM `memberinvites` where `companyid`=@companyid;
DELETE FROM `messages` where `companyid`=@companyid;
DELETE FROM `post` where `companyid`=@companyid;
DELETE FROM `recruiting` where `companyid`=@companyid;
DELETE FROM `referral` where `companyid`=@companyid;
# DELETE FROM `group_resources` where `companyid`=@companyid;
DELETE FROM `newsletters` where `companyid`=@companyid;
DELETE FROM `integration_records` where `companyid`=@companyid;
DELETE FROM `disclaimer_consents` where `companyid`=@companyid;
DELETE FROM `disclaimers` where `companyid`=@companyid;
DELETE FROM survey_responses_v2 USING survey_responses_v2 INNER JOIN surveys_v2 s on survey_responses_v2.surveyid = s.surveyid WHERE s.companyid=@companyid;
DELETE FROM surveys_v2 where `companyid`=@companyid;
DELETE FROM `budget_charge_codes` where `companyid`=@companyid;
DELETE FROM `budgets_other_funding` where `companyid`=@companyid;
DELETE FROM `discussions` where `companyid`=@companyid;
DELETE FROM `event_recording_link_clicks` USING event_recording_link_clicks INNER JOIN events e on event_recording_link_clicks.eventid=e.eventid WHERE e.companyid=@companyid;
DELETE FROM `event_reminder_history` where `companyid`=@companyid;
DELETE FROM `event_speakers` where `companyid`=@companyid;
DELETE FROM `handle_hashtags` WHERE companyid=@companyid;
DELETE FROM `member_join_requests` WHERE companyid=@companyid;
DELETE FROM `recognitions`  WHERE companyid=@companyid;
DELETE FROM `team_members` USING team_members JOIN teams t on t.teamid = team_members.teamid WHERE t.companyid=@companyid;
DELETE FROM `team_requests` where companyid=@companyid;
DELETE FROM `team_tasks` where companyid=@companyid;
DELETE FROM `teams` where companyid=@companyid;
DELETE FROM topic_approvals WHERE companyid=@companyid;
DELETE FROM topic_approvals__logs WHERE companyid=@companyid;
DELETE FROM topic_usage_logs WHERE companyid=@companyid;
DELETE FROM user_inbox WHERE companyid=@companyid;
DELETE FROM user_points USING user_points INNER JOIN points_programs using (points_program_id) WHERE points_programs.company_id=@companyid;
DELETE FROM users_api_session WHERE companyid=@companyid;
DELETE FROM users_common_session WHERE companyid=@companyid;
DELETE FROM zone_disclaimers WHERE companyid=@companyid;
/* */
COMMIT;
/* */

