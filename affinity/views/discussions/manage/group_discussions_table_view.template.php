<table id="discussion_table" class="display table table-hover display compact" summary="This table display the list of announcements of a group">
    <thead>
        <tr>
            <th width="35%" class="color-black" scope="col"><?= gettext("Title");?></th>
            <?php if($groupid){ ?>
                <th width="20%" scope="col"><?=gettext('Scope')?></th>
              <?php } ?>
            <th width="15%" class="color-black" scope="col"><?= gettext("Date");?></th>
            <th width="18%" class="color-black" scope="col"><?= gettext("Creator");?></th>
            <th width="2%" class="color-black" scope="col"></th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
<script>
    $(document).ready(function() {
        var byState = $("#filterByState").val();
	    var byYear = $("#filterByYear").val();
        var groupStateType = $('#filterByGroup').find(':selected').data('section');
        var groupState = $('#filterByGroup').val();
        
        if (typeof byState === 'undefined' || byState === null){
            byState = '';
        } else {
            localStorage.setItem("state_filter", byState);
        }
        if (typeof groupState === 'undefined'  || groupState === null ){
            groupState ='';
        } else {
            localStorage.setItem("erg_filter", groupState);
        }
        if (typeof groupStateType === 'undefined' || groupStateType === null){
            groupStateType ='';
        } else {
            localStorage.setItem("erg_filter_section", groupStateType);
        }
        if (typeof byYear === 'undefined'  || byYear === null){
            byYear = '';
        } else {
            localStorage.setItem("year_filter", byYear);
        }
        var notOrderable = [3,4];
        var orderBy = 1;
        <?php if($groupid){ ?>
            notOrderable = [1,4];
            orderBy = 3;
        <?php } ?>
        var x = parseInt(localStorage.getItem("local_variable_for_table_pagination"));
        var dtable = 	$('#discussion_table').DataTable( {
            serverSide: true,
            processing: true,
            bFilter: true,
            bInfo : false,
            bDestroy: true,
            pageLength:x,
            order: [[ orderBy, "DESC" ]],
            language: {
                    searchPlaceholder: "...",
                    url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
                },
                columnDefs: [
                { targets: notOrderable, orderable: false }
                ],
            ajax:{
                url :"ajax_discussions.php?getDiscussionsList=<?= $enc_groupid; ?>&isactive="+byState+"&year="+byYear+'&groupStateType='+groupStateType+'&groupState='+groupState, // json datasource
                type: "POST",  // method  , by default get
                error: function(data){  // error handling
                    $(".table-grid-error").html("");
                    $("#table-members-server").append('<tbody class="table-grid-error"><tr><th colspan="6">No data found!</th></tr></tbody>');
                    $("#table-grid_processing").css("display","none");
                },complete : function(){
                    $('.initial').initial({
                        charCount: 2,
                        textColor: '#ffffff',
                        color: window.tskp?.initial_bgcolor ?? null,
                        seed: 0,
                        height: 30,
                        width: 30,
                        fontSize: 15,
                        fontWeight: 300,
                        fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
                        radius: 0
                    });
                    $(".confirm").popConfirm({content: ''});
                }
            },
        } );

        screenReadingTableFilterNotification('#discussion_table',dtable);

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

  $('#discussion_table').on( 'length.dt', function ( e, settings, len ) {
    localStorage.setItem("local_variable_for_table_pagination", len);
} );
</script>