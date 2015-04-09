jQuery.noConflict();

jQuery(document).ready(function ($) {
	
	//MENU CUFON...
	Cufon('.main-menu-container > ul:first > li.current-page-item > a', {
		hover: { color: '#6c3d00'},
		color: '#6c3d00',
		fontFamily: 'Helvetica Inserat LT Std',
		fontSize: '18px',
		textShadow: '1px 1px 1px #deae70'
	});
	Cufon('.main-menu-container > ul:first > li > a', {
		hover: { color: '#6c3d00', textShadow: '1px 1px 1px #deae70' },
		color: '#f9f9f9',
		fontFamily: 'Helvetica Inserat LT Std',
		fontSize: '18px',
		textShadow: '1px 1px 1px #79582e'
	});
	Cufon.replace('.main-menu-container > ul:first > li:first', {
		color: '#f9f9f9',
		fontFamily: 'Helvetica Inserat LT Std',
		fontSize: '18px',
		textShadow: '1px 1px 1px #79582e'
	});
	Cufon.replace('.main-menu-container > ul:first > li.current-page-item:first > a', {
		color: '#6c3d00',
		fontFamily: 'Helvetica Inserat LT Std',
		fontSize: '18px',
		textShadow: '1px 1px 1px #deae70'
	});

	//STORY TITLE CUFON...
	Cufon.replace('.storytitle h3', { textShadow: '0px 1px 1px #fcfaf7', fontFamily: 'Helvetica Inserat LT Std' , hover:true });
	Cufon.replace('.storytitle h5', { textShadow: '0px 1px 1px #fcfaf7', fontFamily: 'Helvetica LT CondensedLight' , hover:true });
	
	//PAGE AND BLOG DETAILS CUFON...
	Cufon.replace('.hr-line h1', { textShadow: '0px 1px 1px #21365f' , fontFamily: 'Helvetica Inserat LT Std' , hover:true });
	Cufon.replace('.hr-line h2', { textShadow: '0px 1px 1px #5b5048' , fontFamily: 'Helvetica Inserat LT Std' , hover:true });
	
	Cufon.replace('.post-date', { textShadow: '0px 1px 1px #fcfaf7', fontFamily: 'Helvetica Inserat LT Std' , hover:true });
	Cufon.replace('h2.hr-line1', { textShadow: '0px 1px 1px #fcfaf7', fontFamily: 'Helvetica Inserat LT Std' , hover:true });		
	Cufon.replace('.home-recent-news h3, .inner h3, .portfolio-container h3, .portfolio-detail h2', { textShadow: '1px 1px 1px #ffffff', fontFamily: 'Helvetica Inserat LT Std' , hover:true });		
	Cufon.replace('.portfolio-read, .portfolio-zoom', { fontFamily: 'Helvetica Inserat LT Std' , hover:true });		
	
	//OTHERS...
	Cufon.replace('.featured-services-big ul li, .featured-services-small ul li', { textShadow: '0px -1px 1px #564226' , fontFamily: 'Helvetica Inserat LT Std' , hover:true });
	
	//SIDEBAR WIDGET TITLE CUFON...	
	Cufon.replace('.sidebar .widget h3', { textShadow: '0px 1px 1px #5b5048', fontFamily: 'Helvetica Inserat LT Std' , hover:true });	
	Cufon.replace('#contact-widget-container .widget h3', { textShadow: '0px 1px 1px #fcfaf7', fontFamily: 'Helvetica Inserat LT Std' , hover:true });
	
	
	//FOOTOR WIDGET TITLE...
	Cufon.replace('.footer-widgets .widget h3', { textShadow: '0px 1px 1px #221912', fontFamily: 'Helvetica Inserat LT Std' , hover:true });
});	