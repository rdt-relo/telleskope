<?php
ob_start();
require_once __DIR__ . '/../include/Company.php';
// IMPORTANT: For this module all time operations are on UTC. Any time translations for the device will be done by application
date_default_timezone_set("UTC");
//--------------------------ERG-SURVEY API INDEX.PHP------------------------------//
$db = new Hems();
$get = $_REQUEST;
$get = $db->cleanInputs($get);
$exemptedMethod = array('completeLoginAfterWebAuth', 'getSystemMessages');
$_COMPANY = null;
$_USER = null;
$_ZONE = null;
$sessionkey = 0;

$post_css = '<style type="text/css"> .post-inner { margin: 5%; } .post-inner figure { margin: 0 !important; padding: 0 !important; } .post-inner figcaption { padding: 5px 5px 5px 5px !important; text-align: center !important; font-size: 12px !important; color: #808080 !important; } .post-inner img { max-width: 100% !important; margin: 0 auto !important; display: block !important; } .post-inner a { color: #0077b5 !important; } .post-inner ol { list-style-type: decimal !important; padding-left: 0 !important; padding-top: 0 !important; margin-left: 30px !important; margin-top: 5px !important; color: #505050 !important; line-height: 1.5em !important; } .post-inner ul { list-style-type: disc !important; padding-left: 0 !important; padding-top: 0 !important; margin-left: 30px !important; margin-top: 5px !important; color: #505050 !important; line-height: 1.5em !important; } .post-inner hr { width: 100% !important; border-top: 1px solid #505050 !important; height: 0 !important; } .post-inner p { margin: 1em 0 !important; color: #505050 !important; line-height: 1.5em !important; } .post-inner blockquote { font-style: italic !important; background: #f9f9f9 !important; border-left: 10px solid #ccc !important; margin: 1.5em 10px !important; padding: 0.5em 10px !important; line-height: 1.5em !important; } .post-inner blockquote p { display: inline !important; } td img { width: 600px !important; } </style> <div class="post-inner">';

/**
 * Main Controller
 */
if (!empty($get['method'])) {
    $method = $get['method'];
    Logger::Log("API - ${method}|" . json_encode($get), Logger::SEVERITY['INFO']);

    if (!in_array($method, $exemptedMethod)) {

        $bearerToken =  Teleskope::getBearerToken();
        if (!$bearerToken){
            exit(MobileAppApi::buildApiResponseAsJson($method, '', 0, gettext('Authentication Token is Invalid'), 400));
        }
        [$companyid,$userid,$sessionkey] = explode(':',encrypt_decrypt($bearerToken,2));
        $checkRequired = $db->checkRequired(array('userid' => $userid, 'sessionkey' => $sessionkey));
        
        if ($checkRequired) {
            exit(MobileAppApi::buildApiResponseAsJson($method, '', 0, gettext('Authentication Token is Invalid'), 400));
        }

        // Note: RestoreSession will initialize $_USER, $_COMPANY, and $_ZONE
        if (!($user = User::RestoreSession($userid, $sessionkey)) || $user->cid() != $companyid) {
            exit(MobileAppApi::buildApiResponseAsJson($method, '', 0, gettext('Session not found'), 401));
        } else {
            $_USER = $user;
        }
        setLangContext($_USER->val('language'));
    } else {
        if ($method == 'completeLoginAfterWebAuth') {
            $check = array('auth_token' => @$get['auth_token']);
            if ($db->checkRequired($check)) {
                exit(MobileAppApi::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$db->checkRequired($check)), 200));
            }

            $val_str = aes_encrypt($get['auth_token'], TELESKOPE_USERAUTH_API_KEY, "u7KD33py3JsrPPfWCilxOxojsDDq0D3M", true);
            if (empty($val_str)) { // Invalid token
                exit(MobileAppApi::buildApiResponseAsJson($method, '', 0, gettext('Authentication Token is Invalid'), 400));
            }
            $vals = json_decode($val_str, true);
            if (!count($vals) || empty($vals['u']) || empty($vals['c']) || (time() - $vals['now'] > 10)) {
                // Invalid token, note:
                //      token expires after 10 seconds
                //      token is valid only for the session that initiatied it, identified with 'as' key.
                //      'as' key will change if the user tried starting the session with one domain and used login username password that takes him to another.

                Logger::Log("Invalid Navite-App Token received at " . time() . ", Token = " . $val_str);
                exit(MobileAppApi::buildApiResponseAsJson($method, '', 0, gettext('Invalid or Expired Authentication Token'), 200));
            }
            $companyid = $vals['c'];
            $userid = $vals['u'];
            $app_type = $vals['app'] ?? 'affinities';
            $_COMPANY = Company::GetCompany($companyid);
            $_USER = User::GetUser($userid);
            $zoneid = $_USER->getMyConfiguredZone($app_type);
            if (!$zoneid) {
                // This is the first time the user is sigining in so lets assign a default zone if one exists.
                $zones = array_values($_COMPANY->getHomeZones('affinities'));
                // Assign the first zone.
                // Note in web application we assign a zone only if there is one home zone but,
                // here we will just assign the first zone as we currently do not have capability to allow user to choose a zone.
                // Todo improve it in the future to ask the user to choose a zone.
                //
                if (!empty($zones)) {
                    $zoneid = $zones[0]['zoneid'];
                    $_USER->addUserZone($zoneid, true, true);
                }
            }
            if ($zoneid) {
                $_ZONE = $_COMPANY->getZone($zoneid);
            } else {
                exit(MobileAppApi::buildApiResponseAsJson($method, '', 0, gettext('Unable to assign a zone, please sign in into web application to complete your account setup.'), 200));
            }

            $check = array('devicetype' => @$get['devicetype'], 'devicetoken' => @$get['devicetoken']);
            $checkRequired = $db->checkRequired($check);
            if ($checkRequired) {
                exit(MobileAppApi::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
            } else {
                $devicetype = $get['devicetype'];
                $devicetoken = $get['devicetoken'];
                if ($_USER) {
                    $sessionkey = $_USER->updateAppSession($devicetype, $devicetoken);
                    $userid = $_COMPANY->encodeId($_USER->id());
                    exit(MobileAppApi::buildApiResponseAsJson($method, array('authorization_token' => encrypt_decrypt($_COMPANY->id() . ':' . $_USER->id() . ':' . $sessionkey, 1),'userid'=>$userid), 1, gettext("Welcome! You have signed in successfully."), 200));
                } else {
                    exit(MobileAppApi::buildApiResponseAsJson($method, '', 0, gettext('Your mobile application session has expired. Please sign in again.'), 401));
                }
            }

        }
    }

    if (method_exists('MobileAppApi', $method)) {
        $apiObject = new MobileAppApi($sessionkey,$get);
        $execute = $apiObject->$method($apiObject->getFields);
    } else {
        exit(MobileAppApi::buildApiResponseAsJson('', '', 0, gettext('Method not exist'), 400));
    }
} else {
    exit(MobileAppApi::buildApiResponseAsJson('', '', 0, gettext('Please select a method'), 400));
}

class MobileAppApi
{
    private $companyData = array();
    private $userData = array();
    private $zoneData = array();
    public $userSessionkey;
    public $getFields = array();

    public function __construct(int $sid, array $parameters) {
		$this->userSessionkey = $sid;
        $this->getFields = $parameters;
	}

    public function getUserLatestData($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getUserLatestData";

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $userProfile = $this->userObjToArray($_USER);
        $companyData = $this->companyObjToArray();
        $ZoneData = $this->zoneObjToArray();

        if (!empty($ZoneData)){
            unset($ZoneData['customization']['app']['locales']);
            $ZoneData['customization']['app']['languages_allowed'] = $_COMPANY->getValidLanguages();
            $ZoneData['appUrl'] = $_COMPANY->getAppURL($_ZONE->val('app_type'));
            $ZoneData['appUrlBase'] = $_COMPANY->getAppURLBase($_ZONE->val('app_type'));
            // To show the list of all categories
            $ZoneData['groupCategories'] = Group::GetAllGroupCategories(true);
        }
        // check if inbox feature available

        $userInbox = array('inbox' => false);
        if ($_USER->isUserInboxEnabled()){
            $userInbox['inbox'] = true;
        }

        if ($userProfile) {

            $branchData = $_COMPANY->getBranch($_USER->val('homeoffice'));
            $branchName = "";
            $branchAddress = "";
            if($branchData){
                $branchName = $branchData->val('branchname');
                $branchAddress = trim($branchData->val('street').', '.$branchData->val('city').', '.$branchData->val('state').', '.$branchData->val('zipcode'),', ');
            }

            $userProfile['userid'] = $_COMPANY->encodeId($userProfile['userid']);
            $userProfile['sessionkey'] = (string) $this->userSessionkey;
            $userProfile['branchName'] = $branchName;
            $userProfile['branchAddress'] = $branchAddress;
            $userProfile['department'] = htmlspecialchars_decode((string) ($_USER->getDepartmentName() ?? ''));
            $userProfile['allowProfileUpdate'] = array('firstname'=>true,'lastname'=>true,'pronouns'=> ($_COMPANY->getAppCustomization()['profile']['enable_pronouns'] ? true : false),'homeoffice'=>false,'jobtitle'=>false,'department'=>false);
            $userProfile['allowProfilePictureUpdate'] = true;
            $userProfile['notification'] =(int) $userProfile['notification'];
            $homezone = $_COMPANY->getZone($_USER->getMyConfiguredZone('affinities'));
            $userProfile['homezonename'] =$homezone ? $homezone->val('zonename') : '';
        }
        $data = array("profile" => $userProfile, 'company' => $companyData, 'zone' => $ZoneData, 'inbox' => $userInbox);
        exit(self::buildApiResponseAsJson($method, $data, 1, gettext('Profile data'), 200));
    }

    /**
     * Convert response to JSON
     * The processing logic on the device side for $http_code (statusCode) is
     * ** switch (statusCode) {
     * **     case 400:
     * **         throw (msg != "" ? msg : "Cannot process your request");
     * **     case 401:
     * **         AppConfig.showToast(msg != "" ? msg : "Something went wrong");
     * **         LogOut();
     * **         break;
     * **     case 403:
     * **         throw (msg != "" ? msg : "Permission denied");
     * **     case 301:
     * **         break;
     * **     case 302:
     * **         break;
     * **     default:
     * **         throw (msg != "" ? msg : 'Something went wrong');
     * ** }
     * @param string $method name of the method to be sent back to client
     * @param string $data the data to be sent back to client
     * @param int $success 1 on success, 0 on error
     * @param string $message message that can be displayed by the application
     * @param int $http_code
     * @return false|string
     */
    public static function buildApiResponseAsJson($method = '', $data = '', $success = 1, $message = '', $http_code = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($http_code);
        $response = array();
        $response['message'] = $message;
        $response['success'] = $success;
        $response['apiFetchTime'] = time();
        $response['cacheExpiryTime'] = $response['apiFetchTime'] + 3600;
        if ($method) {
            $response['method'] = $method;
        }
        if ($data) {
            $response['data'] = $data;
        }
        //Logger::Log("Returning: ".json_encode($response));
        return json_encode($response);
    }

    private function userObjToArray($user)
    {
        global $_COMPANY, $_ZONE;

        $retVal = array(
            'userid' => $user->val('userid'),
            'firstname' => htmlspecialchars_decode($user->val('firstname') ?? ''),
            'lastname' => htmlspecialchars_decode($user->val('lastname') ?? ''),
            'pronouns' => htmlspecialchars_decode($user->val('pronouns') ?? ''),
            'email' => htmlspecialchars_decode($user->val('email') ?? ''),
            'isZoneAdmin' => $user->isAdmin(),
            'picture' => $user->val('picture'),
            'jobtitle' => htmlspecialchars_decode($user->val('jobtitle') ?? ''),
            'branchName' => $_COMPANY->getBranchName($user->val('homeoffice')),
            'department' => htmlspecialchars_decode($_COMPANY->getDepartmentName($user->val('department')) ?? ''),
            'defaultLanguage' => $user->val('language'),
            'notification' => $user->val('notification')
        );

        if (!$_COMPANY->getAppCustomization()['profile']['enable_pronouns']) {
            $retVal['pronouns'] = '';
        }
        return $retVal;

    }

    private function companyObjToArray()
    {
        global $_COMPANY;
        $vendorSupport = $_COMPANY->val('vendor_support_email') ?: 'support@teleskope.atlassian.net';
        $vendorSupport = 'mailto:'.$vendorSupport.'?subject='.$_COMPANY->val('companyname').' App Support';

        $loginMethods = $_COMPANY->getCompanyLoginMethods('affinities', 1);
        if (empty($this->companyData)) {
            $this->companyData = array(
                'companyid' => $_COMPANY->val('companyid'),
                'companyname' => htmlspecialchars_decode($_COMPANY->val('companyname') ?? ''),
                'logo' => $_COMPANY->val('logo'),
                'vendor_support' => $vendorSupport,
                'loginmethod' =>   !empty($loginMethods) ? $loginMethods[0]['loginmethod'] : 'username'
            );

        }
        return $this->companyData;
    }

    private function zoneObjToArray()
    {
        global $_COMPANY, $_ZONE;

        if (empty($this->zoneData)) {
            $this->zoneData = array(
                'zoneid' => $_ZONE->val('zoneid'),
                'zonename' => htmlspecialchars_decode($_ZONE->val('zonename') ?? ''),
                'app_type' => $_ZONE->val('app_type'),
                'customization' => $_ZONE->val('customization'),
                'banner_background' => $_ZONE->val('banner_background'),
                'banner_title' => htmlspecialchars_decode($_ZONE->val('banner_title') ?? ''),
                'banner_subtitle' => htmlspecialchars_decode($_ZONE->val('banner_subtitle') ?? ''),
                'show_group_overlay' => $_ZONE->val('show_group_overlay'),
                'group_landing_page' => $_ZONE->val('group_landing_page')
            );
        }
        return $this->zoneData;
    }

    /**
     * User Logout method
     * @param $get
     * @param $this
     */
    public function userLogOut($get)
    {
        global $_USER;
        $method = "userLogOut";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }

        if ($_USER->appLogout($this->userSessionkey)) {
            exit(self::buildApiResponseAsJson($method, '', 1, gettext('You are now signed out. See you later'), 200));

        } else {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong. Please try again.'), 200));
        }
    }

    /**
     * Turn notifications on/off
     * @param $get
     * @param $this
     */
    public function notificationOnOff($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "notificationOnOff";

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }

        $notification = $_USER->val('notification');
        if ($notification == 1) { // Set OFF
            $status = '2';
            $message = gettext('Notifications are OFF now.');

        } else { // set ON
            $status = '1';
            $message = gettext('Notifications are ON now.');
        }
        $_USER->updateNotificationStatus($status);
        exit(self::buildApiResponseAsJson($method, '', 1, $message, 200));
    }

    /**
     * For home office dropdown
     * @param $get
     * @param $this
     */
    public function getHomeOfficeDropdown($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getHomeOfficeDropdown";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $branches = $_COMPANY->getAllBranches();
        exit(self::buildApiResponseAsJson($method, $branches, 1, gettext('All branches list'), 200));
    }

    public function uploadProfilePicture($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "uploadProfilePicture";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('picture' => @$_FILES['picture']['tmp_name']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            $file = basename($_FILES['picture']['name']);
            $size = $_FILES['picture']['size'];
            $tmp = $_FILES['picture']['tmp_name'];
            $ext = $db->getExtension($file);
            $valid_formats = array("jpg", "png", "jpeg", "PNG", "JPG", "JPEG");

            if (in_array($ext, $valid_formats)) {

                if ($size < (2 * 1024 * 1024)) {
                    $s3_file = 'profile_' . teleskope_uuid() . "." . $ext;
                    $tmp = $_COMPANY->resizeImage($tmp, $ext, 240);

                    try {
                        $s3_url = $_COMPANY->saveFile($tmp, $s3_file, 'USER');

                        $updateStatus = $_USER->updateProfilePicture($s3_url);

                        //Next delete the old picture if the update was successful
                        if ($updateStatus && $_USER->has('picture')) {
                            $_COMPANY->deleteFile($_USER->val('picture'));
                        }

                        exit(self::buildApiResponseAsJson($method, ['picture' => $s3_url], 1, gettext('Profile picture uploaded successfully'), 200));

                    } catch (S3Exception $e) {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Uploading error! Please try again'), 200));
                    }
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Maximum allowed size of profile picture is 2MB'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Only .jpg,.jpeg,.png files are allowed'), 200));
            }
        }
    }

    public function updateUserProfile($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "updateUserProfile";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $firstname = $get['firstname'] ?? '';
        $lastname = $get['lastname'] ?? '';
        $pronouns = $get['pronouns'] ?? '';
        $homeoffice = isset($get['homeoffice']) ? $_COMPANY->getBranchName($get['homeoffice']) : '';
        $jobtitle = $get['jobtitle'] ?? '';
        $department = isset($get['department']) ? $_COMPANY->getDepartmentName($get['department']) : '';
        $defaultLanguage = isset($get['defaultLanguage']) ? $get['defaultLanguage'] : $_USER->val('language');
        $update1 = 0;
        $update2 = 0;
        if (!empty($firstname) || !empty($lastname)) {
            $update1 = $_USER->updateProfileSelf($firstname, $lastname, $pronouns, $defaultLanguage);
        }
        if (!empty($homeoffice) || !empty($jobtitle) || !empty($department)) {
            $update2 = $_USER->updateProfile2('', '', '', '', $jobtitle, $department, $homeoffice, '', '', '', '', '', '', '', '', true, null, null, null);
        }
        if ($update1 || $update2) {
            if (!empty($get['homezoneid']) && ($zoneid = $get['homezoneid'])>0){
                $_USER->changeHomeZone($zoneid,'affinities');
            }
            exit(self::buildApiResponseAsJson($method, '', 1, gettext('Profile updated successfully'), 200));
        } else {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong. Please try again.'), 200));
        }
    }

    /**
     *    Get All Groups
     *        Group Structure - On Application Start
     *        Group Details
     *        List of all chapters and chapter details
     *        List of all channels and channel details
     *        Group My membership  - On Application and refresh on Join/Leave
     *        Group strucutre with chapters and channels, and list of chaterps /channels joined, and which can be joined
     *        { 'groupid': {'joined': no, 'canjoin': no}, chapters: { }}
     *        Group Data Section Based (announcement, events, highlights, newsletters)
     * @param $get
     * @param $this
     */
    public function getAllGroups($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getAllGroups";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }

        // setting category id while fetching groups 
        $group_category_id = $get['group_category_id'] ?? 0;
        $groups = Group::GetAllGroupsByCompanyid($_COMPANY->id(), $_ZONE->id(), true, $group_category_id);
        $allGroups = [];
        $joinedGroups = [];

        foreach ($groups as $group) {
            $groupArray = $this->groupObjToArray($group);
            $groupArray['isGroupMember'] = $_USER->isGroupMember($group->id());
            $groupArray['membersCount'] = (int)$group->getGroupMembersCount();

            $allChapters = Group::GetChapterList($group->id());
            $allChannels = Group::GetChannelList($group->id());
            $groupArray['chaptersCount'] = count($allChapters) ?? 0;
            $groupArray['channelsCount'] = count($allChannels) ?? 0;

            $allChaptersArray = array();
            if ($allChapters) {
                foreach ($allChapters as $chapter) {
                    $chapterCleanArray = $this->chapterObjToArray($chapter);
                    $chapterCleanArray['isChapterMember'] = $_USER->isGroupMember($group->id(), $chapter['chapterid']);
                    $allChaptersArray[] = $chapterCleanArray;
                }
            }
            $groupArray['chapters'] = $allChaptersArray;

            $allChannelsArray = array();
            if ($allChannels) {
                foreach ($allChannels as $channel) {
                    $channelCleanArray = $this->channelObjToArray($channel);
                    $channelCleanArray['isChannelMember'] = $_USER->isGroupChannelMember($group->id(), $channel['channelid']);
                    $allChannelsArray[] = $channelCleanArray;
                }
            }
            $groupArray['channels'] = $allChannelsArray;
           
            if ($groupArray['isGroupMember']) {
                $joinedGroups[] = $groupArray;
            }
            if ($group->val('group_type') != Group::GROUP_TYPE_INVITATION_ONLY){
                $allGroups[] = $groupArray;
            }
        }

        $bannerMessage = new stdClass;
        if (count($joinedGroups)<1) {
            $bannerMessage = [
                'message' => sprintf(gettext('Click on any %s tile to join!'), $_COMPANY->getAppCustomization()['group']['name-short']),
            ];
        }

        if (count($allGroups)) {
            $view_type = $_ZONE->val('app_type') == 'affinities' ? 'carousel' : 'carousel';  //'grid' For now we are showing carousel for all app type
            $show_home_feeds = $_COMPANY->getAppCustomization()['mobileapp']['show_global_feed']; // Use global feed setting specific to mobile app.
            $show_group_filter = $_ZONE->val('app_type') == 'affinities' ? true : false;

            exit(self::buildApiResponseAsJson($method, ['allGroups' => $allGroups, 'joinedGroups' => $joinedGroups, 'bannerMessage' => $bannerMessage, 'show_group_filter'=>$show_group_filter, 'show_home_feeds' => $show_home_feeds, 'view_type' => $view_type], 1, gettext('All groups'), 200));
        } else {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('No groups found'), 200));
        }
    }

    private function groupObjToArray($group)
    {
        global $post_css;
        global $_COMPANY, $_ZONE;
        return array(
            'groupid' => $group->val('groupid'),
            'group_type' => $group->val('group_type'),
            'group_category' => $group->val('group_category'),
            'groupname' => $group->val('groupname'),
            'groupname_short' => $group->val('groupname_short'),
            'abouttitle' => $group->val('abouttitle'),
            'aboutgroup' => $post_css . '<div class="post-inner">' . $group->val('aboutgroup') . '</div>',
            'about_show_members' => $group->val('about_show_members'),
            'coverphoto' => $group->val('app_coverphoto') ? : $group->val('coverphoto'),
            'sliderphoto' => $group->val('app_sliderphoto') ? : $group->val('sliderphoto'),
            'sliderphoto_300W' => $group->val('sliderphoto') ?: '',
            'overlaycolor' => $group->val('overlaycolor'),
            'overlaycolor2' => $group->val('overlaycolor2'),
            'groupicon' => $group->val('groupicon'),
            'permatag' => $group->val('permatag'),
            'show_overlay_logo' =>$group->val('show_app_overlay_logo'),
            'chapter_assign_type' =>$group->val('chapter_assign_type')

        );
    }

    private function chapterObjToArray($chapter)
    {
        global $post_css;
        global $_COMPANY, $_ZONE;
        return array(
            'chapterid' => $chapter['chapterid'],
            'chaptername' => $chapter['chaptername'],
            'colour' => htmlspecialchars_decode($chapter['colour'] ?? ''),
            'about' => $post_css . '<div class="post-inner">' . $chapter['about'] . '</div>',
            'region' => $chapter['region'] ?? '',
            'branchids' => $chapter['branchids'],
            'branchname' => $chapter['branchname'] ?? '',
            'isactive' => $chapter['isactive'],
        );
    }

    private function channelObjToArray($channel)
    {
        global $post_css;
        global $_COMPANY, $_ZONE;
        return array(
            'channelid' => $channel['channelid'],
            'channelname' => $channel['channelname'],
            'colour' => htmlspecialchars_decode($channel['colour'] ?? ''),
            'about' => $post_css . '<div class="post-inner">' . $channel['about'] . '</div>',
            'isactive' => $channel['isactive']
        );
    }

    /**
     * @param $get
     * @param $this
     */
    public function viewSelectedGroup($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "viewSelectedGroup";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'], 'page' => @$get['page'], 'section' => @$get['section']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $page = intval($get['page']);
            $groupid = intval($get['groupid']); // handling negative value for group if ever used.
            $section = intval($get['section']);
            $start = ($page - 1) * 30;
            $end = 30;
            // this will handle any negative value for chapter/channel ids as 0
            $chapterid = intval($get['chapterid'] ?? 0);
            $channelid = intval($get['channelid'] ?? 0);
            $api_auth_token_for_album_video_player = $_COMPANY->encryptArray2String([
                'companyid' => $_COMPANY->id(),
                'userid' => $_USER->id(),
                'valid_for' => 'album_video_player',
                'valid_until' => time() + 604800 // 7 days
            ]);

            if ($section == 1) { // Group Detail && Discussion
                $group = Group::GetGroup($groupid);
                
                if ($group && $group->val('isactive') == 1) {
                    // Note we are doing something unconventional ... we are changing a $_ZONE!!! not a great idea.
                    if ($_ZONE->id() != $group->val('zoneid')){
                        $_ZONE = $_COMPANY->getZone($group->val('zoneid'));
                        if (!$_ZONE) {
                            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                        }
                    }
                    $data = Group::GetGroupDetail($groupid);
                    $data[0]['joinStatus'] = $_USER->isGroupMember($data[0]['groupid']);
                    $data[0]['myLeadStatus'] = $_USER->isGrouplead($data[0]['groupid']);
                    $data[0]['addedby'] = $_COMPANY->encodeId($data[0]['addedby']);
                    $data[0]['coverphoto'] = $data[0]['app_coverphoto'] ? : $data[0]['coverphoto'];
                    $data[0]['sliderphoto'] = $data[0]['app_sliderphoto'] ? : $data[0]['sliderphoto'];
                    $data[0]['sliderphoto_300W'] =  $data[0]['sliderphoto'] ?: '';
                    $data[0]['show_overlay_logo'] = $data[0]['show_app_overlay_logo'];
                    $data[0]['joinRequestStatus'] = Team::GetRequestDetail($groupid,0) ? 1 : 0;
                    $data[0]['required_to_join_chapter'] = '';

                    // If needed, insert API auth token for video player
                    if (strpos ($data[0]['aboutgroup'], '/album_video_player?') !== false) {
                        $data[0]['aboutgroup'] = str_replace('/album_video_player?', '/album_video_player?api_auth_token='.$api_auth_token_for_album_video_player.'&',$data[0]['aboutgroup']);
                    }

                    if ($_USER->isGroupMember($groupid) && $group->val('group_type')!=Group::GROUP_TYPE_MEMBERSHIP_DISABLED){
                        if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && !empty(Group::GetChapterList($groupid))){
                            $selectedChapters = $_USER->getFollowedGroupChapterAsCSV($groupid) ?? '';
                            if (!$selectedChapters && $data[0]['chapter_assign_type'] == 'by_user_atleast_one') {
                                $data[0]['required_to_join_chapter'] ='You need to join one or more '.$_COMPANY->getAppCustomization()['chapter']['name-short-plural'];
                            } elseif (!$selectedChapters && $data[0]['chapter_assign_type'] == 'by_user_exactly_one') {
                                $data[0]['required_to_join_chapter'] = 'You need to join a '.$_COMPANY->getAppCustomization()['chapter']['name-short'];
                            } 
                        }
                    } 
                    
                    $data[0]['can_create_discussion'] = $_USER->isGroupMember($groupid) || $_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid);
                    $data[0]['allowed_chapters_channels'] = $this->filterChapterAndChannelByPermission($groupid,'create');
                    $data[0]['show_manage_membership'] = $_ZONE->val('app_type') != 'talentpeak';
                    $data[0]['is_team_enabled'] = $group->isTeamsModuleEnabled();
                    $data[0]['request_role_button_label'] = !empty(Team::GetUserJoinRequests($groupid,0,0)) ? gettext('Manage Registration') : gettext('Register') ;
                    
                    $programTyes = array_flip(Team::TEAM_PROGRAM_TYPE);
                    $data[0]['program_type'] = $programTyes[$group->getTeamProgramType()];
                    $posts = Post::GetAllPostsInGroup($groupid, $_COMPANY->getAppCustomization()['post']['show_global_posts_in_group_feed'], $chapterid, 0, $channelid, $page);
                    $postData = [];
                    foreach ($posts as $p) {
                        $post = $this->postObjToArray($p);
                        if ($p->isActive() && !empty($post)) {
                            $channel = Group::GetChannelName($post['channelid'], $post['groupid']);
                            $post['totalComments'] = (int)Post::GetCommentsTotal($p->id()); // Should be int
                            $post['likeStatus'] = Post::GetUserLikeStatus($p->id()) ? 1 : 2;; // Should be int
                            $post['likeCount'] = (int)Post::GetLikeTotals($p->id());
                            $post['chapters'] = Group::GetChaptersCSV($post['chapterid'], $post['groupid']);
                            $post['chapterName'] = '';
                            $post['chapterColor'] = '';
                            $post['channelName'] = $post['channelid'] ? htmlspecialchars_decode($channel['channelname'] ?? '') : '';
                            $post['channelColor'] = $post['channelid'] ? $channel['colour'] : '';
                            $postData[] = $post;
                        }
                    }
                    $data[0]['posts'] = $postData;
                    
                    exit(self::buildApiResponseAsJson($method, $data[0], 1, gettext('Group detail'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Group not found'), 200));
                }
            }
            elseif ($section == 2) { // Events

                $timezone = "UTC"; // Use UTC timezone as timezone conversions will be done on the frontend.
                $newEventsOnly = true;
                $globalChapterOnly = $chapterid == 0;
                $globalChannelOnly = $channelid == 0;
                // GetGroupEventsViewData returns one extra row then the provided limit, pass 29 to get 30 rows per page.
                $newEvents = Event::GetGroupEventsViewData(Event::EVENT_CLASS['EVENT'],$groupid, $globalChapterOnly, $chapterid, $globalChannelOnly, $channelid, $page, 29, $newEventsOnly, false, $timezone);
                $pinnedEvents  = array();
                if ($page == 1){
                    $pinnedEvents = Event::GetGroupEventsViewData(Event::EVENT_CLASS['EVENT'],$groupid, $globalChapterOnly, $chapterid, $globalChannelOnly, $channelid, $page, 100, $newEventsOnly, true, $timezone);
                }

                $data = array_merge($pinnedEvents,$newEvents);
                $finalData = array();
                foreach ($data as $key => $datum) {
                    $row = array();
                    $evnt = Event::GetEvent($datum['eventid']);
                    $row['companyid'] = $datum['companyid'];
                    $row['groupid'] = $datum['groupid'];
                    $row['chapterid'] = $datum['chapterid'];
                    $row['channelid'] = $datum['channelid'];
                    $row['eventid'] = $datum['eventid'];
                    $row['rsvp_display'] = $datum['rsvp_display'];
                    $row['hostedby'] = $datum['hostedby'];
                    $row['eventtype'] = $datum['eventtype'];
                    $row['event_series_id'] = $datum['event_series_id'];
                    $row['collaborating_groupids'] = $datum['collaborating_groupids'];
                    $row['pin_to_top'] = $datum['pin_to_top'];
                    $row['eventtitle'] = htmlspecialchars_decode($evnt->val('eventtitle') ?? ''); 
                    $row['event_description'] = $evnt->val('event_description'); 
                    $row['eventvanue'] = htmlspecialchars_decode($evnt->val('eventvanue') ?? ''); 
                    $row['vanueaddress'] = htmlspecialchars_decode($evnt->val('vanueaddress') ?? ''); 
                    $row['joinersCount'] =  $evnt->val('rsvp_display') > 0 ? $evnt->getJoinersCount() : 0; // Should be int
                    $row['eventJoiners'] =  $evnt->val('rsvp_display') > 1 ? $evnt->getRandomJoiners(6) : array();
                    $row['myJoinStaus'] = (int)$db->myEventJoinStatus($_USER->id(), $datum['eventid']);
                    $row['month'] = date('F', strtotime($datum['start']));
                    $row['addedon'] = (int)strtotime($datum['addedon']);
                    $row['publishdate'] = $datum['publishdate'];
                    $row['start'] = (int)strtotime($datum['start']);
                    $row['end'] = (int)strtotime($datum['end']);
                    $row['chapters'] = Group::GetChaptersCSV($evnt->val('chapterid'), $evnt->val('groupid'));
                    $channel = Group::GetChannelName($datum['channelid'], $datum['groupid']);
                    $row['channelName'] = $datum['channelid'] ? $channel['channelname'] : '';
                    $row['channelColor'] = $datum['channelid'] ? $channel['colour'] : '';
                    $row['eventSubtitle'] = self::Util_GetEventSubtitle ($evnt);

                    $row['totalComments'] = (int)Event::GetCommentsTotal($datum['eventid']); // Should be int
                    $row['likeStatus'] = Event::GetUserLikeStatus($datum['eventid']) ? 1 : 2;; // Should be int
                    $row['likeCount'] = (int)Event::GetLikeTotals($datum['eventid']);

                    
                    $row['eventSeriesName'] = '';
                    if ($datum['event_series_id'] && ($datum['eventid'] == $datum['event_series_id'])) {
                        $event_series = Event::GetEvent($datum['event_series_id']);
                        $row['eventSeriesName'] = htmlspecialchars_decode($event_series->val('eventtitle') ?? '');
                    }

                    $row['volunteerRequired'] = false;
                    $row['volunteerRequirementStats'] = array();

                    if($_COMPANY->getAppCustomization()['event']['volunteers'] && !empty($evnt->getEventVolunteerRequests()) && !$evnt->hasEnded() && $evnt->isPublished() && !$evnt->isAllRequestedVolunteersSignedup()){
                        $row['volunteerRequired'] = true;
                        $volunteerRequests = $evnt->getEventVolunteerRequests();
                        $stats = array();
                        foreach ($volunteerRequests as $volunteerRequest) {
                            $volunteerType = Event::GetEventVolunteerType($volunteerRequest['volunteertypeid']);
                            if ( $volunteerType){
                                $volunteerCountNeeded = $volunteerRequest['volunteer_needed_count'] - ($evnt->getVolunteerCountByType($volunteerRequest['volunteertypeid']) ?? 0);
                                $stats[] = array('volunteerType'=>$volunteerType['type'],'volunteerNeeded'=>$volunteerCountNeeded);
                            }
                        }
                        $row['volunteerRequirementStats'] = $stats;
                    }
                    $finalData[] = $row;
                }
                $data = array_values($finalData); // Remove the holes created by unset in the previous section.

                if (!empty($data)) {
                    exit(self::buildApiResponseAsJson($method, ['events' => $data], 1, gettext('Group events'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('No events'), 200));
                }

            }
            elseif ($section == 3) { // Group Members
                $data = $db->get("
                    SELECT a.`userid`,a.groupid,a.memberid,b.firstname,b.lastname,b.picture,b.email,b.jobtitle,b.homeoffice 
                    FROM `groupmembers` a 
                        INNER JOIN users b ON a.userid=b.userid  
                    WHERE a.groupid='{$groupid}' 
                    AND a.isactive='1' ORDER BY memberid DESC
                    ");

                if (count($data) > 0) {
                    for ($i = 0; $i < count($data); $i++) {
                        $data[$i]['firstname'] = htmlspecialchars_decode($data[$i]['firstname'] ?? '');
                        $data[$i]['lastname'] = htmlspecialchars_decode($data[$i]['lastname'] ?? '');
                        $data[$i]['email'] = htmlspecialchars_decode($data[$i]['email'] ?? '');
                        $data[$i]['jobtitle'] = htmlspecialchars_decode($data[$i]['jobtitle'] ?? '');
                        $data[$i]['leadsStatus'] = $_USER->isGrouplead($data[$i]['groupid']);
                        $data[$i]['homeoffice'] = $_COMPANY->getBranchName($data[$i]['homeoffice']);
                    }
                }
                $leads = $db->get("
                    SELECT a.*,b.`firstname`,b.`lastname`,b.email, b.`picture`,b.`jobtitle`,b.homeoffice,
                           IFNULL((SELECT type FROM grouplead_type WHERE typeid=a.grouplead_typeid),'') AS role_name 
                    FROM `groupleads` a 
                        INNER JOIN users b on a.`groupid`='{$groupid}' AND a.`userid`=b.`userid` AND a.`isactive`=1"
                );
                if (count($leads) > 0) {
                    for ($i = 0; $i < count($leads); $i++) {
                        $leads[$i]['firstname'] = htmlspecialchars_decode($leads[$i]['firstname'] ?? '');
                        $leads[$i]['lastname'] = htmlspecialchars_decode($leads[$i]['lastname'] ?? '');
                        $leads[$i]['email'] = htmlspecialchars_decode($leads[$i]['email'] ?? '');
                        $leads[$i]['jobtitle'] = htmlspecialchars_decode($leads[$i]['jobtitle'] ?? '');
                        $leads[$i]['role_name'] = htmlspecialchars_decode($leads[$i]['role_name'] ?? '');
                        $leads[$i]['homeoffice'] = $_COMPANY->getBranchName($leads[$i]['homeoffice']);
                    }
                }

                exit(self::buildApiResponseAsJson($method, ['members' => $data, 'leads' => $leads], 1, gettext("Group members"), 200));

            }
            elseif ($section == 4) { // About Us
                $data = $db->get("
                    SELECT `abouttitle`, `aboutgroup` 
                    FROM `groups` 
                    WHERE `groupid`='{$groupid}' "
                );

                // If needed, insert API auth token for video player
                if (strpos ($data[0]['aboutgroup'], '/album_video_player?') !== false) {
                    $data[0]['aboutgroup'] = str_replace('/album_video_player?', '/album_video_player?api_auth_token='.$api_auth_token_for_album_video_player.'&',$data[0]['aboutgroup']);
                }

                $order = "";
                $priority = $db->get("
                    SELECT `priority` 
                    FROM `groupleads` 
                    WHERE `groupid`='{$groupid}' 
                    ORDER BY `leadid` ASC LIMIT 1"
                );

                if (count($priority)) {
                    $priority = $priority[0]['priority'];
                    if ($priority) {
                        $order = " ORDER BY FIELD(leadid,{$priority})";
                    } else {
                        $order = " ORDER BY leadid ASC";
                    }
                }

                $leads = $db->get("
                    SELECT a.`userid`,
                           IFNULL((SELECT type FROM grouplead_type WHERE typeid=a.grouplead_typeid),'') AS role_name,
                           b.`firstname`,b.`lastname`,b.email, b.`picture`,b.`jobtitle`,b.homeoffice 
                    FROM `groupleads` a 
                        LEFT JOIN users b on a.userid=b.userid 
                    WHERE a.`groupid`='{$groupid}' 
                     AND a.`isactive`='1' {$order} "
                );

                if (count($leads) > 0) {
                    for ($i = 0; $i < count($leads); $i++) {
                        $leads[$i]['firstname'] = htmlspecialchars_decode($leads[$i]['firstname'] ?? '');
                        $leads[$i]['lastname'] = htmlspecialchars_decode($leads[$i]['lastname'] ?? '');
                        $leads[$i]['email'] = htmlspecialchars_decode($leads[$i]['email'] ?? '');
                        $leads[$i]['jobtitle'] = htmlspecialchars_decode($leads[$i]['jobtitle'] ?? '');
                        $leads[$i]['role_name'] = htmlspecialchars_decode($leads[$i]['role_name'] ?? '');
                        $leads[$i]['homeoffice'] = $_COMPANY->getBranchName($leads[$i]['homeoffice']);
                    }
                }

                exit(self::buildApiResponseAsJson($method, ['aboutus' => $data, 'leads' => $leads], 1, gettext("About us"), 200));

            }
            elseif ($section == 5) { // Newsletters 

                $chapterCondition = '';
                if ($chapterid == 0) {
                    $chapterCondition = " AND newsletters.chapterid='0'";
                } elseif ($chapterid > 0) {
                    $chapterCondition = " AND FIND_IN_SET(" . $chapterid . ",newsletters.chapterid)";
                }
            
                $channelCondition = "";
                if ($channelid == 0) {
                    $channelCondition    = " AND newsletters.channelid='0'";
                } elseif ($channelid > 0) {
                    $channelCondition = " AND newsletters.channelid='{$channelid}'";
                }

                $groupCondition = "AND newsletters.groupid = {$groupid}";
                if ($chapterid <= 0 || $channelid <= 0) {
                    $groupCondition = " AND newsletters.groupid IN ({$groupid},0)";
                }
                

                $data = $db->get("
                    SELECT newsletters.newsletterid,newsletters.newslettername,newsletters.pin_to_top,newsletters.newsletter AS newsletter,newsletters.groupid,newsletters.chapterid,newsletters.channelid,
                           IFNULL(newsletters.publishdate,newsletters.modifiedon) AS publishdate,
                           IFNULL(`groups`.groupname,'') AS groupname, group_channels.channelname,group_channels.colour 
                    FROM `newsletters` 
                        LEFT JOIN `groups` ON `groups`.groupid=newsletters.groupid 
                        LEFT JOIN group_channels ON group_channels.channelid=newsletters.channelid 
                    WHERE newsletters.companyid = '{$_COMPANY->id()}' 
                      AND newsletters.zoneid='{$_ZONE->id()}'
                      {$groupCondition}
                      {$chapterCondition} 
                      {$channelCondition} 
                       AND newsletters.isactive=1 
                       ORDER BY newsletters.pin_to_top DESC,
                                CASE newsletters.pin_to_top WHEN 1 THEN newsletters.modifiedon ELSE newsletters.publishdate END DESC,
                                newsletters.newsletterid DESC limit {$start}, {$end}"
                );

                if (count($data)) {
                    for($i=0;$i<count($data);$i++){
                        $data[$i]['newslettername'] = htmlspecialchars_decode($data[$i]['newslettername'] ?? '');
                        $data[$i]['newsletter'] = htmlspecialchars_decode($data[$i]['newsletter'] ?? '');
                        $data[$i]['groupname'] = $data[$i]['groupname'];
                        $data[$i]['chapters'] = Group::GetChaptersCSV($data[$i]['chapterid'], $data[$i]['groupid']);
                        $data[$i]['channelname'] = htmlspecialchars_decode($data[$i]['channelname']??'');
                        $data[$i]['channelColor'] = htmlspecialchars_decode($data[$i]['colour']??'');
                        $data[$i]['pin_to_top'] = $data[$i]['pin_to_top'];                        
                    }
                    exit(self::buildApiResponseAsJson($method, $data, 1, gettext("Group newsletters"), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext("No newsletters"), 200));
                }
            }
            elseif($section == 6){ // Discussions
                $group = Group::GetGroup($groupid);
                $discussions = Discussion::GetGroupDiscussions($groupid, $chapterid, $channelid, 0, $page);
                $finalData = array();
                foreach ($discussions as $discussion) {
                    $row = array();
                    $channel = Group::GetChannelName($discussion['channelid'], $discussion['groupid']);
                    $creator = User::GetUser($discussion['createdby']);
                    $latestComment =    $discussion['anonymous_post'] ? 
                                        Discussion::GetCommentsAnonymized_2($discussion['discussionid'],0,1) :
                                        $this->getCleanComments('Discussion',$discussion['discussionid'],0,1);

                    $row['discussionid'] = $discussion['discussionid'];
                    $row['groupid'] = $discussion['groupid'];
                    $row['chapterid'] = $discussion['chapterid'];
                    $row['channelid'] = $discussion['channelid'];
                    $row['title'] = htmlspecialchars_decode($discussion['title'] ?? '');
                    $row['discussion'] = $discussion['discussion'];
                    $row['publishdate'] = $discussion['createdon'];
                    $row['isactive'] = $discussion['isactive'];
                    $row['pin_to_top'] = $discussion['pin_to_top'];
                    $row['totalComments'] = (int)Discussion::GetCommentsTotal($discussion['discussionid']); // Should be int
                    $row['likeStatus'] = Discussion::GetUserLikeStatus($discussion['discussionid']) ? 1 : 2; // Should be int
                    $row['likeCount'] = (int)Discussion::GetLikeTotals($discussion['discussionid']);
                    $row['chapters'] = Group::GetChaptersCSV($discussion['chapterid'], $discussion['groupid']);
                    $row['chapterName'] = '';
                    $row['chapterColor'] = '';
                    $row['channelName'] = $discussion['channelid'] ? htmlspecialchars_decode($channel['channelname'] ?? '') : '';
                    $row['channelColor'] = $discussion['channelid'] ? $channel['colour'] : '';
                    $row['creator'] = ($discussion['anonymous_post'])
                        ?
                        array (
                            'userid' => $_COMPANY->encodeId(0),
                            'firstname' => 'Anonymous',
                            'lastname' => 'User',
                            'picture' => '',
                        )
                        :
                        array (
                            'userid' =>  $creator ? $_COMPANY->encodeId($creator->id()) : $_COMPANY->encodeId(0),
                            'firstname' => $creator ? $creator->val('firstname') : 'Deleted',
                            'lastname' => $creator ? $creator->val('lastname') : 'User',
                            'picture' => $creator ? $creator->val('picture') : '',
                        );
                    $row['lastCommentBy'] = '';
                    if (!empty($latestComment)) {
                        $row['lastCommentBy'] =
                            ($discussion['anonymous_post'])
                            ?
                            array(
                                'firstname' => 'Anonymous',
                                'lastname' => 'User',
                                'picture' => '',
                                'createdon' => '',
                                'jobtitle' => '',
                                'email' => '',
                                'anonymized' => true
                            )
                            :
                            array(
                                'firstname' => $latestComment[0]['firstname'],
                                'lastname' => $latestComment[0]['lastname'],
                                'picture' => $latestComment[0]['picture'],
                                'createdon' => $latestComment[0]['createdon'],
                                'jobtitle' => $latestComment[0]['jobtitle'],
                                'email' => $latestComment[0]['email']??''
                            );
                    }
                    $finalData[] = $row;
                }
                
                $discussionSettings = $group->getDiscussionsConfiguration();
                $createUpdateDiscussionLink = '';
                if (($discussionSettings['who_can_post']=='members' && $_USER->isGroupMember($groupid)) || $_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
                    $createUpdateDiscussionLink = $_COMPANY->getAdminURL().'/native/createUpdateDiscussion.php?groupid='.$_COMPANY->encodeId($groupid).'&discussionid='.$_COMPANY->encodeId(0);
                }
                exit(self::buildApiResponseAsJson($method,array('discussions'=>$finalData,'createDiscussionLink'=>$createUpdateDiscussionLink), 1, gettext('Discussions list'), 200));

            }
            elseif ($section == 7) { // Events

                $timezone = "UTC";
                $newEventsOnly = false;
                $globalChapterOnly = $chapterid == -1;
                $globalChannelOnly = $channelid == -1;
                $data = Event::GetGroupEventsViewData(Event::EVENT_CLASS['EVENT'],$groupid, $globalChapterOnly, $chapterid, $globalChannelOnly, $channelid, $page, 30, $newEventsOnly, false, $timezone);
                $finalData = array();

                foreach ($data as $key => $datum) {
                    $row = array();
                    $evnt = Event::GetEvent($datum['eventid']);
                    $row['companyid'] = $datum['companyid'];
                    $row['groupid'] = $datum['groupid'];
                    $row['chapterid'] = $datum['chapterid'];
                    $row['channelid'] = $datum['channelid'];
                    $row['eventid'] = $datum['eventid'];
                    $row['rsvp_display'] = $datum['rsvp_display'];
                    $row['hostedby'] = $datum['hostedby'];
                    $row['eventtype'] = $datum['eventtype'];
                    $row['event_series_id'] = $datum['event_series_id'];
                    $row['collaborating_groupids'] = $datum['collaborating_groupids'];
                    $row['pin_to_top'] = $datum['pin_to_top'];
                    $row['eventtitle'] = htmlspecialchars_decode($evnt->val('eventtitle') ?? ''); 
                    $row['event_description'] = $evnt->val('event_description'); 
                    $row['eventvanue'] = htmlspecialchars_decode($evnt->val('eventvanue') ?? ''); 
                    $row['vanueaddress'] = htmlspecialchars_decode($evnt->val('vanueaddress') ?? ''); 
                    $row['joinersCount'] =  $evnt->val('rsvp_display') > 0 ? $evnt->getJoinersCount() : 0; // Should be int
                    $row['eventJoiners'] =  $evnt->val('rsvp_display') > 1 ? $evnt->getRandomJoiners(6) : array();
                    $row['myJoinStaus'] = (int)$db->myEventJoinStatus($_USER->id(), $datum['eventid']);
                    $row['month'] = date('F', strtotime($datum['start']));
                    $row['addedon'] = (int)strtotime($datum['addedon']);
                    $row['publishdate'] = $datum['publishdate'];
                    $row['start'] = (int)strtotime($datum['start']);
                    $row['end'] = (int)strtotime($datum['end']);
                    $row['chapters'] = Group::GetChaptersCSV($evnt->val('chapterid'), $evnt->val('groupid'));
                    $channel = Group::GetChannelName($datum['channelid'], $datum['groupid']);
                    $row['channelName'] = $datum['channelid'] ? $channel['channelname'] : '';
                    $row['channelColor'] = $datum['channelid'] ? $channel['colour'] : '';
                    $row['eventSubtitle'] = self::Util_GetEventSubtitle ($evnt);
                    $row['totalComments'] = (int)Event::GetCommentsTotal($datum['eventid']); // Should be int
                    $row['likeStatus'] = Event::GetUserLikeStatus($datum['eventid']) ? 1 : 2;; // Should be int
                    $row['likeCount'] = (int)Event::GetLikeTotals($datum['eventid']);

                    $row['eventSeriesName'] = '';
                    if ($datum['event_series_id'] && ($datum['eventid'] != $datum['event_series_id'])) {
                        $event = Event::GetEvent($datum['event_series_id']);
                        $row['eventSeriesName'] = htmlspecialchars_decode($event->val('eventtitle') ?? '');
                    }
                    $row['volunteerRequired'] = false;
                    $finalData[] = $row;
                }

                $data = array_values($finalData); // Remove the holes created by unset in the previous section.

                if (!empty($data)) {
                    exit(self::buildApiResponseAsJson($method, ['events' => $data], 1, gettext('Group past events'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('No events'), 200));
                }

            }
            else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext("Please select a section"), 200));
            }
        }
    }
    
    public function viewGroupDetail($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "viewGroupDetail";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = intval($get['groupid']); // handling negative value for group if ever used.
            // this will handle any negative value for chapter/channel ids as 0
            $chapterid = intval($get['chapterid'] ?? 0);
            $channelid = intval($get['channelid'] ?? 0);
            $api_auth_token_for_album_video_player = $_COMPANY->encryptArray2String([
                'companyid' => $_COMPANY->id(),
                'userid' => $_USER->id(),
                'valid_for' => 'album_video_player',
                'valid_until' => time() + 604800 // 7 days
            ]);
            $group = Group::GetGroup($groupid);
            if ($group && $group->val('isactive') == 1) {
                if ($_ZONE->id() != $group->val('zoneid')){
                    $_ZONE = $_COMPANY->getZone($group->val('zoneid'));
                    if (!$_ZONE) {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                    }
                }
                $data = Group::GetGroupDetail($groupid);
                $data[0]['joinStatus'] = $_USER->isGroupMember($data[0]['groupid']);
                $data[0]['myLeadStatus'] = $_USER->isGrouplead($data[0]['groupid']);
                $data[0]['addedby'] = $_COMPANY->encodeId($data[0]['addedby']);
                $data[0]['coverphoto'] = $data[0]['app_coverphoto'] ? : $data[0]['coverphoto'];
                $data[0]['sliderphoto'] = $data[0]['app_sliderphoto'] ? : $data[0]['sliderphoto'];
                $data[0]['sliderphoto_300W'] =  $data[0]['sliderphoto'] ?: '';
                $data[0]['show_overlay_logo'] = $data[0]['show_app_overlay_logo'];
                $data[0]['joinRequestStatus'] = Team::GetRequestDetail($groupid,0) ? 1 : 0;
                $data[0]['required_to_join_chapter'] = '';
                $data[0]['isAllowedToJoinGroup'] = $_USER->isAllowedToJoinGroup($groupid);

               
                $groupDisclaimers = array();
                if (Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_JOIN_BEFORE'], $groupid)) {
                    $groupDisclaimers['group_join_before_id'] = Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_JOIN_BEFORE'];
                }
                if (Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_LEAVE_BEFORE'], $groupid)) {
                    $groupDisclaimers['group_leave_before_id'] = Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_LEAVE_BEFORE'];
                }
                $data[0]['group_disclaimers'] = $groupDisclaimers ?: (object) array();

                // If needed, insert API auth token for video player
                if (strpos ($data[0]['aboutgroup'], '/album_video_player?') !== false) {
                    $data[0]['aboutgroup'] = str_replace('/album_video_player?', '/album_video_player?api_auth_token='.$api_auth_token_for_album_video_player.'&',$data[0]['aboutgroup']);
                }

                if ($_USER->isGroupMember($groupid) && $group->val('group_type')!=Group::GROUP_TYPE_MEMBERSHIP_DISABLED){
                    if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && !empty(Group::GetChapterList($groupid))){
                        $selectedChapters = $_USER->getFollowedGroupChapterAsCSV($groupid) ?? '';
                        if (!$selectedChapters && $data[0]['chapter_assign_type'] == 'by_user_atleast_one') {
                            $data[0]['required_to_join_chapter'] ='You need to join one or more '.$_COMPANY->getAppCustomization()['chapter']['name-short-plural'];
                        } elseif (!$selectedChapters && $data[0]['chapter_assign_type'] == 'by_user_exactly_one') {
                            $data[0]['required_to_join_chapter'] = 'You need to join a '.$_COMPANY->getAppCustomization()['chapter']['name-short'];
                        } 
                    }
                } 
                $data[0]['can_create_discussion'] = $_USER->isGroupMember($groupid) || $_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid);
                $data[0]['allowed_chapters_channels'] = $this->filterChapterAndChannelByPermission($groupid,'create');
                $data[0]['show_manage_membership'] = $_ZONE->val('app_type') != 'talentpeak';
                $data[0]['is_team_enabled'] = $group->isTeamsModuleEnabled();
                $data[0]['request_role_button_label'] = !empty(Team::GetUserJoinRequests($groupid,0,0)) ? gettext('Manage Registration') : gettext('Register') ;
                
                $programTyes = array_flip(Team::TEAM_PROGRAM_TYPE);
                $data[0]['program_type'] = $programTyes[$group->getTeamProgramType()];               
                $leads= $group->getGroupLeads('',true);
                $cleanLeadData = array();
                foreach($leads as $lead) {
                    $cleanLeadData[] = array (
                        "userid"=> $lead['userid'],
                        "role_name"=> $lead['type'],
                        "firstname"=> $lead['firstname'],
                        "lastname"=> $lead['lastname'],
                        "email"=> $lead['email'],
                        "picture"=> $lead['picture'],
                        "jobtitle"=> $lead['jobtitle'],
                        "pronouns"=> $_COMPANY->getAppCustomization()['profile']['enable_pronouns'] && isset($lead['pronouns']) ? ($lead['pronouns']??'') : ''
                    );
                }
                $data[0]['leads'] = $cleanLeadData;

                $canViewContent = $_USER->canViewContent($groupid);
                $groupTabs = array();
                $contentRestriction= (object) array();
                if (!$canViewContent) {
                    $contentRestriction = array('heading' => sprintf(gettext('This is a private %1$s. Only members can view the content.'), $_COMPANY->getAppCustomization()['group']['name-short']),'message' =>  sprintf(gettext(' If you recently joined, your access might take a few moments. Try reloading the page after a few seconds to see the content.')));
                }

                if ($_COMPANY->getAppCustomization()['aboutus']['enabled']) { 
                    $groupTabs[] = array (
                        'title' => gettext('About Us'),
                        'key' => 'about',
                        'load_default' => $canViewContent ? false : true,
                        'contentRestriction' => $contentRestriction
                    );
                }
                if ($_COMPANY->getAppCustomization()['donations']['enabled'] && $canViewContent && 0 ) { // Disabled for Moblie app 
                    $groupTabs[] = array (
                        'title' => gettext('Donations'),
                        'key' => 'donations',
                        'load_default' => false,
                        'contentRestriction' => $contentRestriction
                    );

                }
                if ($_COMPANY->getAppCustomization()['post']['enabled'] && $canViewContent) {
                    $groupTabs[] = array (
                        'title' => Post::GetCustomName(true),
                        'key' => 'post',
                        'load_default' => $canViewContent ? true : false,
                        'contentRestriction' => $contentRestriction
                    );

                }
                if ($_COMPANY->getAppCustomization()['event']['enabled'] && $canViewContent) { 
                    $groupTabs[] = array (
                        'title' => gettext('Events'),
                        'key' => 'events',
                        'load_default' => false,
                        'contentRestriction' => $contentRestriction
                    );
                }
                if ($_COMPANY->getAppCustomization()['newsletters']['enabled'] && $canViewContent) {
                    $groupTabs[] = array (
                        'title' => gettext('Newsletters'),
                        'key' => 'newsletters',
                        'load_default' => false,
                        'contentRestriction' => $contentRestriction
                    );
                }
                if ($_COMPANY->getAppCustomization()['resources']['enabled'] && $canViewContent) {
                    $groupTabs[] = array (
                        'title' => gettext('Resources'),
                        'key' => 'resources',
                        'load_default' => false,
                        'contentRestriction' => $contentRestriction
                    );
                }
                if ($_COMPANY->getAppCustomization()['albums']['enabled'] && $canViewContent) {
                    $groupTabs[] = array (
                        'title' => gettext('Albums'),
                        'key' => 'albums',
                        'load_default' => false,
                        'contentRestriction' => $contentRestriction
                    );
                }

                if ($group->isTeamsModuleEnabled() && $canViewContent) { 
                    $groupTabs[] = array (
                        'title' => sprintf(gettext("My %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1)),
                        'key' => 'myteams',
                        'load_default' => false,
                        'contentRestriction' => $contentRestriction
                    );
                }

                if ($_COMPANY->getAppCustomization()['discussions']['enabled'] && $canViewContent) {
                    $groupTabs[] = array (
                        'title' => gettext('Discussions'),
                        'key' => 'discussions',
                        'load_default' => false,
                        'contentRestriction' => $contentRestriction
                    );
                }
                if ($_COMPANY->getAppCustomization()['recognition']['enabled'] && $canViewContent && $group->getRecognitionConfiguration()['enable_user_view_recognition']) {
                    $groupTabs[] = array (
                        'title' => Recognition::GetCustomName(true),
                        'key' => 'recognition',
                        'load_default' => false,
                        'contentRestriction' => $contentRestriction
                    );
                }

                if(isset($data[0]['abouttitle']))
                {
                  $data[0]['abouttitle'] = "";
                }

                $data[0]['groupTabs'] = $groupTabs;
               
                exit(self::buildApiResponseAsJson($method, $data[0], 1, gettext('Group detail'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Group not found'), 200));
            }   
        }
    }


    public function getGroupsSelectedTabData($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getGroupsSelectedTabData";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'], 'page' => @$get['page'], 'section' => @$get['section']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $page = intval($get['page']);
            $groupid = intval($get['groupid']); // handling negative value for group if ever used.
            $section = strval($get['section']);
            $start = ($page - 1) * 30;
            $end = 30;
            // this will handle any negative value for chapter/channel ids as 0
            $chapterid = intval($get['chapterid'] ?? 0);
            $channelid = intval($get['channelid'] ?? 0);
            $api_auth_token_for_album_video_player = $_COMPANY->encryptArray2String([
                'companyid' => $_COMPANY->id(),
                'userid' => $_USER->id(),
                'valid_for' => 'album_video_player',
                'valid_until' => time() + 604800 // 7 days
            ]);

            if (!$_USER->canViewContent($groupid)) {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('This is a private %1$s. Only members can view the content.'), $_COMPANY->getAppCustomization()['group']['name-short']), 200));
            }
          
            if ($section == 'post') { // Group Posts
                $posts = Post::GetAllPostsInGroup($groupid, $_COMPANY->getAppCustomization()['post']['show_global_posts_in_group_feed'], $chapterid, 0, $channelid, $page);
                $postData = [];
                foreach ($posts as $p) {
                    $post = $this->postObjToArray($p);
                    if ($p->isActive() && !empty($post)) {
                        $groupName = '';
                        if($post['groupid'] == 0){
                            $groupName = $_COMPANY->getAppCustomization()['group']['groupname0'];
                        }
                        $channel = Group::GetChannelName($post['channelid'], $post['groupid']);
                        $post['totalComments'] = (int)Post::GetCommentsTotal($p->id()); // Should be int
                        $post['likeStatus'] = Post::GetUserLikeStatus($p->id()) ? 1 : 2;; // Should be int
                        $post['likeCount'] = (int)Post::GetLikeTotals($p->id());
                        $post['groupname'] = $groupName;
                        $post['chapters'] = Group::GetChaptersCSV($post['chapterid'], $post['groupid']);
                        $post['chapterName'] = '';
                        $post['chapterColor'] = '';
                        $post['channelName'] = $post['channelid'] ? htmlspecialchars_decode($channel['channelname'] ?? '') : '';
                        $post['channelColor'] = $post['channelid'] ? $channel['colour'] : '';
                        $postData[] = $post;
                    }
                }
                if (!empty($postData)){
                    exit(self::buildApiResponseAsJson($method, $postData, 1, gettext('Announcements list'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('No announcements'), 200));
                }
                
            }
            elseif ($section == 'events') { // Events

                $timezone = "UTC"; // Use UTC timezone as timezone conversions will be done on the frontend.
                $newEventsOnly = true;
                $globalChapterOnly = isset($get['chapterid']) && $chapterid == 0;
                $globalChannelOnly = isset($get['channelid']) && $channelid == 0;
                // GetGroupEventsViewData returns one extra row then the provided limit, pass 29 to get 30 rows per page.
                $newEvents = Event::GetGroupEventsViewData(Event::EVENT_CLASS['EVENT'],$groupid, $globalChapterOnly, $chapterid, $globalChannelOnly, $channelid, $page, 29, $newEventsOnly, false, $timezone);
                $pinnedEvents  = array();
                if ($page == 1){
                    $pinnedEvents = Event::GetGroupEventsViewData(Event::EVENT_CLASS['EVENT'],$groupid, $globalChapterOnly, $chapterid, $globalChannelOnly, $channelid, $page, 100, $newEventsOnly, true, $timezone);
                }

                $data = array_merge($pinnedEvents,$newEvents);
                $finalData = array();
                foreach ($data as $key => $datum) {
                    $row = array();
                    $evnt = Event::GetEvent($datum['eventid']);
                    $groupName  = '';
                    if (!$datum['groupid'] && !$datum['collaborating_groupids']){
                        $groupName = $_COMPANY->getAppCustomization()['group']['groupname0'];
                    }
                    $row['groupname'] =  $groupName;
                    $row['companyid'] = $datum['companyid'];
                    $row['groupid'] = $datum['groupid'];
                    $row['chapterid'] = $datum['chapterid'];
                    $row['channelid'] = $datum['channelid'];
                    $row['eventid'] = $datum['eventid'];
                    $row['rsvp_display'] = $datum['rsvp_display'];
                    $row['rsvp_enabled'] = $datum['rsvp_enabled'];
                    $row['hostedby'] = $datum['hostedby'];
                    $row['eventtype'] = $datum['eventtype'];
                    $row['event_series_id'] = $datum['event_series_id'];
                    $row['collaborating_groupids'] = $datum['collaborating_groupids'];
                    $row['pin_to_top'] = $datum['pin_to_top'];
                    $row['eventSeriesName'] = '';
                    if ($datum['event_series_id'] && ($datum['eventid'] != $datum['event_series_id'])) {
                        $event_series = Event::GetEvent($datum['event_series_id']);
                        $row['eventSeriesName'] = htmlspecialchars_decode($event_series->val('eventtitle') ?? '');
                    }
                    $row['eventtitle'] = htmlspecialchars_decode($evnt->val('eventtitle') ?? ''); 
                    $row['event_description'] = $evnt->val('event_description'); 
                    $row['venue_info'] = $evnt->val('venue_info');
                    $row['eventvanue'] = htmlspecialchars_decode($evnt->val('eventvanue') ?? ''); 
                    $row['venue_room'] = htmlspecialchars_decode($datum['venue_room']??'');
                    $row['vanueaddress'] = htmlspecialchars_decode($evnt->val('vanueaddress') ?? ''); 
                    $row['joinersCount'] =  $evnt->val('rsvp_display') > 0 ? $evnt->getJoinersCount() : 0; // Should be int
                    $row['eventJoiners'] =  $evnt->val('rsvp_display') > 1 ? $evnt->getRandomJoiners(6) : array();
                    $row['myJoinStaus'] = (int)$db->myEventJoinStatus($_USER->id(), $datum['eventid']);
                    $row['month'] = date('F', strtotime($datum['start']));
                    $row['addedon'] = (int)strtotime($datum['addedon']);
                    $row['publishdate'] = $datum['publishdate'];
                    $row['start'] = (int)strtotime($datum['start']);
                    $row['end'] = (int)strtotime($datum['end']);
                    $row['chapters'] = Group::GetChaptersCSV($evnt->val('chapterid'), $evnt->val('groupid'));
                    $channel = Group::GetChannelName($datum['channelid'], $datum['groupid']);
                    $row['channelName'] = $datum['channelid'] ? $channel['channelname'] : '';
                    $row['channelColor'] = $datum['channelid'] ? $channel['colour'] : '';
                    $row['eventSubtitle'] = self::Util_GetEventSubtitle ($evnt);

                    $row['totalComments'] = (int)Event::GetCommentsTotal($datum['eventid']); // Should be int
                    $row['likeStatus'] = Event::GetUserLikeStatus($datum['eventid']) ? 1 : 2;; // Should be int
                    $row['likeCount'] = (int)Event::GetLikeTotals($datum['eventid']);

                    $row['volunteerRequired'] = false;
                    $row['volunteerRequirementStats'] = array();

                    if($_COMPANY->getAppCustomization()['event']['volunteers'] && !empty($evnt->getEventVolunteerRequests()) && !$evnt->hasEnded() && $evnt->isPublished() && !$evnt->isAllRequestedVolunteersSignedup()){
                        $row['volunteerRequired'] = true;
                        $volunteerRequests = $evnt->getEventVolunteerRequests();
                        $stats = array();
                        foreach ($volunteerRequests as $volunteerRequest) {

                            if (isset($volunteerRequest['hide_from_signup_page']) && $volunteerRequest['hide_from_signup_page'] == 1) {
                                continue;
                            }
                            $volunteerType = Event::GetEventVolunteerType($volunteerRequest['volunteertypeid']);
                            if ( $volunteerType){
                                $volunteerCountNeeded = $volunteerRequest['volunteer_needed_count'] - ($evnt->getVolunteerCountByType($volunteerRequest['volunteertypeid']) ?? 0);
                                $stats[] = array('volunteerType'=>$volunteerType['type'],'volunteerNeeded'=>$volunteerCountNeeded);
                            }
                        }
                        $row['volunteerRequirementStats'] = $stats;
                    }
                    $finalData[] = $row;
                }
                $data = array_values($finalData); // Remove the holes created by unset in the previous section.

                if (!empty($data)) {
                    exit(self::buildApiResponseAsJson($method, $data, 1, gettext('Events lists'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('No events'), 200));
                }

            }
            elseif ($section == 'members') { // Group Members
                $data = $db->get("
                    SELECT a.`userid`,a.groupid,a.memberid,b.firstname,b.lastname,b.picture,b.email,b.jobtitle,b.homeoffice 
                    FROM `groupmembers` a 
                        INNER JOIN users b ON a.userid=b.userid  
                    WHERE a.groupid='{$groupid}' 
                    AND a.isactive='1' ORDER BY memberid DESC
                    ");

                if (count($data) > 0) {
                    for ($i = 0; $i < count($data); $i++) {
                        $data[$i]['firstname'] = htmlspecialchars_decode($data[$i]['firstname'] ?? '');
                        $data[$i]['lastname'] = htmlspecialchars_decode($data[$i]['lastname'] ?? '');
                        $data[$i]['email'] = htmlspecialchars_decode($data[$i]['email'] ?? '');
                        $data[$i]['jobtitle'] = htmlspecialchars_decode($data[$i]['jobtitle'] ?? '');
                        $data[$i]['leadsStatus'] = $_USER->isGrouplead($data[$i]['groupid']);
                        $data[$i]['homeoffice'] = $_COMPANY->getBranchName($data[$i]['homeoffice']);
                    }
                }
                $leads = $db->get("
                    SELECT a.*,b.`firstname`,b.`lastname`,b.email, b.`picture`,b.`jobtitle`,b.homeoffice,
                           IFNULL((SELECT type FROM grouplead_type WHERE typeid=a.grouplead_typeid),'') AS role_name 
                    FROM `groupleads` a 
                        INNER JOIN users b on a.`groupid`='{$groupid}' AND a.`userid`=b.`userid` AND a.`isactive`=1"
                );
                if (count($leads) > 0) {
                    for ($i = 0; $i < count($leads); $i++) {
                        $leads[$i]['firstname'] = htmlspecialchars_decode($leads[$i]['firstname'] ?? '');
                        $leads[$i]['lastname'] = htmlspecialchars_decode($leads[$i]['lastname'] ?? '');
                        $leads[$i]['email'] = htmlspecialchars_decode($leads[$i]['email'] ?? '');
                        $leads[$i]['jobtitle'] = htmlspecialchars_decode($leads[$i]['jobtitle'] ?? '');
                        $leads[$i]['role_name'] = htmlspecialchars_decode($leads[$i]['role_name'] ?? '');
                        $leads[$i]['homeoffice'] = $_COMPANY->getBranchName($leads[$i]['homeoffice']);
                    }
                }

                exit(self::buildApiResponseAsJson($method, ['members' => $data, 'leads' => $leads], 1, gettext("Group members"), 200));

            }
            elseif ($section == 'newsletters') { // Newsletters 

                $chapterCondition = '';
                if ($chapterid == 0) {
                    $chapterCondition = " AND newsletters.chapterid='0'";
                } elseif ($chapterid > 0) {
                    $chapterCondition = " AND FIND_IN_SET(" . $chapterid . ",newsletters.chapterid)";
                }
            
                $channelCondition = "";
                if ($channelid == 0) {
                    $channelCondition    = " AND newsletters.channelid='0'";
                } elseif ($channelid > 0) {
                    $channelCondition = " AND newsletters.channelid='{$channelid}'";
                }

                $groupCondition = "AND newsletters.groupid = {$groupid}";
                if ($chapterid <= 0 || $channelid <= 0) {
                    $groupCondition = " AND newsletters.groupid IN ({$groupid},0)";
                }
                

                $data = $db->get("
                    SELECT newsletters.newsletterid,newsletters.newslettername,newsletters.pin_to_top,newsletters.newsletter AS newsletter,newsletters.groupid,newsletters.chapterid,newsletters.channelid,
                           IFNULL(newsletters.publishdate,newsletters.modifiedon) AS publishdate,
                           IFNULL(`groups`.groupname,'') AS groupname, group_channels.channelname,group_channels.colour 
                    FROM `newsletters` 
                        LEFT JOIN `groups` ON `groups`.groupid=newsletters.groupid 
                        LEFT JOIN group_channels ON group_channels.channelid=newsletters.channelid 
                    WHERE newsletters.companyid = '{$_COMPANY->id()}' 
                      AND newsletters.zoneid='{$_ZONE->id()}'
                      {$groupCondition}
                      {$chapterCondition} 
                      {$channelCondition} 
                       AND newsletters.isactive=1 
                       ORDER BY newsletters.pin_to_top DESC,
                                CASE newsletters.pin_to_top WHEN 1 THEN newsletters.modifiedon ELSE newsletters.publishdate END DESC,
                                newsletters.newsletterid DESC limit {$start}, {$end}"
                );

                if (count($data)) {
                    for($i=0;$i<count($data);$i++){
                        $groupName = '';
                        if($data[$i]['groupid'] == 0){
                            $groupName = $_COMPANY->getAppCustomization()['group']['groupname0'];
                        }
                        $data[$i]['newslettername'] = htmlspecialchars_decode($data[$i]['newslettername'] ?? '');
                        $data[$i]['newsletter'] = htmlspecialchars_decode($data[$i]['newsletter'] ?? '');
                        $data[$i]['groupname'] = $groupName;
                        $data[$i]['chapters'] = Group::GetChaptersCSV($data[$i]['chapterid'], $data[$i]['groupid']);
                        $data[$i]['channelname'] = htmlspecialchars_decode($data[$i]['channelname']??'');
                        $data[$i]['channelColor'] = htmlspecialchars_decode($data[$i]['colour']??'');
                        $data[$i]['pin_to_top'] = $data[$i]['pin_to_top'];                        
                    }
                    exit(self::buildApiResponseAsJson($method, $data, 1, gettext("Group newsletters"), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext("No newsletters"), 200));
                }
            }
            elseif($section == 'discussions'){ // Discussions
                $group = Group::GetGroup($groupid);
                $discussions = Discussion::GetGroupDiscussions($groupid, $chapterid, $channelid, 0, $page);
                $finalData = array();
                foreach ($discussions as $discussion) {
                    $row = array();
                    $channel = Group::GetChannelName($discussion['channelid'], $discussion['groupid']);
                    $creator = User::GetUser($discussion['createdby']);
                    $latestComment =    $discussion['anonymous_post'] ? 
                                        Discussion::GetCommentsAnonymized_2($discussion['discussionid'],0,1) :
                                        $this->getCleanComments('Discussion',$discussion['discussionid'],0,1);

                    $row['discussionid'] = $discussion['discussionid'];
                    $row['groupid'] = $discussion['groupid'];
                    $row['chapterid'] = $discussion['chapterid'];
                    $row['channelid'] = $discussion['channelid'];
                    $row['title'] = htmlspecialchars_decode($discussion['title'] ?? '');
                    $row['discussion'] = $discussion['discussion'];
                    $row['publishdate'] = $discussion['createdon'];
                    $row['isactive'] = $discussion['isactive'];
                    $row['pin_to_top'] = $discussion['pin_to_top'];
                    $row['totalComments'] = (int)Discussion::GetCommentsTotal($discussion['discussionid']); // Should be int
                    $row['likeStatus'] = Discussion::GetUserLikeStatus($discussion['discussionid']) ? 1 : 2; // Should be int
                    $row['likeCount'] = (int)Discussion::GetLikeTotals($discussion['discussionid']);
                    $row['chapters'] = Group::GetChaptersCSV($discussion['chapterid'], $discussion['groupid']);
                    $row['chapterName'] = '';
                    $row['chapterColor'] = '';
                    $row['channelName'] = $discussion['channelid'] ? htmlspecialchars_decode($channel['channelname'] ?? '') : '';
                    $row['channelColor'] = $discussion['channelid'] ? $channel['colour'] : '';
                    $row['creator'] = ($discussion['anonymous_post'])
                        ?
                        array (
                            'userid' => $_COMPANY->encodeId(0),
                            'firstname' => 'Anonymous',
                            'lastname' => 'User',
                            'picture' => '',
                        )
                        :
                        array (
                            'userid' =>  $creator ? $_COMPANY->encodeId($creator->id()) : $_COMPANY->encodeId(0),
                            'firstname' => $creator ? $creator->val('firstname') : 'Deleted',
                            'lastname' => $creator ? $creator->val('lastname') : 'User',
                            'picture' => $creator ? $creator->val('picture') : '',
                        );
                    $row['lastCommentBy'] = '';
                    if (!empty($latestComment)) {
                        $row['lastCommentBy'] =
                            ($discussion['anonymous_post'])
                            ?
                            array(
                                'firstname' => 'Anonymous',
                                'lastname' => 'User',
                                'picture' => '',
                                'createdon' => '',
                                'jobtitle' => '',
                                'email' => '',
                                'anonymized' => true
                            )
                            :
                            array(
                                'firstname' => $latestComment[0]['firstname'],
                                'lastname' => $latestComment[0]['lastname'],
                                'picture' => $latestComment[0]['picture'],
                                'createdon' => $latestComment[0]['createdon'],
                                'jobtitle' => $latestComment[0]['jobtitle'],
                                'email' => $latestComment[0]['email']??''
                            );
                    }
                    $finalData[] = $row;
                }
                
                $discussionSettings = $group->getDiscussionsConfiguration();
                $createUpdateDiscussionLink = '';
                $disclaimerHookId = 0;
                if (($discussionSettings['who_can_post']=='members' && $_USER->isGroupMember($groupid)) || $_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {

                    if (Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['DISCUSSION_CREATE_BEFORE'], $groupid)) {
                        $disclaimerHookId = Disclaimer::DISCLAIMER_HOOK_TRIGGERS['DISCUSSION_CREATE_BEFORE'];

                    }
                    $createUpdateDiscussionLink = $_COMPANY->getAdminURL().'/native/createUpdateDiscussion.php?groupid='.$_COMPANY->encodeId($groupid).'&discussionid='.$_COMPANY->encodeId(0);
                }

                exit(self::buildApiResponseAsJson($method,array('discussions'=>$finalData,'createDiscussionLink'=>$createUpdateDiscussionLink, 'disclaimerHookId'=>$disclaimerHookId), 1, gettext('Discussions list'), 200));

            }
            elseif ($section == 'events_past') { // Events

                $timezone = "UTC";
                $newEventsOnly = false;
                $globalChapterOnly = isset($get['chapterid']) && $chapterid == 0;
                $globalChannelOnly = isset($get['channelid']) && $channelid == 0;
                $data = Event::GetGroupEventsViewData(Event::EVENT_CLASS['EVENT'],$groupid, $globalChapterOnly, $chapterid, $globalChannelOnly, $channelid, $page, 30, $newEventsOnly, false, $timezone);
                $finalData = array();
                foreach ($data as $key => $datum) {
                    $row = array();
                    $evnt = Event::GetEvent($datum['eventid']);
                    $groupName  = '';
                    if (!$datum['groupid'] && !$datum['collaborating_groupids']){
                        $groupName = $_COMPANY->getAppCustomization()['group']['groupname0'];
                    }
                    $row['groupname'] =  $groupName;
                    $row['companyid'] = $datum['companyid'];
                    $row['groupid'] = $datum['groupid'];
                    $row['chapterid'] = $datum['chapterid'];
                    $row['channelid'] = $datum['channelid'];
                    $row['eventid'] = $datum['eventid'];
                    $row['rsvp_display'] = $datum['rsvp_display'];
                    $row['rsvp_enabled'] = $datum['rsvp_enabled'];
                    $row['hostedby'] = $datum['hostedby'];
                    $row['eventtype'] = $datum['eventtype'];
                    $row['event_series_id'] = $datum['event_series_id'];
                    $row['collaborating_groupids'] = $datum['collaborating_groupids'];
                    $row['pin_to_top'] = $datum['pin_to_top'];
                    $row['eventtitle'] = htmlspecialchars_decode($evnt->val('eventtitle') ?? ''); 
                    $row['event_description'] = $evnt->val('event_description'); 
                    $row['venue_info'] = $evnt->val('venue_info');
                    $row['eventvanue'] = htmlspecialchars_decode($evnt->val('eventvanue') ?? ''); 
                    $row['venue_room'] = htmlspecialchars_decode($datum['venue_room']??'');
                    $row['vanueaddress'] = htmlspecialchars_decode($evnt->val('vanueaddress') ?? ''); 
                    $row['joinersCount'] =  $evnt->val('rsvp_display') > 0 ? $evnt->getJoinersCount() : 0; // Should be int
                    $row['eventJoiners'] =  $evnt->val('rsvp_display') > 1 ? $evnt->getRandomJoiners(6) : array();
                    $row['myJoinStaus'] = (int)$db->myEventJoinStatus($_USER->id(), $datum['eventid']);
                    $row['month'] = date('F', strtotime($datum['start']));
                    $row['addedon'] = (int)strtotime($datum['addedon']);
                    $row['publishdate'] = $datum['publishdate'];
                    $row['start'] = (int)strtotime($datum['start']);
                    $row['end'] = (int)strtotime($datum['end']);
                    $row['chapters'] = Group::GetChaptersCSV($evnt->val('chapterid'), $evnt->val('groupid'));
                    $channel = Group::GetChannelName($datum['channelid'], $datum['groupid']);
                    $row['channelName'] = $datum['channelid'] ? $channel['channelname'] : '';
                    $row['channelColor'] = $datum['channelid'] ? $channel['colour'] : '';
                    $row['eventSubtitle'] = self::Util_GetEventSubtitle ($evnt);
                    $row['totalComments'] = (int)Event::GetCommentsTotal($datum['eventid']); // Should be int
                    $row['likeStatus'] = Event::GetUserLikeStatus($datum['eventid']) ? 1 : 2;; // Should be int
                    $row['likeCount'] = (int)Event::GetLikeTotals($datum['eventid']);

                    $row['eventSeriesName'] = ''; 
                    if ($datum['event_series_id'] && ($datum['eventid'] != $datum['event_series_id'])) {
                        $event = Event::GetEvent($datum['event_series_id']);
                        $row['eventSeriesName'] = htmlspecialchars_decode($event->val('eventtitle') ?? '');
                    }
                    $row['volunteerRequired'] = false;
                    $finalData[] = $row;
                }

                $data = array_values($finalData); // Remove the holes created by unset in the previous section.

                if (!empty($data)) {
                    exit(self::buildApiResponseAsJson($method, $data, 1, gettext('Group past events'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('No events'), 200));
                }

            }
            else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext("Please select a section"), 200));
            }
        }
    }

    private function postObjToArray($post)
    {
        global $_COMPANY, $_ZONE;
        return array(
            'postid' => $post->val('postid'),
            'groupid' => $post->val('groupid'),
            'chapterid' => $post->val('chapterid'),
            'channelid' => $post->val('channelid'),
            'title' => htmlspecialchars_decode($post->val('title') ?? ''),
            'post' => $post->val('post'),
            'publishdate' => $post->val('publishdate'),
            'isactive' => $post->val('isactive'),
            'pin_to_top' => $post->val('pin_to_top')
        );
    }

    /**
     * Get Aboutus section for a chapter
     * @param $get
     * @param $this
     */
    public function getChapterAboutUs($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getChapterAboutUs";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'], 'chapterid' => @$get['chapterid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, $checkRequired . gettext(" can't be empty"), 200));
        } else {
            $groupid = $get['groupid'];
            $chapterid = $get['chapterid'];
            $group = Group::GetGroup($groupid);
            if ($group) {
                $isGroupMember = $_USER->isGroupMember($groupid);
                $chapter = $group->getChapter($chapterid);
                $chapterLeads = $group->getChapterLeads($chapterid);
                $isChapterlead = $_USER->isChapterlead($groupid, $chapterid);

                if ($chapter) {
                    $chapterCleanArray = $this->chapterObjToArray($chapter);
                    exit(self::buildApiResponseAsJson($method, ['chapter' => $chapterCleanArray, 'chapterLeads' => $chapterLeads, 'isChapterlead' => $isChapterlead], 1, gettext('About chapter'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Chapter not found'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Group not found'), 200));
            }
        }
    }

    /**
     * Get Chapter members
     * @param $get
     * @param $this
     */
    public function getChapterMembers($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getChapterMembers";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'], 'chapterid' => @$get['chapterid'], 'page' => @$get['page']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = intval($get['groupid']);
            $chapterid = intval($get['chapterid']);
            $page = intval($get['page']);
            $start = ($page - 1) * 30;
            $end = 30;

            $group = Group::GetGroup($groupid);
            if ($group) {
                $isGroupMember = $_USER->isGroupMember($groupid);
                $isChapterMember = $_USER->isGroupMember($groupid, $chapterid);
                if ($group->val('about_show_members') === "1" || ($group->val('about_show_members') === "2" && $isGroupMember)) {
                    $chapterMembers = $db->get("
                        SELECT a.*,b.firstname,b.lastname,b.jobtitle,b.picture,b.email 
                        FROM `groupmembers` a 
                            JOIN users b ON b.userid=a.userid 
                        WHERE b.companyid='{$_COMPANY->id()}' 
                          AND (a.`groupid`='{$groupid}' 
                          AND a.`isactive`='1' 
                          AND b.`isactive`='1'  
                          AND FIND_IN_SET({$chapterid}, a.chapterid)) 
                          LIMIT {$start}, {$end}"
                    );
                    for($i=0;$i<count($chapterMembers);$i++){
                        $chapterMembers[$i]['firstname'] = htmlspecialchars_decode($chapterMembers[$i]['firstname'] ?? '');
                        $chapterMembers[$i]['lastname'] = htmlspecialchars_decode($chapterMembers[$i]['lastname'] ?? '');
                        $chapterMembers[$i]['email'] = htmlspecialchars_decode($chapterMembers[$i]['email'] ?? '');
                        $chapterMembers[$i]['jobtitle'] = htmlspecialchars_decode($chapterMembers[$i]['jobtitle'] ?? '');
                    }
                    $totalmembers = $db->get("
                        SELECT count(1) AS totalmembers 
                        FROM `groupmembers` a 
                            JOIN users b ON b.userid=a.userid 
                        WHERE b.companyid='{$_COMPANY->id()}' 
                          AND (a.`groupid`='{$groupid}' 
                                   AND FIND_IN_SET({$chapterid},a.chapterid) 
                                   AND a.`isactive`='1' 
                                   AND b.`isactive`='1' A
                                   ND a.`anonymous`='0'
                               )"
                    )[0]['totalmembers'];

                    exit(self::buildApiResponseAsJson($method, ['totalChapterMembers' => $totalmembers, 'chapterMembers' => $chapterMembers, 'isChapterMember' => $isChapterMember], 1, gettext('Chapter members list.'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Chapter members listing not allowed'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Group not found'), 200));
            }
        }
    }

    /**
     * Get Channel about
     * @param $get
     * @param $this
     */
    public function getChannelAboutUs($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getChannelAboutUs";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'], 'channelid' => @$get['channelid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $channelid = $get['channelid'];
            $group = Group::GetGroup($groupid);
            if ($group) {
                $isGroupMember = $_USER->isGroupMember($groupid);
                $channel = $group->getChannel($channelid);
                $channelLeads = $group->getChannelLeads($channelid);
                $isChannelLead = $_USER->isChannellead($groupid, $channelid);

                if ($channel) {
                    $channelCleanArray = $this->channelObjToArray($channel);
                    exit(self::buildApiResponseAsJson($method, ['channel' => $channelCleanArray, 'channelLeads' => $channelLeads, 'isChannelLead' => $isChannelLead], 1, gettext('About channel'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Channel not found'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Group not found'), 200));
            }
        }
    }

    /**
     * @param $get
     * @param $this
     */
    public function getChannelMembers($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getChannelMembers";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'], 'channelid' => @$get['channelid'], 'page' => @$get['page']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $channelid = $get['channelid'];
            $page = $get['page'];
            $start = ($page - 1) * 30;
            $end = 30;

            $group = Group::GetGroup($groupid);
            if ($group) {
                $isGroupMember = $_USER->isGroupMember($groupid);
                $isChannelMember = $_USER->isGroupChannelMember($groupid, $channelid);
                if ($group->val('about_show_members') === "1" || ($group->val('about_show_members') === "2" && $isGroupMember)) {

                    $channelMembers = $db->get("
                        SELECT a.*,b.firstname,b.lastname,b.jobtitle,b.picture,b.email 
                        FROM `groupmembers` a 
                            JOIN users b ON b.userid=a.userid 
                        WHERE b.companyid='{$_COMPANY->id()}' 
                          AND (a.`groupid`='{$groupid}' 
                                   AND a.`isactive`='1' 
                                   AND b.`isactive`='1' 
                                   AND FIND_IN_SET({$channelid}, a.channelids) 
                              ) 
                        LIMIT {$start},{$end}"
                    );

                    for($i=0;$i<count($channelMembers);$i++){
                        $channelMembers[$i]['firstname'] = htmlspecialchars_decode($channelMembers[$i]['firstname'] ?? '');
                        $channelMembers[$i]['lastname'] = htmlspecialchars_decode($channelMembers[$i]['lastname'] ?? '');
                        $channelMembers[$i]['email'] = htmlspecialchars_decode($channelMembers[$i]['email'] ?? '');
                        $channelMembers[$i]['jobtitle'] = htmlspecialchars_decode($channelMembers[$i]['jobtitle'] ?? '');
                    }

                    $totalmembers = $db->get("
                        SELECT count(1) AS totalmembers 
                        FROM `groupmembers` a 
                            JOIN users b ON b.userid=a.userid 
                        WHERE b.companyid='{$_COMPANY->id()}' 
                          AND (a.`groupid`='{$groupid}' 
                                   AND FIND_IN_SET({$channelid},a.channelids) 
                                   AND a.`isactive`='1' 
                                   AND b.`isactive`='1' 
                                   AND a.`anonymous`='0'
                            )"
                    )[0]['totalmembers'];


                    exit(self::buildApiResponseAsJson($method, ['totalChannelMembers' => $totalmembers, 'channelMembers' => $channelMembers, 'isChannelMember' => $isChannelMember], 1, gettext('Channel members list.'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Channel members listing not allowed'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Group not found'), 200));
            }
        }
    }

    /**
     * Get All my groups
     * @param $get
     * @param $this
     */
    public function getmyJoinedGroups($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getmyJoinedGroups";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('page' => @$get['page']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            $page = intval($get['page']);
            $start = ($page - 1) * 30;
            $end = 30;
            $grouptype = Group::GROUP_TYPE_INVITATION_ONLY;

            $data = $db->get("
                SELECT memberid,groupmembers.groupid,groupname,coverphoto,app_coverphoto,overlaycolor,groupicon,groupmembers.userid,groupjoindate 
                FROM groupmembers 
                    LEFT JOIN `groups` USING (groupid) 
                WHERE groupmembers.userid='{$_USER->id()}' 
                  AND (groups.isactive=1 
                           AND groupmembers.isactive=1 
                           AND groups.group_type!={$grouptype}
                    )
                ORDER BY groupname ASC
                LIMIT {$start}, {$end}"
            );
            if (count($data) > 0) {
                for ($i = 0; $i < count($data); $i++) {
                    $data[$i]['joinStatus'] = '1';
                    $data[$i]['joinRequestStatus'] = 0;
                    $data[$i]['coverphoto'] = $data[$i]['app_coverphoto'] ? : $data[$i]['coverphoto'];
                    $data[$i]['myLeadStatus'] = $_USER->isGrouplead($data[$i]['groupid']);
                }
                exit(self::buildApiResponseAsJson($method, $data, 1, gettext('Joined groups'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('No groups joined'), 200));
            }
        }

    }

    /**
     * @param $get
     * @param $this
     */
    public function feedsOnMyGroupOrDiscoverPage($get,$version = "v1")
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "feedsOnMyGroupOrDiscoverPage";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('section' => @$get['section'], 'page' => @$get['page']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            $page = intval($get['page']);
            $section = intval($get['section']);
            $myFeedsOnly = intval($section);
            $timezone = $get['timezone'] ?? 'UTC';
            $group_category_id = intval($get['group_category_id'] ?? 0);
            $limit = MAX_HOMEPAGE_FEED_ITERATOR_ITEMS; 
          
            $utc_tz = new DateTimeZone('UTC');
            $local_tz = new DateTimeZone($timezone ?: 'UTC');
            $joinedChapters = array();
            $joinedChannels = array();
            $globalOnly = false;
            if ($myFeedsOnly == 1) {
                //$groupIdAry = Group::GetAvailableGroupsForGlobalFeeds('ERG',1);
                $groupIdAry = Group::GetAvailableGroupsForGlobalFeeds($group_category_id,1);
                $joinedChapterChannels = $db->ro_get("SELECT IFNULL(GROUP_CONCAT(`chapterid`),0) as joinedChapters, IFNULL(GROUP_CONCAT(`channelids`),0) as joinedChannels FROM `groupmembers` WHERE `userid`='{$_USER->id()}'");
                $joinedChapters = array_unique(explode(',', $joinedChapterChannels[0]['joinedChapters']));
                $joinedChannels = array_unique(explode(',', $joinedChapterChannels[0]['joinedChannels']));
            } else {
                //$groupIdAry = Group::GetAvailableGroupsForGlobalFeeds('ERG',0);
                $groupIdAry = Group::GetAvailableGroupsForGlobalFeeds($group_category_id,0);
                $globalOnly = true;
            }

            if (empty($groupIdAry)) {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('No content available'), 200));
            }
            
            $groupIdAry[] = 0; // Add back global group


            $finalGroupIdAry = array();
            foreach ($groupIdAry as $gid) {
                if ($_USER->canViewContent($gid)) {
                    $finalGroupIdAry[] = $gid;
                }
            }
            if (in_array(0,$groupIdAry)) {
                $finalGroupIdAry[] = 0;
            }

            $feeds = array();
            $maxAutoFeatch = 1;
             // $include_content_types = Content::GetAvailableContentTypes();
            // Load home feeds 
            //filters are not available for the mobile app
            $include_content_types = Array('post','event','newsletter','album', 'discussion'); 
            // check for disabled modules
            $appCustomization = $_COMPANY->getAppCustomization();
            $keyMappings = [
                'post' => 'post',
                'event' => 'event',
                'newsletter' => 'newsletters',
                'album' => 'albums',
                'discussion'=>'discussions'
            ];

            foreach ($include_content_types as $key => $contentType) {
                $appCustomizationKey = $keyMappings[$contentType];

                if(!isset($appCustomization[$appCustomizationKey]) || !$appCustomization[$appCustomizationKey]['enabled']){
                    unset($include_content_types[$key]);
                }
            }
            do{ 
                $contents  = Content::GetContent($finalGroupIdAry,$globalOnly, $page, $limit, $include_content_types);
                $contentsCount = count($contents);
                $skipLastContent = ($contentsCount > MAX_HOMEPAGE_FEED_ITERATOR_ITEMS);
                $index = 0;
                $maxAutoFeatch++;
                $page++;
            
                foreach($contents as $content){
                    if ($skipLastContent && $index == MAX_HOMEPAGE_FEED_ITERATOR_ITEMS){ // Don't process last content because it is fetched only for pagination purpose
                        break;
                    }
                    $index++;
                    $row = array();
                    if ($myFeedsOnly ==1 ) {
                        // For my feeds section, if the content is chapter or channel specific then only show the rows that have chapter or channels that the user subscribes to
                        // If we are requested to return content for joined chapters then we will filter out rows that have chapter id set but chapter id does not match
                        if (!empty($content['content_chapterids']) && empty(array_intersect($joinedChapters, explode(',', $content['content_chapterids'])))) {
                        //$feeds[] = $row; // Return empty row or empty content type so that we can still keep the correct count of rows for 'load more' to work
                            continue;
                        }
                        if (!empty($content['content_channelids']) && empty(array_intersect($joinedChannels, explode(',', $content['content_channelids'])))) {
                            //$feeds[] = $row; // Return empty row or empty content type so that we can still keep the correct count of rows for 'load more' to work
                            continue;
                        }
                    }
                    $row['content_type'] = ''; // If row was skipped, e.g. chapter match did not happen then type is left as 0 to not match anything
                    
                    if ($content['content_groupids'] == 0) {
                        $row['groupname'] = $_COMPANY->getAppCustomization()['group']['groupname0'];
                        $row['groupname_short'] = $_COMPANY->getAppCustomization()['group']['groupname0'];
                        $row['overlaycolor'] = '';
                        $row['coverphoto'] = '';
                        $row['groupicon'] = '';
                    } else {
                        $group = Group::GetGroup($content['content_groupids']);
                        $row['groupname'] =  $group->val('groupname');
                        $row['groupname_short'] = $group->val('groupname_short');
                        $row['overlaycolor'] = $group->val('overlaycolor');
                        $row['coverphoto'] = $group->val('coverphoto');
                        $row['groupicon'] = $group->val('groupicon');
                    }
                    if ($content['content_type'] == 'event'){
                        $event = Event::GetEventFromCache($content['content_id']);
                        if ($event){
                            $eventChapters = array();
                            if ($event->val("collaborating_groupids")){
                                $colleboratedIds = explode(',',$event->val("collaborating_groupids"));
                                $skip = true;
                                foreach($colleboratedIds as $colleboratedId){
                                    if(in_array($colleboratedId,$finalGroupIdAry)){
                                        $skip = false;
                                        break;
                                    }
                                }
                                if ($skip){
                                    continue; // Skip event not related to filtered groups
                                }
                                if (!empty($event->val('chapterid'))) {
                                    $chapters = Group::GetChapterNamesByChapteridsCsv($event->val('chapterid'));
                                    foreach($chapters as $chapter){
                                        $eventChapters[] = array(
                                            'chapterid'=>$chapter['chapterid'],
                                            'chaptername'=>htmlspecialchars_decode($chapter['chaptername'] ?? ''),
                                            'colour'=>$chapter['colour'],
                                            'isactive' => $chapter['isactive']
                                        );
                                    }
                                }
                            } elseif ($event->val('zoneid') != $_ZONE->id()) {
                                // FILTER_GLOBAL_EVENTS_OF_COLLABORATIVE_ZONES section
                                continue; // Skip events from other zones that are not collaborative, i.e. global events.
                            } else {
                                $eventChapters = Group::GetChaptersCSV($event->val('chapterid'), $event->val('groupid'));
                            }


                            $row['companyid'] = $event->val('companyid');
                            $row['eventid'] = $event->val('eventid');
                            $row['userid'] = $event->val('userid');
                            $row['event_series_id'] = $event->val('event_series_id');
                            $row['groupid'] = $event->val('groupid');
                            $row['chapterid'] = $event->val('chapterid');
                            $row['channelid'] = $event->val('channelid');
                            $row['eventtitle'] = htmlspecialchars_decode($event->val('eventtitle'));
                            $row['eventvanue'] = $event->val('eventvanue');
                            $row['vanueaddress'] = $event->val('vanueaddress');
                            $row['event_description'] = $event->val('event_description');
                            $row['addedon'] = (int)strtotime($event->val('publishdate'));
                            $row['rsvp_display'] = $event->val('rsvp_display');
                            $row['start'] = (int)strtotime($event->val('start'));
                            $row['end'] = (int)strtotime($event->val('end'));
                            $row['event_attendence_type'] = $event->val('event_attendence_type');
                            $row['web_conference_sp'] = $event->val('web_conference_sp');
                            $row['publishdate'] = $event->val('publishdate');
                            $row['isactive'] = $event->val('isactive');
                            $row['joinersCount'] = $event->getJoinersCount();
                            $row['pin_to_top'] = $event->val('pin_to_top');
                            $row['joinStatus'] = $_USER->isGroupMember($event->id());
                            $row['localStart'] = (new DateTime($event->val('start'), $utc_tz))->setTimezone($local_tz);
                            $row['collaboratedWith'] = array();
                            if ($event->val('collaborating_groupids')) {
                                $row['collaboratedWith'] = $db->ro_get("SELECT `groupid`, `groupname`, `groupname_short`,`overlaycolor`,`groupicon` FROM `groups` WHERE `groupid` IN(" . $event->val('collaborating_groupids') . ") AND `isactive`=1");
                            }
                            $row['type'] = 1;
                            $row['filetype'] = '';
                            $row['totalComments'] = (int) Event::GetCommentsTotal($event->id());

                            $row['chapters'] = $eventChapters;
                            $channel = Group::GetChannelName($event->val('channelid'), $event->val('groupid'));
                            $row['channelName'] = $event->val('channelid') ? $channel['channelname'] : '';
                            $row['channelColor'] = $event->val('channelid') ? $channel['colour'] : '';
                            $row['likeStatus'] = Event::GetUserLikeStatus($event->id()) ? 1 : 2;; // Should be int
                            $row['likeCount'] = (int)Event::GetLikeTotals($event->id());; // Should be Int
                            $row['joinRequestStatus'] = Team::GetRequestDetail($event->val('groupid'),0) ? 1 : 0;
                            $row['myLeadStatus'] = $_USER->isGrouplead($event->val('groupid')); // Should be boolean

                        }
                    } elseif($content['content_type'] == 'post') {
                        $post = Post::GetPostFromCache ($content['content_id']);
                        if ($post){
                            if ($post->val('zoneid') != $_ZONE->id()) {
                                continue; // Skip Post from other zones that are not collaborative, i.e. global post.
                            }
                            $row['postid'] = $post->val('postid');
                            $row['groupid'] = $post->val('groupid');
                            $row['companyid'] = $post->val('companyid');
                            $row['userid'] = $post->val('userid');
                            $row['chapterid'] = $post->val('chapterid');
                            $row['channelid'] = $post->val('channelid');
                            $row['title'] = htmlspecialchars_decode($post->val('title'));
                            $row['post'] = $post->val('post');
                            $row['addedon'] = (int)strtotime($post->val('publishdate'));
                            $row['pin_to_top'] = $post->val('pin_to_top');
                            $row['isactive'] = $post->val('isactive');
                            $row['publishdate'] = $post->val('publishdate');
                            $row['type'] = 2;
                            $row['totalComments'] = (int) Post::GetCommentsTotal($post->id()); // Should be int
                            $row['joinersCount'] = 0; // Should be int
                            $row['chapters'] = Group::GetChaptersCSV($post->val('chapterid'), $post->val('groupid'));
                            $channel = Group::GetChannelName($post->val('channelid'), $post->val('groupid'));
                            $row['channelName'] = $post->val('channelid') ? $channel['channelname'] : '';
                            $row['channelColor'] = $post->val('channelid') ? $channel['colour'] : '';
                            $row['likeStatus'] = Post::GetUserLikeStatus($post->id()) ? 1 : 2; // Should be int
                            $row['likeCount'] = (int)Post::GetLikeTotals($post->id());
                            $row['joinStatus'] = $_USER->isGroupMember($post->val('groupid'));
                            $row['joinRequestStatus'] = Team::GetRequestDetail($post->val('groupid'),0) ? 1 : 0;
                            $row['myLeadStatus'] = $_USER->isGrouplead($post->val('groupid'));
                            $row['start'] = 0;
                            $row['end'] = 0;
                        }

                    } elseif ($content['content_type'] == 'newsletter') {
                        $newsletter = Newsletter::GetNewsletterFromCache($content['content_id']);
                        if ($newsletter){
                            if ($newsletter->val('zoneid') != $_ZONE->id()) {
                                continue; // Skip Newsletter from other zones that are not collaborative, i.e. global newsletter.
                            }
                            $row['newsletterid'] = $newsletter->val('newsletterid');
                            $row['groupid'] = $newsletter->val('groupid');
                            $row['companyid'] = $newsletter->val('companyid');
                            $row['zoneid'] = $newsletter->val('zoneid');
                            $row['userid'] = $newsletter->val('userid');
                            $row['chapterid'] = $newsletter->val('chapterid');
                            $row['channelid'] = $newsletter->val('channelid');
                            $row['newslettername'] = htmlspecialchars_decode($newsletter->val('newslettername'));
                            $row['templateid'] = $newsletter->val('templateid');
                            $row['newsletter'] = $newsletter->val('newsletter');
                            $row['pin_to_top'] = $newsletter->val('pin_to_top');
                            $row['publish_to_email'] = $newsletter->val('publish_to_email');
                            $row['chapters'] = Group::GetChaptersCSV($newsletter->val('chapterid'), $newsletter->val('groupid'));
                            $channel = Group::GetChannelName($newsletter->val('channelid'),$newsletter->val('groupid'));
                            $row['channelName'] = $newsletter->val('channelid') ? $channel['channelname'] : '';
                            $row['channelColor'] = $newsletter->val('channelid') ? $channel['colour'] : '';
                            $row['addedon'] = (int)strtotime($newsletter->val('publishdate'));
                            $row['publishdate'] = $newsletter->val('publishdate');
                            $row['createdon'] = $newsletter->val('createdon');
                            $row['modifiedon'] = $newsletter->val('modifiedon');
                            $row['isactive'] = $newsletter->val('isactive');
                            $row['template'] = $newsletter->val('template');
                            $row['timezone'] = $newsletter->val('timezone');
                            $row['version'] = $newsletter->val('version');
                            $row['type'] = 3; // Should be int
                            $row['start'] = 0;
                            $row['end'] = 0;
                        }
                    }
                    elseif ($content['content_type'] == 'album') {
                        $album = Album::GetAlbumFromCache($content['content_id']);
                        if ($album){
                            if ($album->val('zoneid') != $_ZONE->id()) {
                                continue; // Skip Albums from other zones
                            }
                            $row['albumid'] = $album->val('albumid');
                            $row['groupid'] = $album->val('groupid');
                            $row['chapterid'] = $album->val('chapterid');
                            $row['channelid'] = $album->val('channelid');
                            $channel = Group::GetChannelName($album->val('channelid'),$album->val('groupid'));
                            $row['channelName'] = $album->val('channelid') ? $channel['channelname'] : '';
                            $row['channelColor'] = $album->val('channelid') ? $channel['colour'] : '';
                            $row['title'] = htmlspecialchars_decode($album->val('title'));
                            $row['cover'] = $album->val('cover_mediaid');
                            $row['addedon'] = (int)strtotime($album->val('addedon'));
                            $mediaList = $album->getAlbumMediaListForFeed();
                            $row['media_ids_json'] = array_column($mediaList,'album_mediaid');
                            $row['preview_urls'] = array_column($mediaList,'thumbnail_url');
                            $row['album_total_likes'] = $album->getAlbumTotalLikes();
                            $row['album_total_comments'] = $album->getAlbumTotalComments();
                            $row['chapters'] = Group::GetChaptersCSV($album->val('chapterid'), $album->val('groupid'));
                            $row['pin_to_top'] = '0';
                            $row['content_type'] = 'album';
                            $row['type'] = 4; // Should be int
                            $row['start'] = 0;
                            $row['end'] = 0;
                        }else{
                            $row = [];
                        }
                    }
                    elseif ($content['content_type'] == 'discussion') {
                        $discussions = Discussion::GetDiscussionFromCache($content['content_id']);
                        if ($discussions){
                            if ($discussions->val('zoneid') != $_ZONE->id()) {
                                continue; // Skip Discussion from other zones
                            }
                            $row['discussionid'] = $discussions->val('discussionid');
                            $row['groupid'] = $discussions->val('groupid');
                            $row['chapterid'] = $discussions->val('chapterid');
                            $row['channelid'] = $discussions->val('channelid');
                            $channel = Group::GetChannelName($discussions->val('channelid'),$discussions->val('groupid'));
                            $row['channelName'] = $discussions->val('channelid') ? $channel['channelname'] : '';
                            $row['channelColor'] = $discussions->val('channelid') ? $channel['colour'] : '';
                            $row['title'] = htmlspecialchars_decode($discussions->val('title'));
                            $row['discussion'] = $discussions->val('discussion');
                            $row['addedon'] = (int)strtotime($discussions->val('createdon'));
                            $row['pin_to_top'] = $discussions->val('pin_to_top');
                            $row['total_likes'] = Discussion::GetLikeTotals($discussions->val('discussionid'));
                            $row['total_comments'] = Discussion::GetCommentsTotal($discussions->val('discussionid'));
                            $row['chapters'] = Group::GetChaptersCSV($discussions->val('chapterid'), $discussions->val('groupid'));
                            $row['content_type'] = 'discussion';
                            $row['type'] = 5; // Should be int
                            $row['start'] = 0;
                            $row['end'] = 0;
                        }
                    }
                    if(!empty($row)){
                        $feeds[] = $row;
                    }
                    
                }
            } while ($version == 'v2' &&  count($feeds) < MAX_HOMEPAGE_FEED_ITERATOR_ITEMS &&  $maxAutoFeatch < 10);
            
            $payload = $feeds;
            if ($version == 'v2') {
                $payload = array('feeds'=>$feeds,'page'=>$page);
            }
            exit(self::buildApiResponseAsJson($method, $payload, 1, gettext('All feeds'), 200));
        }
    }

    public function feedsOnMyGroupOrDiscoverPageV2($get)
    {
        $this->feedsOnMyGroupOrDiscoverPage($get,'v2');
    }


    /**
     * @param $get
     * @param $this
     */
    public function viewPost($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "viewPost";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();

        $check = array('postid' => @$get['postid'], 'page' => @$get['page']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }

            if (!$_COMPANY->getAppCustomization()['post']['enabled']) { 
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('Access denied. The %s feature is disabled. Please contact your administrator.'),Post::GetCustomName(true)), 400));
            }

            $postid = intval($get['postid']);
            $page = intval($get['page']);
            $start = ($page - 1) * 30;
            $end = 30;

            $data = $db->get("
                SELECT post.`postid`, post.`companyid`, post.`groupid`, post.`userid`,post.title, post.`post`, post.`publishdate`,`post`.chapterid,`post`.channelid,`post`.pin_to_top,`post`.isactive, users.firstname,users.lastname,users.picture,users.jobtitle 
                FROM `post` 
                    LEFT JOIN users ON users.userid=post.userid 
                WHERE post.companyid='{$_COMPANY->id()}' 
                  AND `post`.zoneid='{$_ZONE->id()}' 
                  AND post.`postid`='{$postid}' 
                  AND post.isactive='1' "
            );

            if (count($data) > 0) {

                if (!$_USER->canViewContent($data[0]['groupid'])) {
                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('This is a private %1$s. Only members can view the content.'), $_COMPANY->getAppCustomization()['group']['name-short']), 200));
                }
                $p = Post::ConvertDBRecToPost($data[0]);
                $data[0]['groupJoinStatus'] = $_USER->isGroupMember($data[0]['groupid']);
                $data[0]['joinRequestStatus'] = Team::GetRequestDetail($data[0]['groupid'],0) ? 1 : 0;

                $data[0]['myLikeType'] = Post::GetUserReactionType($p->id());
                $data[0]['likeStatus'] = !empty($data[0]['myLikeType']) ? 1 : 2; // Should be int
                $data[0]['likeCount'] = (int)Post::GetLikeTotals($p->id()); // Should be int
                $data[0]['likeTotalsByType'] = Post::GetLikeTotalsByType($p->id());

                $data[0]['userid'] = $_COMPANY->encodeId($data[0]['userid']);
                $data[0]['title'] = htmlspecialchars_decode($data[0]['title'] ?? '');
                $data[0]['chapters'] = Group::GetChaptersCSV($data[0]['chapterid'], $data[0]['groupid']);
                global $post_css;
                $data[0]['post'] = $post_css . '<div class="post-inner">' . $data[0]['post'] . '</div>';
                $channel = Group::GetChannelName($data[0]['channelid'], $data[0]['groupid']);
                $data[0]['channelName'] = $data[0]['channelid'] ? htmlspecialchars_decode($channel['channelname'] ?? '') : '';
                $data[0]['channelColor'] = $data[0]['channelid'] ? htmlspecialchars_decode($channel['color'] ?? '') : '';
                $comments = $this->getCleanComments('Post',$postid);
                $data[0]['totalComments'] = (int) Post::GetCommentsTotal($postid); // Should be int
                $data[0]['comments'] = $comments;
                $data[0]['likesCommentsFeature']= array ('likes'=>$_COMPANY->getAppCustomization()['post']['likes'],'comments'=>$_COMPANY->getAppCustomization()['post']['comments']);
                $data[0]['pin_to_top'] = $data[0]['pin_to_top'];

                $attachments = array();
                $attachmentsObj = $p->getAttachments(false);
                foreach ($attachmentsObj as $attachment) {
                    $attachments[] = array(
                                        'attachmentid'=>$attachment->encodedId(),
                                        'icon'=>$attachment->getImageIcon(true),
                                        'displayName'=>$attachment->getDisplayName(),
                                        'fileSize'=> $attachment->getReadableSize()
                                    );
                }
                $data[0]['attachments']  = $attachments;
                exit(self::buildApiResponseAsJson($method, $data[0], 1, gettext('Post detail'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this announcement has been removed.'), 200));
            }
        }
    }

    /**
     * This API is used to get newsletter detail for display, e.g. when a newsletter notification is sent out, the
     * the app will use newsletterid provided in the API to fetch the newsletter.
     * @param $get
     * @param $this
     */
    public function viewNewsletter($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "viewNewsletter";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();

        $check = array('newsletterid' => @$get['newsletterid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }

            if (!$_COMPANY->getAppCustomization()['newsletters']['enabled']) { 
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Access denied. The newsletter feature is disabled. Please contact your administrator.'), 400));
            }

            $newsletterid = intval($get['newsletterid']);
            $data = $db->get("
                SELECT newsletters.newsletterid, newsletters.newslettername, newsletters.newsletter as newsletter, newsletters.groupid, newsletters.chapterid, newsletters.channelid, 
                       IFNULL(newsletters.publishdate, newsletters.modifiedon) AS publishdate 
                FROM newsletters 
                WHERE newsletters.companyid = {$_COMPANY->id()} 
                  AND ( `newsletters`.zoneid = {$_ZONE->id()} 
                            AND newsletters.newsletterid='{$newsletterid}'
                            AND newsletters.isactive=1
                      )"
            );

            if (count($data) > 0) {
                if (!$_USER->canViewContent($data[0]['groupid'])) {
                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('This is a private %1$s. Only members can view the content.'), $_COMPANY->getAppCustomization()['group']['name-short']), 200));
                }
                $data[0]['newslettername'] = htmlspecialchars_decode($data[0]['newslettername'] ?? '');
                $data[0]['newsletter'] = $data[0]['newsletter'];
                $data[0]['chapters'] = Group::GetChaptersCSV($data[0]['chapterid'], $data[0]['groupid']);
                $channel = Group::GetChannelName($data[0]['channelid'], $data[0]['groupid']);
                $data[0]['channelName'] = $data[0]['channelid'] ? htmlspecialchars_decode($channel['channelname'] ?? '') : '';
                $data[0]['channelColor'] = $data[0]['channelid'] ? $channel['colour'] : '';
                $data[0]['likesCommentsFeature']= array ('likes'=>$_COMPANY->getAppCustomization()['newsletters']['likes'],'comments'=>$_COMPANY->getAppCustomization()['newsletters']['comments']);
                exit(self::buildApiResponseAsJson($method, $data[0], 1, gettext('Newsletter detail'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this newsletter has been removed.'), 200));
            }
        }
    }

    /**
     * @param $get
     * @param $this
     */
    public function addComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "addComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('postid' => @$get['postid'], 'comment' => @$get['comment']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            $postid = $get['postid'];
            $comment = $get['comment'];
            $post = Post::GetPost($postid);
            $commentid = 0;
            if (isset($get['commentid'])) {
                $commentid = $get['commentid'];
            }
            $media = array();
            if (!empty($_FILES['media']['name'])){
                $media = $_FILES;
            }
            if ($post) {
                if ($commentid > 0) {
                    // Sub Comment
                    if (Comment::CreateComment_2($commentid, $comment,  $media)) {
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added'), 200));
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again.'), 200));
                    }
                } else {

                    if (Post::CreateComment_2($postid, $comment,  $media)) {
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added'), 200));
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again.'), 200));
                    }
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this announcement has been removed.'), 200));
            }

        }
    }

    /**
     * @param $get
     * @param $this
     */
    public function getDepartments($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getDepartments";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $data = $db->get("
            SELECT `departmentid`, `department` 
            FROM `departments` 
            WHERE `companyid`='{$_COMPANY->id()}' 
              AND `isactive`='1'"
        );
        for($i=0;$i<count($data);$i++){
            $data[$i]['department'] = htmlspecialchars_decode($data[$i]['department'] ?? '');
        }
        exit(self::buildApiResponseAsJson($method, $data, 1, gettext('All departments'), 200));
    }

    /**
     * View Event, Provide Options to Join and existing Join Status
     * @param $get
     * @param $this
     */
    public function viewEvent($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "viewEvent";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('eventid' => @$get['eventid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }

            $eventid = intval($get['eventid']);
            $event = Event::GetEvent($eventid);
            if ($event && $event->val('isactive') == Event::STATUS_ACTIVE) {

                if($event->val('groupid') && !$_USER->canViewContent($event->val('groupid'))){
                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('This is a private %1$s. Only members can view the content.'), $_COMPANY->getAppCustomization()['group']['name-short']), 200));
                }
    
                
                [$iAccessible,$errorMessage] = $event->isEventAccessible();

                if (!$iAccessible) {
                    exit(self::buildApiResponseAsJson($method, '', 0, $errorMessage, 200));
                }

                $data = $this->eventObjToArray($event);
                $attendance = $db->get("
                    SELECT `joineeid`,`checkedin_date` 
                    FROM `eventjoiners` 
                    WHERE `eventid`='{$event->id()}' 
                      AND `userid`='{$_USER->id()}'"
                );
                $data['attendance'] = "0";
                if (count($attendance) && !empty($attendance['checkedin_date'])) {
                    $data['attendance'] = "1";
                }
                if (!empty($data['web_conference_link'])){
                    $data['web_conference_link'] = $event->getWebConferenceLink();
                }

                $data['myJoinStaus'] = (int)$db->myEventJoinStatus($_USER->id(), $eventid); // Should be int
                $data['groupJoinStatus'] = $_USER->isGroupMember($data['groupid']);
                $data['joinRequestStatus'] = Team::GetRequestDetail($data['groupid'],0) ? 1 : 0;
                $data['start'] = (int)strtotime($data['start']);
                $data['end'] = (int)strtotime($data['end']);
                $data['eventSubtitle'] = self::Util_GetEventSubtitle ($event);

                $eventChapters = array();
                if ($event->val("collaborating_groupids")){
                    if (!empty($event->val('chapterid'))) {
                        $chapters = Group::GetChapterNamesByChapteridsCsv($event->val('chapterid'));
                        foreach($chapters as $chapter){
                            $eventChapters[] = array(
                                'chapterid'=>$chapter['chapterid'],
                                'chaptername'=>htmlspecialchars_decode($chapter['chaptername'] ?? ''),
                                'colour'=>$chapter['colour'],
                                'isactive' => $chapter['isactive']
                            );
                        }
                    }
                } else {
                    $eventChapters = Group::GetChaptersCSV($event->val('chapterid'), $event->val('groupid'));
                }

                $data['chapters'] = $eventChapters;
                $channel = Group::GetChannelName($data['channelid'], $data['groupid']);
                $data['channelName'] = $data['channelid'] ? htmlspecialchars_decode($channel['channelname'] ?? '') : '';
                $data['channelColor'] = $data['channelid'] ? $channel['colour'] : '';
                global $post_css;
                $data['event_description'] = $post_css . '<div class="post-inner">' . $data['event_description'] . '</div>';
                $rsvpOptions= new stdClass;
                $joinersCount = 0;
                $eventJoiners = array();

                if (!$event->isSeriesEventHead()){
                    if ($event->val('eventclass') !='holiday') {
                        $rsvpOptions = $event->getMyRSVPOptions();

                        if (($event->isPublished() && $event->hasEnded()) || !$event->val('rsvp_enabled')){
                            $rsvpOptions['message'] = "";
                        }

                        $rsvpBtns = $rsvpOptions['buttons'];
                        $rsvpButtons = [];
                        foreach ($rsvpBtns as $key => $value) {
                            $surveyLink = "";
                            if ($_COMPANY->getAppCustomization()['event']['enable_event_surveys'] && $event->isPublished() && !$event->hasEnded()) {
                                if ($event->isEventSurveyAvailable(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'])){
                                    $surveyLink = $_COMPANY->getAdminURL().'/native/eventPrePostSurvey.php?eventid='.$_COMPANY->encodeId($event->val('eventid')).'&trigger='.Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'].'&joinStatus='.$_COMPANY->encodeId($key);
                                }
                            }
                            $rsvpButtons[] = array('id' => (string)$key, 'name' => $value,'surveyLink'=>$surveyLink);
                        }
                        $rsvpOptions['buttons'] = $rsvpButtons;

                        $eventRsvpOverMessage = '';
                        if ($event->isPublished() && $event->hasEnded()){
                            $eventRsvpOverMessage = gettext("This event is over");
                        } elseif($event->isPublished() && $event->hasRSVPEnded() && !$event->hasEnded()) {
                            $eventRsvpOverMessage = $event->val('rsvp_enabled') ? gettext("Sorry, this Event RSVP has closed. We are no longer accepting RSVP updates for this event.") : '';
                        }
                        $rsvpOptions['eventRsvpOverMessage'] = $eventRsvpOverMessage;
                    }
                    $joinersCount = (int)($event->val('rsvp_display') > 0 ? $event->getJoinersCount() : 0); // Should be int
                    $eventJoiners =  $event->val('rsvp_display') > 1 ? $event->getRandomJoiners(12) : array();

                    $comments = $this->getCleanComments('Event',$event->id());
                    $data['totalComments'] = (int) Event::GetCommentsTotal($event->id()); // Should be int
                    $data['comments'] = $comments;

                    $data['myLikeType'] = Event::GetUserReactionType($event->id());
                    $data['likeStatus'] = !empty($data['myLikeType']) ? 1 : 2; // Should be int
                    $data['likeCount'] = (int)Event::GetLikeTotals($event->id()); 
                    $data['likeTotalsByType'] = Event::GetLikeTotalsByType($event->id());
                }

                $eventSeriesData = array();
                $event_series_name = null;
                if ($event->val('event_series_id')) {
                    $getSeriesAllEvents = Event::GetEventsInSeries($event->val('event_series_id'));
                    $seriesEventArray = [];
                    // Covert all series event obj to array
                    foreach ($getSeriesAllEvents as $seriesEvent) {
                        $seriesEvt = $this->eventObjToArray($seriesEvent);
                        $seriesEvt['start'] = (int)strtotime($seriesEvt['start']);
                        $seriesEvt['end'] = (int)strtotime($seriesEvt['end']);

                        $comments = $this->getCleanComments('Event',$seriesEvt['eventid']);
                        $seriesEvt['totalComments'] = (int) Event::GetCommentsTotal($seriesEvt['eventid']); // Should be int
                        $seriesEvt['comments'] = $comments;
                        $seriesEvt['likeStatus'] = Event::GetUserLikeStatus($seriesEvt['eventid']) ? 1 : 2; // Should be int
                        $seriesEvt['likeCount'] = (int)Event::GetLikeTotals($seriesEvt['eventid']);
                        $seriesEventArray[] = $seriesEvt;
                    }

                    if ($event->isSeriesEventHead()){
                        $event_series_name = $event->val('eventtitle');
                        $eventSeriesData = $seriesEventArray;
                    } else {
                        $eventGroup = Event::GetEvent($event->val('event_series_id'));
                        $event_series_name = $eventGroup->val('eventtitle');
                        $eventSeriesData = $seriesEventArray;
                    }
                }
                $eventSeriesData = array_values($eventSeriesData);
                $data['event_series_name'] = htmlspecialchars_decode($event_series_name??'');

                // volunteer needed count + volunteer role
                $eventVolunteerRequired = array();
                $volunteerNote = "";
                if ($_COMPANY->getAppCustomization()['event']['volunteers'] && !$event->hasEnded() && $event->isPublished() && $event->val('rsvp_enabled')){
                    $eventVolunteerRequests = $event->getEventVolunteerRequests();
                    
                    foreach ($eventVolunteerRequests as $key => $volunteer) {
                        if (isset($volunteer['hide_from_signup_page']) && $volunteer['hide_from_signup_page'] == 1) {
                            continue;
                        }
                        $volunteerCount = $event->getVolunteerCountByType($volunteer['volunteertypeid']);
                        $volunteerRequestCount = $volunteer['volunteer_needed_count'];
                        $volunteerStatus = $event->isEventVolunteerSignup($_USER->id(), $volunteer['volunteertypeid']);
                        $canSendRequest = true;
                        if ($volunteerStatus){
                            $canSendRequest = false;
                        }
                        if ($volunteerCount >= $volunteerRequestCount && !$volunteerStatus){
                            $canSendRequest = false;
                        }
                        $volunteerAvailability = $volunteerRequestCount - $volunteerCount; 
                        $volunteertype = $event->getVolunteerTypeValue($volunteer['volunteertypeid']);

                        $volunteerConfirmation = (object)array();
                        $volunteerTypeData = $event->getEventVolunteerRequestByVolunteerTypeId($volunteer['volunteertypeid']);
                        if ($canSendRequest && $volunteerTypeData) {
                            $description_html = '';
                            if (array_key_exists('volunteer_description', $volunteerTypeData) && !empty($volunteerTypeData['volunteer_description'])) {
                                $description_html = $volunteerTypeData['volunteer_description'];
                            }

                            $volunteerConfirmation = array(
                                'title' => gettext('Please confirm!'),
                                'confirmMessage' => gettext("Are you sure your want to sign up for this role?"),
                                'description' => $description_html
                            );
                        }

                        $eventVolunteerRequired[] = array('volunteertypeid'=>$volunteer['volunteertypeid'],'volunteer_type'=>$volunteertype, 'volunteer_needed_count'=>$volunteer['volunteer_needed_count'],'volunteer_availability_count'=>$volunteerAvailability,'my_join_status'=>$volunteerStatus,'can_send_request'=>$canSendRequest,
                        'volunteer_confirmation' => $volunteerConfirmation
                        );
                    }

                    if($event->getMyRsvpStatus() == 0){
                        $volunteerNote = gettext("Note: If you sign up for a role, your RSVP for this event will be automatically added.");
                    }
                } 
                
                $data['eventVolunteerRequests'] = $eventVolunteerRequired;
                $data['eventVolunteerNote']  = $volunteerNote;
               
                $attachments = array();
                $attachmentsObj = $event->getAttachments(false);
                foreach ($attachmentsObj as $attachment) {
                    $attachments[] = array(
                                        'attachmentid'=>$attachment->encodedId(),
                                        'icon'=>$attachment->getImageIcon(true),
                                        'displayName'=>$attachment->getDisplayName(),
                                        'fileSize'=> $attachment->getReadableSize()
                                    );
                }
                $data['attachments']  = $attachments;
                $eventPrePostSurveys = array();

                if ($_COMPANY->getAppCustomization()['event']['enable_event_surveys']) {
                    
                    $userPreEventSurveyResponses = $event->getEventSurveyResponsesByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'],true);
                    $userPostEventSurveyResponses = $event->getEventSurveyResponsesByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT'],true);

                    if ($event->isPublished() && $event->hasEnded()){
                        $data['event_recording_link']  = true; 
                        if ($userPostEventSurveyResponses){
                            if(!empty($event->getEventSurveyResponsesByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT']))){
                                $buttonText = gettext("Update");
                                $title = gettext("Post event survey responses");
                            } else {
                                $buttonText = gettext("Respond");
                                $title = gettext("Post event survey is available");
                            }
                            $surveyLink = $_COMPANY->getAdminURL().'/native/eventPrePostSurvey.php?eventid='.$_COMPANY->encodeId($event->val('eventid')).'&trigger='.Event::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT'].'&joinStatus='.$_COMPANY->encodeId($event->getMyRsvpStatus());
                            $eventPrePostSurveys = array(
                                'buttonText'=>$buttonText,
                                'title' => $title,
                                'surveyLink' => $surveyLink
                            );
                        }

                    } elseif ($event->isPublished() && !$event->hasRSVPEnded() && !$event->hasEnded()) {   
                        $data['event_recording_link'] = false;  
                        $data['followup_notes'] = "";      
                        if ($userPreEventSurveyResponses){
                            if ($event->getEventSurveyResponsesByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'],true)){
                                $title =  gettext("Pre event survey responses");
                                $buttonText = gettext("Update");
                                $surveyLink = $_COMPANY->getAdminURL().'/native/eventPrePostSurvey.php?eventid='.$_COMPANY->encodeId($event->val('eventid')).'&trigger='.Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'].'&joinStatus='.$_COMPANY->encodeId($event->getMyRsvpStatus());
                                $eventPrePostSurveys = array(
                                    'buttonText'=>$buttonText,
                                    'title' => $title,
                                    'surveyLink' => $surveyLink
                                );
                            } 
                        }
                    }
                }
                
                $commentLikeFeature =  $event->val('eventclass') !='holiday' ? array ('likes'=>$_COMPANY->getAppCustomization()['event']['likes'],'comments'=>$_COMPANY->getAppCustomization()['event']['comments']) : array('likes'=>false,'comments'=>false);

                $disclaimersObj = Disclaimer::GetDisclaimersByIdCsv($event->val('disclaimerids'));
                $disclaimers = array();
                $lockMessage = '';
                if (!empty($disclaimersObj) && $event->isPublished() && !$event->hasEnded()){
                    foreach($disclaimersObj as $disclaimer){ 
                    
                        $disclaimer_language = $_USER->val('language');
                        $disclaimerMessage =  $disclaimer->getDisclaimerBlockForLanguage($disclaimer_language);
                
                        if (!empty($disclaimerMessage)){
                            $disclaimer_language = $disclaimerMessage['language'];
                        }
                        $consent_required = $disclaimer->val('consent_required');
                        $userConcent = array();
                        $concentHelpText = '';
                        if($consent_required){
                            $concentHelpText = gettext('Before accepting the waiver, please read the disclaimer. Click the disclaimer title to see the full details.');
                            $isDisclaimerAvailable = Disclaimer::IsDisclaimerAvailableV2($disclaimer->val('disclaimerid'),$event->id());
                            if (!$isDisclaimerAvailable){
                                $concentHelpText = "";
                                $userConcent = $disclaimer->getConsentForUserid($_USER->id(),$event->val('eventid'));
                            }
                        }
                        $disclaimers[] = array (
                            'concentHelpText' => $concentHelpText,
                            'isUserConcentDone' => !empty($userConcent),
                            'consentRequired' => $consent_required,
                            'consentType' => $disclaimer->val('consent_type'),
                            'disclaimerid' => $disclaimer->val('disclaimerid'),
                            'disclaimerTitle' => $disclaimerMessage['title'],
                            'disclaimer'=>$disclaimerMessage['disclaimer'],
                            'consentInputValue' =>$disclaimerMessage['consent_input_value'],
                            'consentInputLabel' => $disclaimer->val('consent_type') == 'text' ? sprintf(gettext("By typing in <strong><i>%s</i></strong> below, I provide my consent"),$disclaimerMessage['consent_input_value']) : gettext('I Agree'),
                            'consentLanguage' => $disclaimer_language,
                        );
                    }

                    if ($event->isPublished() && !$event->hasEnded() && !Disclaimer::IsAllWaiverAccepted($event->val('disclaimerids'),$eventid)){
                        $lockMessage = gettext('In order to RSVP for this Event please accept the Event Waivers above.');
                    }
                }

                exit(self::buildApiResponseAsJson($method,
                    [
                        'event' => $data,
                        'eventSeriesData' => $eventSeriesData,
                        'rsvpOptions' => $rsvpOptions,
                        'joinersCount' => $joinersCount,
                        'eventJoiners' => $eventJoiners,
                        'likesCommentsFeature' => $commentLikeFeature,
                        'eventSurvey' => (object) $eventPrePostSurveys,
                        'rsvp_disabled_message' => $event->val('rsvp_enabled')==0 ? gettext('RSVPs are not required for this event.') : '',
                        'disclaimers' => $disclaimers?:(object)[],
                        'eventRsvpLockMessage' =>$lockMessage,
                        //'collaboratedWith' => $collaboratedWithFormated,
                    ],
                    1, gettext('Event Detail'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this event has been removed.'), 200));
            }
        }
    }

    private function eventObjToArray($event)
    {
        global $_COMPANY, $_ZONE;

        return array(
            'eventid' => $event->val('eventid'),
            'groupid' => $event->val('groupid'),
            'chapterid' => $event->val('chapterid'),
            'channelid' => $event->val('channelid'),
            'userid' => $event->val('userid'),
            'event_series_id' => $event->val('event_series_id'),
            'eventtitle' => htmlspecialchars_decode($event->val('eventtitle') ?? ''),
            'start' => $event->val('start'),
            'end' => $event->val('end'),
            'timezone' => $event->val('timezone'),
            'eventrecurreing' => $event->val('eventrecurreing'),
            'eventvanue' => htmlspecialchars_decode($event->val('eventvanue') ?? ''),
            'venue_room' => htmlspecialchars_decode($event->val('venue_room')??''),
            'vanueaddress' => htmlspecialchars_decode($event->val('vanueaddress') ?? ''),
            'venue_info' => htmlspecialchars_decode($event->val('venue_info') ??''),
            'event_description' => $event->val('event_description'),
            'latitude' => $event->val('latitude'),
            'longitude' => $event->val('longitude'),
            'eventtype' => $event->val('eventtype'),
            'collaborate' => $event->val('collaborating_groupids'),
            'invited_groups' => $event->val('invited_groups'),
            'event_attendence_type' => $event->val('event_attendence_type'),
            'web_conference_link' => $event->val('web_conference_link'),
            'web_conference_detail' => htmlspecialchars_decode($event->val('web_conference_detail') ?? ''),
            'web_conference_sp' => $event->val('web_conference_sp'),
            'checkin_enabled' => $event->val('checkin_enabled'),
            'max_inperson' => $event->val('max_inperson'),
            'max_online' => $event->val('max_online'),
            'max_inperson_waitlist' => $event->val('max_inperson_waitlist'),
            'max_online_waitlist' => $event->val('max_inperson_waitlist'),
            'invited_locations' => $event->val('invited_locations'),
            'rsvp_dueby' => $event->val('rsvp_dueby'),
            'rsvp_restriction' => $event->val('rsvp_restriction'),
            'custom_fields' => $event->val('custom_fields'),
            'rsvp_display' => $event->val('rsvp_display'),
            'publishdate' => $event->val('publishdate'),
            'isactive' => $event->val('isactive'),
            'pin_to_top' => $event->val('pin_to_top'),
            'event_contact' => $event->val('event_contact'),
            'event_contact_phone_number' => $event->val('event_contact_phone_number'),
            'rsvp_enabled' => $event->val('rsvp_enabled'),
            'followup_notes' =>$event->val('followup_notes') ?? '',
            'event_recording_link' => $event->val('event_recording_link') ? true : false,
            'event_recording_note' => $event->val('event_recording_note') ? htmlspecialchars($event->val('event_recording_note')) : '',
            'event_recording_mark_as_watched' => ($_COMPANY->getAppCustomization()['event']['checkin'] && !empty($event->getMyCheckinDate()))
        );
    }

    /**
     * Deprecated
     * @param $get
     * @param $this
     */
    public function getEventJoinees($get)
    {
        $method = "getEventJoinees";
        exit(self::buildApiResponseAsJson($method, '', 0, gettext("Deprecated method"), 400));
    }

    /**
     * @param $get
     * @param $this
     */
    public function joinOrLeaveEvent($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "joinOrLeaveEvent";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $timezone = date_default_timezone_get();
        $check = array('eventid' => @$get['eventid'], 'type' => @$get['type']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $eventid = $get['eventid'];
            $type = $get['type'];
            $event = Event::GetEvent($eventid);
            if ($event) {
                $rsvpOptions = $event->getMyRSVPOptions();
                $rsvpTypes = $rsvpOptions['buttons'];

                if (array_key_exists($type, $rsvpTypes)) {
                    $res =  $event->joinEvent($_USER->id(), $type, 2,1);
                    if ($res) {
                        $rsvpStatus = $rsvpTypes[$type];
                        $successMessage = sprintf(gettext('Thank you for RSVPing %s to this event. Your RSVP has been recorded, and you will receive an email with a corresponding calendar hold.'),$rsvpStatus);
                        if($rsvpStatus === 'DECLINE'){
                            $successMessage = gettext('Thank you for RSVPing "'.$rsvpStatus.'" to this event. Your RSVP selection has been recorded.');
                        }
                        exit(self::buildApiResponseAsJson($method, '', 1, $successMessage, 200));
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again'), 200));
                    }
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Not a valid type'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this event has been removed.'), 200));
            }
        }
    }

    /**
     * Deprecated
     * @param $get
     * @param $this
     */
    public function getMyEvents($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getMyEvents";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();

        if (!$_COMPANY->getAppCustomization()['event']['enabled']) {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Event feature is disabled'), 200));
        }
        $joinedevents = $db->myJoinStatus($_USER->id());
        $section = $get['section'];
        if ($section == 2) {
            $data = $db->get("
                SELECT companyid, groupid, chapterid, channelid, `eventid`, `eventtitle`, `rsvp_display`, `start`,`end`, `eventvanue`, `vanueaddress`, `hostedby`, `addedon`,`event_description`,`collaborating_groupids`,publishdate 
                FROM `events` 
                WHERE eventid IN ({$joinedevents}) 
                  AND end < NOW() 
                  AND isactive=1 
                ORDER BY start ASC"
            );
            $msg = gettext("Past events");

        } else {
            $data = $db->get("
                SELECT companyid, groupid, chapterid, channelid, `eventid`, `eventtitle`, `rsvp_display`, `start`,`end`, `eventvanue`, `vanueaddress`, `hostedby`, `addedon`,`event_description`,`collaborating_groupids`,publishdate 
                FROM `events` 
                WHERE eventid IN ({$joinedevents}) 
                AND end > NOW()  
                AND isactive=1 
                ORDER BY start ASC"
            );
            $msg = gettext("Upcoming events");
        }

        if (count($data) > 0) {
            for ($i = 0; $i < count($data); $i++) {
                $evnt = Event::ConvertDBRecToEvent($data[$i]);
                $channel = Group::GetChannelName($data[$i]['channelid'], $data[$i]['groupid']);
                $data[$i]['eventtitle'] = htmlspecialchars_decode($evnt->val('eventtitle') ?? '');
                $data[$i]['eventvanue'] = htmlspecialchars_decode($evnt->val('eventvanue') ?? '');
                $data[$i]['vanueaddress'] = htmlspecialchars_decode($evnt->val('vanueaddress') ?? '');
                $data[$i]['event_description'] = $evnt->val('event_description');
                $data[$i]['joinersCount'] = $evnt->val('rsvp_display') > 0 ? $evnt->getJoinersCount() : 0; // Should be int
                $data[$i]['eventJoiners'] = $evnt->val('rsvp_display') > 1 ? $evnt->getRandomJoiners(6) : array();
                $data[$i]['myJoinStaus'] = 1;
                $data[$i]['month'] = date('F', strtotime($data[$i]['start']));
                $data[$i]['start'] = (int)strtotime($data[$i]['start']);
                $data[$i]['end'] = (int)strtotime($data[$i]['end']);
                $data[$i]['addedon'] = (int)strtotime($data[$i]['addedon']);
                $data[$i]['eventSubtitle'] = self::Util_GetEventSubtitle ($evnt);
                $data[$i]['chapters'] = Group::GetChaptersCSV($evnt->val('chapterid'), $evnt->val('groupid'));
                $data[$i]['channelName'] = $data[$i]['channelid'] ? $channel['channelname'] : '';
                $data[$i]['channelColor'] = $data[$i]['channelid'] ? $channel['colour'] : '';

                $data[$i]['totalComments'] = (int)Event::GetCommentsTotal($evnt->val('eventid')); // Should be int
                $data[$i]['likeStatus'] = Event::GetUserLikeStatus($evnt->val('eventid')) ? 1 : 2;; // Should be int
                $data[$i]['likeCount'] = (int)Event::GetLikeTotals($evnt->val('eventid'));
            }
            exit(self::buildApiResponseAsJson($method, $data, 1, $msg, 200));

        } else {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('No events'), 200));
        }
    }

    /**
     * @param $get
     * @param $this
     */
    public function getAllNotifications($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getAllNotifications";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('page' => @$get['page']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $page = intval($get['page']);
            $start = ($page - 1) * 30;
            $end = 30;

            $old = date("Y-m-d H:i:s", strtotime("-1 days"));

            $data = $db->get("
                SELECT `notificationid`, `section`, `userid`, `whodo`, `tableid`, `message`, `datetime`, `isread` 
                FROM `notifications` 
                WHERE `zoneid`='{$_ZONE->id()}' 
                  AND `userid`={$_USER->id()} 
                  AND (isread=2 || (isread=1 AND `datetime` > '{$old}') ) 
                ORDER BY notificationid DESC 
                LIMIT $start, $end"
            );

            if (count($data) > 0) {
                for ($i = 0; $i < count($data); $i++) {
                    $data[$i]['datetime'] = (int)strtotime($data[$i]['datetime']);
                    $userData = User::getUser($data[$i]['whodo']) ?? User::GetEmptyUser($data[$i]['whodo']);
                    if ($data[$i]['section'] == 2 || $data[$i]['section'] == 4 || $data[$i]['section'] == 5) {
                        $data[$i]['message'] = htmlspecialchars_decode(rtrim(($userData->val('firstname') . " " . $userData->val('lastname')), " ")) . " " . htmlspecialchars_decode($data[$i]['message'] ?? '');
                        $postObj = Post::GetPost($data[$i]['tableid']);
                        $data[$i]['detail'] = (substr(strip_tags($postObj->val('post')), 0, 100));
                        $data[$i]['username'] = htmlspecialchars_decode(rtrim(($userData->val('firstname') . " " . $userData->val('lastname')), " "));
                        $data[$i]['picture'] = $userData->val('picture');
                    } else {
                        $data[$i]['username'] = htmlspecialchars_decode(rtrim(($userData->val('firstname') . " " . $userData->val('lastname')), " "));
                        $data[$i]['picture'] = $userData->val('picture');
                        $data[$i]['detail'] = "";
                    }
                }
                exit(self::buildApiResponseAsJson($method, $data, 1, gettext('All notifications'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('No notifications'), 200));
            }
        }
    }

    /**
     * @param $get
     * @param $this
     */
    public function getUnreadNotificationCount($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getUnreadNotificationCount";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();

        $data = $db->get("
            SELECT count(`notificationid`) AS unReadCount 
            FROM `notifications` 
            WHERE `userid`={$_USER->id()} 
              AND isread='2'"
        );
        exit(self::buildApiResponseAsJson($method, array("unreadCount" => $data[0]['unReadCount']), 1, gettext("Unread count"), 200));
    }

    /**
     * @param $get
     * @param $this
     */
    public function setReadNotification($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "setReadNotification";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('notificationid' => @$get['notificationid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $notificationid = $get['notificationid'];

            if ($notificationid == 'all') {
                Notification::ReadAllNotifications();
                exit(self::buildApiResponseAsJson($method, '', 1, gettext("All viewed"), 200));
            } else {
                $notification = Notification::GetNotification($notificationid);
                if ($notification ){
                    $notification->readNotification();
                }
               exit(self::buildApiResponseAsJson($method, '', 1, gettext("Notification viewed"), 200));
            }

        }
    }

    /**
     * @param $get
     * @param $this
     */
    public function clearNotifications($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "clearNotifications";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('notificationid' => @$get['notificationid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $notificationid = $get['notificationid'];
            if ($notificationid == 'all') {
                Notification::DeleteAllNotifications();
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('All notification cleared'), 200));
            } else {
                $notification = Notification::GetNotification($notificationid);
                if ($notification){
                    $notification->deleteNotification();
                }
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Notification cleared'), 200));
            }

        }
    }

    /**
     * Record appUsage
     * @param $get
     * @param $this
     */
    public function appUsage($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson('appUsage', '', 0, gettext('Bad request'), 400));
        }
        $_USER->recordUsage($_ZONE->id(),'native');

        $_USER->enableDisableInbox(true);

        exit(self::buildApiResponseAsJson('appUsage', '', 1, gettext('Done'), 200));
    }

    /**
     * Like or dislike post
     * @param $get
     * @param $this
     */
    public function likeOrDislikePost($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "likeOrDislikePost";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('postid' => @$get['postid'], 'action' => @$get['action']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $postid = $get['postid'];
            $post = Post::GetPost($postid);
            $reactiontype = $get['reactiontype'] ?? 'like';

            $action = $get['action'];
            if ($post) {
                Post::LikeUnlike($postid, $reactiontype);
                $myLikeType = Post::GetUserReactionType($postid);
                $myLikeStatus = (int) !empty($myLikeType);
                exit(self::buildApiResponseAsJson($method, array(
                    'likeStatus' => ($myLikeStatus ? '1' : '2'),
                    'myLikeType' => $myLikeType,
                ), 1, gettext('Updated successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this post has been removed.'), 200));
            }
        }
    }

    /**
     * Mark event attendance
     * @param $get
     * @param $this
     */
    public function eventAttendance($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "eventAttendance";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('eventid' => @$get['eventid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $eventid = $get['eventid'];
            ##Check valid event.
            $event = Event::GetEvent($eventid);
            if ($event) {
                ## Check event join status
                $event->checkInByUserid($_USER->id(), Event::EVENT_CHECKIN_METHOD['MOBILE_APP']);
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Check-in Successful.'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('It seems this event has been removed.'), 200));
            }

        }
    }

    public function getGroupChapters($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getGroupChapters";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $group = Group::GetGroup($groupid);
            if ($group) {
                $allChapters = Group::GetChapterList($group->id());
                $allChaptersArray = array();
                if ($allChapters) {
                    foreach ($allChapters as $chapter) {
                        $chapterCleanArray = $this->chapterObjToArray($chapter);
                        $chapterCleanArray['isChapterMember'] = $_USER->isGroupMember($group->id(), $chapter['chapterid']);
                        $allChaptersArray[] = $chapterCleanArray;
                    }
                }
                exit(self::buildApiResponseAsJson($method, $allChaptersArray, 1, gettext('Chapters list'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this group has been removed.'), 200));
            }
        }
    }

    public function getGroupChannels($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getGroupChannels";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $group = Group::GetGroup($groupid);
            if ($group) {
                $allChannels = Group::GetChannelList($group->id());
                $allChannelsArray = array();
                if ($allChannels) {
                    foreach ($allChannels as $channel) {
                        $channelCleanArray = $this->channelObjToArray($channel);
                        $channelCleanArray['isChannelMember'] = $_USER->isGroupChannelMember($group->id(), $channel['channelid']);
                        $allChannelsArray[] = $channelCleanArray;
                    }
                }
                exit(self::buildApiResponseAsJson($method, $allChannelsArray, 1, gettext('Channels list'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this group has been removed.'), 200));
            }
        }
    }

    /**
     * @deprecated  remove this after Dec 31, 2022
     * @param $get
     * @return void
     */
    public function getEventHighlights($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getEventHighlights";
        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Event Highlights details'), 200));
    }

    /**
     * @deprecated  remove this after Dec 31, 2022
     * @param $get
     * @return void
     */
    public function uploadHighlight($get)
    {
        $method = "uploadHighlight";
        exit(self::buildApiResponseAsJson($method, '', 0, '', 200));
    }

    /**
     * @deprecated  remove this after Dec 31, 2022
     * @param $get
     * @return void
     */
    public function viewHighlight($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "viewHighlight";
        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Event highlight detail'), 200));
    }

    /**
     * @deprecated  remove this after Dec 31, 2022
     * @param $get
     * @return void
     */
    public function likeUnlikeHighlight($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "likeUnlikeHighlight";
        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Disliked successfully'), 200));
    }

    /**
     * @deprecated  remove this after Dec 31, 2022
     * @param $get
     * @return void
     */
    public function addHighlightComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "addHighlightComment";
        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added successfully'), 200));
    }

    public function getLinkForQRCode($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getLinkForQRCode";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('eventid' => @$get['eventid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $eventid = $get['eventid'];
            $link = "" . $_COMPANY->getAppURL($_ZONE->val('app_type')) . "ec2?e=" . $_COMPANY->encodeId($eventid);
            exit(self::buildApiResponseAsJson($method, ['link' => $link], 1, gettext('Link generated successfully'), 200));
        }
    }

    public function deleteAnnouncementComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteAnnouncementComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('commentid' => @$get['commentid'], 'postid' => @$get['postid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $postid = $get['postid'];
            $commentid = $get['commentid'];
            $isAdmin = $_USER->isAdmin();
            $userCondition = " AND `userid`='" . $_USER->id() . "' ";
            if ($isAdmin) {
                $userCondition = "";
            }
            $check = Comment::GetCommentDetail($commentid);
            if ($check && ($_USER->isAdmin() || $check['userid'] == $_USER->id())) {
                if ($check['topictype']== 'CMT'){
                    Comment::DeleteComment_2($check['topicid'], $commentid);
                 } else {
                    Post::DeleteComment_2($postid, $commentid);   
                 }
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment deleted successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Comment not found'), 200));
            }
        }
    }

    /**
     * @deprecated  remove this after Dec 31, 2022
     * @param $get
     * @return void
     */
    public function deleteHighlightComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteHighlightComment";
        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment deleted successfully'), 200));
    }

    public function checkInEventByLink($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "checkInEventByLink";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('checkin_url' => @$get['checkin_url']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $checkin_url = explode("=", $get['checkin_url']);

            if (empty($checkin_url[1])) {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Invalid checkin url.'), 200));
            }

            if (($eventid = $_COMPANY->decodeId($checkin_url[1])) < 1) {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Invalid checkin url.'), 200));
            }
            $event = Event::GetEventByCompany($_COMPANY, $eventid);

            if ($event === null) {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Event not found'), 200));
            } else {
                if ($event->hasCheckinEnded()) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('You are too late. Checkin has ended'), 200));
                } elseif (!$event->hasCheckinStarted()) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('You are too early. Checkin is not open yet'), 200));
                } else {
                    $event->checkInByUserid($_USER->id(),Event::EVENT_CHECKIN_METHOD['MOBILE_APP']);
                    
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('You are checked in successfully'), 200));
                }
            }
        }
    }

    /**
     * Like unlike comment Common
     */

    public function likeUnlikeComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "likeUnlikeComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();

        $check = array('commentid' => @$get['commentid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $commentid = $get['commentid'];
            $reactiontype = $get['reactiontype'] ?? 'like';
            Comment::LikeUnlike($commentid, $reactiontype);
            exit(self::buildApiResponseAsJson($method, '', 1, 'Success', 200));
        }
    }

    public function getSystemMessages($get)
    {
        $method = "getSystemMessages";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('platform' => @$get['platform'], 'app_version' => @$get['app_version'], 'bundle_id' => @$get['bundle_id']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $platform = in_array($get['platform'], array('ios','android','any')) ? $get['platform'] : 'any';
            $app_version = $get['app_version'];
            $bundle_id = $get['bundle_id'];
            $app = $db->getPS("
                SELECT `app_version`, `bundle_id` 
                FROM `app_versions` 
                WHERE `platform`=? 
                  AND `isactive`=1",
                'x',
                $platform
            );

            if (0 && !empty($app)) {
                $approved = array_column($app, 'app_version');
                if (in_array($app_version, $approved)) {
                    $approve_status_response = array('status' => 0, "message" => "App version is approved.");
                } else {
                    $approve_status_response = array('status' => 1, "message" => "Please update your app to a new version");
                }
            } else {
                // If there are no app versions, then the default behavior is approved.
                $approve_status_response = array('status' => 0, "message" => "");
            }

            $general_message = ''; // General dismissible message will be here.

            $system_message = ''; // Non dismissible message will be here.

            $data = ['app_version_status' => $approve_status_response, 'general_message' => $general_message, 'system_message' => $system_message];
            exit(self::buildApiResponseAsJson($method, $data, 1, gettext('System messages'), 200));
        }
    }

    public function editAnnouncementComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "editAnnouncementComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('commentid' => @$get['commentid'], 'postid' => @$get['postid'], 'comment' => @$get['comment']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $postid = $get['postid'];
            $commentid = $get['commentid'];
            $comment = $get['comment'];
            $check = Comment::GetCommentDetail($commentid);

            if ($check && ($_USER->isAdmin() || $check['userid'] == $_USER->id())) {
                Comment::UpdateComment_2($check['topicid'], $commentid,  $comment);
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment updated successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Comment not found'), 200));
            }
        }
    }

    public function manageGroupMembership($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "manageGroupMembership";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            $groupid = $get['groupid'];
            $homeoffice = $_USER->val('homeoffice');
            $group = Group::GetGroup($groupid);

            if ($group) {
                $chapters = null;
                if ($_COMPANY->getAppCustomization()['chapter']['enabled']) {
                    $chapters = Group::GetChapterList($group->id());
                }
                $allChannels = null;
                if ($_COMPANY->getAppCustomization()['channel']['enabled']) {
                    $allChannels = Group::GetChannelList($group->id());
                }
                $allChaptersArray = array();
                if ($chapters) {
                    foreach ($chapters as $chapter) {
                        $chapterCleanArray = $this->chapterObjToArray($chapter);
                        $chapterCleanArray['isChapterMember'] = $_USER->isGroupMember($group->id(), $chapter['chapterid']);
                        $allChaptersArray[] = $chapterCleanArray;
                    }
                }

                $allChannelsArray = array();
                if ($allChannels) {
                    foreach ($allChannels as $channel) {
                        $channelCleanArray = $this->channelObjToArray($channel);
                        $channelCleanArray['isChannelMember'] = $_USER->isGroupChannelMember($group->id(), $channel['channelid']);
                        $allChannelsArray[] = $channelCleanArray;
                    }
                }

                // Join Group automatically as per #1795 - Fixes
                if (!$_USER->isGroupMember($group->id())) {
                    $_USER->joinGroup($group->id(),0,0);
                }

                $isGroupMember = $_USER->isGroupMember($group->id());
                $msg = gettext("Manage your membership");

                if ($group->val('chapter_assign_type') == 'auto' && $homeoffice > 0) {
                    $autoAssign = [];
                    for ($c = 0; $c < count($allChaptersArray); $c++) {
                        $branchids = explode(',', $allChaptersArray[$c]['branchids']);
                        if (in_array($homeoffice, $branchids)) {
                            $_USER->joinGroup($groupid, (int)$allChaptersArray[$c]['chapterid'], 0);
                            $autoAssign[] = $allChaptersArray[$c]['chaptername'];
                            $allChaptersArray[$c]['isChapterMember'] = true;
                        }
                    }
                    if (count($autoAssign) > 0) {
                        $msg = gettext('As per your office location you have been auto assigned ' . implode(', ', $autoAssign) . ' chapter(s).');
                    }
                }
                $groupArray = $this->groupObjToArray($group);
                $groupArray['isGroupMember'] = $isGroupMember;

                $manageTeamRoles  = array();
                if ($group->isTeamsModuleEnabled()) { 
                    $manageTeamRoles = $this->manageTeamJoinRoles($groupid);
                }

                exit(self::buildApiResponseAsJson($method, ['group' => $groupArray, 'chapters' => $allChaptersArray, 'channels' => $allChannelsArray, 'chapter_auto_assign' => strval($group->val('chapter_assign_type') == 'auto'),'manage_team_roles'=>$manageTeamRoles], 1, $msg, 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this group has been removed.'), 200));
            }
        }
    }

    public function joinOrLeaveGroup($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "joinOrLeaveGroup";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'], 'type' => @$get['type']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $type = $get['type'];
            $homeoffice = $_USER->val('homeoffice');
            $group = Group::GetGroup($groupid);
            $anonymous_join = $get['anonymous_join'] == 1 ? 1 : 0;
            if ($group) {
                $chapterid = 0;
                $channelid = 0;

                if (isset($get['chapterid'])) {
                    $chapterid = $get['chapterid'];
                }
                if (isset($get['channelid'])) {
                    $channelid = $get['channelid'];
                }

                if ($type == 1) { // Join

                    if ($_USER->isAllowedToJoinGroup($groupid)) {

                        $join = $_USER->joinGroup($groupid, $chapterid, $channelid,$anonymous_join);

                        if ($join) {
                            exit(self::buildApiResponseAsJson($method, '', 1, gettext('Joined successfully'), 200));
                        } else {
                            exit(self::buildApiResponseAsJson($method, '', 1, gettext('Something went wrong. Please try again.'), 200));
                        }
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext("You can't join this group."), 200));
                    }

                } elseif ($type == 2) { // Leave

                    if($_USER->getWhyCannotLeaveGroup($groupid, $chapterid, $channelid) === 'LEADER_CANNOT_LEAVE_MEMBERSHIP') {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Leader cannot leave membership.'), 200));
                    }
                    
                    if ($group->isTeamsModuleEnabled()) {
                        $reason = Team::GetWhyCannotLeaveProgramMembership($groupid, $_USER->id());
                        if ($reason) {
                            exit(self::buildApiResponseAsJson($method, '', 0, $reason, 200));
                        }
                    }

                    $leave = $_USER->leaveGroup($groupid, $chapterid, $channelid);
                    if ($leave) {
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Left successfully'), 200));
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong. Please try again.'), 200));
                    }

                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Not a valid type'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this group has been removed.'), 200));
            }
        }
    }

    public function changePassword($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "changePassword";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('oldpassword' => @$get['oldpassword'], 'newpassword' => @$get['newpassword']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $oldPassword = $get['oldpassword'];
            $newPassword = $get['newpassword'];
            if ($oldPassword == $newPassword) {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext("New password can't be the same as old password"), 200));
            } elseif (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\-\ \!\@\#\$\%\^\&\_])[a-zA-Z\d\-\ \!\@\#\$\%\^\&\_]{8,}$/', $newPassword)) {
                $data = $db->get("
                    SELECT password 
                    FROM users 
                    WHERE userid={$_USER->id()} and isactive='1'"
                );
                if (count($data) > 0 and password_verify($oldPassword, $data[0]['password'])) {
                    $_USER->updatePassword($newPassword);
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Password changed successfully'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Incorrect old password provided! Please try again'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('New password must be minimum 8 characters long and must contain an uppercase letter, a lowercase letter, a number and a special character.'), 200));
            }
        }
    }

    /**
     * @deprecated  remove this after Dec 31, 2022
     * @param $get
     * @return void
     */
    public function editHighlightComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "editHighlightComment";
        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment updated successfully'), 200));
    }

    public function getSurvey($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getSurvey";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('surveyType' => @$get['surveyType']);
        $checkRequired = $db->checkRequired($check);
        $surveyids = array();
        $hideSurveyCloseButton = false;
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $surveyType = (int)$get['surveyType']; # 1 for Group/Channel/Chapter Join, 2 for Group/Channel/Chapter Leave, 3 for Login,4 Open Link
            $link = '';
            if ($surveyType == 3) { // Login survey
                //$checkSurvey = $db->get("SELECT surveyid FROM surveys_v2 WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND (surveysubtype = 3 AND isactive=1)");
                $checkSurvey = ZoneMemberSurvey::GetActiveLoginSurvey();
                if (!empty($checkSurvey) && $checkSurvey[0]->canSurveyRespond()) {
                    $surveyids[] = $checkSurvey[0]->val('surveyid');
                    if ($checkSurvey[0]->val('is_required')){
                        $hideSurveyCloseButton = true;
                    }
                }
            } else {
                // Instead of getting the survey for surveyType 1 or 2 we will just pop the surveys and process them
                while (($surveySet = $_USER->popSurveyFromSession()) !== null) {
                    if ($surveySet['surveysubtype'] == Survey2::SURVEY_TRIGGER['ON_JOIN']) { //On join
                        $_USER->canViewContent($surveySet['groupid'], true);
                        $surveyData = GroupMemberSurvey::GetActiveGroupMemberSurveyForGroupJoin($surveySet['groupid'], $surveySet['chapterid'], $surveySet['channelid']);
                    } elseif ($surveySet['surveysubtype'] == Survey2::SURVEY_TRIGGER['ON_LEAVE']) { //On Leave
                        $_USER->canViewContent($surveySet['groupid'], true);
                        $surveyData = GroupMemberSurvey::GetActiveGroupMemberSurveyForGroupLeave($surveySet['groupid'], $surveySet['chapterid'], $surveySet['channelid']);
                    } //elseif ($surveySet['surveysubtype'] == Survey2::SURVEY_TRIGGER['ON_LOGIN']) { // Login Survey
                        //$surveyData = GroupMemberSurvey::GetActiveLoginSurvey();
                        //continue;
                    //}

                    if (!empty($surveyData)) {
                        $surveyids[] = $surveyData[0]->val('surveyid');
                        if ($surveyData[0]->val('is_required')){
                            $hideSurveyCloseButton = true;
                        }

                    }
                }
            }

            if (!empty($surveyids)) {
                $link = $_COMPANY->getSurveyURL($_ZONE->val('app_type')) . 'native';
                $link .= '?uid=' . $_COMPANY->encodeId($_USER->id());
                foreach ($surveyids as $surveyid) {
                    $link .= '&sid[]='.$_COMPANY->encodeId($surveyid);
                }

                exit(self::buildApiResponseAsJson($method, ['survey_link' => $link,'hide_survey_close_button'=>$hideSurveyCloseButton], 1, gettext('Survey Link'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('No survey found'), 200));
            }
        }
    }

    public static function Util_GetEventSubtitle(Event $evt)
    {
        $retVal = '';
        if (trim($evt->val('collaborating_groupids'))) {
            $retVal = sprintf(gettext('This is a collaborative event between %s'), htmlspecialchars_decode($evt->getFormatedEventCollaboratedGroupsOrChapters() ?? ''));
        } else {
            $retVal = 'Published in ' . htmlspecialchars_decode(Group::GetGroupName($evt->val('groupid')) ?? '');
            $ch = array();
            $chn = array();
            if ($evt->val('chapterid')) {
                
                $chap_var = Group::GetChaptersCSV($evt->val('chapterid'), $evt->val('groupid'));
                if (!empty($chap_var)) {
                    $ch = array_column( $chap_var,'chaptername');
                }
            }
            if (!empty($ch)) {
                $retVal .= ' [' . Arr::NaturalLanguageJoin($ch) . '] and';
            }
            if ($evt->val('channelid')) {
                $chan_var = Group::GetChannelName($evt->val('channelid'), $evt->val('groupid'));
                if (!empty($chan_var)) {
                    $chn[]= htmlspecialchars_decode($chan_var['channelname'] ?? '');
                }
            }

            if (!empty($chn)) {
                $retVal .= ' [' . Arr::NaturalLanguageJoin($chn) . ']';
            }
        }
        return rtrim($retVal,' and');
    }

    public static function getLocalization($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getLocalization";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('language_code' => @$get['language_code']);
        $checkRequired = $db->checkRequired($check);
        $surveyids = array();
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            setLangContext($get['language_code']);
            $data = array();
            if (file_exists('./locales/source/app_locales.json')){
                $dataString = file_get_contents('./locales/source/app_locales.json');
                $data = json_decode($dataString,true);
                foreach($data as $key => $value){
                    $data[$key] = gettext($value);
                }
                exit(self::buildApiResponseAsJson($method, $data, 1, gettext('Translated strings'), 200));
            }  else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Translation not available'), 200));
            }
        }
    }

    public static function sendGroupJoinRequest($get){
        global $_COMPANY, $_USER, $_ZONE;
        $method = "sendGroupJoinRequest";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $group =Group::GetGroup($groupid);
            if ($group->val('group_type') != Group::GROUP_TYPE_REQUEST_TO_JOIN){
                exit(self::buildApiResponseAsJson($method, '', 0, gettext("This feature is not available"), 200));
            }

            if ($_USER->isGroupMember($groupid)){
                exit(self::buildApiResponseAsJson($method, '', 0, gettext("You are already a member"), 200));
            }
            $checkRequest = Team::GetRequestDetail($groupid,0);
            if ($checkRequest){
                $sendRequest = Team::CancelTeamJoinRequest($groupid,0, $_USER->id());
                exit(self::buildApiResponseAsJson($method, array('status'=>0), 1, gettext("Your request canceled successfully."), 200));
            } else {
                $sendRequest = Team::SaveTeamJoinRequestData($groupid,0,'{}');
                if ($sendRequest) {
                    // Send email notifications based on ERG settings
                    $joinRequestEmailSettings = $group->getJoinRequestMailSettings();
                    $emails=[];
                    if(empty($joinRequestEmailSettings) || $joinRequestEmailSettings['mail_to_leader']) {
                        $leads = $group->getGroupLeads(Group::LEADS_PERMISSION_TYPES['MANAGE']); // Only with manage permissions
                        if (!empty($leads)){
                            $emails = array_column($leads,'email');
                        }
                    }
                    if (!empty($joinRequestEmailSettings) && $joinRequestEmailSettings['mail_to_specific_emails'] && !empty($joinRequestEmailSettings['specific_emails'])) {
                        $specific_emails =  explode(',', $joinRequestEmailSettings['specific_emails']);
                        $emails = array_merge($emails, $specific_emails);

                    }
                    if(!empty($emails)){
                        $emails = implode(',', array_unique($emails));
                            $app_type = $_ZONE->val('app_type');
                            $reply_addr = $group->val('replyto_email');
                            $from = $group->val('from_email_label') .' '. $_COMPANY->getAppCustomization()['group']["name-short"]. ' Join Request';
                            $requestrName = $_USER->getFullName();
                            $subject = "New Request to Join";
                            $appUrl = $_COMPANY->getAppURL($_ZONE->val('app_type')).'manage?id='.$_COMPANY->encodeId($groupid);

                            $msg = <<<EOMEOM
                                <p>{$requestrName} requested to join the {$group->val('groupname')} {$_COMPANY->getAppCustomization()['group']["name-short"]}.</p>
                                <br/>
                                <p>Please login to the application and point to <strong>Manage > Users > {$_COMPANY->getAppCustomization()['group']["name-short"]} Join Requests</strong> to Approve or Deny the request.</p>
                                <br/>
                                <p>Link : <a href="{$appUrl}">{$appUrl}</a></p>
    EOMEOM;
                            $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
                            $emesg	= str_replace('#messagehere#',$msg,$template);
                            $_COMPANY->emailSend2($from, $emails, $subject, $emesg, $app_type,$reply_addr);
                        }
                    exit(self::buildApiResponseAsJson($method, array('status'=>1), 1, sprintf(gettext("%s join request sent successfully."),$_COMPANY->getAppCustomization()['group']["name-short"]), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext("Something went wrong! Please try again."), 200));
                }
            }
        }
    }

    public function getFeedsByHashtag($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        global $post_css;
        $method = "getFeedsByHashtag";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('hashtag_url' => @$get['hashtag_url']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $hashtag_url = explode("=", $get['hashtag_url']);

            $handle = $hashtag_url[1] ?? '';
            if ( empty($handle) || empty($hashtag = HashtagHandle::GetHandle($handle))) {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Hashtag does not exist.'), 200));
            }
            
            $data = Group::GetFeedsByHashtag($hashtag['hashtagid'],'UTC');
            $feeds = array();
            for($i=0;$i<count($data);$i++){
                $feed = array();
                $groupData = Group::GetGroup($data[$i]['groupid']);
                $channel = Group::GetChannelName($data[$i]['channelid'], $data[$i]['groupid']);
                if ($data[$i]['type'] == 1){ // Event
                    $evnt = Event::ConvertDBRecToEvent($data[$i]);
                    $eventSeriesName = '';
                    if ($data[$i]['event_series_id']) {
                        if ($data[$i]['eventid'] == $data[$i]['event_series_id']) {
                            continue;
                        } else {
                            $event = Event::GetEvent($data[$i]['event_series_id']);
                            $eventSeriesName = htmlspecialchars_decode($event->val('eventtitle') ?? '');
                        }
                    }

                    $feed = array(
                        'groupid' => $evnt->val('groupid'),
                        'eventid' => $evnt->val('eventid'),
                        'eventtitle'=> htmlspecialchars_decode($evnt->val('eventtitle') ?? ''), 
                        'event_description' => $evnt->val('event_description'),
                        'eventvanue' => htmlspecialchars_decode($evnt->val('eventvanue') ?? ''),
                        'vanueaddress' =>htmlspecialchars_decode($evnt->val('vanueaddress') ?? ''),
                        'joinersCount' =>  $evnt->val('rsvp_display') > 0 ? $evnt->getJoinersCount() : 0, // Should be int
                        'eventJoiners' =>  $evnt->val('rsvp_display') > 1 ? $evnt->getRandomJoiners(6) : array(),
                        'myJoinStaus' => (int)$db->myEventJoinStatus($_USER->id(), $data[$i]['eventid']),
                        'month' => date('F', strtotime($data[$i]['start'])),
                        'publishdate' => $data[$i]['addedon'],
                        'start' => (int)strtotime($data[$i]['start']),
                        'end' => (int)strtotime($data[$i]['end']),
                        'chapters' => Group::GetChaptersCSV($evnt->val('chapterid'), $evnt->val('groupid')),
                        'channelName' => $data[$i]['channelid'] ? $channel['channelname'] : '',
                        'channelColor' => $data[$i]['channelid'] ? $channel['colour'] : '',
                        'eventSubtitle' => self::Util_GetEventSubtitle ($evnt),
                        'eventSeriesName'=>$eventSeriesName,
                        'groupname' => $groupData->val('groupname_short'), // We do not want to show the full name
                        'overlaycolor' => $groupData->val('overlaycolor'),
                        'coverphoto' => $groupData->val('app_coverphoto') ? : $groupData->val('coverphoto'),
                        'groupicon' => $groupData->val('groupicon'),
                        'type'=>1
                    );
                   

                } elseif($data[$i]['type'] == 2){ // Announcement
                    $feed = array(
                            'groupid' => $data[$i]['groupid'],
                            'postid' => $data[$i]['postid'],
                            'likeStatus' => Post::GetUserLikeStatus($data[$i]['postid']) ? 1 : 2, // Should be int
                            'likeCount' => (int)Post::GetLikeTotals($data[$i]['postid']), // Should be int
                            'userid' => $_COMPANY->encodeId($data[$i]['userid']),
                            'title' => htmlspecialchars_decode($data[$i]['title'] ?? ''),
                            'chapters' => Group::GetChaptersCSV($data[$i]['chapterid'], $data[$i]['groupid']),
                            'post' => $data[$i]['post'],
                            'channelName' => $data[$i]['channelid'] ? htmlspecialchars_decode($channel['channelname'] ?? '') : '',
                            'channelColor' => $data[$i]['channelid'] ? $channel['colour'] : '',
                            'totalComments' => (int) Post::GetCommentsTotal($data[$i]['postid']),// Should be int
                            'groupname' => $groupData->val('groupname_short'), // We do not want to show the full name
                            'overlaycolor' => $groupData->val('overlaycolor'),
                            'coverphoto' => $groupData->val('app_coverphoto') ? : $groupData->val('coverphoto'),
                            'groupicon' => $groupData->val('groupicon'),
                            'type'=>2
                    );
                }
                $feeds[] = $feed;
            }
            $finalData['hashtag'] = $handle;
            $finalData['feeds'] = $feeds;
            exit(self::buildApiResponseAsJson($method, $finalData, 1, sprintf(gettext("Feeds related to %s tag."),$handle), 200));
        }
    }
    /**
     * Discussion obj to array
     */

    private function DiscussionObjToArray($discussion)
    {
        global $_COMPANY, $_ZONE;
        return array(
            'discussionid' => $discussion->val('discussionid'),
            'groupid' => $discussion->val('groupid'),
            'chapterid' => $discussion->val('chapterid'),
            'channelid' => $discussion->val('channelid'),
            'title' => htmlspecialchars_decode($discussion->val('title') ?? ''),
            'discussion' => $discussion->val('discussion'),
            'publishdate' => $discussion->val('createdon'),
            'isactive' => $discussion->val('isactive'),
            'pin_to_top' => $discussion->val('pin_to_top'),
            'createdby' => $discussion->val('createdby')
        );
    }
    /**
     * Veiw Discussion
     */
    public function viewDiscussion($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "viewDiscussion";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();

        $check = array('discussionid' => @$get['discussionid'], 'page' => @$get['page']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }

            if (!$_COMPANY->getAppCustomization()['discussions']['enabled']) { 
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Access denied. The discussion feature is disabled. Please contact your administrator.'), 400));
            }

            $discussionid = $get['discussionid'];
            $page = $get['page'];
            $start = ($page - 1) * 100;
            $end = 100;

            $discussion = Discussion::GetDiscussion($discussionid);
            $finalData = array();

            if ($discussion) {
                if (!$_USER->canViewContent($discussion->val('groupid'))) {
                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('This is a private %1$s. Only members can view the content.'), $_COMPANY->getAppCustomization()['group']['name-short']), 200));
                }
                $finalData = $this->DiscussionObjToArray($discussion);
                $channel = Group::GetChannelName($finalData['channelid'], $finalData['groupid']);
                $creator = User::GetUser($finalData['createdby']);
                $finalData['totalComments'] = (int)Discussion::GetCommentsTotal($finalData['discussionid']); // Should be int

                $finalData['myLikeType'] = Discussion::GetUserReactionType($finalData['discussionid']);
                $finalData['likeStatus'] = !empty($finalData['myLikeType']) ? 1 : 2; // Should be int
                $finalData['likeCount'] = (int)Discussion::GetLikeTotals($finalData['discussionid']);
                $finalData['likeTotalsByType'] = Discussion::GetLikeTotalsByType($finalData['discussionid']);

                $finalData['chapters'] = Group::GetChaptersCSV($finalData['chapterid'], $finalData['groupid']);
                $finalData['channelName'] = $finalData['channelid'] ? htmlspecialchars_decode($channel['channelname'] ?? '') : '';
                $finalData['channelColor'] = $finalData['channelid'] ? $channel['colour'] : '';
                $finalData['groupJoinStatus'] = $_USER->isGroupMember($finalData['groupid']);
                $finalData['joinRequestStatus'] = Team::GetRequestDetail($finalData['groupid'],0) ? 1 : 0;
                $finalData['creator'] = ($discussion->val('anonymous_post'))
                                ?
                                array(
                                    'userid' => $creator ? $_COMPANY->encodeId($creator->id()) : $_COMPANY->encodeId(0),
                                    'firstname' => 'Anonymous',
                                    'lastname' => 'User',
                                    'picture' => ''
                                ) 
                                :
                                array(
                                    'userid' => $creator ? $_COMPANY->encodeId($creator->id()) : $_COMPANY->encodeId(0),
                                    'firstname' => $creator ? $creator->val('firstname') :  'Deleted',
                                    'lastname' => $creator ? $creator->val('lastname') : 'User',
                                    'picture' => $creator ? $creator->val('picture') : '',
                                );
                $canUpdate = $_USER->id()==$discussion->val('createdby') || $_USER->canUpdateContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'),$discussion->val('isactive'));
                $finalData['canUpdateDiscussion'] = $canUpdate;

                $updateDiscussionLink = '';
                if($canUpdate){
                    $updateDiscussionLink = $_COMPANY->getAdminURL().'/native/createUpdateDiscussion.php?groupid='.$_COMPANY->encodeId($discussion->val('groupid')).'&discussionid='.$_COMPANY->encodeId($discussionid);
                }
                $finalData['updateDiscussionLink'] =  $updateDiscussionLink;
                $finalData['canDeleteDiscussion'] = $canUpdate || $_USER->canPublishContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'));

                $comments = $this->getCleanComments('Discussion',$discussionid,$start,$end,$discussion->val('anonymous_post'));
                $finalData['comments'] = $comments;

                $finalData['likesCommentsFeature']= array ('likes'=>true,'comments'=>true);
                exit(self::buildApiResponseAsJson($method, $finalData, 1, gettext('Discussion detail'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this discussioin has been removed.'), 200));
            }
        }
    }

    public function newCommentOnDiscussion($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "newCommentOnDiscussion";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('discussionid' => @$get['discussionid'],'comment' => @$get['comment']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            $discussionid = $get['discussionid'];
            $comment = $get['comment'];
            $discussion = Discussion::GetDiscussion($discussionid);
            $commentid = 0;
            if (isset($get['commentid'])) {
                $commentid = $get['commentid'];
            }
            $media = array();
            if (!empty($_FILES['media']['name'])){
                $media = $_FILES;
            }
            if ($discussion) {
               
                if ($commentid > 0) {
                    // Sub Comment
                    if (Comment::CreateComment_2($commentid, $comment,  $media)) {
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added'), 200));
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again.'), 200));
                    }
                } else {

                    if (Discussion::CreateComment_2($discussionid, $comment,  $media)) {
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added'), 200));
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again.'), 200));
                    }
                }
               
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this discussion has been removed.'), 200));
            }

        }
    }

    public function updateDiscussionComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "updateDiscussionComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('commentid' => @$get['commentid'], 'comment' => @$get['comment']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $commentid = $get['commentid'];
            $comment = $get['comment'];
            $checkComment = Comment::GetCommentDetail($commentid);
            
            if ($checkComment && ($_USER->isAdmin() || $checkComment['userid'] == $_USER->id())) {
                Comment::UpdateComment_2($checkComment['topicid'], $commentid,  $comment);
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment updated successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Comment not found'), 200));
            }
        }
    }

    public function deleteDiscussionComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteDiscussionComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('commentid' => @$get['commentid'], 'discussionid' => @$get['discussionid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $discussionid = $get['discussionid'];
            $commentid = $get['commentid'];
            $isAdmin = $_USER->isAdmin();
            $check = Comment::GetCommentDetail($commentid);
            if ($check && ($_USER->isAdmin() || $check['userid'] == $_USER->id())) {
                if ($check['topictype']== 'CMT'){
                    Comment::DeleteComment_2($check['topicid'], $commentid);
                 } else {
                    Discussion::DeleteComment_2($discussionid, $commentid);   
                 }
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment deleted successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Comment not found'), 200));
            }
        }
    }
    /**
     * Like dislike discussions
     */
    public function likeOrDislikeDiscussion($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "likeOrDislikeDiscussion";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('discussionid' => @$get['discussionid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $discussionid = $get['discussionid'];
            $discussion = Discussion::GetDiscussion($discussionid);
           
            if ($discussion) {
                $reactiontype = $get['reactiontype'] ?? 'like';
                Discussion::LikeUnlike($discussionid, $reactiontype);
                $myLikeType = Discussion::GetUserReactionType($discussionid);
                $myLikeStatus = (int) !empty($myLikeType);
                exit(self::buildApiResponseAsJson($method, array(
                    'likeStatus' => ($myLikeStatus ? '1' : '2'),
                    'myLikeType' => $myLikeType,
                ), 1, gettext('Updated successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this discussion has been removed.'), 200));
            }
        }
    }
    /**
     * Delete Discussion
     */
    public function deleteDiscussion($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteDiscussion";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('discussionid' => @$get['discussionid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            
            $discussionid    = $get['discussionid'];
            $discussion = Discussion::GetDiscussion($discussionid);
            if ($discussion){

                if ($discussion->val('zoneid') != $_ZONE->id()){
                    $_ZONE = $_COMPANY->getZone($discussion->val('zoneid'));
                    if (!$_ZONE) {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                    }
                }
                
                if (!$_USER->canPublishContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid')) && $_USER->id() != $discussion->val('createdby')) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Permission denied'), 200));
                }
               
                if ($discussion->deleteIt()) {
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Discussion deleted successfully'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong. Please try again.'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this discussion has been removed.'), 200));
            }
        }
    }
    
    public function getAlbums($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getAlbums";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $chapterFilter = "";
            $groupid = $get['groupid'];
            $chapterid = $get['chapterid'] ?? 0;
            $channelid = $get['channelid'] ?? 0;

            $page = (int) ($get['page'] ?? 1);
            $limit = 30;
            $mobileRequest = 1; 
            $rows = Album::GetGroupAlbums($groupid,$chapterid,$channelid,($chapterid == 0 ? 1 : 0),($channelid == 0 ? 1 : 0), $page, $limit,$mobileRequest);
            $data = array();
            foreach($rows as $row){
                $album = Album::ConvertDBRecToAlbum($row);
                $coverPicture = '';
                $type = '';
                if(!is_null($row['media'])){
                    $coverPicture = $row['cover_photo'];
                    $type = $row['type'];
                }
                $canUpload = $album->loggedinUserCanAddMedia();
                $channel = Group::GetChannelName($row['channelid'], $row['groupid']);
                $data[] = array(
                    "albumid"=>$row['albumid'],
                    "title"=>htmlspecialchars_decode($row['title']),
                    "cover_photo"=>$coverPicture,
                    "cover_mediaid"=>$row['cover_mediaid'],
                    "media_type"=>$type,
                    "userid"=> $_COMPANY->encodeId($row['userid'] ?: 0),
                    "total_media"=>$row['total'],
                    "totalLikes"=>$row['totalLikes'],
                    "totalComments"=>$row['totalComments'],
                    "likeStatus" =>Album::GetUserLikeStatus($row['albumid']) ? 1 : 2,
                    'chapterids' => explode(',',$row['chapterid']),
                    'channelid' => $row['channelid'],
                    'who_can_upload_media' => $row['who_can_upload_media'],
                    'can_edit_album' => $_USER->canCreateOrPublishContentInScopeCSV($row['groupid'], $row['chapterid'],  $row['channelid']),
                    'can_delete_album' => ($row['total'] == 0 
                    && $_USER->canCreateOrPublishContentInScopeCSV($row['groupid'], $row['chapterid'],  $row['channelid'])),
                    'can_upload_media'=> $canUpload,
                    'chapters' => Group::GetChaptersCSV($row['chapterid'], $row['groupid']),
                    'channelName' => $row['channelid'] ? htmlspecialchars_decode($channel['channelname'] ?? '') : '',
                    'channelColor' => $row['channelid'] ? $channel['colour'] : ''
                );

            }
            exit(self::buildApiResponseAsJson($method, $data, 1, gettext('Albums list'), 200));
        }
    }

    public static function getMediaCommentsAndLikes(MobileAppApi $classObject, $albumid, $mediaid){  
        global $_COMPANY, $_USER, $_ZONE;
        $data = array();
        $album = Album::GetAlbum($albumid);
        if ($album){
            $media = $album->getMediaDetail($mediaid);
            if ($media) {
                $data['myLikeType'] = Album::GetUserReactionType($mediaid);
                $data ['likeStatus'] = !empty($data['myLikeType']) ? 1 : 2; // Should be int
                $data ['likeCount'] = (int)Album::GetLikeTotals($mediaid);
                $data['likeTotalsByType'] = Album::GetLikeTotalsByType($mediaid);
                $comments = $classObject->getCleanComments('Album',$mediaid);
                $data['totalComments'] = (int) Album::GetCommentsTotal($mediaid); // Should be int
                $data['comments'] = $comments;
            }
        }
        return $data;

    }

    public function getAlbumMediaList($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "openAlbumMediaList";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('albumid' => @$get['albumid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $chapterFilter = "";
            $albumid = $get['albumid'];
            $album = Album::GetAlbum($albumid);
            if ($album){

                if (!$_USER->canViewContent($album->val('groupid'))) {
                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('This is a private %1$s. Only members can view the content.'), $_COMPANY->getAppCustomization()['group']['name-short']), 200));
                }

                $mediaList  = $album->getAlbumMediaList();
                $data = array();
                $index = 1;

                if (isset($get['zoneid'])){
                    $zoneid = $get['zoneid'];
                    $_ZONE = $_COMPANY->getZone($zoneid);
                    if (!$_ZONE) {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                    }
                }

                foreach($mediaList as $media){
                    $row = $media;
                    $row['is_cover_media'] = 0;
                    if ($album->val('cover_mediaid') && $album->val('cover_mediaid')  == $row['album_mediaid']){
                        $row['is_cover_media'] = 1;
                    }

                    $row['userid'] = $_COMPANY->encodeId($row['userid']);
                    $row['media_uuid'] = $row['media'];
                    $row['media'] = Album::GetPreSignedURL($row['media'], $album->id(), $_ZONE->id(),'GetObject');
                    $row['thumbnail'] = Album::GetPreSignedURL($row['media_uuid'], $album->id(), $_ZONE->id(),'GetObject',1);
                    $row['can_delete_media'] = $album->loggedinUserCanDeleteMedia($row['album_mediaid']);
                    $row['can_set_cover_picture']  =  $album->loggedinUserCanManageAlbum();
                    $row['likesCommentsFeature']= array ('likes'=>$_COMPANY->getAppCustomization()['albums']['likes'],'comments'=>$_COMPANY->getAppCustomization()['albums']['comments']);
                    $index++;
                    $data[] = $row;
                }
                if (!empty($data)) {
                    exit(self::buildApiResponseAsJson($method, $data, 1, gettext('Media lists'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '',0, gettext("Media not exist."), 200));
                }
                
            } else {
                exit(self::buildApiResponseAsJson($method, '',0, gettext("Album not exist"), 200));
            }     
        }
    }
    public function getMediaLikesAndComments($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getMediaLikesAndComments";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('albumid' => @$get['albumid'],'album_mediaid' => @$get['album_mediaid'],);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $chapterFilter = "";
            $albumid = $get['albumid'];
            $album = Album::GetAlbum($albumid);
            if ($album){
                $album_mediaid = $get['album_mediaid'];
                $likesAndComments  = self::getMediaCommentsAndLikes($this, $albumid, $album_mediaid);
                exit(self::buildApiResponseAsJson($method, $likesAndComments, 1, gettext('Media like and commens'), 200));
              
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext("Album does not exist"), 200));
            }     
        }
    }

    public function createNewAlbum($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "createNewAlbum";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('title' => @$get['title'],'groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $title = $get['title'];
            $groupid = $get['groupid'];
            $whocanuploadmedia = $get['whocanuploadmedia'] ?? 'leads';
            if (!in_array($whocanuploadmedia,array('leads','leads_and_members'))) {
                $whocanuploadmedia = 'leads';
            }
            $chapterids = $get['chapterid'] ?? 0;
            $channelid = $get['channelid'] ?? 0;

            if (!$_USER->canCreateOrPublishContentInScopeCSV($groupid, $chapterids, $channelid)) {
                
                if ((empty($chapterids) && $_COMPANY->getAppCustomization()['chapter']['enabled']) || ($_COMPANY->getAppCustomization()['channel']['enabled'] &&  empty($channelid))) {
                    $contextScope = $_COMPANY->getAppCustomization()['channel']['name-short'];
                    if (empty($chapterids)){
                        $contextScope = $_COMPANY->getAppCustomization()['chapter']['name-short'];
                    }

                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("Please select a %s scope"),$contextScope), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Access denied'), 200));
                }
            }

            $status = Album::CreateAlbum($title, $groupid, $chapterids , $channelid, $whocanuploadmedia);
            exit(self::buildApiResponseAsJson($method, '', 1, gettext('Done'), 200));
        }
    }

    public function editAlbum($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "editAlbum";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('albumid'=>@$get['albumid'],'title' => @$get['title'],'groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $albumid= $get['albumid'];
            $album = Album::GetAlbum($albumid);
            if ($album){ 
                $title = $get['title'];
                $groupid = $get['groupid'];
                $chapterids = $get['chapterid'] ?? 0;
                $channelid = $get['channelid'] ?? 0;

                if (!$_USER->canUpdateContentInScopeCSV($groupid, $chapterids,$channelid,$album->val('isactive'))) {

                    if ((empty($chapterids) && $_COMPANY->getAppCustomization()['chapter']['enabled']) || ($_COMPANY->getAppCustomization()['channel']['enabled'] &&  empty($channelid))) {

                        $contextScope = $_COMPANY->getAppCustomization()['channel']['name-short'];
                        if (empty($chapterids)){
                            $contextScope = $_COMPANY->getAppCustomization()['chapter']['name-short'];
                        }
                        exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("Please select a %s scope"),$contextScope), 200));
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Access denied'), 200));
                    }
                }
                $whocanuploadmedia = $get['whocanuploadmedia'] ?? 'leads';
                if (!in_array($whocanuploadmedia,array('leads','leads_and_members'))) {
                    $whocanuploadmedia = 'leads';
                }
                $status = Album::UpdateAlbum($title, $albumid, $chapterids, $channelid, $whocanuploadmedia);
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Done'), 200));
            }
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this album has been removed.'), 200));
        }
    }


    public function deleteAlbum($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteAlbum";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('album_id' => @$get['album_id']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $album_id = $get['album_id'];
            $album = Album::GetAlbum($album_id);
            
            if ($album){
                if (!$_USER->canCreateOrPublishContentInScopeCSV($album->val('groupid'), $album->val('chapterid'), $album->val('channelid'))) {
                    exit(self::buildApiResponseAsJson($method, '', 0, HTTP_FORBIDDEN, 200));
                }

                $mediaList  = $album->getAlbumMediaList();
                if (!empty( $mediaList)){
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Deletion failed because album is not empty.'), 200));
                } else {                
                    $status = Album::DeleteAlbum($album_id);
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Done'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this album has been removed.'), 200));
            }
        }
    }

    public function setAlbumMediaCover($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "setAlbumMediaCover";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('album_id' => @$get['album_id'],'media_id' => @$get['media_id']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $album_id = $get['album_id'];
            $album_id = (int)$get['album_id'];
            $album = Album::GetAlbum($album_id);
            if ($album){
                if (!$_USER->canCreateOrPublishContentInScopeCSV($album->val('groupid'), $album->val('chapterid'),  $album->val('channelid'))) {
                    exit(self::buildApiResponseAsJson($method, '', 0, HTTP_FORBIDDEN, 200));
                }
                $media_row = Album::GetMedia($get['media_id']);

                if (empty($media_row)) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('No such media exist'), 400));
                }

                Album::SetMediaCover((int)$media_row["album_mediaid"], (int)$album_id);

                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Done'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this album has been removed.'), 200));
            }
        }
    }

    public function deleteAlbumMedia($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteAlbumMedia";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('album_id' => @$get['album_id'],'media_id' => @$get['media_id']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $album_id = $get['album_id'];
            $album = Album::GetAlbum($album_id);
            if ($album){
                $media_id = $get['media_id'];
                if (!$album->loggedinUserCanDeleteMedia($media_id)){
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Permission denied'), 200));
                }
                Album::DeleteMedia($media_id,$album_id);
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Done'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this album has been removed.'), 200));
            }
        }
    }


    public function getAlbumMediaUploadURL($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getAlbumMediaUploadURL";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('album_id' => @$get['album_id'],'filename' => @$get['filename']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $album_id = (int)$get['album_id'];
            $album = Album::GetAlbum($album_id);
            if ($album){
                if (!$_USER->canCreateOrPublishContentInScopeCSV($album->val('groupid'), $album->val('chapterid'),  $album->val('channelid'))) {
                    exit(self::buildApiResponseAsJson($method, '', 0, HTTP_FORBIDDEN, 200));
                }
                $ext = $db->getExtension($get['filename']);
                if ($ext == 'mov') {
                    $ext = 'mp4'; // On mobile app we are using compression library which converts .mov to .mp4. Since the presigned URL is created before the conversion happens, lets force extension to .mp4.
                }
                $media_uuid = teleskope_uuid() . '.' . $ext;

                $presigned_url = Album::GetPreSignedURL($media_uuid, $album_id,$_ZONE->id(),'PutObject');
                exit(self::buildApiResponseAsJson($method, array('album_id'=>$album_id,'media_uuid'=>$media_uuid,'presigned_url'=>$presigned_url), 1, gettext('Done'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this album has been removed.'), 200));
            }
        }  
    }

    public function updateMediaUploadComplete($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "updateMediaUploadComplete";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('album_id' => @$get['album_id'],'media_uuid' => @$get['media_uuid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $album_id = (int)$get['album_id'];
            $album = Album::GetAlbum($album_id);
            if ($album){
                if (!$_USER->canCreateOrPublishContentInScopeCSV($album->val('groupid'), $album->val('chapterid'),  $album->val('channelid'))) {
                    exit(self::buildApiResponseAsJson($method, '', 0, HTTP_FORBIDDEN, 200));
                }
                $media_uuid = $get['media_uuid'];
                $ext = pathinfo(trim($media_uuid), PATHINFO_EXTENSION);
                $alt_text = '';
                $album->registerMedia($media_uuid, $ext, $alt_text);
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Done'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this album has been removed.'), 200));
            }   
        }  
    }

    public function newAlbumMediaComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "newAlbumMediaComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('album_id' => @$get['album_id'],'album_mediaid' => @$get['album_mediaid'],'comment' => @$get['comment']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            $album_id = $get['album_id'];
            $album_mediaid = $get['album_mediaid'];
            $comment = $get['comment'];
            $album = Album::GetAlbum($album_id);
            $commentid = 0;
            if (isset($get['commentid'])) {
                $commentid = $get['commentid'];
            }
            $media = array();
            if (!empty($_FILES['media']['name'])){
                $media = $_FILES;
            }
            if ($album) {
                $mediaDetail = $album->getMediaDetail($album_mediaid);
                if ($mediaDetail){
                    if ($commentid > 0) {
                        // Sub Comment
                        if (Comment::CreateComment_2($commentid, $comment,  $media)) {
                            exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added'), 200));
                        } else {
                            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again.'), 200));
                        }
                    } else {

                        if (Album::CreateComment_2($album_mediaid, $comment,  $media)) {
                            exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added'), 200));
                        } else {
                            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again.'), 200));
                        }
                    }
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this album media has been removed.'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this album has been removed.'), 200));
            }

        }
    }

    public function updateAlbumMediaComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "updateAlbumMediaComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('commentid' => @$get['commentid'], 'comment' => @$get['comment']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $commentid = $get['commentid'];
            $comment = $get['comment'];
            $checkComment = Comment::GetCommentDetail($commentid);

            if ($checkComment && ($_USER->isAdmin() || $checkComment['userid'] == $_USER->id())) {
                Comment::UpdateComment_2($checkComment['topicid'], $commentid,  $comment);
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment updated successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Comment not found'), 200));
            }
        }
    }

    public function deleteAlbumMediaComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteAlbumMediaComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('commentid' => @$get['commentid'], 'album_mediaid' => @$get['album_mediaid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $album_mediaid = $get['album_mediaid'];
            $commentid = $get['commentid'];
            $check = Comment::GetCommentDetail($commentid);
            if ($check && ($_USER->isAdmin() || $check['userid'] == $_USER->id())) {
                if ($check['topictype']== 'CMT'){
                    Comment::DeleteComment_2($check['topicid'], $commentid);
                 } else {
                    Album::DeleteComment_2($album_mediaid, $commentid);   
                 }
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment deleted successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Comment not found'), 200));
            }
        }
    }

    /**
     * Like Unlike Album Media
     */

    public function likeOrDislikeAlbumMedia($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "likeOrDislikeAlbumMedia";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('album_id' => @$get['album_id'],'album_mediaid' => @$get['album_mediaid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $album_id = $get['album_id'];
            $album_mediaid = $get['album_mediaid'];
            $album = Album::GetAlbum($album_id);
           
            if ($album) {
                $media = $album->getMediaDetail($album_mediaid);
                if ($media){
                    $reactiontype = $get['reactiontype'] ?? 'like';
                    Album::LikeUnlike($album_mediaid, $reactiontype);
                    $myLikeType = Album::GetUserReactionType($album_mediaid);
                    $myLikeStatus = (int) !empty($myLikeType);
                    exit(self::buildApiResponseAsJson($method, array('likeStatus' => ($myLikeStatus ? '1' : '2'), 'myLikeType' => $myLikeType), 1, gettext('Updated successfully'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this album media has been removed.'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this album has been removed.'), 200));
            }
        }
    }

    private function filterChapterAndChannelByPermission(int $groupid, string $permission){
        global $_COMPANY, $_USER, $_ZONE;

        $allowGroup = false;
        if ($permission == 'create' && $_USER->canCreateContentInGroup($groupid)){
            $allowGroup = true;
        } elseif($permission == 'update' && $_USER->canPublishContentInGroup($groupid)){
            $allowGroup = true;
        }

        $canCreateUpdateContentInChannel = $_USER->canCreateContentInGroupSomeChannel($groupid);
		$canCreateUpdateContentInChapter = $_USER->canCreateContentInGroupSomeChapter($groupid);

        $validateFirstSelection = 'chapter';
        if ($canCreateUpdateContentInChannel){
            $validateFirstSelection = 'channel';
        }

        $chapters = array();
        if ($_COMPANY->getAppCustomization()['chapter']['enabled']){
            $allChapters = Group::GetChapterList($groupid);

            foreach($allChapters as $chapter){
                if (!$_USER->canCreateContentInGroupChapter($groupid, $chapter['regionids'], $chapter['chapterid'])){
                    if ($canCreateUpdateContentInChannel) {
                        $chapter['chaptername'] .= ' (R) ';
                    } else {
                       continue;
                    }
                }
                $chapters[] =$this->chapterObjToArray($chapter);
            }
        }
        $channels = array();

        if ($_COMPANY->getAppCustomization()['channel']['enabled']) {
            $allChannels= Group::GetChannelList($groupid);

            foreach($allChannels as $channel){

                $canManageChn = $_USER->canCreateContentInGroupChannel($groupid,$channel['channelid']);
                if(!$canManageChn){
                    if ($canCreateUpdateContentInChapter) {
                        $channel['channelname'] .= ' (R) ';
                    } else {
                        continue;
                    }
                }
                $channels[] =   $this->channelObjToArray($channel);
            }
        }

        return array('allowGroup' => $allowGroup, 'chapters' => $chapters, 'channels'=>$channels,'validateFirstSelection'=>$validateFirstSelection);
    }

    
    public function getNodesForAlbumManagement($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getNodesForAlbumManagement";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();

        $action = $get['action']; //create/update/publish/delete
        $action = in_array($action, array('create','update','publish','delete')) ? $action : '';
        $check = array('groupid' => @$get['groupid'], 'action' => $action);
        $checkRequired = $db->checkRequired($check);
        $chaptersChannelsList = array();
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $group = Group::GetGroup($groupid);
            $isAllowed = false;
            if ($group){
                $action = ($action == 'delete') ? 'create' : $action ;
                $chaptersChannelsList = $this->filterChapterAndChannelByPermission($groupid, $action);
                $isAllowed = $chaptersChannelsList['allowGroup'] || !empty($chaptersChannelsList['chapters']) || !empty($chaptersChannelsList['channels']);
            }
            if ($isAllowed){
                exit(self::buildApiResponseAsJson($method,$chaptersChannelsList, 1, gettext('Allowed'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Not allowed.'), 200));
            }
        }
    }

    public function updateAlbum($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "updateAlbum";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('title' => @$get['title'],'album_id' => @$get['album_id']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $title = $get['title'];
            $album_id = $get['album_id'];
            $album = Album::GetAlbum($album_id);
            
            if ($album){
                if (!$_USER->canUpdateContentInScopeCSV($album->val('groupid'), $album->val('chapterid'), $album->val('channelid'),$album->val('isactive')) || !$_USER->isAdmin()) {
                    exit(self::buildApiResponseAsJson($method, '', 0, HTTP_FORBIDDEN, 200));
                }
                $chapterid = $get['chapterid'] ?? 0;
                $channelid = $get['channelid'] ?? 0;
                $status = Album::UpdateAlbum($title, $album_id, $chapterid, $channelid);
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Done'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this album has been removed.'), 200));
            }
        }
    }

    public function getActiveZones($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getActiveZones";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $allZones = $_COMPANY->getZones();
        $zones = array();

        foreach($allZones as $key => $value){

            if (!$value['customization']['app']['mobileapp']['enabled'] || $value['app_type'] == 'peoplehero'){
                continue;
            }
            
            if ($value['home_zone'] == -1){
                continue;
            } else {
                $zones[] = array('zoneid'=>$value['zoneid'],'zonename'=>$value['zonename'],'homezone'=> ($value['zoneid'] ==  $_USER->getMyConfiguredZone('affinities')), 'currentzone' => ($value['zoneid'] ==  $_ZONE->id()));
            }
        }
        
        exit(self::buildApiResponseAsJson($method, $zones, 1, gettext('List of zones'), 200));
    }
   
    public function switchZone($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "switchZone";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('zoneid' => @$get['zoneid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $zoneid = $get['zoneid'];

            if($_USER->swithMobileAppZone($zoneid,$this->userSessionkey)){
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Zone switched successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong. Please try again.'), 200));
            }
        }
    }


    public function getMyCalendarEvents($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getMyCalendarEvents";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();

        if (!$_COMPANY->getAppCustomization()['event']['enabled']) {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Event feature is disabled'), 200));
        }      
       $joinedGroup =  $_USER->getFollowedGroupsAsCSV();
     
    if ($_COMPANY->getAppCustomization()['event']['enabled'] && $joinedGroup){
        $collaborating_groupids = "";
        $joinedGroupIds = explode(',', $joinedGroup);
        if (!empty($joinedGroupIds)) {
            $collaborating_groupids = ' OR (';
            foreach ($joinedGroupIds as $index => $joinedGroupId) {
                // Ensure to trim and sanitize $joinedGroupId if necessary
                $joinedGroupId = trim($joinedGroupId);
                if ($index > 0) {
                    $collaborating_groupids .= ' OR ';
                }

                $collaborating_groupids .= "FIND_IN_SET($joinedGroupId, events.collaborating_groupids) > 0";
            }
            $collaborating_groupids .= ')'; 
        }
         $data = $db->get("
SELECT events.companyid, events.groupid, events.chapterid, events.channelid, events.`eventid`, events.`eventtitle`, events.`rsvp_display`, events.`start`,events.`end`, events.`eventvanue`, events.`vanueaddress`, events.`hostedby`, events.`addedon`,events.`event_description`,events.`collaborating_groupids`,events.publishdate ,`groups`.overlaycolor, `groups`.overlaycolor2, eventclass
         FROM events 
             LEFT JOIN `groups` USING (groupid) 
         WHERE 
           events.companyid={$_COMPANY->id()} 
           AND (
               events.zoneid={$_ZONE->id()}
               AND (
                 ((events.groupid IN({$joinedGroup}) {$collaborating_groupids}) AND events.eventclass='event') 
                 OR 
                 events.eventclass='holiday' 
                 OR
                 (events.groupid = 0 and collaborating_groupids = '')
               )
           )
           AND `start` > now() - interval 3 month  
           AND (event_series_id !=eventid)
           AND events.`isactive` = '1'
         ");    

              
            if (count($data) > 0) {
                $eventRsvoType = Event::RSVP_TYPE;
                $joinStaus = array(
                    $eventRsvoType['RSVP_DEFAULT']=>'',
                    $eventRsvoType['RSVP_YES']=>'Yes',
                    $eventRsvoType['RSVP_INPERSON_YES']=>'Yes',
                    $eventRsvoType['RSVP_ONLINE_YES']=>'Yes',
                    $eventRsvoType['RSVP_NO']=>'No',
                    $eventRsvoType['RSVP_MAYBE']=>'Maybe',
                    $eventRsvoType['RSVP_INPERSON_WAIT']=>'Wait',
                    $eventRsvoType['RSVP_ONLINE_WAIT']=>'Wait',
                    $eventRsvoType['RSVP_INPERSON_WAIT_CANCEL']=>'WaitCancel',
                    $eventRsvoType['RSVP_ONLINE_WAIT_CANCEL']=>'WaitCancel',
                );
                for ($i = 0; $i < count($data); $i++) {
                    $evnt = Event::ConvertDBRecToEvent($data[$i]);
                    $rsvpOptions   = $evnt->getMyRSVPOptions();
                    $channel = Group::GetChannelName($data[$i]['channelid'], $data[$i]['groupid']);
                    $data[$i]['eventtitle'] = htmlspecialchars_decode($evnt->val('eventtitle') ?? '');
                    $data[$i]['eventvanue'] = htmlspecialchars_decode($evnt->val('eventvanue') ?? '');
                    $data[$i]['vanueaddress'] = htmlspecialchars_decode($evnt->val('vanueaddress') ?? '');
                    $data[$i]['event_description'] = $evnt->val('event_description');
                    $data[$i]['myJoinStaus'] = $joinStaus[$rsvpOptions['my_rsvp_status']];
                    $data[$i]['month'] = date('F', strtotime($data[$i]['start']));
                    $data[$i]['start'] = (int)strtotime($data[$i]['start']);
                    $data[$i]['end'] = (int)strtotime($data[$i]['end']);
                    $data[$i]['addedon'] = (int)strtotime($data[$i]['addedon']);
                    $data[$i]['eventSubtitle'] = self::Util_GetEventSubtitle ($evnt);
                    $data[$i]['chapters'] = Group::GetChaptersCSV($evnt->val('chapterid'), $evnt->val('groupid'));
                    $data[$i]['channelName'] = $data[$i]['channelid'] ? $channel['channelname'] : '';
                    $data[$i]['channelColor'] = $data[$i]['channelid'] ? $channel['colour'] : '';
                    $data[$i]['eventOverlayColor'] = $data[$i]['overlaycolor'] ?? $data[$i]['overlaycolor2'];
                }
                exit(self::buildApiResponseAsJson($method, $data, 1, "Calendar events", 200));

            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('No events'), 200));
            }
        } else {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('No events'), 200));
        }
    }

    public function parseSharedUrl($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "parseSharedUrl";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('url' => @$get['url']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $fullUrl = $get['url'];
            $company = Company::GetCompanyByUrl($fullUrl);
            
            if (!$company || ($company && $company->id() != $_COMPANY->id())){
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Invalid url').' ERR:2', 200));
            }
            $topicTypes = Group::TOPIC_TYPES;
            $url = parse_url($fullUrl);
            $urlArray = explode('/',$url['path']);
            $section = end($urlArray);
            $queryString = $url['query'];
            parse_str($queryString, $queryparams);
            $success = false;
            $link = '';
            $groupid = '';
            $sectionName = '';
            if (in_array($section,array('goto','detail','home','viewpost','eventview','newsletter','viewdiscussion','album','resource','resource_folder','my_inbox'))){
                if ($section == 'viewpost'){
                    if (array_key_exists('id',$queryparams)){
                        $id = $_COMPANY->decodeId($queryparams['id']);
                        $post = Post::GetPost($id);
                        if ($post){
                            $success = true;
                            $section = "viewpost";
                            $callbackMethod = "viewPost";
                            $zoneid = $post->val('zoneid');
                        }
                    }
                } elseif ($section == 'eventview'){
                    if (array_key_exists('id',$queryparams)){
                        $id = $_COMPANY->decodeId($queryparams['id']);
                        $event = Event::GetEvent($id);
                        if($event){
                            $success = true;
                            $section = "eventview";
                            $callbackMethod = "viewEvent";
                            $zoneid = $event->val('zoneid');
                        }
                    }
                } elseif ($section == 'newsletter'){
                    if (array_key_exists('id',$queryparams)){
                        $id = $_COMPANY->decodeId($queryparams['id']);
                        $newsletter = Newsletter::GetNewsletter($id);
                        if ($newsletter){
                            $success = true;
                            $section = "newsletter";
                            $callbackMethod = "viewNewsletter";
                            $zoneid = $newsletter->val('zoneid');
                        } 
                    }

                } elseif ($section == 'viewdiscussion'){
                    if (array_key_exists('id',$queryparams)){
                        $id = $_COMPANY->decodeId($queryparams['id']);
                        $discussion = Discussion::GetDiscussion($id);
                        if ($discussion){
                            $success = true;
                            $section = "viewdiscussion";
                            $callbackMethod = "viewDiscussion";
                            $zoneid = $discussion->val('zoneid');
                        } 
                    }

                } elseif ($section == 'album'){
                    if (array_key_exists('id',$queryparams)){
                        $id = $_COMPANY->decodeId($queryparams['id']);
                        $album = Album::GetAlbum($id);
                        if ($album){
                            $success = true;
                            $section = "album";
                            $callbackMethod = "getAlbumMediaList";
                            $zoneid = $album->val('zoneid');
                            $groupid = $album->val('groupid');
                            $sectionName = $album->val('title');
                        }
                    }
                } elseif ($section == 'resource'){
                    if (array_key_exists('id',$queryparams)){
                        $id = $_COMPANY->decodeId($queryparams['id']);
                        $resource = Resource::GetResource($id,true);
                        if ($resource){
                            $success = true;
                            $section = "resource";
                            $callbackMethod = "downloadResource";
                            $zoneid = $resource->val('zoneid');
                            $groupid = $resource->val('groupid');
                            $sectionName = $resource->val('resource');
                        }
                    }
                } elseif ($section == 'resource_folder'){
                    if (array_key_exists('id',$queryparams)){
                        $id = $_COMPANY->decodeId($queryparams['id']);
                        $resource = Resource::GetResource($id,true);
                        if ($resource){
                            $success = true;
                            $section = "resource_folder";
                            $callbackMethod = "getSubResources";
                            $zoneid = $resource->val('zoneid');
                            $groupid = $resource->val('groupid');
                            $sectionName = $resource->val('resource_name');
                        } 
                    }
                } elseif ($section == 'my_inbox'){ 
                    $success = true;
                    $section = "my_inbox";
                    $id = 0;
                    $callbackMethod = "getMyInboxMessages";
                    $groupid = "0";
                    $zoneid = "0";
                } elseif (array_key_exists('group',$queryparams)){
                    $permatag = $queryparams['group'];
                    $check_groupid = Group::GetGroupIdByPermatag($permatag);
                    $group = ($check_groupid) ? Group::GetGroup($check_groupid) : null;
                    if ($group){
                        $success = true;
                        $id = $check_groupid;
                        $section = 'groupDetail';
                        $zoneid =  $group->val('zoneid');
                        $callbackMethod = "viewSelectedGroup";
                    }
                } else {

                    if (array_key_exists('hash',$queryparams)){
                        $gid = $_COMPANY->decodeId($queryparams['id']);
                        $hash = explode('-',$queryparams['hash']);
                        $id = $_COMPANY->decodeId($hash[1]);
                        if ($id > 0) {
                            $group = Group::GetGroup($gid);
                            if ($group){
                                $groupid = $group->id();
                                $success = true;
                                $section = 'myteams';
                                $zoneid =  $group->val('zoneid');
                                $callbackMethod = "getMyTeamDetail";   
                            }
                        }
                    } elseif (array_key_exists('id',$queryparams)){
                        $id = $_COMPANY->decodeId($queryparams['id']);
                        $group = Group::GetGroup($id);
                        if ($group){
                            $success = true;
                            $section = 'groupDetail';
                            $zoneid =  $group->val('zoneid');
                            $callbackMethod = "viewSelectedGroup";   
                        }
                    }
                }
            } else {
                if (in_array('survey',$urlArray)){
                    if (array_key_exists('surveyid',$queryparams)){
                        $id = $_COMPANY->decodeId($queryparams['surveyid']);
                        $survey = Survey2::GetSurvey($id);
                        if ($survey){
                            $success = true;
                            $section = 'survey';
                            $callbackMethod = "";
                            $zoneid = $survey->val('zoneid');
                            $appType = $_ZONE->val('app_type');
                            
                            if ($zoneid != $_ZONE->id()){
                                $zone = $_COMPANY->getZone($zoneid);
                                $appType = $zone->val('app_type');
                            }
                            $link = $_COMPANY->getSurveyURL($appType) . 'native?uid=' . $_COMPANY->encodeId($_USER->id()).'&sid[]='.$_COMPANY->encodeId($id);
                        }
                    }
                }
            }
            if ($success){
                $data = array("callbackMethod" => $callbackMethod,'url' => $link, 'section' => $section, 'id' => $id,'zoneid' => $zoneid, 'groupid' => $groupid,'sectionName' => $sectionName);
                exit(self::buildApiResponseAsJson($method, $data, 1, gettext('Callback data'), 200));
            } 
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Invalid urls').' ERR:3', 200));
        }
    }
    
    public function deleteMyAccount($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteMyAccount";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        if (@$get['confirm'] == 'Delete'){
            $delete =  $_USER->wipeClean();
            if ($delete){
                exit(self::buildApiResponseAsJson($method, '', 1, gettext("Your account has been marked for deletion and will be permanently deleted, including all information, after thirty days."), 200));
            } else{
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong. Please try again.'), 200));
            }
        } else {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext("Please type 'Delete' in text box to provide your consent to delete your account"), 200));
        }
    }

    public function getMyInboxMessages($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getMyInboxMessages";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        if (!$_USER->isUserInboxEnabled()){
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Inbox Feature is not active for you'), 400));
        }
        $db = new Hems();
        $check = array('page' => @$get['page'], 'folder' => @$get['folder']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $page = $get['page'];
            $folder = $get['folder'] == 'inbox' ? 'INBOX' : 'TRASH';
            $start = ($page - 1) * 30;
            $end = 30;

            $messages = UserInbox::GetMyMessages($folder, $start, $end);
            if (!empty($messages)) {
                exit(self::buildApiResponseAsJson($method, $messages, 1, gettext('Messages list.'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('No messages found'), 200));
            }
        }
    }

    public function viewMessageDetail($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "viewMessageDetail";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('messageid' => @$get['messageid'], 'zoneid' => @$get['zoneid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $messageid = $get['messageid'];
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            // check if the message exists by getting the message detail
            $messageData = UserInbox::GetMessage($messageid);
            if(!($messageData)){
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Unable to load message as it might be deleted.'), 200));
            }
            // Check if message is read
            if(!$messageData['readon']){
                UserInbox::ReadInboxMessage($messageid);
                $messageData = UserInbox::GetMessage($messageid);
            }
            exit(self::buildApiResponseAsJson($method, $messageData, 1, gettext('Message detail.'), 200));
        }
    }

    public function deleteMyInboxMessage($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteMyInboxMessage";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('messageid' => @$get['messageid'], 'purge' => @$get['purge']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $messageid = $get['messageid'];
            $deletePermanently = $get['purge'] == 'true';
            // Delete Message
            $deleteMessage = UserInbox::DeleteInboxMessage($messageid, $deletePermanently);
            if ($deleteMessage) {
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Deleted Successfully.'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Unable to delete. Please try again later.'), 200));
            }
        }
    }

    public function signupEventVolunteerRole($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "signupEventVolunteerRole";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('eventid' => @$get['eventid'],'volunteertypeid'=>@$get['volunteertypeid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $eventid = $get['eventid'];
            // $volunteerid = $get['volunteerid'];
            $volunteertypeid = $get['volunteertypeid'];

            $event = Event::GetEvent($eventid);
            // add check to see if user is already volunteer
            $checkCurrentStatus = $event->isEventVolunteerSignup($_USER->id(), $volunteertypeid);
            if ($checkCurrentStatus){
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('You already signed up as a volunteer'), 200));
            }
            // add user as volunteer
            $signup = $event->addOrUpdateEventVolunteer($_USER->id(), $volunteertypeid);
            if ($signup==-1) {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Event Volunteer request capacity has been met'), 200));
            } elseif($signup==1 || $signup==2){
                $rsvpDetails = $event->getMyRSVPOptions();
                if ($rsvpDetails['my_rsvp_status'] ==0 && $signup == 1) {
                    if ($rsvpDetails['max_inperson'] >0 || $rsvpDetails['max_online'] >0){
                        if ($rsvpDetails['available_inperson'] >0 ) {
                            $event->joinEvent($_USER->id(), Event::RSVP_TYPE['RSVP_INPERSON_YES'], 2,1);
                        } elseif ($rsvpDetails['available_online'] >0 ) {
                            $event->joinEvent($_USER->id(), Event::RSVP_TYPE['RSVP_ONLINE_YES'], 2,1);
                        } else {
                            if ($rsvpDetails['max_inperson'] >0) {
                                $event->joinEvent($_USER->id(), Event::RSVP_TYPE['RSVP_INPERSON_WAIT'], 2,1);
                            } else {
                                $event->joinEvent($_USER->id(), Event::RSVP_TYPE['RSVP_ONLINE_WAIT'], 2,1);
                            }                    
                        }
                    } else {
                        $event->joinEvent($_USER->id(), Event::RSVP_TYPE['RSVP_YES'], 2,1);
                    }
                }  
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Volunteer role signup successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong. Please try again'), 200));
            }
        }
    }   

    public function declineEventVolunteerRole($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "declineEventVolunteerRole";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('eventid' => @$get['eventid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $eventid = $get['eventid'];
            $event = Event::GetEvent($eventid);
            $event->removeEventVolunteer($_USER->id());
            exit(self::buildApiResponseAsJson($method, '', 1, gettext('Volunteer role declined successfully'), 200));
        }
    }
    
    // Resource API
    public function getResourcesList($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getResourcesList";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('page' => @$get['page'], 'groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $parent_id = 0;
            $chapterid = $get['chapterid'] ?? 0;
            $channelid = $get['channelid'] ?? 0;
            $page = $get['page'];
            $start = ($page - 1) * 30;
            $end = 30;
            $resourceList = Resource::GetResourcesForGroupMobileApi($groupid, $parent_id, $chapterid,$channelid, $start, $end);
            if (!empty($resourceList)) {
            // Set the data
            $resourceData = array(); 
            for ($i = 0; $i < count($resourceList); $i++) {
                $resourceData[$i]['resource_id'] = $resourceList[$i]['resource_id'];
                $resourceData[$i]['pin_to_top'] = $resourceList[$i]['pin_to_top'];
                $resourceData[$i]['resource_name'] = $resourceList[$i]['resource_name'];
                $resourceData[$i]['resource'] = $resourceList[$i]['resource'];
                $resourceData[$i]['resource_description'] = $resourceList[$i]['resource_description'];
                $resourceData[$i]['extention'] = $resourceList[$i]['resource_type'] !=3 ? $resourceList[$i]['extention'] : 'folder';
                if ($resourceList[$i]['resource_type']==2) {
                    $resourceData[$i]['size'] = convertBytesToReadableSize($resourceList[$i]['size']);
                }else{
                    $resourceData[$i]['size'] = '';
                }
                $resourceData[$i]['channelname'] ='';
                $resourceData[$i]['channelcolor'] = '';
                if($resourceList[$i]['channelid']){
                    $resourceData[$i]['channelname'] = $resourceList[$i]['channelname'];
                    $resourceData[$i]['channelcolor'] = $resourceList[$i]['channelColor']; 
                    
                }
                $resourceData[$i]['chaptername'] ='';
                $resourceData[$i]['chaptercolor'] = '';
                if($resourceList[$i]['chapterid']){
                    $resourceData[$i]['chaptername'] = $resourceList[$i]['chaptername'];
                    $resourceData[$i]['chapterColor'] = $resourceList[$i]['chapterColor'];
                }
                $resourceData[$i]['modifiedon'] = $resourceList[$i]['modifiedon'];          
            }
                exit(self::buildApiResponseAsJson($method, $resourceData, 1, gettext('Resource list.'), 200));
                
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('No resource found'), 200));
            }
        }
    }

    public function getSubResources($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getSubResources";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('page' => @$get['page'],'groupid' => @$get['groupid'], 'resourceid' => @$get['resourceid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $resourceid = $get['resourceid'];
            $chapterid = isset($get['chapterid']) ? $get['chapterid'] : 0;
            $channelid = isset($get['channelid']) ? $get['channelid'] : 0;
            $page = $get['page'];
            $start = ($page - 1) * 30;
            $end = 30;

            if (!$_USER->canViewContent($groupid)) {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('This is a private %1$s. Only members can view the content.'), $_COMPANY->getAppCustomization()['group']['name-short']), 200));
            }

            $resourceList = Resource::GetResourcesForGroupMobileApi($groupid, $resourceid, $chapterid, $channelid, $start, $end);
            if (!empty($resourceList)) {
                 // Set the data 
                 $resourceData = array();
                 for ($i = 0; $i < count($resourceList); $i++) {
                    $resourceData[$i]['resource_id'] = $resourceList[$i]['resource_id'];
                    $resourceData[$i]['resource_name'] = $resourceList[$i]['resource_name'];
                    $resourceData[$i]['resource'] = $resourceList[$i]['resource'];
                    $resourceData[$i]['resource_description'] = $resourceList[$i]['resource_description'];
                    $resourceData[$i]['extention'] =  $resourceList[$i]['resource_type'] !=3 ? $resourceList[$i]['extention'] : 'folder';
                    if ($resourceList[$i]['resource_type']==2) {
                        $resourceData[$i]['size'] = convertBytesToReadableSize($resourceList[$i]['size']);
                    }else{
                        $resourceData[$i]['size'] = '';
                    }
                    $resourceData[$i]['channelname'] ='';
                    $resourceData[$i]['channelcolor'] = '';
                    if($resourceList[$i]['channelid']){
                        $resourceData[$i]['channelname'] = $resourceList[$i]['channelname'];
                        $resourceData[$i]['channelcolor'] = $resourceList[$i]['channelColor']; 
                        
                    }
                    $resourceData[$i]['chaptername'] ='';
                    $resourceData[$i]['chaptercolor'] = '';
                    if($resourceList[$i]['chapterid']){
                        $resourceData[$i]['chaptername'] = $resourceList[$i]['chaptername'];
                        $resourceData[$i]['chapterColor'] = $resourceList[$i]['chapterColor'];
                    }
                    $resourceData[$i]['modifiedon'] = $resourceList[$i]['modifiedon'];      
                }
                exit(self::buildApiResponseAsJson($method, $resourceData, 1, gettext('Resource list.'), 200));
                
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('No resource found'), 200));
            }
        }
    }

    public function downloadResource($get){
        global $_COMPANY, $_USER, $_ZONE;
        $method = "downloadResource";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('resourceid' => @$get['resourceid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $resourceId = $get['resourceid'];
            // resource object
            $resource = Resource::GetResource($resourceId,true);
            if ($resource){

                if (!$_USER->canViewContent($resource->val('groupid'))) {
                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('This is a private %1$s. Only members can view the content.'), $_COMPANY->getAppCustomization()['group']['name-short']), 200));
                }

                try {
                    $downloadurl = $resource->download();
                } catch (\Aws\S3\Exception\S3Exception $e) {
                    if ($e->getAwsErrorCode() === 'NoSuchKey') {
                        exit(self::buildApiResponseAsJson(
                            $method,
                            '',
                            0,
                            gettext('The requested resource is no longer available'),
                            404
                        ));
                    }

                    throw $e;
                }

                if($downloadurl){
                    header('Content-Description: File Transfer');
                    header('Content-Type: ' . $downloadurl['ContentType']);
                    header('Content-Disposition: attachment; filename=' . $downloadurl['DownloadFilename']);
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    echo $downloadurl['Body'];
                    exit();
                    
                }
            }
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Resource you are looking for does not exist.'), 200));

        }
    }

    public function getRecognitionTypes($get){

        global $_COMPANY, $_USER, $_ZONE;
        $method = "getRecognitionTypes";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }

        $types = Recognition::RECOGNITION_TYPES;
        $encTypes = array();
        foreach($types as $key => $val){
            $encTypes[$key] = array('type'=>ucfirst(str_replace('_',' ',$key)),'id'=>$val);
            
        }
        exit(self::buildApiResponseAsJson($method, $encTypes, 1, gettext('Recognition types list.'), 200));
    }


    public function getRecognitions($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getRecognitions";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('page' => @$get['page'],'groupid' => @$get['groupid'], 'recognition_type' => @$get['recognition_type']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $recognition_type = $get['recognition_type'];
            $page = $get['page'];
            $start = ($page - 1) * 30;
            $end = 30;
            $group = Group::GetGroup($groupid);
            $recognitions = Recognition::GetRecognitions($groupid,$recognition_type, 1, 0, "", "recognition_date", "DESC", $start, $end);
            $baseUrl = $_COMPANY->getAdminURL().'/native/createUpdateRecognition.php?groupid='.$_COMPANY->encodeId($groupid);
            if ($group  && !empty($recognitions)) {
                // Check if likes and comments are enabled
                $isLikesEnabled = $_COMPANY->getAppCustomization()['recognition']['likes'] ? true : false;
                $isCommentsEnabled = $_COMPANY->getAppCustomization()['recognition']['comments'] ? true : false;
                $finalRecognitions = array();
                for($i = 0; $i<count($recognitions); $i++){
                    $recognitions[$i]['can_manage']= ($_USER->id() == $recognitions[$i]['recognizedby']) || $_USER->canManageGroupSomething($recognitions[$i]['groupid']);

                    $recognizedUser = User::GetUser($recognitions[$i]['recognizedto']) ?? User::GetEmptyUser();
                    $recognizedBy = User::GetUser($recognitions[$i]['recognizedby']) ?? User::GetEmptyUser();

                    $recognizedUserName = ($recognizedUser ? $recognizedUser->val('firstname') : 'Deleted').' '.($recognizedUser ? $recognizedUser->val('lastname') : 'User');
                    $recognizedByUserName = ($recognizedBy ? $recognizedBy->val('firstname') : 'Deleted').' '.($recognizedBy ? $recognizedBy->val('lastname') : 'User');
                    $recognitions[$i]['recognized_to_name'] = $recognizedUserName;
                    $recognitions[$i]['recognized_by_name'] = $recognizedByUserName;

                    // Checking if user can edit
                    $hasEditPermissions = ($_USER->isGrouplead($recognitions[$i]['groupid']) && $_USER->canPublishContentInGroup($recognitions[$i]['groupid'])) || ($_USER->id() == $recognitions[$i]['createdby']);
                    // Recgnition type for recognitions to make the edit link work
                    $edit_recognition_type = 0;
                    if ($recognitions[$i]['createdby']=$_USER->id() && $recognitions[$i]['recognizedto'] != $_USER->id()) {
                        $edit_recognition_type = Recognition::RECOGNITION_TYPES['recognize_a_colleague'];                        
                    } elseif($recognitions[$i]['createdby']=$_USER->id() && $recognitions[$i]['recognizedto'] == $_USER->id()){
                        $edit_recognition_type = Recognition::RECOGNITION_TYPES['recognize_my_self']; 
                    } elseif($recognitions[$i]['recognizedto'] = $_USER->id()){
                        $edit_recognition_type = Recognition::RECOGNITION_TYPES['received_recognitions'];
                    }
                    $recognitions[$i]['recognizedby'] = $_COMPANY->encodeId($recognitions[$i]['recognizedby']);
                    $recognitions[$i]['recognizedto'] = $_COMPANY->encodeId($recognitions[$i]['recognizedto']);
                    $recognitionId = $_COMPANY->encodeId($recognitions[$i]['recognitionid']);
                    $recognitions[$i]['edit'] = $hasEditPermissions ? $baseUrl.'&recognitionid='.$recognitionId.'&recognition_type='.$_COMPANY->encodeId($edit_recognition_type) : '';

                    // Likes and comments add if enabled
                    $recognitions[$i]['total_likes'] = $isLikesEnabled ? Recognition::GetLikeTotals($recognitions[$i]['recognitionid']) : 0;
                    $recognitions[$i]['comments'] = $isCommentsEnabled ? Recognition::GetCommentsTotal($recognitions[$i]['recognitionid']) : 0;
                    
                    $recognitions[$i]['likesCommentsFeature']= array ('likes'=>$isLikesEnabled,'comments'=>$isCommentsEnabled);
                
                    $recognition_obj = Recognition::Hydrate($recognitions[$i]['recognitionid'], $recognitions[$i]);
                    $recognition_custom_fields = [];
                    foreach (($recognition_obj->getCustomFieldsAsArray()) as $field => $value) {
                        if (empty($value)) {
                            continue;
                        }
                        $recognition_custom_fields[] = [
                            'custom_field_name' => $field,
                            'custom_type_values' => $value,
                        ];
                    }
                    $recognitions[$i]['custom_fields'] = $recognition_custom_fields;

                    unset($recognitions[$i]['companyid'],$recognitions[$i]['zoneid'],$recognitions[$i]['createdon'],$recognitions[$i]['modifiedon'],$recognitions[$i]['isactive'],$recognitions[$i]['attributes']);
                }
                
            }


            $baseUrl = $baseUrl.'&recognitionid='.$_COMPANY->encodeId(0);
            $isRecognizeColleagueEnabled = $group->getRecognitionConfiguration()['enable_colleague_recognition'];
            $recognizeColleagueLink = $isRecognizeColleagueEnabled ? $baseUrl.'&recognition_type='.$_COMPANY->encodeId(Recognition::RECOGNITION_TYPES['recognize_a_colleague']) : '';
            $isRecognizeMyselfEnabled = $group->getRecognitionConfiguration()['enable_self_recognition'];
            $recognizeMySelfLink = $isRecognizeMyselfEnabled ? $baseUrl.'&recognition_type='.$_COMPANY->encodeId(Recognition::RECOGNITION_TYPES['recognize_my_self']) : "";

            $data = array('recognitions'=>$recognitions, 'links'=> array('recognizeColleagueLink'=>$recognizeColleagueLink,'recognizeMySelfLink'=>$recognizeMySelfLink ));
            exit(self::buildApiResponseAsJson($method, $data, 1, gettext('Filtered recognitions list'), 200));
        }
    }

    public function getTopicLikers($get){
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getTopicLikers";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('topicid' => @$get['topicid'], 'topic_type' => @$get['topic_type'], 'page' => @$get['page']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $page = (int) ($get['page'] ?? 1);
            $topicId = $get['topicid'];
            $topic_type = $get['topic_type'];
            $topicName = TELESKOPE::TOPIC_TYPES_ENGLISH[$topic_type];
            if(!$topicName || empty($topicName)){
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Incorrect Topic Type.'), 200));
            }
            $TopicClass = $topicName ? Teleskope::TOPIC_TYPE_CLASS_MAP[$topic_type] : '';
            if(!$TopicClass || empty($TopicClass)){
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Incorrect Topic Type.'), 200));
            }
            $likersData = $TopicClass::GetLatestLikers($topicId, true, 30, $page) ?? [];
            if ($topic_type == TELESKOPE::TOPIC_TYPES['DISCUSSION']){
                $discussion = Discussion::GetDiscussion($topicId);
                if($discussion->val('anonymous_post')){
                    for($i=0; $i<count($likersData); $i++){
                        $likersData[$i]['userid'] = 0;
                        $likersData[$i]['firstname'] = "Anonymous";
                        $likersData[$i]['lastname'] = 'User';
                        $likersData[$i]['picture'] = '';
                        $likersData[$i]['email'] = '';
                        $likersData[$i]['jobtitle'] = '';
                    }
                }
            }
            $data['likersData'] = $likersData;
            exit(self::buildApiResponseAsJson($method, $data, 1, gettext('Likers Details'), 200));
        }
    }
       /**
     * This API is used to get Recognition detail for display, 
     * the app will use recognitionid provided in the API to fetch the Recognition.
     * @param $get
     * @param $this
     */
    public function viewRecognition($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "viewRecognition";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();

        $check = array('groupid' => @$get['groupid'], 'recognitionid' => @$get['recognitionid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }

            if (!$_COMPANY->getAppCustomization()['recognition']['enabled']) { 
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Access denied. The Recognition feature is disabled. Please contact your administrator.'), 400));
            }

            $recognitionid = intval($get['recognitionid']);
            $recognition = Recognition::GetRecognition($recognitionid);
            $data = array();
            if ($recognition) {
                $recognizedUser = User::GetUser($recognition->val('recognizedto')) ?? User::GetEmptyUser();
                $recognizedBy = User::GetUser($recognition->val('recognizedby')) ?? User::GetEmptyUser();
                $recognizedUserName = ($recognizedUser ? $recognizedUser->val('firstname') : 'Deleted').' '.($recognizedUser ? $recognizedUser->val('lastname') : 'User');
                $recognizedByUserName = ($recognizedBy ? $recognizedBy->val('firstname') : 'Deleted').' '.($recognizedBy ? $recognizedBy->val('lastname') : 'User');
                $isCommentsEnabled = $_COMPANY->getAppCustomization()['recognition']['comments'] ? true : false;
                $isLikesEnabled = $_COMPANY->getAppCustomization()['recognition']['likes'] ? true : false;
                
                $data['recognitionid'] = $recognition->id();
                $data['recognized_user'] = $recognizedUserName;
                $data['recognized_by'] = $recognizedByUserName;
                $data['recognizedby_name'] = $recognition->val('recognizedby_name');
                $data['description'] =  $recognition->val('description');
                $data['recognitiondate'] = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($recognition->val('recognitiondate'),true,false,false);
                $comments = $isCommentsEnabled ? $this->getCleanComments('Recognition',$recognition->id()) : [];
                $data['comments'] = $comments;

                // Likes and comments add if enabled
                $data['total_comments'] = $isCommentsEnabled ? Recognition::GetCommentsTotal($recognition->id()) : 0;

                $data['myLikeType'] = Recognition::GetUserReactionType($recognition->id());
                $data['isLiked'] = !empty($data['myLikeType']) ? 1 : 2; // Should be int
                $data['total_likes'] = $isLikesEnabled ? Recognition::GetLikeTotals($recognition->id()) : 0;
                $data['likeTotalsByType'] = Recognition::GetLikeTotalsByType($recognition->id());

                //Recognition Profile Picture
                $data['recognized_user_pic'] = $recognizedUser->val('picture');
                $data['likesCommentsFeature']= array ('likes'=>$isLikesEnabled,'comments'=>$isCommentsEnabled);

                $data['custom_fields'] = [];
                foreach (($recognition->getCustomFieldsAsArray()) as $field => $value) {
                    if (empty($value)) {
                        continue;
                    }
                    $data['custom_fields'][] = [
                        'custom_field_name' => $field,
                        'custom_type_values' => $value,
                    ];
                }
                exit(self::buildApiResponseAsJson($method, $data, 1, gettext('Recognition detail'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this recognition has been removed.'), 200));
            }
        }
    }

    
    /**
     * Like or dislike Recognition
     * @param $get
     * @param $this
     */
    public function likeOrDislikeRecognition($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "likeOrDislikeRecognition";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('recognitionid' => @$get['recognitionid'], 'action' => @$get['action']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $recognitionid = $get['recognitionid'];
            $recognition = Recognition::GetRecognition($recognitionid);
            if ($recognition) {
                $reactiontype = $get['reactiontype'] ?? 'like';
                Recognition::LikeUnlike($recognitionid, $reactiontype);
                $myLikeType = Recognition::GetUserReactionType($recognitionid);
                $myLikeStatus = (int) !empty($myLikeType);
                exit(self::buildApiResponseAsJson($method, array('likeStatus' => ($myLikeStatus ? '1' : '2'), 'myLikeType' => $myLikeType), 1, gettext('Updated successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this recognition has been removed.'), 200));
            }
        }
    }

    public function newCommentOnRecognition($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "newCommentOnRecognition";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('recognitionid' => @$get['recognitionid'],'comment' => @$get['comment']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            $recognitionid = $get['recognitionid'];
            $comment = $get['comment'];
            $recognition = Recognition::GetRecognition($recognitionid);
            $commentid = 0;
            if (isset($get['commentid'])) {
                $commentid = $get['commentid'];
            }
            $media = array();
            if (!empty($_FILES['media']['name'])){
                $media = $_FILES;
            }
            if ($recognition) {
               
                if ($commentid > 0) {
                    // Sub Comment
                    if (Comment::CreateComment_2($commentid, $comment,  $media)) {
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added'), 200));
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again.'), 200));
                    }
                } else {

                    if (Recognition::CreateComment_2($recognitionid, $comment,  $media)) {
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added'), 200));
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again.'), 200));
                    }
                }
               
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this recognition has been removed.'), 200));
            }

        }
    }

    public function updateRecognitionComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "updateRecognitionComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('commentid' => @$get['commentid'], 'comment' => @$get['comment']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $commentid = $get['commentid'];
            $comment = $get['comment'];
            $checkComment = Comment::GetCommentDetail($commentid);
            
            if ($checkComment && ($_USER->isAdmin() || $checkComment['userid'] == $_USER->id())) {
                Comment::UpdateComment_2($checkComment['topicid'], $commentid,  $comment);
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment updated successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Comment not found'), 200));
            }
        }
    }

    public function deleteRecognitionComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteRecognitionComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('commentid' => @$get['commentid'], 'recognitionid' => @$get['recognitionid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $recognitionid = $get['recognitionid'];
            $commentid = $get['commentid'];
            $isAdmin = $_USER->isAdmin();
            $check = Comment::GetCommentDetail($commentid);
            if ($check && ($_USER->isAdmin() || $check['userid'] == $_USER->id())) {
                if ($check['topictype']== 'CMT'){
                    Comment::DeleteComment_2($check['topicid'], $commentid);
                 } else {
                    Recognition::DeleteComment_2($recognitionid, $commentid);   
                 }
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment deleted successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Comment not found'), 200));
            }
        }
    }


    public function deleteRecognition($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteRecognition";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('recognitionid' => @$get['recognitionid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $recognitionid = $get['recognitionid'];
            
            if (
                ($recognitionid = $get['recognitionid'])<0 || 
                ($recognition = recognition::Getrecognition($recognitionid)) === NULL
            ) {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Recognition found'), 200));
            }
        
            if (
                $_USER->id() != $recognition->val('recognizedby') && !$_USER->canManageGroupSomething($recognition->val('groupid'))
            ) { //Allow creators to delete unpublished content
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Permission denied'), 200));
            }
           
            $recognition->inactivateIt();
            exit(self::buildApiResponseAsJson($method, '', 1, gettext('Recognition deleted successfully'), 200));
        }
    }

    public function getWebViewAuthToken($get){

        global $_COMPANY, $_USER, $_ZONE;
        $method = "getWebViewAuthToken";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $zoneid = 0;
        if (isset($get['zoneid'])){
            $zoneid = $get['zoneid'];
            $z = $_COMPANY->getZone($zoneid);
            if ($z) {
                $zoneid = $z->id();
            }
        }
        if (!$zoneid) {
            $zoneid = $_ZONE->id();
        }
        $token = encrypt_decrypt($_COMPANY->id() . ':' .$zoneid. ':'. $_USER->id(), 1);
        exit(self::buildApiResponseAsJson($method, array('web_auth_token'=>$token), 1, gettext('Web view auth token generated'), 200));
    }


    public function deleteProfilePicture($get){
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteProfilePicture";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }

        $_USER->updateProfilePicture('');
        if ($_USER->has('picture')){
            $_COMPANY->deleteFile($_USER->val('picture'));
            $_USER->clearSessionCache();
        }
        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Profile picture deleted successfully'), 200));
    }
    
    /**
     * cleanTaskItemRow Helper method to  get the cleaned data of Action item
     * 
     * @param  Class $group
     * @param  Class $team
     * @param  array $data
     * @param  string $info ['basic', 'full']
     * @return array
     */
    private function cleanTaskItemRow(Group $group, Team $team, $data, $info = 'full')
    {
        global $_COMPANY, $_ZONE;

        $status = Team::GET_TALENTPEAK_TODO_STATUS;
        $update_action_item_link = '';
        if ($team->val('isactive') == Team::STATUS_ACTIVE && $data['isactive'] !=52){
            $update_action_item_link = $_COMPANY->getAdminURL().'/native/createUpdateActionItem.php?teamid=' .$_COMPANY->encodeId($data['teamid']) . '&taskid='.$_COMPANY->encodeId($data['taskid']);
        }

        $addtoTouchPointLink = "";
        if( !in_array(TEAM::PROGRAM_TEAM_TAB['TOUCH_POINT'], $group->getHiddenProgramTabSetting()) && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ 
            $addtoTouchPointLink = $_COMPANY->getAdminURL().'/native/createUpdateTouchPoint.php?teamid=' .$_COMPANY->encodeId($team->val('teamid')) . '&touchpointid='.$_COMPANY->encodeId(0).'&parent_taskid='.$_COMPANY->encodeId($data['taskid']);
        }

        [$showComments, $canAddComment, $canLike] = $team->getCommentsLikesSetting();

        if ($info == 'full'){
            $createdBy = User::GetUser($data['createdby']);
            return array(
                'taskid' => $data['taskid'],
                'type' => $data['task_type'],
                'teamid' => $data['teamid'],
                'tasktitle' => $data['tasktitle'],
                'assignedto' => $_COMPANY->encodeId($data['assignedto']),
                'duedate' => strtotime($data['duedate']) > 0 ?  $data['duedate'] : '',
                'description' => $data['description'],
                'createdby' => $_COMPANY->encodeId($data['createdby']),
                'modifiedon' => $data['modifiedon'],
                'createdon' => $data['createdon'],
                'parent_taskid' => $data['parent_taskid'],
                'firstname' => $data['assignedto'] ? $data['firstname'] : '',
                'lastname' => $data['assignedto'] ? $data['lastname'] : '',
                'picture' => $data['assignedto'] ? $data['picture'] : '',
                'creator_firstname' => $createdBy ? $createdBy->val('firstname') : 'Deleted',
                'creator_lastname' => $createdBy ? $createdBy->val('lastname') : 'User',
                'creator_picture' => $createdBy ? $createdBy->val('picture') : '',
                'status' => $status[$data['isactive']],
                'update_status_options' => $status,
                'update_action_item_link'=>$update_action_item_link,
                'show_comments' => $showComments,
                'can_add_comment' => $canAddComment,
                'can_like' => $canLike,
                'add_to_touch_point_link' => $addtoTouchPointLink
            );
        } else {
            return array(
                'taskid' => $data['taskid'],
                'teamid' => $data['teamid'],
                'tasktitle' => $data['tasktitle'],
                'duedate' => strtotime($data['duedate']) > 0 ? $data['duedate'] : '',
                'status' => $status[$data['isactive']],
                'update_action_item_link'=>$update_action_item_link,
                'show_comments' => $showComments,
                'can_add_comment' => $canAddComment,
                'can_like' => $canLike,
                'add_to_touch_point_link' => $addtoTouchPointLink
            );
        }
    }
    
    /**
     * cleanTouchPointRow Helper method to get the clean data of Touch point
     *
     * @param  Class $team
     * @param  array $data
     * @param  string $info ['full', 'basic']
     * @return array
     */
    private function cleanTouchPointRow(Team $team, $data,$info='full')
    {
        global $_COMPANY, $_ZONE,$_USER;
        $status = Team::GET_TALENTPEAK_TODO_STATUS;

        $touch_point_update_link = (($team->val('isactive') == Team::STATUS_ACTIVE) && $data['isactive'] !=52) ? $_COMPANY->getAdminURL().'/native/createUpdateTouchPoint.php?teamid=' .$_COMPANY->encodeId($data['teamid']) . '&touchpointid='.$_COMPANY->encodeId($data['taskid']) : '';

        [$showComments, $canAddComment, $canLike] = $team->getCommentsLikesSetting();
        $canDeleteEvent = false;
        if ($info == 'full'){
            $createdBy = User::GetUser($data['createdby']);
            $touchpoint_event = null;
            $create_touchpoint_event_link = '';
            $update_touch_point_event = '';

            $group = Group::GetGroup($team->val('groupid'));
            $touchPointTypeConfig = $group->getTouchpointTypeConfiguration();
           
            if ($data['eventid']){
                $event = Event::GetEvent($data['eventid']);
                if(!$team->isComplete() && !$team->isIncomplete() && !$team->isInactive() && !$team->isPaused()){
                    $canDeleteEvent = $event->loggedinUserCanManageEvent();
                }
                $touchpoint_event = $this->eventObjToArray($event);
                $update_touch_point_event =  (($team->val('isactive') == Team::STATUS_ACTIVE)) ? $_COMPANY->getAdminURL().'/native/createUpdateTouchPointEvent.php?teamid=' .$_COMPANY->encodeId($data['teamid']) . '&eventid='.$_COMPANY->encodeId($data['eventid']).'&timezone='.$event->val('timezone').'&touchpointid='.$_COMPANY->encodeId($data['taskid']) : '';
                $touchpoint_event['update_touch_point_event'] = $update_touch_point_event;
                $touchpoint_event['joinersCount'] =  $event->val('rsvp_display') > 0 ? $event->getJoinersCount() : 0; // Should be int
                $touchpoint_event['start'] = (int) strtotime($touchpoint_event['start']);
                $touchpoint_event['end'] = (int) strtotime($touchpoint_event['end']);
                $touchpoint_event['team_name'] = $team->val('team_name');

            } else {
                $timezone = "UTC";
                if ($_USER->val('timezone')){
                    $timezone = $_USER->val('timezone');
                }
                if ($touchPointTypeConfig['type']!='touchpointonly'){
                    $create_touchpoint_event_link = ($team->val('isactive') == Team::STATUS_ACTIVE &&  $data['isactive'] !=52) ? $_COMPANY->getAdminURL().'/native/createUpdateTouchPointEvent.php?&teamid=' .$_COMPANY->encodeId($team->val('teamid')) . '&eventid='.$_COMPANY->encodeId(0).'&timezone='.$timezone.'&touchpointid='.$_COMPANY->encodeId($data['taskid']) : '';
                }
            }
            
            return array(
                'touchpointid' => $data['taskid'],
                'type' => $data['task_type'],
                'teamid' => $data['teamid'],
                'touchTointTitle' => $data['tasktitle'],
                'assignedto' => $_COMPANY->encodeId($data['assignedto']),
                'duedate' => strtotime($data['duedate']) > 0 ? $data['duedate'] : '',
                'description' => $data['description'],
                'createdby' => $_COMPANY->encodeId($data['createdby']),
                'modifiedon' => $data['modifiedon'],
                'createdon' => $data['createdon'],
                'parent_taskid' => $data['parent_taskid'],
                'eventid' => $data['eventid'],
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'picture' => $data['picture'],
                'creator_firstname' => $createdBy ? $createdBy->val('firstname') : 'Deleted',
                'creator_lastname' => $createdBy ? $createdBy->val('lastname') : 'User',
                'creator_picture' => $createdBy ? $createdBy->val('picture') : '',
                'status' => $status[$data['isactive']],
                'update_status_options' => $status,
                'eventDetail' => $touchpoint_event,
                'touch_point_update_link' => $touch_point_update_link,
                'create_touchpoint_event_link' =>$create_touchpoint_event_link,
                'update_touchpoint_event_link' => $update_touch_point_event,
                'show_comments' => $showComments,
                'can_add_comment' => $canAddComment,
                'can_like' => $canLike,
                'can_delete_event' => $canDeleteEvent
            );
        } else {
            return array(
                'touchpointid' => $data['taskid'],
                'teamid' => $data['teamid'],
                'touchTointTitle' => $data['tasktitle'],
                'duedate' => strtotime($data['duedate']) > 0 ? $data['duedate'] : '',
                'status' => $status[$data['isactive']],
                'touch_point_update_link' => $touch_point_update_link,
                'show_comments' => $showComments,
                'can_add_comment' => $canAddComment,
                'can_like' => $canLike
            );
        }
    }
    
    /**
     * cleanFeedbackRow Helper method to get the clean feedback data
     *
     * @param  Class $group
     * @param  Class $team
     * @param  array $data
     * @return array
     */
    private function cleanFeedbackRow(Group $group, Team $team, $data)
    {
        global $_COMPANY, $_ZONE, $_USER;

        $createdBy = User::GetUser($data['createdby']);
        $data['assignedto_level'] = '';
        if (!$data['assignedto']){
            $data['assignedto_level'] = sprintf(gettext('%s Leaders'),$_COMPANY->getAppCustomization()['group']['name-short']);
        }
        $update_feedback_link = (($team->val('isactive') == Team::STATUS_ACTIVE) && $data['createdby'] == $_USER->id()) ? $_COMPANY->getAdminURL().'/native/createUpdateTeamFeedback.php?teamid=' .$_COMPANY->encodeId($data['teamid']) . '&feedbackid='.$_COMPANY->encodeId($data['taskid']) : '';

        [$showComments, $canAddComment, $canLike] = $team->getCommentsLikesSetting();

        $addtoTodoLink = "";
        if (!in_array(TEAM::PROGRAM_TEAM_TAB['ACTION_ITEM'], $group->getHiddenProgramTabSetting()) && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){    
            $addtoTodoLink = $_COMPANY->getAdminURL().'/native/createUpdateActionItem.php?teamid=' .$_COMPANY->encodeId($team->val('teamid')) . '&taskid='.$_COMPANY->encodeId(0).'&parent_taskid='.$_COMPANY->encodeId($data['taskid']);
        }

        return array(
            'feedbackid' => $data['taskid'],
            'type' => $data['task_type'],
            'teamid' => $data['teamid'],
            'feedbackTitle' => $data['tasktitle'],
            'assignedto' => $_COMPANY->encodeId($data['assignedto']),
            'description' => $data['description'],
            'createdby' => $_COMPANY->encodeId($data['createdby']),
            'modifiedon' => $data['modifiedon'],
            'createdon' => $data['createdon'],
            'parent_taskid' => $data['parent_taskid'],
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'picture' => $data['picture'],
            'creator_firstname' => $createdBy ? $createdBy->val('firstname') : 'Deleted',
            'creator_lastname' => $createdBy ? $createdBy->val('lastname') : 'User',
            'creator_picture' => $createdBy ? $createdBy->val('picture') : '',
            'alternative_assignedto_level' => $data['assignedto_level'],
            'update_feedback_link' => $update_feedback_link,
            'show_comments' => $showComments,
            'can_add_comment' => $canAddComment,
            'can_like' => $canLike,
            'add_to_todo_link' => $addtoTodoLink
        );
    }
    
    /**
     * getCleanComments Helper function to get clean data of comments
     *
     * @param  string $context [Post, Discussion, Album, Team, etc ]
     * @param  int $contextid
     * @param  int $start
     * @param  int $end
     * @param  int $anonymous_post
     * @return array
     */
    private function getCleanComments(string $context, int $contextid, int $start=-1, int $end=-1,int $anonymous_post = 0)
    {
        global $_COMPANY, $_ZONE;
        if ($start !=-1){
            $allComments = $context::GetComments_2($contextid,$start,$end);
        } else {
            $allComments = $context::GetComments_2($contextid);
        }
    
        $comments = array();
        for ($i = 0; $i < count($allComments); $i++) {
            $subcomments = array();
            $comments[$i]['commentid'] = $allComments[$i]['commentid'];
            $comments[$i]['encCommentid'] = $_COMPANY->encodeId($allComments[$i]['commentid']);
            $comments[$i]['topicid'] = $allComments[$i]['topicid'];
            $comments[$i]['userid'] = $_COMPANY->encodeId($allComments[$i]['userid']);
            $comments[$i]['comment'] = $allComments[$i]['comment'];
            $comments[$i]['attachment'] = $allComments[$i]['attachment'];
            $comments[$i]['subcomment_count'] = $allComments[$i]['subcomment_count'];
            $comments[$i]['commentedon'] = (int)strtotime($allComments[$i]['createdon']);
            $comments[$i]['createdon'] = $allComments[$i]['createdon'];
            $comments[$i]['stage'] = 1;
            $comments[$i]['firstname'] = $anonymous_post ? 'Anonymous' :htmlspecialchars_decode($allComments[$i]['firstname'] ?? '') ;
            $comments[$i]['lastname'] = $anonymous_post ? 'User' : htmlspecialchars_decode($allComments[$i]['lastname'] ?? '');
            $comments[$i]['picture'] = $anonymous_post ? '' : $allComments[$i]['picture'];
            $comments[$i]['jobtitle'] =$anonymous_post ? '' :  htmlspecialchars_decode($allComments[$i]['jobtitle']??'');
            $comments[$i]['isLiked'] = Comment::GetUserLikeStatus($allComments[$i]['commentid']);
            $comments[$i]['totalLikes'] = Comment::GetLikeTotals($allComments[$i]['commentid']);
            $allSubComments = Comment::GetComments_2($allComments[$i]['commentid']);
            for($s=0;$s<count($allSubComments); $s++){
                $subcomments[$s]['commentid'] = $allSubComments[$s]['commentid'];
                $subcomments[$s]['encCommentid'] = $_COMPANY->encodeId($allSubComments[$s]['commentid']);
                $subcomments[$s]['topicid'] = $allSubComments[$s]['topicid'];
                $subcomments[$s]['userid'] = $_COMPANY->encodeId($allSubComments[$s]['userid']);
                $subcomments[$s]['comment'] = $allSubComments[$s]['comment'];
                $subcomments[$s]['attachment'] = $allSubComments[$s]['attachment'];
                $subcomments[$s]['subcomment_count'] = $allSubComments[$s]['subcomment_count'];
                $subcomments[$s]['commentedon'] = (int)strtotime($allSubComments[$s]['createdon']);
                $subcomments[$s]['stage'] = 2;
                $subcomments[$s]['firstname'] = $anonymous_post ? 'Anonymous' : htmlspecialchars_decode($allSubComments[$s]['firstname'] ?? '');
                $subcomments[$s]['lastname'] = $anonymous_post ? 'User' : htmlspecialchars_decode($allSubComments[$s]['lastname'] ?? '');
                $subcomments[$s]['picture'] = $anonymous_post ? '' : $allSubComments[$s]['picture'];
                $subcomments[$s]['jobtitle'] = $anonymous_post ? '' : htmlspecialchars_decode($allSubComments[$s]['jobtitle'] ?? '');
                $subcomments[$s]['isLiked'] = 0;
                $subcomments[$s]['totalLikes'] = 0;  
            }
            $comments[$i]['subcomments'] = $subcomments;
        }
        return $comments;
    }
    
    /**
     * getMyTeams API for getting the list of Joined Teams
     *
     * @param  array $get array of GET request parameters [ method:getMyTeams; groupid:153; zoneid:27; chapterid: {0 || -1 || chapterid value} ] 
     * @return Json response
     */
    public function getMyTeams($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getMyTeams";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $groupid = $get['groupid'];
            $group = Group::GetGroup($groupid);
            if ($group){ 

                if (!$group->getTeamProgramType()){ // Validation
                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('This %1$s is not yet configured to use the %2$s feature. Please contact your administrator.'), $_COMPANY->getAppCustomization()['group']['name-short'], $_COMPANY->getAppCustomization()['teams']['name-short']), 200));
                }
            
                if (empty(Team::GetProgramTeamRoles($groupid,1))) { // Validation
                    exit(self::buildApiResponseAsJson($method, '', 0,sprintf(gettext('No role types have yet been configured in this %1$s. Please contact your administrator.'), $_COMPANY->getAppCustomization()['group']['name-short']) , 200));
                }

                if (Team::CanCreateNetworkingTeam($groupid, $group->getTeamProgramType())){ // Networking 
                    [$status, $suggestionsRows]  = Team::GetTeamMembersSuggestionsForRequestRoles($group,$_USER->id());

                    if($status == 1 && $group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['NETWORKING']) { // Create Team automatically
                        $suggestions = $suggestionsRows[0]['suggestions'];
                        if (!empty($suggestions)) {
                            $bestSuggestedUserid = Team::GetBestSuggestedUserForNetworking($groupid, $_USER->id(), $suggestions);
                            if ($bestSuggestedUserid){
                                Team::CreateNetworkingTeam($groupid,$suggestions[0]['roleid'],$_USER->id(),$bestSuggestedUserid);
                            }
                        }
                    } 
                }

                if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ // Individual Development
                    $startIndividualDevelopment = $get['startIndividualDevelopment'] ?? false;
                    $myTeams = Team::GetMyTeams($groupid);
                    if (empty($myTeams)){

                        if ($startIndividualDevelopment){
                            $individualDevelopmentRole = Team::GetProgramTeamRoles($groupid,1,4);
                            
                            if (!empty($individualDevelopmentRole)){
                                $roleid = $individualDevelopmentRole[0]['roleid'];
                                $teamid = Team::CreateOrUpdateTeam($groupid,0, $_USER->val('firstname').'\'s Individual Development');
                                if ($teamid){
                                    $team = Team::GetTeam($teamid);
                                    $team->addUpdateTeamMember($roleid, $_USER->id());
                                    $team->activate();
                                }
                            } else {
                                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('No Individual Development %s role type found. You should contact your administrator to get help creating the new role.'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
                            }
                        } else {
                            exit(self::buildApiResponseAsJson($method, array('heading'=>sprintf(gettext("You have not started the %s program yet. Please start now."),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1)),'button_text'=>gettext('Start Now'), 'confirmation_text'=>sprintf(gettext('Are you sure you want to start %s program?'),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1))), 2, gettext('Individual Development not started yet'), 200));
                        }
                    }
                }

                $chapterid = intval($get['chapterid'] ?? 0);
                $globalChapterOnly = $chapterid == 0;

                $teams = Team::GetMyTeams($groupid,$chapterid, $globalChapterOnly);
                if (
                    ($_ZONE->val('app_type') !== 'talentpeak')
                    && $group->isTeamsModuleEnabled()
                    && !$_USER->isGroupMember($groupid)
                    && empty($teams)
                ) {
                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%1$s page is available only to the members of %2$s %3$s, please join  %2$s %3$s as a member first. '), $_COMPANY->getAppCustomization()['teams']['name-short'], $group->val('groupname_short'), $_COMPANY->getAppCustomization()['group']['name-short']), 200));
                }

                $data = array();
                $status = array(
                    '0'=>gettext('In-active'),
                    '1'=>gettext('Active'),
                    '2'=>gettext('Draft'),
                    '100'=>gettext('Delete'),
                    '110'=>gettext('Complete'),
                    '109'=>gettext('Incomplete'),
                    '108'=>gettext('Paused')
                );
                $allRoles = Team::GetProgramTeamRoles($groupid, 1);

                foreach($teams as $team){
                    if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']) {
                        $circleRolesCapacity = Team::GetCircleRolesCapacity($groupid,$team['teamid']);
                    }
                    $row = array();
                    $row['teamid'] = $team['teamid'];
                    $row['groupid'] = $team['groupid'];
                    $row['chapterid'] = $team['chapterid'];
                    $row['team_name'] = $team['team_name'];
                    $row['team_meta_name'] = $team['team_meta_name']??'Team';
                    $row['status'] = $status[$team['isactive']];
                    $row['show_detail'] = ($team['isactive'] == 1 || $team['isactive'] == 110 || $team['isactive'] == 109|| $team['isactive'] == 108) ? true : false;

                    $row['hashtags'] =  $team['handleids'] ? HashtagHandle::GetAllHashTagHandles($team['handleids']) : array();
                    global $post_css;
                    $row['team_description'] = $team['team_description'] ? $post_css . '<div class="post-inner">' . $team['team_description'] . '</div>' : '';
                    $roleAndMembers = array();
                   
                    foreach($allRoles as $role){ 
                        $tm = Team::GetTeam($team['teamid']);
                        $members = $tm->getTeamMembers((int)$role['roleid']);
                        $m = array();


                        $totalmembers = count($members);
                        $showAvailableCapacity = '';
                        if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] && $role['sys_team_role_type'] != 2) {
                            $allowedRoleCapacity = $circleRolesCapacity[$role['roleid']]['circle_role_max_capacity'];
                            $availableRoleCapacity = ($allowedRoleCapacity - $totalmembers);
                            $showAvailableCapacity = array('title'=>gettext('Available Slots'), 'availableRoleCapacity'=>$availableRoleCapacity);
                        }    

                        foreach($members as $member){
                            $m[] = array(
                                'team_memberid'=>$member['team_memberid'],
                                'role'=>$member['role'],
                                'roletitle' => $member['roletitle'],
                                'firstname'=>$member['firstname'],
                                'lastname'=>$member['lastname'],
                                'email'=>$member['email'],
                                'picture'=>$member['picture'],
                                'jobtitle'=>$member['jobtitle']
                            );

                        }
                        $roleAndMembers[] = array(
                            'roleid' => $role['roleid'],
                            'role_type' => $role['type'],
                            'members' => $m,
                            'showAvailableCapacity' => $showAvailableCapacity
                        );
                    }
                    $row['team_role_and_members'] = $roleAndMembers;
                    $data[] = $row;
                }
                $teamCreateLink = '';
                $disclaimerHookId = 0;
                if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] && Team::CanCreateCircleByRole($groupid,$_USER->id())) {
                    if (Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['TEAMS__CIRCLE_CREATE_BEFORE'], $groupid)) {
                        $disclaimerHookId = Disclaimer::DISCLAIMER_HOOK_TRIGGERS['TEAMS__CIRCLE_CREATE_BEFORE'];

                    }

                    $teamCreateLink = $_COMPANY->getAdminURL().'/native/createUpdateTeam.php?teamid=' .$_COMPANY->encodeId(0) . '&groupid='.$_COMPANY->encodeId($groupid);
                }

                $finalData = array (
                    'my_teams' => $data,
                    'team_role_requested' => (count(Team::GetUserJoinRequests($groupid)) > 0),
                    'teamCreateLink' => $teamCreateLink,
                    'disclaimerHookId'=>$disclaimerHookId
                );
               
                exit(self::buildApiResponseAsJson($method, $finalData, 1, gettext('My teams lists'), 200));
                   
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['group']['name-short']), 200));
            }
        }
    }
    
    /**
     * getMyTeamDetail API getting the detail of a Team
     *
     * @param  array $get array of GET request parameters [ method:getMyTeamDetail; zoneid:27; teamid:288 ]
     * @return Json response
     */
    public function getMyTeamDetail($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getMyTeamDetail";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            
            $teamid = $get['teamid'];
            $team = Team::GetTeam($teamid);
            if ($team){ 
                $group = Group::GetGroup($team->val('groupid'));

                if ($group->val('isactive') != Group::STATUS_ACTIVE ) {
                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('Access denied. The %s is not active. Please contact your adminstroator'),$_COMPANY->getAppCustomization()['group']['name-short']), 200));
                }

                if (!$group->isTeamsModuleEnabled()){
                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('Access denied. The %s feature is disabled. Please contact your adminstroator'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
                }

                if($team->val('isactive') == Team::STATUS_INACTIVE){
                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('This %s is currently inactive'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
                }

                $getBasicInfo = (int) ($get['getBasicInfo'] ?? 0);
                if(!$getBasicInfo && !$team->isTeamMember($_USER->id())){
                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('Access denied. You are not currently a member of this %s'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
                }
                $roleJoinOptions = array();
                if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] && $team->val('isactive') != Team::STATUS_COMPLETE && $team->val('isactive') != Team::STATUS_INCOMPLETE && $team->val('isactive') != Team::STATUS_PAUSED){
                    $allRoles = Team::GetProgramTeamRoles($team->val('groupid'), 1);
                    $circleRolesCapacity = Team::GetCircleRolesCapacity($team->val('groupid'),$team->val('teamid'));

                    
                    foreach($allRoles as $role) {
                        if ($role['sys_team_role_type'] != '3') continue; // We will only show mentee type join buttons
                       
                        $members = $team->getTeamMembers($role['roleid']);
                        $allowedRoleCapacity = $circleRolesCapacity[$role['roleid']]['circle_role_max_capacity'];
                        $availableRoleCapacity = ($allowedRoleCapacity - count($members));
                        $isRoleJoined = $team->isTeamMember($_USER->id(), $role['roleid']);
                        $isTeamMember = $team->isTeamMember($_USER->id());
                        $canJoinRole = Team::CanJoinARoleInTeam($team->val('groupid'),$_USER->id(),$role['roleid']); 

                        $isRequestAllowd  = true;
                        $guardRails = json_decode($role['restrictions'],true);
                        if (!empty($guardRails)){
                            $isRequestAllowd = Team::CheckRequestRoleJoinAllowed($_USER->id(), $guardRails);
                        }

                        $helpText = "";
                        $disabled = false;
                        $buttonText = sprintf(gettext('Join %1$s as %2$s'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),$role['type']);
                        if ($isRoleJoined || $isTeamMember) { 
                            $helpText = sprintf(gettext("You are already joined this %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
                            $disabled = true;
                        } elseif (!$canJoinRole) {
                            $helpText = sprintf(gettext("Are you sure you want to join this %s?"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
                        } elseif ( $isRequestAllowd && $availableRoleCapacity > 0) {
                            if(!empty($role['registration_end_date']) && $role['registration_end_date'] < date('Y-m-d')){
                                $helpText = sprintf(gettext("Registration is now closed, and we are no longer accepting new requests for this role."),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));
                                $disabled = true;
                            } else {
                                $helpText = gettext("Are you sure you want to join this circle?");
                            }
                        } else { 
                            $helpText =  gettext("All slots are filled");
                            $disabled = true;
                        }
                        $roleJoinOptions[] = array(
                            'roleid' => $role['roleid'],
                            'roletype' => $role['type'],
                            'helpText' => $helpText,
                            'buttonText' => $buttonText,
                            'disabled' => $disabled
                        ); 
                    }
                }

                $hiddenTabs = $group->getHiddenProgramTabSetting();
                $status = array(
                    '0'=>gettext('In-active'),
                    '1'=>gettext('Active'),
                    '2'=>gettext('Draft'),
                    '100'=>gettext('Delete'),
                    '110'=>gettext('Complete'),
                    '109'=>gettext('Incomplete'),
                    '108'=>gettext('Paused')
                );

                $members = $team->getTeamMembers(0);
               
                $row = array();
                $row['teamid'] = $team->val('teamid');
                $row['groupid'] = $team->val('groupid');
                $row['chapterid'] = $team->val('chapterid');
                $row['team_name'] = $team->val('team_name');
                $row['team_meta_name'] = $team->val('team_meta_name');
                $row['status'] = $status[$team->val('isactive')];
                $row['isactive'] = $team->val('isactive');
                $row['hashtags'] =  $team->val('handleids') ? HashtagHandle::GetAllHashTagHandles($team->val('handleids')) : array();
                global $post_css;
                $row['team_description'] = $team->val('team_description') ? $post_css . '<div class="post-inner">' . $team->val('team_description') . '</div>' : '';
                $m = array();
                foreach($members as $member){
                    
                    if ( $group->getTeamProgramType() ==Team::TEAM_PROGRAM_TYPE['CIRCLES'] && ( $team->val('isactive') != Team::STATUS_COMPLETE && $team->val('isactive') != Team::STATUS_INCOMPLETE && $team->val('isactive') != Team::STATUS_PAUSED && (($team->isCircleCreator() && $member['userid']!=$_USER->id()) || $team->canLeaveCircle($member['team_memberid']))) ){ 
                        if ($team->isCircleCreator()){ 
                            $leveMembershipConfirmMessage = sprintf(gettext("Are you sure to remove this member from %s?"), Team::GetTeamCustomMetaName($group->getTeamProgramType()));
                            $leaveButtonText = gettext("Remove");
                        } else {
                            
                        } 
                    } 

                    $m[] = array(
                        'team_memberid'=>$member['team_memberid'],
                        'role'=>$member['role'],
                        'roletitle' => $member['roletitle'],
                        'firstname'=>$member['firstname'],
                        'lastname'=>$member['lastname'],
                        'email'=>$member['email'],
                        'picture'=>$member['picture'],
                        'jobtitle'=>$member['jobtitle']
                    );
                }
                $row['members'] = $m;
                $row['action_item_enabled'] = !in_array(TEAM::PROGRAM_TEAM_TAB['ACTION_ITEM'], $hiddenTabs);
                $row['touch_point_enabled'] = !in_array(TEAM::PROGRAM_TEAM_TAB['TOUCH_POINT'], $hiddenTabs);
                $row['feedback_enabled'] = !in_array(TEAM::PROGRAM_TEAM_TAB['FEEDBACK'], $hiddenTabs);
                $row['message_enabled'] = $group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV'] ? false : !in_array(TEAM::PROGRAM_TEAM_TAB['TEAM_MESSAGE'], $hiddenTabs);

                $canUpdateStatus = false;
                if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['ADMIN_LED'] && $team->canUpdateTeamStatus($group) && in_array($team->val('isactive'),[Team::STATUS_ACTIVE, Team::STATUS_PAUSED])) {
                    $canUpdateStatus = true;
                }
                $row['can_update_team_status'] = $canUpdateStatus;
                $programTyes = array_flip(Team::TEAM_PROGRAM_TYPE);
                $row['program_type']  = $programTyes[$group->getTeamProgramType()];

                $leveMembershipConfirmMessage = '';
                $leaveButtonText  = '';
                $can_leave_team = false;
                $myTeamMembershipId = 0;
                $myMembershipRecord = Arr::SearchColumnReturnRow($members, $_USER->id(),'userid');
                $mySysRecordIsMentee = ($myMembershipRecord['sys_team_role_type'] == 3);
                $teamUpdateLink = '';
                $canEditTeam = false;
                $mySysRecordIsAdmin = (Arr::SearchColumnReturnRow($members, $_USER->id(),'userid')['sys_team_role_type'] == 2);

                if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['ADMIN_LED'] &&
                !in_array($team->val('isactive'), [Team::STATUS_COMPLETE, Team::STATUS_INCOMPLETE, Team::STATUS_PAUSED]) &&
                !($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] && !$mySysRecordIsAdmin)) {
                    $teamUpdateLink = $_COMPANY->getAdminURL().'/native/createUpdateTeam.php?teamid=' .$_COMPANY->encodeId($teamid) . '&groupid='.$_COMPANY->encodeId($team->val('groupid'));
                    $canEditTeam = true;
                }

                $row['can_edit_team'] = $canEditTeam;
                $row['teamUpdateLink'] = $teamUpdateLink;
                
                if (($_USER->id() != $team->val('createdby')) && ($team->val('isactive') == Team::STATUS_ACTIVE) && ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']) && $mySysRecordIsMentee /*mentee*/)
                {   
                    $myTeamMembershipId = (int) $myMembershipRecord['team_memberid'];
                    $can_leave_team = true;
                    $leveMembershipConfirmMessage = sprintf(gettext("Are you sure you want to leave this %s?"), Team::GetTeamCustomMetaName($group->getTeamProgramType()));
                    $leaveButtonText = gettext("Leave");
                }
                $row['can_leave_team'] = $can_leave_team;
                $row['leveMembershipConfirmMessage']= $leveMembershipConfirmMessage;
                $row['leaveButtonText'] = $leaveButtonText;
                $row['disclaimer'] = $group->getProgramDisclaimer()??'';
                $row['roleJoinOptions'] = $roleJoinOptions;
                $row['myTeamMembershipId'] = $myTeamMembershipId;
                exit(self::buildApiResponseAsJson($method, $row, 1, gettext('Teams detail'), 200));
                  
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
            }
        }
    }
    
    /**
     * getTeamActionItems API for getting the list of Action items of a Team
     *
     * @param  array $get array of GET request parameters [ method:getTeamActionItems; zoneid:27; teamid:288 ]
     * @return Json response
     */
    public function getTeamActionItems($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getTeamActionItems";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $teamid = $get['teamid'];
            $team = Team::GetTeam($teamid);
            if ($team){ 
                $group = Group::GetGroup($team->val('groupid'));
                $actionItemsList = $team->getTeamsTodoList();
                $createActionItemLink = '';
                if ($team->val('isactive') == Team::STATUS_ACTIVE){
                    $createActionItemLink = $_COMPANY->getAdminURL().'/native/createUpdateActionItem.php?teamid=' .$_COMPANY->encodeId($team->val('teamid')) . '&taskid='.$_COMPANY->encodeId(0);
                }
                $finalData = array();
               
                foreach($actionItemsList as $actionItem){
                    $finalData[] = $this->cleanTaskItemRow($group, $team, $actionItem);
                }
                exit(self::buildApiResponseAsJson($method, array('action_items'=>$finalData,'action_item_create_link'=>$createActionItemLink), 1, gettext('Action item list'), 200));
               
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
            }
        }
    }
    
    /**
     * getTeamTouchPoints API for getting the list of Touch points of a Team
     *
     * @param  array $get array of Get request parameters [ method:getTeamTouchPoints; zoneid:27; teamid:288; timezone: {users current timezone e.g Asia/Kolkata} ]
     * @return Json response
     */
    public function getTeamTouchPoints($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getTeamTouchPoints";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }

            $timezone = "UTC";
            if (isset($get['timezone'])){
                $timezone = $get['timezone'];
            }
            $teamid = $get['teamid'];
            $team = Team::GetTeam($teamid);
            if ($team){ 
                $group = Group::GetGroup($team->val('groupid'));
                $touchpoints = $team->getTeamsTouchPointsList();
                $touchPointTypeConfig = $group->getTouchpointTypeConfiguration();
                $create_touchpoint_link = "";
                if ($touchPointTypeConfig['type']!='touchpointevents' && $team->val('isactive') == Team::STATUS_ACTIVE){
                    $create_touchpoint_link = $_COMPANY->getAdminURL().'/native/createUpdateTouchPoint.php?teamid=' .$_COMPANY->encodeId($team->val('teamid')) . '&touchpointid='.$_COMPANY->encodeId(0);
                }
                $create_touchpoint_event_link = "";
                if ($touchPointTypeConfig['type']=='touchpointevents' && $_COMPANY->getAppCustomization()['teams']['team_events']['enabled'] && $team->val('isactive') == Team::STATUS_ACTIVE){
                    $create_touchpoint_event_link = $_COMPANY->getAdminURL().'/native/createUpdateTouchPointEvent.php?&teamid=' .$_COMPANY->encodeId($team->val('teamid')) . '&eventid='.$_COMPANY->encodeId(0).'&touchpointid='.$_COMPANY->encodeId(0).'&timezone='.$timezone;
                }

                $cleanTouchpoints = array();
                foreach($touchpoints as $touchpoint){
                    $cleanTouchpoints[] = $this->cleanTouchPointRow($team, $touchpoint);
                }
                
                $finalData = array('touchpoints' => $cleanTouchpoints,'configuration'=>$touchPointTypeConfig,'create_touchpoint_link'=>$create_touchpoint_link,'create_touchpoint_event_link'=>$create_touchpoint_event_link);
             
                exit(self::buildApiResponseAsJson($method, $finalData, 1, gettext('Touch points item list'), 200));
               
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
            }
        }
    }
    
    /**
     * getTeamFeedbacks API for getting the list of Feedbacks of a Team
     *
     * @param  array $get array of GET request parameter [ method:getTeamFeedbacks; zoneid:27; teamid:288 ]
     * @return Json response
     */
    public function getTeamFeedbacks($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getTeamFeedbacks";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $teamid = $get['teamid'];
            $team = Team::GetTeam($teamid);
            if ($team){ 
                $group = Group::GetGroup($team->val('groupid'));
                $feedbacks = $team->getTeamsFeedbackList();

                $finalData = array();
                foreach($feedbacks as $feedback){
                    if (!$team->loggedinUserCanViewFeedback($feedback['visibility'], $feedback['createdby'], $feedback['assignedto'])) {
                        continue;
                    }
                   $finalData[] = $this->cleanFeedbackRow($group, $team, $feedback);
                }
                $create_feedback_link = ($team->val('isactive') == Team::STATUS_ACTIVE) ? $_COMPANY->getAdminURL().'/native/createUpdateTeamFeedback.php?teamid=' .$_COMPANY->encodeId($team->val('teamid')) . '&feedbackid='.$_COMPANY->encodeId(0) : '';
                exit(self::buildApiResponseAsJson($method, array('feedbacks'=>$finalData,'create_feedback_link'=>$create_feedback_link), 1, gettext('Feedbacks list'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
            }
        }
    }
    
    /**
     * getActionItemDetail API for getting the detail of Action items
     *
     * @param  array $get array of GET request parameters [ method:getActionItemDetail; zoneid:27; teamid:288; taskid:168 ] 
     * @return Json response
     */
    public function getActionItemDetail($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getActionItemDetail";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid'], 'taskid' => @$get['taskid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $teamid = $get['teamid'];
            $team = Team::GetTeam($teamid);
            if ($team){ 
                $taskid = $get['taskid'];
                $group = Group::GetGroup($team->val('groupid'));
                $data = $team->getTodoDetailWithChild($taskid);

                if (empty($data[0]) ||
                    (($data[0]['task_type'] == 'feedback') && !$team->loggedinUserCanViewFeedback($data[0]['visibility'], $data[0]['createdby'], $data[0]['assignedto']))
                ) {
                    exit(self::buildApiResponseAsJson($method, '', 0, HTTP_FORBIDDEN, 200));
                }

                $actionItem = $this->cleanTaskItemRow($group, $team, $data[0]);
                array_shift($data);
                if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){
                    $data = array();
                }

                $relatedTouchPoints = array();
                foreach($data as $row){
                    $relatedTouchPoints[] = $this->cleanTouchPointRow($team, $row,'basic');
                }

                if ($actionItem['show_comments']){
                    $comments = $this->getCleanComments('TeamTask', $taskid);
                    $actionItem['totalComments'] = (int) TeamTask::GetCommentsTotal($taskid); // Should be int
                    $actionItem['comments'] = $comments;
                } else {
                    $actionItem['totalComments'] = 0; // Should be int
                    $actionItem['comments'] =  array();
                }

                $topic = Teleskope::GetTopicObj('TSK', $taskid);
                $attachmentsObj = $topic->getAttachments(false);
                   
                $attachments = array();
                foreach ($attachmentsObj as $attachment) {
                    $attachments[] = array(
                                        'attachmentid'=>$attachment->encodedId(),
                                        'icon'=>$attachment->getImageIcon(true),
                                        'displayName'=>$attachment->getDisplayName(),
                                        'fileSize'=> $attachment->getReadableSize()
                                    );
                }
                $actionItem['attachments']  = $attachments;
                $finalData = array('actionItemDetail'=>$actionItem,'relatedTouchPoints'=>$relatedTouchPoints);
                exit(self::buildApiResponseAsJson($method, $finalData, 1, gettext('Action item detail'), 200));
                
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
            }
        }
    }
    
    /**
     * getTouchPointDetail API for getting the detail of Touch point
     *
     * @param  array $get array of GET request parameters [ method:getTouchPointDetail; zoneid:27; teamid:288; touchpointid:173 ]
     * @return Json response
     */
    public function getTouchPointDetail($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getTouchPointDetail";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid'],'touchpointid'=>@$get['touchpointid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $teamid = $get['teamid'];
            $team = Team::GetTeam($teamid);
            if ($team){ 
                $touchpointid = $get['touchpointid'];
                $data = $team->getTodoDetailWithChild($touchpointid);

                if (empty($data[0]) ||
                    (($data[0]['task_type'] == 'feedback') && !$team->loggedinUserCanViewFeedback($data[0]['visibility'], $data[0]['createdby'], $data[0]['assignedto']))
                ) {
                    exit(self::buildApiResponseAsJson($method, '', 0, HTTP_FORBIDDEN, 200));
                }
                $touchpoint = $this->cleanTouchPointRow($team, $data[0]);

                if ($touchpoint['show_comments']){
                    $comments = $this->getCleanComments('TeamTask',$touchpointid);
                    $touchpoint['totalComments'] = (int) TeamTask::GetCommentsTotal($touchpointid); // Should be int
                    $touchpoint['comments'] = $comments;
                } else {
                    $touchpoint['totalComments'] = 0; // Should be int
                    $touchpoint['comments'] = array();
                }
                   
               
                $topic = Teleskope::GetTopicObj('TSK', $touchpointid);

                $attachmentsObj = $topic->getAttachments(false);
                   
                $attachments = array();
                foreach ($attachmentsObj as $attachment) {
                    $attachments[] = array(
                                        'attachmentid'=>$attachment->encodedId(),
                                        'icon'=>$attachment->getImageIcon(true),
                                        'displayName'=>$attachment->getDisplayName(),
                                        'fileSize'=> $attachment->getReadableSize()
                                    );
                }
           
                $touchpoint['attachments']  = $attachments;
                

                exit(self::buildApiResponseAsJson($method, $touchpoint, 1, gettext('Touchpoint detail'), 200));
                
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
            }
        }
    }
    
    /**
     * getTeamFeedbackDetail API for getting the detail of Feedback
     *
     * @param  array $get array of GET request parameters [ method:getTeamFeedbackDetail; zoneid:27; teamid:288; feedbackid:176 ] 
     * @return Json response
     */
    public function getTeamFeedbackDetail($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getTeamFeedbackDetail";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid'], 'feedbackid' => @$get['feedbackid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $teamid = $get['teamid'];
            $team = Team::GetTeam($teamid);
            if ($team){ 
                $feedbackid = $get['feedbackid'];
                $group = Group::GetGroup($team->val('groupid'));
                $data = $team->getTodoDetailWithChild($feedbackid);

                if (empty($data[0]) ||
                    (($data[0]['task_type'] == 'feedback') && !$team->loggedinUserCanViewFeedback($data[0]['visibility'], $data[0]['createdby'], $data[0]['assignedto']))
                ) {
                    exit(self::buildApiResponseAsJson($method, '', 0, HTTP_FORBIDDEN, 200));
                }

                $feedback = $this->cleanFeedbackRow($group, $team, $data[0]);
                array_shift($data);
                
                if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){
                    $data = array();
                }
                $relatedActionItems = array();
                foreach($data as $row){
                    $relatedActionItems[] = $this->cleanTaskItemRow($group, $team, $row,'basic');
                }
                
                if ($feedback['show_comments']){
                    $comments = $this->getCleanComments('TeamTask',$feedbackid);
                    $feedback['totalComments'] = (int) TeamTask::GetCommentsTotal($feedbackid); // Should be int
                    $feedback['comments'] = $comments;
                } else {
                    $feedback['totalComments'] = 0; // Should be int
                    $feedback['comments'] = array();
                }

                $topic = Teleskope::GetTopicObj('TSK', $feedbackid);
                $attachmentsObj = $topic->getAttachments(false);
                $attachments = array();
                foreach ($attachmentsObj as $attachment) {
                    $attachments[] = array(
                                        'attachmentid'=>$attachment->encodedId(),
                                        'icon'=>$attachment->getImageIcon(true),
                                        'displayName'=>$attachment->getDisplayName(),
                                        'fileSize'=> $attachment->getReadableSize()
                                    );
                }
                $feedback['attachments']  = $attachments;
                $finalData = array('feedbackDetail'=>$feedback,'relatedActionItems'=>$relatedActionItems);
                exit(self::buildApiResponseAsJson($method, $finalData, 1, gettext('Feedback detail'), 200));
                
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
            }
        }
    }
    
    /**
     * updateTeamContentStatus API to update Team Status
     *
     * @param  array $get array of POST request parameters [ method:updateTeamContentStatus; teamid:288; contentid:{ taskid  in case of Action item and touchpointi in case of Touch point}; status:{ 1 => "Pending", 51 => 'In Progress', 52 => 'Done'}; zoneid:27 ]
     * @return Json response
     */
    function updateTeamContentStatus($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "updateTeamContentStatus";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid'], 'contentid' => @$get['contentid'],'status' => @$get['status']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad requestS'), 400));
                }
            }
            $teamid = $get['teamid'];
            $team = Team::GetTeam($teamid);
            if ($team){ 
                $availableStatus = Team::GET_TALENTPEAK_TODO_STATUS;
                $contentid = (int)$get['contentid'];
                $status = (int)$get['status'];
                if (!array_key_exists($status,$availableStatus)){
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
                
                if (
                    ($content = $team->getTodoDetail($contentid)) == null
                ) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            
                if ($team->updateTeamTaskStatus($contentid, $status)) {
                    $send_email_notification = ((int) ($get['send_email_notification'] ?? 0) === 1);
                    if ($status != 1 && $send_email_notification) {
                        if ($content['task_type'] == 'todo') {
                            $team->sendActionItemEmailToTeamMember($contentid);
                        }
                        if ($content['task_type'] == 'touchpoint') {
                           $team->sendTouchPointEmailToTeamMember($contentid);
                        }
                    }
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Status updated successfully'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong while updating status.'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
            }
        }
    }
    
    /**
     * deleteTeamContent API to delete Actionitem/touchpoint/feedback
     *
     * @param  array $get array of POST request parameters [ method:deleteTeamContent; teamid:288; contentid:{ taskid  in case of Action item and touchpointi in case of Touch point, feedbackid in case of feedback}; zoneid:27 ]
     * @return Json response
     */
    function deleteTeamContent($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteTeamContent";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid'], 'contentid' => @$get['contentid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad requestS'), 400));
                }
            }
            $teamid = $get['teamid'];
            $team = Team::GetTeam($teamid);
            if ($team){ 
                $contentid = (int)$get['contentid'];
                if (
                    ($content = $team->getTodoDetail($contentid)) == null
                ) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            
                if ($team->deleteTeamTask($contentid)) {
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Deleted successfully'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong while updating status.'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
            }
        }
    }
    
    /**
     * updateTeamInformation API to update team information
     *
     * @param  array $get array of POST request parameters [ method:updateTeamInformation; zoneid:27; teamid:288; team_name: Updated name ]
     * @return Json response
     */
    function updateTeamInformation($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "updateTeamInformation";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid'], 'team_name' => @$get['team_name']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad requestS'), 400));
                }
            }
            $teamid = $get['teamid'];
            $team = Team::GetTeam($teamid);
            if ($team){ 
                $team_name = (string)$get['team_name'];
                if (!$team_name) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            
                if (Team::CreateOrUpdateTeam($team->val('groupid'), $teamid, $team_name, $team->val('chapterid'))) {
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Updated successfully'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong while updating.'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
            }
        }
    }
    
    /**
     * updateTeamStatus API to update team status
     *
     * @param  array $get array of POST request parameters [ method:updateTeamStatus; zoneid:27; teamid:288; status: { 1 => “activate, 0 => ‘Inactivate’, 100 => ‘delete’, ‘110’ => complete, ‘109’ => incomplete} ]
     * @return Json Response
     */
    function updateTeamStatus($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "updateTeamStatus";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid'],'status' => @$get['status']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad requestS'), 400));
                }
            }
            $teamid = $get['teamid'];
            $team = Team::GetTeam($teamid);
            
            if ($team){ 
                $group = Group::GetGroup($team->val('groupid'));
                $status = (int)$get['status'];
                
                if (in_array($status, array(Team::STATUS_INACTIVE,Team::STATUS_PURGE,Team::STATUS_ACTIVE,Team::STATUS_COMPLETE,Team::STATUS_INCOMPLETE,Team::STATUS_PAUSED))){
                    if ($status == Team::STATUS_ACTIVE ){

                        $reUpdateUsedRoleCapacity = false;
                        if ($team->isComplete() || $team->isIncomplete() || $team->isPaused()){ //This is the case of reopen team after complate/uncomplete marked 
                            $isRoleCapacityAvailable = $team->isRoleCapacityAvailableOfMembers();
                            if (!$isRoleCapacityAvailable) {
                                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('This %s cannot be reactivated because the maximum number of concurrent programs supported for one member has been reached.'),Team::GetTeamCustomMetaName($group->getTeamProgramType())), 200));
                            }
                            $reUpdateUsedRoleCapacity = true;
                        }
                        $response = $team->activate();
                        if ($response['status']){
                            if ($reUpdateUsedRoleCapacity) {
                                $team->resetRoleUsedCapacity();
                            }

                            if ($group->getTeamProgramType()== Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){
                                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Development program started successfully'), 200));
                            } else {
                                exit(self::buildApiResponseAsJson($method, '', 1, sprintf(gettext('%1$s %2$s activated successfully.'),$team->val('team_name'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
                            }
                        } else {
                            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%1$s %2$s Error'),$team->val('team_name'), $_COMPANY->getAppCustomization()['teams']['name']).' : '.$response['error'], 200));
                        }
                    } elseif ($status == Team::STATUS_INACTIVE){
                        $team->deactivate();
                        exit(self::buildApiResponseAsJson($method, '', 1, sprintf(gettext('%1$s %2$s deactivated successfully.'),$team->val('team_name'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
                    } elseif ($status == Team::STATUS_COMPLETE){
                        // Check if all touchpoints and action items are completed
                        [$pendingActionItemsCount,$pendingTouchPointsCount] = $team->GetPendingActionItemAndTouchPoints();

                        if ($pendingActionItemsCount>0 || $pendingTouchPointsCount > 0) {
                            $actionItem = $pendingActionItemsCount>0 ? gettext('Action Items') : '';
                            $touchPoints = $pendingTouchPointsCount > 0 ? gettext('Touch Points') : '';
                            $divider =  ($pendingActionItemsCount>0  && $pendingTouchPointsCount > 0) ? ' ' .gettext('and'). ' ' : '';
                            $finalString = $actionItem.$divider.$touchPoints;
                            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('You have not completed all required %1$s, so the %2$s cannot be marked as complete'), $finalString,Team::GetTeamCustomMetaName($group->getTeamProgramType())), 200));
                        }

                        $team->complete();
                        exit(self::buildApiResponseAsJson($method, '', 1, sprintf(gettext('%1$s %2$s completed successfully.'),$team->val('team_name'),$_COMPANY->getAppCustomization()['teams']['name']), 200));

                    } elseif ($status == Team::STATUS_INCOMPLETE){
                        $team->incomplete();
                        exit(self::buildApiResponseAsJson($method, '', 1, sprintf(gettext('%1$s %2$s closed as incomplete successfully.'),$team->val('team_name'),$_COMPANY->getAppCustomization()['teams']['name']), 200));

                    } elseif ($status == Team::STATUS_PAUSED){
                        $team->paused();
                        exit(self::buildApiResponseAsJson($method, '', 1, sprintf(gettext('%1$s %2$s is paused successfully.'),$team->val('team_name'),$_COMPANY->getAppCustomization()['teams']['name']), 200));

                    }  elseif($status == Team::STATUS_PURGE){
                        $team->deleteTeamPermanently();
                        exit(self::buildApiResponseAsJson($method, '', 1, sprintf(gettext('%1$s %2$s deleted successfully.'),$team->val('team_name'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
                    }else {
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Update Failed.'), 200));
                    }
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
            }
        }
    }
    
    /**
     * linkTeamContent API to link Action item to Touchpoint and Feedback to Action item
     *
     * @param  array $get array of POST request paramters [ method:linkTeamContent; teamid:288; contentid:{ taskid  in case of Action item and feedbackid in case of Feedback}; zoneid:27 ]
     * @return Json response
     */
    function linkTeamContent($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "linkTeamContent";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid'], 'contentid' => @$get['contentid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad requestS'), 400));
                }
            }
            $teamid = $get['teamid'];
            $team = Team::GetTeam($teamid);
            if ($team){ 
                $group = Group::GetGroup($team->val('groupid'));
                $contentid = (int)$get['contentid'];
                if (
                    ($content = $team->getTodoDetail($contentid)) == null
                ) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
                $link_content_with = $content['task_type'] == 'todo' ? 'touchpoint' : 'todo';
                $tasktitle = $content['tasktitle'];
                $assignedto = $content['assignedto'];
                $duedate = $content['duedate'];
                $description = $content['description'];
                $visibility = $content['visibility'];

                if (($id = $team->addOrUpdateTeamTask(0, $tasktitle, $assignedto, $duedate, $description, $link_content_with, $visibility, $contentid))) {
                    
                    if ($link_content_with == 'touchpoint'){
                        if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ // Skip email notification if individual development
                            $team->sendTouchPointEmailToTeamMember($id, 'created');
                        }
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext('New touch point linked successfully'), 200));
                    } else {
                        if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ // Skip email notification if individual development
                            $team->sendActionItemEmailToTeamMember($id, 'created');
                        }
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext('New task linked successfully'), 200));
                    }

                } else {
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Something went wrong while linking content'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
            }
        }
    }

    
    /**
     * manageTeamRoleJoinRequests API for managing the list of program roles to send join request
     *
     * @param  array $get array of GET request parameters  [ method:manageTeamRoleJoinRequests; groupid:154; zoneid:27 ]
     * @return Json reqponse
     */
    function manageTeamRoleJoinRequests($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "manageTeamRoleJoinRequests";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $groupid = $get['groupid'];
            $group = Group::GetGroup($groupid);
            if ($group){ 
                $roles = $this->manageTeamJoinRoles($groupid);
                if (!empty($roles)){
                    exit(self::buildApiResponseAsJson($method, $roles, 1, gettext('Manage roles requests'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('No role are available to request'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['group']['name-short']), 200));
            }
        }
    }
    
    /**
     * cancelTeamRoleJoinRequest Api to cancel request team role
     *
     * @param  array $get array of POST requests parameters [ method:cancelTeamRoleJoinRequest; groupid:154; roleid:100; zoneid:27 ]
     * @return Json Response
     */
    function cancelTeamRoleJoinRequest($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "cancelTeamRoleJoinRequest";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'],'roleid' =>@$get['roleid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $groupid = $get['groupid'];
            $group = Group::GetGroup($groupid);
            if ($group){ 
            $roleid = (int)$get['roleid'];
                if (Team::CancelTeamJoinRequest($groupid, $roleid, $_USER->id())) {
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Registration canceled successfully'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong while updating.'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['group']['name-short']), 200));
            }
        }
    }
    
    /**
     * discoverTeamMembers Api to discover suggestions for team role invities
     *
     * @param  array $get array of GET requests parameters [ method:discoverTeamMembers groupid:154; zoneid:27 ]
     * @return Json response
     */
    function discoverTeamMembers($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "discoverTeamMembers";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $groupid = $get['groupid'];
            $showAvailableCapacity = (int) ($get['showAvailableCapacity'] ?? 0);
            $group = Group::GetGroup($groupid);
            if ($group){

                $matchingParameters = $group->getTeamMatchingAlgorithmParameters();
               
                $filter_attribute_keyword = $get['filter_attribute_keyword'] ?? array();
                if (!is_array($filter_attribute_keyword )) {
                    $filter_attribute_keyword  = array($filter_attribute_keyword );
                }
                $filter_primary_attribute = $get['filter_primary_attribute'] ?? array();
                if (!is_array($filter_primary_attribute )) {
                    $filter_primary_attribute  = array($filter_primary_attribute );
                }
                $name_keyword = trim($get['name_keyword'] ?? '');
                $oppositeUseridsWithRoles = array();
                $filter_attribute_type = explode(',', $get['filter_attribute_type']??'');
                [$status, $suggestionsRows]  = Team::GetTeamMembersSuggestionsForRequestRoles($group,$_USER->id(),0, $oppositeUseridsWithRoles,$filter_attribute_keyword, $filter_primary_attribute, $name_keyword, $filter_attribute_type);
                if ($status == 1) {
                    $suggestions = $this->cleanSuggestionsData($group,$suggestionsRows,$showAvailableCapacity);
                    $row['sugesstionsData'] = $suggestions;
                    $row['matchingAlgo']['enabled'] = true;
                    $row['matchingAlgo']['title'] = "";
                    $row['matchingAlgo']['description'] = "";
                    if(!$matchingParameters){
                        $matchingParamErrorDesc = sprintf(gettext('The Discover feature relies on matching criteria. Please request your %1$s leaders to complete the setup process.'), $_COMPANY->getAppCustomization()['group']['name']);
                        $matchingParamErrorHeading = sprintf(gettext('%1$s setup is not complete'), $_COMPANY->getAppCustomization()['group']['name-short']);
                        $row['matchingAlgo']['enabled'] = false;
                        $row['matchingAlgo']['title'] = $matchingParamErrorHeading;
                        $row['matchingAlgo']['description'] = $matchingParamErrorDesc;
                    }
                   
                    exit(self::buildApiResponseAsJson($method, $row, 1, gettext('Discover suggessions'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('To Discover your matches, please Register for a role'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['group']['name-short']), 200));
            }
        }
    }

    /**
     * cleanSuggestionsData  Helper method to discover suggestions
     *
     * @param  Class $group
     * @param  array $suggestionsRows
     * @return array
     */
    private function cleanSuggestionsData(Group $group, array $suggestionsRows, bool $showAvailableCapacityOnly=false)
    {
        global $_COMPANY, $_USER, $_ZONE;
        if (empty($suggestionsRows)){
            return array();
        }
        $groupid = $group->id();
        $suggestions = array();
        foreach($suggestionsRows as $rows) {
            $oppositeRole = Team::GetTeamRoleType($rows['oppositRoleId']);
            $canSendRequest = true;
            if(!Team::CanSendP2PTeamJoinRequest($groupid,$_USER->id(),$rows['roleid'])){
                $canSendRequest = false;
                $bannerHoverTextSenderCapacity = sprintf(gettext('You can\'t send a request to this user as you\'ve reached your maximum available capacity limit. This limit is based on the number of %1$s you\'re already in and any outstanding %2$s join requests'), Team::GetTeamCustomMetaName($group->getTeamProgramType(), 1), Team::GetTeamCustomMetaName($group->getTeamProgramType(), 0));
            }
            $inviteUserButtontext  = gettext('Invite user');
            $disableInviteUserButton = false;
            if ($canSendRequest && Team::CanJoinARoleInTeam($groupid,$_USER->id(),$rows['roleid'])){ 
                if (!Team::IsTeamRoleRequestAllowed($groupid,$oppositeRole['roleid'])){
                    $disableInviteUserButton = true;
                }
                $inviteUserSectionHeaderText =  sprintf(gettext("In addition to the following recommendations, you can also directly invite users to join the %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
             } else {
                $inviteUserSectionHeaderText =  sprintf(gettext('You\'ve reached the maximum number of %1$s you can participate in as a %2$s. Therefore, you can\'t send requests or invite users to form new %1$s where you take on the %2$s role.'),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1),$rows['type']);
                $disableInviteUserButton = true;
            }

            $allSuggestions = $rows['suggestions'];
            if ($showAvailableCapacityOnly) {  // Fiter 
                $availableRequestCapacityMatchedUsers = array();
                foreach($allSuggestions as $s) {
                list(
                    $roleSetCapacity,
                    $roleUsedCapacity,
                    $roleRequestBuffer,
                    $roleAvailableCapacity,
                    $roleAvailableRequestCapacity,
                    $roleAvailableBufferedRequestCapacity,
                    $pendingSentOrReceivedRequestCount
                ) = Team::GetRoleCapacityValues($groupid, $rows['oppositRoleId'], $s['userid']);
                
                if ($roleSetCapacity == 0 || $roleAvailableRequestCapacity > 0){
                    $availableRequestCapacityMatchedUsers[] = $s;
                }
            }
                $allSuggestions = $availableRequestCapacityMatchedUsers;
            }

            $cleanSuggestions = array();
            foreach($allSuggestions as $s) {
                list(
                    $roleSetCapacity,
                    $roleUsedCapacity,
                    $roleRequestBuffer,
                    $roleAvailableCapacity,
                    $roleAvailableRequestCapacity,
                    $roleAvailableBufferedRequestCapacity,
                    $pendingSentOrReceivedRequestCount
                ) = Team::GetRoleCapacityValues($groupid, $rows['oppositRoleId'], $s['userid']);

                $canAcceptRequest = true;
                $bannerHeading = gettext('Accepting New Requests');
                $bannerSubHeading = ($roleAvailableRequestCapacity == 1) ? sprintf(gettext('%s spot available'),$roleAvailableRequestCapacity) : sprintf(gettext('%s spots available'),$roleAvailableRequestCapacity);
                $requestButtonHoverText = '';
                if ($roleSetCapacity !=0 && $roleAvailableRequestCapacity < 1){
                    $canAcceptRequest = false;
                    $bannerHeading = gettext('Not Accepting New Requests');
                    $bannerSubHeading = gettext('No spots available');
                    $requestButtonHoverText = gettext('You cannot send request as user\'s maximum outstanding requests have been reached.');
                }

                $checkRequestedDetail =  Team::GetTeamJoinRequestDetail($groupid,$rows['userid'],$rows['roleid'],$s['userid'],$rows['oppositRoleId']);
                $request_id = 0;
                $requestButtonConfirmationText = gettext('Are you sure you want to send this request?');
                $buttonText = gettext('Send Request');
                $disableRequestButton = ($canAcceptRequest && $canSendRequest) ? false : true;
                if ($checkRequestedDetail && $checkRequestedDetail['status'] == 1){
                    $request_id = (int)$checkRequestedDetail['team_request_id'];
                    $requestButtonConfirmationText = gettext('Are you sure you want to delete this request?');
                    $buttonText = gettext('Delete Request');
                    $disableRequestButton = false;
                    $requestButtonHoverText = '';
                }
                $requestButtonHoverText = $disableRequestButton ? ($bannerHoverTextSenderCapacity ?: ($requestButtonHoverText?:'')) : '';
                $cleanSuggestions[] = array(
                    'suggested_userid' => $s['userid'],
                    'suggested_firstname' => $s['firstname'],
                    'suggested_lastname' => $s['lastname'],
                    'suggested_email' => $s['email'],
                    'suggested_jobtitle' => $s['jobtitle'],
                    'suggested_picture' => $s['picture'],
                    'suggested_department' => $s['department'],
                    'parameter_wise_matching_percentage' => $s['parameterWiseMatchingPercentage'],
                    'matching_percentage' => $s['matchingPercentage'],
                    'suggested_roleid' => $s['roleid'],
                    'request_id' => $request_id,
                    'canAcceptRequest'=>$canAcceptRequest,
                    'bannerHeading' => $bannerHeading,
                    'bannerSubHeading' => $bannerSubHeading, 
                    'requestButtonHoverText' => $requestButtonHoverText,
                    'requestButtonText' => $buttonText,
                    'disableSendRequestButton' => $disableRequestButton,
                    'requestButtonConfirmationText' => $requestButtonConfirmationText

                );
            }
            $suggestions[] = array(
                'heading' => (empty($oppositeRole['discover_tab_html']) ? sprintf(gettext("Based on your registration information here are the %s matches recommended for you to connect with:"), $rows['oppositRolesType']) : $oppositeRole['discover_tab_html']),
                'join_requestid' => $rows['join_requestid'],
                'roleid' => $rows['roleid'],
                'userid' => $rows['userid'],
                'type' => $rows['type'],
                'oppositRoleId' => $rows['oppositRoleId'],
                'oppositRolesType' => $rows['oppositRolesType'],
                'suggestions' => $cleanSuggestions,
                'inviteUserButtontext' => $inviteUserButtontext,
                'disableInviteUserButton' => $disableInviteUserButton, 
                'inviteUserSectionHeaderText' => $inviteUserSectionHeaderText,
                'discover_tab_show' => $oppositeRole['discover_tab_show'],
                'discover_tab_html' => $oppositeRole['discover_tab_html']
            );
        }
        return $suggestions;
        
    }
    
    /**
     * inviteUserToCreateTeam Api to Invite the discovered user to join team role from discover section
     *
     * @param  array $get array of POST request parameters [ method:inviteUserToCreateTeam; groupid:154; zoneid:27; receiver_id:1; receiver_roleid:22; sender_roleid : 20 ]
     * @return Join response
     */
    function inviteUserToCreateTeam($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "inviteUserToCreateTeam";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }

        $db = new Hems();
        $check = array('groupid' => @$get['groupid'],'receiver_id' =>@$get['receiver_id'], 'receiver_roleid' => @$get['receiver_roleid'],'sender_roleid' => @$get['sender_roleid'] );
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $groupid = $get['groupid'];
            $group = Group::GetGroup($groupid);
            if ($group){ 
                $receiver_id = (int)$get['receiver_id'];
                $receiver_roleid = (int)$get['receiver_roleid'];
                $sender_roleid = (int)$get['sender_roleid'];
                if (Team::SendRequestToJoinTeam($groupid, $receiver_id, $receiver_roleid, $sender_roleid,'','')) {
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Invite sent successfully'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong while sending .'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['group']['name-short']), 200));
            }
        }
    }
    
    /**
     * cancelCreateTeamInvite API to cancel the sent request to join team role on Discover section
     *
     * @param  array $get array of POST request parameters [ method:cancelCreateTeamInvite; groupid:154; zoneid:27; request_id:1 ]
     * @return Json Response
     */
    function cancelCreateTeamInvite($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "cancelCreateTeamInvite";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }

        $db = new Hems();
        $check = array('groupid' => @$get['groupid'],'request_id' =>@$get['request_id']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $groupid = $get['groupid'];
            $group = Group::GetGroup($groupid);
            if ($group){ 
                $request_id = (int)$get['request_id'];
                if (Team::DeleteTeamRequest($groupid, $request_id)) {
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Request deleted successfully'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong while deleting invite'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['group']['name-short']), 200));
            }
        }
    }
    
    /**
     * getTeamJoinInvites API for getting the list of team role join requests from discover section
     *
     * @param  array $get array of GET request parameters [ method: getTeamJoinInvites; groupid:154; zoneid:27]
     * @return json response
     */
    function getTeamJoinInvites($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getTeamJoinInvites";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }

        $db = new Hems();
        $check = array('groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $groupid = $get['groupid'];
            $group = Group::GetGroup($groupid);
            if ($group){ 
                $receivedRequests = Team::GetAllTeamRequestsReceivedByUser($groupid, $_USER->id());
                if (!empty($receivedRequests)) {
                    foreach($receivedRequests as &$receivedRequest){

                        $showAcceptButton = false;
                        $showRejectButton = false;
                        $showDeleteButton = false;
                        $acceptConfirmMsg = "";
                        $rejectConfirmMsg = "";
                        $deleteConfirmMsg = "";

                        if ($receivedRequest['status'] == Team::TEAM_REQUEST_STATUS['PENDING']) {
                            $showAcceptButton = true;
                            $showRejectButton = true;
                            $acceptConfirmMsg = gettext("Are you sure you want to accept this request?");
                            $rejectConfirmMsg = gettext("Are you sure you want to reject this request?");
                        }
                        if ($receivedRequest['status'] != Team::TEAM_REQUEST_STATUS['PENDING']) {
                            $showDeleteButton = true;
                            $deleteConfirmMsg = gettext("Are you sure you want to delete this request?");
                        }

                        $showViewMatchButton = false;
                        if($receivedRequest['status'] == Team::TEAM_REQUEST_STATUS['PENDING'] || $receivedRequest['status'] == Team::TEAM_REQUEST_STATUS['ACCEPTED']){
                            $showViewMatchButton = true;
                        }
                        $receivedRequest['actionButtons'] =array(
                            'showAcceptButton' => $showAcceptButton,
                            'showRejectButton' => $showRejectButton,
                            'showViewMatchButton' => $showViewMatchButton, 
                            'showDeleteButton' => $showDeleteButton,
                            'acceptConfirmMsg' => $acceptConfirmMsg,
                            'rejectConfirmMsg' => $rejectConfirmMsg,
                            'deleteConfirmMsg' => $deleteConfirmMsg
                        );

                        $receivedRequest['status'] = Team::GetTeamRequestStatusLabel($receivedRequest['status']);
                        $requestAllowed = true;
                        if (!empty(array_column(Team::GetUserJoinRequests($groupid,0,0),'roleid')) && $group->getTeamJoinRequestSetting()!=1){
                            $requestAllowed = false;
                        }
                        $roleRequestLink = '';
                        if ($requestAllowed && !Team::GetRequestDetail($groupid,$receivedRequest['receiver_role_id'],$receivedRequest
                        ['receiverid'])) {
                            $roleRequestLink =  $_COMPANY->getAdminURL().'/native/sendUdateTeamRoleJoinRequest.php?groupid='.$_COMPANY->encodeId($groupid).'&roleid='.$_COMPANY->encodeId(0).'&preselected='.$_COMPANY->encodeId($receivedRequest['receiver_role_id']);

                        }
                        $receivedRequest['roleRequestLink'] = $roleRequestLink;

                    }
                    unset($receivedRequest);
                    exit(self::buildApiResponseAsJson($method, $receivedRequests, 1, gettext('Recived requests list'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('No invites found'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['group']['name-short']), 200));
            }
        }
    }
    
    /**
     * acceptOrRejectTeamInvite Api to accept or reject team join requests received 
     * from team discover section
     *
     * @param  array $get array of POST request parameters [ method:acceptOrRejectTeamInvite; groupid:154; zoneid:27; request_id : 1; status: {'accept','reject'} ]
     * @return json response
     */
    function acceptOrRejectTeamInvite($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "inviteUserToCreateTeam";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }

        $db = new Hems();
        $check = array('groupid' => @$get['groupid'],'request_id' =>@$get['request_id'], 'status' => @$get['status']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $groupid = $get['groupid'];
            $group = Group::GetGroup($groupid);
            if ($group){ 
                $request_id = (int)$get['request_id'];
                $status = (string)$get['status'];

                if (!in_array($status,array('accept','reject'))){
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request--'), 400));
                }
                $getRequestDetail = Team::GetTeamRequestDetail($groupid,$request_id);


                if ($getRequestDetail) {
                    $status = ($status == 'accept') ? 2 : 0;

                    if ($status == 2){ // check maximum number of concurrent programs support
                        if (!Team::CanJoinARoleInTeam($groupid,$_USER->id(),$getRequestDetail['receiver_role_id'])){
                            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Unable to process your request because maximum capacity for active matches has been reached!'), 400));
                        }
                        if (!Team::CanJoinARoleInTeam($groupid,$getRequestDetail['senderid'],$getRequestDetail['sender_role_id'])){
                            exit(self::buildApiResponseAsJson($method, '', 0, gettext('This person has already matched with someone else and no longer has availability. Please decline their request under the Action button.'), 400));
                        }
                    }
                    $rejection_reason = '';
                    if (!$status) {
                        $rejection_reason = $get['rejection_reason']??'';
                    }
                    $resp = Team::AcceptOrRejectTeamRequest($groupid, $request_id, $status, $rejection_reason);
        
                    if ($resp) {
                        $senderDetail = User::GetUser($getRequestDetail['senderid']);
                        $receiverRole = Team::GetTeamRoleType($getRequestDetail['receiver_role_id']);
                        $baseurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));
                        $teamUrl = $baseurl . 'detail?id=' . $_COMPANY->encodeId($groupid) . '&hash=getMyTeams#getMyTeams';
                        if ($status == 2) {
                            $acceptedRequestsCount = Team::GetAcceptedTeamRequestsCount($groupid, $getRequestDetail['senderid'],$getRequestDetail['sender_role_id'], $getRequestDetail['receiver_role_id']);
                            
                            if ($acceptedRequestsCount >= $receiverRole['min_required'] && $acceptedRequestsCount <= $receiverRole['max_allowed']){
                                $teamName = $senderDetail->getFullName() .' & '.$_USER->getFullName();
                                $teamid = Team::CreateOrUpdateTeam($groupid, 0, $teamName);
                                if ($teamid){ 
                                    $team = Team::GetTeam($teamid);
                                    // Add Members
                                    $team->addUpdateTeamMember($getRequestDetail['sender_role_id'], $senderDetail->id());
                                    $team->addUpdateTeamMember($receiverRole['roleid'], $_USER->id());
                                    
                                    // Clear Team Join request
                                    //Team::DeleteTeamRequest($groupid, $request_id);

                                    // Update Team Status
                                    $team->activate(); // This method will take care of creating touchpiont and task from templates and send email to members
                                }
                            }

                            exit(self::buildApiResponseAsJson($method, '', 1, gettext('Request accepted successfully.'), 200)); 
                        } else {
                            $reMessage = gettext("Request rejected successfully");
                            $subject = "{$_COMPANY->getAppCustomization()['teams']['name']} join request rejected";
                            $message = "<p>Hi {$senderDetail->val('firstname')} {$senderDetail->val('lastname')},</p>";
                            $message .= "<br/>";
                            $message .= "<p>Your {$_COMPANY->getAppCustomization()['teams']['name']} join request for {$receiverRole['type']} role is rejected by {$_USER->val('firstname')} {$_USER->val('lastname')}.</p>";
                            $message .= "<br/>";
                            $message .= "<p>Please click on following link to view the {$_COMPANY->getAppCustomization()['teams']['name']} detail:</p>";
                            $message .= "<br/>";
                            $message .= "<a href='{$teamUrl}'>{$teamUrl}</a></p>";
                            $message .= "<br/>";
                            $message .= "<br/>";
                            $message .= "<p>Thank you</p>";
                            $emesg = $message;

                            $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
                            $emesg = str_replace('#messagehere#', $emesg, $template);
                            if ($senderDetail->val('email')){
                                $_COMPANY->emailSend2('', $senderDetail->val('email'), $subject, $emesg, $_ZONE->val('app_type'), '');
                            }

                            exit(self::buildApiResponseAsJson($method, '', 1, gettext('Request rejected successfully.'), 200)); 
                        }
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong, please try again.'), 200));
                    }
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Request not found.'), 200));  
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['group']['name-short']), 200));
            }
        }
    }

    public function viewMatchTeamInvite($get)
    {
        global $_COMPANY, $_USER, $_ZONE;

        $method = "viewMatchTeamInvite";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'],'request_id' => @$get['request_id']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $request_id = $get['request_id'];
            
            $requestDetail = Team::GetTeamRequestDetail($groupid,$request_id);

            if ($requestDetail){

                $senderid       = $requestDetail['senderid'];
                $sender_role_id = $requestDetail['sender_role_id'];
                $receiverid     = $requestDetail['receiverid'];
                $receiver_role_id = $requestDetail['receiver_role_id'];
                $oppositeUseridsWithRoles = array(
                        array(
                            'userid'=>$senderid,
                            'roleid'=>$sender_role_id
                        )
                );
                $oppositeUseridsWithRoles = [
                    'oppositeUserids' => $oppositeUseridsWithRoles,
                    'skipJoinRequestCapacityCheck'=>true
                ];
                $group = Group::GetGroup($groupid);
                [$status, $matchingStats]  = Team::GetTeamMembersSuggestionsForRequestRoles($group, $receiverid, $receiver_role_id, $oppositeUseridsWithRoles);
                $matchedUser = array();
                $matchParams = [];
                if (empty($matchingStats) || empty($matchingStats[0]['suggestions'][0])) {
                    $senderRequestDetail = Team::GetRequestDetail($groupid, $sender_role_id, $senderid);
                    $sender = User::GetUser($senderid);
                    $matchedUser['firstname'] = $sender->val('firstname');
                    $matchedUser['lastname'] = $sender->val('lastname');
                    $matchedUser['picture'] = $sender->val('picture');
                    $matchedUser['email'] = $sender->val('email');
                    $matchedUser['jobtitle'] = $sender->val('jobtitle');
                    $matchedUser['department'] = $sender->getDepartmentName();
                    $roleName = $senderRequestDetail['type']??''; 
                    $matchingPercentage  = 0;
                    $matchParams[] = [
                        'title' => 'Matched based on role request', 
                        'value' =>'', 
                        'percentage' => '0%',
                        'showPercentage' => 'show',
                        'showValue' => 'hide',
                    ];

                } else {
                    $matchedUser = $matchingStats[0]['suggestions'][0];
                    $roleName = $matchingStats[0]['oppositRolesType']; 
                    $matchingPercentage = $matchedUser['matchingPercentage'];
                    $parameterWiseMatchingPercentage = $matchedUser['parameterWiseMatchingPercentage'];
                    foreach($parameterWiseMatchingPercentage as $k =>$v){
                            $showPercentage = 'show';
                            $showValue = 'hide';
                            if ($v['attributeType']){
                                $showPercentage = $group->getTeamMatchingAttributeKeyVisibilitySetting($v['attributeType'],$k,Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_matchp_users']);
                                $showValue = $group->getTeamMatchingAttributeKeyVisibilitySetting($v['attributeType'],$k,Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_value_users']);
                            }
                        $matchParams[] = [
                        'title' => $v['title'], 
                        'value' => (string) $v['value'], 
                        'percentage' => $v['percentage'].'%',
                        'showPercentage' => $showPercentage,
                        'showValue' => $showValue,
                        ];
                    }
                }
                $inviteMatchDetail = [
                    'Name' => $matchedUser['firstname']." ".$matchedUser['lastname'],
                    'email' => $matchedUser['email'],
                    'jobtitle' => $matchedUser['jobtitle'],
                    'department' => $matchedUser['department'],
                    'rolename' => $roleName,
                    'matchpercentage' => $matchingPercentage,
                    'matchParams' => $matchParams
                    ];
                
                exit(self::buildApiResponseAsJson($method, $inviteMatchDetail, 1, gettext('View match details'), 200));
                
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext("The request does not exist anymore."), 200));
            }
        }
    }


    public function addTeamContentComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "addTeamContentComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid'],'contentid' => @$get['contentid'],'comment' => @$get['comment']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $teamid = $get['teamid'];
            $team = Team::GetTeam($teamid);
            if ($team){ 
                $contentid = (int)$get['contentid'];
                if (
                    ($content = $team->getTodoDetail($contentid)) == null ||
                    $content['teamid'] != $teamid
                ) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
                $comment = $get['comment'];
                $commentid = 0;
                if (isset($get['commentid'])) {
                    $commentid = $get['commentid'];
                }

                $media = array();
                if (!empty($_FILES['media']['name'])){
                    $media = $_FILES;
                }

                if ($commentid > 0) {
                    // Sub Comment
                    if (Comment::CreateComment_2($commentid, $comment,  $media)) {
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added'), 200));
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again.'), 200));
                    }
                } else {

                    if (TeamTask::CreateComment_2($contentid, $comment,  $media)) {
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added'), 200));
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again.'), 200));
                    }
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
            }
        }
    }

    public function updateTeamContentComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "updateTeamContentComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('commentid' => @$get['commentid'], 'comment' => @$get['comment']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $commentid = $get['commentid'];
            $comment = $get['comment'];
            $checkComment = Comment::GetCommentDetail($commentid);

            if ($checkComment && $checkComment['userid'] == $_USER->id()) {
                Comment::UpdateComment_2($checkComment['topicid'], $commentid,  $comment);
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment updated successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Comment not found'), 200));
            }
        }
    }

    public function deleteTeamContentComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteTeamContentComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid'],'commentid' => @$get['commentid'], 'contentid' => @$get['contentid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $teamid = $get['teamid'];
            $team = Team::GetTeam($teamid);
            if ($team){ 
                $contentid = $get['contentid'];
                if (
                    ($content = $team->getTodoDetail($contentid)) == null ||
                    $content['teamid'] != $teamid
                ) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
                $commentid = $get['commentid'];
                $check = Comment::GetCommentDetail($commentid);
                if ($check && ($_USER->isAdmin() || $check['userid'] == $_USER->id())) {
                    if ($check['topictype']== 'CMT'){
                        Comment::DeleteComment_2($check['topicid'], $commentid);
                    } else {
                        TeamTask::DeleteComment_2($contentid, $commentid);
                    }
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment deleted successfully'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Comment not found'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name']), 200));
            }
        }
    }

    public function getDisclaimerHooks($get){
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getDisclaimerHooks";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        exit(self::buildApiResponseAsJson($method, Disclaimer::DISCLAIMER_HOOK_TRIGGERS, 1, gettext('Available hooks'), 200));
            
    }

    public function getDisclaimerByHook($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getDisclaimerByHook";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('disclaimerHook' => @$get['disclaimerHook']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $disclaimerHook = $get['disclaimerHook'];
            $consentContextId = isset($get['consentContextId']) ? $get['consentContextId'] : 0;

            if (in_array($disclaimerHook, Disclaimer::DISCLAIMER_HOOK_TRIGGERS)) {

                // @Todo if disclaimer is of type GROUP_JOIN or LEAVE, then we need to pass the groupid
                if (Disclaimer::IsDisclaimerAvailable($disclaimerHook, $consentContextId)){
                    $disclaimer = Disclaimer::GetDisclaimerByHook($disclaimerHook);

                    if (!empty($disclaimer)){
                        $disclaimer_language = $_USER->val('language');
                        $disclaimerMessage =  $disclaimer->getDisclaimerBlockForLanguage($disclaimer_language);
                        if (!empty($disclaimerMessage)){
                            $disclaimer_language = $disclaimerMessage['language'];
                        }
                        
                        if (!empty($disclaimerMessage)){
                            $disclaimerMessage['disclaimerid'] = (int)$disclaimer->val('disclaimerid');
                            $disclaimerMessage['consentLanguage'] = $disclaimer_language;
                            $disclaimerMessage['consent_required'] = $disclaimer->val('consent_required');
                            $disclaimerMessage['consent_type'] = $disclaimer->val('consent_type')??'';
                            exit(self::buildApiResponseAsJson($method, $disclaimerMessage, 1, gettext('Available disclaimer'), 200));
                        }
                    }
                }
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('No disclaimer available'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Invalid disclaimer hook provided'), 200));
            } 
        }
    }

    public function saveUserConsent($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "saveUserConsent";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('disclaimerid' => @$get['disclaimerid'],'consentText' => @$get['consentText'],'consentLanguage' => @$get['consentLanguage']);
        $checkRequired = $db->checkRequired($check);
       
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $disclaimerid = $get['disclaimerid'];
            $disclaimer = Disclaimer::GetDisclaimerById($disclaimerid);
            $consentContextId = isset($get['consentContextId']) ? $get['consentContextId'] : 0;

            if ($disclaimer){
                $consentText = $get['consentText'];
                $consentLanguage = $get['consentLanguage'];

                if ($disclaimer->saveUserConsent($consentText, $consentLanguage, $consentContextId)){
                   exit(self::buildApiResponseAsJson($method, '', 1, gettext('Consent saved successfully'), 200));
                }
            }
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('No disclaimer available'), 200));
            
        }
    }


     /**
     * @param $get
     * @param $this
     */
    public function addEventComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "addEventComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('eventid' => @$get['eventid'], 'comment' => @$get['comment']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            $eventid = $get['eventid'];
            $comment = $get['comment'];
            $event = Event::GetEvent($eventid);
            $commentid = 0;
            if (isset($get['commentid'])) {
                $commentid = $get['commentid'];
            }
            $media = array();
            if (!empty($_FILES['media']['name'])){
                $media = $_FILES;
            }
            if ($event) {
                if ($commentid > 0) {
                    // Sub Comment
                    if (Comment::CreateComment_2($commentid, $comment,  $media)) {
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added'), 200));
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again.'), 200));
                    }
                } else {

                    if (Event::CreateComment_2($eventid, $comment,  $media)) {
                        exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added'), 200));
                    } else {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again.'), 200));
                    }
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this event has been removed.'), 200));
            }

        }
    }

    public function updateEventComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "updateEventComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('commentid' => @$get['commentid'], 'eventid' => @$get['eventid'], 'comment' => @$get['comment']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $eventid = $get['eventid'];
            if (Event::GetEvent($eventid)){
                $commentid = $get['commentid'];
                $comment = $get['comment'];
                $check = Comment::GetCommentDetail($commentid);

                if ($check && ($_USER->isAdmin() || $check['userid'] == $_USER->id())) {
                    Comment::UpdateComment_2($check['topicid'], $commentid,  $comment);
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment updated successfully'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Comment not found'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this event has been removed.'), 200));
            }
        }
    }

    public function likeOrDislikeEvent($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "likeOrDislikeEvent";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('eventid' => @$get['eventid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $eventid = $get['eventid'];
            $event = Event::GetEvent($eventid);
            if ($event) {
                $reactiontype = $get['reactiontype'] ?? 'like';
                Event::LikeUnlike($eventid, $reactiontype);
                $myLikeType = Event::GetUserReactionType($eventid);
                $myLikeStatus = (int) !empty($myLikeType);
                exit(self::buildApiResponseAsJson($method, array(
                    'likeStatus' => ($myLikeStatus ? '1' : '2'),
                    'myLikeType' => $myLikeType,
                ), 1, gettext('Updated successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('It seems this event has been removed.'), 200));
            }
        }
    }

    public function deleteEventComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteEventComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('commentid' => @$get['commentid'], 'eventid' => @$get['eventid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $eventid = $get['eventid'];
            $commentid = $get['commentid'];
            $check = Comment::GetCommentDetail($commentid);
            if ($check && ($_USER->isAdmin() || $check['userid'] == $_USER->id())) {
                if ($check['topictype']== 'CMT'){
                    Comment::DeleteComment_2($check['topicid'], $commentid);
                 } else {
                    Event::DeleteComment_2($eventid, $commentid);   
                 }
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment deleted successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Comment not found'), 200));
            }
        }
    }

    public function getEventRecordingLink($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getEventRecordingLink";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('eventid' => @$get['eventid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (($eventid = $get['eventid'])<1 ||
                ($event = Event::GetEvent($eventid)) === NULL
            ){
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Event not found'), 200));
            }

            if (!$event->val('event_recording_link')) {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('No recording link found for this event yet. Please check later.'), 200));
            }
          
             $event_recording_link =  "";
        if ($event->isPublished() && $event->hasEnded()){ // For past events
                $event_recording_link = $event->val('event_recording_link');
                $event->logEventRecordingLinkClick();
        }
           
            exit(self::buildApiResponseAsJson($method, array('event_recording_link'=>$event_recording_link), 1, gettext('Event recording link.'), 200));
        }
    }

    
    public function confirmEventRecordingAttendance($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "confirmEventRecordingAttendance";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('eventid' => @$get['eventid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (($eventid = $get['eventid'])<1 ||
                ($event = Event::GetEvent($eventid)) === NULL
            ){
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Event not found'), 200));
            }

            $link_stats = $event->getEventRecordingLinkClickStatsForUser($_USER->id());

            // Allow attendance to be marked after 30 minutes of first click.
            if (empty($link_stats['first_clicked_at']) || strtotime($link_stats['first_clicked_at']. ' UTC') > time() - 1800) {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('You can mark your attendance 30 minutes after watching the recording'), 200));
            }
        
            if ($event->checkInByUserid($_USER->id(), Event::EVENT_CHECKIN_METHOD['VIEWED_RECORDING'])) {
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Successfully marked attendance'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Unable to mark your attendance at this time, please try again later'), 200));
            }
        }
    }


    private function manageTeamJoinRoles(int  $groupid)
    {
        Global $_COMPANY, $_ZONE,$_USER;
        $group = Group::GetGroup($groupid);
        $requestedRoleIds = array_column(Team::GetUserJoinRequests($groupid,0,0),'roleid');
        $myTeams = Team::GetMyTeams($groupid);
        $allRoles = Team::GetProgramTeamRoles($groupid, 1);
        
        $roles = array();

        foreach($allRoles as $row){
            if (($row['sys_team_role_type'] !=2 && $row['sys_team_role_type'] !=3) || $row['hide_on_request_to_join'] ){
                continue;
            }
            $isRequestAllowd = true;
            $guardRails = json_decode($row['restrictions'],true);

            if (!empty($guardRails)){
                $isRequestAllowd = Team::CheckRequestRoleJoinAllowed($_USER->id(), $guardRails);
            }

            if ($isRequestAllowd){
                $disable_registration_and_pauserequest_btn = false;
                $cancel_registration_confirmation_text = '';
                $pause_registration_confirmation_text = '';
                $resume_registration_confirmation_text = '';
                $isRequestPaused = false;
                $baseUrl = $_COMPANY->getAdminURL().'/native/';
                if(in_array($row['roleid'],$requestedRoleIds)){  // User requested to join a team
                    
                    $requestDetail = Team::GetRequestDetail($groupid,$row['roleid']);
                    $cancelAllowed = true;
                    // if ($requestDetail){
                    //     if ($requestDetail['isactive'] == 2){ // No needs of this check now 
                    //         $cancelAllowed = false;
                    //     }
                    // }
                    $disable_registration_and_pauserequest_btn =  (int) $requestDetail['isactive'] === 0 ? true : false;

                    $requestBtnLabel = gettext("Update Registration");
                    $cancelBtnLabel = gettext("Cancel Registration");
                    $cancel_registration_confirmation_text = sprintf(gettext('Are you sure you want to cancel your registration? You can still access your existing %1$s in \'My %1$s,\'. However your profile won\'t be shown in new matches until you register again'), Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));
                    $pauseBtnLabel = "";
                    if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['NETWORKING']) {
                        
                        if($requestDetail['isactive'] == 1) {
                            $pauseBtnLabel = gettext("Pause Registration");
                            $pause_registration_confirmation_text = gettext("Pause registration? You'll be hidden from searches, but can resume anytime.");
                        } else {
                            $pauseBtnLabel = gettext("Resume Registration");
                            $resume_registration_confirmation_text = gettext("Ready to be seen again? Resume your registration to get matched"); 
                        }
                        $isRequestPaused = ($requestDetail['isactive'] == 1);
                        
                    }
                    $infoMessage = "";
                    $requestLink =  $baseUrl.'sendUdateTeamRoleJoinRequest.php?groupid='.$_COMPANY->encodeId($groupid).'&roleid='.$_COMPANY->encodeId($row['roleid']).'&preselected='.$_COMPANY->encodeId($row['roleid']);
                } else{
                    $infoMessage = "";
                    $requestAllowed = true;

                    if(in_array($group->getTeamProgramType(),[Team::TEAM_PROGRAM_TYPE['PEER_2_PEER'],Team::TEAM_PROGRAM_TYPE['ADMIN_LED']]) && !Team::IsTeamRoleRequestAllowed($groupid,$row['roleid'])){
                        $requestAllowed = false;
                        $infoMessage = sprintf(gettext('Registration Closed - Maximum Registrations Reached'));
                    } elseif (!empty($requestedRoleIds) && $group->getTeamJoinRequestSetting()!=1){
                        $requestAllowed = false;
                        $infoMessage = sprintf(gettext('You cannot register for this role because %1$s settings only allow users to register for only one role at a time'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
                    } elseif (in_array($row['roleid'], array_column($myTeams,'roleid'))) {   
                        $infoMessage = sprintf(gettext('You\'re already assigned to a %1$s for this role. But you can still register to join other %1$s for future assignments.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
                    }
                    
                    $requestBtnLabel = gettext("Register");
                    $cancelBtnLabel = "";
                    $pauseBtnLabel = "";
                    $requestLink = $requestAllowed ? $baseUrl.'sendUdateTeamRoleJoinRequest.php?groupid='.$_COMPANY->encodeId($groupid).'&roleid='.$_COMPANY->encodeId(0).'&preselected='.$_COMPANY->encodeId($row['roleid']) : '';
                    $cancelAllowed = false;
                }

                $preSelectedRole = Team::GetTeamRoleType($row['roleid']);
                if (!empty($preSelectedRole['registration_start_date']) && $preSelectedRole['registration_start_date'] > date('Y-m-d')){
                    $infoMessage = sprintf(gettext('Registration is currently unavailable. Please check back after %s'),$preSelectedRole['registration_start_date'] );
                }
                if (!empty($preSelectedRole['registration_end_date']) && $preSelectedRole['registration_end_date'] < date('Y-m-d')){
                    $requestAllowed = false;
                    $requestLink = '';
                    $infoMessage =  gettext('Registration is now closed, and we are no longer accepting new requests for this role.');
                }

                $role = array(
                    'roleid' => $row['roleid'],
                    'role' => $row['type'],
                    'request_button_label' => $requestBtnLabel,
                    'cancel_button_label' => $cancelBtnLabel,
                    'pause_button_label' => $pauseBtnLabel,
                    'request_link' => $requestLink,
                    'cancel_allowed' =>$cancelAllowed,
                    'already_assigned_message' => $infoMessage,
                    'is_request_paused' =>$isRequestPaused,
                    'cancel_registration_confirmation_text' => $cancel_registration_confirmation_text,
                    'pause_registration_confirmation_text'=>$pause_registration_confirmation_text,
                    'resume_registration_confirmation_text' => $resume_registration_confirmation_text,
                    'disable_registration_and_pauserequest_btn' => $disable_registration_and_pauserequest_btn
                );

                $roles[] = $role;
            }
        }

        return $roles;
    }


    public function togglePauseTeamJoinRequest($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "togglePauseTeamJoinRequest";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'],'roleid'=>@$get['roleid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            $groupid = $get['groupid'];
            $roleid = $get['roleid'];

            if (Team::TogglePauseTeamJoinRequest($groupid, $roleid, $_USER->id())) {
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Request updated successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong while updating your request. Please try again.'), 200));
            }
        }
    }

    function discoverCircles($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "discoverCircles";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }
            $groupid = $get['groupid'];
            $group = Group::GetGroup($groupid);
            if ($group){

                $filter_attribute_keyword = $get['filter_attribute_keyword'] ?? array();
                if (!is_array($filter_attribute_keyword )) {
                    $filter_attribute_keyword  = array($filter_attribute_keyword );
                }
                $filter_primary_attribute = $get['filter_primary_attribute'] ?? array();
                if (!is_array($filter_primary_attribute )) {
                    $filter_primary_attribute  = array($filter_primary_attribute );
                }
                $name_keyword = trim($get['name_keyword'] ?? '');

                $page = (int) ($get['page'] ?? 1);
                $per_page = 10;

                // if ($page === 1) {
                //     $joinRequests = Team::GetMyJoinRequests($groupid);
                //     if (empty($joinRequests)) {
                //         exit(self::buildApiResponseAsJson($method, '', 0, gettext('To Discover your matches, please Register for a role'), 200));
                //     }
                // }
                $enable_search_circles = (
                    $_COMPANY->getAppCustomization()['teams']['search']
                    && $group->getTeamProgramType() === Team::TEAM_PROGRAM_TYPE['CIRCLES']
                );
                if($enable_search_circles){
                    $search_str = '';
                    $hashtag_ids = [];
                    $search_filters = $get['search_filters'] ?? '';
                    if ($search_filters) {
                        $search_str = trim($search_filters ?? '');
                    }
                }

                $allRoles = Team::GetProgramTeamRoles($groupid, 1);
                $showAvailablesOnly = (int) ($get['showAvailablesOnly'] ??  $group->getProgramDiscoverSearchAttributes()['default_for_show_only_with_available_capacity']);
                $filter_attribute_type = explode(',', $get['filter_attribute_type']??'');
                [$availabeTeams, $showmore, $total_count] = Team::DiscoverAvailableCircles($groupid,$_USER->id(), $page, $per_page, $filter_attribute_keyword, $filter_primary_attribute, $name_keyword, $showAvailablesOnly, $search_str, $hashtag_ids, $filter_attribute_type);

                if ($page == 1){
                    if (empty($availabeTeams) && ($filter_attribute_keyword || $filter_primary_attribute || $name_keyword)) {
                        exit(self::buildApiResponseAsJson($method, '', 0, gettext('Your search criteria did not return any matching result. Please change your search criteria and try again.'), 200));
                    }
                }


                $availableCircles = array();
                $availableCircles['globalSearch'] = $enable_search_circles ? true: false;

                $dynamicCircleName = Team::GetTeamCustomMetaName($group->getTeamProgramType());
                foreach($availabeTeams as $team){
                
                    $row = array();
                    $row['teamid'] = $team->val('teamid');
                    $row['groupid'] = $team->val('groupid');
                    $row['chapterid'] = $team->val('chapterid');
                    $row['team_name'] = $team->val('team_name');
                    $row['team_meta_name'] = $team->val('team_meta_name');
                    $row['hashtags'] =  $team->val('handleids') ? HashtagHandle::GetAllHashTagHandles($team->val('handleids')) : array();
                    global $post_css;
                    $row['team_description'] = $team->val('team_description') ? $post_css . '<div class="post-inner">' . $team->val('team_description') . '</div>' : '';
                    
                    $circleRolesCapacity = Team::GetCircleRolesCapacity($groupid,$team->val('teamid'));
                    $roleAndMembers = array();
                    $isTeamMember = $team->isTeamMember($_USER->id());
                    foreach($allRoles as $role){
                        $m = array();
                        $members = $team->getTeamMembers($role['roleid']);
                        $isRoleJoined = $team->isTeamMember($_USER->id(), $role['roleid']);
                        $canJoinRole = Team::CanJoinARoleInTeam($groupid,$_USER->id(),$role['roleid']);
                        $totalmembers = count($members);
                        $allowedRoleCapacity = '';
                        $showAvailableCapacity = '';
                        $availableSpotsMessage = "";
                       
                        if ($role['sys_team_role_type'] != 2) {
                            $availableRoleCapacity = ($circleRolesCapacity[$role['roleid']]['circle_role_max_capacity'] - $totalmembers);
                            if ($availableRoleCapacity == 0 ){
                                $availableSpotsMessage = sprintf(gettext('%s spots available!'), $availableRoleCapacity);
                            } elseif($availableRoleCapacity ==1 ){
                                $availableSpotsMessage = sprintf(gettext('%s spot available!'), $availableRoleCapacity);
                            } else {
                                $availableSpotsMessage = sprintf(gettext('%s spots available!'), $availableRoleCapacity);
                            }
                        }  

                        foreach($members as $member){
                            $m[] = array(
                                'team_memberid'=>$member['team_memberid'],
                                'role'=>$member['role'],
                                'roletitle' => $member['roletitle'],
                                'firstname'=>$member['firstname'],
                                'lastname'=>$member['lastname'],
                                'email'=>$member['email'],
                                'picture'=>$member['picture'],
                                'jobtitle'=>$member['jobtitle']
                            );
                        }

                        $showJoinButton = false;
                        $disableJoinButton = true;
                        $joinButtonLabel = '';
                        $joinButtonHelpText = '';
                        $showMember = false;

                        if($role['sys_team_role_type'] == 2 && !empty($members)){
                            $showMember = true;
                        } else {
                            if($isRoleJoined ){
                                $joinButtonLabel = gettext('You Joined');
                            } elseif ($isTeamMember){ 
                                $showJoinButton = true;
                                $joinButtonLabel =  sprintf(gettext("Join %s"),$dynamicCircleName);
                                $joinButtonHelpText = sprintf(gettext("You are already part of this %s"),$dynamicCircleName);
                            } elseif (!$canJoinRole){ 
                                $showJoinButton = true;
                                $joinButtonLabel =  sprintf(gettext("Join %s"),$dynamicCircleName);
                                $joinButtonHelpText = sprintf(gettext("You have reached the maximum number of %s you can participate in with this role."),$dynamicCircleName);
                            } elseif($availableRoleCapacity>0) {

                                $isRequestAllowd  = true;
                                $guardRails = json_decode($role['restrictions'],true);
                                if (!empty($guardRails)){
                                    $isRequestAllowd = Team::CheckRequestRoleJoinAllowed($_USER->id(), $guardRails);
                                }
                                if(!empty($role['registration_end_date']) && $role['registration_end_date'] < date('Y-m-d')){ 
                                    $showJoinButton = true;
                                    $disableJoinButton = true;
                                    $joinButtonLabel = sprintf(gettext("Join %s"),$dynamicCircleName);
                                    $joinButtonHelpText = gettext("Registration is now closed, and we are no longer accepting new requests for this role.");
                                }elseif ($isRequestAllowd){
                                    $showJoinButton = true;
                                    $disableJoinButton = false;
                                    $joinButtonLabel = sprintf(gettext("Join %s"),$dynamicCircleName);
                                    $joinButtonHelpText = sprintf(gettext("Are you sure you want to join this %s?"),$dynamicCircleName);
                                } else {
                                    $showJoinButton = true;
                                    $disableJoinButton = true;
                                    $joinButtonLabel = sprintf(gettext("Join %s"),$dynamicCircleName);
                                    $joinButtonHelpText = gettext("You do not meet the role registration restrictions criteria");
                                }
                            } else {
                                $showJoinButton = true;
                                $disableJoinButton = true;
                                $joinButtonLabel = sprintf(gettext("Join %s"),$dynamicCircleName);
                                $joinButtonHelpText = gettext("All roles are filled");
                            }
                        }
                        $roleAndMembers[] = array(
                            'roleid' => $role['roleid'],
                            'role_type' => $role['type'],
                            'members' => $m,
                            'allowedRoleCapacity' => $allowedRoleCapacity,
                            'showAvailableCapacity' => $showAvailableCapacity,
                            'showJoinButton' => $showJoinButton,
                            'disableJoinButton' => $disableJoinButton,
                            'joinButtonLabel' => $joinButtonLabel,
                            'joinButtonHelpText' => $joinButtonHelpText,
                            'showMember' => $showMember,
                            'availableSpotsMessage' => $availableSpotsMessage
                        );
                    }
                    $row['team_role_and_members'] = $roleAndMembers;
                    $availableCircles['circles_list'][] = $row;
                }
               
                if (!empty($availableCircles)) {
                    exit(self::buildApiResponseAsJson($method, $availableCircles, 1, gettext('Available circles list'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('No circles are avaiable for now. Stay tuned'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['group']['name-short']), 200));
            }
        }
    }

    public function joinCircle($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "joinCircle";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid'],'roleid'=>@$get['roleid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }

            $teamid = $get['teamid'];
            $roleid = $get['roleid'];

            if (
                ($team = Team::GetTeam($teamid)) === null ||
                ($teamRole = Team::GetTeamRoleType($roleid)) === NULL
            ) {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong while updating your request. Please try again.'), 200));
            }
        
            if ($team->isTeamMember($_USER->id())){
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('You are already a member'), 200));
            }
        
            if (!$team->isAllowedNewTeamMemberOnRole($roleid)) {
               
                exit(self::buildApiResponseAsJson($method, '', 0,gettext('Maximum allowed members limit reached for selected role!'), 200));
            }
        
            if (!Team::CanJoinARoleInTeam($team->val('groupid'), $_USER->id(), $roleid)){
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('You cannot join this role because the maximum requested capacity for this role has been reached.'), 200));
            }

            $group = Group::GetGroup($team->val('groupid'));

            if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] ) {
                // Init silent registration process of mentee sys role type
                $join_request_status = Team::CreateAutomaticJoinRequest($group, $_USER->id(), $roleid);
                if (!$join_request_status['status']) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Unable to update Registration.'), 200));
                }
            }
        
            if($team->addUpdateTeamMember($roleid, $_USER->id())){
                exit(self::buildApiResponseAsJson($method, '', 1, gettext("You have successfully joined this circle."), 200));
            }
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong while updating your request. Please try again.'), 200));

        }
    }

    public function leaveCircleMembership($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "leaveCircleMembership";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid'],'memberid'=>@$get['memberid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }

            $teamid = $get['teamid'];
            $memberid = $get['memberid'];

            if (
                ($team = Team::GetTeam($teamid)) === null ||
                ($group = Group::GetGroup($team->val('groupid'))) === null ||
                ($member = $team->getTeamMemberById($memberid )) === null
            ) {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong while updating your request. Please try again.'), 200));
            }

            if ($team->deleteTeamMember($memberid)){

                $mentor = $team->getTeamMembersBasedOnSysRoleid(2);
                if (!empty($mentor) && $member['sys_team_role_type'] != 2){ // Send Email Notification to Mentor
                    $team->sendLeaveCircleNotificationToMentor($group,$mentor[0]);  
                }
                
                if ($team->isCircleCreator()){
                    exit(self::buildApiResponseAsJson($method, '', 1, sprintf(gettext("Member removed from this %s"), Team::GetTeamCustomMetaName($group->getTeamProgramType())), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 1,sprintf(gettext("%s left successfully"), Team::GetTeamCustomMetaName($group->getTeamProgramType())), 200));
                }
            }
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong while updating your request. Please try again.'), 200));
        }
    }

    public function getTeamMessages($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getTeamMessages";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {

            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }

            $teamid = $get['teamid'];
            $page = $get['page']??1;
            $start = ($page - 1) * 100;
            $end = 100;

            if (
                ($team = Team::GetTeam($teamid)) === null 
            ) {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name-short']), 200));
            }
            $data = array();
            $data['totalComments'] = (int)Team::GetCommentsTotal($team->id()); // Should be int
            $data['comments'] = $this->getCleanComments('Team',$team->id(),$start,$end);

            exit(self::buildApiResponseAsJson($method, $data, 1, gettext("Messages"), 200));
        }
    }


    public function addNewTeamMessage($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "addNewTeamMessage";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('teamid' => @$get['teamid'],'comment' => @$get['comment']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            if (isset($get['zoneid'])){
                $zoneid = $get['zoneid'];
                $_ZONE = $_COMPANY->getZone($zoneid);
                if (!$_ZONE) {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
                }
            }

            $teamid = $get['teamid'];
            $comment = $get['comment'];
            if (
                ($team = Team::GetTeam($teamid)) === null 
            ) {
                exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('%s not found'),$_COMPANY->getAppCustomization()['teams']['name-short']), 200));
            }

            $commentid = 0;
            if (isset($get['commentid'])) {
                $commentid = $get['commentid'];
            }
            $media = array();
            if (!empty($_FILES['media']['name'])){
                $media = $_FILES;
            }
               
            if ($commentid > 0) {
                // Sub Comment
                if (Comment::CreateComment_2($commentid, $comment,  $media)) {
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again.'), 200));
                }
            } else {

                if (Team::CreateComment_2($teamid, $comment,  $media)) {
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment added'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong! Please try again.'), 200));
                }
            }
        }
    }

    public function updateTeamComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "updateTeamComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('commentid' => @$get['commentid'], 'comment' => @$get['comment']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $commentid = $get['commentid'];
            $comment = $get['comment'];
            $checkComment = Comment::GetCommentDetail($commentid);
            
            if ($checkComment && ($_USER->isAdmin() || $checkComment['userid'] == $_USER->id())) {
                Comment::UpdateComment_2($checkComment['topicid'], $commentid,  $comment);
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment updated successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Comment not found'), 200));
            }
        }
    }

    public function deleteTeamComment($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteTeamComment";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('commentid' => @$get['commentid'], 'teamid' => @$get['teamid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $teamid = $get['teamid'];
            $commentid = $get['commentid'];
            $isAdmin = $_USER->isAdmin();
            $check = Comment::GetCommentDetail($commentid);
            if ($check && ($_USER->isAdmin() || $check['userid'] == $_USER->id())) {
                if ($check['topictype']== 'CMT'){
                    Comment::DeleteComment_2($check['topicid'], $commentid);
                 } else {
                    Team::DeleteComment_2($teamid, $commentid);
                 }
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Comment deleted successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Comment not found'), 200));
            }
        }
    }

    public function getFilterAttributeKeywordsForP2PandCircle($get)
    {
        global $_COMPANY, $_USER, $_ZONE;

        $method = "getFilterAttributeKeywordsForP2PandCircle";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
       
        $db = new Hems();
        $check = array('groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $group = Group::GetGroup($groupid);
            if ($group){ 

                $primary_parameters  = array_flip(array_values(UserCatalog::GetAllCatalogCategories()));
                
                $searchAttributes =  $group->getProgramDiscoverSearchAttributes();
                $finalAttributes = [
                    'primary' => [],
                    'custom' => [],
                ];

                foreach($primary_parameters as $parameter => $value) {
                    if(in_array($parameter, $searchAttributes['primary'])){
                        $finalAttributes['primary'][$parameter] = $parameter;
                    }
                }
                foreach ($searchAttributes['custom'] as $key) {
                    $question = $group->getTeamCustomAttributesQuestion($key);
                    if (empty($question)) {
                        continue;
                    }
                    $newKey = $question['title'] ?? $key;
                    $finalAttributes['custom'] = array_merge($finalAttributes['custom'],array($newKey=>$key));
                }

                if (!empty($finalAttributes)){
                    exit(self::buildApiResponseAsJson($method, $finalAttributes, 1, gettext('Available primary attributes fetched successfully'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('No primary attributes are available'), 200));
                }
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Access denied'), 200));
            }
        }
    }

    public function getfilterPrimaryAttributeByKeyword($get)
    {
        global $_COMPANY, $_USER, $_ZONE;

        $method = "getfilterPrimaryAttributeByKeyword";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('primaryAttributeKey' => @$get['primaryAttributeKey'], 'groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $primaryAttributeKey = $get['primaryAttributeKey'];
            $primaryAttributeValues =  UserCatalog::GetAllCategoryKeys($primaryAttributeKey);
            $primaryAttributeValues = array_map(function($value){
                return  ['value' => $value, 'text' => $value];
            }, $primaryAttributeValues);
            // For custom question, let's check if above is empty. 
            if(empty($primaryAttributeValues)){
                $groupid = $get['groupid'];
                $group = Group::GetGroup($groupid);
                if($group){
                    $questionData = $group->getTeamCustomAttributesQuestion($primaryAttributeKey);
                    if($questionData['type'] === 'rating'){
                        if(isset($questionData['rateValues']) && is_array($questionData['rateValues'])){
                            $primaryAttributeValues = $questionData['rateValues'];
                        } else{
                            $rateCount = $questionData['rateCount'] ?? 5;
                            $primaryAttributeValues = array_map(function($i){
                                return ['value' => $i   , 'text' => $i];
                            }, range(1, $rateCount));
                        }
                    }else{
                        $choices = $questionData['choices'] ?? [];
                        $primaryAttributeValues = array_map(function($value){
                            return is_array($value) ? $value : ['value' => $value, 'text' => $value];
                        }, $choices);
                    }
                }
            }
            exit(self::buildApiResponseAsJson($method, $primaryAttributeValues, 1, gettext('Primary attribute values fetched successfully'), 200));
        }
    }
    public function inviteUserForTeamByRole($get)
    {

        global $_COMPANY, $_USER, $_ZONE;

        $method = "inviteUserForTeamByRole";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'],'receiver_id' => @$get['receiver_id'],'receiver_roleid' => @$get['receiver_roleid'],'sender_roleid' => @$get['sender_roleid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $receiver_id = $get['receiver_id'];
            $receiver_roleid = $get['receiver_roleid'];
            $sender_roleid = $get['sender_roleid'];
            $group = Group::GetGroup($groupid);
            $receiverRole = Team::GetTeamRoleType($receiver_roleid);
            if ($group  && $receiverRole ) {
                if (!empty($receiverRole['registration_start_date']) && $receiverRole['registration_start_date'] > date('Y-m-d')){
                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('Registration is currently unavailable. Please check back after %s'),$receiverRole['registration_start_date'] ), 200));
                }
                if (!empty($receiverRole['registration_end_date']) && $receiverRole['registration_end_date'] < date('Y-m-d')){
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('Registration is now closed for this role.'), 200));
                }

                if ($group->getTeamJoinRequestSetting()!=1){ // If setting is not allowing multiple role requests
                    $requestedRoleIds = array_column(Team::GetUserJoinRequests($groupid,$receiver_id,0),'roleid');
                    if (count($requestedRoleIds) && !in_array($receiverRole['roleid'],$requestedRoleIds)){ //if user have already requests and invited role is not in existing requests
                        exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext('Users can only have one %1$s role in this %2$s. This role is unavailable'), Team::GetTeamCustomMetaName($group->getTeamProgramType()), $_COMPANY->getAppCustomization()['group']['name-short']), 200));
                    }
                }
            
                if (!Team::CanJoinARoleInTeam($groupid, $receiver_id, $receiver_roleid)) {
                    exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("You've reached the limit for %s with this role."),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1)), 200));
                }

                list(
                    $roleSetCapacity,
                    $roleUsedCapacity,
                    $roleRequestBuffer,
                    $roleAvailableCapacity,
                    $roleAvailableRequestCapacity,
                    $roleAvailableBufferedRequestCapacity,
                    $pendingSentOrReceivedRequestCount
                ) = Team::GetRoleCapacityValues($groupid, $receiver_roleid, $receiver_id);

                $roleRequest = Team::GetRequestDetail($groupid, $receiver_roleid, $receiver_id);
                if ($roleRequest && $roleSetCapacity!=0 && $roleAvailableRequestCapacity < 1){ // Check available request capacity only if user already requested for this role
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('User cannot join more programs with this role.'), 200));
                }
            
                $sendRequest = Team::SendRequestToJoinTeam($groupid, $receiver_id, $receiver_roleid, $sender_roleid, '', '');
            
                if ($sendRequest) {
                    exit(self::buildApiResponseAsJson($method, '', 1, gettext('Invite sent!'), 200));
                } 
            } 
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong. Please try again.'), 200));
        }
    }

    public function searchUsertoInviteForTeam($get)
    {
        global $_COMPANY, $_USER, $_ZONE;

        $method = "searchUsertoInviteForTeam";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'],'roleid'=> @$get['roleid'], 'keyword' => @$get['keyword']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            
            $groupid = $get['groupid'];
            $roleid = $get['roleid'];
            $group = Group::GetGroup($groupid);
            $keyword = raw2clean($get['keyword']);
            if ($group && $keyword && $roleid ){
            
                $excludeCondition = " users.userid!={$_USER->id()}";
                $searchAllUsersConditon = "";
                $activeusers = User::SearchUsersByKeyword($keyword,$searchAllUsersConditon ,$excludeCondition);

                if (count($activeusers) > 0) {
                    exit(self::buildApiResponseAsJson($method, $activeusers, 1, gettext('Users found!'), 200));
                } else {
                    exit(self::buildApiResponseAsJson($method, '', 0, gettext('No users were found matching the keyword you searched'), 200));
                }
            } 
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong. Please try again.'), 200));
        }
    }


    public function getTeamInvites($get) 
    {
        global $_COMPANY, $_USER, $_ZONE;

        $method = "getTeamInvites";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $invitedLists = Team::GetTeamInvites($groupid, $_USER->id());
            if (!empty($invitedLists)){
                $finalInvites = array();
                foreach($invitedLists as $item) {
                    $row = $item;
                    $invitedUser = User::GetUser($item['receiverid']) ?? User::GetEmptyUser();
                    $row['receiver_name'] = $invitedUser->getFullName() ?: 'Deleted User';
                    $row['receiver_picture'] = $invitedUser->val('picture');
                    $row['role_type'] = Team::GetTeamRoleType($item['receiver_role_id'])['type'];
                    $row['status_text'] = Team::GetTeamRequestStatusLabel($item['status']);
                    $canResendInvite = false;
                    if ($item['status'] == 1){ 
                        $canResendInvite = true;
                    }
                    $row['can_resend_invite'] = $canResendInvite;

                    $showResendButton = false;
                    $showCancelButton = false;
                    $showDeleteButton = false;
                    $resendConfirmMsg = "";
                    $cancelConfirmMsg = "";
                    $deleteConfirmMsg = "";

                    if ($item['status'] == Team::TEAM_REQUEST_STATUS['PENDING']) {
                        $showResendButton = true;
                        $showCancelButton = true;
                        $resendConfirmMsg = gettext("Are you sure you want to resend request?");
                        $cancelConfirmMsg = gettext("Are you sure you want to cancel this request?");
                    }
                    if ($item['status'] != Team::TEAM_REQUEST_STATUS['PENDING']) {
                        $showDeleteButton = true;
                        $deleteConfirmMsg = gettext("Are you sure you want to delete this request?");
                    }
                    $row['actionButtons'] =array(
                        'showResendButton' => $showResendButton,
                        'showCancelButton' => $showCancelButton, 
                        'showDeleteButton' => $showDeleteButton,
                        'resendConfirmMsg' => $resendConfirmMsg,
                        'cancelConfirmMsg' => $cancelConfirmMsg,
                        'deleteConfirmMsg' => $deleteConfirmMsg
                    );

                    $finalInvites[] = $row;

                }
                exit(self::buildApiResponseAsJson($method, $finalInvites, 1, gettext('Invited list'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('No invites founds'), 200));
            }
        }
    }

    public function resendTeamRoleInvite($get)
    {
        global $_COMPANY, $_USER, $_ZONE;

        $method = "resendTeamRoleInvite";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'],'team_request_id' => @$get['team_request_id']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $team_request_id = $get['team_request_id'];
            
            $requestDetail = Team::GetTeamRequestDetail($groupid,$team_request_id);
            if ($requestDetail) {
                Team::SendRequestToJoinTeam($groupid, $requestDetail['receiverid'], $requestDetail['receiver_role_id'], $requestDetail['sender_role_id'], '', '');
                
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Invite sent successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong. Please try again.'), 200));
            }
        }
    }

    public function cancelTeamRequest($get)
    {
        global $_COMPANY, $_USER, $_ZONE;

        $method = "cancelTeamRequest";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'],'team_request_id' => @$get['team_request_id']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $team_request_id = $get['team_request_id'];
            $deleteRequest = Team::CancelTeamRequest($groupid,$team_request_id);
            if ($deleteRequest) {
               exit(self::buildApiResponseAsJson($method, '', 1, gettext('request canceled successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong. Please try again.'), 200));
            }
        }
    }

    public function deleteTeamInvite($get)
    {
        global $_COMPANY, $_USER, $_ZONE;

        $method = "deleteTeamInvite";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('groupid' => @$get['groupid'],'team_request_id' => @$get['team_request_id']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $groupid = $get['groupid'];
            $team_request_id = $get['team_request_id'];
            $deleteRequest = Team::DeleteTeamRequest($groupid,$team_request_id);
            if ($deleteRequest) {
               exit(self::buildApiResponseAsJson($method, '', 1, gettext('Request deleted successfully'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong. Please try again.'), 200));
            }
        }
    }


    public function downloadAttachment($get){
        global $_COMPANY, $_USER, $_ZONE;
        $method = "downloadAttachment";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('attachmentid' => @$get['attachmentid']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $attachment_id = $_COMPANY->decodeId($get['attachmentid']);
            $attachment = Attachment::GetAttachment($attachment_id);

            if (!$attachment) {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Attachment you are looking for does not exist.'), 200));
            }

            $topic = Teleskope::GetTopicObj($attachment->val('topictype'), $attachment->val('topicid'));

            if (!$topic
                || $topic?->isSoftDeleted()
            ) {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Attachment you are looking for does not exist.'), 200));
            }

            $attachment->download();
        }
    }

    public function getMyGloballyJoinedGroups($get)
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "getMyGlobalJoinedGroups";
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }

        // setting category id while fetching groups 
        $groups = Group::GetUserGloballyJoinedGroups($_USER->id());
        $joinedGroups = [];

        foreach ($groups as $group) {
            $groupArray = $this->groupObjToArray($group);
            $groupArray['isGroupMember'] = true;
            $groupArray['membersCount'] = (int)$group->getGroupMembersCount();

            $allChapters = Group::GetChapterList($group->id());
            $allChannels = Group::GetChannelList($group->id());
            $groupArray['chaptersCount'] = count($allChapters) ?? 0;
            $groupArray['channelsCount'] = count($allChannels) ?? 0;

            $joinedGroups[] = $groupArray;
        }

        if (count($joinedGroups)) {
            exit(self::buildApiResponseAsJson($method, $joinedGroups, 1, gettext('All globally joined groups'), 200));
        } else {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('No groups joined'), 200));
        }
    }

    public function deleteTouchPointEvent($get) 
    {
        global $_COMPANY, $_USER, $_ZONE;
        $method = "deleteTouchPointEvent";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exit(self::buildApiResponseAsJson($method, '', 0, gettext('Bad request'), 400));
        }
        $db = new Hems();
        $check = array('eventid' => @$get['eventid'],'event_cancel_reason' => @$get['event_cancel_reason']);
        $checkRequired = $db->checkRequired($check);
        if ($checkRequired) {
            exit(self::buildApiResponseAsJson($method, '', 0, sprintf(gettext("%s can't be empty"),$checkRequired), 200));
        } else {
            $eventid = $get['eventid'];
            if (
                ($event = TeamEvent::GetEvent($eventid)) === NULL ||
                ($team = Team::GetTeam($event->val('teamid'))) == null
            ){
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Event not found'), 200));
            }

            // Authorization Check
            if (!$event->loggedinUserCanManageEvent()
            ) { //Allow creators to delete unpublished content
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Access denied'), 200));
            }

            $event_cancel_reason = $get['event_cancel_reason'];
            $sendCancellationEmails = true; // For team events always send cancellation emails.
            if ($event->cancelEvent($event_cancel_reason, $sendCancellationEmails)) {
                $team->unlinkEventFromTouchpoint($eventid);
                exit(self::buildApiResponseAsJson($method, '', 1, gettext('Event deleted successfully.'), 200));
            } else {
                exit(self::buildApiResponseAsJson($method, '', 0, gettext('Something went wrong. Please try again.'), 200));
            }
        }

    }
    

}

function setLangContext($language_code)
{
    global $_COMPANY, $_ZONE;

    $allowedLanguages = array('en');
    if ($_COMPANY && $_ZONE){ // If both company and zone are set
        if ($_COMPANY->getAppCustomization()['locales']['enabled']){
            $allowedLanguages = array_keys($_COMPANY->getAppCustomization()['locales']['languages_allowed']);
        }
    }

    //setting the source/default locale, for informational purposes
    $selectedLanguage = 'en';

    if (in_array($language_code,$allowedLanguages)) {
        $selectedLanguage = $language_code;
    }

    if ($selectedLanguage != 'en') {
        $selectedLanguage .= '.UTF8';
    }
    Env::Put('LANG=' . $selectedLanguage);
    setlocale(LC_ALL, $selectedLanguage);// or Logger::Log("Language Setup Warning: {$selectedLanguage} is not supported");
    // this will make Gettext look for ../locales/<lang>/LC_MESSAGES/main.mo
    bindtextdomain('mobileapp_core', __DIR__ . '/locales');
    bind_textdomain_codeset('mobileapp_core', 'UTF-8');
    // here we indicate the default domain the gettext() calls will respond to
    textdomain('mobileapp_core');
}