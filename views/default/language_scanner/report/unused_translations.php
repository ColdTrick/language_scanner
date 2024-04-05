<?php
/**
 * Show a list of language keys which are present in the language file but not used in the code
 */

use ColdTrick\LanguageScanner\LanguageReport;

$report = elgg_extract('report', $vars);
if (!$report instanceof LanguageReport) {
	return;
}

$body = elgg_echo('language_scanner:result:unused_keys', [$report->getUnusedKeyCount()]);

$unused = $report->getUnusedKeys();
if (!empty($unused)) {
	$list_items = '';
	
	$list_options = [];
	$i = 0;
	foreach ($unused as $key => $value) {
		$i++;
		if ($i === 5) {
			$list_options['class'] = 'hidden';
			$list_items .= elgg_format_element('li', [], elgg_view('output/url', [
				'text' => elgg_echo('language_scanner:result:show_more'),
				'href' => false,
				'onclick' => '$(this).parents("ul").find(".hidden").show(); $(this).parent().hide(); return false;',
			]));
		}
		
		$list_items .= elgg_format_element('li', $list_options, strip_tags($key));
	}

	$body .= elgg_format_element('ul', ['class' => 'mts'], $list_items);
}

echo elgg_view_module('info', elgg_echo('language_scanner:result:unused_translations:title'), $body);
