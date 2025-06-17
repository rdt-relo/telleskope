<?php
#
# Note this file is also included by 1/native/views/createUpdateTouchPoint_event_html.php
# @todo move this component to common components directory to allow sharing across different sections
#
$search_in_google_maps = ($_COMPANY->getAppCustomization()['plugins']['google_maps']);

$event_office_locations = EventOfficeLocation::All();
$search_in_office_locations = (bool) count($event_office_locations);

$event ??= null;

$show_event_location_picker = true;
if ($event) {
  $show_event_location_picker = ($event->val('event_attendence_type') == 1 || $event->val('event_attendence_type')==3 || $event->val('event_attendence_type') == 0);
}

$col_label = isset($col_12) ? "col-sm-12" : "col-sm-2";
$col_value = isset($col_12) ? "col-sm-12" : "col-sm-10";
?>
<div id="venue_div" class="ui-front" <?= $show_event_location_picker ? '' : 'style="display: none;"' ?>>
  <div class="form-group">
    <label class="control-lable <?=$col_label?>" for="autocomplete"><?= gettext('Venue');?><span style="color: #ff0000;"> *</span></label>
    <div class="<?=$col_value?>">
      <div <?= ($search_in_google_maps && $search_in_office_locations) ? '' : 'style="display:none;"' ?> role="group" aria-label="<?= gettext('Venue search in') ?>">
        <?= gettext('Search in') ?> &nbsp;
        <div class="form-check form-check-inline">
          <label class="form-check-label">
            <input class="form-check-input js-search-in-office-locations" type="checkbox" value="office_locations" <?= $search_in_office_locations ? 'checked' : '' ?>>
            <?= gettext('Office Locations') ?>
          </label>
        </div>
        <div class="form-check form-check-inline">
          <label class="form-check-label">
            <input class="form-check-input js-search-in-google-maps" type="checkbox" value="google_maps" <?= $search_in_google_maps ? 'checked' : '' ?>>
            <?= gettext('Google Maps') ?>
          </label>
        </div>
      </div>
      <div class="form-row">
        <div class="col">
          <input type="text" id="autocomplete" name="eventvanue" required class="form-control js-event-venue-input" placeholder="<?= gettext('Event venue');?>" value="<?= $event?->val('eventvanue') ?? '' ?>" />
        </div>
        <div class="col-auto">
          <div class="spinner-border text-primary js-spinner" role="status" style="display: none;">
            <span class="sr-only">Loading...</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($show_additional_location_fields)) { ?>
  <div class="form-group">
      <label class="control-lable col-sm-12" for="venueroom"><?= gettext('Room or Meeting Point');?></label>
      <div class="col-sm-12">
          <input type="text" id="venueroom" name="venue_room" value="<?= $event ? htmlspecialchars_decode($event->val('venue_room')) : ''; ?>" class="form-control" placeholder="<?= gettext('Room or Meeting Point, maximum 64 characters');?>" />
      </div>
  </div>
  <?php } ?>

  <div class="form-group">
    <label class="control-lable <?=$col_label?>" for="vanueaddress"><?= gettext('Address');?><span style="color: #ff0000;"></span></label>
    <div class="<?=$col_value?>">
      <textarea class="form-control js-event-venue-address" id="vanueaddress" name="vanueaddress" placeholder="<?= gettext('Address');?>"><?= $event?->val('vanueaddress') ?? '' ?></textarea>
    </div>
  </div>

  <?php if (!empty($show_additional_location_fields)) { ?>
  <div class="form-group">
    <label class="control-lable col-sm-12" for="venueinfo"><?= gettext('Additional Information');?> </label>
    <div class="col-sm-12">
      <input type="text" id="venueinfo" name="venue_info" value="<?= $event ? htmlspecialchars_decode($event->val('venue_info')) : ''; ?>" class="form-control" placeholder="<?= gettext('what to bring, what to wear, parking, etc., maximum 256 characters');?>" />
    </div>
  </div>
  <?php } ?>

  <?php if ($search_in_office_locations) { ?>
    <template class="js-office-locations-list">
      <?= json_encode(array_map(function (EventOfficeLocation $event_office_location) {
        return [
          'location_name' => $event_office_location->val('location_name'),
          'location_address' => $event_office_location->val('location_address'),
        ];
      }, $event_office_locations)) ?>
    </template>
  <?php } ?>
</div>

