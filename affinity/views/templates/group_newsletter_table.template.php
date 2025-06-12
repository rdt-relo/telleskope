<style>
    .hide-span-date{display:none;}
    </style>
<table id="table-event" class="table table-hover display compact" summary="This table displays the list of group newsletters">
    <thead >
        <tr> 
            <th width="30%" scope="col"><?= gettext("Newsletter Name");?></th>
            <th width="25%" scope="col"><?=gettext('Scope')?></th>
            <th width="20%" scope="col"><?= gettext("Created By");?></th>
            <th width="20%" scope="col"><?= gettext("Status");?></th>
            <th width="20%" scope="col" class="id-column"><?= gettext("Approval Status");?></th>
            <th width="2%" scope="col"></th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>


<script>
    $(document).ready(function() {
        let approvalsEnabled = <?= json_encode($_COMPANY->getAppCustomization()['newsletters']['approvals']['enabled']) ?>;
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
        var notOrderable = [1,5];
        var x = parseInt(localStorage.getItem("local_variable_for_table_pagination"));
        var dtable = 	$('#table-event').DataTable( {
            serverSide: true,
            processing: true,
            bFilter: true,
            bInfo : false,
            bDestroy: true,
            pageLength:x,
            order: [[ 3, "DESC" ]],
            "drawCallback": function() {
                setAriaLabelForTablePagination(); 
            },
            "initComplete": function(settings, json) {                            
                setAriaLabelForTablePagination(); 
                $('.current').attr("aria-current","true");  
            },
            language: {
                    searchPlaceholder: "...",
                    url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
                },	
            columnDefs: [
                { targets: [4], visible: (byState === "<?=$_COMPANY->encodeId(2)?>" && approvalsEnabled === true)},{ targets: notOrderable, orderable: false }
                ],
            ajax:{
                url :"ajax_groupHome.php?getNewslettersList=<?= $encGroupId; ?>&isactive="+byState+"&year="+byYear+'&groupStateType='+groupStateType+'&groupState='+groupState, // json datasource
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
        screenReadingTableFilterNotification('#table-event',dtable);       
    });
  </script>

<script>
 $('#table-event').on( 'length.dt', function ( e, settings, len ) {
    localStorage.setItem("local_variable_for_table_pagination", len);
} );
</script>