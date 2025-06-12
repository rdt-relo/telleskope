
<div class="container-offset-lr mt-4">
  <div class="row">
    <div class="col-12">
      <div class="card p-4">
        <div class="border-bottom mb-4">
          <h1><?= $pageTitle ?></h1>
        </div>
        <form action="" method="POST">
          <div class="mb-3 row">
            <label class="col-md-2 col-form-label">Username</label>
            <div class="col-md-10">
              <p class="form-control-plaintext"><?= $eai_account->getUsername() ?></p>
            </div>
          </div>

          <div class="mb-3 row">
            <label class="col-md-2 col-form-label">Permissions</label>
            <div class="col-md-10">
              <?php
                $permissions = [];
                if ($eai_account->val('module') == 'graph') {
                    $permissions = EaiGraphPermission::cases();
                } elseif ($eai_account->val('module') == 'uploader') {
                    $permissions = EaiUploaderPermission::cases();
                }
                foreach ($permissions as $permission):
              ?>
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    name="permissions[]"
                    value="<?= $permission->value ?>"
                    <?= $eai_account->hasPermission($permission) ? 'checked' : '' ?>
                  >
                  <label class="form-check-label">
                    <?= $permission->value ?>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-3 row">
            <label class="col-md-2 col-form-label">Zones</label>
            <div class="col-md-10">
              <?php foreach ($zones as $zone): ?>
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    name="zone_ids[]"
                    value="<?= $_COMPANY->encodeId($zone['zoneid']) ?>"
                    <?= $eai_account->canAccessZone($zone['zoneid']) ? 'checked' : '' ?>
                  >
                  <label class="form-check-label">
                    <?= $zone['zonename'] ?> (<?= $zone['app_type'] ?>)
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-3 row">
            <label class="col-md-2 col-form-label">IP Allow List</label>
            <div class="col-md-4">
              <input class="form-control" placeholder="In CIDR Notation e.g. 10.20.30.40/32" id="js-input-ip">
            </div>
            <div class="col-md-2 mt-2 mt-md-0">
              <button type="button" onclick="addIp()" class="btn btn-sm btn-primary">Add IP</button>
            </div>
          </div>

          <div class="mb-3 row">
            <label class="col-md-2 col-form-label">List of IP addresses allowed:</label>
            <div class="col-md-8" id="js-eai-whitelist-container">
              <?php if ($eai_account->getEaiWhitelistedIps()): ?>
                <?php foreach ($eai_account->getEaiWhitelistedIps() as $ip): ?>
                  <div class="row align-items-center mb-2 js-eai-whitelist-row">
                    <div class="col-md-4">
                      <input class="form-control" type="text" readonly name="eai_whitelisted_ips[]" value="<?= $ip ?>">
                    </div>
                    <div class="col-md-8">
                      <button type="button" class="btn btn-sm btn-link" onclick="removeIp(event)">Remove IP</button>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                All IP addresses are allowed
              <?php endif; ?>
            </div>
          </div>

          <div class="mb-3 row">
            <div class="col-md-2"></div>
            <div class="col-md-8 d-flex gap-2">
              <button type="button" class="btn cancel_template btn-secondary" onclick="window.history.back();">Cancel</button>
              <button class="btn create_template btn-primary" name="submit" type="submit">Submit</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
  <?php require_once __DIR__ . '/../../include/common/js/ip.js.php'; ?>

  function addIp() {
    var ip = $('#js-input-ip').val();

    if (!isIP(ip)) {
      swal.fire({
        title: 'Error!',
        text: 'Please enter a valid IP address in CIDR notation e.g. 10.20.30.40/[16-32]'
      });
      return;
    }

    $('#js-input-ip').val('');

    $('#js-eai-whitelist-container').append(`
      <div class="row align-items-center mb-2 js-eai-whitelist-row">
        <div class="col-md-4">
          <input class="form-control" type="text" readonly name="eai_whitelisted_ips[]" value="${ip}">
        </div>
        <div class="col-md-8">
          <button type="button" class="btn btn-sm btn-link" onclick="removeIp(event)">Remove IP</button>
        </div>
      </div>
    `);
  }

  function removeIp(e) {
    $(e.target).closest('.js-eai-whitelist-row').remove();

    if (!$('.js-eai-whitelist-row').length) {
      $('#js-eai-whitelist-container').text('All IP addresses are allowed');
    }
  }
</script>
