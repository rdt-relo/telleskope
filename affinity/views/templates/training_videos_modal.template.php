<?php

global $_COMPANY;
$rows = TrainingVideo::GetFrontEndTrainingVideos();
$tags = TrainingVideo::GetFrontEndHashTags();

$modal = '' .
'<div tabindex="-1" id="trainingVideosModal" class="modal fade">' . PHP_EOL .
  '<div aria-label="'. gettext("Training Videos") .'" class="modal-dialog" role="dialog" aria-modal="true">' . PHP_EOL .
    '<div class="modal-content">' . PHP_EOL .
      '<div class="modal-header">' . PHP_EOL .
        '<h2 id="modal-title" class="modal-title">' . gettext("Training Videos") . '</h2>' . PHP_EOL .
        '<button aria-label="Close dialog" tabindex="0" type="button" class="close close-video-player" data-dismiss="modal" id="setFocusOnClose" onclick="closeTrainingVideoModal();">&times;</button>' . PHP_EOL .
      '</div>' . PHP_EOL .
      '<div class="modal-body">' . PHP_EOL .
        '<div id="video-tag-search">' . PHP_EOL .
          '<div>' . PHP_EOL .
            '<label for="video-tag-selector" class="control-label">' . gettext("Select tags") . '</label>' . PHP_EOL .
            '<div class="">' . PHP_EOL .
              '<select class="form-control" id="video-tag-selector" multiple name="selected_video_tags[]" required>' . PHP_EOL;

$selected_tags_array = explode(",", $selected_tags);
foreach($tags as $tag){
    $selected_option = '';
    if (in_array($tag["label"], $selected_tags_array)) {
        $selected_option = ' selected ';
    }else if($selected_tags == ""){
        $selected_option = ' selected ';
    }

    $modal .= '<option value="' . $tag['hashtag_id'] . '" ' . $selected_option . '>' .  $tag["label"] . '</option>' . PHP_EOL;
}

$modal .= '              </select>' . PHP_EOL .
            '</div>' . PHP_EOL .
          '</div>' . PHP_EOL .
        '</div>' . PHP_EOL .
        '<div id="video-list" class="mt-2">' . PHP_EOL .
            '<div class="list-group">' . PHP_EOL;

foreach($rows as $row){
    $encoded_video_id = $_COMPANY->encodeId($row['video_id']);
    $modal .= <<<HTML
<button type="button" id="video-btn-{$encoded_video_id}" tag-data="{$row['tags']}"  class="list-group-item list-group-item-action video-btn-iterator" onclick="viewTrainingVideo('{$encoded_video_id}')">
  {$row['label']}
</button>
HTML;
}

$modal .=
          '</div>' . PHP_EOL .
        '</div>' . PHP_EOL .
        '<div id="video-player"></div>' . PHP_EOL .
      '</div>' . PHP_EOL .
      '<div class="modal-footer">' . PHP_EOL .
        '<button tabindex="0" type="button" id="backtolist_video_button" class="btn btn-secondary">' . gettext('Back') . '</button>' . PHP_EOL .
        '<button tabindex="0" type="button" id="close_video_button" class="btn btn-secondary close-video-player" data-dismiss="modal">' .  gettext('Close') . '</button>' . PHP_EOL .
      '</div>' . PHP_EOL .
    '</div>' . PHP_EOL .
  '</div>' . PHP_EOL .
'</div>' . PHP_EOL;

$modal .= " <script>
$('#trainingVideosModal').on('shown.bs.modal', function () {
    $('#setFocusOnClose').trigger('focus');
    $('.select2-selection--multiple').attr('aria-label','Select tags for help video');    
    $('.select2-selection--multiple').attr('aria-controls','select2-video-tag-selector-results'); 
});

$('#trainingVideosModal').on('hide.bs.modal', function (e) {
  e.stopPropagation();
  $('body').css('padding-right','');
}); 
var button = $('#backtolist_video_button');
button.click(function(){       
    setTimeout(function () {
      $('.select2-search__field').focus();
    }, 50);
});
retainFocus('#trainingVideosModal');
</script>";

$training_video_modal_data = json_encode(array(
        'status' => "success",
        'dialog' => $modal,
        'tags' => $tags,
        'videos' => $rows
));

