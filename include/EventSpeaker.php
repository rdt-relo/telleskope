<?php

class EventSpeaker extends Teleskope
{
    use TopicCustomFieldsTrait;

    public static function GetTopicType(): string
    {
        return self::TOPIC_TYPES['EVENT_SPEAKER'];
    }
}
