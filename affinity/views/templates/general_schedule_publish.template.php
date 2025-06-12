<?php
    [$publish_Ymd,$publish_h,$publish_i,$publish_A] = explode ("%",date("Y-m-d%h%i%A"));
    //RoundUp minutes to the nearest 5 in "05" format.
    $publish_i = sprintf("%02d",ceil(((int)$publish_i+1)/5)*5);

    // Set following to default if there are not preset.
    $checked = $checked ?? 'checked';
    $pre_select_publish_to_email = $pre_select_publish_to_email ?? true;
    // Publish on various channels
    $check_publish_to_web = $checked;
    $check_publish_to_email = ($checked && $pre_select_publish_to_email) ? 'checked' : '';
    $check_publish_to_external = ($checked && $pre_select_publish_to_email) ? 'checked' : '';
    $disclaimer = $disclaimer ?? '';
?>

<div id="general_schedule_publish_modal" tabindex="-1" class="modal fade">
    <div aria-label="<?= sprintf(gettext("Publish %s"),$template_publish_what);?>" class="modal-dialog" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="general_schedule_publish_title"><?= sprintf(gettext("Publish %s"),$template_publish_what);?></h4>
                <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="col-sm-12">
                    <form class="" id="schedulePublishForm">
                        <input type="hidden" name="groupid" value="<?= $enc_groupid ?>">
                        <input type="hidden" name="objectid" value="<?= $enc_objectid; ?>">
                        <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
                    <?php if($template_publish_js_method !='saveScheduleMessagePublishing'){ ?>
                        <div class="form-group">
                            <div class="col-sm-12 alert-info">
                                <?=$disclaimer; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3"><strong><?= gettext("Where");?>:</strong></label>
                            <div class="col-sm-9">

                                <?php if(!$hidePlatformPublish) { ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="online" id="publish_where_online" name="publish_where" required <?= $check_publish_to_web ?> disabled>
                                    <label class="form-check-label" for="publish_where_online">
                                        <?= gettext("This platform only");?>
                                    </label>
                                </div>
                                <?php } ?>

                                <?php if(!$hideEmailPublish){ ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="online_email" id="publish_where_online_email" name="publish_where_email" <?= !isset($hideEmailAndExternalPublishing) ? $check_publish_to_email : '' ?> >
                                    <label class="form-check-label" for="publish_where_online_email">
                                        <?= gettext("Email");?>
                                    </label>
                                    <div id="email_subtext" class="form-text alert-warning small px-2 py-1 mb-2" style="display:none;">
                                     <?= $email_subtext ?? '' ?>
                                    </div>
                                </div>
                                <?php } ?>
                                
                                <div class="clearfix"></div>
                                <?php if(!$hidePlatformPublish) {?>
                                <?php foreach($integrations as $integration){ ?>
                            <div class="form-check integrations-div">
                                <input id="publish_where_integrations_<?=$integration['externalId']?>" class="form-check-input integrations" type="checkbox" value="<?= $_COMPANY->encodeId($integration['externalId']) ?>" name="publish_where_integration[]" <?= !isset($hideEmailAndExternalPublishing) ? ($integration['publish_option_pre_selected'] ? $check_publish_to_external : '') : ''; ?> >
                                <label class="form-check-label" for="publish_where_integrations_<?=$integration['externalId']?>">
                                    <?= $integration['externalName'];?>
                                </label>
                            </div>
                            <?php } ?>
                                <?php } ?>

                            </div>
                        </div>
                    <?php } ?>
                        <div id="schedule_later_option" class="form-group">
                            <label class="col-sm-3"><strong><?= gettext("When");?>:</strong></label>
                            <div class="col-sm-9">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" value="now" id="publish_when_now" name="publish_when" required checked>
                                    <label class="form-check-label" for="publish_when_now">
                                        <?= gettext("Now");?>
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" value="scheduled" id="publish_when_scheduled" name="publish_when" required>
                                    <label class="form-check-label" for="publish_when_scheduled">
                                        <?= gettext("Schedule for later");?>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="schedule_later_form" class="schedule_later_box" style="display: none;">
                        <div class="form-group ">
                            <p><strong><?= gettext("Publish On");?></strong></p>
                        </div>
                        <div class="form-group ">
                        <div class="row">
                            <label class="col-sm-2" for="start_date"><?= gettext("Date");?></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="start_date" name="publish_Ymd"
                                       value="<?= date('Y-m-d'); ?>" placeholder="YYYY-MM-DD" readonly required>
                            </div>
                        </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                            <label for="inputEmail" class="col-sm-2 control-lable"><?= gettext("Time");?></label>
                            <div class="col-sm-3 hrs-minutes">
                                <select aria-label="<?= gettext("Time");?>" class="form-control" id="publishtime" name='publish_h' required>
                                    <?=getTimeHoursAsHtmlSelectOptions($publish_h);?>
                                </select>
                            </div>
                            <div class="col-sm-3 hrs-minutes">
                                <select aria-label="<?= gettext("hour");?>" class="form-control" name="publish_i" required>
                                    <?=getTimeMinutesAsHtmlSelectOptions($publish_i);?>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label class="radio-inline"><input aria-label="<?= gettext("AM");?>" type="radio" value="AM" name="publish_A"
                                                                   required
                                                                   <?= ($publish_A == 'AM') ? 'checked' : '' ?>>AM</label>
                                <label class="radio-inline"><input aria-label="<?= gettext("PM");?>" type="radio" value="PM" name="publish_A"
                                                                   <?= ($publish_A == 'PM') ? 'checked' : '' ?>>PM</label>
                            </div>
                            </div>
                            <div class="row">
                            <div class="col-sm-2">&nbsp;</div>
                            <div class="col-sm-10">
                                <p class='timezone' onclick="showTzPicker();"><a tabindex="0" class="link_show"
                                                                                 id="tz_show"><?= $_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' ?>
                                        <?= gettext("Time");?></a></p>
                            </div>
                            </div>
                            <div class="row">
                            <input type="hidden" name="timezone" id="tz_input"
                                   value="<?= $_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' ?>">
                            <div id="tz_div" style="display:none;">
                                <div class="col-sm-2">&nbsp;</div>
                                <div class="col-sm-10">
                                    <select class="form-control teleskope-select2-dropdown" id="selected_tz" onchange="selectedTimeZone()" style="width: 100%;">
                                        <?php echo getTimeZonesAsHtmlSelectOptions($_SESSION['timezone']); ?>
                                    </select>
                                </div>
                            </div>
                            </div>
                        </div>
                        </div>
                    </form>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="modal-footer text-center">
                <button id="publishPostBtn" type="submit" class="pop-identifier btn btn-affinity confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= sprintf(gettext("Are you sure you want to publish this %s?"),$template_publish_what);?>" style="background-color: #0077B5 !important;"
                        onclick=<?=$template_publish_js_method?>("<?= $enc_groupid; ?>")><?= gettext("Submit");?>
                </button>
            </div>
        </div>

    </div>
</div>

<script>

    jQuery(function () {
        jQuery("#start_date").datepicker({
            prevText: "click for previous months",
            nextText: "click for next months",
            showOtherMonths: true,
            selectOtherMonths: false,
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            maxDate: 30
        });
    });

    jQuery(document).ready(function() {
        jQuery(".confirm").popConfirm({content: ''});
    });  

    jQuery(document).on("change", "#publish_when_now, #publish_when_scheduled", function () {
        let val = $(this).val();
        if (val == "scheduled") {
            $("#schedule_later_form").show().css('display', 'inline-block');
        } else {
            $("#schedule_later_form").show().css('display', 'none');
        }
    });

    $('#email_subtext').toggle(!$('#publish_where_online_email').prop('checked'));

    $('#publish_where_online_email').change(function () {
      $('#email_subtext').toggle(!this.checked);
    });

$('#general_schedule_publish_modal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
});

//On Enter Key...
 $(function(){
       $("#tz_show").keypress(function (e) {
           if (e.keyCode == 13) {
               $(this).trigger("click");
           }
        });
    });

</script>
<script>
	$('.pop-identifier').each(function() {
		$(this).popConfirm({
		container: $("#general_schedule_publish_modal"),
		});
	});

</script>