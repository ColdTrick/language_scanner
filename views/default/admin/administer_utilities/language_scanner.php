<?php

$plugins = elgg_get_plugins('all');

$ordered_plugins = [];
foreach ($plugins as $plugin) {
	$output = elgg_view('output/url', [
		'icon' => 'eye',
		'text' => $plugin->getDisplayName(),
		'href' => elgg_http_add_url_query_elements('ajax/view/language_scanner/report', [
			'plugin_name' => $plugin->getID(),
		]),
		'class' => 'elgg-lightbox',
		'data-colorbox-opts' => json_encode([
			'width' => 750,
			'maxHeight' => '80%',
		]),
	]);
	
	$description = $plugin->getDescription();
	if (!empty($description)) {
		$output .= elgg_format_element('span', ['class' => ['elgg-subtext', 'mlm']], $description);
	}
	
	$ordered_plugins[$plugin->getDisplayName()] = elgg_format_element('li', [], $output);
}

uksort($ordered_plugins, 'strnatcasecmp');

$body = elgg_format_element('ul', [], implode(PHP_EOL, $ordered_plugins));

echo elgg_view_module('inline', elgg_echo('language_scanner:admin:pick_plugin'), $body);
