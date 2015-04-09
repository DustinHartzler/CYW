 <?php 
/*
* Template Name: Pre Contact Form Page
*/
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Conquer Your Website</title>
    <link type="text/css" rel="stylesheet" href="https://forms.moon-ray.com/v2.4/include/minify/?g=moonrayCSS" />
    <link type="text/css" rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/smoothness/jquery-ui.css" />
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script> 
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/jquery-ui.min.js"></script>
    <script type="text/javascript" src="https://ajax.microsoft.com/ajax/jquery.validate/1.7/jquery.validate.min.js"></script> 
    <script type="text/javascript" src="https://forms.moon-ray.com/v2.4/include/minify/?g=moonrayJS"></script>
    <script type="text/javascript" src="https://forms.moon-ray.com/v2.4/include/minify/?g=moonrayJSCart"></script>
        <script type="text/javascript">
    jQuery(document).ready(function( $ ) {
    $(document).bind('ready.moonray_order_form_jb_321',function(){
        $("div.jb_321 form").moonrayOrderForm({
            timeZoneOffset:5,
            products:{"1":{"chargedToday":297,"chargedLater":[],"delayDate":false,"taxable":false,"shippable":false,"hasPaymentPlans":false}}
        }); 
    });
});
    </script>  
<?php wp_head(); ?>
</head>
<body>
        
    <div class="top-line"></div>
    <!-- header div start here -->
    <div id="header">
        <!-- wrapper div start here -->
        <div class="wrapper">
            <a href="http://ConquerYourWebsite.com" id="logo"><img src="<?php bloginfo('template_url'); ?>/images/logo_cyw.png" alt="" title="" /></a>
                <div class="main-menu-container" id="myslidemenu"></div>
            
            <div class="storytitle">
                <h3>Conquer Your Website</h3>
                <h5>Security | Backups | SEO | and More!</h5>
            </div>
        </div>
        <!-- wrapper div End here -->
    </div>
    <!-- header div End here -->
        
    <div class="bottom-line"></div>


<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<!-- wrapper div start here -->
	<div class="wrapper">
            <!-- page-content div start here -->
            <div id="page-content" class="full-width">
                    <div class="hr-line">
                        <h1>
                            <span><?php echo get_post_meta($post->ID, 'bonus', true); ?></span>
                        </h1>
                    </div>
                    <? the_content(); ?><br><br><br>
            </div>
            <!-- page-content div End here -->  
    </div>
    <!-- wrapper div End here -->

<?php endwhile; endif; ?>

<?php get_footer(); ?>