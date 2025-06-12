window.tskp ||= {}

window.tskp.event_volunteer = {
  container_id: 'js-event-volunteer-modal-container',
  _container: null,

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

  openExternalEventVolunteerModal: function (jsevent) {
    var btn = $(jsevent.currentTarget);

    var eventid = btn.data('eventid');
    var volunteerid = btn.data('volunteerid');

    $.ajax({
      url: 'ajax_events.php?getMyExternalVolunteers=1',
      type: 'GET',
      data: {
        eventid,
        volunteerid,
      },
      tskp_submit_btn: btn,
      success: (html) => {
        if (volunteerid) {
          var div = $('<div></div>').html(html);
          this.container.find('.modal-body').html(div.find('.modal-body').html());
          return;
        } else {
          openNestedModal(this.container, html);
          this.addEventListeners();
        }
      }
    });
  },

  addOrEditExternalEventVolunteer: function (jsevent)
  {
    jsevent.preventDefault();

    var form = jsevent.target;
    var formdata = new FormData(form);

    var btn = $(form).find('[type="submit"]')[0];

    $.ajax({
      url: 'ajax_events.php?addExternalEventVolunteer=1',
      type: 'POST',
      data: formdata,
      processData: false,
			contentType: false,
      tskp_submit_btn: btn,
      success: (html) => {
        try {
          var json = JSON.parse(html);
          Swal.fire({
            title: json.title,
            text: json.message
          });
        } catch (e) {
          var div = $('<div></div>').html(html);
          this.container.find('.modal-body').html(div.find('.modal-body').html());
          this.addEventListeners();
        }
      }
    });
  },

  deleteExternalEventVolunteer: function (jsevent, eventid, volunteerid) {
    jsevent.preventDefault();

    var btn = $(jsevent.currentTarget);

    $.ajax({
      url: 'ajax_events.php?deleteExternalEventVolunteer=1',
      type: 'POST',
      data: {
        eventid,
        volunteerid,
      },
      tskp_submit_btn: btn,
      success: (html) => {
        var div = $('<div></div>').html(html);
        this.container.find('.modal-body').html(div.find('.modal-body').html());
        this.addEventListeners();
      }
    });
  },

  addEventListeners: function () {
    this.container.find('.tskp-hidemessage').delay(5000).fadeOut(3000);
    this.container.find('.tskp-popconfirm').popConfirm();
    this.container.find('table').DataTable();
  },

  openAddOrEditExternalVolunteerByLeaderModal: function (jsevent) {
    var btn = $(jsevent.currentTarget);
    var eventid = btn.data('eventid');
    var volunteerid = btn.data('volunteerid');

    $.ajax({
      url: 'ajax_events.php?openAddOrEditExternalVolunteerByLeaderModal=1',
      type: 'GET',
      data: {
        eventid,
        volunteerid
      },
      tskp_submit_btn: btn,
      success: (html) => {
        if (volunteerid) {
          openNestedModal(this.container, html);
        } else {
          var div = $('<div></div>').html(html);
          $('#new_volunteer_form_modal').find('.modal-body').html(div.find('.modal-body').html());
        }
      }
    });
  },

  addOrEditExternalEventVolunteerByLeader: function (jsevent) {
    jsevent.preventDefault();

    var form = jsevent.target;
    var formdata = new FormData(form);

    var btn = $(form).find('[type="submit"]')[0];

    $.ajax({
      url: 'ajax_events.php?addOrEditExternalEventVolunteerByLeader=1',
      type: 'POST',
      data: formdata,
      processData: false,
			contentType: false,
      tskp_submit_btn: btn,
      dataType: 'json',
      success: (jsonData) => {
        Swal.fire({
          title: jsonData.title,
          text: jsonData.message,
          focusConfirm: true,
          allowOutsideClick: false
        }).then((result) => {
          if (jsonData.status) {
            if (formdata.get('volunteerid')) {
              this.container.find('.modal').hide();
            }
            manageVolunteers(formdata.get('eventid'), 0);
          }
        });
      }
    });
  },
}
