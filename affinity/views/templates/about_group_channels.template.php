<?php if (isset($channels) &&  count($channels)) { ?>
    <div class="row">
     <div class="col-md-12">
        <div class="form-group">
            <label for="getChannelAboutUs"><?= sprintf(gettext("Select %s"),$_COMPANY->getAppCustomization()['channel']['name-short']);?></label>
            <select id="getChannelAboutUs" class="form-control"
                    onchange="getChannelAboutUs('<?= $_COMPANY->encodeId($groupid) ?>',this.value)">
                <?php foreach ($channels as $ch) { ?>
                    <option value="<?= $_COMPANY->encodeId($ch['channelid']) ?>" <?= $ch['channelid'] == $channelid ? 'selected' : ''; ?>><?= htmlspecialchars($ch['channelname']); ?></option>
                <?php } ?>
            </select>
        </div>
     </div>
    </div>
    <div class="clearfix"></div>
    <div id="ChannelAboutUs">
        <?php
        include __DIR__ . '/about_channel.template.php';
        ?>
    </div>
<?php } else { ?>
    <div class="row">
      <div class="col-md-12">
        <p><?= sprintf(gettext("Sorry! No %s found"),$_COMPANY->getAppCustomization()['channel']['name-short-plural']);?></p>
      </div>
    </div>
<?php } ?>
<script>
    $(document).ready(function () {
        $('#getChannelAboutUs').focus();
    });
</script>