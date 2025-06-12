<div class="container-offset-lr mt-5">
  <div class="row">
    <div class="col-12">
      <div class="card p-4 mt-2">
        <div class="row mb-3">
          <div class="col-md-6">
            <h4 ><?= !empty($super_admin) ? 'Edit Super Admin' : 'Create New Super Admin' ?></h4>
          </div>
        </div>

        <form
          method="POST"
          action="<?= !empty($super_admin) ? 'manage_super_admins?action=update' : 'manage_super_admins?action=create' ?>"
          onsubmit="submitCreateOrUpdateSuperAdminForm(event)"
        >
          <input type="hidden" name="super_admin_id" value="<?= $super_admin['superid'] ?? '' ?>">

          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input
              type="text"
              class="form-control"
              id="username"
              placeholder="Username"
              name="username"
              value="<?= $super_admin['username'] ?? '' ?>"
              <?= !empty($super_admin) ? 'readonly' : '' ?>
            >
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input
              type="email"
              class="form-control"
              id="email"
              placeholder="Email"
              name="email"
              value="<?= $super_admin['email'] ?? '' ?>"
              <?= !empty($super_admin) ? 'readonly' : '' ?>
            >
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input
              type="text"
              class="form-control"
              id="password"
              placeholder="Password"
              name="password"
              value="<?= !empty($super_admin) ? '******' : $password ?>"
            >

            <?php if (!empty($super_admin)) { ?>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="update_password" id="update_password">
                <label class="form-check-label" for="update_password">
                  Do you want to update the password?
                </label>
              </div>
            <?php } ?>
          </div>

          <div class="mb-2">
            <strong>Select Companies</strong>
          </div>

          <?php foreach ($companies as $company) { ?>
            <div class="form-check mb-1">
              <input
                class="form-check-input"
                type="checkbox"
                name="manage_company_ids[]"
                id="company_<?= $company['companyid'] ?>"
                value="<?= $company['companyid'] ?>"
                <?= in_array($company['companyid'], $super_admin['manage_companyids'] ?? []) ? 'checked' : '' ?>
              >
              <label class="form-check-label" for="company_<?= $company['companyid'] ?>">
                <?= $company['companyname'] ?> (<?= $company['subdomain'] ?>)
              </label>
            </div>
          <?php } ?>

          <div class="mt-4 mb-2">
            <strong>Select Permissions</strong>
          </div>

          <?php foreach (Permission::cases() as $permission) { ?>
            <div class="form-check mb-1">
              <input
                class="form-check-input"
                type="checkbox"
                name="permissions[]"
                id="perm_<?= $permission->value ?>"
                value="<?= $permission->value ?>"
                <?= in_array($permission->value, $super_admin['permissions'] ?? []) ? 'checked' : '' ?>
              >
              <label class="form-check-label" for="perm_<?= $permission->value ?>">
                <?= $permission->value ?>
              </label>
            </div>
          <?php } ?>

          <div class="mt-4">
            <a href="list_super_admins" class="btn btn-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary">Submit</button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>
