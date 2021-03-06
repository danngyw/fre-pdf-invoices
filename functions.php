<?php

function file_is_locked( $fp ) {
		if (!flock($fp, LOCK_EX|LOCK_NB, $wouldblock)) {
			if ($wouldblock) {
				return true; // file is locked
			} else {
				return true; // can't lock for whatever reason (could be locked in Windows + PHP5.3)
			}
		} else {
			flock($fp,LOCK_UN); // release lock
			return false; // not locked
		}
	}

function fre_wait_for_file_lock( $path ) {
	$fp = fopen($path, 'r+');
	if ( $locked = file_is_locked( $fp ) ) {
		// optional delay (ms) to double check if the write process is finished
		$delay = intval( apply_filters( 'wpo_wcpdf_attachment_locked_file_delay', 250 ) );
		if ( $delay > 0 ) {
			usleep( $delay * 1000 );
			$locked =file_is_locked( $fp );
		}
	}
	fclose($fp);

	return $locked;
}

function fre_get_wp_upload_base () {
		$upload_dir = wp_upload_dir();
		if ( ! empty($upload_dir['error']) ) {
			$wp_upload_base = false;
		} else {
			$upload_base = trailingslashit( $upload_dir['basedir'] );
			$wp_upload_base = $upload_base;
		}
		return $wp_upload_base;
	}

 function fre_get_tmp_base ( $append_random_string = true ) {
		// wp_upload_dir() is used to set the base temp folder, under which a
		// 'wpo_wcpdf' folder and several subfolders are created
		//
		// wp_upload_dir() will:
		// * default to WP_CONTENT_DIR/uploads
		// * UNLESS the ‘UPLOADS’ constant is defined in wp-config (http://codex.wordpress.org/Editing_wp-config.php#Moving_uploads_folder)
		//
		// May also be overridden by the wpo_wcpdf_tmp_path filter

		$wp_upload_base = fre_get_wp_upload_base();
		if( $wp_upload_base ) {
			if( $append_random_string && $code = time() ) {
				$tmp_base = $wp_upload_base . 'wpo_wcpdf_'.$code.'/';
			} else {
				$tmp_base = $wp_upload_base . 'wpo_wcpdf/';
			}
		} else {
			$tmp_base = false;
		}

		$tmp_base = apply_filters( 'wpo_wcpdf_tmp_path', $tmp_base );
		if ($tmp_base !== false) {
			$tmp_base = trailingslashit( $tmp_base );
		}

		return $tmp_base;
	}


function fre_invoice_get_tmp_path ( $type = 'attachments' ) {
		$wp_upload_base = fre_get_wp_upload_base();
		$tmp_base = $wp_upload_base . 'wpo_wcpdf/';
		// don't continue if we don't have an upload dir
		if ($tmp_base === false) {
			return false;
		}

		// check if tmp folder exists => if not, initialize
		if ( ! @is_dir( $tmp_base ) || ! wp_is_writable( $tmp_base ) ) {
			//$this->init_tmp();
		}

		if ( empty( $type ) ) {
			return $tmp_base;
		}
		$tmp_path = '';
		switch ( $type ) {
			case 'dompdf':
				$tmp_path = $tmp_base . 'dompdf';
				break;
			case 'font_cache':
			case 'fonts':
				$tmp_path = $tmp_base . 'fonts';
				break;
			case 'attachments':
				$tmp_path = $tmp_base . 'attachments/';
				break;
			default:
				$tmp_path = $tmp_base . $type;
				break;
		}

		// double check for existence, in case tmp_base was installed, but subfolder not created
		if ( ! is_dir( $tmp_path ) ) {
			$dir = mkdir( $tmp_path );

			if ( ! $dir ) {
				update_option( 'wpo_wcpdf_no_dir_error', $tmp_path );
				wcpdf_log_error( "Unable to create folder {$tmp_path}", 'critical' );
				return false;
			}
		} elseif( ! wp_is_writable( $tmp_path ) ) {
			update_option( 'wpo_wcpdf_no_dir_error', $tmp_path );
			wcpdf_log_error( "Temp folder {$tmp_path} not writable", 'critical' );
			return false;
		}

		return apply_filters( 'wpo_wcpdf_tmp_path_{$type}', $tmp_path );;
	}

