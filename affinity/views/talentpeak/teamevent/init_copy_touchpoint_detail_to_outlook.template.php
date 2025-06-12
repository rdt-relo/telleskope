
    <style>
        .margin-left-30 {
            margin-left: 30px;
        }
    </style>
    
    <!-- Start of Next Event section -->
    <div class="container inner-background inner-background-next-event">
        <div class="row">
            <div class="col-md-12 p-0">
                <div class="row upcoming-event-row">
                    <div class="col-1">
                        <i class="fa fa-lightbulb fa-2x pt-4" aria-hidden="true"></i>
                    </div>
                    <div class="col-11 pl-0">
                        <?= gettext("We recommend that you use Outlook to schedule your Touch Point. This will allow you to conveniently select a time that works for all participants. To make the process easier, you can copy this Touch Point's details and participants and paste them into your new meeting invite.");?>
                    </div>
                    <div class="col-12 text-center">
                        <button class="btn btn-link" onclick="initTouchPointDetailToOutlook('<?= $_COMPANY->encodeId($groupid) ?>','<?=$_COMPANY->encodeId($data[0]['teamid'])?>','<?=$_COMPANY->encodeId($data[0]['taskid'])?>');"><?= gettext("Copy Touch Point Details and Participants"); ?>&emsp;<i class="fa fa-copy" aria-hidden="true"></i></button>
                    </div>
                </div>
                <br/>
            </div>
        </div>
    </div>
    <!-- End of Next Event Section -->
 