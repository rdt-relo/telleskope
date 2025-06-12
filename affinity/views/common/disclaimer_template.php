
<style>
  .active-page{
      display:block;
  }
  .inactive-page{
      display:none;
  }
</style>
<div class="modal fade" id="loadCompanyDisclaimerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" aria-modal="true" role="dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title"></h2>
      </div>
      <div class="modal-body">
      <?php 
        $p = 1;
        $totalDisclaimer = count($disclaimerObj);
        foreach($disclaimerObj as $disclaimer){ 
            $disclaimerMessage =  $disclaimer->getDisclaimerMessageForLanguage($_USER->val('language')); 
          ?>
            <div class="div-pagination<?= $p ==1 ? '-active-'.$hook : '-'.$hook; ?> <?= $p ==1 ? 'active-page' : 'inactive-page'; ?>" data-page="<?= $p; ?>" id="page<?= $hook;?>_<?= $p;?>" >
            <?= $disclaimerMessage['disclaimer']; ?>
          </div>
        <?php 
          $p++; }
        ?>
      </div>
      <div class="modal-footer">
      <?php if($totalDisclaimer > 1){ ?>
          <div class="col-md-12 mb-3">
              <ul class="pagination justify-content-end">
                  <li class="page-item prev<?= $hook; ?> disabled"><a class="page-link" onclick="suggestionsPagination(<?= $totalDisclaimer; ?>,'<?= $hook; ?>', 1)" href="javascript:void(0)"><?=gettext('Previous')?></a></li>
                  <li class="page-item next<?= $hook; ?>"><a class="page-link" onclick="suggestionsPagination(<?= $totalDisclaimer; ?>,'<?= $hook; ?>',2)" href="javascript:void(0)"><?=gettext('Next')?></a></li>
              </ul>
          </div>
      <?php } ?>
        <button id="modal_close_btn" type="button" class="btn btn-secondary" <?= $totalDisclaimer > 1 ? 'style="display:none;"':''?>
        <?php if ($reloadOnclose){ ?> onclick="closeModal();window.location.reload()" <?php } ?>
        <?php if(!empty($callOtherMethodOnClose)){ ?>
          onclick="closeModal();<?=$callOtherMethodOnClose['method']?>(
            <?php 
              $i = 1;
              foreach($callOtherMethodOnClose['parameters'] as $param){ ?>
                '<?=$param?>'
                <?php if(count($callOtherMethodOnClose['parameters'])>$i){ ?>,<?php } $i++; ?>
            <?php } ?>
              )";
        <?php } ?>
        ><?=gettext('Proceed')?></button>
      </div>
    </div>
  </div>