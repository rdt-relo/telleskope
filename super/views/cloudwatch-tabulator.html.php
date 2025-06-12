<div class="container-offset-lr mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card widget-simple-chart p-3">
                <div class="card-body">
                    <h1>Cloudwatch Logs</h1>
                    <div class="col-12">
                        <h6> Company ID  <?php echo $_GET['company-id'] ?? ''; ?> </h6>
                    </div>

                    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="GET" class="form-horizontal">
                        <div class="mb-3">
                            <div class="col-lg-6">
                                <input id="company-id" class="form-control" type="hidden" name="company-id" value="<?php echo $_GET['company-id'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="user-id" class="col-lg-3 col-form-label text-end">User ID:</label>
                            <div class="col-lg-6">
                                <input id="user-id" class="form-control" type="number" name="user-id" value="<?php echo $_GET['user-id'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="user-email" class="col-lg-3 col-form-label text-end">User Email:</label>
                            <div class="col-lg-6">
                                <input id="user-email" class="form-control" type="email" name="user-email" value="<?php echo $_GET['user-email'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="zone-id" class="col-lg-3 col-form-label text-end">Zone ID:</label>
                            <div class="col-lg-6">
                                <input id="zone-id" class="form-control" type="number" name="zone-id" value="<?php echo $_GET['zone-id'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="search-by" class="col-lg-3 col-form-label text-end">Search By:</label>
                            <div class="col-lg-6">
                                <label>
                                    <input type="radio" name="search-by" value="interval" <?= ($_GET['search-by'] ?? 'interval') === 'interval' ? 'checked' : '' ?>>
                                    Interval
                                </label>
                                &nbsp;&nbsp;
                                <label>
                                    <input type="radio" name="search-by" value="date" <?= ($_GET['search-by'] ?? '') === 'date' ? 'checked' : '' ?>>
                                    Date
                                </label>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="relative-time" class="col-lg-3 col-form-label text-end">Search in last</label>
                            <div class="col-lg-6">
                                <select name="relative-time" id="relative-time" class="form-select" required>
                                    <?php foreach (RELATIVE_TIMES as $rt_k => $rt_v) { ?>
                                    <option value="<?=$rt_k?>" <?= (($_GET['relative-time'] ?? '5-minute') === $rt_k) ? 'selected' : '' ?> >
                                        <?=$rt_k?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="start-date" class="col-lg-3 col-form-label text-end">Start Date</label>
                            <div class="col-lg-6">
                                <input id="start-date" class="form-control" type="text" placeholder="Start Date" name="start-date" autocomplete="off" required value="<?= $_GET['start-date'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="start-time" class="col-lg-3 col-form-label text-end">Start Time (<?= $timezone->getName() ?>)</label>
                            <div class="col-lg-6">
                                <input id="start-time" class="form-control" type="time" placeholder="Start Time" name="start-time" autocomplete="off" required value="<?= $_GET['start-time'] ?? '00:00' ?>">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="end-date" class="col-lg-3 col-form-label text-end">End Date</label>
                            <div class="col-lg-6">
                                <input id="end-date" class="form-control" type="text" placeholder="End Date" name="end-date" autocomplete="off" required value="<?= $_GET['end-date'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="end-time" class="col-lg-3 col-form-label text-end">End Time (<?= $timezone->getName() ?>)</label>
                            <div class="col-lg-6">
                                <input id="end-time" class="form-control" type="time" placeholder="End Time" name="end-time" autocomplete="off" required value="<?= $_GET['end-time'] ?? '23:59' ?>">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="severity" class="col-lg-3 col-form-label text-end">Severity</label>
                            <div class="col-lg-6">
                                <select name="severity" id="severity" class="form-select">
                                    <option value="" <?= empty($_GET['severity']) ? 'selected' : ''; ?>>All</option>
                                    <?php foreach (Logger::SEVERITY as $severityKey => $severityValue) { ?>
                                        <option value="<?= $severityValue ?>" <?= ($_GET['severity'] ?? '') === $severityValue ? 'selected' : ''; ?>><?= $severityValue ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="module" class="col-lg-3 col-form-label text-end">Module</label>
                            <div class="col-lg-6">
                                <select name="module" id="module" class="form-select">
                                    <option value="" <?= empty($_GET['module']) ? 'selected' : ''; ?>>All</option>
                                    <?php foreach (Logger::MODULE as $moduleKey => $moduleValue) { ?>
                                        <option value="<?= $moduleValue ?>" <?= ($_GET['module'] ?? '') === $moduleValue ? 'selected' : ''; ?>><?= $moduleValue ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="search-keyword" class="col-lg-3 col-form-label text-end">Search keyword:</label>
                            <div class="col-lg-6">
                                <input id="search-keyword" class="form-control" type="text" name="search-keyword" value="<?= $_GET['search-keyword'] ?? '' ?>">
                                <small class="form-text text-muted">If searching by email, enter email hash instead of email; email hash can be generated using check user functionality.</small>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="max-count" class="col-lg-3 col-form-label text-end">Maximum Number of Records:</label>
                            <div class="col-lg-6">
                                <input id="max-count" class="form-control" type="number" name="max-count" value="<?php echo $_GET['max-count'] ?? '100'; ?>">
                            </div>
                        </div>
                        <div class="mb-3 text-center">
                            <button type="submit" name="add" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                    <br><br>
                    <!-- Tabulator table -->
                    <div>
                        <select id="filter-field" >
                            <option>Select a value</option>
                            <option value="zone_id">Zone id</option>
                            <option value="user_id">User id</option>
                            <option value="method">Method</option>
                        </select>

                        <select id="filter-type" >
                            <option value="=">=</option>
                            <option value="<"><</option>
                            <option value="<="><=</option>
                            <option value=">">></option>
                            <option value=">=">>=</option>
                            <option value="!=">!=</option>
                            <option value="like">like</option>
                        </select>

                        <input id="filter-value"  type="text" placeholder="Value to filter">

                        <button id="add-filter" class="btn btn-secondary btn-sm" style="margin-bottom: 1px;">Add Filter</button>
                        <button id="filter-clear" class="btn btn-danger btn-sm">Clear Filter</button>
                        <button id="download-csv" class="btn btn-success btn-sm">Download CSV</button>
                        <div id="currentFilters"></div>
                    </div>

                    <div id="example-table"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery('#menu-toggle').click(function(e) {
        e.preventDefault();
        jQuery('wrapper').toggleClass('toggled');
    });
</script>

<script>
    $('#sidebar-wrapper ul li:nth-child(9)').addClass('myactive');
</script>

<script>
    function renderDateFields(search_by) {
        $('#relative-time, #start-date, #start-time, #end-date, #end-time')
                .prop('disabled', true)
                .closest('div.form-group')
                .hide();

        if (search_by === 'interval') {
            $('#relative-time')
                .prop('disabled', false)
                .closest('div.form-group')
                .show();
            return;
        }

        $('#start-date, #start-time, #end-date, #end-time')
            .prop('disabled', false)
            .closest('div.form-group')
            .show();
    }
</script>
<script>
        $('#start-date, #end-date').datepicker({
            prevText: 'click for previous months',
            nextText: 'click for next months',
            showOtherMonths: true,
            selectOtherMonths: false,
            dateFormat: 'yy-mm-dd'
        });

        renderDateFields('<?= $_GET['search-by'] ?? 'interval' ?>');

        $('input[type=radio][name=search-by]').change(function() {
            renderDateFields(this.value);
        });
</script>
<script>
//define column header menu as column visibility toggle
var headerMenu = function(){
    var menu = [];
    var columns = this.getColumns();

    for(let column of columns){

        //create checkbox element using font awesome icons
        let icon = document.createElement("i");
        icon.classList.add("fas");
        icon.classList.add(column.isVisible() ? "fa-check-square" : "fa-square");

        //build label
        let label = document.createElement("span");
        let title = document.createElement("span");

        title.textContent = " " + column.getDefinition().title;

        label.appendChild(icon);
        label.appendChild(title);

        //create menu item
        menu.push({
            label:label,
            action:function(e){
                //prevent menu closing
                e.stopPropagation();

                //toggle current column visibility
                column.toggle();

                //change menu item icon
                if(column.isVisible()){
                    icon.classList.remove("fa-square");
                    icon.classList.add("fa-check-square");
                }else{
                    icon.classList.remove("fa-check-square");
                    icon.classList.add("fa-square");
                }
            }
        });
    }

   return menu;
};

</script>
<script>
        // Tabulator filters
        //Define variables for input elements
        var fieldEl = document.getElementById("filter-field");
        var typeEl = document.getElementById("filter-type");
        var valueEl = document.getElementById("filter-value");
        var addNew = document.getElementById("add-filter");

        document.getElementById("add-filter").addEventListener("click", function(){
        var filterVal = fieldEl.options[fieldEl.selectedIndex].value;
        var typeVal = typeEl.options[typeEl.selectedIndex].value;

        var filter = filterVal == "function" ? customFilter : filterVal;

        if(filterVal == "function" ){
            typeEl.disabled = true;
            valueEl.disabled = true;
        }else{
            typeEl.disabled = false;
            valueEl.disabled = false;
        }
                // get current filters
                var filtersArr = table.getFilters();
                // assign new filters to array
                var newFilters = {field:filter,type:typeVal, value: valueEl.value};
                // console.log(newFilters);
                filtersArr.push(newFilters);
                // console.log(filtersArr);
                // refresh
                table.refreshFilter();
                // set filter again
                table.setFilter(filtersArr);

                const container = document.getElementById("currentFilters");

                // Clear the container's content
                container.innerHTML = '';

                if (filtersArr && filtersArr.length > 0) {
                const filterbox = document.createElement("div");

                filtersArr.forEach(obj => {
                    const filterdiv = document.createElement("div");
                    filterdiv.style.background = "#2D75B8";
                    filterdiv.style.display = "inline-block";
                    filterdiv.style.marginRight = "10px";
                    filterdiv.style.borderRadius = "4px";
                    filterdiv.style.padding = "3px 6px";
                    filterdiv.style.color = "#fff";
                    filterdiv.style.textTransform = "Capitalize";


                    const filterline = document.createElement("p");
                    filterline.style.margin = "0";
                    filterline.textContent = `${obj.field}  ${obj.type}  ${obj.value}`;

                    filterdiv.appendChild(filterline);
                    filterbox.appendChild(filterdiv);
                });

                container.appendChild(filterbox);
            }

        });

        //Clear filters on "Clear Filters" button click
        document.getElementById("filter-clear").addEventListener("click", function(){
        fieldEl.value = "";
        typeEl.value = "=";
        valueEl.value = "";
        document.getElementById("currentFilters").innerHTML = "";
        table.clearFilter();
        });

        //trigger download of data.csv file
        document.getElementById("download-csv").addEventListener("click", function(){
            table.download("csv", "data.csv");
        });
        
        var table = new Tabulator("#example-table", {
            data: <?= json_encode($table_json) ?>,
 	        layout:"fitData",
            formatter:"rownum",
            pagination:"local",       
            paginationSize:100,
            paginationCounter:"rows",
            columnMaxWidth:100,
            autoColumns:true,
            autoColumnsDefinitions:function(definitions){
                 definitions.forEach((column) => {
                    column.headerFilter = true; 
                    column.headerMenu = headerMenu; // headermenu enable for show/hide code to work
                });

                return definitions;
            },
            persistence:{
                headerFilter: true, //use only if we need to keep header filters even on page refresh
                sort: true, 
                group: true, 
                page: false,
                columns: true,
            },
            movableRows: "true",
        });

</script>
