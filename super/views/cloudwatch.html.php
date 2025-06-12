<div class="container-offset-lr mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card p-3 mb-5">
                <div class="card-body">
                    <h1>Cloudwatch Logs</h1>
                    <div class="mb-3">
                        <h6> Company ID <?php echo $_GET['company-id'] ?? ''; ?> </h6>
                    </div>

                    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="GET" class="form-horizontal">
                        <div class="mb-3">
                            <input id="company-id" class="form-control" type="hidden" name="company-id" value="<?php echo $_GET['company-id'] ?? ''; ?>">
                        </div>

                        <div class="mb-3 row">
                            <label for="user-id" class="col-lg-3 col-form-label text-end">User ID:</label>
                            <div class="col-lg-6">
                                <input id="user-id" class="form-control" type="number" name="user-id" value="<?php echo $_GET['user-id'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="user-email" class="col-lg-3 col-form-label text-end">User Email:</label>
                            <div class="col-lg-6">
                                <input id="user-email" class="form-control" type="email" name="user-email" value="<?php echo $_GET['user-email'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="zone-id" class="col-lg-3 col-form-label text-end">Zone ID:</label>
                            <div class="col-lg-6">
                                <input id="zone-id" class="form-control" type="number" name="zone-id" value="<?php echo $_GET['zone-id'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="search-by" class="col-lg-3 col-form-label text-end">Search By:</label>
                            <div class="col-lg-6">
                                <div class="form-check form-check-inline">
                                    <input type="radio" name="search-by" value="interval" class="form-check-input" <?= ($_GET['search-by'] ?? 'interval') === 'interval' ? 'checked' : '' ?>>
                                    <label class="form-check-label">Interval</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" name="search-by" value="date" class="form-check-input" <?= ($_GET['search-by'] ?? '') === 'date' ? 'checked' : '' ?>>
                                    <label class="form-check-label">Date</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="relative-time" class="col-lg-3 col-form-label text-end">Search in last</label>
                            <div class="col-lg-6">
                                <select name="relative-time" id="relative-time" class="form-select" required>
                                    <?php foreach (RELATIVE_TIMES as $rt_k => $rt_v) { ?>
                                    <option value="<?=$rt_k?>" <?= (($_GET['relative-time'] ?? '5-minute') === $rt_k) ? 'selected' : '' ?> >
                                        <?=$rt_k?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="start-date" class="col-lg-3 col-form-label">Start Date</label>
                            <div class="col-lg-6">
                                <input id="start-date" class="form-control" type="text" placeholder="Start Date" name="start-date" autocomplete="off" required value="<?= $_GET['start-date'] ?? '' ?>">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="start-time" class="col-lg-3 col-form-label">Start Time (<?= $timezone->getName() ?>)</label>
                            <div class="col-lg-6">
                                <input id="start-time" class="form-control" type="time" placeholder="Start Time" name="start-time" autocomplete="off" required value="<?= $_GET['start-time'] ?? '00:00' ?>">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="end-date" class="col-lg-3 col-form-label">End Date</label>
                            <div class="col-lg-6">
                                <input id="end-date" class="form-control" type="text" placeholder="End Date" name="end-date" autocomplete="off" required value="<?= $_GET['end-date'] ?? '' ?>">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="end-time" class="col-lg-3 col-form-label">End Time (<?= $timezone->getName() ?>)</label>
                            <div class="col-lg-6">
                                <input id="end-time" class="form-control" type="time" placeholder="End Time" name="end-time" autocomplete="off" required value="<?= $_GET['end-time'] ?? '23:59' ?>">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="severity" class="col-lg-3 col-form-label text-end">Severity</label>
                            <div class="col-lg-6">
                                <select name="severity" id="severity" class="form-select">
                                    <option value="" <?= empty($_GET['severity']) ? 'selected' : ''; ?>>All</option>
                                    <?php foreach (Logger::SEVERITY as $severityKey => $severityValue) { ?>
                                        <option value="<?= $severityValue ?>" <?= ($_GET['severity'] ?? '') === $severityValue ? 'selected' : ''; ?>><?= $severityValue ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="module" class="col-lg-3 col-form-label text-end">Module</label>
                            <div class="col-lg-6">
                                <select name="module" id="module" class="form-select">
                                    <option value="" <?= empty($_GET['module']) ? 'selected' : ''; ?>>All</option>
                                    <?php foreach (Logger::MODULE as $moduleKey => $moduleValue) { ?>
                                        <option value="<?= $moduleValue ?>" <?= ($_GET['module'] ?? '') === $moduleValue ? 'selected' : ''; ?>><?= $moduleValue ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="search-keyword" class="col-lg-3 col-form-label text-end">Search keyword:</label>
                            <div class="col-lg-6">
                                <input id="search-keyword" class="form-control" type="text" name="search-keyword" value="<?= $_GET['search-keyword'] ?? '' ?>">
                                <small class="form-text text-muted">If searching by email, enter email hash instead of email; email hash can be generated using check user functionality.</small>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="max-count" class="col-lg-3 col-form-label text-end">Maximum Number of Records:</label>
                            <div class="col-lg-6">
                                <input id="max-count" class="form-control" type="number" name="max-count" value="<?php echo $_GET['max-count'] ?? '100'; ?>">
                            </div>
                        </div>

                        <div class="mb-3 text-center">
                            <button type="submit" name="add" class="btn btn-primary">Submit</button>
                        </div>
                    </form>

                    <table class="table table-bordered table-hover display compact" id="company_log_table" style="width:100%;">
                        <thead>
                            <tr>
                                <?php
                                $cols = array_keys($logs[0] ?? []);
                                foreach ($cols as $col) {
                                    echo "<th scope='col'>{$col}</th>";
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach (($logs ?? []) as $log) {
                                echo "<tr>";
                                foreach ($cols as $col) {
                                    echo "<td style='word-break:break-all;'>{$log[$col]}</td>";
                                }
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery('#menu-toggle').click(function(e) {
        e.preventDefault();
        jQuery('wrapper').toggleClass('toggled');
    });
</script>

<script>
    $('#sidebar-wrapper ul li:nth-child(8)').addClass('myactive');
</script>
<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/datatables-2.1.8/datatables.min.js"></script>
<script>
    function renderDateFields(search_by) {
        $('#relative-time, #start-date, #start-time, #end-date, #end-time')
                .prop('disabled', true)
                .closest('div.mb-3')
                .hide();

        if (search_by === 'interval') {
            $('#relative-time')
                .prop('disabled', false)
                .closest('div.mb-3')
                .show();
            return;
        }

        $('#start-date, #start-time, #end-date, #end-time')
            .prop('disabled', false)
            .closest('div.mb-3')
            .show();
    }

    $(document).ready(function() {
        $('#start-date, #end-date').datepicker({
            prevText: 'click for previous months',
            nextText: 'click for next months',
            showOtherMonths: true,
            selectOtherMonths: false,
            dateFormat: 'yy-mm-dd'
        });

        renderDateFields('<?= $_GET['search-by'] ?? 'interval' ?>');

        $('input[type=radio][name=search-by]').change(function() {
            renderDateFields(this.value);
        });

        $('#company_log_table').DataTable( {
            "order": [0, "asc"],
            "bPaginate": true,
            "bInfo" : false,
            pageLength:100,
            lengthChange:true,
            language: {
                searchPlaceholder: "Type anything.."
            }
        });
    });
</script>
</body>
</html>