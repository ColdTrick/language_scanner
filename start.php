<?php
	
	function language_scanner_init() {
	
		// register library 
		elgg_register_library('language_scanner:functions', dirname(__FILE__) ."/lib/functions.php");
	
		elgg_register_admin_menu_item('administer', 'language_scanner', 'administer_utilities');
	}
	
	elgg_register_event_handler("init", "system", "language_scanner_init");
	elgg_register_event_handler("pagesetup", "system", "language_scanner_pagesetup");