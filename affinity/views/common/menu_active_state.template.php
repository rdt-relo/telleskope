<script type="text/javascript">
    var handleLocationHash = function (isHashChangeEvent) {
        var hash = window.location.hash.substr(1);

<?php if ($canViewContent) { ?> // If user have permission to view Group content
        if (hash){
            if (isHashChangeEvent) {
                if (!hash.startsWith('circles/hashtags')) {
                    return;
                }
            }
           

            $(".innerMenu").removeClass("submenuActive");
            if (false){
            <?php if ($_COMPANY->getAppCustomization()['post']['enabled']) { ?>
            } else if (hash =='announcements'){
                getHome('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>');
                $(".getHome").addClass("submenuActive");
                
            <?php } ?>
            <?php if ($_COMPANY->getAppCustomization()['event']['enabled']){ ?>
            } else if (hash =='events'){
                getEvent('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>');
                $(".getEvent").addClass("submenuActive");
            <?php } ?>
            <?php if ($_COMPANY->getAppCustomization()['aboutus']['enabled']) { ?>
            } else if (hash =='about'){
                getAboutusTabs('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>');
                $(".getAboutus").addClass("submenuActive");
            <?php } ?>
            <?php if ($_COMPANY->getAppCustomization()['newsletters']['enabled']){ ?>
            } else if(hash =='newsletters'){
                getGroupChaptersNewsletters('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>',<?= date('Y')?>);
                $(".getGroupChaptersNewsletters").addClass("submenuActive");
            <?php } ?>
            <?php if ($_COMPANY->getAppCustomization()['discussions']['enabled']) { ?>
            }  else if (hash =='discussion'){
                getGroupDiscussions('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>');
                $(".getGroupDiscussions").addClass("submenuActive");
            <?php }  ?>
            <?php if ($_COMPANY->getAppCustomization()['albums']['enabled']) { ?>
            } else if (hash =='albums'){
                getAlbums('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>');
                $(".getAlbums").addClass("submenuActive");
            <?php }  ?>
            <?php if ($_COMPANY->getAppCustomization()['resources']['enabled']) { ?>
            } else if (hash =='resources'){
                getComonGroupResources('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>');
                $(".getComonGroupResources").addClass("submenuActive");
            <?php }  ?>
            <?php if ($_COMPANY->getAppCustomization()['teams']['enabled']) { ?>
            } else if (hash =='getMyTeams' || hash.indexOf('getMyTeams-') != -1 || hash.indexOf('getMyTeams/') != -1){
                if (hash.indexOf('getMyTeams-') != -1){
                    var hashArray  = hash.split('-');
                    if (hashArray.length == 3){
                        getTeamDetail('<?=$enc_groupid;?>',hashArray[1],hashArray[2]);
                    } else if(hashArray.length == 4) {
                        getTeamDetail('<?=$enc_groupid;?>',hashArray[1],hashArray[2],0,hashArray[3]);
                    }else {
                        getTeamDetail('<?=$enc_groupid;?>',hashArray[1]);
                    }
                }  else {
                    initMyTeamsContainer('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>',1);
                }
                $(".getMyTeams").addClass("submenuActive");
            <?php }  ?>
            } else if (hash =='join_leave'){
                document.location.hash = '';
                getFollowChapterChannel('<?=$enc_groupid;?>',2);
            } else if(hash =='announcementDetail'){
                <?php if ((basename($_SERVER['PHP_SELF']) !='viewpost.php')){ ?>
                    getHome('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>');
                <?php } ?>
                $(".getHome").addClass("submenuActive");
            } else if(hash =='eventDetail'){
                <?php if ((basename($_SERVER['PHP_SELF']) !='eventview.php')){ ?>
                    getEvent('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>');
                <?php } ?>
                $(".getEvent").addClass("submenuActive");
            } else if(hash =='discussionDetail'){
                <?php if ((basename($_SERVER['PHP_SELF']) !='viewdiscussion.php')){ ?>
                    getGroupDiscussions('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>');
                <?php } ?>
                $(".getGroupDiscussions").addClass("submenuActive");
            } else if(hash =='recognition'){
                getRecognitions('<?=$enc_groupid;?>','<?=$_COMPANY->encodeId(0) ?>',1);
                $(".getRecognitions").addClass("submenuActive");
            } 
            else if (hash.startsWith('circles/hashtags')) {
                closeAllActiveModal(); 
                $("#my_team_menu").trigger('click');
            
        <?php if($_COMPANY->getAppCustomization()['linked-group']['enabled']){ ?>
            }  else if(hash =='linkedGroups'){
                getOfficeRavenGroups('<?=$enc_groupid;?>');
                $(".getOfficeRavenGroups").addClass("submenuActive");
        <?php } ?>
            } else if(hash =='bookings'){
                getMyBookings('<?=$enc_groupid;?>');
                $(".getMyBookings").addClass("submenuActive");
            } 
            else { // Custom Tabs
                $('[data-id='+hash+'_li]').trigger("click");
                $('[data-id='+hash+']').trigger("click");
            }
        } else {
            <?php if ($_ZONE->val('group_landing_page') == 'about') { ?>
                getAboutusTabs('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>');
                $(".getAboutus").addClass("submenuActive");
            <?php } else if ($_ZONE->val('group_landing_page') == 'announcements' ) { ?>
                getHome('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>');
                $(".getHome").addClass("submenuActive");
            <?php } else if ($_ZONE->val('group_landing_page') == 'events' && $_COMPANY->getAppCustomization()['event']['enabled']) { ?>
                getEvent('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>');
                $(".getEvent").addClass("submenuActive");
            <?php } else { ?>
                getHome('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>');
                $(".getHome").addClass("submenuActive");
            <?php } ?>
            
        }

    <?php } else { ?> // Load Default about us page
        getAboutusTabs('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>');
                $(".getAboutus").addClass("submenuActive");
    <?php } ?>

        if (!isHashChangeEvent) {
        $(".menu-button").click(function(){ 
            setTimeout(() => {     
                $('.menu-button.active').removeClass('active');
                $('.menu-button').attr( 'aria-selected', 'false' );
                $('.menu-button').attr( 'tabindex', '-1' ); 
                $(this).addClass('active');
                if ($(".menu-button").hasClass("active") ) {
                    $(this).attr( 'aria-selected', 'true' );
                    $(this).attr( 'tabindex', '0' );                    
                }
            }, 500);
        });
        }

        if ($(".innerMenu").hasClass("submenuActive") ) {           
            $('.menu-button').attr('aria-selected', 'false'); 
            $('.submenuActive').find('.menu-button').attr('aria-selected', 'true');            
        }

    };

    $(document).ready(function () {  
        handleLocationHash(false);   
        $('.submenuActive').find('.menu-button').attr('aria-selected', 'true');  
        $('.submenuActive').find('.menu-button').attr( 'tabindex', '0' );         
    });

    window.onhashchange = function () {   
        handleLocationHash(true);
    };
</script>