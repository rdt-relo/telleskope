window.tskp ||= {}

window.tskp.attachments = {
  files: [],
  topictype: null,
  topicid: null,
  container_id: 'js-attachments-modal-container',
  _container: null,
  max_file_size: 50,
  max_file_attachments: 3,

  get container() {
    if (this._container) {
      return this._container;
    }

    this._container = $('#' + this.container_id);
    if (!this.container.length) {
      $('#loadAnyModal').after(`<div id="${this.container_id}"></div>`);
      this._container = $('#' + this.container_id);
    }

    return this._container;
  },

  viewAttachments: function (event) {
    preventMultiClick(1);

		var btn = $(event.currentTarget)
		var topictype = btn.data('topictype');
		var topicid = btn.data('topicid');

    $.ajax({
      url: 'ajax_attachments.php?view_attachments=1',
      type: 'GET',
      data: {
        topictype,
        topicid
      },
      success : (data) => {
        try {
          var json = JSON.parse(data);
          Swal.fire({
            title: json.title,
            text: json.message
          });
        } catch (e) {
        openNestedModal(this.container, data);
        }
        setTimeout(() => {
          $('#btn_close').trigger('focus');
        }, 500)
      },
      complete: function() {
        preventMultiClick(0);
      }
    });
  },

  openAttachmentsUploader: function (event) {
    preventMultiClick(1);

		var btn = $(event.currentTarget)
		var topictype = btn.data('topictype');
		var topicid = btn.data('topicid');

    $.ajax({
      url: 'ajax_attachments?open_attachments_uploader=1',
      type: 'GET',
      data: {
        topictype,
        topicid
      },
      success : (data) => {
        try {
          var json = JSON.parse(data);
          Swal.fire({
            title: json.title,
            text: json.message
          });
        } catch (e) {
          openNestedModal(this.container, data);
        }
      },
      complete: function() {
        preventMultiClick(0);
      }
    });
  },

  initUploader: function (topictype, topicid, max_file_attachments) {
    this.topictype = topictype;
    this.topicid = topicid;
    this.max_file_attachments = max_file_attachments;
    this.files = [];

    this.refreshAttachmentsUploaderUI();
  },

  refreshAttachmentsUploaderUI: function () {
    var select_btn = this.container.find('input');
    var submit_btn = this.container.find('button[type="submit"]');

    if (typeof ejs === 'undefined') {
      select_btn.hide();
      submit_btn.hide();

      $.ajax({
        url: `<?= TELESKOPE_.._STATIC ?>/vendor/js/ejs-3.1.10/ejs.min.js`,
        dataType: 'script',
        cache: true,
        success: () => {
          this.refreshAttachmentsUploaderUI();
    }
      });

      return;
    }

    select_btn.show();

    if (this.files.filter(function (file) {
      return file.status === 'selected';
    }).length) {
      submit_btn.show();
    } else {
      submit_btn.hide();
      this.container.find('input[type="file"]').val('');
    }

    this.container.find('.js-files-list').html(ejs.render(window.tskp.templates.attachments_uploader_filelisting_template, {files: this.files}));
  },

  onFileSelect: function (input) {
    this.files = this.files.filter(function (file) {
      return !['failed', 'big_file'].includes(file.status);
    });

    if (!input.files || !(input.files.length)) {
      return;
    }

    if (input.files.length > this.max_file_attachments) {
      Swal.fire({title: 'Error', text: `Please select ${this.max_file_attachments} files or less`});
      return;
    }

    for (var i = 0; i < input.files.length; i++) {
      input.files[i].status = 'selected';

      if (input.files[i].size > this.max_file_size * 1024 * 1024) {
        input.files[i].status = 'big_file';
      }

      this.files.push(input.files[i]);
    }

    this.refreshAttachmentsUploaderUI();
  },

  removeSelectedFile: function (btn) {
    var index = $(btn).closest('tr').data('index');
    this.files.splice(index, 1);
    this.refreshAttachmentsUploaderUI();
	$('.btn-secondary').focus();
  },

  uploadFiles: function (btn) {
    for (var i = 0; i < this.files.length; i++) {
      if (this.files[i].status === 'selected') {
        this.uploadFile(this.files[i]);
      }
    }
  },

  uploadFile: function (file) {   
    preventMultiClick(1);
    file.status = 'uploading';   
    var hiddenDiv = document.getElementById("hidden_div_for_notification");
    if(hiddenDiv) {
      hiddenDiv.innerHTML="<?= gettext('A file is being uploaded');?>";
    }
    this.refreshAttachmentsUploaderUI();

    var form_data = new FormData();
    form_data.set('file', file);
    form_data.set('topictype', this.topictype);
    form_data.set('topicid', this.topicid);

    $.ajax({
      url: 'ajax_attachments.php?upload_attachment=1',
      method: 'POST',
      data: form_data,
      processData: false,
      contentType: false,
      success: (data) => {
        var json = JSON.parse(data);
        if (json.status === 0) {
          file.status = 'failed';        
          var hiddenDiv = document.getElementById("hidden_div_for_notification");
          if(hiddenDiv) {
            hiddenDiv.innerHTML="<?= gettext('A file uploading failed');?>";
          }  
          this.refreshAttachmentsUploaderUI();
          return;
        }

        $("#hidden_div_for_notification").attr('aria-live','polite');
        file.status = 'uploaded';
        var hiddenDiv = document.getElementById("hidden_div_for_notification");
        if(hiddenDiv) {
          hiddenDiv.innerHTML="<?= gettext('A file is uploaded');?>";
        }       
        this.refreshAttachmentsUploaderUI();        
      },
      error: (jqxhr, status, thrownError) => {
        jqxhr.skip_default_error_handler = true;
        file.status = 'failed';
        this.refreshAttachmentsUploaderUI();
      }
    });

    preventMultiClick(0);
  },

  deleteAttachment: function (attachment_id) {
    $.ajax({
      url: 'ajax_attachments?delete_attachment=1',
      type: 'POST',
      data: {
        attachment_id
      },
      success: function (data) {
        var json = JSON.parse(data);
        $(`#js-attachment-${attachment_id}`).remove();
        Swal.fire({
          title: json.title,
          text: json.message,
        }).then(function () {

          setTimeout(() => {
            $('#btn_close').focus();
          }, 200)		

		});

    setTimeout(() => {
			$(".swal2-confirm").focus();
		}, 200)

      }
    });
  },

  renderBudgetRequestAttachments: function (attachments) {
    if (!attachments) {
      return '';
    }

    return `
      <tr>
        <td width="20%"><?=gettext('File Attachments:')?></td>
        <td>
          ${attachments.map(function (attachment) {
            return `
              <a href="${attachment.download_url}" class="js-download-link">
                ${attachment.image_icon}
                &nbsp;
                ${attachment.display_name}
                (${attachment.readable_size})
                &nbsp;&nbsp;
              </a>
            `;
          })}
        </td>
      </tr>
    `;
  }
}
