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
            <?php if ( is_active_sidebar( 'home_right_1' ) ) : ?>
                    <div id="widget-area" class="widget widget-latest-news">
                        <?php dynamic_sidebar( 'home_right_1' ); ?>
                    </div><!-- .widget-area -->
                <?php endif; ?>
        </div>
        <!-- sidebar div End here -->