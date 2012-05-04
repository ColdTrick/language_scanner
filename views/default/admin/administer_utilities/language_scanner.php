<?php

	elgg_load_library('language_scanner:functions');

	if($plugin_name = get_input('plugin_name')){
		$language_scanner_result = language_scanner_scan_language($plugin_name);
		$language_scanner_missing_key_result = language_scanner_scan_missing_key($plugin_name);

		if ($plugin_name == 'core') {
			$title = elgg_echo("language_scanner:result:title", array('core'));
		} else {
			$plugin = elgg_get_plugin_from_id($plugin_name);
			$title = elgg_echo("language_scanner:result:title", array($plugin->getFriendlyName()));
		}

		$body .= '<h3>' . elgg_echo("language_scanner:result:total_keys", array($language_scanner_result['start_count'])) . "</h3><hr/>";

		if($language_scanner_result['unused']) {
			$body .= '<h4 class="mtm mbs">' . elgg_echo("language_scanner:result:unused_keys", array($language_scanner_result['end_count'])) . '</h4><ul>';
			
			foreach($language_scanner_result['unused'] as $key => $value) {
				$body .= '<li>' . strip_tags($key) . '&nbsp;<span class="elgg-subtext">' . $value .'</span></li>';
			}
			
			$body .= '</ul>';
		}
		
		if($language_scanner_result['skipped']) {
			$body .= '<h4 class="mtm mbs">' . elgg_echo('language_scanner:result:skipped_keys') . '</h4><ul>';
			
			foreach($language_scanner_result['skipped'] as $key => $value) {
				$body .= '<li>' . strip_tags($key) . '&nbsp;<span class="elgg-subtext">' . $value .'</span></li>';
			}
			
			$body .= '</ul>';
		}
		
		$body .= '<h3 class="mtm">' . elgg_echo("language_scanner:result:total_keys_in_files", array($language_scanner_missing_key_result['keys_in_code_found_count'] )) . "</h3><hr />";
		
		if($language_scanner_missing_key_result['missing_key']) {
			$body .= '<h4 class="mtm mbs">' . elgg_echo('language_scanner:result:missing_keys', array(count($language_scanner_missing_key_result['missing_key']))) . '</h4><ul>';
			
			foreach($language_scanner_missing_key_result['missing_key'] as $key => $value) {
				$body .= '<li>' . strip_tags($key) . '&nbsp;<span class="elgg-subtext">' . $value .'</span></li>';
			}
			
			$body .= '</ul>';
		}
		
		if($language_scanner_missing_key_result['missing_key_with_var']) {
			$body .= '<h4 class="mtm mbs">' . elgg_echo('language_scanner:result:keys_with_var', array(count($language_scanner_missing_key_result['missing_key_with_var']))) . '</h4><ul>';
			
			foreach($language_scanner_missing_key_result['missing_key_with_var'] as $key => $value) {
				$body .= '<li>' . strip_tags($key) . '&nbsp;<span class="elgg-subtext">' . $value .'</span></li>';
			}
			
			$body .= '</ul>';
		}

		echo elgg_view_module("inline", $title, $body);
		
	} elseif($plugins = elgg_get_plugins("all")) {
	
		$base_url = $vars["url"] . "admin/administer_utilities/language_scanner?plugin_name=";

		$title = elgg_echo("language_scanner:admin:pick_plugin");

		$body = "<ul>";
		foreach($plugins as $plugin) {
			$body .= "<li>" . elgg_view("output/url", array("text" => $plugin->getFriendlyName(), "href" => $base_url . $plugin->getID())) . "</li>";
		}

		// adding core
		$body .= "<li>" . elgg_view("output/url", array("text" => 'Core <i>!!! It takes time !</i>', "href" => $base_url . 'core')) . "</li>";
		
		$body .= "</ul>";

		echo elgg_view_module("inline", $title, $body);
		
	} else {
		echo elgg_echo("notfound");
	}