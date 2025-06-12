<style>
    li > a {
        color: black;
    }

    .btn-link.focus, .btn-link:focus {
        outline: 0;
        box-shadow: 0 0 0 .2rem rgba(0, 123, 255, .25);
    }

    .section_title {
        float: left;
    }

    .volunteer-header {
        width: 100%;
        display: -ms-flexbox;
        display: flex;
        -ms-flex-align: start;
        align-items: flex-start;
        -ms-flex-pack: justify;
        justify-content: space-between;
        padding: .5rem;
        border-bottom: 1px solid #dee2e6;
        border-top-left-radius: calc(0.3rem - 1px);
        border-top-right-radius: calc(0.3rem - 1px);
    }
    .volunteer-header h3{
        font-size: 1.25rem;
    }
</style>
<div id="manageVolunteersModal" class="modal fade" tabindex="-1">
    <div aria-label="<?= gettext("Manage Event Volunteers"); ?>" class="modal-dialog modal-dialog-w1000"
         aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="col-md-10">
                    <h2><?= gettext("Manage Event Volunteers"); ?>&nbsp;</h2>
                </div>
                <div class="col-md-2 text-right">
                    <?php
                    $page_tags = 'manage_event_volunteer';
                    ViewHelper::ShowTrainingVideoButton($page_tags);
                    ?>
                </div>
            </div>

            <div class="modal-body">
                <!-- Manage Volunteer Requests Section -->
                <div class="row">
                    <div class="volunteer-header">
                        <div class="col-md-12">
                            <h3 class="modal-title" id="form_title"><?= gettext("Manage Volunteer Roles"); ?></h3>
                            <a role="button" id="addEventVolunteer" href="javascript:void(0);"  tabindex="0" onclick="addUpdateEventVolunteerRequestModal('<?= $_COMPANY->encodeId($eventid)?>','<?= $_COMPANY->encodeId(0); ?>')" <?= $event->hasEnded() ? 'disabled' : ''?>><i class="fa fa-lg fa-plus-circle" title="<?= gettext('Request Event Volunteers'); ?>"></i></a>
                        </div>
                    </div>
                    <div class="col-md-12 mt-3">
                        <div class="table-responsive">
                            <table id="table_event_volunteer_request" class="table table-hover display compact" style="width:100%"
                                   summary="This table display the list of event volunteers">
                                <thead>
                                <tr>
                                    <th width="30%" class="color-black"
                                        scope="col"><?= gettext("Requested Volunteer Type"); ?></th>
                                        <th width="20%" class="color-black"
                                        scope="col"><?= gettext("Volunteer Hours"); ?></th>
                                    <th width="20%" class="color-black"
                                        scope="col"><?= gettext("Number of Volunteers Needed"); ?></th>
                                    <th width="10%" class="color-black"
                                        scope="col"><?= gettext("Hide"); ?></th>
                                    <?php if ($_COMPANY->getAppCustomization()['event']['volunteers'] && $_COMPANY->getAppCustomization()['event']['external_volunteers']) { ?>
                                    <th width="10%" class="color-black" scope="col"><?= gettext('Allow External Volunteers') ?></th>
                                    <?php } ?>
                                    <th width="10%" class="color-black" scope="col"><?= gettext("Action"); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($eventVolunteerRequests as $key => $volunteer) { ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($event->getVolunteerTypeValue($volunteer['volunteertypeid'])) ?>
                                        </td>
                                        <td>
                                        <?= $volunteer['volunteer_hours'] ?? '0'; ?>
                                        </td>
                                        <td>
                                            <?= $volunteer['volunteer_needed_count']; ?>
                                        </td>
                                        <td>
                                            <?= isset($volunteer['hide_from_signup_page']) && $volunteer['hide_from_signup_page'] == 1 ? 'TRUE' : 'FALSE'; ?>
                                        </td>
                                        <?php if ($_COMPANY->getAppCustomization()['event']['volunteers'] && $_COMPANY->getAppCustomization()['event']['external_volunteers']) { ?>
                                        <td>
                                          <?= ($volunteer['allow_external_volunteers'] ?? false ) ? 'Yes' : 'No' ?>
                                        </td>
                                        <?php } ?>
                                        <td>
                                            <div class="">
                                                <button class="btn btn-sm btn-affinity" data-toggle="dropdown"><?= gettext("Action"); ?></button>
                                                <ul class="dropdown-menu" style="width:200px;">
                                                    <li>
                                                        <a href="javascript:void(0);" tabindex="0" class=""
                                                           onclick="addUpdateEventVolunteerRequestModal('<?= $_COMPANY->encodeId($eventid) ?>','<?= $_COMPANY->encodeId($volunteer['volunteertypeid']); ?>')"><i
                                                                    class="fa fas fa-edit"
                                                                    aria-hidden="true"></i>&emsp;<?= gettext("Update"); ?>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" tabindex="0"
                                                           class="confirm pop-identifier"
                                                           data-confirm-noBtn="<?= gettext('No') ?>"
                                                           data-confirm-yesBtn="<?= gettext('Yes') ?>"
                                                           title="<?= gettext("Are you sure you want to delete?"); ?>"
                                                           onclick="deleteEventVolunteerRequest('<?= $_COMPANY->encodeId($eventid) ?>','<?= $_COMPANY->encodeId($volunteer['volunteertypeid']); ?>')"><i
                                                                    class="fa fa-trash"
                                                                    aria-hidden="true"></i>&emsp;<?= gettext("Delete"); ?>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="my-2">&nbsp</div>
                <!-- Manage Volunteers Section -->
                <div class="row">

                    <div class="volunteer-header">
                        <div class="col-md-12">
                            <h3 class="modal-title" id="form_title"><?= gettext("Manage Event Volunteers");?></h3>
                            &nbsp;
                            <a role="button" href="javascript:void(0);"  tabindex="0" onclick="addUpdateEventVolunteerModal('<?= $_COMPANY->encodeId($eventid)?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId(0); ?>')" <?= $event->hasEnded() ? 'disabled' : ''?>><i class="fa fa-lg fa-plus-circle" title="<?= gettext('Add event volunteers'); ?>"></i></a>
                            <?php if(!empty($eventVolunteers)){ ?>
                            &nbsp;
                            <a href="javascript:void(0)" onclick="sendEmailToVolunteersModal('<?= $_COMPANY->encodeId($eventid)?>');" tabindex="0"><i class="fa fa-lg fa-envelope" title="<?= gettext('Send email to volunteers'); ?>"></i></a>
                            <?php } ?>
                        </div>

                    </div>

                    <div class="col-md-12 mt-3">
                        <div class="table-responsive">
                            <table id="table_event_volunteer" class="table table-hover display compact" style="width:100%"
                                   summary="This table display the list of event volunteers">
                                <thead>
                                <tr>
                                    <th class="color-black" scope="col"></th>
                                    <th class="color-black" scope="col"><?= gettext("Volunteer"); ?></th>
                                    <th class="color-black" scope="col"><?= gettext("Volunteer Type"); ?></th>
                                    <th class="color-black" scope="col"><?= gettext("Action"); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($eventVolunteers as $volunteer) { ?>
                                    <?php $volunteer_obj = EventVolunteer::Hydrate($volunteer['volunteerid'], $volunteer); ?>
                                    <tr>
                                        <td>
                                          <?php if ($volunteer_obj->isExternalVolunteer()) { ?>
                                            <?=
                                              sprintf(
                                                gettext('External Volunteer added by %s %s (%s)'),
                                                $volunteer_obj->getCareofUser()->val('firstname'),
                                                $volunteer_obj->getCareofUser()->val('lastname'),
                                                $volunteer_obj->getCareofUser()->val('email')
                                              )
                                            ?>
                                            <?php } else { ?>
                                              <?= User::BuildProfilePictureImgTag($volunteer['firstname'], $volunteer['lastname'], $volunteer['picture'], 'memberpic2', 'Volunteer profile p
                                              icture', $volunteer['userid'], 'profile_basic'); ?>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?= $volunteer_obj->getFirstName() . " " . $volunteer_obj->getLastName(); ?><br>
                                            <?= $volunteer['jobtitle'] ?: '-' ?><br>
                                            <?= $volunteer_obj->getVolunteerEmail() ?>
                                        <td>
                                            <?= $volunteer['type']; ?>
                                        </td>
                                        <td>
                                            <div class="">
                                                <button
                                                    <?php if ($event->hasEnded()) { ?>
                                                        class="btn btn-sm btn-affinity-gray"
                                                        data-toggle="popover"
                                                        data-trigger="hover"
                                                        data-html="true"
                                                        data-content="<?= gettext("This event has ended. Action disabled!"); ?>"
                                                        title=""
                                                    <?php } else { ?>
                                                        class="btn btn-sm btn-affinity"
                                                        data-toggle="dropdown"
                                                        tabindex="0"
                                                    <?php } ?>
                                                ><?= gettext("Action"); ?>
                                                </button>
                                                <ul class="dropdown-menu" style="width:200px;">
                                                    <?php if ($volunteer['approval_status'] == 1) { ?>
                                                        <li>
                                                            <a href="javascript:void(0);" tabindex="0" class="confirm"
                                                               data-confirm-noBtn="<?= gettext('No') ?>"
                                                               data-confirm-yesBtn="<?= gettext('Yes') ?>"
                                                               title="<?= gettext("Are you sure you want to approve?"); ?>"
                                                               onclick="approveEventVolunteer('<?= $_COMPANY->encodeId($eventid) ?>','<?= $_COMPANY->encodeId($volunteer['volunteerid']); ?>')"><i
                                                                        class="fa fa-check" aria-hidden="true"></i>&emsp;<?= gettext("Approve"); ?>
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                    <li>
                                                        <a
                                                          href="javascript:void(0);"
                                                          tabindex="0"
                                                          <?php if (!$volunteer_obj->isExternalVolunteer()) { ?>
                                                            onclick="addUpdateEventVolunteerModal('<?= $_COMPANY->encodeId($eventid) ?>','<?= $_COMPANY->encodeId($volunteer['userid']); ?>','<?= $_COMPANY->encodeId($volunteer['volunteertypeid']); ?>')"
                                                          <?php } else { ?>
                                                            onclick="window.tskp.event_volunteer.openAddOrEditExternalVolunteerByLeaderModal(event)"
                                                            data-eventid="<?= $_COMPANY->encodeId($eventid) ?>"
                                                            data-volunteerid="<?= $_COMPANY->encodeId($volunteer_obj->id()) ?>"
                                                          <?php } ?>
                                                        >
                                                          <i class="fa fas fa-edit" aria-hidden="true"></i>&emsp;<?= gettext("Update"); ?>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" tabindex="0"
                                                           class="confirm popover-identifier"
                                                           data-confirm-noBtn="<?= gettext('No') ?>"
                                                           data-confirm-yesBtn="<?= gettext('Yes') ?>"
                                                           title="<?= gettext("Are you sure you want to delete?"); ?>"
                                                           onclick="deleteEventVolunteer('<?= $_COMPANY->encodeId($eventid) ?>','<?= $_COMPANY->encodeId($volunteer['userid']); ?>','<?= $_COMPANY->encodeId($volunteer['volunteerid']) ?>')"><i
                                                                    class="fa fa-trash"
                                                                    aria-hidden="true"></i>&emsp;<?= gettext("Delete"); ?>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btn_close2" type="button" class="btn btn-secondary" data-dismiss="modal"
                        tabindex="0"
                    <?php if ($refreshPage) { ?>
                        onclick="location.reload();"
                    <?php } ?>
                >Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#table_event_volunteer').DataTable({
            "autoWidth": false,
            "order": [[1, 'desc']],
            "bPaginate": true,
            "bInfo": false,
            "pageLength": 10,
            'language': {
                url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },
            drawCallback: function (settings) {
                var pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
                pagination.toggle(this.api().page.info().pages > 1);
            },

            columns: [
                { width: "40%", orderable: false },
                { width: "20%", orderable: true },
                { width: "30%", orderable: true },
                { width: "10%", orderable: false },
            ]

        });

        $(function () {
            $('[data-toggle="popover"]').popover({html: true, placement: "top"});
        })


        $('#table_event_volunteer_request').DataTable({
            "autoWidth": false,
            "order": [[0, 'desc']],
            "bPaginate": true,
            "bInfo": false,
            "pageLength": 10,
            'language': {
                url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },
            drawCallback: function (settings) {
                var pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
                pagination.toggle(this.api().page.info().pages > 1);
            },
            columns: [
                { width: "30%", orderable: true },
                { width: "20%", orderable: true },
                { width: "20%", orderable: true },
                { width: "10%", orderable: true },
                <?php if ($_COMPANY->getAppCustomization()['event']['volunteers'] && $_COMPANY->getAppCustomization()['event']['external_volunteers']) { ?>
                { width: "10%", orderable: true },
                <?php } ?>
                { width: "10%", orderable: false },
            ]

        });


    });

    function sendEmailToVolunteersModal(e) {
        closeAllActiveModal();
        $.ajax({
            url: 'ajax_events.php?sendEmailToVolunteersModal=1',
            type: "GET",
            data: {'eventid': e},
            success: function (data) {
                $('#loadAnyModal').html(data);
                $('#volunteer_email_form_modal').modal({
                    backdrop: 'static',
                    keyboard: false
                });
            }
        });
    }

    $('#manageVolunteersModal').on('shown.bs.modal', function () {
        $('#addEventVolunteer').trigger('focus');
        
        if( $('#table_event_volunteer_request tr').length > 0 ){                   
            $('#table_event_volunteer_request tr').find('th:first').trigger('click');                        
        }
        if( $('#table_event_volunteer tr').length > 0 ){                   
            $('#table_event_volunteer tr').find('th').eq(1).trigger('click');           
        }
    });

    $('.pop-identifier').each(function () {
        $(this).popConfirm({
            container: $("#manageVolunteersModal"),
        });
    });
    $('popover-identifier').each(function () {
        $(this).popConfirm({
            container: $("#manageVolunteersModal"),
        });
    });
</script>