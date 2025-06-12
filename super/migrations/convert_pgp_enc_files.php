<?php

require_once __DIR__ . '/../head.php';

use Aws\S3\S3Client;

function convertPgpEncFile(string $s3_key, string $bucket, string $s3_area): void
{
    global $_COMPANY, $s3;

    if (!str_ends_with($s3_key, '.pgp')) {
        return;
    }

    $pathinfo = pathinfo($s3_key);

    $tmpfilename = TmpFileUtils::GetTemporaryFile($_COMPANY->val('subdomain').'_');

    file_put_contents(
        $tmpfilename,
        $_COMPANY->decryptWithPGP(
            $_COMPANY->getFileFromUploader(
                $pathinfo['basename'],
                $s3_area
            ),
            Company::DEFAULT_PGP_KEY_NAME
        )
    );

    $new_s3_key = str_replace('.pgp', '', $s3_key);

    if ($s3->doesObjectExistV2($bucket, $new_s3_key)) {
        $s3->copyObject([
            'Bucket' => $bucket,
            'CopySource' => $bucket . '/' . $new_s3_key,
            'Key' => 'deleted/' . $new_s3_key,
        ]);
    }

    CompanyEncKey::EncryptFileAndUploadToS3($tmpfilename, [
        'Bucket' => $bucket,
        'Key' => $new_s3_key,
    ]);

    $s3->copyObject([
        'Bucket' => $bucket,
        'CopySource' => $bucket . '/' . $s3_key,
        'Key' => 'deleted/' . $s3_key,
    ]);

    $s3->deleteObject([
        'Bucket' => $bucket,
        'Key' => $s3_key,
    ]);

    echo "\t\t... Successfully converted PGP encrypted file - {$s3_key}\n";
}

$db = new Hems();

$s3 = new S3Client([
    'version' => 'latest',
    'region' => S3_REGION,
]);

$company_ids = $db->get('SELECT `companyid` FROM `companies` WHERE `isactive` = 1 AND `status` = 1');
$company_ids = array_column($company_ids, 'companyid');
echo "<pre>";
foreach ($company_ids as $company_id) {
    $_COMPANY = Company::GetCompany($company_id);
    $company_realm = $_COMPANY->getRealm();
    echo "Checking {$company_realm}\n";

    $s3_areas = ['user-data-sync', 'user-data-delete'];

    foreach ($s3_areas as $s3_area) {
        $result = $s3->ListObjectsV2([
            'Bucket' => S3_UPLOADER_BUCKET,
            'Prefix' => $company_realm . Company::S3_UPLOADER_AREA[$s3_area],
        ]);

        foreach ($result['Contents'] as $file) {
            echo "\t* Processing {$file['Key']}\n";
            convertPgpEncFile($file['Key'], S3_UPLOADER_BUCKET, $s3_area);
        }
    }

    $_COMPANY = null;
}
