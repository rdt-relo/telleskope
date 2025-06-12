<div id="theReportModal" class="modal fade">
	<div aria-label="<?= gettext("Recognition Download Options");?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h2 class="modal-title text-center" id="rsvp_report_title"><?= gettext("Recognition Download Options");?></h2>
                <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
            <div class="modal-body">

<div class="col-md-12">
    <div class="col-md-12">
	    <div class=" manage-page-bttn row">
            <form class="form-horizontal" id="reportsForm" action="ajax_reports?download_recognition_report=<?= $enc_groupid; ?>" method="post" role="form" style="display: block;width:100% !important">
                <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
                <div id="rosterOptions" style="padding: 0 50px; padding-top:10px;">
                    
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="s_group"><?=gettext('From Date')?></label>
                                <input aria-label="<?= gettext("From Date"); ?>" type="text" class="form-control" id="start_date" name="startDate" value="" readonly placeholder="<?= gettext('YYYY-MM-DD');?>">
                            </div>
                            <div class="form-group">
                                <label for="s_group"><?=gettext('To Date')?></label>
                                <input aria-label="<?= gettext("To Date"); ?>" type="text" class="form-control" id="end_date"  name="endDate" value="" readonly placeholder="<?= gettext('YYYY-MM-DD');?>">
                            </div>
                        </div>
                        <div class="col-sm-1"></div>
                        <div class="col-sm-5">
                            <fieldset>
                            <legend style="font-size: 1.2rem;"><?= gettext("Select Fields");?></legend>                             
                                <div class="mb-2 text-sm">
                                    <a role="button" href="javascript:void(0)" class="link_show" onclick="checkUncheckAllCheckBoxes('userOptionsMultiCheck',true)"> <?= gettext("Select All");?></a> | <a role="button" href="javascript:void(0)" class="link_show" onclick="checkUncheckAllCheckBoxes('userOptionsMultiCheck',false)"> <?= gettext("Deselect All");?></a>
                                </div>
                                <?php foreach($fields as $key => $value){ ?>
                                    <input aria-label="<?= $value; ?>" class="userOptionsMultiCheck" type="checkbox" name="<?= $key; ?>" value="<?= $key; ?>" checked>&emsp;<?= $value; ?><br>
                                <?php } ?>
                                <input class="userOptionsMultiCheck" type="checkbox" name="includeCustomFields" value="1" checked>&emsp;<?= gettext("Include Custom Fields"); ?>
                            </fieldset>
                        </div>

                    </div>
                </div>

                <div class="form-group mt-2">
                    <div class="text-center">
                        <button type="submit" name="submit" class="btn btn-primary"><?= gettext("Download");?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
        </div>
	</div>
</div>

<script>
    $(function() {
        $( "#start_date" ).datepicker({
            prevText:"click for previous months",
            nextText:"click for next months",
            showOtherMonths:true,
            selectOtherMonths: false,
            dateFormat: 'yy-mm-dd',
            onSelect: function (date) {
                var date2 = $('#start_date').datepicker('getDate');
                $('#end_date').datepicker('option', 'minDate', date2);
            },
            beforeShow:function(textbox, instance){
                $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
            }
        });
        $( "#end_date" ).datepicker({
            prevText:"click for previous months",
            nextText:"click for next months",
            showOtherMonths:true,
            selectOtherMonths: true,
            dateFormat: 'yy-mm-dd',
            beforeShow:function(textbox, instance){
                $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
            } 
        });
    });

    $('#theReportModal').on('shown.bs.modal', function () {
        $('#btn_close').trigger('focus')
    });

$(function() {
    $('#reportsForm').submit(function(){         
        $('#btn_close').focus();     
    });
});
</script>

