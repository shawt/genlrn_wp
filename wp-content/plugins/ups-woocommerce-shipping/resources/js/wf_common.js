jQuery(document).ready(function(){
	
	// Toggle Packing Methods
	wf_load_packing_method_options();
	jQuery('.packing_method').change(function(){
		wf_load_packing_method_options();
	});
	
	// Advance settings tab
	jQuery('.wf_settings_heading_tab').next('table').hide();
	jQuery('.wf_settings_heading_tab').click(function(){
		jQuery(this).next('table').toggle();
	});
});

function wf_load_packing_method_options(){
	pack_method	=	jQuery('.packing_method').val();
	jQuery('#packing_options').hide();
	jQuery('.weight_based_option').closest('tr').hide();
	switch(pack_method){
		
		case 'box_packing':
			jQuery('#packing_options').show();
			break;
			
		case 'weight_based':
			jQuery('.weight_based_option').closest('tr').show();
			break;
			
		case 'per_item':
		
		default:
			break;
			
		
	}
}