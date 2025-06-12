<!-- New Announcement POP UP -->
<style>
.fa.fa-times {
	color: #f80e0e;
	background-color: #fff;
	position: absolute;
	margin-left: 0px;
}
a.disclaimerbtn {
color: #0077b5;
text-decoration: underline;
cursor: pointer;
}

.post-text{
	text-transform: capitalize;
}
legend {
	font-size:1rem;
}
</style>

<div class="container inner-background">
	<div class="row row-no-gutters">
		<div class="col-md-12">
			<div class="col-md-10">
				<div class="inner-page-title">
					<h2 id="newPost"><?= sprintf(gettext("New %s"),Post::GetCustomName(false)) .' - '. $group->val('groupname_short');?></h2>
				</div>
			</div>
		</div> 

		<div class="col-md-12 modal-body">
			<div>
				<form class="form-horizontal" id="newAnnouncement">
					 <p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
					<div id="replace_edit">
						<input type="hidden" name="groupid" value="<?= $_COMPANY->encodeId($groupid); ?>">

                        <div class="form-group">
                            <label class="control-lable"><?= gettext('Create In');?></label>
                            <select aria-label="<?= gettext('Create In');?>" class="form-control" name="post_scope" id="post_scope" onchange="postScopeSelector(this.value)">
                                <?php if($groupid){?>
                                    <option value="group"><?= sprintf(gettext("This %s only"),$_COMPANY->getAppCustomization()['group']["name"]);?></option>
                                    <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled'] && ($_USER->isAdmin() || $_USER->isGroupLead($groupid)))  {?>
                                    	<option value="dynamic_list"><?=sprintf(gettext("This %s Dynamic Lists"), $_COMPANY->getAppCustomization()['group']['name']) ?></option>
                                    <?php } ?>
                                <?php } else {?>
                                    <option value="zone"><?= sprintf(gettext("All %s"),$_COMPANY->getAppCustomization()['group']["name-plural"]);?></option>
                                    <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {?>
                                        <option value="dynamic_list"><?= gettext("Dynamic Lists") ?></option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                        </div>

					    <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {
							// Dynamic list prompt
							if($groupid === 0){
								$dynamic_list_info = gettext("Only the zone members who are also part of users in the selected dynamic lists will receive an email notification when you publish by email.");
							}else{
								$dynamic_list_info = sprintf(gettext("Only the %s members who are also part of users in the selected dynamic lists will receive an email notification when you publish by email."), $group->val('groupname_short'));
							}							
							?>
							<div class="form-group" id="dynamic_list_select" style="  display:none;" >
								<label class="control-lable" id="dynamic_list_label"><?= gettext('Select Dynamic Lists');?></label>
								<select aria-label="<?= gettext('Select Dynamic Lists');?>" class="form-control" name="list_scope[]" id="list_scope" multiple>
									<?php foreach($lists as $list){ ?>
										<option value="<?= $_COMPANY->encodeId($list->val('listid')); ?>"><?= $list->val('list_name'); ?></option>
									<?php } ?>
								</select>

                                <small>
                                    <?= gettext("You can choose one or more existing dynamic lists or you can") ?>
                                    <a aria-label="Add a new dynamic list" onclick="manageDynamicListModal('<?=$_COMPANY->encodeId($groupid);?>')" href="javascript:void(0)"><?= gettext("create your own.")?></a>
                                    <?= $dynamic_list_info ?>
                                    <?= gettext("View the users associated with the selected lists: ")?><a role="button" aria-label="View users" onclick="getDynamicListUsers('<?=$_COMPANY->encodeId($groupid);?>')" href="javascript:void(0)"><?= gettext("view users")?></a>
                                </small>
							</div>
					    <?php } ?>

						<div class="form-group">
                            <label for="title" class="control-lable post-text"><?= sprintf(gettext("%s title"),Post::GetCustomName(false));?><span style="color: #ff0000;"> *</span></label>
                            <input type="text" class="form-control" id="title" name="title" placeholder="<?= sprintf(gettext("%s title"),Post::GetCustomName(false));?>" required>
						</div>
						
						<div class="form-group">
						<label for="redactor_content" class="control-lable post-text"><?= gettext('Description');?><span style="color: #ff0000;"> *</span></label>
                            <div id="post-inner" class="post-inner-edit">
                                <textarea aria-describedby="redactorStatusbar" class="form-control" name="post" rows="6" id="redactor_content" required maxlength="2000" placeholder="<?= gettext("Add description here. Begin by adding text and then insert images.");?>"></textarea>
                            </div>
						</div>
					<?php if($global == 0){ ?>
                        <?php $warn_if_all_chapters_are_selected = true; ?>
						<?php include_once __DIR__.'/../common/init_chapter_channel_selection_box.php'; ?>
					<?php } ?>


                    <div class="form-group">
					<fieldset>					    
						<legend class="control-lable"><?= gettext('Other Options');?></legend>
						<div role="group" id="otherOptions">
                        <?php if ($_COMPANY->getAppCustomization()['post']['post_disclaimer']['enabled']) { ?>
                        <div class="col-12 ml-1 mt-1">

                            <input aria-label="<?= sprintf(gettext("Add %s Disclaimer"),$_COMPANY->val("companyname"));?>" <?= $_COMPANY->getAppCustomization()['post']['post_disclaimer']['enabled_default'] ? 'checked="checked"' : '' ?> type="checkbox" class="form-check-input" name="add_post_disclaimer" id="add_post_disclaimer" >
                            <?= sprintf(gettext("Add %s Disclaimer"),$_COMPANY->val("companyname"));?>
                            <a aria-expanded="false" role="button" tabindex="0" href="javascript:void(0)" class='disclaimerbtn small' onclick='disclaimerBtnClickHideShow()'> <?=gettext("[view]")?> </a>
                            <div id="disclaimerdiv" class="small alert-secondary p-3" style="display:none;">
                                <?=  $_COMPANY->getAppCustomization()['post']['post_disclaimer']['disclaimer']; ?>
                            </div>

                        </div>
                        <?php } ?>

                        <div class="col-12 ml-1 mt-1">
                            <input type="checkbox" class="form-check-input" name="content_replyto_email_checkbox" id="content_replyto_email_checkbox">
                        <label id="custom_replyto_email_label" for="content_replyto_email_checkbox"> <?= gettext("Custom Reply To Email");?></label>
                            <div id="replyto_email" class="" style="display:none;">
                                <input aria-describedby="custom_replyto_email_label" type="email" id="content_replyto_email" name="content_replyto_email" value="" class="form-control" placeholder="<?= gettext('Add a custom reply to email');?>" />
                            </div>
                        </div>
						</div>
						</fieldset>
                    </div>

					<?= Post::CreateEphemeralTopic()->renderAttachmentsComponent('v8') ?>

					<div class="form-group mt-4">
						<div class="col-sm-12 text-center">
							<button id="postSaveDraftBtn" type="button" onclick="submitNewAnnoucement('<?= $_COMPANY->encodeId(0); ?>',0);" class="btn btn-affinity"><?= gettext("Save Draft");?></button>
							<button id="postCancelDraftBtn" type="button" class="btn btn-affinity-gray" onClick="window.location.reload();"><?= gettext("Close");?></button>
						</div>
					</div>

					<div class="form-group">
						<div style="font-size:smaller;text-align: center;">
						<?= sprintf(gettext('Note: You will have to select "Publish" from %s options on the next page to publish the %s and to send notifications to group members.'),Post::GetCustomName(false), Post::GetCustomName(false));?>
						</div>
					</div>

				</div>
				</form>

			</div>
		</div>
	</div>
</div>
<script type="text/javascript">

	function disclaimerBtnClickHideShow()
	{
		$("#disclaimerdiv").toggle();
		var disc=$("#disclaimerdiv").is(":hidden")
		if(!disc){
			$(".disclaimerbtn").attr('aria-expanded','true');
		}else{
			$(".disclaimerbtn").attr('aria-expanded','false');
		}

	}

    $(function () {
        $("#content_replyto_email_checkbox").click(function () {
            if ($(this).is(":checked")) {
                $("#replyto_email").show();
            } else {
                $("#replyto_email").hide();
            }
        });
    });
</script>
<script>
	$(document).ready(function(){
		$('#custom_email_reply').click(function(){
			$("#customEmailReply").toggle();
		});		
	});

	$(document).ready(function(){
		var fontColors = <?= $fontColors; ?>;
		$('#redactor_content').initRedactor('redactor_content', 'post',['fontcolor','counter','handle','fontsize'],fontColors,'<?= $_COMPANY->getImperaviLanguage(); ?>');
		$(".redactor-voice-label").text("<?= gettext('Add description');?>");
		redactorFocusOut('#title'); // function used for focus out from redactor when press shift + tab.
		$('.redactor-statusbar').attr('aria-live',"polite");
    });

	function submitNewAnnoucement(edit,type){
		$(document).off('focusin.modal');
		edit = (typeof edit !== 'undefined') ? edit : 0;
		var formdata = $('#newAnnouncement')[0];
		var finaldata  = new FormData(formdata);
		finaldata.append("edit",edit);
		finaldata.append("type",type);

		$.ajax({
			url: 'ajax_announcement?submitNewAnnoucement=1',
			type: 'POST',
			enctype: 'multipart/form-data',
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			tskp_submit_btn: $('#postSaveDraftBtn'),
			success: function(data) {
				try {
                    let jsonData = JSON.parse(data);
					resetContentFilterState(2);
                    swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false})
                    .then(function(result) {
                        if (jsonData.status == 1) {
                            //window.location.href="viewpost.php?"+jsonData.val;
							location.reload();
                        } else if (jsonData.status == -3) {
                            $("#chapter_input").focus();
                            $("#channel_input").focus();
                        }
						$('#title').focus();
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
      $(document).ready(function () {
        var currentSelection = $("#post_scope").val();
        postScopeSelector(currentSelection);
      });
</script>
<script>
    function postScopeSelector(i) {
		handleCreationScopeDropdownChange('#post_scope');
        if (i == 'dynamic_list') {
            $("#dynamic_list_select").show();
            $("#chapter").val('<?=$_COMPANY->encodeId(0)?>');
			$('.multiselect-selected-text').attr('id',"no_list");
			$('.multiselect').attr('aria-labelledby',"dynamic_list_label no_list");
			$("#channels_selection_div").hide();
            $("#chapters_selection_div").hide();
			$("#chapter_input").prop("disabled", true);
			$("#channel_input").prop("disabled", true);
			$("#list_scope").multiselect("enable");
			$("#list_scope").multiselect("refresh");
        } else {
            $("#dynamic_list_select").hide();
            $("#chapter").prop("disabled", false);
			$("#channels_selection_div").show();
            $("#chapters_selection_div").show();
			$("#chapter_input").prop("disabled", false);
			$("#channel_input").prop("disabled", false);
			$("#list_scope").multiselect("disable");
			$("#list_scope").multiselect("refresh");
        }
    }


	$('#list_scope').multiselect({
		nonSelectedText: "<?=gettext("No list selected"); ?>",
		numberDisplayed: 3,
		nSelectedText: "<?= gettext('List selected');?>",
		disableIfEmpty: true,
		allSelectedText: "<?= gettext('Multiple lists selected'); ?>",
		enableFiltering: true,
		maxHeight: 400,
		enableClickableOptGroups: true,
	});

$(".multiselect-container li a").keyup(function (e) {
  var keyCode = e.keyCode || e.which; 
	if (keyCode == 9) { 
		$("#title").focus();
		$(".multiselect-container").removeClass('show');
	}
});
$(document).on('click', '.multiselect', function () {
    if($('.multiselect-container').is(':visible')){
		$("#hidden_div_for_notification").attr({role:"status","aria-live":"polite"}); 
		document.getElementById('hidden_div_for_notification').innerHTML="<?= gettext('Search new dynamic lists OR select dynamic lists.') ?>";
	}
});

$('#list_scope').on('change', function() {
	checkCheckboxes(); 
});

	$(document).on('keypress','.multiselect-search', function(){	
		$("#hidden_div_for_notification").html('');
		$("#hidden_div_for_notification").removeAttr('aria-live');
			setTimeout(() => {
				$("#hidden_div_for_notification").attr({role:"status","aria-live":"polite"}); 
				var numItems = $('.multiselect-container li[class=""]').length; 
				var numItem = $('.multiselect-container li:not([class])').length; 
				if(numItems){
					document.getElementById('hidden_div_for_notification').innerHTML= numItems+"<?= gettext(' option available');?>";
				}else{
					document.getElementById('hidden_div_for_notification').innerHTML= numItem+"<?= gettext(' option available');?>";
				}
					
			}, 500)							                
	});	
</script>
