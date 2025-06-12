<style>
    .card {
        width: 31%;
    }
</style>

<style>
    .info-card {
        -webkit-perspective: 1000px;
        width: 100%;
        height: 160px;
        cursor: pointer;
    }

    .front, .back {
        background: #FFF;
        transition: -webkit-transform 0.1s;
        -webkit-backface-visibility: hidden;
        border: .02em solid #ddd;
        border-radius: 5px;
        box-shadow: 0px 2px 8px 4px rgba(0, 0, 0, 0.2);
        height: 160px;
    }

    .front {
        position: absolute;
        z-index: 1;
        width: 100%;
        height: 100%;
        padding-top: 25px;
    }

    /* .back {
        -webkit-transform: rotateY(-180deg);
        width: 100%;
        height: 100%;
    }

    .info-card:hover .back {
        -webkit-transform: rotateY(0);
    }

    .info-card:hover .front {
        -webkit-transform: rotateY(180deg);
    }  */

    .info-card.toggleFlip .front {
        -webkit-transform: rotateY(180deg);
        width: 100%;
        height: 100%;
        box-shadow: 0px 2px 8px 4px rgba(0, 0, 0, 0.2);
    }

    .col-12 {
        margin-bottom: 20px;
    }

    .title-left {
        float: left;
        margin-top: 0px !important;
        margin-right: 10px;
    }
    .download-stats {
        position: absolute;
        right: 0;
        margin-right: 5px;
        z-index: 9999;
    }
    .text-center {
        display: block;
    }
</style>

<script>
    function createChart(id, label, data, title) {
        var chartData = {
            labels: JSON.parse(label),
            datasets: [
                {
                    fill: false,
                    borderColor: '#0077b5',
                    backgroundColor: '#2196f3',
                    borderWidth: 1,
                    data: JSON.parse(data),
                    label: title,
                }
            ]
        }
        var chartCanvas = document.getElementById(id);
        var chart = new Chart(chartCanvas, {
            type: 'line',
            data: chartData,
            options: {
                plugins: {
                    datalabels: {
                        display: false,
                    },
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            suggestedMin: 0,
                            beginAtZero: true,
                            userCallback: function (label, index, labels) {
                                if (Math.floor(label) === label) {
                                    if (label > 999) {
                                        return label / 1000 + 'k';
                                    } else {
                                        return label;
                                    }
                                }
                            },
                        }
                    }],
                }
            }
        });

         // Create the button container
         var buttonContainer = document.createElement('div');
        buttonContainer.classList.add('dropdown', 'mt-2' , 'pull-right', 'download-stats');
        
        // Create the button toggle icon
        var buttonToggleIcon = document.createElement('i');
        buttonToggleIcon.classList.add('dropdown-toggle', 'fa', 'fa-ellipsis-v', 'fa-sm');        
        buttonToggleIcon.setAttribute('data-toggle', 'dropdown');
        buttonToggleIcon.setAttribute('role', 'button'); // Add 'role' attribute with value 'button' for accessibility
        buttonToggleIcon.setAttribute('tabindex', '0'); // Add 'tabindex' attribute to make the element focusable
        buttonToggleIcon.setAttribute('aria-label', '<?= addslashes(gettext("More option for"))?> '+title); 
        
        // For accessibility through tab and enter or space key
        buttonToggleIcon.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            buttonToggleIcon.click(); // Programmatically trigger a click event on the buttonToggleIcon
            dropdownMenu.classList.add('show'); // Add the 'show' class to display the dropdown menu
            dropdownMenu.firstChild.focus(); // Set focus to the first item in the dropdown menu
            }
        });

        buttonContainer.appendChild(buttonToggleIcon);

        
        // Create the dropdown menu
        var dropdownMenu = document.createElement('ul');
        dropdownMenu.classList.add('dropdown-menu', 'dropdown-menu-right');
        buttonContainer.appendChild(dropdownMenu);
        
        // Create the export to CSV button
        var exportButton = document.createElement('li');
        var exportLink = document.createElement('a');
        exportLink.setAttribute('id', id + '-export-csv');
        exportLink.setAttribute('href', 'javascript:void(0)');
        exportLink.innerText = '<?= gettext("Export to CSV");?>';
        exportLink.addEventListener('click', function() {
            exportChartData(chartData);
        });
        exportButton.appendChild(exportLink);
        dropdownMenu.appendChild(exportButton);

        // Insert the button container before the chart canvas
        chartCanvas.parentNode.insertBefore(buttonContainer, chartCanvas);
    }

    function exportChartData(chartData) {
        var csvContent = "data:text/csv;charset=utf-8,";
        
        // Add labels row
        var labels = chartData.labels;
        csvContent += 'Label,' + labels.join(',') + '\n';
        
        // Add data rows
        var datasets = chartData.datasets;
        for (var i = 0; i < datasets.length; i++) {
            var data = datasets[i].data;
            csvContent += datasets[i].label + ',' + data.join(',') + '\n';
        }
        
        // Create a temporary link element to initiate the download
        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "chart_data.csv");
        
        // Trigger the link to start the download
        link.click();
    }
