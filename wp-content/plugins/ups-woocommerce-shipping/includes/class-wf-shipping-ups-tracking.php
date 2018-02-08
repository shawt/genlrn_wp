<?php

class WF_Shipping_UPS_Tracking
{
	const TRACKING_MESSAGE_KEY 		= "wfupstrackingmsg";
	const TRACK_SHIPMENT_KEY		= "wf_ups_track_shipment";
	const SHIPMENT_IDS_KEY			= "ups_shipment_ids";
	const META_BOX_TITLE		 	= "UPS Shipment Tracking";
	const SHIPPING_METHOD_ID		= WF_UPS_ID;
	const SHIPPING_METHOD_DISPLAY	= "UPS";
	const TRACKING_URL				= "http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=";
	const TEXT_DOMAIN				= "ups-woocommerce-shipping";

	public function __construct(){
		//Print Shipping Label.
		if ( is_admin() ) { 
			add_action( 'add_meta_boxes', array( $this, 'wf_add_admin_metabox' ), 15 );
			add_action( 'admin_notices', array( $this, 'wf_admin_notice' ), 15 );
			
			// Shipment Tracking.
			add_action( 'woocommerce_process_shop_order_meta', array($this, 'wf_process_order_meta_fields_save'), 15 );
		}

		// Shipment Tracking - Customer Order Details Page.
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'wf_display_customer_track_shipment' ) );
		add_action( 'woocommerce_email_order_meta', array( $this, 'wf_add_ups_tracking_info_to_email'), 20 );

		// Shipment Tracking - Admin end.
		if ( isset( $_GET[self::TRACK_SHIPMENT_KEY] ) ) {
			add_action( 'init', array( $this, 'wf_display_admin_track_shipment' ), 15 );
		}
	}

	function wf_load_order( $orderId ){
		if ( !class_exists( 'WC_Order' ) ) {
			return false;
		}
		return new WC_Order( $orderId );      
	}

	function wf_user_check() {
		if ( is_admin() ) {
			return true;
		}
		return false;
	}
	
	function wf_admin_notice(){
		global $pagenow;
		global $post;
		
		if( !isset( $_GET[ self::TRACKING_MESSAGE_KEY ] ) && empty( $_GET[ self::TRACKING_MESSAGE_KEY ] ) ) {
			return;
		}
	
		$wftrackingmsg = $_GET[ self::TRACKING_MESSAGE_KEY ];

		switch ( $wftrackingmsg ) {
			case "0":
				echo '<div class="error"><p>'.self::SHIPPING_METHOD_DISPLAY.': Sorry, Unable to proceed.</p></div>';
				break;
			case "4":
				echo '<div class="error"><p>'.self::SHIPPING_METHOD_DISPLAY.': Unable to track the shipment. Please cross check shipment id or try after some time.</p></div>';
				break;
			case "5":
				$wftrackingmsg = get_post_meta( $post->ID, self::TRACKING_MESSAGE_KEY, true);
				echo '<div class="updated"><p>'.$wftrackingmsg.'</p></div>';
				break;
			case "6":
				echo '<div class="updated"><p>'.self::SHIPPING_METHOD_DISPLAY.': Shipment tracking IDs are empty.</p></div>';
				break;
			default:
				break;
		}
	}

	function wf_add_ups_tracking_info_to_email( $order, $sent_to_admin = false, $plain_text = false ) {
		$shipment_id_cs 	= get_post_meta( $order->id , self::SHIPMENT_IDS_KEY, true );

		if( $shipment_id_cs != '' ) {
			$shipment_ids = explode(",", $shipment_id_cs);
		
			if( empty( $shipment_ids ) ) {
				return;
			}
			
			echo '<h3>'.__( 'Shipping Detail', 'ups-woocommerce-shipping' ).'</h3>';
			$order_notice 	= __( 'Your order is shipped via UPS. To track shipment, please follow the shipment ID(s) ', 'ups-woocommerce-shipping' );
			foreach ( $shipment_ids as $shipment_id ) {
				$order_notice 	.= '<a href="'.self::TRACKING_URL.$shipment_id.'" target="_blank">'.$shipment_id.'</a>'.' | ';
			}

			echo '<p>'.$order_notice.'</p></br>';
		}
	}
	
	function wf_display_customer_track_shipment ( $order ) {
		$order_id 		= $order->id;
		$shipment_id_cs	= get_post_meta( $order_id, self::SHIPMENT_IDS_KEY, true );
		
		if( ! $this->tracking_eligibility( $order, true ) ) {
			return;
		}
		
		if( $shipment_id_cs == '' ) {
			return;
		}
		
		$shipment_ids = explode(",", $shipment_id_cs);
		
		if( empty( $shipment_ids ) ) {
			return;
		}

		$shipment_info = $this->get_shipment_info( $order_id, $shipment_id_cs );
		
		if( empty ( $shipment_info ) || false == $shipment_info ) {
			return;
		}

		echo '<h3>Shipment Tracking</h3>';
		echo '<table class="shop_table wooforce_tracking_details">
			<thead>
				<tr>
					<th class="product-name">Shipment ID<br/>(Follow link for detailed status.)</th>
					<th class="product-total">Status</th>
				</tr>
			</thead>
			<tfoot>';

			foreach ( $shipment_info as $shipment_id => $message ) {
				echo '<tr>';
				echo '<th scope="row">'.'<a href="'.self::TRACKING_URL.$shipment_id.'" target="_blank">'.$shipment_id.'</a></th>';
				echo '<td><span>'.$message.'</span></td>';
				echo '</tr>';
			}
			echo '</tfoot>
		</table>';
	}

	function wf_display_admin_track_shipment() {
		if( !$this->wf_user_check() ) {
			echo "You don't have admin privileges to view this page.";
			exit;
		}
		
		$post_id 		= isset( $_GET['post'] ) ? $_GET['post'] : '';
		$shipment_id_cs	= isset( $_GET[ self::TRACK_SHIPMENT_KEY ] ) ? $_GET[ self::TRACK_SHIPMENT_KEY ] : '';
		
		$admin_notice = '';
		$shipment_info = $this->get_shipment_info( $post_id, $shipment_id_cs );
		
		foreach ( $shipment_info as $shipment_id => $message ) {
			$admin_notice .= '<strong>'.$shipment_id.': </strong>'.$message.'</br>';
		}

		$wftrackingmsg = 5;
		update_post_meta( $post_id, self::TRACKING_MESSAGE_KEY, $admin_notice );
		wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit&'.self::TRACKING_MESSAGE_KEY.'='.$wftrackingmsg ) );
		exit;	
	}
	
	function get_shipment_info( $post_id, $shipment_id_cs ) {
		return $this->wf_ups_track_shipment( $post_id, $shipment_id_cs );
	}

	function wf_process_order_meta_fields_save( $post_id ){
		global $wpdb, $woocommerce;
		
		if(isset( $_POST[self::SHIPMENT_IDS_KEY] )) {
			$shipment_ids = $_POST[self::SHIPMENT_IDS_KEY];
			update_post_meta( $post_id, self::SHIPMENT_IDS_KEY, $shipment_ids );
		}
	}

	function wf_add_admin_metabox(){
		global $post;
		if ( !$post ) return false;
		$order 	= $this->wf_load_order( $post->ID );
		
		if( ! $this->tracking_eligibility( $order ) ) {
			return;
		}
		
		// Shipment Tracking meta box for UPS.
		add_meta_box( 'WFUPSTracking_metabox', __( self::META_BOX_TITLE , self::TEXT_DOMAIN ), array( $this, 'wf_ups_admin_tracking_metabox' ), 'shop_order', 'side', 'default' );
	}
	
	function wf_ups_admin_tracking_metabox(){
		global $post;

		$shipmentId 	= '';
		$shipment_ids 	= get_post_meta( $post->ID, self::SHIPMENT_IDS_KEY, true );
		?>
		
		<div class="add_label_id">
			<strong>Enter Tracking IDs (Comma Separated)</strong>
			<textarea rows="1" cols="25" class="input-text" id="<?php echo self::SHIPMENT_IDS_KEY; ?>" name="<?php echo self::SHIPMENT_IDS_KEY; ?>" type="text"><?php echo $shipment_ids; ?></textarea>
		</div>
		<?php
			$tracking_url = admin_url( '/?post='.( $post->ID ) );
		?>
			<a class="button button-primary ups_shipment_tracking tips" href="<?php echo $tracking_url; ?>" data-tip="<?php _e('Save/Show Tracking Info', self::TEXT_DOMAIN); ?>"><?php _e('Save/Show Tracking Info', self::TEXT_DOMAIN); ?></a><hr style="border-color:#0074a2">
			
			<script type="text/javascript">
				jQuery("a.ups_shipment_tracking").on("click", function() {
				   location.href = this.href + '&wf_ups_track_shipment=' + jQuery('#ups_shipment_ids').val().replace(/ /g,'');
				   return false;
				});
			</script> 
		<?php
	}
	
	function wf_ups_track_shipment( $post_id, $shipment_id_cs ) {
		if( empty( $post_id ) ) {
			$wftrackingmsg = 0;
			wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit&'.self::TRACKING_MESSAGE_KEY.'='.$wftrackingmsg ) );
			exit;
		}

		if( empty( $shipment_id_cs ) ) {
			update_post_meta( $post_id, self::SHIPMENT_IDS_KEY, $shipment_id_cs );
			$wftrackingmsg = 6;
			wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit&'.self::TRACKING_MESSAGE_KEY.'='.$wftrackingmsg ) );
			exit;
		}
		
		$shipment_ids 		= preg_split( '@,@', $shipment_id_cs, NULL, PREG_SPLIT_NO_EMPTY );
		$shipment_id_cs 	= implode( ',',$shipment_ids );
		
		update_post_meta( $post_id, self::SHIPMENT_IDS_KEY, $shipment_id_cs );
		
		$shipment_ids 		= explode( ",", $shipment_id_cs );
		$responses 			= $this->wf_ups_trackv2_response( $shipment_ids, $post_id );
		
		if( empty( $responses ) ) {
			$wftrackingmsg = 4;
			wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit&'.self::TRACKING_MESSAGE_KEY.'='.$wftrackingmsg ) );
			exit;
		}

		$shipment_info		= array();
		
		foreach ( $responses as $shipment_id => $response ) {
			$response_obj		= simplexml_load_string( $response['body'] );
			$response_code 		= (string)$response_obj->Response->ResponseStatusCode;
			
			if('0' == $response_code) {
				$error_code 	= (string)$response_obj->Response->Error->ErrorCode;
				$error_desc 	= (string)$response_obj->Response->Error->ErrorDescription;
				$message		= $error_desc.' [Error Code: '.$error_code.']';
				$shipment_info[ $shipment_id] = $message;
			}
			else {
				$trackinfo			= $response_obj->Shipment;
			
				if( isset( $trackinfo->Error ) ) {
					$message 		= (string)$trackinfo->Error->Description.' ['.$trackinfo->Error->Number.']>';
					$shipment_info[ (string)$trackinfo->Package->TrackingNumber ] = $message;
				}
				else {
					if( isset( $trackinfo->Package->Activity[0] ) && $trackinfo->Package->Activity[0]->Status->StatusType->Description != '' ) {
						$message 	= (string)$trackinfo->Package->Activity[0]->Status->StatusType->Description.'';
						$shipment_info[ (string)$trackinfo->Package->TrackingNumber ] = $message;
					}
					else if( $trackinfo->CurrentStatus->Description != '' ) {
						$message 	= (string)$trackinfo->CurrentStatus->Description.'';
						$shipment_info[ (string)$trackinfo->InquiryNumber->Value ] = $message;
					}
					else {
						$message 	= 'Unable to track this number.';
						$shipment_info[ $shipment_id] = $message;
					}
				}
			}
		}
		
		return $shipment_info;
	}
	
	function wf_ups_trackv2_response( $shipment_ids, $order_id ) {
		// Load Shipping Method Settings.
		$settings		= get_option( 'woocommerce_'.self::SHIPPING_METHOD_ID.'_settings', null ); 
		$user_id		= !empty( $settings['user_id'] ) ? $settings['user_id'] : '';
		$password       = isset( $settings['password'] ) ? $settings['password'] : '';
		$access_key     = isset( $settings['access_key'] ) ? $settings['access_key'] : '';
		$shipper_number = isset( $settings['shipper_number'] ) ? $settings['shipper_number'] : '';
		$api_mode		= isset( $settings['api_mode'] ) ? $settings['api_mode'] : 'Test';
		
		$endpoint	= '';
		if( "Live" == $api_mode ) {
			$endpoint = 'https://www.ups.com/ups.app/xml/Track';
		}
		else {
			$endpoint = 'https://wwwcie.ups.com/ups.app/xml/Track';
		}
		
		$responses = array();
		foreach ( $shipment_ids as $shipment_id ) {
			$request 	= $this->wf_ups_trackv2_request( $shipment_id, $user_id, $password, $access_key, $order_id );
			$response	= wp_remote_post( $endpoint,
				array(
					'timeout'   => 70,
					'sslverify' => 0,
					'body'      => $request
				)
			);
			
			$responses[ $shipment_id ] 	= $response;
		}

		return $responses;
	}
	
	function wf_ups_trackv2_request( $shipment_id, $user_id, $password, $access_key, $order_id ) {
		$xml_request 	 = '<?xml version="1.0" ?>';
		$xml_request 	.= '<AccessRequest xml:lang="en-US">'; 
		$xml_request 	.= '<AccessLicenseNumber>'.$access_key.'</AccessLicenseNumber>';
		$xml_request 	.= '<UserId>'.$user_id.'</UserId>';
		$xml_request 	.= '<Password>'.$password.'</Password>';
		$xml_request 	.= '</AccessRequest>';
		$xml_request 	.= '<?xml version="1.0" ?>';
		$xml_request 	.= '<TrackRequest>';
		$xml_request 	.= '<Request>';
		$xml_request 	.= '<TransactionReference>';
		$xml_request 	.= '<CustomerContext>'.$order_id.'</CustomerContext>';
		$xml_request 	.= '</TransactionReference>';
		$xml_request 	.= '<RequestAction>Track</RequestAction>';
		$xml_request 	.= '</Request>'; 
		$xml_request 	.= '<TrackingNumber>'.$shipment_id.'</TrackingNumber>';
		$xml_request 	.= '</TrackRequest>';

		$request 		= str_replace( array( "\n", "\r" ), '', $xml_request );
		
		return $request;
	}
	
	function tracking_eligibility( $order, $for_consumer = false ) {
		return $this->check_ups_tracking_eligibility( $order, $for_consumer );
	}
	
	function check_ups_tracking_eligibility ( $order, $for_consumer ) {
		$eligibility = false;
	
		if ( !$order ) return false; 
		
		$ups_settings 				= get_option( 'woocommerce_'.self::SHIPPING_METHOD_ID.'_settings', null ); 
		$disble_shipment_tracking	= isset( $ups_settings['disble_shipment_tracking'] ) ? $ups_settings['disble_shipment_tracking'] : 'TrueForCustomer';
		
		if( $disble_shipment_tracking != 'True' ) {
			if( true == $for_consumer && 'TrueForCustomer' == $disble_shipment_tracking ) {
				$eligibility = false;
			}
			else {
				$eligibility = true;
			}
		}

		return $eligibility;
	}
	
	function wf_get_ups_shipping_service_data($order){
		//TODO: Take the first shipping method. The use case of multiple shipping method for single order is not handled.
		
		$shipping_methods = $order->get_shipping_methods();
		if ( ! $shipping_methods ) {
			return false;
		}

		$shipping_method = array_shift( $shipping_methods );
		$shipping_service_tmp_data = explode( ':',$shipping_method['method_id'] );
		
		if( (count($shipping_service_tmp_data) < 2) ){
			return false;
		}
		
		$shipping_service_data['shipping_method'] 		= $shipping_service_tmp_data[0];
		$shipping_service_data['shipping_service'] 		= $shipping_service_tmp_data[1];
		$shipping_service_data['shipping_service_name']	= $shipping_method['name'];
		
		return $shipping_service_data;
	}
}

new WF_Shipping_UPS_Tracking();

?>
