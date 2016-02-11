<?php

$plugin_name = get_input('plugin_name');
if (empty($plugin_name)) {
	return;
}

$plugin = elgg_get_plugin_from_id($plugin_name);
if (empty($plugin)) {
	return;
}

$title = elgg_echo('language_scanner:result:title', [$plugin->getFriendlyName()]);

$plugin_report = new \ColdTrick\LanguageScanner\PluginReport($plugin->getGUID());

$body = elgg_echo('language_scanner:result:total_keys', [$plugin_report->getTotalKeyCount()]) . '<br />';
$body .= elgg_view('language_scanner/report/unused_translations', ['plugin_report' => $plugin_report]);
$body .= elgg_view('language_scanner/report/missing_translations', ['plugin_report' => $plugin_report]);
$body .= elgg_view('language_scanner/report/core_suggestions', ['plugin_report' => $plugin_report]);

echo elgg_view_module('inline', $title, $body);
