<?php
// Do no use require_once as this class is included in Company.php.

class Album extends Teleskope {

    protected $attachments;
    protected $comments;

    private $albumMediaList = null;
    private $albumMediaListForFeed = null;
    private $albumTotalLikes = null;
    private $albumTotalComments = null;

    const ALBUM_UPLOAD_OPTIONS = array ('leads','leads_and_members');

	protected function __construct(int $id,int $cid,array $fields) {
		parent::__construct($id,$cid,$fields);

	}
    
    public function getAlbumTotalLikes(): int
    {
        if ($this->albumTotalLikes === null) {
            // Not initialized yet, lets do it now.
            $this->getAlbumMediaList(true);
        }
        return $this->albumTotalLikes;
    }

    public function getAlbumTotalComments(): int
    {
        if ($this->albumTotalComments === null) {
            // Not initialized yet, lets do it now.
            $this->getAlbumMediaList(true);
        }
        return $this->albumTotalComments;
    }

    // Even though we are in Album class, we are setting the topic as ALBUM_MEDIA as likes and comments work at
    // individual media level ... not at the album level.
    // Ideally we should have a seperate class for Album Media  ... a long term @todo
    // Add traits right after the constructor
    public static function GetTopicType():string {return self::TOPIC_TYPES['ALBUM_MEDIA'];}
    use TopicLikeTrait;
    use TopicCommentTrait;

    /**
     * Get Album Object
     */

    public static function GetAlbum(int $id) {
		$obj = null;
        global $_COMPANY; /* @var Company $_COMPANY */
			//  AND isactive=1
		$r1 = self::DBGet("SELECT * FROM albums WHERE albumid='{$id}' AND companyid = '{$_COMPANY->id()}'");

		if (!empty($r1)) {
			$obj = new Album($id, $_COMPANY->id(), $r1[0]);
		} else {
			Logger::Log("Album: GetAlbumObject({$id}) failed. Context (Company={$_COMPANY->id()})", Logger::SEVERITY['WARNING_ERROR']);
		}
		return $obj;
	}

    /**
     * Function to get a pre-signed URL from S3 for PUT (upload media) or GET (read/stream media)
     * @param $file_name_uuid string  hashed name of the file, for masking, security and uniqueness
     * @param $album_id int album id
     * @param int $zone_id Zoneid
     * @param string $upload_command 'PutObject' for upload,  'GetObject' for download
     * @param int $onlyThumbnail if true thumbnail is provided.
     * @param int $forceToDownload, if true url will result in download
     * @return string
     * @params
     */
    public static function GetPreSignedURL(string $file_name_uuid, int $album_id, int $zone_id, string $upload_command, int $onlyThumbnail=0, int $forceToDownload = 0) : string
    {
        global $_COMPANY;

        $s3 = Aws\S3\S3Client::factory([
            'version' => 'latest',
            'region' => S3_REGION
        ]);
        if ($onlyThumbnail){
            if (substr($file_name_uuid, -4) == '.mp4') {
                $file_name_uuid = str_replace('.mp4','.jpg',$file_name_uuid); // Video thumbnails are in JPG
            }
            $s3name = 'album_media/' . $_COMPANY->val('s3_folder') . '/'. $zone_id . '/'. $album_id . '/thumbnail_200_200/'. $file_name_uuid;
        } else {
            $s3name = 'album_media/' . $_COMPANY->val('s3_folder') . '/'. $zone_id . '/'. $album_id . '/'. $file_name_uuid;
        }

        //Creating a pre-signed URL
        if ($upload_command === 'GetObject') {
            // All get URLs have start time of past sunday and expire time of upcoming sunday.
            $requestStartTime = strtotime('last sunday');
            $requestEndTime = strtotime('sunday')-3600; // Remove one hour for DST changes
            $secondsInOneWeek = 604800; // 1 Week, cached objects will expire in one week.

            if ($requestEndTime - time() < 7200 ) { // If request end time is less than two hour then add a day
                $secondsInOneDay = 86400;
                $requestStartTime += $secondsInOneDay;
                $requestEndTime += $secondsInOneDay;
            }

            $expireCommand = $requestEndTime; // Get objects url expire on sundays
            $s3meta = [
                'Bucket'=>S3_ALBUM_BUCKET,
                'Key'=>$s3name,
                'ResponseCacheControl' => "public, max-age={$secondsInOneWeek}, immutable"
            ];

            if ($forceToDownload ){
                $s3meta['ResponseContentType'] = 'application/octet-stream';
                $s3meta['ResponseContentDisposition'] = "attachment; filename={$file_name_uuid}";
            }
            $cmd = $s3->getCommand($upload_command,  $s3meta);

            $request = $s3->createPresignedRequest($cmd, $requestEndTime, ['start_time' => $requestStartTime]);
        } else {
            $cmd = $s3->getCommand($upload_command, [
                'Bucket'=>S3_ALBUM_BUCKET,
                'Key'=>$s3name
            ]);
            $request = $s3->createPresignedRequest($cmd, '+20 minutes'); // Upload URL's expire in 20 minutes
        }

        return (string)$request->getUri();
    }

