<?php

class Url
{
    public const VALID_ADMIN_DOMAIN = '.teleskope.io';

    public const VALID_APP_DOMAINS = [
        '.affinities.io',
        '.officeraven.io',
        '.talentpeak.io',
        '.peoplehero.io',
    ];

    public const OLD_URL_PREFIXES = [
        '/1/affinity/',
        '/1/officeraven/',
        '/1/talentpeak/',
        '/1/peoplehero/',
        '/1/admin/',
    ];

    public const FAVICON_URLS = [
            'affinities' =>     '/images/favicons/affinities_favicon.ico',   #'/image/favicons/affinities_favicon.ico'
            'officeraven' =>    '/images/favicons/officeraven_favicon.ico',  #'/image/favicons/officeraven_favicon.ico'
            'talentpeak' =>     '/images/favicons/talentpeak_favicon.ico',  #'/image/favicons/talentpeak_favicon.ico'
            'peoplehero' =>     '/images/favicons/peoplehero_favicon.ico',  #'/image/favicons/peoplehero_favicon.ico'
            'teleskope' =>      '/images/favicons/teleskope_favicon.ico',  #'/image/favicons/affinities_favicon.ico'
            'default' =>        '/images/favicons/default_favicon.png',  #'/image/favicons/teleskope_favicon.ico'
        ];

    public static function GetAppFromServerName(): string
    {
        // Get hostname from server hostname
        $hostname = $_SERVER['SERVER_NAME'];

        if (str_ends_with($hostname, '.affinities.io')) {
            return 'affinities';
        } elseif (str_ends_with($hostname, '.talentpeak.io')) {
            return 'talentpeak';
        } elseif (str_ends_with($hostname, '.officeraven.io')) {
            return 'officeraven';
        } elseif (str_ends_with($hostname, '.peoplehero.io')) {
            return 'peoplehero';
        } elseif (str_ends_with($hostname, '.teleskope.io')) {
            return 'teleskope';
        } else {
            return '';
        }
    }

    public static function IsValidTeleskopeAdminDomain (string $domain) : bool
    {
        return str_ends_with($domain, self::VALID_ADMIN_DOMAIN);
    }

    public static function IsValidTeleskopeAppDomain (string $domain) : bool
    {
        foreach (self::VALID_APP_DOMAINS as $valid_domain) {
            if (str_ends_with($domain, $valid_domain))
                return true;
        }
        return false;
    }

    public static function IsValidTeleskopeDomain (string $domain) : bool
    {
        return self::IsValidTeleskopeAdminDomain($domain) || self::IsValidTeleskopeAppDomain($domain);
    }


