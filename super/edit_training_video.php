<?php
if(!(isset($_GET["param"]) && ((int)$_GET["param"])>0)) {
  header("location:manage_training_videos");
  exit();
}
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::GlobalManageTrainingVideos);

unset($_SESSION['companyid']);

$video_id = (int)$_GET["param"];

$db	= new Hems();
$pageTitle = "Configure Training Video";

if(isset($_POST) && isset($_POST["data"])) {
  $post = json_decode($_POST["data"]);
  if (isset($post->action) && "updateTrainingVideo" == $post->action && !empty($post->video_id)) {

    // drop all tags
    $_SUPER_ADMIN->super_update("DELETE FROM `training_video_hashtags` WHERE `video_id` = " . $post->video_id);

    // add new tags
    if (!empty($post->tags)) {
      $tags = explode(',', trim($post->tags));
      foreach($tags as $tag) {

        // insert into hashtag
        $tag = trim($tag);
        $_SUPER_ADMIN->super_insert("INSERT IGNORE INTO `training_hashtags` (`label`) VALUES ('" . $tag . "');");
        $rows=$_SUPER_ADMIN->super_get("SELECT `hashtag_id` FROM `training_hashtags` WHERE `label` = '" . $tag . "'");
        $hashtag_id = $rows[0]["hashtag_id"];

        // create connection to video
        $_SUPER_ADMIN->super_insert("INSERT IGNORE INTO `training_video_hashtags` (`video_id`, `hashtag_id`) VALUES (" . $post->video_id . "," . $hashtag_id . ");");
      }
    }

    // ToDo clear unused tags


    if (!$post->is_active) {
      $post->is_active = 0;
    }
    $widget_code = $post->widget_code;
    // Add '&nbsp' to all empty <p> tags. This will show empty <p> tags as newlines
    $widget_code = preg_replace('#<p></p>#','<p>&nbsp;</p>', $widget_code);

    $_SUPER_ADMIN->super_update("UPDATE `training_videos` SET `label` = '" . $post->label . "', `is_active` = " . $post->is_active . ", `widget_code` = '" . $widget_code . "' 
WHERE `video_id` = " . $post->video_id . ";");
      echo '{"status":"success"}';
      exit;
    }
  echo '{"status":"error", "details":"correct post data is missing"}';
  exit;
}

$db	= new Hems();
$hashtag_string = "";
$hashtag_select = "SELECT GROUP_CONCAT(`h`.`label`) AS `tags` FROM `training_hashtags` `h`
JOIN `training_video_hashtags` `hv` 
    ON `h`.`hashtag_id` = `hv`.`hashtag_id`
    AND `hv`.`video_id` = " . $video_id . "
ORDER BY `h`.`label` DESC";
$hrow=$_SUPER_ADMIN->super_get($hashtag_select);
if (sizeof($hrow) > 0) {
  $hashtag_string = $hrow[0]["tags"];
}


$select = "SELECT * FROM `training_videos` WHERE `video_id` = " . $video_id;
$rows=$_SUPER_ADMIN->super_get($select);

$sample_editor_code = '<div>' . PHP_EOL .
'<p>This is a sample top description.</p>' . PHP_EOL .
  '<video style="width:100%; height:100%;" controls>' . PHP_EOL .
  '<source src="[TRAINING_VIDEO_PLACEHOLDER]" type="video/mp4">' . PHP_EOL .
'</video>' . PHP_EOL .
'<p>This is a sample bottom description.</p>' . PHP_EOL .
'</div>' . PHP_EOL;

include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/edit_training_video.html');
include(__DIR__ . '/views/footer.html');
?>
