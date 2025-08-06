<table id="event_table" class="table table-hover display compact" summary="This table display the list of events of a group">
    <thead>
        <tr>
            <th width="5%" scope="col" class="id-column"><?= gettext("ID"); ?></th> <!-- ID column added -->
            <th width="30%" scope="col"><?= gettext("Event");?></th>
            <th width="20%" scope="col"><?=gettext('Scope')?></th>
            <th width="20%" scope="col"><?= gettext("Start Date");?></th>
            <th width="15%" scope="col"><?= gettext("Creator");?></th>
            <th width="5%" scope="col"><?= gettext("RSVPs");?></th>
            <th width="5%" scope="col" class="id-column"><?= gettext("Approval status");?></th>
            <th width="2%" scope="col"></th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>

<script>
    $(document).ready(function() {
        let approvalsEnabled = <?= json_encode($_COMPANY->getAppCustomization()['event']['approvals']['enabled']) ?>;
        var searchValue = '<?= $_GET['searchText']??'';?>';
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
        var notOrderable = [2,4,5,7];
        var orderBy = 3;
       
        var x = parseInt(localStorage.getItem("local_variable_for_table_pagination"));            
        var dtable = $('#event_table').DataTable( {
            serverSide: true,
            processing: true,
            bFilter: true,
            bInfo : false,
            bDestroy: true,
            pageLength: x,
            order: [[ orderBy, "DESC" ]],
            "drawCallback": function() {
                setAriaLabelForTablePagination(); 
            },
            "initComplete": function(settings, json) {                            
                setAriaLabelForTablePagination(); 
                $('.current').attr("aria-current","true");
                // Set the search value and trigger search
                 this.api().search(searchValue).draw();
            },
            language: {
                    searchPlaceholder: "...",
                    url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
                },
          columnDefs: [
            { targets: [6], visible: (byState === "<?=$_COMPANY->encodeId(2)?>" && approvalsEnabled === true)}, { targets: notOrderable, orderable: false },{ targets: 5, visible: (byState != "<?=$_COMPANY->encodeId(2)?>"), orderable: true } // Conditionally display ID column
        ],
            ajax:{
                url :"ajax_groupHome.php?getEventsList=<?= $encGroupId; ?>&isactive="+byState+"&year="+byYear+'&groupStateType='+groupStateType+'&groupState='+groupState, // json datasource
                data:{upcomingEvents:'<?= $upcomingEvents??false; ?>',pastEvents:'<?= $pastEvents??false; ?>',reconciledEvent:'<?= $reconciledEvent??false; ?>',notReconciledEvent:'<?= $notReconciledEvent??false; ?>',collabEvents:'<?= $collabEvents??false; ?>'},
                type: "POST",  // method  , by default get
                error: function(data){  // error handling
                    $(".table-grid-error").html("");
                    $("#table-members-server").append('<tbody class="table-grid-error"><tr><th colspan="6"><?= gettext("No data found");?>!</th></tr></tbody>');
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
        // function for Accessiblity screen reading.
        screenReadingTableFilterNotification('#event_table',dtable);
    });
</script>

<script>
  $('#event_table').on( 'length.dt', function ( e, settings, len ) {
    localStorage.setItem("local_variable_for_table_pagination", len);
} );
</script>