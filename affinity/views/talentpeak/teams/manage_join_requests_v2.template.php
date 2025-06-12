<?php
if (empty($allRoles)) {
    echo gettext('No options are available at this time, please check back later');
    return;
}
?>

<?php
$table_classes = 'table-borderless';
require __DIR__ . '/manage_join_requests_table.template.php';
?>
