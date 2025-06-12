<?php
require_once __DIR__.'/head.php';

$htmlTitle = sprintf(gettext("Welcome to %s Admin Content Section"), $_COMPANY->val('companyname') .' '. $_COMPANY->getAppCustomization()['group']['name-plural'] . ' - ' . $_ZONE->val('zonename') . ' Zone');

global $_USER; /* @var User $_USER */
global $_ZONE;
$banner	  = $_ZONE->val('banner_background');
$bannerTitle = $_ZONE->val('admin_content_page_title') ? $_ZONE->val('admin_content_page_title') : '';
include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/manage_admin_contents.php');
include(__DIR__ . '/views/footer_html.php');
