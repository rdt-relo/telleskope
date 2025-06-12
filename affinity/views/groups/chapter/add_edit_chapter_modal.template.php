<style>
.btn-block {
	background: #418bbb9c;		
}
.pac-container {    
    z-index: 9999 !important;    
}
</style>
<div class="modal fade" id="new_chapter_model">
	<div aria-label="<?= $pagetitle; ?>"  class="modal-dialog" style="max-width:1250px" aria-modal="true" role="dialog">
    	<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<h2 class="modal-title"><?= $pagetitle; ?></h2>
				<button id="btn_close" aria-label="close" type="button" class="close btn_close" data-dismiss="modal">&times;</button>			  
			</div>
			<div class="modal-body" id='replace-box'>	
			<form class="form-horizontal" id="newChapter" method="post">
			
                                <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                                <div class="form-group">
								    <p class="mb-2 col-sm-12"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
								</div>
                                <div class="form-group">
                                    <label class="control-lable col-sm-2" ><?= $_COMPANY->getAppCustomization()['chapter']["name-short"]?> <?= gettext('Name');?><span style="color: #ff0000;">*</span> </label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="chapter_name" name="chapter_name" value="<?= $chapterid > 0 ? htmlspecialchars($edit['chaptername']) : ''; ?>" placeholder="<?= $_COMPANY->getAppCustomization()['chapter']["name-short"]?> <?= gettext('Name');?>" maxlength="34" required>
                                    </div>
                                </div>
                                        
                                <div class="form-group">
                                    <label class="control-lable col-sm-2" ><?= $_COMPANY->getAppCustomization()['chapter']["name-short"]?> <?= gettext('Colour');?></label>
                                    <div  id="cp2" class="col-sm-10 input-group colorpicker-component ">
                                         <input aria-label="<?= $_COMPANY->getAppCustomization()['chapter']["name-short"]?> <?= gettext('Colour');?>" type="text"  id="chapter_color" class="form-control" name="chapter_color" value="<?= $chapterid > 0 ? $edit['colour'] : '#000'; ?> " /><span class="input-group-addon" id="input-addon"><i aria-label="<?= gettext('Select colour');?>" role="button" tabindex="0"></i></span>
                                    </div>
                                </div>  

								<?php if (0) { /* Disabling the ability to modify chapter description from Admin section */?>
                                <div class="form-group">
                                    <label class="control-lable col-sm-2" ><?= $_COMPANY->getAppCustomization()['chapter']["name-short"]?><?= gettext('Description');?></label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" name="about_chapter" id="redactor_content" placeholder="<?= $_COMPANY->getAppCustomization()['chapter']["name-short"]?> <?= gettext('Description');?>"  ><?= $chapterid > 0 ? $edit['about'] : ''; ?></textarea>
                                    </div>
								</div>
								<?php } ?>
                                <div class="form-group" >
									<label class="control-lable col-sm-2"><?= gettext('Office Location');?></label>
									<div class="col-lg-10">
										<div class="row">
											<div class="col-sm-5"><?= gettext('Locations in');?> <?= $_COMPANY->getAppCustomization()['chapter']["name-short"]?> <?= gettext('Region');?></div>
											<div class="col-sm-2"></div>
											<div class="col-sm-5"><?= gettext('Locations assigned to ');?><?= $_COMPANY->getAppCustomization()['chapter']["name-short"]?> </div>
											<div class="col-sm-5">
												<select aria-label="<?= gettext('List of office locations available to assign from');?>" name="from[]" id="multiselect" class="form-control" size="20" multiple="multiple">
													<?php if (count($branches)>0){ ?>
														<?php for($i=0;$i<count($branches);$i++){ 
															$disabled = 'disabled';
															?>
													<?php	if(isset($_GET['cid'])){
																if ($branches[$i]['alreadyUsed'] == $edit['chapterid']){
																	$disabled = '';
																}
																
																if(in_array($branches[$i]['branchid'],explode(',',$edit['branchids']))){
																	$sel = "selected";
																}else{
																	$sel = "";
																}
															}else{ 
																$sel = "";
															} ?>
															<option value="<?=$_COMPANY->encodeId($branches[$i]['branchid'])?>" <?= $branches[$i]['alreadyUsed'] > 0 ? $disabled : '' ?> <?= $sel; ?>><?=htmlspecialchars($branches[$i]['branchname']) ?> (<?= htmlspecialchars(trim(str_replace(',,',',',($branches[$i]['city'].','.$branches[$i]['state'].','.$branches[$i]['country'])),',')) ?>)</option>
															
													<?php	} ?>
													<?php }else{ ?>
															<option value="0"><?= gettext('No office locations');?></option>									
													<?php } ?>
												</select>
											</div>
									
											<div class="col-sm-2 multiselect_action ">
												<br>
												<br>
												<button aria-label="<?= gettext('Right select, Add Locations assigned to ');?><?= $_COMPANY->getAppCustomization()['chapter']["name-short"]?>" type="button" id="multiselect_rightSelected" class="btn btn-block"><i class="fa fa-chevron-right" aria-hidden="true"></i></button>
												<button aria-label="<?= gettext('Left Select, Add Locations in');?> <?= $_COMPANY->getAppCustomization()['chapter']["name-short"]?> <?= gettext('Region');?>" type="button" id="multiselect_leftSelected" class="btn btn-block"><i class="fa fa-chevron-left" aria-hidden="true"></i></button>
											
											</div>
									
											<div class="col-sm-5">
												<select aria-label="<?= gettext('List of office locations assigned');?>"  name="branchids[]" id="multiselect_to" class="form-control" size="20" multiple="multiple"></select>
											</div>
										</div>


									</div>
								</div>

                                <?php if ($_COMPANY->getAppCustomization()['event']['calendar_loc_filter']) { ?>
								<div class="form-group">
                                    <label class="control-lable col-sm-2" ><?= gettext('Virtual Events Location');?> </label>
                                    <div  class="col-sm-10 input-group">
                                        <input type="text" id="autocomplete" class="form-control" name="virtual_event_location" value="<?= $chapterid > 0 ? htmlspecialchars($edit['virtual_event_location']) : ''; ?>" placeholder="Enter a address that is central to all office locations in the <?= $_COMPANY->getAppCustomization()['chapter']["name-short"]?>" required />
										<input type="hidden" id="latitude" name="latitude" value="<?= $chapterid > 0 ? $edit['latitude'] : '0'; ?>">
										<input type="hidden" id="longitude" name="longitude" value="<?= $chapterid > 0 ? $edit['longitude'] : '0'; ?>">
                                    </div>
                                </div> 
                                <?php } ?>

							    <div class="form-group">
                                    <div class="col-sm-12 text-center">			
	<button type="button" class="btn btn-affinity prevent-multi-clicks" onclick="add_update_chapter('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($chapterid);?>','<?=$_COMPANY->encodeId($regionid);?>')" ><?= gettext('Submit');?></button>
	<button type="button" id="chapter_close_button" class="btn btn-secondary" data-dismiss="modal"><?= gettext('Cancel');?></button>	
                                    </div>
                                </div>
                    </form>		 
			</div>			
		  </div>
	</div>
