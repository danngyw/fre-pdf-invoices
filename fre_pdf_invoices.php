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

define ('FRE_PDF_PATH', dirname(__FILE__) );
require_once FRE_PDF_PATH.'/debug.php';
require_once FRE_PDF_PATH.'/admin.php';
Class Fre_PDF_Invoices{
	function __construct(){

	}
}
new Fre_PDF_Invoices();
// wcpdf_get_pdf_maker