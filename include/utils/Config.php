<?php

use Aws\SecretsManager\SecretsManagerClient;

class Config
{
    private static $config_vars = [];

    public static function Init(): void
    {
        self::LoadEnvVars();
    }

    private static function LoadEnvVars(): void
    {
        $env_vars_key = Env::Get('AWS_SECRETS_MANAGER_KEY');
        $ttl = 3600 * 24;

        $json = apcu_fetch($env_vars_key);

        static::$config_vars = json_decode($json, true) ?: [];

        if (empty(static::$config_vars)) {
            require_once(__DIR__ . '/../libs/vendor/autoload.php');

            $client = new SecretsManagerClient([
                'region' => Env::Get('AWS_DEFAULT_REGION'),
                'version' => 'latest',
            ]);

            $result = $client->getSecretValue([
                'SecretId' => $env_vars_key,
            ]);

            $json = $result['SecretString'];
            static::$config_vars = json_decode($json, true);
            self::ValidateAndSetDefaults(); // Validate config before caching
            $json = json_encode(static::$config_vars);

            apcu_store($env_vars_key, $json, $ttl);
        }
    }

    private static function Set(string $key, $value): void
    {
        static::$config_vars[$key] = $value;
    }

    public static function Get(string $key)
    {
        $function_name = 'Get' . Str::ConvertSnakeCaseToCamelCase($key);
        if (method_exists(static::class, $function_name)) {
            return call_user_func([static::class, $function_name], $key);
        }

        return self::GetCachedDefaultValue($key);
    }

    public static function GetCachedDefaultValue(string $key)
    {
        return static::$config_vars[$key] ?? null;
    }

