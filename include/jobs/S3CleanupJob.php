<?php

use Aws\S3\S3Client;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\MimeType;

class S3CleanupJob extends Job
{
    private const IMAGE_DELETE_BATCH_SIZE = 10;
    private S3Client $s3;

    public function __construct()
    {
        parent::__construct();
        $this->delay = 86400 * 7 * 4; // Every 4 weeks
    }

    // Job can be seeded at global level as
    // insert into jobs (jobid,jobtype,jobsubtype,companyid,createdby,createdon,status,processafter,details,options) values ('SCJ_0_0_0_0',118,127,0,0,now(),0,(now() + interval 1 minute),'','');
    // or at the company level (e.g. for testing for a specific companies), e.g. below we are setting job for companyid 43 to run every 7 days
    // insert into jobs (jobid,jobtype,jobsubtype,companyid,createdby,createdon,status,processafter,details,options) values ('SCJ_43_0_0_0',118,127,43,0,now(),0,(now() + interval 1 minute),'','{"next_job_delay_in_days":7}');
    protected function processAsPerpetualType()
    {
        if (!empty($this->options['next_job_delay_in_days']) && $this->options['next_job_delay_in_days'] > 0) {
            $this->delay = 86400 * intval($this->options['next_job_delay_in_days']);
        }

        if ($this->cid) {
            // If this a company specific job then fetch the specific company
            $list_of_companies = self::DBROGet("SELECT `companyid`,`subdomain`,`s3_folder` FROM `companies` WHERE `companyid`={$this->cid}");
        } else {
            // Fetch all companies
            $list_of_companies = self::DBROGet("SELECT `companyid`,`subdomain`,`s3_folder` FROM `companies`");
        }

        foreach ($list_of_companies as $company) {
            Logger::Log("Processing S3 cleanup for {$company['subdomain']}|{$company['s3_folder']}", Logger::SEVERITY['INFO']);
            $this->deleteS3ImagesByCompany($company['companyid'], $company['s3_folder']);
        }
    }

