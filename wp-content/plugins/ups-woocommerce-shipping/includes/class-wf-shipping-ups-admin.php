<?php
class WF_Shipping_UPS_Admin
{
	private $ups_services = array(
		// Domestic
		"12" => "3 Day Select",
		"03" => "Ground",
		"02" => "2nd Day Air",
		"59" => "2nd Day Air AM",
		"01" => "Next Day Air",
		"13" => "Next Day Air Saver",
		"14" => "Next Day Air Early AM",

		// International
		"11" => "Standard",
		"07" => "Worldwide Express",
		"54" => "Worldwide Express Plus",
		"08" => "Worldwide Expedited",
		"65" => "Worldwide Saver",
		
		// SurePost
		"92" =>	"SurePost Less than 1 lb",
		"93" =>	"SurePost 1 lb or Greater",
		"94" =>	"SurePost BPM",
		"95" =>	"SurePost Media",
		
		//New Services
		"M2" => "First Class Mail",
		"M3" => "Priority Mail",
		"M4" => "Expedited Mail Innovations ",
		"M5" => "Priority Mail Innovations ",
		"M6" => "EconomyMail Innovations ",
		"70" => "Access Point Economy ",
		"96" => "Worldwide Express Freight",
	);
	
	public function __construct(){
		$this->wf_init();

		//Print Shipping Label.
		if ( is_admin() ) {
			$this->init_bulk_printing();
			add_action( 'add_meta_boxes', array( $this, 'wf_add_ups_metabox' ), 15 );
			add_action('admin_notices',array(new wf_admin_notice, 'throw_notices'), 15); // New notice system
		}
		
		if ( isset( $_GET['wf_ups_shipment_confirm'] ) ) {
			add_action( 'init', array( $this, 'wf_ups_shipment_confirm' ), 15 );
		}
		else if ( isset( $_GET['wf_ups_shipment_accept'] ) ) {
			add_action( 'init', array( $this, 'wf_ups_shipment_accept' ), 15 );
		}
		else if ( isset( $_GET['wf_ups_print_label'] ) ) {
			add_action( 'init', array( $this, 'wf_ups_print_label' ), 15 );
		}
		else if( isset( $_GET['wf_ups_print_commercial_invoice'] ) ){
			add_action( 'init', array( $this, 'wf_ups_print_commercial_invoice' ), 15 );
		}
		else if ( isset( $_GET['wf_ups_void_shipment'] ) ) {
			add_action( 'init', array( $this, 'wf_ups_void_shipment' ), 15 );
		}
		else if ( isset( $_GET['wf_ups_generate_packages'] ) ) {
			add_action( 'init', array( $this, 'wf_ups_generate_packages' ), 15 );
		}
	}

	private function wf_init() {
		global $post;
		
		$shipmentconfirm_requests 			= array();
		// Load UPS Settings.
		$this->settings 					= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		//Print Label Settings.
		$this->disble_ups_print_label		= isset( $this->settings['disble_ups_print_label'] ) ? $this->settings['disble_ups_print_label'] : '';
		$this->packing_method  				= isset( $this->settings['packing_method'] ) ? $this->settings['packing_method'] : 'per_item';
		$this->disble_shipment_tracking		= isset( $this->settings['disble_shipment_tracking'] ) ? $this->settings['disble_shipment_tracking'] : 'TrueForCustomer';
		$this->show_label_in_browser	    = isset( $this->settings['show_label_in_browser'] ) ? $this->settings['show_label_in_browser'] : 'no';
		$this->box_max_weight			=	isset($this->settings[ 'box_max_weight']) ?  $this->settings[ 'box_max_weight'] : '';
		$this->weight_packing_process	=	isset($this->settings[ 'weight_packing_process']) ? $this->settings[ 'weight_packing_process'] : '';
		
		// Units
		$this->units			= isset( $this->settings['units'] ) ? $this->settings['units'] : 'imperial';
		
		//Advanced Settings
		$this->ssl_verify			= isset( $this->settings['ssl_verify'] ) ? $this->settings['ssl_verify'] : false;

		if ( $this->units == 'metric' ) {
			$this->weight_unit = 'KGS';
			$this->dim_unit    = 'CM';
		} else {
			$this->weight_unit = 'LBS';
			$this->dim_unit    = 'IN';
		}
		if ( ! class_exists( 'WF_Shipping_UPS' ) ) {
	  		include_once 'class-wf-shipping-ups.php';
	  	}
		
		$this->countries_with_statecodes	=	array('US','CA');
		
		$this->wcsups	=	new WF_Shipping_UPS();
		include_once( 'class-wf-shipping-ups-tracking.php' );
		
		add_filter('wf_ups_filter_label_packages',array($this,'manual_packages'),10,2);		
	}

	function wf_add_ups_metabox(){
		global $post;
		
		if( $this->disble_ups_print_label == 'yes' ) {
			return;
		}

		if ( !$post ) return;

		$order = $this->wf_load_order( $post->ID );
		if ( !$order ) return; 
		
		add_meta_box( 'CyDUPS_metabox', __( 'UPS Shipment Label', 'ups-woocommerce-shipping' ), array( $this, 'wf_ups_metabox_content' ), 'shop_order', 'advanced', 'default' );
	}

	function wf_ups_metabox_content(){
		global $post;
		$shipmentId = '';
		
		$order 								= $this->wf_load_order( $post->ID );
		$shipping_service_data				= $this->wf_get_shipping_service_data( $order ); 
		$default_service_type 				= $shipping_service_data['shipping_service'];

		$created_shipments_details_array 	= get_post_meta( $post->ID, 'ups_created_shipments_details_array', true );
		if( empty( $created_shipments_details_array ) ) {		
			
			
			$download_url = admin_url( '/?wf_ups_shipment_confirm='.base64_encode( $shipmentId.'|'.$post->ID ) );
			$stored_packages	=	get_post_meta( $post->ID, '_wf_ups_stored_packages', true );
			if(empty($stored_packages)	&&	!is_array($stored_packages)){
				echo '<strong>'.__( 'Step 1: Auto generate packages.', 'ups-woocommerce-shipping' ).'</strong></br>';
			}else{
				echo '<strong>'.__( 'Step 2: Initiate your shipment.', 'ups-woocommerce-shipping' ).'</strong></br>';
							
				echo '<ul>';
				
				echo '<li><label for="ups_cod"><input type="checkbox" style="" id="ups_cod" name="ups_cod" class="">' . __('Collect On Delivery', 'ups-woocommerce-shipping') . '</label><img class="help_tip" style="float:none;" data-tip="'.__( 'Collect On Delivery would be applicable only for single package which may contain single or multiple product(s).', 'ups-woocommerce-shipping' ).'" src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" /></li>';
				
				echo '<li><label for="ups_return"><input type="checkbox" style="" id="ups_return" name="ups_return" class="">' . __('Include Return Label', 'ups-woocommerce-shipping') . '</label><img class="help_tip" style="float:none;" data-tip="'.__( 'You can generate the return label only for single package order.', 'ups-woocommerce-shipping' ).'" src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" /></li>';
				
				echo '<li><label for="ups_sat_delivery"><input type="checkbox" style="" id="ups_sat_delivery" name="ups_sat_delivery" class="">' . __('Saturday Delivery', 'ups-woocommerce-shipping') . '</label><img class="help_tip" style="float:none;" data-tip="'.__( 'Saturday Delivery from UPS allows you to stretch your business week to Saturday', 'ups-woocommerce-shipping' ).'" src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" /></li>';
				
				echo '<li>';
					echo '<h4>'.__( 'Package(s)' , 'ups-woocommerce-shipping').': </h4>';
					echo '<table id="wf_ups_package_list" class="wf-shipment-package-table">';					
						echo '<tr>';
							echo '<th>'.__('Wt.', 'ups-woocommerce-shipping').'</br>('.$this->weight_unit.')</th>';
							echo '<th>'.__('L', 'ups-woocommerce-shipping').'</br>('.$this->dim_unit.')</th>';
							echo '<th>'.__('W', 'ups-woocommerce-shipping').'</br>('.$this->dim_unit.')</th>';
							echo '<th>'.__('H', 'ups-woocommerce-shipping').'</br>('.$this->dim_unit.')</th>';
							echo '<th>'.__('Insur.', 'ups-woocommerce-shipping').'</th>';
							
							echo '<th>';
								echo __('Service', 'ups-woocommerce-shipping');
								echo '<img class="help_tip" style="float:none;" data-tip="'.__( 'Contact UPS for more info on this services.', 'ups-woocommerce-shipping' ).'" src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" />';
							echo '</th>';
							echo '<th>&nbsp;</th>';
						echo '</tr>';
						foreach($stored_packages as $stored_package_key	=>	$stored_package){
							$dimensions	=	$this->get_dimension_from_package($stored_package);
							if(is_array($dimensions)){
								?>
								<tr>
									<td><input type="text" id="ups_manual_weight" name="ups_manual_weight[]" size="2" value="<?php echo $dimensions['Weight'];?>" /></td>     
									<td><input type="text" id="ups_manual_length" name="ups_manual_length[]" size="2" value="<?php echo $dimensions['Length'];?>" /></td>
									<td><input type="text" id="ups_manual_width" name="ups_manual_width[]" size="2" value="<?php echo $dimensions['Width'];?>" /></td>
									<td><input type="text" id="ups_manual_height" name="ups_manual_height[]" size="2" value="<?php echo $dimensions['Height'];?>" /></td>
									<td><input type="text" id="ups_manual_insurance" name="ups_manual_insurance[]" size="2" value="<?php echo $dimensions['InsuredValue'];?>" /></td>
									<td>
										<select class="select ups_manual_service" id="ups_manual_service" name="ups_manual_service[]">
										<?php foreach($this->ups_services as $service_code => $service_name){
											echo '<option value="'.$service_code.'" ' . selected($default_service_type, $service_code) . ' >'.$service_name.'</option>';
										}?>
										</select>
									</td>
									<td>&nbsp;</td>
								</tr>
								<?php
							}
						}
					echo '</table>';
					echo '<a class="wf-action-button wf-add-button" style="font-size: 12px;" id="wf_ups_add_package">Add Package</a>';
				
				echo '</li>';
				?>
				<a class="button button-primary tips ups_create_shipment" href="<?php echo $download_url; ?>" data-tip="<?php _e( 'Confirm Shipment', 'ups-woocommerce-shipping' ); ?>"><?php _e( 'Confirm Shipment', 'ups-woocommerce-shipping' ); ?></a></br></br>
				<script type="text/javascript">
					jQuery(document).ready(function(){
						jQuery('#wf_ups_add_package').on("click", function(){
							var new_row = '<tr>';
								new_row 	+= '<td><input type="text" id="ups_manual_weight" name="ups_manual_weight[]" size="2" value="0"></td>';
								new_row 	+= '<td><input type="text" id="ups_manual_length" name="ups_manual_length[]" size="2" value="0"></td>';								
								new_row 	+= '<td><input type="text" id="ups_manual_width" name="ups_manual_width[]" size="2" value="0"></td>';
								new_row 	+= '<td><input type="text" id="ups_manual_height" name="ups_manual_height[]" size="2" value="0"></td>';
								new_row 	+= '<td><input type="text" id="ups_manual_insurance" name="ups_manual_insurance[]" size="2" value="0"></td>';
								new_row 	+= '<td>';
									new_row 	+= '<select class="select ups_manual_service" id="ups_manual_service">';
									<?php foreach($this->ups_services as $service_code => $service_name){?>
										new_row 	+= '<option value="<?php echo $service_code;?>"><?php echo $service_name;?></option>';
									<?php }?>
									new_row 	+= '</select>';
								new_row 	+= '</td>';
								new_row 	+= '<td><a class="wf_ups_package_line_remove">&#x26D4;</a></td>';
							new_row 	+= '</tr>';
							
							jQuery('#wf_ups_package_list tr:last').after(new_row);
						});
						
						jQuery(document).on('click', '.wf_ups_package_line_remove', function(){
							jQuery(this).closest('tr').remove();
						});
					});
					jQuery("a.ups_create_shipment").on("click", function() {
						var manual_weight_arr 	= 	jQuery("input[id='ups_manual_weight']").map(function(){return jQuery(this).val();}).get();
						var manual_weight 		=	JSON.stringify(manual_weight_arr);
						
						var manual_height_arr 	= 	jQuery("input[id='ups_manual_height']").map(function(){return jQuery(this).val();}).get();
						var manual_height 		=	JSON.stringify(manual_height_arr);
						
						var manual_width_arr 	= 	jQuery("input[id='ups_manual_width']").map(function(){return jQuery(this).val();}).get();
						var manual_width 		=	JSON.stringify(manual_width_arr);
						
						var manual_length_arr 	= 	jQuery("input[id='ups_manual_length']").map(function(){return jQuery(this).val();}).get();
						var manual_length 		=	JSON.stringify(manual_length_arr);
						
						var manual_insurance_arr 	= 	jQuery("input[id='ups_manual_insurance']").map(function(){return jQuery(this).val();}).get();
						var manual_insurance 		=	JSON.stringify(manual_insurance_arr);
						
						var manual_service_arr	=	[];
						jQuery('.ups_manual_service').each(function(){
							manual_service_arr.push(jQuery(this).val());
						});
						var manual_service 		=	JSON.stringify(manual_service_arr);
						
					    location.href = this.href + '&weight=' + manual_weight +
						'&length=' + manual_length
						+ '&width=' + manual_width
						+ '&height=' + manual_height
						+ '&insurance=' + manual_insurance
						+ '&service=' + manual_service
						+ '&cod=' + jQuery('#ups_cod').is(':checked')
						+ '&sat_delivery=' + jQuery('#ups_sat_delivery').is(':checked')
						+ '&is_return_label=' + jQuery('#ups_return').is(':checked');
					   return false;
					});
				</script>
				<?php
			}
			?>			
			<a class="button button-primary tips ups_generate_packages" href="<?php echo admin_url( '/?wf_ups_generate_packages='.base64_encode( $shipmentId.'|'.$post->ID ) ); ?>" data-tip="<?php _e( 'Generate Packages', 'ups-woocommerce-shipping' ); ?>"><?php _e( 'Generate Packages', 'ups-woocommerce-shipping' ); ?></a><hr style="border-color:#0074a2">
			<script type="text/javascript">
				jQuery("a.ups_generate_packages").on("click", function() {
					location.href = this.href;
				});
			</script>
			<?php
		}
		else {
			$ups_label_details_array = get_post_meta( $post->ID, 'ups_label_details_array', true );
			$ups_commercial_invoice_details = get_post_meta( $post->ID, 'ups_commercial_invoice_details', true );
			if(!empty($ups_label_details_array) && is_array($ups_label_details_array)){
				foreach ( $created_shipments_details_array as $shipmentId => $created_shipments_details ){
					/////
					echo __( 'Shipment ID: ', 'ups-woocommerce-shipping' ).'</strong>'.$shipmentId.'<hr style="border-color:#0074a2">';
					
					if( "yes" == $this->show_label_in_browser ) {
						$target_val = "_blank";
					}
					else {
						$target_val = "_self";
					}
					
					// Multiple labels for each package.
					$index = 0;
					foreach ( $ups_label_details_array[$shipmentId] as $ups_label_details ) {
						$label_extn_code 	= $ups_label_details["Code"];
						$tracking_number 	= isset( $ups_label_details["TrackingNumber"] ) ? $ups_label_details["TrackingNumber"] : '';
						$download_url 		= admin_url( '/?wf_ups_print_label='.base64_encode( $shipmentId.'|'.$post->ID.'|'.$label_extn_code.'|'.$index.'|'.$tracking_number ) );
						$post_fix_label		= '';
						
						if( count($ups_label_details_array) > 1 ) {
							$post_fix_label = '#'.( $index + 1 );
						}
						?>
						<strong><?php _e( 'Tracking No: ', 'ups-woocommerce-shipping' ); ?></strong><a href="http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=<?php echo $ups_label_details["TrackingNumber"] ?>" target="_blank"><?php echo $ups_label_details["TrackingNumber"] ?></a><br/>
						<a class="button button-primary tips" href="<?php echo $download_url; ?>" data-tip="<?php _e( 'Print Label ', 'ups-woocommerce-shipping' );echo $post_fix_label; ?>" target="<?php echo $target_val; ?>"><?php _e( 'Print Label ', 'ups-woocommerce-shipping' );echo $post_fix_label ?></a>
						<hr style="border-color:#0074a2">
						<?php						
						// Return Label Link
						if(isset($created_shipments_details['return'])&&!empty($created_shipments_details['return'])){
							$return_shipment_id=current(array_keys($created_shipments_details['return'])); // only one return label is considered now
							$ups_return_label_details_array = get_post_meta( $post->ID, 'ups_return_label_details_array', true );
							if(is_array($ups_return_label_details_array)&&isset($ups_return_label_details_array[$return_shipment_id])){// check for return label accepted data
								$ups_return_label_details=$ups_return_label_details_array[$return_shipment_id];
								if(is_array($ups_return_label_details)){
									$ups_return_label_detail=current($ups_return_label_details);
									$label_index=0;// as we took only one label so index is zero
									$return_download_url = admin_url( '/?wf_ups_print_label='.base64_encode( $return_shipment_id.'|'.$post->ID.'|'.$label_extn_code.'|'.$label_index.'|return' ) );
									?>
									<strong><?php _e( 'Tracking No: ', 'ups-woocommerce-shipping' ); ?></strong><a href="http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=<?php echo $ups_return_label_detail["TrackingNumber"] ?>" target="_blank"><?php echo $ups_return_label_detail["TrackingNumber"] ?></a><br/>
									<a class="button button-primary tips" href="<?php echo $return_download_url; ?>" data-tip="<?php _e( 'Print Return Label ', 'ups-woocommerce-shipping' );echo $post_fix_label; ?>" target="<?php echo $target_val; ?>"><?php _e( 'Print Return Label ', 'ups-woocommerce-shipping' );echo $post_fix_label ?></a><hr style="border-color:#0074a2">
									<?php
								}
							}
						}
						
						
						// EOF Return Label Link						
						$index = $index + 1;
					}
					
					if(isset($ups_commercial_invoice_details[$shipmentId])){
						echo '<a class="button button-primary tips" target="'.$target_val.'" href="'.admin_url( '/?wf_ups_print_commercial_invoice='.base64_encode($post->ID.'|'.$shipmentId)).'" data-tip="'.__('Print Commercial Invoice', 'ups-woocommerce-shipping').'">'.__('Commercial Invoice', 'ups-woocommerce-shipping').'</a></br>';
					}
				}
				$void_shipment_url = admin_url( '/?wf_ups_void_shipment='.base64_encode( $post->ID ) );
				?>
				<strong><?php _e( 'Cancel the Shipment', 'ups-woocommerce-shipping' ); ?></strong></br>
				<a class="button tips" href="<?php echo $void_shipment_url; ?>" data-tip="<?php _e( 'Void Shipment', 'ups-woocommerce-shipping' ); ?>"><?php _e( 'Void Shipment', 'ups-woocommerce-shipping' ); ?></a><hr style="border-color:#0074a2">
				<?php
			}else{
				$accept_shipment_url = admin_url( '/?wf_ups_shipment_accept='.base64_encode( $post->ID ) );
				?>
				<strong><?php _e( 'Step 3: Accept your shipment.', 'ups-woocommerce-shipping' ); ?></strong></br>
					<a class="button button-primary tips" href="<?php echo $accept_shipment_url; ?>" data-tip="<?php _e('Accept Shipment', 'ups-woocommerce-shipping'); ?>"><?php _e( 'Accept Shipment', 'ups-woocommerce-shipping' ); ?></a><hr style="border-color:#0074a2">
				<?php
			}
			
		}
	}

