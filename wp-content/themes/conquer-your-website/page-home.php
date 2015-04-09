<?php 
/*
* Template Name: Home Page w/ Video
*/
?>
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
	
    <!-- banner div start here -->
    <div class="banner">
        <!-- wrapper div start here -->
        <div class="wrapper">
            <div class="slider-wrapper theme-default">
                <iframe src="http://fast.wistia.net/embed/iframe/mnthalb9vw?controlsVisibleOnLoad=true&version=v1&videoHeight=360&videoWidth=640&volumeControl=true&videoFoam=true" allowtransparency="true" frameborder="0" scrolling="no" class="wistia_embed" name="wistia_embed" width="640" height="360"></iframe>
                <script src='//fast.wistia.com/static/iframe-api-v1.js'></script>            
            </div>
            <ul class="video-series-nav">
                <li><a class="active" href="http://conqueryourwebsite.com/video-6-new-features-in-wordpress-3-6/">6 New Features</a></li>
                <li><span>Rookie Mistakes <a href="http://conqueryourwebsite.com/free-training"><img src="https://www.createawesomeonlinecourses.com/wp-content/themes/custom/images/videoseries/keep-eye-out.png" /></a></span></li>
                <li><span>The Tools! <a href="http://conqueryourwebsite.com/free-training"><img src="https://www.createawesomeonlinecourses.com/wp-content/themes/custom/images/videoseries/keep-eye-out.png" /></a></span></li>
                <li><span>The Next Step <a href="http://conqueryourwebsite.com/"><img src="https://www.createawesomeonlinecourses.com/wp-content/themes/custom/images/videoseries/keep-eye-out.png" /></a></span></li>                        
            </ul>
        </div>
        <!-- wrapper div ends here -->
    </div>
    <!-- banner div ends here -->
        
    <!-- wrapper div start here -->
    <div class="wrapper">
        <ul class="video-series-share">
            <h3 style="padding-top:10px;">Know someone that would love this video series? Send it to 'em!</h3>
            <li>
                <div class="fb-like" data-href="http://www.conqueryourwebsite.com" data-send="false" data-layout="button_count" data-width="44" data-show-faces="false"></div>
            </li>
            <li>
                <a href="https://twitter.com/share" class="twitter-share-button" data-lang="en" data-count="none" data-url="http://www.conqueryourwebsite.com" data-text="Free Video Series: Learn the new features in WordPress 3.6 and how to use them:">Tweet</a>
                <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
            </li>
            <li>
                <!-- Place this tag where you want the share button to render. -->
                <div class="g-plus" data-action="share" data-annotation="none" data-href="http://www.conqueryourwebsite.com"></div>
                
                <!-- Place this tag after the last share tag. -->
                <script type="text/javascript">
                  (function() {
                    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
                    po.src = 'https://apis.google.com/js/plusone.js';
                    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
                  })();
                </script>
            </li>
            <li>
                <a href="http://pinterest.com/pin/create/button/?url=https://www.conqueryourwebsite.com/video-series-5-steps/&description=Video Series: 5 Steps" class="pin-it-button" count-layout="none"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a>
            </li>
            <li>
                <script type="text/javascript" src="http://platform.linkedin.com/in.js"></script><script type="in/share" data-url="https://www.conqueryourwebsite.com/video-series-5-steps/" data-counter="none"></script>
            </li>
        </ul>        
        <div class="fb-comments" data-href="http://conqueryourwebsite.com" data-width="960" data-num-posts="10"></div>
    </div> 
    <!-- wrapper div end here -->

    <div class="bottom-line"></div>
    
    <!-- footer div start here -->
    <div id="footer">
        <!-- footer-info div start here --> 
        <div class="footer-info">
            <div class="wrapper">
                <p><? echo "Copyright &copy; ".date('Y'); ?> <a href="http://YourWebsiteEngineer.com">YourWebsiteEngineer.com</a>. All Rights Reserved</p>
            </div>
        </div>
        <!-- footer-info Div End Here --> 
    </div>   
    <!-- wrapper div End here -->
     <?php wp_footer(); ?>
</body>

</html>