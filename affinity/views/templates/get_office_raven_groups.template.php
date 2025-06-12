<style>
    .overlay {
        position: relative;
    }

    .overlay:before {
        position: absolute;
        content: "";
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0.4;
        border-radius: 5px;
    }

    .overlay:hover:before {
        opacity: 0.4;
    }

    .sp {
        padding: 85px 0 0 10px;
        z-index: 2;
        position: relative;
        font-size: large;
        block-size: 150px;
    }
    .vv {
        padding: 0 10px !important;
        position: relative;
    }
	.group-block {
        margin-bottom: 0px;
        padding: 5px !important;
    }

</style>

<div class="container inner-background">
    <div class="row row-no-gutters w-100">
        <div class="col-md-12">
            <div class="col-md-9 col-xs-12">
                <div class="inner-page-title">
                    <h1><?= gettext('Groups') .' - '. $group->val('groupname')?></h1>
                </div>
            </div>
            <div class="col-md-3 col-xs-12">
                <?php if ($_USER->isAdmin()) { ?>
                <div class="mt-4 pull-right">
                    <a class="btn-affinity btn-sm btn button-manage-padding confirm" title="<?= gettext('All existing group links will be reset. Do you want to continue?');?>" onclick="relinkOfficeRavenGroupLinkedGroups('<?=$encGroupId?>')"><?= gettext('Relink Groups')?></a>
                </div>
                <?php } ?>
            </div>
        </div>
        <hr class="lineb" >
        <?php
        $blocks = array(
            array('globalOnly'=>false),
            //array('globalOnly'=>true),
        );
        foreach ($blocks as $block) {
        ?>
       
        <div class="col-md-12 p-0 pt-4">
            <h5  class="mb-3 title-mobile" ><?= $block['globalOnly'] ? gettext("Global Groups") : sprintf(gettext("Local %s Groups & Chapters"),$group->val('groupname_short')); ?> </h5>
            <div class="col-12 col-sm-12 col-md-12" style="padding:0px;">
                <?php
                $i=0;
                foreach($localGroups as $localGroup) {
                    $localChapters = Arr::SearchColumnReturnColumnVal($linkedRows, $localGroup->id(), 'linked_groupid', 'linked_chapterids');
                    if (($block['globalOnly'] && $localChapters != 0) || (!$block['globalOnly'] && $localChapters == 0)) {
                        // This logic filters out global Groups from the regional groups.
                        continue; // Skip
                    }
                    $localChapters = explode(',', $localChapters);
                    $enChapterid = $_COMPANY->encodeId(0);
                    if (count($localChapters)){
                        $enChapterid = $_COMPANY->encodeId($localChapters[0]);
                    }
                ?> 
                    <?php if ($_ZONE->val('show_group_overlay')){ ?>
						<style>
							.red<?=$i;?>:before {background-color: <?=$localGroup->val('overlaycolor');?> !important;}
						</style>
					<?php } ?>
						<div class="col-xs-12 col-md-4 col-sm-4 group-block moblie-view">
							<a style='text-decoration:none' href="<?=$_COMPANY->getAppURL('affinities')?>detail?id=<?= $_COMPANY->encodeId($localGroup->val('groupid')); ?>&chapterid=<?= $enChapterid; ?>" target="_blank">
                                <div class="col-md-12 we overlay red<?= $i; ?>" style="border-radius:5px; max-height:175px;width:300px; background:url(<?= $localGroup->val('sliderphoto') ? $localGroup->val('sliderphoto') :  $localGroup->val('coverphoto'); ?>) no-repeat;<?php if(!$localGroup->val('sliderphoto')){ ?>background-size:cover;background-position: center center;<?php } ?>"
                                    ><!-- <img src="img/flag.png" class="fl"> -->
									<h3 class="sp">
									<?php if ($localGroup->val('groupicon')) { ?>
										<img class="icon-img" src="<?= $localGroup->val('groupicon'); ?>">
									<?php }else{ ?>
										<?= $localGroup->val('groupname'); ?>
									<?php } ?>
									</h3>
									
									<p class="col-md-6 vv">
										<?php if($localGroup->getGroupMembersCount()>0){ ?><?= $localGroup->getGroupMembersCount(); ?> <?= gettext('Members')?><?php }else{ echo "&nbsp;"; }?>
                                    </p>
                                    <p class="col-md-6 vv text-right">
                                        <?php 
                                            $l_chaptercount = $localGroup->getChapterCount();
                                        if($l_chaptercount>0){ echo $l_chaptercount; ?> <?= gettext('Chapters')?><?php } ?>
									</p>
								</div>
							</a>
						</div>				
				
                <?php
                    $i++;
                }
                if ($i==0){
                    echo '<br><br><br>-- '.gettext("No Groups found").' --<br><br><br>';
                }
                ?>
            </div> 
        </div>
        <?php } ?>
        </div>
    </div>
</div>
<script>
jQuery(document).ready(function() {
    jQuery(".confirm").popConfirm();
});
</script>