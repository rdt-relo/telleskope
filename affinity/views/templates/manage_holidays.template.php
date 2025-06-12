<style>
    li > a{
        color:black;
    }
#form_title {
    float: left;
}
</style>
<div id="manageHolidaysModal" class="modal fade" tabindex="-1">
	<div aria-label="<?= gettext("Manage Cultural Observances");?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
		<div class="modal-content">
			<div class="modal-header">
                <div><h2 class="modal-title" id="form_title"><?= gettext("Manage Cultural Observances");?>&nbsp;</h2>
                    <?php if ($isAllowedToCreateContent) { ?>
                        <a role="button" class="new-holiday-btn" href="javascript:void(0);" onclick="newHolidayModal('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId(0); ?>')"><i aria-label="<?= gettext("Add Cultural Observances");?>" class="fa fa-plus-circle"></i></a>
                    <?php } ?>
                    </div>
				<button id="btn_close" aria-label="close" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">               
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive" id="holidaysContainer">
                            <?php
                                include(__DIR__ . "/holidays_table.template.php");
                            ?>
                            
                        </div>
                    </div>
                </div>
			</div>
		</div>  
	</div>
</div>

<script>
$('#manageHolidaysModal').on('shown.bs.modal', function () {
    $('.new-holiday-btn').trigger('focus');
    if( $('#table_holidays tr').length > 0 ){                   
        $('#table_holidays tr').find('th:first').trigger('click');                        
    }
});

$('#viewRecognitionDetialModal').on('hidden.bs.modal', function () {      
    $('.manage-cultural-ob-btn').focus();
});

$(document).on('click','.confirm-dialog-btn-abort', function(){
    setTimeout(function () {
        $('.new-holiday-btn').focus();
    }, 100);
});  
</script>