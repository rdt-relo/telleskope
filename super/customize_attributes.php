<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageLoginMethods);

$db	= new Hems();
$data = null;
$id = 0;
$type = 0;

if (isset($_GET['id'])){
    $id = base64_decode($_GET['id']);
    $example_json_string = '{}';
    $data = $_SUPER_ADMIN->super_get("SELECT * FROM `company_login_settings` WHERE `companyid`='{$_SESSION['companyid']}' AND `settingid`='{$id}'");
    if ($data[0]['loginmethod']=='saml2'){
		$example_json_string='{"fields":{"externalid":{"ename":"samlNameId"},"email":{"ename":"email"},"firstname":{"ename":"givenName","pattern":"/(\w+)/","replace":"$1"},"lastname":{"ename":"familyName"},"jobtitle":{"ename":"jobTitle"},"employeetype":{"ename":"employeeType"},"department":{"ename":"department"},"branchname":{"ename":"officeLocation"},"opco":{"ename":"companyName"},"externalroles":{"ename":"externalRoles"}}}';
	} elseif ($data[0]['loginmethod']=='microsoft'){
		$example_json_string = '{"fields":{"externalid":{"ename":"mail"},"email":{"ename":"mail"},"firstname":{"ename":"givenName"},"lastname":{"ename":"surname"},"jobtitle":{"ename":"jobTitle"},"department":{"ename":"department"},"branchname":{"ename":"officeLocation"},"employeetype":{"ename":"employeetype"},"opco":{"ename":"companyName"},"externalroles":{"ename":"externalRoles"},"extended":{"CAI":{"ename":"onPremisesSamAccountName"}}},"picture":{"sync_if_null":true,"sync_always":false}}';
	}
} else {
    $_SESSION['error'] = time();
    $_SESSION['error_msg'] = 'Bad request';
    header("location:login_method_list");
}

if (isset($_POST['submit'])){

    $customization = trim($_POST['customization']);
    if (!empty($customization)) {

        $customization = json_encode(Arr::Json2Array($customization, true), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); // Add backslashes for JSON coming from browser text editor

        if (!empty($customization) && $customization != 'null') {
            //$_SUPER_ADMIN->super_update("UPDATE `company_login_settings` SET `customization`='{$customization}',`modifiedon`=NOW() WHERE `companyid`='{$_SESSION['companyid']}' AND `settingid`='{$id}' ");
            $_SUPER_ADMIN->super_update_ps("UPDATE `company_login_settings` SET `customization`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `settingid`=? ", 'xii', $customization, $_SESSION['companyid'], $id);
            $_SESSION['updated'] = time();
            header("location:login_method_list");
            exit();
        }
    }
    $_SESSION['error'] = time();
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/customize_attributes.html');
include(__DIR__ . '/views/footer.html');

?>
