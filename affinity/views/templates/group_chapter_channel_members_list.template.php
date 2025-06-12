
<style>
	#table-members-server {
		width: 100%;
	}	 
</style>
<div class="table-responsive" id="list-view">
    <table id="table-members-server" class="table table-hover responsive display compact" summary="<?= sprintf(gettext("This table displays the list of members of a %s"),$ergName);?>">
        <thead>
            <tr>
                <th width="25%" class="color-black" scope="col"><?= gettext('Name'); ?></th>
                <th width="25%" class="color-black" scope="col"><?= gettext('Job Title'); ?></th>                
				<th width="25%" class="color-black" scope="col"><?= gettext('Office Location'); ?></th>
                <th width="25%" class="color-black" scope="col"><?= gettext('Since'); ?></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>	
<script>
	$(document).ready(function() {
		var x = parseInt(localStorage.getItem("local_variable_for_table_pagination"));
		var dtable = 	$('#table-members-server').DataTable({
                responsive: true,
				"bAutoWidth": false	,
				serverSide: true,
				bFilter: true,
				bInfo : true,
				bDestroy: true,
				pageLength:x,
				columnDefs: [
                	{ targets: [2], orderable: false }
                ],
				language: {
						searchPlaceholder: "<?= gettext('name or email')?>",
						url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
					},				
				ajax:{
						url :"ajax.php?getGroupChapterChannelMembersList=<?=$encGroupId?>&section=<?=$section;?>&sectionid=<?= $encSectionId ?>", // json datasource
						type: "POST",  // method  , by default get
						error: function(data){  // error handling
							$(".table-grid-error").html("");
							$("#table-members-server").append('<tbody class="table-grid-error"><tr><th colspan="6">No data found!</th></tr></tbody>');
							$("#table-grid_processing").css("display","none");
						},complete : function(){
							setAriaLabelForTablePagination();
						}
					},		
				
			});			
			
			var sortingDisabledElements = document.querySelectorAll('.sorting_disabled');
            sortingDisabledElements.forEach(function (element){
                element.setAttribute('tabindex', '-1');
            }); 
			
			$(".dataTables_filter input")
			.unbind()
			.bind("input", function(e) {
				if(this.value.length >= 3 || e.keyCode == 13) {
					dtable.search(this.value).draw();
				}
				if(this.value == "") {
					dtable.search("").draw();
				}
				return;
			});
			
		});
		
$('#table-members-server').on( 'length.dt', function ( e, settings, len ) {
    localStorage.setItem("local_variable_for_table_pagination", len);
} );
</script>