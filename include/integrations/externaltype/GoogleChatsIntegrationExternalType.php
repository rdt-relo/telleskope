<?php
require_once __DIR__ . '/IntegrationExternalType.php';

class GoogleChatsIntegrationExternalType implements IntegrationExternalType
{
    private $access_token;
    
    public function __construct(array $config)
    {
        $this->access_token = $config['external']['access_token'];
    }

    public function createMessage(string $message, string $title, string $link): string
    {
        $message_parts = json_decode($message, true);
        $post_message =  array( 
                "cardsV2" => [ array(
                  //"cardId"=> "createCardMessage",
                  "card"=> array(
                    "header"=> array(
                      "title"=> $title,
                      "subtitle"=> $message_parts['subtitle'],
                      "imageUrl"=> $message_parts['icon_url'],
                      "imageType"=> "SQUARE"
                    ),
                    "sections"=> [
                      array(
                        "collapsible" => false,
                        "widgets"=> [
                          array(
                           "decoratedText" => array (
                              "text" => $message_parts['section_body']
                            )
                          ),
                          array(
                            "buttonList"=> array(
                              "buttons"=> [
                                array(
                                  "text"=> "View Details",
                                  "onClick"=> array(
                                    "openLink"=> array(
                                      "url"=> $link
                                    )
                                  )
                                )
                              ]
                            )
                          )
                        ]
                      )
                    ]
                  )
                )]
        );
        // if ink is empty, it means the announcement/event is deleted. If URL is empty this webhook doesn't work. so we're unsetting the url widget from the array.
        if(empty($link)){
          unset($post_message["cardsV2"][0]['card']['sections'][0]['widgets'][1]);
        }
        $json_message = json_encode($post_message); 
        // Set headers
        $headers = array(
            'Content-type: application/json',
            'Content-Length: ' . strlen($json_message)
        );
        $result = post_curl($this->access_token, $headers, $json_message); // Note for teams access_token is access_url
        // Extract message id from the result.
        $decoded_result = json_decode($result, true);
        if (!empty($decoded_result['name'])) {
            $decoded_result_parts = explode('/', $decoded_result['name']);
            if (count($decoded_result_parts) == 4) {
                return $decoded_result_parts[3];
            }
        }
        Logger::Log('Google chat Integration: Error '.$result);
        return '';
    }

    public function updateMessage(string $messageid, string $message, string $title, string $link): string
    {
        // Since this is webhook integration, we can just let the user know message has been update instead of actually updating the original message
        if ($this->access_token) {
            return $this->createMessage($message, $title, $link);
        }
        Logger::Log('Google Chat Integration: Error missing token ');
        return '';
    }

    public function deleteMessage(string $messageid,string $title, string $message): bool
    {
        // Since this is webhook integration, we can just let the user know message has been deleted instead of actually deleting the message
        if ($this->access_token) {
            return $this->createMessage($message, $title, '');
        }
        Logger::Log('Google Chat Integration: Error missing token');
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
        $body = json_encode([
            'subtitle' => $newOrUpdated . ' Event' . ' hosted by ' . $hosted_by,
            'icon_url' => TELESKOPE_.._STATIC .'/static/gchat_icons/calendar.png',
            'section_body' =>
                "When: {$when}<br/>" .
                "Where: {$where}<br/>" .
                "<br/>" .
                "{$description}<br/>"
        ]);
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
        $body = json_encode([
            'subtitle' => $newOrUpdated . ' Announcement' . ' posted in ' . $posted_by,
            'icon_url' => TELESKOPE_.._STATIC .'/static/gchat_icons/megaphone.png',
            'section_body' => $description
        ]);
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
        $body = json_encode([
            'subtitle' => 'Newsletter' . ' posted in ' . $posted_by,
            'icon_url' => TELESKOPE_.._STATIC .'/static/gchat_icons/newsletter.png',
            'section_body' => $description
        ]);
        return array ($body, $title, $url);
    }
}