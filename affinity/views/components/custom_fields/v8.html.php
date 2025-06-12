<div class="d-flex d-inline-flex mr-3 col-12 mt-1">
  <div class="content d-flex">
    <p>
      <?php foreach ($custom_fields as $field => $value) { ?>
        <?php if (empty($value)) { continue; } ?>

        <?php if ($add_semicolon_prefix ?? false) { ?>
          ;&nbsp;&nbsp;
        <?php } ?>
        <?php $add_semicolon_prefix = true; ?>

        <?= htmlspecialchars($value) ?>
        <span class="dark-gray">
         (<?= $field ?>)
        </span>

      <?php } ?>
    </p>
  </div>
</div>
