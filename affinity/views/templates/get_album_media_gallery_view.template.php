<style>
     .gallery-page {
        margin-bottom: 3rem;
     }

     .gallery-card-container {
         padding: 0;
         width: 100%;
         margin: 10px auto;
     }

    .gallery-card {
        position: relative;
        text-align: center;
        max-height: 200px;
        max-width: 200px;
        margin: auto;
    }

     .gallery-card-title{
         width: 100%;
         z-index: 1;
         text-align: initial;
         position: absolute;
         background-color : rgba(0, 0, 0, 0.70);
     }

    .gallery-card-title input[type=checkbox]:checked{
        box-shadow: -0.5px -0.5px 1.5px 1.5px gray;
    }

     .gallery-card-image {
         min-height: 200px;
         min-width: 200px;
         max-height: 200px;
         max-width: 200px;
         margin: auto;
         cursor: pointer;
         line-height: 40px;
         text-align: center;
         background-color: lightgray;
     }

    .active-page{
        display:block;
    }
    .inactive-page{
        display:none;
    }   


</style>
<div class="container inner-background">
    <div class="row row-no-gutters w-100">
        <div class="col-md-12">
            <div class="col-xs-12">
                <div class="inner-page-title">
                    <h1><?php echo $documentTitle = $album->val('title') .' - Gallery View'; ?></h1>
                <?php if ($canAddMedia && count($albumMediaList) < MAX_ALBUM_MEDIA_ITEMS) {?>
                    <span style="font-size: 24px;">
                    <i id="album_<?= $encAlbumId ?>" tabindex="0" onclick="openAlbumBulkUploadModal('<?= $encAlbumId ?>','<?= $encGroupId ?>','<?= $encChapterId ?>','<?= $encChannelId ?>',1)" onkeypress="openAlbumBulkUploadModal('<?= $encAlbumId ?>','<?= $encGroupId ?>','<?= $encChapterId ?>','<?= $encChannelId ?>',1)" 
                    class="fa fa-plus-circle link-pointer" title="Upload" role="button" aria-label="<?= gettext("Upload album media");?>">
                    </i>
                    </span>
                    <?php } ?>
                </div>
            </div>
            
        </div>
      
            
        <div class="col-md-12 gallery-page">
        <?php  if (!empty($albumMediaList)){ 
            $totalAlbumMedia = count($albumMediaList);
            $albumMediaListChuncks = array_chunk($albumMediaList,MAX_ALBUM_MEDIA_PAGE_ITEMS);
            $totalAlbumMediaListChunks = count($albumMediaListChuncks);
            $disabled = "";
        ?>
            <div class="text-right col-md-12">
                <div class="mb-2">             
                <a href="javascript:void(0);" class="link_show" onclick="checkUncheckAllCheckBoxes('gallery-card-title-checkbox',true),showHideDeleteAction()"> <?= gettext("Select All");?></a>  | <a href="javascript:void(0);" class="link_show" onclick="checkUncheckAllCheckBoxes('gallery-card-title-checkbox',false),showHideDeleteAction()"> <?= gettext("Deselect All");?></a><span id="delete_action_button" style="display:none;">  | <a id="popConfirmBtn" href="javascript:void(0);" class="link-pointer red confirm" onclick='initBulkAlbumMediaDeletion("<?= $encAlbumId; ?>","<?= $encGroupId ;?>","<?= $encChapterId; ?>","<?= $encChannelId; ?>","<?= $totalAlbumMedia; ?>")' title="<?= gettext('Are you sure you want to delete selected media?'); ?>" >  <?= gettext("Delete Selected");?>(<span id="selectedCount">0</span>)</a></span>                
                </div>
            </div>
        <?php 
            $p = 1;   
            foreach($albumMediaListChuncks as $albumMediaList ){  ?>
               <div class="div-pagination<?= $p ==1 ? '-active-'.$album_id : '-'.$album_id; ?> <?= $p ==1 ? 'active-page' : 'inactive-page'; ?>" data-page="<?= $p; ?>" id="page<?= $album_id;?>_<?= $p;?>" >
                    
                   
            <?php
                $i = 0;  
                foreach($albumMediaList as $albumMedia ){ 
                    $preview_url = Album::GetPreSignedURL($albumMedia["media"], $album_id, $_ZONE->id(), 'GetObject', 1);
                    $download_url = Album::GetPreSignedURL($albumMedia["media"], $album_id, $_ZONE->id(), 'GetObject', 0,1);
                    $canDeleteMedia = $album->loggedinUserCanDeleteMedia($albumMedia['album_mediaid']);
                    if(!$canDeleteMedia){
                        $disabled = "disabled";
                    }
                ?>
                    
                    <div class="col-md-3 gallery-card-container">
                    <div class="gallery-card">

                        <div class="gallery-card-title">
                      
                            <input aria-label="Media item <?= $i+1 ?>" type="checkbox" class="gallery-card-title-checkbox ml-2" onchange="showHideDeleteAction(this);" name="mediaiteam" value="<?= $_COMPANY->encodeId($albumMedia['album_mediaid'])?>" id="<?= $_COMPANY->encodeId($albumMedia['album_mediaid'])?>" <?= $disabled; ?>/>

                            <div class="pull-right">
                            <a aria-label="<?= sprintf(gettext('%s album download this media'), $album->val('title')); ?>" href="<?= $download_url; ?>" class="js-download-link">
                                <i class="fa fa-download" style="color:#fff;" aria-hidden="true"></i>
                            </a>
                            &nbsp;
                            <?php if ($_USER->canCreateOrPublishContentInScopeCSV($album->val('groupid'), $album->val('chapterid'), $album->val('channelid'))) { ?>
                                <a href="javascript:void(0);"  class="link-pointer <?=$_COMPANY->encodeId($albumMedia['album_mediaid'])?>" onclick="openChangeAlbumMediaModal('<?= $_COMPANY->encodeId($album_id)?>','<?= $_COMPANY->encodeId($albumMedia['album_mediaid'])?>','<?= $totalAlbumMedia; ?>',<?= ($p-1)*MAX_ALBUM_MEDIA_PAGE_ITEMS + $i + 1; ?>)" aria-label="<?= sprintf(gettext('%s album change media position'), $album->val('title')); ?>">
                                    <i class="fa fa-solid fa-sort mr-2" aria-hidden="true" style="color: #ffffff;"></i>
                                </a>
                            <?php } ?>
                            </div>
                      
                            
                        </div>
                        <img tabindex="0" role="button" class="gallery-card-image" onclick='viewAlbumMedia("<?= $encAlbumId; ?>", <?= ($p-1)*MAX_ALBUM_MEDIA_PAGE_ITEMS + $i; ?>, <?= $mediaKeys; ?>,"<?= $encGroupId ;?>","<?= $encChapterId; ?>","<?= $encChannelId; ?>",1)' src="<?= $preview_url ?>" alt="<?= $albumMedia['media_alt_text']?>">
                    </div>
                    </div>               

            <?php 
                    $i++;
                }
            ?>
            </div>
        <?php 
                $p++;
            } 
        ?>

            <?php if($totalAlbumMedia > MAX_ALBUM_MEDIA_PAGE_ITEMS){ ?>
                <div class="col-md-12 mb-3">
                    <ul class="pagination justify-content-end">
                        <li class="page-item prev<?= $album_id; ?> disabled"><a class="page-link" onclick="suggestionsPagination(<?= $totalAlbumMediaListChunks; ?>,<?= $album_id; ?>, 1)" href="javascript:void(0)">Previous</a></li>
                        <li class="page-item next<?= $album_id; ?>"><a class="page-link" onclick="suggestionsPagination(<?= $totalAlbumMediaListChunks; ?>,<?= $album_id; ?>,2)" href="javascript:void(0)">Next</a></li>
                    </ul>
                </div>
            <?php } ?>
        <?php } else { ?>

            <div class="container w6">
                <div class="col-md-12 bottom-sp">
                    <br/>
                    <p style="text-align:center;margin-top:-40px;">Whoops!</p>
                    <p style="text-align:center;margin-top:0px">
                        <img src="../image/nodata/no-highlights.png" alt="No album placeholder image" height="200px;"/>
                    </p>
                    <p style="text-align:center;margin-top:-40px;color:#767676;"><?= gettext("Stay tuned for photos & videos to be added"); ?></p>
                </div>
            </div>

            <?php } ?>

        </div>
    </div>