	function wf_ups_shipment_confirmrequest($order,$return_label=false) {
		global $post;
		
		$ups_settings 					= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		
		// Apply filter on settings data
		$ups_settings	=	apply_filters('wf_ups_confirm_shipment_settings', $ups_settings, $order);
		
		// Define user set variables
		$ups_enabled					= isset( $ups_settings['enabled'] ) ? $ups_settings['enabled'] : '';
		$ups_title						= isset( $ups_settings['title'] ) ? $ups_settings['title'] : 'UPS';
		$ups_availability    			= isset( $ups_settings['availability'] ) ? $ups_settings['availability'] : 'all';
		$ups_countries       			= isset( $ups_settings['countries'] ) ? $ups_settings['countries'] : array();
		// WF: Print Label Settings.
		$print_label_type     			= isset( $ups_settings['print_label_type'] ) ? $ups_settings['print_label_type'] : 'gif';
		$ship_from_address      		= isset( $ups_settings['ship_from_address'] ) ? $ups_settings['ship_from_address'] : 'origin_address';
		$phone_number 					= isset( $ups_settings['phone_number'] ) ? $ups_settings['phone_number'] : '';
		// API Settings
		$ups_user_name        			= isset( $ups_settings['ups_user_name'] ) ? $ups_settings['ups_user_name'] : '';
		$ups_display_name        		= isset( $ups_settings['ups_display_name'] ) ? $ups_settings['ups_display_name'] : '';
		$ups_user_id         			= isset( $ups_settings['user_id'] ) ? $ups_settings['user_id'] : '';
		$ups_password        			= isset( $ups_settings['password'] ) ? $ups_settings['password'] : '';
		$ups_access_key      			= isset( $ups_settings['access_key'] ) ? $ups_settings['access_key'] : '';
		$ups_shipper_number  			= isset( $ups_settings['shipper_number'] ) ? $ups_settings['shipper_number'] : '';
		$ups_negotiated      			= isset( $ups_settings['negotiated'] ) && $ups_settings['negotiated'] == 'yes' ? true : false;
        $ups_residential		        = isset( $ups_settings['residential'] ) && $ups_settings['residential'] == 'yes' ? true : false;
		$shipping_first_name 		= $order->shipping_first_name;
		$shipping_last_name 		= $order->shipping_last_name;
		$shipping_full_name			= $shipping_first_name.' '.$shipping_last_name;
		$shipping_company 			= $order->shipping_company;
		$shipping_address_1 		= $order->shipping_address_1;
		$shipping_address_2 		= $order->shipping_address_2;
		$shipping_city 				= $order->shipping_city;
		$shipping_postcode 			= $order->shipping_postcode;
		$shipping_country 			= $order->shipping_country;
		$shipping_state 			= $order->shipping_state;
		$billing_email 				= $order->billing_email;
		$billing_phone 				= $order->billing_phone;
		$this->accesspoint_locator 	= (isset($this->settings[ 'accesspoint_locator']) && $this->settings[ 'accesspoint_locator']=='yes') ? true : false;
		
		$ups_origin_addressline 	= $order->billing_address_1.', '.$order->billing_address_2;
		$ups_origin_city 			= $order->billing_city;
		$ups_origin_postcode 		= $order->billing_postcode;
		$origin_country				= $order->billing_country;
		$origin_state 				= $order->billing_state;
		
		$cod						= get_post_meta($order->id,'_wf_ups_cod',true);
		$sat_delivery				= get_post_meta($order->id,'_wf_ups_sat_delivery',true);
		$order_total				= $order->get_total();
		$order_currency				= $order->get_order_currency();
		
		$commercial_invoice		        = isset( $ups_settings['commercial_invoice'] ) && $ups_settings['commercial_invoice'] == 'yes' ? true : false;
		
		
		$ship_options=array('return_label'=>$return_label); // Array to pass options like return label on the fly.
		
		if( 'billing_address' == $ship_from_address ) { 
			$ups_display_name	= $order->billing_company;
			$phone_number		= $billing_phone;
			$billing_full_name	= $order->billing_first_name.' '.$order->billing_last_name;
			$ups_user_name		= $billing_full_name;
		}
		else {
			$ups_origin_addressline 		= isset( $ups_settings['origin_addressline'] ) ? $ups_settings['origin_addressline'] : '';
			$ups_origin_city 				= isset( $ups_settings['origin_city'] ) ? $ups_settings['origin_city'] : '';
			$ups_origin_postcode 			= isset( $ups_settings['origin_postcode'] ) ? $ups_settings['origin_postcode'] : '';
			$ups_origin_country_state 		= isset( $ups_settings['origin_country_state'] ) ? $ups_settings['origin_country_state'] : '';
			
			if ( strstr( $ups_origin_country_state, ':' ) ) :
				// WF: Following strict php standards.
				$origin_country_state_array 	= explode(':',$ups_origin_country_state);
				$origin_country 				= current($origin_country_state_array);
				$origin_country_state_array 	= explode(':',$ups_origin_country_state);
				$origin_state   				= end($origin_country_state_array);
			else :
				$origin_country = $ups_origin_country_state;
				$origin_state   = '';
			endif;
			
                        $origin_state = ( isset( $origin_state ) && !empty( $origin_state ) ) ? $origin_state : $ups_settings['origin_custom_state'];
                        
		}

		$shipping_service_data	= $this->wf_get_shipping_service_data( $order ); 
		$shipping_method		= $shipping_service_data['shipping_method'];
		$shipping_service		= $shipping_service_data['shipping_service'];
		$shipping_service_name	= $shipping_service_data['shipping_service_name'];

		if($origin_country	==	$shipping_country){ // Delivery confirmation available only for domestic shipments
			$ship_options['delivery_confirmation_applicable']	= true;
		}
		$package_data = $this->wf_get_package_data( $order,$ship_options);

		if( empty( $package_data ) ) {
			return false;
		}
		
		$package_data		=	apply_filters('wf_ups_filter_label_packages',$package_data);
		$shipments			=	$this->split_shipment_by_services($package_data, $order);
		$shipments			=	apply_filters('wf_ups_shipment_data', $shipments, $order); // Filter to break shipments further, with other business logics, like multi vendor
		
		$shipment_requests	=	array();
		
		if(is_array($shipments)){
			
			foreach($shipments as $shipment){
				$request_arr	=	array();
				$xml_request = '<?xml version="1.0" encoding="UTF-8"?>';
				$xml_request .= '<AccessRequest xml:lang="en-US">';
				$xml_request .= '<AccessLicenseNumber>'.$ups_access_key.'</AccessLicenseNumber>';
				$xml_request .= '<UserId>'.$ups_user_id.'</UserId>';
				$xml_request .= '<Password>'.$ups_password.'</Password>';
				$xml_request .= '</AccessRequest>';
				$xml_request .= '<?xml version="1.0" ?>';
				$xml_request .= '<ShipmentConfirmRequest>';
				$xml_request .= '<Request>';
				$xml_request .= '<TransactionReference>';
				$xml_request .= '<CustomerContext>'.$order->id.'</CustomerContext>';
				$xml_request .= '<XpciVersion>1.0001</XpciVersion>';
				$xml_request .= '</TransactionReference>';
				$xml_request .= '<RequestAction>ShipConfirm</RequestAction>';
				$xml_request .= '<RequestOption>nonvalidate</RequestOption>';
				$xml_request .= '</Request>';
				
				
				// Taking Confirm Shipment Data Into Array for Better Processing and Filtering
				$request_arr['Shipment']=array();
				
				//request for access point
				if($this->accesspoint_locator){// Access Point Addresses Are All Commercial So Overridding ResidentialAddress Condition
					$access_point_node	=	$this->get_confirm_shipment_accesspoint_request($order);
					if(!empty($access_point_node)){
						$ups_residential	=	false;
						$request_arr['Shipment'] = array_merge($access_point_node);
					}
				}
				$request_arr['Shipment']['Description']	=	htmlspecialchars( $this->wf_get_shipment_description( $order ) );
				if($return_label){
					$request_arr['Shipment']['ReturnService']	=	array('Code'	=>	9);
				}
				
				if( $origin_country != $shipping_country || !in_array($origin_country,array('US','PR'))){// ReferenceNumber Valid if the origin/destination pai is not US/US or PR/PR
					$request_arr['Shipment']['ReferenceNumber']	=	array(
						'Code'	=>	'PO',
						'Value'	=>	$order->id,
					);
				}
				
				$phone_number	=	(strlen($phone_number) < 10) ? '0000000000' :  htmlspecialchars( $phone_number );
				$request_arr['Shipment']['Shipper']	=	array(
					'Name'	=>	htmlspecialchars( $ups_user_name ),
					'AttentionName'	=>	htmlspecialchars( $ups_display_name ),
					'PhoneNumber'	=>	(strlen($phone_number) < 10) ? '0000000000' :  htmlspecialchars( $phone_number ),
					'ShipperNumber'	=>	$ups_shipper_number,
					'Address'		=>	array(
						'AddressLine1'		=>	htmlspecialchars( $ups_origin_addressline ),
						'City'				=>	$ups_origin_city,
						'StateProvinceCode'	=>	$origin_state,
						'CountryCode'		=>	$origin_country,
						'PostalCode'		=>	$ups_origin_postcode,
					),
				);
				
				if($return_label){
					$request_arr['Shipment']['ShipTo']	=	array(
						'CompanyName'	=>	htmlspecialchars( $ups_user_name ),
						'AttentionName'	=>	htmlspecialchars( $ups_display_name ),
						'PhoneNumber'	=>	(strlen( $phone_number ) < 10) ? '0000000000' : htmlspecialchars( $phone_number ),
						'Address'		=>	array(
							'AddressLine1'		=>	htmlspecialchars( $ups_origin_addressline ),
							//'AddressLine2'		=>	htmlspecialchars( $ups_origin_addressline ),
							'City'				=>	$ups_origin_city,
							'StateProvinceCode'	=>	$origin_state,
							'CountryCode'		=>	$origin_country,
							'PostalCode'		=>	$ups_origin_postcode,
						)
					);
				}else{
					if( '' == trim( $shipping_company ) ) {
						$shipping_company = '-';
					}
					$request_arr['Shipment']['ShipTo']	=	array(
						'CompanyName'	=>	htmlspecialchars( $shipping_company ),
						'AttentionName'	=>	htmlspecialchars( $shipping_full_name ),
						'PhoneNumber'	=>	(strlen( $billing_phone ) < 10) ? '0000000000' : htmlspecialchars( $billing_phone ),
						'Address'		=>	array(
							'AddressLine1'		=>	htmlspecialchars( $shipping_address_1 ),
							'AddressLine2'		=>	htmlspecialchars( $shipping_address_2 ),
							'City'				=>	$shipping_city,
							'CountryCode'		=>	$shipping_country,
							'PostalCode'		=>	$shipping_postcode,
						)
					);
					
					if(in_array($shipping_country, $this->countries_with_statecodes)){ // State Code valid for certain countries only
						$request_arr['Shipment']['ShipTo']['Address']['StateProvinceCode']	=	$shipping_state;
					}
				}
				
				if( $ups_residential ) {
					$request_arr['Shipment']['ResidentialAddress']='';
				}
				
				$request_arr['Shipment']['Service']	=	array(
					'Code'			=>	$shipment['shipping_service'],
					'Description'	=>	htmlspecialchars( $shipping_service_name ),					
				);
				
				$request_arr['Shipment']['PaymentInformation']	=	array(
					'Prepaid'	=>	array(
						'BillShipper'	=>	array(
							'AccountNumber'	=>	$ups_shipper_number,
						),
					),					
				);
				$request_arr['Shipment']['package']['multi_node']	=	1;
				foreach ( $shipment['packages'] as $package ) {
					$request_arr['Shipment']['package'][]=$package;
				}
				
				
				// Negotiated Rates Flag
				if ( $ups_negotiated ) {
					$request_arr['Shipment']['RateInformation']['NegotiatedRatesIndicator']	=	'';
				}
				
				// Ship From Address is required for Return Label.
				// For return label, Ship From address will be set as Shipping Address of order.
				if($return_label){
					$request_arr['Shipment']['ShipFrom']	=	array(
						'CompanyName'	=>	htmlspecialchars( $shipping_full_name ),
						'AttentionName'	=>	htmlspecialchars( $shipping_company ),
						'Address'		=>	array(
							'AddressLine1'	=>	htmlspecialchars( $shipping_address_1 ),
							'City'			=>	$shipping_city,
							'PostalCode'	=>	$shipping_postcode,
							'CountryCode'	=>	$shipping_country,
						),
					);
					
					if(in_array($shipping_country, $this->countries_with_statecodes)){ // State Code valid for certain countries only
						$request_arr['Shipment']['ShipFrom']['Address']['StateProvinceCode']	=	$shipping_state;
					}
				}
				
				$shipmentServiceOptions = array();
				if($sat_delivery){
					$shipmentServiceOptions['SaturdayDelivery']	=	'';
				}
				
				if($commercial_invoice && ($origin_country	!=	$shipping_country)){ // Commercial Invoice is available only for international shipments
					$soldToPhone	=	(strlen($billing_phone) < 10) ? '0000000000':htmlspecialchars( $billing_phone ); 
					
					
					$sold_to_arr	=	array(
						'CompanyName'	=>	htmlspecialchars($shipping_company),
						'AttentionName'	=>	htmlspecialchars( $shipping_full_name ),
						'PhoneNumber'	=>	$soldToPhone,
						'Address'		=>	array(
							'AddressLine1'	=>	htmlspecialchars( $shipping_address_1 ),
							'City'			=>	$shipping_city,
							'CountryCode'	=>	$shipping_country
						),
					);
					if(in_array($shipping_country, $this->countries_with_statecodes)){ // State Code valid for certain countries only
						$sold_to_arr['StateProvinceCode']	=	$shipping_state;
					}
					$request_arr['Shipment']['SoldTo'] =	$sold_to_arr;
					
					$invoice_products	=	array();
					$orderItems = $order->get_items();
					foreach( $orderItems as $orderItem )
					{
						$item_id 			= $orderItem['variation_id'] ? $orderItem['variation_id'] : $orderItem['product_id'];
						$product_data 		= wc_get_product( $item_id );
						
						$product_unit_weight	=	woocommerce_get_weight( $product_data->get_weight(), $this->weight_unit );
						$product_quantity		=	$orderItem['qty'];
						$product_line_weight	=	$product_unit_weight	*	$product_quantity;
						
						$invoice_products[]['Product']		=	array(
							'Description'	=>	$product_data->get_title(),
							'Unit'			=>	array(
								'Number'	=>	$product_quantity,
								'UnitOfMeasurement'	=>	array('Code'	=>	$this->weight_unit),
								'Weight'	=>	$product_unit_weight,
								'Value'		=>	$product_data->get_price()
							),
							'OriginCountryCode'	=>	$origin_country,
							'NumberOfPackagesPerCommodity'	=>	'1',
							'ProductWeight'	=>	array(
								'UnitOfMeasurement'	=>	$this->weight_unit,
								'Weight'			=>	$product_line_weight,
							),
						);
					}
					
					$shipmentServiceOptions['InternationalForms']	=	array(
						'FormType'				=>	'01',
						'InvoiceNumber'			=>	$order->id,
						'InvoiceDate'			=>	date("Ymd"),
						'Contacts'				=>	array(
							'SoldTo'	=>	array(
								'Name'						=>	htmlspecialchars($shipping_company),
								'AttentionName'				=>	htmlspecialchars( $shipping_full_name ),
								'TaxIdentificationNumber'	=>	'',
								'Phone'						=>	array(
									'Number'	=>	$soldToPhone,
								),
								'Address'					=>	array(
									'AddressLine'	=>	htmlspecialchars( $shipping_address_1 ).' '.htmlspecialchars( $shipping_address_2 ),
									'City'			=>	$shipping_city,
									'PostalCode'	=>	$shipping_postcode,
									'CountryCode'	=>	$shipping_country
								)
							)
						),
						'ExportDate'			=>	date('Ymd'),
						'ExportingCarrier'		=>	'UPS',
						'ReasonForExport'		=>	'SALE',
						'CurrencyCode'			=>	$this->wcsups->get_ups_currency(),
						'OverridePaperlessIndicator'	=>	'1',
					);
					if(in_array($shipping_country, $this->countries_with_statecodes)){
						$shipmentServiceOptions['InternationalForms']['Contacts']['SoldTo']['Address']['StateProvinceCode']	=	$shipping_state;
					}
					
					$shipmentServiceOptions['InternationalForms']['Product']	=	array_merge(array('multi_node'=>1), $invoice_products);
				}
				
				
				if(sizeof($shipmentServiceOptions)){
					$request_arr['Shipment']['ShipmentServiceOptions']	=	$shipmentServiceOptions;
				}
				
				$request_arr['LabelSpecification']['LabelPrintMethod']	=	$this->get_code_from_label_type( $print_label_type );
				$request_arr['LabelSpecification']['HTTPUserAgent']		=	'Mozilla/4.5';
				
				if( 'zpl' == $print_label_type || 'epl' == $print_label_type || 'png' == $print_label_type ) {
					$request_arr['LabelSpecification']['LabelStockSize']	=	array('Height' => 4, 'Width' => 6);
				}
				$request_arr['LabelSpecification']['LabelImageFormat']	=	$this->get_code_from_label_type( $print_label_type );
				
				$request_arr	=	apply_filters('wf_ups_shipment_confirm_request_data', $request_arr, $order);
				
				// Converting array data to xml
				$xml_request .= $this->wcsups->wf_array_to_xml($request_arr);
				
				$xml_request .= '</ShipmentConfirmRequest>';
				$xml_request	=	apply_filters('wf_ups_shipment_confirm_request', $xml_request, $order);
				$shipment_requests[]	=	$xml_request;
			}			
		}
		return $shipment_requests;
	}
	public function get_confirm_shipment_accesspoint_request($order_details){
		$accesspoint = (isset($order_details->shipping_accesspoint)) ? json_decode($order_details->shipping_accesspoint) : '';
		$confirm_accesspoint_request = array();
		if(isset($accesspoint->AddressKeyFormat)){
				
				$access_point_consignee		= $accesspoint->AddressKeyFormat->ConsigneeName;
				$access_point_addressline	= $accesspoint->AddressKeyFormat->AddressLine;
				$access_point_city			= $accesspoint->AddressKeyFormat->PoliticalDivision2;
				$access_point_state			= $accesspoint->AddressKeyFormat->PoliticalDivision1;
				$access_point_postalcode	= $accesspoint->AddressKeyFormat->PostcodePrimaryLow;
				$access_point_country		= $accesspoint->AddressKeyFormat->CountryCode;
				$access_point_id			= $accesspoint->AccessPointInformation->PublicAccessPointID;
				
				$confirm_accesspoint_request	=	array(
					'ShipmentIndicationType'	=>	array('Code'=>02),
					'AlternateDeliveryAddress'	=>	array(
						'Name'				=>	$access_point_consignee,
						'AttentionName'		=>	$access_point_consignee,
						'UPSAccessPointID'	=>	$access_point_id,
						'Address'			=>	array(
							'AddressLine1'		=>	$access_point_addressline,
							'City'				=>	$access_point_city,
							'StateProvinceCode'	=>	$access_point_state,
							'PostalCode'		=>	$access_point_postalcode,
							'CountryCode'		=>	$access_point_country,
						),						
					),
				);
		}	
		return $confirm_accesspoint_request;
		
	}
	private function get_code_from_label_type( $label_type ){
		switch ($label_type) {
			case 'zpl':
				$code_val = 'ZPL';
				break;
			case 'epl':
				$code_val = 'EPL';
				break;
			case 'png':
				$code_val = 'ZPL';
				break;
			default:
				$code_val = 'GIF';
				break;
		}
		return array('Code'=>$code_val);
	}
	
