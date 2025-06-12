<div class="container-fluid">
  <div class="form-group">
   
      <form action="" method="GET" class="form-horizontal">
        <div class="row">
          <div class="col-8 col-xs-10 col-sm-10 col-md-10 col-lg-10 pr-1">
            <input class="form-control" type="text" placeholder="<?=gettext('Enter text to search')?>" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" aria-label="<?=gettext('Enter text to search')?>">
          </div>
            <div class="col-4 col-xs-2 col-sm-2 col-md-2 col-lg-2">
                <button type="submit" class="btn btn-primary"><?=gettext('Search');?></button>
            </div>
        </div>

      </form>
    </div>
  </div>