function fre_get_bulk_document( $document_type, $order_ids, $payer) {
	// return new Fre_Document( $document_type, $order_ids );
	return new \WPO\WC\PDF_Invoices\Documents\Fre_Document( $document_type, $order_ids, $payer );

}
function wcpdf_get_bulk_document11( $document_type, $order_ids ) {
	return new \WPO\WC\PDF_Invoices\Documents\Bulk_Document( $document_type, $order_ids );
}

function fre_pdf_get_file($order_id, $payer ){
	$document_type = 'invoice';
	$email_order_id = $order_id;

	//do_action( 'wpo_wcpdf_before_attachment_creation', $email_order, $email_id, $document_type );
	$attachments = array();
	try {
		// prepare document
		// we use ID to force to reloading the order to make sure that all meta data is up to date.
		// this is especially important when multiple emails with the PDF document are sent in the same session
		//$document = wcpdf_get_document( $document_type, (array) $email_order_id, true );
		// $document = fre_get_bulk_document( $document_type, (array) $email_order_id );
		$document = fre_get_bulk_document($document_type, $email_order_id, $payer);
		if ( !$document ) { // something went wrong, continue trying with other documents
			et_log('die !document');
			wp_die('123');
		}

		$tmp_path = fre_invoice_get_tmp_path('invoice');
		$filename = $document->get_filename();
		$filename = "/invoice-{$order_id}.pdf";
		$pdf_path = $tmp_path . $filename;
		$lock_file =  true ;

		// if this file already exists in the temp path, we'll reuse it if it's not older than 60 seconds
		$max_reuse_age = 60 ;
		if ( file_exists($pdf_path) && $max_reuse_age > 0 ) {
			// get last modification date
			if ($filemtime = filemtime($pdf_path)) {
				$time_difference = time() - $filemtime;
				if ( $time_difference < $max_reuse_age ) {
					// check if file is still being written to
					if ( $lock_file && fre_wait_for_file_lock( $pdf_path ) === false ) {
						$attachments[] = $pdf_path;
						//continue;
					} else {
						// make sure this gets logged, but don't abort process
						wcpdf_log_error( "Attachment file locked (reusing: {$pdf_path})", 'critical' );
					}
				}
			}
		}

		// get pdf data & store
		$pdf_data = $document->get_pdf();

		if ( $lock_file ) {
			file_put_contents ( $pdf_path, $pdf_data, LOCK_EX );
		} else {
			file_put_contents ( $pdf_path, $pdf_data );
		}

		// wait for file lock
		if ( $lock_file && fre_wait_for_file_lock( $pdf_path ) === true ) {
			wcpdf_log_error( "Attachment file locked ({$pdf_path})", 'critical' );
		}

		$attachments[] = $pdf_path;

		do_action( 'wpo_wcpdf_email_attachment', $pdf_path, $document_type, $document );
	} catch ( \Exception $e ) {
		wcpdf_log_error( $e->getMessage(), 'critical', $e );

	} catch ( \Dompdf\Exception $e ) {
		wcpdf_log_error( 'DOMPDF exception: '.$e->getMessage(), 'critical', $e );

	} catch ( \Error $e ) {
		wcpdf_log_error( $e->getMessage(), 'critical', $e );

	}

	return $attachments;
}

