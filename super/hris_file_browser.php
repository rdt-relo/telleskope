<?php

require_once __DIR__ .'/head.php';
require_once __DIR__ . '/../include/libs/vendor/autoload.php';

Auth::CheckPermission(Permission::ViewHrisFileBrowser);

use Aws\S3\S3Client;

function inspectHrisFile(string $filename, string $s3_area, string $search_keyword = '', string $search_column = ''): array
{
    global $_COMPANY;

    set_time_limit(120); // Increse processing timeout to 120 seconds

    $json_path = [];
    $record_count = 0;
    $parts = explode('.', $filename);
    $extension = $parts[count($parts) - 1];
    $file_format = $extension;
    if ($extension === 'pgp') {
        $file_format = $parts[count($parts) - 2];
    }
    $file_format = strtolower($file_format);

    $result = $_COMPANY->getFileFromUploader(
        $filename,
        $s3_area,
        true
    );

    // Instead of loading file in memory, we will operate on the file from disk.
    //$file_contents = file_get_contents($result['filename']);
    //if ($extension === 'pgp') {
    //    $file_contents = $_COMPANY->decryptWithPGP($file_contents, Company::DEFAULT_PGP_KEY_NAME);
    //}

    if ($file_format === 'txt') {
        $file_format = 'tsv';
    }

    $records = [];
    if ($file_format === 'csv' || $file_format === 'tsv') {

        $fileHandle = fopen($result['filename'], 'r');
        $record_count = getNumberOfLinesInFile($fileHandle) - 1;
        $delimiter = ($file_format === 'tsv') ? "\t" : ',';

        $cols = [];

        while (count($records) < 3 && ($cells = fgetcsv($fileHandle, 0, $delimiter)) !== FALSE) {

            if (!$cols) {
                $cols = $cells;
            } else {
                $record = [];
                $search_keyword_found = false;
                foreach ($cells as $j => $cell) {
                    $record[$cols[$j]] = $cell;

                    if ($search_keyword) { // Optimization: execute the following code only if the search keyword is set.
                        if ($search_column && ($cols[$j] !== $search_column)) {
                            continue;
                        }

                        if (str_contains(strtolower($cell), strtolower($search_keyword))) {
                            $search_keyword_found = true;
                        }
                    }
                }

                if (empty($search_keyword) || $search_keyword_found) {
                    $records[] = $record;
                }
            }
        }
        fclose($fileHandle);
    }
    elseif ($file_format === 'json') {
        $file_contents = file_get_contents($result['filename']);

        //if ($extension === 'pgp') {
        //    $file_contents = $_COMPANY->decryptWithPGP($file_contents, Company::DEFAULT_PGP_KEY_NAME);
        //}

        $json_rows = json_decode($file_contents, true);

        if ($json_rows && Arr::IsAssoc($json_rows)) {
            list($json_path, $json_rows) = detectJsonPath($json_rows);
        }

        $record_count = count($json_rows);

        foreach ($json_rows as $json_row) {
            $json_row = Arr::Dot($json_row);
            if (empty($cols)) {
                $cols = array_keys($json_row);
            }

            if (count($records) >= 3)
                break;

            if ($search_keyword) {
                $search_string = $search_column ? $json_row[$search_column] : implode(' ', array_values($json_row));

                if (str_contains(strtolower($search_string), strtolower($search_keyword))) {
                    $records[] = $json_row;
                }
            } else {
                $records[] = $json_row;
            }
        }
    }



    return [
        'record_count' => $record_count,
        'records' => $records,
        'aws_kms_key_id' => $result['aws_result']['Metadata']['aws_kms_key_id'] ?? null,
        'cols' => $cols ?? [],
        'json_path' => ($file_format === 'json') ? ('First 1000 characters ... ' . substr($file_contents,0,1000)) : '' //implode('/',$json_path),
    ];

}

function detectJsonPath(array $records): array
{
    $queue = [];
    $path = [];

    if (!Arr::IsAssoc($records)) {
        return $records;
    }

    do {
        foreach ($records as $key => $value) {
            if ($value && is_array($value)) {
                if (!Arr::IsAssoc($value)) {
                    return array ($path, $value);
                } else {
                    $queue[] = $value;
                }
            }
            $path[] = $key;
        }

        $records = array_shift($queue);
    } while ($records);

    return [];
}

function getNumberOfLinesInFile($fileHandle): int
{
    $lineNumber = 0;
    while (!feof($fileHandle)) {
        fgets($fileHandle);
        $lineNumber++;
    }
    rewind($fileHandle);
    return $lineNumber;
}

$company_id = (int) $_SESSION['companyid'];


{
    // We will temporarily create $_COMPANY variable as $_COMPANY global is not approved for super admin folder use
    $_COMPANY = Company::GetCompany($company_id);
    $company_realm = $_COMPANY->getRealm();
    if (isset($_GET['inspectHrisFile']) && $_GET['inspectHrisFile'] === '1') {
        $filename = urldecode($_GET['filename']);
        $s3_area = $_GET['s3_area'];
        $search_keyword = $_GET['search_keyword'] ?? '';
        $search_column = $_GET['search-column'] ?? '';
        $record_data = inspectHrisFile($filename, $s3_area, $search_keyword, $search_column);
        include(__DIR__ . '/views/hris_file_inspect.html.php');
        exit();
    }
    elseif (isset($_GET['deleteHrisFile']) && $_GET['deleteHrisFile'] === '1') {
        $filename = urldecode($_POST['filename']);
        $s3_area = $_POST['s3_area'];
        $_COMPANY->deleteFileFromUploader($filename, $s3_area);
    }
    // reset $_COMPANY variable after temporary use.
    $_COMPANY = null;
}

$s3 = new S3Client([
    'version' => 'latest',
    'region' => S3_REGION,
]);

$result = $s3->ListObjectsV2([
    'Bucket' => S3_UPLOADER_BUCKET,
    'Prefix' => $company_realm . Company::S3_UPLOADER_AREA['user-data-sync'],
]);

$user_data_sync_files = $result['Contents'] ?? [];

$result = $s3->ListObjectsV2([
    'Bucket' => S3_UPLOADER_BUCKET,
    'Prefix' => $company_realm . Company::S3_UPLOADER_AREA['user-data-delete'],
]);

$user_data_delete_files = $result['Contents'] ?? [];

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/hris_file_browser.html.php');
include(__DIR__ . '/views/footer.html');
