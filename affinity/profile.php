<?php
require_once __DIR__.'/head.php';
global $_COMPANY, $_ZONE, $_USER;

if (!defined('INDEX_PAGE') && empty ($_ZONE)) { // User is logged in, but for some reason the $_ZONE is not set, logout and retry
  Http::Redirect('logout');
}

$htmlTitle = sprintf(gettext('Profile Page for %1$s | %2$s'), $_USER->getFullName(), $_COMPANY->val('companyname'));

$banner	  = $_ZONE->val('banner_background');
// Since we do not have profile on/off setting in the zone, we will use calendar_page_banner_title check,
// if it is empty then we will not show Profile label, else we will show (ouch ... lazy implementation).
$bannerTitle = empty($_ZONE->val('calendar_page_banner_title')) ? '' : gettext('Profile');
$allowedLanguages = $_COMPANY->getValidLanguages();
$mywebconferenceurl = $_USER->getUserPreference(UserPreferenceType::MyWebConferenceURL) ?? '';
$mywebconferencedetail = $_USER->getUserPreference(UserPreferenceType::MyWebConferenceDetail) ?? '';

if (isset($_GET['edit'])) {
    $htmlTitle = sprintf(gettext('Update Profile for %1$s | %2$s'), $_USER->getFullName(), $_COMPANY->val('companyname'));
}

if (isset($_POST['update'])){

  
  $error_message = array();
  $default_language = 'en';

  if (isset($_POST['firstname']) || isset($_POST['lastname'])) {
      $firstname	= Sanitizer::SanitizePersonName($_POST['firstname']);
      $lastname 	= Sanitizer::SanitizePersonName($_POST['lastname']);    

      if($firstname != $_POST['firstname']){
        $error_message[] = gettext("The first name you have entered is invalid. Please correct the information and resubmit.");
      }
      if($lastname != $_POST['lastname']){
        $error_message[] = gettext("The last name you have entered is invalid. Please correct the information and resubmit.");
      } 
    
  }else{
      $firstname = $_USER->val('firstname') ?? '';
      $lastname = $_USER->val('lastname') ?? '';     
  }

  if(isset($_POST['pronouns'])){
    $pronouns 	= Sanitizer::SanitizePersonPronouns($_POST['pronouns']);
  }else{
    $pronouns = $_USER->val('pronouns') ?? '';
  }
  
  if (isset($_POST['date_format'])){
      $date_format = $_POST['date_format'];
  }else{
      $error_message[] = gettext("Something wrong with date format.");
  }
  if (isset($_POST['time_format'])){
    $time_format = $_POST['time_format'];
  }else{
      $error_message[] = gettext("Something wrong with time format.");
  }
	//	$jobtitle	= $_POST['jobtitle'];
  //  $homeoffice = $_COMPANY->decodeId($_POST['homeoffice']);
	//	$branch		= $_COMPANY->getBranch($homeoffice);
	//	$regionid	= $branch? $branch->val('regionid'): '';
		$timezone	= $_POST['timezone'];
	//	$department = $_COMPANY->decodeId($_POST['department']);
	//	$file 	    =	basename(@$_FILES['picture']['name']);

  if (!isValidTimeZone($timezone)){
    $error_message[] = gettext("Timezone is incorrect.");
  }
  if (empty($error_message )) {
    $_SESSION['timezone'] = $timezone;

    if (!empty($_POST['default_language']) && $_COMPANY->isValidLanguage($_POST['default_language'])){
      $default_language = $_POST['default_language'];
    }

    $_USER->updateProfileSelf($firstname, $lastname, $pronouns, $default_language, $timezone, $date_format, $time_format);

    if (!empty($_POST['homezone']) && ($homezone = $_COMPANY->decodeId($_POST['homezone']))>0){
      $_USER->changeHomeZone($homezone,$_ZONE->val('app_type'));
    }

    $mywebconferenceurl = $_USER->getUserPreference(UserPreferenceType::MyWebConferenceURL) ?? '';
    $new_mywebconferenceurl  = empty($_POST['mywebconferenceurl']) ? null : $_POST['mywebconferenceurl'];
    if ($mywebconferenceurl != $new_mywebconferenceurl) {
        $_USER->setUserPreference(UserPreferenceType::MyWebConferenceURL, $new_mywebconferenceurl);
    }

    $mywebconferencedetail = $_USER->getUserPreference(UserPreferenceType::MyWebConferenceDetail) ?? '';
    $new_mywebconferencedetail  = empty($_POST['mywebconferencedetail']) ? null : $_POST['mywebconferencedetail'];
    if ($mywebconferencedetail != $new_mywebconferencedetail) {
      $_USER->setUserPreference(UserPreferenceType::MyWebConferenceDetail, $new_mywebconferencedetail);
    }
    $_USER->clearSessionCache();

    $_SESSION['profile_updated'] = time();
    Http::Redirect("profile");
  } else {
    $error_message = implode('<br>',$error_message);
  }
}

$mygroups = Group::GetUserMembershipGroupsChaptersChannelsByZone($_USER->id(), $_ZONE->id());


/**
 * TODO - Fix the FE user-facing points-view
 * This is still a WIP page as we have hardcoded lorem-ipsum text in the page
 */
$pointsTransactions = [];
// $pointsTransactions = PointsTransaction::GetAllTransactions();


$my_grantor_users = DelegatedAccess::GetGrantorUsersByUserid(grantee_userid: $_USER->id(), zoneid: $_ZONE->id());
$my_grantee_users = DelegatedAccess::GetGranteeUsersByUserid(grantor_userid: $_USER->id(), zoneid: $_ZONE->id());

include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/profile_html.php');
include(__DIR__ . '/views/footer_html.php');

?>
