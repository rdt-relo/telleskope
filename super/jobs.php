<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ViewCompanyJobs);

$db	= new Hems();
if (!$_SESSION['manage_accounts'] || !isset($_GET['job_companyid'])) {
    echo "Method not allowed";
    exit();
}

$days = 1;
if (isset($_GET['past'])){
    $days = $_GET['past'];
}
$company_filter = '';
if ($_GET['job_companyid'] == 'global' && $_SESSION['manage_super']) {
    unset($_SESSION['companyid']);
    $pageTitle = 'Global Jobs';
    $company_filter = " AND companyid = '0'";
    $my_link = "?job_companyid=global";
} elseif ($_GET['job_companyid'] == 'session' && !empty($_SESSION['manage_companyids']) && ($_SESSION['manage_companyids'] == -1 || in_array($_SESSION['companyid'], explode(',', $_SESSION['manage_companyids'])))) {
    $pageTitle = 'Company Jobs';
    $company_filter = " AND companyid = '{$_SESSION['companyid']}'";
    $my_link = "?job_companyid=session";
}

if ($company_filter) {
    $select = "SELECT companyname,jobid,jobs.createdon,processafter, processedon, processedby, if (jobs.status < 100 or processedby = 'CANCELLED',0,UNIX_TIMESTAMP(processedon)-UNIX_TIMESTAMP(processafter)) as processing_time,jobs.status FROM jobs LEFT JOIN companies USING (companyid) WHERE jobs.createdon > now() - INTERVAL " . $days . " DAY {$company_filter} ORDER BY jobs.status,jobs.createdon DESC";
    $rows = $_SUPER_ADMIN->super_get($select);
    $status = array('0' => 'Scheduled', '1' => 'Processing', '100' => 'Processed');
} else {
    echo "Method not allowed";
    exit();
}
if ($_GET['job_companyid'] == 'session') {
    include(__DIR__ . '/views/header.html');
} else {
    include(__DIR__ . '/views/headermain.html');
}
include(__DIR__ . '/views/jobs.html');
include(__DIR__ . '/views/footer.html');
?>
