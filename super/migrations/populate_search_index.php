<?php

require_once __DIR__ . '/../head.php';

Auth::CheckSuperSuperAdmin();

Typesense::CreateCollection();

function uploadAllData()
{
    $db = new Hems();

    $company_ids = $db->ro_get('SELECT `companyid` FROM `companies` WHERE `isactive` = 1 AND `status` = 1');
    $company_ids = array_column($company_ids, 'companyid');

    foreach ($company_ids as $company_id) {
        uploadCompanyData($company_id);
    }
}

function uploadCompanyData(int $company_id): void
{
    global $_COMPANY, $_ZONE;

    $_COMPANY = null;
    $_ZONE = null;

    $_COMPANY = Company::GetCompany($company_id);

    uploadCompanyPosts();
    uploadCompanyEvents();
    uploadCompanyDiscussions();
    uploadCompanyNewsletters();

    $zones = $_COMPANY->getZones();
    foreach ($zones as $zone) {
        $_ZONE = $_COMPANY->getZone($zone['zoneid']);
        uploadCompanyTeams();
    }

    $_COMPANY = null;
    $_ZONE = null;
}

function uploadCompanyPosts(): void
{
    global $_COMPANY;
    $db = new Hems();

    $batch_size = 100;
    $min_id = 0;

    do {
        $query = '
            SELECT
                        *
            FROM        `post`
            WHERE       `companyid` = ?
            AND         `postid` > ?
            ORDER BY    `postid` ASC
            LIMIT       ?
        ';

        $posts = $db->getPS(
            $query,
            'iii',
            $_COMPANY->id(),
            $min_id,
            $batch_size
        );

        $posts = array_map(function (array $post) {
            return Post::Hydrate($post['postid'], $post);
        }, $posts);

        Typesense::UploadCollection($posts);

        $count = count($posts);

        if ($count) {
            $min_id = $posts[$count - 1]->val('postid');
        }
    } while ($count === $batch_size);
}

function uploadCompanyEvents()
{
    global $_COMPANY;

    $db = new Hems();

    $batch_size = 100;
    $min_id = 0;

    do {
        $query = '
            SELECT
                        *
            FROM        `events`
            WHERE       `companyid` = ?
            AND         `eventid` > ?
            ORDER BY    `eventid` ASC
            LIMIT       ?
        ';

        $events = $db->getPS(
            $query,
            'iii',
            $_COMPANY->id(),
            $min_id,
            $batch_size
        );

        $events = array_map(function (array $event) {
            return Event::Hydrate($event['eventid'], $event);
        }, $events);

        Typesense::UploadCollection($events);

        $count = count($events);

        if ($count) {
            $min_id = $events[$count - 1]->val('eventid');
        }
    } while ($count === $batch_size);
}

function uploadCompanyDiscussions()
{
    global $_COMPANY;

    $db = new Hems();

    $batch_size = 100;
    $min_id = 0;

    do {
        $query = '
            SELECT
                        *
            FROM        `discussions`
            WHERE       `companyid` = ?
            AND         `discussionid` > ?
            ORDER BY    `discussionid` ASC
            LIMIT       ?
        ';

        $discussions = $db->getPS(
            $query,
            'iii',
            $_COMPANY->id(),
            $min_id,
            $batch_size
        );

        $discussions = array_map(function (array $discussion) {
            return Discussion::Hydrate($discussion['discussionid'], $discussion);
        }, $discussions);

        Typesense::UploadCollection($discussions);

        $count = count($discussions);

        if ($count) {
            $min_id = $discussions[$count - 1]->val('discussionid');
        }
    } while ($count === $batch_size);
}

function uploadCompanyNewsletters()
{
    global $_COMPANY;

    $db = new Hems();

    $batch_size = 100;
    $min_id = 0;

    do {
        $query = '
            SELECT
                        *
            FROM        `newsletters`
            WHERE       `companyid` = ?
            AND         `newsletterid` > ?
            ORDER BY    `newsletterid` ASC
            LIMIT       ?
        ';

        $newsletters = $db->getPS(
            $query,
            'iii',
            $_COMPANY->id(),
            $min_id,
            $batch_size
        );

        $newsletters = array_map(function (array $newsletter) {
            return Newsletter::Hydrate($newsletter['newsletterid'], $newsletter);
        }, $newsletters);

        Typesense::UploadCollection($newsletters);

        $count = count($newsletters);

        if ($count) {
            $min_id = $newsletters[$count - 1]->val('newsletterid');
        }
    } while ($count === $batch_size);
}

function uploadCompanyTeams()
{
    global $_COMPANY;

    $db = new Hems();

    $batch_size = 100;
    $min_id = 0;

    do {
        $query = '
            SELECT      *
            FROM        `teams`
            WHERE       `companyid` = ?
            AND         `teamid` > ?
            ORDER BY    `teamid` ASC
            LIMIT       ?
        ';

        $teams = $db->getPS(
            $query,
            'iii',
            $_COMPANY->id(),
            $min_id,
            $batch_size
        );

        $teams = array_map(function (array $team) {
            return Team::Hydrate($team['teamid'], $team);
        }, $teams);

        Typesense::UploadCollection($teams);

        $count = count($teams);

        if ($count) {
            $min_id = $teams[$count - 1]->val('teamid');
        }
    } while ($count === $batch_size);
}

uploadAllData();
