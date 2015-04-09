<?php
/* 
Plugin Name: Scrivener's Custom Login
Plugin URI: http://YourWebsiteEngineer.com
Description: Let's you display a custom login screen
Version: 1.0
Author: Dustin Hartzler
Author URI: http://YourWebsiteEngineer.com
*/

function custom_login_css(){
	echo '<link rel="stylesheet" type="text/css" href="' . plugins_url( '/style.css' , __FILE__ ) . '"/> ';
}
add_action ('login_head','custom_login_css');

add_filter('login_headerurl', 'custom_login_header_url');
function custom_login_header_url($url){
	return 'http://learnscrivenerfast.com';
}