function fre_pdf_get_html($order_id, $user){
	$order     	= new AE_Order( $order_id );
  	$order_pay 	= $order->get_order_data();
  	$product 	= array_pop( $order_pay['products'] );

  	$order_date = $order_pay['created_date']; //2021-01-14 14:14:17
	$sku 		= $product['ID'];
  	$pack_des 	= $product['NAME'];
  	$type 		= $product['TYPE']; //fre_credit_plan
  	$des 		= $pack_des;

	ob_start(); ?>
	<link rel="stylesheet" type="text/css" href="<?php echo plugins_url();?>/woocommerce-pdf-invoices-packing-slips/templates/Simple/style.css" />

	<table class="head container">
		<tr>
			<td class="header">
				<?php echo fre_logo();?> &nbsp; &nbsp; &nbsp;
			</td>
			<td class="shop-info">
				<div class="shop-name"><h3>ORGANIZLY SRL-D</h3></div>
				<div class="shop-address">RO 39290937<br /> J26/681/2018</div>
			</td>
		</tr>
	</table>

	<h1 class="document-type-label">Invoice</h1>

	<div class="bottom-spacer"><br /> &nbsp; </div>
	<table class="order-data-addresses">
		<tr>
			<td class="address billing-address">
				<?php

				//company_name
				$company_name 	= get_user_meta($user->ID,'company_name', true);
				$cifcui 		= get_user_meta($user->ID,'cifcui', true);
				$phone 			= get_user_meta($user->ID,'phone', true);
				?>
				<h3><?php _e( 'Billing Address:', 'et_domain' ); ?></h3>
				<div class="billing-name"><?php echo $user->display_name;?></div>

				<div class="billing-email"><?php echo $user->user_email;?></div>

				<?php if($phone){?>
					<div class="billing-phone"><?php echo $phone;?></div>
				<?php } ?>
				<?php if($cifcui){ ?>
					<div class="billing-email"><?php echo $cifcui;?></div>
				<?php } ?>

			</td>
			<td class="address shipping-address">
				&nbsp;
			</td>
			<td class="order-data">
				<table>

					<tr class="invoice-number">
						<th><?php _e( 'Invoice Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
						<td>#<?php echo $order_id;?></td>
					</tr>


					<tr class="invoice-date">
						<th><?php _e( 'Invoice Date:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
						<td><?php  echo date( 'M d, Y', strtotime($order_date) ); ?></td>
					</tr>
					<!--
					<tr class="order-number ">
						<th><?php _e( 'Order Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
						<td> #<?php echo $order_id;?></td>
					</tr>
					!-->

					<tr class="payment-method">
						<th><?php _e( 'Payment Gateway:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
						<td><?php echo $order_pay['payment'];?></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>


	<table class="order-details">
		<thead>
			<tr>
				<th class="product"><?php _e('Product', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
				<th class="quantity"><?php _e('Quantity', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
				<th class="price"><?php _e('Price', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
			</tr>
		</thead>
		<tbody>

			<tr>
				<td class="product">
					<?php $description_label = __( 'Description', 'woocommerce-pdf-invoices-packing-slips' ); // registering alternate label translation ?>
					<span class="item-name"><?php echo $des;?></span>


					<dl class="meta">

						<dt class="sku"><?php _e( 'SKU:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt>
						<dd class="sku"><?php echo $sku; ?></dd>
						<?php if($type == "fre_credit_plan"){?>
							<dt class="sku"><?php _e( 'Type:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt>
							<dd class="sku"> Deposit Credit</dd>
						<?php } ?>

					</dl>

				</td>
				<td class="quantity">1</td>
				<td class="price"><?php echo $order_pay['total'];; ?></td>
			</tr>

		</tbody>
		<tfoot>
			<tr class="no-borders">
				<td class="no-borders">
					<div class="document-notes">

						<h3><?php _e( 'Notes', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
						Note here
					</div>
					<!--
					<div class="customer-notes">
						<h3><?php _e( 'Customer Notes', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
							Customer Notes
					</div>
					!-->
				</td>
				<td class="no-borders" colspan="2">
					<table class="totals">
						<tfoot>

							<tr>
								<td class="no-borders"></td>
								<th class="description">Total</th>
								<td class="price"><span class="totals-price"><?php echo $order_pay['total'].'('.$order_pay['currency'].')'; ?></span></td>
							</tr>

						</tfoot>
					</table>
				</td>
			</tr>
		</tfoot>
	</table>

	<div class="bottom-spacer"></div>

	<div id="footer">
		this is footer
	</div><!-- #letter-footer -->

	<?php
	$html = ob_get_contents();
	ob_end_clean();
	return $html;
}
