<?php include __DIR__ . '/header.html'; ?>


<!-- New recognition POP UP -->
<style>
.fa.fa-times {
	color: #f80e0e;
	background-color: #fff;
	position: absolute;
	margin-left: 0px;
}
</style>
<div class="container">
    <div class="row">
        <div class="modal" id="loadNewDiscussionModal" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><?= $formTitle;?></h2>
               
            </div>
            <div class="modal-body">
                <div class="">
                <form class="form-horizontal" id="newDiscussion">
					<div id="replace_edit">
						<input type="hidden" name="groupid" value="<?= $_COMPANY->encodeId($groupid); ?>">
						<input type="hidden" name="discussionid" value="<?= $_COMPANY->encodeId($discussionid); ?>">
						<p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
						<div class="form-group">
						<label class="control-lable "><?= gettext("Add discussion title");?></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?= $discussion ? $discussion->val('title') : ''; ?>" placeholder="<?= gettext("Discussion title");?>" required>
						</div>
						
						<div class="form-group">
                            <div id="post-inner" class="">
							<label class="control-lable "><?= gettext("Add discussion description");?></label>
                            <textarea class="form-control" name="discussion" rows="6" id="redactor_content" required maxlength="2000" placeholder="<?= gettext("Add discussion description.");?>"><?= $discussion ? htmlspecialchars($discussion->val('discussion')) : ''; ?></textarea>
                            </div>
						</div>
                        <?php if($global == 0 && $discussionid == 0){ ?>
						<?php include_once __DIR__.'/common/init_chapter_channel_selection_box.php'; ?>
					<?php } ?>

					<?php if ($discussionSettings['allow_anonymous_post'] == 1 && $global == 0 && $discussionid == 0){ ?>
					<div class="form-group">
						<div id="post-inner" class=""> 
						<label class="control-lable "><?= gettext("Post anonymously");?>&nbsp;<i class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('If you choose to create this discussion anonymously, then the name of the creator and the names of respondents will not be displayed on the discussion. Anonymous settings cannot be changed after the discussion is created')?>"></i>&nbsp;&nbsp; </label>
						<label class="radio-inline"> 
						<input aria-label="Post anonymously no" type="radio" value="0" name="anonymous_post" checked=""><?= gettext("No");?></label>
						<label class="radio-inline">
						<input aria-label="Post anonymously yes" type="radio" value="1" name="anonymous_post"><?= gettext("Yes");?></label>
						</div>
					</div>
					<?php } ?>

					<div class="form-group">
						<div class="col-sm-12 text-center">
						<?php if (!empty($discussionSettings) && $discussionSettings['allow_email_publish'] == 1){?>

							<button type="button" onclick="int_swal_publish('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($discussionid); ?>');" class="btn btn-affinity"><?= $submitButton;?></button>

							<?php }else{ ?>
								<button type="button" onclick="submitNewDiscussion('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($discussionid); ?>');" class="btn btn-affinity"><?= $submitButton;?></button>
							<?php } ?>
							<button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="window.location.href='success_callback.php'"><?= gettext("Cancel");?></button>
						</div>
					</div>

				</div>
				</form>
                </div>
            </div>
            </div>
        </div>
        </div>
    </div>
</div>
<script>
	$(document).ready(function(){
		var fontColors = <?= $fontColors; ?>;
		$('#redactor_content').initRedactor('redactor_content', 'discussion',['fontcolor','counter','handle'],fontColors,'<?= $_COMPANY->getImperaviLanguage(); ?>');
   		$(".redactor-voice-label").text("Add discussioin description");
        $('#loadNewDiscussionModal').modal({
            backdrop: 'static',
            keyboard: false
        });
    });

	function submitNewDiscussion(g,i){
		var formdata = $('#newDiscussion')[0];
		var finaldata  = new FormData(formdata);
		finaldata.append("groupuid",g);
		finaldata.append("discussionid",i);
		finaldata.append("publish_to_email",$('input[name="publish_to_email"]:checked').length);
		preventMultiClick(1);
		$.ajax({
			url: 'ajax_native.php?submitNewDiscussion=1',
			type: 'POST',
			enctype: 'multipart/form-data',
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success: function(data) {
				try {
                    let jsonData = JSON.parse(data);
					//resetContentFilterState(2);
                    swal.fire({title:jsonData.title,text:jsonData.message})
                    .then(function(result) {
                        if (jsonData.status == 1) {
                            window.location.href= 'success_callback.php';
                        } else if (jsonData.status == -3) {
                            $("#chapter_input").focus();
                            $("#channel_input").focus();
                        }
                    });
				} catch(e) {
					// Nothing to do
                    swal.fire({title: 'Error', text: "Unknown error."});
				}
			},
			error: function ( data )
			{
				swal.fire({title: 'Error!',text:'Internal server error, please try after some time.'});
			}
		});
	}
</script>

<script>
    function int_swal_publish(g,i) {
<?php 
	if($discussionid){
		$swalTitle = addslashes(gettext("Where do you want to publish the discussion update?"));
	} else {
		$swalTitle = addslashes(gettext("Where do you want to publish the discussion?"));
	}
?>
Swal.fire({
	//title: 'Where do you want to publish?',
	html:
		'<h6><?= $swalTitle; ?></h6>'+
		'<hr>'+
		'<div class="form-group mb-0">'+
			'<div class="col-md-5">&nbsp;</div>'
			+'<div class="form-check text-left col-md-7">'
				+' <input aria-label="<?= gettext("This platform");?>" class="form-check-input" type="checkbox" value="online" name="publish_where[]" checked disabled>'
				+'<small class="form-check-label">'
				+'<?= gettext("This platform");?>'
				+'</small>'
			+'</div>'+
			'<div class="col-md-5">&nbsp;</div>'
			+'<div class="form-check text-left col-md-7 removeit">'
				+' <input aria-label="<?= gettext("email");?>" class="form-check-input" type="checkbox" value="email" name="publish_to_email" id="sendEmails" >'
				+'<small class="form-check-label">'
				+'<?= gettext("Email");?>'
				+'</small>'
			+'</div>'+
		'</div>'+
		'<br>' +
		'<br>' +
		'<button type="button" class="btn btn-affinity mt-2 prevent-multi-clicks" onclick=submitNewDiscussion("'+g+'","'+i+'")><?= addslashes(gettext("Publish Update"));?></button>'+
		'<br>' +
		'<br>',
	showCloseButton: true,
	showCancelButton: false,
	showConfirmButton: false,
	focusConfirm: true,
	allowOutsideClick:false,
}).then(function(result){ 
	$('#swal2-close').trigger('focus');			
});

	if ($('input[name="anonymous_post"]:checked').length > 0) {
		// Get the value of the checked radio button
		var value = $('input[name="anonymous_post"]:checked').val();

		// Check the value
	  if (value == '1') {
			$("#sendEmails").parents(".removeit").remove();  
	  } 
	}

  <?php if(isset($discussion) && $discussion->val('anonymous_post') == "1"){ ?>

	$("#sendEmails").parents(".removeit").remove();  

  <?php } ?>


}

</script>

<script>
$(document).ready(function(){
  $(".multiselect").click(function(){
	$(".multiselect-selected-text").attr('aria-live',"polite");	
  });

});
$(function () {
      $('[data-toggle="tooltip"]').tooltip();
  })

    $(document).ready(function () {
	    $('#channel_input').attr( 'tabindex', '-1' );
		$('#chapter_input').attr( 'tabindex', '-1' );
    });
</script>