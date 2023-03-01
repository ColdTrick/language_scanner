<?php
/**
 * Show a list of untranslatable system messages (forgot to add elgg_echo())
 */

use ColdTrick\LanguageScanner\PluginReport;

$plugin_report = elgg_extract('plugin_report', $vars);
if (!$plugin_report instanceof PluginReport) {
	return;
}

$system_messages = $plugin_report->getUntranslatableSystemMessages();
if (empty($system_messages)) {
	return;
}

$body = '';

$list_items = '';
$list_options = [];
$i = 0;
foreach ($system_messages as $value) {
	$i++;
	if ($i === 5) {
		$list_options['class'] = 'hidden';
		$list_items .= elgg_format_element('li', [], elgg_view('output/url', [
			'text' => elgg_echo('language_scanner:result:show_more'),
			'href' => false,
			'onclick' => '$(this).parents("ul").find(".hidden").show(); $(this).parent().hide(); return false;',
		]));
	}
	
	$list_items .= elgg_format_element('li', $list_options, $value);
}

$body .= elgg_format_element('ul', ['class' => 'mts'], $list_items);

echo elgg_view_module('info', elgg_echo('language_scanner:result:system_messages:title'), $body);
