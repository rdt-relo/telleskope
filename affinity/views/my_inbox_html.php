<main>
    <div class="container w2 overlay"
        style="background: url(<?= $_ZONE->val('banner_background') ?: 'img/img.png'?>) no-repeat; background-size:cover; background-position:center;">
        <div class="col-md-12">
            <h1 class="ll icon-pic-custom" >
                <?= gettext("My Inbox"); ?>
            </h1>
        </div>
    </div>
    <div class="container inner-background">
        <div class="row">
            <div class="col-12 mt-4">
                <h4><?= gettext("Manage Inbox Messages")?></h4>
                <button id="configureModalPopup" class="btn btn-sm btn-affinity pull-right" data-toggle="modal" data-target="#configureModal"><?= gettext("Configure") ?></button>
                <hr class="lineb" >
            </div>

            <div class="col-md-12">
                <div class="table-responsive " id="list-view">
                    <table id="myInboxData" class="table table-hover display compact" style="width: 100%;">
                        <thead>
                            <tr>
                                <th width="5%"><?= gettext("Select");?></th>
                                <th width="25%"><?= gettext("From") ?></th>
                                <th width="50%"><?= gettext("Subject");?></th>
                                <th width="15%"><?= gettext("Date");?></th>
                                <th width="5%"><?= gettext("View");?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach($messages as $message){
                                $style = empty($message['readon']) ? 'font-weight: bold;' : '';
                            ?>
                            <tr id="<?= $message['messageid']?>" style="<?=$style?>">
                                <td><input type="checkbox" name="message_ids[]" value="<?= $_COMPANY->encodeId($message['messageid'])?>" onchange="updateBulkActionButtonsForInbox()" data-messageid="<?= $_COMPANY->encodeId($message['messageid'])?>"></td>
                                <td><?= $message['from_name'] ?></td>
                                <td><?= $message['message_subject'] ?></td>
                                <td><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($message['createdon'], true,false, false)?></td>
                                <td><button class="btn btn-sm btn-affinity" onclick="readInboxMessage('<?= $_COMPANY->encodeId($message['messageid'])?>')"><?= gettext('Open') ?></button></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <!-- Bulk action -->
                    <div id="bulk-action-links" class="bulk-action-links" style="font-size: 14px; display: none;" >
                        <button class="btn btn-sm btn-affinity" onclick="performBulkAction('mark_as_read')"><?= gettext("Mark as Read") ?></button>
                        <button class="btn btn-sm btn-affinity" onclick="performBulkAction('delete')"><?= gettext("Delete") ?></button>
                    </div>
                </div>
            </div>
            <div class="col-md-12 my-3">
                <p><small><?=  gettext("Note: Messages will be deleted after 30 days")?></small></p>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap Modal -->
<div class="modal fade" id="configureModal" tabindex="-1" role="dialog" aria-labelledby="configureModalLabel" aria-hidden="true">
    <div aria-label="<?= gettext('Configure Inbox')?>" class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="configureModalLabel">Configure Inbox</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <label for="externalEmail">Email address for inbox notifications</label>
                <br>
                <small style="color: darkred;">Note: Once an external email address is set, it cannot be modified by the user. To make changes, please submit a support ticket.</small>
                <input type="email" id="externalEmail" class="form-control mt-2" value="<?= $_USER->val('external_email') ?>" <?= empty($_USER->val('external_email')) ? '' :'disabled'?>>
                <button type="button" class="btn btn-primary mt-3"  <?= empty($_USER->val('external_email')) ? 'onclick="configureInbox()"' :'disabled'?>>Submit</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#myInboxData').DataTable( {
            "order": [],
			"bPaginate": true,
			"bInfo" : false,
            'language': {
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },	
            'aoColumnDefs': [{
                'bSortable': false,
                'aTargets': [0,4]
             }],
                
        });
    });
    function configureInbox() {
        var external_email = $('#externalEmail').val();

        // Validate email using regex
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (external_email.trim().length == 0 && !emailRegex.test(external_email)) {
            swal.fire({
                    title: 'Error!',
                    text: 'Please enter a valid email address.',
                });
            return;
        }

        $.ajax({
            url: 'ajax.php?configureInbox',
            type: "POST",
            data: {'external_email': external_email},
            success: function (data) {
                let jsonData = JSON.parse(data);
                
				swal.fire({title:jsonData.title,text:jsonData.message}).then(function(result) {
                    $('#configureModal').modal('hide');
				});      
                $(".swal2-confirm").focus();                 
            }
        });
    }

    function readInboxMessage(id){
        $.ajax({
		url: 'ajax.php?readInboxMessage',
		type: "GET",
		data: {'messageid':id},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$("#loadAnyModal").html(data);
				$('#readMessageModal').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
        });
    }

    function performBulkAction(action) {
    var selectedCheckboxes = document.querySelectorAll('input[name="message_ids[]"]:checked');
    var messageIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.getAttribute('data-messageid'));

    if (messageIds.length === 0) {
        swal.fire({title:"Error", text:"Please select at least one message to perform the action."});
        return;
    }
    $.ajax({
		url: 'ajax.php?performBulkAction',
		type: "POST",
		data: { 'action': action,'messageIds': messageIds},
        dataType: 'json',
		success : function(data) {
        swal.fire({title:data.title,text:data.message}).then(function(result) {
                location.reload();
            });
		}
        });
    }

    function updateBulkActionButtonsForInbox() {
        let selectedCheckboxes = document.querySelectorAll('input[name="message_ids[]"]:checked');
        if (selectedCheckboxes.length) {
            $("#bulk-action-links").show();
        } else {
            $("#bulk-action-links").hide();
        }
    }

    $('#configureModal').on('shown.bs.modal', function () {   
    setTimeout(function(){
      $('.close').trigger('focus');
    },100);  
})
</script>
</body>
</html>
