<?php
require_once __DIR__ . '/IntegrationExternalType.php';

class SlackIntegrationExternalType implements IntegrationExternalType
{
    const SLACK_URI = 'https://slack.com/api/';

    private $access_token;
    private $slack_groupid;
    public function __construct(array $config)
    {
        $this->access_token = $config['external']['access_token'];
        $this->slack_groupid = $config['external']['slack_groupid'];
    }

    public function createMessage(string $message, string $title, string $link): string
    {
        $url = self::SLACK_URI . 'chat.postMessage';
        $headers = array(
            'Authorization: Bearer ' . $this->access_token,
            'Content-type: application/json',
            'charset:utf-8'
        );
        $block =  array(
            array(
                "type"=>"header",
                "text"=>array(
                    "type"=>"plain_text",
                    "text"=>$title
                )
            ),
            array(
                "type"=>"section",
                "text"=>array(
                    "type"=>"mrkdwn",
                    "text"=>$message
                )
            ),
        );

        $fields = array(
            'channel' => $this->slack_groupid,
            'blocks' => $block
        );
       
        $result = post_curl($url, $headers, json_encode($fields));
        if ($result === false) {
            $result = 'Connection Error';
        } elseif ($result) {
            $result_vals = json_decode($result, true);

            if (!empty($result_vals['ts'])) {
                return $result_vals['ts'];
            }
        }
        Logger::Log('Slack Integration: Error '.$result);
        return '';
        
    }

    public function updateMessage(string $messageid, string $message, string $title, string $link): string
    {
        $url = self::SLACK_URI . 'chat.postMessage';
        $headers = array(
            'Authorization: Bearer ' . $this->access_token,
            'Content-type: application/json',
            'charset:utf-8'
        );
        $block =  array(
            array(
                "type"=>"header",
                "text"=>array(
                    "type"=>"plain_text",
                    "text"=>$title
                )
            ),
            array(
                "type"=>"section",
                "text"=>array(
                    "type"=>"mrkdwn",
                    "text"=>$message
                )
            ),
        );

        $fields = array(
            'channel' => $this->slack_groupid,
            'ts' =>$messageid,
            'blocks' => $block
        );
//        Logger::Log('Slack Integration: channel '.$this->slack_groupid);
//        Logger::Log('Slack Integration: messageid '.$messageid);
        $result = post_curl($url, $headers, json_encode($fields)); 
        if ($result === false) {
            $result = 'Connection Error';
        } elseif ($result) {
            $result_vals = json_decode($result, true);
            if (isset($result_vals['ok'])
                && !empty($result_vals['ts'])) {
                return $result_vals['ts']; // Message updated, return the same message id
            } elseif ((isset($result_vals['error']) && ($result_vals['error'] == 'message_not_found'))
            ) {
                // Message deleted on Slack ... recreate it
                return $this->createMessage($message, $title, $link);
            }
        }
        Logger::Log('Slack Integration: Error '.$result);
        return '';
        
    }

    public function deleteMessage(string $messageid,string $title,string $message): bool
    {
        $url = self::SLACK_URI . 'chat.delete';
        $headers = array(
            'Authorization: Bearer ' . $this->access_token,
            'Content-type: application/json',
            'charset:utf-8'
        );

        $fields = array(
            'channel' => $this->slack_groupid,
            'ts' =>$messageid
        );
        $result = post_curl($url, $headers, json_encode($fields)); 
        if ($result === false) {
            $result = 'Connection Error';
        } elseif ($result) {
            $result_vals = json_decode($result, true);
            if (isset($result_vals['ok'])
            ) {
                return $this->createMessage($message,$title,'');
            } 
        }
        Logger::Log('Slack Integration: Error '.$result);
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
        $newOrUpdated = $forUpdate == -1 ? "Deleted" :( $forUpdate ? 'Updated' : 'New') ;
        $eventUrl = $forUpdate != -1 ? "<$url|View Details & RSVP>" : '';
        $body =  "*{$newOrUpdated} Event*\n>Event Hosted by: {$hosted_by}\n>When: {$when}\n>Where: {$where}\n>\n>{$description}\n>".$eventUrl;
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
        $newOrUpdated = $forUpdate == -1 ? "Deleted" :( $forUpdate ? 'Updated' : 'New') ;
        $postUrl = $forUpdate != -1 ? "<$url|View Details>" : '';
        $body = "*{$newOrUpdated} Announcement*\n>Posted in {$posted_by}\n>{$description}\n>".$postUrl;
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
        $newOrUpdated = $forUpdate == -1 ? "Deleted " :'' ;
        $newsletterUrl = $forUpdate != -1 ? "<$url|View Details>" : '';

        $body = "*{$newOrUpdated}Newsletter*\n>Posted in {$posted_by}\n{$description}\n>".$newsletterUrl;
        return array ($body, $title, $url);
    }

}