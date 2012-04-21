<?php

	elgg_load_library('language_scanner:functions');

	if($plugin_name = get_input('plugin_name')){
		$plugin = elgg_get_plugin_from_id($plugin_name);
		$language_scanner_result = language_scanner_scan_language($plugin_name);

		$title = elgg_echo("language_scanner:result:title", array($plugin->getFriendlyName()));

		$body = elgg_echo("language_scanner:result:total_keys", array($language_scanner_result['start_count'])) . "<br />";
		$body .= elgg_echo("language_scanner:result:unused_keys", array($language_scanner_result['end_count'])) . "<hr />";

		if($language_scanner_result['unused']) {
			$body .= '<ul>';
			
			foreach($language_scanner_result['unused'] as $key => $value) {
				$body .= '<li>' . strip_tags($key) . '</li>';
			}
			
			$body .= '</ul>';
		}
		
		if($language_scanner_result['skipped']) {
			$body .= '<h3 class="mtm mbs">' . elgg_echo('language_scanner:result:skipped_keys') . '</h3><ul>';
			
			foreach($language_scanner_result['skipped'] as $key => $value) {
				$body .= '<li>' . strip_tags($key) . '</li>';
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

		$body .= "</ul>";

		echo elgg_view_module("inline", $title, $body);
	} else {
		echo elgg_echo("notfound");
	}