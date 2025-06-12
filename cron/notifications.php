<?php 
require_once __DIR__.'/../include/jobs/Job.php';

$retVal="START";

$_COMPANY = null;
/* @var Company $_COMPANY */
/* The $_COMPANY variable will be initialized in fetchAndProcessAJob */
$max_jobs = 500;
$job_count = 0;
$start = time();
$end = time()+300; // 300 seconds clock time is max for each execution
// Run the loop until there are no further jobs, clock time or max jobs timer has expired.
while ($job_count < $max_jobs && (time()<$end)) {
    time_nanosleep(0, random_int(10000,99999)*10000); // Introduce a random delay to avoid race conditions
	$retVal = fetchAndProcessAJob();

    if (str_starts_with($retVal, '000NONE000')) {
        // There are no jobs, quit
        break;
    } else {
        $job_count++; // Job was executed, so increment the counter.

        if (str_starts_with($retVal, '000-ERROR')) {
            // Job errored out
            break;
        }
    }
}

$_LOGGER_META_JOB['start_time'] = date('Y-m-d H:i:s', $start);
$_LOGGER_META_JOB['job_count'] = $job_count;

