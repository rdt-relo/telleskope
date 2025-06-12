<?php
/* @var User $_USER */
/* @var Company $_COMPANY */
?>

<link href="<?=TELESKOPE_CDN_STATIC?>/vendor/js/slim-image-cropper/slim/slim.min.css" rel="stylesheet">
<style>

    .delete-account {
        margin-top: 10px;
        font-size: .875rem;
        font-weight: 400;
        text-align: center;
    }

    .no-drop{
        cursor: no-drop !important;
    }

    .slim {
        border-radius: 50%;
    }
    .remove-profile{
        margin-left: -146px;
        /*position: absolute;*/
        background: #0077b5;
        border-radius: 100%;
        padding: 14px 17px;
    }
    .remove-profile:hover{
        background-color: #72aada;
    }

    .profile-btn-del {
        background: #0077b5;
        border-radius: 100%;
        margin-top: -4rem;
        overflow: visible;
        position: relative;
        top: -17px;
    }
    .profile-btn-upload {
        background: #0077b5;
        border-radius: 100%;
        margin-top: -4rem;
    }
    .profile-btne:hover{
        background-color: #72aada;
    }

    .select2-selection__rendered {
        line-height: 38px !important;
    }
    .select2-container .select2-selection--single {
        height: 38px !important;
    }
    .select2-selection__arrow {
        height: 38px !important;
    }

    .profile-var-name {
        text-align: right;
        padding: 0 1rem;
        margin: 8px 0 4px 0;
    }
    .profile-var-value {
        text-align: left;
        padding: 0 1rem;
        font-weight: bold ;
        margin: 8px 0 4px 0;
    }
    @media (max-width: 767px) {
        .profile-var-name {
            margin: 8px 0 2px 0;
            text-align: center;
            font-size: 14px;
        }
        .profile-var-value {
            margin: 2px 0 8px 0;
            text-align: center;
        }
    }
    .container-sub {
        margin-top: 10px;
        background-color: #ffffff;
        min-height: auto;
        display: flex;
        padding: 20px 0;
    }
    .privacy-disabled {
        color: darkgray;
    }

.inputfile {
    margin-top: -30px;
    cursor: pointer;
}
.inputfile2 {
    margin-left: 25px;
}
#profilePictureForm .confirm {
    cursor: pointer;
    }
</style>

<main>
<!-- banner -->
<div class="as row-no-gutters"
     style="background: url(<?= $banner ? $banner : 'img/img.png'?>) no-repeat; background-size:cover; background-position:center;">
    <h1 class="banner-title" style="width: 100%; padding-top:85px;"><?= $bannerTitle; ?></h1>
</div>

