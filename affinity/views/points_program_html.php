<style>
    .inner-page h2 {    
    margin-bottom:5px;
}
    </style>
<div class="as row-no-gutters"
     style="background: url(<?= $banner ? $banner : 'img/img.png'?>) no-repeat; background-size:cover; background-position:center;">
    <h1 style="width: 100%; padding-top:85px;"><?= $bannerTitle; ?></h1>
</div>
<div class="container inner-background">
<div class="row row-no-gutters">
    <div class="mt-4">
    <div class="container-sub">
    <div class="row col-12">
       <!-- <div class="col-12">
            <div class="col-12">
                <div class="inner-page">
                <h2> <?= gettext("Points Program Name")?></h2>
                <p><?= gettext("ABC");?></p>               
                </div>
            
            </div>
        </div>-
        <div class="col-12 mt-4">
            <div class="col-12">
                <div class="inner-page">
                <h2> <?= gettext("Points Program Description")?></h2><p> <?= gettext(" ");?></p>               
                </div>
               
            </div>
        </div>-->
    
        <div class="col-12 mt-4">
            <div class="col-12">
                <div class="">
                <h2> <?= gettext("Points Earning History")?> <span style="float:right;"></span></h2>
                <hr class="linec">
                </div>
            </div>
       
        <div class="col-12">
                <div class="col-12">
                <div class="table-responsive">                
                <table class="table table-hover mb-3 display compact" id="pointsTransactions" summary="This table display the list of joined groups and chapters">
                    <thead>
                    <tr>          
                        <th width="20%" scope="col" tabindex="0" role="button"><?= gettext('Points'); ?></th>
                        <th width="20%" scope="col" tabindex="0" role="button"><?= gettext('Date/Time'); ?></th>

                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pointsTransactions as $points) { ?>
                        <tr>                           
                            <td><?= $_USER->formatAmountForDisplay($points['amount']); ?></td>
                            <td><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($points['created_at'],true,true,false);?></td>
                        </tr>
                    <?php } ?>
                    <tr><td><h2><?= sprintf(gettext("Total Points (%s)"),$_USER->getPointsBalance(true));?></h2></td><td></td></tr>
                    </tbody>
                </table>
            </div>
                </div>
            </div>
        </div>
    </div>
</div>
       
    </div>
</div>


</div>
</div>
</div>