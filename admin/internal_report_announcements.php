<?php
require_once __DIR__.'/head.php';
include_once __DIR__.'/../include/reports/ReportAffinitiesAnnouncementsInternal.php';

    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportAnnouncementsInternal::GetDefaultReportRecForDownload();
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'Announcements Report';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportAnnouncementsInternal($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();