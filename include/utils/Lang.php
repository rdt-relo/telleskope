<?php

class Lang
{
    public static function Init(?string $selectedLanguage = null): void
    {
        $selectedLanguage ??= self::DetectLanguage();
        $locales_dir = __DIR__ . '/../../affinity/locales';

        // here we define the global system locale given the found language
        Env::Put('LANG='.$selectedLanguage);
        setlocale(LC_ALL, $selectedLanguage) ;//or Logger::Log("Language Setup Warning: {$selectedLanguage} is not supported");
        bindtextdomain('webapp_core', $locales_dir);
        bind_textdomain_codeset('webapp_core', 'UTF-8');
        // here we indicate the default domain the gettext() calls will respond to
        textdomain('webapp_core');
    }

    private static function DetectLanguage(): string
    {
        global $_COMPANY, $_ZONE, $_USER;

        $allowedLanguages = array('en');
        if ($_COMPANY && $_ZONE){ // If both company and zone are set
            if ($_COMPANY->getAppCustomization()['locales']['enabled']){
                $allowedLanguages = array_keys($_COMPANY->getAppCustomization()['locales']['languages_allowed']);
            }
        }

        //setting the source/default locale, for informational purposes
        $selectedLanguage = 'en';

        if ($_USER && $_USER->val('language') && in_array( $_USER->val('language'),$allowedLanguages)) {
            // the locale can be changed through the query-string
            $selectedLanguage = $_USER->val('language');
        } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // default: look for the languages the browser says the user accepts
            $httpHeaderLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            array_walk($httpHeaderLanguages, function (&$l) { $l = strtr(strtok($l, ';'), ['-' => '_']); });
            foreach ($httpHeaderLanguages as $httpHeaderLanguage) {
                if (in_array($httpHeaderLanguage, $allowedLanguages)) {
                    $selectedLanguage = $httpHeaderLanguage;
                    break;
                }
            }
        }

        if ($selectedLanguage != 'en') {
            $selectedLanguage .= '.UTF8';
        }

        return $selectedLanguage;
    }

    public static function GetSelectedLanguage()
    {
        return Env::Get('LANG') ?: self::DetectLanguage();
    }
}
