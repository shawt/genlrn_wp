<?php

$acive_template = get_option('wf_invoice_active_key');
$main_data = get_option($acive_template);
if (get_option($acive_template . 'value')) {
    $main_data_value = get_option($acive_template . 'value');
} else {
    $main_data_value = get_option('wf_invoice_active_value');
}

$main_data_array = explode('|', $main_data_value);

$payment_method = (WC()->version < '2.7.0') ? $order->payment_method_title : $order->get_payment_method_title();

$order_number = (WC()->version < '2.7.0') ? $order->id : $order->get_id();

$order_subtotal = $order->get_subtotal_to_display();

$items = $order->get_items();
$cell_height = sizeof($items)*60;

$line_items = '';
foreach ( $items as $item ) {
    
    $product_name = $item['name'];
    $qty = $item['qty'];
    $price = wc_format_decimal($order->get_item_subtotal($item), 2);
    $total = wc_format_decimal($order->get_line_total($item), 2);


    $line_items .='<tr style="background-color:#fff; height:60px;">
                            <td style="width:160px;" >'.$product_name.'</td>
                            <td style="width:160px;" >'.$product_name.'</td>
                            <td style="width:90px;" >'.$qty.'</td>
                            <td style="width:90px;" >'.$price.'</td>
                            <td style="width:130px;" >'.$total.'</td>
                        </tr>';
}







$order_line_items = '';


$Shipping = '';
if (get_option('woocommerce_calc_shipping') === 'yes') {


    $Shippingdetials = $order->get_items('shipping');
    if (!empty($Shippingdetials)) {
        foreach ($Shippingdetials as $key) {
            $Shipping = get_woocommerce_currency_symbol() . ' ' . $key['cost'] . ' via ' . $key['name'];
        }
    } else {
        $Shipping = $order->get_shipping_to_display();
    }
}
$tax_total = wc_price($order->get_total_tax());

$total_price_final = '';
$refund_amount = 0;

if (wc_price((WC()->version < '2.7.0') ? $order->order_total : get_post_meta($order_id, '_order_total', true))) {
    $total_price_final = (WC()->version < '2.7.0') ? $order->order_total : get_post_meta($order_id, '_order_total', true);


    $refund_data_array = $order->get_refunds();
    if (!empty($refund_data_array)) {
        foreach ($refund_data_array as $refund) {
            $refund_id = (WC()->version < '2.7.0') ? $refund->id : $refund->get_id();
            $total_price_final += get_post_meta($refund_id, '_order_total', true);
            $refund_amount -=(int) get_post_meta($refund_id, '_order_total', true);
        }
    }
}
$this->wf_generate_invoice_for_tax = apply_filters('wf_invoice_set_default_tax_type', array('ex_tax'));
if (!empty($refund_amount) && $refund_amount != 0) {

    $data = in_array('in_tax', $this->wf_generate_invoice_for_tax) && !empty($sub_loop_data) ? ' (incl. tax  ' . wc_price($sub_loop_data) . ')' : '';
    $_order_total_string = '<strike>' . wc_price((WC()->version < '2.7.0') ? $order->order_total : get_post_meta($order_id, '_order_total', true)) . '</strike> ' . wc_price($total_price_final) . $data . '  ( Refund -' . wc_price($refund_amount) . ' )';
} else {

    $data = in_array('in_tax', $this->wf_generate_invoice_for_tax) && !empty($sub_loop_data) ? ' (incl. tax  ' . wc_price($sub_loop_data) . ')' : '';
    $_order_total_string = wc_price((WC()->version < '2.7.0') ? $order->order_total : get_post_meta($order_id, '_order_total', true)) . $data;
}




$billing_address = $this->get_billing_address($order);

$billing_data_address = $billing_address['first_name'] . ' ' . $billing_address['last_name'] . '<br>';
if ($billing_address['company'] != '') {
    $billing_data_address .= $billing_address['company'] . '<br>';
}
$billing_data_address .= $billing_address['address_1'] . '<br>';
if ($billing_address['address_2'] != '') {
    $billing_data_address .= $billing_address['address_2'] . '<br>';
}

$billing_address['city'] = empty($billing_address['city']) ? ' ' : $billing_address['city']. ', ';
$billing_data_address .= $billing_address['city'] . ' ' . $billing_address['state'] . ' ' . $billing_address['postcode'] . '<br>';
$billing_data_address .= $billing_address['country'] . '<br>';


