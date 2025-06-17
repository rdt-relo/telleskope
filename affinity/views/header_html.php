<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php if($_COMPANY->getAppCustomization()['mobileapp']['custom']['enabled']){ ?>
        <!-- Start SmartBanner configuration -->
        <meta name="smartbanner:title" content="<?= $_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_name'];  ?>">
        <meta name="smartbanner:author" content="Teleskope, LLC">
        <meta name="smartbanner:price" content="FREE">
        <meta name="smartbanner:price-suffix-apple" content=" - On the App Store">
        <meta name="smartbanner:price-suffix-google" content=" - In Google Play">
        <meta name="smartbanner:icon-apple" content="img/appicon/192x192.png">
        <meta name="smartbanner:icon-google" content="img/appicon/192x192.png">
        <meta name="smartbanner:button" content="VIEW">
        <meta name="smartbanner:button-url-apple" content="<?= $_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_ios_url']; ?>">
        <meta name="smartbanner:button-url-google" content="<?= $_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_android_url']; ?>">
        <meta name="smartbanner:enabled-platforms" content="android,ios">
        <meta name="smartbanner:close-label" content="Close">
        <meta name="smartbanner:hide-ttl" content="86400000">
        <meta name="smartbanner:custom-design-modifier" content="ios">
        <meta name="smartbanner:exclude-user-agent-regex" content="iPad">
<!-- End SmartBanner configuration -->
<?php } ?>


    <link rel="icon" type="image/png" sizes="96x96" href="<?= Url::GetFavIconUrl(); ?>">

    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title><?= $htmlTitle ?? ucfirst(pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME)).'-'.$_COMPANY->val('companyname') ?></title>
    <!-- Bootstrap -->
    <!--link href="./css/stylesheet.css" rel="stylesheet"-->
    <link href="../vendor/js/bootstrap-4.4.1/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="../vendor/fonts/fontawesome-free-5.12.0-web/css/all.min.css" rel="stylesheet">
    <link href="../vendor/js/datatables-2.1.8/datatables.min.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'" >
    <?php 
    $current_page = basename($_SERVER['PHP_SELF']);
    if($current_page === 'calendar.php'){ ?>
    <link href="../vendor/js/bootstrap-multiselect-1.1.2/dist/css/bootstrap-multiselect-latest.css" rel="stylesheet">
    <?php }else{?>
    <link href="../vendor/js/bootstrap-multiselect/dist/css/bootstrap-multiselect.css" rel="stylesheet">
    <?php } ?>

    <link href="../vendor/js/jquery-ui-1.14.0/themes/ui-lightness/jquery-ui.min.css" rel="stylesheet">
    <link href="./css/hems.css?<?=REL_HASH?>" rel="stylesheet">
    <!-- Teleskope styles, comes after bootstrap from parent folder -->
    <link href="../css/teleskope.css" rel="stylesheet">
    <!-- User Application specific style guides from project folder -->
    <link href="./css/affinity.css?<?=REL_HASH?>" rel="stylesheet">

    <!-- Customer/Tenant overrides for fonts any -->
    <?php if (!empty($_COMPANY->getStyleCustomization()['override_fonts_css_url'])) { ?>
    <link href="<?=$_COMPANY->getStyleCustomization()['override_fonts_css_url']?>" rel="stylesheet">
    <?php } ?>

    <!-- Bootstrap -->
    <script src="../vendor/js/jquery-3.5.1/dist/jquery.min.js"></script>
    <script src="../vendor/js/jquery-ui-1.14.0/jquery-ui.min.js"></script>
    <script src="../vendor/js/bootstrap-4.4.1/dist/js/bootstrap.bundle.min.js"></script>
    <?php if($current_page === 'calendar.php'){ ?>
        <script src="../vendor/js/bootstrap-multiselect-1.1.2/dist/js/bootstrap-multiselect-latest.js"></script>
    <?php }else{?>
        <script src="../vendor/js/bootstrap-multiselect/dist/js/bootstrap-multiselect.js"></script>
    <?php }?>
    <script src="../vendor/js/popper-1.16.0.min.js"></script>
    <!-- settings for popconfirm to work with bootstrap 3.4.1+, should be immediately after bootstrap -->
    <script type="text/javascript">
        $.fn.tooltip.Constructor.Default.whiteList.p = ['style'];
        $.fn.tooltip.Constructor.Default.whiteList.button = [];
    </script>
    <script defer type="text/javascript" src="../vendor/js/popconfirm-0.4.3/jquery.popconfirm.tele.2023.11.15.js"></script>

    <!--<script src="../vendor/js/initial-0.2.0/dist/initial.min.js"></script>-->
    <script src="../vendor/js/initial-0.2.0/dist/initial.teleskope.min.js"></script>
    
    <script defer src="../vendor/js/datatables-2.1.8/datatables.min.js"></script>

    <script src="./js/jPushMenu.js"></script>

    <!-- Chart Js  -->
    <link href="../vendor/js/chartjs-2.9.4/dist/Chart.min.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <script defer src="../vendor/js/chartjs-2.9.4/dist/Chart.min.js"></script>
    <script defer src="../vendor/js/chartjs-plugin-datalabels-0.7.0/dist/chartjs-plugin-datalabels.min.js"></script>
	<!-- End ChartJs -->

    <!-- select2 -->
    <link href="../vendor/js/select2-4.0.12/dist/css/select2.min.css" rel="stylesheet" />
    <script defer src="../vendor/js/select2-4.0.12/dist/js/select2.min.js"></script>
    <!-- end of select 2 -->

    <!-- revolvapp css -->
    <link href="../vendor/js/revolvapp-2-3-10/css/revolvapp.min.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'"/>
    <link href="../vendor/js/revolvapp-2-3-10/plugins/variable/variable.min.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'" />

    <!-- revolvapp js -->
    <script defer src="../vendor/js/revolvapp-2-3-10/revolvapp-tele-2023-11-15.min.js"></script>
    <script defer src="../vendor/js/revolvapp-2-3-10/plugins/variable/variable.min.js"></script>
    <script defer src="../vendor/js/revolvapp-2-3-10/plugins/reorder/reorder.min.js"></script>
    <script defer src="../vendor/js/revolvapp_config/revolvapp_config.js"></script>
    <script defer src="../vendor/js/revolvapp_config/lang/<?= $_COMPANY->getImperaviLanguage(); ?>.js"></script>

	<!-- redactor  -->
    <link rel="stylesheet" href="../vendor/js/redactor-3-5-2/redactor.min.css" />
    <link rel="stylesheet" href="../vendor/js/redactor-3-5-2/plugins/handle/handle.min.css" />
    <!--<script src="../vendor/js/redactor-3-5-2/redactor.tele.min.js?v=1"></script>-->
    <script src="../vendor/js/redactor-3-5-2/redactor.tele_v2.min.js"></script>
    <script src="../vendor/js/redactor-3-5-2/plugins/video/video.min.js"></script>
    <script src="../vendor/js/redactor-3-5-2/plugins/fontcolor/fontcolor.min.js"></script>
    <script src="../vendor/js/redactor-3-5-2/plugins/counter/counter.min.js"></script>
    <script src="../vendor/js/redactor-3-5-2/plugins/handle/handle.min.js"></script>
    <script src="../vendor/js/redactor-3-5-2/plugins/table/table.min.js"></script>
    <script src="../vendor/js/redactor-3-5-2/plugins/fontsize/fontsize.min.js"></script>
    <script src="../vendor/js/redactor-3-5-2/plugins/alignment/alignment.min.js"></script>
    <script src="../vendor/js/redactor-3-5-2/plugins/limiter/limiter.min.js"></script>
    <script src="../vendor/js/redactor-3-5-2/langs/<?= $_COMPANY->getImperaviLanguage(); ?>.js"></script>
    <script src="./js/initRedactor.js"></script>

    <script defer type="text/javascript" src="../vendor/js/jquery-qrcode-master/jquery.qrcode.min.js"></script>

    <!-- Fancybox   -->
    <link href="../vendor/js/fancybox-3.5.7/dist/jquery.fancybox.min.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'"/>
    <script defer src="../vendor/js/fancybox-3.5.7/dist/jquery.fancybox.min.js"></script>

    <!-- Sweetalert   -->
    <link href="../vendor/js/sweetalert2-9.14.0/dist/sweetalert2.min.css" rel="stylesheet" />
    <script src="../vendor/js/sweetalert2-9.14.0/dist/sweetalert2.min.js"></script>

    <!-- Survey.js -->
    <!-- <link type="text/css" rel="stylesheet" href="../vendor/js/surveyjs-1.9.110/survey.min.css"> -->
    <link type="text/css" rel="stylesheet" href="../vendor/js/surveyjs-1.11.2/defaultV2.css">
    <link type="text/css" href="../vendor/js/surveyjs-1.11.2/modern.min.css" rel="stylesheet">
    <script src="../vendor/js/surveyjs-1.11.2/survey.jquery.min.js"></script>

    <!-- Optional: include a polyfill for ES6 Promises for IE11 -->
    <?php if(isset($_SESSION['ie11']) && $_SESSION['ie11'] === true) { ?>
        <script src="../vendor/js/promise-polyfill/dist/polyfill.min.js"></script>
    <?php } ?>

    <?php require_once __DIR__ . '/../../include/common/views/zone_id_setup.html.php' ?>
    <script src="./js/index.js.php?v=<?=REL_HASH?>&lang=<?= Lang::GetSelectedLanguage() ?>"></script>

    <?php if($_COMPANY->getAppCustomization()['mobileapp']['custom']['enabled']){ ?>
        <!-- SmartBanner -->
        <link rel="stylesheet" href="../vendor/js/smartbanner/dist/smartbanner.min.css">
        <script src="../vendor/js/smartbanner/dist/smartbanner.min.js"></script>
    <?php } ?>
    <script>
        var teleskopeCsrfToken="<?=Session::GetInstance()->csrf;?>";
        $(document).ready(function() {
            //initial for blank profile picture
            $('.initial').initial({
                charCount: 2,
                textColor: '#ffffff',
                color: window.tskp?.initial_bgcolor ?? null,
                seed: 0,
                height: 50,
                width: 50,
                fontSize: 20,
                fontWeight: 300,
                fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
                radius: 0
            });
        });
    </script>
    <!-- js -->

    <!-- Remove horizontal scrollbar -->
    <style>
        html, body {
            overflow-x: hidden;
        }
        #cp2 .input-group-addon {
            border-radius: 0;
            border: 1px solid #d2d6de;
            background-color: #fff;
            border-left: 0;
            padding-top: 6px;
            padding: 6px 8px 0px;
        }        
        button.navbar-toggler:focus{
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgb(0 123 255 / 25%);
        }
    </style>

    <!-- Color Picker js, css -->
    <script type="text/javascript" src="../vendor/js/bootstrap-colorpicker-2.5.1/dist/js/bootstrap-colorpicker.min.js"></script>
    <link href="../vendor/js/bootstrap-colorpicker-2.5.1/dist/css/bootstrap-colorpicker.min.css" rel="stylesheet"/>
     <!-- End Color Picker js, css -->

    <?php if (
            ($_COMPANY->getAppCustomization()['integrations']['analytics']['adobe']['enabled'] ?? false)
            && !empty(trim($_COMPANY->getAppCustomization()['integrations']['analytics']['adobe']['js_src'])) #Default value of src is spaces
    ) { ?>
      <!-- custom adobe analytics url -->
      <script async src="<?= $_COMPANY->getAppCustomization()['integrations']['analytics']['adobe']['js_src'] ?>"></script>
    <?php } ?>
