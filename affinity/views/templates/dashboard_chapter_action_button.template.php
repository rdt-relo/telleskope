<div class="" style="color: #fff; float: left;">
    <button aria-label="<?= $chapter['chaptername']; ?>" id="chapter_<?= $encodedChapterId; ?>" class="btn btn-sm btn-affinity dropdown-toggle" title="Action" type="button" data-toggle="dropdown">
      <?= gettext("Action"); ?>
      &emsp;&#9662;
    </button>
    <ul class="dropdown-menu dropdown-menu-right" style="width: 250px; cursor: pointer;">        
        <li>
            <a href="javascript:void(0)" class="edit"
            onclick="editChapterModal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $encodedChapterId; ?>','<?= $_COMPANY->encodeId($chapter['regionids']); ?>')">
            <i class="fa fa-edit" ></i>&nbsp; <?= gettext("Edit");?></a>

        </li> 
        <li>
            <?php if ($chapter['isactive'] == 1) { ?>
                <a aria-label="<?= gettext("Deactivate");?>" href="javascript:void(0)" class="deluser"
                    onclick="changeGroupChapterStatus('<?= $_COMPANY->encodeId($groupid); ?>','<?= $encodedChapterId; ?>','<?= $_COMPANY->encodeId($chapter['regionids']); ?>', 0, this)"
                    title="<strong><?= gettext("Are you sure you want to Deactivate!");?></strong>"><i
                            class="fa fa-lock" title="Deactivate"
                            aria-hidden="true"></i>&nbsp;  <?= gettext("Deactivate");?></a>

            <?php } else { ?>
                <a aria-label="<?= gettext("Activate");?>" href="javascript:void(0)" class="deluser"
                    onclick="changeGroupChapterStatus('<?= $_COMPANY->encodeId($groupid); ?>','<?= $encodedChapterId; ?>','<?= $_COMPANY->encodeId($chapter['regionids']); ?>', 1, this)"
                    title="<strong><?= gettext("Are you sure you want to Activate!");?></strong>"><i
                            class="fa fa-unlock-alt" title="Activate"
                            aria-hidden="true"></i>&nbsp; <?= gettext("Activate");?></a>

            <?php } ?>
        </li>
      
    </ul>
</div>

