<div class="custom-fields">
  <?php foreach ($custom_fields as $field => $value) { ?>
    <?php if (empty($value)) { continue; } ?>
    <div class="d-flex mr-3">
      <div class="content d-flex" style="font-size: small;">
        <b><?= $field ?>: &nbsp;</b>
        <span><?= htmlspecialchars($value) ?></span>
      </div>
    </div>
  <?php } ?>
</div>
