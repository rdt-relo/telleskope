<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageZones);

$db	= new Hems();

$id = 0;
$edit = null;
$companyid = $_SESSION['companyid'];
$pagetitle = 'New Zone';
$allowFullUpdate = true;
if (isset($_GET['id'])){
    $id = base64_decode($_GET['id']);
    $edit = $_SUPER_ADMIN->super_get("SELECT * FROM `company_zones` WHERE `companyid`='{$companyid}' AND `zoneid`='{$id}' ");
    $pagetitle = "Update Zone";
    $allowFullUpdate = $edit[0]['isactive'] != 1;
}

$allApps = Company::APP_LABEL;

if (isset($_POST['creteZone'])){

    $zonename = $allowFullUpdate ? raw2clean($_POST['zonename']) : $edit[0]['zonename'];
    $app_type = $allowFullUpdate ? raw2clean($_POST['app_type']) :  $edit[0]['app_type'];
    $home_zone = $allowFullUpdate ? intval($_POST['home_zone']) : $edit[0]['home_zone'];

    if ($id >0){
        $_SUPER_ADMIN->super_update("UPDATE `company_zones` SET  `zonename`='{$zonename}', `app_type`='{$app_type}', home_zone='{$home_zone}' WHERE `companyid`='{$companyid}' AND zoneid='{$id}'");

    } else {
        $group_landing_page = "announcements";
        $category_name = 'ERG';
        if ($app_type == "talentpeak"){
            $group_landing_page = "about";
            $category_name = 'Mentoring';
        } elseif ($app_type == "peoplehero"){
            $group_landing_page = "about";
            $category_name = 'Module';
        }
        $zoneid = $_SUPER_ADMIN->super_insert("INSERT INTO `company_zones`( `companyid`, `zonename`, `app_type`, `home_zone`, `email_from_label`,`group_landing_page`) VALUES ('{$companyid}','{$zonename}','{$app_type}','{$home_zone}','','{$group_landing_page}')");
        // Add a default category for the group category table
        $default_categoryid = $_SUPER_ADMIN->super_insert("INSERT into `group_categories` (companyid,zoneid,category_label,category_name,is_default_category,createdon,modifiedon,isactive) VALUES ({$companyid},{$zoneid},'','{$category_name}','1',NOW(),NOW(),'1')");
    }
    
    header("Location:manage_zones");
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/createZone.html');
include(__DIR__ . '/views/footer.html');
?>
