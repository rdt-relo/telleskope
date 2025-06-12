
<style>
    #bulk_album_media_table{
        width:100%;
    }

    .block-container {
        position: relative;
        margin-bottom: 15px;
        border: 1px solid #80808036;
        height: 280px;
    }

    .block-container .contents {
        position: absolute;
        /* Position the background text */
        bottom: 0px;
        /* At the bottom. Use top:0 to append it to the top */
        background: #000000B3;
        /* Fallback color */
        background: rgba(0, 0, 0, 0.7);
        /* Black background with 0.5 opacity */
        color: #f1f1f1;
        /* Grey text */
        width: 100%;
        /* Full width */
        padding: 10px;
        /* Some padding */
    }

    .pointer {
        cursor: pointer;
    }

    .pointer:hover {
        color: #B7D3EB;
    }
    i.fa.fa-ellipsis-v.col-doutd.dark-gray:hover {
        color: #0085CC !important;
    }
    .left_arrow {
        position: absolute;
        top: 45%;
        left: 2%;
        font-size: 30px;
        color: white !important;
    }

    .left_arrow i {
        color: #fff;
        text-shadow: 0 0 2px #000;
    }

    .right_arrow {
        position: absolute;
        top: 45%;
        right: 2%;
        font-size: 30px;
        color: white !important;
    }

    .right_arrow i {
        color: #fff;
        text-shadow: 0 0 2px #000;
    }

    .black {
        color: #fff !important;
    }

    .red {
        color: red !important;
    }

    .comment-text {
        font-size: 14px;
    }

    #media {
        object-fit: contain;
        max-width: 100%;
        min-height: 535px;
    }

    .bar-left {
        color: #fff;
        position: absolute;
        left: 10px;
        bottom: 2px;
    }
    .bar-center {
        color: #fff;
        position: absolute;
        left: 50%;
        -webkit-transform: translateX(-50%);
        transform: translateX(-50%);
        bottom: 2px;
    }
    .bar-right {
        color: #fff;
        position: absolute;
        right: 10px;
        bottom: 2px;
    }
    .bar-pointer {
        cursor: pointer;
    }

    .highlight-slider-overlay {
        position: absolute;
        background: #b7b7b7bf;
        height: 34px;
        width: 97%;
        bottom: 5px;
        color: #fff;
    }

    .highlight-caption {
        color: #5f5d5d;
        font-size: 15px;
        font-style: italic;
        line-height: initial;
        width: 100%;
        height: 120px;
        padding: 10px;
        border-style: solid;
        border-color: #ededed;
        border-bottom-width: 2px;
    }

    .container-highlights {
        background-color: #ffffff;
        margin-top: 10px;
        padding: 0;
    }


    .image-side {
        background: #8080801c;
    }

    .comment-side {
        border-style: solid;
        border-color: #ffffff;
        border-left-width: 1px;
    }

    div#like_unlike_btn {
        position: absolute;
        bottom: 2px;
        left: 10px;
        /* color: #fff; */
        cursor: pointer;
    }

    button.close.dismiss {
        position: absolute;
        right: 10px;
        z-index: 1000;
        top: 3px;
    }
    .upload-highlight {
    background-color: #333333ba;
    position: absolute;
    right: 24px;
    top: 6px;
    font-size: 19px;
    border: 2px solid #fff;
    padding: 4px 5px 4px 5px;
    border-radius: 16px;
    color: #fff !important;
    }
    .douted-icon-list i {
        color: #fff !important;
    }
    .menupopup i {
    color: #0077b5 !important;
    }

    .douted-icon-list {
    position: absolute;   
    top: 10px;
    right: 2px; 
    }

    .highlight-title {
        white-space: nowrap;
        width: 90%;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 18px;
        font-weight: 600; float: left;
    }

    .comment-input-area {
        width: 90%;
        padding: 5px;
        bottom: auto;
        border-style: solid;
        border-color: #ededed;
        border-top-width: 2px;
        position: absolute;
        bottom: 0px;
    }

.swal2-styled.swal2-cancel {
    background-color: #0077B5 !important;
}


