<?php
/*
	Plugin Name: UPS WooCommerce Shipping
	Plugin URI: http://www.wooforce.com/shop
	Description: Obtain Real time shipping rates, Print shipping labels and Track Shipment via the UPS Shipping API.
	Version: 3.1.4
	Author: WooForce
	Author URI: http://www.wooforce.com
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
function wf_ups_pre_activation_check(){
	//check if basic version is there
	if ( is_plugin_active('ups-woocommerce-shipping-method/ups-woocommerce-shipping.php') ){
        deactivate_plugins( basename( __FILE__ ) );
		wp_die( __("Oops! You tried installing the premium version without deactivating and deleting the basic version. Kindly deactivate and delete UPS(Basic) Woocommerce Extension and then try again", "ups-woocommerce-shipping" ), "", array('back_link' => 1 ));
	}
}
register_activation_hook( __FILE__, 'wf_ups_pre_activation_check' );

// Required functions
if ( ! function_exists( 'wf_is_woocommerce_active' ) ) {
	require_once( 'wf-includes/wf-functions.php' );
}
// WC active check
if ( ! wf_is_woocommerce_active() ) {
	return;
}

define("WF_UPS_ID", "wf_shipping_ups");
define("WF_UPS_ADV_DEBUG_MODE", "off"); // Turn 'on' for demo/test sites.

/**
 * WC_UPS class
 */
class UPS_WooCommerce_Shipping {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'wf_plugin_action_links' ) );
		add_action( 'woocommerce_shipping_init', array( $this, 'wf_shipping_init') );
		add_filter( 'woocommerce_shipping_methods', array( $this, 'wf_ups_add_method') );
		add_action( 'admin_enqueue_scripts', array( $this, 'wf_ups_scripts') );
	}

	public function init() {
		// Add Notice Class
		include_once ( 'includes/class-wf-admin-notice.php' );
		// WF Print Shipping Label.
		include_once ( 'includes/class-wf-shipping-ups-admin.php' );
		
		include_once ( 'includes/class-wf-ups-accesspoint-locator.php' );
		
		
		if ( is_admin() ) {
			//include api manager
			include_once ( 'includes/wf_api_manager/wf-api-manager-config.php' );
			
			//include pickup functionality
			include_once ( 'includes/class-wf-ups-pickup-admin.php' );
			
			include_once ( 'includes/class-wf-ups-admin-options.php' );
		}
		// Localisation
		load_plugin_textdomain( 'ups-woocommerce-shipping', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/' );
	}

	/**
	 * Plugin page links
	 */
	public function wf_plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=wf_shipping_ups' ) . '">' . __( 'Settings', 'ups-woocommerce-shipping' ) . '</a>',
			'<a href="http://support.wooforce.com">' . __( 'Support', 'ups-woocommerce-shipping' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}
	
	/**
	 * wc_ups_init function.
	 *
	 * @access public
	 * @return void
	 */
	function wf_shipping_init() {
		include_once( 'includes/class-wf-shipping-ups.php' );
	}

	/**
	 * wc_ups_add_method function.
	 *
	 * @access public
	 * @param mixed $methods
	 * @return void
	 */
	function wf_ups_add_method( $methods ) {
		$methods[] = 'WF_Shipping_UPS';
		return $methods;
	}

	/**
	 * wc_ups_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	function wf_ups_scripts() {
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'wf-common-script', plugins_url( '/resources/js/wf_common.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'wf-ups-script', plugins_url( '/resources/js/wf_ups.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_style( 'wf-common-style', plugins_url( '/resources/css/wf_common_style.css', __FILE__ ));
	}
}
new UPS_WooCommerce_Shipping();

/* Add a new country to countries list */
if(!function_exists('wf_add_puert_rico_country')){
	function wf_add_puert_rico_country( $country ) {
	  $country["PR"] = 'Puert Rico';  
		return $country; 
	}
	add_filter( 'woocommerce_countries', 'wf_add_puert_rico_country', 10, 1 );
}