<!-- manage profile -->
<div id="main_section" class="container inner-background">
<div class="row row-no-gutters">
    <div class="col-12 mt-4">

      <?php if ($_USER->isDelegatedAccessUser()) { ?>
        <?php
          /**
           * Remember, $_USER is now the grantor
           * To get the grantee userid, see the session variable $_SESSION['grantee_userid']
           */
          $grantee_user = User::GetUser($_SESSION['grantee_userid']);
        ?>
        <div class="alert-danger p-3">
            <?= sprintf(gettext('You are currently logged in as another user (%1$s). To return to your account (%2$s), please log out and sign in again.'), $_USER->getEmailForDisplay(), $grantee_user->getEmailForDisplay()) ?>
        </div>
      <?php } ?>

        <?php if (!isset($_GET['edit'])){ ?>
        <div class="col">
            <div class="inner-page-title">
                <div class="col-11">
                    <h2><?= gettext('User Profile'); ?></h2>
                </div>
                <div class="col-1 help-icon">
                    <?php
                        $page_tags = 'user_profile,user_profile_language,user_profile_notifications,howto_navigate_homescreen,user_delete_account';                    
                        ViewHelper::ShowTrainingVideoButton($page_tags, 30);
                    ?>
                </div>
            </div>
            <hr class="linec">
        </div>
        <?php } ?>

        <?= User::BuildProfilePictureImgTag($_USER->val('firstname'), $_USER->val('lastname'), $_USER->val('picture'),'profile-pic d-block mx-auto');?>
    </div>

    <div class="col-12 text-center mx-auto">
        <form method="post" enctype="multipart/form-data" id="profilePictureForm">
            <?php if( $_USER->val('picture')){ ?>

                <label class="profile-btn-del mr-4"><button data-confirm-noBtn="<?=gettext('No')?>" aria-label="Remove Profile Image" data-confirm-yesBtn="<?=gettext('Yes')?>" class="confirm btn-no-style" onclick="removeProfilePicture()" title="<?=gettext('Are you sure you want to remove profile picture?')?>">                
                <i class="fa fa-trash" style="color:#ffffff;width: 50px;height: 50px;padding-top: 18px;" aria-hidden="true"></i> </button>
                </label>               

            <?php } ?>
            <a role="button" href="javascript:void(0);"  aria-label="Upload Profile Image" onclick="uploadProfileImage()" user_id="<?= $_COMPANY->encodeId($_USER->val('userid')); ?>" id="showoutline" class="inputfile <?= $_USER->val('picture')? 'inputfile2' : ''?>"></a>
            <span class="profile-btn-upload <?=$_USER->val('picture') ? 'ml-4' : ''?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="17" viewBox="0 0 20 17">
                    <path d="M10 0l-5.2 4.9h3.3v5.1h3.8v-5.1h3.3l-5.2-4.9zm9.3 11.5l-3.2-2.1h-2l3.4 2.6h-3.5c-.1 0-.2.1-.2.1l-.8 2.3h-6l-.8-2.2c-.1-.1-.1-.2-.2-.2h-3.6l3.4-2.6h-2l-3.2 2.1c-.4.3-.7 1-.6 1.5l.6 3.1c.1.5.7.9 1.2.9h16.3c.6 0 1.1-.4 1.3-.9l.6-3.1c.1-.5-.2-1.2-.7-1.5z"></path>
                </svg>

            </span>

        </form>
    </div>

    <?php if((time()- ($_SESSION['profile_updated'] ?? 0)) < 3){ ?>
        <script> swal.fire({title: '<?= gettext('Success') ?>',text: '<?= gettext('Profile Updated Successfully.') ?>'} ) </script>
    <?php } ?>

    <?php if (!empty($error_message)) { ?>
    <div class="col-12 alert alert-danger">
      <?=  $error_message; ?>
    </div>
    <?php } ?>

    <?php if (isset($_GET['edit'])){ ?>

    <div class="row">
        <div class="col-12">
            <div class="mb-2">
                <h2>
                    <?= gettext('Update Profile'); ?>
                </h2>
            </div>
        </div>
        <div class="container-extra-margin">
            <div class="row">
                <div class="col-12">
                <form class="form-horizontal" method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
                    <?php if (isset($done)) { ?>
                        <div id="hidemesage" class="alert alert-info alert-dismissable">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">x</a>
                            <?= $done; ?>
                        </div>
                    <?php } elseif (isset($error)) { ?>
                        <div id="hidemesage" class="alert alert-danger alert-dismissable">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">x</a>
                            <?= $error; ?>
                        </div>
                <?php } ?>
                <div class="form-group"><p class="ml-3"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>  </div>
                <?php if ($_COMPANY->getAppCustomization()['profile']['allow_update_name']) { ?>
                    <div class="form-group">
                        <label for="firstname" class="control-label col-sm-4"><?= gettext('First Name'); ?><span style="color: #ff0000;">*</span></label>
                        <div class=" col-sm-8">
                            <input type="text" class=" form-control" id="firstname" name="firstname" autocomplete="<?= isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : $_USER->val('firstname');?>"
                                   value="<?= isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : $_USER->val('firstname');?>"
                                   placeholder="<?= isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : $_USER->val('firstname');?>" required/>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="lastname" class="control-label col-sm-4"><?= gettext('Last Name'); ?><span style="color: #ff0000;">*</span></label>
                        <div class="col-sm-8">
                            <input id="lastname" type="text" name="lastname" autocomplete="<?= isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : $_USER->val('lastname');?>"
                                   value="<?= isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : $_USER->val('lastname');?>"
                                   class="form-control" placeholder="<?= isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : $_USER->val('lastname');?>" required/>
                        </div>
                    </div>
                <?php }?>

                    <?php if ($_COMPANY->getAppCustomization()['profile']['enable_pronouns'] && $_COMPANY->getAppCustomization()['profile']['allow_update_pronouns']) { ?>
                    <div class="form-group">
                        <label for="pronouns" class="col-sm-4 control-label"><?= gettext('Pronouns'); ?>
                            <span style="color: #ff0000;"> </span></label>
                        <div class="col-sm-8">
                            <input id="pronouns" class="form-control" placeholder="<?= gettext('Pronouns'); ?>" name="pronouns" type="text" value="<?= isset($_POST['pronouns']) ? htmlspecialchars($_POST['pronouns']) : $_USER->val('pronouns');?>">
                        </div>
                    </div>
                    <?php }?>

                    <div class="form-group">
                        <label id="Timezone" aria-label="Timezone" class="control-label col-sm-4"><?= gettext('Timezone'); ?></label>
                        <div id="timezone-section" class="col-sm-8">
                            <select class="form-control teleskope-select2-dropdown" name="timezone" style="width: 100%;">
                                <?php echo getTimeZonesAsHtmlSelectOptions($_USER->val('timezone')); ?>
                            </select>
                        </div>
                    </div>
                    

                    <?php
                    $selected_language = (isset($_POST['default_language']) && $_COMPANY->isValidLanguage($_POST['default_language'])) ? htmlspecialchars($_POST['default_language']) : $_USER->val('language');
                    ?>


                    <div class="form-group">
                        <label for="homezone" class="control-label col-sm-4"><?= gettext('Home Zone'); ?><span style="color: #ff0000;">*</span></label>
                        <div class="col-sm-8">
                            <select aria-describedby="zoneTextNote"  id="homezone" class="form-control" name="homezone" style="width: 100%;" required >
                                <option value="" disabled><?= gettext("Select Zone")?></option>
                            <?php
                            $user_zones = Str::ConvertCSVToArray($_USER->val('zoneids'));
                            foreach($_COMPANY->getZones($_SESSION['app_type']) as $zone){
                                if ($zone['home_zone'] == -1){
                                    continue;
                                }
                                if (!in_array($zone['zoneid'], $user_zones)) {
                                    continue;
                                }
                            ?>
                                <option value="<?= $_COMPANY->encodeId($zone['zoneid']); ?>" <?= $_USER->getMyConfiguredZone($_SESSION['app_type']) == $zone['zoneid'] ? 'selected' : '';?>><?= $zone['zonename']; ?></option>
                            <?php } ?>
                            </select>
                            <small id="zoneTextNote"> <?= gettext('You can choose one of your member zones as your home zone. To become a member of the zone, join a group within the desired zone.') ?></small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label id ="default-language" class="control-label col-sm-4"><?= gettext('Default Language'); ?></label>
                        <div id="language-section" class="col-sm-8">
                            <select class="form-control teleskope-select2-dropdown" name="default_language" style="width: 100% !important;">
                                <option value="" disabled>Select Language</option>
                            <?php foreach($allowedLanguages as $languageKey => $languageValue){ 
                                $selectedLag = "";
                                if ($languageKey == $selected_language){
                                    $selectedLag = "selected";
                                }    
                            ?>
                                <option value="<?= $languageKey; ?>" <?= $selectedLag; ?> ><?= $languageValue;?></option>
                            <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="date_format" class="control-label col-sm-4"><?= gettext('Date Format'); ?></label>
                        <div class="col-sm-8">
                            <select id="date_format" class="form-control" name="date_format" style="width: 100% !important;">
                            <?php foreach(User::DATE_FORMATS as $key=>$val){ ?>
                                <option value="<?php echo $key;?>" <?php if($_USER->val('date_format')== $key ){ echo 'selected="selected"'; } ?>> <?= $val; ?> </option>
                            <?php  }?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="time_format" class="control-label col-sm-4"><?= gettext('Time Format'); ?></label>
                        <div class="col-sm-8">
                            <select id="time_format" class="form-control" name="time_format" style="width: 100% !important;">
                            <?php foreach(User::TIME_FORMATS as $tkey=>$tval){ ?>
                            <option value="<?php echo $tkey;?>" <?php if($_USER->val('time_format')==$tkey){ echo 'selected="selected"'; } ?>> <?= $tval; ?> </option>
                            <?php } ?>
                            </select>
                        </div>
                    </div>
                    <?php if ($_COMPANY->getAppCustomization()['profile']['allow_meeting_link']) { ?>
                    <div class="form-group">
                        <label for="mywebconferenceurl" class="control-label col-sm-4"><?= gettext('My meeting link'); ?></label>
                        <div class="col-sm-8">
                            <input  aria-describedby="placeholdertext" id="mywebconferenceurl" type="text" name="mywebconferenceurl"
                                   value="<?= htmlspecialchars($mywebconferenceurl);?>"
                                   class="form-control"/>
                                    <p id="placeholdertext"> <?= gettext('Note: https://... your teams or zoom meeting link ...');?></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mywebconferencedetail" class="control-label col-sm-4"><?= gettext('My meeting link details'); ?></label>
                        <div class="col-sm-8">
                            <input  aria-describedby="placeholdertext_conf_details" id="mywebconferencedetail" type="text" name="mywebconferencedetail"
                                    value="<?= htmlspecialchars($mywebconferencedetail);?>"
                                    class="form-control"/>
                            <p id="placeholdertext_conf_details"> <?= gettext('Enter your meeting join instruction details.');?></p>
                        </div>
                    </div>
                    <?php } ?>

                    <div class="form-group">
                        <div class="col-12 text-center">
                            <button class="btn btn-primary " type="submit" name="update"><?= gettext('Update'); ?></button>
                        </div>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>  
    <?php } else { ?>
    <div class="row pr-5">
        <div class="col-12 col-sm-6  profile-var-name">
            <?= gettext('First Name'); ?>
        </div>
        <div class="col-12 col-sm-6  profile-var-value">
            <?= $_USER->val('firstname'); ?>
        </div>    
        <div class="col-12 col-sm-6  profile-var-name">
            <?= gettext('Last Name'); ?>
        </div>
        <div class="col-12 col-sm-6  profile-var-value">
            <?= $_USER->val('lastname'); ?>
        </div>
        <?php if ($_COMPANY->getAppCustomization()['profile']['enable_pronouns']) { ?>
        <div class="col-12 col-sm-6  profile-var-name">
            <?= gettext('Pronouns'); ?>
        </div>
        <div class="col-12 col-sm-6  profile-var-value">
            <?= $_USER->val('pronouns'); ?>
        </div>
        <?php } ?>
        <div class="col-12 col-sm-6  profile-var-name">
            <?= gettext('Email Address'); ?>
        </div>
        <div class="col-12 col-sm-6  profile-var-value">
            <?= $_USER->getEmailForDisplay(true); ?>
        </div>

        <div class="col-12 col-sm-6  profile-var-name">
            <?= gettext('Job Title'); ?>
        </div>
        <div class="col-12 col-sm-6  profile-var-value">
            <?= $_USER->val('jobtitle'); ?>
        </div>

        <div class="col-12 col-sm-6  profile-var-name">
           <?= gettext('Department'); ?>
        </div>
        <div class="col-12 col-sm-6  profile-var-value">
            <?= $_USER->getDepartmentName() ?>
        </div>

        <div class="col-12 col-sm-6  profile-var-name">
            <?= gettext('Office Location'); ?>
        </div>
        <div class="col-12 col-sm-6  profile-var-value">
            <?= $_USER->getBranchName(); ?>
        </div>

        <div class="col-12 col-sm-6  profile-var-name">
            <?= gettext('Timezone'); ?>
        </div>
        <div class="col-12 col-sm-6  profile-var-value">
            <?= TskpTime::OUTDATED_TIMEZONE_MAP[$_USER->val('timezone')] ?? $_USER->val('timezone'); ?>
        </div>

        <div class="col-12 col-sm-6  profile-var-name">
           <?= gettext('Language'); ?>
        </div>
        <div class="col-12 col-sm-6  profile-var-value">
            <?= $_USER->val('language') && array_key_exists($_USER->val('language'),$allowedLanguages) ? $allowedLanguages[$_USER->val('language')] : 'English'; ?>
        </div>

        <div class="col-12 col-sm-6  profile-var-name">
           <?= gettext('Home Zone'); ?>
        </div>
        <div class="col-12 col-sm-6  profile-var-value">
            <?= $_COMPANY->getZone($_USER->getMyConfiguredZone($_SESSION['app_type']))->val('zonename') ?? '-' ?>
        </div>


        <div class="col-12 col-sm-6  profile-var-name">
           <?= gettext('Date Format'); ?>
        </div>
        <div class="col-12 col-sm-6  profile-var-value">   
        <?= User::DATE_FORMATS[$_USER->val('date_format')] ?>
        </div>

        <div class="col-12 col-sm-6  profile-var-name">
           <?= gettext('Time Format'); ?>
        </div>
        <div class="col-12 col-sm-6  profile-var-value">
            <?= User::TIME_FORMATS[$_USER->val('time_format')] ?>
        </div>

        <?php if (!empty($mywebconferenceurl) && $_COMPANY->getAppCustomization()['profile']['allow_meeting_link']) { ?>
        
        <div class="col-12 col-sm-6  profile-var-name">
            <?= gettext('My meeting link'); ?>
        </div>
        <div class="col-12 col-sm-6  profile-var-value break-long-text">
            <?= htmlspecialchars($mywebconferenceurl) ?>
        </div>

        <div class="col-12 col-sm-6  profile-var-name">
            <?= gettext('My meeting link details'); ?>
        </div>
        <div class="col-12 col-sm-6  profile-var-value">
            <?= htmlspecialchars($mywebconferencedetail) ?>
        </div>
        <?php }       
        ?>
        
        <div class="col-12 text-center mt-5 mb-4">
            <?php if (!isset($_GET['edit'])) { ?>
                <a id="update_profile" class="btn btn-sm btn-affinity newfocus" href="?edit">
                    <?= gettext('Update Profile'); ?>
                </a>
            <?php } ?>          
                      
        </div>
      
    </div>
    <?php } ?>

</div>
</div>

<?php if ($_COMPANY->getAppCustomization()['profile']['enable_bio']) { ?>
<div class="container container-sub">
    <div class="row col-12">
      <div class="col-12">
        <div class="col-12">
            <h2><?= gettext('Bio') ?></h2>
            <button class="btn btn-affinity pull-right" id="bioToggleBtn" onclick="showHideUpdateBioForm()">
                    <?= gettext('Update Bio'); ?>
                </button>
            <hr class="linec mt-3">
        </div>
      </div>

      <div class="col-12 mt-3">
      <div class="col-12">
       

        <?php if (!($_USER->getBio())) { ?>
            <div class="col-12 text-center" id="bio_container">
                <h3 style="font-size: 1.2rem;"><?= gettext('You have not added a bio yet') ?></h3>
                
            </div>
      
        <?php } else { ?>
            <div class="col-12" id="bio_container">
                <div id="post-inner">
                    <?= $_USER->getBio(); ?>
                </div>
               
            </div>
        
        <?php } ?>

        <div class="col-12" id="bio_form" style="display:none;">
            <div class="form-group">
                <label for="time_format" class="control-label col-sm-4"></label>
                <div class="col-sm-12">
                    <textarea class="form-control" name="bio" rows="10" id="redactor_content" placeholder="<?= gettext("Bio here"); ?>"><?= htmlspecialchars($_USER->getBio()); ?></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-12 text-center">
                    <button class="btn btn-affinity" onclick="updateUserBio();"><?= gettext('Update Bio'); ?></button>
                    <button class="btn btn-affinity-gray " onclick="showHideUpdateBioForm();"><?= gettext('Cancel'); ?></button>
                </div>
            </div>
        </div>

        <script>
            $('#redactor_content').initRedactor('redactor_content','user_bio',['counter','table']);
            $(".redactor-voice-label").text("<?= gettext('Add bio');?>");
        </script>
      </div>

      </div>

    </div>
  </div>
<?php } ?>

<!-- Existing memberships -->
<?php if (!isset($_GET['edit'])){ ?>
<div class="container container-sub">
    <div class="row col-12">
        <div class="col-12">
            <div class="col-12">
                <h2> <?= sprintf(gettext("Joined %s"),$_COMPANY->getAppCustomization()['group']['name-short-plural'])?></h2>
                <hr class="linec mt-1">
            </div>
        </div>
        <div class="col-12">
            <div class="col-12">
            <div class="table-responsive">
            <div id="joinedGroupInfo" style="display:none;" role="status" aria-live="polite"></div>
                <table class="table table-hover mb-3 display compact" id="joined_group_list" summary="<?= sprintf(gettext("Joined %s"),$_COMPANY->getAppCustomization()['group']['name-short-plural'])?>">
                    <thead>
                    <tr>
                        <th role="columnheader" width="20%" scope="col"><?=$_COMPANY->getAppCustomization()['group']['name-short']?> <?= gettext('Name'); ?></th>

                        <?php if($_COMPANY->getAppCustomization()['chapter']['enabled'] || $_COMPANY->getAppCustomization()['channel']['enabled']){?>
                        <th role="columnheader" width="20%" scope="col"><?= $_COMPANY->getAppCustomization()['chapter']['enabled'] ? $_COMPANY->getAppCustomization()['chapter']['name-short-plural'] . ( $_COMPANY->getAppCustomization()['channel']['enabled'] ? ' / '.$_COMPANY->getAppCustomization()['channel']['name-short-plural'] : '') : $_COMPANY->getAppCustomization()['channel']['name-short-plural']; ?></th>
                        <?php } ?>
                       
                        <th role="columnheader" width="20%" scope="col"><?= gettext('Joined Date'); ?></th>
                        <th role="columnheader" width="20%" scope="col"><?= gettext('Allow Emails'); ?></th>

                        <?php if($_COMPANY->getAppCustomization()['group']['allow_anonymous_join']){?>
                        <th role="columnheader" width="20%" scope="col"><?= gettext('Privacy'); ?></th>
                        <?php } ?>
                        
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($mygroups as $data) { ?>
                    <?php if ($data['zoneid'] == $_ZONE->id()) { ?>
                        <tr>
                            <td><a style='text-decoration:none' href="detail?id=<?= $_COMPANY->encodeId($data['groupid']); ?>"><?= $data['groupname']; ?></a></td>

                            <?php if($_COMPANY->getAppCustomization()['chapter']['enabled'] && $data['chapters'] || $_COMPANY->getAppCustomization()['channel']['enabled'] && $data['channels']){?>                            
                            <td>
                                <?php } ?>
                               
                                    <?php if($_COMPANY->getAppCustomization()['chapter']['enabled'] && $data['chapters']){?>
                                        <p>
                                        <i class="fas fa-globe" style="" aria-hidden="true"></i> <?= implode('<br/><i class="fas fa-globe" style="" aria-hidden="true"></i> ', array_column($data['chapters'], 'chaptername')); ?>
                                       </p>
                                    <?php }?>                                
                                
                                    <?php if($_COMPANY->getAppCustomization()['channel']['enabled'] && $data['channels']){?>
                                        <p>
                                        <i class="fas fa-layer-group" style="" aria-hidden="true"></i> <?= implode('<br/><i class="fas fa-layer-group" style="" aria-hidden="true"></i> ', array_column($data['channels'], 'channelname'));?>
                                    </p>
                                    <?php }?>
                               
                              <?php if($_COMPANY->getAppCustomization()['chapter']['enabled'] && $data['chapters'] || $_COMPANY->getAppCustomization()['channel']['enabled'] && $data['channels']){?>                            
                              </td>
                            <?php }else { 
                                    if($_COMPANY->getAppCustomization()['chapter']['enabled'] || $_COMPANY->getAppCustomization()['channel']['enabled']){
                                    echo "<td>-</td>";}
                                } ?>                       

                            <td><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($data['groupjoindate'], true, false, false)?></td>

                            <td>
                                <?php if ($_COMPANY->getAppCustomization()['event']['enabled']) { ?>
                                <input aria-label="<?= gettext('Events'); ?>" type="checkbox" <?= $data['notify_events'] ? 'checked' : ''; ?>
                                       onchange="changeSetting(this,'<?= $_COMPANY->encodeId($data['memberid']); ?>','e')">
                                <?= gettext('Events'); ?>
                                <br/>
                                <?php } ?>
                                <?php if ($_COMPANY->getAppCustomization()['post']['enabled']) { ?>
                                <input aria-label="<?=Post::GetCustomName(true)?>" type="checkbox" <?= $data['notify_posts'] ? 'checked' : ''; ?>
                                       onchange="changeSetting(this,'<?= $_COMPANY->encodeId($data['memberid']); ?>','p')">
                                    <?=Post::GetCustomName(true)?>
                                <br/>
                                <?php } ?>
                                <?php if ($_COMPANY->getAppCustomization()['newsletters']['enabled']) { ?>
                                <input aria-label="<?= gettext('Newsletters'); ?>" type="checkbox" <?= $data['notify_news'] ? 'checked' : ''; ?>
                                       onchange="changeSetting(this,'<?= $_COMPANY->encodeId($data['memberid']); ?>','n')">
                                       <?= gettext('Newsletters'); ?>
                                <br/>
                                <?php } ?>
                                <?php if ($_COMPANY->getAppCustomization()['discussions']['enabled']) { ?>
                                <input aria-label="<?= gettext('Discussions'); ?>" type="checkbox" <?= $data['notify_discussion'] ? 'checked' : ''; ?>
                                       onchange="changeSetting(this,'<?= $_COMPANY->encodeId($data['memberid']); ?>','d')">
                                       <?= gettext('Discussions'); ?>
                                <br/>
                                <?php } ?>
                            </td>
                                
                            <?php if($_COMPANY->getAppCustomization()['group']['allow_anonymous_join']){?>
                            <td
                                <?= $data['join_group_anonymously']
                                    ? ''
                                    : 'class="privacy-disabled" title="'.sprintf(gettext('Privacy settings have been disabled for this %s'), $_COMPANY->getAppCustomization()['group']['name-short']).'"'
                                ?>
                            >
                                <input
                                    aria-label="<?= gettext('Anonymous Member'); ?>" type="checkbox" <?= $data['anonymous'] ? 'checked' : ''; ?>
                                    onchange="changePrivacySetting(this,'<?= $_COMPANY->encodeId($data['memberid'])?>')"
                                    <?= $data['join_group_anonymously'] ? '' : 'disabled'?>
                                >
                                <?= gettext('Anonymous Member'); ?>
                            </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>
</div>

<?php if ($_COMPANY->getAppCustomization()['donations']['enabled']) { ?>
<div class="container container-sub">
    <div class="row col-12">
        <div class="col-12">
            <div class="col-12">
                <h2> <?= gettext("Donation History")?> <span style="float:right;"><?= gettext("Total Donations ($400)");?></span></h2>
                <hr class="linec mt-1">
            </div>
        </div>

        <div class="col-12">
                <div class="col-12">
                <div class="table-responsive">
                <div id="donationsTransactionsInfo" style="display:none;" role="status" aria-live="polite"></div>
                <table class="table table-hover display compact" id="donationsTransactions" summary="This table display the list of joined groups and chapters">
                    <thead>
                    <tr>
                        <th width="20%" scope="col" tabindex="0" role="button"> <?= gettext('Organizations'); ?></th>
                        <th width="20%" scope="col" tabindex="0" role="button"><?= gettext('Description'); ?></th>
                        <th width="20%" scope="col" tabindex="0" role="button"><?= gettext('Employee Donation'); ?></th>
                        <th width="20%" scope="col" tabindex="0" role="button"><?= gettext('Employer Match'); ?></th>
                        <th width="20%" scope="col" tabindex="0" role="button"><?= gettext('Date/Time'); ?></th>

                    </tr>
                    </thead>
                    <tbody>                  
                        <tr>
                            <td>American Red Cross</td>
                            <td>January Donation</td>
                            <td>$50</td>
                            <td>$50</td>
                            <td>03-09-2022 01:50 pm</td>
                        </tr>
                        <tr>
                            <td>Habitat for Humanity</td>
                            <td>Disaster Relief</td>
                            <td>$200</td>
                            <td>$200</td>
                            <td>02-02-2023 01:50 pm</td>
                        </tr>
                        <tr>
                            <td>Feeding America</td>
                            <td>Food Drive</td>
                            <td>$100</td>
                            <td>$100</td>
                            <td>01-09-2022 01:50 pm</td>
                        </tr>
                        <tr>
                            <td>Kennedy Center</td>
                            <td>Children Performing Arts Fund</td>
                            <td>$50</td>
                            <td>$50</td>
                            <td>01-06-2022 01:50 pm</td>
                        </tr>
                   
                    </tbody>
                </table>
            </div>
                </div>
            </div>
        </div>
       
    </div>
</div>
<?php } ?>

<?php if (
  $_COMPANY->getAppCustomization()['profile']['allow_delegated_access']
  && !$_USER->isDelegatedAccessUser()
) { ?>
  <div class="container container-sub">
    <div class="row col-12">
      <div class="col-12">
        <div class="col-12">
            <h2><?= gettext('Delegated Access') ?></h2>
            <hr class="linec mt-1">
        </div>
      </div>

      <div class="col-12 mt-3">
      <div class="col-12">
        <div class="mb-3">
          <h3 style="font-size: 1.2rem;"><?= gettext('Users with Delegated Access to Act as Me') ?></h3>
            <a tabindex="0" aria-label="<?= gettext('Add a delegate') ?>" href="javascript:void(0);" role="button" onclick="showAddNewGranteeForm(event)">
                <i class="fa fa-plus-circle link-pointer" title="<?= gettext('Add a delegate') ?>" aria-hidden="true" style="font-size: 1.2rem;"></i>
            </a>
        </div>

        <?php if (!isset($my_grantee_users[0])) { ?>
        <div>
          <p class="m-3 alert alert-secondary"><?= gettext('No delegates found') ?></p>
        </div>

        <?php } else { ?>
        <div class="table-responsive">
          <table class="table table-hover display compact" id="mygrantee_users">
            <thead>
              <tr>
                <th width="30%"><?= gettext('Delegate Name') ?></th>
                <th width="30%"><?= gettext('Delegate Email') ?></th>
                <th width="20%"><?= gettext('Access Granted On') ?></th>
                <th width="20%"><?= gettext('Action') ?></th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($my_grantee_users as $delegated_access) { ?>
              <?php
                $my_grantee_user = User::GetUser($delegated_access->val('grantee_userid'));
                if (!$my_grantee_user) continue;
              ?>
              <tr>
                <td><?= $my_grantee_user->getFullName() ?></td>
                <td><?= $my_grantee_user->getEmailForDisplay() ?></td>
                <td>
                  <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($delegated_access->val('createdon'), true, true, true) ?>
                </td>
                <td>
                  <button
                    class="btn btn-danger btn-sm confirm" style="padding-left: 5px!important; padding-right: 5px!important;"
                    onclick="revokeDelegatedAccess('<?= $_COMPANY->encodeId($delegated_access->id()) ?>')"
                    title="<?= gettext('Are you sure you want to revoke access?') ?>"
                    aria-label="<?= gettext('Are you sure you want to revoke access?') ?>"
                  >
                    <?= gettext('Revoke Access') ?>
                  </button>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
        <?php } ?>
      </div>

      <div class="col-12 mt-3">
        <div class="mb-3">
          <h3 style="font-size: 1.2rem;"><?= gettext('Delegated Access Given to Me by Others') ?></h3>
        </div>
        <?php if (!isset($my_grantor_users[0])) { ?>
        <div>
          <p class="m-3 alert alert-secondary"><?= gettext('No grantors found') ?></p>
        </div>
        <?php } ?>
        <?php if (isset($my_grantor_users[0])) { ?>
        <div class="table-responsive">
          <table class="table table-hover display compact" id="mygrantor_users">
            <thead>
              <tr>
                <th width="30%"><?= gettext('Grantor Name') ?></th>
                <th width="30%"><?= gettext('Grantor Email') ?></th>
                <th width="20%"><?= gettext('Access Granted On') ?></th>
                <th width="20%"><?= gettext('Action') ?></th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($my_grantor_users as $delegated_access) {
                $my_grantor_user = User::GetUser($delegated_access->val('grantor_userid'));
                if (!$my_grantor_user->isActive()) {
                    continue;
                }
            ?>
              <tr>
                <td><?= $my_grantor_user->getFullName() ?></td>
                <td><?= $my_grantor_user->getEmailForDisplay() ?></td>
                <td>
                  <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($delegated_access->val('createdon'), true, true, true) ?>
                </td>
                <td>
                  <a class="btn btn-affinity btn-sm confirm" style="padding-left: 5px!important; padding-right: 5px!important;" href="delegated_access_login?delegate_access_token=<?= $delegated_access->getDelegatedAccessToken() ?>" title="<?= sprintf(gettext('Are you sure you want to sign in as %s?'), $my_grantor_user->getFullName()) ?>"
                     aria-label="<?= sprintf(gettext('Are you sure you want to sign in as %s?'), $my_grantor_user->getFullName())  ?>">
                    <?= gettext('Use Delegated Access') ?>
                  </a>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
        <?php } ?>
      </div>
      </div>

    </div>
  </div>

  <div class="modal fade" id="add-new-delegated-access-modal" tabindex="-1" role="dialog" aria-labelledby="add-new-delegated-access-heading" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="add-new-delegated-access-heading">
            <?= gettext('Add New Grantee') ?>
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form onsubmit="addNewDelegatedAccess(event)">
            <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
            <div class="form-group">
              <label for="add-new-delegated-access-search">Search User</label>
              <input type="text" class="form-control" required id="add-new-delegated-access-search" aria-describedby="add-new-delegated-access-search" autocomplete="off" onkeyup="searchUsersForDelegatedAccess(this.value)" placeholder="<?= gettext('Search users by name or email') ?>">
              <div id="show_dropdown"> </div>
            </div>

            <div class="form-group text-center">
              <button type="button" data-dismiss="modal" class="btn btn-secondary"><?= gettext('Cancel') ?></button>&ensp;
              <button type="submit" class="btn btn-primary"><?= gettext('Submit') ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>


  <script>
    function showAddNewGranteeForm(jsevent) {
      Swal.fire({
        title: 'Confirmation',
        html: `
          <div class="text-left" style="font-size: 16px;">
            <?= sprintf(gettext('By adding a new delegate, you authorize them to act on your behalf in this application zone (%1$s), including signing in as you and performing any action.'), $_ZONE->val('zonename')) ?>
            <br>
            <br>
            <?= sprintf(gettext("To confirm you understand and accept this risk, type '%s' (all caps) below."), 'I AGREE') ?>
          </div>
        `,
        input: 'text',
        inputValue: '',
        inputPlaceholder: 'I AGREE',
        confirmButtonText: 'Continue',
        width: 600,
        inputValidator: function (result) {
          if (result !== 'I AGREE') {
            return "<?= sprintf(gettext("You need to type '%s' (all caps)"), 'I AGREE') ?>";
        }
      }
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }

      $('#add-new-delegated-access-modal').modal();
    });
  }

  function searchUsersForDelegatedAccess(value) {
    delayAjax(function(){
      if (value.length < 3) {
        return;
      }

      $.ajax({
        type: 'GET',
        url: 'ajax_delegated_access.php?searchUsersForDelegatedAccess=1',
        data: {
          search_keyword_user: value
        },
        success: function (response) {
          $('#show_dropdown').html(response);
          let myDropDown=$("#user_search");
          let length = $('#user_search> option').length;
          myDropDown.attr('size',length);
        },
      });
    }, 500 );
  }

  function closeDropdown() {
    var myDropDown = $('#user_search');
    var length = $('#user_search> option').length;
    myDropDown.attr('size',0);
  }

  function addNewDelegatedAccess(jsevent) {
    jsevent.preventDefault();

    var form = $(jsevent.target);
    var modal = form.closest('.modal');

    $.ajax({
      url: 'ajax_delegated_access.php?addNewDelegatedAccess=1',
      method: 'POST',
      data: form.serialize(),
      dataType: 'json',
      tskp_submit_btn: form.find('button[type="submit"]'),
      success: function (json) {
        Swal.fire({
          title: json.title,
          text: json.message,
        }).then(function (result) {
          if (json.status === 1) {
            window.location.reload();
          }
        });
      },
    });
  }

  function revokeDelegatedAccess(delegated_access_id) {
    $.ajax({
      url: 'ajax_delegated_access.php?revokeDelegatedAccess=1',
      method: 'POST',
      data: {
        delegated_access_id
      },
      dataType: 'json',
      success: function (json) {
        Swal.fire({
          title: json.title,
          text: json.message,
        }).then(function (result) {
          if (json.status === 1) {
            window.location.reload();
          }
        });
      },
    });
  }

  </script>
<?php } ?>

<?php if ($_COMPANY->getUserLifecycleSettings()['allow_delete'] && $_COMPANY->getAppCustomization()['profile']['allow_account_deletion'] && !$_USER->isDelegatedAccessUser()) { ?>
<!-- Delete my account -->

<div class="container container-sub">
    <div class="col-12">
        <div class="col-12 mx-auto">
            <div class=" delete-account">
                <?= gettext('I no longer wish  to use this platform anymore, and I would like to delete my account along with my personal information.'); ?>
                 &emsp;
                <button class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#DeleteAccount"><?= gettext('Delete my account'); ?></button>
            </div>
        </div>
    </div>
</div>
<?php }
} ?>

<!-- Modals -->
<div tabindex="-1" id="DeleteAccount" class="modal fade">
    <div aria-label="<?= gettext('Confirmation')?>" class="modal-dialog" aria-modal="true" role="dialog" aria-label="Alert Dialog">
        <div class="modal-content">
            <div class="modal-header text-center">
                <h2 class="modal-title"><?= gettext('Confirmation'); ?></h2>
            </div>
            <div class="modal-body">
                <p><?= sprintf(gettext("I understand and agree that all information related to my account will be permanently lost after 30 days and I provide my consent to delete my account. Type '%s' below to provide your consent."),'Delete')?></p>
                <div class="form-group">
                <label for="confirm_delete_account">Type your consent</label>
                    <input type="text" class="form-control" id="confirm_delete_account" onkeyup="initDeleteAccount()" placeholder="Delete" name="confirm_delete_account">
                  </div>
            </div>
            <div class="modal-footer text-center">
                <span id="action_button"><button tabindex="0" class="btn btn-danger no-drop" disabled ><?= gettext('Submit'); ?></button></span>
                <button tabindex="0" type="button" class="btn btn-default" data-dismiss="modal"><?= gettext('Cancel'); ?></button>
            </div>
        </div>
    </div>
</div>
<div tabindex="-1" id="upload_modal" class="modal fade">
    <div aria-label="<?= gettext('Upload Profile Photo')?>" class="modal-dialog" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><?= gettext('Upload Profile Photo'); ?></h2>
                <button tabindex="0" class="showoutline-solid close" aria-label="Close dialog" type="button" data-dismiss="modal">&times;</button>
                
            </div>
            <div class="modal-body">
                <div id="main_upload_page" >
                    <form method="POST" enctype="multipart/form-data" id="uploadprofilePictureForm">
                        <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                        <div class="profile-pic d-block mx-auto">
                            <div class="slim"
                                id="addfocus"
                                 data-label=""
                                 data-max-file-size="2"
                                 data-size="240,240"
                                 data-ratio="1:1">
                                <input tabindex="0" type="file" aria-label="<?= gettext('Drop your image here'); ?>" name="userProfile" id="userProfileImage" accept="image/jpeg,image/png"/>
                            </div>
                        </div>
                        <br>
                        <div class="text-center">
                            <button id="uploadProfilePhoto" tabindex="0" type="button" class="btn btn-primary" onclick="uploadProfilePicture()"><?= gettext('Upload Photo'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Container div for video tags store all html in it for accessibility -->
<div class="datepicker-and-video-tags-html-container"></div>

</main>
<!-- Javascript -->
<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/slim-image-cropper/slim/slim.kickstart.tksp.min.js"></script>
<script>
 
 
    $(document).ready(function () {
        $('#mygrantor_users').DataTable({
            "order": [],
            "bPaginate": false,
            "bInfo": false,
            "bFilter": false
        });
        $('#mygrantee_users').DataTable({
            "order": [],
            "bPaginate": false,
            "bInfo": false,
            "bFilter": false
        });
        var joinedgTable = $('#joined_group_list').DataTable({
            "order": [],
            "bPaginate": true,
            "bInfo": false,
            "bFilter": true,
            "pageLength": 10,
            "aoColumnDefs": [{"bSortable": false, "aTargets": [-1, -2, -3, -4]}],
            'language': {
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },
            "initComplete": function(settings, json) {
                var paginationLinks = $("#joined_group_list_paginate > span > a");
                paginationLinks.each(function() {
                    var pNo = $(this).html();
                    $( this ).attr("aria-label","Page "+pNo) ;
                    $('.current').attr("aria-current","true");
                });
            }
        });

        joinedgTable.on('init.dt', function(){
            var previousButton = document.getElementById('profile_joined_events_table_previous');
            var nextButton = document.getElementById('profile_joined_events_table_next');
            if (previousButton && previousButton.classList.contains('disabled')) {
                    previousButton.removeAttribute('tabindex', '-1');
            } 
            if (nextButton && nextButton.classList.contains('disabled')) {
                nextButton.removeAttribute('tabindex', '-1');
            } 
            // For disabling the headings where there is no sorting
            var sortingDisabledElements = document.querySelectorAll('.sorting_disabled');
            sortingDisabledElements.forEach(function (element){
               element.setAttribute('tabindex', '-1');
            }); 
        });

        var dCount = joinedgTable.rows().count();
        var resultVerb = ' row is';
        if (dCount>1){
            resultVerb = ' rows are'
        }
        $('#joinedGroupInfo').html(dCount+ resultVerb+' available');
        $(".teleskope-select2-dropdown").select2({width: 'resolve'});


        // Define both sections
        const timesection = $("#timezone-section").children();
        const languagesection = $("#language-section").children();
        // target childs
        let timeElement = timesection.find(".select2-selection--single");
        let languageElement = languagesection.find(".select2-selection--single");
        // set aria of both section
        timeElement.attr( { 'aria-labelledby':"Timezone", 'aria-expanded':"false", 'aria-readonly':"true", 'aria-disabled':"false" } );
        languageElement.attr( { 'aria-labelledby':"default-language", 'aria-expanded':"false", 'aria-readonly':"true", 'aria-disabled':"false" } );

        $('#pointsTransactions').DataTable({
            order: [],
            bPaginate: true,
            bInfo: false,
            pageLength: 10
        });

        $('#donationsTransactions').DataTable({
            order: [],
            bPaginate: true,
            bInfo: false,
            pageLength: 10
        });

        $('.confirm').popConfirm({content: ''});
    });

  
    function uploadProfileImage() {
        $('#upload_modal').modal({
            backdrop: 'static',
            keyboard: false
        });
        setTimeout(() => {
            $(".close").focus();    
        }, 500);

        
    }
   
    function removeProfilePicture(){
        $.ajax({
            url: 'ajax.php',
            type: "POST",
            data: 'removeProfilePicture=1',
            success : function(data) {
                location.reload();
            }
        });
    }
   

    function changeSetting(e, m, f) {
        let v = 0;
        if (e.checked) {
            v = 1;
        }
        updateUsersEmailNotifictionSetting(v, m, f);
    }
    
</script>
<script>
    retainFocus("#upload_modal");
</script>
<script>
    let parent = document.getElementById("addfocus"),
drag = document.getElementById("userProfileImage");
parent.addEventListener("drop", () => {
	$('#uploadProfilePhoto').removeAttr('disabled');
});
        $('#userProfileImage').on("change",
            function(){
                if ($(this).val()) {
                    $('#uploadProfilePhoto').removeAttr('disabled');
                    $('#uploadProfilePhoto').focus();
                }
            }
        );

  </script>
<script>
    document.getElementById('userProfileImage').addEventListener('focus', function() {
        document.getElementById('addfocus').classList.add("fileupload-outline");
    });
    document.getElementById('userProfileImage').addEventListener('blur', function() {
        document.getElementById('addfocus').classList.remove("fileupload-outline");
      
    });

$(document).ready(function() {
    $('.fa-question-circle').removeClass('mobile-off');
    
    $('.select2-selection__rendered').click(function() {
        $('.select2-results__options--nested').attr('role', 'listbox');
    });

    $('#timezone-section .select2-selection__rendered').click(function() {        
        $('.select2-search__field').attr('aria-label', 'Search Timezone');
    });

    <?php if ($_COMPANY->getAppCustomization()['profile']['enable_bio']) { ?>
    $('#redactor_content').initRedactor('redactor_content','user_bio',['counter','table']);
    $(".redactor-voice-label").text("<?= gettext('Add bio');?>");
    <?php } ?>

});
trapFocusInModal("#upload_modal");

<?php if ($_COMPANY->getAppCustomization()['profile']['enable_bio']) { ?>
function showHideUpdateBioForm() {
    if ($("#bio_container").is(":visible")) {
        $("#bio_container").hide();
        $("#bio_form").show();
        $("#bioToggleBtn").hide();
    } else {
        $("#bio_form").hide();
        $("#bio_container").show();
        $("#bioToggleBtn").show();
    }
}
<?php } ?>

<?php if ($_COMPANY->getAppCustomization()['profile']['enable_bio']) { ?>
function updateUserBio(){
    let bio = $("#redactor_content").val();
    $.ajax({
		url: 'ajax.php?updateUserBio=1',
        type: "POST",
		data: {bio:bio},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
                        window.location.reload();
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
    });
}
<?php } ?>

</script>