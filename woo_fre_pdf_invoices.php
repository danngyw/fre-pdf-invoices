<?php
/**
Plugin Name: Fre PDF Invoices
Plugin URI: http://enginethemes.com/
Description: Integrates the Stripe payment gateway to your Directory, Freelance site
Version: 1.0
Author: enginethemes
Author URI: http://enginethemes.com/
License: GPLv2
Text Domain: enginetheme
*/
define( 'FRE_PDF_PATH', plugin_dir_path( __FILE__ )  );
function pdf_includes_files(){
	require_once(FRE_PDF_PATH.'/functions.php');
	require_once(FRE_PDF_PATH.'/class-fre-pdf.php');
}
add_action('after_setup_theme','pdf_includes_files');

function debug_pdf_invoice(){
	$order_id 	= 289 ;
	$order     	= new AE_Order( $order_id );
  	$order_pay 	= $order->get_order_data();

  	$order_date = $order_pay['created_date']; //2021-01-14 14:14:17
  	$product = array_pop( $order_pay['products'] );


  	$sku 		= $product['ID'];
  	$pack_name 	= $product['NAME'];
  	$type 		= $product['TYPE']; //fre_credit_plan
  	echo '<pre>';
  	var_dump($order_pay);
  	echo '</pre>';

  	global $user_ID;
	$order_id = 258;
	$user = get_userdata($user_ID);
	// $attachments = fre_pdf_get_file($order_id, $user);

}
add_action('wp_footer','debug_pdf_invoice');

function fre_send_mail_vs_attachment_debug(){




	// $oder_id 		= 239;
	// $attachments 	= array();
	// $attachments 	= fre_pdf_get_file($oder_id);

	// $attachments = array( WP_CONTENT_DIR . '/uploads/wpo_wcpdf/invoiceinvoice-239.pdf' );
	// $attachments = array($pdf_file[0] );
	//$t = wp_mail('danhoat@gmail.com', 'Mail Subject For Test PDF Attach File '. time(),  'Test Content', $header = '', $attachments);
}
add_action('wp_footer','fre_send_mail_vs_attachment_debug');

function fre_attach_pdf_to_email($attachments, $attachments_check){
	$order_id 		= isset($attachments_check['orders']) ? $attachments_check['orders'] : 0;
	$payer 			=  isset($attachments_check['payer']) ? $attachments_check['payer'] : 0;
	if( $order_id ){
		$attachments 	= fre_pdf_get_file($order_id, $payer);
		return $attachments;
	}
	return $attachments;
}
add_filter( 'fre_email_attachments',  'fre_attach_pdf_to_email' , 99, 2 );
