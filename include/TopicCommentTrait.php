<?php

// Do no use require_once as this class is included in Company.php.

trait TopicCommentTrait
{

    abstract protected static function GetTopicType(): string;

    public static function CreateComment_2(int $topicid, string $comment, array $fileAttachment)
    {
        // Todo: Upload the file from this method.
        global $_COMPANY, $_USER, $db;
        $topictype = self::GetTopicType();
        $attachment_actual_name = '';
        $file_meta_json = '';
        if (!empty($fileAttachment['media']['name'])) {
            $file = Sanitizer::SanitizeFilename(basename($fileAttachment['media']['name']));
            $ext = $db->getExtension($file);
            $tmp = $fileAttachment['media']['tmp_name'];          
            $fileSize = self::sizeFilter($fileAttachment['media']['size']);
            $filenameWithoutExtension = substr(pathinfo($file,PATHINFO_FILENAME), 0, 127);
            $file = $filenameWithoutExtension . '.' . $ext; // Reconstruct the filename with detected extension.
            $attachment_actual_name = $topictype . '_' . teleskope_uuid() . "." . $ext;

            $file_meta_json = json_encode(array(
                'file_name' => $file,
                'file_id' => $attachment_actual_name,
                'file_size' => $fileSize,
                'file_ext' => $ext
            ));
            
            $uploaded_attachment = $_COMPANY->saveFileInCommentsArea($tmp, $attachment_actual_name, 'COMMENT');
            if (empty($uploaded_attachment)) {
                Logger::Log('saveFileInCommentsArea Unable to upload the comment attachment ' . $file_meta_json);
                return 0;
            }
        }

        ContentModerator::CheckBlockedWords($comment);

        // zoneid Will not considered anymore because we allow comment on event from non-home zone after (issue #2933:) we allow feature of zone filter on Global calendar.
        $retVal = self::DBInsertPS("INSERT INTO topic_comments (companyid, userid, topicid, topictype, comment, attachment, createdon, modifiedon) VALUES (?, ?, ?, ?, ?, ?, NOW(),NOW())", 'iiixxx', $_COMPANY->id, $_USER->id, $topicid, $topictype, $comment, $file_meta_json);
        $comment_count_key = "{$topictype}_CMC:{$topicid}";
        $_COMPANY->expireRedisCache($comment_count_key);

        // If this comment was on another comment then update the subcomment count of the parent comment
        if ($retVal && $topictype == self::TOPIC_TYPES['COMMENT']) {
            self::_UpdateSubcommentCount($_COMPANY, $topicid, $topictype);
        }

        Points::HandleTrigger('NEW_COMMENT', [
            'commentId' => $retVal,
        ]);

        return $retVal;
    }

    public static function sizeFilter($bytes)
    {
        $label = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB' );
        for( $i = 0; $bytes >= 1024 && $i < ( count( $label ) -1 ); $bytes /= 1024, $i++ );
        return( round( $bytes, 2 ) . " " . $label[$i] );
    }

    public static function UpdateComment_2(int $topicid, int $commentid, string $comment)
    {
        global $_COMPANY;

        ContentModerator::CheckBlockedWords($comment);

        return self::DBMutatePS("UPDATE topic_comments SET comment=?,modifiedon=NOW() WHERE companyid=? AND commentid=? AND topicid=?", 'xiii', $comment, $_COMPANY->id(), $commentid, $topicid);
    }

    public static function GetComments_2(int $topicid, int $start = 0, $batch = 100)
    {
        global $_COMPANY, $_USER;
        $topictype = self::GetTopicType();

        return self::DBGet("SELECT topic_comments.*,IFNULL(users.firstname,'Deleted') as firstname,IFNULL(users.lastname,'User') as lastname,users.picture,users.jobtitle,users.email FROM topic_comments LEFT JOIN users USING (userid, companyid) WHERE topic_comments.companyid={$_COMPANY->id()} AND topic_comments.topicid={$topicid} AND topic_comments.topictype='{$topictype}' ORDER BY topic_comments.commentid DESC LIMIT {$start},{$batch}");
    }