</script>
<div class="row">

    <div class="col-md-12 pl-4 pr-4 m-0">
        <div class="row">
            <div class="col-10">
                <h2>
                    <?= sprintf(gettext("%s Statistics"), $_COMPANY->getAppCustomization()['group']["name-short"]) .' - '. $group->val('groupname_short'); ?>
                </h2>
            <p class="reference-text">
                <?= sprintf(gettext("Please note that, other than membership statistics, all other statistics on this website are updated once a day and they were last updated on %s. Only the most current data point is updated, meaning that if a member leaves, the member count for previous months will not change, only the current month. Membership statistics are updated in real time."), "<strong>{$groupStatsAsOf}</strong>"); ?>
            </p>
            </div>
            <div class="col-2 text-right">
                <?php
                $page_tags = 'group_dashboard';
                ViewHelper::ShowTrainingVideoButton($page_tags);
                ?>
            </div>
        </div>
        <hr class="linec">
    </div>
    <div class="col-md-12 pt-3">
        <div class="col-md-4 col-12">
            <div role="button" class="info-card text-center" tabindex="0">
                <div class="front">
                    <p><span aria-label="<?= sprintf(gettext('Total %1$s Members %2$s, Select to flip the card'), $_COMPANY->getAppCustomization()['group']["name-short"], $_USER->formatNumberForDisplay($user_members_group)); ?> ">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span> &nbsp; <span aria-hidden="true"><?= sprintf(gettext("Total %s Members"), $_COMPANY->getAppCustomization()['group']["name-short"]); ?></span> <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the statistics of the total number of members in the %s."), $_COMPANY->getAppCustomization()['group']["name-short"]); ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the statistics of the total number of members in the %s."), $_COMPANY->getAppCustomization()['group']["name-short"]); ?>"></i> 
                   
                    </p>
                    <br/>
                    <h4 aria-hidden="true">
                    <?= $_USER->formatNumberForDisplay($user_members_group); ?></h4>
                </div>
                <div class="back">
                    <canvas  id="user_members_group" width="290px" height="150" role="img" aria-label="<?= sprintf(gettext("Graph data of Total %s Members"), $_COMPANY->getAppCustomization()['group']["name-short"]); ?>"></canvas>
                    <script>
                        createChart('user_members_group', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_user_members_group)?>', '<?= sprintf(gettext("Total %s Members"), $_COMPANY->getAppCustomization()['group']["name-short"]); ?>');
                    </script>
                </div>
            </div>
        </div>

    <?php if ($_COMPANY->getAppCustomization()['stats']['enabled'] && $group?->isTeamsModuleEnabled()) { ?>
        <!-- Total Active {Team Custom Name} -->
        <?php if($_COMPANY->getAppCustomization()['stats']['teams_active']) { ?>
            <div class="col-md-4 col-12">
                <div role="button" class="info-card text-center" tabindex="0">
                    <div class="front">
                        <p><span aria-label="<?= sprintf(gettext('Total Active %1$s %2$s, Select to flip the card'), $teamsCustomName, $_USER->formatNumberForDisplay($total_teams_active)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= sprintf(gettext('Total Active %1$s'), $teamsCustomName); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of Active Teams in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of Active Teams in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>"></i></p>
                        <br/>
                        <h4  aria-hidden="true"><?= $_USER->formatNumberForDisplay($total_teams_active); ?></h4>
                    </div>
                    <div class="back">
                        <canvas id="total_teams_active" width="290px" height="150" role="img" aria-label="<?= sprintf(gettext('Graph data of Total Active %1$s'), $teamsCustomName); ?>"></canvas>
                        <script>
                            createChart('total_teams_active', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_total_teams_active)?>', '<?= sprintf(gettext('Total Active %1$s'),$teamsCustomName); ?>');
                        </script>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Total Completed {Team Custom Name} -->
        <?php if($_COMPANY->getAppCustomization()['stats']['teams_completed']) { ?>
            <div class="col-md-4 col-12">
                <div role="button" class="info-card text-center" tabindex="0">
                    <div class="front">
                        <p><span aria-label="<?= sprintf(gettext('Total Completed %1$s %2$s, Select to flip the card'), $teamsCustomName, $_USER->formatNumberForDisplay($total_teams_completed)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= sprintf(gettext('Total Completed %1$s'), $teamsCustomName); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of Completed Teams in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of Completed Teams in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>"></i></p>
                        <br/>
                        <h4  aria-hidden="true"><?= $_USER->formatNumberForDisplay($total_teams_completed); ?></h4>
                    </div>
                    <div class="back">
                        <canvas id="total_teams_completed" width="290px" height="150" role="img" aria-label="<?= sprintf(gettext('Graph data of Total Completed %1$s'), $teamsCustomName); ?>"></canvas>
                        <script>
                            createChart('total_teams_completed', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_total_teams_completed)?>', '<?= sprintf(gettext('Total Completed %1$s'),$teamsCustomName); ?>');
                        </script>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Total Not Completed {Team Custom Name} -->
        <?php if($_COMPANY->getAppCustomization()['stats']['teams_not_completed']) { ?>
            <div class="col-md-4 col-12">
                <div role="button" class="info-card text-center" tabindex="0">
                    <div class="front">
                        <p><span aria-label="<?= sprintf(gettext('Total Incomplete %1$s %2$s, Select to flip the card'), $teamsCustomName, $_USER->formatNumberForDisplay($total_teams_not_completed)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= sprintf(gettext('Total Incomplete %1$s'), $teamsCustomName); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of Incomplete Teams in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of Incomplete Teams in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>"></i></p>
                        <br/>
                        <h4  aria-hidden="true"><?= $_USER->formatNumberForDisplay($total_teams_not_completed); ?></h4>
                    </div>
                    <div class="back">
                        <canvas id="total_teams_not_completed" width="290px" height="150" role="img" aria-label="<?= sprintf(gettext('Graph data of Total Incomplete %1$s'), $teamsCustomName); ?>"></canvas>
                        <script>
                            createChart('total_teams_not_completed', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_total_teams_not_completed)?>', '<?= sprintf(gettext('Total Incomplete %1$s'),$teamsCustomName); ?>');
                        </script>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Total Active Mentor -->
        <?php if($_COMPANY->getAppCustomization()['stats']['teams_mentors_active']) { ?>
            <div class="col-md-4 col-12">
                <div role="button" class="info-card text-center" tabindex="0">
                    <div class="front">
                        <p><span aria-label="<?= sprintf(gettext('Total Active Mentors %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($total_teams_mentors_active)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Active Mentors"); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of Active Mentors in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of Active Mentors in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>"></i></p>
                        <br/>
                        <h4  aria-hidden="true"><?= $_USER->formatNumberForDisplay($total_teams_mentors_active); ?></h4>
                    </div>
                    <div class="back">
                        <canvas id="total_teams_mentors_active" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Active Mentors"); ?>"></canvas>
                        <script>
                            createChart('total_teams_mentors_active', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_total_teams_mentors_active)?>', '<?= gettext("Total Active Mentors"); ?>');
                        </script>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Total Completed Mentor -->
        <?php if($_COMPANY->getAppCustomization()['stats']['teams_mentors_completed']) { ?>
          <div class="col-md-4 col-12">
                <div role="button" class="info-card text-center" tabindex="0">
                    <div class="front">
        <p><span aria-label="<?= sprintf(gettext('Total Completed Mentors %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($total_teams_mentors_completed)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Completed Mentors"); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of Completed Mentors in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of Completed Mentors in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>"></i></p>
                        <br/>
                        <h4  aria-hidden="true"><?= $_USER->formatNumberForDisplay($total_teams_mentors_completed); ?></h4>
                    </div>
                    <div class="back">
                        <canvas id="total_teams_mentors_completed" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Completed Mentors"); ?>"></canvas>
                        <script>
                            createChart('total_teams_mentors_completed', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_total_teams_mentors_completed)?>', '<?= gettext("Total Completed Mentors"); ?>');
                        </script>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Total Completed Mentor -->
        <?php if($_COMPANY->getAppCustomization()['stats']['teams_mentors_not_completed']) { ?>
            <div class="col-md-4 col-12">
                <div role="button" class="info-card text-center" tabindex="0">
                    <div class="front">
                        <p><span aria-label="<?= sprintf(gettext('Total Incomplete Mentors %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($total_teams_mentors_not_completed)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Incomplete Mentors"); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of Incomplete Mentors in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of Incomplete Mentors in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>"></i></p>
                        <br/>
                        <h4  aria-hidden="true"><?= $_USER->formatNumberForDisplay($total_teams_mentors_not_completed); ?></h4>
                    </div>
                    <div class="back">
                        <canvas id="total_teams_mentors_not_completed" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Incomplete Mentors"); ?>"></canvas>
                        <script>
                            createChart('total_teams_mentors_not_completed', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_total_teams_mentors_not_completed)?>', '<?= gettext("Total Incomplete Mentors"); ?>');
                        </script>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Total Registered Mentor -->
        <?php if($_COMPANY->getAppCustomization()['stats']['teams_mentors_registered']) { ?>
            <div class="col-md-4 col-12">
                <div role="button" class="info-card text-center" tabindex="0">
                    <div class="front">
                        <p><span aria-label="<?= sprintf(gettext('Total Registered Mentors %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($total_teams_mentors_registered)); ?> ">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Registered Mentors"); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of Registeres Mentor in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of Registeres Mentor in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>"></i></p>
                        <br/>
                        <h4  aria-hidden="true"><?= $_USER->formatNumberForDisplay($total_teams_mentors_registered); ?></h4>
                    </div>
                    <div class="back">
                        <canvas id="total_teams_mentors_registered" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Registered Mentees"); ?>"></canvas>
                        <script>
                            createChart('total_teams_mentors_registered', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_total_teams_mentors_registered)?>', '<?= gettext("Total Registered Mentors"); ?>');
                        </script>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Total Active Mentee -->
        <?php if($_COMPANY->getAppCustomization()['stats']['teams_mentees_active']) { ?>
            <div class="col-md-4 col-12">
                <div role="button" class="info-card text-center" tabindex="0">
                    <div class="front">
                        <p><span aria-label="<?= sprintf(gettext('Total Active Mentees %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($total_teams_mentees_active)); ?> ">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Active Mentees"); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of Active Mentees in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of Active Mentees in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>"></i></p>
                        <br/>
                        <h4  aria-hidden="true"><?= $_USER->formatNumberForDisplay($total_teams_mentees_active); ?></h4>
                    </div>
                    <div class="back">
                        <canvas id="total_teams_mentees_active" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Active Mentees"); ?>"></canvas>
                        <script>
                            createChart('total_teams_mentees_active', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_total_teams_mentees_active)?>', '<?= gettext("Total Active Mentees"); ?>');
                        </script>
                    </div>
                </div>
            </div>
            <?php } ?>

        <!-- Total Completed Mentee -->
        <?php if($_COMPANY->getAppCustomization()['stats']['teams_mentees_completed']) { ?>
            <div class="col-md-4 col-12">
                <div role="button" class="info-card text-center" tabindex="0">
                    <div class="front">
                        <p><span aria-label="<?= sprintf(gettext('Total Completed Mentees %1$s, Select to flip the card'),$_USER->formatNumberForDisplay($total_teams_mentees_completed)); ?>  ">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Completed Mentees"); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of Completed Mentees in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of Completed Mentees in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>"></i></p>
                        <br/>
                        <h4  aria-hidden="true"><?= $_USER->formatNumberForDisplay($total_teams_mentees_completed); ?></h4>
                    </div>
                    <div class="back">
                        <canvas id="total_teams_mentees_completed" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Completed Mentees"); ?>"></canvas>
                        <script>
                            createChart('total_teams_mentees_completed', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_total_teams_mentees_completed)?>', '<?= gettext("Total Completed Mentees"); ?>');
                        </script>
                    </div>
                </div>
            </div>
            <?php } ?>

        <!-- Total Completed Mentee -->
        <?php if($_COMPANY->getAppCustomization()['stats']['teams_mentees_not_completed']) { ?>
            <div class="col-md-4 col-12">
                <div role="button" class="info-card text-center" tabindex="0">
                    <div class="front">
                        <p><span aria-label="<?= sprintf(gettext('Total Incomplete Mentees %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($total_teams_mentees_not_completed)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Incomplete Mentees"); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of Incomplete Mentees in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of Incomplete Mentees in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>"></i></p>
                        <br/>
                        <h4  aria-hidden="true"><?= $_USER->formatNumberForDisplay($total_teams_mentees_not_completed); ?></h4>
                    </div>
                    <div class="back">
                        <canvas id="total_teams_mentees_not_completed" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Incomplete Mentees"); ?>"></canvas>
                        <script>
                            createChart('total_teams_mentees_not_completed', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_total_teams_mentees_not_completed)?>', '<?= gettext("Total Incomplete Mentees"); ?>');
                        </script>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Total Registered Mentee -->
        <?php if($_COMPANY->getAppCustomization()['stats']['teams_mentees_registered']) { ?>
            <div class="col-md-4 col-12">
                <div role="button" class="info-card text-center" tabindex="0">
                    <div class="front">
                        <p><span aria-label="<?= sprintf(gettext('Total Registered Mentees %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($total_teams_mentees_registered)); ?>  ">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Registered Mentees"); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of Registered Mentees in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of Registered Mentees in this %s."),$_COMPANY->getAppCustomization()['group']["name-short"]) ?>"></i></p>
                        <br/>
                        <h4  aria-hidden="true"><?= $_USER->formatNumberForDisplay($total_teams_mentees_registered); ?></h4>
                    </div>
                    <div class="back">
                        <canvas id="total_teams_mentees_registered" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Registered Mentees"); ?>"></canvas>
                        <script>
                            createChart('total_teams_mentees_registered', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_total_teams_mentees_registered)?>', '<?= gettext("Total Registered Mentees"); ?>');
                        </script>
                    </div>
                </div>
            </div>
        <?php } ?>

    <?php } ?>

        <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
            <div class="col-md-4 col-12">
                <div role="button" class="info-card text-center" tabindex="0">
                    <div class="front">
                        <p><span aria-label="<?= sprintf(gettext('Total %1$s %2$s, Select to flip the card'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'],$_USER->formatNumberForDisplay($group_chapters)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= sprintf(gettext("Total %s"), $_COMPANY->getAppCustomization()['chapter']['name-short-plural']); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of %s in this %s."),$_COMPANY->getAppCustomization()['chapter']['name-short-plural'],$_COMPANY->getAppCustomization()['group']["name-short"]) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of %s in this %s."),$_COMPANY->getAppCustomization()['chapter']['name-short-plural'],$_COMPANY->getAppCustomization()['group']["name-short"]) ?>"></i></p>
                        <br/>
                        <h4  aria-hidden="true"><?= $_USER->formatNumberForDisplay($group_chapters); ?></h4>
                    </div>
                    <div class="back">
                        <canvas id="group_chapters" width="290px" height="150" role="img" aria-label="<?= sprintf(gettext("Graph data of Total %s"), $_COMPANY->getAppCustomization()['chapter']["name-short-plural"]); ?>"></canvas>
                        <script>
                            createChart('group_chapters', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_group_chapters)?>', '<?= sprintf(gettext("Total %s"), $_COMPANY->getAppCustomization()["chapter"]["name-short-plural"]); ?>');
                        </script>
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php if ($_COMPANY->getAppCustomization()['channel']['enabled']) { ?>
            <div class="col-md-4 col-12">
                <div role="button" class="info-card text-center" tabindex="0">
                    <div class="front">
                        <p><span aria-label="<?= sprintf(gettext('Total %1$s %2$s, Select to flip the card'), $_COMPANY->getAppCustomization()['channel']['name-short-plural'],$_USER->formatNumberForDisplay($group_channels)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= sprintf(gettext("Total %s"), $_COMPANY->getAppCustomization()['channel']['name-short-plural']); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext('This tile shows the amount of %s within the %s.'), $_COMPANY->getAppCustomization()['channel']['name-short-plural'], $_COMPANY->getAppCustomization()['group']['name-short']) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext('This tile shows the amount of %s within the %s.'), $_COMPANY->getAppCustomization()['channel']['name-short-plural'], $_COMPANY->getAppCustomization()['group']['name-short']) ?>"></i></p>
                        <br/>
                        <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($group_channels); ?></h4>
                    </div>
                    <div class="back">
                        <canvas id="group_channels" width="290px" height="150" role="img" aria-label="<?= sprintf(gettext("Graph data of Total %s"), $_COMPANY->getAppCustomization()['channel']["name-short-plural"]); ?>"></canvas>
                        <script>
                            createChart('group_channels', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_group_channels)?>', '<?= sprintf(gettext("Total %s"), $_COMPANY->getAppCustomization()['channel']['name-short-plural']); ?>');
                        </script>
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php if ($_COMPANY->getAppCustomization()['stats']['enabled']) { ?>
            <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total %1$s Members %2$s, Select to flip the card'), $_COMPANY->getAppCustomization()['chapter']['name-short'],$_USER->formatNumberForDisplay($user_members_chapters)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span> <span aria-hidden="true"><?= sprintf(gettext("Total %s Members"), $_COMPANY->getAppCustomization()['chapter']['name-short']); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of members that are enrolled in the current %s"),$_COMPANY->getAppCustomization()['chapter']['name-short-plural']) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of members that are enrolled in the current %s"),$_COMPANY->getAppCustomization()['chapter']['name-short-plural']) ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($user_members_chapters); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="user_members_chapters" width="290px" height="150" role="img" aria-label="<?= sprintf(gettext("Graph data of Total %s Members"), $_COMPANY->getAppCustomization()['chapter']["name-short"]); ?>"></canvas>
                            <script>
                                createChart('user_members_chapters', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_user_members_chapters)?>', '<?= sprintf(gettext("Total %s Members"), $_COMPANY->getAppCustomization()['chapter']['name-short']); ?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['channel']['enabled']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total %1$s Members %2$s, Select to flip the card'), $_COMPANY->getAppCustomization()['channel']['name-short'], $_USER->formatNumberForDisplay($user_members_channels)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= sprintf(gettext("Total %s Members"), $_COMPANY->getAppCustomization()['channel']['name-short']); ?></span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile show the number of members that are enrolled in a %s within this %s"),$_COMPANY->getAppCustomization()['channel']['name-short-plural'], $_COMPANY->getAppCustomization()['group']['name-short']) ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile show the number of members that are enrolled in a %s within this %s"),$_COMPANY->getAppCustomization()['channel']['name-short-plural'], $_COMPANY->getAppCustomization()['group']['name-short']) ?>" ></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($user_members_channels); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="user_members_channels" width="290px" height="150" role="img" aria-label="<?= sprintf(gettext("Graph data of Total %s Members"), $_COMPANY->getAppCustomization()['channel']["name-short"]); ?>"></canvas>
                            <script>
                                createChart('user_members_channels', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_user_members_channels)?>', '<?= sprintf(gettext("Total %s Members"), $_COMPANY->getAppCustomization()['channel']['name-short']); ?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['stats']['grouplead_exec']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total %1$s %2$s, Select to flip the card'), Group::SYS_GROUPLEAD_TYPES[1] . 's (' . $_COMPANY->getAppCustomization()['group']['name-short'] . ' Level)',$_USER->formatNumberForDisplay($group_admin_1)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= sprintf(gettext("Total %s"), Group::SYS_GROUPLEAD_TYPES[1] . 's <small>(' . $_COMPANY->getAppCustomization()['group']['name-short'] . ' Level)</small>   '); ?></span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of %s within this %s"), Group::SYS_GROUPLEAD_TYPES[1], $_COMPANY->getAppCustomization()['group']['name-short']); ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of %s within this %s"), Group::SYS_GROUPLEAD_TYPES[1], $_COMPANY->getAppCustomization()['group']['name-short']); ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($group_admin_1); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="group_admin_1" width="290px" height="150" role="img" aria-label="<?= sprintf(gettext('Graph data of Total %1$s'), Group::SYS_GROUPLEAD_TYPES[1] . 's'); ?>"></canvas>
                            <script>
                                createChart('group_admin_1', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_group_admin_1)?>', '<?= sprintf(gettext("Total %s"), Group::SYS_GROUPLEAD_TYPES[1] . 's'); ?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['stats']['grouplead_group']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total %1$s %2$s, Select to flip the card'), Group::SYS_GROUPLEAD_TYPES[2] . 's', $_USER->formatNumberForDisplay($group_admin_2)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= sprintf(gettext("Total %s"), Group::SYS_GROUPLEAD_TYPES[2] . 's'); ?></span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext('This tile shows the number of %s within this %s'), Group::SYS_GROUPLEAD_TYPES[2] . 's', $_COMPANY->getAppCustomization()['group']["name-short"]); ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext('This tile shows the number of %s within this %s'), Group::SYS_GROUPLEAD_TYPES[2] . 's', $_COMPANY->getAppCustomization()['group']["name-short"]); ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($group_admin_2); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="group_admin_2" width="290px" height="150" role="img" aria-label="<?= sprintf(gettext('Graph data of Total %1$s'), Group::SYS_GROUPLEAD_TYPES[2] . 's'); ?>"></canvas>
                            <script>
                                createChart('group_admin_2', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_group_admin_2)?>', '<?= sprintf(gettext("Total %s"), Group::SYS_GROUPLEAD_TYPES[2] . 's'); ?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['stats']['grouplead_region']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total %1$s %2$s, Select to flip the card'), Group::SYS_GROUPLEAD_TYPES[3] . 's', $_USER->formatNumberForDisplay($group_admin_3)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= sprintf(gettext("Total %s"), Group::SYS_GROUPLEAD_TYPES[3] . 's'); ?></span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext('This tile shows the number of %s in this %s'), Group::SYS_GROUPLEAD_TYPES[3] . 's', $_COMPANY->getAppCustomization()['group']["name-short"]); ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext('This tile shows the number of %s in this %s'), Group::SYS_GROUPLEAD_TYPES[3] . 's', $_COMPANY->getAppCustomization()['group']["name-short"]); ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($group_admin_3); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="group_admin_3" width="290px" height="150" role="img" aria-label="<?= sprintf(gettext('Graph data of Total %1$s'), Group::SYS_GROUPLEAD_TYPES[3] . 's'); ?>"></canvas>
                            <script>
                                createChart('group_admin_3', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_group_admin_3)?>', '<?= sprintf(gettext("Total %s"), Group::SYS_GROUPLEAD_TYPES[3] . 's'); ?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && $_COMPANY->getAppCustomization()['stats']['grouplead_chapter']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total %1$s %2$s, Select to flip the card'), Group::SYS_GROUPLEAD_TYPES[4] . 's', $_USER->formatNumberForDisplay($group_admin_4)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= sprintf(gettext("Total %s"), Group::SYS_GROUPLEAD_TYPES[4] . 's'); ?></span>  &nbsp; <i tabindex="0" tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of %s in this %s"), Group::SYS_GROUPLEAD_TYPES[4] . 's',  $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of %s in this %s"), Group::SYS_GROUPLEAD_TYPES[4] . 's',  $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($group_admin_4); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="group_admin_4" width="290px" height="150" role="img" aria-label="<?= sprintf(gettext('Graph data of Total %1$s'), Group::SYS_GROUPLEAD_TYPES[4] . 's'); ?>"></canvas>
                            <script>
                                createChart('group_admin_4', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_group_admin_4)?>', '<?= sprintf(gettext("Total %s"), Group::SYS_GROUPLEAD_TYPES[4] . 's'); ?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['channel']['enabled'] && $_COMPANY->getAppCustomization()['stats']['grouplead_channel']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total %1$s %2$s, Select to flip the card'), Group::SYS_GROUPLEAD_TYPES[5] . 's', $_USER->formatNumberForDisplay($group_admin_5)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= sprintf(gettext("Total %s"), Group::SYS_GROUPLEAD_TYPES[5] . 's'); ?></span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of %s in this %s"), Group::SYS_GROUPLEAD_TYPES[5] . 's',  $_COMPANY->getAppCustomization()['group']["name-short"]); ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of %s in this %s"), Group::SYS_GROUPLEAD_TYPES[5] . 's',  $_COMPANY->getAppCustomization()['group']["name-short"]); ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($group_admin_5); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="group_admin_5" width="290px" height="150" role="img" aria-label="<?= sprintf(gettext('Graph data of Total %1$s'), Group::SYS_GROUPLEAD_TYPES[5] . 's'); ?>"></canvas>
                            <script>
                                createChart('group_admin_5', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_group_admin_5)?>', '<?= sprintf(gettext("Total %s"), Group::SYS_GROUPLEAD_TYPES[5] . 's'); ?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['event']['enabled']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total Published Events %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($events_published)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Published Events"); ?></span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the total number of published events within this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the total number of published events within this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($events_published); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="events_published" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Published Events"); ?>"></canvas>
                            <script>
                                createChart('events_published', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_events_published)?>', '<?= addslashes(gettext("Total Published Events"));?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['event']['enabled']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total Draft Events %1$s, Select to flip the card'),$_USER->formatNumberForDisplay($events_draft)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Draft Events"); ?></span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of events that are in draft form in this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of events that are in draft form in this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($events_draft); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="events_draft" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Draft Events"); ?>"></canvas>
                            <script>
                                createChart('events_draft', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_events_draft)?>', '<?= addslashes(gettext("Total Draft Events"));?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['event']['enabled']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total Completed Events %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($events_completed)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Completed Events"); ?></span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of completed events in this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of completed events in this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($events_completed); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="events_completed" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Completed Events"); ?>"></canvas>
                            <script>
                                createChart('events_completed', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_events_completed)?>', '<?= addslashes(gettext("Total Completed Events"));?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['post']['enabled']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total Published Posts %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($posts_published)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Published Posts"); ?></span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of published posts within this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of published posts within this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($posts_published); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="posts_published" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Published Posts"); ?>"></canvas>
                            <script>
                                createChart('posts_published', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_posts_published)?>', '<?= addslashes(gettext("Total Published Posts"));?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['post']['enabled']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total Draft Posts %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($posts_draft)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Draft Posts"); ?></span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number posts that are in draft form and not published in this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number posts that are in draft form and not published in this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($posts_draft); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="posts_draft" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Draft Posts"); ?>"></canvas>
                            <script>
                                createChart('posts_draft', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_posts_draft)?>', '<?= addslashes(gettext("Total Draft Posts"));?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['newsletters']['enabled']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total Published Newsletters %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($newsletters_published)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Published Newsletters"); ?></span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of published newsletters within this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of published newsletters within this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($newsletters_published); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="newsletters_published" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Published Newsletters"); ?>"></canvas>
                            <script>
                                createChart('newsletters_published', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_newsletters_published)?>', '<?= addslashes(gettext("Total Published Newsletters"));?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['newsletters']['enabled']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total Draft Newsletters %1$s, Select to flip the card'),$_USER->formatNumberForDisplay($newsletters_draft)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Draft Newsletters"); ?></span> &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of newsletters that are in draft form in this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of newsletters that are in draft form in this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($newsletters_draft); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="newsletters_draft" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Draft Newsletters"); ?>"></canvas>
                            <script>
                                createChart('newsletters_draft', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_newsletters_draft)?>', '<?= addslashes(gettext("Total Draft Newsletters"));?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['resources']['enabled']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total Resources %1$s, Select to flip the card'),$_USER->formatNumberForDisplay($resources_published)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Resources"); ?> </span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of available resources within the %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of available resources within the %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($resources_published); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="resources_published" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Resources"); ?>"></canvas>
                            <script>
                                createChart('resources_published', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_resources_published)?>', '<?= addslashes(gettext("Total Resources"));?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['surveys']['enabled']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total Published Surveys %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($surveys_published)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Published Surveys"); ?></span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of surveys that are published within the %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of surveys that are published within the %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($surveys_published); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="surveys_published" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Published Surveys"); ?>"></canvas>
                            <script>
                                createChart('surveys_published', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_surveys_published)?>', '<?= addslashes(gettext("Total Published Surveys"));?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['surveys']['enabled']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total Draft Surveys %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($surveys_draft)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Draft Surveys"); ?></span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the number of surveys that are currently in draft form within this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the number of surveys that are currently in draft form within this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($surveys_draft); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="surveys_draft" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Draft Surveys"); ?>"></canvas>
                            <script>
                                createChart('surveys_draft', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_surveys_draft)?>', '<?= addslashes(gettext("Total Draft Surveys"));?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($_COMPANY->getAppCustomization()['albums']['enabled']) { ?>
                <div class="col-md-4 col-12">
                    <div role="button" class="info-card text-center" tabindex="0">
                        <div class="front">
                            <p><span aria-label="<?= sprintf(gettext('Total Published Album Media %1$s, Select to flip the card'), $_USER->formatNumberForDisplay($album_media_published)); ?>">
                                        <!-- no content shown, but is a heading for screen reader Jaws Tool -->
                        </span><span aria-hidden="true"><?= gettext("Total Published Album Media"); ?></span>  &nbsp; <i tabindex="0" class="fa fa-info-circle info-black" aria-label="<?= sprintf(gettext("This tile shows the total album images and videos are published within this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext("This tile shows the total album images and videos are published within this %s"), $_COMPANY->getAppCustomization()['group']["name-short"]);  ?>"></i></p>
                            <br/>
                            <h4 aria-hidden="true"><?= $_USER->formatNumberForDisplay($album_media_published); ?></h4>
                        </div>
                        <div class="back">
                            <canvas id="album_media_published" width="290px" height="150" role="img" aria-label="<?= gettext("Graph data of Total Published Album Media"); ?>"></canvas>
                            <script>
                                createChart('album_media_published', '<?= json_encode($groupTimeLabel)?>', '<?= json_encode($c_album_media_published)?>', '<?= addslashes(gettext("Total Published Album Media"));?>');
                            </script>
                        </div>
                    </div>
                </div>
            <?php } ?>

        <?php } ?>
        <div class="clearfix"></div>
    </div>

    <hr>

    <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
        <?php   $allowChapterCreate = $_USER->canManageGroup($groupid) && $_COMPANY->getAppCustomization()['chapter']['allow_create_from_app']; ?>
        <div class="col-md-12 mt-5">
            <h3 class="ml-3 title-left"><?= $_COMPANY->getAppCustomization()['chapter']['name-short-plural'] ?> </h3>
            <?php if ($allowChapterCreate) { ?>
                <a aria-label="Add a new <?= $_COMPANY->getAppCustomization()['chapter']['name-short'] ?>" href="javascript:void(0)" onclick="selectRegionModal('<?= $_COMPANY->encodeId($groupid); ?>')">
                    <i class="fa fa-plus-circle fa-lg" title="Add new Chapter" aria-hidden="true"></i>
                </a>
            <?php } ?>
        </div>
        <div class="col-md-12">
            <hr class="linec">
            <div class="table-responsive " id="list-view">
                <table id="dashboard-table-chapter" class="table table-hover display compact"
                       summary="<?= sprintf(gettext('This table displays the list of %1$s %2$s'), $_COMPANY->getAppCustomization()['group']['name-short'], $_COMPANY->getAppCustomization()['chapter']['name-short-plural']);?>">
                    <thead>
                    <tr>
                        <th width="30%"
                            scope="col"><?= sprintf(gettext("%s Name"), $_COMPANY->getAppCustomization()['chapter']['name-short']); ?></th>
                        <th width="20%" scope="col"><?= gettext("Region"); ?></th>
                        <th width="20%" scope="col"><?= gettext("Office location"); ?></th>
                        <th width="20%"
                            scope="col"><?= sprintf(gettext("Number of %s"), $_COMPANY->getAppCustomization()['group']['memberlabel'] . 's'); ?></th>
                        <?php if ($allowChapterCreate) { ?>
                            <th width="10%" scope="col"><?= gettext("Action"); ?></th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($chapters) {
                        foreach ($chapters as $chapter) {
                            if (!isset($counter)) $counter = 0;
                            $counter++;
                            $encodedChapterId = $_COMPANY->encodeId($chapter['chapterid']);
                            if ($chapter['isactive'] == 0) {
                                $color = "#ffffce";
                            } elseif ($chapter['isactive'] == 100) {
                                $color = "#fde1e1";
                            } else {
                                $color = "#ffffff";
                            }
                    ?>

                            <?php if ($allowChapterCreate) { //Check permission... ?>
                                <tr <?= ($chapter['isactive'] == 0) ? 'style="background-color: rgb(255, 255, 206);"' : '' ?> >
                                    <td>
                                        <?= htmlentities($chapter['chaptername']); ?>
                                    </td>
                                    <td><?= $chapter['region'] ? $chapter['region'] : '-'; ?></td>
                                    <td>
                                        <?php if ($chapter['regionids'] == 0 && $chapter['userid'] == 0) {
                                            echo "All Unmapped";
                                        } else { /* Since we are passing the result to javascript to show as html, need double htmlentitles */
                                            $attribute = "";
                                            if($chapter['branchname'] === '-'){
                                                $attribute = 'tabindex="-1" aria-disabled="true" ';
                                            }
                                         ?>
                                            <button <?= $attribute ?> class="btn btn-affinity btn-sm"
                                                    onclick="showOfficeLocations('<?= addslashes(htmlspecialchars(htmlspecialchars(htmlspecialchars($chapter['chaptername'])))); ?>','<?= addslashes(htmlspecialchars($chapter['branchname'])); ?>')"><?= $chapter['branchname'] != '-' ? count(explode('||', $chapter['branchname'])) : 0; ?>
                                                locations
                                            </button>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?= $chapter['membersCount'] ?> <?= $_COMPANY->getAppCustomization()['group']['memberlabel'] . 's'; ?>
                                    </td>
                                    <?php if ($allowChapterCreate) { ?>
                                        <td>
                                        <?php
                                            include(__DIR__ . '/dashboard_chapter_action_button.template.php');
                                            ?>
                                        </td>
                                    <?php } ?>
                                </tr>
                            <?php } elseif ($chapter['isactive'] == 1) { //Get all active chapter?>
                                <tr>
                                    <td><?= htmlspecialchars($chapter['chaptername']); ?></td>
                                    <td><?= $chapter['region'] ? $chapter['region'] : '-'; ?></td>
                                    <td>
                                        <?php if ($chapter['regionids'] == 0 && $chapter['userid'] == 0) {
                                            echo "All Unmapped";
                                        } else { /* Since we are passing the result to javascript to show as html, need double htmlentitles */ 
                                            $attribute = "";
                                            if($chapter['branchname'] === '-'){
                                                $attribute = ' tabindex="-1" aria-disabled="true" ';
                                            }
                                        ?>
                                            <button <?= $attribute ?> class="btn btn-affinity btn-sm"
                                                    onclick="showOfficeLocations('<?= addslashes(htmlspecialchars(htmlspecialchars($chapter['chaptername']))); ?>','<?= addslashes(htmlspecialchars($chapter['branchname'])); ?>')"><?= $chapter['branchname'] != '-' ? count(explode('||', $chapter['branchname'])) : 0; ?>
                                                locations
                                            </button>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?= $chapter['membersCount'] ?> <?= $_COMPANY->getAppCustomization()['group']['memberlabel'] . 's'; ?>
                                    </td>
                                    <?php if ($allowChapterCreate) { ?>
                                        <td>
                                            <?php
                                            include(__DIR__ . '/dashboard_chapter_action_button.template.php');
                                            ?>
                                        </td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                    <?php
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">
                                - <?= sprintf(gettext("No %s created yet"), $_COMPANY->getAppCustomization()['chapter']['name-short']); ?>
                                -
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <?php if ($allowChapterCreate) { ?> <td></td> <?php } ?>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php } ?>

    <?php if ($_COMPANY->getAppCustomization()['channel']['enabled']) { ?>
        <?php $allowChannelCreate = $_USER->canManageGroup($groupid) && $_COMPANY->getAppCustomization()['channel']['allow_create_from_app']; ?>
    <div class="col-md-12 mt-5">
        <h3 class="ml-3 mt-3 title-left"><?= $_COMPANY->getAppCustomization()['channel']['name-short-plural'] ?></h3>
        <?php if ($allowChannelCreate) {?>
            <a aria-label="<?= sprintf(gettext("Add new %s"), $_COMPANY->getAppCustomization()['channel']['name-short']); ?>" href="javascript:void(0)" onclick="add_edit_channel_modal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId(0); ?>')">
                <i class="fa fa-plus-circle fa-lg" title="<?= sprintf(gettext("Add new %s"), $_COMPANY->getAppCustomization()['channel']['name-short']); ?>" aria-hidden="true"></i>
            </a>
        <?php } ?>
    </div>
    <div class="col-md-12">
        <hr class="linec">
        <div class="table-responsive " id="list-view">
            <table id="dashboard-table-channel" class="table table-hover display compact" summary="<?= sprintf(gettext('This table displays the list of %1$s %2$s'), $_COMPANY->getAppCustomization()['group']['name-short'], $_COMPANY->getAppCustomization()['channel']['name-short-plural']);?>">
                <thead>
                <tr>
                    <th width="50%"
                        scope="col"><?= sprintf(gettext("%s Name"), $_COMPANY->getAppCustomization()['channel']['name-short']); ?></th>
                    <th width="30%"
                        scope="col"><?= sprintf(gettext("Number of %s"), $_COMPANY->getAppCustomization()['group']['memberlabel'] . 's'); ?></th>
                    <?php if ($allowChannelCreate) { ?>
                    <th width="10%" scope="col"><?= gettext("Action"); ?></th>
                    <?php } ?>
                </tr>
                </thead>
                <tbody>
                <?php if ($channels) {
                    foreach ($channels as $channel) {
                        $encodedChannelId = $_COMPANY->encodeId($channel['channelid']);
                        //Check permission...
                ?>
                        <?php if ($allowChannelCreate) { ?>
                            <tr <?= ($channel['isactive'] == 0) ? 'style="background-color: rgb(255, 255, 206);"' : '' ?> >
                                <td>
                                    <?= htmlspecialchars($channel['channelname']); ?>
                                </td>
                                <td>
                                    <?= $channel['membersCount'] ?> <?= $_COMPANY->getAppCustomization()['group']['memberlabel'] . 's'; ?>
                                </td>
                                <?php if ($allowChannelCreate) { ?>
                                <td>
                                    <?php
                                    include(__DIR__ . '/dashboard_channel_action_button.template.php');
                                    ?>
                                    
                                </td>
                                <?php } ?>
                            </tr>
                        <?php } elseif ($channel['isactive'] == 1) { // Get all active channel ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($channel['channelname']); ?>
                                </td>
                                <td>
                                    <?= $channel['membersCount'] ?> <?= $_COMPANY->getAppCustomization()['group']['memberlabel'] . 's'; ?>
                                </td>
                                <?php if ($allowChannelCreate) { ?>
                                    <td>
                                    <?php
                                    include(__DIR__ . '/dashboard_channel_action_button.template.php');
                                    ?>                                      
                                    </td>
                                <?php } ?>
                            </tr>

                        <?php } ?>
                <?php
                    }//End foreach.
                } else {
                ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">
                            - <?= sprintf(gettext("No %s created yet"), $_COMPANY->getAppCustomization()['channel']['name-short']); ?>
                            -
                        </td><td></td><?php if ($allowChannelCreate) { ?><td></td><?php } ?>
                    </tr>
                <?php } ?>

                </tbody>
            </table>
        </div>
    </div>
<?php } ?>

</div>
<div id="regionModal" class="modal fade">
    <div aria-label="<?= gettext("Office Locations"); ?>" class="modal-dialog" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="location_modal_title"><?= gettext("Office Locations"); ?></h4>
                <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>

            </div>
            <div class="modal-body" style="min-height: 100px; max-height:450px; overflow-y:scroll;">
                <div class="col-md-12" id="regionsList">
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?= gettext("Close"); ?></button>
            </div>
        </div>
    </div>
</div>
<div class="my-5"></div>
<script>
    $(document).ready(function () {
        $('.info-card').on('click', function (event) {
            if ($(event.target).closest('.dropdown').length > 0) {
                return; // Stop the event if the clicked element or its ancestor has the class "dropdown"
            }
            $(this).toggleClass('toggleFlip'); 

            if ($(this).hasClass("toggleFlip")){                    
                $('.toggleFlip .info-black').attr("tabindex","-1"); 
                $('.toggleFlip .info-black').attr("aria-hidden","true");  
                setTimeout(function() {	                  
                    document.getElementById('hidden_div_for_notification').innerHTML=" <?= gettext('Flipped Front') ?>"; 
                }, 300);                                       
            }else{
                setTimeout(function() {	 
                    document.getElementById('hidden_div_for_notification').innerHTML=" <?= gettext('Flipped back') ?>"; 
                }, 300); 
            }

        });
    });   

    //On Enter Key...
    $(function(){
        $(".info-card").keypress(function (e) {
            if (e.keyCode == 13) {        
                $(this).toggleClass("toggleFlip");
                if ($(this).hasClass("toggleFlip")){                    
                $('.toggleFlip .info-black').attr("tabindex","-1"); 
                $('.toggleFlip .info-black').attr("aria-hidden","true");  
                setTimeout(function() {	                  
                    document.getElementById('hidden_div_for_notification').innerHTML=" <?= gettext('Flipped Front') ?>"; 
                }, 300);                                       
            }else{
                setTimeout(function() {	 
                    document.getElementById('hidden_div_for_notification').innerHTML=" <?= gettext('Flipped back') ?>"; 
                }, 300); 
            }
            }
        })
    });  

    function showOfficeLocations(c, r) {
        if (r != '-') {
            var array = r.split('||');
            var region = '';
            for (var i = 0; i < array.length; i++) {
                region += '<p>' + (i + 1) + '.&emsp;' + array[i] + '</p>';
            }
            $("#location_modal_title").html(c + '\'s Office Locations');
            $("#regionsList").html(region);
            $('#regionModal').modal('show');
        }      
    }

    function selectRegionModal(g) {
        $.ajax({
            url: 'ajax.php?getRegionsForGroup=' + g,
            type: "GET",
            success: function (data) {
                if (data == 0) {
                    swal.fire({
                        title: 'Error',
                        text: "This Group is not configured to use Regions, please update the group first."
                    });
                } else {
                    $('#loadAnyModal').html(data);
                    $('#select_region_modal').modal('show');
                }
            }
        });       
    }

    function editChapterModal(g, c, r) {
        $.ajax({
            url: 'ajax.php?openNewChapterModel=1',
            type: "GET",
            data: {'gid': g, 'cid': c, 'rid': r},
            success: function (data) {
                closeAllActiveModal();
                $('#loadAnyModal').html(data);
                $('#new_chapter_model').modal('show');
            }
        });              
    }

    function add_edit_channel_modal(g, c) {
        $.ajax({
            url: 'ajax.php?openNewChannelModel=1',
            type: "GET",
            data: {'gid': g, 'cid': c,},
            success: function (data) {
                $('#loadAnyModal').html(data);
                $('#new_channel_model').modal('show');
            }
        });
    }


    function changeGroupChapterStatus(gid, cid, rid, status, element) {      
        $.ajax({
            url: 'ajax.php?change_group_chapter_status=' + cid,
            type: "POST",
            data: {'gid': gid, 'rid': rid, 'status': status},

            success: function (data) {
                manageDashboard(gid);
                if (status == 1) {
                    $(element).parent('td').parent('tr').css({'background-color': '#ffffff !important'});
                } else {
                    $(element).parent('td').parent('tr').css({'background-color': '#ffffce !important'});
                }
                $(element).parent('td').html(data);
                jQuery(".deluser").popConfirm({content: ''});
            }
        });
    }

    function changeGroupChannelStatus(gid, cid, status, element) {
        $.ajax({
            url: 'ajax.php?change_group_channel_status=' + cid,
            type: "POST",
            data: {'gid': gid, 'status': status},

            success: function (data) {
                manageDashboard(gid);
                if (status == 1) {
                    $(element).parent('td').parent('tr').css({'background-color': '#ffffff !important'});
                } else if (status == 100) {
                    $(element).parent('td').parent('tr').css({'background-color': '#fde1e1 !important'});
                } else {
                    $(element).parent('td').parent('tr').css({'background-color': '#ffffce !important'});
                }
                $(element).parent('td').html(data);
                jQuery(".deluser").popConfirm({content: ''});
            }
        });
    }

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });

    $('#regionModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus')
});



$(document).ready(function() {
    var dtable = $('#dashboard-table-chapter').DataTable( {
			"order": [[0,"asc"]],
			"bPaginate": true,
            "language": {
                "sZeroRecords": "<?= gettext('No data available in table');?>",
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },	
			"bInfo" : false,
            columnDefs: [
            { 
                targets: [2,3,4],
                orderable: false,
                defaultContent: "-",                      
                targets: "_all"
            }],
            "drawCallback": function() {
                setAriaLabelForTablePagination(); 
            },
            "initComplete": function(settings, json) {                            
                setAriaLabelForTablePagination(); 
                $('.current').attr("aria-current","true");  
            },
				
		});
        if(dtable){
            screenReadingTableFilterNotification('#dashboard-table-chapter',dtable);
        }
    
        var dt = $('#dashboard-table-channel').DataTable( {
			"order": [[0,"asc"]],
			"bPaginate": true,
            "language": {
                "sZeroRecords": "<?= gettext('No data available in table');?>",
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },
			"bInfo" : false,
            columnDefs: [
                { targets: [1,2], 
                orderable: false, 
                defaultContent: "-",                      
                targets: "_all"
            }],
            "drawCallback": function() {
                setAriaLabelForTablePagination(); 
            },
            "initComplete": function(settings, json) {                            
                setAriaLabelForTablePagination(); 
                $('.current').attr("aria-current","true");  
            },
				
		});
        if(dt){
            screenReadingTableFilterNotification('#dashboard-table-channel',dt); 
        }              
        
        $('.fa-ellipsis-v').attr('aria-expanded', 'false');
	});
    
    $("#chapter-status").popover().click(function(e) {
        e.preventDefault();
     });

     retainFocus("#regionModal");
</script>