/* Extra small devices (phones, 600px and down) */
@media only screen and (max-width: 600px) {
        #media {
            min-height: 300px;
            display: block;
            max-width: 100%;
            margin: 0 auto;
            padding: 0;
            background-color: #aeaeae;
        }

        .cmt-image-block {
            width: 10%;
        }

        .comment-text {
            width: 100% !important;
            white-space: normal;
        }

        .cmt-delete-block {
            width: 7%;
            margin-top: -55px;
            right: 0;
            margin-left: 90%;
        }

        .row.comment {
            margin-bottom: 20px;
        }

        .block-container .contents {
            position: absolute;
            bottom: 5px;
            background: #000000B3;
            background: rgba(0, 0, 0, 0.7);
            color: #f1f1f1;
            width: 100%;
            padding: 5px;
            height: 75px;
        }

        .file-upload-image {
            height: auto;
            margin: auto;
            width: 100%;
        }

        .button.close.dismiss {
            position: relative;
            right: 10px;
            z-index: 1000;
        }

        input#caption_input {
            width: 90%;
            border: 0.5px solid gray;
            padding: 3px 0;
        }

        button.btn_upt {
            border: 0;
            float: right;
            margin-top: 5px;
            margin-right: 30px;
        }

    }
    @media(max-width:768px) {
    #create_album{
        margin: 5px 0 !important;
       }
       
}
@media only screen and (max-width: 375px) {
    .highlight-slider-overlay {
        font-size: 14px;
    }
}
@media only screen and (max-width: 425px) {
    .douted-icon-list {
        position: unset; 
    }
}
@media only screen and (max-width: 767px) {
    .douted-icon-list {
        position: unset; 
    }
    .fa-share-square {
        margin-right: 0px !important;
    }
}
</style>

