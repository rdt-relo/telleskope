<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::MasqOfficeraven);

$db	= new Hems();
$companyid = $_SESSION['companyid'];
$rand_tok = $_SESSION['rand_tok'];
$userid = aes_encrypt($_GET['id'], 'Dhi27nNOaS1PrBmWiQmUf3Kp'.$rand_tok,'cUnYGfpSjqFwOo18700sjCr7aylnz4dJ',true);

$check = $_SUPER_ADMIN->super_get("SELECT `userid` FROM `users` WHERE `userid`='{$userid}' and `companyid`='{$companyid}' and `isactive`='1'");

if (count($check)){

$check2 = $_SUPER_ADMIN->super_get("SELECT `subdomain`,`aes_suffix` FROM `companies` WHERE `companyid`='{$companyid}' and `isactive`='1'");

	if (count($check2)) {

		$vals = array();
		$vals['i'] = mt_rand();
		$vals['su'] = $_SESSION['superid'];
		$vals['u'] = $userid;
		$vals['c'] = $companyid;
		$vals['now'] = time();
		$vals['t'] = $_SESSION['tz_b'];
		$vals['nonce'] = base64_encode('A' .mt_rand().mt_rand(). 'Z');
		$aes_prefix = substr(TELESKOPE_USERAUTH_OFFICERAVEN_KEY,2,22);
		$aes_suffix = $check2[0]['aes_suffix'];

		$encrypted_token = aes_encrypt(json_encode($vals), $aes_prefix.$aes_suffix, 'GSqPYm18v3eEN9gbSxaNT7Rvqx2IRxjFGooBb8Rr', false);
		$uri = 'https://'.$check2[0]['subdomain'].'.officeraven.io/1/officeraven/login_masq_officeraven?HqsM4kCF5z='.$encrypted_token;
		Logger::Log("Super Admin - 0|{$superid}|MasqOfficeRaven into {$companyid}|{$userid}", Logger::SEVERITY['INFO']);
		header("location:  ".$uri);
		exit();
	}
}
?>
