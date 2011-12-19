<?php

	include(dirname(__FILE__) . '/lib/functions.php');
	
	function language_scanner_init()
	{		
		
		elgg_register_menu_item('page', array(	'name' 			=> 'language_scannerw',
												'href' 			=> 'admin/language/scan',
												'text' 			=> elgg_echo('language_scanner'),
												'context' 		=> 'admin',
												'section' 		=> 'administer'
											));
	}
	
	function language_scanner_pagesetup()
	{
		
	}
	
	elgg_register_event_handler("init", "system", "language_scanner_init");
	elgg_register_event_handler("pagesetup", "system", "language_scanner_pagesetup");