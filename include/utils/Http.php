<?php

class Http
{
    /**
     * @param string $url
     * @return void
     */
    public static function Redirect (string $url, bool $is_permanent_redirect = false): void
    {
        $status_code = $is_permanent_redirect ? 301 : 302;
        http_response_code($status_code);
        header("Location: {$url}");
        exit();
    }

    public static function RedirectIfOldUrl(?int $zid = null): void
    {
        global $_COMPANY, $_ZONE;

        if (Url::GetZoneidFromRequestURL()) {
            return;
        }

        if (!Url::IsOldUrl($_SERVER['REQUEST_URI'])) {
            return;
        }

        $zid = $zid ?? $_GET['zoneid'] ?? $_GET['zone'] ?? 0;

        if (!$zid) {
            return;
        }

        if (!is_numeric($zid)) {
            $zid = $_COMPANY->decodeId($zid);
        }

        if ($zid === $_ZONE->id()) {
            return;
        }

        // The URL we are processing is an old URL with zone explicitly provided,
        // change it to the correct one which has zoneid embedded in the URL and redirect
        $new_url = Url::ConvertOldUrl($_SERVER['REQUEST_URI'], $zid);

        $new_url = Url::ConvertHashAttributeToFrag($new_url);

        Http::Redirect($new_url);
    }

    public static function RedirectIfHashAttributeUrl(): void
    {
        if (Url::IsHashAttributeUrl($_SERVER['REQUEST_URI'])) {
            Http::Redirect(Url::ConvertHashAttributeToFrag($_SERVER['REQUEST_URI']));
        }
    }

    public static function Forbidden(string $why = '')
    {
        http_response_code(403);
        echo '<h4>Forbidden (Access Denied)</h4>';
        if ($why) {
            echo "<p>{$why}</p>";
        }
        exit(1);
    }

    public static function Unavailable(string $why = '')
    {
        http_response_code(503);
        echo '<h4>Unavailable</h4>';
        if ($why) {
            echo "<p>{$why}</p>";
        }
        exit(1);
    }

    public static function IsAjaxRequest(): bool
    {
        return (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest');
    }

    public static function IsMobileApiRequest(): bool
    {
        return str_starts_with($_SERVER['REQUEST_URI'], '/1/api/');
    }

    public static function NotFound(string $custom_message = '')
    {
        $_GET['code'] = 404;
        require __DIR__ . '/../../errorpage.php';
        http_response_code(404);
        exit(1);
    }

    public static function Download(string $filepath, string $filename): void
    {
        $content_type = \GuzzleHttp\Psr7\MimeType::fromFilename($filename);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit();
    }

    public static function Cache(int $seconds)
    {
        if ($seconds < 1) return;
        // Calculate the time 5 minutes from now.
        $expires = gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT';

        // Send the caching headers to the browser.
        header('Expires: ' . $expires);
        header('Cache-Control: public, max-age=' . $seconds);
        header('Pragma: public');
    }
}
