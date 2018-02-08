<?php $id = get_the_ID();
$schoolopen = get_post_meta( $id, 'attend', true );
                if ($schoolopen =="1") {
                    echo "<article id='post-" . $id . "'><div class='students'>Students Only</div>";
                } else if ($schoolopen =="2"){
                    echo "<article id='post-" . $id . "'><div class='parents'>Parents Only</div>";
                } else {
                     echo "<article id='post-" . $id . "'><div class='both'>Parents and Students</div>";   
                }
                ?>
<?php if (has_post_thumbnail( $post->ID ) ): ?>
            
            <!--Get Page Featured Image and use it as the background-->
            <?php $url = wp_get_attachment_url( get_post_thumbnail_id($post->ID, 'thumbnail') ); ?>
            <div class="imageContainer"><a class="lightbox" href="<?php echo $url ?>"><img src="<?php echo $url ?>" /></a></div>
        
		<?php endif; ?>	

<a class="postLink" href="<?php echo get_permalink(); ?>"><h2><?php echo get_the_title(); ?></h2></a>
                <h3><?php the_field('event_date'); ?>  &nbsp;  <?php the_field('event_time'); ?></h3>
                <h3><?php the_field('location'); ?></h3>
<?php the_tags( '<ul id="tags"><li>', '</li><li>', '</li></ul>' ); ?>
<div class="clear"></div>
                <div class="text"><?php the_content(); ?></div>

<?php  $id = get_the_ID();
$details = get_post_meta( $id, 'additional_details', true );
if (!empty($details)) { echo "<div class='details'>" . $details . "</div>"; }
?>
                <div class="meta"><?php edit_post_link( __( 'Edit', 'twentyfifteen' ), '<span class="edit-link">', '</span><!-- .entry-footer -->' ); ?></div>
</article>
