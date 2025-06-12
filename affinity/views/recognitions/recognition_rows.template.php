<style>
.recognitionfeed{box-shadow:0 4px 8px 0 rgb(0 0 0 / 20%),0 6px 20px 0 rgb(0 0 0 / 19%);border-radius:10px;padding:14px;min-height:150px;margin-bottom:15px;width:100%;float:left}.recognitionfeed .dropdown-toggle{border:none;background:no-repeat;color:#0e77b5;font-size:1.4rem;margin:0 8px}.recognitionfeed .dropdown-toggle::after{display:none}.recognitionfeed img{margin:8px}img.memberpic2:not(.skip-custom-style){margin-top:25px}.recognitionfeed .profile h5{font-size:1rem;margin:30px 0 0;color:#505050}.recognitionfeed .profile h6{font-size:.9rem}.recognitionfeed span{font-weight:500;font-size:.8rem}.recognitionfeed p{color:#505050;font-size:.8rem}.recognitionfeed label{color:var(--darkgrey);font-size:.7rem;font-weight:600;margin:0}.recognitionfeed .content h4{font-size:.9rem;margin:9px 10px 0 0}.profile-name{display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;width:200px}.profile.recognizedby h5{margin-top:0}.recognitionfeed h2{font-size:18px}.profile.recognizedby h3{margin-top:-26px;font-size:18px}h2.profile-name{margin-top:26px}@media (max-width:768px){.profile-name{width:100px!important}.memberpic{height:40px;width:40px}.recognitionfeed .profile h5{margin:20px 0 0}}
</style>
    <!-- start of IF for printing recognitions -->
    <?php if(!empty($recognitions)) { ?>
            <div class="row mb-3">
            <!-- start of loop for printing recognitions -->
            <?php 	for($i=0;$i<$max_iter;$i++) {
                $current_custom_fields = $recognitions[$i]['attributes'] ? json_decode($recognitions[$i]['attributes'],true) : array();
            ?>
            <div class="col-12">
            <div class="recognitionfeed">
                    <?php if ($_USER->canPublishOrManageContentInScopeCSV($recognitions[$i]['groupid']) || ($_USER->id() == $recognitions[$i]['createdby'])){ ?>
                <div class="d-flex flex-row-reverse" style="height: 31px;">
                    <div class="dropdown">
                        <a aria-expanded="false" role="button" aria-label="<?= $recognitions[$i]['firstname_by'].' '.$recognitions[$i]['lastname_by'] ;?> more options" href="javascript:void(0);" tabindex="0" class="dropdown-toggle"  data-toggle="dropdown" onclick="getRecognitionActionButton('<?= $_COMPANY->encodeId($recognitions[$i]['recognitionid']); ?>',this)"><i  class="fa fa-ellipsis-v" ></i></a> 
                        <ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton<?= $_COMPANY->encodeId($recognitions[$i]['recognitionid']); ?>" style="width: 250px; cursor: pointer;"></ul>
                    </div>
                </div>
                <?php } ?>
            <div>
                <div class="row">
                <?php if($recognitions[$i]['recognizedby'] == $recognitions[$i]['recognizedto'] && $recognitions[$i]['recognizedby'] != 0){ ?>

                    <div class="col-6 d-flex">
                        <?= User::BuildProfilePictureImgTag($recognitions[$i]['firstname_to'],$recognitions[$i]['lastname_to'], $recognitions[$i]['picture_to'],'memberpic', sprintf(gettext('%s Profile Picture'),$recognitions[$i]['firstname_to'])); ?>
                        <button aria-label="<?= $recognitions[$i]['firstname_to'].' '.$recognitions[$i]['lastname_to']; ?>" class="profile profile-name btn-no-style" onclick="viewRecognitionDetial('<?= $_COMPANY->encodeId($recognitions[$i]['recognitionid']); ?>')" onkeypress="viewRecognitionDetial('<?= $_COMPANY->encodeId($recognitions[$i]['recognitionid']); ?>')">
                            <?= $recognitions[$i]['firstname_to'].' '.$recognitions[$i]['lastname_to']; ?>
                        </button>
                    </div>
                    <div class="col-6">
                        <div class="profile">
                            <label><?= gettext("Recognized by"); ?></label>
                            <div class="profile recognizedby">
                                <p><?= gettext("Self") ?></p>
                            </div>
                            <p>
                                <small class="newgrey" style="color:#000;">
                                    <?php
                                    $datetime = $recognitions[$i]['recognitiondate'];
                                    echo $_USER->formatUTCDatetimeForDisplayInLocalTimezone($datetime, true, false, false);
                                    ?>
                                </small>
                            </p>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="col-6 d-flex">

                        <?php if(0) { /* Commented out on 09/05/2024 as the picture did not look great */ ?>
                        <?= User::BuildProfilePictureImgTag($recognitions[$i]['firstname_to'],$recognitions[$i]['lastname_to'], $recognitions[$i]['picture_to'],'memberpic', sprintf(gettext('%s Profile Picture'),$recognitions[$i]['firstname_to'])) ?>
                        <?php } ?>

                        <button aria-label="<?= $recognitions[$i]['firstname_to'].' '.$recognitions[$i]['lastname_to']; ?>" class="profile profile-name btn-no-style" onclick="viewRecognitionDetial('<?= $_COMPANY->encodeId($recognitions[$i]['recognitionid']); ?>')" onkeypress="viewRecognitionDetial('<?= $_COMPANY->encodeId($recognitions[$i]['recognitionid']); ?>')">
                            <?= $recognitions[$i]['firstname_to'].' '.$recognitions[$i]['lastname_to']; ?>
                        </button>

                    </div>
                    <div class="col-6 d-flex">
                    <?php
                    if($recognitions[$i]['recognizedby'] != 0){ 
                        $recognizedBy = User::GetUser($recognitions[$i]['recognizedby']) ?? User::GetEmptyUser();
                        if(!empty($recognizedBy)){
                    ?>

                       <?php if (0) { /* Commented on 09/05/2024 as pic did not look great */ ?>
                        <?= User::BuildProfilePictureImgTag($recognizedBy->val('firstname'),$recognizedBy->val('lastname'), $recognizedBy->val('picture'),'memberpic2', sprintf(gettext('%s Profile Picture'),$recognizedBy->val('firstname'))) ?>
                        <?php } ?>

                        <?php } } ?>
                        <div class="profile">
                            <label><?= gettext("Recognized by")?></label>
                            <div class="profile recognizedby">

                                <p>
                                <?php if($recognitions[$i]['recognizedby'] == 0){ ?>
                                    <?= $recognitions[$i]['recognizedby_name'] ?>
                                <?php } elseif(!empty($recognizedBy)){ ?>
                                    <?= $recognizedBy->getFullName() ?>
                                <?php } ?>
                               </p>

                            </div>
                            <p><small class="newgrey" style="color:#000;">
                            <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($recognitions[$i]['recognitiondate'],true,false,false);?>
                        </small></p>
                        </div>
                    </div>
                <?php } ?>
                </div >

                <?= (Recognition::Hydrate($recognitions[$i]['recognitionid'], $recognitions[$i]))->renderCustomFieldsComponent('v8') ?>

                <?php
                    $descriptioin = convertHTML2PlainText($recognitions[$i]['description']);
                    $readmmore = "";
                    if (strlen($descriptioin)>300){
                        $descriptioin = convertHTML2PlainText($recognitions[$i]['description'],300);
                        $readmmore = '<a style="text-decoration:underline;color:#0077B5 !important" >'.gettext("Read more").'</a>';
                    }
                ?>
                <div class="recognitioncls col-12"><p><?= $descriptioin.$readmmore;?></p></div>
              
<?php if ($_COMPANY->getAppCustomization()['recognition']['likes'] || $_COMPANY->getAppCustomization()['recognition']['comments']) { 
    $recognitionsPersonName =  $recognitions[$i]['firstname_to'].' '.$recognitions[$i]['lastname_to'];
    ?>
        <div class="row">
            <div class="col-sm-12">
                <div class="link-icons img-down">
                <?php if ($_COMPANY->getAppCustomization()['recognition']['likes']) {                   
                     ?>
                    <div id="x<?= $recognitions[$i]['recognitionid']; ?>" class="like-2">
                        <span  style="cursor:pointer;">
                            <a role="button" aria-label="<?= Recognition::GetLikeTotals($recognitions[$i]['recognitionid']); ?> <?=sprintf(gettext('like to %s Recognition Feed'),$recognitionsPersonName);?>" 
                            href="javascript:void(0);">
                                <i class="fa fa-thumbs-up fa-regular newgrey" title="<?= gettext('Like') ?>"></i>
                                <span class="gh1"><?= Recognition::GetLikeTotals($recognitions[$i]['recognitionid']); ?></span>
                            </a>
                        </span>
                    </div>
                    <?php } ?>
                    
                    <?php if ($_COMPANY->getAppCustomization()['recognition']['comments']) { ?>
                    <div class="review-2">
                        <a role="button" aria-label="<?= Recognition::GetCommentsTotal($recognitions[$i]['recognitionid']); ?> <?=sprintf(gettext('comments for %s Recognition Feed'),$recognitionsPersonName);?>" 
                        href="javascript:void(0);">
                            <i class="fa fa-regular fa-comment-dots newgrey" title="<?= gettext('Total Comments') ?>"></i>
                            <span class="gh1"><?= Recognition::GetCommentsTotal($recognitions[$i]['recognitionid']); ?></span>
                        </a>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } ?>
    </div>
    </div>
    </div>
            <?php 	} ?>
            <!-- end of loop for printing posts -->
            </div>
            <?php if ($show_more) { // End fragment with span month element to pass next month ?>
                <span style="display: none">_l_=<?=$show_more?></span>
             <?php } ?>
        <?php }else{ ?>
            <!-- Print no recognitions message -->
            <div class="container w6">
                <div class="col-sm-12 bottom-sp">
                    <br/>
                    <p style="text-align:center;margin-top:0px">Whoops!</p>
                    <p style="text-align:center;margin-top:-40px">
                        <img src="../image/nodata/no-discussion.png"  alt="" height="200px;"/>
                    </p>
                    <p style="text-align:center;margin-top:-40px;color:#767676;"><?= gettext("Looks like there aren't any recognitions yet"); ?></p>
                </div>
            </div>
        <?php } ?>
            <!-- end of if -->