</head>

<body style="background-color:<?= $_COMPANY->getStyleCustomization()['css']['body']['background-color'] ?: '#d3d3d1' ?>;">

<header>
    <nav class="navbar navbar-expand-lg navbar-light" aria-label="Primary">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <a
                    href='<?=str_starts_with(basename($_SERVER['PHP_SELF']), 'choose_zone') ? '' : 'home';?>'
                    class="focus-black-color-img"><img src="<?= $_COMPANY->val('logo') ?>"
                    alt="<?= sprintf(gettext("%s %s Home - %s Zone"),$_COMPANY->val('companyname'), $_COMPANY->getAppCustomization()['group']['name-plural'], $_ZONE->val('zonename'))?>"
                    height="50px"
                    class=" logo-img"
                >
                    <span class="logo-name"><strong></strong></span>
                </a>
                

                <button aria-label="Site Menu" tabindex="0" onclick="getFocus()" class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false">
                 <span class="icon-bar top-bar"></span>
                <span class="icon-bar middle-bar"></span>
                <span class="icon-bar bottom-bar"></span>
               </button></div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <?php
				//$unreadcount = count($db->get("SELECT `notificationid` FROM `notifications` WHERE `userid`='".$_SESSION['userid']."' and isread='2'"));
                $noti = [];
                /**  Disabled on 11/22/2023 as we are currently not using the notifications. See ticket. #3091
				$noti = $db->ro_get("SELECT n.*,w.firstname as whodo_firstname, w.lastname as whodo_lastname, w.picture as whodo_picture FROM `notifications` n LEFT JOIN `users` w ON n.whodo=w.userid WHERE n.`userid`='".$_SESSION['userid']."' AND n.zoneid='{$_ZONE->id()}' order by notificationid desc");

				if(count($noti)>0){
					for($n=0;$n<count($noti);$n++){
						$noti[$n]['message'] = rtrim($noti[$n]['whodo_firstname']." ".$noti[$n]['whodo_lastname']," ")." ".$noti[$n]['message'];
					}
				}
                **/

			?>

            <a tabindex="0" href="#main_section" class="skip-to-content-link"><?= gettext('Skip to main content'); ?></a>
            <?php
                $web_hotlink_placement = $_ZONE->val('hotlink_placement');
            ?>
           
            <div class="collapse navbar-collapse " id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto navbar-right float-right text-right ">
                    <?php                     
                    if (!str_starts_with(basename($_SERVER['PHP_SELF']), 'choose_zone')) {

                        if($_USER->isAdmin()){ ?>
                            <li class="nav-item dropdown  mobile-off">
                                <a class="nav-link dropdown-toggle home_nav" id="home-a" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false" ><?= gettext('Admin'); ?> &#9662;</a>
                                <div class="dropdown-menu">
                                    <ul><li>
                                    <a class="dropdown-item" rel="noreferrer" target="_blank" rel="noopener" href="<?= Url::GetZoneAwareUrlBase($_ZONE->id(), 'admin')?>" ><?= gettext('Admin Panel'); ?></a>
                                    </li><li>
                                    <a class="dropdown-item" href="manage_admin_contents" onclick='localStorage.setItem("manage_active", "manageGlobalAnnouncements");' ><?= gettext('Admin Content'); ?></a>
                                    </li></ul>
                                </div>
                            </li>

                        <?php } ?>
                    
                    <?php if(!isset($hideMainMenuOptionsTemporary)){ ?>
                    <?php if ($_SESSION['app_type'] == 'affinities') { ?>

                        <?php
                            $zone_affinities = $_COMPANY->getZones($_SESSION['app_type']);

                            // Remove hideen zones
                            foreach ($zone_affinities as $k => $z) {
                                if ($z['home_zone'] == -1)
                                    unset($zone_affinities[$k]);
                            }
                            $otherZones = [];
                            $homeZone = $_USER->getMyConfiguredZone($_SESSION['app_type']);


                            if (!empty($_SESSION['allow_user_to_choose_zone'])) {
                                // If the user came to this zone as a result of choose zone, then the session variable
                                //allow_user_to_choose_zone will be set and in that case users home should point to
                                // choose zone.
                        ?>
                                <li class="nav-item">
                                    <a href="<?= Url::GetZoneAwareUrlBase($_ZONE->id()) . 'choose_zone'?>" id="home-h" class="nav-link home_nav">
                                        <p><?= gettext('Home'); ?></p>
                                    </a>
                                </li>
                        <?php
                            } elseif (count($zone_affinities) > 1) {
                                // This section deals with showing a zone drop down for legacy purposes.
                                $selectedZone = array_filter($zone_affinities,function ($z) { global $_ZONE; return ($z['zoneid'] == $_ZONE->id());});
                                $selectedZone = empty($selectedZone) ? array('zonename' => 'Home') : array_values($selectedZone)[0];
                                $isDelegatedAccessUser = $_USER->isDelegatedAccessUser();
                                $delegatedAccessUserAuthorizedZones = $_USER->getDelegatedAccessUserAuthorizedZones();
                        ?>

                            <li class="nav-item dropdown">
                                <a aria-current="page" id="home-h" class="nav-link dropdown-toggle home_nav" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"><?= ($_USER->isInHomeZone())? $selectedZone['zonename'].' &#9662;':$selectedZone['zonename'].' &#9662;'?></a>
                                <div class="dropdown-menu">
                                <ul>
                                
                                    <?php
                                    foreach($zone_affinities as $zone_affinity){
                                        if ($zone_affinity['zoneid'] == $homeZone){
                                            $disabled = '';
                                            if ($isDelegatedAccessUser && !in_array($zone_affinity['zoneid'], $delegatedAccessUserAuthorizedZones)) {
                                                $disabled = "disabled";
                                            }
                                    ?>
                                    <li>
                                        <a class="dropdown-item <?=$disabled?>" <?= ($zone_affinity['zoneid'] == $_ZONE->id()) ? 'style="background:#efefef;"' : '' ?> href="<?= Url::GetZoneAwareUrlBase($zone_affinity['zoneid']) . 'home' ?>"><?= $zone_affinity['zonename'] ?><small> [<?= gettext('Home zone'); ?>]</small></a>
                                    </li>
                                    
                                    <?php
                                        } else {
                                            array_push($otherZones,$zone_affinity);
                                        }
                                    } ?>
                           
                                    <?php
                                    foreach($otherZones as $zone_affinity){
                                        $disabled = '';
                                        if ($isDelegatedAccessUser && !in_array($zone_affinity['zoneid'], $delegatedAccessUserAuthorizedZones)) {
                                            $disabled = "disabled";
                                        }
                                    ?>
                                    <li>
                                        <a class="dropdown-item <?=$disabled?>" <?= ($zone_affinity['zoneid'] == $_ZONE->id()) ? 'style="background:#efefef;"' : '' ?>  href="<?= Url::GetZoneAwareUrlBase($zone_affinity['zoneid']) . 'home' ?>"><?= $zone_affinity['zonename'] ?></a>
                                    </li>
                                    <?php } ?>

                                </ul>
                                </div>
                            </li>
                        <?php } else { ?>
                            <li class="nav-item">
                                <a href="<?= Url::GetZoneAwareUrlBase($_ZONE->id()) . 'home'?>" id="home-h" class="nav-link home_nav" onclick="changeMenuClass(2)">
                                    <p><?= gettext('Home'); ?></p>
                                </a>
                            </li>
                        <?php } ?>
                    <?php } elseif($_SESSION['app_type'] == 'talentpeak' || $_SESSION['app_type'] == 'peoplehero'){ ?>
                        <li class="nav-item">
                            <a href="<?= Url::GetZoneAwareUrlBase($_ZONE->id()) . 'home'?>" id="home-h" class="nav-link home_nav">
                                <p><?= gettext('Home'); ?></p>
                            </a>
                        </li>
                    <?php } else { ?>

                            <?php if (1) { /* Hidden per issue #963 */ ?>
                            <li class="nav-item">
                                <a class="nav-link home_nav" href="<?= Url::GetZoneAwareUrlBase($_ZONE->id()) . 'home'?>" id="home-h" class="active-1">
                                    <p><?= gettext('Home'); ?></p>
                                </a>
                            </li>
                            <?php } ?>

                            <?php if ($_COMPANY->getAppCustomization()['header']['show_my_location_menu']) { ?>
                            <li class="nav-item">
                                <a class="nav-link home_nav" href="<?= Session::GetInstance()->mylocation_url ?: Url::GetZoneAwareUrlBase($_ZONE->id()) . 'home' ?>" id="home-mh" class="active-1">
                                    <p><?= gettext('My Location'); ?></p>
                                </a>
                            </li>
                            <?php } ?>
                    <?php } ?>
                <?php } ?>
                        <?php  if($web_hotlink_placement == 'header'){
                            $hotlink = $_COMPANY->getHotlinks();
                            if(count($hotlink) > 3){ ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle home_nav" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false" ><?= gettext('More Links'); ?> &#9662;</a>
                                    <div class="dropdown-menu">
                                        <?php for($h=0;$h<count($hotlink);$h++){ ?>
                                            <a class="dropdown-item" target="_blank" rel="noopener" href="<?= $hotlink[$h]['link']; ?>" ><?= $hotlink[$h]['title']; ?></a>
                                        <?php	} 	?>
                                    </div>
                                </li>
                            <?php  } elseif (count($hotlink) > 0){
                                for($h=0;$h<count($hotlink);$h++){ ?>
                                    <li class="nav-item">
                                        <a target="_blank" rel="noopener" href="<?= $hotlink[$h]['link']; ?>" class="nav-link home_nav">
                                            <p><?= $hotlink[$h]['title']; ?></p>
                                        </a>
                                    </li>
                                <?php	}
                            }
                        } ?>
                        <?php if ($_COMPANY->getAppCustomization()['calendar']['enabled'] && $_COMPANY->getAppCustomization()['event']['enabled']) {?>
                        <li class="nav-item">
                            <a href="calendar" id="home-c" class="nav-link home_nav">
                                <p><?= gettext('Calendar'); ?></p>
                            </a>
                        </li>
                        <?php } ?>
                    <?php if(!isset($hideMainMenuOptionsTemporary)){ ?>
                        <?php if (Config::Get('ENABLE_ZONE_SEARCH')) { ?>
                          <li class="nav-item">
                            <a href="search" id="home-s" class="nav-link home_nav" aria-label="Go to search page to search for content in this zone">
                              <i id="home-s-icon" class="fa fa-search home_nav"></i>
                            </a>
                          </li>
                        <?php } ?>
                    <?php } ?>

                        <?php if ($_COMPANY->getAppCustomization()['header']['notifications']) { ?>
                        <li class="nav-item dropdown mobile-off">
                            <div class="dropdown-menu calender" aria-labelledby="navbarDropdown">
                                <div class="parh dyn-height">
                                <tr>
                                <?php if (!empty($noti)){ ?>
                                    <button type="button" onclick="setAllReadNotification()" class="btn btn-info setallread"><?= gettext('Set all read'); ?></button>
                                    <table summary="This table shows notifications" >
                                        <colgroup>
                                            <col span="1" style="width: 10%;">
                                            <col span="1" style="width: 90%;">
                                        </colgroup>
                                        <tbody>
                                    <?php
                                    $unreadcount = 0;
                                    for ($z = 0; $z < count($noti); $z++){
                                        if ($noti[$z]['isread'] == 2) {$unreadcount = $unreadcount + 1; }
                                    ?>
                                    <tr id="noti<?= $z; ?>"
                                         onclick="readNotification(<?= $noti[$z]['section']; ?>,'<?= $_COMPANY->encodeId($noti[$z]['tableid']); ?>',<?= $noti[$z]['notificationid']; ?>)"
                                        style="cursor: pointer;">
                                        <td>
                                            <?= User::BuildProfilePictureImgTag($noti[$z]['whodo_firstname'],$noti[$z]['whodo_lastname'],$noti[$z]['whodo_picture'],'memberpic2')?>
                                        </td>
                                        <td>
                                            <span style="font-size:14px; color:<?= ($noti[$z]['isread'] == 1) ? '#b0b0b0' : '#333333';?>;"><?= $noti[$z]['message']; ?></span>
                                            <span class="hour">
                                                <span class="fa fa-clock"
                                                      style="padding:5px;font-size:12px;color:#b0b0b0;"> <?= $db->timeago($noti[$z]['datetime']); ?>
                                                </span>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                        </tbody>
                                    </table>
                                <?php } else { ?>
                                        <div class="media">
                                                    <div>
                                                        <p>
                                                            ------- <?= gettext('No new notifications'); ?>! -------
                                                        </p>
                                                    </div>
                                                </div>

                                <?php } ?>
                                </div>
                            </div>
							 <a role="button" href="#" aria-expanded="false" class="focus-black-color-img dropdown-toggle" data-toggle="dropdown"><img src="img/alarm.png" alt="Notification you have <?= $unreadcount??0; ?> notifications" width="20px" style="margin-top:10px;" height="20px" class=" menu-m"><span class="button__badge"><?= $unreadcount??0; ?></span></a>
                        </li>
                        <?php }                    
                    } ?>

                        <li class="nav-item dropdown mobile-off">
                            <a role="button" href="#" aria-expanded="false" class="focus-black-color-img dropdown-toggle" data-toggle="dropdown">
                              <?php if ($_USER->isDelegatedAccessUser()) { ?>
                                <?php
                                  /**
                                   * Show icon as grantee name -> grantor name
                                   * Remember, $_USER is now the grantor
                                   * To get the grantee userid, see the session variable $_SESSION['grantee_userid']
                                   */
                                  $grantee_user = User::GetUser($_SESSION['grantee_userid']);
                                ?>
                                <?= User::BuildProfilePictureImgTag($grantee_user->val('firstname'), $grantee_user->val('lastname'), $grantee_user->val('picture'),'raound'); ?>
                                <i class="fas fa-arrow-right align-middle"></i>
                                <?= User::BuildProfilePictureImgTag($_USER->val('firstname'), $_USER->val('lastname'), $_USER->val('picture'),'raound'); ?>
                              <?php } else { ?>
                                <?= User::BuildProfilePictureImgTag($_USER->val('firstname'), $_USER->val('lastname'), $_USER->val('picture'),'raound'); ?>
                              <?php } ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="profile"><i class="fa fa-fw fa-user" aria-hidden="true"></i>&nbsp;  <?= gettext('Profile'); ?></a></li>

                                <?php if ($_COMPANY->getAppCustomization()['my_schedule']['enabled']) { ?>
                                <li><a onclick="manageAvailalbeSchedules()" href="javascript:void(0);"><i class="fa fa-fw fa-user" aria-hidden="true"></i>&nbsp;  <?= gettext('My Schedule'); ?></a></li>
                                <?php } ?>

                                <?php if ($_USER->isZoneAdmin($_ZONE->id()) && $_COMPANY->getAppCustomization()['booking']['enabled']) { ?>
                                    <li><a href="manage_bookings"><i class="fa fa-solid fa-calendar-plus" aria-hidden="true"></i>&nbsp;  <?= gettext('Manage Bookings'); ?></a></li>
                                <?php } ?>

                                <?php
                              
                                    if ($_COMPANY->getAppCustomization()['event']['enabled'] && $_COMPANY->getAppCustomization()['event']['my_events']['enabled']) { ?>
                                        <li>
                                            <a href="my_events"><i class="fa fa-fw fa-calendar" aria-hidden="true"></i>&nbsp;  <?= gettext('My Events'); ?></a>
                                        </li>
                                    <?php } ?>

                                <?php
                                if (
                                    ($_COMPANY->getAppCustomization()['post']['approvals']['enabled'] || $_COMPANY->getAppCustomization()['event']['approvals']['enabled'] || $_COMPANY->getAppCustomization()['newsletters']['approvals']['enabled'] ||
                                    $_COMPANY->getAppCustomization()['surveys']['approvals']['enabled'])
                                    &&
                                    $_USER->canApproveSomething()
                                ) { ?>
                                    <li>
                                        <a href="my_approvals"><i class="fa fa-fw fa-check-square" aria-hidden="true"></i>&nbsp;  <?= gettext('My Approvals'); ?></a>
                                    </li>
                                <?php } ?>

                                <?php if ($_USER->isUserInboxEnabled()) { ?>
                                    <li>
                                        <a href="my_inbox"><i class="fa fa-fw fa-mail-bulk" aria-hidden="true"></i>&nbsp;  <?= gettext('My Inbox'); ?></a>
                                    </li>
                                <?php } ?>

                                <?php if ($_COMPANY->getAppCustomization()['points']['enabled'] && $_COMPANY->getAppCustomization()['points']['frontend_enabled']) { ?>

                                <?php if (0) { //@todo temporary use... remove in the future?>
                                <li><a target="_blank" href="https://awsuat-augeodemo.augeocms.com/"><i class="fa fa-gem" aria-hidden="true"></i>&nbsp;  <?= gettext('Redeem Points'); ?></a></li>
                                <?php } ?>                        
                                <li>
                                  <a href="points_program">                                  
                                  <i class="fa fa-gem" aria-hidden="true"></i>&nbsp;  <?= $_USER->getPointsBalance(); ?> <?= gettext('Points'); ?>
                                  </a>
                                </li>

                                <?php } ?>

						        <?php if(0){ /* Commented on 12/27/22 by Aman as a result of removal of Company loginmethod column */ ?>
                                <li><a href="changepassword"><i class="fa fa-fw fa-cog" aria-hidden="true"></i>&nbsp;  <?= gettext('Change Password'); ?></a></li>
						        <?php	} ?>

                                <li class="divider"></li>
                                <li><a href="logout?logout=1"><i class="fa fa-fw fa-power-off" aria-hidden="true"></i>&nbsp;  <?= gettext('Logout'); ?></a></li>
                            </ul>
                        </li>
                        

                        <!-- ONLY MOBILE START-->
                        <li class="nav-item only-mobile">
                            <a href="profile" class="nav-link home_nav" >
                                <p><?= gettext('Profile'); ?></p>
                            </a>
                        </li>
                        
                    <?php if(0){ /* Commented on 12/27/22 by Aman as a result of removal of Company loginmethod column */ ?>
                        <li class="nav-item only-mobile">
                            <a href="changepassword" class="nav-link home_nav">
                                <p><?= gettext('Change Password'); ?></p>
                            </a>
                        </li>
                    <?php } ?>
                        <li class="nav-item only-mobile">
                            <a href="logout?logout=1" class="nav-link home_nav" >
                                <p><?= gettext('Logout'); ?></p>
                            </a>
                        </li>

                        <!-- ONLY MOBILE END -->

                        <li class="dropdown">
                        </li>
                    </ul>
                </div>
               
                <!-- /.navbar-collapse -->
        </div>
        <!-- /.container-fluid -->
    </nav>
    </header>
    <div id="container">
        <div id="stepper"></div>
    </div>
    <!-- Load any dynamic modal view here -->
    <div id="loadAnyModal"></div>
    <script>
        (function(window, document) {

            var requestAnimationFrame = window.requestAnimationFrame || window.webkitRequestAnimationFrame;
            var container = document.getElementById("container");
            var stepper = document.getElementById("stepper");
            var div = container.offsetWidth / 60;
            var count = 0;

            function step() {
                count++;

                //stepper.style.width = (count*20)+"px";
                stepper.style.width = (div * count) + "px";
                if ((div * count) <= container.offsetWidth) {
                    requestAnimationFrame(step);
                } else {
                    $("#stepper").css("background-color", "#d3d3d1");
                }
            }

            requestAnimationFrame(step);

        })(window, document);
    </script>
    <script>
        $('.close').click(function() {
            swal.fire({title: 'Message',text:"Closed"});
        })


    function getFocus() {
        document.getElementById("navbarSupportedContent").focus();
    }

    function manageAvailalbeSchedules(){
        closeAllActiveModal();
        $.ajax({
            url: 'ajax_user_schedule.php',
            type: 'GET',
            data: 'manageAvailalbeSchedules=1',
            success: function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title:jsonData.title,text:jsonData.message});
                    
                } catch(e) {
                    $('#modal_over_modal').html(data);
                    $('#manageAvailableScheduleModal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                }
            }
        });
    }
</script>
