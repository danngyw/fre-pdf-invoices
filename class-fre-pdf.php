<?php

namespace WPO\WC\PDF_Invoices\Documents;

use WPO\WC\PDF_Invoices\Compatibility\WC_Core as WCX;
use WPO\WC\PDF_Invoices\Compatibility\Order as WCX_Order;
use WPO\WC\PDF_Invoices\Compatibility\Product as WCX_Product;
use WPO\WC\PDF_Invoices\Compatibility\WC_DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

Class Fre_Document extends Bulk_Document{
	public $order_id;
	public $payer;
	function __construct($document_type, $order_ids, $payer){
		$this->order_id = $order_ids;
		$this->payer = $payer;
	}

	public function get_html() {

		$html_content = array();

		do_action( 'wpo_wcpdf_after_html', $this->get_type(), $this );

		// $order      = new AE_Order( $this->order_id );

		// $order_data = $order->get_order_data();

		$html ="test 123 <br /> test 123 <br /> test 123 <br /> test 123 <br />";
		//$html.="Price: ".$order_data['total']."(".$order_data['currency'].")";
		$html = fre_pdf_get_html($this->order_id, $this->payer);


		return $html;
	}
}