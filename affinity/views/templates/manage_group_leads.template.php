
<div class="col-md-12 p-0">
       
    <div class="lead-button col-md-12 text-right pr-0">
    <?php 
        if ($_COMPANY->getAppCustomization()['group']['manage']['allow_update_groupleads'] && $_USER->canManageGrantGroup($groupid)) { ?>
        <button onclick="groupLeadRoleModal('<?=$encGroupId;?>','<?=$_COMPANY->encodeId(0)?>')" class="btn btn-primary btn-sm"><?= sprintf(gettext("Add %s Leader"),$_COMPANY->getAppCustomization()['group']['name-short']);?></button>
        <?php } else { ?>
            <button class="btn btn-primary btn-sm" disabled><?= sprintf(gettext("Add %s Leader"),$_COMPANY->getAppCustomization()['group']['name-short']);?></button>
        <?php
        }
    
    ?>
    
    </div>
    
    <div class="clearfix"></div>
    <div class="table-responsive" id="list-view">
        <table id="table_Leads" class="table table-hover display mb-5 compact" summary="This table displays the list of group leaders" width="100%">
            <thead>
                <tr>                   
                    <th width="30%" class="color-black" scope="col"><?= gettext("Name");?></th>
                    <th width="28%" class="color-black" scope="col"><?= gettext("Role");?></th>
                    <th width="28%" class="color-black" scope="col"><?= gettext("Role Title");?></th>
                    <th width="20%" class="color-black" scope="col"><?= gettext("Permissions");?></th>
                    <th width="12%" class="color-black" scope="col"><?= gettext("Since");?></th>
                    <th width="5%" class="color-black" scope="col"> </th>
                    
                   
                </tr>
            </thead>
            <tbody>
            <?php	if(count($leads)>0){ ?>
                <?php	for($i=0;$i<count($leads);$i++){
                    $encodedId = $_COMPANY->encodeId($leads[$i]['leadid']);
                    ?>
                    <tr id="<?=$encodedId?>">
                    <td>
                           <?= User::BuildProfilePictureImgTag($leads[$i]['firstname'], $leads[$i]['lastname'], $leads[$i]['picture'],'memberpicture_small','Group lead profile picture', $leads[$i]['userid'], 'profile_basic');?>
                       
                        
                        <strong>
                            <?php 
                                echo rtrim($leads[$i]['firstname']." ".$leads[$i]['lastname']," ");
                            ?>
                         </strong> 
                       <span class="pt-2 pl-20">
                        <?=  $leads[$i]['jobtitle'] ? '<br/>'.$leads[$i]['jobtitle']: '' ?>
                        <br/>
                        <?= User::PickEmailForDisplay($leads[$i]['email'], $leads[$i]['external_email'], false)?>
                        </span>
                        </td>
                        <td> 
                            <?= $leads[$i]['rolename'] ?  $leads[$i]['rolename'].'<br/>('.$systemLeadType[$types[$leads[$i]['grouplead_typeid']]['sys_leadtype']].')' : '-- '.gettext('Not Defined').' --'?>
                        </td>

                        <td> 
                            <?= htmlspecialchars($leads[$i]['roletitle'])?:'-';?>
                        </td>

                        <td> <?= !empty($leads[$i]['permissions']) ?  '&#8226; '.implode('<br>&#8226; ',$leads[$i]['permissions']): '--' ?> </td>
                        <td>
                            <span style="display:none;"><?= $leads[$i]['assigneddate']; ?></span>
                            <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($leads[$i]['assigneddate'],true,true,false); ?> </td>
                                                
                            <td>
                            <a aria-expanded="false" id="lead_<?=$_COMPANY->encodeId($leads[$i]['leadid'])?>" role="button" tabindex="0" class="dropdown-toggle  fa fa-ellipsis-v col-doutd three-dot-action-btn" data-toggle="dropdown" aria-label="Action dropdown"></a>
                            <ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton" style="width: 200px; cursor: pointer;">
                           
                            <?php
                                if ($_COMPANY->getAppCustomization()['group']['manage']['allow_update_groupleads'] && $_USER->canManageGrantGroup($groupid)) { ?>
                                
                                <li><a id="grouplead_<?=$_COMPANY->encodeId($leads[$i]['leadid']);?>" role="button" aria-label="<?= gettext('Edit');?>" href="javascript:void(0)" class="" title="Edit" onclick="groupLeadRoleModal('<?=$encGroupId;?>','<?=$_COMPANY->encodeId($leads[$i]['leadid']);?>')"><i class="fa fas fa-edit" title="Edit" aria-hidden="true"></i>&emsp;<?= gettext('Edit'); ?></a></li>
                                <li><a role="button" data-toggle="popover" aria-label="<?= gettext('Delete');?>" href="javascript:void(0)" class="deluser"  onclick="deleteGroupLeadRole('<?=$encodedId?>','<?=$encGroupId;?>','<?=$_COMPANY->encodeId($leads[$i]['leadid']);?>')" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to remove this Group Leader?");?>"><i class="fa fa-trash" title="Delete" aria-hidden="true"></i>&emsp;<?= gettext('Delete'); ?></a></li>

                            <?php } else { ?>                              

                                <li style="color:lightgrey;"><i class="fa fas fa-edit" style="color:lightgrey; padding-left:6px; padding-right: 6px;" title="<?= gettext("Insufficient permissions for Edit");?>" aria-hidden="true"></i> <?= gettext('Edit'); ?></li>           
                                <li style="color:lightgrey;"><i class="fa fa-trash" style="color:lightgrey; padding-left:6px; padding-right: 6px;" title="<?= gettext("Insufficient permissions for Delete");?>" aria-hidden="true"></i> <?= gettext('Delete'); ?></li>
                            <?php 
                            }                            
                        
                        ?>
                         </ul>

                        </td>
                </tr>
                <?php		} ?>
                <?php 	} ?>
        
            </tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function() {   
        var x = parseInt(localStorage.getItem("local_variable_for_table_pagination"));
       var dt = $('#table_Leads').DataTable({
            "order": [],
            "bPaginate":true,
            "bInfo" : false,
           // bLengthChange: true,
           //pageLength:x,
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

        screenReadingTableFilterNotification('#table_Leads',dt);

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

        <?php if ($_USER->canManageContentInScopeCSV($groupid,0,0) && $_COMPANY->getAppCustomization()['group']['manage']['allow_update_groupleads']) { ?>
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
        $("#table_Leads tbody").sortable({
            helper: fixHelperModified,
            stop: function(event,ui) {renumber_table('#table_Leads')}
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

        updateGroupleadPriorityFrontEnd(ids,'<?=$encGroupId?>');
    }

    $('#table_Leads').on( 'length.dt', function ( e, settings, len ) {
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