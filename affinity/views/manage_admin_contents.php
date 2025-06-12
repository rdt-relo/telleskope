<style>
	.navbar-nav li.innerMenu a button.btn-affinity.btn-info.btn {
    margin: -3px 0;
}
	#myNavbar{
		height: 57px;
	}
	li.sub a {
		padding: 5px 10px 5px 10px;
	}
	.dropdown-menu.show {
		right: auto;
		left: auto;
	}
	#ajax{
		width:100% !important;
	}
	
    @media (max-width: 834px) {
        li.innerMenu {
            display: inline-block;
            z-index: 999999 !important;
            position: relative;
        }

        .inner-background .table-responsive {
            margin-top: 42px;
        }

        li.innerMenu .dropdown-menu.show {
            position: absolute;
            top: 47px;
        }
    }
	
	.navbar-light .navbar-nav .nav-link {
		color: #505050;
	}

	li.innerMenu.submenuActive .dropdown-menu a {
		color: #505050 !important; 
	}	
	li.innerMenu.submenuActive a, li.sub-menu-li:hover a {
		color: #505050 !important; 
	}
	li.innerMenu.submenuActive a, li.innerMenu.submenuActive a:hover{
		color: #fff !important; 
	}
	li.innerMenu.dropdown a:hover{
		color: #fff !important; 
	}

</style>
<script>
    $("#home-h").removeClass("active-1").addClass('home_nav');
    $("#home-mh").removeClass("active-1").addClass('home_nav');
    $("#home-c").removeClass("active-1").addClass('home_nav');
    $("#home-a").removeClass("home_nav").addClass('active-1');
    $("#home-s-icon").addClass('home_nav');
</script>
<main>
<div class="container w2 overlay green"
		style="background:url(<?= $banner ? $banner : 'img/img.png'; ?>) no-repeat; background-size:cover; background-position:center;" >
    <div class="col-md-12">
        <h1 class="ll">
            <span>
                <?= $bannerTitle ?>
            </span>
        </h1>
	</div>
</div>
<div id="main_section" class="container w3 subnav px-3">
    <nav class="navbar navbar-expand-lg navbar-light" aria-label="Secondary">
        <div class="container-fluid">
            <div id="myNavbar" class="admin-content-menu">
				<ul class="navbar-nav mr-auto" id="innerMenuBar" role="tablist">

					<li role="none" class="innerMenu" id="manageGlobalAnnouncements_li">
					    <button aria-selected="false" tabindex="-1" role="tab" type="button" class="btn-no-style menu-button" id="manageGlobalAnnouncements" onclick="manageGlobalAnnouncements()"><?= Post::GetCustomName(false);?></button>
                    </li>

					<?php if ($_COMPANY->getAppCustomization()['event']['enabled']) { ?>
                    <li role="none" class="innerMenu" id="manageGlobalEvents_li">
                        <button aria-selected="false" tabindex="-1" role="tab" type="button" class="btn-no-style menu-button" id="manageGlobalEvents" onclick="manageGlobalEvents()"><?= gettext("Events");?></button>
                    </li>
                    <?php } ?>

                    <?php if ($_COMPANY->getAppCustomization()['newsletters']['enabled']) { ?>
					<li role="none" class="innerMenu" id="getGroupNewsletters_li"  >
                        <button aria-selected="false" tabindex="-1" role="tab" type="button" class="btn-no-style menu-button" id="getGroupNewsletters"  onclick="manageGlobalNewsletters(),getGroupNewsletters('<?= $_COMPANY->encodeId(0)?>')"><?= gettext("Newsletters");?></button>
                    </li>
                    <?php } ?>

					<?php if ($_COMPANY->getAppCustomization()['surveys']['enabled']) { ?>
					<li role="none" class="innerMenu" id="getGroupSurveys_li">
                        <button aria-selected="false" tabindex="-1" role="tab" type="button" class="btn-no-style menu-button" id="getGroupSurveys" onclick="getAdminSurveys()"><?= gettext("Survey");?></button>
                    </li>
					<?php } ?>

					<?php if($_COMPANY->getAppCustomization()['messaging']['enabled']){ ?>
						<li role="none" id="emails_li_menu" class="innerMenu dropdown sub-menu-li">
							<button aria-selected="false" tabindex="-1" class="nav-link dropdown-toggle sub-menu-title menu-button" href="#" id="navbarDropdown" role="tab" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<?= gettext("Email"); ?> &#9662;</button>
							<div class="dropdown-menu stage-two-menu" aria-labelledby="navbarDropdown">	
								<button role="button" class="dropdown-item tool-tip" id="manageCommunicationsTemplates" onclick="manageCommunicationsTemplates('<?= $_COMPANY->encodeId(0)?>')" ><?= gettext("Automated Email");?></button>
								<button role="button" class="dropdown-item tool-tip" id="groupMessageList" onclick="groupMessageList('<?= $_COMPANY->encodeId(0)?>',2)"><?= gettext("Direct Email");?></button>
							</div>
						</li>
					<?php } ?>
				</ul>

				<ul class=" navbar-nav ml-auto">
					<li class="innerMenu">
						<a href="<?= Url::GetZoneAwareUrlBase($_ZONE->id()) . 'home'?>" class="btn-affinity btn-info btn mt-2" >
						<?= gettext("Back&nbsp;to&nbsp;Home");?></a>
					</li>
				</ul>
            </div>
        </div>
    </nav>
