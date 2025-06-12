<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageDomains);

$db	= new Hems();
$pageTitle = "Add Domain";
$rows = null;

if (isset($_GET['param'])) {
   $pageTitle = "Edit Domain";
   $domain_id = base64_decode($_GET['param']);

   $rows=$_SUPER_ADMIN->super_get("SELECT * FROM `company_email_domains`  WHERE  `companyid`='".$_SESSION['companyid']."' AND `domain_id`= ".$domain_id);
}

$successCode = 0;
$errorMessage = '';
$errorDomainList = '';
if (isset($_POST['submit'])){     // saving

    $domain = $_POST['domain'];
    $routable = (int)$_POST['routable'];

    $rows[0]['domain'] = $domain;
    $rows[0]['routable'] = $routable;

    if (isset($_POST['domain_id']) && $_POST['domain_id'] > 0) { // update
      $domain = explode(',',$domain)[0]; // Keep only a single value if multiple were provided.
      $domain_id = (int)$_POST['domain_id'];
      $chk = $_SUPER_ADMIN->super_get("SELECT count(1) AS chk FROM company_email_domains WHERE `domain_id` != {$domain_id} AND `domain`='{$domain}'")[0]['chk'];
      if ($chk) {
          $errorMessage = "Domain {$domain} is already in use";
      } else {
          $_SUPER_ADMIN->super_update("UPDATE `company_email_domains` SET `domain` = '". trim($domain) ."', `routable` = " . $routable . " WHERE `domain_id` = $domain_id  AND  `companyid` = " . $_SESSION['companyid']);
          $successCode = 1;
      }

    } else { // insert
      $errorDomains = array();
      $domains = explode(",", $domain);
      foreach ($domains as $domain) {
          $chk = $_SUPER_ADMIN->super_get("SELECT count(1) AS chk FROM company_email_domains WHERE `domain`='{$domain}'")[0]['chk'];
          if ($chk) {
              $errorDomains[] = $domain;
          } else {
              $domain = trim($domain);
              $domain = strtolower($domain);
              $id = $_SUPER_ADMIN->super_insert("INSERT INTO `company_email_domains`(`domain`, `companyid`, `routable`) VALUES ('" . $domain . "','" . $_SESSION['companyid'] . "', " . $routable . ")");
          }
      }
      if (empty($errorDomains)) {
        $successCode = 1;
      } else {
        $errorDomainList = implode(',', $errorDomains);
        $errorMessage = 'Unable to add the following domains: '. $errorDomainList;
      }
    }


}
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/edit_domain.html');
include(__DIR__ . '/views/footer.html');
?>