    public static function GetCommentsAnonymized_2(int $topicid, int $start = 0, $batch = 100)
    {
        $comments = self::GetComments_2($topicid, $start, $batch);
        foreach ($comments as &$comment) {
            // $comment['userid'] = -1;
            $comment['firstname'] = 'Anonymous';
            $comment['lastname'] = 'User';
            $comment['picture'] = '';
            $comment['jobtitle'] = '';
            $comment['email'] = '';
            $comment['anonymized'] = true;
        }
        unset($comment);
        return $comments;
    }

    /**
     * Deletes all comments, comment likes and subcomments for a given topic.
     * @param int $topicid
     * @return int
     */
    public static function DeleteAllComments_2(int $topicid)
    {
        global $_COMPANY;
        $topictype = self::GetTopicType();
        $comments = self::DBGet("SELECT commentid FROM topic_comments WHERE companyid={$_COMPANY->id()} AND topicid={$topicid} AND topictype='{$topictype}'");
        foreach ($comments as $comment) {
            self::DeleteComment_2($topicid, $comment['commentid']);
        }
        return 1;
    }

    /**
     * Deletes a comment, comment likes and all subcomments for a given comment.
     * @param int $topicid
     * @param int $commentid
     * @return int
     */
    public static function DeleteComment_2(int $topicid, int $commentid)
    {
        $retval = self::_DeleteComment($topicid, $commentid);

        /**
         * Side-effect of the wrong topictype added when a comment is made on an album-media
         * In topic_comments table, when a comment is added to an album-media, the topictype is ALM, it should had been ALBMED
         * Side-effect experienced in the points-module (Points::GetTriggerContext, DELETE_COMMENT)
         *
         * Read comment on Album::GetTopicType() method
         *
         * Even though we are in Album class, we are setting the topic as ALBUM_MEDIA as likes and comments work at
         * individual media level ... not at the album level.
         * Ideally we should have a seperate class for Album Media  ... a long term @todo
         */
        $topictype = self::GetTopicType();
        if ($topictype === 'ALM') {
            $topictype = 'ALBMED';
        }

        Points::HandleTrigger('DELETE_COMMENT', [
            'commentId' => $commentid,
            'topicid' => $topicid,
            'topictype' => $topictype,
        ]);

        return $retval;
    }

    private static function _DeleteComment(int $topicid, int $commentid, int $recursionIteration = 0)
    {
        global $_COMPANY;
        // For the first iteration get TopicType from the calling class, otherwise force it to Comment Type
        $topictype = ($recursionIteration) ? self::TOPIC_TYPES['COMMENT'] : self::GetTopicType();
        $rec = self::DBGet("SELECT attachment,subcomment_count FROM topic_comments WHERE companyid={$_COMPANY->id()} AND commentid={$commentid} AND topicid={$topicid} AND topictype='{$topictype}'");

        if (empty($rec)) {
            return 1;
        }

        if (!empty($rec[0]['subcomment_count'])) {
            // Get all subcomments and recursively delete them
            $comment_topictype = self::TOPIC_TYPES['COMMENT'];
            $subcomments = self::DBGet("SELECT commentid FROM topic_comments WHERE companyid={$_COMPANY->id()} AND topicid={$commentid} AND topictype='{$comment_topictype}'");

            foreach ($subcomments as $subcomment) {
                self::_DeleteComment($commentid, $subcomment['commentid'], $recursionIteration + 1);
            }
        }

        if (!empty($rec[0]['attachment'])) {
            $jsonData = json_decode($rec[0]['attachment'], true);            
            $_COMPANY->deleteFileFromCommentsArea($jsonData['file_id'], 'COMMENT');
        }

        if (
            self::DBMutate("DELETE FROM topic_comments WHERE companyid={$_COMPANY->id()} AND commentid={$commentid} AND topictype='{$topictype}'")
            && Comment::DeleteAllLikes($commentid)) {

            // Since this is a recursive delete, we will update subcomment count only for the root comment if the topic
            // was comment
            if ($recursionIteration == 0 && $topictype == self::TOPIC_TYPES['COMMENT']) {
                self::_UpdateSubcommentCount($_COMPANY, $topicid, $topictype);
            }

            // Clear redis cache
            $comment_count_key = "{$topictype}_CMC:{$topicid}";
            $_COMPANY->expireRedisCache($comment_count_key);

            return 1;
        }

        return 0;
    }

