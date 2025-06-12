<?php

$_EAI_MODULE = 'UPLOADER'; // Module is needed by head.php
require_once __DIR__.'/head.php'; // $_COMPANY variable will be set after authenticating

/**
 * Note: In this file we use die() instead of exit() as die closes the connection immediately on return.
 */

// Only POST is supported
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate operation
    $operation = $_GET['op'];

    $_AUDIT_META['operation'] = $operation ?: '';
    $valid_operations = array('user-data-sync', 'user-data-delete');
    if (!in_array($operation, $valid_operations)) {
        $_AUDIT_META['error_code'] = 'unsupported operation requested';
        header(HTTP_BAD_REQUEST);
        die ('Error: Invalid op attribute specified. Valid values are: '. implode(', ', $valid_operations));
    }

    // Validate API version
    $version = $_GET['version'];
    $_AUDIT_META['version'] = $version ?: '';
    $valid_versions = array('v3');
    if (!in_array($version, $valid_versions)) {
        $_AUDIT_META['error_code'] = 'unsupported version requested';
        header(HTTP_BAD_REQUEST);
        die ('Error: Invalid version attribute specified. Valid values are: '. implode(', ', $valid_versions));
    }

    $encryption = $_GET['encryption'] ?? '';
    $_AUDIT_META['encryption'] = $encryption ?: '';
    $valid_encryptions = array('pgp');
    if (!empty($encryption) && !in_array($encryption, $valid_encryptions)) {
        $_AUDIT_META['error_code'] = 'unsupported encryption requested';
        header(HTTP_BAD_REQUEST);
        die ('Error - Invalid encryption specified. Valid values are: '. implode(', ', $valid_encryptions));
    }

    $format = $_GET['format'];
    $_AUDIT_META['format'] = $format ?: '';
    $valid_formats = array('csv','tsv','json');

    // Validate the file format attribute
    if (!in_array($format, $valid_formats)) {
        $_AUDIT_META['error_code'] = 'unsupported format provided';
        header(HTTP_BAD_REQUEST);
        die ('Error: Invalid format specified. Valid values are: ' . implode(', ', $valid_formats));
    }

    // Validate one file is uploaded
    if (!empty($_FILES) || count($_FILES) === 1) {
        // File uploaded as a form
        $uploaded_file = array_values($_FILES)[0];
        $uploaded_file_name = basename($uploaded_file['name']);
        $uploaded_file_tmpname = $uploaded_file['tmp_name'];
        $uploaded_file_size = $uploaded_file['size'];
        $uploaded_file_exts = explode('.', $uploaded_file_name);

        $_AUDIT_META['content_length'] = $uploaded_file_size;

    } elseif ($_SERVER['CONTENT_LENGTH']) {

        $_AUDIT_META['content_length'] = $_SERVER['CONTENT_LENGTH'];
        // File uploaded as content
        $uploaded_file_name = '';
        // Get file name from content disposition
        if (!empty($_GET['filename'])) {
            $uploaded_file_name = $_GET['filename'];
        } elseif (isset($_SERVER['HTTP_CONTENT_DISPOSITION'])) {
            $_AUDIT_META['http_content_disposition'] = $_SERVER['HTTP_CONTENT_DISPOSITION'];
            $uploaded_file_name = preg_replace('/^.*filename[ ]*=[ ]*/', '', $_SERVER['HTTP_CONTENT_DISPOSITION']);
        }

        if (empty($uploaded_file_name)) {
            // Generate one
            $uploaded_file_name = $_COMPANY->val('subdomain').'_uploaded.'.$format;
            if ($encryption) {
                $uploaded_file_name .= '.'.$encryption;
            }
            $_AUDIT_META['warning_code'] = "unable to extract filename from content disposition, generating {$uploaded_file_name}";
        }

        $_AUDIT_META['tmp_filename'] = $uploaded_file_name;

        $uploaded_file_tmpname = TmpFileUtils::GetTemporaryFile($_COMPANY->val('subdomain').'_');
        file_put_contents($uploaded_file_tmpname , file_get_contents('php://input'));
        $uploaded_file_name = basename(trim($uploaded_file_name,"\"' "));
        $uploaded_file_size = $_SERVER['CONTENT_LENGTH'];
        $uploaded_file_exts = explode('.', $uploaded_file_name);
    } else {
        $_AUDIT_META['error_code'] = 'exactly one attachment is expected';
        header(HTTP_BAD_REQUEST);
        die ('Error: Exactly one attachment is expected');
    }

    // Check if the file is compressed and uncompress it if it was compressed
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $contentType = $finfo->file($uploaded_file_tmpname);
    if ($contentType === 'application/zip') {
        $uploaded_file_name = str_replace('.zip', '', $uploaded_file_name);
        copy('zip://' . $uploaded_file_tmpname . '#' . $uploaded_file_name, $uploaded_file_tmpname . '_uz');
        unlink($uploaded_file_tmpname);
        $uploaded_file_tmpname = $uploaded_file_tmpname . '_uz';
    }

    $pathinfo = pathinfo($uploaded_file_name);
    if ($pathinfo['extension'] === 'pgp' || $encryption === 'pgp') {

        if ($pathinfo['extension'] === 'pgp') {
            $uploaded_file_name = $pathinfo['filename']; // .pgp extension was added, we remove it here.
        } else {
            $uploaded_file_name = $pathinfo['filename'] . '.' . $pathinfo['extension']; // restore original extension
        }

        $file_contents = file_get_contents($uploaded_file_tmpname);
        $file_contents = $_COMPANY->decryptWithPGP($file_contents, Company::DEFAULT_PGP_KEY_NAME);

        $tmpfile = TmpFileUtils::GetTemporaryFile($_COMPANY->val('subdomain').'_');
        file_put_contents($tmpfile, $file_contents);
        unlink($uploaded_file_tmpname); // Remove the file as we no longer need it

        $uploaded_file_tmpname = $tmpfile;
    }

    $retVal = $_COMPANY->saveFileInUploader($uploaded_file_tmpname, $uploaded_file_name, $operation); // Encrypt if the file is not already encrypted


    unlink($uploaded_file_tmpname); // Remove the file as we no longer need it

    if (!empty($retVal)) {
        die ('Success');
    } else {
        $_AUDIT_META['error_message'] = 'Unable to upload the file';
        header(HTTP_INTERNAL_SERVER_ERROR);
        die('Upload error');
    }
}
$_AUDIT_META['error_message'] = 'unsupported HTTP operation';
header(HTTP_NOT_FOUND);
die ('Bad Request (004)');