$shipping_address = $this->get_shipping_address($order);
$shipping_address_data = $shipping_address['first_name'] . ' ' . $shipping_address['last_name'] . '<br>';
if ($shipping_address['company'] != '') {
    $shipping_address_data .= $shipping_address['company'] . '<br>';
}
$shipping_address_data .= $shipping_address['address_1'] . '<br>';
if ($shipping_address['address_2'] != '') {
    $shipping_address_data .= $shipping_address['address_2'] . '<br>';
}

$shipping_address['city'] = empty($shipping_address['city']) ? ' ' : $shipping_address['city']. ', ';
$shipping_address_data .= $shipping_address['city'] . ' ' . $shipping_address['state'] . ' ' . $shipping_address['postcode'] . '<br>';
$shipping_address_data .= $shipping_address['country'] . '<br>';


$invoice_created_date = date($main_data_array[10], strtotime('now'));

$ship_from_address = $this->wf_shipment_label_get_from_address();
$from_address_data = '';
$j = 0;
foreach ($ship_from_address as $key => $value) {
    if (!empty($value)) {
        $from_address_data .= $value . ' ';
    }
    if ($j == 2)
        $from_address_data.=',<br/>';
    $j++;
}

$invoice_number = $this->wf_generate_invoice_number($order);

$logo = ($main_data_array[2] !== "no")? '<img src="' . $this->wf_packinglist_get_logo($action) . '" alt="logo"/>' : '';
$invoice_num = ($main_data_array[4] !== 'no') ? $invoice_number : '';

if($main_data_array[9] === 'no'  )
	{$main_data 		= str_replace('[invoice date show hide]','display:none !important;', $main_data);}
else
	{$main_data 		= str_replace('[invoice date show hide]','', $main_data);}


$wf_invoice_pdf_template = '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Invoice</title>
<style>
            body {  
                font-family: \'Oxygen-Regular\'  
            }
            @page {
                size: 670pt 670pt; margin: 20px;
                margin: 5px 10px 10px 10px;
            }
            body {
                height: 100%;
                margin: 5px 10px 10px 10px;
                background-size: 100% 100%;
                background-repeat: no-repeat;
            }
