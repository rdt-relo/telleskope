    /***
Usage mysql -u user_id -p password -h mysql-host -A -e "set @companyid=companyid; source delete_company.sql;" database_name
***/
SET @companyid=-1;
SELECT CONCAT('Deleting ',companyname,'(',companyid,')',' ... please delete the s3 folder ',s3_folder,' from S3') FROM companies WHERE companyid=@companyid;
/* */
START TRANSACTION;
/* */
DELETE `eventjoiners` FROM `eventjoiners` JOIN `events` USING (eventid) WHERE (events.companyid=@companyid);
DELETE `chapters` FROM `chapters` JOIN `groups` USING (groupid) WHERE (groups.companyid=@companyid);
DELETE `groupleads` FROM `groupleads` JOIN `groups` USING (groupid) WHERE (groups.companyid=@companyid);
DELETE `groupmembers` FROM `groupmembers` JOIN `groups` USING (groupid) WHERE (groups.companyid=@companyid);
DELETE `event_counters` FROM `event_counters` JOIN `events` USING (eventid) WHERE (events.companyid=@companyid);
DELETE `notifications` FROM `notifications` JOIN `users` USING (userid) WHERE (users.companyid=@companyid);

DELETE FROM `topic_likes` WHERE companyid=@companyid;
DELETE FROM `topic_comments` WHERE companyid=@companyid;
DELETE FROM `users_api_session` WHERE companyid=@companyid;
DELETE FROM `users_common_session` WHERE companyid=@companyid;
DELETE FROM `budgetuses_items` WHERE companyid=@companyid;
DELETE FROM `budget_charge_codes` WHERE companyid=@companyid;
DELETE FROM `budget_expense_types` WHERE companyid=@companyid;
DELETE FROM `budget_years` WHERE company_id=@companyid;
DELETE FROM `budgets_other_funding` WHERE companyid=@companyid;


DELETE `newsletter_attachments` FROM `newsletter_attachments` JOIN newsletters n USING (groupid) WHERE (n.companyid=@companyid);
DELETE `chapterleads` FROM `chapterleads` JOIN users u USING (userid) WHERE u.companyid=@companyid;
DELETE `group_channel_leads` FROM `group_channel_leads` JOIN users u USING (userid) WHERE u.companyid=@companyid;

DELETE FROM `appusage` where `companyid`=@companyid;
DELETE FROM `budgetuses` where `companyid`=@companyid;
DELETE FROM `budgets_v2` where `companyid`=@companyid;
DELETE FROM `budget_requests` where `companyid`=@companyid;
DELETE FROM `company_customizations` where `companyid`=@companyid;
DELETE FROM `companybranches` where `companyid`=@companyid;
DELETE FROM `contactus` where `companyid`=@companyid;

DELETE FROM `departments` where `companyid`=@companyid;
DELETE FROM `event_type` where `companyid`=@companyid;
DELETE FROM `grouplead_type` where `companyid`=@companyid;
DELETE FROM `hot_link` where `companyid`=@companyid;
DELETE FROM `leadsinvites` where `companyid`=@companyid;
DELETE FROM `memberinvites` where `companyid`=@companyid;
DELETE FROM `messages` where `companyid`=@companyid;
DELETE FROM `post` where `companyid`=@companyid;

DELETE FROM `group_resources` where `companyid`=@companyid;
DELETE FROM `newsletters` where `companyid`=@companyid;
DELETE FROM `integration_records` where `companyid`=@companyid;
DELETE FROM `recruiting` where `companyid`=@companyid;
DELETE FROM `referral` where `companyid`=@companyid;
DELETE FROM `regions` where `companyid`=@companyid;

DELETE FROM `company_reports` where `companyid`=@companyid;
DELETE FROM `company_email_settings` where `companyid`=@companyid;
DELETE FROM `company_login_settings` where `companyid`=@companyid;
DELETE FROM `company_contacts` where `companyid`=@companyid;
DELETE FROM `company_security_settings` where `companyid`=@companyid;

DELETE FROM `jobs` where `companyid`=@companyid;
DELETE FROM `company_admins` where `companyid`=@companyid;
DELETE `album_media` FROM `album_media` JOIN  albums USING (albumid) WHERE `companyid`=@companyid;
DELETE FROM `albums` where `companyid`=@companyid;

