<?php

class TskpGlobals
{
    public static function InitZone(): void
    {
        global $_COMPANY, $_ZONE;

        $url_zoneid = Url::GetZoneidFromRequestURL();
        if ($url_zoneid) {
            $_ZONE = $_COMPANY->getZone($url_zoneid);
            return;
        }

        if (self::EnableFallbackZone()) {
            self::SetFallbackZone();
        }

        if (!$_ZONE && Env::IsLocalEnv()) {
            self::DeprecateOldUrl();
        }
    }

    private static function EnableFallbackZone(): bool
    {
        if (!Env::IsLocalEnv()) {
            return true;
        }

        $parsed_url = parse_url($_SERVER['REQUEST_URI']);

        parse_str($parsed_url['query'] ?? '', $query_params);
        if (($query_params['enable_fallback_zone'] ?? '') === '1') {
            return true;
        }

        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
            return true;
        }

        $whitelist_urls = [
            '/1/affinity/',
            '/1/affinity/update_login_profile',
            '/1/officeraven/',
            '/1/officeraven/update_login_profile',
            '/1/talentpeak/',
            '/1/talentpeak/update_login_profile',
            '/1/peoplehero/',
            '/1/peoplehero/update_login_profile',
        ];

        return in_array($parsed_url['path'], $whitelist_urls);
    }

    private static function SetFallbackZone(): void
    {
        global $_COMPANY, $_USER, $_ZONE;

        if ($_ZONE) {
            return;
        }

        $_ZONE = $_COMPANY->getZone($_USER->getHomeZone($_SESSION['app_type']));
    }

    private static function DeprecateOldUrl(): void
    {
        global $_ZONE;

        if (!Env::IsLocalEnv()) {
            return;
        }

        self::SetFallbackZone();

        if (!$_ZONE) {
            return;
        }

        $new_url = Url::ConvertOldUrl($_SERVER['REQUEST_URI'], $_ZONE->id());

        $current_url = "https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}";
        $parsed_url = parse_url($current_url);

        parse_str($parsed_url['query'] ?? '', $query_params);
        $query_params['enable_fallback_zone'] = 1;
        $parsed_url['query'] = http_build_query($query_params);

        $query_params = [
            'rurl_new' => $new_url,
            'rurl_old' => Url::UnparseUrl($parsed_url),
        ];

        Http::Redirect('/1/affinity/deprecate_old_url.html.php?' . http_build_query($query_params));
    }
}
