<style>
.fa.fa-times {
	color: #f80e0e;
	background-color: #fff;
	position: absolute;
	margin-left: 0;
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
					<h3><?= sprintf(gettext("Update %s"),Post::GetCustomName(false));?> </h3>
				</div>
			</div>
		</div>
		<div class="col-md-12">
			<div class="form-container">

			<form class="form-horizontal" id="newAnnouncement">
				<input type="hidden" name="groupid" value="<?= $_COMPANY->encodeId($edit->val('groupid')); ?>">
				<input type="hidden" name="version" value="<?= $_COMPANY->encodeId($edit->val('version')); ?>">
				<p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                <!-- Option to select dynamic lists. -->
				<div class="form-group">
					<label class="control-lable"><?= gettext('Create In');?></label>
                    <select aria-label="<?= gettext('Create In'); ?>" class="form-control" name="post_scope" id="post_scope" onchange="postScopeSelector(this.value)">

                        <?php if (($groupid == 0) && $_USER->isAdmin()) { ?>
                        <option value="zone" <?= ($edit->val('listids') == '0' && $edit->val('groupid') == 0) ? 'selected' : ($edit->val('isactive') == 1 ? 'disabled' : ''); ?> ><?= sprintf(gettext("All %s"), $_COMPANY->getAppCustomization()['group']["name-plural"]); ?></option>
                        <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {  ?>
                        <option value="dynamic_list" <?= $edit->val('listids') != '0' ? 'selected' : ($edit->val('isactive') == 1 ? 'disabled' : ''); ?>><?= gettext("Dynamic Lists") ?></option>
                        <?php } ?>

                        <?php } else { ?>

                        <option value="group" <?= ($edit->val('listids') == '0' && $edit->val('groupid') > 0) ? 'selected' : ($edit->val('isactive') == 1 ? 'disabled' : ''); ?>><?= sprintf(gettext("This %s only"), $_COMPANY->getAppCustomization()['group']["name"]); ?></option>
                        <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled'] && ($_USER->isAdmin() || $_USER->isGroupLead($groupid))) { ?>
                        <option value="dynamic_list" <?= $edit->val('listids') != '0' ? 'selected' : ($edit->val('isactive') == 1 ? 'disabled' : ''); ?>><?= sprintf(gettext("This %s Dynamic Lists"), $_COMPANY->getAppCustomization()['group']['name']) ?></option>
                        <?php } ?>

                        <?php } ?>
                    </select>
				</div>

                <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {
					// Dynamic list prompt
					if($groupid == 0){
						$dynamic_list_info = gettext("Only the zone members who are also part of users in the selected dynamic lists will receive an email notification when you publish by email.");
					}else{
						$dynamic_list_info = sprintf(gettext("Only the %s members who are also part of users in the selected dynamic lists will receive an email notification when you publish by email."), $_COMPANY->getAppCustomization()['group']["name"]);
					}
					?>
				<div class="form-group" id="list_selection" style="display:<?= $edit->val('listids') !='0'  ? 'block': 'none'; ?>;">

                    <?php if($edit->val('isactive') == 1){ ?>

                    <div class="form-admin-option m-0 p-2">
                        <strong>
                            <?= sprintf(gettext('This %1$s is published in %2$s dynamic list'),Post::GetCustomName(false),DynamicList::GetFormatedListNameByListids($edit->val('listids')));?>
                        </strong>
                    </div>

					<?php } else { ?>

                    <label id="dynamic_list_label" class="control-lable"><?= gettext("Select Dynamic Lists"); ?></label>
                    <select aria-label="<?= gettext('Select Dynamic Lists');?>" class="form-control" name="list_scope[]" id="list_scope" multiple>
                        <?php
                            $listids = explode(',',$edit->val('listids'));
                            foreach($lists as $list){ ?>
                            <option value="<?= $_COMPANY->encodeId($list->val('listid')); ?>" <?= in_array($list->val('listid'),$listids) ? 'selected': ($edit->val('isactive') == 1 ? 'disabled' : ''); ?>><?= $list->val('list_name'); ?></option>
                        <?php } ?>
                    </select>

                    <?php } ?>
					<?php
						// if already published, hide option to create dynamic list
						if(!$edit->isPublished()){ ?>
						 	<small>
                                <?= gettext("You can choose one or more existing dynamic lists or you can") ?>
                                <a aria-label="Add a new dynamic list" onclick="manageDynamicListModal('<?=$_COMPANY->encodeId($edit->val('groupid'));?>')" href="javascript:void(0)"><?= gettext("create your own.")?></a>
                                <?= $dynamic_list_info ?>
                                <?= gettext("View the users associated with the selected lists: ")?><a role="button" aria-label="View users" onclick="getDynamicListUsers('<?=$_COMPANY->encodeId($groupid);?>')" href="javascript:void(0)"><?= gettext("view users")?></a>
                            </small>
						<?php } ?>
				</div>
			    <?php } ?>
                <!-- End of dynamic lists option -->
				<div class="form-group">
                    <label for="title" class="control-lable post-text"><?= sprintf(gettext("%s title"),Post::GetCustomName(false));?>
                        <?php if($allowTitleUpdate ){ ?>
                        <span style="color: #ff0000;"> *</span>
                        <?php } else {?>
                        <i class="fa fa-info-circle fa-small info-black" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext('%s editing is disabled because the %s has been submitted for approval.'), sprintf(gettext("%s title"),Post::GetCustomName(false)), Post::GetCustomName(false))?>"></i>
                        <?php } ?>
                    </label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="<?= sprintf(gettext("%s title"),Post::GetCustomName(false));?>" value="<?= $edit->val('title'); ?>" <?= !$allowTitleUpdate ? 'readonly' : ''; ?> required>
                </div>

				<div class="form-group">
					<label for="redactor_content" class="control-lable post-text"><?= gettext('Description');?>
                        <?php if($allowDescriptionUpdate ){ ?>
                        <span style="color: #ff0000;"> *</span>
                        <?php } else { ?>
                        <i class="fa fa-info-circle fa-small info-black" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext('%s editing is disabled because the %s has been submitted for approval.'), sprintf(gettext("%s description"),Post::GetCustomName(false)), Post::GetCustomName(false))?>"></i>
                        <?php }  ?>
                    </label>
                    <div id="post-inner" class="post-inner-edit">
                        <textarea  aria-describedby="redactorStatusbar" class="form-control" name="post" rows="10" id="redactor_content" required maxlength="2000" placeholder="<?= sprintf(gettext("%s description"),Post::GetCustomName(false)); ?>"><?= htmlspecialchars($edit->val('post')); ?></textarea>
                    </div>
				</div> 

				<?php if ($edit->val('groupid') && (1 != $edit->val('isactive'))){ ?>
                    <?php $use_and_chapter_connector = $edit->val('use_and_chapter_connector'); ?>
                    <?php $warn_if_all_chapters_are_selected = true; ?>
					<?php include_once __DIR__.'/../common/init_chapter_channel_selection_box.php'; ?>
				<?php } ?>

                <div class="form-group">
					<fieldset>
					<legend class="control-lable"><?= gettext('Other Options');?></legend>
					<div role="group" id="otherOptions">
						<?php if ($_COMPANY->getAppCustomization()['post']['post_disclaimer']['enabled']) { ?>
						<div class="col-12 ml-1 mt-1">
							<input aria-label="<?= sprintf(gettext("Add %s Disclaimer"),$_COMPANY->val("companyname"));?>" <?= $edit->val('add_post_disclaimer') ? 'checked="checked"' : '' ?> type="checkbox" class="form-check-input" name="add_post_disclaimer" id="add_post_disclaimer">
                        <?= sprintf(gettext("Add %s Disclaimer"), $_COMPANY->val("companyname")); ?> <a aria-expanded="false" role="button" tabindex="0" href="javascript:void(0)" class='disclaimerbtn small'onclick='disclaimerBtnClickHideShow()'> <?= gettext("[view]") ?> </a>
							<div id="disclaimerdiv" class="small alert-secondary p-3" style="display:none;">
								<?= $_COMPANY->getAppCustomization()['post']['post_disclaimer']['disclaimer']; ?>
							</div>
						</div>
						<?php } ?>

						<div class="col-12 ml-1 mt-1">
							<input type="checkbox" class="form-check-input" name="content_replyto_email_checkbox" id="content_replyto_email_checkbox" <?= $edit->val('content_replyto_email') ? 'checked' : '' ?>>
							<label id="custom_replyto_email_label" for="content_replyto_email_checkbox"> <?= gettext("Custom Reply To Email");?></label>
							<div id="replyto_email" class="" <?= empty($edit->val('content_replyto_email')) ? 'style="display:none;"' : ''?> >
								<input aria-describedby="custom_replyto_email_label" type="email" id="content_replyto_email" name="content_replyto_email" value="<?= $edit->val('content_replyto_email') ?>" class="form-control" placeholder="<?= gettext('Add a custom reply to email');?>" />
							</div>
						</div>
					</div>
					</fieldset>
                </div>

				<?= $edit->renderAttachmentsComponent() ?>

				<div class="form-group">

					<?php $isActive = $edit->val('isactive'); ?>

					<div class="col-sm-12 text-center">
						<?php if($isActive ==1){?>
						<button id="publishPostUpdateBtn" type="button" class="confirm btn-affinity btn" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?=gettext('Are you sure you want to publish this update ?')?>" onclick="swal_publish();"><?= gettext("Publish Update");?></button>
                        <?php }else if($isActive ==Post::STATUS_DRAFT || $isActive == Post::STATUS_UNDER_REVIEW){?>
						<button id="savePostDraftUpdateBtn" type="button" onclick="submitNewAnnoucement('<?= $_COMPANY->encodeId($id); ?>',0);" class="btn btn-affinity"><?= gettext("Save Draft");?></button>
						<?php }?>
						<button id="savePostDraftCancelBtn" type="button" class="btn btn-affinity-gray" onClick="window.location.reload();"><?= gettext("Close");?></button>
					</div>

					<?php if($isActive ==Post::STATUS_DRAFT || $isActive == Post::STATUS_UNDER_REVIEW){?>
					<div class="form-group">
						<div style="font-size:smaller;text-align: center;">
							<?= sprintf(gettext('Note: You will have to select "Publish" from %s options on the next page to publish the %s and to send notifications to group members.'),Post::GetCustomName(false),Post::GetCustomName(false));?>
							
						</div>
					</div>
					<?php } ?>
					
				</div>

			</form>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
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
	
	$(document).ready(function(){
		$('[data-toggle="tooltip"]').tooltip();
		var fontColors = <?= $fontColors; ?>;
        let redactorApp = $('#redactor_content').initRedactor('redactor_content','post',['fontcolor','counter','handle','fontsize'],fontColors,'<?= $_COMPANY->getImperaviLanguage(); ?>');
		<?php if(!$allowDescriptionUpdate){ ?>
			redactorApp.enableReadOnly();
		<?php } ?>

		$(".redactor-voice-label").text("<?= gettext('Add description');?>");
		redactorFocusOut('#title'); // function used for focus out from redactor when press shift +tab.
		$('.redactor-statusbar').attr('aria-live',"polite");
    });
	function submitNewAnnoucement(edit,type){
		edit = (typeof edit !== 'undefined') ? edit : 0;
		var formdata = $('#newAnnouncement')[0];
		var finaldata  = new FormData(formdata);
		finaldata.append("edit",edit);
		if (type == 1){

			var sendEmails = 0;
			if ($('#sendEmails').is(":checked")){
				sendEmails = 1;
			}
			finaldata.append("sendEmails",sendEmails);
			var publish_where_integration = [];
			$("input:checkbox[name='publish_where_integration[]']:checked").each(function(){
				finaldata.append("publish_where_integration[]",$(this).val());
			});
		}
		finaldata.append("type",type);

		$.ajax({
			url: 'ajax_announcement?submitNewAnnoucement=1',
			type: 'POST',
			enctype: 'multipart/form-data',
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success: function(data) {
				if (type == 0) resetContentFilterState(2);
				try {
                    let jsonData = JSON.parse(data);
					swal.fire({title:jsonData.title,text:jsonData.message})
					.then(function(result) {
						if (jsonData.status == 1){
							//window.location.href="viewpost.php?"+jsonData.val;
							location.reload();
						} else if (jsonData.status == -3) {
							$("#chapter_input").focus();
							$("#channel_input").focus();
						}
					});
				} catch(e) {
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
    jQuery(document).ready(function() {
        jQuery(".confirm").popConfirm({content: ''});
    });

    function swal_publish() {
		Swal.fire({
            //title: 'Where do you want to publish?',
            html:
                '<h5><?= addslashes(gettext("Where do you want to publish update?")); ?></h5>'+
                '<hr>'+
				'<div class="form-group">'+
				<?php if($edit->val('listids') == 0){ ?>
					'<div class="col-md-5">&nbsp;</div>'
					+'<div class="form-check text-left col-md-7">'
						+' <input class="form-check-input" type="checkbox" value="online" name="publish_where_online" checked disabled>'
						+'<small class="form-check-label" for="publish_where_integrations">'
						+'<?= addslashes(gettext("This platform"));?>'
						+'</small>'
					+'</div>'+
				<?php } ?>
                <?php $hideEmailPublish = $_COMPANY->getAppCustomization()['post']['disable_email_publish']?? false; ?>
				<?php if(!$hideEmailPublish){ ?>
					'<div class="col-md-5">&nbsp;</div>'
					+'<div class="form-check text-left col-md-7">'
						+' <input class="form-check-input" type="checkbox" value="email" name="publish_where_email" id="sendEmails" <?= $edit->val('publish_to_email') ? 'checked' : '' ?>>'
						+'<small class="form-check-label" for="publish_where_integrations">'
						+'<?= gettext("Email");?>'
						+'</small>'
					+'</div>'+
                <?php } ?>
				<?php if($edit->val('listids') == 0){ ?>
					<?php foreach($integrations as $integration){ ?>
							'<div class="col-md-5">&nbsp;</div>'
							+'<div class="form-check text-left col-md-7">'
								+' <input class="form-check-input" type="checkbox" value="<?= $_COMPANY->encodeId($integration['externalId']) ?>" name="publish_where_integration[]"  <?= $integration['checked']; ?>>'
								+'<small class="form-check-label" for="publish_where_integrations">'
								+'<?= $integration['externalName'];?>'
								+'</small>'
							+'</div>'+
					<?php } ?>
				<?php } ?>

				'</div>'+
				'<br>' +
				'<br>' +
				'<button id="publishUpdate" type="button" class="btn btn-affinity mt-2" onclick=submitNewAnnoucement("<?= $_COMPANY->encodeId($id); ?>",1)><?= addslashes(gettext("Publish Update"));?></button>'+
                '<br>' +
                '<br>',
            showCloseButton: true,
            showCancelButton: false,
            showConfirmButton: false,
            focusConfirm: false,
        });
    }
</script>
<script>
      $(document).ready(function () {
        var currentSelection = $("#post_scope").val();
        postScopeSelector(currentSelection);
      });
	$('#list_scope').on('change', function() {
        toggleChapterChannelBoxVisibility(); 
    });
</script>
<script>
	function postScopeSelector(i) {
		if (i == 'dynamic_list') {
			$("#list_selection").show();
			$('.multiselect-selected-text').attr('id',"no_list");
			$('.multiselect').attr('aria-labelledby',"dynamic_list_label no_list");
			$("#channels_selection_div").hide();
            $("#chapters_selection_div").hide();
			$("#chapter_input").prop("disabled", true);
			$("#channel_input").prop("disabled", true);
			$("#list_scope").prop("disabled", false);
			$('#list_scope').multiselect('refresh');
		} else {
            $("#list_selection").hide();
			$("#chapter_input").prop("disabled", false);
			$("#channel_input").prop("disabled", false);
            $("#channels_selection_div").show();
            $("#chapters_selection_div").show();
			$("#list_scope").prop("disabled", true);
			$('#list_scope').multiselect('refresh');
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
		enableClickableOptGroups: true
	});

	$(document).on('click', '.multiselect', function () {
    if($('.multiselect-container').is(':visible')){
		$("#hidden_div_for_notification").attr({role:"status","aria-live":"polite"}); 
		document.getElementById('hidden_div_for_notification').innerHTML="<?= gettext('Search new dynamic lists OR select dynamic lists.') ?>";
	}
});
</script>
