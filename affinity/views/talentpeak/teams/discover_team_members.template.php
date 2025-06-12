<style>
.card {
    background-color: #fff;
    color:#505050;
    border-radius: 10px;
    border: none;
    position: relative;
    margin-bottom: 30px;
    box-shadow: 0 0.46875rem 2.1875rem rgba(90,97,105,0.1), 0 0.9375rem 1.40625rem rgba(90,97,105,0.1), 0 0.25rem 0.53125rem rgba(90,97,105,0.12), 0 0.125rem 0.1875rem rgba(90,97,105,0.1);
}

.l-bg-blue-dark {
    background: linear-gradient(to right, #fff, #fff) !important;
    color: #505050;
    width: 100% !important;
    /* height: 290px !important; */
}
.l-bg-blue-dark:hover{
    background: linear-gradient(to left, #dbdbdb, #f4f7fc) !important;
}
.card .card-statistic-3 .card-icon-large .fas, .card .card-statistic-3 .card-icon-large .far, .card .card-statistic-3 .card-icon-large .fab, .card .card-statistic-3 .card-icon-large .fal {
    font-size: 210px;
}

.card .card-statistic-3 .card-icon {
    line-height: 50px;
    margin-left: 15px;
    color: #000;
    position: absolute;
    right: 10px;
    top: 10px;
    opacity: 0.1;
}

.l-bg-green {
    background: linear-gradient(135deg, #23bdb8 0%, #43e794 100%) !important;
    color: #fff;
}

.active-page{
    display:block;
}
.inactive-page{
    display:none;
}
.popover {
    width: 80% !important;
}

</style>

<div class="row">

    <?php if (!$matchingParameters){ ?>
        <div class="col-12 alert-warning p-3 mx-0">
            <p class="text-center"><b><?= sprintf(gettext('%1$s setup is not complete'), $_COMPANY->getAppCustomization()['group']['name-short']); ?>!</b></p>
            <p class="text-center py-2" style="font-size: small;"><?= sprintf(gettext('The Discover feature relies on matching criteria. Please request your %1$s leaders to complete the setup process.'), $_COMPANY->getAppCustomization()['group']['name']); ?></p>
        </div>
    <?php } ?>
 
    <?php
    foreach($roleRequestsWithSuggestions as $joinRequest){
        $matchedUsers = $joinRequest['suggestions'];
        $totalSuggetions = $joinRequest['totalSuggestionsCount'];
        $loadMoreDataAvailable = $joinRequest['loadMoreDataAvailable'];
        $oppositeRole = Team::GetTeamRoleType($joinRequest['oppositRoleId']);

        //        unset ($joinRequest['suggestions']);
        //        echo "<pre>";print_r($oppositeRole);echo "</pre>";
        //        echo "<pre>";print_r($joinRequest);echo "</pre>";
        //        continue;

        $canSendRequest = true;
        $bannerHoverTextSenderCapacity = '';
        if(!Team::CanSendP2PTeamJoinRequest($groupid,$_USER->id(),$joinRequest['roleid'])){
            $canSendRequest = false;
            $bannerHoverTextSenderCapacity = sprintf(gettext('You can\'t send a request to this user as you\'ve reached your maximum available capacity limit. This limit is based on the number of %1$s you\'re already in and any outstanding %2$s join requests'), Team::GetTeamCustomMetaName($group->getTeamProgramType(), 1), Team::GetTeamCustomMetaName($group->getTeamProgramType(), 0));
        }
    ?>
        <?php
        // This section handles the scenarios where discover_tab_show is off
        // If discover_tab_html is set then we will show the discover_tab_html and skip the roll, else we will skip
        if (empty($oppositeRole['discover_tab_show'])) {
            if (!empty($oppositeRole['discover_tab_html'])) {
        ?>
        <div class="col-md-12 p-3">
            <h4 class="px-0"><?= sprintf(gettext('%s Matches'), $joinRequest['oppositRolesType'])?></h4>
            <p class="my-2">
                <?php if (!empty($oppositeRole['discover_tab_html'])) { ?>
                <?= $oppositeRole['discover_tab_html'] ?>
                <?php } ?>
            </p>
        </div>
        <?php
            }
            continue;
        }
        ?>

        <div class="col-md-12 p-3" id="suggestions_byrole_container_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>">
            <h2 class="px-0"><?= sprintf(gettext('%s Matches'), $joinRequest['oppositRolesType'])?></h2>
            <p class="my-2">
                <?php if (empty($oppositeRole['discover_tab_html'])) { ?>
                    <?= sprintf(gettext("Based on your registration information here are the %s matches recommended for you to connect with:"), $joinRequest['oppositRolesType']); ?>
                <?php } else { ?>
                    <?= $oppositeRole['discover_tab_html'] ?>
                <?php } ?>
            </p>

            <div style="text-align: center; border-top: 1.5px solid rgb(185, 182, 182)" class="mb-3"></div>

            <div class="col-12 mb-3 invite-user-section">
                <?php if ($canSendRequest && Team::CanJoinARoleInTeam($groupid,$_USER->id(),$joinRequest['roleid'])){ ?>
                <p class=""><?= sprintf(gettext("In addition to the following recommendations, you can also directly invite users to join the %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType()))?>
                <?php if(Team::IsTeamRoleRequestAllowed($groupid,$oppositeRole['roleid'])){ ?>
                    <button class="btn-inline btn btn-sm btn-affinity" onclick="getInviteUserManuallyForTeamModel('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>', '<?= $_COMPANY->encodeId($joinRequest['oppositRoleId']); ?>');">
                        <?= gettext('Invite user')?>
                    </button>
                <?php } else { ?>
                    <button class="btn-inline btn btn-sm btn-affinity" tabindex="0" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<?= gettext('Registration Closed - Maximum Registrations Reached'); ?>" role="none" disabled>
                        <?= gettext('Invite user')?>
                    </button>
                <?php } ?>
                    <p>
                <?php } else { ?>
                <p class="red"><?= sprintf(gettext('You\'ve either reached the maximum number of %1$s you can participate in as a %2$s or you have pending requests sent or received where you hold the %2$s role. Therefore, you can\'t send requests or invite users to form new %3$s where you take on the %2$s role.'),$_COMPANY->getAppCustomization()['group']['name-short-plural'],$joinRequest['type'],Team::GetTeamCustomMetaName($group->getTeamProgramType(),1))?></p>
                <?php } ?>
            </div>
      
    <?php   if (!empty($matchedUsers)){
                if ($showAvailableCapacityOnly) {  // Fiter 
                    $availableRequestCapacityMatchedUsers = array();
                    foreach($matchedUsers as $matchedUser) {
                        list(
                            $roleSetCapacity,
                            $roleUsedCapacity,
                            $roleRequestBuffer,
                            $roleAvailableCapacity,
                            $roleAvailableRequestCapacity,
                            $roleAvailableBufferedRequestCapacity,
                            $pendingSentOrReceivedRequestCount
                        ) = Team::GetRoleCapacityValues($groupid, $joinRequest['oppositRoleId'], $matchedUser['userid']);

                        if ($roleSetCapacity==0 || $roleAvailableRequestCapacity > 0){
                            $availableRequestCapacityMatchedUsers[] = $matchedUser;
                        } 
                    }
                    $matchedUsers = $availableRequestCapacityMatchedUsers;
                } 
                $totalSuggetionsPerCall = count($matchedUsers);
                $matchedUsers = array_chunk($matchedUsers,MAX_TEAMS_ROLE_MATCHING_RESULTS_PER_DISCOVER_PAGE);
                $totalSuggetionsChunks = count($matchedUsers);
                
        ?>
            <!-- <div class="col-12" id="Maind"> -->
            <?php
            $p = 1;
            foreach($matchedUsers as $matchedUserChunk){ ?>
                <div data-page="<?= $p; ?>" class="pagination_container_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>" >

                <?php foreach($matchedUserChunk as $matchedUser){
                    $matchingPercentage = $matchedUser['matchingPercentage'];
                    $parameterWiseMatchingPercentage = $matchedUser['parameterWiseMatchingPercentage'];
                    $requestDetail = Team::GetTeamJoinRequestDetail($groupid,$joinRequest['userid'],$joinRequest['roleid'],$matchedUser['userid'],$joinRequest['oppositRoleId']);

                    list(
                        $roleSetCapacity,
                        $roleUsedCapacity,
                        $roleRequestBuffer,
                        $roleAvailableCapacity,
                        $roleAvailableRequestCapacity,
                        $roleAvailableBufferedRequestCapacity,
                        $pendingSentOrReceivedRequestCount
                    ) = Team::GetRoleCapacityValues($groupid, $joinRequest['oppositRoleId'], $matchedUser['userid']);
                    $roleAvailableRequestCapacityLabel = $roleAvailableRequestCapacity;
                    if ($roleSetCapacity == 0) {
                        $roleAvailableRequestCapacityLabel = gettext('Unlimited');
                    }
                    $canAcceptRequest = true;
                    $bannerHeading = gettext('Accepting New Requests');
                    $bannerSubHeading = ($roleAvailableRequestCapacity == 1) ? sprintf(gettext('%s spot available'),$roleAvailableRequestCapacityLabel) : sprintf(gettext('%s spots available'),$roleAvailableRequestCapacityLabel);
                    $bannerHoverText = '';
                    if ($roleSetCapacity!=0 && $roleAvailableRequestCapacity < 1){ //$roleSetCapacity = 0 means unlimited
                        $canAcceptRequest = false;
                        $bannerHeading = gettext('Not Accepting New Requests');
                        $bannerSubHeading = gettext('No spots available');
                        $bannerHoverText = gettext('You cannot send request as user\'s maximum outstanding requests have been reached.');
                //                    } elseif(!Team::CanJoinARoleInTeam($groupid,$matchedUser['userid'],$joinRequest['oppositRoleId'])){
                //                        $canAcceptRequest = false;
                //                        $bannerHeading = gettext('Not Accepting New Requests');
                //                        $bannerSubHeading = gettext('Maximum capacity reached');
                //                        $bannerHoverText = gettext('You cannot send request as user\'s maximum available capacity have been reached .');
                }
                   include(__DIR__ . "/discover_team_member_card.template.php");
                } ?>
                <!-- </div> -->
            </div>
            <?php $p++; } ?>
            <input type="hidden" id="suggestion_counter_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>" value="<?= $p; ?>">
            <input type="hidden" id="suggestion_page_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>" value="2">
            <input type="hidden" id="suggestion_active_pagination_page_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>" value="1">
        </div>

        <div class="col-md-12 mb-5">
            <?php if($totalSuggetionsPerCall > MAX_TEAMS_ROLE_MATCHING_RESULTS_PER_DISCOVER_PAGE){ ?>
            <ul class="pagination justify-content-center pagination-sm pagination<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>"></ul>
            <script>
                // Pagniation
                function getPageList(totalPages, page, maxLength) {
                    if (maxLength < 3) throw "maxLength must be at least 3";

                    function range(start, end) {
                        return Array.from(Array(end - start + 1), (_, i) => i + start);
                    }

                    var sideWidth = maxLength < parseInt(<?= MAX_TEAMS_ROLE_MATCHING_RESULTS; ?>) ? 1 : 2;
                    var leftWidth = (maxLength - sideWidth * 2 - 3) >> 1;
                    var rightWidth = (maxLength - sideWidth * 2 - 2) >> 1;
                    if (totalPages <= maxLength) {
                        // no breaks in list
                        return range(1, totalPages);
                    }
                    if (page <= maxLength - sideWidth - 1 - rightWidth) {
                        // no break on left of page
                        return range(1, maxLength - sideWidth - 1)
                        .concat([0])
                        .concat(range(totalPages - sideWidth + 1, totalPages));
                    }
                    if (page >= totalPages - sideWidth - 1 - rightWidth) {
                        // no break on right of page
                        return range(1, sideWidth)
                        .concat([0])
                        .concat(
                            range(totalPages - sideWidth - 1 - rightWidth - leftWidth, totalPages)
                        );
                    }
                    // Breaks on both sides
                    return range(1, sideWidth)
                        .concat([0])
                        .concat(range(page - leftWidth, page + rightWidth))
                        .concat([0])
                        .concat(range(totalPages - sideWidth + 1, totalPages));
                }

                $(function() {
                    // Number of items and limits the number of items per page
                    var numberOfItems = $(".pagination_container_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?> .content<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").length;
                    var limitPerPage = <?=MAX_TEAMS_ROLE_MATCHING_RESULTS_PER_DISCOVER_PAGE?>;
                    // Total pages rounded upwards
                    var totalPages = Math.ceil(numberOfItems / limitPerPage);
                    var paginationSize = 8;
                    var currentPage;

                    function showPage(whichPage) {
                        if (whichPage < 1 || whichPage > totalPages) return false;
                        $("#suggestion_active_pagination_page_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").val(whichPage);
                        currentPage = whichPage;
                        $(".pagination_container_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?> .content<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>")
                        .hide()
                        .slice((currentPage - 1) * limitPerPage, currentPage * limitPerPage)
                        .show();

                        
                        // Replace the navigation items (not prev/next):
                        $(".pagination<?= $_COMPANY->encodeId($joinRequest['roleid']); ?> li").slice(1, -1).remove();
                        getPageList(totalPages, currentPage, paginationSize).forEach(item => {
                            var activeItem = (item === currentPage ? "true" : "");

                        $("<li>")
                            .addClass(
                            "page-item " +
                                (item ? "current-page " : "") +
                                (item === currentPage ? "active " : "")
                            )
                            .append(
                            $("<a>")
                                .attr("aria-label","page "+item)
                                .attr("aria-current",activeItem)
                                .addClass("page-link")                                                              
                                .attr({
                                    id: "page_link_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>"+item,
                                href: "javascript:void(0)"
                                })
                                .text(item || "...")
                            )
                            .insertBefore("#next-page<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>");
                        });
                        return true;
                    }

                    // Include the prev/next buttons:
                    $(".pagination<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").append(
                        $("<li>").addClass("page-item").attr({ id: "previous-page<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>" }).append(
                        $("<a>")
                            .addClass("page-link")
                            .attr("aria-label","<?= gettext('Previous Page');?>")
                            .attr({
                            href: "javascript:void(0)"
                            })
                            .text("Prev")
                        ),
                        $("<li>").addClass("page-item").attr({ id: "next-page<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>" }).append(
                        $("<a>")
                            .addClass("page-link")
                            .attr("aria-label","<?= gettext('Next Page');?>")
                            .attr({
                            href: "javascript:void(0)"
                            })
                            .text("Next")
                        )
                    );
                    // Show the page links
                    $(".pagination_container_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").show();
                    showPage(1);

                    // Use event delegation, as these items are recreated later
                    $(
                        document
                    ).on("click", ".pagination<?= $_COMPANY->encodeId($joinRequest['roleid']); ?> li.current-page:not(.active)", function() {
                        return showPage(+$(this).text());
                    });
                    $("#next-page<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").on("click", function() {
                        return showPage(currentPage + 1);
                    });

                    $("#previous-page<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").on("click", function() {
                        return showPage(currentPage - 1);
                    });
                });

            </script>
            <?php } ?>
            <div class="total-card text-center small mt-2" id="suggetionCards<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>">
                <?= ($loadMoreDataAvailable) ?
                    sprintf(gettext('Found <span class="totalSuggetionCards box_%2$s">%1$s</span> matches, showing <span class="totalSuggetionCards">%1$s</span> results'),$totalSuggetionsPerCall,$joinRequest['oppositRolesType']) :
                    sprintf(gettext('Found <span class="totalSuggetionCards box_%2$s">%1$s</span> matches'),$totalSuggetionsPerCall,$joinRequest['oppositRolesType'])
                ?>
                <?php if ($loadMoreDataAvailable){ ?>
                    <span id="load_more_container_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>">
                        <button class="btn btn-link btn-no-style mb-1 text-small" onclick="loadMoreDiscoverSuggestions('<?= $_COMPANY->encodeId($groupid); ?>',1,'<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>')">... <?= gettext("load more")?></button>
                    </span>

                <?php } ?>
            </div>
        </div>

        <?php } else {
            if (($filter_attribute_keyword || $filter_primary_attribute || $name_keyword)) {
                $emptyMessage = gettext('Your search criteria did not return any matching result. Please change your search criteria and try again. ');
            }

            if ($loadMoreDataAvailable){ ?>
                loadMoreDiscoverSuggestions('<?= $_COMPANY->encodeId($groupid); ?>',1,'<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>')
        <?php }
        ?>
            <div class="col-md-12 text-center p-3" id="noSpeaker">
                <p><?= isset($emptyMessage) ? $emptyMessage : sprintf(gettext("No matches found for %s role"),$joinRequest['oppositRolesType']);?></p>
            </div>
        <?php } ?>
       
    <?php }  ?>
</div>

<script>
    $(function(){
        $('[data-toggle="popover"]').popover({
            sanitize:false                    
        });  
    });     


    function getInviteUserManuallyForTeamModel(g,rid,sid) {
        $.ajax({
            url: './ajax_talentpeak.php?getInviteUserManuallyForTeamModel=1',
            type: 'get',
            data: {groupid:g,userRoleid:rid,subjectRoleid:sid},
            success: function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title:jsonData.title,text:jsonData.message});
                } catch(e) {
                    $('#loadAnyModal').html(data);
                    $('#searchUserToInviteForTeam').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                }
            }
        });
    }


//On Enter Key...
document.addEventListener('keydown', function( ev ) {
var keyCode = ev.keyCode || ev.which;
var linkId = ev.target.id;
if(keyCode === 13 & linkId != '') {           
        setTimeout(function(){
            $('#'+linkId).focus();
        }, 100);                    
    }     
});

function loadMoreDiscoverSuggestions(g,i,r) {

    let filter_attribute_type = [];
	$('select[name="primary_attribute[]"]').each(function(index,input){
		filter_attribute_type.push($(input).find('option:selected').attr('data-keytype'));
	});
	let suggestion_counter = $('#suggestion_counter_'+r).val();
    let suggestion_page_counter = $("#suggestion_page_"+r).val();
	let search = 1;
	let form_data = $('#filterByNameForm');
	let isFormExist = (form_data.length > 0);
	let finalData;
	if (isFormExist){
		finalData  = new FormData($('#filterByNameForm')[0]);
		finalData.append('search', search);
		finalData.append('groupid', g);
        finalData.append('suggestion_counter', suggestion_counter);
        finalData.append('suggestion_page_counter', suggestion_page_counter);
        finalData.append('roleit_to_match', r);
        
		finalData.append('filter_attribute_type', filter_attribute_type);
	} else {
		finalData = new FormData(); // start empty Form object this is the case of Networking program type
		finalData.set('search', search);
		finalData.set('groupid', g);
        finalData.set('suggestion_counter', suggestion_counter);
        finalData.set('suggestion_page_counter', suggestion_page_counter);
		finalData.set('filter_attribute_type', filter_attribute_type);
        finalData.set('roleit_to_match', r);
	}
  
	$.ajax({
		url: './ajax_talentpeak.php?loadMoreDiscoverSuggestions=1',
		type: 'POST',
		processData: false,
        contentType: false,
        cache: false,
		data: finalData,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message});
			} catch(e) {
				$('#suggestions_byrole_container_'+r).append(data);
				$(".confirm").popConfirm({content: ''});
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
                $("#suggestion_page_"+r).val(parseInt(suggestion_page_counter)+1);
			}
		}
	});

}


</script>
