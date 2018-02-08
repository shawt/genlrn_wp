<?php
 /*
Template Name: 3 Column Page
*/ ?>
<?php  get_header(); ?>
<?php  if ( have_posts() ) : ?>
<?php
    while ( have_posts() ) :
    the_post();
    ?>
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
                <?php  wp_nav_menu( array( 'menu' => 'Navigation', 'container' => false ) ); ?>
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
     <?php $id = get_the_ID(); $icon = get_post_meta( $id, 'page-icon', true );  if (!empty($icon)) : ?>


    <img id="sectionIcon" src="<?php the_field('page-icon'); ?>" alt="<?php the_title(); ?>"/>
    <?php endif ; ?>
    <h1><?php  the_title(); ?></h1>
</header>
<section id="main" class="centered border" >
    <article class="bodyText">
        <?php  if (has_post_thumbnail( $post->ID ) ): ?>
        <?php  $photo = wp_get_attachment_url( get_post_thumbnail_id($post->ID, 'thumbnail') ); ?>
            <div id="featured-image"><img src="<?php  echo $photo  ?>" alt="Featured Image"/></div>
        <?php  endif; ?>
        
 <?php
global $more;
$more = 0;
?>
    <div class="shortContent"><?php the_content() ?></div>
<?php $more = 1; ?>
<div class="allContent">
    <?php the_content() ?>
    <input id="readLessButton" type="button" value="Read Less"/>
