<?php
require_once __DIR__.'/head.php';
$pagetitle = "Manage Reports";
$error = null;
$success = null;
global $_COMPANY; /* @var Company $_COMPANY */
global $_ZONE;
global $db;
$zoneid = $_ZONE->id();

if ($_COMPANY->val('in_maintenance') < 2) {
    echo "Error: Company needs to be in <strong style='color:blue;'>maintenance mode 2 or higher</strong> for creating new reports";
    exit(0);
}
$data = $db->get("SELECT `reportid`, `companyid`, `zoneid`, `reportname`, `reportdescription`, `reporttype`, `reportmeta`, `purpose`, `createdby`, `createdon`, `modifiedon`, `isactive` FROM `company_reports` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND `isactive`=1");
$type = [
    Report::REPORT_TYPE_USER_MEMBERSHIP=>'User Membership',
    Report::REPORT_TYPE_USERS=>'Users',
    Report::REPORT_TYPE_EVENT=>'Event',
    Report::REPORT_TYPE_BUDGET=>'Budget',
    Report::REPORT_TYPE_SURVEY=>'Survey',
    Report::REPORT_TYPE_SURVEY_DATA => 'Survey Data',
    Report::REPORT_TYPE_EVENT_RSVP=>'Event RSVP',
    Report::REPORT_TYPE_ANNOUNCEMENT=>'Announcement',
    Report::REPORT_TYPE_NEWSLETTERS=>'Newsletter',
    Report::REPORT_TYPE_DIRECT_MAIL=>'Direct Mail',
    Report::REPORT_TYPE_OFFICELOCATIONS=>'Office Locations',
    Report::REPORT_TYPE_GROUP_DETAILS=>'Group Details',
    Report::REPORT_TYPE_GROUP_CHAPTER_DETAILS => 'Group Chapter Details',
    Report::REPORT_TYPE_GROUP_CHANNEL_DETAILS => 'Group Channel Details',
    Report::REPORT_TYPE_GROUPCHAPTER_LOCATION => 'Group Chapter Locations',
    Report::REPORT_TYPE_TEAM_USER=>'Talent Peak User (Deprecated)',
    Report::REPORT_TYPE_TEAM_TEAMS=>'Teams',
    Report::REPORT_TYPE_TEAM_MEMBERS=>'Team Members',
    Report::REPORT_TYPE_TEAM_REGISTRATIONS=>'Team Registrations',
    Report::REPORT_TYPE_TEAM_FEEDBACK=>'Team Feedback',
    Report::REPORT_TYPE_LOGINS=>'Login Usage Report',
    Report::REPORT_TYPE_POINTS_BALANCE=>'User Point Balance',
    Report::REPORT_TYPE_APPROVALS => 'Approvals Report',
];

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manage_reports.html');
