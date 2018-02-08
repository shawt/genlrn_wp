<?php
/*
Template Name: Download Doc
*/
?>
<?php get_header(); ?>
<?php if ( have_posts() ) : ?>
<?php while ( have_posts() ) : the_post(); ?>
<body class="inner">
<header id="homePage" class="centered border">
    <div class="left">
        <div class="top">
            <?php  $pods = pods('theme_settings'); ?>
            <a href="<?php  echo get_option('home'); ?>"><img id="logo" src="<?php  echo $pods->field('logo.guid'); ?>" alt="Genesis Learning Logo" data-ko="<?php  echo $pods->field('logo_ko.guid'); ?>"/></a>
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
                    <?php  $pods = pods('theme_settings'); ?>
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
<header id="contentHead" class="centered border" data-color="<?php  the_field('color'); ?>">
    <h1 class="center"><?php  the_title(); ?></h1>
</header>
    <?php $id = get_the_ID();  $showform = get_post_meta( $id, 'show-form', true ); ?>
    <?php  if ($showform =="1") : ?>
        <section id="main" class="centered border split" >
    <?php else : ?>
        <section id="main" class="centered border" >
    <?php endif; ?>
    <article class="bodyText">
        <?php  if (has_post_thumbnail( $post->ID ) ): ?>
        <?php  $photo = wp_get_attachment_url( get_post_thumbnail_id($post->ID, 'thumbnail') ); ?>
            <div id="featured-image"><img src="<?php  echo $photo  ?>" alt="Featured Image"/></div>
        <?php  endif; ?>
        <div id="fileDownload">
            <?php $attachment_id = get_field('document');
$url = wp_get_attachment_url( $attachment_id );
$title = get_the_title( $attachment_id );

$filetype = wp_check_filetype($url);

if( get_field('document') ): ?>
         <p id="fileLink" data-ext="<?php echo $filetype['ext'] ?>"><?php echo $title . '.' . $filetype['ext'] ?></p>

<?php endif; ?>
<script type="text/javascript">
    jQuery(document).ready(function(){
        jQuery(document).bind('gform_confirmation_loaded', function(){
        var url = '<?php echo $url; ?>';
            var trackingCode = '_gaq.push(["_trackEvent","Download","PDF",this.href]);';
           jQuery('#fileLink').wrap("<a href='"+url+"'></a>");
            jQuery('#fileDownload').addClass('enable');
        });
    });
</script>
        </div>
        <?php  the_content(); ?>
    </article>
    <?php  if ($showform =="1") : ?>
    <aside>
        <div class='group blue'>
                      <?php  $pods = pods('social'); ?>
                        <h4 class="center">Call or eMail for more details and pricing.</h4>
                        <h5 class="center"><a href="tel:<?php  echo $pods->field('phone'); ?>"><?php  echo $pods->field('phone'); ?></a><br>
                        <a href="mailto:<?php  echo $pods->field('email'); ?>"><?php  echo $pods->field('email'); ?></a></h5>
                        <nav class="socialMenu">
                            <ul>
                                <li><a class="mail" href="mailto:<?php  echo $pods->field('email'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/mail-icon-inner-ko.png" width="37" height="37" alt="email us"/></a></li>
                                <li><a class="phone" href="tel:<?php  echo $pods->field('phone'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/tel-icon-inner-ko.png" width="37" height="37" alt="call us"/></a></li>
                                <li><a class="twit" href="<?php  echo $pods->field('twitter'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/twit-icon-inner-ko.png" width="37" height="37" alt="Find us on Twitter"/></a></li>
                                <li><a class="lin" href="<?php  echo $pods->field('linkedin'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/lin-icon-inner-ko.png" width="37" height="37" alt="Find us on LinkedIn"/></a></li>
                                <li><a class="g" href="<?php  echo $pods->field('g'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/g-icon-inner-ko.png" width="37" height="37" alt="Find us on Google+"/></a></li>
                            </ul>
                        </nav>
                        <?php  $pods = new Pod('theme_settings'); ?>
                        <a class="contactUs" href="<?php  echo $pods->field('contact_us.guid'); ?>" title="Contact Us">Contact Us</a>
                </div>
    </aside>
    <?php  endif; ?>


</section>
<?php  endwhile; ?>
<?php  else : ?>
<h2>No Text Found</h2>
<?php  endif; ?>
<?php  get_footer(); ?>
