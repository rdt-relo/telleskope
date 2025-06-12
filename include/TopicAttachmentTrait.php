<?php

require_once __DIR__ . '/CacheableTrait.php';

trait TopicAttachmentTrait
{
    use CacheableTrait;

    public function isAttachmentsModuleActivated(): bool
    {
        global $_COMPANY;

        $topictype = $this->getCurrentTopicType();
        if ($topictype === 'TMP') {
            $topictype = $this->val('topictype');

            if ($topictype === 'APR') {
                $topictype = $this->val('APPROVAL_TOPICTYPE');

                if (!$topictype) {
                    // Unable to find the approval topictype, assuming that the feature-flag is ON
                    return true;
                }
            }
        }

        if ($topictype === 'APR') {
            $topictype = $this->val('topictype');
        }

        switch ($topictype) {
            case 'POS':
                return $_COMPANY->getAppCustomization()['post']['attachments']['enabled'] ?? false;

            case 'EVT':
            case 'APRLOG':
            case 'APRTSK':
                return $_COMPANY->getAppCustomization()['event']['attachments']['enabled'] ?? false;

            case 'EXP':
            case 'BRQ':
                return $_COMPANY->getAppCustomization()['budgets']['attachments']['enabled'] ?? false;

            case 'MSG':
                return $_COMPANY->getAppCustomization()['messaging']['attachments']['enabled'] ?? false;

            case 'TSK':
                return
                    ($_COMPANY->getAppCustomization()['teams']['enabled'] ?? false)
                    && ($_COMPANY->getAppCustomization()['teams']['attachments']['enabled'] ?? false);
        }

        return false;
    }

    public function getAttachments(): array
    {
        if (!$this->isAttachmentsModuleActivated()) {
            return [];
        }

        if ($this->isSoftDeleted()) {
            return [];
        }

        $attachments = $this->getFromRedisCache('ATTACHMENTS:FILES');
        if ($attachments !== false) {
            return $attachments;
        }

        $topicid = $this->id();
        $topictype = $this->getCurrentTopicType();

        $attachments = Attachment::GetAllAttachments($topicid, $topictype);

        $this->putInRedisCache('ATTACHMENTS:FILES', $attachments, 3600);
        return $attachments;
    }

    public function renderAttachmentsComponent(string $version = 'v1'): string
    {
        if (!$this->isAttachmentsModuleActivated()) {
            return '';
        }

        switch ($version) {
            case 'v3':
            case 'v4':
            case 'v6':
            case 'v12':
            case 'v13':
            case 'v14':
            case 'v15':
            case 'v16':
            case 'v18':
            case 'v20':
            case 'v21':
            case 'v23':
            case 'v24':
            case 'v25':
            case 'v27':
            case 'v29':
            case 'v30':
                $attachments = $this->getAttachments();
        }

        ob_start();
        require __DIR__ . "/../affinity/views/components/attachments/{$version}.html.php";
        return ob_get_clean();
    }

    public function uploadAttachment(array $file): int
    {
        if (!$this->isAttachmentsModuleActivated()) {
            return -1;
        }

        global $_COMPANY;

        $this->expireRedisCache('ATTACHMENTS:FILES');

        $filename = Sanitizer::SanitizeFilename($file['name']);

        $pathinfo = pathinfo($filename);
        $attachment_file_s3_name = 'attachment_' . teleskope_uuid() . '.' . $pathinfo['extension'];

        $_COMPANY->saveFileInSafe(
            $file['tmp_name'],
            $attachment_file_s3_name,
            'ATTACHMENTS',
            true
        );

        return Attachment::CreateAttachment(
            topicid: $this->id(),
            topictype: $this->getCurrentTopicType(),
            attachment_file_name: $filename,
            attachment_file_s3_name: $attachment_file_s3_name,
            attachment_file_size: $file['size'],
            attachment_file_ext: $pathinfo['extension']
        );
    }

    public function copyAttachmentsFrom(Teleskope $source_topic_obj, bool $copy_s3_file = true): void
    {
        global $_COMPANY;

        $this->expireRedisCache('ATTACHMENTS:FILES');

        $source_topic_attachments = $source_topic_obj->getAttachments();

        foreach ($source_topic_attachments as $attachment) {
            $dest_file_s3_name = $attachment->val('attachment_file_s3_name');

            if ($copy_s3_file) {
                $dest_file_s3_name = 'attachment_' . teleskope_uuid() . '.' . $attachment->val('attachment_file_ext');

                $_COMPANY->saveFileInSafe(
                    $attachment->getS3PathUrl(),
                    $dest_file_s3_name,
                    'ATTACHMENTS',
                    true
                );
            }

            Attachment::CreateAttachment(
                topicid: $this->id(),
                topictype: $this->getCurrentTopicType(),
                attachment_file_name: $attachment->val('attachment_file_name'),
                attachment_file_s3_name: $dest_file_s3_name,
                attachment_file_size: $attachment->val('attachment_file_size'),
                attachment_file_ext: $attachment->val('attachment_file_ext')
            );
        }
    }

    public function deleteAllAttachments(bool $delete_s3_file = true): void
    {
        $topicid = $this->id();
        $topictype = $this->getCurrentTopicType();
        $attachments = Attachment::GetAllAttachments($topicid, $topictype);
        foreach ($attachments as $attachment) {
            $attachment->delete($delete_s3_file);
        }
    }

    public function moveAttachmentsFrom(?EphemeralTopic $ephemeral_topic): void
    {
        if ($ephemeral_topic === null) {
            return;
        }

        $this->copyAttachmentsFrom(
            source_topic_obj: $ephemeral_topic,
            copy_s3_file: false
        );

        $ephemeral_topic->deleteIt(delete_s3_file: false);
    }

    public function moveAttachmentsFromEphemeralTopic(): void
    {
        global $_COMPANY;
        if (empty($_POST['ephemeral_topic_id'])) {
            return;
        }

        $ephemeral_topic_id = $_COMPANY->decodeId($_POST['ephemeral_topic_id']);
        $ephemeral_topic = EphemeralTopic::GetEphemeralTopic($ephemeral_topic_id);

        $this->moveAttachmentsFrom($ephemeral_topic);
    }
}
