<div class="container-offset-lr mt-4">
  <div class="row">
    <div class="col-12">
      <div class="card p-3">
        <div>
          <h1 >Cloudwatch Logs</h1>
          <div class="col-12">
            <h6>Company ID <?= $_GET['company_id'] ?></h6>
          </div>

          <form action="" method="GET" class="needs-validation" novalidate>
            <input type="hidden" name="company_id" value="<?= $_GET['company_id'] ?>">
            
            <!-- Select Zone -->
            <div class="mb-3 row">
              <label class="col-form-label col-sm-3 text-end">Select Zone</label>
              <div class="col-sm-6">
                <select name="zone_id" class="form-select" required>
                  <?php foreach ($zones as $zone) { ?>
                    <?php
                      $company_groups ??= [];
                      $company_groups += Group::GetAllGroupsByCompanyid($_COMPANY->id(), $zone['zoneid'], true);
                      $zone_id = $_COMPANY->encodeId($zone['zoneid']);
                    ?>
                    <option
                      value="<?= $zone_id ?>"
                      <?= (($_GET['zone_id'] ?? '') === $zone_id) ? 'selected' : '' ?>
                    >
                      <?= $zone['zonename'] ?>
                    </option>
                  <?php } ?>
                </select>
              </div>
            </div>

            <!-- Search Keyword -->
            <div class="mb-3 row">
              <label class="col-form-label col-sm-3 text-end">Search keyword:</label>
              <div class="col-sm-6">
                <input class="form-control" type="text" name="q" value="<?= $_GET['q'] ?? '' ?>">
              </div>
            </div>

            <!-- Select Type -->
            <div class="mb-3 row">
              <label class="col-form-label col-sm-3 text-end">Select Type:</label>
              <div class="col-sm-6">
                <select name="type" class="form-select">
                  <?php $types = [
                    '' => 'All',
                    TypesenseDocumentType::Post->value => 'Posts',
                    TypesenseDocumentType::Event->value => 'Events',
                    TypesenseDocumentType::Discussion->value => 'Discussions',
                    TypesenseDocumentType::Newsletter->value => 'Newsletters',
                  ];
                  foreach ($types as $type => $name) { ?>
                    <option
                      value="<?= $type ?>"
                      <?= ($_GET['type'] ?? '') === $type ? 'selected' : '' ?>
                    >
                      <?= $name ?>
                    </option>
                  <?php } ?>
                </select>
              </div>
            </div>

            <!-- Select Group -->
            <div class="mb-3 row">
              <label class="col-form-label col-sm-3 text-end">Select Group:</label>
              <div class="col-sm-6">
                <select name="group_id" class="form-select">
                  <option value="">All</option>
                  <?php foreach ($company_groups as $group) { ?>
                    <?php $group_id = $_COMPANY->encodeId($group->id()) ?>
                    <option
                      value="<?= $group_id ?>"
                      <?= (($_GET['group_id'] ?? '') === $group_id) ? 'selected' : '' ?>
                      class="js-group js-groups-zone-<?= $_COMPANY->encodeId($group->val('zoneid')) ?>"
                    >
                      <?= $group->val('groupname') ?>
                    </option>
                  <?php } ?>
                </select>
              </div>
            </div>

            <!-- Page Number -->
            <div class="mb-3 row">
              <label class="col-form-label col-sm-3 text-end">Page number:</label>
              <div class="col-sm-6">
                <input class="form-control" type="number" step="1" name="page" value="<?= $_GET['page'] ?? 1 ?>">
              </div>
            </div>

            <!-- Results Per Page -->
            <div class="mb-3 row">
              <label class="col-form-label col-sm-3 text-end">Results per page:</label>
              <div class="col-sm-6">
                <input class="form-control" type="number" step="1" name="per_page" value="<?= $_GET['per_page'] ?? 10 ?>">
              </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
              <button type="submit" class="btn btn-primary">Search</button>
            </div>
          </form>

          <?php if ($search_results) { ?>
            <div class="col-12 mt-4">
              <h4>Total results found: <?= $search_results['found'] ?></h4>
            </div>

            <?php foreach ($search_results['hits'] as $hit) { ?>
              <div class="mt-2">
                <pre><?= htmlentities(json_encode($hit, JSON_PRETTY_PRINT)) ?></pre>
              </div>
            <?php } ?>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  function renderGroups(is_initial_state = false)
  {
    var groups = $('.js-group');

    groups.hide();

    if (!is_initial_state) {
      groups.removeAttr('selected');
    }

    var classname = '.js-groups-zone-' + $('select[name="zone_id"]').val();
    groups.filter(classname).show();
  }

  $('select[name="zone_id"]').on('change', function() {
    renderGroups();
  });
  renderGroups(true);
</script>
<script>
    $("#sidebar-wrapper ul li:nth-child(11)").addClass("myactive");
</script>