DELETE FROM `company_analytics` where `companyid`=@companyid;
DELETE FROM `company_footer_links` where `companyid`=@companyid;
DELETE FROM `discussions` where `companyid`=@companyid;
DELETE FROM `eai_accounts` where `companyid`=@companyid;
DELETE FROM `event_custom_fields` where `companyid`=@companyid;
DELETE FROM `event_speaker_fields` where `companyid`=@companyid;
DELETE FROM `event_speakers` where `companyid`=@companyid;
DELETE FROM `event_volunteer_type` where `companyid`=@companyid;
DELETE `event_volunteers` FROM `event_volunteers` JOIN `events` USING (eventid) where `companyid`=@companyid;

DELETE FROM `group_channels` where `companyid`=@companyid;
DELETE FROM `group_communications` where `companyid`=@companyid;
DELETE `group_linked_groups` FROM `group_linked_groups` JOIN `groups` USING (groupid) where `companyid`=@companyid;
DELETE FROM `group_tags` where `companyid`=@companyid;
DELETE FROM `group_user_logs` where `companyid`=@companyid;
DELETE FROM `handle_hashtags` where `companyid`=@companyid;

DELETE FROM `integrations` where `companyid`=@companyid;
DELETE FROM `member_join_requests` where `companyid`=@companyid;
DELETE FROM `recognition_custom_fields` where `companyid`=@companyid;
DELETE FROM `recognitions` where `companyid`=@companyid;
DELETE FROM `stats_company_daily_count` where `companyid`=@companyid;
DELETE FROM `stats_groups_daily_count` where `companyid`=@companyid;
DELETE FROM `stats_zones_daily_count` where `companyid`=@companyid;

DELETE survey_responses_v2 FROM `survey_responses_v2` JOIN surveys_v2 USING (surveyid) where `surveys_v2`.`companyid`=@companyid;
DELETE FROM `surveys_v2` where `companyid`=@companyid;
DELETE `team_members` FROM `team_members` JOIN `teams` USING (teamid) where teams.`companyid`=@companyid;
DELETE FROM `team_requests` where `companyid`=@companyid;
DELETE FROM `team_role_type` where `companyid`=@companyid;
DELETE FROM `team_tasks` where `companyid`=@companyid;
DELETE FROM `teams` where `companyid`=@companyid;
DELETE FROM `templates` where `companyid`=@companyid;

DELETE FROM `topic_approvals` where `companyid`=@companyid;
DELETE FROM `topic_approvals__configuration` where `companyid`=@companyid;
DELETE FROM `topic_approvals__logs` where `companyid`=@companyid;
DELETE FROM `topic_usage_logs` where `companyid`=@companyid;

DELETE FROM `user_catalogs` where `companyid`=@companyid;
DELETE FROM `user_connect` where `companyid`=@companyid;
DELETE FROM `user_inbox` where `companyid`=@companyid;
DELETE FROM `zone_disclaimers` where `companyid`=@companyid;

DELETE FROM company_email_domains where `companyid`=@companyid;
DELETE FROM `event_reminder_history` where `companyid`=@companyid;
DELETE FROM `events` where `companyid`=@companyid;
DELETE FROM `groups` where `companyid`=@companyid;
DELETE FROM `users` where `companyid`=@companyid;
DELETE FROM company_zones where `companyid`=@companyid;
DELETE FROM `companies` where `companyid`=@companyid;
/* */
COMMIT;
/* */

/* Find all email domains that were used by the company and set them in the email_domain_id variable */
USE dblog;
SET @email_domain_id=-1;
START TRANSACTION;
DELETE email_log_rcpts FROM email_log_rcpts JOIN email_logs USING (email_log_id) WHERE email_logs.email_domain_id=@email_domain_id;
DELETE email_log_rcpt_url_clicks FROM email_log_rcpt_url_clicks JOIN email_log_urls USING(email_url_id) JOIN email_logs USING (email_log_id) WHERE email_logs.email_domain_id=@email_domain_id;
DELETE email_log_urls FROM email_log_urls JOIN email_logs USING (email_log_id) WHERE email_logs.email_domain_id=@email_domain_id;
DELETE email_logs FROM email_logs WHERE email_logs.email_domain_id=@email_domain_id;
DELETE email_domains FROM email_domains WHERE email_domains.email_domain_id=@email_domain_id;
COMMIT;

