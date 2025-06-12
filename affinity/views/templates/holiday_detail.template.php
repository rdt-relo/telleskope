<div class="modal" tabindex="-1" id="holidayDetailModal">
    <div aria-label="<?= $holiday->val('eventtitle'); ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
      <div class="modal-content">
        <div class="modal-header">
          <div class="modal-title p-3">
              <h4>
              <?php if($holiday->val('isactive') == Event::STATUS_DRAFT || $holiday->val('isactive') == Event::STATUS_UNDER_REVIEW){ ?>
                  <span style="text-align:justify;color:red;">
                  <?= $holiday->val('eventtitle'); ?>
                      <img src="img/draft_ribbon.png" alt="Draft icon image" height="16px"/>
                  </span>
              <?php } else { ?>
                  <?= $holiday->val('eventtitle'); ?>
              <?php } ?>              
              </h4>
              <p>&nbsp;</p>
              <p><?= gettext("On");?>: <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($holiday->val('start'),true,false,false,'','UTC');  ?> <?= (substr($holiday->val('end'),0,10) > substr($holiday->val('start'),0,10)) ? ' to '. $_USER->formatUTCDatetimeForDisplayInLocalTimezone($holiday->val('end'),true,false,false,'','UTC') : '' ;  ?> </p>
              <p><?= gettext("Published in");?>: <?= $groupName ? $groupName : 'Global'; ?></p>
          </div>         
        </div>
        <div class="modal-body">
            <div class="col-md-12" id="post-inner">
                <?= $holiday->val('event_description'); ?>
            </div>
        </div>
        <div class="modal-footer">
            <button id="btn_close2" type="button" class="btn btn-secondary" data-dismiss="modal"><?= gettext("Close");?></button>
        </div>
      </div>
    </div>
  </div>
<script>
$('#holidayDetailModal').on('shown.bs.modal', function () {
   $('#btn_close2').trigger('focus');
});

$('#holidayDetailModal').on('hidden.bs.modal', function () {    
    if ($('.modal').is(':visible')){
        $('body').addClass('modal-open');
    } 
    setTimeout(function(){
      $('#holiday_<?= $_COMPANY->encodeId($eventid);?>').focus();
    },600);    
})
</script>