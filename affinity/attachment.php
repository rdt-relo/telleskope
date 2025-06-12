<?php

require_once __DIR__ . '/head.php';

$attachment_id = $_COMPANY->decodeId($_GET['id']);
$attachment = Attachment::GetAttachment($attachment_id);

if (!$attachment) {
    Attachment::NotFound();
}

$topic = Teleskope::GetTopicObj($attachment->val('topictype'), $attachment->val('topicid'));

if (!$topic
    || $topic?->isSoftDeleted()
) {
    Attachment::NotFound();
}

$attachment->download();