	private function wf_get_shipment_description( $order ){
		$shipment_description	= '\nOrder Id - '.$order->id.'\n';
		$order_items	= $order->get_items();
		
		$shipment_description	.=	'Items - '; 
		if(is_array($order_items) && count($order_items)){
			
			foreach( $order_items as $order_item ) {
				$product_data	= wc_get_product( $order_item['variation_id'] ? $order_item['variation_id'] : $order_item['product_id'] );
				$title 	= $product_data->get_title();
				$shipment_description 	.= $title.', ';
			}
		}

		if ('' == $shipment_description ) {
			$shipment_description = 'Package/customer supplied.';
		}

		$shipment_description = ( strlen( $shipment_description ) >= 50 ) ? substr( $shipment_description, 0, 45 ).'...' : $shipment_description;
		
		return $shipment_description;
	}

	function wf_get_package_data( $order, $ship_options=array()) {
		$package				= $this->wf_create_package( $order );
		
		if ( ! class_exists( 'WF_Shipping_UPS' ) ) {
	  		include_once 'class-wf-shipping-ups.php';
	  	}
		$this->wcsups 			= new WF_Shipping_UPS( $order );
		$package_data_array	= array();		
		
		if(!isset($ship_options['return_label']) || !$ship_options['return_label']){ // If return label is printing, cod can't be applied
			$this->wcsups->wf_set_cod_details($order);
		}
		
		$service_code=get_post_meta($order->id,'wf_ups_selected_service',1);
		if($service_code)
		{
			$this->wcsups->wf_set_service_code($service_code);
			if(in_array($service_code, array(92,93,94,95))){// Insurance value doen't wprk with sure post services
				$this->wcsups->insuredvalue = false;
			}
		}
		
		$package_params	=	array();
		if(isset($ship_options['delivery_confirmation_applicable'])){
			$package_params['delivery_confirmation_applicable']	=	$ship_options['delivery_confirmation_applicable'];
		}
		
		$package_data 		= $this->wcsups->wf_get_api_rate_box_data( $package, $this->packing_method, $package_params);
		
		return $package_data;
	}
	
