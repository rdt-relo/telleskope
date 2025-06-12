<?php $office_location ??= null; ?>
<div class="container col-md-offset-2 margin-top">
  <div class="row">
    <div class="col-sm-12">
      <div class="widget-simple-chart card-box">
        <div class="col-sm-12 divider">
          <h6><?= isset($office_location) ? gettext('Edit Event Location') : gettext('Create New Event Location') ?></h6>
        </div>

        <form action="event_office_locations" method="POST">
          <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
          <input type="hidden" name="event_office_location_id" value="<?= $office_location?->encodedId() ?? '' ?>">
          <div class="form-group row">
            <label class="col-sm-2 col-form-label"><?= gettext('Location Name') ?> <span style="color:red;">*</span></label>
            <div class="col-sm-10">
              <input
                type="text"
                class="form-control"
                placeholder="<?= gettext('Location Name') ?>"
                name="location_name"
                value="<?= $office_location?->val('location_name') ?? '' ?>"
              >
            </div>
          </div>

          <div class="form-group row">
            <label class="col-sm-2 col-form-label"><?= gettext('Location Address') ?> <span style="color:red;">*</span></label>
            <div class="col-sm-10">
              <input
                type="text"
                class="form-control"
                placeholder="<?= gettext('Location Address') ?>"
                name="location_address"
                value="<?= $office_location?->val('location_address') ?? '' ?>"
              >
            </div>
          </div>

          <div class="form-group row">
            <label class="col-sm-2 col-form-label">&nbsp;</label>
            <div class="col-sm-10">
              <button type="button" onclick="goBack()" class="btn btn-primary"><?= gettext('Cancel') ?></button>
              <button type="submit" class="btn btn-primary"><?= gettext('Save') ?></button>
            </div>
          </div>
        </form>
			</div>
		</div>
	</div>
</div>
