<div class="tl-container-offset-lr">
  <div class="row">
    <div class="col-12">
      <div class="card p-3">
        <div class="mb-3 d-flex">
          <h1 class="h4 mb-0"><?= $pagetitle; ?></h1>
          <button aria-label="<?= gettext('Add new blocked keyword') ?>" class="btn btn-link p-0" onclick="showAddUpdateBlockedKeywordModal('<?= $_COMPANY->encodeId(0)?>')">
            <i class="fa fa-plus-circle fa-lg text-primary ms-2" title="<?= gettext('Add new blocked keyword') ?>" aria-hidden="true"></i>
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-hover js-data-table display compact w-100" summary="<?= gettext('This table shows the list of Blocked Keywords') ?>">
            <thead>
              <tr>
                <th scope="col" style="width: 50%;"><?= gettext('Blocked Keyword') ?></th>
                <th scope="col" style="width: 20%;"><?= gettext('Action') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($blocked_keywords as $keyword) { ?>
                <tr>
                  <td><?= $keyword->val('blocked_keyword') ?></td>
                  <td>
                    <button type="button" title="Edit" class="btn btn-sm text-primary" onclick="showAddUpdateBlockedKeywordModal('<?= $keyword->encodedId() ?>')">
                      <i class="fa fa-edit"></i>
                    </button>
                    <button type="button" aria-label="<?= gettext('Delete') ?>" class="btn btn-sm deluser ms-2 text-primary" onclick="deleteBlockedKeyword('<?= $keyword->encodedId() ?>')" title="<?= gettext('Are you sure to Delete?') ?>">
                      <i class="fa fa-trash" aria-hidden="true" title="<?= gettext('Delete') ?>"></i>
                    </button>
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
  $(document).ready(function() {
    $('.js-data-table').DataTable({
      order: [],
      bPaginate: true,
      bInfo: true,
      columnDefs: [
        { targets: [-1], orderable: false }
      ],
      language: {
        searchPlaceholder: "<?= gettext('...');?>",
        url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
      },
    });
  });

  function showAddUpdateBlockedKeywordModal(blocked_keyword_id)
  {
      $.ajax({
          url: 'ajax.php?showAddUpdateBlockedKeywordModal=1',
          type: 'GET',
          data: {
              blocked_keyword_id,
          },
          success: function (data) {
            $('#loadAnyModal').html(data);
            const modalElement = document.querySelector('#blockedKeywordModal');
            if (modalElement) {
              const bsModal = new bootstrap.Modal(modalElement, {
              backdrop: 'static',
              keyboard: false
              });
              bsModal.show();
            }

          }
      });
  }
</script>
