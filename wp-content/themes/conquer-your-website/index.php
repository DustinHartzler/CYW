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
        
    </div>
    <!-- wrapper div End here -->

<?php endwhile; endif; ?>
<?php get_sidebar(); ?>
<?php get_footer(); ?>