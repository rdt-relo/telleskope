<div class="container inner-background myteam">
    <div class="row row-no-gutters">
        <div class="col-md-12 col-xs-12">
            <div class="inner-page-title">
                <h1> <?= $documentTitle = sprintf(gettext("My %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></h1>
            </div>
        </div>
        <hr class="lineb">
    
        <div class="col-md-12 p-0"> 
        <p class="text-center">
        <?= sprintf(gettext("You have not started the %s program yet. Please start now."),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?>   <br><br>
        <button class="btn btn-affinity ml-3 btn-link pop-identifier confirm" data-toggle="popover" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= sprintf(gettext('Are you sure you want to start %s program?'),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1)); ?>" onclick="initMyTeamsContainer('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>')">
            <?= gettext('Start Now');?>
        </button>
        </p>
        </div>

    </div>
</div>

<script>
	$('.pop-identifier').each(function() {
		$(this).popConfirm({
		container: $(".myteam"),
		});
	});

    updatePageTitle('<?= addslashes(sprintf(gettext('%1$s | %2$s'), $documentTitle, $_COMPANY->val('companyname')));?>');
</script>