    /**
     * Function to record S3 media upload in database
     * @param $media_uuid string name of the media file
     * @param $ext string  type of the media file
     * @returns int
     */
    public function registerMedia(string $media_uuid, string $ext, string $alt_text) : int
    {
        global $_USER;

        return self::DBInsertPS(
            "INSERT INTO `album_media` (`albumid`,`media`,`media_alt_text`,`type`,`createdon`,`userid`,`sorting_order`)  
                VALUES (?, ?, ?, ?, NOW(), ?, (SELECT IFNULL(MAX(inner_am.sorting_order),0)+1 FROM album_media inner_am WHERE inner_am.albumid={$this->id()}))",'isssi', $this->id(), $media_uuid, $alt_text, $ext,$_USER->id()
        );
    }

    /**
     * Function to get S3 media preview data from database
     * @param $media_id int album media S3 hashed filename
     * @returns array with: media_uuid and widget_code - generated in this function based on media type (i.e. code for video or for image)
     */
    public static function GetMedia(int $media_id) : array
    {
        global $_COMPANY;
        $row = self::DBGetPS("SELECT `am`.`media` AS `media_uuid`, `am`.`media_alt_text`, `am`.`type`, `am`.`album_mediaid`, `a`.`groupid` FROM `album_media` `am`
            JOIN `albums` `a` ON `a`.`albumid` = `am`.`albumid`
            WHERE `a`.`companyid` = ? AND `am`.`album_mediaid` = ?;", 'ii', $_COMPANY->id(), $media_id);

        if (!sizeof($row)) {
            // media not found
            return array();
        }

        $row = $row[0];

        if (in_array($row["type"], array("avi", "mov", "mpeg", "mp4", "wmv"))) {   // video
            $row["widget_code"] = '<video class="album_media_item" controls=""><source src="[MEDIA_URL_PLACEHOLDER]" type="video/mp4"></video>';
            $row["media_type"] = "video";
        } elseif (in_array($row["type"], array("png", "jpeg", "jpg", "gif"))) {    // image
            $row["widget_code"] = '<img alt="'.$row["media_alt_text"].'" class="album_media_item" src="[MEDIA_URL_PLACEHOLDER]">';
            $row["media_type"] = "image";
        }

        return $row;
    }

    /**
     * Generate a video tag for video identified with uuid, in album albumid in $zoneid in $_COMPANY
     * @param string $uuid
     * @param int $albumid
     * @param int $zoneid
     * @param bool $autoplay
     * @param bool $loop
     * @param bool $muted
     * @return string
     */
    public static function GetVideoTagForIframe(string $uuid, int $albumid, int $zoneid, bool $autoplay, bool $loop, bool $muted) : string
    {
        if (substr($uuid, -4) !== '.mp4') {
            return '';
        }
        $playback_options = $autoplay ? ' autoplay ' : '';
        $playback_options .= $loop ? ' loop ' : '';
        $playback_options .= $muted ? ' muted ' : '';
        $video_src_url = self::GetPreSignedURL($uuid, $albumid, $zoneid, 'GetObject', 0);

        return '<video width="100%" controls ' . $playback_options . '><source src="' . $video_src_url . '" type="video/mp4">Your browser does not support the video tag</video>';
    }

    // This method permanently deltes the Album and its objects
    public function deleteIt() {
        global $_COMPANY;

        foreach ($this->getAlbumMediaList() as $media) {
            self::DeleteMedia($media['album_mediaid'], $this->id());
        }

        $result =  self::DBMutate("DELETE FROM albums WHERE companyid={$_COMPANY->id()} AND albumid={$this->id}");

        if ($result) {
            // Expire redis cache
            $_COMPANY->expireRedisCache("ABM:{$this->id}");
            self::LogObjectLifecycleAudit('delete', 'albums', $this->id(), 0);
        }
        return $result;
    }

    /**
     * Function to record S3 media upload in database
     * @param $media_id int media id
     * @param $album_id int id of the album
     * @returns bool
     */
    public static function DeleteMedia(int $media_id, int $album_id) : bool
    {
        global $_COMPANY, $_ZONE;

        $s3 = Aws\S3\S3Client::factory([
            'version' => 'latest',
            'region' => S3_REGION
        ]);

        $media = self::DBGet("SELECT `media` AS `media_uuid`, `sorting_order` FROM `album_media` 
                    JOIN `albums` USING (albumid)
                    WHERE albums.companyid={$_COMPANY->id()} AND `album_mediaid`='{$media_id}'");

        if (!empty($media)) {

            $media_uuid = $media[0]['media_uuid'];
            $media_sorting_order = $media[0]['sorting_order'];

            $s3name = 'album_media/' . $_COMPANY->val('s3_folder') . '/'. $_ZONE->id() . '/'. $album_id . '/'. $media_uuid;

            try {
                $s3->deleteObject([
                    'Bucket' => S3_ALBUM_BUCKET,
                    'Key' => $s3name
                ]);
            }catch(\Exception $e){
                Logger::Log("Fatal Error in Album->DeleteMedia({$s3name}) ".$e->getMessage(), Logger::SEVERITY['WARNING_ERROR']);
                return false;
            }

// Since lambda created the thumbnail, it should remove it.
//            $s3name_thumbnail = 'album_media/' . $_COMPANY->val('s3_folder') . '/'. $_ZONE->id() . '/'. $album_id . '/thumbnail_300_300/'. $media_uuid;
//            try {
//                $s3->deleteObject([
//                    'Bucket' => S3_ALBUM_BUCKET,
//                    'Key' => $s3name_thumbnail
//                ]);
//            }catch(\Exception $e){
//                error_log("Fatal Error in Album->DeleteMedia({$s3name_thumbnail}) ".$e->getMessage());
//                return true;
//            }

            // delete all likes and comments of media
            self::DeleteAllComments_2($media_id);
            self::DeleteAllLikes($media_id);

            self::DBUpdate("DELETE FROM `album_media` WHERE `album_mediaid` = {$media_id}");
            self::DBMutate("UPDATE `album_media` SET `sorting_order` = `sorting_order`-1 WHERE `albumid` = {$album_id} AND `sorting_order` > {$media_sorting_order}");

            // Expire album cache for feed
            $_COMPANY->expireRedisCache("ABM:{$album_id}");

            return true;
        }
        return false;
    }

    /**
     * This function returns all the Albums for a given Group and and Chapter
     * @param int $groupid
     * @param int $chapterid
     * @return array
     */
    public static function GetGroupAlbums(int $groupid, int $chapterid, int $channelid,int $showGlobalChapterOnly=0,int $showGlobalChannelOnly=0, int $page = 1, int $perPage = 10,int $mobileRequest = 0) {
        global $_COMPANY, $_ZONE;      
        

		$chapterFilter="";

        if ($showGlobalChapterOnly){
            $chapterFilter = " AND `chapterid`=0";
        } else {
            if ($chapterid > 0){
                $chapterFilter = " AND FIND_IN_SET(" . $chapterid . ", chapterid) ";
            }
        }
        
        $channelFilter = "";
        if ($showGlobalChannelOnly){
            $channelFilter = " AND channelid=0 ";
        } else{
            if ($channelid > 0){
                $channelFilter = " AND channelid IN (" . $channelid . ") ";
            }
        }

        // Calculate the offset based on the page and perPage values
        $offset = ($page - 1) * ($perPage - 1);

        if($mobileRequest == 1)
        {
            $offset = ($page - 1) * $perPage;
        }
        

		$rows = self::DBGet("SELECT *, `a`.`albumid` AS `albumid`, `aai`.`media` AS `cover_photo` FROM `albums` `a`
                            LEFT JOIN `album_media` `aai` ON `a`.`cover_mediaid` = `aai`.`album_mediaid`
                            WHERE `companyid` = '{$_COMPANY->id()}'
                            AND `groupid` = '{$groupid}' 
                            AND `zoneid` = '{$_ZONE->id()}'
                            ".$chapterFilter." 
                            ".$channelFilter."                            
                            ORDER BY `a`.`albumid` DESC
                            LIMIT {$offset}, {$perPage}");

        for ($i = 0; $i < sizeof($rows); $i++ ) {

            $subrows = self::DBGet("SELECT `album_mediaid`,`media` as media_uuid,`type` as media_type FROM `album_media` WHERE `albumid` =  " . $rows[$i]["albumid"]);
            $totalCommentCounts = 0;
            $totalLikesCounts = 0;
            foreach($subrows as $subrow){
                $totalCommentCounts += Album::GetCommentsTotal($subrow['album_mediaid']);
                $totalLikesCounts +=  Album::GetLikeTotals($subrow['album_mediaid']);
            }

            $rows[$i]['total'] = count($subrows);

            $rows[$i]['totalLikes'] = (int)$totalLikesCounts;
            $rows[$i]['totalComments'] = (int)$totalCommentCounts;
            $rows[$i]['totalCounts'] = 0;
           
            $file_name_uuid =  ('0' != $rows[$i]['cover_mediaid']) ? $rows[$i]['cover_photo']: '';
            if ("" != $file_name_uuid && NULL != $file_name_uuid) {
                $rows[$i]['cover_photo'] = Album::GetPreSignedURL($file_name_uuid, (int)$rows[$i]['albumid'], $_ZONE->id(), 'GetObject');
            } else {
                $rows[$i]['cover_photo'] = '';  //
                // Try to assign the next subrow as cover photo
                $validImageTypes = array('png','jpg','jpeg');
                foreach ($subrows as $sr) {
                    if (in_array($sr['media_type'], $validImageTypes)) {
                        $new_cover_photo_id = $sr['album_mediaid'];
                        $new_cover_photo_uuid = $sr['media_uuid'];
                        self::SetMediaCover($new_cover_photo_id, $rows[$i]["albumid"]);
                        $rows[$i]["cover_photo"] = Album::GetPreSignedURL($new_cover_photo_uuid, (int)$rows[$i]["albumid"], $_ZONE->id(), 'GetObject');
                        break;
                    }
                }
            }
        }

		return $rows;

	}

    /**
     * Function to record S3 media upload in database
     * @param $album_id int $album_id
     * @returns bool
     */
    public static function DeleteAlbum(int $album_id) {

        global $_COMPANY;
        
        // Delete All likes and comments of album [Don't enable this!!! Currently Albums don't have likes or comments. Also Albums have to have a separate Topic ID]
        // self::DeleteAllLikes($album_id);
        // self::DeleteAllComments_2($album_id);

        $retVal = self::DBUpdate("DELETE FROM `albums` WHERE `albumid` = $album_id AND `companyid` = " . $_COMPANY->id());

        if ($retVal) {			
            $_COMPANY->expireRedisCache("ABM:{$album_id}");
            self::LogObjectLifecycleAudit('delete', 'album', $album_id, 0);                       
        }
        return $retVal;
    }


    /**
     * Function to record S3 media upload in database
     * @param $title string album title
     * @param $groupid int $groupid
     * @param $chapterids string $chapterids
     * @param $channelid int $channelid
     * @returns bool
     */
    public static function CreateAlbum(string $title, int $groupid, string $chapterids, int $channelid, string $whocanupload='leads') {

        global $_USER, $_COMPANY, $_ZONE; 

        if (!in_array($whocanupload, self::ALBUM_UPLOAD_OPTIONS)) {
            return 0;
        }
         	
        $retVal = self::DBInsertPS("INSERT INTO `albums` (`companyid`, `groupid`, `zoneid`, `chapterid`, `channelid`, `title`, `who_can_upload_media`, `cover_mediaid`, `userid`, `addedon`, `isactive`)
                     VALUES (?, ?, ?, ?, ?, ?, ?, '0',?, NOW(), '1')",'iiisisxi', $_COMPANY->id(), $groupid, $_ZONE->id(), $chapterids, $channelid, $title, $whocanupload, $_USER->id()
        );

        if ($retVal) {	            
            self::LogObjectLifecycleAudit('create', 'album', $retVal, 0);             
        }
        return $retVal;
    }

    /**
     * Function to update Album title
     * @param $title string album title
     * @param $albumid int $albumid
     * @returns bool
     */
    public static function UpdateAlbum(string $title, int $albumid, string $chapterids, int $channelid, string $whocanupload = 'leads') {

        global $_COMPANY;
       
        if (!in_array($whocanupload, self::ALBUM_UPLOAD_OPTIONS)) {
            return 0;
        }

        $retVal = self::DBUpdatePS("UPDATE `albums` SET `title` =  ?, `who_can_upload_media` = ?, `chapterid` = ? , `channelid` = ? 
                WHERE `albumid` = ? AND `companyid` = ?;",'sxsiii', $title, $whocanupload, $chapterids, $channelid, $albumid, $_COMPANY->id());

        if ($retVal) {		
            $_COMPANY->expireRedisCache("ABM:{$albumid}");	
            self::LogObjectLifecycleAudit('update', 'album', $albumid, 0);            
            
        }
        return $retVal;
    }

    /**
     * This function gets the album by id
     * @param int $album_id
     * @return array
     */
    public static function GetAlbumArray(int $album_id) {
        global $_COMPANY;

        return self::DBGet("SELECT * FROM `albums` WHERE `companyid`='{$_COMPANY->id()}' AND albumid={$album_id}");
    }

    /**
     * This function sets the album as cover photo
     * @param int $media_id
     * @param int $album_id
     * @return int
     */
    public static function SetMediaCover(int $media_id, int $album_id) {
			return self::DBMutate("UPDATE `albums` SET `cover_mediaid`='{$media_id}' WHERE `albumid`='{$album_id}' ");
	}


    public function getMediaDetail(int $media_id){
        global $_COMPANY, $_ZONE;
        $row = null;
        // Company id check not required as this method is class method and the only way class object can be
        // instantiated is by giving proper companyid in the context.
        $r = self::DBGet("SELECT * FROM `album_media` WHERE `album_mediaid`='{$media_id}' ");
        if (!empty($r)){
            $row = $r[0];
            $row['media_uuid'] = $row['media'];
            $row['media'] = self::GetPreSignedURL($row['media'], $this->id(), $_ZONE->id(), 'GetObject');
        }
        return $row;
    }

    public function getAlbumMediaList(bool $deepLoad = false){
        // Company id check not required as this method is class method and the only way class object can be
        // instantiated is by giving proper companyid in the context.
        if ($this->albumMediaList === null) {
            // Not initialized, lets do it now.
            $albumMediaList = self::DBGet("SELECT *, 0 as is_cover_media FROM `album_media` WHERE `albumid`='{$this->id()}' ");
            usort($albumMediaList, function ($item1, $item2) {
                return $item1['sorting_order'] <=> $item2['sorting_order'];
            });
            $this->albumMediaList = $albumMediaList;
        }

        if ($deepLoad && ($this->albumTotalLikes === null || $this->albumTotalComments === null)) {
            $this->albumTotalLikes = 0;
            $this->albumTotalComments = 0;
            foreach ($this->albumMediaList as &$media) {

                // Load comment count
                $media['comment_count'] = Album::GetCommentsTotal($media['album_mediaid']);
                $this->albumTotalComments += $media['comment_count'];

                // Load like count
                $media['like_count'] = Album::GetLikeTotals($media['album_mediaid']);
                $this->albumTotalLikes += $media['like_count'];

                // Load urls
                $media['thumbnail_url'] = Album::GetPreSignedURL($media["media"], $this->id(), $this->val('zoneid'), 'GetObject', 1);
            }
        }

        return $this->albumMediaList;
    }

    /**
     * @param int $media_id
     * @param int $oldSortingOrder
     * @param int $newSortingOrder
     * @return int
     */
    public function changeAlbumMediaPosition(int $media_id,int $oldSortingOrder, int $newSortingOrder)
    {
        $mediaRow = self::DBGet("SELECT sorting_order FROM album_media WHERE album_mediaid={$media_id}");
        $oldSortingOrder = (int) $mediaRow[0]['sorting_order'] ?? $oldSortingOrder;
        if ($newSortingOrder < $oldSortingOrder) {
            return self::DBMutate("UPDATE album_media SET sorting_order = IF (album_mediaid={$media_id},$newSortingOrder, sorting_order + 1) WHERE albumid={$this->id} AND sorting_order BETWEEN {$newSortingOrder} AND {$oldSortingOrder}");
        } else {
            return self::DBMutate("UPDATE album_media SET sorting_order = IF (album_mediaid={$media_id},$newSortingOrder, sorting_order - 1) WHERE albumid={$this->id} AND sorting_order BETWEEN {$oldSortingOrder} AND {$newSortingOrder}");
        }
    }

    /**
     * This method can be used to reset Album Media sort order to the same order in which the media was uploaded.
     * @return void
     */
    public function resetAlbumMediaPositionToDefault()
    {
        $album_media = self::DBGet("SELECT album_mediaid, sorting_order FROM album_media WHERE albumid={$this->id()} ORDER BY album_mediaid");
        $albumOrder = 0;
        foreach ($album_media as $media) {
            $albumOrder++;
            self::DBMutate("UPDATE album_media SET sorting_order={$albumOrder} WHERE album_mediaid={$media['album_mediaid']}");
        }
    }

    /**
     * This method checks if the logged in user ($_USER) can add/edit settings for Album
     * @return bool
     */
    public function loggedinUserCanManageAlbum() : bool
    {
        global $_USER;
        return $_USER->canCreateOrPublishContentInScopeCSV($this->val('groupid'),$this->val('chapterid'),$this->val('channelid'));
    }

    /**
     * This method checks if the logged in user ($_USER) can add/edit content in Album
     * @return bool
     */
    public function loggedinUserCanAddMedia(): bool
    {
        global $_USER;

        $allow = (
            (
                $this->val('who_can_upload_media') === 'leads_and_members' &&
                $_USER->isGroupMemberInScopeCSV($this->val('groupid'), $this->val('chapterid'), $this->val('channelid'))
            ) ||
            $this->loggedinUserCanManageAlbum()
        );

        return $allow;
    }

    /**
     * This method checks if the logged in user ($_USER) can delete specified media in Album
     * @param int $media_id
     * @return bool
     */
    public function loggedinUserCanDeleteMedia(int $media_id) : bool
    {
        global $_USER;

        $media_row = $this->getMediaDetail($media_id);

        $allow = (
            ($media_row['userid'] == $_USER->id()) ||
            $this->loggedinUserCanManageAlbum()
        );

        return $allow;
    }

    /**
     * Convert row of album table to Album object.
     * @param array $rec
     * @return Album|null
     */
    public static function ConvertDBRecToAlbum (array $rec): ?Album
    {
        global $_COMPANY;
        $obj = null;
        $a = (int)$rec['albumid'];
        $c = (int)$rec['companyid'];
        if ($a && $c && $c === $_COMPANY->id())
            $obj = new Album($a, $c, $rec);
        return $obj;
    }

    public static function GetAlbumFromCache (int $id) {
        global $_COMPANY;
        return self::GetAlbumByCompany($_COMPANY, $id);
    }

    public static function GetAlbumByCompany(Company $company, int $id) {
        global $_ZONE, $_COMPANY;
        $obj = null;
        $key = "ABM:{$id}";
        if (($obj = $company->getFromRedisCache($key)) === false) {
            $obj = null; // Reset $obj to initial value
            $r1 = self::DBROGet("SELECT * FROM albums WHERE companyid ='{$company->id()}' AND albumid='{$id}'");
            if (!empty($r1)) {
                $obj = new Album($id, $company->id(), $r1[0]);
                $obj->loadAlbumMediaList();
                if(empty($obj->albumMediaList)){
                    // If album do not have media, return false
                    return false;
                }
                $company->putInRedisCache($key, $obj, 300);
            }
        }
        return $obj;
    }

    public function loadAlbumMediaList()
    {
        $albumMediaList = $this->getAlbumMediaList(true);
        //if (count($albumMediaList) > 3) {

        if (empty($albumMediaList))
            return;

        // sort the images for likes + comments here
        usort($albumMediaList, function ($a, $b) {
            return $b['like_count'] + $b['comment_count'] <=> $a['like_count'] + $a['comment_count'];
        });

         // Place cover image first
        if(!empty($this->val('cover_mediaid'))) {
            $albumMediaList = $this->placeCoverImageFirst($albumMediaList);
        }
        //}
        $this->albumMediaListForFeed = $albumMediaList;
    }

    public function getAlbumMediaListForFeed()
    {
        if ($this->albumMediaListForFeed === null) {
            $this->loadAlbumMediaList();
        }
        return $this->albumMediaListForFeed ?? [];
    }



    private function placeCoverImageFirst($albumMediaList)
    {
        $coverImageId = $this->val('cover_mediaid');
        $coverImage = null;
        foreach ($albumMediaList as $key => $albumMedia) {
            if($albumMedia['album_mediaid'] == $coverImageId){
                $coverImage = $albumMedia;
                unset($albumMediaList[$key]);
                break;
            }
        }

        if($coverImage){
            array_unshift($albumMediaList, $coverImage);
        }
        return $albumMediaList;
    }
}
