<style>
	.green:before {background-color: <?= $_ZONE->val('show_group_overlay') ? $group->val('overlaycolor') : ''; ?>;}
	.timepicker{ z-index:9999 !important; }
</style>
<main>
<?php include __DIR__.'/templates/group_banner_menu_html.php'; ?>
    <div class="container inner-background">
        <div id="ajax">
        </div>
    </div>
	<!-- Container div for Datepicker & video tags store all html in it for accessibility -->
	<div class="datepicker-and-video-tags-html-container"></div>
	
</main>
<script>
	$('textarea.expand').focus(function(){
	
		/*to make this flexible, I'm storing the current width in an attribute*/
		$(this).animate({ height: "90px" }, 'slow');
		$("#submitpost").removeClass('hidden');
		if($("#postarea").val()==""){
			$("#submitpost").attr('disabled','disabled');
		}
		
	}).blur(function() {
		/* lookup the original width */
		if( $("#postarea").val()=="" ){
			$(this).animate({ height: "40px" }, 'slow');
			
			//if ( $("#postarea").height() == 32 ){
				setTimeout(function() {
					$("#submitpost").addClass('hidden');
				}, 500); 
			//}
			
		}
	});
	$("form").keyup(function(){
		$("#submitpost").removeAttr('disabled');
	});
</script>
<script>
    $("#home-h").removeClass("active-1").addClass('home_nav');
    $("#home-mh").removeClass("home_nav").addClass('active-1');
    $("#home-c").removeClass("active-1").addClass('home_nav');
    $("#home-a").removeClass("active-1").addClass('home_nav');
    $("#home-s-icon").removeClass("home_nav").addClass('home_nav');
</script>
<script>
	localStorage.setItem("manage_active", "manageDashboard");
</script>