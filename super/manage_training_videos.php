<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::GlobalManageTrainingVideos);

unset($_SESSION['companyid']);

if(isset($_POST) && isset($_POST["data"])) {
  $post = json_decode($_POST["data"]);
  if (isset($post->action)) {
    if ("getUploadURL" == $post->action  && !empty($post->filename)) {

      $random_suffix = substr(md5(time()),0,10);
      $post->filename = preg_replace('/(\.mp4|\.avi|\.mov)/', '_'. $random_suffix .'$1', $post->filename);

      TrainingVideo::RegisterVideo($post->filename);
      $presigned_url = TrainingVideo::GetPreSignedURL($post->filename, 'PutObject', 0);
      echo  $presigned_url;
      exit;
    } elseif ("deleteVideo" == $post->action && !empty($post->video_id)) {
       $response = TrainingVideo::DeleteVideo($post->video_id, $post->companyid);
       echo '{"status":"' . $response . '"}';
       exit;

    } elseif ("getPreviewVideo" == $post->action && !empty($post->video_id)) {

      $db	= new Hems();
      $rows=$_SUPER_ADMIN->super_get("SELECT * FROM `training_videos` WHERE `video_id` = " . $post->video_id);

      if(!isset($rows) || !isset($rows[0])) {
        echo '{"status":"error", "details":"video_id not found in database"}';
        exit;
      }

      // get S3 GET URL
      $preview_url = TrainingVideo::GetPreSignedURL($rows[0]["filename"], 'GetObject', 0);

      // replace the placeholder in widget code
      $widget_code = str_replace('[TRAINING_VIDEO_PLACEHOLDER]', $preview_url, $rows[0]["widget_code"]);
      $label = $rows[0]["label"];

      // return JSON with label and widget_code
      echo json_encode(array(
          "status" => "success",
          "label" => $label,
          "widget_code" => $widget_code
        )
      );
      exit;
    }
  }
  exit;
}

$db	= new Hems();

$select = "SELECT * FROM `training_videos` `tv`
LEFT JOIN 
(SELECT GROUP_CONCAT(`h`.`label`) AS `tags`, `hv`.`video_id` AS `vid`
FROM `training_hashtags` `h` 
JOIN `training_video_hashtags` `hv` 
ON `h`.`hashtag_id` = `hv`.`hashtag_id` 
GROUP BY `vid`) `t`
ON `tv`.`video_id` = `t`.`vid`
ORDER BY `tv`.`video_id` DESC";
$rows=$_SUPER_ADMIN->super_get($select);

include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/manage_training_videos.html');
include(__DIR__ . '/views/footer.html');
?>
