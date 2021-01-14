<?php

function pdf_formet_setting(){
	$non_historical_settings = apply_filters( 'wpo_wcpdf_non_historical_settings', array(
		'enabled',
		'attach_to_email_ids',
		'disable_for_statuses',
		'number_format', // this is stored in the number data already!
		'my_account_buttons',
		'my_account_restrict',
		'invoice_number_column',
		'paper_size',
		'font_subsetting',
	) );
	if ( in_array( $key, $non_historical_settings ) && isset($this->latest_settings) ) {
		$setting = isset( $this->latest_settings[$key] ) ? $this->latest_settings[$key] : $default;
	} else {
		$setting = isset( $this->settings[$key] ) ? $this->settings[$key] : $default;
	}
	return $setting;
}

function fre_invoice_get_pdf(){

	$document = wcpdf_get_bulk_document( 'test','11' );
	$html = 'abc test html';
	$pdf_settings = array(
		'paper_size'		=> 'A4',
		'paper_orientation'	=> 'portrait',
		'font_subsetting'	=>  'font_subsetting',
	);
	$pdf_maker 	= wcpdf_get_pdf_maker( $html, $pdf_settings );

	$pdf 		=  $pdf_maker->output();
	return $pdf;


}

function fre_pdf_create(){
	// $pdf_data = fre_invoice_get_pdf();

	$document = wcpdf_get_bulk_document( 'invoice','11' );
	$filename = $document->get_filename();
	$tmp_path = $this->get_tmp_path('attachments');
	$pdf_path = $tmp_path . $filename;


	// file_put_contents ( $pdf_path, $pdf_data, LOCK_EX );

	// file_put_contents ( $pdf_path, $pdf_data );

}
function fre_pdf_debug(){
	fre_pdf_create();

}
add_action('wp_footer','fre_pdf_debug');