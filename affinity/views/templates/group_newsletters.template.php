<div class="col-md-12">
  <div class="row">

      <div class="col-12">
        <h2><?= gettext("Manage Newsletters").' - '. $group->val('groupname_short');?></h2>
        <hr class="lineb" >
      </div>
    <?php
    if ($_USER->canCreateContentInGroupSomething($groupid)) {

        $isGlobal = !$groupid ? 1 : 0;
        if (Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['NEWSLETTER_CREATE_BEFORE'])) {
            $callOtherMethod = base64_url_encode(json_encode(array("method" => "loadCreateNewsletterModal", "parameters" => array($encGroupId, $isGlobal)))); // base64_encode for prevent js parsing error
            $hookid = $_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['NEWSLETTER_CREATE_BEFORE']);
            $encoded0 = $_COMPANY->encodeId(0);
            $on_create_function = "loadDisclaimerByHook('{$hookid}', '{$encoded0}', '0', '{$callOtherMethod}')";
        } else {
            $on_create_function = "loadCreateNewsletterModal('{$encGroupId}', '{$isGlobal}')";
        }
        $newbtn = '<button id="createNewsletter" class="btn btn-primary dropdown-toggle roster" type="button" onclick="' . $on_create_function . '">' . addslashes(gettext('Create Newsletter')) . '</button>';

        include(__DIR__ . "/manage_section_dynamic_button.html");
    }
    ?>
    <div class="col-md-12">
      <div class="col-md-4">
          <div class="form-group col-md-12 ">
              <label style="font-size:small;"><?= sprintf(gettext("Filter by %s State"), 'Newsletter');?></label>
              <select aria-label="<?= sprintf(gettext("Filter by %s State"), 'Newsletter');?>" class="form-control" onchange="filterNewsletters('<?= $encGroupId; ?>');" id="filterByState" style="font-size:small;border-radius: 5px;">
                <option value="<?= $_COMPANY->encodeId(2)?>" <?= $state_filter==2 ? 'selected' : '' ?> ><?= gettext("Draft / Not Published");?></option>
                <option value="<?= $_COMPANY->encodeId(1)?>" <?= $state_filter==1 ? 'selected' : '' ?> ><?= gettext("Published");?></option>
              </select>
            </div>

      </div>
      <div class="col-md-4">
        <div class="form-group col-md-12 ">
        <?php if($groupid>0){ 
          $chapters = Group::GetChapterList($groupid);
          $channels= Group::GetChannelList($groupid);
          ?>
          
            <label for="filterByGroup" style="font-size:small;"><?= gettext("Filter by Scope");?></label>
            <select id="filterByGroup"  onchange="filterNewsletters('<?= $encGroupId; ?>');" style="font-size:small;border-radius: 5px;" class="form-control" >
              <?php if ($_USER->canCreateOrPublishOrManageContentInScopeCSV($groupid,0,0)) {?>
              <option data-section="<?= $_COMPANY->encodeId(0) ?>" value="<?= $_COMPANY->encodeId(0);?>" <?= $erg_filter_section == 0 && $erg_filter == 0 ? 'selected' : '' ?> ><?= $groupid ? $group->val('groupname') : gettext('All');?></option>
              <?php } ?>
          <?php if ($chapters) { ?>
            <optgroup label="<?=$_COMPANY->getAppCustomization()['chapter']['name-short-plural']?>">
            <?php foreach ($chapters as $chapter) { ?>
              <?php if ($_USER->canCreateOrPublishOrManageContentInScopeCSV($groupid,$chapter['chapterid'],0)){ ?>
                <option data-section="<?= $_COMPANY->encodeId(1) ?>" value="<?= $_COMPANY->encodeId($chapter['chapterid'])?>" <?= $erg_filter_section == 1 && $erg_filter == $chapter['chapterid'] ? 'selected' : '' ?> ><?= htmlspecialchars($chapter['chaptername']); ?></option>
              <?php } ?>
            <?php
            }
            ?>
            </optgroup>
          <?php } ?>
          <?php if ($channels) { ?>
            <optgroup label="<?=$_COMPANY->getAppCustomization()['channel']['name-short-plural']?>">
            <?php foreach ($channels as $channel) {     ?>
              <?php if ($_USER->canCreateOrPublishOrManageContentInScopeCSV($groupid,0,$channel['channelid'])){ ?>
                <option data-section="<?= $_COMPANY->encodeId(2) ?>" value="<?= $_COMPANY->encodeId($channel['channelid'])?>" <?= $erg_filter_section == 2 && $erg_filter == $channel['channelid'] ? 'selected' : '' ?> ><?= htmlspecialchars($channel['channelname']); ?></option>
              <?php } ?>
            <?php
            }
            ?>
            </optgroup>
          <?php } ?>
          </select>
          
        <?php } ?>
        </div>

      </div>
      <div class="col-md-4">
          <div class="form-group col-md-12 " style="float:right !important;">
              <label for="filterByYear" style="font-size:small;"><?= gettext("Filter by Calendar Year");?></label>
              <select aria-label="<?= gettext("Filter by calendar year");?>" class="form-control" id="filterByYear" onchange="filterNewsletters('<?= $encGroupId; ?>');" style="font-size:small;border-radius: 5px;">
                  <?php
                  $current_year = date('Y');
                  for($i=(date("Y")-date("Y",strtotime($_COMPANY->val('createdon'))));$i>=0;$i--){
                      $sel = "";
                      if ($year_filter){
                          if (($year_filter > $current_year) && ($i == 0)) {
                              // Year filter can be set to future years in events, if so we select the last row
                              $sel = "selected";
                          }
                          else if ($year_filter == ($current_year -$i)){
                             $sel = "selected";
                          }

                      } else {
                        if (($current_year -$i) == $current_year){
                          $sel = "selected";
                        }
                      }
                  ?>
                  <option value="<?php
                  if (($year_filter > $current_year) && ($i == 0)) {
                      echo  $_COMPANY->encodeId($year_filter);
                  }
                  else{
                      echo  $_COMPANY->encodeId($current_year -$i);
                  }
                  ?>"  <?= $sel; ?> ><?=  $i==0 ? sprintf(gettext("Current Year (%s)"),($current_year -$i)) : sprintf(gettext("Calendar Year(%s)"),($current_year -$i)); ?></option>
                  <?php } ?>
              </select>
          </div>
      </div>
  </div>
  <div class="clearfix"></div>
    <div class="col-md-12">
      <div class="table-responsive " id="newsletterTable">
        <?php
            include(__DIR__ . "/group_newsletter_table.template.php");
        ?>
      </div>
    </div>
  </div>
</div>
<div id="review_or_publish_modal"></div>
