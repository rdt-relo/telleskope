<?php include __DIR__ . '/header.html'; ?>


<!-- New recognition POP UP -->
<style>
.fa.fa-times {
	color: #f80e0e;
	background-color: #fff;
	position: absolute;
	margin-left: 0px;
}
.fa.fa-times {
	color: #f80e0e;
	background-color: #fff;
	position: absolute;
	margin-left: 0px;
}
.hideIt
{
  display: none;
}

.disabledbtns
{
	pointer-events: none; 
	color: gray; 
}

.hideElementCls, .hide-it-all, .hideElementCls +.recognizing-label
{
	display:none;
}
</style>

<?php
    $selectedP = $selectedT = $displayTtxtbox = $displayPtxtbox = $disabledForEdit = $editpart= $disabledbtnscls = $hideIt = "";
    $recognizedbyTeam = $group->val('groupname');
    if(isset($recognition) && !empty($recognition)) {
    $disabledForEdit = "readonly"; $editpart = 1; $disabledbtnscls = "disabledbtns";
    $recognizedbyTeam = $recognition->val('recognizedby_name');
    $hideIt = "hideIt";
    }

    if(isset($recognition) && $recognition->val('recognizedby') > 0 ){
        $selectedP = "checked";
        $displayTtxtbox = "display:none";
        $displayPtxtbox = "";
    }elseif(isset($recognition) and  $recognition->val('recognizedby') == 0){
        $selectedT = "checked";
        $displayPtxtbox = "display:none";
        $displayTtxtbox = "";
    }elseif(!isset($recognition)){
        $selectedP = "checked";
        $displayTtxtbox = "display:none";
        $displayPtxtbox = "";
    }
?>

