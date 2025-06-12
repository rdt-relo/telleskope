<?php
// Do no use require_once as this class is included in Company.php.

class TrainingVideo extends Teleskope {

	protected function __construct(int $id,int $cid,array $fields) {
		parent::__construct($id,$cid,$fields);
		//declaring it protected so that no one can create it outside this class.
	}

  /**
   * Function to get a pre-signed URL from S3 for PUT (upload video) or GET (read/stream video)
   * @param $file_name string  name of the file
   * @param $upload_command 'PutObject' for upload,  'GetObject' for download
   * @param $company_id int company id
   * @returns string
   */
  public static function GetPreSignedURL(string $file_name, string $upload_command, int $company_id) : string
  {
    $s3 = Aws\S3\S3Client::factory([
      'version' => 'latest',
      'region' => S3_REGION
    ]);

    $subfolder = 'common/';
    if ($company_id > 0) {
        // ToDo  (for company we will use `companies`.`s3_folder` db value instead of "common") No need to create folders in S3 manually.
        // this will be needed once we allow companies to upload their own videos.
    }

    $s3name = 'training_videos/' . $subfolder . basename($file_name);

    //Creating a pre-signed URL
    $cmd = $s3->getCommand($upload_command, [
      'Bucket'=>S3_TRAINING_VIDEO_BUCKET,
      'Key'=>$s3name
    ]);

    $request = $s3->createPresignedRequest($cmd, '+20 minutes');

    return (string)$request->getUri();
  }

  /**
   * Function to record S3 video upload in database
   * @param $file_name string  name of the video file
   * @returns int
   */
  public static function RegisterVideo(string $file_name) : int
  {
    return self::DBInsertPS("INSERT INTO `training_videos` (`filename`)  SELECT '" . $file_name . "' AS `filename`; ");
  }

  /**
   * Function to record S3 video upload in database
   * @param $video_id int video id
   * @param $company_id int company id
   * @returns bool
   */
  public static function DeleteVideo(int $video_id, int $company_id) : bool
  {

    // delete video on the AWS S3 bucket. Only on success of that delete from database.
    $s3 = Aws\S3\S3Client::factory([
      'version' => 'latest',
      'region' => S3_REGION
    ]);

    $subfolder = 'common/';
    if ($company_id > 0) {
      // ToDo  (for company we will use `companies`.`s3_folder` db value instead of "common") No need to create folders in S3 manually.
    }

    $row = self::DBGetPS("SELECT `filename` FROM `training_videos` WHERE `video_id` = " . $video_id . ";");
    if (!sizeof($row)) {
        Logger::Log("Fatal Error in TrainingVideo->DeleteVideo({$video_id}) , video not found ");
        return false;
    }

    $s3name = 'training_videos/' . $subfolder . basename($row[0]["filename"]);

    $retVal = false;
    try {
      $s3->deleteObject([
        'Bucket' => S3_TRAINING_VIDEO_BUCKET,
        'Key' => $s3name
      ]);
      $retVal = true;
    }catch(\Exception $e){
      Logger::Log("Fatal Error in Company->deleteFileFromSafe({$s3name}) ".$e->getMessage());
      return false;
    }

    if ($retVal) {
        self::DBUpdate("DELETE FROM `training_videos` WHERE `video_id` = " . $video_id . ";");
        return true;
    }
    return $retVal;
  }

  /**
   * Function to get list of training videos for Affinities:show for company_id first, then for 0 (the ones that we added in /super)
   * @returns array
   */
  public static function GetFrontEndTrainingVideos() : array
  {
      // ToDo use company_id, from global
      $video_library = TrainingVideoLibrary::GetTrainingVideoLibrary();
      return $video_library->getTrainingVideos();
  }

   /**
    * Function to get all hashtags for Affinities: only for videos with company_id && 0 (the ones that we added in /super)
    * @returns array
    */
    public static function GetFrontEndHashTags() : array
    {
        // ToDo use company_id, from global
        $video_library = TrainingVideoLibrary::GetTrainingVideoLibrary();
        return $video_library->getFrontendHashtags();
    }