</div>
    </article>
    <article class="cols">
        <?php  $id = get_the_ID(); ?>
        <div class='col'>
            <?php  if( have_rows('col-1-group') ): while ( have_rows('col-1-group') ) : the_row(); ?>
                <div class="group">
                <div class='iconContainer'><img class="icon" src="<?php  the_sub_field('col-1-photo'); ?>" alt="<?php  the_sub_field('col-1-headline'); ?>"/></div>
                <h3 class="center"><?php  the_sub_field('col-1-headline'); ?></h3>
                <div class="description">
                    <?php $islist = get_sub_field('col-1-is-list'); if( $islist =="list") : ?>
                    <?php  $liststyle = get_sub_field('col-1-list-style'); ?>
                    <?php  if ($liststyle =="check") {  echo "<ul>";  }
                           elseif($liststyle =="circle") { echo "<ul class='course'>"; }  ?>
                    <?php if( have_rows('col-1-list') ):  while ( have_rows('col-1-list') ) :  the_row(); ?>
                            <li><?php  the_sub_field('col-1-list-item'); ?></li>
                    <?php  endwhile; ?>
                    <?php  endif; ?>
                    </ul>
                    <?php  else : ?>
                    <?php  the_sub_field('col-1-content')  ?>
                    <?php  endif; ?>
                </div>
            </div>
            <?php  endwhile; ?>
            <?php  else : ?>
            <!--no rows found-->
            <?php  endif; ?>
        </div>
        <div class='col'>
                <?php if( have_rows('col-2-group') ): while ( have_rows('col-2-group') ) : the_row(); ?>
                <div class="group">
                <div class='iconContainer'><img class="icon" src="<?php  the_sub_field('col-2-photo'); ?>" alt="<?php  the_sub_field('col-1-headline'); ?>"/></div>
                <h3 class="center"><?php  the_sub_field('col-2-headline'); ?></h3>
                <div class="description">
                <?php $islist = get_sub_field('col-2-is-list'); if( $islist =="list") : ?>
                <?php  $liststyle = get_sub_field('col-2-list-style'); ?>
                <?php if ($liststyle =="check") { echo "<ul>"; }
                      elseif($liststyle =="circle") { echo "<ul class='course'>"; } ?>
                <?php if( have_rows('col-2-list') ):  while ( have_rows('col-2-list') ) : the_row(); ?>
                    <li><?php  the_sub_field('col-2-list-item'); ?></li>
                <?php  endwhile; ?>
                <?php  endif; ?>
                </ul>
                <?php  else : ?>
                <?php  the_sub_field('col-2-content')  ?>
                <?php  endif; ?>
                </div>
            </div>
            <?php  endwhile; ?>
            <?php  else : ?>
            <!--no rows found-->
            <?php  endif; ?>
        </div>
    <?php $showform = get_post_meta( $id, 'show-form', true ); ?>
        <?php  if ($showform =="1") : ?>
        <?php  $whichForm = get_post_meta( $id, 'col-3-form-type', true ); ?>
        <?php  if ($whichForm =="compact") : ?>
        <div class="col">
        <?php if( have_rows('col-3-group') ): while ( have_rows('col-3-group') ) : the_row(); ?>
                <div class="group">
                    <div class='iconContainer'><img class="icon" src="<?php  the_sub_field('col-3-photo'); ?>" alt="<?php  the_sub_field('col-2-headline'); ?>"/></div>
                    <h3 class="center"><?php  the_sub_field('col-3-headline'); ?></h3>
                    <div class="description twitter">
                        <?php $islist = get_sub_field('col-3-is-list'); if( $islist =="list") : ?>
                        <?php $liststyle = get_sub_field('col-3-list-style'); ?>
                        <?php if ($liststyle =="check") { echo "<ul>"; }
                              elseif($liststyle =="circle") { echo "<ul class='course'>"; } ?>
                            <?php

                            if( have_rows('col-3-list') ): while ( have_rows('col-3-list') ) : the_row(); ?>
                                <li><?php  the_sub_field('col-3-list-item'); ?></li>
                            <?php  endwhile; ?>
                            <?php  endif; ?>
                        </ul>
                        <?php  else : ?>
                        <?php  the_sub_field('col-3-content')  ?>
                        <?php  endif; ?>
                    </div>

                    <?php  endwhile; ?>
                    <?php  else : ?>
                    <!--no rows found-->
                    <?php  endif; ?>
                </div>
                <div class='group blue'>
                      <?php  $pods = pods('social'); ?>
                        <h4 class="center">Call or eMail for more details and pricing.</h4>
                        <h5 class="center"><a href="tel:<?php  echo $pods->field('phone'); ?>"><?php  echo $pods->field('phone'); ?></a><br>
                        <a href="mailto:<?php  echo $pods->field('email'); ?>"><?php  echo $pods->field('email'); ?></a></h5>
                        <nav class="socialMenu">
                            <ul>
                                <li><a class="mail" href="mailto:<?php  echo $pods->field('email'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/mail-icon-inner-ko.png" width="37" height="37" alt="email us"/></a></li>

                                <li><a class="twit" href="<?php  echo $pods->field('twitter'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/twit-icon-inner-ko.png" width="37" height="37" alt="Find us on Twitter"/></a></li>
                                <li><a class="lin" href="<?php  echo $pods->field('linkedin'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/lin-icon-inner-ko.png" width="37" height="37" alt="Find us on LinkedIn"/></a></li>
                                <li><a class="g" href="<?php  echo $pods->field('g'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/g-icon-inner-ko.png" width="37" height="37" alt="Find us on Google+"/></a></li>
                            </ul>
                        </nav>

                        <?php  $pods = pods('theme_settings'); ?>
                        <a class="contactUs" href="<?php  echo $pods->field('contact_us.guid'); ?>" title="Contact Us">Contact Us</a>
                </div>
        <?php  elseif ($whichForm =="tower") : ?>
                <div class='col blue'>
                    <?php  $pods = pods('social'); ?>
                    <h4 class="center">Call or eMail for more details and pricing.</h4>
                    <h5 class="center"><a href="tel:<?php  echo $pods->field('phone'); ?>"><?php  echo $pods->field('phone'); ?></a><br>
                    <a href="mailto:<?php  echo $pods->field('email'); ?>"><?php  echo $pods->field('email'); ?></a></h5>
                    <nav class="socialMenu">
                        <ul>
                            <li><a class="mail" href="mailto:<?php  echo $pods->field('email'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/mail-icon-inner-ko.png" width="37" height="37" alt="email us"/></a></li>

                            <li><a class="twit" href="<?php  echo $pods->field('twitter'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/twit-icon-inner-ko.png" width="37" height="37" alt="Find us on Twitter"/></a></li>
                            <li><a class="lin" href="<?php  echo $pods->field('linkedin'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/lin-icon-inner-ko.png" width="37" height="37" alt="Find us on LinkedIn"/></a></li>
                            <li><a class="g" href="<?php  echo $pods->field('g'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/g-icon-inner-ko.png" width="37" height="37" alt="Find us on Google+"/></a></li>
                        </ul>
                    </nav>

                    <?php  $pods = new Pod('theme_settings'); ?>
                    <a class="contactUs" href="<?php  echo $pods->field('contact_us.guid'); ?>" title="Contact Us">Contact Us</a>
                </div>
        <?php  endif; ?>
    <?php else : ?>
    <div class="col">
        <?php if( have_rows('col-3-group') ): while ( have_rows('col-3-group') ) : the_row(); ?>
                <div class="group">
                    <div class='iconContainer'><img class="icon" src="<?php  the_sub_field('col-3-photo'); ?>" alt="<?php  the_sub_field('col-2-headline'); ?>"/></div>
                    <h3 class="center"><?php  the_sub_field('col-3-headline'); ?></h3>
                    <div class="description twitter">
                        <?php $islist = get_sub_field('col-3-is-list'); if( $islist =="list") : ?>
                        <?php $liststyle = get_sub_field('col-3-list-style'); ?>
                        <?php if ($liststyle =="check") { echo "<ul>"; }
                              elseif($liststyle =="circle") { echo "<ul class='course'>"; } ?>
                            <?php

                            if( have_rows('col-3-list') ): while ( have_rows('col-3-list') ) : the_row(); ?>
                                <li><?php  the_sub_field('col-3-list-item'); ?></li>
                            <?php  endwhile; ?>
                            <?php  endif; ?>
                        </ul>
                        <?php  else : ?>
                        <?php  the_sub_field('col-3-content')  ?>
                        <?php  endif; ?>
                    </div>

                    <?php  endwhile; ?>
                    <?php  else : ?>
                    <!--no rows found-->
                    <?php  endif; ?>
                </div>
    <?php  endif; ?>
    </article>
 <?php $showWide = get_post_meta( $id, 'wide_bar', true ); ?>
        <?php  if ($showWide =="1") : ?>
