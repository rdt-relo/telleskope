<?php

require_once __DIR__ . '/../head.php';

Auth::CheckSuperSuperAdmin();

function migrateAllData()
{
    $db = new Hems();

    $company_ids = $db->ro_get('SELECT `companyid` FROM `companies` WHERE `isactive` = 1 AND `status` = 1');
    $company_ids = array_column($company_ids, 'companyid');

    foreach ($company_ids as $company_id) {
        migrateCompanyData($company_id);
    }
}

function migrateCompanyData(int $company_id): void
{
    global $_COMPANY, $_ZONE;

    $_COMPANY = null;
    $_ZONE = null;

    $_COMPANY = Company::GetCompany($company_id);

    $zones = $_COMPANY->getZones();
    foreach ($zones as $zone) {
        $_ZONE = $_COMPANY->getZone($zone['zoneid']);
        migrateCompanyZoneData();
    }

    $_COMPANY = null;
    $_ZONE = null;
}

function migrateCompanyZoneData()
{
    global $_COMPANY, $_ZONE;

    $db = new Hems();

    $groups = $db->ro_get("SELECT * FROM `groups` WHERE `companyid` = {$_COMPANY->id()} AND `zoneid` = {$_ZONE->id()}");

    foreach ($groups as $group) {
        $group_obj = Group::Hydrate($group['groupid'], $group);
        migrateGroupData($group_obj);
    }
}

function migrateGroupData(Group $group)
{
    migrateGroupLeaders($group);
    migrateChapterLeaders($group);
    migrateChannelLeaders($group);
}

function migrateGroupLeaders(Group $group)
{
    $db = new Hems();

    $group_leader_users = $db->ro_get("
        SELECT      `users`.*
        FROM        `groupleads`
        INNER JOIN  `users`
        ON          `groupleads`.`userid` = `users`.`userid`
        WHERE       `groupid`= {$group->id()}
        AND         `groupleads`.`isactive` = 1
    ");

    foreach ($group_leader_users as $user) {
        $user_obj = User::Hydrate($user['userid'], $user);

        if (!$user_obj->isGroupMember($group->id(), 0, false)) {
            if ($group->addOrUpdateGroupMemberByAssignment($user_obj->id(), 0, 0)) {
                echo "Added UserID {$user_obj->id()} to GroupID {$group->id()} <br>";
            }
        }
    }
}

function migrateChapterLeaders(Group $group)
{
    $chapters = Group::GetChapterListByRegionalHierachies($group->id());

    foreach ($chapters as $region => $regional_chapters) {
        foreach ($regional_chapters as $chapter) {
            $chapter_leaders = $group->getChapterLeads($chapter['chapterid']);
            foreach ($chapter_leaders as $chapter_leader) {
                $user_obj = User::Hydrate($chapter_leader['userid'], $chapter_leader);

                if (!$user_obj->isGroupMember($group->id(), $chapter['chapterid'], false)) {
                    if ($group->addOrUpdateGroupMemberByAssignment($user_obj->id(), $chapter['chapterid'], 0)) {
                        echo "Added UserID {$user_obj->id()} to GroupID {$group->id()}, ChapterID {$chapter['chapterid']} <br>";
                    }
                }
            }
        }
    }
}

function migrateChannelLeaders(Group $group)
{
    $channels = Group::GetChannelList($group->id());

    foreach ($channels as $channel) {
        $channel_leaders = $group->getChannelLeads($channel['channelid']);
        foreach ($channel_leaders as $channel_leader) {
            $user_obj = User::Hydrate($channel_leader['userid'], $channel_leader);

            if (!$user_obj->isGroupChannelMember($group->id(), $channel['channelid'], false)) {
                if ($group->addOrUpdateGroupMemberByAssignment($user_obj->id(), 0, $channel['channelid'])) {
                    echo "Added UserID {$user_obj->id()} to GroupID {$group->id()}, ChannelID {$channel['channelid']} <br>";
                }
            }
        }
    }

}

migrateAllData();

echo 'Migration successful <br>';
