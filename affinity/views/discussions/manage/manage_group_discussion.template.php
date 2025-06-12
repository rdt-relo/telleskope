
<div class="col-md-12">
    <div class="row">
        <div class="col-9">
          <h2><?= gettext("Manage Discussions").' - '. $group->val('groupname_short');?></h2>
        </div>
        <div class="col-md-3">
          <?php if ($_USER->canManageGroup($groupid)) { ?>
          <div style="margin-bottom:-16px; margin-left: 60px;">
          <a href="javascript:void(0)" class="btn-affinity btn-sm btn settings-btn pull-left text-center ml-20" onclick="openDiscussionsConfigurationModal('<?= $_COMPANY->encodeId($groupid); ?>')" aria-label="Discussion Settings"><?= gettext("Settings");?></a>
          </div>
          <?php } ?>
            <div class="pull-right text-right" style="margin-bottom: -16px;">
                <?php
                $page_tags = 'manage_discussions';
                ViewHelper::ShowTrainingVideoButton($page_tags);
                ?>
            </div>
        </div>        
    </div><hr class="lineb" >
</div>
<div class="col-md-12">
    <div class="row">
        <div class="col-md-12">
            <div class="col-md-4">
              &nbsp;
              <input type="hidden" id="filterByState" value="<?= $_COMPANY->encodeId(1)?>">
            </div>
            <div class="col-md-4">
                <div class="form-group col-md-12 ">
                <?php if($groupid>0){ 
                  $chapters = Group::GetChapterList($groupid);
                  $channels= Group::GetChannelList($groupid);
                  ?>
                  
                    <label for="filterByGroup" style="font-size:small;"><?= gettext("Filter by Scope");?></label>
                    <select id="filterByGroup"  onchange="filterDiscussions('<?= $enc_groupid; ?>');" style="font-size:small;border-radius: 5px;" class="form-control" >
                      <?php if ($_USER->canCreateOrPublishOrManageContentInScopeCSV($groupid,0,0)) { ?>
                      <option data-section="<?= $_COMPANY->encodeId(0) ?>" value="<?= $_COMPANY->encodeId(0);?>" <?= $erg_filter_section == 0 && $erg_filter == 0 ? 'selected' : '' ?> ><?= Group::GetGroupName($groupid)?></option>
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
            <div class="col-md-4 ">
                <div class="form-group col-md-12 " style="float:right !important;">
                    <label for="filterByYear" style="font-size:small;"><?= gettext("Filter by Calendar Year");?></label>
                    <select class="form-control" id="filterByYear" onchange="filterDiscussions('<?= $enc_groupid; ?>'
                    );" style="font-size:small;border-radius: 5px;">
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
                        ?>" <?= $sel; ?> ><?=  $i==0 ? gettext("Current Year"). " (".($current_year -$i).")" : gettext("Calendar Year")." (".($current_year -$i).")"; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
      
        <div class="col-md-12 mt-2">
            <div class="clearfix"></div>
            <div class="table-responsive" id="discussionTableContainer">
                <?php
                    include(__DIR__ . "/group_discussions_table_view.template.php");
                ?>
            </div>
        </div>
    </div>
</div>