</div>

<div class="modal" id="media_deletion_progress" tabindex="-1">
    <div aria-label="<?= gettext('Deleting media');?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><?= gettext('Deleting <span id ="totalBulkRecored"></span> media(s)');?></h2>
            </div>
            <div class="modal-body">
                <div class="progress_bar form-group hide" id="progress_bar">
                    <div class="progress">
                        <div class="progress-bar progress-bar-animated" id="prgress_bar" style="width:0%"></div>
                    </div>
                    <div class="text-center progress_status"></div>
                </div>
            </div>
            
            <div class="modal-footer" id="refresh_grallery" style="display:none;">
                <button type="button"  id="close_show_progress_btn" class="btn btn-secondary hide" onclick='albumMediaGalleryView("<?= $encAlbumId; ?>","<?= $encChapterId; ?>","<?= $encChannelId; ?>");' data-dismiss="modal"><?= gettext("Close"); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="change_media_order" tabindex="-1" aria-label="<?= gettext('Change media position');?>">
    <div aria-label="<?= gettext('Change media position');?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" ><?= gettext('Change media position');?></h2>
            </div>
            <div class="modal-body">
                <div class="col-12">
                    <form id="orderchangeform">
                        <input type="hidden" id="change_order_media_id">
                        <input type="hidden" id="albumidvalue">
                        <input type="hidden" id="max_order_value">
                        <div class="form-group">
                            <?= gettext("Current position is ")?><span  id="currentValue"></span>, <?= gettext("update position to: ")?>
                        <input aria-describedby="changeOrderHelptext" type="number" autocomplete="off" min="1" max="<?= $totalAlbumMedia; ?>" class="form-control" id="neworderValue" placeholder="" aria-label="<?= sprintf(gettext('Enter new position value between 1 and %d'), $totalAlbumMedia); ?>">
                            <small id="changeOrderHelptext" class="form-text text-muted red"><?= sprintf(gettext('Enter new position value between 1 and %d'), $totalAlbumMedia); ?></small>
                        </div>
                    </form>
                </div>
                
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-affinity" onclick='changeAlbumMediaPosition();'><?= gettext("Change Position"); ?></button>
                <button type="button" class="btn btn-affinity-gray" data-dismiss="modal"><?= gettext("Cancel"); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Upload Album Media Modal -->
