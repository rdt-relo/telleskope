<?php
ini_set('max_execution_time', 10000);
header( 'Content-type: text/plain; charset=utf-8' );





$check2 = date('Ymd');
if ($check2 > '20240830') {
    echo "<p>Migration expired</p>";
    echo "<p>Exiting</p>";
    exit();
}






require_once __DIR__ . '/../head.php';

Auth::CheckSuperSuperAdmin();

$companies = $_SUPER_ADMIN->super_get("SELECT companyid,companyname FROM companies WHERE isactive=1");
echo "<pre>";
foreach ($companies as $company) {
    $companyid = $company["companyid"];

    $company_zones = $_SUPER_ADMIN->super_get("SELECT zoneid,zonename FROM company_zones WHERE companyid={$companyid} AND isactive=1");

    foreach ($company_zones as $company_zone) {
        $zoneid = $company_zone["zoneid"];

        echo "Processing {$company['companyname']} - {$company_zone['zonename']}\n";

        $group_members = $_SUPER_ADMIN->super_get("SELECT DISTINCT users.userid,users.email,users.zoneids FROM groupmembers JOIN `groups` USING (groupid) JOIN users USING (userid) WHERE `users`.companyid={$companyid} AND `groups`.companyid={$companyid} AND `groups`.zoneid={$zoneid} AND `groups`.isactive=1 AND NOT FIND_IN_SET({$zoneid}, users.zoneids)");

        if (!empty($group_members)) {
            foreach ($group_members as $group_member) {
                echo "\tAdding {$company_zone['zoneid']} to {$group_member['zoneids']} for user {$group_member['userid']} - {$group_member['email']}\n";
                $retVal = $_SUPER_ADMIN->super_update("UPDATE `users` SET `zoneids`= TRIM(LEADING ',' FROM CONCAT(zoneids,',', {$company_zone['zoneid']})) WHERE`companyid`={$companyid} AND `userid`={$group_member['userid']}");
            }
        }
        flush();
        ob_flush();
    }
}