<div>
	<div class="col-md-12 mb-5" id="addMembersMembers" style="display:none">
		<div class="mb-3 progress_bar hide" id="progress_bar">
			<p><?= sprintf(gettext('Adding <span id ="totalBulkRecored"></span> %s members by email addresses. Please wait.'),$_COMPANY->getAppCustomization()['group']["name-short"]);?></p>
			<div class="progress">
				<div class="progress-bar progress-bar-animated" id="prgress_bar" style="width:0%"></div>
			</div>
			<div class="text-center progress_status" aria-live="polite"></div>
		</div>

		<div class="mb-3 p-4 progress_done progress_bar hide">
			<strong><?= gettext("Added");?> :</strong>
			<p class="pl-3" id="inviteSent" style="color: green;"></p>

			<strong><?= gettext("Skipped or Activated (existing members)");?>: </strong>
			<p class="pl-3 " style="color: blue;" id="invitesSkipped"></p>

            <?php if ($_COMPANY->getAppCustomization()['group']['member_restrictions']) { ?>
            <strong><?= gettext("Skipped (do not meet membership requirements)");?>: </strong>
            <p class="pl-3 " style="color: blue;" id="invitesRestricted"></p>
            <?php } ?>

			<strong><?= gettext("Failed");?>: </strong>
			<p class="pl-3 " style="color: red;" id="inviteFailed"></p>
			<div class="co-md-12 text-center pt-3">
				<button id="close_show_progress_btn" class="btn btn-affinity hide" onclick="mangeGroupMemberList('<?= $_COMPANY->encodeId($groupid); ?>')"><?= gettext("Close And Refresh Members List");?></button>
			</div>
		</div>

		<div class="col-md-12 mt-3 p-3" style="border:1px solid #ccc;">
			<form class="form-horizontal" id="form-addMembers">
                <p class="font-weight-bold" role="heading" aria-level="3"><?= sprintf(gettext('Add %s by Email'),$_COMPANY->getAppCustomization()['group']['memberlabel']);?></p>
                <p class="mb-3"><?=gettext('You can add users whose email accounts are already set up in the system. For new users, use the "Invite Users" functionality to send them an invitation to join.')?></p>
				<div class="form-group">
					<p class="col-md-12 control-lable"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
			</div>
				<div class="form-group">
					<label class="col-md-12 control-lable" for="group_chapter_channel_id"><?= sprintf(gettext('%s will join'),$_COMPANY->getAppCustomization()['group']['memberlabel']);?> </label>
					<div class="col-md-12">
						<select aria-label="<?= gettext('Member will join');?>" tabindex="0" type="text" class="form-control" id="group_chapter_channel_id" name="group_chapter_channel_id" >
						<?php if ($_USER->canManageGroup($groupid)) { ?>
							<option data-section="<?= $_COMPANY->encodeId(0) ?>" value="<?= $_COMPANY->encodeId(0) ?>" ><?= $group->val('groupname')." ".$_COMPANY->getAppCustomization()['group']['name-short']; ?></option>
						<?php } ?>

						<?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
						<optgroup label="<?=$_COMPANY->getAppCustomization()['chapter']['name-short']?>">
							<?php if($group->val('chapter_assign_type') == 'auto'){  ?> <!--chapter auto assign -->
								<option data-section="<?= $_COMPANY->encodeId(1) ?>" value="<?= $_COMPANY->encodeId(0) ?>"  disabled ><?= sprintf(gettext("%s will be assigned automatically based on users office location"),$_COMPANY->getAppCustomization()['chapter']['name-short']); ?></option>
							<?php } else { ?>
							<?php for($i=0;$i<count($chapters);$i++){ ?>
							<?php if ($_USER->canManageGroupChapter($groupid,$chapters[$i]['regionids'],$chapters[$i]['chapterid'])) { ?>
							<option data-section="<?= $_COMPANY->encodeId(1) ?>" value="<?= $_COMPANY->encodeId($chapters[$i]['chapterid']) ?>"  >&emsp;<?= htmlspecialchars($chapters[$i]['chaptername']); ?></option>
							<?php } ?>
							<?php } ?>
						<?php } ?>
						</optgroup>

						<?php } ?>

						<?php if ($_COMPANY->getAppCustomization()['channel']['enabled']) { ?>
						<optgroup  label="<?=$_COMPANY->getAppCustomization()['channel']['name-short']?>">
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

				<div class="form-group">
					<label class="control-lable col-sm-12" for="inviteMembersTextArea"><?= gettext("Email");?><span style="color: #ff0000;">*</span></label>
					<div class="col-sm-12">
						<textarea id="inviteMembersTextArea" rows="3" tabindex="0" class="form-control" name="emails"  value="" placeholder="<?= gettext("Enter up to 1000 emails");?>" required></textarea>
					</div>
				</div>
				<div class="form-group">
					
					<div class="col-sm-12 text-center">
						<button id="" class="btn btn-affinity prevent-multi-clicks" type="button" onclick="addNewGroupMember('<?=$_COMPANY->encodeId($groupid);?>');" name="invite"><?= sprintf(gettext('Add %s'),$_COMPANY->getAppCustomization()['group']['memberlabel']);?></button>
						<button class="btn btn-affinity-gray" type="button" onclick="openAddMemberForm();" name="invite"><?= gettext("Close");?></button>
					</div>
				</div>
			</form>
		</div>
	</div>

	<div class="col-md-7 chapter_select text-right mt-0 mb-3" style="display: <?= ($_ZONE->val('app_type') !== 'talentpeak' || !$group->isTeamsModuleEnabled()) ? 'block' : 'none'; ?>">
	<?php
		$datatableFilterColCount = 4;
		if($_COMPANY->getAppCustomization()['chapter']['enabled']){
			$datatableFilterColCount = $datatableFilterColCount+1;
		}
		if($_COMPANY->getAppCustomization()['channel']['enabled']){
			$datatableFilterColCount = $datatableFilterColCount+1;
		}
	?>
		<select aria-label="<?= gettext("members by chapter");?>" class="form-control col-6 float-md-right mt-0" name="chapter" id="chapter" onchange="membersByChapters('<?= $encGroupId;?>',this.value,<?= $datatableFilterColCount;?>)">
			<?php if ($_USER->canManageGroup($groupid)) {
				$chooseAChapter = $_COMPANY->encodeId(0);
				$section = $_COMPANY->encodeId(0);
			?>
			<option data-section="<?= $_COMPANY->encodeId(0) ?>" value="<?= $_COMPANY->encodeId(0);?>"><?= Group::GetGroupName($groupid)?></option>

			<?php } ?>
			<?php if ($chapters) { ?>
				<optgroup label="<?=$_COMPANY->getAppCustomization()['chapter']['name-short-plural']?>">
				<?php foreach ($chapters as $chapter) { ?>
					<?php if ($_USER->canManageGroupChapter($groupid,$chapter['regionids'],$chapter['chapterid'])){
						$any_allowed = 1;
						if (!isset($chooseAChapter)) {
							$chooseAChapter = $_COMPANY->encodeId($chapter['chapterid']);
							$section = $_COMPANY->encodeId(1);
						}
						?>
					<option data-section="<?= $_COMPANY->encodeId(1) ?>" value="<?= $_COMPANY->encodeId($chapter['chapterid'])?>"><?= htmlspecialchars($chapter['chaptername']); ?></option>
					<?php } ?>
				<?php
				}
				?>
				</optgroup>
			<?php } ?>
			<?php if ($channels) { ?>
				<optgroup label="<?=$_COMPANY->getAppCustomization()['channel']['name-short-plural']?>">
				<?php foreach ($channels as $channel) {     ?>
					<?php if ($_USER->canManageGroupChannel($groupid, $channel['channelid'])){
						$any_allowed = 1;
						if (!isset($chooseAChapter)) {
							$chooseAChapter = $_COMPANY->encodeId($channel['channelid']);
							$section = $_COMPANY->encodeId(2);
						}
					?>
					<option data-section="<?= $_COMPANY->encodeId(2) ?>" value="<?= $_COMPANY->encodeId($channel['channelid'])?>"><?= htmlspecialchars($channel['channelname']); ?></option>
					<?php } ?>
				<?php
				}
				?>
				</optgroup>
			<?php } ?>
		</select>
	</div>
	<div class="col-md-5 text-right" id="addMemberBtnDiv">
        <?php if($_COMPANY->getAppCustomization()['group']['manage']['allow_add_members'] && ($_ZONE->val('app_type') !== 'talentpeak' || !$group->isTeamsModuleEnabled())){ ?>
		    <button onclick="openAddMemberForm('<?=$_COMPANY->encodeId($groupid)?>')" class="btn btn-primary">				
			<?= sprintf(gettext('Add %s'),$_COMPANY->getAppCustomization()['group']['memberlabel']);?>
		
		</button>
        <?php } ?>
        </div>
	</div>

	<div class="table-responsive mt-3 ">
		<table id="table-members-server" class="table table-hover display compact" width="100%" summary="This table displays the list of group members">
			<thead>
				<tr>
					<th width="23%" class="color-black" scope="col"><?= gettext("Name");?></th>
				<?php if($_COMPANY->getAppCustomization()['chapter']['enabled']){ ?>
					<th width="12%" class="color-black" scope="col"><?=$_COMPANY->getAppCustomization()['chapter']['name-short-plural']?></th>
				<?php } ?>
				<?php if($_COMPANY->getAppCustomization()['channel']['enabled']){ ?>
					<th width="12%" class="color-black" scope="col"><?=$_COMPANY->getAppCustomization()['channel']['name-short-plural']?></th>
				<?php } ?>
					<th width="20%" class="color-black" scope="col"><?= gettext("Email");?></th>
					<th width="10%" class="color-black" scope="col"><?= gettext("Since");?></th>
					<th width="8%" class="color-black" scope="col"></th>
				
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
</div>	
<script>
	$(document).ready(function() {
		var totalCols = 6;
        <?php if (!$_COMPANY->getAppCustomization()['chapter']['enabled']){ ?>
            totalCols = totalCols -1;
        <?php } ?>
        <?php if (!$_COMPANY->getAppCustomization()['channel']['enabled']){ ?>
            totalCols = totalCols -1;
        <?php } ?>

		var notOrderable = [1,2,5];
        var orderBy = 0;
      
		if (totalCols == 5){
			notOrderable = [1,4];
		} else if(totalCols == 4){
			notOrderable = [3];
		}
		else if(totalCols == 3){
			notOrderable = [];
		}
     
		var x = parseInt(localStorage.getItem("local_variable_for_table_pagination"));
		var dtable = 	$('#table-members-server').DataTable({
				serverSide: true,
				bFilter: true,
				bInfo : false,
				bDestroy: true,
				pageLength:x,
				order: [[ orderBy, "asc" ]],
				language: {
					searchPlaceholder: "<?= gettext('name or email');?>",
					url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
				},
				columnDefs: [
					{ targets: notOrderable, orderable: false }
					],				
				ajax:{
						url :"ajax.php?getGroupMembersList=<?=$encGroupId?>&chapter=<?=$chooseAChapter?>&section=<?=$section?>", // json datasource
						type: "POST",  // method  , by default get
						error: function(data){  // error handling
							$(".table-grid-error").html("");
							$("#table-members-server").append('<tbody class="table-grid-error"><tr><th colspan="6">No data found!</th></tr></tbody>');
							$("#table-grid_processing").css("display","none");
						},complete : function(){
							$(".deluser").popConfirm({content: ''});
							$('.initial').initial({
								charCount: 2,
								textColor: '#ffffff',
								color: window.tskp?.initial_bgcolor ?? null,
								seed: 0,
								height: 30,
								width: 30,
								fontSize: 15,
								fontWeight: 300,
								fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
								radius: 0
							});

							retainPopoverLastFocus(); //When Cancel the popover then retain the last focus.
						}
					},

			});
			
			$(".dataTables_filter input")
			.unbind()
			.bind("input", function(e) {
				if(this.value.length >= 3 || e.keyCode == 13) {
					dtable.search(this.value).draw();
				}
				if(this.value == "") {
					dtable.search("").draw();
				}
				return;
			});
			
			screenReadingTableFilterNotification('#table-members-server',dtable);
		});

$('#table-members-server').on( 'length.dt', function ( e, settings, len ) {
    localStorage.setItem("local_variable_for_table_pagination", len);
} );

$(document).on("keydown", ".three-dot-action-btn", function(e) {
  if (e.which == 13) {
    $(this).trigger("click");
  }
})
</script>