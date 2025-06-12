
<div class="container inner-background">
    <div class="row row-no-gutters w-100">

        <div class="col-md-12">
            <div class="col-md-12 col-xs-12">
                <div class="inner-page-title">
                    <h1 id="tab_name"><?= $customTabDetail['tab_name'] .' - '. $group->val('groupname'); ?></h1>
                </div>
            </div>
        </div>
        <hr class="lineb" >
        <div class="col-md-12 mt-3">
            <div class="content-container">     
                
           <?php 
                if($customTabDetail['tab_type'] == "yammer"){               
                    echo $customTabDetail['tab_html'];
                }else if($customTabDetail['tab_type'] == "streams"){
                    echo $customTabDetail['tab_html'];
                }else if($customTabDetail['tab_type'] == "custom"){ 
            ?>
                    <div id="post-inner">
                        <?= $customTabDetail['tab_html']; ?>
                    </div>
            <?php  
                }
            ?>

            </div>
        </div>
    </div>
</div>