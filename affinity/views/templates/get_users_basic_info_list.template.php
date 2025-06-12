<div id="users_basic_list" class="modal">
    <div aria-label="<?= $modalTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title" class="modal-title"><?= $modalTitle; ?></h2>
                <button type="button" id="btn_close" class="close" aria-label="close" data-dismiss="modal">&times;
                </button>
            </div>
            <div class="modal-body">
                <div class="col-md-12">
                    <div class="table-responsive " id="list-view">
                        <div id="filterInfo" style="position:absolute; z-index:-9999;" role="status" aria-live="polite"></div>
                        <table id="table-event-likes" class="table table-hover display compact" width="100%" aria-label="This table displays the list of users">
                            <thead>
                            <tr>
                                <th width="25%" scope="col"><?= gettext("First Name");?></th>
                                <th width="25%" scope="col"><?= gettext("Last Name");?></th>
                                <th width="25%" scope="col"><?= gettext("Title");?></th>
                                <th width="25%" scope="col"><?= gettext('Like Reaction') ?></th>
                            </tr>
                            </thead>

                            <tbody>

                            <?php
                            $i = 0;
                            foreach ($usersList as $u) {
                                $encMemberUserID = $_COMPANY->encodeId($u['userid']);
                            ?>
                                <tr tabindex="0" id="<?= $i++; ?>" style="cursor: pointer;"
                                    onclick="getProfileDetailedView(this,{'userid':'<?= $encMemberUserID; ?>'})"
                                    onkeypress="getProfileDetailedView(this,{'userid':'<?= $encMemberUserID; ?>'})">
                                    <td><?= $u['firstname']; ?></td>
                                    <td><?= $u['lastname']; ?></td>
                                    <td><?= $u['jobtitle']??'-'; ?></td>
                                    <td><?= ucfirst($u['reactiontype']) ?></td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <script>
                    $(document).ready(function () {
                        var x = parseInt(localStorage.getItem("local_variable_for_table_pagination"));
                        var dTable = $('#table-event-likes').DataTable({
                            pageLength:x,
                            "order": [],
                            "bPaginate": true,
                            "bInfo": false,
                            "drawCallback": function() {
                                setAriaLabelForTablePagination(); 
                            },
                            'language': {
                                url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json',
                                aria: {
                                    paginate: {
                                        first: 'First Page',
                                        previous: 'Previous Page',
                                        next: 'Next Page',
                                        last: 'Last Page',
                                        number: 'Page: ',
                                    },
                                },	
                            },	
                            "initComplete": function(settings, json) {                            
                               setAriaLabelForTablePagination(); 
                               $('.current').attr("aria-current","true");  
                            }
                        });
                                           
                        dTable.on( 'search.dt', function () {
                            var dCount = dTable.rows( {search:'applied'} ).count();
                            var resultVerb = ' result is';
                            if (dCount>1){
                                resultVerb = ' results are'
                            }
                            $('#filterInfo').html(dCount+ resultVerb+' available');
                        } );

                        dTable.on( 'draw.dt', function () {
                            var paginationLinks = $("#table-event-likes_paginate > span > a");
                                paginationLinks.each(function() {
                                    var pNo = $(this).html();
								    $( this ).attr("aria-label","Page "+pNo) ;
                                    $('.current').attr("aria-current","true");
                                });
                        } );
                    });
                </script>
            </div>
            <div class="modal-footer text-center">
                <button type="button" class="btn btn-affinity" data-dismiss="modal"><?= gettext("Close");?></button>
            </div>
        </div>
    </div>
</div>

<script>    
$('#users_basic_list').on('shown.bs.modal', function () {
   $('.close').trigger('focus');
});

$('#users_basic_list').on('hidden.bs.modal', function (e) { 
    $('.modal').removeClass('js-skip-esc-key');
    if ($('.modal').is(':visible')){
        $('body').addClass('modal-open');
    }         
    setTimeout(() => {
        $('#showAllLikers').focus();  
    }, 20); 
}) 

$('#table-event-likes').on( 'length.dt', function ( e, settings, len ) {
    localStorage.setItem("local_variable_for_table_pagination", len);
} );

trapFocusInModal("#users_basic_list");
</script>