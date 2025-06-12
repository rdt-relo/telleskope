<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__.'/head.php';

/* @var Company $_COMPANY */
/* @var User $_USER */

###### All Ajax Calls For Events ##########

## OK
## Get All Group Albums
if (isset($_GET['getAlbums'])){

    // initial values for $chapterid and $channelid
    $chapterid = 0;
    $channelid = 0;

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getAlbums']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        (isset($_GET['chapterid']) && ($chapterid = $_COMPANY->decodeId($_GET['chapterid'])) < 0) ||
        (isset($_GET['channelid']) && ($channelid = $_COMPANY->decodeId($_GET['channelid'])) < 0)) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $showGlobalChapterOnly = intval($_SESSION['showGlobalChapterOnly'] ?? 0);
    $showGlobalChannelOnly = intval($_SESSION['showGlobalChannelOnly'] ?? 0);

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['perPage']) ? intval($_GET['perPage']) : 10;

	$data = Album::GetGroupAlbums($groupid,$chapterid,$channelid,$showGlobalChapterOnly,$showGlobalChannelOnly, $page, $perPage);
    $albumDataCount = count($data);
	include(__DIR__ . '/views/templates/get_all_albums.template.php');
}

## Create/update Album
elseif (isset($_GET['createUpdateAlbum']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        !isset($_POST['album_title'])
        ||  ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0
        ||  ($albumid = $_COMPANY->decodeId($_POST['albumid'])) < 0
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (isset($_POST['chapters'])) {
        $chapterids = implode(',', $_COMPANY->decodeIdsInArray($_POST['chapters']));
    } else {
        $chapterids = 0;
    }

    if (isset($_POST['channelid'])) {
        $channelid = $_COMPANY->decodeId($_POST['channelid']);
    } else {
        $channelid = 0;
    }

    if ($albumid){
        $album = Album::GetAlbum($albumid);
        // Authorization Check -
        if (!$_USER->canUpdateContentInScopeCSV($groupid,$chapterids, $channelid, $album->val('isactive')) && !$_USER->canCreateOrPublishContentInScopeCSV($groupid,$chapterids, $channelid)){
            if ((empty($chapterids) && $_COMPANY->getAppCustomization()['chapter']['enabled']) || ($_COMPANY->getAppCustomization()['channel']['enabled'] &&  empty($channelid))) {
                $contextScope = $_COMPANY->getAppCustomization()['channel']['name-short'];
                if (empty($chapterids)){
                    $contextScope = $_COMPANY->getAppCustomization()['chapter']['name-short'];
                }
                AjaxResponse::SuccessAndExit_STRING(-3, '', sprintf(gettext("Please select a %s scope"),$contextScope), gettext('Error'));
            } else {
                header(HTTP_FORBIDDEN);
                exit();
            }
        }
        $status = Album::UpdateAlbum($_POST['album_title'], $albumid, $chapterids, $channelid, $_POST['whocanuploadmedia']);
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Album updated successfully"), gettext('Success'));
    } else {
        // Authorization Check -
        if (!$_USER->canCreateOrPublishContentInScopeCSV($groupid, $chapterids, $channelid)) {
            if ((empty($chapterids) && $_COMPANY->getAppCustomization()['chapter']['enabled']) || ($_COMPANY->getAppCustomization()['channel']['enabled'] &&  empty($channelid))) {

                $contextScope = $_COMPANY->getAppCustomization()['channel']['name-short'];
                if (empty($chapterids)){
                    $contextScope = $_COMPANY->getAppCustomization()['chapter']['name-short'];
                }
                AjaxResponse::SuccessAndExit_STRING(-3, '', sprintf(gettext("Please select a %s scope"),$contextScope), gettext('Error'));
            } else {
                header(HTTP_FORBIDDEN);
                exit();
            }
        }
        $status = Album::CreateAlbum($_POST['album_title'], $groupid, $chapterids, $channelid, $_POST['whocanuploadmedia']);
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Album created successfully"), gettext('Success'));
    }
}

## openBulkUploadModal
elseif (isset($_GET['openBulkUploadModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $chapterid = 0;
    $channelid = 0;
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['openBulkUploadModal'])) < 1 ||
        ($album_id = $_COMPANY->decodeId($_GET['album_id'])) < 0 ||
        ($album = Album::GetAlbum($album_id)) === null ||
        ($group = Group::GetGroup($groupid)) === null ||
        (isset($_GET['chapterid']) && ($chapterid = $_COMPANY->decodeId($_GET['chapterid'])) < 0) ||
        (isset($_GET['channelid']) && ($channelid = $_COMPANY->decodeId($_GET['channelid'])) < 0)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$album->loggedinUserCanAddMedia()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $is_gallery_view = 0;
    if(isset($_GET['is_gallery_view'])){
        $is_gallery_view = $_GET['is_gallery_view'];
    }
    $modalTitle = gettext('Upload Media');
    include(__DIR__ . "/views/templates/album_add_update.template.php");

}

## Album Media ajax handling: getUploadURL
elseif (isset($_GET['albumGetUploadURL']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    //Data Validation
    if (empty($_POST['data']) ||
        empty($decoded_post_input = json_decode($_POST["data"], true)) ||
        empty($decoded_post_input['album_id']) ||
        ($album_id = $_COMPANY->decodeId($decoded_post_input['album_id'])) < 0 ||
        ($album = Album::GetAlbum($album_id)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }


    // Authorization Check
    if (!$album->loggedinUserCanAddMedia()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (isset($decoded_post_input['action'])) {
        if ("getUploadURL" == $decoded_post_input['action']  && !empty($decoded_post_input['filename'])) {

            $ext = $db->getExtension($decoded_post_input['filename']);
            $media_uuid = teleskope_uuid() . '.' . $ext;

            $presigned_url = Album::GetPreSignedURL($media_uuid, $album_id, $_ZONE->id(), 'PutObject');

            $alt_tag = $decoded_post_input['alt_tag'];

            ob_clean();
            echo json_encode(array(
                    "status" => "success",
                    "presigned_url" => $presigned_url,
                    "media_uuid" => $media_uuid,
                    "album_id" => $_COMPANY->encodeId($album_id),
                    "ext" => $ext,
                    "alt_tag" => $alt_tag,
                    "filename" => $decoded_post_input['filename']
                )
            );
            exit;
        }
    }
    ob_clean();
    exit;
}

## Album Media ajax handling: finalizeAlbumMediaUpload
elseif (isset($_GET['albumFinalizeAlbumMediaUpload']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    //Data Validation
    if (empty($_POST['data']) ||
        empty($decoded_post_input = json_decode($_POST["data"], true)) ||
        empty($decoded_post_input['album_id']) ||
        ($album_id = $_COMPANY->decodeId($decoded_post_input['album_id'])) < 0 ||
        ($album = Album::GetAlbum($album_id)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$album->loggedinUserCanAddMedia()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    
    if ( count($album->getAlbumMediaList()) >= MAX_ALBUM_MEDIA_ITEMS){
        ob_clean();
        echo '{"status":"failed","message":"Album media upload not allowed because album media maximum limit of 400 has been reached."}';
        exit();
    }
    
    if (isset($decoded_post_input['action'])) {
        if ("finalizeAlbumMediaUpload" == $decoded_post_input['action'] && !empty($decoded_post_input['media_uuid']) && !empty($decoded_post_input['ext'])) {

            if ($album->registerMedia($decoded_post_input['media_uuid'], $decoded_post_input['ext'], $decoded_post_input['alt_tag'])) {
                $response = '{"status":"success","message": "Uploaded"}';
            } else {
                $response = '{"status":"failed","message": "Something went wrong. Please try again."}';
            }

            ob_clean();
            echo $response;
            exit;

        }
    }
    ob_clean();
    echo '{"status":"failed","message": "Something went wrong. Please try again."}';
    exit;
}

## Album Media ajax handling: deleteAlbumMedia
elseif (isset($_GET['albumDeleteAlbumMedia']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    //Data Validation
    if (empty($_POST['data']) ||
        empty($decoded_post_input = json_decode($_POST["data"], true)) ||
        empty($decoded_post_input['album_id']) ||
        ($album_id = $_COMPANY->decodeId($decoded_post_input['album_id'])) < 0 ||
        ($album = Album::GetAlbum($album_id)) === null ||
        (!isset($decoded_post_input['action'])) || ("deleteAlbumMedia" != $decoded_post_input['action']) ||
        empty($decoded_post_input['media_id']) ||
        ($media_id = $_COMPANY->decodeId($decoded_post_input['media_id'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check -
    if (!$album->loggedinUserCanDeleteMedia($media_id)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (Album::DeleteMedia($media_id, $album_id)) {
        $response = "success";
    } else {
        $response = "failed";
    }
    ob_clean();
    echo '{"status":"' . $response . '"}';
    exit;
}

## Album Media ajax handling: deleteAlbum
elseif (isset($_GET['albumDeleteAlbum']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    //Data Validation
    if (empty($_POST['data']) ||
        empty($decoded_post_input = json_decode($_POST["data"], true)) ||
        empty($decoded_post_input['album_id']) ||
        ($album_id = $_COMPANY->decodeId($decoded_post_input['album_id'])) < 0 ||
        ($album = Album::GetAlbum($album_id)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check -
    if (!$_USER->canCreateOrPublishContentInScopeCSV($album->val('groupid'),$album->val('chapterid'),$album->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (isset($decoded_post_input['action'])) {
        if ("deleteAlbum" == $decoded_post_input['action']) {

            if (Album::DeleteAlbum($album_id)) {
                $response = "success";
            } else {
                $response = "failed";
            }
            ob_clean();
            echo '{"status":"' . $response . '"}';
            exit;

        }
    }
    ob_clean();
    exit;
}

## Album Media ajax handling: setAlbumMediaCover
elseif (isset($_GET['albumSetAlbumMediaCover']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    //Data Validation
    if (empty($_POST['data']) ||
        empty($decoded_post_input = json_decode($_POST["data"], true)) ||
        empty($decoded_post_input['album_id']) ||
        ($album_id = $_COMPANY->decodeId($decoded_post_input['album_id'])) < 0 ||
        ($album = Album::GetAlbum($album_id)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check -
    if (!$_USER->canCreateOrPublishContentInScopeCSV($album->val('groupid'), $album->val('chapterid'), $album->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (isset($decoded_post_input['action'])) {
         if ("setAlbumMediaCover" == $decoded_post_input['action'] && !empty($decoded_post_input['media_id'])) {
            $media_id = (int)$_COMPANY->decodeId($decoded_post_input['media_id']);
            $media_row = Album::GetMedia($media_id);

            if (empty($media_row)) {
                ob_clean();
                echo '{"status":"error", "details":"no such media exist"}';
                exit;
            }

            Album::SetMediaCover((int)$media_row["album_mediaid"], $album_id);
            ob_clean();
            echo json_encode(array(
                    "status" => "success"
                )
            );
            exit;
        }
    }
    ob_clean();
    exit;
}

## Album Media ajax handling:  getPreviewAlbumMedia
elseif (isset($_GET['albumGetPreviewAlbumMedia']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    //Data Validation
    if (empty($_POST['data']) ||
        empty($decoded_post_input = json_decode($_POST["data"], true)) ||
        empty($decoded_post_input['album_id']) ||
        ($album_id = $_COMPANY->decodeId($decoded_post_input['album_id'])) < 0 ||
        ($album = Album::GetAlbum($album_id)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    //  Authorization Check (for viewing)
    if (!$_USER->canViewContent((int)$album->val('groupid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (isset($decoded_post_input['action'])) {
        if ("getPreviewAlbumMedia" == $decoded_post_input['action'] && !empty($decoded_post_input['media_id'])) {

            $media_id = (int)$_COMPANY->decodeId($decoded_post_input['media_id']);
            $media_row = Album::GetMedia($media_id);

            if (empty($media_row)) {
                ob_clean();
                echo '{"status":"error", "details":"no such media exist"}';
                exit;
            }

            // get S3 GET URL
            $preview_url = Album::GetPreSignedURL($media_row["media_uuid"], $album_id, $_ZONE->id(), 'GetObject', 0);

            $download_url = Album::GetPreSignedURL($media_row["media_uuid"], $album_id, $_ZONE->id(), 'GetObject', 0,1);

            // replace the placeholder in widget code
            $widget_code = str_replace('[MEDIA_URL_PLACEHOLDER]', $preview_url, $media_row["widget_code"]);

            // check if user can delete media or make it cover photo
            $is_cover = ($album->val('cover_mediaid') == $media_row["album_mediaid"]);
            $can_delete = $album->loggedinUserCanDeleteMedia($media_row["album_mediaid"]);
            $can_manage = $album->loggedinUserCanManageAlbum();

          $documentTitle =  $album->val('title').' - Carousel View';
            // return JSON with label and widget_code
            ob_clean();
            echo json_encode(array(
                    'status' => 'success',
                    'can_delete' => $can_delete,
                    'is_cover' => $is_cover,
                    'can_change_cover' => $can_manage,
                    'widget_code' => $widget_code,
                    'media_type' => $media_row['media_type'],
                    'media_download_url' => $download_url,
                    'documentTitlee_short' => $documentTitle
                )
            );
            exit;
        }
    }
    ob_clean();
    exit;
}

## Album Media ajax handling:  getAlbumMediaIDs
elseif (isset($_GET['albumGetAlbumMediaIDs']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    //Data Validation
    if (empty($_POST['data']) ||
        empty($decoded_post_input = json_decode($_POST["data"], true)) ||
        empty($decoded_post_input['album_id']) ||
        ($album_id = $_COMPANY->decodeId($decoded_post_input['album_id'])) < 0 ||
        ($album = Album::GetAlbum($album_id)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    //  Authorization Check (for viewing)
    if (!$_USER->canViewContent($album->val('groupid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (isset($decoded_post_input['action'])) {
        if ("getAlbumMediaIDs" == $decoded_post_input['action']) {

            $rows = $album->getAlbumMediaList();
            $rows = $_COMPANY->encodeIdsInArray(array_column($rows, 'album_mediaid'));

            if (!$rows) {
                ob_clean();
                echo '{"status":"error", "details":"no such media exist"}';
                exit;
            }
            ob_clean();
            echo json_encode(array(
                    "status" => "success",
                    "media_ids" => $rows
                )
            );
            exit;
        }
    }
    ob_clean();
    exit;
}

## Album Media ajax handling: getAlbumMediaLikesAndComments
elseif (isset($_GET['albumGetAlbumMediaLikesAndComments']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    //Data Validation
    if (empty($_POST['data']) ||
        empty($decoded_post_input = json_decode($_POST["data"], true)) ||
        empty($decoded_post_input['album_id']) ||
        ($album_id = $_COMPANY->decodeId($decoded_post_input['album_id'])) < 0 ||
        ($album = Album::GetAlbum($album_id)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    //  Authorization Check (for viewing)
    if (!$_USER->canViewContent($album->val('groupid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (isset($decoded_post_input['action'])) {
         if ("getAlbumMediaLikesAndComments" == $decoded_post_input['action'] && !empty($decoded_post_input['media_id'])) {

            if (!$_COMPANY->getAppCustomization()['albums']['likes'] && !$_COMPANY->getAppCustomization()['albums']['comments']) {  ?>
                <div class="col-md-12 p-0 mt-5">
                    <div class="text-center"><i class="fa fa-info-circle gray fa-2x" aria-hidden="true"></i></div>
                    <div class="text-center"><?= gettext('Like and comments feature are disabled for album. Please contact your administrator to enable these features!'); ?></div>
                </div>
    <?php   
                exit();
            }  
            $media_id = (int)$_COMPANY->decodeId($decoded_post_input['media_id']);
            $media_row = Album::GetMedia($media_id);
            $groupid = (int)$album->val('groupid');
            $comments = Album::GetComments_2((int)$media_row["album_mediaid"]);
            $topicid = (int)$media_row["album_mediaid"];
            $commentid = 0;
            $disableAddEditComment = false;
            $submitCommentMethod = "AlbumMediaComment";
            $mediaUploadAllowed = false;

            $maxLikersToShow = 6;
            $myLikeType = Album::GetUserReactionType($topicid);
            $myLikeStatus = (int) !empty($myLikeType);
            $latestLikers = Album::GetLatestLikers($topicid, true, $maxLikersToShow);
            $totalLikers = Album::GetLikeTotals($topicid);
            $likeTotalsByType = Album::GetLikeTotalsByType($topicid);
            $showAllLikers = true;
            $likeUnlikeMethod = 'AlbumMediaTopic';
            $wide_layout = true;

            ob_clean();
            if ($_COMPANY->getAppCustomization()['albums']['likes']) { 
            /* Likes Widget */
                echo '<div class="px-2 after-comnt" id="likeUnlikeWidget">';                            
                    include (__DIR__.'/views/common/comments_and_likes/topic_like_and_likers_bedge.template.php'); 
                echo '</div>';   
            }            
          
           
            if ($_COMPANY->getAppCustomization()['albums']['comments']) { 
                /* Comments Widget */
                include (__DIR__ . "/views/common/comments_and_likes/comments_container.template.php");
            }  
            exit;
        }
    }
    ob_clean();
    exit;
}

## Create New Album
elseif (isset($_GET['openCreateUpdateAlbumModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if ( 
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 || 
        ($albumid = $_COMPANY->decodeId($_GET['albumid']))<0
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
   
    // Authorization Check -
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) { 
        header(HTTP_FORBIDDEN);
        exit();
    }
    $chapters = array();
    $channels = array();
    $selectedChapterIds = array();
    $selectedChannelId = 0;

    $modalTitle = gettext("New Album");
    $chapterid  = $_COMPANY->decodeId($_GET['chapterid']) ?? 0;
    $channelid  = $_COMPANY->decodeId($_GET['channelid']) ?? 0;
    $album = null;
    $selectedChapterIds = array();
    if ($albumid){
        $modalTitle = gettext("Update Album");
        $album = Album::GetAlbum($albumid);
        $selectedChapterIds = explode(',', $album->val('chapterid'));
        $selectedChannelId = $album->val('channelid');
    }

    if($_COMPANY->getAppCustomization()['chapter']['enabled']){
        $chapters = Group::GetChapterListByRegionalHierachies($groupid);
    }
    if($_COMPANY->getAppCustomization()['channel']['enabled']){
        $channels= Group::GetChannelList($groupid);
    }
    $displayStyle = 'row';
    include (__DIR__ . "/views/templates/new_album.teamplate.php");
   
}

elseif (isset($_GET['albumMediaGalleryView']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $chapterid = 0;
    $channelid  = 0;
    //Data Validation
    if (
        ($album_id = $_COMPANY->decodeId($_GET['albumid'])) < 0 ||
        ($album = Album::GetAlbum($album_id)) === null ||        
        (isset($_GET['chapterid']) && ($chapterid = $_COMPANY->decodeId($_GET['chapterid'])) < 0) ||
        (isset($_GET['channelid']) && ($channelid = $_COMPANY->decodeId($_GET['channelid'])) < 0)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    //  Authorization Check (for viewing)
    if (!$_USER->canViewContent((int)$album->val('groupid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $encAlbumId = $_COMPANY->encodeId($album_id);
    $encGroupId = $_COMPANY->encodeId($album->val('groupid'));
    $encChapterId = $_COMPANY->encodeId($chapterid );
    $encChannelId = $_COMPANY->encodeId($channelid);
    $canAddMedia = $album->loggedinUserCanAddMedia();

    $albumMediaList = $album->getAlbumMediaList();

    $mediaKeys = json_encode($_COMPANY->encodeIdsInArray(array_column($albumMediaList,'album_mediaid')));
    include(__DIR__ . "/views/templates/get_album_media_gallery_view.template.php");
}

elseif (isset($_GET['changeAlbumMediaPosition']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    //Data Validation
    if (($album_id = $_COMPANY->decodeId($_POST['album_id'])) < 0 ||
        ($album = Album::GetAlbum($album_id)) === null ||
        ($media_id = $_COMPANY->decodeId($_POST['media_id'])) < 0  ||
        ($current_order_value = $_POST['current_order_value']) < 0  || 
        ($new_order_value = $_POST['new_order_value']) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canCreateOrPublishContentInScopeCSV($album->val('groupid'), $album->val('chapterid'), $album->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $changeOrder  = $album->changeAlbumMediaPosition($media_id,$current_order_value,$new_order_value);
    if ($changeOrder) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Media position changed successfully"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("An error occurred while updating the media position. Please try again."), gettext('Error'));
    }

}

elseif (isset($_GET['getAlbumMediaIframe']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    //Data Validation
    if (($albumid = $_COMPANY->decodeId($_GET['albumid'])) < 0 ||
        ($album = Album::GetAlbum($albumid)) === null ||
        ($mediaid = $_COMPANY->decodeId($_GET['mediaid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

   $media = $album->getMediaDetail($mediaid); 
   if ($media){ ?>


        <style>
            .iframe-xmp {
                white-space: pre-wrap; 
                white-space: -moz-pre-wrap;
                white-space: -pre-wrap;
                white-space: -o-pre-wrap;
                word-wrap: break-word;
                background-color:#000;
                color:#fff;
                padding:20px;
            }
        </style>
        <div id="videoIframeModal" class="modal fade">
            <div aria-label="<?=gettext("Copy iFrame")?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="form_title"><?=gettext("Copy iFrame")?></h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="col-md-12">
                            <xmp class="iframe-xmp" id="iframe_content"><iframe width="880" height="510" src="<?= $_COMPANY->getiFrameURL($_ZONE->val('app_type')); ?>album_video_player?uid=<?= $media['media_uuid'];?>&aid=<?= $albumid?>&zid=<?= $_ZONE->id(); ?>&autoplay=off&loop=off&muted=off"></iframe></xmp>
                            <p style="color:gray;"><?= gettext("Note: You can embed this iFrame as Video in the About Us page. If this video is deleted, the iFrame will stop working.") ?></p>
                            <div class="col-md-12 text-center mt-3">
                                <button class="btn btn-affinity" onclick="copyIframe();">Copy iFrame</button>
                            </div>
                        </div>
                    </div>
                </div>  
            </div>
        </div>

        <script>
            function copyIframe(){
                var dummy = document.createElement('input'),
                text = $("#iframe_content").html();
                document.body.appendChild(dummy);
                dummy.value = text;
                dummy.select();
                dummy.setSelectionRange(0, 99999);  /* For mobile devices */
                document.execCommand('copy');
                document.body.removeChild(dummy);
                swal.fire({title: '<?= gettext("Success");?>',text:'<?= gettext("iFrame copied successfully to your clipboard"); ?>'}).then(function(result) {
                    $('#videoIframeModal').modal('hide');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                });
            }
        </script>
<?php
   } else {
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Media not found."), gettext('Error'));
   }


}

// download all media files as zip
//elseif (isset($_GET['downloadBulkAlbumMedia']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
//
//    if (($album_id = $_COMPANY->decodeId($_POST['encalbumid'])) < 0 ||
//        ($album = Album::GetAlbum($album_id)) === null
//    ) {
//        header(HTTP_BAD_REQUEST);
//        exit();
//    }
//    $mediaiteam = $_POST['mediaiteam'];
//    $teampname = "album_media_".time().'.zip';
//    $download = tempnam(sys_get_temp_dir(), $teampname);
//    $zip = new ZipArchive;
//    $zip->open($download, ZipArchive::CREATE);
//    foreach ($mediaiteam as $enciteam) {
//        if (($media_id = $_COMPANY->decodeId($enciteam)) >0 && ($media = $album->getMediaDetail($media_id))){
//            $signedUrl = Album::GetPreSignedURL($media['media_uuid'], $album_id, $_ZONE->id(), 'GetObject',0,true);;
//            $filename = $media['media_uuid'];
//            $zip->addFromString($filename,  file_get_contents($signedUrl));
//        }
//    }
//    $zip->close();
//    header('Content-Type: application/zip');
//    header("Content-Disposition: attachment; filename = $teampname");
//    header('Content-Length: ' . filesize($download));
//    readfile($download);
//    unlink($download);
//}


else {
    /** @noinspection ForgottenDebugOutputInspection */
    Logger::Log('Nothing to do ...');
    header('HTTP/1.1 501 Not Implemented (Bad Request)');
    exit;
}