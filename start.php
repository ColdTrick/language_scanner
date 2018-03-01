<?php

/**
 * Init function for Language Scanner
 *
 * @return void
 */
function language_scanner_init() {
	elgg_register_menu_item('page', [
		'name' => 'administer_utilities:language_scanner',
		'text' => elgg_echo('admin:administer_utilities:language_scanner'),
		'href' => 'admin/administer_utilities/language_scanner',
		'context' => 'admin',
		'parent_name' => 'administer_utilities',
		'section' => 'administer',
	]);
	
	elgg_register_ajax_view('language_scanner/report');
}

elgg_register_event_handler('init', 'system', 'language_scanner_init');
