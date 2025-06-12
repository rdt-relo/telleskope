<?php
$encGroupId = $_COMPANY->encodeId($groupid);
?>
<style>
.card {
    background-color: #fff;
    color:#505050;
    border-radius: 10px;
    border: none;
    position: relative;
    margin-bottom: 25px;
    box-shadow: 0 0.46875rem 2.1875rem rgba(90,97,105,0.1), 0 0.9375rem 1.40625rem rgba(90,97,105,0.1), 0 0.25rem 0.53125rem rgba(90,97,105,0.12), 0 0.125rem 0.1875rem rgba(90,97,105,0.1);
}
.card:hover{
    background: linear-gradient(to left, #dbdbdb, #f4f7fc) !important;
}
.swal2-timer-progress-bar {
    background: #0077b5;
}
</style>

<div class="container inner-background">
    <div class="row row-no-gutters w-100">

        <div class="col-md-12">
            <div class="col-md-12">
                <div class="inner-page-title">
                    <h1>
                    <?= $documentTitle = sprintf(gettext('My %s'),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1)) .' - '. $group->val('groupname'); ?>
                    </h1>
                </div>
            </div>
        </div>
        <hr class="lineb" >


    <?php if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER'] || $group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?>
        <div class="col-12">
            <div class="inner-page-title m-0">
                <ul class="nav nav-tabs js-circles-navbar" role="tablist">
                    <li class="nav-item" role="none">
                        <a role="tab" class="nav-link <?= isset($_GET['getMyTeams']) ? 'active' : '' ?>" href="javascript:void(0);" tabindex="0" role="tab" aria-haspopup="true" aria-selected="true" data-toggle="tab"  id="myteams" onclick="getMyTeams('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($chapterid); ?>')" ><?= sprintf(gettext("My %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></a>
                    </li>
                    <?php if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER']){ ?>  
                        <li class="nav-item" role="none">
                            <a class="nav-link" href="javascript:void(0);" tabindex="-1" role="tab" aria-haspopup="true" aria-selected="false" data-toggle="tab"  id="initDiscoverTeamMembers" onclick="initDiscoverTeamMembers('<?= $_COMPANY->encodeId($groupid); ?>')"><?= gettext('Discover'); ?></a>
                        </li>

                        <li class="nav-item" role="none">
                            <a class="nav-link" href="javascript:void(0);" tabindex="-1" role="tab" aria-haspopup="true" aria-selected="false" data-toggle="tab"  id="getTeamInvites" onclick="getTeamInvites('<?= $_COMPANY->encodeId($groupid); ?>')"><?= gettext('Requests Sent'); ?></a>
                        </li>

                        <li class="nav-item" role="none">
                            <a class="nav-link" href="javascript:void(0);" tabindex="-1" role="tab" aria-haspopup="true" aria-selected="false" data-toggle="tab"  id="getTeamReceivedRequests" onclick="getTeamReceivedRequests('<?= $_COMPANY->encodeId($groupid); ?>')"><?= gettext('Requests Received'); ?></a>
                        </li>
                    <?php }else { ?>
                        <li class="nav-item" role="none">
                            <a class="nav-link js-discover-circles-navitem <?= isset($_GET['discoverCircles']) ? 'active' : '' ?>" href="javascript:void(0);" tabindex="-1" role="tab" aria-haspopup="true" aria-selected="false" data-toggle="tab"  id="initDiscoverCircles" onclick="initDiscoverCircles('<?= $_COMPANY->encodeId($groupid); ?>')"><?= gettext('Discover Circles'); ?></a>
                        </li>
                    <?php } ?>
                    
                    
                    <?php if (Team::CanCreateCircleByRole($groupid, $_USER->id())) { ?>
                        <li class="ml-auto js-start-circles-btn align-self-center" role="none">
                            <?php if (Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['TEAMS__CIRCLE_CREATE_BEFORE'], $groupid)) {
                                $encoded0 = $_COMPANY->encodeId(0);
                                $callOtherMethod = base64_url_encode(json_encode(array("method" => "openNewTeamModal", "parameters" => array($encGroupId, $encoded0, 'myTeams')))); // base64_encode for prevent js parsing error
                                $hookid = $_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['TEAMS__CIRCLE_CREATE_BEFORE']);
                                $on_create_function = "loadDisclaimerByHook('{$hookid}', '{$encGroupId}', '0', '{$callOtherMethod}')";
                            } else {
                                $encoded0 = $_COMPANY->encodeId(0);
                                $on_create_function = "openNewTeamModal('{$encGroupId}', '{$encoded0}', 'myTeams')";
                            }
                            ?>
                            <button type="button" class="btn btn-sm btn-affinity"  onclick="<?= $on_create_function ?>"><?= gettext("Start A Circle") ?></button>
                        </li>
                    <?php } ?>

                </ul>
            </div>
        </div>
    <?php } ?>
        
    <div class="col-12 min-vh-100 w-100" id="dynamicContent">

    </div>
</div>
</div>

<script>
 <?php if (Team::CanCreateNetworkingTeam($groupid, $group->getTeamProgramType())){ ?>
    Swal.fire({
        title: '<?= addslashes(gettext('Searching for the best match')); ?>',
        html: '<?= addslashes(gettext('We are searching for the best match for your requested role. Please wait.')); ?>',
        timer: 30000,
        timerProgressBar: true,
    });
    discoverTeamMembers('<?= $_COMPANY->encodeId($groupid); ?>')

<?php } ?>

    updatePageTitle('<?= addslashes(sprintf(gettext('%1$s | %2$s'), $documentTitle, $_COMPANY->val('companyname')));?>');

    $('.js-discover-circles-navitem')
        .on('shown.bs.tab', function () {
            $('.js-start-circles-btn').hide();
        })
    .on('hide.bs.tab', function () {
        $('.js-start-circles-btn').show();
    });


    $( document ).ready(function() {
        var hash = window.location.hash.substr(1);
        if (hash){
            if(hash.indexOf('getMyTeams/') != -1) {
                var hashArray  = hash.split('/');
                if (hashArray.length == 2){
                    let subHashArray = hashArray[1].split('-');
                    if (subHashArray.length == 2) {
                        $("#"+subHashArray[0]).trigger("click");
                        getTeamDetail('<?= $_COMPANY->encodeId($groupid); ?>',subHashArray[1],0,1)
                    } else {
                        $("#"+hashArray[1]).trigger("click");
                    }
                }
            } else if(hash.startsWith('circles/hashtags')){
                $('#initDiscoverCircles').trigger('click');
            }else {
                getMyTeams('<?=$_COMPANY->encodeId($groupid);?>','<?= $_COMPANY->encodeId($chapterid); ?>');
                $('#myteams').addClass('active');
            }
        } else {
            getMyTeams('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($chapterid); ?>');
            $('#myteams').addClass('active');
        }
    });


    $(function() {                       
        $(".nav-link").click(function() { 
        $('.nav-link').attr('tabindex', '-1');
        $(this).attr('tabindex', '0');    
        });
    });
  
    $('.nav-link').keydown(function(e) {  
        if (e.keyCode == 39) {       
            $(this).parent().next().find(".nav-link:last").focus();       
        }else if(e.keyCode == 37){       
            $(this).parent().prev().find(".nav-link:last").focus();  
        }
    });

</script>