<script>
  window.tskp ||= {};

  window.tskp.event_location_picker = {
    container: '#venue_div',
    $container: null,
    show_all_office_locations: null,

    init: function () {
      this.$container = $(this.container);

      <?php if ($search_in_google_maps) { ?>
        if (!window.google?.maps) {
          $.ajaxSetup({cache: true});
          $.getScript("https://maps.googleapis.com/maps/api/js?key=<?=GOOGLE_MAPS_API_KEY?>&libraries=places", this.init.bind(this));
          return;
        }
      <?php } ?>

      this.initAutocomplete();

      this.$container.find('.js-search-in-office-locations, .js-search-in-google-maps').change((function () {
        this.$container.find('.js-event-venue-input').autocomplete('search');
      }).bind(this));
    },

    initAutocomplete: function () {
      this.$container.find('.js-event-venue-input').autocomplete({
        source: (function (request, response) {
          var search_in_office_locations = this.$container.find('input.js-search-in-office-locations').is(':checked');
          var search_in_google_maps = this.$container.find('input.js-search-in-google-maps').is(':checked');

          if (!search_in_office_locations && !search_in_google_maps) {
            return response([]);
          }

          if (!search_in_google_maps) {
            this.show_all_office_locations = true;
          }

          var results = [];

          if (search_in_office_locations) {
            results = this.searchOfficeLocations(request.term);
          }

          if (!search_in_google_maps) {
            return response(results);
          }

          if (request.term.trim().length < 3) {
            return response(results);
          }

          this.searchGoogleMaps(request.term, function (predictions) {
            if (results.length) {
              results[results.length - 1].add_line_delimiter = true;
            }

            results = results.concat(predictions);

            response(results);
          });

        }).bind(this),

        select: (function (event, ui) {
          event.preventDefault();

          if (ui.item.google_place_id) {
            return this.selectGoogleMapsPlace(ui.item);
          }

          this.selectOfficeLocation(ui.item);

        }).bind(this),

        close: (function( event, ui ) {
          this.show_all_office_locations = false;
        }).bind(this),
      })
      .autocomplete('instance')._renderItem = (function (ul, item) {
        var html;
        if (item.google_place_id) {
          html = this.renderGooglePlaceItem(item);
        } else {
          html = this.renderOfficeLocationItem(item);
        }

        return $('<li>').append(html).appendTo(ul);
      }).bind(this)
    },

    searchOfficeLocations: function (search_input) {
      var json = this.$container.find('.js-office-locations-list').html().trim();
      var results = JSON.parse(json);
      var search_keywords = search_input.trim().split(/\s+/);

      results = results.map(function (office_location) {
        office_location.label = office_location.location_name + ' ' + office_location.location_address;
        return office_location;
      });

      for (var i = 0; i < search_keywords.length; i++) {
        results = $.ui.autocomplete.filter(results, search_keywords[i]);
      }

      if (this.show_all_office_locations) {
        return results;
      }

      if (results.length <= 6) {
        return results;
      }

      var total_results = results.length;
      results = results.slice(0, 6);
      results[results.length - 1].show_more = `+ ${total_results - 6} office locations`;

      return results;
    },

    searchGoogleMaps: function (search_input, callback) {
      var spinner = this.$container.find('.js-spinner');
      spinner.show();

      var autocomplete_service = new google.maps.places.AutocompleteService();
      autocomplete_service.getPlacePredictions({
        input: search_input
      }, function (predictions) {
        spinner.hide();

        if (!predictions) {
          return;
        }

        predictions = predictions.map(function (prediction) {
          return {
            google_place_id: prediction.place_id,
            label: prediction.description,
            structured_formatting: prediction.structured_formatting,
          };
        });

        predictions[predictions.length - 1].show_google_attribution = true;

        callback(predictions);
      });
    },

    showAllOfficeLocations: function (e) {
      event.preventDefault();
      event.stopPropagation();

      this.show_all_office_locations = true;
      this.$container.find('.js-event-venue-input').autocomplete('search');
    },

    fillInAddress: function (place) {
      var componentForm = {
        street_number: 'short_name',
        route: 'long_name',
        locality: 'long_name',
        administrative_area_level_1: 'short_name',
        country: 'long_name',
        postal_code: 'short_name'
      };

      var val = '';
      for (var i = 0; i < place.address_components.length; i++) {
        var addressType = place.address_components[i].types[0];
        if (componentForm[addressType]) {
          val += place.address_components[i][componentForm[addressType]] + ', ';
        }
      }
      this.$container.find('.js-event-venue-address').val(val.slice(0, -2));
    },

    renderGooglePlaceItem: function (item) {
      // Cached the 'Powered by Google' logo on our own ..
      // Original: https://maps.gstatic.com/mapfiles/api-3/images/powered-by-google-on-white3.png
      // Cached: /1/image/gmaps/powered-by-google-on-white3.png
      return `
        <div>
          ${item.label}

          ${item.show_google_attribution ? `
            <p class="text-right">
              <img src="/1/image/gmaps/powered-by-google-on-white3.png" alt="Powered by Google">
            </p>
          ` : ''}
        </div>
      `;
    },

    renderOfficeLocationItem: function (item) {
      return `
        <div>
          ${item.location_name} <${item.location_address}>

          ${item.show_more ? `
            <br>
            <a href="javascript:void(0);" class="text-primary float-right" onclick="window.tskp.event_location_picker.showAllOfficeLocations(event)">
              <small>${item.show_more}</small>
            </a>
            <br>
          `: ''}

          ${item.add_line_delimiter ? '<hr>': ''}
        </div>
      `;
    },

    selectGoogleMapsPlace: function (item) {
      var spinner = this.$container.find('.js-spinner');
      spinner.show();

      /**
       * The value of attribution_container doesn't matter much
       * Google just wants us to show the attribution where we show their places data
       * https://stackoverflow.com/questions/19499234/what-is-the-condition-of-showing-googles-attribution-on-google-maps-api-android
       * https://developers.google.com/maps/documentation/javascript/place-autocomplete#places-searchbox
       */
      var attribution_container = document.createElement('div');
      var places_service = new google.maps.places.PlacesService(attribution_container);
      places_service.getDetails({
        placeId: item.google_place_id,
        fields: ['address_components'],
      }, (function (place) {
        spinner.hide();
        this.fillInAddress(place);
      }).bind(this));

      var venue_input = this.$container.find('.js-event-venue-input');
      venue_input.val(item.structured_formatting.main_text);
      return;
    },

    selectOfficeLocation: function (item) {
      var venue_input = this.$container.find('.js-event-venue-input');
      venue_input.val(item.location_name);

      var address_input = this.$container.find('.js-event-venue-address');
      address_input.val(item.location_address);
    }
  };

  $(function () {
    window.tskp.event_location_picker.init();
  });
</script>

