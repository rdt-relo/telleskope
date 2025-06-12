<?php
// Do no use require_once as this class is included in Company.php.

class Comment extends Teleskope {
    private $topic_obj = null;

    // Add traits right after the constructor
    public static function GetTopicType():string {return self::TOPIC_TYPES['COMMENT'];}
    use TopicLikeTrait;
    use TopicCommentTrait;

    public static function GetComment(int $id): ?Self
    {
        global $_COMPANY;

        $comment = Self::GetCommentDetail($id, true);
        if (!$comment) {
            return null;
        }
        return new Self($id, $_COMPANY->id(), $comment);
    }

    /**
     * Used by points-module when a new comment is added to a comment (ie on reply to a comment)
     * Points-module is group-aware and wants to know the groupID of the trigger
     * Used in Points::GetTriggerContext, NEW_COMMENT
     */
    public function __getval_groupid(): int
    {
        $topic_obj = $this->getCommentTopicObj();
        return $topic_obj?->val('groupid') ?: 0;
    }

    /**
     * Used by points-module when a new comment is added to comment (ie on reply to a comment)
     * Points-module is group-aware and wants to know the groupID of the trigger
     * Used in Points::GetTriggerContext, NEW_COMMENT
     */
    public function __getval_collaborating_groupids(): string
    {
        $topic_obj = $this->getCommentTopicObj();
        return $topic_obj?->val('collaborating_groupids') ?: '';
    }

    private function getCommentTopicObj(): ?Teleskope
    {
        if ($this->topic_obj) {
            return $this->topic_obj;
        }

        $this->topic_obj = Teleskope::GetTopicObj($this->val('topictype'), $this->val('topicid'));
        return $this->topic_obj;
    }
}
