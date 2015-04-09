jQuery.noConflict();

jQuery(document).ready(function($){
	// Initialize capSlide plugin
	$(".capslide_img_cont").capslide({
		caption_color	: '5b5b5b',
		overlay_bgcolor : 'white',
		border			: '',
		showcaption	    : false
	});

	//Initilize portfolio2 column caption
	$("#port_slide_right li").hover(function(){
		$('.inner', this).stop().animate({right:"7px"},{queue:false,duration:500});
	}, function() {
		$('.inner', this).stop().animate({right:"-222px"},{queue:false,duration:500});
	});

	// Initialize prettyPhoto plugin
	$(".ic_caption a[rel^='prettyPhoto'], .inner a[rel^='prettyPhoto']").prettyPhoto({
		theme:'dark_rounded', 
		autoplay_slideshow: false, 
		overlay_gallery: false, 
		show_title: false
	});
	
	var $container = $('.portfolio-container');
	$container.isotope({
		filter: '*',
		animationOptions: {
			duration: 750,
			easing: 'linear',
			queue: false
		}
	});
	
	$('.category-filter a').click(function(){
		$('.category-filter').find('a').removeClass('active');
		$(this).addClass('active');
		var selector = $(this).attr('data-filter');
		$container.isotope({
			filter: selector,
			animationOptions: {
				duration: 750,
				easing: 'linear',
				queue: false
			}
		});
		return false;
	});	
});