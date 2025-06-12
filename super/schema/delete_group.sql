#Set the variables first.

# FIRST DELETE ALL CHAPTERS AND CHANNELS
# FIRST DELETE ALL CHAPTERS AND CHANNELS
# FIRST DELETE ALL CHAPTERS AND CHANNELS
# FIRST DELETE ALL CHAPTERS AND CHANNELS

set @groupid_delete = 0;
set @companyid = 0;
#end of variables
delete from groupleads where groupid=@groupid_delete;
delete from groupmembers where groupid=@groupid_delete;
delete newsletter_attachments from newsletter_attachments join newsletters n on newsletter_attachments.newsletterid = n.newsletterid where n.companyid=@companyid and n.groupid=@groupid_delete;
delete from newsletters where companyid=@companyid and groupid=@groupid_delete;
delete event_speakers from event_speakers join events e on event_speakers.eventid = e.eventid WHERE e.companyid=@companyid and e.groupid=@groupid_delete;
#delete eventjoiners from eventjoiners join events using (eventid) where events.companyid=@companyid AND  groupid=@groupid_delete;
delete from events where companyid=@companyid AND  groupid=@groupid_delete;
delete from post where companyid=@companyid AND  groupid=@groupid_delete;
delete survey_responses_v2 from survey_responses_v2 join surveys_v2 using (surveyid) where surveys_v2.companyid=@companyid AND groupid=@groupid_delete;
delete from surveys_v2 where companyid=@companyid AND  groupid=@groupid_delete;
delete from `groups` where `groups`.companyid=@companyid AND  `groups`.groupid=@groupid_delete;