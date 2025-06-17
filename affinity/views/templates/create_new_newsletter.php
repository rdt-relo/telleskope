<div id="createNewsletterFormModal" class="modal fade" role="dialog" tabindex="-1">
	<div aria-label="<?=$form_title?>" class="modal-dialog modal-lg">  
		<div class="modal-content">
			<div class="modal-header">
        <h2 class="modal-title" id="form_title"><?=$form_title?></h2>
				<button id="btn_close" aria-label="Close" type="button" class="close" onclick="closeAllActiveModal()" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
                <div class="col-md-12">
                    <form class="form-horizontal" id="createNewsletterForm" method="post" action="" >
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <div class="form-group">
                        <p class=" control-lable col-md-12" > <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                    </div>

                <div class="form-group">
                    <label for="newslettername" class=" control-lable col-md-12" ><?= gettext("Newsletter Name");?><span style="color: #ff0000;"> *</span></label>
                    <div class="col-md-12">
                        <input type="text" class="form-control" name="newslettername" id="newslettername" placeholder="<?= gettext("Newsletter name here");?>" required>
                    </div>
                </div>

                <?php if($groupid){ ?>
                    <?php 
                    $warn_if_all_chapters_are_selected = true; 
                    $displayStyle = 'row12';
                    ?>
                    <?php include_once __DIR__.'/../common/init_chapter_channel_selection_box.php'; ?>
                <?php } ?>
               
                <div class="form-group">
                    <div id="template_container">
                        <label for="newsletter_template" class="col-md-12 control-lable" ><?= gettext("Template");?><span style="color: #ff0000;"> *</span></label>
                        <div class="col-md-12">
                            <select aria-label="<?= gettext("Select Newsletter Template");?>" type="text" class="form-control" id="newsletter_template" name="templateid" required>
                                <option value=""><?= gettext("Select Template");?></option>
                                <?php for($i=0;$i<count($templates);$i++){ ?>
                                <option value='<?= $_COMPANY->encodeId($templates[$i]['templateid']) ?>'><?= htmlspecialchars($templates[$i]['templatename']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="clearfix"></div>
                <div id="sample_email_template_outer" style="min-height: 465px;">
                    <div id="sample_email_template" style="min-height: 500px; transform: scale(0.93); transform-origin: 50% 0% 0px; overflow-y:scroll; border: 1px solid darkgray;"></div>
                </div>
                <div class="clearfix"></div>
                       
                        <div class="form-group text-center mt-4">
                        <button type="button" id="newsletter-btn-submit" name="submit" onclick="createNewsletter('<?=$encGroupId;?>');" class="btn btn-primary newsletter-btn-submit" aria-label="<?= gettext("Create");?>"><?= gettext("Create");?></button>
                            <button type="button" data-dismiss="modal" class="btn btn-affinity-gray" onclick="closeAllActiveModal();" aria-label="Close"><?= gettext("Close");?></button>&nbsp;
                        </div>  
                    </form>
                </div>
			</div>
		</div>  
	</div>
</div>
<script>
    // For editor
    var dropdown = document.getElementById("newsletter_template");
    var templateId = "";

    dropdown.addEventListener("change", function() {
        templateId = dropdown.value;
        if (templateId) {
            $("#newsletter-btn-submit").prop("disabled", false);
        } else {
            $("#newsletter-btn-submit").prop("disabled", true);
        }
        initRevolappEditor('<?= $encGroupId ?>');
    });

    function initRevolappEditor (g){

        if (typeof app === 'undefined' || app === null) {
          var app = Revolvapp('#sample_email_template', {
              source: false,
              content: '',
              editor: {
                  viewOnly: true,
                  font: 'TeleskopeNewsletter, Lato,Helvetica, Arial, sans-serif',
                  path: '../vendor/js/revolvapp-2-3-10/',
                  lang: '<?= $_COMPANY->getImperaviLanguage();?>'
              },
              toolbar: {
                  sticky:false,
              },
          });
        }

        if (templateId) {
            var c = $("#chapter_input option:selected" ).val();
             $('#newsletter-btn-submit').prop("disabled", false);
             //$('#sample_email_template').show();
            $.ajax({
                url: 'ajax_newsletters?fetchTemplate='+g,
                type: 'GET',
                data: {'templateid': templateId, 'chapterid': c},
                success: function (data) {
                    if (data.startsWith('Error')) {
                        swal.fire({title: '<?= addslashes(gettext("Error"))?>', text: "<?= addslashes(gettext('Unable to load the template'))?>"});
                    } else {
                        const sample_template_div = $('#sample_email_template');
                        sample_template_div.hide();
                        app.editor.setTemplate(data);
                        sample_template_div.show();
                        app.editor.adjustHeight();
                    }
                }
            });
        } else {
            app.editor.setTemplate('<re-html><<re-body><re-main><re-block align="center" padding="200px 0 205px 0"><re-text><?= addslashes(gettext("This area is for previewing newsletter templates. Once you choose a template, a preview will be shown here.")) ?></re-text></re-block></re-main></re-body></re-html>');
        }
    }

    initRevolappEditor('');

    $('#createNewsletterFormModal').on('shown.bs.modal', function () {	
        $(".rex-editor").removeAttr("role");
        $('#btn_close').focus();
        $(".rex-button").attr("aria-label","Mobile View");
        $(".rex-button").attr("tabindex","0");
        $(".rex-editor").attr("title","presentation");
        $(".rex-editor").attr("tabindex","-1");
    });
</script>