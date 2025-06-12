<?php

require_once __DIR__ . '/points_head.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 404 Not Found');
    exit();
}

if (!empty($_GET['members'])) {
    $redis_key = 'POINTS:CREDIT_DAILY_EARNINGS_JOB:MEMBERS';

    if ($_ZONE->getFromRedisCache($redis_key)) {
        $msg = gettext('Error: Please wait up to 24 hours before retrying.');
        Http::Redirect('points.php?msg='.$msg);
    }

    $job = new PointsCreditJob($_ZONE->id());
    $job->saveJob('credit_points_to_members');

    $_ZONE->putInRedisCache($redis_key, 1, 24 * 60 * 60);

    $msg = gettext("Your request is being processed. You will be notified when it's complete");
    Http::Redirect('points.php?msg='.$msg.'#membersPoints');
}
