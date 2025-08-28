<div class="col-md-12  p-0"> 
    <div class="mt-3 mb-5">
        <div class="col-4">&nbsp;</div>
        <div class="col-4 p-0"> 
            <select aria-label="<?= sprintf(gettext("Select a %s"), $_COMPANY->getAppCustomization()['channel']["name-short"])?>" class="form-control" name="channel_filter" id="channel_filter" onchange="mangeChannelLeads('<?= $encGroupId;?>')" style="font-size:small;border-radius: 5px;">
                <?php if ($_USER->canManageGroup($groupid) || empty($filterChannelId)) {?>
                <option  value="<?= $_COMPANY->encodeId(0);?>" onchange="mangeChannelLeads('<?= $encGroupId;?>')"><?= sprintf(gettext("Select a %s"), $_COMPANY->getAppCustomization()['channel']["name-short"]);?></option>
                <?php } ?>
                <?php if ($channels) { ?>
				    <?php foreach ($channels as $channel) { ?>
                    <?php if ($_USER->canManageGroupChannel($groupid,$channel['channelid'])){ ?>
                <option  <?=  $channel['channelid'] == $filterChannelId ? 'selected' : ''; ?> value="<?= $_COMPANY->encodeId($channel['channelid'])?>"><?= htmlspecialchars($channel['channelname']); ?></option>
                    <?php } ?>
                    <?php } ?>
                <?php } ?>
            </select>
        </div>
        <div class=" no-padding col-4 text-right p-0">
    <?php if ($_COMPANY->getAppCustomization()['group']['manage']['allow_update_channelleads'] && $_USER->canManageGrantSomeChannel($groupid)) { ?>
            <button onclick="openChannelLeadRole('<?=$encGroupId;?>','<?=$_COMPANY->encodeId(0)?>','<?=$_COMPANY->encodeId(0)?>')" class="btn btn-primary btn-sm"><?= sprintf(gettext("Add %s Leader"),$_COMPANY->getAppCustomization()['channel']['name-short']);?></button>
    <?php } else { ?>
            <button class="btn btn-primary btn-sm" disabled><?= sprintf(gettext("Add %s Leader"),$_COMPANY->getAppCustomization()['channel']['name-short']);?></button>
    <?php } ?>
    </div>
    <div class="clearfix"></div>
    <div class="table-responsive mt-4" id="channel-list-view">
        <table id="channel-lead-table" class="table table-hover display compact"  width="100%" summary="<?= sprintf(gettext('This table display the list of leaders of a %s'), $_COMPANY->getAppCustomization()['channel']['name-short']);?>">
            <thead>
                <tr>
                    <th width="25%" class="color-black" scope="col"><?= gettext("Name");?></th>
                    <th width="17%" class="color-black" scope="col"><?=$_COMPANY->getAppCustomization()['channel']['name-short-plural']?></th>
                    <th width="20%" class="color-black" scope="col"><?= gettext("Role");?></th>
                    <th width="20%" class="color-black" scope="col"><?= gettext("Role Title");?></th>
                    <th width="17%" class="color-black" scope="col"><?= gettext("Permissions");?></th>
                    <th width="10%" class="color-black" scope="col"><?= gettext("Since");?></th>
                    <th width="5%" class="color-black" scope="col"></th>
                    
                </tr>
            </thead>
            <tbody>
            <!-- Channel Leads -->
                <?php	if(count($channelLeads)>0){ ?>
                    <?php	for($c=0;$c<count($channelLeads);$c++){ ?>
                    <?php
                        $cName = '-';
                        $validated = false;

                        if(!empty($channels)) {
                            foreach ($channels as $channel) { 
                                if ($channel['channelid'] == $channelLeads[$c]['channelid']) {
                                    if ($_USER->canManageGroupChannel($groupid, $channel['channelid']))
                                        $validated = true;
                                    $cName = '<li>'.htmlspecialchars($channel['channelname']).'</li>';
                                    break;
                                }
                            }
                        }

                        if (!$validated)
                            continue;

                        $encodedIdC = $_COMPANY->encodeId($channelLeads[$c]['leadid']);
                    ?>
                        <tr id="<?= $encodedIdC ?>">
                            <td>
                                <?php
                                $channelLeaderImgAlt = sprintf(gettext("%s leader profile picture"),$_COMPANY->getAppCustomization()['channel']['name-short']);

                                echo User::BuildProfilePictureImgTag($channelLeads[$c]['firstname'],$channelLeads[$c]['lastname'], $channelLeads[$c]['picture'],'memberpicture_small', $channelLeaderImgAlt, $channelLeads[$c]['userid'], 'profile_basic'); ?>
                            <strong>					
                            <?php
                            echo rtrim($channelLeads[$c]['firstname']." ".$channelLeads[$c]['lastname']," "); 
                            ?>
                            </strong>

                            <?= $channelLeads[$c]['jobtitle'] ? '<br/>'.$channelLeads[$c]['jobtitle']:'' ?>
                            <br>
                            <?= User::PickEmailForDisplay($channelLeads[$c]['email'], $channelLeads[$c]['external_email'], false)?>
                            </td>
                            <td><?= $cName;?> </td>
                            <td> <?= $channelLeads[$c]['rolename'] ?  htmlspecialchars($channelLeads[$c]['rolename']).'<br/>('.$systemLeadType[$types[$channelLeads[$c]['grouplead_typeid']]['sys_leadtype']].')' : '-- '.gettext('Not Defined').' --' ?></td>
                            <td><?= htmlspecialchars($channelLeads[$c]['roletitle']) ?: '-'; ?> </td>
                            <td> <?= !empty($channelLeads[$c]['permissions']) ?  '&#8226; '.implode('<br>&#8226; ',$channelLeads[$c]['permissions']): '--' ?> </td>
                            <td>
                                <span style="display:none;"><?= $channelLeads[$c]['assigneddate']; ?></span>
                                <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($channelLeads[$c]['assigneddate'],true,true,false); ?>
                            </td>

                            <?php if ($_COMPANY->getAppCustomization()['group']['manage']['allow_update_channelleads'] && $_USER->canManageGrantGroupChannel($groupid, $channelLeads[$c]['channelid'])) { ?>
                            <td>
                            <a aria-expanded="false" id="lead_<?=$_COMPANY->encodeId($channelLeads[$c]['leadid'])?>" role="button" tabindex="0" class="dropdown-toggle  fa fa-ellipsis-v col-doutd three-dot-action-btn" data-toggle="dropdown" aria-label="Action dropdown"></a>
                            <ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton" style="width: 200px; cursor: pointer;">

                                <li><a role="button" aria-label="<?= gettext('Edit');?>" href="javascript:void(0)" class="" onclick="openChannelLeadRole('<?=$encGroupId;?>','<?=$_COMPANY->encodeId($channelLeads[$c]['channelid'])?>','<?=$_COMPANY->encodeId($channelLeads[$c]['leadid'])?>')"><i class="fa fas fa-edit" title="Edit" aria-hidden="true"></i>&emsp;<?= gettext('Edit'); ?></a></li>
                              
                                <li> <a role="button" data-toggle="popover" aria-label="<?= gettext('Delete');?>" href="javascript:void(0)" class="deluser"  onclick="deleteChannelLeadRole('c<?=$c+1?>','<?=$encGroupId;?>','<?=$_COMPANY->encodeId($channelLeads[$c]['channelid'])?>','<?=$_COMPANY->encodeId($channelLeads[$c]['leadid']);?>')" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= sprintf(gettext("Are you sure you want to remove this %s leader?"),$_COMPANY->getAppCustomization()['channel']['name-short']);?>"><i class="fa fa-trash" title="Delete" aria-hidden="true"></i>&emsp;<?= gettext('Delete'); ?></a></li>
                                </ul>
                            </td>
                            <?php } else { ?>
                            <td>
                            <a aria-expanded="false" id="lead_<?=$_COMPANY->encodeId($channelLeads[$c]['leadid'])?>" role="button" tabindex="0" class="dropdown-toggle  fa fa-ellipsis-v col-doutd three-dot-action-btn" data-toggle="dropdown" aria-label="Action dropdown"></a>
                            <ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton" style="width: 200px; cursor: pointer;">

                            <li style="color:lightgrey;"><i class="fa fas fa-edit" style="color:lightgrey; padding-left:6px; padding-right: 6px;" title="<?= gettext("Insufficient permissions for Edit");?>" aria-hidden="true"></i> <?= gettext('Edit'); ?></li>           
                            <li style="color:lightgrey;"><i class="fa fa-trash" style="color:lightgrey; padding-left:6px; padding-right: 6px;" title="<?= gettext("Insufficient permissions for Delete");?>" aria-hidden="true"></i> <?= gettext('Delete'); ?></li></ul>
                            </td>
                            <?php } ?>
                    </tr>
                    <?php		} ?>
                    <?php 	} ?>
            </tbody>
        </table>
    </div>
