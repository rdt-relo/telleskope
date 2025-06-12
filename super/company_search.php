<?php

require_once __DIR__ . '/head.php';

Auth::CheckSuperSuperAdmin();

$company_id = $_GET['company_id'];
$_COMPANY = Company::GetCompany($company_id);

$zones = $_COMPANY->getZones();
$query = $_GET['q'] ?? '';
$search_results = null;
if ($query) {
    $_ZONE = $_COMPANY->getZone($_COMPANY->decodeId($_GET['zone_id']));

    try {
        $search_results = Typesense::Search(
            query: $query,
            filters: [
                'group_id' => $_GET['group_id'] ? $_COMPANY->decodeId($_GET['group_id']) : '',
                'type' => $_GET['type'] ?? '',
            ],
            page: $_GET['page'],
            per_page: $_GET['per_page']
        );
    } catch (Exception $e) {
        echo 'Unable to perform search, check typesense configuration, exception ' . $e->getMessage();
        exit();
    }
}

require __DIR__ . '/views/header.html';
require __DIR__ . '/views/company_search.html.php';
require __DIR__ . '/views/footer.html';
