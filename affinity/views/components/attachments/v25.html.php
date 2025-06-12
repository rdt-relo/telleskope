<?php

$div_classes = 'pt-3';
global $topicTypeLabel;
$note = sprintf(gettext('Note: These files are not attached to the %1$s. Instead, these files are attached to this approval note. So these files are only shown to the %2$s creators and the approvers, not to the %2$s viewers.'), $topicTypeLabel, strtolower($topicTypeLabel));

require __DIR__ . '/v3.html.php';
