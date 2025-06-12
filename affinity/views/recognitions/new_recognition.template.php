<!-- New recognition POP UP -->
<style>
.fa.fa-times {
	color: #f80e0e;
	background-color: #fff;
	position: absolute;
	margin-left: 0px;
}
.hideElementCls, .hide-it-all, .hideElementCls +.recognizing-label
{
	display:none;
}
</style>
<div class="modal" id="loadNewRecognitioinModal">
  <div aria-label="<?= $formTitle;?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modal-title" class="modal-title"><?= $formTitle;?></h2>
		<button type="button" class="close" id="startfocus" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="">
			<form class="form-horizontal" id="newrecognition">
				<input type="hidden" name="groupid" value="<?= $_COMPANY->encodeId($groupid); ?>">
				<input type="hidden" name="recognition_type" value="<?= $_COMPANY->encodeId($recognition_type); ?>">
				<input type="hidden" name="recognitionid" value="<?= $_COMPANY->encodeId($recognitionid); ?>">
				<p style="font-size: small"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
				<br>
				    <div class="form-group <?= ($recognition_type != Recognition::RECOGNITION_TYPES['recognize_a_colleague']) ? 'hideElementCls' : '' ?>" role="group">
					<fieldset>
						<label id="radioRecognition"><?= gettext("Recognition is on behalf of");?><span style="color: #ff0000;"> *</span></label>
				
                        <div class="form-check">
                            <input  aria-required="true" aria-labelledby="radioRecognition radioPersonBtn" class="form-check-input" type="radio" name="behalfOf" value="Person" id="behalfOf1" checked >
                            <label id="radioPersonBtn" class="form-check-label" for="behalfOf1"><?= gettext("Person");?></label>
                        </div>
					    <div class="form-check">
					        <input aria-labelledby="radioRecognition radioTeamBtn" class="form-check-input" type="radio" name="behalfOf" value="Team" id="behalfOf2" >
					        <label id="radioTeamBtn" class="form-check-label" for="behalfOf2"><?= gettext("Team");?></label>
					    </div>
					</fieldset>
					</div>
                      
				   <label for="searchBox2" class="recognizing-label"><?= gettext("Who is recognizing");?><span style="color: #ff0000;"> *</span></label>
				   <div class="form-group <?= ($recognition_type != Recognition::RECOGNITION_TYPES['recognize_a_colleague']) ? 'hide-it-all' : '' ?>" id="recognizedbyPerson">
						<input aria-describedby="countSearchResultsWhoIs" aria-required="true" class="form-control" autocomplete="off" id="searchBox2" value="" onkeyup="searchUserForRecognizing('<?= $_COMPANY->encodeId($groupid); ?>',this.value)" placeholder="<?= gettext('Search user');?>"  type="text">
						<div id="show_dropdown2"  role="status" aria-live="polite"> </div>
                  </div>
				  <div class="form-group" id="recognizedbyTeam" style="display:none;">
                    <input class="form-control"  value="<?= $group->val('groupname'); ?>"  type="text" name="recognizedbyTeam" placeholder="Team Name" aria-required="true">
				</div>

				   <div class="form-group">
				   <label for="searchBox"><?= gettext("Who to recognize");?><span style="color: #ff0000;"> *</span></label>
					<?php if($recognition_type == Recognition::RECOGNITION_TYPES['recognize_a_colleague']){ ?>
						
						<div class="checkbox">
                            <label><input type="checkbox" id="searchAllusers" value="1"> <small><?= sprintf(gettext("Search all users (if unchecked only members in this %s are searched)"),$_COMPANY->getAppCustomization()['group']["name-short"]); ?></small></label>
                        </div>
						<input aria-describedby="countSearchResults" aria-required="true" class="form-control" autocomplete="off" id="searchBox" value="" onkeyup="searchUserForRecognition('<?= $_COMPANY->encodeId($groupid); ?>',this.value)" placeholder="<?= gettext('Search user');?>"  type="text">
						<div id="show_dropdown"  role="status" aria-live="polite"> </div>


					<?php } else { ?>
						<input aria-required="true" class="form-control"  value="<?= gettext("Myself") ?>"  type="text" readonly >
						<input class="form-control" name='userid' id='user_search' value="<?= $_COMPANY->encodeId($_USER->id()); ?>"  type="hidden" >
					<?php } ?>
                </div>
				<div class="form-group">
					<label for="input_date"><?= gettext("Recognition date"); ?> <span style="font-size: xx-small">[<?= gettext("YYYY-MM-DD");?>]</span><span style="color: #ff0000;"> *</span></label>

					<input type="text" name="recognitiondate" class="form-control" id="input_date" placeholder="<?= gettext('YYYY-MM-DD');?>" autocomplete="off" data-previous-value="" aria-required="true">
					<span id="input_date_error_msg" class="error-message" role="alert"></span>
				</div>	

				<div class="form-group">
					<label for="redactor_content"><?= gettext("Recognition description")?>:</label>
					<div id="post-inner" class="" role="textbox">
						<textarea class="form-control" name="description" rows="6" id="redactor_content" required maxlength="2000" placeholder="<?= gettext("Add recognition description.");?>"><?= $recognition ? htmlspecialchars($recognition->val('recognition')) : ''; ?></textarea>
					</div>
				</div>

				<?php
					$topictype = 'REC';
					include(__DIR__ . '/../templates/event_custom_fields.template.php');
				?>

				<div class="form-group"> 
					<div class="col-12 text-center">
						<button id="addRecognitioin" type="button" onclick="addOrUpdateRecognitioin('<?= $_COMPANY->encodeid($groupid)?>','<?= $_COMPANY->encodeId($recognition_type); ?>')" class="btn btn-affinity mb-2 prevent-multi-clicks"><?= gettext("Submit");?></button>
						<button type="button" class="btn btn-secondary mb-2" data-dismiss="modal"><?= gettext("Close");?></button>
					</div>
				</div>
			</form>
		</div>
      </div>
    </div>
  </div>
