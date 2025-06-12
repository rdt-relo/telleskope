$.ajaxPrefilter(function(options, originalOptions, jqXHR ) {
  if (!options.tskp_submit_btn) {
    return;
  }

  var btn = $(options.tskp_submit_btn);
  btn.prop('disabled', true);
});

$(document).on('ajaxComplete', function(event, request, options ) {
  if (!options.tskp_submit_btn) {
    return;
  }

  var btn = $(options.tskp_submit_btn);
  btn.prop('disabled', false);
});

function preventMultiClick(action){

	if (action == 1) {
		$('.prevent-multi-clicks').prop('disabled', true);
	} else {
		$('.prevent-multi-clicks').prop('disabled', false);
	}
}

function openNestedModal(container, html)
{
  window.tskp ||= {};

  /**
   * If modal is already open in this 'container', then the already open modal would be hidden and destroyed
   * So the new modal would replace this already open modal
   * The minimized old modal would still remain minimized, so its essentially behaving like replacing the modal
   * Scenario is Manage-ERG > Manage-Teams listing > add/edit a team-member
   * Add/Edit team-members modal appears (Modal-1)
   * Click on already added team member's avatar, on clicking it user profile modal appears (Modal-2)
   * This user has a manager avatar, on clicking it we try to open another user profile modal (Modal-3), but it conflicts with the already open modal-2 as it has the same container-ID
   * So we close Modal-2 and open Modal-3
   * On closing Modal-3, Modal-1 appears not Modal-2, so we essentially remove Modal-2 and replace it with Modal-3
   */
  if ($('body').hasClass('modal-open')) {
    var conflicting_modal = container.find('.modal');
    if (conflicting_modal.length && (conflicting_modal.data('bs.modal') || {})._isShown) {
      conflicting_modal.off('hidden.bs.modal');
      conflicting_modal.on('hidden.bs.modal', function () {
        conflicting_modal.modal('dispose');
        container.html(html);
        window.tskp.new_modal = container.find('.modal').first();
        window.tskp.new_modal.modal({
          backdrop: 'static',
        });
        if (window.tskp.old_modal.length) {
          window.tskp.new_modal.on('hidden.bs.modal', function () {
            window.tskp.old_modal.modal('show');
            window.tskp.is_nested_modal_open = 0;
          });;
        }
      });
      conflicting_modal.modal('hide');
      return;
    }
  }

  window.tskp.old_modal = (function() {
    if (!$('body').hasClass('modal-open')) {
      return [];
    }

    return $('.modal').filter(function () {
      if ($.contains(container.get(0), this)) {
        return false;
      }
      return ($(this).data('bs.modal') || {})._isShown;
    }).first();
  })();

  container.html(html);
  window.tskp.new_modal = container.find('.modal').first();

  if (window.tskp.old_modal.length) {
    /**
     * Wait for the old modal to hide before opening the new modal
     * If we open the new modal immediately, then we get the issue where the backdrop is scrollable
     */
    window.tskp.old_modal.on('hidden.bs.modal', function () {
      window.tskp.new_modal.modal({
        backdrop: 'static',
      });

      /**
       * Unregister the listener after one time
       * If we remove this line and keep this listener ON, then we get stuck in a loop
       * When old modal closes, then the new modal opens
       * When new modal closes, then the old modal opens
       * And again when old modal closes, then the new modal opens
       * To avoid this, we need to remove this listener after the first time
       */
      window.tskp.old_modal.off('hidden.bs.modal');
    });

    window.tskp.old_modal.modal('hide');
    window.tskp.is_nested_modal_open = 1;
  } else {
    window.tskp.new_modal.modal({
      backdrop: 'static',
    });
  }

  if (window.tskp.old_modal.length) {
    window.tskp.new_modal.on('hidden.bs.modal', function () {
      window.tskp.old_modal.modal('show');
      window.tskp.is_nested_modal_open = 0;
    });
	$('.attachment_modal').on('shown.bs.modal', function () {
		$('.close').trigger('focus');
	});
  }
}

function fileSizeFormatter(bytes, si=false, dp=1) {
  const thresh = si ? 1000 : 1024;

  if (Math.abs(bytes) < thresh) {
    return bytes + ' B';
  }

  const units = si
    ? ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']
    : ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
  let u = -1;
  const r = 10**dp;

  do {
    bytes /= thresh;
    ++u;
  } while (Math.round(Math.abs(bytes) * r) / r >= thresh && u < units.length - 1);


  return bytes.toFixed(dp) + ' ' + units[u];
}


function onlyInt(num)
{
    if (!num.value) return;
    num.value = num.value.replace(/[^0-9]*/g,"");
}

function onlyFloat(num)
{
    if (!num.value) return;
    num.value = num.value.replace(/[^0-9.]*/g,"");
    if (! /^[0-9]+\.?[0-9]*$/.test(num.value)) {
        alert('Please enter only numbers 0-9, optionally with a decimal');
    }
}

// After the survey is completed, wait for 3 seconds before automatically closing the "Thank You" msg modal
$(document).on("click", "#sv-nav-complete input[type='button']", function () {
	setTimeout(function () {$("#survey_content .modal, #showSurveyModal, #surveyPreview, #eventSurveyModal").modal("hide");}, 3000);
});
