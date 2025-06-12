<?php foreach ($custom_fields as $field => $value) { ?>
  <small><?= $field ?>:</small>
  <strong><?= trim(htmlspecialchars($value)) ?: 'Blank' ?></strong>
<?php } ?>
<p class="px-2"><strong>Other:</strong>