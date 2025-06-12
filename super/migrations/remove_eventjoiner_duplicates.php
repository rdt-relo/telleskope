<?php

require_once __DIR__ . '/../head.php';

Auth::CheckSuperSuperAdmin();

$commit = false;
if (($_GET['commit'] ?? 0) != 1) {
    echo "<h2>Dry run ... in order to commit changes add <code>?commit=1</code> argument to the url</h2>";
    echo "<style>h1,h2 {color: red;} body {color: green;}</style>";
} else {
    $commit = true;
}

function    migrateAllData(int $cid)
{
    $db = new Hems();

    if ($cid) {
        migrateCompanyData($cid);
        return;
    }

    $companyids = $db->ro_get('SELECT `companyid` FROM `companies` WHERE `isactive` = 1 AND `status` = 1');
    $companyids = array_column($companyids, 'companyid');

    foreach ($companyids as $companyid) {
        migrateCompanyData($companyid);
    }
}

function migrateCompanyData(int $companyid): void
{
    global $_COMPANY, $_ZONE;

    $_COMPANY = null;
    $_ZONE = null;

    $_COMPANY = Company::GetCompany($companyid);

    $zid = $_GET['zoneid'] ?? 0;

    $zones = $_COMPANY->getZones();
    foreach ($zones as $zone) {
        if ($zid && $zid != $zone['zoneid']) {
            continue; // If zoneid is provided then skip all zones that do not match the provided zone.
        }
        $_ZONE = $_COMPANY->getZone($zone['zoneid']);
        $message = "Migrating {$_COMPANY->val('subdomain')} ({$_COMPANY->id()}) > {$_ZONE->val('zonename')} ({$_ZONE->id()})";
        Logger::LogDebug($message);
        echo "{$message}<br>";
        migrateCompanyZoneData();
    }

    $_COMPANY = null;
    $_ZONE = null;
}

function migrateCompanyZoneData()
{
    migrateEvents();
}

function migrateEvents()
{
    global $_COMPANY, $_ZONE;
    global $_SUPER_ADMIN;

    $db = new Hems();

    $events = $db->ro_get("
        SELECT  eventid,eventtitle
        FROM    `events` 
        WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND isactive=1
    ");
    foreach ($events as $event) {
        ob_start();
        migrateEventData($event);
        ob_end_flush();
    }
}

function migrateEventData(array $e)
{
    global $_COMPANY, $_ZONE;
    global $_SUPER_ADMIN;
    global $commit;

    $db = new Hems();

    $joiners = $db->ro_get("
        SELECT  *
        FROM    `eventjoiners` 
        WHERE   eventid = {$e['eventid']};
    ");

    if (!empty($joiners)) {
        echo "-&emsp; Checking ". count($joiners) . " of event {$e['eventtitle']} ({$e['eventid']})" . "<br>";
    }
    foreach ($joiners as $joiner) {
        if (!$joiner['other_data'])
            continue;
        $joineeid = $joiner['joineeid'];
        $other_data = Arr::Json2Array($joiner['other_data']);
        $email = $other_data['email'] ?? '';
        if (!$email)
            continue;
        echo " &emsp;*&emsp; {$email}" . "<br>";
        $userObj = User::GetUserByEmail($email) ?? User::GetUserByExternalUserName($email) ?? User::GetUserByEmailUsingAnyConfiguredDomain($email);
        if ($userObj) {
            $duplicate_joinee = Arr::SearchColumnReturnRow($joiners,$userObj->id(),'userid');
            if ($duplicate_joinee && empty($duplicate_joinee['checkedin_date'])) {
                echo "-&emsp;-&emsp;-Merging joinee " . json_encode($joiner) . " into " . json_encode($duplicate_joinee). "<br>";
                if ($commit) {
                    $updated = $_SUPER_ADMIN->super_update("
                            UPDATE  `eventjoiners`
                            SET     `other_data` = '{$joiner['other_data']}',
                                    `checkedin_date` = '{$joiner['checkedin_date']}',
                                    `checkedin_by` = '{$joiner['checkedin_by']}'
                            WHERE   `joineeid` = {$duplicate_joinee['joineeid']}
                ");
                    if ($updated) {
                        echo "-&emsp;-&emsp;-&emsp;- Deleting joinee " . $joiner['joineeid'] . "<br>";
                        $_SUPER_ADMIN->super_update("DELETE from eventjoiners where joineeid={$joiner['joineeid']}");
                    }
                }
            } else {
                //echo "-&emsp;-&emsp;-Updating joinee " . json_encode($joiner) . ", set userid={$userObj->id()}" . "<br>";
                //$_SUPER_ADMIN->super_update("UPDATE eventjoiners SET userid={$userObj->id()} WHERE joineeid={$joiner['joineeid']}");
            }
        }
    }


}

$cid = $_GET['companyid'] ?? 0;
migrateAllData($cid);

echo 'Migration successful <br>';
