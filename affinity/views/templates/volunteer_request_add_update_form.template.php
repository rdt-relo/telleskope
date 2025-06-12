<style>
	.select2-container .select2-selection--single {
		height: calc(1.5em + .75rem + 2px);
	}
	.select2-container--default .select2-selection--single .select2-selection__rendered {
		line-height: 33px;
	}
	.select2-container--default .select2-selection--single {
		border: 1px solid #dbdbdb;
	}
</style>
<div id="new_volunteer_request_form_modal" class="modal fade">
	<div aria-label="<?=$form_title?>" class="modal-dialog  modal-lg" aria-modal="true" role="dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h2 class="modal-title" id="form_title"><?=$form_title?></h2>
                <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">               
                <form class="form-horizontal" method="post" id="event_volunteer_request_form">
                    
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <div class="form-group">
                        <label class="control-label col-md-12" for="email"><?= gettext("Event Volunteer Types");?></label>
                        <div class="col-md-12">
                            <select class="form-control" id="volunteer_types" name="volunteer_type" style="width: 100%;">
                                    <?php if($volunteerRequest){ ?>
                                        <option value="<?= $type; ?>"><?= htmlspecialchars($type) ?></option>
                                    <?php } else { ?>
                                        <option value=""></option>
                                    <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-12 control-label"><?= gettext("Number of Volunteers needed");?></label>
                        <div class="col-md-12">
                            <input class="form-control" name="volunteer_needed_count" id="volunteer_needed_count" placeholder="<?= gettext("Number of volunteers needed");?>"  value="<?= $volunteerRequest ? $volunteerRequest['volunteer_needed_count'] : ''; ?>" onkeyup="onlyInt(this)" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-12 control-label"><?= gettext("Volunteer Hours");?></label>
                        <div class="col-md-12">
                            <input class="form-control" name="volunteer_hours" id="volunteer_hours" placeholder="<?= gettext("Volunteer Hours");?>"  value="<?= $volunteerRequest ? $volunteerRequest['volunteer_hours'] : $volunteeringHours; ?>"  onkeyup="onlyFloat(this)" required>
                            <small><?=gettext('Enter hours in decimal format. For example, 1 hour and 15 minutes should be entered as 1.25.')?></small>
                        </div>
                    </div>
                    <div class="form-group">
                            <label class="col-md-12 control-label" for="redactor_content"><?= gettext("Description")?>:</label>
                            <div class="col-md-12" role="textbox">
                                <textarea class="form-control" name="volunteer_description" rows="6" id="volunteer_description" required placeholder="<?= gettext("Describe your volunteer role & expectations (max 1000 characters)");?>"><?= $volunteerRequest ? htmlspecialchars($volunteerRequest['volunteer_description']) : ''; ?></textarea>
                            </div>
                        </div>
                    <div class="form-group">
                        <label class="col-md-12 control-label"><?= gettext("CC Email");?></label>
                        <div class="col-md-12">
                            <input class="form-control" name="cc_email" id="cc_email" placeholder="<?= gettext("CC Email");?>" value="<?= $volunteerRequest ? $volunteerRequest['cc_email'] : ''; ?>" type="email" >
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <div class="form-check ml-3">
                            <label class="form-check-label">
                                <input type="checkbox" id="hide_from_signup_page" name="hide_from_signup_page" class="form-check-input" value="1" <?= $volunteerRequest && isset($volunteerRequest['hide_from_signup_page']) && $volunteerRequest['hide_from_signup_page'] == 1 ? 'checked' : '';  ?>><?= gettext("Hide from signup page"); ?>
                            </label>
                        </div>
                    </div>
                    
                    <?php if ($_COMPANY->getAppCustomization()['event']['volunteers'] && $_COMPANY->getAppCustomization()['event']['external_volunteers']) { ?>
                    <div class="form-group mb-0">
                      <div class="form-check ml-3">
                        <label class="form-check-label">
                          <input
                            type="checkbox"
                            id="allow_external_volunteers"
                            name="allow_external_volunteers"
                            class="form-check-input"
                            value="1"
                            <?= ($volunteerRequest && ($volunteerRequest['allow_external_volunteers'] ?? false)) ? 'checked' : ''  ?>
                          >
                          <?= gettext('Allow external volunteers (friends and family)') ?>
                        </label>
                      </div>
                    </div>
                    <?php } ?>

                    <p>&nbsp;</p>
                    <div class="form-group text-center">
                        <button type="button" data-dismiss="modal" class="btn btn-secondary" onclick="manageVolunteers('<?= $_COMPANY->encodeId($eventid)?>');"><?= gettext("Cancel");?></button>&ensp;
                        <button type="button" onclick="addUpdateEventVolunteerRequest('<?= $_COMPANY->encodeId($eventid)?>');" class="btn btn-primary prevent-multi-clicks"><?= gettext("Submit");?></button>
                    </div>
                </form>	
			</div>
		</div>  
	</div>
</div>
		
<script>
$(document).ready(function(){
    //document.querySelector("#startfocus").focus();
    $R('#volunteer_description',
	{
		styles: true,
        limiter: 1000,
		minHeight: '200px',
		buttons: ['redo', 'undo', 'bold', 'italic', 'underline'],
		formatting: ['p', 'blockquote'],
		pasteImages: false, //Since we have clipboardUpload set to true.
		pastePlainText: false,
		pasteBlockTags: [ 'p','blockquote'],
		pasteInlineTags: ['a', 'br', 'b', 'u', 'i'],
		// Uploads
		plugins: ['fontcolor','counter', 'limiter'],
	}
);
    $(".redactor-voice-label").text("Add Volunteer description");
    redactorFocusOut('#volunteer_needed_count'); // function used for focus out from redactor when press shift +tab.  
});
$("#volunteer_types").select2({
    placeholder: "<?= gettext('Select Event Volunteer Type'); ?>...",
    allowClear: true,
    data: <?= json_encode($eventVolunteerTypes); ?>,
    tags: true,
    createTag: function (params) {
        return {
        id: params.term,
        text: params.term,
        newOption: true
        }
    },
    templateResult: function (data) {
        var $result = $("<span></span>");

        $result.text(data.text);

        if (data.newOption) {
        $result.append('<em style="float: right;">(Add New)</em>');
        }

        return $result;
  }
}).on('select2:opening', function(e) {
    $(this).data('select2').$dropdown.find(':input.select2-search__field').attr('placeholder', "<?= gettext('Add or Search Event Volunteer Type') ?>..")
});



    function closeDropdown(){
        var myDropDown=$("#user_search");
        var length = $('#user_search> option').length;
        myDropDown.attr('size',0);
    }

    function clearSelected(){
        var elements = document.getElementById("sel1").options;
    
        for(var i = 0; i < elements.length; i++){
          elements[i].selected = false;
        }
    }
    
    function showHideSelectRegion(v){
        $("#role_type option:first").attr("disabled", "true");
        if ('' == v) {
            return false;
        }

        $.ajax({
            url: 'ajax.php?checkGroupleadType='+v,
            type: "POST",
            processData: false,
            contentType: false,
            cache: false,
            success: function(data) {
                if (data == 3){
                    $("#select_region").show();
                } else{
                    $('select#sel1 option').removeAttr("selected")
                    $("#select_region").hide();
                }
            }
        });
    }

$('#new_volunteer_request_form_modal').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus')
});
</script>