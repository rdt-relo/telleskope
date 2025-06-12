<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::MasqAdmin);

$db	= new Hems();
$companyid = $_SESSION['companyid'];
$rand_tok = $_SESSION['rand_tok'];
$userid = aes_encrypt($_GET['id'], 'JwzIHV4GchUFeo87TXyhDd5J'.$rand_tok,'vbpTFWFT95EDeQte2R6Wu17f8KgoIovh',true);

$check = $_SUPER_ADMIN->super_get("SELECT userid,accounttype FROM users WHERE userid='".$userid."' and companyid='".$companyid."' and isactive='1' and accounttype > 1");

if (count($check)){
	$accounttype = $check[0]['accounttype'];

	$check2 = $_SUPER_ADMIN->super_get("SELECT `subdomain`,`aes_suffix` FROM companies WHERE `companyid`='{$companyid}' and `isactive`='1'");

	if (count($check2)) {
		$vals = array();
		$vals['i'] = mt_rand();
		$vals['su'] = $_SESSION['superid'];
		$vals['a'] = $userid;
		$vals['c'] = $companyid;
		$vals['now'] = time();
		$vals['t'] = $_SESSION['tz_b'];
		$vals['nonce'] = base64_encode('A' .mt_rand().mt_rand(). 'Z');
		$aes_prefix = substr(TELESKOPE_USERAUTH_ADMIN_KEY,2,22);
		$aes_suffix = $check2[0]['aes_suffix'];

		$encrypted_token = aes_encrypt(json_encode($vals), $aes_prefix.$aes_suffix, 'naSHgZnovA4hN4UQlGq7GO38TJqKH6', false);
		$uri = 'https://'.$check2[0]['subdomain'].'.teleskope.io/1/admin/login_masq_admin?FT15hgNq5n='.$encrypted_token;
		Logger::Log("Super Admin - 0|{$superid}|MasqAdmin into {$companyid}|{$userid}", Logger::SEVERITY['INFO']);
		header("location:  ".$uri);
		exit();

	}
}
?>
