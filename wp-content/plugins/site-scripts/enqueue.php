<?php
/*
Plugin Name: Barts Scripts Loader
Description: Enqueue Scripts
Version: 1.0
Author: Bartosz Klemensowski
Author URI: http://www.btkgraphics.com/
License: CC
*/

function loadScripts() {

    wp_enqueue_script('jquery');

    wp_register_script( 'backstretch', get_template_directory_uri() . '/js/jquery.backstretch.min.js', array('jquery'),'',true  );
    wp_register_script( 'waypoints', get_template_directory_uri() . '/js/jquery.waypoints.min.js', array('jquery'),'',true  );
    wp_register_script( 'sticky', get_template_directory_uri() . '/js/sticky.min.js', array('jquery'),'',true  );
    wp_register_script( 'smartresize', get_template_directory_uri() . '/js/smartresize.js', array('jquery'),'',true  );
    wp_register_script( 'scrollto', get_template_directory_uri() . '/js/jquery.scrollTo-1.4.3.1-min.js', array('jquery'),'',true  );
    wp_register_script( 'marquee', get_template_directory_uri() . '/js/jquery.marquee.min.js', array('jquery'),'',true  );
    wp_register_script( 'conversion', get_template_directory_uri() . '/js/conversion.js', array('jquery'),'',true  );
    wp_register_script( 'docReady', get_template_directory_uri() . '/js/docReady-2.js', array('jquery'),'',true  );

     wp_enqueue_script( 'backstretch' );
    wp_enqueue_script( 'waypoints' );
    wp_enqueue_script( 'sticky' );
    wp_enqueue_script( 'smartresize' );
     wp_enqueue_script( 'marquee' );
    wp_enqueue_script( 'scrollto' );
    wp_enqueue_script( 'docReady' );
wp_enqueue_script( 'conversion' );


}

add_action( 'wp_enqueue_scripts', 'loadScripts' );
