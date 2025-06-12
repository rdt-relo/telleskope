<style>
    /* Styling for the copyable div */
    .copyable {
        border: 1px dashed #333; /* Dashed border, do not change */
        padding: 8px; /* Padding for content,  do not change */
        margin: 15px; /* Margin for spacing, do not change = 800 - (2 x border + 2 x padding + 2 x margin) = 750 */
        cursor: pointer; /* Change cursor to pointer on hover */
        /* Optional: Add some background color or other styles */
        background-color: #f8f8f8;
    }
</style>
<div class="modal" id="touch_point_copy_detail_model" tabindex="-1">
  <div aria-label="<?= gettext("Copy Touch-point Detail");?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <button type="button" id="copy_element_button" onclick="copyElementToClipboard('innerhtml', <?=$touchpoint->val('isactive') == 1 ? 1 : 0?>)" class="btn btn-affinity"><?= gettext("Copy Touch Point Detail");?></button>
        <button id="btn_close" type="button"  class="close text-right ml-3" data-dismiss="modal" aria-label="<?= gettext('Close'); ?>">
					 <span aria-hidden="true" style="font-size: 25px;">&times;</span>
		</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body copyable">
        <div id="innerhtml">
            <style>
                .link-line {
                    background-color: #FFFFAA !important;
                    padding: 5px !important;
                    font-size: 14px !important;
                    line-height: 1.6em;
                }
                .email-list {
                    width: 750px;
                    margin-left: auto !important;
                    margin-right: auto !important;
                    font-size: 14px !important;
                }
                .email-list-items {
                    line-height: 1.2em!important;
                    font-family: monospace;
                }
                .content-heading {
                    font-size: 20px !important;
                    font-weight: bold;
                    margin-top: 15px !important;
                    marging-bottom: 15px !important;
                }
                .content {
                    font-size: 16px !important;
                    line-height: 1.6em;
                }
                .content tr {
                    width: 100% !important;
                }
                .content table {
                    width: 100% !important;
                }
                .content th,
                .content td {
                    padding: 10px !important;
                }
                .content ul,
                .content ol {
                    padding-left: 10px !important;
                    margin-left: 10px !important;
                    padding-bottom: 5px !important;
                    padding-top: 5px !important;
                    margin-top: 10px !important;
                    margin-bottom: 10px !important;
                }
                .content li {
                    line-height: 1.7em !important;
                    padding-left: 20px;
                }

                .content p {
                    margin-top: 15px !important;
                    margin-bottom: 15px !important;
                }
                .content img {
                    max-width: 730px !important; /* do not change; 750 - 2 x td  = 730 */
                }
                #content-inner-2024-wasqwa > table > tbody > tr > td {
                    border: 1px #F0F0F0 solid;
                }
                .content figure {
                    max-width: 100% !important;
                }
                .content hr {
                    max-width: 100% !important;
                }
            </style>
            <div class="email-list">
                <p>
                    <b><?= gettext("Participant Emails");?>:</b>
                </p>
                <p class="email-list-items">
                <?php if(!empty($teamMembers)){ ?>
                    <?php usort($teamMembers, function($a,$b) { return strcmp($b['roletitle'], $a['roletitle']); }); ?>
                    <?php $emails = array_column($teamMembers, 'email') ?>
                    <?= implode('<br>', $emails); ?>
                <?php }  ?>
                </p>
            </div>
            <br>
            <table style="width:750px !important;border: none!important;margin-left: auto !important;margin-right: auto !important;">
                <tr>
                    <td>
                        <p class="content-heading">
                            <?= htmlspecialchars($touchpoint->val('tasktitle')); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="content" id="content-inner-2024-wasqwa">
                            <?= $touchpoint->val('description'); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="background-color: #FFFFAA !important;">
                            <p class="link-line">
                                <?= gettext("When the meeting is complete, please click this")?> <a role="button" href="<?= $markCompleteUrl;?>" aria-label="Click this link to mark the touchpoint as done"><?= gettext("link");?></a> <?= gettext("to mark the touch point as done");?>
                            </p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
            <button type="button" class="btn btn-affinity" data-dismiss="modal"><?= gettext("Close")?></button>
      </div>

    </div>
  </div>
</div>
<script>
$('#touch_point_copy_detail_model').on('shown.bs.modal', function () {
   $('#copy_element_button').trigger('focus')
});

</script>
<script>
    function copyElementToClipboard(elementId, show_next_steps) {
	    // Get the element to be copied
        const element = document.getElementById(elementId);

        // Check if element exists
        if (!element) {
            console.error("Element with ID", elementId, "not found");
            return;
        }

        // Create a temporary element to hold the content
        const tempElement = document.createElement("div");
        tempElement.appendChild(element.cloneNode(true)); // Clone element with formatting

        // Make the temporary element invisible
        tempElement.style.position = "absolute";
        tempElement.style.left = "-9999px";
        document.body.appendChild(tempElement);

        // Select the temporary element
        const selection = window.getSelection();
        const range = document.createRange();
        range.selectNodeContents(tempElement);
        selection.removeAllRanges();
        selection.addRange(range);

        let next_steps = show_next_steps
                            ?   `<div class="text-left">
                                    <p><b><?= gettext('Next Steps:')?></b></p>
                                    <ol style="padding: 10px 0 10px 25px;">
                                        <li><?= gettext('Create the event in your Outlook calendar.')?></li>
                                        <li><?= gettext('Update the Touch Point status to "In Progress".')?></li>
                                    </ol>
                                </div>`
                            : '';
        // Copy the selection to the clipboard
        try {
            document.execCommand("copy");
            swal.fire({
                    html: `<div class="text-left">
                            <p><?= gettext('Touch Point details and participant emails have been copied to your clipboard.')?></p>
                            <br>
                          </div>` + next_steps,
                    title: '<?= gettext('Success')?>'
                });
                $('.swal2-confirm').focus();
        } catch (err) {
            swal.fire({
                    text: "An error occurred while copying details. Please try again.",
                    title: 'Error!'
                });
        }
        // Cleanup temporary element
        document.body.removeChild(tempElement);
        selection.removeAllRanges();
    }
</script>