    /**
     * Function for Affinities: verify that for given tags there is at least 1 mapped video (only videos for current company (company_id) or Teleskope (0)).
     * @param $page_tags string comma-separated list of tags for the page
     * @returns bool
     */
    public static function DoTagsHaveVideos(string $page_tags) : bool
    {
        // ToDo use company_id, from global
        $video_library = TrainingVideoLibrary::GetTrainingVideoLibrary();
        $video_id_and_tag_labels = $video_library->getVideoIdAndTagnames();

        if ("" == trim($page_tags)) {
            return !empty($video_id_and_tag_labels);

        } else {

            $tags_array = explode(",",trim($page_tags));

            foreach ($video_id_and_tag_labels as $v) {
                if (in_array($v["label"],$tags_array)) {
                    return true;
                }
            }
            return false;
        }
    }

}

class TrainingVideoLibrary extends Teleskope {
    protected function __construct(array $fields)
    {
        parent::__construct(0,0,$fields);
    }

    public static function __set_state (array $properties ) : TrainingVideoLibrary {
        $retVal = new TrainingVideoLibrary($properties['fields']);
        $retVal->timestamp = $properties['timestamp'];
        return $retVal;
    }

    public static function GetTrainingVideoLibrary (bool $refresh = false) {
        $obj = null;
        $cachekey = sprintf(".TrainingVideoLibrary");
        // First look in the cache and validate cache has not expired (300 seconds)
        if (($obj = self::CacheGet($cachekey)) === null || (time() - $obj->timestamp) > 300 || $refresh) {
            $rows = self::DBROGet("SELECT companyid,subdomain FROM companies WHERE isactive=1");
            $fields = array();

            $training_videos = self::DBGet("
                SELECT * FROM `training_videos` `tv`
                    LEFT JOIN (
                        SELECT GROUP_CONCAT(`h`.`hashtag_id`) AS `tags`, `hv`.`video_id` AS `vid` 
                        FROM `training_hashtags` `h` 
                            JOIN `training_video_hashtags` `hv` ON `h`.`hashtag_id` = `hv`.`hashtag_id` GROUP BY `vid`) `t`
                        ON `tv`.`video_id` = `t`.`vid`
                HAVING `tv`.`is_active` = 1 
                   AND `widget_code` != ''
                ORDER BY `tv`.`video_id` DESC"
            );
            $fields['training_videos'] = empty($training_videos) ? array() : $training_videos;

            $frontend_hashtags = self::DBGet("
                SELECT DISTINCT(`label`), `h`.`hashtag_id` FROM `training_hashtags` `h`
                    JOIN `training_video_hashtags` `hv`
                        ON `h`.`hashtag_id` = `hv`.`hashtag_id`
                ORDER BY `h`.`label` DESC"
                );

            $fields['frontend_hashtags'] = empty($frontend_hashtags) ? array() : $frontend_hashtags;

            $video_id_tag_label = self::DBGet("
                SELECT `tv`.`video_id`, `h`.`label` FROM `training_videos` `tv`
                    JOIN `training_video_hashtags` `hv` 
                        ON `tv`.`video_id` = `hv`.`video_id`
                        AND `tv`.`widget_code` != ''
                        AND `tv`.`is_active` = 1 
                        AND `tv`.`companyid`=0
                    JOIN `training_hashtags` `h`  
                    ON `h`.`hashtag_id` = `hv`.`hashtag_id`"
            );
            $fields['video_id_tag_label'] = empty($video_id_tag_label) ? array() : $video_id_tag_label;

            self::CacheSet($cachekey, new TrainingVideoLibrary($fields));
            $obj = self::CacheGet($cachekey);
        }

        return $obj;
    }

    public function getTrainingVideos()
    {
        return $this->fields['training_videos'] ?: [];
    }

    public function getFrontendHashtags()
    {
        return $this->fields['frontend_hashtags'] ?: [];
    }

    public function getVideoIdAndTagnames()
    {
        return $this->fields['video_id_tag_label'] ?: [];
    }
}
