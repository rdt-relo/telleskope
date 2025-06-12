<?php
require_once __DIR__ . '/IntegrationExternalType.php';

class FbWorkplaceIntegrationExternalType implements IntegrationExternalType
{
    const FB_URI = 'https://graph.workplace.com/';

    private $access_token;
    private $fb_groupid;
    private $link_unfurling;
    public function __construct(array $config)
    {
        $this->access_token = $config['external']['access_token'];
        $this->fb_groupid = $config['external']['fb_groupid'];
        $this->link_unfurling = $config['external']['link_unfurling'];
    }

    public function createMessage(string $message, string $title, string $link): string
    {
        $url = self::FB_URI . $this->fb_groupid . '/feed';
        $fields = array(
            'access_token' => $this->access_token,
            'formatting' => 'MARKDOWN',
            'message' => $message
        );

        if (!empty($link) && $this->link_unfurling) {
            $fields['link'] = $this->createUnfurlableLink($link);
        }

        $result = post_curl($url, array(), $fields);
        if ($result === false) {
            $result = 'Connection Error';
        } elseif ($result) {
            $result_vals = json_decode($result, true);
            if ($result_vals['id']) {
                return $result_vals['id'];
            }
        }
        Logger::Log('Workplace Integration: Error '.$result);
        return '';
    }

    public function updateMessage(string $messageid, string $message, string $title, string $link): string
    {
        $url = self::FB_URI . $messageid;
        $fields = array(
            'access_token' => $this->access_token,
            'formatting' => 'MARKDOWN',
            'message' => $message
        );

        if (!empty($link) && $this->link_unfurling) {
            $fields['link'] = $this->createUnfurlableLink($link);
        }

        $result = post_curl($url, array(), $fields);
        if ($result === false) {
            $result = 'Connection Error';
        } elseif ($result) {
            $result_vals = json_decode($result, true);
            if (isset($result_vals['success'])
                && $result_vals['success']) {
                return $messageid;
            } elseif (isset($result_vals['error']) && stripos($result_vals['error']['message'],'Invalid post id') !== false) {
                // Message deleted on Facebook ... recreate it
                return $this->createMessage($message, $title, $link);
            }
        }
        Logger::Log('Workplace Integration: Error '.$result);
        return '';
    }

    public function deleteMessage(string $messageid,string $title, string $message): bool
    {
        $url = self::FB_URI . $messageid;
        $fields = array(
            'access_token' => $this->access_token
        );
        $result = delete_curl($url, array(), $fields);
        if ($result === false) {
            $result = 'Connection Error';
        } elseif ($result) {
            $result_vals = json_decode($result, true);
            if ((isset($result_vals['success']) && $result_vals['success']) ||
                (isset($result_vals['error']) && stripos($result_vals['error']['message'],'Invalid ') !== false)
            ) {
                return true;
            }
        }
        Logger::Log('Workplace Integration: Error '.$result);
        return false;
    }

    /**
     * Converts Event  into various strings that can be used for publishing to various external integration points
     * @param int $forUpdate set to true if the message needs to built for updating external application
     * @param string $hosted_by
     * @param string $title
     * @param string $description
     * @param string $url
     * @param string $when
     * @param string $where
     * @return string[] Returns body, title, link that can be unfurled
     */
    public static function BuildEventMessage(int $forUpdate, string $hosted_by, string $title, string $description, string $url, string $when, string $where): array
    {
        $newOrUpdated = $forUpdate ? 'Updated' : 'New';
        $body =  <<<EOMEOM
*{$newOrUpdated} Event*

**{$title}**
Hosted by {$hosted_by}

* When: {$when}
* Where: {$where}

{$description} [view details & RSVP]({$url})
EOMEOM;
        return array($body,$title,$url);
    }

    /**
     * Converts Post into various strings that can be used for publishing to various external integration points
     * @param int $forUpdate set to true if the message needs to built for updating external application
     * @param string $posted_by
     * @param string $title
     * @param string $description
     * @param string $url
     * @return string[] Returns body, title, link that can be unfurled
     */
    public static function BuildPostMessage(int $forUpdate, string $posted_by, string $title, string $description, string $url): array
    {
        $newOrUpdated = $forUpdate ? 'Updated' : 'New';
        $body = <<<EOMEOM
*{$newOrUpdated} Announcement*

**{$title}**
Posted in {$posted_by}

{$description} [view details]({$url})
EOMEOM;
        return array($body,$title,$url);
    }

    /**
     * Converts Newsletter into various strings that can be used for publishing to various external integration points
     * @param int $forUpdate set to true if the message needs to built for updating external application
     * @param string $posted_by
     * @param string $title
     * @param string $description
     * @param string $url
     * @return string[] Returns body, title, link that can be unfurled
     */
    public static function BuildNewsletterMessage(int $forUpdate, string $posted_by, string $title, string $description, string $url): array
    {
        $newOrUpdated = $forUpdate ? 'Updated' : 'New';
        $body = <<<EOMEOM
*{$newOrUpdated} Newsletter*

**{$title}**
Posted in {$posted_by}

{$description} [view details]({$url})
EOMEOM;
        return array ($body, $title, $url);
    }

    /**
     * @param string $link
     * @return string
     */
    private function createUnfurlableLink(string $link): string
    {
        // "facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)"
        global $_COMPANY;
        $unfurl_params = array();
        $unfurl_params['allowed_user_agent'] = 'facebookexternalhit';
        $unfurl_params['nonce'] = teleskope_uuid(); // To add some randomness
        $unfurl_params['not_after'] = time() + 600;// 10 minutes
        $unfurl_params['embedded_url'] = $link;
        $unfurl_tok = $_COMPANY->encryptArray2String($unfurl_params);
        $urlhost = parse_url($link, PHP_URL_HOST);
        $app_type = strtolower(explode('.', $urlhost, 3)[1]);
        return 'https://'.$_COMPANY->val('subdomain').'.'.$app_type.'.io'.BASEDIR.'/unfurl/index?u='.$unfurl_tok;
    }
}