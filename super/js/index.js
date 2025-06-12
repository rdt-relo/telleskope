jQuery(document).ready(function(){
   
    jQuery("#sucessMessage").fadeOut(3000);

	var lastid = document.location.href.match(/[^\/]+$/)[0];
	console.log("lastid=>",lastid);
		if (lastid.indexOf('dashboardres') >= 0){
			$("#collapseOne").removeClass('collapse');
			$("#collapseOne_m").removeClass('collapse');
			$("#collapseOne").addClass('in');
			$("#collapseOne_m").addClass('in');
			//Adds it to the current element
			$("#one1").addClass('myactive');
			$("#one1_m").addClass('myactive');
			
		} else if (lastid.indexOf('group') >= 0 || lastid.indexOf('manageleads') >= 0 || lastid.indexOf('newleads') >= 0 || lastid.indexOf('manageChapters') >= 0 || lastid.indexOf('manageChapterLeads') >= 0 || lastid.indexOf('newChapter') >= 0  || lastid.indexOf('newChapterLead') >= 0  ){
			$("#collapseOne").removeClass('collapse');
			$("#collapseOne_m").removeClass('collapse');
			$("#collapseOne").addClass('in');
			$("#collapseOne_m").addClass('in');
			$("#one2").addClass('myactive');
			$("#one2_m").addClass('myactive');
		} else if (lastid.indexOf("manageusers") >= 0){
			$("#collapseOne").removeClass('collapse');
			$("#collapseOne_m").removeClass('collapse');
			$("#collapseOne").addClass('in');
			$("#collapseOne_m").addClass('in');
			$("#one3").addClass('myactive');
			$("#one3_m").addClass('myactive');

		}else if(lastid.indexOf("result") >= 0){
			$("#collapseTwo").removeClass('collapse');
			$("#collapseTwo_m").removeClass('collapse');
			$("#collapseTwo").addClass('in');
			$("#collapseTwo_m").addClass('in');
			$("#two2").addClass('myactive');
			$("#two2_m").addClass('myactive');


		}else if(lastid.indexOf("support") >= 0){
			$("#collapseThree").removeClass('collapse');
			$("#collapseThree_m").removeClass('collapse');
			$("#collapseThree").addClass('in');
			$("#collapseThree_m").addClass('in');
			$("#three6").addClass('myactive');
			$("#three6_m").addClass('myactive');
		}else if(lastid.indexOf("faq") >= 0){
			$("#collapseFour").removeClass('collapse');
			$("#collapseFour_m").removeClass('collapse');
			$("#collapseFour").addClass('in');
			$("#collapseFour_m").addClass('in');
			$("#four2").addClass('myactive');
			$("#four2_m").addClass('myactive');
		}else if(lastid.indexOf("user_guides") >= 0){
			$("#collapseFour").removeClass('collapse');
			$("#collapseFour_m").removeClass('collapse');
			$("#collapseFour").addClass('in');
			$("#collapseFour_m").addClass('in');
			$("#four4").addClass('myactive');
			$("#four4_m").addClass('myactive');
		}else if(lastid.indexOf("sendemail") >= 0){
			$("#collapseFour").removeClass('collapse');
			$("#collapseFour_m").removeClass('collapse');
			$("#collapseFour").addClass('in');
			$("#collapseFour_m").addClass('in');
			$("#four1").addClass('myactive');
			$("#four1_m").addClass('myactive');
		}else if(lastid.indexOf("howto") >= 0){
			$("#collapseFour").removeClass('collapse');
			$("#collapseFour_m").removeClass('collapse');
			$("#collapseFour").addClass('in');
			$("#collapseFour_m").addClass('in');
			$("#four3").addClass('myactive');
			$("#four3_m").addClass('myactive');
		}else if(lastid.indexOf("message") >= 0){
			$("#collapseOne").removeClass('collapse');
			$("#collapseOne_m").removeClass('collapse');
			$("#collapseOne").addClass('in');
			$("#collapseOne_m").addClass('in');
			$("#one10").addClass('myactive');
			$("#one10_m").addClass('myactive');
		}
		
		 // Read more
		$(".td").each(
			function( intIndex ) {
				var textToHide = $(this).text().substring(100);
				var visibleText = $(this).text().substring(0, 100);
				var count = $(this).text().length;
				if (count >100) {
					$(this)
						.html(visibleText + ('<span>' + textToHide + '</span>'))
					
							.append('<a id="read-more" title="Read More" style="display: block; cursor: pointer;">Read More&hellip;</a>')
							.click(function() {
								$(this).find('span').toggle();
								$(this).find('a:last').toggle();
							});
						
					$(this).find("span").hide();
				}else if (count == 0 ){
					$(this)
					.html(' ---')
				}else{
					$(this)
						.html(visibleText)
				}
			}
		)
	 
	 
		
		
    
});

//go back
function goBack() {
    window.history.back();
}