</div>
<script>
$(document).ready(function() {
    // Channel Lead Table
    var x = parseInt(localStorage.getItem("local_variable_for_table_pagination"));
    var dtable = $('#channel-lead-table').DataTable({
        "order": [],
        "bPaginate": true,
        "bInfo" : false,
        //bLengthChange: true,
       // pageLength:x,
        "pageLength": 200, // Set the number of entries per page to 200
        "lengthChange": false, // Hide the entries per page dropdown
        language: {
            "sZeroRecords": "<?= gettext('No data available in table');?>",
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },
        columnDefs: [
                    { targets: [-1], orderable: false }
                ],

    });    
    screenReadingTableFilterNotification('#channel-lead-table',dtable);

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

    <?php if ($_USER->canManageContentInScopeCSV($groupid,0,0) && $filterChannelId>0 && $_COMPANY->getAppCustomization()['group']['manage']['allow_update_channelleads']) { ?>
        //Helper function to keep table row from collapsing when being sorted
        var fixHelperModified = function(e, tr) {
            var $originals = tr.children();
            var $helper = tr.clone();
            $helper.children().each(function(index)
            {
                $(this).width($originals.eq(index).width())
            });
            return $helper;
        };
        //Make diagnosis table sortable
        $("#channel-lead-table tbody").sortable({
            helper: fixHelperModified,
            stop: function(event,ui) {renumber_table('#channel-lead-table')}
        }).disableSelection();
        <?php } ?>
    
    });

 //Renumber table rows
 function renumber_table(tableID) {
        var ids = [];
        $(tableID + " tr").each(function(index, tr) {
            var id = $(this).closest('tr').attr('id');
            if (id){
                ids.push(id);
            }
        });

        updateChannelleadPriorityFrontEnd(ids,'<?=$encGroupId?>','<?= $_COMPANY->encodeId($filterChannelId)?>');
    }

    $('#channel-lead-table').on( 'length.dt', function ( e, settings, len ) {
    localStorage.setItem("local_variable_for_table_pagination", len);
} );

//On Enter Key...
$(function(){ 
    $(".three-dot-action-btn").keypress(function (e) {
        if (e.keyCode == 13) {
            $(this).trigger("click");
        }
    });
});
retainPopoverLastFocus(); //When Cancel the popover then retain the last focus.
</script>