.left{float:left !important;}
.right{float:right !important;}
.clr{ clear:both;}
.Invoice .wrapper{ width:900px; margin:5 auto;}
.Invoice header, .Invoice footer{ float:left; width:100%;}
.full-row{ clear:both; width:100%;}
.Invoice header .left, .Invoice header .right{ float:left; width:50%; text-align:left; padding-left:50px;}
.logo{ padding-bottom:30px;height:100px;}
.Invoice header .right{ width:205px;}
.Invoice header .right.invoice-date{ width:275px;height:90px; border:#CCCCCC 0px solid; background-color:#F2FAFD; padding:20px; margin-right:50px;}
.Invoice header .left p{ float:left; width:100%; text-align:left; font-size:20px; line-height:30px; color:#252525;}
.bar-code{ float:right; display:block; text-align:right; padding-bottom:20px;}
.Invoice header .right table{  float:right; clear:both;}
.Invoice header .right table td{ font-size:18px; color:#242424;}

.address-full{ float:left; width:835px; padding:0 50px;}
.address-full .halfwidth{ float:left; width:400px; padding-right:30px;}
.address-full .halfwidth h3{ color:#fff; font-size:18px; padding-bottom:10px;}
.address-full .halfwidth p{ font-size:18px; line-height:30px; color:#fff;}
.address-right{ float:right !important; width:400px !important;}

.moneyback{ float:right; background:#F0F8F6; width:301px; padding:45px;}
.moneyback p{ float:left; width:100%; font-size:18px; color:#000; text-align:center; line-height:40px;}
.moneyback p span{ color:#00aeef; font-size:40px; text-align:center;}

.product-summary { float:left; width:100%; padding:30px 0 0 0;}
.product-summary table{ width:100%; border:none !important;}
.product-summary table th{ text-align:left; background:#66BDA9; color:#fff; padding-left:50px; padding-top:20px; padding-bottom:20px; font-size:20px; font-weight:400;}
.product-summary table td{ padding-left:50px; padding-top:20px; padding-bottom:20px; color:#363636;}
.product-summary table tr{ background-color:#fff;}
.product-summary table tr:nth-child(even) {background-color: #F9F9F9;}
.product-summary table tr:last-child{ border-bottom:#CCCCCC 1px solid !important;}

.payment-summary{ float:left; width:100%; border-top:#ccc 1px solid; padding-top:35px;}
.payment-summary .left{ float:left; width:350px; padding-left:50px;}
.payment-summary .left h4{ font-size:25px; line-height:30px; color:#363636; font-weight:700; width:100%; float:left;}
.payment-summary .left p{ font-size:20px; line-height:30px; color:#888888; font-weight:400; width:100%; float:left;}
.payment-summary .right{ float:right; width:252px;}
.payment-summary .right table{ float:right !important;}
.payment-summary .right table td{ font-size:18px; line-height:30px; padding-left:50px; line-height:40px;}
tr.total-amount{ background:#CC5A6A; margin-top:10px; color:#fff;}
tr.total-amount td{ padding:10px 0;}
.wf-amount{ float:left; width:100%; color:#363636; font-size:18px; padding:10px; text-align:center;}
.payment-summary .right p.note{ float:left; width:100%; color:#888; font-size:16px; padding-top:15px; padding-bottom:15px; text-align:center;}
.terms{ float:left; width:100%; padding-left:50px;}
.terms h3{ float:left; width:100%; text-align:left; font-size:20px; font-weight:400; padding-bottom:10px;}
.terms ul{ float:left; width:100%; margin:0; padding:0; padding-left:20px;}
.terms li{ float:left; width:100%; list-style:disc !important; color:#888; font-size:16px; line-height:30px;}
.Invoice footer p{ float:left; width:100%; border-top:#E5E5E5 1px solid; line-height:90px; text-align:center; color:#888;}
</style>
</head>
<body class="Invoice">
    <header style="height:140px;">
        <div class="wrapper" style="height:140px;">
            <div class="left" style="width:70%;float: left;">
                <div class="full-row logo"><a href="#">'.$logo.'</a></div><!--full-row-->
            </div> 
            <div class="company-info" style="width:30%;float: right;color: #66BDA9;line-height: 14px;">
                <div class="address" id="wf_from_address_filed" style="text-align:left !important;"> 
                    <p>'.$from_address_data.'</p>
                </div>        
            </div>
        </div>     
    </header>
    <div class="clr"></div>
 

<section style="height:50px; background-color: #CC5A6A;"> 
    <div style="padding: 0 10px; height:50px;">
      <div class="details" style="background-color: #CC5A6A;color: #ffffff;line-height: 18px;">
        <div class="client" style="float:left;width: 50%;">
          <p class="name" id="wf_invoice_name" style="font-size:21px !important;"><label id="wf_invoice_label" style="font-weight:bold !important;">INVOICE:</label> ' . $invoice_num . '</p>
        </div>
        <div class="client" style="float:left;width: 50%;">
          <p class="name" id="wf_order_number" style="float:right"><label id="wf_order_number_label" style="">Order No</label>:  '.$order_number.'</p>
        </div>
      </div>
    </div>
</section>
 <div class="clr"></div>
 <br/>
    <section style="height:'.($cell_height+300).'px;" >
    	<div class="" style="margin-bottom: 25px;line-height: 20px;">
        	<div class="address-full" style="height:200px;margin-bottom: 25px;padding: 10px 10px;background-color: #CC5A6A;color: #ffffff;line-height: 18px;">
            	<div class="halfwidth"><h3>Billing address:</h3>'.$billing_data_address.'</div>
                <div class="halfwidth address-right"><h3>Billing address:</h3>'.$shipping_address_data.'</div>
            </div>
            <div class="clr"></div>
            <div class="product-summary" style="height:'.$cell_height.'px; padding-right:10px;">
            	<table width="100%" border="0" cellspacing="0" cellpadding="13" style="height:25px;margin-right:50px;">
                	<thead>
                    	<th>Product</th>
                        <th width="160px;">Title</th>
                        <th width="90px">Qty</th>
                        <th width="90px">Price($)</th>
                        <th width="130px">Total($)</th>
                	</thead>'.$line_items.'
                  <div class="clr"></div>
                </table>
            </div><!--product-summary-->
            <div class="clr"></div>
            <div class="payment-summary" style="height:130px; padding-right:10px;">
                <div class="right">
                	<table width="auto" border="0" cellspacing="0" cellpadding="0" style="padding-right:50px;">
                      <tr>
                        <td width="150px;">Sub Total</td>
                        <td width="20px" valign="middle" align="left">:</td>
                        <td width="190px" align="left">' . $order_subtotal . '</td>
                      </tr>
                      <tr>
                        <td>Tax</td>
                        <td width="20px" valign="middle" align="left">:</td>
                        <td align="left">' . $tax_total . '</td>
                      </tr>
                      <br/>
                      <tr class="total-amount">
                        <td>Total Amount</td>
                        <td width="20px" valign="middle" align="left">:</td>
                        <td align="left">' . $_order_total_string . '</td>
                      </tr>
                    </table>
                </div><!--right-->
            </div><!--payment-summary-->
            <div class="clr"></div>
        </div><!--wrapper-->
    </section>
</body>
</html>';
return $wf_invoice_pdf_template;
