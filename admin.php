<?php
function fre_pdf_admin_notice__success() {
	if(class_exists('WPO_WCPDF')){
		return ;
	}
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e( 'Please install and active the plug "WooCommerce PDF Invoices & Packing Slips" First. Link to plugin <a target="_blank" href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/"> Detail </a>', 'sample-text-domain' ); ?></p>
    </div>
    <?php
}
add_action( 'admin_notices', 'fre_pdf_admin_notice__success' );