</div>

<script type="text/javascript" src="js/custom_multi_select.js"></script>
<script>
	$(function() { $('#cp2').colorpicker({format: 'rgb'});});	
	setTimeout(function() {
		$("#multiselect_rightSelected").trigger('click');
    },10);
</script>
<script>
	function initAutocomplete() {
        var input = document.getElementById('autocomplete');
        var autocomplete = new google.maps.places.Autocomplete(input);
        google.maps.event.addListener(autocomplete, 'place_changed', function () {
            var place = autocomplete.getPlace();
            document.getElementById('latitude').value = place.geometry.location.lat();
            document.getElementById('longitude').value = place.geometry.location.lng();
        });
    }
</script>

<?php if ($_COMPANY->getAppCustomization()['plugins']['google_maps']) { ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?=GOOGLE_MAPS_API_KEY?>&libraries=places&callback=initAutocomplete" async defer></script>
<?php } ?>

<script>
function add_update_chapter(g,c,r){
	$(document).off('focusin.modal');
	var chapterName = $("#chapter_name").val().trim();	
	if(chapterName == ""){		
        swal.fire({title: "<?=gettext("Error")?>", text: "<?=sprintf(gettext("%s name cannot be empty"),$_COMPANY->getAppCustomization()['chapter']['name-short'])?>", allowOutsideClick:false});
		return;
	}
	$('#multiselect_to option').prop('selected', true); 
	var formdata = $('#newChapter')[0];
	var finaldata  = new FormData(formdata);
	finaldata.append("gid",g);
	finaldata.append("cid",c);
	finaldata.append("rid",r);	
	$.ajax({
		url: 'ajax.php?add_update_chapter=1',
		type: 'POST',
		data: finaldata,		
		processData: false,
		contentType: false,
		cache: false,		
        success : function(data) {
			manageDashboard(g); 
            closeAllActiveModal();
            //$('#loadAnyModal').html(data);
            $('#new_chapter_model').modal('show');	
			let jsonData = JSON.parse(data);			
			swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false})
		}
	});
}

$('#new_chapter_model').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus')
});

$("#input-addon").keypress(function (event) {
            if (event.keyCode === 13) {
                $("#input-addon").click();
            }
        });


$('#new_chapter_model').on('hidden.bs.modal', function (e) {
	$('#chapter_<?=$_COMPANY->encodeId($chapterid);?>').trigger('focus');
})
</script>
