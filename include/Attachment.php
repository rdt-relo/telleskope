<?php

class Attachment extends Teleskope
{
    public static function GetAttachment(int $id): ?Attachment
    {
        global $_COMPANY;

        $attachment = self::DBROGet("SELECT * FROM `topic_attachments` WHERE `attachmentid` = {$id} AND `companyid` = {$_COMPANY->id()}");

        if (empty($attachment)) {
            return null;
        }

        return Attachment::Hydrate($id, $attachment[0]);
    }

    public static function GetAllAttachments(int $topicid, string $topictype): array
    {
        global $_COMPANY;

        $attachments = self::DBGet("
            SELECT *
            FROM    `topic_attachments`
            WHERE   `companyid` = {$_COMPANY->id()}
            AND     `topicid` = {$topicid}
            AND     `topictype` = '{$topictype}'
            ORDER BY `createdon` ASC
        ");

        if (!$attachments) {
            return [];
        }

        return array_map(function (array $attachment) {
            return Attachment::Hydrate($attachment['attachmentid'], $attachment);
        }, $attachments);
    }

    public static function CreateAttachment(
        int $topicid,
        string $topictype,
        string $attachment_file_name,
        string $attachment_file_s3_name,
        int $attachment_file_size,
        string $attachment_file_ext
    ): int
    {
        global $_COMPANY, $_USER;

        return self::DBInsertPS('
            INSERT INTO `topic_attachments` (`companyid`, `topicid`, `topictype`, `userid`, `attachment_file_name`, `attachment_file_s3_name`, `attachment_file_size`, `attachment_file_ext`)
            VALUES (?,?,?,?,?,?,?,?)',
            'iisissis',
            $_COMPANY->id(),
            $topicid,
            $topictype,
            $_USER->id(),
            $attachment_file_name,
            $attachment_file_s3_name,
            $attachment_file_size,
            strtolower($attachment_file_ext)
        );
    }

    public function getDownloadUrl(): string
    {
        return $this->getShareableLink();
    }

    public function getShareableLink(): string
    {
        global $_COMPANY, $_ZONE;
        return $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'attachment?id=' . $this->encodedId();
    }

    public function download(bool $save_file_to_disk = false)
    {
        global $_COMPANY;

        $s3 = new Aws\S3\S3Client([
            'version' => 'latest',
            'region' => S3_REGION
        ]);

        $obj_name = $_COMPANY->val('s3_folder') . Company::S3_SAFE_AREA['ATTACHMENTS'] . basename($this->val('attachment_file_s3_name'));

        $params = [
            'Bucket' => S3_SAFE_BUCKET,
            'Key' => $obj_name,
        ];

        if ($save_file_to_disk) {
            $tmpfile = TmpFileUtils::GetTemporaryFile();
            $params['SaveAs'] = $tmpfile;
        }

        $result = $s3->getObject($params);

        if ($save_file_to_disk) {
            return $tmpfile;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $result['ContentType']);
        header('Content-Disposition: attachment; filename="' . $this->val('attachment_file_name') . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        echo $result['Body'];
        exit();
    }

    public function delete(bool $delete_s3_file = true): int
    {
        global $_COMPANY;

        $topic = Teleskope::GetTopicObj($this->val('topictype'), $this->val('topicid'));
        $topic?->expireRedisCache('ATTACHMENTS:FILES');

        if ($delete_s3_file) {
            $_COMPANY->deleteFileFromSafe($this->val('attachment_file_s3_name'), 'ATTACHMENTS', true);
        }

        return self::DBMutate("DELETE FROM `topic_attachments` WHERE `attachmentid` = {$this->id()} AND `companyid` = {$_COMPANY->id()}");
    }

    public function getDisplayName(?int $display_length = null): string
    {
        $display_name = htmlspecialchars($this->val('attachment_file_name'));
        if (!$display_length) {
            return $display_name;
        }

        if (strlen($display_name) > $display_length) {
            return substr($display_name, 0, $display_length - 3) . '...';
        }

        return $display_name;
    }

    public function getReadableSize(): string
    {
        return convertBytesToReadableSize($this->val('attachment_file_size'));
    }

    public function getImageIcon(bool $returnLinkOnly = false): string
    {
        global $_COMPANY, $_ZONE;

        $src = (
            Url::IsValidTeleskopeAdminDomain($_SERVER['HTTP_HOST'])
                ? $_COMPANY->getAdminURL().'/admin/'
                : $_COMPANY->getAppURL($_ZONE->val('app_type'))
            ) . GROUP::GROUP_RESOURCE_PLACEHOLDERS[$this->val('attachment_file_ext')];
        
        if ($returnLinkOnly) {
            return $src ;
        }
        $alt = in_array($this->val('attachment_file_ext'), ['jpg', 'png', 'jpeg']) ? 'image' : $this->val('attachment_file_ext');

        return <<<IMG
        <img src="{$src}" alt="{$alt}" height="16px">
IMG;
    }

    public function getS3PathUrl(): string
    {
        global $_COMPANY;

        return 's3://' . S3_SAFE_BUCKET . '/' . $_COMPANY->val('s3_folder') . Company::S3_SAFE_AREA['ATTACHMENTS'] . $this->val('attachment_file_s3_name');
    }

    public static function NotFound(): void
    {
        Http::NotFound(gettext('This file is no longer available and may have been deleted by the content owner. Please contact your administrator for help.'));
    }
}
