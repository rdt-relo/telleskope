<?php

define('AJAX_CALL', 1);

require_once __DIR__ . '/points_head.php';

if (isset($_GET['changePointsProgramStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$_USER->canManageAffinitiesContent()) {
        Http::Forbidden();
    }

    $points_program_id = $_COMPANY->decodeId($_GET['changePointsProgramStatus']);
    $program = PointsProgram::GetPointsProgram($points_program_id);

    echo $program->updatePointsProgramStatus($_POST['status']);
    exit();
}

if (isset($_GET['pointsTransactionsReportModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        Http::Forbidden();
    }

    $timezone = new DateTimeZone($_SESSION['timezone'] ?? 'UTC');

    $first_day_of_month = new DateTimeImmutable('first day of this month', $timezone);
    $from = $first_day_of_month->format('Y-m-d');

    $today = new DateTimeImmutable('today', $timezone);
    $to = $today->format('Y-m-d');

    $groups = Group::GetAllGroupsByCompanyid($_COMPANY->id(), $_ZONE->id(), true);

    require __DIR__ . '/views/templates/point_transactions_report_modal.html.php';
    exit();
}

if (isset($_GET['downloadPointsTransactionsReport']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        Http::Forbidden();
    }

    $timezone = $_SESSION['timezone'] ?? 'UTC';
    $timezone_obj = new DateTimeZone($_SESSION['timezone'] ?? 'UTC');

    $first_day_of_month = new DateTimeImmutable('first day of this month', $timezone_obj);
    $from_date = $_POST['from'] ?? ($first_day_of_month->format('Y-m-d'));

    $today = new DateTimeImmutable('today', $timezone_obj);
    $to_date = $_POST['to'] ?? ($today->format('Y-m-d'));

    $from_datetime = DateTimeImmutable::createFromFormat('Y-m-d', $from_date, $timezone_obj);
    $to_datetime = DateTimeImmutable::createFromFormat('Y-m-d', $to_date, $timezone_obj);
    $to_datetime = min($to_datetime, $today, $from_datetime->add(new DateInterval('P60D')));
    $to_date = $to_datetime->format('Y-m-d');

    $reportMeta = ReportPointsTransactions::GetDefaultReportRecForDownload();
    $reportMeta['Options']['startDate'] = $from_date . ' 00:00:00';
    $reportMeta['Options']['endDate'] = $to_date . ' 23:59:59';

    $selected_group_id = null;
    if (!empty($_POST['selected_group_id'])) {
        $selected_group_id = $_COMPANY->decodeId($_POST['selected_group_id']);
    }

    $reportMeta['Filters']['selected_group_id'] = $selected_group_id;

    switch ($_POST['group_users_selector'] ?? 'ALL') {
        case 'ALL':
            $reportMeta['Filters']['include_group_leader_transactions'] = true;
            $reportMeta['Filters']['include_group_member_transactions'] = true;
            break;

        case 'GROUP_LEADERS':
            $reportMeta['Filters']['include_group_leader_transactions'] = true;
            $reportMeta['Filters']['include_group_member_transactions'] = false;
            break;

        case 'GROUP_MEMBERS':
            $reportMeta['Filters']['include_group_leader_transactions'] = false;
            $reportMeta['Filters']['include_group_member_transactions'] = true;
            break;
    }

    $record = [];
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'Points Transactions Report';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportPointsTransactions($_COMPANY->id(), $record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);

    exit();
}

if (isset($_GET['pointsBalanceReportModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        Http::Forbidden();
    }

    $timezone = new DateTimeZone($_SESSION['timezone'] ?? 'UTC');

    $first_day_of_month = new DateTimeImmutable('first day of this month', $timezone);
    $from = $first_day_of_month->format('Y-m-d');

    $today = new DateTimeImmutable('today', $timezone);
    $to = $today->format('Y-m-d');

    $groups = Group::GetAllGroupsByCompanyid($_COMPANY->id(), $_ZONE->id(), true);

    require __DIR__ . '/views/templates/points_balance_report_modal.html.php';
    exit();
}

if (isset($_GET['downloadPointsBalanceReport']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        Http::Forbidden();
    }
    
    $timezone = $_SESSION['timezone'] ?? 'UTC';
    $timezone_obj = new DateTimeZone($_SESSION['timezone'] ?? 'UTC');

    $first_day_of_month = new DateTimeImmutable('first day of this month', $timezone_obj);
    $from_date = $_POST['from'] ?? ($first_day_of_month->format('Y-m-d'));

    $today = new DateTimeImmutable('today', $timezone_obj);
    $to_date = $_POST['to'] ?? ($today->format('Y-m-d'));

    $from_datetime = DateTimeImmutable::createFromFormat('Y-m-d', $from_date, $timezone_obj);
    $to_datetime = DateTimeImmutable::createFromFormat('Y-m-d', $to_date, $timezone_obj);
    $to_datetime = min($to_datetime, $today, $from_datetime->add(new DateInterval('P60D')));
    $to_date = $to_datetime->format('Y-m-d');

    $reportMeta = ReportPointsBalance::GetDefaultReportRecForDownload();
    $reportMeta['Options']['startDate'] = $from_date . ' 00:00:00';
    $reportMeta['Options']['endDate'] = $to_date . ' 23:59:59';

    $selected_group_id = null;
    if (!empty($_POST['selected_group_id'])) {
        $selected_group_id = $_COMPANY->decodeId($_POST['selected_group_id']);
    }

    $reportMeta['Filters']['selected_group_id'] = $selected_group_id;

    switch ($_POST['group_users_selector'] ?? 'ALL') {
        case 'ALL':
            $reportMeta['Filters']['include_group_leader_transactions'] = true;
            $reportMeta['Filters']['include_group_member_transactions'] = true;
            break;

        case 'GROUP_LEADERS':
            $reportMeta['Filters']['include_group_leader_transactions'] = true;
            $reportMeta['Filters']['include_group_member_transactions'] = false;
            break;

        case 'GROUP_MEMBERS':
            $reportMeta['Filters']['include_group_leader_transactions'] = false;
            $reportMeta['Filters']['include_group_member_transactions'] = true;
            break;
    }

    $record = [];
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'Points Balance Report';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportPointsBalance($_COMPANY->id(), $record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);

    exit();
}

if (isset($_GET['viewUserPointsBreakup']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$_USER->isAdmin()) {
        Http::Forbidden();
    }

    $userid = $_COMPANY->decodeId($_GET['userid']);
    $points_program_id = $_COMPANY->decodeId($_GET['points_program_id']);

    $user = User::GetUser($userid);
    $points_program = PointsProgram::GetPointsProgram($points_program_id);

    $timezone = $_SESSION['timezone'] ?? 'UTC';
    $timezone_obj = new DateTimeZone($_SESSION['timezone'] ?? 'UTC');

    $a_year_ago = new DateTimeImmutable('365 days ago', $timezone_obj);
    $from_date = $_GET['from'] ?? ($a_year_ago->format('Y-m-d'));

    $today = new DateTimeImmutable('today', $timezone_obj);
    $to_date = $_GET['to'] ?? ($today->format('Y-m-d'));

    $from_datetime = DateTimeImmutable::createFromFormat('Y-m-d', $from_date, $timezone_obj);
    $to_datetime = DateTimeImmutable::createFromFormat('Y-m-d', $to_date, $timezone_obj);
    $to_datetime = min($to_datetime, $today, $from_datetime->add(new DateInterval('P364D')));
    $to_date = $to_datetime->format('Y-m-d');

    $from_datetime_utc = $from_datetime->setTime(0, 0, 0)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $to_datetime_utc = $to_datetime->setTime(23, 59, 59)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

    $selected_group_id = null;
    if (!empty($_GET['selected_group_id'])) {
        $selected_group_id = $_COMPANY->decodeId($_GET['selected_group_id']);
    }

    $transactions = PointsTransaction::GetUserPointsBreakdown($userid, $points_program_id, $from_datetime_utc, $to_datetime_utc, $selected_group_id);

    $groups = Group::GetAllGroupsByCompanyid($_COMPANY->id(), $_ZONE->id(), true);

    require __DIR__ . '/views/templates/view_user_points_breakup_modal.html.php';

    exit();
}
