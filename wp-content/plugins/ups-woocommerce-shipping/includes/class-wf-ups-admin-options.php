<?php
if( !class_exists('WF_UPS_Admin_Options') ){
    class WF_UPS_Admin_Options{
        function __construct(){
			$this->init();
        }

        function init(){
            //add a custome field in product page
            add_action( 'woocommerce_product_options_shipping', array($this,'wf_add_deliveryconfirmation_field')  );

            //Saving the values
            add_action( 'woocommerce_process_product_meta', array( $this, 'wf_save_deliveryconfirmation_field' ) );
        }

        function wf_add_deliveryconfirmation_field() {
			 
            // Print a custom select field
            woocommerce_wp_select( array(
                'id' => '_wf_ups_deliveryconfirmation',
                'label' => __('Delivery Confirmation'),
				'options'         => array(
				    0      => __( 'Confirmation Not Required', 'ups-woocommerce-admin-options' ),
					1      => __( 'Confirmation Required', 'ups-woocommerce-admin-options' ),
				    2      => __( 'Confirmation With Signature', 'ups-woocommerce-admin-options' ),
					3      => __( 'Confirmation With Adult Signature', 'ups-woocommerce-admin-options' )
				),
                'desc_tip' => false,
            ) );
			 
        }
		

        function wf_save_deliveryconfirmation_field( $post_id ) {
            if ( isset( $_POST['_wf_ups_deliveryconfirmation'] ) ) {
                update_post_meta( $post_id, '_wf_ups_deliveryconfirmation', esc_attr( $_POST['_wf_ups_deliveryconfirmation'] ) );
            }
        }
    }
    new WF_UPS_Admin_Options();
}