<div class="modal" id="loadNewRecognitioinModal">
  <div aria-label="<?= $formTitle;?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modal-title" class="modal-title"><?= $formTitle;?></h2>
		</div>
      <div class="modal-body">
        <div class="">
			<form class="form-horizontal" id="newrecognition">
				<input type="hidden" name="groupid" value="<?= $_COMPANY->encodeId($groupid); ?>">
				<input type="hidden" name="recognition_type" value="<?= $_COMPANY->encodeId($recognition_type); ?>">
				<input type="hidden" name="recognitionid" value="<?= $_COMPANY->encodeId($recognitionid); ?>">
				<p style="font-size: small"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
				    <div class="form-group <?= ($recognition_type != Recognition::RECOGNITION_TYPES['recognize_a_colleague']) ? 'hideElementCls' : '' ?>" role="group">
					<fieldset>
						<label id="radioRecognition"><?= gettext("Recognition is on behalf of");?><span style="color: #ff0000;"> *</span></label>
				
                        <div class="form-check">
                            <input aria-labelledby="radioRecognition radioPersonBtn" class="form-check-input" type="radio" name="behalfOf" value="Person" id="behalfOf1"  <?= $recognition ? ($recognition->val('recognizedby') > 0 ? 'checked' : 'disabled') : 'checked' ?> >
                            <label id="radioPersonBtn" class="form-check-label" for="behalfOf1"  ><?= gettext("Person");?></label>
                        </div>
					    <div class="form-check">
					        <input aria-labelledby="radioRecognition radioTeamBtn" class="form-check-input" type="radio" name="behalfOf" value="Team" id="behalfOf2" <?= $recognition ? ($recognition->val('recognizedby')== 0  ? 'checked' : 'disabled') : '' ?> >
					        <label id="radioTeamBtn" class="form-check-label" for="behalfOf2"><?= gettext("Team");?></label>
					    </div>
					</fieldset>
					</div>
                      
				   <label for="searchBox2" class="recognizing-label"><?= gettext("Who is recognizing");?><span style="color: #ff0000;"> *</span></label>
				   <div class="form-group <?= ($recognition_type != Recognition::RECOGNITION_TYPES['recognize_a_colleague']) ? 'hide-it-all' : '' ?>" id="recognizedbyPerson" style="<?=$displayPtxtbox;?>">
						<input aria-describedby="countSearchResultsWhoIs" aria-required="true" class="form-control" autocomplete="off" id="searchBox2" value="" onkeyup="searchUserForRecognizing('<?= $_COMPANY->encodeId($groupid); ?>',this.value)" placeholder="<?= gettext('Search user');?>"  type="text">
						<div id="show_dropdown2"  role="status" aria-live="polite"> </div>
                  </div>
				  <div class="form-group" id="recognizedbyTeam" style="<?= $displayTtxtbox; ?>">
                    <input class="form-control"  value="<?= $recognizedbyTeam; ?>"  type="text" name="recognizedbyTeam" placeholder="Team Name">
				</div>

				   <div class="form-group">
				   <label for="searchBox"><?= gettext("Who to recognize");?><span style="color: #ff0000;"> *</span></label>
					<?php if($recognition_type == Recognition::RECOGNITION_TYPES['recognize_a_colleague']){ ?>
						
						<div class="checkbox" id="selectAllCheckBox">
                            <label><input type="checkbox" id="searchAllusers" value="1"> <small><?= sprintf(gettext("Search all users (if unchecked only members in this %s are searched)"),$_COMPANY->getAppCustomization()['group']["name-short"]); ?></small></label>
                        </div>
						<input aria-describedby="countSearchResults" aria-required="true" class="form-control" autocomplete="off" id="searchBox" value="" onkeyup="searchUserForRecognition('<?= $_COMPANY->encodeId($groupid); ?>',this.value)" placeholder="<?= gettext('Search user');?>"  type="text">
						<div id="show_dropdown"  role="status" aria-live="polite"> </div>


					<?php } else { ?>
						<input class="form-control"  value="<?= gettext("Myself") ?>"  type="text" readonly >
						<input class="form-control" name='userid' id='user_search' value="<?= $_COMPANY->encodeId($_USER->id()); ?>"  type="hidden" >
					<?php } ?>
                </div>
				<div class="form-group">
					<label for="input_date"><?= gettext("Recognition date"); ?> <span style="font-size: xx-small">[<?= gettext("YYYY-MM-DD");?>]</span><span style="color: #ff0000;"> *</span></label>

					<input type="text" name="recognitiondate" class="form-control" id="start_date" placeholder="<?= gettext('YYYY-MM-DD');?>" autocomplete="off" data-previous-value="" aria-required="true" value="<?= $recognition ? $recognition->val('recognitiondate') : ''; ?>">
					<span id="input_date_error_msg" class="error-message" role="alert"></span>
				</div>	

				<div class="form-group">
					<label for="redactor_content"><?= gettext("Recognition description")?>:</label>
					<div id="post-inner" class="" role="textbox">
						<textarea class="form-control" name="description" rows="6" id="redactor_content" required maxlength="2000" placeholder="<?= gettext("Add recognition description.");?>"><?= $recognition ? htmlspecialchars($recognition->val('description')) : ''; ?></textarea>
					</div>
				</div>

				<?php
				  $topictype = 'REC';
				  include(__DIR__ . '/../templates/event_custom_fields.template.php');
				?>

				<div class="form-group"> 
					<div class="col-12 text-center">
						<button id="addRecognitioin" type="button" onclick="addOrUpdateRecognitioin('<?= $_COMPANY->encodeid($groupid)?>','<?= $_COMPANY->encodeId($recognition_type); ?>')" class="btn btn-affinity mb-2 prevent-multi-clicks"><?= gettext("Submit");?></button>
                        <button type="button" class="btn btn-secondary mb-2" data-dismiss="modal" onclick="window.location.href='success_callback.php'"><?= gettext("Close");?></button>
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
        var fontColors = <?= $fontColors; ?>;
		$('#redactor_content').initRedactor('redactor_content', 'recognition',['fontcolor','counter','handle'],fontColors,'<?= $_COMPANY->getImperaviLanguage(); ?>');
   		$(".redactor-voice-label").text("Add recognition description");

           $('#loadNewRecognitioinModal').modal({
                backdrop: 'static',
                keyboard: false
            });
    })
