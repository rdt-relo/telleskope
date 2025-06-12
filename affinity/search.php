<?php

require_once __DIR__ . '/head.php';

if (!Config::Get('ENABLE_ZONE_SEARCH')) {
    http_response_code(404);
    exit();
}

$query = trim($_GET['q'] ?? '');
$page = (int) ($_GET['page'] ?? 1);
$per_page = MAX_HOMEPAGE_FEED_ITERATOR_ITEMS;

$groupCategoryRows = array(0); // for the groupcategory loop
$feeds = [];
$show_more = false;
$total_count = 0;
if ($query) {
    $active_groups = $_COMPANY->getAllActiveGroups();
    $active_group_ids = array_map(function (Group $group) {
        global $_USER;
        if ($_USER->canViewContent($group->id())) {
            return $group->id();
        }
        return 0;
    }, $active_groups);

    $search_results = Typesense::Search($query, [
        'group_id' => $active_group_ids,
        'type' => [
            TypesenseDocumentType::Post->value,
            TypesenseDocumentType::Event->value,
            TypesenseDocumentType::Discussion->value,
            TypesenseDocumentType::Newsletter->value,
        ],
    ], $page, $per_page);

    $feeds = array_map(function (array $result) {
        $model = Typesense::GetModelById($result['document']['id']);

        if (!$model) {
            Typesense::DeleteDocument($result['document']['id']);
            return ['content_type' => ''];
        }

        return $model->getHomeFeedData();
    }, $search_results['hits']);

    $total_count = $search_results['found'];
    $show_more = ($total_count > ($page * $per_page));

    $contentsCount = count($feeds);
    if ($show_more) {
        $contentsCount = $per_page + 1;
    }
}

$title = sprintf(gettext('Found %s Results'),$total_count);
$empty_results_msg = 'No results found';
$groups = [];
$show_banner_component = false;
$show_groups_dropdown = false;
$show_discover_groups_btn = false;
$show_home_feed_filters = false;
$filter = '';
$landing_page = 0;
$enable_auto_refresh = false;
$activate_last_selected_btn = false;
$show_homepage_group_tiles = false;
$group_category_id = 0;

if ($page === 1) {
    ob_start();
    require __DIR__ . '/views/search_form.html.php';
    $before_listing_html = ob_get_clean();

    if ($contentsCount) {
        ob_start();
        require __DIR__ . '/views/home/feed_rows.template.php';
        $feed_listing_html_first_page = ob_get_clean();
    }
}

require __DIR__ . '/views/search.html.php';
