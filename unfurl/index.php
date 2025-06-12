<?php
require_once __DIR__.'/../include/Company.php';

$unfurl_url = "https://{$_SERVER['HTTP_HOST']}";
$_UNFURL_COMPANY = Company::GetCompanyByUrl($unfurl_url);
$unfurl_params = isset($_GET['u']) ? $_UNFURL_COMPANY->decryptString2Array(urldecode($_GET['u'])) : array();

if ((!strpos($_SERVER['HTTP_HOST'], '.affinities.io') &&
    !strpos($_SERVER['HTTP_HOST'], '.officeraven.io') &&
    !strpos($_SERVER['HTTP_HOST'], '.talentpeak.io') &&
    !strpos($_SERVER['HTTP_HOST'], '.peoplehero.io'))
    ||
    empty($unfurl_params)
    ||
    empty($unfurl_params['embedded_url'])
    ||
    empty($unfurl_params['not_after'])
    ||
    empty($unfurl_params['allowed_user_agent'])
    ||
    (parse_url($unfurl_params['embedded_url'], PHP_URL_HOST) != parse_url($unfurl_url, PHP_URL_HOST))
) {
    //Block access to admin pages via non https://companyname.affinities.io domains
    header(HTTP_NOT_FOUND);
    die('Error: Invalid URL');
}

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if ($unfurl_params['not_after'] > time() && strpos($ua, $unfurl_params['allowed_user_agent']) === 0) {
    // Token is not expired and target User Agent intended for unfurling matched
    $embedded_url_parts = parse_url($unfurl_params['embedded_url']);
    parse_str($embedded_url_parts['query'], $embedded_url_params);

    $_COMPANY = $_UNFURL_COMPANY; // Temporarily set $_COMPANY to allow GetPost, GetEvent to work.
    if (stripos( $embedded_url_parts['path'],'viewpost') !== false) { // Of Announcement type
        if (isset($embedded_url_params['id']) &&
            ($postid = $_COMPANY->decodeId($embedded_url_params['id']))>0 &&
            ($post = Post::GetPost($postid)) &&
            $post->isActive()
        ) {
            Logger::Log("Unfurl: Processed Post Unfurl - {$_UNFURL_COMPANY->id()}|0|{$_SERVER['REQUEST_METHOD']}|{$_SERVER['HTTP_HOST']}|{$_SERVER['REQUEST_URI']}, mapped to {$unfurl_params['embedded_url']}", Logger::SEVERITY['INFO']);
            ob_clean();
            echo "<html><head><title>{$post->val('title')}</title></head><body><h1>{$post->val('title')}</h1>{$post->val('post')}</body></html>";
            exit();
        }
    } elseif (stripos( $embedded_url_parts['path'],'eventview') !== false) { // Of Event type
        if (isset($embedded_url_params['id']) &&
            ($eventid = $_COMPANY->decodeId($embedded_url_params['id']))>0 &&
            ($event = Event::GetEvent($eventid)) &&
            $event->isActive()
        ) {
            Logger::Log("Unfurl: Processed Event Unfurl - {$_UNFURL_COMPANY->id()}|0|{$_SERVER['REQUEST_METHOD']}|{$_SERVER['HTTP_HOST']}|{$_SERVER['REQUEST_URI']}, mapped to {$unfurl_params['embedded_url']}", Logger::SEVERITY['INFO']);
            ob_clean();
            echo "<html><head><title>{$event->val('eventtitle')}</title></head><body><h1>{$event->val('eventtitle')}</h1>{$event->val('event_description')}</body></html>";
            exit();
        }
    } elseif (stripos( $embedded_url_parts['path'],'newsletter') !== false) { // Of Newsletter type
        if (isset($embedded_url_params['id']) &&
            ($newsletterid = $_COMPANY->decodeId($embedded_url_params['id']))>0 &&
            ($newsletter = Newsletter::GetNewsletter($newsletterid)) &&
            $newsletter->isActive()
        ) {
            Logger::Log("Unfurl: Processed Newsletter Unfurl - {$_UNFURL_COMPANY->id()}|0|{$_SERVER['REQUEST_METHOD']}|{$_SERVER['HTTP_HOST']}|{$_SERVER['REQUEST_URI']}, mapped to {$unfurl_params['embedded_url']}", Logger::SEVERITY['INFO']);
            ob_clean();
            echo $newsletter->val('newsletter');
            exit();
        }
    }
    $_COMPANY = null; // Just incase .... we reache here reset the company back to null
}

Logger::Log("Unfurl: Processed Redirect - {$_UNFURL_COMPANY->id()}|0|{$_SERVER['REQUEST_METHOD']}|{$_SERVER['HTTP_HOST']}|{$_SERVER['REQUEST_URI']}, redirected to {$unfurl_params['embedded_url']}", Logger::SEVERITY['INFO']);
header('location: '.$unfurl_params['embedded_url']);
