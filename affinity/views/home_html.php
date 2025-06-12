<?php
$show_banner_component ??= true;
$show_discover_groups_btn ??= true;
$login_disclaimer_shown = Session::GetInstance()->login_disclaimer_shown;
?>
<style>

    li.nav-item.dropdown.show {
        background: none !important;
    }

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
        padding: 72px 0 0 10px;
        z-index: 2;
        position: relative;
        font-size: large;
        block-size: 150px;
    }
    .vv {
        padding: 0 5px !important;
        position: relative;
        line-height: 20px;
    }

    .content_center {
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .filter-button {
        margin-left: -4px;
        z-index: 2;
        background: white;
    }
    @media(max-width:768px) {
        .filter-button {
            margin-left: 0;
            margin-top: 5px;
        }
        span.input-group-append.filter-button > button {
            border-radius: 5px !important;
        }
    }

.filter-box button:focus-visible {
  outline: none;
  box-shadow: 0 0 2px 2px #000;
}

</style>
<main>
<div class="row">
    <?php if ($show_banner_component) { ?>
    <div class="row as content_center row-no-gutters" style="background:url(<?= $banner ? $banner : 'img/img.png'; ?>) no-repeat; background-size:cover; background-position:center;">
        <div class="container ">
            <div class="col-md-12 ">
               <?php if($banner_title){?> <h1 class="h-sp"><?= $banner_title; ?></h1> <?php } ?>
                <p class="pt-1"><?= $banner_subtitle; ?></p>

                <?php
                if($_ZONE && $_ZONE->val('hotlink_placement') == 'banner' && !empty($hotlink = $_COMPANY->getHotlinks())){
                ?>
                <div class="wizard">
                    <div class="wizard-inner">
                    <nav class="" aria-label="Secondary">
                        <center>
                            <ul class="nav nav-tabs non" role="tablist">
                                <?php for($h=0;$h<count($hotlink);$h++){ ?>
                                    <li>
                                        <?php if ($hotlink[$h]['link_type'] === '1') { ?>
                                            <a href="<?= $hotlink[$h]['link']; ?>" target="_blank" rel="noopener noreferrer" >
                                            <?php } else { ?>
                                            <a href="<?= $hotlink[$h]['link']; ?>" data-fancybox class="iframe" style="color:#fff;">
                                                <?php } ?>
                                                <span class="round-tab">
                                                <?php if($hotlink[$h]['image']){ ?>
                                                    <img src="<?= $hotlink[$h]['image']; ?>" alt="Link icon image" class="img-responsive hotlink-icon">
                                                <?php } else { ?>
                                                    <div class="hotlink-icon-blank"></div>
                                                <?php } ?>
                                                </span>
                                                <p class="hotlink"><?= $hotlink[$h]['title']; ?></p>
                                            </a>
                                    </li>
                                <?php	} 	?>
                            </ul>
                        </center>
                    </nav>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php } ?>
    <div id="main_section" class="container inner-background inner-background-tall">
        <div class="container-fluid">
            <div class="row">
                <?php if (count($groupCategoryRows) > 1) { ?>
                <div class="col-md-4 my-4" >
                    <select aria-label="<?= gettext('Show All');?>" class="form-control col-md-8" onchange="filterByGroupCategory(this.value)"  >
                        <option value="0"><?=gettext('Show All')?></option>
                        <?php foreach ($groupCategoryRows as $groupCategoryRow) { ?>
                            <option value="<?=$groupCategoryRow['categoryid']?>" <?= $group_category_id == $groupCategoryRow['categoryid'] ? 'selected' : ''; ?> ><?=$groupCategoryRow['category_name']?></option>
                        <?php  } ?>
                    </select>
                </div>
                <?php } else { ?>
                <div class="col-md-4">&nbsp;</div>
                <?php }  ?>
                <?php if ($show_discover_groups_btn) { ?>
                <div class="col-md-4" style="display: <?= $_COMPANY->getAppCustomization()['group']['homepage']['show_my_groups_option'] ? 'block' : 'none'; ?>">
                    <div role="tablist" class="discover-myerg-switch-button" >
                        <button tabindex="0" aria-selected="<?= ($landing_page == 0) ? 'true' : 'false' ?>" role="tab" type="link" id='btnone' class="home-tab btn btnone <?= ($landing_page == 0) ? 'activebtn' : 'inactivebtn' ?>" onclick="discoverGroups('<?= $group_category_id; ?>')">
                        <?= gettext('Discover'); ?>
                        </button>

                        <button tabindex="-1" role="tab" type="link" id='btntwo' aria-selected="<?= ($landing_page == 1) ? 'true' : 'false' ?>" class="home-tab btn btntwo <?= $landing_page == 1 ? 'activebtn' : 'inactivebtn' ?>" onclick="getMyGroups('<?= $group_category_id; ?>')">
                        <?= sprintf(gettext('My %s'),$_COMPANY->getAppCustomization()['group']['name-short-plural']); ?>
                        </button>
                    </div>
                </div>
                <?php } ?>
                <div class="col-md-4">&nbsp;</div>
            </div>
        </div>
    <?php if(!empty($allTags)){ ?>
        <div class="col-md-12 mt-0 pt-0 mb-3 filter-box">
            <small class="text-left">
            <button aria-expanded="false" aria-label="Filter by tags" class="btn-no-style" id="inittagfilter"><i  class="fa fa-solid fa-filter"> <?= gettext("Filter by tags"); ?></i> </button>
            </small>
            <div class="input-group mb-3 justify-content-center" id="tagfilteraction" style="display:none;">
                <select class="form-control py-2" id="group_tags" name="group_tags[]" style="width: 85%;" multiple>
                    <?php foreach($allTags as $tag){ ?>
                        <option value="<?= htmlspecialchars($tag['tagid']); ?>"><?= htmlspecialchars($tag['tag']); ?></option>
                    <?php } ?>
                </select>
                <span class="input-group-append filter-button">
                    <button class="btn btn-outline-primary rounded-right" type="button" onclick="filterGroupsByTags()">
                        <i class="fas fa-solid fa-filter"> <?= gettext("Filter")?></i>
                    </button>
                </span>
            </div>
        </div>
    <?php } ?>

        <?= $before_listing_html ?? '' ?>

        <div id="ajaxreplace">
            <!-- Discover or My Feeds data will display here -->
            <?php
                include(__DIR__ . "/templates/home_html.template.php");
            ?>
        </div>

    </div>
</div>
<!-- Container div for Datepicker & video tags store all html in it for accessibility -->
<div class="datepicker-and-video-tags-html-container"></div>
</main>
<script>
    $("#home-c").removeClass("active-1").addClass('home_nav');
    $("#home-a").removeClass("active-1").addClass('home_nav');
    $("#home-h").removeClass("home_nav").addClass('active-1');
    $("#home-s-icon").addClass('home_nav');
</script>
<script>
     $( "#inittagfilter" ).click(function() {
      $( "#tagfilteraction" ).slideToggle( "slow", function() {
        
            if($('#tagfilteraction').is(':visible'))
            {
                $("#inittagfilter").attr("aria-expanded", "true");
            }else{
                $("#inittagfilter").attr("aria-expanded", "false");
            }
      });

      $(".select2-search__field").attr({"aria-label":"<?= gettext('Filter by tags');?>" });
    });


    $(document).ready(function() {

        //var $ = jQuery.noConflict();
        $("a.iframe").fancybox({
            'maxWidth': 800,
            'maxHeight': 600,
            'fitToView': false,
            'width': '70%',
            'height': '80%',
            'closeClick': false,
            'openEffect': 'elastic',
            'closeEffect': 'elastic',
            'type': 'iframe'
        });
    });

</script>
<script>
    $(document).ready(function() {
        // Select2 tag
        $("#group_tags").select2({
            placeholder: "<?= gettext('Add or search tags'); ?>",
            allowClear: true,
            tags: false,
            "language": {
                "noResults": function(){
                    return "<?= gettext('No tags found to filter'); ?>";
                }
            },
        }).on('select2:opening', function(e) {
            $(this).data('select2').$dropdown.find(':input.select2-search__field').attr('placeholder', "<?= sprintf(gettext('Select Tags to filter %s'),$_COMPANY->getAppCustomization()['group']['name-short-plural']); ?>..")
        }).on('select2:selecting', function (e) {
            filterGroupsByTags();
        }).on('select2:unselecting', function (e) {
            filterGroupsByTags();
        });
    });

    function filterGroupsByTags(){
        setTimeout(() => {
            $(".activebtn").trigger("click");
        },200);
    }

</script>
<script type="text/javascript">
	$(document).ready(function(){
        <?php if (isset($_SESSION["ie11"]) && $_SESSION["ie11"] != false) {  $_SESSION["ie11"] = false; ?>
        Swal.fire({
          icon: "warning",
          title: "Upgrade your browser",
          html: "We no longer support Internet Explorer 11 and older<br>Please use Microsoft Edge, Google Chrome, Safari or Firefox for best user experience.",
          showConfirmButton: true,
          confirmButtonText: "Continue this session ..."
        }).then(function (result) {
        });
        <?php } ?>

        <?php if (!$login_disclaimer_shown && Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['LOGIN_FIRST'])) { ?>
            loadDisclaimerByHook('<?=$_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['LOGIN_FIRST']) ?>','<?=$_COMPANY->encodeId(0)?>', true,'<?= base64_encode('{}')?>');
        <?php
        } else {
            if (!$login_disclaimer_shown) { // Set login_disclaimer_shown to 1 if not already set.
                Session::GetInstance()->login_disclaimer_shown = 1;
            }
        ?>
            // Load Survey if
            loadSurveyModal("<?= $_COMPANY->encodeId(0); ?>");
        <?php } ?>
	});
</script>

<?php if (!empty($_GET['show_admin_content'])) { ?>

    <?php
    // If there is newsletter that we need to show... show it now.
    if (!empty($_SESSION['show_newsletter_id'])) {
        $enc_nid = $_COMPANY->encodeId($_SESSION['show_newsletter_id']);
        unset($_SESSION['show_newsletter_id']);
        ?>
        <script>
            $(document).ready(function () {
                previewNewsletter('<?= $_COMPANY->encodeId(0)?>', '<?= $enc_nid?>');
            });
        </script>
    <?php } ?>

    <?php
    // If there is announcement that we need to show... show it now.
    if (!empty($_SESSION['show_announcement_id'])) {
        $enc_nid = $_COMPANY->encodeId($_SESSION['show_announcement_id']);
        unset($_SESSION['show_announcement_id']);
        ?>
        <script>
            $(document).ready(function () {
                getAnnouncementDetailOnModal('<?= $enc_nid?>', '<?= $_COMPANY->encodeId(0)?>', '<?= $_COMPANY->encodeId(0)?>');
            });
        </script>
    <?php } ?>


    <?php
    // If there is Event that we need to show... show it now.
    if (!empty($_SESSION['show_event_id']) || isset($_GET['show_event_id'])) {
        if (!empty($_SESSION['show_event_id'])){
            $enc_nid = $_COMPANY->encodeId($_SESSION['show_event_id']);
            unset($_SESSION['show_event_id']);
        } else {
           $enc_nid = $_COMPANY->encodeId($_COMPANY->decodeId($_GET['show_event_id']));
        }
        ?>
        <script>
            $(document).ready(function () {
                removeUrlParam('show_event_id');
                getEventDetailModal('<?= $enc_nid?>', '<?= $_COMPANY->encodeId(0)?>', '<?= $_COMPANY->encodeId(0)?>');
            });
        </script>
    <?php } ?>

<?php } ?>

<script>
$(function() {                       
  $(".home-tab").click(function() { 
    $('.home-tab').attr('tabindex', '-1');
    $(this).attr('tabindex', '0');    
  });
});

$('.home-tab').keydown(function(e) {  
    if (e.keyCode == 39) {       
        $(this).next('.home-tab').focus();       
    }else if(e.keyCode == 37){       
        $(this).prev('.home-tab').focus(); 
    }
});


</script>