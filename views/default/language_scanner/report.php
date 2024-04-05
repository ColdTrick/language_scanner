<?php

use ColdTrick\LanguageScanner\LanguageReport;
use ColdTrick\LanguageScanner\PluginReport;

$plugin_name = get_input('plugin_name');
if (empty($plugin_name)) {
	return;
}

if ($plugin_name === 'core') {
	$display_name = elgg_echo('language_scanner:report:core');
	
	$report = new LanguageReport();
} else {
	$plugin = elgg_get_plugin_from_id($plugin_name);
	if (empty($plugin)) {
		return;
	}
	
	$display_name = $plugin->getDisplayName();
	$report = new PluginReport($plugin);
}

$report->generateReport();

$title = elgg_echo('language_scanner:result:title', [$display_name]);

$body = elgg_echo('language_scanner:result:total_keys', [$report->getTotalKeyCount()]) . '<br />';
$body .= elgg_view('language_scanner/report/unused_translations', ['report' => $report]);
$body .= elgg_view('language_scanner/report/missing_translations', ['report' => $report]);
$body .= elgg_view('language_scanner/report/core_suggestions', ['report' => $report]);
$body .= elgg_view('language_scanner/report/system_messages', ['report' => $report]);

echo elgg_view_module('inline', $title, $body);