</div>

<script>
	$(document).ready(function(){
		//document.querySelector("#startfocus").focus();
		
		var fontColors = <?= $fontColors; ?>;
		$('#redactor_content').initRedactor('redactor_content', 'recognition',['fontcolor','counter','handle'],fontColors,'<?= $_COMPANY->getImperaviLanguage(); ?>');
   		$(".redactor-voice-label").text("Add recognition description");	
		   
		var element = $(".reco-custom-select-field").last().attr('ID');
		if(element) {
			redactorFocusOut('#'+element); 
		}else{
			redactorFocusOut('#input_date'); 
		}
						
    // Attach event listeners to input fields
    let startDateInput = document.querySelector('#input_date');    
    startDateInput.addEventListener('keydown', customKeyPress);  
    startDateInput.addEventListener('blur', dateOnBlurFn);

	let todayDate = new Date();
	// Initialize datepickers
	
    $("#input_date").datepicker({
        showOtherMonths: true,
        selectOtherMonths: true,
        minDate: todayDate,
        beforeShow: openDatepicker,
        onClose: closeDatepicker,
        dateFormat: 'yy-mm-dd',
        onSelect: function(selectedDate, inst){
            validateDateInput(this);           
        },
		beforeShow:function(textbox, instance){
			$('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
		}
    });
	// End datepicker
 

	 searchUserForRecognizing('<?= $_COMPANY->encodeId($groupid); ?>','','<?= $_COMPANY->encodeId($_USER->id());?>');
	
	 $('.redactor-statusbar').attr('id',"redactorStatusbar");
	 $('.redactor-statusbar').attr('aria-live',"polite");  
	 $('.redactor-source').attr('aria-describedby',"redactorStatusbar");
});

	function searchUserForRecognition(g,k){
        delayAjax(function(){
            if(k.length >= 3){
				var searchAllUsers = $('#searchAllusers').is(':checked');
				$.ajax({
					url: 'ajax_recognition.php?searchUserForRecognition=1',
					type: "GET",
					data: {'groupid':g,'keyword':k,'searchAllUsers':searchAllUsers},
					success: function(data){
						try {
							let jsonData = JSON.parse(data);
							swal.fire({title:jsonData.title,text:jsonData.message});
						} catch(e) {
							$("#show_dropdown").html(data);
							var myDropDown=$("#user_search");
							var length = $('#user_search> option').length;
							var searchCount = $('#user_search option').length;
							if (myDropDown.val()){
								$("#show_dropdown").prepend('<div id="countSearchResults" style="margin-left: 12px;" class="countResults">'+searchCount+' <?= gettext("results are available.");?> </div>');
							}
						}
					}
				});
			}
        }, 500 );
    }



	function closeDropdown(){
        var myDropDown=$("#user_search");
        var length = $('#user_search> option').length;
        myDropDown.attr('size',0);
    }

	function removeSelectedUser(i){
        $("#searchBox").val('');
        $("#show_dropdown").html('');
        $("#"+i).show();
    }

	function removeSelectedrecognizebyUser(i){
        $("#searchBox2").val('');
        $("#show_dropdown2").html('');
        $("#"+i).show();
    }

	function addOrUpdateRecognitioin(g,t,checkform=0){
		var formdata = $('#newrecognition')[0];
		var finaldata  = new FormData(formdata);
		finaldata.append("groupuid",g);
		finaldata.append("recognition_type",t);
		preventMultiClick(1);
		$.ajax({
			url: 'ajax_recognition.php?addOrUpdateRecognitioin=1',
			type: 'POST',
			enctype: 'multipart/form-data',
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success: function(data) {
				try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title:jsonData.title,text:jsonData.message})
                    .then(function(result) {

                        if (jsonData.status == 1) {
							$('#loadNewRecognitioinModal').modal('hide');
							$('body').removeClass('modal-open');
							$('body').css('overflow','hidden');
							$('.modal-backdrop').remove();
                            if(!checkform){
							getRecognitions(g,jsonData.val,1);
							}else{alert("manager");}							
                        }
						
						$("#addRecognitioin").focus();
                    });
					setTimeout(() => {
						$(".swal2-confirm").focus();
					}, 500)
				} catch(e) {
					// Nothing to do
                    swal.fire({title: 'Error', text: "Unknown error."});
				}
			},
			error: function ( data )
			{
				swal.fire({title: 'Error!',text:'Internal server error!. Please try after some time.'});
			}
		});
		setTimeout(() => {
			$(".swal2-confirm").focus();
		}, 200)
	}
