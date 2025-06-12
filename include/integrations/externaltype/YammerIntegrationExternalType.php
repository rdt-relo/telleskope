<?php
require_once __DIR__ . '/IntegrationExternalType.php';

class YammerIntegrationExternalType implements IntegrationExternalType
{
    const YAMMER_URI_V1 = 'https://www.yammer.com/api/v1/';
    const YAMMER_URI_V2 = 'https://www.yammer.com/api/v2/';

    private $access_token;
    private $yammer_groupid;
    public function __construct(array $config)
    {
        $this->access_token = $config['external']['access_token'];
        $this->yammer_groupid = $config['external']['yammer_groupid'];
    }

    public function createMessage(string $message, string $title, string $link): string
    {
        $url = self::YAMMER_URI_V1 . '/messages.json';
        $headers = array(
            'Authorization: Bearer ' . $this->access_token,
        );
        $fields = array(
            'group_id' => $this->yammer_groupid,
            'is_rich_text' => true,
            'message_type' => 'announcement',
            'title' => $title,
            'body' => $message,
        );
        $result = post_curl($url, $headers, $fields);
        if ($result === false) {
            $result = 'Connection Error';
        } elseif ($result) {
            $result_vals = json_decode($result, true);
            if (!empty($result_vals['messages'][0]['id'])) {
                return $result_vals['messages'][0]['id'];
            }
        }
        Logger::Log('Viva Engage / Yammer Integration: Error '.$result);
        return '';
    }

    public function updateMessage(string $messageid, string $message, string $title, string $link): string
    {
        $url = self::YAMMER_URI_V2 . 'messages/' . $messageid;
        $headers = array(
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json' // Fields need to be JSON format in V2
        );
        $fields = array(
            'is_rich_text' => true,
            'message_type' => 'announcement',
            'title' => $title,
            'body' => $message,
        );
        $result = patch_curl($url, $headers, json_encode($fields)); // V2 Patch requires JSON encoded fields
        if ($result === false) {
            $result = 'Connection Error';
        } elseif ($result) {
            $result_vals = json_decode($result, true);
            if (isset($result_vals['messages'])
                && !empty($result_vals['messages'][0]['id'])
                && (isset($result_vals['messages'][0]['state']) && stripos($result_vals['messages'][0]['state'], 'deleted') === false)) {
                return $messageid; // Message updated, return the same message id
            } elseif ((isset($result_vals['error']) && stripos($result_vals['error'],'Invalid initial state') !== false)
                ||    (isset($result_vals['messages'][0]['state']) && stripos($result_vals['messages'][0]['state'], 'deleted') !== false)) {
                // Message deleted on Viva Engage / Yammer ... recreate it
                return $this->createMessage($message, $title, $link);
            }
        }
        Logger::Log('Viva Engage / Yammer Integration: Error '.$result);
        return '';
    }

    public function deleteMessage(string $messageid,string $title, string $message): bool
    {
        $url = self::YAMMER_URI_V1 . 'messages/' . $messageid;
        $headers = array(
            'Authorization: Bearer ' . $this->access_token,
        );
        $fields = array();
        $result = delete_curl($url, $headers, $fields);
        if ($result === false) {
            $result = 'Connection Error';
        } elseif (empty(trim($result))) {
            return true; // If the Viva Engage / Yammer deleted the message, then we will not get a response.
        } elseif ($result) {
            $result_vals = json_decode($result, true);
            if (!empty($result_vals['deletion_outcome']) &&
                stripos($result_vals['deletion_outcome'],'THREAD_REMOVED') !== false) {
                return true;
            }
        }
        Logger::Log('Viva Engage / Yammer Integration: Error '.$result);
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
Event Hosted by: {$hosted_by}<br/>
When: {$when}<br/>
Where: {$where}<br/>   
<br/>
{$description} <a href="{$url}">[view details & RSVP]</a><br/>
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
    public static function BuildPostMessage(int $forUpdate,string $posted_by, string $title, string $description, string $url): array
    {
        $body = <<<EOMEOM
Posted in {$posted_by}<br/>
<br/>
{$description} <a href="{$url}">[view details]</a><br/>
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
        $body = <<<EOMEOM

Posted in {$posted_by}<br/>
<br/>
{$description} <a href="{$url}">[view details]</a><br/>
EOMEOM;
        return array ($body, $title, $url);
    }
}