<div class="container inner-background">
    <div class="row row-no-gutters w-100">
        <div class="col-md-12">
            <div class="col-md-12 col-xs-12">
                <div class="inner-page-title">
                    <h1><?php echo $documentTitle = gettext("Albums").' - '. $group->val('groupname'); ?></h1>
                        <?php  if ($_USER->canCreateContentInGroupSomething($groupid)) { ?>
                        <a id="addAlbumBtn" href="javascript:void(0);" onclick="openCreateUpdateAlbumModal('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>')" role="button" aria-label="<?= addslashes(gettext("Create new album"));?>">
                            <i class="fa fa-plus-circle link-pointer" title="<?= addslashes(gettext("Create new album"))?>" aria-hidden="true" style="font-size: 1.5rem;"></i>
                        </a>            
                        <?php  }  ?>
                    
                </div>
            </div>
        </div>
    <hr class="lineb" >
 
    <div class="col-md-12 p-0">
        <div id="albumContainer">
            <?php
            if (!empty($data)){
                for($i=0;$i<min(count($data), 9);$i++){
                    $album = Album::ConvertDBRecToAlbum($data[$i]);
                    $cover_photo = ("" == $data[$i]['cover_photo']) ? "../image/nodata/no-highlights.png" : $data[$i]['cover_photo'];

            ?>

            <?php
                $canUpload = $album->loggedinUserCanAddMedia();
            ?>
                
            <div class="col-md-4 highlight-block">
                <div class="block-container">
                    <img src="<?= $cover_photo ?>" alt="<?= $data[$i]['media_alt_text']?>"
                         style="width:100%; height:280px; object-fit: cover;"
                         <?php if ($data[$i]['total'] > 0) { ?>
                                 class="pointer backup_picture"
                         <?php } ?>
                <?php if ($data[$i]['total'] > 0) { ?>
                    onclick="viewAlbumMediaDetail('<?= $_COMPANY->encodeId((int)$data[$i]['albumid']) ?>',0,'', '<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>')"
                <?php } ?>
                    >
                    <div class="contents">
                        <div class="row-no-gutters">
                        <p role="button" tabindex="0" class="highlight-title                                             
                        <?php if ($data[$i]['total'] > 0) { ?>
                                pointer
                        <?php } ?>"
                        <?php if ($data[$i]['total'] > 0) { ?>
                        onclick="viewAlbumMediaDetail('<?= $_COMPANY->encodeId((int)$data[$i]['albumid']) ?>',0,'', '<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>')" onkeypress="viewAlbumMediaDetail('<?= $_COMPANY->encodeId((int)$data[$i]['albumid']) ?>',0,'', '<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>')"
                        <?php } ?>
                        >
                        <?= $data[$i]['title']; ?>
                        </p>
                                                   
                <div class="col-md-2 douted-icon-list">
                <button id="<?= $_COMPANY->encodeId($data[$i]['albumid']) ?>" aria-label="<?= sprintf(gettext('%s Album action dropdown'), $data[$i]['title']); ?>" tabindex="0" class="btn-no-style dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <i role="button" class="fa fa-ellipsis-v col-doutd dark-gray" aria-hidden="true"></i>
                    </button>

                    <ul class="dropdown-menu dropmenu menupopup">
                        <?php if((int)$data[$i]['total']>0 ){?>
                        <li role="listitem">
                            <a role="button" tabindex="0" href="javascript:void(0);" class="dropdown-item" onclick="getShareableLink('<?= $_COMPANY->encodeId($data[$i]['groupid']) ?>','<?= $_COMPANY->encodeId($data[$i]['albumid']) ?>','8')" onkeypress="getShareableLink('<?= $_COMPANY->encodeId($data[$i]['groupid']) ?>','<?= $_COMPANY->encodeId($data[$i]['albumid']) ?>','8')"><i class="fa fa-share-square" aria-hidden="true"></i>&emsp;<?= gettext("Get Shareable Link"); ?></a>
                        </li>  
                        <?php } ?>

                        <li role="listitem"> 
                            <?php
                            if ($_USER->canCreateOrPublishContentInScopeCSV($data[$i]['groupid'],$data[$i]['chapterid'],$data[$i]['channelid'])) { ?>
                            <a role="button" tabindex="0" href="javascript:void(0);" class="dropdown-item"
                                title="<?= gettext("Edit Album"); ?>"
                                onclick="openCreateUpdateAlbumModal('<?= $_COMPANY->encodeId($data[$i]['groupid']) ?>','<?= $_COMPANY->encodeId($data[$i]['albumid']) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>');" onkeypress="openCreateUpdateAlbumModal('<?= $_COMPANY->encodeId($data[$i]['groupid']) ?>','<?= $_COMPANY->encodeId($data[$i]['albumid']) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>');">
                                <i class="fa fa-edit" aria-hidden="true">
                                </i>&emsp;<?= gettext("Edit Album"); ?>
                            </a>
                            <?php } ?>
                        </li>
                            
                        <li role="listitem">
                            <?php if ((int)$data[$i]['total']==0 && $_USER->canCreateOrPublishContentInScopeCSV($data[$i]['groupid'],$data[$i]['chapterid'],$data[$i]['channelid'])){ ?>
                            <a role="button" href="javascript:void(0);" class="dropdown-item confirm"
                            data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" 
                            title="<?= gettext("Are you sure you want to delete album?"); ?>"
                            onclick="deleteAlbum('<?= $_COMPANY->encodeId($data[$i]['albumid']) ?>', '<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>');">
                            <i class="fa fa-trash" aria-hidden="true">
                            </i>&emsp;<?= gettext("Delete Album"); ?>
                            </a>
                            <?php } ?>
                        </li>                        
                        <?php if ((int)$data[$i]['total']==0 && !$_USER->canCreateOrPublishContentInScopeCSV($data[$i]['groupid'],$data[$i]['chapterid'],$data[$i]['channelid'])){ ?>
                        <li role="listitem"><a class="dropdown-item disabled"><i class="fa fas fa-exclamation" aria-hidden="true"></i>&emsp;<?= gettext('No options available');?></a></li>
                        <?php } ?>
                    </ul>
                </div>
                    </div>
                        <br>
                        <div class="row row-no-gutters">
                    <?php 
                     if ($_COMPANY->getAppCustomization()['albums']['likes']) { ?>
                        <div class="bar-left">
                        
                            <i role='img' aria-label="<?= sprintf(gettext('%s Likes'), $data[$i]['totalLikes']); ?>" style="color:#fff !important" class="fa fa-regular fa-thumbs-up newgrey">
                            </i> <?= $data[$i]['totalLikes']; ?>
                        </div>
                    <?php } ?>

                    <?php  if ($_COMPANY->getAppCustomization()['albums']['comments']) { ?>
                        <div class="bar-center">
                            <i role='img' aria-label="<?= sprintf(gettext('%s Comments'), $data[$i]['totalComments']); ?>" style="color:#fff !important" class="fa fa-comment-dots">
                            </i> <?= $data[$i]['totalComments']; ?>
                        </div>
                    <?php } ?>
                    
                        <div class="bar-right">
                            <span><?= $data[$i]['total']; ?> <?= $data[$i]['total']==0 || $data[$i]['total']>1 ? gettext('items'):gettext('item')?></span>
                        </div>
                      
                        </div>
                    </div>
                </div>


                <?php 
                if ($canUpload && $data[$i]['total'] < MAX_ALBUM_MEDIA_ITEMS) {?>
                <span style="float:right; font-size: 20px;">
                    <i id="album_<?= $_COMPANY->encodeId($data[$i]['albumid'])?>" tabindex="0" onclick="openAlbumBulkUploadModal('<?= $_COMPANY->encodeId($data[$i]['albumid'])?>','<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>')" onkeypress="openAlbumBulkUploadModal('<?= $_COMPANY->encodeId($data[$i]['albumid'])?>','<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>')"
                       class="fa fa-plus upload-highlight pointer" title="Upload" role="button" aria-label="<?= sprintf(gettext('Upload album media - %s'), $data[$i]['title']);?>">
                    </i>
                </span>
                <?php } ?>               

            </div>
            <?php
                }
            } else {
            ?>

            <div class="container w6">
                <div class="col-md-12 bottom-sp">
                    <br/>
                    <p style="text-align:center;margin-top:-40px;">Whoops!</p>
                    <p style="text-align:center;margin-top:0px">
                        <img src="../image/nodata/no-highlights.png" alt="No album placeholder image" height="200px;"/>
                    </p>
                    <p style="text-align:center;margin-top:-40px;color:#767676;"><?= gettext("Stay tuned for photos & videos to be Added"); ?></p>
                </div>
            </div>

            <?php } ?>
        </div>
                    <!-- Add "Load More" button -->
