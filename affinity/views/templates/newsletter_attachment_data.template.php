<style>
    .attachment-list {
        border-bottom: 1px solid rgba(158, 158, 158, 0.61);
        padding-bottom: 20px;
        margin-bottom: 20px;
    }

.about-button:focus { 
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 .1rem rgba(0,123,255,.25);
}
</style>
<?php if (!empty($attachments)){ ?>
    <div class="attachment-list">                    
    <?php for($i=0;$i<count($attachments);$i++){ ?>
        <div class="col-md-12">
            <div class="col-md-8">
                <?= $attachments[$i]['title'] ? $attachments[$i]['title'] : '-<?= gettext("No Title");?>-'; ?>
            </div>
            <div class="col-md-3">
                <a aria-label="<?= gettext("Attachment");?>" style="color:#4f9fcf" href="<?= $attachments[$i]['attachment']; ?>"><?= gettext("Attachment");?></a>
            </div>
            <div class="col-md-1">
                <?php if ($isAllowedToUpdateContent && ($newsletter->isDraft() || $newsletter->isUnderReview())) { ?>
                <a aria-label="<?= gettext("delete attachment");?>" href="javascript:void(0)" class="fa fa-trash deluser" onclick="deletNewsLetterAttachment('<?=$encGroupId;?>','<?= $encNewsletterId ?>','<?= $_COMPANY->encodeId($attachments[$i]['attachment_id']) ?>')" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="" data-original-title="<?= gettext('Are you sure you want to delete?');?>"></a>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
    <div class="clearfix"></div>
    </div>
    <?php } else { ?>
        <div style="margin: 50px 30%;">-- <?= gettext("No Attachments");?> --</div>
    <?php } ?>
    
    <div class="row attachemt-form">
        <form class="form-horizontal" enctype="multipart/form-data" id="newsletterAttachmentForm">
        <?php if ($isAllowedToUpdateContent && ($newsletter->isDraft() || $newsletter->isUnderReview()) &&
                (count($attachments)<5)) {
        ?>
            <div class="form-group">
                <strong><?= gettext("Upload new .ics file");?> &emsp;<a aria-label="<?= gettext("Add attachment");?>" class="add_button" href="javascript:void(0)"><i tabindex="0" class="fa fa-plus-circle" aria-hidden="true"></i></a></strong>
                <div class="field_wrapper">
                </div>
            </div>
        <?php } ?>
            <div class="form-group" id="submit_form" style="display:none;">
                <div class="col-md-12 text-center">
                    <button type="button" onclick="upoadNewsLetterAttachments('<?=$encGroupId;?>','<?=$encNewsletterId;?>');" class="about-button"><?= gettext("Upload");?></button>
                </div>
            </div>
        </form>
    </div>
    <script type="text/javascript">
        $(document).ready(function(){
            var maxField = <?= (6-count($attachments))?>; //Input fields increment limitation
            var addButton = $('.add_button'); //Add button selector
            var wrapper = $('.field_wrapper'); //Input field wrapper
            var fieldHTML = '<div class="col-md-12"><div class="col-md-6"> <?= gettext("File Title");?><br><input type="text" name="title[]" class="form-control" placeholder="<?= gettext("Title");?>" required></div><div class="col-md-6"> <?= gettext("Attachment");?><br><input type="file" name="attachment[]" accept=".ics"  class="form-control" style="padding: 3px;" required></div></div>'; //New input field html
            var x = 1; //Initial field counter is 1
            $(addButton).click(function(){ //Once add button is clicked
                $(".add_button").hide();                
                if(x < maxField){ //Check maximum number of input fields
                    x++; //Increment field counter
                    $(wrapper).append(fieldHTML); // Add field html
                    $("#submit_form").show();
                }
            });
            $(wrapper).on('click', '.remove_button', function(e){ 
                e.preventDefault();
                $(this).parent('div').remove(); //Remove field html
                x--; //Decrement field counter
                if(x == 1){
                    $("#submit_form").hide();
                }
                
            });
        });
</script>
    
