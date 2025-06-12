<?php
require_once __DIR__ . '/IntegrationExternalType.php';

class TeamsIntegrationExternalType implements IntegrationExternalType
{
    private $access_token;
    
    public function __construct(array $config)
    {
        $this->access_token = $config['external']['access_token'];
    }

    public function createMessage(string $message, string $title, string $link): string
    {
        $post_message = array(
            
                "@type" => "MessageCard",
                "themeColor"=> "4e4ebf",
                "summary"=> $title,
                "sections"=> [array(
                    "activityTitle" => $title,
                    "activitySubtitle"=> $message
                )],
                "potentialAction" => [array(
                    "@type"=> "OpenUri",
                    "name"=> "View Details",
                    
                    "targets" => [array(
                        "os" => "default",
                        "uri"=> $link
                    )],
                )]
        );
        $json_message = json_encode($post_message); 
        // Set headers
        $headers = array(
            'Content-type: application/json',
            'Content-Length: ' . strlen($json_message)
        );
        $result = post_curl($this->access_token, $headers, $json_message); // Note for teams access_token is access_url
        if($result == 1){
            return true;
        }else{
            $result = 'Connection Error';
        }
        Logger::Log('Teams Integration: Error '.$result);
        return '';
    }

    public function updateMessage(string $messageid, string $message, string $title, string $link): string
    {
        
        if ($this->access_token) {
            return $this->createMessage($message, $title, $link);
        }
        Logger::Log('Teams Integration: Error ');
        return '';
    }

    public function deleteMessage(string $messageid,string $title, string $message): bool
    {
        if ($this->access_token) {
            return $this->createMessage($message, $title, '');
        }
        Logger::Log('Teams Integration: Error ');
        return '';
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
        $body =  <<<EOMEOM
        *{$newOrUpdated} Event*\n>Event Hosted by: {$hosted_by}<br/>
        When: {$when}<br/>
        Where: {$where}<br/>   
        <br/>
        {$description} <br/>
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
        $newOrUpdated = $forUpdate == -1 ? "Deleted" :( $forUpdate ? 'Updated' : 'New') ;
        $body = <<<EOMEOM
        *{$newOrUpdated} Announcement*\n> Posted in {$posted_by}<br/>
        <br/>
        {$description} <br/>
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
        $newOrUpdated = $forUpdate == -1 ? "Deleted " :'' ;
        $body = <<<EOMEOM
        *Newsletter*\n> Posted in {$posted_by}<br/>
        <br/>
        {$description}<br/>
        EOMEOM;
        return array ($body, $title, $url);
    }
}