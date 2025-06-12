<?php 	for($i=0;$i<count($data);$i++){ ?>
					<div class="event-block-container js-newsletter-row">
						<?php
							$show_newsletter_year = (function () use ($data, $i) {
								if ((int) $data[$i]['pin_to_top'] === 1) {
									return false;
								}

							if ($i === 0) {
									return true;
								}

								if ((int) $data[$i-1]['pin_to_top'] === 1) {
									return true;
							}

							if (($data[($i-1)]['year'] ?? '') != $data[$i]['year']) {
									return true;
								}

								return false;
							})();

							$show_newsletter_month = (function () use ($data, $i, $show_newsletter_year) {
								if ((int) $data[$i]['pin_to_top'] === 1) {
									return false;
							}

							if ($show_newsletter_year) {
									return true;
							}

							if (($data[($i-1)]['month'] ?? '') != $data[$i]['month']) {
									return true;
							}

								return false;
							})();
						?>

					<?php if (
							((int) ($data[$i-1]['pin_to_top'] ?? 0) === 1)
							&& ((int) ($data[$i]['pin_to_top']) === 0)
						) { ?>
						<hr>
					<?php } ?>

			<?php   if ($show_newsletter_year) { ?>
						<div class="row mt-4 js-newsletter-year" data-newsletter-year="<?= $data[$i]['year']; ?>">
							<div class="col text-right">
								<h2><?= $data[$i]['year']; ?></h2>
							</div>
						</div>
				<?php 	} ?>

			<?php   if ($show_newsletter_month) { ?>
						<div class="row js-newsletter-month" data-newsletter-month="<?= $data[$i]['month']; ?>">
							<div class="col">
								<h2><?= $data[$i]['month']; ?></h2>
								<hr class="linec">
							</div>
						</div>

				<?php 	} ?>

						<div class="row no-gutters mb-3 pb-3 newsletter-row" id="nl<?= $data[$i]['newsletterid']; ?>">
                            <div class="col-md-7 col-12">
							<a role="button" tabindex="0" href="javascript:void(0);" style="cursor: pointer;" onclick="previewNewsletter('<?= $enc_groupid;?>','<?= $_COMPANY->encodeId($data[$i]['newsletterid']);?>')">
								<h3 aria-label="<?= $data[$i]['newslettername']; ?>" class="active2"><?= $data[$i]['newslettername']; ?>
								<?php if($data[$i]['pin_to_top']){ ?>
                            		<i class="fa fa-thumbtack ml-1" style="color:#0077b5;vertical-align:super;font-size: small" aria-hidden="true"></i>
                        	<?php } ?>
								</h3>
							</a>
						</div>
							<div class="col-md-7 col-12">
                                <span class="dta-tm">
                                    <?= gettext("Published on")?>
									<?php
                                    $datetime = $data[$i]['publishdate'];
									echo $_USER->formatUTCDatetimeForDisplayInLocalTimezone($datetime,true,true,true);
									?>

									<?php if($data[$i]['groupid'] == 0 ){ ?>
										in <span class="group-label ml-1" style="color:<?= $_COMPANY->getAppCustomization()['group']['group0_color']; ?>"><?= $_COMPANY->getAppCustomization()['group']['groupname0']; ?></span>
									<?php } ?>
									<?php if (count($data[$i]['chapters'])) {
                                    $ch = $data[$i]['chapters'];
                                    ?>
                                        in
                                    <?php for($c=0;$c<count($ch);$c++){ ?>
                                        <span class="chapter-label" style="color:<?= $ch[$c]['colour'] ?>">
                                            <i class="fas fa-globe" style="color:<?= $ch[$c]['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($ch[$c]['chaptername']); ?>
                                        </span>&nbsp;
                                    <?php } ?>
									<?php } ?>

									<?php
										if ($data[$i]['channelid'] > 0){
											$chh = Group::GetChannelName($data[$i]['channelid'],$data[$i]['groupid']);
									?>
										<span class="chapter-label ml-1" style="color:<?= $chh['colour'] ?>">
											<i class="fas fa-layer-group" style="color:<?= $chh['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($chh['channelname']); ?></span>
									<?php
										}
									?>
                                </span>
                            </div>

                            <div class="col-md-5 col-12 right-or-center-aligned mt-2">
                                <button id="<?= $_COMPANY->encodeId($data[$i]['newsletterid']);?>" tabindex="0" aria-label="<?= sprintf(gettext('Share %s Newsletter'),$data[$i]['newslettername'].' '. $data[$i]['month']) ?>" class="btn btn-sm btn-affinity mb-1" onclick="getShareableLink('<?= $enc_groupid;?>','<?= $_COMPANY->encodeId($data[$i]['newsletterid']);?>','3')"><?= gettext("Share Newsletter"); ?></button>
                                &nbsp;
                                <button tabindex="0" aria-label="<?= sprintf(gettext('Email Me %s Newsletter'),$data[$i]['newslettername'].' '. $data[$i]['month']) ?>" class="btn btn-sm btn-affinity mb-1" onclick="emaildialog('<?= gettext('Do you want to receive this newsletter by email?'); ?>','<?= $enc_groupid;?>','<?= $_COMPANY->encodeId($data[$i]['newsletterid']);?>')"><?= gettext("Email Me"); ?></button>
                                &nbsp;
                            </div>

							<?php if ($_COMPANY->getAppCustomization()['newsletters']['likes'] || $_COMPANY->getAppCustomization()['newsletters']['comments']) { ?>
							<div class="col-sm-12 event-block">
								<div class="link-icons img-down">
									<?php if($data[$i]['isactive'] == 1){ ?>
								<?php if ($_COMPANY->getAppCustomization()['newsletters']['likes']) { ?>
									<div id="x<?= $i; ?>" class="like-2">
										<span  style="cursor:pointer;">
											<a role="button" aria-label="<?= sprintf(gettext('like %1$s newsletter. %1$s has %2$s likes'), $data[$i]['newslettername'], Newsletter::GetLikeTotals($data[$i]['newsletterid']))?>"
											onclick="previewNewsletter('<?= $enc_groupid;?>','<?= $_COMPANY->encodeId($data[$i]['newsletterid']);?>')"
											href="javascript:void(0);">
												<i class="fa fa-thumbs-up fa-regular newgrey" title="<?= gettext('Like') ?>"></i>
												<span class="gh1"><?= Newsletter::GetLikeTotals($data[$i]['newsletterid']) ?></span>
											</a>
										</span>
									</div>
								<?php } ?>
								<?php if ($_COMPANY->getAppCustomization()['newsletters']['comments']) { ?>
									<div class="review-2">
										<a role="button" aria-label="<?= sprintf(gettext('comment %1$s newsletter. %1$s has %2$s comments'), $data[$i]['newslettername'], Newsletter::GetCommentsTotal($data[$i]['newsletterid']))?>"										
										onclick="previewNewsletter('<?= $enc_groupid;?>','<?= $_COMPANY->encodeId($data[$i]['newsletterid']);?>')"
										href="javascript:void(0);">
											<i class="fa fa-regular fa-comment-dots newgrey" title="<?= gettext('Total Comments') ?>"></i>
											<span class="gh1"><?= Newsletter::GetCommentsTotal($data[$i]['newsletterid']); ?></span>
										</a>
									</div>
								<?php } ?>
								<?php } ?>
								</div>
							</div>
						<?php } ?>
						</div>
					</div>
				<?php	} ?> <!-- end of for loop -->
