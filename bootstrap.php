<?php

if ($_SERVER['SCRIPT_NAME'] == '/index.php') return; // Skip init for root index.php

class Bootstrap
{
    public static function Init(): void
    {
        require_once(__DIR__ . '/include/utils/Str.php');
        require_once(__DIR__ . '/include/utils/Arr.php');
        require_once(__DIR__ . '/include/utils/Env.php');
        require_once(__DIR__ . '/include/utils/Date.php');
        require_once(__DIR__ . '/include/utils/TskpTime.php');
        self::InitConfig();
        self::InitLogger();
        ini_set('zend.exception_ignore_args', 1);
        require_once(__DIR__ . '/include/utils/TeleskopeException.php');
        require_once(__DIR__ . '/include/utils/Http.php');
        require_once(__DIR__ . '/include/utils/Session.php');
        require_once(__DIR__ . '/include/utils/Url.php');
        require_once(__DIR__ . '/include/utils/IP.php');
        require_once(__DIR__ . '/include/utils/TskpGlobals.php');
        require_once(__DIR__ . '/include/utils/Html.php');
        require_once(__DIR__ . '/include/utils/Lang.php');

        Url::HandleOldSubdomains();
    }

    private static function InitConfig(): void
    {
        require_once(__DIR__ . '/include/utils/Config.php');
        Config::Init();
    }

    private static function InitLogger(): void
    {
        require_once(__DIR__ . '/include/utils/Logger.php');
        Logger::Init();
    }
}

Bootstrap::Init();
