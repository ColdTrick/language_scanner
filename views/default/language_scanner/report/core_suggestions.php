<?php

$plugin_report = elgg_extract('plugin_report', $vars);

// suggestions for replacing plugin translations with core translations

$suggestions = $plugin_report->getSuggestions();

$list_items = '';
if (!empty($suggestions)) {
	foreach ($suggestions as $original_key => $suggested_key) {
		
		$suggestion = elgg_format_element('i', [], $suggested_key . ' [' . elgg_echo($suggested_key) . ']');
		$original = elgg_format_element('i', [], $original_key . ' [' . elgg_echo($original_key) . ']');
	
		$list_items .= elgg_format_element('li', [], elgg_echo('language_scanner:result:core_suggestions:suggestion', [$suggestion, $original]));
	}
} else {
	$list_items = elgg_echo('language_scanner:result:core_suggestions:no_suggestion');
}

$body = elgg_format_element('ul', ['class' => 'mts'], $list_items);

echo elgg_view_module('info', elgg_echo('language_scanner:result:core_suggestions:title'), $body);