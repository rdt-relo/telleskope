<?php foreach ($custom_fields as $field => $value) { ?>
    <p class="px-2"><strong><?= $field ?>:</strong> <?= trim(htmlspecialchars($value)) ?: 'Blank' ?></p>
<?php } ?>