<article class="wide"><div class='blue' style="padding-bottom: 10px;">
                    <?php  $pods = pods('social'); ?>
                    <h4 class="center">Call or eMail for more details and pricing.</h4>
                    <h5 class="center"><a href="tel:<?php  echo $pods->field('phone'); ?>"><?php  echo $pods->field('phone'); ?></a><br>
                    <a href="mailto:<?php  echo $pods->field('email'); ?>"><?php  echo $pods->field('email'); ?></a></h5>
                    <nav class="socialMenu">
                        <ul>
                            <li><a class="mail" href="mailto:<?php  echo $pods->field('email'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/mail-icon-inner-ko.png" width="37" height="37" alt="email us"/></a></li>

                            <li><a class="twit" href="<?php  echo $pods->field('twitter'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/twit-icon-inner-ko.png" width="37" height="37" alt="Find us on Twitter"/></a></li>
                            <li><a class="lin" href="<?php  echo $pods->field('linkedin'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/lin-icon-inner-ko.png" width="37" height="37" alt="Find us on LinkedIn"/></a></li>
                            <li><a class="g" href="<?php  echo $pods->field('g'); ?>"><img src="<?php  echo get_template_directory_uri(); ?>/images/g-icon-inner-ko.png" width="37" height="37" alt="Find us on Google+"/></a></li>
                        </ul>
                    </nav>

                    <?php  $pods = pods('theme_settings'); ?>
                    <a class="contactUs" href="<?php  echo $pods->field('contact_us.guid'); ?>" title="Contact Us">Contact Us</a>
    <div class="clear"></div>
                </div></article>
<?php endif; ?>
</section>
<?php  endwhile; ?>
<?php  else : ?>
<h2>No Text Found</h2>
<?php  endif; ?>
<?php  get_footer(); ?>
