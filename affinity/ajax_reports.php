<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__ .'/head.php'; //Common head.php includes destroying Session for timezone so defining INDEX_PAGE. Need head for logging and protecting

global $_COMPANY;
global $_USER;
global $_ZONE;
global $db;

/* @var Company $_COMPANY */
/* @var User $_USER */

###### All Ajax Calls For Affinity Reports ##########

###### AJAX Calls ######
##### Should be in if-elseif-else #####

if (isset($_GET['getAllReports']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getAllReports']))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // group id
    $enc_groupid = $_COMPANY->encodeId($groupid);
    // set channels for report permission
    $channels= Group::GetChannelList($groupid);
    // For budget reports & report permission
    $channelid = 0;
    $chapters= Group::GetChapterList($groupid);
    $budgetYears = Budget2::GetCompanyBudgetYears();

    // include main view
    include(__DIR__ . "/views/templates/report_tabs.php");
}    
elseif(isset($_GET['getUserReports']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getUserReports']))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $encGroupId = $_GET['getUserReports'];
    // Set up the dates
    $timezone = @$_SESSION['timezone'];
    $isEnabled=false;
    // $startDate = date("Y-m-d");
    // set chapters and channels
    $chapters = Group::GetChapterList($groupid);
    $channels= Group::GetChannelList($groupid);
    

    // Company Customized inpts for Reports
    $reportMeta = ReportUserMembership::GetDefaultReportRecForDownload();

    $fields = array();

    if ($reportMeta) {
        $fields = $reportMeta['Fields'];
    }
    if ($_USER->canManageGroup($groupid) && $_GET['reportType'] == 'chapter lead report'){
        $channels = false;
    }elseif($_USER->canManageGroup($groupid) && $_GET['reportType'] == 'channel lead report'){
        $chapters = false;
    }
    // set report type
    //$reportType= $_GET['reportType'] == 'members report' ? 'members' : 'groupleads';
    $cpuUsageMessage = gettext("Note: Selecting too many fields may slow down your system due to high CPU and memory usage for data analysis. So please choose the least fields for analytics for better performance.");
    $excludeAnalyticMetaFields = json_encode(ReportUserMembership::GetMetadataForAnalytics()['ExludeFields']);
    if($_GET['reportType'] == 'members report'){
        $reportType="members";
        $reportTitle = $_COMPANY->getAppCustomization()['group']['memberlabel'];
        unset($fields['enc_groupleaderid']);
        unset($fields['enc_chapterleaderid']);
        unset($fields['enc_channelleaderid']);
    }elseif ($_GET['reportType'] == 'erg Lead report') {
        $reportType="groupleads";
        $reportTitle = $_COMPANY->getAppCustomization()['group']["name"]." Leaders";
        unset($fields['chaptername']);
        unset($fields['enc_chapterid']);
        unset($fields['channelname']);
        unset($fields['enc_channelid']);
        unset($fields['role']);
        unset($fields['enc_groupmemberid']);
        unset($fields['enc_chapterleaderid']);
        unset($fields['enc_channelleaderid']);
    }elseif ($_GET['reportType'] == 'chapter lead report') {
        $reportType="chapterleads";
        $reportTitle = $_COMPANY->getAppCustomization()['chapter']["name"]." Leaders";
        unset($fields['channelname']);
        unset($fields['enc_channelid']);
        unset($fields['role']);
        unset($fields['enc_groupmemberid']);
        unset($fields['enc_groupleaderid']);
        unset($fields['enc_channelleaderid']);
    }else{
        $reportType="channelleads";
        $reportTitle = $_COMPANY->getAppCustomization()['channel']["name"]." Leaders";        
        unset($fields['chaptername']);
        unset($fields['enc_chapterid']);
        unset($fields['role']);
        unset($fields['enc_groupmemberid']);
        unset($fields['enc_groupleaderid']);
        unset($fields['enc_chapterleaderid']);
    }    
    include(__DIR__ . "/views/templates/roster_download_options.template.php"); 
}
## OK
elseif (isset($_GET['excel_roster_new'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['excel_roster_new']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (empty($_POST['reportType']) ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $includeMembers =  $_POST['reportType'] == 'members' ? true : false;
    $includeGroupleads = $_POST['reportType'] == 'groupleads' ? true : false;
    $includeChapterleads = $_POST['reportType'] == 'chapterleads' ? true : false;
    $includeChannelleads = $_POST['reportType'] == 'channelleads' ? true : false;
    $ids = [$groupid];
    $report_chapterids = array();
    $report_channelids = array();
    // set the type - chapter or channel
    if (!empty($_POST['chapters'])) {
        $decodedChapterIds = $_COMPANY->decodeIdsInArray($_POST['chapters']);
        $decodedChapterIdsCSV = implode(',', $decodedChapterIds);
        if (!$_USER->canManageContentInEveryScopeCSV($groupid, $decodedChapterIdsCSV, 0)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $report_chapterids = $decodedChapterIds;
    }
    if (!empty($_POST['channelid']) && ($chn_id = $_COMPANY->decodeId($_POST['channelid'])) !=0 ) {
        if (!$_USER->canManageContentInScopeCSV($groupid, 0, $chn_id)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $report_channelids[] = $chn_id;
    }
    $reportMeta = ReportUserMembership::GetDefaultReportRecForDownload();

    if ($_POST['filterType'] === 'date') {
        if(!empty($_POST['startDate'])){
            $reportMeta['Options']['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
        }
        if(!empty($_POST['endDate'])){
            $reportMeta['Options']['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
        }
    } elseif ($_POST['filterType'] === 'month') {
        // Converting month into a date for the start and end of the selected month.
        $selectedMonth = (int)$_POST['anniversaryMonth'];
        $reportMeta['Filters']['anniversaryMonth'] = $selectedMonth;
    }
    $reportMeta['Options']['includeMembers'] = $includeMembers;
    $reportMeta['Options']['includeGroupleads'] = $includeGroupleads && $_USER->canManageGroup($groupid);
    $reportMeta['Options']['includeChapterleads'] = $includeChapterleads && ($_USER->canManageGroup($groupid) || count($report_chapterids));
    $reportMeta['Options']['includeChannelleads'] = $includeChannelleads && ($_USER->canManageGroup($groupid) || count($report_channelids));
    $reportMeta['Options']['onlyActiveUsers'] = !empty($_POST['onlyActiveUsers']);
    $reportMeta['Options']['includeNonMembers'] = false;
    $reportMeta['Options']['seperateLinesForChaptersChannels'] = false; // Concat Chapter and Channel Names
    if (isset($reportMeta['Options']['uniqueRecordsOnly']) && $reportMeta['Options']['uniqueRecordsOnly']) {
        $reportMeta['Options']['includeMembers'] = true;
        $filePrefix = 'unique_members';
    }

    $reportMeta['Filters']['groupids'] = $ids; // List of groupids, or empty for all groups
    $reportMeta['Filters']['chapterids'] = $report_chapterids;
    $reportMeta['Filters']['channelids'] = $report_channelids;

    foreach ($reportMeta['Fields'] as $k => $v) {
        if (strpos($k,'extendedprofile.') !== false) {
            $kk = str_replace('extendedprofile.','extendedprofile_', $k);
        } else {
            $kk = $k;
        }

        // Do not allow chapter name to be disabled if chapterid is selected
        if ($report_chapterids && $k == 'chaptername') continue;

        // Do not allow channel name to be disabled if channelid is selected
        if ($report_channelids && $k == 'channelname') continue;

        if (!isset($_POST[$kk])) {
            unset($reportMeta['Fields'][$k]);
        }
    }
    // Lastly remove Admin fields
    unset($reportMeta['AdminFields']);

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'user';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportUserMembership ($_COMPANY->id(),$record);
    $reportAction = $_POST['reportAction'];
    $group = GROUP::GetGroup($groupid);
    if ( $reportAction == 'download'){
        $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
        #else echo false
        echo false;
        exit();
    } else {
        $title = gettext("Members Report Analytics");
        $analyticsData = $report->generateAnalyticData($title);
        $pagetitle = "Analytics";
        $_SESSION['analyticsPageRefreshed'] = true;
        $_SESSION['analytics_data'] = array();

        $analyticsTitle = $analyticsData['title'];
        $questionJson = json_encode($analyticsData['questions']);
        $answerJson = json_encode($analyticsData['answers']);
        if(empty($analyticsData['questions']) || empty($analyticsData['answers'])){
           echo false;
        }else{
            $totalResponses = count($analyticsData['answers']);
            include(__DIR__ . '/views/templates/analytics.template.php');
        }
    }


}
elseif (isset($_GET['downloadEventsReport'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    
    $enc_groupid = $_GET['groupid'];

     //Data Validation
     if (($groupid = $_COMPANY->decodeId($enc_groupid))<1 ||
     ($group = Group::GetGroup($groupid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    //Authorization Check
    if (!$_USER->canManageGroupSomething($groupid) ||
        !$_COMPANY->getAppCustomization()['event']['enabled']) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    // Set the groupid
    
    $timezone = @$_SESSION['timezone'];
    $isEnabled=false;

    $startDate = date("Y-m-d", strtotime('-1 month'));
   
    // // Get group
    $chapters = Group::GetChapterList($groupid);
    $channels = Group::GetChannelList($groupid);
    // Get Region
    $regionids = $_ZONE->val("regionids") ?? 0;
    $zoneRegions = $_COMPANY->getRegionsByZones([$_ZONE->id()]);
    $isEnabled = true;
    $cpuUsageMessage = gettext("Note: Selecting too many fields may slow down your system due to high CPU and memory usage for data analysis. So please choose the least fields for analytics for better performance.");
    if ($_GET['downloadEventsReport'] == 'event_list') {
        $reportMeta = ReportEvents::GetDefaultReportRecForDownload();
        $reportType = array();
        $fields = $reportMeta ? $reportMeta['Fields'] : array();
        $excludeAnalyticMetaFields = json_encode(ReportEvents::GetMetadataForAnalytics()['ExludeFields']);
        if (!$_COMPANY->getAppCustomization()['event']['volunteers']) {
            unset($fields['total_volunteering_hours']);
            unset($fields['volunteering_hours_configured']);
        }
        if(!$_COMPANY->getAppCustomization()['event']['reconciliation']['enabled']){
            unset($fields['is_event_reconciled']);
        }
        if(!$_COMPANY->getAppCustomization()['dynamic_list']['enabled']){
            unset($fields['dynamic_list']);
        }
        include(__DIR__ . "/views/templates/event_list_report.template.php");
    } elseif ($_GET['downloadEventsReport'] == 'event_rsvp') {
        $reportRsvpMeta = ReportEventRSVP::GetDefaultReportRecForDownload();
        $fields_rsvp = $reportRsvpMeta ? $reportRsvpMeta['Fields'] : array();
        $excludeAnalyticMetaFields = json_encode(ReportEventRSVP::GetMetadataForAnalytics()['ExludeFields']);
        if (!$_COMPANY->getAppCustomization()['event']['volunteers']) {
            unset($fields_rsvp['total_volunteering_hours']);
            unset($fields_rsvp['volunteering_hours_configured']);
            unset($fields_rsvp['volunteers_filled']);
        }
        if(!$_COMPANY->getAppCustomization()['dynamic_list']['enabled']){
            unset($fields['dynamic_list']);
        }
        include(__DIR__ . "/views/templates/event_rsvp_report.template.php");
    }
    elseif ($_GET['downloadEventsReport'] == 'event_volunteer' && $_COMPANY->getAppCustomization()['event']['volunteers']) {
        $reportVolunteerMeta = ReportEventVolunteers::GetDefaultReportRecForDownload();
        $volunteer_fields = $reportVolunteerMeta ? $reportVolunteerMeta['Fields'] : array();
        $excludeAnalyticMetaFields = json_encode(ReportEventVolunteers::GetMetadataForAnalytics()['ExludeFields']);
        if(!$_COMPANY->getAppCustomization()['dynamic_list']['enabled']){
            unset($fields['dynamic_list']);
        }
        include(__DIR__ . "/views/templates/event_volunteer_report.template.php");
    }
}
elseif (isset($_GET['download_event_list_report'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

   //Data Validation
   if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
   ($group = Group::GetGroup($groupid)) === NULL
  ) {
      header(HTTP_BAD_REQUEST);
      exit();
  }

  if (!$_USER->canManageGroupSomething($groupid)) {
    header(HTTP_FORBIDDEN);
    exit();
    }
    // Current group id
    $ids = [$groupid];
    // set the type - chapter or channel
    $report_chapterids = array();
    $report_channelids = array();
    if (!empty($_POST['chapters'])) {
        $decodedChapterIds = $_COMPANY->decodeIdsInArray($_POST['chapters']);
        $decodedChapterIdsCSV = implode(',', $decodedChapterIds);
        if (!$_USER->canManageContentInEveryScopeCSV($groupid, $decodedChapterIdsCSV, 0)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $report_chapterids = $decodedChapterIds;
    }
    if (!empty($_POST['channelid']) && ($chn_id = $_COMPANY->decodeId($_POST['channelid'])) !=0 ) {
        if (!$_USER->canManageContentInScopeCSV($groupid, 0, $chn_id)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $report_channelids[] = $chn_id;
    }
   
    $options = array();
    if(!empty($_POST['startDate'])){
        $options['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
    }
    if(!empty($_POST['endDate'])){
        $options['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
    }


    $reportMeta = ReportEvents::GetDefaultReportRecForDownload();
    $Fields = $reportMeta['Fields'];

    foreach ($Fields as $k => $v) {
        if (!isset($_POST[$k])) {
            unset($Fields[$k]);
        }
    }

    $customFields = array();
    if(isset($_POST['includeCustomFields'])){
        $options['includeCustomFields'] = $_POST['includeCustomFields'];

        //Append custom fields
        $allCustomFields = Event::GetEventCustomFields();
        foreach ($allCustomFields as $custom_field) {
            $Fields['custom'.$custom_field['custom_field_id']] = $custom_field['custom_field_name'];
        }
    }

    $meta = array(
        'Fields' => $Fields,
        'Options' => $options,
        'Filters' => array(
            'groupids' => $ids, // List of groupids, or empty for all groups
            'chapterids' => $report_chapterids,
            'channelids' => $report_channelids
        )
    );

    // Lastly remove Admin fields
    unset($meta['AdminFields']);

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'event';
    $record['reportmeta'] = json_encode($meta);

    $report = new ReportEvents($_COMPANY->id(),$record);
    $reportAction = $_POST['reportAction'];
    if ( $reportAction == 'download'){
        $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
        #else echo false
        echo false;
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('report downloaded.  '), gettext('Error'));
    } else {
        $title = gettext("Events Report Analytics");
        $analyticsData = $report->generateAnalyticData($title);
        $pagetitle = "Analytics";
        $_SESSION['analyticsPageRefreshed'] = true;
        $_SESSION['analytics_data'] = array();

        $analyticsTitle = $analyticsData['title'];
        $questionJson = json_encode($analyticsData['questions']);
        $answerJson = json_encode($analyticsData['answers']);
        if(empty($analyticsData['questions']) || empty($analyticsData['answers'])){
           echo false;
        }else{
            $totalResponses = count($analyticsData['answers']);
            include(__DIR__ . '/views/templates/analytics.template.php');
        }
       
    }
    exit();
}

elseif (isset($_GET['download_event_rsvp_report']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
   //Data Validation
   if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
   ($group = Group::GetGroup($groupid)) === NULL
  ) {
      header(HTTP_BAD_REQUEST);
      exit();
  }

  if (!$_USER->canManageGroupSomething($groupid)) {
    header(HTTP_FORBIDDEN);
    exit();
    }

    global $_ZONE;

    $reportMeta = ReportEventRSVP::GetDefaultReportRecForDownload();

    $ids[] = $groupid;
    // set the type - chapter or channel
    $report_chapterids = array();
    $report_channelids = array();

    if (!empty($_POST['chapters'])) {
        $decodedChapterIds = $_COMPANY->decodeIdsInArray($_POST['chapters']);
        $decodedChapterIdsCSV = implode(',', $decodedChapterIds);
        if (!$_USER->canManageContentInEveryScopeCSV($groupid, $decodedChapterIdsCSV, 0)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $report_chapterids = $decodedChapterIds;
    }
    if (!empty($_POST['channelid']) && ($chn_id = $_COMPANY->decodeId($_POST['channelid'])) !=0 ) {
        if (!$_USER->canManageContentInScopeCSV($groupid, 0, $chn_id)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $report_channelids[] = $chn_id;
    }

    $reportMeta['Filters']['groupids'] = $ids;
    $reportMeta['Filters']['chapterids'] = $report_chapterids;
    $reportMeta['Filters']['channelids'] = $report_channelids;
    $options = array();

    if(!empty($_POST['startDate'])){
        $options['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
    }
    if(!empty($_POST['endDate'])){
        $options['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
    }

    if(isset($_POST['includeCustomFields'])){
        $options['includeCustomFields'] = $_POST['includeCustomFields'];
    }
    $reportMeta['Options'] = $options;

    $customFields = array();

    if (!isset($_POST['chaptername'])) {
        unset($reportMeta['Fields']['chaptername']);
        unset($reportMeta['Fields']['enc_chapterid']);
    }

    if (!isset($_POST['channelname'])) {
        unset($reportMeta['Fields']['channelname']);
        unset($reportMeta['Fields']['enc_channelid']);
    }

    if (!isset($_POST['groupname'])) {
        unset($reportMeta['Fields']['groupname']);
        unset($reportMeta['Fields']['enc_groupid']);
    }

    foreach ($reportMeta['Fields'] as $k => $v) {
        if (strpos($k,'extendedprofile.') !== false) {
            $kk = str_replace('extendedprofile.','extendedprofile_', $k);
        } else {
            $kk = $k;
        }
        if (!isset($_POST[$kk])) {
            unset($reportMeta['Fields'][$k]);
        }
    }

    // Lastly remove Admin fields
    unset($reportMeta['AdminFields']);

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'event_rsvp';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportEventRSVP ($_COMPANY->id(),$record);
    $reportAction = $_POST['reportAction'];
    if ( $reportAction == 'download'){
        $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
        #else echo false
        echo false;
    } else {
        $title = gettext("Events RSVP Report Analytics");
        $analyticsData = $report->generateAnalyticData($title);
        $pagetitle = "Analytics";
        $_SESSION['analyticsPageRefreshed'] = true;
        $_SESSION['analytics_data'] = array();

        $analyticsTitle = $analyticsData['title'];
        $questionJson = json_encode($analyticsData['questions']);
        $answerJson = json_encode($analyticsData['answers']);
        if(empty($analyticsData['questions']) || empty($analyticsData['answers'])){
           echo false;
        }else{
            $totalResponses = count($analyticsData['answers']);
            include(__DIR__ . '/views/templates/analytics.template.php');
        }
    }
    exit();

    
}elseif (isset($_GET['download_event_organization_report'])) {

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['download_event_organization_report']))<1 ||
    ($group = Group::GetGroup($groupid)) === NULL
   ) {
       header(HTTP_BAD_REQUEST);
       exit();
   }
    if (!$_USER->canManageGroupSomething($groupid)) { 
        header(HTTP_FORBIDDEN);
        exit();
        }    

    $ids[] = $groupid;
    $reportMeta = ReportEventOrganization::GetDefaultReportRecForDownload();
    $reportMeta['Filters']['groupids'] = $ids;
    $reportMeta['Options']['topictype'] = 'ORG';
    $reportMeta['Options']['includeCustomFields'] = 1;

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'event_organization';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportEventOrganization ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}
elseif (isset($_GET['download_event_speaker_report'])) {
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['download_event_speaker_report']))<1 ||
    ($group = Group::GetGroup($groupid)) === NULL
   ) {
       header(HTTP_BAD_REQUEST);
       exit();
   }
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
        }    

    $ids[] = $groupid;
    $reportMeta = ReportEventSpeaker::GetDefaultReportRecForDownload();
    $reportMeta['Filters']['groupids'] = $ids;
    $reportMeta['Options']['topictype'] = 'EVTSPK';
    $reportMeta['Options']['includeCustomFields'] = 1;

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'event_speaker';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportEventSpeaker ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}
elseif (isset($_GET['downloadEventVolunteersReport'])) {
    
    //Data Validation
   if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
   (($group = Group::GetGroup($groupid)) === NULL) ||
   !$_COMPANY->getAppCustomization()['event']['volunteers']
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
   
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }   
   
    // Current group id
    $ids = [$groupid];
    // set the type - chapter or channel
    $report_chapterids = array();
    $report_channelids = array();
    if (!empty($_POST['chapters'])) {
        $decodedChapterIds = $_COMPANY->decodeIdsInArray($_POST['chapters']);
        $decodedChapterIdsCSV = implode(',', $decodedChapterIds);
        if (!$_USER->canManageContentInEveryScopeCSV($groupid, $decodedChapterIdsCSV, 0)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $report_chapterids = $decodedChapterIds;
    }
    if (!empty($_POST['channelid']) && ($chn_id = $_COMPANY->decodeId($_POST['channelid'])) !=0 ) {
        if (!$_USER->canManageContentInScopeCSV($groupid, 0, $chn_id)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $report_channelids[] = $chn_id;
    }

    $options = array();

    if(!empty($_POST['startDate'])){
        $options['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
    }
    if(!empty($_POST['endDate'])){
        $options['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
    }

    $reportMeta = ReportEventVolunteers::GetDefaultReportRecForDownload();
    $Fields = $reportMeta['Fields'];

    foreach ($Fields as $k => $v) {
        if (!isset($_POST[$k])) {
            unset($Fields[$k]);
        }
    }

    if(isset($_POST['includeCustomFields'])){
        $options['includeCustomFields'] = $_POST['includeCustomFields'];
    }

    $reportMeta = array(
        'Fields' => $Fields,
        'Options' => $options,
        'Filters' => array(
            'groupids' => $ids, // List of groupids, or empty for all groups
            'chapterids' => $report_chapterids,
            'channelids' => $report_channelids
        )
    );
    // Lastly remove Admin fields
    unset($reportMeta['AdminFields']);

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'event_volunteers';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportEventVolunteers ($_COMPANY->id(),$record);
    $reportAction = $_POST['reportAction'];
    if ( $reportAction == 'download'){
        $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
        #else echo false
        echo false;
    } else {
        $title = gettext("Events Volunteers Report Analytics");
        $analyticsData = $report->generateAnalyticData($title);
        $pagetitle = "Analytics";
        $_SESSION['analyticsPageRefreshed'] = true;
        $_SESSION['analytics_data'] = array();

        $analyticsTitle = $analyticsData['title'];
        $questionJson = json_encode($analyticsData['questions']);
        $answerJson = json_encode($analyticsData['answers']);
        if(empty($analyticsData['questions']) || empty($analyticsData['answers'])){
           echo false;
        }else{
            $totalResponses = count($analyticsData['answers']);
            include(__DIR__ . '/views/templates/analytics.template.php');
        }
    }
    exit();
}
elseif (isset($_GET['budgetReportsModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
   //Data Validation
   if (($groupid = $_COMPANY->decodeId($_GET['budgetReportsModal']))<1 ||
   ($group = Group::GetGroup($groupid)) === NULL
  ) {
      header(HTTP_BAD_REQUEST);
      exit();
  }
 
    if (!$_USER->canManageBudgetGroupSomething($groupid)) {
    header(HTTP_FORBIDDEN);
    exit();
    }

    $budgetYears = Budget2::GetCompanyBudgetYears();
    if (empty($budgetYears)){
        echo 100;
        exit();
    }

    // Set the report type
    $reportType = "budget";
    $reportTitle = "Budget Summary";
    if ($_GET['reportType'] == 'expense') {
        $reportType="expense";
        $reportTitle = "Expense/Spend";
    }
    // Default year
    $defaultBudgetYear = Session::GetInstance()->budget_year ?: Budget2::GetBudgetYearIdByDate($_USER->getLocalDateNow());
    // set the chapters as per group
    $chapters = Group::GetChapterList($groupid);
    // Get the years
    
    $currentBudgetYearId = Budget2::GetBudgetYearIdByDate($_USER->getLocalDateNow());
    // include the view
    include(__DIR__ . "/views/templates/budget_report_modal.template.php");

}

elseif (isset($_GET['downloadBudgetReport'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    $chapterid = 0;
    if (
        ($groupid = $_COMPANY->decodeId($_GET['downloadBudgetReport']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($year = $_COMPANY->decodeId($_POST['budget_year']))<1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check for chapters
    if (!empty($_POST['chapters'])) {
        $decodedChapterIds = $_COMPANY->decodeIdsInArray($_POST['chapters']);

        foreach ($decodedChapterIds as $chid) {
            $regionid = Group::GetChapterName($chid,$groupid)['regionids'] ?? 0;
            if (!$_USER->canManageBudgetGroupChapter($groupid, $regionid, $chid)) {
                header(HTTP_FORBIDDEN);
                exit();
            }
        }

        $report_chapterids = $decodedChapterIds;
    }

    if($_POST['reportType'] == 'budget'){
        // for budget reports
        $reportMeta = ReportBudgetYear::GetDefaultReportRecForDownload();
        $reportMeta['Filters']['groupid'] = $groupid;
        $reportMeta['Filters']['chapterids'] = $report_chapterids ?? array();
    } elseif ($_POST['reportType'] == 'expense') {
        // for expense reports
        $reportMeta = ReportBudget::GetDefaultReportRecForDownload();
        if (!$_COMPANY->getAppCustomization()['budgets']['other_funding']) {
            unset($reportMeta['Fields']['funding_source']);
        }
        $reportMeta['Filters']['groupids'] = array($groupid);
        $reportMeta['Filters']['chapterids'] = $report_chapterids ?? array();
        $reportMeta['Options']['topictype'] = 'EXP';
        $reportMeta['Options']['includeCustomFields'] = 1;
    } else {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $reportMeta['Filters']['year'] = $year;

    // Lastly remove Admin fields
    unset($reportMeta['AdminFields']);

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = ($_POST['reportType'] == 'budget') ? 'budget' : 'expense';
    $record['reportmeta'] = json_encode($reportMeta);


    if($_POST['reportType'] == 'budget'){
        $report = new ReportBudgetYear ($_COMPANY->id(),$record);
    }else{
        $report = new ReportBudget ($_COMPANY->id(),$record);
    }
    $report_file = $report->generateReport(Report::FILE_FORMAT_CSV, false);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}
elseif(isset($_GET['getAnnouncementsReport']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $enc_groupid = $_GET['getAnnouncementsReport'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($enc_groupid))<1 ||
    ($group = Group::GetGroup($groupid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

     // Authorization Check
     if (!$_USER->canManageGroupSomething($groupid)
     ) { 
         header(HTTP_FORBIDDEN);
         exit();
     }
     // // Get group
    $chapters = Group::GetChapterList($groupid);
    $channels = Group::GetChannelList($groupid);

    // Meta for Reports
    $announcement_meta_fields = ReportAnnouncement::GetDefaultReportRecForDownload();
    $fields = $announcement_meta_fields['Fields'];
    if(!$_COMPANY->getAppCustomization()['dynamic_list']['enabled']){
        unset($fields['dynamic_list']);
    }
    include(__DIR__ . "/views/templates/announcement_report.template.php"); 
}
elseif (isset($_GET['downloadAnouncementsReport']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
    ($group = Group::GetGroup($groupid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$_USER->canManageGroupSomething($groupid)) {
    header(HTTP_FORBIDDEN);
    exit();
    }
    // Current group id
    $ids = [$groupid];
    $reportMeta = ReportAnnouncement::GetDefaultReportRecForDownload();

    // set the type - chapter or channel
    $report_chapterids = array();
    $report_channelids = array();
    if (!empty($_POST['chapters'])) {
        $decodedChapterIds = $_COMPANY->decodeIdsInArray($_POST['chapters']);
        $decodedChapterIdsCSV = implode(',', $decodedChapterIds);
        if (!$_USER->canManageContentInEveryScopeCSV($groupid, $decodedChapterIdsCSV, 0)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $report_chapterids = $decodedChapterIds;
    }
    if (!empty($_POST['channelid']) && ($chn_id = $_COMPANY->decodeId($_POST['channelid'])) !=0 ) {
        if (!$_USER->canManageContentInScopeCSV($groupid, 0, $chn_id)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $report_channelids[] = $chn_id;
    }

    $reportMeta['Filters']['groupids'] = $ids;
    $reportMeta['Filters']['chapterids'] = $report_chapterids;
    $reportMeta['Filters']['channelids'] = $report_channelids;
    $options = array();

    if(!empty($_POST['startDate'])){
        $options['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
    }
    if(!empty($_POST['endDate'])){
        $options['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
    }

    $reportMeta['Options'] = $options;

    if (!isset($_POST['chaptername'])) {
        unset($reportMeta['Fields']['chaptername']);
        unset($reportMeta['Fields']['enc_chapterid']);
    }

    if (!isset($_POST['channelname'])) {
        unset($reportMeta['Fields']['channelname']);
        unset($reportMeta['Fields']['enc_channelid']);
    }

    if (!isset($_POST['groupname'])) {
        unset($reportMeta['Fields']['groupname']);
        unset($reportMeta['Fields']['enc_groupid']);
    }

    foreach ($reportMeta['Fields'] as $k => $v) {
        if (strpos($k,'extendedprofile.') !== false) {
            $kk = str_replace('extendedprofile.','extendedprofile_', $k);
        } else {
            $kk = $k;
        }
        if (!isset($_POST[$kk])) {
            unset($reportMeta['Fields'][$k]);
        }
    }
    // Lastly remove Admin fields
    unset($reportMeta['AdminFields']);

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'announcement';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportAnnouncement($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}
elseif (isset($_GET['downloadNewslettersReport']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
    ($group = Group::GetGroup($groupid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$_USER->canManageGroupSomething($groupid)) {
    header(HTTP_FORBIDDEN);
    exit();
    }
    // Current group id
    $ids = [$groupid];
    $reportMeta = ReportNewsletter::GetDefaultReportRecForDownload();

    // set the type - chapter or channel
    $report_chapterids = array();
    $report_channelids = array();
    if (!empty($_POST['chapters'])) {
        $decodedChapterIds = $_COMPANY->decodeIdsInArray($_POST['chapters']);
        $decodedChapterIdsCSV = implode(',', $decodedChapterIds);
        if (!$_USER->canManageContentInEveryScopeCSV($groupid, $decodedChapterIdsCSV, 0)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $report_chapterids = $decodedChapterIds;
    }
    if (!empty($_POST['channelid']) && ($chn_id = $_COMPANY->decodeId($_POST['channelid'])) !=0 ) {
        if (!$_USER->canManageContentInScopeCSV($groupid, 0, $chn_id)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $report_channelids[] = $chn_id;
    }

    $reportMeta['Filters']['groupids'] = $ids;
    $reportMeta['Filters']['chapterids'] = $report_chapterids;
    $reportMeta['Filters']['channelids'] = $report_channelids;
    $options = array();

    if(!empty($_POST['startDate'])){
        $options['start_date'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
    }
    if(!empty($_POST['endDate'])){
        $options['end_date'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
    }

    $reportMeta['Options'] = $options;

    if (!isset($_POST['chaptername'])) {
        unset($reportMeta['Fields']['chaptername']);
        unset($reportMeta['Fields']['enc_chapterid']);
    }

    if (!isset($_POST['channelname'])) {
        unset($reportMeta['Fields']['channelname']);
        unset($reportMeta['Fields']['enc_channelid']);
    }

    if (!isset($_POST['groupname'])) {
        unset($reportMeta['Fields']['groupname']);
        unset($reportMeta['Fields']['enc_groupid']);
    }

    foreach ($reportMeta['Fields'] as $k => $v) {
        if (strpos($k,'extendedprofile.') !== false) {
            $kk = str_replace('extendedprofile.','extendedprofile_', $k);
        } else {
            $kk = $k;
        }
        if (!isset($_POST[$kk])) {
            unset($reportMeta['Fields'][$k]);
        }
    }
    // Lastly remove Admin fields
    unset($reportMeta['AdminFields']);

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'newsletter';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportNewsletter($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}
elseif(isset($_GET['getNewslettersReport']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $enc_groupid = $_GET['getNewslettersReport'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($enc_groupid))<1 ||
    ($group = Group::GetGroup($groupid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

     // Authorization Check
     if (!$_USER->canManageGroupSomething($groupid)
     ) { 
         header(HTTP_FORBIDDEN);
         exit();
     }
     // // Get group
    $chapters = Group::GetChapterList($groupid);
    $channels = Group::GetChannelList($groupid);

    // Meta for Reports
    $newsletter_meta_fields = ReportNewsletter::GetDefaultReportRecForDownload();
    $fields = $newsletter_meta_fields['Fields'];
    if(!$_COMPANY->getAppCustomization()['dynamic_list']['enabled']){
        unset($fields['dynamic_list']);
    }
    include(__DIR__ . "/views/templates/newsletter_report.template.php"); 
}
elseif(isset($_GET['getRecognitionReports']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $enc_groupid = $_GET['getRecognitionReports'];
    if (
        ($groupid = $_COMPANY->decodeId($enc_groupid))<0 
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)
    ) { 
        header(HTTP_FORBIDDEN);
        exit();
    }
    // Company Customized inpts for Reports
    $repofieldsrtMeta = ReportRecognitions::GetDefaultReportRecForDownload();
    $fields = $repofieldsrtMeta['Fields'];
    include(__DIR__ . "/views/recognitions/manage/download_recognitioin_report.template.php"); 

}

elseif (isset($_GET['download_recognition_report']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    
    if (
        ($groupid = $_COMPANY->decodeId($_GET['download_recognition_report']))<0 
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid,0,0)
    ) { //Allow creators to delete unpublished content
        header(HTTP_FORBIDDEN);
        exit();
    }
   

    $reportMeta = ReportRecognitions::GetDefaultReportRecForDownload();
    $Fields = $reportMeta['Fields'];
    $options = array();
    $options['topictype'] = 'REC'; // it is type of Recognition
    if(!empty($_POST['startDate'])){
        $options['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
    }
    if(!empty($_POST['endDate'])){
        $options['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
    }

    foreach ($Fields as $k => $v) {
        if (!isset($_POST[$k])) {
            unset($Fields[$k]);
        }
    }

    if (isset($_POST['includeCustomFields'])){
        $options['topictype'] = 'REC';
        $options['includeCustomFields'] = 1;
    }

    if (isset($_POST['groupname'])) {
        $Fields['groupname'] = $_COMPANY->getAppCustomization()['group']["name-short"];
    } else {
        unset($Fields['groupname']);
    }

    $meta = array(
        'Fields' => $Fields,
        'Options' => $options,
        'Filters' => array(
            'groupids' => array($groupid) // List of groupids, or empty for all groups
        )
    );

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'recognition';
    $record['reportmeta'] = json_encode($meta);

    $report = new ReportRecognitions($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}
elseif (isset($_GET['getGroupJoinRequestReportOptions']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $modalTitle = sprintf(gettext('%s Join Requests Download Options'), $_COMPANY->getAppCustomization()['group']['name-short']); 
    $reportMeta = ReportGroupJoinRequests::GetDefaultReportRecForDownload();
    $fields = array();
    if ($reportMeta) {
        $fields = $reportMeta['Fields'];
        $ids[] = $groupid;
        $reportMeta['Filters']['groupids'] = $ids;
    }
    include(__DIR__ . "/views/templates/report_group_join_request_options.template.php");
    exit();
}
elseif (isset($_GET['download_group_join_requests_report'])) {
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['download_group_join_requests_report']))<1 ||
    ($group = Group::GetGroup($groupid)) === NULL
   ) {
       header(HTTP_BAD_REQUEST);
       exit();
   }
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
        }    


    $reportMeta = ReportGroupJoinRequests::GetDefaultReportRecForDownload();
    $Fields = $reportMeta['Fields'];
    $options = array();

    if(!empty($_POST['startDate'])){
        $options['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
    }
    if(!empty($_POST['endDate'])){
        $options['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
    }

    foreach ($Fields as $k => $v) {
        if (!isset($_POST[$k])) {
            unset($Fields[$k]);
        }
    }

    if (isset($_POST['groupname'])) {
        $Fields['groupname'] = $_COMPANY->getAppCustomization()['group']["name-short"];
    } else {
        unset($Fields['groupname']);
    }

    $reportMeta = array(
        'Fields' => $Fields,
        'Options' => $options,
        'Filters' => array(
            'groupid' => array($groupid), // List of groupids, or empty for all groups
            'userid' => 0
        )
    );

    // Removing Admin fields for now
    unset($reportMeta['AdminFields']);

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'group_join_requests';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportGroupJoinRequests ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}
// For talentpeak team report
elseif (isset($_GET['getTeamsReportOptions']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $modalTitle = sprintf(gettext("%s Report Download Options"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    $reportMeta = ReportTeamTeams::GetDefaultReportRecForDownload();
    // For hashtags
    if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']) {
        unset($reportMeta['Fields']['hashtags']);
        unset($reportMeta['Fields']['team_description']);
        unset($reportMeta['Fields']['circle_max_capacity']);
        unset($reportMeta['Fields']['circle_vacancy']);
    }
    // Hide message option if erg type is individual
    if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){
        unset($reportMeta['Fields']['count_messages']);
    }

    $fields = array();

    if ($reportMeta) {
        $fields = $reportMeta['Fields'];
        // Get the keys to hide based on Hidden Tab settings
        $hiddenTabs = $group->getHiddenProgramTabSetting();
        $keysToHide = [];
        foreach ($hiddenTabs as $hiddenTabValue) {
            $key = array_search($hiddenTabValue, TEAM::PROGRAM_TEAM_TAB);
            if($key !== false){
                $mappedKeys = array_keys(TEAM::FIELD_KEY_MAP, $key);
                $keysToHide = array_merge($keysToHide, $mappedKeys);
            }
        }
        $fields = array_filter($fields, function($key) use ($keysToHide){
            return !in_array($key, $keysToHide);
        }, ARRAY_FILTER_USE_KEY); 
    }

    include(__DIR__ . "/views/talentpeak/teams/teams_report_download_options.template.php");
    exit();
}

elseif (isset($_GET['getTeamMemberReportOptions']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $allRoles = Team::GetProgramTeamRoles($groupid, 1);

    $modalTitle = sprintf(gettext("%s Members Report Download Options"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    $reportMeta = ReportTeamMembers::GetDefaultReportRecForDownload();
    if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){
        unset($reportMeta['Fields']['count_messages']);
    }
    $fields = array();

    if ($reportMeta) {
        $fields = $reportMeta['Fields'];
    }
    $excludeAnalyticMetaFields = json_encode(ReportTeamMembers::GetMetadataForAnalytics()['ExludeFields']);
    include(__DIR__ . "/views/talentpeak/teams/team_members_report_download_options.template.php");
    exit();
}
elseif (isset($_GET['event_recording_link_clicks_report']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (($eventid = $_COMPANY->decodeId($_GET['event_id']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportEventRecordingLinkClicks::GetDefaultReportRecForDownload();
    $reportMeta['Filters']['eventid'] = $eventid;

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'event_recording_clicks';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportEventRecordingLinkClicks($_COMPANY->id(), $record);

    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    echo false;
    exit();
}
elseif (isset($_GET['download_direct_mail_report'])){
    if (
        ($groupid = $_COMPANY->decodeId($_GET['download_direct_mail_report'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
        }   

    $reportMeta = ReportDirectMails::GetDefaultReportRecForDownload();

    $reportMeta['Filters']['groupids'] = array($groupid); 
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'direct_mail';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportDirectMails ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}else {
    Logger::Log ("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}