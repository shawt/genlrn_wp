<?php

if(!class_exists('wf_ups_accesspoint_locator')){
	
	class wf_ups_accesspoint_locator{
		
		public function __construct(){
			
			$this->settings 			= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null );
			$this->accesspoint_locator 			= (isset($this->settings[ 'accesspoint_locator']) && $this->settings[ 'accesspoint_locator']=='yes') ? true : false;
			$this->api_mode      				= isset( $this->settings['api_mode'] ) ? $this->settings['api_mode'] : 'Test';
			
			if( "Live" == $this->api_mode ) {
				$this->endpoint = 'https://www.ups.com/ups.app/xml/Locator';
			}
			else {
				$this->endpoint = 'https://wwwcie.ups.com/ups.app/xml/Locator';
			}
				
			$this->user_id         		= isset( $this->settings['user_id'] ) ? $this->settings['user_id'] : '';
			$this->password        		= isset( $this->settings['password'] ) ? str_replace( '&', '&amp;',$this->settings['password']) : '';
			$this->access_key      		= isset( $this->settings['access_key'] ) ? $this->settings['access_key'] : '';
			
			if($this->accesspoint_locator){
				$this->init();
			}
		}
		
		private function init(){
			//add accesspoint select field in checkout page
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'wf_ups_add_accesspoint_to_checkout_fields') );
            
			add_filter( 'woocommerce_order_formatted_billing_address', array($this,'wf_ups_order_formatted_billing_address'),10,3 );
			add_filter( 'woocommerce_order_formatted_shipping_address', array($this,'wf_ups_order_formatted_shipping_address'),10,3 );
			
			add_filter( 'woocommerce_formatted_address_replacements',  array($this,'wf_ups_formatted_address_replacements'),10,3  );
			add_filter('woocommerce_localisation_address_formats', array($this,'wf_ups_address_formats'));
			
			//Display access point in my-account/address
			add_filter( 'woocommerce_my_account_my_address_formatted_address',array($this,'wf_ups_my_account_formated_address'),10,3 );
			
			// Giving options to access point select box while calling ajax
			add_filter( 'woocommerce_update_order_review_fragments', array($this,'update_access_point_select_options'), 90, 1);
			//Updating Selected accesspoint value
			add_action( 'woocommerce_checkout_update_order_review', array($this,'wf_ups_update_accesspoint'), 10, 1 );
			
		}
		
		public function wf_ups_add_accesspoint_to_checkout_fields( $fields ) {
				
			$fields['shipping']['shipping_accesspoint'] = array(
				
				'label'       => __('Access Point Locator', 'ups-woocommerce-shipping'),
				'placeholder' => _x('', 'placeholder', 'ups-woocommerce-shipping'),
				'required'    => false,
				'clear'       => false,
				'type'        => 'select',
				'class' 	  => array ('address-field', 'update_totals_on_change' ),
				'options'     => array(
					'' => __('Select Accesspoint Locator', 'ups-woocommerce-shipping' )
					)
				
			);
			
			return $fields;
		}
		
		public function wf_ups_order_formatted_billing_address( $array,$address_fields ) { 
				$array['accesspoint'] = '';
				return $array; 
		}
		
		public function wf_ups_order_formatted_shipping_address( $array,$address_fields ) { 
		
				$decoded_order_formatted_accesspoint	=	( isset($address_fields->shipping_accesspoint) ) ? json_decode($address_fields->shipping_accesspoint) : '';
				$order_shipping_accesspoint = (isset($decoded_order_formatted_accesspoint->AddressKeyFormat->ConsigneeName)) ? $decoded_order_formatted_accesspoint->AddressKeyFormat->ConsigneeName : '';
				$array['accesspoint'] = $order_shipping_accesspoint; 
				return $array; 
		}
		
		public function wf_ups_my_account_formated_address($array,$customer_id,$name  ) { 
				$getting_accesspoint = get_user_meta( $customer_id, $name . '_accesspoint', true );
				$decoded_my_account_accesspoint	=	( isset($getting_accesspoint) ) ? json_decode($getting_accesspoint) : '';
				$my_account_shipping_accesspoint	=	(isset($decoded_my_account_accesspoint->AddressKeyFormat->ConsigneeName)) ? $decoded_my_account_accesspoint->AddressKeyFormat->ConsigneeName : '';
				
				$array['accesspoint'] = ($name . '_accesspoint' == 'shipping_accesspoint') ? $my_account_shipping_accesspoint :'';
				return $array; 
		}
		
		public function wf_ups_formatted_address_replacements( $array, $accesspoint_locator ) {
			
			$accesspoint_tag = ($accesspoint_locator['accesspoint']!= '') ? 'Accesspoint Locator:  '.$accesspoint_locator['accesspoint'] :'';
			$array['{accesspoint}'] = $accesspoint_tag;
			return $array; 
		}
		
		public function wf_ups_address_formats( $formats ) {
			foreach ($formats as $key => $format) {
				$formats[$key]=$format."\n{accesspoint}";
			}		
			return $formats;
		}
		
		public function update_access_point_select_options($array){			
			
			$shipping_address = WC()->customer->get_shipping_address();
			$shipping_city = WC()->customer->get_shipping_city();
			$shipping_postalcode = WC()->customer->get_shipping_postcode();
			$shipping_state = WC()->customer->get_shipping_state();
			$shipping_country = WC()->customer->get_shipping_country();
			
			$xmlRequest = '<?xml version="1.0"?>
			<AccessRequest xml:lang="en-US">
				<AccessLicenseNumber>'.$this->access_key.'</AccessLicenseNumber>
				<UserId>'.$this->user_id.'</UserId>
				<Password>'.$this->password.'</Password>
			</AccessRequest>
			<?xml version="1.0"?>
			<LocatorRequest>
				<Request>
					<RequestAction>Locator</RequestAction>
					<RequestOption>1</RequestOption>
				</Request>
				<OriginAddress>
					<PhoneNumber>1234567891</PhoneNumber>
					<AddressKeyFormat>
						<ConsigneeName>yes</ConsigneeName>
						<AddressLine>'.$shipping_address.'</AddressLine>
						<PoliticalDivision2>'.$shipping_city.'</PoliticalDivision2>
						<PoliticalDivision1>'.$shipping_state.'</PoliticalDivision1>
						<PostcodePrimaryLow>'.$shipping_postalcode.'</PostcodePrimaryLow>
						<CountryCode>'.$shipping_country.'</CountryCode>
					</AddressKeyFormat>
				</OriginAddress>
				<Translate>
					<Locale>en_US</Locale>
				</Translate>
				<UnitOfMeasurement>
					<Code>MI</Code>
				</UnitOfMeasurement>
				<LocationSearchCriteria>
					<SearchOption>
						<OptionType>
							<Code>01</Code>
						</OptionType>
						<OptionCode>
							<Code>018</Code>
						</OptionCode>
					</SearchOption>
					<MaximumListSize>6</MaximumListSize>
					<SearchRadius>50</SearchRadius>
				</LocationSearchCriteria>
			</LocatorRequest>';
			
			try{
				
				$response = wp_remote_post( $this->endpoint,
					array(
						'timeout'   => 70,
						'sslverify' => 0,
						'body'      => $xmlRequest
					)
				);
			}catch(Exception $e){
				// do nothing
			}
			
			$locators = array();
			$full_address = array();
			$drop_locations = array();
			
			$xml = simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/','', $response['body'] ) . '</root>' );
			if(isset($xml->LocatorResponse->SearchResults->DropLocation)){
				$drop_locations = ($xml->LocatorResponse->SearchResults->DropLocation);
			}
		
			if(!empty($drop_locations)){
				foreach($drop_locations as $drop_location){
					$locator_consignee_name	=	(string)$drop_location->AddressKeyFormat->ConsigneeName;
					$drop_location_data						=	new stdClass();
					$drop_location_data->LocationID			=	$drop_location->LocationID;
					$drop_location_data->AddressKeyFormat	=	$drop_location->AddressKeyFormat;
					$locator_full_address[$locator_consignee_name] = json_encode($drop_location_data);
					$locators[] = $locator_consignee_name;
				}
			}		
		
			//$this->debug("Location API: xmlRequest_RRRRcountry:<pre>".print_r( htmlspecialchars( $xmlRequest ), true )."</pre>");
			//$this->debug("Location API: response:<pre>".print_r( htmlspecialchars($response['body']), true)."</pre>");
		
			$locator='<select id="shipping_accesspoint" name="shipping_accesspoint" class="select">';
			$locator .=	"<option value=''>Select Accesspoint Locator</option>";
			
			if(!empty($locators)){
				foreach ($locators as $access_point_locator){
					
					$updated_accesspoint = WC()->customer->__get('shipping_accesspoint');
					$decoded_selected_accesspoint = (isset($updated_accesspoint)) ? json_decode($updated_accesspoint) : '';
					$selected_accesspoint_locator = (isset($decoded_selected_accesspoint->AddressKeyFormat->ConsigneeName)) ?
						  $decoded_selected_accesspoint->AddressKeyFormat->ConsigneeName : '';
					
					if($selected_accesspoint_locator == $access_point_locator){
						$locator .= "<option selected='selected' value='" . $locator_full_address[$access_point_locator] . "'>" .$access_point_locator ."</option>";
					}
					else{
						$locator .= "<option value='" . $locator_full_address[$access_point_locator] . "'>" .$access_point_locator ."</option>";
					}
					
				}
			}
			
			$locator .=	'</select>';
			$array['#shipping_accesspoint'] = $locator;
			return $array;
		}
		
		public function wf_ups_update_accesspoint($updated_data){
			$key = 'shipping_accesspoint';
			$selected_accesspoint = '';
			
			$updated_fields = explode("&",$updated_data);
			if(is_array($updated_fields)){
				foreach($updated_fields as $updated_field){
					
					$updated_field_values = explode('=',$updated_field);
					if(is_array($updated_field_values)){
						if(in_array('shipping_accesspoint',$updated_field_values)){
								$selected_accesspoint = urldecode($updated_field_values[1]);	
						}
					}
				}
			}
			WC()->customer->__set( $key, $selected_accesspoint );
			
		}
		
	}
	
	new wf_ups_accesspoint_locator();
}