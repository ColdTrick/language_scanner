<?php

// usage of elgg_echo found in the code but no language key exists

$plugin_report = elgg_extract('plugin_report', $vars);
$untranslatable = $plugin_report->getUntranslatableCodeLanguageKeys();

$body = elgg_echo('language_scanner:result:code_keys', [$plugin_report->countCodeLanguageKeys()]) . '<br />';
$body .= elgg_echo('language_scanner:result:code_keys:untranslatable', [count($untranslatable)]) . '<br />';

if ($untranslatable) {
	$list_items = '';
	$list_options = [];
	$i = 0;
	foreach ($untranslatable as $value) {
		$i++;
		if ($i == 5) {
			$list_options['class'] = 'hidden';
			$list_items .= elgg_format_element('li', [], elgg_view('output/url', [
				'text' => elgg_echo('language_scanner:result:show_more'),
				'href' => '#',
				'onclick' => '$(this).parents("ul").find(".hidden").show(); $(this).parent().hide(); return false;',
			]));
		}
		
		if ((strpos($value, ' ') !== false) || ($value !== strtolower($value))) {
			$value = elgg_format_element('strong', [], $value);
		}
		$list_items .= elgg_format_element('li', $list_options, $value);
	}
	
	$body .= elgg_format_element('ul', ['class' => 'mts'], $list_items);
}

echo elgg_view_module('inline', elgg_echo('language_scanner:result:missing_translations:title'), $body);