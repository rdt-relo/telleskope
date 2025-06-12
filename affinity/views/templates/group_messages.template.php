<div class="col-md-12">
    <div class="row">

        <div class="col-12">
            <h2><?= gettext("Messages").' - '. $group->val('groupname_short');?></h2>
            <hr class="lineb" >
        </div>
     <?php
    //  Disclaimer for DM
    $isGlobal = !$groupid ? 1 : 0;
    if(Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['DIRECT_MESSAGE_CREATE_BEFORE'])){
        $call_method_parameters = array(
            $_COMPANY->encodeId($groupid),
            $_COMPANY->encodeId(0),
            $isGlobal,
        );

        $call_other_method = base64_url_encode(json_encode(
            array (
                "method" => "groupMessageForm",
                "parameters" => $call_method_parameters
            )
        ));
        $reloadOnClose = 0;
        $onClickFunc = 'loadDisclaimerByHook(\'' . $_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['DIRECT_MESSAGE_CREATE_BEFORE']) . '\',\'' . $_COMPANY->encodeId($groupid) . '\',\'' . $reloadOnClose . '\',\'' . $call_other_method . '\')';
    }else{
        $onClickFunc = 'groupMessageForm(\'' . $_COMPANY->encodeId($groupid) . '\',\'' . $_COMPANY->encodeId(0) . '\',\'' . $isGlobal . '\')';
    }

        $newbtn= '<button class="btn btn-affinity btn-sm mb-3" type="button" onclick="'.$onClickFunc.'">'.gettext("New Message").'</button>';
        include(__DIR__ . "/manage_section_dynamic_button.html");
    ?>

    <div class="col-md-12 text-right">
    <?php
        $page_tags = 'manage_message';
        ViewHelper::ShowTrainingVideoButton($page_tags);
    ?>
    </div>

        <div class="col-md-12">
            <div class="table-responsive " id="list-view">
                <table id="messageTable" class="table table-hover display compact" summary="Company messages list">
                    <thead>
                        <tr>
                            <th width="28%" scope="col"><?= gettext("Subject");?></th>
                            <th width="40%" scope="col"><?= gettext("To");?></th>
                            <th width="15%" scope="col"><?= gettext("Sent By");?></th>
                            <th width="15%" scope="col"><?= gettext("Date");?></th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>

                <?php $i= 0;
                    $recipients_base = array(
                        1 => gettext("All Users of Zone"),
                        2 => gettext("Non-Group Members"),
                        3 => gettext('Group Members'),
                        4 => gettext("Dynamic Lists")
                    );
                    foreach($rows as $row){ ?>

                        <?php
                        if (strpos($row['groupids'],',') === false && !$_USER->canManageContentInScopeCSV((int)$row['groupids'],$row['chapterids'],$row['channelids'])) {
                            continue;
                        }
                        ?>

                        <tr id="<?=$i+1;?>" >
                            <td><?= htmlspecialchars($row['subject']); ?>
                            <?= $row['isactive'] == 2 ? '<small style="color:red"> ['. gettext("draft").']</small>' :
                            ($row['isactive'] == 3 ? '<small style="color:green"> ['. gettext("Sent for Review").']</small>' :
                            ($row['isactive'] == 5 ? '<small style="color:green"> ['. gettext("Scheduled").' '.
                                $_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['publishdate'], true, true, true)
                                .']</small>' : '')) ?></td>
                            <td>
                            <?php if($row['is_admin'] == 1){ ?>
                                <strong><?= gettext("Recipients Base");?> :</strong> <?= $recipients_base[$row['recipients_base']]; ?><br/>
                            <?php } ?>
                            <?php if($row['recipients_base'] == 3){ ?>
                                <?php if($row['is_admin']) { ?>
                                <strong><?= gettext("Groups");?>:</strong> <?= $row['groupname']; ?><br>
                                <?php } ?>

                                <?php
                                if($row['chapterids'] != ''){
                                    $chapter_arr = explode(',', $row['chapterids']);
                                    if (in_array(0, $chapter_arr)) {
                                        $labelForChapterNotAssigned = sprintf(gettext('%s not assigned'),$_COMPANY->getAppCustomization()['chapter']['name']);
                                        $row['chaptername'] = empty($row['chaptername']) ? $labelForChapterNotAssigned : $row['chaptername'].', '.$labelForChapterNotAssigned;
                                    }
                                ?>
                                    <strong><?= $_COMPANY->getAppCustomization()['chapter']["name-plural"]?>:</strong> <?= htmlspecialchars($row['chaptername']); ?><br>
                                <?php } ?>

                                <?php
                                if($row['channelids'] != ''){
                                    $channel_arr = explode(',', $row['channelids']);
                                    if (in_array(0, $channel_arr)) {
                                        $labelForChannelNotAssigned = sprintf(gettext('%s not assigned'),$_COMPANY->getAppCustomization()['channel']['name']);
                                        $row['channelname'] = empty($row['channelname']) ? $labelForChannelNotAssigned : htmlspecialchars($row['channelname']).', '.$labelForChannelNotAssigned;
                                    }
                                ?>

                                    <strong><?= $_COMPANY->getAppCustomization()['channel']["name-plural"]?>:</strong> <?= htmlspecialchars($row['channelname']); ?><br>
                                <?php } ?>

                                <strong><?= gettext("Sent To");?>:</strong>
                                    <?php
                                    $types = array(
                                        0 => "Other",
                                        1 => sprintf(gettext("%s Leaders"),$_COMPANY->getAppCustomization()['group']['name-short']),
                                        3 => sprintf(gettext("%s Leaders"),$_COMPANY->getAppCustomization()['chapter']["name-short"]),
                                        4 => sprintf(gettext("%s Leaders"),$_COMPANY->getAppCustomization()['channel']["name-short"]),
                                        2 => sprintf(gettext("%s Members"),$_COMPANY->getAppCustomization()['group']['name-short']),
                                        5 => sprintf(gettext('%1$s Members'), $_COMPANY->getAppCustomization()['teams']['name']),
                                    );
                                    $values = implode(",",array_values(array_intersect_key($types, array_flip(explode(',',$row['sent_to'])))));
                                    echo $values;
                                    ?>
                                <br>

                                <?php
                                $roleTypes = $row['team_roleids'] ? Team::GetTeamRoleTypesByIds($groupid,$row['team_roleids']) : [];
                                $roleNames = implode(',',array_column( $roleTypes,'type'));

                                if( $roleNames){
                                ?>
                                    <strong><?= sprintf(gettext("%s Roles"),$_COMPANY->getAppCustomization()['teams']['name']) ;?>:</strong> <?= $roleNames; ?><br>
                                <?php } ?>
                            <?php } else if ($row['recipients_base'] == 4){ 
                                    $listids = explode(',',$row['listids']);
                                    $listname = array();
                                    foreach($listids as $listid){
                                        $l = DynamicList::GetList($listid);
                                        
                                        if ($l){
                                            $listname[] = $l->val('list_name');
                                        }
                                    }
                                    $listsNameCsv = implode(', ',  $listname);
                                ?>

                                <strong><?= gettext("Dynamic Lists");?>:</strong> <?= $listsNameCsv; ?><br>

                            <?php } ?>
                                <strong>#<?= gettext("Recipients");?>:</strong> <?= $row['total_recipients']; ?>
                            </td>
                            <td><?= $row['sent_by']; ?></td>
                            <td>
                                <span style="display: none;"><?= $row['createdon']; ?></span>
                                <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['createdon'],true,true,true);?>
                            </td>
                            <td>
                                <?php
                                    include(__DIR__ . '/group_message_action_button.template.php');
                                ?>
                            </td>
                        </tr>

                <?php $i++;	} ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div id="viewMessageModal" tabindex="-1" class="modal fade"  data-keyboard="false" data-backdrop="static">
    <div aria-label="<?= gettext("Message View");?>" class="modal-dialog" style="max-width: 960px;" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title" class="modal-title"><?= gettext("Message View");?></h2>
            <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
                
            </div>
            <div class="modal-body">
                <div class="p-3">
                    <div id="viewMessage"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?= gettext("Close");?></button>
            </div>
        </div>
    </div>
</div>

<script>
	$(document).ready(function() {
  var table = $('#messageTable').DataTable({
    "order": [[3, "desc"]],
    "bPaginate": true,
    "bInfo": false,
    bLengthChange: true,
    "drawCallback": function() {
        setAriaLabelForTablePagination(); 
    },
    "initComplete": function(settings, json) {                            
        setAriaLabelForTablePagination(); 
        $('.current').attr("aria-current","true");  
    },
    language: {
        "sZeroRecords": "<?= gettext('No data available in table');?>",
      url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
    },
    columnDefs: [
      { targets: [4], orderable: false }
    ]
  });

  $('.initial').initial({
    charCount: 2,
    textColor: '#ffffff',
    color: window.tskp?.initial_bgcolor ?? null,
    seed: 0,
    height: 30,
    width: 30,
    fontSize: 15,
    fontWeight: 300,
    fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
    radius: 0
  });

  // function for Accessiblity screen reading.
  screenReadingTableFilterNotification('#messageTable',table);
});

$('#viewMessageModal').on('shown.bs.modal', function() {
  $('.close').focus();
});

</script>