    /**
     * Checks if the path of the given URL ends with a given type such as index or home. Provide the $type without .php
     * extension. This method will check for both with and without .php extension.
     * @param string $url
     * @param string $type
     * @return bool
     */
    public static function IsUrlPathOfType(string $url, string $type) : bool
    {
        $p = parse_url($url);
        $path = $p['path'];

        // Note this url may be in old format and may not have zone awareness but the redirected URL, e.g. eventview
        // should be able to handle proper redirection after loading the event and extracting zoneid from it.

        $path = substr(strrchr($p['path'], '/') ?: '', 1);

        if (empty($type)) { // If $type is empty, then return true only if $path is also empty.
            return empty($path);
        } else { // Check if $path ends with $type
            if (str_ends_with($path, $type) || str_ends_with($path, $type . '.php')) {
                return true;
            }
        }
        return false;
    }
    /**
     * https://www.php.net/manual/en/function.parse-url.php#106731
     */
    public static function UnparseUrl(array $parsed_url): string
    {
        $scheme   = !empty($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = !empty($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = !empty($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = !empty($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = !empty($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = !empty($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = !empty($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = !empty($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Returns a URL base with embedded encoded zoneid. The URL has a trailing slash
     * @param int $zoneid if zoneid cannot be loaded in $_COMPANY context, empty string will be returned.
     * @param string $context either 'admin' or 'app' (default).
     * @return string
     */
    public static function GetZoneAwareUrlBase (int $zoneid, string $context = 'app') : string
    {
        global $_COMPANY;

        $zone = $_COMPANY->getZone($zoneid);  // Loading zone to make sure zoneid is a valid company zone
        if (!$zone) {
            return '';
        }

        $context = ($context == 'admin') ? 'admin' : 'app';
        $domain = ($context == 'admin') ? 'teleskope' : $zone->val('app_type');

        $encoded_zoneid = $_COMPANY->encodeId($zone->id());;
        $context_and_zone = "{$context}-{$encoded_zoneid}";

        return "https://{$_COMPANY->val('subdomain')}.{$domain}.io" . BASEDIR . "/{$context_and_zone}/";
    }

    /**
     * Extracts zoneid from current REQUEST URI and returns zoneid.
     * @return int zoneid or 0 if zoneid cannot be extracted.
     */
    public static function GetZoneidFromRequestURL() : int
    {
        global $_COMPANY;
        $zoneid = 0;

        $re = '!^' . BASEDIR . '/(app|admin)-([^/]*)!m'; // Same reqular expression as apache rewrite module
        $uri = rtrim($_SERVER['REQUEST_URI'],'/') . '/'; // Ensure uri ends with a trailing slash

        if (preg_match($re, $uri, $matches)) {
            $encoded_zoneid = $matches[2];
            $zoneid = $_COMPANY->decodeId($encoded_zoneid);
        }
        return ($zoneid > 0) ? $zoneid : 0;
    }

    public static function IsOldUrl($url): bool
    {
        $parts = parse_url($url);

        if (empty($parts['path'])) {
            return false;
        }

        foreach (self::OLD_URL_PREFIXES as $url_prefix) {
            if (str_starts_with($parts['path'], $url_prefix)) {
                return true;
            }
        }

        return false;
    }

    public static function ConvertOldUrl(string $url, int $zoneid): string
    {
        $parsed_url = parse_url($url);

        $context = 'app';
        if (str_starts_with($parsed_url['path'], '/1/admin/')) {
            $context = 'admin';
        }

        foreach (Url::OLD_URL_PREFIXES as $url_prefix) {
            $parsed_url['path'] = str_replace($url_prefix, '', $parsed_url['path']);
        }

        $parsed_url['path'] = ltrim($parsed_url['path'], '/');

        parse_str($parsed_url['query'] ?? '', $query_params);
        unset($query_params['zone'], $query_params['zoneid']);
        $parsed_url['query'] = http_build_query($query_params);

        $new_url = Url::GetZoneAwareUrlBase($zoneid, $context)
            . $parsed_url['path']
            . (empty($parsed_url['query']) ? '' : '?' . $parsed_url['query'])
        ;

        $new_url = Url::ConvertHashAttributeToFrag($new_url);

        return $new_url;
    }

    public static function Valid(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parsed_url = parse_url($url);
        return in_array($parsed_url['scheme'] ?? '', ['', 'http', 'https']);
    }

    /**
     * Checks if the provided URL has hash tag attribute that can be converted to fragment
     * @param $url
     * @return bool
     */
    public static function IsHashAttributeUrl ($url): bool
    {
        return str_contains($url, '&hash=') || str_contains($url, '?hash=');
    }

    /**
     * Converts hash attribute in the URL to a fragment,
     * e.g. converts
     * https://gmail.teleskope.io/1/home?hash=aboutus
     * to
     * https://gmail.teleskope.io/1/home#aboutus
     *
     * @param string $url
     * @return string
     */
    public static function ConvertHashAttributeToFrag (string $url) : string
    {
        if (!self::IsHashAttributeUrl($url))
            return $url;

        $parts = parse_url($url);
        parse_str($parts['query'], $query);
        if (array_key_exists('hash',$query)){
            $parts['fragment'] = $query['hash'];
            unset($query['hash']);
            $parts['query'] = http_build_query($query);
        }
        return self::UnparseUrl($parts);
    }

    public static function HandleOldSubdomains(): void
    {
        $parsed_url = parse_url("https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}");

        $domain = $parsed_url['host'];

        if (!Url::IsValidTeleskopeAppDomain($domain)) {
            return;
        }

        $old_subdomains = [
            //'amerisourcebergen' => 'cencora',
            'empower-retirement'  => 'empower' 
        ];

        $parts = explode('.', $domain);
        $subdomain = $parts[0];

        if (!isset($old_subdomains[$subdomain])) {
            return;
        }

        $parts[0] = $old_subdomains[$subdomain];
        $parsed_url['host'] = implode('.', $parts);

        Http::Redirect(Url::UnparseUrl($parsed_url), true);
    }

    public static function GetFavIconUrl(?string $app = null): string
    {
        $app = $app ?? (self::GetAppFromServerName() ?: 'default');
        return TELESKOPE_CDN_STATIC . (self::FAVICON_URLS[$app]);
    }
}
