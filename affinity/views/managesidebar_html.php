    <!-- Survey Analytics Stuff-->
    <script src="../vendor/js/surveyjs-1.11.2/vendor/typedarray.js"></script>
    <!--script src="https://polyfill.io/v3/polyfill.min.js"></script-->
    <script src="../vendor/js/surveyjs-1.11.2/vendor/plotly-latest.min.js"></script>
    <script src="../vendor/js/surveyjs-1.11.2/vendor/wordcloud2.js"></script>
    <link href="../vendor/js/surveyjs-1.11.2/survey.analytics.min.css" rel="stylesheet"/>
    <script src="../vendor/js/surveyjs-1.11.2/survey.analytics.min.js"></script>
<style>
    .tool-tip{
        display: block !important;   
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        text-align: left;
        max-width:185px;
    } 

    .sub-menu-title{
        padding:4px !important;
        color: inherit !important;
    }
    li.nav-item.dropdown.sub-menu-li.show {
        background: #0077b5 !important;
    }
    li.stage-two-menu a{
        color:#000 !important;
    }

    li.innerMenu div.stage-two-menu a, li.innerMenu div.stage-two-menu:hover a {
        color: #000 !important;
    }
    
    .sub-menu-li {
        padding: 2px 0!important;
    }

    .sub-menu-li:hover>.dropdown-menu {
        display: block;
    }

    .sub-menu-li>.dropdown-toggle:active {
        pointer-events: none;
    }
    .innerMenu-disabled{
        padding-top:14px;
        color : #ccc !important;
        cursor:no-drop !important;
    }
</style>
<?php
	$enc_groupid = $_COMPANY->encodeId($groupid);
	$canManageSomething = $_USER->canManageGroupSomething($groupid);
    $canCreateSomething = $_USER->canCreateContentInGroupSomething($groupid);
    $canPublishSomething = $_USER->canPublishContentInGroupSomething($groupid);
    $canBudgetSomething = $_USER->canManageBudgetGroupSomething($groupid);
    $canCreatePublishManageSomething = $canCreateSomething || $canPublishSomething || $canManageSomething;
    $helperMessageNotAvailable = gettext("This feature is disabled in this zone. Please contact Teleskope support to discuss options to enable this feature");
    $helperMessageNoPermissions = gettext("Your role does not have sufficient permissions to manage this feature");
    $helperMessage_ManageCheck = !$canManageSomething ? $helperMessageNoPermissions : $helperMessageNotAvailable;
    $helperMessage_CreateCheck = !$canCreateSomething ? $helperMessageNoPermissions : $helperMessageNotAvailable;
    $helperMessage_PublishCheck = !$canPublishSomething ? $helperMessageNoPermissions : $helperMessageNotAvailable;
    $helperMessage_CreatePublishManageCheck = !$canCreatePublishManageSomething ? $helperMessageNoPermissions : $helperMessageNotAvailable;
    $helperMessage_BudgetCheck = !$canBudgetSomething ? $helperMessageNoPermissions : $helperMessageNotAvailable;
?>


