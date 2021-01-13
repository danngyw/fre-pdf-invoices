<?php

function fre_pdf_debug(){
	wcpdf_get_pdf_maker();
}
add_action('wp_footer','fre_pdf_debug');