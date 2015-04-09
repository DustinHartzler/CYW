<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Conquer Your Website</title>
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=375414099210429";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<?php wp_head(); ?>
</head>
<body>
        
    <div class="top-line"></div>
    <!-- header div start here -->
    <div id="header">
        <!-- wrapper div start here -->
        <div class="wrapper">
            <a href="index-2.html" id="logo"><img src="<?php bloginfo('template_url'); ?>/images/logo_cyw.png" alt="" title="" /></a>
            <?php wp_nav_menu( array(   'menu' => 'main', 
                                        'sort_column' => 'menu_order', 
                                        'container_class' => 'main-menu-container',
                                        'container_id'    => 'myslidemenu', 
                                        'menu_id' => 'main-menu' ) ); ?>
            
            <div class="storytitle">
                <h3>Conquer Your Website</h3>
                <h5>Security | Backups | SEO | and More!</h5>
            </div>
        </div>
        <!-- wrapper div End here -->
    </div>
    <!-- header div End here -->