<?php
$_MEMOIZE_CACHE = array(); // Used to cache variables for the current execution path. A fancy global

//
// @Todo: Remove all config defines from TODO-Start to TODO-End and replace their references with corresponding Config::Get('...') calls
//

// @TODO-Start
//define ('SITE_ROOT', '/var/www/html/1');
define ('SITE_ROOT', Config::Get('DOCUMENT_ROOT').'/1');
// DB Setting
define ('HOST', Config::Get('DB_HOST'));
define ('DB', Config::Get('DB_NAME'));
define ('DBUSER', Config::Get('DB_USER'));
define ('DBPASS', Config::Get('DB_PASSWORD'));
define ('DB_RO_HOST', Config::Get('DB_RO_HOST'));

//DBLOG setting
define ('DBLOG_HOST', Config::Get('DBLOG_HOST'));
define ('DBLOG_NAME', Config::Get('DBLOG_NAME'));
define ('DBLOG_USER', Config::Get('DBLOG_USER'));
define ('DBLOG_PASSWORD', Config::Get('DBLOG_PASSWORD'));
define ('DBLOG_EMAIL_OPN_URL', Config::Get('DBLOG_EMAIL_OPN_URL'));
define ('DBLOG_EMAIL_CLK_URL', Config::Get('DBLOG_EMAIL_CLK_URL'));
define ('DBLOG_RO_HOST', Config::Get('DBLOG_RO_HOST'));

//DBPOINTS setting
//define ('DBPOINTS_HOST', Env::Get('DBPOINTS_HOST'));
//define ('DBPOINTS_NAME', Env::Get('points'));
//define ('DBPOINTS_USER', Env::Get('DB_USER'));
//define ('DBPOINTS_PASSWORD', Env::Get('DB_PASSWORD'));
define ('DBPOINTS_HOST', HOST);
define ('DBPOINTS_NAME', DB);
define ('DBPOINTS_USER', DBUSER);
define ('DBPOINTS_PASSWORD', DBPASS);
define ('DBPOINTS_RO_HOST', DB_RO_HOST);

//S3 Settings
//
// Instead of setting S3_KEY and S3_SECRET here, set the following two environment variables. Key Id should be set to
// the value of what we used to call S3_KEY and Access Key should be set to the value of what we called S3_SECRET
//
// AWS_ACCESS_KEY_ID
// AWS_SECRET_ACCESS_KEY
//
//define ('S3_KEY', Config::Get('S3_KEY'));
//define ('S3_SECRET', Config::Get('S3_SECRET'));
define ('S3_SAFE_BUCKET', Config::Get('S3_SAFE_BUCKET'));
define ('S3_BUCKET', Config::Get('S3_BUCKET'));
define ('S3_REGION', Config::Get('S3_REGION'));
define ('S3_UPLOADER_BUCKET', Config::Get('S3_UPLOADER_BUCKET'));
define ('S3_COMMENT_BUCKET', Config::Get('S3_COMMENT_BUCKET'));
define ('S3_TRAINING_VIDEO_BUCKET', Config::Get('S3_TRAINING_VIDEO_BUCKET'));
define ('S3_ALBUM_BUCKET', Config::Get('S3_ALBUM_BUCKET'));


//Email Settings & templates default variables
define ('SMTP_HOSTNAME', Config::Get('SMTP_HOSTNAME'));
define ('SMTP_PORT', Config::Get('SMTP_PORT'));
define ('SMTP_USERNAME', Config::Get('SMTP_USERNAME'));
define ('SMTP_PASSWORD', Config::Get('SMTP_PASSWORD'));
define('FROM_EMAIL', Config::Get('FROM_EMAIL'));
define('FROM_NAME', Config::Get('FROM_NAME'));

//Base URL's
define ('BASEURL', Config::Get('BASEURL'));
define ('BASEURL_AFFINITY', Config::Get('BASEURL_AFFINITY'));
define ('BASEDIR', Config::Get('BASEDIR'));

//Office365 Settings - old settings, remove the following 6 after all customers have been migrated to V2 of Login method
define ('OFFICE365_CLIENT_ID', Config::Get('OFFICE365_CLIENT_ID'));
define ('OFFICE365_CLIENT_SECRET', Config::Get('OFFICE365_CLIENT_SECRET'));
define ('OFFICE365_CLIENT_ID_AFFINITY', Config::Get('OFFICE365_CLIENT_ID_AFFINITY'));
define ('OFFICE365_CLIENT_SECRET_AFFINITY', Config::Get('OFFICE365_CLIENT_SECRET_AFFINITY'));
define ('OFFICE365_CLIENT_ID_OFFICERAVEN', Config::Get('OFFICE365_CLIENT_ID_OFFICERAVEN'));
define ('OFFICE365_CLIENT_SECRET_OFFICERAVEN', Config::Get('OFFICE365_CLIENT_SECRET_OFFICERAVEN'));

//Office365 Settings - new settings for V2 of Login method
define ('OFFICE365_CLIENT_ID_ADMIN_V2', Config::Get('OFFICE365_CLIENT_ID_ADMIN_V2'));
define ('OFFICE365_CLIENT_SECRET_ADMIN_V2', Config::Get('OFFICE365_CLIENT_SECRET_ADMIN_V2'));
define ('OFFICE365_CLIENT_ID_APPS_V2', Config::Get('OFFICE365_CLIENT_ID_APPS_V2'));
define ('OFFICE365_CLIENT_SECRET_APPS_V2', Config::Get('OFFICE365_CLIENT_SECRET_APPS_V2'));

