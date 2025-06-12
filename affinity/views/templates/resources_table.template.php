<style>
    .atag{color:#0077b5;}
    .atag:hover {cursor: pointer;text-decoration: underline; }
    .atag a{color:#33617E;}
    .breadcrumb{background-color: #E9ECEF;}
    .breadcrumb-item.active {
	color: #5D5B5B;
}
    .bulkDnD {
      margin-bottom: 45px;
    }
    #bulk_file_table {
      width:100%;
      font-size:15px;
      line-height: 20px;
      padding:5px;
    }
    #bulk_file_table tr {
      border:solid 1px #efefef;
    }
    #bulk_file_table tr:hover {
      background-color: #e2e8ff;
    }

    #bulk_file_table thead, #bulk_file_table thead tr:hover {
      background-color:#ebebeb;
    }
    #bulk_file_table thead tr{
      height:35px;
    }
    #bulk_file_table thead tr th{
      padding-left: 5px;
    }
    #bulk_file_table td {
      padding: 5px;
    }
    #bulk_file_table td:first-child {
      width:40%;
    }
    #bulk_file_table td:nth-child(2) {
      width: 80px;
    }

    #bulk_file_table td:last-child {
    /*  width:20px; */
      font-weight:bold;
      text-align: center;
    }
    #bulk_file_table td.td_icons {
      width: 30px;
      height: 35px;
    }
    #bulk_file_table td.td_icons i {
      cursor:pointer;
      padding:5px;
    }
    #file_edit_row {
      position: absolute;
      width: 100%;
      padding: 7px;
      height: 45px;
    }
    #file_edit_row input {
      width: 65%;
      margin-right:15px;
    }
    #file_edit_row button {
      margin-left: 10px;
    }
    .resource-folder-card-head {
        font-size: .8rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: #5D5B5B;
    }

    .resource-folder-card-title {
        font-size: 1rem;
        font-weight: bold;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
    }
    .resource-folder-card-text {
        font-size: .75rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        color: #5D5B5B;
    }   
    .drag_over{
        border: 1px dotted #0077b5;
        padding: 2px;
    }
    .hover-gray:hover {
        background-color: rgba(224, 224, 224, 0.40);
    }
    .custom-select:focus {
        box-shadow: 0 0 0 .2rem rgb(0 9 19); 
        border-color: unset;
        outline: unset;  
    }
