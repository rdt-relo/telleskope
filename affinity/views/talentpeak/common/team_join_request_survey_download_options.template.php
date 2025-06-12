<?php if ($showOnModel){ ?>
<div class="modal" tabindex="-1" role="dialog" id="download_join_request_options">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title"><?= gettext("Registration Report Download Options");?></h2>
        <button id="btn_close" type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
<?php } ?>
        <form class="form-horizontal" action="ajax_talentpeak.php?downloadUnmachedUsersSurveyResponses=<?= $_COMPANY->encodeId($groupid); ?>" method="POST" role="form" style="display: block;width:100% !important">
            <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
            <div class="mt-3" tyle="padding: 0 50px; border:1px solid rgb(223, 223, 223); padding-top:10px;">
            <?php if (!$showOnModel){ ?>
                <strong class="pt-3"><?= sprintf(gettext("%s Registration Report Download"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></strong>
                <hr>
            <?php } ?>
                    <input type="hidden" name="userid" value="<?= $_COMPANY->encodeId(0); ?>">
                    <input type="hidden" name="roleid" value="<?= $_COMPANY->encodeId(0); ?>">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="teams"><?= gettext("Download Registration Report Options"); ?></label>
                            <select class="form-control" name="downloadOptions[]" id="downloadOptions" multiple size="3">
                                <option value="<?= $_COMPANY->encodeId(2); ?>" selected><?= gettext("Download Registrations for Matched Users");?></option>
                                <option value="<?= $_COMPANY->encodeId(1); ?>" selected><?= gettext("Download Registrations for Unmatched Users");?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-2">&nbsp;</div>
                    <div class="col-sm-4">
                    <fieldset>
                        <legend style="font-size: 1.2rem;"><?= gettext("Select Fields"); ?></legend>
                            <div class="mb-2 text-sm">
                                <a role="button" href="javascript:void(0)" class="link_show" onclick="checkUncheckAllCheckBoxes('userOptionsMultiCheck',true)"> <?= gettext("Select All");?></a> | <a role="button" href="javascript:void(0)" class="link_show" onclick="checkUncheckAllCheckBoxes('userOptionsMultiCheck',false)"> <?= gettext("Deselect All");?></a>
                            </div>
                        <?php foreach($fields as $key => $value){ ?>
                            <input aria-label="<?= $value; ?>" class="userOptionsMultiCheck" type="checkbox" name="<?= $key; ?>" value="<?= $key; ?>" checked>&emsp;<?= $value; ?><br>
                        <?php } ?>
                        </fieldset>
                    </div>
                

            <div class="form-group mt-2">
                <div class="text-center">
                    <button type="submit" name="submit" class="btn btn-primary"><?= gettext("Download Report");?></button>
                    <?php if (!$showOnModel){ ?>
                        <button type="button" class="btn btn-primary" onclick='$("#reportDownLoadOptions" ).slideUp( "slow", function() { $("#reportDownLoadOptions").html(""); 
                        $("#registrationReport").focus();                         
                        });'><?= gettext("Close");?></button>
                    <?php } ?>
                    <hr>
                </div>
            </div>
        </form>
<?php if ($showOnModel){ ?>
    </div>
    </div>
  </div>
</div>
<?php } ?>
