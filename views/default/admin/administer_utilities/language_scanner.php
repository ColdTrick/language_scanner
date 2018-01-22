<?php

elgg_load_js('lightbox');
elgg_load_css('lightbox');

$plugins = elgg_get_plugins('all');

$ordered_plugins = [];
foreach ($plugins as $plugin) {
	$friendly_name = $plugin->getDisplayName();
	$ordered_plugins[$friendly_name] = elgg_format_element('li', [], elgg_view('output/url', [
		'text' => $friendly_name,
		'href' => "ajax/view/language_scanner/report?plugin_name={$plugin->getID()}",
		'class' => 'elgg-lightbox',
		'data-colorbox-opts' => json_encode([
			'width' => 750,
			'maxHeight' => '80%',
		]),
	]));
}

uksort($ordered_plugins, 'strcasecmp');

$body = elgg_format_element('ul', [], implode('', $ordered_plugins));

echo elgg_view_module('inline', elgg_echo('language_scanner:admin:pick_plugin'), $body);
