set @groupid_to_be_duplicated=NNN;
set @companyid=NNN;
####
# Note the old group should contain a string that will be replaced in new group name, e.g. FY22 gets replaced as FY23
####
drop table tt;
create temporary table tt as select * from `groups` where companyid=@companyid and groupid=@groupid_to_be_duplicated;
update tt set groupid=null,groupname=replace(groupname,'FY22','FY23'),groupname_short=replace(groupname_short,'FY22','FY23');
update `groups` set isactive=100,groupicon='',coverphoto='',sliderphoto='', permatag=concat(permatag,'22') where companyid=2000 and groupid=@groupid_to_be_duplicated;
insert into `groups` select * from tt;
drop table tt;

####
set @groupid_new = NNN_new
####
create temporary table yy as select * from groupleads where groupid=@groupid_to_be_duplicated;
update yy set groupid=@groupid_new,leadid=default;
insert into groupleads select * from yy;
drop table yy;

drop table xx;
create temporary table xx as select * from team_role_type where isactive=1 and groupid=@groupid_to_be_duplicated;
update xx set roleid=null,groupid=@groupid_new;
insert into team_role_type select * from xx;