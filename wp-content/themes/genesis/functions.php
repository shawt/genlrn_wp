<?php
    if (function_exists('register_sidebar')) {
        register_sidebar(array(
            'name' => 'Sidebar Widgets',
            'id'   => 'sidebar-widgets',
            'description'   => 'Widgets for the sidebar.',
            'before_widget' => '<div class="widget">',
            'after_widget'  => '</div>',
            'before_title'  => '<h2>',
            'after_title'   => '</h2>'
        ));
         register_sidebar(array(
            'name' => 'Store Before',
            'id'   => 'store-before',
            'description'   => 'Widgets for the sidebar.',
            'before_widget' => '<div class="widget">',
            'after_widget'  => '</div>',
            'before_title'  => '<h2>',
            'after_title'   => '</h2>'
        ));
         register_sidebar(array(
            'name' => 'Store After',
            'id'   => 'store-after',
            'description'   => 'Widgets for the sidebar.',
            'before_widget' => '<div class="widget">',
            'after_widget'  => '</div>',
            'before_title'  => '<h2>',
            'after_title'   => '</h2>'
        ));
         register_sidebar(array(
            'name' => 'Store Main',
            'id'   => 'store-main',
            'description'   => 'Widgets for the sidebar.',
            'before_widget' => '<div class="widget">',
            'after_widget'  => '</div>',
            'before_title'  => '<h2>',
            'after_title'   => '</h2>'
        ));
    }


    function register_my_menus() {
      register_nav_menus(
        array(
          'navigation' => __( 'Navigation' ),
          'shop' => __( 'Shop' )
        )
      );
    }
    add_action( 'init', 'register_my_menus' );
?>
<?php
function add_first_and_last($output) {
  $output = preg_replace('/class="menu-item/', 'class="first menu-item', $output, 1);
  $output = substr_replace($output, 'class="last menu-item', strripos($output, 'class="menu-item'), strlen('class="menu-item'));
  return $output;
}
add_filter('wp_nav_menu', 'add_first_and_last');
add_theme_support( 'post-thumbnails', array( 'post', 'page' ) );

add_filter( 'the_content_more_link', 'modify_read_more_link' );
function modify_read_more_link() {
return '&nbsp;<input id="readMoreButton" type="button" value="Read More"/>';
}
add_editor_style('css/editor-style.css');


function _remove_script_version( $src ){
    $src = preg_replace('/^(http?|https):/', '', $src);

    if ( !strpos( $src, get_template_directory_uri() ) && !strpos( $src, 'fonts.googleapis.com' ) ){
        $parts = explode( '?', $src );
        return $parts[0];
    }
    return $src;
}
add_filter( 'script_loader_src', '_remove_script_version', 15, 1 );
add_filter( 'style_loader_src', '_remove_script_version', 15, 1 );

add_action( 'after_setup_theme', 'woocommerce_support' );
function woocommerce_support() {
    add_theme_support( 'woocommerce' );
}

/*
|---------------------------------------------
| Force https
|---------------------------------------------
*/
function force_https(){
    if( $_SERVER['SERVER_PORT'] != 443){
        $url = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        header("HTTP/1.1 301");
        header("Status: 301");
        header('Location: ' . $url );
    }
}
add_action( 'send_headers', 'force_https' );
add_theme_support( 'post-thumbnails' ); 
?>