<input type="hidden" id="albumDataCount" value="<?= $albumDataCount ?>">
<div id="loadMoreButtonContainer"  class="col-md-12 text-center mb-5 mt-3">
        <button id="loadMoreButton" class="btn btn-affinity" onclick="loadMoreAlbums('<?=$_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>')"><?= gettext('Load more');?>...</button>
</div>
    </div>
</div>

<!-- Upload Album Media Modal -->
<div id="updateAlbumModal"></div>
<div id="mediaUploaded" class="visually-hidden"></div>

<script>

    function openCreateUpdateAlbumModal(g,i,c,ch){
        $.ajax({
            url: 'ajax_albums.php?openCreateUpdateAlbumModal=1',
            type: "GET",
            data: {'groupid':g,'albumid':i,'chapterid':c,'channelid':ch},
            success : function(data) {

                $("#loadAnyModal").html(data);
                $('#new_album_modal').modal({
                    backdrop: 'static',
                    keyboard: false
                });                
            }
        });
    }

<?php
// If there is album that we need to show... show it now.
if (!empty($_SESSION['show_album_id'])) {
$album_id = $_COMPANY->decodeId($_SESSION['show_album_id']);
unset($_SESSION['show_album_id']);
if(($album = Album::GetAlbum($album_id)) !== NULL){    
?>
    $(document).ready(function() {
        viewAlbumMedia('<?= $_COMPANY->encodeId($album->val('albumid')) ?>',0,'', '<?= $_COMPANY->encodeId($album->val('groupid')) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>')
    });
<?php
}
?>

<?php } ?>

function viewAlbumMediaDetail(albumid, media_id_index, album_media_array, g,ch,chnl) {
    var carouselButtonText = '<?= addslashes(gettext("Carousel View"));?>';
    var galleryButtonText = '<?= addslashes(gettext("Gallery View"));?>';
     Swal.fire({
        text: '<?= addslashes(gettext("Choose album media view :"));?>',
        html: "<?= addslashes(gettext("Choose album media view :"));?>" +
            "<br>" +
            '<button type="button" role="button" class="btn btn-sm btn-affinity swal2-confirm carouselview swal2-styled" aria-label="" style="margin-top:15px;">'+carouselButtonText+'</button>' +
            '<button type="button" role="button" tabindex="0" class="btn btn-sm btn-affinity swal2-cancel galleryview swal2-styled" style="margin-top:15px;">'+galleryButtonText+'</button>',
        showCancelButton: false,
        showConfirmButton: false,
        showCloseButton: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
        onBeforeOpen: () => {
            const carouselview = document.querySelector('.carouselview');
            const galleryview = document.querySelector('.galleryview');
            carouselview.addEventListener('click', () => {
                Swal.close();
                viewAlbumMedia(albumid, media_id_index, album_media_array, g,ch,chnl);
                setTimeout(() => {
                var switchFocus = document.querySelector("#album_viewer_area");
                switchFocus.focus();   
                }, 500)     
            });

            galleryview.addEventListener('click', () => {
                Swal.close();
                albumMediaGalleryView(albumid,ch,chnl);
                setTimeout(() => {
                    var switchFocus = document.querySelector(".submenuActive");                
                        $('#getAlbumsMenu').focus();    
                }, 500) 
            });
        }
        }).then(() => {
            Swal.close();
        })
        $('.skip-to-content-link').hide(); 
}  
// replace broken image url with default image url.
$(document).ready(function()
{
    $(".backup_picture").on("error", function(){
        $(this).attr('src', '../image/nodata/no-highlights.png');
    });
});
</script>
<script>
var currentPage = 1;
function loadMoreAlbums(gid, cid, chid) {
    currentPage++;
    getAlbums(gid, cid, chid, currentPage);

    if (currentPage > 1){
		let lastListItem  = document.querySelectorAll('.highlight-block');
		let last = (lastListItem[lastListItem.length -1]);
		last.querySelector(".upload-highlight").focus();
	}
}

updatePageTitle('<?= addslashes(sprintf(gettext('%1$s | %2$s'), $documentTitle, $_COMPANY->val('companyname')));?>');
    
    $(document).on('click','.confirm-dialog-btn-abort', function(){
        setTimeout(function () {
            $('#addAlbumBtn').focus();
        }, 100);
    });  
</script>
