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

    .org-header {
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
    #add_new_org{
        font-weight: bold;
    }
</style>
<div id="manageOrganizationModal" class="modal fade" tabindex="-1">
    <div aria-label="<?= gettext("Manage Event Organizations"); ?>" class="modal-dialog modal-dialog-w1000"
         aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                    <h2><?= gettext("Manage Event Organizations"); ?>&nbsp;</h2>    
            </div>

            <div class="modal-body">
                <!-- Manage organizations Section -->
                <div class="row">

                    <?php if ($isActionDisabledDuringApprovalProcess) { ?>
                    <div class="col-md-12">
                        <div class="alert-warning p-3 text-small">
                            <?=sprintf(gettext('This event is currently in the approval process or has been approved. %1$s changes are not permitted. To make changes, request the event approver to deny the approval.'), gettext('Partner Organization'))?>
                        </div>
                    </div>
                    <?php } ?>

                    <div class="organization-header">
                        <div class="col-md-12">
                            <h5 class="modal-title" id="form_title"><?= gettext("Organizations Added to this Event");?></h5>
                        <?php if (!$isActionDisabledDuringApprovalProcess) { ?>
                            &nbsp;
                            <a href="javascript:void(0);"  tabindex="0" onclick="addUpdateEventOrgModal('<?= $_COMPANY->encodeId($eventid)?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId(0); ?>')" <?= $event->hasEnded() ? 'disabled' : ''?>><i class="fa fa-lg fa-plus-circle" title="<?= gettext('Add event organizations'); ?>"></i></a>
                            <?php if(!empty($eventorganizations)){ ?>
                            &nbsp;
                            <a href="javascript:void(0)" onclick="sendEmailToorganizationsModal('<?= $_COMPANY->encodeId($eventid)?>');" tabindex="0"><i class="fa fa-lg fa-envelope" title="<?= gettext('Send email to organizations'); ?>"></i></a>
                            <?php } ?>
                        <?php } ?>
                        </div>

                    </div>
                    <div class="col-md-12" id="selectOrgDiv" style="display: none;">
                        <div class="form-group">
                            <label for="email"><?=gettext('Add an organization')?>: <span style="color: #ff0000;"> *</span></label>
                            <select id="selected_org" name="selected_org" onchange="openAddOrUpdateOrgModal('<?=$_COMPANY->encodeId($event->id())?>',this.value, 1)" class="form-control">
                                <option value=""><?=gettext('Choose one of the existing organization or add a new organization')?></option>
                                <option style="color: #0077b5; " id="add_new_org" value="<?=$_COMPANY->encodeId(0)?>">
                                    <strong><?=gettext('Add New Organization')?></strong>
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12 mt-3" id="org_card">
                        <div class="table-responsive">
                            <table id="table_event_organization" class="table table-hover display compact" style="width:100%"
                                   summary="This table display the list of event organizations">
                                <thead>
                                <tr>
                                    <th class="color-black" scope="col"><?= gettext("Organization Name"); ?></th>
                                    <th class="color-black" scope="col"><?= gettext("Organization Tax ID"); ?></th>
                                    <th class="color-black" scope="col"><?= gettext("Action"); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php 
                                if(count($eventOrganizations) > 0){
                                    foreach ($eventOrganizations as $organization) {
                                        $organizationObject = Organization::Hydrate($organization['organization_id'], $organization);

                                        $label = '';
                                        if ($organizationObject->isNotApproved()) {
                                            $label = '<span class="label px-2 mx-2" style="background-color: darkred; color: white; border-radius: 3px;">' . gettext("Not Approved") . '</span>';
                                        } elseif ($organizationObject->isApproved()) {
                                            //$label = '<span class="label px-2 mx-2" style="background-color: darkgreen; color: white; border-radius: 3px;">' . gettext("Approved") . '</span>';
                                        }
                                ?>
                                    <tr>
                                        <td>
                                        <?= $organization['organization_name'] . ' ' . $label; ?>
                                        </td>
                                        <td>
                                        <?= $organization['organization_taxid']; ?>
                                        </td>
                                        <td>
                                        <?php if (!$isActionDisabledDuringApprovalProcess) { ?>
                                            <button class="btn-no-style mr-3" style="font-size:14px;" onclick="openAddOrUpdateOrgModal('<?=$_COMPANY->encodeId($event->id())?>','<?= $_COMPANY->encodeId($organization['organization_id'])?>', 0)"><i class="fa fas fa-edit"></i></button>
                                        <?php } ?>

                                        <?php if (!$isDeleteOrganizationDisabled) { ?>
                                            <button aria-label="Delete" style="font-size:14px;" class="btn-no-style confirm" onclick="deleteOrgFromEvent('<?= $_COMPANY->encodeId($organization['organization_id'])?>', '<?= $_COMPANY->encodeId($eventid)?>')" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to delete this organization?");?>" title="<strong>Are you sure you want to delete!</strong>"> <i class="fa fa-trash" title="Delete" aria-hidden="true"></i></button>
                                        <?php } ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btn_close2" type="button" class="btn btn-secondary" data-dismiss="modal"
                        tabindex="0">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#table_event_organization').DataTable({
            "autoWidth": false,
            "order": [],
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
                { width: "40%", orderable: true, className: 'dt-left' },
                { width: "35%", orderable: true, className: 'dt-left', type: 'string' },
                { width: "15%", orderable: false, className: 'dt-left' },
            ]

        });

        $(function () {
            $('[data-toggle="popover"]').popover({html: true, placement: "top"});
        })
    });

    $('#manageorganizationsModal').on('shown.bs.modal', function () {
        $('#form_title').trigger('focus')
    });

    $('.pop-identifier').each(function () {
        $(this).popConfirm({
            container: $("#manageorganizationsModal"),
        });
    });
    $('popover-identifier').each(function () {
        $(this).popConfirm({
            container: $("#manageorganizationsModal"),
        });
    }); 
</script>
<script>
$(document).ready(function() {
    function customMatcher(params, data){
        if($.trim(params.term) === ''){
            return data;
        }
        if(data.id == '<?= $_COMPANY->encodeId(0) ?>'){
            return data;
        }
        if(data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1){
            return data;
        }
        return null;
    }
    $('#selected_org').select2({
        placeholder: "<?= gettext('Choose one of the existing organizations or add a new organization'); ?>",
        language: {
            noResults: function(){
                return $('<span>No results found</span>');
            }
        },
        matcher: customMatcher,
        ajax: {
            url: 'ajax_events.php?showOrgDropdown=1',
            dataType: 'json',
            delay: 10,
            data: function(params) {
                return {
                    searchTerm: params.term, // Search term sent to server
                    eventid: '<?= $_COMPANY->encodeId($event->id()); ?>' // Get the event id from an input or another element
                };
            },
            processResults: function(data) {
                if (!data || data.length === 0) {
                    return {
                        results: [{ id: '', text: 'No data found for the given identifier' }]
                    };
                }
                return {
                    results: $.map(data, function(item) {
                        return {
                            id: item.v, // ID for each option
                            text: item.t // Text to display for each option
                        };
                    })
                };
            },
            cache: true,
        error:function(jqXHR, textStatus, errorThrown) {
        if (textStatus === 'abort') {
            jqXHR.skip_default_error_handler = true;
        } else {
            console.error('Error fetching data:', textStatus, errorThrown);
        }
    }
    }
});
    $('[data-toggle="popover"]').popover({
        sanitize:false
    });
});
</script>