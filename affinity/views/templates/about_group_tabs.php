<style>
    html {
        scroll-behavior: smooth;
    }
    .container.a4 {
        padding: 30px 20px;
    }
    figure{
		text-align: center !important;
	}
    .img-fluid{
        max-width: none !important;
    }
    a.view-member-link:hover {
    text-decoration: underline;
}
</style>

<div class="container inner-background">
    <div class="row row-no-gutters">
        <div class="col-md-12 p-0">
            <div class="col-md-12 col-xs-12">
                <div class="inner-page-title">
                    <h1><?php echo $documentTitle = gettext('About Us'). ' - '. $group->val('groupname'); ?></h1>
                </div>
            </div>
        </div><hr class="lineb" >
        <!-- In Group Aboutus section -->
        <div class="col-md-12 p-0">
        <?php
            $chaptersCount = $group->getChapterCount();
            $totalChannels = $group->getChannelCount();
            $selChapterid = 0;
            $selChannelid = 0;
            $selectedChapterId = $_COMPANY->encodeId(0);
            $selectedChannelId = $_COMPANY->encodeId(0);


            if (isset($_GET['chapterid'])){
                $selChapterid = $_COMPANY->decodeId($_GET['chapterid']);
                $selectedChapterId  = $_COMPANY->encodeId($selChapterid);
            }

            if (isset($_GET['channelid'])){
                $selChannelid = $_COMPANY->decodeId($_GET['channelid']);
                $selectedChannelId = $_COMPANY->encodeId($selChannelid);
            }

    ?>
        <div class="row">
            <?php if (!$canViewContent) { ?>
            <div class="col-12 alert-warning p-3 mx-0">
                <p class="text-center"><b><?= sprintf(gettext('This is a private %1$s. Only members can view the content.'), $_COMPANY->getAppCustomization()['group']['name-short']); ?></b></p>
                <p class="text-center py-2" style="font-size: small;"><?= sprintf(gettext(' If you recently joined, your access might take a few moments. Try reloading the page after a few seconds to see the content.')); ?></p>
            </div>
            <?php } ?>

            <div class="col-md-12 col-xs-12">
                <div class="inner-page-title">
                    <ul class="nav nav-tabs about-nav-tabs" role="tablist">
                        <li class="nav-item" role="none">
                            <a aria-selected="true" role="tab" tabindex="0" class="nav-link inner-page-nav-link active" id="aboutusTab" data-toggle="tab" href="#aboutGroup" onclick="getAboutus('<?= $_COMPANY->encodeId($groupid); ?>','<?= $selectedChapterId; ?>','<?= $selectedChannelId; ?>')"><?= sprintf(gettext('About the %s'), $_COMPANY->getAppCustomization()['group']['name-short']); ?></a>
                        </li>
                        <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && $chaptersCount>0) { ?>
                        <li class="nav-item" role="none">
                            <a aria-selected="false" role="tab" tabindex="-1" data-toggle="tab" id="chaptersTab" class="nav-link inner-page-nav-link " href="javascript:void(0);" onclick="getGroupChaptersAboutUs('<?= $_COMPANY->encodeId($groupid); ?>','<?= $selectedChapterId; ?>')" ><?=$_COMPANY->getAppCustomization()['chapter']['name-short-plural']; ?></a>
                        </li>
                        <?php } ?>
                        <?php if ($_COMPANY->getAppCustomization()['channel']['enabled'] && $totalChannels>0) { ?>
                        <li class="nav-item" role="none">
                            <a aria-selected="false" role="tab" tabindex="-1" data-toggle="tab" id="channelsTab" class="nav-link inner-page-nav-link " href="javascript:void(0);" onclick="getGroupChannelsAboutUs('<?= $_COMPANY->encodeId($groupid); ?>','<?= $selectedChannelId; ?>')" ><?=$_COMPANY->getAppCustomization()['channel']['name-short-plural']; ?></a>
                        </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
            <div class="tab-content">          
                <div class="tab-pane active" id="AboutUS"> 
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    <?php if($selChapterid>0){ ?>
        $('#chaptersTab').trigger('click');
    <?php } else if($selChapterid == 0 &&$selChannelid>0 ) { ?>
        $('#channelsTab').trigger('click');
    <?php } else{ ?>
        $('#aboutusTab').trigger('click');
    <?php } ?>

    updatePageTitle('<?= addslashes(sprintf(gettext('%1$s | %2$s'), $documentTitle, $_COMPANY->val('companyname')));?>');

$(function() {                       
	$(".inner-page-nav-link").click(function() { 
	  $('.inner-page-nav-link').attr('tabindex', '-1');
	  $(this).attr('tabindex', '0');    
	});
  });
  
  $('.inner-page-nav-link').keydown(function(e) {  
	  if (e.keyCode == 39) {       
		  $(this).parent().next().find(".inner-page-nav-link:last").focus();       
	  }else if(e.keyCode == 37){       
		  $(this).parent().prev().find(".inner-page-nav-link:last").focus();  
	  }
  });

</script>