    private function deleteS3ImagesByCompany(int $company_id, string $company_s3_folder)
    {
        self::DBTemporaryTableOperation('
            CREATE TEMPORARY TABLE `tmp_referenced_images` (
                `img_s3_key` VARCHAR(1024) NOT NULL
            )
        ');

        self::DBTemporaryTableOperation('
            CREATE TEMPORARY TABLE `tmp_s3_images` (
                `img_s3_key` VARCHAR(1024) NOT NULL
            )
        ');

        // Announcements
        $this->fetchImagesFromDB('POST', $company_id);
        $this->fetchS3Images('POST', $company_s3_folder, 7);

        // Events
        $this->fetchImagesFromDB('EVENT', $company_id);
        $this->fetchS3Images('EVENT', $company_s3_folder, 7);

        // Event Followups
        $this->fetchImagesFromDB('EVENT_FOLLOWUP', $company_id);
        $this->fetchS3Images('EVENT_FOLLOWUP', $company_s3_folder, 7);

        // Event Reminders, reminders are not stored in DB ... images will be deleted from S3 after 6 mon
        $this->fetchS3Images('EVENT_REMINDER', $company_s3_folder, 120);

        // Event Volunteer emails, volunteer emails are not stored in DB ...  images will be deleted from S3 after 6 mon
        $this->fetchS3Images('EVENT_VOLUNTEER', $company_s3_folder, 120);

        // Messages
        $this->fetchImagesFromDB('MESSAGES', $company_id);
        $this->fetchS3Images('MESSAGES', $company_s3_folder, 7);

        // Newsletters
        $this->fetchImagesFromDB('NEWSLETTER', $company_id);
        $this->fetchS3Images('NEWSLETTER', $company_s3_folder, 7);

        $this->fetchImagesFromDB('POINTS', $company_id);
        $this->fetchS3Images('POINTS', $company_s3_folder, 7);

        $stmt = self::DBTemporaryTableOperation(
            stmt: '
                SELECT      `tmp_s3_images`.`img_s3_key`
                FROM        `tmp_s3_images`
                LEFT JOIN   `tmp_referenced_images`
                ON          `tmp_s3_images`.`img_s3_key` = `tmp_referenced_images`.`img_s3_key`
                WHERE       `tmp_referenced_images`.`img_s3_key` IS NULL
            '
        );

        $images_to_be_deleted = [];
        while ($key = mysqli_fetch_column($stmt)) {
            $images_to_be_deleted[] = $key;
            if (count($images_to_be_deleted) === self::IMAGE_DELETE_BATCH_SIZE) {
                $this->deleteImages($images_to_be_deleted);
                $images_to_be_deleted = [];
            }
        }

        $this->deleteImages($images_to_be_deleted);

        // Drop the temporary tables as they will be created again in the next iteration.
        self::DBTemporaryTableOperation('
            DROP TABLE `tmp_referenced_images` 
        ');
        self::DBTemporaryTableOperation('
            DROP TABLE `tmp_s3_images` 
        ');
    }

    private function deleteImages(array $images_to_be_deleted)
    {
        $promises = [];
        foreach ($images_to_be_deleted as $key) {
            $promises[] = $this->s3->copyObjectAsync([
                'Bucket' => S3_BUCKET,
                'CopySource' => S3_BUCKET . '/' . $key,
                'Key' => 'delete/' . $key,
            ]);
        }

        Utils::unwrap($promises);

        $promises = [];
        foreach ($images_to_be_deleted as $key) {
            Logger::Log("Deleting S3 Image {$key}", Logger::SEVERITY['INFO']);
            $promises[] = $this->s3->deleteObjectAsync([
                'Bucket' => S3_BUCKET,
                'Key' => $key,
            ]);
        }

        Utils::unwrap($promises);
    }

    private function fetchImagesFromDB(string $module, int $company_id)
    {
        $sql = match ($module) {
            'POST' => "SELECT `post` FROM `post` WHERE `companyid`={$company_id}",
            'EVENT' => "SELECT `event_description` FROM `events` WHERE `companyid`={$company_id}",
            'EVENT_FOLLOWUP' => "SELECT `followup_notes` FROM `events` WHERE `companyid`={$company_id}",
            'MESSAGES' => "SELECT `message` FROM `messages` WHERE `companyid`={$company_id}",
            'NEWSLETTER' => "SELECT `newsletter` FROM `newsletters` WHERE `companyid`={$company_id}",
            'POINTS' => "SELECT `points_image_url` FROM `points_programs` WHERE `company_id` = {$company_id}",
        };

        if (empty($sql)) {
            return;
        }

        $stmt = self::DBROGet(
            select: $sql,
            get_result_stmt: true
        );

        while (($html = mysqli_fetch_column($stmt)) !== false) {
            if (!is_string($html)) {
                continue;
            }

            if (filter_var($html, FILTER_VALIDATE_URL)) {
                $content_images = [$html];
            } else {
                preg_match_all('/<img[^>]+>/i', $html, $matches);

                $content_images = array_map(function ($img_tag) {
                    preg_match('/src="([^"]+)/', $img_tag, $img_matches);
                    return $img_matches[1] ?? '';
                }, $matches[0] ?? []);
            }

            foreach ($content_images as $content_image) {
                $parts = parse_url($content_image);
                if (
                    !$parts
                    || empty($parts['host'])
                    || empty($parts['path'])
                ) {
                    continue;
                }

                if ($parts['host'] === (S3_BUCKET . '.s3.amazonaws.com')
                    && $this->isValidImagePath($parts['path'], $module)
                ) {
                    $key = ltrim($parts['path'], '/');
                    self::DBTemporaryTableOperation("INSERT INTO `tmp_referenced_images` (`img_s3_key`) VALUES ('{$key}')");
                }
            }
        }
    }

    private function fetchS3Images(string $module, string $company_s3_folder, int $older_than_days = 7)
    {
        $module_folder = match ($module) {
            'POST' => Company::S3_AREA['POST'],
            'EVENT' => Company::S3_AREA['EVENT'],
            'EVENT_FOLLOWUP' => Company::S3_AREA['EVENT_FOLLOWUP'],
            'EVENT_REMINDER' => Company::S3_AREA['EVENT_REMINDER'],
            'EVENT_VOLUNTEER' => Company::S3_AREA['EVENT_VOLUNTEER'],
            'MESSAGES' => Company::S3_AREA['MESSAGES'],
            'NEWSLETTER' => Company::S3_AREA['NEWSLETTER'],
            'POINTS' => Company::S3_AREA['POINTS'],
        };

        $this->s3 = new S3Client([
            'version' => 'latest',
            'region' => S3_REGION,
        ]);

        $results = $this->s3->getPaginator('ListObjectsV2', [
            'Bucket' => S3_BUCKET,
            'Prefix' => $company_s3_folder . $module_folder,
        ]);

        if ($older_than_days < 7) {
            $older_than_days = 7;
        }

        $modified_since = new DateTime('-' . $older_than_days . ' day');
        foreach ($results->search('Contents[]') as $image) {
            if ($image['LastModified'] < $modified_since
                && $this->isValidImagePath($image['Key'], $module)
            ) {
                self::DBTemporaryTableOperation("INSERT INTO `tmp_s3_images` (`img_s3_key`) VALUES ('{$image['Key']}')");
            }
        }
    }

    private function isValidImagePath(string $path, string $module): bool
    {
        $pathinfo = pathinfo($path);
        $content_type = MimeType::fromExtension($pathinfo['extension'] ?? '');
        $file_type = explode('/', $content_type ?? '')[0];
        if ($file_type !== 'image') {
            return false;
        }

        return str_contains($path, Company::S3_AREA[$module]);
    }
}
