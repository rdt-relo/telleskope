<div id="holidayModal" class="modal fade">
	<div aria-label="<?= $modalTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
		<div class="modal-content">
			<div class="modal-header">
                <h2 class="modal-title" id="form_title"><?= $modalTitle; ?></h2>
				<button aria-label="close" id="btn_close" type="button" onclick="closeHolidayModal()" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">               
                <form class="form-horizontal" method="post" action="" id="holidayModalForm">
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <div class="form-group">
                    <p class="col-sm-12 control-label"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                    </div>
                    <div class="form-group">
                        <label for="eventtitle" class="col-sm-12 control-label"><?= gettext("Cultural Observance Title");?><span style="color:red"> *</span></label>
                        <div class="col-sm-12">
                            <input type="text" class="form-control" placeholder="<?= gettext('Cultural Observance title');?>" id="eventtitle" name="eventtitle" value="<?= $event ? $event->val('eventtitle') : '' ?>" aria-required="true">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="start_date" class="col-sm-12 control-label"><?= gettext("Cultural Observance Date");?><span style="color:red"> *</span></label>
						<div class="col-sm-12">
							<input type="text" onchange="validateHolidayDate()" name="eventdate" id="start_date" value="<?= $event ? date('Y-m-d',strtotime($event->val('start'))) : '' ?>" class="form-control" aria-required="true" placeholder="YYYY-MM-DD" />
						</div>
                        <div class="col-sm-12 mt-3">
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input multiday" name="multiDayHoliday" id="multiDayHoliday" onchange="showHolidayEndDateInput(this)" <?= $event ? ( ($event->getDurationInSeconds() > 86400) ? 'checked' : '') : ''; ?> ><?= gettext('Multi-Day Cultural Observance');?>
                                </label>
                            </div>
                        </div>
					</div>
                    <?php 
                    if($event){
                        $startDate = $event->val('start');
                        $newStartDate = date("Y-m-d H:i:s", strtotime("$startDate +24 hours"));  
                    }                   
                    ?>
                    <div class="form-group" id="holidyEndDay" style="display:<?= $event ? ( $event->val('end') > $newStartDate ? 'block' : 'none' ) : 'none'; ?>;">
                        <label for="end_date" class="col-sm-12 control-label"><?= gettext("Cultural Observance End Date");?><span style="color:red"> *</span></label>
                        <div class="col-sm-12">
                            <input type="text" onchange="validateHolidayDate()" name="enddate" id="end_date" value="<?= $event ? date('Y-m-d',strtotime($event->val('end'))) : '' ?>" class="form-control" aria-required="true" placeholder="YYYY-MM-DD" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="redactor_content" class="col-sm-12 control-label"><?= gettext("Description");?><span style="color:red"> *</span></label>
                        <div class="col-sm-12">
                            <div id="post-inner" class="post-inner-edit">
                            <textarea class="form-control" placeholder="<?= gettext('Write some description about the Cultural Observance');?>" name="event_description" rows="3" id="redactor_content" maxlength="2000" aria-required="true"><?= $event ? htmlspecialchars($event->val('event_description')) : '' ?></textarea>
                            </div>
                        </div>
                    </div>
                    
					<div class="text-center">
						<button type="button" onclick="addOrUpdateHoliday('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($eventid); ?>')" class="btn btn-primary prevent-multi-clicks"><?= gettext("Submit");?></button>
                        
                        <button type="button" onclick="closeHolidayModal()" data-dismiss="modal" class="btn btn-secondary" ><?= gettext("Cancel");?></button>&nbsp;
                        
					</div>
				</form>
			</div>
		</div>  
	</div>
</div>
		
<script>
    $(document).ready(function(){
        $('#redactor_content').initRedactor('redactor_content','event',[],[],'<?= $_COMPANY->getImperaviLanguage(); ?>');

        $(function() {
			$("#start_date").datepicker({
				prevText: "click for previous months",
				nextText: "click for next months",
				showOtherMonths: true,
				selectOtherMonths: false,
				dateFormat: 'yy-mm-dd',
                beforeShow:function(textbox, instance){
                    $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
                },
                beforeShow:function(textbox, instance){
                    $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
                }
			});
            $("#end_date").datepicker({
                prevText: "click for previous months",
                nextText: "click for next months",
                showOtherMonths: true,
                selectOtherMonths: true,
                dateFormat: 'yy-mm-dd',
                beforeShow:function(textbox, instance){
                    $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
                },
                beforeShow:function(textbox, instance){
                    $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
                }
            });
		});        
        redactorFocusOut('#multiDayHoliday'); // function used for focus out from redactor when press shift + tab.
    });

    function showHolidayEndDateInput(e){
        var eday = $('#holidyEndDay');
        if ($(e).is(':checked')) {
            $('#end_date').val('');
            eday.show();
        } else {
            eday.hide();
        }
    }

    function closeHolidayModal(){
    setTimeout(function(){				
        <?php  if(!$eventid){ ?>			
                $('.new-holiday-btn').trigger('focus');
            <?php } else { ?>
                $('#holiday_<?= $_COMPANY->encodeId($eventid);?>').focus();
           <?php } ?>
        },600);
    }

    $('#holidayModal').on('shown.bs.modal', function () {
    setTimeout(function(){							
            $('.close').trigger('focus');
            $('.redactor-in').attr('aria-required', 'true');
        },200);        
    });

    $(document).ready(function(){
        $(".redactor-voice-label").text("<?= gettext('Description');?>");
    });

    $('#holidayModal').on('hidden.bs.modal', function () { 
        if ($('.modal').is(':visible')){ 
            $('body').addClass('modal-open');
        }  
    })

    retainFocus("#holidayModal");

function validateHolidayDate(){
    let start_date = $('#start_date').val();
	let end_date = $('#end_date').val();

	if(start_date){
		let start_date_label = $('#start_date').labels().text();	
		start_date_label = start_date_label.replace("*","");
		start_date_label = start_date_label.replace("[YYYY-MM-DD]","");	
		let validation_msg = start_date_label+ " <?= gettext('field date format should be [YYYY-MM-DD]')?>.";
		if(!isValidDateString(start_date)){
			$('#start_date').val('');
			swal.fire({title: 'Error', text: validation_msg}).then(function(result) {
				$('#start_date').focus();
			});			
		}
	}
    if(end_date){
		let end_date_label = $('#end_date').labels().text();
		end_date_label = end_date_label.replace("*","");
		end_date_label = end_date_label.replace("[YYYY-MM-DD]","");	
		let end_date_validation_msg = end_date_label+ " <?= gettext('field date format should be [YYYY-MM-DD]')?>.";
		if(!isValidDateString(end_date)){
			$('#end_date').val('');
			swal.fire({title: 'Error', text: end_date_validation_msg}).then(function(result) {
				$('#end_date').focus();
			});			
		}
	}	
}
</script>