</script>
<script>
// add focus in modal
trapFocusInModal("#loadNewRecognitioinModal");

$("#loadNewRecognitioinModal").scroll(function() {
  var y = $(this).scrollTop();
  if (y > 5) {
	$("#ui-datepicker-div").hide();		
  } else {
	$('#input_date').click(function() {
		$('#loadNewRecognitioinModal').scrollTop(0);
		$("#ui-datepicker-div").show();
	});	    
  }
});
 
$('#loadNewRecognitioinModal').on('shown.bs.modal', function () {
   $('#startfocus').trigger('focus');
});
 $("input[name='behalfOf']").click(function(){

	if($(this).val() == "Person"){
	  $("#recognizedbyPerson").show();
	  $("#recognizedbyTeam").hide();
	 }else{
    	$("#recognizedbyPerson").hide();
		$("#recognizedbyTeam").show();
	}
	
 })
 function searchUserForRecognizing(g,k,uid=""){ 
        delayAjax(function(){
            if(k.length >= 3 || uid !=""){
				var searchAllUsers = true;
				$.ajax({
					url: 'ajax_recognition.php?searchUserForRecognition=1',
					type: "GET",
					data: {'groupid':g,'keyword':k,'uid':uid,'searchAllUsers':searchAllUsers,'recognizeby':1},
					success: function(data){
						try {
							let jsonData = JSON.parse(data);
							swal.fire({title:jsonData.title,text:jsonData.message});
						} catch(e) {searchBox2
							$("#show_dropdown2").html(data);
							var myDropDown=$("#user_search1");
							var length = $('#user_search1> option').length;
							var searchCount = $('#user_search1 option').length;
							if (myDropDown.val()){
								$("#show_dropdown2").prepend('<div id="countSearchResultsWhoIs" style="margin-left: 12px;" class="countResults">'+searchCount+' <?= gettext("results are available.");?> </div>');
								$("#user_search1").attr("aria-label", "<?= gettext('Recognizing users list');?>");
							}
						}
					}
				});
			}
        }, 500 );
    }
</script>