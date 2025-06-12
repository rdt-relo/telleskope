<?php
require_once __DIR__.'/head.php';

$pagetitle = gettext('Event Locations');

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
    header('HTTP/1.1 403 Forbidden (Access Denied)');
    exit();
}

$event_office_location_id = $_GET['event_office_location_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['action'] ?? '') === 'new')) {
    include __DIR__ . '/views/header.html';
    include __DIR__ . '/views/edit_event_office_location.html.php';

    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$event_office_location_id) {
    $event_office_locations = EventOfficeLocation::All(false);

    include __DIR__ . '/views/header.html';
    include __DIR__ . '/views/event_office_locations.html.php';

    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $event_office_location_id) {
    $event_office_location_id = $_COMPANY->decodeId($event_office_location_id);
    $office_location = EventOfficeLocation::GetEventOfficeLocation($event_office_location_id);

    include __DIR__ . '/views/header.html';
    include __DIR__ . '/views/edit_event_office_location.html.php';

    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'changeEventOfficeLocationStatus')) {
    $event_office_location_id = $_POST['event_office_location_id'] ?? '';

    $event_office_location_id = $_COMPANY->decodeId($event_office_location_id);
    $event_office_location = EventOfficeLocation::GetEventOfficeLocation($event_office_location_id);

    echo $event_office_location->updateEventOfficeLocationStatus($_POST['status']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_office_location_id = $_POST['event_office_location_id'] ?? '';
    $location_name = $_POST['location_name'] ?? '';
    $location_address = $_POST['location_address'] ?? '';

    $location_name = trim($location_name);
    $location_address = trim($location_address);

    if (!$location_name || !$location_address) {
        $_SESSION['error'] = time();
        $_SESSION['form_error'] = gettext('Error: Location name and address cannot be empty');

        Http::Redirect('event_office_locations');
    }

    if ($event_office_location_id) {
        $event_office_location_id = $_COMPANY->decodeId($event_office_location_id);
        $event_office_location = EventOfficeLocation::GetEventOfficeLocation($event_office_location_id);

        $event_office_location->updateEventOfficeLocation(
            location_name: $location_name,
            location_address: $location_address,
        );

        $_SESSION['updated'] = time();

        Http::Redirect('event_office_locations');
    }

    EventOfficeLocation::CreateNewEventOfficeLocation(
        location_name: $location_name,
        location_address: $location_address,
    );

    $_SESSION['added'] = time();

    Http::Redirect('event_office_locations');
}
