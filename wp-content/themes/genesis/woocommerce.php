<?php
/**
 * The template for displaying pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that
 * other "pages" on your WordPress site will use a different template.
 *
 * @package WordPress
 * @subpackage Twenty_Fifteen
 * @since Twenty Fifteen 1.0
 */
 global $post;

get_header(); ?>
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
                    <?php $pods = pods('theme_settings');?>
                    <a class="animated" href="<?php echo get_option('home'); ?>"><img class="animated" src="<?php echo $pods->field('logo_ko.guid'); ?>" alt="Genlrn.com Logo"/></a>
                </div>
                <nav id="shop"><?php wp_nav_menu( array( 'theme_location' => 'shop', 'container' => false ) ); ?></nav>
                <?php wp_nav_menu( array( 'menu' => 'Navigation', 'container' => false ) ); ?>
                 </nav>
</div>
<div id="spacer"></div>
<?php
	//$obj = get_queried_object();
        $obj = get_category();
	$color = get_post_field( 'color', $obj->term_id ) ? get_post_field( 'color', $obj->term_id ) : 'red' ;
?>

<?php if( $obj->term_id == 1534 ){ ?>
	<header id="contentHead" class="centered border" data-color="<?= $color ?>">

		<?php $id = $obj->ID; $icon = get_post_meta( $id, 'page-icon', true );  if (!empty($icon)) : ?>
			<img id="sectionIcon" src="<?php the_field('page-icon', $obj->ID ); ?>" alt="<?= $obj->post_title; ?>"/>
		<?php endif ;?>
		
		<h1><?= $obj->post_title; ?></h1>
		
	</header>
	
<?php } ?>
<section id="main" class="centered border woocommerce" >
	<?//= do_shortcode("[huge_it_slider id='2']"); ?>
	<article>
	
		<?php woocommerce_content(); ?>
		
    </article>
   

</section>
<?php get_footer(); ?>
