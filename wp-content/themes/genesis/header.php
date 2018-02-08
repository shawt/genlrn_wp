<!DOCTYPE HTML>
<html <?php language_attributes(); ?>>
<head>
<!--Barts HTML5 Kit v4 | MIT | http://www.rockemgraphics.com-->

<!--Fonts-->
<link href='http://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700,300italic,400italic,500italic,700italic' rel='stylesheet' type='text/css'>
<link href='http://fonts.googleapis.com/css?family=Coming+Soon' rel='stylesheet' type='text/css'>
<meta name="google-site-verification" content="hO-H1fojmzwPQgUX7thsAM9C4Uo5-2nSaaJ53_XWKBA" />
<!--META-->
<meta charset="<?php bloginfo('charset');?>" />
<meta name="robots" content="index,follow" />

<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<title><?php bloginfo('name'); ?></title>

<!--ICON-->
<?php $pods = pods('theme_settings'); ?>
<link rel="shortcut icon" type="image/x-icon" href="<?php echo $pods->get_field('favicon.guid'); ?>" />

<!--CSS-->
<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/css/normalize.css">
<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/css/animate.min.css">
<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>"/>
<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/woocommerce/style.css">

<!--JS is enqueued with plugin-->

<!--HACKS-->
<!--[if lt IE 9]>
<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/css/ie.css" />
<script type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/js/html5shiv.js"></script>
<![endif]-->

<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>"/>
<?php if ( is_singular() && get_option( 'thread_comments' ) ) wp_enqueue_script('comment-reply'); ?>

<?php wp_head(); ?>

<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-61514668-1', 'auto');
  ga('send', 'pageview');

</script>
</head>
