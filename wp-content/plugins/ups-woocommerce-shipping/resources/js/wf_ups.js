jQuery(document).ready(function(){
	// Toggle pickup options
	wf_ups_load_pickup_options();
	jQuery('#woocommerce_wf_shipping_ups_pickup_enabled').click(function(){
		wf_ups_load_pickup_options();
	});
});

function wf_ups_load_pickup_options(){
	var checked	=	jQuery('#woocommerce_wf_shipping_ups_pickup_enabled').is(":checked");
	if(checked){
		jQuery('.wf_ups_pickup_grp').closest('tr').show();
	}else{
		jQuery('.wf_ups_pickup_grp').closest('tr').hide();
	}
}