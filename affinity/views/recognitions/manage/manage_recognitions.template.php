<div class="col-md-12">
    <div class="row">
        <div class="col-12">
          <h2><?= sprintf(gettext("Manage %s"),Recognition::GetCustomName(true)).' - '. $group->val('groupname_short');?></h2>
          <hr class="lineb" >
        </div>
      <?php
        $newbtn = ' <button type="button" class="btn btn-primary" onclick=\'configureRecognitionCustomFields("' . $enc_groupid . '")\'>' . gettext("Configuration") .'</button>';
        include(__DIR__ . "/../../../views/templates/manage_section_dynamic_button.html");
        
      ?>
        <div class="col-md-12">
            <div class="col-md-3">
              <input type="hidden" id="filterByGroup" value="<?= $enc_groupid; ?>">
              <input type="hidden" id="filterByState" value="<?= $_COMPANY->encodeId(1)?>">
                <div class="form-group col-md-12 " style="float:right !important;">
                    <label for="filterByYear" style="font-size:small;"><?= gettext("Filter by Calendar Year");?></label>
                    <select class="form-control" id="filterByYear" onchange="filterRecognitions('<?= $enc_groupid; ?>'
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
                          echo  $_COMPANY->encodeId($year_filter-1); 
                        }
                        else{
                          echo  $_COMPANY->encodeId($current_year -$i);
                        }              
                        ?>" <?= $sel; ?> ><?=  $i==0 ? gettext("Current Year"). " (".($current_year -$i).")" : gettext("Calendar Year")." (".($current_year -$i).")"; ?></option>
                        <?php } ?>
                        <option value="<?= $_COMPANY->encodeId($current_year +1); ?>" <?= ($year_filter > $current_year) ? '' : '' ?>>
                            <?= gettext("Future Years");?>  (><?php echo $current_year; ?>)
                        </option>
                    </select>
                </div>
            </div>
            <div class="col-md-9">
              <?php
                include(__DIR__ . "/download_recognitioin_report.template.php");
              ?> 
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-md-12 mt-2">
            <div class="clearfix"></div>
            <div class="table-responsive" id="recognitionTableContainer">
                <?php
                    include(__DIR__ . "/recognition_table_view.template.php");
                ?>
            </div>
        </div>
    </div>
</div>
