<?php

require_once __DIR__ . '/../head.php';

Auth::CheckSuperSuperAdmin();

function migrateAllData()
{
    global $_SUPER_ADMIN;

    $companyids = $_SUPER_ADMIN->super_roget('SELECT `companyid` FROM `companies` WHERE `isactive` = 1 AND `status` = 1');
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

    $zones = $_COMPANY->getZones();
    foreach ($zones as $zone) {
        $_ZONE = $_COMPANY->getZone($zone['zoneid']);
        migrateZoneData();
    }

    $_COMPANY = null;
    $_ZONE = null;
}

function migrateZoneData()
{
    global $_COMPANY, $_ZONE;
    global $_SUPER_ADMIN;

    if (!$_COMPANY->getAppCustomization()['event']['pinning']['enabled']) {
        /**
         * Calling Event model pinUnpinEvent() instead of direct update pin_to_top=0 so that redis-cache also gets cleared
         */
        $results = $_SUPER_ADMIN->super_get("
            SELECT  *
            FROM    `events`
            WHERE   `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
            AND     `pin_to_top` = 1
        ");

        foreach ($results as $result) {
            $event = Event::Hydrate($result['eventid'], $result);
            $event->pinUnpinEvent(0);
            echo "Disabled pinning - companyid - {$_COMPANY->id()}, zoneid - {$_ZONE->id()}, event id {$event->id()}<br>";
        }
    }

    if (!$_COMPANY->getAppCustomization()['post']['pinning']['enabled']) {
        /**
         * Calling Post model pinUnpinAnnouncement() instead of direct update pin_to_top=0 so that redis-cache also gets cleared
         */
        $results = $_SUPER_ADMIN->super_get("
            SELECT  *
            FROM    `post`
            WHERE   `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
            AND     `pin_to_top` = 1
        ");

        foreach ($results as $result) {
            $post = Post::Hydrate($result['postid'], $result);
            $post->pinUnpinAnnouncement(0);
            echo "Disabled pinning - companyid - {$_COMPANY->id()}, zoneid - {$_ZONE->id()}, announcement id {$post->id()}<br>";
        }
    }

    if (!$_COMPANY->getAppCustomization()['newsletters']['pinning']['enabled']) {
        $results = $_SUPER_ADMIN->super_get("
            SELECT  *
            FROM    `newsletters`
            WHERE   `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
            AND     `pin_to_top` = 1
        ");

        foreach ($results as $result) {
            $newsletter = Newsletter::Hydrate($result['newsletterid'], $result);
            $newsletter->pinUnpinNewsletter(0);
            echo "Disabled pinning - companyid - {$_COMPANY->id()}, zoneid - {$_ZONE->id()}, newsletter id {$newsletter->id()}<br>";
        }
    }

    if (!$_COMPANY->getAppCustomization()['discussions']['pinning']['enabled']) {
        $results = $_SUPER_ADMIN->super_get("
            SELECT  *
            FROM    `discussions`
            WHERE   `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
            AND     `pin_to_top` = 1
        ");

        foreach ($results as $result) {
            $discussion = Discussion::Hydrate($result['discussionid'], $result);
            $discussion->pinUnpinDiscussion(0);
            echo "Disabled pinning - companyid - {$_COMPANY->id()}, zoneid - {$_ZONE->id()}, discussion id {$discussion->id()}<br>";
        }
    }

    if (!$_COMPANY->getAppCustomization()['resources']['pinning']['enabled']) {
        $results = $_SUPER_ADMIN->super_get("
            SELECT  *
            FROM    `group_resources`
            WHERE   `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
            AND     `pin_to_top` = 1
        ");

        foreach ($results as $result) {
            $resource = Resource::Hydrate($result['resource_id'], $result);
            $resource->pinUnpinResource(0);
            echo "Disabled pinning - companyid - {$_COMPANY->id()}, zoneid - {$_ZONE->id()}, resource id {$resource->id()}<br>";
        }
    }
}

migrateAllData();
echo "Migration completed <br>";
