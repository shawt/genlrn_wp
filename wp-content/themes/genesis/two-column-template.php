<?php
/*
Template Name: Store
*/
?>
<?php get_header(); ?>
<?php if ( have_posts() ) : ?>
<?php while ( have_posts() ) : the_post(); ?>

<body class="inner">
<header id="homePage" class="centered border">
    <div class="left">
        <div class="top">
            <?php $pods = pods('theme_settings'); ?>
            <a href="<?php echo get_option('home'); ?>"><img id="logo" src="<?php echo $pods->field('logo.guid'); ?>" alt="Genesis Learning Logo" data-ko="<?php echo $pods->field('logo_ko.guid'); ?>"/></a>
            <input id="menuToggle" type="button">
            <div id="afterLogo" class="clear"></div>
           </div>
        <div class="middle">
        </div>
    </div>
    <div class="clear"></div>
</header>
<div class="stuck full">
    <nav id="mainNav" class="centered border">
                <div id="logoHolder">
                    <?php $pods = pods('theme_settings'); ?>
                    <a class="animated" href="<?php echo get_option('home'); ?>"><img class="animated" src="<?php echo $pods->field('logo_ko.guid'); ?>" alt="Genlrn.com Logo"/></a>
                </div>
                <nav id="shop"><?php wp_nav_menu( array( 'theme_location' => 'shop', 'container' => false ) ); ?></nav>
                <?php wp_nav_menu( array( 'menu' => 'Navigation', 'container' => false ) ); ?>
                <!--<ul>
                    <li><a href="#">Genesis Learning</a></li>
                    <li><a href="#">Workshops</a></li>
                    <li><a href="#">Library Services</a></li>
                    <li><a href="#">Curriculum</a></li>
                    <li><a href="#">Professional Development</a></li>
                    <li><a href="#">IT Services</a></li>
                    <li><a href="#">Resources</a></li>
                    <li><a href="#">Mission</a></li>
                    <li><a href="#">Contact Us</a></li>
                </ul>-->
    </nav>
</div>
<div id="spacer"></div>
<header id="contentHead" class="centered border" data-color="<?php the_field('color'); ?>">

     <?php $id = get_the_ID(); $icon = get_post_meta( $id, 'page-icon', true );  if (!empty($icon)) : ?>


    <img id="sectionIcon" src="<?php the_field('page-icon'); ?>" alt="<?php the_title(); ?>"/>
    <?php endif ; ?>
    <h1><?php the_title(); ?></h1>
</header>

<section id="main" class="centered border" >
    <article class="bodyText">

            <?php if (has_post_thumbnail( $post->ID ) ): ?>
            <?php $photo = wp_get_attachment_url( get_post_thumbnail_id($post->ID, 'thumbnail') ); ?>
                <div id="featured-image"><img src="<?php echo $photo ?>" alt="Featured Image"/></div>
            <?php endif; ?>
        <?php echo do_shortcode("[huge_it_slider id='2']"); ?>
            <?php the_field('content_before'); ?>
        <?php if ( is_active_sidebar( 'store-before' ) ) : ?>
	<div class="primary-sidebar widget-area" role="complementary">
		<?php dynamic_sidebar( 'store-before' ); ?>
	</div><!-- #primary-sidebar -->
<?php endif; ?>
        <div  id="storeMain">
        <?php the_content(); ?>
        
        <?php if ( is_active_sidebar( 'store-after' ) ) : ?>
	<div class="primary-sidebar widget-area" role="complementary">
		<?php dynamic_sidebar( 'store-after' ); ?>
	</div><!-- #primary-sidebar -->
<?php endif; ?>
                

        <?php the_field('content_after'); ?>
            </div>
         <aside id="storeAside">
        <?php if ( is_active_sidebar( 'store-main' ) ) : ?>
	<div class="storeWidgets widget-area" role="complementary">
		<?php dynamic_sidebar( 'store-main' ); ?>
	</div><!-- #primary-sidebar -->
<?php endif; ?>
    </aside>
    </article>
   
<div class="clear"></div>
</section>





<?php endwhile; ?>
<?php else : ?>
<h2>No Text Found</h2>
<?php endif; ?>

<?php get_footer(); ?>
