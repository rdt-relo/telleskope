<?php

interface IntegrationExternalType
{
    /**
     * @param string $message Body of the message
     * @param string $title Title of the message
     * @param string $link This link can be used for unfurling
     * @return string messageid or empty string
     */
    public function createMessage(string $message, string $title, string $link): string;

    /**
     * @param string $messageid Id that needs to be updated
     * @param string $message Body of the message
     * @param string $title Title of the message
     * @param string $link This link can be used for unfurling
     * @return string messageid or empty string
     */
    public function updateMessage(string $messageid, string $message, string $title, string $link): string;

    /**
     * This method should be implemented in the subclass
     * @param string $messageid
     * @param string $title
     * @param string $message
     * @return bool true if delete was successful
     */
    public function deleteMessage(string $messageid,string $title, string $message): bool;

    /**
     * This method should be implemented in the subclass
     * @param int $forUpdate set to true if the message needs to built for updating external application
     * @param string $hosted_by
     * @param string $title
     * @param string $description
     * @param string $url
     * @param string $when
     * @param string $where
     * @return string[]
     */
    public static function BuildEventMessage(int $forUpdate, string $hosted_by, string $title, string $description, string $url, string $when, string $where): array;

    /**
     * This method should be implemented in subclass
     * @param int $forUpdate set to true if the message needs to built for updating external application
     * @param string $posted_by
     * @param string $title
     * @param string $description
     * @param string $url
     * @return string[]
     */
    public static function BuildPostMessage(int $forUpdate,string $posted_by, string $title, string $description, string $url): array;

    /**
     * This method should be implemented in the subclass
     * @param int $forUpdate set to true if the message needs to built for updating external application
     * @param string $posted_by
     * @param string $title
     * @param string $description
     * @param string $url
     * @return string[]
     */
    public static function BuildNewsletterMessage(int $forUpdate, string $posted_by, string $title, string $description, string $url): array;
}