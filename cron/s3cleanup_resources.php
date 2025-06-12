<?php
require_once __DIR__ .'/../include/Company.php';
$_COMPANY = null;

$startTime = time();
Resource::S3Cleanup();
$elapsedTime = time() - $startTime;
Logger::Log("CRON: Processed s3cleanup_resources in {$elapsedTime} seconds", Logger::SEVERITY['INFO']);