</script>
<script>
	$(document).ready(async function(){
            <?php if(isset($recognition)){ ?>
                let recognizedto = '<?= (int)$recognition->val('recognizedto') ?>' ; 
                let recognizedby = '<?= (int)$recognition->val('recognizedby') ?>' ; 
                try {
                    if( recognizedto > 0 ){
                        await searchUserForRecognition('<?= $_COMPANY->encodeId($groupid); ?>','','<?=$_COMPANY->encodeId($recognition->val('recognizedto'));?>');
                    }

                    if(recognizedby > 0 ){
                        await searchUserForRecognizing('<?= $_COMPANY->encodeId($groupid); ?>','','<?= $_COMPANY->encodeId($recognition->val('recognizedby'));?>');
                    }
                    
                } catch (error) {
                    console.error("An error occured");
                    swal.fire({title:"Error".title,text:"An error occured. Please try again later."});
                }
            <?php }else{?>
                searchUserForRecognizing('<?= $_COMPANY->encodeId($groupid); ?>','','<?= $_COMPANY->encodeId($_USER->id());?>');    
            <?php } ?> 
	});

	function searchUserForRecognition(g,k,uid=""){
        return new Promise((resolve, reject) => {
            if(k.length >= 3 || uid !=""){
				var searchAllUsers = $('#searchAllusers').is(':checked');
				$.ajax({
					url: 'ajax_native.php?searchUserForRecognition=1',
					type: "GET",
					data: {'groupid':g,'keyword':k,'uid':uid,'searchAllUsers':searchAllUsers,'editpart':'<?= $editpart?>'},
					success: function(data){
						try {
							let jsonData = JSON.parse(data);
							swal.fire({title:jsonData.title,text:jsonData.message});
                            reject();
						} catch(e) {
							$("#show_dropdown").html(data);
							var myDropDown=$("#user_search");
							var length = $('#user_search> option').length;
							var searchCount = $('#user_search option').length;
                            if (myDropDown.val()){
							    $("#show_dropdown").prepend('<div style="margin-left: 12px;">'+searchCount+' <?= gettext("results are available.");?> </div>');
                            }

                            <?php if($recognition){ ?>
                                $("#searchBox").hide();
                                $("#show_dropdown > div").hide();
                                $("#selectAllCheckBox").hide();
                            <?php } ?>
                            resolve();
						}
					}
				});
			}
        });
    }

    function searchUserForRecognizing(g,k,uid=""){ 
        return new Promise((resolve, reject) => {
                if(k.length >= 3 || uid !=""){
                    var searchAllUsers = true;
                    $.ajax({
                        url: 'ajax_native.php?searchUserForRecognition=1',
                        type: "GET",
                        data: {'groupid':g,'keyword':k,'uid':uid,'searchAllUsers':searchAllUsers,'recognizeby':1,'editpart':'<?= $editpart;?>'},
                        success: function(data){
                            try {
                                let jsonData = JSON.parse(data);
                                swal.fire({title:jsonData.title,text:jsonData.message});
                                reject();
                            } catch(e) {
                                $("#show_dropdown2").html(data);
                                var myDropDown=$("#user_search1");
                                var length = $('#user_search1> option').length;
                                var searchCount = $('#user_search1 option').length;
                                if (myDropDown.val()){
                                    $("#show_dropdown2").prepend('<div style="margin-left: 12px;">'+searchCount+' <?= gettext("results are available.");?> </div>');
                                }
                                <?php if($recognition){ ?>
                                    $("#searchBox2").hide();
                                    $("#show_dropdown2 > div").hide();
                                <?php } ?>
                                resolve();
                            }
                        }
                    });
                }
        });
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

    $("input[name='behalfOf']").click(function(){
        if($(this).val() == "Person"){
            $("#recognizedbyPerson").show();
            $("#recognizedbyTeam").hide();
        }else{
            $("#recognizedbyPerson").hide();
            $("#recognizedbyTeam").show();
        }
    });

	function addOrUpdateRecognitioin(g,t){
		var formdata = $('#newrecognition')[0];
		var finaldata  = new FormData(formdata);
		finaldata.append("groupuid",g);
		finaldata.append("recognition_type",t);
        preventMultiClick(1);
		$.ajax({
			url: 'ajax_native.php?addOrUpdateRecognitioin=1',
			type: 'POST',
			enctype: 'multipart/form-data',
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success: function(data) {
                preventMultiClick(0);
				try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title:jsonData.title,text:jsonData.message, allowOutsideClick:false})
                    .then(function(result) {

                        if (jsonData.status == 1) {
							window.location.href= 'success_callback.php';
                        }
                    });
				} catch(e) {
					// Nothing to do
                    swal.fire({title: 'Error', text: "Unknown error.", allowOutsideClick:false});
				}
			},
			error: function ( data )
			{
                preventMultiClick(0);
				swal.fire({title: 'Error!',text:'Internal server error!. Please try after some time.', allowOutsideClick:false});
			}
		});
	}
</script>



</body>
</html>
