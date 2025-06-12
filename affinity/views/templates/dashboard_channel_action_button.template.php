<div class="" style="color: #fff; float: left;">
    <button aria-label="<?= $channel['channelname']; ?>" id="channel_<?= $encodedChannelId; ?>" class="btn btn-sm btn-affinity dropdown-toggle" title="Action" type="button" data-toggle="dropdown">
      <?= gettext("Action"); ?>
      &emsp;&#9662;
    </button>
    <ul class="dropdown-menu dropdown-menu-right" style="width: 250px; cursor: pointer;">        
        <li>
            <a href="javascript:void(0)" class="edit"
            onclick="add_edit_channel_modal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $encodedChannelId; ?>')">
            <i class="fa fa-edit"></i>&nbsp; <?= gettext("Edit");?></a>
        </li>
        <li>
            <?php if ($channel['isactive'] == 1) { ?>
            <a aria-label="<?= gettext("Deactivate");?>" href="javascript:void(0)" class="deluser"
                onclick="changeGroupChannelStatus('<?= $_COMPANY->encodeId($groupid); ?>','<?= $encodedChannelId; ?>',0,this)"
                title="<strong><?= gettext("Are you sure you want to Deactivate!");?></strong>"><i
                        class="fa fa-lock" title="Deactivate"
                        aria-hidden="true"></i>&nbsp; <?= gettext("Deactivate");?></a>
            <?php } else { ?>
            <a aria-label="<?= gettext("Activate");?>" href="javascript:void(0)" class="deluser"
                onclick="changeGroupChannelStatus('<?= $_COMPANY->encodeId($groupid); ?>','<?= $encodedChannelId; ?>',1,this)"
                title="<strong><?= gettext("Are you sure you want to Activate!");?></strong>"><i
                        class="fa fa-unlock-alt" title="Activate"
                        aria-hidden="true"></i>&nbsp; <?= gettext("Activate");?></a>
            <?php } ?>
        </li>
      
    </ul>
</div>
  