<div id="main_section" class="container vi w3 subnav px-3">
	<!-- <h3 class="manage-menu-heading"><?= Group::GetGroupName($groupid); ?> Administration Panel</h3> -->	
	<nav class="navbar navbar-expand-lg navbar-light" id="innerMenuBar" aria-label="Secondary">
		<div class="container-fluid" id="skipContent">
			<div id="manageNavbar" class="menu-container">
				<ul class="navbar-nav mr-auto manage-menu" id="manageMenuBar" role="tablist">

                    <li class="innerMenu" id="manageDashboard_li" role="none">
                        <button role="tab" type="button" class="btn-no-style menu-button tool-tip"  data-toggle="tooltip" title="<?= gettext("Dashboard"); ?>" id="manageDashboard" onclick="manageDashboard('<?=$enc_groupid;?>')">
                            <?= gettext("Dashboard"); ?>
                        </button>
                    </li>

                    <?php if($canManageSomething) { ?>
					<li class="innerMenu" id="manageAllReports_li" role="none">
                        <button role="tab" type="button" class="btn-no-style menu-button tool-tip"  data-toggle="tooltip" title="<?= gettext("Reports"); ?>" id="manageAllReports" onclick="getAllReports('<?=$enc_groupid;?>')">
                            <?= gettext("Reports"); ?>
                        </button>
                    </li>
                    <?php } else{ ?>
                    <li class="innerMenu-disabled" tabindex="0" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= $helperMessage_ManageCheck; ?>" role="none">
                        <button role="tab" type="button" class="btn-no-style menu-button nav-link disabled" tabindex="-1"  data-toggle="tooltip" title="<?= gettext("Reports"); ?>" class="nav-link disabled">
                            <?= gettext("Reports"); ?>
                        </button>
                    </li>
                    <?php } ?>
                    
                    <?php if($canManageSomething) { ?>
                    <li class="innerMenu" id="getGroupMembersTab_li" role="none">
                        <button role="tab" type="button" class="btn-no-style menu-button tool-tip"  id="getGroupMembersTab" onclick="getGroupMembersTab('<?=$enc_groupid;?>')">
                            <?= gettext("Users"); ?>
                        </button>
                    </li>
                    <?php } else { ?>
                    <li class="innerMenu-disabled"  tabindex="0" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= $helperMessageNoPermissions; ?>" role="none">
                        <button role="tab" type="button" class="btn-no-style menu-button nav-link disabled" tabindex="-1">
                            <?= gettext("Users"); ?>
                        </button>
                    </li>
                    <?php } ?>

                    <?php if($_COMPANY->getAppCustomization()['aboutus']['enabled'] && $canManageSomething) { ?>
					<li class="innerMenu" id="updateAboutUsData_li" role="none">
                        <button role="tab" type="button" class="btn-no-style menu-button tool-tip" data-toggle="tooltip" title="<?= gettext("About Us"); ?>" id="updateAboutUsData" onclick="updateAboutUsData('<?=$enc_groupid;?>')">
                            <?= gettext("About Us"); ?>
                        </button>
                    </li>
                    <?php } else{ ?>
                    <li class="innerMenu-disabled" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= $helperMessage_ManageCheck; ?>" role="none">
                        <button role="tab" type="button" class="btn-no-style menu-button nav-link disabled" tabindex="-1" data-toggle="tooltip" title="<?= gettext("About Us"); ?>">
                            <?= gettext("About Us"); ?>
                        </button>
                    </li>
                    <?php } ?>

                    <?php if ($_COMPANY->getAppCustomization()['budgets']['enabled'] && $_USER->canManageBudgetGroupSomething($groupid)) { ?>
                    <li class="innerMenu" id="manageBudgetExpSection_li" role="none">
                        <button role="tab" type="button" class="btn-no-style menu-button tool-tip"  id="manageBudgetExpSection" onclick="manageBudgetExpSection('<?=$enc_groupid;?>')">
                            <?= gettext("Budget"); ?>
                        </button>
                    </li>
                    <?php } else{ ?>
                    <li class="innerMenu-disabled" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= $helperMessage_BudgetCheck; ?>" role="none">
                        <button role="tab" type="button" class="btn-no-style menu-button nav-link disabled tool-tip" tabindex="-1">
                            <?= gettext("Budget"); ?>
                        </button>
                    </li>
                    <?php } ?>

                    <li class="innerMenu nav-item dropdown sub-menu-li submenu-list" id="emails_li" role="none">
                        <button role="tab" type="button" class="btn-no-style menu-button nav-link dropdown-toggle sub-menu-title" id="navbarDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <?= gettext("Email"); ?> &#9662;
                        </button>
                        <div class="dropdown-menu stage-two-menu" aria-labelledby="navbarDropdown">
                        <ul role="menu">
                            <li role="none">
                                <?php if($_COMPANY->getAppCustomization()['communications']['enabled'] && $canManageSomething) { ?>
                                <button role="tab" type="button" class="btn-no-style menu-button dropdown-item tool-tip" id="manageCommunicationsTemplates" onclick="manageCommunicationsTemplates('<?=$enc_groupid;?>')" >
                                    <?= gettext("Automated Email");?>
                                </button>
                                <?php } else{ ?>
                                <button role="tab" type="button" class="btn-no-style menu-button innerMenu-disabled tool-tip disabled" tabindex="-1" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= $helperMessage_ManageCheck; ?>" >
                                    <span class="gray"><?= gettext("Automated  Email"); ?></span>
                                </button>
                                <?php } ?>
                            </li>

                            <li role="none">
                             <?php if($_COMPANY->getAppCustomization()['messaging']['enabled'] && $canManageSomething){
                                  if(!$_COMPANY->getAppCustomization()['messaging']['restrict_to_admin_only']){ ?>
                                <button role="tab" type="button" class="btn-no-style menu-button dropdown-item tool-tip" id="groupMessageList" onclick="groupMessageList('<?=$enc_groupid;?>',1)" >
                                    <?= gettext("Direct Email");?>
                                </button>
                                    <?php } } else{ ?>
                                <button role="tab" type="button" class="btn-no-style menu-button innerMenu-disabled tool-tip disabled"  tabindex="-1" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= $helperMessage_ManageCheck; ?>" >
                                    <span class="gray"><?= gettext("Direct Email"); ?></span>
                                </button>
                                <?php } ?>
                            </li>
                        </ul>
                        </div>
                    </li>

                    <li class="innerMenu nav-item dropdown sub-menu-li " id="contents_li" role="none">
                        <button role="tab" type="button" class="btn-no-style menu-button nav-link dropdown-toggle sub-menu-title" id="navbarDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <?= gettext("Content"); ?> &#9662;
                        </button>

                        <div class="dropdown-menu stage-two-menu" aria-labelledby="navbarDropdown">
                            <ul role="menu">
                                <li role="none">
                                    <?php if($_COMPANY->getAppCustomization()['post']['enabled'] && $canCreatePublishManageSomething) { ?>
                                    <button role="tab" type="button" class="btn-no-style menu-button dropdown-item tool-tip" id="manageGlobalAnnouncements"    onclick="manageGlobalAnnouncements('<?=$enc_groupid;?>')">
                                        <?= Post::GetCustomName(true);?>
                                    </button>
                                    <?php } else{ ?>
                                    <button role="tab" type="button" class="btn-no-style menu-button innerMenu-disabled tool-tip disabled"  tabindex="-1" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= $helperMessage_CreatePublishManageCheck; ?>" >
                                        <span class="gray"><?= Post::GetCustomName(true);?></span>
                                    </button>
                                    <?php } ?>
                                </li>

                                <li role="none">

                                    <?php if($_COMPANY->getAppCustomization()['event']['enabled'] && $canCreatePublishManageSomething) { ?>
                                    <button role="tab" type="button" class="btn-no-style menu-button dropdown-item tool-tip"  id="manageGlobalEvents" onclick="manageGlobalEvents('<?=$enc_groupid;?>')">
                                        <?= gettext("Events"); ?>
                                    </button>
                                    <?php } else{ ?>
                                    <button role="tab" type="button" class="btn-no-style menu-button innerMenu-disabled tool-tip disabled" tabindex="-1" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= $helperMessage_CreatePublishManageCheck; ?>" >
                                        <span class="gray"><?= gettext("Events"); ?></span>
                                    </button>
                                    <?php } ?>
                                </li>

                                <li role="none">
                                    <?php if($_COMPANY->getAppCustomization()['newsletters']['enabled'] && $canCreatePublishManageSomething) { ?>
                                    <button role="tab" type="button" class="btn-no-style menu-button dropdown-item tool-tip"  id="getGroupNewsletters"  onclick="getGroupNewsletters('<?=$enc_groupid;?>')">
                                        <?= gettext("Newsletters"); ?>
                                    </button>
                                    <?php } else{ ?>
                                    <button role="tab" type="button" class="btn-no-style menu-button innerMenu-disabled tool-tip disabled"  tabindex="-1" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= $helperMessage_CreatePublishManageCheck; ?>" >
                                        <span class="gray"><?= gettext("Newsletters"); ?></span>
                                    </button>
                                    <?php } ?>
                                </li>
                            </ul>
                        </div>
                    </li>
				   
                    <li class="innerMenu nav-item dropdown sub-menu-li" id="engagements_li" role="none">

                        <button role="tab" type="button" class="btn-no-style menu-button nav-link dropdown-toggle sub-menu-title" id="navbarDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?= gettext("Engagement"); ?> &#9662;
                        </button>

                        <div class="dropdown-menu stage-two-menu" aria-labelledby="navbarDropdown">
                        <ul role="menu">
                            <li role="none">
                                <?php if ($_COMPANY->getAppCustomization()['surveys']['enabled'] && $canManageSomething) { ?>
                                <button role="tab" type="button" class="btn-no-style menu-button dropdown-item tool-tip" id="getGroupSurveys" onclick="getGroupSurveys('<?=$enc_groupid;?>')">
                                    <?= gettext("Surveys"); ?>
                                </button>
                                <?php } else{ ?>
                                <button role="tab" type="button" class="btn-no-style menu-button innerMenu-disabled tool-tip disabled"  tabindex="-1" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= $helperMessage_ManageCheck; ?>" >
                                    <span class="gray"><?= gettext("Surveys"); ?></span>
                                </button>
                                <?php } ?>
                            </li>

                            <li role="none">
                                <?php if ($_COMPANY->getAppCustomization()['discussions']['enabled'] && $canManageSomething) { ?>
                                <button role="tab" type="button" class="btn-no-style menu-button dropdown-item" id="manageGroupDiscussions" onclick="manageGroupDiscussions('<?=$enc_groupid;?>')">
                                    <?= gettext("Discussions"); ?>
                                </button>
                                <?php } else{ ?>
                                <button role="tab" type="button" class="btn-no-style menu-button innerMenu-disabled tool-tip disabled"  tabindex="-1" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= $helperMessage_ManageCheck; ?>" >
                                    <span class="gray"><?= gettext("Discussions"); ?></span>
                                </button>
                                <?php } ?>
                            </li>

                            <li role="none">
                                <?php if ($_COMPANY->getAppCustomization()['recognition']['enabled'] && $_USER->canManageGroup($groupid)) { ?>
                                <button role="tab" type="button" class="btn-no-style menu-button dropdown-item tool-tip"  id="manageRecognitions" onclick="manageRecognitions('<?=$enc_groupid;?>')">
                                    <?= Recognition::GetCustomName(true); ?>
                                </button>
                                <?php } else{ ?>
                                <button role="tab" type="button" class="btn-no-style menu-button innerMenu-disabled tool-tip disabled" tabindex="-1" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= ($_COMPANY->getAppCustomization()['recognition']['enabled']) ? $helperMessageNoPermissions : $helperMessageNotAvailable; ?>" >
                                    <span class="gray"><?= gettext("Recognitions"); ?></span>
                                </button>
                                <?php } ?>
                            </li>
                        </ul>
                        </div>
                    </li>

                    <?php if (($_COMPANY->getAppCustomization()['teams']['enabled']) && $_USER->canManageGroup($groupid)) { ?>

                        <?php if (!$group->isTeamsModuleEnabled()) { ?>
                        <li role="none">
                            <button role="tab" type="button" class="btn-no-style menu-button innerMenu-disabled tool-tip disabled"  tabindex="-1" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= gettext("Not enabled") ?>" >
                                <?= Team::GetTeamCustomMetaName($group->getTeamProgramType(),1); ?> &#9662;
                            </button>
                        </li>

                        <?php } else { ?>
                        <li role="none" class="innerMenu nav-item dropdown sub-menu-li" id="getManageTeamsContainer_li">
                        <button role="tab" type="button" class="btn-no-style menu-button nav-link dropdown-toggle sub-menu-title" id="navbarDropdownTeam" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <?= Team::GetTeamCustomMetaName($group->getTeamProgramType(),1); ?> &#9662;
                        </button>

                            <div class="dropdown-menu stage-two-menu" aria-labelledby="navbarDropdownTeam">
                            <ul role="menu">
                                <li role="none">
                            <?php if ($group->isTeamsModuleEnabled() && ($_USER->canManageGroup($groupid) || $_USER->canManageGroupSomeChapter($groupid))) { ?>
                                
                                <button role="tab" type="button" class="btn-no-style menu-button dropdown-item tool-tip" id="getManageTeamsContainer" onclick="getManageTeamsContainer('<?=$enc_groupid;?>')"><?= sprintf(gettext("Manage %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1)); ?> </button>
                            
                                <?php } ?>
                                </li>
                                <li role="none">
                                <button role="tab" type="button" class="btn-no-style menu-button dropdown-item tool-tip" id="manageTeamsConfiguration" onclick="manageTeamsConfiguration('<?=$enc_groupid;?>')"><?= sprintf(gettext("Configuration"),''); ?> </button>
                            </li>
                            </ul>
                                
                            </div>
                        </li>
                        <?php } ?>

                    <?php } ?>
                
                    <?php if ($_COMPANY->getAppCustomization()['resources']['enabled']) { ?>
                    <li role="none" class="innerMenu" id="manageLeadResources_li">
                        <button role="tab" type="button" data-toggle="tooltip" title="<?= gettext("Documents"); ?>" class="btn-no-style menu-button tool-tip" id="manageLeadResources" onclick="manageLeadResources('<?=$enc_groupid;?>')">
                            <?= gettext("Documents"); ?>
                        </button>
                    </li>
                    <?php } else { ?>
                    <li role="none" class="innerMenu-disabled" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= $helperMessageNotAvailable; ?>" >
                        <button role="tab" type="button"  data-toggle="tooltip" class="btn-no-style menu-button nav-link tool-tip disabled" tabindex="-1" title="<?= gettext("Documents"); ?>" >
                            <?= gettext("Documents"); ?>
                        </button>
                    </li>
                    <?php } ?>

                    <?php if (($_COMPANY->getAppCustomization()['booking']['enabled']) && $_USER->canManageGroup($groupid)) { ?>
                        <li role="none" class="innerMenu nav-item dropdown sub-menu-li" id="getManageBookingConfigurationContainer_li">
                            <button role="tab" type="button" class="btn-no-style menu-button nav-link dropdown-toggle sub-menu-title" id="navbarDropdownTeam" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?= Group::GetBookingCustomName(true); ?> &#9662;
                            </button>
                            <div class="dropdown-menu stage-two-menu" aria-labelledby="navbarDropdownTeam">
                                <ul role="menu">
                                    <li role="none">
                                    
                                    <button role="tab" type="button" class="btn-no-style menu-button dropdown-item tool-tip" id="getManageBookingConfigurationContainer" onclick="getManageBookingConfigurationContainer('<?=$enc_groupid;?>')"><?= gettext('Configuration'); ?> </button>
                                    </li>
                                    <li role="none">
                                    <button role="tab" type="button" class="btn-no-style menu-button dropdown-item tool-tip" id="getManageBookingContainer" onclick="getManageBookingContainer('<?=$enc_groupid;?>')"><?= gettext('Bookings'); ?></button>
                                </li>
                                </ul>
                                    
                            </div>
                        </li>
                           

                    <?php } ?>

				</ul>
			</div>
		</div>
	</nav>	
</div>

<script>
    $('.innerMenu-disabled').popover({
        trigger: 'hover'
    })

    // Add active class to the current button (highlight it)
    var header = document.getElementById("innerMenuBar");
    var btns = header.getElementsByClassName("innerMenu");
    var manage_active = localStorage.getItem("manage_active");
    var first_stage_menu = ['manageDashboard','mangeGroupLeads','updateAboutUsData','manageBudgetExpSection','manageAllReports'];
    var engagements_menu = ['getGroupSurveys','manageGroupDiscussions','manageLeadResources','manageRecognitions'];
    var emails_menu = ['manageCommunicationsTemplates','groupMessageList'];
    var team_menu = ['getManageTeamsContainer','manageTeamsConfiguration'];
    var booking_menu = ['getManageBookingConfigurationContainer','getManageBookingContainer'];
    //$("#manageMenuBar").children().removeClass(" submenuActive");
    // $( "#"+manage_active ). addClass(' submenuActive')
    // Initialize submenuActive class
    if (first_stage_menu.includes(manage_active)){
        let manageDashboard_li = document.getElementById("manageDashboard_li");
        manageDashboard_li.className += " submenuActive";
    } else if(engagements_menu.includes(manage_active)) {
        let engagements_li = document.getElementById("engagements_li");
        engagements_li.className += " submenuActive";
    } else if(emails_menu.includes(manage_active)){
        let emails_li = document.getElementById("emails_li");
        emails_li.className += " submenuActive";
    } else if(team_menu.includes(manage_active)) {
        let teams_li = document.getElementById("getManageTeamsContainer_li");
        teams_li.className += " submenuActive";
    } else if(booking_menu.includes(manage_active)) {
        let booking_li = document.getElementById("getManageBookingConfigurationContainer_li");
        booking_li.className += " submenuActive";
    } else {
        let contents_li = document.getElementById("contents_li");
        contents_li.className += " submenuActive";
    }

    for (var i = 0; i < btns.length; i++) {
        btns[i].addEventListener("click", function () {
            var current = document.getElementsByClassName("submenuActive");
            current[0].className = current[0].className.replace(" submenuActive", "");
            this.className += " submenuActive";
        });
	}

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

    $('.menu-button').keydown(function(e) {  
        if (e.keyCode == 39) {       
            $(this).parent().next().find(".menu-button").focus();       
        }else if(e.keyCode == 37){       
            $(this).parent().prev().find(".menu-button").focus();  
        }
    });

    $('.dropdown-item').keydown(function(e) { 
        setTimeout(() => {     
            $('.submenuActive .sub-menu-title').attr('tabindex', '0');
        }, 1000);
    });
    
    $('.dropdown-item').on('click', function (e) {
        setTimeout(() => {     
            $('.submenuActive .sub-menu-title').attr('tabindex', '0');
        }, 1000);
    });   
</script>