// Google Recaptca Settings
define ('RECAPTCHA_SITE_KEY', Config::Get('RECAPTCHA_SITE_KEY'));
define ('RECAPTCHA_SECRET_KEY', Config::Get('RECAPTCHA_SECRET_KEY'));
//Google Maps API Key Settings
define ('GOOGLE_MAPS_API_KEY', Config::Get('GOOGLE_MAPS_API_KEY'));

//Key for UserAuth function
define ('TELESKOPE_USERAUTH_ADMIN_KEY', Config::Get('TELESKOPE_USERAUTH_ADMIN_KEY'));
define ('TELESKOPE_USERAUTH_AFFINITY_KEY', Config::Get('TELESKOPE_USERAUTH_AFFINITY_KEY'));
define ('TELESKOPE_USERAUTH_OFFICERAVEN_KEY', Config::Get('TELESKOPE_USERAUTH_OFFICERAVEN_KEY'));
define ('TELESKOPE_USERAUTH_API_KEY', Config::Get('TELESKOPE_USERAUTH_API_KEY'));
define ('TELESKOPE_USERAUTH_TALENTPEAK_KEY', Config::Get('TELESKOPE_USERAUTH_TALENTPEAK_KEY'));
define ('TELESKOPE_USERAUTH_PEOPLEHERO_KEY', Config::Get('TELESKOPE_USERAUTH_OFFICERAVEN_KEY')); // Temporarily map to officeraven key

// Generic Keys and Salts
define ('TELESKOPE_GENERIC_KEY', Config::Get('TELESKOPE_GENERIC_KEY'));
define ('TELESKOPE_GENERIC_SALT', Config::Get('TELESKOPE_GENERIC_SALT'));


//Key for Lambda function
define ('TELESKOPE_LAMBDA_API_KEY', Config::Get('TELESKOPE_LAMBDA_API_KEY'));

//Teleskope CDN
define ('TELESKOPE_CDN_STATIC', Config::Get('TELESKOPE_CDN_STATIC'));

// Google MEET Credentials
define ('CLIENT_KEY_GMEET', Config::Get('CLIENT_KEY_GMEET'));
define ('CLIENT_SECRET_GMEET', Config::Get('CLIENT_SECRET_GMEET'));

// Microsoft Teams Credentials
define ('CLIENT_KEY_TEAMS', Config::Get('CLIENT_KEY_TEAMS'));
define ('CLIENT_SECRET_TEAMS', Config::Get('CLIENT_SECRET_TEAMS'));

// Zoom Meetings Credentials
define ('CLIENT_KEY_ZOOM', Config::Get('CLIENT_KEY_ZOOM'));
define ('CLIENT_SECRET_ZOOM', Config::Get('CLIENT_SECRET_ZOOM'));
//define ('REDIRECT_URL_ZOOM', Config::Get('REDIRECT_URL_ZOOM'));

define('ENABLE_JSON_LOGS', Config::Get('ENABLE_JSON_LOGS'));
define('ENABLE_CLOUDWATCH_API', Config::Get('ENABLE_CLOUDWATCH_API'));


define ('PARTNERPATH_USERNAME', Config::Get('PARTNERPATH_USERNAME'));
define ('PARTNERPATH_PASSWORD', Config::Get('PARTNERPATH_PASSWORD'));
define ('PARTNERPATH_BASE_URI', Config::Get('PARTNERPATH_BASE_URI'));
define ('PARTNERPATH_AUTH_KEY', Config::Get('PARTNERPATH_AUTH_KEY'));
// @TODO-End

defined('HTTP_FORBIDDEN')  OR define('HTTP_FORBIDDEN', 'HTTP/1.1 403 Forbidden (Access Denied)');
defined('HTTP_BAD_REQUEST')  OR define('HTTP_BAD_REQUEST', 'HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
defined('HTTP_INTERNAL_SERVER_ERROR')  OR define('HTTP_INTERNAL_SERVER_ERROR', 'HTTP/1.1 500 Internal Server Error');
defined('HTTP_UNAUTHORIZED')  OR define('HTTP_UNAUTHORIZED', 'HTTP/1.1 401 Unauthorized (Please Sign in)');
defined('HTTP_NOT_FOUND') or define('HTTP_NOT_FOUND', 'HTTP/1.1 404 Not Found');

//Messages 
const ADDED = "Record added successfully";
const UPDATED = "Record updated successfully";
const DELETED = "Record deleted successfully";
const ERROR = "Unknown error";

const MAX_HOMEPAGE_FEED_ITERATOR_ITEMS = 15;
const MAX_ALBUM_MEDIA_ITEMS = 1000;
const MAX_ALBUM_MEDIA_PAGE_ITEMS = 40;

const MAX_TEAMS_ROLE_MATCHING_RESULTS = 30;
const MAX_TEAMS_ROLE_MATCHING_RESULTS_PER_DISCOVER_PAGE = 3;
const MAX_TEAMS_ROLE_MATCHING_RESULTS_PER_RECOMMEND_PAGE = 9;

const MAX_POST_SHARE_EMAILS = 25;

//Microsoft return url
const OFFICE365_REDIRECT_URI = BASEURL . '/user/office/app/oauth.php';
const OFFICE_HOME_URI = BASEURL . '/user/login';

// Provides details about release commit and vesion
require_once __DIR__.'/../rel_commit.php';
require_once __DIR__.'/../patch_commit.php';
define("REL_HASH", crc32(REL_COMMIT . PATCH_COMMIT));
