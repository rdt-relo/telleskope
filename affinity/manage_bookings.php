<?php
require_once __DIR__.'/head.php';

if (!defined('INDEX_PAGE') && empty ($_ZONE)) { // User is logged in, but for some reason the $_ZONE is not set, logout and retry
  Http::Redirect('logout');
}

if (!($_COMPANY->getAppCustomization()['booking']['enabled'])) {
    Http::Forbidden('This feature has been disabled for your account');
    header(HTTP_FORBIDDEN);
    exit();
}

$bannerTitle = gettext('Manage Bookings');
$pageTitle = gettext('Manage Bookings');

$groupid = 0;

include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/manage_bookings_html.php');
include(__DIR__ . '/views/footer_html.php');
?>