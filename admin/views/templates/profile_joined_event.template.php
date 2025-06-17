<div id="filterInfo" style="display:none;" role="status" aria-live="polite"></div>
<table id="profile_joined_events_table" class="table table-hover display compact" summary="This table display the list of events all joined group">
    <thead>
        <tr>
        <th width="30%" scope="col"><?= gettext('Event Name'); ?></th>
        <th width="40%" scope="col"><?=$_COMPANY->getAppCustomization()['group']['name-short'] . ($_COMPANY->getAppCustomization()['chapter']['enabled'] ? '/'.$_COMPANY->getAppCustomization()['chapter']['name-short-plural'] : '') . ($_COMPANY->getAppCustomization()['channel']['enabled'] ? '/'.$_COMPANY->getAppCustomization()['channel']['name-short-plural'] : '')?></th>
        <th width="15%" scope="col"><?= gettext('Attended on'); ?></th>
        <th width="15%" scope="col"><?= gettext('Where'); ?></th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
<script src="../vendor/js/datatables-2.1.8/datatables.min.js"></script>
<script>
    $(document).ready(function() {
        var userId = "<?= $_COMPANY->encodeId($searched_userid); ?>";
        var dtable = 	$('#profile_joined_events_table').DataTable( {
            serverSide: true,
            processing: true,
            bFilter: true,
            bInfo : false,
            bDestroy: true,
            order: [[ 2, "DESC" ]],
            language: {
            searchPlaceholder: "...",
            url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val("language")); ?>.json'                
        },
            ajax:{
                url :"ajax.php?getJoinedEventsActivityList=1&section=<?=$section ?? 'zone'?>",
                type: "POST",  // method  , by default get
                data: {'userid':userId},
                error: function(data){  // error handling
                    $(".table-grid-error").html("");
                    $("#table-members-server").append('<tbody class="table-grid-error"><tr><th colspan="6"><?= gettext("No data found");?>!</th></tr></tbody>');
                    $("#table-grid_processing").css("display","none");
                },complete : function(){
                    $('.initial').initial({
                        charCount: 2,
                        textColor: '#ffffff',
                        seed: 0,
                        height: 30,
                        width: 30,
                        fontSize: 15,
                        fontWeight: 300,
                        fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
                        radius: 0
                    });
                    $(".confirm").popConfirm({content: ''});
                    var paginationLinks = $("#profile_joined_events_table_paginate > span > a");
                    paginationLinks.each(function() {
                        var pNo = $(this).html();
						$( this ).attr("aria-label","Page "+pNo) ;
                        $('.current').attr("aria-current","true");
                    });
                }
            },
        } );
        dtable.on( 'search.dt', function () {
            var dCount = dtable.rows( {search:'applied'} ).count();
            var resultVerb = ' result is';
            if (dCount>1){
                resultVerb = ' results are'
            }
            $('#filterInfo').html(dCount+ resultVerb+' available');
        } );

        $(".dataTables_filter input")
        .unbind()
        .bind("input", function(e) {
            if(this.value.length >= 2 || e.keyCode == 13) {
                dtable.search(this.value).draw();
            }
            if(this.value == "") {
                dtable.search("").draw();
            }
            return;
        });
    });
</script>