</style>
<?php if ($canCreateFolder || $canCreateFile) { ?>
        <div class="row p-0">
            <div class="resource-action link " >
                <button class="btn resource-action-btn" <?= $canCreateFile ? '' : 'disabled' ?>  onclick="openResourceModal('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId(0)?>',2,'<?= $_COMPANY->encodeId($chapterid)?>','<?= $_COMPANY->encodeId($channelid)?>')">
                <i class="fa fa-upload" aria-hidden="true"></i> <?= gettext('Upload File'); ?>
                </button>

                <button class="btn resource-action-btn" <?= $canCreateFile ? '' : 'disabled' ?> onclick="openResourceModal('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId(0)?>',4,'<?= $_COMPANY->encodeId($chapterid)?>','<?= $_COMPANY->encodeId($channelid)?>')">
                  <i class="fa fa-upload" aria-hidden="true"></i> <?= gettext('Bulk File Upload'); ?>
                </button>

                <button class="btn resource-action-btn" <?= $canCreateFile ? '' : 'disabled' ?> onclick="openResourceModal('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId(0)?>',1,'<?= $_COMPANY->encodeId($chapterid)?>','<?= $_COMPANY->encodeId($channelid)?>')">
                    <i class="fa fa-link" aria-hidden="true"></i> <?= gettext('New Link'); ?>
                    </button>

                <button class="btn resource-action-btn"  onclick="openResourceModal('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId(0)?>',3,'<?= $_COMPANY->encodeId($chapterid)?>','<?= $_COMPANY->encodeId($channelid)?>')" >
                    <i class="fa fa-folder" aria-hidden="true"></i> <?= gettext('New Folder'); ?>
                </button>
                <div class="resource_sortby_container pull-right m-0 p-0" style="margin-left: 11rem!important;">
                    <label for="colFormLabelSm" class="col-6 p-1 col-form-label col-form-label-sm text-right" style="color:#000;"><?= gettext("Sort By"); ?>:</label>
                    <div class="col-6 p-0">
                        <select aria-label="<?= gettext("Sort By"); ?>" id="sortListOrder" class="custom-select custom-select-sm" onchange="sortGroupResoures('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId($chapterid)?>','<?= $_COMPANY->encodeId($channelid)?>','<?= $folderid ? 1 : 0; ?>',this.value)">
                            <option value="defalut" <?= $_SESSION['resource_sortby'] == 'defalut' ? 'selected' : ''; ?>><?= gettext("Default")?></option>
                            <option value="name" <?= $_SESSION['resource_sortby'] == 'name' ? 'selected' : ''; ?>><?= gettext("Name")?></option>
                            <option value="size" <?= $_SESSION['resource_sortby'] == 'size' ? 'selected' : ''; ?>><?= gettext("Size")?></option>
                            <option value="type" <?= $_SESSION['resource_sortby'] == 'type' ? 'selected' : ''; ?>><?= gettext("Type")?></option>
                            <option value="created" <?= $_SESSION['resource_sortby'] == 'created' ? 'selected' : ''; ?>><?= gettext("Created On")?></option>
                            <option value="modified" <?= $_SESSION['resource_sortby'] == 'modified' ? 'selected' : ''; ?>><?= gettext("Modified On")?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
    <?php
        if ($folder) {
            $parent_rows = $folder->getAllParents();
            
        ?>

        <div class="col-md-12 mt-3 p-0">
            <div class="resource-container">
                <div class="table-responsive" id="resource_data-table" style="line-height: 1rem;font-size: 14px;">
                    <nav aria-label="breadcrumb" aria-label="Main">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item atag">
                                <a href="javascript:void(0);" class="droptarget" data-rid="<?= $_COMPANY->encodeId(0); ?>"
                                ondrop="drop(event)" ondragenter="dragEnter(event)"
                                ondragleave="dragLeave(event)" ondragover="dragOver(event)"
                                onclick="getGroupResources('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId(0); ?>')"><?= gettext('Resources'); ?></a>
                            </li>

                            <?php foreach ($parent_rows as $parent_row) { ?>
                            <li class="breadcrumb-item atag ">
                                <a href="javascript:void(0);" class="droptarget" data-rid="<?= $_COMPANY->encodeId($parent_row['resource_id']); ?>"
                                ondrop="drop(event)" ondragenter="dragEnter(event)" ondragleave="dragLeave(event)"
                                ondragover="dragOver(event)"
                                onclick="getResourceChildData('<?= $_COMPANY->encodeId($parent_row['resource_id']); ?>','<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>')"><?= htmlspecialchars($parent_row['resource_name']) ?></a>
                            </li>
                            <?php } ?>

                            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($folder->val('resource_name')); ?></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

<?php } ?>

<input type="hidden" id="parent_id" value="<?=$_COMPANY->encodeId($folderid)?>">

<?php
    if (!empty($_SESSION['showGlobalChapterOnly'])){
        $chapterid_filter = 0; // Set chapterid filter to 0 to fetch resources which have chapterid = 0
    } else {
        $chapterid_filter = ($chapterid == 0) ? null : $chapterid ;
    }
    if (!empty($_SESSION['showGlobalChannelOnly']) ){
        $channelid_filter = 0; // Set channelid filter to 0 to fetch resources which have channelid = 0
    } else {
        $channelid_filter = ($channelid == 0) ? null : $channelid ;
    }

    // Note:
    // We are generating the chapterid_filter from the values of chapterid and $_SESSION['showGlobalChapterOnly']
    // Simliary, generating the channelid_filter from the values of channelid and $_SESSION['showGlobalChannelOnly']
    // This is done because the way GetResourcesForGroup works - it will ignore value of chapterid_filter if null and
    // ignore value of channelid_filter if null. 0 is valid value and if supplied expilicit filter of 0 is used.
    //
    $sortBy = $_SESSION['resource_sortby'] ?? 'default';
    $child_rows = Resource::GetResourcesForGroup($groupid,$folderid,$chapterid_filter,$channelid_filter,$is_resource_lead_content,$sortBy);
    $placeholder = GROUP::GROUP_RESOURCE_PLACEHOLDERS;
    $folders = [];
    $files = [];
    if(!empty($child_rows)){
        foreach ($child_rows as $child_row) {
            if ($child_row['resource_type']==3) {
                 $folders[] = $child_row; 
            }else{
                $files[] = $child_row;
            }
        }
    }
?>
    <?php if(!empty($folders)){ 
        // sort the folders
        if (!isset($_SESSION['resource_sortby']) || $_SESSION['resource_sortby'] == 'defalut' || $_SESSION['resource_sortby'] == 'size'){ 
            usort($folders, function($a, $b){
                if ($a['pin_to_top'] != $b['pin_to_top']) {
                    return $a['pin_to_top'] ? -1 : 1;
                }
                return strcasecmp($a['resource_name'], $b['resource_name']);
            } );
        } 
        $i = 0; ?>
    <div class="container my-3 px-0 mx-0">
        <div class="row">
            <?php foreach ($folders as $resource_folder) { $i++; $opts = 0; ?>
            <div class="col-12 col-md-4 mt-3 mb-3">
                <div class="card h-100 w-100 py-0" id="folder-<?= $i; ?>">
                    <div class="dropdown card-dropdown" style="text-align: right;">
                        <button aria-expanded="false" aria-label="<?= htmlspecialchars($resource_folder['resource_name']) ?>" class="btn btn-no-style dropdown-toggle" id="rid_<?=$_COMPANY->encodeId($resource_folder['resource_id']) ?>" data-toggle="dropdown" style="min-width: unset!important;">
                            <i class="fas fa fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                        <?php if ($_COMPANY->getAppCustomization()['resources']['pinning']['enabled'] && $_USER->canCreateOrPublishContentInScopeCSV($groupid,$resource_folder['chapterid'],$resource_folder['channelid'])){  $opts++;?>
                            <?php if ( $resource_folder['pin_to_top'] == "0"){ ?>
                            <li><a role="button" href="javascript:void(0);" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to pin this resource to show on top?'); ?>" onclick="pinUnpinResource('<?= $_COMPANY->encodeId($resource_folder['groupid']); ?>','<?=$_COMPANY->encodeId($resource_folder['resource_id'])?>','1','<?= $_COMPANY->encodeId($resource_folder['chapterid']); ?>','<?= $_COMPANY->encodeId($resource_folder['channelid']); ?>')"><i class="fa fa-thumbtack" aria-hidden="true"></i>&emsp;<?= gettext('Pin Resource'); ?></a></li>
                            <?php } else { ?>
                            <li><a role="button" href="javascript:void(0);" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to unpin this resource?'); ?>" onclick="pinUnpinResource('<?= $_COMPANY->encodeId($resource_folder['groupid']); ?>','<?=$_COMPANY->encodeId($resource_folder['resource_id']) ?>','2','<?= $_COMPANY->encodeId($resource_folder['chapterid']); ?>','<?= $_COMPANY->encodeId($resource_folder['channelid']); ?>')"><i class="fa fa-thumbtack" aria-hidden="true"></i>&emsp;<?= gettext('Unpin Resource'); ?></a></li>
                            <?php } ?>
                        <?php } ?>

                        <?php if($_USER->canCreateOrPublishContentInScopeCSV($groupid,$resource_folder['chapterid'],$resource_folder['channelid'])){ $opts++; ?>
                        <li><a role="button" class="" href="javascript:void(0);" onclick="updateFolderIconModal('<?= $_COMPANY->encodeId($resource_folder['groupid']); ?>','<?= $_COMPANY->encodeId($resource_folder['resource_id']); ?>',<?= $resource_folder['resource_type']; ?>)" ><i class="fa fas fa-edit" aria-hidden="true" ></i>&emsp;<?= gettext('Update Folder Icon'); ?></a></li>
                        <?php } ?>

                        <?php if($_USER->canCreateOrPublishContentInScopeCSV($groupid,$resource_folder['chapterid'],$resource_folder['channelid'])){ $opts++; ?>
                        <li><a role="button" class="" href="javascript:void(0);" onclick="openResourceModal('<?= $_COMPANY->encodeId($resource_folder['groupid']); ?>','<?= $_COMPANY->encodeId($resource_folder['resource_id']); ?>',<?= $resource_folder['resource_type']; ?>)" ><i class="fa fas fa-edit" aria-hidden="true" ></i>&emsp;<?= gettext('Edit'); ?></a></li>
                        <?php } ?>

                        <?php if($_USER->canCreateOrPublishContentInScopeCSV($groupid,$resource_folder['chapterid'],$resource_folder['channelid'])){ $opts++; ?>
                        <li><a role="button" href="javascript:void(0);" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to delete this resource?'); ?>" onclick="deleteResource('<?= $_COMPANY->encodeId($resource_folder['groupid']); ?>','<?= $_COMPANY->encodeId($resource_folder['resource_id']); ?>',<?= $i; ?>,'folder')" ><i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= gettext('Delete'); ?></a></li>
                        <?php } ?>

                        <?php if($_USER->canCreateOrPublishContentInScopeCSV($groupid,$resource_folder['chapterid'],$resource_folder['channelid'])){ $opts++; ?>
                        <li><a role="button" href="javascript:void(0);" class="" onclick="showStatistics('<?= $_COMPANY->encodeId($resource_folder['groupid']); ?>','<?= $_COMPANY->encodeId($resource_folder['resource_id']); ?>')"><i class="fa fa-chart-pie" aria-hidden="true"></i>&emsp;<?= gettext('Statistics'); ?></a></li>
                        <?php } ?>
                            <?php if(!$is_resource_lead_content){ $opts++; ?> 
                        <li><a role="button" class="" href="javascript:void(0);" onclick="getShareableLink('<?= $_COMPANY->encodeId($resource_folder['groupid']); ?>','<?= $_COMPANY->encodeId($resource_folder['resource_id']); ?>','7')"><i class="fa fa-share-square" aria-hidden="true"></i>&emsp;<?= gettext('Get Shareable Link'); ?></a></li>
                        <?php } ?>

                        <?php if (!$opts){  ?>
                            <a class="dropdown-item disabled"><i class="fa fas fa-exclamation" aria-hidden="true"></i>&emsp;<?= gettext('No options available');?></a>
                         <?php } ?>
                        </ul>
                    </div>

                    <a role="button" data-rid="<?= $_COMPANY->encodeId($resource_folder['resource_id']); ?>"
                       id="<?= $_COMPANY->encodeId($resource_folder['resource_id']); ?>" href="javascript:void(0);"
                       class="droptarget h-100 hover-gray"
                       onclick="getResourceChildData('<?= $_COMPANY->encodeId($resource_folder['resource_id']); ?>','<?= $_COMPANY->encodeId($resource_folder['groupid']); ?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>')"
                       ondrop="drop(event)" ondragover="dragOver(event)" ondragenter="dragEnter(event)" ondragleave="dragLeave(event)"

                        <?php if ($canCreateOrPublishContentInScope) { ?>
                        draggable="true" ondragstart="drag(event)"
                        <?php } else { ?>
                        draggable="false"
                        <?php }  ?>

                       rel="noopener noreferrer"
                    >
                            <div class="for-border">
                            <div class="my-1 px-3">
                            <?php if($resource_folder['chapterid']) { ?>
                            <p class="resource-folder-card-head">
                                <i class="fas fa-globe" style="color:<?= $resource_folder['chapterColor'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($resource_folder['chaptername']); ?>
                            </p>
                            <?php } elseif($resource_folder['channelid']){ ?>
                            <p class="resource-folder-card-head">
                                <i class="fas fa-layer-group" style="color:<?= $resource_folder['channelColor'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($resource_folder['channelname']); ?>
                            </p>
                            <?php } else { ?>
                            <p class="resource-folder-card-head">
                                &nbsp;
                            </p>
                            <?php } ?>
                        </div>

                        <?php if (!empty($resource_folder['resource'])) { ?>
                        <img src="<?= $resource_folder['resource'] ?>" width="50px" height="50px" draggable="false" style="max-width:50px;max-height: 50px; object-fit: contain;" alt="">
                        <?php } else { ?>
                        <i class="fa fa-folder" style="font-size: 50px;"></i>
                        <?php } ?>

                        <div class="card-body d-flex flex-column py-2">

                            <p class="card-title resource-folder-card-title">
                                <?php if($resource_folder['pin_to_top']){ ?>
                                <i role="img" aria-label="Pinned" class="fa fa-thumbtack mr-1" style="color:#0077b5;font-size: 0.3rem;"></i>
                                <?php } ?>
                                <?= htmlspecialchars($resource_folder['resource_name']) ?>
                            </p>
                            <p class="card-text resource-folder-card-text">
                                <?= htmlspecialchars($resource_folder['resource_description']) ?>
                            </p>
                        </div>
                            </div>
                    </a>
                    
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>

<?php if (!empty($files)) { 
    // sort the files
    if (!isset($_SESSION['resource_sortby']) || $_SESSION['resource_sortby'] == 'defalut'){ 
        usort($files, function($c, $d){
            if ($c['pin_to_top'] != $d['pin_to_top']) {
                return $c['pin_to_top'] ? -1 : 1;
            }
            return strcasecmp($c['resource_name'], $d['resource_name']);
        } );
    }
    $i=0; ?>
<table id="table-resource" class="table display" summary="This table displays the list of resources">
    <thead>
        <tr>
            <th width="33%" scope="col"><?= gettext('Name'); ?></th>
            <th width="37%" scope="col"><?= gettext('Description'); ?></th>
            <th width="10%" scope="col"><?= gettext('Size'); ?></th>
            <th width="18%" scope="col"><?= gettext('Last Modified'); ?></th>
            <th width="2%" scope="col"><?= gettext('Action'); ?></th>
        </tr>                               
    </thead>
    <tbody>
        <?php foreach ($files as $child_row) { $i++; ?>
        <tr id="file-<?= $i; ?>" >
            <td>
                <a 
                    data-rid="<?= $_COMPANY->encodeId($child_row['resource_id']); ?>"
                    id="<?= $_COMPANY->encodeId($child_row['resource_id']); ?>"
                    <?php  
                    if ($child_row['resource_type']==1 ){  ?>
                    <?php if(filter_var($child_row['resource'], FILTER_VALIDATE_EMAIL)){ ?>
                        href="mailto:<?= $child_row['resource']; ?>" target="_blank"            
                    <?php } else { ?>
                        href="resource?groupid=<?= $_COMPANY->encodeId($child_row['groupid']); ?>&id=<?= $_COMPANY->encodeId($child_row['resource_id']); ?>&resourcetype=<?= $child_row['resource_type']; ?>" target="_blank"
                    <?php } ?>
                    <?php  } ?>
                    <?php if($child_row['resource_type']==2 && $child_row['extention'] == 'pdf' ){ ?>
                        class="pdf-resource" href="javascript:void(0);" style="cursor:pointer;"  onclick="openPDF('<?= $_COMPANY->encodeId($child_row['groupid']); ?>' , '<?= $_COMPANY->encodeId($child_row['resource_id']); ?>' , '<?= $child_row['resource_type']; ?>');"    
                    <?php } else { ?>
                        href="resource?groupid=<?= $_COMPANY->encodeId($child_row['groupid']); ?>&id=<?= $_COMPANY->encodeId($child_row['resource_id']); ?>&resourcetype=<?= $child_row['resource_type']; ?>"
                    <?php  } ?>

                    title="<?=htmlspecialchars($child_row['resource_name'])?>"

                    <?php if ($canCreateOrPublishContentInScope) { ?>
                    draggable="true" ondragstart="drag(event)"
                    <?php } else { ?>
                    draggable="false"
                    <?php }  ?>
                        rel="noopener noreferrer" >
                    <img data-rid="<?= $_COMPANY->encodeId($child_row['resource_id']); ?>" src="<?= $placeholder[$child_row['extention']]; ?>" alt="<?php if($child_row['extention'] =='jpg' || $child_row['extention'] =='png' || $child_row['extention'] =='jpeg'){echo "image"; }else{echo $child_row['extention'];} ?>" height="16px">
                    &nbsp;
                    <?= (strlen(htmlspecialchars($child_row['resource_name'])) > 32)? htmlspecialchars(substr($child_row['resource_name'], 0, 29)) . '...' : htmlspecialchars($child_row['resource_name']); ?>
                    <?php if($child_row['pin_to_top']){ ?>
                        <i role="img" aria-label="Pinned" data-rid="<?= $_COMPANY->encodeId($child_row['resource_id']); ?>" class="fa fa-thumbtack ml-1" style="color:#0077b5;vertical-align:super;font-size: 0.3rem;" ></i>
                    <?php } ?>
            </a>
            </td>
            <td style="word-break:break-word">
                <?php if($child_row['parent_id'] == 0){ ?>
                    <?php if($child_row['chapterid']){ ?>
                        <span class="gray">Scope : </span>
                        <small class="chapter-label" style="color:<?= $child_row['chapterColor'] ?>">
                            <i class="fas fa-globe" style="color:<?= $child_row['chapterColor'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($child_row['chaptername']); ?>
                        </small>
                        <br>
                    <?php } ?>
                    <?php if($child_row['channelid']){ ?>
                        <span class="gray">Scope : </span>
                        <small class="chapter-label" style="color:<?= $child_row['channelColor'] ?>">
                            <i class="fas fa-layer-group" style="color:<?= $child_row['channelColor'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($child_row['channelname']); ?>
                        </small>
                        <br>
                    <?php } ?>
                <?php } ?>
                <?php if (0) { /* Disabling single line view for description for now */?>
                <span title="<?=htmlspecialchars($child_row['resource_description'])?>">
                    <?= (strlen(htmlspecialchars($child_row['resource_description'])) > 46)? substr(htmlspecialchars($child_row['resource_description']), 0, 42) . '...' : htmlspecialchars($child_row['resource_description']); ?>
                </span>
                <?php } else {  ?>
                <?= htmlspecialchars($child_row['resource_description']); ?>
                <?php } ?>
            </td>
            <td> 
                <?php
                if ($child_row['resource_type']==2) {
                    echo convertBytesToReadableSize($child_row['size']);
                } elseif($child_row['resource_type']==1){
                    echo '-';
                } else{
                    echo '-';
                }
                ?>
            </td>
            <td>
                <?php
                $datetime = $child_row['modifiedon'];
                echo $_USER->formatUTCDatetimeForDisplayInLocalTimezone($datetime,true,true,false);
                ?>
            </td>
            <td>
                <?php 
                    $optn = 0;
                ?>
                <div class="btn-no-style pull-right" >
                    <a aria-expanded="false" id="rid_<?= $_COMPANY->encodeId($child_row['resource_id']); ?>" role="button" tabindex="0" class="dropdown-toggle  fa fa-ellipsis-v col-doutd resources-btn" data-toggle="dropdown" aria-label="Action dropdown"></a>
                    <ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton<?= $_COMPANY->encodeId($child_row['resource_id']); ?>'" style="width: 250px; cursor: pointer;">

                        <?php if ($_COMPANY->getAppCustomization()['resources']['pinning']['enabled'] && $_USER->canCreateOrPublishContentInScopeCSV($groupid,$child_row['chapterid'],$child_row['channelid'])){ ?>
                            <?php if ( $child_row['pin_to_top'] == "0"){ $optn++;?>
                            <li><a role="button" href="javascript:void(0);" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to pin this resource to show on top?'); ?>" onclick="pinUnpinResource('<?= $_COMPANY->encodeId($child_row['groupid']); ?>','<?=$_COMPANY->encodeId($child_row['resource_id'])?>','1','<?= $_COMPANY->encodeId($child_row['chapterid']); ?>','<?= $_COMPANY->encodeId($child_row['channelid']); ?>')"><i class="fa fa-thumbtack" aria-hidden="true"></i>&emsp;<?= gettext('Pin Resource'); ?></a></li>
                            <?php } else { $optn++;?>
                            <li><a role="button" href="javascript:void(0);" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to unpin this resource?'); ?>" onclick="pinUnpinResource('<?= $_COMPANY->encodeId($child_row['groupid']); ?>','<?=$_COMPANY->encodeId($child_row['resource_id']) ?>','2','<?= $_COMPANY->encodeId($child_row['chapterid']); ?>','<?= $_COMPANY->encodeId($child_row['channelid']); ?>')"><i class="fa fa-thumbtack" aria-hidden="true"></i>&emsp;<?= gettext('Unpin Resource'); ?></a></li>
                            <?php } ?>
                        <?php } ?>

                        <?php if($_USER->canCreateOrPublishContentInScopeCSV($groupid,$child_row['chapterid'],$child_row['channelid'])){ $optn++; ?>
                        <li><a role="button" class="" href="javascript:void(0);" onclick="openResourceModal('<?= $_COMPANY->encodeId($child_row['groupid']); ?>','<?= $_COMPANY->encodeId($child_row['resource_id']); ?>',<?= $child_row['resource_type']; ?>)" ><i class="fa fas fa-edit" aria-hidden="true" ></i>&emsp;<?= gettext('Edit'); ?></a></li>
                        <?php } ?>

                        <?php if($_USER->canCreateOrPublishContentInScopeCSV($groupid,$child_row['chapterid'],$child_row['channelid'])){ $optn++; ?>
                        <li><a role="button" href="javascript:void(0);" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to delete this resource?'); ?>" onclick="deleteResource('<?= $_COMPANY->encodeId($child_row['groupid']); ?>','<?= $_COMPANY->encodeId($child_row['resource_id']); ?>',<?= $i; ?>,'file')" ><i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= gettext('Delete'); ?></a></li>
                        <?php } ?>

                        <?php if ($child_row['resource_type']==2) { $optn++; ?>
                        <li><a role="button" class="" href="javascript:void(0);" onclick="getShareableLink('<?= $_COMPANY->encodeId($child_row['groupid']); ?>','<?= $_COMPANY->encodeId($child_row['resource_id']); ?>','5')"><i class="fa fa-share-square" aria-hidden="true"></i>&emsp;<?= gettext('Get Shareable Link'); ?></a></li>
                        <?php } ?>
                  
                        <?php if($_USER->canCreateOrPublishContentInScopeCSV($groupid,$child_row['chapterid'],$child_row['channelid'])){ $optn++; ?>
                        <li><a role="button" href="javascript:void(0);" class="" onclick="showStatistics('<?= $_COMPANY->encodeId($child_row['groupid']); ?>','<?= $_COMPANY->encodeId($child_row['resource_id']); ?>')"><i class="fa fa-chart-pie" aria-hidden="true"></i>&emsp;<?= gettext('Statistics'); ?></a></li>
                        <?php } ?>

                        <?php if($optn == 0){ ?>
                        <li><a >- No option available - </a></li>
                        <?php } ?>

                    </ul>
                </div>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
<?php } ?>

<?php if (empty($folders) && empty($files)) { ?>
        <p style="text-align: center;"><img height="200px" src="<?= $placeholder['empty']; ?>" alt="No resource placeholder image" /></p>
        <p style="text-align:center;color:gray;"><?= gettext('Stay tuned for Resources to be added'); ?></p>
<?php } ?>
<div class="mb-5"></div>
<div id="resourcesListOrder" class="visually-hidden"></div>
<input type="hidden" id="drag_id">

<script>
    $(".confirm").popConfirm({content: ''});
</script>

<script>
    function findDropTarget(target){
        while (target && !target.getAttribute('data-rid')) {
            target = target.parentNode;
        }
        return target;
    }
    function drag(ev) {
        $("#drag_id").val($( ev.target ).data( "rid" ));  
    }
 
    function drop(ev) {
        let target = findDropTarget(ev.target);
        var drop_id = target.getAttribute( "data-rid" );    
        // var drop_id = $(ev.target).data( "rid" );
        var parent_id = $('#parent_id').val();
        var drag_id = $('#drag_id').val();
        $("#drag_id").val('');
        if (drop_id!=drag_id){
            $.ajax({
                url: 'ajax_resources.php?moveResourceIntoFolder=1',
                type: "POST",
                data: {'drop_id':drop_id,'drag_id':drag_id,'parent_id':parent_id},
                success : function(data) {
                    if(data >0 ){
                        getResourceChildData(parent_id,'<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>');
                    } else if (data  == -1) {
                        swal.fire({title: 'Error',text:'<?= gettext("Error: You cannot move the resource into a folder for a different scope.") ?>'});
                    } else if (data  == -2) {
                        // Ignore
                    } else if (data  == -3) {
                        swal.fire({title: 'Error',text:'<?= gettext("Error: You do not have the permissions required to move the selected resource to the destination folder") ?>'});
                    } else {
                       getGroupResources('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>');
                    }
                }
            });
        }
        ev.preventDefault();
    }

    function dragOver(ev){
        ev.preventDefault();
    }
    function dragEnter(event) {
        event.preventDefault();
        event.stopPropagation()
        let target = event.currentTarget;
        if ( target && target.classList.contains("droptarget") ) {
            target.classList.add('drag_over');
        } 

    } 
    function dragLeave(event) {
        event.preventDefault();
        event.stopPropagation()
        let target = event.currentTarget;
        if ( event.target.classList.contains("droptarget") ) {
            target.classList.remove('drag_over');
        }

    }
 </script>
<?php
if (!empty($_SESSION['show_resource_folder_id'])) {
    $enc_nid = $_COMPANY->encodeId($_SESSION['show_resource_folder_id']);
    unset($_SESSION['show_resource_folder_id']);
    ?>
    <script>
        $(document).ready(function() {
            getResourceChildData('<?= $enc_nid?>','<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>');
        });
    </script>
<?php } ?>


<script>
    function openResourceModal(g,r,t,ch,chnl){
        var is_resource_lead_content = 0;
        if (localStorage.getItem("is_resource_lead_content") !== null){
            is_resource_lead_content = localStorage.getItem("is_resource_lead_content");
        }

        var parent_id = $('#parent_id').val();
        $.ajax({
            url: 'ajax_resources.php?openResourceModal='+g,
            type: "GET",
            data: {'resource_id':r,'resource_type':t,'parent_id':parent_id,'chapterid':ch,'channelid':chnl,'is_resource_lead_content':is_resource_lead_content},
            success : function(data) {
                
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title,text:jsonData.message});
                } catch(e) {
                    $("#updateResourceModal").html(data);
                    $('#resourceModal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                }
            }
        });
    }
</script>
<script>
    function updateFolderIconModal(g,r,t,ch,chnl){
        var is_resource_lead_content = 0;
        if (localStorage.getItem("is_resource_lead_content") !== null){
            is_resource_lead_content = localStorage.getItem("is_resource_lead_content");
        }
        var parent_id = $('#parent_id').val();
        $.ajax({
            url: 'ajax_resources.php?updateFolderIconModal='+g,
            type: "GET",
            data: {'resource_id':r,'resource_type':t,'parent_id':parent_id,'chapterid':ch,'channelid':chnl,'is_resource_lead_content':is_resource_lead_content},
            success : function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title,text:jsonData.message});
                } catch(e) {
                    $("#updateResourceModal").html(data);
                    $('#resourceIconModal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                }
            }
        });
    }
