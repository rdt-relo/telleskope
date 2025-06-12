<?php if (count($chapters)) { ?>
  <div class="row">
    <div class="col-md-12">
        <div class="form-group">
        <label for="getChapterAboutUs"><?= sprintf(gettext("Select %s"),$_COMPANY->getAppCustomization()['chapter']['name-short']);?></label>
            <select id="getChapterAboutUs" class="form-control"
                    onchange="getChapterAboutUs('<?= $_COMPANY->encodeId($groupid) ?>',this.value)">
                <?php foreach ($chapters as $ch) { ?>
                    <option value="<?= $_COMPANY->encodeId($ch['chapterid']) ?>" <?= $ch['chapterid']== $chapterid  ? 'selected' : ''; ?>><?= htmlspecialchars($ch['chaptername']); ?></option>
                <?php } ?>
            </select>
        </div>
    </div>
   </div>
    <div class="clearfix"></div>
    <div id="ChapterAboutUs">
        <?php
        include __DIR__ . '/about_chapter.template.php';
        ?>
    </div> 
<?php } else { ?>
    <div class="col-md-12">
        <p><?= sprintf(gettext("Sorry! No %s found"),$_COMPANY->getAppCustomization()['chapter']['name-short-plural']);?></p>
    </div>
<?php } ?>
<script>
    $(document).ready(function () {
        $('#getChapterAboutUs').focus();
    });
</script>