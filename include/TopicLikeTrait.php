<?php

// Topic Like provides utility methods to Like, Unlike a topic.
trait TopicLikeTrait
{
    abstract protected static function GetTopicType():string;
    /**
     * Sets the user record for user...likes ... topic
     * @param int $topicid
     * @param string $reactiontype
     * @return bool
     */
    private static function Like(int $topicid, string $reactiontype): bool
    {
        global $_USER, $_COMPANY;
        $topictype = self::GetTopicType();

        if (self::DBMutate("INSERT INTO topic_likes(topicid, topictype, companyid, userid, reactiontype) VALUES ('{$topicid}','{$topictype}','{$_COMPANY->id()}','{$_USER->id()}','{$reactiontype}')")) {
            $key = "{$topictype}_LKC_TYPE:{$topicid}";
            $_COMPANY->expireRedisCache($key);
            return true;
        }
        return false;
    }

    private static function UpdateLike(int $topicid, string $reactiontype): bool
    {
        global $_USER, $_COMPANY;
        $topictype = self::GetTopicType();

        if (self::DBMutate("UPDATE `topic_likes` SET `reactiontype` = '{$reactiontype}' WHERE companyid='{$_COMPANY->id()}' AND userid='{$_USER->id()}' AND topicid='{$topicid}' AND topictype='{$topictype}'")) {
            $key = "{$topictype}_LKC_TYPE:{$topicid}";
            $_COMPANY->expireRedisCache($key);
            return true;
        }
        return false;
    }

    /**
     * Delete the record for user...likes ... topic
     * @param int $topicid
     * @return bool
     */
    private static function Unlike(int $topicid):bool
    {
        global $_USER, $_COMPANY;
        $topictype = self::GetTopicType();

        // Using DBMutate instead of DBMutatePS as there is no externally provided string
        if (self::DBMutate("DELETE FROM topic_likes WHERE companyid='{$_COMPANY->id()}' AND topicid='{$topicid}' AND topictype='{$topictype}' AND userid='{$_USER->id()}'")) {
            $key = "{$topictype}_LKC_TYPE:{$topicid}";
            $_COMPANY->expireRedisCache($key);
            return true;
        }
        return false;
    }

    /**
     * Toggles the records for user...likes/unlikes ...topic
     * @param int $topicid
     * @param string $reactiontype
     * @return bool
     */
    public static function LikeUnlike(int $topicid, string $reactiontype) : bool
    {
        global $_USER, $_COMPANY;
        $topictype = self::GetTopicType();

        if (!in_array($reactiontype, ['like','celebrate','support','love','insightful','gratitude'])) {
            return false;
        }

        $check = self::DBGet("SELECT topicid, reactiontype FROM topic_likes WHERE companyid='{$_COMPANY->id()}' AND userid='{$_USER->id()}' AND topicid='{$topicid}' AND topictype='{$topictype}'");

        if (($check[0]['reactiontype'] ?? '') === $reactiontype) {
            return self::Unlike($topicid);
        }

        if (isset($check[0])) {
            return self::UpdateLike($topicid, $reactiontype);
        }

        return self::Like($topicid, $reactiontype);
    }

    /**
     * Return the count of number of times the topic has been liked
     * @param int $topicid
     * @param string|null $reactiontype
     * @return int
     */
    public static function GetLikeTotals(int $topicid, ?string $reactiontype = null) : int
    {
        $likeTotalsByType = self::GetLikeTotalsByType($topicid);
        $count = 0;
        if ($reactiontype) {
            $likeTotalsByType = array_column($likeTotalsByType, 'cc', 'reactiontype');
            $count = $likeTotalsByType[$reactiontype] ?? 0;
        } else {
            $count = Arr::SumColumnValues($likeTotalsByType,'cc');
        }
        return $count;
    }

    public static function GetLikeTotalsByType(int $topicid): array
    {
        global $_COMPANY;
        $topictype = self::GetTopicType();
        $key = "{$topictype}_LKC_TYPE:{$topicid}";
        if (($likeTotalsByType = $_COMPANY->getFromRedisCache($key)) === false) {
            $likeTotalsByType = self::DBGet("SELECT count(1) as cc, reactiontype FROM topic_likes WHERE companyid='{$_COMPANY->id()}' AND topicid='{$topicid}' AND topictype='{$topictype}' GROUP BY reactiontype");
            $_COMPANY->putInRedisCache($key, $likeTotalsByType, 86400 * rand(10,12));
        }
        return $likeTotalsByType;
    }

    public static function GetUserReactionType(int $topicid) : string
    {
        global $_USER, $_COMPANY;
        $topictype = self::GetTopicType();
        $row = self::DBGet("SELECT reactiontype FROM topic_likes WHERE companyid='{$_COMPANY->id()}' AND userid='{$_USER->id()}' AND topicid='{$topicid}' AND topictype='{$topictype}' LIMIT 1");
        return $row[0]['reactiontype'] ?? '';
    }

    /**
     * Return true if the signed in user has liked the topci c
     * @param int $topicid
     * @return int 1 means like, 0 otherwise
     */
    public static function GetUserLikeStatus (int $topicid) : int
    {
        return !empty(self::GetUserReactionType($topicid));
    }

    public static function DeleteAllLikes (int $topicid)
    {
        global $_USER, $_COMPANY;
        $topictype = self::GetTopicType();
        return self::DBMutate("DELETE FROM topic_likes WHERE companyid='{$_COMPANY->id()}' AND topicid='{$topicid}' AND topictype='{$topictype}'");
    }

    public static function GetLatestLikers(int $topicid, bool $latest=true, int $limit = 10, int $page = 0)
    {
        global $_COMPANY;
        $topictype = self::GetTopicType();
        $orderBy = "ORDER BY topic_likes.createdon ASC";
        if ($latest){
            $orderBy = "ORDER BY topic_likes.createdon DESC";
        }

        $offset = '';
        if($page){
            $calcOffset = ($page - 1) * $limit;
            $offset = " OFFSET {$calcOffset}";
        }
        return self::DBROGet("SELECT topic_likes.userid, topic_likes.createdon,IFNULL(users.firstname,'Deleted') firstname,IFNULL(users.lastname,'User') lastname,users.picture,users.email,users.jobtitle, topic_likes.reactiontype FROM topic_likes LEFT JOIN users USING (userid) WHERE topic_likes.companyid='{$_COMPANY->id()}' AND topic_likes.topicid='{$topicid}' AND topic_likes.topictype='{$topictype}' {$orderBy} LIMIT {$limit} {$offset}");
    }
    public static function GetLatestLikersAnonymized(int $topicid, bool $latest=true, int $limit = 10)
    {
        $likers = self::GetLatestLikers($topicid, $latest=true, $limit = 10);
        foreach ($likers as &$liker) {
            $liker['userid'] = -1;
            $liker['firstname'] = 'Anonymous';
            $liker['lastname'] = 'User';
            $liker['picture'] = '';
            $liker['jobtitle'] = '';
            $liker['email'] = '';
            $liker['anonymized'] = true;
        }
        return $likers;
    }
}	

