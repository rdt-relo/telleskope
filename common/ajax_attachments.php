<?php

if (!isset($_GET['download_attachment'])) {
    define('AJAX_CALL', 1);
}

if (Env::IsAdminPanel()) {
    require_once __DIR__ . '/../admin/head.php';
} else {
    require_once __DIR__ . '/../affinity/head.php';
}

if (isset($_GET['view_attachments'])) {
    $topictype = $_GET['topictype'];
    $topicid = $_COMPANY->decodeId($_GET['topicid']);

    $topic = Teleskope::GetTopicObj($topictype, $topicid);
    $attachments = $topic->getAttachments();

    require __DIR__ . '/views/templates/view_attachments.html.php';
    exit();
} elseif (isset($_GET['download_attachment'])) {
    $attachment_id = $_COMPANY->decodeId($_GET['id']);
    $attachment = Attachment::GetAttachment($attachment_id);

    if (!$attachment) {
        Attachment::NotFound();
    }

    if (Env::IsLocalEnv()) {
        $query_params = [
            'rurl_new' => '',
            'rurl_old' => '',
        ];

        Http::Redirect('/1/affinity/deprecate_old_url.html.php?' . http_build_query($query_params));
    }

    Http::Redirect($attachment->getShareableLink());
} elseif (isset($_GET['delete_attachment'])) {
    $attachment_id = $_COMPANY->decodeId($_POST['attachment_id']);
    $attachment = Attachment::GetAttachment($attachment_id);
    $attachment->delete();
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Deleted successfully'), gettext('Success'));

} elseif (isset($_GET['open_attachments_uploader'])) {
    $topictype = $_GET['topictype'];
    $topicid = $_COMPANY->decodeId($_GET['topicid']);
    $topic = Teleskope::GetTopicObj($topictype, $topicid);

    $attachments = $topic->getAttachments();

    $max_file_attachments = 3;
    if (
        ($topictype === 'EXP') // Expense entry
        || (($topictype === 'TMP') && $topic->val('topictype') === 'EXP') // Ephemeral topic of Expense entry
    ) {
        $max_file_attachments = 10;
    }

    if (count($attachments) >= $max_file_attachments) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Cannot upload more than %s attachments'), $max_file_attachments), gettext('Error'));
    }

    require __DIR__ . '/views/templates/attachments_uploader.html.php';

    exit();
} elseif (isset($_GET['upload_attachment'])) {
    $topictype = $_POST['topictype'];
    $topicid = $_COMPANY->decodeId($_POST['topicid']);

    $topic = Teleskope::GetTopicObj($topictype, $topicid);

    $attachments = $topic->getAttachments();

    $max_file_attachments = 3;
    if (
        ($topictype === 'EXP') // Expense entry
        || (($topictype === 'TMP') && $topic->val('topictype') === 'EXP') // Ephemeral topic of Expense entry
    ) {
        $max_file_attachments = 10;
    }

    if (count($attachments) >= $max_file_attachments) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Cannot upload more than %s attachments'), $max_file_attachments), gettext('Error'));
    }

    $topic->uploadAttachment($_FILES['file']);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Uploaded successfully'), gettext('Success'));

}
