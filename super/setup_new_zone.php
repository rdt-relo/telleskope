<?php
require_once __DIR__.'/head.php';
$db	= new Hems();

$id = 0;
$edit = null;
$companyid = $_SESSION['companyid'];
$pagetitle = 'Create & Setup a new Zone';
$allApps = Company::APP_LABEL;
$validation_failed = false;

$affinities_erg_data = array(
    array("group_name"=>"Test Group"),
    array("group_name"=>"African American"),
    array("group_name"=>"Asian Pacific Islander"),
    array("group_name"=>"Latino"),
    array("group_name"=>"Pride"),
    array("group_name"=>"Womens"),
    array("group_name"=>"Young Professionals"),
    array("group_name"=>"Parents"),
    array("group_name"=>"Veterans"),
);

$officeraven_erg_data = array(
    array("group_name"=>"Test Group"),
    array("group_name"=>"New York"),
    array("group_name"=>"San Francisco"),
    array("group_name"=>"Boston")
);

$talentpeak_erg_data = array(
    array("group_name"=>"Test Group"),
    array("group_name"=>"Leadership development"),
    array("group_name"=>"Veteran membership"),
    array("group_name"=>"ERG membership")
);

$peoplehero_erg_data = array(
    array("group_name"=>"Test Module"),
    array("group_name"=>"I9 Verification"),
);