	function wf_create_package( $order ){
		$orderItems = $order->get_items();
		
		foreach( $orderItems as $orderItem )
		{
			$item_id 			= $orderItem['variation_id'] ? $orderItem['variation_id'] : $orderItem['product_id'];
			$product_data 		= wc_get_product( $item_id );
			$items[$item_id] 	= array('data' => $product_data , 'quantity' => $orderItem['qty']);
		}
		
		$package['contents'] = $items;
		$package['destination'] = array (
        'country' 	=> $order->shipping_country,
        'state' 	=> $order->shipping_state,
        'postcode' 	=> $order->shipping_postcode,
        'city' 		=> $order->shipping_city,
        'address' 	=> $order->shipping_address_1,
        'address_2'	=> $order->shipping_address_2);
		
		return $package;
	}
	
	function wf_ups_generate_packages(){
		$query_string 		= 	explode('|', base64_decode($_GET['wf_ups_generate_packages']));
		$post_id 			= 	$query_string[1];
		$order				= 	$this->wf_load_order( $post_id );
		$package_data		=	$this->wf_get_package_data($order);
		update_post_meta( $post_id, '_wf_ups_stored_packages', $package_data );
		wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
		exit;
	}	
	
	function wf_ups_shipment_confirm(){
		if( !$this->wf_user_check() ) {
			echo "You don't have admin privileges to view this page.";
			exit;
		}
		
		// Load UPS Settings.
		$ups_settings 		= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
		$api_mode      		= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
        $debug_mode      	= isset( $ups_settings['debug'] ) && $ups_settings['debug'] == 'yes' ? true : false;
		
		$query_string 		= explode('|', base64_decode($_GET['wf_ups_shipment_confirm']));
		$post_id 			= $query_string[1];
		$wf_ups_selected_service	= isset( $_GET['wf_ups_selected_service'] ) ? $_GET['wf_ups_selected_service'] : '';
		update_post_meta( $post_id, 'wf_ups_selected_service', $wf_ups_selected_service );
		
		$cod	= isset( $_GET['cod'] ) ? $_GET['cod'] : '';
		if($cod=='true'){
			update_post_meta( $post_id, '_wf_ups_cod', true );
		}else{
			delete_post_meta( $post_id, '_wf_ups_cod');
		}

		$sat_delivery	= isset( $_GET['sat_delivery'] ) ? $_GET['sat_delivery'] : '';
		if($sat_delivery=='true'){
			update_post_meta( $post_id, '_wf_ups_sat_delivery', true );
		}else{
			delete_post_meta( $post_id, '_wf_ups_sat_delivery');
		}

		$is_return_label	= isset( $_GET['is_return_label'] ) ? $_GET['is_return_label'] : '';
		if($is_return_label=='true'){
			$ups_return=true;
		}
		else{
			$ups_return=false;
		}
		$order				= $this->wf_load_order( $post_id );
        
		$requests = $this->wf_ups_shipment_confirmrequest( $order );
		
		$created_shipments_details_array = array();
		
		foreach($requests as $request){
			if( $debug_mode ) {
				echo 'SHIPMENT CONFIRM REQUEST: ';
				echo '<xmp>'.$request.'</xmp>'; 
			}
			
			if( !$request ) {
				// Due to some error and request not available, But the error is not catched
				wf_admin_notice::add_notice('Sorry. Something went wrong: please turn on debug mode to investigate more.');
				wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
				exit;//return;
			}
			if( "Live" == $api_mode ) {
				$endpoint = 'https://www.ups.com/ups.app/xml/ShipConfirm';
			}
			else {
				$endpoint = 'https://wwwcie.ups.com/ups.app/xml/ShipConfirm';
			}

			$xml_request = str_replace( array( "\n", "\r" ), '', $request );
			
			$response = wp_remote_post( $endpoint,
				array(
					'timeout'   => 70,
					'sslverify' => $this->ssl_verify,
					'body'      => $xml_request
				)
			);
			if( $debug_mode ) {
				echo 'SHIPMENT CONFIRM RESPONSE: ';
				var_dump( $response );  
			}
			
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				wf_admin_notice::add_notice('Sorry. Something went wrong: '.$error_message);			
				wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
				exit;
			}		

			$response_obj = simplexml_load_string( $response['body'] );
			
			$response_code = (string)$response_obj->Response->ResponseStatusCode;
			if( '0' == $response_code ) {
				$error_code = (string)$response_obj->Response->Error->ErrorCode;
				$error_desc = (string)$response_obj->Response->Error->ErrorDescription;
				
				
				wf_admin_notice::add_notice($error_desc.' [Error Code: '.$error_code.']');
				wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
				exit;
			}			
			
			$created_shipments_details = array();
			$shipment_id = (string)$response_obj->ShipmentIdentificationNumber;
			
			$created_shipments_details["ShipmentDigest"] 			= (string)$response_obj->ShipmentDigest;

			$created_shipments_details_array[$shipment_id] = $created_shipments_details;
			
			
			
			// Creating Return Label 		
			if($ups_return){
				$this->wf_ups_return_shipment_confirm($shipment_id);
			}
		}
		update_post_meta( $post_id, 'ups_created_shipments_details_array', $created_shipments_details_array );
		$this->ups_accept_shipment($post_id);
		wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
		exit;
	}
	
	function wf_ups_return_shipment_confirm($parent_shipment_id){
		if( !$this->wf_user_check() ) {
			echo "You don't have admin privileges to view this page.";
			exit;
		}
		
		// Load UPS Settings.
		$ups_settings 		= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
		$api_mode      		= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
        $debug_mode      	= isset( $ups_settings['debug'] ) && $ups_settings['debug'] == 'yes' ? true : false;
		
		$query_string 		= explode('|', base64_decode($_GET['wf_ups_shipment_confirm']));
		$post_id 			= $query_string[1];
		$wf_ups_selected_service	= isset( $_GET['wf_ups_selected_service'] ) ? $_GET['wf_ups_selected_service'] : '';	
			
		$order				= $this->wf_load_order( $post_id );        
		$request = $this->wf_ups_shipment_confirmrequest( $order,true);//true for return label, false for general shipment, default is false	
                
        
        if( $debug_mode ) {
            echo 'SHIPMENT CONFIRM REQUEST: ';
            var_dump( $request ); 
        }
        
		if( !$request ) return;

		if( "Live" == $api_mode ) {
			$endpoint = 'https://www.ups.com/ups.app/xml/ShipConfirm';
		}
		else {
			$endpoint = 'https://wwwcie.ups.com/ups.app/xml/ShipConfirm';
		}

		$xml_request = str_replace( array( "\n", "\r" ), '', $request );
		
		$response = wp_remote_post( $endpoint,
			array(
				'timeout'   => 70,
				'sslverify' => $this->ssl_verify,
				'body'      => $xml_request
			)
		);
        if( $debug_mode ) {
            echo 'SHIPMENT CONFIRM RESPONSE: ';
            var_dump( $response );   
        }
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$error_message='Return Label - '.$error_message;
			wf_admin_notice::add_notice('Sorry. Something went wrong: '.$error_message);
			wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit' ) );
			exit;
		}
		
		$response_obj = simplexml_load_string( $response['body'] );
		
		$response_code = (string)$response_obj->Response->ResponseStatusCode;
		if( '0' == $response_code ) {
			$error_code = (string)$response_obj->Response->Error->ErrorCode;
			$error_desc = (string)$response_obj->Response->Error->ErrorDescription;
			$error_desc='Return Label - '.$error_desc;
			wf_admin_notice::add_notice($error_desc.' [Error Code: '.$error_code.']');
			wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
			exit;
		}
		$created_shipments_details_array=get_post_meta($post_id, 'ups_created_shipments_details_array', 1);		
		$created_shipments_details = array();
		$shipment_id = (string)$response_obj->ShipmentIdentificationNumber;
		
		$created_shipments_details["ShipmentDigest"] 			= (string)$response_obj->ShipmentDigest;

		$created_shipments_details_array[$parent_shipment_id]['return'][$shipment_id] = $created_shipments_details;
		update_post_meta( $post_id, 'ups_created_shipments_details_array', $created_shipments_details_array );
		return true;
	}
	
	function wf_ups_shipment_accept(){
		if( !$this->wf_user_check() ) {
			echo "You don't have admin privileges to view this page.";
			exit;
		}

		$query_string		= explode('|', base64_decode($_GET['wf_ups_shipment_accept']));
		$post_id 			= $query_string[0];
		
		
		// Load UPS Settings.
		$ups_settings 				= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
		$api_mode      				= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
		$ups_user_id         		= isset( $ups_settings['user_id'] ) ? $ups_settings['user_id'] : '';
		$ups_password        		= isset( $ups_settings['password'] ) ? $ups_settings['password'] : '';
		$ups_access_key      		= isset( $ups_settings['access_key'] ) ? $ups_settings['access_key'] : '';
		$ups_shipper_number  		= isset( $ups_settings['shipper_number'] ) ? $ups_settings['shipper_number'] : '';
		$disble_shipment_tracking	= isset( $ups_settings['disble_shipment_tracking'] ) ? $ups_settings['disble_shipment_tracking'] : 'TrueForCustomer';
        $debug_mode      	        = isset( $ups_settings['debug'] ) && $ups_settings['debug'] == 'yes' ? true : false;
		
		if( "Live" == $api_mode ) {
			$endpoint = 'https://www.ups.com/ups.app/xml/ShipAccept';
		}
		else {
			$endpoint = 'https://wwwcie.ups.com/ups.app/xml/ShipAccept';
		}		
		
		$created_shipments_details_array	= get_post_meta($post_id, 'ups_created_shipments_details_array', true);	

		$shipment_accept_requests	=	array();
		if(is_array($created_shipments_details_array)){
			
			$ups_label_details_array	= array();
			$shipment_id_cs 			= '';
			
			foreach($created_shipments_details_array as $shipmentId => $created_shipments_details){
				if(isset($created_shipments_details['ShipmentDigest'])){
					$xml_request = '<?xml version="1.0" encoding="UTF-8" ?>';
					$xml_request .= '<AccessRequest xml:lang="en-US">';
					$xml_request .= '<AccessLicenseNumber>'.$ups_access_key.'</AccessLicenseNumber>';
					$xml_request .= '<UserId>'.$ups_user_id.'</UserId>';
					$xml_request .= '<Password>'.$ups_password.'</Password>';
					$xml_request .= '</AccessRequest>'; 
					$xml_request .= '<?xml version="1.0" ?>';
					$xml_request .= '<ShipmentAcceptRequest>';
					$xml_request .= '<Request>';
					$xml_request .= '<TransactionReference>';
					$xml_request .= '<CustomerContext>'.$post_id.'</CustomerContext>';
					$xml_request .= '<XpciVersion>1.0001</XpciVersion>';
					$xml_request .= '</TransactionReference>';
					$xml_request .= '<RequestAction>ShipAccept</RequestAction>';
					$xml_request .= '</Request>';
					$xml_request .= '<ShipmentDigest>'.$created_shipments_details["ShipmentDigest"].'</ShipmentDigest>';
					$xml_request .= '</ShipmentAcceptRequest>';
					
					if( $debug_mode ) {
						echo 'SHIPMENT ACCEPT REQUEST: ';
						var_dump( $xml_request );   
					}
					
					$response = wp_remote_post( $endpoint,
						array(
							'timeout'   => 70,
							'sslverify' => $this->ssl_verify,
							'body'      => $xml_request
						)
					);
					
					if( $debug_mode ) {
						echo 'SHIPMENT ACCEPT RESPONSE: ';
						var_dump( $response );   
					}
					
					if ( is_wp_error( $response ) ) {
						$error_message = $response->get_error_message();
						wf_admin_notice::add_notice(__('Order # '.$post_id.' Shipment # '.$shipmentId.' - Sorry. Something went wrong: '.$error_message));
						continue;
					}

					$response_obj = simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/','', $response['body'] ) . '</root>' );	

					$response_code = (string)$response_obj->ShipmentAcceptResponse->Response->ResponseStatusCode;
					
					if('0' == $response_code) {
						$error_code = (string)$response_obj->ShipmentAcceptResponse->Response->Error->ErrorCode;
						$error_desc = (string)$response_obj->ShipmentAcceptResponse->Response->Error->ErrorDescription;
						
						wf_admin_notice::add_notice(__('Order # '.$post_id.' Shipment # '.$shipmentId.' - '.$error_desc.' [Error Code: '.$error_code.']'));
						continue;
					}

					$package_results 			= $response_obj->ShipmentAcceptResponse->ShipmentResults->PackageResults;
					$ups_label_details			= array();
					
					
					
					if(isset($response_obj->ShipmentAcceptResponse->ShipmentResults->Form->Image)){
						$international_forms[$shipmentId]	=	array(
							'ImageFormat'	=>	(string)$response_obj->ShipmentAcceptResponse->ShipmentResults->Form->Image->ImageFormat->Code,
							'GraphicImage'	=>	(string)$response_obj->ShipmentAcceptResponse->ShipmentResults->Form->Image->GraphicImage,
						);
					}
					// Labels for each package.
					foreach ( $package_results as $package_result ) {
						$ups_label_details["TrackingNumber"]		= (string)$package_result->TrackingNumber;
						$ups_label_details["Code"] 					= (string)$package_result->LabelImage->LabelImageFormat->Code;
						$ups_label_details["GraphicImage"] 			= (string)$package_result->LabelImage->GraphicImage;			
						$ups_label_details_array[$shipmentId][]		= $ups_label_details;
						$shipment_id_cs 							.= $ups_label_details["TrackingNumber"].',';
					}
				}
			}
			$shipment_id_cs = rtrim( $shipment_id_cs, ',' );

			if( empty($ups_label_details_array) ) {
				wf_admin_notice::add_notice('UPS: Sorry, An unexpected error occurred.');
				wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
				exit;
			}
			else {
				update_post_meta( $post_id, 'ups_label_details_array', $ups_label_details_array );
				
				if(isset($international_forms)){
					update_post_meta( $post_id, 'ups_commercial_invoice_details', $international_forms );
				}
				
				if( isset($created_shipments_details['return']) && $created_shipments_details['return'] ){// creating return label
					$return_label_ids=$this->wf_ups_return_shipment_accept($post_id,$created_shipments_details['return']);
					if($return_label_ids&&$shipment_id_cs){
						$shipment_id_cs=$shipment_id_cs.','.$return_label_ids;
					}
				}
			}			
			if( 'True' != $disble_shipment_tracking) {
				wp_redirect( admin_url( '/post.php?post='.$post_id.'&wf_ups_track_shipment='.$shipment_id_cs ) );
				exit;
			}
			wf_admin_notice::add_notice('UPS: Shipment accepted successfully. Labels are ready for printing.','notice');
			wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
			exit;
		}
	}
	
	function wf_ups_return_shipment_accept($post_id,$shipment_data){
		if( !$this->wf_user_check() ) {
			echo "You don't have admin privileges to view this page.";
			exit;
		}
		
		// Load UPS Settings.
		$ups_settings 				= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
		$api_mode      				= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
		$ups_user_id         		= isset( $ups_settings['user_id'] ) ? $ups_settings['user_id'] : '';
		$ups_password        		= isset( $ups_settings['password'] ) ? $ups_settings['password'] : '';
		$ups_access_key      		= isset( $ups_settings['access_key'] ) ? $ups_settings['access_key'] : '';
		$ups_shipper_number  		= isset( $ups_settings['shipper_number'] ) ? $ups_settings['shipper_number'] : '';
		$disble_shipment_tracking	= isset( $ups_settings['disble_shipment_tracking'] ) ? $ups_settings['disble_shipment_tracking'] : 'TrueForCustomer';
        $debug_mode      	        = isset( $ups_settings['debug'] ) && $ups_settings['debug'] == 'yes' ? true : false;
		
		if( "Live" == $api_mode ) {
			$endpoint = 'https://www.ups.com/ups.app/xml/ShipAccept';
		}
		else {
			$endpoint = 'https://wwwcie.ups.com/ups.app/xml/ShipAccept';
		}
		
		foreach($shipment_data as $shipment_id=>$created_shipments_details){	
			$created_shipments_details=current($shipment_data);// only one shipment is allowed
			$xml_request = '<?xml version="1.0"?>';
			$xml_request .= '<AccessRequest xml:lang="en-US">';
			$xml_request .= '<AccessLicenseNumber>'.$ups_access_key.'</AccessLicenseNumber>';
			$xml_request .= '<UserId>'.$ups_user_id.'</UserId>';
			$xml_request .= '<Password>'.$ups_password.'</Password>';
			$xml_request .= '</AccessRequest>'; 
			$xml_request .= '<?xml version="1.0"?>';
			$xml_request .= '<ShipmentAcceptRequest>';
			$xml_request .= '<Request>';
			$xml_request .= '<TransactionReference>';
			$xml_request .= '<CustomerContext>'.$post_id.'</CustomerContext>';
			$xml_request .= '<XpciVersion>1.0001</XpciVersion>';
			$xml_request .= '</TransactionReference>';
			$xml_request .= '<RequestAction>ShipAccept</RequestAction>';
			$xml_request .= '</Request>';
			$xml_request .= '<ShipmentDigest>'.$created_shipments_details["ShipmentDigest"].'</ShipmentDigest>';
			$xml_request .= '</ShipmentAcceptRequest>';
			
			if( $debug_mode ) {
				echo 'RETURN SHIPMENT ACCEPT REQUEST: ';
				var_dump( $xml_request );   
			}
			
			$response = wp_remote_post( $endpoint,
				array(
					'timeout'   => 70,
					'sslverify' => $this->ssl_verify,
					'body'      => $xml_request
				)
			);
			
			if( $debug_mode ) {
				echo 'RETURN SHIPMENT ACCEPT RESPONSE: ';
				var_dump( $response );   
			}	
			$response_obj = simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/','', $response['body'] ) . '</root>' );	
			$response_code = (string)$response_obj->ShipmentAcceptResponse->Response->ResponseStatusCode;
			if('0' == $response_code) {
				$error_code = (string)$response_obj->ShipmentAcceptResponse->Response->Error->ErrorCode;
				$error_desc = (string)$response_obj->ShipmentAcceptResponse->Response->Error->ErrorDescription;
				
				wf_admin_notice::add_notice($error_desc.' [Error Code: '.$error_code.']');
				return false;
			}
			$package_results 			= $response_obj->ShipmentAcceptResponse->ShipmentResults->PackageResults;		
			
			$shipment_id_cs = '';
			// Labels for each package.
			foreach ( $package_results as $package_result ) {
				$ups_label_details["TrackingNumber"]		= (string)$package_result->TrackingNumber;
				$ups_label_details["Code"] 					= (string)$package_result->LabelImage->LabelImageFormat->Code;
				$ups_label_details["GraphicImage"] 			= (string)$package_result->LabelImage->GraphicImage;
				$ups_label_details_array[$shipment_id][]	= $ups_label_details;
				$shipment_id_cs 							.= $ups_label_details["TrackingNumber"].',';
			}
			$shipment_id_cs = rtrim( $shipment_id_cs, ',' );			
			if( empty($ups_label_details_array) ) {				
				wf_admin_notice::add_notice('UPS: Sorry, An unexpected error occurred while creating return label.');
				return false;
			}
			else {
				update_post_meta( $post_id, 'ups_return_label_details_array', $ups_label_details_array );
				return $shipment_id_cs;
			}
			break; // Only one return shipment is allowed
			return false;
		}
	}

	function wf_ups_print_label(){
		if( !$this->wf_user_check() ) {
			echo "You don't have admin privileges to view this page.";
			exit;
		}
		
		$ups_settings 				= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		$print_label_type	= isset( $ups_settings['print_label_type'] ) ? $ups_settings['print_label_type'] : 'gif';

		$query_string		= explode('|', base64_decode($_GET['wf_ups_print_label']));
		$shipmentId 		= $query_string[0];
		$post_id 			= $query_string[1];
		$label_extn_code 	= $query_string[2];
		$index			 	= $query_string[3];
        $tracking_number    = $query_string[4];
		
		$label_meta_name='ups_label_details_array';
		if(isset($query_string[4])){
			$return			= $query_string[4];
			if($return=='return'){
				$label_meta_name='ups_return_label_details_array';
			}
		}
		
		$ups_label_details_array = get_post_meta( $post_id, $label_meta_name, true );
        
        $ups_settings 				  = get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
        $show_label_in_browser        = isset( $ups_settings['show_label_in_browser'] ) ? $ups_settings['show_label_in_browser'] : 'no';

		if( empty($ups_label_details_array) ) {
			wf_admin_notice::add_notice('UPS: Sorry, An unexpected error occurred.');
			wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
			exit;
		}

		$graphic_image = $ups_label_details_array[$shipmentId][$index]["GraphicImage"];
		
        if("GIF" == $label_extn_code) {
            if( "yes" == $show_label_in_browser ) {
                echo '<img src="data:image/gif;base64,' . $graphic_image. '" />';
                exit;
            }
            
            //$binary_label = base64_decode($graphic_image);
            $binary_label = base64_decode(chunk_split($graphic_image));
            
			$final_image 	= $binary_label;
			$extn_code		= 'gif';
		}
        // ZPL which will be converted to PNG.
		elseif("ZPL" == $label_extn_code && $print_label_type == 'zpl') {
            $binary_label = base64_decode(chunk_split($graphic_image));
            
            // By default zpl code returned by UPS has ^POI command, which will invert the label because
            // of some reason. Removing it so that label will not be inverted.
            $zpl_label_inverted = str_replace( "^POI", "", $binary_label);
			
			$file_name = 'UPS-ShippingLabel-Label-'.$post_id.'-'.$tracking_number.'.zpl';
			$this->wf_generate_document_file($zpl_label_inverted, $file_name);
			exit;
		}
		elseif("EPL" == $label_extn_code && $print_label_type == 'epl') {
            $binary_label = base64_decode(chunk_split($graphic_image));
            
			$file_name = 'UPS-ShippingLabel-Label-'.$post_id.'-'.$tracking_number.'.epl';
			$this->wf_generate_document_file($binary_label, $file_name);
			exit;
		}

        else {
            //$zpl_label = base64_decode($graphic_image);
            $zpl_label = base64_decode(chunk_split($graphic_image));
            // By default zpl code returned by UPS has ^POI command, which will invert the label because
            // of some reason. Removing it so that label will not be inverted.
            $zpl_label_inverted = str_replace( "^POI", "", $zpl_label);

			$response 		= wp_remote_post( "http://api.labelary.com/v1/printers/8dpmm/labels/4x6/0/",
				array(
					'timeout'   => 70,
					'sslverify' => $this->ssl_verify,
					'body'      => $zpl_label_inverted
				)
			);
            
            //var_dump( $response ); die();
            
			$final_image 	= $response["body"];
			$extn_code		= 'png';
            
            if( "yes" == $show_label_in_browser ) {
                $final_image_base64_encoded = base64_encode( $final_image );
                echo '<img src="data:image/png;base64,' . $final_image_base64_encoded. '" />';
                exit;
            }
        
		}

        header('Content-Description: File Transfer');
        header('Content-Type: image/'.$extn_code.'');
        header('Content-disposition: attachment; filename="UPS-ShippingLabel-' . 'Label-'.$post_id.'-'.$tracking_number.'.'.$extn_code.'"');
		echo $final_image;
		exit;
	}
	
	function wf_ups_print_commercial_invoice(){
		$req_data	= explode('|',base64_decode($_GET['wf_ups_print_commercial_invoice']));
		
		$post_id		=	$req_data[0];
		$shipment_id	=	$req_data[1];
		
		$invoice_details = get_post_meta( $post_id, 'ups_commercial_invoice_details', true );
		$graphic_image = $invoice_details[$shipment_id]["GraphicImage"];
		
		$extn_code	=	$invoice_details[$shipment_id]["ImageFormat"];
		
		header('Content-Description: File Transfer');
        header('Content-Type: image/'.$extn_code.'');
        header('Content-disposition: attachment; filename="UPS-Commercial-Invoice-'.$post_id.'.'.$extn_code.'"');
		echo base64_decode($graphic_image);
		exit;
	}

	private function wf_generate_document_file($content, $file_name){
		
		$uploads_dir_info		=	wp_upload_dir();
		$file_name_with_path	=	$uploads_dir_info['basedir'].$file_name;
		$handle = fopen($file_name_with_path, "w");
		fwrite($handle, $content);
		fclose($handle);
		
		

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.basename($file_name));
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file_name_with_path));
		readfile($file_name_with_path);
		return;
	}

	function wf_ups_void_shipment(){
		if( !$this->wf_user_check() ) {
			echo "You don't have admin privileges to view this page.";
			exit;
		}
	
		$query_string		= explode( '|', base64_decode( $_GET['wf_ups_void_shipment'] ) );
		$post_id 			= $query_string[0];
		$ups_label_details_array 	= get_post_meta( $post_id, 'ups_label_details_array', true );
		
		// Load UPS Settings.
		$ups_settings 				= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
		$api_mode		      		= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
		$ups_user_id         		= isset( $ups_settings['user_id'] ) ? $ups_settings['user_id'] : '';
		$ups_password        		= isset( $ups_settings['password'] ) ? $ups_settings['password'] : '';
		$ups_access_key      		= isset( $ups_settings['access_key'] ) ? $ups_settings['access_key'] : '';
		$ups_shipper_number  		= isset( $ups_settings['shipper_number'] ) ? $ups_settings['shipper_number'] : '';
		
		
		
		$client_side_reset = false;
		if( isset( $_GET['client_reset'] ) ) {
			$client_side_reset = true;
		}
		
		if( "Live" == $api_mode ) {
			$endpoint = 'https://www.ups.com/ups.app/xml/Void';
		}
		else {
			$endpoint = 'https://wwwcie.ups.com/ups.app/xml/Void';
		}	
		
		if( !empty( $ups_label_details_array ) && !$client_side_reset ) {
			foreach($ups_label_details_array as $shipmentId => $ups_label_detail_arr){
				$xml_request = '<?xml version="1.0" ?>';
				$xml_request .= '<AccessRequest xml:lang="en-US">';
				$xml_request .= '<AccessLicenseNumber>'.$ups_access_key.'</AccessLicenseNumber>';
				$xml_request .= '<UserId>'.$ups_user_id.'</UserId>';
				$xml_request .= '<Password>'.$ups_password.'</Password>';
				$xml_request .= '</AccessRequest>';
				$xml_request .= '<?xml version="1.0" encoding="UTF-8" ?>';
				$xml_request .= '<VoidShipmentRequest>';
				$xml_request .= '<Request>';
				$xml_request .= '<TransactionReference>';
				$xml_request .= '<CustomerContext>'.$post_id.'</CustomerContext>';
				$xml_request .= '<XpciVersion>1.0001</XpciVersion>';
				$xml_request .= '</TransactionReference>';
				$xml_request .= '<RequestAction>Void</RequestAction>';
				$xml_request .= '<RequestOption />';
				$xml_request .= '</Request>';
				$xml_request .= '<ExpandedVoidShipment>';
				$xml_request .= '<ShipmentIdentificationNumber>'.$shipmentId.'</ShipmentIdentificationNumber>';
				foreach ( $ups_label_detail_arr as $ups_label_details ) {
					$xml_request .= '<TrackingNumber>'.$ups_label_details["TrackingNumber"].'</TrackingNumber>';
				}
				$xml_request .= '</ExpandedVoidShipment>';
				$xml_request .= '</VoidShipmentRequest>';
				
				
				$response = wp_remote_post( $endpoint,
					array(
						'timeout'   => 70,
						'sslverify' => $this->ssl_verify,
						'body'      => $xml_request
					)
				);
				
				// In case of any issues with remote post.
				if ( is_wp_error( $response ) ) {
					wf_admin_notice::add_notice('Sorry. Something went wrong: '.$error_message);
					wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
					exit;
				}
				
				$response_obj 	= simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/','', $response['body'] ) . '</root>' );
				$response_code 	= (string)$response_obj->VoidShipmentResponse->Response->ResponseStatusCode;

				// It is an error response.
				if( '0' == $response_code ) {
					$error_code = (string)$response_obj->VoidShipmentResponse->Response->Error->ErrorCode;
					$error_desc = (string)$response_obj->VoidShipmentResponse->Response->Error->ErrorDescription;
					
					$message = '<strong>'.$error_desc.' [Error Code: '.$error_code.']'.'. </strong>';

					$current_page_uri	= $_SERVER['REQUEST_URI'];
					$href_url 			= $current_page_uri.'&client_reset';
					
					$message .= 'Please contact UPS to void/cancel this shipment. <br/>';
					$message .= 'If you have already cancelled this shipment by calling UPS customer care, and you would like to create shipment again then click <a class="button button-primary tips" href="'.$href_url.'" data-tip="Client Side Reset">Client Side Reset</a>';
					$message .= '<p style="color:red"><strong>Note: </strong>Previous shipment details and label will be removed from Order page.</p>';
					
					if( "Test" == $api_mode ) {
						$message .= "<strong>Also, noticed that you have enabled 'Test' mode.<br/>Please note that void is not possible in 'Test' mode, as there is no real shipment is created with UPS. </strong><br/>";
					}
					wf_admin_notice::add_notice($message);
					wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
					exit;
				}
				
				$this->wf_ups_void_return_shipment($post_id,$shipmentId);
			}			
		}
		update_post_meta( $post_id, 'ups_created_shipments_details_array', $empty_array );
		update_post_meta( $post_id, 'ups_label_details_array', $empty_array );
		update_post_meta( $post_id, 'ups_commercial_invoice_details', $empty_array );
		update_post_meta( $post_id, 'wf_ups_selected_service', '' );
		
		// Reset of stored meta elements done. Back to admin order page. 
		if( $client_side_reset ){
			wf_admin_notice::add_notice('UPS: Client side reset of labels and shipment completed. You can re-initiate shipment now.','notice');
			wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
			exit;
		}
		wf_admin_notice::add_notice('UPS: Cancellation of shipment completed successfully. You can re-initiate shipment.','notice');
		wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
		exit;
	}
	
	function wf_ups_void_return_shipment($post_id,$shipmentId){
		$ups_created_shipments_details_array=get_post_meta($post_id,'ups_created_shipments_details_array',1);
		if(is_array($ups_created_shipments_details_array)&&isset($ups_created_shipments_details_array[$shipmentId]['return'])){
			$return_shipment_id=current(array_keys($ups_created_shipments_details_array[$shipmentId]['return']));
			if($return_shipment_id){
				// Load UPS Settings.
				$ups_settings 				= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
				// API Settings
				$api_mode		      		= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
				$ups_user_id         		= isset( $ups_settings['user_id'] ) ? $ups_settings['user_id'] : '';
				$ups_password        		= isset( $ups_settings['password'] ) ? $ups_settings['password'] : '';
				$ups_access_key      		= isset( $ups_settings['access_key'] ) ? $ups_settings['access_key'] : '';
				$ups_shipper_number  		= isset( $ups_settings['shipper_number'] ) ? $ups_settings['shipper_number'] : '';
				
				$ups_return_label_details_array 	= get_post_meta( $post_id, 'ups_return_label_details_array', true );
				
				$client_side_reset = false;
				if( isset( $_GET['client_reset'] ) ) {
					$client_side_reset = true;
				}
				
				if( "Live" == $api_mode ) {
					$endpoint = 'https://www.ups.com/ups.app/xml/Void';
				}
				else {
					$endpoint = 'https://wwwcie.ups.com/ups.app/xml/Void';
				}
				
				if( !empty( $ups_return_label_details_array ) && $return_shipment_id) {
					$xml_request = '<?xml version="1.0" ?>';
					$xml_request .= '<AccessRequest xml:lang="en-US">';
					$xml_request .= '<AccessLicenseNumber>'.$ups_access_key.'</AccessLicenseNumber>';
					$xml_request .= '<UserId>'.$ups_user_id.'</UserId>';
					$xml_request .= '<Password>'.$ups_password.'</Password>';
					$xml_request .= '</AccessRequest>';
					$xml_request .= '<?xml version="1.0" encoding="UTF-8" ?>';
					$xml_request .= '<VoidShipmentRequest>';
					$xml_request .= '<Request>';
					$xml_request .= '<TransactionReference>';
					$xml_request .= '<CustomerContext>'.$post_id.'</CustomerContext>';
					$xml_request .= '<XpciVersion>1.0001</XpciVersion>';
					$xml_request .= '</TransactionReference>';
					$xml_request .= '<RequestAction>Void</RequestAction>';
					$xml_request .= '<RequestOption />';
					$xml_request .= '</Request>';
					$xml_request .= '<ExpandedVoidShipment>';
					$xml_request .= '<ShipmentIdentificationNumber>'.$return_shipment_id.'</ShipmentIdentificationNumber>';
					foreach ( $ups_return_label_details_array[$return_shipment_id] as $ups_return_label_details ) {
						$xml_request .= '<TrackingNumber>'.$ups_return_label_details["TrackingNumber"].'</TrackingNumber>';
					}
					$xml_request .= '</ExpandedVoidShipment>';
					$xml_request .= '</VoidShipmentRequest>';
					$response = wp_remote_post( $endpoint,
						array(
							'timeout'   => 70,
							'sslverify' => $this->ssl_verify,
							'body'      => $xml_request
						)
					);
					
					// In case of any issues with remote post.
					if ( is_wp_error( $response ) ) {
						wf_admin_notice::add_notice('Sorry. Something went wrong: '.$error_message);
						return;
					}
					
					$response_obj 	= simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/','', $response['body'] ) . '</root>' );
					$response_code 	= (string)$response_obj->VoidShipmentResponse->Response->ResponseStatusCode;

					// It is an error response.
					if( '0' == $response_code ) {
						$error_code = (string)$response_obj->VoidShipmentResponse->Response->Error->ErrorCode;
						$error_desc = (string)$response_obj->VoidShipmentResponse->Response->Error->ErrorDescription;
						
						$message = '<strong>'.$error_desc.' [Error Code: '.$error_code.']'.'. </strong>';

						$current_page_uri	= $_SERVER['REQUEST_URI'];
						$href_url 			= $current_page_uri.'&client_reset';
						
						$message .= 'Please contact UPS to void/cancel this shipment. <br/>';
						$message .= 'If you have already cancelled this shipment by calling UPS customer care, and you would like to create shipment again then click <a class="button button-primary tips" href="'.$href_url.'" data-tip="Client Side Reset">Client Side Reset</a>';
						$message .= '<p style="color:red"><strong>Note: </strong>Previous shipment details and label will be removed from Order page.</p>';
						
						if( "Test" == $api_mode ) {
							$message .= "<strong>Also, noticed that you have enabled 'Test' mode.<br/>Please note that void is not possible in 'Test' mode, as there is no real shipment is created with UPS. </strong><br/>";
						}
						
						wf_admin_notice::add_notice($message);
						return;
					}
				}
				$empty_array = array();
				update_post_meta( $post_id, 'ups_return_label_details_array', $empty_array );
			}
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
	
	function wf_get_shipping_service_data($order){
		//TODO: Take the first shipping method. The use case of multiple shipping method for single order is not handled.
		
		$shipping_methods = $order->get_shipping_methods();
		if ( ! $shipping_methods ) {
			return false;
		}

		$shipping_method			= array_shift( $shipping_methods );
		$shipping_service_tmp_data	= explode( ':',$shipping_method['method_id'] );
		$wf_ups_selected_service	= '';

		$wf_ups_selected_service 	= get_post_meta( $order->id, 'wf_ups_selected_service', true );

		if( '' != $wf_ups_selected_service ) {
			$shipping_service_data['shipping_method'] 		= WF_UPS_ID;
			$shipping_service_data['shipping_service'] 		= $wf_ups_selected_service;
			$shipping_service_data['shipping_service_name']	= isset( $ups_services[$wf_ups_selected_service] ) ? $ups_services[$wf_ups_selected_service] : '';
		}
		else if( !isset( $shipping_service_tmp_data[0] ) || 
			( isset( $shipping_service_tmp_data[0] ) && $shipping_service_tmp_data[0] != WF_UPS_ID ) ){
			$shipping_service_data['shipping_method'] 		= WF_UPS_ID;
			$shipping_service_data['shipping_service'] 		= '';
			$shipping_service_data['shipping_service_name']	= '';
		}
		else {
			$shipping_service_data['shipping_method'] 		= $shipping_service_tmp_data[0];
			$shipping_service_data['shipping_service'] 		= $shipping_service_tmp_data[1];
			$shipping_service_data['shipping_service_name']	= $shipping_method['name'];	
		}
		
		return $shipping_service_data;
	}
	public function get_dimension_from_package($package){
		
		$dimensions	=	array(
			'Length'		=>	0,
			'Width'			=>	0,
			'Height'		=>	0,
			'Weight'		=>	0,
			'InsuredValue'	=>	0,
		);
		
		if(!isset($package['Package'])){
			return $dimensions;
		}
		if(isset($package['Package']['Dimensions'])){
			$dimensions['Length']	=	$package['Package']['Dimensions']['Length'];
			$dimensions['Width']	=	$package['Package']['Dimensions']['Width'];
			$dimensions['Height']	=	$package['Package']['Dimensions']['Height'];
		}
		
		$weight					=	$package['Package']['PackageWeight']['Weight'];
		
		if($package['Package']['PackageWeight']['UnitOfMeasurement']['Code']=='OZS'){
			if($this->weight_unit=='LBS'){ // make weight in pounds
				$weight	=	$weight/16;
			}else{
				$weight	=	$weight/35.274; // To KG
			}
		}
		//PackageServiceOptions
		if(isset($package['Package']['PackageServiceOptions']['InsuredValue'])){
			$dimensions['InsuredValue']	=	$package['Package']['PackageServiceOptions']['InsuredValue']['MonetaryValue'];
		}
		$dimensions['Weight']	=	$weight;
		return $dimensions;
	}
	
	public function manual_packages($packages){
		if(!isset($_GET['weight'])){
			return $packages;
		}
		
		$length_arr		=	json_decode(stripslashes(html_entity_decode($_GET["length"])));
		$width_arr		=	json_decode(stripslashes(html_entity_decode($_GET["width"])));
		$height_arr		=	json_decode(stripslashes(html_entity_decode($_GET["height"])));
		$weight_arr		=	json_decode(stripslashes(html_entity_decode($_GET["weight"])));		
		$insurance_arr	=	json_decode(stripslashes(html_entity_decode($_GET["insurance"])));
		$service_arr	=	json_decode(stripslashes(html_entity_decode($_GET["service"])));
		
		

		$no_of_package_entered	=	count($weight_arr);
		$no_of_packages			=	count($packages);
		
		// Populate extra packages, if entered manual values
		if($no_of_package_entered > $no_of_packages){ 
			$package_clone	=	current($packages); //get first package to clone default data
			for($i=$no_of_packages; $i<$no_of_package_entered; $i++){
				$packages[$i]	=	array(
					'Package'	=>	array(
						'PackagingType'	=>	array(
							'Code'	=>	'02',
							'Description'	=>	'Package/customer supplied',
						),
						'Description'	=>	'Rate',
						'PackageWeight'	=>	array(
							'UnitOfMeasurement'	=>	array(
								'Code'	=>	$package_clone['Package']['PackageWeight']['UnitOfMeasurement']['Code'],
							),
						),
					),
				);
			}
		}
		
		// Overridding package values
		foreach($packages as $key => $package){
			if(isset($length_arr[$key])){// If not available in GET then don't overwrite.
				$packages[$key]['Package']['Dimensions']['Length']	=	$length_arr[$key];
			}
			if(isset($width_arr[$key])){// If not available in GET then don't overwrite.
				$packages[$key]['Package']['Dimensions']['Width']	=	$width_arr[$key];
			}
			if(isset($height_arr[$key])){// If not available in GET then don't overwrite.
				$packages[$key]['Package']['Dimensions']['Height']	=	$height_arr[$key];
			}
			if(isset($weight_arr[$key])){// If not available in GET then don't overwrite.

				$weight	=	$weight_arr[$key];
				
				if(isset($service_arr[$key]) && $service_arr[$key]==92){// Surepost Less Than 1LBS
					$packages[$key]['Package']['PackageWeight']['UnitOfMeasurement']['Code']	=	'OZS';
				}
				
				if($packages[$key]['Package']['PackageWeight']['UnitOfMeasurement']['Code']=='OZS'){
					if($this->weight_unit=='LBS'){ // make sure weight from pounds to ounces
						$weight	=	$weight*16;
					}else{
						$weight	=	$weight*35.274; // From KG to ounces
					}
				}
				$packages[$key]['Package']['PackageWeight']['Weight']	=	$weight;
			}
			if(isset($insurance_arr[$key]) && $insurance_arr[$key]>0){// If not available in GET then don't overwrite.
				$packages[$key]['Package']['PackageServiceOptions']['InsuredValue']	=	array(
					'CurrencyCode'	=>	$this->wcsups->get_ups_currency(),
					'MonetaryValue'	=>	$insurance_arr[$key],
				);
			}
		}
		return $packages;
	}
	
	function split_shipment_by_services($ship_packages, $order){
		$shipments	=	array();
		if(!isset($_GET['service'])){
			$shipping_service_data				= $this->wf_get_shipping_service_data( $order );
			$default_service_type 				= $shipping_service_data['shipping_service'];
			
			$shipments[]	=	array(
				'shipping_service'	=>	$default_service_type,
				'packages'			=>	$ship_packages,
			);
		}else{
			//service
			$service_arr		=	json_decode(stripslashes(html_entity_decode($_GET["service"])));		
			
			foreach($service_arr as $count => $service_code){
				$shipment_arr[$service_code][]	=	$ship_packages[$count];
			}
			
			
			foreach($shipment_arr as $service_code => $packages){
				$shipments[]	=	array(
					'shipping_service'	=>	$service_code,
					'packages'			=>	$packages,
				);
			}
		}
		return $shipments;
	}
	
	function array2XML($obj, $array)
	{
		foreach ($array as $key => $value)
		{
			if(is_numeric($key))
				$key = 'item' . $key;

			if (is_array($value))
			{
				$node = $obj->addChild($key);
				$this->array2XML($node, $value);
			}
			else
			{
				$obj->addChild($key, htmlspecialchars($value));
			}
		}
	}
	
	// Bulk Label Printing
	
	function init_bulk_printing(){
		add_action('admin_footer', 	array($this, 'add_bulk_print_option'));
		add_action('load-edit.php',	array($this, 'perform_bulk_label_actions'));
		add_action('woocommerce_admin_order_actions_end', array($this, 'label_printing_buttons'));
	}
	
	function add_bulk_print_option(){
		global $post_type;
		
		if($post_type == 'shop_order') {
		?>
		<script type="text/javascript">
		  jQuery(document).ready(function() {
			jQuery('<option>').val('ups_generate_label').text('<?php _e('Generate UPS Label', 'ups-woocommerce-shipping');?>').appendTo("select[name='action']");
			jQuery('<option>').val('ups_generate_label').text('<?php _e('Generate UPS Label', 'ups-woocommerce-shipping');?>').appendTo("select[name='action2']");
			
			jQuery('<option>').val('ups_void_shipment').text('<?php _e('Void UPS Shipment', 'ups-woocommerce-shipping');?>').appendTo("select[name='action']");
			jQuery('<option>').val('ups_void_shipment').text('<?php _e('Void UPS Shipment', 'ups-woocommerce-shipping');?>').appendTo("select[name='action2']");
		  });
		</script>
		<?php
		}
	}
	
	function perform_bulk_label_actions(){
		$wp_list_table = _get_list_table('WP_Posts_List_Table');
		$action = $wp_list_table->current_action();
		
		if($action == 'ups_generate_label'){			
			if(isset($_REQUEST['post']) && is_array($_REQUEST['post'])){
				foreach($_REQUEST['post'] as $order_id){
					
					if($this->ups_confirm_shipment($order_id)){
						$this->ups_accept_shipment($order_id);
					}					
				}
			}
			else{
				wf_admin_notice::add_notice(__('Please select atleast one order', 'ups-woocommerce-shipping'));
			}
		}else if($action == 'ups_void_shipment'){
			if(isset($_REQUEST['post']) && is_array($_REQUEST['post'])){
				foreach($_REQUEST['post'] as $order_id){					
					$this->ups_void_shipment($order_id);				
				}
			}
			else{
				wf_admin_notice::add_notice(__('Please select atleast one order', 'ups-woocommerce-shipping'));
			}
		}
	}
	
	function ups_void_shipment($order_id){
		
		$ups_label_details_array	=	$this->get_order_label_details($order_id);
		if(!$ups_label_details_array){
			wf_admin_notice::add_notice('Order #'. $order_id.': Shipment is not available.');			
			return false;
		}
		
		// Load UPS Settings.
		$ups_settings 				= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
		$api_mode		      		= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
		$ups_user_id         		= isset( $ups_settings['user_id'] ) ? $ups_settings['user_id'] : '';
		$ups_password        		= isset( $ups_settings['password'] ) ? $ups_settings['password'] : '';
		$ups_access_key      		= isset( $ups_settings['access_key'] ) ? $ups_settings['access_key'] : '';
		$ups_shipper_number  		= isset( $ups_settings['shipper_number'] ) ? $ups_settings['shipper_number'] : '';
		
		if( "Live" == $api_mode ) {
			$endpoint = 'https://www.ups.com/ups.app/xml/Void';
		}
		else {
			$endpoint = 'https://wwwcie.ups.com/ups.app/xml/Void';
		}
		
		foreach($ups_label_details_array as $shipmentId => $ups_label_detail_arr){
			
			$xml_request = '<?xml version="1.0" ?>';
			$xml_request .= '<AccessRequest xml:lang="en-US">';
			$xml_request .= '<AccessLicenseNumber>'.$ups_access_key.'</AccessLicenseNumber>';
			$xml_request .= '<UserId>'.$ups_user_id.'</UserId>';
			$xml_request .= '<Password>'.$ups_password.'</Password>';
			$xml_request .= '</AccessRequest>';
			$xml_request .= '<?xml version="1.0" encoding="UTF-8" ?>';
			$xml_request .= '<VoidShipmentRequest>';
			$xml_request .= '<Request>';
			$xml_request .= '<TransactionReference>';
			$xml_request .= '<CustomerContext>'.$order_id.'</CustomerContext>';
			$xml_request .= '<XpciVersion>1.0001</XpciVersion>';
			$xml_request .= '</TransactionReference>';
			$xml_request .= '<RequestAction>Void</RequestAction>';
			$xml_request .= '<RequestOption />';
			$xml_request .= '</Request>';
			$xml_request .= '<ExpandedVoidShipment>';
			$xml_request .= '<ShipmentIdentificationNumber>'.$shipmentId.'</ShipmentIdentificationNumber>';
			foreach ( $ups_label_detail_arr as $ups_label_details ) {
				$xml_request .= '<TrackingNumber>'.$ups_label_details["TrackingNumber"].'</TrackingNumber>';
			}
			$xml_request .= '</ExpandedVoidShipment>';
			$xml_request .= '</VoidShipmentRequest>';
			
			$response = wp_remote_post( $endpoint,
				array(
					'timeout'   => 70,
					'sslverify' => $this->ssl_verify,
					'body'      => $xml_request
				)
			);
			
			// In case of any issues with remote post.
			if ( is_wp_error( $response ) ) {
				wf_admin_notice::add_notice('Order #'. $order_id.': Sorry. Something went wrong: '.$error_message);
				continue;
			}
			
			$response_obj 	= simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/','', $response['body'] ) . '</root>' );
			$response_code 	= (string)$response_obj->VoidShipmentResponse->Response->ResponseStatusCode;
			
			// It is an error response.
			if( '0' == $response_code ) {
				$error_code = (string)$response_obj->VoidShipmentResponse->Response->Error->ErrorCode;
				$error_desc = (string)$response_obj->VoidShipmentResponse->Response->Error->ErrorDescription;
				
				$message = '<strong>'.$error_desc.' [Error Code: '.$error_code.']'.'. </strong>';

				
				$void_shipment_url = admin_url( '/?wf_ups_void_shipment='.base64_encode( $order_id ).'&client_reset');
				$message .= 'Please contact UPS to void/cancel this shipment. <br/>';
				
				// For bulk void shipment we are clearing the data autometically
				
				$message .= 'If you have already cancelled this shipment by calling UPS customer care, and you would like to create shipment again then click <a class="button button-primary tips" href="'.$void_shipment_url.'" data-tip="Client Side Reset">Client Side Reset</a>';
				$message .= '<p style="color:red"><strong>Note: </strong>Previous shipment details and label will be removed from Order page.</p>';
				
				if( "Test" == $api_mode ) {
					$message .= "<strong>Also, noticed that you have enabled 'Test' mode.<br/>Please note that void is not possible in 'Test' mode, as there is no real shipment is created with UPS. </strong><br/>";
				}
				
				wf_admin_notice::add_notice('Order #'. $order_id.': '.$message);
				return false;
			}
			
			$this->wf_ups_void_return_shipment($order_id,$shipmentId);
		}
		
		delete_post_meta( $order_id, 'ups_created_shipments_details_array');
		delete_post_meta( $order_id, 'ups_label_details_array');
		delete_post_meta( $order_id, 'ups_commercial_invoice_details' );
		delete_post_meta( $order_id, 'wf_ups_selected_service');
		
		wf_admin_notice::add_notice('Order #'. $order_id.': Cancellation of shipment completed successfully. You can re-initiate shipment.','notice');
		return true;
	}
	
	function get_order_label_details($order_id){
		$ups_label_details_array	=	get_post_meta( $order_id, 'ups_label_details_array', true );
		if(!empty($ups_label_details_array) && is_array($ups_label_details_array)){
			return $ups_label_details_array;
		}
		return false;
	}
	
	function ups_confirm_shipment($order_id){
		
		// Check if shipment created already
		if($this->get_order_label_details($order_id)){
			wf_admin_notice::add_notice('Order #'. $order_id.': Shipment is already created.','warning');			
			return false;
		}
		
		
		// Load UPS Settings.
		$ups_settings 		= 	get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
		$api_mode      		= 	isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
		
		
		$endpoints			=	array(
			'Live'				=>	'https://www.ups.com/ups.app/xml/ShipConfirm',
			'Test'				=>	'https://wwwcie.ups.com/ups.app/xml/ShipConfirm',
		);
		
		$endpoint	=	$endpoints[$api_mode];
		$order		=	$this->wf_load_order( $order_id );
		$requests 	= 	$this->wf_ups_shipment_confirmrequest($order);
		
		$created_shipments_details_array = array();
		
		foreach($requests as $request){
			$xml_request = str_replace( array( "\n", "\r" ), '', $request );
			
			$response = wp_remote_post( $endpoint,
				array(
					'timeout'   => 70,
					'sslverify' => $this->ssl_verify,
					'body'      => $xml_request
				)
			);
			
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				wf_admin_notice::add_notice('Order #'. $order_id.': Sorry. Something went wrong: '.$error_message);			
				return false;
			}
			
			$response_obj = simplexml_load_string( $response['body'] );
			
			$response_code = (string)$response_obj->Response->ResponseStatusCode;
			if( '0' == $response_code ) {
				$error_code = (string)$response_obj->Response->Error->ErrorCode;
				$error_desc = (string)$response_obj->Response->Error->ErrorDescription;
				
				
				wf_admin_notice::add_notice('Order #'. $order_id.': '.$error_desc.' [Error Code: '.$error_code.']');
				return false;
			}
			
			$created_shipments_details = array();
			$shipment_id = (string)$response_obj->ShipmentIdentificationNumber;
			
			$created_shipments_details["ShipmentDigest"] 			= (string)$response_obj->ShipmentDigest;

			$created_shipments_details_array[$shipment_id] = $created_shipments_details;
		}		
		update_post_meta( $order_id, 'ups_created_shipments_details_array', $created_shipments_details_array );	
		return true;
	}
	
	function ups_accept_shipment($order_id){
		$created_shipments_details_array	= get_post_meta($order_id, 'ups_created_shipments_details_array', true);
		if(empty($created_shipments_details_array) && !is_array($created_shipments_details_array)){
			return false;
		}
		
		// Load UPS Settings.
		$ups_settings 				= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
		$api_mode      				= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
		$ups_user_id         		= isset( $ups_settings['user_id'] ) ? $ups_settings['user_id'] : '';
		$ups_password        		= isset( $ups_settings['password'] ) ? $ups_settings['password'] : '';
		$ups_access_key      		= isset( $ups_settings['access_key'] ) ? $ups_settings['access_key'] : '';
		$ups_shipper_number  		= isset( $ups_settings['shipper_number'] ) ? $ups_settings['shipper_number'] : '';
		$disble_shipment_tracking	= isset( $ups_settings['disble_shipment_tracking'] ) ? $ups_settings['disble_shipment_tracking'] : 'TrueForCustomer';
		$debug_mode      	        = isset( $ups_settings['debug'] ) && $ups_settings['debug'] == 'yes' ? true : false;
		
		
		$endpoints			=	array(
			'Live'				=>	'https://www.ups.com/ups.app/xml/ShipAccept',
			'Test'				=>	'https://wwwcie.ups.com/ups.app/xml/ShipAccept',
		);
		
		$endpoint	=	$endpoints[$api_mode];
		
		foreach($created_shipments_details_array as $shipment_id	=>	$created_shipments_details){			
			
			$xml_request = '<?xml version="1.0" encoding="UTF-8" ?>';
			$xml_request .= '<AccessRequest xml:lang="en-US">';
			$xml_request .= '<AccessLicenseNumber>'.$ups_access_key.'</AccessLicenseNumber>';
			$xml_request .= '<UserId>'.$ups_user_id.'</UserId>';
			$xml_request .= '<Password>'.$ups_password.'</Password>';
			$xml_request .= '</AccessRequest>'; 
			$xml_request .= '<?xml version="1.0" ?>';
			$xml_request .= '<ShipmentAcceptRequest>';
			$xml_request .= '<Request>';
			$xml_request .= '<TransactionReference>';
			$xml_request .= '<CustomerContext>'.$order_id.'</CustomerContext>';
			$xml_request .= '<XpciVersion>1.0001</XpciVersion>';
			$xml_request .= '</TransactionReference>';
			$xml_request .= '<RequestAction>ShipAccept</RequestAction>';
			$xml_request .= '</Request>';
			$xml_request .= '<ShipmentDigest>'.$created_shipments_details["ShipmentDigest"].'</ShipmentDigest>';
			$xml_request .= '</ShipmentAcceptRequest>';
			
			
			if( $debug_mode ) {
				echo 'SHIPMENT ACCEPT REQUEST: ';
				var_dump( $xml_request );   
			}
			
			$response = wp_remote_post( $endpoint,
				array(
					'timeout'   => 70,
					'sslverify' => $this->ssl_verify,
					'body'      => $xml_request
				)
			);
			
			if( $debug_mode ) {
				echo 'SHIPMENT ACCEPT RESPONSE: ';
				var_dump( $response );   
			}
			
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				wf_admin_notice::add_notice('Order #'. $order_id.': Sorry. Something went wrong: '.$error_message);
				return false;
			}

			$response_obj = simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/','', $response['body'] ) . '</root>' );	

			$response_code = (string)$response_obj->ShipmentAcceptResponse->Response->ResponseStatusCode;
			if('0' == $response_code) {
				$error_code = (string)$response_obj->ShipmentAcceptResponse->Response->Error->ErrorCode;
				$error_desc = (string)$response_obj->ShipmentAcceptResponse->Response->Error->ErrorDescription;
				
				wf_admin_notice::add_notice($error_desc.' [Error Code: '.$error_code.']');
				return false;
			}
			
			$package_results 			= $response_obj->ShipmentAcceptResponse->ShipmentResults->PackageResults;
			$ups_label_details			= array();
			$ups_label_details_array	= array();
			$shipment_id_cs 			= '';
			
			if(isset($response_obj->ShipmentAcceptResponse->ShipmentResults->Form->Image)){
				$international_forms[$shipment_id]	=	array(
					'ImageFormat'	=>	(string)$response_obj->ShipmentAcceptResponse->ShipmentResults->Form->Image->ImageFormat->Code,
					'GraphicImage'	=>	(string)$response_obj->ShipmentAcceptResponse->ShipmentResults->Form->Image->GraphicImage,
				);
			}
			// Labels for each package.
			foreach ( $package_results as $package_result ) {
				$ups_label_details["TrackingNumber"]		= (string)$package_result->TrackingNumber;
				$ups_label_details["Code"] 					= (string)$package_result->LabelImage->LabelImageFormat->Code;
				$ups_label_details["GraphicImage"] 			= (string)$package_result->LabelImage->GraphicImage;			
				$ups_label_details_array[$shipment_id][]	= $ups_label_details;
				$shipment_id_cs 							.= $ups_label_details["TrackingNumber"].',';
			}
			
			$shipment_id_cs = rtrim( $shipment_id_cs, ',' );

			if( empty($ups_label_details_array) ) {
				wf_admin_notice::add_notice('Order #'. $order_id.': Sorry, An unexpected error occurred.');
				return false;
			}
			else {
				update_post_meta( $order_id, 'ups_label_details_array', $ups_label_details_array );
				
				if(isset($international_forms)){
					update_post_meta( $order_id, 'ups_commercial_invoice_details', $international_forms );
				}
			}
			/*
			if( 'True' != $disble_shipment_tracking) {
				wp_redirect( admin_url( '/post.php?post='.$post_id.'&wf_ups_track_shipment='.$shipment_id_cs ) );
				exit;
			}*/
			wf_admin_notice::add_notice('Order #'. $order_id.': Shipment accepted successfully. Labels are ready for printing.','notice');
			
		}
		return true;
	}
	
	function get_order_label_links($order_id){
		$links	=	array();
		$created_shipments_details_array 	= get_post_meta( $order_id, 'ups_created_shipments_details_array', true );
		if(!empty($created_shipments_details_array)){
			$ups_label_details_array = $this->get_order_label_details($order_id);
			$ups_commercial_invoice_details = get_post_meta( $order_id, 'ups_commercial_invoice_details', true );
			
			foreach($created_shipments_details_array as $shipmentId => $created_shipments_details){
				$index = 0;
				foreach ( $ups_label_details_array[$shipmentId] as $ups_label_details ) {
					$label_extn_code 	= $ups_label_details["Code"];
					$tracking_number 	= isset( $ups_label_details["TrackingNumber"] ) ? $ups_label_details["TrackingNumber"] : '';
					$links[] 			= admin_url( '/?wf_ups_print_label='.base64_encode( $shipmentId.'|'.$order_id.'|'.$label_extn_code.'|'.$index.'|'.$tracking_number ) );
					
					
					// Return Label Link
					if(isset($created_shipments_details['return'])&&!empty($created_shipments_details['return'])){
						$return_shipment_id=current(array_keys($created_shipments_details['return'])); // only one return label is considered now
						$ups_return_label_details_array = get_post_meta( $order_id, 'ups_return_label_details_array', true );
						if(is_array($ups_return_label_details_array)&&isset($ups_return_label_details_array[$return_shipment_id])){// check for return label accepted data
							$ups_return_label_details=$ups_return_label_details_array[$return_shipment_id];
							if(is_array($ups_return_label_details)){
								$ups_return_label_detail=current($ups_return_label_details);
								$label_index=0;// as we took only one label so index is zero
								$links[] = admin_url( '/?wf_ups_print_label='.base64_encode( $return_shipment_id.'|'.$order_id.'|'.$label_extn_code.'|'.$label_index.'|return' ) );
								
							}
						}
					}
					$index = $index + 1;
				}
				
				if(isset($ups_commercial_invoice_details[$shipmentId])){
					$links[]	=	admin_url( '/?wf_ups_print_commercial_invoice='.base64_encode($order_id.'|'.$shipmentId));
				}
			}
		}
		return $links;
	}
	
	function label_printing_buttons($order){
		$actions	=	array();
		$labels	=	$this->get_order_label_links($order->id);
		if(is_array($labels)){
			foreach($labels as $label_no => $label_link){
				$actions['print_label'.$label_no]	=	array(
					'url'	=>	$label_link,
					'name'	=>	__('Print Label', 'ups-woocommerce-shipping'),
					'action'=>	'wf-print-label'
				);
			}
		}
		
		foreach ( $actions as $action ) {
			printf( '<a class="button tips %s" href="%s" data-tip="%s" target="_blank">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
		}
		
	}
}
new WF_Shipping_UPS_Admin();
