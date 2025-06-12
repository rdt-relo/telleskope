<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageScheduledJobs);

$db	= new Hems();


$temp_cid = (int) $_SESSION['companyid'];

if (isset($_GET['delete_jobid']) && ($delete_jobid = base64_url_decode($_GET['delete_jobid']))) {
    global $_COMPANY;
    $_COMPANY = Company::GetCompany($temp_cid);
    Job::DeleteJob($delete_jobid);
    $_COMPANY = null; // $_COMPANY was set temporarirly
    Http::Redirect('manage_scheduled_jobs');
}

$curr_company = Company::GetCompany($temp_cid);

$data = $curr_company->getScheduledJobs();
$zones = $curr_company->getZones();

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manage_scheduled_jobs.html');
include(__DIR__ . '/views/footer.html');
?>
