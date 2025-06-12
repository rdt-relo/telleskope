<script src="<?= TELESKOPE_CDN_STATIC ?>/vendor/js/datatables-2.1.8/datatables.min.js"></script>

<div class="container-offset-lr mt-5">
  <div class="row">
    <div class="col-12">
      <div class="card p-4 mt-2">
        <div class="row align-items-center mb-3">
          <div class="col-md-6">
            <h1 ><?= gettext('Super Admins') ?></h1>
          </div>
          <div class="col-md-6 text-end">
            <a class="btn btn-outline-primary" href="manage_super_admins?action=new">+ <?= gettext('Add New Super Admin') ?></a>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-hover display compact" id="js-super-admins-list">
            <thead class="table-light">
              <tr>
                <th width="5%">Super Id</th>
                <th>Super Admin Type</th>
                <th>Email</th>
                <th width="5%">Is Active?</th>
                <th width="5%">Is Blocked?</th>
                <th width="5%">Is Expired?</th>
                <th width="5%">2FA enabled?</th>
                <th width="20%">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($super_admins as $super_admin) { ?>
                <tr
                  <?= $super_admin['isactive'] && !$super_admin['is_blocked'] && !$super_admin['is_expired'] && !empty($super_admin['google_auth_code']) ? '' : 'style="background-color: #fde1e1;"' ?>
                >
                  <td><?= $super_admin['superid'] ?></td>
                  <td><?= $super_admin['is_super_super_admin'] ? 'Super Super Admin' : 'Super Admin' ?></td>
                  <td><?= $super_admin['email'] ?></td>
                  <td><?= $super_admin['isactive'] ? 'Yes' : 'No' ?></td>
                  <td><?= $super_admin['is_blocked'] ? 'Yes' : 'No' ?></td>
                  <td><?= $super_admin['is_expired'] ? 'Yes' : 'No' ?></td>
                  <td><?= !empty($super_admin['google_auth_code']) ? 'Yes' : 'No' ?></td>
                  <td>
                    <?php if (!$super_admin['is_super_super_admin']) { ?>
                      <div class="dropdown">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                          Action
                        </button>
                        <ul class="dropdown-menu">
                          <li>
                            <a class="dropdown-item" href="/1/super/manage_super_admins?action=edit&superid=<?= $super_admin['superid'] ?>">Edit</a>
                          </li>
                          <li>
                            <a class="dropdown-item confirm" href="javascript:void(0);" onclick="activeToggleSuperAdmin(<?= $super_admin['superid'] ?>)" title="Are you sure?">
                              <?= (int) $super_admin['isactive'] ? 'Make Inactive' : 'Make Active' ?>
                            </a>
                          </li>

                          <?php if ((int) $super_admin['is_blocked']) { ?>
                            <li>
                              <a class="dropdown-item confirm" href="javascript:void(0);" onclick="unblockSuperAdmin(<?= $super_admin['superid'] ?>)" title="Are you sure?">
                                Unblock Admin
                              </a>
                            </li>
                          <?php } ?>

                          <?php if ((int) $super_admin['is_expired']) { ?>
                            <li>
                              <a class="dropdown-item confirm" href="javascript:void(0);" onclick="renewPassword(<?= $super_admin['superid'] ?>)" title="Are you sure?">
                                Renew Password
                              </a>
                            </li>
                          <?php } ?>

                          <?php if (!empty($super_admin['google_auth_code'])) { ?>
                            <li>
                              <a class="dropdown-item confirm" href="javascript:void(0);" onclick="resetGoogleAuthToken(<?= $super_admin['superid'] ?>)" title="Are you sure?">
                                Reset 2FA Token
                              </a>
                            </li>
                          <?php } ?>
                        </ul>
                      </div>
                    <?php } else { ?>
                      -
                    <?php } ?>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
  // Initialize DataTables
  $('#js-super-admins-list').DataTable();
  $("#sidebar-wrapper ul li:nth-child(13)").addClass("myactive");
</script>
