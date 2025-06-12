<style>
  .create_template {
      background-color: #0077B5;
      color: #fff;
      margin-top: 15px;
  }
  div#ui-datepicker-div {
      z-index: 100 !important;
  }
</style>
<div class="col-md-12">
  <div class="row">
    <div class="col-md-11">
      <h2><?= gettext("Communication Configuration").' - '. $group->val('groupname_short');?></h2>
    </div>
    <div class="col-md-1 pull-right text-right" style="margin-bottom: -16px;">
      <?php
      $page_tags = 'manage_communication';
      ViewHelper::ShowTrainingVideoButton($page_tags);
      ?>
    </div>    
  </div><hr class="lineb" >
</div>

<div class="col-md-12">
    <div class="row">
    <div class="col-md-12">
      <div class="form-container">
            <form class="form-horizontal" id="email_template_form">
                <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                <p class="ml-3 mb-2"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                <div class="form-group">                
                  <label class="col-md-2 control-lable" ><?= gettext("Trigger");?></label>
                  <div class="col-md-10">
                      <select aria-label="<?= gettext('Trigger');?>" type="text" class="form-control" id="communication_trigger"  name="communication_trigger" onchange="setMainScope('<?=$encGroupId?>')" >
                        <option value="">-- <?= gettext("Select Communication Trigger");?> --</option>
                          <option value="" disabled>Join / Leave Triggers:</option>
                          <option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_JOIN'])?>" >&emsp;<?= gettext("On Join");?></option>
                      <?php if ($groupid){ ?>
                          <option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_LEAVE'])?>" >&emsp;<?= gettext("On Leave");?></option>
                          <?php if ($groupid && ($_USER->isGrouplead($groupid) || $_USER->isAdmin())){ ?>
                          <option value="" disabled>Membership Anniversary Triggers:</option>
                          <!--option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_BEFORE_NINETY'])?>" >&emsp;<?= sprintf(gettext("Anniversary: %s days before membership anniversary day"), 90);?></option -->
                          <option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_BEFORE_SIXTY'])?>" >&emsp;<?= sprintf(gettext("Anniversary: %s days before membership anniversary day"), 60);?></option>
                          <option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_BEFORE_FORTYFIVE'])?>" >&emsp;<?= sprintf(gettext("Anniversary: %s days before membership anniversary day"), 45);?></option>
                          <option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_BEFORE_THIRTY'])?>" >&emsp;<?= sprintf(gettext("Anniversary: %s days before membership anniversary day"), 30);?></option>
                          <!-- option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_BEFORE_FOURTEEN'])?>" >&emsp;<?= sprintf(gettext("Anniversary: %s days before membership anniversary day"), 14);?></option -->
                          <!-- option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_BEFORE_SEVEN'])?>" >&emsp;<?= sprintf(gettext("Anniversary: %s days before membership anniversary day"), 7);?></option -->
                          <option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY'])?>" >&emsp;<?= gettext("Anniversary: On membership anniversary day");?></option>
                          <!--option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_AFTER_SEVEN'])?>" >&emsp;<?= sprintf(gettext("Anniversary: %s days after membership anniversary day"), 7);?></option -->
                          <!--option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_AFTER_FOURTEEN'])?>" >&emsp;<?= sprintf(gettext("Anniversary: %s days after membership anniversary day"), 14);?></option -->
                          <option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_AFTER_THIRTY'])?>" >&emsp;<?= sprintf(gettext("Anniversary: %s days after membership anniversary day"), 30);?></option>
                          <option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_AFTER_FORTYFIVE'])?>" >&emsp;<?= sprintf(gettext("Anniversary: %s days after membership anniversary day"), 45);?></option>
                          <option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_AFTER_SIXTY'])?>" >&emsp;<?= sprintf(gettext("Anniversary: %s days after membership anniversary day"), 60);?></option>
                          <!--option value="<?= $_COMPANY->encodeId(Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY_AFTER_NINETY'])?>" >&emsp;<?= sprintf(gettext("Anniversary: %s days after membership anniversary day"), 90);?></option -->
                          <?php } ?>
                      <?php } ?>
                      </select> 
                  </div>
                </div>

                <?php if ($groupid){ ?>
                <div class="form-group" id="communication_scope">
                    <label class="col-md-2 control-lable" ><?= gettext("Scope");?></label>
                    <div class="col-md-10">
                      <select aria-label="<?= gettext('Scope');?>" type="text" class="form-control" id="newsletter_chapter" name="chapterid"  onchange="processCommunicationData('<?=$encGroupId?>')" >
                        <option value="">-- <?= gettext("Select a scope");?> --</option>
                      <?php if ($_USER->canManageGroup($groupid)) { ?>
                        <option data-section="<?= $_COMPANY->encodeId(0) ?>" value="<?= $_COMPANY->encodeId(0) ?>" ><?= $group->val('groupname'); ?></option>
                      <?php } ?>

                    <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
                    <optgroup label="<?=$_COMPANY->getAppCustomization()['chapter']['name-short']?>">
                        <?php for($i=0;$i<count($chapters);$i++){ ?>
                        <?php if ($_USER->canManageGroupChapter($groupid,$chapters[$i]['regionids'],$chapters[$i]['chapterid'])) { ?>
                        <option data-section="<?= $_COMPANY->encodeId(1) ?>" value="<?= $_COMPANY->encodeId($chapters[$i]['chapterid']) ?>"  >&emsp;<?= htmlspecialchars($chapters[$i]['chaptername']); ?></option>
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
              <?php } else { ?>
                <input type="hidden" id="newsletter_chapter"  name="chapterid" value="<?= $_COMPANY->encodeId(0); ?>">
              <?php } ?>

                <div id="action_button">
                  
                </div>
                
            </form>
      </div>
    </div>
  </div>
</div>


<script>
  $("#li-invite").addClass("active2");
  $("#form-invitation").bind("keydown", function(e) {
    if (e.keyCode === 13) return false;
  });
</script>
<script>
  function setMainScope(groupId) {
    var communicationTriggerDropdown = document.getElementById('communication_trigger');
    var newsletterChapterDropdown = document.getElementById('newsletter_chapter');
    // hide whole block instead of just the dropdown
    var scopeBlock = document.getElementById('communication_scope');

    // Get the selected value
    var selectedValue = communicationTriggerDropdown.value;

    if(scopeBlock != null){
    // values that should hide the newsletter dropdown
    var hideDropdownValues = [
        <?= "'" . implode("','", $_COMPANY->encodeIdsInArray(array_keys(Group::GROUP_COMMUNICATION_ANNIVERSARSY_TRIGGER_TO_INTERVAL_DAY_MAP))) . "'"; ?>
    ];

      if( hideDropdownValues.includes(selectedValue)){
      var defautOptionValue = '<?= $_COMPANY->encodeId(0) ?>';
      newsletterChapterDropdown.value = defautOptionValue;
      scopeBlock.style.display="none";
      }else{
        scopeBlock.style.display="block";
      }
    }
    processCommunicationData(groupId);    
  }

</script>
