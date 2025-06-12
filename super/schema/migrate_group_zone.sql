#update the first three sqls
set @newzoneid=0;
set @companyid=0;
create table mzoneids2 as select groupid,zoneid,groupname from groups where companyid=@companyid
                                                                        and 0;
                                                                        #and group_category='IG'
                                                                        #and zoneid < 1002;


#-----
update groups set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update albums set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update budget_requests set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update budgets_other_funding set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update budgets_v2 set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update budgetuses set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update budgetuses_items left join budgetuses using(usesid) set budgetuses_items.zoneid=@newzoneid where budgetuses.groupid in (select groupid from mzoneids2) and budgetuses.companyid=@companyid;
update chapters set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update discussions set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update event_speakers left join events using(eventid) set event_speakers.zoneid=@newzoneid where events.groupid in (select groupid from mzoneids2) and events.companyid=@companyid;
update events set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update group_channels set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update group_communications set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update group_resources set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update group_tabs set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update newsletters set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update newsletter_attachments left join newsletters using(newsletterid) set newsletter_attachments.zoneid=@newzoneid where newsletters.groupid in (select groupid from mzoneids2) and newsletters.companyid=@companyid;
update post set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update recognitions set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update recruiting set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update stats_groups_daily_count set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update surveys_v2 set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update team_role_type set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update teams set zoneid=@newzoneid where groupid in (select groupid from mzoneids2) and companyid=@companyid;
update team_tasks left join teams using(teamid) set team_tasks.zoneid=@newzoneid where teams.groupid in (select groupid from mzoneids2) and teams.companyid=@companyid;
update groupleads set grouplead_typeid=0 where groupid in (select groupid from mzoneids2);
update chapterleads set grouplead_typeid=0 where groupid in (select groupid from mzoneids2);
update group_channel_leads set grouplead_typeid=0 where groupid in (select groupid from mzoneids2);


#todo update zoneid for topic_comments <-- remove zoneid?
#todo update zoneid for jobs
#todo update zoneid for messages

