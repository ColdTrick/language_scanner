<?php
/**
 * List translation keys used in the code, but missing in the translation file
 */

use ColdTrick\LanguageScanner\LanguageReport;

$report = elgg_extract('report', $vars);
if (!$report instanceof LanguageReport) {
	return;
}

$untranslatable = $report->getUntranslatableCodeLanguageKeys();

$body = elgg_echo('language_scanner:result:code_keys', [$report->countCodeLanguageKeys()]) . '<br />';
$body .= elgg_echo('language_scanner:result:code_keys:untranslatable', [count($untranslatable)]) . '<br />';

if (!empty($untranslatable)) {
	$list_items = '';
	$list_options = [];
	$i = 0;
	foreach ($untranslatable as $value) {
		$i++;
		if ($i === 5) {
			$list_options['class'] = 'hidden';
			$list_items .= elgg_format_element('li', [], elgg_view('output/url', [
				'text' => elgg_echo('language_scanner:result:show_more'),
				'href' => false,
				'onclick' => '$(this).parents("ul").find(".hidden").show(); $(this).parent().hide(); return false;',
			]));
		}
		
		if (str_contains($value, ' ') || $value !== strtolower($value)) {
			$value = elgg_format_element('strong', [], $value);
		}
		
		$list_items .= elgg_format_element('li', $list_options, $value);
	}
	
	$body .= elgg_format_element('ul', ['class' => 'mts'], $list_items);
}

echo elgg_view_module('info', elgg_echo('language_scanner:result:missing_translations:title'), $body);
