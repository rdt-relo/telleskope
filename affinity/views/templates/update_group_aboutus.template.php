<style>
	figure{
		text-align: center !important;
	}
</style>
<div class="col-md-12">
	<div class="row">
			<div class="col-10">          
				<h2><?= gettext("About Us").' - '. $group->val('groupname_short'); ?></h2>            
			</div>
			<div class="col-2 text-right" style="margin-bottom: -16px;">
				<?php
                $page_tags = 'manage_aboutus';
                ViewHelper::ShowTrainingVideoButton($page_tags);
				?>
			</div>
    </div>
    <hr class="lineb" >
</div>
	
<div class="col-md-12 inner-page-container">
	<div class="col-md-12">
		<p style="text-align:center; color:green;display:none" id="showmessage"><?= gettext("Information updated successfully.");?></p>
		<form id="form-aboutus">
			<input type="hidden" class="form-control" name="updateboutus" value="about" />

			<div class="form-group">
				<label class="control-lable" ><?= gettext('Scope'); ?></label>
				<div>
					<select aria-label="<?= gettext('Scope');?>"  type="text" class="form-control" id="groupChapterId"  name="groupChapterId" onchange="getAboutUsFields('<?=$encGroupId?>')" required>
						<?php if ($_USER->canManageGroup($groupid)) { ?>
                        <option data-section="<?= $_COMPANY->encodeId(0) ?>" value="<?= $_COMPANY->encodeId(0) ?>"><?= $group->val('groupname')." ".$_COMPANY->getAppCustomization()['group']['name-short']; ?></option>
						<?php } ?>
                        <?php if($chapters){ ?>
						<optgroup label="<?=$_COMPANY->getAppCustomization()['chapter']['name-short-plural']?>">
							<?php for($i=0;$i<count($chapters);$i++){ ?>
							<?php if ($_USER->canManageGroupChapter($groupid,$chapters[$i]['regionids'],$chapters[$i]['chapterid'])) { ?>
							<option data-section="<?= $_COMPANY->encodeId(1) ?>" value="<?= $_COMPANY->encodeId($chapters[$i]['chapterid']) ?>">&emsp;<?= htmlspecialchars($chapters[$i]['chaptername']); ?></option>
							<?php } ?>
							<?php } ?>
						</optgroup>
                        <?php } ?>

                        <?php if($channels){ ?>
                        <optgroup  label="<?=$_COMPANY->getAppCustomization()['channel']['name-short-plural']?>">
						<?php for($i=0;$i<count($channels);$i++){ ?>
                            <?php if ($_USER->canManageGroupChannel($groupid,$channels[$i]['channelid'])) { ?>
							<option  data-section="<?= $_COMPANY->encodeId(2) ?>" value="<?= $_COMPANY->encodeId($channels[$i]['channelid']) ?>">&emsp;<?= htmlspecialchars($channels[$i]['channelname']); ?></option>
                            <?php } ?>
						<?php } ?>
						</optgroup>
                        <?php } ?>
					</select>
				</div>
			</div>
			<div id="ajaxFields">
			</div>

			<?php if ($group->val('about_show_members') === "1" || ($group->val('about_show_members') === "2" && $_USER->isGroupMember($group->id()))) { ?>
				<div class="form-group">
					<small style=" color:#666666;">
						<?= gettext("Helpful Tip: If you want to add a shortcut to the Leaders or Members section on your About Us page, you can do so by following these steps: (1) Click the <strong>Insert Link</strong>> button, (2) In the <strong>URL</strong> field, enter <i>#jump_to_leaders_list</i> or <i>#jump_to_members_list</i>,(3) Uncheck the <strong>Open link in new tab</strong> checkbox.");?>
					</small>
				</div>
    		<?php } ?>
			<div class="form-group">
				<div class="col-md-12 text-center" >
					<button id="" class=" about-button btn btn-affinity prevent-multi-clicks" type="button" onclick="saveAboutUsData('<?=$encGroupId;?>');" name="reset"><?= gettext("Update");?></button>
			    </div>
			</div>
		</form>
	</div>
</div>

<script>
	$(document).ready(function(){        
        getAboutUsFields('<?=$encGroupId?>');
	});
	//setTimeout(function(){
	//	var fontColors = <?//= $fontColors; ?>//;
	//	var resizableimages = true;
	//	$('#redactor_content').initRedactor('redactor_content','group',['video','fontcolor','counter','fontsize','table'],fontColors,'<?//= $_COMPANY->getImperaviLanguage(); ?>//',resizableimages);
	// }, 100);
	
	function saveAboutUsData(i){
		var section = $('#groupChapterId').find(':selected').data('section')
		var formdata = $('#form-aboutus')[0];
		var finaldata  = new FormData(formdata);
		finaldata.append("section",section);
		updateAboutUsData(i,finaldata);
	}
</script>

