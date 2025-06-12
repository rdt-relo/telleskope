<!-- New Discussion POP UP -->
<style>
.fa.fa-times {
	color: #f80e0e;
	background-color: #fff;
	position: absolute;
	margin-left: 0px;
}
button.swal2-close:focus {
    outline: auto;
	border-color: #80bdff;
    box-shadow: 0 0 0 .1rem rgba(0,123,255,.25);
}
.redactor-box {
    border: 1px solid #ced4da;
}
.redactor-in {
    padding: 0.375rem 0.75rem;
}
button.swal2-close {    
    color: #595959 !important;   
}
button.swal2-close:hover {
	color: #F16969 !important;
}

</style>

<div class="container inner-background">
	<div class="row row-no-gutters w-100">
		<div class="col-md-12">
			<div class="col-md-10">
				<div class="inner-page-title">
					<h2><?= $formTitle .' - '. $group->val('groupname'); ?></h2>
				</div>
			</div>
		</div> 
		<div class="col-md-12 modal-body">
			<div>
				<form class="form-horizontal" id="newDiscussion">
					<div id="replace_edit">
						<input type="hidden" name="groupid" value="<?= $_COMPANY->encodeId($groupid); ?>">
						<input type="hidden" name="discussionid" value="<?= $_COMPANY->encodeId($discussionid); ?>">
						<p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
						<div class="form-group">
						<label class="control-lable " for="title"><?= gettext("Add discussion title");?><span style="color: #ff0000;">*</span></label>
                            <input aria-required="true" type="text" class="form-control" id="title" name="title" value="<?= $discussion ? $discussion->val('title') : ''; ?>" placeholder="<?= gettext("Discussion title");?>" required>
						</div>
						
						<div class="form-group">
                            <div id="post-inner" class="">
							<label class="control-lable "><?= gettext("Add discussion description");?><span style="color: #ff0000;">*</span></label>
                            <textarea aria-required="true" class="form-control" name="discussion" rows="6" id="redactor_content" required maxlength="2000" placeholder="<?= gettext("Add discussion description.");?>"><?= $discussion ? htmlspecialchars($discussion->val('discussion')) : ''; ?></textarea>
                            </div>
						</div>
                        <?php if($global == 0 && $discussionid == 0){ ?>
						<?php include_once __DIR__.'/../common/init_chapter_channel_selection_box.php'; ?>
					<?php } ?>
					<?php if (!empty($discussionSettings) && $discussionSettings['allow_anonymous_post'] == 1 && $global == 0 && $discussionid == 0){ ?>
					<div class="form-group">
						<div id="post-inner" class=""> 
						<label class="control-lable "><?= gettext("Post anonymously");?>&nbsp;<i tabindex="0" role='button' class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('If you choose to create this discussion anonymously, then the name of the creator and the names of respondents will not be displayed on the discussion. Anonymous settings cannot be changed after the discussion is created')?>"></i>&nbsp;&nbsp; </label>
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

							<button type="button" onclick="int_swal_publish('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($discussionid); ?>');" class="btn btn-affinity about-button prevent-multi-clicks"><?= $submitButton;?></button>

							<?php }else{ ?>

								<button type="button" onclick="submitNewDiscussion('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($discussionid); ?>');" class="btn btn-affinity about-button prevent-multi-clicks"><?= $submitButton;?></button>

							<?php } ?>
							<button type="button" class="btn about-button btn-affinity-gray prevent-multi-clicks" onClick="window.location.reload();"><?= gettext("Close");?></button>
						</div>
					</div>

				</div>
				</form>

			</div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		var fontColors = <?= $fontColors; ?>;
		$('#redactor_content').initRedactor('redactor_content', 'discussion',['fontcolor','counter','handle'],fontColors,'<?= $_COMPANY->getImperaviLanguage(); ?>');
		$('.redactor-in').attr('aria-required', 'true');
		//$('.redactor-in').attr('aria-live', 'polite');
		redactorFocusOut('#title'); // function used for focus out from redactor when press shift +tab.
    });

	function submitNewDiscussion(g,i){
		$(document).off('focusin.modal');
		var formdata = $('#newDiscussion')[0];
		var finaldata  = new FormData(formdata);
		finaldata.append("groupuid",g);
		finaldata.append("discussionid",i);
		finaldata.append("publish_to_email",$('input[name="publish_to_email"]:checked').length);
		preventMultiClick(1);
		$.ajax({
			url: 'ajax_discussions.php?submitNewDiscussion=1',
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
                    swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false})
                    .then(function(result) {
                        if (jsonData.status == 1) {
							// check if the discussion_detail_modal is open
							if($('#discussion_detail_modal').hasClass('show')){
								getDiscussionDetailOnModal('<?= $_COMPANY->encodeId($discussionid); ?>', '<?= $_COMPANY->encodeId(0)?>', '<?= $_COMPANY->encodeId(0)?>', 1);
							}else{
								window.location.reload();
							}
                          	
                        } else if (jsonData.status == -3) {
                            $("#chapter_input").focus();
                            $("#channel_input").focus();
                        }
                    });
				} catch(e) {
					// Nothing to do
                    swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false});
				}
			},
			error: function ( data )
			{
				swal.fire({title: 'Error!',text:'Internal server error, please try after some time.',allowOutsideClick:false});
			}
		});
	}
