<script type="text/javascript">
    function closeCurrentTab() {
        close();
    }
    setTimeout(function() {
        closeCurrentTab();
    }, 1000 * 120);

</script>

<p>This page will automatically close after 60 seconds ... <input type="button" value="Close Tab" onclick="closeCurrentTab()"> immediately.</p>
<br>
<br>
<p>Inspecting file <strong><?= $filename ?></strong></p>
<p>
 <?php if (!empty($record_data['aws_kms_key_id'])) { ?>
  This file is encrypted with AWS KMS Key ID : <?= $record_data['aws_kms_key_id'] ?>
 <?php } else { ?>
  This file is not AWS-KMS encrypted
 <?php } ?>
</p>
<form>
  <input name="inspectHrisFile" type="hidden" value="1" />
  <input name="filename" type="hidden" value="<?= urlencode($filename) ?>" />
  <input name="s3_area" type="hidden" value="<?= $s3_area ?>" />
  <label>
    Search file by keyword:
    <input name="search_keyword" type="text" minlength="3" placeholder="atleast 3 chars" value="<?= $_GET['search_keyword'] ?? '' ?>" />
  </label>
  <select name="search-column">
    <option value="" <?= empty($_GET['search-column']) ? 'selected' : '' ?>>
      Search in all columns
    </option>
    <?php foreach ($record_data['cols'] as $col) { ?>
      <option value="<?= $col ?>" <?= (($_GET['search-column'] ?? '') === $col) ? 'selected' : '' ?>>
        <?= $col ?>
      </option>
    <?php } ?>
  </select>
  <input type="submit" value="Search" />
</form>
<?php if ($record_data['json_path']) { echo "<p>Json Path: {$record_data['json_path']}</p>"; }  ?>
<p>Showing first 3 records from a total of <strong><?=$record_data['record_count']?></strong> records</p>
<hr>
<pre>
<?php print_r($record_data['records']); ?>
</pre>
<hr>
