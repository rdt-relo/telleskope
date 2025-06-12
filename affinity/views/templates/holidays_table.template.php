<table id="table_holidays" class="table table-hover display compact" style="width:100%" summary="This table display the list of holidays">
    <thead>
        <tr>
            <th style="width:35%;"><?= gettext("Cultural Observance");?></th>
            <th style="width:20%;" class="text-center"><?= gettext("Cultural Observance Date");?></th>
            <th style="width:40%;"><?= gettext("Description");?></th>
            <th class="action-no-sort"></th>
        </tr>
    </thead>
    <tbody>
      <?php foreach($holidays as $holiday){ $opts=0; ?>
        <tr>
            <td>
              <?php if($holiday['isactive'] == Event::STATUS_DRAFT || $holiday['isactive'] == Event::STATUS_UNDER_REVIEW){ ?>
                  <span style="text-align:justify;color:red;">
                      <?= $holiday['eventtitle']; ?>
                      <img src="img/draft_ribbon.png" alt="Draft icon image" height="16px"/>
                  </span>
              <?php } else { ?>
                  <?= $holiday['eventtitle']; ?>
              <?php } ?>
          </td>
            <td  class="text-center">
                <p><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($holiday['start'],true,false,false,'','UTC');  ?> </p>
                <?php if($holiday['end']>$holiday['start']){ ?>
                <p>-</p>
                <p><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($holiday['end'],true,false,false,'','UTC');  ?></p>
                <?php } ?>
            </td>
            <td>
                <?= convertHTML2PlainText($holiday['event_description'], 140); ?>
            </td>
            <td >
              <div class="">
              <button id="holiday_<?= $_COMPANY->encodeId($holiday['eventid']); ?>" class="btn-no-style dropdown-toggle fa fa-ellipsis-v mt-3" data-toggle="dropdown"></button>

                  <ul class="dropdown-menu" style="width:200px;">
                  <?php if($isAllowedToCreateContent && ($holiday['isactive'] == Event::STATUS_DRAFT || $holiday['isactive'] == Event::STATUS_UNDER_REVIEW)){ $opts++; ?>
                      <li><a href="javascript:void(0)" class="" onclick="viewHolidayDetail('<?= $_COMPANY->encodeId($holiday['eventid']); ?>',1)"><i class="fa fas fa-eye" aria-hidden="true"></i>&emsp;<?= gettext("View");?></a></li>

                      <li><a href="javascript:void(0)" class="" onclick="newHolidayModal('<?= $_COMPANY->encodeId($holiday['groupid'])?>','<?= $_COMPANY->encodeId($holiday['eventid']); ?>')"><i class="fa fas fa-edit" aria-hidden="true"></i>&emsp;<?= gettext("Edit");?></a></li>
                  <?php } ?>

                    <?php if($isAllowedToPublishContent && $holiday['isactive'] != Event::STATUS_ACTIVE){ $opts++;?>
                      <li><a href="javascript:void(0)" class="confirm pop-identifier" data-toggle="popover" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to activate this Cultural Observance");?>?" onclick="activateOrDeactivateHoliday('<?= $_COMPANY->encodeId($holiday['eventid']); ?>',<?= Event::STATUS_ACTIVE; ?>,'<?= $_COMPANY->encodeId($groupid); ?>')"><i class="fa fa-lock" aria-hidden="true"></i>&emsp;<?= gettext("Activate");?></a></li>
                  <?php } ?>

                    <?php if($isAllowedToPublishContent && $holiday['isactive'] == Event::STATUS_ACTIVE){ $opts++; ?>
                      <li><a href="javascript:void(0)" class="confirm pop-identifier" data-toggle="popover" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to deactivate this Cultural Observance");?>?" onclick="activateOrDeactivateHoliday('<?= $_COMPANY->encodeId($holiday['eventid']); ?>',<?= Event::STATUS_DRAFT; ?>,'<?= $_COMPANY->encodeId($groupid); ?>')"><i class="fa fa-unlock" aria-hidden="true"></i>&emsp;<?= gettext("Deactivate");?></a></li>
                  <?php } ?>

                  <?php if(($isAllowedToCreateContent || $isAllowedToPublishContent) && $holiday['isactive'] != Event::STATUS_ACTIVE){ $opts++; ?>
                      <li><a href="javascript:void(0)" class="confirm pop-identifier" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to delete this Cultural Observance");?>?" onclick="deleteHoliday('<?= $_COMPANY->encodeId($holiday['eventid']); ?>','<?= $_COMPANY->encodeId($groupid); ?>')" ><i class="fa fa-trash" aria-hidden="true" ></i>&emsp;<?= gettext("Delete");?></a></li>
                  <?php } ?>

                  <?php if (!$opts){  ?>
                      <li><a href="javascript:void(0)" class="disabled"><i class="fa fas fa-exclamation" aria-hidden="true"></i>&emsp;<?= gettext("No options available");?></a></li>
                  <?php } ?>

                  </ul>
              </div>
            </td>
        </tr>
      <?php } ?>
    </tbody>
</table>
<script>
    $(document).ready(function(){
        var dtable = $('#table_holidays').DataTable( {
			"order": [[0, 'desc']],
			"bPaginate": true,
			"bInfo" : false,
            'language': {
                "sZeroRecords": "<?= gettext('No data available in table');?>",
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },	
            'aoColumnDefs': [{
                'bSortable': false,
                'aTargets': ['action-no-sort']
             }],
			
		});
        screenReadingTableFilterNotification('#table_holidays',dtable);
    });

$('.pop-identifier').each(function() {
	$(this).popConfirm({
	container: $("#manageHolidaysModal"),
	});
});
</script>