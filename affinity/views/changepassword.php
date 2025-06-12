<!-- <div class="container w2" style="background:url(img/img.png) no-repeat; background-size: cover; background-position: center;">
    <h1 class="h1-height">Change Password</h1>
</div> -->
<main>

<div class="as row-no-gutters" style="background:url(<?= $banner ? $banner : 'img/img.png'; ?>) no-repeat; background-size:cover; background-position:center;"></div>

<div class="container inner-background inner-background-tall">
	<div class="row row-no-gutters">
		<div class="col-md-12">
			<div class="col-md-10">
				<div class="inner-page-title">
					<h1><?= gettext('Change Password'); ?></h1>
				</div>
			</div>
		</div>
		<div class="col-md-12">
			<div class="form-container">
                <form class="form-horizontal" method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <?php if(isset($done)){ ?>
                    <div id="hidemesage" class="alert alert-info alert-dismissable">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">x</a>
                        <?= $done; ?>
                    </div>
                    <?php }elseif(isset($err)){ ?>
                    <div id="hidemesage" class="alert alert-danger alert-dismissable">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">x</a>
                        <?= $err; ?>
                    </div>
                    <?php } ?>

                    <div class="form-group">
                        <label class="control-lable col-sm-4"><?= gettext('Old Password'); ?></label>
                        <div class="col-sm-8">
                            <input type="password" class=" form-control" name="oldpassword" placeholder="<?= gettext('Old Password'); ?>.."
                                   autocomplete="off" required/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-lable col-sm-4"><?= gettext('New Password'); ?></label>
                        <div class="col-sm-8">
                            <input type="password" name="newpassword" class="form-control" placeholder="<?= gettext('New Password'); ?>.."
                                   autocomplete="off" required/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-lable col-sm-4"><?= gettext('Confirm Password'); ?></label>
                        <div class="col-sm-8">
                            <input type="password" name="confirmpassword" class="form-control"  placeholder="<?= gettext('Confirm Password'); ?>.."
                                   autocomplete="off" required/>
                        </div>
                    </div>
                    <div class="form-group">   
                        <div class="col-sm-12 text-center">                     
                            <button class="btn btn-affinity prevent-multi-clicks" type="submit" name="submit"><?= gettext('Submit'); ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</main>