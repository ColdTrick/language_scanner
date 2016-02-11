<?php

/**
 * Init function for Language Scanner
 *
 * @return void
 */
function language_scanner_init() {
	elgg_register_admin_menu_item('administer', 'language_scanner', 'administer_utilities');
	
	elgg_register_ajax_view('language_scanner/report');
}

elgg_register_event_handler('init', 'system', 'language_scanner_init');
