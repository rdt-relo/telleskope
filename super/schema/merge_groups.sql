#Set the variables first.
#Also not this script is not compatible with groups that have channels.
set @groupid_from = 0;
set @groupid_to = 0;
set @companyid = 0;
#end of variables
update chapters set groupid=@groupid_to where groupid=@groupid_from and companyid=@companyid;
update chapterleads set groupid=@groupid_to where groupid=@groupid_from;
update groupleads set groupid=@groupid_to where groupid=@groupid_from;
create temporary table users_to as select memberid,userid,groupid,chapterid from groupmembers where groupid=@groupid_to;
create temporary table users_from as select memberid,userid,groupid,chapterid from groupmembers where groupid=@groupid_from;
create temporary table users_migrate as select users_from.memberid,users_from.userid,users_from.chapterid from users_from left join users_to using(userid) where users_to.userid is null;
create temporary table users_skip as select users_from.memberid,users_from.userid,users_from.chapterid from users_from left join users_to using(userid) where users_to.userid is not null;
create temporary table users_update as select * from users_skip where chapterid !='0';
create temporary table members_update as select groupmembers.memberid,groupmembers.chapterid as old_chapterid,users_update.chapterid as new_chapterid from groupmembers left join users_update using(userid) where users_update.userid is not null and groupmembers.groupid=@groupid_to;
update groupmembers set groupid=@groupid_to where memberid in (select users_migrate.memberid from users_migrate);
update groupmembers left join members_update using (memberid) set groupmembers.chapterid=members_update.new_chapterid;

update newsletters set groupid=@groupid_to where groupid=@groupid_from;
update events set groupid=@groupid_to where groupid=@groupid_from;
update post set groupid=@groupid_to where groupid=@groupid_from;
update surveys_v2 set groupid=@groupid_to,isactive=0 where groupid=@groupid_from;
