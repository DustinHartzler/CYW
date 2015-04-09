<?php get_header();

if (have_posts()) : while (have_posts()) : the_post(); ?>

<!-- wrapper div start here -->
	<div class="wrapper">
            <!-- page-content div start here -->
            <div id="page-content">
                    <div class="hr-line">
                        <h1>
                            <span><?php echo get_post_meta($post->ID, 'bonus', true); ?></span>
                        </h1>
                    </div>
                    <? the_content(); ?>
            </div>
            <!-- page-content div End here -->
            
        <!-- sidebar div start here -->
        <div class="sidebar">
        	<!-- widget-latest-news div start here -->
        	<div class="widget widget-latest-news">
                <div class="hr-line">
                    <h3 class="widget-title"><span>Useful Info</span><a class="more" href="#"></a></h3>
                </div>
                <ul class="latest-news-container">
                    <li class="post-item">
                        <div class="post-date">
                        	<span>Module</span><h4>00</h4>
                        </div>
                        <div class="post-details">
                            <h4><a href="<?php bloginfo('url'); ?>/module-00-a-tour-of-wordpress/">Start with Module 00</a></h4>
                            <p>You can work your way the course in any order, but I recommend starting with <a href="<?php bloginfo('url'); ?>/module-00-a-tour-of-wordpress/" style="text-decoration:underline;">Module 00</a></p>
                        </div>
                    </li>
                    <li class="post-item">
                        <div class="post-date">
                        	<span>Login</span><h4>INFO</h4>
                        </div>
                        <div class="post-details">
                            <h4><a href="#">Please bookmark this link:</a></h4>
                            <p><a href="http://conqueryourwebsite.com/wp-login.php" style="text-decoration:underline;">http://conqueryourwebsite.com/wp-login.php</a> so you can easily log back in</p>                        
                        </div>
                    </li>
                    <li class="post-item">
                        <div class="post-date">
                        	<span>Any</span><h4>?'s</h4>
						</div>
                        <div class="post-details">
                            <h4><a href="#">Questions or need support?</a></h4>
                            <p>Please use the form on the <a href="http://conqueryourwebsite.com/contact" style="text-decoration:underline;">Contact Page</a> and we will get back with you promptly.</p>
                        </div>
                    </li>
                </ul> 
			</div> 
            <!-- widget-latest-news End Here --> 
            
        </div>
        <!-- sidebar div End here -->
    </div>
    <!-- wrapper div End here -->

<?php endwhile; endif; ?>

<?php get_footer(); ?>