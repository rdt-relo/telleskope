<style>
  pre {
    white-space: pre-wrap;
    /* Since CSS 2.1 */
    white-space: -moz-pre-wrap;
    /* Mozilla, since 1999 */
    white-space: -o-pre-wrap;
    /* Opera 7 */
    word-wrap: break-word;
    /* Internet Explorer 5.5+ */
    background-color: transparent !important;
    border-width: 0 !important;
  }
</style>
<div class="container-offset-lr mt-4">
  <div class="row">
    <div class="col-12">
      <div class="card card p-3">
        <div class="col-md-9">
          <h1 >HRIS File Browser</h1>
        </div>
        <div class="col-12">
          <div class="tab-content" id="jobs_data" style="margin-top: 20px;">
            <div class="table-responsive">
              <table id="table" class="table table-bordered table-hover table-striped">
                <thead>
                  <tr>
                    <th width="30%">Filename</th>
                    <th width="20%">Type</th>
                    <th width="20%">Last Modified</th>
                    <th width="15%">Filesize</th>
                    <th width="15%">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $hris_files = [...$user_data_sync_files, ...$user_data_delete_files]; ?>
                  <?php foreach ($hris_files as $file) { ?>
                    <?php $pathinfo = pathinfo($file['Key']); ?>
                    <?php $pathinfo_parts = explode('/', $pathinfo['dirname']); ?>
                    <?php $s3_area = end($pathinfo_parts); ?>
                    <tr>
                      <td>
                        <a class="text-primary" href="hris_file_browser?inspectHrisFile=1&filename=<?= urlencode($pathinfo['basename']) ?>&s3_area=<?= $s3_area ?>" target="_blank">
                          <?= $pathinfo['basename'] ?>
                        </a>
                      </td>
                      <td><?= $s3_area ?></td>
                      <td><?= $file['LastModified'] ?></td>
                      <td><?= $file['Size'] ?></td>
                      <td>
                        <form method="post" action="?deleteHrisFile=1">
                          <input type="hidden" name="filename" value="<?= urlencode($pathinfo['basename']) ?>">
                          <input type="hidden" name="s3_area" value="<?= $s3_area ?>">
                          <input type="submit" name="delete" value="Delete File" class="btn btn-danger btn-sm" onclick="return ('DELETE' == prompt('Type DELETE to continue'));">
                        </form>
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
  </div>
</div>
<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/datatables-2.1.8/datatables.min.js"></script>
<script>
  $(document).ready(function () {
    $('#table').DataTable({
      "order": [],
      "bPaginate": true,
      "bInfo": false,
      "pageLength": 50
    });
  });
</script>
