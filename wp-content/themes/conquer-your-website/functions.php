<?php

function add_scripts() {
	// jQuery Header
	wp_enqueue_script("jquery");
	wp_enqueue_script("custom", get_template_directory_uri() ."/js/custom.js", array ("jquery"), "1.0");
	wp_enqueue_script("myslidemenu", get_template_directory_uri()."/js/jqueryslidemenu.js", array ("jquery"), "1.0");
	wp_enqueue_script("capSlide", get_template_directory_uri()."/js/plugins/jquery.capSlide.js", array ("jquery"), "1.0");
	wp_enqueue_script("prettyPhoto", get_template_directory_uri()."/js/plugins/jquery.prettyPhoto.js", array ("jquery"), "1.0");
	wp_enqueue_script("isotope", get_template_directory_uri()."/js/plugins/jquery.isotope.min.js", array ("jquery"), "1.0");
	wp_enqueue_script("tinynav", get_template_directory_uri()."/js/tinynav.min.js", array ("jquery"), "1.0");
	wp_enqueue_script("bubble", get_template_directory_uri()."/js/plugins/jquery-bubble-popup-v3.min.js", array ("jquery"), "1.0");


	// jQuery Footer
	// wp_enqueue_script("jquery-min", get_template_directory_uri() ."/js/jquery.min.js", array ("jquery"), "1.0");
	// wp_enqueue_script("validate", get_template_directory_uri() ."/js/jquery.validate.min.js", array ("jquery"), "1.0");
	// wp_enqueue_script("contact", get_template_directory_uri() ."/js/contact.js", array ("jquery"), "1.0");

	// Stylesheets	
	wp_enqueue_style("style_css", get_stylesheet_directory_uri()."/style.css", false, false, "all");
	wp_enqueue_style("responsive", get_stylesheet_directory_uri()."/css/responsive.css", false, false, "all");
	wp_enqueue_style("capSlide_css", get_stylesheet_directory_uri()."/css/capSlide.css", false, false, "all");
	wp_enqueue_style("prettyPhoto_css", get_stylesheet_directory_uri()."/css/prettyPhoto.css", false, false, "all");
	wp_enqueue_style("isotopeStyle_css", get_stylesheet_directory_uri()."/css/isotopeStyle.css", false, false, "all");
	wp_enqueue_style("default_css", get_stylesheet_directory_uri()."/css/themes/default/default.css", false, false, "all");
	wp_enqueue_style("menu_css", get_stylesheet_directory_uri()."/css/menu.css", false, false, "all");
	//wp_enqueue_style("bubble", get_stylesheet_directory_uri()."/jquery-bubble-popup-v3.css", false, false, "all");

	// Cufon Fonts	
	wp_enqueue_script("allcufon", get_template_directory_uri() ."/js/allcufon.js", array ("jquery"), "1.0");
	wp_enqueue_script("cufon", get_template_directory_uri() ."/js/cufon-yui.js", array ("jquery"), "1.0");
	wp_enqueue_script("Helvetica_LT_Compressed_400", get_template_directory_uri() ."/js/Helvetica_LT_Compressed_400.font.js", array ("jquery"), "1.0");
	wp_enqueue_script("Helvetica_LT_CondensedLight_300", get_template_directory_uri() ."/js/Helvetica_LT_CondensedLight_300.font.js", array ("jquery"), "1.0");
	wp_enqueue_script("Helvetica_Inserat_LT_Std_800", get_template_directory_uri() ."/js/Helvetica_Inserat_LT_Std_800.font.js", array ("jquery"), "1.0");
}

add_action ('wp_enqueue_scripts', 'add_scripts');


add_theme_support( 'menus' );
register_nav_menu('main', 'Main Navigation Menu');