    /**
     * This method
     * (a) validates all the required configurtion parameters, if validation fails the system exists with a die()
     * (b) sets default values for missing optional parameters
     * (c) sets dependent parameters
     * @return void
     */
    private static function ValidateAndSetDefaults()
    {
        // Common
        if (empty(self::Get('FROM_EMAIL')))  self::Set('FROM_EMAIL', 'hello@teleskope.io');
        if (empty(self::Get('FROM_NAME'))) self::Set('FROM_NAME', 'The Teleskope Team');
        if (empty(self::Get('BASEDIR'))) self::Set('BASEDIR', '/1');
        if (empty(self::Get('TELESKOPE_CDN_STATIC'))) self::Set('TELESKOPE_CDN_STATIC', '/1');
        if (empty(self::Get('DOCUMENT_ROOT'))) die ('Error 1000: Site Environment not set');

        // DB Setting
        if (empty(self::Get('DB_HOST'))) die ('Error 1001: DB Environment not set');
        if (empty(self::Get('DB_NAME'))) die ('Error 1002: DB Environment not set');
        if (empty(self::Get('DB_USER'))) die ('Error 1003: DB Environment not set');
        if (empty(self::Get('DB_PASSWORD'))) self::Set('DBPASS', '');
        if (empty(Config::Get('DB_RO_HOST'))) self::Set('DB_RO_HOST', self::Get('DB_HOST'));

        //DBLOG setting
        if (empty(self::Get('DBLOG_HOST'))) die ('Error 1001: DBLOG Environment not set');
        if (empty(self::Get('DBLOG_NAME'))) die ('Error 1002: DBLOG Environment not set');
        if (empty(self::Get('DBLOG_USER'))) die ('Error 1003: DBLOG Environment not set');
        if (empty(Config::Get('DBLOG_PASSWORD'))) self::Set('DBLOG_PASSWORD', '');
        if (empty(Config::Get('DBLOG_RO_HOST'))) self::Set('DBLOG_RO_HOST', self::Get('DBLOG_HOST'));

        if (empty(self::Get('DBLOG_EMAIL_OPN_URL'))) die ('Error 1005: Email Log Environment not set');
        if (empty(self::Get('DBLOG_EMAIL_CLK_URL'))) die ('Error 1006: Email Log Environment not set');
        
        //S3 Settings
        //
        // Instead of setting S3_KEY and S3_SECRET here, set the following two environment variables. Key Id should be set to
        // the value of what we used to call S3_KEY and Access Key should be set to the value of what we called S3_SECRET
        //
        // AWS_ACCESS_KEY_ID
        // AWS_SECRET_ACCESS_KEY
        //
        //if (empty(self::Get('S3_KEY'))) die ('Error 1011: Storage Environment not set');
        //if (empty(self::Get('S3_SECRET'))) die ('Error 1012: Storage Environment not set');
        if (empty(self::Get('S3_SAFE_BUCKET'))) die ('Error 1012: Storage Environment not set');
        if (empty(self::Get('S3_BUCKET'))) die ('Error 1013: Storage Environment not set');
        if (empty(self::Get('S3_REGION'))) die ('Error 1014: Storage Environment not set');
        if (empty(self::Get('S3_UPLOADER_BUCKET'))) die ('Error 1015: Storage Environment not set');
        if (empty(self::Get('S3_COMMENT_BUCKET'))) die ('Error 1016: Storage Environment not set');
        if (empty(self::Get('S3_TRAINING_VIDEO_BUCKET'))) die ('Error 1017: Storage Environment not set');
        if (empty(self::Get('S3_ALBUM_BUCKET'))) die ('Error 1018: Storage Environment not set');


        //Email Settings & templates default variables
        if (empty(self::Get('SMTP_HOSTNAME'))) die ('Error 1021: Messaging Environment not set');
        if (empty(self::Get('SMTP_PORT'))) die ('Error 1022: Messaging Environment not set');
        if (empty(self::Get('SMTP_USERNAME'))) die ('Error 1023: Messaging Environment not set');
        if (empty(self::Get('SMTP_PASSWORD'))) die ('Error 1024: Messaging Environment not set');

        //Base URL's
        if (empty(self::Get('BASEURL'))) die ('Error 1031: URL Environment not set');
        if (empty(self::Get('BASEURL_AFFINITY'))) die ('Error 1032: URL Environment not set');

        //Office365 Settings - deprecated, will be removed after all clients move to V2 of Login method
        if (empty(self::Get('OFFICE365_CLIENT_ID'))) die ('Error 1051: SSO Environment not set');
        if (empty(self::Get('OFFICE365_CLIENT_SECRET'))) die ('Error 1052: SSO Environment not set');
        if (empty(self::Get('OFFICE365_CLIENT_ID_AFFINITY'))) die ('Error 1053: SSO Environment not set');
        if (empty(self::Get('OFFICE365_CLIENT_SECRET_AFFINITY'))) die ('Error 1054: SSO Environment not set');
        if (empty(self::Get('OFFICE365_CLIENT_ID_OFFICERAVEN'))) die ('Error 1055: SSO Environment not set');
        if (empty(self::Get('OFFICE365_CLIENT_SECRET_OFFICERAVEN'))) die ('Error 1056: SSO Environment not set');

        // Office 365 Login Settings - New
        if (empty(self::Get('OFFICE365_CLIENT_ID_ADMIN_V2'))) die ('Error 1057A: SSO Environment not set');
        if (empty(self::Get('OFFICE365_CLIENT_SECRET_ADMIN_V2')))    die ('Error 1057B: SSO Environment not set');
        if (empty(self::Get('OFFICE365_CLIENT_ID_APPS_V2'))) die ('Error 1058A: SSO Environment not set');
        if (empty(self::Get('OFFICE365_CLIENT_SECRET_APPS_V2'))) die ('Error 1058B: SSO Environment not set');

        // Google Recaptca Settings
        if (empty(self::Get('RECAPTCHA_SITE_KEY'))) die ('Error 1061: Recaptcha Environment not set');
        if (empty(self::Get('RECAPTCHA_SECRET_KEY'))) die ('Error 1062: Recaptcha Environment not set');
        //Google Maps API Key Settings
        if (empty(self::Get('GOOGLE_MAPS_API_KEY'))) die ('Error 1063: Google Maps API not set');

        //Key for UserAuth function
        if (empty(self::Get('TELESKOPE_USERAUTH_ADMIN_KEY'))) die ('Error 1071: Teleskope User Auth Admin Key not set');
        if (empty(self::Get('TELESKOPE_USERAUTH_AFFINITY_KEY'))) die ('Error 1072: Teleskope User Auth Affinity Key not set');
        if (empty(self::Get('TELESKOPE_USERAUTH_OFFICERAVEN_KEY'))) die ('Error 1073: Teleskope User Auth Affinity Key not set');
        if (empty(self::Get('TELESKOPE_USERAUTH_API_KEY'))) die ('Error 1074: Teleskope User Auth API Key not set');
        if (empty(self::Get('TELESKOPE_USERAUTH_TALENTPEAK_KEY'))) die ('Error 1075: Teleskope User Auth Talenpeak Key not set');
        //if (empty(self::Get('TELESKOPE_USERAUTH_PEOPLEHERO_KEY'))) die ('Error 1076: Teleskope User Auth Peoplehero Key not set');

        // AES prefix for user profile
        if (empty(self::Get('USER_PROFILE_AES_PREFIX'))) die ('Error 1076: Teleskope User AES Prefix not set');

        // Generic Keys and Salts
        if (empty(self::Get('TELESKOPE_GENERIC_KEY'))) die ('Error 1076: Teleskope Generic Key not set');
        if (empty(self::Get('TELESKOPE_GENERIC_SALT'))) die ('Error 1077: Teleskope Generic Salt not set');
        
        //Key for Lambda function
        if (empty(self::Get('TELESKOPE_LAMBDA_API_KEY'))) die ('Error 1081: Teleskope Lambda API not set');

        //Key for Firebase - used for sending notifications to device
        if (empty(self::Get('FIREBASE_API_KEY'))) die ('Error 2011: Key not set');
        if (empty(self::Get('FIREBASE_SERVICE_KEY_BASE64'))) die ('Error 2012: Key not set');

        // Google MEET Credentials
        if (empty(self::Get('CLIENT_KEY_GMEET'))) die ('Error 3001: Google Meet Client Key not set');
        if (empty(self::Get('CLIENT_SECRET_GMEET'))) die ('Error 3002: Google Meet SECRET not set');

        // Microsoft Teams Credentials
        if (empty(self::Get('CLIENT_KEY_TEAMS'))) die ('Error 3011: Microsft Teams Client Key not set');
        if (empty(self::Get('CLIENT_SECRET_TEAMS'))) die ('Error 3012: Microsft Teams Client Secret not set');

        // Zoom Meetings Credentials
        if (empty(self::Get('CLIENT_KEY_ZOOM'))) die ('Error 3021: Zoom Meetings Client Key not set');
        if (empty(self::Get('CLIENT_SECRET_ZOOM'))) die ('Error 3022: Zoom Meetings CLIENT SECRET not set');
        // if (empty(self::Get('REDIRECT_URL_ZOOM'))) die ('Error 3023: Zoom Meetings REDIRECT URL not set');

        // Survey JS License
        if (empty(self::Get('SURVEY_JS_LICENSE_KEY'))) die ('Error 3031: Survey License Key not set');

        // Log configuration
        if (empty(self::Get('ENABLE_JSON_LOGS'))) self::Set('ENABLE_JSON_LOGS', '1');

        // Cloudwatch API
        if (empty(self::Get('ENABLE_CLOUDWATCH_API'))) self::Set('ENABLE_CLOUDWATCH_API', '1');

    }

    public static function GetRecaptchaSiteKey(string $key)
    {
        if (Env::IsSelenium()) {
            return '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
        }

        return self::GetCachedDefaultValue($key);
    }

    public static function GetRecaptchaSecretKey(string $key)
    {
        if (Env::IsSelenium()) {
            return '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';
        }

        return self::GetCachedDefaultValue($key);
    }

    public static function GetEnableGlobalSearch(string $key): bool
    {
        return self::Get('ENABLE_SEARCH') === '1';
    }

    public static function GetEnableZoneSearch(string $key): bool
    {
        global $_COMPANY;

        if (!self::Get('ENABLE_GLOBAL_SEARCH')) {
            return false;
        }

        return $_COMPANY?->getAppCustomization()['search']['enabled'] ?? false;
    }

    public static function GetDocumentRoot(string $key)
    {   // DOCUMENT_ROOT environment variable is set by apache on some OS, e.g. MacOS
        return getenv($key) ?: self::GetCachedDefaultValue($key);
    }
}
