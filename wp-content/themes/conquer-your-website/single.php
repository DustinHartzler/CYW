<?php get_header();

if (have_posts()) : while (have_posts()) : the_post(); ?>

<!-- wrapper div start here -->
	<div class="wrapper">
            <!-- page-content div start here -->
            <div id="page-content" class="full-width">
                    <div class="hr-line">
                        <h1>
                            <span>Module <?php echo get_post_meta($post->ID, 'module', true); ?></span>
                        </h1>
                    </div>
                    <? the_content(); ?>
                    <div class="previousmodule"><?php previous_post_link( '%link', '%title &rarr;' ); ?></div>
                    <div class="nextmodule"><?php next_post_link( '%link', '&larr; %title' ); ?></div>
            </div>
            <!-- page-content div End here -->  
    </div>
    <!-- wrapper div End here -->

<?php endwhile; endif; ?>

<?php get_footer(); ?>