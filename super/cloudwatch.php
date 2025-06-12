<?php

require_once __DIR__ . '/head.php';
require_once __DIR__ . '/../include/libs/vendor/autoload.php';

use Aws\CloudWatchLogs\CloudWatchLogsClient;

const RELATIVE_TIMES = [
    '2-minute' => '- 2 minute',
    '5-minute' => '- 5 minute',
    '10-minute' => '- 10 minute',
    '15-minute' => '- 15 minute',
    '30-minute' => '- 30 minute',
    '1-hour' => '- 1 hour',
    '2-hour' => '- 2 hour',
    '3-hour' => '- 3 hour',
    '4-hour' => '- 4 hour',
    '6-hour' => '- 6 hour',
    '8-hour' => '- 8 hour',
    '12-hour' => '- 12 hour',
    '16-hour' => '- 16 hour',
    '1-day' => '- 1 day',
    '2-day' => '- 2 day',
    '3-day' => '- 3 day',
    '4-day' => '- 4 day',
    '5-day' => '- 5 day',
    '1-week' => '- 1 week',
    '2-week' => '- 2 week',
    '3-week' => '- 3 week',
    '1-month' => '- 1 month',
];

if (!ENABLE_CLOUDWATCH_API) {
    header('HTTP/1.1 404 Not Found');
    exit();
}

Auth::CheckPermission(Permission::ViewCloudwatchLogs);

if (empty($_GET['company-id'])) {
    include __DIR__ . '/views/cloudwatch.html.php';
    exit();
}

function getCloudwatchLogs(
    string $query,
    DateTime $start_time,
    DateTime $end_time
): array
{
    $cloudwatch = CloudWatchLogsClient::factory([
        'version' => 'latest',
        'region' => S3_REGION,
    ]);

    $response = $cloudwatch->startQuery([
        'queryString' => $query,
        'logGroupName' => '/var/log/httpd/error_log',
        'startTime' => (int) ($start_time)->format('U'),
        'endTime' => (int) ($end_time)->format('U'),
        'limit' => 100,
    ]);

    $query_id = $response['queryId'];

    do {
        sleep(3);
        $response = $cloudwatch->getQueryResults(['queryId' => $query_id]);

        if (!in_array($response['status'], ['Scheduled', 'Running'])) {
            break;
        }
    } while (true);

    return array_map(function (array $result) {
        $log = [];
        foreach ($result as $result_field) {
            $log[$result_field['field']] = $result_field['value'];
        }
        
        $json = json_decode($log['@message'], true);
        $sev_color = [ 'FATAL'=>'red', 'INFO' => 'green', 'WARNING' => 'darkorange', 'SECURITY' => 'magenta', 'AUDIT' => 'black', 'ALARM' => 'pink', 'DEBUG' => 'blue', '-' => 'brown'];

        if(!empty($json['MESG'])){
            unset($json['MESG']['s_s']);
            $severity = $json['MESG']['severity'] ?? '-';
            unset($json['MESG']['severity']);
            $module = $json['MESG']['module'] ?? '-';
            unset($json['MESG']['module']);
            $method = $json['MESG']['method'] ?? '-';
            unset($json['MESG']['method']);
            $host = $json['MESG']['host'] ?? '-';
            unset($json['MESG']['host']);
            $uri = $json['MESG']['uri'] ?? '-';
            unset($json['MESG']['uri']);

            $log['Meta'] =
                "<span style='color: white; background: {$sev_color[$severity]}; padding: 2px; font-size: small;' > {$severity} </span> - " .
                "<span style='background: yellow; padding: 2px; font-size: small;'> {$module} </span> - " .
                "<span style='background: lightgrey; padding: 2px; font-size: small;'>{$json['HTTP']['DT']}</span> - " .
                "<span style='color: white; background: #666666; padding: 2px; font-size: small;'> {$method} {$host}{$uri} </span> " .
                "<pre style='color: {$sev_color[$severity]}; overflow: auto; word-wrap: break-word; white-space: pre-wrap;'>" .
                json_encode($json['MESG'], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) .
                '</pre>';
        }else{
            $log['Meta'] = '';
        }

        unset($log['@ptr'], $log['@message']);

        return $log;
    }, $response['results']);
}

function getQuery(array $filters, int $max_count = 100): string
{
    $flattened_filters = [];
    foreach ($filters as $property => $value) {
        if (empty($value)) {
            continue;
        }

        if ($property === 'search_keyword') {
            $flattened_filters[] = "@message like \"{$value}\"";
        } else {
            $flattened_filters[] = "MESG.{$property}=\"{$value}\"";
        }
    }
    $filters_string = implode(' AND ', $flattened_filters);

    return <<<QUERY
    fields @message, MESG.DT
    | filter ({$filters_string})
    | sort MESG.DT desc
    | limit {$max_count}
    QUERY;
}

function getFilters(): array
{
    global $_COMPANY;

    $company_id = $_GET['company-id'] ?? null;
    $user_id = $_GET['user-id'] ?? null;
    $zone_id = $_GET['zone-id'] ?? null;
    $email = $_GET['user-email'] ?? null;
    $severity = $_GET['severity'] ?? null;
    $module = $_GET['module'] ?? null;
    $search_keyword = $_GET['search-keyword'] ?? null;

    if ($email) {
        $_COMPANY = Company::GetCompany($company_id); // Initialize for temporary use.
        $user_id = User::GetUserByEmail($email)->id();
        $_COMPANY = null; // Reset it after use
    }

    return [
        'company_id' => $company_id,
        'user_id' => $user_id,
        'zone_id' => $zone_id,
        'severity' => $severity,
        'module' => $module,
        'search_keyword' => $search_keyword,
    ];
}

$max_count = intval($_GET['max-count'] ?? 100);
$query = getQuery(getFilters(), $max_count);
$timezone = new DateTimeZone($_SESSION['tz_b'] ?: 'UTC');

if (($_GET['search-by'] ?? 'interval') === 'interval') {
    $relative_time = $_GET['relative-time'] ?? '5-minute';

    $start_time = new DateTime(
        RELATIVE_TIMES[$relative_time],
        new DateTimeZone('UTC')
    );
    $end_time = new DateTime('now', new DateTimeZone('UTC'));
} else {

    $start_time = DateTime::createFromFormat(
        'Y-m-d H:i',
        $_GET['start-date'] . ' ' . $_GET['start-time'],
        $timezone
    );

    $end_time = DateTime::createFromFormat(
        'Y-m-d H:i',
        $_GET['end-date'] . ' ' . $_GET['end-time'],
        $timezone
    );
}

$logs = getCloudwatchLogs($query, $start_time, $end_time);

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/cloudwatch.html.php');
include(__DIR__ . '/views/footer.html');
