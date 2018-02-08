<?php
/*
Template Name: Landing Page Template
*/
?>


<?php get_header(); ?>
<?php if ( have_posts() ) : ?>
<?php while ( have_posts() ) : the_post(); ?>

<body class="home">

     <?php $id = get_the_ID(); ?>



<?php $videoorss = get_post_meta($id, 'video_or_slideshow', true ); ?>
                    <?php if ($videoorss =="video") : ?>
                         <header id="homePage" class="centered border" data-video="">
                     <?php elseif($videoorss =="slideshow") : ?>
                          <header id="homePage" class="centered border" data-background="<?php if( have_rows('bg') ): while ( have_rows('bg') ) : the_row(); ?><?php the_sub_field('bg_image'); ?>,<?php endwhile; ?>">
        <?php else: ?>
    "><p>PLEASE SET A FEATURED IMAGE</p>
                        <?php endif; ?>
                    <?php endif; ?>



<?php $showevents = get_post_meta( $id, 'show_events', true ); ?>
        <?php  if ($showevents =="1") : ?>
                              <div class="rotated">
                              <div class="nothing-fancy"><p>Upcoming Events (click for details)</p><ul>
                              <?php
              $events = pods('events');
              $events->find('event_date ASC');
              ?>
           <?php while ( $events->fetch() ) :
               $events_link = $events->field('link');
               $events_location = $events->field('location');
               $slides_title = $events->field('title');
               $postid = $events->field('unique');
                $podsid = '#id-' . $postid;
                                  ?>

    <li><a href="<?php echo get_page_link(361); ?><?php echo $podsid ?>"><?php echo $slides_title ?></a></li>
                                  <?php endwhile ?>
                                  </ul></div></div>
                              <?php endif; ?>



    <div class="left">
        <div class="top">
            <?php $pods = pods('theme_settings'); ?>
            <a href="<?php echo get_option('home'); ?>"><img class="animated" id="logo" src="<?php echo $pods->field('logo.guid'); ?>" alt="Genesis Learning Logo" data-ko="<?php echo $pods->field('logo_ko.guid'); ?>"/></a>
            <input id="menuToggle" type="button">
            <div id="afterLogo" class="clear"></div>
           </div>
        <div class="middle">
            <img id="headerImage" class="full" src="<?php echo the_field('mobile_header_image') ?>" alt="homepage image"/>
<h1><?php the_field('top_header'); ?></h1>
<?php the_field('top_text'); ?>
            <a class="contactUs" href="<?php echo $pods->field('contact_us.guid'); ?>" title="Contact Us">Contact Us</a>
            <?php $pods = pods('social'); ?>
            <nav class="socialMenu">
                <ul>

                    <li><a class="twit" href="<?php echo $pods->field('twitter'); ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/twit-icon-sm.png" width="21" height="21" alt="Find us on Twitter"/></a></li>
                    <li><a class="lin" href="<?php echo $pods->field('linkedin'); ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/lin-icon-sm.png" width="21" height="21" alt="Find us on LinkedIn"/></a></li>
                    <li><a class="g" href="<?php echo $pods->field('g'); ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/g-icon-sm.png" width="21" height="21" alt="Find us on Google+"/></a></li>
                </ul>
            </nav>
            <h5 class="center"><a href="tel:<?php  echo $pods->field('phone'); ?>"><?php  echo $pods->field('phone'); ?></a><br>
                    <a href="mailto:<?php  echo $pods->field('email'); ?>"><?php  echo $pods->field('email'); ?></a></h5>
        </div>
    </div>
    <div class="clear"></div>
    <div class="bottom">

    </div>
</header>
<div class="sticky full">

    <nav id="mainNav" class="centered border">
                <div id="logoHolder">
                    <?php $pods = pods('theme_settings'); ?>
                    <a class="animated" href="<?php echo get_option('home'); ?>"><img class="animated" src="<?php echo $pods->field('logo_ko.guid'); ?>" alt="Genlrn.com Logo"/></a>
                </div>
        <nav id="shop"><?php wp_nav_menu( array( 'theme_location' => 'shop', 'container' => false ) ); ?></nav>
        <?php wp_nav_menu( array( 'theme_location' => 'navigation', 'container' => false ) ); ?>

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