if (isset($_POST) && isset($_POST["createZone"])){

    $regionname = raw2clean(trim($_POST['regionname']));

    $zonename = raw2clean(trim($_POST['zonename']));
    $app_type = raw2clean($_POST['app_type']);

    $groupname = raw2clean(trim($_POST['groupname']));
    $groupshortname = raw2clean(trim($_POST['groupshortname']));
    $enablechapters = ("yes" == $_POST['enablechapters'])?true:false;
    $enablechannels = ("yes" == $_POST['enablechannels'])?true:false;

    $zone_settings = array(
        "app" => array(
            "group" => array(
                "name" => $groupname,
                "name-plural" => $groupname.'s',
                "name-short" => $groupshortname,
                "name-short-plural" => $groupshortname.'s',
            ),
            "chapter" => array(
                "enabled" => $enablechapters
            ),
            "channel" => array(
                "enabled" => $enablechannels
            ),
            "event" => array(
                "enabled" => true
            ),
            "messaging" => array(
                "enabled" => true
            ),
            "resources" => array(
                "enabled" => true,
            ),
            "newsletters" => array(
                "enabled" => true
            ),
            "surveys" => array(
                "enabled" => true
            ),
            "communications" => array(
                "enabled" => true
            ),
            "budgets" => array(
                "enabled" => true
            )
        )
    );

    $zones_json = json_encode($zone_settings);

    if (empty($regionname) || empty($zonename) || empty($groupname) || empty($groupshortname)) {
        $validation_failed = true;
    }


    $group_landing_page = "announcements";
    if ($app_type == "talentpeak"){
        $group_landing_page = "about";
    } elseif ($app_type == "affinities"){
        $zone_settings['app']['budgets']['enabled'] = true;
    }

    // 1. create region
    $regionid = $_SUPER_ADMIN->super_insert("INSERT INTO `regions`(`companyid`, `region`, `userid`, `date`, `isactive`) 
                          VALUES ('{$companyid}','{$regionname}','0',now(),'1')");

    // 2. create zone
    $zoneid = $_SUPER_ADMIN->super_insert("INSERT INTO `company_zones`(`companyid`, `zonename`, `app_type`, `home_zone`, `email_from_label`,`group_landing_page`,`customization`) VALUES ('{$companyid}','{$zonename}','{$app_type}','1','','{$group_landing_page}','{$zones_json}')");
    // 2.1 Add a default category for the group category table
    $default_categoryid = $_SUPER_ADMIN->super_insert("INSERT into `group_categories` (companyid,zoneid,category_label,category_name,is_default_category,createdon,modifiedon,isactive) VALUES ({$companyid},{$zoneid},'','ERG','1',NOW(),NOW(),'1')");

    // 3. Create default user.
    $userid = $_SUPER_ADMIN->super_insert("INSERT INTO users(companyid, firstname, lastname, email, password,
                  accounttype, zoneids, picture, jobtitle, opco, employeetype, homeoffice, timezone, confirmationcode, verificationstatus, signuptype, notification, 
                  department, createdon, modified, externalid, aad_oid, externalusername, extendedprofile, policy_accepted_on, isactive)
          VALUES ({$companyid}, 'Temporary', 'Delete After Setup', concat('setup@_',(select subdomain from companies where companyid={$companyid}),'.teleskope.io'), null,
                  3, {$zoneid},'','','','',0,'UTC','',0,0,0,
                  0,now(),now(),null,null,null,null,null,1)");
    $_SUPER_ADMIN->super_update("insert into company_admins(companyid,zoneid,userid,manage_budget,manage_approvers,createdby) values ({$companyid},0,{$userid},0,0,{$userid})");
    $_SUPER_ADMIN->super_update("insert into company_admins(companyid,zoneid,userid,manage_budget,manage_approvers,createdby) values ({$companyid},{$zoneid},{$userid},1,1,{$userid})");
    //TeleskopeMailingList::AddOrUpdateUserMailingList($userid, 1, 1, 0);

    // 4.  If 2.b is ‘Affinities’ then create the following ERG Lead Types mapping to corresponding sys_leadtype.
    switch($app_type) {
        case "affinities":
            $data_array = $affinities_erg_data;
            break;
        case "officeraven":
            $data_array = $officeraven_erg_data;
            break;
        case "talentpeak":
            $data_array = $talentpeak_erg_data;
            break;
        case "peoplehero":
            $data_array = $peoplehero_erg_data;
            break;
        default:
            $data_array = array();
    }

    /// Next create the selected groups, All the groups will be assigned to the default regionid created as output of processing 1.a
    foreach($data_array as $index => $erg) {
        if (isset($_POST["erg_".$app_type."_group_".$index]) && "yes" == $_POST["erg_".$app_type."_group_".$index]) {
            $permatag = substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(12 / strlen($x)))), 1, 12);
            $selected_groupname = $data_array[$index]["group_name"];
            $_SUPER_ADMIN->super_insert("INSERT into `groups` (companyid, regionid, addedby, groupname,groupname_short, abouttitle, aboutgroup, coverphoto, overlaycolor, overlaycolor2, groupicon, permatag, priority, addedon, from_email_label, zoneid, categoryid, modifiedon, isactive) 
               VALUES ('".$companyid."','".$regionid."',0,'".$selected_groupname."','".$selected_groupname."','".$selected_groupname."','About ".$selected_groupname." ...','','rgb(0,102,204)', 'rgb(0,76,152)','','".$permatag."','',NOW(),'','".$zoneid. "','" .$default_categoryid. "',NOW(),1)");
        }
    }

    if ("affinities" == $app_type) {

        // For Exective Sponsor
        $_SUPER_ADMIN->super_insert("INSERT INTO `grouplead_type` (companyid,`sys_leadtype`, `type`, zoneid, modifiedon, isactive) 
                VALUES ('".$companyid."',1,'Executive Sponsor','".$zoneid."',NOW(),1)");


        // For Group Lead
        $_SUPER_ADMIN->super_insert("INSERT INTO `grouplead_type` (companyid,`sys_leadtype`, `type`, zoneid, modifiedon, isactive) 
                VALUES ('".$companyid."',2,'Group Lead','".$zoneid."',NOW(),1)");
    }

    // if 2.c.iii is yes, then create the following additions lead type
    if("yes" == $enablechapters) {
        $_SUPER_ADMIN->super_insert("INSERT INTO `grouplead_type` (companyid,`sys_leadtype`, `type`, zoneid, modifiedon, isactive) 
                VALUES ('".$companyid."',4,'Chapter Lead','".$zoneid."',NOW(),1)");
    }

    // if 2.c.iv is yes, then create the following additions lead type
    if ("yes" == $enablechannels) {
        $_SUPER_ADMIN->super_insert("INSERT INTO `grouplead_type` (companyid,`sys_leadtype`, `type`, zoneid, modifiedon, isactive) 
                VALUES ('".$companyid."',4,'Channel Lead','".$zoneid."',NOW(),1)");
    }


// Create the following Charge Codes

    //Default
    $_SUPER_ADMIN->super_insert("INSERT INTO budget_charge_codes(`companyid`, `zoneid`, `charge_code`, `createdby`, `modifiedon`, `createdon`, `isactive`) 
    VALUES ({$companyid},{$zoneid},'Default',0,now(),now(),1)");

//Create the following Expense Types

    //Meals
    $_SUPER_ADMIN->super_insert("INSERT INTO budget_expense_types(`companyid`, `zoneid`, `expensetype`, `createdby`, `modifiedon`, `createon`, `isactive`) 
    VALUES ('{$companyid}','{$zoneid}','Meals','0',now(),now(),'1')");

    //Travel
    $_SUPER_ADMIN->super_insert("INSERT INTO budget_expense_types(`companyid`, `zoneid`, `expensetype`, `createdby`, `modifiedon`, `createon`, `isactive`) 
    VALUES ('{$companyid}','{$zoneid}','Travel','0',now(),now(),'1')");

    //Speaker Fees
    $_SUPER_ADMIN->super_insert("INSERT INTO budget_expense_types(`companyid`, `zoneid`, `expensetype`, `createdby`, `modifiedon`, `createon`, `isactive`) 
    VALUES ('{$companyid}','{$zoneid}','Speaker Fees','0',now(),now(),'1')");

    //Misc
    $_SUPER_ADMIN->super_insert("INSERT INTO budget_expense_types(`companyid`, `zoneid`, `expensetype`, `createdby`, `modifiedon`, `createon`, `isactive`) 
    VALUES ('{$companyid}','{$zoneid}','Misc','0',now(),now(),'1')");


//Create the following Budget Year:

    //CY - YYYY
    $year = date("Y");
    $_SUPER_ADMIN->super_insert("INSERT INTO `budget_years`(`company_id`, `zone_id`, `budget_year_title`, `budget_year_start_date`, `budget_year_end_date`,`createdby`) 
        VALUES ('{$companyid}','{$zoneid}','Calendar Year {$year}','{$year}-01-01','{$year}-12-31','0')");


 // Add newsletter templates
    //Default Newsletter
    $newsletter_template = file_get_contents("../admin/newsletter_templates/NEWSLETTER");
    $newsletter_template = str_replace(['"',"'","\n","\r\n"],['\"',"\'",'',''],$newsletter_template);
    $_SUPER_ADMIN->super_insert("INSERT INTO `templates`(`companyid`, `zoneid`, `templatename`, `templatetype`, `template`, `createdby`, `createdon`, `modifiedon`, `isactive`) 
    VALUES ('{$companyid}','{$zoneid}','Default Newsletter','1','{$newsletter_template}','0',NOW(),NOW(),2)");

    // Member Join Email
    $member_join_template = file_get_contents("../admin/newsletter_templates/WELCOME_EMAIL");
    $member_join_template = str_replace(['"',"'","\n","\r\n"],['\"',"\'",'',''],$member_join_template);
    $_SUPER_ADMIN->super_insert("INSERT INTO `templates`(`companyid`, `zoneid`, `templatename`, `templatetype`, `template`, `createdby`, `createdon`, `modifiedon`, `isactive`) 
    VALUES ('{$companyid}','{$zoneid}','Member Join Email','4','{$member_join_template}','0',NOW(),NOW(),2)");

    // Member Leave Email
    $member_leave_template = file_get_contents("../admin/newsletter_templates/LEAVE_EMAIL");
    $member_leave_template = str_replace(['"',"'","\n","\r\n"],['\"',"\'",'',''],$member_leave_template);
    $_SUPER_ADMIN->super_insert("INSERT INTO `templates`(`companyid`, `zoneid`, `templatename`, `templatetype`, `template`, `createdby`, `createdon`, `modifiedon`, `isactive`) 
    VALUES ('{$companyid}','{$zoneid}','Member Leave Email','4','{$member_leave_template}','0',NOW(),NOW(),2)");

    Company::GetCompany($companyid,true); // Reload to refresh cache.
    header("Location:manage_zones");
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/setup_new_zone.html');
include(__DIR__ . '/views/footer.html');
?>
