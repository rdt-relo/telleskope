<?php
require_once __DIR__.'/head.php';

$timezone = @$_SESSION['timezone'];
$topictype = $_GET['topictype'] ?? 'EVT';

if ($topictype === 'EVT') {
    $pagetitle = gettext('Manage Event Custom Fields');
} elseif ($topictype === 'EXP') {
    $pagetitle = gettext('Manage Expense Entry Custom Fields');
} elseif ($topictype === 'BRQ') {
    $pagetitle = gettext('Manage Budget Request Custom Fields');
} elseif ($topictype === 'ORG') {
    $pagetitle = gettext('Manage Organization Custom Fields');
} elseif ($topictype === 'EVTSPK') {
    $pagetitle = gettext('Manage Event Speaker Custom Fields');
} elseif ($topictype === 'REC') {
    $pagetitle = gettext('Manage Recognition Custom Fields');
}

$topic_class = Teleskope::TOPIC_TYPE_CLASS_MAP[$topictype];
// Get all events types of companyid
$data = call_user_func([$topic_class, 'GetEventCustomFields'], false);

$type = ['', 'Dropdown (single select)', 'Checkbox (multi select)', 'Open text field (multi line)', 'Open text field (single line)'];

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/event_custom_fields.html');
?>