</script>
<script>
    function postScopeSelector(i) {
        if (i == 2) {
            $("#chapter").val('<?=$_COMPANY->encodeId(0)?>');
            $("#chapter").prop("disabled", true);
        } else {
            $("#chapter").prop("disabled", false);
        }
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
		$(document).off('focusin.modal');
		Swal.fire({
            //title: 'Where do you want to publish?',
            html:
                '<h2 style="font-size:18px;"><?= $swalTitle; ?></h2>'+
                '<hr>'+
				'<div role="group" aria-label="<?= $swalTitle; ?>" class="form-group mb-0">'+
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
	$('.redactor-statusbar').attr('id',"redactorStatusbar");	
	$('.multiselect').attr('aria-expanded', 'false');
	$('.redactor-statusbar').attr('aria-live',"polite");  

	$('.redactor-source').attr('aria-describedby',"redactorStatusbar");
	 
});

$(document).on('keypress','#channels_selection_div .multiselect-search', function(){	
	$("#hidden_div_for_notification").html('');
	$("#hidden_div_for_notification").removeAttr('aria-live');
		setTimeout(() => {
			$("#hidden_div_for_notification").attr({role:"status","aria-live":"polite"}); 
			var numItems = $('.multiselect-container li[class=""]').length; 
			document.getElementById('hidden_div_for_notification').innerHTML= numItems+"<?= gettext(' option available');?>";	
		}, 500)							                
	});	

	$(document).on('keypress','#chapters_selection_div .multiselect-search', function(){	
	$("#hidden_div_for_notification").html('');
	$("#hidden_div_for_notification").removeAttr('aria-live');
		setTimeout(() => {
			$("#hidden_div_for_notification").attr({role:"status","aria-live":"polite"}); 
			var numItems = $('.multiselect-container li[class=""]').length; 
			document.getElementById('hidden_div_for_notification').innerHTML= numItems+"<?= gettext(' option available');?>";	
		}, 500)							                
	});	


$(function () {
      $('[data-toggle="tooltip"]').tooltip();
  })

    $(document).ready(function () {
	    $('#channel_input').attr( 'tabindex', '-1' );
		$('#chapter_input').attr( 'tabindex', '-1' );
		$(".redactor-voice-label").text("<?= gettext('Add discussion description');?>");
    });

$(document).keyup(function(e) {
	$('.tooltip').show();
	if (e.keyCode == 27) { 
		$('.tooltip').hide();
	}   
});
</script>