<div id="updateAlbumModal"></div>

<script>
$(document).ready(function(){ // Handle drogdrom by Enter key press
    $(function(){
        $('.gallery-card-image').keyup(function(e) { 
            if (e.key == 'Enter') {
                $(this).click();
            }   
        });
    });
    updatePageTitle('<?= addslashes(sprintf(gettext('%1$s | %2$s'), $documentTitle, $_COMPANY->val('companyname')));?>');
    

    const checkboxes = document.querySelectorAll('.gallery-card-title-checkbox');
    const targetLinks = document.querySelectorAll('.link_show');
    checkboxes.forEach(function(checkbox) {
    if (checkbox.disabled) {
            targetLinks.forEach(link => {
                link.style.cursor = 'not-allowed';
                link.setAttribute('aria-disabled', 'true');
                link.removeAttribute('href');  
                link.removeAttribute('onclick');        
            });
    } 
    });
})
   function showHideDeleteAction(e){
        let checkedCheckboxes = document.querySelectorAll('input[name="mediaiteam"]:checked');
        let countCheckedCheckboxes = checkedCheckboxes.length;
        if (countCheckedCheckboxes>0){
            $("#delete_action_button").show();
            $(".confirm").popConfirm({content: ''});
        } else {
            $("#delete_action_button").hide();
        }
        $('#selectedCount').html(countCheckedCheckboxes);
    }

    function initBulkAlbumMediaDeletion(albumid,gid,cid,chid,totalAlbumMedia){        

        var selectedMediaItems = document.querySelectorAll('input[name="mediaiteam"]:checked');
        var noOfUpdate = selectedMediaItems.length;

        if (!noOfUpdate){
            swal.fire({title: 'Error', text: "No media to delete."}).then(function(result) {
              return;
            });
        }
       
        $('#media_deletion_progress').modal({
            backdrop: 'static',
            keyboard: false
        });
        
        var itemCurrentlyProcessing = 1;
        $("#totalBulkRecored").html(noOfUpdate);
        $(".progress_status").html("Processing 0/" + noOfUpdate + " deletion. Please wait.");
        $('div#prgress_bar').width('0%');
        $('#progress_bar').show();

        $("#hidden_div_for_notification").html('');
		$("#hidden_div_for_notification").removeAttr('aria-live'); 
        $("#hidden_div_for_notification").attr({role:"status","aria-live":"polite"});   

        if (noOfUpdate){
            let p = Math.round((1 / noOfUpdate) * 100);
            $(".progress_status").html("Processing " + 1 + "/" + noOfUpdate+" deletion. Please wait.");
            $('div#prgress_bar').width(p + '%');

            document.getElementById('hidden_div_for_notification').innerHTML="Processing " + 1 + "/" + noOfUpdate+" deletion. Please wait.";
        }
        selectedMediaItems.forEach( function (media, index) {

            var media_id = media.value;
            let file_data = JSON.stringify({"action":"deleteAlbumMedia","album_id":albumid, "media_id":media_id});
             $.ajax({
                url: 'ajax_albums.php?albumDeleteAlbumMedia=1',
                type: "POST",
                data: {data: file_data,
                    'groupid': gid,
                    'chapterid': cid,
                    'channelid': chid
                },
                success : function(album_data_response) {

                    
                },
                error: function() {
                    swal.fire({title: 'Error',text:"Something went wrong. Please try again."}).then(function(result) {
                        albumMediaGalleryView(albumid,cid,chid);
                        closeAllActiveModal();
                    });
                }
            }).always( function fn() {
                itemCurrentlyProcessing++;               
                     
			        if (itemCurrentlyProcessing > noOfUpdate) {
                        $(".progress_status").html("<i class='fa fa-check-circle' aria-hidden='true'></i> Completed");
                       
                        setTimeout(function () {                          
                            $("#refresh_grallery").show();
                            $('#close_show_progress_btn').focus();
                            if (totalAlbumMedia == noOfUpdate){
                                swal.fire({title: 'Success', text: "All media deleted successfully."}).then(function(result) {
                                    getAlbums(gid,cid,chid);
                                    closeAllActiveModal();
                                    setTimeout(function () {
                                        $('#addAlbumBtn').focus();
                                    }, 100);
                                });
                            }
                            $(".swal2-confirm").focus();  
                                                        
                        document.getElementById('hidden_div_for_notification').innerHTML=" <?= gettext('Completed') ?>";

                        }, 250);
                        $("#close_show_progress_btn").show();
                    }else{
                        let p = Math.round(((itemCurrentlyProcessing) / noOfUpdate) * 100);
                        $(".progress_status").html("Processing " + (itemCurrentlyProcessing) + "/" + noOfUpdate+" bulk update");
                        $('div#prgress_bar').width(p + '%');

                        document.getElementById('hidden_div_for_notification').innerHTML="Processing " + (itemCurrentlyProcessing) + "/" + noOfUpdate+" bulk update";
                    }
            });
        });
    }

    function openChangeAlbumMediaModal(a,m,t,i){
        $("#albumidvalue").val(a);
        $("#currentValue").html(i);
        $("#change_order_media_id").val(m);
        $("#max_order_value").val(t);
       	$('#change_media_order').modal({
            backdrop: 'static',
            keyboard: false
        });
        $("#neworderValue").focus();
        $('#change_media_order').on('hidden.bs.modal', function (e) {         
	        $('.'+m).focus();
        })
    }

    function changeAlbumMediaPosition(){
        $(document).off('focusin.modal');
        var a =  $("#albumidvalue").val();
        var i = $("#currentValue").html();
        var m = $("#change_order_media_id").val();
        var t = $("#max_order_value").val();
        var n = $("#neworderValue").val();


        if (n =='' || !parseInt(n) || parseInt(n) > parseInt(t)){
            swal.fire({title: '<?= gettext('Error')?>',text:"<?= gettext('Please enter a valid number')?>",allowOutsideClick:false});
            return;
        }

        $.ajax({
            url: 'ajax_albums.php?changeAlbumMediaPosition=1',
            type: "POST",
            data: {'album_id':a,'media_id':m,'current_order_value':i,'new_order_value':n},
            success: function (response) {
                try {
                    let jsonData = JSON.parse(response);
                    swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
                        if (jsonData.status == 1){
                            $("#orderchangeform")[0].reset();
                            closeAllActiveModal();
                            albumMediaGalleryView("<?= $encAlbumId; ?>","<?= $encChapterId; ?>","<?= $encChannelId; ?>");

                            setTimeout(function() {
                                $("."+m).focus();
                            },500);
                        }
                    });
            
                } catch(e) {
                    swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false});
                }
            }
        });       

    }

$(".gallery-card-title-checkbox").keypress(function (event) {
    if (event.keyCode === 13) {
        $(this).click();
    }
});

$('#media_deletion_progress').on('shown.bs.modal', function () {
   $('#close_show_progress_btn').trigger('focus');
});
$(document).on('click','.confirm-dialog-btn-abort', function(){
        setTimeout(function () {
            $('#popConfirmBtn').focus();
        }, 100);
    });  



</script>