</div>
    <div class="container inner-background pt-4 row">
        <div id="ajax">
        </div>
    </div>
	<!-- Container div for Datepicker & video tags store all html in it for accessibility -->
	<div class="datepicker-and-video-tags-html-container"></div>
</main>

<!-- Append Multiple Data -->
<script type="text/javascript">
	$(document).ready(function(){
		 // Add active class to the current button (highlight it)
		var header = document.getElementById("innerMenuBar");
		var btns = header.getElementsByClassName("innerMenu");
		for (var i = 0; i < btns.length; i++) {
			btns[i].addEventListener("click", function () {
				var current = document.getElementsByClassName("submenuActive");
				if (current.length){
					current[0].className = current[0].className.replace(" submenuActive", "");
					this.className += " submenuActive";
				}
			});
		}	
		<?php if (isset($_GET['survey'])){ ?>
			$("#getGroupSurveys").trigger("click");
			$("#getGroupSurveys_li").addClass('submenuActive');
			var newURL = location.href.split("?")[0];
			history.pushState('object', document.title, newURL);
		<?php } else{ ?>
			var activeTab = localStorage.getItem("manage_active");
			var hash = window.location.hash.substr(1);
			if (hash){
				activeTab = "manageGlobalAnnouncements"
				if (hash =='newsletters') {
					activeTab = "getGroupNewsletters"
				} else if (hash =='events') {
					activeTab = "manageGlobalEvents"
				} else if (hash =='eventDetail') {
					activeTab = "manageGlobalEvents";
					<?php
					if(isset($_GET['subjectEventId'])){
						$decoded_eventid = $_COMPANY->decodeId($_GET['subjectEventId']);
					?>
						let newUrl = removeURLParameter(window.location.href,'subjectEventId');
						window.history.replaceState(null, null,newUrl);					
						manageEventSurvey('<?= $_COMPANY->encodeId($decoded_eventid); ?>');
					<?php } ?>

				}	
				$("#"+activeTab).trigger("click");
				$("#"+activeTab+'_li').trigger("click");
				$("#"+activeTab+'_li').addClass('submenuActive');
				$("#"+activeTab+'_li .menu-button').attr('aria-selected', 'true');
				$("#"+activeTab+'_li .menu-button').attr('tabindex', '0');
			}
			else if (activeTab ){
				var seriesId = '';
				if(activeTab.indexOf('manageGlobalEvents-') != -1){
					var tabArray = activeTab.split('-');
					activeTab =tabArray[0];
					seriesId = tabArray[1];
				}
				$("#"+activeTab).trigger("click");
				$("#"+activeTab+'_li').trigger("click");
				$("#"+activeTab+'_li').addClass('submenuActive');
				$("#"+activeTab+'_li .menu-button').attr('aria-selected', 'true');
				$("#"+activeTab+'_li .menu-button').attr('tabindex', '0');

				if (seriesId){
					manageSeriesEventGroup('<?= $_COMPANY->encodeId(0)?>',seriesId);
				}

				<?php
					if(isset($_GET['subjectEventId'])){
						$decoded_eventid = $_COMPANY->decodeId($_GET['subjectEventId']);
					?>
						let newUrl = removeURLParameter(window.location.href,'subjectEventId');
						window.history.replaceState(null, null,newUrl);
						manageEventSurvey('<?= $_COMPANY->encodeId($decoded_eventid); ?>');
				<?php } ?>

			} else {
				// Default
				manageGlobalAnnouncements()
				$("#manageGlobalAnnouncements_li").addClass('submenuActive');
			}
	<?php } ?>
		history.pushState("", document.title, window.location.pathname + window.location.search); // Clear # hash from ur
	});

$(function() {   	                  
  $(".menu-button").click(function() { 
	$('.menu-button').attr('aria-selected', 'false');  
    $('.menu-button').attr('tabindex', '-1');
    $(this).attr('tabindex', '0');    
	$(this).attr('aria-selected', 'true');
	
  });
});
$('.menu-button').keydown(function(e) {  
        if (e.keyCode == 39) {       
            $(this).parent().next().find(".menu-button").focus();    
        }else if(e.keyCode == 37){       
            $(this).parent().prev().find(".menu-button").focus();  
        }
    });   

</script>