    /**
     * @param $_COMPANY
     * @param int $topicid
     * @param string $topictype
     */
    private static function _UpdateSubcommentCount($_COMPANY, int $topicid, string $topictype): void
    {
        $subcomment_count = (int)self::DBGet("SELECT count(1) as cc FROM topic_comments WHERE companyid={$_COMPANY->id()} AND topicid={$topicid} AND topictype='{$topictype}'")[0]['cc'];
        self::DBMutate("UPDATE topic_comments SET subcomment_count={$subcomment_count} WHERE commentid={$topicid}");

        // The topicid here is a actually comment id as sub comments have parent comment. And we want to fetch the parent comment and expire its cache.
        $parent = self::DBGet("SELECT topicid, topictype FROM topic_comments WHERE companyid={$_COMPANY->id()} AND commentid={$topicid}");
        if (!empty($parent)) {
            $parent_topic_type = $parent[0]['topictype'];
            $parent_topic_id = $parent[0]['topicid'];
            $comment_count_key = "{$parent_topic_type}_CMC:{$parent_topic_id}";
            $_COMPANY->expireRedisCache($comment_count_key);
        }
    }

    /**
     * @param $_COMPANY
     * @param int $commentid
     */
    public static function GetCommentDetail(int $commentid, bool $autofix_topictype = false)
    {
        global $_COMPANY;
        $row = null;
        $c = self::DBGet("SELECT topic_comments.*,IFNULL(users.firstname,'Deleted') as firstname,IFNULL(users.lastname,'User') as lastname,users.picture,users.jobtitle FROM topic_comments LEFT JOIN users USING (userid, companyid) WHERE topic_comments.companyid={$_COMPANY->id()} AND topic_comments.commentid={$commentid}");

        if (empty($c)) {
            return null;
        }

            $row = $c[0];

        /**
         * Side-effect of the wrong topictype added when a comment is made on an album-media
         * In topic_comments table, when a comment is added to an album-media, the topictype is ALM, it should had been ALBMED
         * Side-effect experienced in the points-module (Points::GetTriggerContext, NEW_COMMENT)
         *
         * Read comment on Album::GetTopicType() method
         *
         * Even though we are in Album class, we are setting the topic as ALBUM_MEDIA as likes and comments work at
         * individual media level ... not at the album level.
         * Ideally we should have a seperate class for Album Media  ... a long term @todo
         */
        if ($autofix_topictype && ($row['topictype'] === 'ALM')) {
            $row['topictype'] = 'ALBMED';
        }

        return $row;
    }

    /**
     * @param $_COMPANY
     * @param int $file_name
     */
    public static function DownloadAttachment(string $file_name)
    {
        global $_COMPANY;
        return $_COMPANY->getFileFromCommentsArea($file_name, 'COMMENT');
    }


    /**
     * This method returns count of all comments for a given topic for a given topic.
     * @param int $topicid
     * @return int
     */
    public static function GetCommentsTotal(int $topicid)
    {
        global $_COMPANY;
        $topictype = self::GetTopicType();

        $comment_count_key = "{$topictype}_CMC:{$topicid}";
        $count = 0;
        if (($count = $_COMPANY->getFromRedisCache($comment_count_key)) === false) {
            $count = (int)self::DBGet("SELECT IFNULL((count(1)+SUM(`subcomment_count`)),0) as totalComments FROM `topic_comments` WHERE companyid='{$_COMPANY->id()}' AND `topicid`='{$topicid}' AND `topictype`='{$topictype}' ")[0]['totalComments'];
            $_COMPANY->putInRedisCache($comment_count_key, $count, 86400 * rand(10,12)); // put it in cache for 10 to 12 days.
        }
        return (int)$count;
    }
}
