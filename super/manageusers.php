<?php
require_once __DIR__.'/head.php';

$db	= new Hems();

Auth::CheckPermission(Permission::ViewCompanyUsers);

$companyid = $_SESSION['companyid'];
$rand_tok = generateRandomToken(8);
$_SESSION['rand_tok'] = $rand_tok;
//Featch all employee

$curr_company = Company::GetCompany($companyid);
$admins = $curr_company->getAdminUsers();

usort($admins,function($a,$b) {
    return strcmp($a['zoneid'], $b['zoneid']);
});

$processed_userids = array();
$rows = array();
foreach ($admins as $row) {
    if (in_array($row['userid'], $processed_userids)) {
        continue;
    }
    $processed_userids[] = $row['userid'];

    $row['admin_label'] = empty($row['zoneid']) ? 'ADMIN' : 'ZONE ADMIN';
    $end_app_userid = aes_encrypt($row['userid'], 'Dhi27nNOaS1PrBmWiQmUf3Kp'.$rand_tok,'cUnYGfpSjqFwOo18700sjCr7aylnz4dJ',false);
    $row['enc_affinity_userid'] = $end_app_userid;
    $row['enc_officeraven_userid'] = $end_app_userid;
    $row['enc_talentpeak_userid'] = $end_app_userid;
    $row['enc_peoplehero_userid'] = $end_app_userid;
    $row['enc_admin_userid'] = aes_encrypt($row['userid'], 'JwzIHV4GchUFeo87TXyhDd5J'.$rand_tok,'vbpTFWFT95EDeQte2R6Wu17f8KgoIovh',false);
    $rows[] = $row;
}

usort($rows,function($a,$b) {
    return strcmp($b['userid'], $a['userid']);
});
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manageusers.html');
include(__DIR__ . '/views/footer.html');
?>