<section id="main" class="centered border" >
<article class="blue">
     <?php if (has_post_thumbnail( $post->ID ) ): ?>
        <?php $photo = wp_get_attachment_url( get_post_thumbnail_id($post->ID, 'thumbnail') ); ?>
            <div id="featured-image"><img src="<?php echo $photo ?>" alt="Featured Image"/></div>
        <?php endif; ?>
   

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
    <h2>Services</h2>
    <div class="col">
        <a href="<?php the_field('link-1'); ?>"><img class="icon" src="<?php the_field('icon-1'); ?>" alt="<?php the_field('name-1'); ?>"/>
        <h3 class="center"><?php the_field('name-1'); ?></h3>
        <div class="description">
            <?php the_field('desc-1'); ?>
        </div></a>
    </div>
    <div class="col">
        <a href="<?php the_field('link-2'); ?>"><img class="icon" src="<?php the_field('icon-2'); ?>" alt="<?php the_field('name-2'); ?>"/>
        <h3 class="center"><?php the_field('name-2'); ?></h3>
        <div class="description">
            <?php the_field('desc-2'); ?>
        </div></a>
    </div>
    <div class="col">
        <a href="<?php the_field('link-3'); ?>"><img class="icon" src="<?php the_field('icon-3'); ?>" alt="<?php the_field('name-3'); ?>"/>
        <h3 class="center"><?php the_field('name-3'); ?></h3>
        <div class="description">
            <?php the_field('desc-3'); ?>
        </div></a>
    </div>
    </article>
    <article class="cols">
    <div class="col">
        <a href="<?php the_field('link-4'); ?>"><img class="icon" src="<?php the_field('icon-4'); ?>" alt="<?php the_field('name-4'); ?>"/>
        <h3 class="center"><?php the_field('name-4'); ?></h3>
        <div class="description">
            <?php the_field('desc-4'); ?>
        </div></a>
    </div>
    <div class="col">
        <a href="<?php the_field('link-5'); ?>"><img class="icon" src="<?php the_field('icon-5'); ?>" alt="<?php the_field('name-5'); ?>"/>
        <h3 class="center"><?php the_field('name-5'); ?></h3>
        <div class="description">
            <?php the_field('desc-5'); ?>
        </div></a>
    </div>
    <div class="col">
        <a href="<?php the_field('link-6'); ?>"><img class="icon" src="<?php the_field('icon-6'); ?>" alt="<?php the_field('name-6'); ?>"/>
        <h3 class="center"><?php the_field('name-6'); ?></h3>
        <div class="description">
            <p class="center"><?php the_field('desc-6'); ?></p>
        </div></a>
    </div>
<div class="clear"></div>
</article>


     <?php $showevents = get_post_meta( $id, 'show_events', true ); ?>
        <?php  if ($showevents =="1") : ?>
        <article id="events">
        <h2>Events</h2>
         <?php
              $events = pods('events');
              $events->find('event_date ASC');
              ?>
           <?php while ( $events->fetch() ) :
               $events_icon = $events->field('logo.guid');
               $events_link = $events->field('link');
               $events_location = $events->field('location');
               $events_description = $events->field('description');
               $slides_title = $events->field('title');
             $postid = $events->field('unique');
                $podsid = '#id-' . $postid;
            ?>

                <div class="event">

                        <div class="eventIcon"><img src="<?php echo $events_icon ?>" alt="<?php echo $slides_title?>"></div>
                        <div class="eventText">
                            <a href="<?php echo get_page_link(361); ?><?php echo $podsid ?>"><strong><?php echo $slides_title ?> </strong></a>
                            <p><?php echo $events_location ?></p>
                        </div>

                    <div class="clear"></div>
                </div>

           <?php endwhile; ?>


    </article>
    <?php endif; ?>
</section>

<?php endwhile; ?>
<?php else : ?>
<h2>No Text Found</h2>
<?php endif; ?>

<?php get_footer(); ?>
