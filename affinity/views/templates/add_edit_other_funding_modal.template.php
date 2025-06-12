<div id="newOtherFundingModal" class="modal fade">
    <div aria-label="<?= $modalTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="review_publish_title"><?= $modalTitle;?></h2>
            <button aria-label="<?= gettext("close");?>" type="button" id="btn_close" class="close" data-dismiss="modal">×</button>
        </div>
        <div class="modal-body" >
            <div class="col-md-12">
                <form  class="" id="otherFundingForm">
                    <input type="hidden" name="funding_id" value="<?= $_COMPANY->encodeId($funding_id); ?>">
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                    <div class="form-group">
                        <label for="funding_source"><?= gettext("Funding Source");?><span style="color:red"> *</span></label>
                        <input class="form-control" type="text" id="funding_source" name="funding_source" placeholder="<?= gettext('Funding Source e.g. Executive Sponsor, Council etc')?>" value="<?=  $funding_id ? $otherFundingDetail['funding_source'] : '';?>" required>
                    </div>


                    <div class="form-group" id="options">
                            <label class="control-lable" id="scope_owner_label"><?= gettext("Scope");?><span style="color: #ff0000;"> *</span></label>
                            <select aria-label="<?= gettext('Scope');?>" type="text" class="form-control" id="funding_scope" name="funding_scope" required>
                            
                              <?php if ($_USER->canManageBudgetGroup($groupid) && !$selectedChapterId) { ?>
                                <option data-section="<?= $_COMPANY->encodeId(0) ?>" value="<?= $_COMPANY->encodeId(0) ?>" ><?= $group->val('groupname')." ".$_COMPANY->getAppCustomization()['group']['name-short']; ?></option>
                              <?php } ?>
                        <?php if(!empty($chapters)){ ?>
                              <optgroup label="<?=$_COMPANY->getAppCustomization()['chapter']['name-short-plural']?>">
                              <?php for($i=0;$i<count($chapters);$i++){ ?>
                                <?php if ($_USER->canManageBudgetGroupChapter($groupid,$chapters[$i]['regionids'],$chapters[$i]['chapterid'])) { ?>
                                    <?php 
                                        $sel = $funding_id && !$selectedChapterId ? " disabled" : '';
                                        if ($selectedChapterId) {
                                            if ($selectedChapterId == $chapters[$i]['chapterid']) {
                                                $sel = " selected";
                                            } else {
                                                $sel = " disabled";
                                            }

                                        }
                                    ?>
                                  <option data-section="<?= $_COMPANY->encodeId(1) ?>" value="<?= $_COMPANY->encodeId($chapters[$i]['chapterid']) ?>"  <?= $sel; ?> >&emsp;<?= htmlspecialchars($chapters[$i]['chaptername']); ?></option>
                                <?php } ?>
                              <?php } ?>
                            </optgroup>
                        <?php } ?>
                    
                            </select>
                        </div>

                    <div class="form-group">
                        <label for="start_date"><?= gettext("Funding Date");?><span style="color:red"> *</span></label>
                        <input class="form-control"  id="start_date" name="funding_date"  value="<?=  $funding_id ? $otherFundingDetail['funding_date'] : '';?>" placeholder="YYYY-MM-DD" readonly required>
                        <p style="font-size: small"><?= gettext("Funds will be added to the budget year corresponding to the funding date");?></p>
                    </div>
                    <div class="form-group">
                        <label for="budget_amount"><?= gettext("Amount");?> (<?= $_COMPANY->getCurrencySymbol(); ?>) <span style="color:red"> *</span></label>
                        <input class="form-control" type="number" min='0' id="budget_amount" name="funding_amount"  value="<?=  $funding_id ? round($otherFundingDetail['funding_amount'],2) : '';?>" placeholder="e.g. 200.25" required>
                    </div>
                    <div class="form-group">
                        <label for="funding_description"><?= gettext("Funding Description");?><span style="color:red"> *</span></label>
                        <textarea class="form-control" id="funding_description" placeholder="<?= gettext("Funding description");?> ..." rows="4" name="funding_description" required><?=  $funding_id ? $otherFundingDetail['funding_description'] : '';?></textarea>
                    </div>
                </form>
            </div>
            <div class="clearfix"></div>
        </div>
        <div class="modal-footer text-center">
          <button type="submit" class="btn btn-affinity prevent-multi-clicks"  onclick='saveGroupOtherFund("<?= $_COMPANY->encodeId($groupid); ?>", "<?= $_COMPANY->encodeId($selectedChapterId)?>");' ><?= gettext("Submit");?></button>
          <button type="submit" class="btn btn-affinity"   onclick="manageOtherFunding('<?= $_COMPANY->encodeId($groupid); ?>')" ><?= gettext("Close");?></button>
         
        </div>
      </div>

    </div>
  </div>





<script>
    $('#newOtherFundingModal').on('shown.bs.modal', function () {
        $('#btn_close').trigger('focus')
    });

    $(document).ready(function() {
        $('#reviewers').select2({
            placeholder: "<?= gettext("Search and Select reviewer");?>",
        });       
    });
</script>
