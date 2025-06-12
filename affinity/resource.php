<?php
ob_start(); // Start a wrapper buffer, that we will destroy before printing out the file
require_once __DIR__.'/head.php';

global $_USER; /* @var User $_USER; */

if (isset($_GET['id']) ){
    //Data Validation
    if (($resource_id = $_COMPANY->decodeId($_GET['id']))<1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    //Resource not found
    if (($resource = Resource::GetResource($resource_id, true)) === null
    ) {
        Http::NotFound(gettext('This Resource does not exist.'));
        exit();
    }

    Http::RedirectIfOldUrl($resource->val('zoneid'));

    Http::RedirectIfHashAttributeUrl();

    if ((int) $_ZONE->id() !== (int) $resource->val('zoneid')) {
        http_response_code(403);
        exit(1);
    }

    // Authorization Check
    if ($_USER->cid() != $resource->cid() ||
        !$_USER->canViewContent($resource->val('groupid')) ||
        ($resource->val('is_lead_content') && !$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($resource->val('groupid')))
    ) {
        echo "Access Denied (Insufficient permissions)";
        header(HTTP_FORBIDDEN);
        exit();
    }

  Resource::UpdateTopicUsageLogs($resource_id, TopicUsageLogsActionType::READ);

  if (isset($_GET['resourcetype']) && 1 == $_GET['resourcetype']){

        $resource_data = Resource::GetResourceData($resource_id);

        if (is_array($resource_data) && array_key_exists("resource",$resource_data)) {
            Http::Redirect($resource_data["resource"]);
        }
        else {
          echo gettext('Resource you are looking for does not exist.');
        }
        exit();

    } else {
        try {
            $result = $resource->download();
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if ($e->getAwsErrorCode() === 'NoSuchKey') {
                Http::NotFound(gettext('The requested resource is no longer available'));
            }

            throw $e;
        }
    }

    if ($result) {
        ob_end_clean(); // Destroy the buffer that was created by included files, inner buffer.
        ob_end_clean(); // Destroy the buffer that we created on the first line, i.e. Top most buffer.
        ob_start(); // Start a new buffer.

        if (!$_GET['ispdf'] ) {
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $result['ContentType']);
        header('Content-Disposition: attachment; filename=' . $result['DownloadFilename']);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        echo $result['Body'];
        }else {
        $pdfStreamObject = $result['Body'];
        $pdfContent = $pdfStreamObject->getContents();
        $base64Encodedpdf = base64_encode($pdfContent);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename=' . $result['DownloadFilename']);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        echo $base64Encodedpdf;
        }

    }
}
else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}

?>
