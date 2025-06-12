<?php foreach ($custom_fields as $field => $value) { ?>
<div class="row">
  <div class="col">
    <?= $field ?>
  </div>
  <div class="col-8">
    <?= htmlspecialchars($value) ?>
  </div>
</div>
<?php } ?>