</script>
<script>
//On Enter Key...
$(function(){ 
    $(".resources-btn").keypress(function (e) {
        if (e.keyCode == 13) {
            $(this).trigger("click");
        }
    });
});
</script>
<script>
function toggleScrollBar(visible){
 $('html').css('overflow', visible ? 'auto' : 'hidden');   
}
var pdfLinkClickable = true;
function openPDF(groupId, resourceId, resourceType){
    if (pdfLinkClickable) {
        pdfLinkClickable = false;
        $.ajax({
            url: 'resource.php?groupid='+groupId+'&id='+resourceId+'&resourcetype='+resourceType+'&ispdf=1',
            method: 'GET',
            responseType: 'blob',
            success: function(data){

                var base64DecoedData = atob(data);

                var binaryArray = new ArrayBuffer(base64DecoedData.length);
                var uint8Array = new Uint8Array(binaryArray);

                for (let i = 0; i < uint8Array.length; i++) {
                    uint8Array[i] = base64DecoedData.charCodeAt(i);
                }
                var blob = new Blob([binaryArray], {type: 'application/pdf'});
                $.fancybox.open({
                    src: URL.createObjectURL(blob),
                    type: 'iframe',
                    beforeLoad: function () {
                        toggleScrollBar(false);
                    },
                    afterClose: function () {
                        toggleScrollBar(true);
                    }
                });                
            },
            error: function () {
                console.error('Error fetching the pdf: '+error);
            }
        });
        setTimeout(() => {
                    pdfLinkClickable = true;
        }, 1000);
    }
}

</script>