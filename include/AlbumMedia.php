<?php

class AlbumMedia extends Teleskope
{
    public static function GetAlbumMedia(int $id): ?Self
    {
        global $_COMPANY;

        $album_media = Album::GetMedia($id);
        if (!$album_media) {
            return null;
        }

        return new Self($id, $_COMPANY->id(), $album_media);
    }

    /**
     * Had to add a new topictype of ALBUM_MEDIA_2 instead of ALBUM_MEDIA
     *
     * Read comment on Album::GetTopicType() method
     *
     * Even though we are in Album class, we are setting the topic as ALBUM_MEDIA as likes and comments work at
     * individual media level ... not at the album level.
     * Ideally we should have a seperate class for Album Media  ... a long term @todo
     */
    public static function GetTopicType():string
    {
        return self::TOPIC_TYPES['ALBUM_MEDIA_2'];
    }
}
